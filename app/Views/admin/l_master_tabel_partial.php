<?php
if (empty($data)) {
    echo "<tr><td colspan='5' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Data tidak ditemukan</td></tr>";
} else {
    $no = 1;
    foreach ($data as $b) {
?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="text-center">
                                    <span class="text-secondary text-sm"><?php echo $no; ?></span>
                                </td>
                                <td class="py-3">
                                    <?php
                                    $rowJudulInd = trim(str_ireplace('[PERIODE]', '', $b->judul_ind ?? ''));
                                    $rowJudulEnRaw = $b->judul_en ?: $b->judul_ind;
                                    $rowJudulEn = trim(str_ireplace('[PERIODE]', '', $rowJudulEnRaw ?? ''));
                                    ?>
                                    <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="max-width:380px; line-height: 1.5;"><?php echo (!empty($b->no_tabel) ? esc($b->no_tabel) . ' ' : '') . esc($rowJudulInd); ?></h6>
                                    <p class="text-xxs text-secondary mb-0 font-italic text-wrap mt-1" style="max-width:380px; line-height: 1.2;"><?php echo (!empty($b->no_tabel) ? esc($b->no_tabel) . ' ' : '') . esc($rowJudulEn); ?></p>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <?php 
                                        $is_dyn = (($b->jenis_sumber ?? '') === 'dinamis') || (empty($b->jenis_sumber) && !empty($b->is_dynamic));
                                        $id_gen_url = (int)$b->id;
                                        $api_uuid = trim((string)($b->id_api ?? ''));
                                        $api_link = trim((string)($b->link_api ?? ($b->link_tabel ?? '')));
                                        $is_portal = (!empty($api_uuid) || (!empty($api_link) && (stripos($api_link, 'portal') !== false || stripos($api_link, 'satudata') !== false || stripos($api_link, 'view_portal_tabel') !== false)));
                                        $is_sheet = (!empty($api_link) && (stripos($api_link, 'docs.google.com') !== false || stripos($api_link, 'spreadsheet') !== false));
                                        if ($is_dyn): 
                                        ?>
                                            <a href="<?php echo base_url('admin/generator_tabel/' . $id_gen_url); ?>" class="badge badge-sm text-white border-0 align-self-start fw-bold shadow-xs text-decoration-none" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); font-size: 10px;" title="Klik untuk mengelola matriks dinamis">
                                                <i class="bi bi-boxes me-1"></i> TABEL DINAMIS
                                            </a>
                                        <?php elseif ($is_portal): ?>
                                            <span class="badge badge-sm bg-primary-soft text-primary border-primary-soft align-self-start fw-bold" style="font-size: 10px;">
                                                <i class="bi bi-cloud-check me-1"></i> API
                                            </span>
                                            <code class="text-xxs text-secondary text-truncate d-block mt-1" style="max-width: 190px;" title="<?php echo esc($api_uuid ?: $api_link); ?>"><?php echo esc($api_uuid ?: str_replace('index.php/admin/', '', $api_link)); ?></code>
                                        <?php elseif ($is_sheet): ?>
                                            <a href="<?php echo esc($api_link); ?>" target="_blank" class="badge badge-sm bg-success-soft text-success border-success-soft align-self-start fw-bold text-decoration-none" style="font-size: 10px;" title="Buka Spreadsheet">
                                                <i class="bi bi-file-spreadsheet me-1"></i> SPREADSHEET
                                            </a>
                                        <?php elseif (!empty($api_link)): ?>
                                            <a href="<?php echo esc($api_link); ?>" target="_blank" class="badge badge-sm bg-info-soft text-info border-info-soft align-self-start fw-bold text-decoration-none" style="font-size: 10px;" title="Buka Link">
                                                <i class="bi bi-link-45deg me-1"></i> LINK
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-sm bg-secondary-subtle text-secondary border border-secondary-subtle align-self-start fw-semibold" style="font-size: 10px;">
                                                <i class="bi bi-gear me-1"></i> BELUM DIKONFIGURASI
                                            </span>
                                            <span class="text-xxs text-muted fst-italic mt-1">Belum diatur (Dinamis / API)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-xs font-weight-bold text-dark lh-sm"><?php echo esc($b->unitkerja_ind); ?></span>
                                        <span class="text-xxs text-secondary opacity-7 mt-1 text-uppercase fw-bold"><?php echo esc($b->user_wali ?: 'NASIONAL'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-link text-secondary mb-0 shadow-none border-0" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}'>
                                            <i class="bi bi-three-dots-vertical fs-6"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 border-radius-lg p-2">
                                            <li>
                                                <a href="<?php echo base_URL() ?>index.php/admin/master_tabel/edt/<?php echo $b->id; ?>/1" class="dropdown-item border-radius-md py-2 text-sm">
                                                    <i class="bi bi-pencil me-2 text-secondary"></i> Edit Master
                                                </a>
                                            </li>
                                            <?php if ($is_dyn): ?>
                                            <li>
                                                <a href="<?php echo base_url('admin/generator_tabel/' . $id_gen_url); ?>" class="dropdown-item border-radius-md py-2 text-sm text-primary fw-semibold">
                                                    <i class="bi bi-boxes me-2"></i> Generator Matriks
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?php echo base_url('admin/view_tabel_dinamis/' . $id_gen_url); ?>" class="dropdown-item border-radius-md py-2 text-sm text-info fw-semibold">
                                                    <i class="bi bi-table me-2"></i> Lihat Tabel
                                                </a>
                                            </li>
                                            <?php elseif (!empty($api_uuid) || !empty($api_link)): ?>
                                            <li>
                                                <?php 
                                                $target_portal_url = !empty($api_link) 
                                                    ? (stripos($api_link, 'http') === 0 ? $api_link : base_url($api_link)) 
                                                    : base_url('admin/view_portal_tabel?id=' . urlencode($api_uuid));
                                                if (strpos($target_portal_url, 'view_portal_tabel') !== false && strpos($target_portal_url, 'table_id=') === false) {
                                                    $target_portal_url .= (strpos($target_portal_url, '?') !== false ? '&' : '?') . 'table_id=' . $b->id;
                                                }
                                                ?>
                                                <a href="<?php echo $target_portal_url; ?>" class="dropdown-item border-radius-md py-2 text-sm text-info fw-semibold">
                                                    <i class="bi bi-table me-2"></i> Lihat Tabel API
                                                </a>
                                            </li>
                                            <?php endif; ?>
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
