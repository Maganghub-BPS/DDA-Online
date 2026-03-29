# Cara Running Sistem DDA Online (CodeIgniter 4)

Panduan ini menjelaskan langkah-langkah untuk menjalankan sistem DDA Online Versi Codeigneter Versi 4.7.0, baik dari file `.rar` yang sudah jadi maupun dengan melakukan _cloning_ dari repositori GitHub.

---

## Prasyarat Sistem (System Requirements)

Sebelum memulai, pastikan perangkat Anda sudah terinstall:

- **Laragon** (Sangat disarankan untuk memudahkan konfigurasi Apache/Nginx, PHP, dan MySQL).
- **PHP 8.2** atau versi di atasnya.
- **Composer** (Hanya diperlukan jika melakukan cloning dari GitHub).
- **Git** (Hanya diperlukan jika melakukan cloning dari GitHub).

### Konfigurasi PHP & Server (Laragon):

Pastikan pengaturan berikut sudah aktif (Klik kanan icon Laragon):

1. **PHP Extensions:** (PHP -> Extensions)
   - `intl` (Wajib untuk CodeIgniter 4)
   - `mbstring`
   - `curl`
   - `openssl`
   - `gd` (Untuk manipulasi gambar)
   - `xml`
   - `mysqli`
2. **Apache Modules:** (Apache -> Modules)
   - `rewrite_module` (Wajib agar URL bersih tanpa `index.php` berjalan).

---

## Opsi 1: Menjalankan dari File .rar (Full Folder)

Gunakan cara ini jika Anda mendapatkan file `.rar` yang sudah berisi folder sistem lengkap dengan folder `vendor`.

1. **Ekstrak File:**
   - Ekstrak file `.rar` tersebut ke dalam direktori root web server Anda. Jika menggunakan Laragon, biasanya ada di: `C:\laragon\www\`.
   - Pastikan nama foldernya adalah `dda-online-ci4` atau sesuaikan dengan keinginan Anda.

2. **Setup Database:**
   - Buka Laragon, pastikan Apache dan MySQL sudah **Start**.
   - Buka **phpMyAdmin** atau **Adminer** (akses melalui browser: `http://localhost/phpmyadmin`).
   - Buat database baru dengan nama `ddaonline`.
   - **Import** file database `ddaonline.sql` yang ada di dalam folder root project ke database `ddaonline` yang baru Anda buat.

3. **Konfigurasi Environment (.env):**
   - Buka folder project Anda di text editor (VS Code/Sublime/Notepad++).
   - Cari file bernama `.env`.
   - Sesuaikan `app.baseURL`:
     ```env
     app.baseURL = 'http://localhost/dda-online-ci4/public/'
     ```
     _(Sesuaikan nama folder `dda-online-ci4` jika Anda mengubahnya)._
   - Sesuaikan konfigurasi database (jika Anda menggunakan user/password MySQL yang berbeda):
     ```env
     database.default.hostname = 127.0.0.1
     database.default.database = ddaonline
     database.default.username = root
     database.default.password =
     ```

4. **Akses Web:**
   - Buka browser dan akses URL: `http://localhost/dda-online-ci4/public/`

---

## Opsi 2: Cloning dari GitHub

Gunakan cara ini jika Anda ingin mengambil kode terbaru dari repositori GitHub.

1. **Clone Repository:**
   - Buka terminal atau Command Prompt di folder `C:\laragon\www\`.
   - Jalankan perintah:
     ```bash
     git clone https://github.com/MuhammadBayuNugroho/dda-online-ci4.git
     ```
   - Masuk ke folder project: `cd dda-online-ci4`

2. **Install Library (Vendor):**
   - Karena folder `vendor` tidak ada di GitHub (diabaikan oleh git), Anda harus menginstallnya menggunakan Composer.
   - Jalankan perintah:
     ```bash
     composer install
     ```

3. **Setup Environment (.env):**
   - Duplikat/copy file `env` (tanpa titik) menjadi `.env`:
     ```bash
     cp env .env
     ```
   - Lakukan konfigurasi `app.baseURL` dan database seperti pada **Opsi 1 Langkah 3**.

4. **Setup Database:**
   - Lakukan import database `ddaonline.sql` seperti pada **Opsi 1 Langkah 2**.

5. **Akses Web:**
   - Buka browser dan akses URL: `http://localhost/dda-online-ci4/public/`

---

## Troubleshooting (Masalah Umum)

- **Error 404 / File Not Found:** Pastikan Anda mengakses URL dengan tambahan `/public/` di belakangnya.
- **Tips Laragon Virtual Host:** Jika Anda menaruh folder ini di `C:\laragon\www\dda-online-ci4`, Laragon akan otomatis membuatkan domain `http://dda-online-ci4.test`. Gunakan URL tersebut agar akses lebih rapi dan mirip dengan server produksi.
- **White Screen / Error PHP:** Pastikan versi PHP Anda minimal 8.2 dan ekstensi `intl` sudah aktif.
- **Database Connection Error:** Periksa kembali file `.env` dan pastikan nama database, username, dan password sudah benar.

---

_Dibuat untuk sistem DDA Online - Muhammad Bayu Nugroho_
