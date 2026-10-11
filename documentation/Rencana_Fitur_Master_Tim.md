# Rencana Implementasi Fitur Master Tim (Opsi B: Tab Anggota & OPD Binaan)

## 1. Latar Belakang & Tujuan
Saat ini, menu Master Tim (`/admin/master_tim`) hanya menampilkan daftar akun `t_admin` dengan `level = 'lo'`. Di dalamnya, akun tim kelompok kerja (`tim1` s.d `tim6`) masih bercampur dengan akun personil pegawai BPS (`rizchi`, `septyaji`, dll.). Belum ada relasi formal yang mengelompokkan pegawai ke dalam tim tertentu.

**Tujuan Pengembangan:**
1. Membentuk entitas **Tim** yang menaungi banyak **Anggota** (staf BPS).
2. Memungkinkan Admin/Super Admin untuk **menambah** atau **menghapus anggota** dari suatu tim, serta **menambah/menghapus tim**.
3. Menyediakan visibilitas bagi Admin untuk melihat **OPD binaan apa saja** yang ditangani oleh masing-masing tim secara langsung di halaman yang sama (menggunakan **Opsi B: Sistem Tab**).
4. Menghilangkan kebutuhan *account sharing* (rebutan akun bersama `tim1`), sehingga pegawai dapat login dengan akun personalnya masing-masing.

---

## 2. Analisis Data Eksisting

### A. Data Tim & Relasi OPD
* **Kode Tim Eksisting**: Di `t_admin`, sudah ada akun:
  - `tim1`: TIM 1 JAWA TENGAH DALAM ANGKA
  - `tim2`: TIM 2 JAWA TENGAH DALAM ANGKA
  - `tim3`: TIM 3 JAWA TENGAH DALAM ANGKA
  - `tim4`: TIM 4 JAWA TENGAH DALAM ANGKA
  - `tim5`: TIM 5 JAWA TENGAH DALAM ANGKA
  - `tim6`: TIM 6 JAWA TENGAH DALAM ANGKA
* **Relasi OPD Eksisting**: Di tabel `m_unitkerja`, kolom `user_wali` sudah diisi dengan kode tim tersebut:
  - `tim1` membina: *Dinkes, BPBD, Disnaker, Dinsos, Dinpendidikan, Imigrasi, Lapas, Pegadaian*, dll.
  - `tim2` membina: *BPJS, BPOM, DP3AKB, Kejaksaan, Kemenag, Kesbangpol, KONI*, dll.
  - `tim3` membina: *BPDAS, Bulog, DKP, Lingkungan Hidup, Perhutani, Pertanian*, dll.
  - `tim4` membina: *BPKAD, Dishub, Disperindag, Bea Cukai (DJBC), ESDM, Kadin, PLN*, dll.
  - `tim5` membina: *BPKP, KAI, Koperasi, PTSP/Modal, OJK*, dll.
  - `tim6` membina: *BPN, BPPD, Disdukcapil, DJP Pajak, KPU, BMKG Klimatologi*, dll.

### B. Hierarki Role & Kedudukan
* **Super Admin & Admin (Diseminasi/IPDS)**: Pemilik & pengelola sistem. Berdiri di atas seluruh tim, berhak mengelola master tim, dan memegang hak final publikasi.
* **SPV (Supervisor)**: Penanggung jawab/pengawas tim (tersambung via `m_unitkerja.user_spv`).
* **LO (Liaison Officer)**: Pegawai teknis BPS yang didaftarkan menjadi **Anggota Tim**. Semua personil berkedudukan setara sebagai anggota tim pendamping OPD (tidak ada jabatan "Ketua Tim" di sistem).
* **Walidata (OPD)**: Mitra binaan di luar struktur tim BPS.
* **Kominfo**: Mitra pemantau koordinasi SDI tingkat Pemprov.

---

## 3. Konsep Desain Antarmuka (UI/UX - Format 1: Tabel Ringkas Serasi dengan Master OPD)

Untuk menghindari duplikasi fungsi dengan menu **Master OPD** (yang sudah mengelola penugasan OPD ke tim dan pengawas), Master Tim difokuskan 100% pada **Manajemen Tim & Personil Anggota BPS** dengan format tabel yang serasi dan konsisten:

### Tabel Master Tim
| No | Kode Tim | Nama Tim | OPD Binaan | Anggota Tim (LO BPS) | Aksi |
|:---:|:---:|:---|:---:|:---|:---:|
| 1 | `TIM1` | Tim 1 Jawa Tengah Dalam Angka | `10 OPD` | `[Herry]` `[Hesti]` `[Indah]` | [ 👥 Kelola (3) ] [ 🗑️ ] |
| 2 | `TIM2` | Tim 2 Jawa Tengah Dalam Angka | `15 OPD` | `Belum ada anggota` | [ 👥 Kelola (0) ] [ 🗑️ ] |

### Modal Kelola Anggota
Setiap baris tim memiliki tombol **[ 👥 Kelola ]** yang membuka modal dialog interaktif untuk:
1. Menambah pegawai BPS baru ke tim tersebut (form dropdown staf BPS).
2. Melihat daftar personil anggota tim saat ini (lengkap dengan NIP & Username).
3. Mengeluarkan/mencopot personil dari tim via tombol `[ ❌ Keluarkan ]` tanpa menghapus akun pegawai.

---

## 4. Rencana Struktur Database

Untuk menjaga kompatibilitas 100% dan tidak merusak alur aplikasi yang sedang berjalan, kita hanya perlu menambahkan **1 tabel relasi penghubung**:

### Tabel: `t_tim_anggota`
| Kolom | Tipe Data | Keterangan |
|:---|:---|:---|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | ID entri |
| `kode_tim` | VARCHAR(20) | Foreign key logis ke `t_admin.username` (misal: `'tim1'`) |
| `id_admin` | INT | Foreign key ke `t_admin.id` (ID pegawai BPS) |
| `created_at` | DATETIME / TIMESTAMP | Waktu penambahan anggota |

* **Relasi OPD ke Tim**: Tetap menggunakan `m_unitkerja.user_wali = 'tim1'`, tidak memerlukan migrasi tabel OPD.
* **Relasi Pegawai ke Tim**: Dikelola melalui tabel `t_tim_anggota`.

---

## 5. Alur Fitur & Interaksi (User Flow)

### A. Tambah Anggota ke Tim
1. Admin menekan tombol `[ + Tambah Anggota ke Tim ]` pada kartu tim terkait.
2. Muncul modal dialog:
   - Dropdown pencarian pegawai BPS (dari `t_admin WHERE id_unitkerja = 'bps'`).
   - Opsi memfilter pegawai yang belum terdaftar di tim manapun (atau mengizinkan pegawai yang tersedia).
3. Klik `Simpan` $\rightarrow$ Entri baru tersimpan di `t_tim_anggota` dan tabel anggota tim langsung diperbarui via AJAX / reload.

### B. Hapus / Keluarkan Anggota dari Tim
1. Admin menekan tombol `[ ❌ Keluarkan ]` di baris nama pegawai.
2. Muncul dialog konfirmasi: *"Keluarkan [Nama Pegawai] dari [Nama Tim]?"*.
3. Jika disetujui $\rightarrow$ Entri pada `t_tim_anggota` dihapus. Akun pegawai pada `t_admin` tetap aman dan utuh.

### C. Tambah Tim Baru
1. Admin menekan tombol `[ + Tambah Tim Baru ]` di header atas.
2. Form modal:
   - Nama Tim (misal: *Tim 7 Jawa Tengah Dalam Angka*)
   - Kode Tim (misal: *tim7*)
3. Sistem membuat akun tim induk di `t_admin` (`level = 'lo'`) sehingga kode tim tersebut langsung siap dipakai untuk penugasan di *Master OPD*.

### D. Hapus Tim
1. Tombol `[ Hapus Tim ]` pada kartu tim.
2. Validasi keamanan: Jika tim masih memiliki OPD binaan di `m_unitkerja`, sistem menolak penghapusan dengan notifikasi: *"Pindahkan penugasan OPD terlebih dahulu sebelum menghapus tim ini."*

---

## 6. Hak Akses (Role Permission) & Logika Akses OPD
* **Akses Menu Master Tim**: Hanya dapat diakses oleh **Super Admin** dan **Admin (BPS Diseminasi/IPDS)**. Role `lo`, `spv`, `walidata`, `kominfo` disembunyikan.
* **Kebijakan Keanggotaan Tim (Opsi B: Strict 1 Personil = 1 Tim)**:
  - Anggota tim **HANYA** boleh diisi oleh personil dengan `level = 'lo'`.
  - Satu personil LO **hanya dapat bertugas di 1 tim saja**.
  - Dropdown "Tambah Pegawai (LO BPS)" menampilkan status personil:
    - `[Tersedia]` jika belum memiliki tim (dapat dipilih).
    - `[Sudah di Tim X]` dengan status `disabled` jika sudah terdaftar di tim lain.
  - Sisi backend memvalidasi ketat: jika personil sudah terdaftar di tim lain, penambahan ditolak dengan notifikasi informatif untuk mengeluarkan personil tersebut dari tim lama terlebih dahulu.
  - Akun `Super Admin`, `Admin`, dan instansi lain **tidak dapat** dimasukkan sebagai anggota tim agar tidak merusak hierarki pengawasan sistem.
* **Logika Otorisasi OPD Saat Login**:
  - **Login sebagai `Super Admin` / `Admin`:**
    - Memiliki akses **Global (Seluruh 69 OPD se-Jateng)** tanpa batasan tim.
  - **Login sebagai `LO` (misal akun `herry`):**
    - Sistem membaca relasi keanggotaan di tabel `t_tim_anggota` (`id_admin = ?`).
    - Daftar OPD di menu **Input Data DDA** dan **Forum Diskusi** otomatis difilter hanya menampilkan OPD binaan timnya (`WHERE user_wali IN (kode_tim)`).
    - Akun `herry` yang tergabung di `tim1` otomatis hanya dapat mengakses **10 OPD binaan Tim 1** dan tidak bisa mengakses tim lain.

---

## 7. Status Eksekusi & Pengujian (Telah Selesai)
1. **Database Migration**: Tabel `t_tim_anggota` telah dibuat di MySQL lokal `supernewddaonline`.
2. **Backend Controller (`Admin.php`)**:
   - `master_tim()`: Validasi penambahan anggota dibatasi ketat hanya `level = 'lo'` dan menerapkan **Opsi B (1 personil 1 tim)**.
   - `dda()`: Penyesuaian query filter OPD untuk `level = 'lo'` berbasis `t_tim_anggota`.
   - `forum_diskusi()`: Penyesuaian query filter OPD untuk `level = 'lo'` berbasis `t_tim_anggota`.
3. **Frontend View (`l_master_tim.php`)**:
   - Format 1 (Tabel Bersih Serasi Master OPD) telah diterapkan.
   - Dropdown modal menampilkan indikator `— [Tersedia]` vs `— [Sudah di Tim X] (disabled)`.
4. **Hasil Pengujian**:
   - Simulasi penambahan personil terdaftar (`herry` ke Tim 2): Ditolak aman oleh server dengan pesan informatif.
   - Simulasi penambahan personil bebas (`aris` ke Tim 2): Berhasil lolos.
   - Akun `herry` (ID 9, Tim 1): Terfilter presisi menampilkan 10 OPD binaan Tim 1.
   - Akun `admin` (ID 1, Super Admin): Menampilkan seluruh 69 OPD.
   - Dropdown penambahan anggota: Menampilkan 15 personil LO BPS yang sah, akun Admin/Super Admin tidak tercampur.
