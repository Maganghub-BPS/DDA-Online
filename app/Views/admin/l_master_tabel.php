<!-- =========================================================================
     HALAMAN MASTER TABEL DDA (Daftar Publikasi Tabel Sektoral)
     Fitur Utama:
     1. Toolbar Pengelolaan: Tambah tabel, Usulan OPD, Bulk Portal, & Menu Pengelolaan Tahun
     2. Filter Data: Bidang/Tim, Instansi (OPD), dan Tahun Terbit
     3. Live Search Realtime via AJAX (tanpa refresh halaman)
     4. Modal Duplikasi Tabel (Clone), Rapikan Urutan (Resequence), dan Kosongkan Tahun (Reset)
========================================================================= -->
<?php
$current_lvl = strtolower(trim((string)(session()->get('admin_level') ?? '')));
$is_super_admin = in_array($current_lvl, ['super admin', 'superadmin']);

$totalAktif = $total_tabel_aktif ?? 585;

// Selected Jenis
$selJenisArr = is_array($selected_jenis ?? null) ? $selected_jenis : ((!empty($selected_jenis) && $selected_jenis !== 'all') ? [$selected_jenis] : []);
$isJenisAll = empty($selJenisArr) || in_array('all', (array)($selected_jenis ?? []));

if ($isJenisAll || count($selJenisArr) === 0) {
    $labelJenisTrigger = "Semua Jenis";
} elseif (count($selJenisArr) === 1) {
    $valJ = $selJenisArr[0];
    $labelJenisTrigger = ($valJ === 'opd') ? 'OPD' : 'Vertikal';
} else {
    $labelJenisTrigger = count($selJenisArr) . " Jenis";
}

// Selected OPD
$selOpdArr = is_array($selected_opd ?? null) 
    ? array_values(array_filter(array_map('trim', $selected_opd), fn($v) => !empty($v) && $v !== 'all')) 
    : ((!empty($selected_opd) && $selected_opd !== 'all') ? [trim((string)$selected_opd)] : []);
$isOpdAll = empty($selOpdArr) || in_array('all', (array)($selected_opd ?? []));

if ($isOpdAll || count($selOpdArr) === 0) {
    $labelOpdTrigger = "Semua Instansi";
} elseif (count($selOpdArr) === 1) {
    $firstOpdId = $selOpdArr[0];
    $foundOpd = null;
    if (isset($list_opd)) {
        foreach ($list_opd as $o) {
            if (trim((string)$o->id_unitkerja) === $firstOpdId) {
                $foundOpd = $o;
                break;
            }
        }
    }
    $labelOpdTrigger = $foundOpd ? $foundOpd->unitkerja_ind : '1 Instansi';
} else {
    $labelOpdTrigger = count($selOpdArr) . " Instansi";
}

// Selected Bidang
$selBidangArr = is_array($selected_bidang ?? null) ? array_map('strtolower', array_map('trim', $selected_bidang)) : ((!empty($selected_bidang) && $selected_bidang !== 'all') ? [strtolower(trim($selected_bidang))] : []);
$isBidangAll = empty($selBidangArr) || in_array('all', (array)($selected_bidang ?? []));

if ($isBidangAll || count($selBidangArr) === 0) {
    $labelBidangTrigger = "Semua Tim";
} elseif (count($selBidangArr) === 1) {
    $tKey = $selBidangArr[0];
    $labelBidangTrigger = strtoupper($tKey);
} else {
    $labelBidangTrigger = count($selBidangArr) . " Tim";
}
?>

<!-- Kartu Kontrol & Aksi Utama (Toolbar Atas Ringkas 1 Baris) -->
<div class="card border-0 shadow-sm border-radius-2xl mb-0" id="mainMasterTabelCard" style="height: auto; max-height: calc(100vh - 82px); min-height: 0; display: flex; flex-direction: column; overflow: visible; flex-shrink: 0;">
    <div class="card-header py-2 px-3 bg-white border-0 flex-shrink-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <!-- Sisi Kiri: Tombol Aksi + Form Filter (Menyatu 1 Baris) -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Tombol Tambah Tabel Baru Secara Manual -->
                <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel/add" class="btn btn-primary btn-sm px-3 mb-0 shadow-sm fw-bold d-inline-flex align-items-center gap-1" style="background: linear-gradient(45deg, #154679, #1e5c9e); border: none; height: 35px; border-radius: 8px;">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Data</span>
                </a>

                <!-- Tombol Menuju Daftar Usulan Tabel yang Diajukan oleh OPD -->
                <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/" class="btn btn-sm px-3 mb-0 shadow-none d-inline-flex align-items-center gap-1 fw-bold" style="background: #eef4fb; color: #154679; border: 1px solid #c9dcf0; height: 35px; border-radius: 8px;">
                    <i class="bi bi-patch-question"></i>
                    <span>Tabel Usulan</span>
                </a>

                <!-- Tombol Sinkronisasi Massal via CSV ke Portal Data Jateng -->
                <button type="button" class="btn btn-sm btn-outline-secondary px-3 mb-0 shadow-none d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#ModalBulkPortal" style="height: 35px; border-radius: 8px;">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Bulk Portal</span>
                </button>

                <!-- Pemisah Vertikal Halus -->
                <div class="vr mx-1 my-auto d-none d-lg-block" style="height: 22px; opacity: 0.2;"></div>

                <!-- Form Filter Terpadu (Inline) -->
                <form id="formFilterMaster" class="d-flex flex-wrap align-items-center gap-2 mb-0" method="GET" action="<?php echo base_url(); ?>index.php/admin/master_tabel">
                    <!-- Filter Jenis Instansi -->
                    <div class="dropdown custom-filter-dropdown" data-filter-type="jenis">
                        <button type="button" class="btn btn-outline-secondary btn-sm border shadow-none px-2 py-1 d-flex align-items-center gap-1 bg-white text-dark dropdown-toggle filter-trigger-btn" style="height: 35px; border-radius: 8px; border-color: #cbd5e1 !important;" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="<?= esc($labelJenisTrigger); ?>">
                            <i class="bi bi-diagram-3 text-secondary" style="font-size: 13px;"></i>
                            <span class="trigger-label fw-semibold text-xs text-truncate" style="max-width: 120px;"><?= esc($labelJenisTrigger); ?></span>
                        </button>
                        <div class="dropdown-menu shadow-lg border border-light-subtle rounded-4 p-3 mt-1 custom-filter-menu" style="min-width: 320px; z-index: 1060;">
                            <!-- Search Box di Atas -->
                            <div class="mb-2">
                                <input type="text" class="form-control dropdown-search-box" placeholder="Cari jenis instansi...">
                            </div>
                            <!-- Checkbox List -->
                            <div class="dropdown-checkbox-list">
                                <!-- Opsi Semua -->
                                <div class="dropdown-item-check item-all py-1 px-2 rounded-2">
                                    <label class="form-check d-flex align-items-center mb-0 w-100 cursor-pointer">
                                        <input type="checkbox" class="form-check-input me-2 mt-0 check-all" <?= $isJenisAll ? 'checked' : ''; ?>>
                                        <span class="text-sm item-label text-dark fw-bold">Semua</span>
                                        <span class="text-xs text-secondary ms-auto item-count ps-2">(<?= number_format($totalAktif, 0, ',', '.'); ?>)</span>
                                    </label>
                                </div>
                                <!-- Opsi OPD -->
                                <div class="dropdown-item-check py-1 px-2 rounded-2">
                                    <label class="form-check d-flex align-items-center mb-0 w-100 cursor-pointer">
                                        <input type="checkbox" name="filter_jenis[]" value="opd" class="form-check-input me-2 mt-0 check-item" <?= (in_array('opd', $selJenisArr, true) || $isJenisAll) ? 'checked' : ''; ?>>
                                        <span class="text-sm item-label text-dark">Organisasi Perangkat Daerah</span>
                                        <span class="text-xs text-secondary ms-auto item-count ps-2">(<?= number_format($count_per_jenis['opd'] ?? 0, 0, ',', '.'); ?>)</span>
                                    </label>
                                </div>
                                <!-- Opsi Vertikal -->
                                <div class="dropdown-item-check py-1 px-2 rounded-2">
                                    <label class="form-check d-flex align-items-center mb-0 w-100 cursor-pointer">
                                        <input type="checkbox" name="filter_jenis[]" value="vertikal" class="form-check-input me-2 mt-0 check-item" <?= (in_array('vertikal', $selJenisArr, true) || $isJenisAll) ? 'checked' : ''; ?>>
                                        <span class="text-sm item-label text-dark">Organisasi Vertikal</span>
                                        <span class="text-xs text-secondary ms-auto item-count ps-2">(<?= number_format($count_per_jenis['vertikal'] ?? 0, 0, ',', '.'); ?>)</span>
                                    </label>
                                </div>
                            </div>
                            <!-- Footer Action Buttons -->
                            <div class="dropdown-footer d-flex justify-content-end align-items-center gap-2 pt-2 border-top border-light-subtle mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-0 px-3 py-1 btn-dropdown-cancel fw-bold" style="border-radius: 8px; font-size: 12px;">Batal</button>
                                <button type="button" class="btn btn-sm btn-primary mb-0 px-3 py-1 btn-dropdown-apply fw-bold" style="background: #154679; border: none; border-radius: 8px; font-size: 12px;">Terapkan</button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Instansi -->
                    <div class="dropdown custom-filter-dropdown" data-filter-type="opd">
                        <button type="button" class="btn btn-outline-secondary btn-sm border shadow-none px-2 py-1 d-flex align-items-center gap-1 bg-white text-dark dropdown-toggle filter-trigger-btn" style="height: 35px; border-radius: 8px; border-color: #cbd5e1 !important;" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="<?= esc($labelOpdTrigger); ?>">
                            <i class="bi bi-building text-secondary" style="font-size: 13px;"></i>
                            <span class="trigger-label fw-semibold text-xs text-truncate" style="max-width: 160px;"><?= esc($labelOpdTrigger); ?></span>
                        </button>
                        <div class="dropdown-menu shadow-lg border border-light-subtle rounded-4 p-3 mt-1 custom-filter-menu" style="min-width: 420px; max-width: 540px; z-index: 1060;">
                            <!-- Search Box di Atas -->
                            <div class="mb-2">
                                <input type="text" class="form-control dropdown-search-box" placeholder="Cari instansi...">
                            </div>
                            <!-- Checkbox List -->
                            <div class="dropdown-checkbox-list">
                                <!-- Opsi Semua -->
                                <div class="dropdown-item-check item-all py-2 px-2 rounded-2">
                                    <label class="form-check d-flex align-items-center mb-0 w-100 cursor-pointer">
                                        <input type="checkbox" class="form-check-input me-2 mt-0 check-all flex-shrink-0" <?= $isOpdAll ? 'checked' : ''; ?>>
                                        <span class="text-sm item-label text-dark fw-bold">Semua</span>
                                        <span class="text-xs text-secondary ms-auto item-count ps-2 flex-shrink-0">(<?= number_format($totalAktif, 0, ',', '.'); ?>)</span>
                                    </label>
                                </div>
                                <?php
                                if (!empty($list_opd)):
                                    foreach ($list_opd as $opd):
                                        $opdId = trim((string)$opd->id_unitkerja);
                                        $opdJenis = strtolower(trim((string)($opd->jenis_instansi ?? '')));
                                        $opdTim = strtolower(trim((string)($opd->user_wali ?? '')));
                                        $cnt = (int)($count_per_opd[$opdId] ?? 0);
                                        $isChk = $isOpdAll || in_array($opdId, $selOpdArr, true);
                                ?>
                                <div class="dropdown-item-check py-2 px-2 rounded-2" data-jenis="<?= esc($opdJenis, 'attr'); ?>" data-tim="<?= esc($opdTim, 'attr'); ?>">
                                    <label class="form-check d-flex align-items-start mb-0 w-100 cursor-pointer">
                                        <input type="checkbox" name="filter_opd[]" value="<?= esc($opdId, 'attr'); ?>" class="form-check-input me-2 mt-1 check-item flex-shrink-0" <?= $isChk ? 'checked' : ''; ?>>
                                        <span class="text-sm item-label text-dark me-2" style="white-space: normal; line-height: 1.35; flex-grow: 1;" title="<?= esc($opd->unitkerja_ind); ?>"><?= esc($opd->unitkerja_ind); ?></span>
                                        <span class="text-xs text-secondary ms-auto item-count ps-2 flex-shrink-0 mt-1">(<?= number_format($cnt, 0, ',', '.'); ?>)</span>
                                    </label>
                                </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                            <!-- Footer Action Buttons -->
                            <div class="dropdown-footer d-flex justify-content-end align-items-center gap-2 pt-2 border-top border-light-subtle mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-0 px-3 py-1 btn-dropdown-cancel fw-bold" style="border-radius: 8px; font-size: 12px;">Batal</button>
                                <button type="button" class="btn btn-sm btn-primary mb-0 px-3 py-1 btn-dropdown-apply fw-bold" style="background: #154679; border: none; border-radius: 8px; font-size: 12px;">Terapkan</button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Bidang / Tim -->
                    <div class="dropdown custom-filter-dropdown" data-filter-type="bidang">
                        <button type="button" class="btn btn-outline-secondary btn-sm border shadow-none px-2 py-1 d-flex align-items-center gap-1 bg-white text-dark dropdown-toggle filter-trigger-btn" style="height: 35px; border-radius: 8px; border-color: #cbd5e1 !important;" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="<?= esc($labelBidangTrigger); ?>">
                            <i class="bi bi-diagram-2 text-secondary" style="font-size: 13px;"></i>
                            <span class="trigger-label fw-semibold text-xs text-truncate" style="max-width: 110px;"><?= esc($labelBidangTrigger); ?></span>
                        </button>
                        <div class="dropdown-menu shadow-lg border border-light-subtle rounded-4 p-3 mt-1 custom-filter-menu" style="min-width: 320px; z-index: 1060;">
                            <!-- Search Box di Atas -->
                            <div class="mb-2">
                                <input type="text" class="form-control dropdown-search-box" placeholder="Cari bidang / tim...">
                            </div>
                            <!-- Checkbox List -->
                            <div class="dropdown-checkbox-list">
                                <!-- Opsi Semua -->
                                <div class="dropdown-item-check item-all py-1 px-2 rounded-2">
                                    <label class="form-check d-flex align-items-center mb-0 w-100 cursor-pointer">
                                        <input type="checkbox" class="form-check-input me-2 mt-0 check-all" <?= $isBidangAll ? 'checked' : ''; ?>>
                                        <span class="text-sm item-label text-dark fw-bold">Semua</span>
                                        <span class="text-xs text-secondary ms-auto item-count ps-2">(<?= number_format($totalAktif, 0, ',', '.'); ?>)</span>
                                    </label>
                                </div>
                                <?php
                                if (!empty($list_tim)):
                                    foreach ($list_tim as $tim):
                                        $timKey = strtolower(trim((string)$tim->user_wali));
                                        $timName = strtoupper((string)$tim->user_wali);
                                        $cnt = (int)($count_per_tim[$timKey] ?? 0);
                                        $isChk = $isBidangAll || in_array($timKey, $selBidangArr, true);
                                ?>
                                <div class="dropdown-item-check py-1 px-2 rounded-2">
                                    <label class="form-check d-flex align-items-center mb-0 w-100 cursor-pointer">
                                        <input type="checkbox" name="filter_bidang[]" value="<?= esc($timKey, 'attr'); ?>" class="form-check-input me-2 mt-0 check-item" <?= $isChk ? 'checked' : ''; ?>>
                                        <span class="text-sm item-label text-dark"><?= esc($timName); ?></span>
                                        <span class="text-xs text-secondary ms-auto item-count ps-2">(<?= number_format($cnt, 0, ',', '.'); ?>)</span>
                                    </label>
                                </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                            <!-- Footer Action Buttons -->
                            <div class="dropdown-footer d-flex justify-content-end align-items-center gap-2 pt-2 border-top border-light-subtle mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-0 px-3 py-1 btn-dropdown-cancel fw-bold" style="border-radius: 8px; font-size: 12px;">Batal</button>
                                <button type="button" class="btn btn-sm btn-primary mb-0 px-3 py-1 btn-dropdown-apply fw-bold" style="background: #154679; border: none; border-radius: 8px; font-size: 12px;">Terapkan</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Filter -->
                    <button type="submit" class="btn btn-primary btn-sm px-3 mb-0 shadow-none fw-bold d-inline-flex align-items-center gap-1" style="background: #154679; border: none; height: 35px; border-radius: 8px;">
                        <i class="bi bi-filter"></i> <span>Filter</span>
                    </button>

                    <!-- Tombol Reset (Jika Filter Aktif) -->
                    <?php if ((isset($selected_jenis) && $selected_jenis != 'all') || (isset($selected_opd) && $selected_opd != 'all') || (isset($selected_bidang) && $selected_bidang != 'all') || !empty($cari)): ?>
                        <a href="<?php echo base_url(); ?>index.php/admin/master_tabel?action=reset" class="btn btn-link text-secondary text-xs mb-0 px-1 fw-bold text-decoration-none" title="Reset filter">
                            <i class="bi bi-x-circle me-1 text-danger"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Sisi Kanan: Kotak Pencarian Realtime -->
            <div class="ms-auto" style="min-width: 200px; max-width: 280px; flex-shrink: 0;">
                <form method="post" action="<?php echo base_URL(); ?>index.php/admin/master_tabel/cari" class="mb-0">
                    <div class="input-group input-group-sm input-group-alternative border shadow-none px-2 py-0" style="background: #f8f9fa; height: 35px; border-radius: 8px; border-color: #cbd5e1 !important; align-items: center;">
                        <span class="input-group-text bg-transparent border-0 py-0 ps-0 pe-2"><i class="bi bi-search text-muted" style="font-size: 13px;"></i></span>
                        <input type="text" id="inputSearchTabel" class="form-control bg-transparent border-0 ps-0 text-xs py-1" name="q" placeholder="Ketik untuk mencari tabel..." style="box-shadow: none;" value="<?php echo esc($cari ?? ''); ?>">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Area Filter dan Tabel Data -->
    <div class="card-body px-3 pt-0 pb-2 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: visible;">
        <div class="table-responsive table-scroll-container flex-grow-1 rounded-3 border border-light" style="height: auto; min-height: 0; overflow-y: auto; overflow-x: auto;">
            <table class="table table-hover align-items-center mb-0 table-sticky-header">
                <thead class="bg-gray-100">
                    <tr>
                        <th width="45" class="text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">No</th>
                        <th class="valign-middle py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Judul Tabel</th>
                        <th width="200" class="valign-middle py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Sumber / Link</th>
                        <th width="200" class="valign-middle py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Instansi / Unit Kerja</th>
                        <th width="85" class="valign-middle text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableMasterBody" class="divide-y divide-gray-100 bg-white">
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
                                        if ($is_dyn): 
                                        ?>
                                            <a href="<?php echo base_url('admin/generator_tabel/' . $id_gen_url); ?>" class="badge badge-sm text-white border-0 align-self-start fw-bold shadow-xs text-decoration-none" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); font-size: 10px;" title="Klik untuk mengelola matriks dinamis">
                                                <i class="bi bi-boxes me-1"></i> TABEL DINAMIS
                                            </a>
                                        <?php elseif (!empty($api_uuid) || !empty($api_link)): ?>
                                            <span class="badge badge-sm bg-primary-soft text-primary border-primary-soft align-self-start fw-bold" style="font-size: 10px;">
                                                <i class="bi bi-cloud-check me-1"></i> API
                                            </span>
                                            <code class="text-xxs text-secondary text-truncate d-block mt-1" style="max-width: 190px;" title="<?php echo esc($api_uuid ?: $api_link); ?>"><?php echo esc($api_uuid ?: str_replace('index.php/admin/', '', $api_link)); ?></code>
                                        <?php else: ?>
                                            <span class="badge badge-sm bg-light text-secondary border align-self-start fw-semibold" style="font-size: 10px;">
                                                <i class="bi bi-dash-circle me-1"></i> API (BELUM DIISI)
                                            </span>
                                            <span class="text-xxs text-muted fst-italic mt-1">Belum ada UUID API</span>
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
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BULK UPDATE PORTAL DATA (SINKRONISASI MASSAL VIA CSV)              -->
<!-- Fitur ini memungkinkan admin memperbarui tautan (link_tabel) banyak tabel  -->
<!-- sekaligus ke Portal Data Open Data Jawa Tengah menggunakan file CSV mapping.-->
<!-- Alur: Upload CSV -> Pratinjau perubahan link -> Simpan perubahan massal.   -->
<!-- Endpoint: admin/preview_bulk_portal                                       -->
<!-- ========================================================================= -->
<!-- Modal Bulk Update Portal (BS5) -->
<div id="ModalBulkPortal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
            <div class="modal-header bg-gray-100 border-0 pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-cloud-upload me-2 text-primary"></i>Bulk Update Portal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Form pengunggahan file CSV mapping -->
            <form action="<?php echo base_url(); ?>index.php/admin/preview_bulk_portal" method="post" enctype="multipart/form-data">
                <input type="hidden" name="tahun" value="<?= esc($ta ?? session()->get('admin_ta') ?? date('Y')) ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="alert bg-gray-100 border-0 text-dark text-sm mb-4">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                        Gunakan file CSV mapping untuk memperbarui tautan tabel secara massal ke Portal Data Jawa Tengah pada DDA <b>Tahun <?= esc($ta ?? session()->get('admin_ta') ?? date('Y')) ?></b>.
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">1. Download Panduan</label>
                        <a href="<?php echo base_url(); ?>index.php/admin/download_xlsx_template" class="btn btn-outline-primary btn-sm w-100 border-radius-lg py-2">
                            <i class="bi bi-download me-2"></i> Download Template CSV
                        </a>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">2. Unggah File Mapping</label>
                        <div class="input-group">
                            <input type="file" name="file_mapping" class="form-control" accept=".csv" required style="border-radius: 0.6rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle border-top py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4 border" style="border-radius: 8px;" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold" style="border-radius: 8px; background: linear-gradient(135deg, #154679 0%, #1e5c9e 100%); border: none;">
                        <i class="bi bi-cloud-upload"></i>
                        <span>Mulai Sinkronisasi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete (BS5) -->
<div id="ModalDelete" class="modal fade" tabindex="-1" aria-hidden="true"></div>

<style>
    /* Pastikan flexbox container luar tidak mengompres kartu Master Tabel */
    .main-content {
        min-height: auto !important;
        padding-bottom: 12px !important;
    }
    .content-body {
        min-height: auto !important;
        flex: 1 0 auto !important;
    }
    #mainMasterTabelCard {
        height: auto !important;
        max-height: calc(100vh - 82px) !important;
        min-height: 0 !important;
        display: flex;
        flex-direction: column;
        flex-shrink: 0 !important;
    }

    /* Kontainer scroll internal Master Tabel */
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
        border-radius: 4px;
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

    .ls-1 {
        letter-spacing: 0.8px;
    }

    .text-xxs {
        font-size: 0.75rem !important;
    }

    .text-xs {
        font-size: 0.82rem !important;
    }

    .text-sm {
        font-size: 0.92rem !important;
    }

    .font-weight-bolder {
        font-weight: 800 !important;
    }

    .border-radius-2xl {
        border-radius: 1.25rem !important;
    }

    .border-radius-xl {
        border-radius: 1rem !important;
    }

    .border-radius-lg {
        border-radius: 0.6rem !important;
    }

    .border-radius-md {
        border-radius: 0.5rem !important;
    }

    .bg-gray-50 {
        background-color: #fcfcfc !important;
    }

    .bg-gray-100 {
        background-color: #f8f9fa !important;
    }

    .bg-primary-soft {
        background-color: rgba(21, 70, 121, 0.08) !important;
        color: #154679 !important;
    }

    .bg-success-soft {
        background-color: rgba(40, 167, 69, 0.08) !important;
        color: #28a745 !important;
    }

    .border-primary-soft {
        border: 1px solid rgba(21, 70, 121, 0.2) !important;
    }

    .border-success-soft {
        border: 1px solid rgba(40, 167, 69, 0.15) !important;
    }

    .table td,
    .table th {
        border-color: #f1f1f1 !important;
        vertical-align: middle !important;
        font-size: 0.92rem !important;
    }

    .table thead th {
        border-bottom: 0 !important;
        font-size: 0.75rem !important;
    }

    .input-group-alternative {
        transition: all 0.2s ease;
    }

    .input-group-alternative:focus-within {
        background-color: #fff !important;
        box-shadow: 0 4px 12px rgba(21, 70, 121, 0.08) !important;
        border-color: #154679 !important;
    }

    .divide-y>*+* {
        border-top-width: 1px;
    }

    .divide-gray-100>*+* {
        border-color: #f1f1f1;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #154679;
    }

    /* ========================================================================= */
    /* CUSTOM FILTER DROPDOWN: SEARCH & CHECKBOX LIST (MIRROR REFERENCE UI)      */
    /* ========================================================================= */
    .custom-filter-dropdown .filter-trigger-btn {
        background-color: #ffffff !important;
        border: 1px solid #d2d6da !important;
        color: #344767 !important;
        font-size: 0.85rem !important;
        border-radius: 0.5rem !important;
        padding: 0.45rem 0.75rem !important;
        min-height: 42px;
        height: auto;
        transition: all 0.2s ease;
    }

    .custom-filter-dropdown .filter-trigger-btn .trigger-label {
        white-space: normal !important;
        line-height: 1.25 !important;
        font-size: 0.825rem !important;
        text-align: left;
        flex-grow: 1;
        word-break: normal;
    }

    .custom-filter-dropdown .filter-trigger-btn:hover,
    .custom-filter-dropdown .filter-trigger-btn:focus {
        border-color: #154679 !important;
        box-shadow: 0 0 0 2px rgba(21, 70, 121, 0.12) !important;
    }

    .custom-filter-dropdown .filter-trigger-btn::after {
        margin-left: auto;
        color: #64748b;
        vertical-align: middle;
        flex-shrink: 0;
    }

    .custom-filter-menu {
        border-radius: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
    }

    .dropdown-search-box {
        border-radius: 8px !important;
        border: 1.5px solid #cbd5e1 !important;
        font-size: 0.85rem !important;
        padding: 0.45rem 0.75rem !important;
        outline: none !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .dropdown-search-box:focus {
        border-color: #154679 !important;
        box-shadow: 0 0 0 2px rgba(21, 70, 121, 0.15) !important;
    }

    .dropdown-checkbox-list {
        max-height: 200px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 3px;
    }

    /* Scrollbar modern minimalis */
    .dropdown-checkbox-list::-webkit-scrollbar {
        width: 6px;
    }

    .dropdown-checkbox-list::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 4px;
    }

    .dropdown-checkbox-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .dropdown-checkbox-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .dropdown-item-check {
        transition: background-color 0.15s ease;
    }

    .dropdown-item-check:hover {
        background-color: #f1f5f9;
    }

    .dropdown-item-check .form-check-input {
        width: 1.1rem;
        height: 1.1rem;
        cursor: pointer;
        border-color: #94a3b8;
        border-radius: 4px;
    }

    .dropdown-item-check .form-check-input:checked {
        background-color: #154679;
        border-color: #154679;
    }

    .dropdown-item-check .item-label {
        font-size: 0.835rem;
        user-select: none;
    }

    .dropdown-item-check .item-count {
        font-size: 0.775rem;
        color: #64748b;
        font-variant-numeric: tabular-nums;
    }

    .dropdown-item-check.cascaded-out {
        display: none !important;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        // =====================================================================
        // FITUR LIVE SEARCH DENGAN DEBOUNCE (Pencarian Cepat & Ringan)
        // 1. Filter Lokal: Menyaring baris yang sedang tampil secara instan di layar.
        // 2. AJAX Server Search: Mengambil data pencarian tabel lengkap dari database 
        //    (melalui master_tabel/cari) setelah jeda ketik 450ms (debounce) agar tidak membebani server.
        // =====================================================================
        var searchTimer;
        $("#inputSearchTabel").on("keyup", function() {
            var value = $(this).val();
            clearTimeout(searchTimer);
            
            // Filter Lokal (instant feedback pada halaman yang sedang dibuka)
            var lowerValue = value.toLowerCase();
            $("#tableMasterBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(lowerValue) > -1)
            });

            // AJAX Global Search ke database jika kata kunci >= 2 karakter
            searchTimer = setTimeout(function() {
                if (value.length >= 2 || value.length == 0) {
                    $.ajax({
                        url: "<?php echo base_URL(); ?>index.php/admin/master_tabel/cari",
                        type: "POST",
                        data: { q: value },
                        success: function(response) {
                            $("#tableMasterBody").html(response);
                            // Sembunyikan pagination saat dalam mode pencarian aktif
                            if (value.length > 0) {
                                $(".pagination-container").hide();
                            } else {
                                window.location.href = "<?php echo base_url('index.php/admin/master_tabel'); ?>";
                            }
                        }
                    });
                }
            }, 450);
        });

        // =====================================================================
        // MODAL KONFIRMASI HAPUS TABEL (AJAX Modal Loader)
        // Memuat konten view konfirmasi penghapusan aman dari controller master_tabel/del/
        // =====================================================================
        $(document).on("click", ".open_modal", function(e) {
            e.preventDefault();
            var m = $(this).attr("id");

            // Tutup dropdown Bootstrap yang aktif agar tidak macet / membeku di layar
            var dropdownBtn = $(this).closest('.dropdown').find('[data-bs-toggle="dropdown"]')[0];
            if (dropdownBtn) {
                var dd = bootstrap.Dropdown.getInstance(dropdownBtn);
                if (dd) {
                    dd.hide();
                }
            }

            // Panggil form dialog konfirmasi hapus via AJAX
            $.ajax({
                url: "<?php echo base_url(); ?>index.php/admin/master_tabel/del/",
                type: "GET",
                data: {
                    delete_id: m
                },
                success: function(ajaxData) {
                    var modalEl = document.getElementById('ModalDelete');
                    $(modalEl).html(ajaxData);
                    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                }
            });
        });

        // Bersihkan isi modal dan backdrop sisa agar halaman tidak freeze/blur saat modal ditutup
        $('#ModalDelete').on('hidden.bs.modal', function () {
            $(this).empty();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
        });

        // =====================================================================
        // CUSTOM FILTER DROPDOWN: SEARCH, CHECKBOX LIST, BATAL & TERAPKAN
        // Mengimplementasikan perilaku filter sesuai referensi gambar:
        // - Live search instan pada daftar item dropdown
        // - Checkbox 'Semua' yang sinkron dengan seluruh opsi aktif
        // - Tombol 'Batal' mengembalikan pilihan ke kondisi awal sebelum dropdown dibuka
        // - Tombol 'Terapkan' memproses filter multi-select & submit form secara rapi
        // - Cascading Filter: Filter Jenis & Bidang secara cerdas menyaring opsi Instansi
        // =====================================================================

        // Fungsi Sinkronisasi Cascading Filter (Jenis & Tim -> Instansi)
        function syncCascadingFilters() {
            var $jenisDropdown = $('.custom-filter-dropdown[data-filter-type="jenis"]');
            var $bidangDropdown = $('.custom-filter-dropdown[data-filter-type="bidang"]');
            var $opdDropdown = $('.custom-filter-dropdown[data-filter-type="opd"]');

            if ($opdDropdown.length === 0) return;

            // Evaluasi pilihan Jenis
            var isJenisAll = $jenisDropdown.find('.check-all').is(':checked') || 
                             $jenisDropdown.find('.check-item:checked').length === 0 || 
                             $jenisDropdown.find('.check-item:checked').length === $jenisDropdown.find('.check-item').length;
            var activeJenis = isJenisAll ? 'all' : $jenisDropdown.find('.check-item:checked').map(function() { return $(this).val(); }).get();

            // Evaluasi pilihan Bidang / Tim
            var isBidangAll = $bidangDropdown.find('.check-all').is(':checked') || 
                              $bidangDropdown.find('.check-item:checked').length === 0 || 
                              $bidangDropdown.find('.check-item:checked').length === $bidangDropdown.find('.check-item').length;
            var activeBidang = isBidangAll ? 'all' : $bidangDropdown.find('.check-item:checked').map(function() { return $(this).val(); }).get();

            var $opdItems = $opdDropdown.find('.dropdown-checkbox-list .dropdown-item-check:not(.item-all)');
            var totalVisibleTables = 0;

            $opdItems.each(function() {
                var itemJenis = $(this).attr('data-jenis');
                var itemTim = $(this).attr('data-tim');

                var matchJenis = (activeJenis === 'all') || (activeJenis.indexOf(itemJenis) > -1);
                var matchBidang = (activeBidang === 'all') || (activeBidang.indexOf(itemTim) > -1);

                if (matchJenis && matchBidang) {
                    $(this).removeClass('cascaded-out').show();
                    var countMatch = $(this).find('.item-count').text().replace(/[^0-9]/g, '');
                    totalVisibleTables += parseInt(countMatch || 0, 10);
                } else {
                    $(this).addClass('cascaded-out').hide();
                    // Uncheck item yang tidak cocok dengan filter Jenis/Tim yang aktif
                    $(this).find('.check-item').prop('checked', false);
                }
            });

            // Update teks total counter pada opsi 'Semua' di dropdown Instansi
            var formattedTotal = new Intl.NumberFormat('id-ID').format(totalVisibleTables);
            $opdDropdown.find('.item-all .item-count').text('(' + formattedTotal + ')');

            // Sinkronisasi status master 'Semua' pada dropdown Instansi
            var visibleItems = $opdItems.not('.cascaded-out').length;
            var visibleChecked = $opdItems.not('.cascaded-out').find('.check-item:checked').length;
            $opdDropdown.find('.check-all').prop('checked', visibleItems > 0 && visibleItems === visibleChecked);
        }

        // Jalankan sinkronisasi cascading filter saat halaman dimuat
        syncCascadingFilters();

        // Hubungkan perubahan pada dropdown Jenis & Bidang ke Cascading Filter
        $(document).on('change', '.custom-filter-dropdown[data-filter-type="jenis"] input[type="checkbox"], .custom-filter-dropdown[data-filter-type="bidang"] input[type="checkbox"]', function() {
            syncCascadingFilters();
        });

        // Cegah tombol Enter pada kotak pencarian dropdown memicu submit form
        $(document).on('keydown', '.dropdown-search-box', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                return false;
            }
        });

        // Simpan status checkbox saat dropdown mulai dibuka agar bisa di-revert saat 'Batal'
        $(document).on('show.bs.dropdown', '.custom-filter-dropdown', function() {
            var $dropdown = $(this);
            var $menu = $dropdown.find('.custom-filter-menu');
            var ddBtn = $dropdown.find('.filter-trigger-btn')[0];

            // Pasang strategi floating 'fixed' dan auto-flip pada Popper instance
            if (ddBtn && typeof bootstrap !== 'undefined') {
                var dd = bootstrap.Dropdown.getInstance(ddBtn);
                if (dd) {
                    dd._config.popperConfig = {
                        strategy: 'fixed',
                        modifiers: [
                            {
                                name: 'flip',
                                options: {
                                    fallbackPlacements: ['top-start', 'top', 'bottom-start']
                                }
                            },
                            {
                                name: 'preventOverflow',
                                options: {
                                    boundary: 'viewport',
                                    padding: 12
                                }
                            }
                        ]
                    };
                }
            }

            var itemStates = [];
            $menu.find('.check-item').each(function() {
                itemStates.push($(this).is(':checked'));
            });

            $dropdown.data('initial-states', {
                all: $menu.find('.check-all').is(':checked'),
                items: itemStates
            });

            // Reset input search dan tampilkan seluruh baris yang lolos cascading filter
            $menu.find('.dropdown-search-box').val('');
            $menu.find('.dropdown-item-check:not(.cascaded-out)').show();
        });

        // Live filter saat mengetik di kotak pencarian dropdown
        $(document).on('input', '.dropdown-search-box', function() {
            var query = $(this).val().toLowerCase().trim();
            var $list = $(this).closest('.custom-filter-menu').find('.dropdown-checkbox-list');

            $list.find('.dropdown-item-check:not(.item-all)').each(function() {
                if ($(this).hasClass('cascaded-out')) {
                    $(this).hide();
                    return;
                }
                var text = $(this).find('.item-label').text().toLowerCase();
                if (query === '' || text.indexOf(query) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Toggle 'Semua' master checkbox
        $(document).on('change', '.check-all', function() {
            var isChecked = $(this).is(':checked');
            var $menu = $(this).closest('.custom-filter-menu');
            $menu.find('.dropdown-item-check:not(.cascaded-out) .check-item').prop('checked', isChecked);
        });

        // Sinkronisasi status 'Semua' jika checkbox individual diubah
        $(document).on('change', '.check-item', function() {
            var $menu = $(this).closest('.custom-filter-menu');
            var totalItems = $menu.find('.dropdown-item-check:not(.cascaded-out) .check-item').length;
            var totalChecked = $menu.find('.dropdown-item-check:not(.cascaded-out) .check-item:checked').length;

            $menu.find('.check-all').prop('checked', totalItems > 0 && totalItems === totalChecked);
        });

        // Tombol Batal: kembalikan state pilihan ke saat dropdown dibuka lalu tutup menu
        $(document).on('click', '.btn-dropdown-cancel', function(e) {
            e.preventDefault();
            var $dropdown = $(this).closest('.custom-filter-dropdown');
            var $menu = $dropdown.find('.custom-filter-menu');

            var initialStates = $dropdown.data('initial-states');
            if (initialStates) {
                $menu.find('.check-item').each(function(idx) {
                    $(this).prop('checked', initialStates.items[idx] !== undefined ? initialStates.items[idx] : true);
                });
                $menu.find('.check-all').prop('checked', initialStates.all);
            }

            // Bersihkan pencarian
            $menu.find('.dropdown-search-box').val('');
            $menu.find('.dropdown-item-check:not(.cascaded-out)').show();

            // Tutup dropdown
            var ddBtn = $dropdown.find('.filter-trigger-btn')[0];
            if (ddBtn) {
                var instance = bootstrap.Dropdown.getInstance(ddBtn);
                if (instance) {
                    instance.hide();
                }
            }

            // Resync jika dropdown jenis atau bidang dibatalkan
            if ($dropdown.data('filter-type') === 'jenis' || $dropdown.data('filter-type') === 'bidang') {
                syncCascadingFilters();
            }
        });

        // Tombol Terapkan: trigger submit pada form filter
        $(document).on('click', '.btn-dropdown-apply', function(e) {
            e.preventDefault();
            $('#formFilterMaster').submit();
        });

        // Normalisasi parameter sebelum form filter disubmit agar URL tetap bersih & efisien
        $('#formFilterMaster').on('submit', function() {
            $('.custom-filter-dropdown').each(function() {
                var $dropdown = $(this);
                var filterType = $dropdown.data('filter-type');
                var $checkAll = $dropdown.find('.check-all');
                var $activeItems = $dropdown.find('.dropdown-item-check:not(.cascaded-out) .check-item');
                var $checkedItems = $activeItems.filter(':checked');

                // Nonaktifkan semua checkbox yang ter-filter keluar (cascaded-out) agar tidak ikut terkirim
                $dropdown.find('.dropdown-item-check.cascaded-out .check-item').prop('disabled', true);

                // Bersihkan fallback hidden input sebelumnya jika ada
                $dropdown.find('input[type="hidden"].fallback-all').remove();

                // Jika 'Semua' dicentang, atau seluruh item aktif tercentang, atau tidak ada yang tercentang:
                // kirim filter_{type}=all dan nonaktifkan individual checkboxes agar tidak membanjiri URL query string
                if ($checkAll.is(':checked') || $checkedItems.length === 0 || $checkedItems.length === $activeItems.length) {
                    $activeItems.prop('disabled', true);
                    $dropdown.append('<input type="hidden" class="fallback-all" name="filter_' + filterType + '" value="all">');
                } else {
                    $activeItems.prop('disabled', false);
                }
            });
        });
    });



</script>

