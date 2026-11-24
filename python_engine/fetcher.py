# Mengunduh data live dari Portal Satu Data API

import os
import ssl
import json
import time
import socket
import hashlib
import urllib.request
import urllib.error
from config import SATUDATA_TOKEN, HTTP_TIMEOUT, HTTP_USER_AGENT, SATUDATA_INSECURE_SSL

API_URL = "https://satudata.jatengprov.go.id/api/v1/data/{id}?peruntukan=DDA"
MAX_ATTEMPTS = 3
MAX_PAGES = 200
BACKOFF_SECONDS = 1.5
RETRYABLE_HTTP = {429, 502, 503, 504}
# Server Satu Data membalas 404/500 untuk dataset yang belum berisi data -> diperlakukan "kosong".
EMPTY_HTTP = {404, 500}


def _ssl_context():
    if SATUDATA_INSECURE_SSL:
        return ssl._create_unverified_context()
    return ssl.create_default_context()


def _error(message, kind, detail="", **flags):
    out = {"error": message, "error_kind": kind}
    if detail:
        out["detail"] = detail
    out.update(flags)
    return out


def _request_once(api_url, ssl_ctx):
    req = urllib.request.Request(api_url, headers={
        "User-Agent": HTTP_USER_AGENT,
        "Authorization": f"Bearer {SATUDATA_TOKEN}",
        "Accept": "application/json",
    })
    with urllib.request.urlopen(req, timeout=HTTP_TIMEOUT, context=ssl_ctx) as response:
        return json.loads(response.read().decode("utf-8"))


def fetch_data_from_link(link: str):
    if not link or not isinstance(link, str):
        return _error("Tautan data kosong", "empty_link", is_link_empty=True)
    link = link.strip('\r\n\t ')
    if not link:
        return _error("Tautan data kosong", "empty_link", is_link_empty=True)

    if not ("view_portal_tabel" in link or "satudata" in link) or "?id=" not in link:
        return _error("Tabel ini belum dikonfigurasi ke Tabel Dinamis atau API Satu Data",
                      "unsupported_link", is_link_unsupported=True)

    if not SATUDATA_TOKEN:
        return _error("SATUDATA_TOKEN belum di-set (isi di .env atau environment)", "missing_token")

    id_param = link.split("?id=")[1].split("&")[0].strip()
    raw_ids = [x.strip() for x in id_param.split(",") if x.strip()]
    if not raw_ids:
        return _error("ID data tidak ditemukan pada tautan", "invalid_id")

    for id_p in raw_ids:
        if not id_p.isdigit():
            return _error(f"ID data tidak valid pada tautan: '{id_p}'", "invalid_id")

    ssl_ctx = _ssl_context()
    base_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    cache_dir = os.path.join(base_root, "writable", "cache", "api")

    combined_records = []
    last_err = None

    for id_p in raw_ids:
        api_url = API_URL.format(id=id_p)
        cache_file = os.path.join(cache_dir, f"{id_p}.json")
        id_records = None
        last = None

        for attempt in range(1, MAX_ATTEMPTS + 1):
            try:
                all_rows = []
                seen_page_fps = set()
                empty_or_invalid = False
                for page in range(1, MAX_PAGES + 1):
                    page_url = api_url if page == 1 else f"{api_url}&page={page}"
                    data = _request_once(page_url, ssl_ctx)
                    rows = data.get("data", data) if isinstance(data, dict) else data
                    if not rows:
                        if page == 1:
                            empty_or_invalid = True
                        break
                    if not isinstance(rows, list):
                        all_rows.append(rows)
                        break

                    fp = hashlib.md5(json.dumps(rows[0], sort_keys=True, ensure_ascii=False, default=str).encode("utf-8")).hexdigest()
                    if fp in seen_page_fps:
                        break
                    seen_page_fps.add(fp)
                    all_rows.extend(rows)

                    total_pages = None
                    if isinstance(data, dict):
                        pag = data.get("pagination") if isinstance(data.get("pagination"), dict) else {}
                        meta = data.get("_meta") if isinstance(data.get("_meta"), dict) else {}
                        total_pages = (
                            pag.get("total_pages")
                            or meta.get("pageCount")
                            or data.get("total_pages")
                            or data.get("pages")
                            or data.get("total_page")
                        )
                    if total_pages is not None:
                        try:
                            if page >= int(total_pages):
                                break
                        except (ValueError, TypeError):
                            pass
                    if len(rows) < 100:
                        break

                if empty_or_invalid or not all_rows:
                    last = _error("Tidak ada data dalam API", "empty", is_api_empty=True)
                    break
                try:
                    os.makedirs(cache_dir, exist_ok=True)
                    tmp_cache = f"{cache_file}.tmp.{os.getpid()}"
                    with open(tmp_cache, "w", encoding="utf-8") as f:
                        json.dump(all_rows, f, ensure_ascii=False)
                    os.replace(tmp_cache, cache_file)
                except Exception:
                    pass
                id_records = all_rows
                break
            except urllib.error.HTTPError as e:
                detail = f"HTTP Error {e.code}: {e.reason}"
                if e.code in EMPTY_HTTP:
                    last = _error("Tidak ada data dalam API", "empty", detail, is_api_empty=True)
                    break
                if e.code in (401, 403):
                    return _error("Token Satu Data ditolak (periksa SATUDATA_TOKEN)", "auth", detail)
                last = _error(f"API Satu Data error: {detail}", "http", detail)
                if e.code not in RETRYABLE_HTTP:
                    break
            except (urllib.error.URLError, socket.timeout, TimeoutError, ConnectionError) as e:
                reason = getattr(e, "reason", e)
                kind = "ssl" if isinstance(reason, ssl.SSLError) else "network"
                last = _error(f"Gagal terhubung ke API Satu Data: {reason}", kind, str(reason))
            except (ValueError, json.JSONDecodeError) as e:
                last = _error(f"Respons API bukan JSON valid: {e}", "bad_json", str(e))
                break

            if attempt < MAX_ATTEMPTS:
                time.sleep(BACKOFF_SECONDS * attempt)

        if id_records is None and last and last.get("error_kind") != "empty":
            # Fallback ke cache lokal jika server Satu Data sedang 502/down
            try:
                if os.path.exists(cache_file):
                    with open(cache_file, "r", encoding="utf-8") as f:
                        cached_data = json.load(f)
                        if cached_data:
                            id_records = cached_data
            except Exception:
                pass

        if isinstance(id_records, list):
            combined_records.extend(id_records)
        elif id_records is not None:
            combined_records.append(id_records)
        elif last:
            last_err = last

    if combined_records:
        return combined_records
    return last_err or _error("Tidak ada data dalam API", "empty", is_api_empty=True)
