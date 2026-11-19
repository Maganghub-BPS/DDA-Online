#Untuk menentukan nomor baris untuk kolom 
import re
import json
import threading
from normalizer import (
    parse_num, clean_header_text, find_wilayah_with_type,
    is_bps_col_num, is_title_row, resolve_jateng_entity,
    resolve_provinsi_entity, is_header_or_metadata_row,
    strip_pdf_page_watermark, clean_pdf_watermark_text
)
from pdf_extractor import extract_table_content

_CLEAN_PAGE_CACHE = {}
_CLEAN_PAGE_CACHE_LOCK = threading.Lock()

def extract_clean_page_tables(pdf_path, page_num):
    cache_key = (pdf_path, page_num)
    with _CLEAN_PAGE_CACHE_LOCK:
        if cache_key in _CLEAN_PAGE_CACHE:
            return _CLEAN_PAGE_CACHE[cache_key]

    tables_data = []
    try:
        import pymupdf as fitz
        doc = fitz.open(pdf_path)
        if 0 <= page_num - 1 < len(doc):
            page = doc[page_num - 1]
            strip_pdf_page_watermark(doc, page)
            import io, contextlib
            with contextlib.redirect_stdout(io.StringIO()):
                tabs = page.find_tables()
            if tabs.tables:
                for t in tabs.tables:
                    g = t.extract()
                    if g and len(g) >= 2:
                        tables_data.extend(g)
    except Exception:
        pass

    if not tables_data:
        try:
            from pdf_extractor import get_cached_page_tables
            tables_data = get_cached_page_tables(pdf_path, page_num)
        except Exception:
            pass

    with _CLEAN_PAGE_CACHE_LOCK:
        _CLEAN_PAGE_CACHE[cache_key] = tables_data
    return tables_data

def find_bps_numbering_row(source_data):
    if not source_data or not isinstance(source_data, list): return -1, [], 'none'
    year_idx, year_cells = -1, []
    best_candidate = None
    best_score = -1

    for i, r in enumerate(source_data[:25]):
        if not isinstance(r, list): continue
        non_empty = [(idx, str(c).strip()) for idx, c in enumerate(r) if c is not None and str(c).strip() and str(c).strip().lower() != 'none']
        if len(non_empty) < 2: continue
        
        all_bps = all((re.match(r'^[\(\-]?(\d+)\)?$', v) and int(re.match(r'^[\(\-]?(\d+)\)?$', v).group(1)) <= 150) for _, v in non_empty)
        if all_bps:
            has_parens = any(re.match(r'^\(\d+\)$', v) or (v.startswith('-') and v[1:].isdigit()) for _, v in non_empty)
            all_parens = all(re.match(r'^\(\d+\)$', v) or (v.startswith('-') and v[1:].isdigit()) for _, v in non_empty)
            starts_one = (int(re.match(r'^[\(\-]?(\d+)\)?$', non_empty[0][1]).group(1)) == 1 and non_empty[0][0] <= 1)
            starts_early = (non_empty[0][0] <= 1)
            nums = [int(re.match(r'^[\(\-]?(\d+)\)?$', v).group(1)) for _, v in non_empty]
            is_seq = (nums == list(range(nums[0], nums[0] + len(nums))))
            
            # Jika tidak ada tanda kurung dan nomor tidak berurutan, ini pasti baris data angka kecil, bukan nomor kolom BPS!
            if not has_parens and not is_seq:
                continue

            score = (150 if all_parens else (60 if has_parens else 0)) + \
                    (60 if (starts_one and is_seq) else 0) + \
                    (30 if starts_early else 0) + \
                    (30 if is_seq else 0) + \
                    len(non_empty)
            if score > best_score:
                best_score = score
                best_candidate = (i, non_empty, 'bps')
            
        all_year = all(re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', val) for _, val in non_empty)
        if all_year and year_idx == -1:
            year_idx = i
            year_cells = non_empty

    if best_candidate and best_score >= 50:
        return best_candidate
    if year_idx >= 0:
        return year_idx, year_cells, 'year'
    if best_candidate:
        return best_candidate
    return -1, [], 'none'

def detect_active_data_cols(source_data):
    if not source_data or not isinstance(source_data, list): return []
    
    bps_row_idx, bps_cells, row_type = find_bps_numbering_row(source_data)
    
    if bps_row_idx >= 0 and bps_cells:
        is_year_row = (row_type == 'year')
        data_col_indices = []
        for col_idx, val in bps_cells:
            if is_year_row: data_col_indices.append(col_idx)
            else:
                m = re.match(r'^\(?(\d+)\)?$', str(val).strip())
                if m:
                    num_val = int(m.group(1))
                    non_meta = []
                    for r in source_data[bps_row_idx+1:bps_row_idx+40]:
                        if not isinstance(r, list): continue
                        txt = ' '.join(str(c) for c in r).lower().strip()
                        if any(txt.startswith(k) for k in ['sumber:', 'source:', 'catatan:', 'note:', 'perubahan data:']): break
                        if col_idx < len(r) and r[col_idx] is not None and str(r[col_idx]).strip():
                            non_meta.append(str(r[col_idx]).strip())
                    
                    first_3_digits = [re.sub(r'[^\d]', '', v) for v in non_meta[:3]]
                    is_row_num = (col_idx <= 1) and (len(first_3_digits) == 3 and first_3_digits == ['1', '2', '3'])
                    num_count = sum(1 for v in non_meta if parse_num(v) is not None or v in ('-', '–', '—', '−'))
                    is_mostly_numeric = (num_count >= 1 and num_count >= len(non_meta) * 0.3) if non_meta else False
                    is_empty_target_col = (not non_meta or all(v in ['', 'None', '-', '–', '—', '−', 'NA', 'na', 'null'] for v in non_meta))
                    
                    if num_val >= 2:
                        is_unit_col = any(
                            re.search(r'\b(?:satuan|unit)\b', clean_header_text(r[col_idx]).lower())
                            and not re.search(r'\b(?:jumlah|banyaknya|total|nilai|pendidikan|kapal|kerja|usaha|rumah)\b', clean_header_text(r[col_idx]).lower())
                            for r in source_data[:bps_row_idx]
                            if isinstance(r, list) and col_idx < len(r) and r[col_idx]
                        )
                        has_valid_hdr = any(
                            isinstance(r, list) and col_idx < len(r) and r[col_idx] is not None and str(r[col_idx]).strip()
                            for r in source_data[:bps_row_idx]
                        )
                        if not is_row_num and (is_mostly_numeric or (is_empty_target_col and has_valid_hdr)) and not is_unit_col:
                            data_col_indices.append(col_idx)
        if data_col_indices: return data_col_indices
    
    data_rows = []
    is_k = False
    brebes_seen = False
    for r in source_data:
        if not isinstance(r, list): continue
        txt = ' '.join(str(c) for c in r).lower()
        if 'kota/municipality' in txt: is_k = True; brebes_seen = True
        elif 'kabupaten/regency' in txt: is_k = False; brebes_seen = False
        
        c0 = str(r[0]).strip().rstrip('.')
        if c0.isdigit():
            num0 = int(c0)
            if num0 > 6: is_k = False
            elif brebes_seen and 1 <= num0 <= 6: is_k = True
        
        label_parts = [str(c).strip() for c in r[:3] if str(c).strip() and parse_num(str(c).strip()) is None]
        w = find_wilayah_with_type(' '.join(label_parts), is_kota=is_k)
        if 'Brebes' in w: brebes_seen = True
        if w not in ['Umum', 'Total Jawa Tengah']:
            data_rows.append(r)
            continue
        
        if c0.isdigit() and int(c0) <= 40:
            if sum(1 for c in r[1:] if parse_num(str(c).strip()) is not None) >= 1:
                data_rows.append(r)
                
    if len(data_rows) < 3:
        # Fallback generik untuk tabel kategorikal/non-wilayah (misal partai, komoditas, golongan)
        gen_rows = []
        for r in source_data:
            if not isinstance(r, list): continue
            if is_header_or_metadata_row(r) or is_title_row(r): continue
            if any(parse_num(c, is_pdf=False) is not None for c in r):
                gen_rows.append(r)
        if len(gen_rows) >= 2 or (len(gen_rows) == 1 and len(source_data) >= 2 and len(gen_rows[0]) >= 2):
            data_rows = gen_rows
        else:
            return []
        
    first_dr_idx = next((i for i, row in enumerate(source_data) if data_rows and row is data_rows[0]), 0)
    hdr_block = source_data[:max(1, first_dr_idx)]

    max_c = max(len(r) for r in data_rows)
    data_cols = []
    for c_idx in range(max_c):
        vals = [
            '' if (c_idx >= len(r) or r[c_idx] is None or str(r[c_idx]).strip().lower() in ('none', 'null', 'nan'))
            else str(r[c_idx]).strip()
            for r in data_rows
        ]
        non_empty_vals = [v for v in vals if v]

        clean_ints = [int(v.rstrip('.')) for v in non_empty_vals if v.rstrip('.').isdigit()]
        if (
            c_idx <= 1
            and len(clean_ints) >= len(data_rows) * 0.8
            and all(0 <= x <= 50 for x in clean_ints)
            and len(clean_ints) >= 3
            and clean_ints[0] in [0, 1]
            and clean_ints[1] == clean_ints[0] + 1
            and clean_ints[2] == clean_ints[1] + 1
        ):
            continue
        if c_idx <= 2 and non_empty_vals and all(re.match(r'^33\.\d{2}$', v) for v in non_empty_vals): continue
        if c_idx == 0 and max_c >= 2 and non_empty_vals and all(re.match(r'^(?:19|20)\d{2}$', v) for v in non_empty_vals if v.lower() not in ['jumlah', 'total', 'jumlah/total', 'rata-rata']): continue

        hdr_txt = ' '.join(
            clean_header_text(r[c_idx]).lower()
            for r in hdr_block
            if isinstance(r, list) and c_idx < len(r) and r[c_idx] is not None
        ).strip()

        if non_empty_vals:
            num_ok = sum(
                1 for v in non_empty_vals
                if parse_num(v, is_pdf=False) is not None or v in ('-', '–', '—', '−', '...', '…')
            )
            if num_ok >= len(non_empty_vals) * 0.5:
                data_cols.append(c_idx)
        elif c_idx >= 1 and hdr_txt and not re.search(
            r'\b(?:wilayah|kabupaten|kota|provinsi|regency|municipality|uraian|kode|code|nomor|satuan|unit|sumber|keterangan|catatan)\b',
            hdr_txt,
        ):
            # Kolom berheader resmi dengan seluruh sel None/kosong tetap dipertahankan sebagai kolom metrik bernilai '-' (0)
            data_cols.append(c_idx)

    return data_cols

def detect_col_headers(source_data):
    if not source_data or not isinstance(source_data, list): return []
    num_row_idx, _, row_type = find_bps_numbering_row(source_data)

    active_cols = detect_active_data_cols(source_data)
    if active_cols:
        first_data_idx = num_row_idx + 1 if num_row_idx >= 0 else 0
        if first_data_idx == 0:
            for idx, r in enumerate(source_data):
                if not isinstance(r, list): continue
                if find_wilayah_with_type(' '.join(str(c) for c in r[:3])) not in ['Umum', 'Total Jawa Tengah']:
                    first_data_idx = idx
                    break
                c0 = str(r[0]).strip().rstrip('.')
                if c0.isdigit() and int(c0) <= 40 and any(parse_num(str(c).strip()) is not None for c in r[1:]):
                    first_data_idx = idx
                    break
        if first_data_idx == 0:
            for idx, r in enumerate(source_data):
                if not isinstance(r, list) or is_header_or_metadata_row(r):
                    continue
                nums_in_active = [parse_num(str(r[c]).strip()) for c in active_cols if c < len(r) and parse_num(str(r[c]).strip()) is not None]
                if nums_in_active:
                    first_data_idx = idx
                    break
        
        limit_hdr = num_row_idx + 1 if row_type == 'year' else (num_row_idx if num_row_idx != -1 else first_data_idx)
        header_rows = [r for r in source_data[:limit_hdr] if isinstance(r, list) and not is_title_row(r)]
        
        # Horizontal forward fill for merged header cells
        max_c = max((len(r) for r in header_rows if isinstance(r, list)), default=0)
        filled_header_rows = []
        first_active = active_cols[0] if active_cols else 1
        for r_idx, r in enumerate(header_rows):
            r_padded = [clean_header_text(c) for c in r] + [''] * max(0, max_c - len(r))
            f_row = []
            cur = ""
            is_last_header_row = (r_idx == len(header_rows) - 1)
            for idx, cell in enumerate(r_padded):
                is_boundary = any(
                    bool(re.search(r'\b(?:kabupaten/kota|nama\s+kabupaten|nama\s+kota|nama\s+daerah|regency/municipality|lanjutan\s+tabel|kelompok\s+komoditas)\b', clean_header_text(hr[idx]).lower()))
                    for hr in header_rows if idx < len(hr) and hr[idx]
                )
                if is_boundary or idx < first_active:
                    cur = ""
                    f_row.append(cell)
                elif cell:
                    cur = cell
                    f_row.append(cell)
                else:
                    if not is_last_header_row and cur and not is_bps_col_num(cur):
                        f_row.append(cur)
                    else:
                        f_row.append("")
            filled_header_rows.append(f_row)

        col_headers = []
        for c_idx in active_cols:
            parts = []
            for r in filled_header_rows:
                if c_idx < len(r):
                    val = r[c_idx].strip()
                    if not val or is_bps_col_num(val): continue
                    if any(k in val.lower() for k in ['sumber', 'source', 'catatan', 'note', 'perubahan data']): continue
                    if re.match(r'^(?:tabel|table)\b', val.lower()) and not any(k in val.lower() for k in ['rp', 'jumlah', 'nilai', 'luas', 'total', '%', 'ha', 'km', 'desa', 'jiwa']):
                        continue
                    if val not in parts: parts.append(val)
            lbl = ' - '.join(parts) if parts else ""
            if lbl:
                col_headers.append(f"Tahun {lbl}" if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', lbl) else lbl)
            else:
                col_headers.append(f"Kolom {c_idx+1}")
        if col_headers: return col_headers

    first_data_idx = -1
    for i, r in enumerate(source_data[:25]):
        if not isinstance(r, list): continue
        if str(r[0]).strip().lower().startswith(('tabel', 'table')): continue
        if any(re.search(rf'\b{w.lower()}\b', ' '.join(str(c).lower().strip() for c in r[:4])) for w in ['cilacap', 'banyumas', 'purbalingga', 'magelang', 'surakarta']):
            first_data_idx = i
            break
            
    if first_data_idx == -1 and num_row_idx != -1: first_data_idx = num_row_idx + 1

    if first_data_idx == -1:
        for i, r in enumerate(source_data[:25]):
            if not isinstance(r, list) or str(r[0]).strip().lower().startswith(('tabel', 'table', 'sumber', 'source', 'catatan', 'note')): continue
            nums = [parse_num(c) for c in r if parse_num(c) is not None]
            if len(nums) >= 2:
                first_data_idx = i
                break
            elif len(nums) == 1 and i >= 1:
                prev_r = source_data[i-1]
                if isinstance(prev_r, list) and len(prev_r) == len(r) and not any(parse_num(c) is not None for c in prev_r):
                    first_data_idx = i
                    break
            
    if first_data_idx > 0:
        limit_hdr = num_row_idx if num_row_idx != -1 else first_data_idx
        header_rows = [r for r in source_data[:limit_hdr] if isinstance(r, list) and not is_title_row(r)]
        num_cols = max((len(r) for r in source_data[:first_data_idx + 3] if isinstance(r, list)), default=0)
        
        filled_hdr_rows = []
        for r in header_rows:
            r_padded = [clean_header_text(c) for c in r] + [''] * max(0, num_cols - len(r))
            f_row = []
            cur = ""
            for idx, cell in enumerate(r_padded):
                if idx == 0 and (any(k in cell.lower() for k in ['kabupaten', 'kota', 'regency', 'municipality', 'nama kabupaten', 'nama kota', 'jabatan', 'pendidikan', 'golongan', 'pangkat']) or cell in ['No', 'Kode']):
                    cur = ""
                    f_row.append(cell)
                elif cell:
                    cur = cell
                    f_row.append(cell)
                else:
                    if cur and not is_bps_col_num(cur):
                        f_row.append(cur)
                    else:
                        f_row.append("")
            filled_hdr_rows.append(f_row)
            
        col_names = {}
        for col_idx in range(num_cols):
            levels = []
            for r in filled_hdr_rows:
                if col_idx < len(r) and r[col_idx] and not is_bps_col_num(r[col_idx]) and r[col_idx] not in levels:
                    levels.append(r[col_idx])
            if levels: col_names[col_idx] = ' - '.join(levels)
                
        data_headers = []
        for c_idx in range(1, num_cols):
            lbl = col_names.get(c_idx, f"Kolom {len(data_headers)+1}")
            data_headers.append(f"Tahun {lbl}" if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', lbl) else lbl)

        start_idx = 0
        skip_words = ['no', 'nomor', 'kode', 'kode wilayah', 'nama kabupaten', 'nama kota', 'kabupaten/kota', 'regency/municipality', 'nama kabupaten / kota', 'nama daerah', 'wilayah', 'jenis', 'uraian', 'kategori', 'rincian', 'nama']
        while start_idx < len(data_headers) and any(n == k or n.startswith(k + ' ') or n.endswith(' ' + k) or n.startswith(k + '/') for k in skip_words for n in [str(data_headers[start_idx]).lower().strip()]):
            start_idx += 1
        if data_headers[start_idx:]: return data_headers[start_idx:]

    headers = []
    for r in source_data[:8]:
        if not isinstance(r, list) or str(r[0]).strip().lower().startswith(('tabel', 'table')): continue
        if len([str(c).strip() for c in r if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', str(c).strip())]) >= 2:
            for c in r:
                if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', str(c).strip()): headers.append(f"Tahun {str(c).strip()}")
            if headers: break

        if any(k in ' '.join(str(c) for c in r).lower() for k in ['luas', 'tinggi', 'jarak', 'persentase', 'prosentase', 'jumlah', 'persen', 'penduduk', 'kepadatan', 'mdpl', 'km']):
            candidates = [str(c).replace('\n', ' ').rstrip('*').strip() for c in r if str(c).replace('\n', ' ').strip() and not any(k in str(c).replace('\n', ' ').strip().lower() for k in ['no', 'kode', 'nama kabupaten', 'kabupaten/kota', 'regency', '(1)', '(2)', '(3)'])]
            if candidates:
                headers = candidates
                break
    return headers

def extract_pdf_headers(rows, expected_p_len=None, pdf_path=None, target_pages=None, target_table=None):
    if pdf_path and target_pages and len(target_pages) > 1 and target_table:
        all_headers = []
        for p_num in target_pages:
            p_rows = extract_clean_page_tables(pdf_path, p_num)
            if not p_rows: continue
            
            bps_idx, bps_cells, _ = find_bps_numbering_row(p_rows)
            if bps_idx >= 0 and bps_cells:
                data_cols = [col_idx for col_idx, val in bps_cells if int(re.match(r'^\(?(\d+)\)?$', str(val).strip()).group(1)) >= 2]
                hdr_rows = [r for r in p_rows[:bps_idx] if isinstance(r, list) and not is_title_row(r)]
                first_data_col = data_cols[0] if data_cols else 1
            else:
                d_start = -1
                for i, r in enumerate(p_rows):
                    if not isinstance(r, list): continue
                    txt = ' '.join(str(c).replace('\n', ' ') for c in r[:3] if str(c).strip()).lower()
                    if any(re.search(rf'\b{w}\b', txt) for w in ['cilacap', 'banyumas', 'purbalingga', 'magelang', 'surakarta', 'jawa tengah', 'semarang']):
                        d_start = i
                        break
                    c0 = str(r[0]).strip().rstrip('.')
                    if c0.isdigit() and int(c0) <= 40 and any(parse_num(str(c), is_pdf=True) is not None for c in r[1:]):
                        d_start = i
                        break
                    if resolve_provinsi_entity(txt)[0] or sum(1 for c in r if parse_num(str(c), is_pdf=True) is not None) >= 2:
                        d_start = i
                        break
                if d_start <= 0: continue
                hdr_rows = [r for r in p_rows[:d_start] if isinstance(r, list) and not is_title_row(r)]
                first_data_row = p_rows[d_start]
                first_data_col = -1
                for idx, c in enumerate(first_data_row):
                    c_str = str(c).strip()
                    if not c_str: continue
                    if idx == 0 and c_str.rstrip('.').isdigit(): continue
                    if idx <= 3 and re.match(r'^33\.\d{2}$', c_str): continue
                    if parse_num(c_str, is_pdf=True) is not None:
                        first_data_col = idx
                        break
                if first_data_col == -1: first_data_col = 2
                data_cols = None

            if not hdr_rows: continue
            
            max_c = max(len(r) for r in hdr_rows)
            filled_hdr = []
            for r_idx, r in enumerate(hdr_rows):
                r_padded = [clean_header_text(c) for c in r] + [''] * max(0, max_c - len(r))
                f_row = []
                cur = ""
                is_last_hdr = (r_idx == len(hdr_rows) - 1)
                for idx, cell in enumerate(r_padded):
                    if idx < first_data_col:
                        f_row.append("")
                    elif cell:
                        cur = cell
                        f_row.append(cell)
                    else:
                        f_row.append(cur if not is_last_hdr and cur and not is_bps_col_num(cur) else "")
                filled_hdr.append(f_row)
                
            cur_page_headers = []
            active_col_range = data_cols if data_cols is not None else list(range(first_data_col, max_c))
            for c_idx in active_col_range:
                parts = []
                for r in filled_hdr:
                    if c_idx < len(r):
                        val = r[c_idx].strip()
                        if val and not is_bps_col_num(val) and val not in parts:
                            parts.append(val)
                lbl = ' - '.join(parts) if parts else ""
                lbl = re.sub(r'^(?:Tahun\s+)?(19\d{2}|20\d{2})\d$', r'\1', lbl)
                if lbl:
                    cur_page_headers.append(f"Tahun {lbl}" if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', lbl) else lbl)
            
            if not all_headers:
                all_headers.extend(cur_page_headers)
            else:
                if cur_page_headers != all_headers[-len(cur_page_headers):]:
                    all_headers.extend(cur_page_headers)

            if expected_p_len and len(all_headers) >= expected_p_len:
                break

        if all_headers and (not expected_p_len or len(all_headers) == expected_p_len):
            return all_headers

    if not rows or not isinstance(rows, list): return []
    bps_row_idx, bps_cells, row_type = find_bps_numbering_row(rows)
    if bps_row_idx >= 0 and bps_cells:
        data_cols = [col_idx for col_idx, val in bps_cells if int(re.match(r'^\(?(\d+)\)?$', str(val).strip()).group(1)) >= 2]
        if expected_p_len and len(data_cols) > expected_p_len:
            sample_body = [r for r in rows[bps_row_idx + 1:] if isinstance(r, list) and not is_header_or_metadata_row(r) and not is_title_row(r)]
            num_cols = [
                ci for ci in data_cols
                if any(
                    ci < len(r) and r[ci] is not None and (
                        str(r[ci]).strip() in ('...', '…', '..')
                        or parse_num(str(r[ci]).strip(), is_pdf=True) is not None
                    )
                    for r in sample_body
                )
            ]
            if len(num_cols) == expected_p_len:
                data_cols = num_cols
        if not expected_p_len or len(data_cols) == expected_p_len:
            hdr_rows = [r for r in rows[:bps_row_idx] if isinstance(r, list) and not is_title_row(r)]
            max_c = max(len(r) for r in hdr_rows)
            filled_hdr = []
            for r in hdr_rows:
                r_padded = [clean_header_text(c) for c in r] + [''] * max(0, max_c - len(r))
                f_row = []
                cur = ""
                for idx, cell in enumerate(r_padded):
                    if cell:
                        cur = cell
                        f_row.append(cell)
                    else:
                        if cur and not is_bps_col_num(cur):
                            f_row.append(cur)
                        else:
                            f_row.append("")
                filled_hdr.append(f_row)
            col_headers = []
            for c_idx in data_cols:
                parts = []
                for r in filled_hdr:
                    if c_idx < len(r):
                        val = r[c_idx].strip()
                        if val and not is_bps_col_num(val) and val not in parts:
                            parts.append(val)
                lbl = ' - '.join(parts) if parts else ""
                col_headers.append(f"Tahun {lbl}" if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', lbl) else (lbl or f"PDF #{c_idx+1}"))
            return col_headers
    data_start = -1
    for i, r in enumerate(rows):
        if not isinstance(r, list): continue
        txt = ' '.join(str(c).replace('\n', ' ') for c in r[:3] if str(c).strip()).lower()
        if any(re.search(rf'\b{w.lower()}\b', txt) for w in ['cilacap', 'banyumas', 'purbalingga', 'magelang', 'surakarta', 'jawa tengah']):
            data_start = i
            break
        c0 = str(r[0]).strip().rstrip('.')
        if c0.isdigit() and int(c0) <= 40 and any(parse_num(str(c), is_pdf=True) is not None for c in r[1:]):
            data_start = i
            break
            
    if data_start <= 0:
        return [f"PDF #{i+1}" for i in range(expected_p_len)] if expected_p_len else []
        
    hdr_rows = [r for r in rows[:data_start] if isinstance(r, list) and not is_title_row(r)]
    if not hdr_rows:
        return [f"PDF #{i+1}" for i in range(expected_p_len)] if expected_p_len else []
        
    first_data_row = rows[data_start]
    first_data_col = -1
    for idx, c in enumerate(first_data_row):
        c_str = str(c).strip()
        if not c_str: continue
        if idx == 0 and c_str.rstrip('.').isdigit(): continue
        if idx <= 3 and re.match(r'^33\.\d{2}$', c_str): continue
        if parse_num(c_str, is_pdf=True) is not None:
            first_data_col = idx
            break
    if first_data_col == -1: first_data_col = 2
    
    max_c = max(len(r) for r in hdr_rows)
    num_to_extract = expected_p_len if expected_p_len else (max_c - first_data_col)
    
    filled_hdr = []
    for r in hdr_rows:
        r_padded = [clean_header_text(c) for c in r] + [''] * max(0, max_c - len(r))
        f_row = []
        cur = ""
        for idx, cell in enumerate(r_padded):
            if idx < first_data_col:
                f_row.append("")
            elif cell:
                cur = cell
                f_row.append(cell)
            else:
                if cur and not re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', cur) and not is_bps_col_num(cur):
                    f_row.append(cur)
                else:
                    f_row.append("")
        filled_hdr.append(f_row)

    body_rows = [r for r in rows[data_start:] if isinstance(r, list) and not is_header_or_metadata_row(r) and not is_title_row(r)]
    numeric_col_idxs = [
        ci for ci in range(first_data_col, max_c)
        if any(
            ci < len(r) and r[ci] is not None and (
                str(r[ci]).strip() in ('...', '…', '..')
                or parse_num(str(r[ci]).strip(), is_pdf=True) is not None
            )
            for r in body_rows
        )
    ]

    col_headers = []
    for c_idx in range(num_to_extract):
        parts = []
        for r in filled_hdr:
            if len(numeric_col_idxs) == num_to_extract:
                r_idx = numeric_col_idxs[c_idx]
            else:
                offset = max(0, len(r) - num_to_extract)
                r_idx = max(first_data_col + c_idx, offset + c_idx)
            if r_idx < len(r):
                val = r[r_idx].strip()
                if val and not is_bps_col_num(val) and val not in parts:
                    parts.append(val)
        lbl = ' - '.join(parts) if parts else ""
        if lbl:
            col_headers.append(f"Tahun {lbl}" if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', lbl) else lbl)
        else:
            col_headers.append(f"PDF #{c_idx+1}")
            
    return col_headers

def align_columns_semantically(src_headers, pdf_headers, src_dict, pdf_dict, target_year=None, table_title=""):
    s_len = len(src_headers)
    p_len = len(pdf_headers)
    
    if s_len == 0 or p_len == 0:
        return {i: i for i in range(min(s_len, p_len))}, list(range(s_len, p_len)), {}, {}

    def extract_year(text):
        if not text: return None
        cleaned = re.sub(r'\b(19\d{2}|20\d{2})[1-9\*\^rR\†\‡]+(?:\s*,\s*[1-9\*\^rR\†\‡]+)*\)?(?=\s|$|[\)\-\/.,])', r'\1', str(text))
        m_acad = re.findall(r'\b(19\d{2}|20\d{2})\s*/\s*(?:19\d{2}|20\d{2})\b', cleaned)
        if m_acad:
            return m_acad[-1]
        m_range = re.findall(r'(?:19\d{2}|20\d{2})\s*[\-–—]\s*(19\d{2}|20\d{2})\b', cleaned)
        if m_range:
            return m_range[-1]
        m = re.findall(r'\b(19\d{2}|20\d{2})\b', cleaned)
        if m: return m[-1]
        m_glued = re.findall(r'\b(19\d{2}|20\d{2})[\d\*\^rR\†\‡]', str(text))
        if m_glued: return m_glued[-1]
        return None

    def extract_tokens(text):
        if not text: return set()
        t_split = re.sub(r'(?<=\d)(?=[ds]\d)', ' ', str(text).lower())
        cleaned = re.sub(r'[\(\)\[\]\{\}\.,:;/\-–—\*\d]', ' ', str(text).lower())
        cleaned_split = re.sub(r'[\(\)\[\]\{\}\.,:;/\-–—\*]', ' ', t_split)
        stop = {'tahun', 'year', 'kolom', 'data', 'dan', 'di', 'ke', 'dari', 'yang', 'untuk', 'kabupaten', 'kota'}
        raw_toks = {w for w in cleaned.split() if len(w) >= 3 and w not in stop}
        raw_toks.update(w for w in cleaned_split.split() if re.match(r'^(?:d[1-4]|s[1-3])$', w))
        norm_toks = set(raw_toks)
        t_full = str(text).lower()

        for t in raw_toks:
            if t == 'pendidikan':
                norm_toks.add('sekolah')
            elif any(k in t for k in ['guru', 'teacher', 'headmaster']) or t == 'pendidik':
                norm_toks.add('guru')
            elif any(k in t for k in ['murid', 'siswa', 'pupil', 'student']) or (t == 'didik' and 'peserta' in t_full):
                norm_toks.add('murid')
            elif any(k in t for k in ['sekolah', 'school', 'satuan']) and 'kepala' not in t_full:
                norm_toks.add('sekolah')
            elif t in ('pria', 'male', 'laki'):
                norm_toks.add('laki')
            elif t in ('wanita', 'female', 'perempuan'):
                norm_toks.add('perempuan')
            elif t in ('persen', 'percentage', 'persentase'):
                norm_toks.add('persentase')
            elif t in ('budha', 'buddha'):
                norm_toks.add('buddha')
            elif re.match(r'^kh?ongh?uch?u$', t):
                norm_toks.add('khonghucu')

        if 'kepala' in raw_toks or 'pendidik' in raw_toks or 'guru' in raw_toks:
            norm_toks.add('guru')
            norm_toks.discard('sekolah')
        return norm_toks

    src_years = [extract_year(h) for h in src_headers]
    pdf_years = [extract_year(h) for h in pdf_headers]
    
    src_tokens = [extract_tokens(h) for h in src_headers]
    pdf_tokens = [extract_tokens(h) for h in pdf_headers]

    common_w = [w for w in src_dict if w in pdf_dict and w not in ['Total Jawa Tengah', 'Provinsi Jawa Tengah']]

    src_years_with_data = set()
    if src_dict:
        for idx_s, sy_chk in enumerate(src_years):
            if not sy_chk:
                continue
            has_val = any(
                (idx_s < len(s_vals) and s_vals[idx_s] is not None and s_vals[idx_s] != 0.0 and ((isinstance(s_vals[idx_s], (int, float)) and s_vals[idx_s] > -900000) or isinstance(s_vals[idx_s], str)))
                for s_vals in src_dict.values()
            )
            if has_val:
                src_years_with_data.add(sy_chk)

    shared_dim_tokens = {
        tok for tok in ('laki', 'perempuan', 'masuk', 'keluar', 'negeri', 'swasta', 'persentase', 'tps')
        if any(any(tok in w for w in ts) for ts in src_tokens) and any(any(tok in w for w in ts) for ts in pdf_tokens)
    }

    score_matrix = [[0.0 for _ in range(p_len)] for _ in range(s_len)]
    
    for s_idx in range(s_len):
        for p_idx in range(p_len):
            score = 0.0
            
            # 1. VALUE SIGNATURE (Data Correlation)
            if common_w:
                match_cnt = 0
                total_cnt = 0
                for w in common_w:
                    s_vals = src_dict.get(w, [])
                    p_vals = pdf_dict.get(w, [])
                    if s_idx < len(s_vals) and p_idx < len(p_vals):
                        sv = s_vals[s_idx]
                        pv = p_vals[p_idx]
                        if sv is None or pv is None:
                            continue
                        if isinstance(sv, (int, float)) and sv <= -900000:
                            continue
                        if isinstance(pv, (int, float)) and pv <= -900000:
                            continue
                        total_cnt += 1
                        if isinstance(sv, (int, float)) and isinstance(pv, (int, float)):
                            if abs(sv - pv) < 0.0001:
                                match_cnt += 1
                            elif pv != 0 and abs(sv - pv) / abs(pv) < 0.0001:
                                match_cnt += 1
                            elif pv != 0 and sv != 0 and (abs(sv * 1000 - pv) <= 2.0 or abs(pv - round(sv / 1000.0, 2)) < 0.01):
                                match_cnt += 1
                            elif pv != 0 and sv != 0 and (abs(pv * 1000 - sv) <= 2.0 or abs(sv - round(pv / 1000.0, 2)) < 0.01):
                                match_cnt += 1
                            elif pv != 0 and sv != 0 and (abs(sv * 1000000 - pv) <= 2000.0 or abs(pv - round(sv / 1000000.0, 2)) < 0.01):
                                match_cnt += 1
                            elif pv != 0 and sv != 0 and (abs(pv * 1000000 - sv) <= 2000.0 or abs(sv - round(pv / 1000000.0, 2)) < 0.01):
                                match_cnt += 1
                        elif sv == pv or (sv in (0, 0.0, '-') and pv in (0, 0.0, '-')):
                            match_cnt += 1
                if total_cnt >= 5:
                    ratio = match_cnt / total_cnt
                    if ratio >= 0.70:
                        score += 150.0 * ratio
                    elif ratio >= 0.35:
                        score += 80.0 * ratio
                    elif ratio >= 0.15:
                        score += 30.0 * ratio

            # 2. YEAR MATCHING (Selesaikan masalah 2025, 2024, 2023 terbalik)
            sy = src_years[s_idx]
            py = pdf_years[p_idx]
            if sy and py:
                if sy == py:
                    score += 80.0
                else:
                    score -= 200.0
            elif target_year and py:
                if str(target_year) == str(py) or str(py).startswith(str(target_year) + '/'):
                    score += 40.0
                else:
                    score -= 20.0

            all_valid_years = [y for y in ((list(src_years_with_data) if src_years_with_data else src_years) + pdf_years) if y]
            max_yr_in_tbl = max(all_valid_years) if all_valid_years else None
            is_empty_s_col = all((s_vals[s_idx] is None or s_vals[s_idx] == 0.0) for s_vals in src_dict.values() if s_idx < len(s_vals)) if src_dict else False
            if is_empty_s_col and sy and max_yr_in_tbl and sy < max_yr_in_tbl and sy != py:
                score -= 200.0
            if sy and src_years_with_data and sy not in src_years_with_data and not (py and sy == py):
                score -= 220.0

            # 3. SEMANTIC TOKEN MATCHING
            st = src_tokens[s_idx]
            pt = pdf_tokens[p_idx]
            if st and pt:
                overlap = len(st & pt)
                union = len(st | pt)
                if union > 0:
                    score += (overlap / union) * 40.0
                    
                for cat_a, cat_b in [
                    ('laki', 'perempuan'), ('masuk', 'keluar'), ('negeri', 'swasta'), ('pns', 'pppk'), ('darat', 'laut'),
                    ('desa', 'kelurahan'), ('guru', 'murid'), ('sekolah', 'guru'), ('sekolah', 'murid'),
                    ('dosen', 'mahasiswa'), ('dosen', 'perguruan'), ('mahasiswa', 'perguruan'),
                    ('dosen', 'universitas'), ('mahasiswa', 'universitas')
                ]:
                    has_a_src = any(cat_a in w for w in st)
                    has_b_src = any(cat_b in w for w in st)
                    has_a_pdf = any(cat_a in w for w in pt)
                    has_b_pdf = any(cat_b in w for w in pt)
                    if (has_a_src and has_a_pdf) or (has_b_src and has_b_pdf):
                        score += 35.0
                    elif (has_a_src and has_b_pdf) or (has_b_src and has_a_pdf):
                        score -= 250.0

                for d_tok in shared_dim_tokens:
                    has_d_src = any(d_tok in w for w in st)
                    has_d_pdf = any(d_tok in w for w in pt)
                    if has_d_src != has_d_pdf:
                        score -= 250.0

            if s_len == p_len and s_idx == p_idx:
                score += 1.0

            score_matrix[s_idx][p_idx] = score

    col_mapping = {}
    used_pdf = set()
    try:
        from scipy.optimize import linear_sum_assignment
        import numpy as np
        row_ind, col_ind = linear_sum_assignment(-np.array(score_matrix))
        for r, c in zip(row_ind, col_ind):
            if score_matrix[r][c] > -40.0:
                col_mapping[r] = c
                used_pdf.add(c)
    except Exception:
        pairs = []
        for r in range(s_len):
            for c in range(p_len):
                pairs.append((score_matrix[r][c], r, c))
        pairs.sort(reverse=True)
        used_s = set()
        for sc, r, c in pairs:
            if r not in used_s and c not in used_pdf and sc > -40.0:
                col_mapping[r] = c
                used_s.add(r)
                used_pdf.add(c)

    for s_idx in range(s_len):
        if s_idx not in col_mapping:
            avail_p = [p for p in range(p_len) if p not in used_pdf]
            if avail_p:
                best_p = max(avail_p, key=lambda p: score_matrix[s_idx][p])
                if score_matrix[s_idx][best_p] > -40.0:
                    col_mapping[s_idx] = best_p
                    used_pdf.add(best_p)

    pdf_extra_indices = [p for p in range(p_len) if p not in col_mapping.values()]
    
    col_year_tags = {}
    for p_idx in range(p_len):
        y = pdf_years[p_idx]
        if y: col_year_tags[p_idx] = y
        
    final_metric_names = {}
    for s_idx in range(s_len):
        p_idx = col_mapping.get(s_idx)
        sh = src_headers[s_idx] if s_idx < len(src_headers) else ""
        ph = pdf_headers[p_idx] if (p_idx is not None and p_idx < len(pdf_headers)) else ""
        
        is_sh_placeholder = (not sh or re.match(r'^(?:data\s+)?kolom\s+\d+$', sh.strip().lower()))
        is_ph_placeholder = (not ph or re.match(r'^(?:data\s+)?(?:kolom|#|pdf)\s*#?\d+$', ph.strip().lower()))
        
        if not is_sh_placeholder and not is_ph_placeholder:
            final_metric_names[s_idx] = sh
        elif not is_sh_placeholder:
            final_metric_names[s_idx] = sh
        elif not is_ph_placeholder:
            final_metric_names[s_idx] = ph
        else:
            y = src_years[s_idx] or (pdf_years[p_idx] if p_idx is not None else None)
            if y:
                final_metric_names[s_idx] = f"Tahun {y}"
            else:
                clean_t = re.sub(r'^\s*tabel\s+\d+(\.\d+)*\s*', '', table_title, flags=re.IGNORECASE)
                clean_t = re.sub(r'\b(19|20)\d{2}\b', '', clean_t).strip()
                final_metric_names[s_idx] = clean_t if clean_t else f"Metrik #{s_idx + 1}"

    return col_mapping, pdf_extra_indices, col_year_tags, final_metric_names

def detect_table_archetype(pdf_path, target_table, target_pages, source_data):
    if not target_pages or len(target_pages) <= 1:
        sample_rows = source_data[:35] if isinstance(source_data, list) else []
        matched_regencies = {
            resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0]
            for r in sample_rows if isinstance(r, list)
        }
        matched_regencies.discard(None)
        matched_regencies.discard("33.00")
        matched_regencies.discard("00")
        if len(matched_regencies) >= 3:
            return "SINGLE_PAGE_REGIONAL"

        matched_prov = {
            resolve_provinsi_entity(' '.join(str(c) for c in r[:3]))[0]
            for r in sample_rows if isinstance(r, list)
        }
        matched_prov.discard(None)
        matched_prov.discard("00")
        matched_prov.discard("33")
        if len(matched_prov) >= 3:
            return "SINGLE_PAGE_PROVINCIAL"
        return "SINGLE_PAGE_CATEGORICAL"

    p1_rows = extract_clean_page_tables(pdf_path, target_pages[0])
    p2_rows = extract_clean_page_tables(pdf_path, target_pages[1]) if len(target_pages) > 1 else []

    p2_first_ints = []
    for r in p2_rows[:15]:
        if not isinstance(r, list) or not r: continue
        c0 = str(r[0]).strip().rstrip('.')
        if c0.isdigit():
            p2_first_ints.append(int(c0))
            if len(p2_first_ints) >= 3: break

    if p2_first_ints and p2_first_ints[0] > 20:
        return "MULTI_PAGE_ROW_CONTINUATION"

    p1_jateng = sum(1 for r in p1_rows if isinstance(r, list) and resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '33.00', '00'])
    p2_jateng = sum(1 for r in p2_rows if isinstance(r, list) and resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '33.00', '00'])

    if p1_jateng >= 5 and p2_jateng >= 5:
        return "MULTI_PAGE_COLUMN_SPLIT"

    return "MULTI_PAGE_SECTION_PER_PAGE"

def profile_api_schema(api_records):
    """
    Menganalisis skema record API secara generik berbasis statistik, tipe nilai,
    dan karakteristik entitas domain tanpa bergantung pada kamus nama kolom hardcoded.
    Mengembalikan dictionary: {col_name: role} di mana role adalah salah satu dari:
    'system', 'temporal', 'code', 'region', 'metric', 'categorical'.
    """
    if not api_records or not isinstance(api_records, list) or not isinstance(api_records[0], dict):
        return None

    sample = api_records[0]
    keys = list(sample.keys())

    col_roles = {}
    for col in keys:
        cl = col.lower().strip()
        # 1. System / Metadata / Unit keys
        if cl in {'id', '_id', 'created_at', 'updated_at', 'created_by', 'updated_by', 'deleted_at'}:
            col_roles[col] = 'system'
            continue
        if cl in {'satuan', 'unit', 'units'}:
            col_roles[col] = 'unit'
            continue

        # 2. Temporal dimensions (filter tahun/waktu) vs Periodic row dimension (bulan, triwulan, semester)
        if re.search(r'^(tahun|year|waktu)(_|$)', cl):
            col_roles[col] = 'temporal'
            continue
        if re.search(r'^(bulan|month|triwulan|semester)(_|$)', cl):
            col_roles[col] = 'categorical'
            continue

        # 3. Identifiers / Classification codes (cek sebelum empty agar kode_rekening kosong tetap jadi code)
        if re.search(r'^(kode|code|no|nomor)(_|$)', cl) or 'kode_' in cl:
            col_roles[col] = 'code'
            continue

        vals = [r.get(col) for r in api_records if r.get(col) is not None and str(r.get(col)).strip() != '']
        if not vals:
            col_roles[col] = 'empty'
            continue

        # 4. Regional / Geographic dimension (hanya bila benar-benar nama wilayah atau bukan kolom metrik angka)
        if any(w in cl for w in ['wilayah', 'wiayah', 'wialyah', 'kabupaten', 'kota', 'regency', 'municipality', 'daerah', 'provinsi']):
            num_ratio_chk = sum(1 for v in vals if parse_num(v, is_pdf=False) is not None) / len(vals)
            jateng_matches = sum(1 for v in set(vals) if resolve_jateng_entity(v)[0] not in [None, 'Total Jawa Tengah'])
            if jateng_matches >= 3 or (len(set(vals)) >= 25 and num_ratio_chk < 0.75):
                col_roles[col] = 'region'
                continue

        # 5. Nilai Numerik (Metric) vs Nilai Teks (Categorical)
        num_count = sum(1 for v in vals if parse_num(v, is_pdf=False) is not None)
        ratio = num_count / len(vals)
        if ratio >= 0.75:
            col_roles[col] = 'metric'
        else:
            col_roles[col] = 'categorical'

    # Pertahankan kolom all-None sebagai 'metric' jika dataset memang berisi metrik lain yang terisi
    # dan kolom tersebut bukan nama/teks/wilayah/keterangan (menjaga kolom bernilai '-' semua seperti 9.1.3/5.3.7)
    if any(r == 'metric' for r in col_roles.values()):
        for col, role in list(col_roles.items()):
            if role != 'empty':
                continue
            cl = col.lower().strip()
            if re.search(r'^(nama|uraian|ket|keterangan|catatan|alamat|sumber|status|satuan)(_|$)', cl):
                continue
            if any(w in cl for w in ['wilayah', 'wiayah', 'wialyah', 'kabupaten', 'kota', 'regency', 'municipality', 'provinsi']):
                continue
            col_roles[col] = 'metric'

    return col_roles


def _is_non_additive_metric_header(header_name):
    """True bila kolom metrik bersifat rasio/persentase/indeks/rate sehingga tidak boleh di-sum() ke baris Total."""
    h_low = str(header_name or '').lower()
    leaf = re.split(r'\s*(?:-|\|\|)\s*', h_low)[-1].strip() or h_low
    _non_add_kws = (
        'persentase', 'persen', 'pct', 'percent', 'rasio', 'ratio',
        'indeks', 'index', 'ipm', 'uhh', 'rls', 'hls', 'rata', 'average',
        'kepadatan', 'density', 'laju', 'growth', 'angka ', 'rate',
        'prevalensi', 'proporsi', 'cakupan', 'coverage',
        'per 100000', 'per 100.000', 'per 1000', 'per 1.000', 'penemuan kasus',
        'tahun sk', 'year of decree',
    )
    if any(k in leaf for k in _non_add_kws):
        return True
    return any(k in h_low for k in _non_add_kws) and not re.search(r'\b(?:jumlah|banyaknya|total)\b', leaf)


def _filter_api_records_by_target_year(records, temporal_cols, pdf_rows, pub_year=None, pdf_title=None):
    if not temporal_cols:
        return records
    t_col = temporal_cols[0]
    rec_years = sorted(list(set(str(r.get(t_col)).strip() for r in records if r.get(t_col))))
    if not rec_years:
        return records

    max_data_y = (int(pub_year) - 1) if pub_year else None
    eligible_years = [y for y in rec_years if y.isdigit() and (max_data_y is None or int(y) <= max_data_y)]
    if not eligible_years:
        if max_data_y is not None and all(y.isdigit() and int(y) > max_data_y for y in rec_years):
            return []
        eligible_years = rec_years
    if len(eligible_years) == 1:
        target_y = eligible_years[0]
        return [r for r in records if str(r.get(t_col)).strip() == target_y]

    pdf_years = set()
    if pdf_rows:
        pdf_text = " ".join(str(c) for r in pdf_rows[:20] if isinstance(r, list) for c in r if c)
        for y in re.findall(r'\b(20\d{2})\b', pdf_text):
            pdf_years.add(y)
    if not pdf_years and pdf_title:
        for y in re.findall(r'\b(20\d{2})\b', str(pdf_title)):
            pdf_years.add(y)
    common = sorted(list(set(eligible_years).intersection(pdf_years)))
    if common:
        target_y = common[-1]
        return [r for r in records if str(r.get(t_col)).strip() == target_y]
    if max_data_y is not None and str(max_data_y) in eligible_years:
        target_y = str(max_data_y)
    else:
        target_y = eligible_years[-1]
    return [r for r in records if str(r.get(t_col)).strip() == target_y]



def _expand_grid_with_config_totals(grid, config, n_label_cols=1):
    """
    Menyisipkan kolom 'Jumlah per Kelompok Kolom' (show_group_total) dan/atau
    kolom 'Jumlah' horizontal (show_total_col) sesuai algoritma portal_table_scripts.php.
    """
    if not grid or len(grid) < 2 or not isinstance(config, dict):
        return grid
    show_grp = bool(config.get('show_group_total'))
    show_tot = bool(config.get('show_total_col'))
    if not show_grp and not show_tot:
        return grid

    hdr_row = list(grid[0])
    if len(hdr_row) <= n_label_cols:
        return grid

    if show_grp:
        col_specs = [('orig', i, hdr_row[i]) for i in range(n_label_cols)]
        cur_grp_cols = []
        last_grp = None
        for c_idx in range(n_label_cols, len(hdr_row)):
            h_str = re.sub(r'\s*\|\|\s*', ' - ', str(hdr_row[c_idx] or '')).strip()
            parts = [p.strip() for p in h_str.split(' - ') if p.strip()]
            main_grp = parts[0] if parts else h_str

            if last_grp is not None and main_grp != last_grp:
                if len(cur_grp_cols) > 1 and not any(
                    re.search(r'\b(jumlah|total|subjumlah)\b', str(hdr_row[ci]).replace('||', ' - ').split(' - ')[-1].lower())
                    for ci in cur_grp_cols
                ):
                    col_specs.append(('grp_tot', list(cur_grp_cols), f"{last_grp} - Jumlah"))
                cur_grp_cols = []

            col_specs.append(('orig', c_idx, h_str))
            if not re.search(r'\btahun\b', h_str.lower()):
                cur_grp_cols.append(c_idx)
            last_grp = main_grp

        if len(cur_grp_cols) > 1 and last_grp is not None and not any(
            re.search(r'\b(jumlah|total|subjumlah)\b', str(hdr_row[ci]).replace('||', ' - ').split(' - ')[-1].lower())
            for ci in cur_grp_cols
        ):
            col_specs.append(('grp_tot', list(cur_grp_cols), f"{last_grp} - Jumlah"))

        new_grid = [[spec[2] for spec in col_specs]]
        for r in grid[1:]:
            new_r = []
            is_tot_r = str(r[0] if r else '').strip() in ('Total Jawa Tengah', 'Jumlah/Total')
            for kind, src, lbl in col_specs:
                if kind == 'orig':
                    new_r.append(r[src] if src < len(r) else None)
                else:
                    if is_tot_r and _is_non_additive_metric_header(lbl):
                        new_r.append(None)
                    else:
                        vals = [parse_num(r[ci], is_pdf=False) for ci in src if ci < len(r)]
                        num_vals = [v for v in vals if v is not None]
                        if num_vals:
                            s_val = sum(num_vals)
                            new_r.append(int(s_val) if float(s_val).is_integer() else round(s_val, 4))
                        else:
                            new_r.append('-' if any(ci < len(r) and str(r[ci]).strip() == '-' for ci in src) else 0)
            new_grid.append(new_r)
        grid = new_grid
        hdr_row = list(grid[0])

    if show_tot:
        # Di portal_table_scripts.php (baris 486), showTotal hanya aktif bila belum ada kolom 'jumlah'/'total'
        has_vis_tot = any(
            re.search(r'\b(jumlah|total)\b', str(h).lower())
            for h in hdr_row[n_label_cols:]
        )
        if not has_vis_tot:
            data_indices = list(range(n_label_cols, len(hdr_row)))
            new_grid = [hdr_row + ['Jumlah']]
            for r in grid[1:]:
                vals = [parse_num(r[ci], is_pdf=False) for ci in data_indices if ci < len(r)]
                num_vals = [v for v in vals if v is not None]
                if num_vals:
                    s_val = sum(num_vals)
                    tot_cell = int(s_val) if float(s_val).is_integer() else round(s_val, 4)
                else:
                    tot_cell = 0
                new_grid.append(list(r) + [tot_cell])
            grid = new_grid

    return grid


def apply_user_table_config(api_records, config_tabel, pdf_rows=None, target_table=None, pub_year=None, pdf_title=None):
    """
    Menerapkan konfigurasi layout (config_tabel) dari database ke raw records API Satudata.
    Mendukung kolom visibilitas kustom, reordering, custom labels, baris/kolom total,
    serta pivot mode yang disetel admin di portal tabel dinamis.
    """
    if not api_records or not isinstance(api_records, list) or not isinstance(api_records[0], dict):
        return None
    if not config_tabel:
        return None

    config = config_tabel
    if isinstance(config, str):
        try:
            c_str = config.strip()
            if c_str.startswith('"') and c_str.endswith('"'):
                c_str = c_str[1:-1]
            config = json.loads(c_str)
            if isinstance(config, str):
                config = json.loads(config)
        except Exception:
            return None

    if not isinstance(config, dict):
        return None

    cfg_cols = config.get('columns', [])
    if not cfg_cols or not isinstance(cfg_cols, list):
        return None

    api_keys = set(api_records[0].keys())
    cfg_keys = {c.get('key') for c in cfg_cols if isinstance(c, dict) and c.get('key')}
    overlap = api_keys.intersection(cfg_keys)
    if len(overlap) < min(2, len(api_keys)):
        # Config basi/lama yang tidak cocok dengan skema API baru -> fallback
        return None

    pivot = config.get('pivot', {})
    # Filter target year jika dataset multi-tahun; jangan ciutkan ke 1 tahun jika tahun menjadi sumbu baris/kolom pivot (mis. 9.1.19)
    temporal_keys = [k for k in api_keys if any(t in k.lower() for t in ['tahun', 'year', 'periode'])]
    if temporal_keys:
        pivot_axes = set(list(pivot.get('row', []) or []) + list(pivot.get('col', []) or []))
        if pivot.get('enabled') and any(tk in pivot_axes for tk in temporal_keys):
            if pub_year:
                max_dy = int(pub_year) - 1
                tk0 = temporal_keys[0]
                api_records = [
                    r for r in api_records
                    if not (str(r.get(tk0, '')).strip().isdigit() and int(str(r.get(tk0, '')).strip()) > max_dy)
                ]
        else:
            api_records = _filter_api_records_by_target_year(api_records, temporal_keys, pdf_rows, pub_year=pub_year, pdf_title=pdf_title)
    if not api_records:
        return None
    if pivot.get('enabled'):
        # Jika di PDF kolomnya adalah deret multi-tahun (>= 3 tahun, misal 2020..2025 pada 10.3.21)
        # sedangkan di API pivot-nya memutar indikator ke kolom, serahkan ke normalizer bertingkat
        if pdf_rows:
            hdr_txt = " ".join(str(c) for r in pdf_rows[:5] if isinstance(r, list) for c in r if c)
            hdr_years = set(re.findall(r'\b(20\d{2})\b', hdr_txt))
            if len(hdr_years) >= 3:
                return None

        row_keys = pivot.get('row', [])
        col_keys = pivot.get('col', [])
        val_keys = pivot.get('val', [])
        # Validasi ketat bahwa seluruh key pivot benar-benar ada di skema API saat ini (cegah config basi)
        required_pivot_keys = [k for k in (list(row_keys) + list(col_keys) + list(val_keys)) if k]
        if col_keys and val_keys and all(k in api_keys for k in required_pivot_keys):
            mappings = pivot.get('mappings', {})
            mapping_order = pivot.get('mapping_order', [])

            def gen_key(r, keys):
                return " || ".join(str(r.get(k, '')).strip() for k in keys)

            def _canon_col_key(val):
                return re.sub(r'\s*-\s*', '-', re.sub(r'\s+', ' ', str(val or '').strip())).lower()

            cats = []
            canon_to_cat = {}
            for r in api_records:
                c = gen_key(r, col_keys)
                ck = _canon_col_key(c)
                if ck and ck not in canon_to_cat:
                    canon_to_cat[ck] = c
                    cats.append(c)
                elif ck and '-' in c and ' - ' not in c and ' - ' in canon_to_cat[ck]:
                    old_c = canon_to_cat[ck]
                    canon_to_cat[ck] = c
                    cats = [c if x == old_c else x for x in cats]

            if mapping_order:
                def sort_c(cat):
                    if cat in mapping_order:
                        return mapping_order.index(cat)
                    ck = _canon_col_key(cat)
                    for idx_m, m_val in enumerate(mapping_order):
                        if _canon_col_key(m_val) == ck:
                            return idx_m
                    return 999
                cats.sort(key=sort_c)

            entities = []
            for r in api_records:
                ent = gen_key(r, row_keys) if row_keys else 'All'
                if ent not in entities:
                    entities.append(ent)

            if row_keys and (not any(entities) or (len(entities) <= 1 and len(api_records) > 2)):
                return None

            headers = [mappings.get(rk, {}).get('label_id', rk) for rk in row_keys] if row_keys else ['Uraian']
            for c in cats:
                cat_lbl = mappings.get(c, {}).get('label_id', c)
                for vk in val_keys:
                    val_lbl = mappings.get(vk, {}).get('label_id', vk)
                    hdr = f"{cat_lbl} - {val_lbl}" if len(val_keys) > 1 else cat_lbl
                    headers.append(hdr)
            if pivot.get('total_row', True):
                headers.append('Jumlah')

            grid = [headers]
            num_data_cols = len(cats) * len(val_keys)
            col_sums = [0.0] * num_data_cols

            for ent in entities:
                ent_recs = [r for r in api_records if (gen_key(r, row_keys) == ent if row_keys else True)]
                row = [ent]
                r_sum = 0.0
                v_idx = 0
                for c in cats:
                    ck = _canon_col_key(c)
                    c_recs = [r for r in ent_recs if _canon_col_key(gen_key(r, col_keys)) == ck]
                    for vk in val_keys:
                        v = sum(parse_num(r.get(vk), is_pdf=False) or 0.0 for r in c_recs)
                        row.append(int(v) if v.is_integer() else v)
                        r_sum += v
                        col_sums[v_idx] += v
                        v_idx += 1
                if pivot.get('total_row', True):
                    row.append(int(r_sum) if r_sum.is_integer() else r_sum)
                grid.append(row)

            if pivot.get('total_col', True):
                tot_row = ['Jumlah/Total'] if len(row_keys) <= 1 else ['Jumlah/Total'] + [''] * (len(row_keys) - 1)
                for cs in col_sums:
                    tot_row.append(int(cs) if cs.is_integer() else cs)
                if pivot.get('total_row', True):
                    grand_tot = sum(col_sums)
                    tot_row.append(int(grand_tot) if grand_tot.is_integer() else grand_tot)
                grid.append(tot_row)

            return _expand_grid_with_config_totals(grid, config, n_label_cols=max(1, len(row_keys)))

    # Jika pivot tidak diaktifkan (masih format mentah default API), serahkan ke smart schema profiler
    return None

def normalize_api_data(api_records, pdf_rows=None, target_table=None, config_tabel=None, pub_year=None, pdf_title=None):
    """
    Mengonversi list of dict dari API Satudata menjadi representasi 2D tabular generik
    yang kompatibel dengan matching engine compare_table.py (CASE 2).
    Mendukung 5 layout generik:
      1. Regional (35 Kab/Kota)
      2. Multi-Dimensional Cross-Tabulation Pivot (misal Partai Politik x Gender / Umur)
      3. Multi-Year Time Series Pivot (misal Belanja Daerah x Multi-Tahun)
      4. Flat Multi-Metric Categorical (misal PMA Menurut Negara, ASN per Dinas)
      5. Temporal Summary Rows (misal Wisatawan per Tahun)
    """
    if not isinstance(api_records, list) or not api_records or not isinstance(api_records[0], dict):
        return None

    # Prioritaskan layout config_tabel yang disetel admin di portal
    if config_tabel:
        user_grid = apply_user_table_config(api_records, config_tabel, pdf_rows=pdf_rows, target_table=target_table, pub_year=pub_year, pdf_title=pdf_title)
        if user_grid and len(user_grid) > 1:
            return user_grid

    roles = profile_api_schema(api_records)
    if not roles:
        return None

    region_cols = [c for c, r in roles.items() if r == 'region']
    code_cols = [c for c, r in roles.items() if r == 'code']
    temporal_cols = [c for c, r in roles.items() if r == 'temporal']
    cat_cols = [c for c, r in roles.items() if r == 'categorical']
    metric_cols = [c for c, r in roles.items() if r == 'metric']

    parsed_cfg = None
    cfg_label_map = {}
    if config_tabel:
        try:
            c_raw = config_tabel.strip() if isinstance(config_tabel, str) else config_tabel
            if isinstance(c_raw, str):
                if c_raw.startswith('"') and c_raw.endswith('"'):
                    c_raw = c_raw[1:-1]
                c_raw = json.loads(c_raw)
                if isinstance(c_raw, str):
                    c_raw = json.loads(c_raw)
            if isinstance(c_raw, dict):
                c_cols = c_raw.get('columns', [])
                api_k = set(api_records[0].keys())
                cfg_k = {c.get('key') for c in c_cols if isinstance(c, dict) and c.get('key')}
                if len(api_k.intersection(cfg_k)) >= min(2, len(api_k)):
                    parsed_cfg = c_raw
                    p_cfg_chk = parsed_cfg.get('pivot', {}) or {}
                    is_pivot_ready = bool(
                        p_cfg_chk.get('enabled')
                        and p_cfg_chk.get('col')
                        and p_cfg_chk.get('val')
                        and (p_cfg_chk.get('metrics_as_row') or p_cfg_chk.get('row'))
                    )
                    if not is_pivot_ready:
                        vis_m = []
                        has_populated_metric = any(r == 'metric' for r in roles.values())
                        for c in c_cols:
                            if not isinstance(c, dict) or not c.get('key'):
                                continue
                            k = c['key']
                            if k not in api_k:
                                continue
                            lbl = re.sub(r'\s*\|\|\s*', ' - ', str(c.get('label_id') or k)).strip()
                            if lbl and lbl.lower() != k.lower():
                                cfg_label_map[k] = lbl
                            if c.get('visible', True):
                                k_role = roles.get(k)
                                if k_role == 'metric' or (k_role == 'empty' and has_populated_metric and c.get('format', 'number') in ('number', 'decimal', 'percent', 'currency', 'ribuan', 'jutaan')):
                                    vis_m.append(k)
                        if vis_m:
                            metric_cols = vis_m
        except Exception:
            parsed_cfg = None

    # 1. LAYOUT REGIONAL (35 Kab/Kota)
    if region_cols:
        api_records = _filter_api_records_by_target_year(api_records, temporal_cols, pdf_rows, pub_year=pub_year, pdf_title=pdf_title)
        reg_col = region_cols[0]
        # Jika ada kolom kategori breakdown juga di tabel wilayah (misal jenis_ppks per kab/kota)
        if cat_cols:
            cat_col = cat_cols[0]
            pdf_text = ""
            if pdf_rows:
                pdf_text = " ".join(str(c) for r in pdf_rows[:25] if isinstance(r, list) for c in r if c).lower()
            pdf_has_regions = sum(1 for reg in ['cilacap', 'banyumas', 'purbalingga', 'semarang', 'surakarta'] if reg in pdf_text) >= 2

            if not pdf_has_regions and metric_cols:
                # PDF adalah rekap provinsi per kategori: agregasikan nilai metrik per kategori
                categories = []
                for r in api_records:
                    c_val = str(r.get(cat_col, '')).strip()
                    if c_val and c_val not in categories:
                        categories.append(c_val)
                grid = [[cat_col.replace('_', ' ').title()] + [cfg_label_map.get(m, m.replace('_', ' ').title()) for m in metric_cols]]
                for c_val in categories:
                    row = [c_val]
                    for mk in metric_cols:
                        val = sum(parse_num(r.get(mk), is_pdf=False) or 0.0 for r in api_records if str(r.get(cat_col, '')).strip() == c_val)
                        row.append(int(val) if val.is_integer() else val)
                    grid.append(row)
                return _expand_grid_with_config_totals(grid, parsed_cfg, n_label_cols=1)

        # Standar Regional: Baris = 35 Kab/Kota (atau Kab/Kota x Sub-Kategori), Kolom = Metrik numerik
        is_multi_row_reg_cat = False
        any_valid_metric = False
        if cat_cols and pdf_rows:
            unique_regs = len(set(str(r.get(reg_col, '')).strip() for r in api_records if r.get(reg_col)))
            cat_vals_set = set(str(r.get(cat_cols[0], '')).strip().lower() for r in api_records if r.get(cat_cols[0]))
            cat_in_pdf = sum(1 for v in cat_vals_set if len(v) >= 3 and v in pdf_text) >= 2
            if pdf_has_regions and cat_in_pdf and len(api_records) > max(40, unique_regs * 1.3):
                is_multi_row_reg_cat = True
                any_valid_metric = any(parse_num(r.get(mk), is_pdf=False) is not None for r in api_records for mk in metric_cols)

        headers = ['Wilayah'] + [cfg_label_map.get(m, m.replace('_', ' ').title()) for m in metric_cols]
        grid = [headers]
        has_explicit_kota = any(
            str(r.get(reg_col, '')).strip().lower().startswith(('kota ', 'kota.'))
            for r in api_records
        )
        cilacap_seen = False
        brebes_seen = False
        for r in api_records:
            w_name = str(r.get(reg_col, '')).strip()
            if 'Cilacap' in w_name:
                cilacap_seen = True
            if 'Brebes' in w_name:
                brebes_seen = True
            elif (not has_explicit_kota) and cilacap_seen and brebes_seen and w_name.startswith('Kab. '):
                c_name = w_name[5:].strip()
                if c_name in ['Magelang', 'Semarang', 'Pekalongan', 'Tegal']:
                    w_name = f"Kota {c_name}"
            if is_multi_row_reg_cat:
                c_val = str(r.get(cat_cols[0], '')).strip()
                w_clean = re.sub(r'^Kab\.\s*', '', w_name)
                row_lbl = f"{w_clean} - {c_val}" if c_val else w_name
            else:
                row_lbl = w_name
            row = [row_lbl]
            for mk in metric_cols:
                v = parse_num(r.get(mk), is_pdf=False)
                if v is not None:
                    row.append(int(v) if isinstance(v, float) and v.is_integer() else v)
                elif is_multi_row_reg_cat and any_valid_metric:
                    row.append('-')
                else:
                    row.append(r.get(mk))
            grid.append(row)

        has_pdf_total = False
        if pdf_rows:
            for r in pdf_rows:
                if isinstance(r, list) and r:
                    r_txt = " ".join(str(c).lower() for c in r[:3] if c)
                    if any(k in r_txt for k in ['jawa tengah', 'jumlah', 'total']) and not any(k in r_txt for k in ['kabupaten', 'kota', 'dinas']):
                        has_pdf_total = True
                        break

        cfg_wants_total = False
        if config_tabel:
            try:
                cfg_obj = json.loads(config_tabel) if isinstance(config_tabel, str) else config_tabel
                p_cfg = cfg_obj.get('pivot', {}) if isinstance(cfg_obj, dict) else {}
                cfg_wants_total = bool(p_cfg.get('total_col') or p_cfg.get('total_row') or cfg_obj.get('show_total_col') or cfg_obj.get('total_vertikal'))
            except Exception:
                pass

        if (has_pdf_total or cfg_wants_total) and len(grid) > 1 and not is_multi_row_reg_cat:
            tot_row = ['Total Jawa Tengah']
            for col_idx in range(1, len(headers)):
                if _is_non_additive_metric_header(headers[col_idx]):
                    tot_row.append(None)
                else:
                    col_sum = sum(parse_num(row[col_idx], is_pdf=False) or 0.0 for row in grid[1:] if len(row) > col_idx)
                    tot_row.append(int(col_sum) if col_sum.is_integer() else col_sum)
            grid.append(tot_row)

        return _expand_grid_with_config_totals(grid, parsed_cfg, n_label_cols=1)

    # 2. CROSS-TABULATION / HIERARCHICAL BREAKDOWN PIVOT (misal 2.2.1 Partai Politik x Gender, 2.2.5 x Umur)
    if len(cat_cols) >= 2 and metric_cols:
        api_records = _filter_api_records_by_target_year(api_records, temporal_cols, pdf_rows, pub_year=pub_year, pdf_title=pdf_title)
        val_col = metric_cols[0]
        # Entitas utama (baris) adalah kategori dengan kardinalitas tertinggi (misal nama partai ~18)
        cat_cardinality = {c: len(set(str(r.get(c, '')).strip() for r in api_records if r.get(c))) for c in cat_cols}
        sorted_cats = sorted(cat_cols, key=lambda c: cat_cardinality[c], reverse=True)
        primary_dim = sorted_cats[0]
        sub_candidates = sorted_cats[1:]

        # Tentukan sub-dimensi kolom dengan mencocokkan nama / nilai terhadap teks atau header PDF
        pdf_text = ""
        if pdf_rows:
            pdf_text = " ".join(str(c) for r in pdf_rows[:15] if isinstance(r, list) for c in r if c).lower()

        chosen_sub = sub_candidates[0]
        for cand in sub_candidates:
            cand_vals = set(str(r.get(cand, '')).strip().lower() for r in api_records if r.get(cand))
            name_match = any(word in pdf_text for word in cand.lower().split('_') if len(word) >= 3)
            val_match = any(v in pdf_text for v in cand_vals if len(v) >= 2)
            if name_match or val_match:
                chosen_sub = cand
                break

        # Bentuk pivot table
        primary_entities = []
        for r in api_records:
            p_val = str(r.get(primary_dim, '')).strip()
            if p_val and p_val not in primary_entities:
                primary_entities.append(p_val)

        sub_vals_raw = []
        for r in api_records:
            s_val = str(r.get(chosen_sub, '')).strip()
            if s_val and s_val not in sub_vals_raw:
                sub_vals_raw.append(s_val)

        # Deteksi jika PDF menyajikan kolom multi-tahun (>= 3 tahun, misal 2020..2025 pada 10.3.21)
        # dan nilai sub-indikator ditumpuk secara vertikal sebagai baris di bawah tiap kategori utama
        if pdf_rows:
            hdr_txt = " ".join(str(c) for r in pdf_rows[:5] if isinstance(r, list) for c in r if c)
            hdr_years = sorted(list(set(re.findall(r'\b(20\d{2})\b', hdr_txt))))
            if len(hdr_years) >= 3:
                time_col = temporal_cols[0] if temporal_cols else None
                api_years = sorted(list(set(str(r.get(time_col)).strip() for r in api_records if time_col and r.get(time_col))))
                if not api_years:
                    api_years = [hdr_years[-1]]
                grid = [[primary_dim.replace('_', ' ').title()] + [f"Tahun {y}" for y in api_years]]
                for p in primary_entities:
                    for sv in sub_vals_raw:
                        row = [f"{p} - {sv}"]
                        for y in api_years:
                            v = sum(
                                parse_num(r.get(val_col), is_pdf=False) or 0.0
                                for r in api_records
                                if str(r.get(primary_dim, '')).strip() == p
                                and str(r.get(chosen_sub, '')).strip() == sv
                                and (not time_col or str(r.get(time_col, '')).strip() == y)
                            )
                            row.append(int(v) if v.is_integer() else v)
                        grid.append(row)
                return grid

        # Pisahkan sub-kategori yang sudah bernilai 'Jumlah' / 'Total'
        sub_vals_clean = []
        for s in sub_vals_raw:
            sl = s.lower().strip()
            if sl in ['jumlah', 'total', 'jumlah / total', 'all', 'semua']:
                continue
            sub_vals_clean.append(s)

        # Urutkan sub_vals secara kanonik (Gender: L lalu P, Umur: numerik naik)
        def sort_sub_val(val_str):
            vl = val_str.lower().strip()
            if vl in ['l', 'laki-laki', 'laki - laki', 'pria', 'male']:
                return (0, 0)
            if vl in ['p', 'perempuan', 'wanita', 'female']:
                return (0, 1)
            m_num = re.search(r'\d+', val_str)
            if m_num:
                return (1, int(m_num.group(0)))
            return (2, val_str)

        sub_vals = sorted(sub_vals_clean, key=sort_sub_val)

        def format_col_header(s):
            sl = s.lower().strip()
            if sl == 'l': return 'Laki-Laki'
            if sl == 'p': return 'Perempuan'
            return s

        col_headers = [format_col_header(s) for s in sub_vals]
        grid = [[primary_dim.replace('_', ' ').title()] + col_headers + ['Jumlah']]

        for p in primary_entities:
            row = [p]
            row_sum = 0.0
            for sv in sub_vals:
                v = sum(parse_num(r.get(val_col), is_pdf=False) or 0.0 for r in api_records if str(r.get(primary_dim, '')).strip() == p and str(r.get(chosen_sub, '')).strip() == sv)
                row.append(int(v) if v.is_integer() else v)
                row_sum += v
            row.append(int(row_sum) if row_sum.is_integer() else row_sum)
            grid.append(row)

        # Baris Rekapitulasi Total Provinsi (Jawa Tengah) jika belum ada di primary_entities
        has_total = any(resolve_jateng_entity(p)[0] == '33.00' or p.lower().strip() in ['jawa tengah', 'total', 'jumlah'] for p in primary_entities)
        if not has_total:
            total_row = ['Jawa Tengah']
            grand_total = 0.0
            for sv in sub_vals:
                col_sum = sum(parse_num(r.get(val_col), is_pdf=False) or 0.0 for r in api_records if str(r.get(chosen_sub, '')).strip() == sv)
                total_row.append(int(col_sum) if col_sum.is_integer() else col_sum)
                grand_total += col_sum
            total_row.append(int(grand_total) if grand_total.is_integer() else grand_total)
            grid.append(total_row)
        return grid

    # 3. TIME-SERIES / MULTI-YEAR PIVOT (misal 2.4.2 Belanja Daerah x Multi-Tahun, 9.1.19 Penumpang Trans Jateng)
    if len(cat_cols) == 1 and temporal_cols and len(metric_cols) == 1:
        dim_col = cat_cols[0]
        code_col = code_cols[0] if code_cols else None
        time_col = temporal_cols[0]
        val_col = metric_cols[0]

        years = sorted(list(set(str(r.get(time_col)).strip() for r in api_records if r.get(time_col))))
        if pub_year:
            max_dy = int(pub_year) - 1
            elig_years = [y for y in years if y.isdigit() and int(y) <= max_dy]
            if elig_years:
                years = elig_years
        col_headers = [f"Tahun {y}" for y in years]

        # Cek apakah di PDF baris datanya berupa deret tahun (Transpose Mode: Baris = Tahun, Kolom = Kategori)
        pdf_row_is_year = False
        if pdf_rows:
            sample_data_rows = [r for r in pdf_rows[:20] if isinstance(r, list) and not is_title_row(r) and not is_header_or_metadata_row(r)]
            year_matches = sum(1 for r in sample_data_rows if any(re.match(r'^(?:19|20)\d{2}$', str(c).strip().rstrip('.')) for c in r[:3]))
            if year_matches >= 2:
                pdf_row_is_year = True

        if pdf_row_is_year:
            # Layout Transpose: Baris = Tahun, Kolom = Kategori (misal Penumpang Trans Jateng 9.1.19)
            categories = []
            for r in api_records:
                c_name = str(r.get(dim_col, '')).strip()
                if c_name and c_name not in categories:
                    categories.append(c_name)
            grid = [['Tahun Anggaran'] + [c.replace('_', ' ').title() for c in categories]]
            for y in years:
                row = [y]
                for c_name in categories:
                    v = sum(parse_num(r.get(val_col), is_pdf=False) or 0.0 for r in api_records if str(r.get(time_col)) == y and str(r.get(dim_col, '')).strip() == c_name)
                    row.append(int(v) if v.is_integer() else v)
                grid.append(row)
            return grid

        if code_col:
            grid = [['Kode Rekening', dim_col.replace('_', ' ').title()] + col_headers]
            categories = []
            for r in api_records:
                item_id = (str(r.get(code_col, '')).strip(), str(r.get(dim_col, '')).strip())
                if item_id not in categories and (item_id[0] or item_id[1]):
                    categories.append(item_id)
            for c_code, c_name in categories:
                row = [c_code, c_name]
                for y in years:
                    v = sum(parse_num(r.get(val_col), is_pdf=False) or 0.0 for r in api_records if str(r.get(time_col)) == y and str(r.get(code_col, '')).strip() == c_code and str(r.get(dim_col, '')).strip() == c_name)
                    row.append(int(v) if v.is_integer() else v)
                grid.append(row)
        else:
            grid = [[dim_col.replace('_', ' ').title()] + col_headers]
            categories = []
            for r in api_records:
                c_name = str(r.get(dim_col, '')).strip()
                if c_name and c_name not in categories:
                    categories.append(c_name)
            for c_name in categories:
                row = [c_name]
                for y in years:
                    v = sum(parse_num(r.get(val_col), is_pdf=False) or 0.0 for r in api_records if str(r.get(time_col)) == y and str(r.get(dim_col, '')).strip() == c_name)
                    row.append(int(v) if v.is_integer() else v)
                grid.append(row)
        return grid

    # 4. FLAT MULTI-METRIC CATEGORICAL (misal 10.1.5 PMA Menurut Negara, 2.3.22 ASN per Dinas, 2.3.9 PNS Golongan)
    if len(cat_cols) == 1 and len(metric_cols) >= 1:
        if temporal_cols:
            api_records = _filter_api_records_by_target_year(api_records, temporal_cols, pdf_rows, pub_year=pub_year, pdf_title=pdf_title)
        dim_col = cat_cols[0]
        code_col = code_cols[0] if code_cols else None

        def clean_metric_label(m):
            ml = m.lower()
            if re.search(r'(?:^|_|\b)(?:laki[\s\-_]?laki|lakilaki|male)(?:$|_|\b)', ml):
                return 'Laki-Laki'
            if re.search(r'(?:^|_|\b)(?:perempuan|female)(?:$|_|\b)', ml):
                return 'Perempuan'
            if ml in ['jumlah_pns', 'jumlah_asn', 'jumlah_total', 'total', 'jumlah']:
                return 'Jumlah'
            return m.replace('_', ' ').title()

        clean_metrics = [cfg_label_map.get(m, clean_metric_label(m)) for m in metric_cols]
        pdf_text = " ".join(str(c) for r in (pdf_rows or [])[:20] if isinstance(r, list) for c in r if c).lower()
        dim_in_pdf = any(str(r.get(dim_col, '')).strip().lower() in pdf_text for r in api_records[:5] if r.get(dim_col))
        use_code_only = bool(code_col and not dim_in_pdf)

        n_lbl_c = (1 if code_col else 0) + (0 if use_code_only else 1)
        grid_headers = ([code_col.replace('_', ' ').title()] if code_col else []) + ([] if use_code_only else [dim_col.replace('_', ' ').title()]) + clean_metrics
        grid = [grid_headers]
        for r in api_records:
            row = ([r.get(code_col)] if code_col else []) + ([] if use_code_only else [r.get(dim_col)])
            for mk in metric_cols:
                v = parse_num(r.get(mk), is_pdf=False)
                row.append(int(v) if isinstance(v, float) and v.is_integer() else (v if v is not None else r.get(mk)))
            grid.append(row)

        pdf_has_total = any(isinstance(r, list) and any(re.sub(r'[\s/]+', ' ', str(c).strip().lower()) in ['jumlah total', 'total jumlah', 'jumlah', 'total'] for c in r[:2] if c is not None) for r in (pdf_rows or []))
        pdf_has_prov_total = (
            not pdf_has_total
            and bool(pdf_rows)
            and any(
                isinstance(r, list) and any(
                    re.match(r'^(?:provinsi\s+)?jawa\s+tengah$', re.sub(r'\s+', ' ', str(c).strip()), re.I)
                    for c in r[:2] if c is not None
                )
                for r in pdf_rows[-5:]
            )
            and not any('jawa tengah' in str(r.get(dim_col, '')).lower() for r in api_records)
        )
        if pdf_has_total or pdf_has_prov_total:
            tot_label = 'Jawa Tengah' if pdf_has_prov_total else 'Jumlah/Total'
            tot_row = [tot_label] + ([''] * (n_lbl_c - 1) if n_lbl_c > 1 else [])
            for col_i in range(n_lbl_c, len(grid_headers)):
                if _is_non_additive_metric_header(grid_headers[col_i]):
                    tot_row.append(None)
                else:
                    vals = [r[col_i] for r in grid[1:] if isinstance(r[col_i], (int, float))]
                    tot_row.append(int(sum(vals)) if sum(vals).is_integer() else sum(vals))
            grid.append(tot_row)
        return _expand_grid_with_config_totals(grid, parsed_cfg, n_label_cols=max(1, n_lbl_c))

    # 5. TEMPORAL ROWS (misal 8.7 Wisatawan Mancanegara & Domestik per Tahun)
    if temporal_cols and not cat_cols and not region_cols:
        time_col = temporal_cols[0]
        if pub_year:
            max_dy = int(pub_year) - 1
            elig_recs = [r for r in api_records if str(r.get(time_col, '')).strip().isdigit() and int(str(r.get(time_col)).strip()) <= max_dy]
            if elig_recs:
                api_records = elig_recs
        hdr = ['Tahun Anggaran'] + [cfg_label_map.get(m, m.replace('_', ' ').title()) for m in metric_cols]
        grid = [hdr]
        try:
            sorted_records = sorted(api_records, key=lambda x: int(x.get(time_col, 0)))
        except Exception:
            sorted_records = api_records
        for r in sorted_records:
            row = [str(r.get(time_col, ''))]
            for mk in metric_cols:
                v = parse_num(r.get(mk), is_pdf=False)
                row.append(int(v) if isinstance(v, float) and v.is_integer() else (v if v is not None else r.get(mk)))
            grid.append(row)
        return _expand_grid_with_config_totals(grid, parsed_cfg, n_label_cols=1)

    return None

