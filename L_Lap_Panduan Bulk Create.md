# Panduan Penggunaan Fitur Bulk Create/Update Portal Data

Dokumen ini memberikan panduan langkah demi langkah bagi Administrator untuk menggunakan fitur **Bulk Portal Update** pada sistem DDA Online. Fitur ini dirancang untuk memperbarui link data portal secara massal menggunakan file CSV, sehingga tidak perlu menginput ID Portal satu per satu untuk setiap tabel.

---

## 1. Persiapan File CSV

Sebelum mengunggah, pastikan file CSV Anda memiliki format yang benar. Anda dapat menggunakan Excel atau Google Sheets, lalu simpan sebagai format `.csv`.

### Struktur Kolom CSV:

File CSV setidaknya harus memiliki dua kolom utama (tanpa header juga diperbolehkan, namun disarankan mengikuti urutan berikut):

1.  **Kolom A (Judul Tabel atau ID Tabel):** Nama tabel yang ada di sistem DDA atau ID tabelnya.
2.  **Kolom B (ID Portal):** UUID atau ID dari Satu Data Jawa Tengah (bisa lebih dari satu ID, dipisahkan dengan koma jika ingin menggabungkan beberapa sumber data).

> [!TIP]
> Pastikan tidak ada karakter aneh atau spasi berlebih pada judul tabel di CSV agar sistem dapat melakukan pencocokan (_matching_) secara otomatis.

---

## 2. Langkah-Langkah Eksekusi

### Langkah 1: Masuk ke Menu Master Tabel

1.  Login ke Dashboard Admin DDA Online.
2.  Buka menu **Master Tabel** dari sidebar.
3.  Cari dan klik tombol bertanda **"Bulk Update Portal"** (biasanya berwarna oranye atau biru di bagian atas tabel).

### Langkah 2: Unggah File CSV

1.  Klik pada area _Upload_ atau tombol _Browse_.
2.  Pilih file CSV yang telah Anda siapkan.
3.  Klik tombol **"Preview & Validasi"**.

### Langkah 3: Tahap Preview & Validasi (PENTING)

Setelah unggah, sistem akan menampilkan halaman **Preview Bulk Update**. Perhatikan indikator status berikut:

- **Status: Ditemukan (Badge Hijau)**
  Sistem berhasil mencocokkan judul di CSV dengan database DDA. Baris ini otomatis tercentang dan siap diperbarui.
- **Status: Tidak Sesuai (Badge Merah)**
  Terjadi karena judul tabel di CSV berbeda sedikit dengan di database.
  - **Solusi:** Gunakan kolom **"Pilih tabel secara manual"** (dropdown pencarian) untuk memilih tabel yang benar dari database.
  - Setelah dipilih, baris tersebut akan dianggap valid.

### Langkah 4: Konfirmasi & Simpan

1.  Pastikan semua baris yang ingin diperbarui sudah tercentang.
2.  Periksa kembali ID Portal yang tertera di setiap baris.
3.  Klik tombol **"Konfirmasi & Update Sekarang"** di bagian bawah halaman.
4.  Sistem akan memproses data dan memberikan notifikasi sukses jika berhasil.

---

## 3. Verifikasi Hasil

Untuk memastikan data telah terupdate dengan benar:

1.  Kembali ke menu **Master Tabel**.
2.  Cari tabel yang baru saja diperbarui.
3.  Klik pada judul tabel tersebut.
4.  Jika berhasil, sistem akan mengarahkan ke halaman **Portal Data** dan menampilkan data terbaru yang ditarik secara real-time dari API Satu Data Jawa Tengah.

---

## 4. Troubleshooting (Masalah Umum)

| Masalah                    | Penyebab                                                       | Solusi                                                                                        |
| :------------------------- | :------------------------------------------------------------- | :-------------------------------------------------------------------------------------------- |
| **Data Kosong di Preview** | File CSV rusak atau format pemisah (delimiter) tidak dikenali. | Simpan ulang CSV menggunakan format _Comma Delimited_ di Excel.                               |
| **Status Merah Semua**     | Judul di CSV sangat berbeda dengan database.                   | Pastikan menggunakan ID Tabel atau perbaiki judul di file CSV Anda agar mendekati judul asli. |
| **Gagal Update**           | Masalah koneksi database atau sesi login habis.                | Coba refresh halaman dan login ulang sebelum melakukan Bulk Update.                           |
