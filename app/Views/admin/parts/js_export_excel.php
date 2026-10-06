<!-- Progress Bar Export Excel -->
<div id="export-progress-container">
    <div class="export-header">
        <div class="export-title">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
            Proses Ekspor Ke Excel
        </div>
    </div>
    <div class="export-progress-bg">
        <div id="export-progress-fill" class="export-progress-fill"></div>
    </div>
    <div class="export-status">
        <span id="export-status-text">Menyiapkan...</span>
        <span id="export-percent-text">0%</span>
    </div>
</div>

<!-- Load Local Libraries for native .xlsx and OpenXML error suppression -->
<script src="<?= base_url('aset/js/exceljs.min.js') ?>"></script>
<script src="<?= base_url('aset/js/jszip.min.js') ?>"></script>
<script src="<?= base_url('aset/js/xlsx.full.min.js') ?>"></script>

<script>
    /**
     * Memastikan library ExcelJS, JSZip, atau SheetJS siap digunakan
     */
    async function ensureExcelLibraries() {
        const loadScript = (src) => new Promise((resolve) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = () => resolve(true);
            s.onerror = () => resolve(false);
            document.head.appendChild(s);
        });

        if (typeof ExcelJS === 'undefined') {
            await loadScript('<?= base_url('aset/js/exceljs.min.js') ?>');
        }
        if (typeof JSZip === 'undefined') {
            await loadScript('<?= base_url('aset/js/jszip.min.js') ?>');
        }

        if (typeof ExcelJS === 'undefined') {
            await loadScript('https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js');
        }
        if (typeof JSZip === 'undefined') {
            await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js');
        }

        if (typeof ExcelJS !== 'undefined') return 'exceljs';
        if (typeof XLSX !== 'undefined') return 'xlsx';

        return false;
    }

    /**
     * Bersihkan string nama file dari karakter ilegal Windows & newline
     */
    function sanitizeExcelFilename(name) {
        if (!name) return 'Export-Data-Tabel';
        let clean = name.replace(/[\r\n\t]+/g, ' ')
                        .replace(/[\\/:*?"<>|]/g, '-')
                        .replace(/\s+/g, ' ')
                        .trim();
        if (clean.length > 110) {
            clean = clean.substring(0, 110).trim();
        }
        return clean || 'Export-Data-Tabel';
    }

    /**
     * Parsing angka dengan pemisah ribuan titik/koma standar Indonesia
     * Menghasilkan nilai tipe Number murni agar tidak ada peringatan segitiga hijau di Excel
     */
    function parseNumericCell(rawText) {
        if (!rawText) return { isNumeric: false, value: '' };
        const clean = rawText.trim();
        
        // Dash / strip penanda data kosong atau tidak ada
        const cleanNoSpace = clean.replace(/\s+/g, '');
        if (clean === '-' || clean === '–' || clean === '—' || cleanNoSpace === '-' || cleanNoSpace === '–' || cleanNoSpace === '—') {
            return { isNumeric: false, isDash: true, value: '-' };
        }

        // Cek integer murni e.g. "25", "100"
        if (/^-?\d+$/.test(clean)) {
            const val = parseInt(clean, 10);
            return { isNumeric: !isNaN(val), value: val, format: '#,##0' };
        }

        // Cek format ribuan Indonesia e.g. "1.095" atau "49.060"
        if (/^-?\d{1,3}(\.\d{3})+$/.test(clean)) {
            const val = parseInt(clean.replace(/\./g, ''), 10);
            return { isNumeric: !isNaN(val), value: val, format: '#,##0' };
        }

        // Cek desimal Indonesia dengan koma e.g. "1.095,50" atau "85,4"
        if (/^-?(\d{1,3}(\.\d{3})*|\d+),\d+$/.test(clean)) {
            const val = parseFloat(clean.replace(/\./g, '').replace(',', '.'));
            return { isNumeric: !isNaN(val), value: val, format: '#,##0.00' };
        }

        // Cek floating point standar e.g. "33.01"
        if (/^-?\d+\.\d+$/.test(clean)) {
            const val = parseFloat(clean);
            return { isNumeric: !isNaN(val), value: val, format: '#,##0.00' };
        }

        return { isNumeric: false, value: clean };
    }

    /**
     * Hitung total kolom tabel dari thead
     */
    function computeTableTotalColumns(tableEl) {
        let maxCol = 1;
        const firstTr = tableEl.querySelector('thead tr, tr');
        if (firstTr) {
            let sum = 0;
            firstTr.querySelectorAll('th, td').forEach(c => {
                if (window.getComputedStyle(c).display !== 'none') {
                    sum += parseInt(c.getAttribute('colspan')) || 1;
                }
            });
            if (sum > maxCol) maxCol = sum;
        }
        const numTr = tableEl.querySelector('thead tr.num-row');
        if (numTr) {
            const count = numTr.querySelectorAll('th, td').length;
            if (count > maxCol) maxCol = count;
        }
        return Math.max(maxCol, 4);
    }

    /**
     * FUNGSI UTAMA EXPORT EXCEL DDA
     * Format bersih, rapi, tanpa background warna pada header, tanpa segitiga hijau,
     * garis total Jawa Tengah penuh, dan footer catatan/sumber yang terstruktur rapi.
     */
    async function exportTableToExcel(tableID, filename = '') {
        const container = document.getElementById('export-progress-container');
        const fill = document.getElementById('export-progress-fill');
        const statusText = document.getElementById('export-status-text');
        const percentText = document.getElementById('export-percent-text');

        if (container) {
            container.style.display = 'block';
            fill.style.width = '20%';
            percentText.innerText = '20%';
            statusText.innerText = 'Menyiapkan modul Excel...';
        }

        try {
            const libReady = await ensureExcelLibraries();
            if (!libReady) {
                throw new Error('Gagal memuat pustaka Excel (ExcelJS/XLSX).');
            }

            if (fill) {
                fill.style.width = '40%';
                percentText.innerText = '40%';
                statusText.innerText = 'Mengekstrak data tabel...';
            }

            // 1. Tentukan elemen tabel yang akan diekspor
            const targetEl = document.getElementById(tableID);
            if (!targetEl) {
                throw new Error('Tabel dengan ID "' + tableID + '" tidak ditemukan.');
            }

            let tableEntries = [];
            if (targetEl.tagName === 'TABLE') {
                const wrapper = targetEl.closest('.table-wrapper') || targetEl.parentElement;
                tableEntries.push({ table: targetEl, wrapper: wrapper });
            } else {
                const wrappers = Array.from(targetEl.querySelectorAll('.table-wrapper')).filter(w => {
                    return window.getComputedStyle(w).display !== 'none';
                });

                if (wrappers.length > 0) {
                    wrappers.forEach(w => {
                        const tbl = w.querySelector('table.main-table, table');
                        if (tbl && window.getComputedStyle(tbl).display !== 'none') {
                            tableEntries.push({ table: tbl, wrapper: w });
                        }
                    });
                } else {
                    const tables = Array.from(targetEl.querySelectorAll('table')).filter(t => {
                        return window.getComputedStyle(t).display !== 'none';
                    });
                    tables.forEach(t => tableEntries.push({ table: t, wrapper: t.parentElement }));
                }
            }

            if (tableEntries.length === 0) {
                throw new Error('Tidak ada data tabel yang dapat diekspor.');
            }

            // 2. Tentukan Judul Utama dan Nama File
            let resolvedTitle = (filename && filename !== 'portal-data-export') ? filename.trim() : '';
            if (!resolvedTitle) {
                const pageMainTitle = document.querySelector('.table-main-title')?.innerText ||
                                      document.querySelector('.title-id')?.innerText ||
                                      document.querySelector('.modal:not(.show) .modal-title')?.innerText;
                const pageTabelNum = document.querySelector('.tabel-number')?.innerText?.trim();
                
                if (pageMainTitle && !pageMainTitle.includes('KONFIGURASI')) {
                    if (pageTabelNum && !pageMainTitle.toLowerCase().startsWith('tabel')) {
                        resolvedTitle = 'Tabel ' + pageTabelNum + ' ' + pageMainTitle;
                    } else {
                        resolvedTitle = pageMainTitle;
                    }
                } else {
                    resolvedTitle = 'Data Portal Tabel';
                }
            }

            const safeFilename = sanitizeExcelFilename(resolvedTitle);

            if (fill) {
                fill.style.width = '65%';
                percentText.innerText = '65%';
                statusText.innerText = 'Menerapkan format tabel bersih...';
            }

            // 3. Bangun Workbook Excel Resmi Standar BPS
            if (typeof ExcelJS !== 'undefined') {
                const workbook = new ExcelJS.Workbook();
                workbook.creator = 'BPS Provinsi Jawa Tengah - DDA Online';
                workbook.lastModifiedBy = 'DDA Online System';
                workbook.created = new Date();
                workbook.modified = new Date();

                tableEntries.forEach((entry, entryIdx) => {
                    const tableEl = entry.table;
                    const wrapperEl = entry.wrapper;

                    // Ambil Metadata Spesifik Tabel
                    let itemTabelNum = wrapperEl?.querySelector('.tabel-number')?.innerText?.trim() || 
                                       document.querySelector('.tabel-number')?.innerText?.trim() || '';
                    let itemTitleId = wrapperEl?.querySelector('.table-main-title')?.innerText?.trim() || 
                                      document.querySelector('.table-main-title')?.innerText?.trim() || 
                                      resolvedTitle;
                    let itemTitleEn = wrapperEl?.querySelector('.table-sub-title')?.innerText?.trim() || 
                                      document.querySelector('.table-sub-title')?.innerText?.trim() || '';
                    let itemOpd = wrapperEl?.querySelector('.table-opd-badge span')?.innerText?.trim() || 
                                  document.querySelector('.table-opd-badge span')?.innerText?.trim() || '';

                    // Pisahkan nomor tabel jika masih menyatu di judul
                    if (!itemTabelNum) {
                        const mNum = itemTitleId.match(/^Tabel\s+([\d\.\w]+)\s+(.*)$/i);
                        if (mNum) {
                            itemTabelNum = mNum[1];
                            itemTitleId = mNum[2];
                        }
                    } else {
                        itemTitleId = itemTitleId.replace(new RegExp('^Tabel\\s+' + itemTabelNum.replace('.', '\\.') + '\\s*', 'i'), '').trim();
                    }

                    itemTitleId = itemTitleId.replace(/[\r\n\t]+/g, ' ').replace(/\s+/g, ' ').trim();
                    itemTitleEn = itemTitleEn.replace(/[\r\n\t]+/g, ' ').replace(/\s+/g, ' ').trim();

                    // Catatan & Sumber Footer
                    const footerEl = wrapperEl?.querySelector('div[style*="font-size: 11px"]') ||
                                     wrapperEl?.querySelector('.table-footer-notes') ||
                                     document.querySelector('.table-wrapper div[style*="font-size: 11px"]');
                    let footerLines = footerEl ? footerEl.innerText.split('\n').map(l => l.trim()).filter(Boolean) : [];

                    // Pastikan sumber instansi/OPD disertakan di bawah (footer)
                    const hasSumberInFooter = footerLines.some(l => l.toLowerCase().startsWith('sumber') || l.toLowerCase().startsWith('source'));
                    if (!hasSumberInFooter && itemOpd) {
                        footerLines.push('Sumber/Source: ' + itemOpd);
                    }

                    // Penamaan Sheet
                    let sheetName = 'Data Tabel';
                    if (tableEntries.length > 1) {
                        sheetName = ('Tabel ' + (itemTabelNum || (entryIdx + 1))).substring(0, 31).replace(/[\\/*?:[\]]/g, '');
                    }
                    const worksheet = workbook.addWorksheet(sheetName, {
                        views: [{ showGridLines: true }]
                    });

                    // Hitung total kolom tabel
                    const totalCols = computeTableTotalColumns(tableEl);

                    // ==========================================
                    // 1. JUDUL DOKUMEN YANG RAPI & MENARIK (Baris 1 - 2)
                    // ==========================================
                    // Judul Indonesia: digabung mulai dari Kolom A sampai Kolom Terakhir
                    const fullTitleId = itemTabelNum 
                        ? `Tabel ${itemTabelNum} ${itemTitleId}`
                        : itemTitleId;

                    const cellTitle1 = worksheet.getCell(1, 1);
                    cellTitle1.value = fullTitleId;
                    cellTitle1.font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FF111827' } };
                    cellTitle1.alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };
                    worksheet.mergeCells(1, 1, 1, totalCols);
                    worksheet.getRow(1).height = fullTitleId.length > 75 ? 34 : 24;

                    // Baris 2: Subjudul Bahasa Inggris (Italic)
                    let nextRow = 2;
                    if (itemTitleEn) {
                        const fullTitleEn = itemTabelNum 
                            ? `Table ${itemTabelNum} ${itemTitleEn}`
                            : itemTitleEn;

                        const cellTitle2 = worksheet.getCell(nextRow, 1);
                        cellTitle2.value = fullTitleEn;
                        cellTitle2.font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF4B5563' } };
                        cellTitle2.alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };
                        worksheet.mergeCells(nextRow, 1, nextRow, totalCols);
                        worksheet.getRow(nextRow).height = fullTitleEn.length > 75 ? 28 : 20;
                        nextRow++;
                    }

                    // Baris jeda kosong (Sumber di atas ditiadakan, cukup di footer bawah)
                    worksheet.getRow(nextRow).height = 10;
                    nextRow++;

                    const startTableHeadRow = nextRow;
                    let currentRow = startTableHeadRow;
                    let maxActualCol = totalCols;
                    const gridOccupied = {};

                    // ==========================================
                    // 2. PARSING STRUKTUR TABEL (thead, tbody)
                    // ==========================================
                    const rows = tableEl.querySelectorAll('tr');

                    rows.forEach(tr => {
                        if (window.getComputedStyle(tr).display === 'none') return;

                        const isThead = tr.closest('thead') !== null;
                        const isNumRow = tr.classList.contains('num-row') || 
                                         (tr.innerText && /^\s*\(\d+\)[\s\(\)\d]*$/.test(tr.innerText.trim()));
                        
                        let currentCol = 1;
                        const cells = tr.querySelectorAll('th, td');
                        if (cells.length === 0) return;

                        // Deteksi Baris Pembagi Kategori Wilayah (e.g. "Kabupaten / Regency")
                        const rawFirstCellText = cells[0].innerText.trim();
                        const isCategoryHeader = !isThead && (
                            cells.length === 1 || 
                            rawFirstCellText.startsWith('Kabupaten /') || 
                            rawFirstCellText.startsWith('Kota /') || 
                            rawFirstCellText === 'Kabupaten' || 
                            rawFirstCellText === 'Kota'
                        );

                        // Deteksi Baris Total / Jawa Tengah pada level baris (TR)
                        const trText = tr.innerText.toLowerCase();
                        const isTotalRow = !isThead && !isCategoryHeader && (
                            trText.includes('total') || 
                            trText.includes('jawa tengah') ||
                            tr.classList.contains('total-row') ||
                            tr.classList.contains('bg-total')
                        );

                        worksheet.getRow(currentRow).height = isCategoryHeader ? 22 : (isNumRow ? 18 : (isThead ? 26 : 20));

                        if (isCategoryHeader) {
                            // Tangani baris pembagi wilayah: bentangkan dari kolom 1 s.d. maxActualCol
                            const cellRef = worksheet.getCell(currentRow, 1);
                            cellRef.value = rawFirstCellText;
                            cellRef.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF111827' } };
                            cellRef.alignment = { horizontal: 'left', vertical: 'middle', indent: 1 };
                            
                            worksheet.mergeCells(currentRow, 1, currentRow, maxActualCol);
                            for (let c = 1; c <= maxActualCol; c++) {
                                gridOccupied[`${currentRow},${c}`] = true;
                                const mCell = worksheet.getCell(currentRow, c);
                                mCell.border = {
                                    top: { style: 'thin', color: { argb: 'FF94A3B8' } },
                                    bottom: { style: 'thin', color: { argb: 'FF94A3B8' } },
                                    left: { style: 'thin', color: { argb: 'FFD1D5DB' } },
                                    right: { style: 'thin', color: { argb: 'FFD1D5DB' } }
                                };
                            }
                            currentRow++;
                            return;
                        }

                        cells.forEach(cell => {
                            if (window.getComputedStyle(cell).display === 'none') return;

                            // Lewati sel yang sudah terisi oleh rowspan sebelumnya
                            while (gridOccupied[`${currentRow},${currentCol}`]) {
                                currentCol++;
                            }

                            let rowspan = parseInt(cell.getAttribute('rowspan')) || 1;
                            let colspan = parseInt(cell.getAttribute('colspan')) || 1;

                            // Tandai area sel yang terisi
                            for (let r = 0; r < rowspan; r++) {
                                for (let c = 0; c < colspan; c++) {
                                    gridOccupied[`${currentRow + r},${currentCol + c}`] = true;
                                }
                            }

                            const cellRef = worksheet.getCell(currentRow, currentCol);
                            const rawText = cell.innerText.replace(/[\r\n\t]+/g, '\n').trim();

                            if (isThead) {
                                // --- HEADER TABEL: TANPA BACKGROUND WARNA (NATIVE EXCEL) ---
                                // Konversi tahun jika berupa angka murni (misal 2025)
                                if (/^\d{4}$/.test(rawText)) {
                                    cellRef.value = parseInt(rawText, 10);
                                    cellRef.numFmt = '0';
                                } else {
                                    cellRef.value = rawText;
                                }

                                if (isNumRow) {
                                    // Baris nomor kolom e.g. (1), (2), (3)
                                    cellRef.font = {
                                        name: 'Arial',
                                        size: 8.5,
                                        bold: false,
                                        color: { argb: 'FF000000' }
                                    };
                                    cellRef.alignment = {
                                        horizontal: 'center',
                                        vertical: 'middle'
                                    };
                                    // Garis penutup bawah header tegas (medium line)
                                    cellRef.border = {
                                        top: { style: 'thin', color: { argb: 'FF000000' } },
                                        bottom: { style: 'medium', color: { argb: 'FF000000' } },
                                        left: { style: 'thin', color: { argb: 'FF000000' } },
                                        right: { style: 'thin', color: { argb: 'FF000000' } }
                                    };
                                } else {
                                    // Header Kolom (No, Kabupaten/Kota, Nama Variabel)
                                    cellRef.font = {
                                        name: 'Arial',
                                        size: 9.5,
                                        bold: true,
                                        color: { argb: 'FF000000' }
                                    };
                                    cellRef.alignment = {
                                        horizontal: 'center',
                                        vertical: 'middle',
                                        wrapText: true
                                    };
                                    // Garis atas tabel tegas (medium line BPS)
                                    const isTopRow = (currentRow === startTableHeadRow);
                                    cellRef.border = {
                                        top: { style: isTopRow ? 'medium' : 'thin', color: { argb: 'FF000000' } },
                                        bottom: { style: 'thin', color: { argb: 'FF000000' } },
                                        left: { style: 'thin', color: { argb: 'FF000000' } },
                                        right: { style: 'thin', color: { argb: 'FF000000' } }
                                    };
                                }

                            } else {
                                // --- BARIS DATA TABEL (TBODY): BERSIH & JELAS ---
                                const parsed = parseNumericCell(rawText);
                                cellRef.value = parsed.value;
                                if (parsed.format) {
                                    cellRef.numFmt = parsed.format;
                                }

                                const isDash = parsed.isDash || (rawText === '-' || rawText === '–' || rawText === '—');

                                if (isTotalRow) {
                                    // Baris Total / Jawa Tengah: Garis atas tegas & garis bawah ganda memanjang di seluruh kolom
                                    cellRef.font = {
                                        name: 'Arial',
                                        size: 9.5,
                                        bold: true,
                                        color: { argb: 'FF000000' }
                                    };
                                    cellRef.border = {
                                        top: { style: 'thin', color: { argb: 'FF000000' } },
                                        bottom: { style: 'double', color: { argb: 'FF000000' } },
                                        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
                                        right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
                                    };
                                } else {
                                    // Baris data biasa: Garis tipis rapi di seluruh sel
                                    cellRef.font = {
                                        name: 'Arial',
                                        size: 9,
                                        color: { argb: 'FF000000' }
                                    };
                                    cellRef.border = {
                                        top: { style: 'thin', color: { argb: 'FFD1D5DB' } },
                                        bottom: { style: 'thin', color: { argb: 'FFD1D5DB' } },
                                        left: { style: 'thin', color: { argb: 'FFD1D5DB' } },
                                        right: { style: 'thin', color: { argb: 'FFD1D5DB' } }
                                    };
                                }

                                // Perataan Teks (Alignment):
                                // Tanda - atau endash disamakan RATA KANAN sesuai permintaan user
                                if (parsed.isNumeric || isDash) {
                                    cellRef.alignment = { horizontal: 'right', vertical: 'middle' };
                                } else if (currentCol === 1 && (/^\d+\.?$/.test(rawText) || rawText === 'No.')) {
                                    // Kolom No.
                                    cellRef.alignment = { horizontal: 'center', vertical: 'middle' };
                                } else if (currentCol <= 3 && !parsed.isNumeric && !isDash) {
                                    // Kolom Nama Kabupaten / Kota
                                    cellRef.alignment = { horizontal: 'left', vertical: 'middle', indent: 1 };
                                } else {
                                    cellRef.alignment = { horizontal: 'center', vertical: 'middle' };
                                }
                            }

                            // Penggabungan Sel (Colspan & Rowspan)
                            if (rowspan > 1 || colspan > 1) {
                                const endRow = currentRow + rowspan - 1;
                                const endCol = currentCol + colspan - 1;
                                worksheet.mergeCells(currentRow, currentCol, endRow, endCol);

                                for (let r = 0; r < rowspan; r++) {
                                    for (let c = 0; c < colspan; c++) {
                                        const mCell = worksheet.getCell(currentRow + r, currentCol + c);
                                        mCell.border = cellRef.border;
                                    }
                                }
                            }

                            if (currentCol + colspan - 1 > maxActualCol) {
                                maxActualCol = currentCol + colspan - 1;
                            }

                            currentCol += colspan;
                        });

                        // Memastikan baris Total / Jawa Tengah memiliki border memanjang utuh di semua kolom (termasuk di atas angka)
                        if (isTotalRow) {
                            for (let c = 1; c <= maxActualCol; c++) {
                                const totalCell = worksheet.getCell(currentRow, c);
                                totalCell.border = {
                                    top: { style: 'thin', color: { argb: 'FF000000' } },
                                    bottom: { style: 'double', color: { argb: 'FF000000' } },
                                    left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
                                    right: { style: 'thin', color: { argb: 'FFCBD5E1' } }
                                };
                                if (totalCell.font) {
                                    totalCell.font.bold = true;
                                } else {
                                    totalCell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF000000' } };
                                }
                            }
                        }

                        currentRow++;
                    });

                    const endTableHeadRow = currentRow - 1;

                    // ==========================================
                    // 3. FOOTER: CATATAN & SUMBER (Di Bagian Bawah Saja)
                    // ==========================================
                    if (footerLines.length > 0) {
                        currentRow++; // Baris jeda kosong
                        worksheet.getRow(currentRow).height = 10;
                        currentRow++;

                        footerLines.forEach(line => {
                            let formattedLine = line.trim();
                            let richValue = null;

                            // Standarisasi teks catatan dan sumber agar rapi tanpa pemisah kolom
                            if (/^(catatan|note)/i.test(formattedLine)) {
                                const desc = formattedLine.replace(/^(Catatan\s*[\/:]\s*Note|Catatan|Note)\s*[:]\s*/i, '').trim();
                                richValue = {
                                    richText: [
                                        { text: 'Catatan / ', font: { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1F2937' } } },
                                        { text: 'Note', font: { name: 'Arial', size: 9, bold: true, italic: true, color: { argb: 'FF4B5563' } } },
                                        { text: ': ' + desc, font: { name: 'Arial', size: 9, italic: true, color: { argb: 'FF374151' } } }
                                    ]
                                };
                            } else if (/^(sumber|source)/i.test(formattedLine)) {
                                const desc = formattedLine.replace(/^(Sumber\s*[\/:]\s*Source|Sumber|Source)\s*[:]\s*/i, '').trim();
                                richValue = {
                                    richText: [
                                        { text: 'Sumber / ', font: { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1F2937' } } },
                                        { text: 'Source', font: { name: 'Arial', size: 9, bold: true, italic: true, color: { argb: 'FF4B5563' } } },
                                        { text: ': ' + desc, font: { name: 'Arial', size: 9, italic: true, color: { argb: 'FF374151' } } }
                                    ]
                                };
                            }

                            const cellFoot = worksheet.getCell(currentRow, 1);
                            if (richValue) {
                                cellFoot.value = richValue;
                            } else {
                                cellFoot.value = formattedLine;
                                cellFoot.font = { name: 'Arial', size: 9, italic: true, color: { argb: 'FF374151' } };
                            }
                            cellFoot.alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

                            if (maxActualCol >= 2) {
                                worksheet.mergeCells(currentRow, 1, currentRow, maxActualCol);
                            }
                            for (let c = 1; c <= maxActualCol; c++) {
                                worksheet.getCell(currentRow, c).border = {};
                            }
                            worksheet.getRow(currentRow).height = formattedLine.length > 80 ? 24 : 18;
                            currentRow++;
                        });
                    }

                    // ==========================================
                    // 4. ATUR LEBAR KOLOM SECARA PROPORSIONAL
                    // ==========================================
                    for (let c = 1; c <= maxActualCol; c++) {
                        let maxLen = 8;
                        worksheet.getColumn(c).eachCell({ includeEmpty: false }, (colCell, rowNum) => {
                            if (rowNum < startTableHeadRow || rowNum > endTableHeadRow) return;
                            const textVal = colCell.value ? String(colCell.value) : '';
                            textVal.split('\n').forEach(line => {
                                if (line.length > maxLen) maxLen = line.length;
                            });
                        });

                        let colWidth = Math.min(Math.max(maxLen + 3, 11), 45);
                        if (c === 1 && maxLen <= 5) {
                            colWidth = 7;
                        } else if (c === 2 || c === 3) {
                            colWidth = Math.max(colWidth, 26);
                        }
                        worksheet.getColumn(c).width = colWidth;
                    }
                });

                if (fill) {
                    fill.style.width = '85%';
                    percentText.innerText = '85%';
                    statusText.innerText = 'Menyempurnakan berkas Excel...';
                }

                // Tulis workbook ke buffer biner XLSX asli
                let buffer = await workbook.xlsx.writeBuffer();

                // HILANGKAN SEGITIGA HIJAU DI EXCEL SECARA TUNTAS DENGAN OPENXML IGNOREDERRORS
                if (typeof JSZip !== 'undefined') {
                    try {
                        const zip = await JSZip.loadAsync(buffer);
                        const wsFiles = [];
                        zip.forEach((relPath, entry) => {
                            if (relPath.startsWith('xl/worksheets/') && relPath.endsWith('.xml') && !entry.dir) {
                                wsFiles.push({ path: relPath, file: entry });
                            }
                        });

                        for (const item of wsFiles) {
                            let xmlStr = await item.file.async('string');
                            if (!xmlStr.includes('<ignoredErrors>')) {
                                const ignoredTag = '<ignoredErrors><ignoredError sqref="A1:ZZ2000" numberStoredAsText="1" twoDigitTextYear="1" evalError="1" formula="1" formulaRange="1" unlockedFormula="1" emptyCellReference="1" listDataValidation="1" calculatedColumn="1" /></ignoredErrors></worksheet>';
                                xmlStr = xmlStr.replace('</worksheet>', ignoredTag);
                                zip.file(item.path, xmlStr);
                            }
                        }

                        buffer = await zip.generateAsync({ type: 'arraybuffer' });
                        console.log('OpenXML ignoredErrors injected successfully into', wsFiles.length, 'worksheets');
                    } catch (e) {
                        console.warn('JSZip error patching ignoredErrors:', e);
                    }
                }

                if (fill) {
                    fill.style.width = '95%';
                    percentText.innerText = '95%';
                    statusText.innerText = 'Mengunduh file .xlsx...';
                }

                const blob = new Blob([buffer], { 
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = safeFilename + '.xlsx';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

            } else if (typeof XLSX !== 'undefined') {
                const targetTable = tableEntries[0].table;
                const wb = XLSX.utils.table_to_book(targetTable, { sheet: "Data", raw: false });
                XLSX.writeFile(wb, safeFilename + '.xlsx');
            }

            if (fill) {
                fill.style.width = '100%';
                percentText.innerText = '100%';
                statusText.innerText = 'Export Berhasil!';
            }

            setTimeout(() => {
                if (container) container.style.display = 'none';
            }, 2500);

        } catch (error) {
            console.error('Export Excel Error:', error);
            if (statusText) statusText.innerText = 'Gagal: ' + error.message;
            if (fill) {
                fill.style.width = '100%';
                fill.style.background = '#e74c3c';
            }
            alert('Gagal mengekspor ke Excel: ' + error.message);
            setTimeout(() => {
                if (container) {
                    container.style.display = 'none';
                    if (fill) fill.style.background = '';
                }
            }, 3500);
        }
    }
</script>
