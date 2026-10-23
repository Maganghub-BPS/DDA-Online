# Mengunduh data live dari Portal Satu Data API

import ssl
import json
import time
import socket
import urllib.request
import urllib.error
from config import SATUDATA_TOKEN, HTTP_TIMEOUT, HTTP_USER_AGENT, SATUDATA_INSECURE_SSL

API_URL = "https://satudata.jatengprov.go.id/api/v1/data/{id}?peruntukan=DDA"
MAX_ATTEMPTS = 3
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

    id_p = link.split("?id=")[1].split(",")[0].strip()
    if not id_p.isdigit():
        return _error(f"ID data tidak valid pada tautan: '{id_p}'", "invalid_id")

    api_url = API_URL.format(id=id_p)
    ssl_ctx = _ssl_context()
    last = None

    for attempt in range(1, MAX_ATTEMPTS + 1):
        try:
            data = _request_once(api_url, ssl_ctx)
            records = data.get("data", data) if isinstance(data, dict) else data
            if not records:
                return _error("Tidak ada data dalam API", "empty", is_api_empty=True)
            return records
        except urllib.error.HTTPError as e:
            detail = f"HTTP Error {e.code}: {e.reason}"
            if e.code in EMPTY_HTTP:
                return _error("Tidak ada data dalam API", "empty", detail, is_api_empty=True)
            if e.code in (401, 403):
                return _error("Token Satu Data ditolak (periksa SATUDATA_TOKEN)", "auth", detail)
            last = _error(f"API Satu Data error: {detail}", "http", detail)
            if e.code not in RETRYABLE_HTTP:
                return last
        except (urllib.error.URLError, socket.timeout, TimeoutError, ConnectionError) as e:
            reason = getattr(e, "reason", e)
            kind = "ssl" if isinstance(reason, ssl.SSLError) else "network"
            last = _error(f"Gagal terhubung ke API Satu Data: {reason}", kind, str(reason))
        except (ValueError, json.JSONDecodeError) as e:
            return _error(f"Respons API bukan JSON valid: {e}", "bad_json", str(e))

        if attempt < MAX_ATTEMPTS:
            time.sleep(BACKOFF_SECONDS * attempt)

    return last
