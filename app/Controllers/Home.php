<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Home extends BaseController
{
    /**
     * Helper method to generate standard table SELECT expression
     * Supports dynamic period placeholder replacement [PERIODE]
     */
    private function getBaseTableSelect(): string
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

    public function index()
    {
        $db = \Config\Database::connect();
        
        // Get recent or popular tables from m_list_tabel + t_tahun_tabel with complete title (no_tabel, judul, and periode)
        $builder = $db->table('t_tahun_tabel t');
        $builder->select($this->getBaseTableSelect(), false);
        $builder->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner');
        $builder->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left');
        $builder->orderBy('t.id', 'DESC');
        $builder->limit(15);
        $recent_tables = $builder->get()->getResult();

        // Get Top OPDs (Organisasi Perangkat Daerah)
        $units_builder = $db->table('t_tahun_tabel t');
        $units_builder->select('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, COUNT(t.id) as total_tabel');
        $units_builder->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner');
        $units_builder->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left');
        $units_builder->where('m_unitkerja.unitkerja_ind IS NOT NULL');
        $units_builder->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind');
        $units_builder->orderBy('m_unitkerja.unitkerja_ind', 'ASC');
        $opd_list = $units_builder->get()->getResult();

        // Get dynamic stats for hero section
        $total_tabel = $db->table('t_tahun_tabel t')
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->countAllResults();
        $total_opd = $db->table('m_unitkerja')->countAllResults();

        $data = [
            'title' => 'Portal Data Statistik',
            'recent_tables' => $recent_tables,
            'opd_list' => $opd_list,
            'total_tabel' => $total_tabel,
            'total_opd' => $total_opd,
            'page' => 'frontend/home'
        ];

        return view('frontend/layout', $data);
    }

    public function search()
    {
        $db = \Config\Database::connect();
        $q = $this->request->getGet('q');
        $unit = $this->request->getGet('unit');
        
        // Fetch unique unit_kerja for sidebar filter
        $units_builder = $db->table('t_tahun_tabel t');
        $units_builder->select('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, COUNT(t.id) as total');
        $units_builder->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner');
        $units_builder->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left');
        $units_builder->where('m_unitkerja.unitkerja_ind IS NOT NULL');
        $units_builder->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind');
        $units_builder->orderBy('m_unitkerja.unitkerja_ind', 'ASC');
        $unique_units = $units_builder->get()->getResult();

        $builder = $db->table('t_tahun_tabel t');
        $builder->select($this->getBaseTableSelect(), false);
        $builder->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner');
        $builder->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left');
        
        $opd_search = $this->request->getGet('opd_search');
        $tahun = $this->request->getGet('tahun');

        // Filter by keyword (searches table title, table number, or period)
        if (!empty($q)) {
            $q_clean = trim(preg_replace('/\s+/', ' ', $q));
            // Normalize "nomor 4.1" or "no. 4.1" or "no 4.1" or "tabel 4.1" to "4.1"
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

        // Pagination Logic
        $page = max(1, (int)($this->request->getVar('page') ?? 1));
        $perPage = 12;
        $total = $builder->countAllResults(false);
        $builder->limit($perPage, ($page - 1) * $perPage);
        
        $results = $builder->get()->getResult();
        $pager = \Config\Services::pager();

        $data = [
            'title' => 'Eksplorasi Data DDA',
            'q' => $q,
            'unit' => $unit,
            'opd_search' => $opd_search,
            'tahun' => $tahun,
            'unique_units' => $unique_units,
            'results' => $results,
            'pager' => $pager,
            'page_num' => $page,
            'per_page' => $perPage,
            'total_rows' => $total,
            'page' => 'frontend/search'
        ];
        return view('frontend/layout', $data);
    }

    protected function callApi2($url)
    {
        $ch = curl_init();
        $token = 'sdj_UYf5h0TPgUW3E0kcZisn33xrTe5PMRqLuXq6CWbAfb69tEA7dILT0TLhpFtjQiWHqrd1pn9SGvE4NYlN';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }

    public function view_tabel()
    {
        $id_api = $this->request->getGet('id');
        $table_id = $this->request->getGet('table_id');
        if (empty($id_api)) {
            return redirect()->to('/');
        }

        $db = \Config\Database::connect();
        $ids = explode(',', $id_api);

        // Map id_api ke id_api_new dari t_tabel_match jika ada
        $match_rows = $db->table('t_tabel_match')
            ->select('id_api, id_api_new')
            ->whereIn('id_api', $ids)
            ->get()
            ->getResultArray();
            
        $new_ids = [];
        foreach ($ids as $id) {
            $mapped = false;
            foreach ($match_rows as $row) {
                if ($row['id_api'] === trim($id) && $row['id_api_new'] != 0) {
                    $new_ids[] = $row['id_api_new'];
                    $mapped = true;
                    break;
                }
            }
            if (!$mapped) {
                $new_ids[] = trim($id);
            }
        }
        $new_ids_str = implode(',', $new_ids);

        if ($new_ids_str !== trim($id_api) && !empty($new_ids_str)) {
            $table_id_param = (!empty($table_id) && is_numeric($table_id)) ? '&table_id=' . (int)$table_id : '';
            return redirect()->to("home/view_tabel?id=" . $new_ids_str . $table_id_param);
        }

        $all_results = [];
        $api_error_msgs = [];

        foreach ($ids as $id_p) {
            $id_p = trim($id_p);
            if (empty($id_p)) continue;

            if (preg_match('/[?&]id=([^&]+)/', $id_p, $matches)) {
                $id_p = $matches[1];
            } elseif (strpos($id_p, '?') !== false) {
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

            // Fetch table metadata to render title and breadcrumbs
            $select_sql = $this->getBaseTableSelect();
            $dda_info = null;

            if (!empty($table_id) && is_numeric($table_id)) {
                $dda_info = $db->query("
                    SELECT 
                        {$select_sql}
                    FROM t_tahun_tabel t 
                    JOIN m_list_tabel m ON t.id_tabel = m.id
                    LEFT JOIN m_unitkerja ON m.id_unitkerja = m_unitkerja.id_unitkerja
                    WHERE t.id = ?
                    LIMIT 1
                ", [(int)$table_id])->getRow();
            }

            if (!$dda_info) {
                $dda_info = $db->query("
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

            $final_res = $meta_info ?? [];
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

        $data = [
            'title' => 'Lihat Tabel',
            'api_results' => $all_results,
            'api_errors' => $api_error_msgs,
            'id_api' => $id_api,
            'page' => 'frontend/v_tabel'
        ];

        return view('frontend/layout', $data);
    }

    public function view_sheet()
    {
        $url = $this->request->getGet('url');
        $table_id = $this->request->getGet('table_id');
        if (empty($url) && empty($table_id)) {
            return redirect()->to('/');
        }

        $db = \Config\Database::connect();
        $query = $db->table('t_tahun_tabel t')
            ->select($this->getBaseTableSelect(), false)
            ->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner')
            ->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left');

        if (!empty($table_id) && is_numeric($table_id)) {
            $query->where('t.id', (int)$table_id);
        } else {
            $query->where('t.link_tabel', $url);
        }

        $dda_info = $query->orderBy('t.tahun', 'DESC')->orderBy('t.id', 'DESC')->get()->getRow();

        if (empty($url) && $dda_info) {
            $url = $dda_info->link_tabel;
        }

        $data = [
            'title' => $dda_info ? $dda_info->judul_ind : 'Lihat Tabel',
            'sheet_url' => $url,
            'dda_info' => $dda_info,
            'page' => 'frontend/v_tabel_sheet'
        ];

        return view('frontend/layout', $data);
    }

    public function instansi()
    {
        $db = \Config\Database::connect();
        
        $q = $this->request->getGet('q');

        $units_builder = $db->table('t_tahun_tabel t');
        $units_builder->select('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind, COUNT(t.id) as total_tabel');
        $units_builder->join('m_list_tabel m', 'm.id = t.id_tabel', 'inner');
        $units_builder->join('m_unitkerja', 'm_unitkerja.id_unitkerja = m.id_unitkerja', 'left');
        $units_builder->where('m_unitkerja.unitkerja_ind IS NOT NULL');
        
        if (!empty($q)) {
            $units_builder->like('m_unitkerja.unitkerja_ind', $q);
        }

        $units_builder->groupBy('m_unitkerja.id_unitkerja, m_unitkerja.unitkerja_ind');
        $units_builder->orderBy('total_tabel', 'DESC');
        $opd_list = $units_builder->get()->getResult();

        $data = [
            'title' => 'Jelajah Instansi',
            'opd_list' => $opd_list,
            'q' => $q,
            'page' => 'frontend/instansi'
        ];

        return view('frontend/layout', $data);
    }
}