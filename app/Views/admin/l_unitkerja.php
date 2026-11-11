<?php if (session()->getFlashdata("k")): ?>
<div class="flex-shrink-0">
    <?php echo session()->getFlashdata("k"); ?>
</div>
<?php endif; ?>

<!-- Consolidated Control Card -->
<div class="card border-0 shadow-sm border-radius-2xl mb-0" id="mainMasterOpdCard" style="height: auto; max-height: calc(100vh - 82px); min-height: 0; display: flex; flex-direction: column; overflow: hidden; flex-shrink: 0;">
    <div class="card-header pb-3 pt-3 px-4 bg-white border-0 flex-shrink-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="mb-0 font-weight-bolder text-dark">Master OPD</h4>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2">
                    <form method="post" action="<?php echo base_URL(); ?>index.php/admin/master_opd/cari" class="flex-grow-1" onsubmit="return false;">
                        <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="inputSearchOPD" class="form-control bg-transparent border-0 ps-0 text-sm" name="q" placeholder="Cari OPD..." style="box-shadow: none;" autocomplete="off">
                        </div>
                    </form>
                    <a href="<?php echo base_URL(); ?>index.php/admin/master_opd/add" class="btn btn-primary btn-sm px-4 border-radius-lg mb-0 shadow-none d-flex align-items-center" style="height: 40px; white-space: nowrap; background-color: #154679; border-color: #154679;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah OPD
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body px-4 pt-0 pb-3 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
        <div class="table-responsive table-scroll-container flex-grow-1 rounded-3 border border-light" style="height: auto; min-height: 0; overflow-y: auto; overflow-x: auto;">
            <table class="table table-hover align-middle mb-0 table-sticky-header">
                <thead class="bg-gray-100">
                    <tr>
                        <th width="120" class="text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Kode OPD</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Nama Organisasi (ID)</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Organization Name (EN)</th>
                        <th width="140" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Klasifikasi (RBAC)</th>
                        <th width="140" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Walidata</th>
                        <th width="100" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableOPDBody" class="divide-y divide-gray-100 bg-white">
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='6' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Data tidak ditemukan</td></tr>";
                    } else {
                        foreach ($data as $b) {
                            $isOpd = (($b->jenis_instansi ?? 'vertikal') === 'opd');
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors opd-row">
                        <td class="text-center">
                            <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold px-3 py-2 border-radius-md border border-light"><?php echo esc($b->id_unitkerja); ?></span>
                        </td>
                        <td class="py-3">
                            <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="line-height: 1.5;"><?php echo esc($b->unitkerja_ind); ?></h6>
                        </td>
                        <td>
                            <p class="text-xs text-secondary mb-0 font-italic text-wrap" style="line-height: 1.4;"><?php echo esc($b->unitkerja_en); ?></p>
                        </td>
                        <td class="text-center">
                            <?php if ($isOpd): ?>
                                <span class="badge badge-sm bg-success-subtle text-success border border-success-subtle font-weight-bold px-2 py-1 border-radius-md" title="Organisasi Perangkat Daerah (Read-Only)">
                                    <i class="bi bi-bank me-1"></i> OPD Pemprov
                                </span>
                            <?php else: ?>
                                <span class="badge badge-sm bg-info-subtle text-info border border-info-subtle font-weight-bold px-2 py-1 border-radius-md" title="Organisasi Vertikal (Upload Excel Mandiri)">
                                    <i class="bi bi-building me-1"></i> Vertikal
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-primary-soft text-primary font-weight-bold px-3 py-2 border-radius-md border-primary-soft">
                                <i class="bi bi-person-badge me-1"></i><?php echo esc(strtoupper((string)$b->user_wali)); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?php echo base_URL(); ?>index.php/admin/master_opd/edt/<?php echo esc($b->id_unitkerja, 'url'); ?>" class="btn btn-icon-only btn-outline-primary btn-sm border-radius-lg shadow-none" title="Edit OPD">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo base_URL()?>index.php/admin/master_opd/del/<?php echo esc($b->id_unitkerja, 'url');?>" class="btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" title="Hapus OPD" onclick="return confirm('Anda Yakin Akan Menghapus OPD <?php echo esc($b->unitkerja_ind, 'js');?> (<?php echo esc($b->id_unitkerja, 'js');?>) ?')">
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
    .bg-gray-100 { background-color: #f8fafc !important; }
    
    .bg-primary-soft { background-color: rgba(21, 70, 121, 0.08) !important; color: #154679 !important; }
    .bg-info-soft { background-color: rgba(17, 205, 239, 0.08) !important; color: #11cdef !important; }
    .border-primary-soft { border: 1px solid rgba(21, 70, 121, 0.15) !important; }
    .border-info-soft { border: 1px solid rgba(17, 205, 239, 0.15) !important; }

    .bg-success-subtle { background-color: #d1fae5 !important; }
    .text-success { color: #059669 !important; }
    .border-success-subtle { border-color: #a7f3d0 !important; }

    .bg-info-subtle { background-color: #e0f2fe !important; }
    .text-info { color: #0284c7 !important; }
    .border-info-subtle { border-color: #bae6fd !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.92rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .btn-outline-primary {
        color: #154679 !important;
        border-color: #154679 !important;
    }
    .btn-outline-primary:hover {
        background-color: #154679 !important;
        color: #fff !important;
    }

    .input-group-alternative {
        transition: all 0.2s ease;
    }
    .input-group-alternative:focus-within {
        background-color: #fff !important;
        box-shadow: 0 4px 12px rgba(21, 70, 121, 0.08) !important;
        border-color: #154679 !important;
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
        color: #154679;
    }

    /* Pastikan flexbox container luar tidak mengompres kartu Master OPD */
    .main-content {
        min-height: auto !important;
        padding-bottom: 12px !important;
    }
    .content-body {
        min-height: auto !important;
        flex: 1 0 auto !important;
    }
    #mainMasterOpdCard {
        height: auto !important;
        max-height: calc(100vh - 82px) !important;
        min-height: 0 !important;
        display: flex;
        flex-direction: column;
        flex-shrink: 0 !important;
    }

    /* Kontainer scroll internal Master OPD */
    .table-scroll-container {
        height: auto !important;
        max-height: calc(100vh - 82px - 65px) !important;
        min-height: 0 !important;
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: auto;
        position: relative;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    /* Custom Slim Scrollbar */
    .table-scroll-container::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Sticky Table Header */
    .table-scroll-container thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8fafc !important;
        box-shadow: inset 0 -2px 0 #e2e8f0;
        vertical-align: middle !important;
    }
</style>

<script type="text/javascript">
$(document).ready(function () {
    // Instant Client-Side Live Search across all loaded OPD rows
    $("#inputSearchOPD").on("input", function() {
        var value = $(this).val().toLowerCase().trim();
        var $rows = $("#tableOPDBody tr.opd-row");
        var visibleCount = 0;

        $rows.each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(value) > -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        if (visibleCount === 0 && value.length > 0) {
            if ($("#noDataRow").length === 0) {
                $("#tableOPDBody").append('<tr id="noDataRow"><td colspan="6" class="text-center py-5 text-secondary font-weight-bold opacity-7"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>Tidak ada OPD yang sesuai dengan kata kunci</td></tr>');
            } else {
                $("#noDataRow").show();
            }
        } else {
            $("#noDataRow").hide();
        }
    });
});
</script>
