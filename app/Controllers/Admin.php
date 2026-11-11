<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;



class Admin extends BaseController
{
	public $m_kelolakegiatan;
	public $web_model;
	public $upload;

	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		parent::initController($request, $response, $logger);
		date_default_timezone_set('Asia/Jakarta');
		//$this->load->library('encryption'); //in controller
		$this->load->library('MyPHPMailer');
		$this->load->helper('url');
	}

	public function index()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$ta = $this->session->get('admin_ta');
		if (empty($ta)) {
			$ta = date('Y');
		}

		$unitkerjalogin = $this->session->get('admin_unitkerja');
		$user_level     = strtolower(trim((string)$this->session->get('admin_level')));
		$user_id        = (int)$this->session->get('admin_id');
		$username       = $this->session->get('admin_user');

		$filter_unitkerja = null;
		$filter_teams     = null;
		$user_role_scope  = 'admin';
		$scope_label      = 'Provinsi Jawa Tengah';

		// 1. Walidata (OPD di luar BPS / bukan level admin/kominfo)
		if ($unitkerjalogin != 'bps' && !in_array($user_level, ['admin', 'super admin', 'superadmin', 'kominfo'])) {
			$user_role_scope  = 'walidata';
			$filter_unitkerja = [$unitkerjalogin];
			$filter_teams     = ['__NONE__'];
			$unit_row = $this->db->table('m_unitkerja')->where('id_unitkerja', $unitkerjalogin)->get()->getRow();
			$scope_label = $unit_row->unitkerja_ind ?? strtoupper((string)$unitkerjalogin);
		}
		// 2. LO (Liaison Officer BPS)
		else if ($user_level == 'lo') {
			$user_role_scope = 'lo';
			$user_teams = $this->db->query("SELECT kode_tim FROM t_tim_anggota WHERE id_admin = ?", [$user_id])->getResultArray();
			$team_codes = array_filter(array_column($user_teams, 'kode_tim'));
			if (!empty($username) && !in_array($username, $team_codes)) {
				$is_wali = $this->db->query("SELECT 1 FROM m_unitkerja WHERE user_wali = ? LIMIT 1", [$username])->getNumRows();
				if ($is_wali > 0) {
					$team_codes[] = $username;
				}
			}

			if (!empty($team_codes)) {
				$filter_teams = $team_codes;
				$in_clause = implode(',', array_fill(0, count($team_codes), '?'));
				$opd_binaan = $this->db->query("SELECT id_unitkerja FROM m_unitkerja WHERE user_wali IN ($in_clause)", $team_codes)->getResultArray();
				$filter_unitkerja = !empty($opd_binaan) ? array_column($opd_binaan, 'id_unitkerja') : ['__NONE__'];
				$scope_label = 'OPD Binaan Tim ' . strtoupper(implode(', ', $team_codes));
			} else {
				$filter_unitkerja = ['__NONE__'];
				$filter_teams     = ['__NONE__'];
				$scope_label      = 'Belum memiliki tim binaan';
			}
		}
		// 3. Supervisor (SPV BPS)
		else if ($user_level == 'spv') {
			$user_role_scope = 'spv';
			$opd_spv = $this->db->query("SELECT id_unitkerja FROM m_unitkerja WHERE user_spv = ?", [$username])->getResultArray();
			$filter_unitkerja = !empty($opd_spv) ? array_column($opd_spv, 'id_unitkerja') : ['__NONE__'];
			$filter_teams     = null;
			$scope_label      = 'OPD Binaan SPV ' . $username;
		}
		// 4. Admin / Super Admin / Kominfo (Default: Akses Seluruh Data)
		else {
			$user_role_scope  = 'admin';
			$filter_unitkerja = null;
			$filter_teams     = null;
			$scope_label      = 'Provinsi Jawa Tengah';
		}

		$this->load->model('m_kelolakegiatan');
		$stats = $this->m_kelolakegiatan->get_statistik_dashboard($ta, $filter_unitkerja);

		$a['stat_diisi']         = (int) ($stats->diisi ?? 0);
		$a['stat_belum_diisi']   = (int) ($stats->belum_diisi ?? 0);
		$a['stat_sudah_acc']     = (int) ($stats->sudah_acc ?? 0);
		$a['stat_belum_acc']     = (int) ($stats->belum_acc ?? 0);
		$a['stat_diisi_waiting'] = (int) ($stats->diisi_waiting ?? 0);
		$a['stat_total']         = (int) ($stats->total_tabel ?? 0);

		$data_opd = $this->m_kelolakegiatan->get_statistik_per_opd($ta, $filter_unitkerja);

		$nama_opd = array();
		$opd_acc = array();
		$opd_menunggu = array();
		$opd_belum = array();

		if (!empty($data_opd)) {
			foreach ($data_opd as $row) {
				$nama_opd[]     = $row->nama_opd;
				$opd_acc[]      = (int) $row->total_acc;
				$opd_menunggu[] = (int) $row->total_menunggu;
				$opd_belum[]    = (int) $row->total_belum;
			}
		}

		$a['grafik_opd_nama']     = json_encode($nama_opd);
		$a['grafik_opd_acc']      = json_encode($opd_acc);
		$a['grafik_opd_menunggu'] = json_encode($opd_menunggu);
		$a['grafik_opd_belum']    = json_encode($opd_belum);

		$data_tim = ($user_role_scope === 'walidata') ? [] : $this->m_kelolakegiatan->get_statistik_per_tim($ta, $filter_teams);

		$nama_tim = array();
		$tim_acc = array();
		$tim_menunggu = array();
		$tim_belum = array();

		if (!empty($data_tim)) {
			foreach ($data_tim as $row) {
				$nama_tim[]     = strtoupper($row->nama_tim);
				$tim_acc[]      = (int) $row->total_acc;
				$tim_menunggu[] = (int) $row->total_menunggu;
				$tim_belum[]    = (int) $row->total_belum;
			}
		}
		$a['grafik_tim_nama']     = json_encode($nama_tim);
		$a['grafik_tim_acc']      = json_encode($tim_acc);
		$a['grafik_tim_menunggu'] = json_encode($tim_menunggu);
		$a['grafik_tim_belum']    = json_encode($tim_belum);

		$a['user_role_scope']     = $user_role_scope;
		$a['scope_label']         = $scope_label;
		$a['is_bps']              = ($unitkerjalogin == 'bps');

		$a['page']	= "d_amain";
		return view('admin/index', $a);
	}

	public function kalender()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$a['page']	= "f_kalender";

		return view('admin/index', $a);
	}

	public function pengguna()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('pengguna')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Menu Instansi Pengguna hanya untuk Super Admin.</div>');
			return redirect()->to('admin');
		}


		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);

		//ambil variabel Postingan
		$idp					= $this->input->post('idp');
		$nama					= $this->input->post('nama');
		$alamat					= $this->input->post('alamat');
		$kepsek					= $this->input->post('kepsek');
		$nip_kepsek				= $this->input->post('nip_kepsek');

		$cari					= $this->input->post('q');

		//upload config 
		$config['upload_path'] 		= './upload';
		$config['allowed_types'] 	= 'gif|jpg|png|pdf|doc|docx';
		$config['max_size']			= '2000';
		$config['max_width']  		= '3000';
		$config['max_height'] 		= '3000';

		$this->load->library('upload', $config);

		if ($mau_ke == "act_edt") {
			if ($this->upload->do_upload('logo')) {
				$up_data	 	= $this->upload->data();

				$this->db->query("UPDATE tr_instansi SET nama = ?, alamat = ?, kepsek = ?, nip_kepsek = ?, logo = ? WHERE id = ?", [$nama, $alamat, $kepsek, $nip_kepsek, $up_data['file_name'], $idp]);
			} else {
				$this->db->query("UPDATE tr_instansi SET nama = ?, alamat = ?, kepsek = ?, nip_kepsek = ? WHERE id = ?", [$nama, $alamat, $kepsek, $nip_kepsek, $idp]);
			}

			$this->session->remove('cached_instansi_config');
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated</div>");
			return redirect()->to('admin/pengguna');
		} else {
			$a['data']		= $this->db->query("SELECT * FROM tr_instansi WHERE id = '1' LIMIT 1")->getRow();
			$a['page']		= "f_pengguna";
		}

		return view('admin/index', $a);
	}

	public function manage_admin()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('manage_admin')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Menu Manajemen Admin hanya untuk Super Admin.</div>');
			return redirect()->to('admin');
		}


		/* pagination dinonaktifkan: seluruh data admin ditampilkan dan di-scroll di dalam kontainer */
		$a['pagi'] = '';

		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);
		$idu					= $this->uri->segment(4);

		$cari					= $this->input->post('q');

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
		} else {
			$a['data']		= $this->db->query("SELECT * FROM t_admin ORDER BY id ASC")->getResult();
			$a['page']		= "l_manage_admin";
		}

		return view('admin/index', $a);
	}


	public function profil()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


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
		} else {
			$a['datpil']	= $this->db->query("SELECT * FROM t_admin WHERE id = ?", [$idu])->getRow();
			$a['page']		= "f_profil";
		}

		return view('admin/index', $a);
	}

	public function passwod()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$ke				= $this->uri->segment(3);
		$id_user		= $this->session->get('admin_id');

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

	public function logout()
	{
		session()->destroy();
		return redirect()->to('admin/login');
	}







	//dda
	public function dda()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		$ta = $this->session->get('admin_ta');
		if (empty($ta)) {
			$ta = date('Y');
		}

		$thDda = (string)$ta;
		$thData = ((int)$ta) - 1;
		$thDataStr = (string)$thData;

		/* pagination */
		$total_row		= (int)($this->db->query("SELECT COUNT(*) as total FROM m_list_tabel WHERE is_active = 1")->getRow()->total ?? 0);
		$per_page		= 150;

		$awal	= $this->uri->segment(4);
		$awal	= (empty($awal) || $awal == 1) ? 0 : $awal;

		if (empty($awal) || $awal == 1) {
			$awal = 0;
		}
		$akhir	= $per_page;

		$a['pagi']	= _page($total_row, $per_page, 4, base_url() . "admin/dda/p");

		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);
		$idu					= $this->uri->segment(4);
		$cari					= addslashes($this->input->post('q') ?? '');

		//ambil variabel post

		$idp					= $this->input->post('idp');
		$nip					= $this->input->post('nip');
		//$tgl					= $this->input->post('tgl');
		//$jam_keluar			= $this->input->post('jam_keluar');
		//$jam_masuk			= $this->input->post('jam_masuk');
		$status					= $this->input->post('status');
		$kepentingan_kel		= $this->input->post('kepentingan_kel');
		$kepentingan_uraian		= $this->input->post('kepentingan_uraian');

		$cari					= $this->input->post('q');
		$nippegawai = $this->session->get('admin_nip');

		//upload config 
		$config['upload_path'] 		= './upload/surat_masuk';
		$config['allowed_types'] 	= 'gif|jpg|png|pdf|doc|docx';
		$config['max_size']			= '2000';
		$config['max_width']  		= '3000';
		$config['max_height'] 		= '3000';

		$this->load->library('upload', $config);

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
			$id_unitkerja = $this->input->get('id');
			$unit_row = $this->db->table('m_unitkerja')->where('id_unitkerja', $id_unitkerja)->get()->getRow();
			$a['nama_unitkerja']   = $unit_row->unitkerja_ind ?? strtoupper((string)$id_unitkerja);
			$a['target_unitkerja'] = $id_unitkerja;
			$a['is_bps']           = ($this->session->get('admin_unitkerja') == 'bps');
			$a['ta']               = $ta;
			$a['data'] = $this->db->query("
				SELECT 
					m.id as id,
					m.id as id_master,
					m.no_tabel as no_tabel,
					m.judul_ind, m.judul_en, m.kondef, m.id_unitkerja, m.jenis_sumber,
					m.id as id_tahun_tabel,
					COALESCE(t.tahun, ?) as tahun,
					COALESCE(t.is_confirm, '0') as is_confirm,
					COALESCE(t.is_publish, 0) as is_publish,
					CASE 
						WHEN (m.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
							CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
						WHEN m.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
							CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
						WHEN t.is_periksa = 1 THEN 1
						ELSE 0
					END as is_periksa,
					t.catatan_periksa, t.catatan,
					COALESCE(ka.link_api, '') as link_tabel,
					CASE 
						WHEN m.jenis_sumber = 'api_kominfo' THEN 0 
						WHEN m.jenis_sumber = 'dinamis' THEN 1 
						WHEN mi.id IS NOT NULL THEN 1 
						ELSE 0 
					END as is_dynamic,
					ka.id_api,
					ka.tahun_tersedia
				FROM m_list_tabel m
				LEFT JOIN t_status_tahunan t ON m.id = t.id_tabel AND t.tahun = ?
				LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = m.id
				LEFT JOIN (
					SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
				) nd ON nd.id_indikator = mi.id
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
				WHERE m.id_unitkerja = ? AND m.is_active = 1
				ORDER BY m.no_tabel ASC, m.id ASC
			", [$thDda, $thDataStr, "%$thDataStr%", $thDda, $thData, $id_unitkerja])->getResult();
			$a['page'] = "l_dda_periksa";
		} else if ($mau_ke == "periksa_tabel") {
			$id_unitkerja = $this->input->get('id');
			$unit_row = $this->db->table('m_unitkerja')->where('id_unitkerja', $id_unitkerja)->get()->getRow();
			$a['nama_unitkerja']   = $unit_row->unitkerja_ind ?? strtoupper((string)$id_unitkerja);
			$a['target_unitkerja'] = $id_unitkerja;
			$a['is_bps']           = ($this->session->get('admin_unitkerja') == 'bps');
			$a['ta']               = $ta;
			$a['data'] = $this->db->query("
				SELECT 
					m.id as id,
					m.id as id_master,
					m.no_tabel as no_tabel,
					m.judul_ind, m.judul_en, m.kondef, m.id_unitkerja, m.jenis_sumber,
					m.id as id_tahun_tabel,
					COALESCE(t.tahun, ?) as tahun,
					COALESCE(t.is_confirm, '0') as is_confirm,
					COALESCE(t.is_publish, 0) as is_publish,
					CASE 
						WHEN (m.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
							CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
						WHEN m.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
							CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
						WHEN t.is_periksa = 1 THEN 1
						ELSE 0
					END as is_periksa,
					t.catatan_periksa, t.catatan,
					COALESCE(ka.link_api, '') as link_tabel,
					CASE 
						WHEN m.jenis_sumber = 'api_kominfo' THEN 0 
						WHEN m.jenis_sumber = 'dinamis' THEN 1 
						WHEN mi.id IS NOT NULL THEN 1 
						ELSE 0 
					END as is_dynamic,
					ka.id_api,
					ka.tahun_tersedia
				FROM m_list_tabel m
				LEFT JOIN t_status_tahunan t ON m.id = t.id_tabel AND t.tahun = ?
				LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = m.id
				LEFT JOIN (
					SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
				) nd ON nd.id_indikator = mi.id
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
				WHERE m.id_unitkerja = ? AND m.is_active = 1
				ORDER BY m.no_tabel ASC, m.id ASC
				LIMIT ?, ?
			", [$thDda, $thDataStr, "%$thDataStr%", $thDda, $thData, $id_unitkerja, (int)$awal, (int)$akhir])->getResult();
			$a['page'] = "l_dda_periksa";
		} else {
			$unitkerjalogin = $this->session->get('admin_unitkerja');

			if ($unitkerjalogin == 'bps') {
				/* pagination */
				$total_row		= (int)($this->db->query("SELECT COUNT(*) as total FROM m_unitkerja")->getRow()->total ?? 0);
				$per_page		= 150;

				$awal	= $this->uri->segment(4);
				$awal	= (empty($awal) || $awal == 1) ? 0 : $awal;

				if (empty($awal) || $awal == 1) {
					$awal = 0;
				}
				$akhir	= $per_page;

				$a['pagi']	= _page($total_row, $per_page, 4, base_url() . "admin/dda/p");

				$level = $this->session->get('admin_level');
				if ($level == 'Admin' || $level == 'Super Admin') {
					if ($this->request->getGet('action') === 'reset_dda') {
						$this->session->remove('sess_dda_filter_jenis');
					}

					$filter_jenis = $this->request->getGet('filter_jenis');
					if ($filter_jenis !== null) {
						$this->session->set('sess_dda_filter_jenis', $filter_jenis);
					} else {
						$filter_jenis = $this->session->get('sess_dda_filter_jenis') ?? 'all';
					}
					$a['selected_jenis'] = $filter_jenis;

					$where_sql = "";
					$params = [];
					if ($filter_jenis !== 'all' && in_array($filter_jenis, ['opd', 'vertikal'])) {
						$where_sql = "WHERE jenis_instansi = ?";
						$params[] = $filter_jenis;
					}

					$count_sql = "SELECT COUNT(*) as total FROM m_unitkerja $where_sql";
					$total_row = (int)($this->db->query($count_sql, $params)->getRow()->total ?? 0);
					$a['pagi'] = _page($total_row, $per_page, 4, base_url() . "admin/dda/p");

					$params[] = (int)$awal;
					$params[] = (int)$akhir;
					$a['data'] = $this->db->query("SELECT * FROM m_unitkerja $where_sql ORDER BY unitkerja_ind LIMIT ?, ?", $params)->getResult();
				} else {
					$user_wali = $this->session->get('admin_user');
					$user_level = strtolower(trim((string)$this->session->get('admin_level')));
					if ($user_level == 'lo') {
						$user_id = (int)$this->session->get('admin_id');
						$user_teams = $this->db->query("SELECT kode_tim FROM t_tim_anggota WHERE id_admin = ?", [$user_id])->getResultArray();
						$team_codes = array_filter(array_column($user_teams, 'kode_tim'));
						if (!empty($user_wali) && !in_array($user_wali, $team_codes)) {
							$team_codes[] = $user_wali;
						}
						if (!empty($team_codes)) {
							$in_clause = implode(',', array_fill(0, count($team_codes), '?'));
							$a['data'] = $this->db->query("SELECT * FROM m_unitkerja WHERE user_wali IN ($in_clause) ORDER BY unitkerja_ind ASC", $team_codes)->getResult();
						} else {
							$a['data'] = [];
						}
					} else if ($user_level == 'spv') {
						$a['data'] = $this->db->query("SELECT * FROM m_unitkerja where user_spv=? ORDER BY unitkerja_ind ASC", [$user_wali])->getResult();
					}
				}

				$a['page']		= "l_dda_admin";
			} else {
				$unit_row = $this->db->table('m_unitkerja')->where('id_unitkerja', $unitkerjalogin)->get()->getRow();
				$a['nama_unitkerja']   = $unit_row->unitkerja_ind ?? strtoupper((string)$unitkerjalogin);
				$a['target_unitkerja'] = $unitkerjalogin;
				$a['is_bps']           = false;
				$a['ta']               = $ta;
				$a['data'] = $this->db->query("
					SELECT 
						m.id as id,
						m.id as id_master,
						m.no_tabel as no_tabel,
						m.judul_ind, m.judul_en, m.kondef, m.id_unitkerja, m.jenis_sumber,
						m.id as id_tahun_tabel,
						COALESCE(t.tahun, ?) as tahun,
						COALESCE(t.is_confirm, '0') as is_confirm,
						COALESCE(t.is_publish, 0) as is_publish,
						CASE 
							WHEN (m.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL) THEN 
								CASE WHEN nd.id_indikator IS NOT NULL THEN 1 ELSE 0 END
							WHEN m.jenis_sumber = 'api_kominfo' AND (ka.link_api LIKE '%view_portal_tabel%' OR (ka.id_api IS NOT NULL AND ka.id_api != '')) THEN
								CASE WHEN ka.tahun_tersedia IS NOT NULL AND ka.tahun_tersedia != '' AND (FIND_IN_SET(?, ka.tahun_tersedia) > 0 OR ka.tahun_tersedia LIKE ?) THEN 1 ELSE 0 END
							WHEN t.is_periksa = 1 THEN 1
							ELSE 0
						END as is_periksa,
						t.catatan_periksa, t.catatan,
						COALESCE(ka.link_api, '') as link_tabel,
						CASE 
						WHEN m.jenis_sumber = 'api_kominfo' THEN 0 
						WHEN m.jenis_sumber = 'dinamis' THEN 1 
						WHEN mi.id IS NOT NULL THEN 1 
						ELSE 0 
					END as is_dynamic,
						ka.id_api,
						ka.tahun_tersedia
					FROM m_list_tabel m
					LEFT JOIN t_status_tahunan t ON m.id = t.id_tabel AND t.tahun = ?
					LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = m.id
					LEFT JOIN (
						SELECT DISTINCT id_indikator FROM t_nilai_dinamis WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
					) nd ON nd.id_indikator = mi.id
					LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
					WHERE m.id_unitkerja = ? AND m.is_active = 1
					ORDER BY m.no_tabel ASC, m.id ASC
				", [$thDda, $thDataStr, "%$thDataStr%", $thDda, $thData, $unitkerjalogin])->getResult();
				$a['page'] = "l_dda_periksa";
			}
		}

		return view('admin/index', $a);
	}



	public function forum_diskusi()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		// Email settings moved to actions

		$ta = $this->session->get('admin_ta');

		/* pagination dinonaktifkan: semua OPD langsung ditampilkan dan di-scroll di dalam kontainer */
		$a['pagi'] = '';

		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);
		$idu					= $this->uri->segment(4);
		$cari					= addslashes($this->input->post('q') ?? '');
		$unitkerjalogin			= $this->session->get('admin_unitkerja');
		$email 					= $this->session->get('admin_email');
		$nama 					= $this->session->get('admin_nama');

		if ($mau_ke == 'view_topik') {
			$id_unitkerja = $this->input->get('id');
			if ($unitkerjalogin == 'bps' || $this->session->get('admin_level') == 'kominfo') {
				$a['data']		= $this->db->query("SELECT * FROM m_forumtopic where id_unitkerja=? ORDER BY id_topik DESC", [$id_unitkerja])->getResult();
				$a['page']		= "l_forum_topik";
			} else {
				$a['data']		= $this->db->query("SELECT * FROM m_forumtopic where id_unitkerja=? ORDER BY id_topik DESC", [$unitkerjalogin])->getResult();
				$a['page']		= "l_forum_topik";
			}
		} else if ($mau_ke == 'add_topik') {
			$a['page']		= "f_tambah_topik";
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
			$a['data']    = $this->db->query("SELECT * FROM forumcomment WHERE id_topik = ? ORDER BY id ASC", [$id_topik])->getResult();
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
		} else {
			if ($unitkerjalogin == 'bps') {
				$level = $this->session->get('admin_level');
				if ($this->session->get('admin_user') == 'diseminasi' || $level == 'Admin' || $level == 'Super Admin') {
					$a['data']		= $this->db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind ASC")->getResult();
				} else {
					$user_wali = $this->session->get('admin_user');
					$user_level = strtolower(trim((string)$this->session->get('admin_level')));
					if ($user_level == 'lo') {
						$user_id = (int)$this->session->get('admin_id');
						$user_teams = $this->db->query("SELECT kode_tim FROM t_tim_anggota WHERE id_admin = ?", [$user_id])->getResultArray();
						$team_codes = array_filter(array_column($user_teams, 'kode_tim'));
						if (!empty($user_wali) && !in_array($user_wali, $team_codes)) {
							$team_codes[] = $user_wali;
						}
						if (!empty($team_codes)) {
							$in_clause = implode(',', array_fill(0, count($team_codes), '?'));
							$a['data'] = $this->db->query("SELECT * FROM m_unitkerja WHERE user_wali IN ($in_clause) ORDER BY unitkerja_ind ASC", $team_codes)->getResult();
						} else {
							$a['data'] = [];
						}
					} else if ($user_level == 'spv') {
						$a['data'] = $this->db->query("SELECT * FROM m_unitkerja where user_spv=? ORDER BY unitkerja_ind ASC", [$user_wali])->getResult();
					}
				}
				$a['page']		= "l_forum";
			} else {
				$a['data']		= $this->db->query("SELECT * FROM m_unitkerja where id_unitkerja=?", [$unitkerjalogin])->getResult();
				$a['page']		= "l_forum";
			}
			if ($this->session->get('admin_level') == 'kominfo') {
				$a['data']		= $this->db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind ASC")->getResult();
				$a['page']		= "l_forum";
			}
		}
		return view('admin/index', $a);
	}

	//master_tabel
	// =========================================================================
	// FUNGSI CEK KONFLIK NOMOR TABEL (AJAX)
	// Memeriksa secara realtime apakah nomor tabel sudah terpakai
	// =========================================================================
	public function check_new_table_conflict() {
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(["conflict" => false, "message" => "Unauthorized"]);
		}
		if (!can('master_tabel')) {
			return $this->response->setStatusCode(403)->setJSON(["conflict" => false, "message" => "Forbidden"]);
		}
		// Ambil dan bersihkan nomor tabel dari spasi
		$no_tabel = trim(preg_replace('/\s+/', '', (string)$this->request->getPost("no_tabel")));
		$ta = $this->request->getPost("tahun") ?: ($this->session->get("admin_ta") ?: ($this->session->get("sess_tahun") ?: date("Y")));
		$exclude_id_tabel = $this->request->getPost("exclude_id_tabel") ?: $this->request->getPost("exclude_id");
		
		if (!$no_tabel) {
			return $this->response->setJSON(["conflict" => false]);
		}
		
		// Query cek apakah nomor tabel sudah ada di m_list_tabel
		$sql = "
			SELECT m.id, m.no_tabel, m.judul_ind, COALESCE(u.unitkerja_ind, m.id_unitkerja) as unitkerja_ind
			FROM m_list_tabel m
			LEFT JOIN m_unitkerja u ON m.id_unitkerja = u.id_unitkerja
			WHERE m.no_tabel = ? AND m.is_active = 1
		";
		$params = [$no_tabel];
		
		// Kecualikan ID tabel tertentu (jika sedang dalam mode edit)
		if (!empty($exclude_id_tabel)) {
			$sql .= " AND m.id != ? ";
			$params[] = (int)$exclude_id_tabel;
		}
		$sql .= " LIMIT 1 ";
		
		$exists = $this->db->query($sql, $params)->getRow();
		
		// Jika ditemukan nomor kembar/konflik, kembalikan data tabel yang sudah memakai nomor tersebut
		if ($exists) {
			return $this->response->setJSON([
				"conflict"       => true, 
				"no_tabel"       => $no_tabel,
				"existing_judul" => $exists->judul_ind,
				"existing_opd"   => $exists->unitkerja_ind ?: '-'
			]);
		}
		return $this->response->setJSON(["conflict" => false]);
	}

	// =========================================================================
	// FUNGSI CARI NOMOR TABEL TERAKHIR DI SUB-BAB (AJAX LIVE SUGGESTION)
	// Mendeteksi nomor terakhir secara adaptif:
	// - Bab 2-tingkat (Bab.Nomor, misal Bab 8, 11, 14): ketik "11" atau "11." -> "11.10"
	// - Bab 3-tingkat (Bab.Subbab.Nomor, misal Bab 1, 4): ketik "1.1" -> "1.1.9"
	// =========================================================================
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
		$tahun  = $this->request->getGet('tahun') ?: ($this->session->get('admin_ta') ?: date('Y'));

		// Bersihkan spasi
		$prefix = preg_replace('/\s+/', '', $prefix);

		if (empty($prefix)) {
			return $this->response->setJSON(['status' => false]);
		}

		// 1. Ekstrak nomor bab pertama (misal dari "11", "11.", "1.1", "4.1.22")
		if (!preg_match('/^(\d+)(\.|$)/', $prefix, $bm)) {
			return $this->response->setJSON(['status' => false]);
		}
		$bab = $bm[1];

		// 2. Periksa apakah bab ini merupakan Bab 2-Tingkat (Bab.Nomor) atau Bab 3-Tingkat (Bab.Subbab.Nomor)
		$all_bab_tables = $this->db->query(
			"SELECT no_tabel FROM m_list_tabel WHERE is_active = 1 AND no_tabel LIKE ? ORDER BY id ASC",
			[$bab . '.%']
		)->getResult();

		$is_pure_2tier = false;
		if (!empty($all_bab_tables)) {
			$count_2tier = 0;
			$count_3tier = 0;
			foreach ($all_bab_tables as $tb) {
				$dots = substr_count(trim((string)$tb->no_tabel), '.');
				if ($dots === 1) {
					$count_2tier++;
				} else if ($dots >= 2) {
					$count_3tier++;
				}
			}
			// Bab 2-tingkat murni (seperti Bab 8, 11, 14)
			if ($count_2tier > 0 && $count_3tier === 0) {
				$is_pure_2tier = true;
			}
		}

		// 3. Tentukan Target Prefix Berdasarkan Pola Bab:
		$target_prefix = null;
		$is_2tier_mode = false;

		if ($is_pure_2tier) {
			// KASUS A: BAB 2-TINGKAT (Misal Bab 8, 11, 14)
			// Mengetik "11", "11.", atau "11.1" merujuk ke prefix bab "11."
			$target_prefix = $bab . '.';
			$is_2tier_mode = true;
		} else {
			// KASUS B: BAB 3-TINGKAT (Misal Bab 1, 2, 3, 4, dst.)
			// Membutuhkan minimal sub-bab (misal "1.1" atau "1.1.")
			if (preg_match('/^(\d+\.\d+)(\.|$)/', $prefix, $m)) {
				$target_prefix = $m[1] . '.';
			} else {
				// User baru mengetik bab saja (misal "1" atau "1.") pada bab 3-tingkat.
				// Tahan dan jangan beri saran prematur, tunggu sampai sub-bab diketik.
				return $this->response->setJSON(['status' => false]);
			}
		}

		// 4. Query seluruh tabel dengan prefix target
		$tables = $this->db->query(
			"SELECT no_tabel FROM m_list_tabel WHERE is_active = 1 AND no_tabel LIKE ? ORDER BY id ASC",
			[$target_prefix . '%']
		)->getResult();

		if (empty($tables)) {
			$clean_prefix = rtrim($target_prefix, '.');
			return $this->response->setJSON([
				'status'     => true,
				'has_tables' => false,
				'prefix'     => $clean_prefix,
				'last_no'    => null,
				'next_no'    => $target_prefix . '1',
				'badge_text' => $is_2tier_mode ? 'Bab baru' : 'Sub-bab baru'
			]);
		}

		$max_num = 0;
		$last_no = null;

		foreach ($tables as $t) {
			$clean = trim(preg_replace('/\s+/', '', (string)$t->no_tabel));
			// Ekstrak digit angka setelah prefix
			if (preg_match('/^' . preg_quote($target_prefix, '/') . '(\d+)/', $clean, $num_match)) {
				$val = (int)$num_match[1];
				if ($val > $max_num) {
					$max_num = $val;
					$last_no = $clean;
				}
			}
		}

		if ($max_num === 0) {
			return $this->response->setJSON(['status' => false]);
		}

		$next_no = $target_prefix . ($max_num + 1);

		return $this->response->setJSON([
			'status'     => true,
			'has_tables' => true,
			'prefix'     => rtrim($target_prefix, '.'),
			'last_no'    => $last_no ?: ($target_prefix . $max_num),
			'next_no'    => $next_no,
			'badge_text' => 'Nomor terakhir ' . ($last_no ?: ($target_prefix . $max_num))
		]);
	}

	// =========================================================================
	// FUNGSI GESER NOMOR TABEL OTOMATIS (SHIFT TABLE NUMBERS)
	// Menggeser naik (+1) nomor-nomor tabel yang berada di bawah posisi tabel baru
	// Contoh: jika menyisipkan tabel di 1.2, maka 1.2 lama -> 1.3, 1.3 lama -> 1.4, dst.
	// =========================================================================
	private function shift_table_numbers($start_no_tabel, $tahun = null, $exclude_id = null) {
		$clean_no = trim(preg_replace('/\s+/', '', (string)$start_no_tabel));
		
		// Pisahkan prefix (awalan misal "1."), angka terakhir (misal "2"), dan suffix huruf jika ada (misal "a")
		if (preg_match('/^(.*[\.])?(\d+)([a-zA-Z]*)$/', $clean_no, $m)) {
			$prefix = $m[1] ?? '';
			$num    = (int)$m[2];
			$suffix = $m[3] ?? '';
		} else {
			return false;
		}

		// Cari semua tabel dengan awalan prefix yang sama
		$tables = $this->db->query("SELECT id, no_tabel FROM m_list_tabel WHERE is_active = 1 AND no_tabel LIKE ?", [$prefix . '%'])->getResult();
		
		$updates = [];
		foreach ($tables as $t) {
			if ($exclude_id && $t->id == $exclude_id) continue;
			$t_clean = trim(preg_replace('/\s+/', '', (string)$t->no_tabel));
			if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)([a-zA-Z]*)$/', $t_clean, $tm)) {
				$t_num = (int)$tm[1];
				$t_suffix = $tm[2];
				// Jika nomor urut tabel >= nomor yang disisipkan, naikkan angkanya (+1)
				if ($t_num >= $num) {
					$new_num = $t_num + 1;
					$new_no_tabel = $prefix . $new_num . $t_suffix;
					$updates[] = ['id' => $t->id, 'new_no_tabel' => $new_no_tabel, 'old_num' => $t_num];
				}
			}
		}
		
		// Urutkan dari angka terbesar ke terkecil agar tidak terjadi benturan nomor saat UPDATE di database
		usort($updates, function($a, $b) { return $b['old_num'] - $a['old_num']; });
		
		// Eksekusi pembaruan nomor tabel pada m_list_tabel dan m_indikator_tabel
		foreach ($updates as $u) {
			$this->db->table('m_list_tabel')->where('id', $u['id'])->update(['no_tabel' => $u['new_no_tabel']]);
			$this->db->table('m_indikator_tabel')->where('id_tabel', $u['id'])->update(['no_tabel' => $u['new_no_tabel']]);
		}
		return count($updates);
	}


	// =========================================================================
	// FUNGSI PENCARIAN MASTER TABEL VIA AJAX (AUTOCOMPLETE / LIVE SEARCH)
	// Digunakan pada form tambah/edit tabel untuk mencari master tabel yang sudah ada
	// =========================================================================
	public function ajax_search_master_tabel()
	{
		// Cek otentikasi login admin
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
		}

		// Ambil kata kunci pencarian dan tahun aktif
		$q = trim($this->request->getVar('q') ?? '');
		$ta = (int)($this->session->get('admin_ta') ?: ($this->session->get('sess_tahun') ?: date('Y')));

		// Batasi pencarian minimal 2 karakter
		if (mb_strlen($q) < 2) {
			return $this->response->setJSON([]);
		}

		$term = '%' . $q . '%';
		
		// Query mencari data master tabel (m_list_tabel)
		$sql = "
			SELECT m.id, m.no_tabel, m.judul_ind, m.judul_en, m.id_unitkerja, u.unitkerja_ind,
			       GROUP_CONCAT(DISTINCT s.tahun ORDER BY s.tahun DESC SEPARATOR ', ') as tahun_terbit,
			       m.no_tabel as no_tabel_aktif,
			       MAX(CASE WHEN s.tahun = ? THEN 1 ELSE 0 END) as is_in_ta,
			       ka.link_api as latest_link,
			       m.no_tabel as prev_no_tabel
			FROM m_list_tabel m
			LEFT JOIN m_unitkerja u ON m.id_unitkerja = u.id_unitkerja
			LEFT JOIN t_status_tahunan s ON m.id = s.id_tabel
			LEFT JOIN m_konfigurasi_api ka ON m.id = ka.id_tabel
			WHERE m.is_active = 1 AND (m.judul_ind LIKE ? OR m.judul_en LIKE ? OR m.no_tabel LIKE ?)
			GROUP BY m.id, m.no_tabel, m.judul_ind, m.judul_en, m.id_unitkerja, u.unitkerja_ind, ka.link_api
			ORDER BY is_in_ta ASC, m.judul_ind ASC
			LIMIT 12
		";

		$results = $this->db->query($sql, [$ta, $term, $term, $term])->getResult();
		return $this->response->setJSON($results);
	}



	public function master_tabel()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('master_tabel')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengelola Master Tabel.</div>');
			return redirect()->to('admin');
		}


		$ta = $this->session->get('admin_ta') ?: date('Y');
		$a['ta'] = $ta;

		$a['list_opd'] = $this->db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind ASC")->getResult();
		$a['list_tim'] = $this->db->query("SELECT DISTINCT user_wali FROM m_unitkerja WHERE user_wali != '' AND user_wali != '-' ORDER BY user_wali ASC")->getResult();
		$a['tahun_counts'] = $this->db->query("SELECT tahun, COUNT(*) as cnt FROM t_status_tahunan GROUP BY tahun ORDER BY tahun DESC")->getResult();

		// Total tabel aktif & counter per kategori untuk dropdown filter interaktif
		$a['total_tabel_aktif'] = (int)($this->db->query("SELECT COUNT(*) as cnt FROM m_list_tabel WHERE is_active = 1")->getRow()->cnt ?? 0);

		$opdCountsRaw = $this->db->query("SELECT id_unitkerja, COUNT(*) as cnt FROM m_list_tabel WHERE is_active = 1 GROUP BY id_unitkerja")->getResult();
		$opdMap = [];
		foreach ($opdCountsRaw as $r) {
			$opdMap[trim((string)$r->id_unitkerja)] = (int)$r->cnt;
		}
		$a['count_per_opd'] = $opdMap;

		$timCountsRaw = $this->db->query("SELECT u.user_wali, COUNT(ml.id) as cnt FROM m_list_tabel ml JOIN m_unitkerja u ON ml.id_unitkerja = u.id_unitkerja WHERE ml.is_active = 1 GROUP BY u.user_wali")->getResult();
		$timMap = [];
		foreach ($timCountsRaw as $r) {
			$timMap[strtolower(trim((string)$r->user_wali))] = (int)$r->cnt;
		}
		$a['count_per_tim'] = $timMap;

		$jenisCountsRaw = $this->db->query("SELECT u.jenis_instansi, COUNT(ml.id) as cnt FROM m_list_tabel ml JOIN m_unitkerja u ON ml.id_unitkerja = u.id_unitkerja WHERE ml.is_active = 1 GROUP BY u.jenis_instansi")->getResult();
		$jenisMap = [];
		foreach ($jenisCountsRaw as $r) {
			$jenisMap[strtolower(trim((string)$r->jenis_instansi))] = (int)$r->cnt;
		}
		$a['count_per_jenis'] = $jenisMap;

		$mTabelDinamis = new \App\Models\M_tabeldinamis();

		//ambil variabel URL
		$mau_ke = $this->uri->segment(3);
		$idu    = $this->uri->segment(4);

		//ambil variabel post
		$idp             = $this->input->post('idp');
		$judul_ind       = $this->input->post('judul_ind');
		$judul_en        = $this->input->post('judul_en');
		$link_tabel      = $this->input->post('link_tabel');
		$link_api        = $this->input->post('link_api');
		$id_unitkerja    = $this->input->post('id_unitkerja');

		$is_confirm      = '2';
		$catatan         = '-';
		$is_periksa      = '0';
		$catatan_periksa = '-';
		$kondef          = '-';

		if ($mau_ke == "del") {
			$id_delete = (int)$this->input->get('delete_id');
			$ctx = $mTabelDinamis->resolveTableContext($id_delete, $ta, true);
			$datpil = null;
			if ($ctx['tabel']) {
				$datpil = (object)[
					'id'           => $ctx['id_tabel'],
					'id_tabel'     => $ctx['id_tabel'],
					'judul_ind'    => $ctx['tabel']->judul_ind,
					'judul_en'     => $ctx['tabel']->judul_en,
					'kondef'       => $ctx['tabel']->kondef,
					'id_unitkerja' => $ctx['tabel']->id_unitkerja,
					'tahun'        => $ctx['tahun_tabel']->tahun ?? $ta,
					'no_tabel'     => $ctx['tabel']->no_tabel ?? ($ctx['tahun_tabel']->no_tabel ?? ''),
				];
			}
			$a['datpil'] = $datpil;
			$a['act_url'] = base_url('index.php/admin/master_tabel/act_del/');

			$all_years = [];
			if ($datpil && !empty($datpil->id_tabel)) {
				$all_years = $this->db->query("SELECT id, tahun FROM t_status_tahunan WHERE id_tabel = ? ORDER BY tahun DESC", [$datpil->id_tabel])->getResult();
			}
			$a['all_years'] = $all_years;

			return view('admin/f_del_master', $a);
		} else if ($mau_ke == "act_del") {
			$id_oi = (int)$this->input->post('id');
			$scope = $this->input->post('scope') ?? 'current';

			$ctx = $mTabelDinamis->resolveTableContext($id_oi, $ta, true);
			$id_master = $ctx['id_tabel'];

			if ($id_master) {
				$this->db->transStart();

				if ($scope === 'all') {
					// Hapus secara global
					if ($ctx['indikator']) {
						$this->db->table('t_nilai_dinamis')->where('id_indikator', $ctx['indikator']->id)->delete();
						$this->db->table('m_indikator_tabel')->where('id', $ctx['indikator']->id)->delete();
					}
					$this->db->table('m_konfigurasi_api')->where('id_tabel', $id_master)->delete();
					$this->db->table('t_status_tahunan')->where('id_tabel', $id_master)->delete();
					$this->db->table('m_list_tabel')->where('id', $id_master)->delete();
					$msg = "Master tabel beserta seluruh data nilai dan konfigurasi berhasil dihapus.";
				} else {
					// Hapus untuk tahun aktif saja
					$tahunDataTarget = (int)$ta - 1;
					if ($ctx['indikator']) {
						$this->db->table('t_nilai_dinamis')
							->where('id_indikator', $ctx['indikator']->id)
							->where('tahun_data', $tahunDataTarget)
							->delete();
					}
					$this->db->table('t_status_tahunan')
						->where('id_tabel', $id_master)
						->where('tahun', (int)$ta)
						->delete();
					$msg = "Data tabel untuk tahun " . $ta . " berhasil dihapus.";
				}

				$this->db->transComplete();

				if ($this->db->transStatus() === false) {
					$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal menghapus data tabel. Transaksi dibatalkan.</div>");
				} else {
					$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">" . $msg . "</div>");
				}
			} else {
				$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\">Data tabel tidak ditemukan atau sudah dihapus.</div>");
			}

			return redirect()->to('admin/master_tabel/');
		} else if ($mau_ke == "cari") {
			$filter_opd    = $this->session->get('sess_f_opd') ?? 'all';
			$filter_bidang = $this->session->get('sess_f_bidang') ?? 'all';
			$filter_jenis  = $this->session->get('sess_f_jenis') ?? 'all';

			$cari = $this->request->getPost('q');

			$sql_base = "FROM m_list_tabel ml 
			             LEFT JOIN m_unitkerja u ON ml.id_unitkerja = u.id_unitkerja 
			             LEFT JOIN m_konfigurasi_api api ON api.id_tabel = ml.id 
			             LEFT JOIN m_indikator_tabel ind ON ind.id_tabel = ml.id";
			$sql_where = "WHERE ml.is_active = 1";

			$params = [];
			$cleanJenis = is_array($filter_jenis) ? array_values(array_filter($filter_jenis, fn($v) => !empty($v) && $v !== 'all')) : ($filter_jenis !== 'all' ? [$filter_jenis] : []);
			if (!empty($cleanJenis)) {
				$sql_where .= " AND u.jenis_instansi IN (" . implode(',', array_fill(0, count($cleanJenis), '?')) . ") ";
				foreach ($cleanJenis as $cj) $params[] = $cj;
			}

			$cleanOpd = is_array($filter_opd) 
				? array_values(array_filter(array_map('trim', $filter_opd), fn($v) => !empty($v) && $v !== 'all')) 
				: (($filter_opd !== 'all' && !empty($filter_opd)) ? [trim((string)$filter_opd)] : []);
			if (!empty($cleanOpd)) {
				$sql_where .= " AND ml.id_unitkerja IN (" . implode(',', array_fill(0, count($cleanOpd), '?')) . ") ";
				foreach ($cleanOpd as $co) $params[] = (string)$co;
			}

			$cleanBidang = is_array($filter_bidang) ? array_values(array_filter($filter_bidang, fn($v) => !empty($v) && $v !== 'all')) : ($filter_bidang !== 'all' ? [$filter_bidang] : []);
			if (!empty($cleanBidang)) {
				$sql_where .= " AND u.user_wali IN (" . implode(',', array_fill(0, count($cleanBidang), '?')) . ") ";
				foreach ($cleanBidang as $cb) $params[] = $cb;
			}

			$sql_where .= " AND (ml.judul_ind LIKE ? OR u.unitkerja_ind LIKE ? OR ml.no_tabel LIKE ?) ";
			$params[] = '%' . $cari . '%';
			$params[] = '%' . $cari . '%';
			$params[] = '%' . $cari . '%';

			$a['data'] = $this->db->query("
				SELECT ml.id, ml.id as id_master, ml.no_tabel, ml.judul_ind, ml.judul_en, ml.id_unitkerja, ml.jenis_sumber,
				       u.unitkerja_ind, u.user_wali, u.jenis_instansi,
				       api.id_api, api.link_api, api.tahun_tersedia,
				       ind.id as id_indikator, ind.tahun_tampil,
				       ml.id as id_tahun_tabel,
				       (CASE WHEN ml.jenis_sumber = 'api_kominfo' THEN 0 WHEN ml.jenis_sumber = 'dinamis' THEN 1 WHEN ind.id IS NOT NULL THEN 1 ELSE 0 END) as is_dynamic
				$sql_base 
				$sql_where 
				ORDER BY CAST(SUBSTRING_INDEX(COALESCE(ml.no_tabel, '999'), '.', 1) AS UNSIGNED) ASC, 
				         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(COALESCE(ml.no_tabel, '999'),'.0'), '.', 2), '.', -1) AS UNSIGNED) ASC, 
				         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(COALESCE(ml.no_tabel, '999'),'.0.0'), '.', 3), '.', -1) AS UNSIGNED) ASC, 
				         ml.judul_ind ASC
			", $params)->getResult();

			if ($this->request->isAJAX()) {
				return view('admin/l_master_tabel_partial', $a);
			}

			$a['selected_jenis']  = $filter_jenis;
			$a['selected_opd']    = $filter_opd;
			$a['selected_bidang'] = $filter_bidang;

			$a['list_tim']     = $this->db->query("SELECT DISTINCT user_wali FROM m_unitkerja WHERE user_wali != '' ORDER BY user_wali ASC")->getResult();
			$a['list_opd']     = $this->db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind ASC")->getResult();
			$a['opd']          = $a['list_opd'];
			$a['tahun_counts'] = $this->db->query("SELECT tahun, COUNT(*) as cnt FROM t_status_tahunan GROUP BY tahun ORDER BY tahun DESC")->getResult();
			$a['awal']         = 0;
			$a['cari']         = $cari;

			$a['pagi'] = "";
			$a['page'] = "l_master_tabel";
		} else if ($mau_ke == "add") {
			$a['page'] = "f_master_tabel";
		} else if ($mau_ke == "edt") {
			$ctx = $mTabelDinamis->resolveTableContext($idu, $ta, true);
			if (!$ctx['tabel']) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\">Data tabel tidak ditemukan.</div>");
				return redirect()->to('admin/master_tabel');
			}
			$id_master = $ctx['id_tabel'];
			$tRow = $ctx['tabel'];
			$indRow = $ctx['indikator'];
			$ttRow = $ctx['tahun_tabel'];

			$apiRow = $this->db->table('m_konfigurasi_api')->where('id_tabel', $id_master)->get()->getRow();

			$nama_karakteristik = '';
			$nama_baris = '';
			$nama_periode = '';
			if ($indRow) {
				if (!empty($indRow->id_karakteristik_kelompok)) {
					$kk = $this->db->table('m_karakteristik_kelompok')->where('id', $indRow->id_karakteristik_kelompok)->get()->getRow();
					$nama_karakteristik = $kk->nama_kelompok_ind ?? '';
				}
				if (!empty($indRow->id_baris_kelompok)) {
					$bk = $this->db->table('m_baris_kelompok')->where('id', $indRow->id_baris_kelompok)->get()->getRow();
					$nama_baris = $bk->nama_kelompok_ind ?? '';
				}
				if (!empty($indRow->id_periode_kelompok)) {
					$pk = $this->db->table('m_periode_kelompok')->where('id', $indRow->id_periode_kelompok)->get()->getRow();
					$nama_periode = $pk->nama_kelompok_ind ?? '';
				} else if (!empty($indRow->tipe_periode)) {
					$nama_periode = ucfirst(str_replace('_', ' ', $indRow->tipe_periode));
				}
			}

			$a['datpil'] = (object)[
				'id'                     => $id_master,
				'id_master'              => $id_master,
				'id_tahun_tabel'         => $id_master,
				'judul_ind'              => $tRow->judul_ind,
				'judul_en'               => $tRow->judul_en,
				'no_tabel'               => $tRow->no_tabel ?? '',
				'id_unitkerja'           => $tRow->id_unitkerja,
				'jenis_sumber'           => $tRow->jenis_sumber ?? 'api_kominfo',
				'is_dynamic'             => ($tRow->jenis_sumber === 'dinamis' ? 1 : 0),
				'link_tabel'             => $apiRow->link_api ?? ($apiRow->id_api ?? ''),
				'link_api'               => $apiRow->link_api ?? ($apiRow->id_api ?? ''),
				'id_api'                 => $apiRow->id_api ?? '',
				'link_sebelumnya'        => '',
				'id_indikator'           => $indRow->id ?? 0,
				'nama_karakteristik_ind' => $nama_karakteristik,
				'nama_baris_ind'         => $nama_baris,
				'nama_periode_ind'       => $nama_periode,
			];
			$a['page'] = "f_master_tabel";
		} else if ($mau_ke == "act_add") {
			$hariini             = date('Y-m-d');
			$no_tabel            = trim(preg_replace('/\s+/', '', (string)$this->input->post("no_tabel"))) ?: null;
			$judul_ind_raw       = trim($this->input->post('judul_ind') ?? '');
			$judul_en_raw        = trim($this->input->post('judul_en') ?? '');
			$id_unitkerja        = $this->input->post('id_unitkerja');
			$master_id           = $this->input->post("master_id");
			$conflict_resolution = $this->input->post("conflict_resolution");

			// Bersihkan akhiran tahun statis pada judul
			$clean_judul_ind = trim(preg_replace('/,?\s*(Tahun\s+|Year\s+)?(19|20)\d{2}(\s*[-–\/]\s*(19|20)\d{2})?\s*$/i', '', $judul_ind_raw));
			$clean_judul_en  = trim(preg_replace('/,?\s*(Tahun\s+|Year\s+)?(19|20)\d{2}(\s*[-–\/]\s*(19|20)\d{2})?\s*$/i', '', $judul_en_raw));
			if (empty($clean_judul_en)) {
				$clean_judul_en = $clean_judul_ind;
			}

			// Tentukan jenis_sumber
			$jenis_sumber = $this->input->post('jenis_sumber');
			if (empty($jenis_sumber)) {
				$is_dyn_post = $this->input->post('is_dynamic');
				$jenis_sumber = (!empty($is_dyn_post) && (int)$is_dyn_post === 1) ? 'dinamis' : 'api_kominfo';
			}

			$link_input = trim((string)($this->input->post('link_api') ?: $this->input->post('link_tabel')));
			$id_api = null;
			if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $link_input, $matches)) {
				$id_api = $matches[0];
			} elseif (preg_match('/[?&]id=([^&]+)/i', $link_input, $matches)) {
				$id_api = $matches[1];
			} elseif (!empty($link_input) && strlen($link_input) <= 100 && !filter_var($link_input, FILTER_VALIDATE_URL)) {
				$id_api = $link_input;
			}

			$m_id = null;

			if (!empty($master_id)) {
				$check_m = $this->db->query("SELECT id FROM m_list_tabel WHERE id = ?", [$master_id])->getRow();
				if ($check_m) {
					$m_id = $check_m->id;
					$this->db->table('m_list_tabel')->where('id', $m_id)->update([
						'no_tabel'     => $no_tabel,
						'judul_en'     => $clean_judul_en,
						'id_unitkerja' => $id_unitkerja,
						'jenis_sumber' => $jenis_sumber,
					]);
				}
			}

			if (empty($m_id)) {
				$check_m = $this->db->query("SELECT id FROM m_list_tabel WHERE id_unitkerja = ? AND LOWER(TRIM(judul_ind)) = LOWER(TRIM(?)) LIMIT 1", [$id_unitkerja, $clean_judul_ind])->getRow();
				if ($check_m) {
					$m_id = $check_m->id;
					$this->db->table('m_list_tabel')->where('id', $m_id)->update([
						'no_tabel'     => $no_tabel,
						'judul_en'     => $clean_judul_en,
						'jenis_sumber' => $jenis_sumber,
					]);
				} else {
					$this->db->table('m_list_tabel')->insert([
						'no_tabel'     => $no_tabel,
						'judul_ind'    => $clean_judul_ind,
						'judul_en'     => $clean_judul_en,
						'id_unitkerja' => $id_unitkerja,
						'jenis_sumber' => $jenis_sumber,
						'kondef'       => $kondef,
						'is_active'    => 1,
					]);
					$m_id = $this->db->insertID();
				}
			}

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
				if ($cekApi) {
					$this->db->table('m_konfigurasi_api')->where('id', $cekApi->id)->update([
						'id_api'     => $id_api ?: $cekApi->id_api,
						'link_api'   => $link_input ?: $cekApi->link_api,
						'updated_at' => date('Y-m-d H:i:s'),
					]);
				} else {
					$this->db->table('m_konfigurasi_api')->insert([
						'id_tabel'   => $m_id,
						'id_api'     => $id_api,
						'link_api'   => $link_input,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s'),
					]);
				}
			}

			// Sinkronisasi m_indikator_tabel jika dinamis
			if ($jenis_sumber === 'dinamis') {
				// Bersihkan konfigurasi API jika tabel diatur ke dinamis
				$this->db->table('m_konfigurasi_api')->where('id_tabel', $m_id)->delete();

				$cekInd = $this->db->table('m_indikator_tabel')->where('id_tabel', $m_id)->get()->getRow();
				if ($cekInd) {
					$this->db->table('m_indikator_tabel')->where('id', $cekInd->id)->update([
						'no_tabel'     => $no_tabel,
						'judul_ind'    => $clean_judul_ind,
						'judul_en'     => $clean_judul_en,
						'id_unitkerja' => $id_unitkerja,
						'updated_at'   => date('Y-m-d H:i:s'),
					]);
				} else {
					$this->db->table('m_indikator_tabel')->insert([
						'id_tabel'        => $m_id,
						'no_tabel'        => $no_tabel,
						'judul_ind'       => $clean_judul_ind,
						'judul_en'        => $clean_judul_en,
						'id_unitkerja'    => $id_unitkerja,
						'tipe_periode'    => 'tahunan',
						'desimal'         => -1,
						'satuan'          => null,
						'config_tampilan' => json_encode([
							'total_horizontal' => false,
							'jenis_total_horizontal' => 'sum',
							'only_total_horizontal' => false,
							'group_total_horizontal' => false,
							'total_vertikal' => true,
							'jenis_total_vertikal' => 'default',
							'label_total_kustom' => '',
							'group_by_region' => true,
							'prioritas_karakteristik' => false,
							'portrait_mode' => false,
							'portrait_split_col' => false
						]),
						'created_at'      => date('Y-m-d H:i:s'),
						'updated_at'      => date('Y-m-d H:i:s'),
					]);
				}
			}

			if ($conflict_resolution == "shift" && $no_tabel) {
				$this->shift_table_numbers($no_tabel, $ta);
			}

			// Inisialisasi t_status_tahunan untuk $ta
			$mTabelDinamis->upsertStatusTahunan((int)$m_id, (int)$ta, [
				'is_periksa' => ($jenis_sumber !== 'dinamis' && !empty($link_input) && $link_input !== '-') ? 1 : 0,
				'catatan'    => '-',
			]);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Master Tabel berhasil ditambahkan (ID #{$m_id})</div>");

			// Jika admin memilih "Simpan & Lanjut ke Generator Tabel"
			if ($this->input->post('simpan_lanjut_generator') == '1' || ($jenis_sumber === 'dinamis' && $this->input->post('buka_generator') == '1')) {
				return redirect()->to('admin/generator_tabel/' . $m_id);
			}

			return redirect()->to('admin/master_tabel/');
		} else if ($mau_ke == "act_edt") {
			$ctx = $mTabelDinamis->resolveTableContext($idp, $ta, true);
			if (!$ctx['tabel']) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\">Master tabel tidak ditemukan.</div>");
				return redirect()->to('admin/master_tabel/');
			}
			$m_id = $ctx['id_tabel'];

			$no_tabel      = trim(preg_replace('/\s+/', '', (string)$this->input->post('no_tabel'))) ?: null;
			$judul_ind_raw = trim($this->input->post('judul_ind') ?? '');
			$judul_en_raw  = trim($this->input->post('judul_en') ?? '');
			$id_unitkerja  = $this->input->post('id_unitkerja');

			$clean_judul_ind = trim(preg_replace('/,?\s*(Tahun\s+|Year\s+)?(19|20)\d{2}(\s*[-–\/]\s*(19|20)\d{2})?\s*$/i', '', $judul_ind_raw));
			$clean_judul_en  = trim(preg_replace('/,?\s*(Tahun\s+|Year\s+)?(19|20)\d{2}(\s*[-–\/]\s*(19|20)\d{2})?\s*$/i', '', $judul_en_raw));
			if (empty($clean_judul_en)) {
				$clean_judul_en = $clean_judul_ind;
			}

			$jenis_sumber = $this->input->post('jenis_sumber');
			if (empty($jenis_sumber)) {
				$is_dyn_post = $this->input->post('is_dynamic');
				$jenis_sumber = (!empty($is_dyn_post) && (int)$is_dyn_post === 1) ? 'dinamis' : 'api_kominfo';
			}

			$link_input = trim((string)($this->input->post('link_api') ?: $this->input->post('link_tabel')));
			$id_api = null;
			if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $link_input, $matches)) {
				$id_api = $matches[0];
			} elseif (preg_match('/[?&]id=([^&]+)/i', $link_input, $matches)) {
				$id_api = $matches[1];
			} elseif (!empty($link_input) && strlen($link_input) <= 100 && !filter_var($link_input, FILTER_VALIDATE_URL)) {
				$id_api = $link_input;
			}

			// Update m_list_tabel
			$this->db->table('m_list_tabel')->where('id', $m_id)->update([
				'no_tabel'     => $no_tabel,
				'judul_ind'    => $clean_judul_ind,
				'judul_en'     => $clean_judul_en,
				'id_unitkerja' => $id_unitkerja,
				'jenis_sumber' => $jenis_sumber,
			]);

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
				if ($cekApi) {
					$this->db->table('m_konfigurasi_api')->where('id', $cekApi->id)->update([
						'id_api'     => $id_api ?: $cekApi->id_api,
						'link_api'   => $link_input ?: $cekApi->link_api,
						'updated_at' => date('Y-m-d H:i:s'),
					]);
				} else {
					$this->db->table('m_konfigurasi_api')->insert([
						'id_tabel'   => $m_id,
						'id_api'     => $id_api,
						'link_api'   => $link_input,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s'),
					]);
				}
			}

			// Sinkronisasi m_indikator_tabel jika dinamis
			if ($jenis_sumber === 'dinamis') {
				// Bersihkan konfigurasi API jika tabel diatur ke dinamis
				$this->db->table('m_konfigurasi_api')->where('id_tabel', $m_id)->delete();

				$cekInd = $this->db->table('m_indikator_tabel')->where('id_tabel', $m_id)->get()->getRow();
				if ($cekInd) {
					$this->db->table('m_indikator_tabel')->where('id', $cekInd->id)->update([
						'no_tabel'     => $no_tabel,
						'judul_ind'    => $clean_judul_ind,
						'judul_en'     => $clean_judul_en,
						'id_unitkerja' => $id_unitkerja,
						'updated_at'   => date('Y-m-d H:i:s'),
					]);
				} else {
					$this->db->table('m_indikator_tabel')->insert([
						'id_tabel'        => $m_id,
						'no_tabel'        => $no_tabel,
						'judul_ind'       => $clean_judul_ind,
						'judul_en'        => $clean_judul_en,
						'id_unitkerja'    => $id_unitkerja,
						'tipe_periode'    => 'tahunan',
						'desimal'         => -1,
						'satuan'          => null,
						'config_tampilan' => json_encode([
							'total_horizontal' => false,
							'jenis_total_horizontal' => 'sum',
							'only_total_horizontal' => false,
							'group_total_horizontal' => false,
							'total_vertikal' => true,
							'jenis_total_vertikal' => 'default',
							'label_total_kustom' => '',
							'group_by_region' => true,
							'prioritas_karakteristik' => false,
							'portrait_mode' => false,
							'portrait_split_col' => false
						]),
						'created_at'      => date('Y-m-d H:i:s'),
						'updated_at'      => date('Y-m-d H:i:s'),
					]);
				}
			}

			// Inisialisasi/update t_status_tahunan untuk $ta
			$mTabelDinamis->upsertStatusTahunan((int)$m_id, (int)$ta, []);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Master Tabel berhasil diperbarui</div>");

			if ($this->input->post('simpan_lanjut_generator') == '1' || ($jenis_sumber === 'dinamis' && $this->input->post('buka_generator') == '1')) {
				return redirect()->to('admin/generator_tabel/' . $m_id);
			}

			return redirect()->to('admin/master_tabel/');
		} else {
			if (isset($_GET['action']) && $_GET['action'] == 'reset') {
				session()->remove('sess_f_opd');
				session()->remove('sess_f_bidang');
				session()->remove('sess_f_jenis');
				return redirect()->to('admin/master_tabel');
			}

			if (isset($_GET['filter_jenis'])) {
				$val = $_GET['filter_jenis'];
				$this->session->set('sess_f_jenis', $val);
			}

			if (isset($_GET['filter_opd'])) {
				$val = $_GET['filter_opd'];
				$this->session->set('sess_f_opd', $val);
			}

			if (isset($_GET['filter_bidang'])) {
				$val = $_GET['filter_bidang'];
				$this->session->set('sess_f_bidang', $val);
			}

			$filter_jenis  = $this->session->get('sess_f_jenis');
			$filter_opd    = $this->session->get('sess_f_opd');
			$filter_bidang = $this->session->get('sess_f_bidang');

			if (empty($filter_jenis)) $filter_jenis = 'all';
			if (empty($filter_opd)) $filter_opd = 'all';
			if (empty($filter_bidang)) $filter_bidang = 'all';

			$a['selected_jenis']  = $filter_jenis;
			$a['selected_opd']    = $filter_opd;
			$a['selected_bidang'] = $filter_bidang;

			$sql_base  = "FROM m_list_tabel ml 
			             LEFT JOIN m_unitkerja u ON ml.id_unitkerja = u.id_unitkerja 
			             LEFT JOIN m_konfigurasi_api api ON api.id_tabel = ml.id 
			             LEFT JOIN m_indikator_tabel ind ON ind.id_tabel = ml.id";
			$sql_where = "WHERE ml.is_active = 1";

			$params_count = [];

			$cleanJenis = is_array($filter_jenis) ? array_values(array_filter($filter_jenis, fn($v) => !empty($v) && $v !== 'all')) : ($filter_jenis !== 'all' ? [$filter_jenis] : []);
			if (!empty($cleanJenis)) {
				$sql_where .= " AND u.jenis_instansi IN (" . implode(',', array_fill(0, count($cleanJenis), '?')) . ")";
				foreach ($cleanJenis as $cj) $params_count[] = $cj;
			}

			$cleanOpd = is_array($filter_opd) 
				? array_values(array_filter(array_map('trim', $filter_opd), fn($v) => !empty($v) && $v !== 'all')) 
				: (($filter_opd !== 'all' && !empty($filter_opd)) ? [trim((string)$filter_opd)] : []);
			if (!empty($cleanOpd)) {
				$sql_where .= " AND ml.id_unitkerja IN (" . implode(',', array_fill(0, count($cleanOpd), '?')) . ")";
				foreach ($cleanOpd as $co) $params_count[] = (string)$co;
			}

			$cleanBidang = is_array($filter_bidang) ? array_values(array_filter($filter_bidang, fn($v) => !empty($v) && $v !== 'all')) : ($filter_bidang !== 'all' ? [$filter_bidang] : []);
			if (!empty($cleanBidang)) {
				$sql_where .= " AND u.user_wali IN (" . implode(',', array_fill(0, count($cleanBidang), '?')) . ")";
				foreach ($cleanBidang as $cb) $params_count[] = $cb;
			}

			/* pagination dinonaktifkan: seluruh tabel ditampilkan sekaligus dan di-scroll di dalam kontainer */
			$a['pagi'] = '';

			$a['data'] = $this->db->query("
				SELECT ml.id, ml.id as id_master, ml.no_tabel, ml.judul_ind, ml.judul_en, ml.id_unitkerja, ml.jenis_sumber,
				       u.unitkerja_ind, u.user_wali, u.jenis_instansi,
				       api.id_api, api.link_api, api.tahun_tersedia,
				       ind.id as id_indikator, ind.tahun_tampil,
				       ml.id as id_tahun_tabel,
				       (CASE WHEN ml.jenis_sumber = 'api_kominfo' THEN 0 WHEN ml.jenis_sumber = 'dinamis' THEN 1 WHEN ind.id IS NOT NULL THEN 1 ELSE 0 END) as is_dynamic
				$sql_base 
				$sql_where 
				ORDER BY CAST(SUBSTRING_INDEX(COALESCE(ml.no_tabel, '999'), '.', 1) AS UNSIGNED) ASC, 
				         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(COALESCE(ml.no_tabel, '999'),'.0'), '.', 2), '.', -1) AS UNSIGNED) ASC, 
				         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(COALESCE(ml.no_tabel, '999'),'.0.0'), '.', 3), '.', -1) AS UNSIGNED) ASC, 
				         ml.judul_ind ASC
			", $params_count)->getResult();

			$a['awal'] = 0;
			$a['cari'] = '';
			$a['page'] = "l_master_tabel";
		}
		return view('admin/index', $a);
	}


	//REPORT
	public function report()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('report')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk melihat Laporan Rekap.</div>');
			return redirect()->to('admin');
		}


		$jenis_rekap = $this->request->getPost('jenis_rekap') ?? '0';

		$a['jenis_rekap'] = $jenis_rekap;
		$a['page']	= "report";
		return view('admin/index', $a);
	}


	public function master_tabel_opd()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('master_tabel') && !can('usulan_tabel') && !can('input_dda_input') && !can('view_dda')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengakses menu Usulan Tabel.</div>');
			return redirect()->to('admin/dda');
		}

		$ta = $this->session->get('admin_ta') ?: date('Y');

		/* pagination dinonaktifkan: seluruh data usulan ditampilkan sekaligus dan di-scroll di dalam kontainer */
		$a['pagi'] = '';

		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);
		$idu					= $this->uri->segment(4);
		$cari					= $this->input->post('q') ?? '';

		//ambil variabel post
		$idp					= $this->input->post('idp');
		$judul_ind				= $this->input->post('judul_ind');
		$judul_en				= $this->input->post('judul_en');
		$unitkerja				= $this->session->get('admin_unitkerja');
		$addby					= $this->session->get('admin_user');
		$is_setujui				= '0';

		$cari					= $this->input->post('q');

		//upload config 
		$config['upload_path'] 		= './upload/tabel_usulan/';
		$config['allowed_types'] 	= 'xls|xlsx|doc|docx';
		$config['max_size']			= '10000';
		$config['max_width']  		= '3000';
		$config['max_height'] 		= '3000';
		$kode						= rand(1, 1000);
		$safe_unit					= substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$unitkerja), 0, 15);
		$safe_user					= substr(preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$addby), 0, 15);
		$config['file_name'] 		= $kode . '_' . $safe_unit . '_' . $safe_user;

		$this->load->library('upload', $config);

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
		} else if ($mau_ke == "cari") {
			$is_bps = $this->isAdminBps();
			$unitkerja = $this->session->get('admin_unitkerja');
			$cari = $this->input->post('q') ?: ($this->input->post('cari') ?: '');
			if ($is_bps) {
				$a['data'] = $this->db->query("SELECT l.*, u.unitkerja_ind FROM m_master_tabel_usulan l LEFT JOIN m_unitkerja u ON l.id_unitkerja = u.id_unitkerja WHERE l.judul_ind LIKE ? OR l.judul_en LIKE ? OR u.unitkerja_ind LIKE ? ORDER BY l.is_setujui ASC, l.id DESC", ['%' . $cari . '%', '%' . $cari . '%', '%' . $cari . '%'])->getResult();
			} else {
				$a['data'] = $this->db->query("SELECT l.*, u.unitkerja_ind FROM m_master_tabel_usulan l LEFT JOIN m_unitkerja u ON l.id_unitkerja = u.id_unitkerja WHERE l.id_unitkerja = ? AND (l.judul_ind LIKE ? OR l.judul_en LIKE ? OR u.unitkerja_ind LIKE ?) ORDER BY l.is_setujui ASC, l.id DESC", [$unitkerja, '%' . $cari . '%', '%' . $cari . '%', '%' . $cari . '%'])->getResult();
			}
			$a['offset'] = 0;
			$a['pagi'] = "";
			$a['is_bps'] = $is_bps;
			$a['page'] = "l_master_tabel_opd";
		} else if ($mau_ke == "add") {
			$a['is_bps'] = $this->isAdminBps();
			$a['page']		= "f_add_opd";
		} else if ($mau_ke == "edt") {
			if (!$this->isAdminBps()) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Hanya Admin BPS yang berwenang menelaah dan menyetujui usulan tabel.</div>");
				return redirect()->to('admin/master_tabel_opd/');
			}

			$datpil = $this->db->query("SELECT * from m_master_tabel_usulan WHERE id = ?", [$idu])->getRow();
			$a['datpil']   = $datpil;
			$a['list_opd'] = $this->db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind ASC")->getResult();
			$a['ta']       = $ta;

			$linked_m = null;
			$linked_t = null;
			if ($datpil) {
				$linked_m = $this->db->query("SELECT id FROM m_list_tabel WHERE id_unitkerja = ? AND LOWER(TRIM(judul_ind)) = LOWER(TRIM(?)) LIMIT 1", [$datpil->id_unitkerja, $datpil->judul_ind])->getRow();
				if ($linked_m) {
					$linked_t = $this->db->query("SELECT * FROM t_status_tahunan WHERE id_tabel = ? AND tahun = ? LIMIT 1", [$linked_m->id, (int)$ta])->getRow();
				}
			}
			$a['linked_m'] = $linked_m;
			$a['linked_t'] = $linked_t;

			$a['page']     = "f_add_opd";
		} else if ($mau_ke == "act_add") {
			$hariini = date('Y-m-d');
			date_default_timezone_set("Asia/Jakarta");
			$waktusekarang = date("H:i");
			if ($this->upload->do_upload('file_tabel')) {
				$up_data = $this->upload->data();
				$this->db->query("INSERT INTO m_master_tabel_usulan VALUES (NULL, ?, ?, ?, ?, ?, ?)", [$judul_ind, $judul_en, $up_data['file_name'], $is_setujui, $unitkerja, $addby]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Tabel usulan berhasil dikirim ke BPS untuk ditelaah.</div>");
			} else {
				$errMsg = $this->upload->display_errors() ?: 'Gagal mengunggah berkas tabel pendukung.';
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal mengirim usulan: " . esc($errMsg) . "</div>");
			}
			return redirect()->to('admin/master_tabel_opd/');
		} else if ($mau_ke == "act_edt") {
			if (!can('master_tabel')) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Hanya Admin BPS yang berwenang menyetujui usulan tabel.</div>");
				return redirect()->to('admin/master_tabel_opd/');
			}
			// =================================================================
			// PROSES PERSETUJUAN (ACC) USULAN TABEL DARI OPD
			// Mengubah usulan menjadi tabel resmi di master tabel & tahunan
			// =================================================================
			// Ambil input form persetujuan
			$idp             = $this->input->post('idp');
			$judul_ind       = trim($this->input->post('judul_ind') ?? '');
			$judul_en        = trim($this->input->post('judul_en') ?? '');
			$id_unitkerja    = $this->input->post('id_unitkerja') ?: $this->session->get('admin_unitkerja');
			$no_tabel        = trim(preg_replace('/\s+/', '', (string)$this->input->post('no_tabel'))) ?: null;
			$periode_id      = trim($this->input->post('periode_id') ?? '') ?: (trim($this->input->post('periode') ?? '') ?: null);
			$periode_en      = trim($this->input->post('periode_en') ?? '') ?: $periode_id;
			$link_tabel      = $this->input->post('link_tabel') ?: '';
			$link_sebelumnya = $this->input->post('link_sebelumnya') ?: '';
			$ta_target       = trim((string)($this->input->post('ta_target') ?? ''));
			$ta              = !empty($ta_target) ? (int)$ta_target : (int)($this->session->get('admin_ta') ?: date('Y'));

			// Langkah 1: Cek apakah ada file lampiran baru yang diunggah
			if ($this->upload->do_upload('file_tabel')) {
				$up_data = $this->upload->data();
				$this->db->query("UPDATE m_master_tabel_usulan SET file_tabel = ? WHERE id = ?", [$up_data['file_name'], $idp]);
			}

			// Langkah 2: Daftarkan atau Tautkan ke Induk Master Tabel (m_list_tabel)
			$conflict_resolution = $this->input->post("conflict_resolution");

			$m_id = null;
			$check_m = $this->db->query("SELECT id FROM m_list_tabel WHERE id_unitkerja = ? AND LOWER(TRIM(judul_ind)) = LOWER(TRIM(?)) LIMIT 1", [$id_unitkerja, $judul_ind])->getRow();
			if ($check_m) {
				$m_id = $check_m->id;
				$this->db->query("UPDATE m_list_tabel SET no_tabel = COALESCE(?, no_tabel), judul_en = COALESCE(NULLIF(judul_en, ''), ?) WHERE id = ?", [$no_tabel, $judul_en, $m_id]);
			} else {
				$this->db->query("INSERT INTO m_list_tabel (no_tabel, judul_ind, judul_en, id_unitkerja, kondef, is_active) VALUES (?, ?, ?, ?, '-', 1)", [$no_tabel, $judul_ind, $judul_en, $id_unitkerja]);
				$m_id = $this->db->insertID();
			}

			// Simpan link_api ke m_konfigurasi_api jika ada
			if (!empty($link_tabel)) {
				$existKa = $this->db->table('m_konfigurasi_api')->where('id_tabel', $m_id)->get()->getRow();
				if ($existKa) {
					$this->db->table('m_konfigurasi_api')->where('id', $existKa->id)->update([
						'link_api'   => $link_tabel,
						'updated_at' => date('Y-m-d H:i:s'),
					]);
				} else {
					$this->db->table('m_konfigurasi_api')->insert([
						'id_tabel'   => $m_id,
						'link_api'   => $link_tabel,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s'),
					]);
				}
			}

			// Jika user memilih resolusi 'shift', geser nomor tabel yang beririsan (+1)
			if ($conflict_resolution == "shift" && $no_tabel) {
				$this->shift_table_numbers($no_tabel, $ta, $m_id);
			}

			// Langkah 3: Daftarkan ke t_status_tahunan untuk tahun aktif ($ta)
			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$mTabelDinamis->upsertStatusTahunan((int)$m_id, (int)$ta, [
				'catatan' => '-',
			]);

			// Langkah 4: Tandai tabel usulan sebagai telah disetujui (is_setujui = '1')
			$this->db->query("UPDATE m_master_tabel_usulan SET is_setujui = '1', judul_ind = ?, judul_en = ?, id_unitkerja = ? WHERE id = ?", [$judul_ind, $judul_en, $id_unitkerja, $idp]);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Tabel usulan berhasil disetujui (ACC) dan ditautkan ke Master Tabel #{$m_id} untuk DDA Tahun {$ta}</div>");
			return redirect()->to('admin/master_tabel_opd/');
		} else {
			$is_bps = $this->isAdminBps();
			$unitkerja = $this->session->get('admin_unitkerja');
			if ($is_bps) {
				$a['data'] = $this->db->query("SELECT l.*,u.unitkerja_ind FROM m_master_tabel_usulan l left join m_unitkerja u on l.id_unitkerja=u.id_unitkerja ORDER BY l.is_setujui ASC, l.id DESC")->getResult();
			} else {
				$a['data'] = $this->db->query("SELECT l.*,u.unitkerja_ind FROM m_master_tabel_usulan l left join m_unitkerja u on l.id_unitkerja=u.id_unitkerja WHERE l.id_unitkerja = ? ORDER BY l.is_setujui ASC, l.id DESC", [$unitkerja])->getResult();
			}
			$a['offset']	= 0;
			$a['is_bps']	= $is_bps;
			$a['page']		= "l_master_tabel_opd";
		}
		return view('admin/index', $a);
	}


	public function master_opd()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('master_opd')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengelola Master OPD.</div>');
			return redirect()->to('admin');
		}


		/* pagination dinonaktifkan: semua OPD langsung ditampilkan dan di-scroll di dalam kontainer */
		$a['pagi'] = '';

		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);
		$idu					= $this->uri->segment(4);

		$cari					= $this->input->post('q');

		//ambil variabel Postingan
		$idp			= $this->input->post('idp');
		$id_unitkerja	= $this->input->post('id_unitkerja');
		$unitkerja_ind	= $this->input->post('unitkerja_ind');
		$unitkerja_en	= $this->input->post('unitkerja_en');
		$jenis_instansi = $this->input->post('jenis_instansi') ?: 'vertikal';
		$user_wali		= $this->input->post('user_wali');
		$user_spv		= trim((string)($this->input->post('user_spv') ?? '')) ?: '-';


		if ($mau_ke == "del") {
			$this->db->query("DELETE FROM m_unitkerja WHERE id_unitkerja = ?", [$idu]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/master_opd');
		} else if ($mau_ke == "cari") {
			$a['data']		= $this->db->query("SELECT * FROM m_unitkerja WHERE unitkerja_ind LIKE ? OR id_unitkerja LIKE ? ORDER BY id_unitkerja DESC", ['%' . $cari . '%', '%' . $cari . '%'])->getResult();

			if ($this->request->isAJAX()) {
				return view('admin/l_unitkerja_partial', $a);
			}

			$a['page']		= "l_unitkerja";
		} else if ($mau_ke == "add") {
			$a['page']		= "f_unitkerja";
		} else if ($mau_ke == "edt") {
			$a['datpil']	= $this->db->query("SELECT * FROM m_unitkerja WHERE id_unitkerja = ?", [$idu])->getRow();
			$a['page']		= "f_unitkerja";
		} else if ($mau_ke == "act_add") {
			$cek_user_exist = $this->db->query("SELECT id_unitkerja FROM m_unitkerja WHERE id_unitkerja = ?", [$id_unitkerja])->getNumRows();

			if (strlen($id_unitkerja) < 4) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID Unitkerja minimal 4 huruf</div>");
				return redirect()->to('admin/master_opd');
			} else if ($cek_user_exist > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">OPD telah ditambahkan. Ganti yang lain..!</div>");
				return redirect()->to('admin/master_opd');
			} else {
				$this->db->query("INSERT INTO m_unitkerja (id_unitkerja, unitkerja_ind, unitkerja_en, jenis_instansi, user_wali, user_spv) VALUES (?, ?, ?, ?, ?, ?)", [$id_unitkerja, $unitkerja_ind, $unitkerja_en, $jenis_instansi, $user_wali, $user_spv]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
				return redirect()->to('admin/master_opd');
			}
		} else if ($mau_ke == "act_edt") {
			$this->db->query("UPDATE m_unitkerja SET id_unitkerja = ?, unitkerja_ind = ?, unitkerja_en = ?, jenis_instansi = ?, user_wali = ?, user_spv = ? WHERE id_unitkerja = ?", [$id_unitkerja, $unitkerja_ind, $unitkerja_en, $jenis_instansi, $user_wali, $user_spv, $idp]);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated </div>");
			return redirect()->to('admin/master_opd');
		} else {
			$a['data']		= $this->db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind ASC")->getResult();
			$a['page']		= "l_unitkerja";
		}

		return view('admin/index', $a);
	}

	public function set_tahun($tahun = null)
	{
		if (!$tahun) {
			$tahun = date('Y');
		}

		$this->session->set('admin_ta', (int)$tahun);

		// 1. Cek parameter query redirect_to yang dikirim secara eksplisit oleh UI navbar
		$redirectTo = $this->request->getGet('redirect_to');
		if (!empty($redirectTo)) {
			$cleanPath = ltrim(urldecode($redirectTo), '/');
			// Keamanan: Pastikan internal path (bukan external url) dan bukan loop ke set_tahun/login
			if (!preg_match('#^(https?://|//)#i', $cleanPath) && 
				!str_contains($cleanPath, 'set_tahun') && 
				!str_contains($cleanPath, 'login')) {
				return redirect()->to(base_url($cleanPath));
			}
		}

		// 2. Fallback: Gunakan HTTP_REFERER bawaan browser
		$referer = $this->request->getServer('HTTP_REFERER');
		if (!empty($referer)) {
			$parsedPath = parse_url($referer, PHP_URL_PATH) ?? '';
			if (!str_contains($parsedPath, 'set_tahun') && !str_contains($parsedPath, 'login')) {
				return redirect()->to($referer);
			}
		}

		// 3. Fallback terakhir jika dibuka manual langsung dari address bar
		return redirect()->to('admin/index');
	}

	/**
	 * Cek apakah user yang login memiliki hak istimewa Admin BPS / Super Admin
	 */
	private function isAdminBps(): bool
	{
		if (!$this->session->get('admin_valid')) {
			return false;
		}
		$unitkerja = (string)$this->session->get('admin_unitkerja');
		$level = strtolower((string)$this->session->get('admin_level'));
		return ($unitkerja === 'bps' || in_array($level, ['admin', 'super admin', 'superadmin'], true));
	}

	/**
	 * Cek apakah user berwenang mengelola tabel tertentu (Admin BPS atau pemilik unit kerja tabel)
	 */
	private function canManageTable($tabel): bool
	{
		if (!$this->session->get('admin_valid')) {
			return false;
		}
		if ($this->isAdminBps()) {
			return true;
		}
		$unitkerja = (string)$this->session->get('admin_unitkerja');
		$tableUnitkerja = is_object($tabel) ? ($tabel->id_unitkerja ?? '') : ($tabel['id_unitkerja'] ?? '');
		return (!empty($unitkerja) && !empty($tableUnitkerja) && (string)$tableUnitkerja === $unitkerja);
	}

	public function act_update_isi()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$id_tabel = (int)$this->input->post('id_tabel');
		$catatan  = $this->input->post('catatan_periksa');
		$ta       = (int)($this->session->get('admin_ta') ?: date('Y'));

		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$ctx = $mTabelDinamis->resolveTableContext($id_tabel, $ta);
		$id_master = $ctx['id_tabel'] ?: $id_tabel;

		if (!$ctx['tabel'] || !$this->canManageTable($ctx['tabel'])) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">Akses ditolak! Anda hanya berwenang mengubah status tabel instansi Anda sendiri.</div>');
			return redirect()->back();
		}

		if ($id_master > 0) {
			$mTabelDinamis->upsertStatusTahunan($id_master, $ta, [
				'is_periksa'      => 1,
				'catatan_periksa' => $catatan
			]);
		}

		return redirect()->back()->with('k', '<div class="alert alert-success">Status berhasil diperbarui!</div>');
	}

	public function batal_isi($id)
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$id_tabel = (int)$id;
		$ta       = (int)($this->session->get('admin_ta') ?: date('Y'));
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$ctx = $mTabelDinamis->resolveTableContext($id_tabel, $ta);
		$id_master = $ctx['id_tabel'] ?: $id_tabel;

		if (!$ctx['tabel'] || !$this->canManageTable($ctx['tabel'])) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">Akses ditolak! Anda hanya berwenang membatalkan pengisian tabel instansi Anda sendiri.</div>');
			return redirect()->back();
		}

		if ($ctx['tabel']) {
			$link_tabel = $ctx['tahun_tabel']->link_tabel ?? '';
			$is_portal = (stripos($link_tabel, 'portal') !== false || 
			              stripos($link_tabel, 'satudata') !== false || 
			              !empty($ctx['tahun_tabel']->id_api));
			if (!empty($ctx['tabel']->is_dynamic) || $ctx['indikator'] !== null || $is_portal) {
				$this->session->setFlashdata('k', '<div class="alert alert-warning border-0 shadow-sm rounded-3 mb-3" id="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>Status pengisian Tabel Dinamis dan API Portal tidak dapat dibatalkan secara manual.</div>');
				return redirect()->back();
			}
		}

		$mTabelDinamis->upsertStatusTahunan($id_master, $ta, [
			'is_periksa'      => 0,
			'catatan_periksa' => '-'
		]);
		return redirect()->back();
	}

	public function batal_verif($id)
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		// Keamanan RBAC: Hanya akun dengan hak verifikasi (Super Admin, Admin, LO) yang berwenang membatalkan verifikasi
		if (!can('input_dda_verif')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">Akses ditolak! Anda tidak memiliki izin untuk membatalkan verifikasi data.</div>');
			return redirect()->back();
		}

		$id_tabel = (int)$id;
		$ta       = (int)($this->session->get('admin_ta') ?: date('Y'));
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$ctx = $mTabelDinamis->resolveTableContext($id_tabel, $ta);
		$id_master = $ctx['id_tabel'] ?: $id_tabel;

		$mTabelDinamis->upsertStatusTahunan($id_master, $ta, [
			'is_confirm'   => '0',
			'catatan'      => '-',
			'is_publish'   => 0,
			'published_at' => null
		]);

		$this->session->setFlashdata('k', '<div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center mb-3" id="alert" style="background-color: #fff3cd; color: #664d03;"><i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0 text-warning"></i><div><strong>Status Diperbarui:</strong> Verifikasi data berhasil dibatalkan. Tabel otomatis dikembalikan ke status <b>Draft</b>.</div></div>');
		return redirect()->back();
	}

	/**
	 * AJAX endpoint to toggle publish status of a table to the public frontend
	 */
	public function act_toggle_publish()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setJSON([
				'status' => 'error',
				'message' => 'Sesi login telah berakhir. Silakan login kembali.'
			])->setStatusCode(401);
		}

		// Keamanan RBAC: Hanya akun Admin dan Super Admin yang berwenang mempublikasikan tabel ke publik
		if (!can('input_dda_publish')) {
			return $this->response->setJSON([
				'status' => 'error',
				'message' => 'Akses ditolak! Hanya Admin dan Super Admin yang berwenang mengubah status publikasi tabel.'
			])->setStatusCode(403);
		}

		$id = (int)$this->request->getPost('id');
		$is_publish = (int)$this->request->getPost('is_publish');

		if ($id <= 0 || !in_array($is_publish, [0, 1])) {
			return $this->response->setJSON([
				'status' => 'error',
				'message' => 'Parameter tidak valid.'
			])->setStatusCode(400);
		}

		$ta = (int)($this->session->get('admin_ta') ?: date('Y'));
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$ctx = $mTabelDinamis->resolveTableContext($id, $ta);
		$tabel = $ctx['tabel'];
		$id_master = $ctx['id_tabel'] ?: $id;

		if (!$tabel) {
			return $this->response->setJSON([
				'status' => 'error',
				'message' => 'Data tabel tidak ditemukan.'
			])->setStatusCode(404);
		}

		$statusTahunan = $ctx['tahun_tabel'];

		// Validasi persyaratan tampil (hanya saat mengaktifkan publikasi / is_publish = 1)
		if ($is_publish === 1) {
			$is_dynamic = (($tabel->jenis_sumber ?? '') === 'api_kominfo') 
				? false 
				: (((!empty($tabel->jenis_sumber) && $tabel->jenis_sumber === 'dinamis') || ($ctx['indikator'] !== null)));

			// 1. Syarat Link: Harus memiliki tautan data (jika bukan tabel dinamis)
			if (!$is_dynamic && empty(trim($statusTahunan->link_tabel ?? ''))) {
				return $this->response->setJSON([
					'status' => 'error',
					'message' => 'Tabel belum memiliki tautan data (link kosong). Silakan lengkapi link terlebih dahulu sebelum mempublish.'
				])->setStatusCode(422);
			}

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
			if (!$is_filled) {
				return $this->response->setJSON([
					'status' => 'error',
					'message' => 'Tabel belum dapat dipublish karena status pengisian masih Belum Diisi.'
				])->setStatusCode(422);
			}

			// 3. Syarat Verifikasi: Harus sudah diverifikasi oleh BPS (is_confirm == 1)
			if ($statusTahunan->is_confirm != '1') {
				return $this->response->setJSON([
					'status' => 'error',
					'message' => 'Tabel belum dapat dipublish karena belum diverifikasi oleh BPS (status masih Menunggu Cek).'
				])->setStatusCode(422);
			}
		}

		$now = date('Y-m-d H:i:s');
		$updatePayload = [
			'is_publish'   => $is_publish,
			'published_at' => ($is_publish === 1) ? $now : null
		];
		if ($is_publish === 1 && !empty($is_filled)) {
			$updatePayload['is_periksa'] = 1;
		}
		$mTabelDinamis->upsertStatusTahunan($id_master, $ta, $updatePayload);

		$judul_singkat = !empty($tabel->no_tabel) ? 'Tabel ' . trim($tabel->no_tabel) : mb_strimwidth($tabel->judul_ind, 0, 30, '...');

		return $this->response->setJSON([
			'status' => 'success',
			'id' => $id,
			'is_publish' => $is_publish,
			'message' => $is_publish === 1 
				? "{$judul_singkat} berhasil dipublish ke frontend!" 
				: "{$judul_singkat} ditarik dari frontend (Draft)."
		]);
	}

	public function export_excel_instansi()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('report')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengunduh Laporan Rekap.</div>');
			return redirect()->to('admin');
		}

		error_reporting(0);
		ini_set('display_errors', 0);

		$ta = $this->session->get('admin_ta');
		if (empty($ta)) $ta = date('Y');
		$thDda = (string)$ta;
		$thData = ((int)$ta) - 1;
		$thDataStr = (string)$thData;

		header("Content-type: application/vnd-ms-excel");
		header("Content-Disposition: attachment; filename=Laporan_Instansi_$ta.xls");

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

		echo "<table border='1'>";
		echo "<tr>
            <th>Nama Instansi</th>
            <th>Total Tabel</th>
            <th>Belum Diisi</th>
            <th>Menunggu Validasi</th>
            <th>Sudah Validasi</th>
          </tr>";

		foreach ($data as $b) {
			echo "<tr>";
			echo "<td>" . $b->unitkerja_ind . "</td>";
			echo "<td>" . $b->jumlah_tabel . "</td>";
			echo "<td>" . $b->belum_isi . "</td>";
			echo "<td>" . $b->menunggu_validasi . "</td>";
			echo "<td>" . $b->sudah_validasi . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	public function export_excel_tim()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('report')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengunduh Laporan Rekap.</div>');
			return redirect()->to('admin');
		}

		error_reporting(0);
		ini_set('display_errors', 0);

		$ta = $this->session->get('admin_ta');
		if (empty($ta)) $ta = date('Y');
		$thDda = (string)$ta;
		$thData = ((int)$ta) - 1;
		$thDataStr = (string)$thData;

		header("Content-type: application/vnd-ms-excel");
		header("Content-Disposition: attachment; filename=Laporan_Tim_$ta.xls");

		$data = $this->db->query("
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
            LEFT JOIN t_status_tahunan tt ON tt.id_tabel = mt.id AND tt.tahun = ?
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

		echo "<table border='1'>";
		echo "<tr>
                <th>Nama Tim</th>
                <th>Total Tabel</th>
                <th>Belum Diisi</th>
                <th>Menunggu Validasi</th>
                <th>Sudah Validasi</th>
              </tr>";

		foreach ($data as $b) {
			echo "<tr>";
			echo "<td>" . strtoupper($b->user_wali) . "</td>";
			echo "<td>" . $b->jumlah_tabel . "</td>";
			echo "<td>" . $b->belum_isi . "</td>";
			echo "<td>" . $b->menunggu_validasi . "</td>";
			echo "<td>" . $b->sudah_validasi . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	// --- FITUR MASTER TIM (PENGELOLAAN TIM & ANGGOTA + OPD BINAAN) ---
	public function master_tim()
	{
		// Cek otentikasi login
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('master_tim')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengelola Master Tim.</div>');
			return redirect()->to('admin');
		}


		// Batasi hak akses hanya untuk Super Admin dan Admin (Diseminasi)
		$user_lvl = strtolower(trim((string)($this->session->get('admin_level') ?? '')));
		$admin_user = strtolower(trim((string)($this->session->get('admin_user') ?? '')));
		if (!in_array($user_lvl, ['super admin', 'superadmin', 'admin']) && $admin_user != 'diseminasi') {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Menu Master Tim hanya dapat diakses oleh Admin dan Super Admin.</div>");
			return redirect()->to('admin');
		}

		$mau_ke = $this->uri->segment(3);
		$idu = $this->uri->segment(4);

		// AKSI 1: TAMBAH TIM BARU
		if ($mau_ke == "act_add_tim") {
			$nama_tim = trim((string)$this->input->post('nama_tim'));
			$kode_tim = strtolower(trim(preg_replace('/\s+/', '', (string)$this->input->post('kode_tim'))));

			if (empty($nama_tim) || empty($kode_tim)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Nama Tim dan Kode Tim tidak boleh kosong!</div>");
				return redirect()->to('admin/master_tim');
			}

			// Cek apakah username / kode tim sudah ada
			$cek = $this->db->query("SELECT id FROM t_admin WHERE username = ?", [$kode_tim])->getNumRows();
			if ($cek > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Kode Tim '<strong>{$kode_tim}</strong>' sudah digunakan! Gunakan kode lain.</div>");
				return redirect()->to('admin/master_tim');
			}

			$this->db->query("INSERT INTO t_admin (username, password, nama, nip, level, id_unitkerja, email) VALUES (?, ?, ?, ?, ?, ?, ?)", [
				$kode_tim,
				md5('123456'),
				$nama_tim,
				'-',
				'lo',
				'bps',
				'-'
			]);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Tim baru <strong>{$nama_tim}</strong> ({$kode_tim}) berhasil ditambahkan.</div>");
			return redirect()->to('admin/master_tim');
		}

		// AKSI 2: HAPUS TIM
		else if ($mau_ke == "act_del_tim") {
			$id_tim = (int)$idu;
			$tim_row = $this->db->query("SELECT id, username, nama FROM t_admin WHERE id = ?", [$id_tim])->getRow();

			if (!$tim_row) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Data Tim tidak ditemukan!</div>");
				return redirect()->to('admin/master_tim');
			}

			// Validasi Keamanan: Cek apakah tim masih membina OPD di m_unitkerja
			$count_opd = (int)($this->db->query("SELECT COUNT(*) as c FROM m_unitkerja WHERE user_wali = ?", [$tim_row->username])->getRow()->c ?? 0);
			if ($count_opd > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal menghapus! Tim <strong>{$tim_row->nama}</strong> masih memiliki <strong>{$count_opd} OPD Binaan</strong>. Pindahkan penugasan OPD terlebih dahulu sebelum menghapus tim.</div>");
				return redirect()->to('admin/master_tim');
			}

			// Hapus keanggotaan di t_tim_anggota
			$this->db->query("DELETE FROM t_tim_anggota WHERE kode_tim = ?", [$tim_row->username]);
			// Hapus entitas tim di t_admin
			$this->db->query("DELETE FROM t_admin WHERE id = ?", [$id_tim]);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Tim <strong>{$tim_row->nama}</strong> berhasil dihapus.</div>");
			return redirect()->to('admin/master_tim');
		}

		// AKSI 3: TAMBAH ANGGOTA KE TIM
		else if ($mau_ke == "act_add_anggota") {
			$kode_tim = trim((string)$this->input->post('kode_tim'));
			$id_admin = (int)$this->input->post('id_admin');

			if (empty($kode_tim) || empty($id_admin)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Pilih tim dan pegawai dengan benar!</div>");
				return redirect()->to('admin/master_tim');
			}

			// Validasi ketat: Hanya user dengan level LO yang dapat ditambahkan ke tim
			$pegawai = $this->db->query("SELECT id, nama, level FROM t_admin WHERE id = ?", [$id_admin])->getRow();
			if (!$pegawai || strtolower(trim((string)$pegawai->level)) !== 'lo') {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal! Hanya pegawai dengan peran <strong>LO</strong> yang dapat ditambahkan ke dalam tim. Akun Administrator tidak dapat dimasukkan ke tim.</div>");
				return redirect()->to('admin/master_tim');
			}

			// Validasi Opsi B (Strict): 1 Personil LO hanya boleh bertugas di 1 tim
			$cek_existing = $this->db->query("SELECT ta.kode_tim, u.nama, t.nama as nama_tim 
				FROM t_tim_anggota ta 
				JOIN t_admin u ON ta.id_admin = u.id 
				LEFT JOIN t_admin t ON ta.kode_tim = t.username 
				WHERE ta.id_admin = ?", [$id_admin])->getRow();

			if ($cek_existing) {
				$nama_tim_asal = !empty($cek_existing->nama_tim) ? $cek_existing->nama_tim : strtoupper($cek_existing->kode_tim);
				if ($cek_existing->kode_tim == $kode_tim) {
					$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\"><strong>{$cek_existing->nama}</strong> sudah menjadi anggota tim ini!</div>");
				} else {
					$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal! <strong>{$cek_existing->nama}</strong> sudah terdaftar di <strong>{$nama_tim_asal}</strong>. Sesuai aturan, 1 LO hanya dapat bertugas di 1 tim. Silakan keluarkan dari tim sebelumnya terlebih dahulu jika ingin memindahkannya.</div>");
				}
				return redirect()->to('admin/master_tim');
			}

			$this->db->query("INSERT INTO t_tim_anggota (kode_tim, id_admin) VALUES (?, ?)", [$kode_tim, $id_admin]);
			$pegawai_nama = $this->db->query("SELECT nama FROM t_admin WHERE id = ?", [$id_admin])->getRow()->nama ?? 'Pegawai';

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\"><strong>{$pegawai_nama}</strong> berhasil ditambahkan ke tim <strong>{$kode_tim}</strong>.</div>");
			return redirect()->to('admin/master_tim');
		}

		// AKSI 4: KELUARKAN / HAPUS ANGGOTA DARI TIM
		else if ($mau_ke == "act_del_anggota") {
			$rel_id = (int)$idu;
			$rel_row = $this->db->query("SELECT ta.id, ta.kode_tim, u.nama FROM t_tim_anggota ta JOIN t_admin u ON ta.id_admin = u.id WHERE ta.id = ?", [$rel_id])->getRow();

			if ($rel_row) {
				$this->db->query("DELETE FROM t_tim_anggota WHERE id = ?", [$rel_id]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\"><strong>{$rel_row->nama}</strong> berhasil dikeluarkan dari {$rel_row->kode_tim}. Akun pegawai tetap aman.</div>");
			} else {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Data anggota tidak ditemukan!</div>");
			}
			return redirect()->to('admin/master_tim');
		}

		// TAMPILAN UTAMA: MASTER TIM (OPSI B: TAB ANGGOTA & OPD BINAAN)
		else {
			// 1. Ambil seluruh Tim terdaftar di t_admin
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

			// 2. Ambil staf BPS yang berstatus LO saja, serta status penugasan timnya (untuk Opsi B: 1 personil 1 tim)
			$staff_bps = $this->db->query("SELECT u.id, u.nama, u.nip, u.username, u.level, ta.kode_tim as assigned_tim, t.nama as assigned_tim_nama 
				FROM t_admin u 
				LEFT JOIN t_tim_anggota ta ON u.id = ta.id_admin 
				LEFT JOIN t_admin t ON ta.kode_tim = t.username 
				WHERE u.id_unitkerja = 'bps' 
				  AND LOWER(u.level) = 'lo' 
				  AND NOT (u.username LIKE 'tim%' OR u.nama LIKE 'Tim %') 
				ORDER BY (ta.kode_tim IS NOT NULL) ASC, u.nama ASC")->getResult();

			$a['teams']         = $teams_data;
			$a['staff_bps']     = $staff_bps;
			$a['total_tim']     = count($teams_data);
			$a['total_anggota'] = $total_anggota_count;
			$a['total_opd']     = $total_opd_count;
			$a['page']          = "l_master_tim";

			return view('admin/index', $a);
		}
	}

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

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_CONNECTTIMEOUT => 40,
			CURLOPT_SSL_VERIFYPEER => false, // Bypass SSL for local/gov compatibility
			CURLOPT_SSL_VERIFYHOST => 0,     // Bypass Hostname mismatch (penting jika domain backup pakai sertifikat domain utama)
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "", // Handle GZIP/Deflate
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Force IPv4 to avoid slow DNS
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $token,
				'Accept: application/json',
				'Cache-Control: no-cache'
			]
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($response === false) {
			return ['error' => 'CURL_FAIL: ' . curl_error($ch)];
		}

		if ($httpCode >= 400) {
			return ['error' => "HTTP_ERROR_{$httpCode}: " . substr($response, 0, 200)];
		}

		$json = json_decode($response, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return ['error' => 'JSON_PARSE_ERROR: ' . substr($response, 0, 100)];
		}

		return $json;
	}

	private function callApi($url)
	{
		return $this->callApi2($url);
	}

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

		$page = 1;
		$perPage = 20;

		do {
			$result = $this->callApi("https://satudata-backup.jatengprov.go.id/v1/data?page={$page}&per-page={$perPage}");

			if (!$result || !isset($result['data']) || empty($result['data'])) {
				break;
			}

			foreach ($result['data'] as $row) {
				if (!isset($row['id'], $row['judul'])) {
					continue;
				}

				$id    = $row['id'];
				$judul = $row['judul'];

				// ambil detail per ID
				$detail = $this->callApi("https://satudata-backup.jatengprov.go.id/v1/data/{$id}");

				$tahun = null;
				if (isset($detail['data']) && is_array($detail['data'])) {
					$tahunList = array_column($detail['data'], 'tahun_data');
					if (!empty($tahunList)) {
						$tahun = max($tahunList);
					}
				}

				try {
					$this->db->table('m_konfigurasi_api')
						->where('id_api', $id)
						->update([
							'tahun_tersedia' => (string)$tahun,
							'last_sync_at'   => date('Y-m-d H:i:s'),
							'updated_at'     => date('Y-m-d H:i:s'),
						]);
				} catch (\Exception $e) {
					log_message('error', 'Gagal update m_konfigurasi_api: ' . $e->getMessage());
				}
			}

			$page++;
		} while (!empty($result['data']));

		echo json_encode([
			'status' => true,
			'message' => 'Sinkronisasi data berhasil'
		]);
	}


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

		$page    = $this->request->getGet('page') ?? 1;
		$perPage = 20;

		// Ambil list data per halaman
		$result = $this->callApi("https://satudata-backup.jatengprov.go.id/v1/data?page={$page}&per-page={$perPage}");

		if (!$result || !isset($result['data']) || empty($result['data'])) {
			echo json_encode([
				'status'   => false,
				'message'  => $result['message'] ?? 'Data API kosong',
				'has_next' => false
			]);
			return;
		}

		foreach ($result['data'] as $row) {
			if (!isset($row['id'], $row['judul'])) {
				continue;
			}

			$id    = $row['id'];
			$judul = $row['judul'];

			// Ambil detail per ID
			$detail = $this->callApi("https://satudata-backup.jatengprov.go.id/v1/data/{$id}");

			$tahun = null;
			if (isset($detail['data']) && is_array($detail['data'])) {
				// Ambil tahun terbaru (max)
				$tahunList = array_column($detail['data'], 'tahun_data');
				if (!empty($tahunList)) {
					$tahun = max($tahunList);
				}
			}

			// Update m_konfigurasi_api
			try {
				$this->db->table('m_konfigurasi_api')
					->where('id_api', $id)
					->update([
						'tahun_tersedia' => (string)$tahun,
						'last_sync_at'   => date('Y-m-d H:i:s'),
						'updated_at'     => date('Y-m-d H:i:s'),
					]);
			} catch (\Exception $e) {
				log_message('error', 'Gagal update m_konfigurasi_api: ' . $e->getMessage());
			}
		}

		// Hitung total halaman dari metadata API (fallback manual)
		$total     = $result['meta']['total'] ?? 1000;
		$totalPage = ceil($total / $perPage);

		echo json_encode([
			'status'       => true,
			'message'      => "Halaman {$page} selesai",
			'has_next'     => $page < $totalPage,
			'next_page'    => $page + 1,
			'current_page' => $page,
			'total_page'   => $totalPage
		]);
	}

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

		$list = $this->db->table('m_konfigurasi_api')
			->select('id_api')
			->where('id_api IS NOT NULL')
			->where('id_api !=', '')
			->get()
			->getResultArray();

		$updated = 0;

		foreach ($list as $row) {
			$id_api = $row['id_api'];
			$detail = $this->callApi(
				"https://satudata-backup.jatengprov.go.id/v1/data/{$id_api}"
			);

			if (!isset($detail['data']) || !is_array($detail['data'])) {
				continue;
			}

			$tahunList = array_unique(array_filter(array_column($detail['data'], 'tahun_data')));
			if (!empty($tahunList)) {
				sort($tahunList);
				$tahunStr = implode(',', $tahunList);
				$this->db->table('m_konfigurasi_api')
					->where('id_api', $id_api)
					->update([
						'tahun_tersedia' => $tahunStr,
						'last_sync_at'   => date('Y-m-d H:i:s'),
						'updated_at'     => date('Y-m-d H:i:s')
					]);
				$updated++;
			}
		}

		echo json_encode([
			'status'  => true,
			'updated' => $updated
		]);
	}


	/**
	 * Menampilkan tabel data dari Portal Data Jawa Tengah via API
	 * URL: admin/view_portal_tabel?id={id_api}
	 */
	/**
	 * Menampilkan tabel data dari Portal Data Jawa Tengah via API
	 * URL: admin/view_portal_tabel?id={id_api}
	 * Mendukung Multiple ID dipisahkan koma (misal: ?id=uuid1,uuid2)
	 */
	public function view_portal_tabel($seg_id = null)
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$ta = $this->session->get('admin_ta');
		if (empty($ta)) {
			$ta = date('Y');
		}
		$thDda = (int)$ta;
		$thTargetData = $thDda - 1;
		$id_api = $this->request->getGet('id') ?: $this->request->getGet('id_api');
		if (empty($id_api) && !empty($seg_id)) {
			$id_api = $seg_id;
		}
		if (empty($id_api)) {
			$uri = service('uri');
			$seg3 = $uri->getTotalSegments() >= 3 ? $uri->getSegment(3) : null;
			$seg4 = $uri->getTotalSegments() >= 4 ? $uri->getSegment(4) : null;
			$id_api = (!empty($seg3) && $seg3 !== 'view_portal_tabel') ? $seg3 : (!empty($seg4) ? $seg4 : null);
		}
		$table_id = (int)($this->request->getGet('table_id') ?: $this->request->getGet('id_db') ?: 0);
		if (empty($id_api)) {
			$a['page'] = "d_amain";
			$this->session->setFlashdata("k", '<div class="alert alert-danger" id="alert">ID Portal Data tidak ditemukan</div>');
			return view('admin/index', $a);
		}

		// Support multiple IDs (comma separated)
		$ids = explode(',', $id_api);

		// Map id_api_new jika ID berupa UUID lama
		$cleaned_ids = [];
		foreach ($ids as $id_val) {
			$id_val = trim($id_val);
			if (empty($id_val)) continue;

			if (!is_numeric($id_val)) {
				$match = null;
				if ($this->db->tableExists('t_tabel_match')) {
					$match = $this->db->table('t_tabel_match')
						->select('id_api_new')
						->where('id_api', $id_val)
						->get()
						->getRowArray();
				}
				$cleaned_ids[] = (!empty($match['id_api_new'])) ? (string)$match['id_api_new'] : $id_val;
			} else {
				$cleaned_ids[] = $id_val;
			}
		}
		if (!empty($cleaned_ids)) {
			$ids = $cleaned_ids;
		}
		$new_ids_str = implode(',', $ids);

		// Redirect jika ada ID UUID lama yang dikonversi
		if ($new_ids_str !== trim($id_api) && !empty($new_ids_str)) {
			$redir = "admin/view_portal_tabel?id=" . $new_ids_str;
			if ($table_id > 0) $redir .= "&table_id=" . $table_id;
			return redirect()->to($redir);
		}

		// Sekarang $ids sudah berisi id_api_new, langsung proses
		$all_results = [];
		$api_error_msgs = [];

		foreach ($ids as $id_p) {
			$id_p = trim($id_p);
			if (empty($id_p)) continue;

			// --- CLEANING ID ---
			if (strpos($id_p, '?') !== false) {
				$id_p = explode('?', $id_p)[0];
			}
			if (strpos($id_p, 'data/') !== false) {
				$id_p = explode('data/', $id_p)[1];
			}
			$id_p = trim($id_p, '/ ');

			$page = 1;
			$all_rows = [];
			$meta_info = null;
			$first_row_ids = [];

			do {
				$offset = ($page - 1) * 100;
				$url = "https://satudata.jatengprov.go.id/api/v1/data/{$id_p}?peruntukan=DDA";
				$res = $this->callApi2($url);

				if ($res && !isset($res['error'])) {
					$rows = [];
					if (isset($res['data'])) {
						$rows = $res['data'];
					} else if (is_array($res) && isset($res[0])) {
						$rows = $res;
					}

					if (empty($rows)) {
						break;
					}

					$row_fingerprint = md5(json_encode($rows[0]));
					if (in_array($row_fingerprint, $first_row_ids)) {
						break;
					}
					$first_row_ids[] = $row_fingerprint;

					$all_rows = array_merge($all_rows, $rows);

					if ($page === 1) {
						$meta_info = $res;
					}

					$total_pages = $res['pagination']['total_pages']
						?? $res['_meta']['pageCount']
						?? $res['total_pages']
						?? $res['pages']
						?? $res['total_page']
						?? null;

					if ($total_pages !== null && $page >= $total_pages) {
						break;
					}

					if (count($rows) < 100) {
						break;
					}
					$page++;
				} else {
					if (isset($res['error'])) {
						$api_error_msgs[] = "ID [{$id_p}]: " . $res['error'];
					}
					break;
				}
			} while ($page <= 200);

			if (!empty($all_rows)) {
				// Auto-sync available years to m_konfigurasi_api if changed or empty
				$extracted_years = [];
				foreach ($all_rows as $row_item) {
					$y = $row_item['tahun_data'] ?? $row_item['tahun'] ?? $row_item['year'] ?? $row_item['periode'] ?? null;
					if (!empty($y) && is_numeric($y) && strlen((string)$y) === 4) {
						$extracted_years[(int)$y] = true;
					}
				}
				if (!empty($extracted_years)) {
					ksort($extracted_years);
					$tahun_str = implode(',', array_keys($extracted_years));
					$this->db->query("
						UPDATE m_konfigurasi_api 
						SET tahun_tersedia = ?, last_sync_at = NOW() 
						WHERE (link_api REGEXP ? OR id_api = ?) AND (tahun_tersedia IS NULL OR tahun_tersedia != ?)
					", [$tahun_str, 'id=' . $id_p . '([^0-9]|$)', $id_p, $tahun_str]);
				}

				$dda_info = null;
				if ($table_id > 0 && $this->db->tableExists('t_tahun_tabel')) {
					$dda_info = $this->db->query("
						SELECT t.id, t.no_tabel, t.periode_id, t.periode_en, m.judul_ind, m.judul_en, t.config_tabel, u.unitkerja_ind, u.unitkerja_en
						FROM t_tahun_tabel t JOIN m_list_tabel m ON t.id_tabel = m.id 
						LEFT JOIN m_unitkerja u ON m.id_unitkerja = u.id_unitkerja
						WHERE t.id = ?
						LIMIT 1
					", [$table_id])->getRow();
				}
				if (!$dda_info && $this->db->tableExists('t_tahun_tabel')) {
					$dda_info = $this->db->query("
						SELECT t.id, t.no_tabel, t.periode_id, t.periode_en, m.judul_ind, m.judul_en, t.config_tabel, u.unitkerja_ind, u.unitkerja_en
						FROM t_tahun_tabel t JOIN m_list_tabel m ON t.id_tabel = m.id 
						LEFT JOIN m_unitkerja u ON m.id_unitkerja = u.id_unitkerja
						WHERE t.link_tabel REGEXP ?
						LIMIT 1
					", ['id=' . $id_p . '([^0-9]|$)'])->getRow();
				}
				if (!$dda_info && $this->db->tableExists('m_konfigurasi_api')) {
					if ($table_id > 0) {
						$dda_info = $this->db->query("
							SELECT m.id, m.no_tabel, COALESCE(ka.tahun_tersedia, '') as periode_id, '' as periode_en, m.judul_ind, m.judul_en, ka.config_tabel, u.unitkerja_ind, u.unitkerja_en
							FROM m_list_tabel m 
							LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
							LEFT JOIN m_unitkerja u ON m.id_unitkerja = u.id_unitkerja
							WHERE m.id = ?
							LIMIT 1
						", [$table_id])->getRow();
					}
					if (!$dda_info) {
						$dda_info = $this->db->query("
							SELECT m.id, m.no_tabel, COALESCE(ka.tahun_tersedia, '') as periode_id, '' as periode_en, m.judul_ind, m.judul_en, ka.config_tabel, u.unitkerja_ind, u.unitkerja_en
							FROM m_list_tabel m 
							LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
							LEFT JOIN m_unitkerja u ON m.id_unitkerja = u.id_unitkerja
							WHERE ka.link_api REGEXP ? OR ka.id_api = ?
							LIMIT 1
						", ['id=' . $id_p . '([^0-9]|$)', $id_p])->getRow();
					}
				}

				$final_res = $meta_info;
				$final_res['data']           = $all_rows;
				$final_res['res_id']         = $dda_info->id ?? 0;
				$final_res['no_tabel']       = $dda_info->no_tabel ?? '';
				$years_portal = !empty($extracted_years)
					? array_keys($extracted_years)
					: array_filter(array_map('trim', explode(',', $dda_info->periode_id ?? '')));

				$title_ind_base = $dda_info->judul_ind ?? '';
				$title_en_base  = !empty($dda_info->judul_en) ? $dda_info->judul_en : $title_ind_base;

				if (function_exists('formatDynamicTableTitle') && !empty($years_portal)) {
					$final_res['dda_title']    = formatDynamicTableTitle($title_ind_base, $years_portal, 'id');
					$final_res['dda_title_en'] = formatDynamicTableTitle($title_en_base, $years_portal, 'en');
				} else {
					$final_res['dda_title']    = format_judul_tabel($title_ind_base, $dda_info->periode_id ?? '', 'id');
					$final_res['dda_title_en'] = format_judul_tabel($title_en_base, !empty($dda_info->periode_en) ? $dda_info->periode_en : ($dda_info->periode_id ?? ''), 'en');
				}

				$final_res['base_title_ind'] = $title_ind_base;
				$final_res['base_title_en']  = $title_en_base;
				$final_res['config_tabel']   = $dda_info->config_tabel ?? null;
				$final_res['unitkerja_ind']  = $dda_info->unitkerja_ind ?? '-';
				$final_res['unitkerja_en']   = $dda_info->unitkerja_en ?? '-';
				$final_res['id_api']         = $id_p;

				$all_results[] = $final_res;
			}
		}

		$a['api_results']    = $all_results;
		$a['api_errors']     = $api_error_msgs;
		$a['id_api']         = $id_api;
		$a['table_id']       = $table_id ?? null;
		$a['ta']             = $thDda;
		$a['th_target_data'] = $thTargetData;
		$a['page']           = "v_portal_tabel";

		return view('admin/index', $a);
	}

	/**
	 * Fitur Bulk Update Link Portal dari file CSV mapping
	 * Format CSV (semicolon): Judul;ID_Portal1,ID_Portal2
	 */

	/**
	 * Download Template untuk Bulk Portal Update (Format CSV untuk Excel)
	 */
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

		// Add BOM for Excel UTF-8 support
		$csv  = "\xEF\xBB\xBF";
		$csv .= "No Tabel / Judul / ID DDA;ID_Portal_Data\n";
		$csv .= "1.1.1;0f918215-695c-4167-9e09-9d79e01f918d\n";
		$csv .= "Contoh Nama Judul Tabel;uuid-data-1,uuid-data-2,uuid-data-3\n";

		return $this->response->setBody($csv)
			->setHeader('Content-Type', 'text/csv')
			->setHeader('Content-Disposition', 'attachment; filename=' . $filename);
	}

	/**
	 * Preview Bulk Portal Update dari File CSV (Separator Titik Koma atau Koma)
	 */
	public function preview_bulk_portal()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}
		if (!can('master_tabel')) {
			return redirect()->to('admin')->with('k', '<div class="alert alert-danger">Akses ditolak!</div>');
		}

		$ta_input = trim((string)($this->request->getPost('tahun') ?? ''));
		$ta       = !empty($ta_input) ? $ta_input : ($this->session->get('admin_ta') ?: date('Y'));

		$file = $this->request->getFile('file_mapping');
		if (!$file || !$file->isValid()) {
			return redirect()->back()->with('k', '<div class="alert alert-danger">File tidak valid atau tidak terbaca!</div>');
		}

		$handle = fopen($file->getTempName(), "r");

		// Deteksi delimiter otomatis (; atau ,)
		$first_line = fgets($handle);
		$delimiter  = (substr_count((string)$first_line, ';') >= substr_count((string)$first_line, ',')) ? ';' : ',';
		rewind($handle);

		// Skip header
		fgetcsv($handle, 2000, $delimiter);

		$list_data = [];
		while (($row = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
			$input_key = trim($row[0] ?? '');
			$ids       = trim($row[1] ?? '');

			if (empty($input_key)) continue;

			// Cek apakah input adalah ID, Nomor Tabel, atau Judul
			$check = $this->db->query("
				SELECT m.id, m.judul_ind, m.no_tabel, COALESCE(ka.tahun_tersedia, '') as periode_id, ? as tahun 
				FROM m_list_tabel m 
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
				WHERE m.is_active = 1 AND (m.id = ? OR TRIM(m.no_tabel) = TRIM(?) OR LOWER(TRIM(m.judul_ind)) = LOWER(TRIM(?)))
				ORDER BY (m.id = ?) DESC, (TRIM(m.no_tabel) = TRIM(?)) DESC, m.id ASC
				LIMIT 1
			", [$ta, $input_key, $input_key, $input_key, $input_key, $input_key])->getRow();

			$list_data[] = [
				'input_key' => $input_key,
				'judul'     => $check ? $check->judul_ind : $input_key,
				'no_tabel'  => $check ? $check->no_tabel : '',
				'id_tabel'  => $check ? $check->id : null,
				'ids'       => $ids,
				'exists'    => ($check ? true : false)
			];
		}
		fclose($handle);

		// Ambil list semua tabel pada tahun aktif untuk fallback dropdown di VIEW
		$a['all_tables'] = $this->db->query("
			SELECT m.id, m.no_tabel, COALESCE(ka.tahun_tersedia, '') as periode_id, m.judul_ind, ? as tahun 
			FROM m_list_tabel m 
			LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
			WHERE m.is_active = 1 
			ORDER BY CAST(SUBSTRING_INDEX(m.no_tabel, '.', 1) AS UNSIGNED) ASC, 
			         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(m.no_tabel,'.0'), '.', 2), '.', -1) AS UNSIGNED) ASC, 
			         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(m.no_tabel,'.0.0'), '.', 3), '.', -1) AS UNSIGNED) ASC, 
			         m.judul_ind ASC
		", [$ta])->getResult();
		$a['ta']           = $ta;
		$a['data_preview'] = $list_data;
		$a['page']         = "v_preview_bulk";

		return view('admin/index', $a);
	}

	/**
	 * Simpan hasil Bulk Update setelah dikonfirmasi dari Preview
	 */
	public function bulk_portal_save()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}
		if (!can('master_tabel')) {
			return redirect()->to('admin')->with('k', '<div class="alert alert-danger">Akses ditolak!</div>');
		}

		$selected_indices = $this->request->getPost('id_selected'); // Ini array index (0, 1, 2...)
		$id_tabels        = $this->request->getPost('id_tabel_final'); // Array [index => id_tabel]
		$portal_ids_list  = $this->request->getPost('portal_ids'); // Array [index => portal_ids]

		if (empty($selected_indices)) {
			return redirect()->to('admin/master_tabel')->with('k', '<div class="alert alert-danger">Tidak ada data yang dipilih untuk diupdate.</div>');
		}

		$ta = (int)($this->session->get('admin_ta') ?: date('Y'));
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$count = 0;

		foreach ($selected_indices as $index) {
			$id_tabel   = (int)($id_tabels[$index] ?? 0);
			$raw_portal = trim((string)($portal_ids_list[$index] ?? ''));

			if (empty($id_tabel) || empty($raw_portal)) continue;

			// Bersihkan portal ID jika user memasukkan link lengkap atau ada spasi/kutip
			if (preg_match('/view_portal_tabel\?id=([^&]+)/i', $raw_portal, $match)) {
				$clean_ids = trim($match[1]);
			} else {
				$clean_ids = trim($raw_portal, " \t\n\r\0\x0B/\"'");
			}

			if (empty($clean_ids)) continue;

			$new_link = "index.php/admin/view_portal_tabel?id=" . $clean_ids;

			$existKa = $this->db->table('m_konfigurasi_api')->where('id_tabel', $id_tabel)->get()->getRow();
			if ($existKa) {
				$this->db->table('m_konfigurasi_api')->where('id', $existKa->id)->update([
					'id_api'     => $clean_ids,
					'link_api'   => $new_link,
					'updated_at' => date('Y-m-d H:i:s'),
				]);
			} else {
				$this->db->table('m_konfigurasi_api')->insert([
					'id_tabel'   => $id_tabel,
					'id_api'     => $clean_ids,
					'link_api'   => $new_link,
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s'),
				]);
			}

			$thDataStr = (string)($ta - 1);
			$tahunTersedia = (string)($existKa->tahun_tersedia ?? '');
			$hasYear = ($tahunTersedia !== '' && (in_array($thDataStr, array_map('trim', explode(',', $tahunTersedia)), true) || strpos($tahunTersedia, $thDataStr) !== false));
			$mTabelDinamis->upsertStatusTahunan($id_tabel, $ta, ['is_periksa' => $hasYear ? 1 : 0]);
			$count++;
		}

		return redirect()->to('admin/master_tabel')->with('k', '<div class="alert alert-success">Berhasil memperbarui ' . $count . ' tabel ke Portal Data.</div>');
	}

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

			$tabelRow = null;
			if ($this->db->tableExists('m_list_tabel')) {
				$tabelRow = $this->db->table('m_list_tabel')->where('id', $id)->get()->getRow();
			}
			if (!$tabelRow && $this->db->tableExists('t_tahun_tabel')) {
				$tt = $this->db->table('t_tahun_tabel')->where('id', $id)->get()->getRow();
				if ($tt && !empty($tt->id_tabel)) {
					$tabelRow = $this->db->table('m_list_tabel')->where('id', $tt->id_tabel)->get()->getRow();
				}
			}
			if ($tabelRow && method_exists($this, 'canManageTable') && !$this->canManageTable($tabelRow)) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak'])->setStatusCode(403);
			}

			// Simpan ke m_konfigurasi_api jika tabel ada
			if ($this->db->tableExists('m_konfigurasi_api')) {
				$existKa = $this->db->table('m_konfigurasi_api')->where('id_tabel', $id)->get()->getRow();
				if ($existKa) {
					$this->db->table('m_konfigurasi_api')->where('id', $existKa->id)->update([
						'config_tabel' => $config,
						'updated_at'   => date('Y-m-d H:i:s'),
					]);
				} else {
					$this->db->table('m_konfigurasi_api')->insert([
						'id_tabel'     => $id,
						'config_tabel' => $config,
						'created_at'   => date('Y-m-d H:i:s'),
						'updated_at'   => date('Y-m-d H:i:s'),
					]);
				}
			}

			// Simpan ke t_tahun_tabel jika tabel ada
			if ($this->db->tableExists('t_tahun_tabel')) {
				$this->db->table('t_tahun_tabel')->where('id', $id)->update(['config_tabel' => $config]);
			}

			// Dapatkan nomor tabel dan tahun aktif
			$ta = (string)($this->session->get('admin_ta') ?: '2026');
			$no_tabel = '';

			if ($this->db->tableExists('t_tahun_tabel')) {
				$ttRow = $this->db->table('t_tahun_tabel')->select('id_tabel, tahun, no_tabel')->where('id', $id)->get()->getRowArray();
				if ($ttRow) {
					if (!empty($ttRow['tahun'])) $ta = (string)$ttRow['tahun'];
					if (!empty($ttRow['no_tabel'])) $no_tabel = (string)$ttRow['no_tabel'];
					if ($this->db->tableExists('m_konfigurasi_api') && !empty($ttRow['id_tabel'])) {
						$this->db->table('m_konfigurasi_api')->where('id_tabel', $ttRow['id_tabel'])->update(['config_tabel' => $config]);
					}
				}
			} elseif ($this->db->tableExists('m_list_tabel')) {
				$mRow = $this->db->table('m_list_tabel')->select('no_tabel')->where('id', $id)->get()->getRowArray();
				if ($mRow && !empty($mRow['no_tabel'])) {
					$no_tabel = (string)$mRow['no_tabel'];
				}
			}

			// Sinkronkan ke dda_db_{ta}.json jika ada
			$uploadPath = WRITEPATH . 'uploads/';
			$dbJson = $uploadPath . "dda_db_{$ta}.json";
			if (file_exists($dbJson)) {
				$dbList = json_decode(@file_get_contents($dbJson), true);
				if (is_array($dbList)) {
					$changed = false;
					foreach ($dbList as &$row) {
						if ((int)($row['id_db'] ?? 0) === (int)$id) {
							$row['config_tabel'] = $config;
							$row['has_config'] = 1;
							$changed = true;
							break;
						}
					}
					unset($row);
					if ($changed) {
						@file_put_contents($dbJson, json_encode($dbList, JSON_UNESCAPED_UNICODE));
					}
				}
			}

			// Sinkronkan juga ke dda_matched_{ta}.json has_config
			$matchedJson = $uploadPath . "dda_matched_{$ta}.json";
			if (file_exists($matchedJson)) {
				$matchedData = json_decode(@file_get_contents($matchedJson), true);
				if (is_array($matchedData)) {
					$listKey = isset($matchedData['matched_in_pdf']) ? 'matched_in_pdf' : 'matched_tables';
					if (isset($matchedData[$listKey]) && is_array($matchedData[$listKey])) {
						$mChanged = false;
						foreach ($matchedData[$listKey] as &$mRow) {
							if ((int)($mRow['id_db'] ?? 0) === (int)$id) {
								$mRow['has_config'] = 1;
								$mRow['config_tabel'] = $config;
								$mChanged = true;
								break;
							}
						}
						unset($mRow);
						if ($mChanged) {
							@file_put_contents($matchedJson, json_encode($matchedData, JSON_UNESCAPED_UNICODE));
						}
					}
				}
			}

			// Invalidate state cache untuk tabel ini agar bisa divalidasi ulang dengan config terbaru
			$stateJson = $uploadPath . "dda_state_{$ta}.json";
			if (file_exists($stateJson) && !empty($no_tabel)) {
				$stateData = json_decode(@file_get_contents($stateJson), true);
				if (is_array($stateData) && isset($stateData[$no_tabel])) {
					unset($stateData[$no_tabel]);
					@file_put_contents($stateJson, json_encode($stateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
				}
			}

			return $this->response->setJSON(['status' => 'success', 'message' => 'Konfigurasi berhasil disimpan']);
		}
		return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid request']);
	}



	// =========================================================================
	// FUNGSI RESET / KOSONGKAN TABEL PADA TAHUN TERTENTU
	// Menghapus data status dan nilai pada tahun target tanpa menghapus kamus master m_list_tabel & dimensi
	// =========================================================================
	public function reset_tahun()
	{
		// Cek otentikasi login admin
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		// Keamanan tingkat 1: Hak akses dibatasi hanya untuk Super Admin
		$user_lvl = strtolower(trim($this->session->get('admin_level') ?? ''));
		if (!in_array($user_lvl, ['super admin', 'superadmin'])) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Hanya Super Admin yang berhak mengosongkan tabel tahunan.</div>");
			return redirect()->to('admin/master_tabel');
		}

		// Ambil variabel post dari form modal konfirmasi
		$target_tahun = trim($this->input->post('target_tahun') ?? '');
		$confirm_text = trim($this->input->post('confirm_text') ?? '');

		// Validasi target tahun
		if (empty($target_tahun)) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Tahun target tidak valid!</div>");
			return redirect()->to('admin/master_tabel');
		}

		// Keamanan tingkat 2: Type-to-Confirm (User wajib mengetik kata 'HAPUS <TAHUN>')
		// Mencegah klik tombol hapus yang tidak disengaja
		$expected = "HAPUS " . $target_tahun;
		if (strtoupper($confirm_text) !== strtoupper($expected)) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Teks konfirmasi tidak cocok! Anda mengetik '{$confirm_text}', seharusnya '{$expected}'. Tindakan dibatalkan demi keamanan data.</div>");
			return redirect()->to('admin/master_tabel');
		}

		// Hitung jumlah data tabel pada status tahunan tersebut
		$count = $this->db->query("SELECT COUNT(*) as c FROM t_status_tahunan WHERE tahun = ?", [$target_tahun])->getRow()->c ?? 0;

		if ($count == 0) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\">Tidak ada status tabel yang terdaftar pada Tahun {$target_tahun}.</div>");
			return redirect()->to('admin/master_tabel');
		}

		// Eksekusi pembersihan status operasional tahunan di dalam database transaction
		// Master tabel pada m_list_tabel dan konfigurasi perakitan m_indikator_tabel tetap aman dan tidak terhapus
		$this->db->transStart();

		// 1. Hapus nilai dinamis HANYA untuk tahun data publikasi ini (tahun_data = target_tahun - 1)
		$tahunDataTarget = (int)$target_tahun - 1;
		$this->db->query("DELETE FROM t_nilai_dinamis WHERE tahun_data = ?", [$tahunDataTarget]);

		// 2. Hapus entri data status tabel tahunan untuk tahun tersebut
		$this->db->query("DELETE FROM t_status_tahunan WHERE tahun = ?", [$target_tahun]);

		$this->db->transComplete();

		if ($this->db->transStatus() === false) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal mengosongkan status tabel pada Tahun {$target_tahun} karena terjadi kesalahan transaksi database.</div>");
		} else {
			// Berikan notifikasi sukses
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\"><strong>Berhasil!</strong> Sebanyak {$count} status operasional tabel pada DDA Tahun {$target_tahun} telah berhasil dikosongkan. Kamus tabel master dan konfigurasi matriks tetap utuh.</div>");
		}
		return redirect()->to('admin/master_tabel');
	}

	// =========================================================================
	// FUNGSI RESEQUENCE (PENOMORAN ULANG OTOMATIS)
	// Merapikan urutan nomor tabel pada bab tertentu agar berurutan (misal: 1.1.1, 1.1.2, dst.)
	// =========================================================================
	public function resequence_tabel()
	{
		// Cek otentikasi login admin
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}


		// Keamanan: Hak akses dibatasi hanya untuk Super Admin
		$user_lvl = strtolower(trim($this->session->get('admin_level') ?? ''));
		if (!in_array($user_lvl, ['super admin', 'superadmin'])) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Fitur Rapikan Urutan (Resequence) hanya dapat diakses oleh Super Admin.</div>");
			return redirect()->to('admin/master_tabel');
		}

		// Ambil variabel post: prefix bab dan tahun target
		$prefix = trim((string)($this->input->post('prefix') ?? ''));
		$target_tahun = trim((string)($this->input->post('tahun') ?? ''));
		$tahun = !empty($target_tahun) ? $target_tahun : ($this->session->get("admin_ta") ?: date("Y"));

		// Validasi: prefix tidak boleh kosong
		if (empty($prefix)) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Awalan Bab tidak boleh kosong!</div>");
			return redirect()->back();
		}

		// Validasi format awalan:
		// 1. Jika mencakup Bab dan Subbab (misal 1.2 atau 4.1): diizinkan langsung
		// 2. Jika hanya Bab (misal 8 atau 11): diizinkan untuk Bab 2-tingkat murni (Bab.Nomor)
		$prefix_clean = trim($prefix, '.');
		if (preg_match('/^\d+(\.\d+)+$/', $prefix_clean)) {
			// Format subbab valid (misal 1.1, 4.2)
			$prefix = $prefix_clean . '.';
		} else if (preg_match('/^\d+$/', $prefix_clean)) {
			// User hanya memasukkan angka Bab (misal 8 atau 11)
			// Cek apakah bab ini merupakan Bab 2-tingkat murni atau 3-tingkat
			$all_bab_tables = $this->db->query(
				"SELECT no_tabel FROM m_list_tabel WHERE is_active = 1 AND no_tabel LIKE ? ORDER BY id ASC",
				[$prefix_clean . '.%']
			)->getResult();

			if (empty($all_bab_tables)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\">Tidak ditemukan tabel berawalan Bab $prefix_clean pada master tabel.</div>");
				return redirect()->back();
			}

			$count_3tier = 0;
			foreach ($all_bab_tables as $tb) {
				if (substr_count(trim((string)$tb->no_tabel), '.') >= 2) {
					$count_3tier++;
				}
			}

			if ($count_3tier > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Bab {$prefix_clean} memiliki subbab (contoh: {$prefix_clean}.1.1). Silakan masukkan awalan hingga subbab (Contoh: {$prefix_clean}.1 atau {$prefix_clean}.2).</div>");
				return redirect()->back();
			}

			$prefix = $prefix_clean . '.';
		} else {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Format Awalan Bab tidak valid! Gunakan angka (Contoh: 1.2 untuk Bab 3-tingkat atau 8/11 untuk Bab 2-tingkat).</div>");
			return redirect()->back();
		}

		// Ambil semua tabel yang memiliki prefix tersebut pada master tabel,
		// diurutkan secara hirarki numerik agar urutan asli tetap terjaga
		$tables = $this->db->query("SELECT id, no_tabel FROM m_list_tabel WHERE is_active = 1 AND no_tabel LIKE ? ORDER BY CAST(SUBSTRING_INDEX(no_tabel, '.', 1) AS UNSIGNED) ASC, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(no_tabel,'.0'), '.', 2), '.', -1) AS UNSIGNED) ASC, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(no_tabel,'.0.0'), '.', 3), '.', -1) AS UNSIGNED) ASC, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(no_tabel,'.0.0.0'), '.', 4), '.', -1) AS UNSIGNED) ASC, id ASC", [$prefix . '%'])->getResult();
		
		if (empty($tables)) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\">Tidak ditemukan tabel berawalan $prefix pada master tabel.</div>");
			return redirect()->back();
		}

		$updates = 0;
		$current_num = 1;
		$last_orig_no  = null;

		// Looping untuk memperbarui nomor tabel secara berurutan di dalam transaksi database
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

		if ($this->db->transStatus() === false) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal merapikan urutan tabel karena terjadi kesalahan transaksi database.</div>");
		} else {
			$prefix_msg = rtrim($prefix, '.');
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Berhasil merapikan urutan (Resequence) pada $updates tabel untuk bab $prefix_msg di Master Tabel.</div>");
		}
		return redirect()->to('admin/master_tabel');
	}

	// =========================================================================
	// MODUL DATA VERIFIER AI (MATCHING & AUDIT PDF DDA)
	// =========================================================================

	/**
	 * Mendeteksi binary Python yang valid secara lintas-OS (Windows: python, Linux: python3/python)
	 */
	private function getPythonBin(): string
	{
		$custom = getenv('PYTHON_BIN');
		if (!empty($custom)) {
			return $custom;
		}

		if (PHP_OS_FAMILY === 'Windows') {
			return 'python';
		}

		// Cache detection pada request yang sama agar tidak exec berulang
		static $detected = null;
		if ($detected !== null) {
			return $detected;
		}

		if (function_exists('shell_exec')) {
			$whichPy3 = @shell_exec('which python3 2>/dev/null');
			if (!empty(trim((string)$whichPy3))) {
				$detected = 'python3';
				return $detected;
			}
			$whichPy = @shell_exec('which python 2>/dev/null');
			if (!empty(trim((string)$whichPy))) {
				$detected = 'python';
				return $detected;
			}
		}

		$detected = 'python3';
		return $detected;
	}

	/**
	 * Menjalankan background worker Python secara asinkron lintas OS (Windows / Linux)
	 */
	private function runBackgroundPython(string $scriptPath, array $args = [], string $errLog = ''): bool
	{
		$pyBin = $this->getPythonBin();
		$escapedArgs = array_map('escapeshellarg', $args);
		$argsStr = implode(' ', $escapedArgs);
		$scriptEscaped = escapeshellarg($scriptPath);

		if (PHP_OS_FAMILY === 'Windows') {
			$redirectErr = !empty($errLog) ? ' 2>' . escapeshellarg($errLog) : '';
			$cmd = "start /B {$pyBin} {$scriptEscaped} {$argsStr} > NUL{$redirectErr}";
			if (function_exists('popen')) {
				$p = @popen($cmd, 'r');
				if ($p) {
					@pclose($p);
					return true;
				}
			}
			if (function_exists('shell_exec')) {
				@shell_exec($cmd);
				return true;
			}
			if (function_exists('exec')) {
				@exec($cmd);
				return true;
			}
		} else {
			// Linux / Unix (aaPanel, Ubuntu, CentOS, Debian, dll)
			$redirectErr = !empty($errLog) ? ' 2>' . escapeshellarg($errLog) : ' 2>&1';
			$cmd = "nohup {$pyBin} {$scriptEscaped} {$argsStr} > /dev/null{$redirectErr} &";
			if (function_exists('exec')) {
				@exec($cmd);
				return true;
			}
			if (function_exists('shell_exec')) {
				@shell_exec($cmd);
				return true;
			}
			if (function_exists('popen')) {
				$p = @popen($cmd, 'r');
				if ($p) {
					@pclose($p);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Merakit snapshot tabel DB untuk tahun $ta, lengkap dengan klasifikasi source_type
	 * ('dinamis', 'api', 'spreadsheet') serta matriks 2D dari Tabel Dinamis.
	 */
	private function buildDbTablesForMatching(string $ta): array
	{
		$mDinamis = new \App\Models\M_tabeldinamis();
		$dynamicMap = $mDinamis->exportDynamicTablesForMatching((int)$ta);
		$db_tables = [];

		if ($this->db->tableExists('t_tahun_tabel') && $this->db->tableExists('m_list_tabel')) {
			$hasTahun = $this->db->table('t_tahun_tabel')->where('tahun', $ta)->countAllResults();
			if ($hasTahun > 0) {
				$query = $this->db->query("
					SELECT 
						t.id as id_db,
						COALESCE(t.no_tabel, '') as nomor_tabel, 
						m.judul_ind as raw_judul, 
						t.periode_id,
						t.link_tabel,
						COALESCE(t.is_dynamic, 0) as is_dynamic,
						t.config_tabel
					FROM t_tahun_tabel t
					JOIN m_list_tabel m ON t.id_tabel = m.id
					WHERE t.tahun = ?
				", [$ta]);
				$raw_tables = $query->getResultArray();
				foreach ($raw_tables as $r) {
					$idDb = (int)$r['id_db'];
					$formatted_judul = function_exists('format_judul_tabel')
						? format_judul_tabel($r['raw_judul'], $r['periode_id'])
						: $r['raw_judul'];

					$dynInfo = $dynamicMap[$idDb] ?? null;
					$isDyn = ((int)$r['is_dynamic'] === 1 || $dynInfo !== null) ? 1 : 0;
					$linkLower = strtolower(trim($r['link_tabel'] ?? ''));

					if ($isDyn === 1) {
						$sourceType = 'dinamis';
					} elseif (strpos($linkLower, 'view_portal_tabel') !== false || strpos($linkLower, 'satudata') !== false) {
						$sourceType = 'api';
					} else {
						$sourceType = 'spreadsheet';
					}

					$db_tables[] = [
						'id_db'         => $idDb,
						'id_indikator'  => $dynInfo ? (int)($dynInfo['id_indikator'] ?? 0) : 0,
						'nomor_tabel'   => $r['nomor_tabel'],
						'judul_tabel'   => $formatted_judul,
						'link_tabel'    => $r['link_tabel'],
						'is_dynamic'    => $isDyn,
						'source_type'   => $sourceType,
						'config_tabel'  => $r['config_tabel'] ?? null,
						'dynamic_empty' => $dynInfo ? (bool)$dynInfo['dynamic_empty'] : ($isDyn === 1),
						'dynamic_grid'  => $dynInfo ? ($dynInfo['dynamic_grid'] ?? []) : [],
						'template_grid' => $dynInfo ? ($dynInfo['template_grid'] ?? []) : [],
						'cell_coords'   => $dynInfo ? ($dynInfo['cell_coords'] ?? []) : [],
					];
				}
			}
		}

		if (empty($db_tables) && $this->db->tableExists('m_list_tabel')) {
			$query = $this->db->query("
				SELECT 
					m.id as id_db,
					COALESCE(m.no_tabel, '') as nomor_tabel, 
					m.judul_ind as raw_judul, 
					'Tahunan' as periode_id,
					ka.link_api as link_tabel,
					CASE 
					WHEN m.jenis_sumber = 'api_kominfo' THEN 0 
					WHEN m.jenis_sumber = 'dinamis' THEN 1 
					WHEN mi.id IS NOT NULL THEN 1 
					ELSE 0 
				END as is_dynamic,
					ka.config_tabel,
					m.jenis_sumber,
					u.unitkerja_ind,
					u.jenis_instansi
				FROM m_list_tabel m
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
				LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = m.id
				LEFT JOIN m_unitkerja u ON m.id_unitkerja = u.id_unitkerja
				WHERE m.is_active = 1
				ORDER BY m.id ASC
			");
			$raw_tables = $query->getResultArray();
			foreach ($raw_tables as $r) {
				$idDb = (int)$r['id_db'];
				$formatted_judul = function_exists('format_judul_tabel')
					? format_judul_tabel($r['raw_judul'], $r['periode_id'])
					: $r['raw_judul'];

				$dynInfo = $dynamicMap[$idDb] ?? null;
				$isDyn = ((int)$r['is_dynamic'] === 1 || $dynInfo !== null) ? 1 : 0;
				$linkLower = strtolower(trim($r['link_tabel'] ?? ''));

				if ($isDyn === 1) {
					$sourceType = 'dinamis';
				} elseif (($r['jenis_sumber'] ?? '') === 'api_kominfo' || strpos($linkLower, 'view_portal_tabel') !== false || strpos($linkLower, 'satudata') !== false) {
					$sourceType = 'api';
				} else {
					$sourceType = 'spreadsheet';
				}

				$db_tables[] = [
					'id_db'         => $idDb,
					'id_indikator'  => $dynInfo ? (int)($dynInfo['id_indikator'] ?? 0) : 0,
					'nomor_tabel'   => $r['nomor_tabel'],
					'judul_tabel'   => $formatted_judul,
					'link_tabel'    => $r['link_tabel'],
					'is_dynamic'    => $isDyn,
					'source_type'   => $sourceType,
					'instansi'      => $r['unitkerja_ind'] ?? '',
					'jenis_instansi'=> $r['jenis_instansi'] ?? '',
					'config_tabel'  => $r['config_tabel'] ?? null,
					'dynamic_empty' => $dynInfo ? (bool)$dynInfo['dynamic_empty'] : ($isDyn === 1),
					'dynamic_grid'  => $dynInfo ? ($dynInfo['dynamic_grid'] ?? []) : [],
					'template_grid' => $dynInfo ? ($dynInfo['template_grid'] ?? []) : [],
					'cell_coords'   => $dynInfo ? ($dynInfo['cell_coords'] ?? []) : [],
				];
			}
		}

		foreach ($db_tables as &$row) {
			if (empty(trim($row['nomor_tabel']))) {
				if (preg_match("/(?:Tabel|Table)?\s*(\d+\.\d+(?:\.\d+[a-zA-Z]*)?)/i", $row['judul_tabel'], $matches)) {
					$row['nomor_tabel'] = trim($matches[1]);
				}
			}
		}
		unset($row);

		return $db_tables;
	}

	/**
	 * Hitung signature ringan (< 10ms) dari status konfigurasi Tabel Dinamis & API di database
	 * untuk mendeteksi secara instan apabila ada tabel yang baru dikonfigurasi.
	 */
	private function getDynamicConfigSignature(string $ta): string
	{
		if ($this->db->tableExists('t_tahun_tabel')) {
			$sigRow = $this->db->query("
				SELECT COUNT(*) as total_dyn,
				       GROUP_CONCAT(t.id ORDER BY t.id SEPARATOR ',') as dyn_ids,
				       (SELECT COUNT(*) FROM m_indikator_tabel) as ind_cnt,
				       (SELECT MAX(updated_at) FROM m_indikator_tabel) as ind_max_upd,
				       (SELECT COUNT(*) FROM t_nilai_dinamis) as nilai_cnt,
				       (SELECT MD5(GROUP_CONCAT(COALESCE(config_tabel, '') SEPARATOR '|')) FROM t_tahun_tabel WHERE tahun = ?) as config_sig
				FROM t_tahun_tabel t
				WHERE t.tahun = ? AND (t.is_dynamic = 1 OR EXISTS (SELECT 1 FROM m_indikator_tabel i WHERE i.id_tahun_tabel = t.id))
			", [$ta, $ta])->getRowArray();
			return md5(json_encode($sigRow));
		}

		$sigRow = $this->db->query("
			SELECT COUNT(*) as total_dyn,
			       GROUP_CONCAT(m.id ORDER BY m.id SEPARATOR ',') as dyn_ids,
			       (SELECT COUNT(*) FROM m_indikator_tabel) as ind_cnt,
			       (SELECT MAX(updated_at) FROM m_indikator_tabel) as ind_max_upd,
			       (SELECT COUNT(*) FROM t_nilai_dinamis) as nilai_cnt
			FROM m_list_tabel m
			JOIN m_indikator_tabel i ON i.id_tabel = m.id
			WHERE m.is_active = 1
		")->getRowArray();

		$kaSig = '';
		if ($this->db->tableExists('m_konfigurasi_api')) {
			$kaRow = $this->db->query("
				SELECT COUNT(*) as cnt,
				       MAX(updated_at) as max_upd,
				       MD5(GROUP_CONCAT(CONCAT(id_tabel, ':', COALESCE(link_api,''), ':', MD5(COALESCE(config_tabel,''))) ORDER BY id_tabel SEPARATOR '|')) as ka_hash
				FROM m_konfigurasi_api
			")->getRowArray();
			$kaSig = json_encode($kaRow);
		}

		$mlSig = '';
		if ($this->db->tableExists('m_list_tabel')) {
			$mlRow = $this->db->query("
				SELECT COUNT(*) as cnt,
				       MD5(GROUP_CONCAT(CONCAT(id, ':', COALESCE(no_tabel,'')) ORDER BY id SEPARATOR '|')) as ml_hash
				FROM m_list_tabel
				WHERE is_active = 1
			")->getRowArray();
			$mlSig = json_encode($mlRow);
		}

		return md5(json_encode($sigRow) . '_' . $kaSig . '_' . $mlSig . '_' . $ta);
	}

	/**
	 * Sinkronisasi Delta Real-Time Cepat (< 1 detik) status data Tabel Dinamis dari Cloud MySQL
	 * ke snapshot lokal dda_matched_{ta}.json, dda_db_{ta}.json, dan dda_state_{ta}.json.
	 *
	 * Tanpa re-index PDF (match_pdf.py) yang memakan waktu belasan detik!
	 * Langsung mendeteksi tabel mana saja yang baru diisi nilainya oleh operator/teman di Cloud DB,
	 * memperbarui grid data 2D tabel tersebut, dan mengubah statusnya menjadi 'unverified' agar
	 * siap diverifikasi instan secara parsial.
	 *
	 * @param string $ta Tahun aktif (misal '2026')
	 * @param bool $forceReexport Paksa periksa dan sinkronkan tanpa cek signature
	 * @return array Ringkasan perubahan ['changed_to_filled' => [...], 'changed_to_empty' => [...], 'db_sig' => ...]
	 */
	public function syncDynamicTablesRealtime(string $ta, bool $forceReexport = false): array
	{
		$uploadPath = WRITEPATH . 'uploads/';
		$matchedJson = $uploadPath . "dda_matched_{$ta}.json";
		$dbJson = $uploadPath . "dda_db_{$ta}.json";
		$stateJson = $uploadPath . "dda_state_{$ta}.json";

		if (!file_exists($matchedJson) || !file_exists($dbJson)) {
			return ['status' => 'error', 'message' => 'File matched atau db JSON belum tersedia', 'changed_to_filled' => []];
		}

		$currentDbSig = $this->getDynamicConfigSignature($ta);
		$masterData = json_decode(@file_get_contents($matchedJson), true);
		if (!is_array($masterData) || (!isset($masterData['matched_in_pdf']) && !isset($masterData['matched_tables']))) {
			return ['status' => 'error', 'message' => 'Format file matched JSON tidak valid', 'changed_to_filled' => []];
		}

		if (!$forceReexport && !empty($masterData['db_sig']) && $masterData['db_sig'] === $currentDbSig) {
			return [
				'status'            => 'success',
				'message'           => 'Data sudah up-to-date dengan Cloud Aiven.',
				'changed_to_filled' => [],
				'changed_to_empty'  => [],
				'db_sig'            => $currentDbSig
			];
		}

		$sql = "
			SELECT 
				i.id as id_indikator,
				i.id_tabel as id_master,
				m.no_tabel,
				COUNT(n.id) as filled_cnt
			FROM m_indikator_tabel i
			JOIN m_list_tabel m ON m.id = i.id_tabel
			LEFT JOIN t_nilai_dinamis n ON n.id_indikator = i.id AND (
				(n.nilai IS NOT NULL AND n.nilai != '') OR 
				(n.simbol IS NOT NULL AND n.simbol != '')
			)
			WHERE m.is_active = 1
			GROUP BY i.id, i.id_tabel, m.no_tabel
		";
		$rows = $this->db->query($sql)->getResultArray();

		$dbFillById = [];
		foreach ($rows as $r) {
			$idM = (string)($r['id_master'] ?? '');
			$cnt = (int)($r['filled_cnt'] ?? 0);
			if ($cnt > 0) {
				$dbFillById[$idM] = true;
			} elseif (!isset($dbFillById[$idM])) {
				$dbFillById[$idM] = false;
			}
		}

		$dbList = json_decode(@file_get_contents($dbJson), true) ?? [];
		$stateData = file_exists($stateJson) ? (json_decode(@file_get_contents($stateJson), true) ?? []) : [];
		$mDinamis = new \App\Models\M_tabeldinamis();

		$listKey = isset($masterData['matched_in_pdf']) ? 'matched_in_pdf' : 'matched_tables';
		$changedToFilled = [];
		$changedToEmpty = [];
		$matchedUpdated = false;
		$dbUpdated = false;
		$stateUpdated = false;

		$dbIndexMap = [];
		foreach ($dbList as $idx => $dbItem) {
			$idDbStr = (string)($dbItem['id_db'] ?? '');
			if ($idDbStr !== '') {
				$dbIndexMap[$idDbStr] = $idx;
			}
		}

		foreach ($masterData[$listKey] as &$mItem) {
			$tNo = trim((string)($mItem['nomor_tabel'] ?? ''));
			$idDb = (string)($mItem['id_db'] ?? '');
			$isDyn = ($mItem['source_type'] ?? '') === 'dinamis' || ((int)($mItem['is_dynamic'] ?? 0)) === 1;
			if (!$isDyn || $idDb === '') {
				continue;
			}

			$currEmpty = !empty($mItem['dynamic_empty']);
			$hasDataInDb = $dbFillById[$idDb] ?? false;
			$actualEmpty = !$hasDataInDb;

			if ($currEmpty && !$actualEmpty) {
				$mItem['dynamic_empty'] = false;
				$changedToFilled[] = $tNo;
				$matchedUpdated = true;

				$dynExport = $mDinamis->exportSingleDynamicTable((int)$idDb);
				if ($dynExport && isset($dbIndexMap[$idDb])) {
					$idx = $dbIndexMap[$idDb];
					$dbList[$idx]['dynamic_empty'] = false;
					$dbList[$idx]['dynamic_grid'] = $dynExport['dynamic_grid'] ?? [];
					$dbList[$idx]['template_grid'] = $dynExport['template_grid'] ?? [];
					$dbList[$idx]['cell_coords'] = $dynExport['cell_coords'] ?? [];
					$dbUpdated = true;
				}

				if ($tNo !== '') {
					$stateData[$tNo] = [
						'status'      => 'unverified',
						'match_count' => 0,
						'diff_count'  => 0,
						'reason'      => 'Data telah terisi di database, siap diverifikasi.',
						'updated_at'  => date('Y-m-d H:i:s'),
					];
					$stateUpdated = true;
				}
			} elseif (!$currEmpty && $actualEmpty) {
				$mItem['dynamic_empty'] = true;
				$changedToEmpty[] = $tNo;
				$matchedUpdated = true;

				if (isset($dbIndexMap[$idDb])) {
					$idx = $dbIndexMap[$idDb];
					$dbList[$idx]['dynamic_empty'] = true;
					$dbList[$idx]['dynamic_grid'] = [];
					$dbUpdated = true;
				}

				if ($tNo !== '') {
					$stateData[$tNo] = [
						'status'      => 'api_empty',
						'match_count' => 0,
						'diff_count'  => 0,
						'reason'      => 'Data nilai Tabel Dinamis belum diisi di database.',
						'updated_at'  => date('Y-m-d H:i:s'),
					];
					$stateUpdated = true;
				}
			}
		}
		unset($mItem);

		$masterData['db_sig'] = $currentDbSig;
		@file_put_contents($matchedJson, json_encode($masterData, JSON_UNESCAPED_UNICODE));

		if ($dbUpdated) {
			@file_put_contents($dbJson, json_encode($dbList, JSON_UNESCAPED_UNICODE));
		}

		if ($stateUpdated) {
			@file_put_contents($stateJson, json_encode($stateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		}

		return [
			'status'            => 'success',
			'message'           => count($changedToFilled) > 0 
				? 'Ditemukan ' . count($changedToFilled) . ' tabel baru yang telah terisi data dari Cloud Aiven!' 
				: 'Sinkronisasi berhasil! Status data tabel dinamis telah up-to-date.',
			'changed_to_filled' => $changedToFilled,
			'changed_to_empty'  => $changedToEmpty,
			'db_sig'            => $currentDbSig,
			'matched_in_pdf'    => $masterData[$listKey],
			'batch_status'      => $stateData
		];
	}

	private function syncMasterMatchingFiles(string $ta, string $pdfMaster, string $dbJson, string $matchedJson, bool $forceRebuild = false): ?array
	{
		if (!file_exists($matchedJson)) {
			return null;
		}

		$masterData = json_decode(@file_get_contents($matchedJson), true);
		if (!is_array($masterData) || (!isset($masterData['matched_in_pdf']) && !isset($masterData['matched_tables']))) {
			return $masterData;
		}

		$listKey = isset($masterData['matched_in_pdf']) ? 'matched_in_pdf' : 'matched_tables';
		$currentMatched = $masterData[$listKey] ?? [];
		$currentDbSig = $this->getDynamicConfigSignature($ta);

		if (!$forceRebuild && file_exists($dbJson)) {
			$hasLegacyFormat = false;
			foreach ($currentMatched as $mItem) {
				$st = $mItem['source_type'] ?? '';
				if ($st === '' || $st === 'spreadsheet') {
					$hasLegacyFormat = true;
					break;
				}
			}
			if (!$hasLegacyFormat && !empty($masterData['db_sig']) && $masterData['db_sig'] === $currentDbSig) {
				return $masterData;
			}

			// Signature berubah tapi file PDF sudah ada: jalankan Fast Delta Sync (< 1 detik)
			// Alih-alih menjalankan match_pdf.py yang memakan waktu belasan detik
			$this->syncDynamicTablesRealtime($ta);
			$reloaded = json_decode(@file_get_contents($matchedJson), true);
			return is_array($reloaded) ? $reloaded : $masterData;
		}

		$dbList = $this->buildDbTablesForMatching($ta);
		@file_put_contents($dbJson, json_encode($dbList, JSON_UNESCAPED_UNICODE));

		if (file_exists($pdfMaster)) {
			$pythonScript = FCPATH . '../python_engine/match_pdf.py';
			$pyBin = $this->getPythonBin();
			$command = "{$pyBin} " . escapeshellarg($pythonScript) . " " . escapeshellarg($pdfMaster) . " " . escapeshellarg($dbJson) . " 2>&1";
			@shell_exec($command);
			$reloaded = json_decode(@file_get_contents($matchedJson), true);
			if (is_array($reloaded) && isset($reloaded['matched_in_pdf'])) {
				$reloaded['db_sig'] = $currentDbSig;
				@file_put_contents($matchedJson, json_encode($reloaded, JSON_UNESCAPED_UNICODE));

				// Pastikan tabel dinamis baru yang masih kosong otomatis berstatus 'api_empty' (Data Kosong) di dda_state_{ta}.json
				$statePath = WRITEPATH . "uploads/dda_state_{$ta}.json";
				$stateData = file_exists($statePath) ? (json_decode(@file_get_contents($statePath), true) ?? []) : [];
				$stateChanged = false;
				foreach ($reloaded['matched_in_pdf'] as $mRow) {
					$tNo = $mRow['nomor_tabel'] ?? '';
					if ($tNo !== '' && ($mRow['source_type'] ?? '') === 'dinamis') {
						$isEmpty = !empty($mRow['dynamic_empty']);
						$currSt = $stateData[$tNo]['status'] ?? 'unverified';
						if ($isEmpty && $currSt !== 'api_empty') {
							$stateData[$tNo] = [
								'status'      => 'api_empty',
								'match_count' => 0,
								'diff_count'  => 0,
								'reason'      => 'Data nilai Tabel Dinamis belum diisi di database.',
								'updated_at'  => date('Y-m-d H:i:s'),
							];
							$stateChanged = true;
						} elseif (!$isEmpty && $currSt === 'api_empty') {
							$stateData[$tNo] = [
								'status'      => 'unverified',
								'match_count' => 0,
								'diff_count'  => 0,
								'reason'      => 'Data telah terisi di database, siap diverifikasi.',
								'updated_at'  => date('Y-m-d H:i:s'),
							];
							$stateChanged = true;
						}
					}
				}
				if ($stateChanged) {
					@file_put_contents($statePath, json_encode($stateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
				}

				return $reloaded;
			}
		}

		return $masterData;
	}

	public function matching_pdf()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('matching_pdf')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Menu Matching PDF hanya untuk Super Admin.</div>');
			return redirect()->to('admin');
		}


		$ta = $this->session->get('admin_ta');
		if (empty($ta)) {
			$ta = (string)date('Y');
		}

		$uploadPath = WRITEPATH . 'uploads/';
		$pdfMaster = $uploadPath . "dda_master_{$ta}.pdf";
		$matchedJson = $uploadPath . "dda_matched_{$ta}.json";
		$dbJson = $uploadPath . "dda_db_{$ta}.json";
		$stateJson = $uploadPath . "dda_state_{$ta}.json";
		$progressJson = $uploadPath . "dda_progress_{$ta}.json";

		$hasMaster = false;
		$masterData = null;
		$batchStatus = [];
		$isBatchRunning = false;

		// 1. Cek ketersediaan file master PDF dan file hasil scan tabel
		if (file_exists($pdfMaster) && file_exists($matchedJson)) {
			$masterData = $this->syncMasterMatchingFiles((string)$ta, $pdfMaster, $dbJson, $matchedJson);
			if ($masterData && (isset($masterData['matched_in_pdf']) || isset($masterData['matched_tables']))) {
				$hasMaster = true;
				$masterData['pdf_path'] = $pdfMaster;
				$masterData['db_json_path'] = $dbJson;
				$masterData['upload_time'] = date('d M Y H:i', filemtime($pdfMaster));
				$masterData['file_size_mb'] = round(filesize($pdfMaster) / (1024 * 1024), 1);
			}

			// 1a. Auto-heal status state jika ada tabel dinamis yang di state masih 'api_empty' padahal di master data sudah ada nilainya
			if ($masterData && !empty($masterData['matched_in_pdf']) && file_exists($stateJson)) {
				$stData = json_decode(@file_get_contents($stateJson), true) ?? [];
				$stChanged = false;
				foreach ($masterData['matched_in_pdf'] as $mItem) {
					$tNo = $mItem['nomor_tabel'] ?? '';
					$isDyn = ($mItem['source_type'] ?? '') === 'dinamis' || ((int)($mItem['is_dynamic'] ?? 0)) === 1;
					if ($isDyn && $tNo !== '' && empty($mItem['dynamic_empty'])) {
						if (($stData[$tNo]['status'] ?? '') === 'api_empty') {
							$stData[$tNo] = [
								'status'      => 'unverified',
								'match_count' => 0,
								'diff_count'  => 0,
								'reason'      => 'Data telah terisi di database, siap diverifikasi.',
								'updated_at'  => date('Y-m-d H:i:s'),
							];
							$stChanged = true;
						}
					}
				}
				if ($stChanged) {
					@file_put_contents($stateJson, json_encode($stData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
				}
			}
		}

		// 1b. Populasikan has_config pada setiap tabel matched API
		if ($masterData && (isset($masterData['matched_in_pdf']) || isset($masterData['matched_tables']))) {
			$dbMapConfig = [];
			if (file_exists($dbJson)) {
				$dbArr = json_decode(@file_get_contents($dbJson), true) ?? [];
				foreach ($dbArr as $dbItem) {
					$dId = (int)($dbItem['id_db'] ?? 0);
					if ($dId > 0) {
						$hasCfg = (!empty($dbItem['config_tabel']) && trim((string)$dbItem['config_tabel']) !== '' && trim((string)$dbItem['config_tabel']) !== '{}' && trim((string)$dbItem['config_tabel']) !== 'null') ? 1 : 0;
						$dbMapConfig[$dId] = $hasCfg;
					}
				}
			}
			$listKey = isset($masterData['matched_in_pdf']) ? 'matched_in_pdf' : 'matched_tables';
			foreach ($masterData[$listKey] as &$mItem) {
				$mId = (int)($mItem['id_db'] ?? 0);
				if (isset($dbMapConfig[$mId])) {
					$mItem['has_config'] = $dbMapConfig[$mId];
				} elseif (!isset($mItem['has_config'])) {
					$mItem['has_config'] = (!empty($mItem['config_tabel']) && trim((string)$mItem['config_tabel']) !== '' && trim((string)$mItem['config_tabel']) !== '{}' && trim((string)$mItem['config_tabel']) !== 'null') ? 1 : 0;
				}
			}
			unset($mItem);
		}

		// 2. Muat status validasi terakhir dari file state JSON
		if (file_exists($stateJson)) {
			$batchStatus = json_decode(@file_get_contents($stateJson), true) ?? [];
		}

		// 3. Cek apakah background batch worker masih aktif berjalan
		if (file_exists($progressJson)) {
			$prog = json_decode(@file_get_contents($progressJson), true);
			if ($prog && !empty($prog['is_running'])) {
				if (isset($prog['updated_at']) && (time() - $prog['updated_at']) < 45) {
					$isBatchRunning = true;
				}
			}
		}

		$a['page'] = "matching_pdf";
		$a['ta'] = $ta;
		$a['has_master'] = $hasMaster;
		$a['master_data'] = $masterData;
		$a['batch_status'] = $batchStatus;
		$a['is_batch_running'] = $isBatchRunning;

		return view('admin/index', $a);
	}

	/**
	 * Memproses unggahan publikasi PDF buku DDA (Initial Upload maupun Revisi).
	 *
	 * Menggunakan arsitektur "Deterministic Slot Master per Tahun":
	 * 1. File disimpan dengan nama statis `dda_master_{ta}.pdf` (overwrite file lama).
	 * 2. Mengambil metadata tabel tahun aktif dari tabel `m_list_tabel`.
	 * 3. Menjalankan Python engine `match_pdf.py` untuk pemindaian tabel dan indexing halaman.
	 * 4. Menyimpan hasil pencocokan struktur tabel ke `dda_matched_{ta}.json`.
	 * 5. Menjalankan Lazy Garbage Collection otomatis: menghapus file upload sementara
	 *    yang berumur > 24 jam untuk menjaga kapasitas disk server tetap ramping.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface JSON response status dan data hasil scan
	 */
	public function process_matching_pdf()
	{
		set_time_limit(300);

		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$file = $this->request->getFile('pdf_file');
		
		if (!$file || !$file->isValid()) {
			$err = $file ? $file->getErrorString() : 'File kosong';
			return $this->response->setJSON(['status' => 'error', 'message' => 'File PDF tidak valid atau gagal diunggah: ' . $err]);
		}
		
		$ext = strtolower($file->getClientExtension() ?: $file->getExtension());
		if ($ext != 'pdf') {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya file PDF yang diizinkan.']);
		}

		$ta = $this->session->get('admin_ta');
		if (empty($ta)) {
			$ta = (string)date('Y');
		}

		$uploadPath = WRITEPATH . 'uploads/';
		if (!is_dir($uploadPath)) {
			mkdir($uploadPath, 0777, true);
		}

		// Master file paths for year $ta
		$pdfMasterName = "dda_master_{$ta}.pdf";
		$pdfPath = $uploadPath . $pdfMasterName;
		$dbJsonPath = $uploadPath . "dda_db_{$ta}.json";
		$matchedJsonPath = $uploadPath . "dda_matched_{$ta}.json";

		// Overwrite master PDF file & bersihkan cache index lama
		if (file_exists($pdfPath)) {
			@unlink($pdfPath);
		}
		if (file_exists($pdfPath . '.index.json')) {
			@unlink($pdfPath . '.index.json');
		}
		if (file_exists($matchedJsonPath)) {
			@unlink($matchedJsonPath);
		}

		try {
			$file->move($uploadPath, $pdfMasterName, true);
		} catch (\Exception $e) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memindahkan file PDF: ' . $e->getMessage()]);
		}

		// Ambil data database (termasuk Tabel Dinamis & API Satu Data)
		$db_tables = $this->buildDbTablesForMatching((string)$ta);

		// Save array DB ke master JSON file untuk tahun $ta
		file_put_contents($dbJsonPath, json_encode($db_tables, JSON_UNESCAPED_UNICODE));

		// Path ke script Python match_pdf.py
		$pythonScript = FCPATH . '../python_engine/match_pdf.py';
		$pyBin = $this->getPythonBin();
		
		// Run Python lewat shell_exec
		$command = "{$pyBin} " . escapeshellarg($pythonScript) . " " . escapeshellarg($pdfPath) . " " . escapeshellarg($dbJsonPath) . " 2>&1";
		$output = shell_exec($command);

		// Prioritaskan membaca hasil yang disimpan langsung oleh Python ke dda_matched_{ta}.json
		$result = null;
		if (file_exists($matchedJsonPath)) {
			$rawMatchedJson = @file_get_contents($matchedJsonPath);
			if ($rawMatchedJson !== false && !empty(trim($rawMatchedJson))) {
				$result = json_decode($rawMatchedJson, true);
			}
		}

		// Fallback membaca stdout jika file belum terbaca
		if (!$result && !empty($output)) {
			$cleanOutput = mb_convert_encoding($output, 'UTF-8', 'UTF-8');
			$result = json_decode($cleanOutput, true);
		}

		if (!$result) {
			$safeMsg = mb_convert_encoding($output ?? 'Tidak ada output dari Python engine.', 'UTF-8', 'UTF-8');
			return $this->response->setJSON(['status' => 'error', 'message' => 'Error parsing Python output. Detail: ' . $safeMsg]);
		}

		// Pastikan file dda_matched_{ta}.json tersimpan
		if (!file_exists($matchedJsonPath)) {
			file_put_contents($matchedJsonPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		}

		// Lazy Garbage Collection: Hapus file sampah lama (>24 jam) yang bukan master
		$now = time();
		$files = glob($uploadPath . '*');
		foreach ($files as $f) {
			if (is_file($f)) {
				$basename = basename($f);
				if (strpos($basename, 'dda_master_') === 0 || 
					strpos($basename, 'dda_matched_') === 0 || 
					strpos($basename, 'dda_state_') === 0 || 
					strpos($basename, 'dda_progress_') === 0 || 
					strpos($basename, 'dda_db_') === 0 ||
					$basename === 'index.html') {
					continue;
				}
				if (($now - filemtime($f)) > 86400) {
					@unlink($f);
				}
			}
		}

		$result['pdf_path'] = $pdfPath;
		$result['db_json_path'] = $dbJsonPath;
		$result['upload_time'] = date('d M Y H:i');
		$result['file_size_mb'] = round(filesize($pdfPath) / (1024 * 1024), 1);

		return $this->response->setJSON($result);
	}

	/**
	 * Melakukan verifikasi dan rekonsiliasi head-to-head untuk 1 tabel spesifik.
	 *
	 * Menjalankan Python engine `compare_table.py` untuk mengunduh data live terbaru
	 * dari Google Spreadsheet / Satu Data API, lalu mencocokkannya dengan tabel di PDF.
	 * Hasil perbandingan langsung disimpan ke `dda_state_{ta}.json` secara real-time,
	 * sehingga saat data yang salah dibetulkan di spreadsheet dan dicek ulang, status
	 * otomatis berubah menjadi "Cocok 100%" dan persisten.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface Detail perbandingan sel angka (diffs, matches, pdf_only)
	 */
	public function verify_tabel_pdf()
	{
		$nomor_tabel = $this->request->getPost("nomor_tabel");
		$pdfPath = $this->request->getPost("pdf_path");
		$dbJsonPath = $this->request->getPost("db_json_path");
		$tolerance = $this->request->getPost("tolerance") ?? "0.0";

		if (empty($nomor_tabel) || empty($pdfPath) || empty($dbJsonPath)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Nomor tabel tidak valid']);
		}

		// AUTO-SYNC: Selalu perbarui link_tabel & matriks Tabel Dinamis untuk nomor tabel ini dari database ke file JSON snapshot
		$ta = $this->session->get('admin_ta') ?? (string)date('Y');
		if (preg_match('/dda_master_(\d{4})\.pdf/i', $pdfPath, $mYear)) {
			$ta = $mYear[1];
		}
		if (file_exists($dbJsonPath)) {
			$cleanNo = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $nomor_tabel));
			$cleanNo = rtrim($cleanNo, '.');
			
			$rowDb = $this->db->query("
				SELECT m.id as id_db, ka.link_api as link_tabel, 
				CASE 
					WHEN m.jenis_sumber = 'api_kominfo' THEN 0 
					WHEN m.jenis_sumber = 'dinamis' THEN 1 
					WHEN mi.id IS NOT NULL THEN 1 
					ELSE 0 
				END as is_dynamic
				FROM m_list_tabel m
				LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
				LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = m.id
				WHERE (
					TRIM(m.no_tabel) = ? OR 
					TRIM(m.no_tabel) = ? OR 
					TRIM(REPLACE(m.no_tabel, 'Tabel ', '')) = ?
				)
				ORDER BY CASE WHEN mi.id IS NOT NULL THEN 1 ELSE 0 END DESC, m.id DESC
				LIMIT 1
			", [$nomor_tabel, $cleanNo, $cleanNo])->getRow();

			if ($rowDb) {
				$mDinamis = new \App\Models\M_tabeldinamis();
				$dynInfo = $mDinamis->exportSingleDynamicTable((int)$rowDb->id_db);
				$isDyn = ((int)$rowDb->is_dynamic === 1 || $dynInfo !== null) ? 1 : 0;
				$linkLower = strtolower(trim($rowDb->link_tabel ?? ''));
				$srcType = $isDyn === 1 ? 'dinamis' : ((strpos($linkLower, 'view_portal_tabel') !== false || strpos($linkLower, 'satudata') !== false) ? 'api' : 'spreadsheet');

				$rawJson = @file_get_contents($dbJsonPath);
				$listTables = json_decode($rawJson, true);
				if (is_array($listTables)) {
					$updated = false;
					foreach ($listTables as &$item) {
						$curClean = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $item['nomor_tabel'] ?? ''));
						$curClean = rtrim($curClean, '.');
						$isMatch = ($curClean === $cleanNo)
							|| ((int)($item['id_db'] ?? 0) === (int)$rowDb->id_db)
							|| (trim((string)($item['nomor_tabel'] ?? '')) === trim((string)$nomor_tabel));

						if ($isMatch) {
							$item['id_db']         = (int)$rowDb->id_db;
							$item['id_indikator']  = $dynInfo ? ($dynInfo['id_indikator'] ?? null) : null;
							$item['link_tabel']    = $rowDb->link_tabel;
							$item['is_dynamic']    = $isDyn;
							$item['source_type']   = $srcType;
							$item['dynamic_empty'] = $dynInfo ? (bool)$dynInfo['dynamic_empty'] : ($isDyn === 1);
							$item['dynamic_grid']  = $dynInfo ? ($dynInfo['dynamic_grid'] ?? []) : [];
							$item['template_grid'] = $dynInfo ? ($dynInfo['template_grid'] ?? []) : [];
							$item['cell_coords']   = $dynInfo ? ($dynInfo['cell_coords'] ?? []) : [];
							$updated = true;
							break;
						}
					}
					unset($item);
					if ($updated) {
						@file_put_contents($dbJsonPath, json_encode($listTables, JSON_UNESCAPED_UNICODE));
					}
				}
			}
		}

		$pythonScript = FCPATH . "../python_engine/compare_table.py";
		$pyBin = $this->getPythonBin();
		$command = "{$pyBin} " . escapeshellarg($pythonScript) . " " . escapeshellarg($pdfPath) . " " . escapeshellarg($dbJsonPath) . " " . escapeshellarg($nomor_tabel) . " " . escapeshellarg($tolerance) . " 2>&1";
		$output = shell_exec($command);

		if (!$output) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengeksekusi script Python']);
		}

		$res = json_decode($output, true);
		if (!$res && !empty($output)) {
			$jsonStart = strpos($output, '{');
			$jsonEnd = strrpos($output, '}');
			if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart) {
				$subJson = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
				$res = json_decode($subJson, true);
			}
			if (!$res) {
				$cleanOutput = mb_convert_encoding($output, 'UTF-8', 'UTF-8');
				$res = json_decode($cleanOutput, true);
			}
		}
		if (!$res) {
			$cleanOutput = mb_convert_encoding($output ?? 'Gagal membaca respon python.', 'UTF-8', 'UTF-8');
			return $this->response->setJSON(['status' => 'error', 'message' => $cleanOutput]);
		}

		if ($res) {
			// Update state file secara otomatis & persisten
			$ta = $this->session->get('admin_ta') ?? (string)date('Y');
			if (preg_match('/dda_master_(\d{4})\.pdf/i', $pdfPath, $mYear)) {
				$ta = $mYear[1];
			}
			$statePath = WRITEPATH . "uploads/dda_state_{$ta}.json";
			$stateData = [];
			if (file_exists($statePath)) {
				$stateData = json_decode(@file_get_contents($statePath), true) ?? [];
			}

			$status = 'diff';
			$diff_count = 0;
			$match_count = 0;

			if (isset($res['status']) && $res['status'] === 'success') {
				$totalDiffs = $res['summary']['total_diffs'] ?? (isset($res['diffs']) ? count($res['diffs']) : 0);
				$totalMatches = $res['summary']['total_matches'] ?? (isset($res['matches']) ? count($res['matches']) : 0);
				$totalPdfOnly = $res['summary']['total_pdf_only'] ?? (isset($res['pdf_only']) ? count($res['pdf_only']) : 0);
				if ($totalDiffs > 0) {
					$status = 'diff';
					$diff_count = $totalDiffs;
					$match_count = $totalMatches;
				} else if ($totalMatches == 0) {
					$status = 'diff';
					$diff_count = ($totalPdfOnly > 0) ? $totalPdfOnly : '!';
					$match_count = 0;
				} else {
					$status = 'match';
					$diff_count = 0;
					$match_count = $totalMatches;
				}
			} else if (isset($res['is_api_empty']) && $res['is_api_empty']) {
				$status = 'api_empty';
			} else {
				$status = 'diff';
				$diff_count = '!';
			}

			$stateData[$nomor_tabel] = [
				'nomor_tabel' => $nomor_tabel,
				'status' => $status,
				'diff_count' => $diff_count,
				'match_count' => $match_count,
				'updated_at' => date('Y-m-d H:i:s')
			];
			@file_put_contents($statePath, json_encode($stateData, JSON_PRETTY_PRINT));
		}

		return $this->response->setJSON($res);
	}

	/**
	 * Menyalakan Background Worker untuk validasi semua tabel secara massal (Batch Validation).
	 *
	 * Solusi beban berat: Alih-alih browser menembakkan ratusan AJAX bolak-balik menyalakan
	 * proses Python, server menyalakan 1 background worker Python (`batch_verify.py`)
	 * dengan multi-threading internal. Browser user tetap ringan, adem, dan responsif.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface Status keberhasilan inisiasi background task
	 */
	public function start_batch_worker()
	{
		$ta = $this->request->getPost('ta') ?? $this->session->get('admin_ta') ?? (string)date('Y');
		$tolerance = $this->request->getPost('tolerance') ?? "0.0";
		$sourceFilter = strtolower(trim((string)($this->request->getPost('source_filter') ?? 'all')));
		if (!in_array($sourceFilter, ['all', 'dinamis', 'api', 'unverified'], true)) {
			$sourceFilter = 'all';
		}

		$uploadPath = WRITEPATH . 'uploads/';
		$pdfPath = $uploadPath . "dda_master_{$ta}.pdf";
		$matchedJson = $uploadPath . "dda_matched_{$ta}.json";
		$dbJson = $uploadPath . "dda_db_{$ta}.json";
		$stateJson = $uploadPath . "dda_state_{$ta}.json";
		$progressJson = $uploadPath . "dda_progress_{$ta}.json";

		if (!file_exists($pdfPath) || !file_exists($matchedJson) || !file_exists($dbJson)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'File master PDF atau data tabel belum tersedia']);
		}

		// AUTO-SYNC TABEL DINAMIS: Sinkronkan seluruh data terbaru dari MySQL secara real-time delta sync (< 1 detik)
		$this->syncDynamicTablesRealtime((string)$ta);

		// Bersihkan stop file jika ada
		$stopFile = $progressJson . '.stop';
		if (file_exists($stopFile)) {
			@unlink($stopFile);
		}

		// Jika validasi semua, reset state file. Jika filter parsial (misal 'unverified', 'dinamis', 'api'), pertahankan state yang sudah ada!
		if ($sourceFilter === 'all' || !file_exists($stateJson)) {
			@file_put_contents($stateJson, json_encode(new \stdClass(), JSON_FORCE_OBJECT));
		}

		// Initial progress marker
		$progData = [
			'is_running' => true,
			'completed' => 0,
			'total' => 0,
			'percent' => 0,
			'source_filter' => $sourceFilter,
			'updated_at' => time()
		];
		@file_put_contents($progressJson, json_encode($progData));

		$pyScript = FCPATH . '../python_engine/batch_verify.py';
		$errLog = $uploadPath . "dda_batch_stderr_{$ta}.log";
		$this->runBackgroundPython($pyScript, [
			$pdfPath,
			$matchedJson,
			$dbJson,
			$stateJson,
			$progressJson,
			$tolerance,
			$sourceFilter
		], $errLog);

		return $this->response->setJSON(['status' => 'success', 'message' => 'Batch worker berhasil dimulai', 'source_filter' => $sourceFilter]);
	}

	/**
	 * AJAX endpoint: Sinkronkan seluruh data Tabel Dinamis dari Cloud MySQL ke snapshot lokal dda_db_{ta}.json & dda_matched_{ta}.json
	 */
	public function ajax_sync_cloud()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$ta = $this->request->getPost('ta') ?? $this->session->get('admin_ta') ?? '2026';
		$uploadPath = WRITEPATH . 'uploads/';
		$pdfPath = $uploadPath . "dda_master_{$ta}.pdf";
		$matchedJson = $uploadPath . "dda_matched_{$ta}.json";
		$dbJson = $uploadPath . "dda_db_{$ta}.json";
		$stateJson = $uploadPath . "dda_state_{$ta}.json";

		if (!file_exists($pdfPath) || !file_exists($matchedJson) || !file_exists($dbJson)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'File master PDF atau data tabel belum tersedia']);
		}

		// Jalankan Real-Time Delta Sync super cepat (< 1 detik)
		$syncResult = $this->syncDynamicTablesRealtime((string)$ta, true);
		$stateData = file_exists($stateJson) ? (json_decode(@file_get_contents($stateJson), true) ?? []) : [];
		$masterData = file_exists($matchedJson) ? (json_decode(@file_get_contents($matchedJson), true) ?? []) : [];
		$listKey = isset($masterData['matched_in_pdf']) ? 'matched_in_pdf' : 'matched_tables';

		return $this->response->setJSON([
			'status'            => 'success',
			'message'           => $syncResult['message'] ?? 'Data dari Cloud Aiven berhasil disinkronkan!',
			'changed_to_filled' => $syncResult['changed_to_filled'] ?? [],
			'changed_to_empty'  => $syncResult['changed_to_empty'] ?? [],
			'matched_in_pdf'    => $masterData[$listKey] ?? [],
			'batch_status'      => $stateData,
			'db_sig'            => $syncResult['db_sig'] ?? ''
		]);
	}

	/**
	 * Menyimpan hasil ekstraksi nilai dari PDF langsung ke t_nilai_dinamis (One-Click Auto-Fill / Sync).
	 */
	public function sync_pdf_to_dinamis()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$idIndikator = (int)($this->request->getPost('id_indikator') ?? 0);
		$cellsRaw = $this->request->getPost('cells');
		$cells = is_string($cellsRaw) ? json_decode($cellsRaw, true) : $cellsRaw;

		if ($idIndikator <= 0 || empty($cells) || !is_array($cells)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Data sel atau ID Indikator Tabel Dinamis tidak valid']);
		}

		$mDinamis = new \App\Models\M_tabeldinamis();
		$indObj = $this->db->table('m_indikator_tabel')->where('id', $idIndikator)->get()->getRow();
		$availYears = $mDinamis->getAvailableYears($idIndikator);
		$adminTa = (int)($this->session->get('admin_ta') ?: date('Y'));

		if (!empty($availYears)) {
			$taDefault = (int)end($availYears);
		} elseif ($indObj && !empty($indObj->tahun_tampil)) {
			$tParts = array_values(array_filter(array_map('intval', explode(',', (string)$indObj->tahun_tampil))));
			$taDefault = !empty($tParts) ? (int)end($tParts) : ($adminTa - 1);
		} elseif ($indObj && !empty($indObj->tahun) && (int)$indObj->tahun >= 1900) {
			$taDefault = (int)$indObj->tahun;
		} else {
			$taDefault = $adminTa;
		}

		$payload = [];
		foreach ($cells as $c) {
			$idB = (int)($c['id_baris_item'] ?? ($c['b'] ?? 0));
			$idK = (int)($c['id_karakteristik_item'] ?? ($c['k'] ?? 0));
			$idP = (int)($c['id_periode_item'] ?? ($c['p'] ?? 0));
			$rawVal = $c['nilai'] ?? null;
			$simbol = $c['simbol'] ?? null;
			$cTahun = (int)($c['tahun_data'] ?? ($c['t'] ?? ($this->request->getPost('tahun_data') ?: 0)));
			$tahunData = ($cTahun >= 1900 && $cTahun <= 2100) ? $cTahun : $taDefault;

			if ($idP > 190000000) {
				$tahunData = intdiv($idP, 100000);
				$idP       = $idP % 100000;
			} elseif ($idP >= 1900 && $idP <= 2100) {
				$tahunData = $idP;
				$idP       = 0;
			}

			$rawStr = is_string($rawVal) ? trim($rawVal) : (is_numeric($rawVal) ? (string)$rawVal : '');
			$dashTokens = ['-', '–', '—', '−', "\u{2212}", "\u{2011}", "\u{2015}"];
			$dotTokens  = ['...', '…', "\u{2026}", 'na', 'n.a', 'n.a.', 'n/a'];

			if (in_array($rawStr, $dashTokens, true)) {
				// Simbol BPS '-' (Nihil / Nol) -> simpan nilai = 0.0, simbol = '-'
				$nilai = 0.0;
				$simbol = '-';
				$jmlDesimal = 0;
			} elseif (in_array($rawStr, $dotTokens, true) || $rawStr === '') {
				// Simbol BPS '...' (Data belum tersedia) -> simpan nilai = null, simbol = '...'
				$nilai = null;
				$simbol = '...';
				$jmlDesimal = null;
			} else {
				// Cek format angka riil
				$cleanRaw = $rawStr;
				if (preg_match('/^-?\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $cleanRaw)) {
					$cleanRaw = str_replace('.', '', $cleanRaw);
					$cleanRaw = str_replace(',', '.', $cleanRaw);
				} elseif (preg_match('/^-?\d+,\d+$/', $cleanRaw)) {
					$cleanRaw = str_replace(',', '.', $cleanRaw);
				} elseif (preg_match('/^-?\d{1,3}(?:,\d{3})+(?:\.\d+)?$/', $cleanRaw)) {
					$cleanRaw = str_replace(',', '', $cleanRaw);
				}

				if (is_numeric($cleanRaw)) {
					$nilai = (float)$cleanRaw;
					if (abs($nilai) < 0.0000001) {
						// Angka riil 0 -> Simbol BPS '-'
						$nilai = 0.0;
						$simbol = '-';
						$jmlDesimal = 0;
					} else {
						// Angka riil non-nol -> simbol dibersihkan (null)
						$simbol = null;
						if (abs($nilai - round($nilai)) > 0.00001) {
							$vStr = (string)$nilai;
							$jmlDesimal = (strpos($vStr, '.') !== false) ? min(4, strlen(rtrim(explode('.', $vStr)[1], '0'))) : 2;
						} else {
							$jmlDesimal = 0;
						}
					}
				} else {
					$nilai = null;
					$simbol = $rawStr !== '' ? $rawStr : null;
					$jmlDesimal = null;
				}
			}

			$payload[] = [
				'id_baris_item'         => $idB,
				'id_karakteristik_item' => $idK,
				'id_periode_item'       => $idP,
				'tahun_data'            => $tahunData,
				'nilai'                 => $nilai,
				'simbol'                => $simbol,
				'jumlah_desimal'        => $jmlDesimal,
			];
		}

		// Deteksi presisi desimal jika ada data desimal pecahan
		$maxDetectedDec = 0;
		foreach ($payload as $pItem) {
			if (!empty($pItem['jumlah_desimal']) && (int)$pItem['jumlah_desimal'] > $maxDetectedDec) {
				$maxDetectedDec = (int)$pItem['jumlah_desimal'];
			}
		}
		if ($maxDetectedDec > 0 && $indObj) {
			if ((int)$indObj->desimal === 0 || (int)$indObj->desimal === -1) {
				$this->db->table('m_indikator_tabel')->where('id', $idIndikator)->update(['desimal' => $maxDetectedDec]);
			}
		}

		$affected = $mDinamis->batchUpsertNilai($idIndikator, $payload, $taDefault);

		if ($affected > 0) {
			$uploaderName = $this->session->get('admin_nama') ?: ($this->session->get('admin_user') ?: 'Administrator');
			$catatan = "Diunggah oleh {$uploaderName} pada " . date('d/m/Y H:i');

			$indRow = $mDinamis->find($idIndikator);
			if ($indRow && !empty($indRow->id_tabel)) {
				$mDinamis->upsertStatusTahunan((int)$indRow->id_tabel, $taDefault, [
					'is_periksa'      => 1,
					'catatan_periksa' => $catatan
				]);
			}
		}

		return $this->response->setJSON([
			'status'      => 'success',
			'saved_count' => $affected,
			'message'     => "Berhasil menyinkronkan {$affected} sel angka dari PDF ke Tabel Dinamis."
		]);
	}

	/**
	 * Mengonfigurasi Tabel Dinamis secara otomatis (One-Click Auto-Config) dari struktur tabel di PDF Buku DDA.
	 * Mencocokkan atau membuat Master Dimensi (Baris, Karakteristik Kolom, Periode) secara cerdas,
	 * merakit m_indikator_tabel, mengaktifkan is_dynamic = 1, dan opsional langsung mengisi nilai sel dari PDF.
	 */
	/**
	 * Menghasilkan konfigurasi layout otomatis untuk tabel API Satudata berdasarkan standar layout PDF.
	 * Menyembunyikan kolom metadata (tahun, kode wilayah), memformat kolom wilayah (Kabupaten/Kota),
	 * mengubah kolom metrik menjadi Title Case yang rapi, dan menyusun pivot jika berdimensi waktu.
	 */
	public function get_auto_layout_config()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$idDb = (int)$this->request->getPost('id_db');
		$nomorTabel = trim((string)$this->request->getPost('nomor_tabel'));
		$ta = (string)($this->request->getPost('ta') ?: ($this->session->get('admin_ta') ?: '2026'));

		$row = null;
		if ($this->db->tableExists('t_tahun_tabel') && $this->db->tableExists('m_list_tabel')) {
			if ($idDb > 0) {
				$row = $this->db->query("
					SELECT t.id, t.id_tabel, t.no_tabel, t.link_tabel, t.config_tabel, m.judul_ind, m.judul_en
					FROM t_tahun_tabel t
					JOIN m_list_tabel m ON t.id_tabel = m.id
					WHERE t.id = ?
					LIMIT 1
				", [$idDb])->getRowArray();
			}
			if (!$row && $nomorTabel !== '') {
				$cleanNo = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $nomorTabel));
				$row = $this->db->query("
					SELECT t.id, t.id_tabel, t.no_tabel, t.link_tabel, t.config_tabel, m.judul_ind, m.judul_en
					FROM t_tahun_tabel t
					JOIN m_list_tabel m ON t.id_tabel = m.id
					WHERE t.tahun = ? AND (TRIM(t.no_tabel) = ? OR TRIM(REPLACE(t.no_tabel, 'Tabel ', '')) = ?)
					LIMIT 1
				", [$ta, $cleanNo, $cleanNo])->getRowArray();
			}
		}

		if (!$row && $this->db->tableExists('m_list_tabel')) {
			if ($idDb > 0) {
				$row = $this->db->query("
					SELECT m.id, m.id as id_tabel, m.no_tabel, ka.link_api as link_tabel, ka.config_tabel, m.judul_ind, m.judul_en
					FROM m_list_tabel m
					LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
					WHERE m.id = ?
					LIMIT 1
				", [$idDb])->getRowArray();
			}
			if (!$row && $nomorTabel !== '') {
				$cleanNo = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $nomorTabel));
				$row = $this->db->query("
					SELECT m.id, m.id as id_tabel, m.no_tabel, ka.link_api as link_tabel, ka.config_tabel, m.judul_ind, m.judul_en
					FROM m_list_tabel m
					LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
					WHERE TRIM(m.no_tabel) = ? OR TRIM(REPLACE(m.no_tabel, 'Tabel ', '')) = ?
					LIMIT 1
				", [$cleanNo, $cleanNo])->getRowArray();
			}
		}

		if (!$row) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Data tabel tidak ditemukan di database.']);
		}

		$linkTabel = (string)($row['link_tabel'] ?? '');
		if (empty($linkTabel) || (strpos($linkTabel, 'view_portal_tabel') === false && strpos($linkTabel, 'satudata') === false)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Tabel ini bukan merupakan tabel sumber API Satudata.']);
		}

		// Extract API ID
		preg_match('/id=([^&]+)/i', $linkTabel, $matches);
		$idApi = isset($matches[1]) ? trim($matches[1]) : '';
		if (empty($idApi)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'ID API tidak ditemukan pada link tabel.']);
		}

		// Call live API
		$apiIds = explode(',', $idApi);
		$firstId = trim($apiIds[0]);
		if (strpos($firstId, '?') !== false) $firstId = explode('?', $firstId)[0];
		if (strpos($firstId, 'data/') !== false) $firstId = explode('data/', $firstId)[1];
		$firstId = trim($firstId, '/ ');

		$url = "https://satudata.jatengprov.go.id/api/v1/data/{$firstId}?peruntukan=DDA";
		$apiRes = $this->callApi2($url);

		$rows = [];
		if (isset($apiRes['data']) && is_array($apiRes['data'])) {
			$rows = $apiRes['data'];
		} elseif (is_array($apiRes) && isset($apiRes[0])) {
			$rows = $apiRes;
		}

		if (empty($rows)) {
			return $this->response->setJSON([
				'status'      => 'empty',
				'nomor_tabel' => $row['no_tabel'],
				'judul_tabel' => $row['judul_ind'],
				'id_db'       => (int)$row['id'],
				'id_api'      => $idApi,
				'message'     => 'API Satu Data mengembalikan 0 baris data dari server provinsi (data masih kosong).'
			]);
		}

		$firstRow = $rows[0];
		$baseKeys = array_keys($firstRow);

		// Build smart config
		$ignoreCols = ['id', 'tahun', 'tahun_data', 'year', 'periode', 'kode_wilayah', 'kode_kabupatenkota', 'kode_bps', 'kode_kemendagri', 'urutan'];
		$lowerKeys = array_map('strtolower', $baseKeys);
		$hasIndicator = in_array('indikator', $lowerKeys) || in_array('jenis_koperasi', $lowerKeys) || in_array('jenis_usaha', $lowerKeys);
		$hasNilai = in_array('nilai', $lowerKeys) || in_array('jumlah', $lowerKeys) || in_array('jmlh', $lowerKeys);
		$hasWilayah = in_array('nama_wilayah', $lowerKeys) || in_array('nama_kabupatenkota', $lowerKeys) || in_array('kabupaten_kota', $lowerKeys);

		$columns = [];
		$pivot = [
			'enabled' => false,
			'row' => [],
			'col' => [],
			'val' => [],
			'total_row' => true,
			'only_total' => false,
			'total_col' => false,
			'metric_first' => false,
			'metrics_as_row' => false,
			'prefix' => '',
			'prefix_en' => '',
			'mappings' => new \stdClass(),
			'mapping_order' => []
		];

		$groupByRegion = $hasWilayah;

		if ($hasIndicator && $hasNilai && !$hasWilayah) {
			$valCandidates = ['nilai', 'jumlah', 'jmlh', 'total', 'volume_usaha', 'modal_sendiri', 'modal_luar'];
			$valCols = array_values(array_filter($baseKeys, function($k) use ($valCandidates) {
				return in_array(strtolower($k), $valCandidates);
			}));
			if (empty($valCols)) $valCols = [end($baseKeys)];

			$catCandidates = array_values(array_filter($baseKeys, function($k) use ($ignoreCols, $valCols) {
				return !in_array(strtolower($k), $ignoreCols) && !in_array($k, $valCols);
			}));

			$rowCol = $catCandidates[0] ?? $baseKeys[0];
			$colCol = in_array('tahun_data', $baseKeys) ? 'tahun_data' : ($catCandidates[1] ?? 'tahun_data');

			$pivot['enabled'] = true;
			$pivot['row'] = [$rowCol];
			$pivot['col'] = [$colCol];
			$pivot['val'] = $valCols;

			foreach ($baseKeys as $k) {
				$columns[] = [
					'key' => $k,
					'label_id' => $this->formatTitleLabel($k),
					'label_en' => '',
					'visible' => true,
					'format' => in_array($k, $valCols) ? 'number' : 'text'
				];
			}
		} else {
			// Cek apakah tabel ini memiliki mapping otomatis dari PDF buku DDA
			$matchedJsonPath = WRITEPATH . "uploads/dda_matched_{$ta}.json";
			$pdfPath = WRITEPATH . "uploads/dda_master_{$ta}.pdf";
			$matchedPages = [];
			$cleanNo = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $row['no_tabel'] ?? ''));

			if (file_exists($matchedJsonPath)) {
				$mJson = @file_get_contents($matchedJsonPath);
				$mData = json_decode($mJson, true);
				$matchedList = $mData['matched_in_pdf'] ?? $mData['matched_tables'] ?? [];
				foreach ($matchedList as $mItem) {
					if (trim($mItem['nomor_tabel'] ?? '') === $cleanNo) {
						$matchedPages = $mItem['pages'] ?? [];
						break;
					}
				}
			}

			$pdfMappedColumns = null;
			if (!empty($matchedPages) && file_exists($pdfPath)) {
				$pythonScript = FCPATH . '../python_engine/auto_config_pdf.py';
				$pyBin = $this->getPythonBin();
				$b64Pages = base64_encode(json_encode(array_values($matchedPages)));
				$b64Keys  = base64_encode(json_encode(array_values($baseKeys)));
				$targetYearArg = escapeshellarg($ta);
				$cmd = "{$pyBin} " . escapeshellarg($pythonScript) . " " . escapeshellarg($pdfPath) . " " . escapeshellarg($b64Pages) . " " . escapeshellarg($b64Keys) . " " . $targetYearArg . " 2>&1";
				$rawOut = (string)@shell_exec($cmd);
				if (preg_match('/===JSON_START===(.*?)===JSON_END===/s', $rawOut, $mJson)) {
					$decoded = json_decode($mJson[1], true);
					if (is_array($decoded) && !empty($decoded)) {
						$pdfMappedColumns = $decoded;
					}
				}
			}

			if (!empty($pdfMappedColumns)) {
				$columns = $pdfMappedColumns;
			} else {
				foreach ($baseKeys as $k) {
					$kLower = strtolower($k);
					if (in_array($kLower, $ignoreCols)) {
						$columns[] = [
							'key' => $k,
							'label_id' => $k,
							'label_en' => '',
							'visible' => false,
							'format' => 'number'
						];
					} elseif (in_array($kLower, ['nama_wilayah', 'nama_kabupatenkota', 'kabupaten_kota', 'wilayah'])) {
						$columns[] = [
							'key' => $k,
							'label_id' => 'Kabupaten/Kota',
							'label_en' => 'Regency/Municipality',
							'visible' => true,
						'format' => 'text'
						];
					} else {
						$fmtTitle = $this->formatTitleLabel($k);
						$columns[] = [
							'key' => $k,
							'label_id' => $fmtTitle,
							'label_en' => $this->translateLabelEn($fmtTitle),
							'visible' => true,
							'format' => 'number'
						];
					}
				}
			}
		}

		$smartConfig = [
			'columns' => $columns,
			'pivot' => $pivot,
			'merge_datasets' => false,
			'show_total_col' => false,
			'show_group_total' => false,
			'group_by_region' => $groupByRegion
		];

		// Take first 6 rows for preview
		$previewRows = array_slice($rows, 0, 6);

		return $this->response->setJSON([
			'status'         => 'success',
			'nomor_tabel'    => $row['no_tabel'],
			'judul_tabel'    => $row['judul_ind'],
			'id_db'          => (int)$row['id'],
			'id_api'         => $idApi,
			'total_api_rows' => count($rows),
			'config'         => $smartConfig,
			'preview_rows'   => $previewRows
		]);
	}

	private function translateLabelEn(string $labelId): string
	{
		if (strpos($labelId, ' || ') !== false) {
			$parts = explode(' || ', $labelId);
			$enParts = array_map([$this, 'translateLabelEn'], $parts);
			return implode(' || ', $enParts);
		}

		$dict = [
			'Kabupaten/Kota' => 'Regency/Municipality',
			'Tenaga Medis' => 'Medical Personnel',
			'Psikologi Klinis' => 'Clinical Psychologists',
			'Tenaga Keperawatan' => 'Nursing Personnel',
			'Tenaga Kebidanan' => 'Midwifery Personnel',
			'Tenaga Kefarmasian' => 'Pharmaceutical Personnel',
			'Tenaga Kesehatan Masyarakat' => 'Public Health Personnel',
			'Tenaga Kesehatan Lingkungan' => 'Environmental Health Personnel',
			'Tenaga Gizi' => 'Nutritionists',
			'Tenaga Keterapisan Fisik' => 'Physical Therapists',
			'Tenaga Keteknisian Medis' => 'Medical Technicians',
			'Tenaga Teknik Biomedika' => 'Biomedical Engineering Personnel',
			'Tenaga Kesehatan Tradisional' => 'Traditional Health Personnel',
			'Jumlah Rusak Berat' => 'Number of Heavily Damaged',
			'Jumlah Rusak Sedang' => 'Number of Moderately Damaged',
			'Jumlah Rusak Ringan' => 'Number of Lightly Damaged',
			'Milik Pemerintah' => 'Government Owned',
			'Kementerian Kesehatan' => 'Ministry of Health',
			'Pemerintah Provinsi' => 'Provincial Government',
			'Provinsi' => 'Province',
			'Pemerintah Kabupaten/Kota' => 'Regency/Municipality Government',
			'Polri' => 'Indonesian Police',
			'Kementerian Lain' => 'Other Ministry',
			'Kementrian Lain' => 'Other Ministry',
			'Swasta' => 'Non Government / Private',
			'Bukan Milik Pemerintah' => 'Non Government',
			'Tempat Tidur' => 'Beds',
			'Rumah Sakit' => 'Hospital',
			'Puskesmas' => 'Public Health Center',
			'Klinik' => 'Clinic',
			'Dokter' => 'Doctor',
			'Perawat' => 'Nurse',
			'Bidan' => 'Midwife',
			'Jumlah' => 'Total',
			'Sub Jumlah' => 'Sub Total',
			'Total' => 'Total',
			'Laki-Laki' => 'Male',
			'Perempuan' => 'Female',
			'Luas Panen' => 'Harvested Area',
			'Produksi' => 'Production',
			'Produktivitas' => 'Productivity',
			'Nilai' => 'Value',
			'Volume Usaha' => 'Business Volume',
			'Modal Sendiri' => 'Own Capital',
			'Modal Luar' => 'External Capital',
			'Persentase' => 'Percentage',
			'Panti Asuhan' => 'Orphanage',
			'Anak Yang Diasuh' => 'Children Under Care',
			'Jumlah Panti Asuhan' => 'Number of Orphanages',
			'Jumlah Anak Yang Diasuh' => 'Number of Children Under Care'
		];

		$trimmed = trim($labelId);
		if (isset($dict[$trimmed])) return $dict[$trimmed];

		// Case-insensitive lookup
		foreach ($dict as $k => $v) {
			if (strcasecmp($trimmed, $k) === 0) return $v;
		}

		// Pattern heuristics
		if (preg_match('/^Tenaga\s+(.*)$/i', $trimmed, $m)) {
			return trim($m[1]) . ' Personnel';
		}
		if (preg_match('/^Jumlah\s+(.*)$/i', $trimmed, $m)) {
			return 'Number of ' . trim($m[1]);
		}

		return '';
	}

	private function formatTitleLabel(string $key): string
	{
		$k = str_replace('_', ' ', $key);
		$k = preg_replace('/\bribu rupiah\b/i', '(Ribu Rupiah)', $k);
		$k = preg_replace('/\bjuta rupiah\b/i', '(Juta Rupiah)', $k);
		$k = preg_replace('/\brs\b/i', 'Rumah Sakit', $k);
		$words = explode(' ', $k);
		$titleWords = array_map(function($w) {
			if (preg_match('/^\(.*\)$/', $w)) return $w;
			return ucfirst(strtolower($w));
		}, $words);
		return implode(' ', $titleWords);
	}

	public function auto_config_tabel_pdf()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$nomorTabel = trim((string)$this->request->getPost('nomor_tabel'));
		$idDb       = (int)$this->request->getPost('id_db');
		$pagesRaw   = $this->request->getPost('pages');
		$judulPdf   = trim((string)$this->request->getPost('judul_pdf'));
		$autoFill   = (int)$this->request->getPost('auto_fill');
		$ta         = (string)($this->request->getPost('ta') ?: ($this->session->get('admin_ta') ?: date('Y')));

		if ($nomorTabel === '') {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Nomor tabel wajib diisi.']);
		}

		$pages = is_array($pagesRaw) ? $pagesRaw : (json_decode((string)$pagesRaw, true) ?? []);
		$pdfPath = WRITEPATH . "uploads/dda_master_{$ta}.pdf";
		if (!file_exists($pdfPath)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'File Master PDF belum tersedia.']);
		}

		// 1. Cari entri tabel di master tabel m_list_tabel (atau buat otomatis jika belum ada)
		$cleanNo = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $nomorTabel));
		$cleanNo = rtrim($cleanNo, '.');

		$tabelRow = null;
		if ($idDb > 0) {
			$tabelRow = $this->db->query("
				SELECT m.id, m.no_tabel, m.judul_ind, m.judul_en, m.id_unitkerja
				FROM m_list_tabel m
				WHERE m.id = ?
			", [$idDb])->getRow();
		}
		if (!$tabelRow) {
			$tabelRow = $this->db->query("
				SELECT m.id, m.no_tabel, m.judul_ind, m.judul_en, m.id_unitkerja
				FROM m_list_tabel m
				WHERE (TRIM(m.no_tabel) = ? OR TRIM(REPLACE(m.no_tabel, 'Tabel ', '')) = ?)
				LIMIT 1
			", [$cleanNo, $cleanNo])->getRow();
		}

		if (!$tabelRow) {
			// Tabel belum terdaftar di m_list_tabel -> Daftarkan otomatis
			$titleInd = $judulPdf !== '' ? $judulPdf : "Tabel {$cleanNo}";
			$this->db->table('m_list_tabel')->insert([
				'no_tabel'     => $cleanNo,
				'judul_ind'    => $titleInd,
				'judul_en'     => $titleInd,
				'id_unitkerja' => 'bps',
				'kondef'       => '-',
				'jenis_sumber' => 'dinamis',
			]);
			$newMasterId = (int)$this->db->insertID();
			$tabelRow = (object)[
				'id'           => $newMasterId,
				'no_tabel'     => $cleanNo,
				'judul_ind'    => $titleInd,
				'judul_en'     => $titleInd,
				'id_unitkerja' => 'bps',
			];
		}

		$idMasterTabel = (int)$tabelRow->id;

		// Jika pages kosong, ambil dari dda_matched_{ta}.json
		if (empty($pages)) {
			$matchedPath = WRITEPATH . "uploads/dda_matched_{$ta}.json";
			if (file_exists($matchedPath)) {
				$mData = json_decode(@file_get_contents($matchedPath), true);
				foreach (($mData['unregistered_in_db'] ?? []) as $uRow) {
					if (($uRow['nomor_tabel'] ?? '') === $cleanNo && !empty($uRow['pages'])) {
						$pages = $uRow['pages'];
						break;
					}
				}
			}
		}

		if (empty($pages)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Halaman PDF untuk tabel ini tidak ditemukan.']);
		}

		// 2. Jalankan Python extractor auto_config_pdf.py
		$pythonScript = FCPATH . '../python_engine/auto_config_pdf.py';
		$pyBin = $this->getPythonBin();
		$cmd = "{$pyBin} " . escapeshellarg($pythonScript) . " " . escapeshellarg($pdfPath) . " " . escapeshellarg(json_encode(array_values($pages))) . " 2>&1";
		$rawOut = (string)@shell_exec($cmd);

		$ext = null;
		if (preg_match('/===JSON_START===(.*?)===JSON_END===/s', $rawOut, $mJson)) {
			$ext = json_decode($mJson[1], true);
		}
		if (!is_array($ext) || ($ext['status'] ?? '') !== 'success') {
			$errMsg = $ext['message'] ?? 'Gagal mengekstrak struktur tabel dari PDF.';
			return $this->response->setJSON(['status' => 'error', 'message' => $errMsg]);
		}

		$mDinamis = new \App\Models\M_tabeldinamis();

		// 3. Resolusi Dimensi Baris (Kabupaten/Kota vs Kustom)
		$idBarisKelompok = null;
		$konfigurasiTotal = $ext['konfigurasi_total'] ?? 'default';

		if (!empty($ext['is_kabkota'])) {
			$idBarisKelompok = 1; // Master Kabupaten/Kota Jawa Tengah
		} elseif (!empty($ext['rows'])) {
			$rawRows = $ext['rows'];
			// Jika baris terakhir adalah baris total yang sudah ditangani konfigurasi_total, jangan duplikasi di item
			if ($konfigurasiTotal !== 'default' && count($rawRows) > 1) {
				$lastInd = strtolower(trim($rawRows[count($rawRows) - 1]['ind'] ?? ''));
				if (strpos($lastInd, 'jawa tengah') !== false || in_array($lastInd, ['jumlah', 'total', 'jumlah/total'], true)) {
					array_pop($rawRows);
				}
			}

			// Cek apakah sudah ada kelompok baris dengan daftar item yang sama
			$targetRowNorms = array_map(function ($r) {
				return mb_strtolower(trim(preg_replace('/^\d+\.\s*/', '', $r['ind'] ?? '')));
			}, $rawRows);

			$allBarisItems = $this->db->query("SELECT id_kelompok, nama_baris_ind FROM m_baris_item ORDER BY id_kelompok ASC, no_urut ASC")->getResultArray();
			$barisByKel = [];
			foreach ($allBarisItems as $bi) {
				$barisByKel[(int)$bi['id_kelompok']][] = mb_strtolower(trim(preg_replace('/^\d+\.\s*/', '', $bi['nama_baris_ind'])));
			}
			foreach ($barisByKel as $kid => $kNorms) {
				if (count($kNorms) === count($targetRowNorms) && count($kNorms) > 0) {
					$matches = 0;
					foreach ($kNorms as $idx => $kn) {
						if ($kn === $targetRowNorms[$idx]) {
							$matches++;
						}
					}
					if (($matches / count($kNorms)) >= 0.85) {
						$idBarisKelompok = $kid;
						break;
					}
				}
			}

			if (!$idBarisKelompok) {
				$rhInd = trim($ext['row_header']['ind'] ?? 'Rincian');
				$rhEn  = trim($ext['row_header']['en'] ?? 'Description');
				$namaKelBaris = mb_substr("{$rhInd} (Tabel {$cleanNo})", 0, 190);
				$idBarisKelompok = $mDinamis->saveBarisKelompok([
					'nama_kelompok_ind' => $namaKelBaris,
					'nama_kelompok_en'  => mb_substr("{$rhEn} (Table {$cleanNo})", 0, 190),
					'judul_baris_ind'   => mb_substr($rhInd, 0, 190),
					'judul_baris_en'    => mb_substr($rhEn, 0, 190),
				]);
				$barisPayload = [];
				foreach ($rawRows as $idx => $rItem) {
					$barisPayload[] = [
						'nama_baris_ind'  => $rItem['ind'],
						'nama_baris_en'   => $rItem['en'] ?: $rItem['ind'],
						'level_indentasi' => 0,
						'no_urut'         => $idx + 1,
					];
				}
				$mDinamis->syncBarisItems($idBarisKelompok, $barisPayload);
			}
		}

		// 4. Resolusi Dimensi Periode
		$idPeriodeKelompok = null;
		$tipePeriode = 'tahunan';
		$periods = $ext['periods'] ?? [];
		$isAllYearNumbers = !empty($periods) && empty(array_filter($periods, function($p) {
			return !preg_match('/^\d{4}$/', trim((string)$p));
		}));
		$isAllAcademicYears = !empty($periods) && empty(array_filter($periods, function($p) {
			return !preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', trim((string)$p));
		}));
		$tahunTampilPdf = null;

		if ($isAllYearNumbers) {
			$tipePeriode = 'tahunan';
			$idPeriodeKelompok = null;
			$tahunTampilPdf = implode(',', array_map('intval', $periods));
		} elseif ($isAllAcademicYears) {
			$tipePeriode = 'tahun_ajaran';
			$idPeriodeKelompok = null;
			$startYrs = array_map(function($p) {
				return (int)substr(trim((string)$p), 0, 4);
			}, $periods);
			$tahunTampilPdf = implode(',', $startYrs);
		} elseif (!empty($periods)) {
			$targetPerNorm = array_map(function ($p) {
				return mb_strtolower(trim((string)$p));
			}, $periods);

			$allPerItems = $this->db->query("SELECT id_kelompok, nama_periode_ind FROM m_periode_item ORDER BY id_kelompok ASC, no_urut ASC")->getResultArray();
			$perByKel = [];
			foreach ($allPerItems as $pi) {
				$perByKel[(int)$pi['id_kelompok']][] = mb_strtolower(trim($pi['nama_periode_ind']));
			}
			foreach ($perByKel as $pid => $pNorms) {
				if ($pNorms === $targetPerNorm) {
					$idPeriodeKelompok = $pid;
					break;
				}
			}

			if (!$idPeriodeKelompok) {
				$namaKelPer = mb_substr('Periode ' . implode(', ', $periods) . " (Tabel {$cleanNo})", 0, 190);
				$idPeriodeKelompok = $mDinamis->savePeriodeKelompok([
					'nama_kelompok_ind' => $namaKelPer,
					'nama_kelompok_en'  => $namaKelPer,
					'judul_periode_ind' => 'Periode',
					'judul_periode_en'  => 'Period',
				]);
				$perPayload = [];
				foreach ($periods as $idx => $pVal) {
					$perPayload[] = [
						'nama_periode_ind' => (string)$pVal,
						'nama_periode_en'  => (string)$pVal,
						'no_urut'          => $idx + 1,
					];
				}
				$mDinamis->syncPeriodeItems($idPeriodeKelompok, $perPayload);
			}
			$tipePeriode = 'custom';
		}

		// 5. Resolusi Dimensi Karakteristik (Kolom)
		$idKarakteristikKelompok = null;
		$karItems = $ext['karakteristik'] ?? [];
		if (!empty($karItems)) {
			$targetKarNorms = array_map(function ($k) {
				$parts = array_map('trim', explode('||', mb_strtolower($k['item_ind'] ?? '')));
				return implode('|', $parts);
			}, $karItems);

			$allKarItems = $this->db->query("SELECT id_kelompok, grup_induk_ind, grup_sub_ind, item_ind FROM m_karakteristik_item ORDER BY id_kelompok ASC, no_urut ASC")->getResultArray();
			$karByKel = [];
			foreach ($allKarItems as $ki) {
				$pParts = array_filter([
					trim(mb_strtolower($ki['grup_induk_ind'] ?? '')),
					trim(mb_strtolower($ki['grup_sub_ind'] ?? '')),
					trim(mb_strtolower($ki['item_ind'] ?? '')),
				], function ($v) { return $v !== ''; });
				$karByKel[(int)$ki['id_kelompok']][] = implode('|', $pParts);
			}
			foreach ($karByKel as $kid => $kNorms) {
				if (count($kNorms) === count($targetKarNorms) && count($kNorms) > 0) {
					$matches = 0;
					foreach ($kNorms as $idx => $kn) {
						$tn = $targetKarNorms[$idx] ?? '';
						$isMatch = ($kn === $tn);
						if (!$isMatch) {
							$knIsTotal = (strpos($kn, 'jumlah') !== false || strpos($kn, 'total') !== false);
							$tnHasPlus = (strpos($tn, '+') !== false);
							$knHasPlus = (strpos($kn, '+') !== false);
							$tnIsTotal = (strpos($tn, 'jumlah') !== false || strpos($tn, 'total') !== false);
							if (($knIsTotal && $tnHasPlus) || ($knHasPlus && $tnIsTotal)) {
								$isMatch = true;
							}
						}
						if ($isMatch) {
							$matches++;
						}
					}
					if (($matches / count($kNorms)) >= 0.85) {
						$idKarakteristikKelompok = $kid;
						break;
					}
				}
			}

			if (!$idKarakteristikKelompok) {
				$sampleLabels = array_slice(array_map(function ($k) {
					return str_replace(' || ', ' - ', $k['item_ind']);
				}, $karItems), 0, 3);
				$namaKelKar = mb_substr(implode(', ', $sampleLabels) . " (Tabel {$cleanNo})", 0, 190);
				$idKarakteristikKelompok = $mDinamis->saveKarakteristikKelompok([
					'nama_kelompok_ind' => $namaKelKar,
					'nama_kelompok_en'  => $namaKelKar,
				]);
				$karPayload = [];
				foreach ($karItems as $idx => $kItem) {
					$karPayload[] = [
						'item_ind' => $kItem['item_ind'],
						'item_en'  => $kItem['item_en'] ?: $kItem['item_ind'],
						'no_urut'  => $idx + 1,
					];
				}
				$mDinamis->syncKarakteristikItems($idKarakteristikKelompok, $karPayload);
			}
		}

		$dimCount = ($idBarisKelompok ? 1 : 0) + ($idKarakteristikKelompok ? 1 : 0) + (($idPeriodeKelompok || $tipePeriode === 'tahunan' || $tipePeriode === 'tahun_ajaran') ? 1 : 0);
		if ($dimCount < 2) {
			return $this->response->setJSON([
				'status'  => 'error',
				'message' => 'Struktur tabel PDF tidak memiliki minimal 2 dimensi (Baris & Kolom/Periode). Silakan gunakan Konfigurasi Manual.'
			]);
		}

		// 6. Simpan m_indikator_tabel & aktifkan jenis_sumber = dinamis di m_list_tabel
		$now = date('Y-m-d H:i:s');
		$idMasterTabel = (int)$tabelRow->id;
		$idTahunTabel  = $idDb > 0 ? $idDb : $idMasterTabel;
		$existingInd = $mDinamis->where('id_tabel', $idMasterTabel)->first();
		$indPayload = [
			'id_tabel'                  => $idMasterTabel,
			'no_tabel'                  => $tabelRow->no_tabel ?: $cleanNo,
			'judul_ind'                 => $tabelRow->judul_ind,
			'judul_en'                  => $tabelRow->judul_en,
			'id_unitkerja'              => $tabelRow->id_unitkerja ?: 'bps',
			'id_karakteristik_kelompok' => $idKarakteristikKelompok,
			'id_baris_kelompok'         => $idBarisKelompok,
			'tipe_periode'              => $tipePeriode ?? 'tahunan',
			'id_periode_kelompok'       => $idPeriodeKelompok,
			'posisi_hirarki'            => $ext['posisi_hirarki'] ?? 'kolom_atas',
			'tingkat_kolom'             => 'semua',
			'konfigurasi_total'         => $konfigurasiTotal,
			'format_nomor_baris'        => 'none',
			'desimal'                   => isset($ext['desimal']) ? (int)$ext['desimal'] : 0,
			'tahun_tampil'              => $tahunTampilPdf,
			'updated_at'                => $now,
		];

		if ($existingInd) {
			$idIndikator = (int)$existingInd->id;
			$mDinamis->update($idIndikator, $indPayload);
		} else {
			$indPayload['created_at'] = $now;
			$mDinamis->insert($indPayload);
			$idIndikator = (int)$this->db->insertID();
		}

		$this->db->table('m_list_tabel')->where('id', $idMasterTabel)->update(['jenis_sumber' => 'dinamis']);
		$mDinamis->upsertStatusTahunan($idMasterTabel, (int)$ta, []);

		// 7. Update dda_db_{ta}.json, dda_matched_{ta}.json, dan dda_state_{ta}.json secara langsung
		$dbJsonPath      = WRITEPATH . "uploads/dda_db_{$ta}.json";
		$matchedJsonPath = WRITEPATH . "uploads/dda_matched_{$ta}.json";
		$stateJsonPath   = WRITEPATH . "uploads/dda_state_{$ta}.json";

		$dynInfo = $mDinamis->exportSingleDynamicTable($idMasterTabel);
		$formattedJudul = function_exists('format_judul_tabel')
			? format_judul_tabel($tabelRow->judul_ind, $ta)
			: $tabelRow->judul_ind;

		if (file_exists($dbJsonPath)) {
			$dbList = json_decode(@file_get_contents($dbJsonPath), true) ?? [];
			$foundInDbJson = false;
			foreach ($dbList as &$dbItem) {
				if ((int)($dbItem['id_db'] ?? 0) === $idTahunTabel || trim($dbItem['nomor_tabel'] ?? '') === $cleanNo) {
					$dbItem['id_db']         = $idTahunTabel;
					$dbItem['id_indikator']  = $idIndikator;
					$dbItem['nomor_tabel']   = $cleanNo;
					$dbItem['judul_tabel']   = $formattedJudul;
					$dbItem['is_dynamic']    = 1;
					$dbItem['source_type']   = 'dinamis';
					$dbItem['dynamic_empty'] = $dynInfo ? (bool)$dynInfo['dynamic_empty'] : true;
					$dbItem['dynamic_grid']  = $dynInfo ? ($dynInfo['dynamic_grid'] ?? []) : [];
					$dbItem['template_grid'] = $dynInfo ? ($dynInfo['template_grid'] ?? []) : [];
					$dbItem['cell_coords']   = $dynInfo ? ($dynInfo['cell_coords'] ?? []) : [];
					$foundInDbJson = true;
					break;
				}
			}
			unset($dbItem);
			if (!$foundInDbJson) {
				$dbList[] = [
					'id_db'         => $idTahunTabel,
					'id_indikator'  => $idIndikator,
					'nomor_tabel'   => $cleanNo,
					'judul_tabel'   => $formattedJudul,
					'link_tabel'    => '-',
					'is_dynamic'    => 1,
					'source_type'   => 'dinamis',
					'dynamic_empty' => $dynInfo ? (bool)$dynInfo['dynamic_empty'] : true,
					'dynamic_grid'  => $dynInfo ? ($dynInfo['dynamic_grid'] ?? []) : [],
					'template_grid' => $dynInfo ? ($dynInfo['template_grid'] ?? []) : [],
					'cell_coords'   => $dynInfo ? ($dynInfo['cell_coords'] ?? []) : [],
				];
			}
			@file_put_contents($dbJsonPath, json_encode($dbList, JSON_UNESCAPED_UNICODE));
		}

		$newMatchedEntry = [
			'nomor_tabel'   => $cleanNo,
			'id_db'         => (string)$idTahunTabel,
			'judul_db'      => $formattedJudul,
			'judul_pdf'     => $judulPdf ?: $formattedJudul,
			'pages'         => array_values($pages),
			'similarity'    => 100.0,
			'link_tabel'    => '-',
			'source_type'   => 'dinamis',
			'is_dynamic'    => 1,
			'dynamic_empty' => true,
		];

		$stateEntry = [
			'status'      => 'api_empty',
			'match_count' => 0,
			'diff_count'  => 0,
			'reason'      => 'Data nilai Tabel Dinamis belum diisi di database.',
			'updated_at'  => date('Y-m-d H:i:s'),
		];

		$filledCellsCount = 0;

		// 8. Jika auto_fill = 1, langsung ekstrak angka dari PDF dan simpan ke t_nilai_dinamis
		if ($autoFill === 1 && file_exists($dbJsonPath)) {
			$compareScript = FCPATH . '../python_engine/compare_table.py';
			$pyBin = $this->getPythonBin();
			$cmdVerify = "{$pyBin} " . escapeshellarg($compareScript) . " " . escapeshellarg($pdfPath) . " " . escapeshellarg($dbJsonPath) . " " . escapeshellarg($cleanNo) . " 0.0 2>&1";
			$outVerify = @shell_exec($cmdVerify);
			$verRes = $outVerify ? json_decode(mb_convert_encoding($outVerify, 'UTF-8', 'UTF-8'), true) : null;

			if (is_array($verRes) && !empty($verRes['autofill_cells'])) {
				$upsertCells = [];
				foreach ($verRes['autofill_cells'] as $ac) {
					$bId = (int)($ac['id_baris_item'] ?? 0);
					$kId = (int)($ac['id_karakteristik_item'] ?? 0);
					$pId = (int)($ac['id_periode_item'] ?? 0);
					$tahunDataCell = (int)($ac['tahun_data'] ?? $ta);
					if ($pId > 190000000) {
						$tahunDataCell = intdiv($pId, 100000);
						$pId           = $pId % 100000;
					} elseif ($pId >= 1900 && $pId <= 2100) {
						$tahunDataCell = $pId;
						$pId           = 0;
					}
					if ($bId >= 0 && $kId >= 0) {
						$cRaw = $ac['nilai'] ?? null;
						$cRawStr = is_string($cRaw) ? trim($cRaw) : (is_numeric($cRaw) ? (string)$cRaw : '');
						$dashTokens = ['-', '–', '—', '−', "\u{2212}", "\u{2011}", "\u{2015}"];
						$dotTokens  = ['...', '…', "\u{2026}", 'na', 'n.a', 'n.a.', 'n/a'];

						if (in_array($cRawStr, $dashTokens, true)) {
							$cVal = 0.0;
							$cSimb = '-';
						} elseif (in_array($cRawStr, $dotTokens, true) || $cRawStr === '') {
							$cVal = null;
							$cSimb = '...';
						} elseif (is_numeric($cRaw)) {
							$cNum = (float)$cRaw;
							if (abs($cNum) < 0.0000001) {
								$cVal = 0.0;
								$cSimb = '-';
							} else {
								$cVal = $cNum;
								$cSimb = null;
							}
						} else {
							$cVal = null;
							$cSimb = $cRawStr !== '' ? $cRawStr : null;
						}

						$upsertCells[] = [
							'id_baris_item'         => $bId,
							'id_karakteristik_item' => $kId,
							'id_periode_item'       => $pId > 0 ? $pId : 0,
							'tahun_data'            => $tahunDataCell,
							'nilai'                 => $cVal,
							'simbol'                => $cSimb,
						];
					}
				}
				if (!empty($upsertCells)) {
					$filledCellsCount = $mDinamis->batchUpsertNilai($idIndikator, $upsertCells);
					if ($filledCellsCount > 0) {
						$uploaderName = $this->session->get('admin_nama') ?: ($this->session->get('admin_user') ?: 'Administrator');
						$catatan = "Diunggah oleh {$uploaderName} pada " . date('d/m/Y H:i');
						$mDinamis->upsertStatusTahunan($idMasterTabel, (int)$ta, [
							'is_periksa'      => 1,
							'catatan_periksa' => $catatan
						]);
					}
					// Perbarui snapshot dda_db_{ta}.json dengan nilai yang baru diisi
					$dynInfoAfter = $mDinamis->exportSingleDynamicTable($idMasterTabel);
					$dbList = json_decode(@file_get_contents($dbJsonPath), true) ?? [];
					foreach ($dbList as &$dbItem) {
						if ((int)($dbItem['id_db'] ?? 0) === $idMasterTabel || trim($dbItem['nomor_tabel'] ?? '') === $cleanNo) {
							$dbItem['dynamic_empty'] = false;
							$dbItem['dynamic_grid']  = $dynInfoAfter['dynamic_grid'] ?? [];
							break;
						}
					}
					unset($dbItem);
					@file_put_contents($dbJsonPath, json_encode($dbList, JSON_UNESCAPED_UNICODE));

					$newMatchedEntry['dynamic_empty'] = false;

					// Verifikasi ulang untuk mendapatkan status match resmi
					$outVerify2 = @shell_exec($cmdVerify);
					$verRes2 = $outVerify2 ? json_decode(mb_convert_encoding($outVerify2, 'UTF-8', 'UTF-8'), true) : null;
					if (is_array($verRes2) && ($verRes2['status'] ?? '') === 'success') {
						$diffCnt = count($verRes2['diffs'] ?? []);
						$matchCnt = count($verRes2['matches'] ?? []);
						$stateEntry = [
							'status'      => ($diffCnt > 0) ? 'diff' : 'match',
							'match_count' => $matchCnt,
							'diff_count'  => $diffCnt,
							'updated_at'  => date('Y-m-d H:i:s'),
						];
					}
				}
			}
		}

		// Update dda_matched_{ta}.json (pindahkan dari unregistered_in_db ke matched_in_pdf)
		if (file_exists($matchedJsonPath)) {
			$mData = json_decode(@file_get_contents($matchedJsonPath), true) ?? [];
			if (isset($mData['unregistered_in_db']) && is_array($mData['unregistered_in_db'])) {
				$mData['unregistered_in_db'] = array_values(array_filter($mData['unregistered_in_db'], function ($u) use ($cleanNo) {
					return trim($u['nomor_tabel'] ?? '') !== $cleanNo;
				}));
				$mData['unregistered_count'] = count($mData['unregistered_in_db']);
			}
			$listKey = isset($mData['matched_in_pdf']) ? 'matched_in_pdf' : 'matched_tables';
			$mList = $mData[$listKey] ?? [];
			$alreadyInMatched = false;
			foreach ($mList as &$mItem) {
				if (trim($mItem['nomor_tabel'] ?? '') === $cleanNo) {
					$mItem = $newMatchedEntry;
					$alreadyInMatched = true;
					break;
				}
			}
			unset($mItem);
			if (!$alreadyInMatched) {
				$mList[] = $newMatchedEntry;
			}
			$mData[$listKey] = $mList;
			$mData['matched_count'] = count($mList);
			$mData['db_sig'] = $this->getDynamicConfigSignature($ta);
			@file_put_contents($matchedJsonPath, json_encode($mData, JSON_UNESCAPED_UNICODE));
		}

		// Update dda_state_{ta}.json
		$stateData = file_exists($stateJsonPath) ? (json_decode(@file_get_contents($stateJsonPath), true) ?? []) : [];
		$stateData[$cleanNo] = $stateEntry;
		@file_put_contents($stateJsonPath, json_encode($stateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

		$msg = $filledCellsCount > 0
			? "Tabel {$cleanNo} berhasil dikonfigurasi otomatis dari PDF dan {$filledCellsCount} sel angka langsung terisi!"
			: "Tabel {$cleanNo} berhasil dikonfigurasi otomatis menjadi Tabel Dinamis (Status: Data Kosong)!";

		return $this->response->setJSON([
			'status'        => 'success',
			'message'       => $msg,
			'matched_entry' => $newMatchedEntry,
			'state_entry'   => $stateEntry,
			'filled_cells'  => $filledCellsCount,
		]);
	}

	/**
	 * Menampilkan berkas PDF Master secara inline untuk fitur Split-View di Modal Audit.
	 */
	public function serve_master_pdf()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(403)->setBody('Unauthorized');
		}

		$ta = $this->request->getGet('ta') ?? $this->session->get('admin_ta') ?? date('Y');
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: date('Y');
		$pdfPath = WRITEPATH . "uploads/dda_master_{$ta}.pdf";

		if (!file_exists($pdfPath)) {
			return $this->response->setStatusCode(404)->setBody('File PDF Master tidak ditemukan.');
		}

		$size = filesize($pdfPath);
		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setHeader('Content-Disposition', 'inline; filename="dda_master_' . $ta . '.pdf"')
			->setHeader('Accept-Ranges', 'bytes')
			->setHeader('Content-Length', (string)$size)
			->setBody(file_get_contents($pdfPath));
	}

	/**
	 * Merender dan menyajikan halaman spesifik dari PDF Master sebagai gambar PNG resolusi tinggi.
	 * Sangat andal untuk Split-View / Preview di browser tanpa memicu forced download atau kendala plugin browser.
	 */
	public function serve_pdf_page()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(403)->setBody('Unauthorized');
		}

		$ta = $this->request->getGet('ta') ?? $this->session->get('admin_ta') ?? date('Y');
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: date('Y');
		$page = (int)($this->request->getGet('page') ?? 1);
		if ($page < 1) $page = 1;
		$dpi = (int)($this->request->getGet('dpi') ?? 150);
		if ($dpi < 72 || $dpi > 300) $dpi = 150;

		$pdfPath = WRITEPATH . "uploads/dda_master_{$ta}.pdf";
		if (!file_exists($pdfPath)) {
			return $this->response->setStatusCode(404)->setBody('File PDF Master tidak ditemukan.');
		}

		$cacheDir = WRITEPATH . 'cache/pdf_pages/';
		if (!is_dir($cacheDir)) {
			mkdir($cacheDir, 0777, true);
		}
		$cacheFile = $cacheDir . "dda_{$ta}_p{$page}_{$dpi}.png";

		if (!file_exists($cacheFile) || filesize($cacheFile) === 0) {
			$pythonScript = FCPATH . '../python_engine/render_pdf_page.py';
			$pyBin = $this->getPythonBin();
			$cmd = "{$pyBin} " . escapeshellarg($pythonScript) . " --pdf " . escapeshellarg($pdfPath) . " --page " . (int)$page . " --output " . escapeshellarg($cacheFile) . " --dpi " . (int)$dpi . " 2>&1";
			$out = shell_exec($cmd);

			if (!file_exists($cacheFile) || filesize($cacheFile) === 0) {
				log_message('error', 'Gagal render halaman PDF: ' . ($out ?? ''));
				return $this->response->setStatusCode(500)->setBody('Gagal merender halaman PDF: ' . htmlspecialchars($out ?? 'Unknown error'));
			}
		}

		return $this->response
			->setHeader('Content-Type', 'image/png')
			->setHeader('Cache-Control', 'public, max-age=86400')
			->setBody(file_get_contents($cacheFile));
	}

	/**
	 * Mengekspor Laporan Rekapitulasi Global hasil pencocokan & verifikasi seluruh tabel ke Excel (.xls).
	 */
	public function export_rekap_matching()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$ta = $this->request->getGet('ta') ?? $this->session->get('admin_ta') ?? '2026';
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: '2026';
		$source = strtolower(trim((string)($this->request->getGet('source') ?? 'all')));
		if (!in_array($source, ['api', 'dinamis', 'all'], true)) {
			$source = 'all';
		}
		$suffix = '';
		if ($source === 'api') {
			$suffix = '_API';
		} elseif ($source === 'dinamis') {
			$suffix = '_Dinamis';
		}

		$uploadPath = WRITEPATH . 'uploads/';
		$canonicalExcel = $uploadPath . "Rekap_Tabel_Selisih_DDA_{$ta}{$suffix}.xlsx";
		if (file_exists($canonicalExcel) && filesize($canonicalExcel) > 5000) {
			return $this->response->download($canonicalExcel, null)->setFileName("Rekap_Tabel_Selisih_DDA_{$ta}{$suffix}.xlsx");
		}

		// Cek jika ada file generate sebelumnya dengan timestamp
		$recentFiles = glob($uploadPath . "Rekap_Tabel_Selisih_DDA_{$ta}{$suffix}_*.xlsx");
		if (!empty($recentFiles)) {
			usort($recentFiles, function($a, $b) {
				return filemtime($b) - filemtime($a);
			});
			$latest = $recentFiles[0];
			if (file_exists($latest) && filesize($latest) > 5000) {
				@copy($latest, $canonicalExcel);
				return $this->response->download($latest, null)->setFileName("Rekap_Tabel_Selisih_DDA_{$ta}{$suffix}.xlsx");
			}
		}

		// Jika belum ada file, arahkan ke halaman matching_pdf dengan trigger untuk generate di modal
		return redirect()->to("admin/matching_pdf?ta={$ta}&source={$source}&trigger_export=1");
	}

	/**
	 * Mengecek status ketersediaan file Rekap Global Excel (.xlsx) dan status worker background
	 */
	public function ajax_check_rekap_excel()
	{
		$ta = $this->request->getGetPost('ta') ?? $this->session->get('admin_ta') ?? '2026';
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: '2026';
		$source = strtolower(trim((string)($this->request->getGetPost('source') ?? 'all')));
		if (!in_array($source, ['api', 'dinamis', 'all'], true)) {
			$source = 'all';
		}
		$suffix = '';
		if ($source === 'api') {
			$suffix = '_API';
		} elseif ($source === 'dinamis') {
			$suffix = '_Dinamis';
		}

		$uploadPath = WRITEPATH . 'uploads/';
		$canonicalExcel = $uploadPath . "Rekap_Tabel_Selisih_DDA_{$ta}{$suffix}.xlsx";
		$progressJson = $uploadPath . "dda_export_progress_{$ta}" . ($suffix ? strtolower($suffix) : '') . ".json";

		$exists = file_exists($canonicalExcel) && filesize($canonicalExcel) > 5000;
		$mtimeStr = $exists ? date('d M Y H:i', filemtime($canonicalExcel)) : null;
		$fileSizeKb = $exists ? round(filesize($canonicalExcel) / 1024, 1) : 0;

		$isRunning = false;
		$percent = 0;
		$curTable = '';
		if (file_exists($progressJson)) {
			$pData = json_decode(@file_get_contents($progressJson), true) ?? [];
			$isRunning = !empty($pData['is_running']);
			$percent = $pData['percent'] ?? 0;
			$curTable = $pData['current_table'] ?? '';
			if ($isRunning && !empty($pData['updated_at']) && (time() - $pData['updated_at'] > 600)) {
				$isRunning = false;
			}
		}

		return $this->response->setJSON([
			'status' => 'success',
			'source' => $source,
			'file_exists' => $exists,
			'filesize_kb' => $fileSizeKb,
			'updated_at' => $mtimeStr,
			'is_running' => $isRunning,
			'percent' => $percent,
			'current_table' => $curTable,
			'download_url' => base_url("admin/export_rekap_matching?ta={$ta}&source={$source}")
		]);
	}

	/**
	 * Memulai background worker untuk menyusun Rekap Global Excel Multi-Sheet
	 */
	public function start_export_rekap()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$ta = $this->request->getPost('ta') ?? $this->session->get('admin_ta') ?? '2026';
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: '2026';
		$source = strtolower(trim((string)($this->request->getPost('source') ?? 'all')));
		if (!in_array($source, ['api', 'dinamis', 'all'], true)) {
			$source = 'all';
		}
		$suffix = '';
		if ($source === 'api') {
			$suffix = '_API';
		} elseif ($source === 'dinamis') {
			$suffix = '_Dinamis';
		}

		$uploadPath = WRITEPATH . 'uploads/';
		$pdfMaster = $uploadPath . "dda_master_{$ta}.pdf";
		$matchedJson = $uploadPath . "dda_matched_{$ta}.json";
		$dbJson = $uploadPath . "dda_db_{$ta}.json";
		$stateJson = $uploadPath . "dda_state_{$ta}.json";
		$canonicalExcel = $uploadPath . "Rekap_Tabel_Selisih_DDA_{$ta}{$suffix}.xlsx";
		$progressJson = $uploadPath . "dda_export_progress_{$ta}" . ($suffix ? strtolower($suffix) : '') . ".json";
		$stopFile = $progressJson . '.stop';

		if (file_exists($stopFile)) {
			@unlink($stopFile);
		}

		if (!file_exists($pdfMaster) || !file_exists($matchedJson) || !file_exists($dbJson) || !file_exists($stateJson)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'File master PDF atau data hasil verifikasi belum lengkap']);
		}

		// Pastikan tidak ada worker duplikat yang sedang berjalan untuk sumber ini
		if (file_exists($progressJson)) {
			$pData = json_decode(@file_get_contents($progressJson), true) ?? [];
			if (!empty($pData['is_running']) && !empty($pData['updated_at']) && (time() - $pData['updated_at'] < 180)) {
				return $this->response->setJSON(['status' => 'success', 'message' => 'Worker rekap sudah berjalan', 'already_running' => true]);
			}
		}

		// Inisialisasi status progress
		$initProg = [
			'status' => 'running',
			'source' => $source,
			'is_running' => true,
			'completed' => 0,
			'total' => 0,
			'percent' => 0,
			'current_table' => 'Menyiapkan proses...',
			'updated_at' => time()
		];
		@file_put_contents($progressJson, json_encode($initProg));

		$pyScript = FCPATH . "../python_engine/export_global_excel.py";
		$errLog = $uploadPath . "dda_export_stderr_{$ta}" . ($suffix ? strtolower($suffix) : '') . ".log";
		$this->runBackgroundPython($pyScript, [
			$pdfMaster,
			$matchedJson,
			$dbJson,
			$stateJson,
			$canonicalExcel,
			'0.0',
			$progressJson,
			$source
		], $errLog);

		return $this->response->setJSON(['status' => 'success', 'message' => 'Background worker rekap excel berhasil dimulai', 'source' => $source]);
	}

	/**
	 * Mengambil progres terkini dari background worker Rekap Global Excel
	 */
	public function get_export_progress()
	{
		$ta = $this->request->getGetPost('ta') ?? $this->session->get('admin_ta') ?? '2026';
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: '2026';
		$source = strtolower(trim((string)($this->request->getGetPost('source') ?? 'all')));
		if (!in_array($source, ['api', 'dinamis', 'all'], true)) {
			$source = 'all';
		}
		$suffix = '';
		if ($source === 'api') {
			$suffix = '_API';
		} elseif ($source === 'dinamis') {
			$suffix = '_Dinamis';
		}

		$uploadPath = WRITEPATH . 'uploads/';
		$progressJson = $uploadPath . "dda_export_progress_{$ta}" . ($suffix ? strtolower($suffix) : '') . ".json";
		$canonicalExcel = $uploadPath . "Rekap_Tabel_Selisih_DDA_{$ta}{$suffix}.xlsx";

		$prog = [
			'status' => 'idle',
			'source' => $source,
			'is_running' => false,
			'completed' => 0,
			'total' => 0,
			'percent' => 0,
			'current_table' => '',
			'file_ready' => false
		];

		if (file_exists($progressJson)) {
			$raw = @file_get_contents($progressJson);
			if (!empty($raw)) {
				$parsed = json_decode($raw, true);
				if (is_array($parsed)) {
					$prog = array_merge($prog, $parsed);
				}
			}
		}

		if (file_exists($canonicalExcel) && filesize($canonicalExcel) > 5000) {
			$prog['file_ready'] = true;
			$prog['download_url'] = base_url("admin/export_rekap_matching?ta={$ta}&source={$source}");
			$prog['filesize_kb'] = round(filesize($canonicalExcel) / 1024, 1);
			$prog['updated_at'] = date('d M Y H:i', filemtime($canonicalExcel));
		}

		return $this->response->setJSON($prog);
	}

	/**
	 * Menghentikan proses background worker Rekap Global Excel
	 */
	public function stop_export_rekap()
	{
		$ta = $this->request->getPost('ta') ?? $this->session->get('admin_ta') ?? '2026';
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: '2026';
		$source = strtolower(trim((string)($this->request->getPost('source') ?? 'all')));
		if (!in_array($source, ['api', 'dinamis', 'all'], true)) {
			$source = 'all';
		}
		$suffix = '';
		if ($source === 'api') {
			$suffix = '_API';
		} elseif ($source === 'dinamis') {
			$suffix = '_Dinamis';
		}

		$uploadPath = WRITEPATH . 'uploads/';
		$progressJson = $uploadPath . "dda_export_progress_{$ta}" . ($suffix ? strtolower($suffix) : '') . ".json";
		$stopFile = $progressJson . '.stop';
		@file_put_contents($stopFile, '1');

		if (file_exists($progressJson)) {
			$pData = json_decode(@file_get_contents($progressJson), true) ?? [];
			$pData['is_running'] = false;
			$pData['status'] = 'stopped';
			$pData['current_table'] = 'Dibatalkan oleh pengguna';
			@file_put_contents($progressJson, json_encode($pData));
		}

		return $this->response->setJSON(['status' => 'success', 'message' => 'Proses rekap excel berhasil dihentikan']);
	}

	/**
	 * Legacy Fallback HTML-Table Export (.xls)
	 */
	public function export_rekap_matching_legacy()
	{
		$ta = $this->request->getGet('ta') ?? $this->session->get('admin_ta') ?? '2026';
		$ta = preg_replace('/[^0-9]/', '', (string)$ta) ?: '2026';
		$uploadPath = WRITEPATH . 'uploads/';
		$matchedJson = $uploadPath . "dda_matched_{$ta}.json";
		$stateJson = $uploadPath . "dda_state_{$ta}.json";

		$masterData = file_exists($matchedJson) ? (json_decode(@file_get_contents($matchedJson), true) ?? []) : [];
		$stateData = file_exists($stateJson) ? (json_decode(@file_get_contents($stateJson), true) ?? []) : [];

		$matchedList = $masterData['matched_in_pdf'] ?? $masterData['matched_tables'] ?? [];
		$unregList = $masterData['unregistered_in_db'] ?? [];

		$filename = "Rekap_Audit_Tabel_DDA_{$ta}_" . date('Ymd_His') . ".xls";
		header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
		header("Content-Disposition: attachment; filename=" . $filename);
		echo "\xEF\xBB\xBF";

		echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head><body>';
		echo "<h3>REKAPITULASI AUDIT & MATCHING TABEL PUBLIKASI DDA TAHUN {$ta}</h3>";
		echo "<p>Waktu Ekspor: " . date('d M Y H:i:s') . "</p>";
		echo '<table border="1" cellpadding="5" cellspacing="0">';
		echo '<thead style="background:#0f172a;color:#ffffff;font-weight:bold;">';
		echo '<tr><th>No</th><th>Nomor Tabel</th><th>Judul Tabel</th><th>Sumber Data</th><th>Halaman PDF</th><th>Status Validasi</th><th>Angka Cocok</th><th>Angka Selisih</th><th>Update Terakhir</th></tr>';
		echo '</thead><tbody>';

		foreach ($matchedList as $idx => $item) {
			$noTabel = $item['nomor_tabel'] ?? '-';
			$judul = htmlspecialchars($item['judul_db'] ?? ($item['judul_pdf'] ?? '-'));
			$src = (($item['source_type'] ?? '') === 'dinamis' || (int)($item['is_dynamic'] ?? 0) === 1) ? 'Tabel Dinamis' : 'API Satu Data';
			$pages = !empty($item['pages']) ? implode(', ', (array)$item['pages']) : '-';
			$stInfo = $stateData[$noTabel] ?? [];
			$stCode = $stInfo['status'] ?? 'unverified';

			if ($stCode === 'match') {
				$stLabel = 'Cocok 100%';
				$bg = '#dcfce7';
			} elseif ($stCode === 'diff') {
				$stLabel = 'Ada Selisih';
				$bg = '#fee2e2';
			} elseif ($stCode === 'api_empty') {
				$stLabel = 'Data Kosong';
				$bg = '#fef9c3';
			} else {
				$stLabel = 'Belum Dicek';
				$bg = '#f8fafc';
			}

			$matchCnt = $stInfo['match_count'] ?? 0;
			$diffCnt = $stInfo['diff_count'] ?? 0;
			$upd = $stInfo['updated_at'] ?? '-';

			echo "<tr style='background:{$bg};'>";
			echo '<td>' . ($idx + 1) . '</td>';
			echo '<td style="mso-number-format:\@;">' . htmlspecialchars((string)$noTabel) . '</td>';
			echo '<td>' . $judul . '</td>';
			echo '<td>' . $src . '</td>';
			echo '<td style="mso-number-format:\@;">' . htmlspecialchars((string)$pages) . '</td>';
			echo '<td><b>' . $stLabel . '</b></td>';
			echo '<td>' . (int)$matchCnt . '</td>';
			echo '<td>' . htmlspecialchars((string)$diffCnt) . '</td>';
			echo '<td>' . htmlspecialchars((string)$upd) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		if (!empty($unregList)) {
			echo "<br><h4>DAFTAR TABEL BELUM DIKONFIGURASI TABEL DINAMIS (" . count($unregList) . " TABEL)</h4>";
			echo '<table border="1" cellpadding="5" cellspacing="0">';
			echo '<thead style="background:#7f1d1d;color:#ffffff;font-weight:bold;">';
			echo '<tr><th>No</th><th>Nomor Tabel</th><th>Judul di PDF</th><th>Halaman PDF</th><th>Keterangan</th></tr>';
			echo '</thead><tbody>';
			foreach ($unregList as $idx => $u) {
				$pages = !empty($u['pages']) ? implode(', ', (array)$u['pages']) : '-';
				echo '<tr>';
				echo '<td>' . ($idx + 1) . '</td>';
				echo '<td style="mso-number-format:\@;">' . htmlspecialchars((string)($u['nomor_tabel'] ?? '-')) . '</td>';
				echo '<td>' . htmlspecialchars((string)($u['judul_pdf'] ?? '-')) . '</td>';
				echo '<td style="mso-number-format:\@;">' . htmlspecialchars((string)$pages) . '</td>';
				echo '<td>' . htmlspecialchars((string)($u['reason'] ?? '-')) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		echo '</body></html>';
		exit;
	}

	/**
	 * Mengambil progres terkini dan update status tabel dari Background Worker.
	 *
	 * Dipanggil secara ringan (polling) oleh browser setiap 1.5 detik untuk memperbarui
	 * progress bar dan badge tabel secara real-time tanpa membebani server maupun klien.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface Data progres (%) dan kumpulan status tabel
	 */
	public function get_batch_progress()
	{
		$ta = $this->request->getGetPost('ta') ?? $this->session->get('admin_ta') ?? date('Y');
		$uploadPath = WRITEPATH . 'uploads/';
		$progressJson = $uploadPath . "dda_progress_{$ta}.json";
		$stateJson = $uploadPath . "dda_state_{$ta}.json";

		$prog = [
			'is_running' => false,
			'completed' => 0,
			'total' => 0,
			'percent' => 0
		];

		if (file_exists($progressJson)) {
			$parsed = null;
			for ($attempt = 0; $attempt < 4; $attempt++) {
				$raw = @file_get_contents($progressJson);
				if (!empty($raw)) {
					$decoded = json_decode($raw, true);
					if (is_array($decoded) && isset($decoded['is_running'])) {
						$parsed = $decoded;
						break;
					}
				}
				usleep(40000); // 40ms backoff
			}

			if ($parsed) {
				$prog = $parsed;
				// Safety timeout: jika tidak ada pembaruan > 180 detik, anggap proses telah selesai
				// (diperbesar karena beberapa tabel butuh waktu lama untuk fetch API eksternal)
				if (!empty($prog['is_running']) && isset($prog['updated_at']) && (time() - $prog['updated_at']) > 180) {
					$prog['is_running'] = false;
				}
			} else {
				// File ada tapi sesaat sedang di-lock/ditulis oleh Python, pertahankan is_running true
				$prog['is_running'] = true;
			}
		}

		$stateData = [];
		if (file_exists($stateJson)) {
			for ($attempt = 0; $attempt < 3; $attempt++) {
				$rawState = @file_get_contents($stateJson);
				if (!empty($rawState)) {
					$decodedState = json_decode($rawState, true);
					if (is_array($decodedState)) {
						$stateData = $decodedState;
						break;
					}
				}
				usleep(40000);
			}
		}

		return $this->response->setJSON([
			'status' => 'success',
			'progress' => $prog,
			'batch_status' => $stateData
		]);
	}

	/**
	 * Menghentikan proses Background Batch Worker secara graceful.
	 *
	 * Membuat file sinyal `.stop` yang akan dideteksi oleh script Python untuk membatalkan
	 * antrean task yang belum dieksekusi dan menutup thread pool dengan aman.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface Konfirmasi penghentian worker
	 */
	public function stop_batch_worker()
	{
		$ta = $this->request->getPost('ta') ?? $this->session->get('admin_ta') ?? date('Y');
		$uploadPath = WRITEPATH . 'uploads/';
		$progressJson = $uploadPath . "dda_progress_{$ta}.json";
		$stopFile = $progressJson . '.stop';

		@file_put_contents($stopFile, 'STOP');

		if (file_exists($progressJson)) {
			$raw = @file_get_contents($progressJson);
			$parsed = json_decode($raw, true);
			if ($parsed) {
				$parsed['is_running'] = false;
				@file_put_contents($progressJson, json_encode($parsed));
			}
		}

		return $this->response->setJSON(['status' => 'success', 'message' => 'Proses batch dihentikan']);
	}

	/**
	 * Mengekspor Laporan Validasi Data Tabel ke file Excel (.xlsx).
	 *
	 * Menggunakan Python script `export_excel.py` dengan format lembar kerja resmi:
	 * memuat ringkasan KPI, tabel perbandingan nilai, selisih data, serta status validasi.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface Download stream berkas Excel
	 */
	public function export_tabel_excel()
	{
		$nomor_tabel = $this->request->getGet("nomor_tabel");
		$pdfPath = $this->request->getGet("pdf_path");
		$dbJsonPath = $this->request->getGet("db_json_path");
		$tolerance = $this->request->getGet("tolerance") ?? "0.0";

		if (empty($nomor_tabel) || empty($pdfPath) || empty($dbJsonPath)) {
			return $this->response->setStatusCode(400)->setBody("Parameter tidak valid");
		}

		$cleanTable = preg_replace('/[^a-zA-Z0-9_\.]/', '_', $nomor_tabel);
		$outputFileName = "Laporan_Validasi_Tabel_" . $cleanTable . "_" . time() . ".xlsx";
		$outputPath = WRITEPATH . "uploads/" . $outputFileName;

		$pythonScript = FCPATH . "../python_engine/export_excel.py";
		$pyBin = $this->getPythonBin();
		$command = "{$pyBin} " . escapeshellarg($pythonScript) . " " . escapeshellarg($pdfPath) . " " . escapeshellarg($dbJsonPath) . " " . escapeshellarg($nomor_tabel) . " " . escapeshellarg($outputPath) . " " . escapeshellarg($tolerance) . " 2>&1";
		$output = shell_exec($command);

		if (file_exists($outputPath)) {
			return $this->response->download($outputPath, null)->setFileName("Laporan_Validasi_Tabel_" . $cleanTable . ".xlsx");
		} else {
			return $this->response->setStatusCode(500)->setBody("Gagal membuat file Excel: " . $output);
		}
	}

	/**
	 * Menyimpan status verifikasi batch tabel ke file state JSON.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface Status penyimpanan
	 */
	public function save_batch_status()
	{
		$pdfPath = $this->request->getPost("pdf_path");
		$batchData = $this->request->getPost("batch_data");

		if (empty($pdfPath) || empty($batchData)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
		}

		$ta = $this->session->get('admin_ta') ?? date('Y');
		if (preg_match('/dda_master_(\d{4})\.pdf/i', $pdfPath, $mYear)) {
			$ta = $mYear[1];
		}
		$statePath = WRITEPATH . "uploads/dda_state_{$ta}.json";
		file_put_contents($statePath, $batchData);

		return $this->response->setJSON(['status' => 'success', 'message' => 'Status batch berhasil disimpan']);
	}

	/**
	 * Mengambil riwayat verifikasi batch dari file state JSON tahun aktif.
	 *
	 * @return \CodeIgniter\HTTP\ResponseInterface JSON array riwayat status tabel
	 */
	public function get_batch_status()
	{
		$ta = $this->request->getGetPost("ta") ?? $this->session->get('admin_ta') ?? date('Y');
		$statePath = WRITEPATH . "uploads/dda_state_{$ta}.json";
		if (file_exists($statePath)) {
			$data = json_decode(@file_get_contents($statePath), true);
			return $this->response->setJSON(['status' => 'success', 'data' => $data]);
		}

		return $this->response->setJSON(['status' => 'empty', 'data' => []]);
	}

	/**
	 * Normalisasi judul dimensi untuk deteksi variasi spasi, strip, garis miring, tanda kurung, dan multi-spasi.
	 * Contoh: "Tahunan 2020 - 2024" == "Tahunan 2020-2024" == "Tahunan  2020   –  2024"
	 */
	private function normalizeDimensiTitle(string $str): string
	{
		$str = mb_strtolower(trim($str), 'UTF-8');
		// Samakan semua jenis tanda hubung (en-dash, em-dash, minus) ke regular hyphen '-'
		$str = str_replace(['–', '—', '−'], '-', $str);
		// Hilangkan spasi di sekitar pemisah seperti '-', '/', '(', ')', '&'
		// Contoh: "2020 - 2024" -> "2020-2024", "Kabupaten / Kota" -> "Kabupaten/Kota"
		$str = preg_replace('/\s*([-\/\(\)&])\s*/', '$1', $str);
		// Rampingkan spasi berlebih menjadi 1 spasi
		$str = preg_replace('/\s+/', ' ', $str);
		return trim($str);
	}

	/**
	 * Pengecekan cerdas duplikasi judul dimensi pada tabel target (Karakteristik, Baris, Periode)
	 */
	private function checkDuplicateDimensi(string $table, string $nama, int $excludeId = 0): ?array
	{
		$nama = trim($nama);
		if ($nama === '') {
			return null;
		}

		$inputNorm  = $this->normalizeDimensiTitle($nama);
		$inputAlnum = preg_replace('/[^a-z0-9]/', '', $inputNorm);

		$builder = $this->db->table($table)->select('id, nama_kelompok_ind');
		if ($excludeId > 0) {
			$builder->where('id !=', $excludeId);
		}
		$rows = $builder->get()->getResult();

		foreach ($rows as $row) {
			$dbName = (string)$row->nama_kelompok_ind;
			$dbNorm = $this->normalizeDimensiTitle($dbName);

			// 1. Cek Exact Normalized (variasi spasi di sekitar strip, garis miring, simbol)
			if ($inputNorm === $dbNorm) {
				return [
					'id'            => (int)$row->id,
					'existing_name' => $dbName
				];
			}

			// 2. Cek Alphanumeric (jika tanda kurung/simbol diabaikan dan menghasilkan karakter sama)
			$dbAlnum = preg_replace('/[^a-z0-9]/', '', $dbNorm);
			if ($inputAlnum !== '' && $inputAlnum === $dbAlnum) {
				return [
					'id'            => (int)$row->id,
					'existing_name' => $dbName
				];
			}
		}

		return null;
	}

	/**
	 * =========================================================================
	 * MODUL TABEL DINAMIS: MASTER DIMENSI (SPRINT 2)
	 * Mengelola Master Karakteristik (Kolom Asimetris), Baris Bertingkat, dan Periode
	 * =========================================================================
	 */
	public function master_dimensi()
	{
		// Cek otentikasi login
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!can('master_dimensi')) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengelola Master Dimensi.</div>');
			return redirect()->to('admin');
		}


		// Hak akses: hanya untuk akun BPS (Super Admin, Admin, LO, Supervisor)
		$unitkerjalogin = $this->session->get('admin_unitkerja');
		if ($unitkerjalogin != 'bps') {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Menu Master Dimensi hanya dapat diakses oleh Admin dan LO BPS.</div>");
			return redirect()->to('admin');
		}

		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$action = $this->uri->segment(3);
		$param1 = $this->uri->segment(4);

		// ---------------------------------------------------------------------
		// AKSI: AJAX CHECK DUPLICATE NAMA KELOMPOK DIMENSI (REAL-TIME NORMALIZED)
		// ---------------------------------------------------------------------
		if ($action === 'ajax_check_duplicate') {
			$type = (string)$this->request->getVar('type'); // 'karakteristik' | 'baris' | 'periode'
			$nama = trim((string)$this->request->getVar('nama'));
			$id   = (int)$this->request->getVar('id');

			$tableMap = [
				'karakteristik' => 'm_karakteristik_kelompok',
				'baris'         => 'm_baris_kelompok',
				'periode'       => 'm_periode_kelompok'
			];

			if (!isset($tableMap[$type]) || $nama === '') {
				return $this->response->setJSON([
					'status' => 'success',
					'exists' => false
				]);
			}

			$duplicate = $this->checkDuplicateDimensi($tableMap[$type], $nama, $id);

			return $this->response->setJSON([
				'status'        => 'success',
				'exists'        => ($duplicate !== null),
				'existing_name' => $duplicate ? $duplicate['existing_name'] : null,
				'nama'          => $nama,
				'type'          => $type
			]);
		}

		// ---------------------------------------------------------------------
		// AKSI: KARAKTERISTIK (KOLOM & SUB-KOLOM ASIMETRIS)
		// ---------------------------------------------------------------------
		if ($action === 'add_karakteristik') {
			$nama_ind   = trim((string)$this->request->getPost('nama_kelompok_ind'));
			$nama_en    = trim((string)$this->request->getPost('nama_kelompok_en'));
			$items_ind  = trim((string)($this->request->getPost('items_text_ind') ?: $this->request->getPost('items_text')));
			$items_en   = trim((string)$this->request->getPost('items_text_en'));

			if (empty($nama_ind)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Nama Kelompok Karakteristik wajib diisi!</div>");
				return redirect()->to('admin/master_dimensi?tab=karakteristik');
			}

			// Cek duplikasi cerdas (variasi spasi, strip, garis miring)
			$duplicate = $this->checkDuplicateDimensi('m_karakteristik_kelompok', $nama_ind);
			if ($duplicate) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Kelompok Karakteristik '<strong>" . esc($nama_ind) . "</strong>' sudah ada / serupa dengan '<strong>" . esc($duplicate['existing_name']) . "</strong>'! Silakan gunakan kelompok yang sudah ada atau gunakan nama lain.</div>");
				return redirect()->to('admin/master_dimensi?tab=karakteristik');
			}

			$idKelompok = $mTabelDinamis->saveKarakteristikKelompok([
				'nama_kelompok_ind' => $nama_ind,
				'nama_kelompok_en'  => !empty($nama_en) ? $nama_en : null,
			]);

			if (!empty($items_ind)) {
				$lines_ind = preg_split('/\r\n|\r|\n/', $items_ind);
				$lines_en  = ($items_en !== '') ? preg_split('/\r\n|\r|\n/', $items_en) : [];
				$items = [];
				$no = 1;

				foreach ($lines_ind as $idx => $l_ind) {
					$l_ind = trim($l_ind);
					if ($l_ind === '') continue;
					$l_en = isset($lines_en[$idx]) ? trim($lines_en[$idx]) : '';
					if (preg_replace('/[\s\|]/', '', $l_en) === '') {
						$l_en = '';
					}
					$items[] = [
						'item_ind' => $l_ind,
						'item_en'  => $l_en,
						'no_urut'  => $no++,
					];
				}
				$mTabelDinamis->syncKarakteristikItems($idKelompok, $items);
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Karakteristik Kolom '<strong>" . esc($nama_ind) . "</strong>' berhasil ditambahkan!</div>");
			return redirect()->to('admin/master_dimensi?tab=karakteristik');
		}

		if ($action === 'edt_karakteristik') {
			$id = (int)$this->request->getPost('id');
			$nama_ind = trim((string)$this->request->getPost('nama_kelompok_ind'));
			$nama_en  = trim((string)$this->request->getPost('nama_kelompok_en'));

			if (empty($id) || empty($nama_ind)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID dan Nama Kelompok wajib diisi!</div>");
				return redirect()->to('admin/master_dimensi?tab=karakteristik');
			}

			// Cek duplikasi cerdas pada ID lain
			$duplicate = $this->checkDuplicateDimensi('m_karakteristik_kelompok', $nama_ind, $id);
			if ($duplicate) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Nama Kelompok '<strong>" . esc($nama_ind) . "</strong>' serupa dengan '<strong>" . esc($duplicate['existing_name']) . "</strong>' yang sudah digunakan kelompok lain!</div>");
				return redirect()->to('admin/master_dimensi?tab=karakteristik');
			}

			$mTabelDinamis->saveKarakteristikKelompok([
				'nama_kelompok_ind' => $nama_ind,
				'nama_kelompok_en'  => !empty($nama_en) ? $nama_en : null,
			], $id);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Karakteristik berhasil diperbarui!</div>");
			return redirect()->to('admin/master_dimensi?tab=karakteristik');
		}

		if ($action === 'del_karakteristik') {
			$id = (int)($param1 ?: $this->request->getPost('id'));
			if ($id > 0) {
				// Cek integritas relasi: apakah dimensi ini sedang digunakan oleh tabel dinamis aktif?
				$usedCount = $this->db->table('m_indikator_tabel')
					->where('id_karakteristik_kelompok', $id)
					->countAllResults();

				if ($usedCount > 0) {
					$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Kelompok Karakteristik ini sedang digunakan oleh <strong>{$usedCount}</strong> tabel dinamis aktif! Silakan ubah konfigurasi tabel terkait terlebih dahulu sebelum menghapus.</div>");
					return redirect()->to('admin/master_dimensi?tab=karakteristik');
				}

				$mTabelDinamis->deleteKarakteristikKelompok($id);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Karakteristik berhasil dihapus!</div>");
			}
			return redirect()->to('admin/master_dimensi?tab=karakteristik');
		}

		if ($action === 'sync_karakteristik_items') {
			$idKelompok = (int)$this->request->getPost('id_kelompok');
			$items_ind  = (string)($this->request->getPost('items_text_ind') ?: $this->request->getPost('items_text'));
			$items_en   = (string)$this->request->getPost('items_text_en');
			$items_json = $this->request->getPost('items_json');

			if (empty($idKelompok)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID Kelompok tidak valid!</div>");
				return redirect()->to('admin/master_dimensi?tab=karakteristik');
			}

			try {
				if (!empty($items_json)) {
					$decoded = json_decode($items_json, true);
					if (is_array($decoded) && !empty($decoded)) {
						$mTabelDinamis->syncKarakteristikFromTreeItems($idKelompok, $decoded);
						$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Item kolom karakteristik berhasil disimpan!</div>");
						return redirect()->to('admin/master_dimensi?tab=karakteristik');
					}
				}

				// Fallback jika hanya input teks biasa
				$lines_ind = preg_split('/\r\n|\r|\n/', $items_ind);
				$lines_en  = ($items_en !== '') ? preg_split('/\r\n|\r|\n/', $items_en) : [];
				$items = [];
				$no = 1;

				foreach ($lines_ind as $idx => $l_ind) {
					$l_ind = trim($l_ind);
					if ($l_ind === '') continue;
					$l_en = isset($lines_en[$idx]) ? trim($lines_en[$idx]) : '';
					if (preg_replace('/[\s\|]/', '', $l_en) === '') {
						$l_en = '';
					}
					$items[] = [
						'item_ind' => $l_ind,
						'item_en'  => $l_en,
						'no_urut'  => $no++,
					];
				}

				$mTabelDinamis->syncKarakteristikItems($idKelompok, $items);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Item kolom karakteristik berhasil disimpan! (" . count($items) . " kolom)</div>");
			} catch (\Throwable $e) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">" . esc($e->getMessage()) . "</div>");
			}
			return redirect()->to('admin/master_dimensi?tab=karakteristik');
		}

		if ($action === 'get_karakteristik_items') {
			$id = (int)$param1;
			$kelompok = $mTabelDinamis->getKarakteristikKelompok($id);
			$items = $mTabelDinamis->getKarakteristikItems($id);
			$structure = $mTabelDinamis->buildColumnStructure($items);
			return $this->response->setJSON([
				'status'    => 'success',
				'kelompok'  => $kelompok,
				'items'     => $items,
				'structure' => $structure
			]);
		}

		// ---------------------------------------------------------------------
		// AKSI: BARIS BERTINGKAT (HIERARCHICAL ROWS)
		// ---------------------------------------------------------------------
		if ($action === 'add_baris') {
			$nama_ind      = trim((string)$this->request->getPost('nama_kelompok_ind'));
			$nama_en       = trim((string)$this->request->getPost('nama_kelompok_en'));
			$judul_ind     = trim((string)$this->request->getPost('judul_baris_ind'));
			$judul_en      = trim((string)$this->request->getPost('judul_baris_en'));
			$judul_sub_ind = trim((string)$this->request->getPost('judul_sub_baris_ind'));
			$judul_sub_en  = trim((string)$this->request->getPost('judul_sub_baris_en'));
			$items_ind     = trim((string)($this->request->getPost('items_text_ind') ?: $this->request->getPost('items_text')));
			$items_en      = trim((string)$this->request->getPost('items_text_en'));

			if (empty($nama_ind)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Nama Kelompok Baris wajib diisi!</div>");
				return redirect()->to('admin/master_dimensi?tab=baris');
			}

			// Cek duplikasi cerdas (variasi spasi, strip, garis miring) pada nama pilihan master
			$duplicate = $this->checkDuplicateDimensi('m_baris_kelompok', $nama_ind);
			if ($duplicate) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Kelompok Baris '<strong>" . esc($nama_ind) . "</strong>' sudah ada / serupa dengan '<strong>" . esc($duplicate['existing_name']) . "</strong>'! Silakan gunakan kelompok yang sudah ada atau gunakan nama lain.</div>");
				return redirect()->to('admin/master_dimensi?tab=baris');
			}

			// Fallback judul header baris jika kosong
			if (empty($judul_ind)) {
				$judul_ind = $nama_ind;
			}
			if (empty($judul_en)) {
				$judul_en = !empty($nama_en) ? $nama_en : null;
			}

			$idKelompok = $mTabelDinamis->saveBarisKelompok([
				'nama_kelompok_ind'   => $nama_ind,
				'nama_kelompok_en'    => !empty($nama_en) ? $nama_en : null,
				'judul_baris_ind'     => $judul_ind,
				'judul_baris_en'      => $judul_en,
				'judul_sub_baris_ind' => !empty($judul_sub_ind) ? $judul_sub_ind : null,
				'judul_sub_baris_en'  => !empty($judul_sub_en) ? $judul_sub_en : null,
			]);

			$items_json = $this->request->getPost('items_json');
			if (!empty($items_json)) {
				$decoded = json_decode($items_json, true);
				if (is_array($decoded) && !empty($decoded)) {
					$mTabelDinamis->syncBarisFromTreeItems($idKelompok, $decoded);
				} elseif (!empty($items_ind)) {
					$mTabelDinamis->parseBarisFromText($idKelompok, $items_ind, $items_en);
				}
			} elseif (!empty($items_ind)) {
				$mTabelDinamis->parseBarisFromText($idKelompok, $items_ind, $items_en);
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Baris '<strong>" . esc($nama_ind) . "</strong>' berhasil ditambahkan!</div>");
			return redirect()->to('admin/master_dimensi?tab=baris');
		}

		if ($action === 'edt_baris') {
			$id            = (int)$this->request->getPost('id');
			$nama_ind      = trim((string)$this->request->getPost('nama_kelompok_ind'));
			$nama_en       = trim((string)$this->request->getPost('nama_kelompok_en'));
			$judul_ind     = trim((string)$this->request->getPost('judul_baris_ind'));
			$judul_en      = trim((string)$this->request->getPost('judul_baris_en'));
			$judul_sub_ind = trim((string)$this->request->getPost('judul_sub_baris_ind'));
			$judul_sub_en  = trim((string)$this->request->getPost('judul_sub_baris_en'));

			if (empty($id) || empty($nama_ind)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID dan Nama Kelompok wajib diisi!</div>");
				return redirect()->to('admin/master_dimensi?tab=baris');
			}

			// Cek duplikasi cerdas pada ID lain (nama pilihan master)
			$duplicate = $this->checkDuplicateDimensi('m_baris_kelompok', $nama_ind, $id);
			if ($duplicate) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Nama Kelompok '<strong>" . esc($nama_ind) . "</strong>' serupa dengan '<strong>" . esc($duplicate['existing_name']) . "</strong>' yang sudah digunakan kelompok lain!</div>");
				return redirect()->to('admin/master_dimensi?tab=baris');
			}

			// Fallback judul header baris jika kosong
			if (empty($judul_ind)) {
				$judul_ind = $nama_ind;
			}
			if (empty($judul_en)) {
				$judul_en = !empty($nama_en) ? $nama_en : null;
			}

			$mTabelDinamis->saveBarisKelompok([
				'nama_kelompok_ind'   => $nama_ind,
				'nama_kelompok_en'    => !empty($nama_en) ? $nama_en : null,
				'judul_baris_ind'     => $judul_ind,
				'judul_baris_en'      => $judul_en,
				'judul_sub_baris_ind' => !empty($judul_sub_ind) ? $judul_sub_ind : null,
				'judul_sub_baris_en'  => !empty($judul_sub_en) ? $judul_sub_en : null,
			], $id);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Baris berhasil diperbarui!</div>");
			return redirect()->to('admin/master_dimensi?tab=baris');
		}

		if ($action === 'del_baris') {
			$id = (int)($param1 ?: $this->request->getPost('id'));
			if ($id > 0) {
				// Cek integritas relasi: apakah dimensi ini sedang digunakan oleh tabel dinamis aktif?
				$usedCount = $this->db->table('m_indikator_tabel')
					->where('id_baris_kelompok', $id)
					->countAllResults();

				if ($usedCount > 0) {
					$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Kelompok Baris ini sedang digunakan oleh <strong>{$usedCount}</strong> tabel dinamis aktif! Silakan ubah konfigurasi tabel terkait terlebih dahulu sebelum menghapus.</div>");
					return redirect()->to('admin/master_dimensi?tab=baris');
				}

				$mTabelDinamis->deleteBarisKelompok($id);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Baris berhasil dihapus!</div>");
			}
			return redirect()->to('admin/master_dimensi?tab=baris');
		}

		if ($action === 'sync_baris_items') {
			$idKelompok = (int)$this->request->getPost('id_kelompok');
			$items_ind  = trim((string)($this->request->getPost('items_text_ind') ?: $this->request->getPost('items_text')));
			$items_en   = trim((string)$this->request->getPost('items_text_en'));
			$items_json = $this->request->getPost('items_json');

			if (empty($idKelompok)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID Kelompok tidak valid!</div>");
				return redirect()->to('admin/master_dimensi?tab=baris');
			}

			try {
				if (!empty($items_json)) {
					$decoded = json_decode($items_json, true);
					if (is_array($decoded) && !empty($decoded)) {
						$mTabelDinamis->syncBarisFromTreeItems($idKelompok, $decoded);
						$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Item baris bertingkat berhasil disimpan!</div>");
						return redirect()->to('admin/master_dimensi?tab=baris');
					}
				}

				$mTabelDinamis->parseBarisFromText($idKelompok, $items_ind, $items_en);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Item baris bertingkat berhasil disimpan!</div>");
			} catch (\Throwable $e) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">" . esc($e->getMessage()) . "</div>");
			}
			return redirect()->to('admin/master_dimensi?tab=baris');
		}

		if ($action === 'get_baris_items') {
			$id = (int)$param1;
			$kelompok = $mTabelDinamis->getBarisKelompok($id);
			$items = $mTabelDinamis->getBarisItems($id);
			$tree = $mTabelDinamis->getBarisTree($id);
			return $this->response->setJSON([
				'status'   => 'success',
				'kelompok' => $kelompok,
				'items'    => $items,
				'tree'     => $tree
			]);
		}

		// ---------------------------------------------------------------------
		// AKSI: PERIODE WAKTU
		// ---------------------------------------------------------------------
		if ($action === 'add_periode') {
			$nama_ind   = trim((string)$this->request->getPost('nama_kelompok_ind'));
			$nama_en    = trim((string)$this->request->getPost('nama_kelompok_en'));
			$items_ind  = trim((string)($this->request->getPost('items_text_ind') ?: $this->request->getPost('items_text')));
			$items_en   = trim((string)$this->request->getPost('items_text_en'));
			$year_start = (int)$this->request->getPost('year_start');
			$year_end   = (int)$this->request->getPost('year_end');
			$year_order = $this->request->getPost('year_order') ?: 'ASC';

			if (empty($nama_ind)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Nama Kelompok Periode wajib diisi!</div>");
				return redirect()->to('admin/master_dimensi?tab=periode');
			}

			// Cek duplikasi cerdas (variasi spasi, strip, garis miring)
			$duplicate = $this->checkDuplicateDimensi('m_periode_kelompok', $nama_ind);
			if ($duplicate) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Kelompok Periode '<strong>" . esc($nama_ind) . "</strong>' sudah ada / serupa dengan '<strong>" . esc($duplicate['existing_name']) . "</strong>'! Silakan gunakan kelompok yang sudah ada atau gunakan nama lain.</div>");
				return redirect()->to('admin/master_dimensi?tab=periode');
			}

			$judul_ind  = trim((string)$this->request->getPost('judul_periode_ind'));
			$judul_en   = trim((string)$this->request->getPost('judul_periode_en'));
			if (empty($judul_ind)) {
				$judul_ind = 'Tahun';
			}
			if (empty($judul_en)) {
				$judul_en = !empty($nama_en) ? $nama_en : 'Year';
			}

			$idKelompok = $mTabelDinamis->savePeriodeKelompok([
				'nama_kelompok_ind' => $nama_ind,
				'nama_kelompok_en'  => !empty($nama_en) ? $nama_en : null,
				'judul_periode_ind' => $judul_ind,
				'judul_periode_en'  => $judul_en,
			]);

			if (!empty($items_ind)) {
				$mTabelDinamis->parsePeriodeFromText($idKelompok, $items_ind, $items_en);
			} elseif (!empty($year_start) && !empty($year_end) && $year_end >= $year_start) {
				$mTabelDinamis->generatePeriodeYearRange($idKelompok, $year_start, $year_end, $year_order);
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Periode '<strong>" . esc($nama_ind) . "</strong>' berhasil ditambahkan!</div>");
			return redirect()->to('admin/master_dimensi?tab=periode');
		}

		if ($action === 'edt_periode') {
			$id = (int)$this->request->getPost('id');
			$nama_ind = trim((string)$this->request->getPost('nama_kelompok_ind'));
			$nama_en  = trim((string)$this->request->getPost('nama_kelompok_en'));

			if (empty($id) || empty($nama_ind)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID dan Nama Kelompok wajib diisi!</div>");
				return redirect()->to('admin/master_dimensi?tab=periode');
			}

			// Cek duplikasi cerdas pada ID lain
			$duplicate = $this->checkDuplicateDimensi('m_periode_kelompok', $nama_ind, $id);
			if ($duplicate) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Nama Kelompok '<strong>" . esc($nama_ind) . "</strong>' serupa dengan '<strong>" . esc($duplicate['existing_name']) . "</strong>' yang sudah digunakan kelompok lain!</div>");
				return redirect()->to('admin/master_dimensi?tab=periode');
			}

			$judul_ind = trim((string)$this->request->getPost('judul_periode_ind'));
			$judul_en  = trim((string)$this->request->getPost('judul_periode_en'));
			if (empty($judul_ind)) {
				$judul_ind = 'Tahun';
			}
			if (empty($judul_en)) {
				$judul_en = 'Year';
			}

			$mTabelDinamis->savePeriodeKelompok([
				'nama_kelompok_ind' => $nama_ind,
				'nama_kelompok_en'  => !empty($nama_en) ? $nama_en : null,
				'judul_periode_ind' => $judul_ind,
				'judul_periode_en'  => $judul_en,
			], $id);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Periode berhasil diperbarui!</div>");
			return redirect()->to('admin/master_dimensi?tab=periode');
		}

		if ($action === 'del_periode') {
			$id = (int)($param1 ?: $this->request->getPost('id'));
			if ($id > 0) {
				$pk = $mTabelDinamis->getPeriodeKelompok($id);
				if ($pk && isset($pk->kategori_periode) && $pk->kategori_periode === 'standar') {
					$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Kelompok Periode Standar BPS tidak dapat dihapus!</div>");
					return redirect()->to('admin/master_dimensi?tab=periode');
				}
				// Cek integritas relasi: apakah dimensi ini sedang digunakan oleh tabel dinamis aktif?
				$usedCount = $this->db->table('m_indikator_tabel')
					->where('id_periode_kelompok', $id)
					->countAllResults();

				if ($usedCount > 0) {
					$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Gagal: Kelompok Periode ini sedang digunakan oleh <strong>{$usedCount}</strong> tabel dinamis aktif! Silakan ubah konfigurasi tabel terkait terlebih dahulu sebelum menghapus.</div>");
					return redirect()->to('admin/master_dimensi?tab=periode');
				}

				$mTabelDinamis->deletePeriodeKelompok($id);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Kelompok Periode berhasil dihapus!</div>");
			}
			return redirect()->to('admin/master_dimensi?tab=periode');
		}

		if ($action === 'generate_periode_years') {
			$idKelompok = (int)$this->request->getPost('id_kelompok');
			$year_start = (int)$this->request->getPost('year_start');
			$year_end   = (int)$this->request->getPost('year_end');
			$year_order = $this->request->getPost('year_order') ?: 'ASC';

			if (empty($idKelompok) || empty($year_start) || empty($year_end)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Rentang tahun tidak valid!</div>");
				return redirect()->to('admin/master_dimensi?tab=periode');
			}

			try {
				$mTabelDinamis->generatePeriodeYearRange($idKelompok, $year_start, $year_end, $year_order);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Rentang tahun {$year_start}-{$year_end} berhasil di-generate!</div>");
			} catch (\Throwable $e) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">" . esc($e->getMessage()) . "</div>");
			}
			return redirect()->to('admin/master_dimensi?tab=periode');
		}

		if ($action === 'sync_periode_items') {
			$idKelompok = (int)$this->request->getPost('id_kelompok');
			$items_ind  = trim((string)($this->request->getPost('items_text_ind') ?: $this->request->getPost('items_text')));
			$items_en   = trim((string)$this->request->getPost('items_text_en'));
			$items_json = $this->request->getPost('items_json');

			if (empty($idKelompok)) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID Kelompok tidak valid!</div>");
				return redirect()->to('admin/master_dimensi?tab=periode');
			}

			try {
				if (!empty($items_json)) {
					$decoded = json_decode($items_json, true);
					if (is_array($decoded) && !empty($decoded)) {
						$mTabelDinamis->syncPeriodeFromTreeItems($idKelompok, $decoded);
						$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Item periode waktu berhasil disimpan!</div>");
						return redirect()->to('admin/master_dimensi?tab=periode');
					}
				}

				$mTabelDinamis->parsePeriodeFromText($idKelompok, $items_ind, $items_en);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Item periode waktu berhasil disimpan!</div>");
			} catch (\Throwable $e) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">" . esc($e->getMessage()) . "</div>");
			}
			return redirect()->to('admin/master_dimensi?tab=periode');
		}

		if ($action === 'get_periode_items') {
			$id = (int)$param1;
			$kelompok = $mTabelDinamis->getPeriodeKelompok($id);
			$items = $mTabelDinamis->getPeriodeItems($id);
			return $this->response->setJSON([
				'status'   => 'success',
				'kelompok' => $kelompok,
				'items'    => $items
			]);
		}

		// ---------------------------------------------------------------------
		// DEFAULT VIEW: LIST MASTER DIMENSI (3 TAB)
		// ---------------------------------------------------------------------
		$tab = $this->request->getGet('tab') ?? 'karakteristik';
		$a['active_tab'] = in_array($tab, ['karakteristik', 'baris', 'periode'], true) ? $tab : 'karakteristik';

		// Data Karakteristik, Baris, dan Periode (sudah menyertakan total_items via LEFT JOIN COUNT)
		$a['list_karakteristik'] = $mTabelDinamis->getKarakteristikKelompokAll();
		$kCounts = $mTabelDinamis->getKarakteristikCounts();
		foreach ($a['list_karakteristik'] as $k) {
			$k->total_items = $kCounts[(int)$k->id] ?? 0;
		}

		// Data Baris (Optimasi Single Aggregation Query)
		$a['list_baris'] = $mTabelDinamis->getBarisKelompokAll();
		$bCounts = $mTabelDinamis->getBarisCounts();
		$bPipeCounts = $mTabelDinamis->getBarisPipeCounts();
		$totalGroupsWithPipe = 0;
		foreach ($a['list_baris'] as $b) {
			$b->total_items = $bCounts[(int)$b->id] ?? 0;
			$b->total_pipe  = $bPipeCounts[(int)$b->id] ?? 0;
			if ($b->total_pipe > 0) {
				$totalGroupsWithPipe++;
			}
		}
		$a['total_baris_with_pipe'] = $totalGroupsWithPipe;

		// Data Periode (Optimasi Single Aggregation Query)
		$a['list_periode'] = $mTabelDinamis->getPeriodeKelompokAll();
		$pCounts = $mTabelDinamis->getPeriodeCounts();
		foreach ($a['list_periode'] as $p) {
			$p->total_items = $pCounts[(int)$p->id] ?? 0;
		}

		$a['page'] = 'l_master_dimensi';
		return view('admin/index', $a);
	}

	// =========================================================================
	// MODUL TABEL DINAMIS: GENERATOR INDIKATOR TABEL (SPRINT 3)
	// Merakit Dimensi (Kolom, Baris, Periode) dengan Tabel DDA & Live Preview
	// =========================================================================

	/**
	 * Halaman Generator Indikator Tabel & Live Matrix Preview
	 */
	public function generator_tabel($id_param = null)
	{
		// Cek otentikasi login
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		// Hak akses: khusus Admin BPS / Super Admin
		if (!$this->isAdminBps()) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Generator Tabel Dinamis hanya dapat diakses oleh Admin BPS.</div>");
			return redirect()->to('admin');
		}

		if (empty($id_param)) {
			return redirect()->to('admin/master_tabel');
		}

		$ta = $this->session->get('admin_ta') ?: date('Y');
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$ctx = $mTabelDinamis->resolveTableContext($id_param, $ta);

		if (!$ctx['tabel']) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-warning\" id=\"alert\">Data tabel tidak ditemukan.</div>");
			return redirect()->to('admin/master_tabel');
		}

		$tabel = $ctx['tabel'];
		$indikator = $ctx['indikator'];
		$tahunTabel = $ctx['tahun_tabel'];
		$id_master = $ctx['id_tabel'];

		// Jika indikator belum ada, buat entri kerangka default di m_indikator_tabel
		if (!$indikator) {
			$now = date('Y-m-d H:i:s');
			$mTabelDinamis->insert([
				'id_tabel'        => $id_master,
				'no_tabel'        => $tabel->no_tabel ?? ($tahunTabel->no_tabel ?? ''),
				'judul_ind'       => $tabel->judul_ind,
				'judul_en'        => $tabel->judul_en,
				'id_unitkerja'    => $tabel->id_unitkerja,
				'tipe_periode'    => 'tahunan',
				'desimal'         => -1,
				'satuan'          => null,
				'config_tampilan' => json_encode([
					'total_horizontal' => false,
					'jenis_total_horizontal' => 'sum',
					'only_total_horizontal' => false,
					'group_total_horizontal' => false,
					'total_vertikal' => true,
					'jenis_total_vertikal' => 'default',
					'label_total_kustom' => '',
					'group_by_region' => true,
					'prioritas_karakteristik' => false,
					'portrait_mode' => false,
					'portrait_split_col' => false
				]),
				'created_at'      => $now,
				'updated_at'      => $now
			]);
			$indikator = $mTabelDinamis->where('id_tabel', $id_master)->first();
		}

		// Properti kompatibilitas untuk view
		$tabelView = (object)[
			'id'              => $id_master,
			'id_master_tabel' => $id_master,
			'id_tahun_tabel'  => $tahunTabel ? $tahunTabel->id : $id_master,
			'tahun'           => $tahunTabel ? $tahunTabel->tahun : $ta,
			'no_tabel'        => $indikator->no_tabel ?? ($tabel->no_tabel ?? ($tahunTabel->no_tabel ?? '')),
			'judul_ind'       => $tabel->judul_ind,
			'judul_en'        => $tabel->judul_en,
			'id_unitkerja'    => $tabel->id_unitkerja,
			'kondef'          => $tabel->kondef ?? '',
			'is_dynamic'      => 1,
			'link_tabel'      => $tahunTabel ? ($tahunTabel->link_tabel ?? '') : '',
			'link_sebelumnya' => $tahunTabel ? ($tahunTabel->link_sebelumnya ?? '') : '',
		];

		// Ambil unitkerja
		$ukRow = $this->db->table('m_unitkerja')->where('id_unitkerja', $tabel->id_unitkerja)->get()->getRow();
		$tabelView->unitkerja_ind = $ukRow->unitkerja_ind ?? '-';
		$tabelView->user_wali = $ukRow->user_wali ?? '-';

		// Hitung total nilai
		$total_nilai = 0;
		if ($indikator) {
			$total_nilai = (int)$this->db->table('t_nilai_dinamis')
				->where('id_indikator', (int)$indikator->id)
				->countAllResults();
		}

		// Ambil tahun yang sudah terisi data
		$available_years = $indikator ? $mTabelDinamis->getAvailableYears((int)$indikator->id) : [];

		// Master dimensi
		$a['tabel']              = $tabelView;
		$a['indikator']          = $indikator;
		$a['total_nilai']        = $total_nilai;
		$a['available_years']    = $available_years;
		$a['list_karakteristik'] = $mTabelDinamis->getKarakteristikKelompokAll();
		$a['list_baris']         = $mTabelDinamis->getBarisKelompokAll();
		$a['list_periode']       = $mTabelDinamis->getPeriodeKelompokAll(); // sudah menyertakan total_items

		$init_max_depth = 1;
		if (!empty($indikator->id_karakteristik_kelompok)) {
			$kItems = $mTabelDinamis->getKarakteristikItems((int)$indikator->id_karakteristik_kelompok);
			$kStruct = $mTabelDinamis->buildColumnStructure($kItems);
			$init_max_depth = $kStruct['max_depth'] ?? 1;
		}
		$a['init_max_depth'] = $init_max_depth;

		$a['page'] = 'f_generator_tabel';
		return view('admin/index', $a);
	}

	/**
	 * AJAX Endpoint: Mengambil struktur dimensi untuk Live Matrix Preview
	 */
	public function ajax_preview_dimensi()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$id_karakteristik     = (int)($this->request->getVar('id_karakteristik') ?? 0);
		$id_baris             = (int)($this->request->getVar('id_baris') ?? 0);
		$id_periode           = (int)($this->request->getVar('id_periode') ?? 0);
		$tipe_periode         = trim((string)($this->request->getVar('tipe_periode') ?? ''));
		$tingkat_kolom        = trim((string)($this->request->getVar('tingkat_kolom') ?? 'semua'));

		$mTabelDinamis = new \App\Models\M_tabeldinamis();

		$structureKolom = null;
		$itemsKolom     = [];
		if ($id_karakteristik > 0) {
			$itemsKolom     = $mTabelDinamis->getKarakteristikItems($id_karakteristik);
			$structureKolom = $mTabelDinamis->buildColumnStructure($itemsKolom);
		}

		$itemsBaris    = [];
		$kelompokBaris = null;
		if ($id_baris > 0) {
			$kelompokBaris = $this->db->table('m_baris_kelompok')->where('id', $id_baris)->get()->getRow();
			$itemsBaris    = $mTabelDinamis->getBarisItems($id_baris);
		}

		$itemsPeriode    = [];
		$kelompokPeriode = null;

		// Default ke tahunan jika tidak ada spesifikasi periode
		if ($id_periode <= 0 && empty($tipe_periode)) {
			$tipe_periode = 'tahunan';
		}

		// Jika ada id_periode spesifik
		if ($id_periode > 0) {
			$kelompokPeriode = $this->db->table('m_periode_kelompok')->where('id', $id_periode)->get()->getRow();
			$itemsPeriode    = $mTabelDinamis->getPeriodeItems($id_periode);
		} elseif (!empty($tipe_periode)) {
			// Jika tipe_periode dipilih dari dropdown baru (tahunan/tahun_ajaran/bulanan/triwulanan/semesteran/custom)
			if ($tipe_periode === 'tahunan') {
				$currentYear = (int)date('Y');
				$itemsPeriode = [];
				for ($y = $currentYear - 4; $y <= $currentYear; $y++) {
					$itemsPeriode[] = (object)[
						'id'               => $y,
						'id_kelompok'      => 0,
						'nama_periode_ind' => (string)$y,
						'nama_periode_en'  => (string)$y,
						'nama_item_id'     => (string)$y,
						'nama_item_en'     => (string)$y,
						'no_urut'          => $y,
						'is_subtotal'      => 0
					];
				}
				$kelompokPeriode = (object)[
					'id'                => 0,
					'nama_kelompok_ind' => 'Tahunan',
					'nama_kelompok_en'  => 'Annual',
					'judul_periode_ind' => 'Tahun',
					'judul_periode_en'  => 'Year'
				];
			} elseif ($tipe_periode === 'tahun_ajaran') {
				$currentYear = (int)date('Y');
				$itemsPeriode = [];
				for ($y = $currentYear - 2; $y <= $currentYear; $y++) {
					$label = $y . '/' . ($y + 1);
					$itemsPeriode[] = (object)[
						'id'               => $y,
						'id_kelompok'      => 0,
						'nama_periode_ind' => $label,
						'nama_periode_en'  => $label,
						'nama_item_id'     => $label,
						'nama_item_en'     => $label,
						'no_urut'          => $y,
						'is_subtotal'      => 0
					];
				}
				$kelompokPeriode = (object)[
					'id'                => 0,
					'nama_kelompok_ind' => 'Tahun Ajaran',
					'nama_kelompok_en'  => 'Academic Year',
					'judul_periode_ind' => 'Tahun Ajaran',
					'judul_periode_en'  => 'Academic Year'
				];
			} elseif ($tipe_periode === 'bulanan' || $tipe_periode === 'triwulanan' || $tipe_periode === 'semesteran') {
				$pGroup = $this->db->table('m_periode_kelompok')->where('kode_standar', $tipe_periode)->get()->getRow();
				if ($pGroup) {
					$kelompokPeriode = $pGroup;
					$itemsPeriode = $mTabelDinamis->getPeriodeItems((int)$pGroup->id);
				}
			} elseif (strpos($tipe_periode, 'custom:') === 0) {
				$customId = (int)substr($tipe_periode, 7);
				if ($customId > 0) {
					$kelompokPeriode = $this->db->table('m_periode_kelompok')->where('id', $customId)->get()->getRow();
					$itemsPeriode = $mTabelDinamis->getPeriodeItems($customId);
				}
			}
		}

		return $this->response->setJSON([
			'status'           => 'success',
			'kolom'            => $structureKolom,
			'kolom_raw'        => $itemsKolom,
			'baris'            => $itemsBaris,
			'baris_kelompok'   => $kelompokBaris,
			'periode'          => $itemsPeriode,
			'periode_kelompok' => $kelompokPeriode,
			'tipe_periode'     => $tipe_periode,
			'tingkat_kolom'    => $tingkat_kolom
		]);
	}

	/**
	 * AJAX Endpoint: Reset/Hapus seluruh nilai matriks dinamis (t_nilai_dinamis)
	 * untuk membuka kunci konfigurasi dimensi tabel
	 */
	public function ajax_reset_nilai_tabel()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		// Hak akses: hanya untuk akun BPS
		$unitkerjalogin = $this->session->get('admin_unitkerja');
		if ($unitkerjalogin != 'bps') {
			return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak! Fitur ini hanya untuk Admin BPS.']);
		}

		$ta = $this->session->get('admin_ta') ?: date('Y');
		$id_param = (int)($this->request->getPost('id_indikator') ?: ($this->request->getPost('id_tabel') ?: $this->request->getPost('id_tahun_tabel')));
		if (empty($id_param)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'ID Tabel tidak valid.']);
		}

		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$indikator = null;
		$id_indikator = (int)$this->request->getPost('id_indikator');
		if ($id_indikator > 0) {
			$indikator = $mTabelDinamis->find($id_indikator);
		}
		if (!$indikator) {
			$ctx = $mTabelDinamis->resolveTableContext($id_param, $ta);
			$indikator = $ctx['indikator'];
		}

		if (!$indikator) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Konfigurasi indikator tabel tidak ditemukan.']);
		}

		$countDeleted = (int)$this->db->table('t_nilai_dinamis')->where('id_indikator', (int)$indikator->id)->countAllResults();
		$this->db->transStart();
		$mTabelDinamis->clearNilai((int)$indikator->id);

		if (!empty($indikator->id_tabel)) {
			$this->db->table('t_status_tahunan')
				->where('id_tabel', $indikator->id_tabel)
				->update([
					'is_periksa'      => 0,
					'catatan_periksa' => '-',
					'is_confirm'      => '0',
					'is_publish'      => 0,
					'published_at'    => null
				]);
		}

		$this->db->transComplete();

		if ($this->db->transStatus() === false) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mereset data nilai. Transaksi dibatalkan (rollback).']);
		}

		return $this->response->setJSON([
			'status'       => 'success',
			'message'      => 'Berhasil menghapus ' . number_format($countDeleted, 0, ',', '.') . ' data nilai sel dari seluruh tahun. Kunci dimensi telah dibuka.',
			'deleted_rows' => $countDeleted
		]);
	}

	/**
	 * AJAX Endpoint: Menghapus nilai t_nilai_dinamis untuk 1 tahun_data tertentu
	 */
	public function ajax_hapus_nilai_tahun()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
		}

		$unitkerjalogin = $this->session->get('admin_unitkerja');
		if ($unitkerjalogin != 'bps') {
			return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak! Fitur ini hanya untuk Admin BPS.']);
		}

		$id_indikator = (int)$this->request->getPost('id_indikator');
		$tahun_data   = (int)$this->request->getPost('tahun_data');

		if (empty($id_indikator) || empty($tahun_data)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak valid.']);
		}

		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$countDeleted = (int)$this->db->table('t_nilai_dinamis')
			->where('id_indikator', $id_indikator)
			->where('tahun_data', $tahun_data)
			->countAllResults();

		$this->db->transStart();
		$mTabelDinamis->deleteNilaiByTahun($id_indikator, $tahun_data);

		// Ambil indikator untuk tahu id_tabel
		$ind = $this->db->table('m_indikator_tabel')->where('id', $id_indikator)->get()->getRow();
		if ($ind && !empty($ind->id_tabel)) {
			$targetDdaYear = (int)$tahun_data + 1;
			$mTabelDinamis->upsertStatusTahunan((int)$ind->id_tabel, $targetDdaYear, [
				'is_periksa'      => 0,
				'catatan_periksa' => 'Data tahun ' . $tahun_data . ' dihapus via Generator Tabel',
				'is_confirm'      => '0',
				'is_publish'      => 0,
				'published_at'    => null,
			]);
		}

		$this->db->transComplete();

		if ($this->db->transStatus() === false) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menghapus data nilai tahun ' . $tahun_data]);
		}

		return $this->response->setJSON([
			'status'       => 'success',
			'message'      => 'Berhasil menghapus ' . number_format($countDeleted, 0, ',', '.') . ' data nilai sel untuk Tahun ' . $tahun_data . '.',
			'deleted_rows' => $countDeleted,
			'tahun_data'   => $tahun_data
		]);
	}

	/**
	 * Simpan / Update Konfigurasi Perakitan Indikator Tabel
	 */
	public function save_generator_tabel()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}
		if (!$this->isAdminBps()) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Generator Tabel Dinamis hanya dapat diakses oleh Admin BPS.</div>");
			return redirect()->to('admin');
		}

		$ta = $this->session->get('admin_ta') ?: date('Y');
		$id_tahun_tabel   = (int)$this->request->getPost('id_tahun_tabel');
		$id_tabel_post    = (int)$this->request->getPost('id_tabel');
		$id_tabel_param   = $id_tabel_post ?: $id_tahun_tabel;

		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$ctx = $mTabelDinamis->resolveTableContext($id_tabel_param, $ta, $id_tabel_post > 0);
		if (!$ctx['tabel']) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Tabel tidak ditemukan!</div>");
			return redirect()->to('admin/master_tabel');
		}

		$id_master = $ctx['id_tabel'];
		$existing = $ctx['indikator'];

		$tipe_periode_raw = trim((string)$this->request->getPost('tipe_periode'));
		$id_periode_kelompok = null;
		$tipe_periode = 'tahunan';

		if ($tipe_periode_raw === 'tahun_ajaran') {
			$tipe_periode = 'tahun_ajaran';
			$id_periode_kelompok = null;
		} elseif ($tipe_periode_raw === 'bulanan') {
			$tipe_periode = 'bulanan';
			$pRow = $this->db->table('m_periode_kelompok')->where('kode_standar', 'bulanan')->get()->getRow();
			$id_periode_kelompok = $pRow ? (int)$pRow->id : null;
		} elseif ($tipe_periode_raw === 'triwulanan') {
			$tipe_periode = 'triwulanan';
			$pRow = $this->db->table('m_periode_kelompok')->where('kode_standar', 'triwulanan')->get()->getRow();
			$id_periode_kelompok = $pRow ? (int)$pRow->id : null;
		} elseif ($tipe_periode_raw === 'semesteran') {
			$tipe_periode = 'semesteran';
			$pRow = $this->db->table('m_periode_kelompok')->where('kode_standar', 'semesteran')->get()->getRow();
			$id_periode_kelompok = $pRow ? (int)$pRow->id : null;
		} elseif (strpos($tipe_periode_raw, 'custom:') === 0) {
			$tipe_periode = 'custom';
			$id_periode_kelompok = (int)substr($tipe_periode_raw, 7);
		} elseif (is_numeric($tipe_periode_raw) && (int)$tipe_periode_raw > 0) {
			$tipe_periode = 'custom';
			$id_periode_kelompok = (int)$tipe_periode_raw;
		} else {
			$tipe_periode = 'tahunan';
			$id_periode_kelompok = null;
		}

		$id_baris = (int)$this->request->getPost('id_baris_kelompok');
		$id_karakteristik = (int)($this->request->getPost('id_karakteristik_kelompok') ?: 0);
		$satuan = trim((string)$this->request->getPost('satuan'));
		$satuan = ($satuan !== '') ? $satuan : null;
		$desimal = (int)$this->request->getPost('desimal');
		if (!in_array($desimal, [-1, 0, 1, 2, 3], true)) {
			$desimal = -1;
		}

		// Tata letak default selalu standard: periode_atas
		$posisi_hirarki = 'periode_atas';

		$tingkat_kolom = trim((string)$this->request->getPost('tingkat_kolom')) ?: 'semua';
		$konfigurasi_total = trim((string)$this->request->getPost('konfigurasi_total'));
		if (!in_array($konfigurasi_total, ['default', 'grand_total', 'jawa_tengah', 'jumlah', 'none', 'kustom', 'runtun_waktu'], true)) {
			$konfigurasi_total = ($existing && !empty($existing->konfigurasi_total)) ? $existing->konfigurasi_total : 'default';
		}
		$label_total_kustom = trim((string)$this->request->getPost('label_total_kustom'));
		if ($konfigurasi_total !== 'kustom' || empty($label_total_kustom)) {
			$label_total_kustom = null;
		}
		$tampilan_baris = trim((string)$this->request->getPost('tampilan_baris'));
		if (!in_array($tampilan_baris, ['1_kolom', '2_kolom'], true)) {
			$tampilan_baris = ($existing && !empty($existing->tampilan_baris)) ? $existing->tampilan_baris : '1_kolom';
		}
		$kolom_nomor = trim((string)$this->request->getPost('kolom_nomor'));
		if (!in_array($kolom_nomor, ['tampilkan', 'sembunyikan'], true)) {
			$kolom_nomor = ($existing && !empty($existing->kolom_nomor)) ? $existing->kolom_nomor : 'sembunyikan';
		}

		// Smart Locking: jika tabel sudah memiliki nilai data
		if ($existing) {
			$total_nilai = (int)$this->db->table('t_nilai_dinamis')
				->where('id_indikator', (int)$existing->id)
				->countAllResults();

			if ($total_nilai > 0) {
				// Dimensi baris dan kolom dikunci
				$id_baris = !empty($existing->id_baris_kelompok) ? (int)$existing->id_baris_kelompok : $id_baris;
				$id_karakteristik = !empty($existing->id_karakteristik_kelompok) ? (int)$existing->id_karakteristik_kelompok : $id_karakteristik;
				// Jika perpindahan antara tahunan <-> tahun_ajaran, diperbolehkan
				$isAnnualExisting = in_array($existing->tipe_periode, ['tahunan', 'tahunan_jumlah', 'tahunan_rata', 'tahun_ajaran'], true);
				$isAnnualNew = in_array($tipe_periode, ['tahunan', 'tahun_ajaran'], true);
				if ($isAnnualExisting && $isAnnualNew) {
					// Diperbolehkan berganti antara tahunan dan tahun ajaran
				} else {
					$tipe_periode = $existing->tipe_periode;
					$id_periode_kelompok = $existing->id_periode_kelompok;
				}
			}
		}

		$now = date('Y-m-d H:i:s');
		$payload = [
			'id_tabel'                  => $id_master,
			'no_tabel'                  => $ctx['tabel']->no_tabel ?? '',
			'judul_ind'                 => $ctx['tabel']->judul_ind,
			'judul_en'                  => $ctx['tabel']->judul_en,
			'id_unitkerja'              => $ctx['tabel']->id_unitkerja,
			'id_karakteristik_kelompok' => !empty($id_karakteristik) ? $id_karakteristik : null,
			'id_baris_kelompok'         => !empty($id_baris) ? $id_baris : null,
			'tipe_periode'              => $tipe_periode,
			'id_periode_kelompok'       => !empty($id_periode_kelompok) ? $id_periode_kelompok : null,
			'posisi_hirarki'            => $posisi_hirarki,
			'tingkat_kolom'             => $tingkat_kolom,
			'konfigurasi_total'         => $konfigurasi_total,
			'label_total_kustom'        => $label_total_kustom,
			'tampilan_baris'            => $tampilan_baris,
			'kolom_nomor'               => $kolom_nomor,
			'satuan'                    => $satuan,
			'desimal'                   => $desimal,
			'updated_at'                => $now,
		];

		if ($existing) {
			$mTabelDinamis->update($existing->id, $payload);
		} else {
			$payload['created_at'] = $now;
			$mTabelDinamis->insert($payload);
		}

		// Set m_list_tabel.jenis_sumber = 'dinamis'
		$this->db->table('m_list_tabel')->where('id', $id_master)->update(['jenis_sumber' => 'dinamis']);

		// Upsert t_status_tahunan untuk tahun aktif saat ini
		$mTabelDinamis->upsertStatusTahunan($id_master, (int)$ta, [
			'is_confirm' => '2',
			'catatan'    => '-',
		]);


		$this->session->setFlashdata("k", "<div class=\"alert alert-success d-flex align-items-center gap-2\" id=\"alert\"><i class=\"bi bi-check-circle-fill fs-5\"></i><div><b>Berhasil!</b> Konfigurasi Tabel Dinamis berhasil dirakit dan disimpan.</div></div>");
		return redirect()->to('admin/generator_tabel/' . $id_master . '?master=1');
	}

	/**
	 * Lepas Status Tabel Dinamis (Kembalikan ke Non-Dinamis)
	 */
	public function unlink_generator_tabel($id_param = null)
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		if (!$this->isAdminBps()) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Akses ditolak! Pelepasan Tabel Dinamis hanya dapat dilakukan oleh Admin BPS.</div>");
			return redirect()->to('admin');
		}

		$ta = $this->session->get('admin_ta') ?: date('Y');
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$ctx = $mTabelDinamis->resolveTableContext($id_param, $ta);
		if (!$ctx['tabel']) {
			return redirect()->to('admin/master_tabel');
		}

		$id_master = $ctx['id_tabel'];

		$this->db->transStart();
		if ($ctx['indikator']) {
			$mTabelDinamis->clearNilai((int)$ctx['indikator']->id);
			$mTabelDinamis->delete($ctx['indikator']->id);
		}

		$this->db->table('m_list_tabel')->where('id', $id_master)->update(['jenis_sumber' => 'api_kominfo']);

		$this->db->table('t_status_tahunan')
			->where('id_tabel', $id_master)
			->update([
				'is_periksa'      => 0,
				'catatan_periksa' => '-',
				'is_confirm'      => '0',
				'is_publish'      => 0,
				'published_at'    => null
			]);
		$this->db->transComplete();

		if ($this->db->transStatus() === false) {
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger d-flex align-items-center gap-2\" id=\"alert\"><i class=\"bi bi-exclamation-triangle-fill fs-5\"></i><div>Gagal mengembalikan tabel. Transaksi dibatalkan (rollback).</div></div>");
			return redirect()->to('admin/generator_tabel/' . $id_master . '?master=1');
		}

		$this->session->setFlashdata("k", "<div class=\"alert alert-info d-flex align-items-center gap-2\" id=\"alert\"><i class=\"bi bi-info-circle-fill fs-5\"></i><div>Tabel berhasil dikembalikan ke format biasa (Non-Dinamis). Seluruh konfigurasi dan sel data matriks telah dibersihkan.</div></div>");
		return redirect()->to('admin/master_tabel');
	}


	// =========================================================================
	// MODUL TABEL DINAMIS: SERVICE GENERATOR TEMPLATE EXCEL (SPRINT 4)
	// =========================================================================

	public function download_template_tabel($id_param = null, $tahun_param = null)
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$id_param = (int)$id_param;
		if (empty($id_param)) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">ID Tabel tidak valid!</div>');
			return redirect()->to('admin/master_tabel');
		}

		$ta = (int)($this->session->get('admin_ta') ?: ($this->session->get('tahun') ?: date('Y')));
		$defaultTahunData = $ta - 1;
		$tahunInput = (int)($tahun_param ?: ($this->request->getGet('tahun_data') ?: ($this->request->getGet('tahun') ?: $defaultTahunData)));

		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$context = $mTabelDinamis->resolveTableContext($id_param, $ta);
		$tabel = $context['tabel'];

		if (!$tabel || !$this->canManageTable($tabel)) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">Akses ditolak! Anda tidak memiliki izin untuk mengunduh template tabel instansi lain.</div>');
			return redirect()->back();
		}

		try {
			$generator = new \App\Libraries\ExcelTemplateGenerator();
			$generator->download($id_param, $tahunInput);
			exit;
		} catch (\Throwable $e) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger d-flex align-items-center gap-2" id="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>Gagal mengunduh template Excel: ' . htmlspecialchars($e->getMessage()) . '</div></div>');
			return redirect()->back();
		}
	}

	/**
	 * Halaman Viewer Tabel Dinamis Publikasi Resmi BPS Jawa Tengah
	 * Multi-tahun dengan Checklist Tahun, Modal Konfigurasi Tabel, & Judul Dinamis
	 * URL: admin/view_tabel_dinamis/{id_param}
	 */
	public function view_tabel_dinamis($id_param = null)
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$id_param = (int)$id_param;
		if (empty($id_param)) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">ID Tabel tidak valid!</div>');
			return redirect()->to('admin/master_tabel');
		}

		$ta = (int)($this->session->get('admin_ta') ?: ($this->session->get('tahun') ?: date('Y')));
		$thDataDefault = (int)$ta - 1;
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$context = $mTabelDinamis->resolveTableContext($id_param, $ta);
		$tabel = $context['tabel'];
		$indikator = $context['indikator'];
		$tahunTabel = $context['tahun_tabel'];
		$id_tabel = $context['id_tabel'];

		if (!$tabel) {
			$this->session->setFlashdata('k', '<div class="alert alert-warning" id="alert">Data tabel tidak ditemukan.</div>');
			return redirect()->to('admin/master_tabel');
		}

		if (!$this->canManageTable($tabel)) {
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">Akses ditolak! Anda tidak memiliki izin untuk melihat tabel instansi lain.</div>');
			return redirect()->to('admin/dda');
		}

		// Lengkapi objek tabel
		$tabel->id_tahun_tabel = (int)$tabel->id;
		$tabel->id_master_tabel = (int)$tabel->id;

		if (empty($tabel->unitkerja_ind) && !empty($tabel->id_unitkerja)) {
			$uk = $this->db->table('m_unitkerja')->where('id_unitkerja', $tabel->id_unitkerja)->get()->getRow();
			$tabel->unitkerja_ind = $uk->unitkerja_ind ?? 'BPS Provinsi Jawa Tengah';
			$tabel->unitkerja_en  = $uk->unitkerja_en ?? 'BPS-Statistics of Jawa Tengah Province';
		} else {
			$tabel->unitkerja_ind = $tabel->unitkerja_ind ?? 'BPS Provinsi Jawa Tengah';
			$tabel->unitkerja_en  = $tabel->unitkerja_en ?? 'BPS-Statistics of Jawa Tengah Province';
		}

		// Resolusi Back URL (Retensi Session agar riwayat halaman asal tidak hilang saat reload halaman / setelah input data)
		$referer = $this->request->getServer('HTTP_REFERER');
		if ($referer && strpos($referer, 'view_tabel_dinamis') === false && strpos($referer, base_url()) !== false) {
			$this->session->set('dda_back_url_' . $id_param, $referer);
		}

		$smartFallback = (!empty($tabel->id_unitkerja))
			? base_url('admin/dda/view_tabel?id=' . $tabel->id_unitkerja)
			: base_url('admin/dda');

		$back_url = $this->session->get('dda_back_url_' . $id_param) ?: $smartFallback;

		$structureKolom  = null;
		$itemsKolom      = [];
		$itemsBaris      = [];
		$kelompokBaris   = null;
		$itemsPeriode    = [];
		$kelompokPeriode = null;
		$nilaiMap        = [];
		$available_years = [];
		$selected_years  = [];
		$configTampilan  = [
			'total_horizontal'        => false,
			'jenis_total_horizontal'  => 'sum',
			'only_total_horizontal'   => false,
			'group_total_horizontal'  => false,
			'total_vertikal'          => true,
			'jenis_total_vertikal'    => 'default',
			'label_total_kustom'      => '',
			'group_by_region'         => true,
			'prioritas_karakteristik' => false,
			'portrait_mode'           => false,
			'portrait_split_col'      => false,
		];

		if ($indikator) {
			// Parse config_tampilan JSON jika ada
			$hasSavedCfgJson = false;
			if (!empty($indikator->config_tampilan)) {
				$decodedCfg = json_decode($indikator->config_tampilan, true);
				if (is_array($decodedCfg)) {
					$configTampilan = array_merge($configTampilan, $decodedCfg);
					$hasSavedCfgJson = true;
				}
			}

			// Sinkronisasi dengan setting kolom jika config_tampilan belum terisi
			if (!$hasSavedCfgJson) {
				if (!empty($indikator->posisi_hirarki)) {
					if ($indikator->posisi_hirarki === 'periode_baris') {
						$configTampilan['portrait_mode'] = true;
						$configTampilan['portrait_split_col'] = false;
					} elseif ($indikator->posisi_hirarki === 'periode_baris_pisah') {
						$configTampilan['portrait_mode'] = true;
						$configTampilan['portrait_split_col'] = true;
					} elseif ($indikator->posisi_hirarki === 'kolom_atas') {
						$configTampilan['prioritas_karakteristik'] = true;
					}
				}
				if (isset($indikator->konfigurasi_total)) {
					if ($indikator->konfigurasi_total === 'none') {
						$configTampilan['total_vertikal'] = false;
					} else {
						$configTampilan['total_vertikal'] = true;
						$configTampilan['jenis_total_vertikal'] = $indikator->konfigurasi_total;
					}
				}
				if (($indikator->tipe_periode ?? '') === 'tahunan_jumlah') {
					$configTampilan['total_horizontal'] = true;
				}
			}
			if (!empty($indikator->label_total_kustom) && empty($configTampilan['label_total_kustom'])) {
				$configTampilan['label_total_kustom'] = $indikator->label_total_kustom;
			}

			// Dukungan Live Preview dari Modal Konfigurasi tanpa simpan permanen
			$previewRaw = $this->request->getGet('preview_config');
			if (!empty($previewRaw)) {
				$prevCfg = json_decode($previewRaw, true);
				if (is_array($prevCfg)) {
					$configTampilan = array_merge($configTampilan, $prevCfg);
					if (isset($prevCfg['satuan'])) $indikator->satuan = $prevCfg['satuan'];
					if (isset($prevCfg['desimal'])) $indikator->desimal = (int)$prevCfg['desimal'];
					if (isset($prevCfg['tampilan_baris'])) $indikator->tampilan_baris = $prevCfg['tampilan_baris'];
					if (isset($prevCfg['kolom_nomor'])) $indikator->kolom_nomor = $prevCfg['kolom_nomor'];
					if (isset($prevCfg['tingkat_kolom'])) $indikator->tingkat_kolom = $prevCfg['tingkat_kolom'];
				}
			}

			if (!empty($indikator->id_karakteristik_kelompok)) {
				$itemsKolom     = $mTabelDinamis->getKarakteristikItems((int)$indikator->id_karakteristik_kelompok);
				$structureKolom = $mTabelDinamis->buildColumnStructure($itemsKolom);
			}

			if (!empty($indikator->id_baris_kelompok)) {
				$kelompokBaris = $mTabelDinamis->getBarisKelompok((int)$indikator->id_baris_kelompok);
				$itemsBaris    = $mTabelDinamis->getBarisItems((int)$indikator->id_baris_kelompok);
			}

			if (!empty($indikator->id_periode_kelompok)) {
				$kelompokPeriode = $mTabelDinamis->getPeriodeKelompok((int)$indikator->id_periode_kelompok);
				$itemsPeriode    = $mTabelDinamis->getPeriodeItems((int)$indikator->id_periode_kelompok);
			}

			// Daftar tahun yang ada datanya di t_nilai_dinamis
			$available_years = $mTabelDinamis->getAvailableYears((int)$indikator->id);

			// Pastikan tahun aktif data ($thDataDefault = $ta - 1) selalu masuk daftar pilihan tahun di dropdown
			$thDataDefault = $ta - 1;
			$allSelectableYears = $available_years;
			if (!in_array($thDataDefault, $allSelectableYears, true)) {
				$allSelectableYears[] = $thDataDefault;
			}

			// Resolusi tahun terpilih:
			// - Jika ada parameter URL (?tahun= / ?years=), gunakan pilihan manual user
			// - Jika default:
			//   * Tabel 1 Tahun (<= 1 tahun): Otomatis menampilkan tahun data aktif ($thDataDefault)
			//   * Tabel Runtun Waktu (> 1 tahun): Pilihan B (Expanding Window: tahun lama + tahun data aktif)
			$reqYears = $this->request->getGet('tahun') ?: $this->request->getGet('years');
			$rawReqList = [];
			if (!empty($reqYears)) {
				$rawReqList = is_array($reqYears) ? $reqYears : explode(',', (string)$reqYears);
			}

			$selected_years = $mTabelDinamis->resolveDisplayYears($indikator->tahun_tampil ?? null, (int)$ta, $rawReqList, $indikator);

			// Pastikan seluruh tahun terpilih masuk dalam daftar pilihan checkbox dropdown
			foreach ($selected_years as $sy) {
				if (!in_array($sy, $allSelectableYears, true)) {
					$allSelectableYears[] = $sy;
				}
			}
			sort($allSelectableYears);
			sort($selected_years);

			// Ambil semua sel nilai dari t_nilai_dinamis untuk indikator ini (Optimasi Index Seek)
			$cleanYears = array_values(array_filter(array_map('intval', $selected_years)));
			$nilaiBuilder = $this->db->table('t_nilai_dinamis')
				->where('id_indikator', (int)$indikator->id);
			if (!empty($cleanYears)) {
				$nilaiBuilder->whereIn('tahun_data', $cleanYears);
			}
			$nilaiRows = $nilaiBuilder->get()->getResult();

			// 4D Map: [$tahun_data][$id_baris_item][$id_karakteristik_item][$id_periode_item]
			foreach ($nilaiRows as $nr) {
				$th = (int)$nr->tahun_data;
				$bId = (int)$nr->id_baris_item;
				$kId = (int)$nr->id_karakteristik_item;
				$pId = (int)($nr->id_periode_item ?? 0);

				$nilaiMap[$th][$bId][$kId][$pId] = [
					'nilai'          => $nr->nilai !== null ? (float)$nr->nilai : null,
					'simbol'         => $nr->simbol ?: null,
					'jumlah_desimal' => $nr->jumlah_desimal !== null ? (int)$nr->jumlah_desimal : null,
				];
			}
		} else {
			$allSelectableYears = [$ta];
			$selected_years = [$ta];
		}

		// Judul Tabel Dinamis Berdasarkan Tahun yang Dipilih
		$tipePeriodeTabel = $indikator->tipe_periode ?? 'tahunan';
		$display_title_ind = function_exists('formatDynamicTableTitle')
			? formatDynamicTableTitle($tabel->judul_ind, $selected_years, 'id', $tipePeriodeTabel)
			: $tabel->judul_ind . ', ' . implode(', ', $selected_years);
		$display_title_en  = function_exists('formatDynamicTableTitle')
			? formatDynamicTableTitle($tabel->judul_en ?? $tabel->judul_ind, $selected_years, 'en', $tipePeriodeTabel)
			: ($tabel->judul_en ?? $tabel->judul_ind) . ', ' . implode(', ', $selected_years);

		// Fallback notice jika tahun data aktif belum punya data
		$fallbackNotice = null;
		if ($indikator && !empty($available_years) && !in_array($thDataDefault, $available_years, true) && in_array(end($available_years), $selected_years, true)) {
			$lastYr = end($available_years);
			$fallbackNotice = "Menampilkan data tahun {$lastYr} (Data {$thDataDefault} belum tersedia)";
		}

		$a['page']                 = 'v_tabel_dinamis';
		$a['page_title']           = 'Pratinjau Tabel Dinamis';
		$a['tabel']                = $tabel;
		$a['indikator']            = $indikator;
		$a['structureKolom']       = $structureKolom;
		$a['itemsKolom']           = $itemsKolom;
		$a['itemsBaris']           = $itemsBaris;
		$a['kelompokBaris']        = $kelompokBaris;
		$a['itemsPeriode']         = $itemsPeriode;
		$a['kelompokPeriode']      = $kelompokPeriode ?? null;
		$a['nilaiMap']             = $nilaiMap;
		$a['available_years']      = $available_years;
		$a['selectable_years']     = $allSelectableYears;
		$a['selected_years']       = $selected_years;
		$a['config_tampilan']      = $configTampilan;
		$a['display_title_ind']    = $display_title_ind;
		$a['display_title_en']     = $display_title_en;
		$a['fallback_notice']      = $fallbackNotice;
		$a['ta']                   = $ta;
		$a['is_admin_bps']         = ($this->session->get('admin_unitkerja') == 'bps' || in_array(strtolower((string)$this->session->get('admin_level')), ['admin', 'super admin', 'superadmin']));
		$a['back_url']             = $back_url;

		return view('admin/index', $a);
	}

	/**
	 * Endpoint Pratinjau Unggahan Excel Tabel Dinamis
	 * Memvalidasi integritas struktur (anchor validation), parsing nilai,
	 * menyimpan ke session cache sementara, dan mengembalikan HTML preview tabel matriks.
	 */
	public function preview_upload_excel_dinamis()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON([
				'status'  => 'error',
				'message' => 'Sesi login telah berakhir. Silakan login kembali.'
			]);
		}

		if (!can('input_dda_input')) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'  => 'error',
				'message' => 'Akses ditolak! Akun Anda (Walidata OPD) tidak berwenang mengunggah berkas Excel. Data OPD terhubung secara otomatis via Portal Satu Data Pemprov Jateng.'
			]);
		}

		$id_tahun_tabel = (int)$this->request->getPost('id_tahun_tabel');
		if (empty($id_tahun_tabel)) {
			return $this->response->setStatusCode(400)->setJSON([
				'status'  => 'error',
				'message' => 'ID Tabel tidak valid.'
			]);
		}

		// Proteksi IDOR
		$tabel = $this->db->table('m_list_tabel')
			->select('id, id_unitkerja, no_tabel, judul_ind, judul_en')
			->where('id', $id_tahun_tabel)
			->get()->getRow();

		if (!$tabel) {
			return $this->response->setStatusCode(404)->setJSON([
				'status'  => 'error',
				'message' => 'Data tabel tidak ditemukan.'
			]);
		}

		if (!$this->canManageTable($tabel)) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'  => 'error',
				'message' => 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.'
			]);
		}

		$file = $this->request->getFile('file_excel');
		if (!$file || !$file->isValid()) {
			return $this->response->setStatusCode(400)->setJSON([
				'status'  => 'error',
				'message' => 'Berkas Excel tidak valid atau belum dipilih.'
			]);
		}

		$ext = strtolower($file->getClientExtension());
		if ($ext !== 'xlsx') {
			return $this->response->setStatusCode(400)->setJSON([
				'status'  => 'error',
				'message' => 'Format berkas tidak valid! Harap unggah berkas berekstensi .xlsx.'
			]);
		}

		try {
			$ta = (int)($this->session->get('admin_ta') ?: ($this->session->get('tahun') ?: date('Y')));
			$defaultTahunData = $ta - 1;
			$tahun_data_post = (int)$this->request->getPost('tahun_data') ?: $defaultTahunData;

			$parser = new \App\Libraries\ExcelDataParser();
			$parseResult = $parser->parse($file->getTempName(), $id_tahun_tabel, $tahun_data_post);

			// Tolak jika berkas 100% kosong (0 sel terisi)
			if ((int)($parseResult['filled_cells'] ?? 0) === 0) {
				return $this->response->setStatusCode(422)->setJSON([
					'status'  => 'error',
					'message' => 'Berkas Excel yang diunggah masih kosong (0 sel terisi). Harap lengkapi nilai atau simbol statistik BPS sebelum mengunggah.'
				]);
			}

			// Simpan hasil parse ke session cache sementara
			$uploadToken = bin2hex(random_bytes(16));
			$this->session->set('upload_preview_' . $uploadToken, [
				'id_tahun_tabel' => (int)$tabel->id,
				'id_indikator'   => (int)$parseResult['id_indikator'],
				'tahun_data'     => (int)$parseResult['tahun_data'],
				'total_cells'    => (int)$parseResult['total_cells'],
				'filled_cells'   => (int)$parseResult['filled_cells'],
				'empty_cells'    => (int)$parseResult['empty_cells'],
				'symbol_cells'   => (int)($parseResult['symbol_cells'] ?? 0),
				'cells_data'     => $parseResult['cells_data'],
				'file_name'      => $file->getClientName(),
				'file_size'      => $file->getSize(),
				'created_at'     => time(),
			]);

			// Siapkan data matriks untuk rendering pratinjau
			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$indikator = $mTabelDinamis->getIndikator((int)$parseResult['id_indikator']);
			$id_baris = (int)($indikator->id_baris_kelompok ?? 0);
			$itemsBaris = $id_baris > 0 ? $mTabelDinamis->getBarisItems($id_baris) : [];
			$kelompokBaris = $id_baris > 0 ? $mTabelDinamis->getBarisKelompok($id_baris) : null;
			$id_karakteristik = (int)($indikator->id_karakteristik_kelompok ?? 0);
			$itemsKolom = $id_karakteristik > 0 ? $mTabelDinamis->getKarakteristikItems($id_karakteristik) : [];
			$structureKolom = !empty($itemsKolom) ? $mTabelDinamis->buildColumnStructure($itemsKolom) : [];
			$id_periode = (int)($indikator->id_periode_kelompok ?? 0);
			$itemsPeriode = $id_periode > 0 ? $mTabelDinamis->getPeriodeItems($id_periode) : [];
			$kelompokPeriode = $id_periode > 0 ? $mTabelDinamis->getPeriodeKelompok($id_periode) : null;
			$configTampilan = !empty($indikator->config_tampilan) ? json_decode($indikator->config_tampilan, true) : [];

			// Ambil data eksisting dari database untuk tahun_data ini (jika ada)
			$targetTahun  = (int)$parseResult['tahun_data'];
			$existingRows = $this->db->table('t_nilai_dinamis')
				->select('id_baris_item, id_karakteristik_item, id_periode_item, nilai, simbol, jumlah_desimal')
				->where('id_indikator', (int)$parseResult['id_indikator'])
				->where('tahun_data', $targetTahun)
				->get()->getResult();

			$existingMap = [];
			$existingFilledCount = 0;
			foreach ($existingRows as $er) {
				$ebId = (int)$er->id_baris_item;
				$ekId = (int)$er->id_karakteristik_item;
				$epId = (int)($er->id_periode_item ?? 0);
				$isErFilled = ($er->nilai !== null && $er->nilai !== '') || !empty($er->simbol);
				if ($isErFilled) {
					$existingFilledCount++;
				}
				$existingMap[$ebId][$ekId][$epId] = [
					'nilai'          => $er->nilai !== null ? (float)$er->nilai : null,
					'simbol'         => $er->simbol ?: null,
					'jumlah_desimal' => $er->jumlah_desimal !== null ? (int)$er->jumlah_desimal : null,
					'is_filled'      => $isErFilled,
				];
			}

			$hasExistingData = ($existingFilledCount > 0);
			$modifiedCount   = 0;
			$sameCount       = 0;
			$newCount        = 0;

			// Bentuk 4D map untuk tabel matriks dengan deteksi visual diffing
			$previewNilaiMap = [];
			foreach ($parseResult['cells_data'] as $c) {
				$th  = (int)$c['tahun_data'];
				$bId = (int)$c['id_baris_item'];
				$kId = (int)$c['id_karakteristik_item'];
				$pId = (int)($c['id_periode_item'] ?? 0);

				$newVal  = $c['nilai'] !== null ? (float)$c['nilai'] : null;
				$newSimb = !empty($c['simbol']) ? trim((string)$c['simbol']) : null;
				$newDec  = $c['jumlah_desimal'] !== null ? (int)$c['jumlah_desimal'] : null;
				$isNewFilled = ($newVal !== null) || (!empty($newSimb));

				$statusDiff = 'empty';
				$oldVal     = null;
				$oldSimb    = null;

				if ($hasExistingData) {
					$oldCell = $existingMap[$bId][$kId][$pId] ?? null;
					$isOldFilled = !empty($oldCell['is_filled']);
					$oldVal      = $oldCell['nilai'] ?? null;
					$oldSimb     = $oldCell['simbol'] ?? null;

					if ($isNewFilled && $isOldFilled) {
						$isValSame = true;
						if ($newVal !== null && $oldVal !== null) {
							$isValSame = (abs($newVal - $oldVal) < 0.00001);
						} elseif ($newVal !== null || $oldVal !== null) {
							$isValSame = false;
						}

						$isSimbSame = ($newSimb === $oldSimb);

						if ($isValSame && $isSimbSame) {
							$statusDiff = 'same';
							$sameCount++;
						} else {
							$statusDiff = 'modified';
							$modifiedCount++;
						}
					} elseif ($isNewFilled && !$isOldFilled) {
						$statusDiff = 'new';
						$newCount++;
					} elseif (!$isNewFilled && $isOldFilled) {
						$statusDiff = 'removed';
						$modifiedCount++;
					} else {
						$statusDiff = 'empty';
					}
				} else {
					$statusDiff = $isNewFilled ? 'new' : 'empty';
				}

				$previewNilaiMap[$th][$bId][$kId][$pId] = [
					'nilai'          => $newVal,
					'simbol'         => $newSimb,
					'jumlah_desimal' => $newDec,
					'status_diff'    => $statusDiff,
					'old_nilai'      => $oldVal,
					'old_simbol'     => $oldSimb,
				];
			}

			$summaryData = [
				'total_cells'        => (int)$parseResult['total_cells'],
				'filled_cells'       => (int)$parseResult['filled_cells'],
				'empty_cells'        => (int)$parseResult['empty_cells'],
				'has_existing_data'  => $hasExistingData,
				'modified_cells'     => $modifiedCount,
				'same_cells'         => $sameCount,
				'new_cells'          => $newCount,
			];

			$htmlPreview = view('admin/parts/preview_upload_dinamis', [
				'tabel'           => $tabel,
				'indikator'       => $indikator,
				'tahun_data'      => $targetTahun,
				'file_name'       => $file->getClientName(),
				'file_size'       => $file->getSize(),
				'summary'         => $summaryData,
				'hasExistingData' => $hasExistingData,
				'upload_token'    => $uploadToken,
				'structureKolom'  => $structureKolom,
				'itemsKolom'      => $itemsKolom,
				'itemsBaris'      => $itemsBaris,
				'kelompokBaris'   => $kelompokBaris,
				'itemsPeriode'    => $itemsPeriode,
				'kelompokPeriode' => $kelompokPeriode,
				'nilaiMap'        => $previewNilaiMap,
				'config_tampilan' => $configTampilan,
			]);

			return $this->response->setJSON([
				'status'       => 'success',
				'upload_token' => $uploadToken,
				'summary'      => $summaryData,
				'html_preview' => $htmlPreview,
				'csrf_token'   => csrf_token(),
				'csrf_hash'    => csrf_hash(),
			]);
		} catch (\Throwable $e) {
			log_message('error', 'Error preview_upload_excel_dinamis: ' . $e->getMessage());
			return $this->response->setStatusCode(422)->setJSON([
				'status'     => 'error',
				'message'    => $e->getMessage(),
				'csrf_token' => csrf_token(),
				'csrf_hash'  => csrf_hash(),
			]);
		}
	}

	/**
	 * Endpoint Konfirmasi Impor Data Excel ke Database
	 * Mengambil data dari session staging token, melakukan transaksi batch upsert ke t_nilai_dinamis,
	 * memperbarui t_status_tahunan, dan menyinkronkan snapshot cache JSON DDA.
	 */
	public function confirm_upload_excel_dinamis()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON([
				'status'  => 'error',
				'message' => 'Sesi login telah berakhir. Silakan login kembali.'
			]);
		}

		if (!can('input_dda_input')) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'  => 'error',
				'message' => 'Akses ditolak! Akun Anda (Walidata OPD) tidak berwenang menyimpan data Excel. Data OPD terhubung secara otomatis via Portal Satu Data Pemprov Jateng.'
			]);
		}

		$uploadToken    = trim((string)$this->request->getPost('upload_token'));
		$id_tahun_tabel = (int)$this->request->getPost('id_tahun_tabel');

		if (empty($uploadToken)) {
			return $this->response->setStatusCode(400)->setJSON([
				'status'  => 'error',
				'message' => 'Token unggahan tidak valid atau telah kadaluarsa.'
			]);
		}

		$sessionKey = 'upload_preview_' . $uploadToken;
		$stagedData = $this->session->get($sessionKey);

		if (empty($stagedData) || !is_array($stagedData)) {
			return $this->response->setStatusCode(410)->setJSON([
				'status'  => 'error',
				'message' => 'Sesi pratinjau telah berakhir atau berkas telah kedaluwarsa. Silakan pilih dan unggah kembali berkas Anda.'
			]);
		}

		// Validasi IDOR
		$tabel = $this->db->table('m_list_tabel')
			->select('id, id_unitkerja, no_tabel')
			->where('id', (int)$stagedData['id_tahun_tabel'])
			->get()->getRow();

		if (!$tabel) {
			return $this->response->setStatusCode(404)->setJSON([
				'status'  => 'error',
				'message' => 'Data tabel tidak ditemukan.'
			]);
		}

		if (!$this->canManageTable($tabel)) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'     => 'error',
				'message'    => 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.',
				'csrf_token' => csrf_token(),
				'csrf_hash'  => csrf_hash(),
			]);
		}

		$filledCount = (int)($stagedData['filled_cells'] ?? 0);
		if ($filledCount === 0) {
			return $this->response->setStatusCode(422)->setJSON([
				'status'     => 'error',
				'message'    => 'Tidak ada data yang dapat disimpan karena seluruh sel data dalam berkas Excel masih kosong (0 sel terisi). Silakan isi angka pada kolom data berkas template terlebih dahulu.',
				'csrf_token' => csrf_token(),
				'csrf_hash'  => csrf_hash(),
			]);
		}

		try {
			$this->db->transStart();

			$mTabelDinamis   = new \App\Models\M_tabeldinamis();
			$tahunDataTarget = (int)$stagedData['tahun_data'];
			$cellsData       = $stagedData['cells_data'] ?? [];

			$affected = $mTabelDinamis->saveNilaiBatch((int)$stagedData['id_indikator'], $cellsData, $tahunDataTarget);

			// Update status pengisian di t_status_tahunan
			$ta           = (int)($this->session->get('admin_ta') ?: ($this->session->get('tahun') ?: date('Y')));
			$uploaderName = $this->session->get('admin_nama') ?: ($this->session->get('admin_user') ?: 'Walidata');
			$catatan      = "Diunggah oleh {$uploaderName} pada " . date('d/m/Y H:i');

			$tahunTargetDda = !empty($tahunDataTarget) ? ($tahunDataTarget + 1) : (int)$ta;
			$mTabelDinamis->upsertStatusTahunan((int)$tabel->id, (int)$tahunTargetDda, [
				'is_periksa'      => 1,
				'is_confirm'      => '2',
				'catatan_periksa' => $catatan
			]);

			$this->db->transComplete();

			if ($this->db->transStatus() === false) {
				throw new \Exception("Gagal menyimpan data ke database. Seluruh transaksi dibatalkan (rollback).");
			}

			// Hapus session staging token setelah berhasil commit
			$this->session->remove($sessionKey);

			// Sinkronisasi Cache Snapshot DDA (Matching PDF & Live Validation)
			try {
				$tahunTabel = (string)$ta;
				$cleanNo    = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $tabel->no_tabel ?? ''));
				$cleanNo    = rtrim($cleanNo, '.');

				$dbJsonPath = WRITEPATH . "uploads/dda_db_{$tahunTabel}.json";
				if (file_exists($dbJsonPath)) {
					$dynInfoAfter = $mTabelDinamis->exportSingleDynamicTable((int)$tabel->id);
					$dbList       = json_decode(@file_get_contents($dbJsonPath), true) ?? [];
					foreach ($dbList as &$dbItem) {
						if ((int)($dbItem['id_db'] ?? 0) === (int)$tabel->id || trim($dbItem['nomor_tabel'] ?? '') === $cleanNo) {
							$dbItem['dynamic_empty'] = false;
							if (!empty($dynInfoAfter['dynamic_grid'])) {
								$dbItem['dynamic_grid'] = $dynInfoAfter['dynamic_grid'];
							}
							$dbItem['cell_count'] = $dynInfoAfter['cell_count'] ?? $filledCount;
							break;
						}
					}
					unset($dbItem);
					@file_put_contents($dbJsonPath, json_encode($dbList, JSON_UNESCAPED_UNICODE));
				}
			} catch (\Throwable $eSnap) {
				log_message('error', 'Gagal update snapshot JSON: ' . $eSnap->getMessage());
			}

			return $this->response->setJSON([
				'status'       => 'success',
				'message'      => 'Data pengisian tabel dinamis berhasil diverifikasi dan disimpan ke database.',
				'filled_cells' => $filledCount,
				'csrf_token'   => csrf_token(),
				'csrf_hash'    => csrf_hash(),
			]);
		} catch (\Throwable $e) {
			$this->db->transRollback();
			log_message('error', 'Error confirm_upload_excel_dinamis: ' . $e->getMessage());
			return $this->response->setStatusCode(500)->setJSON([
				'status'     => 'error',
				'message'    => 'Terjadi kesalahan sistem saat menyimpan data: ' . $e->getMessage(),
				'csrf_token' => csrf_token(),
				'csrf_hash'  => csrf_hash(),
			]);
		}
	}
	public function upload_excel_dinamis()
	{
		// Cek otentikasi login
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			if ($this->request->isAJAX()) {
				return $this->response->setStatusCode(401)->setJSON([
					'status'  => 'error',
					'message' => 'Sesi login telah berakhir. Silakan login kembali.'
				]);
			}
			return redirect()->to("admin/login");
		}

		if (!can('input_dda_input')) {
			if ($this->request->isAJAX()) {
				return $this->response->setStatusCode(403)->setJSON([
					'status'  => 'error',
					'message' => 'Akses ditolak: Walidata OPD tidak berwenang mengunggah berkas Excel.'
				]);
			}
			$this->session->setFlashdata('k', '<div class="alert alert-danger" id="alert">Akses ditolak: Walidata OPD tidak berwenang mengunggah berkas Excel.</div>');
			return redirect()->back();
		}

		$id_tahun_tabel = (int)$this->request->getPost('id_tahun_tabel');
		if (empty($id_tahun_tabel)) {
			$errMsg = 'ID Tabel tidak valid.';
			if ($this->request->isAJAX()) {
				return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $errMsg]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">{$errMsg}</div>");
			return redirect()->back();
		}

		// Proteksi IDOR: Validasi keberadaan tabel & otorisasi kepemilikan OPD
		$tabel = $this->db->table('m_list_tabel')
			->select('id, id_unitkerja, no_tabel')
			->where('id', $id_tahun_tabel)
			->get()->getRow();

		if (!$tabel) {
			$errMsg = 'Data tabel tidak ditemukan.';
			if ($this->request->isAJAX()) {
				return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => $errMsg]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">{$errMsg}</div>");
			return redirect()->back();
		}

		if (!$this->canManageTable($tabel)) {
			$errMsg = 'Akses ditolak! Anda tidak memiliki izin untuk mengunggah data pada tabel milik OPD/instansi lain.';
			if ($this->request->isAJAX()) {
				return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => $errMsg]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">{$errMsg}</div>");
			return redirect()->back();
		}

		$file = $this->request->getFile('file_excel');
		if (!$file || !$file->isValid()) {
			$errMsg = 'Berkas Excel tidak valid atau belum dipilih.';
			if ($this->request->isAJAX()) {
				return $this->response->setJSON(['status' => 'error', 'message' => $errMsg]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">{$errMsg}</div>");
			return redirect()->back();
		}

		$ext = strtolower($file->getClientExtension());
		if ($ext !== 'xlsx') {
			$errMsg = 'Format berkas tidak valid! Harap unggah berkas berekstensi .xlsx.';
			if ($this->request->isAJAX()) {
				return $this->response->setJSON(['status' => 'error', 'message' => $errMsg]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">{$errMsg}</div>");
			return redirect()->back();
		}

		try {
			$this->db->transStart();

			$ta = (int)($this->session->get('admin_ta') ?: ($this->session->get('tahun') ?: date('Y')));
			$defaultTahunData = $ta - 1;
			$tahun_data_post = (int)$this->request->getPost('tahun_data') ?: $defaultTahunData;
			$parser = new \App\Libraries\ExcelDataParser();
			$parseResult = $parser->parse($file->getTempName(), $id_tahun_tabel, $tahun_data_post);

			// Validasi Opsi A: Tolak jika berkas template 100% kosong (0 sel terisi)
			if ((int)($parseResult['filled_cells'] ?? 0) === 0) {
				$this->db->transRollback();
				$errMsg = 'Berkas Excel yang diunggah masih kosong (0 sel terisi). Harap lengkapi nilai atau simbol statistik BPS (misal: "-" atau "...") sebelum mengunggah.';
				if ($this->request->isAJAX()) {
					return $this->response->setStatusCode(422)->setJSON([
						'status'  => 'error',
						'message' => $errMsg
					]);
				}
				$this->session->setFlashdata("k", "<div class=\"alert alert-warning d-flex align-items-center gap-2\" id=\"alert\"><i class=\"bi bi-exclamation-triangle-fill fs-5\"></i><div>{$errMsg}</div></div>");
				return redirect()->back();
			}

			$mTabelDinamis = new \App\Models\M_tabeldinamis();
			$tahunDataTarget = (int)($parseResult['tahun_data'] ?? $defaultTahunData);
			$affected = $mTabelDinamis->saveNilaiBatch((int)$parseResult['id_indikator'], $parseResult['cells_data'], $tahunDataTarget);

			// Update status pengisian di t_status_tahunan
			$uploaderName = $this->session->get('admin_nama') ?: ($this->session->get('admin_user') ?: 'Walidata');
			$catatan = "Diunggah oleh {$uploaderName} pada " . date('d/m/Y H:i');

			$tahunTargetDda = !empty($tahunDataTarget) ? ($tahunDataTarget + 1) : (int)$ta;
			$mTabelDinamis->upsertStatusTahunan((int)$tabel->id, (int)$tahunTargetDda, [
				'is_periksa'      => 1,
				'is_confirm'      => '2',
				'catatan_periksa' => $catatan
			]);

			$this->db->transComplete();

			if ($this->db->transStatus() === false) {
				throw new \Exception("Gagal menyimpan data ke database. Seluruh transaksi dibatalkan (rollback).");
			}

			// Sinkronisasi Cache Snapshot DDA (Matching PDF & Live Validation)
			try {
				$tahunTabel = (string)$ta;
				$cleanNo = trim(preg_replace('/^(?:tabel|table)\s*/i', '', $tabel->no_tabel ?? ''));
				$cleanNo = rtrim($cleanNo, '.');

				$dbJsonPath = WRITEPATH . "uploads/dda_db_{$tahunTabel}.json";
				if (file_exists($dbJsonPath)) {
					$dynInfoAfter = $mTabelDinamis->exportSingleDynamicTable((int)$tabel->id);
					$dbList = json_decode(@file_get_contents($dbJsonPath), true) ?? [];
					foreach ($dbList as &$dbItem) {
						if ((int)($dbItem['id_db'] ?? 0) === (int)$tabel->id || trim($dbItem['nomor_tabel'] ?? '') === $cleanNo) {
							$dbItem['dynamic_empty'] = false;
							if (!empty($dynInfoAfter['dynamic_grid'])) {
								$dbItem['dynamic_grid'] = $dynInfoAfter['dynamic_grid'];
							}
							$dbItem['cell_count'] = $dynInfoAfter['cell_count'] ?? (int)$parseResult['filled_cells'];
							break;
						}
					}
					unset($dbItem);
					@file_put_contents($dbJsonPath, json_encode($dbList, JSON_UNESCAPED_UNICODE));
				}

				// Perbarui status 'api_empty' di dda_state_{ta}.json agar tidak tertinggal sebagai Data Kosong
				$statePath = WRITEPATH . "uploads/dda_state_{$tahunTabel}.json";
				if (file_exists($statePath) && !empty($cleanNo)) {
					$stateData = json_decode(@file_get_contents($statePath), true) ?? [];
					if (isset($stateData[$cleanNo]) && ($stateData[$cleanNo]['status'] ?? '') === 'api_empty') {
						unset($stateData[$cleanNo]);
						@file_put_contents($statePath, json_encode($stateData, JSON_PRETTY_PRINT));
					}
				}
			} catch (\Throwable $eSync) {
				log_message('error', 'Gagal sinkronisasi cache dda_db setelah upload excel: ' . $eSync->getMessage());
			}

			$successMsg = "Data tabel dinamis berhasil diimpor! Total <b>{$parseResult['filled_cells']}</b> sel data berhasil disimpan.";
			$this->session->setFlashdata("k", "<div class=\"alert alert-success d-flex align-items-center gap-2\" id=\"alert\"><i class=\"bi bi-check-circle-fill fs-5\"></i><div>{$successMsg}</div></div>");

			if ($this->request->isAJAX()) {
				return $this->response->setJSON([
					'status'       => 'success',
					'message'      => $successMsg,
					'total_cells'  => $parseResult['total_cells'],
					'filled_cells' => $parseResult['filled_cells'],
					'empty_cells'  => $parseResult['empty_cells'],
					'symbol_cells' => $parseResult['symbol_cells']
				]);
			}

			return redirect()->back();
		} catch (\Throwable $e) {
			$this->db->transRollback();
			$errMsg = $e->getMessage();
			if ($this->request->isAJAX()) {
				return $this->response->setStatusCode(422)->setJSON([
					'status'  => 'error',
					'message' => $errMsg
				]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-danger d-flex align-items-center gap-2\" id=\"alert\"><i class=\"bi bi-exclamation-triangle-fill fs-5\"></i><div>Gagal mengunggah Excel: " . htmlspecialchars($errMsg) . "</div></div>");
			return redirect()->back();
		}
	}

	/**
	 * Endpoint AJAX untuk Admin/LO menyimpan catatan bagi Walidata
	 */
	public function simpan_catatan_tabel()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Sesi login telah berakhir']);
		}

		$id = (int)$this->request->getPost('id');
		$catatan = trim((string)$this->request->getPost('catatan'));

		if (empty($id)) {
			return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'ID tabel tidak valid']);
		}

		// Proteksi IDOR: Cek keberadaan tabel & otorisasi kepemilikan OPD
		$ta = (int)($this->session->get('admin_ta') ?: date('Y'));
		$tabel = $this->db->table('m_list_tabel')
			->select('id, id_unitkerja')
			->where('id', $id)
			->get()->getRow();

		if (!$tabel) {
			return $this->response->setStatusCode(404)->setJSON([
				'status'  => 'error',
				'message' => 'Data tabel tidak ditemukan'
			]);
		}

		if (!$this->canManageTable($tabel)) {
			return $this->response->setStatusCode(403)->setJSON([
				'status'  => 'error',
				'message' => 'Akses ditolak! Anda tidak memiliki izin untuk memperbarui catatan tabel instansi lain.'
			]);
		}

		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$mTabelDinamis->upsertStatusTahunan((int)$id, $ta, ['catatan' => $catatan]);

		return $this->response->setJSON([
			'status'  => 'success',
			'message' => 'Catatan berhasil disimpan!',
			'catatan' => $catatan
		]);
	}

	/**
	 * Endpoint AJAX: Simpan Konfigurasi Tampilan Tabel Dinamis
	 * Menyimpan ke m_indikator_tabel.config_tampilan serta sinkronisasi posisi_hirarki & konfigurasi_total
	 */
	public function ajax_save_konfigurasi_tampilan_dinamis()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Sesi berakhir']);
		}

		$id_indikator = (int)$this->request->getPost('id_indikator');
		if (!$id_indikator) {
			$id_tabel = (int)$this->request->getPost('id_tabel');
			if ($id_tabel > 0) {
				$ind = $this->db->table('m_indikator_tabel')->where('id_tabel', $id_tabel)->get()->getRow();
				if ($ind) $id_indikator = (int)$ind->id;
			}
		}

		if (!$id_indikator) {
			return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'ID Indikator tidak valid']);
		}

		$indRow = $this->db->table('m_indikator_tabel')->where('id', $id_indikator)->get()->getRow();
		if (!$indRow) {
			return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Indikator tabel tidak ditemukan']);
		}

		if (!empty($indRow->id_tabel)) {
			$tabelRow = $this->db->table('m_list_tabel')->where('id', (int)$indRow->id_tabel)->get()->getRow();
			if ($tabelRow && !$this->canManageTable($tabelRow)) {
				return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak! Anda tidak berwenang mengubah konfigurasi tabel instansi lain.']);
			}
		}

		$configRaw = $this->request->getPost('config_json');
		$cfg = [];
		if (!empty($configRaw)) {
			$cfg = json_decode($configRaw, true) ?: [];
		}

		$mergedConfig = [
			'total_horizontal'        => isset($cfg['total_horizontal']) ? (bool)$cfg['total_horizontal'] : ($this->request->getPost('total_horizontal') === '1' || $this->request->getPost('total_horizontal') === 'true'),
			'jenis_total_horizontal'  => $cfg['jenis_total_horizontal'] ?? ($this->request->getPost('jenis_total_horizontal') ?: 'sum'),
			'only_total_horizontal'   => isset($cfg['only_total_horizontal']) ? (bool)$cfg['only_total_horizontal'] : ($this->request->getPost('only_total_horizontal') === '1' || $this->request->getPost('only_total_horizontal') === 'true'),
			'group_total_horizontal'  => isset($cfg['group_total_horizontal']) ? (bool)$cfg['group_total_horizontal'] : ($this->request->getPost('group_total_horizontal') === '1' || $this->request->getPost('group_total_horizontal') === 'true'),
			'total_vertikal'          => isset($cfg['total_vertikal']) ? (bool)$cfg['total_vertikal'] : ($this->request->getPost('total_vertikal') === '1' || $this->request->getPost('total_vertikal') === 'true'),
			'jenis_total_vertikal'    => $cfg['jenis_total_vertikal'] ?? ($this->request->getPost('jenis_total_vertikal') ?: 'default'),
			'label_total_kustom'      => $cfg['label_total_kustom'] ?? ($this->request->getPost('label_total_kustom') ?: ''),
			'tahun_runtun_waktu'      => $cfg['tahun_runtun_waktu'] ?? ($this->request->getPost('tahun_runtun_waktu') ?: []),
			'group_by_region'         => isset($cfg['group_by_region']) ? (bool)$cfg['group_by_region'] : ($this->request->getPost('group_by_region') === '1' || $this->request->getPost('group_by_region') === 'true'),
			'prioritas_karakteristik' => isset($cfg['prioritas_karakteristik']) ? (bool)$cfg['prioritas_karakteristik'] : ($this->request->getPost('prioritas_karakteristik') === '1' || $this->request->getPost('prioritas_karakteristik') === 'true'),
			'portrait_mode'           => isset($cfg['portrait_mode']) ? (bool)$cfg['portrait_mode'] : ($this->request->getPost('portrait_mode') === '1' || $this->request->getPost('portrait_mode') === 'true'),
			'portrait_split_col'      => isset($cfg['portrait_split_col']) ? (bool)$cfg['portrait_split_col'] : ($this->request->getPost('portrait_split_col') === '1' || $this->request->getPost('portrait_split_col') === 'true'),
		];

		// Sinkronisasi posisi_hirarki
		$posisi_hirarki = 'periode_atas';
		if ($mergedConfig['portrait_mode']) {
			$posisi_hirarki = $mergedConfig['portrait_split_col'] ? 'periode_baris_pisah' : 'periode_baris';
		} elseif ($mergedConfig['prioritas_karakteristik']) {
			$posisi_hirarki = 'kolom_atas';
		}

		// Sinkronisasi konfigurasi_total
		$konfigurasi_total = 'default';
		if (!$mergedConfig['total_vertikal']) {
			$konfigurasi_total = 'none';
		} else {
			$konfigurasi_total = $mergedConfig['jenis_total_vertikal'] ?: 'default';
		}

		$updateData = [
			'config_tampilan'    => json_encode($mergedConfig),
			'posisi_hirarki'     => $posisi_hirarki,
			'konfigurasi_total'  => $konfigurasi_total,
			'label_total_kustom' => !empty($mergedConfig['label_total_kustom']) ? $mergedConfig['label_total_kustom'] : null,
			'updated_at'         => date('Y-m-d H:i:s'),
		];

		if ($this->request->getPost('satuan') !== null) {
			$updateData['satuan'] = trim((string)$this->request->getPost('satuan'));
		}
		if ($this->request->getPost('desimal') !== null) {
			$updateData['desimal'] = (int)$this->request->getPost('desimal');
		}
		if ($this->request->getPost('tampilan_baris') !== null) {
			$updateData['tampilan_baris'] = trim((string)$this->request->getPost('tampilan_baris'));
		}
		if ($this->request->getPost('kolom_nomor') !== null) {
			$updateData['kolom_nomor'] = trim((string)$this->request->getPost('kolom_nomor'));
		}

		$this->db->table('m_indikator_tabel')->where('id', $id_indikator)->update($updateData);

		return $this->response->setJSON([
			'status'  => 'success',
			'message' => 'Konfigurasi tampilan tabel berhasil disimpan.',
			'config'  => $mergedConfig,
		]);
	}

	/**
	 * Endpoint AJAX: Kunci Pilihan Tahun Ini Sebagai Default Tampil
	 */
	public function ajax_save_default_tahun_tampil()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Sesi berakhir']);
		}

		$id_indikator = (int)$this->request->getPost('id_indikator');
		if (!$id_indikator) {
			$id_tabel = (int)$this->request->getPost('id_tabel');
			if ($id_tabel > 0) {
				$ind = $this->db->table('m_indikator_tabel')->where('id_tabel', $id_tabel)->get()->getRow();
				if ($ind) $id_indikator = (int)$ind->id;
			}
		}

		if (!$id_indikator) {
			return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'ID Indikator tidak valid']);
		}

		$indRow = $this->db->table('m_indikator_tabel')->where('id', $id_indikator)->get()->getRow();
		if (!$indRow) {
			return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Indikator tabel tidak ditemukan']);
		}

		if (!empty($indRow->id_tabel)) {
			$tabelRow = $this->db->table('m_list_tabel')->where('id', (int)$indRow->id_tabel)->get()->getRow();
			if ($tabelRow && !$this->canManageTable($tabelRow)) {
				return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak! Anda tidak berwenang mengubah konfigurasi tabel instansi lain.']);
			}
		}

		$tahun_tampil_raw = $this->request->getPost('tahun_tampil');
		$years = [];
		if (is_array($tahun_tampil_raw)) {
			$years = $tahun_tampil_raw;
		} elseif (!empty($tahun_tampil_raw)) {
			$years = explode(',', (string)$tahun_tampil_raw);
		}

		$cleanYears = array_values(array_unique(array_filter(array_map('intval', $years))));
		sort($cleanYears);
		$tahunStr = implode(',', $cleanYears);

		$this->db->table('m_indikator_tabel')
			->where('id', $id_indikator)
			->update([
				'tahun_tampil' => $tahunStr ?: null,
				'updated_at'   => date('Y-m-d H:i:s'),
			]);

		return $this->response->setJSON([
			'status'       => 'success',
			'message'      => 'Tahun tampil default berhasil dikunci.',
			'tahun_tampil' => $tahunStr,
		]);
	}

	/**
	 * Endpoint AJAX: Preview Cepat 5 Baris Matriks Tabel Dinamis
	 * Menghasilkan cuplikan HTML 5 baris table_matrix_dinamis tanpa menyimpan ke database.
	 */
	public function ajax_preview_matrix_dinamis()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Sesi berakhir']);
		}

		$id_tabel = (int)$this->request->getPost('id_tabel');
		$id_indikator = (int)$this->request->getPost('id_indikator');
		if (!$id_tabel && !$id_indikator) {
			return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'ID Tabel atau Indikator tidak valid']);
		}

		$ta = (int)($this->session->get('admin_ta') ?: ($this->session->get('tahun') ?: date('Y')));
		$thDataDefault = (int)$ta - 1;
		$mTabelDinamis = new \App\Models\M_tabeldinamis();
		$context = $mTabelDinamis->resolveTableContext($id_tabel ?: $id_indikator, $ta);
		$tabel = $context['tabel'] ?? null;
		$indikator = $context['indikator'] ?? null;

		if (!$tabel) {
			return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Data tabel tidak ditemukan']);
		}

		// Lengkapi objek tabel
		$tabel->id_tahun_tabel = (int)$tabel->id;
		$tabel->id_master_tabel = (int)$tabel->id;

		$structureKolom  = null;
		$itemsKolom      = [];
		$itemsBaris      = [];
		$kelompokBaris   = null;
		$itemsPeriode    = [];
		$kelompokPeriode = null;
		$nilaiMap        = [];
		$available_years = [];
		$selected_years  = [];
		$configTampilan  = [
			'total_horizontal'        => false,
			'jenis_total_horizontal'  => 'sum',
			'only_total_horizontal'   => false,
			'group_total_horizontal'  => false,
			'total_vertikal'          => true,
			'jenis_total_vertikal'    => 'default',
			'label_total_kustom'      => '',
			'group_by_region'         => true,
			'prioritas_karakteristik' => false,
			'portrait_mode'           => false,
			'portrait_split_col'      => false,
		];

		if ($indikator) {
			if (!empty($indikator->config_tampilan)) {
				$decodedCfg = json_decode($indikator->config_tampilan, true);
				if (is_array($decodedCfg)) {
					$configTampilan = array_merge($configTampilan, $decodedCfg);
				}
			}

			// Timpa konfigurasi dari payload preview
			$configRaw = $this->request->getPost('config_json');
			if (!empty($configRaw)) {
				$prevCfg = is_array($configRaw) ? $configRaw : json_decode($configRaw, true);
				if (is_array($prevCfg)) {
					$configTampilan = array_merge($configTampilan, $prevCfg);
					if (isset($prevCfg['satuan'])) $indikator->satuan = $prevCfg['satuan'];
					if (isset($prevCfg['desimal'])) $indikator->desimal = (int)$prevCfg['desimal'];
					if (isset($prevCfg['tampilan_baris'])) $indikator->tampilan_baris = $prevCfg['tampilan_baris'];
					if (isset($prevCfg['kolom_nomor'])) $indikator->kolom_nomor = $prevCfg['kolom_nomor'];
					if (isset($prevCfg['tingkat_kolom'])) $indikator->tingkat_kolom = $prevCfg['tingkat_kolom'];
				}
			}

			if (!empty($indikator->id_karakteristik_kelompok)) {
				$itemsKolom     = $mTabelDinamis->getKarakteristikItems((int)$indikator->id_karakteristik_kelompok);
				$structureKolom = $mTabelDinamis->buildColumnStructure($itemsKolom);
			}

			if (!empty($indikator->id_baris_kelompok)) {
				$kelompokBaris = $mTabelDinamis->getBarisKelompok((int)$indikator->id_baris_kelompok);
				$itemsBaris    = $mTabelDinamis->getBarisItems((int)$indikator->id_baris_kelompok);
			}

			if (!empty($indikator->id_periode_kelompok)) {
				$kelompokPeriode = $mTabelDinamis->getPeriodeKelompok((int)$indikator->id_periode_kelompok);
				$itemsPeriode    = $mTabelDinamis->getPeriodeItems((int)$indikator->id_periode_kelompok);
			}

			$available_years = $mTabelDinamis->getAvailableYears((int)$indikator->id);

			$reqYears = $this->request->getPost('tahun') ?: $this->request->getPost('years') ?: $this->request->getGet('tahun');
			$rawReqList = [];
			if (!empty($reqYears)) {
				$rawReqList = is_array($reqYears) ? $reqYears : explode(',', (string)$reqYears);
			}

			$selected_years = $mTabelDinamis->resolveDisplayYears($indikator->tahun_tampil ?? null, (int)$ta, $rawReqList, $indikator);
			sort($selected_years);

			// Ambil nilai dari t_nilai_dinamis (Optimasi Index Seek)
			$cleanYears = array_values(array_filter(array_map('intval', $selected_years)));
			$nilaiBuilder = $this->db->table('t_nilai_dinamis')
				->where('id_indikator', (int)$indikator->id);
			if (!empty($cleanYears)) {
				$nilaiBuilder->whereIn('tahun_data', $cleanYears);
			}
			$nilaiRows = $nilaiBuilder->get()->getResult();

			foreach ($nilaiRows as $nr) {
				$th = (int)$nr->tahun_data;
				$bId = (int)$nr->id_baris_item;
				$kId = (int)$nr->id_karakteristik_item;
				$pId = (int)($nr->id_periode_item ?? 0);

				$nilaiMap[$th][$bId][$kId][$pId] = [
					'nilai'          => $nr->nilai !== null ? (float)$nr->nilai : null,
					'simbol'         => $nr->simbol ?: null,
					'jumlah_desimal' => $nr->jumlah_desimal !== null ? (int)$nr->jumlah_desimal : null,
				];
			}
		} else {
			$available_years = [$ta];
			$selected_years = [$ta];
		}

		// Batasi hanya 5 baris data pertama untuk preview cepat (dengan menyertakan header kategori jika ada)
		$previewItemsBaris = [];
		$dataRowCount = 0;
		foreach ($itemsBaris as $b) {
			$previewItemsBaris[] = $b;
			$tipeBaris = strtolower(trim($b->tipe_baris ?? 'data'));
			$lowerBaris = strtolower(trim($b->nama_baris_ind ?? ''));
			$isHeader = ($tipeBaris === 'kategori' || $lowerBaris === 'kabupaten' || $lowerBaris === 'kota');
			if (!$isHeader) {
				$dataRowCount++;
				if ($dataRowCount >= 5) {
					break;
				}
			}
		}
		if (empty($previewItemsBaris)) {
			$previewItemsBaris = array_slice($itemsBaris, 0, 5);
		}

		$viewData = [
			'tabel'            => $tabel,
			'indikator'        => $indikator,
			'structureKolom'   => $structureKolom,
			'itemsKolom'       => $itemsKolom,
			'itemsBaris'       => $previewItemsBaris,
			'kelompokBaris'    => $kelompokBaris,
			'itemsPeriode'     => $itemsPeriode,
			'kelompokPeriode'  => $kelompokPeriode,
			'nilaiMap'         => $nilaiMap,
			'available_years'  => $available_years,
			'selected_years'   => $selected_years,
			'config_tampilan'  => $configTampilan,
			'matrix_dom_id'    => 'table-dinamis-matrix-preview',
			'isPreview'        => true,
		];

		$html = view('admin/parts/table_matrix_dinamis', $viewData);

		return $this->response->setJSON([
			'status' => 'success',
			'html'   => $html,
		]);
	}

}
