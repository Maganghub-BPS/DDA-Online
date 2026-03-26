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
// FUNGSI PINTAR UNTUK MENYUNSUN HEADER BERTINGKAT (AUTO-RECOGNITION)
// -------------------------------------------------------------------------
function parse_nested_headers($columns)
{
    $header_structure = [];
    $raw_labels = [];

    // 1. Dapatkan label bersih (Indo & English jika tersedia)
    foreach ($columns as $idx => $col) {
        $raw_labels[$idx] = get_dda_label_nested($col);
    }

    $i = 0;
    while ($i < count($columns)) {
        // Skip kolom pertama (biasanya Kabupaten/Kota atau Tahun)
        if ($i === 0) {
            $header_structure[] = [
                'type' => 'kab',
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
        // Atau kata pertama saja
        $prefix = (count($parts) > 1) ? ($parts[0] . ' ' . $parts[1]) : $parts[0];

        // Cek apakah 2 kata pertama ini umum digunakan sebagai prefix grup
        // Jika tidak, cek 1 kata saja
        $keywords = ['kayu bulat', 'kayu olahan', 'luas areal', 'tenaga kerja', 'hasil hutan', 'jumlah izin'];
        $found_prefix = '';
        foreach ($keywords as $kw) {
            if (stripos($label, $kw) === 0) {
                $found_prefix = ucwords($kw);
                break;
            }
        }

        if (empty($found_prefix) && count($parts) > 0) {
            // Fallback: anggap kata pertama adalah prefix jika ada kolom lain yang sama
            $found_prefix = $parts[0];
        }

        // Cari seberapa banyak kolom yang punya prefix yang sama
        $count = 1;
        $sub_labels = [];
        $sub_labels[] = [
            'label' => trim(str_ireplace($found_prefix, '', $label)),
            'label_en' => $label_en,
            'col_idx' => $i
        ];

        for ($j = $i + 1; $j < count($columns); $j++) {
            if (!empty($found_prefix) && stripos($raw_labels[$j][0], $found_prefix) === 0) {
                // Pastikan bukan sekadar label yang sama persis (hindari grup jika isinya kosong semua)
                $next_label = $raw_labels[$j][0];
                $sub_candidate = trim(str_ireplace($found_prefix, '', $next_label));
                
                // Jika label anak ternyata kosong (karena sama dengan induk), ini per kolom yang sama
                // Kita izinkan jika ada setidaknya 2 kolom yang share prefix tapi punya sisa (anak) yang berbeda
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

        // Cek apakah minimal salah satu anak punya label (tidak kosong setelah prefix dibuang)
        $has_real_children = false;
        if ($count > 1) {
            foreach ($sub_labels as $sb) {
                if (trim(str_ireplace($found_prefix, '', $raw_labels[$sb['col_idx']][0])) !== '') {
                    $has_real_children = true;
                    break;
                }
            }
        }

        if ($count > 1 && $has_real_children) {
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
        'tahun_data' => ['Tahun', 'Year'],
        'tahun' => ['Tahun', 'Year'],
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
        'Jumlah Izin'  => 'Number of Permits'
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
    <div class="float-end">
        <button onclick="exportTableToExcel('dda-container-all', 'portal-data-export')" class="btn-rounded-modern export-btn-modern shadow-primary">
            <i class="bi bi-file-earmark-excel"></i> EXPORT ALL TO EXCEL
        </button>
    </div>


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
                    $columns = array_keys($rows[0]);
                } else {
                    continue; // Skip if no rows
                }

                $structure = parse_nested_headers($columns);
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
                                    <?php if ($item['type'] === 'kab'): ?>
                                        <th rowspan="<?php echo $has_group ? 2 : 1; ?>" colspan="2"><?php echo $item['label']; ?><br><i><?php echo $item['label_en']; ?></i></th>
                                    <?php elseif ($item['type'] === 'single'): ?>
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
                                <th colspan="2">(1)</th>
                                <?php
                                $col_count_n = 1;
                                foreach ($columns as $idx_c => $col):
                                    if ($idx_c == 0) continue;
                                    $col_count_n++;
                                    echo '<th>(' . $col_count_n . ')</th>';
                                endforeach;
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $lastKab = '';
                            $kabCount = 0;
                            foreach ($rows as $row):
                                $currentKab = reset($row);
                                $isNewKab = ($currentKab !== $lastKab);
                                if ($isNewKab) {
                                    $lastKab = $currentKab;
                                    $kabCount++;
                                }
                            ?>
                                <tr class="<?php echo $isNewKab ? 'new-kab' : ''; ?>">
                                    <td class="num-col"><?php echo $isNewKab ? $kabCount : ''; ?></td>
                                    <td class="kab-col"><?php echo $isNewKab ? htmlspecialchars($currentKab) : ''; ?></td>

                                    <?php
                                    $first = true;
                                    foreach ($row as $key => $val):
                                        if ($first) {
                                            $first = false;
                                            continue;
                                        } // Skip first column (dimensi)
                                    ?>
                                        <td class="val-col">
                                            <?php echo htmlspecialchars($val); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="<?php echo count($columns) + 1; ?>" style="border-top:1px solid #000"></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div style="font-size: 11px; margin-top: 10px; opacity: 0.6;">
                        <b>Catatan/</b><i>Note</i>: Data berasal dari Portal Data Jawa Tengah (API ID: <?php echo $api_result['id_api'] ?? ''; ?>)
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