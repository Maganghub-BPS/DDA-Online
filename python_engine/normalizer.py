#Untuk mencocokkan wilayah
import re

# 1. CANONICAL JAWA TENGAH REGISTRY (35 Kab/Kota + Provinsi)
JATENG_REGISTRY = [
    ("33.01", "Kab. Cilacap", ["cilacap"]),
    ("33.02", "Kab. Banyumas", ["banyumas"]),
    ("33.03", "Kab. Purbalingga", ["purbalingga"]),
    ("33.04", "Kab. Banjarnegara", ["banjarnegara"]),
    ("33.05", "Kab. Kebumen", ["kebumen"]),
    ("33.06", "Kab. Purworejo", ["purworejo"]),
    ("33.07", "Kab. Wonosobo", ["wonosobo"]),
    ("33.08", "Kab. Magelang", [r"(?:kab|kabupaten)\.?\s*magelang", r"\bmagelang\b(?!.*kota)"]),
    ("33.09", "Kab. Boyolali", ["boyolali"]),
    ("33.10", "Kab. Klaten", ["klaten"]),
    ("33.11", "Kab. Sukoharjo", ["sukoharjo"]),
    ("33.12", "Kab. Wonogiri", ["wonogiri"]),
    ("33.13", "Kab. Karanganyar", ["karanganyar"]),
    ("33.14", "Kab. Sragen", ["sragen"]),
    ("33.15", "Kab. Grobogan", ["grobogan"]),
    ("33.16", "Kab. Blora", ["blora"]),
    ("33.17", "Kab. Rembang", ["rembang"]),
    ("33.18", "Kab. Pati", ["pati"]),
    ("33.19", "Kab. Kudus", ["kudus"]),
    ("33.20", "Kab. Jepara", ["jepara"]),
    ("33.21", "Kab. Demak", ["demak"]),
    ("33.22", "Kab. Semarang", [r"(?:kab|kabupaten)\.?\s*semarang", r"\bsemarang\b(?!.*kota)"]),
    ("33.23", "Kab. Temanggung", ["temanggung"]),
    ("33.24", "Kab. Kendal", ["kendal"]),
    ("33.25", "Kab. Batang", ["batang"]),
    ("33.26", "Kab. Pekalongan", [r"(?:kab|kabupaten)\.?\s*pekalongan", r"\bpekalongan\b(?!.*kota)"]),
    ("33.27", "Kab. Pemalang", ["pemalang"]),
    ("33.28", "Kab. Tegal", [r"(?:kab|kabupaten)\.?\s*tegal", r"\btegal\b(?!.*kota)"]),
    ("33.29", "Kab. Brebes", ["brebes"]),
    ("33.71", "Kota Magelang", [r"kota\s+magelang"]),
    ("33.72", "Kota Surakarta", ["surakarta", "solo"]),
    ("33.73", "Kota Salatiga", ["salatiga"]),
    ("33.74", "Kota Semarang", [r"kota\s+semarang"]),
    ("33.75", "Kota Pekalongan", [r"kota\s+pekalongan"]),
    ("33.76", "Kota Tegal", [r"kota\s+tegal"]),
    ("33.00", "Total Jawa Tengah", ["jawa tengah", "total", "jumlah"])
]

KAB_KOTA_JATENG = [name.replace("Kab. ", "").replace("Kota ", "") for _, name, _ in JATENG_REGISTRY if name != "Total Jawa Tengah"] + ["Jawa Tengah"]

def resolve_jateng_entity(text, is_kota_mode=False):
    if not text: return None, None
    t = str(text).lower().strip()
    if 'provinsi jawa tengah' in t or 'pemprov' in t:
        return "33.00", "Provinsi Jawa Tengah"
    
    # Priority check for cities sharing names with regencies
    for w in ['magelang', 'semarang', 'pekalongan', 'tegal']:
        if f'kota {w}' in t or (is_kota_mode and re.search(rf'\b{w}\b', t) and 'kab' not in t):
            code = next(c for c, name, _ in JATENG_REGISTRY if name == f"Kota {w.title()}")
            return code, f"Kota {w.title()}"
        elif f'kab {w}' in t or f'kabupaten {w}' in t:
            code = next(c for c, name, _ in JATENG_REGISTRY if name == f"Kab. {w.title()}")
            return code, f"Kab. {w.title()}"
            
    for code, name, patterns in JATENG_REGISTRY:
        if code in ["33.08", "33.22", "33.26", "33.28", "33.71", "33.74", "33.75", "33.76"]:
            continue
        for p in patterns:
            if re.search(rf'\b{p}\b', t):
                return code, name
                
    for w in ['magelang', 'semarang', 'pekalongan', 'tegal']:
        if re.search(rf'\b{w}\b', t):
            if is_kota_mode:
                code = next(c for c, name, _ in JATENG_REGISTRY if name == f"Kota {w.title()}")
                return code, f"Kota {w.title()}"
            else:
                code = next(c for c, name, _ in JATENG_REGISTRY if name == f"Kab. {w.title()}")
                return code, f"Kab. {w.title()}"
                
    return None, None

# 2. CANONICAL PROVINSI REGISTRY (38 Provinsi + Total Indonesia)
PROVINSI_REGISTRY = [
    ("92", "Papua Barat Daya", ["papua barat daya"]),
    ("96", "Papua Pegunungan", ["papua pegunungan"]),
    ("94", "Papua Selatan", ["papua selatan"]),
    ("95", "Papua Tengah", ["papua tengah"]),
    ("91", "Papua Barat", [r"papua barat(?!.*daya)"]),
    ("93", "Papua", [r"(?<!papua\s)\bpapua\b(?!.*(?:barat|daya|selatan|tengah|pegunungan))"]),
    ("11", "Aceh", [r"(?<!banda\s)\baceh\b"]),
    ("12", "Sumatera Utara", ["sumatera utara", "sumut"]),
    ("13", "Sumatera Barat", ["sumatera barat", "sumbar"]),
    ("14", "Riau", [r"(?<!kepulauan\s)(?<!kep\.\s)\briau\b"]),
    ("15", "Jambi", ["jambi"]),
    ("16", "Sumatera Selatan", ["sumatera selatan", "sumsel"]),
    ("17", "Bengkulu", ["bengkulu"]),
    ("18", "Lampung", ["lampung"]),
    ("19", "Kep. Bangka Belitung", ["bangka belitung", "babel", "kep. bangka belitung", "kepulauan bangka belitung"]),
    ("21", "Kepulauan Riau", ["kepulauan riau", "kep. riau", "kepri"]),
    ("31", "DKI Jakarta", ["dki jakarta", "jakarta"]),
    ("32", "Jawa Barat", ["jawa barat", "jabar"]),
    ("33", "Jawa Tengah", ["jawa tengah", "jateng"]),
    ("34", "DI Yogyakarta", ["yogyakarta", "d.i. yogyakarta", "diy"]),
    ("35", "Jawa Timur", ["jawa timur", "jatim"]),
    ("36", "Banten", ["banten"]),
    ("51", "Bali", ["bali"]),
    ("52", "Nusa Tenggara Barat", ["nusa tenggara barat", "ntb"]),
    ("53", "Nusa Tenggara Timur", ["nusa tenggara timur", "ntt"]),
    ("61", "Kalimantan Barat", ["kalimantan barat", "kalbar"]),
    ("62", "Kalimantan Tengah", ["kalimantan tengah", "kalteng"]),
    ("63", "Kalimantan Selatan", ["kalimantan selatan", "kalsel"]),
    ("64", "Kalimantan Timur", ["kalimantan timur", "kaltim"]),
    ("65", "Kalimantan Utara", ["kalimantan utara", "kaltara"]),
    ("71", "Sulawesi Utara", ["sulawesi utara", "sulut"]),
    ("72", "Sulawesi Tengah", ["sulawesi tengah", "sulteng"]),
    ("73", "Sulawesi Selatan", ["sulawesi selatan", "sulsel"]),
    ("74", "Sulawesi Tenggara", ["sulawesi tenggara", "sultra"]),
    ("75", "Gorontalo", ["gorontalo"]),
    ("76", "Sulawesi Barat", ["sulawesi barat", "sulbar"]),
    ("81", "Maluku", [r"(?<!maluku\s)\bmaluku\b(?!.*utara)"]),
    ("82", "Maluku Utara", ["maluku utara"]),
    ("00", "Indonesia", [r"^(?:total\s+|seluruh\s+)?indonesia$", r"^republik\s+indonesia$"])
]

PROVINSI_MAP = [(name, patterns) for _, name, patterns in PROVINSI_REGISTRY]

def resolve_provinsi_entity(text):
    if not text: return None, None
    t = str(text).lower().strip()
    if re.search(r'\b(?:kab|kabupaten)\b', t):
        return None, None
    # Strip trailing footnote numbers / symbols attached to province names (e.g., Papua Barat Daya5, Papua Barat4)
    t = re.sub(r'[\d\*\)\(\^#\†\‡\.\,\-]+$', '', t).strip()
    for code, name, patterns in PROVINSI_REGISTRY:
        for p in patterns:
            if re.search(rf'\b{p}\b', t):
                return code, name
    return None, None

def find_provinsi(text):
    code, name = resolve_provinsi_entity(text)
    return name

def is_safe_fuzzy_match(s1, s2):
    if not s1 or not s2: return False
    w1 = set(re.findall(r'[a-zA-Z0-9]+', str(s1).lower()))
    w2 = set(re.findall(r'[a-zA-Z0-9]+', str(s2).lower()))
    directions = {'utara', 'selatan', 'barat', 'timur', 'tengah', 'daya', 'pegunungan', 'kepulauan', 'kep'}
    if w1.intersection(directions) != w2.intersection(directions):
        return False
    digits1 = set(re.findall(r'\b\d+\b', str(s1)))
    digits2 = set(re.findall(r'\b\d+\b', str(s2)))
    if digits1 != digits2:
        return False
    roman1 = set(re.findall(r'\b[ivx]+\b', str(s1).lower()))
    roman2 = set(re.findall(r'\b[ivx]+\b', str(s2).lower()))
    if roman1 != roman2:
        return False
    grades1 = set(re.findall(r'\b[a-e]\b', str(s1).lower()))
    grades2 = set(re.findall(r'\b[a-e]\b', str(s2).lower()))
    if grades1 != grades2:
        return False
    return True

class AmbiguousFloat(float):
    """float yang membawa interpretasi alternatif (alts) untuk string ambigu seperti '1.234'
    (ribuan 1234 atau desimal 1,234). Perilaku aritmetika/JSON identik dengan float biasa."""
    alts = ()


def _with_alts(value, alts):
    out = AmbiguousFloat(value)
    out.alts = tuple(a for a in alts if a != value)
    return out if out.alts else float(value)


_MINUS_CHARS = '\u2212\u2013\u2014\u2012\u2015\u2011'
_PERIOD_LABEL_RE = re.compile(r'^\s*(?:19|20)\d{2}\s*[/\-\u2013]\s*(?:(?:19|20)?\d{2})\s*[a-zA-Z]?\s*$')


def parse_num(val_str, is_pdf=False):
    if val_str is None: return None
    # Angka native (dari DB/JSON) dipakai apa adanya: JANGAN dikonversi ke string lalu ditebak ulang
    # (mis. 123.456 tidak boleh berubah menjadi 123456).
    if isinstance(val_str, bool):
        return None
    if isinstance(val_str, (int, float)):
        f = float(val_str)
        return f if f == f and f not in (float('inf'), float('-inf')) else None
    s = str(val_str).strip()
    if not s: return None

    # Label periode seperti '2023/2024' bukan angka
    if _PERIOD_LABEL_RE.match(s) and not re.match(r'^\s*-', s):
        return None
    # Tanda minus unicode di depan angka -> '-' ASCII (agar angka negatif tidak hilang)
    if s[0] in _MINUS_CHARS and len(s) > 1 and (s[1].isdigit() or s[1] in '.,'):
        s = '-' + s[1:]

    # Strip footnote markers: 1), *), **), a)
    s = re.sub(r'\s*\b\d+\)$', '', s)
    s = re.sub(r'\s*[\*\)]+$', '', s)
    s = re.sub(r'\s*[a-zA-Z]\)$', '', s)
    # Strip vertical watermark artifacts (e.g., newline followed by single letter)
    s = re.sub(r'[\r\n]+[a-z][\r\n]*', '', s)

    # Filter unit/code tokens where digits belong to unit or classification code (e.g. 'm3/m3', 'Rp/m3', 'Rp/sm', '(K1)', '(K2)', '(KP3)', '(KMP3)')
    s_raw_token = s.strip()
    if re.match(r'^\(?[a-zA-Z]+\d{1,2}\)?$', s_raw_token):
        return None
    if re.match(r'^\(?[a-zA-Z]{1,4}\d{1,2}(?:/[a-zA-Z]{1,4}\d{1,2})?\)?$', s_raw_token):
        return None
    if re.search(r'(?i)\b[a-zA-Z]+[23]?\s*/\s*[a-zA-Z]+[23]?\b', s_raw_token):
        return None
    s_no_units = re.sub(r'(?i)\b(?:m[23]|cm[23]|mm[23]|km[23]|dm[23]|sm|ha|kg|ton|rp|usd)\b', '', s_raw_token)
    s_no_units = re.sub(r'^[a-zA-Z\s\.\/:\(\)]+', '', s_no_units)
    s_no_units = re.sub(r'[a-zA-Z\s\.\/:\(\)]+$', '', s_no_units)
    if not any(c.isdigit() for c in s_no_units) and any(c.isalpha() for c in s_raw_token):
        return None

    s_clean_token = s.strip().lower()
    dash_nihil = {'–', '-', '—', '−', '\u2212', '\u2011', '\u2015', 'na', 'n.a', 'n.a.', 'n/a', '...', '', '\ufffd', '\u2026', '…'}
    if s_clean_token in dash_nihil:
        return 0.0
    if any(d in s for d in ['–', '-', '—', '−', '\u2212', '\ufffd', '\u2026', '…']) and not any(c.isalnum() for c in s):
        return 0.0

    if is_pdf:
        # Strip watermark prefixes before numbers or symbols (e.g. 'net7.478', 'gne1.470')
        s = re.sub(r'^[a-zA-Z\s\.\/:\n\r]+(?=[0-9–\-—\ufffd\u2026…])', '', s)
        # Strip watermark suffixes after numbers (e.g. '7.084.p')
        s = re.sub(r'(?<=\d)[a-zA-Z\s\.\/:\n\r]+$', '', s)
        # Strip watermark dots only when Indonesian decimal comma is followed by extra dot artifact (e.g. '49.752.646,8.0')
        # Never strip dot if it's US format where comma is thousands and dot is decimal (e.g. '1,887,994.78')
        if ',' in s and '.' in s and re.search(r'\.\d{3},\d+\.\d+', s):
            s = re.sub(r',(\d+)\.(\d+)', r',\1\2', s)

    s_cleaned = re.sub(r'(?i)\b(km[23]?|m[23]?|cm[23]?|dm[23]?|mm[23]?|ha|kg|ton|jiwa|orang|rupiah|persen|ribu|juta|miliar|triliun|sm)\b', '', s)
    if re.search(r'[a-zA-Z]{2,}', s_cleaned):
        return None
    if re.match(r'^(?i:[sd]\.?\s*[1-4])$', s.strip()):
        return None
        
    s = re.sub(r'(?i)\b(km|m|cm|dm|mm)2\b', r'\1', s)
    s = s.replace('%', '')
    s = re.sub(r'^[a-zA-Z\s\.\/]+(?=\d|\ufffd|–|-|—|−|\u2212|\u2026|…)', '', s)
    s = re.sub(r'[^\d,\.\-–—−\u2212\ufffd\u2026…]', '', s).strip()
    if not s: return None

    has_accounting_dash = bool(re.search(r'[,.](?:–|-|—|−|\u2212)+$', s))
    has_id_unit = bool(re.search(r'(?i)\b(?:km[23]?|m[23]?|cm[23]?|dm[23]?|mm[23]?|ha|kg|ton|jiwa|orang|rupiah|persen|ribu|juta|miliar|triliun|sm)\b', s_raw_token))
    is_formatted_text = is_pdf or has_accounting_dash or has_id_unit

    # Handle trailing accounting dash (e.g. '3.500.828.821.04–' -> '3.500.828.821.040', '100,-' -> '100')
    s = re.sub(r'[,.](?:–|-|—|−|\u2212)+$', '', s)
    s = re.sub(r'(\d)(?:–|-|—|−|\u2212)$', r'\g<1>0', s)

    if any(d in s for d in ['–', '-', '—', '−', '\u2212', '\ufffd', '\u2026', '…']) and not any(c.isdigit() for c in s): return 0.0

    ambiguous_alt = None
    # Sisi sumber murni (DB/API tanpa format teks/akuntansi): titik tunggal = desimal programatik
    if not is_formatted_text and re.fullmatch(r'-?\d+\.\d+', s):
        # Jika tepat 3 digit desimal (mis. '1.234'), berikan interpretasi alternatif ribuan
        if re.fullmatch(r'-?[1-9]\d{0,2}\.\d{3}', s):
            return _with_alts(float(s), [float(s.replace('.', ''))])
        return float(s)

    if ',' in s and '.' in s:
        comma_idx = s.rfind(',')
        dot_idx = s.rfind('.')
        if comma_idx > dot_idx: s = s.replace('.', '').replace(',', '.')
        else: s = s.replace(',', '')
    elif ',' in s:
        if s.count(',') >= 2:
            s = s.replace(',', '')
        else:
            # Format Indonesia / BPS: koma tunggal SELALU pemisah desimal (mis. '1,685' -> 1.685, '1500,685' -> 1500.685).
            # Jika tepat 3 digit di belakang koma (mis. '1,234'), sediakan alternatif ribuan untuk kecocokan perbandingan.
            if re.match(r'^-?[1-9]\d{0,2},\d{3}$', s):
                ambiguous_alt = s.replace(',', '')
            s = s.replace(',', '.')
    elif '.' in s:
        if s.count('.') >= 2 or re.match(r'^-?[1-9]\d{0,2}(\.\d{3})+$', s):
            if s.count('.') == 1:
                ambiguous_alt = s                      # '1.234' bisa juga desimal 1.234
            s = s.replace('.', '')

    try:
        value = float(s)
    except ValueError:
        return None
    if ambiguous_alt is not None:
        try:
            return _with_alts(value, [float(ambiguous_alt)])
        except ValueError:
            pass
    return value

def clean_header_text(c):
    if not c: return ""
    lines = [line.strip() for line in str(c).split('\n') if line.strip()]
    s = lines[0] if lines else ""
    s = s.rstrip('*').strip()
    if '/' in s and not re.search(r'\d/\d', s):
        p = s.split('/')
        if len(p) == 2 and any(ch.isalpha() for ch in p[0]) and any(ch.isalpha() for ch in p[1]):
            p0_low = p[0].lower()
            p1_low = p[1].lower()
            if 'schools' in p1_low and ('pendidik' in p0_low or 'kepala' in p0_low):
                s = "Sekolah"
            elif any(k in p1_low for k in ['teachers', 'headmasters']) and ('pendidik' in p0_low or 'guru' in p0_low):
                s = "Guru"
            elif any(k in p1_low for k in ['pupils', 'students']) or 'peserta didik' in p0_low:
                s = "Murid"
            else:
                s = p[0].strip()
    return s

def find_wilayah_with_type(ctx, is_kota=False):
    code, name = resolve_jateng_entity(ctx, is_kota_mode=is_kota)
    return name if name else "Umum"

def is_interval_label(s):
    if not s: return False
    s_clean = re.sub(r'[\s\*]', '', str(s))
    return bool(re.match(r'^(?:0|01|\d+[\-–—\ufffd]\d+|\d+\+|\<\s*\d+|\>\s*\d+)$', s_clean))

def is_bps_col_num(val):
    if not val: return False
    s = str(val).strip()
    m = re.match(r'^(?:\((\d{1,2})\)|-(\d{1,2})-?)$', s)
    if m:
        num = int(m.group(1) or m.group(2))
        return 1 <= num <= 50
    return False

def is_title_row(row):
    if not row or not isinstance(row, list): return True
    txt = ' '.join(str(c) for c in row if str(c).strip()).lower().strip()
    if re.match(r'^(?:tabel|table)\b', txt): return True
    if any(txt.startswith(k) for k in ['sumber:', 'source:', 'catatan:', 'note:', 'perubahan data:']): return True
    return False

def is_header_or_metadata_row(row):
    non_empty = [str(c).strip() for c in row if str(c).strip()]
    if not non_empty: return True
    ctx = ' | '.join(non_empty).lower()
    ctx_norm = re.sub(r'\s*/\s*', '/', ctx)
    
    if any(k in ctx_norm for k in ['kabupaten/regency', 'kota/municipality']): return True
    
    cleaned_cells = [re.sub(r'^[a-zA-Z\.\s/]+\n', '', c).strip() for c in non_empty if c]
    if cleaned_cells and all(is_bps_col_num(c) for c in cleaned_cells): return True
        
    clean_years = [re.sub(r'[\*\^#\s\†\‡]', '', c).rstrip('rpRP') for c in non_empty if c]
    year_matches = sum(1 for c in clean_years if re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', c))
    if year_matches >= len(non_empty) * 0.6: return True
    if len(non_empty) >= 3 and year_matches >= (len(non_empty) - 1) * 0.7: return True
        
    if any(k in ctx for k in ['tabel', 'table', 'sumber', 'source', 'catatan', 'note', 'perubahan data', 'diakses']): return True

    num_non_years = sum(1 for c in non_empty if parse_num(c) is not None and not is_bps_col_num(str(c).strip()) and not re.match(r'^(?:19|20)\d{2}(?:/\d{4,6})?$', re.sub(r'[\*\^#\s\†\‡]', '', str(c))))
    if num_non_years >= 2:
        return False
        
    if any(k in ctx for k in ['laki-laki', 'perempuan', 'female', 'male', 'jenis kelamin', 'sex', 'pangkat', 'golongan', 'tingkat pendidikan']):
        if not any(parse_num(c) is not None for c in non_empty if not is_bps_col_num(str(c).strip())): return True

    if any(k in ctx_norm for k in ['kabupaten/kota', 'regency/municipality', 'nama kabupaten', 'nama kota', 'ibukota kabupaten', 'capital of regency', 'luas wilayah', 'total area', 'tinggi wilayah', 'jarak ke ibukota', 'area height', 'distance to', 'persentase terhadap', 'percentage to', 'jumlah pulau', 'number of island', 'tepi laut', 'bukan tepi laut', 'coastal', 'non-coastal', 'hak milik', 'hak guna', 'jual beli', 'hibah', 'sekolah', 'schools', 'negeri', 'swasta', 'public', 'private', 'guru', 'teachers', 'murid', 'pupils', 'students', 'pemilih', 'pindah', 'dptb', 'kecamatan', 'desa', 'kelurahan', 'tps', 'organisasi perangkat daerah', 'regional apparatus', 'jenis tanaman', 'kind of plant', 'satuan unit', 'satuan/unit', 'satuan / unit']):
        if not any(parse_num(c) is not None and not is_bps_col_num(str(c).strip()) and not re.match(r'^(?:19|20)\d{2}', str(c).strip()) for c in non_empty): return True
    return False

def rounding_noise(pv, sv):
    """True bila selisih hanya akibat pembulatan desimal atau pembulatan cetak buku BPS.
    Angka bulat (jumlah orang/proyek) tetap harus persis."""
    try:
        d = abs(pv - sv)
        if d <= 0.5 and max(abs(pv), abs(sv)) >= 100 and d / max(abs(pv), abs(sv)) < 1e-4:
            return True
        if d <= 0.05 and (abs(pv - round(sv, 1)) < 0.001 or abs(sv - round(pv, 1)) < 0.001):
            return True
        if d <= 0.02 and (pv != 0 and sv != 0) and not (pv.is_integer() and sv.is_integer()):
            return True
        return False
    except Exception:
        return False

def match_party_name(text):
    if not text: return None
    t = re.sub(r'[\r\n]+', ' ', str(text)).upper()
    t = re.sub(r'^[a-z\W\d_]+\s+', '', t) # strip leading garbage/watermark like 'j\n'
    t = re.sub(r'[^\w\s]', ' ', t)
    t = re.sub(r'\s+', ' ', t).strip()
    
    if 'PDI' in t or 'DEMOKRASI INDONESIA PERJUANGAN' in t: return 'PDI-P'
    if 'PKB' in t or 'KEBANGKITAN BANGSA' in t: return 'PKB'
    if 'GERINDRA' in t or 'GERAKAN INDONESIA RAYA' in t: return 'GERINDRA'
    if 'GOLKAR' in t or 'GOLONGAN KARYA' in t: return 'GOLKAR'
    if 'PKS' in t or 'KEADILAN SEJAHTERA' in t: return 'PKS'
    if 'DEMOKRAT' in t: return 'DEMOKRAT'
    if 'PPP' in t or 'PERSATUAN PEMBANGUNAN' in t: return 'PPP'
    if 'PAN' in t or 'AMANAT NASIONAL' in t: return 'PAN'
    if 'NASDEM' in t: return 'NASDEM'
    if 'PSI' in t or 'SOLIDARITAS INDONESIA' in t: return 'PSI'
    if 'JAWA TENGAH' in t or 'TOTAL' in t or 'JUMLAH' in t: return 'TOTAL'
    return None

def _exactly_equal(a, b):
    """Sama persis; toleransi hanya untuk derau floating-point (1e-9 relatif), bukan selisih data."""
    return a == b or abs(a - b) <= 1e-9 * max(1.0, abs(a), abs(b))


def _candidates(v):
    return (v,) + tuple(getattr(v, 'alts', ()))


def explain_mismatch(pv, sv):
    """Petunjuk (bukan pembenaran) penyebab selisih. String kosong bila tidak ada pola dikenal.
    Selisih TETAP dianggap beda; ini hanya membantu penelusuran."""
    if pv is None or sv is None:
        return ""
    if pv != 0 and sv != 0:
        if abs(sv - pv * 1000) <= 5.0 or abs(pv - sv * 1000) <= 5.0:
            return "pola: beda satuan ribuan (x1000)"
        if abs(sv - pv * 1000000) <= 2000.0 or abs(pv - sv * 1000000) <= 2000.0:
            return "pola: beda satuan jutaan (x1.000.000)"
        r = max(abs(sv), abs(pv)) / min(abs(sv), abs(pv))
        if r >= 9.9:
            import math
            l = math.log10(r)
            if abs(l - round(l)) < 1e-4 and round(l) in (1, 2, 3, 4, 6, 8, 10, 12):
                return f"pola: kelipatan 10^{int(round(l))} (kemungkinan salah format satuan/desimal)"
    if pv > 10 and sv > 10:
        try:
            s_pv, s_sv = str(int(round(pv))), str(int(round(sv)))
            if (len(s_pv) == len(s_sv) + 1 and s_pv[:-1] == s_sv and s_pv[-1] in '123') or \
               (len(s_sv) == len(s_pv) + 1 and s_sv[:-1] == s_pv and s_sv[-1] in '123'):
                return "pola: digit catatan kaki menempel pada angka"
        except (ValueError, OverflowError):
            pass
    if abs(pv - round(sv)) < 0.0001 and abs(pv - sv) < 1.0 or abs(sv - round(pv)) < 0.0001 and abs(pv - sv) < 1.0:
        return "pola: pembulatan ke bilangan bulat"
    if rounding_noise(pv, sv):
        return "pola: pembulatan desimal"
    return ""


def evaluate_cell_match(pv, sv, tolerance=0.0):
    """Mengembalikan (is_match, is_tolerance_match, diff_val) dengan diff_val = sv - pv.

    - Cocok bila angka sama persis, atau sama persis menurut salah satu interpretasi format
      penulisan (mis. '1.234' sebagai 1234 atau 1,234).
    - is_tolerance_match hanya True bila pengguna EKSPLISIT memberi tolerance > 0.
    """
    if pv is None or sv is None:
        return False, False, 0.0

    diff_val = round(sv - pv, 3)

    for a in _candidates(pv):
        for b in _candidates(sv):
            if _exactly_equal(a, b):
                return True, False, diff_val

    if tolerance > 0 and abs(pv - sv) <= tolerance:
        return True, True, diff_val

    return False, False, diff_val

