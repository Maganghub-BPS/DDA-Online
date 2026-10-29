# Panduan Teknis Operasional: Tabel Dinamis DDA Online
**BPS Provinsi Jawa Tengah**

---

## 1. Menyiapkan Dimensi (Master Dimensi)
Menu: **Master Dimensi** (`admin/master_dimensi`)

### A. Dimensi Kolom (Karakteristik)
1. Buka tab **Karakteristik**.
2. Klik **Tambah Kelompok Karakteristik** (contoh: `Jenis Sarana Kesehatan`).
3. Tambahkan item kolom:
   - **Kolom Biasa**: Ketik nama kolom langsung (contoh: `Jumlah`).
   - **Kolom Bertingkat / Sub-Kolom**: Gunakan pemisah `||` dengan format `Induk || Anak`.
     * *Contoh*:
       ```text
       Distribusi || Pedagang Besar Farmasi
       Distribusi || Gudang Farmasi
       Pelayanan || Rumah Sakit
       Pelayanan || Puskesmas
       Pelayanan || Apotek
       Jumlah
       ```
     * *Sistem otomatis mengatur baris atas (colspan) dan sub-kolom di bawahnya.*

### B. Dimensi Baris
1. Buka tab **Judul Baris**.
2. Klik **Tambah Kelompok Baris** (contoh: `35 Kabupaten/Kota Jawa Tengah`).
3. Tambahkan item baris dan tentukan tipenya:
   - **`data`**: Baris data yang wajib diisi angka (contoh: *Kabupaten Cilacap*).
   - **`kategori`**: Baris judul kelompok (tebal/arsir, tidak diisi angka).
   - **`subtotal`**: Baris sub-penjumlahan kelompok.
   - **`total`**: Baris grand total / jumlah akhir (garis ganda standar BPS).
4. Atur level indentasi jika bertingkat:
   - `0`: Rata kiri (Induk).
   - `1`: Menjorok 1 tingkat (Sub-bab).
   - `2`: Menjorok 2 tingkat (Sub-sub bab).

### C. Dimensi Periode
1. Buka tab **Periode Waktu**.
2. Tambahkan tahun atau rentang waktu yang dibutuhkan (contoh: `2024`, `2025`, `2026`).

---

## 2. Merakit Tabel Baru (Generator Matriks)
Menu: **Master Tabel** (`admin/master_tabel`)

1. Pada tabel yang ingin diaktifkan, klik tombol **Aksi (titik tiga)** $\rightarrow$ pilih **Rakit Tabel Dinamis**.
2. Pada form konfigurasi di panel kiri:
   - Pilih **Kelompok Karakteristik** (susunan kolom).
   - Pilih **Kelompok Baris** (susunan wilayah / kategori).
   - Pilih **Kelompok Periode** (tahun data).
   - Isi **Satuan / Unit** (contoh: `Jiwa / Person`, `Ton`, `Persen`).
   - Tentukan **Format Desimal**:
     * `0`: Bilangan bulat murni (contoh: `1.500`).
     * `1`: 1 angka desimal (contoh: `12,5`).
     * `2`: 2 angka desimal standar BPS (contoh: `1.007,98` dan `2,80`).
   - Isi **Sumber Data** dan **Catatan Kaki** resmi.
3. Periksa tampilan pada **Live Matrix Preview** di panel kanan.
4. Klik **Simpan Konfigurasi Matriks**.
5. Status tabel otomatis berubah menjadi **TABEL DINAMIS**.

---

## 3. Tiga Cara Mengisi Data Nilai

Pilih salah satu dari 3 cara berikut sesuai kondisi:

### Cara A: Tarik Otomatis dari Buku PDF Master (Paling Cepat)
*Gunakan cara ini jika file PDF buku DDA sudah diunggah di sistem.*

1. Masuk ke menu **Matching PDF** (`admin/matching_pdf`).
2. Cari nomor tabel yang bersangkutan.
3. Klik tombol warna kuning **`Isi / Detail`**.
4. Sistem otomatis membaca halaman PDF, memetakan koordinat sel, dan menyuntikkan angka langsung ke database.
5. Selesai. Status tabel langsung berubah menjadi **Data Sesuai** (Hijau).

---

### Cara B: Alur Template Excel (Untuk Walidata / Dinas Luar)
*Gunakan cara ini untuk mendistribusikan blanko pengisian ke OPD/Dinas.*

1. **Unduh Blanko**:
   - Buka form edit tabel atau detail tabel.
   - Klik tombol **Unduh Template Excel** (`admin/download_template_tabel/{id}`).
   - Sistem menghasilkan file `.xlsx` dengan header dan nama wilayah yang terkunci (*protected*).
2. **Pengisian oleh OPD**:
   - OPD mengisi angka hanya pada sel warna putih.
   - Aturan pengisian BPS:
     * Angka: ketik angka murni tanpa teks satuan (contoh: `1500` atau `12.5`).
     * Data nihil: ketik strip `-` atau `0`.
     * Data belum tersedia: ketik titik-titik `...` atau kosongkan.
3. **Unggah Kembali**:
   - Admin menerima file Excel dari OPD.
   - Masuk ke form upload tabel $\rightarrow$ pilih file $\rightarrow$ klik **Upload Excel** (`admin/upload_excel_dinamis`).
   - Sistem memvalidasi koordinat sel dan menyimpan nilai ke database secara otomatis.

---

### Cara C: Edit Langsung di Web (Inline Editor)
*Gunakan cara ini untuk revisi cepat beberapa sel angka.*

1. Buka menu **Lihat Tabel Dinamis** (`admin/view_tabel_dinamis/{id}`).
2. Klik dua kali (*double click*) pada sel angka yang ingin diubah.
3. Ketik angka baru.
4. Tekan **Enter** atau klik di luar sel.
5. Nilai langsung tersimpan otomatis ke database.

---

## 4. Validasi & Sinkronisasi Selisih Nilai
Menu: **Matching PDF** (`admin/matching_pdf`)

1. Klik tombol **Cek Beda** pada tabel yang berstatus badge merah (*Ada Selisih*).
2. Periksa modal daftar selisih:
   - Sisi kiri: Nilai cetakan PDF.
   - Sisi kanan: Nilai database saat ini.
3. **Menyamakan Data**:
   - **Per baris/sel**: Klik tombol **Gunakan Angka PDF** pada baris yang diinginkan.
   - **Semua sekaligus**: Klik tombol **Samakan Semua Selisih ke Tabel Dinamis** di bagian atas modal.
4. Nilai database seketika diperbarui mengikuti cetakan PDF dan status berubah menjadi hijau (**Data Sesuai**).

---

## 5. Menampilkan & Mengekspor Data Publik
Halaman Publik: `tabel_dinamis/{id}`

1. **Ganti Periode**: Klik tombol pilihan tahun/periode di atas tabel untuk membandingkan data runtun waktu.
2. **Ekspor Data**:
   - Klik **Download Excel** untuk format `.xlsx` siap olah.
   - Klik **Download CSV** untuk format teks tabular.
   - Klik **Print** untuk mencetak tabel dengan layout rapi tanpa navigasi web.
