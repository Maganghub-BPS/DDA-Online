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

// Ekstraksi Nomor Tabel & Pembersihan Judul
$display_title = $tabel->judul_ind ?? 'Tabel Data';
$display_title = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $display_title);

$no_tabel = $tabel->no_tabel ?? '';
$cleanJudulInd = $display_title;

// Jika no_tabel kosong atau judul diawali "Tabel X.X.X"
if (preg_match('/^Tabel\s+([\d\.\w]+)\s+(.*?)(?:,\s*(\d{4}(?:-\d{4})?))?$/is', trim($display_title), $m)) {
    if (empty($no_tabel)) {
        $no_tabel = $m[1];
    }
    $cleanJudulInd = trim($m[2]);
    if (isset($m[3]) && $m[3]) {
        $cleanJudulInd .= ', ' . $m[3];
    }
} else {
    $cleanJudulInd = preg_replace('/^Tabel\s+([\d\.\w]+)?\s*/i', '', $cleanJudulInd);
}

if (!empty($no_tabel)) {
    $cleanJudulInd = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/i', '', $cleanJudulInd);
}

// Tambahkan periode waktu jika belum tercantum di akhir judul
$periode_str = trim((string)($tabel->periode_id ?? $tabel->periode ?? ''));
if (!empty($periode_str) && stripos($cleanJudulInd, $periode_str) === false) {
    $cleanJudulInd = rtrim($cleanJudulInd, " ,") . ', ' . $periode_str;
}

// Judul Bahasa Inggris
$eng_title_raw = $tabel->judul_en ?: ($tabel->judul_ind ?? '');
$eng_title_raw = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $eng_title_raw);
$cleanJudulEn = $eng_title_raw;

if (preg_match('/^Table\s+([\d\.\w]+)\s+(.*?)(?:,\s*(\d{4}(?:-\d{4})?))?$/is', trim($eng_title_raw), $m)) {
    $cleanJudulEn = trim($m[2]);
    if (isset($m[3]) && $m[3]) {
        $cleanJudulEn .= ', ' . $m[3];
    }
} else {
    $cleanJudulEn = preg_replace('/^Table\s+([\d\.\w]+)?\s*/i', '', $cleanJudulEn);
}

if (!empty($no_tabel)) {
    $cleanJudulEn = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/i', '', $cleanJudulEn);
}

$periode_en_str = trim((string)($tabel->periode_en ?? $tabel->periode_id ?? $tabel->periode ?? ''));
if (!empty($periode_en_str) && stripos($cleanJudulEn, $periode_en_str) === false) {
    $cleanJudulEn = rtrim($cleanJudulEn, " ,") . ', ' . $periode_en_str;
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

// Cek Kelengkapan Konfigurasi Dimensi
$dimCount = (!empty($itemsBaris) ? 1 : 0) + (!empty($structureKolom) ? 1 : 0) + (!empty($itemsPeriode) ? 1 : 0);
$isConfigured = ($indikator && $dimCount >= 2);
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
            
            <!-- Right: Unduh Data Modern Navy Pill Dropdown -->
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

                <!-- 3. Matriks Data Utama Responsif -->
                <div class="table-responsive-dda" id="table-container">
                    <table class="main-table dda-table-item" id="tabel-dinamis-publik" style="width: 100% !important; min-width: 100%; border-collapse: collapse; background: #ffffff;">
                        <thead style="background: #154679; color: #fff;">
                            <?php
                            $maxDepth       = !empty($structureKolom['max_depth']) ? (int)$structureKolom['max_depth'] : (!empty($structureKolom['has_groups']) ? 2 : 1);
                            $tingkat_kolom  = $indikator->tingkat_kolom ?? 'semua';

                            $parseActiveLevelsPhp = function(?string $val, int $depth): array {
                                if (empty($val) || $val === 'semua') {
                                    $arr = [];
                                    for ($i = 1; $i <= $depth; $i++) $arr[] = $i;
                                    return $arr;
                                }
                                if ($val === 'sub_bab') {
                                    return ($depth >= 3) ? [2, 3] : [2];
                                }
                                if ($val === 'bab') {
                                    return [1];
                                }
                                $parts = array_filter(array_map('intval', explode(',', $val)));
                                $parts = array_values(array_filter($parts, function($l) use ($depth) {
                                    return $l >= 1 && $l <= $depth;
                                }));
                                if (!empty($parts)) return $parts;
                                $arr = [];
                                for ($i = 1; $i <= $depth; $i++) $arr[] = $i;
                                return $arr;
                            };

                            $activeLevels = $parseActiveLevelsPhp($tingkat_kolom, $maxDepth);
                            sort($activeLevels);

                            $groups         = $structureKolom['groups'] ?? [];
                            $rawFlatKolom   = $structureKolom['flat_items'] ?? $itemsKolom;

                            // Tentukan flatKolom yang digunakan berdasarkan tingkat aktif terendah
                            $flatKolom = $rawFlatKolom;
                            if (!empty($structureKolom) && $maxDepth >= 3) {
                                if (in_array($maxDepth, $activeLevels) || in_array(3, $activeLevels)) {
                                    $flatKolom = $rawFlatKolom;
                                } elseif (in_array(2, $activeLevels)) {
                                    $flatKolom = [];
                                    foreach ($groups as $grp) {
                                        if (!empty($grp['subgroups'])) {
                                            foreach ($grp['subgroups'] as $sub) {
                                                $firstChild = !empty($sub['children']) ? $sub['children'][0] : null;
                                                $flatKolom[] = (object)[
                                                    'id'       => $firstChild ? (int)$firstChild->id : 0,
                                                    'item_ind' => $sub['title_ind'],
                                                    'item_en'  => $sub['title_en'] ?? $sub['title_ind'],
                                                ];
                                            }
                                        } else {
                                            $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                                            $flatKolom[] = (object)[
                                                'id'       => $firstChild ? (int)$firstChild->id : 0,
                                                'item_ind' => $grp['title_ind'],
                                                'item_en'  => $grp['title_en'] ?? $grp['title_ind'],
                                            ];
                                        }
                                    }
                                } else {
                                    $flatKolom = [];
                                    foreach ($groups as $grp) {
                                        $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                                        $flatKolom[] = (object)[
                                            'id'       => $firstChild ? (int)$firstChild->id : 0,
                                            'item_ind' => $grp['title_ind'],
                                            'item_en'  => $grp['title_en'] ?? $grp['title_ind'],
                                        ];
                                    }
                                }
                            } elseif (!empty($structureKolom) && $maxDepth === 2) {
                                if (in_array(2, $activeLevels)) {
                                    $flatKolom = $rawFlatKolom;
                                } else {
                                    $flatKolom = [];
                                    foreach ($groups as $grp) {
                                        $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                                        $flatKolom[] = (object)[
                                            'id'       => $firstChild ? (int)$firstChild->id : 0,
                                            'item_ind' => $grp['title_ind'],
                                            'item_en'  => $grp['title_en'] ?? $grp['title_ind'],
                                        ];
                                    }
                                }
                            }

                            $hasBaris           = !empty($itemsBaris);
                            $hasKolom           = !empty($flatKolom);
                            $hasPeriode         = !empty($itemsPeriode);
                            $posisi_hirarki     = $indikator->posisi_hirarki ?? 'kolom_atas';
                            $cfgTotal           = $indikator->konfigurasi_total ?? 'default';
                            $isRuntunWaktu      = ($cfgTotal === 'runtun_waktu');

                            // Konfigurasi Kolom No: ditentukan eksplisit dari Generator Tabel (tampilkan vs sembunyikan)
                            $cfgKolomNomor = $indikator->kolom_nomor ?? 'tampilkan';
                            $hasNoCol      = ($cfgKolomNomor === 'tampilkan');

                            // Identifikasi id_periode aktif untuk baris rincian pada mode runtun_waktu
                            $idPeriodeAktif = 0;
                            if (!empty($itemsPeriode)) {
                                foreach ($itemsPeriode as $p) {
                                    if (!empty($tabel->tahun) && trim((string)$p->nama_periode_ind) === trim((string)$tabel->tahun)) {
                                        $idPeriodeAktif = (int)$p->id;
                                        break;
                                    }
                                }
                                if (!$idPeriodeAktif) {
                                    $idPeriodeAktif = (int)$itemsPeriode[0]->id;
                                }
                            }

                            $isPeriodeBaris     = in_array($posisi_hirarki, ['periode_baris', 'periode_baris_pisah'], true);
                            $isPeriodeBarisPisah = ($posisi_hirarki === 'periode_baris_pisah');

                            // Hitung level maksimum pada seluruh kelompok baris
                            $maxBarisGroupLevel = 0;
                            if (!empty($itemsBaris)) {
                                foreach ($itemsBaris as $b) {
                                    $lvl = (int)($b->level_indentasi ?? 0);
                                    if ($lvl > $maxBarisGroupLevel) {
                                        $maxBarisGroupLevel = $lvl;
                                    }
                                }
                            }
                            $totalTingkat = $maxBarisGroupLevel + 1;
                            $isBarisDuaKolom    = (!$isPeriodeBaris && !$isRuntunWaktu && $hasBaris && ($indikator->tampilan_baris ?? '1_kolom') === '2_kolom');
                            if ($isBarisDuaKolom && empty($cleanTitleSubInd)) {
                                $cleanTitleSubInd = 'Rincian';
                                $titleSubBarisEn  = 'Details';
                            }

                            // Deteksi Periode Bertingkat (Format pemisah '||', misal: "2024 || Maret")
                            $hasMultiPeriode = false;
                            $parsedPeriode = [];
                            $periodeGroups = [];

                            if (!empty($itemsPeriode)) {
                                foreach ($itemsPeriode as $p) {
                                    if (strpos($p->nama_periode_ind ?? '', '||') !== false) {
                                        $hasMultiPeriode = true;
                                        break;
                                    }
                                }

                                if ($hasMultiPeriode) {
                                    $currGroup = null;
                                    foreach ($itemsPeriode as $p) {
                                        $pInd = array_map('trim', explode('||', $p->nama_periode_ind ?? ''));
                                        $pEn  = !empty($p->nama_periode_en) ? array_map('trim', explode('||', $p->nama_periode_en)) : [];
                                        $itemObj = (object)[
                                            'raw'       => $p,
                                            'id'        => $p->id,
                                            'parentInd' => $pInd[0] ?? '',
                                            'childInd'  => $pInd[1] ?? ($pInd[0] ?? ''),
                                            'parentEn'  => $pEn[0] ?? '',
                                            'childEn'   => $pEn[1] ?? '',
                                        ];
                                        $parsedPeriode[] = $itemObj;

                                        if ($currGroup === null || $currGroup['parentInd'] !== $itemObj->parentInd) {
                                            if ($currGroup !== null) {
                                                $periodeGroups[] = $currGroup;
                                            }
                                            $currGroup = [
                                                'parentInd' => $itemObj->parentInd,
                                                'parentEn'  => $itemObj->parentEn,
                                                'children'  => [$itemObj]
                                            ];
                                        } else {
                                            $currGroup['children'][] = $itemObj;
                                        }
                                    }
                                    if ($currGroup !== null) {
                                        $periodeGroups[] = $currGroup;
                                    }
                                }
                            }

                            $extraPeriodeRow = ($hasMultiPeriode && !$isPeriodeBaris && !$isRuntunWaktu) ? 1 : 0;

                            // Tentukan jumlah baris header
                            $totalHeaderRows = 1;
                            if (!$hasBaris && $hasKolom && $hasPeriode) {
                                $totalHeaderRows = count($activeLevels);
                            } elseif ($hasKolom && $hasPeriode) {
                                $totalHeaderRows = ($isPeriodeBaris || $isRuntunWaktu) ? count($activeLevels) : (count($activeLevels) + 1 + $extraPeriodeRow);
                            } elseif ($hasKolom) {
                                $totalHeaderRows = count($activeLevels);
                            } elseif ($hasPeriode) {
                                $totalHeaderRows = 1 + $extraPeriodeRow;
                            }

                            // Hitung daftar kolom leaf terujung
                            $leafColumns = [];
                            if (!$hasBaris && $hasKolom && $hasPeriode) {
                                // Kasus Runtun Waktu Makro (Tanpa Baris Wilayah)
                                foreach ($flatKolom as $child) {
                                    $leafColumns[] = [
                                        'item_id'    => (int)$child->id,
                                        'periode_id' => 0,
                                    ];
                                }
                            } elseif (!$hasKolom && $hasPeriode) {
                                foreach ($itemsPeriode as $p) {
                                    $leafColumns[] = [
                                        'item_id'    => 0,
                                        'periode_id' => (int)$p->id,
                                    ];
                                }
                            } elseif ($hasKolom && !$hasPeriode) {
                                foreach ($flatKolom as $child) {
                                    $leafColumns[] = [
                                        'item_id'    => (int)$child->id,
                                        'periode_id' => 0,
                                    ];
                                }
                            } elseif ($hasKolom && $hasPeriode) {
                                if ($isRuntunWaktu) {
                                    foreach ($flatKolom as $child) {
                                        $leafColumns[] = [
                                            'item_id'    => (int)$child->id,
                                            'periode_id' => $idPeriodeAktif,
                                        ];
                                    }
                                } elseif ($isPeriodeBaris) {
                                    foreach ($flatKolom as $child) {
                                        $leafColumns[] = [
                                            'item_id'    => (int)$child->id,
                                            'periode_id' => null,
                                        ];
                                    }
                                } elseif ($posisi_hirarki === 'periode_atas') {
                                    foreach ($itemsPeriode as $p) {
                                        foreach ($flatKolom as $child) {
                                            $leafColumns[] = [
                                                'item_id'    => (int)$child->id,
                                                'periode_id' => (int)$p->id,
                                            ];
                                        }
                                    }
                                } else {
                                    foreach ($flatKolom as $child) {
                                        foreach ($itemsPeriode as $p) {
                                            $leafColumns[] = [
                                                'item_id'    => (int)$child->id,
                                                'periode_id' => (int)$p->id,
                                            ];
                                        }
                                    }
                                }
                            }
                            $totalLeafCols = count($leafColumns);

                            // Helper PHP untuk merender sel header suatu tingkat kolom
                            $getLevelCellsPhp = function(int $lvl, int $multiplier) use ($groups, $flatKolom, $maxDepth, $activeLevels, $structureKolom): string {
                                $isAllLevelsActive = (count($activeLevels) === $maxDepth);
                                if ($isAllLevelsActive && !empty($structureKolom['header_rows'][$lvl])) {
                                    $html = '';
                                    foreach ($structureKolom['header_rows'][$lvl] as $cell) {
                                        $colspan = (int)$cell['colspan'] * $multiplier;
                                        $rowspan = (int)$cell['rowspan'];
                                        $fontSize = (!empty($cell['is_leaf']) && $cell['rowspan'] === 1) ? 'font-size: 11px;' : '';
                                        $html .= '<th' 
                                            . ($colspan > 1 ? ' colspan="' . $colspan . '"' : '') 
                                            . ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '') 
                                            . ' style="border: 1px solid #fff; text-align: center; vertical-align: middle; ' . $fontSize . '">';
                                        $html .= esc($cell['title_ind']);
                                        if (!empty($cell['title_en'])) {
                                            $html .= '<br><i>' . esc($cell['title_en']) . '</i>';
                                        }
                                        $html .= '</th>';
                                    }
                                    return $html;
                                }

                                $html = '';
                                $hasL1 = in_array(1, $activeLevels);
                                $hasL2 = in_array(2, $activeLevels);
                                $hasL3 = in_array(3, $activeLevels);

                                if ($maxDepth === 1) {
                                    if ($lvl === 1) {
                                        foreach ($flatKolom as $item) {
                                            $html .= '<th style="border: 1px solid #fff; text-align: center; vertical-align: middle;" colspan="' . $multiplier . '">';
                                            $html .= esc($item->item_ind);
                                            if (!empty($item->item_en)) {
                                                $html .= '<br><i>' . esc($item->item_en) . '</i>';
                                            }
                                            $html .= '</th>';
                                        }
                                    }
                                } elseif ($maxDepth === 2) {
                                    if ($lvl === 1) {
                                        foreach ($groups as $grp) {
                                            if ($grp['type'] === 'group') {
                                                $leafCount = $hasL2 ? count($grp['children']) : 1;
                                                $colspan = $leafCount * $multiplier;
                                                $html .= '<th colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">';
                                                $html .= esc($grp['title_ind']);
                                                if (!empty($grp['title_en'])) {
                                                    $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                }
                                                $html .= '</th>';
                                            } else {
                                                $colspan = 1 * $multiplier;
                                                $rowspan = $hasL2 ? 2 : 1;
                                                $html .= '<th rowspan="' . $rowspan . '" colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">';
                                                $html .= esc($grp['title_ind']);
                                                if (!empty($grp['title_en'])) {
                                                    $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                }
                                                $html .= '</th>';
                                            }
                                        }
                                    } elseif ($lvl === 2) {
                                        foreach ($groups as $grp) {
                                            if ($grp['type'] === 'group') {
                                                foreach ($grp['children'] as $child) {
                                                    $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle; font-size: 11px;">';
                                                    $html .= esc($child->item_ind);
                                                    if (!empty($child->item_en)) {
                                                        $html .= '<br><i>' . esc($child->item_en) . '</i>';
                                                    }
                                                    $html .= '</th>';
                                                }
                                            } else {
                                                if (!$hasL1) {
                                                    $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle; font-size: 11px;">';
                                                    $html .= esc($grp['title_ind']);
                                                    if (!empty($grp['title_en'])) {
                                                        $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                    }
                                                    $html .= '</th>';
                                                }
                                            }
                                        }
                                    }
                                } elseif ($maxDepth === 3) {
                                    if ($lvl === 1) {
                                        foreach ($groups as $grp) {
                                            if ($grp['type'] === 'group') {
                                                if ($hasL3) {
                                                    $leafCount = count($grp['children']);
                                                } elseif ($hasL2 && !empty($grp['subgroups'])) {
                                                    $leafCount = count($grp['subgroups']);
                                                } else {
                                                    $leafCount = 1;
                                                }
                                                $colspan = $leafCount * $multiplier;
                                                $html .= '<th colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">';
                                                $html .= esc($grp['title_ind']);
                                                if (!empty($grp['title_en'])) {
                                                    $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                }
                                                $html .= '</th>';
                                            } else {
                                                $colspan = 1 * $multiplier;
                                                $rowspan = count($activeLevels);
                                                $html .= '<th rowspan="' . $rowspan . '" colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">';
                                                $html .= esc($grp['title_ind']);
                                                if (!empty($grp['title_en'])) {
                                                    $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                }
                                                $html .= '</th>';
                                            }
                                        }
                                    } elseif ($lvl === 2) {
                                        foreach ($groups as $grp) {
                                            if ($grp['type'] === 'group') {
                                                if (!empty($grp['subgroups'])) {
                                                    foreach ($grp['subgroups'] as $sub) {
                                                        $leafCount = $hasL3 ? count($sub['children']) : 1;
                                                        $colspan = $leafCount * $multiplier;
                                                        $rowspan = ($sub['type'] === 'standalone' && $hasL3) ? 2 : 1;
                                                        $html .= '<th colspan="' . $colspan . '" rowspan="' . $rowspan . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">';
                                                        $html .= esc($sub['title_ind']);
                                                        if (!empty($sub['title_en'])) {
                                                            $html .= '<br><i>' . esc($sub['title_en']) . '</i>';
                                                        }
                                                        $html .= '</th>';
                                                    }
                                                } else {
                                                    if ($hasL3) {
                                                        foreach ($grp['children'] as $child) {
                                                            $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle; font-size: 11px;">';
                                                            $html .= esc($child->item_ind);
                                                            if (!empty($child->item_en)) {
                                                                $html .= '<br><i>' . esc($child->item_en) . '</i>';
                                                            }
                                                            $html .= '</th>';
                                                        }
                                                    } else {
                                                        $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">';
                                                        $html .= esc($grp['title_ind']);
                                                        if (!empty($grp['title_en'])) {
                                                            $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                        }
                                                        $html .= '</th>';
                                                    }
                                                }
                                            } else {
                                                if (!$hasL1) {
                                                    $colspan = 1 * $multiplier;
                                                    $rowspan = $hasL3 ? 2 : 1;
                                                    $html .= '<th rowspan="' . $rowspan . '" colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">';
                                                    $html .= esc($grp['title_ind']);
                                                    if (!empty($grp['title_en'])) {
                                                        $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                    }
                                                    $html .= '</th>';
                                                }
                                            }
                                        }
                                    } elseif ($lvl === 3) {
                                        foreach ($groups as $grp) {
                                            if ($grp['type'] === 'group') {
                                                if (!empty($grp['subgroups'])) {
                                                    foreach ($grp['subgroups'] as $sub) {
                                                        if ($sub['type'] === 'standalone' && ($hasL2 || $hasL1)) {
                                                            continue;
                                                        }
                                                        foreach ($sub['children'] as $child) {
                                                            $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle; font-size: 11px;">';
                                                            $html .= esc($child->item_ind);
                                                            if (!empty($child->item_en)) {
                                                                $html .= '<br><i>' . esc($child->item_en) . '</i>';
                                                            }
                                                            $html .= '</th>';
                                                        }
                                                    }
                                                } else {
                                                    if (!$hasL2 && !$hasL1) {
                                                        foreach ($grp['children'] as $child) {
                                                            $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle; font-size: 11px;">';
                                                            $html .= esc($child->item_ind);
                                                            if (!empty($child->item_en)) {
                                                                $html .= '<br><i>' . esc($child->item_en) . '</i>';
                                                            }
                                                            $html .= '</th>';
                                                        }
                                                    }
                                                }
                                            } else {
                                                if (!$hasL1 && !$hasL2) {
                                                    $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; vertical-align: middle; font-size: 11px;">';
                                                    $html .= esc($grp['title_ind']);
                                                    if (!empty($grp['title_en'])) {
                                                        $html .= '<br><i>' . esc($grp['title_en']) . '</i>';
                                                    }
                                                    $html .= '</th>';
                                                }
                                            }
                                        }
                                    }
                                }
                                return $html;
                            };
                            ?>

                            <!-- BARIS HEADER 1: Kolom Pertama & Karakteristik Induk / Periode Induk -->
                            <tr>
                                <?php if (!$hasBaris && $hasKolom && $hasPeriode): ?>
                                    <!-- KASUS KHUSUS: TANPA DIMENSI BARIS (PERIODE WAKTU SEBAGAI SUMBU BARIS) -->
                                    <?php if ($hasNoCol): ?>
                                        <th rowspan="<?= $totalHeaderRows + 1; ?>" style="border: 1px solid #fff; width: 45px; text-align: center; vertical-align: middle;">No.</th>
                                    <?php endif; ?>
                                    <?php if ($hasMultiPeriode): ?>
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 140px; text-align: center; vertical-align: middle;">
                                            <?= esc($titlePeriodeInd); ?>
                                            <?php if (!empty($titlePeriodeEn) && $titlePeriodeEn !== $titlePeriodeInd): ?>
                                                <br><i><?= esc($titlePeriodeEn); ?></i>
                                            <?php endif; ?>
                                        </th>
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 110px; text-align: center; vertical-align: middle;">
                                            Rincian
                                            <?php if (!empty($titlePeriodeEn)): ?>
                                                <br><i>Details</i>
                                            <?php endif; ?>
                                        </th>
                                    <?php else: ?>
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 140px; text-align: center; vertical-align: middle;">
                                            <?= esc($titlePeriodeInd); ?>
                                            <?php if (!empty($titlePeriodeEn) && $titlePeriodeEn !== $titlePeriodeInd): ?>
                                                <br><i><?= esc($titlePeriodeEn); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php endif; ?>
                                    <?= $getLevelCellsPhp($activeLevels[0], 1); ?>
                                <?php else: ?>
                                    <?php if ($hasNoCol && !$isRuntunWaktu): ?>
                                        <th rowspan="<?= $totalHeaderRows + 1; ?>" style="border: 1px solid #fff; width: 45px; text-align: center; vertical-align: middle;">No.</th>
                                    <?php endif; ?>

                                    <?php if ($isMultiBaris && !empty($titleCol2Ind)): ?>
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 180px; text-align: center; vertical-align: middle;">
                                            <?= esc($titleCol1Ind); ?>
                                            <?php if (!empty($titleCol1En) && strtolower($titleCol1En) !== strtolower($titleCol1Ind)): ?>
                                                <br><i><?= esc($titleCol1En); ?></i>
                                            <?php endif; ?>
                                        </th>
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 90px; text-align: center; vertical-align: middle;">
                                            <?= esc($titleCol2Ind); ?>
                                            <?php if (!empty($titleCol2En) && strtolower($titleCol2En) !== strtolower($titleCol2Ind)): ?>
                                                <br><i><?= esc($titleCol2En); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php elseif ($isMultiBaris): ?>
                                        <!-- Header tunggal di-merge horizontal colspan 2 jika tidak ada rincian kolom 2 -->
                                        <th rowspan="<?= $totalHeaderRows; ?>" colspan="2" style="border: 1px solid #fff; min-width: 270px; text-align: center; vertical-align: middle;">
                                            <?= esc($titleCol1Ind); ?>
                                            <?php if (!empty($titleCol1En) && strtolower($titleCol1En) !== strtolower($titleCol1Ind)): ?>
                                                <br><i><?= esc($titleCol1En); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php elseif ($isRuntunWaktu): ?>
                                        <th rowspan="<?= $totalHeaderRows; ?>" colspan="2" style="border: 1px solid #fff; min-width: 220px; text-align: center; vertical-align: middle;">
                                            <?= esc($cleanTitleInd); ?>
                                            <?php if (!empty($titleBarisEn)): ?>
                                                <br><i><?= esc($titleBarisEn); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php elseif ($posisi_hirarki === 'periode_baris'): ?>
                                        <!-- MODE GABUNG HEADER: Kolom 1 & 2 Dimerge Horizontal (1) -->
                                        <th rowspan="<?= $totalHeaderRows; ?>" colspan="2" style="border: 1px solid #fff; min-width: 220px; text-align: center; vertical-align: middle;">
                                            <?= esc($cleanTitleInd); ?>
                                            <?php if (!empty($titleBarisEn)): ?>
                                                <br><i><?= esc($titleBarisEn); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php else: ?>
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 220px; text-align: center; vertical-align: middle;">
                                            <?= esc($cleanTitleInd); ?>
                                            <?php if (!empty($titleBarisEn)): ?>
                                                <br><i><?= esc($titleBarisEn); ?></i>
                                            <?php endif; ?>
                                        </th>
                                        <?php if ($hasKolom && $hasPeriode && ($posisi_hirarki === 'periode_baris' || $posisi_hirarki === 'periode_baris_pisah')): ?>
                                            <!-- MODE PISAH HEADER: Kolom 2 Memiliki Header Tersendiri -->
                                            <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 90px; text-align: center; vertical-align: middle;">
                                                <?= esc($titlePeriodeInd); ?>
                                                <?php if (!empty($titlePeriodeEn) && $titlePeriodeEn !== $titlePeriodeInd): ?>
                                                    <br><i><?= esc($titlePeriodeEn); ?></i>
                                                <?php endif; ?>
                                            </th>
                                        <?php elseif ($isBarisDuaKolom): ?>
                                            <!-- MODE 2 KOLOM BARIS BERTINGKAT: Kolom 2 Rincian (misal: Provinsi) -->
                                            <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 130px; text-align: center; vertical-align: middle;">
                                                <?= esc($cleanTitleSubInd); ?>
                                                <?php if (!empty($titleSubBarisEn) && strtolower($titleSubBarisEn) !== strtolower($cleanTitleSubInd)): ?>
                                                    <br><i><?= esc($titleSubBarisEn); ?></i>
                                                <?php endif; ?>
                                            </th>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (!$hasKolom && $hasPeriode): ?>
                                        <!-- KASUS 1: Hanya Periode Waktu -->
                                        <?php if ($hasMultiPeriode): ?>
                                            <?php foreach ($periodeGroups as $g): ?>
                                                <th colspan="<?= count($g['children']); ?>" style="border: 1px solid #fff; text-align: center; font-weight: bold;">
                                                    <?= esc($g['parentInd']); ?>
                                                    <?php if (!empty($g['parentEn']) && $g['parentEn'] !== $g['parentInd']): ?>
                                                        <br><i><?= esc($g['parentEn']); ?></i>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php foreach ($itemsPeriode as $p): ?>
                                                <th style="border: 1px solid #fff; text-align: center; vertical-align: middle;">
                                                    <?= esc($p->nama_periode_ind); ?>
                                                    <?php if (!empty($p->nama_periode_en) && $p->nama_periode_en !== $p->nama_periode_ind): ?>
                                                        <br><i><?= esc($p->nama_periode_en); ?></i>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                    <?php elseif ($hasKolom && !$hasPeriode): ?>
                                        <!-- KASUS 2: Hanya Karakteristik Kolom (Baris 1) -->
                                        <?= $getLevelCellsPhp($activeLevels[0], 1); ?>

                                    <?php elseif ($hasKolom && $hasPeriode): ?>
                                        <!-- KASUS 3: Kedua Dimensi Ada -->
                                        <?php if ($isRuntunWaktu): ?>
                                            <?= $getLevelCellsPhp($activeLevels[0], 1); ?>
                                        <?php elseif ($isPeriodeBaris): ?>
                                            <?= $getLevelCellsPhp($activeLevels[0], 1); ?>
                                        <?php elseif ($posisi_hirarki === 'periode_atas'): ?>
                                            <!-- PERIODE WAKTU DI ATAS -->
                                            <?php foreach ($itemsPeriode as $p): ?>
                                                <th colspan="<?= count($flatKolom); ?>" style="border: 1px solid #fff; text-align: center; vertical-align: middle;">
                                                    <?= esc($p->nama_periode_ind); ?>
                                                    <?php if (!empty($p->nama_periode_en) && $p->nama_periode_en !== $p->nama_periode_ind): ?>
                                                        <br><i><?= esc($p->nama_periode_en); ?></i>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- KARAKTERISTIK KOLOM DI ATAS (Baris 1) -->
                                            <?= $getLevelCellsPhp($activeLevels[0], count($itemsPeriode)); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tr>

                            <!-- BARIS HEADER LANJUTAN (SUB-KOLOM / TINGKAT KOLOM LAIN / PERIODE DI BAWAH) -->
                            <?php if (!$hasBaris && $hasKolom && $hasPeriode): ?>
                                <?php for ($idx = 1; $idx < count($activeLevels); $idx++): ?>
                                    <tr>
                                        <?= $getLevelCellsPhp($activeLevels[$idx], 1); ?>
                                    </tr>
                                <?php endfor; ?>

                            <?php elseif ($hasKolom && !$hasPeriode): ?>
                                <?php for ($idx = 1; $idx < count($activeLevels); $idx++): ?>
                                    <tr>
                                        <?= $getLevelCellsPhp($activeLevels[$idx], 1); ?>
                                    </tr>
                                <?php endfor; ?>

                            <?php elseif ($hasKolom && $hasPeriode && ($isPeriodeBaris || $isRuntunWaktu)): ?>
                                <?php for ($idx = 1; $idx < count($activeLevels); $idx++): ?>
                                    <tr>
                                        <?= $getLevelCellsPhp($activeLevels[$idx], 1); ?>
                                    </tr>
                                <?php endfor; ?>

                            <?php elseif ($hasKolom && $hasPeriode && $posisi_hirarki === 'periode_atas'): ?>
                                <?php foreach ($activeLevels as $lvl): ?>
                                    <tr>
                                        <?php foreach ($itemsPeriode as $p): ?>
                                            <?= $getLevelCellsPhp($lvl, 1); ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>

                            <?php elseif ($hasKolom && $hasPeriode && $posisi_hirarki !== 'periode_atas' && !$isPeriodeBaris): ?>
                                <?php for ($idx = 1; $idx < count($activeLevels); $idx++): ?>
                                    <tr>
                                        <?= $getLevelCellsPhp($activeLevels[$idx], count($itemsPeriode)); ?>
                                    </tr>
                                <?php endfor; ?>

                                <?php if ($hasMultiPeriode): ?>
                                    <!-- Baris Header Periode Tingkat 1 (Parent, misal Tahun) -->
                                    <tr>
                                        <?php for ($c = 0; $c < count($flatKolom); $c++): ?>
                                            <?php foreach ($periodeGroups as $g): ?>
                                                <th colspan="<?= count($g['children']); ?>" style="border: 1px solid #fff; text-align: center; vertical-align: middle; padding: 4px 6px; font-size: 11px; font-weight: bold;">
                                                    <?= esc($g['parentInd']); ?>
                                                    <?php if (!empty($g['parentEn']) && $g['parentEn'] !== $g['parentInd']): ?>
                                                        <br><i><?= esc($g['parentEn']); ?></i>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endfor; ?>
                                    </tr>

                                    <!-- Baris Header Periode Tingkat 2 (Child, misal Bulan / Triwulan) -->
                                    <tr>
                                        <?php for ($c = 0; $c < count($flatKolom); $c++): ?>
                                            <?php foreach ($parsedPeriode as $p): ?>
                                                <th style="border: 1px solid #fff; text-align: center; vertical-align: middle; padding: 4px 6px; font-size: 11px;">
                                                    <?= esc($p->childInd); ?>
                                                    <?php if (!empty($p->childEn) && $p->childEn !== $p->childInd): ?>
                                                        <br><i><?= esc($p->childEn); ?></i>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endfor; ?>
                                    </tr>
                                <?php else: ?>
                                    <!-- Baris Header Terbawah: Periode Waktu diulang di bawah masing-masing kolom daun -->
                                    <tr>
                                        <?php for ($c = 0; $c < count($flatKolom); $c++): ?>
                                            <?php foreach ($itemsPeriode as $p): ?>
                                                <th style="border: 1px solid #fff; text-align: center; vertical-align: middle; padding: 4px 6px; font-size: 11px;">
                                                    <?= esc($p->nama_periode_ind); ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- BARIS PENOMORAN KOLOM RESMI BPS: (1), (2), (3) ... -->
                            <tr class="num-row row-nomor-kolom" style="background: #154679; color: #ffffff; font-size: 11px; font-weight: bold;">
                                <?php 
                                $colNum = 1;
                                if (!$hasBaris && $hasKolom && $hasPeriode): 
                                ?>
                                    <th style="border: 1px solid rgba(255,255,255,0.2); text-align: center; font-weight: bold; background: #154679; color: #ffffff;">(<?= $colNum++; ?>)</th>
                                <?php else: ?>
                                    <?php if ($isRuntunWaktu || ($posisi_hirarki === 'periode_baris' && !$isMultiBaris)): ?>
                                        <th colspan="2" style="border: 1px solid rgba(255,255,255,0.2); text-align: center; font-weight: bold; background: #154679; color: #ffffff;">(<?= $colNum++; ?>)</th>
                                    <?php else: ?>
                                        <th style="border: 1px solid rgba(255,255,255,0.2); text-align: center; font-weight: bold; background: #154679; color: #ffffff;">(<?= $colNum++; ?>)</th>
                                        <?php if ($isMultiBaris || $isBarisDuaKolom): ?>
                                            <th style="border: 1px solid rgba(255,255,255,0.2); text-align: center; font-weight: bold; background: #154679; color: #ffffff;">(<?= $colNum++; ?>)</th>
                                        <?php endif; ?>
                                        <?php if ($hasKolom && $hasPeriode && ($posisi_hirarki === 'periode_baris' || $posisi_hirarki === 'periode_baris_pisah')): ?>
                                            <th style="border: 1px solid rgba(255,255,255,0.2); text-align: center; font-weight: bold; background: #154679; color: #ffffff;">(<?= $colNum++; ?>)</th>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($n = 1; $n <= $totalLeafCols; $n++): ?>
                                    <th style="border: 1px solid rgba(255,255,255,0.2); text-align: center; font-weight: bold; background: #154679; color: #ffffff;">(<?= $colNum++; ?>)</th>
                                <?php endfor; ?>
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            // Deteksi otomatis format angka desimal cerdas
                            $cfgDesimal = (int)($indikator->desimal ?? 0);
                            $hasDecimalData = false;
                            $maxDecFound = 0;
                            if (!empty($nilaiMap)) {
                                foreach ($nilaiMap as $nmVal) {
                                    if (is_numeric($nmVal) && abs((float)$nmVal - round((float)$nmVal)) > 0.00001) {
                                        $hasDecimalData = true;
                                        $str = rtrim(rtrim(sprintf('%.6f', (float)$nmVal), '0'), '.');
                                        $p = explode('.', $str);
                                        if (isset($p[1])) {
                                            $maxDecFound = max($maxDecFound, strlen($p[1]));
                                        }
                                    }
                                }
                            }
                            // Jika ada konfigurasi eksplisit di indikator ($cfgDesimal > 0), gunakan itu.
                            // Jika belum diset ($cfgDesimal == 0), gunakan digit desimal tertinggi yang ditemukan pada data tabel ($maxDecFound).
                            $desimal = ($cfgDesimal > 0) ? $cfgDesimal : $maxDecFound;

                            $formatCellVal = function($cellVal, int $desimal = 0): string {
                                if ($cellVal === null || $cellVal === '') {
                                    return '–';
                                }
                                if (!is_numeric($cellVal)) {
                                    return esc($cellVal);
                                }
                                $fVal = (float)$cellVal;

                                // 1. Jika angka bulat murni (misal 100, 1500), SELALU tampil bulat murni tanpa koma nol
                                if (abs($fVal - round($fVal)) < 0.000001) {
                                    return number_format($fVal, 0, ',', '.');
                                }

                                // 2. Jika angka desimal (pecahan):
                                // Gunakan presisi standar tabel ($desimal), atau jika sel memiliki desimal lebih panjang, jangan dipotong.
                                $str = rtrim(rtrim(sprintf('%.6f', $fVal), '0'), '.');
                                $p = explode('.', $str);
                                $cellDec = isset($p[1]) ? strlen($p[1]) : 0;
                                $decPlaces = max($desimal, $cellDec);

                                return number_format($fVal, $decPlaces, ',', '.');
                            };

                            $dataRowCounter = 0;

                            if (!$hasBaris && $hasKolom && $hasPeriode):
                                if ($hasMultiPeriode):
                                    foreach ($periodeGroups as $g):
                                        foreach ($g['children'] as $cIdx => $p):
                                            $dataRowCounter++;
                                            $rowClass = ($dataRowCounter % 2 === 1) ? 'row-odd' : 'row-even';
                            ?>
                                            <tr class="<?= $rowClass; ?>">
                                                <!-- Kolom No. -->
                                                <td style="border: 1px solid #ffffff; text-align: center; vertical-align: middle; width: 45px; font-weight: normal;"><?= $dataRowCounter; ?>.</td>

                                                <?php if ($cIdx === 0): ?>
                                                    <td rowspan="<?= count($g['children']); ?>" style="text-align: center; border: 1px solid #ffffff; padding: 7px 12px; font-weight: bold; font-size: 12px; vertical-align: middle;">
                                                        <?= esc($g['parentInd']); ?>
                                                        <?php if (!empty($g['parentEn']) && $g['parentEn'] !== $g['parentInd']): ?>
                                                            / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($g['parentEn']); ?></i>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td style="text-align: left; border: 1px solid #ffffff; padding: 7px 12px; font-weight: 600; font-size: 12px; vertical-align: middle;">
                                                    <?= esc($p->childInd); ?>
                                                    <?php if (!empty($p->childEn) && $p->childEn !== $p->childInd): ?>
                                                        / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($p->childEn); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = '0_' . $colLeaf['item_id'] . '_' . $p->id;
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid #ffffff; font-size: 12px; padding: 6px 12px; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                            <?php
                                        endforeach;
                                    endforeach;
                                else:
                                    foreach ($itemsPeriode as $pIdx => $p):
                                        $dataRowCounter++;
                                        $rowClass = ($dataRowCounter % 2 === 1) ? 'row-odd' : 'row-even';
                            ?>
                                        <tr class="<?= $rowClass; ?>">
                                            <!-- Kolom No. -->
                                            <td style="border: 1px solid #ffffff; text-align: center; vertical-align: middle; width: 45px; font-weight: normal;"><?= $dataRowCounter; ?>.</td>

                                            <td style="text-align: left; border: 1px solid #ffffff; padding: 7px 12px; font-weight: bold; font-size: 12px; vertical-align: middle;">
                                                <?= esc($p->nama_periode_ind); ?>
                                                <?php if (!empty($p->nama_periode_en) && $p->nama_periode_en !== $p->nama_periode_ind): ?>
                                                    / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($p->nama_periode_en); ?></i>
                                                <?php endif; ?>
                                            </td>
                                            <?php foreach ($leafColumns as $colLeaf): ?>
                                                <?php
                                                $cellKey = '0_' . $colLeaf['item_id'] . '_' . $p->id;
                                                $cellVal = $nilaiMap[$cellKey] ?? null;
                                                ?>
                                                <td class="col-val" style="text-align: right; border: 1px solid #ffffff; font-size: 12px; padding: 6px 12px; vertical-align: middle;">
                                                    <?= $formatCellVal($cellVal, $desimal); ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                            <?php
                                    endforeach;
                                endif;
                            else:
                                // Hitung level maksimum pada seluruh kelompok baris
                                $maxBarisGroupLevel = 0;
                                foreach ($itemsBaris as $b) {
                                    $lvl = (int)($b->level_indentasi ?? 0);
                                    if ($lvl > $maxBarisGroupLevel) {
                                        $maxBarisGroupLevel = $lvl;
                                    }
                                }
                                $totalTingkat = $maxBarisGroupLevel + 1;
                                $categoryRowCounter = 0;
                                $hasRenderedViewTotal = false;

                                // Helper untuk mendeteksi apakah baris adalah Parent / Header Banner (kategori melintang tanpa sel data)
                                $isParentBaris = function(int $idx, array $items) use ($maxBarisGroupLevel): bool {
                                    $cur = $items[$idx];
                                    $tipe = strtolower(trim($cur->tipe_baris ?? ''));

                                    // 1. Sesuai role badge di Master Dimensi
                                    if ($tipe === 'kategori') {
                                        return true; // Kategori: banner pemisah sekat tanpa sel data
                                    }
                                    if ($tipe === 'data' || $tipe === 'total' || $tipe === 'subtotal') {
                                        return false; // Data / Total: baris fungsional dengan sel data
                                    }

                                    // 2. Fallback untuk data lawas jika belum terisi
                                    if ($maxBarisGroupLevel === 0) {
                                        return false;
                                    }

                                    $lowerNama = strtolower(trim($cur->nama_baris_ind ?? ''));
                                    if (
                                        $lowerNama === 'jumlah' || $lowerNama === 'total' || 
                                        strpos($lowerNama, 'jawa tengah') !== false || 
                                        strpos($lowerNama, 'produk domestik regional bruto') !== false || 
                                        $lowerNama === 'pdrb' || 
                                        strpos($lowerNama, 'gross regional domestic product') !== false || 
                                        $lowerNama === 'grdp'
                                    ) {
                                        if (strpos($lowerNama, 'curah hujan') === false && strpos($lowerNama, 'hari hujan') === false) {
                                            return false;
                                        }
                                    }

                                    if ($lowerNama === 'kabupaten' || $lowerNama === 'kota') {
                                        return true;
                                    }

                                    return false;
                                };

                                // Pre-compute auto-rowspan untuk Kolom 1 jika nilainya sama berturut-turut (hanya Kolom 1, Kolom 2 dikunci mati!)
                                $col1RowspanMap = [];
                                $col1SkipSet = [];

                                if ($isMultiBaris && $posisi_hirarki !== 'periode_baris') {
                                    $i = 0;
                                    $nItems = count($itemsBaris);
                                    while ($i < $nItems) {
                                        $curBaris = $itemsBaris[$i];
                                        $curNama = $curBaris->nama_baris_ind ?? '';
                                        $isParent = $isParentBaris($i, $itemsBaris);
                                        
                                        if ($isParent || strpos($curNama, '||') === false) {
                                            $i++;
                                            continue;
                                        }
                                        
                                        $pCur = explode('||', $curNama);
                                        $curCol1 = trim($pCur[0]);
                                        $span = 1;
                                        $j = $i + 1;
                                        while ($j < $nItems) {
                                            $nextBaris = $itemsBaris[$j];
                                            $nextIsParent = $isParentBaris($j, $itemsBaris);
                                            if ($nextIsParent) break;
                                            
                                            $nextNama = $nextBaris->nama_baris_ind ?? '';
                                            if (strpos($nextNama, '||') === false) break;
                                            
                                            $pNext = explode('||', $nextNama);
                                            $nextCol1 = trim($pNext[0]);
                                            if ($nextCol1 === $curCol1) {
                                                $span++;
                                                $col1SkipSet[$j] = true;
                                                $j++;
                                            } else {
                                                break;
                                            }
                                        }
                                        if ($span > 1) {
                                            $col1RowspanMap[$i] = $span;
                                        }
                                        $i = $j;
                                    }
                                }

                                // Pre-hitung jumlah anak untuk mode 2 kolom (Induk Komoditi & Rincian Provinsi)
                                $childCountMap = [];
                                if ($isBarisDuaKolom) {
                                    $bCount = count($itemsBaris);
                                    for ($i = 0; $i < $bCount; $i++) {
                                        $lvl = (int)($itemsBaris[$i]->level_indentasi ?? 0);
                                        if ($lvl === 0) {
                                            $count = 0;
                                            for ($j = $i + 1; $j < $bCount; $j++) {
                                                $nextLvl = (int)($itemsBaris[$j]->level_indentasi ?? 0);
                                                if ($nextLvl > 0) {
                                                    $count++;
                                                } else {
                                                    break;
                                                }
                                            }
                                            if ($count > 0) {
                                                $childCountMap[$i] = $count;
                                            }
                                        }
                                    }
                                }
                                $currentParentInd = '';
                                $currentParentEn  = '';
                                $currentParentRowspan = 1;
                                $isFirstChildOfParent = false;

                                foreach ($itemsBaris as $bIdx => $baris):
                                    $tipe         = strtolower(trim($baris->tipe_baris ?? ''));
                                    $rawNamaInd   = trim($baris->nama_baris_ind);
                                    $rawNamaEn    = trim($baris->nama_baris_en ?: '');
                                    $lowerNamaInd = strtolower($rawNamaInd);

                                    // 1. Deteksi Baris Grand Total (tipe === 'total' atau nama PDRB, Jawa Tengah, Jumlah, dsb.)
                                    $isTotalRow = false;
                                    if ($tipe === 'total') {
                                        $isTotalRow = true;
                                    } elseif ($tipe === 'data' || $tipe === 'kategori' || $tipe === 'subtotal') {
                                        $isTotalRow = false;
                                    } else {
                                        $isTotalRow = (
                                            $lowerNamaInd === 'jumlah' || 
                                            $lowerNamaInd === 'total' || 
                                            strpos($lowerNamaInd, 'jawa tengah') !== false || 
                                            strpos($lowerNamaInd, 'produk domestik regional bruto') !== false || 
                                            $lowerNamaInd === 'pdrb' || 
                                            strpos($lowerNamaInd, 'gross regional domestic product') !== false || 
                                            $lowerNamaInd === 'grdp'
                                        )
                                        && strpos($lowerNamaInd, 'curah hujan') === false
                                        && strpos($lowerNamaInd, 'hari hujan') === false;
                                    }

                                    // 2. Deteksi Baris Subtotal (Sub-Jumlah)
                                    $isSubtotalRow = false;
                                    if ($tipe === 'subtotal') {
                                        $isSubtotalRow = true;
                                    } elseif (!$isTotalRow && ($tipe === 'data' || empty($tipe))) {
                                        if (
                                            strpos($lowerNamaInd, 'jumlah ') === 0 || 
                                            strpos($lowerNamaInd, 'total ') === 0 || 
                                            strpos($lowerNamaInd, 'subtotal') === 0 || 
                                            strpos($lowerNamaInd, 'sub total') === 0 || 
                                            strpos($lowerNamaInd, 'sub-total') === 0 || 
                                            strpos($lowerNamaInd, 'sub jumlah') === 0
                                        ) {
                                            if (strpos($lowerNamaInd, 'curah hujan') === false && strpos($lowerNamaInd, 'hari hujan') === false && !preg_match('/^jumlah\s+(curah|hari|penduduk|kasus|sekolah|guru|murid|jiwa|balita|kk|desa|kelurahan|sarana|penerima|armada|tindak|kejadian|korban|kelahiran|kematian|pasien|peserta|tenaga)/i', $lowerNamaInd)) {
                                                $isSubtotalRow = true;
                                            }
                                        }
                                    }

                                    // Penanganan Mode 2 Kolom Baris: Induk Level 0 yang memiliki rincian anak
                                    if ($isBarisDuaKolom && (int)($baris->level_indentasi ?? 0) === 0 && !empty($childCountMap[$bIdx])) {
                                        $currentParentInd = $rawNamaInd;
                                        $currentParentEn  = $rawNamaEn;
                                        $currentParentRowspan = $childCountMap[$bIdx];
                                        $isFirstChildOfParent = true;
                                        continue; // Induk Level 0 tidak dirender baris tersendiri, melainkan dimerge vertikal pada rincian anak di Kolom 1
                                    }

                                    // 3. Deteksi Baris Pembatas Kategori / Parent Header Level Atas
                                    $isCategoryDivider = !$isTotalRow && !$isSubtotalRow && $isParentBaris($bIdx, $itemsBaris);

                                    if ($isCategoryDivider):
                                        $categoryRowCounter = 0; // Reset penomoran per kategori
                                        $dividerText = $rawNamaInd;
                                        if (!empty($rawNamaEn) && strtolower($rawNamaEn) !== $lowerNamaInd) {
                                            $dividerText .= ' / ' . $rawNamaEn;
                                        } elseif ($lowerNamaInd === 'kabupaten') {
                                            $dividerText .= ' / Regency';
                                        } elseif ($lowerNamaInd === 'kota') {
                                            $dividerText .= ' / Municipality';
                                        }
                                ?>
                                    <!-- BANNER PEMBATAS KATEGORI WILAYAH (PARITY 100% GAMBAR 2) -->
                                    <?php $hasCategoryDivider = true; ?>
                                    <tr class="row-category" style="background: #f1f6fc; font-weight: bold; color: #0f2d59;">
                                        <td colspan="<?= $totalLeafCols + ($isRuntunWaktu ? 2 : (($hasNoCol ? 1 : 0) + (($isMultiBaris || $isBarisDuaKolom) ? 2 : 1) + ($isPeriodeBaris ? 1 : 0))); ?>" style="padding: 10px 15px; border: 1px solid #e2e8f0 !important; background-color: #f1f6fc !important; color: #0f2d59; font-weight: bold;">
                                            <?= esc($dividerText); ?>
                                        </td>
                                    </tr>
                                <?php
                                    // 4. Deteksi Baris Subtotal (Sub-Jumlah) seperti "Jumlah makanan / Total food"
                                    elseif ($isSubtotalRow):
                                        if ($isPeriodeBaris):
                                            foreach ($itemsPeriode as $pIdx => $p):
                                ?>
                                            <tr class="row-subtotal" style="background: #dbeafe; font-weight: bold; color: #1e3a8a;">
                                                <?php if ($pIdx === 0): ?>
                                                    <td colspan="<?= ($hasNoCol ? 1 : 0) + ($isMultiBaris ? 2 : 1); ?>" rowspan="<?= count($itemsPeriode); ?>" style="padding: 8px 14px; border: 1px solid #e2e8f0 !important; font-size: 12px; font-weight: bold; color: #1e3a8a !important; background: #dbeafe; vertical-align: middle;">
                                                        <?= esc($rawNamaInd); ?>
                                                        <?php if (!empty($rawNamaEn) && strtolower($rawNamaEn) !== $lowerNamaInd): ?>
                                                            <br><i style="font-weight: normal; color: #0f172a;"><?= esc($rawNamaEn); ?></i>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td style="text-align: center; border: 1px solid #e2e8f0 !important; font-size: 12px; font-weight: bold; color: #1e3a8a !important; background: #dbeafe; vertical-align: middle;">
                                                    <?= esc($p->nama_periode_ind); ?>
                                                </td>
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $p->id;
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid #e2e8f0 !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #1e3a8a !important; background: #dbeafe; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                            endforeach;
                                        else:
                                ?>
                                            <!-- BARIS SUBTOTAL STANDAR (TAMPILAN RESMI BPS: SOFT BLUE #dbeafe) -->
                                            <tr class="row-subtotal" style="background: #dbeafe; font-weight: bold; color: #1e3a8a;">
                                                <td colspan="<?= ($isRuntunWaktu || $isBarisDuaKolom || $isMultiBaris || $hasNoCol) ? 2 : 1; ?>" style="padding: 8px 14px; border: 1px solid #e2e8f0 !important; font-size: 12px; font-weight: bold; color: #1e3a8a !important; background: #dbeafe; vertical-align: middle;">
                                                    <?= esc($rawNamaInd); ?>
                                                    <?php if (!empty($rawNamaEn) && strtolower($rawNamaEn) !== $lowerNamaInd): ?>
                                                        <br><i style="font-weight: normal; color: #0f172a;"><?= esc($rawNamaEn); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . ($isRuntunWaktu ? $idPeriodeAktif : $colLeaf['periode_id']);
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid #e2e8f0 !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #1e3a8a !important; background: #dbeafe; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                        endif;
                                    // 5. Deteksi Baris Grand Total (JUMLAH / JAWA TENGAH / PRODUK DOMESTIK REGIONAL BRUTO) SESUAI GAMBAR 2 & 3
                                    elseif ($isTotalRow):
                                        $cfgTotal = $indikator->konfigurasi_total ?? 'default';
                                        if ($cfgTotal === 'none') {
                                            continue; // Lewati baris total jika dikonfigurasi Tanpa Total
                                        }

                                        if ($cfgTotal === 'jawa_tengah') {
                                            $totalLabel = 'Jawa Tengah<br><i>Central Java</i>';
                                        } elseif ($cfgTotal === 'jumlah') {
                                            $totalLabel = 'Jumlah<br><i>Total</i>';
                                        } elseif ($cfgTotal === 'kustom' && !empty($indikator->label_total_kustom)) {
                                            $totalLabel = esc($indikator->label_total_kustom);
                                        } elseif ($cfgTotal === 'runtun_waktu') {
                                            $totalLabel = 'Jumlah<br><i>Total</i>';
                                        } else {
                                            $totalLabel = esc($rawNamaInd);
                                            if (!empty($rawNamaEn) && strtolower($rawNamaEn) !== $lowerNamaInd && strtolower($rawNamaEn) !== 'jawa tengah') {
                                                $totalLabel .= '<br><i>' . esc($rawNamaEn) . '</i>';
                                            } elseif ($lowerNamaInd === 'jumlah') {
                                                $totalLabel .= '<br><i>Total</i>';
                                            } elseif ($lowerNamaInd === 'total') {
                                                $totalLabel .= '<br><i>Total</i>';
                                            }
                                        }

                                        if ($isRuntunWaktu):
                                            $hasRenderedViewTotal = true;
                                            foreach ($itemsPeriode as $pIdx => $p):
                                ?>
                                            <tr class="row-total" style="background: #154679; font-weight: bold; color: #ffffff;">
                                                <?php if ($pIdx === 0): ?>
                                                    <td rowspan="<?= count($itemsPeriode); ?>" style="padding: 10px 14px; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                        <?= $totalLabel; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td style="text-align: center; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                    <?= esc($p->nama_periode_ind); ?>
                                                </td>
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $p->id;
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                            endforeach;
                                        elseif ($isPeriodeBaris):
                                            foreach ($itemsPeriode as $pIdx => $p):
                                ?>
                                            <tr class="row-total" style="background: #154679; font-weight: bold; color: #ffffff;">
                                                <?php if ($pIdx === 0): ?>
                                                    <td colspan="<?= ($hasNoCol ? 1 : 0) + ($isMultiBaris ? 2 : 1); ?>" rowspan="<?= count($itemsPeriode); ?>" style="padding: 10px 14px; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #154679 !important; vertical-align: middle;">
                                                        <?= $totalLabel; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td style="text-align: center; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                    <?= esc($p->nama_periode_ind); ?>
                                                </td>
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $p->id;
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                            endforeach;
                                        else:
                                ?>
                                            <!-- BARIS GRAND TOTAL STANDAR PERSIS GAMBAR 2 & GAMBAR 3 -->
                                            <tr class="row-total" style="background: #154679; font-weight: bold; color: #ffffff;">
                                                <td colspan="<?= ($hasNoCol ? 1 : 0) + (($isMultiBaris || $isBarisDuaKolom || $isRuntunWaktu) ? 2 : 1); ?>" style="padding: 10px 14px; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                    <?= $totalLabel; ?>
                                                </td>
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . ($isRuntunWaktu ? $idPeriodeAktif : $colLeaf['periode_id']);
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                        endif;
                                    // 6. Baris Data Biasa
                                    else:
                                        $dataRowCounter++;
                                        $categoryRowCounter = ($categoryRowCounter ?? 0) + 1;
                                        if (!empty($hasCategoryDivider)) {
                                            $rowClass = ($categoryRowCounter % 2 === 1) ? 'row-even' : 'row-odd';
                                        } else {
                                            $rowClass = ($dataRowCounter % 2 === 1) ? 'row-odd' : 'row-even';
                                        }

                                        $cleanNama   = $rawNamaInd;
                                        $cleanNamaEn = $rawNamaEn;
                                        $noText      = $dataRowCounter . '.';
                                        $customIndent = null;
                                        $fontWeightStyle = 'font-weight: bold;';

                                        if ($hasNoCol) {
                                            // Mode Tampilkan Kolom No:
                                            if (preg_match('/^\s*(\d+|[a-zA-Z]|[ivxLCDM]+)[\.\)]\s*/i', $rawNamaInd, $matches)) {
                                                $noText = trim($matches[0]);
                                                $cleanNama = trim(preg_replace('/^\s*(\d+|[a-zA-Z]|[ivxLCDM]+)[\.\)]\s*/i', '', $rawNamaInd));
                                                if (!empty($rawNamaEn)) {
                                                    $cleanNamaEn = trim(preg_replace('/^\s*(\d+|[a-zA-Z]|[ivxLCDM]+)[\.\)]\s*/i', '', $rawNamaEn));
                                                }
                                            }
                                        } else {
                                            // Mode Tanpa Kolom No:
                                            $cleanNama   = $rawNamaInd;
                                            $cleanNamaEn = $rawNamaEn;

                                            // Indentasi Visual Hierarkis BPS:
                                            if (preg_match('/^\s*([A-Z])[\.\)]\s*/', $rawNamaInd)) {
                                                $customIndent = 12;
                                                $fontWeightStyle = 'font-weight: bold;';
                                            } elseif (preg_match('/^\s*(\d+)[\.\)]\s*/', $rawNamaInd)) {
                                                $customIndent = 26;
                                                $fontWeightStyle = 'font-weight: 600;';
                                            } elseif (preg_match('/^\s*([a-z])[\.\)]\s*/', $rawNamaInd)) {
                                                $customIndent = 40;
                                                $fontWeightStyle = 'font-weight: normal;';
                                            }
                                        }

                                        $calcIndent = ($customIndent !== null) ? $customIndent : (10 + ((int)($baris->level_indentasi ?? 0) * 16));

                                        $hasDoubleCell = ($isMultiBaris && strpos($cleanNama, '||') !== false);
                                        if ($hasDoubleCell) {
                                            $bParts = explode('||', $cleanNama);
                                            $bCol1 = trim($bParts[0]);
                                            $bCol2 = trim($bParts[1] ?? '');

                                            $bPartsEn = explode('||', $cleanNamaEn);
                                            $bCol1En = trim($bPartsEn[0] ?? '');
                                            $bCol2En = trim($bPartsEn[1] ?? '');
                                        } else {
                                            $bCol1 = $cleanNama;
                                            $bCol1En = $cleanNamaEn;
                                            $bCol2 = '';
                                            $bCol2En = '';
                                        }

                                        if ($isRuntunWaktu):
                                ?>
                                            <tr class="<?= $rowClass; ?>">
                                                <!-- Kolom Nama Wilayah / Baris (Colspan 2) -->
                                                <td colspan="2" style="padding-left: <?= $calcIndent; ?>px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle;">
                                                    <?= esc($cleanNama); ?>
                                                    <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                        / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($cleanNamaEn); ?></i>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Sel Data Matriks (Kolom Angka Rata Kanan Persis View Portal Tabel) -->
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $idPeriodeAktif;
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid #ffffff; font-size: 12px; padding: 6px 12px; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                        elseif ($isPeriodeBaris):
                                            $blockBg = 'background-color: #ffffff !important; color: #0f172a !important;';
                                            foreach ($itemsPeriode as $pIdx => $p):
                                                $pRowClass = ($pIdx % 2 === 0) ? 'row-odd' : 'row-even';
                                ?>
                                            <tr class="<?= $pRowClass; ?>">
                                                <?php if ($pIdx === 0): ?>
                                                    <!-- Kolom No. -->
                                                    <?php if ($hasNoCol): ?>
                                                        <td rowspan="<?= count($itemsPeriode); ?>" style="text-align: center; width: 45px; border: 1px solid #ffffff !important; font-size: 12px; vertical-align: middle; <?= $blockBg; ?>">
                                                            <?= esc($noText); ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php if ($hasDoubleCell): ?>
                                                        <!-- Kolom 1 (Jenis Usaha / Komoditi) -->
                                                        <td rowspan="<?= count($itemsPeriode); ?>" style="padding-left: <?= $calcIndent; ?>px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; vertical-align: middle; <?= $blockBg; ?>">
                                                            <?= esc($bCol1); ?>
                                                            <?php if (!empty($bCol1En) && strtolower($bCol1En) !== strtolower($bCol1)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($bCol1En); ?></i>
                                                            <?php endif; ?>
                                                        </td>

                                                        <!-- Kolom 2 (Satuan / Provinsi) -->
                                                        <td rowspan="<?= count($itemsPeriode); ?>" style="text-align: center; border: 1px solid #ffffff !important; font-size: 12px; font-weight: 600; color: #475569; vertical-align: middle; <?= $blockBg; ?>">
                                                            <?= esc($bCol2); ?>
                                                            <?php if (!empty($bCol2En) && strtolower($bCol2En) !== strtolower($bCol2)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($bCol2En); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php elseif ($isMultiBaris): ?>
                                                        <!-- Baris Kategori Induk tanpa Kolom 2: colspan 2 -->
                                                        <td colspan="2" rowspan="<?= count($itemsPeriode); ?>" style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; vertical-align: middle; background-color: #fafafa; <?= $blockBg; ?>">
                                                            <?= esc($cleanNama); ?>
                                                            <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($cleanNamaEn); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php else: ?>
                                                        <!-- Kolom Nama Wilayah / Baris -->
                                                        <td rowspan="<?= count($itemsPeriode); ?>" style="padding-left: <?= $calcIndent; ?>px; border: 1px solid #ffffff !important; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle; <?= $blockBg; ?>">
                                                            <?= esc($cleanNama); ?>
                                                            <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($cleanNamaEn); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <!-- Kolom Periode Waktu -->
                                                <td style="text-align: center; width: 85px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: 600; vertical-align: middle;">
                                                    <?= esc($p->nama_periode_ind); ?>
                                                </td>

                                                <!-- Sel Data Matriks -->
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $p->id;
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; padding: 6px 12px; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                            endforeach;
                                        else:
                                ?>
                                            <tr class="<?= $rowClass; ?>">
                                                <?php if ($isMultiBaris): ?>
                                                    <!-- Kolom No. -->
                                                    <?php if ($hasNoCol): ?>
                                                        <?php if (empty($col1SkipSet[$bIdx])): ?>
                                                            <?php $noSpanAttr = isset($col1RowspanMap[$bIdx]) ? ' rowspan="' . $col1RowspanMap[$bIdx] . '" style="text-align: center; width: 45px; border: 1px solid #ffffff; font-size: 12px; vertical-align: middle;"' : ' style="text-align: center; width: 45px; border: 1px solid #ffffff; font-size: 12px;"'; ?>
                                                            <td<?= $noSpanAttr; ?>>
                                                                <?= esc($noText); ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    <?php endif; ?>

                                                    <?php if ($hasDoubleCell): ?>
                                                        <?php if (empty($col1SkipSet[$bIdx])): ?>
                                                            <?php $spanAttr = isset($col1RowspanMap[$bIdx]) ? ' rowspan="' . $col1RowspanMap[$bIdx] . '" style="padding-left: ' . (10 + ((int)($baris->level_indentasi ?? 0) * 16)) . 'px; border: 1px solid #ffffff; font-size: 12px; font-weight: bold; vertical-align: middle;"' : ' style="padding-left: ' . (10 + ((int)($baris->level_indentasi ?? 0) * 16)) . 'px; border: 1px solid #ffffff; font-size: 12px; font-weight: bold;"'; ?>
                                                            <!-- Kolom 1 (Jenis Usaha / Komoditi / Tahun) -->
                                                            <td<?= $spanAttr; ?>>
                                                                <?= esc($bCol1); ?>
                                                                <?php if (!empty($bCol1En) && strtolower($bCol1En) !== strtolower($bCol1)): ?>
                                                                    / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($bCol1En); ?></i>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>

                                                        <!-- Kolom 2 (Satuan / Provinsi / Bulan) - DIKUNCI MATI (TIDAK PERNAH DI-MERGE) -->
                                                        <td style="text-align: center; border: 1px solid #ffffff; font-size: 12px; font-weight: 600; color: #475569; vertical-align: middle;">
                                                            <?= esc($bCol2); ?>
                                                            <?php if (!empty($bCol2En) && strtolower($bCol2En) !== strtolower($bCol2)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($bCol2En); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php else: ?>
                                                        <!-- Baris Kategori Induk tanpa Kolom 2: colspan 2 -->
                                                        <td colspan="2" style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff; font-size: 12px; font-weight: bold; background-color: #fafafa; vertical-align: middle;">
                                                            <?= esc($cleanNama); ?>
                                                            <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($cleanNamaEn); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php elseif ($isBarisDuaKolom): ?>
                                                    <?php if ((int)($baris->level_indentasi ?? 0) > 0): ?>
                                                        <?php if ($isFirstChildOfParent): ?>
                                                            <!-- Kolom 1 (No.) -->
                                                            <?php if ($hasNoCol): ?>
                                                                <td rowspan="<?= $currentParentRowspan; ?>" style="border: 1px solid #e2e8f0 !important; text-align: center; vertical-align: middle; width: 45px; font-weight: normal; background-color: #ffffff !important; color: #0f172a;"><?= esc($noText); ?></td>
                                                            <?php endif; ?>

                                                            <!-- Kolom 2 (Induk): Rowspan sebanyak rincian anak -->
                                                            <td rowspan="<?= $currentParentRowspan; ?>" style="padding: 8px 12px; border: 1px solid #e2e8f0 !important; font-size: 12px; font-weight: bold; vertical-align: middle; background-color: #ffffff !important; color: #0f172a;">
                                                                <?= esc($currentParentInd); ?>
                                                                <?php if (!empty($currentParentEn) && strtolower($currentParentEn) !== strtolower($currentParentInd)): ?>
                                                                    / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($currentParentEn); ?></i>
                                                                <?php endif; ?>
                                                            </td>
                                                            <?php $isFirstChildOfParent = false; ?>
                                                        <?php endif; ?>

                                                        <!-- Kolom 3 (Rincian Anak, misal: Provinsi) -->
                                                        <td style="padding: 6px 12px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle;">
                                                            <?= esc($cleanNama); ?>
                                                            <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($cleanNamaEn); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php else: ?>
                                                        <!-- Kolom 1 (No.) -->
                                                        <?php if ($hasNoCol): ?>
                                                            <td style="border: 1px solid #ffffff; text-align: center; vertical-align: middle; width: 45px; font-weight: normal;"><?= esc($noText); ?></td>
                                                        <?php endif; ?>

                                                        <!-- Level 0 Mandiri / Total tanpa anak: Colspan 2 -->
                                                        <td colspan="2" style="padding: 8px 12px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle;">
                                                            <?= esc($cleanNama); ?>
                                                            <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                                / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($cleanNamaEn); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <!-- Kolom 1 (No.) jika aktif -->
                                                    <?php if ($hasNoCol): ?>
                                                        <td style="border: 1px solid #ffffff; text-align: center; vertical-align: middle; width: 45px; font-weight: normal;"><?= esc($noText); ?></td>
                                                    <?php endif; ?>

                                                    <!-- Kolom 2 (Nama Baris) Teks Rata Kiri Persis View Tabel API -->
                                                    <td style="padding-left: <?= $calcIndent; ?>px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle;">
                                                        <?= esc($cleanNama); ?>
                                                        <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                            / <i style="font-weight: normal; font-size: 0.9em;"><?= esc($cleanNamaEn); ?></i>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>

                                                <!-- Sel Data Matriks (Kolom Angka Rata Kanan Persis View Portal Tabel) -->
                                                <?php foreach ($leafColumns as $colLeaf): ?>
                                                    <?php
                                                    $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $colLeaf['periode_id'];
                                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                                    ?>
                                                    <td class="col-val" style="text-align: right; border: 1px solid #ffffff; font-size: 12px; padding: 6px 12px; vertical-align: middle;">
                                                        <?= $formatCellVal($cellVal, $desimal); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                <?php
                                        endif;
                                    endif;
                                endforeach;

                                // Fallback total untuk mode Runtun Waktu jika belum dirender di dalam loop
                                if ($isRuntunWaktu && !$hasRenderedViewTotal && !empty($itemsPeriode)):
                                    foreach ($itemsPeriode as $pIdx => $p):
                                ?>
                                    <tr class="row-total" style="background: #154679; font-weight: bold; color: #ffffff;">
                                        <?php if ($pIdx === 0): ?>
                                            <td rowspan="<?= count($itemsPeriode); ?>" style="padding: 10px 14px; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                Jumlah / Total
                                            </td>
                                        <?php endif; ?>
                                        <td style="text-align: center; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                            <?= esc($p->nama_periode_ind); ?>
                                        </td>
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                            $cellKey = '0_' . $colLeaf['item_id'] . '_' . $p->id;
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid rgba(255,255,255,0.25) !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #154679; vertical-align: middle;">
                                                <?= $formatCellVal($cellVal, $desimal); ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php
                                    endforeach;
                                endif;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>

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
            if (trClasses.includes('row-category') || tdClasses.includes('row-category')) {
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
            const rowFullText = trEl ? (trEl.textContent || '').trim().toLowerCase() : '';
            const isLastRow = domBodyRows && data.row.index === (domBodyRows.length - 1);
            if ((isLastRow && (rowFullText.includes('jumlah') || rowFullText.includes('total') || rowFullText.includes('jawa tengah') || rowFullText.includes('pdrb'))) 
                || rowFullText.startsWith('jumlah') 
                || rowFullText.startsWith('total') 
                || rowFullText.startsWith('jawa tengah')) {
                return 'total';
            }
            if (trEl && trEl.children && trEl.children.length === 1 && (rowFullText.includes('kabupaten') || rowFullText.includes('kota'))) {
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
</script>
