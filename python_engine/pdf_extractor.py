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
    all_cached = True
    for tb in target_pdf_info:
        for p in tb.get("pages", []):
            if (pdf_path, p) not in _PDF_PAGE_CACHE:
                all_cached = False
                break
        if not all_cached:
            break

    if all_cached:
        for tb in target_pdf_info:
            rows = []
            for p in tb.get("pages", []):
                rows.extend(get_cached_page_tables(pdf_path, p))
            tb["extracted_data"] = rows
        return

    with pdfplumber.open(pdf_path) as pdf:
        for tb in target_pdf_info:
            pages = tb.get("pages", [])
            rows = []
            for p in pages:
                cache_key = (pdf_path, p)
                with _PDF_PAGE_CACHE_LOCK:
                    cached = _PDF_PAGE_CACHE.get(cache_key)
                if cached is not None:
                    rows.extend(cached)
                elif 0 <= p - 1 < len(pdf.pages):
                    page = pdf.pages[p - 1]
                    clean_page = page.filter(is_valid_table_char)
                    tables = clean_page.extract_tables()
                    p_rows = []
                    for t in tables:
                        p_rows.extend(strip_empty_table_columns(t))
                    with _PDF_PAGE_CACHE_LOCK:
                        _PDF_PAGE_CACHE[cache_key] = p_rows
                    rows.extend(p_rows)
            tb["extracted_data"] = rows
