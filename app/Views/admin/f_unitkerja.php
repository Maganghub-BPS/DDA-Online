<?php
$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act			= "act_edt";
	$idp			= $datpil->id_unitkerja;
	$id_unitkerja	= $datpil->id_unitkerja;
	$unitkerja_ind	= $datpil->unitkerja_ind;
	$unitkerja_en	= $datpil->unitkerja_en;
	$jenis_instansi	= $datpil->jenis_instansi ?? 'vertikal';
	$user_wali		= $datpil->user_wali;
	$user_spv		= $datpil->user_spv;
	
	$db = \Config\Database::connect();
	$querynama_userwali = $db->query("SELECT nama FROM t_admin WHERE username = ? LIMIT 1", [$user_wali])->getRow();
	$nama_userwali = $querynama_userwali->nama ?? $user_wali;
} else {
	$act			= "act_add";
	$idp			= "";
	$id_unitkerja	= "";
	$unitkerja_ind	= "";
	$unitkerja_en	= "";
	$jenis_instansi	= "vertikal";
	$user_wali		= "";
	$user_spv		= "";
}
?>

<div class="container-fluid py-2 px-3">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 shadow-lg border-radius-2xl overflow-hidden">
                <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape bg-primary-orange shadow-primary text-center border-radius-md me-3" style="width: 42px; height: 42px;">
                            <i class="bi bi-building text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bolder text-dark"><?php echo ($mode == "edt") ? 'Edit Organisasi' : 'Tambah Organisasi Baru'; ?></h5>
                            <p class="text-xs text-secondary mb-0">Manajemen data Unit Kerja / OPD</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4 pt-3">
                    <form action="<?php echo base_URL(); ?>index.php/admin/master_opd/<?php echo esc($act); ?>" method="post" accept-charset="utf-8">
                        <input type="hidden" name="idp" value="<?php echo esc($idp); ?>">

                        <div class="row g-4">
                            <!-- Kode OPD -->
                            <div class="col-md-5">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Kode OPD / Unit Kerja</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-gray-100 border-0"><i class="bi bi-hash text-muted"></i></span>
                                    <input type="text" name="id_unitkerja" required value="<?php echo esc($id_unitkerja); ?>" 
                                           class="form-control border-0 bg-gray-100 ps-2" placeholder="Contoh: BAPPEDA" 
                                           tabindex="1" autofocus style="border-radius: 0 0.5rem 0.5rem 0 !important;">
                                </div>
                                <div class="text-xxs text-info mt-1 ps-1 opacity-7">Minimal 3 karakter</div>
                            </div>

                            <div class="col-12">
                                <hr class="horizontal bg-gray-200 my-1 opacity-1">
                            </div>

                            <!-- Klasifikasi Jenis Instansi (RBAC) -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Klasifikasi Instansi (Hak Akses RBAC)</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="border rounded-3 p-3 bg-white d-block cursor-pointer position-relative <?= ($jenis_instansi == 'opd') ? 'border-primary shadow-xs' : 'border-light'; ?>" style="cursor: pointer;">
                                            <div class="form-check ps-0 mb-0">
                                                <div class="d-flex align-items-center mb-0">
                                                    <input class="form-check-input me-2 mt-0" type="radio" name="jenis_instansi" id="jenis_opd" value="opd" <?= ($jenis_instansi == 'opd') ? 'checked' : ''; ?>>
                                                    <span class="fw-bold text-dark text-sm"><i class="bi bi-bank text-primary-orange me-1"></i> Organisasi Perangkat Daerah</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="border rounded-3 p-3 bg-white d-block cursor-pointer position-relative <?= ($jenis_instansi == 'vertikal') ? 'border-primary shadow-xs' : 'border-light'; ?>" style="cursor: pointer;">
                                            <div class="form-check ps-0 mb-0">
                                                <div class="d-flex align-items-center mb-0">
                                                    <input class="form-check-input me-2 mt-0" type="radio" name="jenis_instansi" id="jenis_vertikal" value="vertikal" <?= ($jenis_instansi == 'vertikal') ? 'checked' : ''; ?>>
                                                    <span class="fw-bold text-dark text-sm"><i class="bi bi-building text-info me-1"></i> Organisasi Vertikal</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Nama Indonesia -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Nama Unit Kerja (Bahasa Indonesia)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100">
                                    <input type="text" name="unitkerja_ind" required value="<?php echo esc($unitkerja_ind); ?>" 
                                           class="form-control border-0 py-2 ps-3" placeholder="Masukkan nama organisasi..." tabindex="2">
                                </div>
                            </div>

                            <!-- Nama English -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Organization Name (English Translation)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100">
                                    <input type="text" name="unitkerja_en" required value="<?php echo esc($unitkerja_en); ?>" 
                                           class="form-control border-0 py-2 ps-3 text-italic text-secondary" placeholder="Organization name in English..." tabindex="3">
                                </div>
                            </div>

                            <!-- LO Row -->
                            <div class="col-md-12">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Tim Pembina / Liaison Officer (Wali Data)</label>
                                <div class="input-group border-radius-lg overflow-hidden shadow-none border border-gray-100 bg-white">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-people-fill text-primary-orange"></i></span>
                                    <select name="user_wali" id="user_wali" class="form-select border-0 py-2" tabindex="5">
                                        <?php if ($mode == "edt" || $mode == "act_edt"): ?>
                                            <option selected value="<?php echo esc($user_wali, 'attr'); ?>"><?php echo esc($nama_userwali); ?></option>
                                        <?php else: ?>
                                            <option selected value="Kosong">- Pilih Tim Pembina / Walidata -</option>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $db = \Config\Database::connect();
                                        $user_wali_data = $db->query("SELECT username, nama FROM t_admin WHERE id_unitkerja = 'bps' ORDER BY nama ASC")->getResultArray();
                                        foreach($user_wali_data as $p){
                                            if ($p['username'] != $user_wali) {
                                                echo "<option value='" . esc($p['username'], 'attr') . "'>" . esc($p['nama']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                            <a href="<?php echo base_URL(); ?>index.php/admin/master_opd" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn bg-primary-orange text-white btn-sm border-radius-lg px-5 mb-0 shadow-primary transition-all hover:scale-105" tabindex="7">
                                <i class="bi bi-check-circle me-1"></i> Simpan Data
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer bg-gray-50 border-0 py-3 text-center">
                    <p class="text-xxs text-secondary opacity-5 mb-0">Pastikan Kode OPD belum pernah digunakan sebelumnya.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.875rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-xl { border-radius: 1rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.4rem !important; }
    
    .bg-gray-50 { background-color: #fcfcfc !important; }
    .bg-gray-100 { background-color: #f8f9fa !important; }
    .bg-gray-200 { background-color: #e9ecef !important; }
    
    .text-primary-orange { color: #FF6D1F !important; }
    .bg-primary-orange { background-color: #FF6D1F !important; }
    .shadow-primary { box-shadow: 0 4px 6px rgba(255, 109, 31, 0.11), 0 1px 3px rgba(255, 109, 31, 0.08) !important; }
    
    .icon-shape {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #FF6D1F !important;
        box-shadow: 0 0 0 2px rgba(255, 109, 31, 0.1) !important;
        background-color: #fff !important;
        font-size: 0.92rem !important;
    }
    
    .input-group:focus-within {
        border-color: #FF6D1F !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
    }
    
    .form-label {
        margin-left: 2px;
    }
</style>
