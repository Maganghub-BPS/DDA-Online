# FORM CHECKLIST PENGUJIAN BLACK BOX LENGKAP (END-TO-END QA)
## SISTEM DDA ONLINE - SELURUH FITUR APLIKASI SECARA KESELURUHAN
**Badan Pusat Statistik (BPS) Provinsi Jawa Tengah**

---

### 📋 Informasi Dokumen & Lingkungan Uji

| Parameter | Keterangan / Nilai |
| :--- | :--- |
| **Nama Aplikasi** | DDA Online (Jawa Tengah Dalam Angka) - Berbasis CodeIgniter 4 |
| **Cakupan Pengujian** | Seluruh Sistem (Autentikasi, Dashboard, Master Tabel, Master Dimensi, Generator Matriks, Penomoran Cerdas, View Publik/Admin, Impor/Ekspor Excel, Input Data OPD, Matching API/PDF, Forum Diskusi, & Laporan Rekap) |
| **Metode Pengujian** | Black Box Testing (Boundary Value Analysis, Equivalence Partitioning, Scenario-based Flow, Role-Based Access Control) |
| **Base URL** | `http://localhost:8080` |
| **Tanggal Pengujian** | `____ / ________ / 2026` |
| **Nama Penguji (Tester)** | `_______________________________` |
| **Peran / Akun Uji** | [ ] Super Admin BPS &nbsp;&nbsp;&nbsp; [ ] Admin Bidang &nbsp;&nbsp;&nbsp; [ ] Diseminasi &nbsp;&nbsp;&nbsp; [ ] Walidata / OPD |
| **Browser / Perangkat** | [ ] Google Chrome &nbsp;&nbsp;&nbsp; [ ] Microsoft Edge &nbsp;&nbsp;&nbsp; [ ] Mozilla Firefox &nbsp;&nbsp;&nbsp; Resolusi: `1920x1080` / `Laptop` |

---

### 📊 Ringkasan Eksekusi Pengujian Sistem Keseluruhan

| No. | Modul Sistem | Total Kasus | Pass (Lolos) | Fail (Gagal) | Blocked / N/A | Persentase |
| :---: | :--- | :---: | :---: | :---: | :---: | :---: |
| 1 | Autentikasi, Hak Akses (RBAC) & Manajemen Akun | 6 | | | | % |
| 2 | Dashboard & Monitoring Progres Data | 4 | | | | % |
| 3 | Master Tabel DDA & Pengelolaan Tahun Data | 8 | | | | % |
| 4 | Master Dimensi Tabel (Kamus Baris, Kolom, Periode, Satuan) | 6 | | | | % |
| 5 | Generator & Editor Konfigurasi Matriks Dinamis | 8 | | | | % |
| 6 | Penomoran Baris & Kolom Cerdas (*Smart Numbering*) | 8 | | | | % |
| 7 | Viewer Tabel Dinamis (Admin & Frontend Publik BPS) | 10 | | | | % |
| 8 | Unduh & Unggah Template Excel Berstandar BPS | 8 | | | | % |
| 9 | Alur Input Data DDA & Verifikasi Walidata OPD | 6 | | | | % |
| 10 | Sinkronisasi API Portal & Matching PDF (Python Engine) | 6 | | | | % |
| 11 | Forum Diskusi & Konsultasi Antar Instansi | 4 | | | | % |
| 12 | Laporan Rekapitulasi & Master Pengguna/OPD/Tim | 6 | | | | % |
| | **TOTAL KESELURUHAN SISTEM** | **80** | | | | **%** |

*Status Akhir Rilis:* &nbsp;&nbsp; `[ ] SIAP RILIS (PRODUCTION READY)` &nbsp;&nbsp;&nbsp;&nbsp; `[ ] PERLU PERBAIKAN (BUGFIX REQUIRED)`

---

## 🧪 Detail Checklist Pengujian Black Box per Modul

---

### Modul 1: Autentikasi, Hak Akses (RBAC) & Manajemen Akun

Pengujian alur masuk, proteksi halaman, perbedaan privilege peran (BPS vs OPD), dan pengelolaan sesi tahun aktif.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-AUTH-01** | Login Berhasil (Akun BPS) | Akses `admin/login`, masukkan username & password akun Admin BPS yang valid. | Username: `admin`, Password valid | Sistem berhasil masuk dan mengarahkan langsung ke halaman Dashboard Admin. | | |
| [ ] | **TC-AUTH-02** | Login Berhasil (Akun OPD) | Akses `admin/login`, masukkan kredensial akun OPD / Walidata dinas. | Akun salah satu OPD dinas | Berhasil masuk; sidebar hanya menampilkan menu hak akses OPD (Input Data, Forum, Ganti Password). | | |
| [ ] | **TC-AUTH-03** | Login Gagal (Kredensial Salah) | Masukkan password yang salah pada form login. | Password acak | Muncul flash alert peringatan `Username atau password salah!`, tetap berada di halaman login. | | |
| [ ] | **TC-AUTH-04** | Proteksi URL Tanpa Sesi (Auth Guard) | Buka tab browser baru (*incognito*), langsung akses URL `admin/dda` atau `admin/master_tabel`. | URL internal tanpa login | Sistem otomatis me-redirect ke halaman `admin/login`. | | |
| [ ] | **TC-AUTH-05** | Switcher Tahun Data Aktif | Klik dropdown tahun di navbar atas, pilih tahun lain (misal: `2025` $\rightarrow$ `2026`). | Pilihan tahun data | Seluruh data tabel, rekap, dan filter otomatis menyesuaikan dengan tahun aktif yang dipilih. | | |
| [ ] | **TC-AUTH-06** | Logout Sistem | Klik profil kanan atas -> klik menu **Logout**. | Tombol logout | Sesi dihancurkan (*session destroy*), pengguna diarahkan kembali ke form login. | | |

---

### Modul 2: Dashboard & Monitoring Progres Data

Pengujian visualisasi statistik pengumpulan data DDA, kartu progres, dan ringkasan status tabel.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-DSH-01** | Kartu Ringkasan Status Tabel | Buka menu `admin/` (Dashboard). Periksa kartu metrik ringkasan. | Halaman dashboard | Menampilkan angka: Total Tabel, Tabel Terisi, Belum Diisi, Menunggu Validasi, dan Validasi Disetujui. | | |
| [ ] | **TC-DSH-02** | Diagram / Grafik Progres Pengumpulan | Periksa grafik progres pengumpulan data per unit kerja OPD. | Grafik pada dashboard | Grafik batang/donat ter-render interaktif dan persentasenya sesuai data riil. | | |
| [ ] | **TC-DSH-03** | Filter Progres per Tahun | Ganti tahun aktif melalui navbar saat berada di dashboard. | Tahun 2024 vs 2025 | Angka total tabel dan persentase keterisian data pada dashboard otomatis diperbarui sesuai tahun terpilih. | | |
| [ ] | **TC-DSH-04** | Navigasi Pintas dari Dashboard | Klik salah satu kartu ringkasan atau tautan tabel di dashboard. | Kartu metrik | Mengarahkan langsung ke halaman daftar tabel terkait sesuai status yang diklik. | | |

---

### Modul 3: Master Tabel DDA & Pengelolaan Tahun Data

Pengujian pengelolaan metadata tabel, jenis sumber data (Dinamis, Portal, Spreadsheet), pencarian AJAX, dan kloning tahunan.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-MT-01** | Visualisasi Jenis Sumber Data | Buka menu `admin/master_tabel`, periksa badge pada kolom sumber data. | Daftar Master Tabel | • Tabel Dinamis berlabel ungu `TABEL DINAMIS`.<br>• Tabel Portal berlabel biru `PORTAL DATA`.<br>• Tabel Spreadsheet berlabel hijau `SPREADSHEET`. | | |
| [ ] | **TC-MT-02** | Live Search (Pencarian Cepat AJAX) | Ketik nama judul tabel di kotak pencarian kanan atas. | Kata kunci: `Produksi` | Tabel memfilter data seketika tanpa reload halaman penuh (debounce 450ms). | | |
| [ ] | **TC-MT-03** | Filter Bidang & OPD | Pilih instansi OPD pada dropdown filter, klik **Filter**. | Filter instansi OPD | Hanya tabel milik instansi yang dipilih yang ditampilkan; tombol reset mengembalikan ke semua data. | | |
| [ ] | **TC-MT-04** | Tambah Master Tabel Baru (Dinamis) | Klik **Tambah Data**, pilih opsi format *Tabel Dinamis*, isi judul dan unit kerja, simpan. | Form Tambah Tabel | Entri tersimpan dengan flag `is_dynamic = 1` dan langsung berlabel ungu di daftar tabel. | | |
| [ ] | **TC-MT-05** | Edit Metadata Tabel | Klik menu baris `...` -> **Edit Detail**, ubah judul dwi-bahasa atau no tabel. | Form Edit Tabel | Perubahan tersimpan tanpa mengubah relasi ID tabel. | | |
| [ ] | **TC-MT-06** | Rapikan Urutan Nomor Tabel | Klik dropdown **Pengelolaan Tahun** -> **Rapikan Urutan**. | Seluruh tabel tahun aktif | Format nomor tabel tersusun rapi secara hierarki bab/seksi (misal: `5.4.1`, `5.4.2`, dst.). | | |
| [ ] | **TC-MT-07** | Duplikasi Tahunan (Kloning Tabel) | Jalankan **Duplikasi Tahun** dari tahun berjalan ke tahun berikutnya. | Sumber: 2025, Tujuan: 2026 | Master tabel dan konfigurasi dimensi tabel dinamis tersalin ke tahun baru dengan nilai sel dikosongkan. | | |
| [ ] | **TC-MT-08** | Hapus Tabel (Safe Delete Guard) | Hapus salah satu tabel uji coba melalui tombol `...` -> **Hapus**. | Tabel uji coba | Muncul modal konfirmasi; entri tahunan terhapus aman tanpa merusak kamus master tabel lain. | | |

---

### Modul 4: Master Dimensi Tabel (Kamus Baris, Kolom, Periode, Satuan)

Pengujian kamus dimensi terpusat sebagai fondasi perakitan matriks dinamis DDA.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-MD-01** | Tab Navigasi Dimensi | Buka `admin/master_dimensi`, berpindah tab: Karakteristik, Judul Baris, Periode, Satuan. | Halaman Master Dimensi | Navigasi tab berjalan mulus tanpa error JavaScript; badge jumlah item terhitung akurat. | | |
| [ ] | **TC-MD-02** | Tambah Kelompok Dimensi Baru | Klik **Tambah Kelompok Baru**, isi nama kelompok dwi-bahasa, simpan. | Nama kelompok baru | Kelompok baru muncul di daftar tabel dan siap digunakan untuk konfigurasi tabel dinamis. | | |
| [ ] | **TC-MD-03** | Deteksi Duplikasi Real-time | Pada modal tambah kelompok, ketikkan nama kelompok yang sudah ada di database. | Nama kelompok existing | Muncul peringatan inline bahwa nama kelompok sudah digunakan. | | |
| [ ] | **TC-MD-04** | Tambah Item Berjenjang (Hierarki) | Buka rincian item, tambahkan sub-item dengan memilih parent item. | Karakteristik bertingkat | Struktur pohon (*tree structure*) tersusun rapi dengan indentasi level yang benar. | | |
| [ ] | **TC-MD-05** | Edit Item Dimensi | Ubah label teks bahasa Indonesia atau bahasa Inggris pada salah satu item dimensi. | Item dimensi aktif | Perubahan label tersimpan dan otomatis terefleksi pada tabel dinamis yang menggunakannya. | | |
| [ ] | **TC-MD-06** | Proteksi Hapus Dimensi Terpakai | Coba hapus kelompok dimensi yang sedang dipakai oleh salah satu tabel dinamis aktif. | Kelompok ID 36 | Sistem menolak penghapusan dan menampilkan alert peringatan foreign key protection. | | |

---

### Modul 5: Generator & Editor Konfigurasi Matriks Dinamis (`admin/generator_tabel`)

Pengujian fungsionalitas perakitan matriks multidimensi oleh Admin BPS.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-GN-01** | Akses Generator dari Master & View | Klik tombol **Konfigurasi Tabel Dinamis** dari halaman view tabel atau master tabel. | Tabel Dinamis | Halaman konfigurasi terbuka dengan dropdown dimensi terisi sesuai konfigurasi aktif. | | |
| [ ] | **TC-GN-02** | Proteksi Hak Akses (Non-BPS) | Akses URL `admin/generator_tabel/{id}` menggunakan sesi login OPD non-BPS. | Akun OPD Dinas | Sistem menolak akses dan me-redirect ke halaman aman. | | |
| [ ] | **TC-GN-03** | Pratinjau Matriks Nyata Seketika | Ganti pilihan dropdown **Judul Baris** atau **Karakteristik Kolom**. | Pilihan dimensi baru | Kontainer *Pratinjau Matriks Nyata* langsung merender matriks baru tanpa reload halaman. | | |
| [ ] | **TC-GN-04** | Switcher Posisi Hirarki | Ganti opsi **Posisi Hirarki** antara `Kolom di Atas` dan `Periode di Atas`. | Tabel 3 Dimensi | Hierarki header kolom atas dan bawah bertukar posisi secara akurat pada pratinjau. | | |
| [ ] | **TC-GN-05** | Switcher Tampilan Baris | Ganti segmented button ke `2 Kolom Rincian`. | Tabel multi-level | Struktur baris terbagi menjadi 2 kolom (Kolom Induk & Kolom Rincian). | | |
| [ ] | **TC-GN-06** | Validasi Minimal 2 Dimensi | Kosongkan dimensi karakteristik kolom dan periode, klik **Simpan Matriks**. | 1 dimensi terpilih | Sistem memunculkan notifikasi validasi bahwa minimal 2 dimensi wajib dipilih. | | |
| [ ] | **TC-GN-07** | Simpan Konfigurasi Matriks | Pilih konfigurasi dimensi lengkap, klik **Simpan Matriks**. | Konfigurasi valid | Konfigurasi tersimpan ke `m_indikator_tabel`, notifikasi sukses muncul, pratinjau matriks terkunci. | | |
| [ ] | **TC-GN-08** | Tombol Lepas Konfigurasi | Klik tombol **Lepas** pada tabel dinamis aktif, lalu konfirmasi. | Tabel aktif | Konfigurasi dimensi dilepas dan tabel kembali ke status belum terkonfigurasi. | | |

---

### Modul 6: Penomoran Baris & Kolom Cerdas (*Smart Numbering System*)

Pengujian aturan penomoran: Kolom `No.` dibuat otomatis jika master baris bernomor, ditiadakan jika tanpa nomor, serta nomor kolom BPS `(1), (2), (3)...`.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-NO-01** | Auto-Create Kolom No. (Master Bernomor) | Buka view tabel yang item master barisnya diawali nomor (`1. `, `2. `). | Tabel ID 562 (`Produksi Hasil Hutan`) | Kolom **No.** otomatis hadir di paling kiri header dan berisi nomor `1.`, `2.`, s/d `8.`. | | |
| [ ] | **TC-NO-02** | Pembersihan Teks Baris (Cegah Dobel No) | Periksa nama rincian baris di samping kolom `No.`. | Tabel ID 562 | Teks nama baris bersih tanpa angka terulang (tampil `Kayu Pertukangan Jati`, bukan `1. Kayu...`). | | |
| [ ] | **TC-NO-03** | Auto-Hide Kolom No. (Master Tanpa Nomor) | Buka view tabel yang master barisnya tidak memiliki nomor urut. | Tabel master `Makanan`, `Bulan`, `Kelompok Umur` | Kolom **No.** otomatis tidak dibuat; tidak ada kolom kosong atau sisa header `No.`. | | |
| [ ] | **TC-NO-04** | Baris Kategori Tanpa Nomor | Periksa baris kategori pada tabel hierarki bertingkat. | Baris kategori induk | Baris kategori membentang penuh (*colspan*) dan tidak diberi nomor urut angka. | | |
| [ ] | **TC-NO-05** | Baris Grand Total Tanpa Nomor | Periksa baris `Jumlah / Total` pada bagian bawah tabel dinamis. | Baris Grand Total | Kolom No. di-merge dengan label Total; tidak muncul nomor urut biasa pada baris total. | | |
| [ ] | **TC-NO-06** | Penomoran Kolom Peach BPS `(1), (2), ...` | Periksa baris nomor kolom berlatar peach di thead tabel. | Baris `num-row` | Header nomor berlatar `#f9a066` dengan angka kurung `(1)`, `(2)`, `(3)`... presisi di setiap kolom. | | |
| [ ] | **TC-NO-07** | Ketepatan Kolom `(1)` vs Rowspan `No.` | Periksa posisi nomor kolom `(1)` terhadap kolom judul baris. | Tabel dengan kolom No. aktif | Header `No.` menyatu rapi (`rowspan`) dan nomor `(1)` tepat berada di bawah judul baris pertama. | | |
| [ ] | **TC-NO-08** | Paritas Frontend Publik vs Admin | Bandingkan tampilan nomor baris di Admin dan Frontend Publik. | URL Admin vs Frontend | Format penomoran kolom `No.` dan baris data 100% identik di kedua sisi. | | |

---

### Modul 7: Viewer Tabel Dinamis (Admin & Frontend Publik BPS)

Pengujian estetika publikasi resmi BPS (warna oranye, dwi-bahasa, format ribuan, desimal, dan mode cetak).

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-VW-01** | Kotak Judul BPS Dwi-Bahasa | Periksa kotak header di atas tabel utama. | Tabel ID 562 | • Kotak kiri: Teks `Tabel / Table` dan nomor tabel (misal: `5.4.10`).<br>• Kotak kanan: Judul Indonesia (tebal) dan judul Inggris (*italic*). | | |
| [ ] | **TC-VW-02** | Header Oranye BPS `#FF6D1F` | Periksa warna latar dan batas header tabel. | View tabel dinamis | Latar header oranye resmi BPS `#FF6D1F`, teks putih tebal, garis pemisah `1px solid #fff`. | | |
| [ ] | **TC-VW-03** | Tampilan Zebra Striping Lembut | Periksa latar baris ganjil dan genap pada tabel data. | Seluruh baris data | Baris selang-seling warna putih `#ffffff` dan peach lembut `#fff4eb`. | | |
| [ ] | **TC-VW-04** | Judul Baris Kiri Selalu Tebal (*Bold*) | Periksa ketebalan font kolom nama baris/wilayah. | Kolom judul baris | Teks judul baris kiri tercetak tebal (*bold*), selaras dengan portal publikasi BPS. | | |
| [ ] | **TC-VW-05** | Format Pemisah Ribuan (Titik) | Periksa sel data yang berisi angka ribuan atau jutaan. | Angka: `138159`, `243962` | Tampil dengan titik pemisah ribuan BPS (`138.159` dan `243.962`). | | |
| [ ] | **TC-VW-06** | Penanganan Nilai Kosong (Strip `–`) | Periksa sel data yang bernilai `NULL` atau belum diisi. | Sel tanpa nilai | Tampil karakter strip tengah panjang (`–`), rata kanan/tengah, bukan kosong melompong. | | |
| [ ] | **TC-VW-07** | Baris Grand Total Oranye BPS | Gulir ke baris paling bawah tabel dinamis. | Baris Grand Total | Baris total berlatar oranye `#FF6D1F` penuh, teks tebal putih, angka penjumlahan akurat. | | |
| [ ] | **TC-VW-08** | Catatan & Sumber Publikasi | Periksa footer di bawah tabel. | Catatan & Sumber | Menampilkan *Catatan/Note* dan *Sumber/Source* resmi secara rapi. | | |
| [ ] | **TC-VW-09** | Filter Dropdown Periode / Tahun | Pilih salah satu tahun pada dropdown toolbar atas. | Pilihan tahun data | Kolom periode yang dipilih tersaring cepat dan mulus. | | |
| [ ] | **TC-VW-10** | Tampilan Mode Cetak (*Print Media*) | Tekan `Ctrl + P` pada keyboard pada halaman view tabel. | Print Preview browser | Toolbar, tombol, sidebar, dan navbar disembunyikan; tabel tercetak rapi sesuai standar kertas. | | |

---

### Modul 8: Unduh & Unggah Template Excel Berstandar BPS

Pengujian berkas Excel (`.xlsx`), proteksi sel input, validasi upload, dan penyimpanan data ke database.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-EX-01** | Unduh Template Excel dari Toolbar | Klik tombol **UNDUH TEMPLATE** di toolbar view tabel dinamis. | Tabel ID 562 | Berkas `.xlsx` terunduh otomatis dengan nama file deskriptif sesuai judul tabel. | | |
| [ ] | **TC-EX-02** | Struktur Header Template Excel | Buka berkas `.xlsx` hasil unduhan di Microsoft Excel / LibreOffice. | File template unduhan | Header bertingkat dwi-bahasa, warna oranye/putih selaras dengan tampilan web. | | |
| [ ] | **TC-EX-03** | Proteksi Sel (*Locked vs Unlocked*) | Coba ketik teks pada sel judul dan label header di Excel. | Sheet utama template | Sel header berstatus *Locked* (terproteksi), sel input angka terbuka (*Unlocked*). | | |
| [ ] | **TC-EX-04** | Hidden Sheet Mapping Metadata | Periksa sheet worksheet di aplikasi Excel. | Worksheet Excel | Sheet mapping ID sel disembunyikan (*hidden*) aman dari manipulasi pengguna. | | |
| [ ] | **TC-EX-05** | Akses Modal Upload Excel | Klik tombol **UPLOAD EXCEL** pada toolbar view tabel. | Akun Admin / Walidata | Modal popup `Upload Berkas Excel Tabel Dinamis` terbuka responsif. | | |
| [ ] | **TC-EX-06** | Validasi Ekstensi Non-Excel | Unggah file berekstensi selain `.xlsx` (misal: `.pdf`, `.docx`). | File `dokumen.pdf` | Sistem menolak unggahan dengan pesan validasi bahwa hanya format `.xlsx` yang diizinkan. | | |
| [ ] | **TC-EX-07** | Sukses Impor Nilai Sel Excel | Masukkan angka ke template Excel, simpan, lalu unggah via modal. | File `.xlsx` terisi data | Muncul flash message sukses berisi jumlah sel yang berhasil disimpan ke `t_nilai_dinamis`. | | |
| [ ] | **TC-EX-08** | Pembaruan Tampilan Pasca Upload | Refresh halaman view tabel dinamis setelah upload berhasil. | Halaman view tabel | Angka-angka yang baru diunggah langsung tampil di sel matriks dengan pemisah ribuan yang benar. | | |

---

### Modul 9: Alur Input Data DDA & Verifikasi Walidata OPD

Pengujian alur kerja penyerahan data oleh instansi OPD dinas dan pengawasan verifikasi oleh Admin BPS.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-OP-01** | Navigasi Judul Tabel Dinamis OPD | Login sebagai OPD, buka daftar tabel dinas (`admin/dda/view_tabel?id={id_opd}`). | Menu Input Data DDA | Mengklik judul tabel dinamis langsung membuka view tabel dinamis (bukan spreadsheet kosong). | | |
| [ ] | **TC-OP-02** | Indikator Status Belum Diisi | Periksa baris tabel dinamis yang belum pernah diunggah nilai selnya. | Tabel tanpa data | Berlabel badge merah soft `Belum Diisi`. | | |
| [ ] | **TC-OP-03** | Perubahan Status Otomatis ke Sudah Diisi | Unggah template Excel terisi pada tabel dinamis OPD. | Upload berkas Excel | Status otomatis berubah menjadi `Sudah Diisi` (badge hijau) lengkap dengan uploader & timestamp. | | |
| [ ] | **TC-OP-04** | Form Catatan Validasi Admin | Buka modal **Catatan** atau **Periksa** pada baris tabel dinamis OPD. | Catatan verifikasi | Catatan tersimpan dan dapat dibaca oleh OPD sebagai panduan revisi data. | | |
| [ ] | **TC-OP-05** | Verifikasi & Approval Admin | Klik tombol **Setujui / ACC** data tabel dinamis OPD. | Tabel terisi | Status berubah menjadi `Validasi` (disetujui); status publisitas tabel aktif. | | |
| [ ] | **TC-OP-06** | Reset Nilai Tabel (Jika Ditolak) | Klik tombol **Reset Nilai** pada baris tabel dinamis (khusus BPS). | Tabel terisi data uji | Seluruh data nilai pada tabel di-reset kembali kosong dengan konfirmasi aman. | | |

---

### Modul 10: Sinkronisasi API Portal & Matching PDF (Python Engine)

Pengujian penarikan data portal satu data dan kecerdasan pencocokan PDF publikasi DDA.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-SY-01** | Akses Menu Sinkronisasi API | Buka menu `admin/matching/`, pilih salah satu tabel yang terhubung ke API portal. | Modul Sinkronisasi API | Tampilan perbandingan data portal dan database lokal tampil rapi. | | |
| [ ] | **TC-SY-02** | Fetch Data Real-time dari API | Klik tombol **Tarik Data API** pada tabel portal aktif. | Endpoint API Satu Data | Data terkini dari portal ditarik sukses dan tabel pratinjau API terisi angka. | | |
| [ ] | **TC-SY-03** | Akses Modul Matching PDF (Python) | Buka menu `admin/matching_pdf/` pada sidebar admin BPS. | Modul Matching PDF | Antarmuka matching PDF terbuka dengan panel ekstraksi tabel dan daftar dokumen PDF DDA. | | |
| [ ] | **TC-SY-04** | Deteksi & Rekomendasi Tabel PDF | Jalankan pencocokan tabel PDF dengan tabel master DDA. | Berkas PDF DDA | Python engine mendeteksi nomor tabel, judul, dan merekomendasikan tabel tujuan yang sesuai. | | |
| [ ] | **TC-SY-05** | Tautan Pintas ke View Tabel Dinamis | Pada hasil matching PDF, klik ikon pensil biru/tombol buka tabel dinamis. | Tombol link tabel | Tautan membuka tab baru ke `admin/view_tabel_dinamis/{id}` yang sesuai. | | |
| [ ] | **TC-SY-06** | Auto-Matching PDF ke Dimensi Dinamis | Simpan hasil matching PDF yang memiliki struktur tabel dinamis. | Matching tabel dinamis | Konfigurasi indikator dinamis tersimpan otomatis dengan aman di database. | | |

---

### Modul 11: Forum Diskusi & Konsultasi Antar Instansi

Pengujian ruang komunikasi interaktif antara tim BPS dan perwakilan dinas OPD.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-FR-01** | Buka Forum Diskusi | Buka menu `admin/forum_diskusi/` dari akun BPS atau OPD. | Modul Forum | Daftar topik diskusi aktif tampil urut berdasarkan aktivitas terbaru. | | |
| [ ] | **TC-FR-02** | Buat Topik Diskusi Baru | Klik **Buat Topik Baru**, pilih judul tabel yang dikonsultasikan, tulis pesan. | Topik konsultasi data | Topik baru terbuat dan muncul di daftar forum diskusi. | | |
| [ ] | **TC-FR-03** | Balas / Komentar Diskusi | Buka salah satu topik, ketik balasan komentar lalu kirim. | Komentar balasan | Komentar langsung terkirim dan muncul di riwayat percakapan secara berurutan. | | |
| [ ] | **TC-FR-04** | Filter Diskusi Berdasarkan OPD | Pilih salah satu instansi pada dropdown filter forum. | Filter dinas OPD | Hanya topik yang melibatkan dinas OPD terkait yang ditampilkan. | | |

---

### Modul 12: Laporan Rekapitulasi & Master Data Pendukung

Pengujian agregasi laporan kemajuan DDA serta master data OPD, Tim LO, dan Pengguna.

| Check | ID Test | Skenario Uji | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Status | Catatan / Hasil Aktual |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| [ ] | **TC-RP-01** | Rekap Progres per Instansi OPD | Buka `admin/report/` -> pilih opsi **Berdasarkan Instansi**. | Laporan Rekapitulasi | Tabel rekap menampilkan total tabel, belum diisi, validasi, dan persentase progres per OPD. | | |
| [ ] | **TC-RP-02** | Rekap Progres per Tim LO BPS | Ganti opsi laporan ke **Berdasarkan Tim Pendamping**. | Laporan Rekapitulasi | Agregasi data terkelompok berdasarkan tim pembina/LO BPS secara akurat. | | |
| [ ] | **TC-RP-03** | Ekspor Laporan Rekap ke Excel | Klik tombol **Export Excel** pada halaman laporan rekapitulasi. | Tombol Export Excel | File laporan `.xlsx` terunduh dengan angka total dan persentase yang identik dengan tampilan web. | | |
| [ ] | **TC-RP-04** | Master OPD / Unit Kerja (`admin/master_opd`) | Buka menu Master OPD, lakukan penambahan atau pengubahan nama instansi dinas. | Data Instansi OPD | Perubahan nama atau penambahan instansi OPD baru tersimpan ke database `m_unitkerja`. | | |
| [ ] | **TC-RP-05** | Master Tim LO (`admin/master_tim`) | Buka menu Master Tim, periksa pembagian anggota tim pendamping BPS. | Data Tim BPS | Pembagian PIC tim LO terhadap instansi OPD binaan tersimpan rapi. | | |
| [ ] | **TC-RP-06** | Manajemen Pengguna & Admin (`admin/manage_admin`) | Buka menu Manajemen Admin, periksa daftar pengguna sistem beserta rolenya. | Akun Pengguna | Penambahan/pengubahan akun pengguna dan hak akses peran (*Super Admin, Admin, Diseminasi, Walidata*) bekerja normal. | | |

---

## 🐞 Log Temuan Kendala / Cacat (Defect & Bug Tracker)

Gunakan tabel ini untuk mencatat skenario yang menghasilkan status `FAIL` saat pengujian berlangsung:

| No. | ID Test | Deskripsi Masalah / Bug | Tingkat Keparahan (Severity)* | Langkah Reproduksi Bug | Status Perbaikan** |
| :---: | :---: | :--- | :---: | :--- | :---: |
| 1 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |
| 2 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |
| 3 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |
| 4 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |
| 5 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |
| 6 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |
| 7 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |
| 8 | | | Low / Medium / High / Critical | | Open / Fixed / Verified |

*\*Tingkat Keparahan (Severity):*
- **Critical**: Fitur utama crash / data hilang / halaman blank (500 error / unhandled exception).
- **High**: Fungsionalitas tabel dinamis gagal bekerja (misal: upload Excel gagal masuk database atau angka tertukar).
- **Medium**: Fungsionalitas berjalan namun terdapat anomali perhitungan, layout berantakan, atau nomor tidak sesuai.
- **Low**: Masalah minor kosmetik / typo teks label / padding CSS yang kurang presisi.

*\*\*Status Perbaikan:*
- **Open**: Cacat baru ditemukan dan dilaporkan ke tim pengembang.
- **Fixed**: Developer telah menerapkan perbaikan pada branch dev / staging.
- **Verified**: Tester telah menguji ulang perbaikan dan mengonfirmasi lolos uji (*PASS*).

---

### ✍️ Lembar Pengesahan Hasil Pengujian (Sign-Off Sheet)

| Peran | Nama Terang | Tanda Tangan / Persetujuan | Tanggal |
| :--- | :--- | :---: | :---: |
| **Quality Assurance / Tester** | ____________________________ | [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ] | ____ / ____ / 2026 |
| **Lead Developer** | ____________________________ | [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ] | ____ / ____ / 2026 |
| **Koordinator Pengolahan Data BPS**| ____________________________ | [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ] | ____ / ____ / 2026 |
