<?php

namespace App\Models;

use CodeIgniter\Model;

class M_frontend extends Model
{
    protected $table = 't_tahun_tabel';
    protected $primaryKey = 'id';

    /**
     * Helper untuk ekspresi SELECT standar tabel DDA
     * Memformat nomor tabel, judul, dan penggantian placeholder periode [PERIODE]
     */
    public function getBaseTableSelect(): string
    {
        return "
            t.id,
            t.id_tabel,
            t.tahun,
            t.no_tabel,
            t.periode_id,
            t.periode_en,
            t.link_tabel,
            t.link_sebelumnya,
            t.config_tabel,
            t.is_confirm,
            t.is_publish,
            m.id_unitkerja,
            m.kondef,
            CONCAT(
                CASE 
                    WHEN t.no_tabel IS NOT NULL AND TRIM(t.no_tabel) != '' 
                    THEN CONCAT('Tabel ', TRIM(t.no_tabel), ' ')
                    ELSE '' 
                END,
                CASE 
                    WHEN m.judul_ind LIKE '%[PERIODE]%' 
                    THEN REPLACE(m.judul_ind, '[PERIODE]', COALESCE(TRIM(t.periode_id), ''))
                    ELSE CONCAT(
                        m.judul_ind,
                        CASE 
                            WHEN t.periode_id IS NOT NULL AND TRIM(t.periode_id) != '' 
                            THEN CONCAT(', ', TRIM(t.periode_id))
                            ELSE '' 
                        END
                    )
                END
            ) AS judul_ind,
            CASE 
                WHEN m.judul_en IS NOT NULL AND TRIM(m.judul_en) != '' 
                THEN CONCAT(
                    CASE 
                        WHEN t.no_tabel IS NOT NULL AND TRIM(t.no_tabel) != '' 
                        THEN CONCAT('Table ', TRIM(t.no_tabel), ' ')
                        ELSE '' 
                    END,
                    CASE 
                        WHEN m.judul_en LIKE '%[PERIODE]%' 
                        THEN REPLACE(m.judul_en, '[PERIODE]', COALESCE(TRIM(t.periode_en), COALESCE(TRIM(t.periode_id), '')))
                        ELSE CONCAT(
                            TRIM(m.judul_en),
                            CASE 
                                WHEN t.periode_en IS NOT NULL AND TRIM(t.periode_en) != '' 
                                THEN CONCAT(', ', TRIM(t.periode_en))
                                WHEN t.periode_id IS NOT NULL AND TRIM(t.periode_id) != ''
                                THEN CONCAT(', ', TRIM(t.periode_id))
                                ELSE '' 
                            END
                        )
                    END
                )
                ELSE '' 
            END AS judul_en,
            m.judul_ind AS raw_judul_ind,
            m_unitkerja.unitkerja_ind,
            m_unitkerja.unitkerja_en
        ";
    }

    /**
     * Ambil tabel-tabel terbaru untuk section carousel Beranda
     */
    public function getRecentTables(int $limit = 15): array
    {
        return $this->db->table('t_tahun_tabel t')
            ->select($this->getBaseTableSelect(), false)
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left')
            ->where('t.is_publish', 1)
            ->orderBy('t.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    /**
     * Ambil daftar OPD teratas beserta jumlah tabel untuk Beranda
     */
    public function getTopOpdList(): array
    {
        return $this->db->table('t_tahun_tabel t')
            ->select('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, COUNT(t.id) as total_tabel')
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left')
            ->where('m_unitkerja.unitkerja_ind IS NOT NULL')
            ->where('t.is_publish', 1)
            ->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind')
            ->orderBy('m_unitkerja.unitkerja_ind', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Ambil statistik ringkasan portal (total tabel dan total OPD)
     */
    public function getPortalStats(): array
    {
        $total_tabel = $this->db->table('t_tahun_tabel t')
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->where('t.is_publish', 1)
            ->countAllResults();
        $total_opd = $this->db->table('m_unitkerja')->countAllResults();

        return [
            'total_tabel' => $total_tabel,
            'total_opd' => $total_opd
        ];
    }

    /**
     * Ambil unit kerja unik untuk dropdown filter sidebar
     */
    public function getUniqueUnits(): array
    {
        return $this->db->table('t_tahun_tabel t')
            ->select('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, COUNT(t.id) as total')
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left')
            ->where('m_unitkerja.unitkerja_ind IS NOT NULL')
            ->where('t.is_publish', 1)
            ->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind')
            ->orderBy('m_unitkerja.unitkerja_ind', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Ambil daftar tahun unik yang tersedia di t_tahun_tabel untuk filter dropdown
     */
    public function getAvailableYears(): array
    {
        $rows = $this->db->table('t_tahun_tabel')
            ->select('DISTINCT(tahun) as tahun')
            ->where('tahun IS NOT NULL')
            ->where("tahun != ''")
            ->orderBy('tahun', 'DESC')
            ->get()
            ->getResultArray();

        return array_column($rows, 'tahun');
    }

    /**
     * Pencarian tabel dengan filter keyword, unit, nama OPD, dan tahun beserta paginasi

     */
    public function searchTables(array $filters, int $page = 1, int $perPage = 12): array
    {
        $builder = $this->db->table('t_tahun_tabel t')
            ->select($this->getBaseTableSelect(), false)
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left')
            ->where('t.is_publish', 1);

        $q = $filters['q'] ?? '';
        $unit = $filters['unit'] ?? '';
        $opd_search = $filters['opd_search'] ?? '';
        $tahun = $filters['tahun'] ?? '';

        if (!empty($q)) {
            $q_clean = trim(preg_replace('/\s+/', ' ', $q));
            $q_normalized = preg_replace('/^(nomor|no\.?|tabel)\s+/i', '', $q_clean);
            $tokens = explode(' ', $q_normalized);

            $builder->groupStart();
            foreach ($tokens as $token) {
                $token = trim($token);
                if (!empty($token)) {
                    $builder->groupStart();
                    $builder->like('m.judul_ind', $token);
                    $builder->orLike('m.judul_en', $token);
                    $builder->orLike('t.no_tabel', $token);
                    $builder->orLike('t.periode_id', $token);
                    $builder->orLike('t.periode_en', $token);
                    $builder->groupEnd();
                }
            }
            $builder->groupEnd();
        }

        if (!empty($unit)) {
            $builder->where('m.id_unitkerja', $unit);
        }

        if (!empty($opd_search)) {
            $builder->like('m_unitkerja.unitkerja_ind', $opd_search);
        }

        if (!empty($tahun)) {
            $builder->where('t.tahun', $tahun);
        }

        $builder->orderBy('t.id', 'DESC');

        // Hitung total sebelum limit
        $total = $builder->countAllResults(false);
        $builder->limit($perPage, ($page - 1) * $perPage);
        $results = $builder->get()->getResult();

        return [
            'results' => $results,
            'total' => $total
        ];
    }

    /**
     * Dapatkan mapping id_api ke id_api_new dari t_tabel_match
     */
    public function getMatchMapping(array $ids): array
    {
        return $this->db->table('t_tabel_match')
            ->select('id_api, id_api_new')
            ->whereIn('id_api', $ids)
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil metadata tabel spesifik untuk view_tabel
     */
    public function getTableMetadata(?int $table_id, string $id_p)
    {
        $select_sql = $this->getBaseTableSelect();
        $dda_info = null;

        if (!empty($table_id)) {
            $dda_info = $this->db->query("
                SELECT 
                    {$select_sql}
                FROM t_tahun_tabel t 
                JOIN m_list_tabel m ON t.id_tabel = m.id
                LEFT JOIN m_unitkerja ON m.id_unitkerja = m_unitkerja.id_unitkerja
                WHERE t.id = ?
                LIMIT 1
            ", [$table_id])->getRow();
        }

        if (!$dda_info) {
            $dda_info = $this->db->query("
                SELECT 
                    {$select_sql}
                FROM t_tahun_tabel t 
                JOIN m_list_tabel m ON t.id_tabel = m.id
                LEFT JOIN m_unitkerja ON m.id_unitkerja = m_unitkerja.id_unitkerja
                WHERE t.link_tabel REGEXP ?
                ORDER BY t.tahun DESC, t.id DESC
                LIMIT 1
            ", ['id=' . $id_p . '([^0-9]|$)'])->getRow();
        }

        return $dda_info;
    }

    /**
     * Ambil metadata spreadsheet spesifik untuk view_sheet
     */
    public function getSheetMetadata(?int $table_id, ?string $url)
    {
        $query = $this->db->table('t_tahun_tabel t')
            ->select($this->getBaseTableSelect(), false)
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left');

        if (!empty($table_id)) {
            $query->where('t.id', $table_id);
        } else {
            $query->where('t.link_tabel', $url);
        }

        return $query->orderBy('t.tahun', 'DESC')->orderBy('t.id', 'DESC')->get()->getRow();
    }

    /**
     * Ambil daftar OPD untuk halaman Jelajah Instansi
     */
    public function getInstansiList(?string $q = null): array
    {
        $builder = $this->db->table('t_tahun_tabel t')
            ->select('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, COUNT(t.id) as total_tabel')
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left')
            ->where('m_unitkerja.unitkerja_ind IS NOT NULL')
            ->where('t.is_publish', 1);

        if (!empty($q)) {
            $builder->like('m_unitkerja.unitkerja_ind', $q);
        }

        return $builder->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind')
            ->orderBy('total_tabel', 'DESC')
            ->get()
            ->getResult();
    }
}
