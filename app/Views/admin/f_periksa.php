<?php

	$id			= $datpil->id;

	$judul_ind		= $datpil->judul_ind;

	$judul_en		= $datpil->judul_en;

	$id_unitkerja	= $datpil->id_unitkerja;

	$unitkerja_ind  = $datpil->unitkerja_ind;

?>

<div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
        <div class="modal-header bg-gray-100 border-0 pt-4 px-4 pb-2">
            <h5 class="modal-title fw-bold text-dark" id="myModalLabel">
                <i class="bi bi-clipboard-check me-2 text-primary"></i>Verifikasi Pemeriksaan Data
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
            <form action="<?php echo site_url('admin/dda/act_periksa'); ?>" name="modal_popup" method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>" />

                <div class="alert bg-info-soft text-info border-0 rounded-3 mb-4 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                    <small>Konfirmasi bahwa data ini telah diperiksa kesesuaiannya dengan sumber.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary ls-1 mb-1">Instansi / Asal Data</label>
                    <input type="text" class="form-control bg-light border-0 fw-bold py-2" value="<?php echo $unitkerja_ind; ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary ls-1 mb-1">Judul Tabel</label>
                    <textarea class="form-control bg-light border-0 fw-medium py-2" rows="2" readonly><?php echo $judul_ind; ?></textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary ls-1 mb-1">Catatan Pemeriksaan</label>
                    <textarea name="catatan_periksa" class="form-control border-radius-md" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                </div>

                <div class="modal-footer border-0 px-0 pb-0 pt-3">
                    <button type="button" class="btn btn-link text-secondary mb-0 fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 10px;">
                        <i class="bi bi-check-lg me-2"></i> Konfirmasi Selesai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-info-soft { background-color: rgba(17, 205, 239, 0.08) !important; color: #11cdef !important; }
    .text-xxs { font-size: 0.7rem !important; }
    .ls-1 { letter-spacing: 0.8px; }
    .border-radius-md { border-radius: 0.6rem !important; }
    .bg-gray-100 { background-color: #f8f9fa !important; }
</style>
