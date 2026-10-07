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
    if (!empty($api_errors)) {
        $errorMsg = implode('<br>', $api_errors);
    } else {
        $errorMsg = 'Tidak ada data yang diterima dari API.';
    }
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
        $found_prefix = '';

        // --- CUSTOM: Support for forced pivot headers via || separator ---
        if (strpos($label, ' || ') !== false) {
            $parts_p = explode(' || ', $label);
            $found_prefix = $parts_p[0];
        }

        $parts = explode(' ', $label);

        // Prefix ditentukan dari 2 kata pertama jika cocok dengan kata kunci umum DDA
        $keywords = ['kayu bulat', 'kayu olahan', 'luas areal', 'tenaga kerja', 'hasil hutan', 'jumlah izin', 'produksi', 'populasi', 'hak guna', 'hak pengelolaan', 'hak milik'];
        if (empty($found_prefix)) {
            foreach ($keywords as $kw) {
                if (stripos($label, $kw) === 0) {
                    $found_prefix = ucwords($kw);
                    break;
                }
            }
        }

        if (empty($found_prefix) && count($parts) > 0) {
            $found_prefix = $parts[0];
        }

        // Cari seberapa banyak kolom yang punya prefix yang sama
        $count = 1;
        $sub_labels = [];
        $clean_label = trim(str_ireplace([$found_prefix . ' || ', $found_prefix], ['', ''], $label)) ?: $label;

        $sub_labels[] = [
            'label' => $clean_label,
            'label_en' => $label_en,
            'col_idx' => $i
        ];

        for ($j = $i + 1; $j < count($columns); $j++) {
            $next_full_label = $raw_labels[$j][0];
            $is_match = false;

            // Check match via || or prefix
            if (strpos($next_full_label, $found_prefix . ' || ') === 0) $is_match = true;
            elseif (!empty($found_prefix) && stripos($next_full_label, $found_prefix) === 0) $is_match = true;

            if ($is_match) {
                $next_label = $raw_labels[$j][0];
                $sub_candidate = trim(str_ireplace([$found_prefix . ' || ', $found_prefix], ['', ''], $next_label));
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
        // Jika dia grup tapi semua label anaknya sama (hanya 1 anak), kita paksa JADI grup jika dia hasil PIVOT (ada separator ||)
        $is_valid_group = false;
        if ($count > 1) {
            foreach ($sub_labels as $sb) {
                if ($sb['label'] != $label) {
                    $is_valid_group = true;
                    break;
                }
            }
        } else if (strpos($label, ' || ') !== false) {
            $is_valid_group = true; // Paksa grup untuk PIVO meskipun cuma 1 kategori (sesuai permintaan user gambarnya multilevel)
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
        'tahun_data' => ['Akhir Tahun', 'End of Year'],
        'tahun' => ['Akhir Tahun', 'End of Year'],
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
        // Parity with Image 2 mapping
        'negara_km' => ['Negara', 'State'],
        'provinsi_km' => ['Provinsi', 'Province'],
        'kab_kota_km' => ['Kabupaten/Kota', 'Regency/Municipality'],
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
        padding: 24px clamp(20px, 2.5vw, 36px) 36px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        color: #0f172a;
        line-height: 1.5;
        width: 100%;
        margin: 0 0 40px 0;
        overflow-x: hidden;
        display: block;
        box-sizing: border-box;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        position: relative;
        min-height: 480px;
    }

    /* Skeleton Loading Overlay (Identik dengan Spreadsheet) */
    .table-skeleton-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        z-index: 20;
        display: flex;
        flex-direction: column;
        padding: 24px clamp(20px, 2.5vw, 36px);
        box-sizing: border-box;
        border-radius: 14px;
        transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s ease;
        overflow: hidden;
        user-select: none;
        pointer-events: none;
    }

    .table-skeleton-overlay.is-hidden {
        opacity: 0;
        visibility: hidden;
    }

    /* Skeleton Table Styles */
    .table-skeleton-overlay .skeleton-table-wrapper {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        min-height: 400px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
    }

    .table-skeleton-overlay .skeleton-table-row {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        gap: 16px;
    }

    .table-skeleton-overlay .skeleton-table-row:nth-child(even) {
        background-color: #fafbfc;
    }

    .table-skeleton-overlay .skeleton-header-row {
        background: #f1f5f9 !important;
        border-bottom: 2px solid #cbd5e1;
        padding: 12px 14px;
    }

    .table-skeleton-overlay .skeleton-header-row .skeleton-cell .skeleton-shimmer {
        height: 15px;
        background: linear-gradient(90deg, #cbd5e1 0%, #e2e8f0 50%, #cbd5e1 100%);
        background-size: 200% 100%;
    }

    .table-skeleton-overlay .skeleton-footer-row {
        background: #f8fafc !important;
        border-top: 2px solid #cbd5e1;
        border-bottom: none;
        margin-top: auto;
        padding: 11px 14px;
    }

    .table-skeleton-overlay .skeleton-cell {
        display: flex;
        align-items: center;
    }

    .table-skeleton-overlay .skeleton-cell-no {
        width: 36px;
        flex-shrink: 0;
        justify-content: center;
    }

    .table-skeleton-overlay .skeleton-cell-name {
        width: 35%;
        min-width: 140px;
        flex-grow: 1;
    }

    .table-skeleton-overlay .skeleton-cell-val {
        width: 18%;
        min-width: 80px;
        justify-content: flex-end;
    }

    .table-skeleton-overlay .skeleton-cell-total {
        width: 20%;
        min-width: 90px;
        justify-content: flex-end;
    }

    /* Shimmer Animation */
    .table-skeleton-overlay .skeleton-shimmer {
        display: inline-block;
        height: 12px;
        width: 100%;
        border-radius: 4px;
        background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
        background-size: 200% 100%;
        animation: skeleton-shimmer-wave 1.6s infinite linear;
    }

    @keyframes skeleton-shimmer-wave {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    /* Force parent containers in the layout to stay within screen width */
    #content, .main-content, .content-body {
        overflow-x: hidden !important;
        max-width: 100% !important;
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
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        background: var(--bps-orange, #F26522); /* Orange Header like Image 4 */
        color: white;
    }

    .main-table thead tr.num-row th {
        font-weight: normal;
        padding: 2px;
        background: #e05e15; /* Darker orange for num-row */
        font-size: 10px;
        color: white;
    }

    .main-table tbody td {
        border: 1px solid #ccc;
        border-left: 1px solid #000;
        border-right: 1px solid #000;
        padding: 6px 8px;
        font-size: 12px;
        vertical-align: top;
    }

    .table-responsive-dda {
        width: 100%;
        max-width: 100%; /* Penting: Batasi lebar maksimal */
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 25px;
        background: #fff;
        border: 1px solid #f1f5f9; /* Bingkai halus */
        border-radius: 12px;
    }
    
    .table-responsive-dda thead {
        position: static !important;
        box-shadow: none !important;
    }
    
    .table-responsive-dda thead th {
        position: static !important;
        background-clip: border-box;
    }

    /* Scrollbar style yang lebih terlihat */
    .table-responsive-dda::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    .table-responsive-dda::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    .table-responsive-dda::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .table-responsive-dda::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Agar tabel tidak "penyek" saat kolom banyak */
    .table-responsive-dda table {
        min-width: 100%;
        width: max-content !important;
    }

    .main-table tbody tr.new-kab td {
        border-top: 1px solid #000;
    }

    .main-table tfoot td {
        border-top: 2px solid #000;
        font-size: 12px;
        font-weight: bold;
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
        padding: 6px 18px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Responsiveness for small screens */
    @media (max-width: 576px) {
        .btn-rounded-modern {
            width: 100%;
            justify-content: center;
            padding: 10px 16px;
        }

        .filter-box-modern {
            width: 100%;
            justify-content: center;
        }
    }

    .btn-back-modern {
        background: #f8fafc;
        color: #64748b;
        text-decoration: none;
        border: 1px solid #e2e8f0;
    }

    .btn-back-modern:hover {
        background: #fff;
        color: #475569;
        border-color: #cbd5e1;
        text-decoration: none;
    }

    .export-btn-modern {
        background: #FF6D1F;
        color: #fff;
    }

    .export-btn-modern:hover {
        background: #e05e15;
        color: #fff;
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

    /* Config Modal Styles */
    #configModal .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    }

    #configModal .modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #eef2f7;
        border-radius: 20px 20px 0 0;
        padding: 20px 25px;
    }

    #configModal .nav-tabs {
        border: none;
        gap: 10px;
        margin-bottom: 20px;
    }

    #configModal .nav-link {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        font-weight: 600;
        padding: 8px 20px;
    }

    #configModal .nav-link.active {
        background: #FF6D1F;
        color: #fff;
        border-color: #FF6D1F;
    }

    .col-item {
        background: #f8fafc;
        padding: 12px 15px;
        border-radius: 12px;
        margin-bottom: 8px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.2s;
    }

    .col-item:hover {
        border-color: #cbd5e1;
        background: #fff;
    }

    .col-item input[type="text"] {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: 12px;
    }

    .badge-pivot {
        background: #e0f2fe;
        color: #0369a1;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 700;
    }
</style>

    <style>
        .header-modern-v2 {
            background: #ffffff; 
            padding: 14px 0 18px; 
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        }

        .table-top-bar {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: nowrap !important;
            width: 100%;
        }

        .table-breadcrumb-nav {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .table-breadcrumb-nav .breadcrumb {
            font-size: 0.85rem;
            font-weight: 500;
            margin: 0;
            padding: 0;
            background: transparent;
            display: flex;
            align-items: center;
            flex-wrap: nowrap !important;
            gap: 6px;
            line-height: 1.4;
            overflow: hidden;
            white-space: nowrap;
        }
        .table-breadcrumb-nav .breadcrumb-item {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .table-breadcrumb-nav .breadcrumb-item a {
            color: #0275d8;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 600;
            white-space: nowrap;
        }
        .table-breadcrumb-nav .breadcrumb-item a:hover {
            color: #01447e;
            text-decoration: underline;
        }
        .table-breadcrumb-nav .breadcrumb-item.active {
            color: #64748b;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            font-weight: 600;
            min-width: 0;
            flex-shrink: 1;
        }

        .table-actions-dropdown {
            flex-shrink: 0;
            margin-left: auto;
        }

        /* --- TITLE AREA GAYA GAMBAR 2 (ELEGAN, OTENTIK BPS, BERSIH) --- */
        .title-area-row {
            display: flex;
            align-items: center;
            gap: 28px;
            width: 100%;
            padding: 2px 0;
        }

        .tabel-label-box {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            background: transparent;
            border: none;
            padding: 0;
            flex-shrink: 0;
        }
        .tabel-label-box .tabel-ind-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .tabel-label-box .tabel-ind {
            font-weight: 800; 
            font-size: 0.88rem; 
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 2px; 
            margin-bottom: 2px;
            line-height: 1.1;
            color: #0f172a;
            text-align: center;
            letter-spacing: -0.2px;
        }
        .tabel-label-box .tabel-en {
            font-style: italic; 
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
            line-height: 1.1;
            text-align: center;
        }
        .tabel-label-box .tabel-number {
            font-size: 1.65rem; 
            font-weight: 800; 
            line-height: 1;
            color: #0d2c4d;
            letter-spacing: -0.4px;
            display: flex;
            align-items: center;
        }

        .title-details-col {
            flex: 1;
            min-width: 0;
            border-left: 2px solid #cbd5e1;
            padding: 4px 0 4px 24px;
        }

        .table-opd-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(21, 70, 121, 0.08);
            color: var(--bps-blue, #154679);
            border: 1px solid rgba(21, 70, 121, 0.16);
            border-radius: 6px;
            padding: 2.5px 10px;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 8px;
            max-width: 100%;
            word-break: break-word;
        }

        .table-main-title {
            font-size: clamp(1.08rem, 1.3vw, 1.25rem);
            font-weight: 700;
            color: #0f172a;
            line-height: 1.48;
            margin: 0 0 6px 0;
            word-break: break-word;
            overflow-wrap: break-word;
            letter-spacing: -0.2px;
        }

        .table-sub-title {
            font-size: clamp(0.85rem, 1vw, 0.92rem);
            font-style: italic;
            font-weight: 500;
            color: #64748b;
            line-height: 1.48;
            margin: 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        @media (max-width: 767.98px) {
            .header-modern-v2 {
                padding: 10px 0 14px;
                margin-bottom: 16px;
            }
            .table-top-bar {
                margin-bottom: 12px;
                padding-bottom: 8px;
                flex-wrap: nowrap !important;
                gap: 8px;
            }
            .table-breadcrumb-nav .breadcrumb {
                font-size: 0.78rem;
                gap: 4px;
            }
            .table-breadcrumb-nav .breadcrumb-item.active {
                max-width: 130px;
            }
            .table-actions-dropdown .btn {
                padding: 5px 12px !important;
                font-size: 12px !important;
            }
            .title-area-row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
                padding: 0 !important;
            }
            .tabel-label-box {
                gap: 12px;
                padding: 0;
            }
            .tabel-label-box .tabel-ind {
                font-size: 0.8rem;
            }
            .tabel-label-box .tabel-en {
                font-size: 0.68rem;
            }
            .tabel-label-box .tabel-number {
                font-size: 1.45rem;
            }
            .title-details-col {
                border-left: none !important;
                padding-left: 0 !important;
                width: 100%;
            }
            .table-opd-badge {
                font-size: 0.74rem;
                padding: 2px 8px;
                margin-bottom: 6px;
                line-height: 1.3;
            }
            .table-main-title {
                font-size: 1.02rem;
                line-height: 1.48;
                margin-bottom: 6px;
            }
            .table-sub-title {
                font-size: 0.84rem;
                line-height: 1.48;
            }
        }

        @media (max-width: 480px) {
            .table-breadcrumb-nav .breadcrumb {
                font-size: 0.74rem;
            }
            .table-breadcrumb-nav .breadcrumb-item.active {
                max-width: 90px;
            }
            .table-actions-dropdown .btn {
                padding: 5px 10px !important;
                font-size: 11.5px !important;
            }
        }
    </style>

<?php
$primary_result = !empty($api_results) ? $api_results[0] : null;
if ($primary_result):
    $portal_title_main = $primary_result['title'] ?? $primary_result['nama'] ?? $primary_result['data']['title'] ?? 'Tabel Data Portal';
    $dda_title_main = $primary_result['dda_title'] ?? '';
    $dda_title_en_main = $primary_result['dda_title_en'] ?? '';

    $display_title_main = $dda_title_main ?: $portal_title_main;
    $display_title_main = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $display_title_main);

    $tabel_nomor_main = '';
    $judul_only_main = $display_title_main;
    if (preg_match('/^Tabel\s+([\d\.\w]+)\s+(.*?)(?:,\s*(\d{4}(?:-\d{4})?))?$/is', trim($display_title_main), $m)) {
        $tabel_nomor_main = $m[1];
        $judul_only_main = trim($m[2]);
        if (isset($m[3]) && $m[3]) {
            $judul_only_main .= ', ' . $m[3];
        }
    }
    $judul_only_main = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $judul_only_main);

    // Format judul ringkas untuk breadcrumb agar tidak panjang dan merusak baris
    $core_breadcrumb_main = preg_split('/\s+(Menurut|Berdasarkan|\()\s+/i', $judul_only_main)[0];
    $core_breadcrumb_main = trim($core_breadcrumb_main);
    if (!empty($tabel_nomor_main)) {
        $breadcrumb_label_main = 'Tabel ' . $tabel_nomor_main . ' - ' . mb_strimwidth($core_breadcrumb_main, 0, 32, '...');
    } else {
        $breadcrumb_label_main = mb_strimwidth($core_breadcrumb_main, 0, 38, '...');
    }

    $eng_title_raw_main = $dda_title_en_main ?: ($primary_result['title_en'] ?? '');
    $eng_title_raw_main = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $eng_title_raw_main);

    $judul_en_only_main = $eng_title_raw_main;
    if (preg_match('/^Table\s+([\d\.\w]+)\s+(.*?)(?:,\s*(\d{4}(?:-\d{4})?))?$/is', trim($eng_title_raw_main), $m)) {
        $judul_en_only_main = trim($m[2]);
        if (isset($m[3]) && $m[3]) {
            $judul_en_only_main .= ', ' . $m[3];
        }
    }
    $judul_en_only_main = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $judul_en_only_main);

    $unitkerja_nm_main = $primary_result['unitkerja_ind'] ?? 'Provinsi Jawa Tengah';
    if ($unitkerja_nm_main === '-' || empty($unitkerja_nm_main)) {
        $unitkerja_nm_main = 'Provinsi Jawa Tengah';
    }
?>
<div class="header-modern-v2">
    <div class="container-fluid" style="max-width: 1680px; margin: 0 auto; padding: 0 clamp(16px, 3vw, 36px);">
        <!-- Top Action Bar -->
        <div class="table-top-bar d-flex align-items-center justify-content-between gap-2">
            <!-- Left: Breadcrumb -->
            <nav aria-label="breadcrumb" class="table-breadcrumb-nav">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= base_url('/') ?>"><i class="fa-solid fa-house-chimney me-1"></i>Beranda</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= base_url('home/search') ?>">Jelajah Data</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" title="<?= htmlspecialchars($judul_only_main) ?>">
                        <span class="d-none d-md-inline"><?= htmlspecialchars($breadcrumb_label_main) ?></span>
                        <span class="d-inline d-md-none"><?= htmlspecialchars($tabel_nomor_main ? 'Tabel ' . $tabel_nomor_main : 'Detail') ?></span>
                    </li>
                </ol>
            </nav>
            
            <!-- Right: Unduh Data -->
            <div class="table-actions-dropdown dropdown">
                <button class="btn d-flex align-items-center gap-2 dropdown-toggle px-3 py-1.5 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: var(--bps-blue, #0d2c4d); color: white; border: none; border-radius: 50px; font-weight: 600; font-size: 13px; transition: all 0.2s; white-space: nowrap;">
                    <i class="fa-solid fa-cloud-arrow-down"></i> <span class="d-none d-sm-inline">Unduh Data</span><span class="d-inline d-sm-none">Unduh</span>
                </button>
                <?php 
                    $clean_nomor = trim((string)($tabel_nomor_main ?? ''));
                    $clean_judul = trim(preg_replace('/[\r\n\t]+/', ' ', (string)($judul_only_main ?? '')));
                    if (!empty($clean_nomor)) {
                        $full_title_for_export = 'Tabel ' . $clean_nomor . ' ' . preg_replace('/^Tabel\s+[\d\.\w]+\s+/i', '', $clean_judul);
                    } else {
                        $full_title_for_export = $clean_judul;
                    }
                ?>
                <ul class="dropdown-menu shadow dropdown-menu-end" style="border-radius: 12px; border: 1px solid #e2e8f0;">
                    <li><a class="dropdown-item py-2" href="#" data-export-title="<?php echo htmlspecialchars($full_title_for_export, ENT_QUOTES, 'UTF-8'); ?>" onclick="exportTableToExcel('dda-table-0', this.getAttribute('data-export-title'))"><i class="fa-solid fa-file-excel text-success me-2"></i> Unduh Format Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item py-2" href="#" 
                           data-nomor="<?php echo htmlspecialchars($tabel_nomor_main, ENT_QUOTES, 'UTF-8'); ?>"
                           data-judul-id="<?php echo htmlspecialchars($judul_only_main, ENT_QUOTES, 'UTF-8'); ?>"
                           data-judul-en="<?php echo htmlspecialchars($judul_en_only_main, ENT_QUOTES, 'UTF-8'); ?>"
                           onclick="exportTableToPDF('dda-table-0', this.getAttribute('data-nomor'), this.getAttribute('data-judul-id'), this.getAttribute('data-judul-en'))">
                           <i class="fa-solid fa-file-pdf text-danger me-2"></i> Unduh Format PDF
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Title Area -->
        <div class="title-area-row">
            <!-- Tabel Number Block -->
            <div class="tabel-label-box">
                <div class="tabel-ind-group">
                    <div class="tabel-ind">Tabel</div>
                    <div class="tabel-en">Table</div>
                </div>
                <div class="tabel-number">
                    <?= htmlspecialchars($tabel_nomor_main ?: '-') ?>
                </div>
            </div>
            
            <!-- Title Details -->
            <div class="title-details-col">
                <div class="table-opd-badge">
                    <i class="fa-solid fa-building-columns"></i>
                    <span><?= htmlspecialchars($unitkerja_nm_main) ?></span>
                </div>
                <h1 class="table-main-title"><?= htmlspecialchars($judul_only_main) ?></h1>
                <?php if (!empty($judul_en_only_main)): ?>
                    <p class="table-sub-title"><?= htmlspecialchars($judul_en_only_main) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid" style="max-width: 1680px; margin: 0 auto; padding: 0 clamp(16px, 3vw, 36px);">
    <div class="dda-body">

        <!-- Skeleton Loading Screen (Identik dengan Spreadsheet) -->
        <div id="table-skeleton-overlay" class="table-skeleton-overlay">
            <div class="skeleton-table-wrapper">
                <!-- Header Row -->
                <div class="skeleton-table-row skeleton-header-row">
                    <div class="skeleton-cell skeleton-cell-no"><span class="skeleton-shimmer"></span></div>
                    <div class="skeleton-cell skeleton-cell-name"><span class="skeleton-shimmer"></span></div>
                    <div class="skeleton-cell skeleton-cell-val"><span class="skeleton-shimmer"></span></div>
                    <div class="skeleton-cell skeleton-cell-val"><span class="skeleton-shimmer"></span></div>
                    <div class="skeleton-cell skeleton-cell-val d-none d-md-flex"><span class="skeleton-shimmer"></span></div>
                    <div class="skeleton-cell skeleton-cell-total"><span class="skeleton-shimmer"></span></div>
                </div>

                <!-- Data Rows (10 Rows for full frame coverage) -->
                <?php 
                $sk_widths_name  = [72, 85, 60, 90, 78, 68, 82, 75, 88, 70];
                $sk_widths_val1  = [55, 70, 48, 65, 58, 72, 60, 52, 68, 62];
                $sk_widths_val2  = [60, 45, 68, 52, 64, 48, 62, 58, 50, 66];
                $sk_widths_val3  = [50, 62, 55, 70, 45, 66, 58, 60, 52, 58];
                $sk_widths_total = [65, 58, 72, 62, 68, 54, 70, 64, 75, 60];
                for ($k = 0; $k < 10; $k++): 
                ?>
                <div class="skeleton-table-row">
                    <div class="skeleton-cell skeleton-cell-no"><span class="skeleton-shimmer" style="width: 24px;"></span></div>
                    <div class="skeleton-cell skeleton-cell-name">
                        <span class="skeleton-shimmer" style="width: <?= $sk_widths_name[$k] ?>%;"></span>
                    </div>
                    <div class="skeleton-cell skeleton-cell-val">
                        <span class="skeleton-shimmer" style="width: <?= $sk_widths_val1[$k] ?>%;"></span>
                    </div>
                    <div class="skeleton-cell skeleton-cell-val">
                        <span class="skeleton-shimmer" style="width: <?= $sk_widths_val2[$k] ?>%;"></span>
                    </div>
                    <div class="skeleton-cell skeleton-cell-val d-none d-md-flex">
                        <span class="skeleton-shimmer" style="width: <?= $sk_widths_val3[$k] ?>%;"></span>
                    </div>
                    <div class="skeleton-cell skeleton-cell-total">
                        <span class="skeleton-shimmer" style="width: <?= $sk_widths_total[$k] ?>%;"></span>
                    </div>
                </div>
                <?php endfor; ?>

                <!-- Summary / Total Row -->
                <div class="skeleton-table-row skeleton-footer-row">
                    <div class="skeleton-cell skeleton-cell-no"></div>
                    <div class="skeleton-cell skeleton-cell-name"><span class="skeleton-shimmer" style="width: 45%; height: 14px;"></span></div>
                    <div class="skeleton-cell skeleton-cell-val"><span class="skeleton-shimmer" style="width: 70%; height: 14px;"></span></div>
                    <div class="skeleton-cell skeleton-cell-val"><span class="skeleton-shimmer" style="width: 65%; height: 14px;"></span></div>
                    <div class="skeleton-cell skeleton-cell-val d-none d-md-flex"><span class="skeleton-shimmer" style="width: 72%; height: 14px;"></span></div>
                    <div class="skeleton-cell skeleton-cell-total"><span class="skeleton-shimmer" style="width: 80%; height: 14px;"></span></div>
                </div>
            </div>
        </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .header-modern-v2, .header-modern-v2 *,
            #dda-container-all, #dda-container-all * {
                visibility: visible;
            }
            #dda-container-all {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .table-skeleton-overlay {
                display: none !important;
            }
            .btn-rounded-modern, .dropdown, .page-header, .table-top-bar {
                display: none !important;
            }
        }
    </style>

    <style>
        .filter-box-modern {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            padding: 2px 12px;
            /* Reduced from 4px 15px */
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            height: 34px;
            /* Reduced from 40px to match new button height */
        }

        .filter-box-modern:hover {
            border-color: #FF6D1F;
        }

        .form-select-modern {
            border: none;
            background: transparent;
            font-size: 0.8rem;
            /* Matched to buttons (from 0.85rem) */
            font-weight: 600;
            color: #475569;
            outline: none;
            cursor: pointer;
            padding-right: 5px;
        }
    </style>

    <script>
        function filterByYear() {
            const checkedYears = Array.from(document.querySelectorAll('.year-filter-check:checked')).map(cb => cb.value);
            currentSelectedYears = checkedYears; // Update global state

            const label = document.getElementById('year-filter-label');
            if (checkedYears.length === 0) label.innerText = "Tahun";
            else if (checkedYears.length === 1) label.innerText = "Tahun " + checkedYears[0];
            else label.innerText = checkedYears.length + " Tahun";

            const tables = document.querySelectorAll('.table-wrapper');
            tables.forEach((wrapper, idx) => {
                const customTable = wrapper.querySelector('.main-table');
                if (customTable && (currentTableConfig || customTable.innerHTML.includes('style='))) {
                    let config = currentTableConfig;
                    if (!config) {
                        const firstTable = rawApiResults[idx];
                        const data = getRowsFromApiResult(firstTable);
                        const keys = data.length > 0 ? Object.keys(data[0]) : [];
                        config = {
                            columns: keys.map(k => ({
                                key: k,
                                label_id: k,
                                label_en: '',
                                visible: true
                            })),
                            pivot: {
                                enabled: false
                            },
                            merge_datasets: false
                        };
                    }
                    renderCustomTable(idx, config);
                } else {
                    const rows = wrapper.querySelectorAll('.main-table tbody tr');
                    let hasVisibleRow = false;
                    rows.forEach(row => {
                        const rowYear = row.getAttribute('data-tahun');
                        if (checkedYears.length === 0 || checkedYears.includes(rowYear)) {
                            row.style.display = "";
                            hasVisibleRow = true;
                        } else {
                            row.style.display = "none";
                        }
                    });
                    wrapper.style.display = hasVisibleRow ? "" : "none";
                }
            });
        }

        function resetYearFilter() {
            document.querySelectorAll('.year-filter-check').forEach(cb => cb.checked = false);
            filterByYear();
        }
    </script>

    <?php echo view('admin/parts/js_export_excel'); ?>

    <?php if (empty($api_results)): ?>
        <div style="color: red; padding: 20px; border: 1px solid red;">Tidak ada data referensi yang ditemukan.</div>
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
                        // 1. Deteksi Kolom Kode Wilayah
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

                        // 2. Deteksi Kolom Nama Wilayah
                        $nameKeys = ['kabupaten', 'kabupaten_kota', 'nama_wilayah', 'wilayah', 'kab_kota'];
                        $nA = '';
                        $nB = '';
                        foreach ($nameKeys as $k) {
                            if (isset($a[$k])) {
                                $nA = (string)$a[$k];
                                break;
                            }
                        }
                        foreach ($b as $k) {
                            if (isset($b[$k])) {
                                $nB = (string)$b[$k];
                                break;
                            }
                        }

                        // 3. Deteksi Kolom Tahun
                        $yearKeys = ['tahun', 'tahun_data', 'tahun_kegiatan', 'year', 'periode', 'thn', 'tahun_anggaran'];
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

                        // URUTAN PRIORITAS 1: Kode Wilayah / Nama Kabupaten
                        if ($cA != 0 && $cB != 0 && $cA != $cB) return $cA <=> $cB;

                        // Fallback mapping order
                        $baseA = trim(strtolower(str_ireplace(['kab.', 'kabupaten', 'kota', 'kodya'], '', $nA)));
                        $baseB = trim(strtolower(str_ireplace(['kab.', 'kabupaten', 'kota', 'kodya'], '', $nB)));
                        $posA = $jatengOrder[$baseA] ?? 99;
                        $posB = $jatengOrder[$baseB] ?? 99;
                        if ($posA != $posB) return $posA <=> $posB;

                        // URUTAN PRIORITAS 2: Tahun (Terbaru di atas / atau sesuai gambar 2023-2025)
                        // Gambar menunjukkan urutan menaik (2023, 2024, 2025)
                        if ($yA != $yB) return $yA <=> $yB;

                        return 0;
                    });
                }
                // Jika rows kosong, set marker tapi jangan continue
                $is_empty_data = empty($rows);
                
                // Jika empty data dan tidak ada referensi sama sekali, kita skip,
                // tapi jika dari DB kita dapat metadata, kita bisa lanjut render header
                if ($is_empty_data && empty($dda_title) && empty($portal_title)) {
                    continue;
                }

                $columns = [];
                if (!$is_empty_data) {
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
                    if ($kbK) $newCols[] = $kbK;
                    if ($thK) $newCols[] = $thK;
                    foreach ($columns as $c) {
                        if ($c !== $thK && $c !== $kbK) $newCols[] = $c;
                    }
                    $columns = $newCols;
                }

                $res_id = $api_result['res_id'] ?? 0;
                $structure = $is_empty_data ? [] : parse_nested_headers($columns, $res_id, $portal_title);
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
                        <span style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 15px; color: #999; font-size: 12px; font-weight: bold; letter-spacing: 1px;">TABEL BERIKUTNYA / NEXT TABLE</span>
                    </div>
                <?php endif; ?>

                <div class="table-wrapper">
                    <!-- HEADER MODERN SEPERTI GAMBAR 1 -->
                    <?php
                            $display_title = '';
                            if ($idx_res === 0) {
                                $display_title = $dda_title ?: $portal_title;
                            } else {
                                $display_title = $portal_title;
                            }

                            // Bersihkan karakter encoding rusak (seperti ?Çô, â€“, dsb)
                            $display_title = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $display_title);

                            $tabel_nomor = '';
                            $judul_only = $display_title;
                            if (preg_match('/^Tabel\s+([\d\.\w]+)\s+(.*?)(?:,\s*(\d{4}(?:-\d{4})?))?$/is', trim($display_title), $m)) {
                                $tabel_nomor = $m[1];
                                $judul_only = trim($m[2]);
                                if (isset($m[3]) && $m[3]) {
                                    $judul_only .= ', ' . $m[3];
                                }
                            }
                            $judul_only = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $judul_only);

                            // Format judul ringkas untuk breadcrumb agar tidak panjang dan merusak baris
                            $core_breadcrumb = preg_split('/\s+(Menurut|Berdasarkan|\()\s+/i', $judul_only)[0];
                            $core_breadcrumb = trim($core_breadcrumb);
                            if (!empty($tabel_nomor)) {
                                $breadcrumb_label = 'Tabel ' . $tabel_nomor . ' - ' . mb_strimwidth($core_breadcrumb, 0, 32, '...');
                            } else {
                                $breadcrumb_label = mb_strimwidth($core_breadcrumb, 0, 38, '...');
                            }

                            $eng_title_raw = '';
                            if ($idx_res === 0) {
                                $eng_title_raw = $dda_title_en ?: ($api_result['title_en'] ?? '');
                            } else {
                                $eng_title_raw = $api_result['title_en'] ?? $dda_title_en ?? '';
                            }
                            $eng_title_raw = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $eng_title_raw);
                            
                            $judul_en_only = $eng_title_raw;
                            if (preg_match('/^Table\s+([\d\.\w]+)\s+(.*?)(?:,\s*(\d{4}(?:-\d{4})?))?$/is', trim($eng_title_raw), $m)) {
                                $judul_en_only = trim($m[2]);
                                if (isset($m[3]) && $m[3]) {
                                    $judul_en_only .= ', ' . $m[3];
                                }
                            }
                            $judul_en_only = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $judul_en_only);
                            
                            $unitkerja_nm = $api_result['unitkerja_ind'] ?? 'Provinsi Jawa Tengah';
                            if ($unitkerja_nm === '-' || empty($unitkerja_nm)) {
                                $unitkerja_nm = 'Provinsi Jawa Tengah';
                            }
                    ?>
                    
                    <?php if ($idx_res > 0): ?>
                    <div class="header-modern-v2" style="margin-top: 24px; background: transparent; box-shadow: none; border-bottom: 1px solid #e2e8f0; padding: 0 0 16px 0;">
                        <div class="title-area-row">
                            <!-- Tabel Number Block -->
                            <div class="tabel-label-box">
                                <div class="tabel-ind-group">
                                    <div class="tabel-ind">Tabel</div>
                                    <div class="tabel-en">Table</div>
                                </div>
                                <div class="tabel-number">
                                    <?= htmlspecialchars($tabel_nomor ?: '-') ?>
                                </div>
                            </div>
                            
                            <!-- Title Details -->
                            <div class="title-details-col">
                                <div class="table-opd-badge">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <span><?= htmlspecialchars($unitkerja_nm) ?></span>
                                </div>
                                <h2 class="table-main-title"><?= htmlspecialchars($judul_only) ?></h2>
                                <?php if (!empty($judul_en_only)): ?>
                                    <p class="table-sub-title"><?= htmlspecialchars($judul_en_only) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Mobile swipe tip -->
                    <div class="d-block d-md-none text-muted small mb-2 px-1">
                        <i class="fa-solid fa-arrows-left-right me-1 text-primary"></i>
                        <span>Geser tabel ke kanan/kiri untuk melihat seluruh kolom data</span>
                    </div>

                    <!-- TABEL DATA UTAMA -->
                    <?php if ($is_empty_data): ?>
                        <div class="alert text-center mt-4 mb-5 p-5" style="background: #fff3ed; border: 1px dashed #F26522; border-radius: 16px;">
                            <i class="fa-solid fa-folder-open mb-3" style="font-size: 3rem; color: #F26522; opacity: 0.5;"></i>
                            <h4 class="mb-0" style="color: #F26522; font-weight: 700;">Data Tabel Belum Tersedia</h4>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive-dda">
                        <table class="main-table dda-table-item" id="dda-table-<?php echo $idx_res; ?>">
                        <thead style="background: #FF6D1F; color: #fff;">
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
                            <tr class="num-row" style="background:#f9a066; color:#000;">
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

                            foreach ($rows as $r_idx => $row):
                                $rowYearValue = (string)($row[$thKey] ?? '');
                            ?>
                                <tr data-tahun="<?php echo $rowYearValue; ?>">
                                    <?php
                                    foreach ($columns as $c_idx => $c_key):
                                        $val = $row[$c_key] ?? '';
                                    ?>
                                        <td class="val-col"><?php echo htmlspecialchars((string)$val); ?></td>
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
                    </div>
                    <?php endif; ?>

                    <div style="font-size: 11px; margin-top: 10px; opacity: 0.6; line-height: 1.5;">
                        <div><b>Catatan/</b><i>Note</i>: Data berasal dari Portal Data Jawa Tengah (API ID: <?php echo $api_result['id_api'] ?? ''; ?>)</div>
                        <div><b>Sumber/</b><i>Source</i>: <?php echo htmlspecialchars($api_result['unitkerja_ind'] ?? '-'); ?></i></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>



    <?php echo view('admin/parts/portal_table_styles'); ?>

    <!-- Load SortableJS for Drag and Drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <?php echo view('admin/parts/portal_table_scripts'); ?>

    <script>
        // Store raw data from PHP
        let rawApiResults = <?php echo json_encode($api_results); ?>;
        let currentSelectedYears = []; // Global state multi-tahun
        let currentTableConfig = null;
        let tableSkeletonDismissed = false;

        function hideTableSkeleton() {
            if (tableSkeletonDismissed) return;
            const skeletonEl = document.getElementById('table-skeleton-overlay');
            if (skeletonEl) {
                tableSkeletonDismissed = true;
                skeletonEl.classList.add('is-hidden');
                setTimeout(function() {
                    skeletonEl.style.display = 'none';
                }, 480);
            }
        }

        // --- 1. AUTO-LOAD DARI DATABASE SAAT HALAMAN DIBUKA ---
        window.addEventListener('DOMContentLoaded', (event) => {
            rawApiResults.forEach((res, idx) => {
                if (res.config_tabel) {
                    try {
                        const savedConfig = JSON.parse(res.config_tabel);
                        if (idx === 0) currentTableConfig = savedConfig;
                        renderCustomTable(idx, savedConfig);
                    } catch (e) {
                        console.error("Gagal load config:", e);
                    }
                }
            });

            // Berikan jeda halus agar rendering tabel selesai & stabil sebelum skeleton memudar
            setTimeout(hideTableSkeleton, 250);
        });

        // Safety fallback jika load event terlambat atau selesai lebih cepat
        window.addEventListener('load', () => {
            setTimeout(hideTableSkeleton, 150);
        });
        setTimeout(hideTableSkeleton, 4500);
        
        function exportTableToPDF(tableID, nomor = '', judulId = '', judulEn = '') {
            if (typeof window.jspdf === 'undefined') {
                alert("Library PDF belum termuat, silakan coba lagi.");
                return;
            }
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape', format: 'a4' });
            
            // Bersihkan judul dari enter/newline yang tersembunyi dari database
            const cleanJudulId = (judulId || "Export Data").replace(/\r?\n|\r/g, " ").replace(/\s+/g, " ");
            const cleanJudulEn = (judulEn || "").replace(/\r?\n|\r/g, " ").replace(/\s+/g, " ");

            // Kalkulasi lebar teks
            const pageWidth = doc.internal.pageSize.getWidth();
            const marginLeft = 10;
            
            // Atur font ke ukuran nomor untuk mengukur dengan akurat
            doc.setFontSize(14);
            doc.setFont("helvetica", "bold");
            const numWidth = nomor ? doc.getTextWidth(nomor) : 10;
            
            const titleStartX = marginLeft + 12 + numWidth + 8; 
            // Panjangkan judul hingga batas kanan (margin kanan 10)
            const maxTitleWidth = pageWidth - titleStartX - 10;
            
            // Gunakan ukuran font yang lebih besar (12 untuk Indo, 10 untuk Eng) agar tidak terlalu kecil
            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            const splitId = doc.splitTextToSize(cleanJudulId, maxTitleWidth);
            
            doc.setFontSize(10);
            doc.setFont("helvetica", "italic");
            const splitEn = doc.splitTextToSize(cleanJudulEn, maxTitleWidth);
            
            // Tinggi judul Indo (12pt ~ 5.5mm per baris) + Eng (10pt ~ 4.5mm per baris) + spacing
            const idHeight = splitId.length * 5.5;
            const enHeight = splitEn.length * 4.5;
            // Tinggi dinamis KHUSUS halaman pertama
            const firstPageStartY = Math.max(30, 15 + idHeight + enHeight + 5);
            
            // Konfigurasi Autotable
            doc.autoTable({
                html: '#' + tableID,
                theme: 'grid',
                startY: firstPageStartY, // Halaman pertama mulai di sini
                margin: { top: 20, right: 10, bottom: 15, left: 10 }, // Halaman lanjutan pakai margin ini
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                    textColor: [0, 0, 0],
                    lineColor: [200, 200, 200],
                    lineWidth: 0.1,
                },
                headStyles: {
                    fillColor: [255, 109, 31],
                    textColor: [255, 255, 255],
                    fontSize: 9,
                    fontStyle: 'bold',
                    halign: 'center',
                    valign: 'middle'
                },
                alternateRowStyles: {
                    fillColor: [255, 244, 235]
                },
                didParseCell: function(data) {
                    if (data.section === 'body') {
                        let txt = data.cell.text.join(' ').trim();
                        // Rata kanan jika isinya angka atau tanda strip
                        if (txt !== '' && /^[\d\.\,\-\s]+$/.test(txt) && !/[a-zA-Z]/.test(txt)) {
                            data.cell.styles.halign = 'right';
                        }
                    }
                },
                didDrawPage: function (data) {
                    const pageNum = doc.internal.getNumberOfPages();
                    
                    if (pageNum === 1) {
                        let x = data.settings.margin.left;
                        let y = 15; // Y Awal
                        
                        // --- Kolom 1: TABEL & TABLE ---
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "bold");
                        doc.setTextColor(0, 0, 0);
                        doc.text("Tabel", x, y);
                        
                        // Garis pemisah bawah Tabel
                        doc.setDrawColor(0,0,0);
                        doc.setLineWidth(0.4);
                        doc.line(x, y + 1.5, x + 9, y + 1.5);
                        
                        doc.setFont("helvetica", "italic");
                        doc.text("Table", x, y + 4.5);
                        
                        // --- Kolom 2: Nomor Tabel ---
                        let numX = x + 11;
                        doc.setFontSize(14);
                        doc.setFont("helvetica", "bold");
                        doc.text(nomor || "", numX, y + 3);
                        
                        // --- Garis Vertikal Pemisah ---
                        let lineX = numX + numWidth + 3;
                        doc.setDrawColor(200, 200, 200);
                        doc.setLineWidth(0.2);
                        doc.line(lineX, y - 2, lineX, y + 6);
                        
                        // --- Kolom 3: Judul Indonesia & Inggris ---
                        let titleX = lineX + 4;
                        
                        // Cetak Judul Indo (Rata Kanan Kiri / Justify)
                        doc.setFontSize(12);
                        doc.setFont("helvetica", "bold");
                        doc.setTextColor(0, 0, 0);
                        doc.text(cleanJudulId, titleX, y + 1, { maxWidth: maxTitleWidth, align: "justify" });
                        
                        // Cetak Judul Eng (Rata Kanan Kiri / Justify)
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "italic");
                        doc.setTextColor(100, 100, 100);
                        doc.text(cleanJudulEn, titleX, y + 1 + idHeight, { maxWidth: maxTitleWidth, align: "justify" });
                        
                    } else {
                        // HALAMAN LANJUTAN
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "bold");
                        doc.setTextColor(0, 0, 0);
                        doc.text("Tabel " + (nomor || "") + " (Lanjutan / Continued)", data.settings.margin.left, 15);
                    }
                }
            });

            const safeTitle = cleanJudulId.substring(0, 50).replace(/[/\\?%*:|"<>]/g, '-');
            const filename = 'Tabel_' + (nomor || "") + '_' + safeTitle + '.pdf';
            doc.save(filename);
        }
    </script>
    <div style="display:none;"><?= csrf_field() ?></div>
    
    <!-- Load jsPDF and AutoTable for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    </div>
</div>