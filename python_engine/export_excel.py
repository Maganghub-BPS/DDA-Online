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

HEADER_GRAY = PatternFill(start_color="D9D9D9", end_color="D9D9D9", fill_type="solid")
PDF_COL_HDR = PatternFill(start_color="E0F2FE", end_color="E0F2FE", fill_type="solid") # Sky 100
DB_COL_HDR = PatternFill(start_color="F1F5F9", end_color="F1F5F9", fill_type="solid")  # Slate 100

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

    # 1. Row 1: Nomor & Judul Tabel Lengkap (Lega & Tidak Terpotong)
    ws.row_dimensions[1].height = 26
    c_title = ws.cell(1, 1, f"Tabel {tnum} : {title}")
    c_title.font = Font(name=FONT_FAMILY, size=12, bold=True, color="0F172A")
    c_title.alignment = Alignment(horizontal="left", vertical="center")
    
    # 2. Row 2: Sumber Data
    ws.row_dimensions[2].height = 20
    c_src = ws.cell(2, 1, f"Sumber Data: {src_type}")
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

    # is_artifact_row already defined at top of function

    def get_col_label(item):
        sec = (item.get("section") or "").strip()
        m = (item.get("metric") or "").strip()
        if sec and sec not in ("-", "None") and not sec.lower().startswith("halaman") and sec.lower() not in m.lower():
            return f"{sec} - {m}"
        return m or "Data"

    cols_order = []
    # Kumpulkan kolom dari matches & diffs terlebih dahulu
    for item in (matches + diffs):
        c = get_col_label(item)
        if c not in cols_order:
            cols_order.append(c)

    # Susun urutan kolom: Non-total di kiri, Total/Jumlah di paling kanan
    non_tot_cols = [c for c in cols_order if not any(k in c.lower() for k in ("jumlah", "total"))]
    tot_cols = [c for c in cols_order if any(k in c.lower() for k in ("jumlah", "total"))]
    cols_order = non_tot_cols + tot_cols

    cell_map = {} # (wilayah, col_label) -> dict

    # 1. Masukkan data matches
    for m in matches:
        w = (m.get("wilayah") or "-").strip()
        if is_artifact_row(w): continue
        c = get_col_label(m)
        cell_map[(w, c)] = {
            "pdf_val": m.get("pdf_val", m.get("val", "")),
            "db_val": m.get("src_val", m.get("val", "")),
            "is_diff": False,
            "is_pdf_only": False
        }

    # 2. Masukkan data diffs (menimpa match jika ada)
    for d in diffs:
        w = (d.get("wilayah") or "-").strip()
        if is_artifact_row(w): continue
        c = get_col_label(d)
        cell_map[(w, c)] = {
            "pdf_val": d.get("pdf_val", ""),
            "db_val": d.get("src_val", ""),
            "is_diff": True,
            "is_pdf_only": False
        }

    # 3. Masukkan data pdf_only (abaikan baris artefak header & data historis)
    for p in real_pdf_only:
        w = (p.get("wilayah") or "-").strip()
        if is_artifact_row(w): continue
        c = get_col_label(p)
        # Cari apakah kolom ini sebenarnya bagian dari kolom yang sudah ada
        matched_c = next((col for col in cols_order if col.lower() in c.lower() or c.lower().endswith(col.lower())), None)
        target_c = matched_c if matched_c else c
        if target_c not in cols_order:
            cols_order.append(target_c)
        vals = p.get("values", [])
        pdf_v = vals[0] if vals else ""
        cell_map[(w, target_c)] = {
            "pdf_val": pdf_v,
            "db_val": "-",
            "is_diff": True,
            "is_pdf_only": True
        }

    # Kumpulkan seluruh baris kandidat
    all_candidate_rows = []
    for (w, c) in cell_map.keys():
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

    # 7. Row 7-8: Matrix Headers
    ws.merge_cells("A7:A8")
    cA7 = ws.cell(7, 1, "Baris (Wilayah / Kategori)")
    cA7.font = Font(name=FONT_FAMILY, size=11, bold=True)
    cA7.fill = HEADER_GRAY
    cA7.alignment = Alignment(horizontal="center", vertical="center")
    cA7.border = BORDER_THIN
    ws.cell(8, 1).border = BORDER_THIN

    curr_col = 2
    for col_label in cols_order:
        start_c = curr_col
        end_c = curr_col + 1
        ws.merge_cells(start_row=7, start_column=start_c, end_row=7, end_column=end_c)
        
        cHdr = ws.cell(7, start_c, col_label)
        cHdr.font = Font(name=FONT_FAMILY, size=11, bold=True)
        cHdr.fill = HEADER_GRAY
        cHdr.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
        cHdr.border = BORDER_THIN
        ws.cell(7, end_c).border = BORDER_THIN

        c_pdf = ws.cell(8, start_c, "PDF")
        c_pdf.font = Font(name=FONT_FAMILY, size=10, bold=True, color="0369A1")
        c_pdf.fill = PDF_COL_HDR
        c_pdf.alignment = Alignment(horizontal="center", vertical="center")
        c_pdf.border = BORDER_THIN

        c_db = ws.cell(8, end_c, "Database")
        c_db.font = Font(name=FONT_FAMILY, size=10, bold=True, color="166534")
        c_db.fill = DB_COL_HDR
        c_db.alignment = Alignment(horizontal="center", vertical="center")
        c_db.border = BORDER_THIN

        curr_col += 2

    ws.row_dimensions[7].height = 28
    ws.row_dimensions[8].height = 20

    # 6. Row 7+: Data Rows
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

    r_start = 9
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

    ws.freeze_panes = "B9"


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

    judul = res.get("judul_tabel") or res.get("judul_db") or res.get("judul_pdf") or f"Tabel {clean_target}"
    src_type = res.get("source_type") or "Tabel Dinamis"

    populate_matrix_sheet(ws, clean_target, judul, src_type, res)

    # Sheet 2 (Opsional jika ada selisih): Rincian Selisih
    diffs = res.get("diffs", [])
    matches = res.get("matches", [])
    pdf_only = res.get("pdf_only", []) if (not diffs and not matches) else []
    if diffs or pdf_only:
        ws2 = wb.create_sheet(title="⚠️ Rincian Selisih")
        ws2.cell(1, 1, f"DAFTAR SELISIH DATA - TABEL {clean_target}").font = Font(name=FONT_FAMILY, size=13, bold=True)
        ws2.cell(2, 1, judul).font = Font(name=FONT_FAMILY, size=10, italic=True)

        headers2 = ["No", "Baris (Wilayah / Kategori)", "Kolom (Metrik Data)", "Nilai di Buku PDF", "Nilai di Database", "Selisih", "Status", "Catatan"]
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
            ws2.cell(r_ptr, 3, d.get("metric", "")).alignment = Alignment(vertical="center", indent=1)
            ws2.cell(r_ptr, 4, d.get("pdf_val", "")).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 5, d.get("src_val", "")).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 6, d.get("diff", "")).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 6).font = DIFF_FONT
            
            c_st = ws2.cell(r_ptr, 7, "BEDA NILAI")
            c_st.font = DIFF_FONT
            c_st.alignment = Alignment(horizontal="center", vertical="center")

            ws2.cell(r_ptr, 8, d.get("note", "")).alignment = Alignment(vertical="center", indent=1)

            for col_i in range(1, 9):
                ws2.cell(r_ptr, col_i).fill = DIFF_FILL
                ws2.cell(r_ptr, col_i).border = BORDER_THIN

            counter += 1
            r_ptr += 1

        for p in pdf_only:
            ws2.row_dimensions[r_ptr].height = 22
            ws2.cell(r_ptr, 1, counter).alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 2, p.get("wilayah", "")).alignment = Alignment(vertical="center", indent=1)
            ws2.cell(r_ptr, 3, p.get("metric", "")).alignment = Alignment(vertical="center", indent=1)
            vals = p.get("values", [])
            ws2.cell(r_ptr, 4, vals[0] if vals else "").alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 5, "-").alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 6, "Data Baru").alignment = Alignment(horizontal="center", vertical="center")
            ws2.cell(r_ptr, 6).font = PDF_ONLY_FONT
            
            c_st = ws2.cell(r_ptr, 7, "HANYA DI PDF")
            c_st.font = PDF_ONLY_FONT
            c_st.alignment = Alignment(horizontal="center", vertical="center")

            ws2.cell(r_ptr, 8, p.get("note", "")).alignment = Alignment(vertical="center", indent=1)

            for col_i in range(1, 9):
                ws2.cell(r_ptr, col_i).fill = PDF_ONLY_FILL
                ws2.cell(r_ptr, col_i).border = BORDER_THIN

            counter += 1
            r_ptr += 1

        ws2.column_dimensions['A'].width = 6
        ws2.column_dimensions['B'].width = 24
        ws2.column_dimensions['C'].width = 30
        ws2.column_dimensions['D'].width = 16
        ws2.column_dimensions['E'].width = 16
        ws2.column_dimensions['F'].width = 14
        ws2.column_dimensions['G'].width = 16
        ws2.column_dimensions['H'].width = 34

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
