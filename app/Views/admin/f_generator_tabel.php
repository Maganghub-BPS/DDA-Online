
<?php
/**
 * VIEW: GENERATOR INDIKATOR TABEL & LIVE MATRIX PREVIEW (f_generator_tabel.php)
 * Modul Sprint 3: Perakitan Data Cube & Pratinjau Matriks Dinamis BPS Jateng
 * Layout: Top-Bottom Stacked (Form di Atas, Live Matrix Preview Lebar Penuh di Bawah)
 */
$is_dynamic = (int)($tabel->is_dynamic ?? 0);
$total_nilai = (int)($total_nilai ?? 0);
$is_locked = $total_nilai > 0;
$id_indikator_existing = $indikator->id ?? null;
$sel_karakteristik = $indikator->id_karakteristik_kelompok ?? '';
$sel_baris = $indikator->id_baris_kelompok ?? '';
$sel_periode = $indikator->id_periode_kelompok ?? '';
$sel_posisi_hirarki = $indikator->posisi_hirarki ?? 'kolom_atas';
$sel_tingkat_kolom = $indikator->tingkat_kolom ?? 'semua';
$sel_konfigurasi_total = $indikator->konfigurasi_total ?? 'default';
$sel_label_total_kustom = $indikator->label_total_kustom ?? '';
$sel_format_nomor_baris = $indikator->format_nomor_baris ?? 'auto';
$sel_tampilan_baris = $indikator->tampilan_baris ?? '1_kolom';
$sumber_val = !empty($tabel->unitkerja_ind) ? 'Dinas/Instansi ' . $tabel->unitkerja_ind : 'BPS Provinsi Jawa Tengah';
$catatan_val = $indikator->catatan ?? '';
?>

<!-- Select2 CSS & JS (Bootstrap 5 Theme) untuk Fitur Pencarian Judul Baris & Karakteristik Kolom -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

    /* Kolom Pertama (Kabupaten/Kota/Baris) di Kiri (Non-Sticky / Normal) */
    .th-freeze-col {
        background-color: #f8fafc !important;
        min-width: 220px !important;
        vertical-align: middle !important;
    }
    .table-matrix tbody td.td-freeze-col {
        background-color: #ffffff !important;
        min-width: 220px !important;
    }
    .row-kategori td.td-freeze-col {
        background-color: #f8fafc !important;
    }
    .row-subtotal td.td-freeze-col {
        background-color: #f9a066 !important;
        color: #000000 !important;
    }
    .row-total td.td-freeze-col {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        font-weight: 800 !important;
    }
    .table-matrix tbody tr:hover td.td-freeze-col {
        background-color: #f1f5f9 !important;
    }
    .table-matrix tbody tr.row-subtotal:hover td.td-freeze-col {
        background-color: #f9a066 !important;
    }

    /* Kolom Kedua (Periode Waktu atau Multi-Kolom Baris) */
    .th-freeze-col-2 {
        background-color: #f8fafc !important;
        min-width: 100px !important;
        vertical-align: middle !important;
        border-right: 2px solid #cbd5e1 !important;
    }
    .table-matrix tbody td.td-freeze-col-2 {
        background-color: #ffffff !important;
        min-width: 100px !important;
        vertical-align: middle !important;
        border-right: 2px solid #cbd5e1 !important;
    }
    .row-subtotal td.td-freeze-col-2 {
        background-color: #f9a066 !important;
        color: #000000 !important;
    }
    .row-total td.td-freeze-col-2 {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        font-weight: 800 !important;
    }
    .table-matrix tbody tr:hover td.td-freeze-col-2 {
        background-color: #f1f5f9 !important;
    }
    .table-matrix tbody tr.row-subtotal:hover td.td-freeze-col-2 {
        background-color: #f9a066 !important;
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
        background-color: #f9a066 !important;
        font-weight: 700 !important;
        color: #000000 !important;
        border: 1px solid #ffffff !important;
    }
    .table-matrix tbody tr.row-subtotal:hover td {
        background-color: #f9a066 !important;
    }
    .row-subtotal .cell-numeric-placeholder {
        color: #000000 !important;
        font-weight: 700 !important;
    }
    .row-total td {
        background-color: #fffbeb !important;
        font-weight: 800 !important;
        color: #92400e !important;
        border-top: 2px solid #f59e0b !important;
        border-bottom: 3px double #d97706 !important;
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

    /* ===== TOMBOL AKSI TOOLBAR ATAS (SERAGAM, INTERAKTIF, JELAS TAMPILAN TOMBOLNYA) ===== */
    .btn-top-action {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 6px 14px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        border-radius: 50px !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        cursor: pointer !important;
        line-height: 1.25 !important;
        transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1) !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
    }
    .btn-top-action:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15) !important;
        text-decoration: none !important;
    }
    .btn-top-action:active {
        transform: translateY(0) !important;
    }

    /* 1. View Tabel Dinamis: Biru BPS */
    .btn-top-blue {
        background-color: #0284c7 !important;
        border: 1px solid #0369a1 !important;
        color: #ffffff !important;
    }
    .btn-top-blue:hover {
        background-color: #0369a1 !important;
        border-color: #075985 !important;
        color: #ffffff !important;
    }

    /* 2. Spreadsheet: Hijau Sheet */
    .btn-top-green {
        background-color: #059669 !important;
        border: 1px solid #047857 !important;
        color: #ffffff !important;
    }
    .btn-top-green:hover {
        background-color: #047857 !important;
        border-color: #065f46 !important;
        color: #ffffff !important;
    }

    /* 3. Edit Master: Abu-abu Slate */
    .btn-top-slate {
        background-color: #475569 !important;
        border: 1px solid #334155 !important;
        color: #ffffff !important;
    }
    .btn-top-slate:hover {
        background-color: #334155 !important;
        border-color: #1e293b !important;
        color: #ffffff !important;
    }

    /* ===== SELECT2 CUSTOM OVERRIDES SESUAI TEMA BPS ORANGE ===== */
    .select2-container--bootstrap-5 .select2-selection {
        font-size: 0.85rem !important;
        min-height: 38px !important;
        height: 38px !important;
        display: flex !important;
        align-items: center !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        background-color: #ffffff !important;
        transition: all 0.2s ease !important;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #FF6D1F !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 109, 31, 0.18) !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        color: #1e293b !important;
        line-height: normal !important;
        padding-left: 0.75rem !important;
        padding-right: 2rem !important;
        font-weight: 500 !important;
        font-size: 0.84rem !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important;
        font-size: 0.84rem !important;
    }
    .select2-dropdown {
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08) !important;
        overflow: hidden !important;
        z-index: 1060 !important;
    }
    .select2-search--dropdown {
        padding: 8px !important;
        background: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
    .select2-search__field {
        border-radius: 6px !important;
        padding: 0.45rem 0.75rem !important;
        font-size: 0.84rem !important;
        border: 1px solid #cbd5e1 !important;
    }
    .select2-search__field:focus {
        border-color: #FF6D1F !important;
        outline: none !important;
        box-shadow: 0 0 0 0.15rem rgba(255, 109, 31, 0.2) !important;
    }
    .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.83rem !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #FF6D1F !important;
        color: #ffffff !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--selected {
        background-color: #fff4eb !important;
        color: #c2410c !important;
        font-weight: 600 !important;
    }

    /* ===== TINGKAT KOLOM PILLS (CHECKBOX ELEGAN & INTERAKTIF) ===== */
    .tingkat-pill-item {
        cursor: pointer;
        margin: 0;
        user-select: none;
        display: inline-flex;
    }
    .tingkat-pill-item input[type="checkbox"] {
        display: none;
    }
    .tingkat-pill-content {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 600;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #64748b;
        transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .tingkat-pill-content:hover {
        border-color: #94a3b8;
        background: #f8fafc;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .tingkat-pill-item input[type="checkbox"]:checked + .tingkat-pill-content {
        border-color: #FF6D1F;
        background: #FFF5ED;
        color: #c2410c;
        box-shadow: 0 2px 8px rgba(255, 109, 31, 0.22);
    }
    .tingkat-pill-item .icon-checked {
        display: none;
        color: #FF6D1F;
        font-size: 0.85rem;
    }
    .tingkat-pill-item .icon-unchecked {
        display: inline-block;
        color: #94a3b8;
        font-size: 0.85rem;
    }
    .tingkat-pill-item input[type="checkbox"]:checked + .tingkat-pill-content .icon-checked {
        display: inline-block;
    }
    .tingkat-pill-item input[type="checkbox"]:checked + .tingkat-pill-content .icon-unchecked {
        display: none;
    }
    .tingkat-pill-item input[type="checkbox"]:disabled + .tingkat-pill-content {
        opacity: 0.65;
        cursor: not-allowed !important;
        pointer-events: none !important;
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    /* ===== POSISI HIRARKI & TAMPILAN BARIS: SEGMENTED SWITCHER ===== */
    .posisi-segmented-group {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 50px;
        border: 1px solid #cbd5e1;
    }
    .btn-segmented {
        border: none;
        background: transparent;
        padding: 4px 11px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #475569;
        border-radius: 50px;
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-segmented i {
        color: #0284c7;
        transition: color 0.2s ease;
    }
    .btn-segmented:hover:not(.active) {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.6);
    }
    .btn-segmented.active {
        background: #ffffff !important;
        color: #FF6D1F !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08) !important;
        font-weight: 700 !important;
    }
    .btn-segmented.active i {
        color: #FF6D1F !important;
    }
    .posisi-segmented-group.disabled {
        opacity: 0.55;
        pointer-events: none;
        background: #e2e8f0;
        border-color: #cbd5e1;
        cursor: not-allowed;
    }
    .posisi-segmented-group.disabled .btn-segmented,
    .btn-segmented:disabled {
        cursor: not-allowed !important;
        pointer-events: none !important;
        opacity: 0.65;
    }
    .posisi-segmented-group.disabled .btn-segmented.active {
        background: #e2e8f0;
        color: #94a3b8;
        box-shadow: none;
    }
    .toolbar-setting-item.disabled-posisi {
        opacity: 0.7;
    }

    /* ===== SUB-PILIHAN HEADER BARIS (GABUNG VS PISAH) ===== */
    .sub-baris-group {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 50px;
        border: 1px solid #cbd5e1;
        transition: all 0.2s ease;
    }
    .btn-sub-baris {
        border: none;
        background: transparent;
        padding: 3px 10px;
        font-size: 0.76rem;
        font-weight: 600;
        color: #64748b;
        border-radius: 50px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .btn-sub-baris:hover:not(.active) {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.6);
    }
    .btn-sub-baris.active {
        background: #ffffff;
        color: #FF6D1F;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    /* ===== TOOLBAR PENGATURAN MATRIKS (TINGKAT, POSISI, TOTAL) ===== */
    .toolbar-setting-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .toolbar-setting-label {
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        color: #64748b;
        text-transform: uppercase;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
    }
    .toolbar-divider {
        width: 1px;
        height: 22px;
        background-color: #cbd5e1;
        opacity: 0.7;
        margin: 0 4px;
    }
    .matrix-settings-ribbon {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 14px;
    }
    .matrix-settings-ribbon.is-locked {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    .toolbar-setting-item.is-locked {
        opacity: 0.55;
        pointer-events: none;
        cursor: not-allowed;
    }
    @media (max-width: 991px) {
        .toolbar-divider {
            display: none;
        }
    }

    /* ===== CUSTOM TOTAL DROPDOWN MODERN (BPS STYLE - PENGGANTI UI GAMBAR 3) ===== */
    .custom-total-dropdown {
        position: relative;
        display: inline-block;
    }
    .btn-total-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 50px;
        padding: 4px 14px 4px 14px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
        user-select: none;
        white-space: nowrap;
        outline: none;
    }
    .btn-total-toggle:hover:not(:disabled) {
        background: #ffffff;
        border-color: #94a3b8;
        box-shadow: 0 2px 5px rgba(0,0,0,0.04);
    }
    .btn-total-toggle:disabled,
    .btn-total-toggle.disabled {
        opacity: 0.65;
        cursor: not-allowed !important;
        pointer-events: none !important;
        background: #e2e8f0;
        border-color: #cbd5e1;
    }
    .custom-total-dropdown.open .btn-total-toggle,
    .btn-total-toggle:focus {
        background: #ffffff;
        border-color: #FF6D1F;
        box-shadow: 0 0 0 3px rgba(255, 109, 31, 0.15);
        color: #FF6D1F;
    }
    .toggle-chevron {
        font-size: 0.72rem;
        color: #64748b;
        transition: transform 0.2s ease;
    }
    .custom-total-dropdown.open .toggle-chevron {
        transform: rotate(180deg);
        color: #FF6D1F;
    }
    .custom-total-menu {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 1050;
        min-width: 220px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 6px 10px -6px rgba(0, 0, 0, 0.05);
        padding: 5px;
        margin: 0;
        list-style: none;
        display: none;
        animation: fadeInMenu 0.15s ease-out;
    }
    @keyframes fadeInMenu {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .custom-total-dropdown.open .custom-total-menu {
        display: block;
    }
    .custom-total-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 7px 11px;
        border-radius: 6px;
        font-size: 0.79rem;
        font-weight: 500;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .custom-total-item:hover {
        background: #f8fafc;
        color: #0f172a;
    }
    .custom-total-item.active {
        background: #FFF5ED;
        color: #FF6D1F;
        font-weight: 600;
    }
    .custom-total-item .item-check {
        font-size: 0.85rem;
        color: #FF6D1F;
        display: none;
    }
    .custom-total-item.active .item-check {
        display: inline-block;
    }
    .custom-total-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 4px 5px;
    }
    .custom-total-input-wrap {
        padding: 4px 6px 6px 6px;
        margin-top: 2px;
        animation: fadeInMenu 0.15s ease-out;
    }
    .total-popover-input {
        font-size: 0.78rem !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px 0 0 6px !important;
        padding: 4px 9px !important;
        color: #1e293b !important;
        background: #ffffff !important;
    }
    .total-popover-input:focus {
        border-color: #FF6D1F !important;
        box-shadow: 0 0 0 2px rgba(255, 109, 31, 0.15) !important;
    }
    .btn-total-apply {
        background: #FF6D1F !important;
        border-color: #FF6D1F !important;
        color: #ffffff !important;
        border-radius: 0 6px 6px 0 !important;
        padding: 4px 10px !important;
        font-size: 0.8rem !important;
    }
    .btn-total-apply:hover {
        background: #e05d15 !important;
    }
    #btn_unlock_reset_data:hover {
        background: #fef2f2 !important;
        border-color: #f87171 !important;
        color: #b91c1c !important;
    }
</style>

<div class="container-fluid py-2 px-0">
    <!-- ===== COMPACT HEADER & NAVIGASI ATAS ===== -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
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

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= base_url('admin/view_tabel_dinamis/' . $tabel->id_tahun_tabel); ?>" class="btn-top-action btn-top-blue shadow-xs" title="Buka Pratinjau Tampilan Resmi Publikasi DDA">
                <i class="bi bi-eye-fill"></i> View Tabel Dinamis
            </a>
            <?php if (!empty($tabel->link_tabel)): ?>
                <a href="<?= esc($tabel->link_tabel); ?>" target="_blank" class="btn-top-action btn-top-green shadow-xs" title="Buka Spreadsheet Sumber Google Drive">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Spreadsheet
                </a>
            <?php endif; ?>
            <a href="<?= base_url('admin/master_tabel/edt/' . $tabel->id_tahun_tabel . '/1'); ?>" class="btn-top-action btn-top-slate shadow-xs" title="Buka Form Edit Master Tabel DDA">
                <i class="bi bi-pencil-square"></i> Edit Master
            </a>
        </div>
    </div>

    <!-- ===== FORMULIR KONFIGURASI DIMENSI (CARD MODERN & ELEGAN DENGAN SEARCHABLE SELECT2) ===== -->
    <div class="card border-0 shadow-sm generator-card mb-3" style="border: 1px solid #e2e8f0 !important; background: #ffffff;">
        <div class="card-body p-3 p-md-4">

            <?php if ($is_locked): ?>
                <!-- SLIM SAFETY LOCK BAR -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 mb-3 rounded-2" 
                     style="background: #fffbeb; border: 1px solid #fde68a;">
                    <div class="d-flex align-items-center gap-2 text-xs">
                        <i class="bi bi-lock-fill text-warning fs-6"></i>
                        <span class="fw-bold text-dark">Dimensi Terkunci</span>
                        <span class="badge bg-white text-secondary border px-2 py-0.5 rounded-pill fw-normal text-xxs">
                            <?= number_format($total_nilai, 0, ',', '.'); ?> nilai terisi
                        </span>
                        <span class="text-muted d-none d-md-inline text-xxs">
                            — Buka kunci untuk mengatur ulang dimensi (data nilai akan direset).
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm text-danger bg-white border border-danger-subtle rounded-pill text-xs fw-semibold d-inline-flex align-items-center gap-1 shadow-xs" id="btn_unlock_reset_data" style="padding: 4px 12px; border-color: #fca5a5 !important;" title="Hapus data nilai untuk membuka kunci dimensi">
                        <i class="bi bi-unlock-fill"></i> Buka Kunci & Reset Data
                    </button>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('admin/save_generator_tabel'); ?>" method="post" id="formGenerator">
                <input type="hidden" name="id_tahun_tabel" value="<?= $tabel->id_tahun_tabel; ?>">
                <input type="hidden" name="posisi_hirarki" id="input_posisi_hirarki" value="<?= esc($sel_posisi_hirarki); ?>">
                <input type="hidden" name="tingkat_kolom" id="input_tingkat_kolom" value="<?= esc($sel_tingkat_kolom); ?>">

                <?php if ($is_locked): ?>
                    <!-- Hidden inputs untuk mempertahankan dimensi yang terkunci saat form disimpan -->
                    <input type="hidden" name="id_baris_kelompok" value="<?= $sel_baris; ?>">
                    <input type="hidden" name="id_karakteristik_kelompok" value="<?= $sel_karakteristik; ?>">
                    <input type="hidden" name="id_periode_kelompok" value="<?= $sel_periode; ?>">
                <?php endif; ?>

                <!-- BARIS 1: 3 DIMENSI UTAMA DENGAN SEARCH SELECT2 -->
                <div class="row g-3">
                    <!-- 1. Judul Baris -->
                    <div class="col-lg-4 col-md-12">
                        <label class="form-label text-xs fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                            <span>
                                <i class="bi bi-list-nested text-primary me-1"></i> Judul Baris
                                <?php if ($is_locked): ?>
                                    <i class="bi bi-lock-fill text-secondary ms-1 text-xxs" title="Dimensi terkunci"></i>
                                <?php endif; ?>
                            </span>
                            <span class="badge bg-light text-secondary border text-xxs fw-normal"><?= count($list_baris); ?> Master</span>
                        </label>
                        <select name="id_baris_kelompok" id="select_baris" class="form-select form-select-sm" <?= $is_locked ? 'disabled' : ''; ?> data-placeholder="Cari atau pilih Judul Baris...">
                            <option value="">-- Tanpa Dimensi Baris --</option>
                            <?php foreach ($list_baris as $b): ?>
                                <option value="<?= $b->id; ?>" <?= ($sel_baris == $b->id) ? 'selected' : ''; ?>
                                    data-judul-ind="<?= esc($b->judul_baris_ind ?? $b->nama_kelompok_ind); ?>"
                                    data-judul-en="<?= esc($b->judul_baris_en ?? $b->nama_kelompok_en ?? ''); ?>"
                                    data-judul-sub-ind="<?= esc($b->judul_sub_baris_ind ?? ''); ?>"
                                    data-judul-sub-en="<?= esc($b->judul_sub_baris_en ?? ''); ?>">
                                    <?= esc($b->nama_kelompok_ind); ?> (<?= (int)$b->total_items; ?> baris)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 2. Karakteristik Kolom -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label text-xs fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                            <span>
                                <i class="bi bi-layout-three-columns text-primary me-1"></i> Karakteristik Kolom
                                <?php if ($is_locked): ?>
                                    <i class="bi bi-lock-fill text-secondary ms-1 text-xxs" title="Dimensi terkunci"></i>
                                <?php endif; ?>
                            </span>
                            <span class="badge bg-light text-secondary border text-xxs fw-normal"><?= count($list_karakteristik); ?> Master</span>
                        </label>
                        <select name="id_karakteristik_kelompok" id="select_karakteristik" class="form-select form-select-sm" <?= $is_locked ? 'disabled' : ''; ?> data-placeholder="Cari atau pilih Karakteristik Kolom...">
                            <option value="">-- Tanpa Karakteristik --</option>
                            <?php foreach ($list_karakteristik as $k): ?>
                                <option value="<?= $k->id; ?>" <?= ($sel_karakteristik == $k->id) ? 'selected' : ''; ?>>
                                    <?= esc($k->nama_kelompok_ind); ?> (<?= (int)$k->total_items; ?> item)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 3. Periode Waktu -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label text-xs fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                            <span>
                                <i class="bi bi-calendar3 text-primary me-1"></i> Periode Waktu
                                <?php if ($is_locked): ?>
                                    <i class="bi bi-lock-fill text-secondary ms-1 text-xxs" title="Dimensi terkunci"></i>
                                <?php endif; ?>
                            </span>
                            <span class="badge bg-light text-secondary border text-xxs fw-normal"><?= count($list_periode); ?> Master</span>
                        </label>
                        <select name="id_periode_kelompok" id="select_periode" class="form-select form-select-sm" <?= $is_locked ? 'disabled' : ''; ?> data-placeholder="Cari atau pilih Periode Waktu...">
                            <option value="">-- Tanpa Periode --</option>
                            <?php foreach ($list_periode as $p): ?>
                                <option value="<?= $p->id; ?>" <?= ($sel_periode == $p->id) ? 'selected' : ''; ?>>
                                    <?= esc($p->nama_kelompok_ind); ?> (<?= (int)$p->total_items; ?> periode)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- BARIS 2: PENGATURAN MATRIKS (TINGKAT KOLOM, POSISI HEADER, BARIS TOTAL) -->
                <div class="matrix-settings-ribbon mt-3 <?= $is_locked ? 'is-locked' : ''; ?>">
                    <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                        <!-- 1. Tingkat Kolom (Opsi 2 Adaptif Pill Chips) -->
                        <div id="box_tingkat_kolom" class="toolbar-setting-item <?= $is_locked ? 'is-locked' : ''; ?>" title="<?= $is_locked ? 'Pengaturan terkunci: tabel memiliki ' . $total_nilai . ' data nilai terisi' : 'Pilih tingkat kolom yang ingin ditampilkan pada tabel'; ?>">
                            <span class="toolbar-setting-label">
                                <i class="bi bi-layers text-primary me-1"></i> Tingkat:
                                <?php if ($is_locked): ?>
                                    <i class="bi bi-lock-fill text-secondary ms-1 text-xxs" title="Pengaturan matriks terkunci"></i>
                                <?php endif; ?>
                            </span>
                            <div id="container_tingkat_kolom" class="d-flex align-items-center gap-1 flex-wrap">
                                <?php 
                                    $curDepth = $init_max_depth ?? 1;
                                    $actL = [];
                                    if (empty($sel_tingkat_kolom) || $sel_tingkat_kolom === 'semua' || ($sel_tingkat_kolom === '1' && $curDepth > 1)) {
                                        $actL = ($curDepth === 3) ? ['1', '2', '3'] : (($curDepth === 2) ? ['1', '2'] : ['1']);
                                    } elseif ($sel_tingkat_kolom === 'sub_bab') {
                                        $actL = ($curDepth === 3) ? ['2', '3'] : ['2'];
                                    } elseif ($sel_tingkat_kolom === 'bab') {
                                        $actL = ['1'];
                                    } else {
                                        $actL = array_filter(array_map('trim', explode(',', $sel_tingkat_kolom)), function($l) use ($curDepth) {
                                            return (int)$l >= 1 && (int)$l <= $curDepth;
                                        });
                                        if (empty($actL) || ($curDepth > 1 && count($actL) === 1 && in_array('1', $actL) && empty($indikator->tingkat_kolom))) {
                                            $actL = ($curDepth === 3) ? ['1', '2', '3'] : (($curDepth === 2) ? ['1', '2'] : ['1']);
                                        }
                                    }

                                    // Label display awal untuk toggle button baris total
                                    $total_label_display = 'Sesuai Master';
                                    if ($sel_konfigurasi_total === 'jawa_tengah') {
                                        $total_label_display = 'Jawa Tengah';
                                    } elseif ($sel_konfigurasi_total === 'jumlah') {
                                        $total_label_display = 'Jumlah';
                                    } elseif ($sel_konfigurasi_total === 'none') {
                                        $total_label_display = 'Tanpa Total (Sembunyikan)';
                                    } elseif ($sel_konfigurasi_total === 'kustom') {
                                        $total_label_display = !empty($sel_label_total_kustom) ? esc($sel_label_total_kustom) : 'Kustom...';
                                    } elseif ($sel_konfigurasi_total === 'runtun_waktu') {
                                        $total_label_display = 'Runtun Waktu (Multi-Tahun)';
                                    }
                                ?>
                                <?php if ($curDepth <= 1): ?>
                                    <span class="badge bg-light text-secondary border px-2-5 py-1 rounded-pill fw-semibold" style="font-size: 0.76rem; border-color: #cbd5e1 !important;"><i class="bi bi-dash-circle me-1 text-muted"></i>1 Tingkat (Rata)</span>
                                <?php else: ?>
                                    <?php for ($i = 1; $i <= $curDepth; $i++): ?>
                                        <label class="tingkat-pill-item" title="<?= $is_locked ? 'Pengaturan matriks terkunci' : 'Tampilkan atau sembunyikan Tingkat ' . $i; ?>">
                                            <input type="checkbox" class="chk-tingkat" id="chk_tingkat_<?= $i; ?>" value="<?= $i; ?>" <?= in_array((string)$i, $actL) ? 'checked' : ''; ?> <?= $is_locked ? 'disabled' : ''; ?>>
                                            <span class="tingkat-pill-content">
                                                <i class="bi bi-check-circle-fill icon-checked"></i>
                                                <i class="bi bi-circle icon-unchecked"></i>
                                                <span>Tingkat <?= $i; ?></span>
                                            </span>
                                        </label>
                                    <?php endfor; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Divider halus vertikal -->
                        <div class="toolbar-divider d-none d-md-block"></div>

                        <!-- 2. Posisi Hirarki Header (Segmented Button Group + Sub-Pilihan Baris) -->
                        <?php 
                        $isRuntunWaktuInit = ($sel_konfigurasi_total === 'runtun_waktu'); 
                        $isBarisInit = in_array($sel_posisi_hirarki, ['periode_baris', 'periode_baris_pisah'], true);
                        $isBarisPisahInit = ($sel_posisi_hirarki === 'periode_baris_pisah');
                        $isPosisiDisabled = ($isRuntunWaktuInit || $is_locked);
                        ?>
                        <div id="box_posisi_hirarki" class="toolbar-setting-item <?= $isPosisiDisabled ? 'disabled-posisi' : ''; ?> <?= $is_locked ? 'is-locked' : ''; ?>" title="<?= $is_locked ? 'Pengaturan terkunci: tabel memiliki ' . $total_nilai . ' data nilai terisi' : ($isRuntunWaktuInit ? 'Terkunci: Pada mode Runtun Waktu, tata letak posisi hirarki kolom/periode dinonaktifkan karena periode disajikan khusus pada baris total' : 'Atur tata letak hirarki kolom atau periode waktu'); ?>">
                            <span class="toolbar-setting-label">
                                <i class="bi bi-arrow-down-up text-primary me-1"></i> Posisi:
                                <?php if ($is_locked): ?>
                                    <i class="bi bi-lock-fill text-secondary ms-1 text-xxs" title="Pengaturan matriks terkunci"></i>
                                <?php endif; ?>
                            </span>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <div class="posisi-segmented-group <?= $isPosisiDisabled ? 'disabled' : ''; ?>" id="group_posisi_hirarki">
                                    <button type="button" class="btn-segmented <?= ($sel_posisi_hirarki === 'kolom_atas') ? 'active' : ''; ?>" data-val="kolom_atas" id="btn_pos_kolom" title="Karakteristik Kolom di atas, Periode di bawah" <?= $isPosisiDisabled ? 'disabled' : ''; ?>>
                                        <i class="bi bi-layout-three-columns me-1"></i> Kolom
                                    </button>
                                    <button type="button" class="btn-segmented <?= ($sel_posisi_hirarki === 'periode_atas') ? 'active' : ''; ?>" data-val="periode_atas" id="btn_pos_periode" title="Periode di atas, Karakteristik Kolom di bawah" <?= $isPosisiDisabled ? 'disabled' : ''; ?>>
                                        <i class="bi bi-calendar3 me-1"></i> Periode
                                    </button>
                                    <button type="button" class="btn-segmented <?= $isBarisInit ? 'active' : ''; ?>" data-val="<?= $isBarisPisahInit ? 'periode_baris_pisah' : 'periode_baris'; ?>" id="btn_pos_baris" title="Periode menjadi baris vertikal (pivot)" <?= $isPosisiDisabled ? 'disabled' : ''; ?>>
                                        <i class="bi bi-layout-sidebar-inset me-1"></i> Baris
                                    </button>
                                </div>

                                <!-- Sub-Pilihan Header Baris: Gabung vs Pisah -->
                                <div class="sub-baris-group <?= ($isBarisInit && !$isRuntunWaktuInit) ? '' : 'd-none'; ?> <?= $is_locked ? 'disabled' : ''; ?>" id="wrap_sub_baris" title="Format Header Baris: Gabung (1) atau Pisah (1 & 2)">
                                    <button type="button" class="btn-sub-baris <?= !$isBarisPisahInit ? 'active' : ''; ?>" data-val="periode_baris" id="btn_sub_gabung" title="Header disatukan dalam 1 kolom (1)" <?= $is_locked ? 'disabled' : ''; ?>>
                                        Gabung
                                    </button>
                                    <button type="button" class="btn-sub-baris <?= $isBarisPisahInit ? 'active' : ''; ?>" data-val="periode_baris_pisah" id="btn_sub_pisah" title="Header dipisah menjadi 2 kolom (1) dan (2)" <?= $is_locked ? 'disabled' : ''; ?>>
                                        Pisah
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Divider halus vertikal -->
                        <div class="toolbar-divider d-none d-md-block"></div>

                        <!-- 3. Penyajian Baris Total (Custom Modern Popover Dropdown) -->
                        <div id="box_konfigurasi_total" class="toolbar-setting-item <?= $is_locked ? 'is-locked' : ''; ?>" title="<?= $is_locked ? 'Pengaturan terkunci: tabel memiliki ' . $total_nilai . ' data nilai terisi' : 'Atur label penyajian baris total atau sembunyikan untuk tabel rasio/pertumbuhan'; ?>">
                            <span class="toolbar-setting-label">
                                <i class="bi bi-calculator text-primary me-1"></i> Baris Total:
                                <?php if ($is_locked): ?>
                                    <i class="bi bi-lock-fill text-secondary ms-1 text-xxs" title="Pengaturan matriks terkunci"></i>
                                <?php endif; ?>
                            </span>
                            <div class="custom-total-dropdown" id="dropdown_total_menu_wrap">
                                <button type="button" class="btn-total-toggle <?= $is_locked ? 'disabled' : ''; ?>" id="btn_total_toggle" <?= $is_locked ? 'disabled' : ''; ?>>
                                    <span id="selected_total_label"><?= esc($total_label_display); ?></span>
                                    <i class="bi bi-chevron-down toggle-chevron"></i>
                                </button>
                                <input type="hidden" name="konfigurasi_total" id="select_konfigurasi_total" value="<?= esc($sel_konfigurasi_total); ?>">
                                
                                <!-- Menu popover melayang modern: Sederhana & Clean -->
                                <div class="custom-total-menu" id="menu_total_options">
                                    <div class="custom-total-item <?= ($sel_konfigurasi_total === 'default') ? 'active' : ''; ?>" data-val="default">
                                        <span class="item-title" id="label_item_default_text">Sesuai Master</span>
                                        <i class="bi bi-check2 item-check"></i>
                                    </div>
                                    <div class="custom-total-item <?= ($sel_konfigurasi_total === 'jawa_tengah') ? 'active' : ''; ?>" data-val="jawa_tengah">
                                        <span class="item-title">Jawa Tengah</span>
                                        <i class="bi bi-check2 item-check"></i>
                                    </div>
                                    <div class="custom-total-item <?= ($sel_konfigurasi_total === 'jumlah') ? 'active' : ''; ?>" data-val="jumlah">
                                        <span class="item-title">Jumlah</span>
                                        <i class="bi bi-check2 item-check"></i>
                                    </div>
                                    <div class="custom-total-divider"></div>
                                    <div class="custom-total-item <?= ($sel_konfigurasi_total === 'runtun_waktu') ? 'active' : ''; ?>" data-val="runtun_waktu" title="Rincian baris untuk tahun aktif, baris total menyajikan data runtun waktu multi-tahun">
                                        <span class="item-title"><i class="bi bi-clock-history text-warning me-1"></i> Runtun Waktu (Multi-Tahun)</span>
                                        <i class="bi bi-check2 item-check"></i>
                                    </div>
                                    <div class="custom-total-divider"></div>
                                    <div class="custom-total-item <?= ($sel_konfigurasi_total === 'none') ? 'active' : ''; ?>" data-val="none">
                                        <span class="item-title text-muted">Tanpa Total (Sembunyikan)</span>
                                        <i class="bi bi-check2 item-check"></i>
                                    </div>
                                    <div class="custom-total-divider"></div>
                                    <div class="custom-total-item <?= ($sel_konfigurasi_total === 'kustom') ? 'active' : ''; ?>" data-val="kustom">
                                        <span class="item-title">Kustom...</span>
                                        <i class="bi bi-check2 item-check"></i>
                                    </div>
                                    
                                    <!-- Input label kustom langsung di dalam menu dropdown popover -->
                                    <div class="custom-total-input-wrap <?= ($sel_konfigurasi_total === 'kustom') ? '' : 'd-none'; ?>" id="box_popover_kustom_input">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="label_total_kustom" id="input_label_total_kustom" 
                                                   class="form-control form-control-sm total-popover-input" 
                                                   value="<?= esc($sel_label_total_kustom); ?>" 
                                                   placeholder="Ketik label kustom...">
                                            <button type="button" class="btn btn-sm btn-total-apply" id="btn_apply_kustom" title="Terapkan dan tutup">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Divider halus vertikal -->
                        <div class="toolbar-divider d-none d-md-block"></div>

                        <!-- 4. Tampilan Baris (Segmented Toggle: 1 Kolom vs 2 Kolom Rincian) -->
                        <div id="box_tampilan_baris" class="toolbar-setting-item <?= $is_locked ? 'is-locked' : ''; ?>" title="<?= $is_locked ? 'Pengaturan terkunci: tabel memiliki ' . $total_nilai . ' data nilai terisi' : 'Atur tampilan baris: 1 Kolom (Standar) atau 2 Kolom (Rincian)'; ?>">
                            <span class="toolbar-setting-label">
                                <i class="bi bi-layout-split text-primary me-1"></i> Tampilan Baris:
                                <?php if ($is_locked): ?>
                                    <i class="bi bi-lock-fill text-secondary ms-1 text-xxs" title="Pengaturan matriks terkunci"></i>
                                <?php endif; ?>
                            </span>
                            <div class="posisi-segmented-group <?= $is_locked ? 'disabled' : ''; ?>" id="group_tampilan_baris">
                                <button type="button" class="btn-segmented <?= ($sel_tampilan_baris === '2_kolom') ? '' : 'active'; ?>" data-val="1_kolom" id="btn_baris_1kolom" title="1 Kolom vertikal standar" <?= $is_locked ? 'disabled' : ''; ?>>
                                    <i class="bi bi-view-stacked me-1"></i> 1 Kolom
                                </button>
                                <button type="button" class="btn-segmented <?= ($sel_tampilan_baris === '2_kolom') ? 'active' : ''; ?>" data-val="2_kolom" id="btn_baris_2kolom" title="2 Kolom: Kolom 1 (Kelompok Induk) & Kolom 2 (Rincian)" <?= $is_locked ? 'disabled' : ''; ?>>
                                    <i class="bi bi-layout-split me-1"></i> 2 Kolom Rincian
                                </button>
                            </div>
                            <input type="hidden" name="tampilan_baris" id="input_tampilan_baris" value="<?= esc($sel_tampilan_baris ?? '1_kolom'); ?>">
                        </div>

                        <input type="hidden" name="format_nomor_baris" id="input_format_nomor_baris" value="none">
                    </div>
                </div>

                <!-- BARIS 3: FOOTER AKSI & STATUS (TOMBOL FIX DI KANAN) -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-3 mt-3 border-top">
                    <div class="d-flex align-items-center gap-2 text-muted text-xs">
                        <i class="bi bi-info-circle text-primary"></i>
                        <span>Pratinjau matriks di bawah otomatis menyesuaikan konfigurasi yang dipilih.</span>
                    </div>

                    <!-- Kanan: Tombol Aksi (Unduh Template, Lepas, Simpan Matriks) -->
                    <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                        <?php if ($is_dynamic): ?>
                            <a href="<?= base_url('admin/download_template_tabel/' . $tabel->id_tahun_tabel); ?>" class="btn btn-outline-success btn-sm px-3 py-1-5 rounded-pill text-xs fw-semibold shadow-xs d-inline-flex align-items-center gap-1" title="Unduh Template Excel (.xlsx) Berstandar BPS">
                                <i class="bi bi-file-earmark-excel"></i> Unduh Template
                            </a>
                            <a href="<?= base_url('admin/unlink_generator_tabel/' . $tabel->id_tahun_tabel); ?>" class="btn btn-outline-danger btn-sm px-2-5 py-1-5 rounded-pill btn-unlink-dinamis text-xs fw-semibold shadow-xs d-inline-flex align-items-center gap-1" title="Lepas Status Dinamis">
                                <i class="bi bi-link-45deg"></i> Lepas
                            </a>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary btn-sm px-4 py-1-5 fw-bold d-inline-flex align-items-center gap-2 shadow-sm rounded-pill text-xs" style="background: linear-gradient(135deg, #FF6D1F, #ff8c42); border: none; box-shadow: 0 4px 10px rgba(255, 109, 31, 0.3) !important;">
                            <i class="bi bi-check2-circle fs-6"></i>
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
                    <h6 class="fw-bold text-dark">Pilih Judul Baris dan Karakteristik Kolom</h6>
                    <p class="text-xs text-muted mb-0 max-w-sm mx-auto">
                        Pilih minimal <b>Judul Baris</b> dan <b>Karakteristik Kolom</b> pada form di atas untuk melihat wujud tabel secara utuh.
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
    const isTableLocked = <?= $is_locked ? 'true' : 'false'; ?>;

    // Inisialisasi Select2 dengan tema Bootstrap 5 dan fitur pencarian
    $('#select_baris').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Cari atau pilih Judul Baris...',
        allowClear: false
    });

    $('#select_karakteristik').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Cari atau pilih Karakteristik Kolom...',
        allowClear: true
    });

    $('#select_periode').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Cari atau pilih Periode Waktu...',
        allowClear: true
    });

    // Sinkronisasi status tombol switch hirarki saat awal dimuat
    updateHirarkiSwitchState();

    // Trigger pembaruan preview saat input dimensi berubah (Select2 & native change)
    $('#select_karakteristik').on('select2:select select2:clear change', function() {
        $('#input_tingkat_kolom').val('semua'); // Pastikan selalu reset ke semua tingkat saat ganti karakteristik!
        updateHirarkiSwitchState();
        fetchDimensiData();
    });

    $('#select_baris, #select_periode').on('select2:select select2:clear change', function() {
        updateHirarkiSwitchState();
        fetchDimensiData();
    });

    $('#toggle_bilingual').on('change', function() {
        if (currentData) {
            renderMatrix(currentData);
        }
    });

    // ===== HANDLER CUSTOM DROPDOWN MODERN BARIS TOTAL (PENGGANTI GAMBAR 3) =====
    // Toggle buka-tutup menu dropdown
    $('#btn_total_toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (isTableLocked) return false;
        const $wrap = $('#dropdown_total_menu_wrap');
        $wrap.toggleClass('open');
        if ($wrap.hasClass('open') && $('#select_konfigurasi_total').val() === 'kustom') {
            setTimeout(function() {
                $('#input_label_total_kustom').focus().select();
            }, 60);
        }
    });

    // Tutup menu dropdown jika pengguna mengklik area luar
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#dropdown_total_menu_wrap').length) {
            $('#dropdown_total_menu_wrap').removeClass('open');
        }
    });

    // Event saat opsi pada menu dropdown diklik
    $(document).on('click', '.custom-total-item', function(e) {
        e.preventDefault();
        if (isTableLocked) return false;
        const val = $(this).data('val');
        const label = $(this).find('.item-title').text();

        $('#select_konfigurasi_total').val(val);
        $('.custom-total-item').removeClass('active');
        $(this).addClass('active');

        // Jika memilih Runtun Waktu, netralkan posisi hirarki ke kolom_atas
        if (val === 'runtun_waktu') {
            $('#input_posisi_hirarki').val('kolom_atas');
            $('#group_posisi_hirarki .btn-segmented').removeClass('active');
            $('#btn_pos_kolom').addClass('active');
            $('#wrap_sub_baris').addClass('d-none');
        }

        // Segera perbarui status interaktif Posisi Hirarki saat opsi total berubah
        updateHirarkiSwitchState();

        if (val === 'kustom') {
            // Tampilkan input inline di dalam popover dan fokuskan
            $('#box_popover_kustom_input').removeClass('d-none');
            const customVal = $('#input_label_total_kustom').val().trim();
            $('#selected_total_label').text(customVal ? customVal : 'Kustom...');
            setTimeout(function() {
                $('#input_label_total_kustom').focus().select();
            }, 60);
        } else {
            // Sembunyikan input kustom dan tutup menu dropdown
            $('#box_popover_kustom_input').addClass('d-none');
            $('#selected_total_label').text(label);
            $('#dropdown_total_menu_wrap').removeClass('open');
        }

        if (currentData) {
            renderMatrix(currentData);
        }
    });

    // Handler input real-time label kustom di dalam popover
    $('#input_label_total_kustom').on('input', function() {
        if (isTableLocked) return false;
        const val = $(this).val().trim();
        if ($('#select_konfigurasi_total').val() === 'kustom') {
            $('#selected_total_label').text(val ? val : 'Kustom...');
        }
        if (currentData) {
            renderMatrix(currentData);
        }
    });

    // Tekan tombol Enter pada input kustom -> tutup dropdown
    $('#input_label_total_kustom').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#dropdown_total_menu_wrap').removeClass('open');
        }
    });

    // Tombol centang Apply pada input kustom di popover
    $('#btn_apply_kustom').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#dropdown_total_menu_wrap').removeClass('open');
    });

    // Handler switch tampilan baris (1 Kolom vs 2 Kolom Rincian)
    $('#group_tampilan_baris .btn-segmented').on('click', function(e) {
        e.preventDefault();
        if (isTableLocked || $(this).prop('disabled') || $('#group_tampilan_baris').hasClass('disabled')) {
            return false;
        }
        const val = $(this).data('val');
        $('#group_tampilan_baris .btn-segmented').removeClass('active');
        $(this).addClass('active');
        $('#input_tampilan_baris').val(val);

        if (currentData) {
            renderMatrix(currentData);
        }
    });

    // Handler switch hirarki segmented buttons: Kolom, Periode, Baris
    $('#group_posisi_hirarki .btn-segmented').on('click', function(e) {
        e.preventDefault();
        if (isTableLocked || $('#group_posisi_hirarki').hasClass('disabled') || $(this).prop('disabled') || $('#select_konfigurasi_total').val() === 'runtun_waktu') {
            return false;
        }
        let val = $(this).data('val');
        if ($(this).attr('id') === 'btn_pos_baris') {
            const subVal = $('#wrap_sub_baris .btn-sub-baris.active').data('val') || 'periode_baris';
            val = subVal;
            $('#wrap_sub_baris').removeClass('d-none');
        } else {
            $('#wrap_sub_baris').addClass('d-none');
        }
        $('#input_posisi_hirarki').val(val);
        $('#group_posisi_hirarki .btn-segmented').removeClass('active');
        $(this).addClass('active');

        if (currentData) {
            renderMatrix(currentData);
        }
    });

    // Handler klik pada sub-baris buttons (Gabung vs Pisah)
    $(document).on('click', '.btn-sub-baris', function(e) {
        e.preventDefault();
        if (isTableLocked) return false;
        const val = $(this).data('val');
        $('.btn-sub-baris').removeClass('active');
        $(this).addClass('active');
        $('#input_posisi_hirarki').val(val);

        if (currentData) {
            renderMatrix(currentData);
        }
    });


    function parseActiveLevels(tingkatKolom, maxDepth) {
        function getDefaultLevels(depth) {
            const arr = [];
            for (let i = 1; i <= depth; i++) arr.push(String(i));
            return arr;
        }
        if (!tingkatKolom || tingkatKolom === 'semua' || (tingkatKolom === '1' && maxDepth > 1)) {
            return getDefaultLevels(maxDepth);
        }
        if (tingkatKolom === 'sub_bab') {
            return (maxDepth >= 3) ? ['2', '3'] : ['2'];
        }
        if (tingkatKolom === 'bab') {
            return ['1'];
        }
        const levels = String(tingkatKolom).split(',').map(s => s.trim()).filter(Boolean);
        const filtered = levels.filter(l => Number(l) >= 1 && Number(l) <= maxDepth);
        return filtered.length > 0 ? filtered : getDefaultLevels(maxDepth);
    }

    function updateTingkatKolomUI(maxDepth) {
        const $container = $('#container_tingkat_kolom');
        let savedVal = $('#input_tingkat_kolom').val();
        // Default tercentang semua jika baru dibuka atau kedalaman kolom bertingkat
        if (!savedVal || savedVal === 'semua' || (savedVal === '1' && maxDepth > 1)) {
            const defArr = [];
            for (let i = 1; i <= maxDepth; i++) defArr.push(String(i));
            savedVal = defArr.join(',');
            $('#input_tingkat_kolom').val(savedVal);
        }
        const active = parseActiveLevels(savedVal, maxDepth);

        if (maxDepth <= 1) {
            $container.html('<span class="badge bg-light text-secondary border px-2-5 py-1 rounded-pill fw-semibold" style="font-size: 0.76rem; border-color: #cbd5e1 !important;"><i class="bi bi-dash-circle me-1 text-muted"></i>1 Tingkat (Rata)</span>');
            $('#input_tingkat_kolom').val('1');
            return;
        }

        $('#input_tingkat_kolom').val(active.join(','));

        let html = '';
        for (let i = 1; i <= maxDepth; i++) {
            const isChecked = active.includes(String(i));
            html += `
                <label class="tingkat-pill-item" title="${isTableLocked ? 'Pengaturan matriks terkunci' : 'Tampilkan atau sembunyikan Tingkat ' + i}">
                    <input type="checkbox" class="chk-tingkat" id="chk_tingkat_${i}" value="${i}" ${isChecked ? 'checked' : ''} ${isTableLocked ? 'disabled' : ''}>
                    <span class="tingkat-pill-content">
                        <i class="bi bi-check-circle-fill icon-checked"></i>
                        <i class="bi bi-circle icon-unchecked"></i>
                        <span>Tingkat ${i}</span>
                    </span>
                </label>
            `;
        }
        $container.html(html);
    }

    // Handler perubahan checkbox Tingkat Kolom Adaptif (Opsi 2)
    $(document).on('change', '.chk-tingkat', function() {
        if (isTableLocked) return false;
        const checked = $('.chk-tingkat:checked');
        if (checked.length === 0) {
            $(this).prop('checked', true);
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Minimal salah satu tingkat kolom harus dipilih.'
            });
            return;
        }

        const active = checked.map(function() { return $(this).val(); }).get();
        $('#input_tingkat_kolom').val(active.join(','));

        if (currentData) {
            renderMatrix(currentData);
        }
    });

    // Validasi submit formulir: Minimal memilih 2 dari 3 dimensi (Baris, Kolom, Periode)
    $('#formGenerator').on('submit', function(e) {
        const idBar = $('#select_baris').val() || 0;
        const idKar = $('#select_karakteristik').val() || 0;
        const idPer = $('#select_periode').val() || 0;

        const count = (parseInt(idBar) > 0 ? 1 : 0) + (parseInt(idKar) > 0 ? 1 : 0) + (parseInt(idPer) > 0 ? 1 : 0);

        if (count < 2) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Dimensi Belum Cukup',
                text: 'Mohon pilih minimal 2 dimensi (antara Judul Baris, Karakteristik Kolom, atau Periode Waktu) untuk merakit tabel dinamis.'
            });
            return false;
        }
    });

    // Panggil langsung saat pertama kali buka jika sudah ada konfigurasi minimal (>= 2 dimensi)
    const initDimCount = (parseInt($('#select_baris').val() || 0) > 0 ? 1 : 0) +
                         (parseInt($('#select_karakteristik').val() || 0) > 0 ? 1 : 0) +
                         (parseInt($('#select_periode').val() || 0) > 0 ? 1 : 0);
    if (initDimCount >= 2) {
        fetchDimensiData();
    }

    /**
     * Memperbarui status interaktif switch hirarki & checkbox tingkat kolom
     */
    function updateHirarkiSwitchState() {
        if (isTableLocked) {
            $('.chk-tingkat').prop('disabled', true);
            $('#box_tingkat_kolom').addClass('is-locked').css({'opacity': '0.55', 'pointer-events': 'none'});
            
            $('#group_posisi_hirarki').addClass('disabled').css({'opacity': '0.55', 'pointer-events': 'none'});
            $('#group_posisi_hirarki .btn-segmented').prop('disabled', true);
            $('#wrap_sub_baris .btn-sub-baris').prop('disabled', true);
            $('#box_posisi_hirarki').addClass('disabled-posisi is-locked').css({'opacity': '0.55', 'pointer-events': 'none'});

            $('#btn_total_toggle').prop('disabled', true).addClass('disabled');
            $('#box_konfigurasi_total').addClass('is-locked').css({'opacity': '0.55', 'pointer-events': 'none'});

            $('#group_tampilan_baris').addClass('disabled').css({'opacity': '0.55', 'pointer-events': 'none'});
            $('#group_tampilan_baris .btn-segmented').prop('disabled', true);
            $('#box_tampilan_baris').addClass('is-locked').css({'opacity': '0.55', 'pointer-events': 'none'});

            // Sinkronisasi status active tampilan baris tetap berjalan agar highlight pilihan yang tersimpan tetap terlihat
            const curTampilan = $('#input_tampilan_baris').val() || '1_kolom';
            $('#group_tampilan_baris .btn-segmented').removeClass('active');
            if (curTampilan === '2_kolom') {
                $('#btn_baris_2kolom').addClass('active');
            } else {
                $('#btn_baris_1kolom').addClass('active');
            }

            // Sinkronisasi status active posisi hirarki agar highlight pilihan yang tersimpan tetap terlihat
            const curPos = $('#input_posisi_hirarki').val() || 'kolom_atas';
            $('#group_posisi_hirarki .btn-segmented').removeClass('active');
            if (curPos === 'periode_baris' || curPos === 'periode_baris_pisah') {
                $('#btn_pos_baris').addClass('active');
                $('#wrap_sub_baris').removeClass('d-none');
            } else if (curPos === 'periode_atas') {
                $('#btn_pos_periode').addClass('active');
                $('#wrap_sub_baris').addClass('d-none');
            } else {
                $('#btn_pos_kolom').addClass('active');
                $('#wrap_sub_baris').addClass('d-none');
            }
            return;
        }

        const hasBaris   = parseInt($('#select_baris').val() || 0) > 0;
        const hasKolom   = parseInt($('#select_karakteristik').val() || 0) > 0;
        const hasPeriode = parseInt($('#select_periode').val() || 0) > 0;
        const cfgTotal   = $('#select_konfigurasi_total').val() || 'default';
        const isRuntunWaktu = (cfgTotal === 'runtun_waktu');

        // Status box_tingkat_kolom
        if (hasKolom) {
            $('.chk-tingkat').prop('disabled', false);
            $('#box_tingkat_kolom').css({'opacity': '1', 'pointer-events': 'auto'});
        } else {
            $('.chk-tingkat').prop('disabled', true);
            $('#box_tingkat_kolom').css({'opacity': '0.45', 'pointer-events': 'none'});
        }

        // Posisi Hirarki:
        // Jika mode Runtun Waktu aktif, posisi hirarki kolom vs periode TIDAK BERLAKU
        // karena dimensi periode disajikan khusus secara vertikal sebagai baris total runtun waktu multi-tahun di bagian bawah.
        if (isRuntunWaktu) {
            $('#input_posisi_hirarki').val('kolom_atas');
            $('#group_posisi_hirarki .btn-segmented').removeClass('active');
            $('#btn_pos_kolom').addClass('active');
            $('#wrap_sub_baris').addClass('d-none');
            $('#box_posisi_hirarki')
                .addClass('disabled-posisi')
                .attr('title', 'Terkunci: Pada mode Runtun Waktu, tata letak posisi hirarki kolom/periode dinonaktifkan karena periode disajikan khusus pada baris total');
            $('#group_posisi_hirarki')
                .addClass('disabled')
                .css({'opacity': '0.5', 'pointer-events': 'none'});
            $('#group_posisi_hirarki .btn-segmented').prop('disabled', true);
        } else {
            // Jika bukan mode Runtun Waktu, normalkan status
            $('#box_posisi_hirarki')
                .removeClass('disabled-posisi')
                .css({'opacity': '1', 'pointer-events': 'auto'})
                .attr('title', 'Atur tata letak hirarki kolom atau periode waktu');

        // Posisi Hirarki hanya relevan jika ada 3 dimensi lengkap (Baris + Kolom + Periode)
        if (hasBaris && hasKolom && hasPeriode) {
            $('#group_posisi_hirarki').removeClass('disabled').css({'opacity': '1', 'pointer-events': 'auto'});
            $('#group_posisi_hirarki .btn-segmented').prop('disabled', false);
            const pos = $('#input_posisi_hirarki').val() || 'kolom_atas';
            $('#group_posisi_hirarki .btn-segmented').removeClass('active');
            if (pos === 'periode_baris' || pos === 'periode_baris_pisah') {
                $('#btn_pos_baris').addClass('active');
                $('#wrap_sub_baris').removeClass('d-none');
                $('.btn-sub-baris').removeClass('active');
                if (pos === 'periode_baris_pisah') {
                    $('#btn_sub_pisah').addClass('active');
                } else {
                    $('#btn_sub_gabung').addClass('active');
                }
            } else if (pos === 'periode_atas') {
                $('#btn_pos_periode').addClass('active');
                $('#wrap_sub_baris').addClass('d-none');
            } else {
                $('#btn_pos_kolom').addClass('active');
                $('#wrap_sub_baris').addClass('d-none');
            }
        } else {
            $('#wrap_sub_baris').addClass('d-none');
            $('#group_posisi_hirarki').addClass('disabled').css({'opacity': '0.45', 'pointer-events': 'none'});
            $('#group_posisi_hirarki .btn-segmented').prop('disabled', true);
            if (hasKolom) {
                $('#input_posisi_hirarki').val('kolom_atas');
                $('#group_posisi_hirarki .btn-segmented').removeClass('active');
                $('#btn_pos_kolom').addClass('active');
            } else if (hasPeriode) {
                $('#input_posisi_hirarki').val('periode_atas');
                $('#group_posisi_hirarki .btn-segmented').removeClass('active');
                $('#btn_pos_periode').addClass('active');
            }
        }
    }

        // Status box_tampilan_baris: hanya aktif bila ada dimensi baris dan bukan mode periode baris / runtun waktu
        const posCurrent = $('#input_posisi_hirarki').val() || 'kolom_atas';
        const isPeriodeBarisNow = (posCurrent === 'periode_baris' || posCurrent === 'periode_baris_pisah');
        if (hasBaris && !isRuntunWaktu && !isPeriodeBarisNow) {
            $('#box_tampilan_baris').css({'opacity': '1', 'pointer-events': 'auto'});
            $('#group_tampilan_baris .btn-segmented').prop('disabled', false);
        } else {
            $('#box_tampilan_baris').css({'opacity': '0.45', 'pointer-events': 'none'});
            $('#group_tampilan_baris .btn-segmented').prop('disabled', true);
        }

        // Sinkronisasi status active tampilan baris persis seperti Posisi
        const curTampilan = $('#input_tampilan_baris').val() || '1_kolom';
        $('#group_tampilan_baris .btn-segmented').removeClass('active');
        if (curTampilan === '2_kolom') {
            $('#btn_baris_2kolom').addClass('active');
        } else {
            $('#btn_baris_1kolom').addClass('active');
        }
    }

    /**
     * Mengambil struktur JSON dimensi dari backend
     */
    function fetchDimensiData() {
        const idKar = $('#select_karakteristik').val() || 0;
        const idBar = $('#select_baris').val() || 0;
        const idPer = $('#select_periode').val() || 0;
        const tingKol = $('#input_tingkat_kolom').val() || 'semua';

        // Wajib memilih minimal 2 dari 3 dimensi
        const count = (parseInt(idBar) > 0 ? 1 : 0) + (parseInt(idKar) > 0 ? 1 : 0) + (parseInt(idPer) > 0 ? 1 : 0);
        if (count < 2) {
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
                id_periode: idPer,
                tingkat_kolom: tingKol
            },
            dataType: 'json',
            success: function(res) {
                $('#preview_loading').addClass('d-none');
                if (res.status === 'success') {
                    currentData = res;
                    const maxDepth = res.kolom ? (res.kolom.max_depth || 1) : 1;
                    updateTingkatKolomUI(maxDepth);
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
        const posHirarki = $('#input_posisi_hirarki').val() || 'kolom_atas';
        const isPeriodeAtas = (posHirarki === 'periode_atas');
        const isPeriodeBaris = (posHirarki === 'periode_baris' || posHirarki === 'periode_baris_pisah');
        const isPeriodeBarisPisah = (posHirarki === 'periode_baris_pisah');
        const tingkatKolom = $('#input_tingkat_kolom').val() || 'semua';
        const konfigurasiTotal = $('#select_konfigurasi_total').val() || 'default';
        const isRuntunWaktu = (konfigurasiTotal === 'runtun_waktu');
        const labelTotalKustom = $('#input_label_total_kustom').val() || '';

        // Pastikan status interaktif posisi hirarki selalu sinkron
        updateHirarkiSwitchState();

        const kolomStruct = data.kolom;
        const maxDepth = kolomStruct ? (kolomStruct.max_depth || (kolomStruct.has_groups ? 2 : 1)) : 1;
        const rawFlatKolom = data.kolom ? (data.kolom.flat_items || []) : [];
        const barisItems = data.baris || [];
        const periodeItems = data.periode || [];

        // Deteksi apakah master baris memiliki item total untuk menyesuaikan label dropdown
        let masterTotalItem = null;
        barisItems.forEach(function(b) {
            let t = (b.tipe_baris || '').toLowerCase().trim();
            let l = (b.nama_baris_ind || '').toLowerCase().trim();
            if (t === 'total' || (!t && (l === 'jumlah' || l === 'total' || l.includes('jawa tengah') || l.includes('produk domestik regional bruto') || l === 'pdrb' || l.includes('gross regional domestic product') || l === 'grdp'))) {
                if (!l.includes('curah hujan') && !l.includes('hari hujan')) {
                    masterTotalItem = b;
                }
            }
        });

        if (masterTotalItem) {
            const origLabel = masterTotalItem.nama_baris_ind || 'Jumlah';
            const masterLabelText = 'Sesuai Master (' + origLabel + ')';
            $('#label_item_default_text').text(masterLabelText);
            if ($('#select_konfigurasi_total').val() === 'default') {
                $('#selected_total_label').text(masterLabelText);
            }
        } else {
            $('#label_item_default_text').text('Sesuai Master');
            if ($('#select_konfigurasi_total').val() === 'default') {
                $('#selected_total_label').text('Sesuai Master');
            }
        }

        // Parse tingkat aktif menjadi array angka [1, 2, 3] dsb
        const activeLevels = parseActiveLevels(tingkatKolom, maxDepth).map(Number).sort((a, b) => a - b);

        // Tentukan flatKolom yang digunakan berdasarkan tingkat aktif terendah
        let flatKolom = rawFlatKolom;
        if (kolomStruct && maxDepth >= 3) {
            if (activeLevels.includes(maxDepth) || activeLevels.includes(3)) {
                flatKolom = rawFlatKolom;
            } else if (activeLevels.includes(2)) {
                flatKolom = [];
                (kolomStruct.groups || []).forEach(function(grp) {
                    if (grp.subgroups && grp.subgroups.length > 0) {
                        grp.subgroups.forEach(function(sub) {
                            const firstChild = (sub.children && sub.children[0]) ? sub.children[0] : {};
                            flatKolom.push({
                                id: firstChild.id || 0,
                                item_ind: sub.title_ind,
                                item_en: sub.title_en || sub.title_ind
                            });
                        });
                    } else {
                        const firstChild = (grp.children && grp.children[0]) ? grp.children[0] : (grp.item || {});
                        flatKolom.push({
                            id: firstChild.id || 0,
                            item_ind: grp.title_ind,
                            item_en: grp.title_en || grp.title_ind
                        });
                    }
                });
            } else {
                flatKolom = (kolomStruct.groups || []).map(function(grp) {
                    const firstChild = (grp.children && grp.children[0]) ? grp.children[0] : (grp.item || {});
                    return {
                        id: firstChild.id || 0,
                        item_ind: grp.title_ind,
                        item_en: grp.title_en || grp.title_ind
                    };
                });
            }
        } else if (kolomStruct && maxDepth === 2) {
            if (activeLevels.includes(2)) {
                flatKolom = rawFlatKolom;
            } else {
                flatKolom = (kolomStruct.groups || []).map(function(grp) {
                    const firstChild = (grp.children && grp.children[0]) ? grp.children[0] : (grp.item || {});
                    return {
                        id: firstChild.id || 0,
                        item_ind: grp.title_ind,
                        item_en: grp.title_en || grp.title_ind
                    };
                });
            }
        }

        const hasKolom = (flatKolom && flatKolom.length > 0);
        const hasPeriode = (periodeItems && periodeItems.length > 0);

        // Hitung level maksimum pada seluruh kelompok baris
        let maxBarisGroupLevel = 0;
        for (let i = 0; i < barisItems.length; i++) {
            const lvl = parseInt(barisItems[i].level_indentasi) || 0;
            if (lvl > maxBarisGroupLevel) maxBarisGroupLevel = lvl;
        }

        // Helper untuk mendeteksi apakah suatu baris merupakan Parent/Header (bukan level terakhir/leaf)
        function isParentBaris(idx, items) {
            const cur = items[idx];
            if (!cur) return false;
            let tipe = (cur.tipe_baris || '').toLowerCase().trim();

            // 1. Prioritas Utama: Sesuai role fungsional yang dipilih pengguna di Master Dimensi
            if (tipe === 'kategori') {
                return true; // Kategori: banner pemisah sekat (tanpa sel data)
            }
            if (tipe === 'data' || tipe === 'total' || tipe === 'subtotal') {
                return false; // Data / Total: baris fungsional (bisa diisi sel datanya)
            }

            // 2. Fallback untuk data lawas yang tipe_baris-nya belum tersimpan di DB
            if (maxBarisGroupLevel === 0) {
                return false;
            }

            let lowerNama = (cur.nama_baris_ind || '').toLowerCase().trim();
            if (
                lowerNama === 'jumlah' || lowerNama === 'total' || 
                lowerNama.includes('jawa tengah') || 
                lowerNama.includes('produk domestik regional bruto') || 
                lowerNama === 'pdrb' || 
                lowerNama.includes('gross regional domestic product') || 
                lowerNama === 'grdp'
            ) {
                if (!lowerNama.includes('curah hujan') && !lowerNama.includes('hari hujan')) {
                    return false;
                }
            }

            if (lowerNama === 'kabupaten' || lowerNama === 'kota') {
                return true;
            }

            return false;
        }

        const hasBaris = (barisItems && barisItems.length > 0);

        let maxBarisLevel = 0;
        barisItems.forEach(function(b) {
            let lvl = parseInt(b.level_indentasi) || 0;
            if (lvl > maxBarisLevel) maxBarisLevel = lvl;
        });
        const totalTingkatBaris = maxBarisLevel + 1;

        // Hitung statistik sel data
        let totalLeafCols = 0;
        let displayRowCount = 0;
        let totalCells = 0;

        if (!hasBaris && hasKolom && hasPeriode) {
            // Kasus Tanpa Dimensi Baris (Runtun Waktu): Kolom adalah flatKolom, Baris adalah periodeItems
            totalLeafCols = flatKolom.length;
            displayRowCount = periodeItems.length;
            totalCells = totalLeafCols * displayRowCount;
        } else {
            if (isRuntunWaktu) {
                totalLeafCols = flatKolom.length;
            } else if (hasKolom && hasPeriode) {
                totalLeafCols = isPeriodeBaris ? flatKolom.length : (flatKolom.length * periodeItems.length);
            } else if (hasKolom) {
                totalLeafCols = flatKolom.length;
            } else if (hasPeriode) {
                totalLeafCols = periodeItems.length;
            }

            const leafDataRows = barisItems.filter((b, idx) => !isParentBaris(idx, barisItems));
            const parentRowsCount = barisItems.length - leafDataRows.length;
            if (isRuntunWaktu) {
                const totalRowsExtra = periodeItems.length > 1 ? (periodeItems.length - 1) : 0;
                displayRowCount = barisItems.length + totalRowsExtra;
                totalCells = totalLeafCols * (leafDataRows.length + totalRowsExtra);
            } else {
                displayRowCount = isPeriodeBaris 
                    ? (parentRowsCount + leafDataRows.length * (periodeItems.length || 1)) 
                    : barisItems.length;
                totalCells = totalLeafCols * leafDataRows.length;
            }
        }

        $('#stat_total_cells').text(displayRowCount + ' Baris × ' + totalLeafCols + ' Kolom (' + totalCells + ' Sel)');

        // =====================================================================
        // 1. RENDER THEAD (MULTI-LEVEL HEADER DINAMIS OPSI 2)
        // =====================================================================
        let theadHtml = '';

        // Tentukan jumlah baris header
        let totalHeaderRows = 1;
        if (!hasBaris && hasKolom && hasPeriode) {
            totalHeaderRows = activeLevels.length;
        } else if (hasKolom && hasPeriode) {
            totalHeaderRows = (isPeriodeBaris || isRuntunWaktu) ? activeLevels.length : (activeLevels.length + 1);
        } else if (hasKolom) {
            totalHeaderRows = activeLevels.length;
        } else if (hasPeriode) {
            totalHeaderRows = 1;
        }

        // Dapatkan Judul Kelompok Baris secara Dinamis
        const barisKelompok = data.baris_kelompok;
        let titleBarisInd = '';
        let titleBarisEn = '';
        let titleSubBarisInd = '';
        let titleSubBarisEn = '';

        if (barisKelompok) {
            titleBarisInd = barisKelompok.judul_baris_ind || barisKelompok.nama_kelompok_ind || '';
            titleBarisEn = barisKelompok.judul_baris_en || barisKelompok.nama_kelompok_en || '';
            titleSubBarisInd = barisKelompok.judul_sub_baris_ind || '';
            titleSubBarisEn = barisKelompok.judul_sub_baris_en || '';
        } else {
            let opt = $('#select_baris option:selected');
            let dataJudulInd = opt.data('judul-ind');
            let dataJudulEn = opt.data('judul-en');
            titleSubBarisInd = opt.data('judul-sub-ind') || '';
            titleSubBarisEn = opt.data('judul-sub-en') || '';
            if (dataJudulInd) {
                titleBarisInd = dataJudulInd;
                titleBarisEn = dataJudulEn || '';
            } else {
                let optText = opt.text().trim();
                if (optText && !optText.startsWith('--')) {
                    titleBarisInd = optText.replace(/\s*\(\d+\s*baris\)$/i, '').trim();
                }
            }
        }

        let cleanTitleInd = titleBarisInd ? titleBarisInd.replace(/^\d+\s+/, '').trim() : 'Kabupaten/Kota';
        let cleanTitleSubInd = titleSubBarisInd ? titleSubBarisInd.replace(/^\d+\s+/, '').trim() : '';
        let cleanTitleSubEn = titleSubBarisEn ? titleSubBarisEn.trim() : '';

        // Tampilan Baris dikontrol MANUAL via tombol, bukan otomatis
        const modeTampilanBaris = $('#input_tampilan_baris').val() || '1_kolom';
        const isBarisDuaKolom = (!isPeriodeBaris && !isRuntunWaktu && hasBaris && modeTampilanBaris === '2_kolom');

        if (isBarisDuaKolom && !cleanTitleSubInd) {
            cleanTitleSubInd = 'Rincian';
            cleanTitleSubEn = 'Details';
        }

        // Deteksi Multi-Baris (Multi-Column Row misal: "Jenis Usaha || Satuan" atau "Jenis Komoditi || Provinsi")
        let isMultiBaris = cleanTitleInd.includes('||');
        if (!isMultiBaris) {
            for (let i = 0; i < barisItems.length; i++) {
                if ((barisItems[i].nama_baris_ind || '').includes('||')) {
                    isMultiBaris = true;
                    break;
                }
            }
        }

        let titleCol1Ind = cleanTitleInd;
        let titleCol2Ind = '';
        let titleCol1En = titleBarisEn;
        let titleCol2En = '';

        if (isMultiBaris) {
            if (cleanTitleInd.includes('||')) {
                let pInd = cleanTitleInd.split('||');
                titleCol1Ind = pInd[0].trim();
                titleCol2Ind = pInd[1].trim();
            } else {
                titleCol1Ind = cleanTitleInd;
                // Cerdas deteksi isi kolom 2 dari barisItems jika cleanTitleInd tidak mengandung ||
                let col2Samples = [];
                for (let k = 0; k < barisItems.length; k++) {
                    let nm = barisItems[k].nama_baris_ind || '';
                    if (nm.includes('||')) {
                        let c2 = nm.split('||')[1].trim();
                        if (c2) col2Samples.push(c2.toLowerCase());
                    }
                }
                let sampleJoined = col2Samples.slice(0, 15).join(' ');
                if (sampleJoined.includes('maret') || sampleJoined.includes('september') || sampleJoined.includes('januari') || sampleJoined.includes('desember') || sampleJoined.includes('juni')) {
                    titleCol2Ind = 'Bulan';
                    titleCol2En = 'Month';
                } else if (sampleJoined.includes('jakarta') || sampleJoined.includes('jawa') || sampleJoined.includes('bali') || sampleJoined.includes('sumatera') || sampleJoined.includes('yogyakarta')) {
                    titleCol2Ind = 'Provinsi';
                    titleCol2En = 'Province';
                } else if (sampleJoined.includes('unit') || sampleJoined.includes('juta') || sampleJoined.includes('rupiah') || sampleJoined.includes('persen') || sampleJoined.includes('%') || sampleJoined.includes('ton') || sampleJoined.includes('kg')) {
                    titleCol2Ind = 'Satuan';
                    titleCol2En = 'Unit';
                } else {
                    titleCol2Ind = 'Satuan / Detail';
                    titleCol2En = 'Detail';
                }
            }

            if (titleBarisEn && titleBarisEn.includes('||')) {
                let pEn = titleBarisEn.split('||');
                titleCol1En = pEn[0].trim();
                titleCol2En = pEn[1].trim();
            } else if (!titleCol2En) {
                titleCol1En = titleBarisEn;
                let lowerCol2 = titleCol2Ind.toLowerCase();
                if (lowerCol2.includes('satuan') || lowerCol2.includes('unit')) {
                    titleCol2En = 'Unit';
                } else if (lowerCol2.includes('provinsi')) {
                    titleCol2En = 'Province';
                } else if (lowerCol2.includes('bulan')) {
                    titleCol2En = 'Month';
                } else {
                    titleCol2En = 'Detail';
                }
            }
        } else {
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
            titleCol1En = titleBarisEn;
        }

        // Dapatkan Judul Kelompok Periode secara Dinamis
        const periodeKelompok = data.periode_kelompok;
        let cleanTitlePeriode = 'Tahun';
        let cleanTitlePeriodeEn = '';
        if (periodeKelompok) {
            cleanTitlePeriode = periodeKelompok.judul_periode_ind || 'Tahun';
            cleanTitlePeriodeEn = periodeKelompok.judul_periode_en || '';
        }

        /**
         * Helper untuk menghasilkan string HTML elemen <th> kolom judul baris (1 kolom atau 2 kolom)
         */
        function getRowHeaderTh(rowspan) {
            if (isBarisDuaKolom) {
                let html = '<th rowspan="' + rowspan + '" class="th-freeze-col text-start">';
                html += '<span>' + escapeHtml(cleanTitleInd) + '</span>';
                if (isBilingual && titleBarisEn && titleBarisEn.trim().toLowerCase() !== cleanTitleInd.trim().toLowerCase()) {
                    html += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleBarisEn) + '</span>';
                }
                html += '</th>';

                html += '<th rowspan="' + rowspan + '" class="th-freeze-col-2 text-start">';
                html += '<span>' + escapeHtml(cleanTitleSubInd) + '</span>';
                if (isBilingual && cleanTitleSubEn && cleanTitleSubEn.trim().toLowerCase() !== cleanTitleSubInd.trim().toLowerCase()) {
                    html += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(cleanTitleSubEn) + '</span>';
                }
                html += '</th>';
                return html;
            }

            if (isMultiBaris && !cleanTitleInd.includes('||')) {
                let th = '<th rowspan="' + rowspan + '" colspan="2" class="th-freeze-col text-center" style="vertical-align: middle;">';
                th += '<span>' + escapeHtml(titleCol1Ind) + '</span>';
                if (isBilingual && titleCol1En && titleCol1En.trim().toLowerCase() !== titleCol1Ind.trim().toLowerCase()) {
                    th += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleCol1En) + '</span>';
                }
                th += '</th>';
                return th;
            }

            let th = '<th rowspan="' + rowspan + '" class="th-freeze-col text-start">';
            th += '<span>' + escapeHtml(titleCol1Ind) + '</span>';
            if (isBilingual && titleCol1En && titleCol1En.trim().toLowerCase() !== titleCol1Ind.trim().toLowerCase()) {
                th += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleCol1En) + '</span>';
            }
            th += '</th>';

            if (isMultiBaris) {
                th += '<th rowspan="' + rowspan + '" class="th-freeze-col-2 text-center">';
                th += '<span>' + escapeHtml(titleCol2Ind) + '</span>';
                if (isBilingual && titleCol2En && titleCol2En.trim().toLowerCase() !== titleCol2Ind.trim().toLowerCase()) {
                    th += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleCol2En) + '</span>';
                }
                th += '</th>';
            }
            return th;
        }
        const renderStubHeaderHtml = getRowHeaderTh;

        /**
         * Helper untuk menghasilkan string HTML elemen <th> untuk suatu tingkat kolom tertentu
         */
        function getLevelCells(lvl, multiplier) {
            if (kolomStruct && kolomStruct.header_rows && kolomStruct.header_rows[lvl]) {
                let cellsHtml = '';
                kolomStruct.header_rows[lvl].forEach(function(cell) {
                    const colspan = cell.colspan * multiplier;
                    const rowspan = cell.rowspan;
                    const thClass = cell.is_leaf ? 'th-data-col text-center' : 'th-group-col text-center';
                    cellsHtml += '<th' 
                        + (colspan > 1 ? ' colspan="' + colspan + '"' : '') 
                        + (rowspan > 1 ? ' rowspan="' + rowspan + '"' : '') 
                        + ' class="' + thClass + '">';
                    cellsHtml += '<span>' + escapeHtml(cell.title_ind) + '</span>';
                    if (isBilingual && cell.title_en) {
                        cellsHtml += '<span class="th-en">' + escapeHtml(cell.title_en) + '</span>';
                    }
                    cellsHtml += '</th>';
                });
                return cellsHtml;
            }

            let cellsHtml = '';
            const hasL1 = activeLevels.includes(1);
            const hasL2 = activeLevels.includes(2);
            const hasL3 = activeLevels.includes(3);

            if (maxDepth === 1) {
                if (lvl === 1) {
                    flatKolom.forEach(function(item) {
                        cellsHtml += '<th class="th-data-col text-center" colspan="' + multiplier + '">';
                        cellsHtml += '<span>' + escapeHtml(item.item_ind) + '</span>';
                        if (isBilingual && item.item_en) {
                            cellsHtml += '<span class="th-en">' + escapeHtml(item.item_en) + '</span>';
                        }
                        cellsHtml += '</th>';
                    });
                }
            } else if (maxDepth === 2) {
                if (lvl === 1) {
                    (kolomStruct.groups || []).forEach(function(grp) {
                        if (grp.type === 'group') {
                            const leafCount = hasL2 ? grp.children.length : 1;
                            const colspan = leafCount * multiplier;
                            const thClass = hasL2 ? 'th-group-col text-center' : 'th-data-col text-center';
                            cellsHtml += '<th colspan="' + colspan + '" class="' + thClass + '">';
                            cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                            if (isBilingual && grp.title_en) {
                                cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                            }
                            cellsHtml += '</th>';
                        } else {
                            const colspan = 1 * multiplier;
                            const rowspan = hasL2 ? 2 : 1;
                            cellsHtml += '<th rowspan="' + rowspan + '" colspan="' + colspan + '" class="th-data-col text-center">';
                            cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                            if (isBilingual && grp.title_en) {
                                cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                            }
                            cellsHtml += '</th>';
                        }
                    });
                } else if (lvl === 2) {
                    (kolomStruct.groups || []).forEach(function(grp) {
                        if (grp.type === 'group') {
                            grp.children.forEach(function(child) {
                                cellsHtml += '<th colspan="' + multiplier + '" class="th-data-col text-center">';
                                cellsHtml += '<span>' + escapeHtml(child.item_ind) + '</span>';
                                if (isBilingual && child.item_en) {
                                    cellsHtml += '<span class="th-en">' + escapeHtml(child.item_en) + '</span>';
                                }
                                cellsHtml += '</th>';
                            });
                        } else {
                            if (!hasL1) {
                                cellsHtml += '<th colspan="' + multiplier + '" class="th-data-col text-center">';
                                cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                                if (isBilingual && grp.title_en) {
                                    cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                                }
                                cellsHtml += '</th>';
                            }
                        }
                    });
                }
            } else if (maxDepth === 3) {
                if (lvl === 1) {
                    (kolomStruct.groups || []).forEach(function(grp) {
                        if (grp.type === 'group') {
                            let leafCount = 0;
                            if (hasL3) {
                                leafCount = grp.children.length;
                            } else if (hasL2 && grp.subgroups && grp.subgroups.length > 0) {
                                leafCount = grp.subgroups.length;
                            } else {
                                leafCount = 1;
                            }
                            const colspan = leafCount * multiplier;
                            const thClass = (hasL2 || hasL3) ? 'th-group-col text-center' : 'th-data-col text-center';
                            cellsHtml += '<th colspan="' + colspan + '" class="' + thClass + '">';
                            cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                            if (isBilingual && grp.title_en) {
                                cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                            }
                            cellsHtml += '</th>';
                        } else {
                            const colspan = 1 * multiplier;
                            const rowspan = activeLevels.length;
                            cellsHtml += '<th rowspan="' + rowspan + '" colspan="' + colspan + '" class="th-data-col text-center">';
                            cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                            if (isBilingual && grp.title_en) {
                                cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                            }
                            cellsHtml += '</th>';
                        }
                    });
                } else if (lvl === 2) {
                    (kolomStruct.groups || []).forEach(function(grp) {
                        if (grp.type === 'group') {
                            if (grp.subgroups && grp.subgroups.length > 0) {
                                grp.subgroups.forEach(function(sub) {
                                    const leafCount = hasL3 ? sub.children.length : 1;
                                    const colspan = leafCount * multiplier;
                                    const thClass = (hasL3 && sub.type !== 'standalone') ? 'th-group-col text-center' : 'th-data-col text-center';
                                    const rowspan = (sub.type === 'standalone' && hasL3) ? 2 : 1;
                                    cellsHtml += '<th colspan="' + colspan + '" rowspan="' + rowspan + '" class="' + thClass + '">';
                                    cellsHtml += '<span>' + escapeHtml(sub.title_ind) + '</span>';
                                    if (isBilingual && sub.title_en) {
                                        cellsHtml += '<span class="th-en">' + escapeHtml(sub.title_en) + '</span>';
                                    }
                                    cellsHtml += '</th>';
                                });
                            } else {
                                if (hasL3) {
                                    grp.children.forEach(function(child) {
                                        cellsHtml += '<th colspan="' + multiplier + '" class="th-data-col text-center">';
                                        cellsHtml += '<span>' + escapeHtml(child.item_ind) + '</span>';
                                        if (isBilingual && child.item_en) {
                                            cellsHtml += '<span class="th-en">' + escapeHtml(child.item_en) + '</span>';
                                        }
                                        cellsHtml += '</th>';
                                    });
                                } else {
                                    cellsHtml += '<th colspan="' + multiplier + '" class="th-data-col text-center">';
                                    cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                                    if (isBilingual && grp.title_en) {
                                        cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                                    }
                                    cellsHtml += '</th>';
                                }
                            }
                        } else {
                            if (!hasL1) {
                                const colspan = 1 * multiplier;
                                const rowspan = hasL3 ? 2 : 1;
                                cellsHtml += '<th rowspan="' + rowspan + '" colspan="' + colspan + '" class="th-data-col text-center">';
                                cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                                if (isBilingual && grp.title_en) {
                                    cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                                }
                                cellsHtml += '</th>';
                            }
                        }
                    });
                } else if (lvl === 3) {
                    (kolomStruct.groups || []).forEach(function(grp) {
                        if (grp.type === 'group') {
                            if (grp.subgroups && grp.subgroups.length > 0) {
                                grp.subgroups.forEach(function(sub) {
                                    if (sub.type === 'standalone' && (hasL2 || hasL1)) {
                                        return;
                                    }
                                    sub.children.forEach(function(child) {
                                        cellsHtml += '<th colspan="' + multiplier + '" class="th-data-col text-center">';
                                        cellsHtml += '<span>' + escapeHtml(child.item_ind) + '</span>';
                                        if (isBilingual && child.item_en) {
                                            cellsHtml += '<span class="th-en">' + escapeHtml(child.item_en) + '</span>';
                                        }
                                        cellsHtml += '</th>';
                                    });
                                });
                            } else {
                                if (!hasL2 && !hasL1) {
                                    grp.children.forEach(function(child) {
                                        cellsHtml += '<th colspan="' + multiplier + '" class="th-data-col text-center">';
                                        cellsHtml += '<span>' + escapeHtml(child.item_ind) + '</span>';
                                        if (isBilingual && child.item_en) {
                                            cellsHtml += '<span class="th-en">' + escapeHtml(child.item_en) + '</span>';
                                        }
                                        cellsHtml += '</th>';
                                    });
                                }
                            }
                        } else {
                            if (!hasL1 && !hasL2) {
                                cellsHtml += '<th colspan="' + multiplier + '" class="th-data-col text-center">';
                                cellsHtml += '<span>' + escapeHtml(grp.title_ind) + '</span>';
                                if (isBilingual && grp.title_en) {
                                    cellsHtml += '<span class="th-en">' + escapeHtml(grp.title_en) + '</span>';
                                }
                                cellsHtml += '</th>';
                            }
                        }
                    });
                }
            }
            return cellsHtml;
        }

        // -------------------------------------------------------------
        // KASUS 0: Tanpa Dimensi Baris (Hanya Karakteristik Kolom & Periode Waktu)
        // -------------------------------------------------------------
        if (!hasBaris && hasKolom && hasPeriode) {
            theadHtml += '<tr>';
            theadHtml += '<th rowspan="' + totalHeaderRows + '" class="th-freeze-col text-center" style="vertical-align: middle;">';
            theadHtml += '<span>' + escapeHtml(cleanTitlePeriode) + '</span>';
            if (isBilingual && cleanTitlePeriodeEn && cleanTitlePeriodeEn.trim().toLowerCase() !== cleanTitlePeriode.trim().toLowerCase()) {
                theadHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(cleanTitlePeriodeEn) + '</span>';
            }
            theadHtml += '</th>';

            // Baris 1: Tingkat Kolom Pertama
            theadHtml += getLevelCells(activeLevels[0], 1);
            theadHtml += '</tr>';

            // Baris berikutnya jika ada tingkat lain
            for (let idx = 1; idx < activeLevels.length; idx++) {
                theadHtml += '<tr>';
                theadHtml += getLevelCells(activeLevels[idx], 1);
                theadHtml += '</tr>';
            }
        }

        // -------------------------------------------------------------
        // KASUS 1: Hanya Periode Waktu (Tanpa Karakteristik Kolom)
        // -------------------------------------------------------------
        else if (!hasKolom && hasPeriode) {
            theadHtml += '<tr>';
            theadHtml += getRowHeaderTh(totalHeaderRows);

            periodeItems.forEach(function(p) {
                theadHtml += '<th class="th-data-col text-center">';
                theadHtml += '<span>' + escapeHtml(p.nama_periode_ind) + '</span>';
                if (isBilingual && p.nama_periode_en && p.nama_periode_en !== p.nama_periode_ind) {
                    theadHtml += '<span class="th-en">' + escapeHtml(p.nama_periode_en) + '</span>';
                }
                theadHtml += '</th>';
            });
            theadHtml += '</tr>';
        }

        // -------------------------------------------------------------
        // KASUS 2: Hanya Karakteristik Kolom (Tanpa Periode Waktu)
        // -------------------------------------------------------------
        else if (hasKolom && !hasPeriode) {
            // Baris 1: Freeze Col + Tingkat Kolom Pertama
            theadHtml += '<tr>';
            theadHtml += getRowHeaderTh(totalHeaderRows);
            theadHtml += getLevelCells(activeLevels[0], 1);
            theadHtml += '</tr>';

            // Baris berikutnya jika ada tingkat lain
            for (let idx = 1; idx < activeLevels.length; idx++) {
                theadHtml += '<tr>';
                theadHtml += getLevelCells(activeLevels[idx], 1);
                theadHtml += '</tr>';
            }
        }

        // -------------------------------------------------------------
        // KASUS 3: Kedua Dimensi Ada (Kolom DAN Periode)
        // -------------------------------------------------------------
        else if (hasKolom && hasPeriode) {
            if (isRuntunWaktu) {
                // =========================================================
                // SUB-KASUS 3D: RUNTUN WAKTU PADA BARIS TOTAL (MULTI-TAHUN)
                // =========================================================
                theadHtml += '<tr>';
                theadHtml += '<th rowspan="' + totalHeaderRows + '" colspan="2" class="th-freeze-col text-start">';
                theadHtml += '<span>' + escapeHtml(cleanTitleInd) + '</span>';
                if (isBilingual && titleBarisEn && titleBarisEn.trim().toLowerCase() !== cleanTitleInd.trim().toLowerCase()) {
                    theadHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleBarisEn) + '</span>';
                }
                theadHtml += '</th>';

                theadHtml += getLevelCells(activeLevels[0], 1);
                theadHtml += '</tr>';

                for (let idx = 1; idx < activeLevels.length; idx++) {
                    theadHtml += '<tr>';
                    theadHtml += getLevelCells(activeLevels[idx], 1);
                    theadHtml += '</tr>';
                }

            } else if (isPeriodeBaris) {
                // =========================================================
                // SUB-KASUS 3C: PERIODE MENJADI BARIS (PIVOT KE BARIS)
                // =========================================================
                theadHtml += '<tr>';
                if (!isPeriodeBarisPisah) {
                    // MODE GABUNG HEADER: Kolom 1 & 2 disatukan (1 kesatuan kolom seperti Tabel 4.6.1)
                    theadHtml += '<th rowspan="' + totalHeaderRows + '" colspan="2" class="th-freeze-col text-start">';
                    theadHtml += '<span>' + escapeHtml(cleanTitleInd) + '</span>';
                    if (isBilingual && titleBarisEn && titleBarisEn.trim().toLowerCase() !== cleanTitleInd.trim().toLowerCase()) {
                        theadHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleBarisEn) + '</span>';
                    }
                    theadHtml += '</th>';
                } else {
                    // MODE PISAH HEADER: Dua kolom stub dengan judul masing-masing
                    theadHtml += '<th rowspan="' + totalHeaderRows + '" class="th-freeze-col text-start">';
                    theadHtml += '<span>' + escapeHtml(cleanTitleInd) + '</span>';
                    if (isBilingual && titleBarisEn && titleBarisEn.trim().toLowerCase() !== cleanTitleInd.trim().toLowerCase()) {
                        theadHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(titleBarisEn) + '</span>';
                    }
                    theadHtml += '</th>';

                    theadHtml += '<th rowspan="' + totalHeaderRows + '" class="th-freeze-col-2 text-center">';
                    theadHtml += '<span>' + escapeHtml(cleanTitlePeriode) + '</span>';
                    if (isBilingual && cleanTitlePeriodeEn && cleanTitlePeriodeEn.trim().toLowerCase() !== cleanTitlePeriode.trim().toLowerCase()) {
                        theadHtml += '<span class="th-en">' + escapeHtml(cleanTitlePeriodeEn) + '</span>';
                    }
                    theadHtml += '</th>';
                }

                theadHtml += getLevelCells(activeLevels[0], 1);
                theadHtml += '</tr>';

                // Baris berikutnya jika ada tingkat kolom lanjutan
                for (let idx = 1; idx < activeLevels.length; idx++) {
                    theadHtml += '<tr>';
                    theadHtml += getLevelCells(activeLevels[idx], 1);
                    theadHtml += '</tr>';
                }

            } else if (isPeriodeAtas) {
                // =========================================================
                // SUB-KASUS 3B: PERIODE WAKTU DI ATAS, KOLOM DI BAWAH
                // =========================================================
                theadHtml += '<tr>';
                theadHtml += getRowHeaderTh(totalHeaderRows);

                // Baris 1: Periode Waktu
                periodeItems.forEach(function(p) {
                    theadHtml += '<th colspan="' + flatKolom.length + '" class="th-group-col text-center">';
                    theadHtml += '<span>' + escapeHtml(p.nama_periode_ind) + '</span>';
                    if (isBilingual && p.nama_periode_en && p.nama_periode_en !== p.nama_periode_ind) {
                        theadHtml += '<span class="th-en">' + escapeHtml(p.nama_periode_en) + '</span>';
                    }
                    theadHtml += '</th>';
                });
                theadHtml += '</tr>';

                // Baris 2..N: Tingkat-tingkat Kolom di bawah setiap periode
                activeLevels.forEach(function(lvl) {
                    theadHtml += '<tr>';
                    periodeItems.forEach(function() {
                        theadHtml += getLevelCells(lvl, 1);
                    });
                    theadHtml += '</tr>';
                });

            } else {
                // =========================================================
                // SUB-KASUS 3A: KARAKTERISTIK KOLOM DI ATAS, PERIODE DI BAWAH
                // =========================================================
                theadHtml += '<tr>';
                theadHtml += getRowHeaderTh(totalHeaderRows);

                // Baris 1: Tingkat Kolom Pertama
                theadHtml += getLevelCells(activeLevels[0], periodeItems.length);
                theadHtml += '</tr>';

                // Baris 2..N: Tingkat Kolom Selanjutnya
                for (let idx = 1; idx < activeLevels.length; idx++) {
                    theadHtml += '<tr>';
                    theadHtml += getLevelCells(activeLevels[idx], periodeItems.length);
                    theadHtml += '</tr>';
                }

                // Baris Terbawah: Periode Waktu di bawah setiap kolom daun
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
        }

        $('#matrix_thead').html(theadHtml);

        // =====================================================================
        // 2. RENDER TBODY (BARIS BERTINGKAT 3 LEVEL & SEL PLACEHOLDER STRIP)
        // =====================================================================
        let tbodyHtml = '';
        const placeholderSimbol = '–'; // Tanda strip standar BPS (data tidak ada)

        if (barisItems.length === 0) {
            if (!hasBaris && hasKolom && hasPeriode) {
                // RENDER TBODY KHUSUS: PERIODE MENJADI BARIS (RUNTUN WAKTU MAKRO / PROVINSI)
                periodeItems.forEach(function(p) {
                    tbodyHtml += '<tr class="row-data">';
                    tbodyHtml += '<td class="td-freeze-col text-center fw-semibold text-dark" style="vertical-align: middle; font-size: 0.85rem;">';
                    tbodyHtml += '<span>' + escapeHtml(p.nama_periode_ind) + '</span>';
                    if (isBilingual && p.nama_periode_en && p.nama_periode_en !== p.nama_periode_ind) {
                        tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.78rem; font-weight: normal;">/ ' + escapeHtml(p.nama_periode_en) + '</span>';
                    }
                    tbodyHtml += '</td>';
                    for (let i = 0; i < totalLeafCols; i++) {
                        tbodyHtml += '<td class="cell-numeric-placeholder">–</td>';
                    }
                    tbodyHtml += '</tr>';
                });
            } else {
                const emptyColspan = (isPeriodeBaris ? (isMultiBaris ? 3 : 2) : (isMultiBaris ? 2 : 1)) + totalLeafCols;
                tbodyHtml = '<tr><td colspan="' + emptyColspan + '" class="text-center py-4 text-muted">Belum ada baris dalam master yang dipilih.</td></tr>';
            }
        } else if (hasKolom && hasPeriode && isPeriodeBaris && !isRuntunWaktu) {
            // RENDER TBODY KHUSUS: PERIODE MENJADI BARIS (NESTED PIVOT)
            barisItems.forEach(function(baris, bIdx) {
                let tipe = (baris.tipe_baris || '').toLowerCase().trim();
                let lowerNama = (baris.nama_baris_ind || '').toLowerCase().trim();
                if (!tipe) {
                    if (
                        lowerNama === 'jumlah' || lowerNama === 'total' || 
                        lowerNama.includes('jawa tengah') || 
                        lowerNama.includes('produk domestik regional bruto') || 
                        lowerNama === 'pdrb' || 
                        lowerNama.includes('gross regional domestic product') || 
                        lowerNama === 'grdp'
                    ) {
                        if (!lowerNama.includes('curah hujan') && !lowerNama.includes('hari hujan')) {
                            tipe = 'total';
                        }
                    }
                    if (!tipe) tipe = 'data';
                }

                // Pengaturan baris total (sembunyikan jika opsi 'none' dipilih)
                if (tipe === 'total' && konfigurasiTotal === 'none') {
                    return;
                }

                const isParent = isParentBaris(bIdx, barisItems);
                const level = parseInt(baris.level_indentasi) || 0;
                let rowClass = isParent ? 'row-kategori' : ('row-' + tipe);
                let indentClass = 'indent-level-' + Math.min(level, 2);
                let isBold = (parseInt(baris.is_bold) === 1 || tipe === 'total' || isParent);
                let textClass = isBold ? ' fw-bold text-dark' : ' text-dark';

                // Tentukan nama tampilan (override jika baris total)
                let namaBarisInd = baris.nama_baris_ind;
                let namaBarisEn = baris.nama_baris_en;
                if (tipe === 'total') {
                    if (konfigurasiTotal === 'jawa_tengah') {
                        namaBarisInd = 'Jawa Tengah';
                        namaBarisEn = 'Central Java';
                    } else if (konfigurasiTotal === 'jumlah') {
                        namaBarisInd = 'Jumlah';
                        namaBarisEn = 'Total';
                    } else if (konfigurasiTotal === 'kustom' && labelTotalKustom.trim() !== '') {
                        namaBarisInd = labelTotalKustom.trim();
                        namaBarisEn = labelTotalKustom.trim();
                    }
                }

                if (isParent) {
                    // Baris Induk / Header Level Atas (misal: "Kabupaten", "Kota", "Suhu"): Bentang penuh seluruh kolom tanpa kolom periode
                    const parentColspan = (isMultiBaris ? 3 : 2) + totalLeafCols;
                    tbodyHtml += '<tr class="' + rowClass + '">';
                    tbodyHtml += '<td colspan="' + parentColspan + '" class="td-freeze-col ' + indentClass + textClass + ' px-3 py-2" style="background-color: #f8fafc !important; border-left: 3px solid #ff6d1f !important;">';
                    tbodyHtml += '<span>' + escapeHtml(namaBarisInd) + '</span>';
                    if (isBilingual && namaBarisEn && namaBarisEn.trim().toLowerCase() !== namaBarisInd.trim().toLowerCase()) {
                        tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(namaBarisEn) + '</span>';
                    }
                    tbodyHtml += '</td>';
                    tbodyHtml += '</tr>';
                } else {
                    // Baris Rincian Daun (Leaf / Level Terakhir): Memiliki kolom kedua periode dan sel data
                    const pCount = periodeItems.length;
                    periodeItems.forEach(function(p, pIdx) {
                        tbodyHtml += '<tr class="' + rowClass + '">';

                        if (isMultiBaris) {
                            let col1Ind = namaBarisInd || '';
                            let col2Ind = '';
                            let col1En = namaBarisEn || '';
                            let col2En = '';

                            if (col1Ind.includes('||')) {
                                let pInd = col1Ind.split('||');
                                col1Ind = pInd[0].trim();
                                col2Ind = pInd[1].trim();
                            }
                            if (col1En.includes('||')) {
                                let pEn = col1En.split('||');
                                col1En = pEn[0].trim();
                                col2En = pEn[1].trim();
                            }

                            if (pIdx === 0) {
                                if (col2Ind) {
                                    tbodyHtml += '<td rowspan="' + pCount + '" class="td-freeze-col ' + indentClass + textClass + '" style="vertical-align: middle;">';
                                    tbodyHtml += '<span>' + escapeHtml(col1Ind) + '</span>';
                                    if (isBilingual && col1En && col1En.trim().toLowerCase() !== col1Ind.trim().toLowerCase()) {
                                        tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(col1En) + '</span>';
                                    }
                                    tbodyHtml += '</td>';

                                    tbodyHtml += '<td rowspan="' + pCount + '" class="td-freeze-col-2 text-center ' + textClass + '" style="vertical-align: middle; font-size: 0.82rem;">';
                                    tbodyHtml += '<span>' + escapeHtml(col2Ind) + '</span>';
                                    if (isBilingual && col2En && col2En.trim().toLowerCase() !== col2Ind.trim().toLowerCase()) {
                                        tbodyHtml += '<span class="th-en" style="font-size: 0.72rem;">' + escapeHtml(col2En) + '</span>';
                                    }
                                    tbodyHtml += '</td>';
                                } else {
                                    tbodyHtml += '<td rowspan="' + pCount + '" colspan="2" class="td-freeze-col ' + indentClass + textClass + '" style="vertical-align: middle;">';
                                    tbodyHtml += '<span>' + escapeHtml(col1Ind) + '</span>';
                                    if (isBilingual && col1En && col1En.trim().toLowerCase() !== col1Ind.trim().toLowerCase()) {
                                        tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(col1En) + '</span>';
                                    }
                                    tbodyHtml += '</td>';
                                }
                            }
                        } else {
                            // Sel Kolom 1 (Nama Wilayah / Baris): Hanya di baris periode pertama dengan rowspan
                            if (pIdx === 0) {
                                tbodyHtml += '<td rowspan="' + pCount + '" class="td-freeze-col ' + indentClass + textClass + '" style="vertical-align: middle;">';
                                tbodyHtml += '<span>' + escapeHtml(namaBarisInd) + '</span>';
                                if (isBilingual && namaBarisEn && namaBarisEn.trim().toLowerCase() !== namaBarisInd.trim().toLowerCase()) {
                                    tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(namaBarisEn) + '</span>';
                                }
                                tbodyHtml += '</td>';
                            }
                        }

                        // Sel Kolom Periode: Selalu muncul di setiap baris periode
                        let periodeTdClass = isMultiBaris ? 'text-center fw-semibold text-secondary' : 'td-freeze-col-2 text-center fw-semibold text-secondary';
                        tbodyHtml += '<td class="' + periodeTdClass + '" style="vertical-align: middle; font-size: 0.82rem;">';
                        tbodyHtml += '<span>' + escapeHtml(p.nama_periode_ind) + '</span>';
                        if (isBilingual && p.nama_periode_en && p.nama_periode_en !== p.nama_periode_ind) {
                            tbodyHtml += '<span class="th-en" style="font-size: 0.72rem;">' + escapeHtml(p.nama_periode_en) + '</span>';
                        }
                        tbodyHtml += '</td>';

                        // Sel-sel Data Kolom (Placeholder Strip)
                        for (let i = 0; i < totalLeafCols; i++) {
                            tbodyHtml += '<td class="cell-numeric-placeholder">–</td>';
                        }

                        tbodyHtml += '</tr>';
                    });
                }
            });
        } else {
            let hasRenderedRuntunTotal = false;

            // Pre-compute auto-rowspan untuk Kolom 1 jika nilainya sama berturut-turut
            // HANYA berlaku untuk Kolom 1. Kolom 2 (Satuan/Bulan/Provinsi) DIKUNCI MATI (tidak pernah di-merge)!
            let col1RowspanMap = {};
            let col1SkipSet = new Set();

            if (isMultiBaris) {
                let i = 0;
                while (i < barisItems.length) {
                    let curBaris = barisItems[i];
                    let curNama = curBaris.nama_baris_ind || '';
                    let isParent = isParentBaris(i, barisItems);
                    
                    if (isParent || !curNama.includes('||')) {
                        i++;
                        continue;
                    }
                    
                    let curCol1 = curNama.split('||')[0].trim();
                    let span = 1;
                    let j = i + 1;
                    while (j < barisItems.length) {
                        let nextBaris = barisItems[j];
                        let nextIsParent = isParentBaris(j, barisItems);
                        if (nextIsParent) break;
                        
                        let nextNama = nextBaris.nama_baris_ind || '';
                        if (!nextNama.includes('||')) break;
                        
                        let nextCol1 = nextNama.split('||')[0].trim();
                        if (nextCol1 === curCol1) {
                            span++;
                            col1SkipSet.add(j);
                            j++;
                        } else {
                            break;
                        }
                    }
                    if (span > 1) {
                        col1RowspanMap[i] = span;
                    }
                    i = j;
                }
            }

            // Pre-hitung jumlah anak untuk mode 2 kolom (Induk Komoditi & Rincian Provinsi)
            let childCountMap = {};
            if (isBarisDuaKolom) {
                for (let i = 0; i < barisItems.length; i++) {
                    const lvl = parseInt(barisItems[i].level_indentasi) || 0;
                    if (lvl === 0) {
                        let count = 0;
                        for (let j = i + 1; j < barisItems.length; j++) {
                            const nextLvl = parseInt(barisItems[j].level_indentasi) || 0;
                            if (nextLvl > 0) {
                                count++;
                            } else {
                                break;
                            }
                        }
                        if (count > 0) {
                            childCountMap[i] = count;
                        }
                    }
                }
            }
            let currentParentInd = '';
            let currentParentEn = '';
            let currentParentRowspan = 1;
            let isFirstChildOfParent = false;

            barisItems.forEach(function(baris, bIdx) {
                let tipe = (baris.tipe_baris || '').toLowerCase().trim();
                let lowerNama = (baris.nama_baris_ind || '').toLowerCase().trim();
                if (!tipe) {
                    if (
                        lowerNama === 'jumlah' || lowerNama === 'total' || 
                        lowerNama.includes('jawa tengah') || 
                        lowerNama.includes('produk domestik regional bruto') || 
                        lowerNama === 'pdrb' || 
                        lowerNama.includes('gross regional domestic product') || 
                        lowerNama === 'grdp'
                    ) {
                        if (!lowerNama.includes('curah hujan') && !lowerNama.includes('hari hujan')) {
                            tipe = 'total';
                        }
                    } else if (
                        lowerNama.startsWith('jumlah ') || lowerNama.startsWith('total ') || 
                        lowerNama.startsWith('subtotal') || lowerNama.startsWith('sub total') || 
                        lowerNama.startsWith('sub-total') || lowerNama.startsWith('sub jumlah')
                    ) {
                        if (!lowerNama.includes('curah hujan') && !lowerNama.includes('hari hujan') && !/^jumlah\s+(curah|hari|penduduk|kasus|sekolah|guru|murid|jiwa|balita|kk|desa|kelurahan|sarana|penerima|armada|tindak|kejadian|korban|kelahiran|kematian|pasien|peserta|tenaga)/i.test(lowerNama)) {
                            tipe = 'subtotal';
                        }
                    }
                    if (!tipe) tipe = 'data';
                }

                // Pengaturan baris total (sembunyikan jika opsi 'none' dipilih)
                if (tipe === 'total' && konfigurasiTotal === 'none') {
                    return;
                }

                const isParent = isParentBaris(bIdx, barisItems);

                const level = parseInt(baris.level_indentasi) || 0;
                let rowClass = isParent ? 'row-kategori' : ('row-' + tipe);
                let indentClass = 'indent-level-' + Math.min(level, 2);

                // Aturan Ketebalan Huruf (Bold):
                let isBold = false;
                if (totalTingkatBaris >= 2 && level === 0) {
                    isBold = true;
                } else if (tipe === 'total' || tipe === 'subtotal' || isParent) {
                    isBold = true;
                } else if (parseInt(baris.is_bold) === 1) {
                    isBold = true;
                }

                let textClass = isBold ? ' fw-bold text-dark' : ' text-dark';

                // Tentukan nama tampilan (override jika baris total)
                let namaBarisInd = baris.nama_baris_ind;
                let namaBarisEn = baris.nama_baris_en;
                if (tipe === 'total') {
                    if (konfigurasiTotal === 'jawa_tengah') {
                        namaBarisInd = 'Jawa Tengah';
                        namaBarisEn = 'Central Java';
                    } else if (konfigurasiTotal === 'jumlah') {
                        namaBarisInd = 'Jumlah';
                        namaBarisEn = 'Total';
                    } else if (konfigurasiTotal === 'kustom' && labelTotalKustom.trim() !== '') {
                        namaBarisInd = labelTotalKustom.trim();
                        namaBarisEn = labelTotalKustom.trim();
                    } else if (konfigurasiTotal === 'runtun_waktu') {
                        namaBarisInd = 'Jumlah';
                        namaBarisEn = 'Total';
                    }
                }

                // Penanganan Khusus Mode Runtun Waktu pada Baris Total
                if (tipe === 'total' && isRuntunWaktu) {
                    hasRenderedRuntunTotal = true;
                    const pCount = periodeItems.length;
                    periodeItems.forEach(function(p, pIdx) {
                        tbodyHtml += '<tr class="row-total" style="background-color: #fff5ed;">';
                        if (pIdx === 0) {
                            tbodyHtml += '<td rowspan="' + pCount + '" class="td-freeze-col fw-bold text-dark" style="vertical-align: middle; background-color: #fff5ed; border-right: 1px solid #fed7aa;">';
                            tbodyHtml += '<span>' + escapeHtml(namaBarisInd) + '</span>';
                            if (isBilingual && namaBarisEn && namaBarisEn.trim().toLowerCase() !== namaBarisInd.trim().toLowerCase()) {
                                tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(namaBarisEn) + '</span>';
                            }
                            tbodyHtml += '</td>';
                        }
                        tbodyHtml += '<td class="text-center fw-bold text-dark" style="vertical-align: middle; font-size: 0.82rem; background-color: #ffedd5; border: 1px solid #fed7aa;">';
                        tbodyHtml += '<span>' + escapeHtml(p.nama_periode_ind) + '</span>';
                        tbodyHtml += '</td>';

                        for (let i = 0; i < totalLeafCols; i++) {
                            tbodyHtml += '<td class="cell-numeric-placeholder fw-bold" style="background-color: #fff5ed; border: 1px solid #fed7aa; color: #ea580c;">–</td>';
                        }
                        tbodyHtml += '</tr>';
                    });
                    return;
                }


                // Mode 2 Kolom Baris: Induk di Kolom 1 (rowspan), Rincian Anak di Kolom 2
                if (isBarisDuaKolom) {
                    const hasChildren = (childCountMap[bIdx] || 0) > 0;
                    if (level === 0 && hasChildren) {
                        currentParentInd = namaBarisInd;
                        currentParentEn = namaBarisEn;
                        currentParentRowspan = childCountMap[bIdx];
                        isFirstChildOfParent = true;
                        return; // Induk tidak membuat baris tr sendiri, melainkan di-rowspan pada anak pertama di Kolom 1
                    } else if (level > 0) {
                        tbodyHtml += '<tr class="' + rowClass + '">';

                        // Sel Kolom 1 (Induk): hanya dirender pada anak pertama dengan rowspan
                        if (isFirstChildOfParent) {
                            tbodyHtml += '<td rowspan="' + currentParentRowspan + '" class="td-freeze-col fw-bold text-dark" style="vertical-align: middle; background-color: #ffffff;">';
                            tbodyHtml += '<span>' + escapeHtml(currentParentInd) + '</span>';
                            if (isBilingual && currentParentEn && currentParentEn.trim().toLowerCase() !== currentParentInd.trim().toLowerCase()) {
                                tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(currentParentEn) + '</span>';
                            }
                            tbodyHtml += '</td>';
                            isFirstChildOfParent = false;
                        }

                        // Sel Kolom 2 (Rincian Anak, misal: coba / Provinsi)
                        tbodyHtml += '<td class="td-freeze-col-2' + textClass + '" style="vertical-align: middle;">';
                        tbodyHtml += '<span>' + escapeHtml(namaBarisInd) + '</span>';
                        if (isBilingual && namaBarisEn && namaBarisEn.trim().toLowerCase() !== namaBarisInd.trim().toLowerCase()) {
                            tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(namaBarisEn) + '</span>';
                        }
                        tbodyHtml += '</td>';

                        // Sel Data Kolom (Placeholder Strip)
                        for (let i = 0; i < totalLeafCols; i++) {
                            tbodyHtml += '<td class="cell-numeric-placeholder">–</td>';
                        }
                        tbodyHtml += '</tr>';
                        return;
                    } else {
                        // Level 0 mandiri atau baris total tanpa anak: bentang penuh 2 kolom stub
                        tbodyHtml += '<tr class="' + rowClass + '">';
                        tbodyHtml += '<td colspan="2" class="td-freeze-col ' + indentClass + textClass + '">';
                        tbodyHtml += '<span>' + escapeHtml(namaBarisInd) + '</span>';
                        if (isBilingual && namaBarisEn && namaBarisEn.trim().toLowerCase() !== namaBarisInd.trim().toLowerCase()) {
                            tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(namaBarisEn) + '</span>';
                        }
                        tbodyHtml += '</td>';

                        for (let i = 0; i < totalLeafCols; i++) {
                            tbodyHtml += '<td class="cell-numeric-placeholder">–</td>';
                        }
                        tbodyHtml += '</tr>';
                        return;
                    }
                }

                if (isParent) {
                    // Baris Induk / Header Level Atas: Bentang penuh seluruh kolom
                    const parentColspan = ((isMultiBaris || isRuntunWaktu) ? 2 : 1) + totalLeafCols;
                    tbodyHtml += '<tr class="' + rowClass + '">';
                    tbodyHtml += '<td colspan="' + parentColspan + '" class="td-freeze-col ' + indentClass + textClass + ' px-3 py-2" style="background-color: #f8fafc !important; border-left: 3px solid #ff6d1f !important;">';
                    tbodyHtml += '<span>' + escapeHtml(namaBarisInd) + '</span>';
                    if (isBilingual && namaBarisEn && namaBarisEn.trim().toLowerCase() !== namaBarisInd.trim().toLowerCase()) {
                        tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(namaBarisEn) + '</span>';
                    }
                    tbodyHtml += '</td>';
                    tbodyHtml += '</tr>';
                } else {
                    tbodyHtml += '<tr class="' + rowClass + '">';

                    if (isMultiBaris) {
                        let col1Ind = namaBarisInd || '';
                        let col2Ind = '';
                        let col1En = namaBarisEn || '';
                        let col2En = '';

                        if (col1Ind.includes('||')) {
                            let pInd = col1Ind.split('||');
                            col1Ind = pInd[0].trim();
                            col2Ind = pInd[1].trim();
                        }
                        if (col1En.includes('||')) {
                            let pEn = col1En.split('||');
                            col1En = pEn[0].trim();
                            col2En = pEn[1].trim();
                        }

                        if (col2Ind) {
                            if (!col1SkipSet.has(bIdx)) {
                                let spanAttr = col1RowspanMap[bIdx] ? (' rowspan="' + col1RowspanMap[bIdx] + '" style="vertical-align: middle;"') : '';
                                tbodyHtml += '<td class="td-freeze-col ' + indentClass + textClass + '"' + spanAttr + '>';
                                tbodyHtml += '<span>' + escapeHtml(col1Ind) + '</span>';
                                if (isBilingual && col1En && col1En.trim().toLowerCase() !== col1Ind.trim().toLowerCase()) {
                                    tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(col1En) + '</span>';
                                }
                                tbodyHtml += '</td>';
                            }

                            tbodyHtml += '<td class="td-freeze-col-2 text-center ' + textClass + '" style="font-size: 0.82rem; vertical-align: middle;">';
                            tbodyHtml += '<span>' + escapeHtml(col2Ind) + '</span>';
                            if (isBilingual && col2En && col2En.trim().toLowerCase() !== col2Ind.trim().toLowerCase()) {
                                tbodyHtml += '<span class="th-en" style="font-size: 0.72rem;">' + escapeHtml(col2En) + '</span>';
                            }
                            tbodyHtml += '</td>';
                        } else {
                            tbodyHtml += '<td colspan="2" class="td-freeze-col ' + indentClass + textClass + '">';
                            tbodyHtml += '<span>' + escapeHtml(col1Ind) + '</span>';
                            if (isBilingual && col1En && col1En.trim().toLowerCase() !== col1Ind.trim().toLowerCase()) {
                                tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(col1En) + '</span>';
                            }
                            tbodyHtml += '</td>';
                        }
                    } else {
                        // Kolom Judul Baris (Sticky Freeze di Kiri)
                        const colSpanAttr = isRuntunWaktu ? ' colspan="2"' : '';
                        tbodyHtml += '<td' + colSpanAttr + ' class="td-freeze-col ' + indentClass + textClass + '">';
                        tbodyHtml += '<span>' + escapeHtml(namaBarisInd) + '</span>';
                        if (isBilingual && namaBarisEn && namaBarisEn.trim().toLowerCase() !== namaBarisInd.trim().toLowerCase()) {
                            tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ ' + escapeHtml(namaBarisEn) + '</span>';
                        }
                        tbodyHtml += '</td>';
                    }

                    // Sel Data Kolom (Seluruh sel menampilkan tanda strip standar BPS)
                    for (let i = 0; i < totalLeafCols; i++) {
                        tbodyHtml += '<td class="cell-numeric-placeholder">–</td>';
                    }

                    tbodyHtml += '</tr>';
                }
            });

            // Jika mode Runtun Waktu aktif namun master baris tidak memiliki item total khusus, otomatis tambahkan blok total
            if (isRuntunWaktu && !hasRenderedRuntunTotal && periodeItems.length > 0) {
                const pCount = periodeItems.length;
                periodeItems.forEach(function(p, pIdx) {
                    tbodyHtml += '<tr class="row-total" style="background-color: #fff5ed;">';
                    if (pIdx === 0) {
                        tbodyHtml += '<td rowspan="' + pCount + '" class="td-freeze-col fw-bold text-dark" style="vertical-align: middle; background-color: #fff5ed; border-right: 1px solid #fed7aa;">';
                        tbodyHtml += '<span>Jumlah</span>';
                        if (isBilingual) {
                            tbodyHtml += ' <span class="fst-italic text-secondary" style="font-size: 0.82rem; font-weight: normal;">/ Total</span>';
                        }
                        tbodyHtml += '</td>';
                    }
                    tbodyHtml += '<td class="text-center fw-bold text-dark" style="vertical-align: middle; font-size: 0.82rem; background-color: #ffedd5; border: 1px solid #fed7aa;">';
                    tbodyHtml += '<span>' + escapeHtml(p.nama_periode_ind) + '</span>';
                    tbodyHtml += '</td>';

                    for (let i = 0; i < totalLeafCols; i++) {
                        tbodyHtml += '<td class="cell-numeric-placeholder fw-bold" style="background-color: #fff5ed; border: 1px solid #fed7aa; color: #ea580c;">–</td>';
                    }
                    tbodyHtml += '</tr>';
                });
            }
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

    // Handler Buka Kunci & Reset Data Sel Matriks
    $('#btn_unlock_reset_data').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Buka Kunci & Reset Data?',
            html: `
                <div class="text-start text-sm">
                    <p class="mb-2">Tabel ini saat ini memiliki <b><?= number_format($total_nilai, 0, ',', '.'); ?> data nilai sel</b> yang tersimpan di database.</p>
                    <div class="alert alert-danger py-2 px-3 text-xs mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <b>PERHATIAN:</b> Mengubah dimensi baris, kolom, atau periode akan merusak relasi koordinat data yang lama.
                    </div>
                    <p class="mb-0 text-xs text-muted">
                        Jika Anda membuka kunci, <b>seluruh <?= number_format($total_nilai, 0, ',', '.'); ?> data nilai sel pada tabel ini akan dihapus secara permanen</b> dari database. Setelah itu Anda dapat memilih ulang dimensi dan mengunggah/mengisi data baru.
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Ya, Hapus Data & Buka Kunci',
            cancelButtonText: 'Batal',
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses Reset Data...',
                    text: 'Mohon tunggu sebentar, sedang membersihkan sel matriks.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '<?= base_url('admin/ajax_reset_nilai_tabel'); ?>',
                    type: 'POST',
                    data: {
                        id_tahun_tabel: <?= (int)$tabel->id_tahun_tabel; ?>
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Kunci Dibuka!',
                                text: res.message || 'Data berhasil direset. Halaman akan dimuat ulang.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', res.message || 'Terjadi kesalahan saat mereset data.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errMsg = 'Gagal menghubungi server.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errMsg, 'error');
                    }
                });
            }
        });
    });

    // Handler Konfirmasi SweetAlert2 saat melepas status dinamis
    $('.btn-unlink-dinamis').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const hasData = <?= $total_nilai > 0 ? 'true' : 'false'; ?>;
        const totalData = '<?= number_format($total_nilai, 0, ',', '.'); ?>';

        let warningHtml = '<p class="text-xs mb-1">Tabel akan dikembalikan ke format non-dinamis (spreadsheet biasa). Konfigurasi dimensi akan dihapus.</p>';
        if (hasData) {
            warningHtml += `<div class="alert alert-danger py-2 px-3 text-xs mt-2 text-start"><i class="bi bi-exclamation-triangle-fill me-1"></i><b>Perhatian:</b> Sebanyak <b>${totalData} data nilai sel</b> yang tersimpan di tabel ini juga akan ikut dihapus secara permanen.</div>`;
        }

        Swal.fire({
            title: 'Lepas Status Tabel Dinamis?',
            html: warningHtml,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Lepas Status Dinamis',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});
</script>
