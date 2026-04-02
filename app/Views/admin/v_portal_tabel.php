<?php

/**
 * View Portal Tabel - Versi Persis Dokumen Fisik DDA
 * Didesain ulang untuk kemiripan 100% dengan layout cetak
 * Ditambahkan Fitur: Smart Multilevel Header (Auto-Grouping)
 */

$hasError = false;
$errorMsg = '';

if (empty($api_results)) {
    $hasError = true;
    $errorMsg = 'Tidak ada data yang diterima dari API.';
}


// -------------------------------------------------------------------------
// TEMPLATE CONFIGURATION (Hardcoded for specific res_id or Titles)
// -------------------------------------------------------------------------
function get_table_template($res_id, $title, $columns)
{
    // Template matching by res_id or Keywords in Title
    $title = strtolower($title);

    // Example for Sertifikat BPN (Table 1.1.5 or res_id 4)
    if ($res_id == 4 || stripos($title, 'sertifikat') !== false) {
        return [
            'type' => 'manual',
            'groups' => [
                ['label' => 'Sertifikat Diterbitkan', 'label_en' => 'Certificates Issued', 'colspan' => count($columns) - 1, 'start' => 1]
            ]
        ];
    }

    // Default: use Auto-Recognition
    return ['type' => 'auto'];
}

function parse_nested_headers($columns, $res_id = 0, $title = '')
{
    $header_structure = [];
    $raw_labels = [];

    // 1. Dapatkan label bersih
    foreach ($columns as $idx => $col) {
        $raw_labels[$idx] = get_dda_label_nested($col);
    }

    $template = get_table_template($res_id, $title, $columns);

    if ($template['type'] === 'manual') {
        // Implementation for manual templates (to be expanded)
        // For now, let's stick to a hybrid: use auto but allow overrides
    }

    $i = 0;
    while ($i < count($columns)) {
        // Identifikasi kolom khusus untuk styling header DDA
        $lowC = strtolower($columns[$i]);
        $isYear = (strpos($lowC, 'tahun') !== false || strpos($lowC, 'year') !== false);
        $isKab = (strpos($lowC, 'kabupaten') !== false || strpos($lowC, 'kota') !== false || strpos($lowC, 'kab') !== false);

        if ($i === 0) {
            $header_structure[] = [
                'type' => 'single',
                'label' => $raw_labels[$i][0],
                'label_en' => $raw_labels[$i][1],
                'col_idx' => $i
            ];
            $i++;
            continue;
        }

        if ($i === 1 && $isKab && $header_structure[0]['type'] === 'single') {
            $header_structure[] = [
                'type' => 'single',
                'label' => $raw_labels[$i][0],
                'label_en' => $raw_labels[$i][1],
                'col_idx' => $i
            ];
            $i++;
            continue;
        }

        $label = $raw_labels[$i][0];
        $label_en = $raw_labels[$i][1];

        // Cari pola "Induk Anak" (Contoh: "Kayu Bulat Iuphhk Ha")
        $parts = explode(' ', $label);

        // Prefix ditentukan dari 2 kata pertama jika cocok dengan kata kunci umum DDA
        $keywords = ['kayu bulat', 'kayu olahan', 'luas areal', 'tenaga kerja', 'hasil hutan', 'jumlah izin', 'produksi', 'populasi', 'hak guna', 'hak pengelolaan', 'hak milik'];
        $found_prefix = '';
        foreach ($keywords as $kw) {
            if (stripos($label, $kw) === 0) {
                $found_prefix = ucwords($kw);
                break;
            }
        }

        if (empty($found_prefix) && count($parts) > 0) {
            $found_prefix = $parts[0];
        }

        // Cari seberapa banyak kolom yang punya prefix yang sama
        $count = 1;
        $sub_labels = [];
        $sub_labels[] = [
            'label' => trim(str_ireplace($found_prefix, '', $label)) ?: $label,
            'label_en' => $label_en,
            'col_idx' => $i
        ];

        for ($j = $i + 1; $j < count($columns); $j++) {
            if (!empty($found_prefix) && stripos($raw_labels[$j][0], $found_prefix) === 0) {
                $next_label = $raw_labels[$j][0];
                $sub_candidate = trim(str_ireplace($found_prefix, '', $next_label));
                $count++;
                $sub_labels[] = [
                    'label' => !empty($sub_candidate) ? $sub_candidate : $next_label,
                    'label_en' => $raw_labels[$j][1],
                    'col_idx' => $j
                ];
            } else {
                break;
            }
        }

        // Jika ada "Hak Guna" atau sejenisnya, biasanya di DDA itu kolom mandiri atau grup khusus
        // Jika dia grup tapi semua label anaknya sama, kita pecah saja
        $is_valid_group = false;
        if ($count > 1) {
            foreach ($sub_labels as $sb) {
                if ($sb['label'] != $label) {
                    $is_valid_group = true;
                    break;
                }
            }
        }

        if ($count > 1 && $is_valid_group) {
            $header_structure[] = [
                'type' => 'group',
                'prefix' => $found_prefix,
                'colspan' => $count,
                'children' => $sub_labels
            ];
            $i += $count;
        } else {
            $header_structure[] = [
                'type' => 'single',
                'label' => $label,
                'label_en' => $label_en,
                'col_idx' => $i
            ];
            $i++;
        }
    }
    return $header_structure;
}

function get_dda_label_nested($col)
{
    $map = [
        'kabupaten' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kabupaten_kota' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kabkot' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kab_ko' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kab_kota' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kab' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kab_kot' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'nama_kabupaten' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'nama_kabupaten_kota' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kabupatenkota_data' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kabupaten_kota_data' => ['Kabupaten/Kota', 'Regency/Municipality'],
        'kod_wil' => ['Kode Wilayah', 'Area Code'],
        'kode_wil' => ['Kode Wilayah', 'Area Code'],
        'kode_kab_kota' => ['Kode Wilayah', 'Area Code'],
        'kode_wilayah' => ['Kode Wilayah', 'Area Code'],
        'kode_bps' => ['Kode Wilayah', 'Area Code'],
        'kode_kemendagri' => ['Kode Wilayah', 'Area Code'],
        'tahun_data' => ['Tahun', 'Year'],
        'tahun' => ['Tahun', 'Year'],
        'pendapat' => ['Pendapatan', 'Income/Revenue'],
        'pedapat' => ['Pendapatan', 'Income/Revenue'],
        'pdpt' => ['Pendapatan', 'Income/Revenue'],
        'pendapatan' => ['Pendapatan', 'Income/Revenue'],
        'jmlh_kec' => ['Jumlah Kecamatan', 'Number of Sub-districts'],
        'jml_kec' => ['Jumlah Kecamatan', 'Number of Sub-districts'],
        'jumlah_kec' => ['Jumlah Kecamatan', 'Number of Sub-districts'],
        'jmlh_kel' => ['Jumlah Desa/Kelurahan', 'Number of Villages'],
        'jml_kel' => ['Jumlah Desa/Kelurahan', 'Number of Villages'],
        'jumlah_kel' => ['Jumlah Desa/Kelurahan', 'Number of Villages'],
        'jmlh_desa' => ['Jumlah Desa', 'Number of Villages'],
        'jumlah_desa' => ['Jumlah Desa', 'Number of Villages'],
        'jml_kelurahan' => ['Jumlah Kelurahan', 'Number of Villages'],
        'jumlah_kelurahan' => ['Jumlah Kelurahan', 'Number of Villages'],
        'jumlah' => ['Jumlah', 'Total'],
        'jmlh' => ['Jumlah', 'Total'],
        'hak_milik' => ['Hak Milik', 'Right of Ownership'],
        'hak_guna_usaha' => ['Hak Guna Usaha', 'Right of Use'],
        'hak_guna_bangunan' => ['Hak Guna Bangunan', 'Right to Build'],
        'hak_pakai' => ['Hak Pakai', 'Use Right'],
        'hak_pengelolaan' => ['Hak Pengelolaan', 'Right Management'],
        'hak_wakaf' => ['Hak Wakaf', 'Endowments Rights'],
        'islam' => ['Islam', 'Islam'],
        'protestan' => ['Protestan', 'Protestant'],
        'katolik' => ['Katolik', 'Catholic'],
        'hindu' => ['Hindu', 'Hindu'],
        'budha' => ['Budha', 'Buddha'],
        'khonghucu' => ['Khonghucu', 'Confucianism'],
        'lainnya' => ['Lainnya', 'Others'],
        'puskesmas' => ['Puskesmas', 'Public Health Center'],
        'rumah_sakit' => ['Rumah Sakit', 'Hospital'],
        'sekolah' => ['Sekolah', 'School'],
        'guru' => ['Guru', 'Teacher'],
        'murid' => ['Murid', 'Pupil'],
    ];
    $key = strtolower(str_replace([' ', '_'], '_', $col));
    if (isset($map[$key])) return $map[$key];

    // Auto format
    $clean = ucwords(str_replace(['_', ' - '], [' ', ' '], $col));

    // English mapping standar DDA
    $en_map = [
        'Kayu Bulat' => 'Logs',
        'Kayu Olahan' => 'Processed Timber',
        'Luas Areal'  => 'Area',
        'Luas Tanam'  => 'Planted Area',
        'Produksi'    => 'Production',
        'Produktivitas' => 'Productivity',
        'Jumlah'      => 'Total',
        'Populasi'    => 'Population',
        'Ternak'      => 'Livestock',
        'Usaha'       => 'Establishment',
        'Tenaga Kerja' => 'Worker',
        'Hasil Hutan'  => 'Forest Product',
        'Jumlah Izin'  => 'Number of Permits',
        'Laki-laki'    => 'Male',
        'Perempuan'   => 'Female',
        'Sertifikat'   => 'Certificate',
        'Kecamatan'    => 'Sub-district',
        'Agama'        => 'Religion',
        'Penduduk'     => 'Population',
        'Kesehatan'    => 'Health',
        'Pendidikan'   => 'Education',
    ];
    $en = '';
    foreach ($en_map as $id_word => $en_word) {
        if (stripos($clean, $id_word) !== false) {
            $en = $en_word;
            break;
        }
    }

    return [$clean, $en];
}
?>

<style>
    .dda-body {
        background: #fff;
        padding: 20px;
        font-family: 'Arial', sans-serif;
        color: #000;
        line-height: 1.2;
    }

    /* Header Sections */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2px;
    }

    .header-table td {
        padding: 8px;
        border-bottom: 2px solid #000;
        vertical-align: bottom;
    }

    .label-box {
        width: 15%;
        text-align: center;
        border-right: 1px solid #000;
    }

    .index-box {
        width: 10%;
        font-weight: bold;
        font-size: 16px;
        text-align: center;
    }

    .title-box {
        text-align: left;
        padding-left: 20px !important;
    }

    .title-id {
        font-weight: bold;
        font-size: 14px;
        margin-bottom: 5px;
        display: block;
    }

    .title-en {
        font-style: italic;
        font-size: 13px;
        display: block;
    }

    /* Main Data Table */
    .main-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 1px solid #000;
    }

    .main-table thead th {
        border: 1px solid #000;
        padding: 8px 4px;
        font-size: 11px;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }

    .main-table thead tr.num-row th {
        font-weight: normal;
        padding: 2px;
        background: #fff;
        font-size: 10px;
    }

    .main-table tbody td {
        border: 1px solid #ccc;
        border-left: 1px solid #000;
        border-right: 1px solid #000;
        padding: 6px 8px;
        font-size: 11px;
        vertical-align: top;
    }

    .main-table tbody tr.new-kab td {
        border-top: 1px solid #000;
    }

    .main-table tfoot td {
        border-top: 2px solid #000;
    }

    .num-col {
        text-align: center;
        width: 30px;
        font-weight: bold;
        border-right: none !important;
    }

    .kab-col {
        border-left: none !important;
        padding-left: 10px !important;
    }

    .val-col {
        text-align: center;
    }

    .btn-rounded-modern {
        border-radius: 50px;
        padding: 8px 24px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .btn-back-modern {
        background: #f8fafc;
        color: #64748b;
        text-decoration: none;
        border: 1px solid #e2e8f0;
    }

    .btn-back-modern:hover {
        background: #fff;
        color: #ff6d1f;
        border-color: #ff6d1f;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255, 109, 31, 0.1);
        text-decoration: none;
    }

    .export-btn-modern {
        background: #FF6D1F;
        color: #fff;
    }

    .export-btn-modern:hover {
        background: #e05e15;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(255, 109, 31, 0.25);
    }

    /* Progress Bar Styles */
    #export-progress-container {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 280px;
        background: #fff;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        border: 1px solid #eef2f7;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        animation: slideInUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    @keyframes slideInUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .export-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .export-title {
        font-size: 13px;
        font-weight: 700;
        color: #2c3e50;
        display: flex;
        align-items: center;
    }

    .export-title i {
        margin-right: 8px;
        color: #27ae60;
    }

    .export-progress-bg {
        height: 6px;
        background: #f1f4f8;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .export-progress-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #2ecc71, #27ae60);
        transition: width 0.4s ease;
        border-radius: 3px;
    }

    .export-status {
        font-size: 11px;
        color: #7f8c8d;
        display: flex;
        justify-content: space-between;
    }
</style>

<div class="dda-body">
    <?php
    $fallback_url = base_url() . 'admin/dda';
    $back_url = $fallback_url;
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        // Jika referer bukan halaman tabel portal ini sendiri, simpan sebagai back URL
        if (strpos($referer, 'view_portal_tabel') === false && strpos($referer, base_url()) !== false) {
            $back_url = $referer;
        }
    }
    ?>
    <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn-rounded-modern btn-back-modern mb-3">
        <i class="bi bi-arrow-left"></i> KEMBALI
    </a>
    <div class="float-end d-flex gap-2 align-items-center">
        <?php
        // Ambil semua tahun unik dari koleksi data untuk dropdown filter
        $unique_years = [];
        foreach ($api_results as $res) {
            $rows_data = $res['data'] ?? [];
            foreach ($rows_data as $rd) {
                $y = $rd['tahun_data'] ?? $rd['tahun'] ?? null;
                if ($y) $unique_years[] = $y;
            }
        }
        $unique_years = array_unique($unique_years);
        rsort($unique_years);
        ?>
        <?php if (!empty($unique_years)): ?>
            <div class="filter-box-modern">
                <i class="bi bi-funnel text-muted"></i>
                <select id="year-filter" onchange="filterByYear(this.value)" class="form-select-modern">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($unique_years as $yr): ?>
                        <option value="<?php echo $yr; ?>">Tahun <?php echo $yr; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <button onclick="exportTableToExcel('dda-container-all', 'portal-data-export')" class="btn-rounded-modern export-btn-modern shadow-primary">
            <i class="bi bi-file-earmark-excel"></i> EXPORT ALL TO EXCEL
        </button>
    </div>

    <style>
        .filter-box-modern {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            padding: 4px 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .filter-box-modern:hover {
            border-color: #FF6D1F;
        }

        .form-select-modern {
            border: none;
            background: transparent;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            outline: none;
            cursor: pointer;
            padding-right: 5px;
        }
    </style>

    <script>
        function filterByYear(year) {
            const tables = document.querySelectorAll('.table-wrapper');

            tables.forEach(wrapper => {
                const rows = wrapper.querySelectorAll('.main-table tbody tr');
                let hasVisibleRow = false;

                rows.forEach(row => {
                    const rowYear = row.getAttribute('data-tahun');
                    if (year === "" || rowYear === year) {
                        row.style.display = "";
                        hasVisibleRow = true;
                    } else {
                        row.style.display = "none";
                    }
                });

                // Sembunyikan seluruh wrapper tabel (termasuk judulnya) jika tidak ada data tahun tersebut
                if (hasVisibleRow) {
                    wrapper.style.display = "";
                } else {
                    wrapper.style.display = "none";
                }
            });
        }
    </script>


    <script>
        function exportTableToExcel(tableID, filename = '') {
            const container = document.getElementById('export-progress-container');
            const fill = document.getElementById('export-progress-fill');
            const statusText = document.getElementById('export-status-text');
            const percentText = document.getElementById('export-percent-text');

            // Reset and Show
            container.style.display = 'block';
            fill.style.width = '0%';
            statusText.innerText = 'Menyiapkan data...';
            percentText.innerText = '0%';

            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.floor(Math.random() * 15) + 5;
                if (progress >= 95) {
                    clearInterval(interval);
                    progress = 95;

                    // Beri jeda sedikit agar user melihat progress 95%
                    setTimeout(executeDownload, 500);
                }
                fill.style.width = progress + '%';
                percentText.innerText = progress + '%';
            }, 150);

            function executeDownload() {
                var dataType = 'application/vnd.ms-excel';
                var tableSelect = document.querySelector("#" + tableID);
                var filename_full = filename ? filename + '.xls' : 'excel_data.xls';

                // Gunakan Blob dan ObjectURL agar lebih stabil untuk data besar
                var blob = new Blob(['\ufeff', tableSelect.outerHTML], {
                    type: dataType
                });

                if (navigator.msSaveOrOpenBlob) {
                    navigator.msSaveOrOpenBlob(blob, filename_full);
                } else {
                    var downloadLink = document.createElement("a");
                    var url = URL.createObjectURL(blob);
                    downloadLink.href = url;
                    downloadLink.download = filename_full;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    setTimeout(() => URL.revokeObjectURL(url), 100);
                }

                // Finish UI
                fill.style.width = '100%';
                percentText.innerText = '100%';
                statusText.innerText = 'Selesai! File diunduh.';

                // Hide after a delay
                setTimeout(() => {
                    container.style.opacity = '0';
                    container.style.transform = 'translateY(20px)';
                    container.style.transition = 'all 0.5s ease';
                    setTimeout(() => {
                        container.style.display = 'none';
                        container.style.opacity = '1';
                        container.style.transform = 'translateY(0)';
                        container.style.transition = 'none';
                    }, 500);
                }, 2500);
            }
        }
    </script>

    <?php if ($hasError): ?>
        <div style="color: red; padding: 20px; border: 1px solid red;"><?php echo $errorMsg; ?></div>
    <?php else: ?>
        <div id="dda-container-all">
            <?php foreach ($api_results as $idx_res => $api_result):
                // Process each result independently
                $portal_title = $api_result['title'] ?? $api_result['nama'] ?? $api_result['data']['title'] ?? 'Tabel Data Portal';
                $dda_title = $api_result['dda_title'] ?? '';
                $dda_title_en = $api_result['dda_title_en'] ?? '';
                $rows = [];
                $columns = [];

                if (isset($api_result['data']) && is_array($api_result['data'])) {
                    if (isset($api_result['data'][0]) && is_array($api_result['data'][0])) {
                        $rows = $api_result['data'];
                    } elseif (isset($api_result['data']['rows'])) {
                        $rows = $api_result['data']['rows'];
                    }
                } elseif (isset($api_result[0]) && is_array($api_result[0])) {
                    $rows = $api_result;
                }

                if (!empty($rows)) {
                    // Fitur: Sorting (Tahun Terbaru & Kode Wilayah Urut dari 3301)
                    // Mapping Urutan Standar BPS Jawa Tengah (Cilacap No 1 s/d Kota Tegal No 35)
                    $jatengOrder = [
                        'cilacap' => 1,
                        'banyumas' => 2,
                        'purbalingga' => 3,
                        'banjarnegara' => 4,
                        'kebumen' => 5,
                        'purworejo' => 6,
                        'wonosobo' => 7,
                        'magelang' => 8,
                        'boyolali' => 9,
                        'klaten' => 10,
                        'sukoharjo' => 11,
                        'wonogiri' => 12,
                        'karanganyar' => 13,
                        'sragen' => 14,
                        'grobogan' => 15,
                        'blora' => 16,
                        'rembang' => 17,
                        'pati' => 18,
                        'kudus' => 19,
                        'jepara' => 20,
                        'demak' => 21,
                        'semarang' => 22,
                        'temanggung' => 23,
                        'kendal' => 24,
                        'batang' => 25,
                        'pekalongan' => 26,
                        'pemalang' => 27,
                        'tegal' => 28,
                        'brebes' => 29,
                        'kota magelang' => 30,
                        'kota surakarta' => 31,
                        'kota salatiga' => 32,
                        'kota semarang' => 33,
                        'kota pekalongan' => 34,
                        'kota tegal' => 35
                    ];

                    usort($rows, function ($a, $b) use ($jatengOrder) {
                        // 1. Deteksi Kolom Tahun
                        $yearKeys = ['tahun', 'tahun_data', 'tahun_kegiatan', 'year'];
                        $yA = 0;
                        $yB = 0;
                        foreach ($yearKeys as $k) {
                            if (isset($a[$k])) {
                                $yA = (int)$a[$k];
                                break;
                            }
                        }
                        foreach ($yearKeys as $k) {
                            if (isset($b[$k])) {
                                $yB = (int)$b[$k];
                                break;
                            }
                        }

                        $cA = 0;
                        $cB = 0;
                        foreach ($a as $k => $v) {
                            $lowK = strtolower($k);
                            if (strpos($lowK, 'kode') !== false || strpos($lowK, 'kod') !== false || strpos($lowK, 'kd') !== false || strpos($lowK, 'bps') !== false) {
                                $cA = (int)$v;
                                break;
                            }
                        }
                        foreach ($b as $k => $v) {
                            $lowK = strtolower($k);
                            if (strpos($lowK, 'kode') !== false || strpos($lowK, 'kod') !== false || strpos($lowK, 'kd') !== false || strpos($lowK, 'bps') !== false) {
                                $cB = (int)$v;
                                break;
                            }
                        }

                        // 3. Deteksi Kolom Nama Wilayah
                        $nameKeys = ['kabupaten', 'kabupaten_kota', 'nama_wilayah', 'wilayah', 'kab_kota'];
                        $nA = '';
                        $nB = '';
                        foreach ($nameKeys as $k) {
                            if (isset($a[$k])) {
                                $nA = (string)$a[$k];
                                break;
                            }
                        }
                        foreach ($nameKeys as $k) {
                            if (isset($b[$k])) {
                                $nB = (string)$b[$k];
                                break;
                            }
                        }

                        // 3. Deteksi Kolom Nama Wilayah (Cari kolom yang mengandung kata kunci wilayah)
                        $nA = '';
                        $nB = '';
                        $nameKeywords = ['kabupaten', 'kota', 'wilayah', 'kabkot'];

                        foreach ($a as $key => $val) {
                            $lowKey = strtolower($key);
                            foreach ($nameKeywords as $kw) {
                                if (strpos($lowKey, $kw) !== false) {
                                    $nA = (string)$val;
                                    break 2;
                                }
                            }
                        }
                        foreach ($b as $key => $val) {
                            $lowKey = strtolower($key);
                            foreach ($nameKeywords as $kw) {
                                if (strpos($lowKey, $kw) !== false) {
                                    $nB = (string)$val;
                                    break 2;
                                }
                            }
                        }

                        // Fallback jika tidak ditemukan kolom spesifik (Coba kolom kedua jika kolom pertama adalah Tahun)
                        if (empty($nA)) {
                            $allVals = array_values($a);
                            $nA = (count($allVals) > 1 && is_numeric($allVals[0])) ? (string)$allVals[1] : (string)$allVals[0];
                        }
                        if (empty($nB)) {
                            $allVals = array_values($b);
                            $nB = (count($allVals) > 1 && is_numeric($allVals[0])) ? (string)$allVals[1] : (string)$allVals[0];
                        }

                        // Urutkan Tahun (Descending - Terbaru di Atas)
                        if ($yA != $yB) return $yB <=> $yA;

                        // Urutkan Kode Wilayah (Jika ada dan bukan nol)
                        if ($cA != 0 && $cB != 0 && $cA != $cB) {
                            return $cA <=> $cB;
                        }

                        // JIKA KODE TIDAK ADA, Urutkan berdasarkan Nama sesuai Mapping jatengOrder
                        // Identifikasi dulu apakah ini Kota atau Kabupaten
                        $isKotaA = (stripos($nA, 'kota') !== false || stripos($nA, 'kodya') !== false || stripos($nA, 'madyia') !== false);
                        $isKotaB = (stripos($nB, 'kota') !== false || stripos($nB, 'kodya') !== false || stripos($nB, 'madyia') !== false);

                        // Bersihkan label dari imbuhan BPS/Portal
                        $baseA = trim(strtolower(str_ireplace(['kab.', 'kabupaten', 'kota', 'kodya'], '', $nA)));
                        $baseB = trim(strtolower(str_ireplace(['kab.', 'kabupaten', 'kota', 'kodya'], '', $nB)));

                        // Deteksi Posisi dalam Mapping
                        if ($isKotaA) {
                            $posA = $jatengOrder['kota ' . $baseA] ?? ($jatengOrder[$baseA] ?? 99);
                        } else {
                            $posA = $jatengOrder[$baseA] ?? ($jatengOrder['kota ' . $baseA] ?? 99);
                        }

                        if ($isKotaB) {
                            $posB = $jatengOrder['kota ' . $baseB] ?? ($jatengOrder[$baseB] ?? 99);
                        } else {
                            $posB = $jatengOrder[$baseB] ?? ($jatengOrder['kota ' . $baseB] ?? 99);
                        }

                        // Khusus untuk "Jawa Tengah" atau "Provinsi", kita taruh paling bawah (posisi 100)
                        if (stripos($nA, 'jawa tengah') !== false || stripos($nA, 'provinsi') !== false) $posA = 100;
                        if (stripos($nB, 'jawa tengah') !== false || stripos($nB, 'provinsi') !== false) $posB = 100;

                        if ($posA != $posB) {
                            return $posA <=> $posB;
                        }

                        return strcasecmp($baseA, $baseB);
                    });

                    $columns = array_keys($rows[0]);

                    // Reorder: Tahun first, then Kabupaten
                    $thK = '';
                    $kbK = '';
                    foreach ($columns as $c) {
                        $lc = strtolower($c);
                        if (empty($thK) && (strpos($lc, 'tahun') !== false || strpos($lc, 'year') !== false)) $thK = $c;
                        if (empty($kbK) && (strpos($lc, 'kabupaten') !== false || strpos($lc, 'kota') !== false || strpos($lc, 'kab') !== false)) $kbK = $c;
                    }

                    $newCols = [];
                    if ($thK) $newCols[] = $thK;
                    if ($kbK) $newCols[] = $kbK;
                    foreach ($columns as $c) {
                        if ($c !== $thK && $c !== $kbK) $newCols[] = $c;
                    }
                    $columns = $newCols;
                } else {
                    continue; // Skip if no rows
                }

                $res_id = $api_result['res_id'] ?? 0;
                $structure = parse_nested_headers($columns, $res_id, $portal_title);
                $has_group = false;
                foreach ($structure as $item) {
                    if ($item['type'] === 'group') {
                        $has_group = true;
                        break;
                    }
                }
            ?>
                <?php if ($idx_res > 0): ?>
                    <div style="margin: 60px 0; border-top: 2px dashed #ccc; position: relative;">
                        <span style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 15px; color: #999; font-size: 11px; font-weight: bold; letter-spacing: 1px;">TABEL BERIKUTNYA / NEXT TABLE</span>
                    </div>
                <?php endif; ?>

                <div class="table-wrapper">
                    <!-- HEADER BAGIAN ATAS -->
                    <table class="header-table">
                        <tr>
                            <td class="label-box" style="width: 100px;">
                                <b>Tabel</b><br><i>Table</i>
                            </td>

                            <td class="title-box">
                                <span class="title-id">
                                    <?php
                                    if ($idx_res === 0) {
                                        echo htmlspecialchars($dda_title ?: $portal_title);
                                    } else {
                                        echo htmlspecialchars($portal_title);
                                    }
                                    ?>
                                </span>

                                <?php if ($idx_res > 0 && !empty($dda_title) && $dda_title !== $portal_title): ?>
                                    <span style="font-size: 11px; color: #666; display: block; margin-top: 2px;">
                                        (Ref: <?php echo htmlspecialchars($dda_title); ?>)
                                    </span>
                                <?php endif; ?>

                                <span class="title-en">
                                    <?php
                                    if ($idx_res === 0) {
                                        echo htmlspecialchars($dda_title_en ?: ($api_result['title_en'] ?? ''));
                                    } else {
                                        echo htmlspecialchars($api_result['title_en'] ?? $dda_title_en ?? '');
                                    }
                                    ?>
                                </span>
                            </td>
                        </tr>
                    </table>

                    <!-- TABEL DATA UTAMA -->
                    <table class="main-table dda-table-item" id="dda-table-<?php echo $idx_res; ?>">
                        <thead>
                            <!-- BARIS HEADER 1: INDUK -->
                            <tr>
                                <?php foreach ($structure as $item): ?>
                                    <?php if ($item['type'] === 'single'): ?>
                                        <th rowspan="<?php echo $has_group ? 2 : 1; ?>"><?php echo $item['label']; ?><br><i><?php echo $item['label_en']; ?></i></th>
                                    <?php else: ?>
                                        <th colspan="<?php echo $item['colspan']; ?>"><?php echo $item['prefix']; ?><br><i></i></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                            <?php if ($has_group): ?>
                                <!-- BARIS HEADER 2: ANAK -->
                                <tr>
                                    <?php foreach ($structure as $item): ?>
                                        <?php if ($item['type'] === 'group'): ?>
                                            <?php foreach ($item['children'] as $child): ?>
                                                <th><?php echo $child['label']; ?><br><i><?php echo $child['label_en']; ?></i></th>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endif; ?>
                            <!-- BARIS PENOMORAN (1), (2), (3) ... -->
                            <tr class="num-row">
                                <?php
                                $col_count_n = 0;
                                foreach ($columns as $idx_c => $col):
                                    $col_count_n++;
                                    echo '<th>(' . $col_count_n . ')</th>';
                                endforeach;
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $kabCount = 0;
                            // Cari Index Deteksi Kolom Kabupaten & Tahun
                            $kabKey = '';
                            $thKey = '';
                            $blnKey = '';
                            $codeKey = '';
                            $codeCheckKeys = ['kode_bps', 'kode_kabupaten', 'kode_kabupaten_kota', 'kode_wilayah', 'kod_wil', 'kode_wil', 'kabupaten_kode', 'kab_id'];

                            foreach ($columns as $c_key) {
                                $lowC = strtolower($c_key);
                                if (empty($kabKey) && strpos($lowC, 'kode') === false && (strpos($lowC, 'kabupaten') !== false || strpos($lowC, 'kota') !== false || strpos($lowC, 'wilayah') !== false || strpos($lowC, 'kabkot') !== false || strpos($lowC, 'kab') !== false)) {
                                    $kabKey = $c_key;
                                }
                                if (empty($thKey) && (strpos($lowC, 'tahun') !== false || strpos($lowC, 'year') !== false)) {
                                    $thKey = $c_key;
                                }
                                if (empty($blnKey) && (strpos($lowC, 'bulan') !== false || strpos($lowC, 'month') !== false || strpos($lowC, 'bln') !== false)) {
                                    $blnKey = $c_key;
                                }
                                if (empty($codeKey) && (strpos($lowC, 'kode') !== false || strpos($lowC, 'kod') !== false || strpos($lowC, 'kd') !== false || strpos($lowC, 'bps') !== false)) {
                                    $codeKey = $c_key;
                                }
                            }
                            if (empty($kabKey)) $kabKey = $columns[0];

                            // Pre-calculate Rowspans separately for Year, Month and Kabupaten
                            $thRowspan = [];
                            $lastTh = null;
                            $thStartIndices = [];

                            $blnRowspan = [];
                            $lastThBln = null;
                            $blnStartIndices = [];

                            $kabRowspan = [];
                            $lastThBlnKab = null;
                            $kabStartIndices = [];

                            foreach ($rows as $r_idx => $r) {
                                $thVal = (string)($r[$thKey] ?? '');
                                $blnVal = (string)($r[$blnKey] ?? '');
                                $kabVal = (string)($r[$kabKey] ?? '');

                                // Logic for Year
                                if ($thVal !== $lastTh) {
                                    $thRowspan[$r_idx] = 0;
                                    $thStartIndices[count($thStartIndices)] = $r_idx;
                                    $lastTh = $thVal;
                                }
                                $thRowspan[$thStartIndices[count($thStartIndices) - 1]]++;

                                // Logic for Month (Nested within Year)
                                $thBlnKey = $thVal . '||' . $blnVal;
                                if ($thBlnKey !== $lastThBln) {
                                    $blnRowspan[$r_idx] = 0;
                                    $blnStartIndices[count($blnStartIndices)] = $r_idx;
                                    $lastThBln = $thBlnKey;
                                }
                                $blnRowspan[$blnStartIndices[count($blnStartIndices) - 1]]++;

                                // Logic for Kabupaten (Nested within Year and Month)
                                $thBlnKabKey = $thVal . '||' . $blnVal . '||' . $kabVal;
                                if ($thBlnKabKey !== $lastThBlnKab) {
                                    $kabRowspan[$r_idx] = 0;
                                    $kabStartIndices[count($kabStartIndices)] = $r_idx;
                                    $lastThBlnKab = $thBlnKabKey;
                                }
                                $kabRowspan[$kabStartIndices[count($kabStartIndices) - 1]]++;
                            }

                            foreach ($rows as $r_idx => $row):
                                $isFirstTh = isset($thRowspan[$r_idx]);
                                $isFirstBln = isset($blnRowspan[$r_idx]);
                                $isFirstKab = isset($kabRowspan[$r_idx]);
                                if ($isFirstKab) $kabCount++;
                                $rowYearValue = (string)($row[$thKey] ?? '');
                            ?>
                                <tr class="<?php echo $isFirstKab ? 'new-kab' : ''; ?>" data-tahun="<?php echo $rowYearValue; ?>">
                                    <?php
                                    foreach ($columns as $c_idx => $c_key):
                                        $val = $row[$c_key] ?? '';

                                        // Case 1: Kolom Tahun (Merge)
                                        if ($c_key === $thKey) {
                                            if ($isFirstTh) {
                                                echo '<td class="val-col" rowspan="' . $thRowspan[$r_idx] . '"><b>' . htmlspecialchars($val) . '</b></td>';
                                            }
                                            continue;
                                        }

                                        // Case 1.1: Kolom Bulan (Merge)
                                        if ($blnKey && $c_key === $blnKey) {
                                            if ($isFirstBln) {
                                                echo '<td class="val-col" rowspan="' . $blnRowspan[$r_idx] . '">' . htmlspecialchars((string)$val) . '</td>';
                                            }
                                            continue;
                                        }

                                        // Case 2: Kolom Kabupaten (Merge)
                                        if ($c_key === $kabKey) {
                                            if ($isFirstKab) {
                                                $kabDisplay = (string)$val;
                                                $lowDisp = strtolower($kabDisplay);

                                                // Jika tidak mengandung "kabupaten" atau "kota" sama sekali
                                                if (strpos($lowDisp, 'kabupaten') === false && strpos($lowDisp, 'kota') === false) {
                                                    $curCode = (int)($row[$codeKey] ?? 0);
                                                    $last2 = $curCode % 100;

                                                    if ($last2 >= 70 && $last2 <= 79) {
                                                        $kabDisplay = 'Kota ' . $kabDisplay;
                                                    } else if ($last2 > 0 && $last2 < 70) {
                                                        $kabDisplay = 'Kabupaten ' . $kabDisplay;
                                                    }
                                                }

                                                // Normalisasi Penamaan (Ganti Kab., Kodya, Madya menjadi Kabupaten/Kota lengkap)
                                                // 1. Bersihkan prefix lama
                                                $cleanName = trim(str_ireplace(['kabupaten', 'kota', 'kab.', 'kodya', 'madya', 'kabupatenkota_data'], '', $kabDisplay));
                                                // 2. Pasang kembali sesuai tipe (Kota jika kodenya 7x atau mengandung kata kota asli)
                                                // Deteksi Tipe Menggunakan Mapping Standar BPS Jawa Tengah
                                                if (!isset($jatengOrder)) {
                                                    $jatengOrder = [
                                                        'cilacap' => 1,
                                                        'banyumas' => 2,
                                                        'purbalingga' => 3,
                                                        'banjarnegara' => 4,
                                                        'kebumen' => 5,
                                                        'purworejo' => 6,
                                                        'wonosobo' => 7,
                                                        'magelang' => 8,
                                                        'boyolali' => 9,
                                                        'klaten' => 10,
                                                        'sukoharjo' => 11,
                                                        'wonogiri' => 12,
                                                        'karanganyar' => 13,
                                                        'sragen' => 14,
                                                        'grobogan' => 15,
                                                        'blora' => 16,
                                                        'rembang' => 17,
                                                        'pati' => 18,
                                                        'kudus' => 19,
                                                        'jepara' => 20,
                                                        'demak' => 21,
                                                        'semarang' => 22,
                                                        'temanggung' => 23,
                                                        'kendal' => 24,
                                                        'batang' => 25,
                                                        'pekalongan' => 26,
                                                        'pemalang' => 27,
                                                        'tegal' => 28,
                                                        'brebes' => 29,
                                                        'kota magelang' => 30,
                                                        'kota surakarta' => 31,
                                                        'kota salatiga' => 32,
                                                        'kota semarang' => 33,
                                                        'kota pekalongan' => 34,
                                                        'kota tegal' => 35
                                                    ];
                                                }

                                                $curCode = (int)($row[$codeKey] ?? 0);
                                                $last2 = $curCode % 100;
                                                $base = trim(strtolower(str_ireplace(['kab.', 'kabupaten', 'kota', 'kodya'], '', $kabDisplay)));
                                                $actualPrefixKota = (strpos($lowDisp, 'kota') !== false || strpos($lowDisp, 'kodya') !== false || strpos($lowDisp, 'madya') !== false);

                                                // Tentukan posisi/tipe
                                                $pos = 0;
                                                if ($last2 >= 70 && $last2 <= 79) $pos = 30; // Force Kota by Code
                                                else if ($last2 > 0 && $last2 < 70) $pos = 1; // Force Kabupaten by Code
                                                else if ($actualPrefixKota) $pos = $jatengOrder['kota ' . $base] ?? 30;
                                                else $pos = $jatengOrder[$base] ?? ($jatengOrder['kota ' . $base] ?? 1);

                                                $tipe = ($pos >= 30) ? 'Kota' : 'Kabupaten';

                                                // Khusus Jawa Tengah / Provinsi (Biasanya kode 3300 atau nama Jawa Tengah)
                                                if (($curCode == 3300 || ($last2 == 0 && $curCode != 0)) || stripos($cleanName, 'jawa tengah') !== false || stripos($cleanName, 'provinsi') !== false) {
                                                    $kabDisplay = $cleanName;
                                                } else {
                                                    $kabDisplay = $tipe . ' ' . $cleanName;
                                                }

                                                // Konversi ke Title Case (Setiap awal kata huruf kapital)
                                                $kabDisplay = ucwords(strtolower($kabDisplay));

                                                echo '<td class="kab-col" rowspan="' . $kabRowspan[$r_idx] . '">' . htmlspecialchars($kabDisplay) . '</td>';
                                            }
                                            continue;
                                        }

                                        // Case 2.1: Kolom Kode Wilayah (Merge)
                                        if ($codeKey && $c_key === $codeKey) {
                                            if ($isFirstKab) {
                                                echo '<td class="val-col" rowspan="' . $kabRowspan[$r_idx] . '">' . htmlspecialchars((string)$val) . '</td>';
                                            }
                                            continue;
                                        }

                                        // Case 3: Kolom Data Biasa
                                    ?>
                                        <td class="val-col">
                                            <?php
                                            $outVal = (string)$val;
                                            // Jika isinya string (bukan angka murni) dan huruf kapital semua, ubah ke Title Case
                                            if (!is_numeric($outVal) && $outVal === strtoupper($outVal)) {
                                                $outVal = ucwords(strtolower($outVal));
                                            }
                                            echo htmlspecialchars($outVal);
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="<?php echo count($columns); ?>" style="border-top:1px solid #000"></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div style="font-size: 11px; margin-top: 10px; opacity: 0.6; line-height: 1.5;">
                        <div><b>Catatan/</b><i>Note</i>: Data berasal dari Portal Data Jawa Tengah (API ID: <?php echo $api_result['id_api'] ?? ''; ?>)</div>
                        <div><b>Sumber/</b><i>Source</i>: <?php echo htmlspecialchars($api_result['unitkerja_ind'] ?? '-'); ?></i></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <!-- Modal Progress Bar Export -->
    <div id="export-progress-container">
        <div class="export-header">
            <div class="export-title">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </div>
        </div>
        <div class="export-progress-bg">
            <div id="export-progress-fill" class="export-progress-fill"></div>
        </div>
        <div class="export-status">
            <span id="export-status-text">Memproses...</span>
            <span id="export-percent-text">0%</span>
        </div>
    </div>
</div>