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

/* Styling Tombol Chip Grand Total Baris (Pilihan 1) */
.tree-item-row.is-total-row {
    background: #fffbeb !important;
    border-color: #fcd34d !important;
    border-left: 4px solid #f59e0b !important;
    box-shadow: 0 2px 6px rgba(245, 158, 11, 0.12) !important;
}
.tree-item-row.is-kategori-row {
    background-color: #fffaf5 !important;
    border-color: #fed7aa !important;
    border-left: 4px solid #ff6d1f !important;
    box-shadow: 0 2px 6px rgba(255, 109, 31, 0.08) !important;
}
.tree-item-row.is-subtotal-row {
    background-color: #fff7ed !important;
    border-color: #fed7aa !important;
    border-left: 4px solid #f97316 !important;
    box-shadow: 0 2px 6px rgba(249, 115, 22, 0.08) !important;
}
.tree-item-row:has(.dropdown.show) {
    z-index: 25;
}

/* Badge Status Fungsional Baris */
.btn-role-badge {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 82px;
    height: 27px;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.btn-role-badge.dropdown-toggle::after {
    margin-left: 5px;
    vertical-align: 0.15em;
    font-size: 0.70rem;
}
.btn-role-badge.role-data {
    background: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
}
.btn-role-badge.role-data:hover,
.btn-role-badge.role-data:focus {
    background: #dcfce7;
    color: #14532d;
    border-color: #86efac;
}
.btn-role-badge.role-kategori {
    background: #fff7ed;
    color: #c2410c;
    border-color: #fed7aa;
}
.btn-role-badge.role-kategori:hover,
.btn-role-badge.role-kategori:focus {
    background: #ffedd5;
    color: #9a3412;
    border-color: #fdba74;
}
.btn-role-badge.role-subtotal {
    background: #ffedd5;
    color: #ea580c;
    border-color: #fed7aa;
    font-weight: 700;
}
.btn-role-badge.role-subtotal:hover,
.btn-role-badge.role-subtotal:focus {
    background: #fed7aa;
    color: #c2410c;
    border-color: #fdba74;
}
.btn-role-badge.role-total {
    background: #fef3c7;
    color: #b45309;
    border-color: #fde68a;
    font-weight: 700;
}
.btn-role-badge.role-total:hover,
.btn-role-badge.role-total:focus {
    background: #fde68a;
    color: #92400e;
    border-color: #fcd34d;
}
.role-dropdown-menu {
    border-radius: 8px;
    min-width: 140px;
    font-size: 0.78rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08) !important;
    padding: 4px;
    z-index: 1060;
}
.role-dropdown-menu .dropdown-item {
    padding: 6px 10px;
    border-radius: 6px;
    font-weight: 500;
    color: #334155;
    transition: all 0.12s ease;
}
.role-dropdown-menu .dropdown-item:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
.role-dropdown-menu .dropdown-item.active {
    background-color: #f8fafc;
    color: #0f172a;
    font-weight: 600;
}
.btn-total-pill {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 2px 9px;
    border-radius: 20px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    height: 25px;
    user-select: none;
}
.btn-total-pill:hover {
    border-color: #f59e0b;
    color: #b45309;
    background: #fef3c7;
    transform: translateY(-1px);
}
.btn-total-pill.active {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: #ffffff !important;
    border-color: #d97706 !important;
    box-shadow: 0 2px 5px rgba(217, 119, 6, 0.3) !important;
}
.badge-total-row {
    background: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
    font-weight: 700 !important;
    font-size: 0.70rem !important;
    min-width: 78px !important;
    text-align: center !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 3px !important;
}

/* Master Dimensi Stat Card & Standard Typography */
.dimensi-stat-card {
    border-radius: 12px;
    border: 1px solid #e2e8f0 !important;
    background: #ffffff;
    transition: all 0.2s ease;
}
.dimensi-stat-card:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important;
}

.dimensi-card-title {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.dimensi-card-num {
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.2;
}
.dimensi-card-unit {
    font-size: 0.85rem;
    font-weight: 400;
    color: #64748b;
}

/* Master Dimensi Table Styling - Readable matching other admin pages */
.table-dimensi {
    font-size: 0.875rem;
}
.table-dimensi thead th {
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #475569;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 11px 14px;
}
.table-dimensi tbody td {
    padding: 12px 14px;
    vertical-align: middle;
}
.table-dimensi .item-title {
    font-size: 0.92rem;
    font-weight: 600;
    color: #1e293b;
}
.table-dimensi .item-sub {
    font-size: 0.8125rem;
    font-style: italic;
}
.table-dimensi .item-badge {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 5px 12px;
}
.btn-action-dimensi {
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 5px 10px;
    border-radius: 6px;
}

/* Toolbar Penomoran Otomatis Baris */
.auto-prefix-toolbar {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
}
.auto-prefix-group {
    width: auto;
    border-radius: 8px !important;
    overflow: hidden;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    transition: all 0.2s ease;
}
.auto-prefix-group:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}
.auto-prefix-label {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0 10px;
    height: 30px;
    border: none;
    border-right: 1px solid #e2e8f0;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
}
.auto-prefix-label-bab {
    background: #eff6ff;
    color: #1d4ed8;
}
.auto-prefix-label-subbab {
    background: #f0fdfa;
    color: #0f766e;
}
.auto-prefix-label-subsub {
    background: #f0fdf4;
    color: #15803d;
}
.auto-prefix-select {
    height: 30px;
    font-size: 0.75rem;
    font-weight: 500;
    color: #1e293b;
    border: none !important;
    background-color: #ffffff;
    padding-top: 0;
    padding-bottom: 0;
    padding-left: 9px;
    padding-right: 28px !important; /* Ruang lega agar teks tidak bertabrakan dengan panah dropdown */
    width: auto;
    min-width: 140px;
    cursor: pointer;
}
.auto-prefix-select:focus {
    box-shadow: none;
    border: none;
    background-color: #ffffff;
}
.btn-prefix-generate {
    background: linear-gradient(135deg, #FF6D1F, #ff8c42);
    border: none;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    height: 30px;
    padding: 0 12px;
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.btn-prefix-generate:hover,
.btn-prefix-generate:active {
    background: linear-gradient(135deg, #e55a10, #ff7a26);
    color: #ffffff;
}
.btn-prefix-clean {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 500;
    height: 30px;
    padding: 0 10px;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.btn-prefix-clean:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}

/* Kotak Pencarian Realtime Master Dimensi */
.search-dimensi-wrapper {
    position: relative;
    width: 270px;
    max-width: 100%;
}
.search-dimensi-box {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 5px 10px;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
}
.search-dimensi-box:focus-within {
    border-color: #FF6D1F;
    box-shadow: 0 0 0 3px rgba(255, 109, 31, 0.15);
}
.search-dimensi-input {
    font-size: 0.84rem;
    color: #1e293b;
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    box-shadow: none !important;
    padding-left: 6px;
    padding-right: 4px;
}
.search-dimensi-input::placeholder {
    color: #94a3b8;
    font-size: 0.82rem;
}
.btn-clear-search-dimensi {
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: color 0.15s ease;
}
.btn-clear-search-dimensi:hover {
    color: #ef4444;
}
</style>

    <!-- 3 Dimension Cards -->
    <div class="row g-3 mb-3">
        <!-- CARD 1: KARAKTERISTIK -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 dimensi-stat-card">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <div class="dimensi-card-title text-dark">
                                    Karakteristik Kolom
                                </div>
                                <div class="text-secondary" style="font-size: 0.8rem;">Kolom & Sub-kolom Asimetris</div>
                            </div>
                            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" 
                                 style="background: #FFF4ED; color: #FF6D1F; width: 42px; height: 42px;">
                                <i class="bi bi-layout-three-columns fs-5"></i>
                            </div>
                        </div>
                        <div class="my-2">
                            <span class="dimensi-card-num text-dark"><?= count($list_karakteristik); ?></span> 
                            <span class="dimensi-card-unit">kelompok</span>
                        </div>
                    </div>
                    <div class="pt-2 border-top mt-2">
                        <?php if ($active_tab === 'karakteristik'): ?>
                            <button type="button" class="btn btn-sm text-white w-100 fw-semibold py-1.5 shadow-xs" style="background: #FF6D1F; border: none; border-radius: 8px; font-size: 0.84rem;" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Sedang Ditampilkan
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('admin/master_dimensi?tab=karakteristik'); ?>" class="btn btn-sm btn-outline-secondary w-100 fw-semibold py-1.5" style="border-radius: 8px; font-size: 0.84rem;">
                                <i class="bi bi-folder2-open me-1"></i> Buka Karakteristik Kolom
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: BARIS BERTINGKAT -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 dimensi-stat-card">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <div class="dimensi-card-title text-dark">
                                    Judul Baris Bertingkat
                                </div>
                                <div class="text-secondary" style="font-size: 0.8rem;">Hierarki & Klasifikasi Baris</div>
                            </div>
                            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" 
                                 style="background: #FFF4ED; color: #FF6D1F; width: 42px; height: 42px;">
                                <i class="bi bi-list-nested fs-5"></i>
                            </div>
                        </div>
                        <div class="my-2">
                            <span class="dimensi-card-num text-dark"><?= count($list_baris); ?></span> 
                            <span class="dimensi-card-unit">kelompok</span>
                        </div>
                    </div>
                    <div class="pt-2 border-top mt-2">
                        <?php if ($active_tab === 'baris'): ?>
                            <button type="button" class="btn btn-sm text-white w-100 fw-semibold py-1.5 shadow-xs" style="background: #FF6D1F; border: none; border-radius: 8px; font-size: 0.84rem;" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Sedang Ditampilkan
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('admin/master_dimensi?tab=baris'); ?>" class="btn btn-sm btn-outline-secondary w-100 fw-semibold py-1.5" style="border-radius: 8px; font-size: 0.84rem;">
                                <i class="bi bi-folder2-open me-1"></i> Buka Baris Bertingkat
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: PERIODE WAKTU -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 dimensi-stat-card">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <div class="dimensi-card-title text-dark">
                                    Periode Waktu
                                </div>
                                <div class="text-secondary" style="font-size: 0.8rem;">Tahun, Triwulan, & Seri Waktu</div>
                            </div>
                            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" 
                                 style="background: #FFF4ED; color: #FF6D1F; width: 42px; height: 42px;">
                                <i class="bi bi-calendar3 fs-5"></i>
                            </div>
                        </div>
                        <div class="my-2">
                            <span class="dimensi-card-num text-dark"><?= count($list_periode); ?></span> 
                            <span class="dimensi-card-unit">kelompok</span>
                        </div>
                    </div>
                    <div class="pt-2 border-top mt-2">
                        <?php if ($active_tab === 'periode'): ?>
                            <button type="button" class="btn btn-sm text-white w-100 fw-semibold py-1.5 shadow-xs" style="background: #FF6D1F; border: none; border-radius: 8px; font-size: 0.84rem;" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Sedang Ditampilkan
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('admin/master_dimensi?tab=periode'); ?>" class="btn btn-sm btn-outline-secondary w-100 fw-semibold py-1.5" style="border-radius: 8px; font-size: 0.84rem;">
                                <i class="bi bi-folder2-open me-1"></i> Buka Periode Waktu
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-3 p-md-4">
            <!-- TAB 1: KARAKTERISTIK (KOLOM & SUB-KOLOM ASIMETRIS) -->
            <?php if ($active_tab === 'karakteristik'): ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Daftar Kelompok Karakteristik (Dimensi Kolom)</h5>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Kotak Pencarian Realtime Karakteristik -->
                        <div class="search-dimensi-wrapper">
                            <div class="search-dimensi-box shadow-xs">
                                <i class="bi bi-search text-muted" style="font-size: 0.85rem;"></i>
                                <input type="text" id="search_master_karakteristik" class="search-dimensi-input" placeholder="Cari kelompok karakteristik..." autocomplete="off">
                                <button type="button" class="btn-clear-search-dimensi d-none" id="clear_search_karakteristik" title="Bersihkan pencarian">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        </div>
                        <span class="badge bg-white text-secondary border shadow-xs search-count-badge d-none py-2 px-2.5" id="count_search_karakteristik" style="border-radius: 8px; font-size: 0.8rem;"></span>
                        <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs"
                                style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;"
                                data-bs-toggle="modal" data-bs-target="#modalAddKarakteristik">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Kelompok Kolom
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-dimensi" id="table_dimensi_karakteristik">
                        <thead>
                            <tr>
                                <th width="6%" class="text-center">No.</th>
                                <th width="48%">Nama Kelompok (Indonesia & English)</th>
                                <th width="22%" class="text-center">Jumlah Kolom</th>
                                <th width="24%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_karakteristik)): ?>
                                <tr class="empty-state-row">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada kelompok karakteristik kolom. Klik tombol <strong>Tambah Kelompok Kolom</strong> di atas untuk membuat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_karakteristik as $k): ?>
                                    <tr class="dimensi-item-row" data-search="<?= esc(strtolower($k->nama_kelompok_ind . ' ' . ($k->nama_kelompok_en ?? '') . ' ' . $k->total_items . ' kolom')); ?>">
                                        <td class="text-center fw-semibold text-muted row-number"><?= $no++; ?></td>
                                        <td>
                                            <div class="item-title"><?= esc($k->nama_kelompok_ind); ?></div>
                                            <?php if (!empty($k->nama_kelompok_en)): ?>
                                                <div class="text-primary item-sub"><i class="bi bi-translate me-1"></i><?= esc($k->nama_kelompok_en); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill item-badge <?= $k->total_items > 0 ? 'bg-primary-soft text-primary-orange border border-warning' : 'bg-light text-secondary border'; ?>">
                                                <i class="bi bi-columns-gap me-1"></i> <?= $k->total_items; ?> Kolom
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 btn-action-dimensi btn-manage-kolom"
                                                        data-id="<?= $k->id; ?>" data-nama="<?= esc($k->nama_kelompok_ind); ?>"
                                                        title="Kelola Kolom & Live Preview">
                                                    <i class="bi bi-gear-fill me-1"></i> Kelola Item
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border rounded-3 btn-action-dimensi btn-edit-kolom"
                                                        data-id="<?= $k->id; ?>" data-ind="<?= esc($k->nama_kelompok_ind); ?>"
                                                        data-en="<?= esc($k->nama_kelompok_en ?? ''); ?>"
                                                        title="Edit Nama Kelompok">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="<?= base_url('admin/master_dimensi/del_karakteristik/' . $k->id); ?>"
                                                   class="btn btn-sm btn-outline-danger rounded-3 btn-action-dimensi"
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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Daftar Kelompok Judul Baris (Dimensi Baris)</h5>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Kotak Pencarian Realtime Judul Baris -->
                        <div class="search-dimensi-wrapper">
                            <div class="search-dimensi-box shadow-xs">
                                <i class="bi bi-search text-muted" style="font-size: 0.85rem;"></i>
                                <input type="text" id="search_master_baris" class="search-dimensi-input" placeholder="Cari kelompok judul baris..." autocomplete="off">
                                <button type="button" class="btn-clear-search-dimensi d-none" id="clear_search_baris" title="Bersihkan pencarian">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        </div>
                        <span class="badge bg-white text-secondary border shadow-xs search-count-badge d-none py-2 px-2.5" id="count_search_baris" style="border-radius: 8px; font-size: 0.8rem;"></span>
                        <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs"
                                style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;"
                                data-bs-toggle="modal" data-bs-target="#modalAddBaris">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Kelompok Baris
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-dimensi" id="table_dimensi_baris">
                        <thead>
                            <tr>
                                <th width="6%" class="text-center">No.</th>
                                <th width="48%">Nama Kelompok Baris (Indonesia & English)</th>
                                <th width="22%" class="text-center">Jumlah Baris</th>
                                <th width="24%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_baris)): ?>
                                <tr class="empty-state-row">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada kelompok baris. Klik tombol <strong>Tambah Kelompok Baris</strong> di atas untuk membuat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_baris as $b): ?>
                                    <tr class="dimensi-item-row" data-search="<?= esc(strtolower($b->nama_kelompok_ind . ' ' . ($b->nama_kelompok_en ?? '') . ' ' . ($b->judul_baris_ind ?? '') . ' ' . ($b->judul_baris_en ?? '') . ' ' . $b->total_items . ' baris')); ?>">
                                        <td class="text-center fw-semibold text-muted row-number"><?= $no++; ?></td>
                                        <td>
                                            <div class="item-title"><?= esc($b->nama_kelompok_ind); ?></div>
                                            <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                                <span class="badge bg-light text-dark border small px-2 py-0" style="font-size: 0.75rem;" title="Judul Header Baris yang akan muncul di tabel publikasi & Excel">
                                                    <i class="bi bi-table me-1 text-primary"></i>Header: <strong><?= esc($b->judul_baris_ind ?: $b->nama_kelompok_ind); ?></strong>
                                                    <?php if (!empty($b->judul_baris_en)): ?>
                                                        <span class="text-muted fst-italic"> / <?= esc($b->judul_baris_en); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if (!empty($b->judul_sub_baris_ind)): ?>
                                                    <span class="badge bg-light text-dark border small px-2 py-0" style="font-size: 0.75rem;" title="Judul Kolom Rincian (Format 2 Kolom)">
                                                        <i class="bi bi-layout-split me-1 text-warning"></i>Rincian: <strong><?= esc($b->judul_sub_baris_ind); ?></strong>
                                                        <?php if (!empty($b->judul_sub_baris_en)): ?>
                                                            <span class="text-muted fst-italic"> / <?= esc($b->judul_sub_baris_en); ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($b->nama_kelompok_en)): ?>
                                                <div class="text-primary item-sub mt-1"><i class="bi bi-globe me-1"></i><?= esc($b->nama_kelompok_en); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill item-badge <?= $b->total_items > 0 ? 'bg-primary-soft text-primary border border-primary' : 'bg-light text-secondary border'; ?>">
                                                <i class="bi bi-list-ol me-1"></i> <?= $b->total_items; ?> Baris
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 btn-action-dimensi btn-manage-baris"
                                                        data-id="<?= $b->id; ?>" data-nama="<?= esc($b->nama_kelompok_ind); ?>"
                                                        title="Kelola Baris & Tree Preview">
                                                    <i class="bi bi-gear-fill me-1"></i> Kelola Item
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border rounded-3 btn-action-dimensi btn-edit-baris"
                                                        data-id="<?= $b->id; ?>"
                                                        data-ind="<?= esc($b->nama_kelompok_ind); ?>"
                                                        data-en="<?= esc($b->nama_kelompok_en ?? ''); ?>"
                                                        data-judul-ind="<?= esc($b->judul_baris_ind ?? $b->nama_kelompok_ind); ?>"
                                                        data-judul-en="<?= esc($b->judul_baris_en ?? $b->nama_kelompok_en ?? ''); ?>"
                                                        data-judul-sub-ind="<?= esc($b->judul_sub_baris_ind ?? ''); ?>"
                                                        data-judul-sub-en="<?= esc($b->judul_sub_baris_en ?? ''); ?>"
                                                        title="Edit Nama Kelompok & Judul Header">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="<?= base_url('admin/master_dimensi/del_baris/' . $b->id); ?>"
                                                   class="btn btn-sm btn-outline-danger rounded-3 btn-action-dimensi"
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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Daftar Kelompok Periode Waktu (Dimensi Waktu)</h5>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Kotak Pencarian Realtime Periode Waktu -->
                        <div class="search-dimensi-wrapper">
                            <div class="search-dimensi-box shadow-xs">
                                <i class="bi bi-search text-muted" style="font-size: 0.85rem;"></i>
                                <input type="text" id="search_master_periode" class="search-dimensi-input" placeholder="Cari kelompok periode..." autocomplete="off">
                                <button type="button" class="btn-clear-search-dimensi d-none" id="clear_search_periode" title="Bersihkan pencarian">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        </div>
                        <span class="badge bg-white text-secondary border shadow-xs search-count-badge d-none py-2 px-2.5" id="count_search_periode" style="border-radius: 8px; font-size: 0.8rem;"></span>
                        <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs"
                                style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;"
                                data-bs-toggle="modal" data-bs-target="#modalAddPeriode">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Kelompok Periode
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-dimensi" id="table_dimensi_periode">
                        <thead>
                            <tr>
                                <th width="6%" class="text-center">No.</th>
                                <th width="48%">Nama Kelompok Periode (Indonesia & English)</th>
                                <th width="22%" class="text-center">Jumlah Periode</th>
                                <th width="24%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_periode)): ?>
                                <tr class="empty-state-row">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada kelompok periode. Klik tombol <strong>Tambah Kelompok Periode</strong> di atas untuk membuat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_periode as $p): ?>
                                    <tr class="dimensi-item-row" data-search="<?= esc(strtolower($p->nama_kelompok_ind . ' ' . ($p->nama_kelompok_en ?? '') . ' ' . ($p->judul_periode_ind ?? '') . ' ' . ($p->judul_periode_en ?? '') . ' ' . $p->total_items . ' periode')); ?>">
                                        <td class="text-center fw-semibold text-muted row-number"><?= $no++; ?></td>
                                        <td>
                                            <div class="item-title"><?= esc($p->nama_kelompok_ind); ?></div>
                                            <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                                <span class="badge bg-light text-dark border small px-2 py-0" style="font-size: 0.75rem;" title="Judul Header jika periode dijadikan baris pada tabel">
                                                    <i class="bi bi-table me-1 text-success"></i>Header: <strong><?= esc($p->judul_periode_ind ?: 'Tahun'); ?></strong>
                                                    <?php if (!empty($p->judul_periode_en)): ?>
                                                        <span class="text-muted fst-italic">/ <?= esc($p->judul_periode_en); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($p->nama_kelompok_en)): ?>
                                                <div class="text-success item-sub mt-1"><i class="bi bi-translate me-1"></i><?= esc($p->nama_kelompok_en); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill item-badge <?= $p->total_items > 0 ? 'bg-success-soft text-success border border-success' : 'bg-light text-secondary border'; ?>">
                                                <i class="bi bi-clock-history me-1"></i> <?= $p->total_items; ?> Periode
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 btn-action-dimensi btn-manage-periode"
                                                        data-id="<?= $p->id; ?>" data-nama="<?= esc($p->nama_kelompok_ind); ?>"
                                                        title="Kelola Daftar Periode">
                                                    <i class="bi bi-gear-fill me-1"></i> Kelola Item
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border rounded-3 btn-action-dimensi btn-edit-periode"
                                                        data-id="<?= $p->id; ?>" data-ind="<?= esc($p->nama_kelompok_ind); ?>"
                                                        data-en="<?= esc($p->nama_kelompok_en ?? ''); ?>"
                                                        data-judul-ind="<?= esc($p->judul_periode_ind ?? 'Tahun'); ?>"
                                                        data-judul-en="<?= esc($p->judul_periode_en ?? ''); ?>"
                                                        title="Edit Nama Kelompok">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="<?= base_url('admin/master_dimensi/del_periode/' . $p->id); ?>"
                                                   class="btn btn-sm btn-outline-danger rounded-3 btn-action-dimensi"
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
                    <!-- Baris 1: Nama Kelompok & Judul Header Baris -->
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="bi bi-tag-fill text-primary me-1"></i>Nama Pilihan Master (Bahasa Indonesia) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nama_kelompok_ind" id="add_baris_nama_ind" class="form-control form-control-sm" placeholder="Contoh: Lapangan Usaha (17 Sektor)" required>
                                <div class="form-text text-xxs text-muted">Nama unik untuk pilihan dropdown generator tabel admin (tidak boleh kembar).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="bi bi-globe text-primary me-1"></i>Nama Pilihan Master (English)
                                </label>
                                <input type="text" name="nama_kelompok_en" id="add_baris_nama_en" class="form-control form-control-sm" placeholder="Contoh: Industry (17 Sectors)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="bi bi-table text-primary-orange me-1"></i>Judul Header Tabel Baris (Indonesia) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="judul_baris_ind" id="add_baris_judul_ind" class="form-control form-control-sm" placeholder="Contoh: Lapangan Usaha" required>
                                <div class="form-text text-xxs text-muted">Judul di kepala baris tabel publikasi & Excel (boleh sama antar kelompok). Otomatis terisi.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="bi bi-translate text-primary-orange me-1"></i>Judul Header Tabel Baris (English)
                                </label>
                                <input type="text" name="judul_baris_en" id="add_baris_judul_en" class="form-control form-control-sm" placeholder="Contoh: Industry">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="bi bi-layout-split text-primary-orange me-1"></i>Judul Kolom Rincian / Sub-Baris (Indonesia)
                                </label>
                                <input type="text" name="judul_sub_baris_ind" id="add_baris_judul_sub_ind" class="form-control form-control-sm" placeholder="Contoh: Provinsi (Opsional jika 2 Kolom)">
                                <div class="form-text text-xxs text-muted">Kosongkan jika baris biasa (1 kolom). Isi jika baris bertingkat 2 kolom (misal: Provinsi).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="bi bi-translate text-primary-orange me-1"></i>Judul Kolom Rincian / Sub-Baris (English)
                                </label>
                                <input type="text" name="judul_sub_baris_en" id="add_baris_judul_sub_en" class="form-control form-control-sm" placeholder="Contoh: Province">
                            </div>
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
                            <div class="auto-prefix-toolbar mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-xs">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-secondary border shadow-xs d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size: 0.75rem; height: 30px;">
                                        <i class="bi bi-sort-numeric-down text-primary fs-6"></i>
                                        <span class="fw-bold text-dark">Penomoran Otomatis:</span>
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="input-group input-group-sm auto-prefix-group shadow-xs">
                                        <span class="input-group-text auto-prefix-label auto-prefix-label-bab">Level 1</span>
                                        <select class="form-select form-select-sm auto-prefix-select" id="add_prefix_bab">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kapital">Huruf (A, B, C...)</option>
                                            <option value="romawi_kapital">Romawi (I, II, III...)</option>
                                            <option value="angka">Angka (1, 2, 3...)</option>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm auto-prefix-group shadow-xs">
                                        <span class="input-group-text auto-prefix-label auto-prefix-label-subbab">Level 2</span>
                                        <select class="form-select form-select-sm auto-prefix-select" id="add_prefix_subbab">
                                            <option value="none" selected>Tanpa Nomor</option>
                                            <option value="angka">Angka (1, 2, 3...)</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm auto-prefix-group shadow-xs">
                                        <span class="input-group-text auto-prefix-label auto-prefix-label-subsub">Level 3</span>
                                        <select class="form-select form-select-sm auto-prefix-select" id="add_prefix_subsub">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="romawi_kecil">Romawi (i, ii, iii...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                        </select>
                                    </div>
                                    <div class="btn-group btn-group-sm shadow-xs">
                                        <button type="button" class="btn btn-prefix-generate" id="btn_add_apply_prefix" title="Terapkan penomoran otomatis ke item">
                                            <i class="bi bi-magic"></i> Generate
                                        </button>
                                        <button type="button" class="btn btn-prefix-clean" id="btn_add_clean_prefix" title="Bersihkan nomor / huruf">
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
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #FF6D1F; border: none;">Simpan Kelompok Baris</button>
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
                        <label class="form-label fw-bold small text-dark"><i class="bi bi-tag-fill text-primary me-1"></i>Nama Pilihan Master (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kelompok_ind" id="edit_baris_ind" class="form-control" required>
                        <div class="form-text small text-muted">Nama unik di daftar master dan dropdown pilihan generator tabel.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark"><i class="bi bi-globe text-primary me-1"></i>Nama Pilihan Master (English)</label>
                        <input type="text" name="nama_kelompok_en" id="edit_baris_en" class="form-control">
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-primary-orange"><i class="bi bi-table me-1"></i>Judul Header Tabel Baris (Bahasa Indonesia) <span class="text-danger">*</span></label>
                        <input type="text" name="judul_baris_ind" id="edit_baris_judul_ind" class="form-control" placeholder="Contoh: Lapangan Usaha" required>
                        <div class="form-text small text-muted">Teks kepala baris di web publikasi dan Excel (boleh sama antar kelompok).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-primary-orange"><i class="bi bi-translate me-1"></i>Judul Header Tabel Baris (English)</label>
                        <input type="text" name="judul_baris_en" id="edit_baris_judul_en" class="form-control" placeholder="Contoh: Industry">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-primary-orange"><i class="bi bi-layout-split me-1"></i>Judul Kolom Rincian / Sub-Baris (Bahasa Indonesia)</label>
                        <input type="text" name="judul_sub_baris_ind" id="edit_baris_judul_sub_ind" class="form-control" placeholder="Contoh: Provinsi (Opsional jika 2 Kolom)">
                        <div class="form-text small text-muted">Kosongkan jika baris biasa (1 kolom). Isi jika baris bertingkat 2 kolom (misal: Provinsi).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-primary-orange"><i class="bi bi-translate me-1"></i>Judul Kolom Rincian / Sub-Baris (English)</label>
                        <input type="text" name="judul_sub_baris_en" id="edit_baris_judul_sub_en" class="form-control" placeholder="Contoh: Province">
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
                            <div class="auto-prefix-toolbar mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-xs">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-secondary border shadow-xs d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size: 0.75rem; height: 30px;">
                                        <i class="bi bi-sort-numeric-down text-primary fs-6"></i>
                                        <span class="fw-bold text-dark">Penomoran Otomatis:</span>
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="input-group input-group-sm auto-prefix-group shadow-xs">
                                        <span class="input-group-text auto-prefix-label auto-prefix-label-bab">Level 1</span>
                                        <select class="form-select form-select-sm auto-prefix-select" id="manage_prefix_bab">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kapital">Huruf (A, B, C...)</option>
                                            <option value="romawi_kapital">Romawi (I, II, III...)</option>
                                            <option value="angka">Angka (1, 2, 3...)</option>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm auto-prefix-group shadow-xs">
                                        <span class="input-group-text auto-prefix-label auto-prefix-label-subbab">Level 2</span>
                                        <select class="form-select form-select-sm auto-prefix-select" id="manage_prefix_subbab">
                                            <option value="none" selected>Tanpa Nomor</option>
                                            <option value="angka">Angka (1, 2, 3...)</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm auto-prefix-group shadow-xs">
                                        <span class="input-group-text auto-prefix-label auto-prefix-label-subsub">Level 3</span>
                                        <select class="form-select form-select-sm auto-prefix-select" id="manage_prefix_subsub">
                                            <option value="none">Tanpa Nomor</option>
                                            <option value="huruf_kecil">Huruf (a, b, c...)</option>
                                            <option value="romawi_kecil">Romawi (i, ii, iii...)</option>
                                            <option value="angka_kurung">Kurung (1), 2)...)</option>
                                        </select>
                                    </div>
                                    <div class="btn-group btn-group-sm shadow-xs">
                                        <button type="button" class="btn btn-prefix-generate" id="btn_manage_apply_prefix" title="Terapkan penomoran otomatis ke item">
                                            <i class="bi bi-magic"></i> Generate
                                        </button>
                                        <button type="button" class="btn btn-prefix-clean" id="btn_manage_clean_prefix" title="Bersihkan nomor / huruf">
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
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #FF6D1F; border: none;">
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
                            <div class="text-xxs text-muted mt-1">Nama untuk pilihan dropdown di aplikasi.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">Nama Kelompok (English)</label>
                            <input type="text" name="nama_kelompok_en" id="add_periode_nama_en" class="form-control" placeholder="Contoh: Quarterly 2025, atau Annual 2021-2025">
                            <div class="text-xxs text-muted mt-1">Label kelompok bahasa Inggris (opsional).</div>
                        </div>
                    </div>

                    <!-- Baris 1b: Judul Kolom Periode di Tabel -->
                    <div class="row g-3 mb-3 p-2 rounded-3 border" style="background: #f8fafc;">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">
                                <i class="bi bi-table text-success me-1"></i> Judul Kolom Header (Indonesia) <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="judul_periode_ind" id="add_periode_judul_ind" class="form-control form-control-sm" value="Tahun" placeholder="Contoh: Tahun, Triwulan, Bulan" required>
                            <div class="text-xxs text-muted mt-1">Teks header tabel jika periode dijadikan kolom/baris (misal: <b>Tahun</b>).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small mb-1">
                                <i class="bi bi-translate text-primary me-1"></i> Judul Kolom Header (English)
                            </label>
                            <input type="text" name="judul_periode_en" id="add_periode_judul_en" class="form-control form-control-sm" placeholder="Contoh: Year, Quarter, Month">
                            <div class="text-xxs text-muted mt-1">Label header bahasa Inggris (misal: <b>Year</b>).</div>
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
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-eye-fill text-success me-1"></i> Live Visual Header Preview</h6>
                                <div class="text-muted" style="font-size: 11px;">Simulasi posisi kolom periode waktu pada tabel seperti dalam publikasi BPS</div>
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
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #FF6D1F; border: none;">Simpan Kelompok Periode</button>
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
                    <div class="row g-2 p-2 rounded-3 border bg-light mb-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1"><i class="bi bi-table text-success me-1"></i>Judul Kolom Header (ID) <span class="text-danger">*</span></label>
                            <input type="text" name="judul_periode_ind" id="edit_periode_judul_ind" class="form-control form-control-sm" placeholder="Contoh: Tahun" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1"><i class="bi bi-translate text-primary me-1"></i>Judul Kolom Header (EN)</label>
                            <input type="text" name="judul_periode_en" id="edit_periode_judul_en" class="form-control form-control-sm" placeholder="Contoh: Year">
                        </div>
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
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-eye-fill text-success me-1"></i> Live Visual Header Preview</h6>
                                <div class="text-muted" style="font-size: 11px;">Simulasi posisi kolom periode waktu pada tabel seperti dalam publikasi BPS</div>
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
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" style="background: #FF6D1F; border: none;">
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

    // Helper pendeteksi baris Total / Grand Total
    function isBarisTotal(nama) {
        if (!nama) return false;
        let clean = stripExistingPrefix(nama).trim().toLowerCase();

        // 1. Jawa Tengah & variasi wilayah agregat provinsi
        if (clean === 'jawa tengah' || clean.startsWith('jawa tengah') || clean === 'provinsi jawa tengah' || clean.startsWith('provinsi jawa tengah') || clean === 'prov. jawa tengah' || clean === 'central java') {
            return true;
        }

        // 2. Produk Domestik Regional Bruto (PDRB) & Agregat Ekonomi Makro BPS
        if (
            clean === 'produk domestik regional bruto' || clean.startsWith('produk domestik regional bruto') ||
            clean === 'pdrb' || clean.startsWith('pdrb') ||
            clean === 'gross regional domestic product' || clean.startsWith('gross regional domestic product') ||
            clean === 'grdp'
        ) {
            return true;
        }

        // 3. Kecualikan nama indikator / variabel statistik yang memuat kata "jumlah"
        if (clean.includes('curah hujan') || clean.includes('hari hujan')) {
            return false;
        }
        if (/^jumlah\s+(curah|hari|penduduk|kasus|sekolah|guru|murid|jiwa|balita|kk|desa|kelurahan|sarana|penerima|armada|tindak|kejadian|korban|kelahiran|kematian|pasien|peserta|tenaga)/i.test(clean)) {
            return false;
        }

        // 4. Ambil kata dasar sebelum karakter '/' atau '('
        let base = clean.split(/[\/\(]/)[0].trim();

        const totalTerms = [
            'jumlah',
            'total',
            'grand total',
            'grandtotal',
            'jumlah total',
            'jumlah seluruhnya',
            'total seluruhnya',
            'produk domestik regional bruto',
            'pdrb',
            'gross regional domestic product',
            'grdp',
            'all'
        ];

        if (totalTerms.includes(clean) || totalTerms.includes(base)) {
            return true;
        }

        // 5. Pola dwibahasa murni seperti "jumlah / total", "total / grand total"
        if (/^(jumlah|total|grand\s*total)\s*(\/|\/\/)\s*(total|grand\s*total|jumlah)$/i.test(clean)) {
            return true;
        }

        return false;
    }

    // Helper pendeteksi baris Subtotal
    function isBarisSubtotal(nama) {
        if (!nama) return false;
        let clean = stripExistingPrefix(nama).trim().toLowerCase();
        if (clean.includes('curah hujan') || clean.includes('hari hujan')) return false;
        if (/^jumlah\s+(curah|hari|penduduk|kasus|sekolah|guru|murid|jiwa|balita|kk|desa|kelurahan|sarana|penerima|armada|tindak|kejadian|korban|kelahiran|kematian|pasien|peserta|tenaga)/i.test(clean)) {
            return false;
        }
        let base = clean.split(/[\/\(]/)[0].trim();
        if (base === 'subtotal' || base === 'sub total' || base === 'sub-total') return true;
        if (clean.startsWith('subtotal') || clean.startsWith('sub total') || clean.startsWith('sub-total') || clean.startsWith('sub jumlah') || clean.startsWith('sub-jumlah')) return true;
        if (base.startsWith('jumlah ') || base.startsWith('total ')) return true;
        return false;
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

        addItem(parentId = null, level = 0, ind = '', en = '', isTotal = null, tipe_baris = null, db_id = null) {
            const autoTotal = (this.type === 'baris') ? isBarisTotal(ind) : false;
            let role = tipe_baris;
            if (!role) {
                if (isTotal !== null) {
                    role = Boolean(isTotal) ? 'total' : 'data';
                } else if (autoTotal) {
                    role = 'total';
                } else {
                    role = 'data';
                }
            }
            const item = {
                id: 'node_' + (++this.idSeq),
                db_id: db_id || null,
                parentId: parentId,
                level: level,
                ind: ind,
                en: en,
                tipe_baris: role,
                isTotal: (role === 'total'),
                isUserToggled: (tipe_baris !== null || isTotal !== null)
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
            const self = this;
            this.items = (items || []).map(it => {
                const autoTotal = (self.type === 'baris') ? isBarisTotal(it.ind || '') : false;
                let role = it.tipe_baris;
                if (!role) {
                    if (it.isTotal !== undefined && Boolean(it.isTotal)) {
                        role = 'total';
                    } else if (autoTotal) {
                        role = 'total';
                    } else {
                        role = 'data';
                    }
                }
                return {
                    id: it.id || ('node_' + (++self.idSeq)),
                    db_id: it.db_id || null,
                    parentId: it.parentId !== undefined ? it.parentId : null,
                    level: parseInt(it.level, 10) || 0,
                    ind: it.ind || '',
                    en: it.en || '',
                    tipe_baris: role,
                    isTotal: (role === 'total'),
                    isUserToggled: it.isUserToggled !== undefined ? it.isUserToggled : (it.tipe_baris !== undefined)
                };
            });
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
                let leftBadgeHtml  = '';

                if (self.type === 'baris') {
                    // Konsep B: Role Badge Dropdown Interaktif
                    const currentRole = item.tipe_baris || (item.isTotal ? 'total' : 'data');
                    let roleIcon = '📊';
                    let roleLabel = 'Data';
                    let roleClass = 'role-data';

                    if (currentRole === 'kategori') {
                        roleIcon = '🏷️';
                        roleLabel = 'Kategori';
                        roleClass = 'role-kategori';
                        placeholderInd = 'Judul Kategori / Sekat (Indonesia)';
                        placeholderEn  = 'Category Header (English)';
                    } else if (currentRole === 'subtotal') {
                        roleIcon = '∑₍ₛ₎';
                        roleLabel = 'Subtotal';
                        roleClass = 'role-subtotal';
                        placeholderInd = 'Subtotal (Indonesia)';
                        placeholderEn  = 'Subtotal (English)';
                    } else if (currentRole === 'total') {
                        roleIcon = '∑';
                        roleLabel = 'Total';
                        roleClass = 'role-total';
                        placeholderInd = 'Grand Total (Indonesia)';
                        placeholderEn  = 'Grand Total (English)';
                    } else {
                        roleIcon = '📊';
                        roleLabel = 'Data';
                        roleClass = 'role-data';
                        placeholderInd = `Level ${item.level + 1} (Indonesia)`;
                        placeholderEn  = `Level ${item.level + 1} (English)`;
                    }

                    leftBadgeHtml = `
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-role-badge ${roleClass} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Ubah status fungsional baris">
                                <span>${roleIcon}</span> <span class="ms-1">${roleLabel}</span>
                            </button>
                            <ul class="dropdown-menu role-dropdown-menu shadow-sm">
                                <li>
                                    <a class="dropdown-item ${currentRole === 'data' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="data" href="#">
                                        <span>📊</span>
                                        <span>Baris Data</span>
                                        ${currentRole === 'data' ? '<i class="bi bi-check2 ms-auto text-success fw-bold"></i>' : ''}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item ${currentRole === 'kategori' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="kategori" href="#">
                                        <span>🏷️</span>
                                        <span>Kategori</span>
                                        ${currentRole === 'kategori' ? '<i class="bi bi-check2 ms-auto text-warning fw-bold"></i>' : ''}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item ${currentRole === 'subtotal' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="subtotal" href="#">
                                        <span class="fw-bold" style="font-size: 0.95em;">∑₍ₛ₎</span>
                                        <span>Subtotal</span>
                                        ${currentRole === 'subtotal' ? '<i class="bi bi-check2 ms-auto text-primary fw-bold"></i>' : ''}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item ${currentRole === 'total' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="total" href="#">
                                        <span class="fw-bold" style="font-size: 0.95em;">∑</span>
                                        <span>Total</span>
                                        ${currentRole === 'total' ? '<i class="bi bi-check2 ms-auto text-danger fw-bold"></i>' : ''}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    `;
                } else if (self.allowSub) {
                    // Untuk kolom atau builder bertingkat non-baris: Level 1 sampai Level 5
                    const lvlNum = item.level + 1;
                    placeholderInd = `Level ${lvlNum} (Indonesia)`;
                    placeholderEn  = `Level ${lvlNum} (English)`;
                    const badgeClasses = ['text-primary', 'text-info', 'text-success', 'text-warning', 'text-purple', 'text-secondary'];
                    const bColor = badgeClasses[item.level] || 'text-secondary';
                    leftBadgeHtml  = `<span class="badge bg-light ${bColor} border small" style="font-size: 0.72rem; min-width: 52px; text-align: center;">Level ${lvlNum}</span>`;
                }

                const isTotalActive = (self.type === 'baris' && item.tipe_baris === 'total');
                const isKategoriActive = (self.type === 'baris' && item.tipe_baris === 'kategori');
                const isSubtotalActive = (self.type === 'baris' && item.tipe_baris === 'subtotal');

                let rowExtraClass = '';
                if (isTotalActive) rowExtraClass = 'is-total-row';
                else if (isKategoriActive) rowExtraClass = 'is-kategori-row';
                else if (isSubtotalActive) rowExtraClass = 'is-subtotal-row';

                const rowClass = `tree-item-row level-${item.level} ${self.theme} ${rowExtraClass}`;

                const row = $(`
                    <div class="${rowClass}" data-id="${item.id}">
                        <div class="d-flex align-items-center gap-2">
                            <div class="tree-badge-container">${leftBadgeHtml}</div>
                            <div class="tree-item-inputs">
                                <input type="text" class="form-control form-control-sm item-input-ind ${isTotalActive || isKategoriActive || isSubtotalActive ? 'fw-bold text-dark' : ''}" placeholder="${placeholderInd}" value="${escapeHtml(item.ind)}">
                                <input type="text" class="form-control form-control-sm item-input-en ${isTotalActive || isKategoriActive || isSubtotalActive ? 'fw-bold text-dark' : ''}" placeholder="${placeholderEn}" value="${escapeHtml(item.en)}">
                            </div>
                            <div class="tree-item-actions">
                                ${(self.allowSub && item.level < self.maxDepth && !isTotalActive && !isSubtotalActive) ? `<button type="button" class="btn btn-sm btn-sub-badge btn-add-sub" title="Tambah Sub-Level"><i class="bi bi-plus-lg"></i> Sub</button>` : ''}
                                <button type="button" class="btn btn-sm btn-delete-item btn-remove" title="Hapus"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>
                `);

                // Event listener ganti peran fungsional baris (Konsep B)
                row.find('.set-role').on('click', function(e) {
                    e.preventDefault();
                    const newRole = $(this).data('role');
                    item.tipe_baris = newRole;
                    item.isTotal = (newRole === 'total');
                    item.isUserToggled = true;
                    self.render();
                    self.notify();
                });

                row.find('.item-input-ind').on('input', function() {
                    item.ind = $(this).val();
                    if (self.type === 'baris' && !item.isUserToggled) {
                        const autoTotal = isBarisTotal(item.ind);
                        const autoSubtotal = isBarisSubtotal(item.ind);
                        const lower = (item.ind || '').toLowerCase().trim();
                        let detectedRole = 'data';
                        if (autoTotal) {
                            detectedRole = 'total';
                        } else if (autoSubtotal) {
                            detectedRole = 'subtotal';
                        } else if (lower === 'kabupaten' || lower === 'kota') {
                            detectedRole = 'kategori';
                        }
                        if (item.tipe_baris !== detectedRole) {
                            item.tipe_baris = detectedRole;
                            item.isTotal = (detectedRole === 'total');
                            self.render();
                            self.listEl.find(`[data-id="${item.id}"] .item-input-ind`).focus();
                        }
                    }
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
                    linesEn.push(chain.map(x => (x.en || '')).join(' || '));
                });
            } else if (this.type === 'baris') {
                this.items.forEach(it => {
                    let prefix = '';
                    if (it.level === 2) prefix = '---- ';
                    else if (it.level === 1) prefix = '-- ';
                    linesInd.push(prefix + it.ind);
                    linesEn.push(prefix + (it.en || ''));
                });
            } else { // periode
                this.items.forEach(it => {
                    linesInd.push(it.ind);
                    linesEn.push(it.en || '');
                });
            }

            const textInd = linesInd.join('\n');
            const textEn  = linesEn.join('\n');
            this.hiddenInd.val(textInd);
            this.hiddenEn.val(textEn);

            // Serialisasi data JSON lengkap dengan flag is_total & tipe_baris
            const itemsList = this.items;
            const jsonPayload = itemsList.map((it, idx) => {
                let tipe = it.tipe_baris || 'data';
                if (it.isTotal) {
                    tipe = 'total';
                }
                return {
                    id: it.id,
                    db_id: it.db_id || null,
                    parentId: it.parentId,
                    level: it.level,
                    ind: it.ind,
                    en: it.en,
                    is_total: (tipe === 'total') ? 1 : 0,
                    tipe_baris: tipe
                };
            });
            this.hiddenJson.val(JSON.stringify(jsonPayload));

            const count = (this.type === 'kolom') ? linesInd.length : this.items.length;
            if (this.countBadge) {
                this.countBadge.text(count + ' ' + this.itemLabel);
            }

            this.onChange(textInd, textEn, jsonPayload);
        }

        applyAutoPrefix(rules) {
            let babCounter = 0;
            let subbabCounter = 0;
            let subsubCounter = 0;

            this.items.forEach((item) => {
                let cleanInd = stripExistingPrefix(item.ind);
                let cleanEn  = item.en ? stripExistingPrefix(item.en) : '';

                let lower = cleanInd.toLowerCase();
                let isAggregate = isBarisTotal(cleanInd) || isBarisSubtotal(cleanInd);

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
                    item.en = cleanEn; // Penomoran otomatis hanya diterapkan pada Bahasa Indonesia
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
    function parseKolomLinesToStructure(textInd, textEn, treeItems = null) {
        if (treeItems && Array.isArray(treeItems) && treeItems.length > 0) {
            const itemMap = {};
            const rootItems = [];
            let maxDepth = 1;

            treeItems.forEach(it => {
                const lvl = parseInt(it.level, 10) || 0;
                itemMap[it.id] = {
                    id: it.id,
                    parentId: it.parentId || null,
                    level: lvl,
                    title_ind: it.ind || '',
                    title_en: it.en || '',
                    children: []
                };
                if ((lvl + 1) > maxDepth) {
                    maxDepth = lvl + 1;
                }
            });

            treeItems.forEach(it => {
                const node = itemMap[it.id];
                if (node.parentId && itemMap[node.parentId]) {
                    itemMap[node.parentId].children.push(node);
                } else {
                    rootItems.push(node);
                }
            });

            return {
                root: { children: rootItems },
                maxDepth: Math.max(1, maxDepth),
                hasItems: rootItems.length > 0
            };
        }

        const linesInd = (textInd || '').split(/\r?\n/).filter(l => l.trim() !== '');
        const linesEn  = (textEn || '').split(/\r?\n/).filter(l => l.trim() !== '');

        const rootItems = [];
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

            let currChildren = rootItems;
            partsInd.forEach(function(part, pIdx) {
                let enPart = (partsEn && partsEn[pIdx] !== undefined) ? partsEn[pIdx] : '';
                const isLeaf = (pIdx === partsInd.length - 1);

                if (!isLeaf) {
                    let found = currChildren.find(c => c.title_ind === part && c.isGroup);
                    if (!found) {
                        found = {
                            title_ind: part,
                            title_en: enPart,
                            level: pIdx,
                            isGroup: true,
                            children: []
                        };
                        currChildren.push(found);
                    }
                    currChildren = found.children;
                } else {
                    // Daun (leaf) selalu dibuat tersendiri agar nama sama tetap kerender
                    const leaf = {
                        title_ind: part,
                        title_en: enPart,
                        level: pIdx,
                        isGroup: false,
                        children: []
                    };
                    currChildren.push(leaf);
                }
            });
        });

        return {
            root: { children: rootItems },
            maxDepth: Math.max(1, maxDepth),
            hasItems: linesInd.length > 0
        };
    }

    function countKolomLeaves(node) {
        const children = Array.isArray(node.children) ? node.children : Object.values(node.children || {});
        if (children.length === 0) return 1;
        let total = 0;
        children.forEach(c => {
            total += countKolomLeaves(c);
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

        const rows = [];
        for (let r = 0; r < maxDepth; r++) {
            const bg = (r === 0) ? '#f8fafc' : (r === 1 ? '#f1f5f9' : '#e2e8f0');
            rows.push($('<tr style="background: ' + bg + '; font-weight: bold; border-bottom: 1px solid #cbd5e1;"></tr>'));
        }

        // Header kolom Baris mencakup seluruh tingkatan (rowspan = maxDepth)
        rows[0].append('<th rowspan="' + maxDepth + '" style="width: 140px; background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 10px;">' + barisTitle + '</th>');

        function renderNode(node, currentLevel) {
            const children = Array.isArray(node.children) ? node.children : Object.values(node.children || {});
            const leaves = countKolomLeaves(node);
            const hasKids = children.length > 0;
            const label = formatLabel(node.title_ind, node.title_en, langMode);

            if (!hasKids) {
                const rowspan = maxDepth - currentLevel;
                rows[currentLevel].append('<th rowspan="' + rowspan + '" style="background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 8px 12px; font-size: ' + (rowspan === 1 ? '11px' : '13px') + ';">' + label + '</th>');
            } else {
                const isAllKidsLeaves = children.every(c => (!c.children || (Array.isArray(c.children) ? c.children.length === 0 : Object.keys(c.children).length === 0)));
                if (isAllKidsLeaves && currentLevel < maxDepth - 1) {
                    const rowspan = (maxDepth - 1) - currentLevel;
                    rows[currentLevel].append('<th colspan="' + leaves + '" rowspan="' + rowspan + '" style="background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 8px 12px; color: #1e293b;">' + label + '</th>');
                    children.forEach(c => renderNode(c, maxDepth - 1));
                } else {
                    rows[currentLevel].append('<th colspan="' + leaves + '" style="background: #e2e8f0; border: 1px solid #cbd5e1; padding: 8px 12px; color: #1e293b;">' + label + '</th>');
                    children.forEach(c => renderNode(c, currentLevel + 1));
                }
            }
        }

        const rootChildren = Array.isArray(structure.root.children) ? structure.root.children : Object.values(structure.root.children || {});
        rootChildren.forEach(c => renderNode(c, 0));

        rows.forEach(tr => thead.append(tr));
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
        maxDepth: 4,
        type: 'kolom',
        itemLabel: 'Kolom',
        onChange: function(textInd, textEn, jsonPayload) {
            const struct = parseKolomLinesToStructure(textInd, textEn, jsonPayload);
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
        maxDepth: 4,
        type: 'kolom',
        itemLabel: 'Kolom',
        onChange: function(textInd, textEn, jsonPayload) {
            const struct = parseKolomLinesToStructure(textInd, textEn, jsonPayload);
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
                    const segmentsInd = [];
                    const segmentsEn  = [];

                    if (it.grup_induk_ind && !isStandalone) {
                        segmentsInd.push(it.grup_induk_ind.trim());
                        segmentsEn.push(it.grup_induk_en ? it.grup_induk_en.trim() : '');
                    }
                    if (it.grup_sub_ind) {
                        segmentsInd.push(it.grup_sub_ind.trim());
                        segmentsEn.push(it.grup_sub_en ? it.grup_sub_en.trim() : '');
                    }
                    if (it.item_ind && it.item_ind.includes('||')) {
                        const partsInd = it.item_ind.split('||').map(s => s.trim()).filter(Boolean);
                        const partsEn  = it.item_en ? it.item_en.split('||').map(s => s.trim()).filter(Boolean) : [];
                        partsInd.forEach((p, idx) => {
                            segmentsInd.push(p);
                            segmentsEn.push(partsEn[idx] || '');
                        });
                    } else if (it.item_ind) {
                        segmentsInd.push(it.item_ind.trim());
                        segmentsEn.push(it.item_en ? it.item_en.trim() : '');
                    }

                    if (segmentsInd.length <= 1) {
                        const standaloneNode = {
                            id: 'node_' + (++idSeq),
                            db_id: it.id,
                            parentId: null,
                            level: 0,
                            ind: segmentsInd[0] || it.item_ind,
                            en: segmentsEn[0] || (it.item_en || '')
                        };
                        treeItems.push(standaloneNode);
                    } else {
                        let parentId = null;
                        let pathKey = '';
                        const totalSegs = segmentsInd.length;
                        segmentsInd.forEach((seg, sIdx) => {
                            pathKey = pathKey ? (pathKey + ' > ' + seg) : seg;
                            const enPart = segmentsEn[sIdx] || '';
                            const isLeaf = (sIdx === totalSegs - 1);
                            if (!isLeaf) {
                                if (!parentMap[pathKey]) {
                                    const node = {
                                        id: 'node_' + (++idSeq),
                                        parentId: parentId,
                                        level: sIdx,
                                        ind: seg,
                                        en: enPart
                                    };
                                    parentMap[pathKey] = node;
                                    treeItems.push(node);
                                }
                                parentId = parentMap[pathKey].id;
                            } else {
                                const leafNode = {
                                    id: 'node_' + (++idSeq),
                                    db_id: it.id,
                                    parentId: parentId,
                                    level: sIdx,
                                    ind: seg,
                                    en: enPart
                                };
                                treeItems.push(leafNode);
                            }
                        });
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
    function parseBarisLines(textInd, textEn, treeItems = null) {
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

            // Cek apakah item ditandai sebagai total oleh tree manager
            const matchingTreeItem = treeItems ? (treeItems[idx] || null) : null;
            const isExplicitTotal = matchingTreeItem ? Boolean(matchingTreeItem.is_total || matchingTreeItem.isTotal) : false;

            items.push({
                no_urut: idx + 1,
                nama_baris_ind: namaInd,
                nama_baris_en: namaEn || '',
                level_indentasi: indent,
                isExplicitTotal: isExplicitTotal
            });
        });

        items.forEach(function(item, idx) {
            let tipe = 'data';
            let lower = item.nama_baris_ind.toLowerCase().trim();

            if (item.isExplicitTotal || isBarisTotal(item.nama_baris_ind)) {
                tipe = 'total';
            } else if (isBarisSubtotal(item.nama_baris_ind)) {
                tipe = 'subtotal';
            } else {
                const matchingTree = treeItems ? (treeItems[idx] || null) : null;
                if (matchingTree && matchingTree.tipe_baris) {
                    tipe = matchingTree.tipe_baris;
                } else if (lower === 'kabupaten' || lower === 'kota') {
                    tipe = 'kategori';
                } else {
                    tipe = 'data';
                }
            }

            item.tipe_baris = tipe;
            item.is_bold = (tipe === 'total' || tipe === 'kategori' || tipe === 'subtotal' || item.level_indentasi === 0) ? 1 : 0;
            delete item.isExplicitTotal;
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
            let isBold = parseInt(item.is_bold) === 1 || item.tipe_baris === 'total' || item.tipe_baris === 'kategori' || item.tipe_baris === 'subtotal';
            let style = isBold ? 'font-weight: bold;' : '';
            let bg = '';
            let badgeClass = 'bg-light text-secondary border';
            let badgeText = 'Data';

            if (item.tipe_baris === 'kategori') {
                bg = 'background-color: #fffaf5; border-left: 3px solid #ff6d1f;';
                badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold';
                badgeText = '🏷️ Kategori';
            } else if (item.tipe_baris === 'total') {
                bg = 'background-color: #fef3c7; border-top: 2px solid #d97706; border-bottom: 3px double #d97706;';
                badgeClass = 'bg-warning text-dark fw-bold';
                badgeText = '∑ Grand Total';
            } else if (item.tipe_baris === 'subtotal') {
                bg = 'background-color: #f9a066; color: #000000; border: 1px solid #ffffff;';
                badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold';
                badgeText = '∑ Subtotal';
            } else {
                if (item.level_indentasi === 1) {
                    badgeText = '📊 Data (L2)';
                    badgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                } else if (item.level_indentasi === 2) {
                    badgeText = '📊 Data (L3)';
                    badgeClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                } else {
                    badgeText = '📊 Data (L1)';
                    badgeClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
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
        onChange: function(textInd, textEn, treeItems) {
            const items = parseBarisLines(textInd, textEn, treeItems);
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
        onChange: function(textInd, textEn, treeItems) {
            const items = parseBarisLines(textInd, textEn, treeItems);
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

    // Auto-fill Judul Header Baris dari Nama Pilihan Master (Add Modal)
    let isAddJudulIndTouched = false;
    let isAddJudulEnTouched = false;

    $('#add_baris_judul_ind').on('input change', function() {
        isAddJudulIndTouched = true;
    });
    $('#add_baris_judul_en').on('input change', function() {
        isAddJudulEnTouched = true;
    });

    $('#add_baris_nama_ind').on('input', function() {
        if (!isAddJudulIndTouched) {
            // Bersihkan teks di dalam tanda kurung/siku, misal: "Lapangan Usaha (17 Sektor)" -> "Lapangan Usaha"
            let clean = $(this).val().replace(/\s*[\(\[].*?[\)\]]/g, '').trim();
            $('#add_baris_judul_ind').val(clean || $(this).val());
        }
    });

    $('#add_baris_nama_en').on('input', function() {
        if (!isAddJudulEnTouched) {
            let clean = $(this).val().replace(/\s*[\(\[].*?[\)\]]/g, '').trim();
            $('#add_baris_judul_en').val(clean || $(this).val());
        }
    });

    $('#modalAddBaris').on('show.bs.modal', function() {
        isAddJudulIndTouched = false;
        isAddJudulEnTouched = false;
    });

    // Modal Edit Baris
    $('.btn-edit-baris').on('click', function() {
        $('#edit_baris_id').val($(this).data('id'));
        $('#edit_baris_ind').val($(this).data('ind'));
        $('#edit_baris_en').val($(this).data('en'));
        $('#edit_baris_judul_ind').val($(this).data('judul-ind') || $(this).data('ind'));
        $('#edit_baris_judul_en').val($(this).data('judul-en') || $(this).data('en') || '');
        $('#edit_baris_judul_sub_ind').val($(this).data('judul-sub-ind') || '');
        $('#edit_baris_judul_sub_en').val($(this).data('judul-sub-en') || '');
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
                    const isTotal = (it.tipe_baris === 'total' || isBarisTotal(it.nama_baris_ind));
                    const role = it.tipe_baris || (isTotal ? 'total' : 'data');
                    const node = {
                        id: 'node_' + (++idSeq),
                        db_id: it.id,
                        parentId: parentId,
                        level: lvl,
                        ind: it.nama_baris_ind,
                        en: it.nama_baris_en || '',
                        isTotal: (role === 'total'),
                        tipe_baris: role,
                        isUserToggled: Boolean(it.tipe_baris)
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
                nama_periode_en:  lEn || ''
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

        // Cek apakah item periode mengandung pemisah bertingkat '||'
        const hasMulti = items.some(it => (it.nama_periode_ind || '').includes('||'));

        if (hasMulti) {
            // Periode Bertingkat (misal: Tahun || Bulan atau Tahun || Triwulan)
            const parsedItems = items.map(it => {
                const indParts = (it.nama_periode_ind || '').split('||').map(s => s.trim());
                const enParts  = (it.nama_periode_en || '').split('||').map(s => s.trim());
                return {
                    parentInd: indParts[0] || '',
                    childInd:  indParts[1] || indParts[0] || '',
                    parentEn:  enParts[0] || '',
                    childEn:   enParts[1] || ''
                };
            });

            // Kelompokkan tingkat 1 (Parent) yang berurutan
            const groups = [];
            let currGroup = null;
            parsedItems.forEach(it => {
                if (!currGroup || currGroup.parentInd !== it.parentInd) {
                    currGroup = {
                        parentInd: it.parentInd,
                        parentEn:  it.parentEn,
                        children: [it]
                    };
                    groups.push(currGroup);
                } else {
                    currGroup.children.push(it);
                }
            });

            // Baris 1: Kolom Baris (rowspan 2) + Header Tingkat 1 (Parent Periode dengan colspan)
            let trHead1 = $('<tr style="background: #f1f5f9; font-weight: bold; border-bottom: 1px solid #cbd5e1;"></tr>');
            trHead1.append('<th rowspan="2" style="width: 160px; background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 10px; color: #1e293b; text-align: center;">' + barisTitle + '</th>');

            groups.forEach(function(g) {
                let pLbl = formatLabel(g.parentInd, g.parentEn, langMode);
                trHead1.append('<th colspan="' + g.children.length + '" style="border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 13px; background: #f8fafc; color: #1e293b; text-align: center;">' + pLbl + '</th>');
            });
            thead.append(trHead1);

            // Baris 2: Header Tingkat 2 (Child Periode)
            let trHead2 = $('<tr style="background: #ffffff; font-weight: 600; border-bottom: 2px solid #cbd5e1;"></tr>');
            parsedItems.forEach(function(it) {
                let cLbl = formatLabel(it.childInd, it.childEn, langMode);
                trHead2.append('<th style="border: 1px solid #cbd5e1; padding: 6px 12px; font-size: 12px; background: #ffffff; color: #1e293b; text-align: center;">' + cLbl + '</th>');
            });
            thead.append(trHead2);

        } else {
            // Header Standar (1 Baris)
            let trHead = $('<tr style="background: #f1f5f9; font-weight: bold; border-bottom: 2px solid #cbd5e1;"></tr>');
            trHead.append('<th style="width: 160px; background: #e2e8f0; border: 1px solid #cbd5e1; vertical-align: middle; padding: 10px; color: #1e293b; text-align: center;">' + barisTitle + '</th>');

            items.forEach(function(item) {
                let pLbl = formatLabel(item.nama_periode_ind, item.nama_periode_en, langMode);
                trHead.append('<th style="border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 13px; background: #ffffff; color: #1e293b; text-align: center;">' + pLbl + '</th>');
            });

            thead.append(trHead);
        }

        $table.append(thead);

        let tbody = $('<tbody></tbody>');
        ['Baris 1', 'Baris 2'].forEach(function(bName, bIdx) {
            let trBody = $('<tr></tr>');
            let bLbl = formatLabel(bName, 'Row ' + (bIdx + 1), langMode);
            trBody.append('<td class="text-start py-2 px-3 fw-semibold text-secondary" style="background: #fafafa; border: 1px solid #e2e8f0;">' + bLbl + '</td>');
            for (let i = 0; i < count; i++) {
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
        $('#edit_periode_judul_ind').val($(this).data('judul-ind') || 'Tahun');
        $('#edit_periode_judul_en').val($(this).data('judul-en') || '');
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
                        db_id: item.id,
                        parentId: null,
                        level: 0,
                        ind: item.nama_periode_ind,
                        en: item.nama_periode_en || ''
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

    // =========================================================================
    // FITUR PENCARIAN REALTIME MASTER DIMENSI (BARIS, KARAKTERISTIK, PERIODE)
    // =========================================================================
    function initDimensiLiveSearch(inputId, tableId, clearBtnId, countBadgeId, itemType) {
        const $input = $(inputId);
        const $table = $(tableId);
        const $clearBtn = $(clearBtnId);
        const $countBadge = $(countBadgeId);

        if ($input.length === 0 || $table.length === 0) return;

        function doSearch() {
            const rawQuery = $input.val();
            const query = $.trim(rawQuery).toLowerCase();
            const $rows = $table.find('tbody tr.dimensi-item-row');
            const totalRows = $rows.length;

            // Hapus baris 'no results' sebelumnya jika ada
            $table.find('tbody tr.no-search-results').remove();

            if (query === '') {
                $rows.show();
                $clearBtn.addClass('d-none');
                $countBadge.addClass('d-none').empty();

                // Re-number urutan asli 1..N
                let idx = 1;
                $rows.each(function() {
                    $(this).find('.row-number').text(idx++);
                });
                return;
            }

            // Tampilkan tombol pembersih (x)
            $clearBtn.removeClass('d-none');

            let matchedCount = 0;
            $rows.each(function() {
                const $row = $(this);
                const searchData = ($row.attr('data-search') || '').toLowerCase();
                const textContent = $row.text().toLowerCase();

                // Pencarian multi-field (nama ind, nama en, header, total item)
                if (searchData.indexOf(query) !== -1 || textContent.indexOf(query) !== -1) {
                    $row.show();
                    matchedCount++;
                    $row.find('.row-number').text(matchedCount);
                } else {
                    $row.hide();
                }
            });

            // Tampilkan counter info hasil pencarian
            if ($countBadge.length) {
                $countBadge.removeClass('d-none').html(
                    '<i class="bi bi-filter text-primary me-1"></i>Menampilkan <strong>' + matchedCount + '</strong> dari ' + totalRows + ' ' + itemType
                );
            }

            // Jika tidak ada data yang cocok
            if (matchedCount === 0 && totalRows > 0) {
                const noResultHtml = `
                    <tr class="no-search-results">
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-search fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            <div>Tidak ada kelompok ${itemType} yang sesuai dengan pencarian "<strong>${escapeHtml(rawQuery)}</strong>".</div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill btn-reset-search-custom">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Bersihkan Pencarian
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                $table.find('tbody').append(noResultHtml);
            }
        }

        // Listener saat mengetik langsung
        $input.on('input keyup paste change', doSearch);

        // Listener klik tombol clear (x)
        $clearBtn.on('click', function() {
            $input.val('').trigger('input').focus();
        });

        // Listener klik tombol reset di baris 'no results'
        $table.on('click', '.btn-reset-search-custom', function() {
            $input.val('').trigger('input').focus();
        });

        // Keyboard shortcut: Escape untuk membatalkan search
        $input.on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if ($input.val() !== '') {
                    $input.val('').trigger('input');
                    e.preventDefault();
                }
            }
        });
    }

    // Aktifkan pencarian di ketiga tab Master Dimensi
    initDimensiLiveSearch('#search_master_karakteristik', '#table_dimensi_karakteristik', '#clear_search_karakteristik', '#count_search_karakteristik', 'karakteristik');
    initDimensiLiveSearch('#search_master_baris', '#table_dimensi_baris', '#clear_search_baris', '#count_search_baris', 'judul baris');
    initDimensiLiveSearch('#search_master_periode', '#table_dimensi_periode', '#clear_search_periode', '#count_search_periode', 'periode');

    // Global keyboard shortcut '/' atau 'Ctrl+K' untuk langsung fokus ke input pencarian dimensi
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey && e.key.toLowerCase() === 'k') || (e.key === '/' && !$(e.target).is('input, textarea, select'))) {
            const $activeSearch = $('.search-dimensi-input:visible').first();
            if ($activeSearch.length) {
                e.preventDefault();
                $activeSearch.focus().select();
            }
        }
    });

});
</script>
