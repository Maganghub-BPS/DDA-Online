# Panduan Lengkap Hosting CodeIgniter 4 & Proyek DDA-Online

Panduan ini mencakup langkah demi langkah men-deploy aplikasi **CodeIgniter 4 (CI4)**—baik untuk kebutuhan website CI4 standar maupun khusus untuk sistem **DDA-Online** yang dilengkapi mesin pemroses **Python (`python_engine/`)**.

---

## Bagian 1: Prasyarat Server (Spesifikasi Wajib)

Sebelum mengunggah kode, pastikan server/hosting memenuhi syarat berikut:

1. **Versi PHP**: Minimal **PHP 8.1** atau **PHP 8.2** (Rekomendasi: PHP 8.2).
2. **Ekstensi PHP Wajib (CI4)**:
   - `php-intl` *(Sangat wajib di CI4, jika mati akan muncul HTTP Error 500)*
   - `php-mbstring`
   - `php-curl`
   - `php-json`
   - `php-mysqlnd` / `php-mysqli`
   - `php-xml` & `php-zip`
3. **Khusus Proyek DDA-Online (Fitur Matching PDF & Export Excel)**:
   - Fungsi PHP `shell_exec`, `exec`, dan `popen` **tidak boleh masuk daftar `disable_functions`** di `php.ini`.
   - Terinstal **Python 3.10+** beserta `pip` dan package: `pymupdf` (`fitz`), `openpyxl`, `requests`.

> **Catatan Penting:**
> Jika Anda hanya ingin menayangkan **Halaman Publik / Tabel Dinamis & API** tanpa menjalankan *Matching PDF* di server produksi (misalnya proses matching dilakukan di lokal lalu file JSON/Excel di-upload ke server), maka **Shared Hosting / cPanel biasa sudah cukup**. Namun jika fitur *Verifikasi PDF & Rekap Excel* ingin dijalankan langsung di server, gunakan **VPS (Ubuntu/Debian) atau Cloud Hosting dengan akses Terminal/SSH**.

---

## Bagian 2: Skenario A — Deploy di VPS Linux (Ubuntu / Nginx / Apache / aaPanel)

Ini adalah skenario paling ideal untuk proyek **DDA-Online**.

### Langkah 1: Clone / Upload Proyek ke Server
Masuk ke SSH server Anda, lalu letakkan proyek di `/var/www/dda-online`:

```bash
cd /var/www
git clone -b tabel_dinamis https://github.com/Maganghub-BPS/DDA-Online.git dda-online
cd dda-online
```

### Langkah 2: Install Dependency PHP (Composer)
Jalankan Composer untuk mengunduh library vendor tanpa paket *development*:

```bash
composer install --no-dev --optimize-autoloader
```

### Langkah 3: Setup Python Engine (Khusus DDA-Online)
Instal Python 3 dan library yang dibutuhkan oleh `python_engine/`:

```bash
sudo apt update && sudo apt install -y python3 python3-pip python3-venv

# Install library Python yang dibutuhkan engine DDA-Online
pip3 install pymupdf openpyxl requests --break-system-packages
```

### Langkah 4: Konfigurasi File `.env`
Salin template `env` menjadi `.env` dan sesuaikan isinya:

```bash
cp env .env
nano .env
```

Ubah bagian berikut di dalam `.env`:

```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://domain-anda.go.id/'
app.indexPage = ''

database.default.hostname = localhost
database.default.database = nama_db_anda
database.default.username = user_db_anda
database.default.password = password_db_anda
database.default.DBDriver = MySQLi
database.default.port = 3306
```

### Langkah 5: Atur Hak Akses Folder (`writable/`)
Di CodeIgniter 4, folder `writable/` (sama seperti `storage/` di Laravel) wajib bisa ditulis oleh user web server (`www-data`):

```bash
sudo chown -R www-data:www-data /var/www/dda-online/writable
sudo chmod -R 775 /var/www/dda-online/writable
```
*(Pastikan folder `writable/Uploads`, `writable/cache`, `writable/logs`, dan `writable/session` memiliki izin tulis).*

### Langkah 6: Arahkan Document Root Web Server ke `/public`
Sama seperti Laravel, jangan arahkan domain ke root folder, melainkan ke subfolder **`/public`**.

#### Contoh Konfigurasi Apache VirtualHost (`/etc/apache2/sites-available/dda.conf`):
```apache
<VirtualHost *:80>
    ServerName domain-anda.go.id
    DocumentRoot /var/www/dda-online/public

    <Directory /var/www/dda-online/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dda_error.log
    CustomLog ${APACHE_LOG_DIR}/dda_access.log combined
</VirtualHost>
```
*(Jangan lupa jalankan `sudo a2enmod rewrite && sudo systemctl restart apache2`).*

#### Contoh Konfigurasi Nginx (`/etc/nginx/sites-available/dda`):
```nginx
server {
    listen 80;
    server_name domain-anda.go.id;
    root /var/www/dda-online/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## Bagian 3: Skenario B — Deploy di Shared Hosting / cPanel (`public_html`)

Jika Anda menggunakan cPanel di mana domain utama **terkunci ke folder `public_html`** dan tidak bisa diubah Document Root-nya:

```text
/home/username/ (Root cPanel)
 ├── dda_core/       <-- Berisi app/, vendor/, writable/, python_engine/, .env, spark
 └── public_html/    <-- Berisi seluruh isi dari folder public/ (index.php, .htaccess, assets/)
```

### Langkah-langkah di cPanel:
1. Buat folder baru di `/home/username/dda_core` (di **luar** `public_html`).
2. Upload seluruh file proyek (`app/`, `vendor/`, `writable/`, `python_engine/`, `.env`, `spark`) ke dalam `/home/username/dda_core`.
3. Pindahkan seluruh isi dari dalam folder `public/` (`index.php`, `.htaccess`, `assets/`, dll.) ke dalam `/home/username/public_html/`.
4. Buka file `/home/username/public_html/index.php`, lalu ubah baris `$pathsConfig`:
   ```php
   // Ubah dari:
   require FCPATH . '../app/Config/Paths.php';

   // Menjadi mengarah ke folder dda_core:
   require FCPATH . '../dda_core/app/Config/Paths.php';
   ```
5. Masuk ke menu **Select PHP Version** di cPanel:
   - Pilih **PHP 8.1** atau **8.2**.
   - Centang ekstensi **`intl`**, **`mbstring`**, **`curl`**, **`mysqli`**, **`zip`**.
6. Buat database di **MySQL Databases** cPanel, import file `.sql` lewat **phpMyAdmin**, dan sesuaikan kredensialnya di `/home/username/dda_core/.env`.

---

## Bagian 4: Penyesuaian Kode untuk Kompatibilitas Windows & Linux (Cross-Platform)

Di file `app/Controllers/Admin.php`, pemanggilan *background worker* Python saat ini menggunakan perintah khusus Windows (`start /B python ... > NUL`). Agar sistem berjalan otomatis baik di lokal (Windows) maupun di server produksi (Linux), gunakan pola berikut pada `Admin.php`:

```php
$isWindows = (PHP_OS_FAMILY === 'Windows');
$pyBin = getenv('PYTHON_BIN') ?: ($isWindows ? 'python' : 'python3');

// 1. Untuk eksekusi sinkron (compare_table.py, match_pdf.py, export_excel.py):
$command = $pyBin . " " . escapeshellarg($pythonScript) . " ... 2>&1";
$output = shell_exec($command);

// 2. Untuk eksekusi background worker (batch_verify.py & export_global_excel.py):
if ($isWindows) {
    $cmd = "start /B {$pyBin} " . $args . " > NUL 2>" . escapeshellarg($errLog);
    pclose(popen($cmd, "r"));
} else {
    $cmd = "nohup {$pyBin} " . $args . " > /dev/null 2>" . escapeshellarg($errLog) . " &";
    exec($cmd);
}
```

---

## Bagian 5: Checklist Troubleshooting Umum Saat Baru Naik Hosting

| Gejala Error | Penyebab | Solusi Cepat |
| :--- | :--- | :--- |
| Muncul halaman **Whoops! We seem to have hit a snag** | Error tersembunyi karena mode `production` | Cek log detailnya di `writable/logs/log-YYYY-MM-DD.log` atau ubah sementara `CI_ENVIRONMENT = development` di `.env`. |
| **HTTP Error 500** saat baru buka halaman utama | Ekstensi `php-intl` belum aktif atau folder `writable/` tidak bisa ditulis | Aktifkan ekstensi `intl` di PHP dan jalankan `chmod -R 775 writable/`. |
| Halaman utama bisa dibuka, tapi **klik menu lain 404 Not Found** | File `.htaccess` di folder `public/` tidak ikut ter-upload (karena hidden file) atau `mod_rewrite` mati | Pastikan file `public/.htaccess` ada di `public_html` dan `app.indexPage = ''` di `.env`. |
| **Matching PDF / Export Excel gagal** di VPS Linux | Perintah `python` di Linux bernama `python3` dan `start /B` hanya untuk Windows | Gunakan deteksi `PHP_OS_FAMILY` seperti pada Bagian 4 di atas. |

