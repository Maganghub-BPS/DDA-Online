<?php

namespace App\Models;

use CodeIgniter\Model;


class Web_model extends Model {

	function getAll($tabel) {
		$q = $this->db->query("SELECT * FROM $tabel");
		return $q->getResult();
	}
	
	function getSpesific($tabel, $where) {
		$q = $this->db->query("SELECT * FROM $tabel $where");
		return $q->getResult();	
	}
	
	function getDataByID($tabel, $kunci, $data) {
		$q = $this->db->query("SELECT * FROM $tabel WHERE $kunci='$data'");
		return $q->getRow();	
	}
	
	function delData($tabel, $field_mana, $id) {
		$q = $this->db->query("DELETE FROM $tabel WHERE $field_mana = '$id'");
		return $q;
	}
	
	function getValueOneField($field, $tabel, $kunci, $data) {
		$q = $this->db->query("SELECT $field FROM $tabel WHERE $kunci='$data'");
		return $q->getRow();	
	}
	
	function EDIT($q, $id, $tabel, $data) { return $this->db->table($tabel)->where($q, $id)->update($data); }
	function ADD($tabel, $data) { return $this->db->table($tabel)->insert($data); }
	
	//qhususon...
	
	//berita
	function getBeritaAll() {
		$q = $this->db->query("SELECT * FROM berita WHERE kategori = '1'");
		return $q->getResult();
	}
	function getBerita($id) {
		$q = $this->db->query("SELECT * FROM berita WHERE kategori = '1' AND idBerita = '$id'");
		return $q->getRow();
	}
	function addBerita($data) { return $this->db->table('berita')->insert($data); }
	function editBerita($id, $data) { return $this->db->table('berita')->where('idBerita', $id)->update($data); }
	function delBerita($id) {
		$q = $this->db->query("DELETE FROM berita WHERE idBerita = '$id'");
		return $q;
	}
	function pubBerita($id) {
		$q = $this->db->query("UPDATE berita SET publish = '1' WHERE idBerita = '$id'");
		return $q;
	}
	function unPubBerita($id) {
		$q = $this->db->query("UPDATE berita SET publish = '0' WHERE idBerita = '$id'");
		return $q;
	}	
	
	//pengumuman	

	function getPengumumanAll() {
		$q = $this->db->query("SELECT * FROM berita WHERE kategori = '2'");
		return $q->getResult();
	}
	function getPengumuman($id) {
		$q = $this->db->query("SELECT * FROM berita WHERE kategori = '2' AND idBerita = '$id'");
		return $q->getRow();
	}
	function addPengumuman($data) { return $this->db->table('berita')->insert($data); }
	function editPengumuman($id, $data) { return $this->db->table('berita')->where('idBerita', $id)->update($data); }
	
	
	
	function getFieldTable($tabel, $field, $id, $id_value) {
		$q = $this->db->query("SELECT $field FROM $tabel WHERE $id = $id_value");
		return $q->getRow();
	}
	
	
	//Profil
	function addProfil($data) { return $this->db->table('halaman')->insert($data); }
	function editProfil($id, $data) { return $this->db->table('halaman')->where('id', $id)->update($data); }
	
	//Galeri 
	function getKategoriGaleri() {
		$q 	= $this->db->query("select * from galeriKategori");
		return $q->getResult();
	}
	function addAlbum($data) { return $this->db->table('galerikategori')->insert($data); }
	function getFileFoto($idAlbum) {
		$q 	= $this->db->query("select file from galeri where kategori = '".$idAlbum."' ");
		return $q->getResult();
	}
	function editNamaAlbum($id, $data) { return $this->db->table('galerikategori')->where('idKategori', $id)->update($data); }
	function uploadFoto($data) { return $this->db->table('galeri')->insert($data); }
	
		//qhususon...
	
	public function validate(){
        // grab user input
        $username = service('request')->getPost('username');
        $password = service('request')->getPost('password');
         
        // Prep the query
        $query = $this->db->table('user')->where('u', $username)->where('p', $password)->get();
        // Let's check if there are any results
        if($query->getNumRows() == 1)
        {
            // If there is a user, then create session data
            $row = $query->getRow();
            $data = array(
                    'user' => $row->u,
                    'pass' => $row->p,
                    'name' => $row->nama,
                    'level' => $row->hakAkses,
					'validated' => true
                    );
            session()->set($data);
            return true;
        }
        // If the previous process did not validate
        // then return false.
        return false;
    }

	
}
?>
