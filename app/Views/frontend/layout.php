<?php
$uri = uri_string();
$isHome = ($uri == '' || $uri == '/' || strpos($uri, 'search') !== false || strpos($uri, 'instansi') !== false);
$isBeranda = ($uri == '' || $uri == '/' || $uri == 'home' || $uri == 'home/index' || current_url() == base_url() || current_url() == base_url('home'));
$isSearch = (strpos($uri, 'search') !== false || strpos(current_url(), 'search') !== false);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?>DDA Jawa Tengah</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            /* BPS Colors - Lighter and more vibrant */
            --bps-blue: #154679;      
            --bps-blue-light: #245c94;
            --bps-orange: #F26522;    
            --bps-orange-hover: #D65518;
            --bps-green: #10B981; /* Accent green for Pojok Statistik feel */
            
            --bg-body: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #E2E8F0;
        }

        *, body, button, input, select, textarea, h1, h2, h3, h4, h5, h6, .navbar-brand, .nav-link {
            font-family: 'Inter', "Inter Fallback", sans-serif;
        }

        body {
            font-family: 'Inter', "Inter Fallback", sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Headings use Inter */
        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: 'Inter', "Inter Fallback", sans-serif;
        }

        /* Standardized Simple Button System (No Over-the-Top Bouncing/Shadows) */
        .btn, button, .btn-search-bar, .btn-hero-search, .btn-opd-expand, .tcc-btn, .btn-modern-arrow, .btn-rounded-modern, .btn-export {
            transform: none !important;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, opacity 0.15s ease !important;
        }
        .btn:hover, button:hover, .btn-search-bar:hover, .btn-hero-search:hover, .btn-opd-expand:hover, .tcc-btn:hover, .btn-modern-arrow:hover, .btn-rounded-modern:hover, .btn-export:hover {
            transform: none !important;
        }
        .btn:active, button:active, .btn-search-bar:active, .btn-hero-search:active, .btn-opd-expand:active, .tcc-btn:active {
            transform: none !important;
            opacity: 0.9 !important;
        }

        /* Modern Sleek Navbar - Transparent & Seamless over Hero Section */
        .navbar-custom {
            padding: 18px 0;
            position: fixed;
            top: 0; 
            left: 0; 
            right: 0;
            z-index: 1050;
            background: transparent !important;
            border-bottom: 1px solid transparent !important;
            box-shadow: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            transition: background 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                        padding 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                        border-color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Scrolled State: Crisp Pure White with Soft Elevation */
        .navbar-custom.scrolled {
            padding: 12px 0;
            background: #ffffff !important;
            border-bottom: 1px solid rgba(226, 232, 240, 0.95) !important;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Brand Logo Typography & Modern Logo Mark */
        .navbar-brand {
            font-family: 'Inter', "Inter Fallback", sans-serif;
            font-weight: 800;
            font-size: 1.55rem;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.4px;
            text-decoration: none;
        }
        .navbar-brand-logo-img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .navbar-brand .brand-title {
            font-weight: 800;
            line-height: 1.1;
            font-size: 1.55rem;
            display: flex;
            align-items: center;
            letter-spacing: -0.5px;
            color: var(--bps-blue);
        }
        .navbar-brand .brand-title .brand-accent {
            color: var(--bps-orange);
        }
        .navbar-brand .brand-subtitle {
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.2px;
            font-family: 'Inter', "Inter Fallback", sans-serif;
            line-height: 1.2;
            margin-top: 2px;
            color: #64748b;
        }

        /* Clean Standard Navbar Navigation */
        .navbar-custom .nav-link {
            font-family: 'Inter', "Inter Fallback", sans-serif;
            font-weight: 600;
            font-size: 0.98rem;
            padding: 8px 16px !important;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            position: relative;
            color: #334155 !important;
            text-shadow: none !important;
        }

        .navbar-custom .nav-link i {
            font-size: 0.94rem;
            transition: color 0.2s ease;
            color: #64748b;
            text-shadow: none !important;
            filter: none !important;
        }

        .navbar-custom .nav-link:hover {
            color: var(--bps-blue) !important;
        }
        .navbar-custom .nav-link:hover i {
            color: var(--bps-blue);
        }

        .navbar-custom .nav-link.active {
            color: var(--bps-orange) !important;
            font-weight: 700 !important;
            text-shadow: none !important;
        }
        .navbar-custom .nav-link.active i {
            color: var(--bps-orange) !important;
            text-shadow: none !important;
            filter: none !important;
        }

        /* Orange Underline Indicator strictly on Active Navbar Link */
        .navbar-custom .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 14px;
            right: 14px;
            height: 3px;
            background: var(--bps-orange);
            border-radius: 4px;
            transform: scaleX(0);
            transform-origin: center;
            transition: transform 0.22s ease, opacity 0.22s ease;
        }
        .navbar-custom .nav-link.active::after {
            transform: scaleX(1);
        }
        .navbar-custom .nav-link:hover::after {
            transform: scaleX(1);
            opacity: 0.5;
        }
        .navbar-custom .nav-link.active:hover::after {
            opacity: 1;
        }

        /* Mobile Navbar */
        .navbar-custom .navbar-toggler i { color: var(--bps-blue) !important; }

        @media (max-width: 991.98px) {
            .navbar-custom .navbar-collapse {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 14px 18px;
                margin-top: 10px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            }
            .navbar-custom .nav-link::after {
                left: 16px;
                width: 32px;
                right: auto;
                bottom: 2px;
            }
        }

        @media (max-width: 576px) {
            body[style*="padding-top"] {
                padding-top: 76px !important;
            }
            .navbar-custom {
                padding: 12px 0 !important;
            }
            .navbar-brand-logo-img {
                width: 36px !important;
                height: 36px !important;
            }
            .navbar-brand .brand-title {
                font-size: 1.3rem !important;
            }
            .navbar-brand .brand-subtitle {
                font-size: 0.72rem !important;
            }
        }

        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }

        main {
            flex: 1;
            padding-top: 0;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Mega Footer Modern & Elegant */
        .footer-mega {
            background: linear-gradient(180deg, #0e2a4a 0%, #0a1f36 100%);
            color: rgba(255, 255, 255, 0.85);
            padding: 68px 0 28px;
            position: relative;
            overflow: hidden;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .footer-content {
            position: relative;
            z-index: 10;
        }

        /* Brand Column Styling */
        .footer-logo-badge {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: rgba(242, 101, 34, 0.14);
            border: 1px solid rgba(242, 101, 34, 0.28);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--bps-orange);
            box-shadow: 0 6px 20px rgba(242, 101, 34, 0.2);
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .footer-logo-badge:hover {
            transform: scale(1.05) rotate(5deg);
        }

        .footer-brand-title {
            font-family: 'Inter', "Inter Fallback", sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.4px;
            color: #ffffff;
            line-height: 1.15;
        }

        .footer-badge-instansi {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            font-size: 0.76rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 5px;
            letter-spacing: 0.2px;
            font-family: 'Inter', "Inter Fallback", sans-serif;
        }

        .footer-desc {
            font-family: 'Inter', "Inter Fallback", sans-serif;
            font-size: 0.92rem;
            color: #cbd5e1;
            line-height: 1.65;
            max-width: 430px;
        }

        .social-links {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            font-size: 0.92rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }
        .social-links a:hover {
            background: var(--bps-orange);
            color: #ffffff;
            border-color: var(--bps-orange);
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(242, 101, 34, 0.35);
        }

        /* Headings */
        .footer-title {
            color: #ffffff;
            font-family: 'Inter', "Inter Fallback", sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -0.2px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 28px;
            height: 3px;
            border-radius: 3px;
            background: var(--bps-orange);
        }

        /* Navigation Links */
        .footer-links-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-link {
            color: #cbd5e1;
            text-decoration: none;
            display: flex;
            align-items: flex-start;
            font-size: 0.92rem;
            font-weight: 500;
            font-family: 'Inter', "Inter Fallback", sans-serif;
            transition: all 0.22s ease;
            width: fit-content;
            line-height: 1.4;
        }

        .footer-link-icon {
            font-size: 0.72rem;
            color: #94a3b8;
            transition: all 0.22s ease;
            margin-right: 8px;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .footer-link:hover {
            color: #ffffff;
            transform: translateX(4px);
        }
        .footer-link:hover .footer-link-icon {
            color: var(--bps-orange);
            transform: translateX(2px);
        }

        /* Contact Details */
        .footer-contact-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .footer-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            transition: all 0.22s ease;
        }

        .footer-contact-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--bps-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
            transition: all 0.22s ease;
        }

        .footer-contact-label {
            display: block;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 2px;
            font-family: 'Inter', "Inter Fallback", sans-serif;
        }

        .footer-contact-val {
            font-size: 0.9rem;
            color: #f1f5f9;
            line-height: 1.5;
            font-family: 'Inter', "Inter Fallback", sans-serif;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .footer-contact-link:hover .footer-contact-icon {
            background: rgba(242, 101, 34, 0.18);
            border-color: rgba(242, 101, 34, 0.4);
            transform: scale(1.05);
        }
        .footer-contact-link:hover .footer-contact-val {
            color: #ffffff;
        }

        /* Responsive Mobile Footer: Kolom Navigasi & Hubungi Kami Bersebelahan */
        @media (max-width: 767.98px) {
            .footer-mega {
                padding: 45px 0 24px;
            }
            .footer-contact-item {
                gap: 10px;
            }
            .footer-contact-icon {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
                border-radius: 8px;
            }
            .footer-contact-label {
                font-size: 0.65rem;
                letter-spacing: 0.4px;
                margin-bottom: 1px;
            }
            .footer-contact-val {
                font-size: 0.8rem;
                line-height: 1.35;
                word-break: break-word;
            }
            .footer-link {
                font-size: 0.82rem;
            }
            .footer-title {
                font-size: 0.96rem;
                margin-bottom: 14px;
                padding-bottom: 8px;
            }
            .footer-links-list {
                gap: 10px;
            }
            .footer-contact-list {
                gap: 12px;
            }
        }

        /* Bottom Copyright & Status Bar */
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 48px;
            padding-top: 24px;
        }

        .footer-copyright {
            font-size: 0.88rem;
            color: #94a3b8;
            font-family: 'Inter', "Inter Fallback", sans-serif;
        }
        .footer-copyright strong {
            color: #e2e8f0;
        }

        .footer-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 50px;
            padding: 5px 14px;
            font-size: 0.8rem;
            color: #cbd5e1;
            font-family: 'Inter', "Inter Fallback", sans-serif;
            font-weight: 500;
        }

        .status-dot-pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Accessibility Widget */
        .a11y-widget { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .a11y-btn { width: 55px; height: 55px; border-radius: 50%; background-color: var(--bps-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.3s ease; border: 3px solid white; }
        .a11y-btn:hover { transform: scale(1.1); }
        .a11y-menu { position: absolute; bottom: 70px; right: 0; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); width: 250px; padding: 15px; display: none; flex-direction: column; gap: 10px; transform-origin: bottom right; border: 1px solid #e2e8f0; }
        .a11y-menu.show { display: flex; animation: popIn 0.3s ease forwards; }
        .a11y-menu-header { font-weight: bold; font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 5px; color: #1e293b; font-family: 'Inter', "Inter Fallback", sans-serif; text-align: center; }
        .a11y-option { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; background: #f8fafc; cursor: pointer; transition: background 0.2s; border: 1px solid transparent; color: #334155; font-weight: 500; font-size: 0.95rem; }
        .a11y-option:hover { background: #eff6ff; border-color: #bfdbfe; color: var(--bps-blue); }
        .a11y-option i { width: 24px; text-align: center; font-size: 1.1rem; }

        @keyframes popIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }

    </style>
</head>
<body <?= !$isHome ? 'style="padding-top: 85px;"' : '' ?>>

    <nav class="navbar navbar-expand-lg navbar-custom <?= !$isHome ? 'scrolled' : '' ?>" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= base_url() ?>">
                <img src="<?= base_url('aset/images/logo-dda.svg') ?>" alt="Logo DDA Online" class="navbar-brand-logo-img me-2">
                <div class="d-flex flex-column">
                    <div class="brand-title">DDA<span class="brand-accent">Online</span></div>
                    <span class="brand-subtitle">Provinsi Jawa Tengah</span>
                </div>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-1 gap-lg-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $isBeranda ? 'active' : '' ?>" href="<?= base_url() ?>">
                            <i class="fa-solid fa-house-chimney me-1"></i> Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $isSearch ? 'active' : '' ?>" href="<?= base_url('home/search') ?>">
                            <i class="fa-solid fa-compass me-1"></i> Jelajah Data
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <?= view($page) ?>
    </main>

    <footer class="footer-mega">
        <div class="container footer-content">
            <div class="row gx-3 gx-sm-4 gx-lg-5 gy-4 justify-content-between mb-2">
                <!-- Kolom 1: Profil / Brand -->
                <div class="col-12 col-lg-5 pe-lg-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="<?= base_url('aset/images/logo-dda.svg') ?>" alt="Logo DDA Online" width="48" height="48" class="footer-logo-img">
                        <div>
                            <div class="footer-brand-title">
                                DDA<span style="color: var(--bps-orange);">Online</span>
                            </div>
                            <span class="footer-badge-instansi">
                                <i class="fa-solid fa-shield-halved text-warning"></i>
                                BPS Provinsi Jawa Tengah
                            </span>
                        </div>
                    </div>
                    
                    <p class="footer-desc mb-4">
                        Portal resmi Daerah Dalam Angka yang menyediakan akses cepat, akurat, dan terstruktur ke basis data indikator statistik se-Jawa Tengah untuk mendukung perumusan kebijakan berbasis data.
                    </p>
                    
                    <div class="social-links">
                        <a href="https://facebook.com/bpsprovjateng" target="_blank" rel="noopener" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="https://twitter.com/bpsprovjateng" target="_blank" rel="noopener" title="Twitter / X"><i class="fa-brands fa-x-twitter"></i></a>
                        <a href="https://instagram.com/bpsprovjateng" target="_blank" rel="noopener" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="https://www.youtube.com/@BPSProvinsiJawaTengah" target="_blank" rel="noopener" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Kolom 2: Navigasi Cepat -->
                <div class="col-5 col-sm-5 col-md-5 col-lg-3 ps-lg-4">
                    <h6 class="footer-title">Navigasi Portal</h6>
                    <div class="footer-links-list">
                        <a href="<?= base_url() ?>" class="footer-link">
                            <i class="fa-solid fa-chevron-right footer-link-icon"></i>
                            <span>Beranda Utama</span>
                        </a>
                        <a href="<?= base_url('home/search') ?>" class="footer-link">
                            <i class="fa-solid fa-chevron-right footer-link-icon"></i>
                            <span>Jelajah Data</span>
                        </a>
                        <a href="<?= base_url('admin/login') ?>" class="footer-link">
                            <i class="fa-solid fa-chevron-right footer-link-icon"></i>
                            <span>Portal Pengelola Data</span>
                        </a>
                    </div>
                </div>
                
                <!-- Kolom 3: Hubungi Kami -->
                <div class="col-7 col-sm-7 col-md-7 col-lg-4 ps-lg-3">
                    <h6 class="footer-title">Hubungi Kami</h6>
                    <div class="footer-contact-list">
                        <a href="https://maps.google.com/?q=BPS+Provinsi+Jawa+Tengah" target="_blank" rel="noopener" class="footer-contact-item footer-contact-link">
                            <div class="footer-contact-icon">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div>
                                <span class="footer-contact-label">Alamat Kantor</span>
                                <span class="footer-contact-val">Jl. Pahlawan No. 6, Pleburan, Kec. Semarang Selatan, Kota Semarang, Jawa Tengah 50241</span>
                            </div>
                        </a>

                        <a href="tel:0248412802" class="footer-contact-item footer-contact-link">
                            <div class="footer-contact-icon">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <span class="footer-contact-label">Telepon Kantor</span>
                                <span class="footer-contact-val">(024) 8412802 / 8412804</span>
                            </div>
                        </a>

                        <a href="mailto:jateng@bps.go.id" class="footer-contact-item footer-contact-link">
                            <div class="footer-contact-icon">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <span class="footer-contact-label">Email Pelayanan</span>
                                <span class="footer-contact-val">jateng@bps.go.id</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Copyright Bar -->
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="footer-copyright text-center text-md-start">
                    &copy; <?= date('Y') ?> <strong>Badan Pusat Statistik Provinsi Jawa Tengah</strong>. Hak Cipta Dilindungi.
                </div>
                <div class="footer-status-badge">
                    <span class="status-dot-pulse"></span>
                    <span>Portal DDA v2.0</span>
                    <span style="opacity: 0.35;">•</span>
                    <span>Satu Data Indonesia</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Accessibility Widget HTML -->
    <div class="a11y-widget">
        <div class="a11y-menu" id="a11yMenu">
            <div class="a11y-menu-header">
                Aksesibilitas
            </div>
            <div class="a11y-option" onclick="changeFontSize(1)">
                <i class="fa-solid fa-magnifying-glass-plus"></i> Perbesar Teks
            </div>
            <div class="a11y-option" onclick="changeFontSize(-1)">
                <i class="fa-solid fa-magnifying-glass-minus"></i> Perkecil Teks
            </div>
            <div class="a11y-option" onclick="resetA11y()">
                <i class="fa-solid fa-rotate-right"></i> Reset Pengaturan
            </div>
        </div>
        <div class="a11y-btn" onclick="document.getElementById('a11yMenu').classList.toggle('show')">
            <i class="fa-solid fa-universal-access"></i>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            offset: 50,
            duration: 800
        });

        // Sticky Navbar: Seamless over Hero, Clean Pure White on Scroll
        <?php if ($isHome): ?>
        function handleNavbarScroll() {
            const navbar = document.getElementById('mainNavbar');
            if (!navbar) return;
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
        window.addEventListener('scroll', handleNavbarScroll, { passive: true });
        window.addEventListener('DOMContentLoaded', handleNavbarScroll);
        handleNavbarScroll();
        <?php endif; ?>

        // Accessibility JS
        let currentFontSizeOffset = 0;
        function changeFontSize(direction) {
            if (direction > 0 && currentFontSizeOffset >= 3) return; // limit max
            if (direction < 0 && currentFontSizeOffset <= -1) return; // limit min
            
            currentFontSizeOffset += direction;
            document.documentElement.style.fontSize = (100 + (currentFontSizeOffset * 10)) + '%';
        }
        function resetA11y() {
            currentFontSizeOffset = 0;
            document.documentElement.style.fontSize = '';
        }
        document.addEventListener('click', function(e) {
            const widget = document.querySelector('.a11y-widget');
            const menu = document.getElementById('a11yMenu');
            if (widget && !widget.contains(e.target) && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });
    </script>
</body>
</html>
