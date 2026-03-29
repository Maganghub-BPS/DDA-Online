<?php

	$id						= $datpil->id;

	$judul_ind		= $datpil->judul_ind;

	$judul_en		= $datpil->judul_en;

?>

<div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
        <div class="modal-header bg-danger-soft border-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold text-danger" id="myModalLabel">
                <i class="bi bi-x-circle me-2"></i>Hapus Master Tabel
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
            <form action="<?php echo base_URL()?>index.php/admin/master_tabel/act_del/" name="modal_popup" method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>" />

                <div class="alert bg-gray-50 border-0 rounded-4 mb-4 d-flex align-items-center">
                    <div class="icon-circle bg-light me-3">
                        <i class="bi bi-trash3 text-danger fs-4"></i>
                    </div>
                    <div>
                        <span class="fw-bold d-block text-dark">Konfirmasi Penghapusan</span>
                        <small class="text-secondary text-xs">Master tabel ini akan dihapus dari sistem secara permanen.</small>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary ls-1 mb-1">Judul Tabel</label>
                    <div class="p-3 bg-light rounded-3 fw-bold text-dark border-1 border-light border text-sm" style="line-height: 1.4;">
                        <?php echo htmlspecialchars($judul_ind ?: $judul_en); ?>
                    </div>
                </div>

                <div class="modal-footer border-0 px-0 pb-0 pt-4">
                    <button type="button" class="btn btn-link text-secondary mb-0 fw-bold" data-bs-dismiss="modal">Batalkan</button>
                    <button type="submit" class="btn btn-danger px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-trash3 me-2"></i> Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.05) !important; color: #dc3545 !important; }
    .text-xxs { font-size: 0.72rem !important; }
    .text-xs { font-size: 0.8rem !important; }
    .ls-1 { letter-spacing: 0.8px; }
    .bg-gray-50 { background-color: #fcfcfc !important; }
    .icon-circle { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
</style>
