# MASTER PROMPT: SPESIFIKASI & PROTOKOL ENGINE PENCOCOKAN TABEL (HEAD-TO-HEAD MATCHING)
**Sistem:** DDA Online (CodeIgniter 4 + Python Engine)  
**Target:** Eliminasi False Positive `pdf_only` ("Hanya ada di PDF"), Koreksi Penjajaran Kolom, dan Presisi Ekstraksi Multi-Tahun untuk **API Satu Data** & **Tabel Dinamis**.

---

## 1. LATAR BELAKANG & TUJUAN UTAMA

Sistem DDA Online memverifikasi kesesuaian data antara cetakan buku publikasi (*PDF Master BPS*) dan *database sumber* (berupa **Tabel Dinamis internal** atau **API Satu Data Jawa Tengah**).

Saat ini masih terjadi anomali kritis:
1. **Data Ada di Kedua Sisi Tetapi Masuk ke "Hanya ada di PDF" (`pdf_only`)**: Angka dan baris tercetak di PDF dan juga tersimpan di database, tetapi engine gagal menjodohkan (*mismatch mapping*), sehingga data PDF terisolasi ke tab `pdf_only` alih-alih dicocokkan sebagai `matches` atau `diffs`.
2. **Pencocokan Salah Tahun / Salah Halaman (Multi-Tahun)**: Tabel PDF memuat deret tahun (misal 2024 dan 2025 di halaman terpisah), sementara database hanya memuat tahun tertentu (misal 2025). Engine salah mengambil data tahun 2024 di PDF untuk dibandingkan dengan 2025 di DB, memicu puluhan selisih data palsu.
3. **Misalignment Kolom & Nama Metrik Ambigu**: Kolom API yang berupa *snake_case* mentah (`jumlah_pns_lakilaki`) tidak terpetakan ke kolom PDF (`Laki-Laki`), atau urutan kolom tertukar sehingga nilai numerik diadu dengan kolom yang salah.
4. **Salah Klasifikasi Wilayah Jawa Tengah**: Kabupaten dan Kota dengan nama kembar (*Magelang, Pekalongan, Tegal*) salah dideteksi sebagai duplikat baris, memicu *false positive* yang merusak tabel regional 35 Kab/Kota menjadi tabel kategorikal acak.

**Target Utama Prompt Ini:**  
Membangun dan menegakkan aturan perbandingan cerdas, deterministik, dan generik agar seluruh data yang ada di database dan PDF **wajib berjodoh 100% secara akurat**, tanpa tambal-sulam khusus per nomor tabel (*no hardcoding hack*).

---

## 2. ATURAN PENCOCOKAN DATA UNTUK API SATU DATA

Setiap endpoint API Satu Data mengembalikan data dalam format `List of JSON Object` (flat dict). Engine wajib menjalankan normalisasi generik berbasis peran skema (*Schema Role Profiling*):

### 2.1. Penentuan Tahun Target (Target Year Synchronization)
1. **Ekstraksi Tahun Sumber:** Engine wajib membaca metadata tahun dari payload API: `tahun_data`, `tahun`, `periode`, atau `waktu`.
2. **Pencocokan Halaman PDF Berbasis Tahun:**
   - Jika data API memuat satu tahun tunggal (misal `2025`), engine **HANYA** boleh membandingkan dengan halaman atau blok kolom PDF yang bertanda tahun `2025`.
   - Halaman atau kolom PDF untuk tahun historis (misal `2022`, `2023`, `2024`) yang **tidak dimuat di API** secara sah dipisahkan ke tab `Khusus Cetakan PDF` (`pdf_only`) dengan catatan informatif yang jelas:  
     `"Data historis (Tahun {Y}) tercetak di buku PDF, tidak tersedia di API sumber data"`.
3. **Larangan:** Dilarang keras menggabungkan baris PDF antar-tahun berbeda jika API hanya memuat data 1 tahun tunggal!

### 2.2. Sanitasi & Pembersihan Nama Kolom/Metrik (Human-Readable Metrics)
1. **Label Kolom Bersih:** Jangan biarkan nama kolom *snake_case* API muncul di antarmuka verifikasi. Terapkan pemetaan semantik otomatis:
   - `.*lakilaki.*` / `.*male.*` $\rightarrow$ `Laki-Laki`
   - `.*perempuan.*` / `.*female.*` $\rightarrow$ `Perempuan`
   - `jumlah_.*` / `total_.*` $\rightarrow$ `Jumlah`
   - `nama_indikator` / `variabel` $\rightarrow$ Gunakan Title Case yang bersih.
2. **Pensejajaran Kolom Semantik (Column Alignment):**  
   Petakan kolom API ke kolom PDF menggunakan kecocokan makna (*semantic keyword match*), bukan semata-mata indeks numerik kolom:
   - Kolom Metrik 1 API (`Laki-Laki`) $\leftrightarrow$ Kolom 1 PDF (`Laki-Laki / Male`)
   - Kolom Metrik 2 API (`Perempuan`) $\leftrightarrow$ Kolom 2 PDF (`Perempuan / Female`)
   - Kolom Metrik 3 API (`Jumlah`) $\leftrightarrow$ Kolom 3 PDF (`Jumlah / Total`)

### 2.3. Rekonstruksi Layout API Menjadi Matriks Tabular Generik
Dukung 5 Layout Arsitektur API:
1. **Layout Regional (35 Kab/Kota):** Baris berupa entitas wilayah, kolom berupa metrik/tahun.
2. **Layout Cross-Tabulation Pivot (Kategori x Sub-Kategori / Gender):** Misal Partai Politik x Jenis Kelamin, Kelompok Umur x Gender.
3. **Layout Time-Series Pivot (Multi-Tahun Horizontal):** Kolom tahun `2022, 2023, 2024, 2025` hasil pivot dari record vertikal API.
4. **Layout Flat Multi-Metric Categorical:** Baris berupa kategori (Golongan PNS, Jenis Izin PMA), kolom berupa metrik numerik.
5. **Layout Temporal Rows:** Baris berupa deret tahun anggaran, kolom berupa nilai realisasi/target.

---

## 3. ATURAN PENCOCOKAN DATA UNTUK TABEL DINAMIS

Tabel Dinamis menyimpan data matriks 2D berstruktur sel (`dynamic_grid`), lengkap dengan koordinat baris-kolom internal (`id_baris_item`, `id_karakteristik_item`, `id_periode_item`).

### 3.1. Penanganan Standar 35 Kabupaten/Kota Jawa Tengah
1. **Urutan Baku BPS Jawa Tengah:**
   - Baris 1 s.d. 29: **Kabupaten** (1. Cilacap s.d. 29. Brebes).
   - Baris 30 s.d. 35: **Kota** (1. Magelang s.d. 6. Tegal).
2. **Pembedaan Kota vs Kabupaten (No Duplicate False-Alarm):**
   - Nama *Magelang, Pekalongan, Tegal* muncul 2 kali dalam daftar karena satu berstatus Kabupaten (`33.08, 33.26, 33.28`) dan satu berstatus Kota (`33.71, 33.75, 33.76`).
   - Engine **WAJIB** melacak state `is_kota_mode` (ketika menemukan sub-header "Kota/Municipality", atau ketika penomoran baris reset ke `1` setelah Brebes).
   - **DILARANG** menganggap kemunculan nama Magelang kedua sebagai tabel kategorikal bertingkat! Tabel ini tetaplah **Tabel Regional 35 Kab/Kota**.
3. **Penyelarasan Nama Wilayah:**
   - Hilangkan nomor urut awalan (`1. `, `01. `).
   - Samakan variasi: `Kabupaten Cilacap` == `Kab. Cilacap` == `Cilacap`.
   - Samakan variasi: `Kota Surakarta` == `Surakarta` == `Solo`.

### 3.2. Penanganan Multi-Karakteristik & Multi-Tahun Horizontal
1. Jika Tabel Dinamis memuat multi-karakteristik (misal: Satuan Pendidikan, Kepala Sekolah/Pendidik, Peserta Didik) yang masing-masing memiliki 2 periode (`2024/2025` dan `2025/2026`), kolom di PDF sering kali disusun berdampingan.
2. Engine wajib membedah multi-level header PDF untuk membentuk kunci metrik komposit:  
   `{Karakteristik} ({Periode})` $\rightarrow$ Contoh: `Kepala Sekolah dan Pendidik (2024/2025)`.
3. Jodohkan setiap sel berdasarkan kesamaan pasangan metrik dan tahun, bukan hanya berdasarkan posisi indeks kolom mentah!

---

## 4. PROTOKOL ELIMINASI FALSE POSITIVE "HANYA ADA DI PDF" (`pdf_only`)

Suatu data **HANYA BOLEH** masuk ke tab `pdf_only` jika memenuhi salah satu kriteria valid berikut:
1. **Tahun Historis Murni:** Kolom/halaman PDF memuat data tahun lama yang secara faktual memang tidak disediakan di database sumber.
2. **Rekapitulasi Total Provinsi:** Baris `Total Jawa Tengah / Jawa Tengah` tercetak di PDF tetapi tidak disimpan sebagai baris individu di database.
3. **Catatan / Kolom Tambahan:** Kolom persentase atau rasio yang dihitung khusus untuk buku cetak dan tidak ada di tabel referensi sumber.

### Anti-Misclassification Checklist (Pencegah Salah Masuk `pdf_only`):
* [ ] **Pemeriksaan Normalisasi Baris:** Sebelum membuang baris PDF ke `pdf_only`, lakukan pencarian fuzzy berbobot (*Fuzzy Token Sort Ratio $\ge 85$*) terhadap semua entitas baris di database.
* [ ] **Pemeriksaan Header Metadata:** Baris judul, baris sub-header (`Kabupaten/Regency`, `Laki-laki/Male`), dan baris sumber/catatan kaki **DILARANG** dianggap sebagai baris data PDF yang hilang! Baris-baris ini harus di-filter oleh `is_header_or_metadata_row`.
* [ ] **Pemeriksaan Indentasi Kategori:** Pada tabel hierarkis (misal jenis pengeluaran), baris anak yang menjorok ke dalam harus digabungkan dengan label induknya agar cocok dengan string deskripsi di database.

---

## 5. STANDAR PERBANDINGAN NILAI NUMERIK (TOLERANSI & FORMAT)

1. **Pembersihan String Numerik:**
   - Titik ribuan Indonesia (`1.234,56`) wajib dikonversi benar menjadi `1234.56`.
   - Strip (`-`, `–`, `—`), titik tiga (`...`), atau `None`/`null` di database jika bertemu angka 0 atau strip di PDF harus diperlakukan setara (*Match* dengan catatan nilai kosong/nol).
2. **Toleransi Desimal:**
   - Toleransi default: `0.0` (Persis).
   - Jika terdapat perbedaan pembulatan cetak (misal di DB `2325.173` vs di PDF `2325.2` atau `2325`), tampilkan selisih nilai secara transparan beserta penjelasan petunjuk pembulatan (*rounding difference*).

---

## 6. LANGKAH VERIFIKASI & BENCHMARK UJI KELAYAKAN

Sebelum sebuah revisi kode dinyatakan selesai, wajib dilakukan live verification terhadap tabel-tabel acuan berikut:

```bash
# Perintah Verifikasi Live Test Python Engine
python -c "
import sys
sys.path.insert(0, 'python_engine')
from compare_table import compare_head_to_head

pdf_path = 'writable/uploads/dda_master_2026.pdf'
db_path = 'writable/uploads/dda_db_2026.json'

benchmarks = ['1.1.1', '2.3.9', '4.1.2', '2.3.3', '2.4.2', '2.2.1']
for tid in benchmarks:
    res = compare_head_to_head(pdf_path, tid, db_path, tolerance=0.0)
    s = res.get('summary', {})
    print(f'Tabel {tid}: Matches={s.get(\"total_matches\")}, Diffs={s.get(\"total_diffs\")}, PDF_Only={s.get(\"total_pdf_only\")}')
"
```

### Kriteria Kelulusan Mutlak (Success Gates):
1. **Tabel 1.1.1 (Tabel Dinamis - Luas Wilayah):**
   - `Matches = 108`, `Diffs = 0`, `PDF_Only = 0`.
2. **Tabel 4.1.2 (Tabel Dinamis - Satuan Pendidikan RA):**
   - `Matches = 108`, `Diffs = 0`. Tidak boleh ada badge `Kabupaten (Cilacap)`, melainkan `Kab. Cilacap`.
3. **Tabel 2.3.9 (API Satu Data - PNS Menurut Golongan):**
   - `Diffs = 0`. Badge wilayah bersih `Golongan II/A` (tanpa prefix `Perempuan`).
   - Nama metrik tampil manusiawi (`Laki-Laki`, `Perempuan`, `Jumlah`), bukan `jumlah_pns_lakilaki`.
   - Data 2024 terpisah rapi ke `pdf_only` (hanya halaman 2025 yang dicocokkan ke API 2025).
4. **Tabel 2.3.3 (Tabel Dinamis - PNS Menurut Tingkat Pendidikan):**
   - `Matches = 102`, `Diffs = 0`.
5. **Tabel 2.4.2 (API Satu Data - Belanja Daerah Multi-Tahun):**
   - `Matches = 19`, `Diffs = 0`. Data tahun lampau masuk tab PDF Only.
6. **Unit Test Suite:**
   - 27 unit tests di `python_engine/tests` wajib lulus 100% (`OK`).
