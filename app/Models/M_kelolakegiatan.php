<?php

namespace App\Models;

use CodeIgniter\Model;


class m_kelolakegiatan extends Model{
    protected $table="t_data";
    
    function cek($id,$data){
        return $this->db->table("t_data")
                        ->where("id",$id)
                        ->where("data",$data)
                        ->get();
    }
    
    function semua($limit=10,$offset=0,$order_column='',$order_type='asc'){
        $builder = $this->builder();
	    if(empty($order_column) || empty($order_type))
            $builder->orderBy($this->primaryKey,'asc');
        else
            $builder->orderBy($order_column,$order_type);
        return $builder->get($limit,$offset);
    }
    
	function semuabyskpd($limit=10,$offset=0,$order_column='',$order_type='asc'){
	   $skpd = session()->get('level');
       $builder = $this->builder();
	    if(empty($order_column) || empty($order_type))
			{$builder->where("skpd1",$skpd);
			$builder->orWhere("skpd2",$skpd);
            $builder->orderBy($this->primaryKey,'asc');}
        else
			{$builder->where("skpd1",$skpd);
			$builder->orWhere("skpd2",$skpd);
            $builder->orderBy($order_column,$order_type);}
        return $builder->get($limit,$offset);
    }
	
	function jumlah(){
        return $this->db->table($this->table)->countAllResults();
    }
	
    function cekKode($kode){
        return $this->db->table("m_indikator")
                        ->where("kode_indikator",$kode)
                        ->get();
    }
	
	function viewdata($kode){
        $builder = $this->db->table('t_data as d');
        $builder->select('d.data,d.id_kab,k.nama_kab,i.nama_indikator,t.tahun,d.kode_indikator,d.flag,d.id,d.username,d.level');
		$builder->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$builder->join('m_tahun as t', 'd.tahun = t.id');
		$builder->join('m_kab as k', 'd.id_kab = k.id_kab');	
		$builder->where("d.kode_indikator",$kode);
        return $builder->get();
    }
	
	
	function viewdataringkas($kode){
        $builder = $this->db->table('t_data as d');
        $builder->select('distinct(d.kode_indikator),d.data, i.nama_indikator,t.tahun,d.flag,d.id,d.username,d.level');
		$builder->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$builder->join('m_tahun as t', 'd.tahun = t.id');
		$builder->where("d.kode_indikator",$kode);
		$builder->where("d.id_kab",'3301');
        return $builder->get();
    }
	
	function viewsdata($kode,$level){
        $builder = $this->db->table('t_data as d');
        $builder->select('d.data,d.id_kab,k.nama_kab,i.nama_indikator,t.tahun,d.kode_indikator,d.flag,d.id,d.username,d.level');
		$builder->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$builder->join('m_tahun as t', 'd.tahun = t.id');
		$builder->join('m_kab as k', 'd.id_kab = k.id_kab');	
		$builder->where("d.kode_indikator",$kode);
		$builder->where("d.level",$level);
        return $builder->get();
    }
	
	function cekDataEdit($tahun, $kode_indikator){
        $builder = $this->db->table('t_data as d');
        $builder->select('d.data, i.nama_indikator,h.tahun,d.kode_indikator,d.kode_goal,d.kode_target,d.flag,t.nama_target, g.nama_goal,d.id');
		$builder->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$builder->join('m_tahun as h', 'd.tahun = h.id');
		$builder->join('m_target as t', 'd.kode_target = t.kode_target');
		$builder->join('m_goal as g', 'd.kode_goal = g.kode_goal');
		$builder->where("h.tahun",$tahun);
		$builder->where("d.kode_indikator",$kode_indikator);
		$builder->where("d.id_kab",'3301');
        return $builder->get();
    }
	
	function cekDataEditLengkap($tahun, $kode_indikator){
           return $this->db->query("select d.*,k.nama_kab from t_data as d inner join m_kab as k on d.id_kab=k.id_kab inner join m_tahun as t on d.tahun=t.id where d.kode_indikator='$kode_indikator' and t.tahun='$tahun'");
    }
	
	function cekData($tahun,$kode_goal,$kode_target,$kode_indikator){
        return $this->db->table('t_data')
                        ->where("tahun",$tahun)
		                ->where("kode_goal",$kode_goal)
		                ->where("kode_target",$kode_target)
		                ->where("kode_indikator",$kode_indikator)
		                ->where("id_kab",'3301')
                        ->get();
    }
    
    function cekId($kode){
        $builder = $this->db->table('m_indikator as i');
		$builder->select('i.*, t.nama_target, g.nama_goal');
		$builder->join('m_target as t', 'i.kode_target = t.kode_target');
		$builder->join('m_goal as g', 'i.kode_goal = g.kode_goal');
		$builder->where("i.kode_indikator",$kode);
		return $builder->get();
    }
    
    function update_record($id,$info){
        $this->db->table("m_indikator")
                 ->where("kode_indikator",$id)
                 ->update($info);
    }
    
	function updatedata($id,$info){
        $this->db->table("t_data")
                 ->where("id",$id)
                 ->update($info);
    }
	
    function inputdata($info){
        $this->db->table("t_data")->insert($info);
    }
    
    function hapus($tahunbeneran,$kode_indikator){
        $this->db->table("t_data")
                 ->where("tahun",$tahunbeneran)
		         ->where("kode_indikator",$kode_indikator)
                 ->delete();
    }
	
	 function verifikasi($kode_indikator,$tahunbeneran,$info){
		$this->db->table("t_data")
                 ->where("tahun",$tahunbeneran)
                 ->where("kode_indikator",$kode_indikator)
                 ->update($info);
    }
	
	function getIndikator($kode){
		$row = $this->db->table('t_data')
                        ->select('kode_indikator')
		                ->where("id",$kode)
                        ->get()
                        ->getRow();
        return $row ? $row->kode_indikator : null;
	}
	
	function getTahun($idtahun)
	{
		$row = $this->db->table('m_tahun')
                        ->select('id')
		                ->where("tahun",$idtahun)
                        ->get()
                        ->getRow();
        return $row ? $row->id : null;
	}

    function get_statistik_dashboard($th){
        $sql = "SELECT 
                    SUM(CASE WHEN is_periksa = 1 OR link_tabel LIKE '%portal%' OR link_tabel LIKE '%satudata%' OR is_confirm = 1 THEN 1 ELSE 0 END) as diisi,
                    SUM(CASE WHEN (is_periksa = 0 AND link_tabel NOT LIKE '%portal%' AND link_tabel NOT LIKE '%satudata%') AND is_confirm != 1 THEN 1 ELSE 0 END) as belum_diisi,
                    SUM(CASE WHEN is_confirm = 1 THEN 1 ELSE 0 END) as sudah_acc,
                    SUM(CASE WHEN is_confirm != 1 THEN 1 ELSE 0 END) as belum_acc
                FROM t_list_tabel 
                WHERE tahun = '$th'"; 

        return $this->db->query($sql)->getRow();
    }

    
    function get_statistik_per_opd($th){
        $sql = "SELECT 
                    m.id_unitkerja as nama_opd, 
                    
                    -- HIJAU: Diverifikasi
                    SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as total_acc,
                    -- KUNING: Sudah Diisi (Menunggu Verifikasi)
                    SUM(CASE WHEN (t.is_periksa = 1 OR t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%') AND t.is_confirm != 1 THEN 1 ELSE 0 END) as total_menunggu,
                    -- MERAH: Belum Diisi
                    SUM(CASE WHEN (t.is_periksa = 0 AND t.link_tabel NOT LIKE '%portal%' AND t.link_tabel NOT LIKE '%satudata%') AND t.is_confirm != 1 THEN 1 ELSE 0 END) as total_belum
                    
                FROM t_list_tabel t  
                JOIN m_unitkerja m ON t.id_unitkerja = m.id_unitkerja 
                WHERE t.tahun = '$th' 
                GROUP BY m.id_unitkerja
                ORDER BY m.id_unitkerja ASC"; 
        
        return $this->db->query($sql)->getResult();
    }

    function get_statistik_per_tim($th){
        $sql = "SELECT 
                    m.user_wali as nama_tim,
                    -- 1. HIJAU: Diverifikasi
                    SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as total_acc,
                    -- 2. KUNING: Sudah Diisi (Menunggu Verifikasi)
                    SUM(CASE WHEN (t.is_periksa = 1 OR t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%') AND t.is_confirm != 1 THEN 1 ELSE 0 END) as total_menunggu,
                    -- 3. MERAH: Belum Diisi
                    SUM(CASE WHEN (t.is_periksa = 0 AND t.link_tabel NOT LIKE '%portal%' AND t.link_tabel NOT LIKE '%satudata%') AND t.is_confirm != 1 AND t.id IS NOT NULL THEN 1 ELSE 0 END) as total_belum
                    
                FROM m_unitkerja m
                LEFT JOIN t_list_tabel t ON m.id_unitkerja = t.id_unitkerja AND t.tahun = '$th'
                WHERE m.user_wali IS NOT NULL AND m.user_wali != '' AND m.user_wali != '-'
                GROUP BY m.user_wali
                ORDER BY m.user_wali ASC";
        
        return $this->db->query($sql)->getResult();
    }
}