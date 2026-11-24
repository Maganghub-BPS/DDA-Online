#Untuk mencocokkan nomor dan judul tabel pdf dengan yang ada di database 
#Mencari dan memetakan identitas tabel (Nomor, Judul, dan Halaman).
import re, sys, json, os

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

from datetime import datetime
try:
    import pymupdf
except ImportError:
    import fitz as pymupdf
import numpy as np
from rapidfuzz import fuzz
from scipy.optimize import linear_sum_assignment

NUM_AT_LINE_START = re.compile(r'(?m)^(\d{1,2}\.\d{1,2}(?:\.\d{1,3})?)(?:\s*[\t]|[ \t]{2,}|[ \t]+(?=[A-Z][A-Za-z]))')

# ---------------------------------------------------------------- 0. Cover Year Detection
def detect_pdf_publication_year(doc_or_path, filename=None):
    """Mendeteksi tahun publikasi buku DDA dari halaman cover/awal PDF."""
    close_after = False
    if isinstance(doc_or_path, str):
        doc = pymupdf.open(doc_or_path)
        close_after = True
    else:
        doc = doc_or_path
    try:
        front_txt = "\n".join(doc[i].get_text() for i in range(min(5, len(doc))))
        patterns = [
            r'(?:Provinsi\s+Jawa\s+Tengah\s+Dalam\s+Angka|Jawa\s+Tengah\s+(?:Province\s+)?in\s+Figures)\s*[:\-]?\s*(20\d{2})',
            r'D\s*A\s*L\s*A\s*M\s+A\s*N\s*G\s*K\s*A[\s\S]{0,120}?\b(20\d{2})\b',
            r'Volume\s+[xxivxlcdm\d]+\s*,\s*(20\d{2})\b',
            r'IN\s+FIGURES\s+(20\d{2})\b',
        ]
        for pat in patterns:
            m = re.search(pat, front_txt, re.IGNORECASE)
            if m:
                yr = int(m.group(1))
                if 2000 <= yr <= 2100:
                    return str(yr)
        for source_name in ([filename] if filename else []) + ([doc_or_path] if isinstance(doc_or_path, str) else []):
            yrs = re.findall(r'\b(20\d{2})\b', os.path.basename(source_name))
            if yrs:
                return yrs[-1]
    finally:
        if close_after:
            doc.close()
    return None

# ---------------------------------------------------------------- 1. Daftar Tabel
def find_toc_span(doc, scan=140):
    first = last = None
    limit = min(scan, len(doc))
    for i in range(limit):
        t = doc[i].get_text()
        if first is None and re.search(r'DAFTAR\s+TABEL(?:\s*/\s*LIST\s+OF\s+TABLES)?', t, re.IGNORECASE):
            first = i
            continue
        if first is not None and (
            re.search(r'DAFTAR\s+GAMBAR(?:\s*/\s*LIST\s+OF\s+FIGURES)?', t, re.IGNORECASE)
            or re.search(r'PENJELASAN\s+TEKNIS|TECHNICAL\s+NOTES|^\s*BAB\s+(?:I|1)\b', t, re.IGNORECASE | re.MULTILINE)
        ):
            last = i - 1
            break
    if first is not None and last is None:
        last = min(len(doc) - 1, first + 35)
    return first, last

def parse_toc(doc):
    first, last = find_toc_span(doc)
    if first is None or last is None or last < first:
        raise RuntimeError("Daftar Tabel tidak ditemukan")
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
        
        # Abaikan entri non-tabel (gambar/peta/grafik/diagram) yang kadang bocor ke Daftar Tabel
        if num.count('.') == 1 and re.match(r'^(?:gambar|peta|grafik|diagram|figure|map|chart)\b', title_id.lower()):
            continue

        # Abaikan judul bagian/bab (section header seperti 1.1 KEADAAN GEOGRAFI)
        if num.count('.') == 1 and title_id.isupper():
            continue

        # Koreksi generik salah ketik nomor 2-segmen (mis. X.1 yang seharusnya C.X.1) berdasarkan konteks nomor tetangga
        if num.count('.') == 1 and i + 1 < len(ms):
            next_num = ms[i + 1].group(1)
            prev_num = ms[i - 1].group(1) if i > 0 else ''
            m_next = re.match(r'^(\d{1,2})\.' + re.escape(num.split('.')[0]) + r'\.(\d{1,3})$', next_num)
            if m_next and int(num.split('.')[1]) + 1 == int(m_next.group(2)):
                inferred = f"{m_next.group(1)}.{num}"
                if not prev_num or prev_num.startswith(f"{m_next.group(1)}."):
                    num = inferred

        out[num] = {
            "judul_pdf": title_id,
            "judul_en": title_en,
            "printed_page": pr_page
        }
    return out


def detect_offset(doc, toc, sample=40, spatial_heads=None, min_idx=0):
    """Selisih (nomor halaman fisik PDF 1-based - nomor halaman cetak), dicari lewat nomor tabel."""
    from collections import Counter
    votes = Counter()
    sample_keys = [k for k in list(toc.items()) if k[1].get("printed_page")][::max(1, len(toc)//sample)]
    for k, v in sample_keys:
        pr = v["printed_page"]
        pat = re.compile(rf'(?:Tabel|Table)?\s*{re.escape(k)}\b', re.IGNORECASE)
        for off in range(0, 150):
            p_0based = pr + off - 1
            if min_idx <= p_0based < len(doc):
                txt = spatial_heads[p_0based] if (spatial_heads and p_0based < len(spatial_heads)) else doc[p_0based].get_text()[:400]
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
    _, toc_last = find_toc_span(doc)
    body_start_idx = (toc_last + 1) if toc_last is not None else 30
    toc = parse_toc(doc)

    # Pre-extract spatial headers (top 40% sorted by Y, X) for fast and precise table detection
    spatial_heads = []
    for p in doc:
        max_y = (p.rect.height or 842) * 0.40
        blocks = [b for b in p.get_text("blocks") if b[1] < max_y and len(b) > 4 and isinstance(b[4], str)]
        blocks.sort(key=lambda b: (round(b[1] / 15.0), b[0]))
        spatial_heads.append("\n".join(b[4].strip() for b in blocks))

    off_1based = detect_offset(doc, toc, spatial_heads=spatial_heads, min_idx=body_start_idx)

    pat_main_t = re.compile(
        r'(?:(?<!Lanjutan\s)(?<!Continued\s)(?:Tabel|Table)(?:\s*/\s*(?:Tabel|Table))?[\r\n\t ]+(\d{1,2}\.\d{1,2}(?:\.\d{1,3})?)\b'
        r'|^\s*(\d{1,2}\.\d{1,2}(?:\.\d{1,3})?)\s*(?:[\t ]|[\r\n]+(?:Tabel|Table)\b))',
        re.IGNORECASE | re.MULTILINE
    )
    pat_lanj_t = re.compile(
        r'(?:(?:Lanjutan\s+Tabel|Continued\s+Table)\s*[\r\n\t ]+(\d{1,2}\.\d{1,2}(?:\.\d{1,3})?)\b)',
        re.IGNORECASE | re.MULTILINE
    )

    def _page_has_table_main(p_1based, t_num):
        if p_1based <= body_start_idx or p_1based > len(doc):
            return False
        sh = spatial_heads[p_1based - 1]
        if pat_lanj_t.search(sh):
            for m_l in pat_lanj_t.finditer(sh):
                if m_l.group(1) == t_num:
                    return False
        for m in pat_main_t.finditer(sh):
            num_found = m.group(1) or m.group(2)
            if num_found == t_num:
                return True
        return False

    def _page_has_table_lanj(p_1based, t_num):
        if p_1based <= body_start_idx or p_1based > len(doc):
            return False
        sh = spatial_heads[p_1based - 1]
        for m in pat_lanj_t.finditer(sh):
            if m.group(1) == t_num:
                return True
        return False

    def find_pages_direct(t_num):
        pat = re.compile(
            rf'(?:(?:Tabel|Table|Lanjutan\s+Tabel|Continued\s+Table)\s*{re.escape(t_num)}\b|^\s*{re.escape(t_num)}\s*(?:[\t ]|[\r\n]+(?:Tabel|Table)\b))',
            re.IGNORECASE | re.MULTILINE
        )
        p_list = []
        for idx in range(body_start_idx, len(doc)):
            if pat.search(spatial_heads[idx]):
                p_list.append(idx + 1)
        if not p_list:
            return []
        groups = []
        curr = [p_list[0]]
        for p in p_list[1:]:
            if p == curr[-1] + 1:
                curr.append(p)
            else:
                groups.append(curr)
                curr = [p]
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

    # Resolve start page in TOC printed_page order using local offset tracking + spatial confirmation
    toc_ordered = sorted(
        toc.items(),
        key=lambda kv: (kv[1].get("printed_page") is None, kv[1].get("printed_page") or 999999)
    )
    table_starts = []
    running_offset = off_1based
    search_deltas = [0]
    for d in range(1, 15):
        search_deltas.extend([d, -d])

    for k, v in toc_ordered:
        pr = v.get("printed_page")
        st = None
        if pr is not None:
            cand_bases = [pr + running_offset]
            if running_offset != off_1based:
                cand_bases.append(pr + off_1based)

            for base_p in cand_bases:
                for delta in search_deltas:
                    cand_p = base_p + delta
                    if _page_has_table_main(cand_p, k):
                        st = cand_p
                        running_offset = cand_p - pr
                        break
                if st:
                    break

            # Fallback jika halaman estimasi menunjuk halaman Lanjutan
            if not st:
                for delta in search_deltas[:21]:
                    cand_p = (pr + running_offset) + delta
                    if _page_has_table_lanj(cand_p, k):
                        for back in range(1, 6):
                            if _page_has_table_main(cand_p - back, k):
                                st = cand_p - back
                                running_offset = st - pr
                                break
                        if st:
                            break

            if not st:
                p_dir = find_pages_direct(k)
                if p_dir:
                    st = p_dir[0]
                    running_offset = st - pr
                else:
                    st = pr + running_offset
        if not st:
            p_dir = find_pages_direct(k)
            st = p_dir[0] if p_dir else None
        if st:
            table_starts.append((k, st, v))

    table_starts.sort(key=lambda x: x[1])

    # Assign strictly non-overlapping page blocks with non-table boundary stopping
    for i, (k, st, v) in enumerate(table_starts):
        nxt_st = table_starts[i+1][1] if i + 1 < len(table_starts) else min(st + 3, len(doc) + 1)
        max_limit = min(nxt_st, st + 35)
        clean_pages = []
        for p in range(st, max_limit):
            if p == st:
                clean_pages.append(p)
                continue
            p_0based = p - 1
            if 0 <= p_0based < len(doc):
                sh = spatial_heads[p_0based]
                m_other = pat_main_t.search(sh)
                if m_other:
                    other_k = m_other.group(1) or m_other.group(2)
                    if other_k != k:
                        break
                txt_head = doc[p_0based].get_text()[:500].lower()
                is_gambar = bool(re.search(r'\b(?:gambar|figure)\s+\d', txt_head)) and not any(tbl in txt_head for tbl in ['tabel', 'table'])
                is_bab = bool(re.search(r'^\s*bab\s+[ivxlc\d]+', txt_head, re.MULTILINE) or re.search(r'\bchapter\s+[ivxlc\d]+', txt_head))
                is_teknis = ('penjelasan teknis' in txt_head or 'technical notes' in txt_head)
                if is_gambar or is_bab or is_teknis:
                    break
            clean_pages.append(p)
        if not clean_pages:
            clean_pages = [st]
        toc[k]["pages"] = clean_pages

    for k in toc:
        if "pages" not in toc[k]:
            toc[k]["pages"] = []

    return toc

# ---------------------------------------------------------------- 2. Pencocokan judul
_MONTH = r'januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember'
def normalize_title(t):
    t = (t or '').lower()
    t = re.sub(r'^\s*(?:tabel|table)\s+\d+(?:\.\d+)*\s*', '', t)
    t = t.replace('tingkatkepangkatan', 'tingkat kepangkatan')
    t = t.replace('sertifikat', 'sertipikat').replace('ditebitkan', 'diterbitkan').replace('negari', 'negeri')
    t = re.sub(r'(?<=[a-z])\d+\b', '', t)                      # catatan kaki: "Desa1" -> "desa"
    t = re.sub(r'\b(tahunan|bulanan|triwulanan|semesteran)\b', ' ', t)
    t = re.sub(r'\(\s*(rupiah|ribu\s+rupiah|juta\s+rupiah|persen|orang|ribu|unit|buah|hektar|ton|ekor|km2?|m2?|liter)\s*\)', ' ', t)
    t = re.sub(rf'\b(19|20)\d{{2}}\b|\b({_MONTH})\b', ' ', t)
    # Normalisasi sinonim umum penamaan tabel BPS agar perubahan redaksi tidak memutus jodoh
    t = re.sub(r'\bbanyaknya\b', 'jumlah', t)
    t = re.sub(r'\bkomoditi\b', 'komoditas', t)
    t = re.sub(r'\bsatuan\s+pendidikan\b', 'sekolah', t)
    t = re.sub(r'\bkepala\s+sekolah\s+(?:dan\s+)?pendidik\b|\bpendidik\b', 'guru', t)
    t = re.sub(r'\bpeserta\s+didik\b', 'murid', t)
    t = re.sub(r'\b(di\s+)?(provinsi\s+)?jawa\s+(provinsi\s+)?tengah\b|\bprovinsi\b|\bpemerintah\b', ' ', t)
    t = re.sub(r'\b(dan|serta|atau|di|yang|menurut|per|dengan)\b', ' ', t)
    t = t.encode('ascii', 'ignore').decode()
    return re.sub(r'\s+', ' ', re.sub(r'[^a-z0-9\s]', ' ', t)).strip()

def _extract_semantic_facets(raw_title):
    """Ekstrak dimensi, subjek, cakupan pemerintahan, area urban/rural, ukuran, & kelas objek dari judul mentah."""
    low = re.sub(r'\s+', ' ', (raw_title or '').lower().replace('tingkatkepangkatan', 'tingkat kepangkatan').replace('negari', 'negeri'))
    low = re.sub(r'(?<=[a-z])\d+\b', '', low)

    dims = set()
    if re.search(r'kabupaten\s*/\s*kota|kabupaten\s+kota|\bkab/kota\b', low):
        dims.add('kabupaten_kota')
    if re.search(r'dinas\s*/\s*instansi|instansi\s+pemerintah', low):
        dims.add('dinas_instansi')
    if re.search(r'\bjabatan\b', low):
        dims.add('jabatan')
    if re.search(r'tingkat\s+pendidikan|pendidikan\s+tertinggi', low):
        dims.add('pendidikan')
    if re.search(r'tingkat\s+kepangkatan|pangkat\s*/?\s*golongan', low):
        dims.add('kepangkatan')
    if re.search(r'(?:kelompok\s+)?umur\b', low):
        dims.add('umur')
    if re.search(r'kelompok\s+pengeluaran', low):
        dims.add('kelompok_pengeluaran')

    subj = None
    if re.search(r'pegawai\s+pemerintah\s+dengan\s+perjanjian\s+kerja|\bpppk\b', low):
        subj = 'pppk_paruh_waktu' if 'paruh waktu' in low else 'pppk'
    elif re.search(r'pegawai\s+negeri\s+sipil|\bpns\b', low):
        subj = 'pns'
    elif re.search(r'aparatur\s+sipil\s+negara|\basn\b', low):
        subj = 'asn'
    elif 'pekerja formal' in low:
        subj = 'pekerja_formal'
    elif 'pekerja informal' in low:
        subj = 'pekerja_informal'

    scope = None
    if subj in ('pns', 'pppk', 'pppk_paruh_waktu', 'asn'):
        if re.search(r'\bpemerintah\s+provinsi\b|\bpemprov\b', low):
            scope = 'pemprov'
        elif re.search(r'\bdi\s+provinsi\s+jawa\s+tengah\b|\bdi\s+jawa\s+tengah\b', low):
            scope = 'provinsi'

    areas = set()
    if 'perkotaan' in low:
        areas.add('perkotaan')
    if 'perdesaan' in low or 'pedesaan' in low:
        areas.add('perdesaan')

    measures = set()
    if re.search(r'\brata[\s\-]?rata\b', low):
        measures.add('rata_rata')
    if re.search(r'\bpersentase\b', low):
        measures.add('persentase')
    if re.search(r'\bindeks\b', low):
        measures.add('indeks')
    if re.search(r'(?:^|(?:dan|,)\s*)distribusi\s+(?:pengeluaran|pendapatan|tenaga|penduduk|lapangan|pdrb|ekonomi)\b', low):
        measures.add('distribusi')

    # Ukuran/metrik utama yang saling eksklusif (hanya dibandingkan bila KEDUA judul eksplisit menyebutnya)
    core_measures = set()
    if re.search(r'\bluas\s+panen\b', low):
        core_measures.add('luas_panen')
    elif re.search(r'\bluas\s+tanam\b', low):
        core_measures.add('luas_tanam')
    elif re.search(r'\bluas\s+(?:areal|lahan|hutan|wilayah|daerah)\b', low):
        core_measures.add('luas_areal')
    if re.search(r'\bproduktivitas\b', low):
        core_measures.add('produktivitas')
    elif re.search(r'\bproduksi\b', low):
        core_measures.add('produksi')
    if re.search(r'\blaju\s+pertumbuhan\b', low):
        core_measures.add('laju_pertumbuhan')
    if re.search(r'\bkepadatan\b', low):
        core_measures.add('kepadatan')
    if re.search(r'\brasio\b', low):
        core_measures.add('rasio')
    if re.search(r'\bpanjang\s+jalan\b', low):
        core_measures.add('panjang_jalan')
    if re.search(r'\bvolume\b', low):
        core_measures.add('volume')

    # Jenjang pendidikan (saling eksklusif bila keduanya menyebut jenjang eksplisit)
    edu_levels = set()
    if re.search(r'\btaman\s+kanak|\btk\b', low):
        edu_levels.add('tk')
    if re.search(r'\braudhatul\s+athfal|\braudlatul\s+athfal|\bra\b', low):
        edu_levels.add('ra')
    if re.search(r'\bsekolah\s+dasar|\bsd\b', low):
        edu_levels.add('sd')
    if re.search(r'\bmadrasah\s+ibtidaiyah|\bmi\b', low):
        edu_levels.add('mi')
    if re.search(r'\bsekolah\s+menengah\s+pertama|\bsmp\b', low):
        edu_levels.add('smp')
    if re.search(r'\bmadrasah\s+tsanawiyah|\bmts\b', low):
        edu_levels.add('mts')
    if re.search(r'\bsekolah\s+menengah\s+atas|\bsma\b', low):
        edu_levels.add('sma')
    if re.search(r'\bmadrasah\s+aliyah|\bma\b', low):
        edu_levels.add('ma')
    if re.search(r'\bsekolah\s+menengah\s+kejuruan|\bsmk\b', low):
        edu_levels.add('smk')
    if re.search(r'\bsekolah\s+luar\s+biasa|\bslb\b', low):
        edu_levels.add('slb')
    if re.search(r'\bperguruan\s+tinggi|\buniversitas|\bpts\b|\bptn\b|\bptais\b', low):
        edu_levels.add('pt')

    # Kementerian pembina (Kemendikdasmen/Kemendikbud vs Kemenag)
    ministry = None
    if re.search(r'\bkementerian\s+agama|\bkemenag\b', low):
        ministry = 'kemenag'
    elif re.search(r'\bkementerian\s+pendidikan|\bkemendik', low):
        ministry = 'kemendik'

    # Dasar harga PDRB (Berlaku vs Konstan)
    price_basis = set()
    if re.search(r'harga\s+berlaku', low):
        price_basis.add('berlaku')
    if re.search(r'harga\s+konstan', low):
        price_basis.add('konstan')

    # Kelas jalan (Negara vs Provinsi vs Kabupaten/Kota)
    road_class = set()
    if re.search(r'\bjalan\s+negara\b', low):
        road_class.add('negara')
    if re.search(r'\bjalan\s+provinsi\b', low):
        road_class.add('provinsi')
    if re.search(r'\bjalan\s+kabupaten', low):
        road_class.add('kabupaten')

    # Kelas hotel (Bintang vs Nonbintang — hanya bila tunggal, bukan gabungan)
    hotel_class = set()
    if re.search(r'hotel\s+bintang', low):
        hotel_class.add('bintang')
    if re.search(r'non[\s\-]?bintang', low):
        hotel_class.add('nonbintang')

    # Kelompok komoditas pertanian / perikanan / layanan kesehatan / indikator spesifik
    agri_group = set()
    if re.search(r'tanaman\s+hias', low):
        agri_group.add('tanaman_hias')
    if re.search(r'buah[\s\-]?buahan|sayuran', low):
        agri_group.add('buah_sayur')
    if re.search(r'tanaman\s+biofarmaka|\bbiofarmaka\b', low):
        agri_group.add('biofarmaka')
    if re.search(r'\bperkebunan\b', low):
        agri_group.add('perkebunan')
    if re.search(r'tanaman\s+pangan', low):
        agri_group.add('tanaman_pangan')
    if re.search(r'perikanan\s+tangkap|penangkapan\s+ikan', low):
        agri_group.add('perikanan_tangkap')
    if re.search(r'perikanan\s+budi\s?daya|budi\s?daya\s+ikan', low):
        agri_group.add('perikanan_budidaya')

    indicator_code = set()
    if re.search(r'partisipasi\s+murni|\bapm\b', low):
        indicator_code.add('apm')
    if re.search(r'partisipasi\s+kasar|\bapk\b', low):
        indicator_code.add('apk')
    if re.search(r'partisipasi\s+sekolah|\baps\b', low):
        indicator_code.add('aps')
    if re.search(r'pengangguran\s+terbuka|\btpt\b', low):
        indicator_code.add('tpt')
    if re.search(r'partisipasi\s+angkatan\s+kerja|\btpak\b', low):
        indicator_code.add('tpak')
    if re.search(r'harapan\s+lama\s+sekolah|\bhls\b', low):
        indicator_code.add('hls')
    if re.search(r'rata[\s\-]?rata\s+lama\s+sekolah|\brls\b', low):
        indicator_code.add('rls')
    if re.search(r'rawat\s+inap', low):
        indicator_code.add('rawat_inap')
    if re.search(r'rawat\s+jalan', low):
        indicator_code.add('rawat_jalan')

    topic_flags = set()
    if 'kebakaran' in low:
        topic_flags.add('kebakaran')
    if re.search(r'\bbencana\s+non[\s\-]?alam\b|\bnon[\s\-]?alam\b', low):
        topic_flags.add('non_alam')
    elif re.search(r'\bbencana\s+alam\b', low):
        topic_flags.add('bencana_alam')
    if re.search(r'\bekspor\b', low) and not re.search(r'\bimpor\b', low):
        topic_flags.add('ekspor')
    elif re.search(r'\bimpor\b', low) and not re.search(r'\bekspor\b', low):
        topic_flags.add('impor')

    return {
        'dims': dims,
        'subj': subj,
        'scope': scope,
        'areas': areas,
        'measures': measures,
        'core_measures': core_measures,
        'edu_levels': edu_levels,
        'ministry': ministry,
        'price_basis': price_basis,
        'road_class': road_class,
        'hotel_class': hotel_class,
        'agri_group': agri_group,
        'indicator_code': indicator_code,
        'topic_flags': topic_flags,
    }


_CORE_OBJ_STOPWORDS = {
    'jumlah', 'banyaknya', 'menurut', 'kabupaten', 'kota', 'kab', 'provinsi',
    'jawa', 'tengah', 'tahunan', 'tahun', 'triwulan', 'semester', 'bulanan',
    'jenis', 'kelompok', 'tingkat', 'status', 'klasifikasi', 'kategori',
    'utama', 'daerah', 'wilayah', 'bawah', 'kementerian', 'negara', 'indonesia',
    'di', 'dan', 'serta', 'atau', 'yang', 'pada', 'dalam', 'dari', 'ke', 'atas',
    'dengan', 'untuk', 'oleh', 'per', 'sebulan', 'setahun', 'selama', 'seminggu',
    'terakhir', 'rupiah', 'persen', 'orang', 'jiwa', 'unit', 'buah', 'ribu',
    'juta', 'miliar', 'triliun', 'km', 'ha', 'ton', 'tangkai', 'ekor', 'batang',
    'rekap', 'data', 'tabel', 'table', 'daftar', 'total', 'dasar', 'nilai',
    'luas', 'produksi', 'produktivitas', 'rata', 'persentase', 'indeks',
    'distribusi', 'laju', 'pertumbuhan', 'kepadatan', 'rasio', 'panjang',
    'volume', 'harga', 'berlaku', 'konstan', 'sekitar', 'tahun', 'berumur',
}

_CORE_OBJ_SYNONYMS = {
    'murid': 'peserta_didik',
    'siswa': 'peserta_didik',
    'guru': 'pendidik',
    'sekolah': 'satuan_pendidikan',
    'upah': 'pendapatan',
    'gaji': 'pendapatan',
    'buruh': 'pekerja',
    'karyawan': 'pekerja',
    'pegawai': 'pekerja',
    'rumahtangga': 'rumah_tangga',
    'kelurahan': 'desa',
}


def _extract_core_object_tokens(raw_title):
    """Ekstrak token objek domain inti (tanpa kata boilerplate & metrik generik) untuk anti-tabrakan topik."""
    s = normalize_title(raw_title)
    s = re.sub(r'\bpeserta\s+didik\b', 'peserta_didik', s)
    s = re.sub(r'\bsatuan\s+pendidikan\b', 'satuan_pendidikan', s)
    s = re.sub(r'\brumah\s+tangga\b', 'rumah_tangga', s)
    toks = []
    for tok in s.split():
        if len(tok) <= 1 or tok.isdigit():
            continue
        tok = _CORE_OBJ_SYNONYMS.get(tok, tok)
        if tok in _CORE_OBJ_STOPWORDS:
            continue
        toks.append(tok)
    return set(toks)


def _has_core_object_conflict(raw_a, raw_b):
    """Tolak pasangan jika kedua judul memiliki objek inti yang sama sekali tidak tumpang tindih."""
    ca = _extract_core_object_tokens(raw_a)
    cb = _extract_core_object_tokens(raw_b)
    if len(ca) < 2 or len(cb) < 2:
        return False
    shared = ca & cb
    core_fuzzy = fuzz.token_set_ratio(' '.join(sorted(ca)), ' '.join(sorted(cb)))
    if len(shared) == 0 and core_fuzzy < 45.0:
        return True
    overlap_ratio = len(shared) / float(min(len(ca), len(cb)))
    if len(shared) == 1 and overlap_ratio < 0.34 and core_fuzzy < 52.0:
        return True
    return False


def _has_semantic_conflict(raw_a, raw_b):
    """Mengembalikan True jika dua judul memiliki dimensi, subjek, ukuran, kelas objek, atau topik inti yang saling bertentangan."""
    fa = _extract_semantic_facets(raw_a)
    fb = _extract_semantic_facets(raw_b)

    if fa['subj'] and fb['subj'] and fa['subj'] != fb['subj']:
        return True
    if fa['scope'] and fb['scope'] and fa['scope'] != fb['scope']:
        return True
    if fa['dims'] and fb['dims'] and fa['dims'] != fb['dims']:
        return True
    if (fa['areas'] or fb['areas']) and fa['areas'] != fb['areas']:
        return True
    if (fa['measures'] or fb['measures']) and fa['measures'] != fb['measures']:
        return True
    if fa['core_measures'] and fb['core_measures'] and fa['core_measures'].isdisjoint(fb['core_measures']):
        return True
    if fa['edu_levels'] and fb['edu_levels'] and fa['edu_levels'] != fb['edu_levels']:
        return True
    if fa['ministry'] and fb['ministry'] and fa['ministry'] != fb['ministry']:
        return True
    if fa['price_basis'] and fb['price_basis'] and fa['price_basis'] != fb['price_basis']:
        return True
    if fa['road_class'] and fb['road_class'] and fa['road_class'] != fb['road_class']:
        return True
    if fa['hotel_class'] and fb['hotel_class'] and fa['hotel_class'] != fb['hotel_class']:
        return True
    if fa['agri_group'] and fb['agri_group'] and fa['agri_group'] != fb['agri_group']:
        return True
    if fa['indicator_code'] and fb['indicator_code'] and fa['indicator_code'] != fb['indicator_code']:
        return True
    if fa['topic_flags'] and fb['topic_flags'] and fa['topic_flags'] != fb['topic_flags']:
        return True
    if _has_core_object_conflict(raw_a, raw_b):
        return True
    return False

def _score(a, b):
    return 0.5*fuzz.token_sort_ratio(a, b) + 0.3*fuzz.token_set_ratio(a, b) + 0.2*fuzz.ratio(a, b)

def _resolve_source_type(item):
    src = str(item.get("source_type", "")).strip().lower()
    if src in ("dinamis", "api", "spreadsheet"):
        return src
    if int(item.get("is_dynamic", 0) or 0) == 1:
        return "dinamis"
    link_lower = str(item.get("link_tabel", "")).strip().lower()
    if "view_portal_tabel" in link_lower or "satudata" in link_lower:
        return "api"
    return "spreadsheet"


def match_tables(toc, db_tables, min_score=65):
    matched, still_missing, unreg, used = [], [], [], set()
    unmatched_db = []

    def route_matched_entry(entry, source_type):
        if source_type in ("dinamis", "api"):
            matched.append(entry)
        else:
            unreg.append({
                "nomor_tabel": entry["nomor_tabel"],
                "id_db": entry.get("id_db", ""),
                "judul_pdf": entry["judul_pdf"],
                "judul_db": entry.get("judul_db", ""),
                "pages": entry.get("pages", []),
                "source_type": "spreadsheet",
                "reason": "Belum dikonfigurasi ke Tabel Dinamis (masih format Spreadsheet)"
            })

    # Urutkan prioritas source_type: dinamis (0) > api (1) > spreadsheet (2)
    prio_map = {"dinamis": 0, "api": 1, "spreadsheet": 2}
    sorted_db = sorted(db_tables, key=lambda x: prio_map.get(_resolve_source_type(x), 3))

    # PASS 1: Pencocokan eksak berdasarkan nomor_tabel + Strict Object & Measure Guard
    # Jika beberapa entri DB memakai nomor_tabel yang sama, pilih kandidat terbaik secara deterministik.
    pass1_buckets = {}
    pass1_no_bucket = []
    for item in sorted_db:
        t_num = str(item.get("nomor_tabel", "")).strip()
        db_id = str(item.get("id_db", item.get("id", ""))).strip()
        title = item.get("judul_tabel", item.get("judul_ind", ""))
        source_type = _resolve_source_type(item)
        if t_num and t_num in toc:
            pass1_buckets.setdefault(t_num, []).append((item, db_id, title, source_type))
        else:
            pass1_no_bucket.append((item, db_id, title, source_type))

    for t_num, candidates in pass1_buckets.items():
        pdf_title = toc[t_num]["judul_pdf"]
        best_cand = None
        best_rank = None
        for idx_c, (item, db_id, title, source_type) in enumerate(candidates):
            title_sim = _score(normalize_title(title), normalize_title(pdf_title))
            if title_sim < min_score or _has_semantic_conflict(title, pdf_title):
                continue
            # Prioritaskan dinamis/api di atas spreadsheet, lalu skor judul tertinggi
            is_configured = 1 if source_type in ("dinamis", "api") else 0
            rank = (is_configured, round(float(title_sim), 2), -prio_map.get(source_type, 3), -idx_c)
            if best_rank is None or rank > best_rank:
                best_rank = rank
                best_cand = (item, db_id, title, source_type, title_sim)

        if best_cand is not None:
            win_item, win_db_id, win_title, win_src, win_sim = best_cand
            used.add(t_num)
            has_cfg = int(bool(win_item.get("config_tabel") and str(win_item.get("config_tabel")).strip() not in ("", "{}", "null")))
            route_matched_entry({
                "nomor_tabel": t_num,
                "nomor_tabel_db": t_num,
                "nomor_bergeser": False,
                "id_db": win_db_id,
                "judul_db": win_title,
                "judul_pdf": pdf_title,
                "pages": toc[t_num]["pages"],
                "similarity": 100.0 if win_sim >= 85 else round(float(win_sim), 1),
                "link_tabel": win_item.get("link_tabel", ""),
                "source_type": win_src,
                "is_dynamic": int(win_item.get("is_dynamic", 0) or 0),
                "dynamic_empty": bool(win_item.get("dynamic_empty", False)),
                "config_tabel": win_item.get("config_tabel"),
                "has_config": has_cfg
            }, win_src)
            for cand in candidates:
                if cand[1] != win_db_id or cand[0] is not win_item:
                    unmatched_db.append(cand)
        else:
            unmatched_db.extend(candidates)

    unmatched_db.extend(pass1_no_bucket)

    # PASS 2: Pencocokan judul fuzzy untuk tabel yang nomor_tabel-nya kosong/belum cocok
    avail_toc = [k for k in toc if k not in used]

    if unmatched_db and avail_toc:
        try:
            # Optimal Hungarian Assignment (Maximum Bipartite Matching) dengan Semantic Conflict Guard
            cost_matrix = []
            score_matrix = []
            for item, db_id, title, source_type in unmatched_db:
                d_norm = normalize_title(title)
                r_scores = []
                r_costs = []
                for k in avail_toc:
                    pdf_title = toc[k]["judul_pdf"]
                    if _has_semantic_conflict(title, pdf_title):
                        sc = 0.0
                    else:
                        sc = _score(d_norm, normalize_title(pdf_title))
                    r_scores.append(sc)
                    # Pasangan < min_score tidak boleh menurunkan cost matriks dan mengorbankan pasangan valid >= min_score
                    r_costs.append((100.0 - sc) if sc >= min_score else 1000.0)
                score_matrix.append(r_scores)
                cost_matrix.append(r_costs)

            row_ind, col_ind = linear_sum_assignment(np.array(cost_matrix))
            matched_db_indices = set()
            for r_idx, c_idx in zip(row_ind, col_ind):
                best_score = score_matrix[r_idx][c_idx]
                item, db_id, title, source_type = unmatched_db[r_idx]
                t_num = str(item.get("nomor_tabel", "")).strip()
                best_k = avail_toc[c_idx]
                link = item.get("link_tabel", "")

                if best_score >= min_score:
                    used.add(best_k)
                    matched_db_indices.add(r_idx)
                    has_cfg = int(bool(item.get("config_tabel") and str(item.get("config_tabel")).strip() not in ("", "{}", "null")))
                    route_matched_entry({
                        "nomor_tabel": best_k,
                        "nomor_tabel_db": t_num,
                        "nomor_bergeser": bool(t_num and t_num != best_k),
                        "id_db": db_id,
                        "judul_db": title,
                        "judul_pdf": toc[best_k]["judul_pdf"],
                        "pages": toc[best_k]["pages"],
                        "similarity": round(float(best_score), 1),
                        "link_tabel": link,
                        "source_type": source_type,
                        "is_dynamic": int(item.get("is_dynamic", 0) or 0),
                        "dynamic_empty": bool(item.get("dynamic_empty", False)),
                        "config_tabel": item.get("config_tabel"),
                        "has_config": has_cfg
                    }, source_type)
                elif source_type in ("dinamis", "api"):
                    still_missing.append({
                        "nomor_tabel": t_num or "-",
                        "id_db": db_id,
                        "judul_db": title,
                        "judul_pdf": "-",
                        "similarity": round(float(best_score), 1),
                        "source_type": source_type,
                        "reason": f"Tidak ada kecocokan di PDF (skor tertinggi {round(float(best_score), 1)}%)"
                    })

            for idx, (item, db_id, title, source_type) in enumerate(unmatched_db):
                if idx not in matched_db_indices and idx not in row_ind and source_type in ("dinamis", "api"):
                    t_num = str(item.get("nomor_tabel", "")).strip()
                    still_missing.append({
                        "nomor_tabel": t_num or "-",
                        "id_db": db_id,
                        "judul_db": title,
                        "judul_pdf": "-",
                        "similarity": 0.0,
                        "source_type": source_type,
                        "reason": "Tidak ada kecocokan di PDF"
                    })
        except Exception as e:
            sys.stderr.write(f"[WARN] Hungarian assignment failed ({e}), falling back to greedy matching\n")
            for item, db_id, title, source_type in unmatched_db:
                t_num = str(item.get("nomor_tabel", "")).strip()
                d_norm = normalize_title(title)
                best_k, best_score = None, 0
                for k in avail_toc:
                    pdf_title = toc[k]["judul_pdf"]
                    if _has_semantic_conflict(title, pdf_title):
                        continue
                    score = _score(d_norm, normalize_title(pdf_title))
                    if score > best_score:
                        best_score = score
                        best_k = k
                if best_score >= min_score and best_k and best_k not in used:
                    used.add(best_k)
                    avail_toc.remove(best_k)
                    has_cfg = int(bool(item.get("config_tabel") and str(item.get("config_tabel")).strip() not in ("", "{}", "null")))
                    route_matched_entry({
                        "nomor_tabel": best_k,
                        "nomor_tabel_db": t_num,
                        "nomor_bergeser": bool(t_num and t_num != best_k),
                        "id_db": db_id,
                        "judul_db": title,
                        "judul_pdf": toc[best_k]["judul_pdf"],
                        "pages": toc[best_k]["pages"],
                        "similarity": round(float(best_score), 1),
                        "link_tabel": item.get("link_tabel", ""),
                        "source_type": source_type,
                        "is_dynamic": int(item.get("is_dynamic", 0) or 0),
                        "dynamic_empty": bool(item.get("dynamic_empty", False)),
                        "config_tabel": item.get("config_tabel"),
                        "has_config": has_cfg
                    }, source_type)
                elif source_type in ("dinamis", "api"):
                    still_missing.append({
                        "nomor_tabel": t_num or "-",
                        "id_db": db_id,
                        "judul_db": title,
                        "judul_pdf": "-",
                        "similarity": round(float(best_score), 1),
                        "source_type": source_type,
                        "reason": f"Tidak ada kecocokan di PDF (skor tertinggi {round(float(best_score), 1)}%)"
                    })
    else:
        for item, db_id, title, source_type in unmatched_db:
            if source_type in ("dinamis", "api"):
                t_num = str(item.get("nomor_tabel", "")).strip()
                still_missing.append({
                    "nomor_tabel": t_num or "-",
                    "id_db": db_id,
                    "judul_db": title,
                    "judul_pdf": "-",
                    "similarity": 0.0,
                    "source_type": source_type,
                    "reason": "Tidak ada kecocokan di PDF"
                })

    for k in toc:
        if k not in used:
            unreg.append({
                "nomor_tabel": k,
                "judul_pdf": toc[k]["judul_pdf"],
                "pages": toc[k].get("pages", []),
                "source_type": "unregistered",
                "reason": "Belum terdaftar di Database / belum dikonfigurasi Tabel Dinamis"
            })

    sk = lambda x: [int(p) for p in re.findall(r'\d+', x["nomor_tabel"])]
    for L in (matched, still_missing, unreg):
        try:
            L.sort(key=sk)
        except Exception:
            pass

    return {"status": "success", "matched_in_pdf": matched, "missing_in_pdf": still_missing, "unregistered_in_db": unreg}


def _atomic_write_json(path, payload, indent=None):
    tmp_path = f"{path}.tmp_{os.getpid()}"
    with open(tmp_path, 'w', encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False, indent=indent)
    os.replace(tmp_path, path)


def main():
    if len(sys.argv) >= 3 and sys.argv[1] == "--detect-year":
        pdf_path = sys.argv[2]
        orig_name = sys.argv[3] if len(sys.argv) > 3 else None
        yr = detect_pdf_publication_year(pdf_path, filename=orig_name)
        print(json.dumps({"status": "success", "year": yr}, ensure_ascii=False))
        return

    pdf_path, db_json = sys.argv[1], sys.argv[2]
    with open(db_json, 'r', encoding='utf-8') as f:
        db = json.load(f)

    idx_file = pdf_path + ".index.json"
    raw_idx = None
    if os.path.exists(idx_file) and os.path.exists(pdf_path) and os.path.getmtime(idx_file) >= os.path.getmtime(pdf_path):
        try:
            with open(idx_file, "r", encoding="utf-8") as f:
                raw_idx = json.load(f)
        except Exception:
            raw_idx = None
    if isinstance(raw_idx, dict) and raw_idx:
        toc = {
            k: {
                "pages": v.get("pages", []),
                "judul_pdf": v.get("judul_pdf") or v.get("display_title", "")
            }
            for k, v in raw_idx.items()
        }
    else:
        toc = build_index(pdf_path, db_tables=db)
        try:
            _atomic_write_json(idx_file, {k: {"pages": v["pages"], "display_title": v["judul_pdf"]} for k, v in toc.items()})
        except Exception as e:
            sys.stderr.write(f"[WARN] Gagal menyimpan index cache file: {e}\n")

    matched = match_tables(toc, db)
    detected_yr = detect_pdf_publication_year(pdf_path)
    matched["detected_pub_year"] = detected_yr

    ta = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3].isdigit() else None
    if not ta:
        m_year = re.search(r'(\d{4})', pdf_path)
        ta = m_year.group(1) if m_year else (detected_yr or str(datetime.now().year))
    up_dir = os.path.dirname(db_json)

    dda_matched_file = os.path.join(up_dir, f"dda_matched_{ta}.json")
    try:
        _atomic_write_json(dda_matched_file, matched, indent=2)
    except Exception as e:
        sys.stderr.write(f"[WARN] Gagal menyimpan dda_matched cache file: {e}\n")

    print(json.dumps(matched, ensure_ascii=False))

if __name__ == "__main__":
    main()