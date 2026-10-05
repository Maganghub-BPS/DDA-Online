# 📋 Rencana Implementasi Hasil Audit (Implementation Plan)
**Sistem**: DDA Online BPS Provinsi Jawa Tengah  
**Fokus**: Pasca Penambahan Fitur Saklar Status Tampil (*Publish / Draft*)  
**Status**: 🟢 *Item 1 dan Item 2 Telah Selesai Dieksekusi & Terverifikasi*  

---

## 📌 Ringkasan Rencana Aksi

Berdasarkan audit menyeluruh dan arahan pengguna, berikut status implementasi dari item-item rencana perbaikan:

| No | Modul / Area | Item Rencana Implementasi | Tingkat Prioritas | Status Eksekusi |
| :---: | :--- | :--- | :---: | :---: |
| **1** | **Keamanan (RBAC)** | Pembatasan Hak Akses Saklar Publish Khusus Admin & Super Admin | 🔴 **Tinggi** | ✅ **Selesai Dieksekusi** |
| **2** | **Privasi Frontend** | Proteksi Akses URL Langsung untuk Tabel Berstatus Draft | 🟡 **Sedang** | ✅ **Selesai Dieksekusi** |
| **3** | **Katalog Frontend** | Filter Dropdown Tahun di Pencarian Publik (`is_publish = 1`) | 🟢 **Rendah** | ⏸️ *Dilewati (Tetap seperti semula)* |
| **4** | **Performa Database**| Indeks Komposit `(tahun, is_publish)` pada `t_tahun_tabel` | 🟢 **Rendah** | ⏳ *Menunggu Arahan Lanjutan* |
| **5** | **Pelaporan Admin** | Penambahan Kolom "Sudah Publish" pada Ekspor Excel Instansi | 🟢 **Rendah** | ⏳ *Menunggu Arahan Lanjutan* |
| **6** | **Integritas Data**  | Resolusi 6 Rekord Master Tabel Tanpa OPD Valid | ⚪ **Opsional** | ⏳ *Menunggu Arahan Lanjutan* |

---

## 🛠️ Rincian Rencana Implementasi per Item

### 1. Pembatasan Hak Akses Saklar Publish Khusus Akun BPS (Prioritas: Tinggi)
- **Latar Belakang**:
  Saat ini, endpoint AJAX `act_toggle_publish()` hanya memvalidasi apakah pengguna sudah login, tanpa memeriksa apakah pengguna berasal dari BPS atau OPD (`walidata`). Dalam tata kelola BPS, penerbitan data ke publik adalah otoritas mutlak BPS.
- **Rencana Perubahan Kode**:
  1. **Backend (`Admin.php`)**:
     Tambahkan validasi hak akses pada `act_toggle_publish()`:
     ```php
     $user_level = strtolower(trim($this->session->get('admin_level') ?? ''));
     $user_unit  = strtolower(trim($this->session->get('admin_unitkerja') ?? ''));
     
     // Hanya BPS (Super Admin, Admin, atau unit kerja BPS) yang diizinkan
     if ($user_unit !== 'bps' && !in_array($user_level, ['admin', 'super admin', 'superadmin'])) {
         return $this->response->setJSON([
             'status' => 'error',
             'message' => 'Akses ditolak! Hanya Admin BPS yang berwenang mempublikasikan tabel ke publik.'
         ])->setStatusCode(403);
     }
     ```
  2. **View Tampilan (`l_dda_periksa.php`)**:
     Jika pengguna yang login bukan BPS, saklar otomatis di-`disabled` dan dilengkapi tooltip informasi: *"Hanya Admin BPS yang berwenang mengubah status publikasi"*.

---

### 2. Proteksi Akses URL Langsung untuk Tabel Draft (Prioritas: Sedang)
- **Latar Belakang**:
  Saat ini filter `is_publish = 1` aktif pada hasil pencarian dan katalog beranda. Namun jika seseorang memiliki tautan lama atau menebak ID tabel (`/home/view_sheet?table_id=...` atau `/home/view_tabel?id=...`), halaman detail masih dapat terbuka meskipun berstatus Draft.
- **Rencana Perubahan Kode**:
  1. Pada `Home::view_sheet()`:
     ```php
     if ($dda_info && (int)($dda_info->is_publish ?? 0) !== 1) {
         // Cek apakah admin sedang login (jika admin sedang login, tetap diizinkan preview)
         $isAdmin = session()->get('admin_valid') && !empty(session()->get('admin_id'));
         if (!$isAdmin) {
             return redirect()->to('/home/search')->with('error', 'Tabel ini belum dipublikasikan untuk umum.');
         }
     }
     ```
  2. Pada `Home::view_tabel()`:
     Lakukan pengecekan status `is_publish` serupa sebelum merender tampilan tabel dinamis.

---

### 3. Filter Dropdown Tahun di Pencarian Publik (Prioritas: Rendah)
- **Latar Belakang**:
  Dropdown "Pilih Tahun" di halaman Jelajah Data mengambil semua tahun dari `t_tahun_tabel`. Jika ada tahun baru hasil duplikasi yang seluruh tabelnya masih Draft, tahun tersebut akan muncul di filter dan menghasilkan pencarian kosong.
- **Rencana Perubahan Kode**:
  Di `M_frontend::getAvailableYears()`:
  ```diff
   $rows = $this->db->table('t_tahun_tabel')
       ->select('DISTINCT(tahun) as tahun')
       ->where('tahun IS NOT NULL')
       ->where("tahun != ''")
  +    ->where('is_publish', 1)
       ->orderBy('tahun', 'DESC')
       ->get()
       ->getResultArray();
  ```

---

### 4. Penambahan Indeks Komposit Database (Prioritas: Rendah / Optimasi)
- **Latar Belakang**:
  Tabel `t_tahun_tabel` sering diakses dengan kondisi `WHERE tahun = ? AND is_publish = 1`. Saat ini belum ada indeks pada kedua kolom tersebut.
- **Rencana Aksi SQL**:
  ```sql
  ALTER TABLE t_tahun_tabel ADD INDEX idx_tahun_publish (tahun, is_publish);
  ```
- **Manfaat**: Menjamin pencarian multi-tahun tetap cepat di bawah 5 milidetik seiring membesarnya basis data DDA.

---

### 5. Penambahan Kolom Status Publish pada Ekspor Excel Admin (Prioritas: Rendah)
- **Latar Belakang**:
  Menu unduh laporan instansi saat ini hanya merangkum: Total Tabel, Belum Diisi, Menunggu Validasi, dan Sudah Validasi. Pimpinan BPS belum bisa melihat jumlah tabel yang sudah benar-benar live/publik di frontend.
- **Rencana Perubahan Kode**:
  Di `Admin::export_excel_instansi()`:
  1. Tambahkan agregasi pada query SQL:
     ```sql
     SUM(CASE WHEN t.is_publish = 1 THEN 1 ELSE 0 END) as sudah_publish
     ```
  2. Tambahkan kolom header dan data `<th>Sudah Dipublish</th>` pada baris tabel Excel.

---

### 6. Resolusi Data Orphan Unit Kerja (Prioritas: Opsional)
- **Latar Belakang**:
  Terdapat 6 tabel master dengan kode instansi yang belum terdaftar di tabel `m_unitkerja`:
  - 5 tabel dengan `id_unitkerja = 'kemenkumham'` (Data Narapidana & Izin Tinggal WNA).
  - 1 tabel dengan `id_unitkerja = 'kopertis6'` (Data Perguruan Tinggi Agama).
- **Rekomendasi Tindakan**:
  - Mendaftarkan entitas `kemenkumham` (Kantor Wilayah Kementerian Hukum dan HAM Jawa Tengah) dan `kopertis6` (LLDIKTI Wilayah VI) ke tabel `m_unitkerja` agar nama instansinya muncul rapi di filter instansi frontend.

---

## 💬 Silakan Berikan Masukan / Komentar
Apakah seluruh item rencana di atas disetujui untuk dieksekusi, atau ada poin tertentu yang ingin disesuaikan terlebih dahulu?
