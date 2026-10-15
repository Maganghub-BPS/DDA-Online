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
    s = str(val).strip()
    return s

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

def is_row_number_str(v):
    v = v.strip()
    if not v: return False
    if re.match(r'^0*\d+\.?$', v):
        return True
    if re.match(r'^\(?[0-9]+\)?$', v):
        return True
    return False

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
    if len(digits) >= 3 and all(digits[i+1] == digits[i] + 1 for i in range(min(5, len(digits)-1))):
        return True
    return False

def is_year_val(v):
    return bool(re.match(r'^(19|20)\d{2}(\s*[\-\/]\s*(19|20)?\d{2})?$', v.strip()))

def detect_structure(grid):
    max_r = len(grid)
    if max_r == 0:
        return 0, 0, 0, [], None
    max_c = max(len(r) for r in grid) if grid else 0

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

    # Cek baris banner seksi di awal data_start
    while data_start < max_r:
        non_empty = [grid[data_start][c].strip() for c in range(max_c) if grid[data_start][c].strip()]
        if not non_empty:
            data_start += 1
            continue
        if len(set(non_empty)) <= 1 and not any(is_stat_val(v) for v in non_empty):
            data_start += 1
        else:
            break

    h_rows = [r for r in range(header_start, header_end) if any(grid[r][c].strip() for c in range(max_c))]
    if not h_rows:
        h_rows = [header_start]

    return header_start, header_end, data_start, h_rows, col_num_row_idx

def find_best_row_col(grid, data_start, max_r, max_c, seq_cols):
    best_col = None
    best_score = -1
    
    for c in range(min(6, max_c)):
        if c in seq_cols:
            continue
        sample_vals = [grid[r][c].strip() for r in range(data_start, min(data_start + 20, max_r)) if grid[r][c].strip()]
        if not sample_vals:
            continue
        total_vals = len(sample_vals)
        num_start_cnt = sum(1 for v in sample_vals if re.match(r'^\d', v))
        avg_len = sum(len(v) for v in sample_vals) / total_vals
        alpha_cnt = sum(1 for v in sample_vals if any(ch.isalpha() for ch in v) and v.lower() not in ROW_ID_KEYWORDS)
        unique_texts = set(v.lower() for v in sample_vals if any(ch.isalpha() for ch in v) and v.lower() not in ROW_ID_KEYWORDS)
        
        # Abaikan kolom nomor urut murni (1, 2, 3 atau 01., 02.)
        if num_start_cnt >= total_vals * 0.7 and avg_len < 5:
            continue
            
        score = len(unique_texts) * 50 + alpha_cnt * 10 + avg_len
        if score > best_score:
            best_score = score
            best_col = c
            
    return best_col if best_col is not None else (1 if 1 not in seq_cols and max_c > 1 else 0)

def inspect_source(source, sheet_name=None):
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

    # Cari kolom teks nama baris utama
    best_row_col_idx = find_best_row_col(grid, data_start, max_r, max_c, seq_cols)

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

    # Kolom sebelum dan termasuk best_row_col_idx serta seq_cols adalah identitas baris
    row_identifier_cols = set(range(best_row_col_idx + 1))
    for c in seq_cols:
        row_identifier_cols.add(c)

    # Jika kolom di sebelah kanan best_row_col memiliki header sama persis dengan best_row_col (merged cell di baris header)
    for c in range(best_row_col_idx + 1, max_c):
        same_h = any(grid[r][c].strip() == grid[r][best_row_col_idx].strip() and grid[r][best_row_col_idx].strip() != "" for r in h_rows)
        if same_h:
            row_identifier_cols.add(c)
        else:
            break

    # Deteksi tabel bersambung samping (side-by-side continuation)
    first_data_col = min([c for c in range(max_c) if c not in row_identifier_cols] or [best_row_col_idx + 1])
    ref_row_samples = [grid[r][best_row_col_idx].strip() for r in range(data_start, min(data_start + 10, max_r))]
    for c in range(first_data_col, max_c):
        col_samples = [grid[r][c].strip() for r in range(data_start, min(data_start + 10, max_r))]
        if ref_row_samples and col_samples == ref_row_samples:
            row_identifier_cols.add(c)
            if c > 0 and (c - 1) not in row_identifier_cols:
                prev_samples = [grid[r][c-1].strip() for r in range(data_start, min(data_start + 6, max_r))]
                if all(is_row_number_str(v) for v in prev_samples if v):
                    row_identifier_cols.add(c - 1)

    # 3. Deteksi Baris Periode Waktu di Header
    period_row_indices = set()
    year_row_idx = None
    month_row_idx = None

    data_cols = [c for c in range(max_c) if c not in row_identifier_cols]
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

    # 4. Baris Header Khusus Karakteristik (Tanpa baris periode waktu)
    col_h_rows = [r for r in h_rows if r not in period_row_indices and any(grid[r][c].strip() for c in data_cols)]
    if not col_h_rows and h_rows:
        col_h_rows = h_rows

    # 5. Susun Columns Info & Hierarki Cerdas
    columns_info = []
    distinct_parents = []
    seen_parents = set()

    for c in range(max_c):
        col_letter = chr(65 + c) if c < 26 else f"Col{c+1}"
        is_row_id = (c in row_identifier_cols)

        # Cek apakah kolom ini kosong total
        is_empty_col = not any(grid[r][c].strip() for r in range(header_start, max_r))
        if is_empty_col:
            continue

        ind_header = ""
        en_header = ""
        sub_header = ""

        if is_row_id:
            top_vals = [grid[r][c].strip() for r in h_rows if grid[r][c].strip()]
            top_val = top_vals[0] if top_vals else f"Baris {col_letter}"
            if '\n' in top_val:
                p = top_val.split('\n')
                ind_header, en_header = p[0].strip(), p[1].strip()
            elif ' / ' in top_val:
                p = top_val.split(' / ')
                ind_header, en_header = p[0].strip(), p[1].strip()
            else:
                ind_header = top_val
                en_header = top_val
        else:
            raw_h_vals = [grid[r][c].strip() for r in col_h_rows if grid[r][c].strip()]
            if len(col_h_rows) >= 2:
                r_top = grid[col_h_rows[0]][c].strip()
                r_sub = grid[col_h_rows[1]][c].strip()
                r_third = grid[col_h_rows[2]][c].strip() if len(col_h_rows) > 2 else ""

                # Cek apakah r_top membentang (muncul di kolom sebelah dengan nilai sama tapi r_sub beda)
                is_parent_span = False
                if c + 1 < max_c and grid[col_h_rows[0]][c+1].strip() == r_top and grid[col_h_rows[1]][c+1].strip() != r_sub and r_top:
                    is_parent_span = True
                elif c > 0 and grid[col_h_rows[0]][c-1].strip() == r_top and grid[col_h_rows[1]][c-1].strip() != r_sub and r_top:
                    is_parent_span = True

                is_bilingual_translation = (r_sub.lower() in KNOWN_EN_TERMS or 
                                            (r_top.lower() in KNOWN_EN_TERMS.values() and not is_parent_span))

                if is_parent_span and not is_bilingual_translation:
                    ind_header = r_top
                    sub_header = r_sub
                    if r_third:
                        sub_header += f" - {r_third}"
                elif is_bilingual_translation:
                    ind_header = r_top
                    en_header = r_sub
                    if r_third:
                        sub_header = r_third
                else:
                    ind_header = r_top if r_top else r_sub
                    if r_sub and r_sub != r_top:
                        sub_header = r_sub
            elif len(col_h_rows) == 1:
                ind_header = grid[col_h_rows[0]][c].strip()

            if not ind_header and raw_h_vals:
                ind_header = raw_h_vals[0]
            if not ind_header:
                ind_header = f"Kolom {col_letter}"

            # Pecah bilingual internal sel
            if not en_header or en_header == ind_header:
                if "\n" in ind_header:
                    p = ind_header.split("\n")
                    ind_header, en_header = p[0].strip(), p[1].strip()
                elif " / " in ind_header:
                    p = ind_header.split(" / ")
                    ind_header, en_header = p[0].strip(), p[1].strip()
                elif ind_header.lower() in KNOWN_EN_TERMS.values():
                    for en_k, id_v in KNOWN_EN_TERMS.items():
                        if id_v.lower() == ind_header.lower():
                            en_header = en_k.title()
                            break

            if not en_header:
                en_header = ind_header

        full_title = ind_header
        if sub_header and sub_header != ind_header:
            full_title = f"{ind_header} ({sub_header})"

        col_vals = [grid[r][c].strip() for r in range(data_start, max_r) if grid[r][c].strip()]
        preview_vals = [v for v in col_vals if v != ind_header and not is_col_num_series([v])][:6]

        columns_info.append({
            "index": c,
            "letter": col_letter,
            "header": ind_header,
            "header_en": en_header,
            "sub_header": sub_header,
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

    # 7. Susun auto_rows
    auto_rows = []
    seen_rows = set()
    for r in range(data_start, max_r):
        val_ind = grid[r][best_row_col_idx].strip()
        if not val_ind or is_col_num_series([val_ind]) or is_row_number_str(val_ind):
            continue
        lower_v = val_ind.lower()
        if lower_v in ROW_ID_KEYWORDS:
            continue
        # Berhenti jika bertemu baris footer
        if any(lower_v.startswith(p) for p in ['sumber', 'source', 'catatan', 'note', 'keterangan']):
            break

        clean_v = val_ind
        level = 0
        if val_ind.startswith('----'):
            level = 2
            clean_v = val_ind[4:].strip()
        elif val_ind.startswith('--'):
            level = 1
            clean_v = val_ind[2:].strip()
        elif val_ind.startswith('    '):
            level = 2
            clean_v = val_ind.strip()
        elif val_ind.startswith('  ') or val_ind.startswith('\t'):
            level = 1
            clean_v = val_ind.strip()

        row_key = f"{level}::{clean_v.lower()}"
        if row_key in seen_rows:
            continue
        seen_rows.add(row_key)

        val_en = clean_v
        if lower_v in MONTH_MAP:
            val_en = MONTH_MAP[lower_v]

        auto_rows.append({"ind": clean_v, "en": val_en, "level": level})

    # 8. Usulan Nama Cerdas
    best_col_meta = next((c for c in columns_info if c['index'] == best_row_col_idx), None)
    sugg_baris_ind = best_col_meta['header'] if best_col_meta else 'Judul Baris'
    sugg_baris_en  = best_col_meta['header_en'] if best_col_meta else sugg_baris_ind

    if any(k in sugg_baris_ind.lower() for k in ['tampilkan data', 'kolom', 'baris', 'lanjutan', 'no.', 'no']):
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
    if not sugg_kolom_ind and distinct_parents:
        if len(distinct_parents) == 1 and distinct_parents[0]['ind'] not in ['Kolom', 'Data']:
            sugg_kolom_ind = distinct_parents[0]['ind']
            sugg_kolom_en  = distinct_parents[0]['en']
        elif sheet_clean and sheet_clean.lower() != 'sheet1' and not re.match(r'^\d+(\.\d+)*(\s*\(\d+\))?$', sheet_clean) and not sheet_clean.lower().startswith('tabel'):
            sugg_kolom_ind = sheet_clean
            sugg_kolom_en = sheet_clean
        elif distinct_parents:
            sugg_kolom_ind = distinct_parents[0]['ind']

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

def extract_rows(source, sheet_name=None, ind_col=0, en_col=None, deduplicate=True, clean_headers=True, auto_translate_month=True):
    res = fetch_raw_data(source, sheet_name)
    if "error" in res:
        return res

    grid = res.get("rows", [])
    if not grid:
        return {"error": "Sheet kosong."}

    max_r = len(grid)
    header_start, header_end, data_start, h_rows, col_num_row_idx = detect_structure(grid)

    extracted = []
    seen = set()

    start_idx = data_start if clean_headers else 0

    for r_idx in range(start_idx, max_r):
        row = grid[r_idx]
        if ind_col >= len(row):
            continue

        val_ind = row[ind_col].strip()
        if not val_ind:
            continue

        if clean_headers:
            if is_col_num_series([val_ind]) or is_row_number_str(val_ind):
                continue
            lower_ind = val_ind.lower()
            if lower_ind in ROW_ID_KEYWORDS:
                continue
            if any(lower_ind.startswith(p) for p in ['sumber', 'source', 'catatan', 'note', 'keterangan']):
                break

        val_en = ""
        if en_col is not None and en_col >= 0 and en_col < len(row):
            val_en = row[en_col].strip()

        if auto_translate_month and not val_en:
            lower = val_ind.lower()
            if lower in MONTH_MAP:
                val_en = MONTH_MAP[lower]

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
        print(json.dumps({"error": "Argumen tidak mencukupi"}, ensure_ascii=True))
        sys.exit(1)

    mode = sys.argv[1]
    src = sys.argv[2]
    sheet = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] != "None" else None

    if mode == "inspect":
        result = inspect_source(src, sheet)
        print(json.dumps(result, ensure_ascii=True))
    elif mode == "extract":
        ind_col = int(sys.argv[4]) if len(sys.argv) > 4 else 0
        en_col = int(sys.argv[5]) if len(sys.argv) > 5 and sys.argv[5] != "None" and sys.argv[5] != "-1" else None
        dedup = (sys.argv[6].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 6 else True
        clean_headers = (sys.argv[7].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 7 else True
        auto_translate = (sys.argv[8].lower() in ['1', 'true', 'yes']) if len(sys.argv) > 8 else True
        result = extract_rows(src, sheet, ind_col, en_col, deduplicate=dedup, clean_headers=clean_headers, auto_translate_month=auto_translate)
        print(json.dumps(result, ensure_ascii=True))
    else:
        print(json.dumps({"error": f"Mode tidak dikenal: {mode}"}, ensure_ascii=True))
