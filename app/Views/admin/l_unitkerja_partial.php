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
