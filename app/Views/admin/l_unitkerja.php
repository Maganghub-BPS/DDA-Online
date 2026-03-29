<?php echo session()->getFlashdata("k"); ?>

<!-- Consolidated Control Card -->
<div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
    <div class="card-header pb-3 pt-4 px-4 bg-white border-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="mb-0 font-weight-bolder text-dark">Master OPD</h4>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2">
                    <form method="post" action="<?php echo base_URL(); ?>index.php/admin/master_opd/cari" onsubmit="return false;" class="flex-grow-1">
                        <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="inputSearchOPD" class="form-control bg-transparent border-0 ps-0 text-sm" name="q" placeholder="Cari OPD..." style="box-shadow: none;">
                        </div>
                    </form>
                    <a href="<?php echo base_URL(); ?>index.php/admin/master_opd/add" class="btn btn-primary btn-sm px-4 border-radius-lg mb-0 shadow-none d-flex align-items-center" style="height: 40px; white-space: nowrap;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah OPD
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body px-4 pt-0 pb-4">
        <div class="table-responsive rounded-3 border border-light overflow-hidden">
            <table class="table table-hover align-items-center mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        <th width="120" class="text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Kode OPD</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Nama Organisasi (ID)</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Organization Name (EN)</th>
                        <th width="150" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Walidata</th>
                        <th width="150" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Pengawas</th>
                        <th width="100" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableOPDBody" class="divide-y divide-gray-100 bg-white">
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='6' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Data tidak ditemukan</td></tr>";
                    } else {
                        foreach ($data as $b) {
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="text-center">
                            <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold px-3 py-2 border-radius-md border border-light"><?php echo $b->id_unitkerja; ?></span>
                        </td>
                        <td class="py-3">
                            <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="line-height: 1.5;"><?php echo $b->unitkerja_ind; ?></h6>
                        </td>
                        <td>
                            <p class="text-xs text-secondary mb-0 font-italic text-wrap" style="line-height: 1.4;"><?php echo $b->unitkerja_en; ?></p>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-primary-soft text-primary font-weight-bold px-3 py-2 border-radius-md border-primary-soft">
                                <i class="bi bi-person-badge me-1"></i><?php echo strtoupper($b->user_wali); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-info-soft text-info font-weight-bold px-3 py-2 border-radius-md border-info-soft">
                                <i class="bi bi-shield-check me-1"></i><?php echo strtoupper($b->user_spv); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?php echo base_URL(); ?>index.php/admin/master_opd/edt/<?php echo $b->id_unitkerja; ?>" class="btn btn-icon-only btn-outline-primary btn-sm border-radius-lg shadow-none" title="Edit OPD">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo base_URL()?>index.php/admin/master_opd/del/<?php echo $b->id_unitkerja;?>" class="btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" title="Hapus OPD" onclick="return confirm('Anda Yakin Akan Menghapus OPD <?php echo $b->unitkerja_ind;?> (<?php echo $b->id_unitkerja;?>) ?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>                  
                        </td>
                    </tr>
                    <?php 
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

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.9rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.4rem !important; }
    
    .bg-gray-50 { background-color: #fcfcfc !important; }
    .bg-gray-100 { background-color: #f8f9fa !important; }
    
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .bg-info-soft { background-color: rgba(17, 205, 239, 0.08) !important; color: #11cdef !important; }
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    .border-info-soft { border: 1px solid rgba(17, 205, 239, 0.15) !important; }

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
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .divide-y > * + * { border-top-width: 1px; }
    .divide-gray-100 > * + * { border-color: #f1f1f1; }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #FF6D1F;
    }
</style>

<script type="text/javascript">
$(document).ready(function () {
    // Real-time Client-side Search (Automatic)
    $("#inputSearchOPD").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tableOPDBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        
        // Show empty state
        var visibleRows = $("#tableOPDBody tr:visible").length;
        if (visibleRows === 0) {
            if ($("#emptySearchState").length === 0) {
                $("#tableOPDBody").append('<tr id="emptySearchState"><td colspan="6" class="text-center py-5 text-secondary font-weight-bold opacity-5"><i class="bi bi-search fs-2 d-block mb-2"></i>Tidak ada OPD yang cocok</td></tr>');
            }
        } else {
            $("#emptySearchState").remove();
        }
    });
});
</script>
