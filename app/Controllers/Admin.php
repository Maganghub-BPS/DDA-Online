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

		$this->load->model('m_kelolakegiatan');
		$stats = $this->m_kelolakegiatan->get_statistik_dashboard($ta);

		$a['stat_diisi']       = (int) $stats->diisi;
		$a['stat_belum_diisi'] = (int) $stats->belum_diisi;
		$a['stat_sudah_acc']   = (int) $stats->sudah_acc;
		$a['stat_belum_acc']   = (int) $stats->belum_acc;

		$data_opd = $this->m_kelolakegiatan->get_statistik_per_opd($ta);

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

		$data_tim = $this->m_kelolakegiatan->get_statistik_per_tim($ta);

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

		/* pagination */
		$total_row		= $this->db->query("SELECT * FROM t_admin")->getNumRows();
		$per_page		= 10;

		$awal	= $this->uri->segment(4);
		$awal	= (empty($awal) || $awal == 1) ? 0 : $awal;

		//if (empty($awal) || $awal == 1) { $awal = 0; } { $awal = $awal; }
		$akhir	= $per_page;

		$a['pagi']	= _page($total_row, $per_page, 4, base_url() . "admin/manage_admin/p");

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
			$a['data']		= $this->db->query("SELECT * FROM t_admin LIMIT $awal, $akhir ")->getResult();
			$a['page']		= "l_manage_admin";
		}

		return view('admin/index', $a);
	}

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

	public function profil()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

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



	public function get_kegiatan()
	{
		$unitkerja = $this->input->post('unitkerja');
		$bidang = substr($unitkerja, 1, 4);
		$query 	=  $this->db->query("SELECT * FROM m_jeniskegiatan WHERE substring(id_jeniskegiatan,1,4)='$bidang'");
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
			$this->db->query("update t_kontrak set flag_konfirm='1' where id_kontrak=?", [$id_kontrak_konfirm]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data sudah dikonfirmasi selesai</div>");
			return redirect()->to('admin/oi/');
		} else {
			$a['page'] = 'dashboard';
		}

		return view('admin/index', $a);
	}




	//dda
	public function dda()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$ta = $this->session->get('admin_ta');

		/* pagination */
		$total_row		= $this->db->query("SELECT * FROM t_list_tabel")->getNumRows();
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
			$id_konfirmasi = $this->input->get('konfirmasi_id');;
		?>
			<script>
				//window.alert('<?php echo $id_konfirmasi; ?>');
			</script>
		<?php
			$a['datpil']	= $this->db->query("select l.id, l.judul_ind, l.judul_en, l.id_unitkerja, m.unitkerja_ind from t_list_tabel l left join m_unitkerja m on l.id_unitkerja=m.id_unitkerja where l.id=?", [$id_konfirmasi])->getRow();
			$a['page']		= "f_konfirmasi";
		} else if ($mau_ke == "act_konfirmasi") {
			$id_data = $this->input->post('id');
			$catatan = $this->input->post('catatan');

			//$confirmed_by=$this->session->get('admin_nama');
			$this->db->query("UPDATE t_list_tabel SET is_confirm='1', catatan =? where id=?", [$catatan, $id_data]);
			return redirect()->back()->with('k', '<div class="alert alert-success" id="alert">Data has been updated</div>');
		} else if ($mau_ke == "periksa") {
			$id_konfirmasi = $this->input->get('konfirmasi_id');;
		?>
			<script>
				//window.alert('<?php echo $id_konfirmasi; ?>');
			</script>
		<?php
			$a['datpil']	= $this->db->query("select l.id, l.judul_ind, l.judul_en, l.id_unitkerja, m.unitkerja_ind from t_list_tabel l left join m_unitkerja m on l.id_unitkerja=m.id_unitkerja where l.id=?", [$id_konfirmasi])->getRow();
			$a['page']		= "f_periksa";
		} else if ($mau_ke == "act_periksa") {
			$id_data = $this->input->post('id');
			$catatan_periksa = $this->input->post('catatan_periksa');

			//$confirmed_by=$this->session->get('admin_nama');
			$this->db->query("UPDATE t_list_tabel SET is_confirm='1',catatan=? where id=?", [$catatan_periksa, $id_data]);
			return redirect()->back()->with('k', '<div class="alert alert-success" id="alert">Data has been updated</div>');
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
		?>
			<script>
				//window.alert('<?php echo $idu; ?>');
			</script>
		<?php
			$this->db->query("UPDATE t_oi SET kepentingan_kel = ?, kepentingan_uraian = ? where id = ?", [$kepentingan_kel, $kepentingan_uraian, $idp]);

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated</div>");
			return redirect()->to('admin/dda/');
		} else if ($mau_ke == "kondef") {
			$id_tabel = $this->input->get('id');
			$a['datpil']	= $this->db->query("SELECT * from t_list_tabel WHERE id = ?", [$id_tabel])->getRow();
			$a['page']		= "f_kondef";
		} else if ($mau_ke == "kondef_update") {
			$idp					= $this->input->post('idp');
			$kondef					= $this->input->post('kondef');
			$a['datpil']			= $this->db->query("update t_list_tabel set kondef=? WHERE id = ?", [$kondef, $idp]);
			return redirect()->to('admin/dda/');
		} else if ($mau_ke == "view_tabel") {
			$id_unitkerja = $this->input->get('id');
			$a['data']		= $this->db->query("SELECT * FROM t_list_tabel where id_unitkerja=? and tahun=? ", [$id_unitkerja, $ta])->getResult();
			$a['page']		= "l_dda";
		} else if ($mau_ke == "periksa_tabel") {
			$id_unitkerja = $this->input->get('id');
			$level = $this->session->get('admin_level');
			if ($this->session->get('admin_user') == 'diseminasi' || $level == 'Admin' || $level == 'Super Admin') {
				$a['data']		= $this->db->query("SELECT * FROM t_list_tabel where id_unitkerja=? and tahun=? LIMIT $awal, $akhir ", [$id_unitkerja, $ta])->getResult();
				$a['page']		= "l_dda_periksa";
			} else {
				$a['data']		= $this->db->query("SELECT * FROM t_list_tabel where id_unitkerja=? and is_confirm='1' and tahun=? LIMIT $awal, $akhir ", [$id_unitkerja, $ta])->getResult();
				$a['page']		= "l_dda_periksa";
			}
		} else {
			$unitkerjalogin = $this->session->get('admin_unitkerja');

			if ($unitkerjalogin == 'bps') {
				/* pagination */
				$total_row		= $this->db->query("SELECT * FROM m_unitkerja")->getNumRows();
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
					$a['data']		= $this->db->query("SELECT * FROM m_unitkerja order by unitkerja_ind LIMIT ?, ?", [(int)$awal, (int)$akhir])->getResult();
					//$a['datadiperiksa'] = $this->db->query("SELECT * FROM m_unitkerja LIMIT $awal, $akhir")->getResult();
				} else {
					$user_wali = $this->session->get('admin_user');
					$user_level = $this->session->get('admin_level');
					if ($user_level == 'lo') {
						$a['data']		= $this->db->query("SELECT * FROM m_unitkerja where user_wali=?", [$user_wali])->getResult();
					} else if ($user_level == 'spv') {
						$a['data'] = $this->db->query("SELECT * FROM m_unitkerja where user_spv=?", [$user_wali])->getResult();
					}
				}

				$a['page']		= "l_dda_admin";
			} else {
				$a['data']		= $this->db->query("SELECT * FROM t_list_tabel where id_unitkerja=? and tahun=? ", [$unitkerjalogin, $ta])->getResult();
				$a['page']		= "l_dda";
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

		/* pagination */
		$total_row		= $this->db->query("SELECT * FROM m_unitkerja")->getNumRows();
		$per_page		= 10;

		$awal	= $this->uri->segment(4);
		$awal	= (empty($awal) || $awal == 1) ? 0 : $awal;

		if (empty($awal) || $awal == 1) {
			$awal = 0;
		}
		$akhir	= $per_page;

		$a['pagi']	= _page($total_row, $per_page, 4, base_url() . "admin/forum_diskusi/p");

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
				$a['data']		= $this->db->query("SELECT * FROM m_forumtopic where id_unitkerja=? LIMIT ?, ?", [$id_unitkerja, (int)$awal, (int)$akhir])->getResult();
				$a['page']		= "l_forum_topik";
			} else {
				$a['data']		= $this->db->query("SELECT * FROM m_forumtopic where id_unitkerja=? LIMIT ?, ?", [$unitkerjalogin, (int)$awal, (int)$akhir])->getResult();
				$a['page']		= "l_forum_topik";
			}
		} else if ($mau_ke == 'add_topik') {
			$a['page']		= "f_tambah_topik";
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
		} else {
			if ($unitkerjalogin == 'bps') {
				$level = $this->session->get('admin_level');
				if ($this->session->get('admin_user') == 'diseminasi' || $level == 'Admin' || $level == 'Super Admin') {
					$a['data']		= $this->db->query("SELECT * FROM m_unitkerja LIMIT ?, ?", [(int)$awal, (int)$akhir])->getResult();
				} else {
					$user_wali = $this->session->get('admin_user');
					$user_level = $this->session->get('admin_level');
					if ($user_level == 'lo') {
						$a['data']		= $this->db->query("SELECT * FROM m_unitkerja where user_wali=?", [$user_wali])->getResult();
					} else if ($user_level == 'spv') {
						$a['data'] = $this->db->query("SELECT * FROM m_unitkerja where user_spv=?", [$user_wali])->getResult();
					}
				}
				$a['page']		= "l_forum";
			} else {
				$a['data']		= $this->db->query("SELECT * FROM m_unitkerja where id_unitkerja=? LIMIT ?, ?", [$unitkerjalogin, (int)$awal, (int)$akhir])->getResult();
				$a['page']		= "l_forum";
			}
			if ($this->session->get('admin_level') == 'kominfo') {
				$a['data']		= $this->db->query("SELECT * FROM m_unitkerja LIMIT ?, ?", [(int)$awal, (int)$akhir])->getResult();
				$a['page']		= "l_forum";
			}
		}
		return view('admin/index', $a);
	}

	//master_tabel
	public function master_tabel()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$ta = $this->session->get('admin_ta');

		$a['list_opd'] = $this->db->query("SELECT * FROM m_unitkerja ORDER BY unitkerja_ind ASC")->getResult();
		$a['list_tim'] = $this->db->query("SELECT DISTINCT user_wali FROM m_unitkerja WHERE user_wali != '' AND user_wali != '-' ORDER BY user_wali ASC")->getResult();

		$mau_ke = $this->uri->segment(3);
		$idu    = $this->uri->segment(4);

		$idp             = $this->input->post('idp');
		$judul_ind       = $this->input->post('judul_ind');
		$judul_en        = $this->input->post('judul_en');
		$link_tabel      = $this->input->post('link_tabel');
		$link_sebelumnya = $this->input->post('link_sebelumnya');
		$id_unitkerja    = $this->input->post('id_unitkerja');

		$is_confirm      = '2';
		$catatan         = '-';
		$is_periksa      = '0';
		$catatan_periksa = '-';
		$kondef          = '-';


		if ($mau_ke == "del") {
			$id_delete   = $this->input->get('delete_id');
			$a['datpil'] = $this->db->query("select * from t_list_tabel where id=?", [$id_delete])->getRow();
			$a['page']   = "f_del_master";
		} else if ($mau_ke == "act_del") {
			$id_oi = $this->input->post('id');
			$this->db->query("DELETE FROM t_list_tabel WHERE id = ?", [$id_oi]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/master_tabel/');
		} else if ($mau_ke == "cari") {
			$filter_opd    = $this->session->get('sess_f_opd') ?? 'all';
			$filter_bidang = $this->session->get('sess_f_bidang') ?? 'all';
			$filter_tahun  = $this->session->get('sess_f_tahun') ?? 'all';

			$where_tahun = ($filter_tahun != 'all') ? "l.tahun = ?" : "1=1";
			$cari = $this->request->getPost('q');

			$params = [];
			if ($filter_tahun != 'all') $params[] = $filter_tahun;

			$where_filters = "WHERE $where_tahun";
			if ($filter_opd != 'all') {
				$where_filters .= " AND l.id_unitkerja = ? ";
				$params[] = $filter_opd;
			}
			if ($filter_bidang != 'all') {
				$where_filters .= " AND u.user_wali = ? ";
				$params[] = $filter_bidang;
			}

			$params[] = '%' . $cari . '%';
			$params[] = '%' . $cari . '%';

			$a['data'] = $this->db->query("
            SELECT l.*, u.unitkerja_ind, u.user_wali 
            FROM t_list_tabel l 
            LEFT JOIN m_unitkerja u ON l.id_unitkerja = u.id_unitkerja 
            $where_filters 
            AND (l.judul_ind LIKE ? OR u.unitkerja_ind LIKE ?)
            ORDER BY l.id DESC
        ", $params)->getResult();

			if ($this->request->isAJAX()) {
				return view('admin/l_master_tabel_partial', $a);
			}

			$a['selected_opd']    = $filter_opd;
			$a['selected_bidang'] = $filter_bidang;
			$a['selected_tahun']  = $filter_tahun;

			$a['list_tim'] = $this->db->query("SELECT DISTINCT user_wali FROM m_unitkerja WHERE user_wali != '' ORDER BY user_wali ASC")->getResult();
			$a['opd']      = $this->db->query("SELECT * FROM m_unitkerja ORDER BY id_unitkerja ASC")->getResult();

			$a['pagi'] = "";
			$a['page'] = "l_master_tabel";
		} else if ($mau_ke == "add") {
			$a['page'] = "f_master_tabel";
		} else if ($mau_ke == "edt") {
			$a['datpil'] = $this->db->query("SELECT * from t_list_tabel WHERE id = ?", [$idu])->getRow();
			$a['page']   = "f_master_tabel";
		} else if ($mau_ke == "act_add") {
			$hariini = date('Y-m-d');
			$this->db->query("INSERT INTO t_list_tabel (id, judul_ind, judul_en, link_tabel, link_sebelumnya, id_unitkerja, is_confirm, catatan, is_periksa, catatan_periksa, kondef, tahun) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$judul_ind, $judul_en, $link_tabel, $link_sebelumnya, $id_unitkerja, $is_confirm, $catatan, $is_periksa, $catatan_periksa, $kondef, $ta]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data berhasil ditambahkan</div>");
			return redirect()->to('admin/master_tabel/');
		} else if ($mau_ke == "act_edt") {
			$this->db->query("UPDATE t_list_tabel SET judul_ind =?, judul_en= ?, link_tabel = ?, link_sebelumnya = ?, id_unitkerja=? where id=?", [$judul_ind, $judul_en, $link_tabel, $link_sebelumnya, $id_unitkerja, $idp]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data berhasil di update</div>");
			return redirect()->to('admin/master_tabel/');
		} else {
			if (isset($_GET['action']) && $_GET['action'] == 'reset') {
				session()->remove('sess_f_opd');
				session()->remove('sess_f_bidang');
				session()->remove('sess_f_tahun');
				return redirect()->to('admin/master_tabel');
			}

			if (isset($_GET['filter_opd'])) {
				$val = $_GET['filter_opd'];
				$this->session->set('sess_f_opd', $val);
			}

			if (isset($_GET['filter_bidang'])) {
				$val = $_GET['filter_bidang'];
				$this->session->set('sess_f_bidang', $val);
			}

			if (isset($_GET['filter_tahun'])) {
				$val = $_GET['filter_tahun'];
				$this->session->set('sess_f_tahun', $val);
			}

			$filter_opd    = $this->session->get('sess_f_opd');
			$filter_bidang = $this->session->get('sess_f_bidang');
			$filter_tahun  = $this->session->get('sess_f_tahun');

			if (empty($filter_opd)) $filter_opd = 'all';
			if (empty($filter_bidang)) $filter_bidang = 'all';
			if (empty($filter_tahun)) $filter_tahun = 'all';

			$a['selected_opd']    = $filter_opd;
			$a['selected_bidang'] = $filter_bidang;
			$a['selected_tahun']  = $filter_tahun;

			$sql_base  = "FROM t_list_tabel l LEFT JOIN m_unitkerja u ON l.id_unitkerja = u.id_unitkerja";
			$sql_where = "WHERE 1=1";

			$params_count = [];
			if ($filter_tahun != 'all') {
				$sql_where .= " AND l.tahun = ?";
				$params_count[] = $filter_tahun;
			}

			if ($filter_opd != 'all') {
				$sql_where .= " AND l.id_unitkerja = ?";
				$params_count[] = $filter_opd;
			}

			if ($filter_bidang != 'all') {
				$sql_where .= " AND u.user_wali = ?";
				$params_count[] = $filter_bidang;
			}

			$total_row = $this->db->query("SELECT l.id $sql_base $sql_where", $params_count)->getNumRows();
			$per_page  = 20;

			$awal = $this->uri->segment(4);
			$awal = (empty($awal) || $awal == 1) ? 0 : $awal;
			if (empty($awal)) {
				$awal = 0;
			}

			$a['pagi'] = _page($total_row, $per_page, 4, base_url() . "admin/master_tabel/p");

			$params_data = $params_count;
			$params_data[] = (int)$awal;
			$params_data[] = (int)$per_page;

			$a['data'] = $this->db->query("
            SELECT l.*, u.unitkerja_ind, u.user_wali 
            $sql_base 
            $sql_where 
            ORDER BY l.id DESC 
            LIMIT ?, ?
        ", $params_data)->getResult();

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

		$ta = $this->session->get('admin_ta');

		/* pagination */
		$total_row		= $this->db->query("SELECT * FROM m_master_tabel_usulan")->getNumRows();
		$per_page		= 10;

		$awal	= $this->uri->segment(4);
		$awal	= (empty($awal) || $awal == 1) ? 0 : $awal;

		if (empty($awal) || $awal == 1) {
			$awal = 0;
		}
		$akhir	= $per_page;

		$a['pagi']	= _page($total_row, $per_page, 4, base_url() . "admin/master_tabel_opd/p");

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
		$config['file_name'] 		= $kode . '_' . $unitkerja . '_' . $addby;

		$this->load->library('upload', $config);

		if ($mau_ke == "del") {
			$id_delete = $this->input->get('delete_id');;
		?>
			<script>
				//window.alert('<?php echo $id_delete; ?>');
			</script>
<?php
			$a['datpil']	= $this->db->query("select * from m_master_tabel_usulan where id=?", [$id_delete])->getRow();
			$a['page']		= "f_del_master";
		} else if ($mau_ke == "act_del") {
			$id = $this->input->post('id');
			$this->db->query("DELETE FROM m_master_tabel_usulan WHERE id = ?", [$id]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been deleted </div>");
			return redirect()->to('admin/master_tabel_opd/');
		} else if ($mau_ke == "cari") {

			$a['data']		= $this->db->query("SELECT * from t_list_tabel where keterangan_uraian LIKE ? and tahun=?", ['%' . $cari . '%', $ta])->getResult();
			$a['page']		= "l_master_tabel";
		} else if ($mau_ke == "add") {

			$a['page']		= "f_add_opd";
		} else if ($mau_ke == "edt") {

			$a['datpil']	= $this->db->query("SELECT * from m_master_tabel_usulan WHERE id = ?", [$idu])->getRow();
			$a['page']		= "f_add_opd";
		} else if ($mau_ke == "act_add") {
			$hariini = date('Y-m-d');
			date_default_timezone_set("Asia/Jakarta");
			$waktusekarang = date("H:i");
			if ($this->upload->do_upload('file_tabel')) {
				$up_data	 	= $this->upload->data();
				$this->db->query("INSERT INTO m_master_tabel_usulan VALUES (NULL, ?, ?, ?, ?, ?, ?)", [$judul_ind, $judul_en, $up_data['file_name'], $is_setujui, $unitkerja, $addby]);
			}
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Tabel berhasil ditambahkan</div>");
			return redirect()->to('admin/');
		} else if ($mau_ke == "act_edt") {
			$id_unitkerja				= $this->input->post('id_unitkerja');
			$this->db->query("UPDATE m_master_tabel_usulan SET is_setujui ='1' where id=?", [$idp]);
			$this->db->query("INSERT INTO t_list_tabel (id, judul_ind, judul_en, link_tabel, link_sebelumnya, id_unitkerja, is_confirm, catatan, is_periksa, catatan_periksa, kondef, tahun) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$judul_ind, $judul_en, '', '', $id_unitkerja, '2', '-', '0', '-', '-', $ta]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data berhasil di konfirmasi</div>");
			return redirect()->to('admin/master_tabel_opd/');
		} else {
			$a['data']		= $this->db->query("SELECT l.*,u.unitkerja_ind FROM m_master_tabel_usulan l left join m_unitkerja u on l.id_unitkerja=u.id_unitkerja LIMIT ?, ?", [(int)$awal, (int)$akhir])->getResult();
			$a['page']		= "l_master_tabel_opd";
		}
		return view('admin/index', $a);
	}


	public function master_opd()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		/* pagination */
		$total_row		= $this->db->query("SELECT * FROM m_unitkerja")->getNumRows();
		$per_page		= 10;

		$awal	= $this->uri->segment(4);
		$awal	= (empty($awal) || $awal == 1) ? 0 : $awal;

		//if (empty($awal) || $awal == 1) { $awal = 0; } { $awal = $awal; }
		$akhir	= $per_page;

		$a['pagi']	= _page($total_row, $per_page, 4, base_url() . "admin/master_opd/p");

		//ambil variabel URL
		$mau_ke					= $this->uri->segment(3);
		$idu					= $this->uri->segment(4);

		$cari					= $this->input->post('q');

		//ambil variabel Postingan
		$idp			= $this->input->post('idp');
		$id_unitkerja	= $this->input->post('id_unitkerja');
		$unitkerja_ind	= $this->input->post('unitkerja_ind');
		$unitkerja_en	= $this->input->post('unitkerja_en');
		$user_wali		= $this->input->post('user_wali');
		$user_spv		= $this->input->post('user_spv');


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

			if (strlen($id_unitkerja) < 3) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">ID Unitkerja minimal 4 huruf</div>");
				return redirect()->to('admin/master_opd');
			} else if ($cek_user_exist > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">OPD telah ditambahkan. Ganti yang lain..!</div>");
				return redirect()->to('admin/master_opd');
			} else {
				$this->db->query("INSERT INTO m_unitkerja VALUES (?, ?, ?, ?, ?)", [$id_unitkerja, $unitkerja_ind, $unitkerja_en, $user_wali, $user_spv]);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
			}

			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been added</div>");
			return redirect()->to('admin/master_opd');
		} else if ($mau_ke == "act_edt") {

			$this->db->query("UPDATE m_unitkerja SET id_unitkerja = ?, unitkerja_ind = ?, unitkerja_en = ?, user_wali = ?, user_spv = ? WHERE id_unitkerja = ?", [$id_unitkerja, $unitkerja_ind, $unitkerja_en, $user_wali, $user_spv, $idp]);


			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data has been updated </div>");
			return redirect()->to('admin/master_opd');
		} else {
			$a['data']		= $this->db->query("SELECT * FROM m_unitkerja LIMIT ?, ?", [(int)$awal, (int)$akhir])->getResult();
			$a['page']		= "l_unitkerja";
		}

		return view('admin/index', $a);
	}

	public function set_tahun($tahun = null)
	{
		if (!$tahun)
			$tahun = date('Y');

		$this->session->set('admin_ta', $tahun);
		return redirect()->to('admin/index');
	}

	public function act_update_isi()
	{
		$id_tabel = $this->input->post('id_tabel');
		$catatan  = $this->input->post('catatan_periksa');

		$data = array(
			'is_periksa' => '1',
			'catatan_periksa' => $catatan
		);

		$this->db->table('t_list_tabel')->where('id', $id_tabel)->update($data);

		return redirect()->back()->with('k', '<div class="alert alert-success">Status berhasil diperbarui!</div>');
	}

	public function batal_isi($id)
	{
		$data = array(
			'is_periksa' => '0',
			'catatan_periksa' => '-'
		);
		$this->db->table('t_list_tabel')->where('id', $id)->update($data);
		return redirect()->back();
	}
	public function export_excel_instansi()
	{
		error_reporting(0);
		ini_set('display_errors', 0);

		$ta = $this->session->get('admin_ta');
		if (empty($ta)) $ta = date('Y');

		header("Content-type: application/vnd-ms-excel");
		header("Content-Disposition: attachment; filename=Laporan_Instansi_$ta.xls");

		$data = $this->db->query("
        SELECT m.unitkerja_ind,
            COUNT(t.id) as jumlah_tabel,
            SUM(CASE WHEN t.is_confirm != 1 AND t.is_periksa = 0 AND NOT (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%') THEN 1 ELSE 0 END) as belum_isi,
            SUM(CASE WHEN t.is_confirm != 1 AND (t.is_periksa = 1 OR (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%')) THEN 1 ELSE 0 END) as menunggu_validasi,
            SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as sudah_validasi
        FROM m_unitkerja m 
        LEFT JOIN t_list_tabel t ON m.id_unitkerja = t.id_unitkerja AND t.tahun = ?
        GROUP BY m.id_unitkerja 
        ORDER BY m.unitkerja_ind ASC
    ", [$ta])->getResult();

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

		error_reporting(0);
		ini_set('display_errors', 0);

		$ta = $this->session->get('admin_ta');
		if (empty($ta)) $ta = date('Y');

		header("Content-type: application/vnd-ms-excel");
		header("Content-Disposition: attachment; filename=Laporan_Tim_$ta.xls");

		$data = $this->db->query("
            SELECT 
                u.user_wali,
                COUNT(t.id) as jumlah_tabel,
                SUM(CASE WHEN t.is_confirm != 1 AND t.is_periksa = 0 AND NOT (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%') THEN 1 ELSE 0 END) as belum_isi,
                SUM(CASE WHEN t.is_confirm != 1 AND (t.is_periksa = 1 OR (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%')) THEN 1 ELSE 0 END) as menunggu_validasi,
                SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as sudah_validasi
            FROM m_unitkerja u
            LEFT JOIN t_list_tabel t ON u.id_unitkerja = t.id_unitkerja AND t.tahun = ?
            WHERE u.user_wali IS NOT NULL AND u.user_wali != '' AND u.user_wali != '-'
            GROUP BY u.user_wali 
            ORDER BY u.user_wali ASC
        ", [$ta])->getResult();

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
	// --- FITUR MASTER TIM  ---
	public function master_tim()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$total_row      = $this->db->query("SELECT * FROM t_admin WHERE level = 'lo'")->getNumRows();
		$per_page       = 10;

		$awal   = $this->uri->segment(4);
		$awal   = (empty($awal) || $awal == 1) ? 0 : $awal;
		$akhir  = $per_page;

		$a['pagi']  = _page($total_row, $per_page, 4, base_url() . "admin/master_tim/p");

		$mau_ke         = $this->uri->segment(3);
		$idu            = $this->uri->segment(4);
		$cari           = $this->input->post('q');

		$idp            = $this->input->post('idp');
		$nama           = $this->input->post('nama');
		$nip            = $this->input->post('nip');
		$username       = $this->input->post('username');
		$password       = md5($this->input->post('password'));

		$level          = "lo";
		$unitkerja      = "bps";

		if ($mau_ke == "del") {
			$this->db->query("DELETE FROM t_admin WHERE id = ?", [$idu]);
			$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Data Tim berhasil dihapus</div>");
			return redirect()->to('admin/master_tim');
		} else if ($mau_ke == "cari") {

			$a['data']      = $this->db->query("SELECT * FROM t_admin WHERE level = 'lo' AND (nama LIKE ? OR username LIKE ?) ORDER BY username ASC", ['%' . $cari . '%', '%' . $cari . '%'])->getResult();
			$a['page']      = "l_master_tim";
		} else if ($mau_ke == "act_add") {
			$cek = $this->db->query("SELECT * FROM t_admin WHERE username=?", [$username])->getNumRows();

			if ($cek > 0) {
				$this->session->setFlashdata("k", "<div class=\"alert alert-danger\" id=\"alert\">Nama Tim (Username) sudah digunakan!</div>");
				return redirect()->to('admin/master_tim');
			} else {
				$this->db->query("INSERT INTO t_admin (username, password, nama, nip, level, id_unitkerja, email) VALUES (?, ?, ?, ?, ?, ?, ?)", [$username, $password, $nama, $nip, $level, $unitkerja, '-']);
				$this->session->setFlashdata("k", "<div class=\"alert alert-success\" id=\"alert\">Tim Baru berhasil ditambahkan</div>");
				return redirect()->to('admin/master_tim');
			}
		} else {

			$a['data']      = $this->db->query("SELECT * FROM t_admin WHERE level = 'lo' ORDER BY username ASC LIMIT ?, ?", [(int)$awal, (int)$akhir])->getResult();
			$a['page']      = "l_master_tim";
		}

		return view('admin/index', $a);
	}

	public function matching()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/matching");
		}
		$jenis_rekap = $this->input->post('jenis_rekap');
		if ($jenis_rekap == '0') {
			$data_byjk	= $this->db->query("SELECT t.id_unitkerja,u.unitkerja_ind,count(judul_ind) as jumlah_tabel,sum(case when t.is_confirm = 1 then 1 else 0 end ) as terentri FROM `t_list_tabel` t left join m_unitkerja u on  t.id_unitkerja=u.id_unitkerja group by id_unitkerja order by u.id_unitkerja ")->getResult();
			$a['page']		= "view_report";
		}

		$a['page']	= "matching";
		return view('admin/index', $a);
	}


	private function callApi2($url)
	{
		$token = 'wenFlEPchGCa7aIMTBoB5Lt0pN1fcsDq';

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
					$this->db->query(
						"INSERT INTO t_api_data (id_api, judul, tahun_data)
						 VALUES (?, ?, ?)
						 ON DUPLICATE KEY UPDATE
						 judul = VALUES(judul),
						 tahun_data = VALUES(tahun_data)",
						[$id, $judul, $tahun]
					);
				} catch (\Exception $e) {
					log_message('error', 'Gagal insert t_api_data: ' . $e->getMessage());
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

			// Insert atau update
			try {
				$this->db->query(
					"INSERT INTO t_api_data (id_api, judul, tahun_data)
					 VALUES (?, ?, ?)
					 ON DUPLICATE KEY UPDATE
					 judul = VALUES(judul),
					 tahun_data = VALUES(tahun_data)",
					[$id, $judul, $tahun]
				);
			} catch (\Exception $e) {
				log_message('error', 'Gagal insert t_api_data: ' . $e->getMessage());
				// Lanjut saja ke item berikutnya
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

	/*
	public function ambil_tahun_terakhir_dari_match()
{
    ini_set('max_execution_time', 0);
    set_time_limit(0);

    $list = $this->db
        ->select('id_api')
        ->get('t_tabel_match')
        ->getResultArray();

    $updated = 0;

    foreach ($list as $row) {

        $id_api = $row['id_api'];

        $detail = $this->callApi(
            "https://satudata-backup.jatengprov.go.id/v1/data/{$id_api}"
        );

        // âœ… STRUKTUR SESUAI POSTMAN
        if (!isset($detail['data']) || !is_array($detail['data'])) {
            continue;
        }

        $tahunList = array_column($detail['data'], 'tahun_data');

        if (empty($tahunList)) {
            continue;
        }

        $tahunTerakhir = max($tahunList);

        // INSERT / UPDATE
        $this->db->query(
            "INSERT INTO t_dataportal (id_portal, tahun_data, updated_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                tahun_data = VALUES(tahun_data),
                updated_at = VALUES(updated_at)",
            [$id_api, $tahunTerakhir, date('Y-m-d H:i:s')]
        );

        if ($this->db->affected_rows() > 0) {
            $updated++;
        }
    }

    echo json_encode([
        'status'  => true,
        'updated' => $updated
    ]);
}*/

	public function ambil_tahun_terakhir_dari_match()
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);

		$list = $this->db->table('t_tabel_match')
			->select('id_api')
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

			// default NULL
			$tahunTerakhir = null;

			$tahunList = array_column($detail['data'], 'tahun_data');
			if (!empty($tahunList)) {
				$tahunTerakhir = max($tahunList);
			}

			$this->db->query(
				"INSERT INTO t_dataportal (id_portal, tahun_data, updated_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                tahun_data = VALUES(tahun_data),
                updated_at = VALUES(updated_at)",
				[$id_api, $tahunTerakhir, date('Y-m-d H:i:s')]
			);

			if ($this->db->affectedRows() > 0) {
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
	public function view_portal_tabel()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$id_api = $this->request->getGet('id');
		if (empty($id_api)) {
			$a['page'] = "d_amain";
			$this->session->setFlashdata("k", '<div class="alert alert-danger" id="alert">ID Portal Data tidak ditemukan</div>');
			return view('admin/index', $a);
		}

		// Support multiple IDs (comma separated)
		$ids = explode(',', $id_api);
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
				$url = "https://satudata-backup.jatengprov.go.id/v1/data/{$id_p}?page={$page}&per-page=100&offset={$offset}&limit=100";
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
				$dda_info = $this->db->query("
                    SELECT t.id, t.judul_ind, t.judul_en, t.config_tabel, u.unitkerja_ind, u.unitkerja_en 
                    FROM t_list_tabel t 
                    LEFT JOIN m_unitkerja u ON t.id_unitkerja = u.id_unitkerja
                    WHERE t.link_tabel LIKE ? 
                    LIMIT 1
                ", ['%' . $id_p . '%'])->getRow();

				$final_res = $meta_info;
				$final_res['data']           = $all_rows;
				$final_res['res_id']         = $dda_info->id ?? 0;
				$final_res['dda_title']      = $dda_info->judul_ind ?? '';
				$final_res['dda_title_en']   = $dda_info->judul_en ?? '';
				$final_res['config_tabel']   = $dda_info->config_tabel ?? null;
				$final_res['unitkerja_ind']  = $dda_info->unitkerja_ind ?? '-';
				$final_res['unitkerja_en']   = $dda_info->unitkerja_en ?? '-';
				$final_res['id_api']         = $id_p;

				$all_results[] = $final_res;
			}
		}

		$a['api_results']  = $all_results;
		$a['api_errors']   = $api_error_msgs;
		$a['id_api']       = $id_api;
		$a['page']         = "v_portal_tabel";

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
		$filename = "template_bulk_portal.csv";

		// Add BOM for Excel UTF-8 support
		$csv  = "\xEF\xBB\xBF";
		$csv .= "Judul Tabel DDA (Indonesia);ID_Portal_Data\n";
		$csv .= "Contoh Nama Tabel Satu (ID Tunggal);0f918215-695c-4167-9e09-9d79e01f918d\n";
		$csv .= "Contoh Nama Tabel Multiple (Gunakan Koma);uuid-data-1,uuid-data-2,uuid-data-3\n";

		return $this->response->setBody($csv)
			->setHeader('Content-Type', 'text/csv')
			->setHeader('Content-Disposition', 'attachment; filename=' . $filename);
	}

	/**
	 * Preview Bulk Portal Update dari File CSV (Separator Titik Koma)
	 */
	public function preview_bulk_portal()
	{
		if ($this->session->get('admin_valid') == FALSE && $this->session->get('admin_id') == "") {
			return redirect()->to("admin/login");
		}

		$file = $this->request->getFile('file_mapping');
		if (!$file || !$file->isValid()) {
			return redirect()->back()->with('k', '<div class="alert alert-danger">File tidak valid atau tidak terbaca!</div>');
		}

		$handle = fopen($file->getTempName(), "r");

		// Skip header
		fgetcsv($handle, 2000, ";");

		$list_data = [];
		while (($row = fgetcsv($handle, 2000, ";")) !== FALSE) {
			$input_key = trim($row[0] ?? '');
			$ids       = trim($row[1] ?? '');

			if (empty($input_key)) continue;

			// Cek apakah input adalah ID (Angka) atau Judul
			// Flexible matching: Cek per ID atau per Judul (case insensitive)
			$check = $this->db->query("SELECT id, judul_ind FROM t_list_tabel WHERE id = ? OR LOWER(judul_ind) = LOWER(?)", [$input_key, $input_key])->getRow();

			$list_data[] = [
				'input_key' => $input_key,
				'judul'     => $check ? $check->judul_ind : $input_key,
				'id_tabel'  => $check ? $check->id : null,
				'ids'       => $ids,
				'exists'    => ($check ? true : false)
			];
		}
		fclose($handle);

		// Ambil list semua tabel untuk fallback dropdown di VIEW
		$a['all_tables']   = $this->db->query("SELECT id, judul_ind FROM t_list_tabel ORDER BY judul_ind ASC")->getResult();
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

		$selected_indices = $this->request->getPost('id_selected'); // Ini array index (0, 1, 2...)
		$id_tabels        = $this->request->getPost('id_tabel_final'); // Array [index => id_tabel]
		$portal_ids_list  = $this->request->getPost('portal_ids'); // Array [index => portal_ids]

		if (empty($selected_indices)) {
			return redirect()->to('admin/master_tabel')->with('k', '<div class="alert alert-danger">Tidak ada data yang dipilih untuk diupdate.</div>');
		}

		$count = 0;
		foreach ($selected_indices as $index) {
			$id_tabel   = $id_tabels[$index] ?? '';
			$portal_ids = $portal_ids_list[$index] ?? '';

			if (empty($id_tabel) || empty($portal_ids)) continue;

			$new_link = "index.php/admin/view_portal_tabel?id=" . $portal_ids;
			$this->db->query("UPDATE t_list_tabel SET link_tabel = ? WHERE id = ?", [$new_link, $id_tabel]);
			$count += $this->db->affectedRows();
		}

		return redirect()->to('admin/master_tabel')->with('k', '<div class="alert alert-success">Berhasil memperbarui ' . $count . ' tabel ke Portal Data.</div>');
	}

	public function simpan_config_tabel()
	{
		if ($this->request->isAJAX()) {
			$id = $this->request->getPost('id');
			$config = $this->request->getPost('config');

			if (!$id || !$config) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);
			}

			$update = $this->db->table('t_list_tabel')->where('id', $id)->update(['config_tabel' => $config]);

			if ($update) {
				return $this->response->setJSON(['status' => 'success', 'message' => 'Konfigurasi berhasil disimpan']);
			}
			return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal simpan database']);
		}
	}
}
