<?php
$idp = $datpil->id;
$judul_ind = $datpil->judul_ind;
$kondef = $datpil->kondef;
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-info-circle me-2"></i>Konsep & Definisi
                </h5>
            </div>
            
            <div class="card-body p-4">
                <form action="<?php echo base_URL()?>index.php/admin/dda/kondef_update" method="POST">
                    <input type="hidden" name="idp" value="<?php echo $idp; ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary mb-2">Internal Judul Tabel</label>
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-primary">
                            <?php echo htmlspecialchars($judul_ind); ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary mb-2">Penjelasan Konsep Dan Definisi</label>
                        <textarea name="kondef" tabindex="1" required class="form-control" rows="6" placeholder="Masukkan penjelasan konsep dan definisi operasional untuk tabel ini..."><?php echo $kondef; ?></textarea>
                        <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Informasi ini akan membantu pengguna memahami data yang disajikan.</div>
                    </div>
                    
                    <hr class="text-secondary opacity-25 mt-4 mb-3">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo base_URL(); ?>index.php/admin/dda" class="btn btn-light border" tabindex="3">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary px-4" tabindex="2">
                            <i class="bi bi-check2-circle me-1"></i> Perbarui Kondef
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
