<?php
/**
 * VIEW: VIEWER TABEL DINAMIS PUBLIKASI RESMI BPS (v_tabel_dinamis.php)
 * Didesain 100% selaras dengan tampilan View API (v_portal_tabel.php):
 * 1. Toolbar Modern: Tombol KEMBALI, filter TAHUN, KONFIGURASI, dan tombol EXPORT EXCEL oranye (#FF6D1F).
 * 2. Header Table Klasik BPS: Kotak nomor tabel terpisah garis vertikal dengan judul dwibahasa (Ind/En).
 * 3. Matriks Oranye Resmi BPS: Header tabel oranye cerah (#FF6D1F) dengan teks putih, dan baris nomor kolom peach (#f9a066).
 * 4. Pemisah Wilayah: Banner pembatas Kabupaten / Regency dan Kota / Municipality berlatar #fff1e6 dengan border oranye.
 * 5. Baris Selang-Seling: Tampilan lembut bergantian (#fff dan #fff4eb), nilai kosong standar strip ('–').
 * 6. Catatan & Sumber Standar DDA BPS.
 */

// Siapkan Back URL
$fallback_url = base_url('admin/dda');
$back_url = $fallback_url;
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (strpos($referer, 'view_tabel_dinamis') === false && strpos($referer, base_url()) !== false) {
        $back_url = $referer;
    }
}

// Ekstraksi Nomor Tabel & Judul Bersih
$no_tabel = $tabel->no_tabel ?? '';
if (empty($no_tabel) && preg_match('/^(\d+(?:\.\d+)+(?:[a-zA-Z])?)\s+(.*)$/u', $tabel->judul_ind, $m_num)) {
    $no_tabel = $m_num[1];
}
if (empty($no_tabel)) {
    $no_tabel = '1.1.1';
}

$cleanJudulInd = format_judul_tabel($tabel->judul_ind, $tabel->periode_id ?? $tabel->periode ?? '');
if (!empty($no_tabel)) {
    $cleanJudulInd = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/u', '', $cleanJudulInd);
}

$cleanJudulEn = format_judul_tabel($tabel->judul_en ?: $tabel->judul_ind, $tabel->periode_en ?? $tabel->periode_id ?? $tabel->periode ?? '');
if (!empty($no_tabel)) {
    $cleanJudulEn = preg_replace('/^' . preg_quote($no_tabel, '/') . '\s*/u', '', $cleanJudulEn);
}

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
        // Cerdas deteksi isi kolom 2 dari itemsBaris jika cleanTitleInd tidak mengandung ||
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
        } elseif (strpos($sampleJoined, 'jakarta') !== false || strpos($sampleJoined, 'jawa') !== false || strpos($sampleJoined, 'bali') !== false || strpos($sampleJoined, 'sumatera') !== false || strpos($sampleJoined, 'yogyakarta') !== false) {
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
$dimCount = (!empty($itemsBaris) ? 1 : 0) + (!empty($structureKolom) ? 1 : 0) + (!empty($itemsPeriode) ? 1 : 0);
$isConfigured = ($indikator && $dimCount >= 2);
?>

<style>
    /* Parity 100% dengan v_portal_tabel.php (View API) */
    .dda-body {
        background: #fff;
        padding: 20px;
        font-family: 'Arial', sans-serif;
        color: #000;
        line-height: 1.2;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        display: block;
        box-sizing: border-box;
    }

    /* Force parent containers in layout to stay within screen width */
    #content, .main-content, .content-body {
        overflow-x: hidden !important;
        max-width: 100% !important;
    }

    /* Action Toolbar Buttons */
    .btn-rounded-modern {
        border-radius: 50px;
        padding: 6px 18px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-decoration: none;
        cursor: pointer;
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
        color: #fff !important;
        text-decoration: none;
    }

    .export-btn-modern:hover {
        background: #e05e15;
        color: #fff !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(255, 109, 31, 0.25);
        text-decoration: none;
    }

    .upload-btn-modern {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
        color: #fff !important;
        text-decoration: none;
        border: none !important;
    }

    .upload-btn-modern:hover {
        background: linear-gradient(135deg, #047857 0%, #059669 100%) !important;
        color: #fff !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(16, 185, 129, 0.3);
        text-decoration: none;
    }

    /* Header Table (Title Box Standar Publikasi BPS) */
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
        color: #000;
        line-height: 1.3;
    }

    .title-en {
        font-style: italic;
        font-size: 13px;
        display: block;
        color: #333;
        line-height: 1.3;
    }

    /* Kontainer Scrollable Mulus */
    .table-responsive-dda {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 25px;
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.02);
    }

    .table-responsive-dda::-webkit-scrollbar {
        height: 10px;
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

    .table-responsive-dda table {
        min-width: 100%;
        width: max-content !important;
    }

    /* Tabel Data Utama Oranye BPS */
    .main-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 1px solid #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .main-table thead th {
        background: #FF6D1F;
        color: #fff;
        border: 1px solid #fff !important;
        padding: 8px 6px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        line-height: 1.25;
    }

    .main-table thead th i {
        display: block;
        font-weight: normal;
        font-size: 0.85em;
        margin-top: 2px;
        color: rgba(255, 255, 255, 0.9);
    }

    .main-table thead tr.num-row th {
        font-weight: bold !important;
        padding: 4px 4px;
        background: #f9a55f !important;
        color: #000 !important;
        font-size: 11px;
        border: 1px solid #fff !important;
        text-align: center;
    }

    .main-table tbody td {
        border: 1px solid #ffffff !important;
        padding: 6px 8px;
        font-size: 12px;
        vertical-align: middle;
        color: #000000;
    }

    .main-table tbody tr.row-odd,
    .main-table tbody tr.row-odd td {
        background-color: #fde5ce !important;
    }

    .main-table tbody tr.row-even,
    .main-table tbody tr.row-even td {
        background-color: #fef3e6 !important;
    }

    .main-table tbody td.col-val {
        text-align: right !important;
        padding-right: 12px !important;
        padding-left: 8px !important;
    }

    /* Baris Subtotal / Sub-Jumlah (Sama Seperti Angka di Bawah Kolom Header Sesuai Gambar 1 & 2) */
    .main-table tbody tr.row-subtotal,
    .main-table tbody tr.row-subtotal td {
        background-color: #f9a55f !important;
        color: #000000 !important;
        font-weight: bold !important;
        border: 1px solid #ffffff !important;
    }

    .main-table tbody tr.row-subtotal td.col-val {
        color: #000000 !important;
        text-align: right !important;
        font-weight: bold !important;
    }

    .main-table tbody tr.row-subtotal td i {
        color: #000000 !important;
        font-weight: normal;
    }

    /* Baris Grand Total (Jumlah / Total / Jawa Tengah) Menyerupai Header Sesuai Gambar 2 */
    .main-table tbody tr.row-total,
    .main-table tbody tr.row-total td {
        background-color: #FF6D1F !important;
        color: #ffffff !important;
        font-weight: bold !important;
        border: 1px solid #ffffff !important;
    }

    .main-table tbody tr.row-total td.col-val {
        color: #ffffff !important;
        text-align: right !important;
    }

    .main-table tbody tr.row-total td i {
        color: rgba(255, 255, 255, 0.95) !important;
    }

    /* Hover effect dinonaktifkan agar selaras dengan view portal tabel */

    .main-table tfoot td {
        border-top: 2px solid #000;
        font-size: 12px;
        font-weight: bold;
    }

    /* Responsiveness */
    @media (max-width: 576px) {
        .btn-rounded-modern {
            width: 100%;
            justify-content: center;
            padding: 10px 16px;
        }
    }

    /* Print Media */
    @media print {
        body {
            background: #fff !important;
        }
        .top-navbar, .sidebar, #sidebar, .breadcrumb-container, .footer, .no-print {
            display: none !important;
        }
        .main-content {
            padding: 0 !important;
            margin: 0 !important;
        }
        .dda-body {
            padding: 0 !important;
        }
        .table-responsive-dda {
            overflow: visible !important;
            border: none !important;
            box-shadow: none !important;
        }
        .table-responsive-dda table {
            width: 100% !important;
        }
    }
</style>

<div class="dda-body">
    <!-- ===== TOOLBAR ATAS: KEMBALI, KONFIGURASI, UNDUH TEMPLATE ===== -->
    <div class="row g-3 mb-4 align-items-center no-print">
        <div class="col-12 col-md-auto">
            <a href="<?= esc($back_url); ?>" class="btn-rounded-modern btn-back-modern w-100 w-md-auto">
                <i class="bi bi-arrow-left"></i> KEMBALI
            </a>
        </div>

        <div class="col-12 col-md d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
            <!-- Tombol Konfigurasi (Khusus Admin BPS) -->
            <?php if (!empty($is_admin_bps)): ?>
                <a href="<?= base_url('admin/generator_tabel/' . $tabel->id_tahun_tabel); ?>" class="btn-rounded-modern btn-back-modern shadow-sm">
                    <i class="bi bi-gear-fill"></i> KONFIGURASI TABEL DINAMIS
                </a>
            <?php endif; ?>

            <!-- Tombol Upload Excel (Admin BPS & Walidata) -->
            <button type="button" class="btn-rounded-modern upload-btn-modern shadow-success" data-bs-toggle="modal" data-bs-target="#ModalUploadExcelDinamis">
                <i class="bi bi-cloud-arrow-up-fill"></i> <span class="d-none d-sm-inline">UPLOAD EXCEL</span><span class="d-inline d-sm-none">UPLOAD</span>
            </button>

            <!-- Tombol Unduh Template Excel Berstandar BPS -->
            <a href="<?= base_url('admin/download_template_tabel/' . $tabel->id_tahun_tabel); ?>" class="btn-rounded-modern export-btn-modern shadow-primary">
                <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-sm-inline">UNDUH TEMPLATE</span><span class="d-inline d-sm-none">UNDUH TEMPLATE</span>
            </a>
        </div>
    </div>

    <?php if (!$isConfigured): ?>
        <!-- ===== TAMPILAN JIKA DIMENSI BELUM DIRAKIT ===== -->
        <div class="text-center p-5 my-4 border rounded-3 bg-light">
            <div class="mb-3">
                <div class="d-inline-flex p-3 rounded-circle bg-white border text-secondary shadow-xs">
                    <i class="bi bi-grid-3x3-gap fs-1"></i>
                </div>
            </div>
            <h5 class="fw-bold text-dark mb-2">Dimensi Tabel Belum Dikonfigurasi</h5>
            <p class="text-secondary text-sm max-w-md mx-auto mb-4" style="max-width: 520px;">
                Tabel dinamis ini belum dirakit struktur dimensinya (Karakteristik Kolom, Judul Baris, dan Periode Waktu) oleh Admin BPS Provinsi Jawa Tengah.
            </p>
            <?php if (!empty($is_admin_bps)): ?>
                <a href="<?= base_url('admin/generator_tabel/' . $tabel->id_tahun_tabel); ?>" class="btn-rounded-modern export-btn-modern shadow-primary">
                    <i class="bi bi-gear-fill"></i> Rakit Dimensi di Konfigurasi
                </a>
            <?php else: ?>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill text-xs">
                    <i class="bi bi-info-circle me-1"></i> Menunggu konfigurasi dimensi oleh Admin BPS
                </span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- ===== TAMPILAN RESMI 100% PERSIS VIEW API (v_portal_tabel.php) ===== -->
        <div id="dda-container-all" style="padding: 20px;">
            <div class="table-wrapper">
            <!-- HEADER BAGIAN ATAS (KOTAK NOMOR DAN JUDUL BPS) -->
            <table class="header-table">
                <tr>
                    <td class="label-box">
                        <div class="label-box-content">
                            <div class="label-text">
                                <b>Tabel</b><br><i>Table</i>
                            </div>
                            <?php if (!empty($no_tabel)): ?>
                                <div class="label-number">
                                    <?= esc($no_tabel); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="title-box">
                        <span class="title-id">
                            <?= esc($cleanJudulInd); ?>
                        </span>
                        <span class="title-en">
                            <?= esc($cleanJudulEn); ?>
                        </span>
                    </td>
                </tr>
            </table>

            <!-- TABEL DATA UTAMA -->
            <div class="table-responsive-dda">
                <table class="main-table dda-table-item" id="table-dinamis-<?= $tabel->id_tahun_tabel; ?>">
                    <thead style="background: #FF6D1F; color: #fff;">
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
                        $rawFlatKolom   = $structureKolom['flat_items'] ?? [];

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
                        $format_nomor_baris = 'none';
                        $hasNoCol           = true; // Kolom No. selalu aktif 100% identik dengan view tabel API portal

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

                        // Tentukan jumlah baris header
                        $totalHeaderRows = 1;
                        if (!$hasBaris && $hasKolom && $hasPeriode) {
                            $totalHeaderRows = count($activeLevels);
                        } elseif ($hasKolom && $hasPeriode) {
                            $totalHeaderRows = ($isPeriodeBaris || $isRuntunWaktu) ? count($activeLevels) : (count($activeLevels) + 1);
                        } elseif ($hasKolom) {
                            $totalHeaderRows = count($activeLevels);
                        } elseif ($hasPeriode) {
                            $totalHeaderRows = 1;
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
                                        'periode_id' => $idPeriodeAktif, // Untuk data rincian unit wilayah tahun aktif
                                    ];
                                }
                            } elseif ($isPeriodeBaris) {
                                foreach ($flatKolom as $child) {
                                    $leafColumns[] = [
                                        'item_id'    => (int)$child->id,
                                        'periode_id' => null, // Periode ditentukan per baris di tbody
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
                            if (!empty($structureKolom['header_rows'][$lvl])) {
                                $html = '';
                                foreach ($structureKolom['header_rows'][$lvl] as $cell) {
                                    $colspan = (int)$cell['colspan'] * $multiplier;
                                    $rowspan = (int)$cell['rowspan'];
                                    $fontSize = (!empty($cell['is_leaf']) && $cell['rowspan'] === 1) ? 'font-size: 11px;' : '';
                                    $html .= '<th' 
                                        . ($colspan > 1 ? ' colspan="' . $colspan . '"' : '') 
                                        . ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '') 
                                        . ' style="border: 1px solid #fff; text-align: center; ' . $fontSize . '">';
                                    $html .= esc($cell['title_ind']);
                                    if (!empty($cell['title_en'])) {
                                        $html .= ' <i>' . esc($cell['title_en']) . '</i>';
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
                                        $html .= '<th style="border: 1px solid #fff; text-align: center;" colspan="' . $multiplier . '">';
                                        $html .= esc($item->item_ind);
                                        if (!empty($item->item_en)) {
                                            $html .= ' <i>' . esc($item->item_en) . '</i>';
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
                                            $html .= '<th colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center;">';
                                            $html .= esc($grp['title_ind']);
                                            if (!empty($grp['title_en'])) {
                                                $html .= ' <i>' . esc($grp['title_en']) . '</i>';
                                            }
                                            $html .= '</th>';
                                        } else {
                                            $colspan = 1 * $multiplier;
                                            $rowspan = $hasL2 ? 2 : 1;
                                            $html .= '<th rowspan="' . $rowspan . '" colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center;">';
                                            $html .= esc($grp['title_ind']);
                                            if (!empty($grp['title_en'])) {
                                                $html .= ' <i>' . esc($grp['title_en']) . '</i>';
                                            }
                                            $html .= '</th>';
                                        }
                                    }
                                } elseif ($lvl === 2) {
                                    foreach ($groups as $grp) {
                                        if ($grp['type'] === 'group') {
                                            foreach ($grp['children'] as $child) {
                                                $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; font-size: 11px;">';
                                                $html .= esc($child->item_ind);
                                                if (!empty($child->item_en)) {
                                                    $html .= ' <i>' . esc($child->item_en) . '</i>';
                                                }
                                                $html .= '</th>';
                                            }
                                        } else {
                                            if (!$hasL1) {
                                                $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; font-size: 11px;">';
                                                $html .= esc($grp['title_ind']);
                                                if (!empty($grp['title_en'])) {
                                                    $html .= ' <i>' . esc($grp['title_en']) . '</i>';
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
                                            $html .= '<th colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center;">';
                                            $html .= esc($grp['title_ind']);
                                            if (!empty($grp['title_en'])) {
                                                $html .= ' <i>' . esc($grp['title_en']) . '</i>';
                                            }
                                            $html .= '</th>';
                                        } else {
                                            $colspan = 1 * $multiplier;
                                            $rowspan = count($activeLevels);
                                            $html .= '<th rowspan="' . $rowspan . '" colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center;">';
                                            $html .= esc($grp['title_ind']);
                                            if (!empty($grp['title_en'])) {
                                                $html .= ' <i>' . esc($grp['title_en']) . '</i>';
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
                                                    $html .= '<th colspan="' . $colspan . '" rowspan="' . $rowspan . '" style="border: 1px solid #fff; text-align: center;">';
                                                    $html .= esc($sub['title_ind']);
                                                    if (!empty($sub['title_en'])) {
                                                        $html .= ' <i>' . esc($sub['title_en']) . '</i>';
                                                    }
                                                    $html .= '</th>';
                                                }
                                            } else {
                                                if ($hasL3) {
                                                    foreach ($grp['children'] as $child) {
                                                        $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; font-size: 11px;">';
                                                        $html .= esc($child->item_ind);
                                                        if (!empty($child->item_en)) {
                                                            $html .= ' <i>' . esc($child->item_en) . '</i>';
                                                        }
                                                        $html .= '</th>';
                                                    }
                                                } else {
                                                    $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center;">';
                                                    $html .= esc($grp['title_ind']);
                                                    if (!empty($grp['title_en'])) {
                                                        $html .= ' <i>' . esc($grp['title_en']) . '</i>';
                                                    }
                                                    $html .= '</th>';
                                                }
                                            }
                                        } else {
                                            if (!$hasL1) {
                                                $colspan = 1 * $multiplier;
                                                $rowspan = $hasL3 ? 2 : 1;
                                                $html .= '<th rowspan="' . $rowspan . '" colspan="' . $colspan . '" style="border: 1px solid #fff; text-align: center;">';
                                                $html .= esc($grp['title_ind']);
                                                if (!empty($grp['title_en'])) {
                                                    $html .= ' <i>' . esc($grp['title_en']) . '</i>';
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
                                                        $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; font-size: 11px;">';
                                                        $html .= esc($child->item_ind);
                                                        if (!empty($child->item_en)) {
                                                            $html .= ' <i>' . esc($child->item_en) . '</i>';
                                                        }
                                                        $html .= '</th>';
                                                    }
                                                }
                                            } else {
                                                if (!$hasL2 && !$hasL1) {
                                                    foreach ($grp['children'] as $child) {
                                                        $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; font-size: 11px;">';
                                                        $html .= esc($child->item_ind);
                                                        if (!empty($child->item_en)) {
                                                            $html .= ' <i>' . esc($child->item_en) . '</i>';
                                                        }
                                                        $html .= '</th>';
                                                    }
                                                }
                                            }
                                        } else {
                                            if (!$hasL1 && !$hasL2) {
                                                $html .= '<th colspan="' . $multiplier . '" style="border: 1px solid #fff; text-align: center; font-size: 11px;">';
                                                $html .= esc($grp['title_ind']);
                                                if (!empty($grp['title_en'])) {
                                                    $html .= ' <i>' . esc($grp['title_en']) . '</i>';
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
                                <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 140px; text-align: center; vertical-align: middle;">
                                    <?= esc($titlePeriodeInd); ?>
                                    <?php if (!empty($titlePeriodeEn) && $titlePeriodeEn !== $titlePeriodeInd): ?>
                                        <i><?= esc($titlePeriodeEn); ?></i>
                                    <?php endif; ?>
                                </th>
                                <?= $getLevelCellsPhp($activeLevels[0], 1); ?>
                            <?php else: ?>
                                <?php if ($hasNoCol && !$isRuntunWaktu): ?>
                                    <th rowspan="<?= $totalHeaderRows + 1; ?>" style="border: 1px solid #fff; width: 45px; text-align: center;">No.</th>
                                <?php endif; ?>

                                <?php if ($isMultiBaris && strpos($cleanTitleInd, '||') === false): ?>
                                    <!-- Header tunggal di-merge horizontal colspan 2 (persis tabel BPS asli!) -->
                                    <th rowspan="<?= $totalHeaderRows; ?>" colspan="2" style="border: 1px solid #fff; min-width: 270px; text-align: center; vertical-align: middle;">
                                        <?= esc($titleCol1Ind); ?>
                                        <?php if (!empty($titleCol1En) && strtolower($titleCol1En) !== strtolower($titleCol1Ind)): ?>
                                            <i><?= esc($titleCol1En); ?></i>
                                        <?php endif; ?>
                                    </th>
                                <?php elseif ($isMultiBaris): ?>
                                    <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 180px; text-align: center;">
                                        <?= esc($titleCol1Ind); ?>
                                        <?php if (!empty($titleCol1En) && strtolower($titleCol1En) !== strtolower($titleCol1Ind)): ?>
                                            <i><?= esc($titleCol1En); ?></i>
                                        <?php endif; ?>
                                    </th>
                                    <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 90px; text-align: center;">
                                        <?= esc($titleCol2Ind); ?>
                                        <?php if (!empty($titleCol2En) && strtolower($titleCol2En) !== strtolower($titleCol2Ind)): ?>
                                            <i><?= esc($titleCol2En); ?></i>
                                        <?php endif; ?>
                                    </th>
                                <?php elseif ($isRuntunWaktu): ?>
                                    <th rowspan="<?= $totalHeaderRows; ?>" colspan="2" style="border: 1px solid #fff; min-width: 220px; text-align: center;">
                                        <?= esc($cleanTitleInd); ?>
                                        <?php if (!empty($titleBarisEn)): ?>
                                            <i><?= esc($titleBarisEn); ?></i>
                                        <?php endif; ?>
                                    </th>
                                <?php elseif ($posisi_hirarki === 'periode_baris'): ?>
                                    <!-- MODE GABUNG HEADER: Kolom 1 & 2 Dimerge Horizontal (1) -->
                                    <th rowspan="<?= $totalHeaderRows; ?>" colspan="2" style="border: 1px solid #fff; min-width: 220px; text-align: center;">
                                        <?= esc($cleanTitleInd); ?>
                                        <?php if (!empty($titleBarisEn)): ?>
                                            <i><?= esc($titleBarisEn); ?></i>
                                        <?php endif; ?>
                                    </th>
                                <?php else: ?>
                                    <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 220px; text-align: center;">
                                        <?= esc($cleanTitleInd); ?>
                                        <?php if (!empty($titleBarisEn)): ?>
                                            <i><?= esc($titleBarisEn); ?></i>
                                        <?php endif; ?>
                                    </th>
                                    <?php if ($hasKolom && $hasPeriode && ($posisi_hirarki === 'periode_baris' || $posisi_hirarki === 'periode_baris_pisah')): ?>
                                        <!-- MODE PISAH HEADER: Kolom 2 Memiliki Header Tersendiri -->
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 90px; text-align: center;">
                                            <?= esc($titlePeriodeInd); ?>
                                            <?php if (!empty($titlePeriodeEn) && $titlePeriodeEn !== $titlePeriodeInd): ?>
                                                <i><?= esc($titlePeriodeEn); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php elseif ($isBarisDuaKolom): ?>
                                        <!-- MODE 2 KOLOM BARIS BERTINGKAT: Kolom 2 Rincian (misal: Provinsi) -->
                                        <th rowspan="<?= $totalHeaderRows; ?>" style="border: 1px solid #fff; min-width: 130px; text-align: center;">
                                            <?= esc($cleanTitleSubInd); ?>
                                            <?php if (!empty($titleSubBarisEn) && strtolower($titleSubBarisEn) !== strtolower($cleanTitleSubInd)): ?>
                                                <i><?= esc($titleSubBarisEn); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!$hasKolom && $hasPeriode): ?>
                                    <!-- KASUS 1: Hanya Periode Waktu -->
                                    <?php foreach ($itemsPeriode as $p): ?>
                                        <th style="border: 1px solid #fff; text-align: center;">
                                            <?= esc($p->nama_periode_ind); ?>
                                            <?php if (!empty($p->nama_periode_en) && $p->nama_periode_en !== $p->nama_periode_ind): ?>
                                                <i><?= esc($p->nama_periode_en); ?></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>

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
                                            <th colspan="<?= count($flatKolom); ?>" style="border: 1px solid #fff; text-align: center;">
                                                <?= esc($p->nama_periode_ind); ?>
                                                <?php if (!empty($p->nama_periode_en) && $p->nama_periode_en !== $p->nama_periode_ind): ?>
                                                    <i><?= esc($p->nama_periode_en); ?></i>
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

                            <!-- Baris Header Terbawah: Periode Waktu diulang di bawah masing-masing kolom daun -->
                            <tr>
                                <?php for ($c = 0; $c < count($flatKolom); $c++): ?>
                                    <?php foreach ($itemsPeriode as $p): ?>
                                        <th style="border: 1px solid #fff; text-align: center; padding: 4px 6px; font-size: 11px;">
                                            <?= esc($p->nama_periode_ind); ?>
                                        </th>
                                    <?php endforeach; ?>
                                <?php endfor; ?>
                            </tr>
                        <?php endif; ?>

                        <!-- BARIS PENOMORAN KOLOM RESMI BPS: (1), (2), (3) ... -->
                        <tr class="num-row" style="background: #f9a066; color: #000; font-size: 11px; font-weight: bold;">
                            <?php 
                            $colNum = 1;
                            if (!$hasBaris && $hasKolom && $hasPeriode): 
                            ?>
                                <th style="border: 1px solid #fff; text-align: center; font-weight: bold;">(<?= $colNum++; ?>)</th>
                            <?php else: ?>
                                <?php if ($hasNoCol && !$isRuntunWaktu): ?>
                                    <th style="border: 1px solid #fff; text-align: center; font-weight: bold;">(<?= $colNum++; ?>)</th>
                                <?php endif; ?>

                                <?php if ($isRuntunWaktu || ($posisi_hirarki === 'periode_baris' && !$isMultiBaris)): ?>
                                    <th colspan="2" style="border: 1px solid #fff; text-align: center; font-weight: bold;">(<?= $colNum++; ?>)</th>
                                <?php else: ?>
                                    <th style="border: 1px solid #fff; text-align: center; font-weight: bold;">(<?= $colNum++; ?>)</th>
                                    <?php if ($isMultiBaris || $isBarisDuaKolom): ?>
                                        <th style="border: 1px solid #fff; text-align: center; font-weight: bold;">(<?= $colNum++; ?>)</th>
                                    <?php endif; ?>
                                    <?php if ($hasKolom && $hasPeriode && ($posisi_hirarki === 'periode_baris' || $posisi_hirarki === 'periode_baris_pisah')): ?>
                                        <th style="border: 1px solid #fff; text-align: center; font-weight: bold;">(<?= $colNum++; ?>)</th>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($n = 1; $n <= $totalLeafCols; $n++): ?>
                                <th style="border: 1px solid #fff; text-align: center; font-weight: bold;">(<?= $colNum++; ?>)</th>
                            <?php endfor; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                        $desimal = (int)($indikator->desimal ?? 2);
                        $dataRowCounter = 0;

                        if (!$hasBaris && $hasKolom && $hasPeriode):
                            foreach ($itemsPeriode as $pIdx => $p):
                                $dataRowCounter++;
                                $rowClass = ($dataRowCounter % 2 === 1) ? 'row-odd' : 'row-even';
                        ?>
                                <tr class="<?= $rowClass; ?>">
                                    <td style="text-align: center; border: 1px solid #ffffff; padding: 6px 12px; font-weight: bold; font-size: 12px; vertical-align: middle;">
                                        <?= esc($p->nama_periode_ind); ?>
                                        <?php if (!empty($p->nama_periode_en) && $p->nama_periode_en !== $p->nama_periode_ind): ?>
                                            / <i style="font-weight: normal;"><?= esc($p->nama_periode_en); ?></i>
                                        <?php endif; ?>
                                    </td>
                                    <?php foreach ($leafColumns as $colLeaf): ?>
                                        <?php
                                        $cellKey = '0_' . $colLeaf['item_id'] . '_' . $p->id;
                                        $cellVal = $nilaiMap[$cellKey] ?? null;
                                        ?>
                                        <td class="col-val" style="text-align: right; border: 1px solid #ffffff; font-size: 12px; padding: 6px 12px;">
                                            <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                        <?php
                            endforeach;
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
                            <tr style="background: #fde5ce; font-weight: bold; color: #000;">
                                <td colspan="<?= $totalLeafCols + ($isRuntunWaktu ? 2 : (($hasNoCol ? 1 : 0) + (($isMultiBaris || $isBarisDuaKolom) ? 2 : 1) + ($isPeriodeBaris ? 1 : 0))); ?>" style="padding: 10px 15px; border: 1px solid #ffffff !important; background-color: #fde5ce !important;">
                                    <?= esc($dividerText); ?>
                                </td>
                            </tr>
                        <?php
                            // 4. Deteksi Baris Subtotal (Sub-Jumlah) seperti "Jumlah makanan / Total food"
                            elseif ($isSubtotalRow):
                                if ($isPeriodeBaris):
                                    foreach ($itemsPeriode as $pIdx => $p):
                        ?>
                                    <tr class="row-subtotal" style="background: #f9a066; font-weight: bold; color: #000000;">
                                        <?php if ($pIdx === 0): ?>
                                            <td colspan="<?= $hasNoCol ? 2 : 1; ?>" rowspan="<?= count($itemsPeriode); ?>" style="padding: 8px 14px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #000000 !important; background: #f9a066; vertical-align: middle;">
                                                <?= esc($rawNamaInd); ?>
                                                <?php if (!empty($rawNamaEn) && strtolower($rawNamaEn) !== $lowerNamaInd): ?>
                                                    / <i style="font-weight: normal; color: #000000;"><?= esc($rawNamaEn); ?></i>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td style="text-align: center; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #000000 !important; background: #f9a066; vertical-align: middle;">
                                            <?= esc($p->nama_periode_ind); ?>
                                        </td>
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                            $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $p->id;
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #000000 !important; background: #f9a066;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                        <?php
                                    endforeach;
                                else:
                        ?>
                                    <!-- BARIS SUBTOTAL STANDAR (TAMPILAN RESMI BPS GAMBAR 1: SAMA SEPERTI ANGKA HEADER #f9a066) -->
                                    <tr class="row-subtotal" style="background: #f9a066; font-weight: bold; color: #000000;">
                                        <td colspan="<?= ($isRuntunWaktu || $isBarisDuaKolom || $hasNoCol) ? 2 : 1; ?>" style="padding: 8px 14px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #000000 !important; background: #f9a066;">
                                            <?= esc($rawNamaInd); ?>
                                            <?php if (!empty($rawNamaEn) && strtolower($rawNamaEn) !== $lowerNamaInd): ?>
                                                / <i style="font-weight: normal; color: #000000;"><?= esc($rawNamaEn); ?></i>
                                            <?php endif; ?>
                                        </td>
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                            $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . ($isRuntunWaktu ? $idPeriodeAktif : $colLeaf['periode_id']);
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #000000 !important; background: #f9a066;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                        <?php
                                endif;
                            // 5. Deteksi Baris Grand Total (JUMLAH / JAWA TENGAH / PRODUK DOMESTIK REGIONAL BRUTO)
                            elseif ($isTotalRow):
                                $cfgTotal = $indikator->konfigurasi_total ?? 'default';
                                if ($cfgTotal === 'none') {
                                    continue; // Lewati baris total jika dikonfigurasi Tanpa Total
                                }

                                if ($cfgTotal === 'jawa_tengah') {
                                    $totalLabel = 'Jawa Tengah / Central Java';
                                } elseif ($cfgTotal === 'jumlah') {
                                    $totalLabel = 'Jumlah / Total';
                                } elseif ($cfgTotal === 'kustom' && !empty($indikator->label_total_kustom)) {
                                    $totalLabel = $indikator->label_total_kustom;
                                } elseif ($cfgTotal === 'runtun_waktu') {
                                    $totalLabel = 'Jumlah / Total';
                                } else {
                                    $totalLabel = $rawNamaInd;
                                    if (!empty($rawNamaEn) && strtolower($rawNamaEn) !== $lowerNamaInd && strtolower($rawNamaEn) !== 'jawa tengah') {
                                        $totalLabel .= ' / ' . $rawNamaEn;
                                    } elseif ($lowerNamaInd === 'jumlah') {
                                        $totalLabel .= ' / Total';
                                    } elseif ($lowerNamaInd === 'total') {
                                        $totalLabel .= ' / Total';
                                    }
                                }

                                if ($isRuntunWaktu):
                                    $hasRenderedViewTotal = true;
                                    foreach ($itemsPeriode as $pIdx => $p):
                        ?>
                                    <tr class="row-total" style="background: #FF6D1F; font-weight: bold; color: #ffffff;">
                                        <?php if ($pIdx === 0): ?>
                                            <td rowspan="<?= count($itemsPeriode); ?>" style="padding: 10px 14px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #FF6D1F; vertical-align: middle;">
                                                <?= esc($totalLabel); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td style="text-align: center; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #FF6D1F; vertical-align: middle;">
                                            <?= esc($p->nama_periode_ind); ?>
                                        </td>
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                            $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $p->id;
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #FF6D1F;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                        <?php
                                    endforeach;
                                elseif ($isPeriodeBaris):
                                    foreach ($itemsPeriode as $pIdx => $p):
                        ?>
                                    <tr class="row-total" style="background: #FF6D1F; font-weight: bold; color: #ffffff;">
                                        <?php if ($pIdx === 0): ?>
                                            <td colspan="<?= ($hasNoCol ? 1 : 0) + ($isMultiBaris ? 2 : 1); ?>" rowspan="<?= count($itemsPeriode); ?>" style="padding: 10px 14px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #FF6D1F; vertical-align: middle;">
                                                <?= esc($totalLabel); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td style="text-align: center; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #FF6D1F; vertical-align: middle;">
                                            <?= esc($p->nama_periode_ind); ?>
                                        </td>
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                             $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $p->id;
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #FF6D1F;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                        <?php
                                    endforeach;
                                else:
                        ?>
                                    <!-- BARIS GRAND TOTAL STANDAR -->
                                    <tr class="row-total" style="background: #FF6D1F; font-weight: bold; color: #ffffff;">
                                        <td colspan="<?= ($hasNoCol ? 1 : 0) + (($isMultiBaris || $isBarisDuaKolom || $isRuntunWaktu) ? 2 : 1); ?>" style="padding: 10px 14px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #FF6D1F;">
                                            <?= esc($totalLabel); ?>
                                        </td>
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                            $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $colLeaf['periode_id'];
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #FF6D1F;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                        <?php
                                endif;
                            // 6. Baris Data Biasa
                            else:
                                $dataRowCounter++;
                                $rowClass = ($dataRowCounter % 2 === 1) ? 'row-odd' : 'row-even';

                                $cleanNama   = $rawNamaInd;
                                $cleanNamaEn = $rawNamaEn;
                                $noText      = $dataRowCounter . '.';

                                // Aturan Huruf Tebal (Bold):
                                // Judul baris kiri SELALU TEBAL (font-weight: bold;) persis seperti di View Portal Tabel API
                                $fontWeightStyle = 'font-weight: bold;';

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
                                        <td colspan="2" style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle;">
                                            <?= esc($cleanNama); ?>
                                            <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                / <i style="font-weight: normal;"><?= esc($cleanNamaEn); ?></i>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Sel Data Matriks (Kolom Angka Rata Kanan Persis View Portal Tabel) -->
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                            $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $idPeriodeAktif;
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff; font-size: 12px; padding: 6px 12px;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                        <?php
                                elseif ($isPeriodeBaris):
                                    $blockBg = 'background-color: #fde5ce !important;';
                                    foreach ($itemsPeriode as $pIdx => $p):
                                        $pRowClass = ($pIdx % 2 === 0) ? 'row-odd' : 'row-even';
                        ?>
                                    <tr class="<?= $pRowClass; ?>">
                                        <?php if ($pIdx === 0): ?>
                                            <?php if ($hasNoCol): ?>
                                                <!-- Kolom No. -->
                                                <td rowspan="<?= count($itemsPeriode); ?>" style="text-align: center; width: 45px; border: 1px solid #ffffff !important; font-size: 12px; vertical-align: middle; <?= $blockBg; ?>">
                                                    <?= esc($noText); ?>
                                                </td>
                                            <?php endif; ?>

                                            <?php if ($hasDoubleCell): ?>
                                                <!-- Kolom 1 (Jenis Usaha / Komoditi) -->
                                                <td rowspan="<?= count($itemsPeriode); ?>" style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; vertical-align: middle; <?= $blockBg; ?>">
                                                    <?= esc($bCol1); ?>
                                                    <?php if (!empty($bCol1En) && strtolower($bCol1En) !== strtolower($bCol1)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($bCol1En); ?></i>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Kolom 2 (Satuan / Provinsi) -->
                                                <td rowspan="<?= count($itemsPeriode); ?>" style="text-align: center; border: 1px solid #ffffff !important; font-size: 12px; font-weight: 600; color: #475569; vertical-align: middle; <?= $blockBg; ?>">
                                                    <?= esc($bCol2); ?>
                                                    <?php if (!empty($bCol2En) && strtolower($bCol2En) !== strtolower($bCol2)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($bCol2En); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                            <?php elseif ($isMultiBaris): ?>
                                                <!-- Baris Kategori Induk tanpa Kolom 2: colspan 2 -->
                                                <td colspan="2" rowspan="<?= count($itemsPeriode); ?>" style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; vertical-align: middle; background-color: #fafafa; <?= $blockBg; ?>">
                                                    <?= esc($cleanNama); ?>
                                                    <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($cleanNamaEn); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                            <?php else: ?>
                                                <!-- Kolom Nama Wilayah / Baris -->
                                                <td rowspan="<?= count($itemsPeriode); ?>" style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff !important; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle; <?= $blockBg; ?>">
                                                    <?= esc($cleanNama); ?>
                                                    <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($cleanNamaEn); ?></i>
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
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; padding: 6px 12px;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
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
                                                            / <i style="font-weight: normal;"><?= esc($bCol1En); ?></i>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>

                                                <!-- Kolom 2 (Satuan / Provinsi / Bulan) - DIKUNCI MATI (TIDAK PERNAH DI-MERGE) -->
                                                <td style="text-align: center; border: 1px solid #ffffff; font-size: 12px; font-weight: 600; color: #475569; vertical-align: middle;">
                                                    <?= esc($bCol2); ?>
                                                    <?php if (!empty($bCol2En) && strtolower($bCol2En) !== strtolower($bCol2)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($bCol2En); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                            <?php else: ?>
                                                <!-- Baris Kategori Induk tanpa Kolom 2: colspan 2 -->
                                                <td colspan="2" style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff; font-size: 12px; font-weight: bold; background-color: #fafafa;">
                                                    <?= esc($cleanNama); ?>
                                                    <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($cleanNamaEn); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php elseif ($isBarisDuaKolom): ?>
                                            <?php if ((int)($baris->level_indentasi ?? 0) > 0): ?>
                                                <?php if ($isFirstChildOfParent): ?>
                                                    <!-- Kolom 1 (Induk): Rowspan sebanyak rincian anak -->
                                                    <td rowspan="<?= $currentParentRowspan; ?>" style="padding: 8px 12px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; vertical-align: middle; background-color: #fde5ce !important;">
                                                        <?= esc($currentParentInd); ?>
                                                        <?php if (!empty($currentParentEn) && strtolower($currentParentEn) !== strtolower($currentParentInd)): ?>
                                                            / <i style="font-weight: normal;"><?= esc($currentParentEn); ?></i>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php $isFirstChildOfParent = false; ?>
                                                <?php endif; ?>

                                                <!-- Kolom 2 (Rincian Anak, misal: Provinsi) -->
                                                <td style="padding: 6px 12px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle;">
                                                    <?= esc($cleanNama); ?>
                                                    <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($cleanNamaEn); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                            <?php else: ?>
                                                <!-- Level 0 Mandiri / Total tanpa anak: Colspan 2 -->
                                                <td colspan="2" style="padding: 8px 12px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?>">
                                                    <?= esc($cleanNama); ?>
                                                    <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                        / <i style="font-weight: normal;"><?= esc($cleanNamaEn); ?></i>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($hasNoCol): ?>
                                                <!-- Kolom No. -->
                                                <td style="text-align: center; width: 45px; border: 1px solid #ffffff; font-size: 12px; vertical-align: middle;">
                                                    <?= esc($noText); ?>
                                                </td>
                                            <?php endif; ?>

                                            <!-- Kolom Nama Wilayah / Baris -->
                                            <td style="padding-left: <?= 10 + ((int)($baris->level_indentasi ?? 0) * 16); ?>px; border: 1px solid #ffffff; font-size: 12px; <?= $fontWeightStyle; ?> vertical-align: middle;">
                                                <?= esc($cleanNama); ?>
                                                <?php if (!empty($cleanNamaEn) && strtolower($cleanNamaEn) !== strtolower($cleanNama)): ?>
                                                    / <i style="font-weight: normal;"><?= esc($cleanNamaEn); ?></i>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <!-- Sel Data Matriks (Kolom Angka Rata Kanan Persis View Portal Tabel) -->
                                        <?php foreach ($leafColumns as $colLeaf): ?>
                                            <?php
                                            $cellKey = $baris->id . '_' . $colLeaf['item_id'] . '_' . $colLeaf['periode_id'];
                                            $cellVal = $nilaiMap[$cellKey] ?? null;
                                            ?>
                                            <td class="col-val" style="text-align: right; border: 1px solid #ffffff; font-size: 12px; padding: 6px 12px;">
                                                <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                        <?php
                                endif;
                            endif;
                        endforeach;

                        // Jika mode Runtun Waktu aktif namun master baris tidak memiliki item total khusus, otomatis tambahkan blok total
                        if ($isRuntunWaktu && !$hasRenderedViewTotal && !empty($itemsPeriode)):
                            foreach ($itemsPeriode as $pIdx => $p):
                        ?>
                            <tr class="row-total" style="background: #FF6D1F; font-weight: bold; color: #ffffff;">
                                <?php if ($pIdx === 0): ?>
                                    <td rowspan="<?= count($itemsPeriode); ?>" style="padding: 10px 14px; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #FF6D1F; vertical-align: middle;">
                                        Jumlah / Total
                                    </td>
                                <?php endif; ?>
                                <td style="text-align: center; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; color: #ffffff !important; background: #FF6D1F; vertical-align: middle;">
                                    <?= esc($p->nama_periode_ind); ?>
                                </td>
                                <?php foreach ($leafColumns as $colLeaf): ?>
                                    <?php
                                    $cellKey = '0_' . $colLeaf['item_id'] . '_' . $p->id;
                                    $cellVal = $nilaiMap[$cellKey] ?? null;
                                    ?>
                                    <td class="col-val" style="text-align: right; border: 1px solid #ffffff !important; font-size: 12px; font-weight: bold; padding: 6px 12px; color: #ffffff !important; background: #FF6D1F;">
                                        <?= ($cellVal !== null && $cellVal !== '') ? (is_numeric($cellVal) ? number_format((float)$cellVal, $desimal, ',', '.') : esc($cellVal)) : '–'; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php
                            endforeach;
                        endif;
                        endif;
                        ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="<?= (!$hasBaris && $hasKolom && $hasPeriode) ? ($totalLeafCols + 1) : ($totalLeafCols + ($isRuntunWaktu ? 2 : (($hasNoCol ? 1 : 0) + (($isMultiBaris || $isBarisDuaKolom) ? 2 : 1) + ($isPeriodeBaris ? 1 : 0)))); ?>" style="border-top: 1px solid #000"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- FOOTER CATATAN DAN SUMBER RESMI DDA BPS -->
            <div style="font-size: 11px; margin-top: 10px; opacity: 0.6; line-height: 1.5;" class="no-print">
                <div><b>Catatan/</b><i>Note</i>: <?= nl2br(esc($indikator->catatan ?: '-')); ?></div>
                <div><b>Sumber/</b><i>Source</i>: <?= esc($tabel->unitkerja_ind ?: 'BPS Provinsi Jawa Tengah'); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL UPLOAD EXCEL WALIDATA (TABEL DINAMIS) -->
<div id="ModalUploadExcelDinamis" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom px-4 py-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center shadow-xs" style="width: 40px; height: 40px;">
                        <i class="bi bi-file-earmark-arrow-up-fill fs-5 text-success"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.05rem;">Upload Berkas Excel Tabel Dinamis</h5>
                        <small class="text-muted" style="font-size: 0.8rem;">Impor data pengisian tabel dinamis secara otomatis</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formUploadExcelDinamis" enctype="multipart/form-data">
                <input type="hidden" name="id_tahun_tabel" value="<?= esc($tabel->id_tahun_tabel); ?>">
                
                <div class="modal-body p-4">
                    <!-- Info Alert: Judul Tabel & Link Template -->
                    <div class="p-3 rounded-3 mb-3 border bg-light d-flex align-items-center justify-content-between gap-3" style="border-color: #e2e8f0 !important;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-table text-primary fs-5 mt-0.5"></i>
                            <div class="fw-semibold text-dark" style="font-size: 0.95rem; line-height: 1.4;"><?= esc($tabel->judul_ind); ?></div>
                        </div>
                        <a href="<?= base_url('admin/download_template_tabel/' . $tabel->id_tahun_tabel); ?>" class="btn btn-sm btn-outline-success text-nowrap d-inline-flex align-items-center gap-1 shadow-xs fw-semibold flex-shrink-0" style="font-size: 0.75rem; border-radius: 6px;">
                            <i class="bi bi-download"></i> Unduh Template
                        </a>
                    </div>

                    <!-- Drag & Drop Zone Modern -->
                    <div id="dropZoneExcel" class="upload-dropzone p-4 text-center rounded-3 mb-3" style="border: 2px dashed #94a3b8; background: #f8fafc; cursor: pointer; transition: all 0.25s ease;">
                        <input type="file" id="file_excel_input" name="file_excel" accept=".xlsx" style="display: none;">
                        
                        <div id="dropZonePrompt" class="py-2">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-xs" style="width: 58px; height: 58px; background: #ecfdf5; color: #059669;">
                                <i class="bi bi-file-earmark-excel" style="font-size: 1.85rem;"></i>
                            </div>
                            <div class="fw-bold text-dark mb-1" style="font-size: 1rem;">
                                Pilih berkas Excel atau seret ke sini
                            </div>
                            <div class="text-secondary small mb-3">
                                Format template resmi DDA berekstensi <strong>.xlsx</strong>
                            </div>
                            <div>
                                <span class="btn btn-sm btn-success px-3 py-1.5 fw-semibold shadow-xs" style="background: #059669 !important; border: none; font-size: 0.8rem; border-radius: 6px; pointer-events: none;">
                                    <i class="bi bi-folder2-open me-1"></i> Telusuri Berkas
                                </span>
                            </div>
                        </div>

                        <div id="dropZoneFileInfo" style="display: none;" class="py-2">
                            <div class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between gap-3 shadow-xs text-start" style="border-color: #e2e8f0 !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-2 bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                        <i class="bi bi-file-earmark-spreadsheet-fill fs-4 text-success"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark text-truncate" id="selectedFileName" style="max-width: 380px;">file.xlsx</div>
                                        <div class="text-muted small" id="selectedFileSize">0 KB &bull; <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Siap diimpor</span></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle d-flex align-items-center justify-content-center" id="btnRemoveFile" style="width: 32px; height: 32px; padding: 0;" title="Hapus berkas terpilih">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Instruksi Ringkas -->
                    <div class="p-3 rounded-3 mb-3 border bg-light" style="font-size: 0.8rem; line-height: 1.5; border-color: #e2e8f0 !important;">
                        <div class="fw-bold text-secondary mb-1 d-flex align-items-center gap-1">
                            <i class="bi bi-lightbulb-fill text-warning"></i> <span>Panduan Pengisian:</span>
                        </div>
                        <ul class="mb-0 ps-3 text-muted">
                            <li>Isi data hanya pada area sel putih di lembar kerja <b>"Data"</b>.</li>
                            <li>Gunakan koma (<code>,</code>) untuk desimal atau format angka standar Excel.</li>
                            <li>Simbol resmi BPS: <code>-</code> (kosong), <code>NA</code> (belum tersedia), <code>e</code> (estimasi).</li>
                            <li>Jangan mengubah susunan kolom, baris, maupun struktur berkas template.</li>
                        </ul>
                    </div>

                    <!-- Progress Bar / Processing State -->
                    <div id="uploadProgressContainer" class="mt-3" style="display: none;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold text-success small" id="uploadStatusText"><i class="spinner-border spinner-border-sm me-1"></i> Membaca & memverifikasi metadata berkas...</span>
                            <span class="badge bg-success-subtle text-success" id="uploadPercentText">Proses</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>

                    <!-- Error Alert Inside Modal -->
                    <div id="uploadErrorAlert" class="alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0" style="display: none !important;">
                        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                        <div id="uploadErrorMessage" style="font-size: 0.85rem;"></div>
                    </div>

                    <!-- Success Alert Inside Modal -->
                    <div id="uploadSuccessAlert" class="alert alert-success d-flex align-items-center gap-2 mt-3 mb-0" style="display: none !important;">
                        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
                        <div id="uploadSuccessMessage" style="font-size: 0.85rem;"></div>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3 bg-light border-top">
                    <button type="button" class="btn btn-sm btn-light border px-3 fw-semibold text-secondary" data-bs-dismiss="modal" id="btnCancelUpload" style="border-radius: 6px;">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success px-4 fw-semibold shadow-xs d-inline-flex align-items-center gap-2" id="btnSubmitUpload" disabled style="background: #059669 !important; border-color: #059669 !important; border-radius: 6px;">
                        <i class="bi bi-cloud-arrow-up-fill"></i> Mulai Impor Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var dropZone = document.getElementById('dropZoneExcel');
    var fileInput = document.getElementById('file_excel_input');

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', function(e) {
            if (e.target.id === 'btnRemoveFile' || $(e.target).closest('#btnRemoveFile').length) {
                return;
            }
            fileInput.click();
        });

        ['dragenter', 'dragover'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#10b981';
                dropZone.style.backgroundColor = '#ecfdf5';
            }, false);
        });

        ['dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#94a3b8';
                dropZone.style.backgroundColor = '#f8fafc';
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            var dt = e.dataTransfer;
            var files = dt.files;
            if (files && files.length > 0) {
                fileInput.files = files;
                handleSelectedFile(files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleSelectedFile(this.files[0]);
            }
        });

        $('#btnRemoveFile').on('click', function(e) {
            e.stopPropagation();
            fileInput.value = '';
            $('#dropZonePrompt').show();
            $('#dropZoneFileInfo').hide();
            $('#btnSubmitUpload').prop('disabled', true);
        });
    }

    function handleSelectedFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'xlsx') {
            $('#uploadErrorMessage').text('Berkas yang dipilih harus berekstensi .xlsx');
            $('#uploadErrorAlert').show().addClass('d-flex');
            fileInput.value = '';
            $('#btnSubmitUpload').prop('disabled', true);
            return;
        }

        $('#uploadErrorAlert').hide().removeClass('d-flex');
        $('#selectedFileName').text(file.name);
        var sizeKb = (file.size / 1024).toFixed(1);
        $('#selectedFileSize').text(sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb + ' KB');
        $('#dropZonePrompt').hide();
        $('#dropZoneFileInfo').show();
        $('#btnSubmitUpload').prop('disabled', false);
    }

    // Modal reset when opened
    var uploadModalEl = document.getElementById('ModalUploadExcelDinamis');
    if (uploadModalEl) {
        uploadModalEl.addEventListener('show.bs.modal', function() {
            if (fileInput) fileInput.value = '';
            $('#dropZonePrompt').show();
            $('#dropZoneFileInfo').hide();
            $('#uploadProgressContainer').hide();
            $('#uploadErrorAlert').hide().removeClass('d-flex');
            $('#uploadSuccessAlert').hide().removeClass('d-flex');
            $('#btnSubmitUpload').prop('disabled', true);
            $('#btnCancelUpload').prop('disabled', false);
        });
    }

    // AJAX Form Submit
    $('#formUploadExcelDinamis').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $('#uploadProgressContainer').show();
        $('#uploadErrorAlert').hide().removeClass('d-flex');
        $('#uploadSuccessAlert').hide().removeClass('d-flex');
        $('#btnSubmitUpload').prop('disabled', true);
        $('#btnCancelUpload').prop('disabled', true);

        $.ajax({
            url: '<?= base_url("admin/upload_excel_dinamis"); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                $('#uploadProgressContainer').hide();
                if (res.status === 'success') {
                    $('#uploadSuccessMessage').html(res.message);
                    $('#uploadSuccessAlert').show().addClass('d-flex');

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Impor Berhasil!',
                            html: res.message + '<br><small class="text-muted">Total ' + (res.filled_cells || 0) + ' sel data diperbarui</small>',
                            confirmButtonColor: '#059669',
                            confirmButtonText: 'Selesai'
                        }).then(function() {
                            window.location.reload();
                        });
                    } else {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    $('#uploadErrorMessage').text(res.message || 'Terjadi kesalahan saat memproses berkas Excel.');
                    $('#uploadErrorAlert').show().addClass('d-flex');
                    $('#btnSubmitUpload').prop('disabled', false);
                    $('#btnCancelUpload').prop('disabled', false);
                }
            },
            error: function(xhr) {
                $('#uploadProgressContainer').hide();
                var msg = 'Gagal menghubungi server. Pastikan koneksi stabil.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#uploadErrorMessage').text(msg);
                $('#uploadErrorAlert').show().addClass('d-flex');
                $('#btnSubmitUpload').prop('disabled', false);
                $('#btnCancelUpload').prop('disabled', false);
            }
        });
    });
});
</script>
