# Dokumentasi Fitur Matching - DDA Online

Dokumen ini menjelaskan alur kerja, konsep, dan panduan penggunaan fitur **Matching** yang menghubungkan **DDA Online** dengan **Portal Satu Data Jawa Tengah**.

---

## 1. Alur Kerja (Workflow)
Fitur Matching berfungsi sebagai jembatan sinkronisasi status ketersediaan data antara Portal dan DDA Online.

1.  **Mapping Dataset**: Admin melakukan pemetaan antara tabel di DDA Online dengan ID dataset yang ada di Portal Satu Data (melalui tabel `t_tabel_match`).
2.  **Metadata Sync**: Sistem memanggil API Portal untuk menarik daftar judul dan tahun data terbaru.
3.  **Status Update**: Jika di Portal ditemukan tahun data terbaru (misal: 2024), sistem akan memperbarui status di database DDA Online.
4.  **Reporting**: Dashboard Monitoring di DDA Online akan otomatis menandai tabel tersebut sebagai "Sudah Diisi" jika datanya sudah tersedia di Portal.

---

## 2. Konsep Penarikan Data
Penting untuk memahami batasan teknis penarikan data saat ini:

> [!IMPORTANT]
> **Status vs Baris Data**  
> Sistem saat ini hanya menarik **Metadata (Tahun & Status)**, bukan baris data (angka/record).

*   **Bukan Sinkronisasi Isi**: Data yang ditarik **tidak otomatis masuk** ke dalam sel-sel spreadsheet. Spreadsheet tetap berfungsi sebagai media input manual atau link referensi.
*   **Otomasi Monitoring**: Tujuan utama fitur ini adalah agar Admin BPS tidak perlu mengecek secara manual ketersediaan data di Portal satu per satu setiap harinya.

---

## 3. Struktur Data & Filtering
*   **Agnostik Struktur**: Karena sistem hanya mengecek "Tahun Data", maka perbedaan struktur kolom antara Portal dan Spreadsheet DDA tidak menjadi kendala.
*   **Filtering**: Sistem tidak menyaring kolom secara otomatis. Kriteria keberhasilan sinkronisasi hanya didasarkan pada kecocokan **Tahun Data** antara portal dengan tahun anggaran berjalan di DDA Online.

---

## 4. Panduan Penggunaan Fitur

| Tombol / Fitur | Fungsi Utama |
| :--- | :--- |
| **Ambil Data API** | Memperbarui daftar seluruh dataset yang tersedia di Portal ke database lokal. |
| **Ambil Data Match** | Melakukan pengecekan khusus pada tabel-tabel yang sudah di-*mapping* untuk melihat tahun data terakhirnya. |
| **Report Portal** | Melihat ringkasan tabel mana saja yang statusnya "Sudah Tersedia" di portal vs "Belum Tersedia". |

---

## 5. Hubungan dengan Spreadsheet
Sistem DDA Online menyimpan **URL Spreadsheet** sebagai referensi akses cepat. Saat ini sistem belum membaca konten di dalam file spreadsheet secara langsung (misal: total baris atau nilai tertentu). Seluruh validasi data angka masih dilakukan oleh petugas melalui verifikasi link yang tersedia.

---

> [!TIP]
> **Pengembangan Mendatang**  
> Untuk otomatisasi pengisian konten spreadsheet, diperlukan integrasi tambahan menggunakan *Google Sheets API* atau *PHPSpreadsheet* untuk memproses file CSV hasil download dari Portal secara otomatis.
