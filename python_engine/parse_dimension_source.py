import sys
import os
import re
import json
import time
import csv
from io import StringIO

MONTH_MAP = {
    'januari': 'January', 'februari': 'February', 'maret': 'March',
    'april': 'April', 'mei': 'May', 'juni': 'June',
    'juli': 'July', 'agustus': 'August', 'september': 'September',
    'oktober': 'October', 'november': 'November', 'desember': 'December'
}

def clean_cell_str(val):
    if val is None:
        return ""
    s = str(val).strip()
    return s

def fetch_raw_data(source, sheet_name=None):
    source = source.strip('\r\n\t"')
    
    # 1. Local Excel file (.xlsx / .xls)
    if os.path.exists(source) and (source.endswith('.xlsx') or source.endswith('.xls')):
        import openpyxl
        wb = openpyxl.load_workbook(source, data_only=True)
        sheet_names = wb.sheetnames
        
        target_sheet = sheet_names[0]
        if sheet_name and sheet_name in sheet_names:
            target_sheet = sheet_name
        
        ws = wb[target_sheet]
        raw_rows = []
        for row in ws.iter_rows(values_only=True):
            raw_rows.append([clean_cell_str(c) for c in row])
        return {
            "type": "xlsx",
            "sheets": sheet_names,
            "current_sheet": target_sheet,
            "rows": raw_rows
        }

    # 2. Local CSV file
    if os.path.exists(source) and source.endswith('.csv'):
        raw_rows = []
        with open(source, 'r', encoding='utf-8', errors='replace') as f:
            reader = csv.reader(f)
            for row in reader:
                raw_rows.append([clean_cell_str(c) for c in row])
        return {
            "type": "csv",
            "sheets": ["Sheet1"],
            "current_sheet": "Sheet1",
            "rows": raw_rows
        }

    # 3. Google Spreadsheets URL
    if "docs.google.com/spreadsheets" in source:
        import urllib.request
        import ssl
        from io import BytesIO

        match_key = re.search(r"/d/([a-zA-Z0-9-_]+)", source)
        doc_key = match_key.group(1) if match_key else None
        gid = None
        if "gid=" in source:
            gid = source.split("gid=")[1].split("#")[0].split("&")[0].strip()

        now_ts = int(time.time())
        ssl_ctx = ssl._create_unverified_context()

        # Strategi 1: Coba export format=xlsx untuk mendapatkan SEMUA sheet tabs secara utuh
        if doc_key:
            xlsx_url = f"https://docs.google.com/spreadsheets/d/{doc_key}/export?format=xlsx&_t={now_ts}"
            try:
                req = urllib.request.Request(xlsx_url, headers={'User-Agent': 'Mozilla/5.0'})
                with urllib.request.urlopen(req, timeout=12, context=ssl_ctx) as response:
                    raw_bytes = response.read()
                    if len(raw_bytes) > 200 and not raw_bytes.startswith(b"<!DOCTYPE") and not raw_bytes.startswith(b"<html"):
                        import openpyxl
                        wb = openpyxl.load_workbook(BytesIO(raw_bytes), data_only=True)
                        sheet_names = wb.sheetnames
                        target_sheet = sheet_names[0]
                        if sheet_name and sheet_name in sheet_names:
                            target_sheet = sheet_name
                        ws = wb[target_sheet]
                        raw_rows = []
                        for row in ws.iter_rows(values_only=True):
                            raw_rows.append([clean_cell_str(c) for c in row])
                        return {
                            "type": "google_sheets",
                            "sheets": sheet_names,
                            "current_sheet": target_sheet,
                            "rows": raw_rows
                        }
            except Exception:
                pass

        # Strategi 2: Fallback ke CSV export (mendukung per-gid)
        if doc_key:
            urls = [
                f"https://docs.google.com/spreadsheets/d/{doc_key}/export?format=csv" + (f"&gid={gid}" if gid else "") + f"&_t={now_ts}",
                f"https://docs.google.com/spreadsheets/d/{doc_key}/gviz/tq?tqx=out:csv" + (f"&gid={gid}" if gid else "") + f"&_t={now_ts}"
            ]
        else:
            csv_link = re.sub(r"/edit.*", "/export?format=csv", source)
            if gid: csv_link += f"&gid={gid}"
            urls = [csv_link + f"&_t={now_ts}"]

        csv_data = None
        for url in urls:
            try:
                req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
                with urllib.request.urlopen(req, timeout=10, context=ssl_ctx) as response:
                    raw = response.read().decode('utf-8', errors='replace')
                    if "<html" not in raw.lower() and "google-site-verification" not in raw.lower():
                        csv_data = raw
                        break
            except Exception:
                continue

        if not csv_data:
            return {"error": "Gagal mengunduh data dari tautan Google Spreadsheet. Pastikan tautan disetel dapat diakses publik (Anyone with the link can view)."}

        reader = csv.reader(StringIO(csv_data))
        raw_rows = [[clean_cell_str(c) for c in row] for row in reader]
        sheet_label = f"Sheet (gid={gid})" if gid else "Sheet Aktif"
        return {
            "type": "google_sheets",
            "sheets": [sheet_label],
            "current_sheet": sheet_label,
            "rows": raw_rows
        }

    return {"error": "Sumber tidak dikenali. Mohon masukkan path file Excel (.xlsx), CSV, atau tautan Google Spreadsheet."}

def inspect_source(source, sheet_name=None):
    res = fetch_raw_data(source, sheet_name)
    if "error" in res:
        return res

    rows = res.get("rows", [])
    if not rows:
        return {"error": "Data kosong (tidak ditemukan baris dalam sheet)."}

    max_cols = max(len(r) for r in rows) if rows else 0
    if max_cols == 0:
        return {"error": "Sheet tidak memiliki kolom."}

    # Cari baris header potensial (baris teratas yang berisi teks)
    columns_info = []
    for c_idx in range(max_cols):
        col_letter = chr(65 + c_idx) if c_idx < 26 else f"Col{c_idx+1}"
        
        # Ambil sampel nilai dari kolom ini
        col_vals = [r[c_idx] for r in rows if c_idx < len(r) and r[c_idx].strip() != ""]
        
        header_name = f"Kolom {col_letter}"
        if col_vals:
            # Gunakan nilai pertama yang bukan angka sebagai label nama kolom
            for v in col_vals[:3]:
                if not re.match(r'^\(?[0-9\.\,\-]+\)?$', v):
                    header_name = v
                    break

        preview_vals = [v for v in col_vals if v != header_name and not re.match(r'^\([0-9]+\)$', v)][:6]

        columns_info.append({
            "index": c_idx,
            "letter": col_letter,
            "header": header_name,
            "preview": preview_vals,
            "sample_count": len(col_vals)
        })

    return {
        "status": "success",
        "type": res.get("type"),
        "sheets": res.get("sheets", []),
        "current_sheet": res.get("current_sheet"),
        "columns": columns_info,
        "total_rows": len(rows)
    }

def extract_rows(source, sheet_name=None, ind_col=0, en_col=None, deduplicate=True, clean_headers=True, auto_translate_month=True):
    res = fetch_raw_data(source, sheet_name)
    if "error" in res:
        return res

    rows = res.get("rows", [])
    if not rows:
        return {"error": "Sheet kosong."}

    extracted = []
    seen = set()

    for r_idx, row in enumerate(rows):
        if ind_col >= len(row):
            continue
        
        val_ind = row[ind_col].strip()
        if not val_ind:
            continue

        # Filter baris header angka seperti (1), (2) dan label header tabel
        if clean_headers:
            if re.match(r'^\([0-9]+\)$', val_ind):
                continue
            header_blacklist = [
                'no', 'nomor', 'no.', 'uraian', 'rincian', 'keterangan',
                'bulan', 'month', 'kabupaten/kota', 'kab/kota', 'wilayah',
                'daerah', 'nama', 'indikator', 'variabel', 'item', 'komoditas',
                'stasiun', 'station', 'pos'
            ]
            if val_ind.lower() in header_blacklist and r_idx < 4:
                continue

        # Nilai English
        val_en = ""
        if en_col is not None and en_col >= 0 and en_col < len(row):
            val_en = row[en_col].strip()

        # Deteksi otomatis bulan dwibahasa jika en kosong
        if auto_translate_month and not val_en:
            lower = val_ind.lower()
            if lower in MONTH_MAP:
                val_en = MONTH_MAP[lower]

        # Deteksi hierarki indentasi
        level = 0
        clean_text_ind = val_ind
        if val_ind.startswith('----'):
            level = 2
            clean_text_ind = val_ind[4:].strip()
        elif val_ind.startswith('--'):
            level = 1
            clean_text_ind = val_ind[2:].strip()
        elif val_ind.startswith('    '):
            level = 2
            clean_text_ind = val_ind.strip()
        elif val_ind.startswith('  ') or val_ind.startswith('\t'):
            level = 1
            clean_text_ind = val_ind.strip()

        # Deduplikasi jika diminta
        dedup_key = f"{level}::{clean_text_ind.lower()}"
        if deduplicate:
            if dedup_key in seen:
                continue
            seen.add(dedup_key)

        extracted.append({
            "ind": clean_text_ind,
            "en": val_en if val_en else clean_text_ind,
            "level": level
        })

    return {
        "status": "success",
        "items": extracted,
        "total_extracted": len(extracted)
    }

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Argumen tidak mencukupi"}))
        sys.exit(1)

    mode = sys.argv[1]
    src = sys.argv[2]
    sheet = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] != "None" else None

    if mode == "inspect":
        result = inspect_source(src, sheet)
        print(json.dumps(result, ensure_ascii=False))
    elif mode == "extract":
        ind_col = int(sys.argv[4]) if len(sys.argv) > 4 else 0
        en_col = int(sys.argv[5]) if len(sys.argv) > 5 and sys.argv[5] != "None" and sys.argv[5] != "-1" else None
        dedup = (sys.argv[6].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 6 else True
        clean_headers = (sys.argv[7].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 7 else True
        auto_translate = (sys.argv[8].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 8 else True
        result = extract_rows(src, sheet, ind_col, en_col, deduplicate=dedup, clean_headers=clean_headers, auto_translate_month=auto_translate)
        print(json.dumps(result, ensure_ascii=False))
    else:
        print(json.dumps({"error": f"Mode tidak dikenal: {mode}"}))
