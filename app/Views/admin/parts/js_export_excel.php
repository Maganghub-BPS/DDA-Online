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

<script>
    /**
     * FUNGSI EXPORT EXCEL MODAL DDA
     * Hanya export apa yang sedang nampak di layar
     */
    function exportTableToExcel(tableID, filename = '') {
        const container = document.getElementById('export-progress-container');
        const fill = document.getElementById('export-progress-fill');
        const statusText = document.getElementById('export-status-text');
        const percentText = document.getElementById('export-percent-text');

        container.style.display = 'block';
        fill.style.width = '30%';
        statusText.innerText = 'Mengekstrak data tabel...';
        
        setTimeout(() => {
            const originalContainer = document.getElementById(tableID);
            const clone = originalContainer.cloneNode(true);
            
            // --- 1. FILTER HANYA YANG TAMPIL ---
            clone.querySelectorAll('.table-wrapper').forEach(w => {
                const originalWrappers = document.querySelectorAll('.table-wrapper');
                const allClonedWrappers = Array.from(clone.querySelectorAll('.table-wrapper'));
                const idx = allClonedWrappers.indexOf(w);
                
                if (originalWrappers[idx] && window.getComputedStyle(originalWrappers[idx]).display === 'none') {
                    w.remove();
                }
            });

            // --- 2. AMBIL JUDUL UTAMA ---
            const mainTitle = document.querySelector('h5.fw-bold')?.innerText || "Export Portal Data";
            const subTitle = document.querySelector('p.text-muted')?.innerText || "";

            // --- 3. BERSIHKAN ATRIBUT CSS ---
            clone.querySelectorAll('table').forEach(tbl => {
                tbl.setAttribute('border', '1');
                tbl.style.borderCollapse = 'collapse';
                tbl.style.width = '100%';
            });

            const htmlContent = clone.innerHTML;
            const excelFile = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                    <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
                    <x:Name>Portal Data</x:Name>
                    <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                    </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
                    <style>
                        .excel-title { font-size: 16pt; font-weight: bold; text-align: left; }
                        .excel-subtitle { font-size: 10pt; font-style: italic; color: #555; text-align: left; }
                        thead, .bg-orange { background-color: #FF6D1F !important; color: #ffffff !important; }
                        th, td { border: 0.5pt solid #000000; padding: 4px; vertical-align: middle; }
                        th { font-weight: bold; text-align: center; }
                    </style>
                </head>
                <body>
                    <table><tr><td colspan="10" class="excel-title">${mainTitle}</td></tr>
                    <tr><td colspan="10" class="excel-subtitle">${subTitle}</td></tr>
                    <tr><td colspan="10"></td></tr></table>
                    ${htmlContent}
                </body>
                </html>`;

            const blob = new Blob([excelFile], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            const safeFilename = (mainTitle.substring(0, 50).replace(/[/\\?%*:|"<>]/g, '-')) || 'export-portal';
            
            a.href = url;
            a.download = safeFilename + '.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            fill.style.width = '100%';
            percentText.innerText = '100%';
            statusText.innerText = 'Export Berhasil!';
            
            setTimeout(() => {
                container.style.display = 'none';
            }, 2500);
        }, 600);
    }
</script>
