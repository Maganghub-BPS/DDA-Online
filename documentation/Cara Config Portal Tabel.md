# Panduan Konfigurasi Portal Tabel (DDA Online)

Dokumen ini menjelaskan cara menggunakan fitur **Konfigurasi Tabel** pada portal data DDA Online untuk menghasilkan tampilan tabel yang profesional, sesuai standar publikasi "Jawa Tengah Dalam Angka", dan mendukung fitur bilingual.

---

## 1. Mengakses Menu Konfigurasi
Untuk memulai konfigurasi:
1. Buka halaman detail tabel pada Portal Tabel.
2. Klik tombol **KONFIGURASI** yang terletak di pojok kanan atas (ikon gerigi).
3. Modal konfigurasi akan muncul dengan dua tab utama: **Kolom & Label** dan **Pivot Mode**.

---

## 2. Tab: Kolom & Label
Tab ini digunakan untuk mengatur properti dasar kolom tabel.

### A. Visibilitas Kolom
- Gunakan **Checkbox** di sebelah kiri setiap nama kolom untuk menampilkan atau menyembunyikan kolom tersebut.
- Kolom yang tidak dicentang tidak akan muncul di tampilan tabel maupun hasil export Excel.

### B. Mengubah Urutan Kolom (Drag & Drop)
- Terdapat ikon **Grip Vertical** (<i class="bi bi-grip-vertical"></i>) di setiap item kolom.
- Klik dan tahan ikon tersebut, lalu tarik ke atas atau ke bawah untuk menentukan urutan kolom dari kiri ke kanan.

### C. Kustomisasi Label (Bilingual)
- **Label ID**: Masukkan nama kolom dalam Bahasa Indonesia.
- **Label EN**: Masukkan nama kolom dalam Bahasa Inggris.
- Sistem akan menampilkan label ID di atas dan label EN (miring/italic) di bawahnya secara otomatis.

### D. Membuat Header Bertingkat (Nested Headers)
Fitur ini memungkinkan Anda membuat grup header.
- Gunakan separator ` || ` (spasi-pipa-pipa-spasi) pada Label ID/EN.
- **Contoh**: Jika Anda mengisi `Produksi || Kayu Bulat`, maka di tabel akan muncul header induk "Produksi" dan di bawahnya terdapat sub-header "Kayu Bulat".
- Header dengan nama induk yang sama akan otomatis tergabung (merged) secara horizontal.

### E. Gabungkan Seluruh Dataset
- Aktifkan switch **"Gabungkan Seluruh Dataset Portal"** jika tabel terdiri dari beberapa ID Dataset yang ingin ditampilkan dalam satu kesatuan layout.

---

## 3. Tab: Pivot Mode (Putar Data)
Fitur ini digunakan untuk mentransformasi data mentah (baris) menjadi tampilan kategori (kolom), serupa dengan fitur Pivot Table di Excel.

### A. Aktivasi Pivot
- Aktifkan switch **"Aktifkan Putar Data (Pivot Mode)"**.

### B. Pengaturan Dimensi
- **Baris Tetap**: Pilih kolom yang akan menjadi sumbu Y (misal: *Kabupaten/Kota* atau *Tahun*).
- **Kategori Kolom (Hierarki)**: Pilih kolom yang akan diputar menjadi header (misal: *Jenis Kelamin* atau *Kategori Sertifikat*).
  - Anda dapat memilih lebih dari satu kolom untuk membuat hierarki kolom yang lebih dalam.
  - Atur urutan hierarki dengan menarik badge biru di area "Urutan Hierarki".
- **Kolom Nilai**: Pilih kolom yang berisi angka/data yang ingin dijumlahkan.

### C. Induk Header & Total
- **Induk Header (ID/EN)**: Berikan nama grup utama untuk seluruh hasil putar data (misal: "Jumlah Sertifikat").
- **Hitung Total Otomatis**: Centang untuk menambahkan kolom "Jumlah" di akhir tabel yang menjumlahkan seluruh nilai per baris secara otomatis.

### D. Penyesuaian Hasil (Mapping)
Setelah memilih kolom pivot, akan muncul area **Mapping Area**:
- Gunakan area ini untuk memperbaiki atau mempercantik label yang dihasilkan dari data mentah.
- **Contoh**: Jika di database nilainya `1`, Anda bisa mengubahnya menjadi `Tersertifikasi` di kolom Label ID.

---

## 4. Pratinjau & Penyimpanan

### A. Preview Cepat
- Sebelum menyimpan, klik tombol **Preview** untuk melihat contoh tampilan tabel (5 baris pertama).
- Periksa apakah header bertingkat dan urutan kolom sudah sesuai keinginan.

### B. Terapkan & Simpan
- Klik tombol **Terapkan & Simpan** untuk memperbarui tampilan tabel secara permanen di sistem.
- Konfigurasi ini bersifat *stateful*, artinya akan terus tersimpan untuk tabel tesebut hingga diubah kembali.

---

## 5. Fitur Pendukung Lainnya

### A. Filter Tahun
- Di halaman utama tabel, tersedia dropdown **Filter Tahun**.
- Filter ini bekerja secara dinamis, bahkan jika Anda menggunakan Pivot Mode atau Dataset Merging.

### B. Export Excel
- Seluruh konfigurasi (urutan kolom, label bilingual, header bertingkat, dan hasil pivot) akan terbawa secara otomatis saat Anda melakukan **Export Excel**.
- Hasil export didesain bersih tanpa judul tambahan agar mudah diolah lebih lanjut.

---
> **Tip**: Selalu gunakan separator ` || ` jika ingin membuat tampilan tabel yang menyerupai layout fisik buku publikasi resmi.
