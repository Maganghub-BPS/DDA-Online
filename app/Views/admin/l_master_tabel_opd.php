<?php echo session()->getFlashdata("k"); ?>

<div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
    <div class="card-header pb-3 pt-3 px-4 bg-white border-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel/" class="btn btn-sm btn-outline-secondary border-radius-lg px-3 mb-0 shadow-none d-flex align-items-center">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-patch-question me-2 text-primary-orange"></i>Review Tabel Usulan
                    </h5>
                </div>
            </div>
            <div class="col-lg-5">
                <form method="post" action="<?php echo base_URL(); ?>index.php/admin/master_tabel/cari">
                    <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="inputSearchUsulan" class="form-control bg-transparent border-0 ps-0" name="q" placeholder="Ketik untuk mencari usulan..." style="box-shadow: none;">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body px-4 pt-0 pb-4">
        <div class="alert bg-gray-100 border-0 text-sm mb-4 px-3 py-2 border-radius-lg">
            <i class="bi bi-info-circle text-primary me-2"></i>
            Halaman ini menampilkan tabel baru yang diusulkan oleh OPD. Klik <strong>ACC</strong> untuk mendaftarkan ke Master Tabel.
        </div>

        <div class="table-responsive rounded-3 border border-light overflow-hidden">
            <table class="table table-hover align-items-center mb-0">
                <thead class="bg-gray-100 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">
                    <tr>
                        <th width="45" class="text-center py-3">No</th>
                        <th class="py-3">Judul Indonesia (Draft)</th>
                        <th class="py-3">Judul Inggris</th>
                        <th width="120" class="text-center py-3">Dokumen</th>
                        <th width="180" class="py-3">Instansi Pengusul</th>
                        <th width="120" class="text-center py-3">Pengirim</th>
                        <th width="100" class="text-center py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableUsulanBody" class="divide-y divide-gray-100 bg-white">
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='7' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Belum ada usulan baru</td></tr>";
                    } else {
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="text-center">
                            <span class="text-secondary text-sm"><?php echo $no; ?></span>
                        </td>
                        <td class="py-3">
                            <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="max-width:320px; line-height: 1.5;"><?php echo $b->judul_ind; ?></h6>
                        </td>
                        <td>
                            <p class="text-xxs text-secondary mb-0 font-italic text-wrap" style="max-width:320px;"><?php echo $b->judul_en; ?></p>
                        </td>
                        <td class="text-center">
                            <a href="<?php echo base_URL()?>upload/tabel_usulan/<?php echo $b->file_tabel; ?>" target='_blank' class="badge bg-primary-soft text-primary border-primary-soft align-self-start py-2 px-3 fw-bold border-radius-lg shadow-none text-decoration-none d-inline-block">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Unduh
                            </a>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold text-dark lh-sm"><?php echo $b->unitkerja_ind; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold text-uppercase"><?php echo $b->addby; ?></span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?php echo base_URL()?>index.php/admin/master_tabel_opd/edt/<?php echo $b->id; ?>" class="btn btn-icon-only btn-success btn-sm border-radius-lg shadow-none" title="Terima Usulan (ACC)">
                                    <i class="bi bi-check-lg"></i>
                                </a>
                                <a href="#" class="open_modal btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" id="<?php echo $b->id; ?>" title="Tolak / Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>  
                        </td>
                    </tr>
                    <?php 
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if(!empty($pagi)): ?>
        <div class="mt-4">
            <?php echo $pagi; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Delete (BS5) -->
<div id="ModalDelete" class="modal fade" tabindex="-1" aria-hidden="true"></div>

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.92rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.4rem !important; }
    
    .bg-gray-100 { background-color: #f8f9fa !important; }
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    .text-primary-orange { color: #FF6D1F !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.92rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .input-group-alternative {
        transition: all 0.2s ease;
    }
    .input-group-alternative:focus-within {
        background-color: #fff !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        border-color: #FF6D1F !important;
    }
    
    .btn-icon-only {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .divide-y > * + * { border-top-width: 1px; }
    .divide-gray-100 > * + * { border-color: #f1f1f1; }
</style>

<script type="text/javascript">
$(document).ready(function () {
    // Real-time Client-side Search
    $("#inputSearchUsulan").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tableUsulanBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        
        var visibleRows = $("#tableUsulanBody tr:visible").length;
        if (visibleRows === 0) {
            if ($("#emptySearchState").length === 0) {
                $("#tableUsulanBody").append('<tr id="emptySearchState"><td colspan="7" class="text-center py-5 text-secondary font-weight-bold opacity-5"><i class="bi bi-search fs-2 d-block mb-2"></i>Tidak ada usulan yang cocok</td></tr>');
            }
        } else {
            $("#emptySearchState").remove();
        }
    });

    $(".open_modal").click(function(e) {
        e.preventDefault();
        var m = $(this).attr("id");
        $.ajax({
            url: "<?php echo base_url(); ?>index.php/admin/master_tabel_opd/del/",
            type: "GET",
            data: {delete_id: m},
            success: function (ajaxData) {
                $("#ModalDelete").html(ajaxData);
                var bsModal = new bootstrap.Modal(document.getElementById('ModalDelete'));
                bsModal.show();
            }
        });
    });
});
</script>
