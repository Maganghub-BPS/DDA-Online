<div class="container-fluid py-2 px-3">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-5">
            <div class="card border-0 shadow-lg border-radius-2xl overflow-hidden">
                <!-- Premium Header -->
                <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape bg-primary-orange shadow-primary text-center border-radius-md d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
                            <i class="bi bi-shield-lock-fill text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bolder text-dark">Ubah Password</h5>
                            <p class="text-xs text-secondary mb-0">Perbarui kata sandi akun Anda</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4 pt-3">
                    <?php echo session()->getFlashdata("k_passwod"); ?>

                    <form action="<?php echo base_URL()?>index.php/admin/passwod/simpan" method="post" accept-charset="utf-8">	
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-1 ls-1 ps-1">Identitas Log-in</label>
                                <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none bg-gray-50 mb-3 opacity-7">
                                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-person text-secondary"></i></span>
                                    <input type="text" name="username" class="form-control border-0 py-2 ps-1 text-sm bg-transparent font-weight-bold" 
                                           readonly value="<?php echo session()->get('admin_user')?>">
                                </div>

                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-1 ls-1 ps-1">Kredensial Keamanan</label>
                                
                                <div class="mb-2">
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-key text-primary-orange"></i></span>
                                        <input type="password" name="p1" class="form-control border-0 py-2 ps-1 text-sm bg-white" 
                                               autofocus required placeholder="Masukkan Password Saat Ini">
                                    </div>
                                </div>

                                <div class="row g-2 mb-0 mt-2">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-shield-plus text-primary-orange"></i></span>
                                                <input type="password" name="p2" class="form-control border-0 py-2 ps-1 text-sm bg-white" 
                                                       required placeholder="Password Baru">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-shield-check text-primary-orange"></i></span>
                                                <input type="password" name="p3" class="form-control border-0 py-2 ps-1 text-sm bg-white" 
                                                       required placeholder="Ulangi Password Baru">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Footer -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                            <a href="<?php echo base_URL()?>index.php/admin" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none font-weight-bold">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn bg-primary-orange text-white btn-sm border-radius-lg px-5 mb-0 shadow-primary transition-all hover:scale-105">
                                <i class="bi bi-shield-check me-2"></i> Perbarui Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <div class="alert bg-gray-50 border-0 py-2 px-3 border-radius-lg d-inline-block shadow-none mb-0">
                    <span class="text-xs text-secondary opacity-7">
                        <i class="bi bi-info-circle-fill text-warning me-1"></i> Gunakan kombinasi karakter yang kuat.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.92rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.45rem !important; }
    
    .bg-gray-50 { background-color: #fcfcfc !important; }
    .bg-gray-100 { background-color: #f8f9fa !important; }
    
    .text-primary-orange { color: #FF6D1F !important; }
    .bg-primary-orange { background-color: #FF6D1F !important; }
    .shadow-primary { box-shadow: 0 4px 6px rgba(255, 109, 31, 11), 0 1px 3px rgba(255, 109, 31, 0.08) !important; }
    
    .icon-shape {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .input-group-alternative {
        transition: all 0.2s ease;
        background-color: #fff;
    }
    .input-group-alternative:focus-within {
        border-color: #FF6D1F !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
    }
    
    .form-control:focus {
        box-shadow: none !important;
        font-size: 0.92rem !important;
    }
    
    .hover\:scale-105:hover {
        transform: scale(1.02);
    }
    
    .transition-all {
        transition: all 0.25s ease;
    }

    .card-body {
        background-image: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
    }
</style>
