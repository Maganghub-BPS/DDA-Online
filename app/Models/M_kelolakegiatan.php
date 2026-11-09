<?php

namespace App\Models;

use CodeIgniter\Model;


class M_kelolakegiatan extends Model{
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
           return $this->db->query("select d.*,k.nama_kab from t_data as d inner join m_kab as k on d.id_kab=k.id_kab inner join m_tahun as t on d.tahun=t.id where d.kode_indikator=? and t.tahun=?", [$kode_indikator, $tahun]);
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

    public function get_statistik_dashboard($th, $filter_unitkerja = null)
    {
        $thDda = (string)$th;
        $thData = ((int)$th) - 1;
        $thDataStr = (string)$thData;

        $whereUnit = "";
        $unitParams = [];
        if (!empty($filter_unitkerja)) {
            if (is_array($filter_unitkerja)) {
                $cleanUnits = array_values(array_filter($filter_unitkerja));
                if (!empty($cleanUnits)) {
                    $placeholders = implode(',', array_fill(0, count($cleanUnits), '?'));
                    $whereUnit = " AND mt.id_unitkerja IN ($placeholders) ";
                    $unitParams = $cleanUnits;
                } else {
                    $whereUnit = " AND 1=0 ";
                }
            } else {
                $whereUnit = " AND mt.id_unitkerja = ? ";
                $unitParams = [$filter_unitkerja];
            }
        }

        $sql = "
            SELECT 
                COUNT(mt.id) as total_tabel,
                SUM(CASE 
                    WHEN tt.is_confirm = '1' THEN 1 
                    ELSE 0 
                END) as sudah_acc,
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
                    ) THEN 1 
                    ELSE 0 
                END) as diisi,
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
                END) as diisi_waiting,
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
                END) as belum_diisi,
                SUM(CASE 
                    WHEN tt.is_confirm IS NULL OR tt.is_confirm != '1' THEN 1 
                    ELSE 0 
                END) as belum_acc
            FROM m_list_tabel mt
            LEFT JOIN t_status_tahunan tt ON tt.id_tabel = mt.id AND tt.tahun = ?
            LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
            LEFT JOIN (
                SELECT DISTINCT id_indikator 
                FROM t_nilai_dinamis 
                WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
            ) nd ON nd.id_indikator = mi.id
            LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
            WHERE mt.is_active = 1
            $whereUnit
        ";

        $baseParams = [
            $thDataStr, "%$thDataStr%",
            $thDataStr, "%$thDataStr%",
            $thDataStr, "%$thDataStr%",
            $thDda, $thData
        ];
        $params = array_merge($baseParams, $unitParams);

        return $this->db->query($sql, $params)->getRow();
    }

    public function get_statistik_per_opd($th, $filter_unitkerja = null)
    {
        $thDda = (string)$th;
        $thData = ((int)$th) - 1;
        $thDataStr = (string)$thData;

        $whereUnit = "";
        $unitParams = [];
        if (!empty($filter_unitkerja)) {
            if (is_array($filter_unitkerja)) {
                $cleanUnits = array_values(array_filter($filter_unitkerja));
                if (!empty($cleanUnits)) {
                    $placeholders = implode(',', array_fill(0, count($cleanUnits), '?'));
                    $whereUnit = " AND m.id_unitkerja IN ($placeholders) ";
                    $unitParams = $cleanUnits;
                } else {
                    $whereUnit = " AND 1=0 ";
                }
            } else {
                $whereUnit = " AND m.id_unitkerja = ? ";
                $unitParams = [$filter_unitkerja];
            }
        }

        $sql = "
            SELECT 
                m.id_unitkerja as nama_opd,
                COUNT(mt.id) as total_tabel,
                SUM(CASE 
                    WHEN tt.is_confirm = '1' THEN 1 
                    ELSE 0 
                END) as total_acc,
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
                END) as total_menunggu,
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
                END) as total_belum
            FROM m_list_tabel mt
            JOIN m_unitkerja m ON mt.id_unitkerja = m.id_unitkerja
            LEFT JOIN t_status_tahunan tt ON tt.id_tabel = mt.id AND tt.tahun = ?
            LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
            LEFT JOIN (
                SELECT DISTINCT id_indikator 
                FROM t_nilai_dinamis 
                WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
            ) nd ON nd.id_indikator = mi.id
            LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
            WHERE mt.is_active = 1
            $whereUnit
            GROUP BY m.id_unitkerja
            ORDER BY m.id_unitkerja ASC
        ";

        $baseParams = [
            $thDataStr, "%$thDataStr%",
            $thDataStr, "%$thDataStr%",
            $thDda, $thData
        ];
        $params = array_merge($baseParams, $unitParams);

        return $this->db->query($sql, $params)->getResult();
    }

    public function get_statistik_per_tim($th, $filter_teams = null)
    {
        $thDda = (string)$th;
        $thData = ((int)$th) - 1;
        $thDataStr = (string)$thData;

        $whereTim = "";
        $timParams = [];
        if (!empty($filter_teams)) {
            if (is_array($filter_teams)) {
                $cleanTeams = array_values(array_filter($filter_teams));
                if (!empty($cleanTeams)) {
                    $placeholders = implode(',', array_fill(0, count($cleanTeams), '?'));
                    $whereTim = " AND m.user_wali IN ($placeholders) ";
                    $timParams = $cleanTeams;
                } else {
                    $whereTim = " AND 1=0 ";
                }
            } else {
                $whereTim = " AND m.user_wali = ? ";
                $timParams = [$filter_teams];
            }
        }

        $sql = "
            SELECT 
                m.user_wali as nama_tim,
                COUNT(mt.id) as total_tabel,
                SUM(CASE 
                    WHEN tt.is_confirm = '1' THEN 1 
                    ELSE 0 
                END) as total_acc,
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
                END) as total_menunggu,
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
                END) as total_belum
            FROM m_unitkerja m
            JOIN m_list_tabel mt ON m.id_unitkerja = mt.id_unitkerja
            LEFT JOIN t_status_tahunan tt ON tt.id_tabel = mt.id AND tt.tahun = ?
            LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
            LEFT JOIN (
                SELECT DISTINCT id_indikator 
                FROM t_nilai_dinamis 
                WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
            ) nd ON nd.id_indikator = mi.id
            LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
            WHERE mt.is_active = 1
              AND m.user_wali IS NOT NULL AND m.user_wali != '' AND m.user_wali != '-'
            $whereTim
            GROUP BY m.user_wali
            ORDER BY m.user_wali ASC
        ";

        $baseParams = [
            $thDataStr, "%$thDataStr%",
            $thDataStr, "%$thDataStr%",
            $thDda, $thData
        ];
        $params = array_merge($baseParams, $timParams);

        return $this->db->query($sql, $params)->getResult();
    }

    public function get_progress($ta, $id_unitkerja = null)
    {
        $thDda = (string)$ta;
        $thData = ((int)$ta) - 1;
        $thDataStr = (string)$thData;
        $whereOpd = '';
        $params = [
            $thDataStr, "%$thDataStr%",
            $thDataStr, "%$thDataStr%",
            $thDataStr, "%$thDataStr%",
            $thDda, $thData
        ];
        if (!empty($id_unitkerja)) {
            $whereOpd = " AND mt.id_unitkerja = ? ";
            $params[] = $id_unitkerja;
        }

        $sql = "
            SELECT 
                COUNT(mt.id) as total_tabel,
                SUM(CASE 
                    WHEN tt.is_confirm = '1' THEN 1 
                    ELSE 0 
                END) as sudah_acc,
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
                    ) THEN 1 
                    ELSE 0 
                END) as diisi,
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
                END) as diisi_waiting,
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
                END) as belum_diisi
            FROM m_list_tabel mt
            LEFT JOIN t_status_tahunan tt ON tt.id_tabel = mt.id AND tt.tahun = ?
            LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = mt.id
            LEFT JOIN (
                SELECT DISTINCT id_indikator 
                FROM t_nilai_dinamis 
                WHERE tahun_data = ? AND (nilai IS NOT NULL OR (simbol IS NOT NULL AND simbol != ''))
            ) nd ON nd.id_indikator = mi.id
            LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = mt.id
            WHERE mt.is_active = 1
            $whereOpd
        ";

        return $this->db->query($sql, $params)->getRow();
    }
}