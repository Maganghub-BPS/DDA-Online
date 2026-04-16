<script>
    const BPS_REGIONAL_MAP = {
        "3301": "Cilacap",
        "3302": "Banyumas",
        "3303": "Purbalingga",
        "3304": "Banjarnegara",
        "3305": "Kebumen",
        "3306": "Purworejo",
        "3307": "Wonosobo",
        "3308": "Magelang",
        "3309": "Boyolali",
        "3310": "Klaten",
        "3311": "Sukoharjo",
        "3312": "Wonogiri",
        "3313": "Karanganyar",
        "3314": "Sragen",
        "3315": "Grobogan",
        "3316": "Blora",
        "3317": "Rembang",
        "3318": "Pati",
        "3319": "Kudus",
        "3320": "Jepara",
        "3321": "Demak",
        "3322": "Semarang",
        "3323": "Temanggung",
        "3324": "Kendal",
        "3325": "Batang",
        "3326": "Pekalongan",
        "3327": "Pemalang",
        "3328": "Tegal",
        "3329": "Brebes",
        "3371": "Kota Magelang",
        "3372": "Kota Surakarta",
        "3373": "Kota Salatiga",
        "3374": "Kota Semarang",
        "3375": "Kota Pekalongan",
        "3376": "Kota Tegal"
    };

    const REGENCY_NAME_MAP = {};
    const CITY_NAME_MAP = {};
    Object.entries(BPS_REGIONAL_MAP).forEach(([code, name]) => {
        const clean = name.replace(/^Kota\s+/i, '').toLowerCase();
        if (parseInt(code) >= 3371) {
            CITY_NAME_MAP[clean] = code;
        } else {
            REGENCY_NAME_MAP[clean] = code;
        }
    });

    function getBpsCode(item) {
        // 1. Coba cari berdasarkan kolom kode eksplisit
        const keywords = ['kode', 'bps', 'kod_wil', 'kd_wil', 'kodwil', 'id_wilayah', 'daerah', 'kab_kot'];
        const kodeKey = Object.keys(item).find(k => keywords.some(key => k.toLowerCase().includes(key)));

        if (kodeKey) {
            let raw = String(item[kodeKey] || '').trim();
            
            // Jika isinya angka murni dan panjangnya 4, kemungkinan besar itu kode BPS
            if (/^\d{4}$/.test(raw) && BPS_REGIONAL_MAP[raw]) return raw;

            let cleanCode = raw.replace(/\./g, '');
            if (cleanCode.length > 4) cleanCode = cleanCode.substring(0, 4);

            if (raw.includes('.')) {
                let parts = raw.split('.');
                if (parts.length >= 2) {
                    let prov = parts[0];
                    let kab = parts[1] || "";
                    let tryExact = prov + kab;
                    if (BPS_REGIONAL_MAP[tryExact]) return tryExact;
                    if (kab.length === 1) {
                        if (BPS_REGIONAL_MAP[prov + kab + '0']) return prov + kab + '0';
                        if (BPS_REGIONAL_MAP[prov + '0' + kab]) return prov + '0' + kab;
                    }
                }
            }
            if (BPS_REGIONAL_MAP[cleanCode]) return cleanCode;
            let match = raw.match(/\d{4}/);
            if (match && BPS_REGIONAL_MAP[match[0]]) return match[0];
        }

        // 2. Jika tidak ada kode eksplisit, coba cari berdasarkan kolom Nama Wilayah
        const nameKeywords = ['wilayah', 'kab', 'kot', 'nama', 'label', 'unit'];
        const nameKey = Object.keys(item).find(k => nameKeywords.some(kw => k.toLowerCase().includes(kw)));
        if (nameKey) {
            const rawValue = String(item[nameKey] || '').toLowerCase();
            
            // FALLBACK: Jika isinya ternyata angka 4 digit (seperti 3305) di kolom yang harusnya nama
            if (/^\d{4}$/.test(rawValue.trim())) {
                const cleanCode = rawValue.trim();
                if (BPS_REGIONAL_MAP[cleanCode]) return cleanCode;
            }

            const isKota = rawValue.includes('kota') || rawValue.includes('city');
            const isKab = rawValue.includes('kab.') || rawValue.includes('kabupaten') || rawValue.includes('regency');
            
            // Perbaikan regex: \s* agar spasi bersifat opsional (menangani Kab.Cilacap tanpa spasi)
            const clean = rawValue.replace(/^kab\.\s*|^kabupaten\s*|^kota\s*/i, '').trim();
            if (isKota) return CITY_NAME_MAP[clean] || '9999';
            if (isKab) return REGENCY_NAME_MAP[clean] || '9999';
            return REGENCY_NAME_MAP[clean] || CITY_NAME_MAP[clean] || '9999';
        }
        return '9999';
    }

    function getNormalizedRegencyName(item) {
        const code = getBpsCode(item);
        return code !== '9999' ? BPS_REGIONAL_MAP[code] : null;
    }

    function formatVal(val, formatType = 'number') {
        if (val === '-' || val === null || val === undefined) return '-';
        const num = parseFloat(String(val).replace(',', '.')) || 0;
        if (isNaN(num)) return val;
        switch (formatType) {
            case 'decimal':
                return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            case 'percent':
                return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
            case 'currency':
                return 'Rp' + num.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            case 'ribuan':
                return Math.round(num / 1000).toLocaleString('id-ID');
            case 'jutaan':
                return Math.round(num / 1000000).toLocaleString('id-ID');
            default:
                return num.toLocaleString('id-ID');
        }
    }

    function sortArrayWithOrder(arr, order) {
        if (!order || !Array.isArray(order) || order.length === 0) {
            return arr.sort((a, b) => String(a).localeCompare(String(b)));
        }
        return arr.sort((a, b) => {
            let idxA = order.indexOf(a);
            let idxB = order.indexOf(b);
            if (idxA === -1) idxA = 999;
            if (idxB === -1) idxB = 999;
            if (idxA === idxB) return String(a).localeCompare(String(b));
            return idxA - idxB;
        });
    }

    function getRowsFromApiResult(apiResult) {
        if (!apiResult) return [];
        if (apiResult.data && Array.isArray(apiResult.data)) {
            if (apiResult.data[0] && typeof apiResult.data[0] === 'object') return apiResult.data;
        }
        if (apiResult.data && apiResult.data.rows) return apiResult.data.rows;
        if (Array.isArray(apiResult)) return apiResult;
        return [];
    }

    function pivotData(data, config) {
        const rowKeys = config.pivot.row || [];
        const colKeys = config.pivot.col || [];
        const valKeys = config.pivot.val || [];
        if (data.length === 0) return data;
        if (!config.pivot.metrics_as_row) {
            if (rowKeys.length === 0 || colKeys.length === 0 || valKeys.length === 0) return data;
        } else {
            if (colKeys.length === 0 || valKeys.length === 0) return data;
        }
        const normalizedData = data.map(item => {
            const newItem = { ...item };
            const bakuName = getNormalizedRegencyName(newItem);
            if (bakuName) {
                const possibleNameKeys = Object.keys(newItem).filter(k => k.toLowerCase().includes('wilayah') || k.toLowerCase().includes('kab') || k.toLowerCase().includes('kot'));
                possibleNameKeys.forEach(nk => newItem[nk] = bakuName);
            }
            
            // --- NEW: Aggressive Mapping for Merging ---
            rowKeys.forEach(rk => {
                const val = String(newItem[rk] || '').trim();
                const mapping = config.pivot.mappings?.[val];
                if (mapping) newItem[rk] = mapping.label_id;
            });
            colKeys.forEach(ck => {
                const val = String(newItem[ck] || '').trim();
                const mapping = config.pivot.mappings?.[val];
                if (mapping) newItem[ck] = mapping.label_id;
            });
            // ------------------------------------------

            return newItem;
        });
        const generateKey = (item, keys) => keys.map(k => String(item[k] || 'N/A').trim()).join(' || ');
        let categories = [...new Set(normalizedData.map(item => generateKey(item, colKeys)))];
        sortArrayWithOrder(categories, config.pivot.mapping_order);
        if (config.pivot.metrics_as_row) {
            const results = [];
            // Jika ada rowKeys, kita kelompokkan metrik di bawah setiap grup tersebut
            if (rowKeys.length > 0) {
                const groups = [...new Set(normalizedData.map(item => generateKey(item, rowKeys)))];
                groups.forEach(groupKey => {
                    valKeys.forEach(vk => {
                        const metricLabel = config.pivot.mappings?.[vk]?.label_id || vk;
                        const metricParts = metricLabel.split(/\s*\|\|\s*/);
                        
                        // Gunakan bagian pertama metrik sebagai bagian dari hierarki baris pertama
                        const combinedKey = groupKey + ' || ' + metricParts[0];
                        const row = { 'Uraian': combinedKey, 'isMetricRow': true, 'originalKey': vk };
                        
                        const groupItems = normalizedData.filter(item => generateKey(item, rowKeys) === groupKey);
                        if (groupItems.length > 0) {
                            rowKeys.forEach((rk, rkIdx) => {
                                if (rkIdx === 0) {
                                    row[rk] = combinedKey;
                                } else {
                                    // Jika metrik memiliki bagian tambahan (misal: Satuan), masukkan ke kolom baris berikutnya
                                    if (metricParts[rkIdx]) {
                                        row[rk] = metricParts[rkIdx];
                                    } else {
                                        row[rk] = groupItems[0][rk];
                                    }
                                }
                            });
                        }

                        categories.forEach(cat => {
                            const cellData = groupItems.filter(item => generateKey(item, colKeys) === cat);
                            // Agregasi: Sum (Default)
                            const sum = cellData.reduce((acc, item) => acc + (parseFloat(item[vk]) || 0), 0);
                            row[cat] = sum;
                        });
                        if (config.pivot.total_row) row['Jumlah'] = categories.reduce((acc, cat) => acc + (row[cat] || 0), 0);
                        results.push(row);
                    });
                });
            } else {
                // Perilaku asli jika tidak ada rowKeys
                valKeys.forEach(vk => {
                    const row = { 'Uraian': vk, 'isMetricRow': true, 'originalKey': vk };
                    categories.forEach(cat => {
                        const cellData = normalizedData.filter(item => generateKey(item, colKeys) === cat);
                        const sum = cellData.reduce((acc, item) => acc + (parseFloat(item[vk]) || 0), 0);
                        row[cat] = sum;
                    });
                    if (config.pivot.total_row) row['Jumlah'] = categories.reduce((acc, cat) => acc + (row[cat] || 0), 0);
                    results.push(row);
                });
            }
            return results;
        }
        const grouped = {};
        normalizedData.forEach(item => {
            const groupKey = generateKey(item, rowKeys);
            if (!grouped[groupKey]) {
                grouped[groupKey] = {};
                rowKeys.forEach(rk => grouped[groupKey][rk] = item[rk]);
                const keywords = ['kode', 'bps', 'kod_wil', 'kd_wil', 'kodwil', 'id_wilayah', 'daerah', 'kab_kot'];
                const kodeKey = Object.keys(item).find(k => keywords.some(key => k.toLowerCase().includes(key)));
                if (kodeKey) grouped[groupKey][kodeKey] = item[kodeKey];
                categories.forEach(cat => { valKeys.forEach(vk => { grouped[groupKey][cat + ' || ' + vk] = 0; }); });
                if (config.pivot.total_row) grouped[groupKey]['Jumlah'] = 0;
            }
            const catKey = generateKey(item, colKeys);
            valKeys.forEach(vk => {
                const currentVal = parseFloat(item[vk]) || 0;
                const finalKey = catKey + ' || ' + vk;
                grouped[groupKey][finalKey] = (grouped[groupKey][finalKey] || 0) + currentVal;
                if (config.pivot.total_row) grouped[groupKey]['Jumlah'] += currentVal;
            });
        });
        const finalArray = Object.values(grouped);
        const order = config.pivot.mapping_order || [];
        finalArray.sort((a, b) => {
            const codeA = getBpsCode(a);
            const codeB = getBpsCode(b);

            // Prioritas 1: Standar BPS (Khusus Wilayah)
            if (codeA !== codeB && codeA !== '9999' && codeB !== '9999') return codeA.localeCompare(codeB);

            // Prioritas 2: Urutan Kustom (Drag & Drop)
            const nameA = String(a[rowKeys[0]] || '');
            const nameB = String(b[rowKeys[0]] || '');
            let idxA = order.indexOf(nameA);
            let idxB = order.indexOf(nameB);
            if (idxA !== -1 && idxB !== -1) return idxA - idxB;
            if (idxA !== -1) return -1;
            if (idxB !== -1) return 1;

            // Prioritas 3: Abjad (Fallback)
            return nameA.localeCompare(nameB);
        });
        return finalArray;
    }

    function renderCustomTable(tableIdx, config, targetEl = null, limitRows = 0) {
        const tableId = 'dda-table-' + tableIdx;
        const tableEl = targetEl || document.getElementById(tableId);
        if (!tableEl) return;
        let rawDataFull = [];
        const isPreview = (targetEl !== null);
        if (config.merge_datasets) {
            const allWrappers = document.querySelectorAll('.table-wrapper');
            allWrappers.forEach((w, i) => { if (!isPreview && i > 0) w.style.display = 'none'; });
            rawApiResults.forEach(res => {
                const rows = getRowsFromApiResult(res);
                const sourceName = res.judul || res.name || res.label || "Dataset " + (res.id_portal || "");
                rows.forEach(r => { let newRow = { ...r }; newRow['Nama Dataset'] = sourceName; rawDataFull.push(newRow); });
            });
        } else {
            rawDataFull = getRowsFromApiResult(rawApiResults[tableIdx]);
            if (!isPreview) {
                const allWrappers = document.querySelectorAll('.table-wrapper');
                allWrappers.forEach(w => w.style.display = '');
            }
        }
        if (rawDataFull.length === 0) {
            if (targetEl) targetEl.innerHTML = '<div class="alert alert-warning">Tidak ada data.</div>';
            return;
        }
        rawDataFull.sort((a, b) => {
            const codeA = getBpsCode(a);
            const codeB = getBpsCode(b);
            if (codeA !== codeB) return codeA.localeCompare(codeB);
            return String(a.kab_ko || '').localeCompare(String(b.kab_ko || ''));
        });
        let rawData = rawDataFull;
        if (currentSelectedYears.length > 0) {
            rawData = rawDataFull.filter(item => {
                const itemYear = String(item.tahun || item.tahun_data || item.year || "");
                return currentSelectedYears.includes(itemYear);
            });
        }
        let finalCols = [];
        let isPivotReady = config.pivot.enabled && config.pivot.col?.length > 0 && config.pivot.val?.length > 0;
        if (isPivotReady && !config.pivot.metrics_as_row && config.pivot.row?.length === 0) isPivotReady = false;
        if (isPivotReady) {
            const rowKeys = config.pivot.row || [];
            const colKeys = config.pivot.col;
            const valKeys = config.pivot.val;
            const generateKey = (item, keys) => keys.map(k => String(item[k] || 'N/A')).join(' || ');
            let categories = [...new Set(rawData.map(item => generateKey(item, colKeys)))];
            sortArrayWithOrder(categories, config.pivot.mapping_order);
            sortArrayWithOrder(valKeys, config.pivot.mapping_order);
            sortArrayWithOrder(rowKeys, config.pivot.mapping_order);
            rawData = pivotData(rawData, config);
            if (config.pivot.metrics_as_row) {
                if (rowKeys.length > 0) {
                    rowKeys.forEach(rk => {
                        const colConfig = config.columns.find(c => c.key === rk) || { key: rk, label_id: rk, label_en: '' };
                        finalCols.push({ ...colConfig, isRow: true });
                    });
                } else {
                    finalCols.push({ key: 'Uraian', label_id: 'Uraian', label_en: 'Description', isRow: true });
                }
                categories.forEach(cat => {
                    const mapping = config.pivot.mappings?.[cat] || { label_id: cat, label_en: '' };
                    const prefix = config.pivot.prefix ? (config.pivot.prefix.trim() + ' || ') : '';
                    const prefix_en = config.pivot.prefix_en ? (config.pivot.prefix_en.trim() + ' || ') : '';
                    finalCols.push({ key: cat, label_id: prefix + mapping.label_id, label_en: prefix_en + mapping.label_en, format: 'number' });
                });
            } else {
                rowKeys.forEach(rk => {
                    const mapping = config.pivot.mappings?.[rk] || { label_id: rk, label_en: '' };
                    finalCols.push({ key: rk, label_id: mapping.label_id, label_en: mapping.label_en, isRow: true });
                });
                const loops = config.pivot.metric_first ? [valKeys, categories] : [categories, valKeys];
                loops[0].forEach(outer => {
                    loops[1].forEach(inner => {
                        const cat = config.pivot.metric_first ? inner : outer;
                        const vk = config.pivot.metric_first ? outer : inner;
                        const catMapping = config.pivot.mappings?.[cat] || { label_id: cat, label_en: '' };
                        const valMapping = config.pivot.mappings?.[vk] || { label_id: vk, label_en: '' };
                        const prefix = config.pivot.prefix ? (config.pivot.prefix.trim() + ' || ') : '';
                        const prefix_en = config.pivot.prefix_en ? (config.pivot.prefix_en.trim() + ' || ') : '';
                        let labelID = config.pivot.metric_first ? (prefix + valMapping.label_id + (categories.length > 0 ? ' || ' + catMapping.label_id : '')) : (prefix + catMapping.label_id + (valKeys.length > 1 ? ' || ' + valMapping.label_id : ''));
                        let labelEN = config.pivot.metric_first ? (prefix_en + (valMapping.label_en || '') + (categories.length > 0 ? ' || ' + (catMapping.label_en || '') : '')) : (prefix_en + (catMapping.label_en || '') + (valKeys.length > 1 ? ' || ' + (valMapping.label_en || '') : ''));
                        finalCols.push({ key: cat + ' || ' + vk, label_id: labelID, label_en: labelEN, format: config.columns.find(c => c.key === vk)?.format || 'number', hidden: config.pivot.only_total || false });
                    });
                });
            }
            if (config.pivot.total_row) finalCols.push({ key: 'Jumlah', label_id: 'Jumlah', label_en: 'Total', format: 'number' });
        } else {
            finalCols = config.columns.filter(c => c.visible);
        }

        // --- NEW: Horizontal Sub-total per Group ---
        if (config.show_group_total) {
            const newFinalCols = [];
            let currentGroupCols = [];
            let lastGroup = null;

            finalCols.forEach((col, idx) => {
                const parts = (col.label_id || col.key).split(' || ');
                const mainGroup = parts[0];

                if (lastGroup !== null && mainGroup !== lastGroup) {
                    if (currentGroupCols.length > 1) {
                        newFinalCols.push({
                            key: 'group_total_' + idx,
                            label_id: lastGroup + ' || Jumlah',
                            label_en: lastGroup + ' || Total',
                            isGroupTotal: true,
                            sourceKeys: currentGroupCols.map(c => c.key),
                            format: 'number'
                        });
                    }
                    currentGroupCols = [];
                }

                newFinalCols.push(col);
                if (!col.isRow && !col.key.toLowerCase().includes('tahun')) {
                    currentGroupCols.push(col);
                }
                lastGroup = mainGroup;
            });

            if (currentGroupCols.length > 1 && lastGroup !== null) {
                newFinalCols.push({
                    key: 'group_total_last',
                    label_id: lastGroup + ' || Jumlah',
                    label_en: lastGroup + ' || Total',
                    isGroupTotal: true,
                    sourceKeys: currentGroupCols.map(c => c.key),
                    format: 'number'
                });
            }
            finalCols = newFinalCols;
        }
        const parseHeaderTree = (columns) => {
            const tree = [];
            columns.filter(c => !c.hidden).forEach(col => {
                const idParts = (col.label_id || col.key).split(' || ');
                const enParts = (col.label_en || '').split(' || ');
                let currentNode = tree;
                idParts.forEach((part, depth) => {
                    let node = currentNode.find(n => n.label === part);
                    if (!node) { node = { label: part, label_en: enParts[depth] || '', children: [], depth: depth, key: col.key }; currentNode.push(node); }
                    currentNode = node.children;
                });
            });
            return tree;
        };
        const headerTree = parseHeaderTree(finalCols);
        const getMaxDepth = (nodes) => Math.max(0, ...nodes.map(n => n.children.length > 0 ? 1 + getMaxDepth(n.children) : 1));
        const maxHeaderRows = getMaxDepth(headerTree);
        const calculateColspan = (node) => {
            if (node.children.length === 0) return 1;
            node.colspan = node.children.reduce((sum, child) => sum + calculateColspan(child), 0);
            return node.colspan;
        };
        headerTree.forEach(calculateColspan);
        const headerRows = Array.from({ length: maxHeaderRows }, () => []);
        const fillHeaderRows = (nodes, currentRow) => {
            nodes.forEach(node => {
                if (node.children.length > 0) {
                    headerRows[currentRow].push({ label: node.label, label_en: node.label_en, colspan: node.colspan, rowspan: 1 });
                    fillHeaderRows(node.children, currentRow + 1);
                } else {
                    headerRows[currentRow].push({ label: node.label, label_en: node.label_en, colspan: 1, rowspan: maxHeaderRows - currentRow });
                }
            });
        };
        fillHeaderRows(headerTree, 0);
        const rowspanMap = {};
        const lastRowValues = {};
        const startIndices = {};
        const rowHeaderCols = finalCols.filter(c => c.isRow);
        rawData.forEach((row, r_idx) => {
            let path = "";
            rowHeaderCols.forEach((col, c_idx) => {
                path += (c_idx > 0 ? "||" : "") + String(row[col.key] || '');
                if (!lastRowValues[col.key] || lastRowValues[col.key] !== path) { rowspanMap[col.key + '-' + r_idx] = 0; startIndices[col.key] = r_idx; lastRowValues[col.key] = path; }
                rowspanMap[col.key + '-' + startIndices[col.key]]++;
            });
        });
        const hasVisibleApiTotal = finalCols.some(c => !c.hidden && !c.isRow && (c.label_id.toLowerCase().includes('jumlah') || c.label_id.toLowerCase().includes('total')));
        const showTotal = (config.show_total_col || (config.pivot && config.pivot.enabled && config.pivot.total_row)) && !hasVisibleApiTotal;
        let html = `<table class="${isPreview ? 'table table-bordered table-sm' : 'main-table dda-table-item'}" style="width:100%; border-collapse: collapse; background:#fff;">`;
        html += '<thead style="background:#FF6D1F; color:#fff;">';
        headerRows.forEach((row, rIdx) => {
            html += '<tr>';
            if (rIdx === 0) html += `<th rowspan="${maxHeaderRows + 1}" style="border:1px solid #fff; width:40px;">No.</th>`;
            row.forEach(cell => { html += `<th colspan="${cell.colspan}" rowspan="${cell.rowspan}" style="border:1px solid #fff; padding:8px; text-align:center;">${cell.label}${cell.label_en ? `<br><i>${cell.label_en}</i>` : ''}</th>`; });
            if (rIdx === 0 && showTotal) html += `<th rowspan="${maxHeaderRows}" style="border:1px solid #fff; ${config.pivot.only_total ? 'background:#FF6D1F;' : ''}">Jumlah<br><i>Total</i></th>`;
            html += '</tr>';
        });
        html += '<tr style="background:#f9a066; color:#000; font-size:10px;">';
        finalCols.filter(c => !c.hidden).forEach((c, idx) => html += `<th style="border:1px solid #fff; text-align:center;">(${idx + 1})</th>`);
        if (showTotal) html += `<th style="border:1px solid #fff; text-align:center;">(${finalCols.filter(c => !c.hidden).length + 1})</th>`;
        html += '</tr></thead><tbody>';
        let lastTipe = null; 
        let lastLevel1 = null; 
        let lastLevel2 = null;
        let lastRowGroup = null; // Legacy support
        let sectionIdx = 0;
        const displayData = limitRows > 0 ? rawData.slice(0, limitRows) : rawData;
        const verticalTotals = {};
        finalCols.forEach(col => verticalTotals[col.key] = 0);
        displayData.forEach((row, r_idx) => {
            const fullCode = getBpsCode(row);
            const curCodeInt = parseInt(fullCode);
            const isKotaRow = (curCodeInt >= 3371 && curCodeInt <= 3376);
            const tipeLabel = (fullCode === '9999') ? null : (isKotaRow ? 'Kota / Municipality' : 'Kabupaten / Regency');
            if (config.group_by_region && tipeLabel && tipeLabel !== lastTipe) {
                html += `<tr style="background:#fff1e6; font-weight:bold; color:#000;"><td colspan="${finalCols.length + 2}" style="padding:10px 15px; border:1px solid #eee; border-left:2px solid #FF6D1F;">${tipeLabel}</td></tr>`;
                lastTipe = tipeLabel; sectionIdx = 0;
            }
            sectionIdx++;
            
            let rowHtml = "";
            let rowClass = (r_idx % 2 === 0 ? '' : 'background:#fff4eb;');
            
            // --- NEW: Row Hierarchy Logic (Optimized) ---
            const fCol = finalCols[0];
            let rawFVal = (fCol.isGroupTotal) 
                ? fCol.sourceKeys.reduce((acc, k) => acc + (parseFloat(String(row[k] || 0).replace(',', '.')) || 0), 0)
                : (row[fCol.key] ?? 0);
            
            let fMapped = rawFVal;
            if (config.pivot.metrics_as_row && fCol.key === 'Uraian' && row.originalKey) {
                const mapping = config.pivot.mappings?.[row.originalKey];
                if (mapping) fMapped = mapping.label_id || rawFVal;
            } else {
                const mapping = config.pivot.mappings?.[rawFVal];
                if (mapping) fMapped = mapping.label_id || rawFVal;
            }

            const rowParts = String(fMapped).split(/\s*\|\|\s*/);
            const visibleCols = finalCols.filter(c => !c.hidden).length;
            const totalColSpan = visibleCols + (showTotal ? 2 : 1);

            if (rowParts.length > 1) {
                const level1 = rowParts[0];
                const level2 = rowParts.length > 2 ? rowParts[1] : null;

                // Handle Level 1 Header (Master Divider)
                if (level1 !== lastLevel1) {
                    html += `<tr style="background:#e9ecef; font-weight:bold; color:#1a252f;"><td colspan="${totalColSpan}" style="padding:10px 15px; border:1px solid #dee2e6; border-left:5px solid #6c757d;">${level1}</td></tr>`;
                    lastLevel1 = level1;
                    lastLevel2 = null; // Reset level 2 when level 1 changes
                }

                // Handle Level 2 Sub-Header (Sub-Category Row)
                if (level2 && level2 !== lastLevel2) {
                    html += `<tr style="background:#fcfcfc; font-weight:bold;"><td colspan="${totalColSpan}" style="padding:8px 15px; border:1px solid #eee; border-left:2px solid #adb5bd;">${level2}</td></tr>`;
                    lastLevel2 = level2;
                }
            } else {
                lastLevel1 = null;
                lastLevel2 = null;
            }
            // ------------------------------------------

            html += `<tr style="${rowClass}">`;
            const firstRowCol = rowHeaderCols[0]?.key;
            const noRowspan = firstRowCol ? rowspanMap[firstRowCol + '-' + r_idx] : 1;
            if (noRowspan !== 0) html += `<td ${noRowspan > 1 ? `rowspan="${noRowspan}"` : ''} style="border:1px solid #eee; text-align:center; border-left:2px solid #FF6D1F; vertical-align:top; padding-top:8px;">${config.group_by_region ? sectionIdx : r_idx + 1}.</td>`;
            let rowSum = 0;
            finalCols.forEach((col, c_idx) => {
                const rsValue = rowspanMap[col.key + '-' + r_idx];
                if (rsValue === 0) return;

                let rawVal = 0;
                if (col.isGroupTotal) {
                    rawVal = col.sourceKeys.reduce((acc, k) => acc + (parseFloat(String(row[k] || 0).replace(',', '.')) || 0), 0);
                } else {
                    rawVal = row[col.key] ?? 0;
                }

                let num = parseFloat(String(rawVal).replace(',', '.')) || 0;
                let isNumeric = !isNaN(parseFloat(String(rawVal))) && isFinite(String(rawVal).replace(',', '.'));
                const isTotalInLabel = col.label_id.toLowerCase().includes('jumlah') || col.label_id.toLowerCase().includes('total');
                const isTahunCol = col.key.toLowerCase().includes('tahun') || col.key.toLowerCase().includes('year');
                
                if (isNumeric && !col.isRow && !isTahunCol) { 
                    verticalTotals[col.key] = (verticalTotals[col.key] || 0) + num; 
                    if (!isTotalInLabel) rowSum += num; 
                }
                let displayVal = rawVal;
                    if (col.isRow || c_idx === 0) {
                        const bakuName = getNormalizedRegencyName(row);
                        displayVal = String(rawVal).toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                        
                        if (c_idx === 0 && !config.pivot.metrics_as_row) {
                            if (bakuName) {
                                displayVal = bakuName;
                            }
                            // Tambahkan prefix "Kabupaten/Kota" HANYA jika ini wilayah (bakuName ada)
                            if (bakuName && !config.group_by_region && !displayVal.includes('Kabupaten') && !displayVal.includes('Kota') && !displayVal.includes('Provinsi')) {
                                displayVal = (isKotaRow ? 'Kota ' : 'Kabupaten ') + displayVal;
                            }
                        }

                        let mappingKey = rawVal;
                        if (config.pivot.metrics_as_row && col.key === 'Uraian' && row.originalKey) mappingKey = row.originalKey;
                        const mapping = config.pivot.mappings?.[mappingKey] || config.pivot.mappings?.[rawVal];
                        if (mapping) {
                            displayVal = mapping.label_id || displayVal;
                        }
                        
                        // Menangani indentasi/pembersihan jika ada hierarki || (Pindahkan ke luar mapping agar kena ke hasil pivot otomatis)
                        const parts = displayVal.split(/\s*\|\|\s*/);
                        if (parts.length > 1) {
                            const indent = (parts.length - 1) * 20;
                            displayVal = `<span style="padding-left:${indent}px">${parts[parts.length - 1]}</span>`;
                        }
                    } else displayVal = isNumeric ? formatVal(num, col.format || 'number') : rawVal;
                if (col.hidden) return;
                html += `<td ${rsValue > 1 ? `rowspan="${rsValue}"` : ''} style="border:1px solid #eee; padding:8px; ${col.isRow || c_idx === 0 ? 'font-weight:bold;' : 'text-align:center;'}">${displayVal}</td>`;
            });
            if (showTotal) html += `<td style="border:1px solid #eee; text-align:center; font-weight:bold; background:#fffafa;">${formatVal(rowSum, 'number')}</td>`;
            html += '</tr>';
        });
        if (config.pivot.total_col) {
            const jtMapping = config.pivot.mappings?.['Jawa Tengah'] || { label_id: 'Jawa Tengah', label_en: 'Central Java' };
            html += `<tr style="background: #FF6D1F; color: #fff; font-weight: bold;"><td colspan="2" style="padding:10px; border:1px solid #fff;">${jtMapping.label_id}${jtMapping.label_en ? `<br><i>${jtMapping.label_en}</i>` : ''}</td>`;
            let grandTotal = 0;
            finalCols.forEach((col, c_idx) => {
                if (c_idx === 0 || col.hidden) return;
                if (col.isRow || col.key.toLowerCase().includes('tahun') || col.key.toLowerCase().includes('year')) html += `<td style="border:1px solid #fff;"></td>`;
                else {
                    const sum = verticalTotals[col.key];
                    if (!(col.label_id.toLowerCase().includes('jumlah') || col.label_id.toLowerCase().includes('total'))) grandTotal += sum;
                    html += `<td style="border:1px solid #fff; text-align:center;">${formatVal(sum, col.format || 'number')}</td>`;
                }
            });
            if (showTotal) html += `<th style="border:1px solid #fff; text-align:center;">${formatVal(grandTotal, 'number')}</th>`;
            html += '</tr>';
        }
        html += '</tbody></table>';
        tableEl.innerHTML = html;
    }
</script>
