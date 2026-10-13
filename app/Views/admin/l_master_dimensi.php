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
                    <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2"
                            style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;"
                            data-bs-toggle="modal" data-bs-target="#modalAddKarakteristik">
                        <i class="bi bi-plus-circle"></i> Tambah Kelompok Kolom
                    </button>
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
                    <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-bold d-inline-flex align-items-center gap-2"
                            style="background: linear-gradient(45deg, #2563EB, #3b82f6); border: none;"
                            data-bs-toggle="modal" data-bs-target="#modalAddBaris">
                        <i class="bi bi-plus-circle"></i> Tambah Kelompok Baris
                    </button>
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
