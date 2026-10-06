<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-cloud-arrow-up me-2"></i>PREVIEW BULK UPDATE PORTAL
                </h5>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <i class="bi bi-info-circle-fill text-info" style="font-size: 1.5rem;"></i>
        </div>
        <div>
            Berikut adalah data dari file CSV Anda untuk <strong>DDA Tahun <?= esc($ta ?? session()->get('admin_ta') ?? date('Y')) ?></strong>. Silakan periksa apakah judul tabel sudah sesuai dengan yang ada di sistem DDA. 
            Hanya baris dengan status <strong>Ditemukan</strong> atau yang Anda pasangkan secara manual yang akan diperbarui.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <form action="<?php echo base_url(); ?>index.php/admin/bulk_portal_save" method="post">
            <?php echo csrf_field(); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">
                                <input class="form-check-input" type="checkbox" id="checkAll" checked>
                            </th>
                            <th width="45%">Judul Tabel (DDA)</th>
                            <th width="30%">ID Portal</th>
                            <th width="20%" class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (empty($data_preview)) {
                            echo "<tr><td colspan='4' class='text-center py-4 text-muted'><i class='bi bi-inbox me-2'></i>Data Kosong</td></tr>";
                        } else {
                            foreach ($data_preview as $index => $row) { 
                        ?>
                            <tr class="<?php echo $row['exists'] ? '' : 'table-danger'; ?>">
                                <td class="text-center">
                                    <input class="form-check-input chkItem" type="checkbox" name="id_selected[]" value="<?php echo $index; ?>" <?php echo $row['exists'] ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <?php if ($row['exists']): ?>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge bg-secondary-subtle text-secondary border">ID: <?php echo esc($row['id_tabel']); ?></span>
                                            <?php if (!empty($row['no_tabel'])): ?>
                                                <span class="badge bg-primary-subtle text-primary border">No. <?php echo esc($row['no_tabel']); ?></span>
                                            <?php endif; ?>
                                            <span class="fw-medium text-dark"><?php echo esc($row['judul']); ?></span>
                                        </div>
                                        <input type="hidden" name="id_tabel_final[<?php echo $index; ?>]" value="<?php echo esc($row['id_tabel']); ?>">
                                    <?php else: ?>
                                        <div class="text-danger mb-2">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> ID/No/Judul <strong>"<?php echo htmlspecialchars($row['input_key']); ?>"</strong> tidak ditemukan pada DDA Tahun <?= esc($ta ?? session()->get('admin_ta') ?? date('Y')); ?>.
                                        </div>
                                        <div class="small fw-bold text-muted mb-1">Pilih tabel secara manual:</div>
                                        <select name="id_tabel_final[<?php echo $index; ?>]" class="form-select form-select-sm select-table" style="width: 100%;">
                                             <option></option>
                                             <?php foreach ($all_tables as $t): ?>
                                                 <option value="<?php echo esc($t->id); ?>">
                                                     <?php echo (!empty($t->no_tabel) ? esc($t->no_tabel) . ' - ' : '') . esc(format_judul_tabel($t->judul_ind, $t->periode_id ?? $t->periode ?? '')); ?> (ID: <?php echo esc($t->id); ?>)
                                                 </option>
                                             <?php endforeach; ?>
                                         </select>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="text" name="portal_ids[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($row['ids']); ?>" class="form-control form-control-sm">
                                </td>
                                <td class="text-center status-col">
                                    <?php if ($row['exists']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Ditemukan</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Sesuai</span>
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

            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel" class="btn btn-light border"><i class="bi bi-x-circle me-1"></i>Batal</a>
                    <button type="submit" class="btn btn-primary" <?php echo empty($data_preview) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check2-all me-1"></i> Konfirmasi & Update Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Custom adjustments for Select2 inside BS5 tables */
    .select2-container .select2-selection--single {
        height: 31px !important;
    }
    .select2-container--bootstrap-5 .select2-selection {
        font-size: 0.875rem;
    }
</style>

<script>
$(document).ready(function() {
    $("#checkAll").click(function() {
        $(".chkItem").prop('checked', $(this).prop('checked'));
    });

    // Initialize Select2 with Bootstrap 5 theme
    $('.select-table').select2({
        theme: 'bootstrap-5',
        placeholder: "- Cari dan Pilih Tabel -",
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
            tr.removeClass('table-danger').addClass('table-success');
            tr.find('.status-col').html('<span class="badge bg-info text-dark"><i class="bi bi-check2-circle me-1"></i>Manual</span>');
        } else {
            tr.find('.chkItem').prop('checked', false);
            tr.removeClass('table-success').addClass('table-danger');
            tr.find('.status-col').html('<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Sesuai</span>');
        }
    });
});
</script>
