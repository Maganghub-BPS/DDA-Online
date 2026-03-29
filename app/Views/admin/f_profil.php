<?php $datpil = (object)$datpil; ?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 shadow-lg border-radius-2xl overflow-hidden">
                <!-- Header Card -->
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape bg-primary-orange shadow-primary text-center border-radius-md me-3" style="width: 46px; height: 46px;">
                            <i class="bi bi-person-bounding-box text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bolder text-dark">Profil Saya</h5>
                            <p class="text-xs text-secondary mb-0">Kelola informasi data pribadi Anda</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 pt-4">
                    <form action="<?php echo base_URL(); ?>index.php/admin/profil/act_edt" method="post" accept-charset="utf-8">
                        <input type="hidden" name="idp" value="<?php echo $datpil->id; ?>">
                        
                        <?php echo session()->getFlashdata("k"); ?>

                        <div class="row g-4">
                            <!-- Field Nama Lengkap -->
                            <div class="col-md-6">
                                <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1 ls-1 text-uppercase">Nama Lengkap</label>
                                <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-person text-primary-orange"></i></span>
                                    <input type="text" name="nama" required value="<?php echo $datpil->nama; ?>" 
                                           class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Masukkan nama lengkap" tabindex="1">
                                </div>
                            </div>

                            <!-- Field Username -->
                            <div class="col-md-6">
                                <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1 ls-1 text-uppercase">Username</label>
                                <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-at text-primary-orange"></i></span>
                                    <input type="text" name="username" required value="<?php echo $datpil->username; ?>" 
                                           class="form-control border-0 py-2 ps-1 text-sm bg-white font-weight-bold" placeholder="Username login" tabindex="2">
                                </div>
                            </div>

                            <!-- Field NIP -->
                            <div class="col-md-6">
                                <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1 ls-1 text-uppercase">NIP / ID Pegawai</label>
                                <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-card-text text-primary-orange"></i></span>
                                    <input type="text" name="nip" required value="<?php echo $datpil->nip; ?>" 
                                           class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Masukkan NIP" tabindex="3">
                                </div>
                            </div>

                            <!-- Field Email -->
                            <div class="col-md-6">
                                <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1 ls-1 text-uppercase">Alamat Email</label>
                                <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-envelope text-primary-orange"></i></span>
                                    <input type="email" name="email" required value="<?php echo $datpil->email; ?>" 
                                           class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="nama@instansi.go.id" tabindex="4">
                                </div>
                            </div>

                            <!-- Info Section (Read Only) -->
                            <div class="col-12 mt-4">
                                <div class="bg-light-subtle rounded-4 p-3 border border-light border-radius-lg d-flex align-items-center">
                                    <div class="icon-circle bg-white border border-light me-3 shadow-sm">
                                        <i class="bi bi-shield-lock text-primary-orange"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-sm mb-0 fw-bold">Keamanan Akun</h6>
                                        <p class="text-xs text-secondary mb-0">Tingkat Akses: <span class="badge bg-primary-soft text-primary-orange border-0 py-1 px-2"><?php echo strtoupper($datpil->level); ?></span></p>
                                    </div>
                                    <div class="ms-auto">
                                        <a href="<?php echo base_url(); ?>admin/passwod" class="btn btn-outline-primary btn-xs mb-0 border-radius-md px-3">
                                            <i class="bi bi-key me-1"></i> Ganti Password
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Footer -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-light">
                            <a href="<?php echo base_URL(); ?>index.php/admin" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn bg-primary-orange text-white btn-sm border-radius-lg px-5 mb-0 shadow-primary transition-all hover:scale-105" tabindex="5">
                                <i class="bi bi-check-circle me-2"></i> Perbarui Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.72rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.9rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.8rem !important; }
    .border-radius-md { border-radius: 0.5rem !important; }
    
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .text-primary-orange { color: #FF6D1F !important; }
    .bg-primary-orange { background-color: #FF6D1F !important; }
    .shadow-primary { box-shadow: 0 4px 6px rgba(255, 109, 31, 0.11), 0 1px 3px rgba(255, 109, 31, 0.08) !important; }
    
    .icon-shape {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .icon-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
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
    }

    .btn-xs {
        padding: 0.35rem 0.8rem;
        font-size: 0.75rem;
    }
</style>
