<?php

	$id			= $datpil->id;

	$kepentingan_kel		= $datpil->kepentingan_kel;

	$kepentingan_uraian		= $datpil->kepentingan_uraian;

?>

<div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
        <div class="modal-header bg-danger-soft border-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold text-danger" id="myModalLabel">
                <i class="bi bi-trash3 me-2"></i>Konfirmasi Hapus Data
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
            <form action="<?php echo base_URL()?>index.php/admin/oi/act_del/" name="modal_popup" method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>" />

                <div class="alert bg-gray-50 border-0 rounded-3 mb-4 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-3"></i>
                    <div>
                        <span class="fw-bold d-block text-dark">Data akan dihapus permanen!</span>
                        <small class="text-secondary">Tindakan ini tidak dapat dibatalkan.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary ls-1 mb-1">Uraian / Keterangan</label>
                    <div class="p-3 bg-light rounded-3 fw-medium text-dark border-1 border-light border text-sm">
                        <?php echo htmlspecialchars($kepentingan_kel . ' : ' . $kepentingan_uraian); ?>
                    </div>
                </div>

                <div class="modal-footer border-0 px-0 pb-0 pt-3">
                    <button type="button" class="btn btn-link text-secondary mb-0 fw-bold" data-bs-dismiss="modal">Batalkan</button>
                    <button type="submit" class="btn btn-danger px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 10px;">
                        <i class="bi bi-trash3 me-2"></i> Hapus Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.05) !important; color: #dc3545 !important; }
    .text-xxs { font-size: 0.7rem !important; }
    .ls-1 { letter-spacing: 0.8px; }
    .bg-gray-50 { background-color: #fcfcfc !important; }
</style>
