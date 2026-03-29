<?php $data = (object)$data; ?>
<div class="container-fluid py-2 px-3">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg border-radius-2xl overflow-hidden">
                <!-- Compact Header -->
                <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape bg-primary-orange shadow-primary text-center border-radius-md me-3" style="width: 42px; height: 42px;">
                            <i class="bi bi-gear-fill text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bolder text-dark">Konfigurasi Instansi</h5>
                            <p class="text-xs text-secondary mb-0">Sesuaikan profil lembaga Anda</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4 pt-3">
                    <form action="<?php echo base_URL(); ?>index.php/admin/pengguna/act_edt" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                        <input type="hidden" name="idp" value="<?php echo $data->id; ?>">
                        
                        <?php echo session()->getFlashdata("k"); ?>

                        <div class="row g-3">
                            <!-- Left Column: Primary Info -->
                            <div class="col-md-6 border-end border-light pe-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Informasi Instansi</label>
                                
                                <div class="mb-3">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Nama Instansi</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-building text-primary-orange"></i></span>
                                        <input type="text" name="nama" required value="<?php echo $data->nama; ?>" 
                                               class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama Instansi" tabindex="1">
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Alamat Lengkap</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0 align-items-start pt-2"><i class="bi bi-geo-alt text-primary-orange"></i></span>
                                        <textarea name="alamat" required class="form-control border-0 py-2 ps-1 text-sm bg-white" 
                                                  placeholder="Alamat Lengkap Instansi" style="height: 100px;" tabindex="2"><?php echo $data->alamat; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Leadership & Media -->
                            <div class="col-md-6 ps-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Profil Pimpinan & Aset</label>
                                
                                <div class="mb-2">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">Nama Pimpinan / Kepala</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-person-badge text-primary-orange"></i></span>
                                        <input type="text" name="kepsek" required value="<?php echo $data->kepsek; ?>" 
                                               class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama Pimpinan" tabindex="3">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1">NIP Pimpinan</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-upc-scan text-primary-orange"></i></span>
                                        <input type="text" name="nip_kepsek" required value="<?php echo $data->nip_kepsek; ?>" 
                                               class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="NIP Pimpinan" tabindex="4">
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label text-xxs text-secondary mb-1 ps-1">Update Logo Instansi (.png/.jpg)</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none bg-gray-50">
                                        <input type="file" name="logo" class="form-control border-0 py-2 ps-3 text-xs bg-transparent" tabindex="5">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Action Footer -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                            <a href="<?php echo base_URL(); ?>index.php/admin" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn bg-primary-orange text-white btn-sm border-radius-lg px-5 mb-0 shadow-primary transition-all hover:scale-105" tabindex="6">
                                <i class="bi bi-save me-2"></i> Simpan
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
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.9rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.4rem !important; }
    
    .bg-gray-100 { background-color: #f8f9fa !important; }
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .bg-danger-soft { background-color: rgba(234, 6, 6, 0.08) !important; color: #ea0606 !important; }
    .bg-success-soft { background-color: rgba(45, 206, 137, 0.08) !important; color: #2dce89 !important; }
    .bg-info-soft { background-color: rgba(17, 205, 239, 0.08) !important; color: #11cdef !important; }
    
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    .border-danger-soft { border: 1px solid rgba(234, 6, 6, 0.15) !important; }
    .border-success-soft { border: 1px solid rgba(45, 206, 137, 0.15) !important; }
    .border-info-soft { border: 1px solid rgba(17, 205, 239, 0.15) !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.92rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .text-primary-orange { color: #FF6D1F !important; }
    .bg-primary-orange { background-color: #FF6D1F !important; }
    .shadow-primary { box-shadow: 0 4px 6px rgba(255, 109, 31, 0.11), 0 1px 3px rgba(255, 109, 31, 0.08) !important; }
    
    .icon-shape {
        width: 44px;
        height: 44px;
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
    
    .card-body {
        background-image: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
    }

    @media (min-width: 992px) {
        .border-end {
            border-right: 1px solid #f1f1f1 !important;
        }
    }
</style>
