<?php

namespace App\Models;

use CodeIgniter\Model;

class M_frontend extends Model
{
    protected $table = 'm_list_tabel';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    /**
     * Base select untuk kartu & daftar tabel di Frontend Publik.
     * Entitas utama adalah m_list_tabel (1 baris = 1 tabel master unik),
     * di-join dengan status publikasi terbaru (atau tahun tertentu) dari t_status_tahunan.
     */
    private function getBaseTableSelect()
    {
        return "m_list_tabel.id,
                m_list_tabel.id as id_tabel,
                s.tahun,
                m_list_tabel.no_tabel,
                COALESCE(NULLIF(mi.tahun_tampil, ''), NULLIF(ka.tahun_tersedia, ''), CAST((s.tahun - 1) AS CHAR)) as periode_id,
                COALESCE(ka.link_api, '') as link_tabel,
                ka.config_tabel,
                s.is_confirm,
                s.is_publish,
                s.published_at,
                (CASE WHEN m_list_tabel.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL THEN 1 ELSE 0 END) as is_dynamic,
                m_list_tabel.id_unitkerja,
                m_list_tabel.kondef,
                m_list_tabel.jenis_sumber,
                (CASE 
                    WHEN m_list_tabel.judul_ind LIKE '%20%' THEN m_list_tabel.judul_ind
                    ELSE CONCAT(m_list_tabel.judul_ind, ', ', COALESCE(NULLIF(mi.tahun_tampil, ''), NULLIF(ka.tahun_tersedia, ''), CAST((s.tahun - 1) AS CHAR)))
                END) as judul_ind,
                (CASE 
                    WHEN m_list_tabel.judul_en LIKE '%20%' THEN m_list_tabel.judul_en
                    ELSE CONCAT(m_list_tabel.judul_en, ', ', COALESCE(NULLIF(mi.tahun_tampil, ''), NULLIF(ka.tahun_tersedia, ''), CAST((s.tahun - 1) AS CHAR)))
                END) as judul_en,
                m_unitkerja.unitkerja_ind,
                m_unitkerja.unitkerja_en";
    }

    /**
     * Subquery status publikasi per tabel:
     * Jika $tahun diberikan, ambil status pada tahun tersebut.
     * Jika $tahun kosong, ambil baris status publikasi dari tahun DDA terbaru yang is_publish = 1.
     */
    private function applyPublishedStatusJoin($builder, $tahun = null)
    {
        if (!empty($tahun)) {
            $tahunEsc = $this->db->escape((int)$tahun);
            $builder->join(
                "t_status_tahunan s",
                "s.id_tabel = m_list_tabel.id AND s.tahun = {$tahunEsc} AND s.is_publish = 1",
                "inner"
            );
        } else {
            $subSql = "(SELECT s1.* FROM t_status_tahunan s1
                        INNER JOIN (
                            SELECT id_tabel, MAX(tahun) AS max_tahun
                            FROM t_status_tahunan
                            WHERE is_publish = 1
                            GROUP BY id_tabel
                        ) s2 ON s1.id_tabel = s2.id_tabel AND s1.tahun = s2.max_tahun
                        WHERE s1.is_publish = 1) s";
            $builder->join($subSql, 's.id_tabel = m_list_tabel.id', 'inner');
        }

        $builder->join('m_unitkerja', 'm_list_tabel.id_unitkerja = m_unitkerja.id_unitkerja', 'left')
                ->join('m_konfigurasi_api ka', 'ka.id_tabel = m_list_tabel.id', 'left')
                ->join('m_indikator_tabel mi', 'mi.id_tabel = m_list_tabel.id', 'left')
                ->where('m_list_tabel.is_active', 1);

        return $builder;
    }

    public function getRecentTables($limit = 5, $tahun = null)
    {
        $builder = $this->db->table('m_list_tabel')->select($this->getBaseTableSelect());
        $this->applyPublishedStatusJoin($builder, $tahun);

        return $builder->orderBy('s.published_at', 'DESC')
                       ->orderBy('s.tahun', 'DESC')
                       ->orderBy('m_list_tabel.id', 'DESC')
                       ->limit($limit)
                       ->get()
                       ->getResult();
    }

    public function getTopOpdList($limit = 8, $tahun = null)
    {
        $builder = $this->db->table('m_list_tabel')
                        ->select('m_unitkerja.id_unitkerja as id, m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, m_unitkerja.unitkerja_en, COUNT(DISTINCT m_list_tabel.id) as total_tabel');
        $this->applyPublishedStatusJoin($builder, $tahun);

        return $builder->where('m_unitkerja.id_unitkerja IS NOT NULL')
                       ->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, m_unitkerja.unitkerja_en')
                       ->orderBy('total_tabel', 'DESC')
                       ->limit($limit)
                       ->get()
                       ->getResult();
    }

    public function getPortalStats($tahun = null)
    {
        $db = \Config\Database::connect();

        $tabelBuilder = $db->table('m_list_tabel')->select('COUNT(DISTINCT m_list_tabel.id) as total');
        $this->applyPublishedStatusJoin($tabelBuilder, $tahun);
        $rowTabel = $tabelBuilder->get()->getRow();
        $totalTabel = $rowTabel ? (int)$rowTabel->total : 0;

        $opdBuilder = $db->table('m_list_tabel')->select('COUNT(DISTINCT m_list_tabel.id_unitkerja) as total_opd');
        $this->applyPublishedStatusJoin($opdBuilder, $tahun);
        $opdRow = $opdBuilder->get()->getRow();
        $totalOpd = $opdRow ? (int)$opdRow->total_opd : 0;

        $tahunRow = $db->table('t_status_tahunan')
                       ->select('MAX(tahun) as max_tahun, MIN(tahun) as min_tahun')
                       ->where('is_publish', 1)
                       ->get()
                       ->getRow();

        return (object)[
            'total_tabel' => $totalTabel,
            'total_opd'   => $totalOpd,
            'max_tahun'   => $tahunRow->max_tahun ?? date('Y'),
            'min_tahun'   => $tahunRow->min_tahun ?? date('Y')
        ];
    }

    public function getAvailableYears()
    {
        return $this->db->table('t_status_tahunan')
                        ->select('DISTINCT(tahun) as tahun')
                        ->where('is_publish', 1)
                        ->orderBy('tahun', 'DESC')
                        ->get()
                        ->getResult();
    }

    public function getUniqueUnits($tahun = null)
    {
        $builder = $this->db->table('m_list_tabel')
                        ->select('DISTINCT(m_unitkerja.unitkerja_ind) as unitkerja_ind, m_unitkerja.unitkerja_en, m_unitkerja.id_unitkerja as id, m_unitkerja.id_unitkerja');
        $this->applyPublishedStatusJoin($builder, $tahun);

        return $builder->where('m_unitkerja.unitkerja_ind IS NOT NULL')
                       ->orderBy('m_unitkerja.unitkerja_ind', 'ASC')
                       ->get()
                       ->getResult();
    }

    public function searchTables($keyword = '', $tahun = '', $opd = '', $bab = '')
    {
        $builder = $this->db->table('m_list_tabel')->select($this->getBaseTableSelect());
        $this->applyPublishedStatusJoin($builder, $tahun);

        if (!empty($keyword)) {
            $builder->groupStart()
                    ->like('m_list_tabel.judul_ind', $keyword)
                    ->orLike('m_list_tabel.judul_en', $keyword)
                    ->orLike('m_list_tabel.no_tabel', $keyword)
                    ->orLike('m_unitkerja.unitkerja_ind', $keyword)
                    ->groupEnd();
        }

        if (!empty($opd)) {
            $builder->where('m_unitkerja.unitkerja_ind', $opd);
        }

        if (!empty($bab)) {
            $builder->groupStart()
                    ->like('m_list_tabel.no_tabel', $bab . '.', 'after')
                    ->orWhere('m_list_tabel.no_tabel', $bab)
                    ->groupEnd();
        }

        return $builder->orderBy('s.tahun', 'DESC')
                       ->orderBy('CAST(SUBSTRING_INDEX(m_list_tabel.no_tabel, ".", 1) AS UNSIGNED)', 'ASC', false)
                       ->orderBy('CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(m_list_tabel.no_tabel, ".", 2), ".", -1) AS UNSIGNED)', 'ASC', false)
                       ->orderBy('CAST(SUBSTRING_INDEX(m_list_tabel.no_tabel, ".", -1) AS UNSIGNED)', 'ASC', false)
                       ->get()
                       ->getResult();
    }

    public function getInstansiList($tahun = null)
    {
        $builder = $this->db->table('m_list_tabel')
                        ->select('m_unitkerja.id_unitkerja as id, m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, m_unitkerja.unitkerja_en, COUNT(DISTINCT m_list_tabel.id) as total_tabel');
        $this->applyPublishedStatusJoin($builder, $tahun);

        return $builder->where('m_unitkerja.id_unitkerja IS NOT NULL')
                       ->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, m_unitkerja.unitkerja_en')
                       ->orderBy('m_unitkerja.unitkerja_ind', 'ASC')
                       ->get()
                       ->getResult();
    }

    private function buildMetadataRow($table_id)
    {
        $table_id = (int)$table_id;
        return $this->db->table('m_list_tabel')
                        ->select("
                            m_list_tabel.id,
                            m_list_tabel.id as id_tabel,
                            COALESCE(s.tahun, " . (int)date('Y') . ") as tahun,
                            m_list_tabel.no_tabel,
                            COALESCE(NULLIF(mi.tahun_tampil, ''), NULLIF(ka.tahun_tersedia, ''), CAST((COALESCE(s.tahun, " . (int)date('Y') . ") - 1) AS CHAR)) as periode_id,
                            COALESCE(ka.link_api, '') as link_tabel,
                            ka.config_tabel,
                            COALESCE(s.is_confirm, '0') as is_confirm,
                            COALESCE(s.is_publish, 0) as is_publish,
                            s.published_at,
                            (CASE WHEN m_list_tabel.jenis_sumber = 'dinamis' OR mi.id IS NOT NULL THEN 1 ELSE 0 END) as is_dynamic,
                            m_list_tabel.id_unitkerja,
                            m_list_tabel.kondef,
                            m_list_tabel.jenis_sumber,
                            (CASE 
                                WHEN m_list_tabel.judul_ind LIKE '%20%' THEN m_list_tabel.judul_ind
                                ELSE CONCAT(m_list_tabel.judul_ind, ', ', COALESCE(NULLIF(mi.tahun_tampil, ''), NULLIF(ka.tahun_tersedia, ''), CAST((COALESCE(s.tahun, " . (int)date('Y') . ") - 1) AS CHAR)))
                            END) as judul_ind,
                            (CASE 
                                WHEN m_list_tabel.judul_en LIKE '%20%' THEN m_list_tabel.judul_en
                                ELSE CONCAT(m_list_tabel.judul_en, ', ', COALESCE(NULLIF(mi.tahun_tampil, ''), NULLIF(ka.tahun_tersedia, ''), CAST((COALESCE(s.tahun, " . (int)date('Y') . ") - 1) AS CHAR)))
                            END) as judul_en,
                            m_unitkerja.unitkerja_ind,
                            m_unitkerja.unitkerja_en
                        ")
                        ->join('m_unitkerja', 'm_list_tabel.id_unitkerja = m_unitkerja.id_unitkerja', 'left')
                        ->join('m_konfigurasi_api ka', 'ka.id_tabel = m_list_tabel.id', 'left')
                        ->join('m_indikator_tabel mi', 'mi.id_tabel = m_list_tabel.id', 'left')
                        ->join(
                            '(SELECT s1.* FROM t_status_tahunan s1 INNER JOIN (SELECT id_tabel, MAX(tahun) as max_tahun FROM t_status_tahunan GROUP BY id_tabel) s2 ON s1.id_tabel = s2.id_tabel AND s1.tahun = s2.max_tahun) s',
                            's.id_tabel = m_list_tabel.id',
                            'left'
                        )
                        ->where('m_list_tabel.id', $table_id)
                        ->get()
                        ->getRow();
    }

    public function getTableMetadata($table_id)
    {
        $row = $this->buildMetadataRow($table_id);
        if ($row && !empty($row->link_tabel) && strpos($row->link_tabel, 'bps.go.id') !== false) {
            return $row;
        }
        return null;
    }

    public function getSheetMetadata($table_id)
    {
        $row = $this->buildMetadataRow($table_id);
        if ($row && !empty($row->link_tabel) && strpos($row->link_tabel, 'docs.google.com/spreadsheets') !== false) {
            return $row;
        }
        return null;
    }

    public function getDynamicTableMetadata($table_id)
    {
        $row = $this->buildMetadataRow($table_id);
        if (!$row) {
            return null;
        }
        if ((int)$row->is_dynamic === 1 || ($row->jenis_sumber ?? '') === 'dinamis') {
            return $row;
        }
        return null;
    }
}
