<?php
/**
 * VIEW: TAMPILAN PUBLIK TABEL DINAMIS (frontend/v_tabel_dinamis.php)
 * 100% PERSIS DENGAN TAMPILAN TABEL PORTAL API (frontend/v_tabel.php)
 * 
 * Mengadopsi Layout Resmi:
 * 1. Full-width .header-modern-v2 (Breadcrumbs, Tombol Unduh Modern Navy Pill, Tabel Label Box, & Title Details).
 * 2. Kontainer .container > .dda-body (Card putih dengan sudut melengkung dan bayangan lembut).
 * 3. Matriks Data .table-responsive-dda > .main-table.dda-table-item:
 *    - Header Navy BPS (#154679) dengan pembatas halus & subtitle miring.
 *    - Num-row baris penomoran (1), (2), (3)... berlatar Midnight Navy (#0f355c) dengan teks putih.
 *    - Baris data selang-seling warna soft ice blue & putih (#f8fbff & #ffffff) dengan grid garis terstruktur (#e2e8f0).
 * 4. Sumber Resmi di bagian bawah tabel.
 */

// Ekstraksi Nomor Tabel & Pembersihan Judul Dasar
$tipePeriodeTabel = $indikator->tipe_periode ?? 'tahunan';

// 1. Judul Bahasa Indonesia
$rawJudulInd = $tabel->judul_ind ?? 'Tabel Data';
$rawJudulInd = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $rawJudulInd);

$no_tabel = $tabel->no_tabel ?? '';
if (preg_match('/^Tabel\s+([\d\.\w]+)\s+(.*?)$/is', trim($rawJudulInd), $m)) {
    if (empty($no_tabel)) {
        $no_tabel = $m[1];
    }
    $rawJudulInd = trim($m[2]);
} else {
    $rawJudulInd = preg_replace('/^Tabel\s+([\d\.\w]+)?\s*/i', '', $rawJudulInd);
}

if (!empty($no_tabel)) {
    $rawJudulInd = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/i', '', $rawJudulInd);
}

// Bersihkan akhiran tahun lama / koma mentah jika ada di master judul
$cleanJudulInd = preg_replace('/,?\s*(?:Tahun\s+|Year\s+)?(?:19|20)\d{2}(?:\s*[\–\-\/]\s*(?:19|20)\d{2})?\s*$/iu', '', trim($rawJudulInd));
$cleanJudulInd = preg_replace('/,?\s*(?:19|20)\d{2}(?:,\s*(?:19|20)\d{2})*(?:\s*(?:dan|and|&)\s*(?:19|20)\d{2})?\s*$/iu', '', $cleanJudulInd);
$cleanJudulInd = trim(rtrim($cleanJudulInd, " ,"));

// 2. Judul Bahasa Inggris
$rawJudulEn = $tabel->judul_en ?: ($tabel->judul_ind ?? '');
$rawJudulEn = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $rawJudulEn);

if (preg_match('/^Table\s+([\d\.\w]+)\s+(.*?)$/is', trim($rawJudulEn), $m)) {
    $rawJudulEn = trim($m[2]);
} else {
    $rawJudulEn = preg_replace('/^Table\s+([\d\.\w]+)?\s*/i', '', $rawJudulEn);
}

if (!empty($no_tabel)) {
    $rawJudulEn = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/i', '', $rawJudulEn);
}

$cleanJudulEn = preg_replace('/,?\s*(?:Tahun\s+|Year\s+)?(?:19|20)\d{2}(?:\s*[\–\-\/]\s*(?:19|20)\d{2})?\s*$/iu', '', trim($rawJudulEn));
$cleanJudulEn = preg_replace('/,?\s*(?:19|20)\d{2}(?:,\s*(?:19|20)\d{2})*(?:\s*(?:dan|and|&)\s*(?:19|20)\d{2})?\s*$/iu', '', $cleanJudulEn);
$cleanJudulEn = trim(rtrim($cleanJudulEn, " ,"));

// 3. Format Judul Dinamis Rapi Standar BPS (Opsi A) Berdasarkan Tahun yang Dipilih Pengguna
if (function_exists('formatDynamicTableTitle')) {
    $cleanJudulInd = formatDynamicTableTitle($cleanJudulInd, $selected_years ?? [], 'id', $tipePeriodeTabel);
    $cleanJudulEn  = formatDynamicTableTitle($cleanJudulEn, $selected_years ?? [], 'en', $tipePeriodeTabel);
}

// Format judul ringkas untuk breadcrumb
$core_breadcrumb = preg_split('/\s+(Menurut|Berdasarkan|\()\s+/i', $cleanJudulInd)[0];
$core_breadcrumb = trim($core_breadcrumb);
if (!empty($no_tabel)) {
    $breadcrumb_label = 'Tabel ' . $no_tabel . ' - ' . mb_strimwidth($core_breadcrumb, 0, 32, '...');
} else {
    $breadcrumb_label = mb_strimwidth($core_breadcrumb, 0, 38, '...');
}

// Produsen Data / OPD
$unitkerja_nm = $tabel->unitkerja_ind ?? ($indikator->sumber ?? 'Provinsi Jawa Tengah');
if ($unitkerja_nm === '-' || empty($unitkerja_nm)) {
    $unitkerja_nm = 'Provinsi Jawa Tengah';
}

// Judul lengkap untuk file export
$full_export_title = (!empty($no_tabel) ? 'Tabel ' . $no_tabel . ' ' : '') . $cleanJudulInd;

// Format Judul Kolom Baris Dinamis
$titleBarisInd = !empty($kelompokBaris->judul_baris_ind) ? $kelompokBaris->judul_baris_ind : ($kelompokBaris->nama_kelompok_ind ?? 'Kabupaten/Kota');
$cleanTitleInd = preg_replace('/^\d+\s+/', '', $titleBarisInd);
$titleBarisEn  = !empty($kelompokBaris->judul_baris_en) ? $kelompokBaris->judul_baris_en : ($kelompokBaris->nama_kelompok_en ?? '');

// Deteksi Multi-Kolom Baris (format pemisah '||')
$isMultiBaris = (strpos($cleanTitleInd, '||') !== false);
if (!$isMultiBaris && !empty($itemsBaris)) {
    foreach ($itemsBaris as $ib) {
        if (strpos($ib->nama_baris_ind ?? '', '||') !== false) {
            $isMultiBaris = true;
            break;
        }
    }
}

if ($isMultiBaris) {
    if (strpos($cleanTitleInd, '||') !== false) {
        $pInd = array_map('trim', explode('||', $cleanTitleInd));
        $titleCol1Ind = $pInd[0] ?? $cleanTitleInd;
        $titleCol2Ind = $pInd[1] ?? '';
    } else {
        $titleCol1Ind = $cleanTitleInd;
        $titleCol2Ind = '';
    }

    if (empty($titleCol2Ind)) {
        // Cerdas deteksi isi kolom 2 dari itemsBaris jika titleCol2Ind belum terisi
        $col2Samples = [];
        if (!empty($itemsBaris)) {
            foreach ($itemsBaris as $ib) {
                $nm = $ib->nama_baris_ind ?? '';
                if (strpos($nm, '||') !== false) {
                    $parts = explode('||', $nm);
                    $c2 = trim($parts[1] ?? '');
                    if ($c2 !== '') {
                        $col2Samples[] = strtolower($c2);
                    }
                }
            }
        }
        $sampleJoined = implode(' ', array_slice($col2Samples, 0, 15));
        if (strpos($sampleJoined, 'maret') !== false || strpos($sampleJoined, 'september') !== false || strpos($sampleJoined, 'januari') !== false || strpos($sampleJoined, 'desember') !== false || strpos($sampleJoined, 'juni') !== false) {
            $titleCol2Ind = 'Bulan';
            $titleCol2En  = 'Month';
        } elseif (strpos($sampleJoined, 'jakarta') !== false || strpos($sampleJoined, 'jawa') !== false || strpos($sampleJoined, 'bali') !== false || strpos($sampleJoined, 'sumatera') !== false || strpos($sampleJoined, 'yogyakarta') !== false || strpos($sampleJoined, 'kalimantan') !== false || strpos($sampleJoined, 'sulawesi') !== false) {
            $titleCol2Ind = 'Provinsi';
            $titleCol2En  = 'Province';
        } elseif (strpos($sampleJoined, 'unit') !== false || strpos($sampleJoined, 'juta') !== false || strpos($sampleJoined, 'rupiah') !== false || strpos($sampleJoined, 'persen') !== false || strpos($sampleJoined, '%') !== false || strpos($sampleJoined, 'ton') !== false || strpos($sampleJoined, 'kg') !== false) {
            $titleCol2Ind = 'Satuan';
            $titleCol2En  = 'Unit';
        } else {
            $titleCol2Ind = 'Satuan / Detail';
            $titleCol2En  = 'Detail';
        }
    }

    if (!empty($titleBarisEn) && strpos($titleBarisEn, '||') !== false) {
        $pEn = array_map('trim', explode('||', $titleBarisEn));
        $titleCol1En = $pEn[0] ?? $titleCol1Ind;
        $titleCol2En = $pEn[1] ?? ($titleCol2En ?: 'Detail');
    } elseif (empty($titleCol2En)) {
        $titleCol1En = $titleBarisEn ?: $titleCol1Ind;
        $lowerCol2 = strtolower($titleCol2Ind);
        if (strpos($lowerCol2, 'satuan') !== false || strpos($lowerCol2, 'unit') !== false) {
            $titleCol2En = 'Unit';
        } elseif (strpos($lowerCol2, 'provinsi') !== false) {
            $titleCol2En = 'Province';
        } elseif (strpos($lowerCol2, 'bulan') !== false) {
            $titleCol2En = 'Month';
        } else {
            $titleCol2En = 'Detail';
        }
    }
} else {
    $titleCol1Ind = $cleanTitleInd;
    $titleCol1En  = $titleBarisEn;
    $titleCol2Ind = '';
    $titleCol2En  = '';

    if (empty($titleBarisEn) || strtolower($titleBarisEn) === strtolower($cleanTitleInd)) {
        $lower = strtolower($cleanTitleInd);
        if (strpos($lower, 'kabupaten') !== false && strpos($lower, 'kota') !== false) {
            $titleBarisEn = 'Regency/Municipality';
        } elseif (strpos($lower, 'kabupaten') !== false) {
            $titleBarisEn = 'Regency';
        } elseif (strpos($lower, 'kota') !== false) {
            $titleBarisEn = 'Municipality';
        } elseif (strpos($lower, 'kecamatan') !== false) {
            $titleBarisEn = 'District';
        } elseif (strpos($lower, 'desa') !== false || strpos($lower, 'kelurahan') !== false) {
            $titleBarisEn = 'Village';
        } elseif (strpos($lower, 'provinsi') !== false) {
            $titleBarisEn = 'Province';
        } else {
            $titleBarisEn = 'Detail / Region';
        }
        $titleCol1En = $titleBarisEn;
    }
}

// Format Judul Kolom Periode Dinamis
$titlePeriodeInd = !empty($kelompokPeriode->judul_periode_ind) ? $kelompokPeriode->judul_periode_ind : 'Tahun';
$titlePeriodeEn  = !empty($kelompokPeriode->judul_periode_en) ? $kelompokPeriode->judul_periode_en : '';

// Format Judul Kolom Rincian / Sub-Baris Dinamis (Mode 2 Kolom)
$titleSubBarisInd = !empty($kelompokBaris->judul_sub_baris_ind) ? $kelompokBaris->judul_sub_baris_ind : '';
$cleanTitleSubInd = preg_replace('/^\d+\s+/', '', $titleSubBarisInd);
$titleSubBarisEn  = !empty($kelompokBaris->judul_sub_baris_en) ? $kelompokBaris->judul_sub_baris_en : '';

// Cek Kelengkapan Konfigurasi Dimensi (Minimal 2 dari 3 Dimensi: Baris, Kolom, Periode)
$hasDimBaris   = !empty($itemsBaris);
$hasDimKolom   = !empty($structureKolom) || !empty($itemsKolom);
$hasDimPeriode = !empty($itemsPeriode) || in_array($indikator->tipe_periode ?? '', ['tahunan', 'tahun_ajaran', 'tahunan_jumlah', 'tahunan_rata', 'tahunan_jumlah_rata'], true) || !empty($selected_years);
$dimCount      = ($hasDimBaris ? 1 : 0) + ($hasDimKolom ? 1 : 0) + ($hasDimPeriode ? 1 : 0);
$isConfigured  = ($indikator && $dimCount >= 2);
?>

<style>
    /* Sembunyikan progress box ekspor ke excel di frontend */
    #export-progress-container {
        display: none !important;
    }

    /* Header Section Modern - 100% Persis dengan v_tabel.php */
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

    /* Tombol Unduh Modern Navy Pill */
    .btn-download-modern {
        background: #0b3c68;
        color: #ffffff;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 13px;
        box-shadow: 0 2px 6px rgba(11, 60, 104, 0.25);
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .btn-download-modern:hover,
    .btn-download-modern:focus,
    .btn-download-modern[aria-expanded="true"] {
        background: #082d4f;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(11, 60, 104, 0.35);
    }
    .btn-download-modern i {
        color: #bbc9d5;
        font-size: 15px;
        transition: color 0.2s ease;
    }
    .btn-download-modern:hover i,
    .btn-download-modern:focus i,
    .btn-download-modern[aria-expanded="true"] i {
        color: #ffffff;
    }
    .btn-download-modern.dropdown-toggle::after {
        color: #ffffff;
        vertical-align: 0.15em;
    }
    .dropdown-menu-download {
        border-radius: 14px !important;
        min-width: 210px;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
    }
    .dropdown-menu-download .dropdown-item {
        font-size: 13.5px;
        font-weight: 500;
        color: #334155;
        transition: all 0.15s ease;
    }
    .dropdown-menu-download .dropdown-item:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }

    /* Title Area - Format Elegan Otentik BPS */
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

    /* Container Body Card */
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

    .table-responsive-dda {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 25px;
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
    }

    /* Main Table Styles - Full 100% Card Width Parity with View Tabel API */
    .table-responsive-dda table,
    .main-table,
    #tabel-dinamis-publik {
        width: 100% !important;
        min-width: 100% !important;
        border-collapse: collapse !important;
        table-layout: auto !important;
        background: #ffffff !important;
    }

    .main-table thead th {
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        padding: 8px 8px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        background: #154679 !important;
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

    /* Baris Penomoran (1), (2), (3)... Tema Navy BPS (#154679) Seragam dengan Header */
    .main-table thead tr.num-row th,
    .main-table thead tr.row-nomor-kolom th {
        font-weight: bold !important;
        padding: 6px 6px;
        background: #154679 !important;
        font-size: 11px;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        text-align: center;
    }

    /* Zebra Striping: Turunan Biru Lembut (#f1f6fc) & Putih Bersih (#ffffff) */
    .main-table tbody tr.row-odd,
    .main-table tbody tr.row-odd td {
        background-color: #f1f6fc !important;
    }

    .main-table tbody tr.row-even,
    .main-table tbody tr.row-even td {
        background-color: #ffffff !important;
    }

    .main-table tbody td {
        border: 1px solid #e2e8f0 !important;
        padding: 7px 10px;
        font-size: 12px;
        vertical-align: middle;
        color: #1e293b;
    }

    .main-table tbody td.col-val {
        text-align: right !important;
        padding-right: 12px !important;
        padding-left: 8px !important;
    }

    /* Baris Kategori Wilayah / Master Divider */
    .main-table tr.category-row,
    .main-table tr.category-row td,
    .main-table tr.row-category,
    .main-table tr.row-category td {
        background-color: #f1f6fc !important;
        font-weight: bold !important;
        color: #0f2d59 !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* Baris Subtotal / Sub-Jumlah */
    .main-table tbody tr.row-subtotal,
    .main-table tbody tr.row-subtotal td {
        background-color: #dbeafe !important;
        color: #1e3a8a !important;
        font-weight: bold !important;
        border: 1px solid #e2e8f0 !important;
    }

    .main-table tbody tr.row-subtotal td.col-val {
        color: #1e3a8a !important;
        text-align: right !important;
        font-weight: bold !important;
    }

    .main-table tbody tr.row-subtotal td i {
        color: #1e3a8a !important;
        font-weight: normal;
    }

    /* Baris Grand Total (Jumlah / Total) Sama Persis Warna Judul Kolom (#154679) */
    .main-table tbody tr.row-total,
    .main-table tbody tr.row-total td {
        background-color: #154679 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
    }

    .main-table tbody tr.row-total td.col-val {
        color: #ffffff !important;
        text-align: right !important;
        font-weight: bold !important;
        background-color: #154679 !important;
    }

    .main-table tbody tr.row-total td i {
        color: #e0edfd !important;
        font-style: italic;
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

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        .header-modern-v2, .header-modern-v2 *,
        .dda-body, .dda-body * {
            visibility: visible;
        }
        .dda-body {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
        .table-top-bar, .table-actions-dropdown, .latifa-widget, .navbar-custom, .footer-mega {
            display: none !important;
        }
        .table-responsive-dda {
            overflow: visible !important;
            border: none !important;
        }
        @page {
            size: landscape;
            margin: 10mm;
        }
    }
</style>

<!-- 1. FULL WIDTH HEADER SECTION PERSIS VIEW_TABEL.PHP -->
<div class="header-modern-v2">
    <div class="container">
        <!-- Top Action Bar -->
        <div class="table-top-bar d-flex align-items-center justify-content-between gap-2">
            <!-- Left: Breadcrumb Navigasi -->
            <nav aria-label="breadcrumb" class="table-breadcrumb-nav">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= base_url('/'); ?>"><i class="fa-solid fa-house-chimney me-1"></i>Beranda</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= base_url('home/search'); ?>">Jelajah Data</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" title="<?= esc($cleanJudulInd); ?>">
                        <span class="d-none d-md-inline"><?= esc($breadcrumb_label); ?></span>
                        <span class="d-inline d-md-none"><?= esc(!empty($no_tabel) ? 'Tabel ' . $no_tabel : 'Detail'); ?></span>
                    </li>
                </ol>
            </nav>
            
            <!-- Right: Year Filter & Unduh Data -->
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <?php if (!empty($selectable_years)): ?>
                    <div class="dropdown">
                        <button class="btn btn-download-modern d-flex align-items-center gap-2 dropdown-toggle px-3 py-1.5" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="bi bi-calendar-event" style="font-size: 15px; color: #bbc9d5;"></i> <span id="year-filter-label">Tahun (<?= count($selected_years ?? []); ?>)</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg border-0 mt-2" style="border-radius: 12px; min-width: 220px; font-size: 13px;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="dropdown-header px-0 mb-0 fw-bold text-dark" style="font-size: 11px;">PILIH TAHUN DATA</h6>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary fw-semibold" style="font-size: 11px;" onclick="toggleAllYears()">Pilih Semua</button>
                            </div>
                            <div id="year-checklist-container" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($selectable_years as $yr): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input year-filter-check" type="checkbox" value="<?= $yr; ?>" id="yr-<?= $yr; ?>" <?= in_array($yr, $selected_years ?? [], true) ? 'checked' : ''; ?> onchange="applyYearFilter()">
                                        <label class="form-check-label small fw-bold" for="yr-<?= $yr; ?>">
                                            Tahun <?= $yr; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <hr class="my-2">
                            <button type="button" onclick="resetYearFilter()" class="btn btn-sm btn-link text-muted p-0 text-decoration-none small w-100 text-center" style="font-size: 11px;">Reset ke Default</button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="table-actions-dropdown dropdown">
                    <button class="btn btn-download-modern d-flex align-items-center gap-2 dropdown-toggle px-3 py-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-cloud-arrow-down" style="font-size: 15px; color: #bbc9d5;"></i> <span class="d-none d-sm-inline">Unduh Data</span><span class="d-inline d-sm-none">Unduh</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-download dropdown-menu-end p-2 border-0 mt-2">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2 px-3 rounded-3" href="#" id="btn-export-excel">
                                <i class="bi bi-file-earmark-excel me-2.5 fs-5" style="color: #198754;"></i> Format Excel (.xlsx)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2 px-3 rounded-3" href="#" id="btn-export-pdf">
                                <i class="bi bi-file-earmark-pdf me-2.5 fs-5" style="color: #dc3545;"></i> Format PDF (.pdf)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Title Area Row: Nomor Tabel Kiri & Judul Kanan -->
        <div class="title-area-row">
            <!-- Tabel Number Block -->
            <div class="tabel-label-box">
                <div class="tabel-ind-group">
                    <div class="tabel-ind">Tabel</div>
                    <div class="tabel-en">Table</div>
                </div>
                <div class="tabel-number">
                    <?= esc($no_tabel ?: '-'); ?>
                </div>
            </div>
            
            <!-- Title Details -->
            <div class="title-details-col">
                <div class="table-opd-badge">
                    <i class="fa-solid fa-building-columns"></i>
                    <span><?= esc($unitkerja_nm); ?></span>
                </div>
                <h1 class="table-main-title"><?= esc($cleanJudulInd); ?></h1>
                <?php if (!empty($cleanJudulEn)): ?>
                    <p class="table-sub-title"><?= esc($cleanJudulEn); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 2. BODY CONTENT SECTION PERSIS VIEW_TABEL.PHP -->
<div class="container">
    <div class="dda-body">

        <?php if (!$isConfigured): ?>
            <!-- Notifikasi Jika Konfigurasi Dimensi Belum Lengkap -->
            <div class="alert text-center my-4 p-5" style="background: #f0f7ff; border: 1px dashed #2563eb; border-radius: 16px;">
                <i class="fa-solid fa-folder-open mb-3" style="font-size: 3rem; color: #2563eb; opacity: 0.6;"></i>
                <h4 class="mb-1" style="color: #1e40af; font-weight: 700;">Data Tabel Belum Tersedia</h4>
                <p class="text-muted small mb-0">Struktur matriks data tabel ini sedang dikonfigurasi oleh produsen data dan BPS Provinsi Jawa Tengah.</p>
            </div>
        <?php else: ?>

            <div class="table-wrapper">
                <!-- Mobile swipe tip -->
                <div class="d-block d-md-none text-muted small mb-2 px-1">
                    <i class="fa-solid fa-arrows-left-right me-1 text-primary"></i>
                    <span>Geser tabel ke kanan/kiri untuk melihat seluruh kolom data</span>
                </div>

                <!-- 3. Matriks Data Utama Responsif Multi-Tahun -->
                <?= view('admin/parts/table_matrix_dinamis', [
                    'tabel'             => $tabel,
                    'indikator'         => $indikator,
                    'structureKolom'    => $structureKolom,
                    'itemsKolom'        => $itemsKolom,
                    'itemsBaris'        => $itemsBaris,
                    'kelompokBaris'     => $kelompokBaris,
                    'itemsPeriode'      => $itemsPeriode,
                    'kelompokPeriode'   => $kelompokPeriode,
                    'nilaiMap'          => $nilaiMap,
                    'available_years'   => $available_years ?? [],
                    'selected_years'    => $selected_years ?? [],
                    'config_tampilan'   => $config_tampilan ?? [],
                    'matrix_dom_id'     => 'tabel-dinamis-publik',
                    'is_admin'          => false,
                ]); ?>

                <!-- 4. Sumber Resmi (Cukup Sumber Saja, Tanpa Catatan) -->
                <div style="font-size: 11px; margin-top: 14px; opacity: 0.75; line-height: 1.6;" class="table-footer-notes">
                    <div><b>Sumber/</b><i>Source</i>: <?= esc($unitkerja_nm); ?></div>
                </div>

            </div><!-- /.table-wrapper -->
        <?php endif; ?>

    </div>
</div>

<!-- Library Excel Export Local / CDN -->
<?php echo view('admin/parts/js_export_excel'); ?>

<!-- Load jsPDF and AutoTable for PDF Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hapus elemen progress box ekspor ke excel agar tidak muncul di frontend
    const progressBox = document.getElementById('export-progress-container');
    if (progressBox) {
        progressBox.remove();
    }

    // 1. Ekspor ke Excel (.xlsx)
    const btnExcel = document.getElementById('btn-export-excel');
    if (btnExcel) {
        btnExcel.addEventListener('click', function(e) {
            e.preventDefault();
            const exportTitle = <?= json_encode($full_export_title); ?>;
            if (typeof exportTableToExcel === 'function') {
                exportTableToExcel('tabel-dinamis-publik', exportTitle);
            } else {
                alert('Modul ekspor Excel sedang memuat, silakan coba beberapa detik lagi.');
            }
        });
    }

    // 2. Ekspor ke PDF (.pdf)
    const btnPdf = document.getElementById('btn-export-pdf');
    if (btnPdf) {
        btnPdf.addEventListener('click', function(e) {
            e.preventDefault();
            const noTabel = <?= json_encode($no_tabel); ?>;
            const judulId = <?= json_encode($cleanJudulInd); ?>;
            const judulEn = <?= json_encode($cleanJudulEn); ?>;
            exportTableToPDF('tabel-dinamis-publik', noTabel, judulId, judulEn);
        });
    }

    /**
     * Ekspor Dokumen ke Format PDF Landscape Resmi Standar Publikasi BPS
     */
    function exportTableToPDF(tableID, nomor = '', judulId = '', judulEn = '') {
        if (typeof window.jspdf === 'undefined') {
            alert("Pustaka PDF sedang memuat dari jaringan, silakan coba beberapa saat lagi.");
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', format: 'a4' });

        const cleanJudulId = (judulId || "Export Data").replace(/\r?\n|\r/g, " ").replace(/\s+/g, " ");
        const cleanJudulEn = (judulEn || "").replace(/\r?\n|\r/g, " ").replace(/\s+/g, " ");

        const pageWidth = doc.internal.pageSize.getWidth();
        const marginLeft = 10;

        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        const numWidth = nomor ? doc.getTextWidth(nomor) : 10;

        const titleStartX = marginLeft + 12 + numWidth + 8;
        const maxTitleWidth = pageWidth - titleStartX - 10;

        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");
        const splitId = doc.splitTextToSize(cleanJudulId, maxTitleWidth);

        doc.setFontSize(9.5);
        doc.setFont("helvetica", "italic");
        const splitEn = cleanJudulEn ? doc.splitTextToSize(cleanJudulEn, maxTitleWidth) : [];

        const idHeight = splitId.length * 5;
        const enHeight = splitEn.length * 4.2;
        const firstPageStartY = Math.max(28, 14 + idHeight + enHeight + 6);

        const domTable = document.getElementById(tableID);
        const domBodyRows = domTable ? Array.from(domTable.querySelectorAll('tbody tr')) : [];
        const domHeadRows = domTable ? Array.from(domTable.querySelectorAll('thead tr')) : [];

        // Helper untuk mengekstrak elemen TR DOM asli dari hook AutoTable
        const getDomTr = (data) => {
            if (data.section === 'head') {
                return (data.row.raw && data.row.raw._element) || (domHeadRows ? domHeadRows[data.row.index] : null);
            }
            return (data.row.raw && data.row.raw._element)
                || (data.cell.raw && data.cell.raw._element ? data.cell.raw._element.closest('tr') : null)
                || (data.cell.raw && data.cell.raw.closest ? data.cell.raw.closest('tr') : null)
                || (domBodyRows ? domBodyRows[data.row.index] : null);
        };

        const getDomTd = (data) => {
            return (data.cell.raw && data.cell.raw._element)
                || (data.cell.raw instanceof HTMLElement ? data.cell.raw : null);
        };

        const getRowType = (data) => {
            if (data.section === 'head') return 'head';
            if (data.section === 'foot') return 'foot';

            const trEl = getDomTr(data);
            const tdEl = getDomTd(data);

            const trClasses = trEl ? (trEl.className || '') : '';
            const tdClasses = tdEl ? (tdEl.className || '') : '';
            const trStyle = trEl ? (trEl.getAttribute('style') || '') : '';
            const tdStyle = tdEl ? (tdEl.getAttribute('style') || '') : '';
            const combinedStyle = trStyle + ' ' + tdStyle;

            // 1. Deteksi Baris Grand Total (Jumlah / Total / Jawa Tengah / PDRB)
            if (trClasses.includes('row-total') 
                || tdClasses.includes('row-total')
                || combinedStyle.includes('#154679') 
                || combinedStyle.includes('21, 70, 121') 
                || combinedStyle.includes('21,70,121')) {
                return 'total';
            }

            // 2. Deteksi Banner Pembatas Kategori (Kabupaten / Kota)
            if (trClasses.includes('row-category') || trClasses.includes('category-row') || tdClasses.includes('row-category') || tdClasses.includes('category-row')) {
                return 'category';
            }

            // 3. Deteksi Baris Subtotal
            if (trClasses.includes('row-subtotal') 
                || tdClasses.includes('row-subtotal')
                || combinedStyle.includes('#dbeafe') 
                || combinedStyle.includes('219, 234, 254')) {
                return 'subtotal';
            }

            // 4. Fallback deteksi teks untuk Total dan Kategori
            let labelText = '';
            if (trEl && trEl.children && trEl.children.length > 0) {
                for (let i = 0; i < Math.min(3, trEl.children.length); i++) {
                    const cTxt = (trEl.children[i].textContent || '').trim();
                    if (cTxt && !/^\d+$/.test(cTxt)) {
                        labelText = cTxt.toLowerCase().replace(/\s+/g, ' ');
                        break;
                    }
                }
            }
            if (!labelText) {
                labelText = trEl ? (trEl.textContent || '').trim().toLowerCase().replace(/\s+/g, ' ') : '';
            }

            const isLastRow = domBodyRows && data.row.index === (domBodyRows.length - 1);
            const isGenuineTotal = (txt) => {
                if (!txt) return false;
                const clean = txt.trim().toLowerCase().replace(/\s+/g, ' ');
                // Murni label total / jumlah (bukan nama indikator seperti "curah hujan", "hari hujan", "penduduk", dll)
                if (/^(jumlah|total|grand\s+total|total\s+keseluruhan)(\s*[:\/\-]\s*(total|jumlah|grand\s+total))?$/i.test(clean)) {
                    return true;
                }
                // Label total provinsi Jawa Tengah (di tabel kabupaten/kota)
                if (/^(provinsi\s+)?jawa\s+tengah(\s*[:\/\-]\s*(central\s+java|central\s+java\s+province))?$/i.test(clean)) {
                    return true;
                }
                // Label total PDRB murni pada baris akhir
                if (/^(total\s+pdrb|jumlah\s+pdrb|pdrb)(\s*[:\/\-]\s*.*)?$/i.test(clean) && isLastRow) {
                    return true;
                }
                return false;
            };

            if (isGenuineTotal(labelText) || (isLastRow && /^(jumlah|total|grand\s+total|jawa\s+tengah)\b/i.test(labelText) && !/(curah\s+hujan|hari\s+hujan|penduduk|tenaga\s+kerja|perusahaan|sekolah|guru|siswa|luas|kasus|fasilitas)/i.test(labelText))) {
                return 'total';
            }
            if (trEl && trEl.children && trEl.children.length === 1 && (labelText.includes('kabupaten') || labelText.includes('kota'))) {
                return 'category';
            }

            // 5. Deteksi Baris Ganjil (Turunan Biru Muda #f1f6fc)
            if (trClasses.includes('row-odd') 
                || tdClasses.includes('row-odd')
                || combinedStyle.includes('#f1f6fc') 
                || combinedStyle.includes('241, 246, 252')) {
                return 'odd';
            }

            // 6. Deteksi Baris Genap (Putih Bersih #ffffff)
            if (trClasses.includes('row-even') 
                || tdClasses.includes('row-even')
                || combinedStyle.includes('#ffffff') 
                || combinedStyle.includes('255, 255, 255')) {
                return 'even';
            }

            // Fallback baris bergantian (selang-seling): ganjil #f1f6fc, genap #ffffff
            return (data.row.index % 2 === 0) ? 'odd' : 'even';
        };

        doc.autoTable({
            html: '#' + tableID,
            theme: 'grid',
            startY: firstPageStartY,
            showFoot: 'never',
            margin: { top: 20, right: 10, bottom: 15, left: 10 },
            styles: {
                fontSize: 7.5,
                cellPadding: 2,
                textColor: [0, 0, 0],
                lineColor: [200, 200, 200],
                lineWidth: 0.1,
            },
            headStyles: {
                fillColor: [21, 70, 121], // Navy BPS (#154679)
                textColor: [255, 255, 255],
                fontSize: 8,
                fontStyle: 'bold',
                halign: 'center',
                valign: 'middle'
            },
            footStyles: {
                fillColor: false,
                textColor: [0, 0, 0],
                fontSize: 0,
                cellPadding: 0
            },
            alternateRowStyles: {},
            didParseCell: function(data) {
                if (data.section === 'foot') {
                    data.cell.styles.fillColor = false;
                    data.cell.styles.cellPadding = 0;
                    data.cell.styles.fontSize = 0;
                    data.cell.styles.minCellHeight = 0;
                    return;
                }
                if (data.section === 'head') {
                    data.cell.styles.fillColor = [21, 70, 121]; // Navy BPS (#154679)
                    data.cell.styles.textColor = [255, 255, 255];
                    data.cell.styles.fontStyle = 'bold';
                    data.cell.styles.halign = 'center';
                    data.cell.styles.valign = 'middle';
                    const headTr = getDomTr(data);
                    if (headTr && headTr.className && (headTr.className.includes('row-nomor-kolom') || headTr.className.includes('num-row'))) {
                        data.cell.styles.fontSize = 7;
                    }
                } else if (data.section === 'body') {
                    let txt = data.cell.text.join(' ').trim();
                    if (txt !== '' && /^[\d\.\,\-\s–—]+$/.test(txt) && !/[a-zA-Z]/.test(txt)) {
                        data.cell.styles.halign = 'right';
                    }

                    const tdEl = getDomTd(data);
                    if (tdEl) {
                        const tdWeight = tdEl.style.fontWeight || (window.getComputedStyle ? window.getComputedStyle(tdEl).fontWeight : '');
                        if (tdWeight === 'bold' || parseInt(tdWeight) >= 600) {
                            data.cell.styles.fontStyle = 'bold';
                        }
                    }

                    const rType = getRowType(data);

                    if (rType === 'total') {
                        // BARIS GRAND TOTAL: Navy BPS (#154679) + Teks Putih Tebal (Persis Gambar 2)
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.fillColor = [21, 70, 121]; // #154679
                        data.cell.styles.textColor = [255, 255, 255];
                        data.cell.styles.lineWidth = 0.1;
                        data.cell.styles.lineColor = [255, 255, 255];
                    } else if (rType === 'category') {
                        // BANNER PEMBATAS KATEGORI: Turunan Biru Muda (#f1f6fc) + Teks Navy Tebal
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.fillColor = [241, 246, 252]; // #f1f6fc
                        data.cell.styles.textColor = [15, 45, 89]; // #0f2d59
                        data.cell.styles.lineWidth = 0.1;
                        data.cell.styles.lineColor = [226, 232, 240];
                    } else if (rType === 'subtotal') {
                        // SUBTOTAL: Soft Blue (#dbeafe) + Teks Biru Tebal
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.fillColor = [219, 234, 254]; // #dbeafe
                        data.cell.styles.textColor = [30, 58, 138];
                        data.cell.styles.lineWidth = 0.1;
                        data.cell.styles.lineColor = [226, 232, 240];
                    } else if (rType === 'odd') {
                        // BARIS GANJIL: Turunan Biru Muda (#f1f6fc)
                        data.cell.styles.fillColor = [241, 246, 252]; // #f1f6fc
                        data.cell.styles.textColor = [30, 41, 59];
                        data.cell.styles.lineWidth = 0.1;
                        data.cell.styles.lineColor = [226, 232, 240];
                    } else if (rType === 'even') {
                        // BARIS GENAP: Putih Bersih (#ffffff)
                        data.cell.styles.fillColor = [255, 255, 255]; // #ffffff
                        data.cell.styles.textColor = [30, 41, 59];
                        data.cell.styles.lineWidth = 0.1;
                        data.cell.styles.lineColor = [226, 232, 240];
                    }
                }
            },
            willDrawCell: function(data) {
                if (data.section === 'foot') {
                    return false;
                }
                if (data.section === 'head') {
                    doc.setFillColor(21, 70, 121);
                    doc.setTextColor(255, 255, 255);
                    doc.setFont(undefined, 'bold');
                } else if (data.section === 'body') {
                    const rType = getRowType(data);
                    if (rType === 'total') {
                        doc.setFillColor(21, 70, 121);
                        doc.setTextColor(255, 255, 255);
                        doc.setFont(undefined, 'bold');
                    } else if (rType === 'category') {
                        doc.setFillColor(241, 246, 252);
                        doc.setTextColor(15, 45, 89);
                        doc.setFont(undefined, 'bold');
                    } else if (rType === 'subtotal') {
                        doc.setFillColor(219, 234, 254);
                        doc.setTextColor(30, 58, 138);
                        doc.setFont(undefined, 'bold');
                    } else if (rType === 'odd') {
                        doc.setFillColor(241, 246, 252);
                        doc.setTextColor(30, 41, 59);
                        if (data.cell.styles.fontStyle === 'bold') {
                            doc.setFont(undefined, 'bold');
                        }
                    } else if (rType === 'even') {
                        doc.setFillColor(255, 255, 255);
                        doc.setTextColor(30, 41, 59);
                        if (data.cell.styles.fontStyle === 'bold') {
                            doc.setFont(undefined, 'bold');
                        }
                    }
                }
            },

            didDrawPage: function (data) {
                const pageNum = doc.internal.getNumberOfPages();

                if (pageNum === 1) {
                    let x = data.settings.margin.left;
                    let y = 14;

                    // Kotak Label Tabel BPS
                    doc.setFontSize(9.5);
                    doc.setFont("helvetica", "bold");
                    doc.setTextColor(0, 0, 0);
                    doc.text("Tabel", x, y);

                    doc.setDrawColor(0, 0, 0);
                    doc.setLineWidth(0.3);
                    doc.line(x, y + 1.2, x + 8.5, y + 1.2);

                    doc.setFont("helvetica", "italic");
                    doc.text("Table", x, y + 4.2);

                    // Nomor Tabel
                    let numX = x + 11;
                    doc.setFontSize(13);
                    doc.setFont("helvetica", "bold");
                    doc.text(nomor || "", numX, y + 3);

                    // Garis Pemisah Vertikal
                    let lineX = numX + numWidth + 3;
                    doc.setDrawColor(200, 200, 200);
                    doc.setLineWidth(0.2);
                    doc.line(lineX, y - 2, lineX, y + 6);

                    // Judul Dwibahasa
                    let titleX = lineX + 4;

                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.setTextColor(0, 0, 0);
                    doc.text(cleanJudulId, titleX, y + 1, { maxWidth: maxTitleWidth, align: "justify" });

                    if (cleanJudulEn) {
                        doc.setFontSize(9.5);
                        doc.setFont("helvetica", "italic");
                        doc.setTextColor(90, 90, 90);
                        doc.text(cleanJudulEn, titleX, y + 1 + idHeight, { maxWidth: maxTitleWidth, align: "justify" });
                    }
                } else {
                    // Header Halaman Lanjutan
                    doc.setFontSize(9.5);
                    doc.setFont("helvetica", "bold");
                    doc.setTextColor(0, 0, 0);
                    doc.text("Tabel " + (nomor || "") + " (Lanjutan / Continued)", data.settings.margin.left, 14);
                }

                // Penomoran Halaman di Footer
                const pageCount = doc.internal.getNumberOfPages();
                doc.setFontSize(8);
                doc.setFont("helvetica", "normal");
                doc.setTextColor(120, 120, 120);
                doc.text(
                    `Halaman ${pageNum}`,
                    pageWidth - data.settings.margin.right,
                    doc.internal.pageSize.getHeight() - 8,
                    { align: 'right' }
                );
            }
        });

        // Cetak Sumber di bawah tabel PDF (cukup Sumber saja, tanpa Kondef atau Catatan)
        const finalY = (doc.lastAutoTable && doc.lastAutoTable.finalY) ? doc.lastAutoTable.finalY : firstPageStartY + 40;
        const pageHeight = doc.internal.pageSize.getHeight();
        let footerY = finalY + 7;
        
        if (footerY > pageHeight - 15) {
            doc.addPage();
            footerY = 20;
        }

        const wrapperEl = document.getElementById(tableID)?.closest('.table-wrapper') || document.querySelector('.table-wrapper');
        let sumberText = '';
        if (wrapperEl) {
            const footerEl = wrapperEl.querySelector('.table-footer-notes') || wrapperEl.querySelector('div[style*="font-size: 11px"]');
            if (footerEl) {
                const lines = footerEl.innerText.split('\n').map(l => l.trim()).filter(Boolean);
                const sLine = lines.find(l => /^(sumber|source)/i.test(l));
                if (sLine) {
                    sumberText = sLine;
                }
            }
        }
        if (!sumberText) {
            const opdBadge = document.querySelector('.table-opd-badge span');
            if (opdBadge && opdBadge.innerText.trim()) {
                sumberText = 'Sumber/Source: ' + opdBadge.innerText.trim();
            }
        }

        if (sumberText) {
            doc.setFontSize(8.5);
            doc.setFont("helvetica", "bold");
            doc.setTextColor(31, 41, 55);
            doc.text("Sumber / ", marginLeft, footerY);
            const w1 = doc.getTextWidth("Sumber / ");

            doc.setFont("helvetica", "bolditalic");
            doc.setTextColor(75, 85, 99);
            doc.text("Source", marginLeft + w1, footerY);
            const w2 = doc.getTextWidth("Source");

            doc.setFont("helvetica", "italic");
            doc.setTextColor(55, 65, 81);
            const sDesc = sumberText.replace(/^(Sumber\s*[\/:]\s*Source|Sumber|Source)\s*[:]\s*/i, '').trim();
            doc.text(": " + sDesc, marginLeft + w1 + w2, footerY);
        }

        const safeTitle = cleanJudulId.substring(0, 50).replace(/[/\\?%*:|"<>]/g, '-');
        const filename = 'Tabel_' + (nomor ? nomor + '_' : '') + safeTitle + '.pdf';
        doc.save(filename);
    }
});

function applyYearFilter() {
    var checked = Array.from(document.querySelectorAll('.year-filter-check:checked')).map(function(cb) { return cb.value; });
    if (!checked.length) {
        alert('Pilih minimal 1 tahun untuk ditampilkan.');
        return;
    }
    var url = new URL(window.location.href);
    url.searchParams.set('tahun', checked.join(','));
    window.location.href = url.toString();
}

function toggleAllYears() {
    var checkboxes = document.querySelectorAll('.year-filter-check');
    var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
    checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
    applyYearFilter();
}

function resetYearFilter() {
    var url = new URL(window.location.href);
    url.searchParams.delete('tahun');
    url.searchParams.delete('years');
    window.location.href = url.toString();
}
</script>