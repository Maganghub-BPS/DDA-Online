<?php
/**
 * VIEW: PORTAL DATA VIEWER TABEL DINAMIS (v_portal_tabel.php)
 * Didesain khusus menyerupai 100% tata letak dokumen fisik buku publikasi DDA BPS:
 * 1. Smart Multilevel Header: Pengelompokan header bertingkat otomatis atau via delimiter '||'.
 * 2. Bilingual Formatting: Judul, subjudul, dan header kolom dwibahasa (Indonesia - Inggris).
 * 3. Integrasi Open Data: Membaca struktur JSON/API portal data secara dinamis.
 * 4. Kompatibilitas Cetak & Ekspor: Menyediakan tombol cetak ramah printer dan ekspor Excel.
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

if (!function_exists('parse_portal_api_error')) {
    function parse_portal_api_error($raw_msg, $id_api = '') {
        $info = [
            'code'        => 'ERROR',
            'badge_class' => 'bg-danger-subtle text-danger border-danger-subtle',
            'title'       => 'Gagal Memuat Data dari Portal',
            'desc'        => 'Terjadi kendala saat menghubungi server Portal Satu Data Jawa Tengah.',
            'raw'         => $raw_msg
        ];

        if (stripos($raw_msg, 'HTTP_ERROR_404') !== false || stripos($raw_msg, 'No query results') !== false) {
            $info['code']        = 'HTTP 404 (Not Found)';
            $info['title']       = 'Dataset Tidak Ditemukan di Portal';
            $info['desc']        = 'Dataset dengan ID tersebut tidak ditemukan di server Portal Satu Data Jawa Tengah. Kemungkinan ID belum sesuai atau dataset telah dihapus/diubah oleh walidata di portal.';
        } elseif (stripos($raw_msg, 'HTTP_ERROR_500') !== false || stripos($raw_msg, 'Server Error') !== false) {
            $info['code']        = 'HTTP 500 (Server Error)';
            $info['title']       = 'Gangguan Server Portal / Format ID Lama';
            $info['desc']        = 'Server Portal Satu Data Jawa Tengah merespons dengan kendala internal (Server Error). Jika ID berupa format UUID lama, server baru memerlukan pemetaan ke ID numerik portal.';
        } elseif (stripos($raw_msg, 'HTTP_ERROR_401') !== false || stripos($raw_msg, 'HTTP_ERROR_403') !== false || stripos($raw_msg, 'MISSING_TOKEN') !== false) {
            $info['code']        = 'Autentikasi Gagal';
            $info['badge_class'] = 'bg-warning-subtle text-warning border-warning-subtle';
            $info['title']       = 'Akses Token API Ditolak';
            $info['desc']        = 'Token otorisasi API ke Portal Satu Data Jawa Tengah tidak valid atau belum dikonfigurasi.';
        } elseif (stripos($raw_msg, 'CURL_FAIL') !== false || stripos($raw_msg, 'timeout') !== false) {
            $info['code']        = 'Koneksi Terputus';
            $info['badge_class'] = 'bg-secondary-subtle text-secondary border-secondary-subtle';
            $info['title']       = 'Gagal Terhubung ke Server Portal';
            $info['desc']        = 'Koneksi ke server satudata.jatengprov.go.id mengalami waktu habis (timeout) atau jaringan sedang tidak stabil.';
        }

        return $info;
    }
}


// -------------------------------------------------------------------------
// TEMPLATE CONFIGURATION (Hardcoded for specific res_id or Titles)
// -------------------------------------------------------------------------
if (!function_exists('get_table_template')) {
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
}

if (!function_exists('parse_nested_headers')) {
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
}

if (!function_exists('get_dda_label_nested')) {
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
}
?>

<style>
    /* Pastikan scroll vertikal mengalir bebas di luar (natural outer scroll) */
    .main-content {
        height: auto !important;
        min-height: auto !important;
        overflow-y: visible !important;
        padding-bottom: 36px !important;
    }

    .content-body {
        height: auto !important;
        min-height: auto !important;
        flex: 1 0 auto !important;
        overflow-y: visible !important;
    }

    .dda-body {
        background: #fff;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        font-family: 'Arial', sans-serif;
        color: #000;
        line-height: 1.2;
        width: 100%;
        max-width: 100%;
        height: auto !important;
        max-height: none !important;
        overflow-y: visible !important;
        overflow-x: hidden;
        display: block;
        box-sizing: border-box;
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
        vertical-align: middle;
    }

    .label-box {
        width: 1%;
        white-space: nowrap;
        text-align: center;
        border-right: 1px solid #000;
        vertical-align: middle !important;
        padding: 8px 20px 8px 12px !important;
    }

    .label-box-content {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
    }

    .label-text {
        display: inline-block;
        vertical-align: middle;
        text-align: left;
        line-height: 1.25;
    }

    .label-text b {
        font-size: 13.5px;
        font-weight: 700;
        color: #000;
    }

    .label-text i {
        font-size: 12.5px;
        color: #333;
    }

    .label-number {
        display: inline-block;
        vertical-align: middle;
        font-weight: 700;
        font-size: 15px;
        color: #000;
        letter-spacing: 0.3px;
        text-align: center;
    }

    .title-box {
        text-align: left;
        padding-left: 20px !important;
        vertical-align: middle !important;
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
        border: 1px solid rgba(255, 255, 255, 0.25);
        padding: 8px 4px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        background: #154679;
        color: white;
    }

    .main-table thead th i {
        display: block !important;
        font-style: italic !important;
        font-weight: normal !important;
        font-size: 0.85em !important;
        margin-top: 2px !important;
        line-height: 1.25 !important;
        opacity: 0.95 !important;
        color: #e0edfd !important;
    }

    .main-table thead tr.num-row th {
        font-weight: bold;
        padding: 6px 4px;
        background: #154679;
        font-size: 11px;
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .main-table tbody td {
        border: 1px solid #e2e8f0;
        padding: 7px 10px;
        font-size: 12px;
        vertical-align: middle;
        color: #1e293b;
    }

    .main-table tbody tr.row-odd,
    .main-table tbody tr.row-odd td {
        background-color: #f1f6fc !important;
    }

    .main-table tbody tr.row-even,
    .main-table tbody tr.row-even td {
        background-color: #ffffff !important;
    }

    .main-table tbody tr.row-total,
    .main-table tbody tr.row-total td {
        background-color: #154679 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
    }

    /* Kontainer Scrollable Mulus (Murni horizontal, bebas dari scroll vertikal dalam) */
    .table-responsive-dda {
        width: 100%;
        max-width: 100%;
        height: auto !important;
        max-height: none !important;
        overflow-y: visible !important;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 25px;
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        box-shadow: inset 0 0 10px rgba(0,0,0,0.02);
    }

    /* Scrollbar style yang lebih terlihat */
    .table-responsive-dda::-webkit-scrollbar {
        height: 8px;
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
        /* Reduced from 8px 24px */
        font-weight: 600;
        font-size: 0.8rem;
        /* Slightly smaller from 0.85rem */
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
        color: #154679;
        border-color: #154679;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(21, 70, 121, 0.15);
        text-decoration: none;
    }

    .export-btn-modern {
        background: #154679;
        color: #fff;
    }

    .export-btn-modern:hover {
        background: #0f355c;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(21, 70, 121, 0.25);
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
        background: #154679;
        color: #fff;
        border-color: #154679;
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

<div class="dda-body">
    <?php
    $id_unitkerja = $api_results[0]['id_unitkerja'] ?? 0;
    $fallback_url = !empty($id_unitkerja) ? base_url('admin/dda/view_tabel?id=' . $id_unitkerja) : (base_url() . 'admin/dda');
    $back_url = $fallback_url;
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        // Jika referer bukan halaman tabel portal ini sendiri, simpan sebagai back URL
        if (strpos($referer, 'view_portal_tabel') === false && strpos($referer, base_url()) !== false) {
            $back_url = $referer;
        }
    }
    ?>
    <div class="row g-3 mb-4 align-items-center">
        <div class="col-12 col-md-auto">
            <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn-rounded-modern btn-back-modern w-100 w-md-auto">
                <i class="bi bi-arrow-left"></i> KEMBALI
            </a>
        </div>

        <div class="col-12 col-md d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
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
                <div class="dropdown">
                    <button class="btn-rounded-modern btn-back-modern shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        <i class="bi bi-calendar-event me-1"></i> <span id="year-filter-label">Tahun (<?php echo count($unique_years); ?>)</span>
                    </button>
                    <div class="dropdown-menu p-3 shadow-lg border-0" style="border-radius: 1rem; min-width: 220px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="dropdown-header px-0 mb-0 fw-bold text-dark" style="font-size: 11px;">PILIH TAHUN</h6>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary fw-semibold" style="font-size: 11px;" onclick="toggleAllYears()">Pilih Semua</button>
                        </div>
                        <div id="year-checklist-container" style="max-height: 220px; overflow-y: auto;">
                            <?php foreach ($unique_years as $yr): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input year-filter-check" type="checkbox" value="<?php echo $yr; ?>" id="yr-<?php echo $yr; ?>" checked onchange="filterByYear()">
                                    <label class="form-check-label small fw-bold" for="yr-<?php echo $yr; ?>">Tahun <?php echo $yr; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr class="my-2">
                        <button type="button" onclick="resetYearFilter()" class="btn btn-sm btn-link text-muted p-0 text-decoration-none small">Reset ke Default</button>
                    </div>
                </div>
            <?php endif; ?>

            <button onclick="openConfigModal()" class="btn-rounded-modern btn-back-modern shadow-sm" title="Buka Pengaturan Konfigurasi Tabel">
                <i class="bi bi-sliders text-primary"></i> KONFIGURASI
            </button>

            <button onclick="exportTableToExcel('dda-container-all', 'portal-data-export')" class="btn-rounded-modern export-btn-modern shadow-primary">
                <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-sm-inline">EXPORT EXCEL</span><span class="d-inline d-sm-none">EXPORT</span>
            </button>
        </div>
    </div>

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
            border-color: #154679;
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
        function formatDynamicTitleJs(baseTitle, selectedYears, lang) {
            if (!baseTitle) return '';
            const years = selectedYears.map(y => parseInt(y, 10)).filter(y => y > 1900 && y < 2100);
            years.sort((a, b) => a - b);
            const uniqueYears = Array.from(new Set(years));
            if (uniqueYears.length === 0) return baseTitle;

            const isEn = (lang === 'en');
            const conj = isEn ? ' and ' : ' dan ';
            let yearStr = '';

            if (uniqueYears.length === 1) {
                yearStr = uniqueYears[0].toString();
            } else if (uniqueYears.length === 2) {
                yearStr = uniqueYears[0] + conj + uniqueYears[1];
            } else {
                let isConsecutive = true;
                for (let i = 1; i < uniqueYears.length; i++) {
                    if (uniqueYears[i] !== uniqueYears[i - 1] + 1) {
                        isConsecutive = false;
                        break;
                    }
                }
                if (isConsecutive) {
                    yearStr = uniqueYears[0] + '–' + uniqueYears[uniqueYears.length - 1];
                } else {
                    const lastYear = uniqueYears[uniqueYears.length - 1];
                    const otherYears = uniqueYears.slice(0, -1);
                    const oxford = isEn ? ', and ' : ', dan ';
                    yearStr = otherYears.join(', ') + oxford + lastYear;
                }
            }

            if (baseTitle.toLowerCase().includes('[periode]')) {
                return baseTitle.replace(/\[periode\]/gi, yearStr);
            }
            return baseTitle + ', ' + yearStr;
        }

        function filterByYear() {
            const checkedYears = Array.from(document.querySelectorAll('.year-filter-check:checked')).map(cb => cb.value);

            if (checkedYears.length === 0) {
                alert('Pilih minimal 1 tahun untuk ditampilkan.');
                document.querySelectorAll('.year-filter-check').forEach(cb => cb.checked = true);
                filterByYear();
                return;
            }

            currentSelectedYears = checkedYears; // Update global state

            const totalYears = document.querySelectorAll('.year-filter-check').length;
            const label = document.getElementById('year-filter-label');
            if (checkedYears.length === totalYears) {
                label.innerText = "Tahun (" + checkedYears.length + ")";
            } else if (checkedYears.length === 1) {
                label.innerText = "Tahun " + checkedYears[0];
            } else {
                label.innerText = "Tahun (" + checkedYears.length + ")";
            }

            const tables = document.querySelectorAll('.table-wrapper');
            tables.forEach((wrapper, idx) => {
                // Perbarui judul tabel secara dinamis sesuai pilihan checkbox tahun
                const titleBox = wrapper.querySelector('.title-box');
                if (titleBox) {
                    const titleIdSpan = titleBox.querySelector('.title-id');
                    const titleEnSpan = titleBox.querySelector('.title-en');

                    if (checkedYears.length === 0) {
                        if (titleIdSpan && titleBox.dataset.defaultId) titleIdSpan.textContent = titleBox.dataset.defaultId;
                        if (titleEnSpan && titleBox.dataset.defaultEn) titleEnSpan.textContent = titleBox.dataset.defaultEn;
                    } else {
                        const baseId = titleBox.dataset.baseId || '';
                        const baseEn = titleBox.dataset.baseEn || '';
                        if (titleIdSpan && baseId) {
                            titleIdSpan.textContent = formatDynamicTitleJs(baseId, checkedYears, 'id');
                        }
                        if (titleEnSpan && baseEn) {
                            titleEnSpan.textContent = formatDynamicTitleJs(baseEn, checkedYears, 'en');
                        }
                    }
                }

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
                        if (checkedYears.includes(rowYear)) {
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

        function toggleAllYears() {
            const checkboxes = document.querySelectorAll('.year-filter-check');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            filterByYear();
        }

        function resetYearFilter() {
            document.querySelectorAll('.year-filter-check').forEach(cb => cb.checked = true);
            filterByYear();
        }
    </script>

    <?php echo view('admin/parts/js_export_excel'); ?>

    <?php 
    $target_year_found = !empty($th_target_data) && in_array((string)$th_target_data, array_map('strval', $unique_years));
    if (!$hasError && !empty($th_target_data) && !$target_year_found): 
    ?>
        <div class="alert border-0 shadow-xs d-flex align-items-center mb-3 p-3" style="background: #ffffff; border: 1px solid #fed7aa !important; border-left: 4px solid #f59e0b !important; border-radius: 10px; box-shadow: 0 2px 10px -2px rgba(245, 158, 11, 0.08) !important;">
            <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 40px; height: 40px; background: #fff7ed; color: #d97706; border: 1px solid #fde68a; border-radius: 10px;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.15rem;"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="fw-bold text-dark" style="font-size: 0.925rem; letter-spacing: -0.01em;">
                        Data Tahun <?= htmlspecialchars((string)$th_target_data); ?> Belum Tersedia
                    </span>
                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-0.5 fw-bold" style="font-size: 10px; letter-spacing: 0.3px;">
                        BELUM DIISI
                    </span>
                </div>
                <div class="text-muted" style="font-size: 0.835rem; line-height: 1.5;">
                    Publikasi DDA <?= htmlspecialchars((string)($ta ?? ((int)$th_target_data + 1))); ?> memerlukan data tahun <strong><?= htmlspecialchars((string)$th_target_data); ?></strong>. 
                    Tahun data tersedia saat ini: 
                    <?php if (!empty($unique_years)): ?>
                        <?php foreach ($unique_years as $yr): ?>
                            <span class="badge bg-light text-dark border px-2 py-0.5 fw-semibold me-1" style="font-size: 11px;"><?= htmlspecialchars((string)$yr); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-secondary fst-italic">Belum ada data</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($hasError): ?>
        <div class="card border-0 shadow-xs rounded-4 mb-4" style="background: #ffffff; border-left: 5px solid #ef4444 !important; border: 1px solid #fee2e2;">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 12px;">
                        <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <?php 
                        $errList = !empty($api_errors) ? $api_errors : [$errorMsg];
                        foreach ($errList as $singleErr): 
                            $parsed = parse_portal_api_error($singleErr, $id_api ?? '');
                        ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="badge <?= $parsed['badge_class']; ?> border px-2 py-0.5 fw-bold" style="font-size: 11px;">
                                        <?= htmlspecialchars($parsed['code']); ?>
                                    </span>
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">
                                        <?= htmlspecialchars($parsed['title']); ?>
                                    </h6>
                                </div>
                                <p class="text-secondary small mb-2" style="line-height: 1.5;">
                                    <?= htmlspecialchars($parsed['desc']); ?>
                                </p>
                                
                                <details class="mt-2">
                                    <summary class="text-muted small fw-semibold cursor-pointer" style="font-size: 0.75rem; user-select: none;">
                                        <i class="bi bi-code-slash me-1"></i> Detail Respons Teknis API
                                    </summary>
                                    <div class="bg-light p-2.5 rounded-3 mt-1.5 border" style="font-family: monospace; font-size: 0.75rem; word-break: break-all; color: #475569;">
                                        <?= htmlspecialchars($parsed['raw']); ?>
                                    </div>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div id="dda-container-all" style="padding: 20px;">
            <?php foreach ($api_results as $idx_res => $api_result):
                // Process each result independently
                $portal_title = $api_result['title'] ?? $api_result['nama'] ?? $api_result['data']['title'] ?? 'Tabel Data Portal';
                $dda_title = $api_result['dda_title'] ?? '';
                $dda_title_en = $api_result['dda_title_en'] ?? '';
                $no_tabel = $api_result['no_tabel'] ?? '';
                $base_title_ind = $api_result['base_title_ind'] ?? ($dda_title ?: $portal_title);
                $base_title_en  = $api_result['base_title_en'] ?? ($dda_title_en ?: ($api_result['title_en'] ?? ''));

                if (empty($no_tabel) && preg_match('/^(\d+(?:\.\d+)+(?:[a-zA-Z])?)\s+(.*)$/u', $dda_title ?: $portal_title, $m_num)) {
                    $no_tabel = $m_num[1];
                }

                if (!empty($no_tabel)) {
                    $dda_title = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/u', '', $dda_title);
                    $portal_title = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/u', '', $portal_title);
                    $dda_title_en = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/u', '', $dda_title_en);
                    $base_title_ind = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/u', '', $base_title_ind);
                    $base_title_en = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/u', '', $base_title_en);
                }

                // Bersihkan akhiran tahun dari base title agar formatDynamicTitleJs bisa menyusun rentang tahun secara bersih
                $base_title_ind = preg_replace('/,?\s*(?:Tahun\s+|Year\s+)?(?:19|20)\d{2}(?:\s*[\–\-\/]\s*(?:19|20)\d{2})?\s*$/iu', '', $base_title_ind);
                $base_title_ind = preg_replace('/,?\s*(?:19|20)\d{2}(?:,\s*(?:19|20)\d{2})*(?:\s*(?:dan|and|&)\s*(?:19|20)\d{2})?\s*$/iu', '', $base_title_ind);
                $base_title_ind = trim(rtrim($base_title_ind, ','));

                $base_title_en = preg_replace('/,?\s*(?:Tahun\s+|Year\s+)?(?:19|20)\d{2}(?:\s*[\–\-\/]\s*(?:19|20)\d{2})?\s*$/iu', '', $base_title_en);
                $base_title_en = preg_replace('/,?\s*(?:19|20)\d{2}(?:,\s*(?:19|20)\d{2})*(?:\s*(?:dan|and|&)\s*(?:19|20)\d{2})?\s*$/iu', '', $base_title_en);
                $base_title_en = trim(rtrim($base_title_en, ','));
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
                        <span style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 15px; color: #999; font-size: 12px; font-weight: bold; letter-spacing: 1px;">TABEL BERIKUTNYA / NEXT TABLE</span>
                    </div>
                <?php endif; ?>

                <div class="table-wrapper">
                    <!-- HEADER BAGIAN ATAS -->
                    <table class="header-table">
                        <tr>
                            <td class="label-box">
                                <div class="label-box-content">
                                    <div class="label-text">
                                        <b>Tabel</b><br><i>Table</i>
                                    </div>
                                    <?php if (!empty($no_tabel)): ?>
                                        <div class="label-number">
                                            <?php echo htmlspecialchars($no_tabel); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="title-box" data-base-id="<?php echo htmlspecialchars($base_title_ind); ?>" data-base-en="<?php echo htmlspecialchars($base_title_en); ?>" data-default-id="<?php echo htmlspecialchars($idx_res === 0 ? ($dda_title ?: $portal_title) : $portal_title); ?>" data-default-en="<?php echo htmlspecialchars($idx_res === 0 ? ($dda_title_en ?: ($api_result['title_en'] ?? '')) : ($api_result['title_en'] ?? $dda_title_en ?? '')); ?>">
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
                                    <span style="font-size: 12px; color: #666; display: block; margin-top: 2px;">
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
                    <div class="table-responsive-dda">
                        <table class="main-table dda-table-item" id="dda-table-<?php echo $idx_res; ?>">
                        <thead style="background: #154679; color: #fff;">
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
                            <tr class="num-row" style="background:#154679; color:#fff;">
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

                    <div style="font-size: 11px; margin-top: 10px; opacity: 0.6; line-height: 1.5;">
                        <div><b>Catatan/</b><i>Note</i>: Data berasal dari Portal Data Jawa Tengah (API ID: <?php echo $api_result['id_api'] ?? ''; ?>)</div>
                        <div><b>Sumber/</b><i>Source</i>: <?php echo htmlspecialchars($api_result['unitkerja_ind'] ?? '-'); ?></i></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php echo view('admin/parts/modal_config_tabel'); ?>


    <?php echo view('admin/parts/portal_table_styles'); ?>

    <!-- Load SortableJS for Drag and Drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <?php echo view('admin/parts/portal_table_scripts'); ?>

    <script>
        // Store raw data from PHP
        let rawApiResults = <?php echo json_encode($api_results); ?>;
        let currentSelectedYears = <?php echo json_encode(array_values(array_map('strval', $unique_years ?? []))); ?>; // Global state multi-tahun default all
        let currentTableConfig = null;

        // --- 1. AUTO-LOAD DARI DATABASE SAAT HALAMAN DIBUKA ---
        window.addEventListener('DOMContentLoaded', (event) => {
            rawApiResults.forEach((res, idx) => {
                const dataRows = getRowsFromApiResult(res);
                const baseKeys = (dataRows.length > 0) ? Object.keys(dataRows[0]) : [];
                const hasIndicatorPattern = baseKeys.some(k => k.toLowerCase() === 'indikator') && baseKeys.some(k => k.toLowerCase() === 'nilai') && baseKeys.length >= 4 && dataRows.some(r => r.indikator !== null && r.indikator !== '');

                if (res.config_tabel) {
                    try {
                        let savedConfig = JSON.parse(res.config_tabel);
                        if (typeof savedConfig === 'string') savedConfig = JSON.parse(savedConfig);
                        if (hasIndicatorPattern && (!savedConfig.pivot || (!savedConfig.pivot.enabled && (!savedConfig.pivot.col || savedConfig.pivot.col.length === 0))) && typeof buildSmartPivotConfig === 'function') {
                            savedConfig = buildSmartPivotConfig(baseKeys, dataRows);
                        }
                        if (idx === 0) currentTableConfig = savedConfig;
                        renderCustomTable(idx, savedConfig);
                    } catch (e) {
                        console.error("Gagal load config:", e);
                    }
                } else if (hasIndicatorPattern && typeof buildSmartPivotConfig === 'function') {
                    const smartCfg = buildSmartPivotConfig(baseKeys, dataRows);
                    if (idx === 0) currentTableConfig = smartCfg;
                    renderCustomTable(idx, smartCfg);
                }
            });
        });
    </script>
    <div style="display:none;"><?= csrf_field() ?></div>
</div>