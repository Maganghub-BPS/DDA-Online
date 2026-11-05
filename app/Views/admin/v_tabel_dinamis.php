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

// Siapkan Back URL dengan Smart Fallback ke Instansi Tabel
$id_unitkerja = $tabel->id_unitkerja ?? 0;
$smartFallback = !empty($id_unitkerja) ? base_url('admin/dda/view_tabel?id=' . $id_unitkerja) : base_url('admin/dda');

$back_url = $back_url ?? $smartFallback;
if (empty($back_url) && isset($_SERVER['HTTP_REFERER'])) {
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
        padding: 3px 4px !important;
        background: #f9a066 !important;
        color: #000000 !important;
        font-size: 10px !important;
        border: 1px solid #ffffff !important;
        text-align: center !important;
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
        color: #000000 !important;
    }

    .main-table tbody tr.row-even,
    .main-table tbody tr.row-even td {
        background-color: #fef3e6 !important;
        color: #000000 !important;
    }

    .main-table tbody tr.category-row,
    .main-table tbody tr.category-row td {
        background-color: #fde5ce !important;
        color: #000000 !important;
        font-weight: bold !important;
        border: 1px solid #ffffff !important;
        padding: 10px 15px !important;
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
    .main-table tbody tr.row-total td,
    .main-table tbody tr.grand-total-row,
    .main-table tbody tr.grand-total-row td,
    .main-table tbody tr.grand-total-row th {
        background-color: #FF6D1F !important;
        color: #ffffff !important;
        font-weight: bold !important;
        border: 1px solid #ffffff !important;
    }

    .main-table tbody tr.row-total td.col-val,
    .main-table tbody tr.grand-total-row td.text-end,
    .main-table tbody tr.grand-total-row td.col-val {
        color: #ffffff !important;
        text-align: right !important;
    }

    .main-table tbody tr.row-total td i,
    .main-table tbody tr.grand-total-row td i {
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
    <!-- ===== TOOLBAR ATAS: KEMBALI, TAHUN CHECKLIST, KONFIGURASI, UPLOAD, UNDUH ===== -->
    <div class="row g-3 mb-4 align-items-center no-print">
        <div class="col-12 col-md-auto">
            <a href="<?= esc($back_url); ?>" class="btn-rounded-modern btn-back-modern w-100 w-md-auto">
                <i class="bi bi-arrow-left"></i> KEMBALI
            </a>
        </div>

        <div class="col-12 col-md d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
            <!-- Dropdown Checklist Multi-Tahun -->
            <?php if (!empty($selectable_years)): ?>
                <div class="dropdown">
                    <button class="btn-rounded-modern btn-back-modern shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        <i class="bi bi-calendar-event me-1 text-primary"></i> <span id="year-filter-label">Tahun (<?= count($selected_years ?? []); ?>)</span>
                    </button>
                    <div class="dropdown-menu p-3 shadow-lg border-0" style="border-radius: 1rem; min-width: 230px;">
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
                                        <?php if ($yr == ($ta - 1)): ?>
                                            <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">Aktif</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr class="my-2">
                        <?php if (!empty($is_admin_bps)): ?>
                            <button type="button" onclick="lockDefaultYears()" class="btn btn-sm btn-outline-primary w-100 py-1 fw-semibold mb-2" style="font-size: 11px; border-radius: 6px;">
                                <i class="bi bi-pin-angle-fill me-1"></i> Kunci Sebagai Default
                            </button>
                        <?php endif; ?>
                        <button type="button" onclick="resetYearFilter()" class="btn btn-sm btn-link text-muted p-0 text-decoration-none small w-100 text-center" style="font-size: 11px;">Reset ke Default</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tombol Konfigurasi Tabel (Khusus Admin BPS) -->
            <?php if (!empty($is_admin_bps)): ?>
                <button type="button" class="btn-rounded-modern btn-back-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#configModalDinamis">
                    <i class="bi bi-gear-fill text-warning"></i> KONFIGURASI
                </button>
            <?php endif; ?>

            <!-- Tombol Upload Excel (Admin BPS & Walidata) -->
            <button type="button" class="btn-rounded-modern upload-btn-modern shadow-success" data-bs-toggle="modal" data-bs-target="#ModalUploadExcelDinamis">
                <i class="bi bi-cloud-arrow-up-fill"></i> <span class="d-none d-sm-inline">UPLOAD EXCEL</span><span class="d-inline d-sm-none">UPLOAD</span>
            </button>

            <?php $thDataOtomatis = (int)$ta - 1; ?>
            <!-- Tombol Unduh Template Excel Otomatis Sesuai Tahun Publikasi ($ta - 1) -->
            <a href="<?= base_url('admin/download_template_tabel/' . $tabel->id . '/' . $thDataOtomatis); ?>" class="btn-rounded-modern export-btn-modern shadow-primary" title="Unduh Template Excel Resmi Data Tahun <?= $thDataOtomatis; ?>">
                <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-sm-inline">UNDUH TEMPLATE</span><span class="d-inline d-sm-none">UNDUH</span>
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
                <a href="<?= base_url('admin/generator_tabel/' . $tabel->id); ?>" class="btn-rounded-modern export-btn-modern shadow-primary">
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
                            <?= esc($display_title_ind ?? $cleanJudulInd); ?>
                        </span>
                        <span class="title-en">
                            <?= esc($display_title_en ?? $cleanJudulEn); ?>
                        </span>
                    </td>
                </tr>
            </table>

            <!-- INFO NOTICE -->
            <?php if (!empty($fallback_notice)): ?>
                <div class="alert alert-info py-2 px-3 mb-3 border-0 rounded-3 shadow-none d-flex align-items-center gap-2" style="font-size: 0.82rem; background: #e0f2fe; color: #0369a1;">
                    <i class="bi bi-info-circle-fill fs-6"></i>
                    <span><?= esc($fallback_notice); ?></span>
                </div>
            <?php endif; ?>

            <!-- RENDER MATRIKS TABEL DINAMIS MULTI-TAHUN -->
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
                'is_admin'          => !empty($is_admin_bps),
            ]); ?>

            <!-- FOOTER CATATAN DAN SUMBER RESMI DDA BPS -->
            <div style="font-size: 11px; margin-top: 10px; opacity: 0.6; line-height: 1.5;" class="no-print">
                <?php if (!empty($indikator->satuan)): ?>
                    <div><b>Satuan/</b><i>Unit</i>: <?= esc($indikator->satuan); ?></div>
                <?php endif; ?>
                <div><b>Catatan/</b><i>Note</i>: <?= nl2br(esc($indikator->catatan ?: '-')); ?></div>
                <div><b>Sumber/</b><i>Source</i>: <?= esc($tabel->unitkerja_ind ?? 'BPS Provinsi Jawa Tengah'); ?></div>
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
                <?= csrf_field(); ?>
                <input type="hidden" name="id_tabel" value="<?= esc($tabel->id); ?>">
                    <input type="hidden" name="id_tahun_tabel" value="<?= esc($tabel->id); ?>">
                <input type="hidden" name="tahun_data" value="<?= $thDataOtomatis; ?>">
                
                <div class="modal-body p-4">
                    <div class="mb-3 p-2.5 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-cloud-arrow-up-fill text-success fs-5"></i>
                            <span class="small fw-bold text-dark">Upload Tabel Tahun <?= esc($thDataOtomatis); ?></span>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 fw-bold" style="font-size: 0.85rem;">
                            Tahun <?= esc($ta); ?>
                        </span>
                    </div>
                    <!-- Info Alert: Judul Tabel & Link Template -->
                    <div class="p-3 rounded-3 mb-3 border bg-light d-flex align-items-center justify-content-between gap-3" style="border-color: #e2e8f0 !important;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-table text-primary fs-5 mt-0.5"></i>
                            <div class="fw-semibold text-dark" style="font-size: 0.95rem; line-height: 1.4;"><?= esc($tabel->judul_ind); ?></div>
                        </div>
                        <a href="<?= base_url('admin/download_template_tabel/' . $tabel->id . '/' . $thDataOtomatis); ?>" class="btn btn-sm btn-outline-success text-nowrap d-inline-flex align-items-center gap-1 shadow-xs fw-semibold flex-shrink-0" style="font-size: 0.75rem; border-radius: 6px;">
                            <i class="bi bi-download"></i> Unduh Template (<?= $thDataOtomatis; ?>)
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
                                Format template resmi DDA (Data Tahun <strong><?= esc($thDataOtomatis); ?></strong>) berekstensi <strong>.xlsx</strong><br>
                                <span class="text-muted text-xxs"><i class="bi bi-info-circle me-1"></i>Berkas dari tahun data lain akan ditolak otomatis oleh sistem.</span>
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
                    <button type="button" class="btn btn-light border rounded-pill px-4 py-2 text-xs fw-semibold text-secondary shadow-xs" data-bs-dismiss="modal" id="btnCancelUpload">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 py-2 text-xs fw-semibold shadow-xs d-inline-flex align-items-center gap-2" id="btnSubmitUpload" disabled style="background: #059669 !important; border-color: #059669 !important;">
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
                        // Sembunyikan modal bootstrap agar tidak menumpuk di latar belakang
                        var uploadModalEl = document.getElementById('ModalUploadExcelDinamis');
                        if (uploadModalEl) {
                            var modalInst = bootstrap.Modal.getInstance(uploadModalEl);
                            if (modalInst) {
                                modalInst.hide();
                            }
                        }

                        Swal.fire({
                            html: `
                                <div class="text-center pt-2 pb-1">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-xs" style="width: 56px; height: 56px; background: #ecfdf5; border: 2px solid #a7f3d0; color: #059669;">
                                        <i class="bi bi-check-lg" style="font-size: 1.85rem;"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-2">Impor Berhasil!</h5>
                                    <p class="text-secondary small mb-3 px-2" style="line-height: 1.55;">
                                        Data pengisian tabel dinamis berhasil diproses dan disimpan ke database.
                                    </p>
                                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-success-subtle text-success border border-success-subtle text-xs fw-semibold">
                                        <i class="bi bi-database-check"></i>
                                        <span><b>${res.filled_cells || 0} sel data</b> berhasil diperbarui</span>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Selesai',
                            width: '390px',
                            customClass: {
                                popup: 'rounded-4 shadow-lg border-0 p-4',
                                htmlContainer: 'm-0 p-0',
                                actions: 'mt-3 w-100 justify-content-center border-0 p-0',
                                confirmButton: 'btn btn-success rounded-pill px-4 py-2 text-xs fw-semibold shadow-xs'
                            },
                            buttonsStyling: false
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

<!-- MODAL KONFIGURASI TABEL DINAMIS -->
<?= view('admin/parts/modal_config_dinamis', [
    'tabel'           => $tabel,
    'indikator'       => $indikator,
    'config_tampilan' => $config_tampilan ?? [],
]); ?>

<script>
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

function lockDefaultYears() {
    var checked = Array.from(document.querySelectorAll('.year-filter-check:checked')).map(function(cb) { return cb.value; });
    if (!checked.length) {
        alert('Pilih minimal 1 tahun terlebih dahulu.');
        return;
    }
    var idIndikator = <?= (int)($indikator->id ?? 0); ?>;
    var idTabel = <?= (int)($tabel->id_master_tabel ?? ($tabel->id ?? 0)); ?>;

    var fd = new FormData();
    fd.append('id_indikator', idIndikator);
    fd.append('id_tabel', idTabel);
    fd.append('tahun_tampil', checked.join(','));
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= base_url("admin/ajax_save_default_tahun_tampil"); ?>', {
        method: 'POST',
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            alert('Tahun default (' + checked.join(', ') + ') berhasil dikunci untuk publikasi!');
        } else {
            alert('Gagal mengunci: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(function(err) {
        alert('Kesalahan jaringan: ' + err);
    });
}
</script>
