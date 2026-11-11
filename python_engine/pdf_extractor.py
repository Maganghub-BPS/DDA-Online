# Untuk mengambil tabel dari PDF dan cache halaman
import os
import re
import json
import threading
import pdfplumber

_PDF_PAGE_CACHE = {}
_PDF_PAGE_CACHE_LOCK = threading.Lock()


def is_valid_table_char(obj):
    if obj.get('object_type') != 'char':
        return True
    if not obj.get('upright', True):
        return False
    font = str(obj.get('fontname', ''))
    size = float(obj.get('size', 0))
    if 'Arial' in font and size > 15:
        return False
    return True


def strip_empty_table_columns(table):
    """Menghapus kolom artefak kosong hasil ekstraksi PDFPlumber agar indeks kolom konsisten."""
    if not table or not isinstance(table, list):
        return table
    max_cols = max((len(r) for r in table if isinstance(r, list)), default=0)
    if max_cols <= 1:
        return table
    valid_cols = [
        c for c in range(max_cols)
        if any(isinstance(r, list) and c < len(r) and r[c] is not None and str(r[c]).strip() != '' for r in table)
    ]
    if 0 < len(valid_cols) < max_cols:
        return [[r[c] if c < len(r) else None for c in valid_cols] for r in table if isinstance(r, list)]
    return table


def extract_page_tables_robust(clean_page):
    """Ekstraksi tabel dari halaman PDF dengan fallback unskew untuk garis/poligon yang sedikit miring."""
    page_tables = clean_page.extract_tables()
    if page_tables:
        return page_tables
    h_lines = []
    v_lines = []
    p_height = float(getattr(clean_page, 'height', 842.0) or 842.0)
    for c in getattr(clean_page, 'curves', []) or []:
        pts = c.get('pts', [])
        path = c.get('path', [])
        if len(pts) >= 2 and all(op[0] in ('m', 'l', 'h') for op in path):
            for i in range(len(pts) - 1):
                x0, y0 = pts[i]
                x1, y1 = pts[i + 1]
                if abs(y1 - y0) < 3.0 and abs(x1 - x0) > 5.0:
                    ym = (y0 + y1) / 2.0
                    h_lines.append({
                        'x0': min(x0, x1), 'x1': max(x0, x1),
                        'top': ym, 'bottom': ym,
                        'y0': p_height - ym, 'y1': p_height - ym,
                        'width': abs(x1 - x0), 'height': 0,
                        'orientation': 'h', 'object_type': 'line'
                    })
                elif abs(x1 - x0) < 3.0 and abs(y1 - y0) > 5.0:
                    xm = (x0 + x1) / 2.0
                    v_lines.append({
                        'x0': xm, 'x1': xm,
                        'top': min(y0, y1), 'bottom': max(y0, y1),
                        'y0': p_height - max(y0, y1), 'y1': p_height - min(y0, y1),
                        'width': 0, 'height': abs(y1 - y0),
                        'orientation': 'v', 'object_type': 'line'
                    })
    if h_lines and v_lines:
        return clean_page.extract_tables({
            'vertical_strategy': 'explicit',
            'horizontal_strategy': 'explicit',
            'explicit_vertical_lines': v_lines,
            'explicit_horizontal_lines': h_lines,
            'snap_tolerance': 3,
            'join_tolerance': 3,
        })
    return []


def get_cached_page_tables(pdf_path, page_num):
    cache_key = (pdf_path, page_num)
    with _PDF_PAGE_CACHE_LOCK:
        if cache_key in _PDF_PAGE_CACHE:
            return _PDF_PAGE_CACHE[cache_key]

    with pdfplumber.open(pdf_path) as pdf:
        if 0 <= page_num - 1 < len(pdf.pages):
            page = pdf.pages[page_num - 1]
            clean_page = page.filter(is_valid_table_char)
            page_tables = extract_page_tables_robust(clean_page)
            res = []
            for t in page_tables:
                res.extend(strip_empty_table_columns(t))
            with _PDF_PAGE_CACHE_LOCK:
                _PDF_PAGE_CACHE[cache_key] = res
            return res
        return []


def extract_tables_from_pdf(pdf_path, target_tables):
    up_dir = os.path.dirname(os.path.abspath(pdf_path))
    m_year = re.search(r'(\d{4})', pdf_path)
    ta = m_year.group(1) if m_year else "2026"
    candidates = [
        os.path.join(up_dir, f"dda_matched_{ta}.json"),
        os.path.join(up_dir, "dda_matched_2026.json"),
    ]
    data = {}
    for cand in candidates:
        if os.path.exists(cand):
            try:
                with open(cand, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                break
            except Exception:
                pass
    res = {}
    for t in data.get('matched_in_pdf', data.get('matched_tables', [])):
        if t.get('nomor_tabel') in target_tables:
            res[t['nomor_tabel']] = t
    return res


def stitch_table_pages(pdf_path, pages):
    if not pages:
        return []
    if len(pages) == 1:
        return get_cached_page_tables(pdf_path, pages[0])

    all_pages_data = [get_cached_page_tables(pdf_path, p) for p in pages]

    from table_profiler import find_bps_numbering_row, is_header_or_metadata_row
    from normalizer import parse_num, resolve_jateng_entity

    def _get_bps_cols(rows):
        idx, cells, r_type = find_bps_numbering_row(rows)
        if idx < 0:
            return -1, []
        cols = []
        for c_idx, c_val in cells:
            m = re.search(r'\b(\d+)\b', str(c_val))
            if m:
                cols.append((c_idx, int(m.group(1))))
        return idx, cols

    def _norm_label(r):
        parts = []
        for idx, c in enumerate(r[:3]):
            c_str = str(c).strip() if c is not None else ''
            if not c_str or c_str.lower() == 'none':
                continue
            if re.match(r'^\d+\.?$', c_str):
                continue
            if parse_num(c_str, is_pdf=True) is not None:
                continue
            parts.append(c_str)
        raw = ' '.join(parts).lower()
        code, name = resolve_jateng_entity(raw)
        if name:
            return f"REG_{code}"
        return re.sub(r'[^\w]', '', raw)

    page_col_info = [_get_bps_cols(p_rows) for p_rows in all_pages_data]

    # Check whether table has horizontal continuation
    has_horiz = False
    for i in range(1, len(page_col_info)):
        prev_idx, prev_cols = page_col_info[i - 1]
        curr_idx, curr_cols = page_col_info[i]
        if prev_cols and curr_cols:
            max_prev = max(c[1] for c in prev_cols)
            curr_data_nums = [c[1] for c in curr_cols if c[1] > 1]
            if curr_data_nums and any(n > max_prev and n > max((c[1] for c in page_col_info[0][1]), default=0) for n in curr_data_nums):
                has_horiz = True
                break

    if not has_horiz:
        res = []
        for p_rows in all_pages_data:
            res.extend(p_rows)
        return res

    # 1. Group contiguous pages into horizontal blocks
    blocks = []
    current_block = []
    for p_idx, (p_num, (bps_idx, bps_cols)) in enumerate(zip(pages, page_col_info)):
        if not bps_cols:
            current_block.append((p_num, all_pages_data[p_idx], bps_idx, bps_cols))
            continue
        col_numbers = [c[1] for c in bps_cols]
        if not blocks and not current_block:
            current_block.append((p_num, all_pages_data[p_idx], bps_idx, bps_cols))
        else:
            prev_num, prev_data, prev_b_idx, prev_b_cols = current_block[-1]
            prev_col_nums = [c[1] for c in prev_b_cols]
            if any(n > max(prev_col_nums) and n > max((c[1] for c in page_col_info[0][1]), default=0) for n in col_numbers if n > 1):
                blocks.append(current_block)
                current_block = [(p_num, all_pages_data[p_idx], bps_idx, bps_cols)]
            else:
                current_block.append((p_num, all_pages_data[p_idx], bps_idx, bps_cols))
    if current_block:
        blocks.append(current_block)

    # 2. Build flat block for each
    flat_blocks = []
    for b_idx, block in enumerate(blocks):
        b_headers = []
        b_data_rows = []
        b_bps_cols = []
        for item in block:
            p_num, p_rows, bps_idx, bps_cols = item
            if not p_rows:
                continue
            if not b_bps_cols and bps_cols:
                b_bps_cols = bps_cols
            p_data_start = (bps_idx + 1) if bps_idx >= 0 else 0
            if not b_headers and bps_idx >= 0:
                b_headers = p_rows[:p_data_start]
            for r in p_rows[p_data_start:]:
                if not isinstance(r, list) or not r:
                    continue
                txt = ' '.join(str(c) for c in r if str(c).strip()).lower()
                is_gender = any(k in txt for k in ['laki-laki', 'perempuan', 'male', 'female', 'laki laki']) and not any(parse_num(c, is_pdf=True) for c in r if str(c).strip())
                if is_header_or_metadata_row(r) and not is_gender:
                    continue
                b_data_rows.append(r)
        flat_blocks.append({
            'headers': b_headers,
            'data_rows': b_data_rows,
            'bps_cols': b_bps_cols
        })

    if not flat_blocks:
        return []

    main_block = flat_blocks[0]
    main_headers = [list(h) for h in main_block['headers']]
    main_rows = [list(r) for r in main_block['data_rows']]
    main_keys = [_norm_label(r) for r in main_rows]

    for b_idx in range(1, len(flat_blocks)):
        ext_block = flat_blocks[b_idx]
        ext_headers = ext_block['headers']
        ext_rows = ext_block['data_rows']
        ext_bps_cols = ext_block['bps_cols']

        first_data_col = 1
        if ext_bps_cols:
            for c_idx, num in ext_bps_cols:
                if num > 1:
                    first_data_col = c_idx
                    break

        m_bps_idx, _, _ = find_bps_numbering_row(main_headers)
        e_bps_idx, _, _ = find_bps_numbering_row(ext_headers)
        if m_bps_idx >= 0 and e_bps_idx >= 0:
            offset = m_bps_idx - e_bps_idx
            if offset < 0:
                pad_count = -offset
                col_count = len(main_headers[0]) if main_headers else 0
                main_headers = [[''] * col_count for _ in range(pad_count)] + main_headers
                m_bps_idx += pad_count
                offset = 0
            for e_i, e_row in enumerate(ext_headers):
                target_m_i = e_i + offset
                ext_cols_to_add = e_row[first_data_col:]
                if target_m_i < len(main_headers):
                    main_headers[target_m_i].extend(ext_cols_to_add)
                else:
                    main_headers.append([''] * len(main_headers[0]) + ext_cols_to_add)
        else:
            max_h_len = max(len(main_headers), len(ext_headers))
            for h_i in range(max_h_len):
                m_h = main_headers[h_i] if h_i < len(main_headers) else [''] * len(main_headers[0])
                e_h = ext_headers[h_i] if h_i < len(ext_headers) else [''] * len(ext_headers[0])
                ext_cols_to_add = e_h[first_data_col:]
                m_h.extend(ext_cols_to_add)
                if h_i < len(main_headers):
                    main_headers[h_i] = m_h
                else:
                    main_headers.append(m_h)

        ext_map = {}
        for r_idx, r in enumerate(ext_rows):
            k = _norm_label(r)
            if k and k not in ext_map:
                ext_map[k] = r[first_data_col:]
            ext_map[r_idx] = r[first_data_col:]

        has_dup_keys = len(main_keys) != len(set(main_keys))
        ext_col_count = len(ext_rows[0][first_data_col:]) if ext_rows else 0
        for r_idx, r in enumerate(main_rows):
            k = main_keys[r_idx]
            match_ext = None
            if not has_dup_keys and k in ext_map:
                match_ext = ext_map.get(k)
            else:
                match_ext = ext_map.get(r_idx)
            if match_ext is not None:
                r.extend(match_ext)
            else:
                r.extend([None] * ext_col_count)

    return main_headers + main_rows


def extract_table_content(pdf_path, target_pdf_info):
    missing_pages = set()
    for tb in target_pdf_info:
        for p in tb.get("pages", []):
            if (pdf_path, p) not in _PDF_PAGE_CACHE:
                missing_pages.add(p)

    if missing_pages:
        with pdfplumber.open(pdf_path) as pdf:
            for p in missing_pages:
                cache_key = (pdf_path, p)
                if 0 <= p - 1 < len(pdf.pages):
                    page = pdf.pages[p - 1]
                    clean_page = page.filter(is_valid_table_char)
                    tables = extract_page_tables_robust(clean_page)
                    p_rows = []
                    for t in tables:
                        p_rows.extend(strip_empty_table_columns(t))
                    with _PDF_PAGE_CACHE_LOCK:
                        _PDF_PAGE_CACHE[cache_key] = p_rows

    for tb in target_pdf_info:
        pages = tb.get("pages", [])
        if len(pages) > 1:
            tb["extracted_data"] = stitch_table_pages(pdf_path, pages)
        elif pages:
            tb["extracted_data"] = get_cached_page_tables(pdf_path, pages[0])
        else:
            tb["extracted_data"] = []
