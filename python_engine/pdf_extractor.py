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


def get_cached_page_tables(pdf_path, page_num):
    cache_key = (pdf_path, page_num)
    with _PDF_PAGE_CACHE_LOCK:
        if cache_key in _PDF_PAGE_CACHE:
            return _PDF_PAGE_CACHE[cache_key]

    with pdfplumber.open(pdf_path) as pdf:
        if 0 <= page_num - 1 < len(pdf.pages):
            page = pdf.pages[page_num - 1]
            clean_page = page.filter(is_valid_table_char)
            page_tables = clean_page.extract_tables()

            # Deteksi apakah ekstraksi berbasis garis gagal / menghasilkan tabel borderless 1 kolom
            needs_fallback = False
            if not page_tables:
                needs_fallback = True
            else:
                # Periksa apakah semua tabel yang terekstrak hanya memiliki <= 1 kolom valid
                has_multi_col = False
                for t in page_tables:
                    clean_t = strip_empty_table_columns(t)
                    if clean_t and len(clean_t) > 0 and isinstance(clean_t[0], list) and len(clean_t[0]) > 1:
                        has_multi_col = True
                        break
                if not has_multi_col:
                    raw_txt = clean_page.extract_text() or ""
                    # Bila teks halaman memiliki deretan angka tabular (>= 2 kolom per baris)
                    multi_num_lines = sum(1 for line in raw_txt.splitlines() if len(re.findall(r'\b\d+[\d.,]*\b', line)) >= 2)
                    if multi_num_lines >= 3:
                        needs_fallback = True

            if needs_fallback:
                # Fallback Strategi 1: Kolom vertikal berbasis spasi teks (stream) + garis horizontal
                stream_tables = clean_page.extract_tables(table_settings={
                    "vertical_strategy": "text",
                    "horizontal_strategy": "lines",
                    "snap_tolerance": 3,
                    "join_tolerance": 3,
                })
                if stream_tables and any(len(strip_empty_table_columns(t)[0]) > 1 for t in stream_tables if t and len(t) > 0 and isinstance(t[0], list)):
                    page_tables = stream_tables
                else:
                    # Fallback Strategi 2: Full stream (kolom & baris berbasis koordinat teks)
                    text_tables = clean_page.extract_tables(table_settings={
                        "vertical_strategy": "text",
                        "horizontal_strategy": "text",
                        "snap_tolerance": 4,
                        "join_tolerance": 3,
                    })
                    if text_tables and any(len(strip_empty_table_columns(t)[0]) > 1 for t in text_tables if t and len(t) > 0 and isinstance(t[0], list)):
                        page_tables = text_tables

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


def extract_table_content(pdf_path, target_pdf_info):
    for tb in target_pdf_info:
        pages = tb.get("pages", [])
        rows = []
        for p in pages:
            rows.extend(get_cached_page_tables(pdf_path, p))
        tb["extracted_data"] = rows
