# Mengunduh data live dari Portal Satu Data API

import ssl
import json
import urllib.request
import urllib.error
from config import SATUDATA_TOKEN, HTTP_TIMEOUT, HTTP_USER_AGENT


def fetch_data_from_link(link: str):
    if not link or not isinstance(link, str):
        return {"error": "Tautan data kosong", "is_link_empty": True}
    link = link.strip('\r\n\t')
    if not link:
        return {"error": "Tautan data kosong", "is_link_empty": True}

    ssl_ctx = ssl._create_unverified_context()

    try:
        if "view_portal_tabel" in link or "satudata" in link:
            if "?id=" in link:
                ids = link.split("?id=")[1]
                id_p = ids.split(",")[0].strip()
                api_url = f"https://satudata.jatengprov.go.id/api/v1/data/{id_p}?peruntukan=DDA"
                req = urllib.request.Request(api_url, headers={
                    "User-Agent": HTTP_USER_AGENT,
                    "Authorization": f"Bearer {SATUDATA_TOKEN}",
                    "Accept": "application/json"
                })
                try:
                    with urllib.request.urlopen(req, timeout=HTTP_TIMEOUT, context=ssl_ctx) as response:
                        data = json.loads(response.read().decode("utf-8"))
                        records = data.get("data", data)
                        if not records or len(records) == 0:
                            return {"error": "Tidak ada data dalam API", "is_api_empty": True}
                        return records
                except urllib.error.HTTPError as e:
                    return {"error": "Tidak ada data dalam API", "is_api_empty": True, "detail": f"HTTP Error {e.code}: {e.reason}"}
                except Exception as e:
                    return {"error": "Tidak ada data dalam API", "is_api_empty": True, "detail": str(e)}

        return {"error": "Tabel ini belum dikonfigurasi ke Tabel Dinamis atau API Satu Data", "is_link_unsupported": True}
    except Exception as e:
        return {"error": str(e)}
