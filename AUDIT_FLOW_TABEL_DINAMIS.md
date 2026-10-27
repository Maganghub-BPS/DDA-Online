# Laporan Audit Teknis: End-to-End Flow Tabel Dinamis & Manajemen Master Data

**Proyek**: DDA Online CI4 - Modul Tabel Dinamis (`dda-online-ci4-tabel-dinamis`)  
**Cakupan Audit**:
1. Input Data & Fitur *Shifting* Nomor Tabel (`master_tabel`, `master_tabel_opd`, `shift_table_numbers`, `resequence_tabel`)
2. Fungsi Manajemen Master Data: **Duplikat/Clone Tahunan (`clone_tahunan`)**, **Hapus Semua/Reset Tahun (`reset_tahun`)**, **Hapus Satuan/Global (`act_del`)**, **Kelola/Sync Item Master Dimensi (`sync_karakteristik_items`, `sync_periode_items`)**, dan **Buka Kunci & Reset Data (`unlock_dimensi_tabel`)**
3. Pengisian Master Tabel Dinamis (`master_dimensi`, `M_tabeldinamis`)
4. Pembuatan Kerangka Tabel Dinamis (`generator_tabel`, `save_generator_tabel`, `ExcelTemplateGenerator`)
5. Pengisian Data Tabel Dinamis (`upload_excel_dinamis`, `ExcelDataParser`, `batchUpsertNilai`)
6. Verifikasi & Publikasi (`dda`, `periksa_tabel`, `konfirmasi`, `periksa`, `batal_verif`, `act_toggle_publish`)
7. Penampilan Data di Frontend (`Home::index`, `Home::search`, `Home::view_dinamis`, `M_frontend`, `frontend/v_tabel_dinamis.php`)

---

## Ringkasan Eksekutif

Berdasarkan hasil pemeriksaan menyeluruh pada *controller*, *model*, *library*, dan *view*, **tidak ditemukan *syntax error* fatal (PHP Parse Error)** yang menghentikan aplikasi secara langsung. Namun, ditemukan **27 temuan teknis** yang terdiri dari **bug logika bisnis & operasi Master Data (data hilang/yatim/terkorupsi)**, **celah keamanan (IDOR, RBAC bypass, SQL Injection, Hardcoded Token)**, **ketidakselarasan tampilan Admin vs Frontend**, serta **pelanggaran *best practice* performa & transaksi database**.

| Tingkat Keparahan | Jumlah Temuan | Fokus Area |
| :--- | :---: | :--- |
| 🔴 **P0 - Kritis (Critical)** | 7 (1 Fixed) | Kehilangan data tabel dinamis saat edit/sync item dimensi (Fixed 2.1), Celah Keamanan (Auth/RBAC/SQLi/Token), Korupsi Angka Desimal US, Desinkronisasi Multi-Baris Frontend |
| 🟠 **P1 - Tinggi (High)** | 10 (2 Fixed) | *Orphaned data* pada Hapus Semua (`reset_tahun`) & Hapus Global (`act_del`) (Fixed 2.2 & 2.3), Duplikat Tahunan (`clone_tahunan`) tanpa transaksi & salah tahun periode, Shifting nomor tabel tidak sinkron ke `m_indikator_tabel`, Bypass verifikasi publish |
| 🟡 **P2 - Sedang (Medium)** | 5 | Bug formatting Excel Subtotal, variabel `$satuan` tidak terdefinisi, filter tahun di `admin/dda` mati, nested transaction leak |
| 🔵 **P3 - Rendah / Best Practice** | 5 | Penghapusan dimensi via GET, Double query count, full-table scan duplicate check, *Fat Controller*, AutoRoute CI4 |

---

## Tahap 1: Input Data & Fitur Shifting Nomor Tabel

File terkait:
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L951-L1435)
- [f_master_tabel.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_master_tabel.php#L298-L785)
- [f_add_opd.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_add_opd.php#L368-L545)

### 1.1. [🟠 High] Operasi Shifting & Resequence Tidak Dibungkus Database Transaction dan Tidak Meng-update `m_indikator_tabel.no_tabel`
- **Lokasi**: [`Admin::shift_table_numbers()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L999-L1038) dan [`Admin::resequence_tabel()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3032-L3055)
- **Masalah**:
  1. Proses *shifting* mengeksekusi puluhan query `UPDATE t_tahun_tabel SET no_tabel = ? WHERE id = ?` di dalam perulangan `foreach` **tanpa `$this->db->transStart()` dan `$this->db->transComplete()`**. Jika terjadi kegagalan di tengah iterasi, penomoran tabel akan rusak sebagian (setengah bergeser, setengah tidak).
  2. Ketika nomor tabel digeser oleh `shift_table_numbers()` atau `resequence_tabel()`, hanya kolom `t_tahun_tabel.no_tabel` yang diperbarui, sedangkan **`m_indikator_tabel.no_tabel` TIDAK ikut diperbarui**. Akibatnya, nomor tabel di `m_indikator_tabel` menjadi tidak sinkron dengan `t_tahun_tabel`.
- **Rekomendasi**:
  Bungkus seluruh rangkaian *shifting* dan *insert* dalam satu transaksi database (`$this->db->transStart()`), dan tambahkan query sinkronisasi:
  ```php
  $this->db->query("UPDATE m_indikator_tabel SET no_tabel = ? WHERE id_tahun_tabel = ?", [$new_no_tabel, $row->id]);
  ```

### 1.2. [🟠 High] Pengecekan Konflik & Fitur Shifting Absen Saat Edit Master Tabel (`act_edt`)
- **Lokasi**: [`Admin::master_tabel()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1332-L1351) dan [f_master_tabel.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_master_tabel.php#L298) & [baris 643](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_master_tabel.php#L643)
- **Masalah**:
  Modal resolusi konflik dan *interceptor* AJAX `check_no_tabel` di `f_master_tabel.php` dibungkus dalam kondisi `<?php if ($act == "act_add"): ?>`. Di sisi backend (`act_edt` baris 1332), perubahan `no_tabel` langsung di-*update* ke `t_tahun_tabel` tanpa mengecek apakah nomor tabel baru tersebut sudah dipakai tabel lain. Akibatnya, jika admin mengedit nomor tabel ke nomor yang sudah ada, akan terjadi duplikasi nomor tabel tanpa opsi *shifting*.
- **Rekomendasi**:
  Aktifkan pengecekan `check_no_tabel` pada mode `edt` (dengan mengecualikan `id_tahun_tabel` yang sedang diedit) apabila nilai `no_tabel` berubah dari nilai awalnya.

### 1.3. [🔴 Critical] Potensi SQL Injection pada Variabel Session `$ta` yang Tidak Di-bind
- **Lokasi**: [`Admin::master_tabel()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1197) & [baris 1425](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1425), serta [`Admin::dda()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L599)
- **Masalah**:
  Variabel `$ta` diambil dari input GET `$_GET['ta']` di [`Admin::set_ta()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L516-L527) tanpa validasi tipe/integer (`$ta = $this->request->getGet('ta'); session()->set('admin_ta', $ta);`), lalu digabungkan langsung (*string concatenation*) ke dalam klausa SQL:
  ```php
  ORDER BY (l.tahun = '" . $ta . "') DESC
  ```
- **Rekomendasi**:
  Sanitasi `$ta = (int) $this->request->getGet('ta');` saat disimpan ke session dan gunakan *parameter binding* `?` di seluruh query SQL.

### 1.4. [🟡 Medium] Inkonsistensi Nilai Default `is_confirm` (`'2'` vs `'0'`)
- **Lokasi**: [`Admin::master_tabel()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1111) vs [`Admin::batal_verif()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1763)
- **Masalah**:
  Saat tabel baru dibuat (`act_add`), `is_confirm` diisi `'2'`, sedangkan saat verifikasi dibatalkan (`batal_verif`), `is_confirm` diubah menjadi `'0'`. Di berbagai query statistik, kondisi "Belum Terverifikasi" harus memeriksa `is_confirm != '1'`, namun jika ada query yang memeriksa `is_confirm = '0'`, tabel baru dengan nilai `'2'` tidak akan terhitung.
- **Rekomendasi**:
  Standarisasikan nilai *default* `is_confirm` menjadi `'0'` (Belum Konfirmasi) dan `'1'` (Sudah Terkonfirmasi).

---

## Tahap 2: Audit Fungsi-Fungsi Master Data (Duplikat, Hapus Semua, Hapus Satuan, & Sync Item Dimensi)

File terkait:
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1068-L1096) (`master_tabel` -> `act_del`)
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2785-L2948) (`clone_tahunan`, `reset_tahun`)
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L4708-L5065) (`del_*`, `sync_*_items`)
- [M_tabeldinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L113-L555) (`syncKarakteristikItems`, `parsePeriodeFromText`, `generatePeriodeYearRange`)
- [l_master_tabel.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_tabel.php#L650-L812)
- [l_master_dimensi.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_dimensi.php#L750-L1000)

### 2.1. [✅ Fixed] Fungsi "Kelola Item" (`sync_karakteristik_items` & `sync_periode_items`) Telah Diperbaiki Menggunakan Pola `UPDATE` Berbasis `db_id` & Proteksi Data Nilai
- **Status**: **SUDAH DIPERBAIKI (23 Oktober 2026)**
- **Solusi yang Diterapkan**:
  1. Pada [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php), `sync_karakteristik_items` dan `sync_periode_items` kini membaca input `items_json` yang dikirim dari form modal master dimensi, lalu memanggil method baru `syncKarakteristikFromTreeItems()` dan `syncPeriodeFromTreeItems()`.
  2. Pada [M_tabeldinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php), `syncKarakteristikItems()`, `syncPeriodeItems()`, dan `syncBarisItems()` memprioritaskan pencocokan `db_id` dari client. Jika `db_id` cocok, item lama di-`UPDATE` di tempat sehingga primary key `id` **tidak pernah berubah**, menjaga relasi data `t_nilai_dinamis` tetap utuh saat perbaikan typo atau perubahan teks.
  3. Ditambahkan **Safety Guard** pada `syncKarakteristikItems()`, `syncPeriodeItems()`, dan `syncBarisItems()`: jika admin mencoba menghapus item yang sudah memiliki data nilai aktif di `t_nilai_dinamis` (`nilai IS NOT NULL OR simbol IS NOT NULL`), transaksi otomatis dibatalkan (*rollback*) dan sistem melemparkan pesan peringatan informatif, mencegah penghancuran data secara tidak sengaja.
  4. Seluruh alur dibungkus blok `try-catch` di [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php) sehingga pesan error ditampilkan secara rapi di flash message antarmuka admin.
- **Lokasi Kode**:
  - [`M_tabeldinamis::syncKarakteristikFromTreeItems()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L118) & [`M_tabeldinamis::syncKarakteristikItems()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L225)
  - [`M_tabeldinamis::syncPeriodeFromTreeItems()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L1079) & [`M_tabeldinamis::syncPeriodeItems()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L1105)
  - [`Admin::master_dimensi()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L4727-L5105)

### 2.2. [✅ Fixed] Fungsi "Hapus Semua / Kosongkan Tabel Tahunan" (`Admin::reset_tahun`) Telah Dibungkus Transaksi & Menghapus Bertingkat `t_nilai_dinamis` -> `m_indikator_tabel` -> `t_tahun_tabel`
- **Status**: **SUDAH DIPERBAIKI (26 Oktober 2026)**
- **Solusi yang Diterapkan**:
  1. Pada [`Admin::reset_tahun()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2939-L2965), operasi penghapusan dibungkus secara menyeluruh dalam transaksi database (`$this->db->transStart()` dan `$this->db->transComplete()`).
  2. Ditambahkan penghapusan data nilai dinamis secara eksplisit: `DELETE FROM t_nilai_dinamis WHERE id_indikator IN (SELECT mi.id FROM m_indikator_tabel mi JOIN t_tahun_tabel tt ON mi.id_tahun_tabel = tt.id WHERE tt.tahun = ?)`.
  3. Ditambahkan penghapusan konfigurasi dimensi tabel dinamis secara eksplisit: `DELETE FROM m_indikator_tabel WHERE id_tahun_tabel IN (SELECT id FROM t_tahun_tabel WHERE tahun = ?)`.
  4. Baru kemudian mengeksekusi `DELETE FROM t_tahun_tabel WHERE tahun = ?`. Dengan demikian, tidak ada data yatim (*orphaned rows*) yang tersisa, bahkan jika konfigurasi server database menonaktifkan foreign key checks. Kamus master `m_list_tabel` tetap aman dipertahankan.

### 2.3. [✅ Fixed] Fungsi "Hapus Master Tabel" (`Admin::master_tabel` -> `act_del`) Telah Mendukung Penghapusan Eksplisit Serta Pilihan Lingkup Hapus (Tahun Ini vs Global Semua Tahun)
- **Status**: **SUDAH DIPERBAIKI (26 Oktober 2026)**
- **Solusi yang Diterapkan**:
  1. Pada [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1120-L1190) (`mau_ke == "del"`), sistem memeriksa seluruh riwayat tahun terbit tabel (`all_years`) untuk dikirimkan ke view.
  2. Pada modal konfirmasi [f_del_master.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_del_master.php), jika tabel terdaftar di lebih dari 1 tahun, disediakan opsi radio button:
     - `scope = 'current'`: Hanya menghapus tabel pada tahun yang dipilih.
     - `scope = 'all'`: Menghapus tabel secara global dari seluruh tahun sekaligus beserta kamus master tabel.
  3. Pada `Admin::master_tabel` (`mau_ke == "act_del"`):
     - Jika `scope === 'all'`: mengeksekusi penghapusan berurutan `t_nilai_dinamis`, `m_indikator_tabel`, `t_tahun_tabel`, `m_list_tabel`, dan `m_kondef` untuk seluruh tahun yang berinduk pada tabel tersebut di dalam transaksi database.
     - Jika `scope === 'current'`: mengeksekusi penghapusan berurutan untuk `$id_oi` tahun aktif, dan membersihkan `m_list_tabel` jika tabel sudah tidak memiliki tautan di tahun lain.

### 2.4. [🟠 High] Fungsi "Duplikat / Clone Tahunan" (`Admin::clone_tahunan`) Tanpa Transaksi Database, Mengalami N+1 Query, dan Menyalin Tahun Periode Lama Mentah-mentah
- **Lokasi**: [`Admin::clone_tahunan()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2785-L2889)
- **Masalah**:
  1. **Tanpa Database Transaction**: Fungsi ini melakukan ratusan `INSERT INTO t_tahun_tabel` dan `INSERT INTO m_indikator_tabel` di dalam perulangan `foreach ($data_sumber as $row)` **tanpa `$this->db->transStart()` / `$this->db->transComplete()`**. Jika proses terputus di tengah jalan (misal *timeout* atau error DB pada tabel ke-250 dari 500 tabel), tahun tujuan akan berisi data setengah jalan.
  2. **N+1 Query di Dalam Loop**: Pada baris 2862, di dalam loop `foreach ($data_sumber as $row)`, apabila `$row['jenis_tabel'] === 'dinamis'`, sistem mengeksekusi query `SELECT * FROM m_indikator_tabel WHERE id_tahun_tabel = ?` satu per satu untuk setiap tabel.
  3. **Tahun Periode Tidak Disesuaikan ke Tahun Tujuan**:
     - Pada baris 2852–2853, kolom `periode_id` dan `periode_en` di `t_tahun_tabel` disalin mentah-mentah dari tahun sumber (`$row['periode_id']`). Padahal saat membuat tabel baru secara normal di `master_tabel/act_add` (baris 1054), `periode_id` dan `periode_en` diisi dengan tahun publikasi (`$tahun`). Akibatnya, tabel hasil duplikasi dari tahun 2025 ke 2026 tetap memiliki `periode_id = '2025'`.
     - Selain itu, di `m_indikator_tabel` (baris 2875), `id_periode_kelompok` tetap menunjuk ke kelompok periode tahun sumber (misal kelompok periode `"2024"` atau `"2021-2025"`), sehingga admin harus mengedit ulang periode satu per satu.
- **Rekomendasi**:
  1. Bungkus seluruh proses `clone_tahunan()` di dalam `$this->db->transStart()` dan `$this->db->transComplete()`.
  2. Jika `$row['periode_id'] == $tahun_sumber`, otomatis perbarui menjadi `$tahun_tujuan` saat duplikasi.
  3. Ambil seluruh `m_indikator_tabel` tahun sumber sekaligus di luar loop (`WHERE id_tahun_tabel IN (...)`) untuk menghilangkan *N+1 query*.

### 2.5. [🔵 Best Practice] Tombol Hapus Kelompok Dimensi (`del_karakteristik`, `del_baris`, `del_periode`) Menggunakan HTTP `GET`
- **Lokasi**: [l_master_dimensi.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_dimensi.php#L772), [baris 880](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_dimensi.php#L880), [baris 977](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_dimensi.php#L977) dan [`Admin::master_dimensi()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L4708-L5048)
- **Masalah**:
  Aksi penghapusan kelompok dimensi dipicu melalui tautan `<a href="<?= base_url('admin/master_dimensi/del_karakteristik/' . $k->id) ?>">` (HTTP GET). Operasi destruktif yang menggunakan HTTP GET rentan terhadap *CSRF* maupun *browser link prefetching*.
- **Rekomendasi**:
  Gunakan form `POST` (seperti yang sudah diterapkan dengan baik pada `unlock_dimensi_tabel` dan `reset_tahun`).

---

## Tahap 3: Pengisian Master Tabel Dinamis (Master Dimensi)

File terkait:
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L4530-L5150)
- [M_tabeldinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L25-L600)

### 3.1. [🔵 Best Practice] Query Hitung Item Dimensi Dieksekusi Dua Kali (Redundant Query)
- **Lokasi**: [`Admin::master_dimensi()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5088-L5107) dan [`Admin::generator_tabel()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5171-L5188)
- **Masalah**:
  Method [`M_tabeldinamis::getKarakteristikKelompokAll()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L46-L56), `getBarisKelompokAll()`, dan `getPeriodeKelompokAll()` sudah melakukan `LEFT JOIN ... COUNT(i.id) AS total_items`. Namun di Controller, dipanggil lagi `getKarakteristikCounts()`, `getBarisCounts()`, dan `getPeriodeCounts()` untuk menimpa properti `$k->total_items` yang sebenarnya sudah terisi dari query pertama.
- **Rekomendasi**:
  Hapus pemanggilan `get*Counts()` beserta *loop* penimpaannya di `master_dimensi()` dan `generator_tabel()` karena `total_items` sudah dihitung di query `get*KelompokAll()`.

### 3.2. [🔵 Best Practice] *Full Table Scan* di Memori PHP pada `checkDuplicateDimensi()`
- **Lokasi**: [`Admin::checkDuplicateDimensi()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L4533-L4572)
- **Masalah**:
  Setiap kali menyimpan kelompok dimensi baru atau mengedit dimensi, fungsi ini menarik **seluruh baris** dari tabel `m_karakteristik_kelompok` / `m_baris_kelompok` / `m_periode_kelompok` ke memori PHP (`$builder->get()->getResult()`), lalu melakukan `foreach` dan `preg_replace` satu per satu.
- **Rekomendasi**:
  Gunakan query SQL `WHERE LOWER(TRIM(nama_kelompok_ind)) = ?` secara langsung di database atau simpan kolom `slug`/`normalized_name` terindeks di tabel master.

---

## Tahap 4: Pembuatan Kerangka Tabel Dinamis & Template Excel

File terkait:
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5155-L5525)
- [ExcelTemplateGenerator.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L1-L2167)
- [f_generator_tabel.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_generator_tabel.php#L1-L800)

### 4.1. [🟡 Medium] Variabel `$satuan` Tidak Terdefinisi di `ExcelTemplateGenerator::generate()`
- **Lokasi**: [`ExcelTemplateGenerator::generate()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L457-L463)
- **Masalah**:
  Pada baris 457 terdapat pengecekan:
  ```php
  if (!empty($satuan)) {
      $sheet->setCellValue("A{$currentRow}", 'Satuan / Unit: ' . $satuan);
      ...
  }
  ```
  Namun variabel `$satuan` **tidak pernah didefinisikan** di dalam method `generate()` (pada awal method baris 66 hanya ada `$sumber` dan `$catatan`). Akibatnya, blok ini menjadi *dead code* dan satuan indikator tidak pernah tercetak di template Excel.
- **Rekomendasi**:
  Definisikan `$satuan = $indikator->satuan ?? '';` di baris 66 bersama `$sumber` dan `$catatan`.

### 4.2. [🟡 Medium] Bug Batas Awal Kolom Loop pada Baris `subtotal` di `ExcelTemplateGenerator`
- **Lokasi**: [`ExcelTemplateGenerator::generate()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L1890-L1895)
- **Masalah**:
  Pada cabang standar untuk baris bertipe `subtotal` (baris 1869), perulangan untuk memformat sel angka dimulai dari kolom ke-2 (`$c = 2`, yaitu kolom `B`):
  ```php
  for ($c = 2; $c <= $lastColIndex; $c++) {
      $colCoord = Coordinate::stringFromColumnIndex($c);
      $sheet->getStyle("{$colCoord}{$currentRow}")->getNumberFormat()->setFormatCode($numFormat);
      $sheet->getStyle("{$colCoord}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
  }
  ```
  Padahal jika tabel menggunakan mode **Multi-Baris** (`$isMultiBaris === true`) atau **Baris 2 Kolom** (`$isBarisDuaKolom === true`), kolom `B` adalah kolom teks label ke-2 dan data angka baru dimulai pada `$startDataColIndex` (kolom ke-3 / `C`). Akibatnya, label kolom `B` pada baris subtotal akan terformat rata kanan sebagai angka.
- **Rekomendasi**:
  Ubah inisialisasi loop dari `for ($c = 2; ...)` menjadi `for ($c = $startDataColIndex; $c <= $lastColIndex; $c++)` (selaras dengan blok baris `total` di baris 1966).

### 4.3. [🟠 High] Proteksi Lembar Kerja Excel Dinonaktifkan (`setSheet(false)`) Membahayakan Koordinat Metadata
- **Lokasi**: [`ExcelTemplateGenerator::generate()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L2028)
- **Masalah**:
  Pada baris 2028, proteksi sheet dimatikan secara eksplisit:
  ```php
  $sheet->getProtection()->setSheet(false);
  ```
  Sementara seluruh sel data telah di-set `setLocked(Protection::PROTECTION_UNPROTECTED)`. Karena proteksi sheet dimatikan, pengguna (OPD/Walidata) dapat dengan sengaja atau tidak sengaja **menyisipkan/menghapus baris atau kolom** di Excel. Ketika file tersebut diunggah kembali, [`ExcelDataParser::parse()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L604) membaca nilai berdasarkan koordinat statis `excel_row` dan `excel_col` yang tersimpan di sheet tersembunyi `__METADATA_DDA__`. Jika ada 1 baris saja yang bergeser di sheet `Data`, seluruh nilai yang diimpor akan masuk ke wilayah/item yang salah tanpa terdeteksi!
- **Rekomendasi**:
  Aktifkan kembali `$sheet->getProtection()->setSheet(true);` (tanpa mengunci sel input data yang sudah `PROTECTION_UNPROTECTED`), atau tambahkan verifikasi label baris/kolom di `ExcelDataParser` sebelum membaca koordinat sel.

### 4.4. [🟡 Medium] Nilai Default `desimal` Selalu `0` Saat Menyimpan Konfigurasi Generator
- **Lokasi**: [`Admin::save_generator_tabel()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5396) vs [`ExcelTemplateGenerator::generate()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L67)
- **Masalah**:
  Di `save_generator_tabel()`, field `desimal` disimpan dengan nilai `!empty($existing->desimal) ? (int)$existing->desimal : 0`, dan di form `f_generator_tabel.php` tidak ada input pengaturan jumlah desimal. Akibatnya, di `ExcelTemplateGenerator.php` baris 67:
  ```php
  $desimal = (int)($indikator->desimal ?? 2);
  ```
  Karena `$indikator->desimal` bernilai `0` (bukan `null`), operator `??` tidak jatuh ke `2` melainkan tetap `0`, sehingga format sel di template Excel selalu `#,##0` (membulatkan tampilan angka desimal saat diketik pengguna di Excel).
- **Rekomendasi**:
  Gunakan format angka fleksibel `General` atau `#,##0.##` di template Excel jika `desimal == 0`, atau sediakan opsi input jumlah desimal di `f_generator_tabel.php`.

---

## Tahap 5: Pengisian Data Tabel Dinamis (Upload Excel & Parser)

File terkait:
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5530-L5768)
- [ExcelDataParser.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L1-L730)
- [M_tabeldinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L1170-L1255)

### 5.1. [🔴 Critical] Bug Korupsi Angka pada `ExcelDataParser::sanitizeValue()` untuk String Berformat Ribuan Koma (`1,234.56`)
- **Lokasi**: [`ExcelDataParser::sanitizeValue()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L696-L716)
- **Masalah**:
  Ketika sel di Excel bertipe teks atau hasil formula teks yang mengandung titik (`.`) dan koma (`,`) sekaligus, kode pada baris 705 mengasumsikan format tersebut **selalu** format Indonesia (`1.234,56`):
  ```php
  if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
      // Keduanya ada: titik adalah ribuan, koma adalah desimal (1.234,56 -> 1234.56)
      $cleaned = str_replace('.', '', $cleaned);
      $cleaned = str_replace(',', '.', $cleaned);
  }
  ```
  Jika pengguna menyalin-tempel (*copy-paste*) teks angka berformat internasional/US seperti `"1,234.56"` ke dalam sel Excel, `str_replace('.', '', $cleaned)` akan menghapus titik desimal menjadi `"1,23456"`, lalu mengubah koma menjadi titik `"1.23456"`. Nilai `1234.56` berubah menjadi `1.23456` (terkorupsi 1.000x lipat)!
- **Rekomendasi**:
  Deteksi posisi kemunculan terakhir antara `.` dan `,`:
  ```php
  $lastComma = strrpos($cleaned, ',');
  $lastDot   = strrpos($cleaned, '.');
  if ($lastComma > $lastDot) {
      // Format ID: 1.234,56
      $cleaned = str_replace('.', '', $cleaned);
      $cleaned = str_replace(',', '.', $cleaned);
  } else {
      // Format US: 1,234.56
      $cleaned = str_replace(',', '', $cleaned);
  }
  ```

### 5.2. [🟠 High] Checksum Keamanan `__METADATA_DDA__` Dihitung tetapi Tidak Pernah Divalidasi
- **Lokasi**: [`ExcelDataParser::parse()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L17) & [baris 55-115](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L55-L115)
- **Masalah**:
  `ExcelTemplateGenerator` membuat hash SHA-256 (`checksum`) menggunakan `BPS_JATENG_SECRET_SALT` di sel `B8` dan di dalam JSON `B15`. `ExcelDataParser` juga mendeklarasikan konstanta `private const SECRET_SALT = 'BPS_JATENG_SECRET_SALT';` di baris 17 dan membaca `B8`, **tetapi tidak pernah membandingkan/memvalidasi nilai checksum tersebut**. Konstanta `SECRET_SALT` di `ExcelDataParser` sama sekali tidak terpakai (*unused constant*).
- **Rekomendasi**:
  Hitung ulang `hash('sha256', $idTahunTabel . '_' . $idIndikator . '_' . $dimHash . '_' . self::SECRET_SALT)` di `ExcelDataParser::parse()` dan cocokkan dengan `$meta['checksum']` serta dimensi aktif di database saat ini (untuk mencegah pengguna mengunggah template versi lama setelah admin mengubah susunan dimensi).

### 5.3. [🟡 Medium] *Nested Transaction* Tanpa *Try-Finally* Berisiko Menggantung Transaksi jika Terjadi Exception
- **Lokasi**: [`Admin::upload_excel_dinamis()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5661-L5737) dan [`M_tabeldinamis::batchUpsertNilai()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L1178-L1254)
- **Masalah**:
  `upload_excel_dinamis()` membuka `$this->db->transStart()` di dalam blok `try` (baris 5661), lalu memanggil `$mTabelDinamis->batchUpsertNilai()` yang juga membuka `$this->db->transStart()` di baris 1178. Jika terjadi *exception* di antara baris 5662–5724, eksekusi langsung melompat ke `catch (\Throwable $e)` di baris 5752 **tanpa memanggil `$this->db->transComplete()`** untuk menutup transaksi luar.
- **Rekomendasi**:
  Gunakan `$this->db->transRollback()` di dalam blok `catch (\Throwable $e)` jika transaksi sudah dimulai, atau cukup gunakan satu level transaksi di level *service/controller*.

---

## Tahap 6: Verifikasi & Publikasi Tabel

File terkait:
- [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L530-L650) & [baris 1712-1865](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1712-L1865)
- [l_dda.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_dda.php#L94-L533)
- [l_dda_periksa.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_dda_periksa.php#L1-L687)

### 6.1. [🔴 Critical] Celah Keamanan Tanpa Autentikasi & IDOR pada `act_update_isi()` dan `batal_isi()`
- **Lokasi**: [`Admin::act_update_isi()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1712-L1735) dan [`Admin::batal_isi()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1737-L1747)
- **Masalah**:
  Kedua method publik ini **tidak memiliki pengecekan sesi login (`session()->get('admin_valid')`) maupun pengecekan kepemilikan OPD (`admin_id_unitkerja`)**. Karena `$routes->setAutoRoute(true)` aktif di `Routes.php`, siapa pun tanpa login dapat menembak:
  - `GET /admin/batal_isi/{id}` untuk mereset status pengisian tabel (`is_periksa = '0'`, `is_confirm = '0'`) milik instansi mana pun.
  - `POST /admin/act_update_isi` untuk memodifikasi `link_tabel` dan `catatan` tabel mana pun.
- **Rekomendasi**:
  Tambahkan validasi autentikasi session di awal kedua fungsi serta pastikan pengguna OPD hanya bisa mengubah tabel milik `id_unitkerja`-nya sendiri (kecuali Admin BPS).

### 6.2. [🔴 Critical] Celah *Privilege Escalation* (RBAC Bypass) pada Aksi Verifikasi `dda/act_konfirmasi` & `dda/act_periksa`
- **Lokasi**: [`Admin::dda()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L540-L561) dan [`Admin::batal_verif()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1749-L1766)
- **Masalah**:
  Di dalam `Admin::dda()`, blok `elseif ($mau_ke == "act_konfirmasi")` (baris 540) dan `elseif ($mau_ke == "act_periksa")` (baris 551) hanya memeriksa apakah user sudah login, tetapi **tidak memeriksa apakah `session()->get('admin_unitkerja') === 'bps'`**. Akibatnya, akun OPD biasa dapat mengirimkan request `POST /admin/dda/act_konfirmasi` untuk memverifikasi sendiri (`is_confirm = '1'`) tabel miliknya tanpa melalui verifikator BPS! Hal serupa terjadi pada `batal_verif($id)` yang bahkan tidak memeriksa login sama sekali.
- **Rekomendasi**:
  Tambahkan penjagaan eksplisit:
  ```php
  if (session()->get('admin_unitkerja') !== 'bps') {
      return redirect()->to(base_url('admin/dda'))->with('error', 'Akses ditolak: Hanya Verifikator BPS yang berhak melakukan verifikasi.');
  }
  ```

### 6.3. [🟠 High] Inkonsistensi Validasi Publish: Tabel Dinamis Kosong Bisa Di-Publish di Backend
- **Lokasi**: [`Admin::act_toggle_publish()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1835) vs [l_dda_periksa.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_dda_periksa.php#L323)
- **Masalah**:
  - Di tampilan `l_dda_periksa.php` baris 323:
    ```php
    $is_filled = ($b->is_periksa == '1' || $is_portal);
    ```
  - Namun di controller `Admin::act_toggle_publish()` baris 1835:
    ```php
    $is_filled = ($tabel->is_periksa == '1' || $is_portal || $is_dynamic);
    ```
  Karena `$is_dynamic` dimasukkan ke dalam kondisi `OR` di controller, tabel dinamis yang **belum diisi datanya sama sekali (`is_periksa == 0`)** tetap dianggap `$is_filled = true` oleh backend!
- **Rekomendasi**:
  Hapus `|| $is_dynamic` pada baris 1835 di `Admin::act_toggle_publish()` sehingga tabel dinamis wajib berstatus `is_periksa == '1'` (sudah terisi datanya) dan `is_confirm == '1'` sebelum dapat dipublikasikan.

### 6.4. [🟡 Medium] Dropdown Filter Tahun di Halaman `admin/dda` Tidak Berfungsi
- **Lokasi**: [l_dda.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_dda.php#L94-L103) dan [`Admin::dda()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L590-L645)
- **Masalah**:
  Di `l_dda.php`, terdapat `<select name="filter_tahun">` yang mengirimkan parameter GET `filter_tahun` ke `admin/dda`. Namun di `Admin::dda()`, parameter `filter_tahun` tidak pernah dibaca; query selalu menggunakan `WHERE t.tahun = '$ta'` dari session.
- **Rekomendasi**:
  Baca `$filter_tahun = $this->request->getGet('filter_tahun') ?: $ta;` di `Admin::dda()` dan teruskan ke view serta query filter.

---

## Tahap 7: Penampilan Data di Frontend

File terkait:
- [Home.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Home.php#L1-L399)
- [M_frontend.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_frontend.php#L1-L333)
- [frontend/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php#L1-L2364)
- [admin/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L1-L2300)

### 7.1. [🔴 Critical] Ketidakselarasan Rendering Tabel Multi-Baris (`||`) antara Admin Viewer vs Frontend Publik
- **Lokasi**: [admin/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L47-L112) & [baris 1085-1105](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L1085-L1105) & [baris 1883-1921](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L1883-L1921) **VS** [frontend/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php#L87-L120) & [baris 1199-1221](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php#L1199-L1221) & [baris 1872-1925](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php#L1872-L1925)
- **Masalah**:
  Di `admin/v_tabel_dinamis.php`, terdapat logika lengkap untuk menangani `$isMultiBaris` (ketika judul baris atau item baris menggunakan pemisah `||`, misalnya `Jenis Usaha || Satuan` atau `Tahun || Bulan`), termasuk perhitungan `$col1RowspanMap` dan pemisahan sel `<td>` menjadi 2 kolom.
  Namun di **`frontend/v_tabel_dinamis.php`**, seluruh logika `$isMultiBaris` **belum diterapkan/hilang**:
  - Variabel `$isMultiBaris`, `$titleCol1Ind`, `$titleCol2Ind`, `$col1RowspanMap`, dan `$hasDoubleCell` tidak ada di `frontend/v_tabel_dinamis.php`.
  - Akibatnya, jika sebuah tabel dinamis yang menggunakan format `||` dipublikasikan ke frontend, **teks mentah `||` akan tampil apa adanya di satu kolom** (misal: `"Perdagangan || Unit"`) dan *colspan* penomoran kolom di baris `(1), (2), (3)` menjadi berbeda dengan tampilan di Admin maupun Excel!
- **Rekomendasi**:
  Sinkronkan logika deteksi dan *rendering* `$isMultiBaris` dari `admin/v_tabel_dinamis.php` ke `frontend/v_tabel_dinamis.php`, atau ekstrak komponen tabel matriks menjadi satu *partial view* bersama.

### 7.2. [🟠 High] Baris Total Otomatis pada Mode `runtun_waktu` & `Catatan` Tabel Tidak Tampil di Frontend
- **Lokasi**: [admin/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L1984-L2025) vs [frontend/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php#L1940-L1952)
- **Masalah**:
  1. Pada `admin/v_tabel_dinamis.php` (baris 1984–2008), jika tabel dikonfigurasi dengan `konfigurasi_total === 'runtun_waktu'` namun master baris tidak memiliki item bertipe `total` (`!$hasRenderedViewTotal`), sistem otomatis merender baris `Jumlah / Total` multi-periode dengan mengambil key `'0_' . $colLeaf['item_id'] . '_' . $p->id` (yang memang disediakan di template Excel dan diimpor oleh `ExcelDataParser`). Di **`frontend/v_tabel_dinamis.php`**, blok fallback `if ($isRuntunWaktu && !$hasRenderedViewTotal)` ini **tidak ada**, sehingga data total runtun waktu yang sudah diinput dengan `id_baris = 0` hilang/tidak tampil di frontend!
  2. Pada `admin/v_tabel_dinamis.php` baris 2023, `Catatan/Note` dari `$indikator->catatan` ditampilkan di bawah tabel, sedangkan di `frontend/v_tabel_dinamis.php` baris 1949 hanya menampilkan `Sumber/Source` dan mengabaikan `$indikator->catatan`.
- **Rekomendasi**:
  Tambahkan blok fallback `if ($isRuntunWaktu && !$hasRenderedViewTotal && !empty($itemsPeriode))` dan tampilan `Catatan/Note` (jika `$indikator->catatan` tidak kosong) di `frontend/v_tabel_dinamis.php`.

### 7.3. [🟠 High] Pergeseran Kolom pada Baris Subtotal di `admin/v_tabel_dinamis.php` Jika `$isBarisDuaKolom` & `$hasNoCol` Aktif Bersamaan
- **Lokasi**: [admin/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L1640) vs [baris 1737](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L1737)
- **Masalah**:
  Di `admin/v_tabel_dinamis.php` baris 1737 (baris Grand Total), `colspan` dihitung secara tepat menggunakan:
  ```php
  <td colspan="<?= ($hasNoCol ? 1 : 0) + (($isMultiBaris || $isBarisDuaKolom || $isRuntunWaktu) ? 2 : 1); ?>">
  ```
  Namun di baris 1640 (baris Subtotal), perhitungan `colspan` menggunakan logika boolean sederhana:
  ```php
  <td colspan="<?= ($isRuntunWaktu || $isBarisDuaKolom || $hasNoCol) ? 2 : 1; ?>">
  ```
  Jika `$isBarisDuaKolom` (atau `$isMultiBaris`) **dan** `$hasNoCol` keduanya bernilai `true` (total 3 kolom label di sisi kiri: `No.`, `Induk`, `Rincian`), maka pada baris Subtotal `colspan` hanya bernilai `2` (kurang 1 kolom!), sehingga seluruh kolom angka pada baris Subtotal bergeser 1 kolom ke kiri!
- **Rekomendasi**:
  Samakan rumus `colspan` pada baris Subtotal (baris 1640) dengan baris Grand Total (baris 1737): `($hasNoCol ? 1 : 0) + (($isMultiBaris || $isBarisDuaKolom || $isRuntunWaktu) ? 2 : 1)`.

### 7.4. [🔴 Critical] Hardcoded Production Bearer Token & Penonaktifan Verifikasi SSL (`CURLOPT_SSL_VERIFYPEER => false`)
- **Lokasi**: [`Home::callApi2()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Home.php#L77-L88) dan [`Admin::callApi2()`](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2176-L2187)
- **Masalah**:
  Token Bearer API Satu Data Jawa Tengah ditulis secara *hardcoded* di dalam source code (`Authorization: Bearer sdj_UYf5...`) dan pemeriksaan sertifikat SSL dimatikan (`CURLOPT_SSL_VERIFYPEER => false`, `CURLOPT_SSL_VERIFYHOST => false`), membuka celah *Man-in-the-Middle (MitM)* dan kebocoran kredensial di repositori Git.
- **Rekomendasi**:
  Pindahkan token API ke file `.env` (misal `env('SATUDATA_API_TOKEN')`) dan aktifkan kembali verifikasi SSL pada environment produksi.

---

## Temuan Arsitektur & Best Practice Umum

### 8.1. [🟠 High] `$routes->setAutoRoute(true)` Aktif di `app/Config/Routes.php`
- **Lokasi**: [Routes.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Config/Routes.php#L9)
- **Masalah**:
  Mengaktifkan `setAutoRoute(true)` di CodeIgniter 4 memungkinkan seluruh method `public` di dalam `Admin.php` dan `Home.php` diakses langsung lewat URL dengan HTTP verb apa pun (GET/POST). Dikombinasikan dengan beberapa method di `Admin.php` yang lupa memeriksa session login (seperti `batal_isi`, `act_update_isi`, `batal_verif`, `cleanup_junk`, `fix_table_years`), hal ini menjadi risiko keamanan yang sangat tinggi.
- **Rekomendasi**:
  Gunakan **CodeIgniter 4 Auth Filter** (`app/Filters/AuthFilter.php`) untuk memproteksi seluruh rute `/admin/*` secara terpusat di `Routes.php`, dan matikan `setAutoRoute(false)` setelah mendefinisikan rute secara eksplisit.

### 8.2. [🔵 Best Practice] *Fat Controller* & Duplikasi Kode Matriks (~4.500 Baris Kode Duplikat)
- **Lokasi**:
  - [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php) (5.769 baris)
  - [f_generator_tabel.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_generator_tabel.php) (3.238 baris)
  - [admin/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php) (2.300 baris)
  - [frontend/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php) (2.364 baris)
- **Masalah**:
  Logika penyusunan struktur header matriks, leaf columns, dan body tabel ditulis berulang di 4 tempat berbeda (`f_generator_tabel.php`, `admin/v_tabel_dinamis.php`, `frontend/v_tabel_dinamis.php`, dan `ExcelTemplateGenerator.php`). Akibatnya, ketika ada pembaruan fitur seperti `$isMultiBaris` (`||`) atau fallback total `runtun_waktu`, perubahan yang diterapkan di `admin/v_tabel_dinamis.php` tertinggal di `frontend/v_tabel_dinamis.php`.
- **Rekomendasi**:
  Pindahkan logika kalkulasi matriks (`$leafColumns`, `$activeLevels`, `$isMultiBaris`, `$col1RowspanMap`, format angka) ke dalam satu *Library/View Cell* khusus (misalnya `app/Libraries/DynamicTableRenderer.php`) sehingga Admin Preview, Admin Viewer, dan Frontend Publik menggunakan satu sumber kebenaran (*Single Source of Truth*) yang sama.

---

## Tabel Prioritas Eksekusi Perbaikan (Action Plan)

Berikut adalah urutan prioritas eksekusi yang direkomendasikan dari yang **paling mendesak (P0 - Kritis: mencegah kehilangan data & menutup celah keamanan)** hingga **optimasi arsitektur (P3 - Best Practice)**:

| Urutan | Prioritas | Kode Temuan | Modul / Fungsi | File & Baris | Masalah Utama | Dampak jika Tidak Segera Diperbaiki |
| :---: | :---: | :---: | :--- | :--- | :--- | :--- |
| **1** | ✅ **Selesai (Fixed)** | **2.1** | Master Dimensi (`sync_karakteristik_items` & `sync_periode_items`) | [M_tabeldinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L118-L380), [baris 1079-1195](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L1079-L1195) | `DELETE` + `INSERT` ulang diganti dengan `UPDATE` in-place berbasis `db_id` dan proteksi *safety guard* database transaction. | **Sudah Beres**: Relasi data nilai di `t_nilai_dinamis` terjaga 100% dan tidak ada lagi risiko data terhapus saat edit teks / typo. |
| **2** | 🔴 **P0 (Kritis)** | **6.1 & 6.2** | Verifikasi & Pengisian (`act_update_isi`, `batal_isi`, `batal_verif`, `dda`) | [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L540-L561), [baris 1712-1766](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1712-L1766) | Tidak ada cek login pada `act_update_isi`, `batal_isi`, `batal_verif`; tidak ada cek role `bps` pada `act_konfirmasi` & `act_periksa`. | **Celah Keamanan Fatal**: User tanpa login bisa mereset isian OPD; akun OPD biasa bisa memverifikasi tabelnya sendiri tanpa BPS. |
| **3** | 🔴 **P0 (Kritis)** | **5.1** | Upload Excel (`ExcelDataParser::sanitizeValue`) | [ExcelDataParser.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L696-L716) | Angka string berformat US (`1,234.56`) selalu dianggap format ID (`1.234,56`). | **Korupsi Angka**: Nilai `1,234.56` berubah menjadi `1.23456` (mengecil 1.000x lipat) saat diimpor ke database. |
| **4** | 🔴 **P0 (Kritis)** | **7.1 & 7.2** | Frontend Viewer (`v_tabel_dinamis.php`) | [frontend/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php#L87-L120), [baris 1872-1952](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/frontend/v_tabel_dinamis.php#L1872-L1952) | Logika Multi-Baris (`\|\|`), baris Total `runtun_waktu`, dan `Catatan` belum ada di Frontend. | **Tampilan Publik Rusak**: Tabel berformat `\|\|` tampil mentah di frontend publik dan baris total runtun waktu hilang. |
| **5** | 🔴 **P0 (Kritis)** | **1.3 & 7.4** | Keamanan Query `$ta` & Token API | [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L516-L527), [Home.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Home.php#L77-L88) | `$ta` dari GET dimasukkan ke SQL tanpa binding/cast `(int)`; Bearer Token Satu Data ditulis *hardcoded*. | Risiko **SQL Injection** melalui parameter `?ta=` dan kebocoran token produksi. |
| **6** | ✅ **Selesai (Fixed)** | **2.2 & 2.3** | Master Data: Hapus Semua (`reset_tahun`) & Hapus Global (`act_del`) | [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1120-L1190), [baris 2939-2965](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2939-L2965), [f_del_master.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_del_master.php#L65-L108) | Penghapusan bertingkat eksplisit `t_nilai_dinamis` -> `m_indikator_tabel` -> `t_tahun_tabel` dalam transaksi DB & penambahan opsi lingkup hapus (`current` vs `all`). | **Sudah Beres**: Database bersih dari data yatim (*orphaned data*), Master Dimensi tidak terkunci semu, dan admin leluasa memilih lingkup hapus tahun ini atau global. |
| **7** | 🟠 **P1 (Tinggi)** | **2.4** | Master Data: Duplikat Tahunan (`clone_tahunan`) | [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2785-L2889) | Duplikasi ratusan tabel tidak dibungkus transaksi DB, menyalin `periode_id` tahun lama mentah-mentah, dan N+1 query. | Jika gagal di tengah jalan, data tahun baru masuk sebagian; periode tahun tabel baru tetap tertulis tahun lama. |
| **8** | 🟠 **P1 (Tinggi)** | **1.1 & 1.2** | Master Tabel: Shifting & Resequence (`shift_table_numbers`, `resequence_tabel`, `act_edt`) | [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L999-L1038), [baris 1332](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1332), [baris 3032](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3032) | Shifting tanpa transaksi DB, tidak meng-update `m_indikator_tabel.no_tabel`, dan absen saat mode Edit (`act_edt`). | Nomor tabel di `m_indikator_tabel` tidak sinkron dengan `t_tahun_tabel`, dan edit nomor tabel bisa menghasilkan nomor ganda. |
| **9** | 🟠 **P1 (Tinggi)** | **4.3 & 5.2** | Proteksi Sheet Excel & Validasi Checksum Metadata | [ExcelTemplateGenerator.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L2028), [ExcelDataParser.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelDataParser.php#L55-L115) | `setSheet(false)` membiarkan baris Excel disisipkan/dihapus; checksum SHA-256 tidak divalidasi saat upload. | Jika pengguna menyisipkan 1 baris di Excel atau mengupload template lama, angka masuk ke baris/item yang salah. |
| **10** | 🟠 **P1 (Tinggi)** | **6.3 & 7.3** | Validasi Toggle Publish & Colspan Subtotal Admin | [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1835), [admin/v_tabel_dinamis.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/v_tabel_dinamis.php#L1640) | `$is_dynamic` meloloskan cek `$is_filled` saat publish; colspan subtotal kurang 1 jika `$hasNoCol && $isBarisDuaKolom`. | Tabel dinamis kosong bisa di-publish lewat endpoint; angka baris subtotal bergeser 1 kolom ke kiri. |
| **11** | 🟡 **P2 (Sedang)** | **4.1, 4.2, 4.4** | Generator Template Excel (`$satuan`, Loop Subtotal, Default Desimal) | [ExcelTemplateGenerator.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L67), [baris 457](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L457), [baris 1890](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Libraries/ExcelTemplateGenerator.php#L1890) | Variabel `$satuan` belum didefinisikan; loop format subtotal mulai dari kolom B; default desimal `0`. | Satuan tidak tercetak di Excel; kolom B pada baris subtotal rata kanan; angka desimal dibulatkan tampilannya di Excel. |
| **12** | 🟡 **P2 (Sedang)** | **1.4, 5.3, 6.4** | Standarisasi `is_confirm`, Transaksi Upload, & Filter Tahun DDA | [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L590), [baris 1111](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1111), [baris 5661](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5661) | Default `is_confirm` `'2'` vs `'0'`; nested transaction tanpa rollback di `catch`; filter tahun `l_dda` diabaikan. | Dropdown filter tahun di halaman `admin/dda` tidak bereaksi saat dipilih. |
| **13** | 🔵 **P3 (Best Practice)** | **2.5, 3.1, 3.2, 8.1, 8.2** | Refactoring Arsitektur, Method POST Hapus Dimensi, & AuthFilter | [Routes.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Config/Routes.php#L9), [Admin.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L4533), [l_master_dimensi.php](file:///E:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_dimensi.php#L772) | Penghapusan dimensi via GET, query `get*Counts` ganda, `setAutoRoute(true)`, dan duplikasi view matriks. | Beban pemeliharaan kode tinggi (perubahan di admin viewer harus disalin manual ke frontend viewer). |
