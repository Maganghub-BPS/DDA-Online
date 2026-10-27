"""
=============================================================================
DDA ONLINE CI4 - HEAD-TO-HEAD TABLE COMPARISON ENGINE
(Mendukung Tabel Dinamis & API Satu Data)
=============================================================================
"""

import sys
import os
import json
import re
from difflib import SequenceMatcher

sys.path.append(os.path.dirname(os.path.abspath(__file__)))

if sys.platform == "win32":
    try:
        import io
        if hasattr(sys.stdout, 'buffer'):
            sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        if hasattr(sys.stderr, 'buffer'):
            sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except Exception:
        pass

from pdf_extractor import (
    extract_tables_from_pdf,
    extract_table_content,
    get_cached_page_tables,
)
from fetcher import fetch_data_from_link
from normalizer import (
    KAB_KOTA_JATENG,
    evaluate_cell_match,
    explain_mismatch,
    parse_num,
    resolve_jateng_entity,
    resolve_provinsi_entity,
    find_provinsi,
    find_wilayah_with_type,
    is_interval_label,
    is_bps_col_num,
    is_safe_fuzzy_match,
    match_party_name,
)
from table_profiler import (
    is_title_row,
    is_header_or_metadata_row,
    detect_col_headers,
    detect_active_data_cols,
    extract_pdf_headers,
    align_columns_semantically,
    detect_table_archetype,
    normalize_api_data,
)


def _hint(pv, sv):
    """Sufiks petunjuk pola selisih untuk kolom 'note' (kosong bila tidak ada pola)."""
    h = explain_mismatch(pv, sv)
    return f" — {h}" if h else ""


def _to_num(v):
    """Angka dari sisi sumber (API) -> float atau None; tidak pernah melempar."""
    return v if isinstance(v, float) else parse_num(v, is_pdf=False)


def format_display_val(val):
    """Format angka display ramah pengguna tanpa trailing float noise panjang (maks 3 desimal)."""
    if val is None:
        return None
    if isinstance(val, (int, float)):
        if isinstance(val, float) and val.is_integer():
            return int(val)
        if isinstance(val, float):
            return round(val, 3)
        return val
    return val


def compare_head_to_head(pdf_path, target_table, db_json_path, tolerance=0.0, id_db=None):
    with open(db_json_path, "r", encoding="utf-8") as f:
        db_tables = json.load(f)
    if isinstance(db_tables, dict):
        db_tables = db_tables.get("matched_in_pdf", db_tables.get("matched_tables", []))

    def clean_num(n):
        if not n:
            return ""
        c = re.sub(r"^(?i:tabel|table)\s*", "", str(n).strip())
        c = re.sub(r'\s+', '', c)
        return c.rstrip('.')

    target_info = None

    # 1. Prioritas Lookup via ID Spesifik Database jika diberikan oleh Batch/Excel
    if id_db:
        target_info = next((
            t for t in db_tables
            if str(t.get("id_db", t.get("id", ""))) == str(id_db)
            or clean_num(t.get("nomor_tabel")) == clean_num(id_db)
        ), None)

    # 2. Lookup via Nomor Tabel dari PDF
    if not target_info:
        target_info = next((
            t for t in db_tables
            if clean_num(t.get("nomor_tabel")) == clean_num(target_table)
            or str(t.get("id_db", t.get("id", ""))) == clean_num(target_table)
        ), None)

    # 3. SAFETY NET FALLBACK FUZZY MATCH: Jika tidak ketemu, Fuzzy Search ke DB menggunakan Judul PDF dari Cache Index
    index_cache_path = pdf_path + ".index.json"
    if not target_info and os.path.exists(index_cache_path):
        try:
            with open(index_cache_path, "r", encoding="utf-8") as f:
                index_cache = json.load(f)
            pdf_cache_info = next((val for k, val in index_cache.items() if clean_num(k) == clean_num(target_table)), None)

            if pdf_cache_info and "display_title" in pdf_cache_info:
                from rapidfuzz import fuzz

                def norm_t(text):
                    t = str(text).lower()
                    t = t.replace('sertifikat', 'sertipikat').replace('ditebitkan', 'diterbitkan')
                    t = re.sub(r'\b20\d{2}\b', ' ', t)
                    t = re.sub(r'\b(?:di\s+)?jawa\s+provinsi\s+tengah\b', '', t)
                    t = re.sub(r'\b(?:di\s+)?provinsi\s+jawa\s+tengah\b', '', t)
                    t = re.sub(r'\b(?:di\s+)?jawa\s+tengah\b', '', t)
                    t = re.sub(r'\b(?:dan|serta|atau)\b', ' ', t)
                    t = t.encode('ascii', 'ignore').decode('ascii')
                    t = re.sub(r'[^\w\s]', ' ', t)
                    return re.sub(r'\s+', ' ', t).strip()

                norm_pdf_title = norm_t(pdf_cache_info["display_title"])
                best_sim = 0
                best_t = None
                for t in db_tables:
                    norm_db = norm_t(t.get('judul_tabel', t.get('judul_ind', '')))
                    sim = max(fuzz.partial_ratio(norm_pdf_title, norm_db), fuzz.token_set_ratio(norm_pdf_title, norm_db))
                    if sim > best_sim:
                        best_sim = sim
                        best_t = t
                if best_sim >= 80:
                    target_info = best_t
        except Exception:
            pass

    if not target_info:
        return {"status": "error", "message": "Tabel tidak ditemukan di database (ID tidak cocok dan Fuzzy Search gagal)"}

    src_type_code = str(target_info.get("source_type", "")).strip().lower()
    is_dynamic = int(target_info.get("is_dynamic", 0) or 0) == 1 or src_type_code == "dinamis"
    link = str(target_info.get("link_tabel", "") or "").strip()

    target_pages = None
    if os.path.exists(index_cache_path):
        try:
            with open(index_cache_path, "r", encoding="utf-8") as f:
                index_cache = json.load(f)
            for k, val in index_cache.items():
                if clean_num(k) == clean_num(target_table):
                    target_pages = val.get("pages", []) if isinstance(val, dict) else val
                    break
        except Exception:
            pass

    if not target_pages:
        pdf_tables = extract_tables_from_pdf(pdf_path, {clean_num(target_table)})
        matched_k = next((k for k in pdf_tables if clean_num(k) == clean_num(target_table)), None)
        if matched_k:
            target_pages = pdf_tables[matched_k]["pages"]
            try:
                cur_cache = {}
                if os.path.exists(index_cache_path):
                    with open(index_cache_path, "r", encoding="utf-8") as f:
                        cur_cache = json.load(f)
                cur_cache[target_table] = {"pages": target_pages, "display_title": pdf_tables[matched_k].get("display_title", "")}
                with open(index_cache_path, "w", encoding="utf-8") as f:
                    json.dump(cur_cache, f)
            except Exception:
                pass

    is_template_extraction = False
    cell_coords = target_info.get("cell_coords") or {}
    tpl_grid = target_info.get("template_grid") or []

    if is_dynamic:
        source_label = "Tabel Dinamis"
        dyn_grid = target_info.get("dynamic_grid", [])
        if target_info.get("dynamic_empty", False) or not dyn_grid or len(dyn_grid) <= 2:
            if tpl_grid and len(tpl_grid) > 2 and cell_coords and target_pages:
                is_template_extraction = True
                source_data = tpl_grid
            else:
                return {
                    "status": "error",
                    "source_type": "Tabel Dinamis",
                    "message": "Data Tabel Dinamis belum diisi",
                    "is_api_empty": True,
                    "id_db": target_info.get("id_db"),
                    "id_indikator": target_info.get("id_indikator"),
                    "pages": target_pages or []
                }
        else:
            source_data = dyn_grid
    elif src_type_code == "spreadsheet":
        return {
            "status": "error",
            "source_type": "Belum Dikonfigurasi",
            "message": "Tabel ini belum dikonfigurasi ke Tabel Dinamis",
            "id_db": target_info.get("id_db"),
            "pages": target_pages or []
        }
    else:
        source_label = "API Satudata"
        if not link:
            return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada link API untuk tabel ini", "is_api_empty": True, "pages": target_pages or []}

        source_data = fetch_data_from_link(link)
        if isinstance(source_data, dict) and "error" in source_data:
            err_msg = source_data.get('error', '')
            detail_msg = source_data.get('detail', '')
            if source_data.get("is_api_empty") or source_data.get("error_kind") == "empty":
                return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "detail": detail_msg or err_msg, "is_api_empty": True, "pages": target_pages or []}
            return {"status": "error", "source_type": "API Satudata", "message": f"Gagal mengambil data dari link: {err_msg}", "detail": detail_msg, "error_kind": source_data.get("error_kind", ""), "pages": target_pages or []}

        if not source_data:
            return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "is_api_empty": True, "pages": target_pages or []}

    if not target_pages:
        return {"status": "error", "message": "Tabel tidak terdeteksi di PDF"}

    target_pdf_info = [{"nomor_tabel": target_table, "pages": target_pages}]
    extract_table_content(pdf_path, target_pdf_info)
    rows = target_pdf_info[0].get("extracted_data", [])

    results = {
        "status": "success",
        "source_type": source_label,
        "diffs": [],
        "matches": [],
        "pdf_only": [],
        "summary": {}
    }

    # CASE 1: API Satudata (List of Dict records)
    if isinstance(source_data, list) and source_data and isinstance(source_data[0], dict):
        sample_keys = [str(k).lower() for k in source_data[0].keys()]
        is_school = any('sekolah' in k or 'guru' in k or 'murid' in k for k in sample_keys)

        if is_school:
            pdf_map = {}
            cur_sec = ""
            is_kota_mode = False

            for r in rows:
                if not isinstance(r, list):
                    continue
                txt = ' '.join(str(c).replace('\n', ' ') for c in r if str(c).strip()).lower()
                if 'kota/municipality' in txt:
                    is_kota_mode = True
                elif 'kabupaten/regency' in txt:
                    is_kota_mode = False

                for s in ['sekolah', 'guru', 'murid']:
                    if s in txt:
                        cur_sec = s.title()
                        break
                if is_header_or_metadata_row(r):
                    continue
                w = find_wilayah_with_type(' '.join(str(c) for c in r), is_kota=is_kota_mode)
                if w == "Umum":
                    continue

                first_idx = next((i for i, c in enumerate(r) if str(c).strip()), -1)
                nums = []
                for idx, c in enumerate(r):
                    c_str = str(c).strip()
                    if not c_str:
                        continue
                    if idx == first_idx and c_str.rstrip('.').isdigit():
                        continue
                    if idx <= 3 and re.match(r'^33\.\d{2}$', c_str):
                        continue
                    raw_w_name = w.replace('Kab. ', '').replace('Kota ', '')
                    if any(k.lower() in c_str.lower() for k in [raw_w_name]):
                        continue
                    val = parse_num(c_str, is_pdf=True)
                    if val is not None:
                        nums.append(val)
                pdf_map[(w, cur_sec)] = nums

            for rec in source_data:
                w_raw = rec.get("nama_wilayah") or rec.get("wilayah") or ""
                is_k = 'kota' in w_raw.lower()
                w = find_wilayah_with_type(w_raw, is_kota=is_k)

                for s_name, k_pfx in [("Sekolah", "sekolah"), ("Guru", "guru"), ("Murid", "murid")]:
                    key_neg = f"jumlah_{k_pfx}_negeri"
                    key_swa = f"jumlah_{k_pfx}_swasta"
                    key_tot = f"jumlah_total_{k_pfx}" if f"jumlah_total_{k_pfx}" in rec else f"jumlah_{k_pfx}_total"

                    if key_neg not in rec:
                        continue

                    api_neg = parse_num(rec[key_neg], is_pdf=False)
                    api_swa = parse_num(rec[key_swa], is_pdf=False)
                    api_tot = parse_num(rec[key_tot], is_pdf=False)

                    p_nums = pdf_map.get((w, s_name), [])
                    if len(p_nums) == 6:
                        pdf_neg_25, pdf_swa_25, pdf_tot_25 = p_nums[1], p_nums[3], p_nums[5]
                        metrics_chk = [("Negeri", pdf_neg_25, api_neg), ("Swasta", pdf_swa_25, api_swa), ("Total", pdf_tot_25, api_tot)]
                        for m_lbl, p_v, a_v in metrics_chk:
                            m_full = f"{s_name} Negeri" if m_lbl == "Negeri" else f"{s_name} Swasta" if m_lbl == "Swasta" else f"Total {s_name}"
                            is_m, _, d_val = evaluate_cell_match(p_v, a_v, tolerance=tolerance) if (p_v is not None and a_v is not None) else (False, False, None)
                            if is_m:
                                results["matches"].append({"wilayah": w, "section": s_name, "metric": m_full, "val": int(a_v) if a_v.is_integer() else a_v})
                            elif a_v is None or p_v is None:
                                results["diffs"].append({
                                    "wilayah": w, "section": s_name, "metric": m_full,
                                    "pdf_val": None if p_v is None else (int(p_v) if p_v.is_integer() else p_v),
                                    "src_val": None if a_v is None else (int(a_v) if a_v.is_integer() else a_v),
                                    "diff": None, "note": "Nilai kosong / tidak terbaca di salah satu sisi", "type": "replace"
                                })
                            else:
                                abs_diff = int(abs(d_val)) if abs(d_val).is_integer() else abs(d_val)
                                note = f"Selisih {abs_diff} ({s_name} di PDF {'lebih sedikit' if d_val > 0 else 'lebih banyak'})" + _hint(p_v, a_v)
                                results["diffs"].append({
                                    "wilayah": w, "section": s_name, "metric": m_full,
                                    "pdf_val": int(p_v) if p_v.is_integer() else p_v, "src_val": int(a_v) if a_v.is_integer() else a_v,
                                    "diff": d_val, "note": note, "type": "replace"
                                })
                        results["pdf_only"].append({
                            "wilayah": w, "section": s_name, "metric": "Tahun Ajaran 2024/2025",
                            "values": [int(x) if x.is_integer() else x for x in [p_nums[0], p_nums[2], p_nums[4]]],
                            "note": "Tercetak di PDF untuk tahun ajaran lalu (2024/2025), tidak dimuat di API 2025", "type": "delete"
                        })
                    elif len(p_nums) == 3:
                        pdf_metrics = [(f"{s_name} Negeri", p_nums[0], api_neg), (f"{s_name} Swasta", p_nums[1], api_swa), (f"Total {s_name}", p_nums[2], api_tot)]
                        for m_lbl, p_v, a_v in pdf_metrics:
                            is_m, _, d_val = evaluate_cell_match(p_v, a_v, tolerance=tolerance) if (p_v is not None and a_v is not None) else (False, False, None)
                            if is_m:
                                results["matches"].append({"wilayah": w, "section": s_name, "metric": m_lbl, "val": int(a_v) if a_v.is_integer() else a_v})
                            elif a_v is None or p_v is None:
                                results["diffs"].append({
                                    "wilayah": w, "section": s_name, "metric": m_lbl,
                                    "pdf_val": None if p_v is None else (int(p_v) if p_v.is_integer() else p_v),
                                    "src_val": None if a_v is None else (int(a_v) if a_v.is_integer() else a_v),
                                    "diff": None, "note": "Nilai kosong / tidak terbaca di salah satu sisi", "type": "replace"
                                })
                            else:
                                abs_diff = int(abs(d_val)) if abs(d_val).is_integer() else abs(d_val)
                                note = f"Selisih {abs_diff} ({s_name} di PDF {'lebih sedikit' if d_val > 0 else 'lebih banyak'})" + _hint(p_v, a_v)
                                results["diffs"].append({
                                    "wilayah": w, "section": s_name, "metric": m_lbl, "pdf_val": int(p_v) if p_v.is_integer() else p_v,
                                    "src_val": int(a_v) if a_v.is_integer() else a_v, "diff": d_val, "note": note, "type": "replace"
                                })

            if len(results["matches"]) > 0 or len(results["diffs"]) > 0:
                results["summary"] = {
                    "total_matches": len(results["matches"]),
                    "total_diffs": len(results["diffs"]),
                    "total_pdf_only": len(results.get("pdf_only", []))
                }
                results["mismatches"] = results["diffs"] + results.get("pdf_only", [])
                results["replace_count"] = len(results["diffs"])
                results["delete_count"] = len(results.get("pdf_only", []))
                results["pdf_count"] = len(results["diffs"]) + len(results["matches"]) + len(results.get("pdf_only", []))
                results["source_count"] = len(results["diffs"]) + len(results["matches"])
                return results

        # Deteksi dataset API kosong / dummy (misal hanya 1 baris Grobogan berisi angka 0 pada tabel regional)
        if isinstance(source_data, list) and source_data and isinstance(source_data[0], dict):
            if len(source_data) <= 1:
                all_zero_or_empty = True
                for r in source_data:
                    for k, val in r.items():
                        if k in ['tahun_data', 'tahun', 'kode_wilayah', 'nama_wilayah', 'id', 'created_at']:
                            continue
                        nv = parse_num(val, is_pdf=False)
                        if nv is not None and nv != 0.0:
                            all_zero_or_empty = False
                            break
                if all_zero_or_empty:
                    return {
                        "status": "error",
                        "source_type": "API Satudata",
                        "message": "Data API belum diisi di Portal Satu Data (hanya memuat 1 baris kosong)",
                        "is_api_empty": True,
                        "id_db": target_info.get("id_db"),
                        "pages": target_pages or []
                    }

        # Fallback / General API Normalizer: convert dict list into 2D grid and let CASE 2 process it!
        api_year = None
        if isinstance(source_data, list) and source_data and isinstance(source_data[0], dict):
            api_years = sorted(list(set(str(r.get('tahun_data', r.get('tahun', ''))).strip() for r in source_data if r.get('tahun_data') or r.get('tahun'))))
            if len(api_years) == 1 and api_years[0].isdigit() and len(api_years[0]) == 4:
                api_year = api_years[0]

        grid = normalize_api_data(source_data, rows, target_table)
        if grid and len(grid) > 1:
            source_data = grid
        else:
            return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "is_api_empty": True}

    # CASE 2: Matriks 2D (Tabel Dinamis & Normalized API Tables)
    if not (source_data and isinstance(source_data[0], dict)):
        headers = detect_col_headers(source_data)
        archetype = detect_table_archetype(pdf_path, target_table, target_pages, source_data)
        sample_rows = [r for r in source_data[:35] if isinstance(r, list) and not is_title_row(r) and not is_header_or_metadata_row(r)]
        prov_matches = sum(1 for r in sample_rows if resolve_provinsi_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '00', '33.00', 'Total Jawa Tengah', 'Indonesia'])
        jateng_matches = sum(1 for r in sample_rows if resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '33.00', 'Total Jawa Tengah'])

        # Cek apakah wilayah muncul berulang kali di baris-baris data (indikasi kuat tabel bertingkat / kategori x wilayah)
        prov_entities = [resolve_provinsi_entity(' '.join(str(c) for c in r[:3]))[0] for r in sample_rows]
        prov_entities = [p for p in prov_entities if p not in [None, '00', '33.00', 'Total Jawa Tengah', 'Indonesia']]
        has_dup_prov = len(prov_entities) > len(set(prov_entities))

        jateng_entities = []
        is_kota_scan = False
        for r in sample_rows:
            txt_scan = ' '.join(str(c) for c in r).lower()
            if 'kota' in txt_scan and ('kabupaten' not in txt_scan or txt_scan.find('kota') < txt_scan.find('kabupaten')):
                is_kota_scan = True
            ent, _ = resolve_jateng_entity(' '.join(str(c) for c in r[:3]), is_kota_mode=is_kota_scan)
            if ent and ent not in [None, '33.00', 'Total Jawa Tengah']:
                jateng_entities.append(ent)
                if ent == '33.29':  # Brebes adalah kabupaten terakhir di urutan BPS Jawa Tengah
                    is_kota_scan = True
        has_dup_jateng = len(jateng_entities) > len(set(jateng_entities))

        has_duplicate_regions = has_dup_prov or has_dup_jateng

        is_univ_table = any(k in str(headers).lower() for k in ['perguruan tinggi', 'universitas', 'dosen']) or target_table in ['4.1.20', '4.1.21']
        is_regional = (prov_matches >= 3 or jateng_matches >= 3) and not is_univ_table and not has_duplicate_regions
        is_categorical = (not is_regional and (archetype in ["MULTI_PAGE_ROW_CONTINUATION", "MULTI_PAGE_SECTION_PER_PAGE", "SINGLE_PAGE_CATEGORICAL"])) or has_duplicate_regions

        if is_categorical:
            clean_pdf_rows = []
            for r in rows:
                if not isinstance(r, list):
                    continue
                txt = ' '.join(str(c) for c in r if str(c).strip()).lower()
                if any(k in txt for k in ['bab / chapter', 'chapter \nbab', 'bab\nchapter']) or re.search(r'^\s*bab\s+\d+\b', txt):
                    break
                if any(txt.startswith(k) for k in ['tabel / table', 'tabel\n', 'table\n']) and target_table not in txt:
                    m_next = re.search(r'tabel\s+(\d+\.\d+\.\d+)', txt)
                    if m_next and m_next.group(1).strip('.') != target_table.strip('.'):
                        break
                clean_pdf_rows.append(r)

            clean_db_rows = []
            for r in source_data:
                if not isinstance(r, list):
                    continue
                txt = ' '.join(str(c) for c in r).lower().strip()
                if any(txt.startswith(k) for k in ['sumber:', 'source:', 'catatan:', 'note:']):
                    break
                clean_db_rows.append(r)

            def get_cat_key(text):
                if not text:
                    return ''
                if is_interval_label(text):
                    digits = re.findall(r'\d+', str(text))
                    if digits:
                        return f"INT_{'_'.join(digits)}"
                    c = re.sub(r'[\s\*]', '', str(text))
                    if c in ['0', '01']:
                        return 'JAM_0'
                    if '+' in c:
                        return f"JAM_{c.replace('+', '_PLUS')}"
                    c_dash = re.sub(r'[\-–—\ufffd]', '_', c)
                    return f"JAM_{c_dash}"
                p = find_provinsi(text)
                if p:
                    return f"PROV_{re.sub(r'[^\w]', '', p.upper())}"
                s = re.sub(r'(\b(?:19|20)\d{2})\d+\b', r'\1', str(text)).upper()
                if 'CURAH HUJAN' in s or 'PRECIP' in s:
                    return 'CURAH_HUJAN'
                if 'HARI HUJAN' in s or 'RAINY' in s:
                    return 'HARI_HUJAN'
                if 'PENYINARAN MATAHARI' in s or 'SUNSHINE' in s:
                    return 'PENYINARAN_MATAHARI'

                if s in ['JUMLAH', 'TOTAL', 'JUMLAH/TOTAL', 'TOTAL/JUMLAH', 'JUMLAH TOTAL']:
                    return 'TOTAL'
                if (s.startswith('JUMLAH') or s.startswith('TOTAL')) and not any(k in s for k in ['CURAH', 'HARI', 'HUJAN', 'PENDUDUK', 'SEKOLAH', 'GURU', 'MURID']):
                    return 'TOTAL'
                s = s.replace('FUNGSIHONAL', 'FUNGSIONAL').replace('FUNGSHIONAL', 'FUNGSIONAL')

                m_grade = re.search(r'\b([I|V|X]+/[A-E])\b', s)
                if m_grade:
                    return m_grade.group(1).upper()
                m_range = re.search(r'\bGOLONGAN\s+([I|V|X]+)\b', s)
                if m_range:
                    return f"GOL_{m_range.group(1).upper()}"
                if any(k in s.lower() for k in ['tidak tamat sd', 'belum tamat sd', 'not completed primary']):
                    return 'TIDAK_TAMAT_SD'
                for lvl in ['SD', 'SMP', 'SMA', 'SMK']:
                    if re.search(rf'\b{lvl}\b', s):
                        return lvl
                m_dip = re.search(r'DIPLOMA\s+(I|II|III|IV)\b', s)
                if m_dip:
                    return f"D_{m_dip.group(1)}"
                m_s = re.search(r'\b(S1|S2|S3)\b', s)
                if m_s:
                    return m_s.group(1)
                if 'STRUKTURAL' in s:
                    return 'STRUKTURAL'
                if 'TERTENTU' in s:
                    return 'FUNGSIONAL_TERTENTU'
                if 'UMUM' in s or 'STAF' in s:
                    return 'FUNGSIONAL_UMUM'
                if 'MINIMUM' in s:
                    return 'MINIMUM'
                if 'MAKSIMUM' in s or 'MAXIMUM' in s:
                    return 'MAKSIMUM'
                if 'RATA' in s or 'AVERAGE' in s:
                    return 'RATA_RATA'
                m_yr = re.search(r'\b(19|20\d{2})\b', s)
                yr_pfx = f"Y{m_yr.group(0)}_" if m_yr else ""
                months = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER']
                for idx, m in enumerate(months):
                    if re.search(rf'\b{m}\b', s):
                        return f"{yr_pfx}BLN_{idx+1:02d}"
                return re.sub(r'[^\w]', '', s)[:25]

            def clean_cat_display(text):
                t = re.sub(r'(\b(?:19|20)\d{2})\d+\b', r'\1', str(text).strip())
                if is_interval_label(t):
                    c = re.sub(r'[\s\*]', '', str(t))
                    if c in ['0', '01']:
                        return '0 Jam (Sementara Tidak Bekerja)'
                    digits = re.findall(r'\d+', c)
                    c_dash = ' - '.join(digits) if digits else re.sub(r'[\-–—\ufffd]', ' - ', c)
                    first_r_txt = ' '.join(str(x) for x in (clean_db_rows[0] if clean_db_rows else [])).lower()
                    is_age = any(k in first_r_txt for k in ['umur', 'usia', 'sekolah', 'pendidikan']) or target_table.startswith('4.')
                    is_hours = 'jam' in first_r_txt or 'hour' in first_r_txt
                    if is_age and not is_hours:
                        return f"{c_dash} Tahun"
                    elif is_hours:
                        return f"{c} Jam" if '+' in c else f"{c_dash} Jam"
                    else:
                        return f"{c_dash}"
                p = find_provinsi(t)
                if p:
                    return p
                m_grade = re.search(r'\b([I|V|X]+/[A-E])\b', t, re.IGNORECASE)
                if m_grade:
                    return f"Golongan {m_grade.group(1).upper()}"
                m_range = re.search(r'\bGolongan\s+([I|V|X]+)\b', t, re.IGNORECASE)
                if m_range:
                    return f"Golongan {m_range.group(1).upper()}"
                t_low = t.lower()
                if 'curah hujan' in t_low:
                    return 'Jumlah Curah Hujan'
                if 'hari hujan' in t_low:
                    return 'Jumlah Hari Hujan'
                if 'penyinaran matahari' in t_low:
                    return 'Penyinaran Matahari'
                t = re.sub(r'^[a-z\.\s/:\n]+(?=[A-Z0-9])', '', t).strip()
                if not re.match(r'^(?:19|20)\d{2}\b', t):
                    t = re.sub(r'^(?:[IVXLCDM]+|\d+)[\.\s]+', '', t).strip()
                lines = [l.strip() for l in t.split('\n') if l.strip()]
                valid_lines = [l for l in lines if len(l) > 2 or any(c.isdigit() for c in l)]
                first_line = valid_lines[0] if valid_lines else (lines[0] if lines else t)
                m_bi_yr = re.match(r'^([A-Za-z]+)\s*/\s*[A-Za-z]+\s+(\d{4})$', first_line)
                if m_bi_yr:
                    first_line = f"{m_bi_yr.group(1)} {m_bi_yr.group(2)}"
                elif '/' in first_line and not re.search(r'\d/\d', first_line):
                    first_line = first_line.split('/')[0].strip()
                first_line = re.sub(r'\s+[a-z]$', '', first_line)
                clean = re.sub(r'[^\w\s\(\)/]', '', first_line).strip()
                clean = re.sub(r'\s+', ' ', clean).strip()
                return clean if clean else t

            headers = detect_col_headers(clean_db_rows)
            src_title = ' '.join(str(c) for c in (source_data[0] if source_data else []))
            title_years = re.findall(r'\b(20\d{2})\b', src_title)
            target_year = title_years[-1] if title_years else (api_year if 'api_year' in locals() and api_year else None)
            db_header_years = set(re.findall(r'\b(20\d{2})\b', ' '.join(headers)))

            pdf_by_year = {}
            cur_y = "default"
            for r in clean_pdf_rows:
                if not isinstance(r, list):
                    continue
                txt = ' '.join(str(c) for c in r)
                m = re.search(r'\b(20\d{2})\b', txt)
                if m and not any(parse_num(c, is_pdf=True) for c in r if str(c).strip() and str(c).strip() != m.group(1)):
                    cur_y = m.group(1)
                    if cur_y not in pdf_by_year:
                        pdf_by_year[cur_y] = []
                    continue
                if cur_y not in pdf_by_year:
                    pdf_by_year[cur_y] = []
                pdf_by_year[cur_y].append(r)

            if target_year and target_year in pdf_by_year and len(pdf_by_year) > 1 and len(db_header_years) <= 1:
                target_pdf_rows = pdf_by_year[target_year]
                hist_pdf_years = {y: r_list for y, r_list in pdf_by_year.items() if y != target_year and y != "default"}
            else:
                target_pdf_rows = clean_pdf_rows
                hist_pdf_years = {}

            active_cat_cols = detect_active_data_cols(clean_db_rows)

            def get_gender_sec(txt, r):
                all_txt = txt.lower()
                if any(parse_num(c, is_pdf=True) is not None for c in r if str(c).strip()):
                    return None
                if re.search(r'\b(kabupaten|regency)\b', all_txt) and not re.search(r'\b(kota|municipality)\b', all_txt):
                    return "Kabupaten"
                elif re.search(r'\b(kota|municipality)\b', all_txt) and not re.search(r'\b(kabupaten|regency)\b', all_txt):
                    return "Kota"
                has_male = bool(re.search(r'\b(laki[\s\-_]?laki|lakilaki|male)\b', all_txt))
                has_female = bool(re.search(r'\b(perempuan|female)\b', all_txt))
                if has_male and has_female:
                    return None
                if has_male:
                    return "Laki-Laki"
                elif has_female:
                    return "Perempuan"
                return None

            def extract_cat_rows_std(data_list, is_pdf=False, target_cols=None):
                records = []
                cur_sec = ""
                use_cols = target_cols if target_cols is not None else active_cat_cols
                last_parent_label = ""
                for r in data_list:
                    if not isinstance(r, list):
                        continue
                    all_txt = ' '.join(str(c).strip().lower() for c in r if str(c).strip())
                    if any(all_txt.startswith(k) for k in ['sumber', 'source', 'catatan', 'note', 'perubahan data']):
                        break
                    if any(k in all_txt for k in ['sumber:', 'source:', 'catatan:', 'note:', 'sumber/source', 'catatan/note']):
                        break

                    if is_header_or_metadata_row(r):
                        continue

                    sec = get_gender_sec(all_txt, r)
                    if sec:
                        cur_sec = sec
                        continue

                    code = None
                    if not is_pdf and use_cols:
                        raw_vals = [
                            0.0 if str(r[c]).strip() in ('-', '–', '—') else parse_num(str(r[c]).strip(), is_pdf=False)
                            for c in use_cols if c < len(r)
                        ]
                        if not any(v is not None for v in raw_vals):
                            continue
                        first_col = use_cols[0]
                        if first_col > 1:
                            c0 = str(r[0]).strip()
                            if re.match(r'^\d+(?:\.\d+)*\.?$', c0) and not re.match(r'^(?:19|20)\d{2}', c0):
                                code = c0.rstrip('.')
                        label_cells = [re.sub(r'^\d+\.\s+', '', str(c).strip()) for c in r[:first_col] if str(c).strip()]
                        has_alpha_label = any(re.search(r'[a-zA-Z]{3,}', c) for c in label_cells)
                        if has_alpha_label:
                            raw_label = ' '.join(c for c in label_cells if parse_num(c) is None)
                        elif any(is_interval_label(c) for c in label_cells):
                            raw_label = next(c for c in label_cells if is_interval_label(c))
                        elif any(re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c) for c in label_cells):
                            raw_label = next(c for c in label_cells if re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c))
                        else:
                            raw_label = ' '.join(c for c in label_cells if parse_num(c) is None)
                        nums = []
                        for c_idx in use_cols:
                            c_str = str(r[c_idx]).strip() if c_idx < len(r) else ''
                            v = 0.0 if c_str in ('-', '–', '—') else parse_num(c_str, is_pdf=False)
                            nums.append(0.0 if v is None else v)
                    else:
                        first_idx = next((i for i, c in enumerate(r) if c is not None and str(c).strip() and str(c).strip().lower() != 'none'), -1)
                        raw_label = ''
                        nums = []
                        for idx_c, c in enumerate(r):
                            c_str = str(c).strip() if c is not None else ''
                            if not c_str or c_str.lower() == 'none':
                                continue
                            if not raw_label and (is_interval_label(c_str) or re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c_str)) and not nums:
                                raw_label = c_str
                                continue
                            c_code_cand = re.sub(r'\s+', '', c_str)
                            if idx_c == first_idx and re.match(r'^\d+(?:\.\d+)*\.?$', c_code_cand) and not is_interval_label(c_str) and not re.match(r'^(?:19|20)\d{2}', c_code_cand):
                                code = c_code_cand.rstrip('.')
                                continue
                            if not nums and re.match(r'^(19|20)\d{2}$', c_str) and any(k in raw_label.lower() for k in ['jumlah', 'total']):
                                continue
                            if not nums and re.search(r'[a-zA-Z]{3,}', c_str):
                                if not raw_label:
                                    raw_label = c_str
                                else:
                                    raw_label += ' ' + c_str
                                continue
                            v = parse_num(c_str, is_pdf=is_pdf)
                            if v is not None:
                                nums.append(v)
                            else:
                                if not nums:
                                    if not raw_label:
                                        raw_label = c_str
                                    else:
                                        raw_label += ' ' + c_str

                    if is_pdf:
                        col0_txt = str(r[0]).strip() if len(r) > 0 and r[0] is not None else ''
                        col1_txt = str(r[1]).strip() if len(r) > 1 and r[1] is not None else ''
                        col0_clean = re.sub(r'\s+', '', col0_txt)
                        if not code and re.match(r'^\d+(?:\.\d+)*\.?$', col0_clean) and not re.match(r'^(?:19|20)\d{2}', col0_clean):
                            code = col0_clean.rstrip('.')
                        if col0_txt and parse_num(col0_txt, is_pdf=True) is None and not is_bps_col_num(col0_txt):
                            last_parent_label = col0_txt
                        if not col0_txt and col1_txt and last_parent_label and parse_num(col1_txt, is_pdf=True) is None:
                            if last_parent_label not in raw_label:
                                raw_label = f"{last_parent_label} {raw_label}".strip()

                    if not raw_label:
                        label_candidates = [str(c).strip() for c in r[:2] if str(c).strip()]
                        if any(is_interval_label(c) for c in label_candidates):
                            raw_label = next(c for c in label_candidates if is_interval_label(c))
                        elif any(re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c) for c in label_candidates):
                            raw_label = next(c for c in label_candidates if re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c))
                        else:
                            raw_label = ' '.join(str(c).strip() for c in r[:2] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None)

                    clean_disp = clean_cat_display(raw_label)
                    if not clean_disp:
                        continue
                    key = get_cat_key(clean_disp)
                    if cur_sec:
                        digits = re.findall(r'\d+', str(raw_label))
                        c_dash = ' - '.join(digits) if digits else raw_label
                        disp_unit = f"{c_dash} Tahun" if is_interval_label(raw_label) else clean_disp
                        full_disp = f"{cur_sec} ({disp_unit})"
                        full_key = f"{cur_sec.upper().replace(' ', '_')}_{'_'.join(digits) if digits else key}"
                    else:
                        full_disp = clean_disp
                        full_key = key

                    if nums and full_key:
                        records.append({'key': full_key, 'display': full_disp, 'nums': nums, 'sec': cur_sec, 'code': code})
                return records

            col_years_map = {}
            if len(pdf_by_year) >= 2 and active_cat_cols:
                for c_idx, h in zip(active_cat_cols, headers):
                    m_y = re.findall(r'\b(20\d{2})\b', str(h))
                    if m_y:
                        col_years_map[c_idx] = m_y[-1]

            common_years = [y for y in sorted(pdf_by_year.keys()) if y in set(col_years_map.values())]
            has_multi_year_cat = (len(common_years) >= 2)

            sec_b = f"Halaman {target_pages[0]}" if len(target_pages) == 1 else (f"Halaman {target_pages[0]}-{target_pages[-1]}" if len(target_pages) > 1 else "")

            if has_multi_year_cat:
                for y in common_years:
                    cols_y = [c for c in active_cat_cols if col_years_map.get(c) == y]
                    s_recs_y = extract_cat_rows_std(clean_db_rows, is_pdf=False, target_cols=cols_y)
                    p_recs_y = extract_cat_rows_std(pdf_by_year[y], is_pdf=True)
                    headers_y = [headers[active_cat_cols.index(c)] for c in cols_y]

                    used_p = set()
                    for s_rec in s_recs_y:
                        s_disp = s_rec['display']
                        s_nums = s_rec['nums']
                        match_p = next((p for idx_p, p in enumerate(p_recs_y) if idx_p not in used_p and p['key'] == s_rec['key']), None)
                        if not match_p:
                            match_p = next((
                                p for idx_p, p in enumerate(p_recs_y)
                                if idx_p not in used_p and len(p['key']) >= 6 and len(s_rec['key']) >= 6 and (
                                    p['key'].startswith(s_rec['key']) or s_rec['key'].startswith(p['key']) or
                                    p['key'].endswith(s_rec['key']) or s_rec['key'].endswith(p['key'])
                                )
                            ), None)
                        if not match_p:
                            match_p = next((p for idx_p, p in enumerate(p_recs_y) if idx_p not in used_p and SequenceMatcher(None, p['display'], s_disp).ratio() >= 0.85 and is_safe_fuzzy_match(p['display'], s_disp)), None)
                        if not match_p:
                            continue
                        used_p.add(p_recs_y.index(match_p))
                        p_nums = match_p['nums']

                        for i in range(min(len(s_nums), len(p_nums))):
                            sv = s_nums[i]
                            pv = p_nums[i]
                            base_h = headers_y[i] if i < len(headers_y) else f"Metrik #{i+1}"
                            if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                base_h = f"Metrik #{i+1}"

                            if y and y not in str(base_h):
                                base_h = f"{base_h} ({y})"

                            is_match, is_tol, diff_val = evaluate_cell_match(pv, sv, tolerance=tolerance)
                            abs_diff = format_display_val(abs(diff_val))

                            p_disp = format_display_val(pv)
                            src_disp = format_display_val(sv)

                            if is_match:
                                m_item = {"wilayah": s_disp, "section": sec_b, "metric": base_h, "val": p_disp, "pdf_val": p_disp, "src_val": src_disp}
                                if is_tol:
                                    m_item["is_tolerance_match"] = True
                                    m_item["tolerance_diff"] = round(diff_val, 4)
                                results["matches"].append(m_item)
                            else:
                                note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})" + _hint(pv, sv)
                                results["diffs"].append({
                                    "wilayah": s_disp, "section": sec_b, "metric": base_h,
                                    "pdf_val": p_disp, "src_val": src_disp,
                                    "diff": diff_val, "note": note, "type": "replace"
                                })
            else:
                s_recs = extract_cat_rows_std(clean_db_rows, is_pdf=False)
                for idx_s, s in enumerate(s_recs):
                    s['id'] = idx_s
                p_recs = extract_cat_rows_std(target_pdf_rows, is_pdf=True)


                p_grouped_items = {s['id']: [] for s in s_recs}
                used_p_indices = set()

                # Pass 0: Exact code matching (e.g. kode_rekening / nomor baris hierarki)
                for idx_p, p in enumerate(p_recs):
                    p_code = p.get('code')
                    if p_code:
                        match_s = next((s for s in s_recs if s.get('code') == p_code and s.get('sec') == p.get('sec')), None)
                        if not match_s:
                            match_s = next((s for s in s_recs if s.get('code') == p_code), None)
                        if match_s:
                            p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                            used_p_indices.add(idx_p)

                # Pass 1: Strict key matching (with section priority)
                for idx_p, p in enumerate(p_recs):
                    if idx_p in used_p_indices:
                        continue
                    match_s = next((s for s in s_recs if s['key'] == p['key'] and s.get('sec') == p.get('sec')), None)
                    if not match_s:
                        match_s = next((s for s in s_recs if s['key'] == p['key']), None)
                    if match_s:
                        p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                        used_p_indices.add(idx_p)

                # Pass 1.5: Prefix / suffix containment key matching (e.g. Roman numeral prefix or bilingual suffix diff)
                def is_key_compatible(k1, k2):
                    y1 = re.search(r'Y(19|20\d{2})_', k1)
                    y2 = re.search(r'Y(19|20\d{2})_', k2)
                    if y1 or y2:
                        return bool(y1 and y2 and y1.group(1) == y2.group(1))
                    return True

                for idx_p, p in enumerate(p_recs):
                    if idx_p in used_p_indices:
                        continue
                    match_s = next((
                        s for s in s_recs
                        if s.get('sec') == p.get('sec') and len(p['key']) >= 4 and len(s['key']) >= 4 and is_key_compatible(p['key'], s['key']) and (
                            p['key'].startswith(s['key']) or s['key'].startswith(p['key']) or
                            p['key'].endswith(s['key']) or s['key'].endswith(p['key']) or
                            p['key'].split('_')[0] == s['key'] or s['key'].split('_')[0] == p['key']
                        )
                    ), None)
                    if not match_s:
                        match_s = next((
                            s for s in s_recs
                            if len(p['key']) >= 4 and len(s['key']) >= 4 and is_key_compatible(p['key'], s['key']) and (
                                p['key'].startswith(s['key']) or s['key'].startswith(p['key']) or
                                p['key'].endswith(s['key']) or s['key'].endswith(p['key']) or
                                p['key'].split('_')[0] == s['key'] or s['key'].split('_')[0] == p['key']
                            )
                        ), None)
                    if match_s:
                        p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                        used_p_indices.add(idx_p)

                # Pass 2: Safe fuzzy matching for unmatched PDF rows
                for idx_p, p in enumerate(p_recs):
                    if idx_p in used_p_indices:
                        continue
                    match_s = next((s for s in s_recs if s.get('sec') == p.get('sec') and SequenceMatcher(None, p['display'], s['display']).ratio() >= 0.85 and is_safe_fuzzy_match(p['display'], s['display'])), None)
                    if not match_s:
                        match_s = next((s for s in s_recs if SequenceMatcher(None, p['display'], s['display']).ratio() >= 0.85 and is_safe_fuzzy_match(p['display'], s['display'])), None)
                    if match_s:
                        p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                        used_p_indices.add(idx_p)

                # Sort multi-page chunks by original vertical/page order (idx_p) before flattening
                p_grouped = {}
                for s_id, items in p_grouped_items.items():
                    items.sort(key=lambda x: x[0])
                    nums_flat = []
                    for _, n_list in items:
                        nums_flat.extend(n_list)
                    p_grouped[s_id] = nums_flat

                def _norm_hdr(raw_h):
                    h = str(raw_h).lower().replace('-', ' ')
                    h = re.sub(r'\(.*?\)', '', h)
                    h = re.sub(r'\bpresentase\b', 'persentase', h)
                    h = re.sub(r'\bprosentase\b', 'persentase', h)
                    h = re.sub(r'\bblla\b', 'biia', h)
                    h = re.sub(r'\bbiia\b', 'biia', h)
                    tokens = set(re.findall(r'\b\w+\b', h))
                    return tokens, h.strip()

                col_map = {}
                pdf_cols = []
                is_row_continuation = False
                if target_pages and len(target_pages) > 1:
                    p1_info = [{'nomor_tabel': target_table, 'pages': [target_pages[0]]}]
                    p2_info = [{'nomor_tabel': target_table, 'pages': [target_pages[1]]}]
                    extract_table_content(pdf_path, p1_info)
                    extract_table_content(pdf_path, p2_info)
                    p1_h = detect_col_headers(p1_info[0].get('extracted_data', []))
                    p2_h = detect_col_headers(p2_info[0].get('extracted_data', []))
                    if p1_h and p2_h and p1_h == p2_h:
                        is_row_continuation = True

                    if is_row_continuation:
                        for h in p1_h:
                            t_set, h_clean = _norm_hdr(h)
                            pdf_cols.append((h, t_set, h_clean, target_pages[0]))
                    else:
                        for p_num in target_pages:
                            target_page_info = [{'nomor_tabel': target_table, 'pages': [p_num]}]
                            extract_table_content(pdf_path, target_page_info)
                            p_page_rows = target_page_info[0].get('extracted_data', [])
                            p_hdrs = detect_col_headers(p_page_rows)
                            for h in p_hdrs:
                                t_set, h_clean = _norm_hdr(h)
                                pdf_cols.append((h, t_set, h_clean, p_num))
                else:
                    p_hdrs = detect_col_headers(target_pdf_rows)
                    for h in p_hdrs:
                        t_set, h_clean = _norm_hdr(h)
                        pdf_cols.append((h, t_set, h_clean, target_pages[0] if target_pages else None))

                used_p = set()
                for s_idx, sh in enumerate(headers):
                    s_tokens, sh_clean = _norm_hdr(sh)
                    best_p = None
                    best_score = 0
                    for p_idx, p_tuple in enumerate(pdf_cols):
                        ph, p_tokens, ph_clean = p_tuple[0], p_tuple[1], p_tuple[2]
                        if p_idx in used_p:
                            continue
                        overlap = len(s_tokens & p_tokens)
                        sim = SequenceMatcher(None, sh_clean, ph_clean).ratio()
                        if s_tokens == p_tokens or sh_clean == ph_clean:
                            best_p = p_idx
                            break
                        elif (overlap > best_score and (s_tokens.issubset(p_tokens) or p_tokens.issubset(s_tokens))) or sim >= 0.8:
                            if sim > best_score or overlap > best_score:
                                best_score = max(sim, overlap)
                                best_p = p_idx

                    # Prioritas Kesamaan Tahun Eksplisit (jika header sumber memuat tahun tertentu, misal '2025')
                    if best_p is None:
                        s_yrs = set(re.findall(r'\b(19|20\d{2})\b', sh))
                        if s_yrs:
                            for p_idx, p_tuple in enumerate(pdf_cols):
                                ph, p_tokens, ph_clean = p_tuple[0], p_tuple[1], p_tuple[2]
                                if p_idx in used_p:
                                    continue
                                p_yrs = set(re.findall(r'\b(19|20\d{2})\b', ph))
                                if s_yrs == p_yrs:
                                    best_p = p_idx
                                    break

                    if best_p is not None:
                        col_map[s_idx] = best_p
                        used_p.add(best_p)

                p_headers = detect_col_headers(target_pdf_rows)

                for s_rec in s_recs:
                    s_disp = s_rec['display']
                    s_nums = s_rec['nums']
                    p_nums = p_grouped.get(s_rec['id'], [])
                    if not p_nums:
                        continue

                    matched_p_cols = set()
                    for i in range(len(s_nums)):
                        p_col_idx = col_map.get(i, i) if col_map else i
                        if p_col_idx >= len(p_nums):
                            continue
                        matched_p_cols.add(p_col_idx)
                        sv = s_nums[i]
                        pv = p_nums[p_col_idx]
                        base_h = headers[i] if i < len(headers) else f"Metrik #{i+1}"
                        if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                            base_h = f"Metrik #{i+1}"
                        base_h = str(base_h).replace('||', ' - ')

                        p_sec = sec_b
                        if col_map and target_pages and len(target_pages) > 1 and pdf_cols and not is_row_continuation:
                            if p_col_idx < len(pdf_cols) and len(pdf_cols[p_col_idx]) >= 4 and pdf_cols[p_col_idx][3]:
                                p_sec = f"Halaman {pdf_cols[p_col_idx][3]}"
                            else:
                                cols_per_page = max(1, len(pdf_cols) // len(target_pages))
                                page_idx = min(len(target_pages) - 1, p_col_idx // cols_per_page)
                                p_sec = f"Halaman {target_pages[page_idx]}"

                        is_match, is_tol, diff_val = evaluate_cell_match(pv, sv, tolerance=tolerance)
                        abs_diff = format_display_val(abs(diff_val))

                        p_disp = format_display_val(pv)
                        src_disp = format_display_val(sv)

                        if target_year and hist_pdf_years and target_year not in str(base_h):
                            base_h = f"{base_h} ({target_year})"

                        if is_match:
                            m_item = {"wilayah": s_disp, "section": p_sec, "metric": base_h, "val": p_disp, "pdf_val": p_disp, "src_val": src_disp}
                            if is_tol:
                                m_item["is_tolerance_match"] = True
                                m_item["tolerance_diff"] = round(diff_val, 4)
                            results["matches"].append(m_item)
                        else:
                            note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})" + _hint(pv, sv)
                            results["diffs"].append({
                                "wilayah": s_disp, "section": p_sec, "metric": base_h,
                                "pdf_val": p_disp, "src_val": src_disp,
                                "diff": diff_val, "note": note, "type": "replace"
                            })

                    # Kolom-kolom di PDF yang tidak ada di sumber database (misal multi-tahun horizontal) dialirkan ke pdf_only
                    unmatched_p_cols = [p_idx for p_idx in range(len(p_nums)) if p_idx not in matched_p_cols]
                    if unmatched_p_cols:
                        for p_idx in unmatched_p_cols:
                            pv = p_nums[p_idx]
                            if pv is None:
                                continue
                            base_h = p_headers[p_idx] if (p_headers and p_idx < len(p_headers)) else f"Kolom Ekstra #{p_idx+1}"
                            base_h = str(base_h).replace('||', ' - ')
                            pv_disp = format_display_val(pv)
                            m_yr = re.search(r'\b(20\d{2})\b', str(base_h))
                            if m_yr:
                                note = f"Tercetak di buku PDF untuk Tahun {m_yr.group(1)}, tidak tersedia di database resmi"
                            else:
                                note = f"Tercetak di buku PDF untuk {base_h}, tidak tersedia di database resmi"

                            p_sec_extra = sec_b
                            if target_pages and len(target_pages) > 1 and pdf_cols and not is_row_continuation:
                                if p_idx < len(pdf_cols) and len(pdf_cols[p_idx]) >= 4 and pdf_cols[p_idx][3]:
                                    p_sec_extra = f"Halaman {pdf_cols[p_idx][3]}"

                            results["pdf_only"].append({
                                "wilayah": s_disp, "section": p_sec_extra, "metric": base_h,
                                "values": [pv_disp], "note": note, "type": "delete"
                            })

                for idx_p, p in enumerate(p_recs):
                    if idx_p not in used_p_indices:
                        p_disp = p['display']
                        p_sec = p.get('sec') or sec_b
                        for i, pv in enumerate(p['nums']):
                            base_h = p_headers[i] if (p_headers and i < len(p_headers)) else (headers[i] if i < len(headers) else f"Metrik #{i+1}")
                            if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                base_h = f"Metrik #{i+1}"
                            pv_disp = format_display_val(pv)
                            results["pdf_only"].append({
                                "wilayah": p_disp, "section": p_sec, "metric": base_h,
                                "values": [pv_disp],
                                "note": "Tercetak di buku PDF, tetapi tidak ditemukan di database resmi",
                                "type": "delete"
                            })

            for hy, h_rows in hist_pdf_years.items():
                h_recs = extract_cat_rows_std(h_rows, is_pdf=True)
                for h_rec in h_recs:
                    match_s = next((s for s in s_recs if s['key'] == h_rec['key']), None)
                    disp = match_s['display'] if match_s else h_rec['display']
                    for i, pv in enumerate(h_rec['nums']):
                        base_h = headers[i] if i < len(headers) else f"Metrik #{i+1}"
                        if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                            base_h = f"Metrik #{i+1}"
                        p_disp = format_display_val(pv)
                        results["pdf_only"].append({
                            "wilayah": disp, "section": sec_b, "metric": f"{base_h} ({hy})",
                            "values": [p_disp],
                            "note": f"Data historis ({hy}) tercetak di buku PDF, tidak dimuat di database {target_year or ''}".strip(),
                            "type": "delete"
                        })

        else:
            clean_source_rows = source_data
            src_title = ' '.join(str(c) for c in (clean_source_rows[0] if clean_source_rows else []))
            title_years = re.findall(r'\b(20\d{2})\b', src_title)
            header_years = re.findall(r'\b(20\d{2})\b', ' '.join(headers))
            target_year = header_years[0] if header_years else (title_years[-1] if title_years else (api_year if 'api_year' in locals() and api_year else None))

            hist_pdf_years_reg = {}

            def get_clean_rows_dict(data_list, is_pdf=False, expected_len=None, target_year=None):
                out = {}
                is_k = False
                brebes_seen = False
                is_prov = False
                common_first_data_col = None
                active_cols = detect_active_data_cols(data_list) if not is_pdf else []

                has_year_subrows = False
                year_col_idx = -1
                if data_list:
                    subrow_pairs = 0
                    cur_chk_w = None
                    cur_chk_y = None
                    for r in data_list[:35]:
                        if not isinstance(r, list) or len(r) < 3 or is_header_or_metadata_row(r):
                            continue
                        r_y = None
                        r_y_idx = -1
                        for idx_c, c in enumerate(r[:5]):
                            c_clean = re.sub(r'^[a-zA-Z\s\.\/:\n\r]+', '', str(c)).strip()
                            if re.match(r'^(?:19|20)\d{2}$', c_clean):
                                r_y = c_clean
                                r_y_idx = idx_c
                                break

                        lbl_parts = [str(c).strip() for c in r[:r_y_idx] if str(c).strip() and str(c).strip().lower() != 'none' and parse_num(str(c).strip(), is_pdf=is_pdf) is None] if r_y_idx >= 0 else []
                        lbl_txt = ' '.join(lbl_parts)
                        found_w = find_wilayah_with_type(lbl_txt) if lbl_txt else "Umum"

                        if found_w != 'Umum':
                            cur_chk_w = found_w
                            cur_chk_y = r_y
                            if year_col_idx == -1 and r_y_idx >= 0:
                                year_col_idx = r_y_idx
                        elif not lbl_txt and cur_chk_w and r_y and r_y != cur_chk_y:
                            subrow_pairs += 1
                            if year_col_idx == -1 and r_y_idx >= 0:
                                year_col_idx = r_y_idx

                    if subrow_pairs >= 2 and year_col_idx >= 0:
                        has_year_subrows = True

                if has_year_subrows:
                    cur_w = None
                    by_w_year = {}
                    for r in data_list:
                        if not isinstance(r, list) or len(r) < 3:
                            continue
                        txt = ' '.join(str(c) for c in r).lower()
                        if 'kota/municipality' in txt:
                            is_k = True
                            brebes_seen = True
                        elif 'kabupaten/regency' in txt:
                            is_k = False
                            brebes_seen = False
                        elif 'provinsi/province' in txt or (txt.strip().startswith('provinsi') and '/' in txt and not any(parse_num(c, is_pdf=is_pdf) for c in r)):
                            is_prov = True
                            continue
                        if is_header_or_metadata_row(r):
                            continue

                        c0 = str(r[0]).strip().rstrip('.')
                        if c0.isdigit():
                            num0 = int(c0)
                            if num0 > 6:
                                is_k = False
                            elif brebes_seen and 1 <= num0 <= 6:
                                is_k = True

                        row_year = None
                        row_y_col = year_col_idx
                        for idx_c, c in enumerate(r[:5]):
                            m_y = re.search(r'\b((?:19|20)\d{2})\b', str(c))
                            if m_y:
                                row_year = m_y.group(1)
                                row_y_col = idx_c
                                break

                        label_parts = [str(c).strip() for c in r[:row_y_col] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None]
                        row_label = ' '.join(label_parts) if label_parts else ''

                        if is_prov and (c0 == '1' or 'jawa tengah' in row_label.lower() or 'provinsi' in row_label.lower()):
                            cur_w = 'Provinsi Jawa Tengah'
                            is_prov = False
                        elif 'provinsi jawa tengah' in row_label.lower() or 'pemprov' in row_label.lower():
                            cur_w = 'Provinsi Jawa Tengah'
                            is_prov = False
                        elif any(k in row_label.lower() for k in ['jumlah', 'total']) and not any(k in row_label.lower() for k in ['curah', 'hari', 'hujan', 'penduduk']):
                            cur_w = 'Total Jawa Tengah'
                            is_prov = False
                        else:
                            prov_code, prov_name = resolve_provinsi_entity(row_label)
                            if prov_name:
                                cur_w = prov_name
                                is_prov = False
                            elif row_label:
                                found_w = find_wilayah_with_type(row_label, is_kota=is_k)
                                if found_w != "Umum":
                                    cur_w = found_w
                            elif any('jawa tengah' in str(c).lower() for c in r[:row_y_col+1]):
                                cur_w = "Total Jawa Tengah"

                        if cur_w and ('Brebes' in cur_w or cur_w == 'Kota Tegal'):
                            brebes_seen = True

                        if cur_w and row_year:
                            nums = []
                            for c in r[row_y_col+1:]:
                                v = parse_num(c, is_pdf=is_pdf)
                                if v is not None:
                                    nums.append(v)
                            if cur_w not in by_w_year:
                                by_w_year[cur_w] = {}
                            by_w_year[cur_w][row_year] = nums

                    all_years = sorted({y for y_dict in by_w_year.values() for y in y_dict})
                    active_y = target_year if (target_year and any(target_year in y_dict for y_dict in by_w_year.values())) else (all_years[-1] if all_years else None)
                    for w_entity, y_dict in by_w_year.items():
                        out[w_entity] = y_dict.get(active_y, [])
                        if is_pdf:
                            for y_hist, nums in y_dict.items():
                                if y_hist != active_y:
                                    if y_hist not in hist_pdf_years_reg:
                                        hist_pdf_years_reg[y_hist] = {}
                                    hist_pdf_years_reg[y_hist][w_entity] = nums
                    return out

                for r in data_list:
                    if not isinstance(r, list):
                        continue
                    txt = ' '.join(str(c) for c in r).lower()
                    if is_pdf and any(k in txt for k in ['tabel', 'table']):
                        m_next = re.search(r'tabel\s+(\d+\.\d+\.\d+)', txt)
                        if m_next and m_next.group(1).strip('.') != target_table.strip('.'):
                            break
                    if 'kota/municipality' in txt:
                        is_k = True
                        brebes_seen = True
                    elif 'kabupaten/regency' in txt:
                        is_k = False
                        brebes_seen = False
                    elif 'provinsi/province' in txt or (txt.strip().startswith('provinsi') and '/' in txt and not any(parse_num(c) for c in r)):
                        is_prov = True
                        continue
                    if is_header_or_metadata_row(r):
                        continue

                    c0 = str(r[0]).strip().rstrip('.')
                    if c0.isdigit():
                        num0 = int(c0)
                        if num0 > 6:
                            is_k = False
                        elif brebes_seen and 1 <= num0 <= 6:
                            is_k = True

                    label_parts = [str(c).strip() for c in r[:3] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None]
                    row_label = ' '.join(label_parts) if label_parts else ' '.join(str(c) for c in r[:2])

                    if is_prov and (c0 == '1' or 'jawa tengah' in row_label.lower() or 'provinsi' in row_label.lower()):
                        w = 'Provinsi Jawa Tengah'
                        is_prov = False
                    elif 'provinsi jawa tengah' in row_label.lower() or 'pemprov' in row_label.lower():
                        w = 'Provinsi Jawa Tengah'
                        is_prov = False
                    elif any(k in row_label.lower() for k in ['jumlah', 'total']) and not any(k in row_label.lower() for k in ['curah', 'hari', 'hujan', 'penduduk', 'kecamatan', 'desa', 'kelurahan', 'tps', 'sekolah', 'guru', 'murid', 'kabupaten', 'kota']):
                        w = 'Total Jawa Tengah'
                        is_prov = False
                    else:
                        prov_code, prov_name = resolve_provinsi_entity(row_label)
                        if prov_name:
                            w = prov_name
                            is_prov = False
                        else:
                            w = find_wilayah_with_type(row_label, is_kota=is_k)
                            if w == "Umum":
                                if any('jawa tengah' in str(c).lower() for c in r):
                                    w = "Total Jawa Tengah"
                                elif brebes_seen and is_k and (not label_parts or 'total' in txt or 'jumlah' in txt) and any(parse_num(str(c).strip(), is_pdf=is_pdf) is not None for c in r):
                                    w = "Total Jawa Tengah"
                                else:
                                    continue

                    if 'Brebes' in w or w == 'Kota Tegal':
                        brebes_seen = True

                    first_idx = next((i for i, c in enumerate(r) if str(c).strip()), -1)
                    raw_w = w.replace('Kab. ', '').replace('Kota ', '')
                    nums = []

                    if is_pdf:
                        for idx, c in enumerate(r):
                            c_str = str(c).strip()
                            if not c_str:
                                continue
                            if idx == first_idx and c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                continue
                            if c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                next_val = next((str(r[k]).strip() for k in range(idx+1, len(r)) if str(r[k]).strip()), '')
                                if any(k.lower() in next_val.lower() for k in [raw_w]) or any(k.lower() in next_val.lower() for k in KAB_KOTA_JATENG):
                                    continue
                            if any(k.lower() in c_str.lower() for k in [raw_w]):
                                continue
                            if idx <= 3 and re.match(r'^33\.\d{2}$', c_str):
                                continue

                            val = parse_num(c_str, is_pdf=True)
                            if val is not None:
                                nums.append(val)
                    else:
                        if active_cols:
                            raw_vals = [parse_num(str(r[c]).strip(), is_pdf=False) for c in active_cols if c < len(r)]
                            if not any(v is not None for v in raw_vals):
                                continue
                            for c_idx in active_cols:
                                c_str = str(r[c_idx]).strip() if c_idx < len(r) else ''
                                v = parse_num(c_str, is_pdf=False)
                                nums.append(0.0 if v is None else v)
                        elif w in ('Total Jawa Tengah', 'Jawa Tengah', 'Provinsi Jawa Tengah') and common_first_data_col is not None:
                            first_data_col = common_first_data_col
                        else:
                            w_idx = -1
                            for i, c in enumerate(r[:4]):
                                c_s = str(c).strip()
                                if raw_w.lower() in c_s.lower():
                                    w_idx = i
                                    break
                            first_data_col = 2 if w_idx == -1 else w_idx + 1
                            if first_data_col < len(r) and re.match(r'^33\.\d{2}$', str(r[first_data_col]).strip()):
                                first_data_col += 1
                            if w not in ('Total Jawa Tengah', 'Jawa Tengah', 'Provinsi Jawa Tengah'):
                                common_first_data_col = first_data_col

                        if not active_cols:
                            if expected_len is None:
                                for idx, c in enumerate(r):
                                    c_str = str(c).strip()
                                    if not c_str:
                                        continue
                                    if idx == first_idx and c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                        continue
                                    if c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                        next_val = next((str(r[k]).strip() for k in range(idx+1, len(r)) if str(r[k]).strip()), '')
                                        if any(k.lower() in next_val.lower() for k in [raw_w]) or any(k.lower() in next_val.lower() for k in KAB_KOTA_JATENG):
                                            continue
                                    if any(k.lower() in c_str.lower() for k in [raw_w]):
                                        continue
                                    if idx <= 3 and re.match(r'^33\.\d{2}$', c_str):
                                        continue
                                    val = parse_num(c_str, is_pdf=False)
                                    if val is not None:
                                        nums.append(val)
                            else:
                                row_data = r[first_data_col: first_data_col + expected_len]
                                for c in row_data:
                                    c_str = str(c).strip()
                                    if not c_str:
                                        nums.append(0.0)
                                    else:
                                        v = parse_num(c_str, is_pdf=False)
                                        nums.append(0.0 if v is None else v)

                    if nums:
                        if w not in out:
                            out[w] = []
                        out[w].extend(nums)
                return out

            # Deteksi Page-Per-Year pada tabel Regional multi-halaman
            page_years = {}
            if target_pages and len(target_pages) > 1:
                for p in target_pages:
                    p_tables = get_cached_page_tables(pdf_path, p)
                    p_y = None
                    for r_chk in p_tables[:5]:
                        if not isinstance(r_chk, list):
                            continue
                        for c in r_chk:
                            c_str = str(c).strip() if c else ''
                            if re.match(r'^(?:19|20)\d{2}$', c_str):
                                p_y = c_str
                                break
                        if p_y:
                            break
                        m_txt = re.findall(r'\b(20\d{2})\b', ' '.join(str(c) for c in r_chk))
                        if m_txt and len(set(m_txt)) == 1:
                            p_y = m_txt[0]
                            break
                    if p_y:
                        page_years[p] = p_y

            is_page_per_year = (
                len(set(page_years.values())) >= 2
                and target_year in page_years.values()
                and len(set(header_years)) <= 1
            )

            if is_page_per_year:
                active_pages = [p for p, y in page_years.items() if y == target_year]
                active_rows = []
                for p in active_pages:
                    active_rows.extend(get_cached_page_tables(pdf_path, p))

                pdf_dict = get_clean_rows_dict(active_rows, is_pdf=True, target_year=target_year)

                # Ekstrak data historis untuk halaman tahun selain target_year
                for hy in sorted(set(page_years.values())):
                    if hy != target_year:
                        hy_pages = [p for p, y in page_years.items() if y == hy]
                        hy_rows = []
                        for p in hy_pages:
                            hy_rows.extend(get_cached_page_tables(pdf_path, p))
                        hist_pdf_years_reg[hy] = get_clean_rows_dict(hy_rows, is_pdf=True, target_year=hy)

                rows_for_headers = active_rows
                reg_page_label = f"Halaman {active_pages[0]} ({target_year})" if len(active_pages) == 1 else f"Halaman {active_pages[0]}-{active_pages[-1]} ({target_year})"
            else:
                pdf_dict = get_clean_rows_dict(rows, is_pdf=True, target_year=target_year)
                rows_for_headers = rows
                reg_page_label = f"Halaman {target_pages[0]}" if len(target_pages) == 1 else (f"Halaman {target_pages[0]}-{target_pages[-1]}" if len(target_pages) > 1 else "")

            sample_p_nums = next((nums for nums in pdf_dict.values() if nums), [])
            p_len = len(sample_p_nums)
            src_dict = get_clean_rows_dict(clean_source_rows, is_pdf=False, expected_len=p_len, target_year=target_year)

            sample_s_nums = next((nums for nums in src_dict.values() if nums), [])
            s_len = len(sample_s_nums)

            pdf_headers = extract_pdf_headers(rows_for_headers, expected_p_len=p_len, pdf_path=pdf_path, target_pages=active_pages if is_page_per_year else target_pages, target_table=target_table)

            if len(headers) > s_len and any('tahun' in h.lower() for h in headers[:len(headers)-s_len]):
                headers = headers[-s_len:]

            col_mapping, pdf_extra_indices, col_year_tags, final_metric_names = align_columns_semantically(
                headers, pdf_headers, src_dict, pdf_dict, target_year=target_year, table_title=src_title
            )

            empty_src_cols = set()
            for c_idx in range(s_len):
                if all(s_vals[c_idx] == 0.0 for s_vals in src_dict.values() if c_idx < len(s_vals)):
                    empty_src_cols.add(c_idx)

            for w, p_nums in pdf_dict.items():
                s_nums = src_dict.get(w, [])
                if not s_nums:
                    if w == "Total Jawa Tengah":
                        for p_idx, pv in enumerate(p_nums):
                            p_disp = format_display_val(pv)
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Metrik #{p_idx + 1}"
                            results["pdf_only"].append({
                                "wilayah": w, "section": reg_page_label, "metric": base_m,
                                "values": [p_disp], "note": "Total Jawa Tengah tercetak di buku PDF, tidak dimuat di database sumber",
                                "type": "delete"
                            })
                    continue

                for s_idx, p_idx in col_mapping.items():
                    if p_idx >= len(p_nums) or s_idx >= len(s_nums):
                        continue
                    pv = p_nums[p_idx]
                    sv = s_nums[s_idx]
                    metric_name = final_metric_names.get(s_idx, (headers[s_idx] if s_idx < len(headers) else f"Metrik #{s_idx + 1}"))
                    if is_page_per_year and target_year and target_year not in str(metric_name):
                        metric_name = f"{metric_name} ({target_year})"

                    is_match, is_tol, diff_val = evaluate_cell_match(pv, sv, tolerance=tolerance)
                    abs_diff = format_display_val(abs(diff_val))

                    p_disp = format_display_val(pv)
                    s_disp = format_display_val(sv)

                    if is_match:
                        m_item = {"wilayah": w, "section": reg_page_label, "metric": metric_name, "val": p_disp, "pdf_val": p_disp, "src_val": s_disp}
                        if is_tol:
                            m_item["is_tolerance_match"] = True
                            m_item["tolerance_diff"] = round(diff_val, 4)
                        results["matches"].append(m_item)
                    else:
                        if s_idx in empty_src_cols and sv == 0.0:
                            note = f"Di cetakan PDF tertulis {p_disp}, tetapi di database kolom ini kosong"
                        else:
                            note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})" + _hint(pv, sv)
                        results["diffs"].append({
                            "wilayah": w, "section": reg_page_label, "metric": metric_name, "pdf_val": p_disp,
                            "src_val": s_disp, "diff": diff_val, "note": note, "type": "replace"
                        })

                if pdf_extra_indices:
                    for p_idx in pdf_extra_indices:
                        if p_idx < len(p_nums):
                            pv = p_nums[p_idx]
                            pv_disp = format_display_val(pv)
                            y_tag = col_year_tags.get(p_idx, "")
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Kolom Ekstra #{p_idx + 1}"

                            m_name = f"{base_m} ({y_tag})" if (y_tag and y_tag not in base_m) else base_m
                            note = f"Data historis ({y_tag}) tercetak di buku PDF, tidak dimuat di database sumber" if y_tag else "Tercetak di PDF tapi tidak ada kolomnya di database"

                            results["pdf_only"].append({
                                "wilayah": w, "section": reg_page_label, "metric": m_name, "values": [pv_disp], "note": note, "type": "delete"
                            })

            if hist_pdf_years_reg:
                for hy, w_dict in hist_pdf_years_reg.items():
                    hy_pages = [p for p, y in page_years.items() if y == hy] if 'page_years' in locals() else []
                    hy_sec = f"Halaman {hy_pages[0]} ({hy})" if len(hy_pages) == 1 else (f"Halaman {hy_pages[0]}-{hy_pages[-1]} ({hy})" if hy_pages else f"Tahun {hy}")
                    for w_ent, nums in w_dict.items():
                        for p_idx, pv in enumerate(nums):
                            pv_disp = format_display_val(pv)
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Metrik #{p_idx + 1}"
                            base_m = re.sub(r'^(?:19|20)\d{2}\s*[\-–—]\s*', '', str(base_m)).strip()
                            if not base_m or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_m).lower().strip()):
                                base_m = f"Metrik #{p_idx + 1}"
                            metric_lbl = f"{base_m} ({hy})" if hy not in base_m else base_m
                            results["pdf_only"].append({
                                "wilayah": w_ent, "section": hy_sec, "metric": metric_lbl,
                                "values": [pv_disp], "note": f"Data historis ({hy}) tercetak di buku PDF, tidak dimuat di database sumber {target_year or ''}".strip(),
                                "type": "delete"
                            })

    results["pages"] = target_pages or []
    results["id_db"] = target_info.get("id_db")
    results["id_indikator"] = target_info.get("id_indikator")

    if is_template_extraction:
        autofill_cells = []
        for d in results.get("diffs", []):
            sv = d.get("src_val")
            if isinstance(sv, (int, float)) and int(round(sv)) <= -900001:
                seq_key = str(abs(int(round(sv))) - 900000)
                coord = cell_coords.get(seq_key)
                if coord:
                    autofill_cells.append({
                        "id_baris_item": coord["b"],
                        "id_karakteristik_item": coord["k"],
                        "id_periode_item": coord["p"],
                        "nilai": d.get("pdf_val"),
                        "wilayah": d.get("wilayah", ""),
                        "metric": d.get("metric", ""),
                        "section": d.get("section", ""),
                    })
        return {
            "status": "error",
            "source_type": "Tabel Dinamis",
            "message": "Data Tabel Dinamis belum diisi",
            "is_api_empty": True,
            "id_db": target_info.get("id_db"),
            "id_indikator": target_info.get("id_indikator"),
            "pages": target_pages or [],
            "can_autofill": len(autofill_cells) > 0,
            "autofill_count": len(autofill_cells),
            "autofill_cells": autofill_cells,
        }

    if is_dynamic and results.get("diffs") and tpl_grid and cell_coords:
        try:
            tpl_info = dict(target_info)
            tpl_info["dynamic_empty"] = True
            tpl_db_list = [tpl_info]
            import tempfile
            with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
                json.dump(tpl_db_list, tf, ensure_ascii=False)
                tmp_path = tf.name
            try:
                tpl_res = compare_head_to_head(pdf_path, target_table, tmp_path, tolerance=tolerance, id_db=id_db)
                coord_lookup = {}
                for c_item in tpl_res.get("autofill_cells", []):
                    k_lookup = (str(c_item.get("wilayah", "")).lower().strip(), str(c_item.get("metric", "")).lower().strip())
                    coord_lookup[k_lookup] = {
                        "id_baris_item": c_item["id_baris_item"],
                        "id_karakteristik_item": c_item["id_karakteristik_item"],
                        "id_periode_item": c_item["id_periode_item"],
                    }
                for d in results["diffs"]:
                    k_d = (str(d.get("wilayah", "")).lower().strip(), str(d.get("metric", "")).lower().strip())
                    if k_d in coord_lookup:
                        d["coord"] = coord_lookup[k_d]
            finally:
                if os.path.exists(tmp_path):
                    os.remove(tmp_path)
        except Exception:
            pass

    results["summary"] = {
        "total_diffs": len(results["diffs"]),
        "total_matches": len(results["matches"]),
        "total_pdf_only": len(results["pdf_only"]),
    }

    results["mismatches"] = results["diffs"] + results["pdf_only"]
    results["replace_count"] = len(results["diffs"])
    results["delete_count"] = len(results["pdf_only"])
    results["pdf_count"] = len(results["diffs"]) + len(results["matches"]) + len(results["pdf_only"])
    results["source_count"] = len(results["diffs"]) + len(results["matches"])
    results["data_row_count"] = len(source_data) if isinstance(source_data, list) else 0

    return results


def main():
    if len(sys.argv) < 4:
        print(json.dumps({"status": "error", "message": "Missing arguments"}))
        return 1

    pdf_path = sys.argv[1]
    db_json_path = sys.argv[2]
    target_table = sys.argv[3]
    tolerance = float(sys.argv[4]) if len(sys.argv) > 4 else 0.0
    id_db = sys.argv[5] if len(sys.argv) > 5 else None

    try:
        res = compare_head_to_head(pdf_path, target_table, db_json_path, tolerance=tolerance, id_db=id_db)
        print(json.dumps(res))
        return 0
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
        return 1


if __name__ == "__main__":
    sys.exit(main())
