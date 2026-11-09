# Panduan Eksekusi & Temuan Audit Sistem Login Admin DDA Online (Spesifikasi Anti-Salah untuk Gemini Flash 3.8)

**Tanggal Audit**: 7 November 2026  
**Target Eksekutor**: Gemini Flash 3.8  
**Cakupan**: 100% Area Login (`Admin.php`, `BaseController.php`, `Mail.php`, `M_kelolakegiatan.php`, `M_tabeldinamis.php`, `Web_model.php`, `ExcelTemplateGenerator.php`, `ExcelDataParser.php`, `RbacService.php`, `RbacFilter.php`, `my_helper.php`, `rbac_helper.php`, `Views/admin/*`, dan Database MySQL `ddafix`).  
**Pengecualian**: Modul Publik (`Home.php`, `M_frontend.php`, `Views/frontend/*`) dan Modul Matching PDF (`matching`, `matching_pdf`, `process_matching_pdf`, `verify_tabel_pdf`, `start_batch_worker`, `sync_pdf_to_dinamis`, `auto_config_tabel_pdf`, `ambil_data`, `ambil_databatch`).

---

## ⚠️ ATURAN WAJIB UNTUK GEMINI FLASH 3.8 SEBELUM MENGEKSEKUSI (GUARDRAILS)

> [!CAUTION]
> **WAJIB DIBACA OLEH MODEL EKSEKUTOR (GEMINI FLASH 3.8):**
> 1. **JANGAN PERNAH** menulis ulang seluruh isi file besar seperti `app/Controllers/Admin.php` (7.811 baris) atau `app/Models/M_tabeldinamis.php` (2.498 baris) menggunakan `write_file`. Selalu gunakan `edit_file` hanya pada blok baris yang ditunjuk!
> 2. **JANGAN MENGGANTI NAMA ALIAS KOLOM SQL** pada `view_report.php`, `export_excel_instansi()`, atau `M_kelolakegiatan.php`, karena kode HTML/View di bawahnya membaca properti spesifik (`$b->unitkerja_ind`, `$b->jumlah_tabel`, `$b->belum_isi`, `$b->menunggu_validasi`, `$b->sudah_validasi` pada `view_report.php` vs `$b->nama_opd`, `$b->total_tabel`, `$b->total_acc`, `$b->total_menunggu`, `$b->total_belum` pada `d_amain.php`).
> 3. **JANGAN MENGUBAH PASSWORD MD5 LANGSUNG TANPA DUAL-CHECK (FALLBACK MD5)** di `do_login()`, karena 110 akun di database saat ini masih berformat MD5 32 karakter. Jika langsung diganti ke `password_verify()` murni tanpa fallback `md5()`, **seluruh user tidak akan bisa login!** Ikuti kode *Dual-Mode Auto-Upgrade* pada **TASK-06**.
> 4. **JANGAN MENGUBAH KODE DI LUAR INSTRUKSI** (terutama fungsi-fungsi `matching*` di `Admin.php` baris 3771–5450).
> 5. Eksekusi perbaikan secara bertahap per **TASK** di bawah ini menggunakan blok **`TARGET (KODE LAMA)`** dan **`REPLACEMENT (KODE BARU)`** yang sudah disediakan secara presisi.

---

## Ringkasan 39 Temuan Audit & Pemetaan ke Task Eksekusi

| Kode Temuan | Kategori | Deskripsi Singkat | Nomor Task Eksekusi | Status |
| :--- | :---: | :--- | :---: | :---: |
| **P0-01** | Kritis | Hardcoded SMTP Password BPS & Bearer Token API Satu Data di `Admin.php` & `.env` | **TASK-01** | ✅ [x] Selesai |
| **P0-02** | Kritis | Celah IDOR ambil alih akun Super Admin di `Admin::profil()` (`$idp` dari POST) | **TASK-02** | ✅ [x] Selesai |
| **P0-03** | Kritis | Endpoint `Admin::simpan_config_tabel()` tanpa cek login & RBAC | **TASK-03** | ✅ [x] Selesai |
| **P0-04** | Kritis | Bypass RBAC Verifikasi DDA pada `Admin::dda()` cabang `act_periksa` | **TASK-04** | ✅ [x] Selesai |
| **P0-05** | Kritis | Fitur `Admin::reset_tahun()` ikut menghapus `tahun_data = $target_tahun` (data tahun depan) | **TASK-05** | ✅ [x] Selesai |
| **P0-06** | Kritis | Password `MD5` tanpa salt & tanpa `session->regenerate()` di `do_login()`, `passwod()`, `manage_admin()` | **TASK-06** | ⏳ [ ] Belum |
| **P0-07** | Kritis | Celah otorisasi (Walidata bisa ACC usulan sendiri & hapus usulan OPD lain) di `master_tabel_opd()` | **TASK-07** | ✅ [x] Selesai |
| **P0-08** | Kritis | Ketiadaan guard `can('master_tabel')` di `preview_bulk_portal()` & `bulk_portal_save()` | **TASK-08** | ✅ [x] Selesai |
| **P0-09** | Kritis | `RbacFilter` belum aktif memproteksi grup route `admin` di `Config/Filters.php` | **TASK-09** | ✅ [x] Selesai |
| **P0-10** | Kritis | **SQL Injection langsung di View** `l_forum_komen.php:6` & `l_forum_topik.php:7` (`$_GET` mentah) | **TASK-10** | ✅ [x] Selesai |
| **P0-11** | Kritis | Controller `Mail.php` tanpa autentikasi dengan hardcoded password Gmail | **TASK-11** | ✅ [x] Selesai |
| **P1-01** | Tinggi | Tabel orphan `id=207` (`id_unitkerja = 'kopertis6'`) bikin selisih angka Dashboard (584 vs 583) | **TASK-12** | ✅ [x] Selesai |
| **P1-02** | Tinggi | Auto-delete `m_indikator_tabel` (cascade hapus nilai dinamis!) di `M_tabeldinamis::resolveTableContext()` & `master_tabel` | **TASK-13** | ✅ [x] Selesai |
| **P1-03** | Tinggi | Inkonsistensi cek tahun API Portal Data (`$ta - 1`) di `act_toggle_publish()`, `export_excel_instansi()`, & `bulk_portal_save()` | **TASK-14** | ✅ [x] Selesai |
| **P1-04** | Tinggi | Crash fatal (`Attempt to read property "email" on null`) & IDOR di `Admin::forum_diskusi()` | **TASK-15** | ✅ [x] Selesai |
| **P1-05** | Tinggi | Duplikat username `'imigrasi'` di DB `t_admin` & validasi `strlen($username) < 3` (seharusnya `< 4`) | **TASK-16** | ✅ [x] Selesai |
| **P1-06** | Tinggi | Bug variabel `$total_opd_count` selalu `0` di `Admin::master_tim()` | **TASK-17** | ✅ [x] Selesai |
| **P1-07** | Tinggi | Fallback `ORDER BY tahun DESC` pada `t_status_tahunan` di `M_tabeldinamis::resolveTableContext()` membocorkan status lintas tahun | **TASK-13** | ✅ [x] Selesai |
| **P1-08** | Tinggi | `clone_tahunan()` dan `resequence_tabel()` mengeksekusi ratusan query tanpa `transStart()`/`transComplete()` | **TASK-18** | ✅ [x] Selesai |
| **P1-09** | Tinggi | Query berulang `SELECT * FROM tr_instansi LIMIT 1` pada setiap request di `BaseController.php` karena tabel kosong | **TASK-19** | ✅ [x] Selesai |
| **P1-10** | Tinggi | Menu **Laporan Rekap (`view_report.php`)** menjalankan query SQL lama sendiri di View sehingga angkanya beda dengan Dashboard | **TASK-20** | ✅ [x] Selesai |
| **P1-11** | Tinggi | View `l_dda.php` baris 192 belum mengecek `portal_has_year` jika dipanggil | **TASK-21** | ✅ [x] Selesai |
| **P2-01..04** | Sedang | Perbedaan Collation (`latin1` vs `utf8mb4`), panjang `id_unitkerja VARCHAR(15)`, dan kurangnya Index di DB | **TASK-12** | ✅ [x] Selesai |
| **P2-05..06** | Sedang | N+1 Query di `M_tabeldinamis::getPeriodeKelompokWithItems()` & `l_forum_topik.php` | **TASK-22** | ✅ [x] Selesai |
| **P2-07..08** | Sedang | SQL Injection di `my_helper.php` (`gval`), dead model `Web_model.php`, & dead methods `M_kelolakegiatan.php` | **TASK-23** | ✅ [x] Selesai |
| **P3-01..08** | Rendah | PSR-4 class name `M_kelolakegiatan`, duplikasi `<input name="id_tabel">` di `f_generator_tabel.php`, dead JS di `l_master_tabel.php`, XSS di View, & file `.bak` | **TASK-24** | ✅ [x] Selesai |

---

## DAFTAR INSTRUKSI EKSEKUSI (STEP-BY-STEP)

### [x] TASK-01: Amankan Kredensial SMTP & API Token ke `.env` (Memperbaiki P0-01) - SELESAI ✅

#### Langkah 1.1 — Tambahkan variabel SMTP & Salt di `.env`
- **File**: [`.env`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/.env#L61-L64)
- **Instruksi**: Gunakan `edit_file` pada akhir file `.env`:
- **TARGET**:
```ini
#--------------------------------------------------------------------
# SATU DATA JATENG API
#--------------------------------------------------------------------
SATUDATA_TOKEN = sdj_UYf5h0TPgUW3E0kcZisn33xrTe5PMRqLuXq6CWbAfb69tEA7dILT0TLhpFtjQiWHqrd1pn9SGvE4NYlN
```
- **REPLACEMENT**:
```ini
#--------------------------------------------------------------------
# SATU DATA JATENG API & SMTP & EXCEL SALT
#--------------------------------------------------------------------
SATUDATA_TOKEN = sdj_UYf5h0TPgUW3E0kcZisn33xrTe5PMRqLuXq6CWbAfb69tEA7dILT0TLhpFtjQiWHqrd1pn9SGvE4NYlN
SMTP_HOST = ssl://smtp.bps.go.id
SMTP_PORT = 465
SMTP_USER = ipds3300@bps.go.id
SMTP_PASS = oapeaa360
EXCEL_SECRET_SALT = BPS_JATENG_SECRET_SALT
```

#### Langkah 1.2 — Gunakan `env('SATUDATA_TOKEN')` di `Admin::callApi2()`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2898-L2906)
- **TARGET**:
```php
	private function callApi2($url)
	{
		//$token = 'wenFlEPchGCa7aIMTBoB5Lt0pN1fcsDq';
		//$token = 'k83pdzFWM5C4VF5eOLeaFF7V4iW0bO0q1I9AKlJ2i1JbTEsxhmdLMwC7OXip';
		// $token ='1ScZHTidKmSn562vRXFWBf2wcWImhNL3XcTdtlFMx5cLVYU1zJS9H8uAzJG4';
		// $token = 'uIJibrGVvdBvfi4EroYKbKeBCkpIsVMeVxnlgSTZk8xc4ttmYX63dHDhKoHa';
		$token = 'sdj_UYf5h0TPgUW3E0kcZisn33xrTe5PMRqLuXq6CWbAfb69tEA7dILT0TLhpFtjQiWHqrd1pn9SGvE4NYlN';

		$ch = curl_init($url);
```
- **REPLACEMENT**:
```php
	private function callApi2($url)
	{
		$token = env('SATUDATA_TOKEN', 'sdj_UYf5h0TPgUW3E0kcZisn33xrTe5PMRqLuXq6CWbAfb69tEA7dILT0TLhpFtjQiWHqrd1pn9SGvE4NYlN');

		$ch = curl_init($url);
```

---

### [x] TASK-02: Tutup Celah IDOR Ambil Alih Akun di `Admin::profil()` (Memperbaiki P0-02) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L364-L382)
- **Penjelasan**: Pastikan update profil **hanya** meng-update baris milik `$idu = (int)$this->session->get('admin_id')` dan menolak jika `username` baru sudah digunakan oleh akun lain.
- **TARGET**:
```php
		$idu					= $this->session->get('admin_id');
		$mau_ke					= $this->uri->segment(3);

		//ambil variabel Postingan
		$idp					= $this->input->post('idp') ?? '';
		$nama					= $this->input->post('nama') ?? '';
		$nip					= $this->input->post('nip') ?? '';
		$email					= $this->input->post('email') ?? '';
		$username				= $this->input->post('username') ?? '';

		if ($mau_ke == "act_edt") {
			$this->db->query("UPDATE t_admin SET nama = ?, nip = ?, email = ?, username = ? WHERE id = ?", [$nama, $nip, $email, $username, $idp]);
			// Sync session data
			$this->session->set('admin_nama', $nama);
			$this->session->set('admin_user', $username);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Profil berhasil diperbarui</div>");
			return redirect()->to('admin/profil');
```
- **REPLACEMENT**:
```php
		$idu					= (int)$this->session->get('admin_id');
		$mau_ke					= $this->uri->segment(3);

		//ambil variabel Postingan
		$nama					= trim((string)($this->input->post('nama') ?? ''));
		$nip					= trim((string)($this->input->post('nip') ?? ''));
		$email					= trim((string)($this->input->post('email') ?? ''));
		$username				= trim((string)($this->input->post('username') ?? ''));

		if ($mau_ke == "act_edt") {
			if (strlen($username) < 4) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username minimal 4 karakter.</div>");
				return redirect()->to('admin/profil');
			}
			$dup = $this->db->query("SELECT id FROM t_admin WHERE username = ? AND id != ? LIMIT 1", [$username, $idu])->getRow();
			if ($dup) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username sudah digunakan oleh akun lain.</div>");
				return redirect()->to('admin/profil');
			}

			$this->db->query("UPDATE t_admin SET nama = ?, nip = ?, email = ?, username = ? WHERE id = ?", [$nama, $nip, $email, $username, $idu]);
			// Sync session data
			$this->session->set('admin_nama', $nama);
			$this->session->set('admin_user', $username);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Profil berhasil diperbarui</div>");
			return redirect()->to('admin/profil');
```

---

### [x] TASK-03: Tambahkan Autentikasi & RBAC pada `Admin::simpan_config_tabel()` (Memperbaiki P0-03) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3477-L3487)
- **TARGET**:
```php
	public function simpan_config_tabel()
	{
		if ($this->request->isAJAX()) {
			$id = (int)$this->request->getPost('id');
			$config = $this->request->getPost('config');

			if (!$id || !$config) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);
			}
```
- **REPLACEMENT**:
```php
	public function simpan_config_tabel()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
		}

		if ($this->request->isAJAX()) {
			$id = (int)$this->request->getPost('id');
			$config = $this->request->getPost('config');

			if (!$id || !$config) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);
			}

			$tabelRow = $this->db->table('m_list_tabel')->where('id', $id)->get()->getRow();
			if (!$tabelRow || !$this->canManageTable($tabelRow)) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak'])->setStatusCode(403);
			}
```

---

### [x] TASK-04: Tambahkan Guard RBAC `can('input_dda_verif')` pada `dda/act_periksa` (Memperbaiki P0-04) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L666-L675)
- **TARGET**:
```php
		} else if ($mau_ke == "act_periksa") {
			$id_data = (int)($this->request->getPost('id') ?: $this->input->post('id'));
			$catatan_verif = trim((string)($this->request->getPost('catatan') ?: ($this->request->getPost('catatan_periksa') ?: ($this->input->post('catatan') ?: $this->input->post('catatan_periksa')))));
			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$mTabelDinamis->upsertStatusTahunan($id_data, (int)$ta, [
				'is_confirm' => '1',
				'catatan'    => $catatan_verif,
			]);
			return redirect()->back()->with('k', '<div class="alert alert-success" id="alert">Data berhasil diverifikasi</div>');
```
- **REPLACEMENT**:
```php
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
```

---

### [x] TASK-05: Perbaiki Bug Kehilangan Data Tahun Depan pada `Admin::reset_tahun()` (Memperbaiki P0-05) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3641-L3648)
- **Penjelasan**: Publikasi DDA Tahun `$target_tahun` (misal 2025) memuat data `tahun_data = $target_tahun - 1` (2024). Jangan pernah menghapus `$target_tahun` (2025) karena itu adalah milik DDA 2026!
- **TARGET**:
```php
		// 1. Hapus nilai dinamis untuk tahun data publikasi ini (tahun_data = target_tahun - 1 dan target_tahun)
		$tahunData1 = (int)$target_tahun - 1;
		$tahunData2 = (int)$target_tahun;
		$this->db->query("DELETE FROM t_nilai_dinamis WHERE tahun_data IN (?, ?)", [$tahunData1, $tahunData2]);

		// 2. Hapus entri data status tabel tahunan untuk tahun tersebut
		$this->db->query("DELETE FROM t_status_tahunan WHERE tahun = ?", [$target_tahun]);
```
- **REPLACEMENT**:
```php
		// 1. Hapus nilai dinamis HANYA untuk tahun data publikasi ini (tahun_data = target_tahun - 1)
		$tahunDataTarget = (int)$target_tahun - 1;
		$this->db->query("DELETE FROM t_nilai_dinamis WHERE tahun_data = ?", [$tahunDataTarget]);

		// 2. Hapus entri data status tabel tahunan untuk tahun tersebut
		$this->db->query("DELETE FROM t_status_tahunan WHERE tahun = ?", [$target_tahun]);
```

---

### TASK-06: Upgrade Keamanan Password (Dual-Mode Bcrypt + Fallback MD5 Auto-Upgrade) & Regenerate Session (Memperbaiki P0-06 & P1-05)

> [!IMPORTANT]
> Sebelum mengubah kode PHP pada Task ini, **WAJIB** memperbesar kolom `t_admin.password` menjadi `VARCHAR(255)` terlebih dahulu (lihat **TASK-12**). Kode di bawah ini mendukung **Bcrypt (`password_hash`)** untuk password baru, sekaligus tetap menerima hash **MD5** lama saat login dan **otomatis meng-upgrade-nya ke Bcrypt** begitu user berhasil login!

#### Langkah 6.1 — Perbaiki `manage_admin()` di `Admin.php`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L251-L300)
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
			}
			return redirect()->to('admin/manage_admin');
		} else if ($mau_ke == "act_edt") {
			$dup = $this->db->query("SELECT id FROM t_admin WHERE username = ? AND id != ? LIMIT 1", [$username, $idp])->getRow();
			if ($dup) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Username telah dipakai oleh akun lain!</div>");
				return redirect()->to('admin/manage_admin');
			}
			if ($raw_password === "-" || $raw_password === "") {
				$this->db->query("UPDATE t_admin SET username = ?, nama = ?, nip = ?, level = ?, email=?, id_unitkerja=? WHERE id = ?", [$username, $nama, $nip, $level, $email, $unitkerja, $idp]);
			} else {
				$hashed_pw = password_hash($raw_password, PASSWORD_BCRYPT);
				$this->db->query("UPDATE t_admin SET username = ?, password = ?, nama = ?, nip = ?, level = ?, email=?, id_unitkerja=? WHERE id = ?", [$username, $hashed_pw, $nama, $nip, $level, $email, $unitkerja, $idp]);
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated </div>");
			return redirect()->to('admin/manage_admin');
```

#### Langkah 6.2 — Perbaiki `passwod()` dan `do_login()` di `Admin.php`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L400-L466)
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
				// Auto-upgrade hash MD5 ke Bcrypt jika kolom password sudah mendukung panjang >= 60
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

---

### [x] TASK-07: Tambahkan Otorisasi RBAC pada `Admin::master_tabel_opd()` (Memperbaiki P0-07) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2021-L2031) & baris 2069–2075
- **Instruksi 7.1 (Proteksi Hapus Usulan)**:
- **TARGET**:
```php
		if ($mau_ke == "del") {
			$id_delete   = $this->input->get('delete_id');
			$a['datpil']  = $this->db->query("select * from m_master_tabel_usulan where id=?", [$id_delete])->getRow();
			$a['act_url'] = base_url('index.php/admin/master_tabel_opd/act_del/');
			return view('admin/f_del_master', $a);
		} else if ($mau_ke == "act_del") {
			$id = $this->input->post('id');
			$this->db->query("DELETE FROM m_master_tabel_usulan WHERE id = ?", [$id]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/master_tabel_opd/');
```
- **REPLACEMENT**:
```php
		if ($mau_ke == "del") {
			$id_delete   = $this->input->get('delete_id');
			$rowDel      = $this->db->query("select * from m_master_tabel_usulan where id=?", [$id_delete])->getRow();
			if (!$rowDel || (!$this->isAdminBps() && $rowDel->id_unitkerja !== $unitkerja)) {
				return redirect()->to('admin/master_tabel_opd/');
			}
			$a['datpil']  = $rowDel;
			$a['act_url'] = base_url('index.php/admin/master_tabel_opd/act_del/');
			return view('admin/f_del_master', $a);
		} else if ($mau_ke == "act_del") {
			$id = $this->input->post('id');
			$rowDel = $this->db->query("select * from m_master_tabel_usulan where id=?", [$id])->getRow();
			if ($rowDel && ($this->isAdminBps() || $rowDel->id_unitkerja === $unitkerja)) {
				$this->db->query("DELETE FROM m_master_tabel_usulan WHERE id = ?", [$id]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			}
			return redirect()->to('admin/master_tabel_opd/');
```
- **Instruksi 7.2 (Proteksi Persetujuan/ACC Usulan Hanya untuk Admin BPS)**:
- **TARGET**:
```php
		} else if ($mau_ke == "act_edt") {
			// =================================================================
			// PROSES PERSETUJUAN (ACC) USULAN TABEL DARI OPD
			// Mengubah usulan menjadi tabel resmi di master tabel & tahunan
			// =================================================================
```
- **REPLACEMENT**:
```php
		} else if ($mau_ke == "act_edt") {
			if (!can('master_tabel')) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Hanya Admin BPS yang berwenang menyetujui usulan tabel.</div>");
				return redirect()->to('admin/master_tabel_opd/');
			}
			// =================================================================
			// PROSES PERSETUJUAN (ACC) USULAN TABEL DARI OPD
			// Mengubah usulan menjadi tabel resmi di master tabel & tahunan
			// =================================================================
```

---

### [x] TASK-08: Tambahkan Guard `can('master_tabel')` pada `preview_bulk_portal()` & `bulk_portal_save()` (Memperbaiki P0-08 & P1-03) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3343-L3350) & baris 3417–3475
- **Instruksi 8.1 (`preview_bulk_portal`)**:
- **TARGET**:
```php
	public function preview_bulk_portal()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$ta_input = trim((string)($this->request->getPost('tahun') ?? ''));
```
- **REPLACEMENT**:
```php
	public function preview_bulk_portal()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}
		if (!can('master_tabel')) {
			return redirect()->to('admin')->with('k', '<div class="alert alert-danger">Akses ditolak!</div>');
		}

		$ta_input = trim((string)($this->request->getPost('tahun') ?? ''));
```
- **Instruksi 8.2 (`bulk_portal_save` — tambah guard RBAC & cek tahun `$ta - 1` sebelum set `is_periksa`)**:
- **TARGET**:
```php
	public function bulk_portal_save()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$selected_indices = $this->request->getPost('id_selected'); // Ini array index (0, 1, 2...)
```
- **REPLACEMENT**:
```php
	public function bulk_portal_save()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}
		if (!can('master_tabel')) {
			return redirect()->to('admin')->with('k', '<div class="alert alert-danger">Akses ditolak!</div>');
		}

		$selected_indices = $this->request->getPost('id_selected'); // Ini array index (0, 1, 2...)
```
- **Instruksi 8.3 (Baris 3470 di `bulk_portal_save`)**:
- **TARGET**:
```php
			$mTabelDinamis->upsertStatusTahunan($id_tabel, $ta, ['is_periksa' => 1]);
			$count++;
```
- **REPLACEMENT**:
```php
			$thDataStr = (string)($ta - 1);
			$tahunTersedia = (string)($existKa->tahun_tersedia ?? '');
			$hasYear = ($tahunTersedia !== '' && (in_array($thDataStr, array_map('trim', explode(',', $tahunTersedia)), true) || strpos($tahunTersedia, $thDataStr) !== false));
			$mTabelDinamis->upsertStatusTahunan($id_tabel, $ta, ['is_periksa' => $hasYear ? 1 : 0]);
			$count++;
```

---

### [x] TASK-09: Pasang Filter RBAC secara Terpusat di `Config/Filters.php` (Memperbaiki P0-09) - SELESAI ✅

- **File**: [`app/Config/Filters.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Config/Filters.php#L30-L38)
- **Penjelasan**: Karena `RbacFilter.php` sudah memiliki daftar `$publicActions = ['login', 'do_login', 'logout', 'set_tahun']` serta pengecekan sesi dan permission untuk seluruh route `admin/*`, kita cukup mengaktifkannya pada properti `$filters` di `app/Config/Filters.php`.
- **TARGET**:
```php
    public array $filters = [];
```
- **REPLACEMENT**:
```php
    public array $filters = [
        'rbac' => [
            'before' => ['admin', 'admin/*'],
        ],
    ];
```

---

### [x] TASK-10: Tutup Celah SQL Injection & N+1 Query di View Forum Diskusi (`l_forum_topik.php` & `l_forum_komen.php`) (Memperbaiki P0-10, P2-09, P3-07) - SELESAI ✅

#### Langkah 10.1 — Perbaiki `app/Views/admin/l_forum_topik.php`
- **File**: [`app/Views/admin/l_forum_topik.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_forum_topik.php#L1-L106)
- **Penjelasan**: Ganti interpolasi `where id_unitkerja='$id_unitkerja'` dengan parameter binding `?`, ganti N+1 query penghitung komentar menjadi 1 kali query agregat di awal (atau binding `?`), dan tambahkan `esc()`.
- **TARGET**:
```php
<?php
$id_unitkerja = service('request')->getGet('id');
$unitkerjalogin = session()->get('admin_unitkerja');
$hariini = tgl_jam_sql(date('Y-m-d'));

$db = \Config\Database::connect();
$query_unitkerja = $db->query("SELECT * from m_unitkerja where id_unitkerja='$id_unitkerja' LIMIT 1")->getRow();
$nama_opd = $query_unitkerja ? $query_unitkerja->unitkerja_ind : 'Forum OPD';
?>
```
- **REPLACEMENT**:
```php
<?php
$id_unitkerja = (string)service('request')->getGet('id');
$unitkerjalogin = session()->get('admin_unitkerja');
$hariini = tgl_jam_sql(date('Y-m-d'));

$db = \Config\Database::connect();
$query_unitkerja = $db->query("SELECT * FROM m_unitkerja WHERE id_unitkerja = ? LIMIT 1", [$id_unitkerja])->getRow();
$nama_opd = $query_unitkerja ? $query_unitkerja->unitkerja_ind : 'Forum OPD';

$commentCounts = [];
if (!empty($data)) {
    $topikIds = array_values(array_filter(array_map(fn($r) => (int)$r->id_topik, $data)));
    if (!empty($topikIds)) {
        $placeholders = implode(',', array_fill(0, count($topikIds), '?'));
        $rowsCount = $db->query("SELECT id_topik, COUNT(*) AS jml FROM forumcomment WHERE id_topik IN ($placeholders) GROUP BY id_topik", $topikIds)->getResult();
        foreach ($rowsCount as $rc) {
            $commentCounts[(int)$rc->id_topik] = (int)$rc->jml;
        }
    }
}
?>
```
- **TARGET (baris 80–105 di `l_forum_topik.php`)**:
```php
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                            $jmlhcomment = $db->query("select * from forumcomment comment where id_topik=".$b->id_topik)->getNumRows();
                    ?>
                    <tr class="topik-row">
                        <td class="text-center text-muted fw-bold topik-no" style="font-size: 0.85rem;"><?php echo $no;?></td>
                        <td>
                            <div class="d-flex align-items-center py-1">
                                <div class="icon-box me-3 bg-primary-subtle text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                                    <i class="bi bi-chat-left-text-fill" style="font-size: 1.1rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.4;"><?php echo $b->nama_topik;?></h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">Diskusi internal di instansi terkait</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_comment?id_unitkerja=<?php echo $id_unitkerja.'&id_topik='.$b->id_topik; ?>" 
```
- **REPLACEMENT**:
```php
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                            $jmlhcomment = $commentCounts[(int)$b->id_topik] ?? 0;
                    ?>
                    <tr class="topik-row">
                        <td class="text-center text-muted fw-bold topik-no" style="font-size: 0.85rem;"><?php echo $no;?></td>
                        <td>
                            <div class="d-flex align-items-center py-1">
                                <div class="icon-box me-3 bg-primary-subtle text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                                    <i class="bi bi-chat-left-text-fill" style="font-size: 1.1rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.4;"><?php echo esc($b->nama_topik);?></h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">Diskusi internal di instansi terkait</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_comment?id_unitkerja=<?php echo urlencode((string)$id_unitkerja).'&id_topik='.(int)$b->id_topik; ?>" 
```

#### Langkah 10.2 — Perbaiki `app/Views/admin/l_forum_komen.php`
- **File**: [`app/Views/admin/l_forum_komen.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_forum_komen.php#L1-L25)
- **TARGET**:
```php
<?php
$id_topik = service('request')->getGet('id_topik');
$id_unitkerja = service('request')->getGet('id_unitkerja');

$db = \Config\Database::connect();
$query_topik = $db->query("SELECT * from m_forumtopic where id_topik = '$id_topik' LIMIT 1")->getRow();
$nama_topik = $query_topik ? $query_topik->nama_topik : 'Komentar Forum';

$unitkerjalogin = session()->get('admin_unitkerja');
$hariini = tgl_jam_sql(date('Y-m-d'));
?>

<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/view_topik?id=<?php echo $id_unitkerja; ?>" class="btn btn-sm btn-light border me-3 shadow-sm"><i class="bi bi-arrow-left"></i></a>
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-chat-dots me-2"></i>Komentar: <span class="text-dark small fw-normal fst-italic"><?php echo $nama_topik; ?></span>
                    </h5>
```
- **REPLACEMENT**:
```php
<?php
$id_topik = (int)service('request')->getGet('id_topik');
$id_unitkerja = (string)service('request')->getGet('id_unitkerja');

$db = \Config\Database::connect();
$query_topik = $db->query("SELECT * FROM m_forumtopic WHERE id_topik = ? LIMIT 1", [$id_topik])->getRow();
$nama_topik = $query_topik ? $query_topik->nama_topik : 'Komentar Forum';

$unitkerjalogin = session()->get('admin_unitkerja');
$hariini = tgl_jam_sql(date('Y-m-d'));
?>

<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/view_topik?id=<?php echo esc($id_unitkerja, 'url'); ?>" class="btn btn-sm btn-light border me-3 shadow-sm"><i class="bi bi-arrow-left"></i></a>
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-chat-dots me-2"></i>Komentar: <span class="text-dark small fw-normal fst-italic"><?php echo esc($nama_topik); ?></span>
                    </h5>
```

---

### TASK-11: Amankan Controller `Mail.php` dari Akses Tanpa Login & Hardcoded Password (Memperbaiki P0-11) - SELESAI ✅

- **File**: [`app/Controllers/Mail.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Mail.php#L10-L40)
- **TARGET**:
```php
    public function index()
    {
        $a['data'] = $this->db->query("SELECT * FROM m_unitkerja")->getResult();
        $a['page'] = "sent_mail";
        return view('admin/index', $a);
    }

    public function send()
    {
        $email = $this->input->post('email');
        $body  = $this->input->post('body');

        $mail = new \PHPMailer();

        $mail->IsSMTP(); // we are going to use SMTP
        $mail->SMTPAuth   = true; // enabled SMTP authentication
        $mail->SMTPSecure = "ssl";  // prefix for secure protocol to connect to the server
        $mail->Host       = "smtp.gmail.com";      // setting GMail as our SMTP server
        $mail->Port       = 465;                   // SMTP port to connect to GMail
        $mail->Username   = "dda.jateng@gmail.com";  // user email address
        $mail->Password   = "dd4j4t3ng3300";            // password in GMail
```
- **REPLACEMENT**:
```php
    public function index()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
        $a['data'] = $this->db->query("SELECT * FROM m_unitkerja")->getResult();
        $a['page'] = "sent_mail";
        return view('admin/index', $a);
    }

    public function send()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
        $email = $this->input->post('email');
        $body  = $this->input->post('body');

        require_once APPPATH . 'Libraries/PHPMailer/class.phpmailer.php';
        require_once APPPATH . 'Libraries/PHPMailer/class.smtp.php';
        $mail = new \PHPMailer();

        $mail->IsSMTP();
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = "ssl";
        $mail->Host       = env('SMTP_HOST', 'ssl://smtp.bps.go.id');
        $mail->Port       = (int)env('SMTP_PORT', 465);
        $mail->Username   = env('SMTP_USER', '');
        $mail->Password   = env('SMTP_PASS', '');
```

---

### TASK-12: Perbaikan Integritas Data, Collation, Panjang Kolom & Index di Live Database (Memperbaiki P1-01, P1-05, P2-01, P2-02, P2-03) - SELESAI ✅

- **Instruksi untuk Gemini Flash 3.8**: Buat dan jalankan script PHP CLI sementara (atau gunakan `php -r`) untuk mengeksekusi query perbaikan database berikut secara aman:
```sql
-- 1. Perbaiki tabel orphan id=207 (kopertis6 -> lldikti) agar angka Dashboard (584) sama persis dengan tabel rincian OPD
UPDATE m_list_tabel SET id_unitkerja = 'lldikti' WHERE id = 207 AND id_unitkerja = 'kopertis6';

-- 2. Perbesar kolom password di t_admin menjadi VARCHAR(255) untuk mendukung Bcrypt
ALTER TABLE t_admin MODIFY COLUMN password VARCHAR(255) NOT NULL;

-- 3. Perbesar kolom id_unitkerja di m_list_tabel menjadi VARCHAR(50) agar tidak terpotong
ALTER TABLE m_list_tabel MODIFY COLUMN id_unitkerja VARCHAR(50) DEFAULT NULL;

-- 4. Bersihkan duplikat username 'imigrasi' di t_admin (pertahankan ID terkecil)
DELETE t1 FROM t_admin t1
INNER JOIN t_admin t2 
WHERE t1.id > t2.id AND t1.username = t2.username AND t1.username = 'imigrasi';

-- 5. Tambahkan Index performa pada tabel inti (jika belum ada)
ALTER TABLE m_list_tabel ADD INDEX idx_active_unitkerja (is_active, id_unitkerja);
ALTER TABLE m_list_tabel ADD INDEX idx_no_tabel (no_tabel);
ALTER TABLE t_admin ADD UNIQUE INDEX uk_admin_username (username);
ALTER TABLE t_admin ADD INDEX idx_admin_unitkerja (id_unitkerja);
ALTER TABLE forumcomment ADD INDEX idx_comment_topik (id_topik);
ALTER TABLE m_forumtopic ADD INDEX idx_topic_unitkerja (id_unitkerja);
```

---

### TASK-13: Hentikan Auto-Delete `m_indikator_tabel` & Hapus Fallback Lintas Tahun di `M_tabeldinamis::resolveTableContext()` (Memperbaiki P1-02 & P1-07) - SELESAI ✅

- **File**: [`app/Models/M_tabeldinamis.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L2374-L2415)
- **Penjelasan**:
  1. Di `resolveTableContext()`, jangan pernah memanggil `$this->where('id_tabel', $idMaster)->delete()` karena `ON DELETE CASCADE` akan menghapus seluruh histori `t_nilai_dinamis`! Cukup set `$indikator = null;` di memori jika `jenis_sumber === 'api_kominfo'`.
  2. Hapus blok fallback `if (!$statusRow) { ... orderBy('tahun', 'DESC') ... }` agar status tahun lama tidak bocor ke tahun baru yang belum diisi.
- **TARGET**:
```php
        // 2. Ambil konfigurasi m_indikator_tabel (1:1 dengan m_list_tabel.id)
        $indikator = $this->where('id_tabel', $idMaster)->first();
        if (($tabel->jenis_sumber ?? '') === 'api_kominfo') {
            if ($indikator) {
                // Bersihkan record indikator orphan jika tabel sudah diubah ke API Kominfo
                $this->where('id_tabel', $idMaster)->delete();
                $indikator = null;
            }
        }

        // Self-healing: jika karakteristik memiliki grup bertingkat (max_depth >= 2) namun tingkat_kolom di DB tersimpan '1',
        // pulihkan otomatis ke seluruh tingkat aktif ('1,2' dsb) agar tidak terjadi kolom buntung di tampilan
        if ($indikator && !empty($indikator->id_karakteristik_kelompok)) {
            $rawTingkat = trim((string)($indikator->tingkat_kolom ?? ''));
            if ($rawTingkat === '1') {
                $kItems = $this->getKarakteristikItems((int)$indikator->id_karakteristik_kelompok);
                $kStruct = $this->buildColumnStructure($kItems);
                if (!empty($kStruct['max_depth']) && (int)$kStruct['max_depth']) {
                    $allLevels = range(1, (int)$kStruct['max_depth']);
                    $indikator->tingkat_kolom = implode(',', $allLevels);
                    $this->update($indikator->id, ['tingkat_kolom' => $indikator->tingkat_kolom]);
                }
            }
        }

        // 3. Ambil status tahunan dari t_status_tahunan untuk tahun aktif (atau fallback tahun terbaru)
        $statusRow = null;
        if ($this->db->tableExists('t_status_tahunan')) {
            $statusRow = $this->db->table('t_status_tahunan')
                ->where('id_tabel', $idMaster)
                ->where('tahun', $tahunAktifInt)
                ->get()
                ->getRow();

            if (!$statusRow) {
                $statusRow = $this->db->table('t_status_tahunan')
                    ->where('id_tabel', $idMaster)
                    ->orderBy('tahun', 'DESC')
                    ->get()
                    ->getRow();
            }
        }
```
> *Catatan*: Perhatikan baris 2391 di file asli adalah `if (!empty($kStruct['max_depth']) && (int)$kStruct['max_depth'] >= 2) {`. Gunakan dua `edit_file` terpisah untuk bagian #2 dan bagian #3 di `M_tabeldinamis.php`:
- **TARGET 13A (`M_tabeldinamis.php` baris 2374–2382)**:
```php
        // 2. Ambil konfigurasi m_indikator_tabel (1:1 dengan m_list_tabel.id)
        $indikator = $this->where('id_tabel', $idMaster)->first();
        if (($tabel->jenis_sumber ?? '') === 'api_kominfo') {
            if ($indikator) {
                // Bersihkan record indikator orphan jika tabel sudah diubah ke API Kominfo
                $this->where('id_tabel', $idMaster)->delete();
                $indikator = null;
            }
        }
```
- **REPLACEMENT 13A**:
```php
        // 2. Ambil konfigurasi m_indikator_tabel (1:1 dengan m_list_tabel.id)
        $indikator = $this->where('id_tabel', $idMaster)->first();
        if (($tabel->jenis_sumber ?? '') === 'api_kominfo') {
            // Jangan hapus fisik m_indikator_tabel di fungsi read agar tidak memicu cascade delete t_nilai_dinamis
            $indikator = null;
        }
```
- **TARGET 13B (`M_tabeldinamis.php` baris 2399–2415)**:
```php
        // 3. Ambil status tahunan dari t_status_tahunan untuk tahun aktif (atau fallback tahun terbaru)
        $statusRow = null;
        if ($this->db->tableExists('t_status_tahunan')) {
            $statusRow = $this->db->table('t_status_tahunan')
                ->where('id_tabel', $idMaster)
                ->where('tahun', $tahunAktifInt)
                ->get()
                ->getRow();

            if (!$statusRow) {
                $statusRow = $this->db->table('t_status_tahunan')
                    ->where('id_tabel', $idMaster)
                    ->orderBy('tahun', 'DESC')
                    ->get()
                    ->getRow();
            }
        }
```
- **REPLACEMENT 13B**:
```php
        // 3. Ambil status tahunan dari t_status_tahunan khusus untuk tahun aktif (tanpa fallback lintas tahun)
        $statusRow = null;
        if ($this->db->tableExists('t_status_tahunan')) {
            $statusRow = $this->db->table('t_status_tahunan')
                ->where('id_tabel', $idMaster)
                ->where('tahun', $tahunAktifInt)
                ->get()
                ->getRow();
        }
```

---

### TASK-14: Sinkronkan Validasi Tahun API Portal Data (`$ta - 1`) pada `act_toggle_publish()` & `export_excel_instansi()` (Memperbaiki P1-03) - SELESAI ✅

#### Langkah 14.1 — Perbaiki `act_toggle_publish()` di `Admin.php`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2449-L2469)
- **TARGET**:
```php
			// 2. Syarat Pengisian: Harus sudah diisi (is_periksa == 1 atau tersinkron portal data)
			$is_portal = (stripos($statusTahunan->link_tabel ?? '', 'portal') !== false || 
			              stripos($statusTahunan->link_tabel ?? '', 'satudata') !== false);
			if ($is_dynamic) {
				$is_filled = false;
				if ($statusTahunan->is_periksa == 1) {
					$is_filled = true;
				} elseif ($ctx['indikator']) {
					$thDataCheck = (int)$ta - 1;
					$has_nilai = ($this->db->table('t_nilai_dinamis')
						->where('id_indikator', (int)$ctx['indikator']->id)
						->where('tahun_data', $thDataCheck)
						->countAllResults() > 0);
					if ($has_nilai) {
						$is_filled = true;
					}
				}
			} else {
				$is_filled = ($statusTahunan->is_periksa == 1 || $is_portal);
			}
```
- **REPLACEMENT**:
```php
			// 2. Syarat Pengisian: Harus sudah diisi pada tahun target ($ta - 1)
			$thDataCheck = (int)$ta - 1;
			$thDataStr = (string)$thDataCheck;
			$is_portal = (stripos($statusTahunan->link_tabel ?? '', 'portal') !== false || 
			              stripos($statusTahunan->link_tabel ?? '', 'satudata') !== false ||
			              !empty($ctx['api']->id_api));
			if ($is_dynamic) {
				$is_filled = false;
				if ($ctx['indikator']) {
					$has_nilai = ($this->db->table('t_nilai_dinamis')
						->where('id_indikator', (int)$ctx['indikator']->id)
						->where('tahun_data', $thDataCheck)
						->groupStart()
							->where('nilai IS NOT NULL')
							->orGroupStart()
								->where('simbol IS NOT NULL')
								->where('simbol !=', '')
							->groupEnd()
						->groupEnd()
						->countAllResults() > 0);
					if ($has_nilai) {
						$is_filled = true;
					}
				}
			} elseif ($is_portal) {
				$tahunTersedia = (string)($ctx['api']->tahun_tersedia ?? '');
				$is_filled = ($tahunTersedia !== '' && (in_array($thDataStr, array_map('trim', explode(',', $tahunTersedia)), true) || strpos($tahunTersedia, $thDataStr) !== false));
			} else {
				$is_filled = ($statusTahunan->is_periksa == 1);
			}
```

#### Langkah 14.2 — Perbaiki `export_excel_instansi()` di `Admin.php`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2530-L2566)
- **Penjelasan**: Samakan logika SQL di `export_excel_instansi()` dengan `export_excel_tim()` dan `M_kelolakegiatan::get_statistik_per_opd()`, **tetapi pertahankan nama alias kolom** (`unitkerja_ind`, `jumlah_tabel`, `belum_isi`, `menunggu_validasi`, `sudah_validasi`).
- **TARGET**:
```php
		$data = $this->db->query("
        SELECT 
            m.unitkerja_ind,
            COUNT(mt.id) as jumlah_tabel,
            SUM(CASE 
                WHEN (tt.is_confirm = '1') OR (
                    tt.is_periksa = 1 
                    OR nd.id_indikator IS NOT NULL 
                    OR (mt.jenis_sumber = 'api_kominfo' AND (COALESCE(ka.tahun_tersedia, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ?))
                ) THEN 0 
                ELSE 1 
            END) as belum_isi,
            SUM(CASE 
                WHEN (tt.is_confirm IS NULL OR tt.is_confirm != '1') AND (
                    tt.is_periksa = 1 
                    OR nd.id_indikator IS NOT NULL 
                    OR (mt.jenis_sumber = 'api_kominfo' AND (COALESCE(ka.tahun_tersedia, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ?))
                ) THEN 1 
                ELSE 0 
            END) as menunggu_validasi,
            SUM(CASE 
                WHEN tt.is_confirm = '1' THEN 1 
                ELSE 0 
            END) as sudah_validasi
        FROM m_list_tabel mt
        JOIN m_unitkerja m ON mt.id_unitkerja = m.id_unitkerja 
        LEFT JOIN t_status_tahunan tt ON tt.id_tabel = mt.id AND tt.tahun = ?
        LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
        LEFT JOIN (
            SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
        ) nd ON nd.id_indikator = mi.id
        LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
        WHERE mt.is_active = 1
        GROUP BY m.id_unitkerja 
        ORDER BY m.unitkerja_ind ASC
    ", ["%$thDataStr%", "%portal%", "%satudata%", "%$thDataStr%", "%portal%", "%satudata%", $thDda, $thData])->getResult();
```
- **REPLACEMENT**:
```php
		$data = $this->db->query("
        SELECT 
            m.unitkerja_ind,
            COUNT(mt.id) as jumlah_tabel,
            SUM(CASE 
                WHEN (tt.is_confirm = '1') OR (
                    CASE 
                        WHEN (mt.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
                            CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
                        WHEN mt.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
                            CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
                        WHEN tt.is_periksa = 1 THEN 1
                        ELSE 0
                    END = 1
                ) THEN 0 
                ELSE 1 
            END) as belum_isi,
            SUM(CASE 
                WHEN (tt.is_confirm IS NULL OR tt.is_confirm != '1') AND (
                    CASE 
                        WHEN (mt.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
                            CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
                        WHEN mt.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
                            CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
                        WHEN tt.is_periksa = 1 THEN 1
                        ELSE 0
                    END = 1
                ) THEN 1 
                ELSE 0 
            END) as menunggu_validasi,
            SUM(CASE 
                WHEN tt.is_confirm = '1' THEN 1 
                ELSE 0 
            END) as sudah_validasi
        FROM m_list_tabel mt
        JOIN m_unitkerja m ON mt.id_unitkerja = m.id_unitkerja 
        LEFT JOIN t_status_tahunan tt ON tt.id_tabel = mt.id AND tt.tahun = ?
        LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
        LEFT JOIN (
            SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
        ) nd ON nd.id_indikator = mi.id
        LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
        WHERE mt.is_active = 1
        GROUP BY m.id_unitkerja 
        ORDER BY m.unitkerja_ind ASC
    ", [$thDataStr, "%$thDataStr%", $thDataStr, "%$thDataStr%", $thDda, $thData])->getResult();
```

---

### TASK-15: Perbaiki Null Pointer Crash & Hardcoded SMTP di `Admin::forum_diskusi()` (Memperbaiki P1-04 & P0-01) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L966-L1050)
- **TARGET**:
```php
		} else if ($mau_ke == 'act_add_topik') {
			$hariini = date('Y-m-d');
			$nama_topik					= $this->input->post('nama_topik');
			$id_unitkerja				= $this->input->post('id_unitkerja');
			$user_id					= $this->session->get('admin_user');
			//$id_unitkerjatopik			= $this->input->get('id');
			$dataemail					= $this->db->query("select * from t_admin where id_unitkerja=?", [$id_unitkerja])->getRow();
			$emailtopik					= $dataemail->email;
			$namatopik					= $dataemail->nama;

			$data_lo				= $this->db->query("select * from m_unitkerja where id_unitkerja=?", [$id_unitkerja])->getRow();
			$lo						= $data_lo->user_wali;
			$dataemail_lo			= $this->db->query("select * from t_admin where username=?", [$lo])->getRow();
			$email_lo				= $dataemail_lo->email;

			//Setting Email
			$mail = new \PHPMailer();
			$mail->IsSMTP();
			$mail->SMTPAuth   = true;
			$mail->SMTPSecure = "ssl";
			$mail->Host       = "ssl://smtp.bps.go.id";
			$mail->Port       = 465;
			$mail->Username   = "ipds3300@bps.go.id";
			$mail->Password   = "oapeaa360";
			$mail->SetFrom('ipds3300@bps.go.id', 'Bidang IPDS BPS. Prov Jawa Tengah');
			$mail->Subject    = "Forum Diskusi Jawa Tengah Dalam Angka";

			$this->db->query("INSERT INTO m_forumtopic VALUES (NULL, ?, ?, ?, ?)", [$id_unitkerja, $nama_topik, $user_id, $hariini]);

			try {
				$mail->Body      = $nama_topik . " telah ditambahkan";
				$mail->AddAddress($emailtopik, $namatopik);
				$mail->AddCC($email_lo);
				$mail->Send();
			} catch (\Exception $e) {
				// Log error or ignore to keep flow fast
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Topik Telah Ditambahkan</div>");
			return redirect()->to('admin/forum_diskusi/' . $unitkerjalogin . '');
		} else if ($mau_ke == 'view_comment') {
			$id_unitkerja 				= $this->input->get('id_unitkerja');
			$id_topik					= $this->input->get('id_topik');
			$a['data']					= $this->db->query("SELECT * FROM forumcomment where id_topik=? LIMIT ?, ?", [$id_topik, (int)$awal, (int)$akhir])->getResult();
			$a['page']					= "l_forum_komen";
		} else if ($mau_ke == 'add_comment') {
			$hariini = date('Y-m-d');
			$comment					= $this->input->post('comment');
			$id_topik					= $this->input->post('id_topik');
			$id_unitkerja 				= $this->input->post('id_unitkerja');
			$dataemail					= $this->db->query("select * from t_admin where id_unitkerja=?", [$id_unitkerja])->getRow();
			$emailcomment				= $dataemail->email;
			$namacomment				= $dataemail->nama;

			$data_lo				= $this->db->query("select * from m_unitkerja where id_unitkerja=?", [$id_unitkerja])->getRow();
			$lo						= $data_lo->user_wali;
			$dataemail_lo			= $this->db->query("select * from t_admin where username=?", [$lo])->getRow();
			$email_lo				= $dataemail_lo->email;

			$user_id					= $this->session->get('admin_user');

			//Setting Email
			$mail = new \PHPMailer();
			$mail->IsSMTP();
			$mail->SMTPAuth   = true;
			$mail->SMTPSecure = "ssl";
			$mail->Host       = "ssl://smtp.bps.go.id";
			$mail->Port       = 465;
			$mail->Username   = "ipds3300@bps.go.id";
			$mail->Password   = "oapeaa360";
			$mail->SetFrom('ipds3300@bps.go.id', 'Bidang IPDS BPS. Prov Jawa Tengah');
			$mail->Subject    = "Forum Diskusi Jawa Tengah Dalam Angka";

			$this->db->query("INSERT INTO forumcomment VALUES (NULL, ?, ?, ?, ?)", [$id_topik, $comment, $user_id, $hariini]);

			try {
				$mail->Body      			= $comment . " telah ditambahkan";
				$mail->AddAddress($emailcomment, $namacomment);
				$mail->AddCC($email_lo);
				$mail->Send();
			} catch (\Exception $e) {
				// Log error or ignore
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Komentar Telah Ditambahkan</div>");
			return redirect()->to('admin/forum_diskusi/view_comment?id_unitkerja=' . $id_unitkerja . '&id_topik=' . $id_topik . '');
```
- **REPLACEMENT**:
```php
		} else if ($mau_ke == 'act_add_topik') {
			$hariini      = date('Y-m-d');
			$nama_topik   = trim((string)$this->input->post('nama_topik'));
			$id_unitkerja = ($unitkerjalogin == 'bps' || $this->session->get('admin_level') == 'kominfo')
				? $this->input->post('id_unitkerja')
				: $unitkerjalogin;
			$user_id      = $this->session->get('admin_user');

			$this->db->query("INSERT INTO m_forumtopic VALUES (NULL, ?, ?, ?, ?)", [$id_unitkerja, $nama_topik, $user_id, $hariini]);

			$dataemail    = $this->db->query("select * from t_admin where id_unitkerja=?", [$id_unitkerja])->getRow();
			$emailtopik   = $dataemail->email ?? '';
			$namatopik    = $dataemail->nama ?? '';

			$data_lo      = $this->db->query("select * from m_unitkerja where id_unitkerja=?", [$id_unitkerja])->getRow();
			$lo           = $data_lo->user_wali ?? '';
			$dataemail_lo = $lo !== '' ? $this->db->query("select * from t_admin where username=?", [$lo])->getRow() : null;
			$email_lo     = $dataemail_lo->email ?? '';

			if (!empty($emailtopik) && filter_var($emailtopik, FILTER_VALIDATE_EMAIL) && class_exists('\PHPMailer')) {
				try {
					$mail = new \PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth   = true;
					$mail->SMTPSecure = "ssl";
					$mail->Host       = env('SMTP_HOST', 'ssl://smtp.bps.go.id');
					$mail->Port       = (int)env('SMTP_PORT', 465);
					$mail->Username   = env('SMTP_USER', 'ipds3300@bps.go.id');
					$mail->Password   = env('SMTP_PASS', '');
					$mail->SetFrom(env('SMTP_USER', 'ipds3300@bps.go.id'), 'Bidang IPDS BPS. Prov Jawa Tengah');
					$mail->Subject    = "Forum Diskusi Jawa Tengah Dalam Angka";
					$mail->Body       = $nama_topik . " telah ditambahkan";
					$mail->AddAddress($emailtopik, $namatopik);
					if (!empty($email_lo) && filter_var($email_lo, FILTER_VALIDATE_EMAIL)) {
						$mail->AddCC($email_lo);
					}
					$mail->Send();
				} catch (\Throwable $e) {
					// Ignore email failure so forum post succeeds
				}
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Topik Telah Ditambahkan</div>");
			return redirect()->to('admin/forum_diskusi/view_topik?id=' . urlencode((string)$id_unitkerja));
		} else if ($mau_ke == 'view_comment') {
			$id_unitkerja = ($unitkerjalogin == 'bps' || $this->session->get('admin_level') == 'kominfo')
				? $this->input->get('id_unitkerja')
				: $unitkerjalogin;
			$id_topik     = (int)$this->input->get('id_topik');
			$topikRow     = $this->db->query("SELECT * FROM m_forumtopic WHERE id_topik = ? LIMIT 1", [$id_topik])->getRow();
			if (!$topikRow || ($unitkerjalogin != 'bps' && $this->session->get('admin_level') != 'kominfo' && $topikRow->id_unitkerja !== $unitkerjalogin)) {
				return redirect()->to('admin/forum_diskusi');
			}
			$a['data']    = $this->db->query("SELECT * FROM forumcomment where id_topik=? LIMIT ?, ?", [$id_topik, (int)$awal, (int)$akhir])->getResult();
			$a['page']    = "l_forum_komen";
		} else if ($mau_ke == 'add_comment') {
			$hariini      = date('Y-m-d');
			$comment      = trim((string)$this->input->post('comment'));
			$id_topik     = (int)$this->input->post('id_topik');
			$id_unitkerja = ($unitkerjalogin == 'bps' || $this->session->get('admin_level') == 'kominfo')
				? $this->input->post('id_unitkerja')
				: $unitkerjalogin;
			$user_id      = $this->session->get('admin_user');

			$this->db->query("INSERT INTO forumcomment VALUES (NULL, ?, ?, ?, ?)", [$id_topik, $comment, $user_id, $hariini]);

			$dataemail    = $this->db->query("select * from t_admin where id_unitkerja=?", [$id_unitkerja])->getRow();
			$emailcomment = $dataemail->email ?? '';
			$namacomment  = $dataemail->nama ?? '';

			$data_lo      = $this->db->query("select * from m_unitkerja where id_unitkerja=?", [$id_unitkerja])->getRow();
			$lo           = $data_lo->user_wali ?? '';
			$dataemail_lo = $lo !== '' ? $this->db->query("select * from t_admin where username=?", [$lo])->getRow() : null;
			$email_lo     = $dataemail_lo->email ?? '';

			if (!empty($emailcomment) && filter_var($emailcomment, FILTER_VALIDATE_EMAIL) && class_exists('\PHPMailer')) {
				try {
					$mail = new \PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth   = true;
					$mail->SMTPSecure = "ssl";
					$mail->Host       = env('SMTP_HOST', 'ssl://smtp.bps.go.id');
					$mail->Port       = (int)env('SMTP_PORT', 465);
					$mail->Username   = env('SMTP_USER', 'ipds3300@bps.go.id');
					$mail->Password   = env('SMTP_PASS', '');
					$mail->SetFrom(env('SMTP_USER', 'ipds3300@bps.go.id'), 'Bidang IPDS BPS. Prov Jawa Tengah');
					$mail->Subject    = "Forum Diskusi Jawa Tengah Dalam Angka";
					$mail->Body       = $comment . " telah ditambahkan";
					$mail->AddAddress($emailcomment, $namacomment);
					if (!empty($email_lo) && filter_var($email_lo, FILTER_VALIDATE_EMAIL)) {
						$mail->AddCC($email_lo);
					}
					$mail->Send();
				} catch (\Throwable $e) {
					// Ignore email failure
				}
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Komentar Telah Ditambahkan</div>");
			return redirect()->to('admin/forum_diskusi/view_comment?id_unitkerja=' . urlencode((string)$id_unitkerja) . '&id_topik=' . $id_topik);
```

---

### TASK-16: Perbaiki Validasi Panjang `id_unitkerja` pada `Admin::master_opd()` (Memperbaiki P1-05) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2209-L2211)
- **TARGET**:
```php
			if (strlen($id_unitkerja) < 3) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID Unitkerja minimal 4 huruf</div>");
				return redirect()->to('admin/master_opd');
```
- **REPLACEMENT**:
```php
			if (strlen($id_unitkerja) < 4) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID Unitkerja minimal 4 huruf</div>");
				return redirect()->to('admin/master_opd');
```

---

### TASK-17: Perbaiki Counter `$total_opd_count` pada `Admin::master_tim()` (Memperbaiki P1-06) - SELESAI ✅

- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L2837-L2840)
- **TARGET**:
```php
				$total_anggota_count += count($members);
				$spv_arr = array_unique(array_filter(array_map(function($o) {
```
- **REPLACEMENT**:
```php
				$total_anggota_count += count($members);
				$total_opd_count += count($opds);
				$spv_arr = array_unique(array_filter(array_map(function($o) {
```

---

### [x] TASK-18: Bungkus `resequence_tabel()` dengan Database Transaction & Hapus Dead Code `clone_tahunan()` (Memperbaiki P1-08) - SELESAI ✅

#### Langkah 18.1 — `clone_tahunan()` di `Admin.php`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3566-L3586)
- **TARGET**:
```php
		// Looping untuk menginisialisasi status operasional tahunan pada tahun tujuan
		$count_inserted = 0;
		$now = date('Y-m-d H:i:s');
		foreach ($data_sumber as $row) {
			$id_tabel = (int)$row['id'];
			
			// Jika status tahun tujuan belum ada, insert baris baru
			if (!isset($target_map[$id_tabel])) {
				$this->db->query("INSERT INTO t_status_tahunan 
					(id_tabel, tahun, is_periksa, catatan_periksa, is_confirm, catatan, is_publish, created_at, updated_at) 
					VALUES (?, ?, 0, '-', '2', '-', 0, ?, ?)", [
					$id_tabel,
					$tahun_tujuan,
					$now,
					$now
				]);
				$target_map[$id_tabel] = true;
				$count_inserted++;
			}
		}
```
- **REPLACEMENT**:
```php
		// Looping untuk menginisialisasi status operasional tahunan pada tahun tujuan
		$count_inserted = 0;
		$now = date('Y-m-d H:i:s');
		$this->db->transStart();
		foreach ($data_sumber as $row) {
			$id_tabel = (int)$row['id'];
			
			// Jika status tahun tujuan belum ada, insert baris baru
			if (!isset($target_map[$id_tabel])) {
				$this->db->query("INSERT INTO t_status_tahunan 
					(id_tabel, tahun, is_periksa, catatan_periksa, is_confirm, catatan, is_publish, created_at, updated_at) 
					VALUES (?, ?, 0, '-', '2', '-', 0, ?, ?)", [
					$id_tabel,
					$tahun_tujuan,
					$now,
					$now
				]);
				$target_map[$id_tabel] = true;
				$count_inserted++;
			}
		}
		$this->db->transComplete();
```

#### Langkah 18.2 — `resequence_tabel()` di `Admin.php`
- **File**: [`app/Controllers/Admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L3741-L3766)
- **TARGET**:
```php
		// Looping untuk memperbarui nomor tabel secara berurutan
		foreach ($tables as $t) {
			$curr_orig_no = trim((string)$t->no_tabel);

			// Jika tabel kolaborasi (memiliki nomor tabel asli yang sama persis),
			// pertahankan status nomor kembar dengan nomor yang sama seperti baris sebelumnya
			$is_twin = ($last_orig_no !== null && $curr_orig_no === $last_orig_no);

			if ($is_twin) {
				$new_no = $prefix . ($current_num - 1);
			} else {
				// Jika tabel independen/baru, gunakan nomor urut berikutnya
				$new_no = $prefix . $current_num;
				$current_num++;
			}
			
			// Update nomor tabel jika berbeda dengan nomor sebelumnya
			if ($curr_orig_no !== $new_no) {
				$this->db->query("UPDATE m_list_tabel SET no_tabel = ? WHERE id = ?", [$new_no, $t->id]);
				$this->db->query("UPDATE m_indikator_tabel SET no_tabel = ? WHERE id_tabel = ?", [$new_no, $t->id]);
				$updates++;
			}
			$last_orig_no = $curr_orig_no;
		}
```
- **REPLACEMENT**:
```php
		// Looping untuk memperbarui nomor tabel secara berurutan di dalam transaksi
		$this->db->transStart();
		foreach ($tables as $t) {
			$curr_orig_no = trim((string)$t->no_tabel);

			// Jika tabel kolaborasi (memiliki nomor tabel asli yang sama persis),
			// pertahankan status nomor kembar dengan nomor yang sama seperti baris sebelumnya
			$is_twin = ($last_orig_no !== null && $curr_orig_no === $last_orig_no);

			if ($is_twin) {
				$new_no = $prefix . ($current_num - 1);
			} else {
				// Jika tabel independen/baru, gunakan nomor urut berikutnya
				$new_no = $prefix . $current_num;
				$current_num++;
			}
			
			// Update nomor tabel jika berbeda dengan nomor sebelumnya
			if ($curr_orig_no !== $new_no) {
				$this->db->query("UPDATE m_list_tabel SET no_tabel = ? WHERE id = ?", [$new_no, $t->id]);
				$this->db->query("UPDATE m_indikator_tabel SET no_tabel = ? WHERE id_tabel = ?", [$new_no, $t->id]);
				$updates++;
			}
			$last_orig_no = $curr_orig_no;
		}
		$this->db->transComplete();
```

---

### [x] TASK-19: Hentikan Query Berulang `tr_instansi` pada Setiap Request di `BaseController.php` (Memperbaiki P1-09) - SELESAI ✅

- **File**: [`app/Controllers/BaseController.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/BaseController.php#L150-L160)
- **Penjelasan**: Karena tabel `tr_instansi` kosong (`null`), `if (!$instansi)` selalu bernilai `true` pada setiap HTTP request dan memicu query ke database cloud Aiven terus-menerus. Gunakan pengecekan `$this->session->has('cached_instansi_config')` agar meskipun hasilnya `null`, hasil tersebut tetap di-cache di session!
- **TARGET**:
```php
        $instansi = $this->session->get('cached_instansi_config');
        if (!$instansi) {
            $instansi = $this->db->query("SELECT * FROM tr_instansi LIMIT 1")->getRow();
            if ($instansi) {
                $this->session->set('cached_instansi_config', $instansi);
            }
        }
        
        // Share with all views globally
        $GLOBALS['instansi_config'] = $instansi; 
```
- **REPLACEMENT**:
```php
        if (!$this->session->has('cached_instansi_config')) {
            $instansi = $this->db->query("SELECT * FROM tr_instansi LIMIT 1")->getRow();
            $this->session->set('cached_instansi_config', $instansi ?: '__EMPTY__');
        } else {
            $cached = $this->session->get('cached_instansi_config');
            $instansi = ($cached === '__EMPTY__') ? null : $cached;
        }
        
        // Share with all views globally
        $GLOBALS['instansi_config'] = $instansi; 
```

---

### [x] TASK-20: Sinkronkan Query Menu Laporan Rekap (`view_report.php`) dengan Dashboard Real Data (Memperbaiki P1-10) - SELESAI ✅

- **File**: [`app/Views/admin/view_report.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/view_report.php#L12-L52) & baris 149–190
- **Penjelasan**: Jangan mengubah nama alias kolom (`unitkerja_ind`, `user_wali`, `jumlah_tabel`, `belum_isi`, `menunggu_validasi`, `sudah_validasi`), tetapi ganti ekspresi `CASE` di dalam query agar persis sama dengan logika `M_kelolakegiatan` (mengecek `nd.id_indikator` untuk tabel dinamis dan `FIND_IN_SET(?, ka.tahun_tersedia)` untuk API Portal Data pada `$thDataStr = $ta - 1`).

#### Langkah 20.1 — Tab Berdasarkan Instansi (`$jenis_rekap == '0'`) di `view_report.php`
- **TARGET**:
```php
    // Ambil data progres pengisian tabel berdasarkan instansi (m_list_tabel, m_unitkerja, t_status_tahunan, t_nilai_dinamis, m_konfigurasi_api)
    $data = \Config\Database::connect()->query("
        SELECT 
            m.unitkerja_ind,
            COUNT(mt.id) as jumlah_tabel,
            SUM(CASE 
                WHEN (tt.is_confirm = '1') OR (
                    tt.is_periksa = 1 
                    OR nd.id_indikator IS NOT NULL 
                    OR (mt.jenis_sumber = 'api_kominfo' AND (COALESCE(ka.tahun_tersedia, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ?))
                ) THEN 0 
                ELSE 1 
            END) as belum_isi,
            SUM(CASE 
                WHEN (tt.is_confirm IS NULL OR tt.is_confirm != '1') AND (
                    tt.is_periksa = 1 
                    OR nd.id_indikator IS NOT NULL 
                    OR (mt.jenis_sumber = 'api_kominfo' AND (COALESCE(ka.tahun_tersedia, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ?))
                ) THEN 1 
                ELSE 0 
            END) as menunggu_validasi,
            SUM(CASE 
                WHEN tt.is_confirm = '1' THEN 1 
                ELSE 0 
            END) as sudah_validasi
        FROM m_list_tabel mt
        JOIN m_unitkerja m ON mt.id_unitkerja = m.id_unitkerja 
        LEFT JOIN (
            SELECT id_tabel, MAX(is_confirm) as is_confirm, MAX(is_periksa) as is_periksa 
            FROM t_status_tahunan WHERE tahun = ? GROUP BY id_tabel
        ) tt ON tt.id_tabel = mt.id
        LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
        LEFT JOIN (
            SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
        ) nd ON nd.id_indikator = mi.id
        LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
        WHERE mt.is_active = 1
        GROUP BY m.id_unitkerja 
        ORDER BY m.unitkerja_ind ASC
    ", ["%$thDataStr%", "%portal%", "%satudata%", "%$thDataStr%", "%portal%", "%satudata%", $thDda, $thData])->getResult();
```
- **REPLACEMENT**:
```php
    // Ambil data progres pengisian tabel berdasarkan instansi (selaras 100% dengan Dashboard)
    $data = \Config\Database::connect()->query("
        SELECT 
            m.unitkerja_ind,
            COUNT(mt.id) as jumlah_tabel,
            SUM(CASE 
                WHEN (tt.is_confirm = '1') OR (
                    CASE 
                        WHEN (mt.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
                            CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
                        WHEN mt.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
                            CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
                        WHEN tt.is_periksa = 1 THEN 1
                        ELSE 0
                    END = 1
                ) THEN 0 
                ELSE 1 
            END) as belum_isi,
            SUM(CASE 
                WHEN (tt.is_confirm IS NULL OR tt.is_confirm != '1') AND (
                    CASE 
                        WHEN (mt.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
                            CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
                        WHEN mt.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
                            CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
                        WHEN tt.is_periksa = 1 THEN 1
                        ELSE 0
                    END = 1
                ) THEN 1 
                ELSE 0 
            END) as menunggu_validasi,
            SUM(CASE 
                WHEN tt.is_confirm = '1' THEN 1 
                ELSE 0 
            END) as sudah_validasi
        FROM m_list_tabel mt
        JOIN m_unitkerja m ON mt.id_unitkerja = m.id_unitkerja 
        LEFT JOIN (
            SELECT id_tabel, MAX(is_confirm) as is_confirm, MAX(is_periksa) as is_periksa 
            FROM t_status_tahunan WHERE tahun = ? GROUP BY id_tabel
        ) tt ON tt.id_tabel = mt.id
        LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
        LEFT JOIN (
            SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
        ) nd ON nd.id_indikator = mi.id
        LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
        WHERE mt.is_active = 1
        GROUP BY m.id_unitkerja 
        ORDER BY m.unitkerja_ind ASC
    ", [$thDataStr, "%$thDataStr%", $thDataStr, "%$thDataStr%", $thDda, $thData])->getResult();
```

#### Langkah 20.2 — Tab Berdasarkan Tim (`$jenis_rekap == '1'`) di `view_report.php`
- **TARGET**:
```php
    // Ambil data progres pengisian tabel berdasarkan tim
    $data = \Config\Database::connect()->query("
        SELECT 
            u.user_wali,
            COUNT(mt.id) as jumlah_tabel,
            SUM(CASE 
                WHEN (tt.is_confirm = '1') OR (
                    tt.is_periksa = 1 
                    OR nd.id_indikator IS NOT NULL 
                    OR (mt.jenis_sumber = 'api_kominfo' AND (COALESCE(ka.tahun_tersedia, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ?))
                ) THEN 0 
                ELSE 1 
            END) as belum_isi,
            SUM(CASE 
                WHEN (tt.is_confirm IS NULL OR tt.is_confirm != '1') AND (
                    tt.is_periksa = 1 
                    OR nd.id_indikator IS NOT NULL 
                    OR (mt.jenis_sumber = 'api_kominfo' AND (COALESCE(ka.tahun_tersedia, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ? OR COALESCE(ka.link_api, '') LIKE ?))
                ) THEN 1 
                ELSE 0 
            END) as menunggu_validasi,
            SUM(CASE 
                WHEN tt.is_confirm = '1' THEN 1 
                ELSE 0 
            END) as sudah_validasi
        FROM m_unitkerja u
        JOIN m_list_tabel mt ON u.id_unitkerja = mt.id_unitkerja 
        LEFT JOIN (
            SELECT id_tabel, MAX(is_confirm) as is_confirm, MAX(is_periksa) as is_periksa 
            FROM t_status_tahunan WHERE tahun = ? GROUP BY id_tabel
        ) tt ON tt.id_tabel = mt.id
        LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
        LEFT JOIN (
            SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
        ) nd ON nd.id_indikator = mi.id
        LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
        WHERE mt.is_active = 1
          AND u.user_wali IS NOT NULL AND u.user_wali != '' AND u.user_wali != '-'
        GROUP BY u.user_wali 
        ORDER BY u.user_wali ASC
    ", ["%$thDataStr%", "%portal%", "%satudata%", "%$thDataStr%", "%portal%", "%satudata%", $thDda, $thData])->getResult();
```
- **REPLACEMENT**:
```php
    // Ambil data progres pengisian tabel berdasarkan tim (selaras 100% dengan Dashboard)
    $data = \Config\Database::connect()->query("
        SELECT 
            u.user_wali,
            COUNT(mt.id) as jumlah_tabel,
            SUM(CASE 
                WHEN (tt.is_confirm = '1') OR (
                    CASE 
                        WHEN (mt.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
                            CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
                        WHEN mt.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
                            CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
                        WHEN tt.is_periksa = 1 THEN 1
                        ELSE 0
                    END = 1
                ) THEN 0 
                ELSE 1 
            END) as belum_isi,
            SUM(CASE 
                WHEN (tt.is_confirm IS NULL OR tt.is_confirm != '1') AND (
                    CASE 
                        WHEN (mt.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
                            CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
                        WHEN mt.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
                            CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
                        WHEN tt.is_periksa = 1 THEN 1
                        ELSE 0
                    END = 1
                ) THEN 1 
                ELSE 0 
            END) as menunggu_validasi,
            SUM(CASE 
                WHEN tt.is_confirm = '1' THEN 1 
                ELSE 0 
            END) as sudah_validasi
        FROM m_unitkerja u
        JOIN m_list_tabel mt ON u.id_unitkerja = mt.id_unitkerja 
        LEFT JOIN (
            SELECT id_tabel, MAX(is_confirm) as is_confirm, MAX(is_periksa) as is_periksa 
            FROM t_status_tahunan WHERE tahun = ? GROUP BY id_tabel
        ) tt ON tt.id_tabel = mt.id
        LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
        LEFT JOIN (
            SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
        ) nd ON nd.id_indikator = mi.id
        LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
        WHERE mt.is_active = 1
          AND u.user_wali IS NOT NULL AND u.user_wali != '' AND u.user_wali != '-'
        GROUP BY u.user_wali 
        ORDER BY u.user_wali ASC
    ", [$thDataStr, "%$thDataStr%", $thDataStr, "%$thDataStr%", $thDda, $thData])->getResult();
```

---

### [x] TASK-21: Perbaiki Logika Status Pengisian di `app/Views/admin/l_dda.php` (Memperbaiki P1-11) - SELESAI ✅

- **File**: [`app/Views/admin/l_dda.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_dda.php#L188-L194)
- **TARGET**:
```php
        $is_portal = (stripos($b->link_tabel, 'portal') !== false || 
                      stripos($b->link_tabel, 'satudata') !== false || 
                      (isset($b->id_api) && !empty($b->id_api)));
        $is_dynamic_uploaded = ($is_dynamic && !empty($b->catatan_periksa) && preg_match('/Diunggah(?:\s+via\s+Excel)?\s+oleh\s+(.+?)\s+pada\s+([^\r\n]+)/iu', $b->catatan_periksa, $m));
        $is_sudah_diisi = ($is_portal) || ($b->is_periksa == '1');
```
- **REPLACEMENT**:
```php
        $is_portal = (stripos($b->link_tabel, 'portal') !== false || 
                      stripos($b->link_tabel, 'satudata') !== false || 
                      (isset($b->id_api) && !empty($b->id_api)));
        $is_dynamic_uploaded = ($is_dynamic && !empty($b->catatan_periksa) && preg_match('/Diunggah(?:\s+via\s+Excel)?\s+oleh\s+(.+?)\s+pada\s+([^\r\n]+)/iu', $b->catatan_periksa, $m));
        $is_sudah_diisi = ($b->is_periksa == '1');
```

---

### [x] TASK-22: Hilangkan N+1 Query di `M_tabeldinamis::getPeriodeKelompokWithItems()` (Memperbaiki P2-06) - SELESAI ✅

- **File**: [`app/Models/M_tabeldinamis.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L1092-L1099)
- **TARGET**:
```php
    public function getPeriodeKelompokWithItems(): array
    {
        $kelompoks = $this->getPeriodeKelompokAll();
        foreach ($kelompoks as $k) {
            $k->items = $this->getPeriodeItems((int)$k->id);
        }
        return $kelompoks;
    }
```
- **REPLACEMENT**:
```php
    public function getPeriodeKelompokWithItems(): array
    {
        $kelompoks = $this->getPeriodeKelompokAll();
        if (empty($kelompoks)) {
            return [];
        }
        $ids = array_map(fn($k) => (int)$k->id, $kelompoks);
        $allItems = $this->db->table('m_periode_item')
            ->whereIn('id_kelompok', $ids)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
        $grouped = [];
        foreach ($allItems as $item) {
            $grouped[(int)$item->id_kelompok][] = $item;
        }
        foreach ($kelompoks as $k) {
            $k->items = $grouped[(int)$k->id] ?? [];
        }
        return $kelompoks;
    }
```

---

### [x] TASK-23: Amankan `my_helper.php` & `M_kelolakegiatan.php` dari SQL Injection serta Perbaiki Nama Class PSR-4 (Memperbaiki P2-07, P2-08, P3-06) - SELESAI ✅

#### Langkah 23.1 — `app/Helpers/my_helper.php`
- **File**: [`app/Helpers/my_helper.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Helpers/my_helper.php#L3-L16)
- **TARGET**:
```php
function gli($tabel, $field_kunci, $pad) {
	$db = \Config\Database::connect();
	$nama = $db->query("SELECT max($field_kunci) AS last FROM $tabel")->getRow();
	$data = (intval($nama->last)) + 1;
	$last = str_pad($data, $pad, '0', STR_PAD_LEFT);
	return $last;
}

function gval($tabel, $field_kunci, $diambil, $where) {
	$db = \Config\Database::connect();	
	$nama = $db->query("SELECT $diambil FROM $tabel WHERE $field_kunci = '$where'")->getRow();
	$data = empty($nama) ? "-" : $nama->$diambil;
	return $data;
}
```
- **REPLACEMENT**:
```php
function gli($tabel, $field_kunci, $pad) {
	$db = \Config\Database::connect();
	$tabel_clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabel);
	$field_clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$field_kunci);
	$nama = $db->query("SELECT MAX(`$field_clean`) AS last FROM `$tabel_clean`")->getRow();
	$data = (intval($nama->last ?? 0)) + 1;
	$last = str_pad((string)$data, (int)$pad, '0', STR_PAD_LEFT);
	return $last;
}

function gval($tabel, $field_kunci, $diambil, $where) {
	$db = \Config\Database::connect();
	$tabel_clean   = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabel);
	$field_clean   = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$field_kunci);
	$diambil_clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$diambil);
	$nama = $db->query("SELECT `$diambil_clean` FROM `$tabel_clean` WHERE `$field_clean` = ?", [$where])->getRow();
	$data = empty($nama) ? "-" : ($nama->$diambil_clean ?? "-");
	return $data;
}
```

#### Langkah 23.2 — `app/Models/M_kelolakegiatan.php` (Nama Class PSR-4 & Parameter Binding)
- **File**: [`app/Models/M_kelolakegiatan.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_kelolakegiatan.php#L8-L10) & baris 96–98
- **TARGET 23.2A (Baris 8)**:
```php
class m_kelolakegiatan extends Model{
```
- **REPLACEMENT 23.2A**:
```php
class M_kelolakegiatan extends Model{
```
- **TARGET 23.2B (Baris 96–98)**:
```php
	function cekDataEditLengkap($tahun, $kode_indikator){
           return $this->db->query("select d.*,k.nama_kab from t_data as d inner join m_kab as k on d.id_kab=k.id_kab inner join m_tahun as t on d.tahun=t.id where d.kode_indikator='$kode_indikator' and t.tahun='$tahun'");
    }
```
- **REPLACEMENT 23.2B**:
```php
	function cekDataEditLengkap($tahun, $kode_indikator){
           return $this->db->query("select d.*,k.nama_kab from t_data as d inner join m_kab as k on d.id_kab=k.id_kab inner join m_tahun as t on d.tahun=t.id where d.kode_indikator=? and t.tahun=?", [$kode_indikator, $tahun]);
    }
```

---

### [x] TASK-24: Perbaiki Duplikasi Input di `f_generator_tabel.php`, XSS di `l_manage_admin.php` & `l_unitkerja.php`, serta Hapus File `.bak` (Memperbaiki P3-07 & P3-08) - SELESAI ✅

#### Langkah 24.1 — Hapus duplikasi `<input type="hidden" name="id_tabel">` di `f_generator_tabel.php`
- **File**: [`app/Views/admin/f_generator_tabel.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/f_generator_tabel.php#L778-L783)
- **TARGET**:
```html
            <form action="<?= base_url('admin/save_generator_tabel'); ?>" method="post" id="formGenerator">
                <input type="hidden" name="id_tabel" value="<?= $tabel->id; ?>">
                        <input type="hidden" name="id_tahun_tabel" value="<?= $tabel->id; ?>">
                <input type="hidden" name="id_tabel" value="<?= $tabel->id_master_tabel ?? $tabel->id; ?>">
                <input type="hidden" name="posisi_hirarki" id="input_posisi_hirarki" value="periode_atas">
```
- **REPLACEMENT**:
```html
            <form action="<?= base_url('admin/save_generator_tabel'); ?>" method="post" id="formGenerator">
                <input type="hidden" name="id_tahun_tabel" value="<?= $tabel->id; ?>">
                <input type="hidden" name="id_tabel" id="input_id_tabel" value="<?= $tabel->id_master_tabel ?? $tabel->id; ?>">
                <input type="hidden" name="posisi_hirarki" id="input_posisi_hirarki" value="periode_atas">
```

#### Langkah 24.2 — Escape Output di `app/Views/admin/l_manage_admin.php`
- **File**: [`app/Views/admin/l_manage_admin.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_manage_admin.php#L57-L68)
- **TARGET**:
```php
                                <div class="d-flex flex-column">
                                    <h6 class="mb-0 text-sm font-weight-bold text-dark"><?php echo $b->username; ?></h6>
                                    <p class="text-xxs text-secondary mb-0"><?php echo $b->email ?: '-'; ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-sm font-weight-bold text-dark lh-sm"><?php echo $b->nama; ?></span>
                                <span class="text-xxs text-secondary mt-1 font-italic">NIP: <?php echo $b->nip ?: '-'; ?></span>
                            </div>
```
- **REPLACEMENT**:
```php
                                <div class="d-flex flex-column">
                                    <h6 class="mb-0 text-sm font-weight-bold text-dark"><?php echo esc($b->username); ?></h6>
                                    <p class="text-xxs text-secondary mb-0"><?php echo esc($b->email ?: '-'); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-sm font-weight-bold text-dark lh-sm"><?php echo esc($b->nama); ?></span>
                                <span class="text-xxs text-secondary mt-1 font-italic">NIP: <?php echo esc($b->nip ?: '-'); ?></span>
                            </div>
```

#### Langkah 24.3 — Escape Output & JS String di `app/Views/admin/l_unitkerja.php`
- **File**: [`app/Views/admin/l_unitkerja.php`](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_unitkerja.php#L50-L83)
- **TARGET**:
```php
                        <td class="text-center">
                            <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold px-3 py-2 border-radius-md border border-light"><?php echo $b->id_unitkerja; ?></span>
                        </td>
                        <td class="py-3">
                            <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="line-height: 1.5;"><?php echo $b->unitkerja_ind; ?></h6>
                        </td>
                        <td>
                            <p class="text-xs text-secondary mb-0 font-italic text-wrap" style="line-height: 1.4;"><?php echo $b->unitkerja_en; ?></p>
                        </td>
                        <td class="text-center">
                            <?php if ($isOpd): ?>
                                <span class="badge badge-sm bg-success-subtle text-success border border-success-subtle font-weight-bold px-2 py-1 border-radius-md" title="Organisasi Perangkat Daerah (Read-Only)">
                                    <i class="bi bi-bank me-1"></i> OPD Pemprov
                                </span>
                            <?php else: ?>
                                <span class="badge badge-sm bg-info-subtle text-info border border-info-subtle font-weight-bold px-2 py-1 border-radius-md" title="Organisasi Vertikal (Upload Excel Mandiri)">
                                    <i class="bi bi-building me-1"></i> Vertikal
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-primary-soft text-primary font-weight-bold px-3 py-2 border-radius-md border-primary-soft">
                                <i class="bi bi-person-badge me-1"></i><?php echo strtoupper($b->user_wali); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?php echo base_URL(); ?>index.php/admin/master_opd/edt/<?php echo $b->id_unitkerja; ?>" class="btn btn-icon-only btn-outline-primary btn-sm border-radius-lg shadow-none" title="Edit OPD">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo base_URL()?>index.php/admin/master_opd/del/<?php echo $b->id_unitkerja;?>" class="btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" title="Hapus OPD" onclick="return confirm('Anda Yakin Akan Menghapus OPD <?php echo $b->unitkerja_ind;?> (<?php echo $b->id_unitkerja;?>) ?')">
```
- **REPLACEMENT**:
```php
                        <td class="text-center">
                            <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold px-3 py-2 border-radius-md border border-light"><?php echo esc($b->id_unitkerja); ?></span>
                        </td>
                        <td class="py-3">
                            <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="line-height: 1.5;"><?php echo esc($b->unitkerja_ind); ?></h6>
                        </td>
                        <td>
                            <p class="text-xs text-secondary mb-0 font-italic text-wrap" style="line-height: 1.4;"><?php echo esc($b->unitkerja_en); ?></p>
                        </td>
                        <td class="text-center">
                            <?php if ($isOpd): ?>
                                <span class="badge badge-sm bg-success-subtle text-success border border-success-subtle font-weight-bold px-2 py-1 border-radius-md" title="Organisasi Perangkat Daerah (Read-Only)">
                                    <i class="bi bi-bank me-1"></i> OPD Pemprov
                                </span>
                            <?php else: ?>
                                <span class="badge badge-sm bg-info-subtle text-info border border-info-subtle font-weight-bold px-2 py-1 border-radius-md" title="Organisasi Vertikal (Upload Excel Mandiri)">
                                    <i class="bi bi-building me-1"></i> Vertikal
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-primary-soft text-primary font-weight-bold px-3 py-2 border-radius-md border-primary-soft">
                                <i class="bi bi-person-badge me-1"></i><?php echo esc(strtoupper((string)$b->user_wali)); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?php echo base_URL(); ?>index.php/admin/master_opd/edt/<?php echo esc($b->id_unitkerja, 'url'); ?>" class="btn btn-icon-only btn-outline-primary btn-sm border-radius-lg shadow-none" title="Edit OPD">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo base_URL()?>index.php/admin/master_opd/del/<?php echo esc($b->id_unitkerja, 'url');?>" class="btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" title="Hapus OPD" onclick="return confirm('Anda Yakin Akan Menghapus OPD <?php echo esc($b->unitkerja_ind, 'js');?> (<?php echo esc($b->id_unitkerja, 'js');?>) ?')">
```

#### Langkah 24.4 — Hapus File Backup Controller yang Tidak Terpakai
- Hapus file `app/Controllers/Admin.php.bak` dan `app/Controllers/Admin.php.bak2` menggunakan perintah shell `Remove-Item`.

---

## Checklist Verifikasi Pasca-Eksekusi (Wajib Dijalankan oleh Gemini Flash 3.8)

Setelah semua Task selesai dieksekusi, jalankan perintah pemeriksaan sintaks PHP berikut di terminal:
```powershell
php -l app/Controllers/Admin.php
php -l app/Controllers/BaseController.php
php -l app/Controllers/Mail.php
php -l app/Models/M_tabeldinamis.php
php -l app/Models/M_kelolakegiatan.php
php -l app/Helpers/my_helper.php
php -l app/Config/Filters.php
php -l app/Views/admin/view_report.php
php -l app/Views/admin/l_forum_topik.php
php -l app/Views/admin/l_forum_komen.php
php -l app/Views/admin/l_dda.php
php -l app/Views/admin/f_generator_tabel.php
php -l app/Views/admin/l_manage_admin.php
php -l app/Views/admin/l_unitkerja.php
```
Pastikan seluruh output menampilkan **`No syntax errors detected`**.
