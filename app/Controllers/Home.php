<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\M_frontend;

class Home extends BaseController
{
    protected M_frontend $frontendModel;
    protected \App\Models\M_tabeldinamis $tabelDinamisModel;

    public function __construct()
    {
        $this->frontendModel = new M_frontend();
        $this->tabelDinamisModel = new \App\Models\M_tabeldinamis();
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
            'total_tabel' => is_object($stats) ? ($stats->total_tabel ?? 0) : ($stats['total_tabel'] ?? 0),
            'total_opd' => is_object($stats) ? ($stats->total_opd ?? 0) : ($stats['total_opd'] ?? 0),
            'page' => 'frontend/home'
        ];

        return view('frontend/layout', $data);
    }

    public function search()
    {
        $q = $this->request->getGet('q');
        $unit = $this->request->getGet('unit');
        $opd_search = $this->request->getGet('opd_search');
        $page = max(1, (int)($this->request->getVar('page') ?? 1));
        $perPage = 12;

        $unique_units = $this->frontendModel->getUniqueUnits();

        $searchData = $this->frontendModel->searchTables([
            'q' => $q,
            'unit' => $unit,
            'opd_search' => $opd_search,
        ], $page, $perPage);

        $data = [
            'title' => 'Eksplorasi Data DDA',
            'q' => $q,
            'unit' => $unit,
            'opd_search' => $opd_search,
            'unique_units' => $unique_units,
            'results' => $searchData['results'] ?? [],
            'pager' => \Config\Services::pager(),
            'page_num' => $page,
            'per_page' => $perPage,
            'total_rows' => $searchData['total'] ?? 0,
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

        // Map id_api dari m_konfigurasi_api via Model
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

            // Proteksi: Jika tabel berstatus Draft, tolak akses publik (kecuali admin login)
            $is_admin = session()->get('admin_valid') && !empty(session()->get('admin_id'));
            if ($dda_info && (int)($dda_info->is_publish ?? 0) !== 1 && !$is_admin) {
                return redirect()->to('/home/search')->with('error', 'Tabel data belum dipublikasikan untuk umum.');
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

        $parsed_table_id = (!empty($table_id) && is_numeric($table_id)) ? (int)$table_id : null;
        $dda_info = $this->frontendModel->getSheetMetadata($parsed_table_id, $url);

        // Proteksi: Jika tabel berstatus Draft, tolak akses publik (kecuali admin login)
        $is_admin = session()->get('admin_valid') && !empty(session()->get('admin_id'));
        if ($dda_info && (int)($dda_info->is_publish ?? 0) !== 1 && !$is_admin) {
            return redirect()->to('/home/search')->with('error', 'Tabel data belum dipublikasikan untuk umum.');
        }

        if (empty($url) && $dda_info) {
            $url = $dda_info->link_tabel;
        }

        $data = [
            'title' => 'Lihat Tabel',
            'sheet_url' => $url,
            'dda_info' => $dda_info,
            'page' => 'frontend/v_tabel_sheet'
        ];

        return view('frontend/layout', $data);
    }

    /**
     * Tampilan publik tabel dinamis internal (Sprint 6 Frontend Publik)
     * URL: home/view_dinamis/{id_tahun_tabel}
     */
    public function view_dinamis($table_id = null)
    {
        $parsed_table_id = (!empty($table_id) && is_numeric($table_id)) ? (int)$table_id : null;
        if (empty($parsed_table_id)) {
            return redirect()->to('/home/search');
        }

        $dda_info = $this->frontendModel->getDynamicTableMetadata($parsed_table_id);
        if (!$dda_info) {
            return redirect()->to('/home/search')->with('error', 'Tabel data tidak ditemukan.');
        }

        // Jika tabel merupakan API Kominfo (view_portal_tabel), arahkan ke view_tabel
        if (strpos($dda_info->link_tabel ?? '', 'view_portal_tabel') !== false) {
            $portal_id = $dda_info->link_tabel;
            if (preg_match('/[?&]id=([^&]+)/', $dda_info->link_tabel, $m_id)) {
                $portal_id = $m_id[1];
            }
            return redirect()->to("home/view_tabel?id=" . urlencode($portal_id) . "&table_id=" . $parsed_table_id);
        }

        // Proteksi: Jika tabel berstatus Draft, tolak akses publik (kecuali admin login)
        $is_admin = session()->get('admin_valid') && !empty(session()->get('admin_id'));
        if ((int)($dda_info->is_publish ?? 0) !== 1 && !$is_admin) {
            return redirect()->to('/home/search')->with('error', 'Tabel data belum dipublikasikan untuk umum.');
        }

        $ta = (int)(session()->get('admin_ta') ?: (session()->get('tahun') ?: date('Y')));
        $context = $this->tabelDinamisModel->resolveTableContext($parsed_table_id, $ta, true);
        $dda_info = $context['tabel'] ?: $dda_info;
        $indikator = $context['indikator'];
        $tahunTabel = $context['tahun_tabel'];
        $dda_info->id_tahun_tabel = $tahunTabel ? (int)$tahunTabel->id : (int)$dda_info->id;

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
            $hasSavedCfgJson = false;
            if (!empty($indikator->config_tampilan)) {
                $decodedCfg = json_decode($indikator->config_tampilan, true);
                if (is_array($decodedCfg)) {
                    $configTampilan = array_merge($configTampilan, $decodedCfg);
                    $hasSavedCfgJson = true;
                }
            }
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

            if (!empty($indikator->id_karakteristik_kelompok)) {
                $itemsKolom     = $this->tabelDinamisModel->getKarakteristikItems((int)$indikator->id_karakteristik_kelompok);
                $structureKolom = $this->tabelDinamisModel->buildColumnStructure($itemsKolom);
            }

            if (!empty($indikator->id_baris_kelompok)) {
                $kelompokBaris = $this->tabelDinamisModel->getBarisKelompok((int)$indikator->id_baris_kelompok);
                $itemsBaris    = $this->tabelDinamisModel->getBarisItems((int)$indikator->id_baris_kelompok);
            }

            if (!empty($indikator->id_periode_kelompok)) {
                $kelompokPeriode = $this->tabelDinamisModel->getPeriodeKelompok((int)$indikator->id_periode_kelompok);
                $itemsPeriode    = $this->tabelDinamisModel->getPeriodeItems((int)$indikator->id_periode_kelompok);
            }

            $available_years = $this->tabelDinamisModel->getAvailableYears((int)$indikator->id);

            // Filter tahun data untuk pengunjung publik: hanya tampilkan tahun yang sudah diisi, diverifikasi BPS, dan dipublish
            $isAdminPreview = (bool)(session()->get('admin_valid') && !empty(session()->get('admin_id')));
            if (!$isAdminPreview && !empty($available_years)) {
                $db = \Config\Database::connect();
                $pubRows = $db->table('t_status_tahunan')
                    ->select('tahun')
                    ->where('id_tabel', (int)$dda_info->id)
                    ->where('is_publish', 1)
                    ->where('is_periksa', 1)
                    ->where('is_confirm', '1')
                    ->orderBy('tahun', 'DESC')
                    ->get()
                    ->getResultArray();

                if (!empty($pubRows)) {
                    $maxPublishedDdaYear = max(array_map(static fn($r) => (int)$r['tahun'], $pubRows));
                    $maxAllowedDataYear = $maxPublishedDdaYear;
                    $available_years = array_values(array_filter($available_years, static function ($yr) use ($maxAllowedDataYear) {
                        return (int)$yr <= $maxAllowedDataYear;
                    }));
                } else {
                    $available_years = [];
                }
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

            $selected_years = $this->tabelDinamisModel->resolveDisplayYears($indikator->tahun_tampil ?? null, (int)$ta, $rawReqList, $indikator);
            sort($selected_years);

            // Ambil seluruh sel nilai
            $db = \Config\Database::connect('default');
            $nilaiRows = $db->table('t_nilai_dinamis')
                ->where('id_indikator', (int)$indikator->id)
                ->get()
                ->getResult();

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

        if ($isAdminPreview) {
            $selectable_years = !empty($available_years) ? $available_years : [$ta];
            if (!in_array($ta, $selectable_years, true)) {
                $selectable_years[] = $ta;
            }
        } else {
            $selectable_years = !empty($available_years) ? $available_years : [];
        }
        rsort($selectable_years);

        // Batasi $selected_years bagi pengunjung publik agar hanya berisi tahun yang sah dipilih
        if (!$isAdminPreview && !empty($selectable_years)) {
            $selected_years = array_values(array_intersect($selected_years, $selectable_years));
            if (empty($selected_years)) {
                $selected_years = [max($selectable_years)];
            }
        }

        $tipePeriodeTabel = $indikator->tipe_periode ?? 'tahunan';
        $display_title = function_exists('formatDynamicTableTitle')
            ? formatDynamicTableTitle($dda_info->judul_ind, $selected_years, 'id', $tipePeriodeTabel)
            : ($dda_info->judul_ind ?? 'Tabel Dinamis');
        $display_title_en = function_exists('formatDynamicTableTitle')
            ? formatDynamicTableTitle($dda_info->judul_en ?: ($dda_info->judul_ind ?? ''), $selected_years, 'en', $tipePeriodeTabel)
            : ($dda_info->judul_en ?? '');

        $data = [
            'title'            => $display_title,
            'display_title'    => $display_title,
            'display_title_en' => $display_title_en,
            'tabel'            => $dda_info,
            'dda_info'         => $dda_info,
            'indikator'        => $indikator,
            'structureKolom'   => $structureKolom,
            'itemsKolom'       => $itemsKolom,
            'itemsBaris'       => $itemsBaris,
            'kelompokBaris'    => $kelompokBaris,
            'itemsPeriode'     => $itemsPeriode,
            'kelompokPeriode'  => $kelompokPeriode,
            'nilaiMap'         => $nilaiMap,
            'available_years'  => $available_years,
            'selectable_years' => $selectable_years,
            'selected_years'   => $selected_years,
            'config_tampilan'  => $configTampilan,
            'ta'               => $ta,
            'page'             => 'frontend/v_tabel_dinamis'
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