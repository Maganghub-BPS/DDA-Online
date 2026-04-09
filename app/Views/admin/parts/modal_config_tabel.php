<!-- Modal Konfigurasi Tabel Dinamis -->
<div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-sliders me-2 text-primary"></i> KONFIGURASI TABEL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-3" id="configTabs">
                    <li class="nav-item flex-fill">
                        <button class="nav-link active w-100 fw-bold py-2 rounded-3" data-bs-toggle="tab" data-bs-target="#cols-pane">Kolom & Label</button>
                    </li>
                    <li class="nav-item flex-fill">
                        <button class="nav-link w-100 fw-bold py-2 rounded-3" data-bs-toggle="tab" data-bs-target="#pivot-pane">Pivot Mode</button>
                    </li>
                </ul>

                <div class="tab-content border rounded-4 bg-white p-3 shadow-sm" style="min-height: 400px;">
                    <!-- TAB 1: KOLOM & LABEL -->
                    <div class="tab-pane fade show active" id="cols-pane">
                        <div class="alert alert-warning py-2 mb-3 border-0 rounded-3 shadow-none" style="font-size: 11px;">
                            <i class="bi bi-info-circle-fill me-1"></i> Tarik ikon <i class="bi bi-grip-vertical"></i> untuk merubah urutan. Gunakan <b>||</b> untuk header bertingkat.
                        </div>
                        <div id="col-list-container">
                            <!-- Kolom di-inject JS -->
                        </div>
                        <hr>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="form-check form-switch bg-light p-3 rounded-3 h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="merge-datasets">
                                    <label class="form-check-label fw-bold small" for="merge-datasets">Gabungkan Dataset</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch bg-light p-3 rounded-3 h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="show-total-col">
                                    <label class="form-check-label fw-bold small" for="show-total-col">Total Horizontal (Tabel Dasar)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch bg-light p-3 rounded-3 h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="group-by-region">
                                    <label class="form-check-label fw-bold small" for="group-by-region">Grup Wilayah (Kab/Kota)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PIVOT MODE -->
                    <div class="tab-pane fade" id="pivot-pane">
                        <div class="form-check form-switch mb-3 p-3 bg-light rounded-3">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="pivot-toggle">
                            <label class="form-check-label fw-bold" for="pivot-toggle">Aktifkan Putar Data (Pivot Mode)</label>
                        </div>
                        <div id="pivot-settings" style="display:none" class="p-2">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted mb-1">Baris Tetap (Hierarki)</label>
                                    <div id="pivot-row-container" class="bg-light rounded-3 p-2 overflow-auto" style="height: 120px; border: 1px solid #eef2f7;">
                                        <!-- Checkboxes -->
                                    </div>
                                    <div id="selected-pivot-row-order" class="mt-2 p-2 border rounded-3 bg-white" style="display:none">
                                        <small class="text-primary fw-bold d-block mb-1" style="font-size:9px">Urutan Baris (Tarik untuk atur):</small>
                                        <div id="pivot-row-order-list" class="d-flex flex-wrap gap-1"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted mb-1 d-block">Kategori Kolom (Hierarki)</label>
                                    <div id="pivot-col-container" class="bg-light rounded-3 p-2 overflow-auto" style="height: 120px; border: 1px solid #eef2f7;">
                                        <!-- Checkboxes -->
                                    </div>
                                    <div id="selected-pivot-col-order" class="mt-2 p-2 border rounded-3 bg-white" style="display:none">
                                        <small class="text-primary fw-bold d-block mb-1" style="font-size:9px">Urutan Kolom (Tarik untuk atur):</small>
                                        <div id="pivot-col-order-list" class="d-flex flex-wrap gap-1"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted mb-1">Kolom Nilai (Metrics)</label>
                                    <div id="pivot-val-container" class="bg-light rounded-3 p-2 overflow-auto" style="height: 120px; border: 1px solid #eef2f7;">
                                        <!-- Checkboxes -->
                                    </div>
                                    <div id="selected-pivot-val-order" class="mt-2 p-2 border rounded-3 bg-white" style="display:none">
                                        <small class="text-primary fw-bold d-block mb-1" style="font-size:9px">Urutan Nilai (Tarik untuk atur):</small>
                                        <div id="pivot-val-order-list" class="d-flex flex-wrap gap-1"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted mb-1">Induk Header (ID)</label>
                                    <input type="text" id="pivot-prefix" class="form-control form-control-sm border-0 bg-light rounded-3" placeholder="Contoh: Produksi || ">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted mb-1">Induk Header (EN)</label>
                                    <input type="text" id="pivot-prefix-en" class="form-control form-control-sm border-0 bg-light rounded-3" placeholder="Example: Production || ">
                                </div>
                            </div>
                            <div class="form-check form-switch mt-3 mb-2">
                                <input class="form-check-input" type="checkbox" id="pivot-total-row" checked>
                                <label class="form-check-label small fw-bold" for="pivot-total-row">Hitung Total Horizontal (Baris)</label>
                            </div>
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="pivot-total-col" checked>
                                <label class="form-check-label small fw-bold" for="pivot-total-col">Hitung Total Vertikal (Kolom - Jawa Tengah)</label>
                            </div>
                            
                            <!-- Mapping Area -->
                            <div id="pivot-mapping-area" style="display:none">
                                <label class="fw-bold small text-primary mb-2 border-top pt-3 w-100"><i class="bi bi-pencil-square me-1"></i> Sesuaikan Hasil Putar (Mapping)</label>
                                <div id="pivot-mapping-list" class="bg-light p-3 rounded-3"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PREVIEW AREA (Outside Tabs for visibility) -->
                <div id="preview-area" class="mt-4 border-top pt-3" style="display:none">
                    <h6 class="fw-bold small text-muted mb-2 ps-1"><i class="bi bi-eye me-1"></i> Preview Cepat (5 Baris)</h6>
                    <div id="preview-container" class="bg-white border rounded-4 overflow-auto" style="max-height: 200px; font-size: 10px;"></div>
                    <div class="text-center mt-2">
                        <button type="button" onclick="document.getElementById('preview-area').style.display='none'" class="btn btn-sm btn-link text-muted text-decoration-none">Sembunyikan Preview</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 p-4 pt-2 d-flex justify-content-between">
                <button type="button" class="btn btn-light px-4 fw-bold rounded-3 text-muted" data-bs-dismiss="modal">Tutup</button>
                <div class="d-flex gap-2">
                    <button type="button" onclick="showPreview()" class="btn btn-info text-white px-4 fw-bold rounded-pill"><i class="bi bi-eye me-1"></i> Preview</button>
                    <button type="button" onclick="applyConfiguration()" class="btn btn-primary px-4 fw-bold rounded-pill shadow-primary"><i class="bi bi-check-circle me-1"></i> Terapkan & Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>

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
                total_col: document.getElementById('pivot-total-col').checked,
                prefix: document.getElementById('pivot-prefix').value,
                prefix_en: document.getElementById('pivot-prefix-en').value,
                mappings: {}
            },
            merge_datasets: document.getElementById('merge-datasets')?.checked || false,
            show_total_col: document.getElementById('show-total-col')?.checked || false,
            group_by_region: document.getElementById('group-by-region')?.checked || false
        };

        document.querySelectorAll('.pivot-map-id').forEach((input, idx) => {
            const cat = input.dataset.cat;
            config.pivot.mappings[cat] = {
                label_id: input.value || cat,
                label_en: document.querySelectorAll('.pivot-map-en')[idx].value || ''
            };
        });

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

    function formatNumber(num, type = 'number') {
        if (isNaN(num)) return num;
        switch(type) {
            case 'decimal': return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            case 'percent': return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
            case 'currency': return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            default: return num.toLocaleString('id-ID');
        }
    }

    function openConfigModal() {
        const modalEl = document.getElementById('configModal');
        let bootstrapModal = bootstrap.Modal.getInstance(modalEl);
        if (!bootstrapModal) bootstrapModal = new bootstrap.Modal(modalEl);
        
        const container = document.getElementById('col-list-container');
        container.innerHTML = '';

        const firstTable = rawApiResults[0]; 
        const dataRows = getRowsFromApiResult(firstTable);
        const baseKeys = (dataRows.length > 0) ? Object.keys(dataRows[0]) : [];

        // AMBIL CONFIG YANG ADA ATAU DEFAULT (STATEFUL)
        if (!currentTableConfig) {
            currentTableConfig = {
                columns: baseKeys.map(k => ({ key: k, label_id: k, label_en: '', visible: true, format: 'number' })),
                pivot: { enabled: false, row: [], col: [], val: [], total_row: true, total_col: true, prefix: '', prefix_en: '', mappings: {} },
                merge_datasets: false,
                show_total_col: false,
                group_by_region: true
            };
        }
        
        // MIGRASI/NORMALISASI CONFIG LAMA
        if (!currentTableConfig.pivot) {
            currentTableConfig.pivot = { enabled: false, row: [], col: [], val: [], total_row: true, total_col: true, prefix: '', prefix_en: '', mappings: {} };
        }
        ['row', 'col', 'val'].forEach(t => {
            if (currentTableConfig.pivot[t] && !Array.isArray(currentTableConfig.pivot[t])) {
                currentTableConfig.pivot[t] = [currentTableConfig.pivot[t]];
            } else if (!currentTableConfig.pivot[t]) {
                currentTableConfig.pivot[t] = [];
            }
        });
        if (currentTableConfig.pivot.total_col === undefined) currentTableConfig.pivot.total_col = true;

        // Render Kolom
        currentTableConfig.columns.forEach(col => {
            const div = document.createElement('div');
            div.className = 'col-item d-flex align-items-center gap-3 bg-light p-2 rounded-3 mb-2 border border-light';
            div.dataset.key = col.key;
            div.innerHTML = `
                <i class="bi bi-grip-vertical text-muted fs-5 cursor-move"></i>
                <input class="form-check-input visibility-check ms-0" type="checkbox" ${col.visible ? 'checked' : ''}>
                <div class="flex-grow-1">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <small class="font-monospace fw-bold text-primary">${col.key}</small>
                            <select class="form-select form-select-sm col-format border-0 bg-white py-0 pe-4" style="width:auto; font-size:9px;">
                                <option value="number" ${col.format === 'number' ? 'selected' : ''}>Angka</option>
                                <option value="decimal" ${col.format === 'decimal' ? 'selected' : ''}>Desimal (0,00)</option>
                                <option value="percent" ${col.format === 'percent' ? 'selected' : ''}>Persentase (%)</option>
                                <option value="currency" ${col.format === 'currency' ? 'selected' : ''}>Mata Uang (Rp)</option>
                            </select>
                        </div>
                        <div class="col-6"><input type="text" class="form-control form-control-sm label-id border-0 shadow-sm" value="${col.label_id}" placeholder="Label ID"></div>
                        <div class="col-6"><input type="text" class="form-control form-control-sm label-en border-0 shadow-sm" value="${col.label_en}" placeholder="Label EN"></div>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });

        new Sortable(container, { animation: 150, handle: '.cursor-move' });

        // Restore Pivot Checklists (Row, Col, Val)
        ['row', 'col', 'val'].forEach(type => {
            const container = document.getElementById(`pivot-${type}-container`);
            container.innerHTML = '';
            baseKeys.forEach(key => {
                const isChecked = Array.isArray(currentTableConfig.pivot[type]) && currentTableConfig.pivot[type].includes(key) ? 'checked' : '';
                const div = document.createElement('div');
                div.className = 'form-check small mb-1';
                div.innerHTML = `
                    <input class="form-check-input pivot-${type}-check" type="checkbox" value="${key}" id="chk-${type}-${key}" ${isChecked} onchange="refreshPivotOrderList('${type}')">
                    <label class="form-check-label fw-bold text-dark" for="chk-${type}-${key}" style="font-size:11px;">${key}</label>
                `;
                container.appendChild(div);
            });

            refreshPivotOrderList(type);
            new Sortable(document.getElementById(`pivot-${type}-order-list`), { 
                animation: 100, 
                onEnd: () => { updatePivotMappingUI(); } 
            });
        });

        // Restore Switch & Values
        document.getElementById('pivot-toggle').checked = currentTableConfig.pivot.enabled;
        document.getElementById('pivot-settings').style.display = currentTableConfig.pivot.enabled ? 'block' : 'none';
        document.getElementById('pivot-prefix').value = currentTableConfig.pivot.prefix || '';
        document.getElementById('pivot-prefix-en').value = currentTableConfig.pivot.prefix_en || '';
        document.getElementById('pivot-total-row').checked = currentTableConfig.pivot.total_row !== false;
        document.getElementById('pivot-total-col').checked = currentTableConfig.pivot.total_col !== false;
        document.getElementById('merge-datasets').checked = currentTableConfig.merge_datasets;
        document.getElementById('show-total-col').checked = currentTableConfig.show_total_col || false;
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
            // Remove redundant listeners as lists are refreshed
            document.getElementById('pivot-total-row').addEventListener('change', updatePivotMappingUI);
            document.getElementById('pivot-total-col').addEventListener('change', updatePivotMappingUI);
            toggle.dataset.hasListener = "true";
        }

        bootstrapModal.show();
    }

    function refreshPivotOrderList(type = 'col') {
        const checked = Array.from(document.querySelectorAll(`.pivot-${type}-check:checked`)).map(cb => cb.value);
        const orderContainer = document.getElementById(`selected-pivot-${type}-order`);
        const list = document.getElementById(`pivot-${type}-order-list`);
        
        // Use saved order if current UI list is empty (first time opening modal)
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
                badge.className = `pivot-${type}-order-item badge bg-primary cursor-move p-2`;
                badge.dataset.key = key;
                badge.innerHTML = `<i class="bi bi-grip-vertical me-1"></i>${key}`;
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

        mappingArea.style.display = 'block';
        mappingList.innerHTML = '';
        
        allTargetCols.forEach(col => {
            let saved = currentTableConfig?.pivot?.mappings?.[col.key] || { label_id: col.key, label_en: '' };
            const div = document.createElement('div');
            div.className = 'mb-3 pb-3 border-bottom border-white';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-bold text-muted font-monospace">Key: ${col.key}</span>
                    <span class="badge bg-white text-primary border" style="font-size:9px">${col.type}</span>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="d-block" style="font-size:9px; color:#aaa">Label ID (Indonesia)</label>
                        <input type="text" class="form-control form-control-sm pivot-map-id border-0 bg-white" data-cat="${col.key}" value="${saved.label_id}" placeholder="Label ID">
                    </div>
                    <div class="col-6">
                        <label class="d-block" style="font-size:9px; color:#aaa">Label EN (English)</label>
                        <input type="text" class="form-control form-control-sm pivot-map-en border-0 bg-white" value="${saved.label_en}" placeholder="Label EN">
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
        container.innerHTML = '<div class="text-center p-3">Menyiapkan...</div>';
        
        setTimeout(() => {
            const tempDiv = document.createElement('div');
            renderCustomTable(0, config, tempDiv, 5); // Hanya 5 baris
            container.innerHTML = tempDiv.innerHTML;
        }, 300);
    }
</script>
