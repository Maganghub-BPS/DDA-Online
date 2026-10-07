# Laporan Pengembangan Sistem DDA Online (CI4)

Dokumen ini menjelaskan seluruh rangkaian pengembangan, pembaruan fitur, dan perubahan sistem yang telah dilakukan pada aplikasi **DDA Online (Jawa Tengah Dalam Angka)** berbasis CodeIgniter 4.

---

## 1. Ringkasan Sistem

Sistem ini awalnya dirancang untuk mengelola tautan (link) tabel dari Google Sheets. Dalam pengembangan terbaru, sistem telah ditransformasi menjadi lebih mandiri dengan integrasi langsung ke **API Portal Data "Satu Data Jawa Tengah"**, penambahan fitur manajemen tabel massal (Bulk Update), serta perombakan total antarmuka pengguna (UI/UX) ke standar modern dan premium.

---

## 2. Fitur Utama yang Dikembangkan

### A. Integrasi API Portal Data (Satu Data Jawa Tengah)

- **Real-time Data Fetching**: Mengambil data terbaru langsung dari API Portal Data tanpa perlu input manual.
- **Status Tracking API Sync**: Implementasi label status _"Sudah Sinkron dengan Portal Data Jateng"_ pada tabel yang telah terhubung.
- **Multiple ID Integration**: Kemampuan menggabungkan data dari beberapa ID Portal (UUID) ke dalam satu tampilan tabel tunggal.
- **Dynamic Pivot Analysis**: Deteksi otomatis multi-level headers yang rapi dan performa loading data yang optimal.

### B. Mesin Konfigurasi Pivot Tabel (Dynamic Pivot Engine)

- **Drag-and-Drop Dimensioning**: Fleksibilitas penuh bagi pengguna untuk mengatur dimensi (Metrik, Wilayah, Tahun) ke posisi baris atau kolom hanya dengan seret-lepas.
- **Mode "Metrics as Rows"**: Kemampuan mentransformasi data horizontal menjadi format vertikal (Portrait) secara instan, sesuai standar publikasi BPS.
- **Persistent State**: Seluruh preferensi layout, filter, dan label kustom disimpan secara otomatis ke database (`tabel_config`) agar konsisten bagi semua pengguna.

### C. Standarisasi & Normalisasi Data Wilayah

- **Automated Mapping (BPS Standard)**: Mengonversi data wilayah yang tidak seragam dari API menjadi format standar BPS Jawa Tengah (Kode BPS).
- **Fixed Geographical Sorting**: Menjamin urutan baris kabupaten/kota selalu mengikuti standar geografis resmi (3301 - 3376 / dari Cilacap hingga Kota Semarang).
- **Data Integrity**: Sistem secara cerdas mendeteksi dan mengabaikan baris "Total" dari sumber luar untuk digantikan dengan kalkulasi internal (Total Jawa Tengah) yang lebih akurat.

### D. Manajemen Tabel Bilingual & Header Kompleks

- **Bilingual Interface**: Dukungan label Bahasa Indonesia dan Bahasa Inggris untuk judul kolom, metrik, dan rincian data.
- **Auto-Leveling Header**: Kalkulasi otomatis `rowspan` dan `colspan` untuk header bertingkat, menghilangkan kebutuhan koding manual.
- **Interactive Formatting**: Penomoran otomatis (Auto Numbering), kontrol simbol desimal, dan pengaturan tampilan metrik.

### E. Fitur Bulk Update Link Portal

- **Mass Update via CSV**: Pembaruan ribuan link tabel DDA secara skalabel menggunakan file CSV mapping.
- **Preview & Validation**: Halaman konfirmasi sebelum penyimpanan data dengan fitur _manual mapping_ (Select2) jika judul tidak sesuai.

### F. Modernisasi UI/UX (Premium Theme)

- **Bootstrap 5.3.3 Migration**: Migrasi total komponen framework untuk stabilitas dan responsivitas maksimal.
- **Orange System Branding**: Standardisasi warna aksen utama `#FF6D1F` (Orange) pada seluruh elemen interaktif untuk identitas brand yang konsisten.
- **Card-Based & Minimalist Design**: Implementasi layout berbasis kartu yang bersih, modern, dan informatif.
- **Stacked Progress Visualization**: Inovasi pada Laporan Rekap dengan progres bar bertumpuk (Stacked) untuk membedakan porsi data "Sudah Validasi" (Hijau) dan "Sudah Diisi" (Kuning) secara visual.

### G. Keamanan Sistem & Modernisasi Database

- **SQL Injection Prevention**: Refaktorisasi total query database pada `Admin.php` menggunakan *Parameterized Queries* (Parameter Binding) untuk menutup celah keamanan SQL Injection.
- **Legacy Cleanup**: Penghapusan fungsi sanitasi manual yang usang seperti `addslashes()` dan penggantiannya dengan standar database driver CI4 yang lebih aman.
- **Code Optimization**: Pembersihan fitur-fitur yang tidak lagi digunakan (seperti fitur *Import SQL*) untuk merampingkan codebase dan mengurangi titik serangan (attack surface).

---

## 3. Daftar File Baru & Modifikasi Penting

| Nama File                                      | Status       | Fungsi Utama                                                                 |
| :--------------------------------------------- | :----------- | :--------------------------------------------------------------------------- |
| `app/Controllers/Admin.php`                    | **Modified** | Penanganan logic API, fitur Bulk Update, dan pengelolaan konfigurasi tabel.  |
| `app/Views/admin/v_portal_tabel.php`           | **Updated**  | Mesin render utama untuk tabel dinamis, pivot, dan kalkulasi otomatis.       |
| `app/Views/admin/parts/modal_config_tabel.php` | **New**      | Interface konfigurasi pivot, filter tahun, dan pemetaan wilayah (Modal).     |
| `app/Views/admin/v_preview_bulk.php`           | **New**      | UI Preview Bulk Update dengan validasi mapping dan checkbox selektif.        |
| `app/Views/admin/l_master_tabel.php`           | **Modified** | Layout Master Tabel berbasis BS5 dengan integrasi fitur pencarian real-time. |
| `app/Views/admin/login.php`                    | **Modified** | Redesain halaman login dengan gaya minimalis dan modern.                     |
| `app/Views/admin/index.css`                    | **Updated**  | Design System terpusat untuk warna, animasi, dan layout kartu.               |
| `app/Views/admin/view_report.php`             | **Updated**  | Implementasi stacked progress bar dan label persentase ganda yang informatif. |

---

## 4. Detail Teknis (Maintenance)

1.  **Optimasi Library**: Penggunaan Native PHP CSV Handling untuk performa ringan saat memproses ribuan baris data.
2.  **Database Modernization**: Migrasi menyeluruh ke CI4 *Query Builder* dan *Prepared Statements* untuk menjamin integritas dan keamanan data.
3.  **UI Consistency**: Penggunaan CSS Variables untuk manajemen warna bertema orange secara konsisten.
4.  **Error Handling**: Validasi response API yang kuat untuk mencegah sistem _down_ jika layanan eksternal bermasalah.
5.  **Clean Code**: Struktur Controller `Admin.php` telah dirapikan dengan menghapus sisa-sisa kode *legacy* dan parameter yang tidak diperlukan.

---

## 5. Rencana & Panduan Implementasi

1.  **Deployment**: Pastikan variabel `app.baseURL` di `.env` sudah diperbarui saat pindah ke server produksi.
2.  **Validasi Bulk**: Selalu gunakan fitur Preview untuk memastikan keakuratan mapping tabel sebelum sinkronisasi.
3.  **Maintenance Tampilan**: Semua pembaruan UI harus merujuk pada `index.css` untuk menjaga konsistensi tema.

---
---

## 6. Modul Baru: Data Verifier AI (Matching & Audit PDF DDA)

Modul ini dikembangkan untuk mengotomasi audit dan verifikasi rekonsiliasi antara dokumen cetak publikasi BPS (**Provinsi Jawa Tengah Dalam Angka / DDA**) melawan sumber data primer (**Google Spreadsheet** atau **API Satu Data Jawa Tengah**).

### A. Arsitektur & Komponen
1. **Python AI/Data Engine (python_engine/)**:
   - match_pdf.py: Pemindaian cepat nomor dan judul tabel menggunakan PyMuPDF (fitz), regex parsing multi-tingkat (strict & relaxed), pembersihan mojibake, fuzzy matching berbasis RapidFuzz (ambang batas 85%), serta pembuatan cache index halaman (.pdf.index.json) untuk pencarian O(1).
   - compare_table.py: Engine komparasi sel-demi-sel (head-to-head). Mendukung pembacaan Google Spreadsheet CSV publik, API Satu Data Jateng (Bearer Token), parser numerik multi-format (ribuan bertitik, desimal koma, dash/tanda minus OCR, satuan km2/persen), pendeteksi header berjenjang, dan pendeteksi tabel multi-tahun.
   - atch_verify.py: Worker background multi-threading (ThreadPoolExecutor, 3 workers) yang memproses 400+ tabel di sisi server secara ringan dan stabil, dilengkapi mekanisme graceful stop (.stop).
   - export_excel.py: Generator laporan resmi Berita Acara Rekonsiliasi berbasis spreadsheet Excel (.xlsx) dengan penandaan warna (Merah = Beda Nilai, Hijau = Cocok, Biru = Cocok Toleransi, Kuning = Khusus Cetakan PDF).

2. **Controller & Endpoint Backend (pp/Controllers/Admin.php)**:
   - matching_pdf(): Halaman utama modul Data Verifier AI.
   - process_matching_pdf(): Handler upload dan pemindaian awal PDF master.
   - erify_tabel_pdf(): Komparasi langsung satu tabel secara instan.
   - start_batch_worker(): Menjalankan worker validasi massal secara background asynchronous (non-blocking).
   - get_batch_progress(): Endpoint polling ringan pembaca file progress JSON.
   - stop_batch_worker(): Endpoint pembatalan proses validasi massal.
   - export_tabel_excel(): Endpoint unduh berkas Berita Acara Rekonsiliasi Excel.
   - save_batch_status() & get_batch_status(): Penyimpanan status verifikasi per nomor tabel.

3. **User Interface (pp/Views/admin/matching_pdf.php)**:
   - Card status berkas master PDF per tahun anggaran aktif.
   - Card monitoring progress bar real-time saat batch worker berjalan.
   - Live filter & quick filter buttons: Semua, Ada Beda Nilai, Cocok, Belum Cek, API Kosong.
   - Modal detail komparasi head-to-head, visualisasi selisih nilai, disposisi status database, dan unduh Excel.

4. **Strategi Penyimpanan (Deterministic Master & Zero-DB Changes)**:
   - File master PDF disimpan terpusat per tahun anggaran: writable/uploads/dda_master_{tahun}.pdf.
   - Riwayat hasil verifikasi disimpan pada writable/uploads/dda_state_{tahun}.json sehingga data audit abadi (persistent) tanpa perlu menambah tabel atau kolom baru di database MySQL.
   - Garbage collector otomatis membersihkan berkas temporer tak bertuan yang berusia lebih dari 24 jam.
