<?php echo session()->getFlashdata("k"); ?>

<!-- Consolidated Control Card -->
<div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
    <div class="card-header pb-3 pt-4 px-4 bg-white border-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="mb-0 font-weight-bolder text-dark">Manajemen Admin</h4>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2">
                    <form method="post" action="<?php echo base_URL(); ?>index.php/admin/manage_admin/cari" onsubmit="return false;" class="flex-grow-1">
                        <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="inputSearchAdmin" class="form-control bg-transparent border-0 ps-0 text-sm" name="q" placeholder="Cari admin..." style="box-shadow: none;">
                        </div>
                    </form>
                    <a href="<?php echo base_URL(); ?>index.php/admin/manage_admin/add" class="btn btn-primary btn-sm px-4 border-radius-lg mb-0 shadow-none d-flex align-items-center" style="height: 40px; white-space: nowrap;">
                        <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
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
                        <th width="45" class="text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">No</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Informasi Akun</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Nama & Identitas</th>
                        <th width="150" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Level Akses</th>
                        <th width="100" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableAdminBody" class="divide-y divide-gray-100 bg-white">
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='5' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Data admin tidak ditemukan</td></tr>";
                    } else {
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="text-center">
                            <span class="text-secondary text-sm"><?php echo $no; ?></span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-gray-100 border-radius-md me-3 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person text-secondary"></i>
                                </div>
                                <div class="d-flex flex-column">
                                    <h6 class="mb-0 text-sm font-weight-bold text-dark"><?php echo $b->username; ?></h6>
                                    <p class="text-xxs text-secondary mb-0"><?php echo $b->email ?: '-'; ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-sm font-weight-bold text-dark lh-sm"><?php echo $b->nama; ?></span>
                                <span class="text-xxs text-secondary mt-1 font-italic">NIP: <?php echo $b->nip ?: '-'; ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php 
                                if($b->level == 'Super Admin') {
                                    $badge = 'bg-danger-soft text-danger border-danger-soft';
                                } else if($b->level == 'Admin') {
                                    $badge = 'bg-primary-soft text-primary border-primary-soft';
                                } else if($b->level == 'lo') {
                                    $badge = 'bg-success-soft text-success border-success-soft';
                                } else if($b->level == 'spv') {
                                    $badge = 'bg-info-soft text-info border-info-soft';
                                } else {
                                    $badge = 'bg-gray-100 text-secondary border-light';
                                }
                            ?>
                            <span class="badge badge-sm <?php echo $badge; ?> font-weight-bold px-3 py-2 border-radius-md border">
                                <?php echo strtoupper($b->level); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?php echo base_URL(); ?>index.php/admin/manage_admin/edt/<?php echo $b->id; ?>" class="btn btn-icon-only btn-outline-primary btn-sm border-radius-lg shadow-none" title="Edit Admin">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo base_URL()?>index.php/admin/manage_admin/del/<?php echo $b->id?>" class="btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" title="Hapus Admin" onclick="return confirm('Anda Yakin ingin menghapus data admin ini?')">
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
    .bg-danger-soft { background-color: rgba(234, 6, 6, 0.08) !important; color: #ea0606 !important; }
    .bg-success-soft { background-color: rgba(45, 206, 137, 0.08) !important; color: #2dce89 !important; }
    .bg-info-soft { background-color: rgba(17, 205, 239, 0.08) !important; color: #11cdef !important; }
    
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    .border-danger-soft { border: 1px solid rgba(234, 6, 6, 0.15) !important; }
    .border-success-soft { border: 1px solid rgba(45, 206, 137, 0.15) !important; }
    .border-info-soft { border: 1px solid rgba(17, 205, 239, 0.15) !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.95rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .avatar-sm {
        width: 32px;
        height: 32px;
    }
    
    .btn-icon-only {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .input-group-alternative {
        transition: all 0.2s ease;
        background-color: #fff;
    }
    .input-group-alternative:focus-within {
        border-color: #FF6D1F !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
    }
    
    .divide-y > * + * { border-top-width: 1px; }
    .divide-gray-100 > * + * { border-color: #f1f1f1; }
</style>

<script type="text/javascript">
$(document).ready(function () {
    // Real-time Client-side Search (Automatic)
    $("#inputSearchAdmin").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tableAdminBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        
        // Show empty state
        var visibleRows = $("#tableAdminBody tr:visible").length;
        if (visibleRows === 0) {
            if ($("#emptySearchState").length === 0) {
                $("#tableAdminBody").append('<tr id="emptySearchState"><td colspan="5" class="text-center py-5 text-secondary font-weight-bold opacity-5"><i class="bi bi-search fs-2 d-block mb-2"></i>Tidak ada admin yang cocok</td></tr>');
            }
        } else {
            $("#emptySearchState").remove();
        }
    });
});
</script>
