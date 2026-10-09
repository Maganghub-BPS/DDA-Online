#Untuk menentukan nomor baris untuk kolom 
import re
from normalizer import (
    parse_num, clean_header_text, find_wilayah_with_type,
    is_bps_col_num, is_title_row, resolve_jateng_entity,
    resolve_provinsi_entity, is_header_or_metadata_row
)
from pdf_extractor import extract_table_content

def find_bps_numbering_row(source_data):
    if not source_data or not isinstance(source_data, list): return -1, [], 'none'
    year_idx, year_cells = -1, []
    best_candidate = None
    best_score = -1

    for i, r in enumerate(source_data[:25]):
        if not isinstance(r, list): continue
        non_empty = [(idx, str(c).strip()) for idx, c in enumerate(r) if c is not None and str(c).strip() and str(c).strip().lower() != 'none']
        if len(non_empty) < 2: continue
        
        all_bps = all((re.match(r'^\(?(\d+)\)?$', v) and int(re.match(r'^\(?(\d+)\)?$', v).group(1)) <= 150) for _, v in non_empty)
        if all_bps:
            has_parens = any(re.match(r'^\(\d+\)$', v) for _, v in non_empty)
            all_parens = all(re.match(r'^\(\d+\)$', v) for _, v in non_empty)
            starts_one = (int(re.match(r'^\(?(\d+)\)?$', non_empty[0][1]).group(1)) == 1 and non_empty[0][0] <= 1)
            starts_early = (non_empty[0][0] <= 1)
            nums = [int(re.match(r'^\(?(\d+)\)?$', v).group(1)) for _, v in non_empty]
            is_seq = (nums == list(range(nums[0], nums[0] + len(nums))))
            score = (100 if all_parens else (50 if has_parens else 0)) + \
                    (50 if (starts_one and is_seq) else 0) + \
                    (30 if starts_early else 0) + \
                    (20 if is_seq else 0) + \
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
                    num_count = sum(1 for v in non_meta if parse_num(v) is not None)
                    is_mostly_numeric = (num_count >= 1 and num_count >= len(non_meta) * 0.3) if non_meta else False
                    
                    if num_val >= 2:
                        if not is_row_num and is_mostly_numeric:
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
                
    if len(data_rows) < 3: return []
        
    max_c = max(len(r) for r in data_rows)
    data_cols = []
    for c_idx in range(max_c):
        vals = [str(r[c_idx]).strip() if c_idx < len(r) else '' for r in data_rows]
        non_empty_vals = [v for v in vals if v]
        
        if not non_empty_vals or len(non_empty_vals) < len(data_rows) * 0.3: continue
        
        clean_ints = [int(v.rstrip('.')) for v in non_empty_vals if v.rstrip('.').isdigit()]
        if len(clean_ints) >= len(data_rows) * 0.8 and all(1 <= x <= 40 for x in clean_ints): continue
        if c_idx <= 2 and non_empty_vals and all(re.match(r'^33\.\d{2}$', v) for v in non_empty_vals): continue
        if sum(1 for n in [parse_num(v) for v in non_empty_vals] if n is not None) >= len(non_empty_vals) * 0.5:
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
        
        limit_hdr = num_row_idx + 1 if row_type == 'year' else (num_row_idx if num_row_idx != -1 else first_data_idx)
        header_rows = [r for r in source_data[:limit_hdr] if isinstance(r, list) and not is_title_row(r)]
        
        # Horizontal forward fill for merged header cells
        max_c = max((len(r) for r in header_rows if isinstance(r, list)), default=0)
        filled_header_rows = []
        for r in header_rows:
            r_padded = [clean_header_text(c) for c in r] + [''] * max(0, max_c - len(r))
            f_row = []
            cur = ""
            for idx, cell in enumerate(r_padded):
                if idx <= 1 and (any(k in cell.lower() for k in ['kabupaten', 'kota', 'regency', 'nama', 'wilayah']) or cell.lower() in ['no', 'kode', '']):
                    cur = ""
                    f_row.append(cell)
                elif cell:
                    cur = cell
                    f_row.append(cell)
                else:
                    if cur and not re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', cur) and not is_bps_col_num(cur):
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
            if len([parse_num(c) for c in r if parse_num(c) is not None]) >= 2:
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
                    if cur and not re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', cur) and not is_bps_col_num(cur):
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
        while start_idx < len(data_headers) and any(n == k or n.startswith(k + ' ') or n.endswith(' ' + k) or n.startswith(k + '/') for k in ['no', 'nomor', 'kode', 'kode wilayah', 'nama kabupaten', 'nama kota', 'kabupaten/kota', 'regency/municipality', 'nama kabupaten / kota', 'nama daerah', 'wilayah'] for n in [str(data_headers[start_idx]).lower().strip()]):
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
            p_info = [{'nomor_tabel': target_table, 'pages': [p_num]}]
            extract_table_content(pdf_path, p_info)
            p_rows = p_info[0].get('extracted_data', [])
            if not p_rows: continue
            
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
            if d_start <= 0: continue
            hdr_rows = [r for r in p_rows[:d_start] if isinstance(r, list) and not is_title_row(r)]
            if not hdr_rows: continue
            
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
            
            max_c = max(len(r) for r in hdr_rows)
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
                        f_row.append(cur if cur and not re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', cur) and not is_bps_col_num(cur) else "")
                filled_hdr.append(f_row)
                
            for c_idx in range(first_data_col, max_c):
                parts = []
                for r in filled_hdr:
                    if c_idx < len(r):
                        val = r[c_idx].strip()
                        if val and not is_bps_col_num(val) and val not in parts:
                            parts.append(val)
                lbl = ' - '.join(parts) if parts else ""
                if lbl:
                    all_headers.append(f"Tahun {lbl}" if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', lbl) else lbl)
        if all_headers and (not expected_p_len or len(all_headers) == expected_p_len):
            return all_headers

    if not rows or not isinstance(rows, list): return []
    bps_row_idx, bps_cells, row_type = find_bps_numbering_row(rows)
    if bps_row_idx >= 0 and bps_cells:
        data_cols = [col_idx for col_idx, val in bps_cells if int(re.match(r'^\(?(\d+)\)?$', str(val).strip()).group(1)) >= 2]
        if expected_p_len and len(data_cols) == expected_p_len:
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
                        if cur and not re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', cur) and not is_bps_col_num(cur):
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

    col_headers = []
    for c_idx in range(num_to_extract):
        parts = []
        for r in filled_hdr:
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
        m = re.findall(r'\b(19\d{2}|20\d{2}(?:/\d{4,6})?)\b', str(text))
        if m: return m[-1]
        m_glued = re.findall(r'\b(19\d{2}|20\d{2})[\d\*\^rR\†\‡]', str(text))
        if m_glued: return m_glued[-1]
        return None

    def extract_tokens(text):
        if not text: return set()
        cleaned = re.sub(r'[\(\)\[\]\{\}\.,:;/\-–—\*\d]', ' ', str(text).lower())
        stop = {'tahun', 'year', 'kolom', 'data', 'dan', 'di', 'ke', 'dari', 'yang', 'untuk', 'kabupaten', 'kota'}
        raw_toks = {w for w in cleaned.split() if len(w) >= 3 and w not in stop}
        norm_toks = set(raw_toks)
        for t in raw_toks:
            if any(k in t for k in ['guru', 'pendidik', 'teacher', 'headmaster']):
                norm_toks.add('guru')
            elif any(k in t for k in ['murid', 'siswa', 'peserta', 'didik', 'pupil', 'student']):
                norm_toks.add('murid')
            elif any(k in t for k in ['sekolah', 'school', 'satuan']):
                norm_toks.add('sekolah')
        return norm_toks

    src_years = [extract_year(h) for h in src_headers]
    pdf_years = [extract_year(h) for h in pdf_headers]
    
    src_tokens = [extract_tokens(h) for h in src_headers]
    pdf_tokens = [extract_tokens(h) for h in pdf_headers]

    common_w = [w for w in src_dict if w in pdf_dict and w not in ['Total Jawa Tengah', 'Provinsi Jawa Tengah']]

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
                        total_cnt += 1
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
                    score -= 80.0
            elif target_year and py:
                if target_year == py:
                    score += 40.0
                else:
                    score -= 20.0

            # 3. SEMANTIC TOKEN MATCHING
            st = src_tokens[s_idx]
            pt = pdf_tokens[p_idx]
            if st and pt:
                overlap = len(st & pt)
                union = len(st | pt)
                if union > 0:
                    score += (overlap / union) * 40.0
                    
                for cat_a, cat_b in [
                    ('laki', 'perempuan'), ('negeri', 'swasta'), ('pns', 'pppk'), ('darat', 'laut'),
                    ('guru', 'murid'), ('sekolah', 'guru'), ('sekolah', 'murid')
                ]:
                    has_a_src = any(cat_a in w for w in st)
                    has_b_src = any(cat_b in w for w in st)
                    has_a_pdf = any(cat_a in w for w in pt)
                    has_b_pdf = any(cat_b in w for w in pt)
                    if (has_a_src and has_a_pdf) or (has_b_src and has_b_pdf):
                        score += 35.0
                    elif (has_a_src and has_b_pdf) or (has_b_src and has_a_pdf):
                        score -= 200.0 if any(k in (cat_a, cat_b) for k in ['guru', 'murid', 'sekolah']) else 50.0

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
        wilayah_matches = sum(1 for r in sample_rows if isinstance(r, list) and resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] is not None)
        if wilayah_matches >= 3:
            return "SINGLE_PAGE_REGIONAL"
        prov_matches = sum(1 for r in sample_rows if isinstance(r, list) and resolve_provinsi_entity(' '.join(str(c) for c in r[:3]))[0] is not None)
        if prov_matches >= 3:
            return "SINGLE_PAGE_PROVINCIAL"
        return "SINGLE_PAGE_CATEGORICAL"

    p1_info = [{'nomor_tabel': target_table, 'pages': [target_pages[0]]}]
    p2_info = [{'nomor_tabel': target_table, 'pages': [target_pages[1]]}]
    extract_table_content(pdf_path, p1_info)
    extract_table_content(pdf_path, p2_info)
    p1_rows = p1_info[0].get('extracted_data', [])
    p2_rows = p2_info[0].get('extracted_data', [])

    p2_first_ints = []
    for r in p2_rows[:15]:
        if not isinstance(r, list) or not r: continue
        c0 = str(r[0]).strip().rstrip('.')
        if c0.isdigit():
            p2_first_ints.append(int(c0))
            if len(p2_first_ints) >= 3: break

    if p2_first_ints and p2_first_ints[0] > 20:
        return "MULTI_PAGE_ROW_CONTINUATION"

    p1_jateng = sum(1 for r in p1_rows if isinstance(r, list) and resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] is not None)
    p2_jateng = sum(1 for r in p2_rows if isinstance(r, list) and resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] is not None)

    if p1_jateng >= 5 and p2_jateng >= 5:
        return "MULTI_PAGE_COLUMN_SPLIT"

    return "MULTI_PAGE_SECTION_PER_PAGE"

def normalize_api_data(api_records, pdf_rows=None, table_num=""):
    """
    Mengonversi list of dict dari API Satudata menjadi representasi 2D tabular
    yang kompatibel dengan matching engine compare_table.py.
    """
    if not isinstance(api_records, list) or not api_records or not isinstance(api_records[0], dict):
        return None

    sample = api_records[0]
    keys = list(sample.keys())

    # 1. CEK APAKAH INI TABEL WILAYAH (35 Kab/Kota)
    reg_key = None
    for k in ['nama_wilayah', 'nama_kabupatenkota', 'nama_wiayah', 'kabupaten_kota', 'wilayah']:
        if k in sample:
            reg_key = k
            break
            
    if reg_key:
        reg_vals = [r.get(reg_key) for r in api_records if r.get(reg_key)]
        if len(reg_vals) == len(set(reg_vals)) or len(set(reg_vals)) >= 25:
            ignore_keys = {'tahun_data', 'kode_wilayah', 'kode_kabupatenkota', 'kode_kabupaten', 'id', 'created_at', 'updated_at', reg_key}
            metric_keys = [k for k in keys if k not in ignore_keys]
            
            headers = ['Wilayah'] + metric_keys
            grid = [headers]
            for r in api_records:
                row = [r.get(reg_key)]
                for mk in metric_keys:
                    row.append(r.get(mk))
                grid.append(row)
            return grid

    # 2. CEK APAKAH INI TABEL TALL / PIVOT (Seperti 2.2.1 dan 2.2.5)
    dim_key = None
    for k in ['asal_partai_politik', 'dinasinstansi_pemerintahan', 'nama_sektor', 'sektor', 'uraian', 'jenis_pendapatan']:
        if k in sample:
            dim_key = k
            break
            
    if dim_key:
        pdf_text = ""
        if pdf_rows:
            for r in pdf_rows[:5]:
                if isinstance(r, list):
                    pdf_text += " " + " ".join(str(c) for c in r)
        pdf_text_lower = pdf_text.lower()
        
        has_gender = 'jenis_kelamin' in sample and ('laki' in pdf_text_lower or 'gender' in pdf_text_lower or 'sex' in pdf_text_lower or 'perempuan' in pdf_text_lower or '(2)' in pdf_text)
        has_age = 'usia' in sample and ('umur' in pdf_text_lower or 'age' in pdf_text_lower or '21' in pdf_text_lower or '35' in pdf_text_lower)
        
        # Kasus 2.2.1: Menurut Partai Politik dan Jenis Kelamin
        if has_gender and not has_age:
            parties = []
            for r in api_records:
                p = r.get(dim_key, '').strip()
                if p and p not in parties:
                    parties.append(p)
            
            grid = [['Partai Politik', 'L', 'P', 'Jumlah']]
            for p in parties:
                l_val = sum(float(r.get('jumlah') or 0) for r in api_records if r.get(dim_key, '').strip() == p and str(r.get('jenis_kelamin', '')).strip().upper() == 'L')
                p_val = sum(float(r.get('jumlah') or 0) for r in api_records if r.get(dim_key, '').strip() == p and str(r.get('jenis_kelamin', '')).strip().upper() == 'P')
                tot = l_val + p_val
                grid.append([p, int(l_val) if l_val.is_integer() else l_val, int(p_val) if p_val.is_integer() else p_val, int(tot) if tot.is_integer() else tot])
            return grid
            
        # Kasus 2.2.5: Menurut Partai Politik dan Kelompok Umur
        if has_age:
            parties = []
            for r in api_records:
                p = r.get(dim_key, '').strip()
                if p and p not in parties:
                    parties.append(p)
            
            age_brackets = ['21-35', '36-49', '50-59', '60+']
            grid = [['Partai Politik'] + age_brackets + ['Jumlah']]
            for p in parties:
                row = [p]
                tot = 0
                for ab in age_brackets:
                    if ab == '60+':
                        val = sum(float(r.get('jumlah') or 0) for r in api_records if r.get(dim_key, '').strip() == p and '60' in str(r.get('usia', '')))
                    else:
                        val = sum(float(r.get('jumlah') or 0) for r in api_records if r.get(dim_key, '').strip() == p and ab in str(r.get('usia', '')).replace('–', '-'))
                    row.append(int(val) if val.is_integer() else val)
                    tot += val
                row.append(int(tot) if tot.is_integer() else tot)
                grid.append(row)
            return grid

        # Tabel Flat Categorical (misal 2.3.22 atau 2.4.11)
        ignore_keys = {'tahun_data', 'id', 'created_at', 'updated_at', dim_key}
        metric_keys = [k for k in keys if k not in ignore_keys]
        grid = [[dim_key] + metric_keys]
        for r in api_records:
            row = [r.get(dim_key)]
            for mk in metric_keys:
                row.append(r.get(mk))
            grid.append(row)
        return grid

    return None
