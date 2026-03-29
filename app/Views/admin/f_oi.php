<?php
$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act		= "act_edt";
	$judul		= "Edit Data Out In";
	$idp		= $datpil->id;
	$nip			= $datpil->nip;
	$tgl				= $datpil->tgl;
	$jam_keluar			= $datpil->jam_keluar;
	$jam_masuk				= $datpil->jam_masuk;
	$status					= $datpil->status;
	$kepentingan_kel		= $datpil->kepentingan_kel;
	$kepentingan_uraian		= $datpil->kepentingan_uraian;
	
} else {
	$act					= "act_add";
	$idp					= "";
	$judul					= "Entri Data Keluar Kantor";
	$nip					= "";
	$tgl					= "";
	$jam_keluar				= "";
	$jam_masuk				= "";
	$status					= "";
	$kepentingan_kel		= "";
	$kepentingan_uraian		= "";
}

?>
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <div class="d-flex align-items-center gap-3">
                    <a href="javascript:history.back()" class="btn btn-sm btn-light border-0 shadow-none bg-light-subtle d-flex align-items-center justify-content-center" style="border-radius: 10px; width: 40px; height: 40px;"><i class="bi bi-arrow-left fs-5"></i></a>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-box-arrow-right me-2 text-primary"></i><?php echo $judul; ?>
                    </h5>
                </div>
            </div>
            
            <div class="card-body p-4">
                <?php echo session()->getFlashdata("k"); ?>
                <form action="<?php echo base_URL() ?>index.php/admin/oi/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                <input type="hidden" name="idp" value="<?php echo $idp; ?>">

                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary mb-3 ls-1 text-uppercase text-xxs">Keperluan Data Keluar Kantor</label>
                    <div class="d-flex flex-column gap-2 bg-light p-3 rounded-4">
                        <?php 
                        $keperluans = ["Sensus/Survei", "Rapat di Luar Kantor", "Besuk/Layat (Teman Kantor)", "Lainnya"];
                        foreach($keperluans as $kp): ?>
                            <div class="form-check custom-radio">
                                <input class="form-check-input" type="radio" name="kepentingan_kel" id="kp_<?php echo md5($kp); ?>" value="<?php echo $kp; ?>" <?php echo ($kepentingan_kel == $kp) ? 'checked' : ''; ?> required>
                                <label class="form-check-label fw-medium ms-2" for="kp_<?php echo md5($kp); ?>">
                                    <?php echo $kp; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Uraian / Keterangan</label>
                    <textarea name="kepentingan_uraian" id="kepentingan_uraian" class="form-control border-radius-lg p-3" rows="4" style="text-transform: uppercase" required placeholder="Jelaskan detail keperluan keluar kantor..."><?php echo $kepentingan_uraian; ?></textarea>
                </div>

                <hr class="text-secondary opacity-10 my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="javascript:history.back()" class="btn btn-light border-0 shadow-none bg-light-subtle px-4 py-2" style="border-radius: 10px;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 10px; background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-save me-2"></i> Simpan Data
                    </button>
                </div>

                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.8rem !important; }
    .custom-radio .form-check-input:checked {
        background-color: #FF6D1F;
        border-color: #FF6D1F;
    }
    .form-check-label { cursor: pointer; }
</style>
	


	
