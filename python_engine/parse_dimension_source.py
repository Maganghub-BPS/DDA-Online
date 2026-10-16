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
    'oktober': 'October', 'november': 'November', 'desember': 'December',
    'pebruari': 'February', 'jan': 'Jan', 'feb': 'Feb', 'mar': 'Mar',
    'apr': 'Apr', 'may': 'May', 'jun': 'Jun', 'jul': 'Jul',
    'aug': 'Aug', 'agu': 'Aug', 'sep': 'Sep', 'oct': 'Oct',
    'okt': 'Oct', 'nov': 'Nov', 'dec': 'Dec', 'des': 'Dec'
}

ROW_ID_KEYWORDS = {
    'no', 'nomor', 'no.', 'no urut', 'bulan', 'month', 'kabupaten/kota', 'kab/kota',
    'kabupaten', 'kota', 'wilayah', 'daerah', 'nama', 'uraian', 'rincian',
    'keterangan', 'kecamatan', 'desa', 'kelurahan', 'stasiun', 'station', 'pos',
    'kelompok umur', 'age group', 'kode pt', 'nama pt', 'deskripsi data', 'deskripsi',
    'instansi', 'unit kerja', 'kantor', 'satuan', 'kode', 'provinsi'
}

KNOWN_EN_TERMS = {
    'male': 'Laki-laki', 'female': 'Perempuan', 'total': 'Jumlah',
    'sex': 'Jenis Kelamin', 'received': 'Masuk', 'finished': 'Diselesaikan',
    'regency': 'Kabupaten', 'city': 'Kota', 'subdistrict': 'Kecamatan',
    'district': 'Kecamatan', 'village': 'Desa', 'month': 'Bulan',
    'year': 'Tahun', 'unit': 'Satuan', 'rest': 'Sisa', 'last': 'Akhir',
    'state': 'Negeri', 'private': 'Swasta', 'number': 'Jumlah',
    'pupil': 'Murid', 'student': 'Mahasiswa', 'teacher': 'Guru', 'lecturer': 'Dosen',
    'beginning': 'Awal', 'reported': 'Dilaporkan', 'dismissed': 'Dicabut', 'decided': 'Diputus'
}

def clean_cell_str(val):
    if val is None:
        return ""
    if isinstance(val, float) and val.is_integer():
        return str(int(val))
    s = str(val).strip()
    if re.match(r'^-?\d+\.0$', s):
        return s[:-2]
    return s

def extract_cell_lang(val):
    val = (val or "").strip()
    if not val:
        return "", ""
    val_lower = val.lower()
    if val_lower in ['kabupaten/kota', 'kabupaten / kota', 'kab/kota', 'kab / kota']:
        return "Kabupaten/Kota", "Regency/Municipality"

    # 1. Separator baris baru (newline)
    if "\n" in val:
        parts = [p.strip() for p in val.split("\n") if p.strip()]
        if len(parts) >= 2:
            return parts[0], parts[1]
        return parts[0], parts[0]
    # 2. Separator garis miring (misal: "Angkatan Kerja/ Economically Active" atau "Angkatan Kerja / Economically Active")
    if "/" in val and not re.search(r'\d\s*/\s*\d', val):
        parts = [p.strip() for p in val.split("/") if p.strip()]
        if len(parts) >= 2:
            return parts[0], parts[1]
        return parts[0], parts[0]
    # 3. Cek kamus istilah umum BPS
    val_lower = val.lower()
    if val_lower in KNOWN_EN_TERMS:
        return KNOWN_EN_TERMS[val_lower], val.title()
    for en_k, id_v in KNOWN_EN_TERMS.items():
        if id_v.lower() == val_lower:
            return val, en_k.title()
    return val, val

def read_openpyxl_sheet(ws):
    max_r = ws.max_row or 0
    max_c = ws.max_column or 0
    if max_r == 0 or max_c == 0:
        return []

    grid = [[clean_cell_str(ws.cell(r, c).value) for c in range(1, max_c + 1)] for r in range(1, max_r + 1)]

    # Propagasikan sel merged ke seluruh rentang baris & kolomnya
    try:
        merged_ranges = list(ws.merged_cells.ranges)
        for rng in merged_ranges:
            min_r, min_c = rng.min_row - 1, rng.min_col - 1
            max_r_rng, max_c_rng = min(max_r, rng.max_row), min(max_c, rng.max_col)
            val = grid[min_r][min_c]
            if val != "":
                for r in range(min_r, max_r_rng):
                    for c in range(min_c, max_c_rng):
                        grid[r][c] = val
    except Exception:
        pass

    return grid

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
        raw_rows = read_openpyxl_sheet(ws)
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

        # Strategi 1: Export format=xlsx
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
                        raw_rows = read_openpyxl_sheet(ws)
                        return {
                            "type": "google_sheets",
                            "sheets": sheet_names,
                            "current_sheet": target_sheet,
                            "rows": raw_rows
                        }
            except Exception:
                pass

        # Strategi 2: Fallback ke CSV export
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

def is_stat_val(v):
    v = v.strip()
    if not v:
        return False
    if v in ['-', '–', '—', '...', 'NA', 'N/A', 'nil', 'null']:
        return True
    if re.match(r'^\d+(\s*:\s*\d+(\.\d+)?)?$', v):
        return True
    clean = v.replace(' ', '').replace(',', '').replace('.', '').replace('%', '').replace('(', '').replace(')', '')
    if clean.isdigit() and len(clean) > 0:
        return True
    return False

def is_bracket_col_num(v):
    v = v.strip()
    if not v:
        return False
    # Format penomoran kolom resmi BPS dalam tanda kurung: (1), (2), ( 1 ), (1+2), (1-2), (1)-(2), dll atau -1, -2
    if re.match(r'^(\(\s*[0-9]+(\s*[\+\-\*\/]\s*[0-9]+)*\s*\)|-[0-9]+)$', v):
        return True
    if re.match(r'^\(\s*[0-9]+\s*\)\s*[\+\-\*\/]\s*\(\s*[0-9]+\s*\)$', v):
        return True
    if re.match(r'^\(\s*[a-zA-Z]\s*\)$', v):
        return True
    return False

def is_row_number_str(v):
    v = v.strip()
    if not v:
        return False
    # Angka 0 (jam kerja 0, nilai 0) adalah data baris yang sah, BUKAN nomor urut baris
    if v == "0" or v == "0.0":
        return False
    # Angka dalam tanda kurung seperti (1), (2) adalah penomoran kolom BPS, bukan data baris
    if is_bracket_col_num(v):
        return True
    # Nomor urut baris bertitik murni seperti 1., 2., 3. (tanpa teks kata)
    if re.match(r'^[1-9]\d*\.$', v):
        return True
    return False

ROMAN_REGEX = re.compile(r'^(?:M{0,4}(?:CM|CD|D?C{0,3})(?:XC|XL|L?X{0,3})(?:IX|IV|V?I{1,3}))[\.\)]\s+', re.I)
ARABIC_REGEX = re.compile(r'^\d+[\.\)]\s+')
LOWER_REGEX = re.compile(r'^[a-z][\.\)]\s+')
UPPER_REGEX = re.compile(r'^[A-Z][\.\)]\s+')
DASH_REGEX = re.compile(r'^[-–—•]\s+')

def detect_row_hierarchy(raw_rows):
    """
    Mendeteksi level hierarki (0: Bab, 1: Sub Bab, 2: Sub-Sub) untuk rangkaian baris teks.
    Mendukung notasi manual (--/----/spasi) serta pola penomoran resmi BPS:
    - Romawi (I., II.) -> Bab, Angka (1., 2.) -> Sub Bab, Huruf kecil (a., b.) / dash -> Sub-Sub
    - Huruf besar (A., B.) -> Bab, Angka (1., 2.) -> Sub Bab
    - Angka (1., 2.) -> Bab, Huruf kecil (a., b.) / dash -> Sub Bab
    """
    cleaned_rows = [r.strip() for r in raw_rows]
    has_roman = any(ROMAN_REGEX.match(r) for r in cleaned_rows)
    has_arabic = any(ARABIC_REGEX.match(r) for r in cleaned_rows)
    has_upper = any(UPPER_REGEX.match(r) and not ROMAN_REGEX.match(r) for r in cleaned_rows)
    has_lower = any(LOWER_REGEX.match(r) for r in cleaned_rows)
    has_dash = any(DASH_REGEX.match(r) for r in cleaned_rows)

    results = []
    for r in raw_rows:
        val = r.strip()
        level = 0
        clean_v = val

        if r.startswith('----'):
            level = 2
            clean_v = val[4:].strip()
        elif r.startswith('--'):
            level = 1
            clean_v = val[2:].strip()
        elif r.startswith('    '):
            level = 2
            clean_v = val.strip()
        elif r.startswith('  ') or r.startswith('\t'):
            level = 1
            clean_v = val.strip()
        elif has_roman and has_arabic:
            if ROMAN_REGEX.match(val):
                level = 0
            elif ARABIC_REGEX.match(val):
                level = 1
            elif LOWER_REGEX.match(val) or DASH_REGEX.match(val):
                level = 2
            elif any(val.lower().startswith(p) for p in ['jumlah', 'total', 'rata-rata', 'average']):
                level = 0
            else:
                level = 0
        elif has_upper and has_arabic:
            if UPPER_REGEX.match(val):
                level = 0
            elif ARABIC_REGEX.match(val):
                level = 1
            elif LOWER_REGEX.match(val) or DASH_REGEX.match(val):
                level = 2
            elif any(val.lower().startswith(p) for p in ['jumlah', 'total', 'rata-rata', 'average']):
                level = 0
            else:
                level = 0
        elif has_arabic and (has_lower or has_dash):
            if ARABIC_REGEX.match(val):
                level = 0
            elif LOWER_REGEX.match(val) or DASH_REGEX.match(val):
                level = 1
            elif any(val.lower().startswith(p) for p in ['jumlah', 'total', 'rata-rata', 'average']):
                level = 0
            else:
                level = 0

        results.append((clean_v, level))

    return results

def is_col_num_series(row_vals):
    non_empty = [v.strip() for v in row_vals if v.strip()]
    if len(non_empty) < 2:
        return False
    
    # Jika ada kata alfabet >= 4 huruf (seperti nama kabupaten/instansi), bukan nomor kolom!
    for v in non_empty:
        alpha_part = re.sub(r'[^a-zA-Z]', '', v)
        if len(alpha_part) >= 4 and alpha_part.lower() not in ['kolom', 'column']:
            return False

    # Format (1), (2) atau -1, -2
    if all(re.match(r'^(\([0-9]+\)|-[0-9]+|\([0-9]+\s*[\+\-]\s*[0-9]+\))$', v) for v in non_empty):
        return True
    
    # Format angka biasa 1, 2, 3, 4, 5 atau formula
    matched = []
    for v in non_empty:
        m = re.match(r'^([0-9]+)(\s*=\s*[0-9\+\-\*\/\(\)]+)?$', v)
        if m:
            matched.append(int(m.group(1)))
    if len(matched) >= 2 and len(matched) >= len(non_empty) * 0.7:
        if all(x <= 50 for x in matched) and any(matched[i] <= matched[i+1] for i in range(len(matched)-1)):
            return True
    return False

def is_sequence_col(vals):
    cleaned = [v.strip().rstrip('.') for v in vals if v.strip()]
    if len(cleaned) < 3:
        return False
    digits = []
    for v in cleaned:
        if v.isdigit():
            digits.append(int(v))
        else:
            return False
    # Nomor urut baris tabel (series number) SELALU mulai dari 0 atau 1. Jika mulai dari 10, itu adalah kode kategorikal (KBLI/Kode Industri)
    if len(digits) >= 3 and digits[0] in (0, 1) and all(digits[i+1] == digits[i] + 1 for i in range(min(5, len(digits)-1))):
        return True
    return False

def is_year_val(v):
    return bool(re.match(r'^(19|20)\d{2}(\s*[\-\/]\s*(19|20)?\d{2})?$', v.strip()))

def detect_structure(grid):
    max_r = len(grid)
    if max_r == 0:
        return 0, 0, 0, [], None
    max_c = max(len(r) for r in grid) if grid else 0
    if max_c == 0:
        return 0, 0, 0, [], None

    for r in range(max_r):
        if len(grid[r]) < max_c:
            grid[r].extend([""] * (max_c - len(grid[r])))

    # 1. Deteksi baris nomor kolom resmi BPS jika ada di 25 baris pertama
    col_num_row_idx = None
    for r in range(min(25, max_r)):
        if is_col_num_series([grid[r][c] for c in range(max_c)]):
            col_num_row_idx = r
            break

    # 2. Cari baris data pertama secara statistik
    data_start = None
    start_search = (col_num_row_idx + 1) if col_num_row_idx is not None else 2
    for r in range(start_search, min(35, max_r)):
        stat_cells = [c for c in range(max_c) if is_stat_val(grid[r][c])]
        
        # Jika hampir seluruh sel numerik adalah angka tahun, ini adalah header tahun!
        year_cells = [c for c in stat_cells if is_year_val(grid[r][c])]
        if len(year_cells) >= 2 and len(year_cells) >= len(stat_cells) * 0.7:
            continue

        has_alpha = any(any(ch.isalpha() for ch in grid[r][c]) for c in range(min(5, max_c)))
        if len(stat_cells) >= 2 and (has_alpha or len(stat_cells) >= 3):
            data_start = r
            break

    if data_start is None:
        data_start = (col_num_row_idx + 1) if col_num_row_idx is not None else min(5, max_r)

    header_end = col_num_row_idx if col_num_row_idx is not None else data_start

    # 3. Cari header_start dengan mundur dari header_end - 1 ke atas
    header_start = header_end - 1 if header_end > 0 else 0
    while header_start > 0:
        prev_r = header_start - 1
        non_empty = [c for c in range(max_c) if grid[prev_r][c].strip()]
        if not non_empty:
            break
        
        row_str = " ".join(grid[prev_r][c].strip() for c in non_empty)
        row_str_lower = row_str.lower()

        is_title = (
            any(row_str_lower.startswith(p) for p in [
                'tabel', 'table', 'lampiran', 'appendix', 'data ', 'statistik',
                'realisasi', 'dinas', 'rekapitulasi', 'posisi ', 'time series',
                'tampilkan data', 'cari nama', 'form '
            ]) or
            'lanjutan tabel' in row_str_lower or 'continued table' in row_str_lower or
            (len(non_empty) <= 3 and len(row_str) > 25 and not any(is_stat_val(grid[prev_r][c]) for c in non_empty)) or
            row_str_lower in ['semua', 'dikdas', 'dikmen']
        )

        if is_title:
            break
        header_start = prev_r

    while header_start < header_end and not any(grid[header_start][c].strip() for c in range(max_c)):
        header_start += 1

    # Cek baris banner seksi di awal data_start (maksimal 2 baris setelah header)
    max_banner_skip = min(data_start + 2, max_r)
    while data_start < max_banner_skip:
        non_empty = [grid[data_start][c].strip() for c in range(max_c) if grid[data_start][c].strip()]
        if not non_empty:
            data_start += 1
            continue
        if len(set(non_empty)) <= 1 and not any(is_stat_val(v) for v in non_empty):
            # Jika baris berikutnya juga memiliki sel teks di kolom yang sama (misal daftar kabupaten di template), jangan lewati!
            if data_start + 1 < max_r and any(grid[data_start + 1][c].strip() for c in range(min(3, max_c))):
                break
            data_start += 1
        else:
            break

    h_rows = [r for r in range(header_start, header_end) if any(grid[r][c].strip() for c in range(max_c))]
    if not h_rows:
        h_rows = [header_start]

    return header_start, header_end, data_start, h_rows, col_num_row_idx

def find_best_row_col(grid, data_start, max_r, max_c, seq_cols, h_rows=None):
    """
    Mencari kolom baris utama pertama dari sebelah kiri tabel (sebelum kolom observasi statistik pertama).
    """
    for c in range(min(10, max_c)):
        if c in seq_cols:
            continue
        sample_vals = [grid[r][c].strip() for r in range(data_start, min(data_start + 20, max_r)) if grid[r][c].strip()]
        if not sample_vals:
            continue
        total_vals = len(sample_vals)
        num_start_cnt = sum(1 for v in sample_vals if re.match(r'^\d', v))
        avg_len = sum(len(v) for v in sample_vals) / total_vals
        alpha_cnt = sum(1 for v in sample_vals if any(ch.isalpha() for ch in v) and v.lower() not in ROW_ID_KEYWORDS)
        stat_cnt = sum(1 for v in sample_vals if is_stat_val(v) and not is_year_val(v))

        # Cek apakah header kolom ini mengindikasikan kolom identitas baris / kode
        header_text = ""
        if h_rows:
            header_text = " ".join(grid[r][c].strip().lower() for r in h_rows if r < max_r and grid[r][c].strip())
        is_code_header = any(k in header_text for k in ['kode', 'code', 'kbli', 'klasifikasi', 'golongan', 'komoditas', 'rincian', 'uraian', 'nama', 'item', 'daerah', 'wilayah'])

        # Jika sudah ada seq_cols di sebelah kiri (misal kolom 0 adalah no urut 1, 2, 3), 
        # maka kolom numerik berikutnya (seperti Kode Industri 10, 11, 12 atau kode wilayah) BUKAN no urut, melainkan KODE BARIS UTAMA!
        if seq_cols and c > max(seq_cols) and (is_code_header or not is_sequence_col(sample_vals)):
            next_is_stat = False
            if c + 1 < max_c:
                next_samples = [grid[r][c+1].strip() for r in range(data_start, min(data_start + 20, max_r)) if grid[r][c+1].strip()]
                next_stat_cnt = sum(1 for v in next_samples if is_stat_val(v) and not is_year_val(v))
                if len(next_samples) > 0 and next_stat_cnt >= len(next_samples) * 0.6:
                    next_is_stat = True
            if next_is_stat or is_code_header:
                return c

        # Abaikan kolom nomor urut murni (1, 2, 3 atau 01., 02.) HANYA jika bukan kolom kode dan murni berurutan dari 1
        if num_start_cnt >= total_vals * 0.7 and avg_len < 5 and not is_code_header:
            cleaned_d = [int(v.split('.')[0]) for v in sample_vals if v.replace('.','').isdigit()]
            if len(cleaned_d) >= 3 and cleaned_d[0] in (0, 1):
                continue

        # Jika sudah bertemu kolom angka data observasi statistik, hentikan pencarian kolom baris pertama dari kiri!
        if stat_cnt >= total_vals * 0.6 and alpha_cnt <= 1 and not is_code_header:
            break

        unique_texts = set(v.lower() for v in sample_vals if any(ch.isalpha() for ch in v) and v.lower() not in ROW_ID_KEYWORDS)
        if len(unique_texts) >= 2 or alpha_cnt >= 2 or is_code_header:
            return c

    # Fallback pencarian: jika ada seq_cols, kolom tepat setelah seq_cols adalah kandidat terbaik
    if seq_cols:
        candidate = max(seq_cols) + 1
        if candidate < max_c:
            return candidate

    # Fallback pencarian dengan skor jika belum ketemu (hindari memilih kolom observasi statistik panjang)
    best_col = None
    best_score = -1
    for c in range(min(6, max_c)):
        if c in seq_cols:
            continue
        sample_vals = [grid[r][c].strip() for r in range(data_start, min(data_start + 20, max_r)) if grid[r][c].strip()]
        if not sample_vals:
            continue
        total_vals = len(sample_vals)
        alpha_cnt = sum(1 for v in sample_vals if any(ch.isalpha() for ch in v) and v.lower() not in ROW_ID_KEYWORDS)
        unique_texts = set(v.lower() for v in sample_vals if any(ch.isalpha() for ch in v) and v.lower() not in ROW_ID_KEYWORDS)
        stat_cnt = sum(1 for v in sample_vals if is_stat_val(v) and not is_year_val(v))
        avg_len = sum(len(v) for v in sample_vals) / total_vals

        # JANGAN PERNAH memilih kolom yang jelas-jelas data observasi statistik sebagai kolom baris fallback!
        if stat_cnt >= total_vals * 0.8 and alpha_cnt == 0 and avg_len >= 5:
            continue

        score = len(unique_texts) * 50 + alpha_cnt * 10
        if score > best_score:
            best_score = score
            best_col = c

    if best_col is not None:
        return best_col

    return 0

def is_summary_col_header(headers):
    for h in headers:
        if not h: continue
        clean = re.sub(r'[\(\[].*?[\)\]]', '', h).strip().lower()
        clean = clean.split('\n')[0].strip()
        if clean in ['jumlah', 'total', 'jumlah / total', 'total / jumlah', 'sum', 'rata-rata', 'average', 'mean']:
            return True
    return False

def find_continuation_row_cols(grid, primary_col, h_rows, col_num_row_idx, data_start, max_r, max_c):
    if primary_col >= max_c:
        return [primary_col]

    primary_headers = [grid[r][primary_col].strip().lower() for r in h_rows if r < max_r and grid[r][primary_col].strip()]
    primary_hdr_text = ' '.join(primary_headers)
    primary_col_num = grid[col_num_row_idx][primary_col].strip() if (col_num_row_idx is not None and col_num_row_idx < max_r and primary_col < len(grid[col_num_row_idx])) else ''

    primary_samples = [grid[r][primary_col].strip() for r in range(data_start, min(data_start + 20, max_r)) if grid[r][primary_col].strip()]
    primary_total = len(primary_samples)
    primary_num_cnt = sum(1 for v in primary_samples if v.isdigit() or is_row_number_str(v))
    primary_is_numeric = (primary_num_cnt / primary_total >= 0.7) if primary_total > 0 else False

    cols = [primary_col]

    for c in range(primary_col + 1, max_c):
        c_headers = [grid[r][c].strip().lower() for r in h_rows if r < max_r and grid[r][c].strip()]
        c_hdr_text = ' '.join(c_headers)
        c_col_num = grid[col_num_row_idx][c].strip() if (col_num_row_idx is not None and col_num_row_idx < max_r and c < len(grid[col_num_row_idx])) else ''

        is_same_header = bool(primary_hdr_text and c_hdr_text and (primary_hdr_text == c_hdr_text or primary_hdr_text in c_hdr_text or c_hdr_text in primary_hdr_text))
        is_same_col_num = bool(primary_col_num and is_bracket_col_num(primary_col_num) and c_col_num == primary_col_num)

        if is_same_header or (is_same_col_num and primary_col_num):
            c_samples = [grid[r][c].strip() for r in range(data_start, min(data_start + 20, max_r)) if grid[r][c].strip()]
            c_total = len(c_samples)
            if c_total > 0:
                c_num_cnt = sum(1 for v in c_samples if v.isdigit() or is_row_number_str(v))
                if not primary_is_numeric and (c_num_cnt / c_total >= 0.5):
                    continue
            cols.append(c)

    return cols

def inspect_source(source, sheet_name=None, row_col_override=None):
    res = fetch_raw_data(source, sheet_name)
    if "error" in res:
        return res

    grid = res.get("rows", [])
    if not grid:
        return {"error": "Data kosong (tidak ditemukan baris dalam sheet)."}

    max_r = len(grid)
    max_c = max(len(r) for r in grid) if grid else 0
    if max_c == 0:
        return {"error": "Sheet tidak memiliki kolom."}

    # Normalisasi panjang setiap baris agar seragam
    for r in range(max_r):
        if len(grid[r]) < max_c:
            grid[r].extend([""] * (max_c - len(grid[r])))

    header_start, header_end, data_start, h_rows, col_num_row_idx = detect_structure(grid)

    # Deteksi kolom nomor urut baris (1, 2, 3...)
    seq_cols = set()
    for c in range(min(4, max_c)):
        sample_vals = [grid[r][c] for r in range(data_start, min(data_start + 10, max_r))]
        if is_sequence_col(sample_vals):
            seq_cols.add(c)
            break

    # Cari kolom teks nama baris utama (atau gunakan override dari input user jika diberikan)
    if row_col_override is not None and str(row_col_override).strip() not in ("", "None", "-1"):
        best_row_col_idx = int(row_col_override)
    else:
        best_row_col_idx = find_best_row_col(grid, data_start, max_r, max_c, seq_cols, h_rows)

    # Cari kolom data statistik pertama (kolom numerik observasi setelah best_row_col_idx)
    first_stat_col = None
    for c in range(best_row_col_idx + 1, max_c):
        stat_cnt = sum(1 for r in range(data_start, min(data_start + 15, max_r)) if is_stat_val(grid[r][c]) and not is_year_val(grid[r][c]))
        alpha_cnt = sum(1 for r in range(data_start, min(data_start + 15, max_r)) if any(ch.isalpha() for ch in grid[r][c]))
        if stat_cnt >= 2 and alpha_cnt <= 1:
            first_stat_col = c
            break

    if first_stat_col is None:
        first_stat_col = best_row_col_idx + 1

    # Kolom identitas baris: best_row_col_idx dan seq_cols, serta kolom teks pendamping sebelum best_row_col
    row_identifier_cols = {best_row_col_idx}
    for c in seq_cols:
        if c < best_row_col_idx:
            row_identifier_cols.add(c)
    for c in range(best_row_col_idx):
        stat_cnt = sum(1 for r in range(data_start, min(data_start + 15, max_r)) if is_stat_val(grid[r][c]) and not is_year_val(grid[r][c]))
        if stat_cnt < 2:
            row_identifier_cols.add(c)

    # Jika kolom di sebelah kanan best_row_col memiliki header sama persis dengan best_row_col (merged cell di baris header dan bukan kolom angka)
    for c in range(best_row_col_idx + 1, max_c):
        same_h = any(grid[r][c].strip() == grid[r][best_row_col_idx].strip() and grid[r][best_row_col_idx].strip() != "" for r in h_rows)
        stat_cnt = sum(1 for r in range(data_start, min(data_start + 15, max_r)) if is_stat_val(grid[r][c]) and not is_year_val(grid[r][c]))
        alpha_cnt = sum(1 for r in range(data_start, min(data_start + 15, max_r)) if any(ch.isalpha() for ch in grid[r][c]))
        if same_h and stat_cnt < 2 and alpha_cnt >= 2:
            row_identifier_cols.add(c)
        else:
            break

    # Deteksi tabel bersambung samping (side-by-side continuation)
    continuation_cols = find_continuation_row_cols(grid, best_row_col_idx, h_rows, col_num_row_idx, data_start, max_r, max_c)
    for c in continuation_cols:
        row_identifier_cols.add(c)
        if c > 0 and (c - 1) not in row_identifier_cols:
            prev_samples = [grid[r][c-1].strip() for r in range(data_start, min(data_start + 6, max_r))]
            prev_non_empty = [v for v in prev_samples if v]
            prev_headers = [grid[r][c-1].strip() for r in h_rows if grid[r][c-1].strip()]
            if len(prev_non_empty) >= 2 and not is_summary_col_header(prev_headers):
                if all(is_row_number_str(v) or is_bracket_col_num(v) or (v.isdigit() and int(v) > 0) for v in prev_non_empty):
                    row_identifier_cols.add(c - 1)

    # Pastikan kolom ringkasan/total data (Jumlah/Total) TIDAK PERNAH masuk sebagai row_identifier_cols
    for c in range(max_c):
        col_headers = [grid[r][c].strip() for r in h_rows if grid[r][c].strip()]
        if is_summary_col_header(col_headers):
            row_identifier_cols.discard(c)

    # 2.5 Deteksi Kolom Bayangan (Shadow Columns) akibat merged cell horizontal di Excel
    shadow_cols = set()
    for c in range(1, max_c):
        is_dup_header = all(grid[r][c].strip() == grid[r][c-1].strip() for r in h_rows)
        is_dup_data = all(grid[r][c].strip() == grid[r][c-1].strip() for r in range(data_start, max_r))
        has_content = any(grid[r][c].strip() for r in range(data_start, max_r))
        if is_dup_header and is_dup_data and has_content:
            shadow_cols.add(c)

    row_identifier_cols.difference_update(shadow_cols)

    # 3. Deteksi Baris Periode Waktu di Header
    period_row_indices = set()
    year_row_idx = None
    month_row_idx = None

    data_cols = [c for c in range(max_c) if c not in row_identifier_cols and c not in shadow_cols]
    for r in h_rows:
        row_vals = [grid[r][c].strip() for c in data_cols if grid[r][c].strip()]
        if not row_vals: continue
        total = len(row_vals)

        # Cek Tahun
        y_count = sum(1 for v in row_vals if re.match(r'^(19|20)\d{2}(\s*[\-\/]\s*(19|20)?\d{2})?$', v))
        if y_count >= 2 and (y_count >= total * 0.35 or y_count >= 2):
            period_row_indices.add(r)
            year_row_idx = r

        # Cek Bulan
        m_count = sum(1 for v in row_vals if v.split('\n')[0].strip().lower() in MONTH_MAP or v.split('/')[0].strip().lower() in MONTH_MAP)
        if m_count >= 2 and (m_count >= total * 0.35 or m_count >= 2):
            period_row_indices.add(r)
            month_row_idx = r

    # 4. Baris Header Khusus Karakteristik (Tanpa baris periode waktu dan tanpa baris nomor kolom BPS)
    col_h_rows = [r for r in h_rows if r not in period_row_indices and r != col_num_row_idx and any(grid[r][c].strip() for c in data_cols)]
    if not col_h_rows and h_rows:
        col_h_rows = [r for r in h_rows if r != col_num_row_idx]

    # 5. Susun Columns Info & Hierarki Cerdas (Mendukung hingga 3 Tingkatan Kolom Asimetris)
    # Deteksi apakah ada baris translasi bahasa murni (misal Row r = Indo, Row r+1 = English)
    en_translation_rows = set()
    if len(col_h_rows) >= 2:
        for i in range(len(col_h_rows) - 1):
            r_top = col_h_rows[i]
            r_bot = col_h_rows[i+1]
            match_en_count = 0
            valid_col_count = 0
            has_span = False
            for c in data_cols:
                v_top = grid[r_top][c].strip()
                v_bot = grid[r_bot][c].strip()
                if not v_top or not v_bot:
                    continue
                valid_col_count += 1
                if c + 1 < max_c and grid[r_top][c+1].strip() == v_top and grid[r_bot][c+1].strip() != v_bot:
                    has_span = True
                if v_bot.lower() in KNOWN_EN_TERMS or (v_top.lower() in KNOWN_EN_TERMS.values()):
                    match_en_count += 1
            if not has_span and valid_col_count > 0 and match_en_count >= valid_col_count * 0.5:
                en_translation_rows.add(r_bot)

    columns_info = []
    distinct_parents = []
    seen_parents = set()

    for c in range(max_c):
        if c in shadow_cols:
            continue
        col_letter = chr(65 + c) if c < 26 else f"Col{c+1}"
        is_row_id = (c in row_identifier_cols)

        # Cek apakah kolom ini kosong total
        is_empty_col = not any(grid[r][c].strip() for r in range(header_start, max_r))
        if is_empty_col:
            continue

        if is_row_id:
            candidate_vals = []
            for r in h_rows:
                if r == col_num_row_idx or r in period_row_indices:
                    continue
                v = grid[r][c].strip()
                if not v:
                    continue
                is_spanned_to_data = any(grid[r][d_col].strip() == v for d_col in range(max_c) if d_col not in row_identifier_cols)
                candidate_vals.append((v, is_spanned_to_data))
            
            specific_vals = [v for v, spanned in candidate_vals if not spanned]
            top_val = specific_vals[-1] if specific_vals else (candidate_vals[-1][0] if candidate_vals else f"Baris {col_letter}")
            ind_header, en_header = extract_cell_lang(top_val)
            sub_header = ""
            sub_header_en = ""
            sub_sub_header = ""
            sub_sub_header_en = ""
            levels = [{"ind": ind_header, "en": en_header}]
            tree_notation_ind = ind_header
            tree_notation_en = en_header
            full_title = ind_header
        else:
            levels = []
            prev_clean = None
            i = 0
            while i < len(col_h_rows):
                r = col_h_rows[i]
                val = grid[r][c].strip()
                if not val or is_bracket_col_num(val) or r == col_num_row_idx:
                    i += 1
                    continue

                ind_part, en_part = extract_cell_lang(val)

                # Jika baris berikutnya adalah baris terjemahan bahasa inggris murni
                if i + 1 < len(col_h_rows) and col_h_rows[i+1] in en_translation_rows:
                    en_val = grid[col_h_rows[i+1]][c].strip()
                    if en_val:
                        en_part = en_val
                    i += 1  # Lewati baris terjemahan

                clean_ind = ind_part.lower()
                # Hindari duplikasi berulang secara vertikal akibat merged cell / rowspan
                if clean_ind != prev_clean:
                    prev_clean = clean_ind
                    levels.append({
                        "ind": ind_part,
                        "en": en_part if en_part else ind_part
                    })
                i += 1

            if not levels:
                levels = [{"ind": f"Kolom {col_letter}", "en": f"Column {col_letter}"}]

            ind_header = levels[0]["ind"]
            en_header  = levels[0]["en"]
            sub_header = levels[1]["ind"] if len(levels) >= 2 else ""
            sub_header_en = levels[1]["en"] if len(levels) >= 2 else ""
            sub_sub_header = levels[2]["ind"] if len(levels) >= 3 else ""
            sub_sub_header_en = levels[2]["en"] if len(levels) >= 3 else ""

            tree_notation_ind = " || ".join([l["ind"] for l in levels])
            tree_notation_en  = " || ".join([l["en"] for l in levels])
            full_title = " > ".join([l["ind"] for l in levels])

        col_vals = [grid[r][c].strip() for r in range(data_start, max_r) if grid[r][c].strip()]
        preview_vals = [v for v in col_vals if v != ind_header and not is_col_num_series([v])][:6]

        columns_info.append({
            "index": c,
            "letter": col_letter,
            "header": ind_header,
            "header_en": en_header,
            "sub_header": sub_header,
            "sub_header_en": sub_header_en,
            "sub_sub_header": sub_sub_header,
            "sub_sub_header_en": sub_sub_header_en,
            "levels": levels,
            "tree_notation_ind": tree_notation_ind,
            "tree_notation_en": tree_notation_en,
            "full_title": full_title,
            "preview": preview_vals,
            "sample_count": len(col_vals),
            "is_row_identifier": is_row_id
        })

        top_baris_vals = [grid[r][best_row_col_idx].strip() for r in h_rows if grid[r][best_row_col_idx].strip()]
        baris_main_text = top_baris_vals[0].split('\n')[0].split(' / ')[0].strip().lower() if top_baris_vals else ""

        if (not is_row_id and 
            ind_header not in seen_parents and 
            not re.match(r'^Kolom\s+[A-Z0-9]+$', ind_header) and
            ind_header.lower() not in ROW_ID_KEYWORDS and
            ind_header.lower() != baris_main_text):
            seen_parents.add(ind_header)
            distinct_parents.append({
                "ind": ind_header,
                "en": en_header
            })

    # Susun matriks header grid untuk live preview
    active_indices = [c['index'] for c in columns_info]
    header_grid = []
    for r in h_rows:
        header_grid.append([grid[r][c_idx] for c_idx in active_indices])

    sample_rows = []
    sample_limit = min(data_start + 5, max_r)
    for r in range(data_start, sample_limit):
        row_cells = [grid[r][c_idx] for c_idx in active_indices]
        if any(v != "" for v in row_cells):
            sample_rows.append(row_cells)

    # 6. Susun auto_periode
    auto_periode = []
    seen_per = set()
    for c in data_cols:
        y_val = grid[year_row_idx][c].strip() if year_row_idx is not None else ""
        m_val = grid[month_row_idx][c].split('\n')[0].strip() if month_row_idx is not None else ""

        per_str = ""
        per_en = ""
        if m_val and y_val:
            m_en = MONTH_MAP.get(m_val.lower(), m_val)
            per_str = f"{m_val} {y_val}"
            per_en = f"{m_en} {y_val}"
        elif y_val:
            per_str = y_val
            per_en = y_val
        elif m_val:
            m_en = MONTH_MAP.get(m_val.lower(), m_val)
            per_str = m_val
            per_en = m_en

        if per_str and per_str not in seen_per:
            seen_per.add(per_str)
            auto_periode.append({"ind": per_str, "en": per_en if per_en else per_str})

    # Fallback auto_periode dari teks judul
    if not auto_periode:
        title_texts = []
        for r in range(min(header_start, max_r)):
            title_texts.append(" ".join(grid[r][c] for c in range(min(10, max_c)) if grid[r][c]))
        sheet_clean_for_year = res.get("current_sheet") or ""
        full_title_str = " ".join(title_texts) + " " + sheet_clean_for_year
        years_found = re.findall(r'\b(20\d{2}(?:\s*[\-\/]\s*20\d{2})?)\b', full_title_str)
        if years_found:
            for y in sorted(list(set(years_found))):
                auto_periode.append({"ind": y, "en": y})

    # 7. Susun auto_rows (Mendukung tabel lanjutan bersambung samping / multi-panel)
    continuation_row_cols = find_continuation_row_cols(grid, best_row_col_idx, h_rows, col_num_row_idx, data_start, max_r, max_c)
    raw_row_candidates = []
    for c_idx in continuation_row_cols:
        if c_idx in shadow_cols:
            continue
        for r in range(data_start, max_r):
            if c_idx >= len(grid[r]):
                continue
            val_ind = grid[r][c_idx].strip()
            if not val_ind or is_bracket_col_num(val_ind) or is_col_num_series([val_ind]) or is_row_number_str(val_ind):
                continue
            lower_v = val_ind.lower()
            if lower_v in ROW_ID_KEYWORDS:
                continue
            # Berhenti jika bertemu baris footer
            if any(lower_v.startswith(p) for p in ['sumber', 'source', 'catatan', 'note', 'keterangan']):
                break
            raw_row_candidates.append(grid[r][c_idx])

    hierarchical_rows = detect_row_hierarchy(raw_row_candidates)

    auto_rows = []
    seen_rows = set()
    for clean_v, level in hierarchical_rows:
        ind_val, en_val = extract_cell_lang(clean_v)
        lower_v = ind_val.lower()
        if lower_v in MONTH_MAP:
            en_val = MONTH_MAP[lower_v]

        row_key = f"{level}::{ind_val.lower()}"
        if row_key in seen_rows:
            continue
        seen_rows.add(row_key)

        auto_rows.append({"ind": ind_val, "en": en_val if en_val else ind_val, "level": level})

    # 8. Usulan Nama Cerdas
    best_col_meta = next((c for c in columns_info if c['index'] == best_row_col_idx), None)
    sugg_baris_ind = best_col_meta['header'] if best_col_meta else 'Judul Baris'
    sugg_baris_en  = best_col_meta['header_en'] if best_col_meta else sugg_baris_ind

    if sugg_baris_ind.lower() in ['kabupaten', 'kota', 'kab/kota']:
        sugg_baris_ind = 'Kabupaten/Kota'
        sugg_baris_en  = 'Regency/Municipality'
    elif any(k in sugg_baris_ind.lower() for k in ['tampilkan data', 'kolom', 'baris', 'lanjutan', 'no.', 'no']):
        if auto_rows:
            first_row_lower = auto_rows[0]['ind'].lower()
            if any(k in first_row_lower for k in ['cilacap', 'banyumas', 'semarang', 'kab.', 'kota']):
                sugg_baris_ind = 'Kabupaten/Kota'
                sugg_baris_en  = 'Regency/Municipality'
            elif any(k in first_row_lower for k in ['januari', 'februari', 'pebruari']):
                sugg_baris_ind = 'Bulan'
                sugg_baris_en  = 'Month'
            elif any(k in first_row_lower for k in ['imigrasi']):
                sugg_baris_ind = 'Kantor Imigrasi'
                sugg_baris_en  = 'Immigration Office'

    title_full = " ".join(" ".join(grid[r][:10]) for r in range(min(header_start, max_r)))
    sugg_kolom_ind = ""
    sugg_kolom_en = ""

    m_menurut = re.search(r'menurut\s+([a-zA-Z\s]+?)(?:\s+dan\s+([a-zA-Z\s]+?))?(?:\s+di|\s+pada|\s+tahun|\s*,\s*20|$)', title_full, re.I)
    m_berdasarkan = re.search(r'berdasarkan\s+([a-zA-Z\s]+?)(?:\s+di|\s+pada|\s+periode|\s+tahun|\s*,\s*20|$)', title_full, re.I)
    m_per = re.search(r'per\s+([a-zA-Z\s]+?)(?:\s+di|\s+pada|\s+provinsi|\s*,\s*20|$)', title_full, re.I)

    if m_menurut:
        k1 = m_menurut.group(1).strip().title()
        k2 = m_menurut.group(2).strip().title() if m_menurut.group(2) else ""
        if any(w in k1.lower() for w in ['kabupaten', 'kecamatan', 'wilayah', 'daerah', 'bulan']) and k2:
            sugg_kolom_ind = k2
        else:
            sugg_kolom_ind = k1
    elif m_berdasarkan:
        sugg_kolom_ind = m_berdasarkan.group(1).strip().title()
    elif m_per:
        sugg_kolom_ind = m_per.group(1).strip().title()

    sheet_clean = (res.get("current_sheet") or "").strip()
    if (not sugg_kolom_ind or sugg_kolom_ind.lower() == sugg_baris_ind.lower()) and distinct_parents:
        if len(distinct_parents) == 1 and distinct_parents[0]['ind'] not in ['Kolom', 'Data']:
            sugg_kolom_ind = distinct_parents[0]['ind']
            sugg_kolom_en  = distinct_parents[0]['en']
        elif sheet_clean and sheet_clean.lower() != 'sheet1' and not re.match(r'^\d+(\.\d+)*(\s*\(\d+\))?$', sheet_clean) and not sheet_clean.lower().startswith('tabel'):
            sugg_kolom_ind = sheet_clean
            sugg_kolom_en = sheet_clean
        elif distinct_parents:
            sugg_kolom_ind = distinct_parents[0]['ind']
            sugg_kolom_en  = distinct_parents[0].get('en', sugg_kolom_ind)

    if not sugg_kolom_en:
        sugg_kolom_en = sugg_kolom_ind

    # Usulan nama kelompok periode
    sugg_periode_ind = ""
    sugg_periode_en = ""
    if auto_periode:
        if len(auto_periode) == 1:
            sugg_periode_ind = f"Tahun {auto_periode[0]['ind']}" if auto_periode[0]['ind'].isdigit() else auto_periode[0]['ind']
            sugg_periode_en = f"Year {auto_periode[0]['en']}" if auto_periode[0]['en'].isdigit() else auto_periode[0]['en']
        elif len(auto_periode) > 1:
            all_years = all(re.match(r'^(19|20)\d{2}(\s*[\-\/]\s*20\d{2})?$', p['ind']) for p in auto_periode)
            if all_years:
                sugg_periode_ind = f"Tahun {auto_periode[0]['ind']}-{auto_periode[-1]['ind']}"
                sugg_periode_en = f"Year {auto_periode[0]['en']}-{auto_periode[-1]['en']}"
            else:
                sugg_periode_ind = f"Periode {auto_periode[0]['ind']} - {auto_periode[-1]['ind']}"
                sugg_periode_en = f"Period {auto_periode[0]['en']} - {auto_periode[-1]['en']}"

    return {
        "status": "success",
        "type": res.get("type"),
        "sheets": res.get("sheets", []),
        "current_sheet": res.get("current_sheet"),
        "columns": columns_info,
        "distinct_parents": distinct_parents,
        "header_grid": header_grid,
        "sample_rows": sample_rows,
        "header_row_count": len(h_rows),
        "total_rows": max_r,
        "auto_rows": auto_rows,
        "auto_periode": auto_periode,
        "best_row_col_index": best_row_col_idx,
        "suggested_baris_ind": sugg_baris_ind,
        "suggested_baris_en": sugg_baris_en,
        "suggested_kolom_ind": sugg_kolom_ind,
        "suggested_kolom_en": sugg_kolom_en,
        "suggested_periode_ind": sugg_periode_ind,
        "suggested_periode_en": sugg_periode_en,
        "has_periode": len(auto_periode) > 0
    }

def extract_rows(source, sheet_name=None, ind_col=0, en_col=None, deduplicate=True, clean_headers=True, auto_translate_month=True, auto_hierarchy=True):
    res = fetch_raw_data(source, sheet_name)
    if "error" in res:
        return res

    grid = res.get("rows", [])
    if not grid:
        return {"error": "Sheet kosong."}

    max_r = len(grid)
    max_c = max(len(r) for r in grid) if grid else 0
    header_start, header_end, data_start, h_rows, col_num_row_idx = detect_structure(grid)

    start_idx = data_start if clean_headers else 0

    continuation_row_cols = find_continuation_row_cols(grid, ind_col, h_rows, col_num_row_idx, data_start, max_r, max_c)
    en_offset = (en_col - ind_col) if (en_col is not None and en_col >= 0) else None

    candidate_records = []
    for c_idx in continuation_row_cols:
        target_en_col = (c_idx + en_offset) if (en_offset is not None and c_idx + en_offset < max_c) else None
        for r_idx in range(start_idx, max_r):
            row = grid[r_idx]
            if c_idx >= len(row):
                continue

            raw_val_ind = row[c_idx].strip()
            if not raw_val_ind or is_bracket_col_num(raw_val_ind):
                continue

            if clean_headers:
                if is_col_num_series([raw_val_ind]) or is_row_number_str(raw_val_ind):
                    continue
                lower_ind = raw_val_ind.lower()
                if lower_ind in ROW_ID_KEYWORDS:
                    continue
                if any(lower_ind.startswith(p) for p in ['sumber', 'source', 'catatan', 'note', 'keterangan']):
                    break

            val_en_custom = ""
            if target_en_col is not None and target_en_col < len(row):
                val_en_custom = row[target_en_col].strip()

            candidate_records.append({
                "raw_str": row[c_idx],
                "val_ind": raw_val_ind,
                "val_en_custom": val_en_custom
            })

    if not candidate_records:
        return {
            "status": "success",
            "items": [],
            "total_extracted": 0
        }

    if auto_hierarchy:
        hierarchy_results = detect_row_hierarchy([c["raw_str"] for c in candidate_records])
    else:
        hierarchy_results = []
        for c in candidate_records:
            val_ind = c["val_ind"]
            r_str = c["raw_str"]
            level = 0
            clean_text_ind = val_ind
            if r_str.startswith('----'):
                level = 2
                clean_text_ind = val_ind[4:].strip()
            elif r_str.startswith('--'):
                level = 1
                clean_text_ind = val_ind[2:].strip()
            elif r_str.startswith('    '):
                level = 2
                clean_text_ind = val_ind.strip()
            elif r_str.startswith('  ') or r_str.startswith('\t'):
                level = 1
                clean_text_ind = val_ind.strip()
            hierarchy_results.append((clean_text_ind, level))

    extracted = []
    seen = set()

    for idx, cand in enumerate(candidate_records):
        clean_text_ind, level = hierarchy_results[idx]
        val_en_custom = cand["val_en_custom"]

        final_ind = clean_text_ind
        final_en = val_en_custom

        # Jika kolom English tidak ditentukan secara terpisah, ekstrak dwibahasa dari string atau terjemahkan bulan
        if not final_en:
            split_ind, split_en = extract_cell_lang(clean_text_ind)
            if split_ind and split_en and split_ind != split_en:
                final_ind = split_ind
                final_en = split_en
            else:
                final_en = clean_text_ind

        if auto_translate_month and (not final_en or final_en == final_ind):
            lower_ind = final_ind.lower()
            if lower_ind in MONTH_MAP:
                final_en = MONTH_MAP[lower_ind]

        dedup_key = f"{level}::{final_ind.lower()}"
        if deduplicate:
            if dedup_key in seen:
                continue
            seen.add(dedup_key)

        extracted.append({
            "ind": final_ind,
            "en": final_en if final_en else final_ind,
            "level": level
        })

    return {
        "status": "success",
        "items": extracted,
        "total_extracted": len(extracted)
    }

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Argumen tidak mencukupi"}, ensure_ascii=True))
        sys.exit(1)

    mode = sys.argv[1]
    src = sys.argv[2]
    sheet = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] not in ("None", "") else None

    if mode == "inspect":
        row_col_override = int(sys.argv[4]) if len(sys.argv) > 4 and sys.argv[4] not in ("None", "", "-1") else None
        result = inspect_source(src, sheet, row_col_override)
        print(json.dumps(result, ensure_ascii=True))
    elif mode == "extract":
        ind_col = int(sys.argv[4]) if len(sys.argv) > 4 else 0
        en_col = int(sys.argv[5]) if len(sys.argv) > 5 and sys.argv[5] != "None" and sys.argv[5] != "-1" else None
        dedup = (sys.argv[6].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 6 else True
        clean_headers = (sys.argv[7].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 7 else True
        auto_translate = (sys.argv[8].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 8 else True
        auto_hierarchy = (sys.argv[9].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 9 else True
        result = extract_rows(src, sheet, ind_col, en_col, deduplicate=dedup, clean_headers=clean_headers, auto_translate_month=auto_translate, auto_hierarchy=auto_hierarchy)
        print(json.dumps(result, ensure_ascii=True))
    else:
        print(json.dumps({"error": f"Mode tidak dikenal: {mode}"}, ensure_ascii=True))

