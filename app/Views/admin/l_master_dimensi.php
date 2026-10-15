<style>
/* Fix bootstrap modal-dialog-scrollable with internal form */
.modal-dialog-scrollable form {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
}
.modal-dialog-scrollable form .modal-body {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
}
.modal-dialog-scrollable form .modal-footer {
    flex-shrink: 0;
}

.tree-builder-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
}
.tree-builder-header {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 8px;
}
.tree-items-list {
    padding-right: 4px;
}
.tree-item-row {
    position: relative;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    margin-bottom: 6px;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.tree-item-row:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
/* Level 0 (Utama) */
.tree-item-row.level-0 {
    margin-left: 0;
    border-left: 4px solid #FF6D1F;
}
.tree-item-row.level-0.theme-baris {
    border-left: 4px solid #2563EB;
}
.tree-item-row.level-0.theme-periode {
    border-left: 4px solid #16A34A;
}
/* Level 1 (Sub Item) */
.tree-item-row.level-1 {
    margin-left: 28px;
    border-left: 4px solid #0ea5e9;
    background: #f8fafc;
}
.tree-item-row.level-1::before {
    content: "";
    position: absolute;
    left: -18px;
    top: 50%;
    width: 14px;
    height: 2px;
    background: #cbd5e1;
}
/* Level 2 (Sub-sub Item) */
.tree-item-row.level-2 {
    margin-left: 56px;
    border-left: 4px solid #10b981;
    background: #f0fdf4;
}
.tree-item-row.level-2::before {
    content: "";
    position: absolute;
    left: -18px;
    top: 50%;
    width: 14px;
    height: 2px;
    background: #cbd5e1;
}
.tree-item-inputs {
    display: flex;
    gap: 8px;
    align-items: center;
    flex: 1;
}
.tree-item-actions {
    display: flex;
    gap: 4px;
    align-items: center;
}
.btn-sub-badge {
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    font-weight: 600;
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 6px;
    transition: all 0.15s ease;
}
.btn-sub-badge:hover {
    background: #e2e8f0;
    color: #0284c7;
    border-color: #0284c7;
}
.btn-delete-item {
    color: #ef4444;
    background: #fef2f2;
    border: 1px solid #fee2e2;
    padding: 3px 7px;
    border-radius: 6px;
    font-size: 0.75rem;
    transition: all 0.15s ease;
}
.btn-delete-item:hover {
    background: #ef4444;
    color: #ffffff;
}
</style>

    <!-- Compact Top Action Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('admin/dda'); ?>" class="btn btn-sm btn-white border rounded-pill px-3 py-1 text-secondary text-xs fw-semibold shadow-xs">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke DDA
            </a>
            <span class="badge rounded-pill bg-light text-secondary border px-2 py-1 text-xxs fw-semibold">
                <i class="bi bi-boxes me-1 text-primary"></i> Master Dimensi Tabel
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm text-white rounded-pill px-3 py-1.5 text-xs fw-bold shadow-xs d-inline-flex align-items-center gap-1 btn-open-terpadu-modal" style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                <i class="bi bi-lightning-charge-fill me-1"></i> Impor Terpadu Sekaligus (Kolom, Baris & Periode)
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards (Sleek & Clean) -->
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-xxs fw-bold text-uppercase">1. Karakteristik Kolom</div>
                        <div class="fs-4 fw-bold text-dark mt-1"><?= count($list_karakteristik); ?> <span class="fs-7 fw-normal text-muted">kelompok</span></div>
                    </div>
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: #FFF4ED; color: #FF6D1F; width: 42px; height: 42px;">
                        <i class="bi bi-layout-three-columns fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-xxs fw-bold text-uppercase">2. Judul Baris Bertingkat</div>
                        <div class="fs-4 fw-bold text-dark mt-1"><?= count($list_baris); ?> <span class="fs-7 fw-normal text-muted">kelompok</span></div>
                    </div>
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: #EFF6FF; color: #2563EB; width: 42px; height: 42px;">
                        <i class="bi bi-list-nested fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-xxs fw-bold text-uppercase">3. Periode Waktu</div>
                        <div class="fs-4 fw-bold text-dark mt-1"><?= count($list_periode); ?> <span class="fs-7 fw-normal text-muted">kelompok</span></div>
                    </div>
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: #F0FDF4; color: #16A34A; width: 42px; height: 42px;">
                        <i class="bi bi-calendar3 fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-0">
            <ul class="nav nav-pills card-header-pills gap-2 pb-3 border-bottom" id="dimensionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link px-3 py-2 fw-semibold rounded-3 d-flex align-items-center gap-2 <?= ($active_tab === 'karakteristik') ? 'active bg-primary-orange text-white shadow-sm' : 'text-secondary bg-light'; ?>"
                       href="<?= base_url('admin/master_dimensi?tab=karakteristik'); ?>" role="tab">
                        <i class="bi bi-layout-three-columns"></i>
                        <span>1. Karakteristik (Kolom Asimetris)</span>
                        <span class="badge <?= ($active_tab === 'karakteristik') ? 'bg-white text-dark' : 'bg-secondary text-white'; ?> rounded-pill ms-1"><?= count($list_karakteristik); ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link px-3 py-2 fw-semibold rounded-3 d-flex align-items-center gap-2 <?= ($active_tab === 'baris') ? 'active bg-primary-orange text-white shadow-sm' : 'text-secondary bg-light'; ?>"
                       href="<?= base_url('admin/master_dimensi?tab=baris'); ?>" role="tab">
                        <i class="bi bi-list-nested"></i>
                        <span>2. Judul Baris (Bertingkat / Hierarki)</span>
                        <span class="badge <?= ($active_tab === 'baris') ? 'bg-white text-dark' : 'bg-secondary text-white'; ?> rounded-pill ms-1"><?= count($list_baris); ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link px-3 py-2 fw-semibold rounded-3 d-flex align-items-center gap-2 <?= ($active_tab === 'periode') ? 'active bg-primary-orange text-white shadow-sm' : 'text-secondary bg-light'; ?>"
                       href="<?= base_url('admin/master_dimensi?tab=periode'); ?>" role="tab">
                        <i class="bi bi-calendar3"></i>
                        <span>3. Periode Waktu (Tahun/Triwulan)</span>
                        <span class="badge <?= ($active_tab === 'periode') ? 'bg-white text-dark' : 'bg-secondary text-white'; ?> rounded-pill ms-1"><?= count($list_periode); ?></span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <!-- TAB 1: KARAKTERISTIK (KOLOM & SUB-KOLOM ASIMETRIS) -->
            <?php if ($active_tab === 'karakteristik'): ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Daftar Kelompok Karakteristik (Dimensi Kolom)</h5>
                        <p class="text-muted small mb-0">Mendukung kolom bertingkat asimetris dengan notasi <code>Induk || Anak</code> serta kolom mandiri dalam dwibahasa (Indonesia & English).</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm px-3 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2 btn-open-import-kolom shadow-xs" data-target-builder="add_direct">
                            <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Impor Spreadsheet / CSV
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2"
                                style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;"
                                data-bs-toggle="modal" data-bs-target="#modalAddKarakteristik">
                            <i class="bi bi-plus-circle"></i> Tambah Kelompok Kolom
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="6%" class="text-center">No.</th>
                                <th width="50%">Nama Kelompok (Indonesia & English)</th>
                                <th width="22%" class="text-center">Jumlah Kolom</th>
                                <th width="22%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_karakteristik)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada kelompok karakteristik kolom. Klik tombol <strong>Tambah Kelompok Kolom</strong> di atas untuk membuat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_karakteristik as $k): ?>
                                    <tr>
                                        <td class="text-center fw-semibold text-muted"><?= $no++; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= esc($k->nama_kelompok_ind); ?></div>
                                            <?php if (!empty($k->nama_kelompok_en)): ?>
                                                <div class="text-primary small fst-italic"><i class="bi bi-translate me-1"></i><?= esc($k->nama_kelompok_en); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge px-3 py-2 rounded-pill <?= $k->total_items > 0 ? 'bg-primary-soft text-primary-orange border border-warning' : 'bg-light text-secondary border'; ?> fw-bold">
                                                <i class="bi bi-columns-gap me-1"></i> <?= $k->total_items; ?> Kolom
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 btn-manage-kolom"
                                                        data-id="<?= $k->id; ?>" data-nama="<?= esc($k->nama_kelompok_ind); ?>"
                                                        title="Kelola Kolom & Live Preview">
                                                    <i class="bi bi-gear-fill me-1"></i> Kelola Item
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border rounded-3 btn-edit-kolom"
                                                        data-id="<?= $k->id; ?>" data-ind="<?= esc($k->nama_kelompok_ind); ?>"
                                                        data-en="<?= esc($k->nama_kelompok_en ?? ''); ?>"
                                                        title="Edit Nama Kelompok">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="<?= base_url('admin/master_dimensi/del_karakteristik/' . $k->id); ?>"
                                                   class="btn btn-sm btn-outline-danger rounded-3"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus kelompok karakteristik ini beserta semua item kolomnya?');"
                                                   title="Hapus Kelompok">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- TAB 2: JUDUL BARIS BERTINGKAT -->
            <?php elseif ($active_tab === 'baris'): ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Daftar Kelompok Judul Baris (Dimensi Baris)</h5>
                        <p class="text-muted small mb-0">Mendukung baris bertingkat dengan indentasi (Level 0, 1, 2) dan tipe baris (Kategori, Data, Subtotal, Grand Total) dwibahasa.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm px-3 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2 btn-open-import-modal shadow-xs" data-target-builder="add_direct">
                            <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Impor Spreadsheet / CSV
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2"
                                style="background: linear-gradient(45deg, #2563EB, #3b82f6); border: none;"
                                data-bs-toggle="modal" data-bs-target="#modalAddBaris">
                            <i class="bi bi-plus-circle"></i> Tambah Kelompok Baris
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="6%" class="text-center">No.</th>
                                <th width="50%">Nama Kelompok Baris (Indonesia & English)</th>
                                <th width="22%" class="text-center">Jumlah Baris</th>
                                <th width="22%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_baris)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada kelompok baris. Klik tombol <strong>Tambah Kelompok Baris</strong> di atas untuk membuat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_baris as $b): ?>
                                    <tr>
                                        <td class="text-center fw-semibold text-muted"><?= $no++; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= esc($b->nama_kelompok_ind); ?></div>
                                            <?php if (!empty($b->nama_kelompok_en)): ?>
                                                <div class="text-primary small fst-italic"><i class="bi bi-translate me-1"></i><?= esc($b->nama_kelompok_en); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge px-3 py-2 rounded-pill <?= $b->total_items > 0 ? 'bg-primary-soft text-primary border border-primary' : 'bg-light text-secondary border'; ?> fw-bold">
                                                <i class="bi bi-list-ol me-1"></i> <?= $b->total_items; ?> Baris
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 btn-manage-baris"
                                                        data-id="<?= $b->id; ?>" data-nama="<?= esc($b->nama_kelompok_ind); ?>"
                                                        title="Kelola Baris & Tree Preview">
                                                    <i class="bi bi-gear-fill me-1"></i> Kelola Item
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border rounded-3 btn-edit-baris"
                                                        data-id="<?= $b->id; ?>" data-ind="<?= esc($b->nama_kelompok_ind); ?>"
                                                        data-en="<?= esc($b->nama_kelompok_en ?? ''); ?>"
                                                        title="Edit Nama Kelompok">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="<?= base_url('admin/master_dimensi/del_baris/' . $b->id); ?>"
                                                   class="btn btn-sm btn-outline-danger rounded-3"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus kelompok baris ini beserta seluruh itemnya?');"
                                                   title="Hapus Kelompok">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- TAB 3: PERIODE WAKTU -->
            <?php elseif ($active_tab === 'periode'): ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Daftar Kelompok Periode Waktu (Dimensi Waktu)</h5>
                        <p class="text-muted small mb-0">Menyimpan dimensi tahun, triwulan, atau bulan dwibahasa untuk kolom waktu.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2"
                            style="background: linear-gradient(45deg, #16A34A, #22c55e); border: none;"
                            data-bs-toggle="modal" data-bs-target="#modalAddPeriode">
                        <i class="bi bi-plus-circle"></i> Tambah Kelompok Periode
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="6%" class="text-center">No.</th>
                                <th width="50%">Nama Kelompok Periode (Indonesia & English)</th>
                                <th width="22%" class="text-center">Jumlah Periode</th>
                                <th width="22%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_periode)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada kelompok periode. Klik tombol <strong>Tambah Kelompok Periode</strong> di atas untuk membuat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_periode as $p): ?>
                                    <tr>
                                        <td class="text-center fw-semibold text-muted"><?= $no++; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= esc($p->nama_kelompok_ind); ?></div>
                                            <?php if (!empty($p->nama_kelompok_en)): ?>
                                                <div class="text-success small fst-italic"><i class="bi bi-translate me-1"></i><?= esc($p->nama_kelompok_en); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge px-3 py-2 rounded-pill <?= $p->total_items > 0 ? 'bg-success-soft text-success border border-success' : 'bg-light text-secondary border'; ?> fw-bold">
                                                <i class="bi bi-clock-history me-1"></i> <?= $p->total_items; ?> Periode
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 btn-manage-periode"
                                                        data-id="<?= $p->id; ?>" data-nama="<?= esc($p->nama_kelompok_ind); ?>"
                                                        title="Kelola Daftar Periode">
                                                    <i class="bi bi-gear-fill me-1"></i> Kelola Item
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border rounded-3 btn-edit-periode"
                                                        data-id="<?= $p->id; ?>" data-ind="<?= esc($p->nama_kelompok_ind); ?>"
                                                        data-en="<?= esc($p->nama_kelompok_en ?? ''); ?>"
                                                        title="Edit Nama Kelompok">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="<?= base_url('admin/master_dimensi/del_periode/' . $p->id); ?>"
                                                   class="btn btn-sm btn-outline-danger rounded-3"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus kelompok periode ini beserta seluruh itemnya?');"
                                                   title="Hapus Kelompok">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODALS SECTION                                                            -->
<!-- ========================================================================= -->

<!-- 1. MODAL ADD KARAKTERISTIK (DENGAN LIVE PREVIEW & INPUT BILINGUAL) -->
<div class="modal fade" id="modalAddKarakteristik" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4" style="background: #FFF4ED;">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-plus-circle-fill text-primary-orange me-2"></i>Tambah Kelompok Karakteristik (Kolom)</h5>
                    <div class="small text-muted">Input nama kelompok, sub-kolom dwibahasa, dan pantau Live Preview struktur tabelnya secara real-time</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/add_karakteristik'); ?>" method="post">
                <div class="modal-body p-4">
                    <!-- Baris 1: Nama Kelompok -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">Nama Kelompok (Bahasa Indonesia) <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kelompok_ind" id="add_karakteristik_nama_ind" class="form-control" placeholder="Contoh: Sarana Farmasi & Kesehatan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">Nama Kelompok (English)</label>
                            <input type="text" name="nama_kelompok_en" class="form-control" placeholder="Contoh: Pharmaceutical & Health Facilities">
                        </div>
                    </div>

                    <!-- Baris 2: Dynamic Tree Builder Karakteristik (Kolom) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark small mb-0">
                                <i class="bi bi-layout-three-columns text-primary-orange me-1"></i> Item Karakteristik Kolom
                            </label>
                            <span class="badge bg-primary-soft text-primary-orange border" id="add_kolom_count">0 Kolom</span>
                        </div>
                        <div class="tree-builder-card">
                            <!-- Quick Action Toolbar: Impor Spreadsheet -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 p-2 bg-light border rounded-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 text-xxs fw-bold shadow-xs d-inline-flex align-items-center gap-1 btn-open-import-kolom" data-target-builder="add">
                                        <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Impor Spreadsheet / CSV
                                    </button>
                                </div>
                                <div class="text-muted small" style="font-size: 0.72rem;">
                                    <i class="bi bi-magic me-1 text-primary"></i>Pilih kolom data dari tabel tanpa mengikutkan judul baris
                                </div>
                            </div>

                            <div class="tree-builder-header d-flex justify-content-between align-items-center">
                                <div style="flex: 1;"><i class="bi bi-flag-fill text-danger me-1"></i> Item Kolom (Indonesia)</div>
                                <div style="flex: 1; margin-left: 8px;"><i class="bi bi-globe text-primary me-1"></i> Item Kolom (Inggris)</div>
                                <div style="width: 100px;" class="text-center">Aksi</div>
                            </div>
                            <div class="tree-items-list" id="add_kolom_items_list"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 mt-2 fw-bold d-inline-flex align-items-center gap-1" id="btn_add_kolom_root">
                                <i class="bi bi-plus-circle-fill"></i> Tambah Item Kolom Baru
                            </button>
                        </div>
                        <input type="hidden" name="items_text_ind" id="add_kolom_text_ind">
                        <input type="hidden" name="items_text_en" id="add_kolom_text_en">
                        <input type="hidden" name="items_json" id="add_kolom_json">
                        <div class="form-text small text-muted mt-2">
                            <i class="bi bi-info-circle me-1 text-primary"></i> Klik tombol <strong>+ Sub</strong> untuk membuat sub-kolom di bawah kolom induk. Item tanpa sub-kolom otomatis menjadi kolom mandiri (rowspan 2).
                        </div>
                    </div>

                    <!-- Baris 3: Live Preview Header Box -->
                    <div class="border rounded-3 p-3 bg-light shadow-inner">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="fw-bold text-dark small"><i class="bi bi-eye-fill text-primary me-1"></i> Live Visual Header Preview (Real-Time)</div>
                            <span class="badge bg-primary-soft text-primary-orange border small px-2 py-1"><i class="bi bi-translate me-1"></i> Dwibahasa (ID / EN)</span>
                        </div>
                        <div class="table-responsive bg-white rounded-2 border" style="min-height: 120px; overflow-x: auto;">
                            <table class="table table-bordered text-center align-middle mb-0" id="table_preview_add_kolom">
                                <thead>
                                    <tr><th class="py-4 text-muted fst-italic">Ketik daftar kolom di atas untuk melihat preview visual otomatis...</th></tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #FF6D1F; border: none;">Simpan Kelompok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL EDIT KARAKTERISTIK -->
<div class="modal fade" id="modalEditKarakteristik" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Kelompok Karakteristik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/edt_karakteristik'); ?>" method="post">
                <input type="hidden" name="id" id="edit_kolom_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Kelompok (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kelompok_ind" id="edit_kolom_ind" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Kelompok (English)</label>
                        <input type="text" name="nama_kelompok_en" id="edit_kolom_en" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL MANAGE & LIVE PREVIEW KOLOM (DENGAN INPUT BILINGUAL) -->
<div class="modal fade" id="modalManageKolom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4 bg-white">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-dark"><i class="bi bi-layout-three-columns text-primary me-2"></i>Kelola Item Kolom: <span id="manage_kolom_title"></span></h5>
                    <div class="small text-muted">Pengaturan sub-kolom dwibahasa (Indonesia & English) dengan live preview otomatis</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/sync_karakteristik_items'); ?>" method="post" id="formSyncKolom">
                <input type="hidden" name="id_kelompok" id="manage_kolom_id">
                <div class="modal-body p-4">
                    <!-- Baris 2: Dynamic Tree Builder Karakteristik (Kolom) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark small mb-0">
                                <i class="bi bi-layout-three-columns text-primary-orange me-1"></i> Item Karakteristik Kolom
                            </label>
                            <span class="badge bg-primary-soft text-primary-orange border" id="manage_kolom_count">0 Kolom</span>
                        </div>
                        <div class="tree-builder-card">
                            <!-- Quick Action Toolbar: Impor Spreadsheet -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 p-2 bg-light border rounded-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 text-xxs fw-bold shadow-xs d-inline-flex align-items-center gap-1 btn-open-import-kolom" data-target-builder="manage">
                                        <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Impor Spreadsheet / CSV
                                    </button>
                                </div>
                                <div class="text-muted small" style="font-size: 0.72rem;">
                                    <i class="bi bi-magic me-1 text-primary"></i>Pilih kolom data dari tabel tanpa mengikutkan judul baris
                                </div>
                            </div>

                            <div class="tree-builder-header d-flex justify-content-between align-items-center">
                                <div style="flex: 1;"><i class="bi bi-flag-fill text-danger me-1"></i> Item Kolom (Indonesia)</div>
                                <div style="flex: 1; margin-left: 8px;"><i class="bi bi-globe text-primary me-1"></i> Item Kolom (Inggris)</div>
                                <div style="width: 100px;" class="text-center">Aksi</div>
                            </div>
                            <div class="tree-items-list" id="manage_kolom_items_list"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 mt-2 fw-bold d-inline-flex align-items-center gap-1" id="btn_manage_kolom_root">
                                <i class="bi bi-plus-circle-fill"></i> Tambah Item Kolom Baru
                            </button>
                        </div>
                        <input type="hidden" name="items_text_ind" id="manage_kolom_textarea_ind">
                        <input type="hidden" name="items_text_en" id="manage_kolom_textarea_en">
                        <input type="hidden" name="items_json" id="manage_kolom_json">
                    </div>

                    <!-- Live Matrix Preview Box -->
                    <div class="border rounded-3 p-3 bg-light shadow-inner">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-eye-fill text-primary me-1"></i> Live Visual Header Preview</h6>
                            <span class="badge bg-primary-soft text-primary-orange border small px-2 py-1"><i class="bi bi-translate me-1"></i> Dwibahasa (ID / EN)</span>
                        </div>
                        <div class="border rounded-2 p-2 bg-white table-responsive" style="min-height: 120px; overflow-x: auto;">
                            <table class="table table-bordered text-center align-middle mb-0" id="preview_table_kolom">
                                <thead id="preview_table_thead"></thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #FF6D1F; border: none;">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Kolom
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. MODAL ADD BARIS (DENGAN LIVE PREVIEW & INPUT BILINGUAL) -->
<div class="modal fade" id="modalAddBaris" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4" style="background: #EFF6FF;">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Kelompok Baris (Bertingkat)</h5>
                    <div class="small text-muted">Input judul baris bertingkat dwibahasa dengan awalan <code>-- </code> dan lihat live render hierarkinya</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/add_baris'); ?>" method="post">
                <div class="modal-body p-4">
                    <!-- Baris 1: Nama Kelompok Baris -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">Nama Kelompok (Bahasa Indonesia) <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kelompok_ind" id="add_baris_nama_ind" class="form-control" placeholder="Contoh: Pengeluaran Pangan & Non-Pangan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">Nama Kelompok (English)</label>
                            <input type="text" name="nama_kelompok_en" class="form-control" placeholder="Contoh: Food & Non-Food Expenditure">
                        </div>
                    </div>

                    <!-- Baris 2: Dynamic Tree Builder Judul Baris -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark small mb-0">
                                <i class="bi bi-list-nested text-primary me-1"></i> Item Judul Baris
                            </label>
                            <span class="badge bg-primary-soft text-primary border" id="add_baris_count">0 Baris</span>
                        </div>
                        <div class="tree-builder-card">
                            <!-- Quick Action Toolbar: Bulk Paste & Presets -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 p-2 bg-light border rounded-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 text-xxs fw-bold shadow-xs d-inline-flex align-items-center gap-1 btn-open-import-modal" data-target-builder="add">
                                        <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Impor Spreadsheet / CSV
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-3 text-xxs fw-bold shadow-xs d-inline-flex align-items-center gap-1" id="btn_toggle_add_bulk">
                                        <i class="bi bi-clipboard2-plus-fill"></i> Mode Cepat (Paste Teks)
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border py-1 px-3 text-xxs fw-bold dropdown-toggle shadow-xs d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-lightning-charge-fill text-warning"></i> Template Siap Pakai
                                        </button>
                                        <ul class="dropdown-menu shadow-sm">
                                            <li><a class="dropdown-item small py-2 fw-semibold" href="javascript:void(0)" id="preset_add_iklim"><i class="bi bi-cloud-sun text-warning me-2"></i>Unsur Iklim (4 Stasiun Lengkap)</a></li>
                                            <li><a class="dropdown-item small py-2 fw-semibold" href="javascript:void(0)" id="preset_add_kabkot"><i class="bi bi-geo-alt text-danger me-2"></i>35 Kabupaten / Kota se-Jawa Tengah</a></li>
                                            <li><a class="dropdown-item small py-2" href="javascript:void(0)" id="preset_add_pangan"><i class="bi bi-basket text-success me-2"></i>Pengeluaran Komoditas (2 Level)</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-muted small" style="font-size: 0.72rem;">
                                    <i class="bi bi-magic me-1 text-primary"></i>Paste puluhan baris sekaligus tanpa klik satu-satu
                                </div>
                            </div>

                            <!-- Drawer / Kotak Paste Teks Bulk (Add) -->
                            <div class="card border-primary p-3 mb-2 bg-white shadow-sm" id="box_add_bulk" style="display: none; border-left: 4px solid #2563EB !important;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-primary mb-0 small d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-textarea-t fs-6"></i> Input Massal / Paste Teks Hierarki
                                    </h6>
                                    <button type="button" class="btn-close btn-sm" id="btn_close_add_bulk" aria-label="Tutup"></button>
                                </div>
                                <p class="text-muted text-xxs mb-2" style="font-size: 0.75rem;">
                                    Paste daftar baris bertingkat di bawah. Gunakan awalan <code>-- </code> untuk <b>Sub Bab (Level 1)</b>, <code>---- </code> untuk <b>Sub-Sub (Level 2)</b>, dan <code>//</code> untuk bahasa Inggris (Contoh: <code>Suhu // Temperature</code> atau <code>-- Minimum // Minimum</code>).
                                </p>
                                <textarea class="form-control font-monospace text-xxs mb-2 p-2 border" id="textarea_add_bulk" rows="8" placeholder="Contoh:&#10;Stasiun Klimatologi Semarang // Semarang Climatological Station&#10;-- Suhu // Temperature&#10;---- Minimum // Minimum&#10;---- Rata-Rata // Average&#10;---- Maksimum // Maximum&#10;-- Kelembaban (%) // Humidity (%)&#10;..."></textarea>
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm py-1 px-3 text-xxs fw-bold shadow-xs" id="btn_apply_add_bulk">
                                            <i class="bi bi-magic me-1"></i> Terapkan ke Tree Builder
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 text-xxs" id="btn_clear_add_bulk">
                                            <i class="bi bi-trash me-1"></i> Bersihkan
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-light btn-sm py-1 px-2 text-xxs text-secondary border" id="btn_copy_current_add_baris">
                                        <i class="bi bi-clipboard me-1"></i> Salin Data Tree Saat Ini
                                    </button>
                                </div>
                            </div>

                            <!-- Toolbar Penomoran & Huruf Otomatis -->
                            <div class="auto-prefix-toolbar bg-white border rounded-2 p-2 mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-xs">
                                <div class="d-flex align-items-center gap-1 text-xxs fw-bold text-secondary">
                                    <i class="bi bi-sort-numeric-down text-primary fs-6"></i>
                                    <span>Penomoran Otomatis:</span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-primary border" style="font-size: 0.68rem;">Bab:</span>
                                        <select class="form-select form-select-sm py-0 px-2 text-xxs" style="height: 25px; width: auto;" id="add_prefix_bab">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kapital">Huruf (A, B, C...)</option>
                                            <option value="romawi_kapital">Romawi (I, II, III...)</option>
                                            <option value="angka">Angka (1, 2, 3...)</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-info border" style="font-size: 0.68rem;">Sub Bab:</span>
                                        <select class="form-select form-select-sm py-0 px-2 text-xxs" style="height: 25px; width: auto;" id="add_prefix_subbab">
                                            <option value="angka" selected>Angka (1, 2, 3...)</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                            <option value="none">Tanpa Nomor</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-success border" style="font-size: 0.68rem;">Sub-Sub:</span>
                                        <select class="form-select form-select-sm py-0 px-2 text-xxs" style="height: 25px; width: auto;" id="add_prefix_subsub">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="romawi_kecil">Romawi (i, ii, iii...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                        </select>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary btn-sm py-0 px-2 text-xxs fw-bold d-inline-flex align-items-center gap-1 shadow-xs" id="btn_add_apply_prefix" style="height: 25px;">
                                            <i class="bi bi-magic"></i> Generate
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 text-xxs d-inline-flex align-items-center gap-1 shadow-xs" id="btn_add_clean_prefix" style="height: 25px;" title="Bersihkan nomor / huruf">
                                            <i class="bi bi-eraser"></i> Bersihkan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="tree-builder-header d-flex justify-content-between align-items-center" style="background: #eff6ff; color: #1e40af;">
                                <div style="flex: 1;"><i class="bi bi-flag-fill text-danger me-1"></i> Baris (Indonesia)</div>
                                <div style="flex: 1; margin-left: 8px;"><i class="bi bi-globe text-primary me-1"></i> Baris (Inggris)</div>
                                <div style="width: 100px;" class="text-center">Aksi</div>
                            </div>
                            <div class="tree-items-list" id="add_baris_items_list"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 mt-2 fw-bold d-inline-flex align-items-center gap-1" id="btn_add_baris_root">
                                <i class="bi bi-plus-circle-fill"></i> Tambah Item Baris Baru
                            </button>
                        </div>
                        <input type="hidden" name="items_text_ind" id="add_baris_text_ind">
                        <input type="hidden" name="items_text_en" id="add_baris_text_en">
                        <input type="hidden" name="items_json" id="add_baris_json">
                        <div class="form-text small text-muted mt-2">
                            <i class="bi bi-info-circle me-1 text-primary"></i> Klik tombol <strong>+ Sub</strong> untuk membuat baris bertingkat (anak kategori/data). Baris bertipe Total otomatis ditebalkan.
                        </div>
                    </div>

                    <!-- Baris 3: Live Preview Hierarki Baris -->
                    <div class="border rounded-3 p-3 bg-light shadow-inner">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="fw-bold text-dark small"><i class="bi bi-eye-fill text-primary me-1"></i> Live Render Hierarki Baris (Real-Time)</div>
                            <span class="badge bg-primary-soft text-primary border small px-2 py-1"><i class="bi bi-translate me-1"></i> Dwibahasa (ID / EN)</span>
                        </div>
                        <div class="table-responsive bg-white rounded-2 border" style="min-height: 120px; overflow-x: auto;">
                            <table class="table table-hover align-middle mb-0" id="table_preview_add_baris">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="10%" class="text-center">No</th>
                                        <th width="65%">Judul Baris</th>
                                        <th width="25%" class="text-center">Tipe / Indent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="3" class="text-center py-4 text-muted fst-italic">Ketik daftar baris di atas untuk melihat preview hierarki otomatis...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #2563EB; border: none;">Simpan Kelompok Baris</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5. MODAL EDIT BARIS -->
<div class="modal fade" id="modalEditBaris" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Kelompok Baris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/edt_baris'); ?>" method="post">
                <input type="hidden" name="id" id="edit_baris_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Kelompok (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kelompok_ind" id="edit_baris_ind" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Kelompok (English)</label>
                        <input type="text" name="nama_kelompok_en" id="edit_baris_en" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 6. MODAL MANAGE & PREVIEW BARIS (DENGAN INPUT BILINGUAL) -->
<div class="modal fade" id="modalManageBaris" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4 bg-white">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-dark"><i class="bi bi-list-nested text-primary me-2"></i>Kelola Item Baris: <span id="manage_baris_title"></span></h5>
                    <div class="small text-muted">Pengaturan baris bertingkat dwibahasa dengan visualisasi hierarki</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/sync_baris_items'); ?>" method="post" id="formSyncBaris">
                <input type="hidden" name="id_kelompok" id="manage_baris_id">
                <div class="modal-body p-4">
                    <!-- Baris 2: Dynamic Tree Builder Judul Baris -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark small mb-0">
                                <i class="bi bi-list-nested text-info me-1"></i> Item Judul Baris
                            </label>
                            <span class="badge bg-primary-soft text-primary border" id="manage_baris_count">0 Baris</span>
                        </div>
                        <div class="tree-builder-card">
                            <!-- Quick Action Toolbar: Bulk Paste & Presets (Manage) -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 p-2 bg-light border rounded-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 text-xxs fw-bold shadow-xs d-inline-flex align-items-center gap-1 btn-open-import-modal" data-target-builder="manage">
                                        <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Impor Spreadsheet / CSV
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-3 text-xxs fw-bold shadow-xs d-inline-flex align-items-center gap-1" id="btn_toggle_manage_bulk">
                                        <i class="bi bi-clipboard2-plus-fill"></i> Mode Cepat (Paste Teks)
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border py-1 px-3 text-xxs fw-bold dropdown-toggle shadow-xs d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-lightning-charge-fill text-warning"></i> Template Siap Pakai
                                        </button>
                                        <ul class="dropdown-menu shadow-sm">
                                            <li><a class="dropdown-item small py-2 fw-semibold" href="javascript:void(0)" id="preset_manage_iklim"><i class="bi bi-cloud-sun text-warning me-2"></i>Unsur Iklim (4 Stasiun Lengkap)</a></li>
                                            <li><a class="dropdown-item small py-2 fw-semibold" href="javascript:void(0)" id="preset_manage_kabkot"><i class="bi bi-geo-alt text-danger me-2"></i>35 Kabupaten / Kota se-Jawa Tengah</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 text-xxs shadow-xs" id="btn_copy_current_manage_baris">
                                    <i class="bi bi-clipboard me-1"></i> Salin Data Tree ke Teks
                                </button>
                            </div>

                            <!-- Drawer / Kotak Paste Teks Bulk (Manage) -->
                            <div class="card border-primary p-3 mb-2 bg-white shadow-sm" id="box_manage_bulk" style="display: none; border-left: 4px solid #2563EB !important;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-primary mb-0 small d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-textarea-t fs-6"></i> Input Massal / Paste Teks Hierarki
                                    </h6>
                                    <button type="button" class="btn-close btn-sm" id="btn_close_manage_bulk" aria-label="Tutup"></button>
                                </div>
                                <p class="text-muted text-xxs mb-2" style="font-size: 0.75rem;">
                                    Gunakan awalan <code>-- </code> untuk <b>Sub Bab (Level 1)</b>, <code>---- </code> untuk <b>Sub-Sub (Level 2)</b>, dan <code>//</code> untuk bahasa Inggris.
                                </p>
                                <textarea class="form-control font-monospace text-xxs mb-2 p-2 border" id="textarea_manage_bulk" rows="8"></textarea>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm py-1 px-3 text-xxs fw-bold shadow-xs" id="btn_apply_manage_bulk">
                                        <i class="bi bi-magic me-1"></i> Terapkan ke Tree Builder
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm py-1 px-2 text-xxs text-secondary border" id="btn_cancel_manage_bulk">Tutup</button>
                                </div>
                            </div>

                            <!-- Toolbar Penomoran & Huruf Otomatis -->
                            <div class="auto-prefix-toolbar bg-white border rounded-2 p-2 mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-xs">
                                <div class="d-flex align-items-center gap-1 text-xxs fw-bold text-secondary">
                                    <i class="bi bi-sort-numeric-down text-primary fs-6"></i>
                                    <span>Penomoran Otomatis:</span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-primary border" style="font-size: 0.68rem;">Bab:</span>
                                        <select class="form-select form-select-sm py-0 px-2 text-xxs" style="height: 25px; width: auto;" id="manage_prefix_bab">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kapital">Huruf (A, B, C...)</option>
                                            <option value="romawi_kapital">Romawi (I, II, III...)</option>
                                            <option value="angka">Angka (1, 2, 3...)</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-info border" style="font-size: 0.68rem;">Sub Bab:</span>
                                        <select class="form-select form-select-sm py-0 px-2 text-xxs" style="height: 25px; width: auto;" id="manage_prefix_subbab">
                                            <option value="angka" selected>Angka (1, 2, 3...)</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                            <option value="none">Tanpa Nomor</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-success border" style="font-size: 0.68rem;">Sub-Sub:</span>
                                        <select class="form-select form-select-sm py-0 px-2 text-xxs" style="height: 25px; width: auto;" id="manage_prefix_subsub">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="romawi_kecil">Romawi (i, ii, iii...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                        </select>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary btn-sm py-0 px-2 text-xxs fw-bold d-inline-flex align-items-center gap-1 shadow-xs" id="btn_manage_apply_prefix" style="height: 25px;">
                                            <i class="bi bi-magic"></i> Generate
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 text-xxs d-inline-flex align-items-center gap-1 shadow-xs" id="btn_manage_clean_prefix" style="height: 25px;" title="Bersihkan nomor / huruf">
                                            <i class="bi bi-eraser"></i> Bersihkan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="tree-builder-header d-flex justify-content-between align-items-center" style="background: #eff6ff; color: #1e40af;">
                                <div style="flex: 1;"><i class="bi bi-flag-fill text-danger me-1"></i> Baris (Indonesia)</div>
                                <div style="flex: 1; margin-left: 8px;"><i class="bi bi-globe text-primary me-1"></i> Baris (Inggris)</div>
                                <div style="width: 100px;" class="text-center">Aksi</div>
                            </div>
                            <div class="tree-items-list" id="manage_baris_items_list"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 mt-2 fw-bold d-inline-flex align-items-center gap-1" id="btn_manage_baris_root">
                                <i class="bi bi-plus-circle-fill"></i> Tambah Item Baris Baru
                            </button>
                        </div>
                        <input type="hidden" name="items_text_ind" id="manage_baris_textarea_ind">
                        <input type="hidden" name="items_text_en" id="manage_baris_textarea_en">
                        <input type="hidden" name="items_json" id="manage_baris_json">
                    </div>

                    <!-- Live Render Hierarki Box -->
                    <div class="border rounded-3 p-3 bg-light shadow-inner">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-eye-fill text-primary me-1"></i> Live Render Hierarki Baris</h6>
                            <span class="badge bg-primary-soft text-primary border small px-2 py-1"><i class="bi bi-translate me-1"></i> Dwibahasa (ID / EN)</span>
                        </div>
                        <div class="border rounded-2 bg-white table-responsive" style="min-height: 120px; overflow-x: auto;">
                            <table class="table table-hover align-middle mb-0" id="preview_table_baris">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="10%" class="text-center">No</th>
                                        <th width="65%">Judul Baris</th>
                                        <th width="25%" class="text-center">Tipe / Indent</th>
                                    </tr>
                                </thead>
                                <tbody id="preview_table_baris_tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #2563EB; border: none;">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Baris
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 6B. MODAL IMPOR BARIS DARI SPREADSHEET / EXCEL / CSV -->
<div class="modal fade" id="modalImportSpreadsheet" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2 bg-white text-success shadow-xs">
                        <i class="bi bi-file-earmark-spreadsheet-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Impor Judul Baris dari Spreadsheet / Excel / CSV</h5>
                        <div class="small text-muted" style="font-size: 0.8rem;">Pilih kolom teks secara spesifik, otomatis buang kolom angka di sampingnya, dan hilangkan duplikasi baris berulang</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- STEP 1: PILIH SUMBER DATA -->
                <div class="card border mb-3 shadow-xs" style="border-radius: 12px; border-color: #cbd5e1 !important;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle" style="width: 20px; height: 20px; padding: 2px;">1</span>
                            SUMBER DATA
                        </span>
                        <ul class="nav nav-pills card-header-pills text-xxs" id="importSourceTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active py-1 px-3 fw-semibold rounded-pill" id="tab_gsheet_link" data-bs-toggle="pill" href="#pane_gsheet" role="tab">
                                    <i class="bi bi-google me-1 text-danger"></i> Google Spreadsheet URL
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-3 fw-semibold rounded-pill" id="tab_file_upload" data-bs-toggle="pill" href="#pane_upload" role="tab">
                                    <i class="bi bi-file-earmark-arrow-up me-1 text-success"></i> Upload File (Excel / CSV)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-3">
                        <div class="tab-content" id="importSourceTabContent">
                            <!-- Tab 1: Google Sheets -->
                            <div class="tab-pane fade show active" id="pane_gsheet" role="tabpanel">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="bi bi-link-45deg fs-5"></i></span>
                                            <input type="url" class="form-control" id="import_input_url" placeholder="Paste link Google Spreadsheet di sini (contoh: https://docs.google.com/spreadsheets/d/.../edit)">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-xs" id="btn_inspect_gsheet">
                                            <i class="bi bi-search"></i> Periksa Sumber
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text text-muted text-xxs mt-2">
                                    <i class="bi bi-shield-check text-success me-1"></i> Pastikan setelan berbagi spreadsheet diatur ke <b>"Siapa saja yang memiliki tautan dapat melihat"</b> (Anyone with the link can view).
                                </div>
                            </div>
                            <!-- Tab 2: Upload File -->
                            <div class="tab-pane fade" id="pane_upload" role="tabpanel">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-9">
                                        <input type="file" class="form-control" id="import_input_file" accept=".xlsx, .xls, .csv">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-success w-100 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-xs" id="btn_inspect_file">
                                            <i class="bi bi-upload"></i> Unggah & Baca
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text text-muted text-xxs mt-2">
                                    <i class="bi bi-info-circle text-primary me-1"></i> Mendukung format Excel Workbook (<b>.xlsx, .xls</b>) dengan banyak sheet atau file <b>.csv</b>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LOADING SPINNER -->
                <div id="import_loading" class="text-center py-4 my-2" style="display: none;">
                    <div class="spinner-border text-primary" role="status" style="width: 2.2rem; height: 2.2rem;">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <div class="text-dark fw-bold mt-2 small" id="import_loading_text">Sedang memindai lembar kerja dan kolom...</div>
                </div>

                <!-- ERROR ALERT -->
                <div id="import_error_box" class="alert alert-danger py-2 px-3 small my-2" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="import_error_msg"></span>
                </div>

                <!-- STEP 2 & 3: CONFIG & LIVE PREVIEW (Shown after inspect) -->
                <div id="import_config_section" style="display: none;">
                    <div class="card border mb-3 shadow-xs" style="border-radius: 12px; border-color: #cbd5e1 !important; background: #fafafa;">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                                <span class="badge bg-success rounded-circle" style="width: 20px; height: 20px; padding: 2px;">2</span>
                                PILIH LEMBAR KERJA (SHEET) & KOLOM TARGET
                            </span>
                            <span class="badge bg-light text-secondary border text-xxs" id="import_meta_badge">Total Data</span>
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div class="row g-3">
                                <!-- Pilihan Sheet (Jika Workbook memiliki sheet) -->
                                <div class="col-md-4" id="box_sheet_select">
                                    <label class="form-label fw-bold text-dark small mb-1">
                                        <i class="bi bi-layers-fill text-primary me-1"></i> Lembar Kerja (Sheet):
                                    </label>
                                    <select class="form-select form-select-sm" id="import_sheet_dropdown"></select>
                                </div>

                                <!-- Pilihan Kolom Indonesia (Wajib) -->
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark small mb-1">
                                        <i class="bi bi-flag-fill text-danger me-1"></i> Kolom Judul Baris (Indonesia) <span class="text-danger">*</span>:
                                    </label>
                                    <select class="form-select form-select-sm border-primary" id="import_col_ind_select"></select>
                                    <div class="text-muted text-xxs mt-1">Hanya kolom ini yang diambil ke bawah, kolom angka di sampingnya otomatis dibuang.</div>
                                </div>

                                <!-- Pilihan Kolom English (Opsional) -->
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark small mb-1">
                                        <i class="bi bi-globe text-primary me-1"></i> Kolom Judul (English / Opsional):
                                    </label>
                                    <select class="form-select form-select-sm" id="import_col_en_select"></select>
                                    <div class="text-muted text-xxs mt-1">Pilih jika ada kolom terjemahan bahasa Inggris di file.</div>
                                </div>
                            </div>

                            <!-- Opsi Pembersihan & Cerdas -->
                            <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="import_opt_dedup" checked>
                                        <label class="form-check-label text-dark fw-bold text-xxs" for="import_opt_dedup">
                                            Hapus Baris Duplikat (Deduplikasi)
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="import_opt_clean_header" checked>
                                        <label class="form-check-label text-dark fw-bold text-xxs" for="import_opt_clean_header">
                                            Buang Header Tabel & Angka Kolom (1), (2)
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="import_opt_translate_month" checked>
                                        <label class="form-check-label text-dark fw-bold text-xxs" for="import_opt_translate_month">
                                            Auto-Translate Bulan ke Inggris
                                        </label>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-3 text-xxs fw-bold shadow-xs d-inline-flex align-items-center gap-1" id="btn_refresh_preview">
                                    <i class="bi bi-arrow-clockwise"></i> Perbarui Preview
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: LIVE PREVIEW EKSTRAKSI -->
                    <div class="card border shadow-xs" style="border-radius: 12px; border-color: #cbd5e1 !important;">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                                <span class="badge bg-info text-white rounded-circle" style="width: 20px; height: 20px; padding: 2px;">3</span>
                                HASIL EKSTRAKSI & PRATINJAU BARIS
                            </span>
                            <span class="badge bg-primary-soft text-primary border text-xxs fw-bold px-2 py-1" id="import_result_count_badge">0 Baris Terdeteksi</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0" id="table_import_preview">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="8%" class="text-center text-xxs">No</th>
                                            <th width="46%" class="text-xxs">Judul Baris (Indonesia)</th>
                                            <th width="34%" class="text-xxs">Judul Baris (English)</th>
                                            <th width="12%" class="text-center text-xxs">Hierarki</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table_import_preview_tbody">
                                        <tr><td colspan="4" class="text-center py-3 text-muted text-xxs">Pilih kolom di atas untuk melihat hasil ekstraksi.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold d-inline-flex align-items-center gap-2 shadow-xs" id="btn_apply_import_to_tree" disabled style="background: linear-gradient(45deg, #2563EB, #3b82f6); border: none;">
                    <i class="bi bi-arrow-down-circle-fill"></i> Terapkan ke Tree Builder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 6C. MODAL IMPOR KARAKTERISTIK KOLOM DARI SPREADSHEET / EXCEL / CSV -->
<div class="modal fade" id="modalImportKolom" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2 bg-white text-primary-orange shadow-xs">
                        <i class="bi bi-layout-three-columns fs-4" style="color: #FF6D1F;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Impor Karakteristik Kolom dari Spreadsheet / Excel / CSV</h5>
                        <div class="small text-muted" style="font-size: 0.8rem;">Pilih kolom data dari tabel. Kolom identitas baris (seperti Bulan, Kabupaten/Kota, No) otomatis dilewati.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- STEP 1: PILIH SUMBER DATA -->
                <div class="card border mb-3 shadow-xs" style="border-radius: 12px; border-color: #fed7aa !important;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                            <span class="badge rounded-circle text-white" style="width: 20px; height: 20px; padding: 2px; background: #FF6D1F;">1</span>
                            SUMBER DATA TABEL
                        </span>
                        <ul class="nav nav-pills card-header-pills text-xxs" id="importKolomSourceTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active py-1 px-3 fw-semibold rounded-pill" id="tab_kolom_gsheet" data-bs-toggle="pill" href="#pane_kolom_gsheet" role="tab">
                                    <i class="bi bi-google me-1 text-danger"></i> Google Spreadsheet URL
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-3 fw-semibold rounded-pill" id="tab_kolom_upload" data-bs-toggle="pill" href="#pane_kolom_upload" role="tab">
                                    <i class="bi bi-file-earmark-arrow-up me-1 text-success"></i> Upload File (Excel / CSV)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-3">
                        <div class="tab-content" id="importKolomTabContent">
                            <!-- Tab 1: Google Sheets -->
                            <div class="tab-pane fade show active" id="pane_kolom_gsheet" role="tabpanel">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="bi bi-link-45deg fs-5"></i></span>
                                            <input type="url" class="form-control" id="import_kolom_input_url" placeholder="Paste link Google Spreadsheet di sini (contoh: https://docs.google.com/spreadsheets/d/.../edit)">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-xs text-white" style="background: #FF6D1F; border: none;" id="btn_inspect_kolom_gsheet">
                                            <i class="bi bi-search"></i> Periksa Sumber
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text text-muted text-xxs mt-2">
                                    <i class="bi bi-shield-check text-success me-1"></i> Pastikan tautan disetel <b>"Siapa saja yang memiliki link dapat melihat"</b>.
                                </div>
                            </div>
                            <!-- Tab 2: Upload File -->
                            <div class="tab-pane fade" id="pane_kolom_upload" role="tabpanel">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-9">
                                        <input type="file" class="form-control" id="import_kolom_input_file" accept=".xlsx, .xls, .csv">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-success w-100 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-xs" id="btn_inspect_kolom_file">
                                            <i class="bi bi-upload"></i> Unggah & Baca
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text text-muted text-xxs mt-2">
                                    <i class="bi bi-info-circle text-primary me-1"></i> Mendukung format Excel Workbook (<b>.xlsx, .xls</b>) multi-sheet atau <b>.csv</b>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LOADING SPINNER -->
                <div id="import_kolom_loading" class="text-center py-4 my-2" style="display: none;">
                    <div class="spinner-border" role="status" style="width: 2.2rem; height: 2.2rem; color: #FF6D1F;">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <div class="text-dark fw-bold mt-2 small" id="import_kolom_loading_text">Sedang memindai kolom lembar kerja...</div>
                </div>

                <!-- ERROR ALERT -->
                <div id="import_kolom_error_box" class="alert alert-danger py-2 px-3 small my-2" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="import_kolom_error_msg"></span>
                </div>

                <!-- STEP 2: CHECKLIST KOLOM KARAKTERISTIK (FITUR 1 & 2) -->
                <div id="import_kolom_config_section" style="display: none;">
                    <div class="card border mb-3 shadow-xs" style="border-radius: 12px; border-color: #cbd5e1 !important;">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                                <span class="badge bg-success rounded-circle" style="width: 20px; height: 20px; padding: 2px;">2</span>
                                PILIH KOLOM DATA (CHECKLIST)
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-secondary border text-xxs" id="import_kolom_meta_badge">0 Kolom</span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 text-xxs fw-bold" id="btn_kolom_check_all">
                                        <i class="bi bi-check-all"></i> Centang Semua Data
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 text-xxs" id="btn_kolom_uncheck_all">
                                        <i class="bi bi-dash-circle"></i> Bersihkan
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3 bg-light">
                            <!-- Multi-sheet dropdown (jika ada) -->
                            <div class="row g-2 align-items-center mb-3" id="box_kolom_sheet_select" style="display: none;">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-dark text-xxs mb-0">
                                        <i class="bi bi-layers-fill text-primary me-1"></i> Lembar Kerja (Sheet):
                                    </label>
                                </div>
                                <div class="col-md-9">
                                    <select class="form-select form-select-sm" id="import_kolom_sheet_dropdown"></select>
                                </div>
                            </div>

                            <!-- Petunjuk Fitur 1: Kolom Baris Dilewati -->
                            <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3 shadow-xs" style="font-size: 0.78rem; border-left: 4px solid #f59e0b !important;">
                                <i class="bi bi-shield-exclamation fs-5 text-warning"></i>
                                <div>
                                    <b>Fitur Cerdas Otomatis:</b> Kolom identitas baris (seperti <code>Bulan</code>, <code>Kabupaten/Kota</code>, <code>Wilayah</code>, atau <code>No</code>) <b>otomatis dilewati</b> agar tidak menduplikasi form judul baris. Header tabel dan sel yang di-merge dikenali secara otomatis.
                                </div>
                            </div>

                            <!-- Pilihan Format Terapkan ke Tree Builder Kolom -->
                            <div class="card p-2 mb-3 bg-white border rounded-3 shadow-xs" style="border-color: #e2e8f0 !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark text-xxs d-flex align-items-center gap-1">
                                        <i class="bi bi-diagram-3-fill text-primary"></i> Format Terapkan ke Tree Builder Kolom:
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap gap-3 py-1">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="opt_kolom_import_mode" id="opt_kolom_mode_tree" value="tree" checked>
                                        <label class="form-check-label text-xxs fw-bold text-primary" for="opt_kolom_mode_tree" style="cursor: pointer;">
                                            <i class="bi bi-diagram-2 me-1"></i> Struktur Hierarki Bab & Sub-Bab (Otomatis Induk & Anak) <span class="badge bg-primary text-white ms-1">Rekomendasi</span>
                                        </label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="opt_kolom_import_mode" id="opt_kolom_mode_parent" value="parent">
                                        <label class="form-check-label text-xxs fw-bold text-success" for="opt_kolom_mode_parent" style="cursor: pointer;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Karakteristik Utama Saja (Deduplikasi Tanpa Sub)
                                        </label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="opt_kolom_import_mode" id="opt_kolom_mode_flat" value="flat">
                                        <label class="form-check-label text-xxs fw-bold text-secondary" for="opt_kolom_mode_flat" style="cursor: pointer;">
                                            <i class="bi bi-list-columns me-1"></i> Kolom Datar / Flat (Semua Kolom Jadi Bab Mandiri)
                                        </label>
                                    </div>
                                </div>
                                <div class="text-muted text-xxs mt-1" id="kolom_mode_help_text" style="font-size: 0.72rem;">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> <b>Rekomendasi:</b> Otomatis menyusun header menjadi <b>Bab (Induk)</b> dan <b>Sub Bab (Anak/Tahun/Kategori)</b> sesuai struktur tabel asli.
                                </div>
                            </div>

                            <!-- Daftar Kolom Checklist (Fitur 2) -->
                            <div class="row g-2" id="container_kolom_checklist" style="max-height: 280px; overflow-y: auto;">
                                <!-- Item checklist dinamis -->
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: LIVE PREVIEW TABEL KOLOM -->
                    <div class="card border shadow-xs" style="border-radius: 12px; border-color: #cbd5e1 !important;">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                                <span class="badge bg-info text-white rounded-circle" style="width: 20px; height: 20px; padding: 2px;">3</span>
                                LIVE PREVIEW STRUKTUR HEADER KOLOM
                            </span>
                            <span class="badge bg-primary-soft text-primary-orange border text-xxs fw-bold px-2 py-1" id="import_kolom_count_badge">0 Kolom Terpilih</span>
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div class="table-responsive bg-white rounded-2 border" style="min-height: 80px; overflow-x: auto;">
                                <table class="table table-bordered text-center align-middle mb-0" id="table_preview_import_kolom">
                                    <thead>
                                        <tr><th class="py-3 text-muted fst-italic text-xs">Centang kolom di atas untuk melihat preview visual otomatis...</th></tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold d-inline-flex align-items-center gap-2 shadow-xs" id="btn_apply_import_kolom_to_tree" disabled style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                    <i class="bi bi-arrow-down-circle-fill"></i> Terapkan ke Tree Builder Kolom
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL IMPOR TERPADU (KOLOM & BARIS SEKALIGUS DARI 1 FILE EXCEL / SHEET)     -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalImportTerpadu" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header py-3 px-4 border-bottom text-white" style="background: linear-gradient(135deg, #FF6D1F 0%, #ff8c42 45%, #2563eb 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-white p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 40px; height: 40px;">
                        <i class="bi bi-lightning-charge-fill text-primary-orange fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">⚡ Impor Terpadu Sekaligus (Kolom & Baris)</h5>
                        <div class="text-white-50 small" style="font-size: 0.76rem;">Unggah 1 file Excel/Spreadsheet, sistem otomatis memilah Karakteristik Kolom dan Judul Baris secara independen.</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- STEP 1: PILIH SUMBER DATA & SHEET -->
                <div class="card border-0 shadow-xs mb-3 rounded-3" style="border: 1px solid #e2e8f0 !important; background: #ffffff;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle" style="width: 20px; height: 20px; padding: 2px;">1</span>
                            PILIH FILE EXCEL / GOOGLE SPREADSHEET
                        </span>
                        <span class="text-muted text-xxs"><i class="bi bi-file-earmark-spreadsheet me-1 text-success"></i>Mendukung multi-sheet .xlsx, .xls, .csv, atau link publik</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark text-xxs mb-1">
                                    <i class="bi bi-upload text-primary me-1"></i> Upload File Excel / CSV:
                                </label>
                                <input type="file" class="form-control form-control-sm" id="terpadu_file_input" accept=".xlsx, .xls, .csv">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark text-xxs mb-1">
                                    <i class="bi bi-link-45deg text-success me-1"></i> Atau Masukkan URL Google Spreadsheet:
                                </label>
                                <input type="url" class="form-control form-control-sm" id="terpadu_url_input" placeholder="https://docs.google.com/spreadsheets/d/...">
                            </div>
                        </div>

                        <!-- Dropdown Multi-sheet (jika ada) -->
                        <div class="row g-2 align-items-center mt-2 p-2 bg-light border rounded-2" id="box_terpadu_sheet_select" style="display: none;">
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark text-xxs mb-0">
                                    <i class="bi bi-layers-fill text-primary me-1"></i> Lembar Kerja (Sheet):
                                </label>
                            </div>
                            <div class="col-md-9">
                                <select class="form-select form-select-sm" id="terpadu_sheet_dropdown"></select>
                            </div>
                        </div>

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <div class="text-muted text-xxs" id="terpadu_source_status">
                                <i class="bi bi-info-circle me-1 text-primary"></i>Pilih file lalu klik tombol Periksa Sumber Data di samping.
                            </div>
                            <button type="button" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold shadow-xs d-inline-flex align-items-center gap-2" id="btn_terpadu_inspect">
                                <i class="bi bi-search"></i> Periksa Sumber Data
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div class="text-center py-3" id="terpadu_loading" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            <span class="text-muted text-xs">Menganalisis tabel, mendeteksi kolom karakteristik, dan mengekstrak baris...</span>
                        </div>

                        <!-- Error Box -->
                        <div class="alert alert-danger py-2 px-3 small mt-3 shadow-xs" id="terpadu_error_box" style="display: none;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="terpadu_error_msg"></span>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: TIGA PANEL KONFIGURASI TERPADU (KOLOM, BARIS, & PERIODE WAKTU) -->
                <div id="terpadu_results_section" style="display: none;">
                    <div class="row g-3">
                        <!-- PANEL 1: 📦 MASTER KARAKTERISTIK (KOLOM) -->
                        <div class="col-lg-4">
                            <div class="card h-100 border shadow-xs rounded-3" id="terpadu_card_kolom" style="border-color: #fdba74 !important; background: #ffffff;">
                                <div class="card-header py-2 px-3 border-bottom d-flex justify-content-between align-items-center" style="background: #fff7ed;">
                                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                        <input class="form-check-input" type="checkbox" id="terpadu_opt_save_kolom" checked style="cursor: pointer; width: 38px; height: 20px;">
                                        <label class="form-check-label fw-bold text-dark text-xs mb-0" for="terpadu_opt_save_kolom" style="cursor: pointer;">
                                            <i class="bi bi-layout-three-columns text-primary-orange me-1"></i> 1. Karakteristik Kolom
                                        </label>
                                    </div>
                                    <span class="badge bg-primary-soft text-primary-orange border text-xxs fw-bold px-2 py-1" id="terpadu_kolom_count_badge">0 Kolom</span>
                                </div>
                                <div class="card-body p-3" id="terpadu_kolom_body">
                                    <!-- Form Nama Kelompok Kolom -->
                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark text-xxs mb-1">
                                            Nama Kelompok Kolom (ID) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="terpadu_nama_kolom_ind" placeholder="Contoh: Jenis Kelamin" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark text-xxs mb-1">
                                            Nama Kelompok Kolom (EN)
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="terpadu_nama_kolom_en" placeholder="Contoh: Sex">
                                    </div>

                                    <!-- Pilihan Format Susunan Kolom -->
                                    <div class="p-2 mb-3 bg-light border rounded-2">
                                        <label class="form-label fw-bold text-dark text-xxs mb-1 d-block">
                                            <i class="bi bi-diagram-3 text-primary me-1"></i> Format Kolom:
                                        </label>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="terpadu_kolom_format" id="terpadu_kolom_fmt_parent" value="parent" checked>
                                            <label class="form-check-label text-xxs fw-bold text-success" for="terpadu_kolom_fmt_parent" style="cursor: pointer;">
                                                Induk Saja (Deduplikasi)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio" name="terpadu_kolom_format" id="terpadu_kolom_fmt_tree" value="tree">
                                            <label class="form-check-label text-xxs fw-bold text-primary" for="terpadu_kolom_fmt_tree" style="cursor: pointer;">
                                                Bab & Sub-Bab
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Preview Ringkas Kolom -->
                                    <label class="form-label fw-bold text-dark text-xxs mb-1 d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-eye text-primary me-1"></i> Preview Item Kolom:</span>
                                    </label>
                                    <div class="border rounded-2 p-2 bg-white" id="terpadu_kolom_preview_list" style="max-height: 180px; overflow-y: auto; font-size: 0.74rem;">
                                        <!-- Item preview kolom dinamis -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL 2: 📑 MASTER JUDUL BARIS (ROW LABELS) -->
                        <div class="col-lg-4">
                            <div class="card h-100 border shadow-xs rounded-3" id="terpadu_card_baris" style="border-color: #93c5fd !important; background: #ffffff;">
                                <div class="card-header py-2 px-3 border-bottom d-flex justify-content-between align-items-center" style="background: #eff6ff;">
                                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                        <input class="form-check-input" type="checkbox" id="terpadu_opt_save_baris" checked style="cursor: pointer; width: 38px; height: 20px;">
                                        <label class="form-check-label fw-bold text-dark text-xs mb-0" for="terpadu_opt_save_baris" style="cursor: pointer;">
                                            <i class="bi bi-list-nested text-primary me-1"></i> 2. Judul Baris
                                        </label>
                                    </div>
                                    <span class="badge bg-primary-soft text-primary border text-xxs fw-bold px-2 py-1" id="terpadu_baris_count_badge">0 Baris</span>
                                </div>
                                <div class="card-body p-3" id="terpadu_baris_body">
                                    <!-- Alert Bantuan Cerdas jika baris tidak dibutuhkan -->
                                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-2 shadow-xs" style="font-size: 0.72rem; border-left: 3px solid #3b82f6 !important;">
                                        <i class="bi bi-lightbulb-fill text-primary fs-5"></i>
                                        <div>
                                            <b>Tips:</b> Uncheck jika baris (seperti Kab/Kota) <b>sudah ada di tabel lain</b>.
                                        </div>
                                    </div>

                                    <!-- Form Nama Kelompok Baris -->
                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark text-xxs mb-1">
                                            Nama Kelompok Baris (ID) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="terpadu_nama_baris_ind" placeholder="Contoh: Kelompok Umur" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark text-xxs mb-1">
                                            Nama Kelompok Baris (EN)
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="terpadu_nama_baris_en" placeholder="Contoh: Age Group">
                                    </div>

                                    <!-- Preview Ringkas Baris -->
                                    <label class="form-label fw-bold text-dark text-xxs mb-1 d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-list-check text-primary me-1"></i> Preview Item Baris:</span>
                                    </label>
                                    <div class="border rounded-2 p-2 bg-white" id="terpadu_baris_preview_list" style="max-height: 180px; overflow-y: auto; font-size: 0.74rem;">
                                        <!-- Item preview baris dinamis -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL 3: 📅 MASTER PERIODE WAKTU (TIME PERIOD) -->
                        <div class="col-lg-4">
                            <div class="card h-100 border shadow-xs rounded-3" id="terpadu_card_periode" style="border-color: #86efac !important; background: #ffffff;">
                                <div class="card-header py-2 px-3 border-bottom d-flex justify-content-between align-items-center" style="background: #f0fdf4;">
                                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                        <input class="form-check-input" type="checkbox" id="terpadu_opt_save_periode" checked style="cursor: pointer; width: 38px; height: 20px;">
                                        <label class="form-check-label fw-bold text-dark text-xs mb-0" for="terpadu_opt_save_periode" style="cursor: pointer;">
                                            <i class="bi bi-calendar3 text-success me-1"></i> 3. Periode Waktu
                                        </label>
                                    </div>
                                    <span class="badge bg-success-soft text-success border text-xxs fw-bold px-2 py-1" id="terpadu_periode_count_badge">0 Periode</span>
                                </div>
                                <div class="card-body p-3" id="terpadu_periode_body">
                                    <!-- Alert Bantuan Cerdas jika periode tidak dibutuhkan -->
                                    <div class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2 mb-2 shadow-xs" style="font-size: 0.72rem; border-left: 3px solid #16a34a !important; background: #f0fdf4;">
                                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                        <div>
                                            <b>Deteksi Otomatis:</b> Tahun / periode waktu otomatis dipilah ke Master Periode Waktu.
                                        </div>
                                    </div>

                                    <!-- Form Nama Kelompok Periode -->
                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark text-xxs mb-1">
                                            Nama Kelompok Periode (ID) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="terpadu_nama_periode_ind" placeholder="Contoh: Tahun 2025-2026" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark text-xxs mb-1">
                                            Nama Kelompok Periode (EN)
                                        </label>
                                        <input type="text" class="form-control form-control-sm" id="terpadu_nama_periode_en" placeholder="Contoh: Year 2025-2026">
                                    </div>

                                    <!-- Preview Ringkas Periode -->
                                    <label class="form-label fw-bold text-dark text-xxs mb-1 d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-clock-history text-success me-1"></i> Preview Item Periode:</span>
                                    </label>
                                    <div class="border rounded-2 p-2 bg-white" id="terpadu_periode_preview_list" style="max-height: 180px; overflow-y: auto; font-size: 0.74rem;">
                                        <!-- Item preview periode dinamis -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: LIVE PREVIEW STRUKTUR TABEL & DATA UTUH -->
                    <div class="card border shadow-xs mt-3 rounded-3" style="border-color: #cbd5e1 !important; background: #ffffff;">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="fw-bold text-dark text-xs d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white rounded-circle" style="width: 20px; height: 20px; padding: 2px;">3</span>
                                <i class="bi bi-table text-primary-orange"></i> LIVE PREVIEW STRUKTUR TABEL & DATA DARI FILE
                            </span>
                            <div class="d-flex align-items-center gap-2 text-xxs">
                                <span class="badge px-2 py-1 border" style="background: #eff6ff; color: #1d4ed8; border-color: #93c5fd !important;">
                                    <i class="bi bi-square-fill me-1" style="color: #2563eb !important;"></i> Judul Baris
                                </span>
                                <span class="badge px-2 py-1 border" style="background: #fff7ed; color: #c2410c; border-color: #fdba74 !important;">
                                    <i class="bi bi-square-fill me-1" style="color: #ea580c !important;"></i> Kolom Karakteristik
                                </span>
                                <span class="badge px-2 py-1 border" style="background: #f0fdf4; color: #15803d; border-color: #86efac !important;" id="terpadu_legend_periode">
                                    <i class="bi bi-square-fill me-1" style="color: #16a34a !important;"></i> Periode Waktu
                                </span>
                                <span class="badge bg-light text-muted border px-2 py-1" id="terpadu_table_meta_badge">0 baris | 0 kolom</span>
                            </div>
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div class="table-responsive bg-white rounded-2 border" style="max-height: 260px; overflow: auto;">
                                <table class="table table-bordered table-sm text-center align-middle mb-0" id="table_preview_terpadu_matrix" style="font-size: 0.74rem;">
                                    <thead id="terpadu_matrix_thead"></thead>
                                    <tbody id="terpadu_matrix_tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top px-4 py-3 bg-white d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold d-inline-flex align-items-center gap-2 shadow-xs" id="btn_terpadu_submit" disabled style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                    <i class="bi bi-rocket-takeoff-fill"></i> Simpan Dimensi Terpilih ke Database
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 7. MODAL ADD PERIODE (DENGAN PRESET TRIWULAN/BULAN/TAHUN & LIVE PREVIEW BILINGUAL) -->
<div class="modal fade" id="modalAddPeriode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4" style="background: #F0FDF4;">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-calendar-plus text-success me-2"></i>Tambah Kelompok Periode (Dimensi Waktu)</h5>
                    <div class="small text-muted">Input periode waktu (Tahunan, Triwulan, Bulanan, Semester) dwibahasa dengan Live Preview otomatis</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/add_periode'); ?>" method="post">
                <div class="modal-body p-4">
                    <!-- Baris 1: Nama Kelompok -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">Nama Kelompok (Bahasa Indonesia) <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kelompok_ind" id="add_periode_nama_ind" class="form-control" placeholder="Contoh: Triwulanan 2025, atau Tahunan 2021-2025" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">Nama Kelompok (English)</label>
                            <input type="text" name="nama_kelompok_en" id="add_periode_nama_en" class="form-control" placeholder="Contoh: Quarterly 2025, atau Annual 2021-2025">
                        </div>
                    </div>

                    <!-- Baris 2: Dynamic List Builder Periode (Tanpa Sub) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark small mb-0">
                                <i class="bi bi-calendar3 text-success me-1"></i> Daftar Periode Waktu
                            </label>
                            <span class="badge bg-success-soft text-success border fw-normal" id="add_periode_count">0 Periode</span>
                        </div>
                        <div class="tree-builder-card">
                            <div class="tree-builder-header d-flex justify-content-between align-items-center" style="background: #f0fdf4; color: #166534;">
                                <div style="flex: 1;"><i class="bi bi-flag-fill text-danger me-1"></i> Periode (Indonesia)</div>
                                <div style="flex: 1; margin-left: 8px;"><i class="bi bi-globe text-primary me-1"></i> Periode (Inggris)</div>
                                <div style="width: 60px;" class="text-center">Aksi</div>
                            </div>
                            <div class="tree-items-list" id="add_periode_items_list"></div>
                            <button type="button" class="btn btn-outline-success btn-sm rounded-3 mt-2 fw-bold d-inline-flex align-items-center gap-1" id="btn_add_periode_root">
                                <i class="bi bi-plus-circle-fill"></i> Tambah Periode Baru
                            </button>
                        </div>
                        <input type="hidden" name="items_text_ind" id="add_periode_text_ind">
                        <input type="hidden" name="items_text_en" id="add_periode_text_en">
                        <input type="hidden" name="items_json" id="add_periode_json">
                        <div class="form-text small text-muted mt-2">
                            <i class="bi bi-info-circle me-1 text-success"></i> Input periode waktu (misal: <code>2025</code>, <code>Triwulan I</code>, atau <code>Januari</code>). Tampil rapi di bawah kolom pada preview.
                        </div>
                    </div>

                    <!-- Baris 3: Live Preview Header Penempatan Periode di Bawah Kolom Karakteristik -->
                    <div class="border rounded-3 p-3 bg-light shadow-inner">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-eye-fill text-success me-1"></i> Live Visual Header Preview (Posisi Periode di Bawah Kolom)</h6>
                                <div class="text-muted" style="font-size: 11px;">Simulasi posisi sub-kolom periode waktu di bawah kolom karakteristik tabel seperti dalam publikasi BPS</div>
                            </div>
                            <span class="badge bg-success-subtle text-success border small px-2 py-1"><i class="bi bi-translate me-1"></i> Dwibahasa (ID / EN)</span>
                        </div>
                        <div class="table-responsive bg-white rounded-2 border" style="min-height: 120px; overflow-x: auto;">
                            <table class="table table-bordered text-center align-middle mb-0" id="table_preview_add_periode">
                                <thead>
                                    <tr><th class="py-4 text-muted fst-italic">Ketik daftar periode di atas untuk melihat preview visual otomatis...</th></tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 rounded-3 fw-bold" style="background: #16A34A; border: none;">Simpan Kelompok Periode</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 8. MODAL EDIT PERIODE -->
<div class="modal fade" id="modalEditPeriode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Kelompok Periode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/edt_periode'); ?>" method="post">
                <input type="hidden" name="id" id="edit_periode_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Kelompok (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kelompok_ind" id="edit_periode_ind" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Kelompok (English)</label>
                        <input type="text" name="nama_kelompok_en" id="edit_periode_en" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 9. MODAL MANAGE PERIODE (DENGAN PRESET TRIWULAN/BULAN/TAHUN & LIVE PREVIEW BILINGUAL) -->
<div class="modal fade" id="modalManagePeriode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4 bg-white">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-dark"><i class="bi bi-calendar-week text-success me-2"></i>Kelola Item Periode: <span id="manage_periode_title"></span></h5>
                    <div class="small text-muted">Pengaturan dimensi waktu dwibahasa (Tahunan, Triwulan, Bulanan) dengan live preview posisi di bawah kolom</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/master_dimensi/sync_periode_items'); ?>" method="post">
                <input type="hidden" name="id_kelompok" id="manage_periode_id">
                <div class="modal-body p-4">
                    <!-- Baris 2: Dynamic List Builder Periode (Tanpa Sub) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark small mb-0">
                                <i class="bi bi-calendar3 text-success me-1"></i> Daftar Periode Waktu
                            </label>
                            <span class="badge bg-success-soft text-success border fw-normal" id="manage_periode_count">0 Periode</span>
                        </div>
                        <div class="tree-builder-card">
                            <div class="tree-builder-header d-flex justify-content-between align-items-center" style="background: #f0fdf4; color: #166534;">
                                <div style="flex: 1;"><i class="bi bi-flag-fill text-danger me-1"></i> Periode (Indonesia)</div>
                                <div style="flex: 1; margin-left: 8px;"><i class="bi bi-globe text-primary me-1"></i> Periode (Inggris)</div>
                                <div style="width: 60px;" class="text-center">Aksi</div>
                            </div>
                            <div class="tree-items-list" id="manage_periode_items_list"></div>
                            <button type="button" class="btn btn-outline-success btn-sm rounded-3 mt-2 fw-bold d-inline-flex align-items-center gap-1" id="btn_manage_periode_root">
                                <i class="bi bi-plus-circle-fill"></i> Tambah Periode Baru
                            </button>
                        </div>
                        <input type="hidden" name="items_text_ind" id="manage_periode_textarea_ind">
                        <input type="hidden" name="items_text_en" id="manage_periode_textarea_en">
                        <input type="hidden" name="items_json" id="manage_periode_json">
                    </div>

                    <!-- Baris 3: Live Preview Header Box -->
                    <div class="border rounded-3 p-3 bg-light shadow-inner">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-eye-fill text-success me-1"></i> Live Visual Header Preview (Posisi Periode di Bawah Kolom)</h6>
                                <div class="text-muted" style="font-size: 11px;">Simulasi posisi sub-kolom periode waktu di bawah kolom karakteristik tabel seperti dalam publikasi BPS</div>
                            </div>
                            <span class="badge bg-success-subtle text-success border small px-2 py-1"><i class="bi bi-translate me-1"></i> Dwibahasa (ID / EN)</span>
                        </div>
                        <div class="table-responsive bg-white rounded-2 border" style="min-height: 120px; overflow-x: auto;">
                            <table class="table table-bordered text-center align-middle mb-0" id="preview_table_periode">
                                <thead>
                                    <tr><th class="py-4 text-muted fst-italic">Memuat preview periode...</th></tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 rounded-3 fw-bold" style="background: #16A34A; border: none;">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Daftar Periode
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT HANDLER (REALTIME BILINGUAL LIVE PREVIEW)                      -->
<!-- ========================================================================= -->
<script>
$(document).ready(function() {

    // Helper: Formatter label berdasarkan mode bahasa (default: both / Dwibahasa)
    function formatLabel(ind, en, mode = 'both') {
        ind = ind || '';
        en  = en || '';
        if (mode === 'en') return en || ind;
        if (mode === 'ind') return ind;
        if (en && en.trim().toLowerCase() !== ind.trim().toLowerCase()) {
            return '<div class="bilingual-label d-inline-block align-middle">' +
                   '  <div class="label-ind lh-sm text-dark">' + ind + '</div>' +
                   '  <div class="label-en text-secondary small fst-italic lh-sm mt-1" style="font-weight: normal; font-size: 0.85em;">' + en + '</div>' +
                   '</div>';
        }
        return ind;
    }

    // Helper: Formatter label khusus Baris (Bahasa Inggris diletakkan di samping memakai '/')
    function formatLabelBaris(ind, en, mode = 'both') {
        ind = ind || '';
        en  = en || '';
        if (mode === 'en') return escapeHtml(en || ind);
        if (mode === 'ind') return escapeHtml(ind);
        if (en && en.trim().toLowerCase() !== ind.trim().toLowerCase()) {
            return '<span class="text-dark">' + escapeHtml(ind) + '</span> ' +
                   '<span class="text-secondary small fst-italic" style="font-size: 0.88em; font-weight: normal;">/ ' + escapeHtml(en) + '</span>';
        }
        return escapeHtml(ind);
    }

    // Helper Penomoran Otomatis
    function toRoman(num) {
        const romanMap = [
            [1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'],
            [100, 'C'], [90, 'XC'], [50, 'L'], [40, 'XL'],
            [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I']
        ];
        let res = '';
        for (let [val, sym] of romanMap) {
            while (num >= val) {
                res += sym;
                num -= val;
            }
        }
        return res || String(num);
    }

    function toLetter(num, isUpper = true) {
        let str = '';
        while (num > 0) {
            let rem = (num - 1) % 26;
            str = String.fromCharCode(65 + rem) + str;
            num = Math.floor((num - 1) / 26);
        }
        return isUpper ? str : str.toLowerCase();
    }

    function getPrefixString(formatType, n) {
        switch (formatType) {
            case 'angka':
                return n + '. ';
            case 'angka_kurung':
                return n + ') ';
            case 'huruf_kapital':
                return toLetter(n, true) + '. ';
            case 'huruf_kecil':
                return toLetter(n, false) + '. ';
            case 'huruf_kurung':
                return toLetter(n, false) + ') ';
            case 'romawi_kapital':
                return toRoman(n) + '. ';
            case 'romawi_kecil':
                return toRoman(n).toLowerCase() + '. ';
            case 'strip':
                return '– ';
            case 'none':
            default:
                return '';
        }
    }

    function stripExistingPrefix(str) {
        if (!str) return '';
        return str.replace(/^(\(?[A-Za-z0-9]+\)[\.\)]?|[A-Za-z0-9]+[\.\)]|[IVXLCDMivxlcdm]+[\.\)]|[–\-\•])\s+/, '').trim();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // =========================================================================
    // DYNAMIC TREE DIMENSION MANAGER (GAMBAR 1 + GAMBAR 2)
    // =========================================================================
    class TreeDimensionManager {
        constructor(cfg) {
            this.listEl       = $(cfg.listId);
            this.addRootBtn   = $(cfg.addRootBtnId);
            this.hiddenInd    = $(cfg.hiddenIndId);
            this.hiddenEn     = $(cfg.hiddenEnId);
            this.hiddenJson   = $(cfg.hiddenJsonId);
            this.countBadge   = $(cfg.countBadgeId);
            this.theme        = cfg.theme || '';
            this.allowSub     = cfg.allowSub === true;
            this.maxDepth     = cfg.maxDepth !== undefined ? cfg.maxDepth : (this.allowSub ? 2 : 0);
            this.type         = cfg.type || 'kolom';
            this.itemLabel    = cfg.itemLabel || 'Item';
            this.onChange     = cfg.onChange || function() {};
            this.items        = [];
            this.idSeq        = 0;

            const self = this;
            this.addRootBtn.on('click', function() {
                self.addItem(null, 0);
            });
        }

        addItem(parentId = null, level = 0, ind = '', en = '') {
            const item = {
                id: 'node_' + (++this.idSeq),
                parentId: parentId,
                level: level,
                ind: ind,
                en: en
            };

            if (parentId === null) {
                this.items.push(item);
            } else {
                let insertIdx = -1;
                for (let i = 0; i < this.items.length; i++) {
                    if (this.items[i].id === parentId) {
                        insertIdx = i;
                        break;
                    }
                }
                if (insertIdx !== -1) {
                    let nextIdx = insertIdx + 1;
                    while (nextIdx < this.items.length && this.items[nextIdx].level > level - 1) {
                        nextIdx++;
                    }
                    this.items.splice(nextIdx, 0, item);
                } else {
                    this.items.push(item);
                }
            }

            this.render();
            this.notify();
            this.listEl.find(`[data-id="${item.id}"] .item-input-ind`).focus();
            return item;
        }

        removeItem(itemId) {
            const idsToRemove = new Set([itemId]);
            let changed = true;
            while (changed) {
                changed = false;
                this.items.forEach(it => {
                    if (it.parentId && idsToRemove.has(it.parentId) && !idsToRemove.has(it.id)) {
                        idsToRemove.add(it.id);
                        changed = true;
                    }
                });
            }
            this.items = this.items.filter(it => !idsToRemove.has(it.id));
            this.render();
            this.notify();
        }

        setItems(items) {
            this.items = items || [];
            this.idSeq = this.items.length;
            this.render();
            this.notify();
        }

        render() {
            const self = this;
            this.listEl.empty();

            if (this.items.length === 0) {
                this.listEl.html('<div class="text-center py-4 text-muted fst-italic small"><i class="bi bi-inbox d-block mb-1 fs-5 opacity-50"></i>Belum ada item. Klik <strong>+ Tambah Item Baru</strong> di bawah.</div>');
                return;
            }

            this.items.forEach(function(item) {
                let placeholderInd = 'Indonesia';
                let placeholderEn  = 'English (opsional)';
                let levelBadge = '';

                if (self.allowSub) {
                    if (item.level === 0) {
                        placeholderInd = 'Bab (Indonesia)';
                        placeholderEn  = 'Bab (English)';
                        levelBadge = `<span class="badge bg-light text-primary border small" style="font-size: 0.72rem; min-width: 46px; text-align: center;">Bab</span>`;
                    } else if (item.level === 1) {
                        placeholderInd = 'Sub Bab (Indonesia)';
                        placeholderEn  = 'Sub Bab (English)';
                        levelBadge = `<span class="badge bg-light text-info border small" style="font-size: 0.72rem; min-width: 46px; text-align: center;">Sub Bab</span>`;
                    } else if (item.level === 2) {
                        placeholderInd = 'Sub Sub Bab (Indonesia)';
                        placeholderEn  = 'Sub Sub Bab (English)';
                        levelBadge = `<span class="badge bg-light text-success border small" style="font-size: 0.72rem; min-width: 46px; text-align: center;">Sub-Sub</span>`;
                    }
                }

                const row = $(`
                    <div class="tree-item-row level-${item.level} ${self.theme}" data-id="${item.id}">
                        <div class="d-flex align-items-center gap-2">
                            ${levelBadge ? `<div>${levelBadge}</div>` : ''}
                            <div class="tree-item-inputs">
                                <input type="text" class="form-control form-control-sm item-input-ind" placeholder="${placeholderInd}" value="${escapeHtml(item.ind)}">
                                <input type="text" class="form-control form-control-sm item-input-en" placeholder="${placeholderEn}" value="${escapeHtml(item.en)}">
                            </div>
                            <div class="tree-item-actions">
                                ${(self.allowSub && item.level < self.maxDepth) ? `<button type="button" class="btn btn-sm btn-sub-badge btn-add-sub" title="Tambah Sub"><i class="bi bi-plus-lg"></i> Sub</button>` : ''}
                                <button type="button" class="btn btn-sm btn-delete-item btn-remove" title="Hapus"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>
                `);

                row.find('.item-input-ind').on('input', function() {
                    item.ind = $(this).val();
                    self.notify();
                });

                row.find('.item-input-en').on('input', function() {
                    item.en = $(this).val();
                    self.notify();
                });

                row.find('.btn-add-sub').on('click', function() {
                    self.addItem(item.id, item.level + 1);
                });

                row.find('.btn-remove').on('click', function() {
                    self.removeItem(item.id);
                });

                self.listEl.append(row);
            });
        }

        notify() {
            const linesInd = [];
            const linesEn  = [];

            if (this.type === 'kolom') {
                const hasChildren = new Set();
                this.items.forEach(it => {
                    if (it.parentId) hasChildren.add(it.parentId);
                });

                const itemMap = {};
                this.items.forEach(it => { itemMap[it.id] = it; });

                this.items.forEach(it => {
                    if (hasChildren.has(it.id)) {
                        return; // Induk kolom yang memiliki sub-kolom bukan leaf kolom
                    }
                    const chain = [it];
                    let curr = it;
                    while (curr.parentId && itemMap[curr.parentId]) {
                        curr = itemMap[curr.parentId];
                        chain.unshift(curr);
                    }
                    linesInd.push(chain.map(x => x.ind).join(' || '));
                    linesEn.push(chain.map(x => (x.en || x.ind)).join(' || '));
                });
            } else if (this.type === 'baris') {
                this.items.forEach(it => {
                    let prefix = '';
                    if (it.level === 2) prefix = '---- ';
                    else if (it.level === 1) prefix = '-- ';
                    linesInd.push(prefix + it.ind);
                    linesEn.push(prefix + (it.en || it.ind));
                });
            } else { // periode
                this.items.forEach(it => {
                    linesInd.push(it.ind);
                    linesEn.push(it.en || it.ind);
                });
            }

            const textInd = linesInd.join('\n');
            const textEn  = linesEn.join('\n');
            this.hiddenInd.val(textInd);
            this.hiddenEn.val(textEn);
            this.hiddenJson.val(JSON.stringify(this.items));

            const count = (this.type === 'kolom') ? linesInd.length : this.items.length;
            if (this.countBadge) {
                this.countBadge.text(count + ' ' + this.itemLabel);
            }

            this.onChange(textInd, textEn, this.items);
        }

        applyAutoPrefix(rules) {
            let babCounter = 0;
            let subbabCounter = 0;
            let subsubCounter = 0;

            this.items.forEach((item) => {
                let cleanInd = stripExistingPrefix(item.ind);
                let cleanEn  = stripExistingPrefix(item.en || item.ind);

                let lower = cleanInd.toLowerCase();
                let isAggregate = (lower.includes('total') || lower.includes('jumlah') || lower === 'jawa tengah');

                let prefix = '';

                if (item.level === 0) {
                    subbabCounter = 0;
                    subsubCounter = 0;
                    if (!isAggregate && rules.bab && rules.bab !== 'none') {
                        babCounter++;
                        prefix = getPrefixString(rules.bab, babCounter);
                    }
                } else if (item.level === 1) {
                    subsubCounter = 0;
                    if (!isAggregate && rules.subbab && rules.subbab !== 'none') {
                        subbabCounter++;
                        prefix = getPrefixString(rules.subbab, subbabCounter);
                    }
                } else if (item.level === 2) {
                    if (!isAggregate && rules.subsub && rules.subsub !== 'none') {
                        subsubCounter++;
                        prefix = getPrefixString(rules.subsub, subsubCounter);
                    }
                }

                item.ind = prefix + cleanInd;
                if (item.en) {
                    item.en = prefix + cleanEn;
                }
            });

            this.render();
            this.notify();
        }

        cleanAllPrefix() {
            this.applyAutoPrefix({ bab: 'none', subbab: 'none', subsub: 'none' });
        }
    }

    // -------------------------------------------------------------------------
    // 1. ENGINE PREVIEW KARAKTERISTIK (KOLOM BERTINGKAT / ASIMETRIS HINGGA 3 LEVEL)
    // -------------------------------------------------------------------------
    function parseKolomLinesToStructure(textInd, textEn) {
        const linesInd = (textInd || '').split(/\r?\n/).filter(l => l.trim() !== '');
        const linesEn  = (textEn || '').split(/\r?\n/).filter(l => l.trim() !== '');

        const root = { children: {}, isRoot: true };
        let maxDepth = 1;

        linesInd.forEach(function(lInd, idx) {
            lInd = lInd.trim();
            if (!lInd) return;

            let lEn = linesEn[idx] ? linesEn[idx].trim() : '';
            if (lInd.includes('//') && !lEn) {
                const partsLang = lInd.split('//').map(s => s.trim());
                lInd = partsLang[0];
                lEn  = partsLang[1] || '';
            }

            const partsInd = lInd.split('||').map(s => s.trim()).filter(Boolean);
            const partsEn  = lEn ? lEn.split('||').map(s => s.trim()).filter(Boolean) : [];

            if (partsInd.length > maxDepth) {
                maxDepth = partsInd.length;
            }

            let curr = root;
            partsInd.forEach(function(part, pIdx) {
                let enPart = partsEn[pIdx] || part;
                if (!curr.children[part]) {
                    curr.children[part] = {
                        title_ind: part,
                        title_en: enPart,
                        level: pIdx,
                        children: {}
                    };
                }
                curr = curr.children[part];
            });
        });

        return {
            root: root,
            maxDepth: Math.min(3, Math.max(1, maxDepth)),
            hasItems: linesInd.length > 0
        };
    }

    function countKolomLeaves(node) {
        const keys = Object.keys(node.children || {});
        if (keys.length === 0) return 1;
        let total = 0;
        keys.forEach(k => {
            total += countKolomLeaves(node.children[k]);
        });
        return total;
    }

    function renderKolomToTable($table, structure, langMode) {
        $table.empty();

        if (!structure || !structure.hasItems) {
            $table.html('<thead><tr><th class="py-4 text-muted fst-italic">Belum ada kolom yang didefinisikan</th></tr></thead>');
            return;
        }

        const thead = $('<thead></thead>');
        const barisTitle = formatLabel('Baris', 'Row', langMode);
        const maxDepth = structure.maxDepth;

        const tr1 = $('<tr style="background: #f8fafc; font-weight: bold; border-bottom: 1px solid #cbd5e1;"></tr>');
        const tr2 = $('<tr style="background: #f1f5f9; font-weight: bold; border-bottom: 1px solid #cbd5e1;"></tr>');
        const tr3 = $('<tr style="background: #e2e8f0; font-weight: bold; border-bottom: 2px solid #cbd5e1;"></tr>');

        // Header kolom Baris mencakup seluruh tingkatan (rowspan = maxDepth)
        tr1.append('<th rowspan="' + maxDepth + '" style="width: 140px; background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 10px;">' + barisTitle + '</th>');

        const rootChildren = Object.values(structure.root.children || {});

        rootChildren.forEach(function(l0) {
            const l0Leaves = countKolomLeaves(l0);
            const l0HasChildren = Object.keys(l0.children || {}).length > 0;
            const l0Label = formatLabel(l0.title_ind, l0.title_en, langMode);

            if (!l0HasChildren) {
                tr1.append('<th rowspan="' + maxDepth + '" style="background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 8px 12px;">' + l0Label + '</th>');
            } else {
                tr1.append('<th colspan="' + l0Leaves + '" style="background: #e2e8f0; border: 1px solid #cbd5e1; padding: 8px 12px; color: #1e293b;">' + l0Label + '</th>');

                Object.values(l0.children).forEach(function(l1) {
                    const l1Leaves = countKolomLeaves(l1);
                    const l1HasChildren = Object.keys(l1.children || {}).length > 0;
                    const l1Label = formatLabel(l1.title_ind, l1.title_en, langMode);

                    if (!l1HasChildren) {
                        const span = maxDepth - 1;
                        tr2.append('<th rowspan="' + span + '" style="background: #f1f5f9; border: 1px solid #cbd5e1; vertical-align: middle; padding: 6px 10px; font-size: 13px;">' + l1Label + '</th>');
                    } else {
                        tr2.append('<th colspan="' + l1Leaves + '" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 13px;">' + l1Label + '</th>');

                        Object.values(l1.children).forEach(function(l2) {
                            const l2Label = formatLabel(l2.title_ind, l2.title_en, langMode);
                            tr3.append('<th style="background: #e2e8f0; border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 12px;">' + l2Label + '</th>');
                        });
                    }
                });
            }
        });

        thead.append(tr1);
        if (maxDepth >= 2) thead.append(tr2);
        if (maxDepth >= 3) thead.append(tr3);

        $table.append(thead);
    }

    // Instansiasi Builder Kolom
    const addKolomBuilder = new TreeDimensionManager({
        containerId: '#add_kolom_builder',
        listId: '#add_kolom_items_list',
        addRootBtnId: '#btn_add_kolom_root',
        hiddenIndId: '#add_kolom_text_ind',
        hiddenEnId: '#add_kolom_text_en',
        hiddenJsonId: '#add_kolom_json',
        countBadgeId: '#add_kolom_count',
        theme: '',
        allowSub: true,
        maxDepth: 2,
        type: 'kolom',
        itemLabel: 'Kolom',
        onChange: function(textInd, textEn) {
            const struct = parseKolomLinesToStructure(textInd, textEn);
            renderKolomToTable($('#table_preview_add_kolom'), struct, 'both');
        }
    });

    // Default 4 kolom kosong untuk Add Kolom
    for (let i = 0; i < 4; i++) {
        addKolomBuilder.addItem(null, 0, '', '');
    }

    const manageKolomBuilder = new TreeDimensionManager({
        containerId: '#manage_kolom_builder',
        listId: '#manage_kolom_items_list',
        addRootBtnId: '#btn_manage_kolom_root',
        hiddenIndId: '#manage_kolom_textarea_ind',
        hiddenEnId: '#manage_kolom_textarea_en',
        hiddenJsonId: '#manage_kolom_json',
        countBadgeId: '#manage_kolom_count',
        theme: '',
        allowSub: true,
        maxDepth: 2,
        type: 'kolom',
        itemLabel: 'Kolom',
        onChange: function(textInd, textEn) {
            const struct = parseKolomLinesToStructure(textInd, textEn);
            renderKolomToTable($('#preview_table_kolom'), struct, 'both');
        }
    });

    // Modal Edit Karakteristik
    $('.btn-edit-kolom').on('click', function() {
        $('#edit_kolom_id').val($(this).data('id'));
        $('#edit_kolom_ind').val($(this).data('ind'));
        $('#edit_kolom_en').val($(this).data('en'));
        $('#modalEditKarakteristik').modal('show');
    });

    // Modal Manage Karakteristik
    $('.btn-manage-kolom').on('click', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        $('#manage_kolom_id').val(id);
        $('#manage_kolom_title').text(nama);

        $.getJSON('<?= base_url('admin/master_dimensi/get_karakteristik_items/'); ?>/' + id, function(res) {
            if (res.status === 'success') {
                const treeItems = [];
                const parentMap = {};
                let idSeq = 0;

                res.items.forEach(function(it) {
                    const isStandalone = parseInt(it.is_standalone, 10) === 1;
                    const pInd = (it.grup_induk_ind && !isStandalone) ? it.grup_induk_ind.trim() : null;
                    const pEn  = (it.grup_induk_en && !isStandalone) ? it.grup_induk_en.trim() : pInd;

                    if (pInd) {
                        const partsInd = pInd.split('||').map(s => s.trim()).filter(Boolean);
                        const partsEn  = pEn ? pEn.split('||').map(s => s.trim()).filter(Boolean) : [];

                        let parentId = null;
                        let pathKey = '';

                        partsInd.forEach(function(part, pIdx) {
                            pathKey = pathKey ? (pathKey + ' > ' + part) : part;
                            let enPart = partsEn[pIdx] || part;
                            if (!parentMap[pathKey]) {
                                const node = {
                                    id: 'node_' + (++idSeq),
                                    parentId: parentId,
                                    level: pIdx,
                                    ind: part,
                                    en: enPart
                                };
                                parentMap[pathKey] = node;
                                treeItems.push(node);
                            }
                            parentId = parentMap[pathKey].id;
                        });

                        const childNode = {
                            id: 'node_' + (++idSeq),
                            parentId: parentId,
                            level: partsInd.length,
                            ind: it.item_ind,
                            en: it.item_en || it.item_ind
                        };
                        treeItems.push(childNode);
                    } else {
                        const standaloneNode = {
                            id: 'node_' + (++idSeq),
                            parentId: null,
                            level: 0,
                            ind: it.item_ind,
                            en: it.item_en || it.item_ind
                        };
                        treeItems.push(standaloneNode);
                    }
                });

                manageKolomBuilder.setItems(treeItems);
                $('#modalManageKolom').modal('show');
            }
        });
    });


    // -------------------------------------------------------------------------
    // 2. ENGINE PREVIEW JUDUL BARIS BERTINGKAT (HIERARKI & BILINGUAL)
    // -------------------------------------------------------------------------
    function parseBarisLines(textInd, textEn) {
        const linesInd = (textInd || '').split(/\r?\n/).filter(l => l.trim() !== '');
        const linesEn  = (textEn || '').split(/\r?\n/).filter(l => l.trim() !== '');
        let items = [];

        linesInd.forEach(function(lInd, idx) {
            lInd = lInd.trim();
            if (!lInd) return;

            let lEn = linesEn[idx] ? linesEn[idx].trim() : '';
            if (lInd.includes('//') && !lEn) {
                const partsLang = lInd.split('//').map(s => s.trim());
                lInd = partsLang[0];
                lEn  = partsLang[1] || '';
            }

            let indent = 0;
            let namaInd = lInd;
            let namaEn  = lEn;

            if (lInd.startsWith('----')) {
                indent = 2;
                namaInd = lInd.substring(4).trim();
            } else if (lInd.startsWith('--')) {
                indent = 1;
                namaInd = lInd.substring(2).trim();
            }

            if (namaEn.startsWith('----')) {
                namaEn = namaEn.substring(4).trim();
            } else if (namaEn.startsWith('--')) {
                namaEn = namaEn.substring(2).trim();
            }

            let tipe = 'data';
            let lower = namaInd.toLowerCase();
            if (lower.includes('subtotal') || lower.includes('sub total')) tipe = 'subtotal';
            else if (lower.includes('total') || lower.includes('jumlah')) tipe = 'total';
            else if (indent === 0 && !lower.includes('kabupaten') && !lower.includes('kota')) tipe = 'kategori';

            items.push({
                no_urut: idx + 1,
                nama_baris_ind: namaInd,
                nama_baris_en: namaEn || namaInd,
                level_indentasi: indent,
                tipe_baris: tipe,
                is_bold: (tipe === 'total' || tipe === 'kategori' || tipe === 'subtotal') ? 1 : 0
            });
        });

        return items;
    }

    function renderBarisToTable($tbody, items, langMode) {
        $tbody.empty();

        if (!items || items.length === 0) {
            $tbody.html('<tr><td colspan="3" class="text-center py-4 text-muted fst-italic">Belum ada baris yang didefinisikan</td></tr>');
            return;
        }

        items.forEach(function(item, idx) {
            let indentPx = parseInt(item.level_indentasi) * 20;
            let isBold = parseInt(item.is_bold) === 1 || item.tipe_baris === 'total' || item.tipe_baris === 'kategori';
            let style = isBold ? 'font-weight: bold;' : '';
            let bg = '';
            let badgeClass = 'bg-light text-secondary border';
            let badgeText = 'Data';

            if (item.tipe_baris === 'kategori') {
                bg = 'background-color: #f8fafc;';
                badgeClass = 'bg-primary-soft text-primary border border-primary-subtle';
                badgeText = 'Bab (L0)';
            } else if (item.tipe_baris === 'total') {
                bg = 'background-color: #fef3c7; border-top: 2px solid #d97706; border-bottom: 3px double #d97706;';
                badgeClass = 'bg-warning text-dark';
                badgeText = 'Grand Total';
            } else if (item.tipe_baris === 'subtotal') {
                bg = 'background-color: #f1f5f9; border-top: 1px solid #cbd5e1;';
                badgeClass = 'bg-secondary text-white';
                badgeText = 'Subtotal';
            } else {
                if (item.level_indentasi === 1) {
                    badgeText = 'Sub Bab (L1)';
                    badgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                } else if (item.level_indentasi === 2) {
                    badgeText = 'Sub Sub Bab (L2)';
                    badgeClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                } else {
                    badgeText = 'Data (L0)';
                    badgeClass = 'bg-light text-secondary border';
                }
            }

            let label = formatLabelBaris(item.nama_baris_ind, item.nama_baris_en, langMode);

            let tr = $('<tr style="' + bg + '"></tr>');
            tr.append('<td class="text-center text-muted small">' + (idx + 1) + '</td>');
            tr.append('<td style="' + style + ' padding-left: ' + (15 + indentPx) + 'px;">' + (item.level_indentasi > 0 ? '<i class="bi bi-arrow-return-right me-1 text-muted small"></i>' : '') + label + '</td>');
            tr.append('<td class="text-center"><span class="badge ' + badgeClass + ' rounded-pill small">' + badgeText + '</span></td>');
            $tbody.append(tr);
        });
    }

    // Instansiasi Builder Baris
    const addBarisBuilder = new TreeDimensionManager({
        containerId: '#add_baris_builder',
        listId: '#add_baris_items_list',
        addRootBtnId: '#btn_add_baris_root',
        hiddenIndId: '#add_baris_text_ind',
        hiddenEnId: '#add_baris_text_en',
        hiddenJsonId: '#add_baris_json',
        countBadgeId: '#add_baris_count',
        theme: 'theme-baris',
        allowSub: true,
        maxDepth: 2,
        type: 'baris',
        itemLabel: 'Baris',
        onChange: function(textInd, textEn) {
            const items = parseBarisLines(textInd, textEn);
            renderBarisToTable($('#table_preview_add_baris tbody'), items, 'both');
        }
    });

    // Default 4 baris kosong untuk Add Baris
    for (let i = 0; i < 4; i++) {
        addBarisBuilder.addItem(null, 0, '', '');
    }

    const manageBarisBuilder = new TreeDimensionManager({
        containerId: '#manage_baris_builder',
        listId: '#manage_baris_items_list',
        addRootBtnId: '#btn_manage_baris_root',
        hiddenIndId: '#manage_baris_textarea_ind',
        hiddenEnId: '#manage_baris_textarea_en',
        hiddenJsonId: '#manage_baris_json',
        countBadgeId: '#manage_baris_count',
        theme: 'theme-baris',
        allowSub: true,
        maxDepth: 2,
        type: 'baris',
        itemLabel: 'Baris',
        onChange: function(textInd, textEn) {
            const items = parseBarisLines(textInd, textEn);
            renderBarisToTable($('#preview_table_baris_tbody'), items, 'both');
        }
    });

    // Event Handler Tombol Penomoran Otomatis Baris (Add Baris & Manage Baris)
    $('#btn_add_apply_prefix').on('click', function() {
        addBarisBuilder.applyAutoPrefix({
            bab: $('#add_prefix_bab').val(),
            subbab: $('#add_prefix_subbab').val(),
            subsub: $('#add_prefix_subsub').val()
        });
    });

    $('#btn_add_clean_prefix').on('click', function() {
        addBarisBuilder.cleanAllPrefix();
    });

    $('#btn_manage_apply_prefix').on('click', function() {
        manageBarisBuilder.applyAutoPrefix({
            bab: $('#manage_prefix_bab').val(),
            subbab: $('#manage_prefix_subbab').val(),
            subsub: $('#manage_prefix_subsub').val()
        });
    });

    $('#btn_manage_clean_prefix').on('click', function() {
        manageBarisBuilder.cleanAllPrefix();
    });

    // -------------------------------------------------------------------------
    // FITUR CERDAS: BULK TEXT PARSER & PRESET TEMPLATE (MEMPERMUDAH INPUT BARIS BANYAK)
    // -------------------------------------------------------------------------
    function parseRawLinesToTreeItems(textRaw) {
        if (!textRaw) return [];
        const lines = textRaw.split(/\r?\n/).filter(l => l.trim() !== '');
        const treeItems = [];
        const levelParents = {};
        let idSeq = 0;

        lines.forEach(function(line) {
            let trimmed = line.trim();
            if (!trimmed) return;

            let ind = trimmed;
            let en = '';

            // Format dwibahasa dengan separator //
            if (trimmed.includes('//')) {
                const parts = trimmed.split('//').map(s => s.trim());
                ind = parts[0];
                en  = parts[1] || '';
            }

            let lvl = 0;
            if (ind.startsWith('----')) {
                lvl = 2;
                ind = ind.substring(4).trim();
            } else if (ind.startsWith('--')) {
                lvl = 1;
                ind = ind.substring(2).trim();
            } else {
                const matchSpace = line.match(/^(\s+)/);
                if (matchSpace) {
                    const spaceCount = matchSpace[1].length;
                    lvl = spaceCount >= 4 ? 2 : 1;
                }
            }

            if (en.startsWith('----')) en = en.substring(4).trim();
            else if (en.startsWith('--')) en = en.substring(2).trim();

            const node = {
                id: 'node_' + (++idSeq),
                parentId: lvl > 0 ? (levelParents[lvl - 1] ? levelParents[lvl - 1].id : null) : null,
                level: lvl,
                ind: ind,
                en: en || ind
            };

            levelParents[lvl] = node;
            treeItems.push(node);
        });

        return treeItems;
    }

    function getPresetUnsurIklim() {
        return `Stasiun Klimatologi Semarang // Semarang Climatological Station
-- Suhu // Temperature
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kelembaban (%) // Humidity (%)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kecepatan Angin (km/jam) // Wind Velocity (km/h)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Tekanan Udara (mb) // Atmospheric Pressure (mb)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Jumlah Curah Hujan (mm) // Number of Precipitation (mm)
-- Jumlah Hari Hujan (hari) // Number of Rainy Days (day)
-- Penyinaran Matahari (%) // Duration of Sunshine (%)
Stasiun Klimatologi Cilacap // Cilacap Climatological Station
-- Suhu // Temperature
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kelembaban (%) // Humidity (%)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kecepatan Angin (km/jam) // Wind Velocity (km/h)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Tekanan Udara (mb) // Atmospheric Pressure (mb)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Jumlah Curah Hujan (mm) // Number of Precipitation (mm)
-- Jumlah Hari Hujan (hari) // Number of Rainy Days (day)
-- Penyinaran Matahari (%) // Duration of Sunshine (%)
Stasiun Klimatologi Tegal // Tegal Climatological Station
-- Suhu // Temperature
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kelembaban (%) // Humidity (%)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kecepatan Angin (km/jam) // Wind Velocity (km/h)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Tekanan Udara (mb) // Atmospheric Pressure (mb)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Jumlah Curah Hujan (mm) // Number of Precipitation (mm)
-- Jumlah Hari Hujan (hari) // Number of Rainy Days (day)
-- Penyinaran Matahari (%) // Duration of Sunshine (%)
Stasiun Klimatologi Banjarnegara // Banjarnegara Climatological Station
-- Suhu // Temperature
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kelembaban (%) // Humidity (%)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Kecepatan Angin (m/det) // Wind Velocity (m/sec)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Tekanan Udara (mb) // Atmospheric Pressure (mb)
---- Minimum // Minimum
---- Rata-Rata // Average
---- Maksimum // Maximum
-- Jumlah Curah Hujan (mm) // Number of Precipitation (mm)
-- Jumlah Hari Hujan (hari) // Number of Rainy Days (day)
-- Penyinaran Matahari (%) // Duration of Sunshine (%)`;
    }

    function getPresetKabupatenKota() {
        return `Cilacap // Cilacap
Banyumas // Banyumas
Purbalingga // Purbalingga
Banjarnegara // Banjarnegara
Kebumen // Kebumen
Purworejo // Purworejo
Wonosobo // Wonosobo
Magelang // Magelang
Boyolali // Boyolali
Klaten // Klaten
Sukoharjo // Sukoharjo
Wonogiri // Wonogiri
Karanganyar // Karanganyar
Sragen // Sragen
Grobogan // Grobogan
Blora // Blora
Rembang // Rembang
Pati // Pati
Kudus // Kudus
Jepara // Jepara
Demak // Demak
Semarang // Semarang
Temanggung // Temanggung
Kendal // Kendal
Batang // Batang
Pekalongan // Pekalongan
Pemalang // Pemalang
Tegal // Tegal
Brebes // Brebes
Kota Magelang // Magelang Municipality
Kota Surakarta // Surakarta Municipality
Kota Salatiga // Salatiga Municipality
Kota Semarang // Semarang Municipality
Kota Pekalongan // Pekalongan Municipality
Kota Tegal // Tegal Municipality
Jawa Tengah // Central Java`;
    }

    function exportTreeToRawText(builder) {
        if (!builder || !builder.items) return '';
        const lines = [];
        builder.items.forEach(it => {
            let pfx = '';
            if (it.level === 2) pfx = '---- ';
            else if (it.level === 1) pfx = '-- ';
            let ind = it.ind || '';
            let en = it.en || '';
            if (en && en !== ind) {
                lines.push(pfx + ind + ' // ' + en);
            } else {
                lines.push(pfx + ind);
            }
        });
        return lines.join('\n');
    }

    // Toggle Box Bulk Add Baris
    $('#btn_toggle_add_bulk').on('click', function() {
        $('#box_add_bulk').slideToggle(200);
        $('#textarea_add_bulk').focus();
    });
    $('#btn_close_add_bulk, #btn_cancel_add_bulk').on('click', function() {
        $('#box_add_bulk').slideUp(200);
    });
    $('#btn_clear_add_bulk').on('click', function() {
        $('#textarea_add_bulk').val('').focus();
    });

    // Apply Bulk ke Add Baris Builder
    $('#btn_apply_add_bulk').on('click', function() {
        const raw = $('#textarea_add_bulk').val();
        if (!raw || !raw.trim()) {
            alert('Teks masih kosong. Silakan paste daftar baris terlebih dahulu.');
            return;
        }
        const items = parseRawLinesToTreeItems(raw);
        if (items.length > 0) {
            addBarisBuilder.setItems(items);
            $('#box_add_bulk').slideUp(200);
        } else {
            alert('Format teks tidak valid.');
        }
    });

    // Salin Tree Saat Ini ke Textarea Bulk Add
    $('#btn_copy_current_add_baris').on('click', function() {
        const text = exportTreeToRawText(addBarisBuilder);
        $('#textarea_add_bulk').val(text);
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
            alert('Daftar baris berhasil disalin ke clipboard dan dimuat ke textarea!');
        }
    });

    // Template Presets Add Baris
    $('#preset_add_iklim').on('click', function() {
        $('#add_baris_nama_ind').val('Unsur Iklim Menurut Stasiun Klimatologi');
        $('input[name="nama_kelompok_en"]').val('Climate Elements by Climatological Station');
        const items = parseRawLinesToTreeItems(getPresetUnsurIklim());
        addBarisBuilder.setItems(items);
        $('#textarea_add_bulk').val(getPresetUnsurIklim());
        $('#box_add_bulk').slideUp(200);
    });

    $('#preset_add_kabkot').on('click', function() {
        $('#add_baris_nama_ind').val('35 Kabupaten/Kota Jawa Tengah');
        $('input[name="nama_kelompok_en"]').val('35 Regencies/Cities in Central Java');
        const items = parseRawLinesToTreeItems(getPresetKabupatenKota());
        addBarisBuilder.setItems(items);
        $('#textarea_add_bulk').val(getPresetKabupatenKota());
        $('#box_add_bulk').slideUp(200);
    });

    // Manage Baris Handlers
    $('#btn_toggle_manage_bulk').on('click', function() {
        $('#box_manage_bulk').slideToggle(200);
        if (!$('#textarea_manage_bulk').val()) {
            $('#textarea_manage_bulk').val(exportTreeToRawText(manageBarisBuilder));
        }
        $('#textarea_manage_bulk').focus();
    });
    $('#btn_close_manage_bulk, #btn_cancel_manage_bulk').on('click', function() {
        $('#box_manage_bulk').slideUp(200);
    });
    $('#btn_apply_manage_bulk').on('click', function() {
        const raw = $('#textarea_manage_bulk').val();
        if (!raw || !raw.trim()) {
            alert('Teks masih kosong.');
            return;
        }
        const items = parseRawLinesToTreeItems(raw);
        if (items.length > 0) {
            manageBarisBuilder.setItems(items);
            $('#box_manage_bulk').slideUp(200);
        }
    });
    $('#btn_copy_current_manage_baris').on('click', function() {
        const text = exportTreeToRawText(manageBarisBuilder);
        $('#textarea_manage_bulk').val(text);
        $('#box_manage_bulk').slideDown(200);
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
            alert('Daftar baris berhasil disalin ke clipboard!');
        }
    });
    $('#preset_manage_iklim').on('click', function() {
        const items = parseRawLinesToTreeItems(getPresetUnsurIklim());
        manageBarisBuilder.setItems(items);
        $('#textarea_manage_bulk').val(getPresetUnsurIklim());
        $('#box_manage_bulk').slideUp(200);
    });
    $('#preset_manage_kabkot').on('click', function() {
        const items = parseRawLinesToTreeItems(getPresetKabupatenKota());
        manageBarisBuilder.setItems(items);
        $('#textarea_manage_bulk').val(getPresetKabupatenKota());
        $('#box_manage_bulk').slideUp(200);
    });

    // Modal Edit Baris
    $('.btn-edit-baris').on('click', function() {
        $('#edit_baris_id').val($(this).data('id'));
        $('#edit_baris_ind').val($(this).data('ind'));
        $('#edit_baris_en').val($(this).data('en'));
        $('#modalEditBaris').modal('show');
    });

    // Modal Manage Baris
    $('.btn-manage-baris').on('click', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        $('#manage_baris_id').val(id);
        $('#manage_baris_title').text(nama);

        $.getJSON('<?= base_url('admin/master_dimensi/get_baris_items/'); ?>/' + id, function(res) {
            if (res.status === 'success') {
                const treeItems = [];
                const levelParents = {};
                let idSeq = 0;

                res.items.forEach(function(it) {
                    const lvl = parseInt(it.level_indentasi, 10) || 0;
                    const parentId = lvl > 0 ? (levelParents[lvl - 1] ? levelParents[lvl - 1].id : null) : null;
                    const node = {
                        id: 'node_' + (++idSeq),
                        parentId: parentId,
                        level: lvl,
                        ind: it.nama_baris_ind,
                        en: it.nama_baris_en || it.nama_baris_ind
                    };
                    levelParents[lvl] = node;
                    treeItems.push(node);
                });

                manageBarisBuilder.setItems(treeItems);
                $('#modalManageBaris').modal('show');
            }
        });
    });

    // =========================================================================
    // FITUR CERDAS: IMPOR JUDUL BARIS DARI SPREADSHEET / EXCEL / CSV
    // =========================================================================
    let currentImportTarget = 'add'; // 'add', 'manage', or 'add_direct'
    let currentInspectData = null;
    let currentExtractedItems = [];
    let currentTempFilePath = '';

    // Buka Modal Impor dari berbagai tombol trigger
    $(document).on('click', '.btn-open-import-modal', function() {
        currentImportTarget = $(this).data('target-builder') || 'add';
        $('#modalImportSpreadsheet').modal('show');
    });

    // 1. Eksekusi Periksa Sumber Google Sheets
    $('#btn_inspect_gsheet').on('click', function() {
        const url = $('#import_input_url').val().trim();
        if (!url) {
            Swal.fire({
                icon: 'warning',
                title: 'URL Belum Diisi',
                text: 'Silakan paste link Google Spreadsheet terlebih dahulu.',
                confirmButtonColor: '#2563EB'
            });
            return;
        }
        currentTempFilePath = '';
        executeInspectSource({ url_source: url });
    });

    // 2. Eksekusi Periksa File Upload
    $('#btn_inspect_file').on('click', function() {
        const fileInput = document.getElementById('import_input_file');
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'File Belum Dipilih',
                text: 'Silakan pilih file Excel (.xlsx, .xls) atau .csv terlebih dahulu.',
                confirmButtonColor: '#16A34A'
            });
            return;
        }
        const formData = new FormData();
        formData.append('file_source', fileInput.files[0]);
        currentTempFilePath = '';
        executeInspectSource(formData, true);
    });

    function executeInspectSource(postData, isFormData = false) {
        $('#import_loading').show();
        $('#import_loading_text').text('Sedang memindai lembar kerja dan struktur kolom...');
        $('#import_error_box').hide();
        $('#import_config_section').hide();
        $('#btn_apply_import_to_tree').prop('disabled', true);

        const ajaxOptions = {
            url: '<?= base_url('admin/master_dimensi/ajax_inspect_dimension_source'); ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                $('#import_loading').hide();
                if (res.status === 'success') {
                    currentInspectData = res;
                    if (res.temp_filepath) {
                        currentTempFilePath = res.temp_filepath;
                    }
                    renderInspectResult(res);
                } else {
                    $('#import_error_msg').text(res.message || 'Gagal membaca sumber data.');
                    $('#import_error_box').show();
                }
            },
            error: function(xhr, status, err) {
                $('#import_loading').hide();
                $('#import_error_msg').text('Terjadi kesalahan koneksi server: ' + err);
                $('#import_error_box').show();
            }
        };

        if (isFormData) {
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        }

        $.ajax(ajaxOptions);
    }

    function renderInspectResult(data) {
        // Render Sheet Dropdown
        const $sheetDropdown = $('#import_sheet_dropdown');
        $sheetDropdown.empty();
        if (data.sheets && data.sheets.length > 0) {
            data.sheets.forEach(function(sh) {
                const isSelected = (sh === data.current_sheet) ? 'selected' : '';
                $sheetDropdown.append(`<option value="${escapeHtml(sh)}" ${isSelected}>${escapeHtml(sh)}</option>`);
            });
            $('#box_sheet_select').show();
        } else {
            $('#box_sheet_select').hide();
        }

        const validCols = (data.columns || []).filter(c => c.sample_count > 0).length;
        $('#import_meta_badge').text(`${data.total_rows} baris data | ${validCols} kolom terisi`);

        // Render Column Dropdowns
        populateColumnDropdowns(data.columns || []);

        $('#import_config_section').slideDown(250);

        // Otomatis picu ekstraksi pertama
        triggerExtractRows();
    }

    function populateColumnDropdowns(columns) {
        const $indSelect = $('#import_col_ind_select');
        const $enSelect = $('#import_col_en_select');

        $indSelect.empty();
        $enSelect.empty();

        $enSelect.append('<option value="-1">[ Otomatis Terjemahkan Bulan / Ikuti Indonesia ]</option>');

        let defaultIndIdx = 0;
        let defaultEnIdx = -1;

        columns.forEach(function(col) {
            const hasData = col.sample_count > 0;
            const previewText = (col.preview && col.preview.length > 0) ? ` (Contoh: ${col.preview.slice(0, 3).join(', ')})` : (hasData ? '' : ' [Kosong]');
            const label = `Kolom ${col.letter} - ${col.header}${previewText}`;

            $indSelect.append(`<option value="${col.index}" ${!hasData ? 'class="text-muted"' : ''}>${escapeHtml(label)}</option>`);
            $enSelect.append(`<option value="${col.index}" ${!hasData ? 'class="text-muted"' : ''}>${escapeHtml(label)}</option>`);

            const hLower = (col.header || '').toLowerCase();
            if (hLower.includes('en') || hLower.includes('english') || hLower.includes('inggris')) {
                defaultEnIdx = col.index;
            }
        });

        $indSelect.val(defaultIndIdx);
        if (defaultEnIdx >= 0) {
            $enSelect.val(defaultEnIdx);
        }
    }

    // Jika ganti sheet
    $('#import_sheet_dropdown').on('change', function() {
        const sheetName = $(this).val();
        const payload = {
            sheet_name: sheetName
        };
        if (currentTempFilePath) {
            payload.temp_filepath = currentTempFilePath;
        } else {
            payload.url_source = $('#import_input_url').val().trim();
        }
        executeInspectSource(payload);
    });

    // Jika ganti kolom atau opsi pembersihan
    $('#import_col_ind_select, #import_col_en_select, #import_opt_dedup, #import_opt_clean_header, #import_opt_translate_month').on('change', function() {
        triggerExtractRows();
    });

    $('#btn_refresh_preview').on('click', function() {
        triggerExtractRows();
    });

    function triggerExtractRows() {
        const indCol = $('#import_col_ind_select').val();
        if (indCol === null || indCol === undefined) return;

        const enCol = $('#import_col_en_select').val();
        const dedup = $('#import_opt_dedup').is(':checked') ? 1 : 0;
        const cleanHdr = $('#import_opt_clean_header').is(':checked') ? 1 : 0;
        const autoTrans = $('#import_opt_translate_month').is(':checked') ? 1 : 0;
        const sheetName = $('#import_sheet_dropdown').val() || '';

        const payload = {
            ind_col: indCol,
            en_col: enCol,
            deduplicate: dedup,
            clean_headers: cleanHdr,
            auto_translate_month: autoTrans,
            sheet_name: sheetName
        };

        if (currentTempFilePath) {
            payload.temp_filepath = currentTempFilePath;
        } else {
            payload.url_source = $('#import_input_url').val().trim();
        }

        $('#import_result_count_badge').html('<span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span> Mengambil data...');
        $('#btn_apply_import_to_tree').prop('disabled', true);

        $.ajax({
            url: '<?= base_url('admin/master_dimensi/ajax_extract_dimension_source'); ?>',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    currentExtractedItems = res.items || [];
                    renderExtractPreview(currentExtractedItems);
                    $('#btn_apply_import_to_tree').prop('disabled', currentExtractedItems.length === 0);
                } else {
                    $('#table_import_preview_tbody').html(`<tr><td colspan="4" class="text-center py-3 text-danger small">${escapeHtml(res.message || 'Gagal mengekstrak data.')}</td></tr>`);
                    $('#import_result_count_badge').text('0 Baris');
                }
            },
            error: function(xhr, status, err) {
                $('#table_import_preview_tbody').html(`<tr><td colspan="4" class="text-center py-3 text-danger small">Error: ${escapeHtml(err)}</td></tr>`);
                $('#import_result_count_badge').text('0 Baris');
            }
        });
    }

    function renderExtractPreview(items) {
        const $tbody = $('#table_import_preview_tbody');
        $tbody.empty();

        $('#import_result_count_badge').text(`${items.length} Baris Siap Diimpor`);

        if (!items || items.length === 0) {
            $tbody.html('<tr><td colspan="4" class="text-center py-3 text-muted text-xxs">Tidak ada data baris yang ditemukan dengan kriteria saat ini.</td></tr>');
            return;
        }

        items.forEach(function(it, idx) {
            let lvlBadge = '<span class="badge bg-light text-primary border text-xxs">Bab (L0)</span>';
            let indentPx = 0;
            if (it.level === 1) {
                lvlBadge = '<span class="badge bg-light text-info border text-xxs">Sub Bab (L1)</span>';
                indentPx = 16;
            } else if (it.level === 2) {
                lvlBadge = '<span class="badge bg-light text-success border text-xxs">Sub-Sub (L2)</span>';
                indentPx = 32;
            }

            const tr = $(`
                <tr>
                    <td class="text-center text-muted text-xxs">${idx + 1}</td>
                    <td class="text-xxs fw-semibold text-dark" style="padding-left: ${10 + indentPx}px;">
                        ${it.level > 0 ? '<i class="bi bi-arrow-return-right text-muted me-1"></i>' : ''}${escapeHtml(it.ind)}
                    </td>
                    <td class="text-xxs text-secondary fst-italic">${escapeHtml(it.en || it.ind)}</td>
                    <td class="text-center">${lvlBadge}</td>
                </tr>
            `);
            $tbody.append(tr);
        });
    }

    // TERAPKAN KE TREE BUILDER
    $('#btn_apply_import_to_tree').on('click', function() {
        if (!currentExtractedItems || currentExtractedItems.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Kosong',
                text: 'Tidak ada data baris yang diekstrak untuk diterapkan.',
                confirmButtonColor: '#2563EB'
            });
            return;
        }

        // Konversi item ekstraksi menjadi treeItems yang siap dipakai TreeDimensionManager
        const treeItems = [];
        const levelParents = {};
        let idSeq = 0;

        currentExtractedItems.forEach(function(it) {
            const lvl = parseInt(it.level, 10) || 0;
            const parentId = lvl > 0 ? (levelParents[lvl - 1] ? levelParents[lvl - 1].id : null) : null;
            const node = {
                id: 'node_' + (++idSeq),
                parentId: parentId,
                level: lvl,
                ind: it.ind,
                en: it.en || it.ind
            };
            levelParents[lvl] = node;
            treeItems.push(node);
        });

        // Tutup modal impor
        $('#modalImportSpreadsheet').modal('hide');

        if (currentImportTarget === 'manage') {
            manageBarisBuilder.setItems(treeItems);
            $('#modalManageBaris').modal('show');
        } else if (currentImportTarget === 'add_direct') {
            addBarisBuilder.setItems(treeItems);
            $('#modalAddBaris').modal('show');
        } else {
            // Target 'add'
            addBarisBuilder.setItems(treeItems);
            $('#modalAddBaris').modal('show');
        }

        // Tampilkan konfirmasi sukses
        Swal.fire({
            icon: 'success',
            title: 'Berhasil Diimpor!',
            text: `${treeItems.length} judul baris telah berhasil dimasukkan ke Tree Builder.`,
            timer: 2000,
            showConfirmButton: false
        });
    });

    // =========================================================================
    // FITUR CERDAS: IMPOR KARAKTERISTIK KOLOM (FITUR 1 & FITUR 2)
    // =========================================================================
    let currentKolomImportTarget = 'add'; // 'add', 'manage', or 'add_direct'
    let currentKolomInspectData = null;
    let currentKolomTempFilePath = '';

    // Buka Modal Impor Kolom
    $(document).on('click', '.btn-open-import-kolom', function() {
        currentKolomImportTarget = $(this).data('target-builder') || 'add';
        $('#modalImportKolom').modal('show');
    });

    // 1. Periksa Google Sheets untuk Kolom
    $('#btn_inspect_kolom_gsheet').on('click', function() {
        const url = $('#import_kolom_input_url').val().trim();
        if (!url) {
            Swal.fire({
                icon: 'warning',
                title: 'URL Belum Diisi',
                text: 'Silakan paste link Google Spreadsheet terlebih dahulu.',
                confirmButtonColor: '#FF6D1F'
            });
            return;
        }
        currentKolomTempFilePath = '';
        executeInspectKolomSource({ url_source: url });
    });

    // 2. Periksa Upload File untuk Kolom
    $('#btn_inspect_kolom_file').on('click', function() {
        const fileInput = document.getElementById('import_kolom_input_file');
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'File Belum Dipilih',
                text: 'Silakan pilih file Excel (.xlsx, .xls) atau .csv terlebih dahulu.',
                confirmButtonColor: '#16A34A'
            });
            return;
        }
        const formData = new FormData();
        formData.append('file_source', fileInput.files[0]);
        currentKolomTempFilePath = '';
        executeInspectKolomSource(formData, true);
    });

    function executeInspectKolomSource(postData, isFormData = false) {
        $('#import_kolom_loading').show();
        $('#import_kolom_error_box').hide();
        $('#import_kolom_config_section').hide();
        $('#btn_apply_import_kolom_to_tree').prop('disabled', true);

        const ajaxOptions = {
            url: '<?= base_url('admin/master_dimensi/ajax_inspect_dimension_source'); ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                $('#import_kolom_loading').hide();
                if (res.status === 'success') {
                    currentKolomInspectData = res;
                    if (res.temp_filepath) {
                        currentKolomTempFilePath = res.temp_filepath;
                    }
                    renderInspectKolomResult(res);
                } else {
                    $('#import_kolom_error_msg').text(res.message || 'Gagal membaca sumber data.');
                    $('#import_kolom_error_box').show();
                }
            },
            error: function(xhr, status, err) {
                $('#import_kolom_loading').hide();
                $('#import_kolom_error_msg').text('Terjadi kesalahan koneksi server: ' + err);
                $('#import_kolom_error_box').show();
            }
        };

        if (isFormData) {
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        }

        $.ajax(ajaxOptions);
    }

    function renderInspectKolomResult(data) {
        // Render Sheet Dropdown
        const $sheetDropdown = $('#import_kolom_sheet_dropdown');
        $sheetDropdown.empty();
        if (data.sheets && data.sheets.length > 0) {
            data.sheets.forEach(function(sh) {
                const isSelected = (sh === data.current_sheet) ? 'selected' : '';
                $sheetDropdown.append(`<option value="${escapeHtml(sh)}" ${isSelected}>${escapeHtml(sh)}</option>`);
            });
            $('#box_kolom_sheet_select').show();
        } else {
            $('#box_kolom_sheet_select').hide();
        }

        const validCols = (data.columns || []).filter(c => c.sample_count > 0).length;
        $('#import_kolom_meta_badge').text(`${data.total_rows} baris | ${validCols} kolom data`);

        // Cerdas: Jika tabel terdeteksi berperiode waktu (seperti tabel horizontal bulanan/tahunan),
        // otomatis rekomendasikan dan pilih mode Karakteristik Utama Saja agar tidak menduplikasi kolom
        if (data.has_periode && data.distinct_parents && data.distinct_parents.length > 0) {
            $('#opt_kolom_mode_parent').prop('checked', true);
            const parentListStr = data.distinct_parents.map(p => escapeHtml(p.ind)).join(', ');
            $('#kolom_mode_help_text').html(`
                <div class="alert alert-success py-1 px-2 mb-0 d-flex align-items-center gap-2" style="font-size: 0.74rem;">
                    <i class="bi bi-magic fs-6 text-success"></i>
                    <div>
                        <b>Terdeteksi Tabel Berperiode Waktu:</b> Direkomendasikan mengimpor <b>Karakteristik Utama Saja</b> (<i>${parentListStr}</i>) agar dimensi periode waktu dikelola mandiri di Master Periode.
                    </div>
                </div>
            `);
        }

        // Render Checklist Kolom (FITUR 1 & FITUR 2)
        renderKolomChecklist(data.columns || []);

        $('#import_kolom_config_section').slideDown(250);

        // Update live preview
        updateKolomImportPreview();
    }

    function renderKolomChecklist(columns) {
        const $container = $('#container_kolom_checklist');
        $container.empty();

        if (!columns || columns.length === 0) {
            $container.html('<div class="col-12 text-center py-3 text-muted">Tidak ditemukan kolom.</div>');
            return;
        }

        columns.forEach(function(col) {
            const hasData = col.sample_count > 0;
            const isRowId = col.is_row_identifier === true;
            // FITUR 1: Jika is_row_identifier, default UNCHECKED! Seluruh kolom data otomatis CHECKED!
            const isChecked = !isRowId;

            const cardBorder = isRowId ? 'border-secondary bg-light text-muted opacity-75' : (hasData ? 'border-light-subtle bg-white' : 'border-light bg-light');
            const previewText = (col.preview && col.preview.length > 0) ? col.preview.slice(0, 4).join(', ') : (hasData ? 'Ada data' : 'Template / data belum terisi');
            const enBadge = (col.header_en && col.header_en !== col.header) ? `<div class="text-primary text-xxs mt-0 fw-normal" style="font-size: 0.72rem;"><i class="bi bi-globe me-1"></i>${escapeHtml(col.header_en)}</div>` : '';

            const colCard = $(`
                <div class="col-md-6 mb-2">
                    <div class="p-2 border rounded-2 d-flex align-items-start gap-2 shadow-xs ${cardBorder}">
                        <div class="form-check mt-1">
                            <input class="form-check-input import-kolom-chk" type="checkbox" 
                                   value="${col.index}" 
                                   data-header-ind="${escapeHtml(col.header)}" 
                                   data-header-en="${escapeHtml(col.header_en || col.header)}"
                                   data-sub-header="${escapeHtml(col.sub_header || '')}"
                                   data-full-title="${escapeHtml(col.full_title || col.header)}"
                                   data-is-row-id="${isRowId ? '1' : '0'}"
                                   id="chk_col_${col.index}" 
                                   ${isChecked ? 'checked' : ''}>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <label class="form-check-label fw-bold text-xxs ${isRowId ? 'text-muted' : 'text-dark'} d-flex flex-wrap align-items-center gap-1 mb-0" for="chk_col_${col.index}" style="cursor: pointer;">
                                <span>Kolom ${col.letter}: ${escapeHtml(col.full_title)}</span>
                                ${isRowId ? '<span class="badge bg-secondary text-white text-xxs ms-1"><i class="bi bi-slash-circle me-1"></i>Identitas Baris (Dilewati)</span>' : ''}
                            </label>
                            ${enBadge}
                            <div class="text-muted text-xxs text-truncate mt-1" style="font-size: 0.72rem;">
                                Sampel: ${escapeHtml(previewText)}
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $container.append(colCard);
        });

        // Event listener saat checkbox diubah
        $('.import-kolom-chk').on('change', function() {
            updateKolomImportPreview();
        });
    }

    // Listener ganti mode import ke tree builder
    $('input[name="opt_kolom_import_mode"]').on('change', function() {
        const m = $(this).val();
        const $ht = $('#kolom_mode_help_text');
        if (m === 'tree') {
            $ht.html('<i class="bi bi-info-circle me-1 text-primary"></i> <b>Rekomendasi:</b> Otomatis menyusun header menjadi <b>Bab (Induk)</b> dan <b>Sub Bab (Anak/Tahun/Kategori)</b> sesuai struktur tabel asli.');
        } else if (m === 'parent') {
            $ht.html('<i class="bi bi-info-circle me-1 text-success"></i> Mengimpor daftar <b>nama karakteristik utama unik dwibahasa</b> (tanpa sub-kolom tahun, cocok jika dimensi tahun diatur terpisah di Master Periode).');
        } else {
            $ht.html('<i class="bi bi-info-circle me-1 text-secondary"></i> Mengimpor <b>setiap kolom menjadi item Bab mandiri</b> dengan nama lengkap digabung.');
        }
    });

    // Ganti Sheet Kolom
    $('#import_kolom_sheet_dropdown').on('change', function() {
        const sheetName = $(this).val();
        const payload = { sheet_name: sheetName };
        if (currentKolomTempFilePath) {
            payload.temp_filepath = currentKolomTempFilePath;
        } else {
            payload.url_source = $('#import_kolom_input_url').val().trim();
        }
        executeInspectKolomSource(payload);
    });

    // Quick Buttons: Centang Semua Data (hanya yang bukan row id) & Bersihkan
    $('#btn_kolom_check_all').on('click', function() {
        $('.import-kolom-chk').each(function() {
            if ($(this).data('is-row-id') == '0') {
                $(this).prop('checked', true);
            }
        });
        updateKolomImportPreview();
    });

    $('#btn_kolom_uncheck_all').on('click', function() {
        $('.import-kolom-chk').prop('checked', false);
        updateKolomImportPreview();
    });

    function getCheckedKolomList() {
        const selected = [];
        $('.import-kolom-chk:checked').each(function() {
            const cIdx = parseInt($(this).val(), 10);
            const colData = (currentKolomInspectData && currentKolomInspectData.columns) 
                ? currentKolomInspectData.columns.find(c => c.index === cIdx)
                : null;
            if (colData) {
                selected.push(colData);
            } else {
                selected.push({
                    index: cIdx,
                    header: $(this).data('header-ind'),
                    header_en: $(this).data('header-en'),
                    sub_header: $(this).data('sub-header'),
                    full_title: $(this).data('full-title')
                });
            }
        });
        return selected;
    }

    function updateKolomImportPreview() {
        const checkedCols = getCheckedKolomList();
        const count = checkedCols.length;

        $('#import_kolom_count_badge').text(`${count} Kolom Terpilih`);
        $('#btn_apply_import_kolom_to_tree').prop('disabled', count === 0);

        const $table = $('#table_preview_import_kolom');
        $table.empty();

        if (count === 0) {
            $table.html('<thead><tr><th class="py-3 text-muted fst-italic text-xs">Belum ada kolom data yang dicentang. Silakan centang kolom di atas.</th></tr></thead>');
            return;
        }

        const thead = $('<thead></thead>');

        // Jika tersedia header_grid multi-level dari Python
        if (currentKolomInspectData && currentKolomInspectData.header_grid && currentKolomInspectData.header_grid.length > 0) {
            const rawGrid = currentKolomInspectData.header_grid;
            const activeColIndices = (currentKolomInspectData.columns || []).map(c => c.index);

            // Filter hanya baris header yang punya isi pada kolom-kolom yang dicentang
            const grid = rawGrid.filter(rowCells => {
                return checkedCols.some(cData => {
                    const pos = activeColIndices.indexOf(cData.index);
                    return pos >= 0 && pos < rowCells.length && rowCells[pos] && rowCells[pos].trim() !== '';
                });
            });

            if (grid.length > 0) {
                grid.forEach(function(rowCells, rIdx) {
                    const tr = $('<tr></tr>');

                    // Kolom Judul Baris dummy di awal dengan rowspan penuh
                    if (rIdx === 0) {
                        tr.append(`<th rowspan="${grid.length}" style="width: 140px; background: #fed7aa; color: #7c2d12; border: 1px solid #fdba74; vertical-align: middle; padding: 8px; font-weight: bold; font-size: 12px;">(Judul Baris)</th>`);
                    }

                    let i = 0;
                    while (i < checkedCols.length) {
                        const cData = checkedCols[i];
                        const pos = activeColIndices.indexOf(cData.index);
                        const val = (pos >= 0 && pos < rowCells.length) ? rowCells[pos] : (cData.header || '');

                        let span = 1;
                        while (i + span < checkedCols.length) {
                            const nextCol = checkedCols[i + span];
                            const nextPos = activeColIndices.indexOf(nextCol.index);
                            const nextVal = (nextPos >= 0 && nextPos < rowCells.length) ? rowCells[nextPos] : (nextCol.header || '');
                            if (nextVal === val && val !== '') {
                                span++;
                            } else {
                                break;
                            }
                        }

                        // Styling bertingkat sesuai hierarki header
                        let cellStyle = "border: 1px solid #fed7aa; padding: 6px 10px; font-size: 12px;";
                        if (rIdx === 0) {
                            cellStyle += "background: #fff7ed; color: #9a3412; font-weight: bold;";
                        } else if (rIdx === 1 && grid.length > 2) {
                            cellStyle += "background: #fffaf5; color: #64748b; font-style: italic; font-size: 11px;";
                        } else {
                            cellStyle += "background: #ffffff; color: #1e293b; font-weight: 600; font-size: 11px;";
                        }

                        tr.append(`<th colspan="${span}" style="${cellStyle}">${escapeHtml(val || cData.header)}</th>`);
                        i += span;
                    }

                    thead.append(tr);
                });
            } else {
                // Fallback jika semua baris header grid kosong
                const tr = $('<tr style="background: #FFF4ED; font-weight: bold; border-bottom: 2px solid #FF6D1F;"></tr>');
                tr.append('<th style="width: 140px; background: #fed7aa; color: #9a3412; border: 1px solid #fdba74; vertical-align: middle; padding: 8px;">(Judul Baris)</th>');
                checkedCols.forEach(function(col) {
                    tr.append(`<th style="background: #ffffff; border: 1px solid #fed7aa; padding: 8px 12px; color: #1e293b; font-size: 12px;">${escapeHtml(col.full_title)}</th>`);
                });
                thead.append(tr);
            }
        } else {
            // Fallback header 1 baris
            const tr = $('<tr style="background: #FFF4ED; font-weight: bold; border-bottom: 2px solid #FF6D1F;"></tr>');
            tr.append('<th style="width: 140px; background: #fed7aa; color: #9a3412; border: 1px solid #fdba74; vertical-align: middle; padding: 8px;">(Judul Baris)</th>');
            checkedCols.forEach(function(col) {
                tr.append(`<th style="background: #ffffff; border: 1px solid #fed7aa; padding: 8px 12px; color: #1e293b; font-size: 12px;">${escapeHtml(col.full_title)}</th>`);
            });
            thead.append(tr);
        }

        $table.append(thead);

        // Render Baris Sampel Data Riil
        if (currentKolomInspectData && currentKolomInspectData.sample_rows && currentKolomInspectData.sample_rows.length > 0) {
            const tbody = $('<tbody></tbody>');
            const activeColIndices = (currentKolomInspectData.columns || []).map(c => c.index);

            // Deteksi label baris dari kolom row identifier (misal Cilacap, Banyumas)
            const rowIdCols = (currentKolomInspectData.columns || []).filter(c => c.is_row_identifier);
            const rowLabelColIdx = (currentKolomInspectData.best_row_col_index !== undefined) 
                ? currentKolomInspectData.best_row_col_index 
                : (rowIdCols.length > 1 ? rowIdCols[1].index : (rowIdCols.length > 0 ? rowIdCols[0].index : 0));
            const rowLabelPos = activeColIndices.indexOf(rowLabelColIdx);

            currentKolomInspectData.sample_rows.forEach(function(sRow, sIdx) {
                const rowLabel = (rowLabelPos >= 0 && sRow[rowLabelPos]) ? sRow[rowLabelPos].trim() : `Data ${sIdx + 1}`;
                if (!rowLabel || rowLabel === '') return;

                const tr = $('<tr></tr>');
                tr.append(`<td style="background: #f8fafc; font-weight: 600; color: #334155; border: 1px solid #e2e8f0; font-size: 11px; padding: 5px 8px; text-align: left;">${escapeHtml(rowLabel)}</td>`);

                checkedCols.forEach(function(col) {
                    const pos = activeColIndices.indexOf(col.index);
                    const val = (pos >= 0 && sRow[pos] && sRow[pos].trim() !== '') ? sRow[pos].trim() : '-';
                    tr.append(`<td style="border: 1px solid #e2e8f0; font-size: 11px; padding: 5px 8px; color: #475569;">${escapeHtml(val)}</td>`);
                });

                tbody.append(tr);
            });

            $table.append(tbody);
        }
    }

    // Terapkan Kolom Terpilih ke Tree Builder Kolom
    $('#btn_apply_import_kolom_to_tree').on('click', function() {
        const checkedCols = getCheckedKolomList();
        if (checkedCols.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Ada Kolom',
                text: 'Silakan centang minimal satu kolom data.',
                confirmButtonColor: '#FF6D1F'
            });
            return;
        }

        const importMode = $('input[name="opt_kolom_import_mode"]:checked').val() || 'tree';
        const treeItems = [];
        let idSeq = 0;

        if (importMode === 'parent') {
            // Mode: Karakteristik Induk Saja (Deduplikasi Tanpa Sub/Periode)
            const seenParentNames = new Set();
            checkedCols.forEach(function(col) {
                const pName = (col.header || '').trim();
                const pKey = pName.toLowerCase();
                if (pName && !seenParentNames.has(pKey)) {
                    seenParentNames.add(pKey);
                    treeItems.push({
                        id: 'node_' + Date.now() + '_' + (++idSeq),
                        parentId: null,
                        level: 0,
                        ind: pName,
                        en: (col.header_en && col.header_en.trim()) ? col.header_en.trim() : pName
                    });
                }
            });
        } else if (importMode === 'flat') {
            // Mode: Kolom Datar Lengkap (Semua Kolom Jadi Bab Mandiri)
            checkedCols.forEach(function(col) {
                treeItems.push({
                    id: 'node_' + Date.now() + '_' + (++idSeq),
                    parentId: null,
                    level: 0,
                    ind: col.full_title || col.header,
                    en: col.full_title || col.header_en || col.header
                });
            });
        } else {
            // Mode: Struktur Hierarki Tree Bab & Sub-Bab (Deduplikasi Parent Cerdas)
            const seenParentsMap = new Map();
            const seenChildrenSet = new Set();

            checkedCols.forEach(function(col) {
                const pName = (col.header || '').trim();
                const pEn   = (col.header_en && col.header_en.trim()) ? col.header_en.trim() : pName;
                const subInd = (col.sub_header || '').trim();
                const subEn  = (col.sub_header || '').trim();

                const pKey = pName.toLowerCase();

                let parentNode = seenParentsMap.get(pKey);
                if (!parentNode) {
                    parentNode = {
                        id: 'node_' + Date.now() + '_' + (++idSeq),
                        parentId: null,
                        level: 0,
                        ind: pName,
                        en: pEn
                    };
                    seenParentsMap.set(pKey, parentNode);
                    treeItems.push(parentNode);
                }

                // Tambahkan node anak jika ada sub_header dan belum pernah ada di parent ini
                if (subInd && subInd.toLowerCase() !== pKey) {
                    const childKey = `${parentNode.id}::${subInd.toLowerCase()}`;
                    if (!seenChildrenSet.has(childKey)) {
                        seenChildrenSet.add(childKey);
                        treeItems.push({
                            id: 'node_' + Date.now() + '_' + (++idSeq),
                            parentId: parentNode.id,
                            level: 1,
                            ind: subInd,
                            en: subEn
                        });
                    }
                }
            });
        }

        // Tutup modal import
        $('#modalImportKolom').modal('hide');

        // Otomatis usulkan nama kelompok jika input nama masih kosong
        if (currentKolomInspectData) {
            const suggNameInd = currentKolomInspectData.suggested_kolom_ind || currentKolomInspectData.current_sheet;
            const suggNameEn = currentKolomInspectData.suggested_kolom_en || suggNameInd;
            const $namaIndInput = $('#add_karakteristik_nama_ind');
            if ($namaIndInput.length && !$namaIndInput.val().trim() && suggNameInd) {
                $namaIndInput.val(suggNameInd);
            }
            const $namaEnInput = $('#add_karakteristik_nama_en');
            if ($namaEnInput.length && !$namaEnInput.val().trim() && suggNameEn) {
                $namaEnInput.val(suggNameEn);
            }
        }

        if (currentKolomImportTarget === 'manage') {
            manageKolomBuilder.setItems(treeItems);
            $('#modalManageKolom').modal('show');
        } else if (currentKolomImportTarget === 'add_direct') {
            addKolomBuilder.setItems(treeItems);
            $('#modalAddKarakteristik').modal('show');
        } else {
            addKolomBuilder.setItems(treeItems);
            $('#modalAddKarakteristik').modal('show');
        }

        Swal.fire({
            icon: 'success',
            title: 'Berhasil Diimpor!',
            text: `${treeItems.length} item karakteristik berhasil disusun ke Tree Builder Kolom.`,
            timer: 2000,
            showConfirmButton: false
        });
    });

    // -------------------------------------------------------------------------
    // 3. ENGINE PREVIEW PERIODE WAKTU (TANPA SUB-BAB)
    // -------------------------------------------------------------------------
    function parsePeriodeLines(textInd, textEn) {
        const linesInd = (textInd || '').split(/\r?\n/).filter(l => l.trim() !== '');
        const linesEn  = (textEn || '').split(/\r?\n/).filter(l => l.trim() !== '');
        let items = [];

        linesInd.forEach(function(lInd, idx) {
            lInd = lInd.trim();
            if (!lInd) return;

            let lEn = linesEn[idx] ? linesEn[idx].trim() : '';
            if (lInd.includes('//') && !lEn) {
                const partsLang = lInd.split('//').map(s => s.trim());
                lInd = partsLang[0];
                lEn  = partsLang[1] || '';
            }

            items.push({
                nama_periode_ind: lInd,
                nama_periode_en:  lEn || lInd
            });
        });

        return items;
    }

    function renderPeriodeToTable($table, items, langMode) {
        $table.empty();

        if (!items || items.length === 0) {
            $table.html('<thead><tr><th class="py-4 text-muted fst-italic">Ketik daftar periode di atas untuk melihat preview visual otomatis...</th></tr></thead>');
            return;
        }

        const thead = $('<thead></thead>');
        const barisTitle = formatLabel('Baris', 'Row', langMode);
        const count = items.length;

        // Row 1: Header Induk
        let tr1 = $('<tr style="background: #f8fafc; font-weight: bold; border-bottom: 1px solid #cbd5e1;"></tr>');
        tr1.append('<th rowspan="2" style="width: 140px; background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 10px;">' + barisTitle + '</th>');

        const sampleColLabels = [
            { ind: 'Kolom 1', en: 'Column 1' },
            { ind: 'Kolom 2', en: 'Column 2' },
            { ind: 'Kolom 3', en: 'Column 3' }
        ];

        sampleColLabels.forEach(function(col) {
            let colLbl = formatLabel(col.ind, col.en, langMode);
            tr1.append('<th colspan="' + count + '" style="background: #e2e8f0; border: 1px solid #cbd5e1; padding: 8px 12px; color: #1e293b;">' + colLbl + '</th>');
        });

        // Row 2: Sub-Header Periode
        let tr2 = $('<tr style="background: #f1f5f9; font-weight: bold; border-bottom: 2px solid #cbd5e1;"></tr>');
        sampleColLabels.forEach(function() {
            items.forEach(function(item) {
                let pLbl = formatLabel(item.nama_periode_ind, item.nama_periode_en, langMode);
                tr2.append('<th style="border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 13px; background: #ffffff;">' + pLbl + '</th>');
            });
        });

        thead.append(tr1);
        thead.append(tr2);
        $table.append(thead);

        let tbody = $('<tbody></tbody>');
        ['Baris 1', 'Baris 2'].forEach(function(bName, bIdx) {
            let trBody = $('<tr></tr>');
            let bLbl = formatLabel(bName, 'Row ' + (bIdx + 1), langMode);
            trBody.append('<td class="text-start py-2 px-3 fw-semibold text-secondary" style="background: #fafafa; border: 1px solid #e2e8f0;">' + bLbl + '</td>');
            for (let i = 0; i < sampleColLabels.length * count; i++) {
                trBody.append('<td class="text-center text-muted small py-2" style="border: 1px solid #e2e8f0;">...</td>');
            }
            tbody.append(trBody);
        });
        $table.append(tbody);
    }

    // Instansiasi Builder Periode (Tanpa Sub-Bab)
    const addPeriodeBuilder = new TreeDimensionManager({
        containerId: '#add_periode_builder',
        listId: '#add_periode_items_list',
        addRootBtnId: '#btn_add_periode_root',
        hiddenIndId: '#add_periode_text_ind',
        hiddenEnId: '#add_periode_text_en',
        hiddenJsonId: '#add_periode_json',
        countBadgeId: '#add_periode_count',
        theme: 'theme-periode',
        allowSub: false,
        maxDepth: 0,
        type: 'periode',
        itemLabel: 'Periode',
        onChange: function(textInd, textEn) {
            const items = parsePeriodeLines(textInd, textEn);
            renderPeriodeToTable($('#table_preview_add_periode'), items, 'both');
        }
    });

    // Default 4 periode kosong untuk Add Periode
    for (let i = 0; i < 4; i++) {
        addPeriodeBuilder.addItem(null, 0, '', '');
    }

    const managePeriodeBuilder = new TreeDimensionManager({
        containerId: '#manage_periode_builder',
        listId: '#manage_periode_items_list',
        addRootBtnId: '#btn_manage_periode_root',
        hiddenIndId: '#manage_periode_textarea_ind',
        hiddenEnId: '#manage_periode_textarea_en',
        hiddenJsonId: '#manage_periode_json',
        countBadgeId: '#manage_periode_count',
        theme: 'theme-periode',
        allowSub: false,
        maxDepth: 0,
        type: 'periode',
        itemLabel: 'Periode',
        onChange: function(textInd, textEn) {
            const items = parsePeriodeLines(textInd, textEn);
            renderPeriodeToTable($('#preview_table_periode'), items, 'both');
        }
    });

    // Modal Edit Periode
    $('.btn-edit-periode').on('click', function() {
        $('#edit_periode_id').val($(this).data('id'));
        $('#edit_periode_ind').val($(this).data('ind'));
        $('#edit_periode_en').val($(this).data('en'));
        $('#modalEditPeriode').modal('show');
    });

    // Modal Manage Periode
    $('.btn-manage-periode').on('click', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        $('#manage_periode_id').val(id);
        $('#manage_periode_title').text(nama);

        $.getJSON('<?= base_url('admin/master_dimensi/get_periode_items/'); ?>/' + id, function(res) {
            if (res.status === 'success') {
                const treeItems = [];
                let idSeq = 0;
                res.items.forEach(function(item) {
                    treeItems.push({
                        id: 'node_' + (++idSeq),
                        parentId: null,
                        level: 0,
                        ind: item.nama_periode_ind,
                        en: item.nama_periode_en || item.nama_periode_ind
                    });
                });
                managePeriodeBuilder.setItems(treeItems);
                $('#modalManagePeriode').modal('show');
            }
        });
    });

    // =========================================================================
    // FITUR: VALIDASI REAL-TIME DUPLIKASI NAMA KELOMPOK DIMENSI (3 DIMENSI)
    // =========================================================================
    function setupDuplicateNameValidator(modalSelector, inputSelector, type, idSelector) {
        const $modal = $(modalSelector);
        const $input = $modal.find(inputSelector);
        const $form = $modal.find('form');
        const $submitBtn = $form.find('button[type="submit"]');

        let $feedback = $input.siblings('.duplicate-feedback');
        if (!$feedback.length) {
            $feedback = $('<div class="duplicate-feedback small mt-1 fw-semibold"></div>');
            $input.after($feedback);
        }

        let debounceTimer = null;

        function checkDuplicate() {
            const nama = $.trim($input.val());
            const currentId = idSelector ? (parseInt($(idSelector).val()) || 0) : 0;

            if (nama === '') {
                $input.removeClass('is-invalid is-valid');
                $feedback.empty();
                $submitBtn.prop('disabled', false).removeClass('disabled');
                return;
            }

            $.ajax({
                url: '<?= base_url('admin/master_dimensi/ajax_check_duplicate'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    type: type,
                    nama: nama,
                    id: currentId
                },
                success: function(res) {
                    if (res && res.exists) {
                        $input.addClass('is-invalid').removeClass('is-valid');
                        const existLabel = res.existing_name ? ' (serupa dengan: <u>' + $('<div>').text(res.existing_name).html() + '</u>)' : '';
                        $feedback.html('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Judul ini sudah terdaftar' + existLabel + '! Variasi spasi atau tanda baca dianggap sama.</span>');
                        $submitBtn.prop('disabled', true).addClass('disabled');
                    } else {
                        $input.removeClass('is-invalid').addClass('is-valid');
                        $feedback.html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Judul tersedia (belum digunakan).</span>');
                        $submitBtn.prop('disabled', false).removeClass('disabled');
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).removeClass('disabled');
                }
            });
        }

        $input.on('input keyup paste change', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(checkDuplicate, 300);
        });

        $modal.on('hidden.bs.modal', function() {
            $input.removeClass('is-invalid is-valid');
            $feedback.empty();
            $submitBtn.prop('disabled', false).removeClass('disabled');
        });

        $modal.on('shown.bs.modal', function() {
            const currentVal = $.trim($input.val());
            if (currentVal !== '') {
                checkDuplicate();
            } else {
                $input.removeClass('is-invalid is-valid');
                $feedback.empty();
                $submitBtn.prop('disabled', false).removeClass('disabled');
            }
        });

        $form.on('submit', function(e) {
            if ($input.hasClass('is-invalid')) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Judul Sudah Ada!',
                    text: 'Judul kelompok dimensi ini sudah digunakan. Silakan gunakan judul lain.',
                    confirmButtonColor: '#ff6d1f'
                });
                return false;
            }
        });
    }

    // =========================================================================
    // FITUR CERDAS: IMPOR TERPADU (KOLOM & BARIS SEKALIGUS)
    // =========================================================================
    let currentTerpaduInspectData = null;
    let currentTerpaduTempFilePath = null;

    // Buka Modal Impor Terpadu
    $('.btn-open-terpadu-modal').on('click', function() {
        $('#modalImportTerpadu').modal('show');
    });

    // =========================================================================
    // FITUR: VALIDASI REAL-TIME DUPLIKASI NAMA KELOMPOK PADA IMPOR TERPADU
    // =========================================================================
    function setupTerpaduFieldValidator(inputSelector, type) {
        const $input = $(inputSelector);
        let $feedback = $input.siblings('.duplicate-feedback');
        if (!$feedback.length) {
            $feedback = $('<div class="duplicate-feedback small mt-1 fw-semibold"></div>');
            $input.after($feedback);
        }

        let debounceTimer = null;

        function check() {
            const nama = $.trim($input.val());
            if (nama === '') {
                $input.removeClass('is-invalid is-valid');
                $feedback.empty();
                updateTerpaduSwitchStates();
                return;
            }

            $.ajax({
                url: '<?= base_url('admin/master_dimensi/ajax_check_duplicate'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    type: type,
                    nama: nama,
                    id: 0
                },
                success: function(res) {
                    if (res && res.exists) {
                        $input.addClass('is-invalid').removeClass('is-valid');
                        const existLabel = res.existing_name ? ' (serupa dengan: <u>' + $('<div>').text(res.existing_name).html() + '</u>)' : '';
                        $feedback.html('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Nama kelompok ini sudah terdaftar' + existLabel + '! Variasi spasi/tanda baca dianggap sama.</span>');
                    } else {
                        $input.removeClass('is-invalid').addClass('is-valid');
                        $feedback.html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Nama kelompok tersedia (belum digunakan).</span>');
                    }
                    updateTerpaduSwitchStates();
                },
                error: function() {
                    updateTerpaduSwitchStates();
                }
            });
        }

        function clear() {
            $input.removeClass('is-invalid is-valid');
            $feedback.empty();
        }

        $input.on('input keyup paste change', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(check, 300);
        });

        return { check: check, clear: clear };
    }

    const validatorKolomTerpadu   = setupTerpaduFieldValidator('#terpadu_nama_kolom_ind', 'karakteristik');
    const validatorBarisTerpadu   = setupTerpaduFieldValidator('#terpadu_nama_baris_ind', 'baris');
    const validatorPeriodeTerpadu = setupTerpaduFieldValidator('#terpadu_nama_periode_ind', 'periode');

    $('#modalImportTerpadu').on('show.bs.modal', function() {
        resetTerpaduModal();
    });

    function resetTerpaduModal() {
        currentTerpaduInspectData = null;
        currentTerpaduTempFilePath = null;
        $('#terpadu_file_input').val('');
        $('#terpadu_url_input').val('');
        $('#box_terpadu_sheet_select').hide();
        $('#terpadu_sheet_dropdown').empty();
        $('#terpadu_loading').hide();
        $('#terpadu_error_box').hide();
        $('#terpadu_results_section').hide();
        $('#btn_terpadu_submit').prop('disabled', true);
        $('#terpadu_opt_save_kolom').prop('checked', true);
        $('#terpadu_opt_save_baris').prop('checked', true);
        $('#terpadu_opt_save_periode').prop('checked', true);
        $('#terpadu_kolom_fmt_parent').prop('checked', true);
        $('#terpadu_kolom_fmt_tree').prop('checked', false);
        $('#terpadu_nama_kolom_ind').val('');
        $('#terpadu_nama_kolom_en').val('');
        $('#terpadu_nama_baris_ind').val('');
        $('#terpadu_nama_baris_en').val('');
        $('#terpadu_nama_periode_ind').val('');
        $('#terpadu_nama_periode_en').val('');
        $('#terpadu_kolom_preview_list').empty();
        $('#terpadu_baris_preview_list').empty();
        $('#terpadu_periode_preview_list').empty();
        $('#terpadu_matrix_thead').empty();
        $('#terpadu_matrix_tbody').empty();
        $('#terpadu_table_meta_badge').text('0 baris | 0 kolom');

        validatorKolomTerpadu.clear();
        validatorBarisTerpadu.clear();
        validatorPeriodeTerpadu.clear();

        updateTerpaduSwitchStates();
    }

    // Toggle switch kolom, baris, & periode
    $('#terpadu_opt_save_kolom, #terpadu_opt_save_baris, #terpadu_opt_save_periode').on('change', function() {
        updateTerpaduSwitchStates();
    });

    function updateTerpaduSwitchStates() {
        const isKolomActive   = $('#terpadu_opt_save_kolom').is(':checked');
        const isBarisActive   = $('#terpadu_opt_save_baris').is(':checked');
        const isPeriodeActive = $('#terpadu_opt_save_periode').is(':checked');

        if (isKolomActive) {
            $('#terpadu_card_kolom').css({ 'opacity': '1', 'filter': 'none' });
            $('#terpadu_kolom_body input').prop('disabled', false);
        } else {
            $('#terpadu_card_kolom').css({ 'opacity': '0.45', 'filter': 'grayscale(40%)' });
            $('#terpadu_kolom_body input').prop('disabled', true);
        }

        if (isBarisActive) {
            $('#terpadu_card_baris').css({ 'opacity': '1', 'filter': 'none' });
            $('#terpadu_baris_body input').prop('disabled', false);
        } else {
            $('#terpadu_card_baris').css({ 'opacity': '0.45', 'filter': 'grayscale(40%)' });
            $('#terpadu_baris_body input').prop('disabled', true);
        }

        if (isPeriodeActive) {
            $('#terpadu_card_periode').css({ 'opacity': '1', 'filter': 'none' });
            $('#terpadu_periode_body input').prop('disabled', false);
        } else {
            $('#terpadu_card_periode').css({ 'opacity': '0.45', 'filter': 'grayscale(40%)' });
            $('#terpadu_periode_body input').prop('disabled', true);
        }

        const hasActiveDuplicate = (isKolomActive && $('#terpadu_nama_kolom_ind').hasClass('is-invalid')) ||
                                   (isBarisActive && $('#terpadu_nama_baris_ind').hasClass('is-invalid')) ||
                                   (isPeriodeActive && $('#terpadu_nama_periode_ind').hasClass('is-invalid'));

        const canSubmit = currentTerpaduInspectData && (isKolomActive || isBarisActive || isPeriodeActive) && !hasActiveDuplicate;
        $('#btn_terpadu_submit').prop('disabled', !canSubmit);

        let activeLabels = [];
        if (isKolomActive) activeLabels.push('Kolom');
        if (isBarisActive) activeLabels.push('Baris');
        if (isPeriodeActive) activeLabels.push('Periode Waktu');

        if (hasActiveDuplicate) {
            $('#btn_terpadu_submit').html(`<i class="bi bi-exclamation-octagon-fill text-danger me-1"></i> Nama Kelompok Sudah Ada (Ganti / Nonaktifkan)`);
        } else if (activeLabels.length > 0) {
            $('#btn_terpadu_submit').html(`<i class="bi bi-rocket-takeoff-fill"></i> Simpan Terpadu (${activeLabels.join(' & ')})`);
        } else {
            $('#btn_terpadu_submit').html(`<i class="bi bi-rocket-takeoff-fill"></i> Pilih Minimal 1 Dimensi`);
        }
    }

    // Klik tombol Periksa Sumber Data
    $('#btn_terpadu_inspect').on('click', function() {
        const file = $('#terpadu_file_input')[0].files[0];
        const url = $('#terpadu_url_input').val().trim();

        if (!file && !url) {
            Swal.fire({
                icon: 'warning',
                title: 'Sumber Data Kosong',
                text: 'Silakan pilih file Excel (.xlsx, .xls, .csv) atau masukkan URL Google Spreadsheet.',
                confirmButtonColor: '#FF6D1F'
            });
            return;
        }

        const formData = new FormData();
        if (file) {
            formData.append('file_source', file);
        } else {
            formData.append('url_source', url);
        }

        executeInspectTerpadu(formData, true);
    });

    function executeInspectTerpadu(payload, isFormData = false) {
        $('#terpadu_loading').show();
        $('#terpadu_error_box').hide();
        $('#terpadu_results_section').hide();
        $('#btn_terpadu_submit').prop('disabled', true);

        const ajaxOptions = {
            url: '<?= base_url('admin/master_dimensi/ajax_inspect_dimension_source'); ?>',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(res) {
                $('#terpadu_loading').hide();
                if (res.status === 'success') {
                    currentTerpaduInspectData = res;
                    if (res.temp_filepath) {
                        currentTerpaduTempFilePath = res.temp_filepath;
                    }
                    renderInspectTerpaduResult(res);
                } else {
                    $('#terpadu_error_msg').text(res.message || 'Gagal membaca sumber data.');
                    $('#terpadu_error_box').show();
                }
            },
            error: function(xhr, status, err) {
                $('#terpadu_loading').hide();
                $('#terpadu_error_msg').text('Terjadi kesalahan koneksi server: ' + err);
                $('#terpadu_error_box').show();
            }
        };

        if (isFormData) {
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        }

        $.ajax(ajaxOptions);
    }

    // Ganti Sheet Terpadu
    $('#terpadu_sheet_dropdown').on('change', function() {
        const sheetName = $(this).val();
        const payload = { sheet_name: sheetName };
        if (currentTerpaduTempFilePath) {
            payload.temp_filepath = currentTerpaduTempFilePath;
        } else {
            payload.url_source = $('#terpadu_url_input').val().trim();
        }
        executeInspectTerpadu(payload, false);
    });

    function renderInspectTerpaduResult(data) {
        // 1. Render Dropdown Sheet
        const $shDropdown = $('#terpadu_sheet_dropdown');
        $shDropdown.empty();
        if (data.sheets && data.sheets.length > 0) {
            data.sheets.forEach(function(sh) {
                const isSelected = (sh === data.current_sheet) ? 'selected' : '';
                $shDropdown.append(`<option value="${escapeHtml(sh)}" ${isSelected}>${escapeHtml(sh)}</option>`);
            });
            $('#box_terpadu_sheet_select').show();
        } else {
            $('#box_terpadu_sheet_select').hide();
        }

        // 2. Kolom Karakteristik
        const validCols = (data.columns || []).filter(c => !c.is_row_identifier);
        const distinctParents = data.distinct_parents || [];

        // Jika terdeteksi periode waktu (seperti tahun 2025, 2026), atau jika kolom tidak memiliki sub-header berjenjang,
        // otomatis aktifkan format "Induk Saja" agar bersih (Laki-Laki, Perempuan, Jumlah) tanpa tahun sebagai sub-bab.
        const hasValidSubHeaders = validCols.some(c => (c.sub_header || '').trim() !== '');
        if (data.has_periode || !hasValidSubHeaders) {
            $('#terpadu_kolom_fmt_parent').prop('checked', true);
            $('#terpadu_kolom_fmt_tree').prop('checked', false);
        } else {
            $('#terpadu_kolom_fmt_tree').prop('checked', true);
            $('#terpadu_kolom_fmt_parent').prop('checked', false);
        }

        const isParentFmt = $('input[name="terpadu_kolom_format"]:checked').val() === 'parent';
        const displayColCount = isParentFmt ? (distinctParents.length > 0 ? distinctParents.length : validCols.length) : (distinctParents.length > 0 && !hasValidSubHeaders ? distinctParents.length : validCols.length);
        $('#terpadu_kolom_count_badge').text(`${displayColCount} Kolom Terdeteksi`);

        // Isi Usulan Nama Kelompok Kolom
        if (!$('#terpadu_nama_kolom_ind').val().trim()) {
            $('#terpadu_nama_kolom_ind').val(data.suggested_kolom_ind || data.current_sheet || '');
        }
        if (!$('#terpadu_nama_kolom_en').val().trim()) {
            $('#terpadu_nama_kolom_en').val(data.suggested_kolom_en || '');
        }

        renderTerpaduKolomPreview(validCols, distinctParents);

        // 3. Judul Baris
        const autoRows = data.auto_rows || [];
        $('#terpadu_baris_count_badge').text(`${autoRows.length} Baris Terdeteksi`);

        // Isi Usulan Nama Kelompok Baris
        if (!$('#terpadu_nama_baris_ind').val().trim()) {
            $('#terpadu_nama_baris_ind').val(data.suggested_baris_ind || 'Kabupaten/Kota');
        }
        if (!$('#terpadu_nama_baris_en').val().trim()) {
            $('#terpadu_nama_baris_en').val(data.suggested_baris_en || '');
        }

        renderTerpaduBarisPreview(autoRows);

        // 4. Periode Waktu
        const autoPeriode = data.auto_periode || [];
        $('#terpadu_periode_count_badge').text(`${autoPeriode.length} Periode Terdeteksi`);

        if (autoPeriode.length > 0) {
            $('#terpadu_opt_save_periode').prop('checked', true);
            if (!$('#terpadu_nama_periode_ind').val().trim()) {
                $('#terpadu_nama_periode_ind').val(data.suggested_periode_ind || '');
            }
            if (!$('#terpadu_nama_periode_en').val().trim()) {
                $('#terpadu_nama_periode_en').val(data.suggested_periode_en || '');
            }
        } else {
            $('#terpadu_opt_save_periode').prop('checked', false);
        }

        renderTerpaduPeriodePreview(autoPeriode);

        // 5. Render Live Preview Matriks Tabel Utuh
        renderTerpaduMatrixPreview(data);

        // Validasi real-time duplikasi nama usulan kelompok dimensi
        validatorKolomTerpadu.check();
        validatorBarisTerpadu.check();
        if (autoPeriode.length > 0) {
            validatorPeriodeTerpadu.check();
        } else {
            validatorPeriodeTerpadu.clear();
        }

        $('#terpadu_results_section').slideDown(250);
        updateTerpaduSwitchStates();
    }

    // Render Preview Kolom
    function renderTerpaduKolomPreview(validCols, distinctParents = []) {
        const $list = $('#terpadu_kolom_preview_list');
        $list.empty();
        if (validCols.length === 0 && distinctParents.length === 0) {
            $list.html('<div class="text-muted fst-italic py-2 text-center">Tidak ditemukan kolom karakteristik.</div>');
            return;
        }

        const format = $('input[name="terpadu_kolom_format"]:checked').val() || 'parent';

        if (format === 'tree') {
            let lastPKey = null;
            validCols.forEach(function(c) {
                const pName = (c.header || '').trim();
                const pKey = pName.toLowerCase();
                const sub = (c.sub_header || '').trim();

                if (pKey !== lastPKey) {
                    lastPKey = pKey;
                    $list.append(`
                        <div class="fw-bold text-dark d-flex align-items-center gap-1 mt-1 pt-1 border-top" style="font-size: 0.76rem;">
                            <span class="badge bg-primary-soft text-primary-orange border" style="font-size: 0.68rem;">Bab</span>
                            <span>${escapeHtml(pName)}</span>
                        </div>
                    `);
                }

                if (sub && sub.toLowerCase() !== pKey) {
                    $list.append(`
                        <div class="text-secondary ms-3 ps-2 border-start border-2 border-info d-flex align-items-center gap-1 my-1" style="font-size: 0.72rem;">
                            <span class="badge bg-light text-info border" style="font-size: 0.65rem;">Sub</span>
                            <span>${escapeHtml(sub)}</span>
                        </div>
                    `);
                }
            });
        } else {
            const items = distinctParents.length > 0 ? distinctParents : validCols.map(c => ({ ind: c.header, en: c.header_en }));
            const seen = new Set();
            items.forEach(function(item) {
                const pName = (item.ind || item.header || '').trim();
                const pKey = pName.toLowerCase();
                if (!seen.has(pKey)) {
                    seen.add(pKey);
                    $list.append(`
                        <div class="fw-semibold text-dark py-1 border-bottom d-flex align-items-center justify-content-between" style="font-size: 0.74rem;">
                            <span><i class="bi bi-check2 text-success me-1"></i> ${escapeHtml(pName)}</span>
                            ${item.en && item.en !== pName ? `<span class="text-muted fst-italic small">(${escapeHtml(item.en)})</span>` : ''}
                        </div>
                    `);
                }
            });
        }
    }

    $('input[name="terpadu_kolom_format"]').on('change', function() {
        if (currentTerpaduInspectData) {
            const validCols = (currentTerpaduInspectData.columns || []).filter(c => !c.is_row_identifier);
            const distinctParents = currentTerpaduInspectData.distinct_parents || [];
            const fmt = $(this).val();
            const count = fmt === 'parent' ? (distinctParents.length > 0 ? distinctParents.length : validCols.length) : validCols.length;
            $('#terpadu_kolom_count_badge').text(`${count} Kolom Terdeteksi`);
            renderTerpaduKolomPreview(validCols, distinctParents);
        }
    });

    // Render Preview Baris
    function renderTerpaduBarisPreview(autoRows) {
        const $list = $('#terpadu_baris_preview_list');
        $list.empty();
        if (autoRows.length === 0) {
            $list.html('<div class="text-muted fst-italic py-2 text-center">Tidak ditemukan item baris otomatis.</div>');
            return;
        }

        autoRows.forEach(function(row, idx) {
            const indent = row.level === 2 ? 'ms-4 ps-2 border-start border-2 border-warning' : (row.level === 1 ? 'ms-3 ps-2 border-start border-2 border-primary' : '');
            const badge = row.level === 2 ? '<span class="badge bg-light text-warning border text-xxs me-1">Sub-Sub</span>' : (row.level === 1 ? '<span class="badge bg-light text-primary border text-xxs me-1">Sub</span>' : '');
            const enText = (row.en && row.en !== row.ind) ? `<span class="text-muted ms-1 fst-italic">(${escapeHtml(row.en)})</span>` : '';

            $list.append(`
                <div class="py-1 border-bottom d-flex align-items-center ${indent}" style="font-size: 0.72rem;">
                    <span class="text-muted me-2" style="font-size: 0.68rem; min-width: 20px;">${idx + 1}.</span>
                    ${badge}
                    <span class="text-dark fw-medium">${escapeHtml(row.ind)}</span>
                    ${enText}
                </div>
            `);
        });
    }

    // Render Preview Periode
    function renderTerpaduPeriodePreview(autoPeriode) {
        const $list = $('#terpadu_periode_preview_list');
        $list.empty();
        if (!autoPeriode || autoPeriode.length === 0) {
            $list.html('<div class="text-muted fst-italic py-2 text-center">Tidak ditemukan item periode otomatis.</div>');
            return;
        }

        autoPeriode.forEach(function(item, idx) {
            const enText = (item.en && item.en !== item.ind) ? `<span class="text-muted ms-1 fst-italic">(${escapeHtml(item.en)})</span>` : '';
            $list.append(`
                <div class="py-1 border-bottom d-flex align-items-center" style="font-size: 0.72rem;">
                    <span class="text-muted me-2" style="font-size: 0.68rem; min-width: 20px;">${idx + 1}.</span>
                    <span class="badge bg-light text-success border text-xxs me-2"><i class="bi bi-calendar3 me-1"></i>Periode</span>
                    <span class="text-dark fw-bold">${escapeHtml(item.ind)}</span>
                    ${enText}
                </div>
            `);
        });
    }

    // 5. Render Live Preview Matriks Tabel dari Sheet/File
    function renderTerpaduMatrixPreview(data) {
        const $thead = $('#terpadu_matrix_thead');
        const $tbody = $('#terpadu_matrix_tbody');
        $thead.empty();
        $tbody.empty();

        const grid = data.header_grid || [];
        const sampleRows = data.sample_rows || [];
        const columns = data.columns || [];
        const bestRowIdx = (data.best_row_col_index !== undefined) ? data.best_row_col_index : 0;

        $('#terpadu_table_meta_badge').text(`${data.total_rows || 0} baris | ${columns.length} kolom data`);

        if (grid.length === 0 && columns.length === 0) {
            $thead.html('<tr><th class="py-3 text-muted fst-italic text-center">Preview struktur tabel tidak tersedia.</th></tr>');
            return;
        }

        // 1. Render Header Grid
        if (grid.length > 0) {
            grid.forEach(function(rowCells, rIdx) {
                const tr = $('<tr></tr>');
                let c = 0;
                while (c < rowCells.length) {
                    const val = rowCells[c];
                    let span = 1;
                    while (c + span < rowCells.length && rowCells[c + span] === val && val !== '') {
                        span++;
                    }

                    // Deteksi peran sel header: Baris, Kolom, atau Periode
                    const colMeta = columns[c] || {};
                    const isRowCol = colMeta.is_row_identifier || c === bestRowIdx;
                    const valLower = (val || '').toLowerCase().trim();
                    const isYearOrPeriod = /^(19|20)\d{2}(\s*[\-\/]\s*(19|20)?\d{2})?$/.test(valLower) ||
                                          /^(triwulan|tw|quarter|q)\s*[1-4ivx]+/i.test(valLower) ||
                                          /^(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)/i.test(valLower);

                    let cellBg = '#fff7ed';
                    let cellColor = '#9a3412';
                    let cellBorder = '#fed7aa';

                    if (isRowCol) {
                        cellBg = '#eff6ff';
                        cellColor = '#1e40af';
                        cellBorder = '#bfdbfe';
                    } else if (isYearOrPeriod) {
                        cellBg = '#f0fdf4';
                        cellColor = '#166534';
                        cellBorder = '#bbf7d0';
                    }

                    const style = `background: ${cellBg}; color: ${cellColor}; border: 1px solid ${cellBorder}; font-weight: bold; padding: 6px 8px;`;
                    tr.append(`<th colspan="${span}" style="${style}">${escapeHtml(val || '-')}</th>`);
                    c += span;
                }
                $thead.append(tr);
            });
        } else {
            const tr = $('<tr></tr>');
            columns.forEach(function(col) {
                const isRowCol = col.is_row_identifier;
                const cellBg = isRowCol ? '#eff6ff' : '#fff7ed';
                const cellColor = isRowCol ? '#1e40af' : '#9a3412';
                const cellBorder = isRowCol ? '#bfdbfe' : '#fed7aa';
                tr.append(`<th style="background: ${cellBg}; color: ${cellColor}; border: 1px solid ${cellBorder}; font-weight: bold; padding: 6px 8px;">${escapeHtml(col.full_title || col.header)}</th>`);
            });
            $thead.append(tr);
        }

        // 2. Render Sample Rows
        if (sampleRows.length > 0) {
            sampleRows.forEach(function(rowVals) {
                const tr = $('<tr></tr>');
                rowVals.forEach(function(cellVal, cIdx) {
                    const colMeta = columns[cIdx] || {};
                    const isRowCol = colMeta.is_row_identifier || cIdx === bestRowIdx;
                    let style = 'border: 1px solid #e2e8f0; padding: 4px 8px;';
                    if (isRowCol) {
                        style += 'background: #f8fafc; font-weight: 600; color: #1e293b; text-align: left;';
                    } else {
                        style += 'background: #ffffff; color: #334155; text-align: right;';
                    }
                    tr.append(`<td style="${style}">${escapeHtml(cellVal !== '' ? cellVal : '-')}</td>`);
                });
                $tbody.append(tr);
            });
        } else {
            $tbody.html('<tr><td colspan="100%" class="text-muted fst-italic py-2 text-center">Tidak ada baris data sampel.</td></tr>');
        }
    }

    // SIMPAN DIMENSI TERPILIH KE DATABASE
    $('#btn_terpadu_submit').on('click', function() {
        const isKolom   = $('#terpadu_opt_save_kolom').is(':checked');
        const isBaris   = $('#terpadu_opt_save_baris').is(':checked');
        const isPeriode = $('#terpadu_opt_save_periode').is(':checked');

        if (!isKolom && !isBaris && !isPeriode) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Dimensi Dipilih',
                text: 'Silakan aktifkan minimal satu pilihan: Karakteristik Kolom, Judul Baris, atau Periode Waktu.',
                confirmButtonColor: '#FF6D1F'
            });
            return;
        }

        const namaKolomInd   = $('#terpadu_nama_kolom_ind').val().trim();
        const namaKolomEn    = $('#terpadu_nama_kolom_en').val().trim();
        const namaBarisInd   = $('#terpadu_nama_baris_ind').val().trim();
        const namaBarisEn    = $('#terpadu_nama_baris_en').val().trim();
        const namaPeriodeInd = $('#terpadu_nama_periode_ind').val().trim();
        const namaPeriodeEn  = $('#terpadu_nama_periode_en').val().trim();

        if (isKolom && !namaKolomInd) {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Kelompok Kolom Wajib Diisi',
                text: 'Silakan isi Nama Kelompok Karakteristik Kolom (Bahasa Indonesia).',
                confirmButtonColor: '#FF6D1F'
            });
            $('#terpadu_nama_kolom_ind').focus();
            return;
        }

        if (isKolom && $('#terpadu_nama_kolom_ind').hasClass('is-invalid')) {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Kelompok Kolom Sudah Ada!',
                text: 'Nama kelompok karakteristik kolom sudah terdaftar di database. Silakan gunakan nama lain atau nonaktifkan switch kolom.',
                confirmButtonColor: '#FF6D1F'
            });
            $('#terpadu_nama_kolom_ind').focus();
            return;
        }

        if (isBaris && !namaBarisInd) {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Kelompok Baris Wajib Diisi',
                text: 'Silakan isi Nama Kelompok Judul Baris (Bahasa Indonesia).',
                confirmButtonColor: '#2563EB'
            });
            $('#terpadu_nama_baris_ind').focus();
            return;
        }

        if (isBaris && $('#terpadu_nama_baris_ind').hasClass('is-invalid')) {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Kelompok Baris Sudah Ada!',
                text: 'Nama kelompok judul baris sudah terdaftar di database. Silakan gunakan nama lain atau nonaktifkan switch baris jika sudah ada di tabel lain.',
                confirmButtonColor: '#2563EB'
            });
            $('#terpadu_nama_baris_ind').focus();
            return;
        }

        if (isPeriode && !namaPeriodeInd) {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Kelompok Periode Wajib Diisi',
                text: 'Silakan isi Nama Kelompok Periode Waktu (Bahasa Indonesia).',
                confirmButtonColor: '#16A34A'
            });
            $('#terpadu_nama_periode_ind').focus();
            return;
        }

        if (isPeriode && $('#terpadu_nama_periode_ind').hasClass('is-invalid')) {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Kelompok Periode Sudah Ada!',
                text: 'Nama kelompok periode waktu sudah terdaftar di database. Silakan gunakan nama lain atau nonaktifkan switch periode.',
                confirmButtonColor: '#16A34A'
            });
            $('#terpadu_nama_periode_ind').focus();
            return;
        }

        // Susun items kolom
        let linesKolomInd = [];
        let linesKolomEn  = [];
        if (isKolom && currentTerpaduInspectData) {
            const format = $('input[name="terpadu_kolom_format"]:checked').val() || 'parent';
            const validCols = (currentTerpaduInspectData.columns || []).filter(c => !c.is_row_identifier);
            const distinctParents = currentTerpaduInspectData.distinct_parents || [];

            if (format === 'tree') {
                const seenEntries = new Set();
                validCols.forEach(function(c) {
                    const pInd = (c.header || '').trim();
                    const pEn  = (c.header_en && c.header_en.trim()) ? c.header_en.trim() : pInd;
                    const sub  = (c.sub_header || '').trim();

                    let entryInd = pInd;
                    let entryEn  = pEn;
                    if (sub && sub.toLowerCase() !== pInd.toLowerCase()) {
                        entryInd = `${pInd} || ${sub}`;
                        entryEn  = `${pEn} || ${sub}`;
                    }
                    const entryKey = entryInd.toLowerCase();
                    if (!seenEntries.has(entryKey)) {
                        seenEntries.add(entryKey);
                        linesKolomInd.push(entryInd);
                        linesKolomEn.push(entryEn);
                    }
                });
            } else {
                const items = distinctParents.length > 0 ? distinctParents : validCols.map(c => ({ ind: c.header, en: c.header_en }));
                const seen = new Set();
                items.forEach(function(item) {
                    const pInd = (item.ind || item.header || '').trim();
                    const pEn  = (item.en && item.en.trim()) ? item.en.trim() : (item.header_en || pInd);
                    const pKey = pInd.toLowerCase();
                    if (!seen.has(pKey)) {
                        seen.add(pKey);
                        linesKolomInd.push(pInd);
                        linesKolomEn.push(pEn);
                    }
                });
            }
        }

        // Susun items baris
        let linesBarisInd = [];
        let linesBarisEn  = [];
        if (isBaris && currentTerpaduInspectData && currentTerpaduInspectData.auto_rows) {
            currentTerpaduInspectData.auto_rows.forEach(function(r) {
                let prefix = '';
                if (r.level === 2) prefix = '---- ';
                else if (r.level === 1) prefix = '-- ';

                linesBarisInd.push(prefix + r.ind);
                linesBarisEn.push(prefix + (r.en || r.ind));
            });
        }

        // Susun items periode
        let linesPeriodeInd = [];
        let linesPeriodeEn  = [];
        if (isPeriode && currentTerpaduInspectData && currentTerpaduInspectData.auto_periode) {
            currentTerpaduInspectData.auto_periode.forEach(function(p) {
                linesPeriodeInd.push(p.ind);
                linesPeriodeEn.push(p.en || p.ind);
            });
        }

        const postData = {
            save_kolom: isKolom ? '1' : '0',
            save_baris: isBaris ? '1' : '0',
            save_periode: isPeriode ? '1' : '0',
            nama_kolom_ind: namaKolomInd,
            nama_kolom_en: namaKolomEn,
            items_kolom_ind: linesKolomInd.join('\n'),
            items_kolom_en: linesKolomEn.join('\n'),
            nama_baris_ind: namaBarisInd,
            nama_baris_en: namaBarisEn,
            items_baris_ind: linesBarisInd.join('\n'),
            items_baris_en: linesBarisEn.join('\n'),
            nama_periode_ind: namaPeriodeInd,
            nama_periode_en: namaPeriodeEn,
            items_periode_ind: linesPeriodeInd.join('\n'),
            items_periode_en: linesPeriodeEn.join('\n')
        };

        // Kirim request simpan
        Swal.fire({
            title: 'Menyimpan Dimensi...',
            text: 'Mohon tunggu sebentar, sistem sedang mendaftarkan kelompok dimensi ke database.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '<?= base_url('admin/master_dimensi/save_terpadu_dimensi'); ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    $('#modalImportTerpadu').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan!',
                        html: resp.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = resp.redirect || window.location.href;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan Dimensi',
                        text: resp.message || 'Terjadi kesalahan sistem saat menyimpan dimensi.',
                        confirmButtonColor: '#FF6D1F'
                    });
                }
            },
            error: function(xhr, status, err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Server',
                    text: 'Terjadi kesalahan koneksi saat menyimpan: ' + err,
                    confirmButtonColor: '#FF6D1F'
                });
            }
        });
    });

    // 1. Karakteristik Kolom (Tambah & Edit)
    setupDuplicateNameValidator('#modalAddKarakteristik', '#add_karakteristik_nama_ind', 'karakteristik', null);
    setupDuplicateNameValidator('#modalEditKarakteristik', '#edit_kolom_ind', 'karakteristik', '#edit_kolom_id');

    // 2. Baris Bertingkat (Tambah & Edit)
    setupDuplicateNameValidator('#modalAddBaris', '#add_baris_nama_ind', 'baris', null);
    setupDuplicateNameValidator('#modalEditBaris', '#edit_baris_ind', 'baris', '#edit_baris_id');

    // 3. Periode Waktu (Tambah & Edit)
    setupDuplicateNameValidator('#modalAddPeriode', '#add_periode_nama_ind', 'periode', null);
    setupDuplicateNameValidator('#modalEditPeriode', '#edit_periode_ind', 'periode', '#edit_periode_id');

});
</script>
