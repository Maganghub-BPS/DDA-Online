# Untuk mengekspor Rekapitulasi Global Multi-Sheet ke Excel (.xlsx)
# Aturan Bos Anas:
# 1. 1 Sheet Per Tabel (Nama Sheet: "Tabel [nomor_tabel]")
# 2. Tabel yang cocok 100% TIDAK dimasukkan ke sheet
# 3. Format matriks berpasangan: PDF vs Database
# 4. Asynchronous progress reporting & cancelable background worker
import sys
import os
import json
import re
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

sys.path.append(os.path.dirname(os.path.abspath(__file__)))
from compare_table import compare_head_to_head
from export_excel import populate_matrix_sheet, get_instansi_name, get_real_pdf_only, FONT_FAMILY, HEADER_GRAY, BORDER_THIN, PDF_COL_HDR, DB_COL_HDR, DIFF_FILL, DIFF_FONT, PDF_ONLY_FILL, PDF_ONLY_FONT, MATCH_FILL, MATCH_FONT

OPD_BANNER_FILL = PatternFill(start_color="E2E8F0", end_color="E2E8F0", fill_type="solid")
OPD_BANNER_FONT = Font(name=FONT_FAMILY, size=11, bold=True, color="0F172A")
SUMMARY_HDR_FILL = PatternFill(start_color="0F172A", end_color="0F172A", fill_type="solid")
SUMMARY_HDR_FONT = Font(name=FONT_FAMILY, size=11, bold=True, color="FFFFFF")
TOTAL_ROW_FILL = PatternFill(start_color="F1F5F9", end_color="F1F5F9", fill_type="solid")
TOTAL_ROW_FONT = Font(name=FONT_FAMILY, size=11, bold=True, color="0F172A")

def write_progress(prog_file, is_running=True, completed=0, total=0, cur_table="", status="running", error=""):
    if not prog_file:
        return
    try:
        tmp_f = prog_file + ".tmp"
        data = {
            "status": status,
            "is_running": is_running,
            "completed": completed,
            "total": total,
            "percent": round((completed / total * 100), 1) if total > 0 else 0,
            "current_table": cur_table,
            "error": error,
            "updated_at": time.time()
        }
        with open(tmp_f, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False)
        if os.path.exists(prog_file):
            try:
                os.remove(prog_file)
            except Exception:
                pass
        os.rename(tmp_f, prog_file)
    except Exception:
        pass


def export_global_diffs_to_excel(pdf_path, matched_json_path, db_json_path, state_json_path, output_path, tolerance=0.0, progress_path=None, source_filter="all"):
    stop_file = (progress_path + ".stop") if progress_path else None
    if stop_file and os.path.exists(stop_file):
        try:
            os.remove(stop_file)
        except Exception:
            pass

    write_progress(progress_path, is_running=True, completed=0, total=0, cur_table="Membaca data...", status="running")

    matched_data = {}
    if os.path.exists(matched_json_path):
        with open(matched_json_path, "r", encoding="utf-8") as f:
            matched_data = json.load(f)

    state_data = {}
    if os.path.exists(state_json_path):
        with open(state_json_path, "r", encoding="utf-8") as f:
            state_data = json.load(f)

    # Preload DB JSON & Index JSON sekali saja ke memori untuk efisiensi kecepatan
    preloaded_db = None
    if os.path.exists(db_json_path):
        try:
            with open(db_json_path, "r", encoding="utf-8") as f:
                preloaded_db = json.load(f)
        except Exception:
            pass

    preloaded_idx = None
    idx_path = pdf_path + ".index.json"
    if os.path.exists(idx_path):
        try:
            with open(idx_path, "r", encoding="utf-8") as f:
                preloaded_idx = json.load(f)
        except Exception:
            pass

    list_key = "matched_in_pdf" if "matched_in_pdf" in matched_data else "matched_tables"
    all_matched = matched_data.get(list_key, [])

    # Filter HANYA tabel yang statusnya diff atau api_empty (Yang match 100% DIKECUALIKAN!)
    diff_tables = []
    total_match_count = 0

    src_filter = (source_filter or "all").lower().strip()
    src_subtitle = "SEMUA SUMBER DATA"
    if src_filter == "api":
        src_subtitle = "SUMBER: API SATUDATA"
    elif src_filter == "dinamis":
        src_subtitle = "SUMBER: TABEL DINAMIS"

    for item in all_matched:
        tnum = str(item.get("nomor_tabel", "")).strip()
        st_info = state_data.get(tnum, {})
        st = st_info.get("status", "unverified")

        # FILTER MUTLAK: Seluruh tabel yang berstatus 'match' (Cocok 100%) DIKECUALIKAN TOTAL
        if st == "match":
            total_match_count += 1
            continue

        if st in ("diff", "api_empty"):
            item_src = str(item.get("source_type") or ("dinamis" if item.get("is_dynamic") else "api")).lower().strip()
            if src_filter != "all" and item_src != src_filter:
                continue
            diff_tables.append((item, st_info))

    total = len(diff_tables)
    write_progress(progress_path, is_running=True, completed=0, total=total, cur_table="Menyiapkan lembar kerja...", status="running")

    wb = openpyxl.Workbook()
    default_sheet = wb.active

    if total == 0:
        ws_idx = wb.create_sheet(title="📋 Rekap Tabel")
        ws_idx['A1'] = f"REKAPITULASI TABEL DDA ({src_subtitle})"
        ws_idx['A1'].font = Font(name=FONT_FAMILY, size=14, bold=True, color="0F172A")
        ws_idx['A3'] = f"Tidak ditemukan tabel bermasalah untuk kategori {src_subtitle}. Seluruh tabel cocok 100% dengan database!"
        ws_idx['A3'].font = Font(name=FONT_FAMILY, size=11, bold=True, color="166534")
        wb.remove(default_sheet)
        temp_out = output_path + ".tmp.xlsx"
        wb.save(temp_out)
        if os.path.exists(output_path):
            try: os.remove(output_path)
            except Exception: pass
        try: os.rename(temp_out, output_path)
        except Exception: wb.save(output_path)
        write_progress(progress_path, is_running=False, completed=0, total=0, cur_table="Selesai (Semua data cocok)", status="completed")
        return output_path

    # ----------------------------------------------------
    # SHEET 0: DAFTAR TABEL BERMASALAH (RINGKASAN & LINK)
    # ----------------------------------------------------
    ws_idx = wb.create_sheet(title="📋 Daftar Tabel Bermasalah")
    ws_idx['A1'] = f"DAFTAR TABEL MEMILIKI SELISIH DATA - BUKU DDA ({src_subtitle})"
    ws_idx['A1'].font = Font(name=FONT_FAMILY, size=14, bold=True, color="0F172A")
    ws_idx['A2'] = f"Catatan: Ditemukan {len(diff_tables)} tabel bermasalah (ada selisih angka atau data kosong di database). Sebanyak {total_match_count} tabel yang Cocok 100% sengaja dikecualikan agar tim pemeriksa fokus hanya pada data yang perlu diperbaiki."
    ws_idx['A2'].font = Font(name=FONT_FAMILY, size=10, italic=True, color="991B1B")

    # KOTAK PANDUAN / LEGENDA WARNA VALIDASI DATA
    ws_idx['A4'] = "PANDUAN / LEGENDA WARNA VALIDASI DATA:"
    ws_idx['A4'].font = Font(name=FONT_FAMILY, size=11, bold=True, color="0F172A")

    legend_items = [
        ("Merah Muda", DIFF_FILL, DIFF_FONT, "Selisih Nilai — Nilai pada cetakan Buku PDF berbeda dengan data di Database Sistem"),
        ("Kuning Muda", PDF_ONLY_FILL, PDF_ONLY_FONT, "Hanya di PDF — Data tercetak di Buku PDF tetapi belum diisi / belum ada di Database"),
        ("Putih", MATCH_FILL, MATCH_FONT, "Cocok 100% — Nilai pada Buku PDF dan Database sama persis (dikecualikan dari sheet file ini)"),
        ("Biru Muda", PDF_COL_HDR, Font(name=FONT_FAMILY, size=10, bold=True, color="0369A1"), "Header Kolom PDF — Menunjukkan kolom data dari dokumen PDF Master DDA"),
        ("Abu-abu Muda", DB_COL_HDR, Font(name=FONT_FAMILY, size=10, bold=True, color="166534"), "Header Kolom Database — Menunjukkan kolom data dari Database (API Satu Data / Tabel Dinamis)")
    ]

    for leg_row, (sample_txt, fill_color, font_color, desc_txt) in enumerate(legend_items, start=5):
        ws_idx.row_dimensions[leg_row].height = 20
        c_samp = ws_idx.cell(leg_row, 1, sample_txt)
        c_samp.fill = fill_color
        c_samp.font = font_color
        c_samp.alignment = Alignment(horizontal="center", vertical="center")
        c_samp.border = BORDER_THIN
        
        ws_idx.merge_cells(start_row=leg_row, start_column=2, end_row=leg_row, end_column=7)
        c_desc = ws_idx.cell(leg_row, 2, desc_txt)
        c_desc.font = Font(name=FONT_FAMILY, size=10)
        c_desc.alignment = Alignment(vertical="center", indent=1)
        for c_col in range(2, 8):
            ws_idx.cell(leg_row, c_col).border = BORDER_THIN

    # Header Tabel Indeks di Baris 11
    idx_header_row = 11
    idx_headers = ["No", "Nomor Tabel", "Judul Tabel Lengkap", "Sumber Data", "Instansi / OPD", "Halaman PDF", "Status Validasi", "Jumlah Selisih", "Tautan Lembar Kerja"]
    ws_idx.row_dimensions[idx_header_row].height = 26
    for c_i, h in enumerate(idx_headers, 1):
        cell = ws_idx.cell(idx_header_row, c_i, h)
        cell.font = Font(name=FONT_FAMILY, size=11, bold=True)
        cell.fill = HEADER_GRAY
        cell.alignment = Alignment(horizontal="center", vertical="center")
        cell.border = BORDER_THIN

    # Eksekusi perbandingan secara paralel
    def compare_worker(entry):
        item, st_info = entry
        tnum = str(item.get("nomor_tabel", "")).strip()
        clean_no = re.sub(r'^(?:tabel|table)\s*', '', tnum, flags=re.I).strip().rstrip('.')
        try:
            res = compare_head_to_head(
                pdf_path, 
                clean_no, 
                db_json_path, 
                tolerance=tolerance, 
                id_db=item.get("id_db"),
                preloaded_db_tables=preloaded_db,
                preloaded_index=preloaded_idx
            )
            return (item, st_info, res)
        except Exception as e:
            return (item, st_info, {"status": "error", "message": str(e)})

    # Batasi worker pool
    max_workers = min(6, os.cpu_count() or 4)
    ordered_results = [None] * total
    completed_cnt = 0

    with ThreadPoolExecutor(max_workers=max_workers) as executor:
        future_map = {executor.submit(compare_worker, entry): (idx, entry) for idx, entry in enumerate(diff_tables)}
        for future in as_completed(future_map):
            if stop_file and os.path.exists(stop_file):
                write_progress(progress_path, is_running=False, completed=completed_cnt, total=total, cur_table="Dibatalkan", status="stopped")
                return None

            idx_pos, entry = future_map[future]
            try:
                res_tuple = future.result()
            except Exception as e:
                res_tuple = (entry[0], entry[1], {"status": "error", "message": str(e)})

            ordered_results[idx_pos] = res_tuple
            completed_cnt += 1
            t_no = str(entry[0].get("nomor_tabel", "")).strip()
            write_progress(progress_path, is_running=True, completed=completed_cnt, total=total, cur_table=f"Tabel {t_no} ({completed_cnt}/{total})", status="running")

    raw_computed = [r for r in ordered_results if r is not None]
    computed_results = []
    state_modified = False

    for entry in raw_computed:
        item, st_info, res = entry
        tnum = str(item.get("nomor_tabel", "")).strip()
        if isinstance(res, dict) and res.get("status") == "success":
            diffs = res.get("diffs", [])
            matches = res.get("matches", [])
            # Sesuai arahan Bos Anas: Data yang hanya ada di PDF tidak dihitung sebagai selisih rekapan jika tabel memiliki data pembanding
            if not diffs and not matches:
                real_pdf_only = get_real_pdf_only(res)
            else:
                real_pdf_only = []
            actual_diff_cnt = len(diffs) + len(real_pdf_only)
            res["_actual_diff_cnt"] = actual_diff_cnt
            if actual_diff_cnt == 0 and len(matches) > 0:
                total_match_count += 1
                if tnum in state_data:
                    state_data[tnum]["status"] = "match"
                    state_data[tnum]["diff_count"] = 0
                    state_data[tnum]["match_count"] = len(matches)
                    state_modified = True
                continue
        computed_results.append(entry)

    if state_modified and state_json_path:
        try:
            with open(state_json_path, "w", encoding="utf-8") as f_st:
                json.dump(state_data, f_st, ensure_ascii=False, indent=2)
        except Exception:
            pass

    ws_idx['A2'] = f"Catatan: Ditemukan {len(computed_results)} tabel bermasalah (ada selisih angka atau data kosong di database). Sebanyak {total_match_count} tabel yang Cocok 100% sengaja dikecualikan agar tim pemeriksa fokus hanya pada data yang perlu diperbaiki."

    write_progress(progress_path, is_running=True, completed=total, total=total, cur_table="Menulis format Excel...", status="running")

    # Helper untuk sorting nomor tabel secara natural (1.1, 1.2, 1.10)
    def natural_table_key(tnum_str):
        parts = re.split(r'(\d+)', str(tnum_str))
        return [int(p) if p.isdigit() else p.lower() for p in parts]

    # Kelompokkan data per Instansi / OPD (Source)
    grouped_by_instansi = {}
    for entry in computed_results:
        item, st_info, res = entry
        tnum = str(item.get("nomor_tabel", "")).strip()
        clean_no = re.sub(r'^(?:tabel|table)\s*', '', tnum, flags=re.I).strip().rstrip('.')
        instansi = get_instansi_name(clean_no, item.get("id_db"), preloaded_db)
        if not instansi or instansi.strip() == "-" or instansi.strip() == "":
            instansi = "Instansi / OPD Lainnya"
        if instansi not in grouped_by_instansi:
            grouped_by_instansi[instansi] = []
        grouped_by_instansi[instansi].append((clean_no, entry))

    # Urutkan tabel di dalam setiap instansi berdasarkan nomor tabel alami
    for inst in grouped_by_instansi:
        grouped_by_instansi[inst].sort(key=lambda x: natural_table_key(x[0]))

    # Urutkan daftar nama instansi secara alfabetis
    sorted_instansi_names = sorted(grouped_by_instansi.keys(), key=lambda s: s.lower())

    # -------------------------------------------------------------------------
    # SHEET 1: REKAPITULASI RINGKAS MENURUT PRODUSEN DATA / OPD (EXECUTIVE SUMMARY)
    # -------------------------------------------------------------------------
    ws_opd = wb.create_sheet(title="🏛️ Rekap Menurut OPD-Source", index=0)
    ws_opd['A1'] = f"REKAPITULASI TABEL SELISIH BERDASARKAN PRODUSEN DATA / OPD ({src_subtitle})"
    ws_opd['A1'].font = Font(name=FONT_FAMILY, size=14, bold=True, color="0F172A")
    ws_opd['A2'] = f"Publikasi DDA: Menampilkan daftar seluruh OPD / Instansi sumber data ({src_subtitle}) yang memiliki tabel bermasalah (Selisih atau Data Kosong). Total: {len(sorted_instansi_names)} Instansi Sumber Data."
    ws_opd['A2'].font = Font(name=FONT_FAMILY, size=10, italic=True, color="475569")

    opd_headers = ["No", "Instansi / OPD (Source)", "Jumlah Tabel Bermasalah", "Ada Selisih Nilai", "Data Kosong di DB", "Daftar Nomor Tabel", "Tautan Cepat"]
    opd_header_row = 4
    ws_opd.row_dimensions[opd_header_row].height = 26
    for c_i, h in enumerate(opd_headers, 1):
        cell = ws_opd.cell(opd_header_row, c_i, h)
        cell.font = SUMMARY_HDR_FONT
        cell.fill = SUMMARY_HDR_FILL
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
        cell.border = BORDER_THIN

    opd_row_ptr = 5
    tot_tbl_all = 0
    tot_diff_all = 0
    tot_empty_all = 0

    # Tentukan baris anchor untuk setiap instansi di sheet Daftar Tabel Bermasalah
    opd_anchor_rows = {}
    idx_row_ptr = 12
    for inst_name in sorted_instansi_names:
        opd_anchor_rows[inst_name] = idx_row_ptr
        # 1 baris banner instansi
        idx_row_ptr += 1
        # baris-baris tabel instansi tersebut
        idx_row_ptr += len(grouped_by_instansi[inst_name])
        # 1 baris spasi pemisah
        idx_row_ptr += 1

    # Tulis isi Sheet Ringkasan OPD
    for o_idx, inst_name in enumerate(sorted_instansi_names, 1):
        tbl_list = grouped_by_instansi[inst_name]
        cnt_tbl = len(tbl_list)
        cnt_diff = sum(1 for _, (_, s_info, _) in tbl_list if s_info.get("status") == "diff")
        cnt_empty = sum(1 for _, (_, s_info, _) in tbl_list if s_info.get("status") == "api_empty")
        t_numbers = ", ".join(t_no for t_no, _ in tbl_list)

        tot_tbl_all += cnt_tbl
        tot_diff_all += cnt_diff
        tot_empty_all += cnt_empty

        ws_opd.row_dimensions[opd_row_ptr].height = 22
        ws_opd.cell(opd_row_ptr, 1, o_idx).alignment = Alignment(horizontal="center", vertical="center")
        ws_opd.cell(opd_row_ptr, 1).border = BORDER_THIN

        c_inst = ws_opd.cell(opd_row_ptr, 2, inst_name)
        c_inst.alignment = Alignment(vertical="center", indent=1)
        c_inst.font = Font(name=FONT_FAMILY, size=10.5, bold=True, color="1E293B")
        c_inst.border = BORDER_THIN

        c_tot = ws_opd.cell(opd_row_ptr, 3, cnt_tbl)
        c_tot.alignment = Alignment(horizontal="center", vertical="center")
        c_tot.font = Font(name=FONT_FAMILY, size=11, bold=True, color="0F172A")
        c_tot.border = BORDER_THIN

        c_d = ws_opd.cell(opd_row_ptr, 4, cnt_diff if cnt_diff > 0 else "-")
        c_d.alignment = Alignment(horizontal="center", vertical="center")
        if cnt_diff > 0:
            c_d.font = Font(name=FONT_FAMILY, size=10.5, bold=True, color="991B1B")
        c_d.border = BORDER_THIN

        c_e = ws_opd.cell(opd_row_ptr, 5, cnt_empty if cnt_empty > 0 else "-")
        c_e.alignment = Alignment(horizontal="center", vertical="center")
        if cnt_empty > 0:
            c_e.font = Font(name=FONT_FAMILY, size=10.5, bold=True, color="D97706")
        c_e.border = BORDER_THIN

        c_tnums = ws_opd.cell(opd_row_ptr, 6, t_numbers)
        c_tnums.alignment = Alignment(vertical="center", indent=1)
        c_tnums.font = Font(name=FONT_FAMILY, size=9.5, color="334155")
        c_tnums.border = BORDER_THIN

        target_row = opd_anchor_rows.get(inst_name, 12)
        c_link = ws_opd.cell(opd_row_ptr, 7, "Lihat Rincian Tabel ➔")
        c_link.hyperlink = f"#'📋 Daftar Tabel Bermasalah'!A{target_row}"
        c_link.font = Font(name=FONT_FAMILY, size=10, color="2563EB", underline="single", bold=True)
        c_link.alignment = Alignment(horizontal="center", vertical="center")
        c_link.border = BORDER_THIN

        opd_row_ptr += 1

    # Baris Total Keseluruhan di Sheet OPD
    ws_opd.row_dimensions[opd_row_ptr].height = 24
    ws_opd.merge_cells(start_row=opd_row_ptr, start_column=1, end_row=opd_row_ptr, end_column=2)
    tot_label = ws_opd.cell(opd_row_ptr, 1, f"TOTAL ({len(sorted_instansi_names)} INSTANSI SUMBER DATA)")
    tot_label.font = TOTAL_ROW_FONT
    tot_label.alignment = Alignment(horizontal="center", vertical="center")
    tot_label.fill = TOTAL_ROW_FILL
    ws_opd.cell(opd_row_ptr, 2).fill = TOTAL_ROW_FILL

    for col_c, val in [(3, tot_tbl_all), (4, tot_diff_all), (5, tot_empty_all)]:
        c_tot_val = ws_opd.cell(opd_row_ptr, col_c, val)
        c_tot_val.font = TOTAL_ROW_FONT
        c_tot_val.alignment = Alignment(horizontal="center", vertical="center")
        c_tot_val.fill = TOTAL_ROW_FILL

    for col_empty in [6, 7]:
        c_emp = ws_opd.cell(opd_row_ptr, col_empty, "-")
        c_emp.font = TOTAL_ROW_FONT
        c_emp.alignment = Alignment(horizontal="center", vertical="center")
        c_emp.fill = TOTAL_ROW_FILL

    for c_i in range(1, 8):
        ws_opd.cell(opd_row_ptr, c_i).border = BORDER_THIN

    ws_opd.column_dimensions['A'].width = 8
    ws_opd.column_dimensions['B'].width = 46
    ws_opd.column_dimensions['C'].width = 24
    ws_opd.column_dimensions['D'].width = 18
    ws_opd.column_dimensions['E'].width = 18
    ws_opd.column_dimensions['F'].width = 40
    ws_opd.column_dimensions['G'].width = 22

    # -------------------------------------------------------------------------
    # SHEET 2: DAFTAR TABEL BERMASALAH (DIKELOMPOKKAN BERDASARKAN SOURCE / OPD)
    # -------------------------------------------------------------------------
    row_ptr = 12
    global_t_idx = 1
    created_sheet_titles = set()

    for inst_name in sorted_instansi_names:
        tbl_list = grouped_by_instansi[inst_name]
        cnt_in_inst = len(tbl_list)

        # Banner Header Instansi / OPD
        ws_idx.row_dimensions[row_ptr].height = 24
        ws_idx.merge_cells(start_row=row_ptr, start_column=1, end_row=row_ptr, end_column=9)
        c_banner = ws_idx.cell(row_ptr, 1, f"🏛️  {inst_name.upper()} — ({cnt_in_inst} Tabel Bermasalah)")
        c_banner.font = OPD_BANNER_FONT
        c_banner.fill = OPD_BANNER_FILL
        c_banner.alignment = Alignment(vertical="center", indent=1)
        for col_b in range(1, 10):
            ws_idx.cell(row_ptr, col_b).border = BORDER_THIN
        row_ptr += 1

        # Tulis masing-masing tabel milik instansi ini
        for clean_no, (item, st_info, res) in tbl_list:
            judul = item.get("judul_db") or item.get("judul_pdf") or f"Tabel {clean_no}"
            src_code = item.get("source_type") or ("dinamis" if item.get("is_dynamic") else "api")
            src_label = "Tabel Dinamis" if src_code == "dinamis" else "API Satu Data"
            pages = ", ".join(str(p) for p in item.get("pages", [])) if item.get("pages") else "-"
            st = st_info.get("status", "diff")
            if isinstance(res, dict) and "_actual_diff_cnt" in res:
                diff_cnt = int(res.get("_actual_diff_cnt", 0))
            elif isinstance(res, dict) and "diffs" in res:
                diff_cnt = len(res.get("diffs", [])) + (len(get_real_pdf_only(res)) if not res.get("diffs") and not res.get("matches") else 0)
            else:
                try:
                    diff_cnt = int(st_info.get("diff_count", 0))
                except (ValueError, TypeError):
                    diff_cnt = 0

            sheet_title = f"Tabel {clean_no}"[:31]
            if sheet_title in created_sheet_titles:
                sheet_title = f"{sheet_title[:27]}_{global_t_idx}"
            created_sheet_titles.add(sheet_title)

            ws_idx.row_dimensions[row_ptr].height = 22
            ws_idx.cell(row_ptr, 1, global_t_idx).alignment = Alignment(horizontal="center", vertical="center")
            ws_idx.cell(row_ptr, 1).border = BORDER_THIN

            c_no = ws_idx.cell(row_ptr, 2, clean_no)
            c_no.alignment = Alignment(horizontal="center", vertical="center")
            c_no.font = Font(name=FONT_FAMILY, size=11, bold=True, color="1E3A8A")
            c_no.border = BORDER_THIN

            c_jd = ws_idx.cell(row_ptr, 3, judul)
            c_jd.alignment = Alignment(vertical="center", indent=1)
            c_jd.border = BORDER_THIN

            ws_idx.cell(row_ptr, 4, src_label).alignment = Alignment(horizontal="center", vertical="center")
            ws_idx.cell(row_ptr, 4).border = BORDER_THIN

            c_inst = ws_idx.cell(row_ptr, 5, inst_name)
            c_inst.alignment = Alignment(vertical="center", indent=1)
            c_inst.border = BORDER_THIN

            ws_idx.cell(row_ptr, 6, pages).alignment = Alignment(horizontal="center", vertical="center")
            ws_idx.cell(row_ptr, 6).border = BORDER_THIN

            st_lbl = "⚠️ Ada Selisih" if st == "diff" else ("Data Kosong" if st == "api_empty" else st)
            c_st = ws_idx.cell(row_ptr, 7, st_lbl)
            c_st.alignment = Alignment(horizontal="center", vertical="center")
            c_st.font = Font(name=FONT_FAMILY, size=11, bold=True, color="991B1B")
            c_st.border = BORDER_THIN

            c_cnt = ws_idx.cell(row_ptr, 8, f"{diff_cnt} Data Beda" if diff_cnt > 0 else "-")
            c_cnt.alignment = Alignment(horizontal="center", vertical="center")
            c_cnt.border = BORDER_THIN

            c_link = ws_idx.cell(row_ptr, 9, f"Buka Sheet {sheet_title}")
            c_link.hyperlink = f"#'{sheet_title}'!A1"
            c_link.font = Font(name=FONT_FAMILY, size=11, color="2563EB", underline="single")
            c_link.alignment = Alignment(horizontal="center", vertical="center")
            c_link.border = BORDER_THIN

            row_ptr += 1
            global_t_idx += 1

            # Buat sheet untuk tabel ini
            try:
                if res.get("status") == "success":
                    ws_table = wb.create_sheet(title=sheet_title)
                    t_title = res.get("judul_tabel") or judul
                    populate_matrix_sheet(ws_table, clean_no, t_title, res.get("source_type") or src_label, res, instansi=inst_name)
                elif res.get("is_api_empty"):
                    ws_table = wb.create_sheet(title=sheet_title)
                    ws_table.row_dimensions[1].height = 26
                    ws_table.cell(1, 1, f"Tabel {clean_no} : {judul}").font = Font(name=FONT_FAMILY, size=12, bold=True, color="0F172A")
                    ws_table.row_dimensions[2].height = 20
                    ws_table.cell(2, 1, f"Sumber Data: {src_label}").font = Font(name=FONT_FAMILY, size=10, color="475569")
                    ws_table.row_dimensions[3].height = 20
                    ws_table.cell(3, 1, f"Instansi / OPD: {inst_name}").font = Font(name=FONT_FAMILY, size=10, bold=True, color="1E3A8A")
                    ws_table.row_dimensions[4].height = 20
                    ws_table.cell(4, 1, "Status: Data Kosong di Database Sistem").font = Font(name=FONT_FAMILY, size=10, bold=True, color="991B1B")
                    ws_table.row_dimensions[5].height = 20
                    ws_table.cell(5, 1, "Petunjuk Warna:  [Merah Muda] Selisih Nilai   |   [Kuning] Hanya Ada di PDF   |   [Putih] Cocok 100%").font = Font(name=FONT_FAMILY, size=9.5, italic=True, color="334155")
                    ws_table.row_dimensions[7].height = 22
                    ws_table.cell(7, 1, "Keterangan: Data tabel ini belum diisi di database sumber.").font = Font(name=FONT_FAMILY, size=11, italic=True)
                    ws_table.column_dimensions['A'].width = 35
            except Exception:
                pass

        # Baris pemisah tipis antar instansi
        ws_idx.row_dimensions[row_ptr].height = 8
        row_ptr += 1

    ws_idx.column_dimensions['A'].width = 14
    ws_idx.column_dimensions['B'].width = 16
    ws_idx.column_dimensions['C'].width = 44
    ws_idx.column_dimensions['D'].width = 18
    ws_idx.column_dimensions['E'].width = 38
    ws_idx.column_dimensions['F'].width = 14
    ws_idx.column_dimensions['G'].width = 18
    ws_idx.column_dimensions['H'].width = 18
    ws_idx.column_dimensions['I'].width = 22

    wb.remove(default_sheet)
    
    # Simpan ke file sementara dulu lalu rename untuk atomisitas
    temp_out = output_path + ".tmp.xlsx"
    wb.save(temp_out)
    if os.path.exists(output_path):
        try:
            os.remove(output_path)
        except Exception:
            pass
    try:
        os.rename(temp_out, output_path)
    except Exception:
        wb.save(output_path)

    write_progress(progress_path, is_running=False, completed=total, total=total, cur_table="Selesai", status="completed")
    return output_path


if __name__ == "__main__":
    if len(sys.argv) < 6:
        print("Usage: python export_global_excel.py <pdf_path> <matched_json_path> <db_json_path> <state_json_path> <output_path> [tolerance] [progress_path]")
        sys.exit(1)

    pdf_p = sys.argv[1]
    matched_p = sys.argv[2]
    db_p = sys.argv[3]
    state_p = sys.argv[4]
    out_p = sys.argv[5]
    tol = float(sys.argv[6]) if len(sys.argv) > 6 else 0.0
    progress_p = sys.argv[7] if len(sys.argv) > 7 else None
    src_filter = sys.argv[8].lower().strip() if len(sys.argv) > 8 else "all"

    export_global_diffs_to_excel(pdf_p, matched_p, db_p, state_p, out_p, tolerance=tol, progress_path=progress_p, source_filter=src_filter)
    print("Global export complete")
