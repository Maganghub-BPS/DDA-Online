import sys
import os
import re
import json

sys.path.append(os.path.dirname(os.path.abspath(__file__)))

try:
    import pymupdf as fitz
except ImportError:
    import fitz

from normalizer import strip_pdf_page_watermark

SINGLE_CHAR_WATERMARK = {'j', 'a', 't', 'e', 'n', 'g', 'b', 'p', 's', 'o', 'i', 'd', '.', 'h', ':'}

ENGLISH_SIGNAL_WORDS = {
    'of', 'to', 'in', 'by', 'and', 'the', 'for', 'with', 'from', 'on', 'at', 'or',
    'right', 'rights', 'ownership', 'endowments', 'management', 'mortgage', 'use', 'build',
    'number', 'total', 'area', 'value', 'rate', 'ratio', 'percentage', 'distribution',
    'group', 'groups', 'type', 'types', 'kind', 'kinds', 'status', 'level', 'levels',
    'public', 'private', 'male', 'female', 'sex', 'age', 'urban', 'rural',
    'regency', 'municipality', 'province', 'district', 'subdistrict', 'village', 'villages',
    'school', 'schools', 'teacher', 'teachers', 'headmaster', 'headmasters', 'pupil', 'pupils', 'student', 'students',
    'average', 'minimum', 'maximum', 'temperature', 'humidity', 'velocity', 'pressure', 'precipitation', 'rainy', 'days',
    'coastal', 'non-coastal', 'flat', 'slope', 'valley', 'altitude', 'distance', 'capital', 'island', 'islands',
    'production', 'capacity', 'customer', 'customers', 'household', 'households', 'industry', 'business', 'social', 'government',
    'employee', 'employees', 'civil', 'servant', 'servants', 'state', 'agreement', 'agreements',
    'population', 'thousand', 'people', 'million', 'billion', 'rupiahs', 'rupiah', 'year', 'years', 'month', 'months',
    'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'
}

def is_english_line(line):
    words = re.findall(r'[a-zA-Z]+', line.lower())
    if not words:
        return False
    eng_hits = sum(1 for w in words if w in ENGLISH_SIGNAL_WORDS)
    return eng_hits >= 1

def clean_cell_text(val):
    if val is None:
        return ""
    lines = str(val).split("\n")
    cleaned_lines = []
    for line in lines:
        tokens = line.strip().split()
        filtered = []
        for tok in tokens:
            t_low = tok.lower().strip()
            if re.match(r'^[IVXLCDM]+\.?$', tok.strip()):
                filtered.append(tok)
                continue
            if len(t_low) == 1 and t_low in SINGLE_CHAR_WATERMARK and not t_low.isdigit():
                continue
            if len(t_low) <= 3 and re.match(r'^[\.:\s]+$', t_low):
                continue
            if t_low in ('.o', 'o.', '.g', 'g.', '.s', 's.', '.i', 'i.', 'ps', 'bp', 'go', 'id') and len(tokens) > 1:
                continue
            filtered.append(tok)
        if filtered:
            cleaned_lines.append(" ".join(filtered))
    return "\n".join(cleaned_lines).strip()

def clean_footnote_markers(s):
    if not s:
        return ""
    s = s.replace('\ufffd', '-').replace('–', '-').replace('—', '-')
    # Footnote pada tahun: 20241), 20241, 2024*), 2024*, 2024a) -> 2024
    s = re.sub(r'\b((?:19|20)\d{2})[1-9\*\^a-zA-Z]?\)', r'\1', s)
    s = re.sub(r'\b((?:19|20)\d{2})[\*\^]+(?=\s|[/\-,]|$)', r'\1', s)
    s = re.sub(r'\b((?:19|20)\d{2})[1-9](?:,[1-9])?(?=\s|[/\-,]|$)', r'\1', s)
    s = re.sub(r'(?<=[a-zA-Z])[1-9](?=[/\s,]|$)', '', s)
    return re.sub(r'\s+', ' ', s).strip()

def split_bilingual(text):
    if not text:
        return ("", "")
    if " || " in text:
        parts = text.split(" || ")
        inds = []
        ens = []
        for p in parts:
            p_ind, p_en = split_bilingual(p)
            inds.append(p_ind)
            ens.append(p_en)
        return (" || ".join(inds), " || ".join(ens))

    lines = [clean_footnote_markers(l) for l in text.split("\n") if clean_footnote_markers(l)]
    if not lines:
        return ("", "")

    if len(lines) == 1:
        s = lines[0]
        if '/' in s and not re.match(r'^\d{4}/\d{4}$', s):
            low = s.lower()
            if not any(k in low for k in ['kabupaten/kota', 'desa/kelurahan', 'lereng/puncak', 'negeri/swasta', 'laki/perempuan', 'kota/kabupaten', 'u/d', 's/d']):
                use_rsplit = s.count('/') >= 2 and not is_english_line(s.split('/', 1)[1].split('/')[0])
                parts = [p.strip() for p in (s.rsplit('/', 1) if use_rsplit else s.split('/', 1))]
                if len(parts) == 2 and parts[0] and parts[1]:
                    return (parts[0], parts[1])
        return (s, s)

    joined = " ".join(lines)
    if '/' in joined and not re.search(r'\d{4}/\d{4}', joined):
        low = joined.lower()
        if not any(k in low for k in ['kabupaten/kota', 'desa/kelurahan', 'lereng/puncak', 'regency/municipality']):
            use_rsplit = joined.count('/') >= 2 and not is_english_line(joined.split('/', 1)[1].split('/')[0])
            parts = [p.strip() for p in (joined.rsplit('/', 1) if use_rsplit else joined.split('/', 1))]
            if len(parts) == 2 and parts[0] and parts[1]:
                return (parts[0], parts[1])

    if len(lines) == 2:
        return (lines[0], lines[1])

    # Detect where English starts in 3+ lines
    split_idx = None
    for idx in range(1, len(lines)):
        if is_english_line(lines[idx]):
            split_idx = idx
            break

    if split_idx is None:
        split_idx = (len(lines) + 1) // 2

    ind = " ".join(lines[:split_idx])
    en = " ".join(lines[split_idx:])
    return (ind, en)

def is_period_token(s):
    s = clean_footnote_markers(s)
    if re.match(r'^(19|20)\d{2}(\s*[-/]\s*(19|20)\d{2})?$', s):
        return True
    return False

def normalize_period_token(s):
    return clean_footnote_markers(s)

def extract_table_structure_from_pdf(pdf_path, pages):
    if not os.path.exists(pdf_path):
        return {"status": "error", "message": "File PDF Master tidak ditemukan."}

    doc = fitz.open(pdf_path)
    page_analyze = []

    for p in pages:
        p = int(p)
        if p < 1 or p > len(doc):
            continue
        page = doc[p - 1]
        strip_pdf_page_watermark(doc, page)
        import io, contextlib
        def _detect_col_nums(grid_cand):
            c_idx_found = -1
            c_nums_found = {}
            for r_idx, row in enumerate(grid_cand[:10]):
                matches = 0
                temp_map = {}
                for c_idx, cell in enumerate(row):
                    c_clean = clean_cell_text(cell)
                    m = re.search(r'\((\d+)\)', c_clean)
                    if m:
                        matches += 1
                        temp_map[c_idx] = int(m.group(1))
                if matches >= 2 and 1 in temp_map.values():
                    c_idx_found = r_idx
                    c_nums_found = temp_map
                    break
            if c_idx_found == -1:
                for r_idx, row in enumerate(grid_cand[:10]):
                    temp_map = {}
                    seq_vals = []
                    for c_idx, cell in enumerate(row):
                        c_clean = clean_cell_text(cell).strip()
                        if c_clean.isdigit():
                            v = int(c_clean)
                            if 1 <= v <= 30:
                                temp_map[c_idx] = v
                                seq_vals.append(v)
                    if len(seq_vals) >= 3 and 1 in seq_vals:
                        is_seq = all(seq_vals[i] < seq_vals[i+1] for i in range(len(seq_vals)-1))
                        if is_seq:
                            c_idx_found = r_idx
                            c_nums_found = temp_map
                            break
            if c_idx_found != -1 and c_idx_found >= len(grid_cand) - 1:
                return -1, {}
            return c_idx_found, c_nums_found

        with contextlib.redirect_stdout(io.StringIO()):
            tabs = page.find_tables()
        extracted_tables = []
        if tabs and tabs.tables:
            for t in tabs.tables:
                try:
                    g = t.extract()
                    if g and len(g) >= 3:
                        extracted_tables.append((len(g) * len(g[0]), g))
                except Exception:
                    pass
        raw_grid = max(extracted_tables, key=lambda x: x[0])[1] if extracted_tables else []
        col_num_idx, col_nums = _detect_col_nums(raw_grid) if raw_grid else (-1, {})

        if col_num_idx == -1:
            try:
                try:
                    from pdf_extractor import get_cached_page_tables
                except ImportError:
                    from python_engine.pdf_extractor import get_cached_page_tables
                fb_grid = get_cached_page_tables(pdf_path, p)
                if fb_grid and len(fb_grid) >= 3:
                    fb_idx, fb_nums = _detect_col_nums(fb_grid)
                    if fb_idx != -1:
                        raw_grid = fb_grid
                        col_num_idx = fb_idx
                        col_nums = fb_nums
            except Exception:
                pass

        if col_num_idx == -1:
            continue

        page_analyze.append({
            "page": p,
            "grid": raw_grid,
            "col_num_idx": col_num_idx,
            "col_nums": col_nums
        })

    if not page_analyze:
        return {"status": "error", "message": "Struktur garis tabel tidak terdeteksi di halaman PDF."}

    first_pa = page_analyze[0]
    data_col_headers = {}
    row_header_raw = ""

    # Deteksi kolom sub-baris (misal 'Provinsi', 'Bulan', 'Satuan') yang memiliki nomor kolom (2) namun berupa teks
    sub_row_ci = None
    candidate_c_indices = sorted([ci for ci, cn in first_pa["col_nums"].items() if cn > 1])
    if candidate_c_indices:
        cand_ci = candidate_c_indices[0]
        # Cek teks header pada cand_ci
        cand_header = " ".join([
            clean_cell_text(first_pa["grid"][r][cand_ci]) 
            for r in range(first_pa["col_num_idx"]) 
            if cand_ci < len(first_pa["grid"][r])
        ]).lower()
        subrow_keywords = [
            'provinsi', 'province', 'bulan', 'month', 'satuan', 'unit', 
            'rincian', 'commodity', 'komoditi', 'kabupaten', 'kota', 'daerah', 'wilayah'
        ]
        has_subrow_header = any(k in cand_header for k in subrow_keywords)
        
        # Cek isi baris data kolom cand_ci
        text_count = 0
        cand_samples = 0
        for r in first_pa["grid"][first_pa["col_num_idx"] + 1:]:
            if cand_ci < len(r):
                c_text = clean_cell_text(r[cand_ci])
                if c_text and not re.match(r'^\(\d+\)$', c_text):
                    cand_samples += 1
                    letters = re.findall(r'[a-zA-Z]', c_text)
                    digits = re.findall(r'[0-9]', c_text)
                    if len(letters) >= 2 and len(letters) > len(digits):
                        text_count += 1
            if cand_samples >= 20:
                break
        
        cand_ratio = (text_count / cand_samples) if cand_samples > 0 else 0
        if has_subrow_header or cand_ratio >= 0.5:
            sub_row_ci = cand_ci

    for pa in page_analyze:
        grid = pa["grid"]
        c_idx_map = pa["col_nums"]
        c_row_idx = pa["col_num_idx"]
        header_rows = grid[:c_row_idx]

        if sub_row_ci is not None:
            data_c_indices = sorted([ci for ci, cn in c_idx_map.items() if ci > sub_row_ci])
        else:
            data_c_indices = sorted([ci for ci, cn in c_idx_map.items() if cn > 1])
            
        if not data_c_indices:
            continue
        first_data_ci = data_c_indices[0]

        if not row_header_raw and header_rows:
            if sub_row_ci is not None:
                rh_col1 = "\n".join([clean_cell_text(hr[ci]) for hr in header_rows for ci in range(sub_row_ci) if clean_cell_text(hr[ci])])
                rh_col2 = "\n".join([clean_cell_text(hr[sub_row_ci]) for hr in header_rows if sub_row_ci < len(hr) and clean_cell_text(hr[sub_row_ci])])
                row_header_raw = f"{rh_col1} || {rh_col2}"
            else:
                rh_parts = []
                for hr in header_rows:
                    for ci in range(first_data_ci):
                        txt = clean_cell_text(hr[ci])
                        if txt:
                            rh_parts.append(txt)
                row_header_raw = "\n".join(rh_parts)

        ffilled_headers = []
        for hr in header_rows:
            row_ff = [None] * len(hr)
            last_val = ""
            for ci in range(first_data_ci, len(hr)):
                txt = clean_cell_text(hr[ci])
                if txt:
                    last_val = txt
                row_ff[ci] = last_val
            ffilled_headers.append(row_ff)

        for ci in data_c_indices:
            cn = c_idx_map[ci]
            col_path = []
            for hr_ff in ffilled_headers:
                val = hr_ff[ci] if ci < len(hr_ff) else ""
                if val and (not col_path or col_path[-1] != val):
                    col_path.append(val)
            norm_path = tuple(re.sub(r'\s+', ' ', clean_footnote_markers(p)).strip().lower() for p in col_path)
            existing_norm_paths = {
                tuple(re.sub(r'\s+', ' ', clean_footnote_markers(p)).strip().lower() for p in ep)
                for ep in data_col_headers.values()
            }
            if cn in data_col_headers:
                if col_path and norm_path not in existing_norm_paths:
                    new_cn = max(data_col_headers.keys()) + 1
                    data_col_headers[new_cn] = col_path
            else:
                if not (len(page_analyze) > 1 and pa is not first_pa and norm_path in existing_norm_paths):
                    target_cn = (max(data_col_headers.keys()) + 1) if (pa is not first_pa and data_col_headers and cn <= max(data_col_headers.keys())) else cn
                    data_col_headers[target_cn] = col_path

    sorted_col_nums = sorted(data_col_headers.keys())
    if not sorted_col_nums:
        return {"status": "error", "message": "Kolom data (2)..(N) tidak ditemukan di PDF."}

    first_page_col_nums = set([cn for ci, cn in first_pa["col_nums"].items() if (ci > sub_row_ci if sub_row_ci is not None else cn > 1)])
    extracted_rows = []
    seen_row_keys = set()
    last_col1 = ""

    for pa in page_analyze:
        grid = pa["grid"]
        c_row_idx = pa["col_num_idx"]
        if sub_row_ci is not None:
            data_c_indices = sorted([ci for ci, cn in pa["col_nums"].items() if ci > sub_row_ci])
        else:
            data_c_indices = sorted([ci for ci, cn in pa["col_nums"].items() if cn > 1])
        if not data_c_indices:
            continue
        first_data_ci = data_c_indices[0]

        for r in grid[c_row_idx + 1:]:
            try:
                from normalizer import is_header_or_metadata_row
                if is_header_or_metadata_row(r):
                    continue
            except Exception:
                pass
            if sub_row_ci is not None:
                # Kolom 1 (Kategori / Jenis Komoditi)
                col1_parts = [clean_cell_text(r[ci]) for ci in range(sub_row_ci) if ci < len(r) and clean_cell_text(r[ci])]
                col1_val = " ".join(col1_parts).strip()
                if col1_val and not re.match(r'^\(\d+\)$', col1_val):
                    last_col1 = col1_val

                # Kolom 2 (Sub-rincian / Provinsi)
                col2_val = clean_cell_text(r[sub_row_ci]) if sub_row_ci < len(r) else ""
                if not col2_val and not col1_val:
                    continue
                if re.match(r'^\(\d+\)$', col2_val):
                    continue

                full_col1 = last_col1 or col1_val
                if not full_col1:
                    full_col1 = col2_val
                    full_col2 = ""
                else:
                    full_col2 = col2_val

                col1_ind, col1_en = split_bilingual(full_col1)
                col2_ind, col2_en = split_bilingual(full_col2)

                if col1_ind.lower().strip() in ('jumlah', 'total', 'jumlah/total', 'total/jumlah'):
                    last_col1 = ""

                if full_col2:
                    row_ind = f"{col1_ind} || {col2_ind}".strip()
                    row_en = f"{col1_en} || {col2_en}".strip()
                    full_label = f"{full_col1} || {full_col2}"
                else:
                    row_ind = col1_ind
                    row_en = col1_en
                    full_label = full_col1

                row_ind = " ".join(row_ind.split()).strip()
                row_en = " ".join(row_en.split()).strip()
                if not row_ind:
                    continue
                norm_key = row_ind.lower()
                if norm_key not in seen_row_keys:
                    seen_row_keys.add(norm_key)
                    new_item = {"ind": row_ind, "en": row_en, "raw": full_label}
                    if full_col2:
                        prefix_low = f"{col1_ind.lower().strip()} || "
                        last_grp_idx = -1
                        for idx_er, er in enumerate(extracted_rows):
                            if er["ind"].lower().startswith(prefix_low):
                                last_grp_idx = idx_er
                        if last_grp_idx >= 0:
                            extracted_rows.insert(last_grp_idx + 1, new_item)
                        elif extracted_rows and extracted_rows[-1]["ind"].lower().strip() in ('jumlah', 'total', 'jumlah/total'):
                            extracted_rows.insert(len(extracted_rows) - 1, new_item)
                        else:
                            extracted_rows.append(new_item)
                    else:
                        extracted_rows.append(new_item)
            else:
                label_parts = []
                for ci in range(first_data_ci):
                    t = clean_cell_text(r[ci])
                    if t:
                        label_parts.append(t)
                if len(label_parts) > 1 and re.match(r'^\d+\.?$', label_parts[0].strip()):
                    label_parts = label_parts[1:]
                full_label = "\n".join(label_parts).strip()
                full_label = re.sub(r'^[/\s]+', '', full_label).strip()
                full_label = re.sub(r'[/\s]+$', '', full_label).strip()
                if not full_label:
                    continue
                if re.match(r'^\(\d+\)$', full_label):
                    continue
                row_ind, row_en = split_bilingual(full_label)
                row_ind = " ".join(row_ind.split()).strip()
                row_en = " ".join(row_en.split()).strip()
                if not row_ind:
                    continue
                norm_key = row_ind.lower()
                if norm_key not in seen_row_keys:
                    seen_row_keys.add(norm_key)
                    extracted_rows.append({"ind": row_ind, "en": row_en, "raw": full_label})

    all_last_are_periods = all(
        len(data_col_headers[cn]) >= 1 and is_period_token(data_col_headers[cn][-1])
        for cn in sorted_col_nums
    )
    all_first_are_periods = (
        not all_last_are_periods and
        all(len(data_col_headers[cn]) >= 2 and is_period_token(data_col_headers[cn][0]) for cn in sorted_col_nums)
    )

    periods = []
    karakteristik_items = []
    posisi_hirarki = "kolom_atas"

    if all_last_are_periods:
        for cn in sorted_col_nums:
            p_tok = normalize_period_token(data_col_headers[cn][-1])
            if p_tok not in periods:
                periods.append(p_tok)

        has_upper = any(len(data_col_headers[cn]) > 1 for cn in sorted_col_nums)
        if has_upper:
            posisi_hirarki = "kolom_atas"
            seen_kar = set()
            for cn in sorted_col_nums:
                upper_path = data_col_headers[cn][:-1]
                if not upper_path:
                    continue
                ind_parts = []
                en_parts = []
                for up in upper_path:
                    u_ind, u_en = split_bilingual(up)
                    clean_up = up.lower().replace(' ', '')
                    if ('+' in up) and any(k in clean_up for k in ['laki-laki+perempuan', 'laki+perempuan', 'male+female', 'pria+wanita', 'l+p', 'wni+wna', 'negeri+swasta']):
                        u_ind = "Jumlah"
                        u_en = "Total"
                    ind_parts.append(u_ind)
                    en_parts.append(u_en)
                kar_ind = " || ".join(ind_parts)
                kar_en = " || ".join(en_parts)
                if kar_ind.lower() not in seen_kar:
                    seen_kar.add(kar_ind.lower())
                    karakteristik_items.append({"item_ind": kar_ind, "item_en": kar_en})
        else:
            posisi_hirarki = "periode_atas"
    elif all_first_are_periods:
        posisi_hirarki = "periode_atas"
        for cn in sorted_col_nums:
            p_tok = normalize_period_token(data_col_headers[cn][0])
            if p_tok not in periods:
                periods.append(p_tok)
        seen_kar = set()
        for cn in sorted_col_nums:
            lower_path = data_col_headers[cn][1:]
            if not lower_path:
                continue
            ind_parts = []
            en_parts = []
            for lp in lower_path:
                u_ind, u_en = split_bilingual(lp)
                clean_lp = lp.lower().replace(' ', '')
                if ('+' in lp) and any(k in clean_lp for k in ['laki-laki+perempuan', 'laki+perempuan', 'male+female', 'pria+wanita', 'l+p', 'wni+wna', 'negeri+swasta']):
                    u_ind = "Jumlah"
                    u_en = "Total"
                ind_parts.append(u_ind)
                en_parts.append(u_en)
            kar_ind = " || ".join(ind_parts)
            kar_en = " || ".join(en_parts)
            if kar_ind.lower() not in seen_kar:
                seen_kar.add(kar_ind.lower())
                karakteristik_items.append({"item_ind": kar_ind, "item_en": kar_en})
    else:
        posisi_hirarki = "kolom_atas"
        for cn in sorted_col_nums:
            path = data_col_headers[cn]
            ind_parts = []
            en_parts = []
            for p_str in path:
                u_ind, u_en = split_bilingual(p_str)
                clean_p = p_str.lower().replace(' ', '')
                if ('+' in p_str) and any(k in clean_p for k in ['laki-laki+perempuan', 'laki+perempuan', 'male+female', 'pria+wanita', 'l+p', 'wni+wna', 'negeri+swasta']):
                    u_ind = "Jumlah"
                    u_en = "Total"
                ind_parts.append(u_ind)
                en_parts.append(u_en)
            kar_ind = " || ".join(ind_parts)
            kar_en = " || ".join(en_parts)
            karakteristik_items.append({"item_ind": kar_ind, "item_en": kar_en})

    rh_ind, rh_en = split_bilingual(row_header_raw)

    try:
        from normalizer import resolve_jateng_entity
    except ImportError:
        try:
            from python_engine.normalizer import resolve_jateng_entity
        except ImportError:
            resolve_jateng_entity = None

    if resolve_jateng_entity:
        kabkota_matches = 0
        for r in extracted_rows:
            code, name = resolve_jateng_entity(r.get("ind", ""))
            if code and code != "33.00":
                kabkota_matches += 1
        is_kabkota = (kabkota_matches >= 20) or (len(extracted_rows) >= 25 and kabkota_matches >= len(extracted_rows) * 0.7)
    else:
        row_text_concat = " ".join([r["ind"].lower() for r in extracted_rows])
        is_kabkota = all(k in row_text_concat for k in ["cilacap", "banyumas", "purbalingga", "semarang", "tegal"])

    konfigurasi_total = "default"
    if extracted_rows:
        last_r = extracted_rows[-1]["ind"].lower()
        if "jawa tengah" in last_r:
            konfigurasi_total = "jawa_tengah"
        elif last_r in ("jumlah", "total", "jumlah/total"):
            konfigurasi_total = "jumlah"

    max_detected_decimals = 0
    try:
        try:
            from normalizer import parse_num
        except ImportError:
            from python_engine.normalizer import parse_num

        if parse_num:
            for pa in page_analyze:
                grid = pa["grid"]
                c_row_idx = pa["col_num_idx"]
                data_c_indices = sorted([ci for ci, cn in pa["col_nums"].items() if cn > 1])
                if not data_c_indices:
                    continue
                first_data_ci = data_c_indices[0]
                for r in grid[c_row_idx + 1:]:
                    for ci in range(first_data_ci, len(r)):
                        c_raw = clean_cell_text(r[ci])
                        v = parse_num(c_raw, is_pdf=True)
                        if v is not None and isinstance(v, float) and not v.is_integer():
                            if not re.match(r'^-?[1-9]\d{0,2}(\.\d{3})+$', c_raw):
                                m_dec = re.search(r'[,.](\d+)\s*$', c_raw)
                                if m_dec:
                                    d_len = len(m_dec.group(1))
                                    if 0 < d_len <= 4 and d_len > max_detected_decimals:
                                        max_detected_decimals = d_len
                                        if max_detected_decimals >= 4:
                                            break
                    if max_detected_decimals >= 4:
                        break
                if max_detected_decimals >= 4:
                    break
    except Exception as e:
        sys.stderr.write(f"[WARN] auto_config_pdf decimal check: {e}\n")

    return {
        "status": "success",
        "row_header": {"ind": rh_ind or "Rincian", "en": rh_en or "Description"},
        "is_kabkota": is_kabkota,
        "rows_count": len(extracted_rows),
        "rows": extracted_rows,
        "karakteristik_count": len(karakteristik_items),
        "karakteristik": karakteristik_items,
        "periods": periods,
        "posisi_hirarki": posisi_hirarki,
        "konfigurasi_total": konfigurasi_total,
        "desimal": max_detected_decimals,
    }

def _clean_token_set(text):
    text = text.lower().replace('-', ' ').replace('_', ' ').replace('||', ' ').replace('/', ' ')
    if '+' in text:
        text += ' jumlah total'
    text = text.replace('kabupatenkota', 'kabupaten kota')
    text = text.replace('kabkota', 'kabupaten kota')
    text = text.replace('lakilaki', 'laki laki')
    text = text.replace('pria', 'laki laki')
    text = text.replace('wanita', 'perempuan')
    text = text.replace('rs', 'rumah sakit')
    text = text.replace('kementrian', 'kementerian')
    return set(re.findall(r'\b[a-z0-9]+\b', text))

def map_api_columns_to_pdf(pdf_path, pages, api_columns, target_year=None):
    pdf_res = extract_table_structure_from_pdf(pdf_path, pages)
    if not pdf_res or pdf_res.get("status") != "success" or not pdf_res.get("karakteristik"):
        return None

    kar = pdf_res["karakteristik"]
    row_hdr = pdf_res.get("row_header", {"ind": "Kabupaten/Kota", "en": "Regency/Municipality"})

    ignore_keys = ["id", "tahun", "tahun_data", "year", "periode", "kode_wilayah", "kode_kabupatenkota", "kode_bps", "kode_kemendagri", "urutan"]
    
    row_hdr_ind = row_hdr.get("ind", "Kabupaten/Kota")
    row_hdr_en = row_hdr.get("en", "Regency/Municipality")
    row_hdr_tokens = _clean_token_set(row_hdr_ind) | _clean_token_set(row_hdr_en)

    mapped = []
    metric_cols = []
    row_col_assigned = False

    for ac in api_columns:
        low = ac.lower()
        if low in ignore_keys:
            mapped.append({
                "key": ac,
                "label_id": ac,
                "label_en": "",
                "visible": False,
                "format": "number"
            })
            continue

        ac_tokens = _clean_token_set(ac)
        is_region = any(rw in ac_tokens for rw in ["wilayah", "kabupaten", "kota", "kabkota"])
        is_row_header_match = bool(ac_tokens.intersection(row_hdr_tokens))
        is_metric_prefix = any(low.startswith(pfx) for pfx in ["jumlah", "nilai", "total", "banyak", "luas", "produksi", "persen", "angka", "kasus"])

        # Identifikasi kolom dimensi baris (kategori pertama / row header)
        if not row_col_assigned and (is_region or is_row_header_match or not is_metric_prefix):
            mapped.append({
                "key": ac,
                "label_id": row_hdr_ind,
                "label_en": row_hdr_en,
                "visible": True,
                "format": "text"
            })
            row_col_assigned = True
        else:
            metric_cols.append(ac)

    # Filter tahun pada karakteristik jika di PDF terdapat banyak tahun (contoh 2024 & 2025 di halaman terpisah)
    years_in_kar = sorted(list(set(re.findall(r'\b(20\d\d)\b', " ".join(k["item_ind"] for k in kar)))))
    if len(years_in_kar) > 1 and len(metric_cols) <= len(kar) // len(years_in_kar) + 1:
        chosen_year = target_year if target_year in years_in_kar else years_in_kar[-1]
        filtered_kar = [k for k in kar if chosen_year in k["item_ind"]]
        if len(filtered_kar) >= len(metric_cols):
            kar = filtered_kar

    used_k_indices = set()
    for m_idx, ac in enumerate(metric_cols):
        ac_tokens = _clean_token_set(ac)
        has_tt_ac = "tempat tidur" in " ".join(ac_tokens) or "tidur" in ac_tokens

        best_k = None
        best_score = -999
        best_k_idx = -1

        for k_idx, k in enumerate(kar):
            if k_idx in used_k_indices:
                continue
            k_ind = k["item_ind"].replace("kementrian", "kementerian").lower()
            k_tokens = _clean_token_set(k_ind)
            has_tt_k = "tempat tidur" in k_ind
            if has_tt_ac != has_tt_k:
                continue

            overlap = len(ac_tokens.intersection(k_tokens)) * 5

            # Keyword specific weighting
            if "laki" in ac_tokens and "laki" in k_tokens:
                overlap += 12
            if "perempuan" in ac_tokens and "perempuan" in k_tokens:
                overlap += 12
            if "jumlah" in ac_tokens and "jumlah" in k_tokens and "laki" not in ac_tokens and "perempuan" not in ac_tokens:
                overlap += 12

            for kw in ["swasta", "provinsi", "polri", "kabupaten", "lain"]:
                if (kw in ac_tokens) != (kw in k_tokens):
                    overlap -= 15

            if ("total" in ac_tokens) != ("total" in k_tokens or "jumlah" in k_ind):
                overlap -= 5

            if "sub" not in ac_tokens and "sub" in k_tokens:
                overlap -= 15

            if len(metric_cols) > 0 and len(kar) > 0:
                rel_pos_diff = abs((m_idx / len(metric_cols)) - (k_idx / len(kar)))
                overlap -= rel_pos_diff * 3

            if overlap > best_score:
                best_score = overlap
                best_k = k
                best_k_idx = k_idx

        if best_k and best_score > -50:
            used_k_indices.add(best_k_idx)
            mapped.append({
                "key": ac,
                "label_id": best_k["item_ind"],
                "label_en": best_k["item_en"],
                "visible": True,
                "format": "number"
            })
        else:
            mapped.append({
                "key": ac,
                "label_id": ac.replace("_", " ").title(),
                "label_en": "",
                "visible": True,
                "format": "number"
            })

    return mapped

def safe_load_json_arg(arg_str):
    if not arg_str:
        return None
    try:
        return json.loads(arg_str)
    except Exception:
        pass
    try:
        import base64
        return json.loads(base64.b64decode(arg_str.encode('ascii')).decode('utf-8'))
    except Exception:
        return None

if __name__ == "__main__":
    if len(sys.argv) >= 4:
        pdf_p = sys.argv[1]
        pages_arg = safe_load_json_arg(sys.argv[2]) or []
        api_cols_arg = safe_load_json_arg(sys.argv[3]) or []
        target_year_arg = sys.argv[4].strip() if len(sys.argv) >= 5 and sys.argv[4].strip() else None
        mapped_cols = map_api_columns_to_pdf(pdf_p, pages_arg, api_cols_arg, target_year=target_year_arg)
        if mapped_cols is not None:
            print("===JSON_START===" + json.dumps(mapped_cols, ensure_ascii=False) + "===JSON_END===")
        else:
            print("===JSON_START===null===JSON_END===")
    elif len(sys.argv) >= 3:
        pdf_p = sys.argv[1]
        pages_arg = safe_load_json_arg(sys.argv[2]) or []
        res = extract_table_structure_from_pdf(pdf_p, pages_arg)
        print("===JSON_START===" + json.dumps(res, ensure_ascii=False) + "===JSON_END===")
