# Untuk memformat ke Excel hasil dari perbandingan tabel
import sys
import os
import json
import re
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

sys.path.append(os.path.dirname(os.path.abspath(__file__)))
from compare_table import compare_head_to_head

FONT_FAMILY = "Times New Roman"

HEADER_TOP = PatternFill(start_color="CBD5E1", end_color="CBD5E1", fill_type="solid")   # Slate 300 (Grup Tingkat Atas)
HEADER_GRAY = PatternFill(start_color="E2E8F0", end_color="E2E8F0", fill_type="solid")  # Slate 200 (Sub-header / Daun)
PDF_COL_HDR = PatternFill(start_color="E0F2FE", end_color="E0F2FE", fill_type="solid")  # Sky 100
DB_COL_HDR = PatternFill(start_color="F1F5F9", end_color="F1F5F9", fill_type="solid")   # Slate 100

DIFF_FILL = PatternFill(start_color="FEE2E2", end_color="FEE2E2", fill_type="solid")    # Soft Red
DIFF_FONT = Font(name=FONT_FAMILY, size=11, bold=True, color="991B1B")

PDF_ONLY_FILL = PatternFill(start_color="FEF3C7", end_color="FEF3C7", fill_type="solid")# Soft Yellow
PDF_ONLY_FONT = Font(name=FONT_FAMILY, size=11, bold=True, color="92400E")

MATCH_FILL = PatternFill(start_color="FFFFFF", end_color="FFFFFF", fill_type="solid")
MATCH_FONT = Font(name=FONT_FAMILY, size=11, color="000000")

BORDER_THIN = Border(
    left=Side(style='thin', color='475569'),
    right=Side(style='thin', color='475569'),
    top=Side(style='thin', color='475569'),
    bottom=Side(style='thin', color='475569')
)

def get_instansi_name(tnum, id_db=None, preloaded_db=None):
    uk_path = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "writable", "uploads", "dda_unitkerja.json")
    if os.path.exists(uk_path):
        try:
            with open(uk_path, "r", encoding="utf-8") as f:
                uk_map = json.load(f)
                if id_db and str(id_db) in uk_map:
                    return uk_map[str(id_db)]
                clean_t = re.sub(r'^(?:tabel|table)\s*', '', str(tnum), flags=re.I).strip().rstrip('.')
                if clean_t in uk_map:
                    return uk_map[clean_t]
        except Exception:
            pass
    if preloaded_db:
        for r in preloaded_db:
            if (id_db and r.get("id_db") == id_db) or (r.get("nomor_tabel") and str(r.get("nomor_tabel")).strip() == str(tnum).strip()):
                inst = r.get("instansi") or r.get("unitkerja") or r.get("unitkerja_ind")
                j_inst = r.get("jenis_instansi")
                if inst:
                    return f"{inst} ({j_inst.upper()})" if j_inst else inst
    return "-"

def is_artifact_row(w):
    if not w:
        return True
    w_clean = str(w).strip().lower()
    if w_clean in ("partai politik", "rincian", "uraian", "kabupaten/kota", "nama kabupaten/kota", 
                   "bulan", "month", "tahun", "year", "periode", "period", "satuan", "unit", 
                   "indikator", "variabel", "dimensi", "kategori", "none", "-", "–", "—", "−"):
        return True
    if w_clean.startswith("none ") or w_clean.endswith(" none") or w_clean == "none none":
        return True
    if w_clean.startswith("tabel ") or w_clean.startswith("table ") or w_clean.startswith("bab ") or w_clean.startswith("chapter "):
        return True
    if re.match(r'^(?:jumlah|total)\s+(?:19\d{2}|20(?:0\d|1\d|2[0-4]))$', w_clean):
        return True
    return False


def get_real_pdf_only(res):
    if not isinstance(res, dict):
        return []
    pdf_only = res.get("pdf_only", [])
    diffs = res.get("diffs", [])
    out = []
    for p in pdf_only:
        w = p.get("wilayah")
        if is_artifact_row(w):
            continue
        w_low = str(w or "").strip().lower()
        if not diffs and w_low in ("jawa tengah", "total jawa tengah", "provinsi jawa tengah", "jumlah", "total", "jumlah/total", "jumlah total"):
            continue
        note_low = str(p.get("note", "")).lower()
        if any(k in note_low for k in ("data historis", "tahun ajaran lalu", "tidak dimuat di database", "total jawa tengah tercetak")):
            continue
        out.append(p)
    return out


def _collect_result_years(res):
    """Kumpulkan seluruh tahun data unik dari hasil komparasi (terurut rapi)."""
    yrs = []
    seen = set()
    for bucket in ("matches", "diffs", "pdf_only"):
        for it in (res.get(bucket) or []):
            if not isinstance(it, dict):
                continue
            y = str(it.get("tahun") or "").strip()
            if not y:
                m_y = re.search(
                    r'\b((?:19|20)\d{2}(?:/(?:19|20)\d{2})?)\b',
                    f"{it.get('metric', '')} {it.get('src_header', '')} {it.get('pdf_header', '')}"
                )
                y = m_y.group(1) if m_y else ""
            if y and y not in seen:
                seen.add(y)
                yrs.append(y)
    if not yrs and res.get("target_year"):
        yrs.append(str(res.get("target_year")).strip())
    return sorted(yrs)


def _extract_col_levels(item, include_year_level=False, year_as_outer=True):
    """Bangun tuple hierarki kolom unik (anti-timpa & siap di-merge multi-baris di Excel)."""
    raw_hier = item.get("kolom_hierarki")
    if isinstance(raw_hier, list) and any(str(x).strip() for x in raw_hier):
        levels = [str(x).strip() for x in raw_hier if str(x).strip()]
    else:
        sec = (item.get("section") or "").strip()
        m = (item.get("metric") or "").strip()
        if sec and sec not in ("-", "None") and not sec.lower().startswith("halaman") and sec.lower() not in m.lower():
            combined = f"{sec} - {m}" if m else sec
        else:
            combined = m or "Data"
        levels = [p.strip() for p in re.split(r'\s*(?:>|›|\|\||\s[-–—]\s)\s*', combined) if p.strip()]
        if not levels:
            levels = ["Data"]

    yr = str(item.get("tahun") or "").strip()
    if include_year_level and yr:
        if not any(yr in lv for lv in levels):
            levels = ([yr] + levels) if year_as_outer else (levels + [yr])

    # Batasi kedalaman wajar (1..5 tingkat)
    if len(levels) > 5:
        levels = levels[:4] + [" - ".join(levels[4:])]
    return tuple(levels)


def populate_matrix_sheet(ws, tnum, title, src_type, res, instansi="", include_pdf_only=False):
    diffs = res.get("diffs", [])
    matches = res.get("matches", [])
    # Sesuai arahan Bos Anas: Data yang hanya ada di PDF tidak ditampilkan di rekapan jika tabel memiliki data pembanding
    if include_pdf_only or (not diffs and not matches):
        real_pdf_only = get_real_pdf_only(res)
    else:
        real_pdf_only = []
    total_diffs = len(diffs) + len(real_pdf_only)
    total_all = len(diffs) + len(matches) + len(real_pdf_only)

    if not instansi or instansi == "-":
        instansi = get_instansi_name(tnum, res.get("id_db"))

    result_years = _collect_result_years(res)
    years_display = ", ".join(result_years) if result_years else "-"
    multi_year_cols = len(result_years) >= 2

    # Deteksi otomatis apakah tahun berada di tingkat luar (Tahun » Metrik, mis. 4.1.3)
    # atau tingkat dalam (Metrik » Tahun, mis. 9.1.2 Mobil Penumpang: 2023 | 2024 | 2025)
    year_as_outer = True
    if multi_year_cols:
        sample_seq = []
        seen_seq = set()
        for it in (matches + diffs):
            w_s = (it.get("wilayah") or "-").strip()
            if is_artifact_row(w_s):
                continue
            y_s = str(it.get("tahun") or "").strip()
            h_s = tuple(it.get("kolom_hierarki") or [it.get("metric") or ""])
            sig = (y_s, h_s)
            if sig not in seen_seq:
                seen_seq.add(sig)
                sample_seq.append(sig)
        same_yr_adj = sum(1 for i in range(len(sample_seq) - 1) if sample_seq[i][0] and sample_seq[i][0] == sample_seq[i + 1][0])
        same_h_adj = sum(1 for i in range(len(sample_seq) - 1) if sample_seq[i][1] and sample_seq[i][1] == sample_seq[i + 1][1])
        if same_h_adj > same_yr_adj:
            year_as_outer = False

    # 1. Row 1: Nomor & Judul Tabel Lengkap (Lega & Tidak Terpotong)
    ws.row_dimensions[1].height = 26
    c_title = ws.cell(1, 1, f"Tabel {tnum} : {title}")
    c_title.font = Font(name=FONT_FAMILY, size=12, bold=True, color="0F172A")
    c_title.alignment = Alignment(horizontal="left", vertical="center")
    
    # 2. Row 2: Sumber Data & Tahun Data
    ws.row_dimensions[2].height = 20
    c_src = ws.cell(2, 1, f"Sumber Data: {src_type}   |   Tahun Data: {years_display}")
    c_src.font = Font(name=FONT_FAMILY, size=10, bold=False, color="475569")
    c_src.alignment = Alignment(horizontal="left", vertical="center")

    # 3. Row 3: Instansi / OPD (Persis di bawah Sumber Data)
    ws.row_dimensions[3].height = 20
    c_inst = ws.cell(3, 1, f"Instansi / OPD: {instansi}")
    c_inst.font = Font(name=FONT_FAMILY, size=10, bold=True, color="1E3A8A")
    c_inst.alignment = Alignment(horizontal="left", vertical="center")
    
    # 4. Row 4: Status Validasi
    ws.row_dimensions[4].height = 20
    if total_diffs > 0:
        st_text = f"Status: Ditemukan {total_diffs} data berbeda (dari total {total_all} angka)"
        st_color = "991B1B"
    elif total_all == 0:
        st_text = "Status: Data Kosong / Strip (–) di PDF & Database"
        st_color = "D97706"
    else:
        st_text = f"Status: Cocok 100% (Semua {total_all} angka sama persis)"
        st_color = "166534"
    c_st = ws.cell(4, 1, st_text)
    c_st.font = Font(name=FONT_FAMILY, size=10, bold=True, color=st_color)
    c_st.alignment = Alignment(horizontal="left", vertical="center")

    # 5. Row 5: Petunjuk Warna Lengkap (Merah, Kuning, Putih)
    ws.row_dimensions[5].height = 20
    c_leg = ws.cell(5, 1, "Petunjuk Warna:  [Merah Muda] Selisih Nilai (PDF ≠ Database)   |   [Kuning] Hanya Ada di PDF   |   [Putih] Cocok 100%")
    c_leg.font = Font(name=FONT_FAMILY, size=9.5, italic=True, color="334155")
    c_leg.alignment = Alignment(horizontal="left", vertical="center")

    # 6. Row 6: Spasi Pemisah Visual
    ws.row_dimensions[6].height = 10

    # Kumpulkan urutan kolom asli (TANPA memindahkan subtotal tengah tabel ke paling kanan)
    # serta gunakan tuple hierarki unik + anti-timpa untuk pasangan (wilayah, col_key).
    cols_order = []
    cols_seen = set()
    cell_map = {}  # (wilayah, col_key) -> dict
    item_col_key_cache = {}  # id(item) -> resolved col_key

    def _register_cell(item, w, base_key, payload):
        # Jika (w, base_key) sudah terisi dengan item berbeda (kasus 2 kolom berbeda namun berlabel identik),
        # pecah ke slot disambiguasi unik agar TIDAK pernah saling menimpa!
        resolved_key = base_key
        if (w, resolved_key) in cell_map:
            existing = cell_map[(w, resolved_key)]
            # Timpa hanya jika diff menimpa match dengan coord identik; selain itu buat slot kolom terpisah
            same_coord = (
                item.get("coord") and existing.get("_coord")
                and item.get("coord") == existing.get("_coord")
            )
            if not (payload.get("is_diff") and not existing.get("is_diff") and same_coord):
                suffix_idx = 2
                while True:
                    cand_key = base_key[:-1] + (f"{base_key[-1]} ({suffix_idx})",)
                    if (w, cand_key) not in cell_map:
                        resolved_key = cand_key
                        break
                    suffix_idx += 1
        if resolved_key not in cols_seen:
            cols_seen.add(resolved_key)
            cols_order.append(resolved_key)
        payload["_coord"] = item.get("coord")
        cell_map[(w, resolved_key)] = payload
        item_col_key_cache[id(item)] = resolved_key

    # Pre-scan urutan kolom asli dari matches + diffs secara kronologis
    for item in (matches + diffs):
        w_chk = (item.get("wilayah") or "-").strip()
        if is_artifact_row(w_chk):
            continue
        ck = _extract_col_levels(item, include_year_level=multi_year_cols, year_as_outer=year_as_outer)
        if ck not in cols_seen:
            cols_seen.add(ck)
            cols_order.append(ck)

    # 1. Masukkan data matches
    for m in matches:
        w = (m.get("wilayah") or "-").strip()
        if is_artifact_row(w):
            continue
        ck = _extract_col_levels(m, include_year_level=multi_year_cols, year_as_outer=year_as_outer)
        _register_cell(m, w, ck, {
            "pdf_val": m.get("pdf_val", m.get("val", "")),
            "db_val": m.get("src_val", m.get("val", "")),
            "is_diff": False,
            "is_pdf_only": False,
        })

    # 2. Masukkan data diffs
    for d in diffs:
        w = (d.get("wilayah") or "-").strip()
        if is_artifact_row(w):
            continue
        ck = _extract_col_levels(d, include_year_level=multi_year_cols, year_as_outer=year_as_outer)
        _register_cell(d, w, ck, {
            "pdf_val": d.get("pdf_val", ""),
            "db_val": d.get("src_val", ""),
            "is_diff": True,
            "is_pdf_only": False,
        })

    # 3. Masukkan data pdf_only (hanya jika tabel tidak punya pembanding atau diminta eksplisit)
    for p in real_pdf_only:
        w = (p.get("wilayah") or "-").strip()
        if is_artifact_row(w):
            continue
        ck = _extract_col_levels(p, include_year_level=multi_year_cols, year_as_outer=year_as_outer)
        vals = p.get("values", [])
        pdf_v = vals[0] if vals else p.get("pdf_val", "")
        _register_cell(p, w, ck, {
            "pdf_val": pdf_v,
            "db_val": "-",
            "is_diff": True,
            "is_pdf_only": True,
        })

    # Kumpulkan seluruh baris kandidat
    all_candidate_rows = []
    for (w, _c) in cell_map.keys():
        if w not in all_candidate_rows:
            all_candidate_rows.append(w)

    # OPSI B: HANYA TAMPILKAN BARIS YANG MEMILIKI SELISIH NILAI (Baris cocok 100% dihapus total)
    diff_rows = [w for w in all_candidate_rows if any(cell_map.get((w, c), {}).get("is_diff", False) for c in cols_order)]
    if not diff_rows:
        diff_rows = all_candidate_rows  # Fallback aman jika tabel tidak punya selisih

    # Susun urutan baris: Data bermasalah di atas, baris Jumlah/Total selalu di paling bawah
    non_tot_rows = [w for w in diff_rows if not any(k in str(w).lower() for k in ("jumlah", "total"))]
    tot_rows = [w for w in diff_rows if any(k in str(w).lower() for k in ("jumlah", "total"))]
    rows_order = non_tot_rows + tot_rows

    # 7. Matrix Headers Bertingkat (Multi-Row Merged sesuai hierarki kolom asli)
    hdr_start_row = 7
    header_depth = max((len(ck) for ck in cols_order), default=1)
    pdf_db_row = hdr_start_row + header_depth

    # Kolom A: merge vertikal dari hdr_start_row s.d. pdf_db_row
    ws.merge_cells(start_row=hdr_start_row, start_column=1, end_row=pdf_db_row, end_column=1)
    cA7 = ws.cell(hdr_start_row, 1, "Baris (Wilayah / Kategori)")
    cA7.font = Font(name=FONT_FAMILY, size=11, bold=True)
    cA7.fill = HEADER_TOP if header_depth >= 2 else HEADER_GRAY
    cA7.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
    for r_b in range(hdr_start_row, pdf_db_row + 1):
        ws.cell(r_b, 1).border = BORDER_THIN
        ws.cell(r_b, 1).fill = HEADER_TOP if header_depth >= 2 else HEADER_GRAY

    # Pad setiap col_key ke panjang header_depth (nilai None menandakan vertical merge dari level terakhir)
    padded_cols = [list(ck) + [None] * (header_depth - len(ck)) for ck in cols_order]
    n_data_cols = len(cols_order)

    # Render baris header tingkat 0 .. header_depth-1 dengan horizontal & vertical merge yang presisi
    merged_tracker = set()
    for lvl in range(header_depth):
        r_excel = hdr_start_row + lvl
        ws.row_dimensions[r_excel].height = 24 if lvl < header_depth - 1 else 26
        c_idx = 0
        while c_idx < n_data_cols:
            lbl = padded_cols[c_idx][lvl]
            if lbl is None:
                c_idx += 1
                continue

            # Hitung span horizontal: kolom bersebelahan dengan prefix ancestor [:lvl+1] identik
            span_end = c_idx
            is_leaf_here = (lvl == header_depth - 1) or (padded_cols[c_idx][lvl + 1] is None)
            if not is_leaf_here:
                prefix = padded_cols[c_idx][:lvl + 1]
                while span_end + 1 < n_data_cols and padded_cols[span_end + 1][:lvl + 1] == prefix:
                    # Jangan gabungkan dua daun berbeda pada level daun
                    next_is_leaf = (lvl == header_depth - 1) or (padded_cols[span_end + 1][lvl + 1] is None)
                    if next_is_leaf:
                        break
                    span_end += 1

            # Hitung span vertikal bila kolom ini lebih dangkal dari header_depth
            v_end_lvl = lvl
            if span_end == c_idx:
                while v_end_lvl + 1 < header_depth and padded_cols[c_idx][v_end_lvl + 1] is None:
                    v_end_lvl += 1

            excel_c_start = 2 + (c_idx * 2)
            excel_c_end = 2 + (span_end * 2) + 1
            excel_r_end = hdr_start_row + v_end_lvl

            if (r_excel, excel_c_start) not in merged_tracker:
                if excel_r_end > r_excel or excel_c_end > excel_c_start:
                    ws.merge_cells(start_row=r_excel, start_column=excel_c_start, end_row=excel_r_end, end_column=excel_c_end)
                fill_use = HEADER_TOP if (header_depth >= 2 and lvl == 0 and not is_leaf_here) else HEADER_GRAY
                cell_h = ws.cell(r_excel, excel_c_start, lbl)
                cell_h.font = Font(name=FONT_FAMILY, size=10.5, bold=True, color="0F172A")
                cell_h.fill = fill_use
                cell_h.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
                for rr in range(r_excel, excel_r_end + 1):
                    for cc in range(excel_c_start, excel_c_end + 1):
                        ws.cell(rr, cc).border = BORDER_THIN
                        ws.cell(rr, cc).fill = fill_use
                        merged_tracker.add((rr, cc))

            c_idx = span_end + 1

    # Baris paling bawah header: PDF | Database untuk tiap kolom daun
    ws.row_dimensions[pdf_db_row].height = 20
    curr_col = 2
    for _ in cols_order:
        c_pdf = ws.cell(pdf_db_row, curr_col, "PDF")
        c_pdf.font = Font(name=FONT_FAMILY, size=10, bold=True, color="0369A1")
        c_pdf.fill = PDF_COL_HDR
        c_pdf.alignment = Alignment(horizontal="center", vertical="center")
        c_pdf.border = BORDER_THIN

        c_db = ws.cell(pdf_db_row, curr_col + 1, "Database")
        c_db.font = Font(name=FONT_FAMILY, size=10, bold=True, color="166534")
        c_db.fill = DB_COL_HDR
        c_db.alignment = Alignment(horizontal="center", vertical="center")
        c_db.border = BORDER_THIN
        curr_col += 2

    # 8. Data Rows
    def format_and_align_cell(cell, val):
        cell.value = val
        cell.border = BORDER_THIN
        if isinstance(val, (int, float)):
            cell.alignment = Alignment(horizontal="right", vertical="center")
            if isinstance(val, int) or (isinstance(val, float) and val.is_integer()):
                cell.number_format = '#,##0'
            else:
                cell.number_format = '#,##0.00'
        else:
            try:
                s_clean = str(val).replace(',', '').strip()
                fval = float(s_clean)
                cell.value = int(fval) if fval.is_integer() else fval
                cell.alignment = Alignment(horizontal="right", vertical="center")
                cell.number_format = '#,##0' if fval.is_integer() else '#,##0.00'
            except (ValueError, TypeError):
                cell.alignment = Alignment(horizontal="center", vertical="center")

    r_start = pdf_db_row + 1
    for r_idx, w in enumerate(rows_order, r_start):
        ws.row_dimensions[r_idx].height = 22
        c_lbl = ws.cell(r_idx, 1, w)
        is_total_row = str(w).strip().lower() in ("jumlah", "total", "jumlah/total")
        c_lbl.font = Font(name=FONT_FAMILY, size=11, bold=is_total_row)
        c_lbl.alignment = Alignment(horizontal="left", vertical="center", indent=1)
        c_lbl.border = BORDER_THIN

        col_ptr = 2
        for c in cols_order:
            cell_data = cell_map.get((w, c), {})
            v_pdf = cell_data.get("pdf_val", "")
            v_db = cell_data.get("db_val", "")
            is_diff = cell_data.get("is_diff", False)
            is_pdf_only = cell_data.get("is_pdf_only", False)

            cP = ws.cell(r_idx, col_ptr)
            format_and_align_cell(cP, v_pdf)

            cW = ws.cell(r_idx, col_ptr + 1)
            format_and_align_cell(cW, v_db)

            if is_diff:
                if is_pdf_only:
                    cP.fill = PDF_ONLY_FILL
                    cP.font = PDF_ONLY_FONT
                    cW.fill = PDF_ONLY_FILL
                    cW.font = PDF_ONLY_FONT
                else:
                    cP.fill = DIFF_FILL
                    cP.font = DIFF_FONT
                    cW.fill = DIFF_FILL
                    cW.font = DIFF_FONT
            else:
                cP.fill = MATCH_FILL
                cP.font = MATCH_FONT
                cW.fill = MATCH_FILL
                cW.font = MATCH_FONT

            col_ptr += 2

    # Column Widths (Auto-Fit Kolom A)
    max_w_len = max([len(str(w)) for w in rows_order] + [len("Baris (Wilayah / Kategori)")]) if rows_order else 26
    col_a_width = max(32, min(max_w_len + 5, 60))
    ws.column_dimensions['A'].width = col_a_width
    for col_i in range(2, curr_col):
        let = get_column_letter(col_i)
        ws.column_dimensions[let].width = 14

    ws.freeze_panes = f"B{r_start}"


def export_table_to_excel(pdf_path, db_json_path, target_table, output_path, tolerance=0.0, id_db=None):
    res = compare_head_to_head(pdf_path, target_table, db_json_path, tolerance=tolerance, id_db=id_db)
    clean_target = re.sub(r'^(?:tabel|table)\s*', '', str(target_table), flags=re.I).strip().rstrip('.')

    wb = openpyxl.Workbook()
    ws = wb.active
    safe_title = f"Tabel {clean_target}"[:31]
    ws.title = safe_title

    if res.get("status") != "success":
        ws['A1'] = f"Status Validasi Tabel {clean_target}: {res.get('message', 'Error')}"
        wb.save(output_path)
        return output_path

    preloaded_db = None
    db_meta = {}
    if db_json_path and os.path.exists(db_json_path):
        try:
            with open(db_json_path, "r", encoding="utf-8") as f_db:
                preloaded_db = json.load(f_db)
            for row in (preloaded_db or []):
                if not isinstance(row, dict):
                    continue
                r_id = str(row.get("id_db") or "").strip()
                r_no = re.sub(r'^(?:tabel|table)\s*', '', str(row.get("nomor_tabel") or ""), flags=re.I).strip().rstrip('.')
                if (id_db and r_id == str(id_db).strip()) or (r_no and r_no == clean_target):
                    db_meta = row
                    break
        except Exception:
            preloaded_db = None

    judul = (
        res.get("judul_tabel")
        or res.get("judul_db")
        or res.get("judul_pdf")
        or db_meta.get("judul_tabel")
        or db_meta.get("judul_ind")
        or f"Tabel {clean_target}"
    )
    judul = re.sub(r'\s+', ' ', str(judul)).strip()
    src_type = res.get("source_type") or ("Tabel Dinamis" if db_meta.get("is_dynamic") else "API Satu Data")
    instansi = get_instansi_name(clean_target, id_db or res.get("id_db") or db_meta.get("id_db"), preloaded_db=preloaded_db)

    populate_matrix_sheet(ws, clean_target, judul, src_type, res, instansi=instansi)

    # Sheet 2 (Opsional jika ada selisih): Rincian Selisih
    diffs = res.get("diffs", [])
    matches = res.get("matches", [])
    pdf_only = res.get("pdf_only", []) if (not diffs and not matches) else []
    if diffs or pdf_only:
        ws2 = wb.create_sheet(title="⚠️ Rincian Selisih")
        ws2.cell(1, 1, f"DAFTAR SELISIH DATA - TABEL {clean_target}").font = Font(name=FONT_FAMILY, size=13, bold=True)
        ws2.cell(2, 1, judul).font = Font(name=FONT_FAMILY, size=10, italic=True)

        headers2 = ["No", "Baris (Wilayah / Kategori)", "Kolom (Hierarki)", "Tahun", "Nilai di Buku PDF", "Nilai di Database", "Selisih", "Status", "Catatan"]
        ws2.row_dimensions[4].height = 26
        for ci, h in enumerate(headers2, 1):
            c = ws2.cell(4, ci, h)
            c.font = Font(name=FONT_FAMILY, size=11, bold=True)
            c.fill = HEADER_GRAY
            c.alignment = Alignment(horizontal="center", vertical="center")
            c.border = BORDER_THIN

        counter = 1
        r_ptr = 5
        for d in diffs:
            ws2.row_dimensions[r_ptr].height = 22
            ws2.cell(r_ptr, 1, counter).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 2, d.get("wilayah", "")).alignment = Alignment(vertical="center", indent=1)
            ws2.cell(r_ptr, 3, d.get("kolom_path") or d.get("metric", "")).alignment = Alignment(vertical="center", indent=1)
            ws2.cell(r_ptr, 4, d.get("tahun") or res.get("target_year") or "-").alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 5, d.get("pdf_val", "")).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 6, d.get("src_val", "")).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 7, d.get("diff", "")).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 7).font = DIFF_FONT
            
            c_st = ws2.cell(r_ptr, 8, "BEDA NILAI")
            c_st.font = DIFF_FONT
            c_st.alignment = Alignment(horizontal="center", vertical="center")

            ws2.cell(r_ptr, 9, d.get("note", "")).alignment = Alignment(vertical="center", indent=1)

            for col_i in range(1, 10):
                ws2.cell(r_ptr, col_i).fill = DIFF_FILL
                ws2.cell(r_ptr, col_i).border = BORDER_THIN

            counter += 1
            r_ptr += 1

        for p in pdf_only:
            ws2.row_dimensions[r_ptr].height = 22
            ws2.cell(r_ptr, 1, counter).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 2, p.get("wilayah", "")).alignment = Alignment(vertical="center", indent=1)
            ws2.cell(r_ptr, 3, p.get("kolom_path") or p.get("metric", "")).alignment = Alignment(vertical="center", indent=1)
            ws2.cell(r_ptr, 4, p.get("tahun") or res.get("target_year") or "-").alignment = Alignment(horizontal="center", vertical="center")
            vals = p.get("values", [])
            ws2.cell(r_ptr, 5, vals[0] if vals else "").alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 6, "-").alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 7, "Data Baru").alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 7).font = PDF_ONLY_FONT
            
            c_st = ws2.cell(r_ptr, 8, "HANYA DI PDF")
            c_st.font = PDF_ONLY_FONT
            c_st.alignment = Alignment(horizontal="center", vertical="center")

            ws2.cell(r_ptr, 9, p.get("note", "")).alignment = Alignment(vertical="center", indent=1)

            for col_i in range(1, 10):
                ws2.cell(r_ptr, col_i).fill = PDF_ONLY_FILL
                ws2.cell(r_ptr, col_i).border = BORDER_THIN

            counter += 1
            r_ptr += 1

        ws2.column_dimensions['A'].width = 6
        ws2.column_dimensions['B'].width = 24
        ws2.column_dimensions['C'].width = 34
        ws2.column_dimensions['D'].width = 14
        ws2.column_dimensions['E'].width = 16
        ws2.column_dimensions['F'].width = 16
        ws2.column_dimensions['G'].width = 14
        ws2.column_dimensions['H'].width = 16
        ws2.column_dimensions['I'].width = 36

    wb.save(output_path)
    return output_path


if __name__ == "__main__":
    if len(sys.argv) < 5:
        print("Usage: python export_excel.py <pdf_path> <db_json_path> <target_table> <output_path> [tolerance] [id_db]")
        sys.exit(1)
        
    pdf_p = sys.argv[1]
    db_p = sys.argv[2]
    tbl = sys.argv[3]
    out_p = sys.argv[4]
    tol = float(sys.argv[5]) if len(sys.argv) > 5 else 0.0
    id_db = sys.argv[6] if len(sys.argv) > 6 else None
    
    export_table_to_excel(pdf_p, db_p, tbl, out_p, tolerance=tol, id_db=id_db)
    print("Export complete")
