#Modul Konfigurasi
import os

_PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


def _load_dotenv(path):
    """Parser .env minimal (KEY=VALUE). Tidak menimpa variabel yang sudah ada di environment."""
    if not os.path.isfile(path):
        return
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, _, val = line.partition("=")
            key = key.strip()
            val = val.strip().strip('"').strip("'")
            if key and key not in os.environ:
                os.environ[key] = val


_load_dotenv(os.path.join(_PROJECT_ROOT, ".env"))

# API Satu Data Jateng. WAJIB di-set lewat environment / .env (lihat python_engine/sample.env).
SATUDATA_TOKEN = os.environ.get("SATUDATA_TOKEN", "").strip()

HTTP_TIMEOUT = int(os.environ.get("HTTP_TIMEOUT", "15"))
HTTP_USER_AGENT = os.environ.get(
    "HTTP_USER_AGENT",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)

# Verifikasi SSL aktif secara default. Set SATUDATA_INSECURE_SSL=1 HANYA untuk debugging lokal.
SATUDATA_INSECURE_SSL = os.environ.get("SATUDATA_INSECURE_SSL", "0") == "1"
