<?php
	$today=getdate();
	$tahunsekarang=$today['year'];

	$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act		= "act_edt";
	$judul_en				= "KONFIRMASI TABEL USUSLAN";
	$idp					= $datpil->id;
	$judul_ind				= $datpil->judul_ind;
	$judul_en				= $datpil->judul_en;
	$id_unitkerja			= $datpil->id_unitkerja;
	$file_tabel				= $datpil->file_tabel;
} else {
	$act		= "act_add";
	$judul_en	= " TAMBAH MASTER TABEL";
	$idp		= "";
	$judul_ind				= "";
	$judul_en				= "";
	$id_unitkerja			= session()->get('admin_unitkerja');
	$file_tabel				= "";
	
}
?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel_opd" class="btn btn-sm btn-light border-0 shadow-none bg-light-subtle d-flex align-items-center justify-content-center" style="border-radius: 10px; width: 40px; height: 40px;"><i class="bi bi-arrow-left fs-5"></i></a>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-patch-question-fill me-2 text-primary"></i>Konfirmasi Usulan Tabel Baru
                    </h5>
                </div>
            </div>
            
            <div class="card-body p-4">
                <form action="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                
                <input type="hidden" name="idp" value="<?php echo $idp; ?>">
                <input type="hidden" name="id_unitkerja" value="<?php echo $id_unitkerja; ?>">

                <div class="alert bg-primary-soft text-primary border-0 rounded-4 mb-4 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                    <div>
                        <span class="fw-bold d-block">Peninjauan Usulan</span>
                        <small>Periksa kembali judul dan file sebelum menyetujui usulan ini masuk ke Master Tabel.</small>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Judul Bahasa Indonesia</label>
                            <textarea name="judul_ind" tabindex="1" required class="form-control border-radius-lg p-3" rows="3" placeholder="Ketik judul tabel bahasa Indonesia..."><?php echo $judul_ind; ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Judul Bahasa Inggris</label>
                            <textarea name="judul_en" tabindex="2" required class="form-control border-radius-lg p-3" rows="3" placeholder="Type english table title..."><?php echo $judul_en; ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">File Tabel</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-file-earmark-arrow-up"></i></span>
                                    <input type="file" name="file_tabel" tabindex="3" class="form-control border-start-0" value="<?php echo $file_tabel;?>">
                                </div>
                                <?php if($file_tabel): ?>
                                <div class="mt-2">
                                    <a href="<?php echo base_URL()?>upload/tabel_usulan/<?php echo $file_tabel; ?>" target='_blank' class="badge bg-light text-primary border-primary-soft text-decoration-none px-3 py-2 fw-medium">
                                        <i class="bi bi-file-earmark-text me-1"></i> <?php echo $file_tabel;?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="text-secondary opacity-10 my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel_opd" class="btn btn-light border-0 shadow-none bg-light-subtle px-4 py-2" style="border-radius: 10px;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 10px; background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-check-lg me-2"></i> Setujui & Simpan
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
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
</style>
