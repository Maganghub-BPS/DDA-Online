<?php
$today = getdate();
$tahunsekarang = $today['year'];
$mode = segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
    $act                    = "act_edt";
    $judul_en_page          = "EDIT MASTER TABEL";
    $idp                    = $datpil->id;
    $judul_ind              = $datpil->judul_ind;
    $judul_en               = $datpil->judul_en;
    $link_tabel             = $datpil->link_tabel;
    $link_sebelumnya        = $datpil->link_sebelumnya;
    $id_unitkerja           = $datpil->id_unitkerja;
    
    $query_unitkerja =\Config\Database::connect()->query("SELECT * from m_unitkerja where id_unitkerja='$id_unitkerja' LIMIT 1")->getRow();
    if($query_unitkerja) {
        $idunitkerja_terpilih = $query_unitkerja->id_unitkerja;
        $unitkerja_terpilih = $query_unitkerja->unitkerja_ind;
    } else {
        $idunitkerja_terpilih = '';
        $unitkerja_terpilih = '- Instansi Tidak Ditemukan -';
    }
    
} else {
    $act        = "act_add";
    $judul_en_page  = " TAMBAH MASTER TABEL";
    $idp        = "";
    $judul_ind              = "";
    $judul_en               = "";
    $link_tabel             = "";
    $link_sebelumnya        = "";
    $id_unitkerja           = "";
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-file-earmark-plus me-2"></i><?php echo ($mode == "edt") ? "Edit Master Tabel" : "Tambah Master Tabel"; ?>
                </h5>
            </div>
            
            <div class="card-body p-4">
                <form action="<?php echo base_URL(); ?>index.php/admin/master_tabel/<?php echo $act; ?>" method="post" accept-charset="utf-8">
                    <input type="hidden" name="idp" value="<?php echo $idp; ?>">
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Judul Bahasa Indonesia</label>
                                <textarea name="judul_ind" tabindex="1" required class="form-control" rows="3" placeholder="Ketik judul tabel bahasa Indonesia..."><?php echo $judul_ind; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Judul Bahasa Inggris</label>
                                <textarea name="judul_en" tabindex="2" required class="form-control" rows="3" placeholder="Type english table title..."><?php echo $judul_en; ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Link Tabel (Tahun Berjalan)</label>
                                <textarea name="link_tabel" tabindex="3" required class="form-control" rows="3" placeholder="Masukkan url/link tabel"><?php echo $link_tabel; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Link Tabel (Tahun Sebelumnya)</label>
                                <textarea name="link_sebelumnya" tabindex="4" required class="form-control" rows="3" placeholder="Masukkan url/link tabel tahun sebelumnya"><?php echo $link_sebelumnya; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block">Penanggung Jawab (Instansi)</label>
                                <select name="id_unitkerja" id="id_unitkerja" class="form-select" tabindex="5" required>
                                    <?php
                                    $db = \Config\Database::connect();
                                    $unitkerja = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();
                                    
                                    if ($mode == "edt" || $mode == "act_edt") {
                                        echo "<option selected value='".$idunitkerja_terpilih."'>".$unitkerja_terpilih."</option>";
                                        foreach($unitkerja as $p){
                                            if ($p['id_unitkerja'] != $idunitkerja_terpilih) {
                                                echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>";
                                            }
                                        }
                                    } else {
                                        echo "<option selected value=''>- Pilih Instansi Penanggung Jawab -</option>";
                                        foreach($unitkerja as $p){
                                            echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>";
                                        }
                                    }
                                    ?>
                                </select>  
                            </div>
                        </div>
                    </div>
                    
                    <hr class="text-secondary opacity-25 mt-4 mb-3">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel" class="btn btn-light border" tabindex="7">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary px-4" tabindex="6">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Data Tabel
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
</div>
