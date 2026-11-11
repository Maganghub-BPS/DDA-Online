# Panduan Lengkap Setup & Pemeliharaan Python Engine DDA-Online
**Khusus Server Produksi Linux (aaPanel / AlmaLinux / Ubuntu) & Lokal (Windows)**

---

## 📌 Ringkasan Arsitektur
Aplikasi DDA-Online menggunakan arsitektur *Hybrid*:
* **Frontend & Backend Utama:** PHP 8.x (CodeIgniter 4) untuk manajemen data, view publik, dan antarmuka admin.
* **Mesin Analitik & Scan PDF:** Python 3.9+ (`python_engine/`) yang dipanggil di latar belakang oleh PHP untuk membaca buku PDF BDA/DDA, mencocokkan matriks angka terhadap database, dan menghasilkan rekap Excel.

---

## 1. Spesifikasi & Versi Python yang Didukung
* **Versi Minimum:** Python 3.9
* **Versi Rekomendasi:** Python 3.10 / 3.11 / 3.12
* **OS yang Didukung:** AlmaLinux 8/9, Rocky Linux, Ubuntu 20.04/22.04/24.04, Debian 11/12, serta Windows 10/11.

> [!NOTE]
> Semua script di dalam `python_engine/` sudah dioptimalkan agar **100% kompatibel lintas versi** (Cross-Version Compatible dari Python 3.9 hingga 3.12+).

---

## 2. Daftar Dependensi Library (`requirements.txt`)
Berikut adalah 7 library resmi yang wajib terpasang beserta fungsinya:

| Library | Minimal Versi | Fungsi di DDA-Online |
| :--- | :---: | :--- |
| **`pymupdf`** (`fitz`) | `>=1.23.0` | Mesin pembaca dokumen PDF ultra cepat, ekstraksi teks per halaman, dan deteksi koordinat bounding box tabel. |
| **`pdfplumber`** | `>=0.10.0` | Rekonstruksi garis tabel, penanganan tabel berbaris banyak (*multi-page stitch*), dan ekstraksi header bertingkat. |
| **`rapidfuzz`** | `>=3.0.0` | Algoritma *fuzzy string matching* berkecepatan C++ untuk mencocokkan nama OPD/Dinas, nama Kabupaten/Kota, dan label kolom. |
| **`openpyxl`** | `>=3.1.0` | Membaca template Excel dan mengekspor Laporan Rekapitulasi Validasi (.xlsx) berformat resmi BPS. |
| **`numpy`** | `>=1.24.0` | Komputasi matriks numerik dan vektorisasi array data. |
| **`scipy`** | `>=1.10.0` | Algoritma optimasi Hungarian (`linear_sum_assignment`) untuk pencocokan semantik kolom antar-tahun data. |
| **`requests`** | `>=2.28.0` | Fetching data API Satu Data / Kominfo / BPS secara realtime. |

---

## 3. Langkah Instalasi di Server (aaPanel / Terminal SSH)

### Cara Cepat (1 Baris Perintah)
Buka menu **Terminal** di aaPanel atau login via SSH, lalu jalankan:

```bash
pip3 install pymupdf pdfplumber rapidfuzz openpyxl numpy scipy requests --break-system-packages
```

*(Jika pip server versi lama tidak mengenal flag `--break-system-packages`, cukup hapus flag tersebut).*

### Cara Alternatif (Via `requirements.txt`)
Jika terminal sudah berada di folder proyek:
```bash
cd /www/wwwroot/dda.jateng.pro
pip3 install -r requirements.txt --break-system-packages
```

---

## 4. Konfigurasi PHP (aaPanel)
Supaya CodeIgniter 4 diizinkan memanggil Python di terminal server:

1. Buka dashboard **aaPanel** ➡️ menu **App Store** (atau akses URL `/soft`).
2. Cari versi PHP yang dipakai web (misal **PHP-8.1** atau **PHP-8.2**) ➡️ klik **Setting**.
3. Buka tab **Disabled functions**.
4. Hapus (**Delete**) 4 nama fungsi ini dari daftar blacklist:
   * `popen`
   * `proc_open`
   * `shell_exec`
   * `exec`
5. Buka tab **Service** di sebelah kiri ➡️ klik tombol **Restart**.

---

## 5. Cara Verifikasi / Tes Mandiri di Terminal Server

Untuk memastikan seluruh library dan script Python siap tempur tanpa error, jalankan perintah tes berikut di terminal server:

### Tes 1: Uji Impor Semua Library
```bash
python3 -c "import fitz, pdfplumber, rapidfuzz, openpyxl, numpy, scipy, requests; print('>>> SEMUA LIBRARY PYTHON SIAP 100%! <<<')"
```
*Jika outputnya menampilkan `>>> SEMUA LIBRARY PYTHON SIAP 100%! <<<`, berarti tidak ada library yang tertinggal.*

### Tes 2: Uji Eksekusi Mesin Pencocok Tabel
```bash
cd /www/wwwroot/dda.jateng.pro
python3 python_engine/compare_table.py
```
*Jika script menampilkan petunjuk parameter pemakaian (`Usage: compare_table.py <pdf_path> ...`), artinya Python engine berjalan normal dan tidak ada syntax error.*

---

## 6. Penanganan Masalah Umum (Troubleshooting)

### Q1: Muncul `SyntaxError: f-string expression part cannot include a backslash`
* **Sebab:** Python 3.9 s.d. 3.11 melarang tanda garis miring terbalik (`\`) di dalam `{...}` f-string.
* **Solusi:** Sudah diperbaiki permanen di repository dengan mengganti f-string menjadi string concatenation biasa (`"PROV_" + re.sub(...)`). Cukup jalankan `git pull origin matching-tabel`.

### Q2: Muncul `ModuleNotFoundError: No module named '...'`
* **Sebab:** Ada library Python yang belum terpasang di environment global.
* **Solusi:** Jalankan ulang perintah instalasi pip pada Poin 3 di atas.

### Q3: File snapshot / progress tidak terbuat saat Scan
* **Sebab:** Izin tulis folder `writable/` belum diberikan ke web server Linux.
* **Solusi:** Jalankan perintah izin akses di terminal:
  ```bash
  cd /www/wwwroot/dda.jateng.pro
  chmod -R 775 writable
  chown -R www-data:www-data writable  # atau www:www jika Nginx aaPanel
  ```

