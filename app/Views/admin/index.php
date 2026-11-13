<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>.:: Jateng Dalam Angka ::.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>" id="meta-csrf">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts Preconnect & Stylesheet -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=optional" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo base_url(); ?>aset/favicon.png">

    <!-- jQuery & jQuery UI (tetap dipertahankan untuk AJAX & plugin) -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>aset/js/jquery/jquery-ui.css" />
    <script src="<?php echo base_url(); ?>aset/js/jquery.min.js"></script>
    <script src="<?php echo base_url(); ?>aset/js/jquery/jquery-ui.js"></script>

    <!-- Chart Libraries - Conditional Load (Hanya di Dashboard) -->
    <?php if ($page == 'd_amain' || $page == 'dashboard'): ?>
        <script src="<?php echo base_url(); ?>aset/js/highcharts.js"></script>
        <script src="<?php echo base_url(); ?>aset/js/highcharts-3d.js"></script>
        <script src="<?php echo base_url(); ?>aset/js/exporting.js"></script>
        <script src="<?php echo base_url(); ?>aset/js/solid-gauge.js"></script>
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 85px;
            --sidebar-bg: #ffffff;
            --sidebar-hover: rgba(21, 70, 121, 0.06);
            --primary-color: #154679;
            --content-bg: #f5f7f9;
            --bs-primary: #154679;
            --bs-primary-rgb: 21, 70, 121;
            --bs-link-color: #154679;
            --bs-link-hover-color: #0f355c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--content-bg);
        }

        /* ===== OVERRIDE BOOTSTRAP PRIMARY ===== */
        .text-primary {
            color: #154679 !important;
        }

        .text-primary-orange {
            color: #154679 !important;
        }

        .bg-primary {
            background-color: #154679 !important;
        }

        .bg-primary-orange {
            background-color: #154679 !important;
        }

        .bg-primary-subtle,
        .bg-primary-soft {
            background-color: rgba(21, 70, 121, 0.08) !important;
        }

        .border-primary-subtle,
        .border-primary-soft {
            border-color: rgba(21, 70, 121, 0.2) !important;
        }

        .shadow-primary {
            box-shadow: 0 4px 6px rgba(21, 70, 121, 0.11), 0 1px 3px rgba(21, 70, 121, 0.08) !important;
        }

        .btn-primary {
            background-color: #154679 !important;
            border-color: #154679 !important;
        }

        .btn-primary:hover {
            background-color: #0f355c !important;
            border-color: #0b2847 !important;
        }

        .btn-outline-primary {
            color: #154679 !important;
            border-color: #154679 !important;
        }

        .btn-outline-primary:hover {
            background-color: #154679 !important;
            border-color: #154679 !important;
            color: #fff !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #154679;
            box-shadow: 0 0 0 0.25rem rgba(21, 70, 121, 0.15);
        }

        a {
            color: #154679;
        }

        a:hover {
            color: #0f355c;
        }

        /* ===== PAGINATION ORANGE OVERRIDE ===== */
        .pagination .page-link {
            transition: all 0.2s;
            color: #64748b;
            border-radius: 6px;
        }

        .pagination .page-item.active .page-link {
            background-color: #154679 !important;
            border-color: #154679 !important;
            color: #fff !important;
            box-shadow: 0 4px 6px rgba(21, 70, 121, 0.2) !important;
        }

        .pagination .page-link:hover {
            background-color: #f8f9fa;
            color: #154679;
        }

        .pagination .page-link:focus {
            box-shadow: 0 0 0 0.25rem rgba(21, 70, 121, 0.1) !important;
        }

        /* ===== CORE ADMIN TYPOGRAPHY & TABLE UTILITIES (MENCEGAH FOUC / LAYOUT JUMP) ===== */
        .ls-1 { letter-spacing: 0.8px; }
        .text-xxs { font-size: 0.75rem !important; }
        .text-xs { font-size: 0.82rem !important; }
        .text-sm { font-size: 0.92rem !important; }
        .font-weight-bolder { font-weight: 800 !important; }

        .border-radius-2xl { border-radius: 1.25rem !important; }
        .border-radius-lg { border-radius: 0.6rem !important; }
        .border-radius-md { border-radius: 0.4rem !important; }

        /* Global Sticky Header & Slim Scrollbar untuk Tabel Admin */
        .table-scroll-container {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .table-scroll-container thead th,
        .table-sticky-header thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8fafc !important;
            box-shadow: inset 0 -2px 0 #e2e8f0;
            vertical-align: middle !important;
        }

        .table-scroll-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ===== WRAPPER LAYOUT ===== */
        #wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
            max-width: 100vw;
            overflow: hidden;
        }

        /* ===== SIDEBAR (FIXED / TIDAK IKUT TERSCROLL) ===== */
        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.03);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100vh;
            flex-shrink: 0;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px 20px 16px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }

        .sidebar-header .brand-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            line-height: 1.2;
        }

        .sidebar-header .brand-sub {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            flex-grow: 1;
            overflow-y: auto;
            padding: 12px 0;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 4px;
        }

        .menu-label {
            padding: 16px 20px 8px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #94a3b8;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            margin: 1px 0;
        }

        .sidebar-link:hover {
            background: var(--sidebar-hover);
            color: var(--primary-color);
            text-decoration: none;
            border-left-color: transparent;
        }

        .sidebar-link.active {
            background: rgba(21, 70, 121, 0.08);
            color: var(--primary-color);
            font-weight: 600;
            border-left-color: var(--primary-color);
        }

        .sidebar-link i {
            font-size: 1.15rem;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid #f1f5f9;
        }

        .sidebar-footer-text {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .sidebar-footer .badge {
            background: rgba(21, 70, 121, 0.1);
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .sidebar-copyright {
            font-size: 0.68rem;
            color: #94a3b8;
            line-height: 1.35;
            border-top: 1px dashed #e2e8f0;
            padding-top: 6px;
            margin-top: 6px;
        }

        /* ===== MAIN CONTENT AREA (HANYA SECTION INI YANG SCROLLABLE) ===== */
        #content {
            flex: 1 1 0%;
            display: flex;
            flex-direction: column;
            height: 100vh;
            min-width: 0;
            max-width: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.35s ease;
            position: relative;
        }

        #content::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        #content::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        #content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        #content::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ===== TOP NAVBAR ===== */
        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 30px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .sidebar-toggle {
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .sidebar-toggle:hover {
            background: #f8fafc;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .navbar-greeting {
            font-size: 0.9rem;
            color: #64748b;
            margin-left: 20px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 6px rgba(21, 70, 121, 0.25);
        }

        .user-info {
            text-align: right;
            margin-right: 10px;
        }

        .user-info .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.88rem;
            line-height: 1.2;
        }

        .user-info .user-role {
            font-size: 0.72rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex-grow: 1;
            padding: 20px 24px;
            min-width: 0;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .content-body {
            min-width: 0;
            width: 100%;
            max-width: 100%;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .breadcrumb-container {
            margin-bottom: 16px;
        }

        .breadcrumb-container .page-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.5rem;
            margin-bottom: 4px;
        }

        .breadcrumb-container .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 0.82rem;
        }

        .breadcrumb-container .breadcrumb-item a {
            color: #94a3b8;
            text-decoration: none;
        }

        .breadcrumb-container .breadcrumb-item.active {
            color: var(--primary-color);
        }

        /* ===== DESKTOP SIDEBAR COLLAPSE ===== */
        @media (min-width: 769px) {
            #sidebar.active {
                min-width: var(--sidebar-collapsed-width);
                max-width: var(--sidebar-collapsed-width);
                width: var(--sidebar-collapsed-width);
            }

            #sidebar.active .link-text,
            #sidebar.active .menu-label,
            #sidebar.active .sidebar-footer-text,
            #sidebar.active .sidebar-copyright,
            #sidebar.active .brand-text,
            #sidebar.active .brand-sub {
                display: none;
            }

            #sidebar.active .sidebar-header {
                justify-content: center;
                padding: 20px 10px 16px;
            }

            #sidebar.active .sidebar-link {
                justify-content: center;
                padding: 12px 10px;
                border-left-width: 3px;
            }

            #sidebar.active .sidebar-link i {
                font-size: 1.35rem;
            }

            #sidebar.active .sidebar-footer {
                text-align: center;
            }
        }

        /* ===== MOBILE SIDEBAR ===== */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                position: fixed;
                height: 100vh;
            }

            #sidebar.active {
                margin-left: 0;
            }

            .top-navbar {
                padding: 0 16px;
            }

            .main-content {
                padding: 20px 16px;
            }

            .navbar-greeting {
                display: none;
            }
        }

        /* ===== GLOBAL CARD STYLES ===== */
        .card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        /* Year switcher button */
        .btn-white {
            background: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .btn-white:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
    </style>
</head>

<body>
    <?php
    $q_instansi = $GLOBALS['instansi_config'] ?? null;
    $level = session()->get('admin_level');
    $user = session()->get('admin_user');
    $nama = session()->get('admin_nama');
    $unitkerja = session()->get('admin_unitkerja');
    $tahun = session()->get('admin_ta');
    $page = $page ?? 'dashboard';
    
    // Page title mapping
    $title_map = [
        'dashboard'      => 'Dashboard Overview',
        'd_amain'        => 'Dashboard Utama',
        'l_dda'          => 'Input Data DDA',
        'l_dda_admin'    => 'Input Data DDA (Admin)',
        'l_dda_new'      => 'Input Data DDA Baru',
        'l_dda_periksa'  => 'Pemeriksaan Data DDA',
        'l_master_tabel' => 'Master Tabel DDA',
        'l_master_dimensi' => 'Master Dimensi Tabel Dinamis',
        'l_master_tabel_opd' => (session()->get('admin_unitkerja') !== 'bps') ? 'Usulan Tabel Baru' : 'Review Tabel Usulan OPD',
        'l_unitkerja'    => 'Manajemen Instansi',
        'f_unitkerja'    => 'Form Master Instansi',
        'l_manage_admin' => 'Manajemen Admin',
        'f_manage_admin' => 'Form Manajemen Admin',
        'l_master_tim'   => 'Master Tim',
        'l_forum'        => 'Forum Diskusi',
        'l_forum_topik'  => 'Forum Topik',
        'l_forum_komen'  => 'Forum Komentar',
        'l_oi'           => 'Organization Info',
        'report'         => 'Laporan Rekap',
        'matching'       => 'Sinkronisasi/Matching',
        'matching_pdf'   => 'Matching PDF (Python)',
        'f_passwod'      => 'Ganti Password',
        'v_portal_tabel' => 'View Portal Tabel',
        'v_preview_bulk' => 'Preview Bulk Update',
        'f_generator_tabel' => 'Generator Indikator Tabel & Matriks Dinamis',
        'v_tabel_dinamis' => 'View Tabel Dinamis',
    ];

    $page_key = is_scalar($page) ? $page : 'dashboard';
    $page_title = isset($title_map[$page_key]) ? $title_map[$page_key] : ucfirst(str_replace('_', ' ', (string)$page_key));
    $initial = strtoupper(substr((string)$nama, 0, 1));
    ?>

    <div id="wrapper">
        <!-- ===== SIDEBAR ===== -->
        <aside id="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo base_url(); ?>aset/images/logo-aksara.png?v=<?= time() ?>" alt="Logo AKSARA" style="width: 48px; height: 48px; object-fit: contain;">
                <div>
                    <div class="brand-text">AKSARA</div>
                    <div class="brand-sub" title="Akses Data Provinsi Jawa Tengah Dalam Angka">Jateng Dalam Angka</div>
                </div>
            </div>

            <div class="sidebar-menu">
                <!-- MAIN NAVIGATION -->
                <div class="menu-label">Main Navigation</div>

                <?php if (can('dashboard')): ?>
                    <a href="<?php echo base_url(); ?>admin/" class="sidebar-link <?php echo ($page == 'd_amain' || $page == 'dashboard') ? 'active' : ''; ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span class="link-text">Dashboard</span>
                    </a>
                <?php endif; ?>

                <?php if (can('input_dda_input') || can('view_dda')): ?>
                    <a href="<?php echo base_url(); ?>admin/dda" class="sidebar-link <?php echo (strpos($page, 'l_dda') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-pencil-square"></i>
                        <span class="link-text"><?php echo (current_user_role() === 'walidata_opd') ? 'Lihat Data DDA' : 'Input Data DDA'; ?></span>
                    </a>

                    <?php if (session()->get('admin_unitkerja') !== 'bps'): ?>
                    <a href="<?php echo base_url(); ?>admin/master_tabel_opd" class="sidebar-link <?php echo (strpos($page, 'master_tabel_opd') !== false || $page === 'f_add_opd') ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-plus"></i>
                        <span class="link-text">Usulan Tabel Baru</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (can('forum_diskusi')): ?>
                    <a href="<?php echo base_url(); ?>admin/forum_diskusi/" class="sidebar-link <?php echo (strpos($page, 'forum') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-chat-dots"></i>
                        <span class="link-text">Forum Diskusi</span>
                    </a>
                <?php endif; ?>

                <?php if (can('matching') || can('matching_pdf') || can('report') || can('master_tabel') || can('master_dimensi') || can('master_opd') || can('pengguna') || can('manage_admin') || can('master_tim')): ?>
                    <!-- SYSTEM ADMIN -->
                    <div class="menu-label">System Admin</div>

                    <?php if (can('matching')): ?>
                        <a href="<?php echo base_url(); ?>admin/matching/" class="sidebar-link <?php echo ($page == 'matching') ? 'active' : ''; ?>">
                            <i class="bi bi-arrow-left-right"></i>
                            <span class="link-text">Sinkronisasi/Matching</span>
                        </a>
                    <?php endif; ?>

                    <!-- [Penambahan Fitur Matching PDF]: Navigasi Menu Sidebar ke modul Matching PDF DDA (Python Engine) -->
                    <?php if (can('matching_pdf')): ?>
                        <a href="<?php echo base_url(); ?>admin/matching_pdf/" class="sidebar-link <?php echo ($page == 'matching_pdf') ? 'active' : ''; ?>">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <span class="link-text">Matching PDF (Python)</span>
                        </a>
                    <?php endif; ?>

                    <?php if (can('report')): ?>
                        <a href="<?php echo base_url(); ?>admin/report/" class="sidebar-link <?php echo ($page == 'report') ? 'active' : ''; ?>">
                            <i class="bi bi-bar-chart-line"></i>
                            <span class="link-text">Laporan Rekap</span>
                        </a>
                    <?php endif; ?>

                    <?php if (can('master_tabel')): ?>
                        <a href="<?php echo base_url(); ?>admin/master_tabel/" class="sidebar-link <?php echo (strpos($page, 'master_tabel') !== false) ? 'active' : ''; ?>">
                            <i class="bi bi-table"></i>
                            <span class="link-text">Master Tabel</span>
                        </a>
                    <?php endif; ?>

                    <?php if (can('master_dimensi')): ?>
                        <a href="<?php echo base_url(); ?>admin/master_dimensi/" class="sidebar-link <?php echo (strpos($page, 'dimensi') !== false) ? 'active' : ''; ?>">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                            <span class="link-text">Master Dimensi Tabel</span>
                        </a>
                    <?php endif; ?>

                    <?php if (can('master_opd')): ?>
                        <a href="<?php echo base_url(); ?>admin/master_opd/" class="sidebar-link <?php echo ($page == 'l_unitkerja') ? 'active' : ''; ?>">
                            <i class="bi bi-building"></i>
                            <span class="link-text">Master Instansi</span>
                        </a>
                    <?php endif; ?>

                    <?php if (can('pengguna')): ?>
                        <a href="<?php echo base_url(); ?>admin/pengguna" class="sidebar-link <?php echo (strpos($page, 'pengguna') !== false) ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i>
                            <span class="link-text">Instansi Pengguna</span>
                        </a>
                    <?php endif; ?>

                    <?php if (can('manage_admin')): ?>
                        <a href="<?php echo base_url(); ?>admin/manage_admin/" class="sidebar-link <?php echo (strpos($page, 'manage_admin') !== false) ? 'active' : ''; ?>">
                            <i class="bi bi-person-gear"></i>
                            <span class="link-text">Manajemen Admin</span>
                        </a>
                    <?php endif; ?>

                    <?php if (can('master_tim')): ?>
                        <a href="<?php echo base_url(); ?>admin/master_tim/" class="sidebar-link <?php echo ($page == 'l_master_tim') ? 'active' : ''; ?>">
                            <i class="bi bi-diagram-3"></i>
                            <span class="link-text">Master Tim</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ACCOUNT -->
                <div class="menu-label">Account Settings</div>

                <?php if (can('ganti_password')): ?>
                    <a href="<?php echo base_url(); ?>admin/passwod" class="sidebar-link <?php echo ($page == 'f_passwod') ? 'active' : ''; ?>">
                        <i class="bi bi-key"></i>
                        <span class="link-text">Ganti Password</span>
                    </a>
                <?php endif; ?>

                <?php if (can('logout')): ?>
                    <a href="<?php echo base_url(); ?>admin/logout" class="sidebar-link">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="link-text">Logout</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="sidebar-footer">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="sidebar-footer-text font-weight-bold">Tahun Data Aktif</span>
                    <span class="badge"><?php echo $tahun ?: date('Y'); ?></span>
                </div>
                <div class="sidebar-copyright">
                    <span>BPS Provinsi Jawa Tengah — DDA Online</span>
                </div>
            </div>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main id="content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="d-flex align-items-center">
                    <button class="sidebar-toggle" id="sidebarCollapse" type="button">
                        <i class="bi bi-list" style="font-size: 1.3rem;"></i>
                    </button>
                    <span class="navbar-greeting">Halo, selamat datang kembali!</span>
                </div>

                <div class="d-flex align-items-center">
                    <!-- Year Switcher -->
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-white border dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-calendar-event me-1"></i> <?= $tahun ?: date('Y') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <h6 class="dropdown-header">Pilih Tahun Data</h6>
                            </li>
                            <?php 
                            $currentUri = uri_string();
                            $queryString = $_SERVER['QUERY_STRING'] ?? '';
                            $returnTarget = $currentUri . ($queryString !== '' ? '?' . $queryString : '');
                            ?>
                            <?php for ($i = 2020; $i <= (date('Y') + 1); $i++): ?>
                                <li>
                                    <a class="dropdown-item <?= ($tahun == $i) ? 'active' : '' ?>" href="<?php echo base_url(); ?>admin/set_tahun/<?= $i ?>/?redirect_to=<?= urlencode($returnTarget); ?>">
                                        <i class="bi bi-calendar3 me-2"></i><?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-info d-none d-md-block">
                                <div class="user-name"><?php echo esc($nama); ?></div>
                                <div class="user-role"><?php echo esc(function_exists('current_user_role_label') ? current_user_role_label() : $level); ?></div>
                            </div>
                            <div class="user-avatar"><?php echo esc($initial); ?></div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <h6 class="dropdown-header"><?php echo esc($nama); ?></h6>
                            </li>
                            <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/profil"><i class="bi bi-person-circle me-2"></i>Profil Saya</a></li>
                            <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/passwod"><i class="bi bi-key me-2"></i>Ganti Password</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?php echo base_url(); ?>admin/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="main-content">
                <!-- Breadcrumb -->
                <div class="breadcrumb-container">
                    <h2 class="page-title"><?php echo $page_title; ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>admin/"><i class="bi bi-house"></i> Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
                        </ol>
                    </nav>
                </div>

                <!-- Content Body -->
                <div class="content-body">
                    <?php if (session()->getFlashdata('k')): ?>
                        <?= session()->getFlashdata('k') ?>
                    <?php endif; ?>

                    <?php echo view('admin/' . $page); ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Sidebar Toggle & Global UI Handler Script -->
    <script>
        // Nonaktifkan pemulihan scroll otomatis bawaan browser agar saat refresh (F5) selalu mulai dari paling atas
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        window.addEventListener('beforeunload', function() {
            var content = document.getElementById('content');
            if (content) content.scrollTop = 0;
            document.querySelectorAll('.table-scroll-container').forEach(function(el) {
                el.scrollTop = 0;
            });
        });

        $(document).ready(function() {
            var content = document.getElementById('content');
            if (content) {
                content.scrollTop = 0;
            }
            $('.table-scroll-container').scrollTop(0);

            // Global CSRF Protection Setup for AJAX & Forms
            var csrfTokenName = '<?= csrf_token() ?>';
            var csrfHash = '<?= csrf_hash() ?>';

            function getLatestCsrfHash() {
                return $('#meta-csrf').attr('content') || csrfHash;
            }

            // Global jQuery AJAX Setup: Otomatis kirim X-CSRF-TOKEN header pada request non-GET
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                        xhr.setRequestHeader('X-CSRF-TOKEN', getLatestCsrfHash());
                    }
                }
            });

            // Injeksi otomatis input hidden CSRF pada semua form POST yang belum memilikinya
            function injectCsrfToForms() {
                var curHash = getLatestCsrfHash();
                $('form').each(function() {
                    var method = ($(this).attr('method') || '').toUpperCase();
                    if (method === 'POST') {
                        var $input = $(this).find('input[name="' + csrfTokenName + '"]');
                        if ($input.length === 0) {
                            $(this).prepend('<input type="hidden" name="' + csrfTokenName + '" value="' + curHash + '">');
                        } else if (!$input.val()) {
                            $input.val(curHash);
                        }
                    }
                });
            }
            injectCsrfToForms();

            // Tangani form dinamis / modal saat submit
            $(document).on('submit', 'form', function() {
                var method = ($(this).attr('method') || '').toUpperCase();
                if (method === 'POST') {
                    var curHash = getLatestCsrfHash();
                    var $input = $(this).find('input[name="' + csrfTokenName + '"]');
                    if ($input.length === 0) {
                        $(this).prepend('<input type="hidden" name="' + csrfTokenName + '" value="' + curHash + '">');
                    } else if (!$input.val()) {
                        $input.val(curHash);
                    }
                }
            });

            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });

            // Auto-dismiss alerts
            setTimeout(function() {
                $("#alert").fadeOut(800);
            }, 5000);

            // Global Handler: SweetAlert2 Konfirmasi Batal Verifikasi
            $(document).on('click', '.btn-batal-verif', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                Swal.fire({
                    title: 'Batalkan Verifikasi?',
                    text: 'Status data akan kembali ke Menunggu Cek dan publikasi tabel otomatis kembali ke Draft.',
                    icon: 'warning',
                    iconColor: '#154679',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    width: '390px',
                    customClass: {
                        popup: 'rounded-4 shadow border-0 p-4',
                        title: 'fs-5 fw-bold text-dark mb-1',
                        htmlContainer: 'text-muted small m-0 mb-3',
                        actions: 'gap-2 mt-1 w-100 justify-content-center',
                        confirmButton: 'btn btn-danger px-3 py-2 rounded-3 fw-medium',
                        cancelButton: 'btn btn-light border px-3 py-2 rounded-3 text-secondary fw-medium'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });

            // Global Handler: SweetAlert2 Konfirmasi Batal Status Pengisian
            $(document).on('click', '.btn-batal-isi', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                Swal.fire({
                    title: 'Batalkan Pengisian?',
                    text: 'Status pengisian OPD untuk tabel ini akan dikembalikan ke Belum Diisi.',
                    icon: 'question',
                    iconColor: '#154679',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    width: '390px',
                    customClass: {
                        popup: 'rounded-4 shadow border-0 p-4',
                        title: 'fs-5 fw-bold text-dark mb-1',
                        htmlContainer: 'text-muted small m-0 mb-3',
                        actions: 'gap-2 mt-1 w-100 justify-content-center',
                        confirmButton: 'btn btn-danger px-3 py-2 rounded-3 fw-medium',
                        cancelButton: 'btn btn-light border px-3 py-2 rounded-3 text-secondary fw-medium'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });

            // Global Dropdown Safety: Pastikan dropdown di dalam .table-responsive menggunakan strategy 'fixed'
            // agar menu tidak terpotong (clipped) oleh overflow kontainer tabel
            document.addEventListener('show.bs.dropdown', function(e) {
                var toggleBtn = e.target;
                if (toggleBtn && (toggleBtn.closest('.table-responsive') || toggleBtn.closest('table'))) {
                    var dd = bootstrap.Dropdown.getInstance(toggleBtn);
                    if (dd && (!dd._config.popperConfig || dd._config.popperConfig.strategy !== 'fixed')) {
                        dd._config.popperConfig = { strategy: 'fixed' };
                    }
                }
            });
        });
    </script>
</body>
</html>