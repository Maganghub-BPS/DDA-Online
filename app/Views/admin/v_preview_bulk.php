<div class="row">
    <div class="col-lg-12">
        <div class="navbar navbar-inverse">
            <div class="container">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#">PREVIEW BULK UPDATE PORTAL</a>
                </div>
            </div>
        </div>

        <div class="well well-sm">
            Berikut adalah data dari file Excel Anda. Silakan periksa apakah judul tabel sudah sesuai dengan yang ada di sistem DDA. 
            Hanya baris dengan status <b>Ditemukan</b> yang akan diperbarui.
        </div>

        <form action="<?php echo base_url(); ?>index.php/admin/bulk_portal_save" method="post">
            <?php echo csrf_field(); ?>
            <table class="table table-bordered table-hover" style="background: #fff;">
                <thead>
                    <tr class="active">
                        <th width="5%" style="text-align: center;"><input type="checkbox" id="checkAll" checked></th>
                        <th width="40%">Judul Tabel (DDA)</th>
                        <th width="30%">ID Portal</th>
                        <th width="25%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (empty($data_preview)) {
                        echo "<tr><td colspan='4' align='center'>Data Kosong</td></tr>";
                    } else {
                        foreach ($data_preview as $index => $row) { 
                    ?>
                        <tr class="<?php echo $row['exists'] ? '' : 'danger'; ?>">
                            <td align="center">
                                <input type="checkbox" name="id_selected[]" value="<?php echo $index; ?>" <?php echo $row['exists'] ? 'checked' : ''; ?> class="chkItem">
                            </td>
                            <td>
                                <?php if ($row['exists']): ?>
                                    <b>[ID: <?php echo $row['id_tabel']; ?>]</b> <?php echo $row['judul']; ?>
                                    <input type="hidden" name="id_tabel_final[<?php echo $index; ?>]" value="<?php echo $row['id_tabel']; ?>">
                                <?php else: ?>
                                    <span style="color:red">ID/Judul <b>"<?php echo htmlspecialchars($row['input_key']); ?>"</b> tidak ditemukan.</span><br>
                                    <small>Pilih tabel secara manual:</small>
                                    <select name="id_tabel_final[<?php echo $index; ?>]" class="form-control input-sm select-table">
                                         <option value="">- Cari Tabel -</option>
                                         <?php foreach ($all_tables as $t): ?>
                                             <option value="<?php echo $t->id; ?>"><?php echo $t->judul_ind; ?> (ID: <?php echo $t->id; ?>)</option>
                                         <?php endforeach; ?>
                                     </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="text" name="portal_ids[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($row['ids']); ?>" class="form-control input-sm">
                            </td>
                            <td>
                                <?php if ($row['exists']): ?>
                                    <span class="label label-success">Ditemukan</span>
                                <?php else: ?>
                                    <span class="label label-danger">Tidak Sesuai (Pilih Manual)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        }
                    } 
                    ?>
                </tbody>
            </table>

            <div style="margin-top: 20px;" class="text-right">
                <a href="<?php echo base_url(); ?>index.php/admin/master_tabel" class="btn btn-default">Batal</a>
                <button type="submit" class="btn btn-primary" <?php echo empty($data_preview) ? 'disabled' : ''; ?>>
                    <i class="icon-ok icon-white"></i> Konfirmasi & Update Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 30px !important;
        padding: 0px 8px !important;
        font-size: 12px !important;
        border-radius: 3px !important;
        border: 1px solid #ccc !important;
    }
    .select2-selection__rendered {
        line-height: 28px !important;
    }
    .select2-selection__arrow {
        height: 28px !important;
    }
</style>

<script>
$(document).ready(function() {
    $("#checkAll").click(function() {
        $(".chkItem").prop('checked', $(this).prop('checked'));
    });

    // Initialize Select2 for table selection
    $('.select-table').select2({
        placeholder: "- Pilih Tabel -",
        allowClear: true,
        width: '100%'
    });
});
</script>
