<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\M_frontend;

class Home extends BaseController
{
    protected M_frontend $frontendModel;

    public function __construct()
    {
        $this->frontendModel = new M_frontend();
    }

    public function index()
    {
        $recent_tables = $this->frontendModel->getRecentTables(15);
        $opd_list = $this->frontendModel->getTopOpdList();
        $stats = $this->frontendModel->getPortalStats();

        $data = [
            'title' => 'Portal Data Statistik',
            'recent_tables' => $recent_tables,
            'opd_list' => $opd_list,
            'total_tabel' => $stats['total_tabel'],
            'total_opd' => $stats['total_opd'],
            'page' => 'frontend/home'
        ];

        return view('frontend/layout', $data);
    }

    public function search()
    {
        $q = $this->request->getGet('q');
        $unit = $this->request->getGet('unit');
        $opd_search = $this->request->getGet('opd_search');
        $tahun = $this->request->getGet('tahun');
        $page = max(1, (int)($this->request->getVar('page') ?? 1));
        $perPage = 12;

        $unique_units = $this->frontendModel->getUniqueUnits();

        $searchData = $this->frontendModel->searchTables([
            'q' => $q,
            'unit' => $unit,
            'opd_search' => $opd_search,
            'tahun' => $tahun
        ], $page, $perPage);

        $data = [
            'title' => 'Eksplorasi Data DDA',
            'q' => $q,
            'unit' => $unit,
            'opd_search' => $opd_search,
            'tahun' => $tahun,
            'unique_units' => $unique_units,
            'results' => $searchData['results'],
            'pager' => \Config\Services::pager(),
            'page_num' => $page,
            'per_page' => $perPage,
            'total_rows' => $searchData['total'],
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

        $ids = explode(',', $id_api);

        // Map id_api ke id_api_new dari t_tabel_match via Model
        $match_rows = $this->frontendModel->getMatchMapping($ids);
            
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
        $force_refresh = (bool)($this->request->getGet('refresh') ?? false);

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

            // 1. Cek apakah respons API sudah tersimpan di Cache CI4
            $cache_key = 'satudata_api_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $id_p);
            $cached_payload = !$force_refresh ? cache($cache_key) : null;

            if (!empty($cached_payload) && is_array($cached_payload) && isset($cached_payload['data'])) {
                $all_rows = $cached_payload['data'];
                $meta_info = $cached_payload['meta'] ?? null;
            } else {
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

                // Simpan ke Cache CI4 selama 2 jam (7200 detik) jika data berhasil diambil
                if (!empty($all_rows)) {
                    cache()->save($cache_key, [
                        'data' => $all_rows,
                        'meta' => $meta_info,
                        'cached_at' => time()
                    ], 7200);
                }
            }

            // Fetch table metadata via Model
            $parsed_table_id = (!empty($table_id) && is_numeric($table_id)) ? (int)$table_id : null;
            $dda_info = $this->frontendModel->getTableMetadata($parsed_table_id, $id_p);

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

        $parsed_table_id = (!empty($table_id) && is_numeric($table_id)) ? (int)$table_id : null;
        $dda_info = $this->frontendModel->getSheetMetadata($parsed_table_id, $url);

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
        $q = $this->request->getGet('q');
        $opd_list = $this->frontendModel->getInstansiList($q);

        $data = [
            'title' => 'Jelajah Instansi',
            'opd_list' => $opd_list,
            'q' => $q,
            'page' => 'frontend/instansi'
        ];

        return view('frontend/layout', $data);
    }
}