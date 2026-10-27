# Rencana Implementasi: Konfigurasi Penomoran Kolom Tabel Dinamis
**Dokumentasi Teknis & Panduan Desain Arsitektur (Opsi A: Manual Generator Tabel)**

---

## 1. Latar Belakang & Identifikasi Masalah

Pada publikasi resmi Badan Pusat Statistik (BPS) seperti publikasi *Provinsi Jawa Tengah Dalam Angka (DDA)*, terdapat dua mazhab/karakter utama penyajian baris data:

### Karakteristik 1: Tabel Daftar Entitas Sejajar (Contoh: Tabel 1.1.1 - Gambar 1)
* **Contoh Data**: Daftar 35 Kabupaten/Kota di Jawa Tengah (`Cilacap`, `Banyumas`, `Purbalingga`, dst.).
* **Karakter Kolom**:
  * Memiliki kolom independen bernama **`No.`** dengan penomoran urut sekuensial `1, 2, 3, ... 35`.
  * Angka tersebut murni merupakan indeks urutan baris, **bukan** bagian dari nama entitas wilayah.
  * Di sebelah kolom `No.` terdapat kolom **`Wilayah / Region`**.

### Karakteristik 2: Tabel Klasifikasi Hirarkis / KBLI / PDRB (Contoh: Tabel 13.1.4 - Gambar 2 & Gambar 3)
* **Contoh Data**: Laju Pertumbuhan PDRB Menurut Lapangan Usaha.
* **Karakter Kolom**:
  * Pada PDF Publikasi Resmi BPS (Gambar 3), **TIDAK ADA kolom `No.` sama sekali**.
  * Kolom pertama langsung berupa nama rincian tunggal: **`Lapangan Usaha / Industry [1]`**.
  * Kode klasifikasi seperti `A.`, `B.` *(Kategori)*, `1.`, `2.` *(Golongan Pokok)*, dan `a.`, `b.` *(Subgolongan)* merupakan **Kode Klasifikasi Baku (KBLI/SNA)** yang melekat sebagai satu kesatuan teks dengan nama lapangannya.

### Permasalahan Sistem Saat Ini (Gambar 2):
* Sistem pada file `app/Views/admin/v_tabel_dinamis.php` menjalankan deteksi regex otomatis: jika membaca teks yang diawali angka atau huruf (seperti `1.` atau `A.`), sistem secara paksa memotong kode tersebut dan memindahkannya ke kolom kiri baru bernama `No.`.
* **Dampak**: Tabel PDRB Lapangan Usaha (Gambar 2) yang seharusnya 1 kolom utuh malah terbelah menjadi 2 kolom ganjil, merusak format resmi buku publikasi BPS dan membuat susunan template Excel berbeda dari PDF sumbernya.

---

## 2. Prinsip & Konsep Solusi (Opsi A: Kendali Manual di Generator Tabel)

Solusi yang disepakati adalah **Opsi A**, yaitu memberikan kendali penuh kepada Admin BPS saat merakit tabel di **Generator Tabel**:

```
+--------------------------------------------------------------------------------------------------+
| PEMISAHAN TANGGUNG JAWAB (SEPARATION OF CONCERNS)                                                |
+--------------------------------------------------------------------------------------------------+
| 1. MASTER DIMENSI (Data Mentah):                                                                 |
|    - Tabel Wilayah : Diinput bersih tanpa nomor (contoh: "Cilacap", "Banyumas").                 |
|    - Tabel Hirarki : Diinput lengkap beserta kode klasifikasinya                                  |
|                      (contoh: "A. Pertanian...", "1. Pertanian...", "a. Tanaman Pangan...").     |
|                                                                                                  |
| 2. GENERATOR TABEL (Tata Letak / Presentasi):                                                    |
|    - Admin memilih layout kolom nomor:                                                           |
|      [ Tampilkan Kolom No. (1, 2, 3...) ]  -> Cocok untuk Tabel Wilayah (Gambar 1).              |
|      [ Tanpa Kolom No. (Nomor Menyatu)  ]  -> Cocok untuk Tabel PDRB/KBLI (Gambar 3).            |
+--------------------------------------------------------------------------------------------------+
```

---

## 3. Spesifikasi Teknis Implementasi

### 3.1. Database Migration (`m_indikator_tabel`)
Menambahkan satu kolom baru pada tabel konfigurasi matriks dinamis `m_indikator_tabel`:
* **Nama Kolom**: `kolom_nomor`
* **Tipe Data**: `ENUM('tampilkan', 'sembunyikan')`
* **Default**: `'tampilkan'`
* **Keterangan**:
  * `'tampilkan'`: Menampilkan kolom `No.` di paling kiri dengan nomor urut otomatis `1, 2, 3...`.
  * `'sembunyikan'`: Meniadakan kolom `No.`. Kolom pertama langsung menampilkan nama dimensi secara utuh.

---

### 3.2. Antarmuka Generator Tabel (`app/Views/admin/f_generator_tabel.php`)
Menambahkan opsi kontrol baru pada *Matrix Settings Ribbon* (berdampingan dengan *Tingkat Kolom*, *Posisi Header*, dan *Baris Total*):

```html
<!-- Kontrol Kolom Nomor (No.) -->
<div id="box_kolom_nomor" class="toolbar-setting-item">
    <span class="toolbar-setting-label">
        <i class="bi bi-hash text-primary me-1"></i> Kolom No:
    </span>
    <div class="posisi-segmented-group" id="group_kolom_nomor">
        <button type="button" class="btn-segmented active" data-val="tampilkan" id="btn_nomor_tampilkan" title="Tampilkan kolom nomor urut terpisah (1, 2, 3...)">
            <i class="bi bi-list-ol me-1"></i> Tampilkan
        </button>
        <button type="button" class="btn-segmented" data-val="sembunyikan" id="btn_nomor_sembunyikan" title="Tanpa kolom nomor terpisah, nomor kode menyatu di teks">
            <i class="bi bi-card-text me-1"></i> Tanpa No
        </button>
    </div>
    <input type="hidden" name="kolom_nomor" id="input_kolom_nomor" value="tampilkan">
</div>
```

* **Live Matrix Preview (JavaScript)**:
  Saat admin mengklik tombol `Tampilkan` vs `Tanpa No`, fungsi `renderMatrixPreview()` langsung merespons secara instan:
  * Jika `sembunyikan`: kolom `No.` di-hide, dan teks rincian ditampilkan utuh dengan indentasi level CSS.
  * Jika `tampilkan`: kolom `No.` ditampilkan kembali.

---

### 3.3. Controller Admin (`app/Controllers/Admin.php`)
1. **Method `generator_tabel($id_tahun_tabel)`**:
   * Mengambil nilai `$indikator->kolom_nomor ?? 'tampilkan'` dan mengirimkannya ke view `$a['sel_kolom_nomor']`.
2. **Method `save_generator_tabel()`**:
   * Menangkap input POST `kolom_nomor`:
     ```php
     $kolom_nomor = trim((string)$this->request->getPost('kolom_nomor'));
     if (!in_array($kolom_nomor, ['tampilkan', 'sembunyikan'], true)) {
         $kolom_nomor = 'tampilkan';
     }
     ```
   * Memasukkan ke dalam array `$payload['kolom_nomor'] = $kolom_nomor;` untuk disimpan/diupdate ke database.

---

### 3.4. Rendering View Web (`v_tabel_dinamis.php` Admin & Frontend)
Memodifikasi logika penentuan kolom nomor dan penghapusan regex paksa:

1. **Penentuan Keberadaan Kolom No**:
   ```php
   // Menggunakan konfigurasi eksplisit dari generator tabel
   $cfgKolomNomor = $indikator->kolom_nomor ?? 'tampilkan';
   $hasNoCol      = ($cfgKolomNomor === 'tampilkan');
   ```

2. **Rendering Baris Teks**:
   * **Jika `$hasNoCol === true`**:
     * Kolom `No.` muncul dengan nomor urut `1, 2, 3...` (`$noText`).
     * Jika di master dimensi ternyata admin terlanjur menginput `"1. Cilacap"`, sistem otomatis membersihkan nomor dari teks nama (`$cleanNama = "Cilacap"`) agar tidak terjadi dobel nomor (`1. 1. Cilacap`).
   * **Jika `$hasNoCol === false` (Mode Tanpa No / PDRB)**:
     * Kolom `No.` tidak dirender.
     * Teks baris **TIDAK DIPOTONG** regex sama sekali (`$cleanNama = $rawNamaInd`).
     * **Indentasi Visual Standar Publikasi BPS**:
       * Kode `A.`, `B.` *(Kategori)*: Huruf tebal (bold), margin kiri `0px`.
       * Kode `1.`, `2.` *(Golongan Pokok)*: Huruf tebal sedang, margin kiri `16px`.
       * Kode `a.`, `b.` *(Subgolongan)*: Huruf reguler, margin kiri `32px`.
       *(Hasil 100% presisi persis Gambar 3 PDF BPS).*

---

### 3.5. Template Excel & Parser (`ExcelTemplateGenerator.php` & `ExcelDataParser.php`)
1. **Unduh Template Excel (`ExcelTemplateGenerator.php`)**:
   * Membaca `$indikator->kolom_nomor`:
     * Jika `'tampilkan'`: Kolom A = `No.`, Kolom B = `Wilayah`, Kolom C dst = Data.
     * Jika `'sembunyikan'`: Kolom A = `Lapangan Usaha`, Kolom B dst = Data.
2. **Upload Data Excel (`ExcelDataParser.php`)**:
   * Menyesuaikan indeks pembacaan kolom data:
     * Offset data dimulai dari kolom B jika tanpa nomor, atau kolom C jika ada kolom nomor.

---

## 4. Rencana Tahapan Eksekusi (Step-by-Step Execution Plan)

| Tahap | Tindakan Teknis | File yang Terlibat | Status |
| :---: | :--- | :--- | :---: |
| **1** | Membuat file migrasi database untuk menambah kolom `kolom_nomor` pada tabel `m_indikator_tabel`. | `app/Database/Migrations/xxxx_AddKolomNomorToIndikatorTabel.php` | **SELESAI** |
| **2** | Memperbarui Model `M_tabeldinamis.php` untuk mendaftarkan `kolom_nomor` ke dalam `$allowedFields`. | `app/Models/M_tabeldinamis.php` | **SELESAI** |
| **3** | Menambahkan penanganan input `kolom_nomor` pada controller saat menyimpan konfigurasi tabel. | `app/Controllers/Admin.php` (`save_generator_tabel` & `generator_tabel`) | **SELESAI** |
| **4** | Menambahkan komponen UI Segmented Buttons di Ribbon Pengaturan Matriks dan update JavaScript Live Matrix Preview. | `app/Views/admin/f_generator_tabel.php` | **SELESAI** |
| **5** | Menyesuaikan logika render baris dan indentasi hierarkis pada view tabel publik dan admin. | `app/Views/admin/v_tabel_dinamis.php`<br>`app/Views/frontend/v_tabel_dinamis.php` | **SELESAI** |
| **6** | Menyelaraskan struktur kolom saat unduh template Excel dan parsing upload Excel. | `app/Libraries/ExcelTemplateGenerator.php`<br>`app/Libraries/ExcelDataParser.php` | **SELESAI** |
| **7** | **Testing & Verifikasi**: Pengujian pada Tabel 1.1.1 (Tipe Kolom No) dan Tabel 13.1.4 (Tipe Tanpa Kolom No). | Blackbox testing di browser & file Excel | **SELESAI** |

---

## 5. Ringkasan Panduan untuk Petugas / Admin

1. **Untuk Tabel Wilayah (Kabupaten/Kota, Kecamatan, OPD)**:
   * **Input Master Dimensi**: Cukup ketik nama bersih tanpa nomor (misal: `Cilacap`, `Banyumas`).
   * **Generator Tabel**: Pilih `Kolom No: Tampilkan`.
   * **Hasil**: Muncul kolom `No.` `(1, 2, 3...)` dan kolom nama wilayah.

2. **Untuk Tabel Klasifikasi / KBLI / PDRB**:
   * **Input Master Dimensi**: Ketik nama lengkap dengan kode klasifikasi (misal: `A. Pertanian...`, `1. Pertanian...`, `a. Tanaman Pangan...`).
   * **Generator Tabel**: Pilih `Kolom No: Tanpa No`.
   * **Hasil**: Kolom nomor hilang, teks tampil utuh dan berindentasi rapi persis PDF publikasi BPS.
