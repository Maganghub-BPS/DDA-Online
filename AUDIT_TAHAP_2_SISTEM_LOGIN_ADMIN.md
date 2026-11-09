# Panduan Eksekusi & Temuan Audit Tahap 2 Sistem Login Admin DDA Online (Spesifikasi Anti-Salah untuk Gemini Flash 3.8)

**Tanggal Audit Tahap 2**: 8 November 2026  
**Target Eksekutor**: Gemini Flash 3.8  
**Cakupan**: 100% Area Login (`app/Controllers/Admin.php`, `app/Controllers/Mail.php`, `app/Models/M_tabeldinamis.php`, `app/Models/M_kelolakegiatan.php`, `app/Views/admin/*`).  
**Pengecualian**: Modul Publik (`Home.php`, `M_frontend.php`, `Views/frontend/*`) dan Modul Matching PDF (`matching_pdf*` di `Admin.php` baris 3765–5375).

---

## ⚠️ ATURAN WAJIB UNTUK GEMINI FLASH 3.8 SEBELUM MENGEKSEKUSI (GUARDRAILS)

> [!CAUTION]
> **WAJIB DIBACA OLEH MODEL EKSEKUTOR (GEMINI FLASH 3.8):**
> 1. **JANGAN PERNAH** menulis ulang seluruh isi file besar seperti `app/Controllers/Admin.php` (7.805 baris) menggunakan `write_file`. Selalu gunakan `edit_file` hanya pada blok baris yang ditunjuk!
> 2. **GUNAKAN BLOK `TARGET` DAN `REPLACEMENT` PERSIS SEPERTI YANG DITULIS DI DOKUMEN INI**. Seluruh blok `TARGET` di bawah ini telah diverifikasi karakter-demi-karakter terhadap isi file terkini pasca-eksekusi TASK-01 s.d. TASK-24.
> 3. **JANGAN MENGUBAH PASSWORD MD5 TANPA FALLBACK MD5** di `do_login()` dan `passwod()`, karena akun eksisting di database saat ini masih berformat MD5 32 karakter. Kode pada **TASK-27** sudah menyediakan *Dual-Mode Auto-Upgrade* (mendukung `password_verify()` Bcrypt sekaligus fallback `md5()` yang otomatis meng-upgrade hash ke Bcrypt saat user login).
> 4. **JANGAN MENGUBAH KODE DI LUAR INSTRUKSI** (terutama fungsi-fungsi `matching_pdf*` dan modul non-login `Home.php`).
> 5. Setelah selesai mengeksekusi setiap TASK, jalankan perintah `php -l <nama_file>` untuk memastikan tidak ada *syntax error*.

---

## Ringkasan 14 Temuan Audit Tahap 2 & Pemetaan ke Task Eksekusi (TASK-25 s.d. TASK-35)

| Kode Temuan | Kategori | Deskripsi Singkat | Nomor Task Eksekusi | Status |
| :--- | :---: | :--- | :---: | :---: |
| **P0-A** | Kritis | Raw SQL String Interpolation (SQL Injection) di View `f_manage_admin.php:16` & `f_unitkerja.php:15` + XSS pada atribut input form | **TASK-25** | ✅ Selesai |
| **P0-B** | Kritis | Risiko kehilangan data historis permanen (`ON DELETE CASCADE`) saat mengubah `jenis_sumber` ke `api_kominfo` di `Admin::master_tabel()` (`act_edt`) | **TASK-26** | ✅ Selesai |
| **P0-C** | Kritis | Password hashing masih `md5()` tanpa salt, belum ada `$this->session->regenerate(true)` saat login, self-deletion & duplikasi username saat edit di `manage_admin()` | **TASK-27** | ⏳ [ ] Belum |
| **P0-D** | Kritis | Hardcoded fallback token produksi `SATUDATA_TOKEN` di `Admin::callApi2()` | **TASK-28** | ✅ Selesai |
| **P1-A** | Tinggi | Endpoint publik di `Admin.php` (`check_new_table_conflict`, `ajax_get_last_table_number`, `ambil_data`, `ambil_databatch`, `ambil_tahun_terakhir_dari_match`, `download_xlsx_template`) & `Mail.php` tanpa guard RBAC | **TASK-29** | ✅ Selesai |
| **P1-B** | Tinggi | Celah IDOR lintas OPD/Tim pada `konfirmasi`, `act_konfirmasi`, `periksa`, `act_periksa`, `kondef`, `kondef_update`, serta upload Excel & catatan tabel + form `f_kondef.php` terbuka untuk role read-only | **TASK-30** | ✅ Selesai |
| **P1-C** | Tinggi | Bug logika boolean `||` pada tombol `+ Tambah Tabel` di `l_dda.php:59` yang muncul untuk semua user termasuk BPS & Walidata OPD | **TASK-31** | ✅ Selesai |
| **P1-D** | Tinggi | View Profil Saya `f_profil.php` (diverifikasi sudah ada, ditambahkan proteksi XSS esc(), minlength=4, & hapus double flashdata) | **TASK-32** | ✅ Selesai |
| **P2-A** | Sedang | N+1 Query Problem (31 query berurutan) pada halaman `Admin::master_tim()` | **TASK-33** | ✅ Selesai |
| **P2-B** | Sedang | Eksekusi 3 query agregasi `COUNT` ganda di `Admin::master_dimensi()` & `Admin::generator_tabel()` + XSS flashdata `{$nama_ind}` | **TASK-34** | ✅ Selesai |
| **P2-C & P3** | Sedang & Rendah | Celah XSS di View (`index.php`, `d_amain.php`, `l_dda_admin.php`, `l_forum.php`, `l_forum_topik.php`, `l_forum_komen.php`, `f_pengguna.php`, `f_master_tabel.php`, `l_master_tabel.php`), double flashdata render, dead JS di `l_master_tabel.php`, & dead legacy methods di `Admin.php` | **TASK-35** | ✅ Selesai |

---

## DAFTAR INSTRUKSI EKSEKUSI (STEP-BY-STEP)

---

### [x] TASK-25: Hapus SQL Injection & Amankan Input XSS di `f_manage_admin.php` dan `f_unitkerja.php` (Memperbaiki P0-A)

#### Langkah 25.1 — Gunakan Parameter Binding `?` & `esc()` di `app/Views/admin/f_manage_admin.php`
- **File**: [`app/Views/admin/f_manage_admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_manage_admin.php#L15-L24)
- **Instruksi**: Gunakan `edit_file` pada baris 15–24 dan baris 55–176:
- **TARGET 1 (Baris 15–24)**:
```php
    $db = \Config\Database::connect();
    $query_unitkerja = $db->query("SELECT * from m_unitkerja where id_unitkerja='$idunitkerja' LIMIT 1")->getRow();
    if ($query_unitkerja) {
        $idunitkerja_terpilih = $query_unitkerja->id_unitkerja;
        $unitkerja_terpilih = $query_unitkerja->unitkerja_ind;
    } else {
        $idunitkerja_terpilih = $idunitkerja;
        $unitkerja_terpilih = "Instansi Tidak Ditemukan ($idunitkerja)";
    }
```
- **REPLACEMENT 1**:
```php
    $db = \Config\Database::connect();
    $query_unitkerja = $db->query("SELECT * FROM m_unitkerja WHERE id_unitkerja = ? LIMIT 1", [$idunitkerja])->getRow();
    if ($query_unitkerja) {
        $idunitkerja_terpilih = $query_unitkerja->id_unitkerja;
        $unitkerja_terpilih = $query_unitkerja->unitkerja_ind;
    } else {
        $idunitkerja_terpilih = $idunitkerja;
        $unitkerja_terpilih = "Instansi Tidak Ditemukan (" . $idunitkerja . ")";
    }
```

- **TARGET 2 (Baris 55–176)**:
```php
                    <form action="<?php echo base_URL(); ?>index.php/admin/manage_admin/<?php echo $act; ?>" method="post" accept-charset="utf-8">
                        <input type="hidden" name="idp" value="<?php echo $idp; ?>">

                        <div class="row g-3">
                            <!-- Left Column: Security Settings -->
                            <div class="col-md-6 border-end border-light pe-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Pengaturan Keamanan</label>

                                <div class="mb-2">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Username Login</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-at text-primary-orange"></i></span>
                                        <input type="text" name="username" required value="<?php echo $username; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Username unik" tabindex="1" autofocus>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Password</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-key text-primary-orange"></i></span>
                                                <input type="password" name="password" required value="<?php echo $password; ?>"
                                                    class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="••••••••" tabindex="2">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Confirm Password</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-shield-check text-primary-orange"></i></span>
                                                <input type="password" name="password2" required value="<?php echo $password; ?>"
                                                    class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="••••••••" tabindex="3">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($mode == 'edt'): ?>
                                    <div class="alert bg-gray-50 border-0 py-2 px-3 border-radius-lg mb-3">
                                        <span class="text-xxs text-secondary"><i class="bi bi-info-circle-fill text-warning me-1"></i>Biarkan <strong>'-'</strong> jika tidak ingin mengubah password lama.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-0">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Email Korespondensi</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-envelope text-primary-orange"></i></span>
                                        <input type="email" name="email" required value="<?php echo $email; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="admin@jatengprov.go.id" tabindex="4">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Identity & Access -->
                            <div class="col-md-6 ps-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-3 ls-1">Identitas & Otoritas</label>

                                <div class="mb-3">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Nama Lengkap</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-person text-primary-orange"></i></span>
                                        <input type="text" name="nama" required value="<?php echo $nama; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama lengkap beserta gelar" tabindex="5">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">N I P</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-upc text-primary-orange"></i></span>
                                        <input type="text" name="nip" required value="<?php echo $nip; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="19XXXXXXXXXXXXXX" tabindex="6">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-7">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Asal Instansi</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none bg-white">
                                                <select name="unitkerja" id="unitkerja" class="form-select border-0 py-2 text-sm" tabindex="8" required>
                                                    <?php
                                                    $db = \Config\Database::connect();
                                                    $unitkerja_data = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();

                                                    if ($mode == "edt" || $mode == "act_edt") {
                                                        echo "<option selected value='" . $idunitkerja_terpilih . "'>" . $unitkerja_terpilih . "</option>";
                                                        foreach ($unitkerja_data as $p) {
                                                            if ($p['id_unitkerja'] != $idunitkerja_terpilih) {
                                                                echo "<option value='" . $p['id_unitkerja'] . "'>" . $p['unitkerja_ind'] . "</option>";
                                                            }
                                                        }
                                                    } else {
                                                        echo "<option selected value=''>- Pilih Instansi -</option>";
                                                        foreach ($unitkerja_data as $p) {
                                                            echo "<option value='" . $p['id_unitkerja'] . "'>" . $p['unitkerja_ind'] . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
```
- **REPLACEMENT 2**:
```php
                    <form action="<?php echo base_URL(); ?>index.php/admin/manage_admin/<?php echo esc($act); ?>" method="post" accept-charset="utf-8">
                        <input type="hidden" name="idp" value="<?php echo esc($idp); ?>">

                        <div class="row g-3">
                            <!-- Left Column: Security Settings -->
                            <div class="col-md-6 border-end border-light pe-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Pengaturan Keamanan</label>

                                <div class="mb-2">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Username Login</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-at text-primary-orange"></i></span>
                                        <input type="text" name="username" required value="<?php echo esc($username); ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Username unik" tabindex="1" autofocus>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Password</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-key text-primary-orange"></i></span>
                                                <input type="password" name="password" required value="<?php echo esc($password); ?>"
                                                    class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="••••••••" tabindex="2">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Confirm Password</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-shield-check text-primary-orange"></i></span>
                                                <input type="password" name="password2" required value="<?php echo esc($password); ?>"
                                                    class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="••••••••" tabindex="3">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($mode == 'edt'): ?>
                                    <div class="alert bg-gray-50 border-0 py-2 px-3 border-radius-lg mb-3">
                                        <span class="text-xxs text-secondary"><i class="bi bi-info-circle-fill text-warning me-1"></i>Biarkan <strong>'-'</strong> jika tidak ingin mengubah password lama.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-0">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Email Korespondensi</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-envelope text-primary-orange"></i></span>
                                        <input type="email" name="email" required value="<?php echo esc($email); ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="admin@jatengprov.go.id" tabindex="4">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Identity & Access -->
                            <div class="col-md-6 ps-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-3 ls-1">Identitas & Otoritas</label>

                                <div class="mb-3">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Nama Lengkap</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-person text-primary-orange"></i></span>
                                        <input type="text" name="nama" required value="<?php echo esc($nama); ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama lengkap beserta gelar" tabindex="5">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">N I P</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-upc text-primary-orange"></i></span>
                                        <input type="text" name="nip" required value="<?php echo esc($nip); ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="19XXXXXXXXXXXXXX" tabindex="6">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-7">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Asal Instansi</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none bg-white">
                                                <select name="unitkerja" id="unitkerja" class="form-select border-0 py-2 text-sm" tabindex="8" required>
                                                    <?php
                                                    $db = \Config\Database::connect();
                                                    $unitkerja_data = $db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind")->getResultArray();

                                                    if ($mode == "edt" || $mode == "act_edt") {
                                                        echo "<option selected value='" . esc($idunitkerja_terpilih, 'attr') . "'>" . esc($unitkerja_terpilih) . "</option>";
                                                        foreach ($unitkerja_data as $p) {
                                                            if ($p['id_unitkerja'] != $idunitkerja_terpilih) {
                                                                echo "<option value='" . esc($p['id_unitkerja'], 'attr') . "'>" . esc($p['unitkerja_ind']) . "</option>";
                                                            }
                                                        }
                                                    } else {
                                                        echo "<option selected value=''>- Pilih Instansi -</option>";
                                                        foreach ($unitkerja_data as $p) {
                                                            echo "<option value='" . esc($p['id_unitkerja'], 'attr') . "'>" . esc($p['unitkerja_ind']) . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
```

#### Langkah 25.2 — Gunakan Parameter Binding `?` & `esc()` di `app/Views/admin/f_unitkerja.php`
- **File**: [`app/Views/admin/f_unitkerja.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_unitkerja.php#L14-L135)
- **Instruksi**: Gunakan `edit_file` pada `app/Views/admin/f_unitkerja.php`:
- **TARGET 1 (Baris 14–17)**:
```php
	$db = \Config\Database::connect();
	$querynama_userwali = $db->query("SELECT nama from t_admin where username='$user_wali' LIMIT 1")->getRow();
	$nama_userwali = $querynama_userwali->nama ?? $user_wali;
```
- **REPLACEMENT 1**:
```php
	$db = \Config\Database::connect();
	$querynama_userwali = $db->query("SELECT nama FROM t_admin WHERE username = ? LIMIT 1", [$user_wali])->getRow();
	$nama_userwali = $querynama_userwali->nama ?? $user_wali;
```

- **TARGET 2 (Baris 46–60)**:
```php
                    <form action="<?php echo base_URL(); ?>index.php/admin/master_opd/<?php echo $act; ?>" method="post" accept-charset="utf-8">
                        <input type="hidden" name="idp" value="<?php echo $idp; ?>">
                        
                        <?php echo session()->getFlashdata("k"); ?>

                        <div class="row g-4">
                            <!-- Kode OPD -->
                            <div class="col-md-5">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Kode OPD / Unit Kerja</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-gray-100 border-0"><i class="bi bi-hash text-muted"></i></span>
                                    <input type="text" name="id_unitkerja" required value="<?php echo $id_unitkerja; ?>" 
```
- **REPLACEMENT 2**:
```php
                    <form action="<?php echo base_URL(); ?>index.php/admin/master_opd/<?php echo esc($act); ?>" method="post" accept-charset="utf-8">
                        <input type="hidden" name="idp" value="<?php echo esc($idp); ?>">

                        <div class="row g-4">
                            <!-- Kode OPD -->
                            <div class="col-md-5">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Kode OPD / Unit Kerja</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-gray-100 border-0"><i class="bi bi-hash text-muted"></i></span>
                                    <input type="text" name="id_unitkerja" required value="<?php echo esc($id_unitkerja); ?>" 
```

- **TARGET 3 (Baris 96–135)**:
```php
                            <!-- Nama Indonesia -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Nama Unit Kerja (Bahasa Indonesia)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100">
                                    <input type="text" name="unitkerja_ind" required value="<?php echo $unitkerja_ind; ?>" 
                                           class="form-control border-0 py-2 ps-3" placeholder="Masukkan nama organisasi..." tabindex="2">
                                </div>
                            </div>

                            <!-- Nama English -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Organization Name (English Translation)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100">
                                    <input type="text" name="unitkerja_en" required value="<?php echo $unitkerja_en; ?>" 
                                           class="form-control border-0 py-2 ps-3 text-italic text-secondary" placeholder="Organization name in English..." tabindex="3">
                                </div>
                            </div>

                            <!-- LO Row -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Tim Pembina / Liaison Officer (Wali Data)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100 bg-white">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-people-fill text-primary-orange"></i></span>
                                    <select name="user_wali" id="user_wali" class="form-select border-0 py-2" tabindex="5">
                                        <?php if ($mode == "edt" || $mode == "act_edt"): ?>
                                            <option selected value="<?php echo $user_wali; ?>"><?php echo $nama_userwali; ?></option>
                                        <?php else: ?>
                                            <option selected value="Kosong">- Pilih Tim Pembina / Walidata -</option>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $db = \Config\Database::connect();
                                        $user_wali_data = $db->query("select username,nama from t_admin where id_unitkerja='bps' order by nama ASC")->getResultArray();
                                        foreach($user_wali_data as $p){
                                            if ($p['username'] != $user_wali) {
                                                echo "<option value='".$p['username']."'>".$p['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
```
- **REPLACEMENT 3**:
```php
                            <!-- Nama Indonesia -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Nama Unit Kerja (Bahasa Indonesia)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100">
                                    <input type="text" name="unitkerja_ind" required value="<?php echo esc($unitkerja_ind); ?>" 
                                           class="form-control border-0 py-2 ps-3" placeholder="Masukkan nama organisasi..." tabindex="2">
                                </div>
                            </div>

                            <!-- Nama English -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Organization Name (English Translation)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100">
                                    <input type="text" name="unitkerja_en" required value="<?php echo esc($unitkerja_en); ?>" 
                                           class="form-control border-0 py-2 ps-3 text-italic text-secondary" placeholder="Organization name in English..." tabindex="3">
                                </div>
                            </div>

                            <!-- LO Row -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Tim Pembina / Liaison Officer (Wali Data)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100 bg-white">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-people-fill text-primary-orange"></i></span>
                                    <select name="user_wali" id="user_wali" class="form-select border-0 py-2" tabindex="5">
                                        <?php if ($mode == "edt" || $mode == "act_edt"): ?>
                                            <option selected value="<?php echo esc($user_wali, 'attr'); ?>"><?php echo esc($nama_userwali); ?></option>
                                        <?php else: ?>
                                            <option selected value="Kosong">- Pilih Tim Pembina / Walidata -</option>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $db = \Config\Database::connect();
                                        $user_wali_data = $db->query("SELECT username, nama FROM t_admin WHERE id_unitkerja = 'bps' ORDER BY nama ASC")->getResultArray();
                                        foreach($user_wali_data as $p){
                                            if ($p['username'] != $user_wali) {
                                                echo "<option value='" . esc($p['username'], 'attr') . "'>" . esc($p['nama']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
```

---

### [x] TASK-26: Cegah Penghapusan Data Historis (`ON DELETE CASCADE`) Saat Edit `jenis_sumber` di `Admin::master_tabel()` (Memperbaiki P0-B)

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1676-L1705) dan baris 1804–1825
- **Penjelasan**: Karena `t_nilai_dinamis` memiliki Foreign Key `ON DELETE CASCADE` ke `m_indikator_tabel(id)`, memanggil `$this->db->table('m_indikator_tabel')->where('id_tabel', $m_id)->delete()` saat `jenis_sumber === 'api_kominfo'` akan langsung menghapus ribuan sel data historis di `t_nilai_dinamis`. Kita hanya boleh menghapus `m_indikator_tabel` jika tabel tersebut belum memiliki baris nilai di `t_nilai_dinamis`.
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET 1 (Baris 1676–1682 — di dalam `act_add`)**:
```php
			// Sinkronisasi m_konfigurasi_api jika api_kominfo
			if ($jenis_sumber === 'api_kominfo') {
				// Bersihkan konfigurasi tabel dinamis jika ada
				$this->db->table('m_indikator_tabel')->where('id_tabel', $m_id)->delete();

				$cekApi = $this->db->table('m_konfigurasi_api')->where('id_tabel', $m_id)->get()->getRow();
```
- **REPLACEMENT 1**:
```php
			// Sinkronisasi m_konfigurasi_api jika api_kominfo
			if ($jenis_sumber === 'api_kominfo') {
				// Hanya bersihkan kerangka indikator dinamis jika belum memiliki data nilai historis di t_nilai_dinamis
				$cekInd = $this->db->table('m_indikator_tabel')->select('id')->where('id_tabel', $m_id)->get()->getRow();
				if ($cekInd) {
					$hasNilai = $this->db->table('t_nilai_dinamis')->where('id_indikator', (int)$cekInd->id)->countAllResults();
					if ($hasNilai === 0) {
						$this->db->table('m_indikator_tabel')->where('id', (int)$cekInd->id)->delete();
					}
				}

				$cekApi = $this->db->table('m_konfigurasi_api')->where('id_tabel', $m_id)->get()->getRow();
```

- **TARGET 2 (Baris 1804–1811 — di dalam `act_edt`)**:
```php
			// Sinkronisasi m_konfigurasi_api jika api_kominfo
			if ($jenis_sumber === 'api_kominfo') {
				// Bersihkan konfigurasi tabel dinamis jika ada
				$this->db->table('m_indikator_tabel')->where('id_tabel', $m_id)->delete();

				$cekApi = $this->db->table('m_konfigurasi_api')->where('id_tabel', $m_id)->get()->getRow();
```
- **REPLACEMENT 2**:
```php
			// Sinkronisasi m_konfigurasi_api jika api_kominfo
			if ($jenis_sumber === 'api_kominfo') {
				// Hanya bersihkan kerangka indikator dinamis jika belum memiliki data nilai historis di t_nilai_dinamis
				$cekInd = $this->db->table('m_indikator_tabel')->select('id')->where('id_tabel', $m_id)->get()->getRow();
				if ($cekInd) {
					$hasNilai = $this->db->table('t_nilai_dinamis')->where('id_indikator', (int)$cekInd->id)->countAllResults();
					if ($hasNilai === 0) {
						$this->db->table('m_indikator_tabel')->where('id', (int)$cekInd->id)->delete();
					}
				}

				$cekApi = $this->db->table('m_konfigurasi_api')->where('id_tabel', $m_id)->get()->getRow();
```

---

### [ ] TASK-27: Upgrade MD5 ke Bcrypt (Dual-Mode), Regenerasi Session ID, & Perbaiki Validasi `manage_admin()` (Memperbaiki P0-C & P1-E)

#### Langkah 27.1 — Perbaiki `Admin::manage_admin()` (Bcrypt, Cegah Hapus Diri Sendiri, Validasi Username >= 4, Cek Duplikat Saat Edit, Hapus Double Flashdata)
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L251-L300)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
		//ambil variabel Postingan
		$idp					= $this->input->post('idp');
		$username				= $this->input->post('username');
		$password				= md5($this->input->post('password'));
		$nama					= $this->input->post('nama');
		$nip					= $this->input->post('nip');
		$level					= $this->input->post('level');
		$unitkerja				= $this->input->post('unitkerja');
		$email					= $this->input->post('email');
		$cari					= $this->input->post('q');


		if ($mau_ke == "del") {
			$this->db->query("DELETE FROM t_admin WHERE id = ?", [$idu]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/manage_admin');
		} else if ($mau_ke == "cari") {
			$a['data']		= $this->db->query("SELECT * FROM t_admin WHERE nama LIKE ? ORDER BY id DESC", ['%' . $cari . '%'])->getResult();
			$a['page']		= "l_manage_admin";
		} else if ($mau_ke == "add") {
			$a['page']		= "f_manage_admin";
		} else if ($mau_ke == "edt") {
			$a['datpil']	= $this->db->query("SELECT * FROM t_admin WHERE id = ?", [$idu])->getRow();
			$a['page']		= "f_manage_admin";
		} else if ($mau_ke == "act_add") {
			$cek_user_exist = $this->db->query("SELECT username FROM t_admin WHERE username = ?", [$username])->getNumRows();

			if (strlen($username) < 3) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username minimal 4 huruf</div>");
				return redirect()->to('admin/manage_admin');
			} else if ($cek_user_exist > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username telah dipakai. Ganti yang lain..!</div>");
				return redirect()->to('admin/manage_admin');
			} else {
				$this->db->query("INSERT INTO t_admin (username, password, nama, nip, level, id_unitkerja, email) VALUES (?, ?, ?, ?, ?, ?, ?)", [$username, $password, $nama, $nip, $level, $unitkerja, $email]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
			return redirect()->to('admin/manage_admin');
		} else if ($mau_ke == "act_edt") {
			if ($password == md5("-")) {
				$this->db->query("UPDATE t_admin SET username = ?, nama = ?, nip = ?, level = ?, email=?, id_unitkerja=? WHERE id = ?", [$username, $nama, $nip, $level, $email, $unitkerja, $idp]);
			} else {
				$this->db->query("UPDATE t_admin SET username = ?, password = ?, nama = ?, nip = ?, level = ?, email=?, id_unitkerja=? WHERE id = ?", [$username, $password, $nama, $nip, $level, $email, $unitkerja, $idp]);
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated </div>");
			return redirect()->to('admin/manage_admin');
```
- **REPLACEMENT**:
```php
		//ambil variabel Postingan
		$idp					= $this->input->post('idp');
		$username				= trim((string)$this->input->post('username'));
		$raw_password			= (string)$this->input->post('password');
		$nama					= trim((string)$this->input->post('nama'));
		$nip					= trim((string)$this->input->post('nip'));
		$level					= $this->input->post('level');
		$unitkerja				= $this->input->post('unitkerja');
		$email					= trim((string)$this->input->post('email'));
		$cari					= $this->input->post('q');


		if ($mau_ke == "del") {
			if ((int)$idu === (int)$this->session->get('admin_id')) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Tidak dapat menghapus akun yang sedang aktif digunakan login!</div>");
				return redirect()->to('admin/manage_admin');
			}
			$this->db->query("DELETE FROM t_admin WHERE id = ?", [(int)$idu]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/manage_admin');
		} else if ($mau_ke == "cari") {
			$a['data']		= $this->db->query("SELECT * FROM t_admin WHERE nama LIKE ? ORDER BY id DESC", ['%' . $cari . '%'])->getResult();
			$a['page']		= "l_manage_admin";
		} else if ($mau_ke == "add") {
			$a['page']		= "f_manage_admin";
		} else if ($mau_ke == "edt") {
			$a['datpil']	= $this->db->query("SELECT * FROM t_admin WHERE id = ?", [(int)$idu])->getRow();
			$a['page']		= "f_manage_admin";
		} else if ($mau_ke == "act_add") {
			$cek_user_exist = $this->db->query("SELECT username FROM t_admin WHERE username = ?", [$username])->getNumRows();

			if (strlen($username) < 4) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username minimal 4 huruf</div>");
				return redirect()->to('admin/manage_admin');
			} else if ($cek_user_exist > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username telah dipakai. Ganti yang lain..!</div>");
				return redirect()->to('admin/manage_admin');
			} else {
				$hashed_pw = password_hash($raw_password, PASSWORD_BCRYPT);
				$this->db->query("INSERT INTO t_admin (username, password, nama, nip, level, id_unitkerja, email) VALUES (?, ?, ?, ?, ?, ?, ?)", [$username, $hashed_pw, $nama, $nip, $level, $unitkerja, $email]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
				return redirect()->to('admin/manage_admin');
			}
		} else if ($mau_ke == "act_edt") {
			if (strlen($username) < 4) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username minimal 4 huruf</div>");
				return redirect()->to('admin/manage_admin');
			}
			$dup = $this->db->query("SELECT id FROM t_admin WHERE username = ? AND id != ? LIMIT 1", [$username, (int)$idp])->getRow();
			if ($dup) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username telah dipakai oleh akun lain!</div>");
				return redirect()->to('admin/manage_admin');
			}
			if ($raw_password === "-" || $raw_password === "") {
				$this->db->query("UPDATE t_admin SET username = ?, nama = ?, nip = ?, level = ?, email=?, id_unitkerja=? WHERE id = ?", [$username, $nama, $nip, $level, $email, $unitkerja, (int)$idp]);
			} else {
				$hashed_pw = password_hash($raw_password, PASSWORD_BCRYPT);
				$this->db->query("UPDATE t_admin SET username = ?, password = ?, nama = ?, nip = ?, level = ?, email=?, id_unitkerja=? WHERE id = ?", [$username, $hashed_pw, $nama, $nip, $level, $email, $unitkerja, (int)$idp]);
			}

			// Jika mengedit akun sendiri, segarkan cache role RBAC di session
			if ((int)$idp === (int)$this->session->get('admin_id')) {
				$this->session->set([
					'admin_user'      => $username,
					'admin_nama'      => $nama,
					'admin_level'     => $level,
					'admin_unitkerja' => $unitkerja,
				]);
				$this->session->remove(['admin_resolved_role', 'admin_jenis_instansi']);
				\App\Libraries\RbacService::resolveRole($this->session);
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated </div>");
			return redirect()->to('admin/manage_admin');
```

#### Langkah 27.2 — Perbaiki `Admin::passwod()` dan `Admin::do_login()` (Dual-Mode Bcrypt + Fallback MD5 + Regenerate Session)
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L409-L475)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
		//var post
		$p1				= md5($this->input->post('p1'));
		$p2				= md5($this->input->post('p2'));
		$p3				= md5($this->input->post('p3'));

		if ($ke == "simpan") {
			$cek_password_lama	= $this->db->query("SELECT password FROM t_admin WHERE id = ?", [$id_user])->getRow();
			//echo 

			if ($cek_password_lama->password != $p1) {
				$this->session->setFlashdata('k_passwod', '<div id="alert" class="alert alert-error">Password Lama tidak sama</div>');
				return redirect()->to('admin/passwod');
			} else if ($p2 != $p3) {
				$this->session->setFlashdata('k_passwod', '<div id="alert" class="alert alert-error">Password Baru 1 dan 2 tidak cocok</div>');
				return redirect()->to('admin/passwod');
			} else {
				$this->db->query("UPDATE t_admin SET password = ? WHERE id = ?", [$p3, $id_user]);
				$this->session->setFlashdata('k_passwod', '<div id="alert" class="alert alert-success">Password berhasil diperbaharui</div>');
				return redirect()->to('admin/passwod');
			}
		} else {
			$a['page']	= "f_passwod";
		}

		return view('admin/index', $a);
	}

	//login
	public function login()
	{
		return view('admin/login');
	}

	public function do_login()
	{
		$u 		= $this->input->post('u');
		$ta 	= $this->input->post('ta');
		$p 		= md5($this->input->post('p'));

		$q_cek	= $this->db->query("SELECT * FROM t_admin WHERE username = ? AND password = ?", [$u, $p]);
		$j_cek	= $q_cek->getNumRows();
		$d_cek	= $q_cek->getRow();

		//echo $this->db->getLastQuery();

		if ($j_cek == 1) {
			$data = array(
				'admin_id' => $d_cek->id,
				'admin_user' => $d_cek->username,
				'admin_nama' => $d_cek->nama,
				'admin_ta' => $ta,
				'admin_level' => $d_cek->level,
				'admin_nip' => $d_cek->nip,
				'admin_unitkerja' => $d_cek->id_unitkerja,
				'admin_eselon' => $d_cek->id_eselon,
				'admin_email' => $d_cek->email,
				'admin_valid' => true
			);
			$this->session->set($data);
			// Segera resolve dan cache role RBAC
			\App\Libraries\RbacService::resolveRole($this->session);
			return redirect()->to('index.php/admin');
		} else {
			$this->session->setFlashdata("k", "<div id=\"alert\" class=\"alert alert-error\">username or password is not valid</div>");
			return redirect()->to('admin/login');
		}
	}
```
- **REPLACEMENT**:
```php
		//var post
		$raw_p1			= (string)$this->input->post('p1');
		$raw_p2			= (string)$this->input->post('p2');
		$raw_p3			= (string)$this->input->post('p3');

		if ($ke == "simpan") {
			$cek_password_lama	= $this->db->query("SELECT password FROM t_admin WHERE id = ?", [$id_user])->getRow();
			$stored_hash = (string)($cek_password_lama->password ?? '');
			$is_old_valid = password_verify($raw_p1, $stored_hash) || ($stored_hash !== '' && hash_equals($stored_hash, md5($raw_p1)));

			if (!$is_old_valid) {
				$this->session->setFlashdata('k_passwod', '<div id="alert" class="alert alert-error">Password Lama tidak sama</div>');
				return redirect()->to('admin/passwod');
			} else if ($raw_p2 !== $raw_p3) {
				$this->session->setFlashdata('k_passwod', '<div id="alert" class="alert alert-error">Password Baru 1 dan 2 tidak cocok</div>');
				return redirect()->to('admin/passwod');
			} else {
				$new_hash = password_hash($raw_p3, PASSWORD_BCRYPT);
				$this->db->query("UPDATE t_admin SET password = ? WHERE id = ?", [$new_hash, $id_user]);
				$this->session->setFlashdata('k_passwod', '<div id="alert" class="alert alert-success">Password berhasil diperbaharui</div>');
				return redirect()->to('admin/passwod');
			}
		} else {
			$a['page']	= "f_passwod";
		}

		return view('admin/index', $a);
	}

	//login
	public function login()
	{
		return view('admin/login');
	}

	public function do_login()
	{
		$u 		= trim((string)$this->input->post('u'));
		$ta 	= $this->input->post('ta');
		$raw_p 	= (string)$this->input->post('p');

		$d_cek	= $this->db->query("SELECT * FROM t_admin WHERE username = ? LIMIT 1", [$u])->getRow();
		$login_ok = false;

		if ($d_cek) {
			$stored_hash = (string)$d_cek->password;
			if (password_verify($raw_p, $stored_hash)) {
				$login_ok = true;
			} elseif (strlen($stored_hash) === 32 && hash_equals($stored_hash, md5($raw_p))) {
				$login_ok = true;
				// Auto-upgrade hash MD5 ke Bcrypt
				$new_hash = password_hash($raw_p, PASSWORD_BCRYPT);
				$this->db->query("UPDATE t_admin SET password = ? WHERE id = ?", [$new_hash, $d_cek->id]);
			}
		}

		if ($login_ok && $d_cek) {
			$this->session->regenerate(true);
			$data = array(
				'admin_id' => $d_cek->id,
				'admin_user' => $d_cek->username,
				'admin_nama' => $d_cek->nama,
				'admin_ta' => $ta,
				'admin_level' => $d_cek->level,
				'admin_nip' => $d_cek->nip,
				'admin_unitkerja' => $d_cek->id_unitkerja,
				'admin_eselon' => $d_cek->id_eselon,
				'admin_email' => $d_cek->email,
				'admin_valid' => true
			);
			$this->session->set($data);
			// Segera resolve dan cache role RBAC
			\App\Libraries\RbacService::resolveRole($this->session);
			return redirect()->to('index.php/admin');
		} else {
			$this->session->setFlashdata("k", "<div id=\"alert\" class=\"alert alert-error\">username or password is not valid</div>");
			return redirect()->to('admin/login');
		}
	}
```

#### Langkah 27.3 — Ganti `md5('123456')` Menjadi `password_hash('123456', PASSWORD_BCRYPT)` di `Admin::master_tim()`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2777-L2786)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
			$this->db->query("INSERT INTO t_admin (username, password, nama, nip, level, id_unitkerja, email) VALUES (?, ?, ?, ?, ?, ?, ?)", [
				$kode_tim,
				md5('123456'),
				$nama_tim,
				'-',
				'lo',
				'bps',
				'-'
			]);
```
- **REPLACEMENT**:
```php
			$this->db->query("INSERT INTO t_admin (username, password, nama, nip, level, id_unitkerja, email) VALUES (?, ?, ?, ?, ?, ?, ?)", [
				$kode_tim,
				password_hash('123456', PASSWORD_BCRYPT),
				$nama_tim,
				'-',
				'lo',
				'bps',
				'-'
			]);
```

---

### [x] TASK-28: Hapus Hardcoded Fallback Token Produksi `SATUDATA_TOKEN` & Bersihkan Query Mubazir di `Admin::matching()` (Memperbaiki P0-D & P3-B)

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2933-L2962)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
	public function matching()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('matching')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Menu Sinkronisasi/Matching hanya untuk Super Admin.</div>');
			return redirect()->to('admin');
		}

		$jenis_rekap = $this->input->post('jenis_rekap');
		$ta = (int)($this->session->get('admin_ta') ?: date('Y'));
		if ($jenis_rekap == '0') {
			$data_byjk	= $this->db->query("SELECT mt.id_unitkerja, u.unitkerja_ind, count(mt.judul_ind) as jumlah_tabel, sum(case when t.is_confirm = '1' then 1 else 0 end ) as terentri FROM m_list_tabel mt LEFT JOIN t_status_tahunan t ON t.id_tabel = mt.id AND t.tahun = ? LEFT JOIN m_unitkerja u ON mt.id_unitkerja=u.id_unitkerja WHERE mt.is_active = 1 GROUP BY mt.id_unitkerja ORDER BY u.id_unitkerja", [$ta])->getResult();
			$a['page']		= "view_report";
		}

		$a['page']	= "matching";
		return view('admin/index', $a);
	}


	private function callApi2($url)
	{
		$token = env('SATUDATA_TOKEN', 'sdj_UYf5h0TPgUW3E0kcZisn33xrTe5PMRqLuXq6CWbAfb69tEA7dILT0TLhpFtjQiWHqrd1pn9SGvE4NYlN');
```
- **REPLACEMENT**:
```php
	public function matching()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('matching')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Menu Sinkronisasi/Matching hanya untuk Super Admin.</div>');
			return redirect()->to('admin');
		}

		$a['page'] = "matching";
		return view('admin/index', $a);
	}


	private function callApi2($url)
	{
		$token = (string)env('SATUDATA_TOKEN', '');
		if ($token === '') {
			return ['error' => 'MISSING_TOKEN: SATUDATA_TOKEN belum dikonfigurasi di .env'];
		}
```

---

### [x] TASK-29: Tambahkan Guard Otorisasi RBAC pada Endpoint Publik di `Admin.php` & `Mail.php` (Memperbaiki P1-A)

#### Langkah 29.1 — Proteksi `check_new_table_conflict()` dan `ajax_get_last_table_number()`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1120-L1175)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
	public function check_new_table_conflict() {
		// Ambil dan bersihkan nomor tabel dari spasi
		$no_tabel = trim(preg_replace('/\s+/', '', (string)$this->request->getPost("no_tabel")));
```
- **REPLACEMENT**:
```php
	public function check_new_table_conflict() {
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(["conflict" => false, "message" => "Unauthorized"]);
		}
		if (!can('master_tabel')) {
			return $this->response->setStatusCode(403)->setJSON(["conflict" => false, "message" => "Forbidden"]);
		}
		// Ambil dan bersihkan nomor tabel dari spasi
		$no_tabel = trim(preg_replace('/\s+/', '', (string)$this->request->getPost("no_tabel")));
```

- **TARGET 2**:
```php
	public function ajax_get_last_table_number()
	{
		// Ambil variabel GET: prefix dan tahun
		$prefix = trim((string)$this->request->getGet('prefix'));
```
- **REPLACEMENT 2**:
```php
	public function ajax_get_last_table_number()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Unauthorized']);
		}
		if (!can('master_tabel')) {
			return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Forbidden']);
		}
		// Ambil variabel GET: prefix dan tahun
		$prefix = trim((string)$this->request->getGet('prefix'));
```

#### Langkah 29.2 — Proteksi `ambil_data()`, `ambil_databatch()`, dan `ambil_tahun_terakhir_dari_match()` dengan `can('matching')`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3002-L3140)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET 1 (`ambil_data` & `ambil_databatch`)**:
```php
	public function ambil_data()
	{
		// biar tidak timeout
		ini_set('max_execution_time', 0); // 0 = unlimited
		set_time_limit(0);

		header('Content-Type: application/json');
```
- **REPLACEMENT 1**:
```php
	public function ambil_data()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Unauthorized']);
		}
		if (!can('matching')) {
			return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
		}

		// biar tidak timeout
		ini_set('max_execution_time', 0); // 0 = unlimited
		set_time_limit(0);

		header('Content-Type: application/json');
```

- **TARGET 2 (`ambil_databatch` & `ambil_tahun_terakhir_dari_match`)**:
```php
	public function ambil_databatch()
	{
		// biar tidak timeout
		ini_set('max_execution_time', 0); // 0 = unlimited
		set_time_limit(0);

		header('Content-Type: application/json');
```
- **REPLACEMENT 2**:
```php
	public function ambil_databatch()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Unauthorized', 'has_next' => false]);
		}
		if (!can('matching')) {
			return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak', 'has_next' => false]);
		}

		// biar tidak timeout
		ini_set('max_execution_time', 0); // 0 = unlimited
		set_time_limit(0);

		header('Content-Type: application/json');
```

- **TARGET 3 (`ambil_tahun_terakhir_dari_match`)**:
```php
	public function ambil_tahun_terakhir_dari_match()
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);
```
- **REPLACEMENT 3**:
```php
	public function ambil_tahun_terakhir_dari_match()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Unauthorized']);
		}
		if (!can('matching')) {
			return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
		}

		ini_set('max_execution_time', 0);
		set_time_limit(0);
```

#### Langkah 29.3 — Proteksi `download_xlsx_template()` dengan `can('master_tabel')`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3379-L3385)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
	public function download_xlsx_template()
	{
		$filename = "template_bulk_portal.csv";
```
- **REPLACEMENT**:
```php
	public function download_xlsx_template()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}
		if (!can('master_tabel')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">Akses ditolak!</div>');
			return redirect()->to('admin');
		}

		$filename = "template_bulk_portal.csv";
```

#### Langkah 29.4 — Proteksi `Mail.php` Hanya untuk Super Admin (`can('manage_admin')`)
- **File**: [`app/Controllers/Mail.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Mail.php#L22-L40)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Mail.php`:
- **TARGET**:
```php
    public function index()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
        $a['data'] = $this->db->query("SELECT * FROM m_unitkerja")->getResult();
        $a['message'] = '';
        $a['page'] = "sent_mail";
        return view('admin/index', $a);
    }

    public function send()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
```
- **REPLACEMENT**:
```php
    public function index()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
        if (!can('manage_admin')) {
            return redirect()->to('admin');
        }
        return redirect()->to('admin');
    }

    public function send()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
        if (!can('manage_admin')) {
            return redirect()->to('admin');
        }
```

---

### [x] TASK-30: Tutup Celah IDOR Lintas OPD/Tim di `Admin::dda()`, Upload Excel, & `f_kondef.php` (Memperbaiki P1-B & P3-A)

#### Langkah 30.1 — Gunakan `$this->canManageTable()` dan Hapus Dead Code `t_oi` di `Admin::dda()`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L599-L720)
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
		if ($mau_ke == "del") {
			$id_delete = $this->input->get('delete_id');
		?>
			<script>
				//window.alert('<?php echo $id_delete; ?>');
			</script>
		<?php
			$a['datpil']	= $this->db->query("select * from t_oi where id=?", [$id_delete])->getRow();
			$a['page']		= "f_del";
		} else if ($mau_ke == "act_del") {
			$id_oi = $this->input->post('id');
			$this->db->query("DELETE FROM t_oi WHERE id = ?", [$id_oi]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/oi/');
		} else if ($mau_ke == "in") {
			$id_in = $this->input->get('in_id');;
		?>
			<script>
				//window.alert('<?php echo $id_in; ?>');
			</script>
		<?php
			$a['datpil']	= $this->db->query("select * from t_oi where id=?", [$id_in])->getRow();
			$a['page']		= "f_in";
		} else if ($mau_ke == "act_in") {
			$id_oi = $this->input->post('id');
			date_default_timezone_set("Asia/Jakarta");
			$tanggal_in = date("H:i");
			$this->db->query("UPDATE t_oi SET jam_masuk=? where id=?", [$tanggal_in, $id_oi]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/oi/');
		} else if ($mau_ke == "konfirmasi") {
			$id_konfirmasi = (int)$this->input->get('konfirmasi_id');
			$a['datpil']	= $this->db->query("
				SELECT m_t.id, m_t.id as id_tabel, ? as tahun, m_t.no_tabel,
					   COALESCE(ka.link_api, '') as link_tabel,
					   COALESCE(t.is_periksa, 0) as is_periksa,
					   t.catatan_periksa,
					   COALESCE(t.is_confirm, '0') as is_confirm,
					   COALESCE(t.catatan, '-') as catatan,
					   m_t.judul_ind, m_t.judul_en, m_t.id_unitkerja, m.unitkerja_ind, m_t.kondef, m_t.sumber_data
				FROM m_list_tabel m_t
				LEFT JOIN t_status_tahunan t ON t.id_tabel = m_t.id AND t.tahun = ?
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m_t.id
				LEFT JOIN m_unitkerja m ON m_t.id_unitkerja = m.id_unitkerja
				WHERE m_t.id = ?
			", [(int)$ta, (int)$ta, $id_konfirmasi])->getRow();
			$a['page']		= "f_konfirmasi";
		} else if ($mau_ke == "act_konfirmasi") {
			if (!can('input_dda_verif')) {
				return redirect()->back()->with('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk memverifikasi/meng-ACC data.</div>');
			}
			$id_data = (int)$this->input->post('id');
			$catatan = $this->input->post('catatan');
			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$mTabelDinamis->upsertStatusTahunan($id_data, (int)$ta, [
				'is_confirm' => '1',
				'catatan'    => $catatan,
			]);
			return redirect()->back()->with('k', '<div class="alert alert-success" id="alert">Data has been updated</div>');
		} else if ($mau_ke == "periksa") {
			$id_konfirmasi = (int)$this->input->get('konfirmasi_id');
			$a['datpil']   = $this->db->query("
				SELECT m_t.id, m_t.id as id_tabel, ? as tahun, m_t.no_tabel,
					   COALESCE(ka.link_api, '') as link_tabel,
					   COALESCE(t.is_periksa, 0) as is_periksa,
					   t.catatan_periksa,
					   COALESCE(t.is_confirm, '0') as is_confirm,
					   COALESCE(t.catatan, '-') as catatan,
					   m_t.judul_ind, m_t.judul_en, m_t.id_unitkerja, m.unitkerja_ind, m_t.kondef, m_t.sumber_data
				FROM m_list_tabel m_t
				LEFT JOIN t_status_tahunan t ON t.id_tabel = m_t.id AND t.tahun = ?
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m_t.id
				LEFT JOIN m_unitkerja m ON m_t.id_unitkerja = m.id_unitkerja
				WHERE m_t.id = ?
			", [(int)$ta, (int)$ta, $id_konfirmasi])->getRow();
			return view('admin/f_periksa', $a);
		} else if ($mau_ke == "act_periksa") {
			if (!can('input_dda_verif')) {
				return redirect()->back()->with('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk memverifikasi/meng-ACC data.</div>');
			}
			$id_data = (int)($this->request->getPost('id') ?: $this->input->post('id'));
			$catatan_verif = trim((string)($this->request->getPost('catatan') ?: ($this->request->getPost('catatan_periksa') ?: ($this->input->post('catatan') ?: $this->input->post('catatan_periksa')))));
			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$mTabelDinamis->upsertStatusTahunan($id_data, (int)$ta, [
				'is_confirm' => '1',
				'catatan'    => $catatan_verif,
			]);
			return redirect()->back()->with('k', '<div class="alert alert-success" id="alert">Data berhasil diverifikasi</div>');
		} else if ($mau_ke == "cari") {

			$a['data']		= $this->db->query("SELECT * from t_oi where keterangan_uraian LIKE ?", ['%' . $cari . '%'])->getResult();
			$a['page']		= "l_oi";
		} else if ($mau_ke == "add") {

			$a['page']		= "f_oi";
		} else if ($mau_ke == "edt") {

			$a['datpil']	= $this->db->query("SELECT * from t_oi WHERE id = ?", [$idu])->getRow();
			$a['page']		= "f_oi";
		} else if ($mau_ke == "act_add") {
			$hariini = date('Y-m-d');
			date_default_timezone_set("Asia/Jakarta");
			$waktusekarang = date("H:i");

			$this->db->query("INSERT INTO t_oi VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$nippegawai, $hariini, $waktusekarang, '', 'T', $kepentingan_kel, $kepentingan_uraian, '', '', '']);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
			return redirect()->to('admin/dda');
		} else if ($mau_ke == "act_edt") {
			$this->db->query("UPDATE t_oi SET kepentingan_kel = ?, kepentingan_uraian = ? where id = ?", [$kepentingan_kel, $kepentingan_uraian, $idp]);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated</div>");
			return redirect()->to('admin/dda/');
		} else if ($mau_ke == "kondef") {
			$id_tabel = (int)$this->input->get('id');
			$a['datpil']	= $this->db->query("SELECT m.id, m.id as id_tabel, m.no_tabel, m.judul_ind, m.judul_en, m.kondef, m.id_unitkerja, m.sumber_data FROM m_list_tabel m WHERE m.id = ?", [$id_tabel])->getRow();
			$a['page']		= "f_kondef";
		} else if ($mau_ke == "kondef_update") {
			$idp					= (int)$this->input->post('idp');
			$kondef					= $this->input->post('kondef');
			$a['datpil']			= $this->db->query("UPDATE m_list_tabel SET kondef=? WHERE id = ?", [$kondef, $idp]);
			return redirect()->to('admin/dda/');
		} else if ($mau_ke == "view_tabel") {
```
- **REPLACEMENT**:
```php
		if ($mau_ke == "konfirmasi") {
			if (!can('input_dda_verif')) {
				return redirect()->back()->with('k', '<div class="alert alert-danger" id="alert">Akses ditolak.</div>');
			}
			$id_konfirmasi = (int)$this->input->get('konfirmasi_id');
			$datpil = $this->db->query("
				SELECT m_t.id, m_t.id as id_tabel, ? as tahun, m_t.no_tabel,
					   COALESCE(ka.link_api, '') as link_tabel,
					   COALESCE(t.is_periksa, 0) as is_periksa,
					   t.catatan_periksa,
					   COALESCE(t.is_confirm, '0') as is_confirm,
					   COALESCE(t.catatan, '-') as catatan,
					   m_t.judul_ind, m_t.judul_en, m_t.id_unitkerja, m.unitkerja_ind, m_t.kondef, m_t.sumber_data
				FROM m_list_tabel m_t
				LEFT JOIN t_status_tahunan t ON t.id_tabel = m_t.id AND t.tahun = ?
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m_t.id
				LEFT JOIN m_unitkerja m ON m_t.id_unitkerja = m.id_unitkerja
				WHERE m_t.id = ?
			", [(int)$ta, (int)$ta, $id_konfirmasi])->getRow();
			if (!$datpil || !$this->canManageTable($datpil)) {
				return redirect()->to('admin/dda')->with('k', '<div class="alert alert-danger" id="alert">Akses ditolak atau tabel tidak ditemukan.</div>');
			}
			$a['datpil'] = $datpil;
			$a['page']   = "f_konfirmasi";
		} else if ($mau_ke == "act_konfirmasi") {
			if (!can('input_dda_verif')) {
				return redirect()->back()->with('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk memverifikasi/meng-ACC data.</div>');
			}
			$id_data = (int)$this->input->post('id');
			$tabelRow = $this->db->table('m_list_tabel')->select('id, id_unitkerja')->where('id', $id_data)->get()->getRow();
			if (!$tabelRow || !$this->canManageTable($tabelRow)) {
				return redirect()->back()->with('k', '<div class="alert alert-danger" id="alert">Akses ditolak: Tabel ini berada di luar cakupan tim/unit kerja Anda.</div>');
			}
			$catatan = $this->input->post('catatan');
			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$mTabelDinamis->upsertStatusTahunan($id_data, (int)$ta, [
				'is_confirm' => '1',
				'catatan'    => $catatan,
			]);
			return redirect()->back()->with('k', '<div class="alert alert-success" id="alert">Data has been updated</div>');
		} else if ($mau_ke == "periksa") {
			$id_konfirmasi = (int)$this->input->get('konfirmasi_id');
			$datpil = $this->db->query("
				SELECT m_t.id, m_t.id as id_tabel, ? as tahun, m_t.no_tabel,
					   COALESCE(ka.link_api, '') as link_tabel,
					   COALESCE(t.is_periksa, 0) as is_periksa,
					   t.catatan_periksa,
					   COALESCE(t.is_confirm, '0') as is_confirm,
					   COALESCE(t.catatan, '-') as catatan,
					   m_t.judul_ind, m_t.judul_en, m_t.id_unitkerja, m.unitkerja_ind, m_t.kondef, m_t.sumber_data
				FROM m_list_tabel m_t
				LEFT JOIN t_status_tahunan t ON t.id_tabel = m_t.id AND t.tahun = ?
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m_t.id
				LEFT JOIN m_unitkerja m ON m_t.id_unitkerja = m.id_unitkerja
				WHERE m_t.id = ?
			", [(int)$ta, (int)$ta, $id_konfirmasi])->getRow();
			if (!$datpil || !$this->canManageTable($datpil)) {
				return $this->response->setStatusCode(403)->setBody('<div class="alert alert-danger m-3">Akses ditolak: Anda tidak memiliki izin untuk memeriksa tabel ini.</div>');
			}
			$a['datpil'] = $datpil;
			return view('admin/f_periksa', $a);
		} else if ($mau_ke == "act_periksa") {
			if (!can('input_dda_verif')) {
				return redirect()->back()->with('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk memverifikasi/meng-ACC data.</div>');
			}
			$id_data = (int)($this->request->getPost('id') ?: $this->input->post('id'));
			$tabelRow = $this->db->table('m_list_tabel')->select('id, id_unitkerja')->where('id', $id_data)->get()->getRow();
			if (!$tabelRow || !$this->canManageTable($tabelRow)) {
				return redirect()->back()->with('k', '<div class="alert alert-danger" id="alert">Akses ditolak: Tabel ini berada di luar cakupan tim/unit kerja Anda.</div>');
			}
			$catatan_verif = trim((string)($this->request->getPost('catatan') ?: ($this->request->getPost('catatan_periksa') ?: ($this->input->post('catatan') ?: $this->input->post('catatan_periksa')))));
			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$mTabelDinamis->upsertStatusTahunan($id_data, (int)$ta, [
				'is_confirm' => '1',
				'catatan'    => $catatan_verif,
			]);
			return redirect()->back()->with('k', '<div class="alert alert-success" id="alert">Data berhasil diverifikasi</div>');
		} else if ($mau_ke == "kondef") {
			$id_tabel = (int)$this->input->get('id');
			$datpil = $this->db->query("SELECT m.id, m.id as id_tabel, m.no_tabel, m.judul_ind, m.judul_en, m.kondef, m.id_unitkerja, m.sumber_data FROM m_list_tabel m WHERE m.id = ?", [$id_tabel])->getRow();
			if (!$datpil || !$this->canManageTable($datpil)) {
				return redirect()->to('admin/dda')->with('k', '<div class="alert alert-danger" id="alert">Akses ditolak atau tabel tidak ditemukan.</div>');
			}
			$a['datpil'] = $datpil;
			$a['page']   = "f_kondef";
		} else if ($mau_ke == "kondef_update") {
			if (!can('input_dda_input')) {
				return redirect()->to('admin/dda')->with('k', '<div class="alert alert-danger" id="alert">Akses ditolak: Role Anda hanya dapat melihat Konsep & Definisi (Read-Only).</div>');
			}
			$idp      = (int)$this->input->post('idp');
			$tabelRow = $this->db->table('m_list_tabel')->select('id, id_unitkerja')->where('id', $idp)->get()->getRow();
			if (!$tabelRow || !$this->canManageTable($tabelRow)) {
				return redirect()->to('admin/dda')->with('k', '<div class="alert alert-danger" id="alert">Akses ditolak: Anda tidak berwenang memperbarui Kondef tabel instansi lain.</div>');
			}
			$kondef = $this->input->post('kondef');
			$this->db->query("UPDATE m_list_tabel SET kondef = ? WHERE id = ?", [$kondef, $idp]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Konsep & Definisi berhasil diperbarui.</div>");
			return redirect()->to('admin/dda/');
		} else if ($mau_ke == "view_tabel") {
```

#### Langkah 30.2 — Seragamkan Proteksi IDOR Menggunakan `$this->canManageTable($tabel)` pada Upload Excel & Simpan Catatan Tabel
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L6855-L7460)
- **Instruksi**: Gunakan `edit_file` pada 4 tempat di `app/Controllers/Admin.php`:
- **TARGET 1 (`preview_upload_excel_dinamis`)**:
```php
		$user_level     = strtolower(trim((string)$this->session->get('admin_level')));
		$unitkerjalogin = $this->session->get('admin_unitkerja');

		if (!in_array($user_level, ['admin', 'super admin', 'superadmin']) && $unitkerjalogin !== 'bps') {
			if ($tabel->id_unitkerja != $unitkerjalogin) {
				return $this->response->setStatusCode(403)->setJSON([
					'status'  => 'error',
					'message' => 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.'
				]);
			}
		}
```
- **REPLACEMENT 1**:
```php
		if (!$this->canManageTable($tabel)) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'  => 'error',
				'message' => 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.'
			]);
		}
```

- **TARGET 2 (`confirm_upload_excel_dinamis`)**:
```php
		$user_level     = strtolower(trim((string)$this->session->get('admin_level')));
		$unitkerjalogin = $this->session->get('admin_unitkerja');

		if (!in_array($user_level, ['admin', 'super admin', 'superadmin']) && $unitkerjalogin !== 'bps') {
			if ($tabel->id_unitkerja != $unitkerjalogin) {
				return $this->response->setStatusCode(403)->setJSON([
					'status'     => 'error',
					'message'    => 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.',
					'csrf_token' => csrf_token(),
					'csrf_hash'  => csrf_hash(),
				]);
			}
		}
```
- **REPLACEMENT 2**:
```php
		if (!$this->canManageTable($tabel)) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'     => 'error',
				'message'    => 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.',
				'csrf_token' => csrf_token(),
				'csrf_hash'  => csrf_hash(),
			]);
		}
```

- **TARGET 3 (`upload_excel_dinamis`)**:
```php
		$user_level     = strtolower(trim((string)$this->session->get('admin_level')));
		$unitkerjalogin = $this->session->get('admin_unitkerja');

		// Hanya Admin/Super Admin atau akun BPS yang berwenang mengunggah ke tabel instansi mana saja
		if (!in_array($user_level, ['admin', 'super admin', 'superadmin']) && $unitkerjalogin !== 'bps') {
			if ($tabel->id_unitkerja != $unitkerjalogin) {
				$errMsg = 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.';
				if ($this->request->isAJAX()) {
					return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => $errMsg]);
				}
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">{$errMsg}</div>");
				return redirect()->back();
			}
		}
```
- **REPLACEMENT 3**:
```php
		if (!$this->canManageTable($tabel)) {
			$errMsg = 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.';
			if ($this->request->isAJAX()) {
				return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => $errMsg]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">{$errMsg}</div>");
			return redirect()->back();
		}
```

- **TARGET 4 (`simpan_catatan_tabel`)**:
```php
		$user_level     = strtolower(trim((string)$this->session->get('admin_level')));
		$unitkerjalogin = $this->session->get('admin_unitkerja');

		// Hanya Admin/Super Admin atau akun BPS yang berwenang mengubah catatan tabel instansi mana saja
		if (!in_array($user_level, ['admin', 'super admin', 'superadmin']) && $unitkerjalogin !== 'bps') {
			if ($tabel->id_unitkerja != $unitkerjalogin) {
				return $this->response->setStatusCode(403)->setJSON([
					'status'  => 'error',
					'message' => 'Akses ditolak! Anda tidak memiliki izin untuk memperbarui catatan tabel instansi lain.'
				]);
			}
		}
```
- **REPLACEMENT 4**:
```php
		if (!$this->canManageTable($tabel)) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'  => 'error',
				'message' => 'Akses ditolak! Anda tidak memiliki izin untuk memperbarui catatan tabel instansi lain.'
			]);
		}
```

#### Langkah 30.3 — Batasi Form `f_kondef.php` Menjadi Read-Only untuk Role yang Tidak Memiliki `can('input_dda_input')`
- **File**: [`app/Views/admin/f_kondef.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_kondef.php#L1-L47)
- **Instruksi**: Gunakan `edit_file` pada `app/Views/admin/f_kondef.php`:
- **TARGET**:
```php
<?php
$idp = $datpil->id;
$judul_ind = $datpil->judul_ind;
$kondef = $datpil->kondef;
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-info-circle me-2"></i>Konsep & Definisi
                </h5>
            </div>
            
            <div class="card-body p-4">
                <form action="<?php echo base_URL()?>index.php/admin/dda/kondef_update" method="POST">
                    <input type="hidden" name="idp" value="<?php echo $idp; ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary mb-2">Internal Judul Tabel</label>
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-primary">
                            <?php echo htmlspecialchars($judul_ind); ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary mb-2">Penjelasan Konsep Dan Definisi</label>
                        <textarea name="kondef" tabindex="1" required class="form-control" rows="6" placeholder="Masukkan penjelasan konsep dan definisi operasional untuk tabel ini..."><?php echo $kondef; ?></textarea>
                        <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Informasi ini akan membantu pengguna memahami data yang disajikan.</div>
                    </div>
                    
                    <hr class="text-secondary opacity-25 mt-4 mb-3">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo base_URL(); ?>index.php/admin/dda" class="btn btn-light border" tabindex="3">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary px-4" tabindex="2">
                            <i class="bi bi-check2-circle me-1"></i> Perbarui Kondef
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
```
- **REPLACEMENT**:
```php
<?php
$idp = $datpil->id;
$judul_ind = $datpil->judul_ind;
$kondef = $datpil->kondef;
$canEditKondef = function_exists('can') ? can('input_dda_input') : true;
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-info-circle me-2"></i>Konsep & Definisi
                </h5>
            </div>
            
            <div class="card-body p-4">
                <form action="<?php echo base_URL()?>index.php/admin/dda/kondef_update" method="POST">
                    <input type="hidden" name="idp" value="<?php echo esc($idp); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary mb-2">Internal Judul Tabel</label>
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-primary">
                            <?php echo esc($judul_ind); ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary mb-2">Penjelasan Konsep Dan Definisi</label>
                        <textarea name="kondef" tabindex="1" required class="form-control" rows="6" <?php echo !$canEditKondef ? 'readonly' : ''; ?> placeholder="Masukkan penjelasan konsep dan definisi operasional untuk tabel ini..."><?php echo esc($kondef); ?></textarea>
                        <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Informasi ini akan membantu pengguna memahami data yang disajikan.</div>
                    </div>
                    
                    <hr class="text-secondary opacity-25 mt-4 mb-3">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo base_URL(); ?>index.php/admin/dda" class="btn btn-light border" tabindex="3">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <?php if ($canEditKondef): ?>
                        <button type="submit" class="btn btn-primary px-4" tabindex="2">
                            <i class="bi bi-check2-circle me-1"></i> Perbarui Kondef
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
```

---

### [x] TASK-31: Perbaiki Logika Tombol `+ Tambah Tabel` & Hapus Double Flashdata di `l_dda.php` (Memperbaiki P1-C)

- **File**: [`app/Views/admin/l_dda.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_dda.php#L50-L65)
- **Penjelasan**:
  1. Baris 50 memanggil `echo session()->getFlashdata("k")` padahal `app/Views/admin/index.php` baris 781 sudah mencetaknya.
  2. Baris 59 menggunakan operator `||` (`!= 'bps' || != ''`) yang selalu bernilai `TRUE` untuk semua akun, padahal fitur usulan tabel (`admin/master_tabel_opd/add`) hanya relevan untuk pengusul yang memiliki hak input atau admin master tabel. Selain itu di `Admin::master_tabel_opd()` perlu ditambahkan pengecekan RBAC.
- **Instruksi 31.1**: Gunakan `edit_file` pada `app/Views/admin/l_dda.php`:
- **TARGET**:
```php
<?php echo session()->getFlashdata("k"); ?>

<!-- Toolbar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-journal-text me-2 text-primary"></i>Tabel Jawa Tengah Dalam Angka
            </h5>
            <?php if(session()->get('admin_unitkerja') != 'bps' || session()->get('admin_unitkerja') != ''): ?>
            <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/add" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Tabel
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
```
- **REPLACEMENT**:
```php
<!-- Toolbar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-journal-text me-2 text-primary"></i>Tabel Jawa Tengah Dalam Angka
            </h5>
            <?php if (can('input_dda_input') && session()->get('admin_unitkerja') !== 'bps' && session()->get('admin_unitkerja') !== ''): ?>
            <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/add" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Usulkan Tabel Baru
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
```

- **Instruksi 31.2**: Tambahkan guard pada `Admin::master_tabel_opd()` di [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L1998-L2006) agar role read-only (`walidata_opd`) tidak dapat mengakses halaman tambah/kelola usulan:
- **TARGET**:
```php
	public function master_tabel_opd()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$ta = $this->session->get('admin_ta') ?: date('Y');
```
- **REPLACEMENT**:
```php
	public function master_tabel_opd()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('master_tabel') && !can('input_dda_input')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengakses menu Usulan Tabel.</div>');
			return redirect()->to('admin/dda');
		}

		$ta = $this->session->get('admin_ta') ?: date('Y');
```

---

### [x] TASK-32: Hardening View `app/Views/admin/f_profil.php` untuk Halaman "Profil Saya" (`admin/profil`) (Memperbaiki P1-D)

- **File Baru**: `app/Views/admin/f_profil.php`
- **Penjelasan**: Method `Admin::profil()` (baris 357–397) me-render `$a['page'] = "f_profil"`, namun file `app/Views/admin/f_profil.php` belum ada sehingga mengklik menu **"Profil Saya"** di navbar menghasilkan `ViewException`.
- **Instruksi**: Gunakan `write_file` untuk membuat file `app/Views/admin/f_profil.php` dengan isi berikut:
```php
<?php
$nama     = $datpil->nama ?? '';
$nip      = $datpil->nip ?? '';
$email    = $datpil->email ?? '';
$username = $datpil->username ?? '';
$level    = $datpil->level ?? '';
$unit     = $datpil->id_unitkerja ?? '';
?>

<div class="container-fluid py-2 px-3">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: rgba(255, 109, 31, 0.12);">
                                <i class="bi bi-person-circle text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">Profil Saya</h5>
                                <p class="text-muted small mb-0">Perbarui identitas akun pengguna Anda</p>
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border px-3 py-2">
                            <i class="bi bi-shield-lock me-1 text-primary"></i><?php echo esc(function_exists('current_user_role_label') ? current_user_role_label() : strtoupper((string)$level)); ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="<?php echo base_url('admin/profil/act_edt'); ?>" method="post" accept-charset="utf-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Username Login</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-at text-primary"></i></span>
                                    <input type="text" name="username" class="form-control" required minlength="4" value="<?php echo esc($username); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Kode Unit Kerja / Instansi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-building text-muted"></i></span>
                                    <input type="text" class="form-control bg-light" value="<?php echo esc(strtoupper((string)$unit)); ?>" readonly disabled>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person text-primary"></i></span>
                                    <input type="text" name="nama" class="form-control" required value="<?php echo esc($nama); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">NIP</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-upc text-primary"></i></span>
                                    <input type="text" name="nip" class="form-control" required value="<?php echo esc($nip); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope text-primary"></i></span>
                                    <input type="email" name="email" class="form-control" required value="<?php echo esc($email); ?>">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 opacity-25">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?php echo base_url('admin/passwod'); ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-key me-1"></i> Ganti Password
                            </a>
                            <div class="d-flex gap-2">
                                <a href="<?php echo base_url('admin'); ?>" class="btn btn-light border px-3">Batal</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

### [x] TASK-33: Eliminasi N+1 Query Problem pada `Admin::master_tim()` (Memperbaiki P2-A)

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2875-L2911)
- **Penjelasan**: Saat ini `Admin::master_tim()` menjalankan 2 query SQL di dalam `foreach ($teams_raw as $t)`. Kita ganti dengan 2 query batch (`WHERE IN`) sebelum loop, lalu dikelompokkan di memori PHP.
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET**:
```php
			$teams_raw = $this->db->query("SELECT * FROM t_admin WHERE (username LIKE 'tim%' OR nama LIKE 'Tim %' OR username IN (SELECT DISTINCT user_wali FROM m_unitkerja)) AND id_unitkerja = 'bps' ORDER BY id ASC")->getResult();

			$teams_data = [];
			$total_anggota_count = 0;
			$total_opd_count = 0;

			foreach ($teams_raw as $t) {
				// Ambil anggota tim dari t_tim_anggota
				$members = $this->db->query("SELECT ta.id as rel_id, ta.created_at, u.id as admin_id, u.nama, u.nip, u.username, u.email 
					FROM t_tim_anggota ta 
					JOIN t_admin u ON ta.id_admin = u.id 
					WHERE ta.kode_tim = ? 
					ORDER BY u.nama ASC", [$t->username])->getResult();

				// Ambil daftar OPD binaan dari m_unitkerja
				$opds = $this->db->query("SELECT id_unitkerja, unitkerja_ind, unitkerja_en, user_spv 
					FROM m_unitkerja 
					WHERE user_wali = ? 
					ORDER BY unitkerja_ind ASC", [$t->username])->getResult();

				$total_anggota_count += count($members);
				$total_opd_count += count($opds);
				$spv_arr = array_unique(array_filter(array_map(function($o) {
					return (!empty($o->user_spv) && $o->user_spv !== '-') ? strtoupper($o->user_spv) : null;
				}, $opds)));
				$spv_text = !empty($spv_arr) ? implode(', ', $spv_arr) : '-';

				$teams_data[] = (object)[
					'id'        => $t->id,
					'kode_tim'  => $t->username,
					'nama'      => $t->nama,
					'members'   => $members,
					'opds'      => $opds,
					'spv'       => $spv_text,
				];
			}
```
- **REPLACEMENT**:
```php
			$teams_raw = $this->db->query("SELECT * FROM t_admin WHERE (username LIKE 'tim%' OR nama LIKE 'Tim %' OR username IN (SELECT DISTINCT user_wali FROM m_unitkerja)) AND id_unitkerja = 'bps' ORDER BY id ASC")->getResult();

			$teams_data = [];
			$total_anggota_count = 0;
			$total_opd_count = 0;

			$membersByTim = [];
			$opdsByTim    = [];
			$kodeTimList  = array_values(array_filter(array_map(fn($r) => $r->username, $teams_raw)));

			if (!empty($kodeTimList)) {
				$placeholders = implode(',', array_fill(0, count($kodeTimList), '?'));

				$allMembers = $this->db->query("SELECT ta.kode_tim, ta.id as rel_id, ta.created_at, u.id as admin_id, u.nama, u.nip, u.username, u.email 
					FROM t_tim_anggota ta 
					JOIN t_admin u ON ta.id_admin = u.id 
					WHERE ta.kode_tim IN ($placeholders) 
					ORDER BY u.nama ASC", $kodeTimList)->getResult();
				foreach ($allMembers as $m) {
					$membersByTim[$m->kode_tim][] = $m;
				}

				$allOpds = $this->db->query("SELECT user_wali, id_unitkerja, unitkerja_ind, unitkerja_en, user_spv 
					FROM m_unitkerja 
					WHERE user_wali IN ($placeholders) 
					ORDER BY unitkerja_ind ASC", $kodeTimList)->getResult();
				foreach ($allOpds as $o) {
					$opdsByTim[$o->user_wali][] = $o;
				}
			}

			foreach ($teams_raw as $t) {
				$members = $membersByTim[$t->username] ?? [];
				$opds    = $opdsByTim[$t->username] ?? [];

				$total_anggota_count += count($members);
				$total_opd_count += count($opds);
				$spv_arr = array_unique(array_filter(array_map(function($o) {
					return (!empty($o->user_spv) && $o->user_spv !== '-') ? strtoupper($o->user_spv) : null;
				}, $opds)));
				$spv_text = !empty($spv_arr) ? implode(', ', $spv_arr) : '-';

				$teams_data[] = (object)[
					'id'        => $t->id,
					'kode_tim'  => $t->username,
					'nama'      => $t->nama,
					'members'   => $members,
					'opds'      => $opds,
					'spv'       => $spv_text,
				];
			}
```

---

### [x] TASK-34: Hapus Query Agregasi `COUNT` Ganda & Escape Flashdata di `master_dimensi()` dan `generator_tabel()` (Memperbaiki P2-B & P2-C)

#### Langkah 34.1 — Escape `{$nama_ind}` pada Flashdata `add_karakteristik`, `add_baris`, dan `add_periode`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5464-L5785)
- **Instruksi**: Gunakan `edit_file` pada 3 tempat di `app/Controllers/Admin.php`:
- **TARGET 1 (Baris 5464)**:
```php
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Karakteristik Kolom '<strong>{$nama_ind}</strong>' berhasil ditambahkan!</div>");
```
- **REPLACEMENT 1**:
```php
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Karakteristik Kolom '<strong>" . esc($nama_ind) . "</strong>' berhasil ditambahkan!</div>");
```

- **TARGET 2 (Baris 5629)**:
```php
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Baris '<strong>{$nama_ind}</strong>' berhasil ditambahkan!</div>");
```
- **REPLACEMENT 2**:
```php
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Baris '<strong>" . esc($nama_ind) . "</strong>' berhasil ditambahkan!</div>");
```

- **TARGET 3 (Baris 5782)**:
```php
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Periode '<strong>{$nama_ind}</strong>' berhasil ditambahkan!</div>");
```
- **REPLACEMENT 3**:
```php
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Periode '<strong>" . esc($nama_ind) . "</strong>' berhasil ditambahkan!</div>");
```

#### Langkah 34.2 — Hapus Pemanggilan Duplikat `getKarakteristikCounts()`, `getBarisCounts()`, `getPeriodeCounts()` di `master_dimensi()` & `generator_tabel()`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L5913-L5936) dan baris 6045–6062
- **Penjelasan**: Method `getKarakteristikKelompokAll()`, `getBarisKelompokAll()`, dan `getPeriodeKelompokAll()` di `M_tabeldinamis.php` sudah mengambil `COUNT(i.id) AS total_items` via `LEFT JOIN`.
- **Instruksi**: Gunakan `edit_file` pada `app/Controllers/Admin.php`:
- **TARGET 1 (`master_dimensi` baris 5913–5935)**:
```php
		// Data Karakteristik (Optimasi Single Aggregation Query)
		$a['list_karakteristik'] = $mTabelDinamis->getKarakteristikKelompokAll();
		$kCounts = $mTabelDinamis->getKarakteristikCounts();
		foreach ($a['list_karakteristik'] as $k) {
			$k->total_items = $kCounts[(int)$k->id] ?? 0;
		}

		// Data Baris (Optimasi Single Aggregation Query)
		$a['list_baris'] = $mTabelDinamis->getBarisKelompokAll();
		$bCounts = $mTabelDinamis->getBarisCounts();
		foreach ($a['list_baris'] as $b) {
			$b->total_items = $bCounts[(int)$b->id] ?? 0;
		}

		// Data Periode (Optimasi Single Aggregation Query)
		$a['list_periode'] = $mTabelDinamis->getPeriodeKelompokAll();
		$pCounts = $mTabelDinamis->getPeriodeCounts();
		foreach ($a['list_periode'] as $p) {
			$p->total_items = $pCounts[(int)$p->id] ?? 0;
		}
```
- **REPLACEMENT 1**:
```php
		// Data Karakteristik, Baris, dan Periode (sudah menyertakan total_items via LEFT JOIN COUNT)
		$a['list_karakteristik'] = $mTabelDinamis->getKarakteristikKelompokAll();
		$a['list_baris']         = $mTabelDinamis->getBarisKelompokAll();
		$a['list_periode']       = $mTabelDinamis->getPeriodeKelompokAll();
```

- **TARGET 2 (`generator_tabel` baris 6045–6062)**:
```php
		$a['list_karakteristik'] = $mTabelDinamis->getKarakteristikKelompokAll();
		$a['list_baris']         = $mTabelDinamis->getBarisKelompokAll();
		$a['list_periode']       = $mTabelDinamis->getPeriodeKelompokAll(); // hanya standar dan kustom

		$kCounts = $mTabelDinamis->getKarakteristikCounts();
		$bCounts = $mTabelDinamis->getBarisCounts();
		$pCounts = $mTabelDinamis->getPeriodeCounts();

		foreach ($a['list_karakteristik'] as $k) {
			$k->total_items = $kCounts[(int)$k->id] ?? 0;
		}
		foreach ($a['list_baris'] as $b) {
			$b->total_items = $bCounts[(int)$b->id] ?? 0;
		}
		foreach ($a['list_periode'] as $p) {
			$p->total_items = $pCounts[(int)$p->id] ?? 0;
		}
```
- **REPLACEMENT 2**:
```php
		$a['list_karakteristik'] = $mTabelDinamis->getKarakteristikKelompokAll();
		$a['list_baris']         = $mTabelDinamis->getBarisKelompokAll();
		$a['list_periode']       = $mTabelDinamis->getPeriodeKelompokAll(); // sudah menyertakan total_items
```

---

### [x] TASK-35: Pembersihan XSS di View, Hapus Double Flashdata, Dead JS, & Dead Legacy Methods (Memperbaiki P2-C & P3)

#### Langkah 35.1 — Hapus Dead Legacy Methods (`get_klasifikasi`, `get_bidang`, `get_instansi_lain`, `get_kegiatan`, `konfirmasi`) & Double Flashdata di `Admin::master_opd()`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php)
- **Instruksi**: Gunakan `edit_file` pada 3 bagian di `app/Controllers/Admin.php`:
- **TARGET 1 (Baris 308–356 — `get_klasifikasi`, `get_bidang`, `get_instansi_lain`)**:
```php
	public function get_klasifikasi()
	{
		$kode 				= $this->input->post('kode', TRUE);

		$data 				=  $this->db->query("SELECT id, kode, nama FROM ref_klasifikasi WHERE kode LIKE ? ORDER BY id ASC", ['%' . $kode . '%'])->getResult();

		$klasifikasi 		=  array();
		foreach ($data as $d) {
			$json_array				= array();
			$json_array['value']	= $d->kode;
			$json_array['label']	= $d->kode . " - " . $d->nama;
			$klasifikasi[] 			= $json_array;
		}

		echo json_encode($klasifikasi);
	}

	//tambahan
	public function get_bidang()
	{
		$kode 				= $this->input->post('kpd_yth', TRUE);

		$data 				=  $this->db->query("SELECT * FROM m_bidang WHERE nama_bidang LIKE ? ORDER BY id_bidang ASC", ['%' . $kode . '%'])->getResult();

		$klasifikasi 		=  array();
		foreach ($data as $d) {
			$json_array				= array();
			$json_array['value']	= $d->id_bidang . " - " . $d->nama_bidang;
			$json_array['label']	= $d->id_bidang . " - " . $d->nama_bidang;
			$klasifikasi[] 			= $json_array;
		}

		echo json_encode($klasifikasi);
	}

	public function get_instansi_lain()
	{
		$kode 				= $this->input->post('dari', TRUE);

		$data 				=  $this->db->query("SELECT dari FROM t_surat_masuk WHERE dari LIKE ? GROUP BY dari", ['%' . $kode . '%'])->getResult();

		$klasifikasi 		=  array();
		foreach ($data as $d) {
			$klasifikasi[] 	= $d->dari;
		}

		echo json_encode($klasifikasi);
	}
```
- **REPLACEMENT 1**: *(Kosongkan / hapus ketiga fungsi warisan aplikasi surat masuk tersebut)*

- **TARGET 2 (`get_kegiatan` dan `konfirmasi` — sekitar baris 485–536)**:
```php
	public function get_kegiatan()
	{
		$unitkerja = $this->input->post('unitkerja');
		$bidang = substr($unitkerja, 1, 4);
		$query 	=  $this->db->query("SELECT * FROM m_jeniskegiatan WHERE substring(id_jeniskegiatan,1,4) = ?", [$bidang]);
?>
		<option value="Kosong">-- Pilih Nama Kegiatan--<?php echo $bidang; ?></option>
		<?php
		foreach ($query->getResult() as $row) {
			echo "<option value='" . $row->id_jeniskegiatan . "'>" . $row->nama_kegiatan . "</option>";
		}
	}


	public function konfirmasi()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$ta = $this->session->get('admin_ta');
		parse_str($_SERVER['QUERY_STRING'], $_GET);

		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);
		$idu					= $this->uri->segment(4);

		//ambil variabel post
		$id_kontrak_konfirm		= $this->input->post('id_kontrak');
		$cari					= $this->input->post('q');

		if ($mau_ke == "edt") {
			$id_edit 		  = $this->input->get('konfirmasi_id');
		?>
			<script>
				//window.alert('<?php echo $id_edit; ?>');
			</script>
		<?php
			$a['datpil']	= $this->db->query("select * from t_kontrak where id_kontrak=?", [$id_edit])->getRow();
			$a['page']		= "f_konfirmasi";
		} else if ($mau_ke == "act_edt") {
			$this->db->query("UPDATE t_kontrak SET flag_konfirm = '1' WHERE id_kontrak = ?", [$id_kontrak_konfirm]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data sudah dikonfirmasi selesai</div>");
			return redirect()->to('admin/oi/');
		} else {
			$a['page'] = 'dashboard';
		}

		return view('admin/index', $a);
	}
```
- **REPLACEMENT 2**: *(Kosongkan / hapus kedua fungsi warisan aplikasi kontrak tersebut)*

- **TARGET 3 (Hapus duplikasi `setFlashdata` di `master_opd` baris 2252–2259)**:
```php
			} else {
				$this->db->query("INSERT INTO m_unitkerja (id_unitkerja, unitkerja_ind, unitkerja_en, jenis_instansi, user_wali, user_spv) VALUES (?, ?, ?, ?, ?, ?)", [$id_unitkerja, $unitkerja_ind, $unitkerja_en, $jenis_instansi, $user_wali, $user_spv]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
			return redirect()->to('admin/master_opd');
```
- **REPLACEMENT 3**:
```php
			} else {
				$this->db->query("INSERT INTO m_unitkerja (id_unitkerja, unitkerja_ind, unitkerja_en, jenis_instansi, user_wali, user_spv) VALUES (?, ?, ?, ?, ?, ?)", [$id_unitkerja, $unitkerja_ind, $unitkerja_en, $jenis_instansi, $user_wali, $user_spv]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
				return redirect()->to('admin/master_opd');
			}
```

#### Langkah 35.2 — Escape Variabel User di `app/Views/admin/index.php` dan `app/Views/admin/d_amain.php`
- **File 1**: [`app/Views/admin/index.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/index.php#L744-L754)
- **TARGET**:
```php
                            <div class="user-info d-none d-md-block">
                                <div class="user-name"><?php echo $nama; ?></div>
                                <div class="user-role"><?php echo function_exists('current_user_role_label') ? current_user_role_label() : $level; ?></div>
                            </div>
                            <div class="user-avatar"><?php echo $initial; ?></div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <h6 class="dropdown-header"><?php echo $nama; ?></h6>
                            </li>
```
- **REPLACEMENT**:
```php
                            <div class="user-info d-none d-md-block">
                                <div class="user-name"><?php echo esc($nama); ?></div>
                                <div class="user-role"><?php echo esc(function_exists('current_user_role_label') ? current_user_role_label() : $level); ?></div>
                            </div>
                            <div class="user-avatar"><?php echo esc($initial); ?></div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <h6 class="dropdown-header"><?php echo esc($nama); ?></h6>
                            </li>
```

- **File 2**: [`app/Views/admin/d_amain.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/d_amain.php#L28-L39)
- **TARGET**:
```php
            Selamat datang <strong><?php echo session()->get('admin_nama'); ?></strong>
```
- **REPLACEMENT**:
```php
            Selamat datang <strong><?php echo esc(session()->get('admin_nama')); ?></strong>
```

#### Langkah 35.3 — Escape Output & Hapus Double Flashdata di `l_dda_admin.php`, `l_forum.php`, `l_forum_topik.php`, `l_forum_komen.php`, `l_master_tim.php`, `l_master_tabel_opd.php`, `f_pengguna.php`, `f_master_tabel.php`, & `l_master_tabel.php`

- **File 1**: [`app/Views/admin/l_dda_admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_dda_admin.php#L1-L82)
  - **TARGET 1 (Baris 1–4)**:
```php
<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
echo session()->getFlashdata("k");
?>  
```
  - **REPLACEMENT 1**:
```php
<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
?>  
```
  - **TARGET 2 (Baris 70–82)**:
```php
                    <tr class="opd-row" data-jenis="<?php echo $b->jenis_instansi ?? 'vertikal'; ?>">
                        <td class="text-center opd-no"><?php echo $no;?></td>
                        <td>
                            <a href="<?php echo base_URL()?>index.php/admin/dda/periksa_tabel?id=<?php echo $b->id_unitkerja ;?>" class="text-decoration-none fw-medium text-dark d-inline-flex align-items-center">
                                <i class="bi bi-folder-check me-2 text-primary"></i><?php echo $b->unitkerja_ind;?>
                            </a>
                        </td>
                        <?php if(session()->get('admin_level') != 'lo'): ?>
                        <td class="text-center text-muted small">
                            <span class="badge bg-light text-dark border"><i class="bi bi-person-badge me-1"></i><?php echo $b->user_wali;?></span>
                        </td>
                        <?php endif; ?>
                    </tr>
```
  - **REPLACEMENT 2**:
```php
                    <tr class="opd-row" data-jenis="<?php echo esc($b->jenis_instansi ?? 'vertikal', 'attr'); ?>">
                        <td class="text-center opd-no"><?php echo $no;?></td>
                        <td>
                            <a href="<?php echo base_URL()?>index.php/admin/dda/periksa_tabel?id=<?php echo esc($b->id_unitkerja, 'url');?>" class="text-decoration-none fw-medium text-dark d-inline-flex align-items-center">
                                <i class="bi bi-folder-check me-2 text-primary"></i><?php echo esc($b->unitkerja_ind);?>
                            </a>
                        </td>
                        <?php if(session()->get('admin_level') != 'lo'): ?>
                        <td class="text-center text-muted small">
                            <span class="badge bg-light text-dark border"><i class="bi bi-person-badge me-1"></i><?php echo esc($b->user_wali);?></span>
                        </td>
                        <?php endif; ?>
                    </tr>
```

- **File 2**: [`app/Views/admin/l_forum.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_forum.php#L1-L50)
  - **TARGET 1 (Baris 1–4)**:
```php
<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
echo session()->getFlashdata("k");
?>  
```
  - **REPLACEMENT 1**:
```php
<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
?>  
```
  - **TARGET 2 (Baris 45–50)**:
```php
                                    <td class="opd-name">
                                        <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_topik?id=<?php echo $b->id_unitkerja ;?>" class="text-decoration-none fw-medium text-primary">
                                            <i class="bi bi-folder2-open me-2 text-warning"></i><?php echo $b->unitkerja_ind;?>
                                        </a>
                                    </td>
```
  - **REPLACEMENT 2**:
```php
                                    <td class="opd-name">
                                        <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_topik?id=<?php echo esc($b->id_unitkerja, 'url');?>" class="text-decoration-none fw-medium text-primary">
                                            <i class="bi bi-folder2-open me-2 text-warning"></i><?php echo esc($b->unitkerja_ind);?>
                                        </a>
                                    </td>
```

- **File 3**: [`app/Views/admin/l_forum_topik.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_forum_topik.php#L58-L70)
  - **TARGET**:
```php
                <h4 class="fw-bold text-dark mb-1" style="letter-spacing: -0.04em;">Forum Diskusi</h4>
                <p class="text-muted small mb-0"><i class="bi bi-building me-1"></i> <?php echo $nama_opd; ?></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group search-container shadow-sm p-0" style="width: 280px; border-radius: 12px; overflow: hidden; background: #fff; border: 1px solid #eee;">
                <span class="input-group-text bg-white border-0 text-muted px-3"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-0 ps-0 py-2 fw-medium" id="searchTopik" placeholder="Cari topik diskusi..." autocomplete="off" style="font-size: 0.85rem;">
            </div>
            <a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/add_topik?id=<?php echo $id_unitkerja;?>" class="btn btn-theme px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 12px; height: 42px; font-weight: 700;">
```
  - **REPLACEMENT**:
```php
                <h4 class="fw-bold text-dark mb-1" style="letter-spacing: -0.04em;">Forum Diskusi</h4>
                <p class="text-muted small mb-0"><i class="bi bi-building me-1"></i> <?php echo esc($nama_opd); ?></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group search-container shadow-sm p-0" style="width: 280px; border-radius: 12px; overflow: hidden; background: #fff; border: 1px solid #eee;">
                <span class="input-group-text bg-white border-0 text-muted px-3"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-0 ps-0 py-2 fw-medium" id="searchTopik" placeholder="Cari topik diskusi..." autocomplete="off" style="font-size: 0.85rem;">
            </div>
            <a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/add_topik?id=<?php echo esc($id_unitkerja, 'url');?>" class="btn btn-theme px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 12px; height: 42px; font-weight: 700;">
```

- **File 4**: [`app/Views/admin/l_forum_komen.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_forum_komen.php#L28-L92)
  - **TARGET**:
```php
<?php echo session()->getFlashdata("k");?>  

<div class="row g-4">
    <div class="col-lg-8 col-md-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-list-stars me-2 text-primary"></i>Daftar Komentar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="10%" class="text-center small fw-bold">No.</th>
                                <th width="70%" class="small fw-bold">Komentar</th>
                                <th width="20%" class="text-center small fw-bold">Pengguna</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (empty($data)) {
                                echo "<tr><td colspan='3' class='text-center py-5 text-muted'><i class='bi bi-chat-square-dots me-2 d-block mb-3 display-6'></i>Belum ada komentar</td></tr>";
                            } else {
                                $no = (isset($offset) ? $offset : 0) + 1;
                                foreach ($data as $b) {
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $no;?></td>
                                <td>
                                    <div class="text-dark py-2 px-1 rounded-3 bg-light-subtle shadow-sm"><?php echo nl2br(htmlspecialchars($b->comment));?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary px-3 py-2"><i class="bi bi-person me-1"></i><?php echo $b->user_id;?></span>
                                </td>
                            </tr>
                            <?php 
                                $no++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12">
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Komentar</h6>
            </div>
            <div class="card-body">
                <form action="<?php echo base_URL()?>index.php/admin/forum_diskusi/add_comment" method="post">
                    <input type="hidden" name="id_topik" value="<?php echo $id_topik;?>">
                    <input type="hidden" name="id_unitkerja" value="<?php echo $id_unitkerja;?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Tanggapan/Komentar Anda</label>
                        <textarea name="comment" class="form-control" rows="5" required placeholder="Tuliskan komentar di sini..."></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success shadow-sm"><i class="bi bi-send me-1"></i> Simpan Komentar</button>
                        <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_topik?id=<?php echo $id_unitkerja; ?>" class="btn btn-light border">Batal</a>
                    </div>
                </form>
```
  - **REPLACEMENT**:
```php
<div class="row g-4">
    <div class="col-lg-8 col-md-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-list-stars me-2 text-primary"></i>Daftar Komentar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="10%" class="text-center small fw-bold">No.</th>
                                <th width="70%" class="small fw-bold">Komentar</th>
                                <th width="20%" class="text-center small fw-bold">Pengguna</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (empty($data)) {
                                echo "<tr><td colspan='3' class='text-center py-5 text-muted'><i class='bi bi-chat-square-dots me-2 d-block mb-3 display-6'></i>Belum ada komentar</td></tr>";
                            } else {
                                $no = (isset($offset) ? $offset : 0) + 1;
                                foreach ($data as $b) {
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $no;?></td>
                                <td>
                                    <div class="text-dark py-2 px-1 rounded-3 bg-light-subtle shadow-sm"><?php echo nl2br(esc($b->comment));?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary px-3 py-2"><i class="bi bi-person me-1"></i><?php echo esc($b->user_id);?></span>
                                </td>
                            </tr>
                            <?php 
                                $no++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12">
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Komentar</h6>
            </div>
            <div class="card-body">
                <form action="<?php echo base_URL()?>index.php/admin/forum_diskusi/add_comment" method="post">
                    <input type="hidden" name="id_topik" value="<?php echo esc($id_topik);?>">
                    <input type="hidden" name="id_unitkerja" value="<?php echo esc($id_unitkerja);?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Tanggapan/Komentar Anda</label>
                        <textarea name="comment" class="form-control" rows="5" required placeholder="Tuliskan komentar di sini..."></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success shadow-sm"><i class="bi bi-send me-1"></i> Simpan Komentar</button>
                        <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_topik?id=<?php echo esc($id_unitkerja, 'url'); ?>" class="btn btn-light border">Batal</a>
                    </div>
                </form>
```

- **File 5**: [`app/Views/admin/l_master_tim.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_tim.php#L1-L4)
  - **TARGET**:
```php
<?php echo session()->getFlashdata("k"); ?>

<!-- Consolidated Control Card (Format 1: Serasi dengan Master OPD) -->
```
  - **REPLACEMENT**:
```php
<!-- Consolidated Control Card (Format 1: Serasi dengan Master OPD) -->
```

- **File 6**: [`app/Views/admin/l_master_tabel_opd.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_tabel_opd.php#L10-L13)
  - **TARGET**:
```php
 */
echo session()->getFlashdata("k"); 
?>
```
  - **REPLACEMENT**:
```php
 */
?>
```

- **File 7**: [`app/Views/admin/f_pengguna.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_pengguna.php#L20-L70)
  - **TARGET**:
```php
                    <form action="<?php echo base_URL(); ?>index.php/admin/pengguna/act_edt" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                        <input type="hidden" name="idp" value="<?php echo $data->id; ?>">
                        
                        <?php echo session()->getFlashdata("k"); ?>

                        <div class="row g-3">
                            <!-- Left Column: Primary Info -->
                            <div class="col-md-6 border-end border-light pe-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Informasi Instansi</label>
                                
                                <div class="mb-3">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Nama Instansi</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-building text-primary-orange"></i></span>
                                        <input type="text" name="nama" required value="<?php echo $data->nama; ?>" 
                                               class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama Instansi" tabindex="1">
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Alamat Lengkap</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0 align-items-start pt-2"><i class="bi bi-geo-alt text-primary-orange"></i></span>
                                        <textarea name="alamat" required class="form-control border-0 py-2 ps-1 text-sm bg-white" 
                                                  placeholder="Alamat Lengkap Instansi" style="height: 100px;" tabindex="2"><?php echo $data->alamat; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Leadership & Media -->
                            <div class="col-md-6 ps-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Profil Pimpinan & Aset</label>
                                
                                <div class="mb-2">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Nama Pimpinan / Kepala</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-person-badge text-primary-orange"></i></span>
                                        <input type="text" name="kepsek" required value="<?php echo $data->kepsek; ?>" 
                                               class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama Pimpinan" tabindex="3">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">NIP Pimpinan</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-upc-scan text-primary-orange"></i></span>
                                        <input type="text" name="nip_kepsek" required value="<?php echo $data->nip_kepsek; ?>" 
```
  - **REPLACEMENT**:
```php
                    <form action="<?php echo base_URL(); ?>index.php/admin/pengguna/act_edt" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                        <input type="hidden" name="idp" value="<?php echo esc($data->id); ?>">

                        <div class="row g-3">
                            <!-- Left Column: Primary Info -->
                            <div class="col-md-6 border-end border-light pe-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Informasi Instansi</label>
                                
                                <div class="mb-3">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Nama Instansi</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-building text-primary-orange"></i></span>
                                        <input type="text" name="nama" required value="<?php echo esc($data->nama); ?>" 
                                               class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama Instansi" tabindex="1">
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Alamat Lengkap</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0 align-items-start pt-2"><i class="bi bi-geo-alt text-primary-orange"></i></span>
                                        <textarea name="alamat" required class="form-control border-0 py-2 ps-1 text-sm bg-white" 
                                                  placeholder="Alamat Lengkap Instansi" style="height: 100px;" tabindex="2"><?php echo esc($data->alamat); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Leadership & Media -->
                            <div class="col-md-6 ps-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Profil Pimpinan & Aset</label>
                                
                                <div class="mb-2">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Nama Pimpinan / Kepala</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-person-badge text-primary-orange"></i></span>
                                        <input type="text" name="kepsek" required value="<?php echo esc($data->kepsek); ?>" 
                                               class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama Pimpinan" tabindex="3">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">NIP Pimpinan</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-upc-scan text-primary-orange"></i></span>
                                        <input type="text" name="nip_kepsek" required value="<?php echo esc($data->nip_kepsek); ?>" 
```

- **File 8**: [`app/Views/admin/f_master_tabel.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_master_tabel.php#L92-L173)
  - **TARGET**:
```php
                                    <textarea name="judul_ind" id="judul_ind" tabindex="1" required class="form-control" rows="3" placeholder="Ketik judul dasar tabel (tanpa tahun)..." autocomplete="off"><?php echo $judul_ind; ?></textarea>
```
  - **REPLACEMENT**:
```php
                                    <textarea name="judul_ind" id="judul_ind" tabindex="1" required class="form-control" rows="3" placeholder="Ketik judul dasar tabel (tanpa tahun)..." autocomplete="off"><?php echo esc($judul_ind); ?></textarea>
```
  - **TARGET 2 (Baris 138–173)**:
```php
                                <textarea name="judul_en" tabindex="2" required class="form-control" rows="3" placeholder="Type english base table title (without year)..."><?php echo $judul_en; ?></textarea>
                                <small class="text-muted" style="font-size: 11px;">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Write base title without static year at the end. The year suffix will be automatically assembled.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-hash me-1 text-primary"></i> No Tabel</label>
                            <input type="text" name="no_tabel" id="no_tabel" class="form-control" value="<?php echo $no_tabel; ?>" placeholder="Contoh: 1.1">
                            <div id="no_tabel_suggestion" class="mt-1" style="display: none;"></div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark d-block"><i class="bi bi-building me-1 text-primary"></i> Penanggung Jawab (Instansi)</label>
                            <select name="id_unitkerja" id="id_unitkerja" class="form-select" tabindex="5" required>
                                <?php
                                $db = \Config\Database::connect();
                                $unitkerja = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();
                                
                                if ($mode == "edt" || $mode == "act_edt") {
                                    echo "<option selected value='".$idunitkerja_terpilih."'>".$unitkerja_terpilih."</option>";
                                    foreach($unitkerja as $p){
                                        if ($p['id_unitkerja'] != $idunitkerja_terpilih) {
                                            echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>";
                                        }
                                    }
                                } else {
                                    echo "<option selected value=''>- Pilih Instansi Penanggung Jawab -</option>";
                                    foreach($unitkerja as $p){
                                        echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>";
                                    }
                                }
                                ?>
                            </select>  
```
  - **REPLACEMENT 2**:
```php
                                <textarea name="judul_en" tabindex="2" required class="form-control" rows="3" placeholder="Type english base table title (without year)..."><?php echo esc($judul_en); ?></textarea>
                                <small class="text-muted" style="font-size: 11px;">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Write base title without static year at the end. The year suffix will be automatically assembled.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-hash me-1 text-primary"></i> No Tabel</label>
                            <input type="text" name="no_tabel" id="no_tabel" class="form-control" value="<?php echo esc($no_tabel); ?>" placeholder="Contoh: 1.1">
                            <div id="no_tabel_suggestion" class="mt-1" style="display: none;"></div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark d-block"><i class="bi bi-building me-1 text-primary"></i> Penanggung Jawab (Instansi)</label>
                            <select name="id_unitkerja" id="id_unitkerja" class="form-select" tabindex="5" required>
                                <?php
                                $db = \Config\Database::connect();
                                $unitkerja = $db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind")->getResultArray();
                                
                                if ($mode == "edt" || $mode == "act_edt") {
                                    echo "<option selected value='" . esc($idunitkerja_terpilih, 'attr') . "'>" . esc($unitkerja_terpilih) . "</option>";
                                    foreach($unitkerja as $p){
                                        if ($p['id_unitkerja'] != $idunitkerja_terpilih) {
                                            echo "<option value='" . esc($p['id_unitkerja'], 'attr') . "'>" . esc($p['unitkerja_ind']) . "</option>";
                                        }
                                    }
                                } else {
                                    echo "<option selected value=''>- Pilih Instansi Penanggung Jawab -</option>";
                                    foreach($unitkerja as $p){
                                        echo "<option value='" . esc($p['id_unitkerja'], 'attr') . "'>" . esc($p['unitkerja_ind']) . "</option>";
                                    }
                                }
                                ?>
                            </select>  
```

- **File 9**: [`app/Views/admin/l_master_tabel.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_tabel.php#L96-L120) & baris 560–639 (Escape dropdown & hapus Dead JS Reset/Clone)
  - **TARGET 1 (Baris 96–120)**:
```php
                        <?php
                        if (isset($list_opd)) {
                            foreach ($list_opd as $opd) {
                                $selected = (isset($selected_opd) && $selected_opd == $opd->id_unitkerja) ? 'selected' : '';
                                echo "<option value='" . $opd->id_unitkerja . "' $selected>" . $opd->unitkerja_ind . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Bidang -->
                <div class="col-md-3">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1"><i class="bi bi-diagram-2 me-1"></i>Bidang / Tim</label>
                    <select name="filter_bidang" class="form-select border-radius-lg border-light shadow-none text-sm py-2" onchange="this.form.submit()">
                        <option value="all">-- Semua Bidang --</option>
                        <?php
                        if (isset($list_tim)) {
                            foreach ($list_tim as $tim) {
                                $sel = (isset($selected_bidang) && $selected_bidang == $tim->user_wali) ? 'selected' : '';
                                echo "<option value='" . $tim->user_wali . "' $sel>" . strtoupper($tim->user_wali) . "</option>";
                            }
                        }
                        ?>
```
  - **REPLACEMENT 1**:
```php
                        <?php
                        if (isset($list_opd)) {
                            foreach ($list_opd as $opd) {
                                $selected = (isset($selected_opd) && $selected_opd == $opd->id_unitkerja) ? 'selected' : '';
                                echo "<option value='" . esc($opd->id_unitkerja, 'attr') . "' $selected>" . esc($opd->unitkerja_ind) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Bidang -->
                <div class="col-md-3">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1"><i class="bi bi-diagram-2 me-1"></i>Bidang / Tim</label>
                    <select name="filter_bidang" class="form-select border-radius-lg border-light shadow-none text-sm py-2" onchange="this.form.submit()">
                        <option value="all">-- Semua Bidang --</option>
                        <?php
                        if (isset($list_tim)) {
                            foreach ($list_tim as $tim) {
                                $sel = (isset($selected_bidang) && $selected_bidang == $tim->user_wali) ? 'selected' : '';
                                echo "<option value='" . esc($tim->user_wali, 'attr') . "' $sel>" . esc(strtoupper((string)$tim->user_wali)) . "</option>";
                            }
                        }
                        ?>
```
  - **TARGET 2 (Baris 560–639 — Hapus Dead JS `#target_tahun_reset` & `#select_tahun_sumber`)**:
```php
<!-- ========================================================================= -->


<script>
// =========================================================================
// SCRIPT: INTERAKTIFITAS RESET TAHUN & SINKRONISASI TAHUN CLONE
// 1. Mengubah teks prompt konfirmasi (misal: "HAPUS 2026") saat tahun di-select.
// 2. Memvalidasi kecocokan ketikan input user secara real-time.
// 3. Mengaktifkan tombol merah jika teks sudah sesuai.
// 4. Mengatur tahun target clone = tahun sumber + 1 secara otomatis.
// =========================================================================
document.addEventListener("DOMContentLoaded", function() {
    const selectTahun = document.getElementById("target_tahun_reset");
    const promptConfirm = document.getElementById("textPromptConfirm");
    const inputConfirm = document.getElementById("inputConfirmReset");
    const btnExecute = document.getElementById("btnExecuteReset");
    const labelJumlah = document.getElementById("labelJumlahTabel");

    // Perbarui teks instruksi konfirmasi sesuai tahun yang dipilih pada dropdown
    function updateResetPrompt() {
        if (!selectTahun) return;
        const selectedOpt = selectTahun.options[selectTahun.selectedIndex];
        const tahun = selectedOpt ? selectedOpt.value : "";
        const count = selectedOpt ? selectedOpt.getAttribute("data-count") : "0";
        
        const expectedText = "HAPUS " + tahun;
        if (promptConfirm) promptConfirm.textContent = expectedText;
        if (labelJumlah) labelJumlah.textContent = "(" + count + " Tabel)";

        checkInput();
    }

    // Periksa apakah teks yang diketikkan user persis sama dengan kata kunci konfirmasi
    function checkInput() {
        if (!selectTahun || !inputConfirm || !btnExecute) return;
        const selectedOpt = selectTahun.options[selectTahun.selectedIndex];
        const tahun = selectedOpt ? selectedOpt.value : "";
        const expectedText = "HAPUS " + tahun;
        
        // Bandingkan secara case-insensitive
        if (inputConfirm.value.trim().toUpperCase() === expectedText.toUpperCase()) {
            btnExecute.removeAttribute("disabled");
            btnExecute.classList.add("shadow-lg");
        } else {
            btnExecute.setAttribute("disabled", "disabled");
            btnExecute.classList.remove("shadow-lg");
        }
    }

    // Event listener perubahan pilihan tahun di dropdown reset
    if (selectTahun) {
        selectTahun.addEventListener("change", function() {
            if (inputConfirm) inputConfirm.value = "";
            updateResetPrompt();
        });
    }

    // Event listener pengetikan teks konfirmasi
    if (inputConfirm) {
        inputConfirm.addEventListener("input", checkInput);
    }

    // Auto sinkronisasi tahun tujuan di Modal Clone (+1 tahun dari tahun sumber)
    const selectSumber = document.getElementById("select_tahun_sumber");
    const inputTujuan = document.getElementById("input_tahun_tujuan");
    if (selectSumber && inputTujuan) {
        selectSumber.addEventListener("change", function() {
            const val = parseInt(this.value);
            if (!isNaN(val)) {
                inputTujuan.value = val + 1;
            }
        });
    }

    // Inisialisasi awal prompt reset
    updateResetPrompt();
});
</script>
<?php endif; ?>
```
  - **REPLACEMENT 2**:
```php
<!-- ========================================================================= -->
<?php endif; ?>
```

---

## Checklist Verifikasi Pasca-Eksekusi (Wajib Dijalankan oleh Gemini Flash 3.8)

Setelah semua Task (TASK-25 s.d. TASK-35) selesai dieksekusi, jalankan perintah pemeriksaan sintaks PHP berikut di terminal:
```powershell
php -l app/Controllers/Admin.php
php -l app/Controllers/Mail.php
php -l app/Views/admin/f_manage_admin.php
php -l app/Views/admin/f_unitkerja.php
php -l app/Views/admin/f_kondef.php
php -l app/Views/admin/f_profil.php
php -l app/Views/admin/l_dda.php
php -l app/Views/admin/index.php
php -l app/Views/admin/d_amain.php
php -l app/Views/admin/l_dda_admin.php
php -l app/Views/admin/l_forum.php
php -l app/Views/admin/l_forum_topik.php
php -l app/Views/admin/l_forum_komen.php
php -l app/Views/admin/l_master_tim.php
php -l app/Views/admin/l_master_tabel_opd.php
php -l app/Views/admin/f_pengguna.php
php -l app/Views/admin/f_master_tabel.php
php -l app/Views/admin/l_master_tabel.php
```
Pastikan seluruh output menampilkan **`No syntax errors detected`**.

