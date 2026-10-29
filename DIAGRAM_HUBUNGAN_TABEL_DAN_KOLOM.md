# 📊 DIAGRAM RELASI DATABASE (ERD) - TABEL DINAMIS DDA
## Badan Pusat Statistik (BPS) Provinsi Jawa Tengah

Berikut adalah diagram relasi database (*Entity Relationship Diagram*) resmi yang disusun sesuai standar pemodelan relasional database, menampilkan struktur tabel, tipe data, *Primary Key* (**PK**), *Foreign Key* (**FK**), serta notasi relasi *Crow's Foot* (**1:1**, **1:N**).

---

## 🖼️ Visual Schema Diagram (ERD)

![Diagram ERD Database Tabel Dinamis](diagram_erd_database.png)

> 💡 **Panduan Presentasi:** Jika gambar di atas diklik, Anda dapat melihat seluruh relasi 10 tabel mulai dari master publikasi di kiri atas, perakitan matriks di tengah, kamus 3 dimensi di kanan dan kiri, hingga bermuara pada tabel koordinat sel angka `t_nilai_dinamis` di bagian bawah.

---

## 📌 Penjelasan Alur Relasi Antar-Tabel & Kolom

### 1. Hubungan Master Publikasi (`m_list_tabel` ──► `t_tahun_tabel`)
* **Kardinalitas:** `1 : N` *(One-to-Many)*
* **Kunci Penghubung:** `m_list_tabel.id` [PK] ──► `t_tahun_tabel.id_tabel` [FK]
* **Makna Bisnis:** Satu judul master tabel statistik berlaku permanen dan menaungi banyak edisi tahun publikasi DDA (misal: 2023, 2024, 2025).

---

### 2. Hubungan Tabel Tahunan ke Matriks Dinamis (`t_tahun_tabel` ──► `m_indikator_tabel`)
* **Kardinalitas:** `1 : 1` *(One-to-One)*
* **Kunci Penghubung:** `t_tahun_tabel.id` [PK] ──► `m_indikator_tabel.id_tahun_tabel` [FK]
* **Makna Bisnis:** Setiap tabel publikasi pada tahun tertentu memiliki tepat satu konfigurasi perakitan matriks dinamis yang mengatur tata letak dan desimalnya.

---

### 3. Hubungan Perakitan Matriks ke Kamus 3 Dimensi (*Reusable Dimensions*)
Tabel `m_indikator_tabel` bertindak sebagai "otak" perakit yang memilih kelompok dimensi:
1. **Dimensi Baris (Sumbu Y):**  
   `m_indikator_tabel.id_baris_kelompok` [FK] ──► `m_baris_kelompok.id` [PK]  
   *Selanjutnya:* `m_baris_kelompok.id` ──► `m_baris_item.id_kelompok` *(Memuat daftar 35 Kab/Kota atau kategori indikator)*.  
   *(Hierarki dan indentasi baris diatur secara linier melalui kolom `level_indentasi` dan `tipe_baris`)*.

2. **Dimensi Kolom (Sumbu X):**  
   `m_indikator_tabel.id_karakteristik_kelompok` [FK] ──► `m_karakteristik_kelompok.id` [PK]  
   *Selanjutnya:* `m_karakteristik_kelompok.id` ──► `m_karakteristik_item.id_kelompok` *(Memuat variabel seperti Laki-laki, Perempuan, Total)*.

3. **Dimensi Waktu (Sumbu Z):**  
   `m_indikator_tabel.id_periode_kelompok` [FK] ──► `m_periode_kelompok.id` [PK]  
   *Selanjutnya:* `m_periode_kelompok.id` ──► `m_periode_item.id_kelompok` *(Memuat titik waktu publikasi)*.

---

### 4. Muara Titik Temu Sel Data (`t_nilai_dinamis`)
Seluruh dimensi bertemu di tabel penyimpanan sel `t_nilai_dinamis`:
* **`id_indikator`** [FK] ◄── `m_indikator_tabel.id` *(Mengunci tabel & tahun)*
* **`id_baris_item`** [FK] ◄── `m_baris_item.id` *(Koordinat Baris / Wilayah)*
* **`id_karakteristik_item`** [FK] ◄── `m_karakteristik_item.id` *(Koordinat Kolom / Variabel)*
* **`id_periode_item`** [FK] ◄── `m_periode_item.id` *(Koordinat Waktu)*
* **`nilai`** *(Angka riil statistik BPS presisi tinggi: `DECIMAL(20,4)`)*
* **`simbol`** *(Notasi resmi statistik BPS: `-`, `NA`, `e`, `r`)*

> 🔒 **Composite Unique Key (`uk_cell_position`):**  
> `UNIQUE KEY (id_indikator, id_baris_item, id_karakteristik_item, id_periode_item)`  
> *Menjamin secara matematis di level database bahwa tidak akan pernah terjadi duplikasi data sel pada satu titik koordinat yang sama.*

---

## 📋 Tabel Rangkuman Kunci Relasi (*Foreign Key Reference*)

| No | Kolom Asal (*Source*) | Tabel Asal | Kolom Tujuan (*Target*) | Tabel Tujuan | Kardinalitas |
|:--:| :--- | :--- | :--- | :--- | :---: |
| 1 | `id` | `m_list_tabel` | `id_tabel` | `t_tahun_tabel` | `1 : N` |
| 2 | `id` | `t_tahun_tabel` | `id_tahun_tabel` | `m_indikator_tabel` | `1 : 1` |
| 3 | `id_baris_kelompok` | `m_indikator_tabel` | `id` | `m_baris_kelompok` | `N : 1` |
| 4 | `id` | `m_baris_kelompok` | `id_kelompok` | `m_baris_item` | `1 : N` |
| 5 | `id_karakteristik_kelompok` | `m_indikator_tabel` | `id` | `m_karakteristik_kelompok` | `N : 1` |
| 6 | `id` | `m_karakteristik_kelompok` | `id_kelompok` | `m_karakteristik_item` | `1 : N` |
| 7 | `id_periode_kelompok` | `m_indikator_tabel` | `id` | `m_periode_kelompok` | `N : 1` |
| 8 | `id` | `m_periode_kelompok` | `id_kelompok` | `m_periode_item` | `1 : N` |
| 9 | `id` | `m_indikator_tabel` | `id_indikator` | `t_nilai_dinamis` | `1 : N` |
| 10 | `id` | `m_baris_item` | `id_baris_item` | `t_nilai_dinamis` | `1 : N` |
| 11 | `id` | `m_karakteristik_item` | `id_karakteristik_item` | `t_nilai_dinamis` | `1 : N` |
| 12 | `id` | `m_periode_item` | `id_periode_item` | `t_nilai_dinamis` | `1 : N` |

---

## 🏛️ Rangkuman Struktur 10 Tabel Dinamis Aktif (Sesuai Database Terkini)

| Nama Tabel | Deskripsi & Peran | Kolom-Kolom Utama |
| :--- | :--- | :--- |
| **`m_list_tabel`** | Master judul buku DDA permanen lintas tahun | `id` [PK], `judul_ind`, `judul_en`, `id_unitkerja` |
| **`t_tahun_tabel`** | Edisi publikasi per tahun (status publish & flag dinamis) | `id` [PK], `id_tabel` [FK], `tahun`, `no_tabel`, `is_publish`, `published_at`, `is_dynamic` |
| **`m_indikator_tabel`** | Konfigurasi perakitan matriks & tata letak tabel dinamis | `id` [PK], `id_tahun_tabel` [FK], `id_karakteristik_kelompok` [FK], `id_baris_kelompok` [FK], `id_periode_kelompok` [FK], `posisi_hirarki`, `tingkat_kolom`, `konfigurasi_total`, `label_total_kustom`, `tampilan_baris`, `kolom_nomor`, `satuan`, `desimal`, `catatan` |
| **`m_baris_kelompok`** | Kelompok dimensi baris (Sumbu Y) | `id` [PK], `nama_kelompok_ind`, `nama_kelompok_en`, `judul_baris_ind`, `judul_baris_en`, `judul_sub_baris_ind`, `judul_sub_baris_en` |
| **`m_baris_item`** | Rincian baris data, kategori, indentasi, & format tebal | `id` [PK], `id_kelompok` [FK], `nama_baris_ind`, `nama_baris_en`, `tipe_baris`, `level_indentasi`, `is_bold`, `no_urut` |
| **`m_karakteristik_kelompok`** | Kelompok dimensi kolom/variabel (Sumbu X) | `id` [PK], `nama_kelompok_ind`, `nama_kelompok_en` |
| **`m_karakteristik_item`** | Rincian sub-kolom bertingkat (Induk & Anak) / standalone | `id` [PK], `id_kelompok` [FK], `grup_induk_ind`, `grup_induk_en`, `grup_sub_ind`, `grup_sub_en`, `item_ind`, `item_en`, `is_standalone`, `no_urut` |
| **`m_periode_kelompok`** | Kelompok dimensi waktu/periode (Sumbu Z) | `id` [PK], `nama_kelompok_ind`, `nama_kelompok_en`, `judul_periode_ind`, `judul_periode_en` |
| **`m_periode_item`** | Rincian titik waktu publikasi (Tahun/Semester/Bulan) | `id` [PK], `id_kelompok` [FK], `nama_periode_ind`, `nama_periode_en`, `no_urut` |
| **`t_nilai_dinamis`** | Koordinat sel data numerik & simbol resmi BPS | `id` [PK], `id_indikator` [FK], `id_baris_item` [FK], `id_karakteristik_item` [FK], `id_periode_item` [FK], `nilai`, `simbol` |

> 💡 **Pembaruan Schema Terkini:**
> 1. Penambahan kolom **`kolom_nomor`** pada `m_indikator_tabel` untuk konfigurasi manual opsi nomor urut tabel (Tampilkan vs Sembunyikan).
> 2. Penambahan kolom **`published_at`** pada `t_tahun_tabel` untuk pelacakan waktu publikasi.
> 3. Peningkatan panjang kolom **`t_nilai_dinamis.simbol`** menjadi `VARCHAR(100)` agar mampu menampung notasi statistik dan satuan kustom.
> 4. Pembersihan kolom usang (*Phase 1 Cleanup*): `parent_id` pada `m_baris_item` serta kolom `keterangan` kelompok telah dihapus demi kesederhanaan dan performa kueri.

