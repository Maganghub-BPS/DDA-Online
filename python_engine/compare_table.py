"""
=============================================================================
DDA ONLINE CI4 - HEAD-TO-HEAD TABLE COMPARISON ENGINE
(Mendukung Tabel Dinamis & API Satu Data)
=============================================================================
"""

import sys
import os
import json
import re
from difflib import SequenceMatcher

sys.path.append(os.path.dirname(os.path.abspath(__file__)))

if sys.platform == "win32":
    try:
        if hasattr(sys.stdout, 'reconfigure'):
            sys.stdout.reconfigure(encoding='utf-8', errors='replace')
        elif hasattr(sys.stdout, 'buffer'):
            import io
            sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        if hasattr(sys.stderr, 'reconfigure'):
            sys.stderr.reconfigure(encoding='utf-8', errors='replace')
        elif hasattr(sys.stderr, 'buffer'):
            import io
            sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except Exception:
        pass

from pdf_extractor import (
    extract_tables_from_pdf,
    extract_table_content,
    get_cached_page_tables,
)
from fetcher import fetch_data_from_link
from normalizer import (
    KAB_KOTA_JATENG,
    evaluate_cell_match,
    explain_mismatch,
    parse_num,
    resolve_jateng_entity,
    resolve_provinsi_entity,
    find_provinsi,
    find_wilayah_with_type,
    is_interval_label,
    is_bps_col_num,
    is_english_line,
    is_safe_fuzzy_match,
    match_party_name,
    extract_pdf_years_expanded,
)
from table_profiler import (
    find_bps_numbering_row,
    is_title_row,
    is_header_or_metadata_row,
    detect_col_headers,
    detect_active_data_cols,
    extract_pdf_headers,
    align_columns_semantically,
    detect_table_archetype,
    normalize_api_data,
    _is_non_additive_metric_header,
)


def _hint(pv, sv):
    """Sufiks petunjuk pola selisih untuk kolom 'note' (kosong bila tidak ada pola)."""
    h = explain_mismatch(pv, sv)
    return f" — {h}" if h else ""


def _to_num(v):
    """Angka dari sisi sumber (API) -> float atau None; tidak pernah melempar."""
    return v if isinstance(v, float) else parse_num(v, is_pdf=False)


def _extract_header_years(text):
    """Ekstrak daftar tahun kanonik 4-digit dari header/judul (bersihkan footnote, ambil tahun awal YYYY/YYYY dan tahun akhir YYYY-YYYY)."""
    if not text:
        return []
    s = re.sub(r'\b(19\d{2}|20\d{2})[1-9\*\^rR\†\‡]+(?:\s*,\s*[1-9\*\^rR\†\‡]+)*\)?(?=\s|$|[\)\-\/.,])', r'\1', str(text))
    s = re.sub(r'\b(19\d{2}|20\d{2})\s*/\s*(?:19\d{2}|20\d{2})\b', r'\1', s)
    s = re.sub(r'\b(?:19\d{2}|20\d{2})\s*[\-–—]\s*(19\d{2}|20\d{2})\b', r'\1', s)
    return re.findall(r'\b((?:19|20)\d{2})\b', s)


def format_display_val(val):
    """Format angka display ramah pengguna tanpa trailing float noise panjang (maks 3 desimal)."""
    if val is None:
        return None
    if isinstance(val, (int, float)):
        if isinstance(val, float) and val.is_integer():
            return int(val)
        if isinstance(val, float):
            return round(val, 3)
        return val
    return val


def _build_diff_entry_meta(pv, sv, p_disp, s_disp, diff_val, is_empty_col=False):
    """Susun (diff_out, note) agar sel sumber kosong/dots tidak menghasilkan 'Selisih 0 (PDF lebih banyak)'."""
    pv_is_dots = pv in ('...', '…') or (isinstance(pv, str) and pv.strip() in ('...', '…'))
    sv_is_empty = sv is None or sv == '' or sv in ('...', '…') or (isinstance(sv, str) and sv.strip() in ('...', '…'))

    if sv_is_empty or (is_empty_col and sv == 0.0):
        pv_num = pv if isinstance(pv, (int, float)) else parse_num(pv, is_pdf=True)
        diff_out = -round(float(pv_num), 3) if (not pv_is_dots and isinstance(pv_num, (int, float))) else diff_val
        scope = "kolom ini kosong" if is_empty_col else "sel ini kosong"
        return diff_out, f"Di cetakan PDF tertulis {p_disp}, tetapi di database {scope}"

    if pv is None:
        sv_num = sv if isinstance(sv, (int, float)) else parse_num(sv, is_pdf=False)
        diff_out = round(float(sv_num), 3) if isinstance(sv_num, (int, float)) else None
        return diff_out, f"Di cetakan PDF sel ini kosong / tidak terbaca angka, tetapi di database tertulis {s_disp}"

    if pv_is_dots:
        return diff_val, f"Di cetakan PDF tertulis '...' (belum tersedia), tetapi di database tertulis {s_disp}"

    if diff_val is not None:
        abs_diff = format_display_val(abs(diff_val))
        return diff_val, f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})" + _hint(pv, sv)

    return diff_val, f"Nilai berbeda: PDF '{pv}' vs Sumber '{sv}'"


def _resolve_pdf_val_with_scale(pv, sv=None, is_en_thousands_col=False):
    """Kembalikan nilai display PDF yang benar untuk AmbiguousFloat (mis. '19,616' di kolom ribuan -> 19616, bukan 19.616)."""
    alts = getattr(pv, 'alts', None)
    if not alts:
        return pv
    if sv is not None and isinstance(sv, (int, float)):
        sv_f = float(sv)
        if abs(float(pv) - sv_f) >= 0.001:
            for a in alts:
                if abs(float(a) - sv_f) < 0.001:
                    return float(a)
    if is_en_thousands_col:
        p_abs = max(abs(float(pv)), 1e-9)
        for a in alts:
            if abs(float(a)) / p_abs >= 500.0:
                return float(a)
    return pv


def _detect_en_thousands_pdf_cols(col_pairs_iter):
    """Deteksi kolom PDF yang konsisten memakai koma ribuan Inggris berdasarkan kecocokan alt vs DB."""
    stats = {}
    for p_col_idx, pv, sv in col_pairs_iter:
        if p_col_idx not in stats:
            stats[p_col_idx] = [0, 0]
        alts = getattr(pv, 'alts', None)
        if not alts or not isinstance(sv, (int, float)):
            continue
        p_abs = max(abs(float(pv)), 1e-9)
        sv_f = float(sv)
        if abs(float(pv) - sv_f) < 0.01:
            stats[p_col_idx][0] += 1
        elif any(abs(float(a) - sv_f) < 0.01 and abs(float(a)) / p_abs >= 500.0 for a in alts):
            stats[p_col_idx][1] += 1
    return {c_idx for c_idx, (ex_w, alt_w) in stats.items() if alt_w >= 2 and alt_w > ex_w}
_OPD_ALIAS_MAP = {
    'SETDA': 'SEKRETARIAT_DAERAH',
    'SEKRETARIAT DAERAH': 'SEKRETARIAT_DAERAH',
    'SETWAN': 'SEKRETARIAT_DPRD',
    'SEKRETARIAT DPRD': 'SEKRETARIAT_DPRD',
    'BAPPEDA': 'BAPPEDA',
    'BADAN PERENCANAAN PEMBANGUNAN': 'BAPPEDA',
    'BKD': 'BKD',
    'BADAN KEPEGAWAIAN DAERAH': 'BKD',
    'BPSDMD': 'BPSDMD',
    'PENGEMBANGAN SUMBER DAYA MANUSIA': 'BPSDMD',
    'BAPENDA': 'BAPENDA',
    'BADAN PENGELOLA PENDAPATAN DAERAH': 'BAPENDA',
    'BPKAD': 'BPKAD',
    'BADAN PENGELOLAAN KEUANGAN DAN ASET': 'BPKAD',
    'BAKESBANGPOL': 'BAKESBANGPOL',
    'KESATUAN BANGSA DAN POLITIK': 'BAKESBANGPOL',
    'BRIDA': 'BRIDA',
    'RISET DAN INOVASI DAERAH': 'BRIDA',
    'SATPOL PP': 'SATPOL_PP',
    'SATUAN POLISI PAMONG PRAJA': 'SATPOL_PP',
    'DISDIKBUD': 'DISDIKBUD',
    'PENDIDIKAN DAN KEBUDAYAAN': 'DISDIKBUD',
    'DINKES': 'DINKES',
    'KESEHATAN': 'DINKES',
    'DPUBMCK': 'DPUBMCK',
    'PEKERJAAN UMUM BINA MARGA': 'DPUBMCK',
    'DPUSDATARU': 'DPUSDATARU',
    'SUMBER DAYA AIR DAN PENATAAN RUANG': 'DPUSDATARU',
    'DISPERAKIM': 'DISPERAKIM',
    'PERUMAHAN RAKYAT DAN KAWASAN PERMUKIMAN': 'DISPERAKIM',
    'DINSOS': 'DINSOS',
    'SOSIAL': 'DINSOS',
    'DISNAKERTRANS': 'DISNAKERTRANS',
    'TENAGA KERJA DAN TRANSMIGRASI': 'DISNAKERTRANS',
    'DESDM': 'DESDM',
    'ENERGI DAN SUMBER DAYA MINERAL': 'DESDM',
    'DISPORAPAR': 'DISPORAPAR',
    'KEPEMUDAAN, OLAHRAGA DAN PARIWISATA': 'DISPORAPAR',
    'KEPEMUDAAN OLAHRAGA DAN PARIWISATA': 'DISPORAPAR',
    'DISHUB': 'DISHUB',
    'PERHUBUNGAN': 'DISHUB',
    'DISKOMINFO': 'DISKOMINFO',
    'KOMUNIKASI DAN INFORMATIKA': 'DISKOMINFO',
    'DISPERINDAG': 'DISPERINDAG',
    'PERINDUSTRIAN DAN PERDAGANGAN': 'DISPERINDAG',
    'DINKOP UKM': 'DINKOP_UKM',
    'KOPERASI, USAHA KECIL DAN MENENGAH': 'DINKOP_UKM',
    'KOPERASI DAN UKM': 'DINKOP_UKM',
    'DISTANBUN': 'DISTANBUN',
    'PERTANIAN DAN PERKEBUNAN': 'DISTANBUN',
    'DISHANPAN': 'DISHANPAN',
    'KETAHANAN PANGAN': 'DISHANPAN',
    'DISNAKKESWAN': 'DISNAKKESWAN',
    'PETERNAKAN DAN KESEHATAN HEWAN': 'DISNAKKESWAN',
    'DKP': 'DKP',
    'KELAUTAN DAN PERIKANAN': 'DKP',
    'DLHK': 'DLHK',
    'LINGKUNGAN HIDUP DAN KEHUTANAN': 'DLHK',
    'DISPERMADESDUKCAPIL': 'DISPERMADESDUKCAPIL',
    'PEMBERDAYAAN MASYARAKAT, DESA': 'DISPERMADESDUKCAPIL',
    'PEMBERDAYAAN MASYARAKAT DESA': 'DISPERMADESDUKCAPIL',
    'DP3AP2KB': 'DP3AP2KB',
    'PEMBERDAYAAN PEREMPUAN, PERLINDUNGAN ANAK': 'DP3AP2KB',
    'PEMBERDAYAAN PEREMPUAN PERLINDUNGAN ANAK': 'DP3AP2KB',
    'DPMPTSP': 'DPMPTSP',
    'PENANAMAN MODAL DAN PELAYANAN TERPADU': 'DPMPTSP',
    'DISARPUS': 'DISARPUS',
    'KEARSIPAN DAN PERPUSTAKAAN': 'DISARPUS',
    'INSPEKTORAT': 'INSPEKTORAT',
    'RSUD MOEWARDI': 'RSUD_MOEWARDI',
    'RSUD MARGONO': 'RSUD_MARGONO',
    'RSUD PROF DR MARGONO': 'RSUD_MARGONO',
    'RSUD ADHYATMA': 'RSUD_ADHYATMA',
    'RSUD TUGUREJO': 'RSUD_ADHYATMA',
    'RSUD KELET': 'RSUD_REHATTA',
    'RSUD REHATTA': 'RSUD_REHATTA',
    'RSJD AMINO': 'RSJD_AMINO',
    'RSJD ARIF ZAINUDIN': 'RSJD_ARIF_ZAINUDIN',
    'RSJD SURAKARTA': 'RSJD_ARIF_ZAINUDIN',
    'RSJD SOEJARWADI': 'RSJD_SOEJARWADI',
    'RSJD KLATEN': 'RSJD_SOEJARWADI'
}

def match_opd_name(text):
    if not text: return None
    t = str(text).upper()
    t = re.sub(r'[\r\n]+', ' ', t)
    t = re.sub(r'^[a-z\W\d_]+\s+', '', t)
    t = re.sub(r'[^\w\s]', ' ', t)
    t = re.sub(r'\s+', ' ', t).strip()
    
    words = t.split()
    for w in words:
        if w in _OPD_ALIAS_MAP:
            return f"OPD_{_OPD_ALIAS_MAP[w]}"
            
    for pattern, canonical in _OPD_ALIAS_MAP.items():
        if pattern in t:
            return f"OPD_{canonical}"
    return None


def compare_head_to_head(pdf_path, target_table, db_json_path, tolerance=0.0, id_db=None, preloaded_db_tables=None, preloaded_index=None):
    m_pub_yr = re.search(r'dda_master_(\d{4})\.pdf', str(pdf_path or ''), re.IGNORECASE)
    pub_year = int(m_pub_yr.group(1)) if m_pub_yr else None
    if pub_year is None and pdf_path:
        try:
            import match_pdf
            det_yr = match_pdf.detect_pdf_publication_year(pdf_path)
            if det_yr and str(det_yr).strip().isdigit():
                pub_year = int(str(det_yr).strip())
        except Exception:
            pass

    if preloaded_db_tables is not None:
        db_tables = preloaded_db_tables
    else:
        with open(db_json_path, "r", encoding="utf-8") as f:
            db_tables = json.load(f)
    if isinstance(db_tables, dict):
        db_tables = db_tables.get("matched_in_pdf", db_tables.get("matched_tables", []))

    def clean_num(n):
        if not n:
            return ""
        c = re.sub(r"^(?i:tabel|table)\s*", "", str(n).strip())
        c = re.sub(r'\s+', '', c)
        return c.rstrip('.')

    target_info = None

    # 1. Prioritas Lookup via ID Spesifik Database jika diberikan oleh Batch/Excel
    if id_db:
        target_info = next((
            t for t in db_tables
            if str(t.get("id_db", t.get("id", ""))) == str(id_db)
            or clean_num(t.get("nomor_tabel")) == clean_num(id_db)
        ), None)

    # 2. Lookup via Nomor Tabel dari PDF
    if not target_info:
        target_info = next((
            t for t in db_tables
            if clean_num(t.get("nomor_tabel")) == clean_num(target_table)
            or str(t.get("id_db", t.get("id", ""))) == clean_num(target_table)
        ), None)

    # 3. SAFETY NET FALLBACK FUZZY MATCH: Jika tidak ketemu, Fuzzy Search ke DB menggunakan Judul PDF dari Cache Index
    index_cache_path = pdf_path + ".index.json"
    if not target_info:
        index_cache = preloaded_index
        if index_cache is None and os.path.exists(index_cache_path):
            try:
                with open(index_cache_path, "r", encoding="utf-8") as f:
                    index_cache = json.load(f)
            except Exception:
                index_cache = None

        if index_cache:
            try:
                pdf_cache_info = next((val for k, val in index_cache.items() if clean_num(k) == clean_num(target_table)), None)

                if pdf_cache_info and "display_title" in pdf_cache_info:
                    from rapidfuzz import fuzz
                    from match_pdf import normalize_title

                    norm_pdf_title = normalize_title(pdf_cache_info["display_title"])
                    best_sim = 0
                    best_t = None
                    for t in db_tables:
                        norm_db = normalize_title(t.get('judul_tabel', t.get('judul_ind', '')))
                        sim = max(fuzz.partial_ratio(norm_pdf_title, norm_db), fuzz.token_set_ratio(norm_pdf_title, norm_db))
                        if sim > best_sim:
                            best_sim = sim
                            best_t = t
                    if best_sim >= 80:
                        target_info = best_t
            except Exception:
                pass

    if not target_info:
        return {"status": "error", "message": "Tabel tidak ditemukan di database (ID tidak cocok dan Fuzzy Search gagal)"}

    src_type_code = str(target_info.get("source_type", "")).strip().lower()
    is_dynamic = int(target_info.get("is_dynamic", 0) or 0) == 1 or src_type_code == "dinamis"
    link = str(target_info.get("link_tabel", "") or "").strip()

    target_pages = None
    pdf_index_title = target_info.get("judul_pdf") or ""
    if preloaded_index is not None:
        for k, val in preloaded_index.items():
            if clean_num(k) == clean_num(target_table):
                target_pages = val.get("pages", []) if isinstance(val, dict) else val
                if isinstance(val, dict) and not pdf_index_title:
                    pdf_index_title = val.get("display_title") or val.get("judul_pdf") or val.get("judul") or ""
                break
    elif os.path.exists(index_cache_path):
        try:
            with open(index_cache_path, "r", encoding="utf-8") as f:
                index_cache = json.load(f)
            for k, val in index_cache.items():
                if clean_num(k) == clean_num(target_table):
                    target_pages = val.get("pages", []) if isinstance(val, dict) else val
                    if isinstance(val, dict) and not pdf_index_title:
                        pdf_index_title = val.get("display_title") or val.get("judul_pdf") or val.get("judul") or ""
                    break
        except Exception:
            pass

    if not target_pages:
        pdf_tables = extract_tables_from_pdf(pdf_path, {clean_num(target_table)})
        matched_k = next((k for k in pdf_tables if clean_num(k) == clean_num(target_table)), None)
        if matched_k:
            target_pages = pdf_tables[matched_k]["pages"]
            if not pdf_index_title:
                pdf_index_title = pdf_tables[matched_k].get("display_title", "") or ""
            try:
                cur_cache = {}
                if os.path.exists(index_cache_path):
                    try:
                        with open(index_cache_path, "r", encoding="utf-8") as f:
                            loaded = json.load(f)
                        if isinstance(loaded, dict):
                            cur_cache = loaded
                    except Exception:
                        cur_cache = {}
                cur_cache[target_table] = {"pages": target_pages, "display_title": pdf_tables[matched_k].get("display_title", "")}
                tmp_idx = f"{index_cache_path}.tmp.{os.getpid()}"
                with open(tmp_idx, "w", encoding="utf-8") as f:
                    json.dump(cur_cache, f, ensure_ascii=False)
                os.replace(tmp_idx, index_cache_path)
            except Exception:
                pass

    # Additive Fallback: Self-Healing Spatial Page Finder if pages still empty
    if not target_pages and os.path.exists(pdf_path):
        try:
            try:
                import pymupdf
            except ImportError:
                import fitz as pymupdf
            doc = pymupdf.open(pdf_path)
            t_clean = clean_num(target_table)
            pat_sp = re.compile(rf'(?:(?:Tabel|Table|Lanjutan\s+Tabel|Continued\s+Table)\s*{re.escape(t_clean)}\b|^\s*{re.escape(t_clean)}\s*[\t ])', re.IGNORECASE | re.MULTILINE)
            found_p = []
            for idx in range(65, len(doc)):
                p_obj = doc[idx]
                max_y = (p_obj.rect.height or 842) * 0.35
                blocks = [b for b in p_obj.get_text("blocks") if b[1] < max_y and len(b) > 4 and isinstance(b[4], str)]
                blocks.sort(key=lambda b: (round(b[1] / 15.0), b[0]))
                sh = "\n".join(b[4].strip() for b in blocks)
                if pat_sp.search(sh):
                    found_p.append(idx + 1)
            doc.close()
            if found_p:
                groups = []
                curr_g = [found_p[0]]
                for p_no in found_p[1:]:
                    if p_no == curr_g[-1] + 1:
                        curr_g.append(p_no)
                    else:
                        groups.append(curr_g)
                        curr_g = [p_no]
                groups.append(curr_g)
                target_pages = max(groups, key=len)
                try:
                    cur_cache = {}
                    if os.path.exists(index_cache_path):
                        try:
                            with open(index_cache_path, "r", encoding="utf-8") as f:
                                loaded = json.load(f)
                            if isinstance(loaded, dict):
                                cur_cache = loaded
                        except Exception:
                            cur_cache = {}
                    cur_cache[target_table] = {"pages": target_pages, "display_title": target_info.get("judul_tabel", "")}
                    tmp_idx = f"{index_cache_path}.tmp.{os.getpid()}"
                    with open(tmp_idx, "w", encoding="utf-8") as f:
                        json.dump(cur_cache, f, ensure_ascii=False)
                    os.replace(tmp_idx, index_cache_path)
                except Exception:
                    pass
        except Exception:
            pass

    is_template_extraction = False
    cell_coords = target_info.get("cell_coords") or {}
    tpl_grid = target_info.get("template_grid") or []

    def _get_tpl_coord(r_idx, c_idx):
        if not is_dynamic or not isinstance(tpl_grid, list) or not cell_coords:
            return None
        if 0 <= r_idx < len(tpl_grid) and isinstance(tpl_grid[r_idx], list) and 0 <= c_idx < len(tpl_grid[r_idx]):
            t_val = tpl_grid[r_idx][c_idx]
            if isinstance(t_val, (int, float)) and int(round(t_val)) <= -900001:
                seq_key = str(abs(int(round(t_val))) - 900000)
                c_obj = cell_coords.get(seq_key)
                if isinstance(c_obj, dict):
                    return {
                        "id_baris_item": c_obj["b"],
                        "id_karakteristik_item": c_obj["k"],
                        "id_periode_item": c_obj["p"],
                        "tahun_data": c_obj.get("t"),
                    }
        return None

    if is_dynamic:
        source_label = "Tabel Dinamis"
        dyn_grid = target_info.get("dynamic_grid", [])
        if target_info.get("dynamic_empty", False) or not dyn_grid or len(dyn_grid) <= 2:
            if tpl_grid and len(tpl_grid) > 2 and cell_coords and target_pages:
                is_template_extraction = True
                source_data = tpl_grid
            else:
                return {
                    "status": "error",
                    "source_type": "Tabel Dinamis",
                    "message": "Data Tabel Dinamis belum diisi",
                    "is_api_empty": True,
                    "id_db": target_info.get("id_db"),
                    "id_indikator": target_info.get("id_indikator"),
                    "pages": target_pages or []
                }
        else:
            source_data = dyn_grid
    elif src_type_code == "spreadsheet":
        return {
            "status": "error",
            "source_type": "Belum Dikonfigurasi",
            "message": "Tabel ini belum dikonfigurasi ke Tabel Dinamis",
            "id_db": target_info.get("id_db"),
            "pages": target_pages or []
        }
    else:
        source_label = "API Satudata"
        if not link:
            return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada link API untuk tabel ini", "is_api_empty": True, "pages": target_pages or []}

        source_data = fetch_data_from_link(link)
        if isinstance(source_data, dict) and "error" in source_data:
            err_msg = source_data.get('error', '')
            detail_msg = source_data.get('detail', '')
            if source_data.get("is_api_empty") or source_data.get("error_kind") == "empty":
                return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "detail": detail_msg or err_msg, "is_api_empty": True, "pages": target_pages or []}
            return {"status": "error", "source_type": "API Satudata", "message": f"Gagal mengambil data dari link: {err_msg}", "detail": detail_msg, "error_kind": source_data.get("error_kind", ""), "pages": target_pages or []}

        if not source_data:
            return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "is_api_empty": True, "pages": target_pages or []}

    if not target_pages:
        return {"status": "error", "message": "Tabel tidak terdeteksi di PDF"}

    # Filter target_pages agar tidak mencakup halaman awal BAB baru atau halaman tabel lain berikutnya
    if target_pages and len(target_pages) > 1:
        valid_pages = []
        pymupdf_doc = None
        try:
            try:
                import pymupdf as _pm_spill
            except ImportError:
                import fitz as _pm_spill
            if os.path.exists(pdf_path):
                pymupdf_doc = _pm_spill.open(pdf_path)
        except Exception:
            pymupdf_doc = None

        target_clean = clean_num(target_table)
        re_tbl_cap = re.compile(r'(?:tabel|table)\s+(?:(?:/\s*)?(?:tabel|table)\s+)?(\d+\.\d+(?:\.\d+)?)', re.IGNORECASE)
        re_cont_cap = re.compile(rf'(?:lanjutan|continued)\s+(?:tabel|table)?\s*(?:/\s*(?:tabel|table)\s*)?{re.escape(target_clean)}\b', re.IGNORECASE)
        try:
            for p_pos, p in enumerate(target_pages):
                p_tables = get_cached_page_tables(pdf_path, p)
                if not p_tables:
                    continue
                p_first_txt = ' '.join(str(c) for r in p_tables[:5] if isinstance(r, list) for c in r).lower()
                p_head_txt = ' '.join(str(c) for r in p_tables[:15] if isinstance(r, list) for c in r if c).lower()
                is_chapter_divider = (
                    any(k in p_first_txt for k in ['bab / chapter', 'chapter \nbab', 'bab\nchapter'])
                    or bool(re.search(r'^\s*bab\s+\d+\b', p_first_txt))
                    or bool(re.search(r'^\s*\d+\.\s+[a-z\s]+/[a-z\s]+\.{4,}', p_first_txt))
                    or (bool(re.search(r'\bbab\b', p_head_txt)) and bool(re.search(r'\bchapter\b', p_head_txt)) and (len(p_tables) <= 5 or 'dalam angka' in p_head_txt))
                )
                if is_chapter_divider:
                    break
                m_other_tbl = re_tbl_cap.search(p_first_txt)
                if m_other_tbl and clean_num(m_other_tbl.group(1)) != target_clean:
                    break
                if p_pos > 0 and pymupdf_doc is not None and 0 <= p - 1 < len(pymupdf_doc):
                    p_obj = pymupdf_doc[p - 1]
                    max_y = (p_obj.rect.height or 842) * 0.25
                    top_blocks = [b for b in p_obj.get_text("blocks") if b[1] < max_y and len(b) > 4 and isinstance(b[4], str)]
                    top_blocks.sort(key=lambda b: (round(b[1] / 15.0), b[0]))
                    top_norm = re.sub(r'\s+', ' ', ' '.join(b[4].strip() for b in top_blocks))
                    m_cap = re_tbl_cap.search(top_norm)
                    if m_cap and clean_num(m_cap.group(1)) != target_clean and not re_cont_cap.search(top_norm):
                        break
                valid_pages.append(p)
        finally:
            if pymupdf_doc is not None:
                try:
                    pymupdf_doc.close()
                except Exception:
                    pass
        if valid_pages:
            target_pages = valid_pages

    target_pdf_info = [{"nomor_tabel": target_table, "pages": target_pages}]
    extract_table_content(pdf_path, target_pdf_info)
    rows = target_pdf_info[0].get("extracted_data", [])

    results = {
        "status": "success",
        "source_type": source_label,
        "target_year": str(pub_year - 1) if pub_year else None,
        "diffs": [],
        "matches": [],
        "pdf_only": [],
        "summary": {}
    }

    # CASE 1: API Satudata (List of Dict records)
    if isinstance(source_data, list) and source_data and isinstance(source_data[0], dict):
        # Deteksi dataset API kosong / dummy (baik 1 baris maupun banyak baris dengan seluruh metrik null/kosong)
        if isinstance(source_data, list) and source_data and isinstance(source_data[0], dict):
            _sys_or_dim_keys = {
                'tahun_data', 'tahun', 'kode_wilayah', 'kode_kabupaten', 'kode_kota',
                'nama_wilayah', 'nama_kabupatenkota', 'wilayah', 'kabupaten_kota',
                'kabupaten', 'kota', 'id', 'created_at', 'updated_at',
            }
            if len(source_data) <= 1:
                all_zero_or_empty = True
                for r in source_data:
                    for k, val in r.items():
                        if str(k).lower() in _sys_or_dim_keys:
                            continue
                        nv = parse_num(val, is_pdf=False)
                        if nv is not None and nv != 0.0:
                            all_zero_or_empty = False
                            break
                if all_zero_or_empty:
                    return {
                        "status": "error",
                        "source_type": "API Satudata",
                        "message": "Data API belum diisi di Portal Satu Data (hanya memuat 1 baris kosong)",
                        "is_api_empty": True,
                        "id_db": target_info.get("id_db"),
                        "pages": target_pages or []
                    }
            else:
                has_any_metric_val = False
                for r in source_data:
                    for k, val in r.items():
                        if str(k).lower() in _sys_or_dim_keys:
                            continue
                        if val is not None and str(val).strip() != '':
                            has_any_metric_val = True
                            break
                    if has_any_metric_val:
                        break
                if not has_any_metric_val:
                    return {
                        "status": "error",
                        "source_type": "API Satudata",
                        "message": "Data API belum diisi di Portal Satu Data (seluruh nilai masih kosong/null)",
                        "is_api_empty": True,
                        "id_db": target_info.get("id_db"),
                        "pages": target_pages or []
                    }

        # Fallback / General API Normalizer: convert dict list into 2D grid and let CASE 2 process it!
        api_year = None
        api_all_years = []
        pdf_years_set = set()
        if isinstance(source_data, list) and source_data and isinstance(source_data[0], dict):
            api_all_years = sorted(list(set(str(r.get('tahun_data', r.get('tahun', ''))).strip() for r in source_data if r.get('tahun_data') or r.get('tahun'))))
            api_years = list(api_all_years)
            if pub_year:
                max_dy = pub_year - 1
                elig_api_y = [y for y in api_years if y.isdigit() and int(y) <= max_dy]
                if not elig_api_y and api_years and all(y.isdigit() and int(y) > max_dy for y in api_years):
                    return {
                        "status": "error",
                        "source_type": "API Satudata",
                        "message": f"Data API untuk Tahun {max_dy} belum tersedia di Portal Satu Data (data yang ada: {', '.join(api_years)})",
                        "is_api_empty": True,
                        "is_year_unavailable": True,
                        "error_kind": "year_unavailable",
                        "requested_years": [str(max_dy)],
                        "available_years": api_years,
                        "id_db": target_info.get("id_db"),
                        "pages": target_pages or []
                    }
                if elig_api_y:
                    api_years = elig_api_y
            if rows:
                pdf_hdr_txt = "\n".join(
                    str(c) for r in rows[:30]
                    if isinstance(r, list) and not is_title_row(r)
                    for c in r if c is not None
                )
                pdf_years_set.update(extract_pdf_years_expanded(pdf_hdr_txt, pub_year=pub_year))
            if pdf_index_title:
                title_yrs_exp = extract_pdf_years_expanded(str(pdf_index_title), pub_year=pub_year)
                if not pdf_years_set or len(title_yrs_exp) > len(pdf_years_set):
                    pdf_years_set.update(title_yrs_exp)
            common_api_pdf_y = sorted(set(api_years).intersection(pdf_years_set))
            if common_api_pdf_y:
                api_year = common_api_pdf_y[-1]
            elif len(api_years) == 1 and api_years[0].isdigit() and len(api_years[0]) == 4:
                api_year = api_years[0]
            elif pub_year and str(pub_year - 1) in api_years:
                api_year = str(pub_year - 1)

        grid = normalize_api_data(source_data, rows, target_table, config_tabel=target_info.get("config_tabel"), pub_year=pub_year, pdf_title=pdf_index_title)
        if grid and len(grid) > 1 and max((len(r) for r in grid if isinstance(r, list)), default=0) > 1:
            source_data = grid
        else:
            return {
                "status": "error",
                "source_type": "API Satudata",
                "message": "Data API belum diisi di Portal Satu Data (tidak ada kolom nilai terisi)",
                "is_api_empty": True,
                "id_db": target_info.get("id_db"),
                "pages": target_pages or []
            }

    # CASE 2: Matriks 2D (Tabel Dinamis & Normalized API Tables)
    if not (source_data and isinstance(source_data[0], dict)):
        headers = detect_col_headers(source_data)
        archetype = detect_table_archetype(pdf_path, target_table, target_pages, source_data)
        sample_rows = [r for r in source_data[:35] if isinstance(r, list) and not is_title_row(r) and not is_header_or_metadata_row(r)]
        prov_matches = sum(1 for r in sample_rows if resolve_provinsi_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '00', '33.00', 'Total Jawa Tengah', 'Indonesia'])
        jateng_matches = sum(1 for r in sample_rows if resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '33.00', 'Total Jawa Tengah'])

        # Cek apakah wilayah muncul berulang kali di baris-baris data (indikasi kuat tabel bertingkat / kategori x wilayah)
        prov_entities = [resolve_provinsi_entity(' '.join(str(c) for c in r[:3]))[0] for r in sample_rows]
        prov_entities = [p for p in prov_entities if p not in [None, '00', '33.00', 'Total Jawa Tengah', 'Indonesia']]
        has_dup_prov = len(prov_entities) > len(set(prov_entities))

        jateng_entities = []
        is_kota_scan = False
        cilacap_seen_scan = False
        for r in sample_rows:
            txt_scan = ' '.join(str(c) for c in r).lower()
            if 'kabupaten/regency' in txt_scan:
                is_kota_scan = False
            elif 'kota/municipality' in txt_scan:
                is_kota_scan = True
            ent, _ = resolve_jateng_entity(' '.join(str(c) for c in r[:3]), is_kota_mode=is_kota_scan)
            if ent == '33.01':
                cilacap_seen_scan = True
            if ent and ent not in [None, '33.00', 'Total Jawa Tengah']:
                jateng_entities.append(ent)
                if ent == '33.29' and cilacap_seen_scan:  # Hanya ubah ke mode kota bila mengikuti urutan kanonik BPS (Cilacap lalu Brebes)
                    is_kota_scan = True
        has_dup_jateng = len(jateng_entities) > len(set(jateng_entities))

        has_duplicate_regions = has_dup_prov or has_dup_jateng

        is_provincial_table = (prov_matches >= 3)
        _univ_kw_re = re.compile(
            r'\b(?:universitas|institut|politeknik|akademi|sekolah\s+tinggi|perguruan\s+tinggi|'
            r'stain|iain|uin|stie|stikes|stkip|setia|stai|stia|stimik|stmik|amiki|akbid|akper)\b',
            re.IGNORECASE,
        )
        _univ_row_hits = sum(
            1 for r in rows[:35]
            if isinstance(r, list) and _univ_kw_re.search(' '.join(str(c) for c in r[:3] if c is not None))
        )
        _tbl_title_low = str((target_info or {}).get('judul_tabel', '') or '').lower()
        is_univ_table = (
            _univ_row_hits >= 3
            or (
                'perguruan tinggi' in _tbl_title_low
                and 'kabupaten' not in _tbl_title_low
                and 'kota' not in _tbl_title_low
            )
        )
        is_regional = (prov_matches >= 3 or jateng_matches >= 3) and not is_univ_table and not has_duplicate_regions
        is_categorical = (not is_regional and (archetype in ["MULTI_PAGE_ROW_CONTINUATION", "MULTI_PAGE_SECTION_PER_PAGE", "SINGLE_PAGE_CATEGORICAL", "MULTI_PAGE_COLUMN_SPLIT"])) or has_duplicate_regions

        if is_categorical:
            clean_pdf_rows = []
            for r in rows:
                if not isinstance(r, list):
                    continue
                txt = ' '.join(str(c) for c in r if str(c).strip()).lower()
                if any(k in txt for k in ['bab / chapter', 'chapter \nbab', 'bab\nchapter']) or re.search(r'^\s*bab\s+\d+\b', txt) or (re.search(r'\bbab\b', txt) and re.search(r'\bchapter\b', txt) and len(r) <= 3):
                    break
                if any(txt.startswith(k) for k in ['tabel / table', 'tabel\n', 'table\n']) and target_table not in txt:
                    m_next = re.search(r'tabel\s+(\d+\.\d+\.\d+)', txt)
                    if m_next and m_next.group(1).strip('.') != target_table.strip('.'):
                        break
                clean_pdf_rows.append(r)

            clean_db_rows = []
            for r in source_data:
                if not isinstance(r, list):
                    continue
                txt = ' '.join(str(c) for c in r).lower().strip()
                if any(txt.startswith(k) for k in ['sumber:', 'source:', 'catatan:', 'note:']):
                    break
                clean_db_rows.append(r)

            # ARCHETYPE: Stacked Period Matrix (Susenas / Gini / Distribusi Pengeluaran)
            # PDF has [Category, Period, Metric 1, Metric 2, ...]
            # DB has wide format: [Category, Metric 1 (Period 1), ..., Metric N (Period M)]
            if clean_db_rows and len(clean_db_rows) >= 3 and clean_pdf_rows and len(clean_pdf_rows) >= 3:
                db_headers = clean_db_rows[0]
                period_matches = [re.search(r'\(((?:19|20)\d{2}\s+[A-Za-z]+)\)', str(h)) for h in db_headers[1:]]
                col1_hdr = str(clean_pdf_rows[0][1]).lower() if len(clean_pdf_rows[0]) > 1 and clean_pdf_rows[0][1] else ''
                if sum(1 for m in period_matches if m) >= 4 and any(k in col1_hdr for k in ['tahun', 'bulan', 'year', 'month', 'periode']):
                    db_periods = []
                    for m in period_matches:
                        if m and m.group(1) not in db_periods:
                            db_periods.append(m.group(1))
                    db_metrics = []
                    for h in db_headers[1:]:
                        m_metric = re.sub(r'\s*\((?:19|20)\d{2}\s+[A-Za-z]+\)', '', str(h)).strip()
                        if m_metric and m_metric not in db_metrics:
                            db_metrics.append(m_metric)
                    n_periods = len(db_periods)
                    n_metrics = len(db_metrics)
                    if n_periods > 0 and n_metrics > 0:
                        data_start = 1
                        for idx_r, r in enumerate(clean_pdf_rows[:4]):
                            if any(re.match(r'^\(\d+\)$', str(c).strip()) for c in r if c):
                                data_start = idx_r + 1
                                break
                        raw_data_rows = [r for r in clean_pdf_rows[data_start:] if isinstance(r, list) and any(str(c).strip() for c in r)]
                        transformed_wide_pdf = [list(clean_db_rows[0]), list(clean_db_rows[1])]
                        cat_idx = 0
                        for chunk_start in range(0, len(raw_data_rows), n_periods):
                            chunk = raw_data_rows[chunk_start : chunk_start + n_periods]
                            if not chunk:
                                continue
                            db_target_row = clean_db_rows[2 + cat_idx] if (2 + cat_idx) < len(clean_db_rows) else None
                            cat_label = db_target_row[0] if db_target_row else str(chunk[0][0])
                            wide_vals = [cat_label]
                            for m_idx in range(n_metrics):
                                for p_idx in range(n_periods):
                                    if p_idx < len(chunk):
                                        pdf_row = chunk[p_idx]
                                        col_in_pdf = 2 + m_idx
                                        val = pdf_row[col_in_pdf] if col_in_pdf < len(pdf_row) else None
                                        wide_vals.append(val)
                                    else:
                                        wide_vals.append(None)
                            transformed_wide_pdf.append(wide_vals)
                            cat_idx += 1
                        if len(transformed_wide_pdf) > 2:
                            clean_pdf_rows = transformed_wide_pdf

            def get_cat_key(text):
                if not text:
                    return ''
                t_raw = str(text).strip()
                c_raw = re.sub(r'[\s\*]', '', t_raw)
                if c_raw in ['0', '01'] or re.match(r'^0[1-9]$', c_raw):
                    return 'JAM_0'

                if is_interval_label(t_raw):
                    _m_op = re.match(r'^([<>≤≥]=?)', c_raw)
                    if _m_op:
                        _op_map = {'<': 'LT', '<=': 'LE', '≤': 'LE', '>': 'GT', '>=': 'GE', '≥': 'GE'}
                        digits = re.findall(r'\d+', c_raw)
                        if digits:
                            return f"INT_{_op_map.get(_m_op.group(1), 'OP')}_{'_'.join(digits)}"
                    digits = re.findall(r'\d+', t_raw)
                    if digits:
                        return f"INT_{'_'.join(digits)}"
                    if '+' in c_raw:
                        return f"JAM_{c_raw.replace('+', '_PLUS')}"
                    c_dash = re.sub(r'[\-–—−\u2212\u2011\u2012\u2015\ufffd]', '_', c_raw)
                    return f"JAM_{c_dash}"

                party = match_party_name(t_raw)
                if party:
                    return f"PARTY_{party}"

                opd = match_opd_name(t_raw)
                if opd:
                    return opd

                if ' - ' in t_raw and not any(k in t_raw.lower() for k in ['s/d', 'sd', 'sampai']):
                    p_parts = [p.strip() for p in t_raw.split(' - ') if p.strip()]
                    if len(p_parts) > 1:
                        return '_'.join(get_cat_key(p) for p in p_parts)

                p = find_provinsi(t_raw)
                if p:
                    if p == 'Jawa Tengah' and not is_provincial_table:
                        return 'TOTAL'
                    return "PROV_" + re.sub(r'[^\w]', '', p.upper())

                s = re.sub(r'(\b(?:19|20)\d{2})\d+\b', r'\1', t_raw).upper()
                if 'CURAH HUJAN' in s or 'PRECIP' in s:
                    return 'CURAH_HUJAN'
                if 'HARI HUJAN' in s or 'RAINY' in s:
                    return 'HARI_HUJAN'
                if 'PENYINARAN MATAHARI' in s or 'SUNSHINE' in s:
                    return 'PENYINARAN_MATAHARI'

                m_yr = re.search(r'\b(19\d{2}|20\d{2})\b', s)
                if m_yr:
                    yr = m_yr.group(1)
                    s_no_yr = re.sub(r'\b(19\d{2}|20\d{2})\b', '', s)
                    s_no_yr_rem = re.sub(r'\b(JUMLAH|TOTAL|REKAP|REKAPITULASI|PER|TAHUN|YEAR|SEMUA|KESELURUHAN|AKHIR)\b', '', s_no_yr)
                    s_no_yr_rem = re.sub(r'[^\w]', '', s_no_yr_rem)
                    if not s_no_yr_rem:
                        return f"TOTAL_Y{yr}"

                if s in ['JUMLAH', 'TOTAL', 'JUMLAH/TOTAL', 'TOTAL/JUMLAH', 'JUMLAH TOTAL']:
                    return 'TOTAL'
                s_rem = re.sub(r'\b(JUMLAH|TOTAL|JAWA\s+TENGAH|PROVINSI|PROVINCE|KABUPATEN|REGENCY|KOTA|MUNICIPALITY|SEMUA|KESELURUHAN|AKHIR\s+TAHUN)\b', '', s)
                s_rem = re.sub(r'[^\w]', '', s_rem)
                if (s.startswith('JUMLAH') or s.startswith('TOTAL')) and not s_rem:
                    return 'TOTAL'
                if 'TENAGA KERJA' in s and any(k in s for k in ['JUMLAH', 'PENYERAPAN', 'TOTAL']):
                    return 'JUMLAH_TENAGA_KERJA'
                s = s.replace('FUNGSIHONAL', 'FUNGSIONAL').replace('FUNGSHIONAL', 'FUNGSIONAL')

                m_grade = re.search(r'\b([I|V|X]+/[A-E])\b', s)
                if m_grade:
                    return m_grade.group(1).upper()
                m_range = re.search(r'\bGOLONGAN\s+([I|V|X]+)\b', s)
                if m_range:
                    return f"GOL_{m_range.group(1).upper()}"
                if any(k in s.lower() for k in ['tidak tamat sd', 'belum tamat sd', 'not completed primary']):
                    return 'TIDAK_TAMAT_SD'
                for lvl in ['SD', 'SMP', 'SMA', 'SMK']:
                    if re.search(rf'\b{lvl}\b', s):
                        return lvl
                m_dip = re.search(r'DIPLOMA\s+(I|II|III|IV)\b', s)
                if m_dip:
                    return f"D_{m_dip.group(1)}"
                m_s = re.search(r'\b(S1|S2|S3)\b', s)
                if m_s:
                    return m_s.group(1)
                if 'STRUKTURAL' in s:
                    return 'STRUKTURAL'
                if 'TERTENTU' in s:
                    return 'FUNGSIONAL_TERTENTU'
                if ('FUNGSIONAL' in s and 'UMUM' in s) or ('STAF' in s and 'FUNGSIONAL' in s):
                    return 'FUNGSIONAL_UMUM'
                if 'MINIMUM' in s:
                    return 'MINIMUM'
                if 'MAKSIMUM' in s or 'MAXIMUM' in s:
                    return 'MAKSIMUM'
                if 'RATA' in s or 'AVERAGE' in s:
                    return 'RATA_RATA'
                if 'MEDIAN' in s:
                    return 'MEDIAN'
                m_yr = re.search(r'\b(19|20\d{2})\b', s)
                yr_pfx = f"Y{m_yr.group(0)}_" if m_yr else ""
                months = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER']
                for idx, m in enumerate(months):
                    if re.search(rf'\b{m}\b', s):
                        return f"{yr_pfx}BLN_{idx+1:02d}"
                s_key = re.sub(r'\s*\((?:%|PERSEN|PERCENT|MB|MM|HARI|JAM|KM\s*/?\s*JAM|KM\s*/?\s*H|KM|M\s*/?\s*DET|M\s*/?\s*S|CM|KG|TON|RP|RIBU|JUTA|UNIT)\)', '', s)
                return re.sub(r'[^\w]', '', s_key or s)

            def clean_cat_display(text):
                t = re.sub(r'(\b(?:19|20)\d{2})\d+\b', r'\1', str(text).strip())
                if is_interval_label(t):
                    c = re.sub(r'[\s\*]', '', str(t))
                    if c in ['0', '01']:
                        return '0 Jam (Sementara Tidak Bekerja)'
                    _m_op = re.match(r'^([<>≤≥]=?)(\d+)$', c)
                    if _m_op:
                        _op_sym = {'<=': '≤', '>=': '≥'}.get(_m_op.group(1), _m_op.group(1))
                        return f"{_op_sym} {_m_op.group(2)}"
                    digits = re.findall(r'\d+', c)
                    c_dash = ' - '.join(digits) if digits else re.sub(r'[\-–—−\u2212\u2011\u2012\u2015\ufffd]', ' - ', c)
                    first_r_txt = ' '.join(str(x) for x in (clean_db_rows[0] if clean_db_rows else [])).lower()
                    is_age = any(k in first_r_txt for k in ['umur', 'usia', 'sekolah', 'pendidikan']) or target_table.startswith('4.')
                    is_hours = 'jam' in first_r_txt or 'hour' in first_r_txt
                    if is_age and not is_hours:
                        return f"{c_dash} Tahun"
                    elif is_hours:
                        return f"{c} Jam" if '+' in c else f"{c_dash} Jam"
                    else:
                        return f"{c_dash}"
                if ' - ' in t and not any(k in t.lower() for k in ['s/d', 'sd', 'sampai']):
                    p_parts = [p.strip() for p in t.split(' - ') if p.strip()]
                    if len(p_parts) > 1:
                        return ' - '.join(clean_cat_display(p) for p in p_parts)
                p = find_provinsi(t)
                if p:
                    return p
                m_grade = re.search(r'\b([I|V|X]+/[A-E])\b', t, re.IGNORECASE)
                if m_grade:
                    return f"Golongan {m_grade.group(1).upper()}"
                m_range = re.search(r'\bGolongan\s+([I|V|X]+)\b', t, re.IGNORECASE)
                if m_range:
                    return f"Golongan {m_range.group(1).upper()}"
                t_low = t.lower()
                if 'curah hujan' in t_low:
                    return 'Jumlah Curah Hujan'
                if 'hari hujan' in t_low:
                    return 'Jumlah Hari Hujan'
                if 'penyinaran matahari' in t_low:
                    return 'Penyinaran Matahari'
                t = re.sub(r'^[a-z\.\s/:\n]+(?=[A-Z0-9])', '', t).strip()
                if not re.match(r'^(?:19|20)\d{2}\b', t):
                    t = re.sub(r'^(?:[IVXLCDM]+|[A-Za-z]|\d+)[\.\s]+', '', t).strip()
                if '/' in t and not re.search(r'\d/\d|\bs/d\b|\b[IVX]+/[A-E]\b|\b(?:km|m)\s*/\s*(?:jam|h|det|s)\b', t, re.IGNORECASE):
                    m_yr_in_t = re.search(r'\b(19\d{2}|20\d{2})\b', t)
                    t_left = t.split('/')[0].strip()
                    if m_yr_in_t and not re.search(r'\b(19\d{2}|20\d{2})\b', t_left):
                        t_left = f"{t_left} {m_yr_in_t.group(1)}"
                    if t_left:
                        t = re.sub(r'\s+', ' ', t_left.replace('\n', ' ')).strip()
                elif '\n' in t and any(t.lower().startswith(p) for p in ['jumlah koperasi', 'jumlah modal', 'jumlah volume', 'koperasi\n', 'koperasi ', 'penyerapan\n']):
                    non_en = [l.strip() for l in t.split('\n') if l.strip() and not re.match(r'^(?:Total\b|Marketing\b|Cooperative\b|Business\b|Volume\b|Equity\b|Outside\b|Producer\b|Consumer\b|Service\b|Savings\b|Red\b|White\b|Village\b|Subdistrict\b)', l.strip(), re.IGNORECASE)]
                    if non_en:
                        t = ' '.join(non_en)
                if any(k in t.lower() for k in ['universitas', 'sekolah tinggi', 'institut', 'akademi', 'politeknik', 'stai', 'stit', 'fai', 'stie', 'stiq', 'stim', 'prodi', 'program studi']):
                    t = ' '.join(l.strip() for l in t.split('\n') if l.strip())
                lines = [l.strip() for l in t.split('\n') if l.strip()]
                valid_lines = [l for l in lines if len(l) > 2 or any(c.isdigit() for c in l)]
                if len(valid_lines) >= 3 and '/' not in t:
                    en_start = next((idx for idx in range(1, len(valid_lines)) if is_english_line(valid_lines[idx])), None)
                    if en_start and en_start >= 2:
                        first_line = ' '.join(valid_lines[:en_start])
                    else:
                        first_line = valid_lines[0]
                elif len(valid_lines) == 2 and '/' not in t and not is_english_line(valid_lines[1]) and (
                    valid_lines[0].endswith((',', 'dan', 'atau', 'serta'))
                    or valid_lines[1][0].islower()
                    or valid_lines[0].count('(') > valid_lines[0].count(')')
                    or bool(re.search(r'\([A-Z0-9\-]{2,}\)', valid_lines[1]))
                ):
                    first_line = ' '.join(valid_lines)
                else:
                    first_line = valid_lines[0] if valid_lines else (lines[0] if lines else t)
                m_bi_yr = re.match(r'^([A-Za-z]+)\s*/\s*[A-Za-z]+\s+(\d{4})$', first_line)
                if m_bi_yr:
                    first_line = f"{m_bi_yr.group(1)} {m_bi_yr.group(2)}"
                elif '/' in first_line and not re.search(r'\d/\d|\bs/d\b|\b[IVX]+/[A-E]\b|\b(?:km|m)\s*/\s*(?:jam|h|det|s)\b', first_line, re.IGNORECASE):
                    first_line = first_line.split('/')[0].strip()
                first_line = re.sub(r'\s+[a-z]$', '', first_line)
                clean = re.sub(r'[^\w\s\(\)/]', '', first_line).strip()
                clean = re.sub(r'\s+', ' ', clean).strip()
                return clean if clean else t

            headers = detect_col_headers(clean_db_rows)
            src_title = ' '.join(str(c) for c in (source_data[0] if source_data else []))
            title_years = _extract_header_years(src_title)
            if pub_year and title_years:
                elig_ty_title = [y for y in title_years if int(y) <= (pub_year - 1)]
                if elig_ty_title:
                    title_years = elig_ty_title

            # Ekstrak tahun tercetak langsung dari judul PDF untuk referensi murni sisi PDF (tidak menyontek tahun lama DB)
            _pdf_title_src = str(pdf_index_title or '')
            if not _pdf_title_src and rows:
                _pdf_title_src = ' '.join(str(c) for r in rows[:4] if isinstance(r, list) for c in r if c and is_title_row(r))
            _m_acad_pdf = re.findall(r'\b((?:19|20)\d{2}/(?:19|20)\d{2})\b', _pdf_title_src)
            _pdf_title_yrs = _extract_header_years(_pdf_title_src)
            if pub_year and _pdf_title_yrs:
                _elig_pdf_ty = [y for y in _pdf_title_yrs if int(y) <= (pub_year - 1)]
                if _elig_pdf_ty:
                    _pdf_title_yrs = _elig_pdf_ty
            if _m_acad_pdf:
                results["pdf_target_year"] = _m_acad_pdf[-1]
            elif _pdf_title_yrs:
                results["pdf_target_year"] = _pdf_title_yrs[-1]
            elif pub_year:
                results["pdf_target_year"] = str(pub_year - 1)

            target_year = title_years[-1] if title_years else (_pdf_title_yrs[-1] if _pdf_title_yrs else (api_year if 'api_year' in locals() and api_year else None))
            if not target_year:
                max_dy_str = str(pub_year - 1) if pub_year else None
                coord_years = [str(c.get('t')) for c in cell_coords.values() if isinstance(c, dict) and c.get('t')]
                if max_dy_str and coord_years:
                    elig_c = [y for y in coord_years if y.isdigit() and y <= max_dy_str]
                    if elig_c:
                        coord_years = elig_c
                if coord_years:
                    target_year = max(coord_years)
                elif target_info.get("tahun_tampil"):
                    ty_parts = _extract_header_years(str(target_info.get("tahun_tampil")))
                    if max_dy_str and ty_parts:
                        elig_ty = [y for y in ty_parts if y <= max_dy_str]
                        if elig_ty:
                            ty_parts = elig_ty
                    if ty_parts:
                        target_year = ty_parts[-1]
                elif target_info.get("tahun"):
                    m_th = re.search(r'\b(20\d{2})\b', str(target_info.get("tahun")))
                    if m_th:
                        ty_val = m_th.group(1)
                        target_year = str(int(ty_val) - 1) if (pub_year and int(ty_val) == pub_year) else ty_val
            db_header_years = set(_extract_header_years(' '.join(headers)))

            pdf_by_year = {}
            cur_y = "default"
            for r in clean_pdf_rows:
                if not isinstance(r, list):
                    continue
                txt = ' '.join(str(c) for c in r)
                m = re.search(r'\b(20\d{2})\*?\b', txt)
                if m and not any(parse_num(c, is_pdf=True) for c in r if str(c).strip() and re.sub(r'[\s\*\(\)]', '', str(c)) != m.group(1)):
                    cur_y = m.group(1)
                    if cur_y not in pdf_by_year:
                        pdf_by_year[cur_y] = []
                    continue
                if cur_y not in pdf_by_year:
                    pdf_by_year[cur_y] = []
                pdf_by_year[cur_y].append(r)

            if not target_year and len(pdf_by_year) > 1:
                valid_num_years = [y for y in pdf_by_year if y.isdigit()]
                if valid_num_years:
                    target_year = max(valid_num_years)
            if target_year:
                results["target_year"] = str(target_year)

            if target_year and target_year in pdf_by_year and len(pdf_by_year) > 1 and len(db_header_years) <= 1:
                target_pdf_rows = pdf_by_year[target_year]
                hist_pdf_years = {y: r_list for y, r_list in pdf_by_year.items() if y != target_year and y != "default"}
            else:
                target_pdf_rows = clean_pdf_rows
                hist_pdf_years = {}

            active_cat_cols = detect_active_data_cols(clean_db_rows)

            def get_gender_sec(txt, r, cur_sec_val="", has_tpl_coord=False):
                if has_tpl_coord:
                    return None
                if any(parse_num(c, is_pdf=True) is not None for c in r if str(c).strip()):
                    return None
                non_empty_cells = [str(c).strip() for c in r if c is not None and str(c).strip() and str(c).strip().lower() != 'none']
                if not non_empty_cells:
                    return None
                if len(non_empty_cells) >= 2 and any(c in ('-', '–', '—', '−', '...', '…') for c in non_empty_cells[1:]):
                    return None
                all_txt = txt.lower()
                if re.search(r'\b(kabupaten|regency)\b', all_txt) and not re.search(r'\b(kota|municipality)\b', all_txt):
                    return "Kabupaten"
                elif re.search(r'\b(kota|municipality)\b', all_txt) and not re.search(r'\b(kabupaten|regency)\b', all_txt):
                    return "Kota"
                if len(non_empty_cells) == 1:
                    clean_lbl = clean_cat_display(non_empty_cells[0]).lower().strip()
                    clean_lbl = re.sub(r'^(?:\d+|[a-z]|[ivxlcdm]+)[\.\)]\s*', '', clean_lbl).strip()
                    if clean_lbl in ('perkotaan', 'urban', 'daerah perkotaan'):
                        return "Perkotaan"
                    if clean_lbl in ('perdesaan', 'pedesaan', 'rural', 'daerah perdesaan', 'daerah pedesaan'):
                        return "Perdesaan"
                    if clean_lbl in ('perkotaan + perdesaan', 'perkotaan dan perdesaan', 'perdesaan + perkotaan'):
                        return "Jumlah"
                    if clean_lbl in ('jumlah', 'total') and cur_sec_val in ("Laki-Laki", "Perempuan", "Laki-Laki + Perempuan", "Perkotaan", "Perdesaan"):
                        return "Jumlah"
                    all_txt = clean_lbl or all_txt
                has_male = bool(re.search(r'\b(laki[\s\-_]?laki|lakilaki|male)\b', all_txt))
                has_female = bool(re.search(r'\b(perempuan|female)\b', all_txt))
                if has_male and has_female:
                    return "Laki-Laki + Perempuan"
                if has_male:
                    return "Laki-Laki"
                elif has_female:
                    return "Perempuan"
                return None

            def extract_cat_rows_std(data_list, is_pdf=False, target_cols=None):
                from collections import Counter
                records = []
                raw_items = []
                cur_sec = ""
                use_cols = target_cols if target_cols is not None else active_cat_cols
                last_parent_label = ""
                last_total_label = ""
                last_year_label = ""
                _tpl = tpl_grid if (not is_pdf and isinstance(tpl_grid, list) and len(tpl_grid) == len(data_list)) else None

                pdf_data_col_indices = []
                unit_cols = set()
                bps_header_limit_idx = -1

                def _resolve_bps_cols(row_idx, r_check):
                    from table_profiler import _clean_bps_or_year_cell
                    bps_num_cols = []
                    for idx, cell in enumerate(r_check):
                        if cell is None:
                            continue
                        cleaned_c = _clean_bps_or_year_cell(cell)
                        if re.match(r'^\(\d+\)$', cleaned_c) and int(re.search(r'\d+', cleaned_c).group(0)) >= 2:
                            bps_num_cols.append(idx)
                    if not bps_num_cols:
                        return None, set()
                    cur_unit_cols = set()
                    hdr_start = max(0, row_idx - 4)
                    for r_hdr in data_list[hdr_start:row_idx]:
                        if isinstance(r_hdr, list):
                            for c_idx in bps_num_cols:
                                if c_idx < len(r_hdr) and r_hdr[c_idx] is not None:
                                    txt = str(r_hdr[c_idx]).strip().lower()
                                    is_dim = bool(re.search(r'\b(satuan|unit|jenjang|akreditasi)\b', txt)) and not bool(re.search(r'\b(jumlah|banyaknya|total|nilai|pendidikan|kapal|kerja|usaha|rumah)\b', txt))
                                    if is_dim:
                                        cur_unit_cols.add(c_idx)
                    text_dim_cols = set()
                    for c_idx in bps_num_cols:
                        if c_idx in cur_unit_cols:
                            continue
                        alpha_cnt = 0
                        num_or_dash_cnt = 0
                        for sr in data_list[row_idx + 1 : row_idx + 35]:
                            if not isinstance(sr, list):
                                continue
                            if any(is_bps_col_num(str(x)) for x in sr if x is not None):
                                break
                            all_sr = ' '.join(str(x).strip().lower() for x in sr if str(x).strip())
                            if any(all_sr.startswith(k) for k in ['sumber', 'source', 'catatan', 'note', 'perubahan data', 'jumlah', 'total']):
                                continue
                            if c_idx >= len(sr) or sr[c_idx] is None:
                                continue
                            s_txt = str(sr[c_idx]).strip()
                            if not s_txt or s_txt.lower() == 'none':
                                continue
                            if s_txt in ('-', '–', '—', '−'):
                                num_or_dash_cnt += 1
                            elif parse_num(s_txt, is_pdf=True) is not None:
                                num_or_dash_cnt += 1
                            elif re.search(r'[a-zA-Z]{3,}', s_txt):
                                alpha_cnt += 1
                        if alpha_cnt >= 2 and alpha_cnt > num_or_dash_cnt:
                            text_dim_cols.add(c_idx)
                    resolved_cols = [c for c in bps_num_cols if c not in cur_unit_cols and c not in text_dim_cols]
                    return resolved_cols, cur_unit_cols

                if is_pdf:
                    for r_idx, r_check in enumerate(data_list):
                        if isinstance(r_check, list):
                            res_cols, res_units = _resolve_bps_cols(r_idx, r_check)
                            if res_cols:
                                bps_header_limit_idx = r_idx
                                unit_cols = res_units
                                pdf_data_col_indices = res_cols
                                break

                for r_idx, r in enumerate(data_list):
                    if is_pdf and bps_header_limit_idx != -1 and r_idx <= bps_header_limit_idx:
                        continue
                    if not isinstance(r, list):
                        continue
                    all_txt = ' '.join(str(c).strip().lower() for c in r if str(c).strip())
                    if any(all_txt.startswith(k) for k in ['sumber', 'source', 'catatan', 'note', 'perubahan data']):
                        break
                    if any(k in all_txt for k in ['sumber:', 'source:', 'catatan:', 'note:', 'sumber/source', 'catatan/note']):
                        break

                    if is_pdf and any(is_bps_col_num(str(c)) for c in r if c is not None):
                        res_cols, res_units = _resolve_bps_cols(r_idx, r)
                        if res_cols:
                            pdf_data_col_indices = res_cols
                            unit_cols = res_units
                        continue
                    if is_header_or_metadata_row(r):
                        continue
                    if is_pdf and any(k in all_txt for k in ['jenis usaha', 'satuan']) and re.search(r'\b(?:19|20)\d{2}\b', all_txt):
                        continue

                    _row_has_tpl_coord = False
                    if _tpl is not None and r_idx < len(_tpl) and isinstance(_tpl[r_idx], list):
                        _row_has_tpl_coord = any(
                            isinstance(_tpl[r_idx][c], (int, float)) and int(round(_tpl[r_idx][c])) <= -900001
                            for c in (use_cols or range(1, len(_tpl[r_idx]))) if c < len(_tpl[r_idx])
                        )
                    sec = get_gender_sec(all_txt, r, cur_sec_val=cur_sec, has_tpl_coord=_row_has_tpl_coord)
                    if sec:
                        cur_sec = sec
                        raw_items.append({'kind': 'sec'})
                        continue

                    code = None
                    is_sub_row_with_parent = False
                    _cur_all_dash = False
                    _has_num_prefix = False
                    row_coords = []
                    if not is_pdf and use_cols:
                        first_col = use_cols[0]
                        db_attr_cols = set()
                        if first_col > 1 and data_list and isinstance(data_list[0], list):
                            c0 = str(r[0]).strip()
                            if re.match(r'^\d+(?:\.\d+)*\.?$', c0) and not re.match(r'^(?:19|20)\d{2}', c0):
                                code = c0.rstrip('.')
                            for _ci in range(1, first_col):
                                if _ci < len(data_list[0]) and data_list[0][_ci] is not None:
                                    _htxt = str(data_list[0][_ci]).strip().lower()
                                    if bool(re.search(r'\b(satuan|unit|jenjang|akreditasi)\b', _htxt)) and not bool(re.search(r'\b(jumlah|banyaknya|total|nilai|pendidikan|kapal|kerja|usaha|rumah)\b', _htxt)):
                                        db_attr_cols.add(_ci)
                        _raw_label_parts = [
                            str(r[_ci]).strip() for _ci in range(first_col)
                            if _ci not in db_attr_cols and _ci < len(r) and r[_ci] is not None and str(r[_ci]).strip() and str(r[_ci]).strip().lower() != 'none'
                        ]
                        _has_num_prefix = bool(code) or any(re.match(r'^\d+[\.\)]\s+\S', lc) for lc in _raw_label_parts)
                        label_cells = [re.sub(r'^\d+\.\s+', '', lc) for lc in _raw_label_parts]
                        raw_vals = [
                            None if (r[c] is None or str(r[c]).strip().lower() in ('', 'none', 'null'))
                            else (0.0 if str(r[c]).strip() in ('-', '–', '—') else parse_num(str(r[c]).strip(), is_pdf=False))
                            for c in use_cols if c < len(r)
                        ]
                        if not any(v is not None for v in raw_vals):
                            grp_raw = ' '.join(c for c in label_cells if parse_num(c) is None).strip()
                            if grp_raw and re.search(r'[a-zA-Z0-9]{2,}|[<>≤≥]', grp_raw) and not is_header_or_metadata_row(r):
                                grp_clean = clean_cat_display(grp_raw)
                                if grp_clean:
                                    _gk_empty = get_cat_key(grp_clean)
                                    _is_unit_only = grp_clean.lower().strip() in ('m3', 'm2', 'km', 'km2', 'ha', 'kg', 'ton', 'orang', 'jiwa', 'unit', 'persen', '%')
                                    if _tpl is not None and r_idx < len(_tpl) and isinstance(_tpl[r_idx], list):
                                        _t_r = _tpl[r_idx]
                                        _has_tpl_coord = any(
                                            isinstance(_t_r[c], (int, float)) and int(round(_t_r[c])) <= -900001
                                            for c in use_cols if c < len(_t_r)
                                        )
                                        if not _has_tpl_coord:
                                            raw_items.append({'kind': 'group_cand', 'label': grp_clean, 'sub_key': _gk_empty, 'sec': cur_sec, 'unit_only': _is_unit_only})
                                        else:
                                            raw_items.append({'kind': 'leaf_blank', 'label': grp_clean, 'sub_key': _gk_empty, 'sec': cur_sec, 'unit_only': _is_unit_only})
                                    else:
                                        raw_items.append({'kind': 'empty_cand', 'label': grp_clean, 'sub_key': _gk_empty, 'sec': cur_sec, 'unit_only': _is_unit_only})
                            continue
                        has_alpha_label = any(re.search(r'[a-zA-Z]{3,}', c) for c in label_cells)
                        if has_alpha_label:
                            raw_label = ' '.join(c for c in label_cells if parse_num(c) is None)
                        elif any(is_interval_label(c) for c in label_cells):
                            raw_label = next(c for c in label_cells if is_interval_label(c))
                        elif any(re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c) for c in label_cells):
                            raw_label = next(c for c in label_cells if re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c))
                        elif code:
                            raw_label = code
                        elif label_cells:
                            raw_label = label_cells[0]
                        else:
                            raw_label = ' '.join(c for c in label_cells if parse_num(c) is None)
                        nums = []
                        for c_idx in use_cols:
                            raw_c = r[c_idx] if c_idx < len(r) else None
                            if raw_c is None or str(raw_c).strip().lower() in ('', 'none', 'null'):
                                nums.append(None)
                            else:
                                c_str = str(raw_c).strip()
                                v = 0.0 if c_str in ('-', '–', '—') else parse_num(c_str, is_pdf=False)
                                nums.append(v)
                            row_coords.append(_get_tpl_coord(r_idx, c_idx))
                    elif is_pdf and pdf_data_col_indices:
                        first_col = pdf_data_col_indices[0]
                        label_cells = [str(r[c]).strip() for c in range(first_col) if c not in unit_cols and c < len(r) and r[c] is not None and str(r[c]).strip() and str(r[c]).strip().lower() != 'none']

                        if len(r) >= 2:
                            c0_txt = str(r[0]).strip() if r[0] is not None else ''
                            c1_txt = str(r[1]).strip() if r[1] is not None else ''
                            m_y = re.search(r'\b(19\d{2}|20\d{2})\b', c0_txt)
                            if m_y:
                                last_year_label = m_y.group(1)
                            m_m = re.search(r'\b(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember|March|August|October|December)\b', c1_txt, re.I)
                            if m_m and last_year_label:
                                en_id = {'March': 'Maret', 'August': 'Agustus', 'October': 'Oktober', 'December': 'Desember'}
                                bln_str = en_id.get(m_m.group(1).capitalize(), m_m.group(1).capitalize())
                                label_cells = [f"{last_year_label} {bln_str}"]

                        if label_cells and re.match(r'^\d+\.?$', label_cells[0]):
                            code = label_cells[0].rstrip('.')
                            cand_parent = label_cells[1] if len(label_cells) > 1 else ""
                        else:
                            cand_parent = ""

                        raw_nums = []
                        valid_num_count = 0
                        has_any_cell = False
                        for c_idx in pdf_data_col_indices:
                            c_val = str(r[c_idx]).strip() if c_idx < len(r) and r[c_idx] is not None else ''
                            if c_val != '':
                                has_any_cell = True
                            if c_val in ('-', '–', '—', '−', ''):
                                raw_nums.append(0.0)
                            else:
                                v = parse_num(c_val, is_pdf=True)
                                if v is not None:
                                    valid_num_count += 1
                                    raw_nums.append(v)
                                else:
                                    raw_nums.append(0.0)

                        has_dash = any(str(r[c_idx]).strip() in ('-', '–', '—', '−') for c_idx in pdf_data_col_indices if c_idx < len(r) and r[c_idx] is not None)
                        if not has_any_cell or (valid_num_count == 0 and not has_dash):
                            # Skip repeated page-top column header rows directly preceding a BPS numbering row
                            _is_rep_hdr = False
                            if r_idx > bps_header_limit_idx:
                                for _la in range(1, 4):
                                    if r_idx + _la < len(data_list) and isinstance(data_list[r_idx + _la], list):
                                        if any(is_bps_col_num(str(_c)) for _c in data_list[r_idx + _la] if _c is not None):
                                            _is_rep_hdr = True
                                            break
                            if _is_rep_hdr:
                                continue
                            if cand_parent:
                                _cp = clean_cat_display(cand_parent)
                                last_parent_label = _cp
                                raw_items.append({'kind': 'numbered_parent', 'label': _cp, 'sec': cur_sec})
                            else:
                                _grp_raw = ' '.join(label_cells).strip() if label_cells else ''
                                _grp_clean = clean_cat_display(_grp_raw) if _grp_raw and re.search(r'[a-zA-Z]{2,}', _grp_raw) else ''
                                if _grp_clean and not is_header_or_metadata_row(r):
                                    raw_items.append({'kind': 'group_cand', 'label': _grp_clean, 'sec': cur_sec})
                            continue
                        _cur_all_dash = (valid_num_count == 0 and has_dash)

                        nums = raw_nums
                        filtered_labels = [c for c in label_cells if c not in ('-', '–', '—', '−', '•', '·') and not (re.match(r'^\d+\.?$', c) and not re.match(r'^(?:19|20)\d{2}$', c))]

                        col0_val = str(r[0]).strip() if len(r) > 0 and r[0] is not None else ''
                        has_bullet = any(c in ('-', '–', '—', '−', '•', '·') for c in label_cells)
                        has_empty_col0 = (col0_val == '' and not cand_parent and not (label_cells and re.match(r'^\d+\.?$', label_cells[0])))
                        if (has_bullet or has_empty_col0) and last_parent_label:
                            is_sub_row_with_parent = True
                        elif cand_parent and len(filtered_labels) >= 2:
                            last_parent_label = clean_cat_display(cand_parent)
                            is_sub_row_with_parent = True

                        is_tot_label = False
                        for c in label_cells:
                            c_clean = re.sub(r'[\s/]+', ' ', c.lower()).strip()
                            if c_clean in ('jumlah', 'total', 'jumlah total', 'total jumlah') or re.match(r'^(?:jumlah|total)\s+(?:19|20)\d{2}$', c_clean):
                                is_tot_label = True
                                break

                        if is_tot_label:
                            last_total_label = "Jumlah"
                            last_parent_label = ""
                            yr_part = next((c for c in label_cells if re.match(r'^(19|20)\d{2}$', c)), '')
                            raw_label = f"Jumlah {yr_part}".strip()
                        elif len(label_cells) == 1 and re.match(r'^(19|20)\d{2}$', label_cells[0]) and last_total_label:
                            raw_label = f"{last_total_label} {label_cells[0]}"
                        else:
                            if not filtered_labels and len(label_cells) == 1 and (re.match(r'^\d+$', label_cells[0]) or re.match(r'^0[1-9]$', label_cells[0])):
                                raw_label = re.sub(r'^0[1-9]$', '0', label_cells[0])
                            elif cand_parent and len(filtered_labels) >= 2:
                                raw_label = ' '.join(filtered_labels[1:])
                            else:
                                raw_label = ' '.join(filtered_labels)
                    else:
                        first_idx = next((i for i, c in enumerate(r) if c is not None and str(c).strip() and str(c).strip().lower() != 'none'), -1)
                        raw_label = ''
                        nums = []
                        for idx_c, c in enumerate(r):
                            c_str = str(c).strip() if c is not None else ''
                            if not c_str or c_str.lower() == 'none':
                                continue
                            if is_pdf and not raw_label and not nums and c_str in ('-', '–', '—', '•', '·'):
                                if any(re.search(r'[a-zA-Z]{2,}', str(x)) for x in r[idx_c+1:] if x is not None):
                                    continue
                            if not raw_label and (is_interval_label(c_str) or re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c_str)) and not nums:
                                raw_label = c_str
                                continue
                            c_code_cand = re.sub(r'\s+', '', c_str)
                            if idx_c == first_idx and re.match(r'^\d+(?:\.\d+)*\.?$', c_code_cand) and not is_interval_label(c_str) and not re.match(r'^(?:19|20)\d{2}', c_code_cand):
                                code = c_code_cand.rstrip('.')
                                continue
                            if not nums and re.match(r'^(19|20)\d{2}$', c_str) and any(k in raw_label.lower() for k in ['jumlah', 'total']):
                                raw_label += ' ' + c_str
                                continue
                            if is_pdf and not raw_label and not nums and re.match(r'^(19|20)\d{2}$', c_str) and last_total_label:
                                raw_label = f"{last_total_label} {c_str}"
                                continue
                            c_low_unit = c_str.lower().strip()
                            if is_pdf and raw_label and not nums and (
                                c_low_unit in ('unit', 'unit/unit', 'orang', 'orang/people', 'jiwa', 'persen', 'percent', '%') or
                                re.match(r'^(?:rp\.?\s*)?(?:juta|miliar|milyar|ribu|triliun)\b', c_low_unit) or
                                re.match(r'^(?:unit|orang|buah|paket|lembar|ekor|ton|kg|km|ha|hektar)\s*/\s*[a-z]+$', c_low_unit)
                            ):
                                continue
                            if not nums and re.search(r'[a-zA-Z]{3,}', c_str):
                                if not raw_label:
                                    raw_label = c_str
                                else:
                                    raw_label += ' ' + c_str
                                continue
                            v = parse_num(c_str, is_pdf=is_pdf)
                            if v is not None:
                                nums.append(v)
                                if not is_pdf:
                                    row_coords.append(_get_tpl_coord(r_idx, idx_c))
                            else:
                                if not nums:
                                    if not raw_label:
                                        raw_label = c_str
                                    else:
                                        raw_label += ' ' + c_str

                    if is_pdf and not pdf_data_col_indices:
                        col0_txt = str(r[0]).strip() if len(r) > 0 and r[0] is not None else ''
                        col1_txt = str(r[1]).strip() if len(r) > 1 and r[1] is not None else ''
                        col0_clean = re.sub(r'\s+', '', col0_txt)
                        if not code and re.match(r'^\d+(?:\.\d+)*\.?$', col0_clean) and not re.match(r'^(?:19|20)\d{2}', col0_clean):
                            code = col0_clean.rstrip('.')
                        if not nums and code and col1_txt and re.search(r'[a-zA-Z]{3,}', col1_txt):
                            last_parent_label = clean_cat_display(col1_txt)
                            continue
                        if col0_txt and any(k in col0_txt.lower() for k in ['jumlah', 'total']):
                            last_parent_label = ""
                            last_total_label = "Jumlah"
                        elif col0_txt and parse_num(col0_txt, is_pdf=True) is None and not is_bps_col_num(col0_txt):
                            last_parent_label = col0_txt
                        if not col0_txt and col1_txt and last_parent_label and parse_num(col1_txt, is_pdf=True) is None:
                            is_sub_row_with_parent = True

                    if not raw_label:
                        label_candidates = [str(c).strip() for c in r[:2] if str(c).strip()]
                        if any(is_interval_label(c) for c in label_candidates):
                            raw_label = next(c for c in label_candidates if is_interval_label(c))
                        elif any(re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c) for c in label_candidates):
                            raw_label = next(c for c in label_candidates if re.match(r'^(?:19|20)\d{2}(?:[/\-–]\d{2,4})?$', c))
                        elif code:
                            raw_label = code
                        else:
                            raw_label = ' '.join(str(c).strip() for c in r[:2] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None)

                    clean_disp = clean_cat_display(raw_label)
                    if not clean_disp or clean_disp.lower() in ('tahun', 'years', 'tahun years', 'tahun/years', 'periode', 'jumlah jam kerja pada pekerjaan utama', 'lapangan pekerjaan utama'):
                        continue
                    sub_key = get_cat_key(clean_disp)
                    _raw_sub = clean_disp
                    if is_sub_row_with_parent and last_parent_label and last_parent_label.lower() not in clean_disp.lower():
                        clean_disp = f"{last_parent_label} - {clean_disp}"
                    key = get_cat_key(clean_disp)
                    if any(k in clean_disp.lower() for k in ['jumlah', 'total']):
                        last_total_label = "Jumlah"
                    if nums and key:
                        _is_tot_flag = (sub_key == 'TOTAL' or sub_key.startswith('TOTAL_Y'))
                        raw_items.append({
                            'kind': 'data',
                            'raw_label': raw_label,
                            'clean_disp': clean_disp,
                            'raw_sub_disp': _raw_sub,
                            'sub_key': sub_key,
                            'key': key,
                            'is_sub_row_with_parent': is_sub_row_with_parent,
                            'is_tot_label': _is_tot_flag,
                            'has_num_prefix': _has_num_prefix,
                            'nums': nums,
                            'coords': row_coords if not is_pdf else [],
                            'sec': cur_sec,
                            'code': code,
                            'all_dash_no_num': _cur_all_dash and not is_sub_row_with_parent and not code,
                        })

                # Pass B: Resolve empty candidates, all-dash section headers, and multi-level hierarchy
                _data_sub_keys = {it['sub_key'] for it in raw_items if it['kind'] == 'data'}
                for it in raw_items:
                    if it['kind'] == 'empty_cand':
                        it['kind'] = 'leaf_blank' if it['sub_key'] in _data_sub_keys else 'group_cand'

                _known_grp_keys = {get_cat_key(it['label']) for it in raw_items if it['kind'] == 'group_cand'}
                for it in raw_items:
                    if it['kind'] == 'data' and it.get('all_dash_no_num') and not it.get('is_tot_label'):
                        if it['sub_key'] in _known_grp_keys:
                            it['kind'] = 'group_cand'
                            it['label'] = it['raw_sub_disp']

                _leaf_counts = Counter(it['sub_key'] for it in raw_items if it['kind'] in ('data', 'leaf_blank'))
                _has_repeated_leaves = any(c > 1 for c in _leaf_counts.values())

                # Identify consecutive group header runs to separate 1st-level (e.g. station) and 2nd-level (e.g. element) blocks
                _runs = []
                _cur_run = []
                for it in raw_items:
                    if it['kind'] == 'group_cand':
                        _cur_run.append(it['label'])
                    else:
                        if _cur_run:
                            _runs.append(_cur_run)
                            _cur_run = []
                if _cur_run:
                    _runs.append(_cur_run)

                _has_2level = any(len(rn) >= 2 for rn in _runs)
                _known_lvl1_keys = {get_cat_key(rn[0]) for rn in _runs if len(rn) >= 2}
                _known_lvl2_keys = {get_cat_key(g) for rn in _runs if len(rn) >= 2 for g in rn[1:]}
                _grp_freq = Counter(get_cat_key(it['label']) for it in raw_items if it['kind'] == 'group_cand')

                _cur_lvl1 = ""
                _cur_lvl2 = ""
                _idx_it = 0
                while _idx_it < len(raw_items):
                    it = raw_items[_idx_it]
                    if it['kind'] in ('sec', 'numbered_parent'):
                        _cur_lvl1 = ""
                        _cur_lvl2 = ""
                        _idx_it += 1
                        continue
                    if it['kind'] == 'group_cand':
                        _rn = []
                        while _idx_it < len(raw_items) and raw_items[_idx_it]['kind'] == 'group_cand':
                            _rn.append(raw_items[_idx_it]['label'])
                            _idx_it += 1
                        if len(_rn) >= 2:
                            _cur_lvl1 = _rn[0]
                            _cur_lvl2 = _rn[-1]
                        else:
                            _g0 = _rn[0]
                            _gk = get_cat_key(_g0)
                            if not _has_2level:
                                _cur_lvl1 = _g0
                                _cur_lvl2 = ""
                            elif _gk in _known_lvl1_keys:
                                _cur_lvl1 = _g0
                                _cur_lvl2 = ""
                            elif _gk in _known_lvl2_keys or (_cur_lvl1 and _grp_freq[_gk] > 1):
                                _cur_lvl2 = _g0
                            else:
                                _cur_lvl1 = _g0
                                _cur_lvl2 = ""
                        continue
                    it['lvl1'] = _cur_lvl1
                    it['lvl2'] = _cur_lvl2
                    _idx_it += 1

                _within_lvl1_counts = Counter(
                    (get_cat_key(it.get('lvl1', '')), it['sub_key'])
                    for it in raw_items
                    if it['kind'] in ('data', 'leaf_blank') and it.get('lvl1')
                )
                _multi_subkeys_in_lvl1 = {
                    sk for (_l1k, sk), cnt in _within_lvl1_counts.items() if cnt > 1
                }

                for it in raw_items:
                    if it['kind'] != 'data':
                        continue
                    c_disp = it['clean_disp']
                    c_sec = it['sec']
                    c_sub_key = it['sub_key']
                    l1 = it.get('lvl1', '')
                    l2 = it.get('lvl2', '')
                    if _has_repeated_leaves and not c_sec and not it.get('is_tot_label') and not it.get('is_sub_row_with_parent') and _leaf_counts[c_sub_key] > 1:
                        l1_k = get_cat_key(l1) if l1 else ''
                        if l1 and l2 and (_within_lvl1_counts[(l1_k, c_sub_key)] > 1 or c_sub_key in _multi_subkeys_in_lvl1):
                            c_disp = f"{l1} - {l2} - {c_disp}"
                        elif l1 and l1.lower() not in c_disp.lower():
                            c_disp = f"{l1} - {c_disp}"
                        elif l2 and l2.lower() not in c_disp.lower():
                            c_disp = f"{l2} - {c_disp}"

                    c_key = get_cat_key(c_disp)
                    if c_sec:
                        digits = re.findall(r'\d+', str(it['raw_label']))
                        c_dash = ' - '.join(digits) if digits else it['raw_label']
                        disp_unit = f"{c_dash} Tahun" if is_interval_label(it['raw_label']) else c_disp
                        full_disp = f"{c_sec} ({disp_unit})"
                        full_key = f"{c_sec.upper().replace(' ', '_')}_{'_'.join(digits) if digits else c_key}"
                    else:
                        full_disp = c_disp
                        full_key = c_key
                    if it['nums'] and full_key:
                        records.append({'key': full_key, 'sub_key': c_sub_key, 'display': full_disp, 'nums': it['nums'], 'coords': it.get('coords', []), 'sec': c_sec, 'code': it['code']})

                # Opsi 2 (Auto-Subtotal Induk Grup Kosong DB - Pass B): sintesis penjumlahan anak untuk induk grup kosong (>= 2 anak langsung)
                if not is_pdf:
                    _stat_leaf_keys = {'MINIMUM', 'MAKSIMUM', 'RATA_RATA', 'MEDIAN'}
                    _parent_blocks = []
                    _allowed_parent_kinds = ('leaf_blank',) if _tpl is not None else ('group_cand', 'leaf_blank')
                    for idx_i, it_p in enumerate(raw_items):
                        if it_p.get('kind') not in _allowed_parent_kinds or it_p.get('unit_only'):
                            continue
                        p_lbl = it_p.get('label', '')
                        p_key = it_p.get('sub_key') or get_cat_key(p_lbl)
                        if not p_key or p_key == 'TOTAL' or p_key.startswith('TOTAL_Y') or p_key in _stat_leaf_keys:
                            continue
                        if re.search(r'\b(median|minimum|maksimum|rata[\s\-]?rata|average)\b', str(p_lbl).lower()):
                            continue
                        if p_key in _data_sub_keys or is_interval_label(p_lbl):
                            continue
                        direct_children = []
                        for it_c in raw_items[idx_i + 1:]:
                            if it_c.get('kind') != 'data' or it_c.get('is_tot_label'):
                                break
                            direct_children.append(it_c)
                        if len(direct_children) < 2:
                            continue
                        if any(ch.get('sub_key') in _stat_leaf_keys for ch in direct_children):
                            continue
                        n_cols = len(direct_children[0]['nums'])
                        if n_cols == 0 or any(len(ch['nums']) != n_cols for ch in direct_children):
                            continue
                        _parent_blocks.append((p_lbl, p_key, it_p.get('sec', ''), direct_children))

                    _existing_keys = {rec['key'] for rec in records}
                    _has_multi_blocks = len(_parent_blocks) >= 2
                    for p_lbl, p_key, p_sec, direct_children in _parent_blocks:
                        if p_key in _existing_keys:
                            continue
                        _all_num_children = all(ch.get('has_num_prefix') for ch in direct_children) and not re.match(r'^\d+[\.\)]', str(p_lbl))
                        if not (_has_multi_blocks or _all_num_children):
                            continue
                        n_cols = len(direct_children[0]['nums'])
                        synth_nums = []
                        for c_i in range(n_cols):
                            c_sum = sum(float(ch['nums'][c_i]) for ch in direct_children if isinstance(ch['nums'][c_i], (int, float)))
                            synth_nums.append(c_sum)
                        records.append({
                            'key': p_key,
                            'sub_key': p_key,
                            'display': p_lbl,
                            'nums': synth_nums,
                            'sec': p_sec,
                            'code': None,
                            'is_synth_parent': True,
                        })
                        _existing_keys.add(p_key)
                return records

            col_years_map = {}
            if len(pdf_by_year) >= 2 and active_cat_cols:
                for c_idx, h in zip(active_cat_cols, headers):
                    m_y = _extract_header_years(str(h))
                    if m_y:
                        col_years_map[c_idx] = m_y[-1]

            common_years = [y for y in sorted(pdf_by_year.keys()) if y in set(col_years_map.values())]
            has_multi_year_cat = (len(common_years) >= 2)

            sec_b = f"Halaman {target_pages[0]}" if len(target_pages) == 1 else (f"Halaman {target_pages[0]}-{target_pages[-1]}" if len(target_pages) > 1 else "")

            if has_multi_year_cat:
                for y in common_years:
                    cols_y = [c for c in active_cat_cols if col_years_map.get(c) == y]
                    s_recs_y = extract_cat_rows_std(clean_db_rows, is_pdf=False, target_cols=cols_y)
                    p_recs_y = extract_cat_rows_std(pdf_by_year[y], is_pdf=True)
                    headers_y = [headers[active_cat_cols.index(c)] for c in cols_y]

                    used_p = set()
                    for s_rec in s_recs_y:
                        s_disp = s_rec['display']
                        s_nums = s_rec['nums']
                        match_p = next((p for idx_p, p in enumerate(p_recs_y) if idx_p not in used_p and p['key'] == s_rec['key']), None)
                        if not match_p:
                            match_p = next((
                                p for idx_p, p in enumerate(p_recs_y)
                                if idx_p not in used_p and len(p['key']) >= 6 and len(s_rec['key']) >= 6 and (
                                    p['key'].startswith(s_rec['key']) or s_rec['key'].startswith(p['key']) or
                                    p['key'].endswith(s_rec['key']) or s_rec['key'].endswith(p['key'])
                                )
                            ), None)
                        if not match_p:
                            match_p = next((p for idx_p, p in enumerate(p_recs_y) if idx_p not in used_p and SequenceMatcher(None, p['display'], s_disp).ratio() >= 0.85 and is_safe_fuzzy_match(p['display'], s_disp)), None)
                        if not match_p:
                            continue
                        used_p.add(p_recs_y.index(match_p))
                        p_nums = match_p['nums']
                        s_coords = s_rec.get('coords') or []

                        for i in range(min(len(s_nums), len(p_nums))):
                            sv = s_nums[i]
                            pv = p_nums[i]
                            c_coord = s_coords[i] if i < len(s_coords) else None
                            base_h = headers_y[i] if i < len(headers_y) else f"Metrik #{i+1}"
                            if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                base_h = f"Metrik #{i+1}"

                            if y and y not in str(base_h):
                                base_h = f"{base_h} ({y})"

                            is_match, is_tol, diff_val = evaluate_cell_match(pv, sv, tolerance=tolerance)
                            abs_diff = format_display_val(abs(diff_val)) if diff_val is not None else "-"

                            p_disp = format_display_val(pv)
                            src_disp = format_display_val(sv)

                            if is_match:
                                m_item = {"wilayah": s_disp, "section": sec_b, "metric": base_h, "val": p_disp, "pdf_val": p_disp, "src_val": src_disp}
                                if c_coord:
                                    m_item["coord"] = c_coord
                                if is_tol:
                                    m_item["is_tolerance_match"] = True
                                    m_item["tolerance_diff"] = round(diff_val, 4)
                                results["matches"].append(m_item)
                            else:
                                diff_out, note = _build_diff_entry_meta(pv, sv, p_disp, src_disp, diff_val)
                                d_item = {
                                    "wilayah": s_disp, "section": sec_b, "metric": base_h,
                                    "pdf_val": p_disp, "src_val": src_disp,
                                    "diff": diff_out, "note": note, "type": "replace"
                                }
                                if c_coord:
                                    d_item["coord"] = c_coord
                                results["diffs"].append(d_item)
            else:
                s_recs = extract_cat_rows_std(clean_db_rows, is_pdf=False)
                for idx_s, s in enumerate(s_recs):
                    s['id'] = idx_s

                _has_gender_abbrev_ctx = (
                    any(any(k in str(h).lower() for k in ('jenis kelamin', 'kelamin', 'sex', 'gender')) for h in headers)
                    or (
                        any(re.fullmatch(r'l|lk', p.strip().lower()) for h in headers for p in re.split(r'\s*(?:\|\||[-–—/])\s*', str(h)))
                        and any(re.fullmatch(r'p|pr', p.strip().lower()) for h in headers for p in re.split(r'\s*(?:\|\||[-–—/])\s*', str(h)))
                    )
                )

                def _norm_hdr(raw_h):
                    h = str(raw_h).lower().replace('-', ' ').replace('–', ' ').replace('—', ' ')
                    h = re.sub(r'(?<=\d)(?=[ds]\d)', ' ', h)
                    h = re.sub(r'\(.*\?\)', '', h)
                    h = re.sub(r'\bpresentase\b', 'persentase', h)
                    h = re.sub(r'\bprosentase\b', 'persentase', h)
                    h = re.sub(r'\bblla\b', 'biia', h)
                    h = re.sub(r'\bbiia\b', 'biia', h)
                    h = re.sub(r'\bbudha\b', 'buddha', h)
                    h = re.sub(r'\bkh?ongh?uch?u\b', 'khonghucu', h)
                    h = re.sub(r'\bolah\s+raga\b', 'olahraga', h)
                    h = re.sub(r'\biuphhk\s*(ha|ht)\b', r'iuphhk \1', h)
                    h = re.sub(r'\b([a-z]{4,})[1-9]+\b', r'\1', h)
                    tokens = set(re.findall(r'\b\w+\b', h))
                    if _has_gender_abbrev_ctx:
                        raw_segs = [seg.strip().lower() for seg in re.split(r'\s*(?:\|\||[-–—/])\s*', str(raw_h)) if seg.strip()]
                        if any(seg in ('l', 'lk') for seg in raw_segs) and '+' not in str(raw_h):
                            tokens.discard('l')
                            tokens.discard('lk')
                            tokens.add('laki')
                        elif any(seg in ('p', 'pr') for seg in raw_segs) and '+' not in str(raw_h):
                            tokens.discard('p')
                            tokens.discard('pr')
                            tokens.add('perempuan')
                    if '+' in str(raw_h) or any(k in str(raw_h).lower() for k in ['laki+perempuan', 'laki laki+ perempuan', 'pria+wanita', 'l+p', 'wni+wna', 'negeri+swasta']):
                        tokens.add('jumlah')
                        tokens.add('total')
                    return tokens, h.strip()

                is_horiz_col_split = (archetype in ["MULTI_PAGE_COLUMN_SPLIT", "MULTI_PAGE_SECTION_PER_PAGE"])

                is_stitched_horiz = False
                if target_pages and len(target_pages) > 1:
                    idx_bps, cells_bps, _ = find_bps_numbering_row(target_pdf_rows)
                    if any(re.match(r'^\(\d+\)$', str(c[1]).strip()) and int(re.search(r'\d+', str(c[1])).group(0)) >= 5 for c in cells_bps):
                        is_stitched_horiz = True

                if is_stitched_horiz:
                    is_horiz_col_split = False

                pdf_cols = []
                is_single_year_filtered = bool(target_year and target_year in pdf_by_year and len(pdf_by_year) > 1 and len(db_header_years) <= 1)
                is_row_continuation = False
                if is_single_year_filtered or is_stitched_horiz:
                    p_hdrs = detect_col_headers(target_pdf_rows)
                    for h in p_hdrs:
                        t_set, h_clean = _norm_hdr(h)
                        pdf_cols.append((h, t_set, h_clean, target_pages[0] if target_pages else None))
                elif target_pages and len(target_pages) > 1:
                    p1_info = [{'nomor_tabel': target_table, 'pages': [target_pages[0]]}]
                    p2_info = [{'nomor_tabel': target_table, 'pages': [target_pages[1]]}]
                    extract_table_content(pdf_path, p1_info)
                    extract_table_content(pdf_path, p2_info)
                    p1_h = detect_col_headers(p1_info[0].get('extracted_data', []))
                    p2_h = detect_col_headers(p2_info[0].get('extracted_data', []))
                    if p1_h and p2_h and p1_h == p2_h:
                        is_row_continuation = True
                        is_horiz_col_split = False

                    if is_row_continuation:
                        for h in p1_h:
                            t_set, h_clean = _norm_hdr(h)
                            pdf_cols.append((h, t_set, h_clean, target_pages[0]))
                    else:
                        for p_num in target_pages:
                            target_page_info = [{'nomor_tabel': target_table, 'pages': [p_num]}]
                            extract_table_content(pdf_path, target_page_info)
                            p_page_rows = target_page_info[0].get('extracted_data', [])
                            p_hdrs = detect_col_headers(p_page_rows)
                            for h in p_hdrs:
                                t_set, h_clean = _norm_hdr(h)
                                pdf_cols.append((h, t_set, h_clean, p_num))
                else:
                    p_hdrs = detect_col_headers(target_pdf_rows)
                    for h in p_hdrs:
                        t_set, h_clean = _norm_hdr(h)
                        pdf_cols.append((h, t_set, h_clean, target_pages[0] if target_pages else None))

                if is_horiz_col_split and not is_row_continuation and not is_single_year_filtered and target_pages and len(target_pages) > 1:
                    p_recs = []
                    for p_num in target_pages:
                        target_page_info = [{'nomor_tabel': target_table, 'pages': [p_num]}]
                        extract_table_content(pdf_path, target_page_info)
                        p_page_rows = target_page_info[0].get('extracted_data', [])
                        clean_p_rows = []
                        for r in p_page_rows:
                            if not isinstance(r, list): continue
                            txt = ' '.join(str(c) for c in r if str(c).strip()).lower()
                            if any(k in txt for k in ['bab / chapter', 'chapter \nbab', 'bab\nchapter']) or re.search(r'^\s*bab\s+\d+\b', txt) or (re.search(r'\bbab\b', txt) and re.search(r'\bchapter\b', txt) and len(r) <= 3):
                                break
                            if any(txt.startswith(k) for k in ['tabel / table', 'tabel\n', 'table\n']) and target_table not in txt:
                                m_next = re.search(r'tabel\s+(\d+\.\d+\.\d+)', txt)
                                if m_next and m_next.group(1).strip('.') != target_table.strip('.'):
                                    break
                            clean_p_rows.append(r)
                        for rec in extract_cat_rows_std(clean_p_rows, is_pdf=True):
                            rec['page'] = p_num
                            p_recs.append(rec)
                else:
                    p_recs = extract_cat_rows_std(target_pdf_rows, is_pdf=True)
                p_grouped_items = {s['id']: [] for s in s_recs}
                used_p_indices = set()
                used_s_ids = set()

                # Pass 0: Exact code matching (e.g. kode_rekening / nomor baris hierarki)
                for idx_p, p in enumerate(p_recs):
                    p_code = p.get('code')
                    if p_code:
                        is_simple_digit = p_code.isdigit() and len(p_code) <= 2
                        candidates = [s for s in s_recs if s.get('code') == p_code and (is_horiz_col_split or s['id'] not in used_s_ids)]
                        match_s = next((s for s in candidates if s.get('sec') == p.get('sec')), None)
                        if not match_s and candidates:
                            match_s = candidates[0]
                        if match_s:
                            if is_simple_digit:
                                lbl_sim = SequenceMatcher(None, p['display'].lower(), match_s['display'].lower()).ratio()
                                if lbl_sim < 0.65 and p['key'] != match_s['key']:
                                    match_s = None
                            if match_s:
                                p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                                used_p_indices.add(idx_p)
                                if not is_horiz_col_split:
                                    used_s_ids.add(match_s['id'])

                # Pass 1: Strict key matching (with section priority)
                for idx_p, p in enumerate(p_recs):
                    if idx_p in used_p_indices:
                        continue
                    candidates = [
                        s for s in s_recs
                        if (s['key'] == p['key'] or (p.get('sub_key') and s['key'] == p['sub_key'])) and (is_horiz_col_split or s['id'] not in used_s_ids)
                    ]
                    match_s = next((s for s in candidates if s.get('sec') == p.get('sec')), None)
                    if not match_s and candidates:
                        match_s = candidates[0]
                    if match_s:
                        p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                        used_p_indices.add(idx_p)
                        if not is_horiz_col_split:
                            used_s_ids.add(match_s['id'])

                # Pass 1.5: Prefix / suffix containment key matching (e.g. Roman numeral prefix or bilingual suffix diff)
                def is_key_compatible(k1, k2):
                    y1 = re.search(r'Y(19|20\d{2})_', k1)
                    y2 = re.search(r'Y(19|20\d{2})_', k2)
                    if y1 or y2:
                        return bool(y1 and y2 and y1.group(1) == y2.group(1))
                    return True

                for idx_p, p in enumerate(p_recs):
                    if idx_p in used_p_indices:
                        continue
                    candidates = [
                        s for s in s_recs
                        if (is_horiz_col_split or s['id'] not in used_s_ids) and
                        len(p['key']) >= 4 and len(s['key']) >= 4 and is_key_compatible(p['key'], s['key']) and (
                            p['key'].startswith(s['key']) or s['key'].startswith(p['key']) or
                            p['key'].endswith(s['key']) or s['key'].endswith(p['key']) or
                            p['key'].split('_')[0] == s['key'] or s['key'].split('_')[0] == p['key']
                        )
                    ]
                    match_s = next((s for s in candidates if s.get('sec') == p.get('sec')), None)
                    if not match_s and candidates:
                        match_s = candidates[0]
                    if match_s:
                        p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                        used_p_indices.add(idx_p)
                        if not is_horiz_col_split:
                            used_s_ids.add(match_s['id'])

                # Pass 2: Safe fuzzy matching for unmatched PDF rows
                for idx_p, p in enumerate(p_recs):
                    if idx_p in used_p_indices:
                        continue
                    candidates = [
                        s for s in s_recs
                        if (is_horiz_col_split or s['id'] not in used_s_ids) and
                        SequenceMatcher(None, p['display'], s['display']).ratio() >= 0.85 and
                        is_safe_fuzzy_match(p['display'], s['display'])
                    ]
                    match_s = next((s for s in candidates if s.get('sec') == p.get('sec')), None)
                    if not match_s and candidates:
                        match_s = candidates[0]
                    if match_s:
                        p_grouped_items[match_s['id']].append((idx_p, p['nums']))
                        used_p_indices.add(idx_p)
                        if not is_horiz_col_split:
                            used_s_ids.add(match_s['id'])

                # Page-Aware Matrix Mapping for multi-page horizontal column split tables
                p_grouped = {}
                page_col_indices = {}
                if is_horiz_col_split and not is_row_continuation and not is_single_year_filtered and pdf_cols:
                    for p_idx, col_tuple in enumerate(pdf_cols):
                        p_num = col_tuple[3] if len(col_tuple) >= 4 else None
                        if p_num is not None:
                            page_col_indices.setdefault(p_num, []).append(p_idx)

                for s_id, items in p_grouped_items.items():
                    if page_col_indices:
                        if not items:
                            p_grouped[s_id] = []
                            continue
                        nums_full = [0.0] * len(pdf_cols)
                        for idx_p, n_list in items:
                            p_page = p_recs[idx_p].get('page') if idx_p < len(p_recs) else None
                            target_indices = page_col_indices.get(p_page, [])
                            for local_idx, val in enumerate(n_list):
                                if local_idx < len(target_indices):
                                    nums_full[target_indices[local_idx]] = val
                        p_grouped[s_id] = nums_full
                    else:
                        items.sort(key=lambda x: x[0])
                        nums_flat = []
                        for _, n_list in items:
                            nums_flat.extend(n_list)
                        p_grouped[s_id] = nums_flat

                col_map = {}

                _EXCLUSIVE_LEAF_PAIRS = (
                    ({'l', 'laki', 'pria', 'male', 'putra'}, {'p', 'perempuan', 'wanita', 'female', 'putri'}),
                    ({'perkotaan', 'urban'}, {'perdesaan', 'pedesaan', 'rural'}),
                    ({'negeri'}, {'swasta'}),
                    ({'wna'}, {'wni'}),
                    ({'dewasa'}, {'anak'}),
                )
                _EXCLUSIVE_PATH_PAIRS = (
                    ({'laki', 'pria', 'male', 'putra'}, {'perempuan', 'wanita', 'female', 'putri'}),
                    ({'perkotaan', 'urban'}, {'perdesaan', 'pedesaan', 'rural'}),
                    ({'negeri'}, {'swasta'}),
                    ({'wna'}, {'wni'}),
                    ({'dewasa'}, {'anak'}),
                )

                def _has_exclusive_hdr_conflict(s_all, s_leaf, p_all, p_leaf):
                    # Jangan tolak kolom gabungan (L+P / Jumlah / Total) terhadap pasangan tunggal
                    if ({'jumlah', 'total'} & s_leaf) and ({'jumlah', 'total'} & p_leaf):
                        return False
                    for grp_a, grp_b in _EXCLUSIVE_LEAF_PAIRS:
                        s_a, s_b = bool(s_leaf & grp_a), bool(s_leaf & grp_b)
                        p_a, p_b = bool(p_leaf & grp_a), bool(p_leaf & grp_b)
                        if ((s_a and not s_b) and (p_b and not p_a)) or ((s_b and not s_a) and (p_a and not p_b)):
                            return True
                    for grp_a, grp_b in _EXCLUSIVE_PATH_PAIRS:
                        if ({'jumlah', 'total'} & s_all) or ({'jumlah', 'total'} & p_all):
                            continue
                        s_a, s_b = bool(s_all & grp_a), bool(s_all & grp_b)
                        p_a, p_b = bool(p_all & grp_a), bool(p_all & grp_b)
                        if ((s_a and not s_b) and (p_b and not p_a)) or ((s_b and not s_a) and (p_a and not p_b)):
                            return True
                    return False

                # Prioritaskan kolom DB yang terisi data (bukan all-None) saat header PDF tidak mencantumkan tahun eksplisit,
                # agar kolom tahun kosong (mis. 2025 di 7.1.4/7.1.5) tidak mencuri pemetaan kolom PDF milik tahun terisi (2022).
                _bps_db_idx, _, _ = find_bps_numbering_row(clean_db_rows)
                _data_db_slice = clean_db_rows[_bps_db_idx + 1:] if _bps_db_idx >= 0 else clean_db_rows
                if active_cat_cols:
                    s_filled_cols = {
                        pos for pos, col_i in enumerate(active_cat_cols)
                        if any(
                            isinstance(r, list) and not is_header_or_metadata_row(r) and not is_title_row(r)
                            and col_i < len(r) and r[col_i] is not None
                            and str(r[col_i]).strip().lower() not in ('', 'none', 'null', 'nan')
                            and (parse_num(str(r[col_i]).strip(), is_pdf=False) is not None or str(r[col_i]).strip() in ('-', '–', '—', '−', '...', '…'))
                            for r in _data_db_slice
                        )
                    }
                else:
                    s_filled_cols = set(range(len(headers)))

                used_p = set()
                _pair_candidates = []
                for s_idx, sh in enumerate(headers):
                    s_tokens, sh_clean = _norm_hdr(sh)
                    s_no_yr = {t for t in s_tokens if not re.match(r'^(?:19|20)\d{2}$', t)}
                    sh_no_yr = re.sub(r'\b(?:19|20)\d{2}(?:\s*/\s*(?:19|20)\d{2})?\b', '', sh_clean)
                    sh_no_yr = re.sub(r'\(\s*\)', '', sh_no_yr).strip()
                    s_leaf_str = str(sh).split(' - ')[-1]
                    s_leaf_toks = {t for t in _norm_hdr(s_leaf_str)[0] if not re.match(r'^(?:19|20)\d{2}$', t)}
                    s_yrs = set(_extract_header_years(str(sh)))
                    for p_idx, p_tuple in enumerate(pdf_cols):
                        ph, p_tokens, ph_clean = p_tuple[0], p_tuple[1], p_tuple[2]
                        p_yrs = set(_extract_header_years(str(ph)))
                        if s_yrs and p_yrs and s_yrs != p_yrs:
                            continue
                        p_no_yr = {t for t in p_tokens if not re.match(r'^(?:19|20)\d{2}$', t)}
                        ph_no_yr = re.sub(r'\b(?:19|20)\d{2}(?:\s*/\s*(?:19|20)\d{2})?\b', '', ph_clean)
                        ph_no_yr = re.sub(r'\(\s*\)', '', ph_no_yr).strip()
                        p_leaf_str = str(ph).split(' - ')[-1]
                        p_leaf_toks = {t for t in _norm_hdr(p_leaf_str)[0] if not re.match(r'^(?:19|20)\d{2}$', t)}

                        if _has_exclusive_hdr_conflict(s_no_yr, s_leaf_toks, p_no_yr, p_leaf_toks):
                            continue

                        overlap = len(s_no_yr & p_no_yr)
                        sim = SequenceMatcher(None, sh_no_yr, ph_no_yr).ratio()
                        s_has_tot = bool(s_no_yr & {'jumlah', 'total'})
                        p_has_tot = bool(p_no_yr & {'jumlah', 'total'})
                        subtotal_asym_pen = 12.0 if (s_has_tot != p_has_tot) else 0.0
                        if (s_no_yr and s_no_yr == p_no_yr) or (sh_no_yr and sh_no_yr == ph_no_yr):
                            sim_score = 100.0
                        elif (
                            p_leaf_toks
                            and (p_leaf_toks <= {'jumlah', 'total'})
                            and (p_no_yr - {'jumlah', 'total'})
                            and p_no_yr.issubset(s_no_yr)
                        ):
                            sim_score = min(98.0, 50.0 + float(overlap) * 10.0 - len(s_no_yr - p_no_yr) * 0.5)
                        elif p_leaf_toks and p_leaf_toks.issubset(s_no_yr) and not (p_leaf_toks <= {'jumlah', 'total'}):
                            extra_pen = len(s_no_yr - p_no_yr) * 0.5
                            ancestor_bonus = min(35.0, float(overlap) * 5.0)
                            exact_leaf_bonus = 10.0 if (s_leaf_toks and s_leaf_toks == p_leaf_toks) else 0.0
                            sim_score = min(98.0, 45.0 + (len(p_leaf_toks) * 10.0) + ancestor_bonus + exact_leaf_bonus - extra_pen - subtotal_asym_pen)
                        elif p_no_yr and p_no_yr <= {'jumlah', 'total'} and ('jumlah' in s_no_yr or 'total' in s_no_yr):
                            sim_score = max(15.0, 40.0 - 4.0 * len(s_no_yr - p_no_yr))
                        elif (overlap > 0 and (s_no_yr.issubset(p_no_yr) or p_no_yr.issubset(s_no_yr))) or sim >= 0.8:
                            sim_score = max(sim * 25.0, float(overlap) * 12.0) - 0.5 * len(s_no_yr ^ p_no_yr)
                        elif s_yrs and p_yrs and s_yrs == p_yrs and not s_no_yr:
                            sim_score = 20.0
                        else:
                            continue

                        year_bonus = 0.0
                        if s_yrs and p_yrs and s_yrs == p_yrs:
                            year_bonus = 100.0
                        elif not s_yrs and target_year and p_yrs:
                            year_bonus = 100.0 if str(target_year) in p_yrs else -100.0
                        elif not p_yrs and s_idx in s_filled_cols:
                            year_bonus = 25.0

                        tot_score = sim_score + year_bonus
                        if tot_score > 0:
                            _pair_candidates.append((tot_score, -abs(s_idx - p_idx), s_idx, p_idx))

                _pair_candidates.sort(reverse=True)
                for _sc, _pos_tie, s_idx, p_idx in _pair_candidates:
                    if s_idx not in col_map and p_idx not in used_p:
                        col_map[s_idx] = p_idx
                        used_p.add(p_idx)

                # Fallback tahun eksplisit jika masih ada sumber bertahun yang belum terpetakan
                for s_idx, sh in enumerate(headers):
                    if s_idx in col_map:
                        continue
                    s_yrs = set(_extract_header_years(str(sh)))
                    if s_yrs:
                        for p_idx, p_tuple in enumerate(pdf_cols):
                            if p_idx in used_p:
                                continue
                            p_yrs = set(_extract_header_years(str(p_tuple[0])))
                            if s_yrs == p_yrs:
                                col_map[s_idx] = p_idx
                                used_p.add(p_idx)
                                break

                p_headers = detect_col_headers(target_pdf_rows)

                # Deteksi kolom PDF yang konsisten memakai pemisah ribuan koma Inggris (anchored ke nilai DB)
                _cat_scale_pairs = []
                for s_rec in s_recs:
                    p_nums = p_grouped.get(s_rec['id'], [])
                    if not p_nums or s_rec.get('is_synth_parent'):
                        continue
                    for i, sv in enumerate(s_rec['nums']):
                        p_col_idx = col_map.get(i, i) if col_map else i
                        if p_col_idx < len(p_nums):
                            _cat_scale_pairs.append((p_col_idx, p_nums[p_col_idx], sv))
                _en_thou_cols = _detect_en_thousands_pdf_cols(_cat_scale_pairs)

                for s_rec in s_recs:
                    s_disp = s_rec['display']
                    s_nums = s_rec['nums']
                    s_coords = s_rec.get('coords') or []
                    is_synth_parent = bool(s_rec.get('is_synth_parent'))
                    p_nums = p_grouped.get(s_rec['id'], [])
                    if not p_nums:
                        continue

                    matched_p_cols = set()
                    for i in range(len(s_nums)):
                        if col_map:
                            if i not in col_map:
                                continue
                            p_col_idx = col_map[i]
                        else:
                            p_col_idx = i
                        if p_col_idx >= len(p_nums):
                            continue
                        matched_p_cols.add(p_col_idx)
                        sv = s_nums[i]
                        pv = p_nums[p_col_idx]
                        c_coord = s_coords[i] if (not is_synth_parent and i < len(s_coords)) else None
                        base_h = headers[i] if i < len(headers) else f"Metrik #{i+1}"
                        if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                            base_h = f"Metrik #{i+1}"
                        base_h = str(base_h).replace('||', ' - ')
                        if is_synth_parent and _is_non_additive_metric_header(base_h):
                            continue

                        p_sec = sec_b
                        if col_map and target_pages and len(target_pages) > 1 and pdf_cols and not is_row_continuation:
                            if p_col_idx < len(pdf_cols) and len(pdf_cols[p_col_idx]) >= 4 and pdf_cols[p_col_idx][3]:
                                p_sec = f"Halaman {pdf_cols[p_col_idx][3]}"
                            else:
                                cols_per_page = max(1, len(pdf_cols) // len(target_pages))
                                page_idx = min(len(target_pages) - 1, p_col_idx // cols_per_page)
                                p_sec = f"Halaman {target_pages[page_idx]}"

                        pv_resolved = _resolve_pdf_val_with_scale(pv, sv=sv, is_en_thousands_col=(p_col_idx in _en_thou_cols))
                        is_match, is_tol, diff_val = evaluate_cell_match(pv_resolved, sv, tolerance=tolerance)
                        abs_diff = format_display_val(abs(diff_val)) if diff_val is not None else "-"

                        p_disp = format_display_val(pv_resolved)
                        src_disp = format_display_val(sv)

                        if target_year and hist_pdf_years and target_year not in str(base_h):
                            base_h = f"{base_h} ({target_year})"

                        pdf_h_cat = p_headers[p_col_idx] if (p_headers and p_col_idx < len(p_headers)) else ""
                        src_h_cat = headers[i] if i < len(headers) else ""

                        if is_match:
                            m_item = {
                                "wilayah": s_disp, "section": p_sec, "metric": base_h,
                                "pdf_header": pdf_h_cat, "src_header": src_h_cat,
                                "val": p_disp, "pdf_val": p_disp, "src_val": src_disp
                            }
                            if c_coord:
                                m_item["coord"] = c_coord
                            if is_synth_parent:
                                m_item["is_group_subtotal_match"] = True
                            if is_tol:
                                m_item["is_tolerance_match"] = True
                                m_item["tolerance_diff"] = round(diff_val, 4)
                            results["matches"].append(m_item)
                        else:
                            if is_synth_parent:
                                note = f"Subtotal anak di database ({src_disp}) berbeda dengan induk di PDF ({p_disp})"
                                diff_out = diff_val
                            else:
                                diff_out, note = _build_diff_entry_meta(pv_resolved, sv, p_disp, src_disp, diff_val)
                            d_item = {
                                "wilayah": s_disp, "section": p_sec, "metric": base_h,
                                "pdf_header": pdf_h_cat, "src_header": src_h_cat,
                                "pdf_val": p_disp, "src_val": src_disp,
                                "diff": diff_out, "note": note, "type": "replace"
                            }
                            if c_coord:
                                d_item["coord"] = c_coord
                            results["diffs"].append(d_item)

                    # Kolom-kolom di PDF yang tidak ada di sumber database (misal multi-tahun horizontal) dialirkan ke pdf_only
                    unmatched_p_cols = [p_idx for p_idx in range(len(p_nums)) if p_idx not in matched_p_cols]
                    if unmatched_p_cols:
                        for p_idx in unmatched_p_cols:
                            pv = _resolve_pdf_val_with_scale(p_nums[p_idx], is_en_thousands_col=(p_idx in _en_thou_cols))
                            if pv is None:
                                continue
                            base_h = p_headers[p_idx] if (p_headers and p_idx < len(p_headers)) else f"Kolom Ekstra #{p_idx+1}"
                            base_h = str(base_h).replace('||', ' - ')
                            if target_year and hist_pdf_years and target_year not in str(base_h):
                                base_h = f"{base_h} ({target_year})"
                            pv_disp = format_display_val(pv)
                            m_yr = re.search(r'\b(20\d{2})\b', str(base_h))

                            p_sec_extra = sec_b
                            if target_pages and len(target_pages) > 1 and pdf_cols and not is_row_continuation:
                                if p_idx < len(pdf_cols) and len(pdf_cols[p_idx]) >= 4 and pdf_cols[p_idx][3]:
                                    p_sec_extra = f"Halaman {pdf_cols[p_idx][3]}"

                            # Layer 2 Fallback: Jika kolom ekstra PDF adalah 'Grup - Jumlah/Total', bandingkan dengan penjumlahan sub-kolom grup di DB
                            if not m_yr and not _is_non_additive_metric_header(base_h):
                                h_parts = [pt.strip() for pt in base_h.split(' - ') if pt.strip()]
                                grp_s_idxs = []
                                if len(h_parts) >= 2 and h_parts[-1].lower() in ('jumlah', 'total', 'subjumlah'):
                                    grp_pfx = ' - '.join(h_parts[:-1]).lower()
                                    for si in range(len(s_nums)):
                                        mp_i = col_map.get(si, si) if col_map else si
                                        cand_h = str(p_headers[mp_i] if (p_headers and mp_i < len(p_headers)) else (headers[si] if si < len(headers) else '')).replace('||', ' - ').lower().strip()
                                        if cand_h.startswith(grp_pfx + ' - ') and not re.search(r'\b(jumlah|total|subjumlah)\b', cand_h.split(' - ')[-1]) and not _is_non_additive_metric_header(cand_h):
                                            grp_s_idxs.append(si)
                                if len(grp_s_idxs) >= 2:
                                    sv_grp = sum(s_nums[si] for si in grp_s_idxs if isinstance(s_nums[si], (int, float)))
                                    pv_res_g = _resolve_pdf_val_with_scale(p_nums[p_idx], sv=sv_grp, is_en_thousands_col=(p_idx in _en_thou_cols))
                                    is_m_g, is_tol_g, diff_g = evaluate_cell_match(pv_res_g, sv_grp, tolerance=tolerance)
                                    p_d_g = format_display_val(pv_res_g)
                                    s_d_g = format_display_val(sv_grp)
                                    if is_m_g:
                                        m_it_g = {
                                            "wilayah": s_disp, "section": p_sec_extra, "metric": base_h,
                                            "pdf_header": base_h, "src_header": base_h,
                                            "val": p_d_g, "pdf_val": p_d_g, "src_val": s_d_g,
                                            "is_group_subtotal_match": True
                                        }
                                        if is_tol_g:
                                            m_it_g["is_tolerance_match"] = True
                                            m_it_g["tolerance_diff"] = round(diff_g, 4)
                                        results["matches"].append(m_it_g)
                                    else:
                                        d_out_g, note_g = _build_diff_entry_meta(pv_res_g, sv_grp, p_d_g, s_d_g, diff_g)
                                        results["diffs"].append({
                                            "wilayah": s_disp, "section": p_sec_extra, "metric": base_h,
                                            "pdf_header": base_h, "src_header": base_h,
                                            "pdf_val": p_d_g, "src_val": s_d_g,
                                            "diff": d_out_g, "note": note_g, "type": "replace"
                                        })
                                    continue

                            if m_yr:
                                note = f"Tercetak di buku PDF untuk Tahun {m_yr.group(1)}, tidak tersedia di database resmi"
                            else:
                                note = f"Tercetak di buku PDF untuk {base_h}, tidak tersedia di database resmi"

                            results["pdf_only"].append({
                                "wilayah": s_disp, "section": p_sec_extra, "metric": base_h,
                                "pdf_header": base_h, "src_header": None,
                                "values": [pv_disp], "note": note, "type": "delete",
                                "is_pdf_extra": True
                            })

                for idx_p, p in enumerate(p_recs):
                    if idx_p not in used_p_indices:
                        p_disp = p['display']
                        p_sec = p.get('sec') or sec_b
                        for i, pv_raw in enumerate(p['nums']):
                            pv = _resolve_pdf_val_with_scale(pv_raw, is_en_thousands_col=(i in _en_thou_cols))
                            base_h = p_headers[i] if (p_headers and i < len(p_headers)) else (headers[i] if i < len(headers) else f"Metrik #{i+1}")
                            if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                base_h = f"Metrik #{i+1}"
                            base_h = str(base_h).replace('||', ' - ')
                            if target_year and hist_pdf_years and target_year not in str(base_h):
                                base_h = f"{base_h} ({target_year})"
                            pv_disp = format_display_val(pv)
                            results["pdf_only"].append({
                                "wilayah": p_disp, "section": p_sec, "metric": base_h,
                                "pdf_header": base_h, "src_header": None,
                                "values": [pv_disp],
                                "note": "Tercetak di buku PDF, tetapi tidak ditemukan di database resmi",
                                "type": "delete"
                            })

            for hy, h_rows in hist_pdf_years.items():
                h_recs = extract_cat_rows_std(h_rows, is_pdf=True)
                for h_rec in h_recs:
                    match_s = next((s for s in s_recs if s['key'] == h_rec['key']), None)
                    disp = match_s['display'] if match_s else h_rec['display']
                    for i, pv in enumerate(h_rec['nums']):
                        base_h = headers[i] if i < len(headers) else f"Metrik #{i+1}"
                        if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                            base_h = f"Metrik #{i+1}"
                        p_disp = format_display_val(pv)
                        results["pdf_only"].append({
                            "wilayah": disp, "section": sec_b, "metric": f"{base_h} ({hy})",
                            "pdf_header": f"{base_h} ({hy})", "src_header": None,
                            "values": [p_disp],
                            "note": f"Data historis ({hy}) tercetak di buku PDF, tidak dimuat di database {target_year or ''}".strip(),
                            "type": "delete",
                            "is_pdf_extra": True
                        })

        else:
            clean_source_rows = source_data
            src_title = ' '.join(str(c) for c in (clean_source_rows[0] if clean_source_rows else []))
            title_years = _extract_header_years(src_title)
            if not title_years and pdf_index_title:
                title_years = _extract_header_years(str(pdf_index_title))
            header_years = _extract_header_years(' '.join(headers))
            if pub_year:
                max_dy = pub_year - 1
                elig_ty = [y for y in title_years if int(y) <= max_dy]
                if elig_ty:
                    title_years = elig_ty
                elig_hy = [y for y in header_years if int(y) <= max_dy]
                if elig_hy:
                    header_years = elig_hy
            target_year = header_years[-1] if header_years else (title_years[-1] if title_years else (api_year if 'api_year' in locals() and api_year else None))
            if not target_year and cell_coords:
                c_yrs = [str(c.get('t')) for c in cell_coords.values() if isinstance(c, dict) and c.get('t')]
                if pub_year and c_yrs:
                    elig_c = [y for y in c_yrs if y.isdigit() and int(y) <= (pub_year - 1)]
                    if elig_c:
                        c_yrs = elig_c
                if c_yrs:
                    target_year = max(c_yrs)
            if target_year:
                results["target_year"] = str(target_year)

            hist_pdf_years_reg = {}

            def _is_org_or_title_not_prov_total(txt):
                low = re.sub(r'\s+', ' ', str(txt or '').lower()).strip()
                return bool(re.search(
                    r'\b(gubernur|wakil\s+gubernur|sekretariat|dinas|badan|dprd|rsud|rsjd|upt|biro|inspektorat|satpol|kantor|kejaksaan|polda|pengadilan|bappeda|bkd|bank|korem|kodam|lanal|lanud|polrestabes|universitas)\b',
                    low
                ))

            def _is_standalone_jateng_total(txt):
                low = re.sub(r'^\s*\d+\.?\s*', '', str(txt or '').lower())
                low = re.sub(r'\s+', ' ', low).strip()
                if not low or _is_org_or_title_not_prov_total(low):
                    return False
                if low in ('jawa tengah', 'total jawa tengah', 'jumlah jawa tengah', 'provinsi jawa tengah', 'prov. jawa tengah', 'jateng'):
                    return True
                return ('jawa tengah' in low and any(k in low for k in ('total', 'jumlah', 'provinsi', 'central java')))

            src_coord_dict = {}
            expanded_pdf_years_reg = []
            expanded_pdf_base_len_reg = None

            def _detect_year_subrows(data_list, is_pdf=False):
                has_year_subrows = False
                year_col_idx = -1
                if data_list:
                    subrow_pairs = 0
                    cur_chk_w = None
                    cur_chk_y = None
                    subrow_regions = set()
                    for r in data_list[:35]:
                        if not isinstance(r, list) or len(r) < 3 or is_header_or_metadata_row(r):
                            continue
                        r_y = None
                        r_y_idx = -1
                        for idx_c, c in enumerate(r[:5]):
                            c_clean = re.sub(r'^[a-zA-Z\s\.\/:\n\r]+', '', str(c)).strip()
                            if re.match(r'^(?:19|20)\d{2}$', c_clean):
                                r_y = c_clean
                                r_y_idx = idx_c
                                break

                        lbl_parts = [str(c).strip() for c in r[:r_y_idx] if str(c).strip() and str(c).strip().lower() != 'none' and parse_num(str(c).strip(), is_pdf=is_pdf) is None] if r_y_idx >= 0 else []
                        lbl_txt = ' '.join(lbl_parts)
                        found_w = find_wilayah_with_type(lbl_txt) if lbl_txt else "Umum"

                        if found_w != 'Umum':
                            cur_chk_w = found_w
                            cur_chk_y = r_y
                            if year_col_idx == -1 and r_y_idx >= 0:
                                year_col_idx = r_y_idx
                        elif not lbl_txt and cur_chk_w and r_y and r_y != cur_chk_y:
                            subrow_pairs += 1
                            subrow_regions.add(cur_chk_w)
                            if year_col_idx == -1 and r_y_idx >= 0:
                                year_col_idx = r_y_idx

                    if subrow_pairs >= 2 and len(subrow_regions) >= 2 and year_col_idx >= 0:
                        has_year_subrows = True
                return has_year_subrows, year_col_idx

            def get_clean_rows_dict(data_list, is_pdf=False, expected_len=None, target_year=None, expand_years_for_db=None):
                out = {}
                is_k = False
                brebes_seen = False
                is_prov = False
                prov_jateng_seen = False
                common_first_data_col = None
                active_cols = detect_active_data_cols(data_list) if not is_pdf else []

                has_year_subrows, year_col_idx = _detect_year_subrows(data_list, is_pdf=is_pdf)

                if has_year_subrows:
                    cur_w = None
                    by_w_year = {}
                    by_w_year_coords = {}
                    sub_cilacap_seen = False
                    for r_idx, r in enumerate(data_list):
                        if not isinstance(r, list) or len(r) < 3:
                            continue
                        txt = ' '.join(str(c) for c in r).lower()
                        if 'kota/municipality' in txt:
                            is_k = True
                            brebes_seen = True
                        elif 'kabupaten/regency' in txt:
                            is_k = False
                            brebes_seen = False
                            sub_cilacap_seen = False
                        elif 'provinsi/province' in txt or (txt.strip().startswith('provinsi') and not any(parse_num(c, is_pdf=is_pdf) for c in r if str(c).strip())):
                            is_prov = True
                            continue
                        if is_header_or_metadata_row(r):
                            continue

                        c0 = str(r[0]).strip().rstrip('.')
                        if c0.isdigit():
                            num0 = int(c0)
                            if num0 > 6:
                                is_k = False
                            elif brebes_seen and 1 <= num0 <= 6:
                                is_k = True

                        row_year = None
                        row_y_col = year_col_idx
                        for idx_c, c in enumerate(r[:5]):
                            m_y = re.search(r'\b((?:19|20)\d{2})\b', str(c))
                            if m_y:
                                row_year = m_y.group(1)
                                row_y_col = idx_c
                                break

                        label_parts = [str(c).strip() for c in r[:row_y_col] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None]
                        row_label = ' '.join(label_parts) if label_parts else ''

                        if is_prov and not _is_org_or_title_not_prov_total(row_label) and (c0 == '1' or 'jawa tengah' in row_label.lower() or 'provinsi' in row_label.lower()):
                            cur_w = 'Provinsi Jawa Tengah'
                            prov_jateng_seen = True
                            is_prov = False
                        elif not _is_org_or_title_not_prov_total(row_label) and ('provinsi jawa tengah' in row_label.lower() or 'pemprov' in row_label.lower()):
                            cur_w = 'Provinsi Jawa Tengah'
                            prov_jateng_seen = True
                            is_prov = False
                        elif any(k in row_label.lower() for k in ['jumlah', 'total']) and not any(k in row_label.lower() for k in ['curah', 'hari', 'hujan', 'penduduk']):
                            cur_w = 'Total Jawa Tengah'
                            is_prov = False
                        else:
                            prov_code, prov_name = resolve_provinsi_entity(row_label) if not _is_org_or_title_not_prov_total(row_label) else (None, None)
                            if prov_name:
                                if prov_name == 'Jawa Tengah' and prov_jateng_seen:
                                    cur_w = 'Total Jawa Tengah'
                                else:
                                    cur_w = prov_name
                                    if prov_name == 'Jawa Tengah' and is_prov:
                                        cur_w = 'Provinsi Jawa Tengah'
                                        prov_jateng_seen = True
                                is_prov = False
                            elif row_label:
                                found_w = find_wilayah_with_type(row_label, is_kota=is_k)
                                if found_w != "Umum":
                                    cur_w = found_w
                            elif any(_is_standalone_jateng_total(c) for c in r[:row_y_col+1]):
                                cur_w = "Total Jawa Tengah"

                        if cur_w == 'Kab. Cilacap':
                            sub_cilacap_seen = True
                        if cur_w and (('Brebes' in cur_w and (is_pdf or sub_cilacap_seen)) or cur_w == 'Kota Tegal'):
                            brebes_seen = True

                        if cur_w and row_year:
                            nums = []
                            r_coords = []
                            for ci, c in enumerate(r[row_y_col+1:], start=row_y_col+1):
                                v = parse_num(c, is_pdf=is_pdf)
                                if v is not None:
                                    nums.append(v)
                                    if not is_pdf:
                                        r_coords.append(_get_tpl_coord(r_idx, ci))
                            if cur_w not in by_w_year:
                                by_w_year[cur_w] = {}
                                by_w_year_coords[cur_w] = {}
                            by_w_year[cur_w][row_year] = nums
                            if not is_pdf:
                                by_w_year_coords[cur_w][row_year] = r_coords

                    all_years = sorted({y for y_dict in by_w_year.values() for y in y_dict})
                    matched_exp_years = [y for y in all_years if expand_years_for_db and y in expand_years_for_db]
                    if is_pdf and len(matched_exp_years) >= 2:
                        row_lens = [len(vals) for y_dict in by_w_year.values() for vals in y_dict.values() if vals]
                        base_yr_len = max(row_lens) if row_lens else 0
                        if base_yr_len > 0:
                            expanded_pdf_years_reg[:] = matched_exp_years
                            nonlocal_base_holder = base_yr_len
                            for w_entity, y_dict in by_w_year.items():
                                flat_vals = []
                                for my in matched_exp_years:
                                    y_vals = list(y_dict.get(my) or [])
                                    if len(y_vals) < base_yr_len:
                                        y_vals.extend([None] * (base_yr_len - len(y_vals)))
                                    flat_vals.extend(y_vals[:base_yr_len])
                                out[w_entity] = flat_vals
                                for y_hist, h_vals in y_dict.items():
                                    if y_hist not in matched_exp_years:
                                        hist_pdf_years_reg.setdefault(y_hist, {})[w_entity] = h_vals
                            out["_expanded_base_p_len_"] = nonlocal_base_holder
                            return out

                    active_y = target_year if (target_year and any(target_year in y_dict for y_dict in by_w_year.values())) else None
                    if not active_y and len(all_years) >= 2:
                        if is_pdf and target_year:
                            for w_entity, y_dict in by_w_year.items():
                                for y_hist, nums in y_dict.items():
                                    hist_pdf_years_reg.setdefault(y_hist, {})[w_entity] = nums
                            return out
                        for w_entity, y_dict in by_w_year.items():
                            for y_val, nums in y_dict.items():
                                out[f"{w_entity} ({y_val})"] = nums
                                if not is_pdf:
                                    src_coord_dict[f"{w_entity} ({y_val})"] = by_w_year_coords.get(w_entity, {}).get(y_val, [])
                        return out
                    elif active_y:
                        for w_entity, y_dict in by_w_year.items():
                            out[w_entity] = y_dict.get(active_y, [])
                            if not is_pdf:
                                src_coord_dict[w_entity] = by_w_year_coords.get(w_entity, {}).get(active_y, [])
                            if is_pdf:
                                for y_hist, nums in y_dict.items():
                                    if y_hist != active_y:
                                        if y_hist not in hist_pdf_years_reg:
                                            hist_pdf_years_reg[y_hist] = {}
                                        hist_pdf_years_reg[y_hist][w_entity] = nums
                        return out
                    else:
                        active_y = all_years[-1] if all_years else None
                        for w_entity, y_dict in by_w_year.items():
                            out[w_entity] = y_dict.get(active_y, [])
                            if not is_pdf:
                                src_coord_dict[w_entity] = by_w_year_coords.get(w_entity, {}).get(active_y, [])
                        return out

                cilacap_seen_flat = False
                pdf_row_slots = []
                for r_idx, r in enumerate(data_list):
                    if not isinstance(r, list):
                        continue
                    txt = ' '.join(str(c) for c in r).lower()
                    if is_pdf and any(k in txt for k in ['tabel', 'table']):
                        m_next = re.search(r'(?:tabel|table)\s+(\d+\.\d+(?:\.\d+)?)', txt)
                        if m_next and clean_num(m_next.group(1)) != clean_num(target_table):
                            break
                    if 'kota/municipality' in txt:
                        is_k = True
                        brebes_seen = True
                    elif 'kabupaten/regency' in txt:
                        is_k = False
                        brebes_seen = False
                        prov_jateng_seen = False
                        cilacap_seen_flat = False
                    elif 'provinsi/province' in txt or (txt.strip().startswith('provinsi') and not any(parse_num(c, is_pdf=is_pdf) for c in r if str(c).strip())):
                        is_prov = True
                        continue
                    if is_header_or_metadata_row(r):
                        continue

                    c0 = str(r[0]).strip().rstrip('.')
                    if c0.isdigit():
                        num0 = int(c0)
                        if num0 > 6:
                            is_k = False
                        elif brebes_seen and 1 <= num0 <= 6:
                            is_k = True

                    label_parts = [str(c).strip() for c in r[:3] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None]
                    row_label = ' '.join(label_parts) if label_parts else ' '.join(str(c) for c in r[:2])

                    is_org_row = _is_org_or_title_not_prov_total(row_label)
                    prov_code, prov_name = resolve_provinsi_entity(row_label) if not is_org_row else (None, None)
                    if prov_name:
                        if prov_name == 'Jawa Tengah' and (is_prov or 'provinsi' in row_label.lower() or 'pemprov' in row_label.lower()):
                            w = 'Provinsi Jawa Tengah'
                            prov_jateng_seen = True
                        elif prov_name == 'Jawa Tengah' and (prov_jateng_seen or brebes_seen):
                            w = 'Total Jawa Tengah'
                        else:
                            w = prov_name
                        is_prov = False
                    elif is_prov and not is_org_row and (c0 == '1' or 'jawa tengah' in row_label.lower() or 'provinsi' in row_label.lower()):
                        w = 'Provinsi Jawa Tengah'
                        prov_jateng_seen = True
                        is_prov = False
                    elif not is_org_row and ('provinsi jawa tengah' in row_label.lower() or 'pemprov' in row_label.lower()):
                        w = 'Provinsi Jawa Tengah'
                        prov_jateng_seen = True
                        is_prov = False
                    elif any(k in row_label.lower() for k in ['jumlah', 'total']) and not any(k in row_label.lower() for k in ['curah', 'hari', 'hujan', 'penduduk', 'kecamatan', 'desa', 'kelurahan', 'tps', 'sekolah', 'guru', 'murid', 'kabupaten', 'kota']):
                        w = 'Total Jawa Tengah'
                        is_prov = False
                    else:
                            w = find_wilayah_with_type(row_label, is_kota=is_k)
                            if w == "Umum":
                                if any(_is_standalone_jateng_total(c) for c in r):
                                    w = "Total Jawa Tengah"
                                elif brebes_seen and is_k and (not label_parts or 'total' in txt or 'jumlah' in txt) and any(parse_num(str(c).strip(), is_pdf=is_pdf) is not None for c in r):
                                    w = "Total Jawa Tengah"
                                else:
                                    continue

                    if w == 'Kab. Cilacap':
                        cilacap_seen_flat = True
                    if ('Brebes' in w and (is_pdf or cilacap_seen_flat)) or w == 'Kota Tegal':
                        brebes_seen = True

                    first_idx = next((i for i, c in enumerate(r) if str(c).strip()), -1)
                    raw_w = w.replace('Kab. ', '').replace('Kota ', '')
                    nums = []
                    r_coords = []

                    if is_pdf:
                        row_pairs = []
                        for idx, c in enumerate(r):
                            c_str = str(c).strip()
                            if not c_str:
                                continue
                            if idx == first_idx and c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                continue
                            if c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                next_val = next((str(r[k]).strip() for k in range(idx+1, len(r)) if str(r[k]).strip()), '')
                                if any(k.lower() in next_val.lower() for k in [raw_w]) or any(k.lower() in next_val.lower() for k in KAB_KOTA_JATENG):
                                    continue
                            if any(k.lower() in c_str.lower() for k in [raw_w]):
                                continue
                            if idx <= 3 and re.match(r'^33\.\d{2}$', c_str):
                                continue
                            if w == 'Total Jawa Tengah' and idx <= 3 and len(row_pairs) == 0 and re.match(r'^(?:19|20)\d{2}$', c_str):
                                rem_vals = [x for x in r[idx + 1:] if parse_num(str(x).strip(), is_pdf=True) is not None]
                                if len(rem_vals) >= 2:
                                    continue
                            if c_str in ('...', '…', '..'):
                                row_pairs.append((idx, '...'))
                                continue

                            val = parse_num(c_str, is_pdf=True)
                            if val is not None:
                                row_pairs.append((idx, val))
                        if row_pairs:
                            pdf_row_slots.append((w, len(r), row_pairs))
                        continue
                    else:
                        if active_cols:
                            raw_vals = [parse_num(str(r[c]).strip(), is_pdf=False) for c in active_cols if c < len(r)]
                            if not any(v is not None for v in raw_vals) and not is_dynamic:
                                continue
                            for c_idx in active_cols:
                                raw_c = r[c_idx] if c_idx < len(r) else None
                                if raw_c is None or str(raw_c).strip().lower() in ('', 'none', 'null'):
                                    nums.append(None)
                                else:
                                    c_str = str(raw_c).strip()
                                    v = parse_num(c_str, is_pdf=False)
                                    nums.append(v)
                                r_coords.append(_get_tpl_coord(r_idx, c_idx))
                        elif w in ('Total Jawa Tengah', 'Jawa Tengah', 'Provinsi Jawa Tengah') and common_first_data_col is not None:
                            first_data_col = common_first_data_col
                        else:
                            w_idx = -1
                            for i, c in enumerate(r[:4]):
                                c_s = str(c).strip()
                                if raw_w.lower() in c_s.lower():
                                    w_idx = i
                                    break
                            first_data_col = 2 if w_idx == -1 else w_idx + 1
                            if first_data_col < len(r) and re.match(r'^33\.\d{2}$', str(r[first_data_col]).strip()):
                                first_data_col += 1
                            if w not in ('Total Jawa Tengah', 'Jawa Tengah', 'Provinsi Jawa Tengah'):
                                common_first_data_col = first_data_col

                        if not active_cols:
                            if expected_len is None:
                                for idx, c in enumerate(r):
                                    c_str = str(c).strip()
                                    if not c_str:
                                        continue
                                    if idx == first_idx and c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                        continue
                                    if c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                        next_val = next((str(r[k]).strip() for k in range(idx+1, len(r)) if str(r[k]).strip()), '')
                                        if any(k.lower() in next_val.lower() for k in [raw_w]) or any(k.lower() in next_val.lower() for k in KAB_KOTA_JATENG):
                                            continue
                                    if any(k.lower() in c_str.lower() for k in [raw_w]):
                                        continue
                                    if idx <= 3 and re.match(r'^33\.\d{2}$', c_str):
                                        continue
                                    val = parse_num(c_str, is_pdf=False)
                                    if val is not None:
                                        nums.append(val)
                                        r_coords.append(_get_tpl_coord(r_idx, idx))
                            else:
                                row_data = r[first_data_col: first_data_col + expected_len]
                                for ci, c in enumerate(row_data, start=first_data_col):
                                    if c is None or str(c).strip().lower() in ('', 'none', 'null'):
                                        nums.append(None)
                                    else:
                                        c_str = str(c).strip()
                                        v = parse_num(c_str, is_pdf=False)
                                        nums.append(v)
                                    r_coords.append(_get_tpl_coord(r_idx, ci))

                    if nums:
                        if w not in out:
                            out[w] = []
                        out[w].extend(nums)
                        if not is_pdf:
                            src_coord_dict.setdefault(w, []).extend(r_coords)

                if is_pdf and pdf_row_slots:
                    from collections import Counter
                    len_counts = Counter(rlen for _, rlen, _ in pdf_row_slots)
                    modal_rlen, modal_rlen_cnt = len_counts.most_common(1)[0]
                    canonical_pdf_cols = None
                    if modal_rlen_cnt >= max(5, int(len(pdf_row_slots) * 0.75)):
                        reg_slot_rows = [
                            pairs for w_s, rlen, pairs in pdf_row_slots
                            if rlen == modal_rlen and w_s not in ('Total Jawa Tengah', 'Jawa Tengah', 'Provinsi Jawa Tengah')
                        ]
                        if len(reg_slot_rows) >= 5:
                            modal_n = Counter(len(p) for p in reg_slot_rows).most_common(1)[0][0]
                            idx_freq = Counter(idx for p in reg_slot_rows for idx, _ in p)
                            min_freq = max(2, int(len(reg_slot_rows) * 0.25))
                            cand_cols = sorted(ci for ci, cnt in idx_freq.items() if cnt >= min_freq)
                            bps_r_idx, bps_r_cells, bps_r_type = find_bps_numbering_row(data_list)
                            bps_data_cols = None
                            if bps_r_idx >= 0 and bps_r_type == 'bps' and len(data_list[bps_r_idx]) == modal_rlen:
                                _bdc = sorted(
                                    ci for ci, lbl in bps_r_cells
                                    if re.search(r'\d+', str(lbl)) and int(re.search(r'\d+', str(lbl)).group(0)) >= 2
                                )
                                observed_cols = {idx for p in reg_slot_rows for idx, _ in p}
                                if _bdc and observed_cols.issubset(set(_bdc)) and any(len(p) == len(_bdc) for p in reg_slot_rows):
                                    bps_data_cols = _bdc
                            if bps_data_cols and len(bps_data_cols) >= len(cand_cols):
                                canonical_pdf_cols = bps_data_cols
                            elif len(cand_cols) == modal_n:
                                canonical_pdf_cols = cand_cols
                    canonical_set = set(canonical_pdf_cols) if canonical_pdf_cols else None
                    for w_s, rlen, pairs in pdf_row_slots:
                        if (
                            canonical_pdf_cols
                            and rlen == modal_rlen
                            and len(pairs) < len(canonical_pdf_cols)
                            and {idx for idx, _ in pairs}.issubset(canonical_set)
                        ):
                            by_idx = dict(pairs)
                            aligned_nums = [by_idx.get(ci, None) for ci in canonical_pdf_cols]
                        else:
                            aligned_nums = [v for _, v in pairs]
                        out.setdefault(w_s, []).extend(aligned_nums)
                return out

            # Deteksi Page-Per-Year pada tabel Regional multi-halaman
            page_years = {}
            if target_pages and len(target_pages) > 1:
                for p in target_pages:
                    p_tables = get_cached_page_tables(pdf_path, p)
                    p_y = None
                    for r_chk in p_tables[:5]:
                        if not isinstance(r_chk, list):
                            continue
                        for c in r_chk:
                            c_str = str(c).strip() if c else ''
                            if re.match(r'^(?:19|20)\d{2}$', c_str):
                                p_y = c_str
                                break
                        if p_y:
                            break
                        m_txt = re.findall(r'\b(20\d{2})\b', ' '.join(str(c) for c in r_chk))
                        if m_txt and len(set(m_txt)) == 1:
                            p_y = m_txt[0]
                            break
                    if p_y:
                        page_years[p] = p_y

            is_page_per_year = (
                len(set(page_years.values())) >= 2
                and target_year in page_years.values()
                and len(set(header_years)) <= 1
            )

            src_has_year_subrows, _ = _detect_year_subrows(clean_source_rows, is_pdf=False)
            db_multi_yr_set = {str(y) for y in header_years} if len(set(header_years)) >= 2 else set()
            expand_yrs_param = db_multi_yr_set if (len(db_multi_yr_set) >= 2 and not src_has_year_subrows) else None

            if is_page_per_year:
                active_pages = [p for p, y in page_years.items() if y == target_year]
                active_rows = []
                for p in active_pages:
                    active_rows.extend(get_cached_page_tables(pdf_path, p))

                pdf_dict = get_clean_rows_dict(active_rows, is_pdf=True, target_year=target_year)

                # Ekstrak data historis untuk halaman tahun selain target_year
                for hy in sorted(set(page_years.values())):
                    if hy != target_year:
                        hy_pages = [p for p, y in page_years.items() if y == hy]
                        hy_rows = []
                        for p in hy_pages:
                            hy_rows.extend(get_cached_page_tables(pdf_path, p))
                        hist_pdf_years_reg[hy] = get_clean_rows_dict(hy_rows, is_pdf=True, target_year=hy)

                rows_for_headers = active_rows
                reg_page_label = f"Halaman {active_pages[0]} ({target_year})" if len(active_pages) == 1 else f"Halaman {active_pages[0]}-{active_pages[-1]} ({target_year})"
            else:
                pdf_dict = get_clean_rows_dict(rows, is_pdf=True, target_year=target_year, expand_years_for_db=expand_yrs_param)
                if "_expanded_base_p_len_" in pdf_dict:
                    expanded_pdf_base_len_reg = pdf_dict.pop("_expanded_base_p_len_")
                rows_for_headers = rows
                reg_page_label = f"Halaman {target_pages[0]}" if len(target_pages) == 1 else (f"Halaman {target_pages[0]}-{target_pages[-1]}" if len(target_pages) > 1 else "")

            sample_p_nums = next((nums for nums in pdf_dict.values() if nums), [])
            p_len = len(sample_p_nums)
            src_dict = get_clean_rows_dict(clean_source_rows, is_pdf=False, expected_len=p_len, target_year=target_year)

            sample_s_nums = next((nums for nums in src_dict.values() if nums), [])
            s_len = len(sample_s_nums)

            hdr_exp_p_len = expanded_pdf_base_len_reg if (expanded_pdf_base_len_reg and expanded_pdf_years_reg) else p_len
            pdf_headers = extract_pdf_headers(rows_for_headers, expected_p_len=hdr_exp_p_len, pdf_path=pdf_path, target_pages=active_pages if is_page_per_year else target_pages, target_table=target_table)
            if expanded_pdf_base_len_reg and expanded_pdf_years_reg:
                pdf_headers = [f"{h} ({ey})" for ey in expanded_pdf_years_reg for h in pdf_headers]

            if len(headers) > s_len and any('tahun' in h.lower() for h in headers[:len(headers)-s_len]):
                headers = headers[-s_len:]

            col_mapping, pdf_extra_indices, col_year_tags, final_metric_names = align_columns_semantically(
                headers, pdf_headers, src_dict, pdf_dict, target_year=target_year, table_title=src_title
            )

            empty_src_cols = set()
            for c_idx in range(s_len):
                if all(s_vals[c_idx] == 0.0 for s_vals in src_dict.values() if c_idx < len(s_vals)):
                    empty_src_cols.add(c_idx)

            clean_reg_sec = "" if (reg_page_label.startswith("Halaman ") or reg_page_label.startswith("Hal. ")) else reg_page_label
            reg_pages_arr = active_pages if is_page_per_year else target_pages

            _reg_scale_pairs = []
            for w, p_nums in pdf_dict.items():
                s_nums = src_dict.get(w, []) or (src_dict.get("Jawa Tengah" if w == "Total Jawa Tengah" else "Total Jawa Tengah", []) if w in ("Total Jawa Tengah", "Jawa Tengah") else [])
                if not s_nums:
                    continue
                for s_idx, p_idx in col_mapping.items():
                    if p_idx < len(p_nums) and s_idx < len(s_nums):
                        _reg_scale_pairs.append((p_idx, p_nums[p_idx], s_nums[s_idx]))
            _en_thou_reg_cols = _detect_en_thousands_pdf_cols(_reg_scale_pairs)

            for w, p_nums in pdf_dict.items():
                s_nums = src_dict.get(w, [])
                s_coords = src_coord_dict.get(w, [])
                if not s_nums and w in ("Total Jawa Tengah", "Jawa Tengah"):
                    alt_w = "Jawa Tengah" if w == "Total Jawa Tengah" else "Total Jawa Tengah"
                    s_nums = src_dict.get(alt_w, [])
                    if not s_coords:
                        s_coords = src_coord_dict.get(alt_w, [])
                if not s_nums:
                    if w == "Total Jawa Tengah":
                        for p_idx, pv_raw in enumerate(p_nums):
                            pv = _resolve_pdf_val_with_scale(pv_raw, is_en_thousands_col=(p_idx in _en_thou_reg_cols))
                            p_disp = format_display_val(pv)
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Metrik #{p_idx + 1}"
                            results["pdf_only"].append({
                                "wilayah": w, "section": clean_reg_sec, "metric": base_m,
                                "pdf_header": base_m, "src_header": None,
                                "values": [p_disp], "note": "Total Jawa Tengah tercetak di buku PDF, tidak dimuat di database sumber",
                                "type": "delete", "pages": reg_pages_arr
                            })
                    continue

                for s_idx, p_idx in col_mapping.items():
                    if p_idx >= len(p_nums) or s_idx >= len(s_nums):
                        continue
                    sv = s_nums[s_idx]
                    c_coord = s_coords[s_idx] if s_idx < len(s_coords) else None
                    pv = _resolve_pdf_val_with_scale(p_nums[p_idx], sv=sv, is_en_thousands_col=(p_idx in _en_thou_reg_cols))
                    pdf_h = pdf_headers[p_idx] if (p_idx is not None and p_idx < len(pdf_headers)) else ""
                    src_h = headers[s_idx] if s_idx < len(headers) else ""
                    metric_name = pdf_h or final_metric_names.get(s_idx, (headers[s_idx] if s_idx < len(headers) else f"Metrik #{s_idx + 1}"))
                    if w == "Total Jawa Tengah" and sv is None:
                        if _is_non_additive_metric_header(src_h) or _is_non_additive_metric_header(metric_name):
                            continue
                    if is_page_per_year and target_year and target_year not in str(metric_name):
                        metric_name = f"{metric_name} ({target_year})"

                    is_match, is_tol, diff_val = evaluate_cell_match(pv, sv, tolerance=tolerance)
                    abs_diff = format_display_val(abs(diff_val)) if diff_val is not None else "-"

                    p_disp = format_display_val(pv)
                    s_disp = format_display_val(sv)

                    if is_match:
                        m_item = {
                            "wilayah": w, "section": clean_reg_sec, "metric": metric_name,
                            "pdf_header": pdf_h, "src_header": src_h,
                            "val": p_disp, "pdf_val": p_disp, "src_val": s_disp,
                            "pages": reg_pages_arr
                        }
                        if c_coord:
                            m_item["coord"] = c_coord
                        if is_tol:
                            m_item["is_tolerance_match"] = True
                            m_item["tolerance_diff"] = round(diff_val, 4)
                        results["matches"].append(m_item)
                    else:
                        diff_out, note = _build_diff_entry_meta(
                            pv, sv, p_disp, s_disp, diff_val,
                            is_empty_col=(s_idx in empty_src_cols)
                        )
                        d_item = {
                            "wilayah": w, "section": clean_reg_sec, "metric": metric_name,
                            "pdf_header": pdf_h, "src_header": src_h,
                            "pdf_val": p_disp, "src_val": s_disp, "diff": diff_out, "note": note, "type": "replace",
                            "pages": reg_pages_arr
                        }
                        if c_coord:
                            d_item["coord"] = c_coord
                        results["diffs"].append(d_item)

                if pdf_extra_indices:
                    for p_idx in pdf_extra_indices:
                        if p_idx < len(p_nums):
                            pv = _resolve_pdf_val_with_scale(p_nums[p_idx], is_en_thousands_col=(p_idx in _en_thou_reg_cols))
                            pv_disp = format_display_val(pv)
                            y_tag = col_year_tags.get(p_idx, "")
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Kolom Ekstra #{p_idx + 1}"

                            # Layer 2 Fallback: Jika kolom ekstra PDF adalah 'Grup - Jumlah/Total', bandingkan dengan penjumlahan sub-kolom grup di DB
                            if not y_tag and not _is_non_additive_metric_header(base_m):
                                m_parts = [pt.strip() for pt in str(base_m).replace('||', ' - ').split(' - ') if pt.strip()]
                                grp_s_idxs = []
                                if len(m_parts) >= 2 and m_parts[-1].lower() in ('jumlah', 'total', 'subjumlah'):
                                    grp_pfx = ' - '.join(m_parts[:-1]).lower()
                                    for si, mp_i in col_mapping.items():
                                        cand_h = str(pdf_headers[mp_i] if (mp_i is not None and mp_i < len(pdf_headers)) else (headers[si] if si < len(headers) else '')).replace('||', ' - ').lower().strip()
                                        if cand_h.startswith(grp_pfx + ' - ') and not re.search(r'\b(jumlah|total|subjumlah)\b', cand_h.split(' - ')[-1]) and not _is_non_additive_metric_header(cand_h):
                                            grp_s_idxs.append(si)
                                if len(grp_s_idxs) >= 2:
                                    sv_grp = sum(s_nums[si] for si in grp_s_idxs if si < len(s_nums) and isinstance(s_nums[si], (int, float)))
                                    pv_res_g = _resolve_pdf_val_with_scale(p_nums[p_idx], sv=sv_grp, is_en_thousands_col=(p_idx in _en_thou_reg_cols))
                                    is_m_g, is_tol_g, diff_g = evaluate_cell_match(pv_res_g, sv_grp, tolerance=tolerance)
                                    p_d_g = format_display_val(pv_res_g)
                                    s_d_g = format_display_val(sv_grp)
                                    if is_m_g:
                                        m_it_g = {
                                            "wilayah": w, "section": clean_reg_sec, "metric": base_m,
                                            "pdf_header": base_m, "src_header": base_m,
                                            "val": p_d_g, "pdf_val": p_d_g, "src_val": s_d_g,
                                            "pages": reg_pages_arr, "is_group_subtotal_match": True
                                        }
                                        if is_tol_g:
                                            m_it_g["is_tolerance_match"] = True
                                            m_it_g["tolerance_diff"] = round(diff_g, 4)
                                        results["matches"].append(m_it_g)
                                    else:
                                        d_out_g, note_g = _build_diff_entry_meta(pv_res_g, sv_grp, p_d_g, s_d_g, diff_g)
                                        results["diffs"].append({
                                            "wilayah": w, "section": clean_reg_sec, "metric": base_m,
                                            "pdf_header": base_m, "src_header": base_m,
                                            "pdf_val": p_d_g, "src_val": s_d_g, "diff": d_out_g, "note": note_g, "type": "replace",
                                            "pages": reg_pages_arr
                                        })
                                    continue

                            m_name = f"{base_m} ({y_tag})" if (y_tag and y_tag not in base_m) else base_m
                            note = f"Data historis ({y_tag}) tercetak di buku PDF, tidak dimuat di database sumber" if y_tag else "Tercetak di PDF tapi tidak ada kolomnya di database"

                            results["pdf_only"].append({
                                "wilayah": w, "section": clean_reg_sec, "metric": m_name,
                                "pdf_header": base_m, "src_header": None,
                                "values": [pv_disp], "note": note, "type": "delete",
                                "pages": reg_pages_arr,
                                "is_pdf_extra": True
                            })

            if hist_pdf_years_reg:
                for hy, w_dict in hist_pdf_years_reg.items():
                    hy_pages = [p for p, y in page_years.items() if y == hy] if 'page_years' in locals() else []
                    hy_sec = f"Halaman {hy_pages[0]} ({hy})" if len(hy_pages) == 1 else (f"Halaman {hy_pages[0]}-{hy_pages[-1]} ({hy})" if hy_pages else f"Tahun {hy}")
                    for w_ent, nums in w_dict.items():
                        for p_idx, pv in enumerate(nums):
                            pv_disp = format_display_val(pv)
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Metrik #{p_idx + 1}"
                            base_m = re.sub(r'^(?:19|20)\d{2}\s*[\-–—]\s*', '', str(base_m)).strip()
                            if not base_m or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_m).lower().strip()):
                                base_m = f"Metrik #{p_idx + 1}"
                            metric_lbl = f"{base_m} ({hy})" if hy not in base_m else base_m
                            results["pdf_only"].append({
                                "wilayah": w_ent, "section": hy_sec, "metric": metric_lbl,
                                "pdf_header": metric_lbl, "src_header": None,
                                "values": [pv_disp], "note": f"Data historis ({hy}) tercetak di buku PDF, tidak dimuat di database sumber {target_year or ''}".strip(),
                                "type": "delete",
                                "is_pdf_extra": True
                            })

    results["pages"] = target_pages or []
    results["id_db"] = target_info.get("id_db")
    results["id_indikator"] = target_info.get("id_indikator")

    # Guard lintas-tahun untuk API Satudata:
    # Status Beda Tahun (year_unavailable) HANYA muncul bila 0% tahun yang tercetak di PDF beririsan
    # dengan tahun yang tersedia di API (misal PDF 2023-2024 vs API cuma 2025, atau PDF 2025 vs API cuma 2026).
    # Selama minimal 1 tahun beririsan (misal PDF 2021-2025 dengan 2025 kosong, dan API punya 2021-2024),
    # sistem WAJIB menjalankan komparasi penuh per data dan per tahun tanpa memvonis Beda Tahun!
    if not is_dynamic and source_label == "API Satudata" and 'api_all_years' in locals() and api_all_years:
        _pdf_yrs_chk = sorted(pdf_years_set) if ('pdf_years_set' in locals() and pdf_years_set) else []
        _max_dy_chk = (pub_year - 1) if pub_year else None
        _elig_api_chk = [y for y in api_all_years if y.isdigit() and (_max_dy_chk is None or int(y) <= _max_dy_chk)]
        _common_chk = set(_elig_api_chk).intersection(set(_pdf_yrs_chk))
        if _pdf_yrs_chk and not _common_chk:
            if len(results["matches"]) == 0 or len(results["diffs"]) > len(results["matches"]):
                _req_y_str = ", ".join(_pdf_yrs_chk)
                _avail_y_str = ", ".join(_elig_api_chk or api_all_years)
                return {
                    "status": "error",
                    "source_type": "API Satudata",
                    "message": f"Data API untuk Tahun {_req_y_str} belum tersedia di Portal Satu Data (tahun tersedia di API: {_avail_y_str})",
                    "is_api_empty": True,
                    "is_year_unavailable": True,
                    "error_kind": "year_unavailable",
                    "requested_years": _pdf_yrs_chk,
                    "available_years": _elig_api_chk or api_all_years,
                    "id_db": target_info.get("id_db"),
                    "pages": target_pages or []
                }

    if is_template_extraction:
        autofill_cells = []
        for d in results.get("diffs", []):
            sv = d.get("src_val")
            if isinstance(sv, (int, float)) and int(round(sv)) <= -900001:
                seq_key = str(abs(int(round(sv))) - 900000)
                coord = cell_coords.get(seq_key)
                if coord:
                    autofill_cells.append({
                        "id_baris_item": coord["b"],
                        "id_karakteristik_item": coord["k"],
                        "id_periode_item": coord["p"],
                        "tahun_data": coord.get("t"),
                        "nilai": d.get("pdf_val"),
                        "wilayah": d.get("wilayah", ""),
                        "metric": d.get("metric", ""),
                        "section": d.get("section", ""),
                    })
        return {
            "status": "error",
            "source_type": "Tabel Dinamis",
            "message": "Data Tabel Dinamis belum diisi",
            "is_api_empty": True,
            "id_db": target_info.get("id_db"),
            "id_indikator": target_info.get("id_indikator"),
            "pages": target_pages or [],
            "can_autofill": len(autofill_cells) > 0,
            "autofill_count": len(autofill_cells),
            "autofill_cells": autofill_cells,
        }

    if is_dynamic and (results.get("diffs") or results.get("pdf_only")) and tpl_grid and cell_coords:
        # Direct Matrix Lookup for cell coordinates without temp-file recursion
        if any("coord" not in d for d in results["diffs"]) or results.get("pdf_only"):
            try:
                direct_map = {}
                hdr_row = [str(c or "").strip().lower() for c in tpl_grid[0]]
                is_single_metric_col = (len(hdr_row) == 2)
                is_kota_ctx = False
                seen_regencies = set()
                from collections import Counter
                last_parts = []
                yr_last_parts = []
                for c_raw in hdr_row[1:]:
                    norm_c = re.sub(r'\s+', ' ', str(c_raw or '').replace("||", " - ")).strip().lower()
                    if " - " in norm_c:
                        p_list = [p.strip() for p in norm_c.split(" - ") if p.strip()]
                        if len(p_list) > 1:
                            last_parts.append(p_list[-1])
                            m_hy = re.match(r'^((?:19|20)\d{2})\b', p_list[0])
                            if m_hy:
                                yr_last_parts.append((m_hy.group(1), p_list[-1]))
                last_part_counts = Counter(last_parts)
                yr_last_counts = Counter(yr_last_parts)
                cur_tpl_group = ""
                for r in tpl_grid[1:]:
                    if not r:
                        continue
                    r0 = str(r[0] or "").strip()
                    r0_low = r0.lower()
                    if ("kabupaten" in r0_low or "regency" in r0_low) and (len(r) < 2 or r[1] is None):
                        is_kota_ctx = False
                        continue
                    if ("kota" in r0_low or "municipality" in r0_low) and (len(r) < 2 or r[1] is None):
                        is_kota_ctx = True
                        continue
                    if r0_low in seen_regencies and r0_low in ("magelang", "semarang", "pekalongan", "tegal"):
                        is_kota_ctx = True
                    seen_regencies.add(r0_low)
                    ent_res = resolve_jateng_entity(r0, is_kota_mode=is_kota_ctx)
                    ent_name = ent_res[1] if isinstance(ent_res, tuple) and ent_res[1] else (ent_res if isinstance(ent_res, str) else r0)
                    has_dyn_vals = any(isinstance(c, (int, float)) and int(round(c)) <= -900001 for c in r[1:])
                    dyn_grid_match = next((dr for dr in (target_info.get("dynamic_grid") or []) if isinstance(dr, list) and dr and str(dr[0] or "").strip().lower() == r0_low), None)
                    row_all_none = bool(dyn_grid_match and all(c is None or str(c).strip() == "" for c in dyn_grid_match[1:]))
                    if r0_low in ("jumlah", "total", "jawa tengah", "provinsi jawa tengah") or ent_name == "Total Jawa Tengah":
                        cur_tpl_group = ""
                    elif row_all_none or not has_dyn_vals:
                        cur_tpl_group = re.sub(r'^(?:\d+|[a-zA-Z](?:\s*,\s*[a-zA-Z])*|[ivxlcdmIVXLCDM]+)[\.\)\s]+', '', r0_low).strip()
                    r0_no_idx = re.sub(r'^(?:\d+|[a-zA-Z](?:\s*,\s*[a-zA-Z])*|[ivxlcdmIVXLCDM]+)[\.\)\s]+', '', r0_low).strip()
                    r0_no_paren = re.sub(r'\s*\([^)]*\)\s*', ' ', r0_no_idx).strip()
                    w_aliases = {
                        ent_name.lower().strip(),
                        r0_low,
                        r0_no_idx,
                        r0_no_paren,
                        re.sub(r'[^\w\s\-]', '', r0_low).strip(),
                        re.sub(r'[^\w\s\-]', '', r0_no_idx).strip(),
                        re.sub(r'[^\w\s\-]', '', ent_name.lower()).strip()
                    }
                    if is_interval_label(r0_no_idx):
                        r0_dash = re.sub(r'\s*[\-–—]\s*', ' - ', r0_no_idx).strip()
                        for iv_base in (r0_no_idx, r0_dash):
                            w_aliases.add(iv_base)
                            w_aliases.add(f"{iv_base} tahun")
                            w_aliases.add(f"{iv_base} jam")
                    if cur_tpl_group and has_dyn_vals and not row_all_none:
                        if r0_no_paren and cur_tpl_group not in r0_no_paren:
                            w_aliases.add(f"{cur_tpl_group}/{r0_no_paren}")
                            w_aliases.add(f"{cur_tpl_group} - {r0_no_paren}")
                            w_aliases.add(f"{cur_tpl_group} ({r0_no_paren})")
                            if is_interval_label(r0_no_paren):
                                r0_p_dash = re.sub(r'\s*[\-–—]\s*', ' - ', r0_no_paren).strip()
                                w_aliases.add(f"{cur_tpl_group} ({r0_p_dash} tahun)")
                            # Contoh: cur_tpl_group='golongan i', r0_no_paren='i/a' -> 'golongan i/a'
                            grp_parts = cur_tpl_group.split()
                            if len(grp_parts) >= 2 and r0_no_paren.startswith(grp_parts[-1] + "/"):
                                w_aliases.add(f"{' '.join(grp_parts[:-1])} {r0_no_paren}")
                    if r0_low in ("jumlah", "total", "jawa tengah", "provinsi jawa tengah") or ent_name == "Total Jawa Tengah":
                        w_aliases.update(("total jawa tengah", "jawa tengah", "jumlah", "total", "provinsi jawa tengah"))
                    for c_idx in range(1, min(len(r), len(hdr_row))):
                        val = r[c_idx]
                        if isinstance(val, (int, float)) and int(round(val)) <= -900001:
                            seq_key = str(abs(int(round(val))) - 900000)
                            c_obj = cell_coords.get(seq_key)
                            if c_obj:
                                coord_val = {
                                    "id_baris_item": c_obj["b"],
                                    "id_karakteristik_item": c_obj["k"],
                                    "id_periode_item": c_obj["p"],
                                    "tahun_data": c_obj.get("t"),
                                }
                                h_raw = str(hdr_row[c_idx] or "").strip()
                                h_norm = re.sub(r'\s+', ' ', h_raw.replace("||", " - ")).strip()
                                h_no_yr = re.sub(r'^(?:19|20)\d{2}(?:\s*/\s*(?:19|20)\d{2})?\s*[\-–—:]?\s*|\s*[\(\-–—]?\s*\b(?:19|20)\d{2}(?:\s*/\s*(?:19|20)\d{2})?\b\s*[\)]?\s*$', '', h_norm).strip()
                                h_aliases = set()
                                for h_str in (h_raw, h_norm, h_no_yr):
                                    if not h_str:
                                        continue
                                    h_low = h_str.lower().strip()
                                    h_aliases.add(h_str)
                                    h_aliases.add(h_low)
                                    if re.match(r'^(?:19|20)\d{2}$', h_low):
                                        h_aliases.add(f"tahun {h_low}")
                                    if "/" in h_low:
                                        h_aliases.add(h_low.split("/")[0].strip())
                                        h_aliases.add(re.sub(r'/[^\s\-]+', '', h_low).strip())
                                    if "kelurahan/desa" in h_low:
                                        h_aliases.add(h_low.replace("kelurahan/desa", "kelurahan/"))
                                        h_aliases.add(h_low.replace("kelurahan/desa", "kelurahan"))
                                    if " - " in h_low:
                                        parts = [p.strip() for p in h_low.split(" - ") if p.strip()]
                                        if len(parts) > 1:
                                            if last_part_counts.get(parts[-1], 0) == 1 and parts[-1] not in ("jumlah", "total"):
                                                h_aliases.add(parts[-1])
                                            m_hy = re.match(r'^((?:19|20)\d{2})\b', parts[0])
                                            if m_hy and yr_last_counts.get((m_hy.group(1), parts[-1]), 0) == 1:
                                                h_aliases.add(f"{m_hy.group(1)} - {parts[-1]}")
                                                h_aliases.add(f"{m_hy.group(1)} {parts[-1]}")
                                            h_aliases.add(" ".join(parts))
                                if is_single_metric_col:
                                    h_aliases.add("*")
                                for wa in sorted(w_aliases):
                                    if not wa:
                                        continue
                                    for ha in sorted(h_aliases):
                                        if (wa, ha) not in direct_map:
                                            direct_map[(wa, ha)] = coord_val

                def _resolve_coord_for_item(item_obj):
                    w_raw = str(item_obj.get("wilayah", "")).lower().strip()
                    w_clean = re.sub(r'^\d+[\.\)\s]+', '', w_raw).strip()
                    cand_headers = []
                    
                    sec_raw = str(item_obj.get("section") or "").lower().strip()
                    sec_clean = re.sub(r'^(?:halaman|hal\.)\s+[\d\-\(\)\s\w]+', '', sec_raw).strip(" -—–:").strip()
                    
                    raw_headers = []
                    for hk in ("src_header", "metric", "pdf_header"):
                        hv = str(item_obj.get(hk) or "").strip()
                        if hv:
                            raw_headers.append(hv)
                    
                    if sec_clean:
                        for rh in list(raw_headers):
                            raw_headers.append(f"{sec_clean} - {rh}")
                            raw_headers.append(f"{sec_clean} {rh}")
                    
                    for hv in raw_headers:
                        hv_low = hv.lower().strip()
                        hv_norm = re.sub(r'\s+', ' ', hv_low.replace("||", " - ")).strip()
                        hv_no_yr = re.sub(r'^(?:19|20)\d{2}(?:\s*/\s*(?:19|20)\d{2})?\s*[\-–—:]?\s*|\s*[\(\-–—]?\s*\b(?:19|20)\d{2}(?:\s*/\s*(?:19|20)\d{2})?\b\s*[\)]?\s*$', '', hv_norm).strip()
                        cand_headers.extend([hv_low, hv_norm])
                        if hv_no_yr:
                            cand_headers.append(hv_no_yr)
                        if " - " in hv_norm:
                            parts = [p.strip() for p in hv_norm.split(" - ") if p.strip()]
                            if len(parts) > 1:
                                if last_part_counts.get(parts[-1], 0) == 1 and parts[-1] not in ("jumlah", "total"):
                                    cand_headers.append(parts[-1])
                                cand_headers.append(" ".join(parts))
                    
                    if is_single_metric_col:
                        cand_headers.append("*")
                    _item_yrs = set(_extract_header_years(' '.join(raw_headers)))
                    def _yr_ok(dm_coord):
                        if not _item_yrs or not isinstance(dm_coord, dict) or not dm_coord.get("tahun_data"):
                            return True
                        return str(dm_coord.get("tahun_data")) in _item_yrs

                    w_nopunct = re.sub(r'[^\w\s\-]', '', w_clean).strip()
                    for wk in (w_raw, w_clean, w_nopunct):
                        if not wk:
                            continue
                        for hc in cand_headers:
                            if (wk, hc) in direct_map and _yr_ok(direct_map[(wk, hc)]):
                                return direct_map[(wk, hc)]
                    
                    # Fallback 2: Prefix / Substring matching on entity name (cegah anak 'Golongan I/A' mencuri induk 'Golongan I')
                    for (dm_w, dm_h), dm_coord in direct_map.items():
                        if dm_h in cand_headers and len(dm_w) >= 6 and _yr_ok(dm_coord):
                            if (w_clean.startswith(dm_w) and not re.match(rf'^{re.escape(dm_w)}\s*(?:/|[\-–—])', w_clean)) or (dm_w in w_clean and len(dm_w) >= 10):
                                return dm_coord

                    # Fallback 3: Fuzzy matching on wilayah (SequenceMatcher >= 0.85) dengan header cocok
                    for (dm_w, dm_h), dm_coord in direct_map.items():
                        if dm_h in cand_headers and _yr_ok(dm_coord):
                            sim = SequenceMatcher(None, w_clean, dm_w).ratio()
                            if sim >= 0.85:
                                return dm_coord

                    # Fallback 4: Two-way fuzzy matching on both wilayah and header (Tier-2 Fuzzy Coordinate)
                    best_coord = None
                    best_score = 0.0
                    for (dm_w, dm_h), dm_coord in direct_map.items():
                        if not _yr_ok(dm_coord):
                            continue
                        w_sim = SequenceMatcher(None, w_clean, dm_w).ratio()
                        if w_sim >= 0.80:
                            h_sim = max((SequenceMatcher(None, hc, dm_h).ratio() for hc in cand_headers), default=0.0)
                            combo_score = (w_sim * 0.6) + (h_sim * 0.4)
                            if combo_score > best_score and combo_score >= 0.80:
                                best_score = combo_score
                                best_coord = dm_coord
                    if best_coord:
                        return best_coord

                    return None

                for d in results["diffs"]:
                    if "coord" not in d:
                        c_found = _resolve_coord_for_item(d)
                        if c_found:
                            d["coord"] = c_found

                if results.get("pdf_only"):
                    used_coords = set()
                    # Tandai semua cell dynamic_grid yang sudah terisi nilai non-None sebagai terpakai
                    _dyn_grid_chk = target_info.get("dynamic_grid") or []
                    if _dyn_grid_chk and len(_dyn_grid_chk) == len(tpl_grid):
                        for _ri in range(1, len(tpl_grid)):
                            _tr = tpl_grid[_ri]
                            _dr = _dyn_grid_chk[_ri]
                            if not isinstance(_tr, list) or not isinstance(_dr, list):
                                continue
                            for _ci in range(1, min(len(_tr), len(_dr))):
                                _tval = _tr[_ci]
                                _dval = _dr[_ci]
                                if _dval is not None and str(_dval).strip() != "" and isinstance(_tval, (int, float)) and int(round(_tval)) <= -900001:
                                    _skey = str(abs(int(round(_tval))) - 900000)
                                    _co = cell_coords.get(_skey)
                                    if _co:
                                        used_coords.add((_co.get("b"), _co.get("k"), _co.get("p"), _co.get("t")))
                    for m in results.get("matches", []):
                        if "coord" in m and m["coord"]:
                            c = m["coord"]
                            used_coords.add((c.get("id_baris_item"), c.get("id_karakteristik_item"), c.get("id_periode_item"), c.get("tahun_data")))
                        else:
                            _mc = _resolve_coord_for_item(m)
                            if _mc:
                                used_coords.add((_mc.get("id_baris_item"), _mc.get("id_karakteristik_item"), _mc.get("id_periode_item"), _mc.get("tahun_data")))
                    for d in results.get("diffs", []):
                        if "coord" in d and d["coord"]:
                            c = d["coord"]
                            used_coords.add((c.get("id_baris_item"), c.get("id_karakteristik_item"), c.get("id_periode_item"), c.get("tahun_data")))

                    remaining_pdf_only = []
                    for p_item in results["pdf_only"]:
                        if p_item.get("is_pdf_extra"):
                            remaining_pdf_only.append(p_item)
                            continue
                        c_found = _resolve_coord_for_item(p_item)
                        coord_key = (c_found.get("id_baris_item"), c_found.get("id_karakteristik_item"), c_found.get("id_periode_item"), c_found.get("tahun_data")) if c_found else None
                        p_vals = p_item.get("values") or []
                        pv_first = p_vals[0] if p_vals else p_item.get("pdf_val")
                        _pv_num_chk = parse_num(str(pv_first), is_pdf=False) if pv_first is not None else None
                        if c_found and pv_first is not None and coord_key not in used_coords and (_pv_num_chk is None or _pv_num_chk != 0.0):
                            used_coords.add(coord_key)
                            results["diffs"].append({
                                "wilayah": p_item.get("wilayah", ""),
                                "section": p_item.get("section", ""),
                                "metric": p_item.get("metric", ""),
                                "pdf_header": p_item.get("pdf_header", ""),
                                "src_header": p_item.get("src_header") or p_item.get("metric", ""),
                                "pdf_val": pv_first,
                                "src_val": 0,
                                "diff": parse_num(str(pv_first), is_pdf=False) or 0,
                                "note": p_item.get("note", "Di cetakan PDF ada angkanya, tetapi di database kosong"),
                                "type": "replace",
                                "pages": p_item.get("pages", target_pages or []),
                                "coord": c_found,
                            })
                        else:
                            remaining_pdf_only.append(p_item)
                    results["pdf_only"] = remaining_pdf_only
            except Exception:
                pass

    # Kebijakan Penyetaraan Simbol Kosong/Nol (Khusus Matching Tabel — Berlaku untuk API Satudata & Tabel Dinamis):
    # Pasangan simbol tidak tersedia ('...', '…', 'NA', kosong) vs nilai tepat nol/nihil (0, 0.0, '-')
    # disetarakan sebagai Cocok (Match). Jika salah satu sisi bernilai non-nol sekecil apa pun (mis. 0.001 atau 1),
    # WAJIB tetap terdeteksi sebagai Selisih (Diff).
    if results.get("diffs"):
        def _is_unavail_sym(v):
            if v is None:
                return True
            s = str(v).strip().lower()
            return s in ("", "...", "…", "na", "n/a", "n.a", "n.a.")

        def _is_exact_zero_or_dash(v):
            if isinstance(v, bool) or v is None:
                return False
            if isinstance(v, (int, float)):
                return float(v) == 0.0
            s = str(v).strip()
            if s in ("0", "0.0", "0,0", "-", "–", "—", "−"):
                return True
            try:
                return float(s.replace(",", ".")) == 0.0
            except Exception:
                return False

        filtered_diffs = []
        _is_api_src = (results.get("source_type") == "API Satudata")
        for _d in results["diffs"]:
            _pv = _d.get("pdf_val")
            _sv = _d.get("src_val")
            _sym_equiv = (
                (_is_unavail_sym(_pv) and _is_exact_zero_or_dash(_sv))
                or (_is_exact_zero_or_dash(_pv) and _is_unavail_sym(_sv))
            )
            if _sym_equiv:
                _m_equiv = {
                    "wilayah": _d.get("wilayah", ""),
                    "section": _d.get("section", ""),
                    "metric": _d.get("metric", ""),
                    "pdf_header": _d.get("pdf_header", ""),
                    "src_header": _d.get("src_header", ""),
                    "val": _pv,
                    "pdf_val": _pv,
                    "src_val": _sv,
                    "is_symbol_equiv": True,
                }
                if _is_api_src:
                    _m_equiv["is_api_symbol_equiv"] = True
                if _d.get("coord"):
                    _m_equiv["coord"] = _d["coord"]
                results["matches"].append(_m_equiv)
            else:
                filtered_diffs.append(_d)
        results["diffs"] = filtered_diffs

    def _extract_item_year(it, bucket_name=""):
        """Ekstrak tahun eksplisit (mendukung tahun ajaran 2024/2025 maupun tahun tunggal 2025)."""
        yr_pat = re.compile(r'\b((?:19|20)\d{2}(?:/(?:19|20)\d{2})?)\b')
        flds = ('metric', 'pdf_header', 'wilayah', 'section') if bucket_name == 'pdf_only' else ('metric', 'src_header', 'pdf_header', 'wilayah', 'section')
        for fld in flds:
            txt_f = str(it.get(fld) or '')
            m_y = yr_pat.search(txt_f)
            if m_y:
                return m_y.group(1)
        if bucket_name != 'pdf_only':
            co = it.get('coord')
            if isinstance(co, dict):
                t_co = co.get('tahun_data')
                if t_co and str(t_co).isdigit() and 1900 <= int(t_co) <= 2100:
                    return str(t_co)
        else:
            pty = results.get('pdf_target_year') or (str(pub_year - 1) if pub_year else None)
            if pty and str(pty).strip():
                return str(pty).strip()
        ty = results.get('target_year')
        if ty and str(ty).strip():
            return str(ty).strip()
        return ''

    def _build_item_kolom_hierarki(it, item_year):
        """Bangun jalur kolom bertingkat bersih (list segmen) tanpa memotong istilah '/' bahasa Indonesia."""
        def _strip_yr(txt):
            s = str(txt or '').strip()
            if not s:
                return ''
            s = re.sub(r'\s*\(\s*(?:19|20)\d{2}(?:/(?:19|20)\d{2})?\s*\)\s*', ' ', s)
            s = re.sub(r'^(?:tahun\s+)?(?:19|20)\d{2}(?:/(?:19|20)\d{2})?\s*[-–—:]*\s*', '', s, flags=re.IGNORECASE)
            s = re.sub(r'\s*[-–—:]*\s*(?:19|20)\d{2}(?:/(?:19|20)\d{2})?$', '', s)
            return re.sub(r'\s+', ' ', s).strip()

        raw_sec = str(it.get('section') or '')
        raw_sec = re.sub(r'^(?:Halaman|Hal\.)\s+[\d\-\(\)\s\w]+', '', raw_sec, flags=re.IGNORECASE)
        raw_sec = re.sub(r'^(?:Tahun\s+)?(?:19|20)\d{2}(?:[/\-–—](?:19|20)\d{2})?$', '', raw_sec.strip(), flags=re.IGNORECASE).strip()

        c_src = _strip_yr(it.get('src_header'))
        c_pdf = _strip_yr(it.get('pdf_header'))
        c_met = _strip_yr(it.get('metric'))

        def _has_hier(s):
            return any(sep in s for sep in (' - ', ' > ', ' › ', '||'))

        def _is_num_leak(s):
            return bool(s and len(re.findall(r'\d+[.,]\d+', s)) >= 2)

        if _is_num_leak(c_pdf):
            c_pdf = ''
        if _is_num_leak(c_met):
            c_met = ''

        if c_src and _has_hier(c_src):
            chosen = c_src
        elif c_pdf and _has_hier(c_pdf):
            chosen = c_pdf
        elif c_met and _has_hier(c_met):
            chosen = c_met
        else:
            chosen = c_pdf or c_met or c_src or ''

        segs = []
        if raw_sec:
            segs.extend([p.strip() for p in re.split(r'\s*(?:>|›|\|\||\s[-–—]\s)\s*', raw_sec) if p.strip()])
        if chosen:
            parts = [p.strip() for p in re.split(r'\s*(?:>|›|\|\||\s[-–—]\s)\s*', chosen) if p.strip()]
            if segs and parts and segs[-1].lower() == parts[0].lower():
                parts = parts[1:]
            segs.extend(parts)

        # Hapus duplikat berurutan
        dedup = []
        for sg in segs:
            if not dedup or dedup[-1].lower() != sg.lower():
                dedup.append(sg)
        if not dedup:
            fallback_lbl = item_year or str(it.get('metric') or it.get('src_header') or 'Nilai').strip()
            dedup = [fallback_lbl]
        return dedup

    for _bucket_key in ("matches", "diffs", "pdf_only"):
        for _it in results.get(_bucket_key, []):
            if isinstance(_it, dict):
                _yr = _extract_item_year(_it, _bucket_key)
                _kh = _build_item_kolom_hierarki(_it, _yr)
                _it["tahun"] = _yr
                _it["kolom_hierarki"] = _kh
                _it["kolom_path"] = " > ".join(_kh)

    results["summary"] = {
        "total_diffs": len(results["diffs"]),
        "total_matches": len(results["matches"]),
        "total_pdf_only": len(results["pdf_only"]),
    }

    results["mismatches"] = results["diffs"] + results["pdf_only"]
    results["replace_count"] = len(results["diffs"])
    results["delete_count"] = len(results["pdf_only"])
    results["pdf_count"] = len(results["diffs"]) + len(results["matches"]) + len(results["pdf_only"])
    results["source_count"] = len(results["diffs"]) + len(results["matches"])
    results["data_row_count"] = len(source_data) if isinstance(source_data, list) else 0

    return results


def main():
    if len(sys.argv) < 4:
        print(json.dumps({"status": "error", "message": "Missing arguments"}))
        return 1

    pdf_path = sys.argv[1]
    db_json_path = sys.argv[2]
    target_table = sys.argv[3]
    tolerance = float(sys.argv[4]) if len(sys.argv) > 4 else 0.0
    id_db = sys.argv[5] if len(sys.argv) > 5 else None

    try:
        res = compare_head_to_head(pdf_path, target_table, db_json_path, tolerance=tolerance, id_db=id_db)
        print(json.dumps(res))
        return 0
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
        return 1


if __name__ == "__main__":
    sys.exit(main())
