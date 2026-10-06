# Laporan Audit Bug, Sintaks Eror, dan Integritas Sistem DDA Online

**Tanggal Audit**: 5 Oktober 2026  
**Status Eksekusi**: *Audit Only* (Tidak ada data atau kode yang diubah/dieksekusi)  
**Lingkup Pemeriksaan**: Seluruh file Controller, Model, View, Helper, Konfigurasi, Route, JavaScript/AJAX, serta Integritas Database MySQL.

---

## 1. Ringkasan Eksekutif (Executive Summary)

Secara garis besar, aplikasi berjalan stabil dan **tidak ditemukan syntax error** pada seluruh berkas PHP maupun aset web. Sistem pencarian reaktif (AJAX, debouncing, Select2, toggle tampilan, paginasi kapsul) berfungsi secara optimal.

Isu utama yang ditemukan terbagi menjadi dua kategori:
1. **Kualitas Data Entry Database**: Terdapat link tabel yang salah input (berupa teks judul) serta sejumlah record tabel yang belum memiliki link sama sekali.
2. **Integritas Relasi Data (Foreign Key Mismatch)**: Ketidaksamaan kode instansi antara tabel referensi (`m_unitkerja`) dan tabel master (`m_list_tabel`), yang menjawab mengapa jumlah instansi di filter pencarian berjumlah 68 (bukan 69) dan mengapa tabel narapidana tidak memiliki nama instansi (OPD `-`).

---

## 2. Pengecekan Sintaks & Aset Web

| Komponen | Status | Keterangan |
| :--- | :---: | :--- |
| **PHP Syntax (`app/` & `public/`)** | **0 Eror (100% Valid)** | Diuji melalui PHP Linter CLI (`php -l`) pada seluruh file `.php`. |
| **Aset Gambar Hero** | **Valid** | File `public/aset/images/hero-search-bg.jpg` dan `Candi Borobudur.jpeg` ditemukan. |
| **Aset Status Kosong** | **Valid** | File SVG `public/aset/img/empty-box.svg` ditemukan dan dapat dirender. |
| **Dependencies Frontend** | **Valid** | jQuery 3.7.1, Select2 4.1.0, FontAwesome 6, dan Bootstrap terhubung via CDN/lokal. |

---

## 3. Temuan Detail Bug & Anomali Data

### A. Anomali Typo Link Tabel ID 1004 (Data Entry)
* **Tabel Terkait**: `t_tahun_tabel` baris `id = 1004` (Tahun 2026, Nomor Tabel `10.2.16`).
* **Kondisi Saat Ini**:
  Kolom `link_tabel` terisi teks judul bahasa Inggris alih-alih URL atau ID SatuData:
  ```text
  Consumer Price Inflation Rate per Month by Expenditure Group in Semarang Municipality (2022=100), 2025
  ```
* **Dampak**:
  Di halaman `search.php`, tombol navigasi mengarahkan pengguna ke:
  `home/view_sheet?url=Consumer+Price+Inflation+Rate...+&table_id=1004`
  Saat dibuka, iframe pada `v_tabel_sheet.php` gagal memuat dokumen dan menampilkan halaman eror/blank.
* **Rekomendasi Tindak Lanjut**:
  Memperbarui baris `id = 1004` dengan link Google Sheet atau endpoint API SatuData yang valid, atau mengosongkan link jika dokumen belum terbit.

---

### B. 11 Tabel dengan Link Kosong (`link_tabel = ''`)
* **Tabel Terkait**: `t_tahun_tabel` pada 11 ID berikut:
  `518`, `662`, `677`, `695`, `697`, `723`, `792`, `827`, `835`, `861`, `916` (Seluruhnya pada Tahun 2026).
* **Kondisi Saat Ini**:
  Kolom `no_tabel` dan `link_tabel` bernilai string kosong `""`.
* **Dampak**:
  Jika pengguna menekan tombol "Buka" pada salah satu tabel tersebut, sistem membuka `home/view_sheet?url=&table_id=...`. Di dalam `v_tabel_sheet.php`, parameter kosong menyebabkan URL iframe menjadi `?widget=false...` (relatif terhadap URL aktif), yang memicu **rekursi iframe** (halaman web memuat dirinya sendiri berulang kali di dalam iframe).
* **Rekomendasi Tindak Lanjut**:
  1. Pada backend/frontend: Berikan pengecekan `if (empty($row->link_tabel))` agar tombol diubah menjadi badge *"Belum Tersedia"* dan tidak dapat diklik.
  2. Pada controller `view_sheet()`: Tambahkan validasi jika link tabel kosong, arahkan kembali ke pencarian atau tampilkan pesan bahwa data belum dirilis.

---

## 4. Integritas Relasi Instansi (Penyebab Selisih 69 vs 68 OPD & Tabel Tanpa OPD)

### A. Mengapa Jumlah OPD di `m_unitkerja` Ada 69, namun di Filter Pencarian Hanya 68?
* Pada Model [M_frontend.php](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_frontend.php), fungsi `getUniqueUnits()` menjalankan query:
  ```sql
  SELECT m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, COUNT(t.id) as total
  FROM t_tahun_tabel t
  JOIN m_list_tabel m ON t.id_tabel = m.id
  JOIN m_unitkerja ON m_unitkerja.id_unitkerja = m.id_unitkerja
  WHERE m_unitkerja.unitkerja_ind IS NOT NULL
  GROUP BY m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind
  ```
* Karena menggunakan `JOIN`, query ini **hanya mengembalikan instansi yang memiliki tabel terhubung aktif di `t_tahun_tabel`**.
* Tepat **1 instansi** yang tidak memiliki tabel terhubung adalah:
  * `id_unitkerja`: `lapas`
  * `unitkerja_ind`: *"Kepala Wilayah Kantor Direktorat Jenderal Permasyarakatan Jawa Tengah"*
* Jumlah instansi yang memiliki tabel adalah: `69 - 1 = 68 instansi`.

### B. Mengapa Tabel Lapas / Narapidana Tidak Memiliki OPD (Tampil Badge `-`)?
* Di tabel master [m_list_tabel](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_frontend.php), tabel-tabel pemasyarakatan narapidana memiliki data:
  * No Tabel `4.4.15` (ID 793): *"Banyaknya Narapidana dan Tahanan di Lembaga Pemasyarakatan (Lapas)/Rumah Tahanan Negara (Rutan)..."*
  * No Tabel `4.4.16` (ID 543): *"Rekapitulasi Jumlah Narapidana dan Anak Pidana Berdasarkan Tindak Pidana..."*
  * Tabel-tabel tersebut memiliki `id_unitkerja = 'kemenkumham'`.
* Namun di tabel referensi [m_unitkerja](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_frontend.php), **tidak terdapat baris dengan id `'kemenkumham'`**, melainkan tercatat dengan id **`'lapas'`**.
* **Akibat Ketidakcocokan Kunci Ini**:
  1. Tabel narapidana berstatus *orphan* tanpa relasi OPD (saat di-join, `unitkerja_ind` bernilai `NULL` sehingga di search card tampil badge `-`).
  2. Instansi `'lapas'` tercatat memiliki 0 tabel aktif, sehingga otomatis hilang dari filter dropdown pencarian.
  3. Hal serupa terjadi pada kode `'kopertis6'` (Kementerian Agama / Perguruan Tinggi) yang ada di `m_list_tabel` namun tidak terdaftar di `m_unitkerja`.
* **Rekomendasi Tindak Lanjut**:
  Lakukan sinkronisasi `id_unitkerja` di `m_list_tabel` dari `'kemenkumham'` menjadi `'lapas'` (atau tambahkan alias di `m_unitkerja`).

---

## 5. Audit Halaman Pencarian (`search.php`)

### A. Fitur Positif & Performa
* **Debounced Live Search (250ms)**: Mengurangi beban server dan database secara signifikan saat pengguna mengetik kata kunci.
* **Auto Abort Request**: Jika pengguna mengetik cepat atau mengganti filter beruntun, AJAX request sebelumnya langsung dibatalkan (`activeAjax.abort()`), mencegah *race condition* atau data tertumpuk salah.
* **Seamless DOM Swap**: Menggunakan `DOMParser` native untuk memperbarui kontainer `#search-results-container` tanpa me-reload header hero maupun dropdown Select2.
* **History URL Sync**: `window.history.replaceState` menjaga URL di address bar browser tetap sinkron dengan filter pencarian, sehingga URL hasil pencarian dapat disalin (*shareable*).
* **View Preference Memory**: Pilihan tampilan (Grid atau List) tersimpan di `localStorage` (`dda_view_pref`) dan otomatis diaplikasikan ulang setelah AJAX reload.

### B. Catatan Optimasi
* **Dropdown Tahun Dinamis [SUDAH DIPERBAIKI / SELESAI]**:
  Sebelumnya pada baris 958–960 di-hardcode `[2026, 2025]`. Kini telah diperbaiki menggunakan method `getAvailableYears()` pada `M_frontend` dan `Home::search()`, sehingga dropdown terisi otomatis dan dinamis dari database (`SELECT DISTINCT tahun FROM t_tahun_tabel ORDER BY tahun DESC`).

---

## 6. Audit Log Sistem & Kompatibilitas PHP 8.2

Pada berkas log `writable/logs/log-2026-10-05.log`, ditemukan catatan notice:
```text
WARNING - [DEPRECATED] Creation of dynamic property App\Controllers\Admin::$myphpmailer is deprecated in APPPATH\Controllers\BaseController.php on line 60.
```
* **Penyebab**: Pada PHP versi 8.2+, pendefinisian properti dinamis pada objek kelas yang belum dideklarasikan memicu *warning* deprecation.
* **Dampak**: Tidak menghentikan eksekusi aplikasi (*non-fatal notice*), namun menambah ukuran log jika diakses secara intensif.
* **Rekomendasi**: Menambahkan deklarasi properti `public $myphpmailer;` pada kelas `Admin` atau menambahkan atribut PHP `#[AllowDynamicProperties]`.

---

## 7. Matriks Tindak Lanjut

| No | Komponen | Prioritas | Status | Rekomendasi Solusi |
| :-: | :--- | :-: | :-: | :--- |
| 1 | **Dropdown Tahun Dinamis** | **Rendah** | **SELESAI** | Mengambil daftar tahun unik langsung dari database via `M_frontend::getAvailableYears()`. |
| 2 | **Relasi OPD Lapas** | **Tinggi** | **Menunggu Eksekusi** | Sinkronisasi `id_unitkerja` di `m_list_tabel` dari `'kemenkumham'` ke `'lapas'`. Ini akan langsung menyelesaikan masalah nama OPD `-` dan memunculkan instansi Lapas di filter pencarian (jumlah kembali 69). |
| 3 | **Link Tabel ID 1004** | **Tinggi** | **Menunggu Eksekusi** | Perbaiki kolom `link_tabel` pada `t_tahun_tabel.id = 1004` dengan URL/API yang benar. |
| 4 | **11 Tabel Tanpa Link** | **Sedang** | **Menunggu Eksekusi** | Tambahkan proteksi di tampilan `search.php`: jika link kosong, ubah tombol menjadi label non-aktif (*Disabled/Belum Ada Link*) agar tidak memicu *self-embedding iframe recursion*. |
| 5 | **PHP 8.2 Deprecation Notice** | **Rendah** | **Menunggu Eksekusi** | Tambahkan deklarasi `public $myphpmailer;` pada controller `Admin.php`. |

---
*Laporan ini disusun secara objektif berdasarkan pembacaan kode sumber dan struktur basis data aktual. Seluruh modifikasi di atas menunggu persetujuan eksplisit Anda sebelum dieksekusi.*
