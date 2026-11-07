<?php
/**
 * PARTIAL VIEW: PRATINJAU IMPOR DATA TABEL DINAMIS (preview_upload_dinamis.php)
 * Menampilkan ringkasan 3 metrik esensial dan matriks tabel interaktif sebelum data dikomit ke database.
 * Bersih, formal, dan bebas dari ikon dekoratif yang tidak perlu.
 */

$formattedSize = ($file_size > 1048576) 
    ? number_format($file_size / 1048576, 2, ',', '.') . ' MB' 
    : number_format($file_size / 1024, 1, ',', '.') . ' KB';
?>

<div class="preview-upload-dinamis-wrapper">
    <style>
        /* ==========================================================================
           STYLING PRATINJAU TABEL UPLOAD DINAMIS
           Mengadopsi format minimalis, bersih & elegan dari Preview Konfigurasi Tabel
           ========================================================================== */
        .preview-matrix-scroll-wrapper {
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px !important;
        }

        .preview-matrix-scroll-wrapper table,
        .preview-matrix-scroll-wrapper .main-table,
        .preview-matrix-scroll-wrapper .table {
            font-size: 11px !important;
            width: 100% !important;
            border-collapse: collapse !important;
            background: #ffffff !important;
            margin-bottom: 0 !important;
            border: 1px solid #dee2e6 !important;
        }

        .preview-matrix-scroll-wrapper th,
        .preview-matrix-scroll-wrapper td {
            padding: 5px 8px !important;
            font-size: 11px !important;
            border: 1px solid #dee2e6 !important;
            vertical-align: middle !important;
        }

        /* THEAD HEADER: Bersih berlatar putih, teks gelap, border halus (Bukan Orange/Biru) */
        .preview-matrix-scroll-wrapper thead {
            background: #ffffff !important;
        }

        .preview-matrix-scroll-wrapper thead th {
            position: sticky;
            top: 0;
            z-index: 15;
            background: #ffffff !important;
            color: #212529 !important;
            font-weight: 700 !important;
            text-align: center !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
        }

        .preview-matrix-scroll-wrapper thead th div {
            color: #212529 !important;
            font-weight: 700 !important;
        }

        .preview-matrix-scroll-wrapper thead th i,
        .preview-matrix-scroll-wrapper thead th small,
        .preview-matrix-scroll-wrapper thead th .header-subtitle {
            color: #6c757d !important;
            font-size: 9.5px !important;
            font-weight: normal !important;
            font-style: italic !important;
            opacity: 1 !important;
        }

        /* BARIS NOMOR URUT KOLOM: (1), (2), (3)... Latar Putih & Teks Abu-abu */
        .preview-matrix-scroll-wrapper thead tr.num-row th {
            background: #ffffff !important;
            color: #6c757d !important;
            font-weight: normal !important;
            border: 1px solid #dee2e6 !important;
            font-size: 9.5px !important;
            padding: 3px 6px !important;
        }

        /* KOLOM STICKY KIRI: Wilayah & Nomor */
        .preview-matrix-scroll-wrapper table thead th.col-nomor,
        .preview-matrix-scroll-wrapper table thead th.col-wilayah {
            position: sticky;
            left: 0;
            z-index: 25;
            background-color: #ffffff !important;
            color: #212529 !important;
            border: 1px solid #dee2e6 !important;
        }

        .preview-matrix-scroll-wrapper table tbody td.col-nomor,
        .preview-matrix-scroll-wrapper table tbody td.col-wilayah {
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #ffffff !important;
            color: #212529 !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: 1px 0 0 #dee2e6;
        }

        /* BARIS KATEGORI (misal: Kabupaten / Regency) */
        .preview-matrix-scroll-wrapper .category-row td,
        .preview-matrix-scroll-wrapper tr.category-row td {
            background: #f8fafc !important;
            color: #334155 !important;
            font-weight: bold !important;
            border: 1px solid #dee2e6 !important;
            padding: 6px 10px !important;
        }

        /* BARIS TOTAL / GRAND TOTAL */
        .preview-matrix-scroll-wrapper .grand-total-row td,
        .preview-matrix-scroll-wrapper tr.total-row td {
            background: #f8fafc !important;
            color: #212529 !important;
            font-weight: bold !important;
            border: 1px solid #dee2e6 !important;
        }
        .preview-matrix-scroll-wrapper tr.total-row td.col-wilayah {
            background: #f8fafc !important;
            color: #212529 !important;
        }

        /* ==========================================================================
           PEWARNAAN STATUS SEL DATA (HIJAU, ABU-ABU, KUNING)
           ========================================================================== */
        /* Sel Terisi Data Baru (Hijau Muda Segar) */
        .preview-matrix-scroll-wrapper tbody td.cell-filled-new {
            background-color: #dcfce7 !important;
            color: #15803d !important;
            font-weight: 600 !important;
            border: 1px solid #bbf7d0 !important;
        }

        /* Sel Berubah Nilai (Kuning dengan Border Amber) */
        .preview-matrix-scroll-wrapper tbody td.cell-modified {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            font-weight: 700 !important;
            border: 1.5px solid #f59e0b !important;
        }

        /* Sel Kosong / Tidak Diisi (Abu-abu Lembut) */
        .preview-matrix-scroll-wrapper tbody td.cell-empty {
            background-color: #f8fafc !important;
            color: #94a3b8 !important;
            border: 1px solid #dee2e6 !important;
            text-align: center !important;
        }

        /* Sel Tetap / Nilai Sama dengan Database (Putih) */
        .preview-matrix-scroll-wrapper tbody td.cell-same {
            background-color: #ffffff !important;
            color: #334155 !important;
            border: 1px solid #dee2e6 !important;
        }

        .preview-matrix-scroll-wrapper .table-responsive-dda {
            border: none !important;
            border-radius: 0 !important;
            margin-bottom: 0 !important;
            background: #ffffff !important;
        }
    </style>
    <!-- Header Informasi Berkas & Status -->
    <div class="p-3 mb-3 bg-light rounded-3 border" style="border-color: #e2e8f0 !important;">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
                <div class="fw-bold text-dark" style="font-size: 1rem; line-height: 1.4;">
                    <?= esc($tabel->judul_ind); ?>
                </div>
                <div class="text-secondary small mt-1" style="font-size: 0.8rem;">
                    <span>Berkas: <b><?= esc($file_name); ?></b> (<?= $formattedSize; ?>)</span>
                    <span class="mx-2">&bull;</span>
                    <span>Tahun Data: <b><?= esc($tahun_data); ?></b></span>
                    <?php if (!empty($indikator->satuan)): ?>
                        <span class="mx-2">&bull;</span>
                        <span>Satuan: <b><?= esc($indikator->satuan); ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-md-end flex-shrink-0">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 fw-semibold" style="font-size: 0.82rem;">
                    Struktur 100% sesuai master
                </span>
            </div>
        </div>
    </div>

    <!-- 3 Kartu Metrik Cepat -->
    <div class="row g-3 mb-3">
        <div class="col-4">
            <div class="p-3 rounded-3 border bg-white text-center shadow-xs" style="border-color: #e2e8f0 !important;">
                <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Sel Data</div>
                <div class="h3 fw-bold text-dark mb-0 mt-1"><?= number_format($summary['total_cells'] ?? 0, 0, ',', '.'); ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 rounded-3 border bg-white text-center shadow-xs" style="border-color: #e2e8f0 !important;">
                <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sel Terisi</div>
                <div class="h3 fw-bold text-success mb-0 mt-1">
                    <?= number_format($summary['filled_cells'] ?? 0, 0, ',', '.'); ?>
                    <?php if (!empty($hasExistingData) && (int)($summary['modified_cells'] ?? 0) > 0): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" style="font-size: 0.68rem; vertical-align: middle;">
                            <?= (int)$summary['modified_cells']; ?> diubah
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 rounded-3 border bg-white text-center shadow-xs" style="border-color: #e2e8f0 !important;">
                <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sel Kosong</div>
                <div class="h3 fw-bold <?= ((int)($summary['empty_cells'] ?? 0) > 0) ? 'text-warning' : 'text-secondary'; ?> mb-0 mt-1"><?= number_format($summary['empty_cells'] ?? 0, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($hasExistingData) && (int)($summary['modified_cells'] ?? 0) > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3 border-0 rounded-3 shadow-xs" style="background: #fefce8; color: #854d0e; border: 1px solid #fde047 !important; font-size: 0.85rem;">
            <i class="bi bi-pencil-square fs-5 flex-shrink-0 text-warning"></i>
            <div>
                <strong>Mode Pemutakhiran Data:</strong> Terdeteksi <strong><?= number_format($summary['modified_cells'], 0, ',', '.'); ?> sel nilai diubah</strong> dari data database sebelumnya (disorot warna kuning).
            </div>
        </div>
    <?php elseif (!empty($hasExistingData) && (int)($summary['modified_cells'] ?? 0) === 0 && (int)($summary['filled_cells'] ?? 0) > 0): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3 py-2 px-3 border-0 rounded-3 shadow-xs" style="background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd !important; font-size: 0.85rem;">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 text-info"></i>
            <div>
                <strong>Informasi:</strong> Seluruh nilai pada berkas ini identik (sama persis) dengan data yang telah tersimpan di database. Tidak ada nilai yang berubah.
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int)($summary['filled_cells'] ?? 0) === 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3 border-0 rounded-3 shadow-xs" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a !important; font-size: 0.85rem;">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 text-warning"></i>
            <div>
                <strong>Seluruh sel data masih kosong (0 sel terisi).</strong> Berkas ini belum berisi angka. Silakan isi data terlebih dahulu pada kolom data berkas template Excel sebelum menyimpan ke database.
            </div>
        </div>
    <?php endif; ?>

    <!-- Kontainer Matriks Tabel dengan Sticky Header & Sticky Kolom -->
    <div class="preview-matrix-scroll-wrapper mb-3" style="max-height: 460px; overflow: auto; border: 1px solid #dee2e6; border-radius: 8px; background: #ffffff;">
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
            'available_years'   => [$tahun_data],
            'selected_years'    => [$tahun_data],
            'config_tampilan'   => $config_tampilan ?? [],
            'is_admin'          => false,
            'isImportPreview'   => true,
            'isPreview'         => true,
            'hasExistingData'   => !empty($hasExistingData),
            'matrix_dom_id'     => 'preview-dinamis-matrix-table',
        ]); ?>
    </div>

    <!-- Keterangan Pewarnaan Sel -->
    <div class="d-flex flex-wrap align-items-center gap-3 px-1 mb-3" style="font-size: 0.82rem;">
        <span class="text-muted fw-semibold">Keterangan:</span>
        <span class="d-inline-flex align-items-center gap-1.5 text-secondary">
            <span style="display: inline-block; width: 14px; height: 14px; background: #dcfce7; border: 1.5px solid #86efac; border-radius: 3px;"></span>
            <span>Hijau: Nilai diisi (data dari berkas Excel)</span>
        </span>
        <span class="d-inline-flex align-items-center gap-1.5 text-secondary">
            <span style="display: inline-block; width: 14px; height: 14px; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 3px;"></span>
            <span>Abu-abu: Tidak diisi (sel kosong / tanda &mdash;)</span>
        </span>
        <?php if (!empty($hasExistingData)): ?>
            <span class="d-inline-flex align-items-center gap-1.5 text-secondary">
                <span style="display: inline-block; width: 14px; height: 14px; background: #fef3c7; border: 1.5px solid #f59e0b; border-radius: 3px;"></span>
                <span>Kuning: Perubahan nilai (berbeda dari database)</span>
            </span>
            <span class="d-inline-flex align-items-center gap-1.5 text-secondary">
                <span style="display: inline-block; width: 14px; height: 14px; background: #ffffff; border: 1.5px solid #dee2e6; border-radius: 3px;"></span>
                <span>Putih: Nilai tetap (sama dengan database)</span>
            </span>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex align-items-center justify-content-between pt-3 border-top" style="border-color: #e2e8f0 !important;">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 text-xs fw-semibold" id="btnPreviewBackToFile">
            Ganti Berkas
        </button>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-light border rounded-pill px-4 py-2 text-xs fw-semibold text-secondary" data-bs-dismiss="modal">
                Batal
            </button>
            <button type="button" class="btn btn-success rounded-pill px-4 py-2 text-xs fw-semibold shadow-xs" id="btnPreviewConfirmCommit" data-token="<?= esc($upload_token); ?>" <?= ((int)($summary['filled_cells'] ?? 0) === 0) ? 'disabled style="background: #94a3b8 !important; border-color: #94a3b8 !important; cursor: not-allowed;" title="Tidak ada sel terisi untuk disimpan"' : 'style="background: #059669 !important; border-color: #059669 !important;"'; ?>>
                Simpan ke Database
            </button>
        </div>
    </div>
</div>
