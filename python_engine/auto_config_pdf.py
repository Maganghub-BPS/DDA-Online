import sys
import os
import re
import json
import fitz

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
    s = re.sub(r'\b((?:19|20)\d{2})[1-9](?:,[1-9])?\b', r'\1', s)
    s = re.sub(r'(?<=[a-zA-Z])[1-9](?=[/\s,]|$)', '', s)
    return re.sub(r'\s+', ' ', s).strip()

def split_bilingual(text):
    if not text:
        return ("", "")
    lines = [clean_footnote_markers(l) for l in text.split("\n") if clean_footnote_markers(l)]
    if not lines:
        return ("", "")

    if len(lines) == 1:
        s = lines[0]
        if '/' in s and not re.match(r'^\d{4}/\d{4}$', s):
            low = s.lower()
            if not any(k in low for k in ['kabupaten/kota', 'desa/kelurahan', 'lereng/puncak', 'negeri/swasta', 'laki/perempuan', 'kota/kabupaten', 'u/d', 's/d']):
                parts = [p.strip() for p in s.split('/', 1)]
                if len(parts) == 2 and parts[0] and parts[1]:
                    return (parts[0], parts[1])
        return (s, s)

    joined = " ".join(lines)
    if '/' in joined and not re.search(r'\d{4}/\d{4}', joined):
        low = joined.lower()
        if not any(k in low for k in ['kabupaten/kota', 'desa/kelurahan', 'lereng/puncak', 'regency/municipality']):
            parts = [p.strip() for p in joined.split('/', 1)]
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
        tabs = page.find_tables()
        if not tabs.tables:
            continue
        best_tab = max(tabs.tables, key=lambda t: len(t.extract()) * (len(t.extract()[0]) if t.extract() else 0))
        raw_grid = best_tab.extract()
        if not raw_grid or len(raw_grid) < 3:
            continue

        col_num_idx = -1
        col_nums = {}
        for r_idx, row in enumerate(raw_grid[:10]):
            matches = 0
            temp_map = {}
            for c_idx, cell in enumerate(row):
                c_clean = clean_cell_text(cell)
                m = re.search(r'\((\d+)\)', c_clean)
                if m:
                    matches += 1
                    temp_map[c_idx] = int(m.group(1))
            if matches >= 2 and 1 in temp_map.values():
                col_num_idx = r_idx
                col_nums = temp_map
                break

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

    for pa in page_analyze:
        grid = pa["grid"]
        c_idx_map = pa["col_nums"]
        c_row_idx = pa["col_num_idx"]
        header_rows = grid[:c_row_idx]

        data_c_indices = sorted([ci for ci, cn in c_idx_map.items() if cn > 1])
        if not data_c_indices:
            continue
        first_data_ci = data_c_indices[0]

        if not row_header_raw and header_rows:
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
            if cn in data_col_headers:
                continue
            col_path = []
            for hr_ff in ffilled_headers:
                val = hr_ff[ci] if ci < len(hr_ff) else ""
                if val and (not col_path or col_path[-1] != val):
                    col_path.append(val)
            data_col_headers[cn] = col_path

    sorted_col_nums = sorted(data_col_headers.keys())
    if not sorted_col_nums:
        return {"status": "error", "message": "Kolom data (2)..(N) tidak ditemukan di PDF."}

    first_page_col_nums = set([cn for ci, cn in first_pa["col_nums"].items() if cn > 1])
    extracted_rows = []
    seen_row_keys = set()

    for pa in page_analyze:
        pa_col_nums = set([cn for ci, cn in pa["col_nums"].items() if cn > 1])
        if pa["page"] != first_pa["page"] and pa_col_nums != first_page_col_nums:
            continue
        grid = pa["grid"]
        c_row_idx = pa["col_num_idx"]
        data_c_indices = sorted([ci for ci, cn in pa["col_nums"].items() if cn > 1])
        first_data_ci = data_c_indices[0]

        for r in grid[c_row_idx + 1:]:
            label_parts = []
            for ci in range(first_data_ci):
                t = clean_cell_text(r[ci])
                if t:
                    label_parts.append(t)
            full_label = "\n".join(label_parts).strip()
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
                    ind_parts.append(u_ind)
                    en_parts.append(u_en)
                kar_ind = " || ".join(ind_parts)
                kar_en = " || ".join(en_parts)
                if kar_ind.lower() not in seen_kar:
                    seen_kar.add(kar_ind.lower())
                    karakteristik_items.append({"item_ind": kar_ind, "item_en": kar_en})
        else:
            posisi_hirarki = "periode_atas"
    else:
        posisi_hirarki = "kolom_atas"
        for cn in sorted_col_nums:
            path = data_col_headers[cn]
            ind_parts = []
            en_parts = []
            for p_str in path:
                u_ind, u_en = split_bilingual(p_str)
                ind_parts.append(u_ind)
                en_parts.append(u_en)
            kar_ind = " || ".join(ind_parts)
            kar_en = " || ".join(en_parts)
            karakteristik_items.append({"item_ind": kar_ind, "item_en": kar_en})

    rh_ind, rh_en = split_bilingual(row_header_raw)

    row_text_concat = " ".join([r["ind"].lower() for r in extracted_rows])
    is_kabkota = all(k in row_text_concat for k in ["cilacap", "banyumas", "purbalingga", "semarang", "tegal"])

    konfigurasi_total = "default"
    if extracted_rows:
        last_r = extracted_rows[-1]["ind"].lower()
        if "jawa tengah" in last_r:
            konfigurasi_total = "jawa_tengah"
        elif last_r in ("jumlah", "total", "jumlah/total"):
            konfigurasi_total = "jumlah"

    has_decimals = False
    try:
        from python_engine.normalizer import parse_num
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
                        has_decimals = True
                        break
                if has_decimals:
                    break
            if has_decimals:
                break
    except Exception:
        pass

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
        "desimal": 2 if has_decimals else 0,
    }

if __name__ == "__main__":
    if len(sys.argv) >= 3:
        pdf_p = sys.argv[1]
        pages_arg = json.loads(sys.argv[2])
        res = extract_table_structure_from_pdf(pdf_p, pages_arg)
        print("===JSON_START===" + json.dumps(res, ensure_ascii=False) + "===JSON_END===")
