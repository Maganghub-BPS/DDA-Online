<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>.:: Jateng Dalam Angka — Login ::.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo base_url('aset/images/logo-dda.svg'); ?>">
    <link rel="alternate icon" type="image/png" href="<?php echo base_url('aset/favicon.png'); ?>">

    <style>
        :root {
            --brand-navy: #0d2c4d;
            --primary-orange: #F26522;
            --accent-cyan: #0284c7;
            --text-dark: #0f172a;
            --text-slate: #334155;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-style: normal !important;
        }

        /* -------------------------------------------------------------
           1. FULLSCREEN TANPA SCROLLBAR DI DESKTOP (100vh, overflow: hidden)
           MENGGUNAKAN GAMBAR ASLI HERO SECTION BERANDA
        ------------------------------------------------------------- */
        html, body {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-style: normal;
            background-color: #f0f6fc;
            /* Gambar Asli Hero Section Beranda Ditampilkan Terang & Bersih */
            background-image: 
                radial-gradient(ellipse at 50% 50%, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0.15) 60%, transparent 100%),
                url('<?php echo base_url('aset/images/hero-home-bg.jpeg'); ?>');
            background-size: cover;
            background-position: center bottom;
            background-repeat: no-repeat;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* -------------------------------------------------------------
           2. TOP HEADER NAVBAR (Hanya Logo & Instansi, Tanpa Tombol Kembali)
        ------------------------------------------------------------- */
        .top-navbar {
            position: relative;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 48px;
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
        }

        .brand-logo-link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo-badge {
            width: 40px;
            height: 40px;
            background: #ffffff;
            border: 1px solid rgba(13, 44, 77, 0.12);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(13, 44, 77, 0.08);
        }

        .brand-logo-icon {
            width: 26px;
            height: 26px;
            object-fit: contain;
        }

        .brand-logo-text {
            display: flex;
            flex-direction: column;
        }

        .brand-logo-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--brand-navy);
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .brand-logo-title span {
            color: var(--primary-orange);
        }

        .brand-logo-sub {
            font-size: 0.74rem;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        /* -------------------------------------------------------------
           3. MAIN CONTENT (2-Column Split: Bersih Tanpa Garis & Badge)
        ------------------------------------------------------------- */
        .main-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1060px;
            margin: auto;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 72px;
            flex: 1;
        }

        /* SISI KIRI: Headline & Deskripsi Rapi */
        .hero-left-section {
            flex: 1;
            max-width: 520px;
        }

        .headline-text {
            font-size: clamp(2.3rem, 3.8vw, 3.1rem);
            font-weight: 800;
            line-height: 1.16;
            letter-spacing: -0.03em;
            color: var(--brand-navy);
            margin-bottom: 20px;
            transform: none !important;
        }

        .headline-text span.text-orange {
            background: linear-gradient(135deg, #F26522 0%, #D65518 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .headline-description {
            font-size: 1.1rem;
            color: var(--text-slate);
            line-height: 1.65;
            margin-bottom: 0;
            font-weight: 500;
            transform: none !important;
        }

        /* -------------------------------------------------------------
           4. SISI KANAN: Card Login Frosted Glass Bersih & Mewah
        ------------------------------------------------------------- */
        .login-card-frosted {
            flex: 0 0 410px;
            background: rgba(255, 255, 255, 0.92); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1.5px solid rgba(255, 255, 255, 0.95);
            border-radius: 26px;
            padding: 34px 32px;
            box-shadow: 
                0 25px 60px -10px rgba(13, 44, 77, 0.18),
                0 0 1px rgba(13, 44, 77, 0.12);
            position: relative;
            color: var(--text-dark);
        }

        .card-header-box {
            margin-bottom: 22px;
        }

        .card-header-box h4 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--brand-navy);
            margin-bottom: 4px;
            letter-spacing: -0.4px;
        }

        .card-header-box p {
            font-size: 0.86rem;
            color: var(--text-muted);
            margin: 0;
            font-weight: 500;
        }

        /* Form Controls */
        .form-group-item {
            margin-bottom: 16px;
        }

        .form-label-custom {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .input-box {
            display: flex;
            align-items: stretch;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.22s ease;
        }

        .input-box:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }

        .input-box:focus-within {
            border-color: var(--brand-navy);
            background: #ffffff;
            box-shadow: 0 0 0 3.5px rgba(13, 44, 77, 0.1);
        }

        .input-box-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            background: #f1f5f9;
            border-right: 1.5px solid #e2e8f0;
            color: #64748b;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .input-box:focus-within .input-box-icon {
            color: var(--brand-navy);
            background: #eff6ff;
        }

        .form-control-custom {
            border: none;
            outline: none;
            padding: 11px 14px;
            font-size: 0.92rem;
            width: 100%;
            color: #0f172a;
            background: transparent;
            font-family: inherit;
            font-weight: 600;
        }

        .form-control-custom::placeholder {
            color: #94a3b8;
            font-size: 0.88rem;
            font-weight: 400;
        }

        .form-select-custom {
            border: none;
            outline: none;
            padding: 11px 14px;
            font-size: 0.92rem;
            width: 100%;
            color: #0f172a;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
        }

        .form-select-custom option {
            background-color: #ffffff;
            color: #0f172a;
            padding: 6px;
        }

        /* Password Toggle Button */
        .password-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.05rem;
            transition: color 0.2s, transform 0.2s;
        }

        .password-toggle:hover {
            color: var(--brand-navy);
            transform: scale(1.1);
        }

        /* Tombol Masuk */
        .btn-login-navy {
            width: 100%;
            padding: 12.5px;
            background: linear-gradient(135deg, #0d2c4d 0%, #174275 100%);
            border: none;
            color: #ffffff;
            font-size: 0.96rem;
            font-weight: 700;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(13, 44, 77, 0.25);
            transition: all 0.25s ease;
            margin-top: 20px;
        }

        .btn-login-navy:hover {
            background: linear-gradient(135deg, #10365e 0%, #1e528f 100%);
            box-shadow: 0 10px 28px rgba(13, 44, 77, 0.35);
            transform: translateY(-1.5px);
            color: #ffffff;
        }

        .btn-login-navy:active {
            transform: translateY(1px);
        }

        /* Alert */
        .alert-custom-danger {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
            border-radius: 11px;
            padding: 9px 13px;
            font-size: 0.84rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .alert-custom-danger .btn-close {
            margin-left: auto;
            padding: 4px;
            font-size: 0.65rem;
        }

        /* -------------------------------------------------------------
           5. BOTTOM FOOTER
        ------------------------------------------------------------- */
        .page-footer {
            position: relative;
            z-index: 3;
            text-align: center;
            padding: 14px 20px;
            font-size: 0.8rem;
            color: #475569;
            font-weight: 600;
        }

        .page-footer a {
            color: var(--brand-navy);
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s;
        }

        .page-footer a:hover {
            color: var(--primary-orange);
        }

        /* -------------------------------------------------------------
           6. RESPONSIVE BREAKPOINT
        ------------------------------------------------------------- */
        @media (max-width: 991.98px) {
            html, body {
                height: auto;
                max-height: none;
                overflow-y: auto;
            }

            .top-navbar {
                padding: 18px 20px;
            }

            .main-content {
                flex-direction: column;
                padding: 16px 20px 32px;
                gap: 32px;
                text-align: center;
                flex: none;
            }

            .hero-left-section {
                max-width: 100%;
            }

            .headline-text {
                font-size: 2.5rem;
                margin-bottom: 16px;
            }

            .login-card-frosted {
                flex: none;
                width: 100%;
                max-width: 410px;
                text-align: left;
                padding: 26px 24px;
            }
        }
    </style>
</head>

<body>
    <?php
    $db = \Config\Database::connect();
    $q_instansi = $db->query("SELECT * FROM tr_instansi LIMIT 1")->getRow();
    ?>

    <!-- 1. TOP HEADER NAVBAR (Hanya Logo & Instansi, Tanpa Tombol Kembali) -->
    <header class="top-navbar">
        <a href="<?php echo base_url(); ?>" class="brand-logo-link">
            <div class="brand-logo-badge">
                <img src="<?php echo base_url('aset/images/logo-dda.svg'); ?>" 
                     alt="Logo DDA Online" 
                     class="brand-logo-icon"
                     onerror="this.onerror=null; this.src='<?php echo base_url('aset/images/logo-dda.png'); ?>';">
            </div>
            <div class="brand-logo-text">
                <span class="brand-logo-title">DDA <span>Online</span></span>
                <span class="brand-logo-sub">Provinsi Jawa Tengah</span>
            </div>
        </a>
    </header>

    <!-- 2. MAIN SPLIT CONTENT (Bersih Tanpa Garis Melengkung, Badge, & Stat Pills) -->
    <main class="main-content">
        <!-- SEBELAH KIRI: Headline & Deskripsi Saja -->
        <section class="hero-left-section">
            <!-- Judul Utama -->
            <h1 class="headline-text">
                Satu Data,<br>
                <span class="text-orange">Daerah Dalam Angka.</span>
            </h1>

            <!-- Subjudul Deskripsi -->
            <p class="headline-description">
                Pusat Pengelolaan Daerah Dalam Angka. Susun, verifikasi, dan publikasikan tabel statistik dari satu tempat
            </p>
        </section>

        <!-- SEBELAH KANAN: Card Login Frosted Glass Bersih & Mewah -->
        <section class="login-card-frosted">
            <div class="card-header-box">
                <h4>Selamat Datang!</h4>
                <p>Masuk Ke Sistem Pengelolaan DDA Online</p>
            </div>

            <!-- Flashdata Alert -->
            <?php if (session()->getFlashdata("k")): ?>
                <div class="alert-custom-danger" id="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                    <div class="flex-grow-1">
                        <?php echo session()->getFlashdata("k"); ?>
                    </div>
                    <button type="button" class="btn-close" aria-label="Close" onclick="document.getElementById('alert').remove();"></button>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="<?php echo base_URL(); ?>index.php/admin/do_login" method="post">
                <?= csrf_field(); ?>

                <!-- Username -->
                <div class="form-group-item">
                    <label class="form-label-custom" for="username">Username</label>
                    <div class="input-box">
                        <span class="input-box-icon"><i class="bi bi-person"></i></span>
                        <input type="text" 
                               id="username" 
                               name="u" 
                               class="form-control-custom" 
                               placeholder="Masukkan username" 
                               required 
                               autofocus 
                               autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group-item">
                    <label class="form-label-custom" for="password">Password</label>
                    <div class="input-box">
                        <span class="input-box-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" 
                               id="password" 
                               name="p" 
                               class="form-control-custom" 
                               placeholder="Masukkan password" 
                               required 
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword" title="Lihat/Sembunyikan password">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Tahun -->
                <div class="form-group-item">
                    <label class="form-label-custom" for="tahun">Tahun</label>
                    <div class="input-box">
                        <span class="input-box-icon"><i class="bi bi-calendar-event"></i></span>
                        <select name="ta" id="tahun" class="form-select-custom">
                            <option value="">-- Opt. for Admin --</option>
                            <?php
                            for ($i = 2016; $i <= (date('Y') + 1); $i++) {
                                if (date('Y') == $i) {
                                    echo "<option value='$i' selected>$i</option>";
                                } else {
                                    echo "<option value='$i'>$i</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login-navy">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Masuk</span>
                </button>
            </form>
        </section>
    </main>

    <!-- 3. FOOTER -->
    <footer class="page-footer">
        DDA Online &copy; <?php echo date('Y'); ?> 
        <a href="http://jateng.bps.go.id" target="_blank" rel="noopener">BPS Provinsi Jawa Tengah</a>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dismiss alert
            const alertEl = document.getElementById('alert');
            if (alertEl) {
                setTimeout(function() {
                    alertEl.remove();
                }, 5000);
            }

            // Password Toggle
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (togglePassword && password && eyeIcon) {
                togglePassword.addEventListener('click', function(e) {
                    e.preventDefault();
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    eyeIcon.classList.toggle('bi-eye');
                    eyeIcon.classList.toggle('bi-eye-slash');
                });
            }
        });
    </script>
</body>

</html>
