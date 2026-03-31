<?php
if (empty($data)) {
    echo "<tr><td colspan='6' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Data tidak ditemukan</td></tr>";
} else {
    $no = 1;
    foreach ($data as $b) {
?>
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="text-center">
                <span class="text-secondary text-sm"><?php echo $no; ?></span>
            </td>
            <td class="text-center">
                <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold"><?php echo $b->tahun; ?></span>
            </td>
            <td class="py-3">
                <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="max-width:350px; line-height: 1.5;"><?php echo $b->judul_ind; ?></h6>
                <p class="text-xxs text-secondary mb-0 font-italic text-wrap mt-1" style="max-width:350px; line-height: 1.2;"><?php echo $b->judul_en; ?></p>
            </td>
            <td>
                <div class="d-flex flex-column gap-1">
                    <?php if (strpos($b->link_tabel, 'view_portal_tabel') !== false): ?>
                        <span class="badge badge-sm bg-primary-soft text-primary border-primary-soft align-self-start fw-bold">
                            <i class="bi bi-cloud-check me-1"></i>PORTAL DATA
                        </span>
                    <?php else: ?>
                        <span class="badge badge-sm bg-success-soft text-success border-success-soft align-self-start fw-bold">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>SPREADSHEET
                        </span>
                    <?php endif; ?>
                    <code class="text-xxs text-secondary text-truncate d-block mt-1" style="max-width: 180px;"><?php echo str_replace('index.php/admin/', '', $b->link_tabel); ?></code>
                </div>
            </td>
            <td>
                <div class="d-flex flex-column">
                    <span class="text-xs font-weight-bold text-dark lh-sm"><?php echo $b->unitkerja_ind; ?></span>
                    <span class="text-xxs text-secondary opacity-7 mt-1 text-uppercase fw-bold"><?php echo $b->user_wali ?: 'NASIONAL'; ?></span>
                </div>
            </td>
            <td class="text-center">
                <div class="dropdown">
                    <button class="btn btn-link text-secondary mb-0 shadow-none border-0" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical fs-6"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 border-radius-lg p-2">
                        <li>
                            <a href="<?php echo base_URL() ?>index.php/admin/master_tabel/edt/<?php echo $b->id; ?>/1" class="dropdown-item border-radius-md py-2 text-sm">
                                <i class="bi bi-pencil me-2 text-primary"></i> Edit Detail
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider opacity-5 mt-1">
                        </li>
                        <li>
                            <a href="#" class="open_modal dropdown-item border-radius-md py-2 text-sm text-danger" id="<?php echo $b->id; ?>">
                                <i class="bi bi-trash me-2"></i> Hapus Tabel
                            </a>
                        </li>
                    </ul>
                </div>
            </td>
        </tr>
<?php
        $no++;
    }
}
?>
