<?php

namespace App\Models;

use CodeIgniter\Model;


class M_inputdata extends Model{
    protected $table="t_data";
    
    function cek($id,$data){
        $this->db->where("id",$id);
        $this->db->where("data",$data);
        return $this->db->get("t_data");
    }
    
    function semua($limit=10,$offset=0,$order_column='',$order_type='asc'){
       // return $this->db->get("m_kab");
	    if(empty($order_column) || empty($order_type))
            $this->db->order_by($this->primary,'asc');
        else
            $this->db->order_by($order_column,$order_type);
        return $this->db->get($this->table,$limit,$offset);
    }
    
	function semuabyskpd($limit=10,$offset=0,$order_column='',$order_type='asc'){
       // return $this->db->get("m_kab");
	   $skpd=$this->session->get('level');
	    if(empty($order_column) || empty($order_type))
			{$this->db->where("skpd1",$skpd);
			$this->db->or_where("skpd2",$skpd);
            $this->db->order_by($this->primary,'asc');}
        else
			{$this->db->where("skpd1",$skpd);
			$this->db->or_where("skpd2",$skpd);
            $this->db->order_by($order_column,$order_type);}
        return $this->db->get($this->table,$limit,$offset);
    }
	
	function jumlah(){
        return $this->db->count_all($this->table);
    }
	
    function cekKode($kode){
        $this->db->where("kode_indikator",$kode);
        return $this->db->get("m_indikator");
    }
	
	function viewdata($kode){
        $this->db->select('d.data,d.id_kab,k.nama_kab,i.nama_indikator,t.tahun,d.kode_indikator,d.flag,d.id,d.username,d.level');
		$this->db->from('t_data as d');
		$this->db->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$this->db->join('m_tahun as t', 'd.tahun = t.id');
		$this->db->join('m_kab as k', 'd.id_kab = k.id_kab');	
		$this->db->where("d.kode_indikator",$kode);
        return $this->db->get();
    }
	
	
	function viewdataringkas($kode){
        $this->db->select('distinct(d.kode_indikator),d.data, i.nama_indikator,t.tahun,d.flag,d.id,d.username,d.level');
		$this->db->from('t_data as d');
		$this->db->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$this->db->join('m_tahun as t', 'd.tahun = t.id');
		$this->db->where("d.kode_indikator",$kode);
		$this->db->where("d.id_kab",'3301');
        return $this->db->get();
    }
	
	function viewsdata($kode,$level){
        $this->db->select('d.data,d.id_kab,,k.nama_kab,i.nama_indikator,t.tahun,d.kode_indikator,d.flag,d.id,d.username,d.level');
		$this->db->from('t_data as d');
		$this->db->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$this->db->join('m_tahun as t', 'd.tahun = t.id');
		$this->db->join('m_kab as k', 'd.id_kab = k.id_kab');	
		$this->db->where("d.kode_indikator",$kode);
		$this->db->where("d.level",$level);
        return $this->db->get();
    }
	
	function cekDataEdit($tahun, $kode_indikator){
        $this->db->select('d.data, i.nama_indikator,h.tahun,d.kode_indikator,d.kode_goal,d.kode_target,d.flag,t.nama_target, g.nama_goal,d.id');
		$this->db->from('t_data as d');
		$this->db->join('m_indikator as i', 'd.kode_indikator = i.kode_indikator');
		$this->db->join('m_tahun as h', 'd.tahun = h.id');
		$this->db->join('m_target as t', 'd.kode_target = t.kode_target');
		$this->db->join('m_goal as g', 'd.kode_goal = g.kode_goal');
		$this->db->where("h.tahun",$tahun);
		$this->db->where("d.kode_indikator",$kode_indikator);
		$this->db->where("d.id_kab",'3301');
        return $this->db->get();
    }
	
	function cekDataEditLengkap($tahun, $kode_indikator){
           return $this->db->query("select d.*,k.nama_kab from t_data as d inner join m_kab as k on d.id_kab=k.id_kab inner join m_tahun as t on d.tahun=t.id where d.kode_indikator='$kode_indikator' and t.tahun='$tahun'");
    }
	
	function cekData($tahun,$kode_goal,$kode_target,$kode_indikator){
        $this->db->where("tahun",$tahun);
		$this->db->where("kode_goal",$kode_goal);
		$this->db->where("kode_target",$kode_target);
		$this->db->where("kode_indikator",$kode_indikator);
		$this->db->where("id_kab",'3301');
        return $this->db->get("t_data");
    }
    
    function cekId($kode){
        //$this->db->where("kode_indikator",$kode);
		$this->db->select('i.*, t.nama_target, g.nama_goal');
		$this->db->from('m_indikator as i');
		$this->db->join('m_target as t', 'i.kode_target = t.kode_target');
		$this->db->join('m_goal as g', 'i.kode_goal = g.kode_goal');
		$this->db->where("i.kode_indikator",$kode);
		return $this->db->get();
        //return $this->db->get("m_indikator");
    }
    
    function update_record($id,$info){
        $this->db->where("kode_indikator",$id);
        $this->db->update("m_indikator",$info);
    }
    
	function updatedata($id,$info){
        $this->db->where("id",$id);
        $this->db->update("t_data",$info);
    }
	
    function inputdata($info){
        $this->db->insert("t_data",$info);
    }
    
    function hapus($tahunbeneran,$kode_indikator){
        $this->db->where("tahun",$tahunbeneran);
		$this->db->where("kode_indikator",$kode_indikator);
        $this->db->delete("t_data");
    }
	
	 function verifikasi($kode_indikator,$tahunbeneran,$info){
		$this->db->where("tahun",$tahunbeneran);
        $this->db->where("kode_indikator",$kode_indikator);
        $this->db->update("t_data",$info);
    }
	
	function getIndikator($kode){
		$this->db->select('kode_indikator');
		$this->db->from('t_data');
		$this->db->where("id",$kode);
		return $this->db->get()->row('kode_indikator');
	}
	
	function getTahun($idtahun)
	{
		$this->db->select('id');
		$this->db->from('m_tahun');
		$this->db->where("tahun",$idtahun);
		return $this->db->get()->row('id');
	}

}