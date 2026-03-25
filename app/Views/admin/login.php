
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #FF6D1F;
            --primary-hover: #e05e15;
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            position: relative;
            overflow: hidden;
        }

        /* Floating decorative circles */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 109, 31, 0.08);
            border-radius: 50%;
            top: -80px;
            right: -80px;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 109, 31, 0.06);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
            z-index: 0;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .brand-logo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            padding: 5px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }

        .brand-logo h4 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #1e293b;
            margin-top: 12px;
            font-size: 1.25rem;
        }

        .brand-logo p {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .login-card h5 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 1.5rem;
        }

        .login-card .subtitle {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .input-group {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .input-group-text {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-right: none;
            color: #94a3b8;
            padding: 10px 14px;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            border-left: none;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            z-index: 0 !important;
        }

        /* Password Toggle Button */
        .password-toggle {
            cursor: pointer;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: none;
            color: #94a3b8;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 109, 31, 0.1);
        }

        .form-control:focus + .input-group-text,
        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .form-select {
            border: 1px solid #e2e8f0;
            border-left: none;
            padding: 10px 14px;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 109, 31, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, #FF6D1F 0%, #e05e15 100%);
            border: none;
            color: #fff;
            padding: 13px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 15px rgba(255, 109, 31, 0.3);
        }

        .btn-login:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(255, 109, 31, 0.4);
            color: #fff;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* Alert animation */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            animation: fadeInDown 0.5s ease;
            border-radius: 12px;
            border: none;
            font-size: 0.9rem;
        }

        /* Decorative extra circle */
        .circle-decoration {
            position: absolute;
            width: 120px;
            height: 120px;
            background: rgba(255, 109, 31, 0.05);
            border-radius: 50%;
            bottom: 20%;
            right: 10%;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="circle-decoration"></div>

    <?php 
    $db = \Config\Database::connect();
    $q_instansi = $db->query("SELECT * FROM tr_instansi LIMIT 1")->getRow();
    ?>

    <div class="login-wrapper">
        <!-- Brand Logo -->
        <div class="brand-logo">
            <img src="<?php echo base_url(); ?>upload/<?php echo $q_instansi->logo; ?>" alt="Logo">
            <h4><?php echo $q_instansi->nama; ?></h4>
            <p><?php echo $q_instansi->alamat; ?></p>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <h5>Selamat Datang</h5>
            <p class="subtitle">Masuk ke Sistem Pengelolaan Jateng Dalam Angka</p>

            <!-- Alert -->
            <?php if(session()->getFlashdata("k")): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo session()->getFlashdata("k"); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="<?php echo base_URL(); ?>index.php/admin/do_login" method="post">
                <!-- Username -->
                <div class="mb-2">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="u" class="form-control" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-2 mt-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="p" id="password" class="form-control" placeholder="Masukkan password" required>
                        <span class="password-toggle" id="togglePassword">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Tahun -->
                <div class="mb-3 mt-3">
                    <label class="form-label">Tahun Data</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <select name="ta" class="form-select">
                            <option value="">-- Opt. for Admin --</option>
                            <?php 
                            for ($i = 2016; $i <= (date('Y')+1); $i++) {
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

                <!-- Submit -->
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                </button>
            </form>
        </div>

        <div class="footer-text">
            Versi 5.0 &copy; <a href="http://jateng.bps.go.id" style="color: var(--primary-color); text-decoration: none;">BPS Provinsi Jawa Tengah</a>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var alertEl = document.getElementById('alert');
            if (alertEl) {
                setTimeout(function() {
                    alertEl.style.transition = 'opacity 0.8s ease';
                    alertEl.style.opacity = '0';
                    setTimeout(function() { alertEl.remove(); }, 800);
                }, 5000);
            }

            // Password Toggle
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Toggle Icon
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>
</html>
