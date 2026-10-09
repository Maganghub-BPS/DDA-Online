#Untuk membandingkan tabel pdf dengan database (Memeriksa isi data dan angka per sel)

import sys
import os
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

if sys.platform == "win32":
    try:
        import io
        if hasattr(sys.stdout, 'buffer'):
            sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        if hasattr(sys.stderr, 'buffer'):
            sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except Exception:
        pass

import json
import re
import time
from difflib import SequenceMatcher

# Import modular components & re-export for 100% backward compatibility
from config import *
from normalizer import *
from fetcher import *
from pdf_extractor import *
from table_profiler import *

# PENTING: Penambahan parameter id_db
def compare_head_to_head(pdf_path, target_table, db_json_path, tolerance=0.0, id_db=None):
    with open(db_json_path, "r", encoding="utf-8") as f:
        db_tables = json.load(f)
        
    def clean_num(n):
        if not n: return ""
        c = re.sub(r"^(?i:tabel|table)\s*", "", str(n).strip())
        c = re.sub(r'\s+', '', c)
        return c.rstrip('.')
        
    target_info = None
    
    # 1. Prioritas Lookup via ID Spesifik Database jika diberikan oleh Batch/Excel
    if id_db:
        target_info = next((t for t in db_tables if str(t.get("id", "")) == str(id_db) or clean_num(t.get("nomor_tabel")) == clean_num(id_db)), None)
        
    # 2. Lookup via Nomor Tabel dari PDF
    if not target_info:
        target_info = next((t for t in db_tables if clean_num(t.get("nomor_tabel")) == clean_num(target_table) or str(t.get("id", "")) == clean_num(target_table)), None)
        
    # 3. SAFETY NET FALLBACK FUZZY MATCH: Jika tidak ketemu, Fuzzy Search ke DB menggunakan Judul PDF dari Cache Index
    index_cache_path = pdf_path + ".index.json"
    if not target_info and os.path.exists(index_cache_path):
        try:
            with open(index_cache_path, "r", encoding="utf-8") as f:
                index_cache = json.load(f)
            pdf_cache_info = next((val for k, val in index_cache.items() if clean_num(k) == clean_num(target_table)), None)
            
            if pdf_cache_info and "display_title" in pdf_cache_info:
                from rapidfuzz import fuzz
                def norm_t(text):
                    t = str(text).lower()
                    t = t.replace('sertifikat', 'sertipikat').replace('ditebitkan', 'diterbitkan')
                    t = re.sub(r'\b20\d{2}\b', ' ', t)
                    t = re.sub(r'\b(?:di\s+)?jawa\s+provinsi\s+tengah\b', '', t)
                    t = re.sub(r'\b(?:di\s+)?provinsi\s+jawa\s+tengah\b', '', t)
                    t = re.sub(r'\b(?:di\s+)?jawa\s+tengah\b', '', t)
                    t = re.sub(r'\b(?:dan|serta|atau)\b', ' ', t)
                    t = t.encode('ascii', 'ignore').decode('ascii')
                    t = re.sub(r'[^\w\s]', ' ', t)
                    return re.sub(r'\s+', ' ', t).strip()
                    
                norm_pdf_title = norm_t(pdf_cache_info["display_title"])
                best_sim = 0
                best_t = None
                for t in db_tables:
                    norm_db = norm_t(t.get('judul_tabel', t.get('judul_ind', '')))
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
        
    link = target_info.get("link_tabel", "")
    if not link: return {"status": "error", "message": "Tidak ada link_tabel untuk tabel ini"}
        
    source_data = fetch_data_from_link(link)
    if isinstance(source_data, dict) and "error" in source_data:
        err_msg = source_data.get('error', '')
        detail_msg = source_data.get('detail', '')
        if source_data.get("is_api_empty") or "tidak ada data dalam api" in err_msg.lower() or "500" in str(detail_msg) or "500" in str(err_msg) or "view_portal_tabel" in link:
            return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "detail": detail_msg or err_msg, "is_api_empty": True}
        return {"status": "error", "message": f"Gagal mengambil data dari link: {err_msg}"}
        
    if not source_data:
        if "view_portal_tabel" in link: return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "is_api_empty": True}
        return {"status": "error", "message": "Format link tidak didukung atau butuh login (misal: Simdasi)"}
        
    # Slicing otomatis jika spreadsheet memuat rekategorisasi/tabel revisi (misal tabel 3.2.5 dan 3.2.7)
    if isinstance(source_data, list):
        rekat_idx = -1
        for idx, r in enumerate(source_data):
            if not isinstance(r, list): continue
            txt = ' '.join(str(c) for c in r).lower()
            if 'rekategorisasi' in txt or ('rse besar' in txt and 'kategori' in txt):
                rekat_idx = idx
                break
        if rekat_idx >= 0:
            source_data = source_data[rekat_idx+1:]
        
    target_pages = None
    if os.path.exists(index_cache_path):
        try:
            with open(index_cache_path, "r", encoding="utf-8") as f:
                index_cache = json.load(f)
            for k, val in index_cache.items():
                if clean_num(k) == clean_num(target_table):
                    target_pages = val.get("pages", []) if isinstance(val, dict) else val
                    break
        except Exception:
            pass
            
    if not target_pages:
        pdf_tables = extract_tables_from_pdf(pdf_path, {clean_num(target_table)})
        matched_k = next((k for k in pdf_tables if clean_num(k) == clean_num(target_table)), None)
        if not matched_k: return {"status": "error", "message": "Tabel tidak terdeteksi di PDF"}
        target_pages = pdf_tables[matched_k]["pages"]
        try:
            cur_cache = {}
            if os.path.exists(index_cache_path):
                with open(index_cache_path, "r", encoding="utf-8") as f: cur_cache = json.load(f)
            cur_cache[target_table] = {"pages": target_pages, "display_title": pdf_tables[matched_k].get("display_title", "")}
            with open(index_cache_path, "w", encoding="utf-8") as f: json.dump(cur_cache, f)
        except Exception:
            pass

    target_pdf_info = [{"nomor_tabel": target_table, "pages": target_pages}]
    extract_table_content(pdf_path, target_pdf_info)
    rows = target_pdf_info[0].get("extracted_data", [])
    
    results = {
        "status": "success",
        "source_type": "Google Sheets" if "docs.google.com" in link else "API Satudata",
        "diffs": [], "matches": [], "pdf_only": [], "summary": {}
    }
    
    # CASE 1: API Satudata (List of Dict records)
    if isinstance(source_data, list) and source_data and isinstance(source_data[0], dict):
        sample_keys = [str(k).lower() for k in source_data[0].keys()]
        is_school = any('sekolah' in k or 'guru' in k or 'murid' in k for k in sample_keys)
        has_party = any('partai' in k for k in sample_keys)

        if has_party:
            grid = normalize_api_data(source_data, rows, target_table)
            if grid and len(grid) > 1:
                header_cols = grid[0][1:]
                api_by_party = {}
                for r in grid[1:]:
                    p_key = match_party_name(r[0])
                    if p_key:
                        api_by_party[p_key] = (r[0], r[1:])

                for pr in rows:
                    if not isinstance(pr, list): continue
                    txt = ' '.join(str(c) for c in pr[:2] if c)
                    p_key = match_party_name(txt)
                    if not p_key or p_key == 'TOTAL': continue

                    nums = []
                    for c in pr[2:]:
                        v = parse_num(c, is_pdf=True)
                        if v is not None:
                            nums.append(v)
                        elif str(c).strip() in ['-', '–', '—', '', ' ']:
                            nums.append(0)

                    if p_key in api_by_party:
                        party_name, api_vals = api_by_party[p_key]
                        for idx in range(min(len(nums), len(api_vals))):
                            pv = nums[idx]
                            av = api_vals[idx]
                            metric = header_cols[idx] if idx < len(header_cols) else f"Kolom {idx+1}"
                            if metric == 'L': metric = 'Laki-Laki'
                            elif metric == 'P': metric = 'Perempuan'
                            elif metric == 'Jumlah': metric = 'Total'

                            if pv == av:
                                results["matches"].append({"wilayah": party_name, "section": "DPRD", "metric": metric, "val": av})
                            else:
                                d_val = round(av - pv, 2)
                                abs_diff = int(abs(d_val)) if abs(d_val).is_integer() else abs(d_val)
                                results["diffs"].append({
                                    "wilayah": party_name, "section": "DPRD", "metric": metric,
                                    "pdf_val": int(pv) if pv.is_integer() else pv,
                                    "src_val": int(av) if av.is_integer() else av,
                                    "diff": d_val,
                                    "note": f"Selisih {abs_diff} (PDF {'lebih sedikit' if d_val > 0 else 'lebih banyak'})",
                                    "type": "replace"
                                })

                results["summary"] = {
                    "total_matches": len(results["matches"]),
                    "total_diffs": len(results["diffs"])
                }
                if len(results["matches"]) > 0 or len(results["diffs"]) > 0:
                    return results

        elif is_school:
            pdf_map = {}
            cur_sec = ""
            is_kota_mode = False
            
            for r in rows:
                if not isinstance(r, list): continue
                txt = ' '.join(str(c).replace('\n', ' ') for c in r if str(c).strip()).lower()
                if 'kota/municipality' in txt: is_kota_mode = True
                elif 'kabupaten/regency' in txt: is_kota_mode = False
                    
                for s in ['sekolah', 'guru', 'murid']:
                    if s in txt:
                        cur_sec = s.title()
                        break
                if is_header_or_metadata_row(r): continue
                w = find_wilayah_with_type(' '.join(str(c) for c in r), is_kota=is_kota_mode)
                if w == "Umum": continue
                
                first_idx = next((i for i, c in enumerate(r) if str(c).strip()), -1)
                nums = []
                for idx, c in enumerate(r):
                    c_str = str(c).strip()
                    if not c_str: continue
                    if idx == first_idx and c_str.rstrip('.').isdigit(): continue
                    if idx <= 3 and re.match(r'^33\.\d{2}$', c_str): continue
                    raw_w_name = w.replace('Kab. ', '').replace('Kota ', '')
                    if any(k.lower() in c_str.lower() for k in [raw_w_name]): continue
                    val = parse_num(c_str, is_pdf=True)
                    if val is not None: nums.append(val)
                pdf_map[(w, cur_sec)] = nums

            for rec in source_data:
                w_raw = rec.get("nama_wilayah") or rec.get("wilayah") or ""
                is_k = 'kota' in w_raw.lower()
                w = find_wilayah_with_type(w_raw, is_kota=is_k)
                
                for s_name, k_pfx in [("Sekolah", "sekolah"), ("Guru", "guru"), ("Murid", "murid")]:
                    key_neg = f"jumlah_{k_pfx}_negeri"
                    key_swa = f"jumlah_{k_pfx}_swasta"
                    key_tot = f"jumlah_total_{k_pfx}" if f"jumlah_total_{k_pfx}" in rec else f"jumlah_{k_pfx}_total"
                    
                    if key_neg not in rec: continue
                    
                    api_neg = parse_num(rec[key_neg], is_pdf=False)
                    api_swa = parse_num(rec[key_swa], is_pdf=False)
                    api_tot = parse_num(rec[key_tot], is_pdf=False)
                    
                    p_nums = pdf_map.get((w, s_name), [])
                    if len(p_nums) == 6:
                        pdf_neg_25, pdf_swa_25, pdf_tot_25 = p_nums[1], p_nums[3], p_nums[5]
                        metrics_chk = [("Negeri", pdf_neg_25, api_neg), ("Swasta", pdf_swa_25, api_swa), ("Total", pdf_tot_25, api_tot)]
                        for m_lbl, p_v, a_v in metrics_chk:
                            m_full = f"{s_name} Negeri" if m_lbl == "Negeri" else f"{s_name} Swasta" if m_lbl == "Swasta" else f"Total {s_name}"
                            if p_v == a_v:
                                results["matches"].append({"wilayah": w, "section": s_name, "metric": m_full, "val": a_v})
                            else:
                                d_val = round(a_v - p_v, 2)
                                abs_diff = int(abs(d_val)) if abs(d_val).is_integer() else abs(d_val)
                                note = f"Selisih {abs_diff} ({s_name} di PDF {'lebih sedikit' if d_val > 0 else 'lebih banyak'})"
                                results["diffs"].append({
                                    "wilayah": w, "section": s_name, "metric": m_full,
                                    "pdf_val": int(p_v) if p_v.is_integer() else p_v, "src_val": int(a_v) if a_v.is_integer() else a_v,
                                    "diff": d_val, "note": note, "type": "replace"
                                })
                        results["pdf_only"].append({
                            "wilayah": w, "section": s_name, "metric": f"Tahun Ajaran 2024/2025",
                            "values": [int(x) if x.is_integer() else x for x in [p_nums[0], p_nums[2], p_nums[4]]],
                            "note": "Tercetak di PDF untuk tahun ajaran lalu (2024/2025), tidak dimuat di API 2025", "type": "delete"
                        })
                    elif len(p_nums) == 3:
                        pdf_metrics = [(f"{s_name} Negeri", p_nums[0], api_neg), (f"{s_name} Swasta", p_nums[1], api_swa), (f"Total {s_name}", p_nums[2], api_tot)]
                        for m_lbl, p_v, a_v in pdf_metrics:
                            if p_v == a_v: results["matches"].append({"wilayah": w, "section": s_name, "metric": m_lbl, "val": a_v})
                            else:
                                d_val = round(a_v - p_v, 2)
                                abs_diff = int(abs(d_val)) if abs(d_val).is_integer() else abs(d_val)
                                note = f"Selisih {abs_diff} ({s_name} di PDF {'lebih sedikit' if d_val > 0 else 'lebih banyak'})"
                                results["diffs"].append({
                                    "wilayah": w, "section": s_name, "metric": m_lbl, "pdf_val": int(p_v) if p_v.is_integer() else p_v,
                                    "src_val": int(a_v) if a_v.is_integer() else a_v, "diff": d_val, "note": note, "type": "replace"
                                })

            if len(results["matches"]) > 0 or len(results["diffs"]) > 0:
                results["summary"] = {
                    "total_matches": len(results["matches"]),
                    "total_diffs": len(results["diffs"])
                }
                return results

        # Fallback / General API Normalizer: convert dict list into 2D grid and let CASE 2 process it!
        grid = normalize_api_data(source_data, rows, target_table)
        if grid and len(grid) > 1:
            source_data = grid
        else:
            return {"status": "error", "source_type": "API Satudata", "message": "Tidak ada data dalam API", "is_api_empty": True}

    # CASE 2: Tabular Google Sheets & Normalized API Tables
    if not (source_data and isinstance(source_data[0], dict)):
        headers = detect_col_headers(source_data)
        archetype = detect_table_archetype(pdf_path, target_table, target_pages, source_data)
        sample_rows = [r for r in source_data[:35] if isinstance(r, list) and not is_title_row(r) and not is_header_or_metadata_row(r)]
        prov_matches = sum(1 for r in sample_rows if resolve_provinsi_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '00', '33.00', 'Total Jawa Tengah', 'Indonesia'])
        jateng_matches = sum(1 for r in sample_rows if resolve_jateng_entity(' '.join(str(c) for c in r[:3]))[0] not in [None, '33.00', 'Total Jawa Tengah'])
        is_regional = (prov_matches >= 3 or jateng_matches >= 3)
        is_categorical = not is_regional and (archetype in ["MULTI_PAGE_ROW_CONTINUATION", "MULTI_PAGE_SECTION_PER_PAGE", "SINGLE_PAGE_CATEGORICAL"])

        if is_categorical:
            # 1. Bersihkan baris PDF dari cover BAB / tabel berikutnya (overflow pages)
            clean_pdf_rows = []
            for r in rows:
                if not isinstance(r, list): continue
                txt = ' '.join(str(c) for c in r if str(c).strip()).lower()
                if any(k in txt for k in ['bab / chapter', 'chapter \nbab', 'bab\nchapter']) or re.search(r'^\s*bab\s+\d+\b', txt):
                    break
                if any(txt.startswith(k) for k in ['tabel / table', 'tabel\n', 'table\n']) and target_table not in txt:
                    m_next = re.search(r'tabel\s+(\d+\.\d+\.\d+)', txt)
                    if m_next and m_next.group(1).strip('.') != target_table.strip('.'):
                        break
                clean_pdf_rows.append(r)

            # 2. Cek apakah ada multi-blok BPS side-by-side di DB
            bps_indices = []
            block_type = 'ones'
            for idx, r in enumerate(source_data[:15]):
                if not isinstance(r, list): continue
                if any(re.search(r'[a-zA-Z]{3,}', str(c)) for c in r): continue
                non_empty = [str(c).strip() for c in r if str(c).strip()]
                if not non_empty or not all(re.match(r'^\(?\d{1,2}\)?\.?$', c) for c in non_empty): continue

                ones = [i for i, c in enumerate(r) if str(c).strip() in ['(1)', '1', '(1).', '1.']]
                twos = [i for i, c in enumerate(r) if str(c).strip() in ['(2)', '2', '(2).', '2.']]
                if len(ones) >= 2:
                    valid_starts = []
                    for col_idx in ones:
                        data_cells = [row[col_idx] for row in source_data[idx+1:idx+12] if isinstance(row, list) and col_idx < len(row) and str(row[col_idx]).strip()]
                        clean_ints = [int(re.sub(r'[^\d]', '', str(cell))) for cell in data_cells if re.sub(r'[^\d]', '', str(cell)).isdigit()]
                        is_row_num = clean_ints and (len(clean_ints) >= 3 and clean_ints[0] == 1 and clean_ints[1] == 2)
                        has_letters = any(any(ch.isalpha() for ch in str(cell)) for cell in data_cells)
                        if has_letters or not data_cells or is_row_num:
                            valid_starts.append(col_idx)
                    if len(valid_starts) >= 2:
                        bps_indices = valid_starts
                    else:
                        bps_indices = ones
                    block_type = 'ones'
                    break
                elif len(twos) >= 2 and target_pages and len(target_pages) >= len(twos):
                    bps_indices = twos
                    block_type = 'twos'
                    break

            def get_cat_key(text):
                if not text: return ''
                if is_interval_label(text):
                    digits = re.findall(r'\d+', str(text))
                    if digits:
                        return f"INT_{'_'.join(digits)}"
                    c = re.sub(r'[\s\*]', '', str(text))
                    if c in ['0', '01']: return 'JAM_0'
                    if '+' in c: return f"JAM_{c.replace('+', '_PLUS')}"
                    c_dash = re.sub(r'[\-–—\ufffd]', '_', c)
                    return f"JAM_{c_dash}"
                p = find_provinsi(text)
                if p: return f"PROV_{re.sub(r'[^\w]', '', p.upper())}"
                s = str(text).upper()
                # 1. Parameter Cuaca Independen (harus sebelum TOTAL agar 'Jumlah Curah Hujan' tidak kena TOTAL)
                if 'CURAH HUJAN' in s or 'PRECIP' in s: return 'CURAH_HUJAN'
                if 'HARI HUJAN' in s or 'RAINY' in s: return 'HARI_HUJAN'
                if 'PENYINARAN MATAHARI' in s or 'SUNSHINE' in s: return 'PENYINARAN_MATAHARI'

                # 2. Total murni
                if s in ['JUMLAH', 'TOTAL', 'JUMLAH/TOTAL', 'TOTAL/JUMLAH', 'JUMLAH TOTAL']: return 'TOTAL'
                if (s.startswith('JUMLAH') or s.startswith('TOTAL')) and not any(k in s for k in ['CURAH', 'HARI', 'HUJAN', 'PENDUDUK', 'SEKOLAH', 'GURU', 'MURID']):
                    return 'TOTAL'
                s = s.replace('FUNGSIHONAL', 'FUNGSIONAL').replace('FUNGSHIONAL', 'FUNGSIONAL')

                m_grade = re.search(r'\b([I|V|X]+/[A-E])\b', s)
                if m_grade: return m_grade.group(1).upper()
                m_range = re.search(r'\bGOLONGAN\s+([I|V|X]+)\b', s)
                if m_range: return f"GOL_{m_range.group(1).upper()}"
                for lvl in ['SD', 'SMP', 'SMA', 'SMK']:
                    if re.search(rf'\b{lvl}\b', s): return lvl
                m_dip = re.search(r'DIPLOMA\s+(I|II|III|IV)\b', s)
                if m_dip: return f"D_{m_dip.group(1)}"
                m_s = re.search(r'\b(S1|S2|S3)\b', s)
                if m_s: return m_s.group(1)
                if 'STRUKTURAL' in s: return 'STRUKTURAL'
                if 'TERTENTU' in s: return 'FUNGSIONAL_TERTENTU'
                if 'UMUM' in s or 'STAF' in s: return 'FUNGSIONAL_UMUM'
                if 'MINIMUM' in s: return 'MINIMUM'
                if 'MAKSIMUM' in s or 'MAXIMUM' in s: return 'MAKSIMUM'
                if 'RATA' in s or 'AVERAGE' in s: return 'RATA_RATA'
                months = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER']
                for idx, m in enumerate(months):
                    if re.search(rf'\b{m}\b', s): return f"BLN_{idx+1:02d}"
                return re.sub(r'[^\w]', '', s)[:25]

            def clean_cat_display(text):
                t = str(text).strip()
                if is_interval_label(t):
                    c = re.sub(r'[\s\*]', '', str(t))
                    if c in ['0', '01']: return '0 Jam (Sementara Tidak Bekerja)'
                    digits = re.findall(r'\d+', c)
                    c_dash = ' - '.join(digits) if digits else re.sub(r'[\-–—\ufffd]', ' - ', c)
                    first_r_txt = ' '.join(str(x) for x in (clean_db_rows[0] if clean_db_rows else [])).lower()
                    is_age = any(k in first_r_txt for k in ['umur', 'usia', 'sekolah', 'pendidikan']) or target_table.startswith('4.')
                    is_hours = 'jam' in first_r_txt or 'hour' in first_r_txt
                    if is_age and not is_hours:
                        return f"{c_dash} Tahun"
                    elif is_hours:
                        return f"{c} Jam" if '+' in c else f"{c_dash} Jam"
                    else:
                        return f"{c_dash}"
                p = find_provinsi(t)
                if p: return p
                m_grade = re.search(r'\b([I|V|X]+/[A-E])\b', t, re.IGNORECASE)
                if m_grade:
                    return f"Golongan {m_grade.group(1).upper()}"
                m_range = re.search(r'\bGolongan\s+([I|V|X]+)\b', t, re.IGNORECASE)
                if m_range:
                    return f"Golongan {m_range.group(1).upper()}"
                t_low = t.lower()
                if 'curah hujan' in t_low: return 'Jumlah Curah Hujan'
                if 'hari hujan' in t_low: return 'Jumlah Hari Hujan'
                if 'penyinaran matahari' in t_low: return 'Penyinaran Matahari'
                t = re.sub(r'^[a-z\.\s/:\n]+(?=[A-Z0-9])', '', t).strip()
                t = re.sub(r'^\d+[\.\s]+', '', t).strip()
                lines = [l.strip() for l in t.split('\n') if l.strip()]
                valid_lines = [l for l in lines if len(l) > 2 or any(c.isdigit() for c in l)]
                first_line = valid_lines[0] if valid_lines else (lines[0] if lines else t)
                if '/' in first_line and not re.search(r'\d/\d', first_line):
                    first_line = first_line.split('/')[0].strip()
                first_line = re.sub(r'\s+[a-z]$', '', first_line)
                clean = re.sub(r'[^\w\s\(\)/]', '', first_line).strip()
                clean = re.sub(r'\s+', ' ', clean).strip()
                return clean if clean else t

            # Bersihkan DB dari baris catatan/sumber
            clean_db_rows = []
            for r in source_data:
                if not isinstance(r, list): continue
                txt = ' '.join(str(c) for c in r).lower().strip()
                if any(txt.startswith(k) for k in ['sumber:', 'source:', 'catatan:', 'note:']):
                    break
                clean_db_rows.append(r)

            # KASUS A: Side-by-side blocks (misal 2.3.6, 2.3.7, 2.3.8, 2.3.13, 1.2.1)
            if bps_indices and target_pages and len(target_pages) >= len(bps_indices):
                col_spans = []
                for i in range(len(bps_indices)):
                    c_start = bps_indices[i]
                    c_end = bps_indices[i+1] if i + 1 < len(bps_indices) else max((len(r) for r in clean_db_rows if isinstance(r, list)), default=c_start+10)
                    col_spans.append((c_start, c_end))

                # Cek apakah ini Tabel Lanjutan (Continuation Table, misal 14.10)
                # Tandanya: ada teks "lanjutan/continued" di header atau baris nomor urut berlanjut (31 > 1)
                is_continuation = (archetype == "MULTI_PAGE_ROW_CONTINUATION")
                if not is_continuation and len(col_spans) >= 2:
                    def get_block_first_int(c_s, c_e):
                        for r in clean_db_rows[3:15]:
                            sub = r[c_s:c_e]
                            for cell in sub[:2]:
                                cs = str(cell).strip().rstrip('.')
                                if cs.isdigit() and int(cs) > 0:
                                    return int(cs)
                        return None
                    int0 = get_block_first_int(col_spans[0][0], col_spans[0][1])
                    int1 = get_block_first_int(col_spans[1][0], col_spans[1][1])
                    if int0 == 1 and int1 is not None and int1 > 20 and int1 <= 150:
                        is_continuation = True

                if is_continuation:
                    sub_db0 = [r[col_spans[0][0]:col_spans[0][1]] for r in clean_db_rows]
                    sub_headers = detect_col_headers(sub_db0)
                    valid_sub_headers = [h for h in sub_headers if not re.match(r'^(?:data\s+)?kolom\s+\d+$', str(h).lower().strip())]

                    stacked_db_recs = []
                    for (c_start, c_end) in col_spans:
                        for r in clean_db_rows:
                            if not isinstance(r, list) or len(r) <= c_start: continue
                            if is_header_or_metadata_row(r): continue
                            sub_r = r[c_start:c_end]
                            non_empty_indices = [idx for idx, c in enumerate(sub_r) if str(c).strip()]
                            if not non_empty_indices: continue
                            c0 = str(sub_r[non_empty_indices[0]]).strip().rstrip('.')
                            if not c0.isdigit(): continue
                            num_id = int(c0)
                            raw_label = str(sub_r[non_empty_indices[1]]).strip() if len(non_empty_indices) > 1 else ''
                            clean_disp = clean_cat_display(raw_label)
                            if not clean_disp: clean_disp = raw_label

                            nums = []
                            label_idx = non_empty_indices[1] if len(non_empty_indices) > 1 else non_empty_indices[0]
                            for c in sub_r[label_idx+1:]:
                                v = parse_num(c, is_pdf=False)
                                if v is not None:
                                    nums.append(v)
                            if nums:
                                stacked_db_recs.append({'id': num_id, 'display': clean_disp, 'key': f"ROW_{num_id}", 'nums': nums})

                    stacked_pdf_recs = []
                    for p_num in target_pages:
                        target_page_info = [{'nomor_tabel': target_table, 'pages': [p_num]}]
                        extract_table_content(pdf_path, target_page_info)
                        p_rows = target_page_info[0].get('extracted_data', [])
                        for r in p_rows:
                            if not isinstance(r, list) or not r: continue
                            if is_header_or_metadata_row(r): continue
                            non_empty = [c for c in r if str(c).strip()]
                            if not non_empty: continue
                            c0 = str(non_empty[0]).strip().rstrip('.')
                            if not c0.isdigit(): continue
                            num_id = int(c0)
                            raw_label = str(non_empty[1]).strip() if len(non_empty) > 1 else ''
                            clean_disp = clean_cat_display(raw_label)
                            if not clean_disp: clean_disp = raw_label

                            nums = []
                            for c in non_empty[2:]:
                                v = parse_num(c, is_pdf=True)
                                if v is not None:
                                    nums.append(v)
                            if nums:
                                stacked_pdf_recs.append({'id': num_id, 'display': clean_disp, 'key': f"ROW_{num_id}", 'nums': nums, 'page': p_num})

                    used_p_ids = set()
                    for s_rec in stacked_db_recs:
                        s_id = s_rec['id']
                        s_disp = s_rec['display']
                        s_nums = s_rec['nums']
                        p_rec = next((p for p in stacked_pdf_recs if p['id'] == s_id and p['id'] not in used_p_ids), None)
                        if not p_rec:
                            p_rec = next((p for p in stacked_pdf_recs if p['display'].lower() == s_disp.lower() and p['id'] not in used_p_ids), None)
                        if not p_rec: continue
                        used_p_ids.add(p_rec['id'])

                        p_nums = p_rec['nums']
                        sec_label = f"Halaman {p_rec['page']}"
                        for i in range(min(len(s_nums), len(p_nums))):
                            sv = s_nums[i]
                            pv = p_nums[i]
                            base_h = valid_sub_headers[i] if i < len(valid_sub_headers) else (sub_headers[i] if i < len(sub_headers) else f"Metrik #{i+1}")
                            if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                base_h = f"Metrik #{i+1}"

                            is_match = False
                            is_tol = False
                            diff_val = round(sv - pv, 3)
                            abs_diff = int(abs(diff_val)) if abs(diff_val).is_integer() else abs(diff_val)

                            if pv == sv or abs(pv - sv) < 0.0001: is_match = True
                            elif pv != 0 and sv != 0 and (abs(sv - (pv * 1000)) <= 2.0 or abs(pv - round(sv / 1000.0, 2)) < 0.01): is_match = True
                            elif pv != 0 and sv != 0 and (abs(pv - (sv * 1000)) <= 2.0 or abs(sv - round(pv / 1000.0, 2)) < 0.01): is_match = True
                            elif pv != 0 and sv != 0 and (abs(sv - (pv * 1000000)) <= 2000.0 or abs(pv - round(sv / 1000000.0, 2)) < 0.01): is_match = True
                            elif pv != 0 and sv != 0 and (abs(pv - (sv * 1000000)) <= 2000.0 or abs(sv - round(pv / 1000000.0, 2)) < 0.01): is_match = True
                            elif rounding_noise(pv, sv):
                                is_match = True
                                is_tol = True
                            elif tolerance > 0 and abs(pv - sv) <= tolerance:
                                is_match = True
                                is_tol = True

                            p_disp = int(pv) if pv.is_integer() else pv
                            src_disp = int(sv) if sv.is_integer() else sv

                            if is_match:
                                m_item = {"wilayah": s_disp, "section": sec_label, "metric": base_h, "val": p_disp, "pdf_val": p_disp, "src_val": src_disp}
                                if is_tol:
                                    m_item["is_tolerance_match"] = True
                                    m_item["tolerance_diff"] = round(diff_val, 4)
                                results["matches"].append(m_item)
                            else:
                                note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})"
                                results["diffs"].append({
                                    "wilayah": s_disp, "section": sec_label, "metric": base_h,
                                    "pdf_val": p_disp, "src_val": src_disp,
                                    "diff": diff_val, "note": note, "type": "replace"
                                })
                else:
                    for block_idx, (p_num, (c_start, c_end)) in enumerate(zip(target_pages[:len(col_spans)], col_spans)):
                        if block_type == 'twos':
                            label_col_len = bps_indices[0]
                            sub_db = [r[:label_col_len] + r[c_start:c_end] for r in clean_db_rows]
                        else:
                            label_col_len = 0
                            sub_db = [r[c_start:c_end] for r in clean_db_rows]

                        sub_headers = detect_col_headers(sub_db)
                        valid_sub_headers = [h for h in sub_headers if not re.match(r'^(?:data\s+)?kolom\s+\d+$', str(h).lower().strip())]
                        sec_label = f"Halaman {p_num} (Blok {block_idx+1})"

                        target_page_info = [{'nomor_tabel': target_table, 'pages': [p_num]}]
                        extract_table_content(pdf_path, target_page_info)
                        sub_pdf_rows = target_page_info[0].get('extracted_data', [])

                        def extract_block_records(data_list, is_pdf=False):
                            sections = ['suhu', 'kelembaban', 'kecepatan angin', 'tekanan udara']
                            cur_sec = ""
                            recs = []
                            for r in data_list:
                                if not isinstance(r, list): continue
                                if is_header_or_metadata_row(r): continue
                                txt = ' '.join(str(c) for c in r).lower().strip()
                                if any(txt.startswith(k) for k in ['sumber:', 'source:', 'catatan:', 'note:']): break

                                # Reset seksi jika masuk ke parameter cuaca independen
                                if any(k in txt for k in ['curah hujan', 'hari hujan', 'penyinaran matahari']):
                                    cur_sec = ''

                                is_sec_hdr = False
                                for s in sections:
                                    if s in txt and not any(parse_num(c, is_pdf=is_pdf) for c in r if str(c).strip()):
                                        cur_sec = s.title()
                                        is_sec_hdr = True
                                        break
                                if is_sec_hdr: continue

                                if block_type == 'twos' and not is_pdf:
                                    raw_label = ' '.join(str(c).strip() for c in r[:label_col_len] if str(c).strip() and parse_num(str(c).strip()) is None)
                                    nums = []
                                    for c in r[label_col_len:]:
                                        v = parse_num(c, is_pdf=False)
                                        nums.append(0.0 if v is None else v)
                                elif is_pdf and block_type == 'twos':
                                    raw_label = str(r[0]).strip()
                                    nums = []
                                    for c in r[1:]:
                                        v = parse_num(c, is_pdf=True)
                                        nums.append(0.0 if v is None else v)
                                else:
                                    first_idx = next((i for i, c in enumerate(r) if str(c).strip()), -1)
                                    raw_label = ''
                                    nums = []
                                    for idx_c, c in enumerate(r):
                                        c_str = str(c).strip()
                                        if not c_str: continue
                                        if idx_c == first_idx and re.match(r'^\d+\.?$', c_str) and int(c_str.rstrip('.')) <= 50: continue
                                        v = parse_num(c_str, is_pdf=is_pdf)
                                        if v is not None: nums.append(v)
                                        else:
                                            if not nums:
                                                if not raw_label: raw_label = c_str
                                                else: raw_label += ' ' + c_str

                                    if not raw_label:
                                        raw_label = ' '.join(str(c).strip() for c in r[:2] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None)

                                clean_disp = clean_cat_display(raw_label)
                                if not clean_disp: continue

                                key = get_cat_key(clean_disp)
                                if cur_sec:
                                    full_key = f"{cur_sec} - {key}"
                                    full_disp = f"{cur_sec} - {clean_disp}"
                                else:
                                    full_key = key
                                    full_disp = clean_disp

                                if nums and full_key:
                                    recs.append({'key': full_key, 'display': full_disp, 'nums': nums})
                            return recs

                        db_recs = extract_block_records(sub_db, is_pdf=False)
                        pdf_recs = extract_block_records(sub_pdf_rows, is_pdf=True)

                        used_pdf = set()
                        for s_rec in db_recs:
                            s_disp = s_rec['display']
                            s_nums = s_rec['nums']
                            match_p_idx = next((idx for idx, p in enumerate(pdf_recs) if idx not in used_pdf and p['key'] == s_rec['key']), None)
                            if match_p_idx is None:
                                match_p_idx = next((idx for idx, p in enumerate(pdf_recs) if idx not in used_pdf and SequenceMatcher(None, p['display'], s_disp).ratio() >= 0.85 and is_safe_fuzzy_match(p['display'], s_disp)), None)
                            if match_p_idx is None: continue
                            used_pdf.add(match_p_idx)
                            p_rec = pdf_recs[match_p_idx]
                            p_nums = p_rec['nums']

                            for i in range(min(len(s_nums), len(p_nums))):
                                sv = s_nums[i]
                                pv = p_nums[i]
                                if len(valid_sub_headers) == len(s_nums):
                                    base_h = valid_sub_headers[i]
                                elif i < len(sub_headers):
                                    base_h = sub_headers[i]
                                else:
                                    base_h = f"Metrik #{i+1}"

                                if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                    base_h = f"Metrik #{i+1}"

                                is_match = False
                                is_tol = False
                                diff_val = round(sv - pv, 3)
                                abs_diff = int(abs(diff_val)) if abs(diff_val).is_integer() else abs(diff_val)

                                if pv == sv or abs(pv - sv) < 0.0001: is_match = True
                                elif pv.is_integer() and (abs(pv - (sv * 1000)) < 0.001 or abs(pv - (sv * 1000000)) < 0.001): is_match = True
                                elif sv.is_integer() and (abs(sv - (pv * 1000)) < 0.001 or abs(sv - (pv * 1000000)) < 0.001): is_match = True
                                elif rounding_noise(pv, sv):
                                    is_match = True
                                    is_tol = True
                                elif tolerance > 0 and abs(pv - sv) <= tolerance:
                                    is_match = True
                                    is_tol = True

                                p_disp = int(pv) if pv.is_integer() else pv
                                src_disp = int(sv) if sv.is_integer() else sv

                                if is_match:
                                    m_item = {"wilayah": s_disp, "section": sec_label, "metric": base_h, "val": p_disp, "pdf_val": p_disp, "src_val": src_disp}
                                    if is_tol:
                                        m_item["is_tolerance_match"] = True
                                        m_item["tolerance_diff"] = round(diff_val, 4)
                                    results["matches"].append(m_item)
                                else:
                                    note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})"
                                    results["diffs"].append({
                                        "wilayah": s_disp, "section": sec_label, "metric": base_h,
                                        "pdf_val": p_disp, "src_val": src_disp,
                                        "diff": diff_val, "note": note, "type": "replace"
                                    })

            # KASUS B: Standard Linear Categorical (misal 1.2.2, 1.2.3, 1.2.4, 2.3.1, 2.3.3)
            else:
                headers = detect_col_headers(clean_db_rows)
                src_title = ' '.join(str(c) for c in (source_data[0] if source_data else []))
                title_years = re.findall(r'\b(20\d{2})\b', src_title)
                target_year = title_years[-1] if title_years else None
                db_header_years = set(re.findall(r'\b(20\d{2})\b', ' '.join(headers)))

                pdf_by_year = {}
                cur_y = "default"
                for r in clean_pdf_rows:
                    if not isinstance(r, list): continue
                    txt = ' '.join(str(c) for c in r)
                    m = re.search(r'\b(20\d{2})\b', txt)
                    if m and not any(parse_num(c, is_pdf=True) for c in r if str(c).strip() and str(c).strip() != m.group(1)):
                        cur_y = m.group(1)
                        if cur_y not in pdf_by_year: pdf_by_year[cur_y] = []
                        continue
                    if cur_y not in pdf_by_year: pdf_by_year[cur_y] = []
                    pdf_by_year[cur_y].append(r)

                if target_year and target_year in pdf_by_year and len(pdf_by_year) > 1 and len(db_header_years) <= 1:
                    target_pdf_rows = pdf_by_year[target_year]
                    hist_pdf_years = {y: r_list for y, r_list in pdf_by_year.items() if y != target_year and y != "default"}
                else:
                    target_pdf_rows = clean_pdf_rows
                    hist_pdf_years = {}

                active_cat_cols = detect_active_data_cols(clean_db_rows)

                def get_gender_sec(txt):
                    all_txt = txt.lower()
                    if ('laki-laki' in all_txt or re.search(r'\bmale\b', all_txt)) and ('perempuan' in all_txt or re.search(r'\bfemale\b', all_txt)):
                        return "Laki-Laki + Perempuan"
                    elif 'laki-laki' in all_txt or re.search(r'\bmale\b', all_txt):
                        return "Laki-Laki"
                    elif 'perempuan' in all_txt or re.search(r'\bfemale\b', all_txt):
                        return "Perempuan"
                    return None

                def extract_cat_rows_std(data_list, is_pdf=False, target_cols=None):
                    records = []
                    cur_sec = ""
                    use_cols = target_cols if target_cols is not None else active_cat_cols
                    for r in data_list:
                        if not isinstance(r, list): continue
                        all_txt = ' '.join(str(c).strip().lower() for c in r if str(c).strip())
                        if any(all_txt.startswith(k) for k in ['sumber', 'source', 'catatan', 'note', 'perubahan data']): break
                        if any(k in all_txt for k in ['sumber:', 'source:', 'catatan:', 'note:', 'sumber/source', 'catatan/note']): break

                        sec = get_gender_sec(all_txt)
                        if sec:
                            cur_sec = sec
                            continue

                        if is_header_or_metadata_row(r): continue

                        if not is_pdf and use_cols:
                            raw_vals = [parse_num(str(r[c]).strip(), is_pdf=False) for c in use_cols if c < len(r)]
                            if not any(v is not None for v in raw_vals):
                                continue
                            first_col = use_cols[0]
                            label_cells = [str(c).strip() for c in r[:first_col] if str(c).strip()]
                            if any(is_interval_label(c) for c in label_cells):
                                raw_label = next(c for c in label_cells if is_interval_label(c))
                            else:
                                raw_label = ' '.join(c for c in label_cells if parse_num(c) is None)
                            nums = []
                            for c_idx in use_cols:
                                c_str = str(r[c_idx]).strip() if c_idx < len(r) else ''
                                v = parse_num(c_str, is_pdf=False)
                                nums.append(0.0 if v is None else v)
                        else:
                            first_idx = next((i for i, c in enumerate(r) if str(c).strip()), -1)
                            raw_label = ''
                            nums = []
                            for idx_c, c in enumerate(r):
                                c_str = str(c).strip()
                                if not c_str: continue
                                if is_interval_label(c_str) and not nums:
                                    raw_label = c_str
                                    continue
                                if idx_c == first_idx and re.match(r'^\d+\.?$', c_str) and int(c_str.rstrip('.')) <= 50 and not is_interval_label(c_str):
                                    continue
                                if not nums and re.search(r'[a-zA-Z]{3,}', c_str):
                                    if not raw_label: raw_label = c_str
                                    else: raw_label += ' ' + c_str
                                    continue
                                v = parse_num(c_str, is_pdf=is_pdf)
                                if v is not None: nums.append(v)
                                else:
                                    if not nums:
                                        if not raw_label: raw_label = c_str
                                        else: raw_label += ' ' + c_str

                        if not raw_label:
                            label_candidates = [str(c).strip() for c in r[:2] if str(c).strip()]
                            if any(is_interval_label(c) for c in label_candidates):
                                raw_label = next(c for c in label_candidates if is_interval_label(c))
                            else:
                                raw_label = ' '.join(str(c).strip() for c in r[:2] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None)

                        clean_disp = clean_cat_display(raw_label)
                        if not clean_disp: continue
                        key = get_cat_key(clean_disp)
                        if cur_sec:
                            digits = re.findall(r'\d+', str(raw_label))
                            c_dash = ' - '.join(digits) if digits else raw_label
                            disp_unit = f"{c_dash} Tahun" if is_interval_label(raw_label) else clean_disp
                            full_disp = f"{cur_sec} ({disp_unit})"
                            full_key = f"{cur_sec.upper().replace(' ', '_')}_{'_'.join(digits) if digits else key}"
                        else:
                            full_disp = clean_disp
                            full_key = key

                        if nums and full_key:
                            records.append({'key': full_key, 'display': full_disp, 'nums': nums, 'sec': cur_sec})
                    return records

                col_years_map = {}
                if len(pdf_by_year) >= 2 and active_cat_cols:
                    for c_idx, h in zip(active_cat_cols, headers):
                        m_y = re.findall(r'\b(20\d{2})\b', str(h))
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
                                match_p = next((p for idx_p, p in enumerate(p_recs_y) if idx_p not in used_p and SequenceMatcher(None, p['display'], s_disp).ratio() >= 0.85 and is_safe_fuzzy_match(p['display'], s_disp)), None)
                            if not match_p: continue
                            used_p.add(p_recs_y.index(match_p))
                            p_nums = match_p['nums']

                            for i in range(min(len(s_nums), len(p_nums))):
                                sv = s_nums[i]
                                pv = p_nums[i]
                                base_h = headers_y[i] if i < len(headers_y) else f"Metrik #{i+1}"
                                if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                    base_h = f"Metrik #{i+1}"

                                is_match = False
                                is_tol = False
                                diff_val = round(sv - pv, 3)
                                abs_diff = int(abs(diff_val)) if abs(diff_val).is_integer() else abs(diff_val)

                                if pv == sv or abs(pv - sv) < 0.0001: is_match = True
                                elif pv != 0 and sv != 0 and (abs(sv - (pv * 1000)) <= 2.0 or abs(pv - round(sv / 1000.0, 2)) < 0.01): is_match = True
                                elif pv != 0 and sv != 0 and (abs(pv - (sv * 1000)) <= 2.0 or abs(sv - round(pv / 1000.0, 2)) < 0.01): is_match = True
                                elif rounding_noise(pv, sv):
                                    is_match = True
                                    is_tol = True
                                elif tolerance > 0 and abs(pv - sv) <= tolerance:
                                    is_match = True
                                    is_tol = True

                                p_disp = int(pv) if pv.is_integer() else pv
                                src_disp = int(sv) if sv.is_integer() else sv

                                if is_match:
                                    m_item = {"wilayah": s_disp, "section": sec_b, "metric": base_h, "val": p_disp, "pdf_val": p_disp, "src_val": src_disp}
                                    if is_tol:
                                        m_item["is_tolerance_match"] = True
                                        m_item["tolerance_diff"] = round(diff_val, 4)
                                    results["matches"].append(m_item)
                                else:
                                    note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})"
                                    results["diffs"].append({
                                        "wilayah": s_disp, "section": sec_b, "metric": base_h,
                                        "pdf_val": p_disp, "src_val": src_disp,
                                        "diff": diff_val, "note": note, "type": "replace"
                                    })
                else:
                    s_recs = extract_cat_rows_std(clean_db_rows, is_pdf=False)
                    for idx_s, s in enumerate(s_recs):
                        s['id'] = idx_s
                    p_recs = extract_cat_rows_std(target_pdf_rows, is_pdf=True)

                    p_grouped = {s['id']: [] for s in s_recs}
                    used_p_indices = set()
                    # Pass 1: Strict key matching
                    for idx_p, p in enumerate(p_recs):
                        match_s = next((s for s in s_recs if s['key'] == p['key']), None)
                        if match_s:
                            p_grouped[match_s['id']].extend(p['nums'])
                            used_p_indices.add(idx_p)

                    # Pass 2: Safe fuzzy matching for unmatched PDF rows
                    for idx_p, p in enumerate(p_recs):
                        if idx_p in used_p_indices: continue
                        match_s = next((s for s in s_recs if SequenceMatcher(None, p['display'], s['display']).ratio() >= 0.85 and is_safe_fuzzy_match(p['display'], s['display'])), None)
                        if match_s:
                            p_grouped[match_s['id']].extend(p['nums'])
                            used_p_indices.add(idx_p)

                    for s_rec in s_recs:
                        s_disp = s_rec['display']
                        s_nums = s_rec['nums']
                        p_nums = p_grouped.get(s_rec['id'], [])
                        if not p_nums: continue

                        for i in range(min(len(s_nums), len(p_nums))):
                            sv = s_nums[i]
                            pv = p_nums[i]
                            base_h = headers[i] if i < len(headers) else f"Metrik #{i+1}"
                            if not base_h or re.match(r'^(?:data\s+)?kolom\s+\d+$', str(base_h).lower().strip()):
                                base_h = f"Metrik #{i+1}"

                            is_match = False
                            is_tol = False
                            diff_val = round(sv - pv, 3)
                            abs_diff = int(abs(diff_val)) if abs(diff_val).is_integer() else abs(diff_val)

                            if pv == sv or abs(pv - sv) < 0.0001: is_match = True
                            elif pv != 0 and sv != 0 and (abs(sv - (pv * 1000)) <= 2.0 or abs(pv - round(sv / 1000.0, 2)) < 0.01): is_match = True
                            elif pv != 0 and sv != 0 and (abs(pv - (sv * 1000)) <= 2.0 or abs(sv - round(pv / 1000.0, 2)) < 0.01): is_match = True
                            elif pv != 0 and sv != 0 and (abs(sv - (pv * 1000000)) <= 2000.0 or abs(pv - round(sv / 1000000.0, 2)) < 0.01): is_match = True
                            elif pv != 0 and sv != 0 and (abs(pv - (sv * 1000000)) <= 2000.0 or abs(sv - round(pv / 1000000.0, 2)) < 0.01): is_match = True
                            elif abs(pv - round(sv)) < 0.0001 and abs(pv - sv) < 1.0:
                                is_match = True
                                is_tol = True
                            elif abs(sv - round(pv)) < 0.0001 and abs(pv - sv) < 1.0:
                                is_match = True
                                is_tol = True
                            elif rounding_noise(pv, sv):
                                is_match = True
                                is_tol = True
                            elif tolerance > 0 and abs(pv - sv) <= tolerance:
                                is_match = True
                                is_tol = True

                            p_disp = int(pv) if pv.is_integer() else pv
                            src_disp = int(sv) if sv.is_integer() else sv

                            if is_match:
                                m_item = {"wilayah": s_disp, "section": sec_b, "metric": base_h, "val": p_disp, "pdf_val": p_disp, "src_val": src_disp}
                                if is_tol:
                                    m_item["is_tolerance_match"] = True
                                    m_item["tolerance_diff"] = round(diff_val, 4)
                                results["matches"].append(m_item)
                            else:
                                note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})"
                                results["diffs"].append({
                                    "wilayah": s_disp, "section": sec_b, "metric": base_h,
                                    "pdf_val": p_disp, "src_val": src_disp,
                                    "diff": diff_val, "note": note, "type": "replace"
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
                            p_disp = int(pv) if pv.is_integer() else pv
                            results["pdf_only"].append({
                                "wilayah": disp, "section": sec_b, "metric": f"{base_h} ({hy})",
                                "values": [p_disp],
                                "note": f"Data historis ({hy}) tercetak di buku PDF, tidak dimuat di spreadsheet sumber {target_year}",
                                "type": "delete"
                            })

        else:
            src_title = ' '.join(str(c) for c in (source_data[0] if source_data else []))
            title_years = re.findall(r'\b(20\d{2})\b', src_title)
            header_years = re.findall(r'\b(20\d{2})\b', ' '.join(headers))
            target_year = header_years[0] if header_years else (title_years[-1] if title_years else None)

            hist_pdf_years_reg = {}

            def get_clean_rows_dict(data_list, is_pdf=False, expected_len=None, target_year=None):
                out = {}
                is_k = False
                brebes_seen = False
                is_prov = False
                common_first_data_col = None
                active_cols = detect_active_data_cols(data_list) if not is_pdf else []

                # Check if PDF table has year sub-rows (e.g. 2023 and 2024 stacked per regency)
                has_year_subrows = False
                year_col_idx = -1
                if is_pdf and data_list:
                    subrow_pairs = 0
                    cur_chk_w = None
                    cur_chk_y = None
                    for r in data_list[:35]:
                        if not isinstance(r, list) or len(r) < 3 or is_header_or_metadata_row(r): continue
                        lbl_parts = [str(c).strip() for c in r[:3] if c is not None and str(c).strip() and str(c).strip().lower() != 'none' and not re.search(r'\b(?:19|20)\d{2}\b', str(c))]
                        lbl_txt = ' '.join(lbl_parts)
                        found_w = find_wilayah_with_type(lbl_txt)

                        r_y = None
                        r_y_idx = -1
                        for idx, c in enumerate(r[:4]):
                            c_clean = re.sub(r'^[a-zA-Z\s\.\/:\n\r]+', '', str(c)).strip()
                            if re.match(r'^(?:19|20)\d{2}$', c_clean):
                                r_y = c_clean
                                r_y_idx = idx
                                break

                        if found_w != 'Umum':
                            cur_chk_w = found_w
                            cur_chk_y = r_y
                            if year_col_idx == -1 and r_y_idx >= 0: year_col_idx = r_y_idx
                        elif not lbl_txt and cur_chk_w and r_y and r_y != cur_chk_y:
                            subrow_pairs += 1
                            if year_col_idx == -1 and r_y_idx >= 0: year_col_idx = r_y_idx

                    if subrow_pairs >= 2 and year_col_idx >= 0:
                        has_year_subrows = True

                if is_pdf and has_year_subrows:
                    cur_w = None
                    by_w_year = {}
                    for r in data_list:
                        if not isinstance(r, list) or len(r) < 3: continue
                        txt = ' '.join(str(c) for c in r).lower()
                        if 'kota/municipality' in txt: 
                            is_k = True
                            brebes_seen = True
                        elif 'kabupaten/regency' in txt: 
                            is_k = False
                            brebes_seen = False
                        elif 'provinsi/province' in txt or (txt.strip().startswith('provinsi') and '/' in txt and not any(parse_num(c, is_pdf=True) for c in r)):
                            is_prov = True
                            continue
                        if is_header_or_metadata_row(r): continue

                        c0 = str(r[0]).strip().rstrip('.')
                        if c0.isdigit():
                            num0 = int(c0)
                            if num0 > 6: is_k = False
                            elif brebes_seen and 1 <= num0 <= 6: is_k = True

                        label_parts = [str(c).strip() for c in r[:year_col_idx] if str(c).strip() and parse_num(str(c).strip(), is_pdf=True) is None]
                        row_label = ' '.join(label_parts) if label_parts else ''
                        
                        if is_prov and (c0 == '1' or 'jawa tengah' in row_label.lower() or 'provinsi' in row_label.lower()):
                            cur_w = 'Provinsi Jawa Tengah'
                            is_prov = False
                        elif 'provinsi jawa tengah' in row_label.lower() or 'pemprov' in row_label.lower():
                            cur_w = 'Provinsi Jawa Tengah'
                            is_prov = False
                        elif any(k in row_label.lower() for k in ['jumlah', 'total']) and not any(k in row_label.lower() for k in ['curah', 'hari', 'hujan', 'penduduk']):
                            cur_w = 'Total Jawa Tengah'
                            is_prov = False
                        else:
                            prov_code, prov_name = resolve_provinsi_entity(row_label)
                            if prov_name:
                                cur_w = prov_name
                                is_prov = False
                            elif row_label:
                                found_w = find_wilayah_with_type(row_label, is_kota=is_k)
                                if found_w != "Umum":
                                    cur_w = found_w
                            elif any('jawa tengah' in str(c).lower() for c in r[:year_col_idx+1]):
                                cur_w = "Total Jawa Tengah"

                        if cur_w and ('Brebes' in cur_w or cur_w == 'Kota Tegal'):
                            brebes_seen = True

                        row_year = None
                        for c in r[:year_col_idx+1]:
                            m_y = re.search(r'\b((?:19|20)\d{2})\b', str(c))
                            if m_y:
                                row_year = m_y.group(1)
                                break

                        if cur_w and row_year:
                            nums = []
                            for c in r[year_col_idx+1:]:
                                v = parse_num(c, is_pdf=True)
                                if v is not None: nums.append(v)
                            if cur_w not in by_w_year: by_w_year[cur_w] = {}
                            by_w_year[cur_w][row_year] = nums

                    all_years = sorted({y for y_dict in by_w_year.values() for y in y_dict})
                    active_y = target_year if (target_year and any(target_year in y_dict for y_dict in by_w_year.values())) else (all_years[-1] if all_years else None)
                    for w_entity, y_dict in by_w_year.items():
                        out[w_entity] = y_dict.get(active_y, [])
                        for y_hist, nums in y_dict.items():
                            if y_hist != active_y:
                                if y_hist not in hist_pdf_years_reg: hist_pdf_years_reg[y_hist] = {}
                                hist_pdf_years_reg[y_hist][w_entity] = nums
                    return out

                for r in data_list:
                    if not isinstance(r, list): continue
                    txt = ' '.join(str(c) for c in r).lower()
                    if is_pdf and any(k in txt for k in ['tabel', 'table']):
                        m_next = re.search(r'tabel\s+(\d+\.\d+\.\d+)', txt)
                        if m_next and m_next.group(1).strip('.') != target_table.strip('.'):
                            break
                    if 'kota/municipality' in txt: 
                        is_k = True
                        brebes_seen = True
                    elif 'kabupaten/regency' in txt: 
                        is_k = False
                        brebes_seen = False
                    elif 'provinsi/province' in txt or (txt.strip().startswith('provinsi') and '/' in txt and not any(parse_num(c) for c in r)):
                        is_prov = True
                        continue
                    if is_header_or_metadata_row(r): continue
                    
                    c0 = str(r[0]).strip().rstrip('.')
                    if c0.isdigit():
                        num0 = int(c0)
                        if num0 > 6: is_k = False
                        elif brebes_seen and 1 <= num0 <= 6: is_k = True
                    
                    label_parts = [str(c).strip() for c in r[:3] if str(c).strip() and parse_num(str(c).strip(), is_pdf=is_pdf) is None]
                    row_label = ' '.join(label_parts) if label_parts else ' '.join(str(c) for c in r[:2])
                    
                    if is_prov and (c0 == '1' or 'jawa tengah' in row_label.lower() or 'provinsi' in row_label.lower()):
                        w = 'Provinsi Jawa Tengah'
                        is_prov = False
                    elif 'provinsi jawa tengah' in row_label.lower() or 'pemprov' in row_label.lower():
                        w = 'Provinsi Jawa Tengah'
                        is_prov = False
                    elif any(k in row_label.lower() for k in ['jumlah', 'total']) and not any(k in row_label.lower() for k in ['curah', 'hari', 'hujan', 'penduduk', 'kecamatan', 'desa', 'kelurahan', 'tps', 'sekolah', 'guru', 'murid', 'kabupaten', 'kota']):
                        w = 'Total Jawa Tengah'
                        is_prov = False
                    else:
                        prov_code, prov_name = resolve_provinsi_entity(row_label)
                        if prov_name:
                            w = prov_name
                            is_prov = False
                        else:
                            w = find_wilayah_with_type(row_label, is_kota=is_k)
                            if w == "Umum":
                                if any('jawa tengah' in str(c).lower() for c in r):
                                    w = "Total Jawa Tengah"
                                elif brebes_seen and is_k and (not label_parts or 'total' in txt or 'jumlah' in txt) and any(parse_num(str(c).strip(), is_pdf=is_pdf) is not None for c in r):
                                    w = "Total Jawa Tengah"
                                else:
                                    continue
                    
                    if 'Brebes' in w or w == 'Kota Tegal': brebes_seen = True
                        
                    first_idx = next((i for i, c in enumerate(r) if str(c).strip()), -1)
                    raw_w = w.replace('Kab. ', '').replace('Kota ', '')
                    nums = []
                    
                    if is_pdf:
                        for idx, c in enumerate(r):
                            c_str = str(c).strip()
                            if not c_str: continue
                            if idx == first_idx and c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200: continue
                            if c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                next_val = next((str(r[k]).strip() for k in range(idx+1, len(r)) if str(r[k]).strip()), '')
                                if any(k.lower() in next_val.lower() for k in [raw_w]) or any(k.lower() in next_val.lower() for k in KAB_KOTA_JATENG):
                                    continue
                            if any(k.lower() in c_str.lower() for k in [raw_w]): continue
                            if idx <= 3 and re.match(r'^33\.\d{2}$', c_str): continue
                                
                            val = parse_num(c_str, is_pdf=True)
                            if val is not None: nums.append(val)
                    else:
                        if active_cols:
                            raw_vals = [parse_num(str(r[c]).strip(), is_pdf=False) for c in active_cols if c < len(r)]
                            if not any(v is not None for v in raw_vals):
                                continue
                            for c_idx in active_cols:
                                c_str = str(r[c_idx]).strip() if c_idx < len(r) else ''
                                v = parse_num(c_str, is_pdf=False)
                                nums.append(0.0 if v is None else v)
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
                            is_side_by_side = (sum(1 for c in r if raw_w.lower() in str(c).lower() and len(raw_w) > 3) >= 2)
                            
                            if is_side_by_side or expected_len is None:
                                for idx, c in enumerate(r):
                                    c_str = str(c).strip()
                                    if not c_str: continue
                                    if idx == first_idx and c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200: continue
                                    if c_str.rstrip('.').isdigit() and int(c_str.rstrip('.')) <= 200:
                                        next_val = next((str(r[k]).strip() for k in range(idx+1, len(r)) if str(r[k]).strip()), '')
                                        if any(k.lower() in next_val.lower() for k in [raw_w]) or any(k.lower() in next_val.lower() for k in KAB_KOTA_JATENG):
                                            continue
                                    if any(k.lower() in c_str.lower() for k in [raw_w]): continue
                                    if idx <= 3 and re.match(r'^33\.\d{2}$', c_str): continue
                                    val = parse_num(c_str, is_pdf=False)
                                    if val is not None: nums.append(val)
                            else:
                                row_data = r[first_data_col : first_data_col + expected_len]
                                for c in row_data:
                                    c_str = str(c).strip()
                                    if not c_str: nums.append(0.0)
                                    else:
                                        v = parse_num(c_str, is_pdf=False)
                                        nums.append(0.0 if v is None else v)
                                    
                    if nums:
                        if w not in out: out[w] = []
                        out[w].extend(nums)
                return out
                
            pdf_dict = get_clean_rows_dict(rows, is_pdf=True, target_year=target_year)
            sample_p_nums = next((nums for nums in pdf_dict.values() if nums), [])
            p_len = len(sample_p_nums)
            src_dict = get_clean_rows_dict(source_data, is_pdf=False, expected_len=p_len)
            
            sample_s_nums = next((nums for nums in src_dict.values() if nums), [])
            s_len = len(sample_s_nums)
            
            pdf_headers = extract_pdf_headers(rows, expected_p_len=p_len, pdf_path=pdf_path, target_pages=target_pages, target_table=target_table)
            
            col_mapping, pdf_extra_indices, col_year_tags, final_metric_names = align_columns_semantically(
                headers, pdf_headers, src_dict, pdf_dict, target_year=target_year, table_title=src_title
            )

            empty_src_cols = set()
            for c_idx in range(s_len):
                if all(s_vals[c_idx] == 0.0 for s_vals in src_dict.values() if c_idx < len(s_vals)):
                    empty_src_cols.add(c_idx)

            reg_page_label = f"Halaman {target_pages[0]}" if len(target_pages) == 1 else (f"Halaman {target_pages[0]}-{target_pages[-1]}" if len(target_pages) > 1 else "")
            
            for w, p_nums in pdf_dict.items():
                s_nums = src_dict.get(w, [])
                if not s_nums:
                    if w == "Total Jawa Tengah":
                        for p_idx, pv in enumerate(p_nums):
                            p_disp = int(pv) if pv.is_integer() else pv
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Metrik #{p_idx + 1}"
                            results["pdf_only"].append({
                                "wilayah": w, "section": reg_page_label, "metric": base_m,
                                "values": [p_disp], "note": "Total Jawa Tengah tercetak di buku PDF, tidak dimuat di sheet sumber",
                                "type": "delete"
                            })
                    continue
                
                for s_idx, p_idx in col_mapping.items():
                    if p_idx >= len(p_nums) or s_idx >= len(s_nums): continue
                    pv = p_nums[p_idx]
                    sv = s_nums[s_idx]
                    metric_name = final_metric_names.get(s_idx, (headers[s_idx] if s_idx < len(headers) else f"Metrik #{s_idx + 1}"))
                    
                    is_match = False
                    is_tol = False
                    diff_val = round(sv - pv, 3)
                    abs_diff = int(abs(diff_val)) if abs(diff_val).is_integer() else abs(diff_val)

                    if pv == sv or abs(pv - sv) < 0.0001: is_match = True
                    elif pv != 0 and sv != 0 and (abs(sv - (pv * 1000)) <= 2.0 or abs(pv - round(sv / 1000.0, 2)) < 0.01): is_match = True
                    elif pv != 0 and sv != 0 and (abs(pv - (sv * 1000)) <= 2.0 or abs(sv - round(pv / 1000.0, 2)) < 0.01): is_match = True
                    elif pv != 0 and sv != 0 and (abs(sv - (pv * 1000000)) <= 2000.0 or abs(pv - round(sv / 1000000.0, 2)) < 0.01): is_match = True
                    elif pv != 0 and sv != 0 and (abs(pv - (sv * 1000000)) <= 2000.0 or abs(pv - round(sv / 1000000.0, 2)) < 0.01): is_match = True
                    elif abs(pv - round(sv)) < 0.0001 and abs(pv - sv) < 1.0:
                        is_match = True
                        is_tol = True
                    elif abs(sv - round(pv)) < 0.0001 and abs(pv - sv) < 1.0:
                        is_match = True
                        is_tol = True
                    elif rounding_noise(pv, sv):
                        is_match = True
                        is_tol = True
                    elif tolerance > 0 and abs(pv - sv) <= tolerance:
                        is_match = True
                        is_tol = True
                        
                    if is_match:
                        p_disp = int(pv) if pv.is_integer() else pv
                        s_disp = int(sv) if sv.is_integer() else sv
                        m_item = {"wilayah": w, "section": reg_page_label, "metric": metric_name, "val": p_disp, "pdf_val": p_disp, "src_val": s_disp}
                        if is_tol:
                            m_item["is_tolerance_match"] = True
                            m_item["tolerance_diff"] = round(diff_val, 4)
                        results["matches"].append(m_item)
                    else:
                        p_disp = int(pv) if pv.is_integer() else pv
                        if s_idx in empty_src_cols and sv == 0.0:
                            note = f"Di cetakan PDF tertulis {p_disp}, tetapi di database kolom ini kosong (tidak ada data di spreadsheet)"
                        else:
                            note = f"Selisih {abs_diff} (PDF {'lebih sedikit' if diff_val > 0 else 'lebih banyak'})"
                        results["diffs"].append({
                            "wilayah": w, "section": reg_page_label, "metric": metric_name, "pdf_val": p_disp,
                            "src_val": int(sv) if sv.is_integer() else sv, "diff": diff_val, "note": note, "type": "replace"
                        })
                        
                if pdf_extra_indices:
                    for p_idx in pdf_extra_indices:
                        if p_idx < len(p_nums):
                            pv = p_nums[p_idx]
                            pv_disp = int(pv) if pv.is_integer() else pv
                            y_tag = col_year_tags.get(p_idx, "")
                            base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Kolom Ekstra #{p_idx + 1}"
                            
                            m_name = f"{base_m} ({y_tag})" if (y_tag and y_tag not in base_m) else base_m
                            note = f"Data historis ({y_tag}) tercetak di buku PDF, tidak dimuat di spreadsheet sumber" if y_tag else "Tercetak di PDF tapi tidak ada kolomnya di spreadsheet"
                            
                            results["pdf_only"].append({
                                "wilayah": w, "section": reg_page_label, "metric": m_name, "values": [pv_disp], "note": note, "type": "delete"
                            })

                if hist_pdf_years_reg:
                    for hy, w_dict in hist_pdf_years_reg.items():
                        if w in w_dict:
                            for p_idx, pv in enumerate(w_dict[w]):
                                pv_disp = int(pv) if pv.is_integer() else pv
                                base_m = pdf_headers[p_idx] if p_idx < len(pdf_headers) else f"Metrik #{p_idx + 1}"
                                results["pdf_only"].append({
                                    "wilayah": w, "section": reg_page_label, "metric": f"{base_m} ({hy})",
                                    "values": [pv_disp], "note": f"Data historis ({hy}) tercetak di buku PDF, tidak dimuat di spreadsheet sumber {target_year or ''}".strip(),
                                    "type": "delete"
                                })

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