<?php
$taActive = esc($ta ?? session()->get('admin_ta') ?? date('Y'));
$totalRows = !empty($data_preview) ? count($data_preview) : 0;
$totalMatched = 0;
$totalUnmatched = 0;
if (!empty($data_preview)) {
    foreach ($data_preview as $item) {
        if (!empty($item['exists'])) {
            $totalMatched++;
        } else {
            $totalUnmatched++;
        }
    }
}
?>

<!-- Consolidated Preview Bulk Card -->
<div class="card border-0 shadow-sm border-radius-2xl mb-4" id="mainPreviewBulkCard" style="height: auto; max-height: calc(100vh - 82px); min-height: 0; display: flex; flex-direction: column; overflow: hidden; flex-shrink: 0;">
    <!-- Card Header -->
    <div class="card-header py-3 px-4 bg-white border-bottom border-light flex-shrink-0">
        <div class="row align-items-center g-2">
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="avatar avatar-sm border-radius-lg d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(21, 70, 121, 0.08); border: 1px solid rgba(21, 70, 121, 0.15); flex-shrink: 0;">
                        <i class="bi bi-cloud-arrow-up-fill fs-6" style="color: #154679;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 15px;">Preview Bulk Update Portal</h6>
                            <span class="badge px-2 py-0.5 text-xxs font-weight-bold" style="background: rgba(21, 70, 121, 0.08); color: #154679; border: 1px solid rgba(21, 70, 121, 0.2); border-radius: 4px;">
                                DDA <?= $taActive ?>
                            </span>
                        </div>
                        <p class="text-xxs text-secondary mb-0">Pemetaan tautan ID Portal Data Jawa Tengah ke Master Tabel DDA</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-1.5 flex-wrap">
                    <div class="d-inline-flex align-items-center gap-1 bg-light p-1 rounded-3 border" style="font-size: 11px;">
                        <span class="badge bg-white text-secondary border shadow-none px-2 py-1 text-xxs font-weight-bold" title="Total baris dari berkas CSV">
                            <i class="bi bi-files me-1"></i>Total: <?= $totalRows ?>
                        </span>
                        <span class="badge bg-white text-success border shadow-none px-2 py-1 text-xxs font-weight-bold" title="Tabel otomatis ditemukan">
                            <i class="bi bi-check-circle-fill me-1"></i>Sesuai: <?= $totalMatched ?>
                        </span>
                        <?php if ($totalUnmatched > 0): ?>
                        <span class="badge bg-white text-danger border shadow-none px-2 py-1 text-xxs font-weight-bold" title="Tabel perlu dipasangkan manual">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Perlu Dicek: <?= $totalUnmatched ?>
                        </span>
                        <?php endif; ?>
                        <span class="badge text-xxs font-weight-bold shadow-none px-2 py-1" id="badgeSelectedCount" style="background: #154679; color: #ffffff;" title="Jumlah data yang dicentang untuk disimpan">
                            <i class="bi bi-check-square-fill me-1"></i>Terpilih: <b id="countSelected"><?= $totalMatched ?></b>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Strip -->
    <div class="px-4 py-2 border-bottom flex-shrink-0 d-flex align-items-center justify-content-between" style="background: #f8fafc;">
        <div class="d-flex align-items-center gap-2 text-xxs text-secondary">
            <i class="bi bi-info-circle-fill text-primary" style="color: #154679 !important; font-size: 13px;"></i>
            <span>Periksa kesesuaian judul tabel. Hanya baris dengan centang <strong>Terpilih</strong> yang akan disimpan saat Anda menekan tombol konfirmasi.</span>
        </div>
    </div>

    <!-- Form & Table Container -->
    <form action="<?php echo base_url(); ?>index.php/admin/bulk_portal_save" method="post" id="formBulkPortal" class="flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
        <?php echo csrf_field(); ?>
        
        <div class="card-body px-4 py-2 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
            <div class="table-responsive table-scroll-container flex-grow-1 rounded-2 border" style="height: auto; min-height: 0; overflow-y: auto; overflow-x: auto; border-color: #e2e8f0 !important;">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: #f8fafc; position: sticky; top: 0; z-index: 2; border-bottom: 1px solid #e2e8f0;">
                        <tr>
                            <th width="42" class="text-center py-2.5">
                                <div class="form-check d-flex justify-content-center m-0">
                                    <input class="form-check-input" type="checkbox" id="checkAll" checked title="Pilih / Batal Pilih Semua" style="cursor: pointer; width: 15px; height: 15px;">
                                </div>
                            </th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 py-2.5" style="min-width: 320px;">
                                Judul Tabel (DDA)
                            </th>
                            <th width="32%" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 py-2.5" style="min-width: 260px;">
                                ID Portal Data Jateng
                            </th>
                            <th width="120" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 py-2.5">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php 
                        if (empty($data_preview)) {
                            echo "<tr><td colspan='4' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-3 d-block mb-2'></i>Tidak ada data yang dapat ditampilkan</td></tr>";
                        } else {
                            foreach ($data_preview as $index => $row) { 
                                $isExists = !empty($row['exists']);
                        ?>
                            <tr class="transition-colors bulk-row <?= $isExists ? 'row-matched' : 'row-unmatched' ?>" data-index="<?= $index ?>" data-matched="<?= $isExists ? '1' : '0' ?>" style="<?= $isExists ? '' : 'background-color: #fffafa; border-left: 3px solid #dc3545;' ?>">
                                <td class="text-center py-2">
                                    <div class="form-check d-flex justify-content-center m-0">
                                        <input class="form-check-input chkItem" type="checkbox" name="id_selected[]" value="<?= $index ?>" <?= $isExists ? 'checked' : '' ?> style="cursor: pointer; width: 15px; height: 15px;">
                                    </div>
                                </td>
                                <td class="py-2">
                                    <?php if ($isExists): ?>
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap mb-1">
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5 text-xxs font-weight-bold">ID: <?= esc($row['id_tabel']); ?></span>
                                            <?php if (!empty($row['no_tabel'])): ?>
                                                <span class="badge px-1.5 py-0.5 text-xxs font-weight-bold" style="background: rgba(21, 70, 121, 0.08); color: #154679; border: 1px solid rgba(21, 70, 121, 0.2); border-radius: 4px;">No. <?= esc($row['no_tabel']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs font-weight-bold text-dark lh-sm"><?= esc($row['judul']); ?></div>
                                        <input type="hidden" name="id_tabel_final[<?= $index ?>]" value="<?= esc($row['id_tabel']); ?>">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap mb-1">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1.5 py-0.5 text-xxs font-weight-bold">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Tidak Ditemukan
                                            </span>
                                            <span class="text-xs fw-bold text-danger">"<?= htmlspecialchars($row['input_key']); ?>"</span>
                                        </div>
                                        <div class="text-xxs text-secondary mb-1">Pilih tabel padanan DDA secara manual:</div>
                                        <select name="id_tabel_final[<?= $index ?>]" class="form-select form-select-sm select-table" style="width: 100%;">
                                            <option></option>
                                            <?php foreach ($all_tables as $t): ?>
                                                <option value="<?= esc($t->id); ?>">
                                                    <?= (!empty($t->no_tabel) ? esc($t->no_tabel) . ' - ' : '') . esc(format_judul_tabel($t->judul_ind, $t->periode_id ?? $t->periode ?? '')); ?> (ID: <?= esc($t->id); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-muted border-end-0 px-2 py-0"><i class="bi bi-link-45deg" style="font-size: 13px;"></i></span>
                                        <input type="text" name="portal_ids[<?= $index ?>]" value="<?= htmlspecialchars($row['ids']); ?>" class="form-control form-control-sm border-start-0 font-monospace text-xs py-1" style="background-color: #fcfdfe; height: 32px;" placeholder="UUID Portal Data" title="ID Portal Data">
                                    </div>
                                </td>
                                <td class="text-center status-col py-2">
                                    <?php if ($isExists): ?>
                                        <span class="badge bg-success-soft text-success border border-success-soft px-2 py-1 text-xxs font-weight-bold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Ditemukan
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-soft text-danger border border-danger-soft px-2 py-1 text-xxs font-weight-bold">
                                            <i class="bi bi-x-circle-fill me-1"></i>Tidak Sesuai
                                        </span>
                                    <?php endif; ?>
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

        <!-- Card Footer -->
        <div class="card-footer bg-white border-top border-light px-4 py-2.5 flex-shrink-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="text-xxs text-secondary text-uppercase font-weight-bold me-1">Seleksi:</span>
                    <button type="button" class="btn btn-xs btn-white border px-2 py-1 mb-0 border-radius-md text-xxs text-secondary" id="btnSelectAll">
                        <i class="bi bi-check-all me-1"></i>Semua
                    </button>
                    <button type="button" class="btn btn-xs btn-white border px-2 py-1 mb-0 border-radius-md text-xxs text-secondary" id="btnDeselectAll">
                        <i class="bi bi-dash me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-xs btn-white border px-2 py-1 mb-0 border-radius-md text-xxs text-primary" id="btnSelectMatchedOnly" style="color: #154679 !important;">
                        <i class="bi bi-funnel me-1"></i>Hanya Sesuai
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel" class="btn btn-white btn-sm px-3 border border-radius-lg mb-0 text-xs text-secondary" style="height: 34px; line-height: 20px;">
                        <i class="bi bi-arrow-left me-1"></i>Batal
                    </a>
                    <button type="submit" id="btnSubmitBulk" class="btn btn-primary btn-sm px-3 border-radius-lg mb-0 shadow-sm d-flex align-items-center text-xs font-weight-bold" style="height: 34px; background: #154679; border-color: #154679;" <?php echo empty($data_preview) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check2-all me-1.5"></i> Konfirmasi & Update Sekarang
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Soft Badges */
    .bg-primary-soft {
        background-color: rgba(21, 70, 121, 0.08) !important;
    }
    .text-primary {
        color: #154679 !important;
    }
    .border-primary-soft {
        border-color: rgba(21, 70, 121, 0.2) !important;
    }
    .bg-success-soft {
        background-color: #ecfdf5 !important;
    }
    .text-success {
        color: #047857 !important;
    }
    .border-success-soft {
        border-color: #a7f3d0 !important;
    }
    .bg-danger-soft {
        background-color: #fef2f2 !important;
    }
    .text-danger {
        color: #b91c1c !important;
    }
    .border-danger-soft {
        border-color: #fecaca !important;
    }

    /* Custom Checkbox */
    .form-check-input:checked {
        background-color: #154679 !important;
        border-color: #154679 !important;
    }
    .form-check-input:focus {
        border-color: #154679 !important;
        box-shadow: 0 0 0 0.15rem rgba(21, 70, 121, 0.15) !important;
    }

    /* Select2 BS5 Theme Adjustments - Compact 32px */
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 6px !important;
        min-height: 32px !important;
        height: 32px !important;
        font-size: 0.8rem !important;
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single {
        padding-top: 1px !important;
        padding-bottom: 1px !important;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #154679 !important;
        box-shadow: 0 0 0 0.15rem rgba(21, 70, 121, 0.15) !important;
    }
    .select2-dropdown {
        border-radius: 6px !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
        font-size: 0.8rem !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
        background-color: #154679 !important;
        color: #fff !important;
    }

    /* Input Focus */
    .form-control:focus {
        border-color: #154679 !important;
        box-shadow: 0 0 0 0.15rem rgba(21, 70, 121, 0.12) !important;
    }
</style>

<script>
$(document).ready(function() {
    function updateCounters() {
        var total = $(".chkItem").length;
        var checked = $(".chkItem:checked").length;
        
        $("#countSelected").text(checked);
        
        // Update checkAll checkbox state
        if (checked === 0) {
            $("#checkAll").prop('checked', false).prop('indeterminate', false);
        } else if (checked === total) {
            $("#checkAll").prop('checked', true).prop('indeterminate', false);
        } else {
            $("#checkAll").prop('checked', false).prop('indeterminate', true);
        }

        // Enable or disable submit button
        if (checked === 0) {
            $("#btnSubmitBulk").prop('disabled', true).addClass('opacity-50');
        } else {
            $("#btnSubmitBulk").prop('disabled', false).removeClass('opacity-50');
        }
    }

    // Toggle checkAll
    $("#checkAll").on('change', function() {
        var isChecked = $(this).prop('checked');
        $(".chkItem").prop('checked', isChecked);
        updateCounters();
    });

    // Individual checkbox change
    $(document).on('change', '.chkItem', function() {
        updateCounters();
    });

    // Quick selection buttons
    $("#btnSelectAll").on('click', function() {
        $(".chkItem").prop('checked', true);
        updateCounters();
    });

    $("#btnDeselectAll").on('click', function() {
        $(".chkItem").prop('checked', false);
        updateCounters();
    });

    $("#btnSelectMatchedOnly").on('click', function() {
        $(".bulk-row").each(function() {
            var row = $(this);
            var isMatched = row.attr('data-matched') === '1';
            var hasManual = row.find('.select-table').val();
            if (isMatched || hasManual) {
                row.find('.chkItem').prop('checked', true);
            } else {
                row.find('.chkItem').prop('checked', false);
            }
        });
        updateCounters();
    });

    // Initialize Select2 with Bootstrap 5 theme
    $('.select-table').select2({
        theme: 'bootstrap-5',
        placeholder: "- Cari nomor atau judul tabel DDA -",
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Tabel tidak ditemukan";
            }
        }
    });

    // Ketika tabel manual dipilih, otomatis centang checkbox dan perbarui tampilan baris
    $('.select-table').on('change', function() {
        var val = $(this).val();
        var tr = $(this).closest('tr');
        if (val) {
            tr.find('.chkItem').prop('checked', true);
            tr.css({
                'background-color': '#f0fdf4',
                'border-left': '3px solid #198754'
            });
            tr.find('.status-col').html('<span class="badge bg-primary-soft text-primary border border-primary-soft px-2 py-1 text-xxs font-weight-bold"><i class="bi bi-check2-circle me-1"></i>Manual</span>');
        } else {
            tr.find('.chkItem').prop('checked', false);
            tr.css({
                'background-color': '#fffafa',
                'border-left': '3px solid #dc3545'
            });
            tr.find('.status-col').html('<span class="badge bg-danger-soft text-danger border border-danger-soft px-2 py-1 text-xxs font-weight-bold"><i class="bi bi-x-circle-fill me-1"></i>Tidak Sesuai</span>');
        }
        updateCounters();
    });

    // Initial counter evaluation
    updateCounters();
});
</script>
