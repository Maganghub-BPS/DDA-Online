<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>.:: Jateng Dalam Angka ::.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
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

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 85px;
            --sidebar-bg: #ffffff;
            --sidebar-hover: rgba(255, 109, 31, 0.06);
            --primary-color: #FF6D1F;
            --content-bg: #f5f7f9;
            --bs-primary: #FF6D1F;
            --bs-primary-rgb: 255, 109, 31;
            --bs-link-color: #FF6D1F;
            --bs-link-hover-color: #e05e15;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Public Sans', sans-serif;
            background: var(--content-bg);
            overflow-x: hidden;
        }

        /* ===== OVERRIDE BOOTSTRAP PRIMARY ===== */
        .text-primary {
            color: #FF6D1F !important;
        }

        .text-primary-orange {
            color: #FF6D1F !important;
        }

        .bg-primary {
            background-color: #FF6D1F !important;
        }

        .bg-primary-orange {
            background-color: #FF6D1F !important;
        }

        .shadow-primary {
            box-shadow: 0 4px 6px rgba(255, 109, 31, 0.11), 0 1px 3px rgba(255, 109, 31, 0.08) !important;
        }

        .btn-primary {
            background-color: #FF6D1F !important;
            border-color: #FF6D1F !important;
        }

        .btn-primary:hover {
            background-color: #e05e15 !important;
            border-color: #d45510 !important;
        }

        .btn-outline-primary {
            color: #FF6D1F !important;
            border-color: #FF6D1F !important;
        }

        .btn-outline-primary:hover {
            background-color: #FF6D1F !important;
            border-color: #FF6D1F !important;
            color: #fff !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #FF6D1F;
            box-shadow: 0 0 0 0.25rem rgba(255, 109, 31, 0.15);
        }

        a {
            color: #FF6D1F;
        }

        a:hover {
            color: #e05e15;
        }

        /* ===== PAGINATION ORANGE OVERRIDE ===== */
        .pagination .page-link {
            transition: all 0.2s;
            color: #64748b;
            border-radius: 6px;
        }

        .pagination .page-item.active .page-link {
            background-color: #FF6D1F !important;
            border-color: #FF6D1F !important;
            color: #fff !important;
            box-shadow: 0 4px 6px rgba(255, 109, 31, 0.2) !important;
        }

        .pagination .page-link:hover {
            background-color: #f8f9fa;
            color: #FF6D1F;
        }

        .pagination .page-link:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 109, 31, 0.1) !important;
        }

        /* ===== WRAPPER LAYOUT ===== */
        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.03);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
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
            background: rgba(255, 109, 31, 0.08);
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
            background: rgba(255, 109, 31, 0.1);
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 6px;
        }

        /* ===== MAIN CONTENT AREA ===== */
        #content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: all 0.35s ease;
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
            box-shadow: 0 4px 6px rgba(255, 109, 31, 0.2);
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
            padding: 30px;
        }

        .breadcrumb-container {
            margin-bottom: 24px;
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

        /* ===== FOOTER ===== */
        .footer {
            padding: 16px 30px;
            text-align: center;
            font-size: 0.8rem;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        /* ===== DESKTOP SIDEBAR COLLAPSE ===== */
        @media (min-width: 769px) {
            #sidebar.active {
                min-width: var(--sidebar-collapsed-width);
                max-width: var(--sidebar-collapsed-width);
            }

            #sidebar.active .link-text,
            #sidebar.active .menu-label,
            #sidebar.active .sidebar-footer-text,
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
        'l_master_tabel_opd' => 'Master Tabel OPD',
        'l_unitkerja'    => 'Manajemen OPD',
        'l_manage_admin' => 'Manajemen Admin',
        'l_master_tim'   => 'Master Tim',
        'l_forum'        => 'Forum Diskusi',
        'l_forum_topik'  => 'Forum Topik',
        'l_forum_komen'  => 'Forum Komentar',
        'l_oi'           => 'Organization Info',
        'report'         => 'Laporan Rekap',
        'matching'       => 'Sinkronisasi/Matching',
        'f_passwod'      => 'Ganti Password',
        'v_portal_tabel' => 'View Portal Tabel',
        'v_preview_bulk' => 'Preview Bulk Update',
    ];

    $page_key = is_scalar($page) ? $page : 'dashboard';
    $page_title = isset($title_map[$page_key]) ? $title_map[$page_key] : ucfirst(str_replace('_', ' ', (string)$page_key));
    $initial = strtoupper(substr((string)$nama, 0, 1));
    ?>

    <div id="wrapper">
        <!-- ===== SIDEBAR ===== -->
        <aside id="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo base_url(); ?>aset/favicon.png" alt="Logo">
                <div>
                    <div class="brand-text">DDA Online</div>
                    <div class="brand-sub">Jateng Dalam Angka</div>
                </div>
            </div>

            <div class="sidebar-menu">
                <!-- MAIN NAVIGATION -->
                <div class="menu-label">Main Navigation</div>

                <a href="<?php echo base_url(); ?>admin/" class="sidebar-link <?php echo ($page == 'd_amain' || $page == 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span class="link-text">Dashboard</span>
                </a>

                <?php if ($level != 'kominfo'): ?>
                    <a href="<?php echo base_url(); ?>admin/dda" class="sidebar-link <?php echo (strpos($page, 'l_dda') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-pencil-square"></i>
                        <span class="link-text">Input Data DDA</span>
                    </a>
                <?php endif; ?>

                <a href="<?php echo base_url(); ?>admin/forum_diskusi/" class="sidebar-link <?php echo (strpos($page, 'forum') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-chat-dots"></i>
                    <span class="link-text">Forum Diskusi</span>
                </a>

                <?php if ($unitkerja == 'bps'): ?>
                    <!-- SYSTEM ADMIN -->
                    <div class="menu-label">System Admin</div>

                    <a href="<?php echo base_url(); ?>admin/matching/" class="sidebar-link <?php echo ($page == 'matching') ? 'active' : ''; ?>">
                        <i class="bi bi-arrow-left-right"></i>
                        <span class="link-text">Sinkronisasi/Matching</span>
                    </a>

                    <a href="<?php echo base_url(); ?>admin/report/" class="sidebar-link <?php echo ($page == 'report') ? 'active' : ''; ?>">
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="link-text">Laporan Rekap</span>
                    </a>

                    <a href="<?php echo base_url(); ?>admin/master_tabel/" class="sidebar-link <?php echo (strpos($page, 'master_tabel') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-table"></i>
                        <span class="link-text">Master Tabel</span>
                    </a>

                    <?php if ($user == 'diseminasi' || $level == 'Admin' || $level == 'Super Admin'): ?>
                        <a href="<?php echo base_url(); ?>admin/master_opd/" class="sidebar-link <?php echo ($page == 'l_unitkerja') ? 'active' : ''; ?>">
                            <i class="bi bi-building"></i>
                            <span class="link-text">Master OPD</span>
                        </a>

                        <a href="<?php echo base_url(); ?>admin/pengguna" class="sidebar-link <?php echo (strpos($page, 'pengguna') !== false) ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i>
                            <span class="link-text">Instansi Pengguna</span>
                        </a>

                        <a href="<?php echo base_url(); ?>admin/manage_admin/" class="sidebar-link <?php echo (strpos($page, 'manage_admin') !== false) ? 'active' : ''; ?>">
                            <i class="bi bi-person-gear"></i>
                            <span class="link-text">Manajemen Admin</span>
                        </a>

                        <a href="<?php echo base_url(); ?>admin/master_tim/" class="sidebar-link <?php echo ($page == 'l_master_tim') ? 'active' : ''; ?>">
                            <i class="bi bi-diagram-3"></i>
                            <span class="link-text">Master Tim</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ACCOUNT -->
                <div class="menu-label">Account Settings</div>

                <a href="<?php echo base_url(); ?>admin/passwod" class="sidebar-link <?php echo ($page == 'f_passwod') ? 'active' : ''; ?>">
                    <i class="bi bi-key"></i>
                    <span class="link-text">Ganti Password</span>
                </a>

                <a href="<?php echo base_url(); ?>admin/logout" class="sidebar-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="link-text">Logout</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-footer-text mb-1">Tahun Data Aktif</div>
                <span class="badge"><?php echo $tahun ?: date('Y'); ?></span>
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
                            <?php for ($i = 2020; $i <= (date('Y') + 1); $i++): ?>
                                <li>
                                    <a class="dropdown-item <?= ($tahun == $i) ? 'active' : '' ?>" href="<?php echo base_url(); ?>admin/set_tahun/<?= $i ?>/">
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
                                <div class="user-name"><?php echo $nama; ?></div>
                                <div class="user-role"><?php echo $level; ?></div>
                            </div>
                            <div class="user-avatar"><?php echo $initial; ?></div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <h6 class="dropdown-header"><?php echo $nama; ?></h6>
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
                    <?php echo view('admin/' . $page); ?>
                </div>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <strong>BPS Jateng</strong> &copy; BPS Provinsi Jawa Tengah — DDA Online
            </footer>
        </main>
    </div>

    <!-- Sidebar Toggle Script -->
    <script>
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });

            // Auto-dismiss alerts
            setTimeout(function() {
                $("#alert").fadeOut(800);
            }, 5000);
        });
    </script>
</body>

</html>