<!-- ========================================================================= -->
<!-- MODAL: KONFIGURASI TAMPILAN TABEL DINAMIS (modal_config_tabel.php)        -->
<!-- Fitur manipulasi struktur dan penyajian tabel portal data:                 -->
<!-- 1. Kolom & Label : Visibilitas, label dwibahasa, sub-header (||), format.   -->
<!-- 2. Pivot Mode     : Memutar matriks data dengan hierarki & custom ordering. -->
<!-- 3. Agregasi Data  : Total kelompok & grand total Jawa Tengah.              -->
<!-- ========================================================================= -->

<div class="modal fade" id="configModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-white border-bottom border-light py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md border-radius-xl d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(21, 70, 121, 0.08); border: 1px solid rgba(21, 70, 121, 0.15); flex-shrink: 0;">
                        <i class="bi bi-sliders2 fs-5" style="color: #154679;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Konfigurasi Tampilan Tabel</h5>
                        <p class="text-xs text-secondary mb-0">Sesuaikan kolom, label dwibahasa (ID/EN), dan struktur pivot matriks tabel</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" onclick="resetConfigToDefault()" style="font-size: 11px;" title="Reset seluruh kolom dan pengaturan pivot kembali ke kolom asli API">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Default API
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" onclick="toggleGuide()" style="font-size: 11px;">
                        <i class="bi bi-question-circle me-1"></i> Panduan
                    </button>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            
            <div class="modal-body p-4 bg-light-subtle">
                <!-- Segmented Tabs -->
                <ul class="nav nav-pills gap-2 bg-white p-1.5 rounded-3 mb-3 border border-light shadow-2xs" id="configTabs">
                    <li class="nav-item flex-fill">
                        <button class="nav-link active w-100 fw-bold py-2 rounded-2" data-bs-toggle="tab" data-bs-target="#cols-pane">
                            <i class="bi bi-layout-three-columns me-1.5"></i> Kolom & Format
                        </button>
                    </li>
                    <li class="nav-item flex-fill">
                        <button class="nav-link w-100 fw-bold py-2 rounded-2" data-bs-toggle="tab" data-bs-target="#pivot-pane">
                            <i class="bi bi-arrow-repeat me-1.5"></i> Mode Putar (Pivot)
                        </button>
                    </li>
                </ul>

                <!-- Panduan Singkat (Collapsible) -->
                <div id="guide-section" class="mb-3 p-3 rounded-3 border" style="display:none; background: #f0f7ff; border-color: rgba(21, 70, 121, 0.2) !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0 text-primary" style="font-size: 12px;"><i class="bi bi-lightbulb-fill me-1.5"></i> Panduan Konfigurasi Tabel</h6>
                        <button type="button" class="btn-close btn-close-sm" onclick="toggleGuide()" style="font-size: 9px;"></button>
                    </div>
                    <ul class="text-xs text-dark mb-0 ps-3 lh-base">
                        <li class="mb-1"><b>Urutan Kolom:</b> Geser ikon <i class="bi bi-grip-vertical text-muted"></i> untuk memindahkan posisi kolom sesuai urutan publikasi.</li>
                        <li class="mb-1"><b>Header Bertingkat:</b> Gunakan pemisah <code> || </code> pada label (contoh: <code>Produksi || Luas</code>) untuk membuat grouping header otomatis.</li>
                        <li class="mb-1"><b>Mode Pivot:</b> Aktifkan jika tabel berupa format indikator mentah yang perlu diputar menjadi kolom (misal: Wilayah sebagai baris, Tahun/Indikator sebagai kolom).</li>
                        <li><b>Terapkan:</b> Klik tombol "Terapkan & Simpan" untuk menyimpan perubahan secara permanen ke database DDA.</li>
                    </ul>
                </div>

                <div class="tab-content">
                    <!-- ========================================== -->
                    <!-- TAB 1: KOLOM & FORMAT                      -->
                    <!-- ========================================== -->
                    <div class="tab-pane fade show active" id="cols-pane">
                        <div class="alert border-0 shadow-none mb-3 py-2 px-3 rounded-3 d-flex align-items-center" style="background: #f1f6fc; border-left: 3px solid #154679 !important;">
                            <i class="bi bi-info-circle-fill me-2 fs-6" style="color: #154679; flex-shrink: 0;"></i>
                            <div class="text-xs text-dark lh-sm">
                                Tarik baris menggunakan ikon <i class="bi bi-grip-vertical text-muted"></i> untuk mengatur urutan. Kosongkan tanda centang untuk menyembunyikan kolom dari tampilan publikasi.
                            </div>
                        </div>

                        <!-- Daftar Kolom Dinamis -->
                        <div id="col-list-container">
                            <!-- Kolom di-inject via JS -->
                        </div>

                        <!-- Opsi Tampilan & Total Tabel -->
                        <div class="card border border-light rounded-3 p-3 bg-white mt-3 shadow-2xs">
                            <h6 class="text-xs text-uppercase font-weight-bold text-secondary mb-3">
                                <i class="bi bi-toggles me-1 text-primary"></i> Pengaturan Tampilan & Agregasi Tabel
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-2 bg-light-subtle rounded-3 border d-flex align-items-center h-100">
                                        <input class="form-check-input ms-1 me-3 my-0" type="checkbox" id="group-by-region" style="cursor: pointer;">
                                        <div>
                                            <label class="form-check-label fw-bold text-xs text-dark d-block mb-0" for="group-by-region" style="cursor: pointer;">Grup Wilayah (Kabupaten/Kota)</label>
                                            <span class="text-xxs text-secondary">Kelompokkan baris berdasarkan kode wilayah BPS</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-2 bg-light-subtle rounded-3 border d-flex align-items-center h-100">
                                        <input class="form-check-input ms-1 me-3 my-0" type="checkbox" id="config_show_total_col" style="cursor: pointer;">
                                        <div>
                                            <label class="form-check-label fw-bold text-xs text-dark d-block mb-0" for="config_show_total_col" style="cursor: pointer;">Tampilkan Total Jawa Tengah</label>
                                            <span class="text-xxs text-secondary">Hitung baris agregasi total provinsi di baris terbawah</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-2 bg-light-subtle rounded-3 border d-flex align-items-center h-100">
                                        <input class="form-check-input ms-1 me-3 my-0" type="checkbox" id="config_show_group_total" style="cursor: pointer;">
                                        <div>
                                            <label class="form-check-label fw-bold text-xs text-dark d-block mb-0" for="config_show_group_total" style="cursor: pointer;">Jumlah per Kelompok Kolom</label>
                                            <span class="text-xxs text-secondary">Tampilkan kolom subtotal horizontal untuk tiap grup</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-2 bg-light-subtle rounded-3 border d-flex align-items-center h-100">
                                        <input class="form-check-input ms-1 me-3 my-0" type="checkbox" id="merge-datasets" style="cursor: pointer;">
                                        <div>
                                            <label class="form-check-label fw-bold text-xs text-dark d-block mb-0" for="merge-datasets" style="cursor: pointer;">Gabungkan Multi-Dataset</label>
                                            <span class="text-xxs text-secondary">Satukan baris data jika tabel terdiri dari beberapa API</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- TAB 2: PIVOT MODE                          -->
                    <!-- ========================================== -->
                    <div class="tab-pane fade" id="pivot-pane">
                        <!-- Master Switch Banner -->
                        <div class="card border rounded-3 p-3 mb-3 bg-white shadow-2xs">
                            <div class="form-check form-switch m-0 d-flex align-items-center">
                                <input class="form-check-input ms-1 me-3 my-0 fs-5" type="checkbox" id="pivot-toggle" style="cursor: pointer;">
                                <div>
                                    <label class="form-check-label fw-bold text-sm text-dark mb-0" for="pivot-toggle" style="cursor: pointer;">
                                        Aktifkan Mode Pivot (Putar Data)
                                    </label>
                                    <span class="text-xs text-secondary d-block">Mentransformasikan baris data indikator menjadi kolom matriks berulang</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pivot Detail Settings -->
                        <div id="pivot-settings" style="display:none">
                            <!-- 3 Dimensi Pivot -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="card border rounded-3 p-3 h-100 bg-white shadow-2xs">
                                        <div class="d-flex align-items-center gap-1.5 mb-2">
                                            <i class="bi bi-list-ul text-primary"></i>
                                            <label class="text-xs fw-bold text-dark mb-0">1. Baris Tetap (Row)</label>
                                        </div>
                                        <div id="pivot-row-container" class="bg-light rounded-2 p-2 overflow-auto" style="height: 120px; border: 1px solid #eef2f7;"></div>
                                        <div id="selected-pivot-row-order" class="mt-2 p-2 border rounded-2 bg-white" style="display:none">
                                            <span class="text-xxs text-primary font-weight-bold d-block mb-1">Urutan Baris (Tarik):</span>
                                            <div id="pivot-row-order-list" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border rounded-3 p-3 h-100 bg-white shadow-2xs">
                                        <div class="d-flex align-items-center gap-1.5 mb-2">
                                            <i class="bi bi-layout-three-columns text-primary"></i>
                                            <label class="text-xs fw-bold text-dark mb-0">2. Kategori Kolom (Col)</label>
                                        </div>
                                        <div id="pivot-col-container" class="bg-light rounded-2 p-2 overflow-auto" style="height: 120px; border: 1px solid #eef2f7;"></div>
                                        <div id="selected-pivot-col-order" class="mt-2 p-2 border rounded-2 bg-white" style="display:none">
                                            <span class="text-xxs text-primary font-weight-bold d-block mb-1">Urutan Kolom (Tarik):</span>
                                            <div id="pivot-col-order-list" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border rounded-3 p-3 h-100 bg-white shadow-2xs">
                                        <div class="d-flex align-items-center gap-1.5 mb-2">
                                            <i class="bi bi-123 text-primary"></i>
                                            <label class="text-xs fw-bold text-dark mb-0">3. Kolom Nilai (Value)</label>
                                        </div>
                                        <div id="pivot-val-container" class="bg-light rounded-2 p-2 overflow-auto" style="height: 120px; border: 1px solid #eef2f7;"></div>
                                        <div id="selected-pivot-val-order" class="mt-2 p-2 border rounded-2 bg-white" style="display:none">
                                            <span class="text-xxs text-primary font-weight-bold d-block mb-1">Urutan Nilai (Tarik):</span>
                                            <div id="pivot-val-order-list" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Induk Header Kolom Pivot -->
                            <div class="card border rounded-3 p-3 bg-white mb-3 shadow-2xs">
                                <h6 class="text-xs text-uppercase font-weight-bold text-secondary mb-2">
                                    <i class="bi bi-type-h1 me-1 text-primary"></i> Induk Header Kolom Pivot
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-secondary border-end-0 text-xxs font-weight-bold px-2" style="width: 70px;">ID (Ind)</span>
                                            <input type="text" id="pivot-prefix" class="form-control form-control-sm border-start-0 text-xs" placeholder="Contoh: Produksi || ">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-secondary border-end-0 text-xxs font-weight-bold px-2" style="width: 70px;">EN (Eng)</span>
                                            <input type="text" id="pivot-prefix-en" class="form-control form-control-sm border-start-0 text-xs" placeholder="Example: Production || ">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Opsi Agregasi & Orientasi Pivot -->
                            <div class="card border rounded-3 p-3 bg-white mb-3 shadow-2xs">
                                <h6 class="text-xs text-uppercase font-weight-bold text-secondary mb-2">
                                    <i class="bi bi-calculator me-1 text-primary"></i> Opsi Total & Orientasi Matriks
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-2 bg-light-subtle rounded-2 border d-flex align-items-center">
                                            <input class="form-check-input ms-1 me-2 my-0" type="checkbox" id="pivot-total-row" onchange="document.getElementById('pivot-only-total-wrapper').style.display = this.checked ? 'block' : 'none'" checked style="cursor: pointer;">
                                            <label class="form-check-label text-xs fw-bold text-dark mb-0" for="pivot-total-row" style="cursor: pointer;">Hitung Total Horizontal (Baris)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-2 bg-light-subtle rounded-2 border d-flex align-items-center">
                                            <input class="form-check-input ms-1 me-2 my-0" type="checkbox" id="pivot-total-col" checked style="cursor: pointer;">
                                            <label class="form-check-label text-xs fw-bold text-dark mb-0" for="pivot-total-col" style="cursor: pointer;">Hitung Total Vertikal (Jawa Tengah)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="pivot-only-total-wrapper">
                                        <div class="form-check form-switch p-2 bg-light-subtle rounded-2 border d-flex align-items-center">
                                            <input class="form-check-input ms-1 me-2 my-0" type="checkbox" id="pivot-only-total" style="cursor: pointer;">
                                            <label class="form-check-label text-xs text-muted mb-0" for="pivot-only-total" style="cursor: pointer;">Hanya Tampilkan Total (Sembunyikan Rincian)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-2 bg-light-subtle rounded-2 border d-flex align-items-center">
                                            <input class="form-check-input ms-1 me-2 my-0" type="checkbox" id="pivot-metric-first" style="cursor: pointer;">
                                            <label class="form-check-label text-xs fw-bold text-primary mb-0" for="pivot-metric-first" style="cursor: pointer;">Prioritas Metrik Terlebih Dahulu (Metrik » Tahun)</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch p-2 bg-light-subtle rounded-2 border d-flex align-items-center">
                                            <input class="form-check-input ms-1 me-2 my-0" type="checkbox" id="pivot-metrics-as-row" style="cursor: pointer;">
                                            <label class="form-check-label text-xs fw-bold text-success mb-0" for="pivot-metrics-as-row" style="cursor: pointer;">Jadikan Metrik sebagai Baris (Portrait Mode)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mapping Area -->
                            <div id="pivot-mapping-area" style="display:none">
                                <div class="card border rounded-3 p-3 bg-white shadow-2xs">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="text-xs text-uppercase font-weight-bold text-secondary mb-0">
                                            <i class="bi bi-pencil-square me-1 text-primary"></i> Sesuaikan Label Hasil Putar (Mapping)
                                        </h6>
                                        <span class="text-xxs text-muted">Tarik untuk mengubah urutan kategori</span>
                                    </div>
                                    <div id="pivot-mapping-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PREVIEW AREA (Outside Tabs for visibility) -->
                <div id="preview-area" class="mt-3 card border rounded-3 p-3 bg-white shadow-sm" style="display:none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold small text-primary mb-0"><i class="bi bi-eye me-1.5"></i> Pratinjau Cepat Hasil Konfigurasi (5 Baris)</h6>
                        <button type="button" onclick="document.getElementById('preview-area').style.display='none'" class="btn btn-sm btn-light border px-2 py-0.5 text-xs text-muted">
                            <i class="bi bi-x me-1"></i>Tutup Preview
                        </button>
                    </div>
                    <div id="preview-container" class="bg-white border rounded-2 overflow-auto p-1" style="max-height: 220px; font-size: 11px;"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white border-top border-light py-3 px-4 d-flex justify-content-between align-items-center">
                <button type="button" onclick="showPreview()" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-bold">
                    <i class="bi bi-eye me-1"></i> Pratinjau Cepat
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-light btn-sm px-4 rounded-pill text-muted fw-bold border" data-bs-dismiss="modal">Batal</button>
                    <button type="button" onclick="applyConfiguration()" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold shadow-sm d-flex align-items-center" style="background: linear-gradient(135deg, #154679 0%, #1e5c9e 100%); border-color: #154679;">
                        <i class="bi bi-check-circle me-1.5 fs-6"></i> Terapkan & Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling Tabs & Active State */
    #configTabs .nav-link {
        color: #64748b;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    #configTabs .nav-link:hover {
        background-color: #f8fafc;
        color: #154679;
    }
    #configTabs .nav-link.active {
        background-color: #154679 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 6px rgba(21, 70, 121, 0.2) !important;
    }

    /* Form Switch Navy BPS */
    .form-check-input:checked {
        background-color: #154679 !important;
        border-color: #154679 !important;
    }
    .form-check-input:focus {
        border-color: #154679 !important;
        box-shadow: 0 0 0 0.2rem rgba(21, 70, 121, 0.15) !important;
    }

    /* Input Focus */
    .form-control:focus,
    .form-select:focus {
        border-color: #154679 !important;
        box-shadow: 0 0 0 0.2rem rgba(21, 70, 121, 0.12) !important;
    }

    /* Sortable Drag Ghost */
    .sortable-ghost {
        background: #f1f6fc !important;
        opacity: 0.75 !important;
        border: 1px dashed #154679 !important;
    }
</style>

<script>
    function getConfigFromModal() {
        const colItems = document.querySelectorAll('.col-item');
        const config = {
            columns: [],
            pivot: {
                enabled: document.getElementById('pivot-toggle').checked,
                row: Array.from(document.querySelectorAll('.pivot-row-order-item')).map(div => div.dataset.key),
                col: Array.from(document.querySelectorAll('.pivot-col-order-item')).map(div => div.dataset.key),
                val: Array.from(document.querySelectorAll('.pivot-val-order-item')).map(div => div.dataset.key), 
                total_row: document.getElementById('pivot-total-row').checked,
                only_total: document.getElementById('pivot-only-total').checked,
                total_col: document.getElementById('pivot-total-col').checked,
                metric_first: document.getElementById('pivot-metric-first').checked,
                metrics_as_row: document.getElementById('pivot-metrics-as-row').checked,
                prefix: document.getElementById('pivot-prefix').value,
                prefix_en: document.getElementById('pivot-prefix-en').value,
                mappings: {}
            },
            merge_datasets: document.getElementById('merge-datasets').checked,
            show_total_col: document.getElementById('config_show_total_col').checked,
            show_group_total: document.getElementById('config_show_group_total').checked,
            group_by_region: document.getElementById('group-by-region').checked
        };

        document.querySelectorAll('.pivot-map-id').forEach((input, idx) => {
            const cat = input.dataset.cat;
            config.pivot.mappings[cat] = {
                label_id: input.value || cat,
                label_en: document.querySelectorAll('.pivot-map-en')[idx].value || ''
            };
        });
        
        // Capture Mapping Order
        config.pivot.mapping_order = Array.from(document.querySelectorAll('.mapping-item')).map(div => div.dataset.key);

        colItems.forEach(item => {
            config.columns.push({
                key: item.dataset.key,
                label_id: item.querySelector('.label-id').value,
                label_en: item.querySelector('.label-en').value,
                visible: item.querySelector('.visibility-check').checked,
                format: item.querySelector('.col-format').value || 'number'
            });
        });
        return config;
    }

    function openConfigModal() {
      try {
        const modalEl = document.getElementById('configModal');
        let bootstrapModal = bootstrap.Modal.getInstance(modalEl);
        if (!bootstrapModal) bootstrapModal = new bootstrap.Modal(modalEl);
        
        const container = document.getElementById('col-list-container');
        container.innerHTML = '';

        const firstTable = rawApiResults[0]; 
        const dataRows = getRowsFromApiResult(firstTable);
        const baseKeys = (dataRows.length > 0) ? Object.keys(dataRows[0]) : [];

        // AMBIL CONFIG YANG ADA ATAU DEFAULT (STATEFUL)
        if (typeof currentTableConfig === 'string') {
            try {
                currentTableConfig = JSON.parse(currentTableConfig);
                if (typeof currentTableConfig === 'string') currentTableConfig = JSON.parse(currentTableConfig);
            } catch(e) {}
        }

        // AUTO-SCHEMA RECONCILIATION:
        if (currentTableConfig && typeof currentTableConfig === 'object' && Array.isArray(currentTableConfig.columns) && baseKeys.length > 0) {
            const cfgKeys = currentTableConfig.columns.map(c => c.key);
            const commonKeys = baseKeys.filter(k => cfgKeys.includes(k));
            if (commonKeys.length === 0) {
                console.warn("Skema kolom API berbeda dengan config lama. Mereset config ke kolom default API...");
                currentTableConfig = null;
            }
        }

        const hasIndicatorPattern = baseKeys.some(k => k.toLowerCase() === 'indikator') && baseKeys.some(k => k.toLowerCase() === 'nilai') && baseKeys.length >= 4;

        if (!currentTableConfig || typeof currentTableConfig !== 'object') {
            if (hasIndicatorPattern) {
                currentTableConfig = buildSmartPivotConfig(baseKeys, dataRows);
            } else {
                currentTableConfig = {
                    columns: baseKeys.map(k => ({ key: k, label_id: k, label_en: '', visible: true, format: 'number' })),
                    pivot: { enabled: false, row: [], col: [], val: [], total_row: true, total_col: true, prefix: '', prefix_en: '', mappings: {} },
                    merge_datasets: false,
                    show_total_col: false,
                    show_group_total: false,
                    group_by_region: true
                };
            }
        } else if (hasIndicatorPattern && (!currentTableConfig.pivot || (!currentTableConfig.pivot.enabled && (!currentTableConfig.pivot.col || currentTableConfig.pivot.col.length === 0)))) {
            currentTableConfig = buildSmartPivotConfig(baseKeys, dataRows);
        }
        
        // MIGRASI/NORMALISASI CONFIG LAMA
        if (!currentTableConfig.columns || !Array.isArray(currentTableConfig.columns)) {
            if (currentTableConfig.columns && typeof currentTableConfig.columns === 'object' && currentTableConfig.columns !== null) {
                currentTableConfig.columns = Object.values(currentTableConfig.columns);
            } else {
                currentTableConfig.columns = baseKeys.map(k => ({ key: k, label_id: k, label_en: '', visible: true, format: 'number' }));
            }
        }
        if (!currentTableConfig.pivot) {
            currentTableConfig.pivot = { enabled: false, row: [], col: [], val: [], total_row: true, total_col: true, prefix: '', prefix_en: '', mappings: {} };
        }
        ['row', 'col', 'val'].forEach(t => {
            if (currentTableConfig.pivot[t] && !Array.isArray(currentTableConfig.pivot[t])) {
                if (typeof currentTableConfig.pivot[t] === 'object') {
                    currentTableConfig.pivot[t] = Object.values(currentTableConfig.pivot[t]);
                } else {
                    currentTableConfig.pivot[t] = [currentTableConfig.pivot[t]];
                }
            } else if (!currentTableConfig.pivot[t]) {
                currentTableConfig.pivot[t] = [];
            }
        });
        if (currentTableConfig.pivot.total_col === undefined) currentTableConfig.pivot.total_col = true;

        // Render Kolom dengan Desain Bersih & Modern
        currentTableConfig.columns.forEach(col => {
            const div = document.createElement('div');
            div.className = 'col-item bg-white p-3 rounded-3 mb-2 border transition-all';
            div.style.borderColor = col.visible ? '#e2e8f0' : '#f1f5f9';
            div.style.opacity = col.visible ? '1' : '0.65';
            div.dataset.key = col.key;
            div.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-grip-vertical text-muted fs-5 cursor-move" title="Geser untuk ubah urutan" style="cursor: grab;"></i>
                        <div class="form-check form-switch m-0 d-flex align-items-center">
                            <input class="form-check-input visibility-check m-0" type="checkbox" id="vis-${col.key}" ${col.visible ? 'checked' : ''} style="cursor: pointer;" title="Tampilkan/Sembunyikan Kolom">
                        </div>
                        <span class="font-monospace text-xs fw-bold px-2 py-0.5 rounded bg-light text-dark border">
                            ${col.key}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="text-xxs text-secondary font-weight-bold text-uppercase me-1">Format:</span>
                        <select class="form-select form-select-sm col-format py-1 px-2 text-xs border rounded-2" style="width: auto; height: 28px;">
                            <option value="number" ${col.format === 'number' ? 'selected' : ''}>Angka Standar</option>
                            <option value="decimal" ${col.format === 'decimal' ? 'selected' : ''}>Desimal (0,00)</option>
                            <option value="percent" ${col.format === 'percent' ? 'selected' : ''}>Persentase (%)</option>
                            <option value="currency" ${col.format === 'currency' ? 'selected' : ''}>Mata Uang (Rp)</option>
                            <option value="ribuan" ${col.format === 'ribuan' ? 'selected' : ''}>Ribuan (/ 1.000)</option>
                            <option value="jutaan" ${col.format === 'jutaan' ? 'selected' : ''}>Jutaan (/ 1.000.000)</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 pt-1 border-top border-light">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-secondary border-end-0 text-xxs font-weight-bold px-2" style="width: 70px;">ID (Ind)</span>
                            <input type="text" class="form-control form-control-sm label-id border-start-0 text-xs" value="${col.label_id}" placeholder="Label bahasa Indonesia...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-secondary border-end-0 text-xxs font-weight-bold px-2" style="width: 70px;">EN (Eng)</span>
                            <input type="text" class="form-control form-control-sm label-en border-start-0 text-xs" value="${col.label_en}" placeholder="English label (opsional)...">
                        </div>
                    </div>
                </div>
            `;

            // Dynamic toggle opacity when unchecked
            const chk = div.querySelector('.visibility-check');
            chk.addEventListener('change', function() {
                div.style.opacity = this.checked ? '1' : '0.65';
                div.style.borderColor = this.checked ? '#e2e8f0' : '#f1f5f9';
            });

            container.appendChild(div);
        });

        try { new Sortable(container, { animation: 150, handle: '.cursor-move', ghostClass: 'sortable-ghost' }); } catch(e) { console.warn('SortableJS not loaded'); }

        // Restore Pivot Checklists (Row, Col, Val)
        ['row', 'col', 'val'].forEach(type => {
            const container = document.getElementById(`pivot-${type}-container`);
            container.innerHTML = '';
            baseKeys.forEach(key => {
                const isChecked = Array.isArray(currentTableConfig.pivot[type]) && currentTableConfig.pivot[type].includes(key) ? 'checked' : '';
                const div = document.createElement('div');
                div.className = 'form-check small mb-1';
                div.innerHTML = `
                    <input class="form-check-input pivot-${type}-check" type="checkbox" value="${key}" id="chk-${type}-${key}" ${isChecked} onchange="refreshPivotOrderList('${type}')" style="cursor: pointer;">
                    <label class="form-check-label text-dark" for="chk-${type}-${key}" style="font-size:11px; cursor: pointer;">${key}</label>
                `;
                container.appendChild(div);
            });

            refreshPivotOrderList(type);
            try {
                new Sortable(document.getElementById(`pivot-${type}-order-list`), { 
                    animation: 100, 
                    ghostClass: 'sortable-ghost',
                    onEnd: () => { updatePivotMappingUI(); } 
                });
            } catch(e) { console.warn('SortableJS not loaded'); }
        });

        // Restore Switch & Values
        document.getElementById('pivot-toggle').checked = currentTableConfig.pivot.enabled;
        document.getElementById('pivot-settings').style.display = currentTableConfig.pivot.enabled ? 'block' : 'none';
        document.getElementById('pivot-prefix').value = currentTableConfig.pivot.prefix || '';
        document.getElementById('pivot-prefix-en').value = currentTableConfig.pivot.prefix_en || '';
        document.getElementById('pivot-total-row').checked = currentTableConfig.pivot.total_row !== false;
        document.getElementById('pivot-only-total').checked = currentTableConfig.pivot.only_total || false;
        document.getElementById('pivot-only-total-wrapper').style.display = document.getElementById('pivot-total-row').checked ? 'block' : 'none';
        document.getElementById('pivot-total-col').checked = currentTableConfig.pivot.total_col !== false;
        document.getElementById('pivot-metric-first').checked = currentTableConfig.pivot.metric_first || false;
        document.getElementById('pivot-metrics-as-row').checked = currentTableConfig.pivot.metrics_as_row || false;
        document.getElementById('merge-datasets').checked = currentTableConfig.merge_datasets;
        document.getElementById('config_show_total_col').checked = currentTableConfig.show_total_col || false;
        document.getElementById('config_show_group_total').checked = currentTableConfig.show_group_total || false;
        document.getElementById('group-by-region').checked = currentTableConfig.group_by_region !== false;

        if (currentTableConfig.pivot.enabled && currentTableConfig.pivot.col) {
            updatePivotMappingUI();
        }

        const toggle = document.getElementById('pivot-toggle');
        if (!toggle.dataset.hasListener) {
            toggle.addEventListener('change', function() {
                document.getElementById('pivot-settings').style.display = this.checked ? 'block' : 'none';
                if (this.checked) updatePivotMappingUI();
            });
            document.getElementById('pivot-total-row').addEventListener('change', updatePivotMappingUI);
            document.getElementById('pivot-total-col').addEventListener('change', updatePivotMappingUI);
            document.getElementById('pivot-metrics-as-row').addEventListener('change', updatePivotMappingUI);
            toggle.dataset.hasListener = "true";
        }

        // Initialize Sortable for Mapping List
        const mappingList = document.getElementById('pivot-mapping-list');
        if (mappingList && !mappingList.dataset.sortableInitialized) {
            try {
                new Sortable(mappingList, { 
                    animation: 150, 
                    handle: '.cursor-move',
                    ghostClass: 'sortable-ghost'
                });
            } catch(e) { console.warn('SortableJS not loaded'); }
            mappingList.dataset.sortableInitialized = "true";
        }

        bootstrapModal.show();
      } catch (err) {
        console.error("Error opening config modal:", err);
        alert("Gagal membuka konfigurasi: " + err.message + "\n\nStack:\n" + err.stack);
      }
    }
    
    function buildSmartPivotConfig(baseKeys, dataRows) {
        const ignoreCols = ['id', 'tahun', 'tahun_data', 'year', 'periode', 'satuan', 'unit', 'kode_wilayah', 'kode_kabupatenkota'];
        const valCandidates = baseKeys.filter(k => ['nilai', 'jumlah', 'total', 'value', 'realisasi'].includes(k.toLowerCase()));
        const valCol = valCandidates.length > 0 ? valCandidates[0] : baseKeys[baseKeys.length - 1];
        const catCandidates = baseKeys.filter(k => !ignoreCols.includes(k.toLowerCase()) && k !== valCol);

        let rowCol = catCandidates[0] || baseKeys[0];
        let colCol = catCandidates.find(k => ['indikator', 'jenis_kelamin', 'gender', 'kategori', 'sub_kategori'].includes(k.toLowerCase())) || catCandidates[1] || 'tahun_data';

        if (catCandidates.length >= 2 && dataRows && dataRows.length > 0) {
            const cardA = new Set(dataRows.map(r => r[catCandidates[0]])).size;
            const cardB = new Set(dataRows.map(r => r[catCandidates[1]])).size;
            if (cardA >= cardB) {
                rowCol = catCandidates[0];
                colCol = catCandidates[1];
            } else {
                rowCol = catCandidates[1];
                colCol = catCandidates[0];
            }
        }

        return {
            columns: baseKeys.map(k => ({ key: k, label_id: k, label_en: '', visible: true, format: 'number' })),
            pivot: {
                enabled: true,
                row: rowCol ? [rowCol] : [],
                col: colCol ? [colCol] : [],
                val: valCol ? [valCol] : [],
                total_row: true,
                only_total: false,
                total_col: true,
                metric_first: false,
                metrics_as_row: false,
                prefix: '',
                prefix_en: '',
                mappings: {},
                mapping_order: []
            },
            merge_datasets: false,
            show_total_col: false,
            show_group_total: false,
            group_by_region: true
        };
    }

    function resetConfigToDefault() {
        if (!confirm("Reset semua pengaturan kolom dan pivot kembali ke format default API saat ini?")) return;
        currentTableConfig = null;
        if (Array.isArray(rawApiResults)) {
            rawApiResults.forEach(r => { r.config_tabel = null; });
        }
        openConfigModal();
    }

    function toggleGuide() {
        const guide = document.getElementById('guide-section');
        guide.style.display = (guide.style.display === 'none') ? 'block' : 'none';
    }

    function refreshPivotOrderList(type = 'col') {
        const checked = Array.from(document.querySelectorAll(`.pivot-${type}-check:checked`)).map(cb => cb.value);
        const orderContainer = document.getElementById(`selected-pivot-${type}-order`);
        const list = document.getElementById(`pivot-${type}-order-list`);
        
        let currentOrder = Array.from(document.querySelectorAll(`.pivot-${type}-order-item`)).map(d => d.dataset.key);
        if (currentOrder.length === 0 && currentTableConfig?.pivot?.[type]) {
            currentOrder = currentTableConfig.pivot[type];
        }
        
        const finalOrder = currentOrder.filter(k => checked.includes(k));
        checked.forEach(k => { if(!finalOrder.includes(k)) finalOrder.push(k); });

        list.innerHTML = '';
        if (finalOrder.length > 0) {
            orderContainer.style.display = 'block';
            finalOrder.forEach(key => {
                const badge = document.createElement('div');
                badge.className = `pivot-${type}-order-item badge bg-primary cursor-move px-2 py-1.5 text-xs d-flex align-items-center gap-1`;
                badge.style.backgroundColor = '#154679';
                badge.dataset.key = key;
                badge.innerHTML = `<i class="bi bi-grip-vertical"></i> ${key}`;
                list.appendChild(badge);
            });
        } else {
            orderContainer.style.display = 'none';
        }
        updatePivotMappingUI();
    }

    function updatePivotMappingUI() {
        const pivotRow = Array.from(document.querySelectorAll('.pivot-row-order-item')).map(div => div.dataset.key);
        const pivotCol = Array.from(document.querySelectorAll('.pivot-col-order-item')).map(div => div.dataset.key);
        const pivotVal = Array.from(document.querySelectorAll('.pivot-val-order-item')).map(div => div.dataset.key);
        const hasTotalRow = document.getElementById('pivot-total-row').checked;
        const hasTotalCol = document.getElementById('pivot-total-col').checked;
        const mappingArea = document.getElementById('pivot-mapping-area');
        const mappingList = document.getElementById('pivot-mapping-list');
        
        if ((pivotCol.length === 0 && pivotRow.length === 0) || !document.getElementById('pivot-toggle').checked) {
            mappingArea.style.display = 'none';
            return;
        }

        const data = getRowsFromApiResult(rawApiResults[0]);
        const allTargetKeys = new Set();
        const allTargetCols = [];

        const addKey = (key, type) => {
            if (!key || allTargetKeys.has(key)) return;
            allTargetKeys.add(key);
            allTargetCols.push({ key, type });
        };

        // 1. Baris Tetap (Row headers)
        pivotRow.forEach(rk => {
            addKey(rk, 'Nama Baris: ' + rk);
            const rowValues = [...new Set(data.map(item => String(item[rk] || 'N/A')))].sort();
            rowValues.forEach(rv => addKey(rv, 'Isi Baris: ' + rk));
        });

        // 2. Kategori Kolom (Col headers)
        pivotCol.forEach(ck => {
            const colValues = [...new Set(data.map(item => String(item[ck] || 'N/A')))].sort();
            colValues.forEach(cv => addKey(cv, 'Isi Kolom: ' + ck));
        });
        
        const generateCatKey = (item) => pivotCol.map(k => String(item[k] || 'N/A')).join(' || ');
        const categories = [...new Set(data.map(item => generateCatKey(item)))].sort();
        categories.forEach(cat => addKey(cat, 'Gabungan Kolom (Hierarki)'));

        // 3. Kolom Nilai (Metrics)
        pivotVal.forEach(vk => addKey(vk, 'Metric / Nilai'));

        // 4. Totals
        if (hasTotalRow) addKey('Jumlah', 'Total Horizontal');
        if (hasTotalCol) addKey('Jawa Tengah', 'Total Vertikal');

        // Apply saved order if exists
        if (currentTableConfig?.pivot?.mapping_order) {
            const savedOrder = currentTableConfig.pivot.mapping_order;
            allTargetCols.sort((a, b) => {
                let idxA = savedOrder.indexOf(a.key);
                let idxB = savedOrder.indexOf(b.key);
                if (idxA === -1) idxA = 999;
                if (idxB === -1) idxB = 999;
                return idxA - idxB;
            });
        }

        mappingArea.style.display = 'block';
        mappingList.innerHTML = '';
        
        allTargetCols.forEach(col => {
            let saved = currentTableConfig?.pivot?.mappings?.[col.key] || { label_id: col.key, label_en: '' };
            const div = document.createElement('div');
            div.className = 'mapping-item p-2.5 mb-2 bg-light rounded-3 border';
            div.dataset.key = col.key;
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1.5">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-grip-vertical text-muted cursor-move fs-6"></i>
                        <span class="text-xs fw-bold font-monospace text-dark">${col.key}</span>
                    </div>
                    <span class="badge bg-white text-secondary border text-xxs">${col.type}</span>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary border-end-0 text-xxs font-weight-bold px-2" style="width: 70px;">ID (Ind)</span>
                            <input type="text" class="form-control form-control-sm pivot-map-id border-start-0 text-xs" data-cat="${col.key}" value="${saved.label_id}" placeholder="Label ID...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-secondary border-end-0 text-xxs font-weight-bold px-2" style="width: 70px;">EN (Eng)</span>
                            <input type="text" class="form-control form-control-sm pivot-map-en border-start-0 text-xs" value="${saved.label_en}" placeholder="English label...">
                        </div>
                    </div>
                </div>
            `;
            mappingList.appendChild(div);
        });
    }

    function applyConfiguration() {
        const config = getConfigFromModal();
        currentTableConfig = config;
        renderCustomTable(0, currentTableConfig);
        
        // Simpan ke SEMUA ID yang ada di hasil saat ini agar sinkron
        const idsToUpdate = rawApiResults.map(res => res.res_id || res.id).filter(id => id && id !== 0);

        if (idsToUpdate.length > 0) {
            const btn = document.querySelector('button[onclick="applyConfiguration()"]');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
            btn.disabled = true;

            const updatePromises = idsToUpdate.map(idDatabase => {
                const formData = new FormData();
                formData.append('id', idDatabase);
                formData.append('config', JSON.stringify(currentTableConfig));
                
                const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
                if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

                return fetch('<?= site_url('admin/simpan_config_tabel') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(response => response.json());
            });

            Promise.all(updatePromises)
            .then(results => {
                const allSuccess = results.every(r => r.status === 'success');
                if (allSuccess) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('configModal'));
                    if(modal) modal.hide();
                } else {
                    const errors = results.filter(r => r.status !== 'success').map(r => r.message || 'Unknown error').join(', ');
                    alert("⚠️ Beberapa gagal disimpan: " + errors);
                }
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            })
            .catch(err => {
                console.error(err);
                alert("❌ Terjadi kesalahan jaringan saat menyimpan konfigurasi.");
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        } else {
             const modal = bootstrap.Modal.getInstance(document.getElementById('configModal'));
             if(modal) modal.hide();
        }
    }

    function showPreview() {
        const config = getConfigFromModal();
        const area = document.getElementById('preview-area');
        const container = document.getElementById('preview-container');
        
        area.style.display = 'block';
        container.innerHTML = '<div class="text-center p-3 text-muted"><span class="spinner-border spinner-border-sm me-1"></span> Menyiapkan pratinjau...</div>';
        
        setTimeout(() => {
            const tempDiv = document.createElement('div');
            renderCustomTable(0, config, tempDiv, 5); // Hanya 5 baris
            container.innerHTML = tempDiv.innerHTML;
        }, 300);
    }
</script>
