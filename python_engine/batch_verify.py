#untuk Worker verifikasi tabel massal
import sys
import os
import json
import time
import threading
import traceback
from concurrent.futures import ThreadPoolExecutor, as_completed

sys.path.append(os.path.dirname(os.path.abspath(__file__)))
from compare_table import compare_head_to_head

def run_batch(pdf_path, matched_json_path, db_json_path, state_json_path, progress_json_path, tolerance=0.0, max_workers=6, source_filter="all"):
    stop_file = progress_json_path + ".stop"
    debug_log = progress_json_path.replace("dda_progress_", "dda_batch_debug_").replace(".json", ".log")
    
    def log_debug(msg):
        try:
            with open(debug_log, "a", encoding="utf-8") as lf:
                lf.write(f"[{time.strftime('%H:%M:%S')}] {msg}\n")
        except Exception:
            pass
    
    log_debug("=" * 60)
    log_debug(f"BATCH START: {time.strftime('%Y-%m-%d %H:%M:%S')}")
    log_debug(f"PDF: {pdf_path}")
    log_debug(f"Matched: {matched_json_path}")
    log_debug(f"DB: {db_json_path}")
    log_debug(f"State: {state_json_path}")
    log_debug(f"Workers: {max_workers}")
    
    if os.path.exists(stop_file):
        try:
            os.remove(stop_file)
        except Exception:
            pass

    if not os.path.exists(matched_json_path):
        log_debug(f"ERROR: Matched JSON not found: {matched_json_path}")
        print(json.dumps({"status": "error", "message": f"Matched JSON not found: {matched_json_path}"}))
        return 1

    with open(matched_json_path, "r", encoding="utf-8") as f:
        matched_data = json.load(f)

    # Load existing state (or start fresh)
    def _load_disk_state(fallback=None):
        if os.path.exists(state_json_path):
            try:
                with open(state_json_path, "r", encoding="utf-8") as f:
                    loaded = json.load(f)
                if isinstance(loaded, dict):
                    return loaded
                log_debug(f"WARNING: state file was not a dict, was {type(loaded).__name__}.")
            except Exception as e:
                log_debug(f"WARNING: Could not load state file: {e}.")
        return dict(fallback) if isinstance(fallback, dict) else {}

    state = _load_disk_state()
    batch_start_epoch = time.time()
    batch_start_ts = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(batch_start_epoch))

    tables = matched_data.get("matched_in_pdf", matched_data.get("matched_tables", []))
    if source_filter == "dinamis":
        tables = [t for t in tables if t.get("source_type") == "dinamis" or int(t.get("is_dynamic", 0) or 0) == 1]
    elif source_filter == "api":
        tables = [t for t in tables if t.get("source_type") == "api" and int(t.get("is_dynamic", 0) or 0) == 0]
    elif source_filter == "unverified":
        def is_unverified(t):
            t_num = (t.get("nomor_tabel") or "").strip()
            if not t_num:
                return False
            if t.get("dynamic_empty", False):
                return False
            st_info = state.get(t_num, {})
            st = st_info.get("status", "unverified") if isinstance(st_info, dict) else "unverified"
            return st in ("unverified", "pending", "")
        tables = [t for t in tables if is_unverified(t)]
    if not tables:
        log_debug("ERROR: No matched tables found")
        print(json.dumps({"status": "error", "message": "No matched tables found to verify"}))
        return 1

    total = len(tables)
    log_debug(f"Total tables to verify: {total}")

    # Preload DB JSON and Index JSON once into memory to eliminate 359 redundant disk reads
    db_tables_cached = None
    if os.path.exists(db_json_path):
        try:
            with open(db_json_path, "r", encoding="utf-8") as f:
                db_tables_cached = json.load(f)
            log_debug("Preloaded db_json successfully into memory.")
        except Exception as e:
            log_debug(f"WARNING: Could not preload db_json: {e}")

    index_cached = None
    index_cache_path = pdf_path + ".index.json"
    if os.path.exists(index_cache_path):
        try:
            with open(index_cache_path, "r", encoding="utf-8") as f:
                index_cached = json.load(f)
            log_debug("Preloaded index_json successfully into memory.")
        except Exception as e:
            log_debug(f"WARNING: Could not preload index_json: {e}")

    db_lock = threading.Lock()
    db_state_ref = {
        "tables": db_tables_cached,
        "snapshot_epoch": time.time(),
        "mtime": os.path.getmtime(db_json_path) if (db_json_path and os.path.exists(db_json_path)) else 0.0,
    }

    # Single lock for ALL file operations and counter updates
    file_lock = threading.Lock()
    completed = [0]  # Use list for mutability in closures
    pending_updates = {}  # {t_num: (clean_info, eff_epoch, eff_ts)}

    def safe_write_json(filepath, data):
        """Atomic write: write to .tmp then rename with Windows lock retry."""
        tmp_path = filepath + f".tmp_{os.getpid()}"
        try:
            content = json.dumps(data, indent=2, ensure_ascii=False)
            with open(tmp_path, "w", encoding="utf-8") as f:
                f.write(content)
                f.flush()
                os.fsync(f.fileno())
            
            # On Windows, os.replace can raise PermissionError if another process (like PHP) has the file open
            for attempt in range(8):
                try:
                    os.replace(tmp_path, filepath)
                    return True
                except (PermissionError, OSError):
                    time.sleep(0.04 * (attempt + 1))
            
            # Final attempt
            os.replace(tmp_path, filepath)
            return True
        except Exception as e:
            log_debug(f"ERROR writing {filepath}: {e}")
            if os.path.exists(tmp_path):
                try: os.remove(tmp_path)
                except Exception: pass
            return False

    last_state_write = [time.time()]
    unflushed_state_count = [0]

    def write_progress(current_item=None, is_running=True):
        pct = int((completed[0] / total) * 100) if total > 0 else 100
        pct = min(pct, 100)
        prog_data = {
            "is_running": is_running, "completed": completed[0], "total": total,
            "percent": pct, "updated_at": time.time(), "last_item": current_item
        }
        safe_write_json(progress_json_path, prog_data)

    def _is_disk_entry_newer(existing, eff_epoch, eff_ts):
        if not isinstance(existing, dict):
            return False
        ext_epoch = existing.get("updated_epoch")
        ext_ts = str(existing.get("updated_at") or "")
        if ext_epoch is not None:
            try:
                return float(ext_epoch) > float(eff_epoch)
            except (TypeError, ValueError):
                pass
        return bool(ext_ts and str(eff_ts) and ext_ts > str(eff_ts))

    def _merge_and_flush_state(force=False):
        now = time.time()
        if not force and not pending_updates:
            return
        if not force and len(pending_updates) < 5 and (now - last_state_write[0]) < 2.0:
            return
        disk_state = _load_disk_state(fallback=state)
        for t_key, (clean_info, eff_epoch, eff_ts) in list(pending_updates.items()):
            existing = disk_state.get(t_key)
            if _is_disk_entry_newer(existing, eff_epoch, eff_ts):
                log_debug(
                    f"SKIP stale overwrite for {t_key}: disk={existing.get('updated_at')} > eval_ref={eff_ts}"
                )
            else:
                disk_state[t_key] = clean_info
                state[t_key] = clean_info
        if safe_write_json(state_json_path, disk_state):
            pending_updates.clear()
            last_state_write[0] = now
            unflushed_state_count[0] = 0

    def write_state_and_progress(result_info=None, is_running=True):
        """Write progress frequently, and merge/flush pending state writes safely."""
        with file_lock:
            prog_info = None
            if result_info:
                t_num = result_info["nomor_tabel"]
                eff_epoch = result_info.pop("_ref_epoch", batch_start_epoch)
                eff_ts = result_info.pop("_ref_ts", batch_start_ts)
                pending_updates[t_num] = (result_info, eff_epoch, eff_ts)
                prog_info = result_info
                completed[0] = min(completed[0] + 1, total)
                unflushed_state_count[0] += 1

            # Flush state every 5 items, atau jika > 2.0 detik berlalu, atau saat batch selesai
            if pending_updates and (not is_running or unflushed_state_count[0] >= 5 or (time.time() - last_state_write[0]) >= 2.0):
                _merge_and_flush_state(force=not is_running)

            write_progress(prog_info, is_running)

    # Initial write
    write_progress(is_running=True)
    
    def verify_single(table_item):
        t_num = table_item.get("nomor_tabel", "").strip()
        id_db = str(table_item.get("id_db") or "").strip()
        if not t_num:
            log_debug(f"SKIP: empty nomor_tabel in item: {json.dumps(table_item, ensure_ascii=False)[:200]}")
            return None

        if os.path.exists(stop_file):
            return None

        try:
            # Refresh snapshot in-memory DB jika db_json dimodifikasi di tengah batch (mis. user klik Samakan Data)
            cur_db_tables = db_state_ref["tables"]
            eval_ref_epoch = db_state_ref["snapshot_epoch"]
            if db_json_path and os.path.exists(db_json_path):
                try:
                    disk_mtime = os.path.getmtime(db_json_path)
                    if disk_mtime > db_state_ref["mtime"]:
                        with db_lock:
                            disk_mtime2 = os.path.getmtime(db_json_path)
                            if disk_mtime2 > db_state_ref["mtime"]:
                                with open(db_json_path, "r", encoding="utf-8") as df:
                                    db_state_ref["tables"] = json.load(df)
                                db_state_ref["mtime"] = disk_mtime2
                                db_state_ref["snapshot_epoch"] = time.time()
                                log_debug(f"Reloaded updated db_json mid-batch (mtime={disk_mtime2})")
                            cur_db_tables = db_state_ref["tables"]
                            eval_ref_epoch = db_state_ref["snapshot_epoch"]
                except Exception:
                    pass

            eval_ref_epoch = min(eval_ref_epoch, time.time())
            eval_ref_ts = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(eval_ref_epoch))

            # INSTANT BYPASS UNTUK TABEL KOSONG:
            # Jika tabel dinamis memang kosong di DB (dynamic_empty: True), langsung beri status api_empty dalam 0ms
            is_dyn = (table_item.get("source_type") == "dinamis" or int(table_item.get("is_dynamic", 0) or 0) == 1)
            if is_dyn and table_item.get("dynamic_empty", False):
                return {
                    "nomor_tabel": t_num,
                    "id_db": id_db,
                    "status": "api_empty",
                    "diff_count": 0,
                    "match_count": 0,
                    "reason": "Data nilai Tabel Dinamis belum diisi di database.",
                    "updated_at": time.strftime("%Y-%m-%d %H:%M:%S"),
                    "updated_epoch": round(time.time(), 4),
                    "_ref_epoch": eval_ref_epoch,
                    "_ref_ts": eval_ref_ts,
                }

            # Small random delay to stagger API requests
            if table_item.get("source_type") != "dinamis":
                time.sleep(0.05 * (hash(t_num) % 5))
            
            res = compare_head_to_head(
                pdf_path, t_num, db_json_path,
                tolerance=tolerance, id_db=id_db,
                preloaded_db_tables=cur_db_tables,
                preloaded_index=index_cached
            )
            
            if res.get("status") == "success":
                summary = res.get("summary", {})
                total_diffs = int(summary.get("total_diffs", len(res.get("diffs", []))))
                total_matches = int(summary.get("total_matches", len(res.get("matches", []))))
                total_pdf_only = int(summary.get("total_pdf_only", len(res.get("pdf_only", []))))
                
                link_str = str(table_item.get("link_tabel", "") or "")
                is_api_or_dyn = (
                    "portal" in link_str.lower()
                    or "api" in link_str.lower()
                    or res.get("source_type") in ("API Satudata", "Tabel Dinamis")
                )
                
                if total_diffs > 0:
                    status = "diff"
                    diff_count = total_diffs
                elif total_matches == 0:
                    if total_pdf_only > 0:
                        status = "pdf_only"
                        diff_count = total_pdf_only
                    elif res.get("is_api_empty"):
                        status = "api_empty"
                        diff_count = 0
                    elif is_api_or_dyn:
                        status = "api_empty"
                        diff_count = 0
                    else:
                        status = "diff"
                        diff_count = "!"
                else:
                    status = "match"
                    diff_count = 0
                match_count = total_matches
                msg_str = ""
            elif (
                res.get("is_year_unavailable")
                or res.get("error_kind") == "year_unavailable"
            ):
                status = "api_year_mismatch"
                diff_count = 0
                match_count = 0
                msg_str = res.get("message", "")
            elif (
                res.get("is_api_empty")
                or res.get("is_link_empty")
                or "tidak ada link" in res.get("message", "").lower()
                or "tidak ada data dalam api" in res.get("message", "").lower()
                or "belum diisi" in res.get("message", "").lower()
            ):
                status = "api_empty"
                diff_count = 0
                match_count = 0
                msg_str = res.get("message", "")
            else:
                err_msg = res.get("message", "unknown error")
                log_debug(f"TABLE {t_num}: non-success result: {err_msg}")
                status = "error"
                diff_count = 0
                match_count = 0
                msg_str = err_msg
        except Exception as e:
            log_debug(f"TABLE {t_num}: EXCEPTION: {type(e).__name__}: {e}")
            log_debug(traceback.format_exc())
            status = "error"
            diff_count = 0
            match_count = 0
            msg_str = str(e)

        info = {
            "nomor_tabel": t_num, "id_db": id_db, "status": status, "diff_count": diff_count,
            "match_count": match_count, "updated_at": time.strftime("%Y-%m-%d %H:%M:%S"),
            "updated_epoch": round(time.time(), 4),
            "_ref_epoch": eval_ref_epoch,
            "_ref_ts": eval_ref_ts,
        }
        if msg_str:
            info["message"] = msg_str
        return info

    error_count = 0
    success_count = 0
    
    # Fast path: Tabel dinamis yang memang belum ada datanya di DB langsung diberi status api_empty di awal (0ms)
    active_tables = []
    for item in tables:
        t_num = item.get("nomor_tabel", "").strip()
        is_dyn = (item.get("source_type") == "dinamis" or int(item.get("is_dynamic", 0) or 0) == 1)
        if is_dyn and item.get("dynamic_empty", False):
            res_info = {
                "nomor_tabel": t_num,
                "status": "api_empty",
                "diff_count": 0,
                "match_count": 0,
                "reason": "Data nilai Tabel Dinamis belum diisi di database.",
                "updated_at": time.strftime("%Y-%m-%d %H:%M:%S"),
                "updated_epoch": round(time.time(), 4),
            }
            pending_updates[t_num] = (res_info, batch_start_epoch, batch_start_ts)
            completed[0] += 1
            error_count += 1
        else:
            active_tables.append(item)

    if completed[0] > 0:
        log_debug(f"Fast-path completed for {completed[0]} empty tables instantly.")
        write_state_and_progress(None, is_running=True)

    with ThreadPoolExecutor(max_workers=max_workers) as executor:
        futures = {executor.submit(verify_single, item): item for item in active_tables}
        
        for future in as_completed(futures):
            if os.path.exists(stop_file):
                log_debug("STOP signal detected, shutting down...")
                try:
                    os.remove(stop_file)
                except Exception:
                    pass
                executor.shutdown(wait=False, cancel_futures=True)
                break

            try:
                res_info = future.result()
                if res_info:
                    write_state_and_progress(res_info, is_running=True)
                    if res_info["status"] in ("match", "diff"):
                        success_count += 1
                    else:
                        error_count += 1
                    
                    if completed[0] % 50 == 0:
                        log_debug(f"Progress: {completed[0]}/{total} (success={success_count}, errors={error_count})")
                else:
                    # None result (empty t_num or stop signal) - still count it
                    with file_lock:
                        completed[0] = min(completed[0] + 1, total)
                    write_progress(None, is_running=True)
            except Exception as e:
                log_debug(f"FUTURE exception: {type(e).__name__}: {e}")
                with file_lock:
                    completed[0] = min(completed[0] + 1, total)
                write_progress(None, is_running=True)

    # Final writes: gabungkan hanya sisa update valid tanpa menimpa update eksternal yang lebih baru
    with file_lock:
        _merge_and_flush_state(force=True)
        disk_final = _load_disk_state(fallback=state)
        state_entry_count = len(disk_final)
    
    write_progress(None, is_running=False)
    
    log_debug(f"BATCH COMPLETE: {completed[0]}/{total}, success={success_count}, errors={error_count}")
    log_debug(f"State entries: {state_entry_count}")
    print(json.dumps({"status": "completed", "completed": completed[0], "total": total, "state_entries": state_entry_count}))
    return 0

if __name__ == "__main__":
    if len(sys.argv) < 6:
        print(json.dumps({"status": "error", "message": "Usage: python batch_verify.py <pdf_path> <matched_json> <db_json> <state_json> <progress_json> [tolerance]"}))
        sys.exit(1)

    pdf_p = sys.argv[1]
    matched_p = sys.argv[2]
    db_p = sys.argv[3]
    state_p = sys.argv[4]
    progress_p = sys.argv[5]
    tol = float(sys.argv[6]) if len(sys.argv) > 6 else 0.0
    src_f = sys.argv[7].strip().lower() if len(sys.argv) > 7 else "all"
    workers = 6 if src_f in ("dinamis", "unverified") else 3

    sys.exit(run_batch(pdf_p, matched_p, db_p, state_p, progress_p, tolerance=tol, max_workers=workers, source_filter=src_f))