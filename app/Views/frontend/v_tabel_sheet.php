<?php
$display_title = $dda_info->judul_ind ?? 'Spreadsheet Data';
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

$eng_title_raw = $dda_info->judul_en ?? '';
$eng_title_raw = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $eng_title_raw);
$judul_en_only = $eng_title_raw;
if (preg_match('/^Table\s+([\d\.\w]+)\s+(.*?)(?:,\s*(\d{4}(?:-\d{4})?))?$/is', trim($eng_title_raw), $m)) {
    $judul_en_only = trim($m[2]);
    if (isset($m[3]) && $m[3]) {
        $judul_en_only .= ', ' . $m[3];
    }
}
$judul_en_only = str_replace(['?Çô', '?Cô', 'â€“', 'â€”', '–', '—', '&ndash;', '&mdash;'], '-', $judul_en_only);

$unitkerja_nm = $dda_info->unitkerja_ind ?? '-';

// Format URL for iframe to hide toolbars
$iframe_url = trim($sheet_url);
if (strpos($iframe_url, '/edit') !== false) {
    $iframe_url = str_replace('/edit', '/htmlembed', $iframe_url);
}
if (strpos($iframe_url, '?') === false && strpos($iframe_url, '#') !== false) {
    $iframe_url = str_replace('#', '?widget=false&headers=false&chrome=false&range=A3:ZZ1000#', $iframe_url);
} elseif (strpos($iframe_url, '?') !== false && strpos($iframe_url, '#') !== false) {
    $iframe_url = str_replace('#', '&widget=false&headers=false&chrome=false&range=A3:ZZ1000#', $iframe_url);
} elseif (strpos($iframe_url, '?') === false && strpos($iframe_url, '#') === false) {
    $iframe_url .= '?widget=false&headers=false&chrome=false&range=A3:ZZ1000';
} else {
    $iframe_url .= '&widget=false&headers=false&chrome=false&range=A3:ZZ1000';
}
?>
<style>
    /* Table Detail Modern Header */
    .header-modern-v2 {
        background: #ffffff; 
        padding: 12px 0 16px; 
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }
    
    .table-top-bar {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }

    .table-breadcrumb-nav .breadcrumb {
        font-size: 0.88rem;
        font-weight: 500;
        margin: 0;
        padding: 0;
        background: transparent;
        display: flex;
        align-items: center;
        flex-wrap: nowrap !important;
        gap: 6px;
        line-height: 1.5;
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
        color: #475569;
        max-width: clamp(180px, 32vw, 360px) !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: inline-block;
        vertical-align: middle;
        font-weight: 600;
        flex-shrink: 1;
    }


    /* Title Block & Table Number */
    .title-area-row {
        display: flex;
        align-items: center;
        gap: 32px;
        width: 100%;
        padding: 4px 4px 2px 4px;
    }

    .tabel-label-box {
        display: inline-flex;
        align-items: center;
        gap: 16px;
        background: transparent;
        border: none;
        border-radius: 0;
        padding: 4px 8px 4px 4px;
        flex-shrink: 0;
    }
    .tabel-label-box .tabel-ind {
        font-weight: 800; 
        font-size: 0.85rem; 
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
        font-size: 1.5rem; 
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
        padding-left: 24px;
    }

    .table-opd-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(21, 70, 121, 0.08);
        color: var(--bps-blue, #154679);
        border: 1px solid rgba(21, 70, 121, 0.16);
        border-radius: 6px;
        padding: 2px 10px;
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 6px;
        max-width: 100%;
        word-break: break-word;
    }

    .table-main-title {
        font-size: clamp(1.05rem, 1.35vw, 1.25rem);
        font-weight: 700;
        color: #0f172a;
        line-height: 1.4;
        margin: 0 0 5px 0;
        word-break: break-word;
        overflow-wrap: break-word;
        letter-spacing: -0.2px;
    }

    .table-sub-title {
        font-size: clamp(0.82rem, 1.05vw, 0.9rem);
        font-style: italic;
        font-weight: 500;
        color: #64748b;
        line-height: 1.42;
        margin: 0;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    /* Responsiveness for Header on Tablet and Mobile */
    @media (max-width: 767.98px) {
        .header-modern-v2 {
            padding: 14px 0 18px;
            margin-bottom: 18px;
        }
        .table-top-bar {
            margin-bottom: 14px;
            padding-bottom: 10px;
        }
        .table-breadcrumb-nav .breadcrumb {
            font-size: 0.8rem;
        }
        .table-breadcrumb-nav .breadcrumb-item.active {
            max-width: 140px;
        }
        .title-area-row {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        .title-details-col {
            border-left: none !important;
            padding-left: 0 !important;
            width: 100%;
        }
        .tabel-label-box {
            padding: 0;
            gap: 12px;
            background: transparent;
            border: none;
        }
        .tabel-label-box .tabel-number {
            font-size: 1.55rem;
        }
        .table-opd-badge {
            font-size: 0.76rem;
            padding: 2px 8px;
        }
    }

    @media (max-width: 420px) {
        .table-breadcrumb-nav .breadcrumb-item.active {
            max-width: none !important;
            white-space: normal !important;
            overflow: visible !important;
        }
    }

    /* Container for Sheet */
    .sheet-container {
        width: 100%;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        margin-bottom: 30px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    .iframe-wrapper {
        padding: 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .sheet-frame {
        width: 100%;
        height: 65vh;
        min-height: 480px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        overflow: hidden;
        position: relative;
    }

    @media (max-width: 767.98px) {
        .iframe-wrapper {
            padding: 8px;
        }
        .sheet-frame {
            height: 60vh;
            min-height: 420px;
            border-radius: 6px;
        }
    }

    .sheet-frame iframe {
        width: 100%;
        height: 100%;
        border: none;
        transform-origin: top left;
        display: block;
    }

    /* Skeleton Loading Overlay */
    .sheet-skeleton-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        z-index: 10;
        display: flex;
        flex-direction: column;
        padding: 0;
        box-sizing: border-box;
        transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s ease;
        overflow: hidden;
        user-select: none;
        pointer-events: none;
    }

    .sheet-skeleton-overlay.is-hidden {
        opacity: 0;
        visibility: hidden;
    }

    /* Skeleton Table Styles */
    .skeleton-table-wrapper {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        border: none;
        border-radius: 0;
        overflow: hidden;
        background: #ffffff;
    }

    .skeleton-table-row {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        gap: 16px;
    }

    .skeleton-table-row:nth-child(even) {
        background-color: #fafbfc;
    }

    .skeleton-header-row {
        background: #f1f5f9 !important;
        border-bottom: 2px solid #cbd5e1;
        padding: 12px 14px;
    }

    .skeleton-header-row .skeleton-cell .skeleton-shimmer {
        height: 15px;
        background: linear-gradient(90deg, #cbd5e1 0%, #e2e8f0 50%, #cbd5e1 100%);
        background-size: 200% 100%;
    }

    .skeleton-footer-row {
        background: #f8fafc !important;
        border-top: 2px solid #cbd5e1;
        border-bottom: none;
        margin-top: auto;
        padding: 11px 14px;
    }

    .skeleton-cell {
        display: flex;
        align-items: center;
    }

    .skeleton-cell-no {
        width: 36px;
        flex-shrink: 0;
        justify-content: center;
    }

    .skeleton-cell-name {
        width: 35%;
        min-width: 140px;
        flex-grow: 1;
    }

    .skeleton-cell-val {
        width: 18%;
        min-width: 80px;
        justify-content: flex-end;
    }

    .skeleton-cell-total {
        width: 20%;
        min-width: 90px;
        justify-content: flex-end;
    }

    /* Shimmer Animation */
    .skeleton-shimmer {
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

    .action-bar {
        padding: 14px 20px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .btn-export {
        background: var(--bps-blue, #154679);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 8px 22px;
        font-weight: 600;
        font-size: 0.88rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.25s ease;
        box-shadow: 0 2px 6px rgba(21, 70, 121, 0.2);
    }
    
    .btn-export:hover {
        background: #0f3459;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(21, 70, 121, 0.3);
    }

    @media (max-width: 576px) {
        .action-bar {
            padding: 10px 14px;
        }
        .zoom-controls {
            justify-content: center;
            width: 100%;
        }
    }
</style>

<div class="header-modern-v2">
    <div class="container-fluid" style="max-width: 1680px; margin: 0 auto; padding: 0 clamp(16px, 3vw, 36px);">
        <!-- Top Action Bar -->
        <div class="table-top-bar d-flex align-items-center">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="table-breadcrumb-nav w-100">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= base_url('/') ?>"><i class="fa-solid fa-house-chimney me-1"></i>Beranda</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= base_url('home/search') ?>">Jelajah Data</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" title="<?= htmlspecialchars($judul_only) ?>">
                        <?= htmlspecialchars($breadcrumb_label) ?>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Title Area -->
        <div class="title-area-row">
            <!-- Tabel Number Block -->
            <div class="tabel-label-box">
                <div class="d-flex flex-column align-items-center justify-content-center">
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
                <h1 class="table-main-title"><?= htmlspecialchars($judul_only) ?></h1>
                <?php if (!empty($judul_en_only)): ?>
                    <p class="table-sub-title"><?= htmlspecialchars($judul_en_only) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid" style="max-width: 1680px; margin: 0 auto; padding: 0 clamp(16px, 3vw, 36px);">
    <!-- Mobile helpful hint -->
    <div class="d-block d-md-none text-muted small mb-2 px-1">
        <i class="fa-solid fa-arrows-up-down-left-right me-1 text-primary"></i>
        <span>Geser spreadsheet ke samping atau ke bawah untuk melihat seluruh data</span>
    </div>
    <div class="sheet-container">
        <!-- Render Iframe with Spreadsheet and Skeleton Overlay -->
        <div class="iframe-wrapper">
            <div class="sheet-frame">
                <!-- Skeleton Loading Screen (Hanya Skeleton Grid Tabel Data) -->
                <div id="sheet-skeleton" class="sheet-skeleton-overlay">
                    <!-- Skeleton Mock Table -->
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
                        $widths_name  = [72, 85, 60, 90, 78, 68, 82, 75, 88, 70];
                        $widths_val1  = [55, 70, 48, 65, 58, 72, 60, 52, 68, 62];
                        $widths_val2  = [60, 45, 68, 52, 64, 48, 62, 58, 50, 66];
                        $widths_val3  = [50, 62, 55, 70, 45, 66, 58, 60, 52, 58];
                        $widths_total = [65, 58, 72, 62, 68, 54, 70, 64, 75, 60];
                        for ($k = 0; $k < 10; $k++): 
                        ?>
                        <div class="skeleton-table-row">
                            <div class="skeleton-cell skeleton-cell-no"><span class="skeleton-shimmer" style="width: 24px;"></span></div>
                            <div class="skeleton-cell skeleton-cell-name">
                                <span class="skeleton-shimmer" style="width: <?= $widths_name[$k] ?>%;"></span>
                            </div>
                            <div class="skeleton-cell skeleton-cell-val">
                                <span class="skeleton-shimmer" style="width: <?= $widths_val1[$k] ?>%;"></span>
                            </div>
                            <div class="skeleton-cell skeleton-cell-val">
                                <span class="skeleton-shimmer" style="width: <?= $widths_val2[$k] ?>%;"></span>
                            </div>
                            <div class="skeleton-cell skeleton-cell-val d-none d-md-flex">
                                <span class="skeleton-shimmer" style="width: <?= $widths_val3[$k] ?>%;"></span>
                            </div>
                            <div class="skeleton-cell skeleton-cell-total">
                                <span class="skeleton-shimmer" style="width: <?= $widths_total[$k] ?>%;"></span>
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

                <iframe id="sheet-iframe" src="<?= htmlspecialchars($iframe_url) ?>" allowfullscreen onload="hideSkeleton()"></iframe>
            </div>
        </div>
        
        <div class="action-bar">
            <div class="zoom-controls d-flex align-items-center gap-2">
                <span class="text-muted small fw-bold me-1">Ukuran Teks:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="zoomTabel(-0.1)" title="Perkecil" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; padding: 0;">
                    <i class="fa-solid fa-minus"></i>
                </button>
                <span id="zoom-level-text" class="fw-bold" style="min-width: 45px; text-align: center;">100%</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="zoomTabel(0.1)" title="Perbesar" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; padding: 0;">
                    <i class="fa-solid fa-plus"></i>
                </button>
                <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="resetZoom()" title="Reset">Reset</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentZoom = 1.0;
    const iframeEl = document.getElementById('sheet-iframe');
    const zoomText = document.getElementById('zoom-level-text');
    let skeletonDismissed = false;

    function hideSkeleton() {
        if (skeletonDismissed) return;
        const skeletonEl = document.getElementById('sheet-skeleton');
        if (skeletonEl) {
            skeletonDismissed = true;
            skeletonEl.classList.add('is-hidden');
            setTimeout(function() {
                skeletonEl.style.display = 'none';
            }, 480);
        }
    }

    if (iframeEl) {
        iframeEl.addEventListener('load', function() {
            setTimeout(hideSkeleton, 300);
        });

        // Safety fallback in case iframe event is delayed or completed earlier
        setTimeout(hideSkeleton, 6500);
    }
    
    function updateZoom() {
        if (currentZoom < 0.5) currentZoom = 0.5;
        if (currentZoom > 2.0) currentZoom = 2.0;
        
        zoomText.innerText = Math.round(currentZoom * 100) + '%';
        
        // Kombinasi transform scale dan penyesuaian ukuran kotak
        // agar iframe secara fisik tidak membesar keluar batas (frame putih tetap)
        // namun isi tabel yang dirender Google membesar & mengisi ruang kosong.
        const scaledSize = (100 / currentZoom).toFixed(3) + '%';
        
        iframeEl.style.transform = `scale(${currentZoom})`;
        iframeEl.style.width = scaledSize;
        iframeEl.style.height = scaledSize;
    }
    
    function zoomTabel(delta) {
        currentZoom += delta;
        updateZoom();
    }
    
    function resetZoom() {
        currentZoom = 1.0;
        updateZoom();
    }
</script>
