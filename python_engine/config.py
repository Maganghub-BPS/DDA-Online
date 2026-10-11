#Modul Konfigurasi 
import os

# API Satu Data Jateng
SATUDATA_TOKEN = os.environ.get(
    "SATUDATA_TOKEN",
    "sdj_UYf5h0TPgUW3E0kcZisn33xrTe5PMRqLuXq6CWbAfb69tEA7dILT0TLhpFtjQiWHqrd1pn9SGvE4NYlN"
)

HTTP_TIMEOUT = int(os.environ.get("HTTP_TIMEOUT", "15"))
HTTP_USER_AGENT = os.environ.get(
    "HTTP_USER_AGENT",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)
