# 📋 Panduan & Rencana Perbaikan Logika Backend Tabel Dinamis (Khusus Sisi Admin / Login)

**Proyek**: DDA Online Provinsi Jawa Tengah (`dda-online-ci4-tabel-dinamis`)  
**Branch**: `tabel_dinamis`  
**Database Aktif**: Aiven Cloud MySQL (`defaultdb` / Port: 17649)  
**Tanggal**: 4 November 2026  
**Fokus**: Perbaikan logika backend khusus halaman Admin (Login) tanpa menyentuh modul Frontend/Publik guna mencegah *merge conflict* dengan rekan tim.

---

## 1. Batasan Ruang Lingkup (Strict Anti-Conflict Boundary)

> [!IMPORTANT]
> **ATURAN ISOLASI MODUL**:
> - ⛔ **DILARANG MENGUBAH BERKAS BERIKUT** (Dikerjakan oleh Rekan Tim):
>   - `app/Controllers/Home.php`
>   - `app/Models/M_frontend.php`
>   - Seluruh view di `app/Views/frontend/*`
> - ✅ **BERKAS YANG MENJADI TANGGUNG JAWAB PERBAIKAN INI**:
>   1. [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php)
>   2. [`app/Models/M_tabeldinamis.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php)
>   3. [`app/Libraries/ExcelTemplateGenerator.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php)
>   4. [`app/Libraries/ExcelDataParser.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php)
>   5. [`app/Helpers/my_helper.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Helpers/my_helper.php)

---

## 2. Rincian Temuan Bug Backend Admin & Rekomendasi Solusi

### ✅ Poin 1 (P0 - Selesai & Terverifikasi): Penolakan Unggah Excel Akibat Ketidaksinkronan Parameter Tahun di `download()` vs `generate()`
* **Berkas**: [`app/Libraries/ExcelTemplateGenerator.php` (baris 2237–2244)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L2237)
* **Permasalahan**:
  Pada method `download()`, jika parameter `$tahunInput` kosong (misalnya tombol unduh template dipanggil via URL standar `admin/download_template_tabel/{id}`), kode mengisi default:
  ```php
  $tahunInput = (int)(session()->get('admin_ta') ?: session()->get('tahun') ?: date('Y'));
  ```
  Nilai ini menghasilkan tahun publikasi aktif `$ta` (`2026`). Sementara di `generate()`, jika `$tahunInput` null, tahun target adalah `$activeTa - 1` (`2025`).
  Akibatnya, template Excel terstempel tahun `2026` di sheet metadata `_META_SYSTEM_`. Saat berkas tersebut diunggah melalui form input default (yang mengirim `$tahun_data = 2025`), `ExcelDataParser` langsung melempar Exception penolakan:  
  *"Tahun pada berkas Excel (2026) tidak sesuai dengan tahun data yang dipilih (2025)"*.
* **Rekomendasi Perbaikan**:
  Ubah penentuan default `$tahunInput` di `download()` agar konsisten dikurangi 1:
  ```php
  if ($tahunInput === null || $tahunInput <= 0) {
      $activeTa = (int)(session()->get('admin_ta') ?: session()->get('tahun') ?: date('Y'));
      $tahunInput = $activeTa - 1;
  }
  ```

---

### ✅ Poin 2 (P0 - Selesai & Terverifikasi): Inkonsistensi Tahun Publikasi DDA vs Tahun Data pada `t_status_tahunan`
* **Berkas**:
  - [`app/Controllers/Admin.php` (`ajax_hapus_nilai_tahun` baris 5994–6001)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5994)
  - [`app/Models/M_tabeldinamis.php` (`saveNilaiBatch` baris 1601–1608)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L1601)
  - [`app/Controllers/Admin.php` (`upload_excel_dinamis` baris 6594–6598)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L6594)
* **Permasalahan**:
  1. Di `ajax_hapus_nilai_tahun()`, saat data tahun 2025 dihapus hingga habis, sistem memanggil:
     `$mTabelDinamis->upsertStatusTahunan((int)$ind->id_tabel, (int)$tahun_data, ...)`
     Parameter yang dikirim adalah `$tahun_data` (2025), bukan tahun edisi buku DDA terkait (`$ta = 2026`). Hal ini menyebabkan status centang hijau pada DDA 2026 tidak ter-reset, dan timbul record salah sasaran untuk tahun 2025.
  2. Saat unggah Excel berhasil di `saveNilaiBatch()` dan `upload_excel_dinamis()`, sistem hanya meng-update `'is_periksa' => 1`, namun tidak memperbarui `'is_confirm' => '2'` (Menunggu Verifikasi). Jika sebelumnya tabel berstatus draft `'0'` (belum diisi), status konfirmasinya tidak berubah.
* **Rekomendasi Perbaikan**:
  1. Pada `ajax_hapus_nilai_tahun()`, petakan target tahun buku DDA:
     ```php
     $targetTa = (int)$tahun_data + 1;
     $mTabelDinamis->upsertStatusTahunan((int)$ind->id_tabel, $targetTa, [
         'is_periksa'      => 0,
         'is_confirm'      => '0',
         'catatan_periksa' => null
     ]);
     ```
  2. Pada `saveNilaiBatch()`, sertakan `'is_confirm' => '2'` saat import data berhasil.

---

### ✅ Poin 3 (P0 - Selesai & Terverifikasi): Celah Keamanan Autentikasi & Otorisasi RBAC di Controller Admin
* **Berkas**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php)
* **Permasalahan**:
  1. Method `act_update_isi()` (baris 2096) dan `batal_isi()` (baris 2116) sama sekali tidak memeriksa session login `admin_valid`. Pengguna anonim tanpa login dapat memicu pembatalan atau perubahan pengisian tabel via URL/POST.
  2. Method `save_generator_tabel()` (baris 6020) dan `unlink_generator_tabel()` (baris 6169) hanya memeriksa `admin_valid`, tanpa memeriksa apakah user adalah Admin BPS. Pengguna akun OPD dapat memanipulasi dimensi tabel apapun.
  3. Method AJAX `ajax_save_konfigurasi_tampilan_dinamis()` (baris 6728) dan `ajax_save_default_tahun_tampil()` (baris 6822) tidak memvalidasi apakah user berwenang atas tabel/indikator yang bersangkutan (IDOR vulnerability).
* **Solusi yang Diterapkan**:
  1. Menambahkan helper method terpusat `isAdminBps(): bool` dan `canManageTable($tabel): bool`.
  2. Menambahkan guard login check dan proteksi kepemilikan unit kerja (`canManageTable`) pada `act_update_isi()` dan `batal_isi()`.
  3. Membatasi eksekusi `save_generator_tabel()` dan `unlink_generator_tabel()` khusus untuk Admin BPS (`isAdminBps()`).
  4. Menambahkan validasi kepemilikan tabel pada `ajax_save_konfigurasi_tampilan_dinamis()` dan `ajax_save_default_tahun_tampil()`.
  5. Pengujian unit otomatis (7 skenario RBAC) membuktikan 100% PASS: Pengguna non-login ditolak, pengguna OPD dibatasi hanya pada instansi sendiri, dan Admin BPS / Super Admin memiliki otoritas penuh.

---

### 🟠 Poin 4 (P1 - Tinggi): Sinkronisasi Otomatis JSON `config_tampilan` di `Admin::save_generator_tabel()`
* **Berkas**: [`app/Controllers/Admin.php` (baris 6095–6150)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L6095)
* **Permasalahan**:
  Pasca-migrasi, seluruh baris `m_indikator_tabel` sudah memiliki string JSON di kolom `config_tampilan`.
  Ketika Admin mengubah `posisi_hirarki`, `konfigurasi_total`, atau `label_total_kustom` melalui formulir Generator Tabel, method `save_generator_tabel()` hanya meng-update kolom fisik tabel tersebut.
  Di sisi viewer ([`view_tabel_dinamis()`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L6332)), jika kolom JSON `config_tampilan` tidak kosong, sistem **sepenuhnya mengabaikan nilai kolom fisik**. Akibatnya, perubahan tata letak matriks yang disimpan dari Generator Tabel tidak berefek sama sekali di halaman pratinjau tabel.
* **Rekomendasi Perbaikan**:
  Di `Admin::save_generator_tabel()`, ambil isi `config_tampilan` lama, decode ke array, sinkronkan key yang diubah, lalu encode kembali sebelum disimpan:
  ```php
  $existingCfg = [];
  if (!empty($existing->config_tampilan)) {
      $existingCfg = json_decode($existing->config_tampilan, true) ?: [];
  }
  $existingCfg['posisi_hirarki'] = $posisi_hirarki;
  if ($konfigurasi_total === 'none') {
      $existingCfg['total_vertikal'] = false;
  } else {
      $existingCfg['total_vertikal'] = true;
      $existingCfg['jenis_total_vertikal'] = $konfigurasi_total;
  }
  if (!empty($label_total_kustom)) {
      $existingCfg['label_total_kustom'] = $label_total_kustom;
  }
  $updateData['config_tampilan'] = json_encode($existingCfg);
  ```

---

### 🟠 Poin 5 (P1 - Tinggi): Hilangnya Nilai Angka Saat Sel Memiliki Simbol Catatan (`125*` / `125e`)
* **Berkas**:
  - [`app/Helpers/my_helper.php` (`formatDynamicCellValue` baris 201–205)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Helpers/my_helper.php#L201)
  - [`app/Libraries/ExcelTemplateGenerator.php` (baris 2069–2073)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L2069)
  - [`app/Models/M_tabeldinamis.php` (`buildDynamicTableExport` baris 2066–2073)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L2066)
* **Permasalahan**:
  Dalam data statistik BPS, sel sering memuat angka yang memiliki simbol catatan/angka sementara (misal `125*` atau `125e`).
  `ExcelDataParser::sanitizeValue()` sudah benar menyimpan angka `125.0` ke kolom `nilai` dan `*` ke kolom `simbol`.
  Namun di `formatDynamicCellValue()`:
  ```php
  if ($simbol !== null && $simbol !== '') {
      return (string)$simbol;
  }
  ```
  Fungsi langsung mengembalikan `'*'` dan membuang angka `125`!
  Lebih parah lagi, saat membuat template Excel baru (`ExcelTemplateGenerator.php` baris 2069), jika sel memiliki simbol, sel Excel hanya diisi karakter `*`. Ketika template tersebut diunggah ulang, angka 125 di database tertimpa menjadi `null`.
* **Rekomendasi Perbaikan**:
  Jika sel memiliki nilai numerik sekaligus simbol catatan (misalnya `*`, `**`, `e`, `r`), format terlebih dahulu angkanya sesuai pengaturan desimal, lalu rekatkan simbol di belakangnya (contoh: `125*` atau `125,00*`). Jika sel tidak memiliki nilai numerik (misal `-` atau `...`), baru kembalikan simbol murni.

---

### ✅ Poin 6 (P2 - Selesai & Terverifikasi): Perataan Kolom Subtotal pada Tabel Hirarki 2 Kolom di Template Excel
* **Berkas**: [`app/Libraries/ExcelTemplateGenerator.php` (baris 1662, 1744, 1819, 1940, 2023)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L1940)
* **Permasalahan**:
  Pada tabel dinamis hirarki / multi-kolom baris, Kolom A adalah Induk Baris dan Kolom B adalah Anak Baris (teks label).
  Sebelumnya, loop format sel subtotal di-*hardcode* dari `$c = 2`:
  ```php
  for ($c = 2; $c <= $lastColIndex; $c++)
  ```
  Hal ini menyebabkan teks nama anak baris di Kolom B terformat sebagai angka dan dipaksa rata kanan layaknya sel data numerik.
* **Solusi yang Diterapkan**:
  Mengganti seluruh batas awal loop pemformatan data subtotal/hirarki menjadi `$startDataColIndex`:
  ```php
  for ($c = $startDataColIndex; $c <= $lastColIndex; $c++)
  ```
  Pengujian otomatis dan round-trip parsing (`test_roundtrip_subtotal.php`) pada tabel hirarki (Tabel ID 366 & 323) berhasil 100% PASS: Kolom B tetap berupa teks dengan alignment rapi, dan kolom data angka (Kolom C/D) terformat rata kanan dengan sempurna.

---

### ✅ Poin 7 (P2 - Selesai & Terverifikasi): Verifikasi Keamanan Hash Checksum `__METADATA_DDA__` (B14)
* **Berkas**:
  - [`app/Libraries/ExcelDataParser.php` (baris 74–105)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L74)
  - [`app/Libraries/ExcelTemplateGenerator.php` (baris 2185–2190)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L2185)
* **Permasalahan**:
  Generator template menyematkan token hash SHA-256 pada sel `B14` di sheet tersembunyi `__METADATA_DDA__`. Namun `ExcelDataParser` mengekstrak nilai tersebut tanpa memvalidasi keasliannya sama sekali, sehingga berkas template yang dimodifikasi secara manual atau rusak tidak terdeteksi.
* **Solusi yang Diterapkan**:
  1. Menambahkan validasi kriptografis menggunakan `hash_equals()` di `ExcelDataParser` dengan konstanta rahasia `SECRET_SALT = 'BPS_JATENG_SECRET_SALT'`.
  2. Menerapkan toleransi hash ganda (*dual-hash compatibility*): mendukung template `v1` lama dan template `v2` baru yang mengikat `tahun_data` ke dalam checksum.
  3. Menolak pengunggahan berkas secara instan jika lembar metadata dimodifikasi atau checksum dihilangkan/rusak demi integritas data publikasi BPS.
  4. Pengujian unit otomatis (`test_checksum_verification_suite.php`) membuktikan 100% PASS: template resmi lolos, modifikasi `ID_INDIKATOR` ditolak, berkas tanpa checksum ditolak, dan template lama tetap didukung.

---

### ✅ Poin 8 (P2 - Selesai & Terverifikasi): Optimasi Query Nilai Matriks di Controller Admin
* **Berkas**:
  - [`Admin::view_tabel_dinamis()` (baris 6631–6638)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L6631)
  - [`Admin::ajax_preview_matrix_dinamis()` (baris 7200–7210)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L7200)
* **Permasalahan**: Controller mengeksekusi `SELECT * FROM t_nilai_dinamis WHERE id_indikator = ?` tanpa klausa `whereIn('tahun_data', $selected_years)`. Semua tahun historis ditarik ke RAM PHP padahal index komposit `idx_indikator_tahun` dan `uk_cell_multiyear` sudah tersedia di database.
* **Solusi yang Diterapkan**:
  1. Menambahkan sanitasi array tahun `$cleanYears = array_values(array_filter(array_map('intval', $selected_years)));`
  2. Menerapkan filter `$nilaiBuilder->whereIn('tahun_data', $cleanYears);` baik di `view_tabel_dinamis()` maupun `ajax_preview_matrix_dinamis()`.
  3. Pengujian performa dan query plan (`test_query_optimization.php` pada Indikator ID 371) membuktikan 100% PASS:
     - EXPLAIN plan beralih menjadi `range seek` menggunakan index komposit `uk_cell_multiyear` (`Using index condition`).
     - Baris yang ditarik ke memori PHP terpotong dari 108 sel menjadi 24 sel (hanya tahun terpilih), menghemat alokasi RAM server dan bandwidth database Aiven Cloud.

---

### 🟢 Poin 9 (P3 - Kebersihan Transaksi): Urutan Hapus Master vs Data pada `sync*Items()`
* **Berkas**: [`app/Models/M_tabeldinamis.php` (baris 307, 828, 922, 1238, 1300)](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L307)
* **Permasalahan**: Tabel master dimensi (`m_*_item`) dihapus sebelum baris data anak (`t_nilai_dinamis`). Walaupun berada dalam transaksi, urutan terbaik secara *referential integrity* adalah menghapus tabel anak terlebih dahulu sebelum menghapus baris induk.
* **Rekomendasi Perbaikan**:
  Pindahkan baris penghapusan `t_nilai_dinamis` sebelum baris penghapusan `m_*_item`.

---

## 3. Rencana Pengujian Khusus Admin (Admin Verification Checklist)

1. **Unduh & Unggah Template Excel**:
   - Buka halaman tabel dinamis di admin -> Klik *Download Template Excel*.
   - Verifikasi tahun data yang dihasilkan adalah `2025` (sesi DDA `2026`).
   - Unggah berkas tersebut via form upload -> Pastikan berkas diterima sukses 100% tanpa error perbedaan tahun.
2. **Pengujian Status Tahunan & Hapus Nilai**:
   - Lakukan penghapusan nilai tahun data -> Pastikan status tabel tahun publikasi aktif DDA ter-reset dengan benar (`is_periksa = 0`, `is_confirm = '0'`).
   - Unggah data baru -> Pastikan status berubah menjadi `is_confirm = '2'` (Menunggu Verifikasi).
3. **Pengujian Generator Tabel**:
   - Ubah posisi hirarki atau konfigurasi total di Generator Tabel -> Simpan -> Buka tampilan tabel di admin -> Pastikan tampilan langsung berubah mengikuti opsi yang baru disimpan.
4. **Pengujian Nilai Angka + Simbol**:
   - Masukkan sel dengan format `125*` -> Buka tabel di admin -> Pastikan angka `125` tetap terlihat dengan simbol `*` di belakangnya, bukan hanya menampilkan `*` sendirian.
5. **Pemeriksaan Git Status**:
   - Jalankan `git status` -> Pastikan **tidak ada** perubahan pada `Home.php`, `M_frontend.php`, maupun view frontend.

