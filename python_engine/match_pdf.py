#Untuk mencocokkan nomor dan judul tabel pdf dengan yang ada di database 
#Mencari dan memetakan identitas tabel (Nomor, Judul, dan Halaman).
import re, sys, json, os

if sys.platform == "win32":
    try:
        import io
        if hasattr(sys.stdout, 'buffer'):
            sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        if hasattr(sys.stderr, 'buffer'):
            sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except Exception:
        pass

import pymupdf
import numpy as np
from rapidfuzz import fuzz
from scipy.optimize import linear_sum_assignment

NUM_AT_LINE_START = re.compile(r'(?m)^(\d{1,2}\.\d{1,2}(?:\.\d{1,3})?)\s*[\t]')

# ---------------------------------------------------------------- 1. Daftar Tabel
def find_toc_span(doc, scan=120):
    first = last = None
    for i in range(min(scan, len(doc))):
        t = doc[i].get_text()
        if first is None and re.search(r'DAFTAR TABEL\s*/\s*LIST OF TABLES', t): first = i
        if first is not None and re.search(r'DAFTAR GAMBAR\s*/\s*LIST OF FIGURES', t):
            last = i - 1; break
    return first, last

def parse_toc(doc):
    first, last = find_toc_span(doc)
    if first is None: raise RuntimeError("Daftar Tabel tidak ditemukan")
    txt = "\n".join(doc[i].get_text() for i in range(first, last + 1))
    txt = re.sub(r'(?m)^(Halaman|Page|Tabel|Table|https://jateng\.bps\.go\.id)\s*$', '', txt)
    txt = re.sub(r'(?m)^[ivxlc]+\s*$', '', txt)
    ms = list(NUM_AT_LINE_START.finditer(txt))
    out = {}
    for i, m in enumerate(ms):
        num = m.group(1)
        blk = txt[m.end(): ms[i+1].start() if i+1 < len(ms) else len(txt)].rstrip()
        blk_clean = re.sub(r'\n\s*\d+\.\t.*$', '', blk, flags=re.S)
        
        pm = re.search(r'(?:\.{1,}|\s{2,}|\t)\s*(\d+)\s*$', blk_clean, re.S)
        pr_page = None
        if pm:
            pr_page = int(pm.group(1))
            title_part = blk_clean[:pm.start()]
        else:
            lines = [l.strip() for l in blk_clean.splitlines() if l.strip()]
            if lines and lines[-1].isdigit():
                pr_page = int(lines[-1])
                title_part = '\n'.join(lines[:-1])
            else:
                title_part = blk_clean
                
        parts = re.split(r'\n\s*\t\s*\n', title_part, maxsplit=1)
        title_id = re.sub(r'\s+', ' ', re.sub(r'\.{2,}.*$', '', parts[0])).strip()
        title_en = re.sub(r'\s+', ' ', re.sub(r'\.{2,}.*$', '', parts[1])).strip() if len(parts) > 1 else ''
        
        # Koreksi salah ketik nomor tabel di Daftar Tabel cetakan BPS: 5.1 -> 2.5.1
        if num == '5.1':
            if 'akta pencatatan sipil' in title_id.lower() or pr_page == 111:
                num = '2.5.1'
            elif pr_page == 538:  # Gambar yang terselip di Daftar Tabel
                continue
            
        # Abaikan judul bagian/bab (section header seperti 1.1 KEADAAN GEOGRAFI)
        if num.count('.') == 1 and title_id.isupper():
            continue
        
        out[num] = {
            "judul_pdf": title_id,
            "judul_en": title_en,
            "printed_page": pr_page
        }
    return out

def _head(doc, p, _c={}):
    if (id(doc), p) not in _c: _c[(id(doc), p)] = doc[p].get_text()[:500]
    return _c[(id(doc), p)]

def detect_offset(doc, toc, sample=40):
    """Selisih (nomor halaman fisik PDF 1-based - nomor halaman cetak), dicari lewat nomor tabel."""
    from collections import Counter
    votes = Counter()
    sample_keys = [k for k in list(toc.items()) if k[1].get("printed_page")][::max(1, len(toc)//sample)]
    for k, v in sample_keys:
        pr = v["printed_page"]
        pat = re.compile(rf'(?:Tabel|Table)?\s*{re.escape(k)}\b', re.IGNORECASE)
        for off in range(10, 130):
            p_0based = pr + off - 1
            if 0 <= p_0based < len(doc):
                txt = doc[p_0based].get_text()[:400]
                if 'Lanjutan' in txt or 'Continued' in txt:
                    continue
                if pat.search(txt):
                    votes[off] += 1
                    break
    if votes:
        return votes.most_common(1)[0][0]
    return 70

def build_index(pdf_path, db_tables=None):
    doc = pymupdf.open(pdf_path)
    toc = parse_toc(doc)
    off_1based = detect_offset(doc, toc)
    
    # Pre-extract page headers for fast direct PDF scanning
    page_heads = [p.get_text()[:400] for p in doc]
    
    def find_pages_direct(t_num):
        pat = re.compile(rf'(?:(?:Tabel|Table|Lanjutan\s+Tabel|Continued\s+Table)\s*{re.escape(t_num)}\b|^\s*{re.escape(t_num)}\s*[\t])', re.IGNORECASE | re.MULTILINE)
        p_list = []
        for idx, ph in enumerate(page_heads):
            if idx < 65: continue  # skip prelims/TOC
            if pat.search(ph):
                p_list.append(idx + 1)
        if not p_list: return []
        groups = []
        curr = [p_list[0]]
        for p in p_list[1:]:
            if p == curr[-1] + 1: curr.append(p)
            else: groups.append(curr); curr = [p]
        groups.append(curr)
        return max(groups, key=len)

    # Ensure any table in db_tables with nomor_tabel is in toc
    if db_tables:
        for r in db_tables:
            no = r.get("nomor_tabel", "").strip()
            if no and no not in toc:
                p_direct = find_pages_direct(no)
                if p_direct:
                    toc[no] = {
                        "judul_pdf": r.get("judul_tabel", ""),
                        "judul_en": "",
                        "printed_page": p_direct[0] - off_1based,
                        "pages": p_direct
                    }

    # Resolve start page for every table and sort strictly by physical start page
    table_starts = []
    for k, v in toc.items():
        pr = v.get("printed_page")
        if pr is not None:
            st = pr + off_1based
        else:
            p_dir = find_pages_direct(k)
            st = p_dir[0] if p_dir else None
        if st:
            table_starts.append((k, st, v))

    table_starts.sort(key=lambda x: x[1])

    # Assign strictly non-overlapping page blocks
    for i, (k, st, v) in enumerate(table_starts):
        nxt_st = table_starts[i+1][1] if i + 1 < len(table_starts) else st + 3
        p_range = list(range(st, min(nxt_st, st + 8)))
        if not p_range: p_range = [st]
        toc[k]["pages"] = p_range

    for k in toc:
        if "pages" not in toc[k]:
            toc[k]["pages"] = []

    return toc

# ---------------------------------------------------------------- 2. Pencocokan judul
_MONTH = r'januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember'
def normalize_title(t):
    t = (t or '').lower()
    t = re.sub(r'^\s*tabel\s+\d+(\.\d+)*\s*', '', t)
    t = t.replace('sertifikat', 'sertipikat').replace('ditebitkan', 'diterbitkan').replace('negari', 'negeri')
    t = re.sub(r'(?<=[a-z])\d\b', '', t)                      # catatan kaki: "Desa1" -> "desa"
    t = re.sub(rf'\b(19|20)\d{{2}}\b|\b({_MONTH})\b', ' ', t)
    t = re.sub(r'\b(di\s+)?(provinsi\s+)?jawa\s+(provinsi\s+)?tengah\b|\bprovinsi\b|\bpemerintah\b', ' ', t)
    t = re.sub(r'\b(dan|serta|atau|di|yang|menurut|per|dengan)\b', ' ', t)
    t = t.encode('ascii', 'ignore').decode()
    return re.sub(r'\s+', ' ', re.sub(r'[^a-z0-9\s]', ' ', t)).strip()

def _score(a, b):
    return 0.5*fuzz.token_sort_ratio(a, b) + 0.3*fuzz.token_set_ratio(a, b) + 0.2*fuzz.ratio(a, b)

def match_tables(toc, db_tables, min_score=65):
    matched, missing, used = [], [], set()
    unmatched_db = []
    
    # PASS 1: Pencocokan eksak berdasarkan nomor_tabel
    for item in db_tables:
        t_num = item.get("nomor_tabel", "").strip()
        db_id = str(item.get("id_db", item.get("id", ""))).strip()
        title = item.get("judul_tabel", item.get("judul_ind", ""))
        
        if t_num and t_num in toc:
            used.add(t_num)
            matched.append({
                "nomor_tabel": t_num,
                "id_db": db_id,
                "judul_db": title,
                "judul_pdf": toc[t_num]["judul_pdf"],
                "pages": toc[t_num]["pages"],
                "similarity": 100.0
            })
        else:
            unmatched_db.append((item, db_id, title))
            
    # PASS 2: Pencocokan judul fuzzy untuk tabel yang nomor_tabel-nya kosong/belum cocok
    avail_toc = [k for k in toc if k not in used]
    still_missing = []
    
    if unmatched_db and avail_toc:
        try:
            # Optimal Hungarian Assignment (Maximum Bipartite Matching)
            cost_matrix = []
            score_matrix = []
            for item, db_id, title in unmatched_db:
                d_norm = normalize_title(title)
                r_scores = []
                r_costs = []
                for k in avail_toc:
                    sc = _score(d_norm, normalize_title(toc[k]["judul_pdf"]))
                    r_scores.append(sc)
                    r_costs.append(100.0 - sc)
                score_matrix.append(r_scores)
                cost_matrix.append(r_costs)
                
            row_ind, col_ind = linear_sum_assignment(np.array(cost_matrix))
            matched_db_indices = set()
            for r_idx, c_idx in zip(row_ind, col_ind):
                best_score = score_matrix[r_idx][c_idx]
                item, db_id, title = unmatched_db[r_idx]
                t_num = item.get("nomor_tabel", "").strip()
                best_k = avail_toc[c_idx]
                
                if best_score >= min_score:
                    used.add(best_k)
                    matched_db_indices.add(r_idx)
                    matched.append({
                        "nomor_tabel": best_k,
                        "id_db": db_id,
                        "judul_db": title,
                        "judul_pdf": toc[best_k]["judul_pdf"],
                        "pages": toc[best_k]["pages"],
                        "similarity": round(float(best_score), 1)
                    })
                else:
                    still_missing.append({
                        "nomor_tabel": t_num or "-",
                        "judul_db": title,
                        "judul_pdf": "-",
                        "similarity": round(float(best_score), 1),
                        "reason": f"Tidak ada kecocokan di PDF (skor tertinggi {round(float(best_score), 1)}%)"
                    })
                    
            for idx, (item, db_id, title) in enumerate(unmatched_db):
                if idx not in matched_db_indices and idx not in row_ind:
                    t_num = item.get("nomor_tabel", "").strip()
                    still_missing.append({
                        "nomor_tabel": t_num or "-",
                        "judul_db": title,
                        "judul_pdf": "-",
                        "similarity": 0.0,
                        "reason": "Tidak ada kecocokan di PDF"
                    })
        except Exception:
            # Fallback ke greedy jika linear_sum_assignment tidak tersedia/error
            for item, db_id, title in unmatched_db:
                t_num = item.get("nomor_tabel", "").strip()
                d_norm = normalize_title(title)
                best_k, best_score = None, 0
                for k in avail_toc:
                    score = _score(d_norm, normalize_title(toc[k]["judul_pdf"]))
                    if score > best_score:
                        best_score = score
                        best_k = k
                if best_score >= min_score and best_k and best_k not in used:
                    used.add(best_k)
                    avail_toc.remove(best_k)
                    matched.append({
                        "nomor_tabel": best_k,
                        "id_db": db_id,
                        "judul_db": title,
                        "judul_pdf": toc[best_k]["judul_pdf"],
                        "pages": toc[best_k]["pages"],
                        "similarity": round(float(best_score), 1)
                    })
                else:
                    still_missing.append({
                        "nomor_tabel": t_num or "-",
                        "judul_db": title,
                        "judul_pdf": "-",
                        "similarity": round(float(best_score), 1),
                        "reason": f"Tidak ada kecocokan di PDF (skor tertinggi {round(float(best_score), 1)}%)"
                    })
            
    unreg = [{"nomor_tabel": k, "judul_pdf": toc[k]["judul_pdf"],
              "reason": "Ada di PDF, tidak ada judul yang mirip di Database"} for k in toc if k not in used]
              
    sk = lambda x: [int(p) for p in re.findall(r'\d+', x["nomor_tabel"])]
    for L in (matched, still_missing, unreg):
        try: L.sort(key=sk)
        except Exception: pass
        
    return {"status": "success", "matched_in_pdf": matched, "missing_in_pdf": still_missing, "unregistered_in_db": unreg}


def main():
    pdf_path, db_json = sys.argv[1], sys.argv[2]
    with open(db_json, 'r', encoding='utf-8') as f:
        db = json.load(f)
        
    toc = build_index(pdf_path, db_tables=db)
    with open(pdf_path + ".index.json", "w", encoding="utf-8") as f:
        json.dump({k: {"pages": v["pages"], "display_title": v["judul_pdf"]} for k, v in toc.items()}, f, ensure_ascii=False)
    
    matched = match_tables(toc, db)
    
    # Save to both standard names with UTF-8 encoding
    m_year = re.search(r'(\d{4})', pdf_path)
    ta = m_year.group(1) if m_year else "2026"
    up_dir = os.path.dirname(db_json)
    
    dda_matched_file = os.path.join(up_dir, f"dda_matched_{ta}.json")
    with open(dda_matched_file, 'w', encoding='utf-8') as f:
        json.dump(matched, f, ensure_ascii=False, indent=2)
        
    matched_tables_file = os.path.join(up_dir, f"matched_tables_{ta}.json")
    with open(matched_tables_file, 'w', encoding='utf-8') as f:
        json.dump(matched, f, ensure_ascii=False, indent=2)
    
    print(json.dumps(matched, ensure_ascii=False))

if __name__ == "__main__":
    main()