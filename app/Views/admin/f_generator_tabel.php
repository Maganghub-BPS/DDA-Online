<?php
/**
 * VIEW: GENERATOR INDIKATOR TABEL & LIVE MATRIX PREVIEW (f_generator_tabel.php)
 * Modul Sprint 3: Perakitan Data Cube & Pratinjau Matriks Dinamis BPS Jateng
 * Layout: Top-Bottom Stacked (Form di Atas, Live Matrix Preview Lebar Penuh di Bawah)
 */
$is_dynamic = (int)($tabel->is_dynamic ?? 0);
$id_indikator_existing = $indikator->id ?? null;
$sel_karakteristik = $indikator->id_karakteristik_kelompok ?? '';
$sel_baris = $indikator->id_baris_kelompok ?? '';
$sel_periode = $indikator->id_periode_kelompok ?? '';
$sumber_val = $indikator->sumber ?? ($tabel->unitkerja_ind ? 'Dinas/Instansi ' . $tabel->unitkerja_ind : '');
$catatan_val = $indikator->catatan ?? '';
?>

<style>
    /* Styling Khusus Generator Tabel Dinamis */
    .generator-card {
        border-radius: 14px;
        transition: all 0.2s ease;
    }
    .badge-dynamic-active {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
    }
    .badge-dynamic-inactive {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .preview-table-container {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        width: 100%;
        max-width: 100%;
    }
    /* Scroll Container Mandiri untuk Tabel Matriks */
    .matrix-scroll-wrapper {
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: 600px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        width: 100%;
        position: relative;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #f1f5f9;
    }
    /* Custom Scrollbar Horizontal & Vertikal Tebal & Jelas */
    .matrix-scroll-wrapper::-webkit-scrollbar {
        height: 12px;
        width: 10px;
    }
    .matrix-scroll-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 6px;
    }
    .matrix-scroll-wrapper::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 6px;
        border: 2px solid #f1f5f9;
    }
    .matrix-scroll-wrapper::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    /* Tabel Matrix Lebar Maksimal Dinamis */
    .table-matrix {
        width: max-content !important;
        min-width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    /* Freeze / Sticky Kolom Pertama (Kabupaten/Kota) di Kiri */
    .th-freeze-col {
        position: sticky !important;
        left: 0 !important;
        z-index: 25 !important;
        background-color: #f8fafc !important;
        min-width: 250px !important;
        width: 250px !important;
        border-right: 2px solid #cbd5e1 !important;
        box-shadow: 3px 0 6px rgba(0, 0, 0, 0.05);
    }
    .table-matrix tbody td.td-freeze-col {
        position: sticky !important;
        left: 0 !important;
        z-index: 15 !important;
        background-color: #ffffff !important;
        min-width: 250px !important;
        width: 250px !important;
        border-right: 2px solid #cbd5e1 !important;
        box-shadow: 3px 0 6px rgba(0, 0, 0, 0.05);
    }
    .row-kategori td.td-freeze-col {
        background-color: #f8fafc !important;
    }
    .row-subtotal td.td-freeze-col {
        background-color: #fffbeb !important;
    }
    .row-total td.td-freeze-col {
        background-color: #ecfdf5 !important;
    }
    .table-matrix tbody tr:hover td.td-freeze-col {
        background-color: #f1f5f9 !important;
    }

    /* Kolom Data Minimal 135px agar Rapi dan Tidak Gepeng */
    .th-data-col {
        min-width: 135px !important;
        white-space: normal !important;
    }
    .th-group-col {
        background-color: #f1f5f9 !important;
        font-weight: 700 !important;
    }
    .th-periode-col {
        min-width: 90px !important;
    }

    /* Header Matriks Resmi BPS */
    .table-matrix thead th {
        background-color: #f8fafc !important;
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        border: 1px solid #cbd5e1 !important;
        padding: 9px 12px !important;
        white-space: normal !important;
        line-height: 1.25;
    }
    .table-matrix thead th .th-en {
        display: block;
        font-weight: 500;
        font-style: italic;
        color: #64748b;
        font-size: 0.74rem;
        margin-top: 2px;
    }
    .table-matrix tbody td {
        border: 1px solid #e2e8f0 !important;
        padding: 8px 12px !important;
        font-size: 0.84rem !important;
        vertical-align: middle !important;
    }
    /* Tipe Baris Khusus */
    .row-kategori td {
        background-color: #f8fafc !important;
        font-weight: 700 !important;
        color: #0f172a !important;
    }
    .row-kategori .cell-hatched {
        background: #f8fafc !important;
        color: #94a3b8 !important;
        font-weight: 600 !important;
        text-align: center !important;
    }
    .row-subtotal td {
        background-color: #fffbeb !important;
        font-weight: 700 !important;
        color: #92400e !important;
        border-top: 1.5px solid #f59e0b !important;
    }
    .row-total td {
        background-color: #ecfdf5 !important;
        font-weight: 800 !important;
        color: #065f46 !important;
        border-top: 2px solid #059669 !important;
        border-bottom: 3px double #059669 !important;
    }
    /* Indentasi Hirarki Baris (Menjorok Bersih Tanpa Strip) */
    .table-matrix tbody td.indent-level-0 {
        padding-left: 14px !important;
    }
    .table-matrix tbody td.indent-level-1 {
        padding-left: 32px !important;
    }
    .table-matrix tbody td.indent-level-2 {
        padding-left: 50px !important;
    }
    .cell-numeric-placeholder {
        text-align: center !important;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        color: #94a3b8 !important;
        font-weight: 600;
    }
</style>

<div class="container-fluid py-2 px-0">
    <!-- ===== COMPACT HEADER & NAVIGASI ATAS ===== -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('admin/master_tabel'); ?>" class="btn btn-sm btn-white border rounded-pill px-3 py-1 text-secondary text-xs fw-semibold shadow-xs">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark text-sm">
                    <?= esc(!empty($tabel->no_tabel) ? 'Tabel ' . $tabel->no_tabel . ' : ' : '') . esc(format_judul_tabel($tabel->judul_ind, $tabel->periode_id ?? $tabel->periode ?? '')); ?>
                </span>
                <?php if ($is_dynamic): ?>
                    <span class="badge rounded-pill badge-dynamic-active px-2 py-1 text-xxs fw-bold">
                        <i class="bi bi-boxes me-1"></i> Dinamis Aktif
                    </span>
                <?php else: ?>
                    <span class="badge rounded-pill bg-light text-secondary border px-2 py-1 text-xxs fw-semibold">
                        Format Spreadsheet
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex align-items-center gap-1">
            <?php if (!empty($tabel->link_tabel)): ?>
                <a href="<?= esc($tabel->link_tabel); ?>" target="_blank" class="btn btn-sm btn-white border rounded-pill px-2 py-1 text-xxs text-muted shadow-xs">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Spreadsheet
                </a>
            <?php endif; ?>
            <a href="<?= base_url('admin/master_tabel/edt/' . $tabel->id_tahun_tabel . '/1'); ?>" class="btn btn-sm btn-white border rounded-pill px-2 py-1 text-xxs text-secondary shadow-xs">
                <i class="bi bi-pencil me-1"></i> Edit Master
            </a>
        </div>
    </div>

    <!-- ===== FORMULIR KONFIGURASI DIMENSI (PANEL SATU BARIS RAMPING) ===== -->
    <div class="card border-0 shadow-sm generator-card mb-3" style="border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-3">
            <form action="<?= base_url('admin/save_generator_tabel'); ?>" method="post" id="formGenerator">
                <input type="hidden" name="id_tahun_tabel" value="<?= $tabel->id_tahun_tabel; ?>">

                <div class="row g-2 align-items-end">
                    <!-- 1. Karakteristik Kolom -->
                    <div class="col-md-3">
                        <label class="form-label text-xxs fw-bold text-uppercase text-secondary mb-1">
                            1. Karakteristik Kolom <span class="text-danger">*</span>
                        </label>
                        <select name="id_karakteristik_kelompok" id="select_karakteristik" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Kolom --</option>
                            <?php foreach ($list_karakteristik as $k): ?>
                                <option value="<?= $k->id; ?>" <?= ($sel_karakteristik == $k->id) ? 'selected' : ''; ?>>
                                    <?= esc($k->nama_kelompok_ind); ?> (<?= (int)$k->total_items; ?> item)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 2. Rincian / Wilayah (Baris) -->
                    <div class="col-md-3">
                        <label class="form-label text-xxs fw-bold text-uppercase text-secondary mb-1">
                            2. Rincian / Wilayah <span class="text-danger">*</span>
                        </label>
                        <select name="id_baris_kelompok" id="select_baris" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Wilayah/Baris --</option>
                            <?php foreach ($list_baris as $b): ?>
                                <option value="<?= $b->id; ?>" <?= ($sel_baris == $b->id) ? 'selected' : ''; ?>>
                                    <?= esc($b->nama_kelompok_ind); ?> (<?= (int)$b->total_items; ?> baris)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 3. Periode Waktu (Opsional) -->
                    <div class="col-md-3">
                        <label class="form-label text-xxs fw-bold text-uppercase text-secondary mb-1">
                            3. Periode Waktu <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <select name="id_periode_kelompok" id="select_periode" class="form-select form-select-sm">
                            <option value="">-- Tanpa Periode --</option>
                            <?php foreach ($list_periode as $p): ?>
                                <option value="<?= $p->id; ?>" <?= ($sel_periode == $p->id) ? 'selected' : ''; ?>>
                                    <?= esc($p->nama_kelompok_ind); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 4. Tombol Aksi Simpan & Lepas Status -->
                    <div class="col-md-3 d-flex align-items-center justify-content-end gap-1">
                        <?php if ($is_dynamic): ?>
                            <a href="<?= base_url('admin/unlink_generator_tabel/' . $tabel->id_tahun_tabel); ?>" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-2 btn-unlink-dinamis text-xxs" style="height: 31px; display: inline-flex; align-items: center;" title="Lepas Status Dinamis">
                                <i class="bi bi-link-45deg me-1"></i> Lepas
                            </a>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary btn-sm px-3 py-1 fw-bold d-inline-flex align-items-center gap-1 shadow-sm rounded-2 text-xs" style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none; height: 31px;">
                            <i class="bi bi-check2-circle"></i>
                            <span>Simpan Matriks</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- BAGIAN 2: KANVAS LIVE MATRIX PREVIEW (BAWAH - LEBAR PENUH 100%)    -->
    <!-- =================================================================== -->
    <div class="card border-0 shadow-sm generator-card mb-4" style="border: 1px solid #e2e8f0 !important;">
        
        <!-- Header Card Pratinjau Ramping & Navigasi Scroll -->
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary fs-6"></i>
                <span class="fw-bold text-dark text-xs mb-0">Pratinjau Matriks Nyata</span>
                <span id="stat_total_cells" class="badge bg-light text-primary border text-xxs fw-bold px-2 py-1">
                    0 Sel
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Tombol Cepat Geser Kiri / Kanan -->
                <div class="btn-group btn-group-sm" role="group" aria-label="Navigasi Geser">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 text-xxs d-inline-flex align-items-center gap-1 shadow-xs" id="btn_scroll_left" title="Geser tabel ke kiri">
                        <i class="bi bi-chevron-left"></i> Geser Kiri
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 text-xxs d-inline-flex align-items-center gap-1 shadow-xs" id="btn_scroll_right" title="Geser tabel ke kanan">
                        Geser Kanan <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <div class="vr mx-1 text-muted opacity-50" style="height: 18px;"></div>

                <div class="form-check form-switch mb-0 d-flex align-items-center gap-1">
                    <input class="form-check-input" type="checkbox" id="toggle_bilingual" checked>
                    <label class="form-check-label text-xxs fw-semibold text-secondary mb-0" for="toggle_bilingual">Bilingual</label>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Kontainer Preview Tabel Lebar Penuh -->
            <div id="preview_matrix_container" class="preview-table-container p-3 p-md-4">
                
                <!-- Loading State -->
                <div id="preview_loading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary mb-2" role="status" style="width: 2.4rem; height: 2.4rem;"></div>
                    <div class="text-muted small">Merender matriks grid...</div>
                </div>

                <!-- Empty State -->
                <div id="preview_empty" class="text-center py-5 text-muted">
                    <i class="bi bi-grid-3x3-gap fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <h6 class="fw-bold text-dark">Pilih Karakteristik Kolom dan Judul Baris</h6>
                    <p class="text-xs text-muted mb-0 max-w-sm mx-auto">
                        Pilih minimal <b>Karakteristik Kolom</b> dan <b>Judul Baris</b> pada form di atas untuk melihat wujud tabel secara utuh.
                    </p>
                </div>

                <!-- Konten Tabel Matriks Dinamis -->
                <div id="preview_content" class="d-none">
                    <!-- Judul Tabel di Atas Matrix -->
                    <div class="text-center mb-3">
                        <div class="fw-bold text-dark text-sm" id="table_caption_ind">
                            <?= (!empty($tabel->no_tabel) ? esc($tabel->no_tabel) . ' ' : '') . esc(format_judul_tabel($tabel->judul_ind, $tabel->periode_id ?? $tabel->periode ?? '')); ?>
                        </div>
                        <div class="fst-italic text-secondary text-xs mt-1" id="table_caption_en">
                            <?= (!empty($tabel->no_tabel) ? esc($tabel->no_tabel) . ' ' : '') . esc(format_judul_tabel($tabel->judul_en ?: $tabel->judul_ind, $tabel->periode_en ?? $tabel->periode_id ?? $tabel->periode ?? '')); ?>
                        </div>
                    </div>

                    <!-- Elemen Tabel Matriks (Scrollable Horizontal & Vertikal Mandiri) -->
                    <div class="matrix-scroll-wrapper" id="matrix_scroll_box">
                        <table class="table table-matrix align-middle mb-0" id="matrix_table_element">
                            <thead id="matrix_thead"></thead>
                            <tbody id="matrix_tbody"></tbody>
                        </table>
                    </div>

                    <!-- Footer Tabel: Sumber & Catatan -->
                    <div class="mt-3 pt-2 border-top text-xxs text-secondary d-flex flex-column gap-1">
                        <div id="preview_catatan_display" class="fst-italic" style="<?= empty($catatan_val) ? 'display: none;' : ''; ?>">
                            <b>Catatan/Note:</b> <span id="text_catatan_val"><?= esc($catatan_val); ?></span>
                        </div>
                        <div id="preview_sumber_display">
                            <b>Sumber/Source:</b> <span id="text_sumber_val"><?= esc($sumber_val ?: '-'); ?></span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT ENGINE: AJAX & REAL-TIME MATRIX GRID CALCULATOR                -->
<!-- ========================================================================= -->
<script>
$(document).ready(function() {
    let currentData = null;

    // Trigger pembaruan preview saat input dimensi berubah
    $('#select_karakteristik, #select_baris, #select_periode').on('change', function() {
        fetchDimensiData();
    });

    $('#toggle_bilingual').on('change', function() {
        if (currentData) {
            renderMatrix(currentData);
        }
    });



    // Panggil langsung saat pertama kali buka jika sudah ada konfigurasi minimal (kolom dan baris)
    if ($('#select_karakteristik').val() && $('#select_baris').val()) {
        fetchDimensiData();
    }

    /**
     * Mengambil struktur JSON dimensi dari backend
     */
    function fetchDimensiData() {
        const idKar = $('#select_karakteristik').val();
        const idBar = $('#select_baris').val();
        const idPer = $('#select_periode').val() || 0;

        // Cukup Karakteristik dan Baris yang wajib untuk memunculkan preview
        if (!idKar || !idBar) {
            $('#preview_empty').removeClass('d-none');
            $('#preview_content').addClass('d-none');
            $('#preview_loading').addClass('d-none');
            $('#stat_total_cells').text('0 Sel');
            return;
        }

        $('#preview_empty').addClass('d-none');
        $('#preview_content').addClass('d-none');
        $('#preview_loading').removeClass('d-none');

        $.ajax({
            url: '<?= base_url('admin/ajax_preview_dimensi'); ?>',
            type: 'GET',
            data: {
                id_karakteristik: idKar,
                id_baris: idBar,
                id_periode: idPer
            },
            dataType: 'json',
            success: function(res) {
                $('#preview_loading').addClass('d-none');
                if (res.status === 'success') {
                    currentData = res;
                    renderMatrix(res);
                    $('#preview_content').removeClass('d-none');
                } else {
                    Swal.fire('Error', res.message || 'Gagal memuat struktur dimensi.', 'error');
                }
            },
            error: function() {
                $('#preview_loading').addClass('d-none');
                $('#preview_empty').removeClass('d-none');
                Swal.fire('Error', 'Gagal menghubungi server untuk memuat dimensi.', 'error');
            }
        });
    }

    /**
     * Merender tabel HTML berdasar struktur hierarki kolom, baris, dan periode
     */
    function renderMatrix(data) {
        const isBilingual = $('#toggle_bilingual').is(':checked');

        const kolomStruct = data.kolom;
        const flatKolom = data.kolom ? data.kolom.flat_items : [];
        const barisItems = data.baris || [];
        const periodeItems = data.periode || [];

        // Hitung statistik sel data
        const hasPeriode = (periodeItems && periodeItems.length > 0);
        const totalLeafCols = flatKolom.length * (hasPeriode ? periodeItems.length : 1);
        const totalDataRows = barisItems.filter(b => b.tipe_baris === 'data').length;
        const totalCells = totalLeafCols * totalDataRows;
        $('#stat_total_cells').text(barisItems.length + ' Baris × ' + totalLeafCols + ' Kolom (' + totalCells + ' Sel)');

        // =====================================================================
        // 1. RENDER THEAD (MULTI-LEVEL HEADER)
        // =====================================================================
        let theadHtml = '';
        const hasGroups = kolomStruct && kolomStruct.has_groups;

        // Tentukan jumlah baris header
        let totalHeaderRows = 1;
        if (hasGroups) totalHeaderRows++;
        if (hasPeriode) totalHeaderRows++;

        // Dapatkan Judul Kelompok Baris secara Dinamis (Menyelaraskan Gambar 1 dengan Gambar 2)
        const barisKelompok = data.baris_kelompok;
        let titleBarisInd = '';
        let titleBarisEn = '';

        if (barisKelompok && barisKelompok.nama_kelompok_ind) {
            titleBarisInd = barisKelompok.nama_kelompok_ind;
            titleBarisEn = barisKelompok.nama_kelompok_en || '';
        } else {
            let optText = $('#select_baris option:selected').text().trim();
            if (optText && !optText.startsWith('--')) {
                titleBarisInd = optText.replace(/\s*\(\d+\s*baris\)$/i, '').trim();
            }
        }

        // Hilangkan prefix angka seperti "35 " jika ada, fallback ke "Kabupaten/Kota"
        let cleanTitleInd = titleBarisInd ? titleBarisInd.replace(/^\d+\s+/, '').trim() : 'Kabupaten/Kota';

        // Penanganan terjemahan bahasa Inggris otomatis jika belum diisi di database
        if (!titleBarisEn || titleBarisEn.trim() === '' || titleBarisEn === titleBarisInd) {
            let lower = cleanTitleInd.toLowerCase();
            if (lower.includes('kabupaten') && lower.includes('kota')) {
                titleBarisEn = 'Regency/Municipality';
            } else if (lower.includes('kabupaten')) {
                titleBarisEn = 'Regency';
            } else if (lower.includes('kota')) {
                titleBarisEn = 'Municipality';
            } else if (lower.includes('kecamatan')) {
                titleBarisEn = 'District';
            } else if (lower.includes('desa') || lower.includes('kelurahan')) {
                titleBarisEn = 'Village';
            } else if (lower.includes('provinsi')) {
                titleBarisEn = 'Province';
            } else {
                titleBarisEn = 'Detail / Region';
            }
        }

        // Baris Header 1 (Top Level)
        theadHtml += '<tr>';
        
        // Kolom Kiri: Judul Baris Dinamis & Sticky Freeze di Kiri (Bahasa Inggris di samping memakai /)
        theadHtml += '<th rowspan="' + totalHeaderRows + '" class="th-freeze-col text-start">';
        theadHtml += '<span>' + escapeHtml(cleanTitleInd) + '</span>';
        if (isBilingual && titleBarisEn && titleBarisEn.trim().toLowerCase() !== cleanTitleInd.trim().toLowerCase()) {
            theadHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleBarisEn) + '</span>';
        }
        theadHtml += '</th>';

        if (hasGroups) {
            // Render Kelompok Kolom Tingkat 1
            kolomStruct.groups.forEach(function(grp) {
                if (grp.type === 'group') {
                    const colspan = grp.children.length * (hasPeriode ? periodeItems.length : 1);
                    theadHtml += '<th colspan="' + colspan + '" class="th-group-col">';
                    theadHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                    if (isBilingual && grp.title_en) {
                        theadHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                    }
                    theadHtml += '</th>';
                } else {
                    // Standalone Column (misal: 'Jumlah' / 'Total')
                    const colspan = 1 * (hasPeriode ? periodeItems.length : 1);
                    const rowspan = hasPeriode ? (totalHeaderRows - 1) : totalHeaderRows;
                    theadHtml += '<th rowspan="' + rowspan + '" colspan="' + colspan + '" class="th-data-col">';
                    theadHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                    if (isBilingual && grp.title_en) {
                        theadHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                    }
                    theadHtml += '</th>';
                }
            });
            theadHtml += '</tr>';

            // Baris Header 2 (Sub-Kolom Anak)
            theadHtml += '<tr>';
            kolomStruct.groups.forEach(function(grp) {
                if (grp.type === 'group') {
                    grp.children.forEach(function(child) {
                        const colspan = hasPeriode ? periodeItems.length : 1;
                        const rowspan = 1;
                        theadHtml += '<th colspan="' + colspan + '" rowspan="' + rowspan + '" class="th-data-col">';
                        theadHtml += '<span>' + escapeHtml(child.item_ind) + '</span>';
                        if (isBilingual && child.item_en) {
                            theadHtml += '<span class="th-en">' + escapeHtml(child.item_en) + '</span>';
                        }
                        theadHtml += '</th>';
                    });
                }
            });
            theadHtml += '</tr>';

        } else {
            // Header Kolom Datar (Tanpa Grup Induk)
            flatKolom.forEach(function(item) {
                const colspan = hasPeriode ? periodeItems.length : 1;
                const rowspan = hasPeriode ? 1 : totalHeaderRows;
                theadHtml += '<th colspan="' + colspan + '" rowspan="' + rowspan + '" class="th-data-col">';
                theadHtml += '<span>' + escapeHtml(item.item_ind) + '</span>';
                if (isBilingual && item.item_en) {
                    theadHtml += '<span class="th-en">' + escapeHtml(item.item_en) + '</span>';
                }
                theadHtml += '</th>';
            });
            theadHtml += '</tr>';
        }

        // Baris Header Terbawah: Sub-Kolom Periode Waktu jika periode dipilih
        if (hasPeriode) {
            theadHtml += '<tr>';
            flatKolom.forEach(function() {
                periodeItems.forEach(function(p) {
                    theadHtml += '<th class="py-1 text-center th-periode-col" style="font-size: 0.78rem !important;">';
                    theadHtml += '<span>' + escapeHtml(p.nama_periode_ind) + '</span>';
                    if (isBilingual && p.nama_periode_en && p.nama_periode_en !== p.nama_periode_ind) {
                        theadHtml += '<span class="th-en">' + escapeHtml(p.nama_periode_en) + '</span>';
                    }
                    theadHtml += '</th>';
                });
            });
            theadHtml += '</tr>';
        }

        $('#matrix_thead').html(theadHtml);

        // =====================================================================
        // 2. RENDER TBODY (BARIS BERTINGKAT 3 LEVEL & SEL PLACEHOLDER STRIP)
        // =====================================================================
        let tbodyHtml = '';
        const placeholderSimbol = '–'; // Tanda strip standar BPS (data tidak ada)

        if (barisItems.length === 0) {
            tbodyHtml = '<tr><td colspan="' + (totalLeafCols + 1) + '" class="text-center py-4 text-muted">Belum ada baris dalam master yang dipilih.</td></tr>';
        } else {
            barisItems.forEach(function(baris) {
                const tipe = baris.tipe_baris || 'data';
                const level = parseInt(baris.level_indentasi) || 0;
                const isBold = (parseInt(baris.is_bold) === 1 || tipe === 'total' || tipe === 'kategori');
                let rowClass = 'row-' + tipe;
                let indentClass = 'indent-level-' + Math.min(level, 2);
                let textClass = isBold ? ' fw-bold text-dark' : '';

                tbodyHtml += '<tr class="' + rowClass + '">';

                // Kolom Judul Baris (Sticky Freeze di Kiri - Bahasa Inggris di samping memakai /)
                tbodyHtml += '<td class="td-freeze-col ' + indentClass + textClass + '">';
                tbodyHtml += '<span>' + escapeHtml(baris.nama_baris_ind) + '</span>';
                if (isBilingual && baris.nama_baris_en && baris.nama_baris_en.trim().toLowerCase() !== baris.nama_baris_ind.trim().toLowerCase()) {
                    tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(baris.nama_baris_en) + '</span>';
                }
                tbodyHtml += '</td>';

                // Sel Data Kolom (Seluruh sel menampilkan tanda strip standar BPS, tidak kosong bolong)
                for (let i = 0; i < totalLeafCols; i++) {
                    if (tipe === 'kategori') {
                        // Kategori Bab (Kabupaten / Kota): tanda strip di tengah
                        tbodyHtml += '<td class="cell-hatched text-center">–</td>';
                    } else {
                        // Data item, subtotal, atau total: tanda strip di tengah
                        tbodyHtml += '<td class="cell-numeric-placeholder">–</td>';
                    }
                }

                tbodyHtml += '</tr>';
            });
        }

        $('#matrix_tbody').html(tbodyHtml);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined || text === '') return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Tombol Navigasi Geser Horizontal
    $('#btn_scroll_left').on('click', function() {
        const box = document.getElementById('matrix_scroll_box');
        if (box) {
            box.scrollBy({ left: -320, behavior: 'smooth' });
        }
    });

    $('#btn_scroll_right').on('click', function() {
        const box = document.getElementById('matrix_scroll_box');
        if (box) {
            box.scrollBy({ left: 320, behavior: 'smooth' });
        }
    });

    // Fitur Drag-to-Scroll Horizontal untuk kemudahan geser tabel matriks
    const scrollBox = document.getElementById('matrix_scroll_box');
    if (scrollBox) {
        let isDown = false;
        let startX = 0;
        let scrollStartLeft = 0;

        scrollBox.addEventListener('mousedown', function(e) {
            if (e.target.closest('button, a, input, select')) return;
            isDown = true;
            scrollBox.style.cursor = 'grabbing';
            startX = e.pageX - scrollBox.offsetLeft;
            scrollStartLeft = scrollBox.scrollLeft;
        });

        scrollBox.addEventListener('mouseleave', function() {
            isDown = false;
            scrollBox.style.cursor = 'default';
        });

        scrollBox.addEventListener('mouseup', function() {
            isDown = false;
            scrollBox.style.cursor = 'default';
        });

        scrollBox.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - scrollBox.offsetLeft;
            const walk = (x - startX) * 1.5;
            scrollBox.scrollLeft = scrollStartLeft - walk;
        });
    }

    // Handler Konfirmasi SweetAlert2 saat melepas status dinamis
    $('.btn-unlink-dinamis').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        Swal.fire({
            title: 'Lepas Status Tabel Dinamis?',
            text: 'Tabel akan dikembalikan ke format non-dinamis (spreadsheet biasa). Konfigurasi dimensi akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kembalikan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});
</script>
