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
    <div class="card-header pb-3 pt-3 px-4 bg-white border-bottom border-light flex-shrink-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md border-radius-xl d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(21, 70, 121, 0.08); border: 1px solid rgba(21, 70, 121, 0.15); flex-shrink: 0;">
                        <i class="bi bi-cloud-arrow-up-fill fs-5" style="color: #154679;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0 font-weight-bolder text-dark">Preview Bulk Update Portal</h5>
                            <span class="badge px-2.5 py-1 text-xs font-weight-bold" style="background: rgba(21, 70, 121, 0.08); color: #154679; border: 1px solid rgba(21, 70, 121, 0.2); border-radius: 6px;">
                                DDA <?= $taActive ?>
                            </span>
                        </div>
                        <p class="text-xs text-secondary mb-0 mt-0.5">Pemetaan tautan ID Portal Data Jawa Tengah ke Master Tabel DDA</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1.5 text-xs font-weight-bold" title="Total baris data dalam berkas CSV">
                        <i class="bi bi-files me-1"></i>Total: <?= $totalRows ?>
                    </span>
                    <span class="badge bg-success-soft text-success border border-success-soft px-2.5 py-1.5 text-xs font-weight-bold" title="Tabel otomatis ditemukan di database">
                        <i class="bi bi-check-circle-fill me-1"></i>Sesuai: <?= $totalMatched ?>
                    </span>
                    <?php if ($totalUnmatched > 0): ?>
                    <span class="badge bg-danger-soft text-danger border border-danger-soft px-2.5 py-1.5 text-xs font-weight-bold" title="Tabel belum terpasang otomatis">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Perlu Dicek: <?= $totalUnmatched ?>
                    </span>
                    <?php endif; ?>
                    <span class="badge border px-2.5 py-1.5 text-xs font-weight-bold" id="badgeSelectedCount" style="background: rgba(21, 70, 121, 0.08); color: #154679; border-color: rgba(21, 70, 121, 0.2) !important;">
                        <i class="bi bi-check-square-fill me-1"></i>Terpilih: <b id="countSelected"><?= $totalMatched ?></b>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Notice Banner -->
    <div class="px-4 pt-3 pb-2 flex-shrink-0">
        <div class="alert border-0 shadow-none mb-0 py-2.5 px-3 rounded-3 d-flex align-items-center" style="background: #f1f6fc; border-left: 4px solid #154679 !important;">
            <i class="bi bi-info-circle-fill me-2 fs-5" style="color: #154679; flex-shrink: 0;"></i>
            <div class="text-xs text-dark lh-sm">
                Berikut adalah hasil pembacaan file CSV Anda untuk <strong>DDA Tahun <?= $taActive ?></strong>. Silakan periksa kesesuaian judul tabel dengan database DDA. Hanya baris yang <strong>dicentang</strong> yang akan diperbarui saat Anda menekan tombol konfirmasi.
            </div>
        </div>
    </div>

    <!-- Form & Table Container -->
    <form action="<?php echo base_url(); ?>index.php/admin/bulk_portal_save" method="post" id="formBulkPortal" class="flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
        <?php echo csrf_field(); ?>
        
        <div class="card-body px-4 py-2 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
            <div class="table-responsive table-scroll-container flex-grow-1 rounded-3 border border-light" style="height: auto; min-height: 0; overflow-y: auto; overflow-x: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-gray-100 position-sticky top-0 shadow-none" style="z-index: 2; border-bottom: 1px solid #e9ecef;">
                        <tr>
                            <th width="48" class="text-center py-3">
                                <div class="form-check d-flex justify-content-center m-0">
                                    <input class="form-check-input" type="checkbox" id="checkAll" checked title="Pilih / Batal Pilih Semua" style="cursor: pointer;">
                                </div>
                            </th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 py-3" style="min-width: 320px;">
                                Judul Tabel (DDA)
                            </th>
                            <th width="32%" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 py-3" style="min-width: 250px;">
                                ID Portal Data Jateng
                            </th>
                            <th width="140" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 py-3">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php 
                        if (empty($data_preview)) {
                            echo "<tr><td colspan='4' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Tidak ada data yang dapat ditampilkan</td></tr>";
                        } else {
                            foreach ($data_preview as $index => $row) { 
                                $isExists = !empty($row['exists']);
                        ?>
                            <tr class="transition-colors bulk-row <?= $isExists ? 'row-matched' : 'row-unmatched' ?>" data-index="<?= $index ?>" data-matched="<?= $isExists ? '1' : '0' ?>" style="<?= $isExists ? '' : 'background-color: #fff9f9; border-left: 3px solid #dc3545;' ?>">
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center m-0">
                                        <input class="form-check-input chkItem" type="checkbox" name="id_selected[]" value="<?= $index ?>" <?= $isExists ? 'checked' : '' ?> style="cursor: pointer;">
                                    </div>
                                </td>
                                <td class="py-2.5">
                                    <?php if ($isExists): ?>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 text-xxs font-weight-bold">ID: <?= esc($row['id_tabel']); ?></span>
                                            <?php if (!empty($row['no_tabel'])): ?>
                                                <span class="badge border px-2 py-0.5 text-xxs font-weight-bold" style="background: rgba(21, 70, 121, 0.08); color: #154679; border-color: rgba(21, 70, 121, 0.2) !important;">No. <?= esc($row['no_tabel']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-sm font-weight-bold text-dark lh-sm d-block"><?= esc($row['judul']); ?></span>
                                        <input type="hidden" name="id_tabel_final[<?= $index ?>]" value="<?= esc($row['id_tabel']); ?>">
                                    <?php else: ?>
                                        <div class="p-2 mb-2 rounded-2 border d-flex align-items-start gap-2" style="background-color: #fff2f2; border-color: #fecaca !important;">
                                            <i class="bi bi-exclamation-triangle-fill text-danger fs-6 mt-0.5 flex-shrink-0"></i>
                                            <div class="text-xs text-dark lh-sm">
                                                ID/No/Judul <span class="badge bg-danger text-white font-weight-bold px-1.5 py-0.5">"<?= htmlspecialchars($row['input_key']); ?>"</span> tidak ditemukan otomatis pada DDA Tahun <?= $taActive ?>.
                                                <div class="text-muted mt-1 fw-bold">Pilih tabel padanannya secara manual:</div>
                                            </div>
                                        </div>
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
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 text-muted px-2"><i class="bi bi-link-45deg"></i></span>
                                        <input type="text" name="portal_ids[<?= $index ?>]" value="<?= htmlspecialchars($row['ids']); ?>" class="form-control form-control-sm border-start-0 font-monospace text-xs" style="background-color: #fcfdfe;" placeholder="UUID ID Portal Data" title="ID Portal Data">
                                    </div>
                                </td>
                                <td class="text-center status-col">
                                    <?php if ($isExists): ?>
                                        <span class="badge bg-success-soft text-success border border-success-soft px-2.5 py-1.5 text-xs font-weight-bold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Ditemukan
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-soft text-danger border border-danger-soft px-2.5 py-1.5 text-xs font-weight-bold">
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
        <div class="card-footer bg-white border-top border-light px-4 py-3 flex-shrink-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-xs text-secondary me-1">Pilih Cepat:</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2.5 py-1 mb-0 border-radius-md text-xs" id="btnSelectAll">
                        <i class="bi bi-check-all me-1"></i>Pilih Semua
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2.5 py-1 mb-0 border-radius-md text-xs" id="btnDeselectAll">
                        <i class="bi bi-dash-square me-1"></i>Batal Semua
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm px-2.5 py-1 mb-0 border-radius-md text-xs" id="btnSelectMatchedOnly" style="color: #154679; border-color: rgba(21, 70, 121, 0.4);">
                        <i class="bi bi-funnel me-1"></i>Hanya yang Sesuai
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel" class="btn btn-light btn-sm px-3 border border-radius-lg mb-0 text-dark">
                        <i class="bi bi-arrow-left me-1"></i>Batal
                    </a>
                    <button type="submit" id="btnSubmitBulk" class="btn btn-primary btn-sm px-4 border-radius-lg mb-0 shadow-sm d-flex align-items-center" style="height: 38px; background: linear-gradient(135deg, #154679 0%, #1e5c9e 100%); border-color: #154679;" <?php echo empty($data_preview) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check2-all me-1.5 fs-6"></i> Konfirmasi & Update Sekarang
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
        box-shadow: 0 0 0 0.2rem rgba(21, 70, 121, 0.15) !important;
    }

    /* Select2 BS5 Theme Adjustments */
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 8px !important;
        min-height: 36px !important;
        font-size: 0.85rem !important;
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single {
        padding-top: 3px !important;
        padding-bottom: 3px !important;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #154679 !important;
        box-shadow: 0 0 0 0.2rem rgba(21, 70, 121, 0.15) !important;
    }
    .select2-dropdown {
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
        background-color: #154679 !important;
        color: #fff !important;
    }

    /* Input Focus */
    .form-control:focus {
        border-color: #154679 !important;
        box-shadow: 0 0 0 0.2rem rgba(21, 70, 121, 0.12) !important;
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
        placeholder: "- Cari berdasarkan Nomor atau Judul Tabel -",
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
            tr.find('.status-col').html('<span class="badge bg-primary-soft text-primary border border-primary-soft px-2.5 py-1.5 text-xs font-weight-bold"><i class="bi bi-check2-circle me-1"></i>Manual</span>');
        } else {
            tr.find('.chkItem').prop('checked', false);
            tr.css({
                'background-color': '#fff9f9',
                'border-left': '3px solid #dc3545'
            });
            tr.find('.status-col').html('<span class="badge bg-danger-soft text-danger border border-danger-soft px-2.5 py-1.5 text-xs font-weight-bold"><i class="bi bi-x-circle-fill me-1"></i>Tidak Sesuai</span>');
        }
        updateCounters();
    });

    // Initial counter evaluation
    updateCounters();
});
</script>