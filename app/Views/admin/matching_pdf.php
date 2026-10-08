<?php
/**
 * =============================================================================
 * VIEW: DATA VERIFIER AI - MODUL MATCHING & AUDIT PDF DDA
 * =============================================================================
 * Antarmuka pengguna untuk memverifikasi dokumen PDF Publikasi Daerah Dalam Angka
 * (BPS) melawan sumber data primer (Google Spreadsheet / API Satu Data Jawa Tengah).
 * 
 * Komponen & Fitur Antarmuka:
 * 1. Panel Upload & Master PDF Status:
 * 2. Background Batch Verification Worker:
 * 3. Filter Bar & Quick Filters:
 * 4. Modal Detail Komparasi (Head-to-Head):
 * =============================================================================
 */
?>
<style>
    .sync-card { border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); }
    .btn-sync-action { border-radius: 8px; font-weight: 700; padding: 10px 12px; width: 100%; transition: all 0.2s ease; font-size: 0.9rem; }
    .btn-sync-action:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    
    .result-section { display: none; }
    .list-group-item { border-left: 4px solid transparent; }
    .list-group-item.matched { border-left-color: #198754; }
    .list-group-item.missing { border-left-color: #fd7e14; }
    .list-group-item.unreg { border-left-color: #dc3545; }

    .diff-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #dee2e6;
    }
    .diff-title { font-size: 0.85rem; font-weight: bold; color: #6c757d; text-transform: uppercase; margin-bottom: 10px; }
    .diff-val { font-size: 1.1rem; font-weight: bold; padding: 5px 10px; border-radius: 6px; display: inline-block; }
    .val-pdf { background: #ffe6e6; color: #dc3545; border: 1px dashed #dc3545; }
    .val-src { background: #e6f8f0; color: #198754; border: 1px dashed #198754; }
    
    .section-title { font-size: 0.75rem; font-weight: 800; color: #adb5bd; text-transform: uppercase; letter-spacing: 0.1em; display: block; margin-bottom: 12px; }
    
    #listMatched li.matched.d-filtered-out {
        display: none !important;
    }
</style>

<div class="row mb-3 mt-2">
    <div class="col-lg-12">
        <div class="d-flex align-items-center mb-2">
            <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-file-earmark-pdf-fill" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h4 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">Data Verifier AI</h4>
                <p class="text-muted" style="font-size: 0.85rem; margin-top: 0; margin-bottom: 0;">Sistem Deteksi Anomali & Sinkronisasi PDF vs Sumber Data (Spreadsheet/API)</p>
            </div>
        </div>
        <hr class="opacity-10 my-3">
    </div>
</div>

<div class="row g-4">
    <!-- PANEL UPLOAD -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sync-card bg-white h-100">
            <div class="card-body p-4">
                <?php if (!empty($has_master)): ?>
                <!-- Master PDF Active Card -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="section-title mb-0">Publikasi Master Aktif</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold" style="font-size:0.75rem;">
                        <i class="bi bi-check-circle-fill me-1"></i> Standby
                    </span>
                </div>
                
                <div class="p-3 rounded-3 mb-3" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0;">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-success text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-book-half fs-5"></i>
                        </div>
                        <div>
                            <h6 id="masterTitle" class="fw-bold text-success-emphasis mb-0" style="font-size: 0.95rem;">DDA Jawa Tengah <?= esc($ta ?? '2026') ?></h6>
                            <span id="masterFileSize" class="text-secondary small" style="font-size: 0.78rem;">Ukuran: <?= esc($master_data['file_size_mb'] ?? '12.4') ?> MB</span>
                        </div>
                    </div>
                    <div class="text-muted small pt-2 border-top border-success-subtle" style="font-size: 0.75rem;">
                        <i class="bi bi-clock-history me-1"></i> Diunggah: <strong id="masterUploadTime"><?= esc($master_data['upload_time'] ?? '-') ?></strong>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sync-action mb-2" onclick="$('#formUploadContainer').slideToggle(200);">
                    <i class="bi bi-arrow-repeat me-1"></i> Unggah Revisi PDF
                </button>

                <div id="formUploadContainer" style="display: none;" class="mt-3 pt-3 border-top">
                    <form id="formUploadPDF" onsubmit="return startMatching(event)">
                        <div class="mb-3 text-center p-3 rounded" style="border: 2px dashed #dee2e6; background: #f8f9fa;">
                            <i class="bi bi-cloud-arrow-up text-secondary mb-1" style="font-size: 2rem; display: block;"></i>
                            <label for="pdf_file" class="form-label fw-bold mb-1 small">Pilih File PDF Revisi</label>
                            <input class="form-control form-control-sm mt-1" type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                        </div>
                        <button type="submit" id="btnScan" class="btn btn-primary btn-sync-action text-white" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); border: none;">
                            <i class="bi bi-cpu-fill me-2"></i> PERBARUI MASTER PDF
                        </button>
                    </form>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <span class="section-title mb-2">Informasi Siklus</span>
                    <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                        File master tersimpan permanen di server untuk Tahun Publikasi <strong><?= esc($ta ?? '2026') ?></strong>. Hasil pengecekan tersimpan abadi dan tidak akan hilang saat browser di-refresh.
                    </p>
                </div>

                <?php else: ?>
                <!-- Fresh Upload Form (Belum ada master) -->
                <span class="section-title mb-3">Pindai Publikasi PDF</span>
                
                <form id="formUploadPDF" onsubmit="return startMatching(event)">
                    <div class="mb-4 text-center p-4 rounded" style="border: 2px dashed #dee2e6; background: #f8f9fa;">
                        <i class="bi bi-cloud-arrow-up-fill text-secondary mb-2" style="font-size: 2.5rem; display: block;"></i>
                        <label for="pdf_file" class="form-label fw-bold mb-1">Pilih File PDF</label>
                        <input class="form-control form-control-sm mt-2" type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                    </div>
                    
                    <button type="submit" id="btnScan" class="btn btn-primary btn-sync-action text-white" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); border: none;">
                        <i class="bi bi-cpu-fill me-2"></i> JALANKAN ANALISA AI
                    </button>
                    <p class="text-muted mt-3" style="font-size: 0.75rem; line-height: 1.4;">
                        Sistem akan meng-ekstrak teks dari PDF, mencocokkan struktur tabel dengan database, lalu memverifikasi data numerik secara mendalam.
                    </p>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PANEL HASIL -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm sync-card p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="section-title mb-0"><i class="bi bi-clipboard2-data-fill me-2 text-dark"></i> Hasil Pemindaian Tabel</span>
                <span id="badgeStatus" class="badge bg-secondary text-white shadow-none px-3 py-2 rounded-3" style="font-size: 10px; font-weight: 800; letter-spacing: 1px;">MENUNGGU FILE</span>
            </div>

            <!-- Loader / Empty State -->
            <div id="loaderArea" class="text-center py-5">
                <div id="idleState" class="opacity-50 text-muted">
                    <i class="bi bi-inbox d-block mb-3" style="font-size: 3rem;"></i>
                    <span class="small fw-bold">Belum ada file yang dianalisa.<br>Upload PDF di samping untuk memulai.</span>
                </div>
                <div id="loadingState" class="d-none">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">AI sedang bekerja...</h6>
                    <p class="text-muted small">Membaca PDF dan mencocokkan dengan Database.</p>
                </div>
            </div>

            <!-- Dashboard Hasil -->
            <div id="resultArea" class="result-section">
                <!-- Accordion untuk merapikan hasil -->
                <div class="accordion" id="accordionResult">
                    
                    <!-- MATCHED -->
                    <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                        <h2 class="accordion-header" id="headingMatched">
                        <button class="accordion-button bg-success-subtle text-success fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMatched" aria-expanded="true" aria-controls="collapseMatched">
                            <i class="bi bi-check-circle-fill me-2"></i> Tabel Ditemukan (<span id="countMatched">0</span>)
                        </button>
                        </h2>
                        <div id="collapseMatched" class="accordion-collapse collapse show" aria-labelledby="headingMatched" data-bs-parent="#accordionResult">
                        <div class="accordion-body p-0">
                            <!-- BATCH ACTION & QUICK FILTER TOOLBAR -->
                            <div class="p-3 bg-light border-bottom">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <button id="btnBatchVerify" onclick="startBatchValidation()" class="btn btn-sm btn-success fw-bold px-3 py-1-5 rounded-pill shadow-xs" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                                            <i class="bi bi-lightning-charge-fill me-1"></i> Validasi Semua Tabel Sekaligus
                                        </button>
                                        <button id="btnStopBatch" onclick="stopBatchValidation()" class="btn btn-sm btn-outline-danger fw-bold px-3 py-1-5 rounded-pill d-none">
                                            <i class="bi bi-stop-circle me-1"></i> Hentikan
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="input-group input-group-sm" style="max-width: 220px;">
                                            <span class="input-group-text bg-white py-0 px-2 border-end-0"><i class="bi bi-search text-muted" style="font-size:0.75rem;"></i></span>
                                            <input type="text" id="searchMatchedInput" class="form-control form-control-sm py-1 px-2 border-start-0" placeholder="Cari tabel..." style="font-size: 0.75rem;" onkeyup="applyMatchedFilters()">
                                        </div>
                                    </div>
                                </div>
                                <!-- Filter Pills -->
                                <div class="d-flex align-items-center gap-1 flex-wrap" id="batchFilters">
                                    <button type="button" id="btnFilterAll" class="btn btn-xs btn-outline-secondary active fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('all', this)">
                                        Semua (<span id="cntAll">0</span>)
                                    </button>
                                    <button type="button" id="btnFilterDiff" class="btn btn-xs btn-outline-danger fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('diff', this)">
                                        <i class="bi bi-exclamation-octagon-fill me-1"></i>Ada Beda (<span id="cntDiff">0</span>)
                                    </button>
                                    <button type="button" id="btnFilterMatch" class="btn btn-xs btn-outline-success fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('match', this)">
                                        <i class="bi bi-check-circle-fill me-1"></i>Cocok (<span id="cntMatch">0</span>)
                                    </button>
                                    <button type="button" id="btnFilterEmpty" class="btn btn-xs btn-outline-warning fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('api_empty', this)">
                                        <i class="bi bi-database-slash me-1"></i>API Kosong (<span id="cntEmpty">0</span>)
                                    </button>
                                    <button type="button" id="btnFilterUnverified" class="btn btn-xs btn-outline-secondary fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('unverified', this)">
                                        <i class="bi bi-clock me-1"></i>Belum Cek (<span id="cntUnverified">0</span>)
                                    </button>
                                </div>
                            </div>
                            
                            <!-- BATCH PROGRESS BAR -->
                            <div id="batchProgressBox" class="p-3 bg-white border-bottom d-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark" id="batchProgressLabel"><i class="bi bi-cpu-fill text-primary me-1"></i> Memvalidasi data tabel...</span>
                                    <span class="badge bg-primary rounded-pill fw-bold" id="batchProgressPercent">0%</span>
                                </div>
                                <div class="progress" style="height: 8px; border-radius: 4px;">
                                    <div id="batchProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <ul class="list-group list-group-flush" id="listMatched"></ul>
                        </div>
                        </div>
                    </div>

                    <!-- MISSING -->
                    <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                        <h2 class="accordion-header" id="headingMissing">
                        <button class="accordion-button collapsed bg-warning-subtle text-warning-emphasis fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMissing" aria-expanded="false" aria-controls="collapseMissing">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Tabel Hilang di PDF (<span id="countMissing">0</span>)
                        </button>
                        </h2>
                        <div id="collapseMissing" class="accordion-collapse collapse" aria-labelledby="headingMissing" data-bs-parent="#accordionResult">
                        <div class="accordion-body p-0">
                            <ul class="list-group list-group-flush" id="listMissing"></ul>
                        </div>
                        </div>
                    </div>

                    <!-- UNREGISTERED -->
                    <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                        <h2 class="accordion-header" id="headingUnreg">
                        <button class="accordion-button collapsed bg-danger-subtle text-danger fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUnreg" aria-expanded="false" aria-controls="collapseUnreg">
                            <i class="bi bi-x-circle-fill me-2"></i> Tabel Asing/Tidak Terdaftar (<span id="countUnreg">0</span>)
                        </button>
                        </h2>
                        <div id="collapseUnreg" class="accordion-collapse collapse" aria-labelledby="headingUnreg" data-bs-parent="#accordionResult">
                        <div class="accordion-body p-0">
                            <ul class="list-group list-group-flush" id="listUnreg"></ul>
                        </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Visual Diff / Verifikasi Angka -->
<div class="modal fade" id="modalDiff" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 94%;">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light pb-3 border-bottom">
        <div>
            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-diff-fill text-primary me-2"></i> Verifikasi Akurasi Angka</h5>
            <p class="text-muted small mb-0 mt-1">Kami membandingkan isi angka secara mendetail dari kiri-kanan, atas-bawah.</p>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-white">
        <div id="diffContent">
            <!-- Content will be injected here -->
        </div>
      </div>
      <div class="modal-footer border-top bg-light">
        <button type="button" class="btn btn-secondary btn-sm rounded-3 fw-bold px-4 py-2" data-bs-dismiss="modal">Tutup Laporan</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentPdfPath = <?= json_encode($master_data['pdf_path'] ?? '') ?>;
    let currentDbJsonPath = <?= json_encode($master_data['db_json_path'] ?? '') ?>;
    let activeYear = '<?= esc($ta ?? '2026') ?>';
    let hasMasterInitial = <?= !empty($has_master) ? 'true' : 'false' ?>;
    let isBatchRunningInitial = <?= !empty($is_batch_running) ? 'true' : 'false' ?>;
    window.currentMatchedTables = <?= json_encode($master_data['matched_in_pdf'] ?? $master_data['matched_tables'] ?? []) ?>;
    window.batchStatusMap = <?= json_encode($batch_status ?? []) ?>;

    function startMatching(e) {
        e.preventDefault();
        
        let fileInput = document.getElementById('pdf_file');
        if (fileInput.files.length === 0) {
            alert('Pilih file PDF terlebih dahulu!');
            return false;
        }

        let formData = new FormData();
        formData.append('pdf_file', fileInput.files[0]);

        // UI Loading State
        $('#btnScan').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> MEMPROSES...');
        $('#badgeStatus').removeClass('bg-secondary bg-success bg-danger').addClass('bg-primary text-white').text('MEMINDAI...');
        
        $('#loaderArea').show();
        $('#idleState').addClass('d-none');
        $('#loadingState').removeClass('d-none');
        $('#resultArea').hide();
        
        // Reset lists
        $('#listMatched, #listMissing, #listUnreg').empty();

        $.ajax({
            url: "<?= site_url('admin/process_matching_pdf') ?>",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#btnScan').prop('disabled', false).html('<i class="bi bi-cpu-fill me-2"></i> JALANKAN ANALISA AI');
                $('#loadingState').addClass('d-none');
                
                if(res.status === 'error') {
                    $('#badgeStatus').removeClass('bg-primary').addClass('bg-danger text-white').text('GAGAL');
                    $('#loaderArea').show();
                    $('#idleState').removeClass('d-none').html(`<div class="text-danger"><i class="bi bi-x-octagon-fill d-block mb-2" style="font-size: 2.5rem;"></i><span class="fw-bold">Error: ${res.message}</span></div>`);
                    return;
                }

                $('#badgeStatus').removeClass('bg-primary').addClass('bg-success text-white').text('SELESAI');
                $('#loaderArea').hide();
                $('#resultArea').fadeIn();

                if (res.pdf_path && res.db_json_path) {
                    currentPdfPath = res.pdf_path;
                    currentDbJsonPath = res.db_json_path;
                }

                // Update master card info
                $('#masterTitle').text('DDA Jawa Tengah ' + activeYear);
                $('#masterUploadTime').text(res.upload_time || 'Baru Saja');
                $('#masterFileSize').text((res.file_size_mb || '12.4') + ' MB');
                $('#formUploadContainer').slideUp(200);

                // Populate Matched
                let matchedList = res.matched_in_pdf || res.matched_tables || [];
                $('#countMatched').text(matchedList.length);
                window.currentMatchedTables = matchedList;
                window.batchStatusMap = {};

                // Cek apakah sudah ada cache hasil validasi batch sebelumnya
                $.getJSON("<?= base_url('admin/get_batch_status') ?>", { ta: activeYear }, function(cacheRes) {
                    if (cacheRes && cacheRes.status === 'success' && cacheRes.data) {
                        window.batchStatusMap = cacheRes.data;
                    }
                    renderAllMatchedRows();
                    updateBatchCounters();
                }).fail(function() {
                    renderAllMatchedRows();
                    updateBatchCounters();
                });

                // Populate Missing
                $('#countMissing').text(res.missing_in_pdf.length);
                if(res.missing_in_pdf.length === 0) {
                    $('#listMissing').append(`<li class="list-group-item text-muted text-center py-4">Semua tabel dari database ditemukan di PDF!</li>`);
                } else {
                    res.missing_in_pdf.forEach(item => {
                        let html = `
                            <li class="list-group-item missing py-3">
                                <span class="badge bg-warning text-dark mb-1">Tabel ${item.nomor_tabel}</span>
                                <h6 class="mb-1 fw-bold text-dark" style="font-size: 0.9rem;">${item.judul_db}</h6>
                                <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i> ${item.reason}</p>
                            </li>`;
                        $('#listMissing').append(html);
                    });
                }

                // Populate Unregistered
                $('#countUnreg').text(res.unregistered_in_db.length);
                if(res.unregistered_in_db.length === 0) {
                    $('#listUnreg').append(`<li class="list-group-item text-muted text-center py-4">Tidak ada tabel asing. Bersih!</li>`);
                } else {
                    res.unregistered_in_db.forEach(item => {
                        let html = `
                            <li class="list-group-item unreg py-3">
                                <span class="badge bg-danger mb-1">Tabel ${item.nomor_tabel}</span>
                                <h6 class="mb-1 fw-bold text-dark" style="font-size: 0.9rem;">${item.judul_pdf}</h6>
                                <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i> ${item.reason}</p>
                            </li>`;
                        $('#listUnreg').append(html);
                    });
                }

            },
            error: function(xhr, status, error) {
                $('#btnScan').prop('disabled', false).html('<i class="bi bi-cpu-fill me-2"></i> JALANKAN ANALISA AI');
                $('#badgeStatus').removeClass('bg-primary').addClass('bg-danger text-white').text('GAGAL');
                $('#loadingState').addClass('d-none');
                let errText = 'Koneksi Terputus / Server Error';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errText = xhr.responseJSON.message;
                }
                $('#idleState').removeClass('d-none').html(`<div class="text-danger"><i class="bi bi-wifi-off d-block mb-2" style="font-size: 2.5rem;"></i><span class="fw-bold">${errText}</span></div>`);
            }
        });

        return false;
    }

    function getSafeId(t_num) {
        return 't_' + String(t_num).replace(/[^a-zA-Z0-9]/g, '_');
    }

    function renderAllMatchedRows() {
        $('#listMatched').empty();
        if (!window.currentMatchedTables || window.currentMatchedTables.length === 0) {
            $('#listMatched').append(`<li class="list-group-item text-muted text-center py-4">Tidak ada tabel yang cocok.</li>`);
            return;
        }

        window.currentMatchedTables.forEach(item => {
            let cleanId = getSafeId(item.nomor_tabel);
            let info = window.batchStatusMap[item.nomor_tabel] || { status: 'unverified' };

            let html = `
                <li class="list-group-item matched py-3 d-flex justify-content-between align-items-center" data-status="${info.status}" data-tnum="${item.nomor_tabel}" id="matched_row_${cleanId}">
                    <div class="pe-3">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-secondary mb-0 fw-bold">Tabel ${item.nomor_tabel}</span>
                            <div id="badge_${cleanId}" class="d-inline-block badge-container"></div>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;">${item.judul_db}</h6>
                    </div>
                    <div id="action_${cleanId}" class="d-flex align-items-center gap-2 flex-shrink-0 action-container"></div>
                </li>`;
            $('#listMatched').append(html);
            renderRowBadge(item, info);
        });
    }

    function renderRowBadge(item, info) {
        let cleanId = getSafeId(item.nomor_tabel);
        let $rows = $(`li.matched[data-tnum="${item.nomor_tabel}"]`);
        if ($rows.length === 0) return;

        let status = info ? info.status : 'unverified';
        $rows.attr('data-status', status);

        // Apply current filter state if filtering is active
        if (window.currentBatchFilter && window.currentBatchFilter !== 'all') {
            let isMatchFilter = (window.currentBatchFilter === 'unverified')
                ? (status !== 'match' && status !== 'diff' && status !== 'api_empty')
                : (status === window.currentBatchFilter);
            if (isMatchFilter) {
                $rows.removeClass('d-filtered-out').addClass('d-flex');
            } else {
                $rows.addClass('d-filtered-out').removeClass('d-flex');
            }
        }

        let badgeHtml = '';
        let btnHtml = '';

        if (status === 'match') {
            badgeHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill me-1"></i>Cocok 100% (${info.match_count ? info.match_count + ' Data' : 'OK'})</span>`;
            btnHtml = `
                <a href="<?= base_url('admin/export_tabel_excel') ?>?nomor_tabel=${encodeURIComponent(item.nomor_tabel)}&pdf_path=${encodeURIComponent(currentPdfPath)}&db_json_path=${encodeURIComponent(currentDbJsonPath)}&tolerance=0.0" target="_blank" class="btn btn-xs btn-outline-success rounded-pill px-2 py-1 shadow-xs" title="Unduh Berita Acara Excel (.xlsx)" style="font-size:0.75rem;">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-outline-success fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <i class="bi bi-search me-1"></i> Detail
                </button>`;
        } else if (status === 'diff') {
            let diffNum = (typeof info.diff_count === 'number') ? info.diff_count : (parseInt(info.diff_count) || null);
            let diffLabel = (diffNum !== null) ? `${diffNum} Selisih` : 'Ada Beda';
            let matchText = (info.match_count && info.match_count > 0) ? ` <span class="opacity-75 font-monospace" style="font-size:0.7rem;">(${info.match_count} Cocok)</span>` : '';
            badgeHtml = `<span class="badge bg-danger text-white px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.75rem;"><i class="bi bi-exclamation-octagon-fill me-1"></i>${diffLabel}${matchText}</span>`;
            btnHtml = `
                <a href="<?= base_url('admin/export_tabel_excel') ?>?nomor_tabel=${encodeURIComponent(item.nomor_tabel)}&pdf_path=${encodeURIComponent(currentPdfPath)}&db_json_path=${encodeURIComponent(currentDbJsonPath)}&tolerance=0.0" target="_blank" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1 shadow-xs" title="Unduh Berita Acara Excel (.xlsx)" style="font-size:0.75rem;">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-danger fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <i class="bi bi-search me-1"></i> Cek Beda
                </button>`;
        } else if (status === 'api_empty') {
            badgeHtml = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.75rem;"><i class="bi bi-database-slash me-1"></i>API Kosong</span>`;
            btnHtml = `
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-outline-secondary fw-semibold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <i class="bi bi-search me-1"></i> Detail
                </button>`;
        } else if (status === 'checking') {
            badgeHtml = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fw-semibold shadow-xs" style="font-size:0.75rem;"><span class="spinner-border spinner-border-sm me-1" style="width:0.65rem; height:0.65rem;"></span> Memeriksa...</span>`;
            btnHtml = `
                <button disabled class="btn btn-sm btn-light text-muted fw-semibold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <span class="spinner-border spinner-border-sm" style="width:0.75rem; height:0.75rem;"></span>
                </button>`;
        } else if (status === 'error') {
            badgeHtml = `<span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.75rem;"><i class="bi bi-exclamation-circle me-1"></i>Belum Cek (Link/Format)</span>`;
            btnHtml = `
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-outline-warning text-dark fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <i class="bi bi-search me-1"></i> Cek Angka
                </button>`;
        } else {
            badgeHtml = `<span class="badge bg-light text-muted border px-2 py-1 rounded-pill fw-normal" style="font-size:0.75rem;"><i class="bi bi-clock me-1"></i>Belum Dicek</span>`;
            btnHtml = `
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-primary fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <i class="bi bi-search me-1"></i> Cek Angka
                </button>`;
        }

        $rows.find('.badge-container').html(badgeHtml);
        $rows.find('.action-container').html(btnHtml);
        $(`#badge_${cleanId}`).html(badgeHtml);
        $(`#action_${cleanId}`).html(btnHtml);
    }

    function updateBatchCounters() {
        let all = window.currentMatchedTables ? window.currentMatchedTables.length : 0;
        let match = 0, diff = 0, empty = 0, unverified = 0;

        if (window.currentMatchedTables) {
            window.currentMatchedTables.forEach(item => {
                let info = window.batchStatusMap[item.nomor_tabel];
                let status = info ? info.status : 'unverified';
                if (status === 'match') match++;
                else if (status === 'diff') diff++;
                else if (status === 'api_empty') empty++;
                else unverified++;
            });
        }

        $('#cntAll').text(all);
        $('#cntMatch').text(match);
        $('#cntDiff').text(diff);
        $('#cntEmpty').text(empty);
        $('#cntUnverified').text(unverified);
    }

    window.currentBatchFilter = 'all';

    function filterMatchedStatus(type, btn) {
        window.currentBatchFilter = type;
        $('#batchFilters button').removeClass('active btn-secondary btn-danger btn-success btn-warning text-white text-dark').addClass('btn-outline-secondary');
        if (type === 'all') $(btn).addClass('active btn-secondary text-white');
        else if (type === 'diff') $(btn).addClass('active btn-danger text-white');
        else if (type === 'match') $(btn).addClass('active btn-success text-white');
        else if (type === 'api_empty') $(btn).addClass('active btn-warning text-dark');
        else if (type === 'unverified') $(btn).addClass('active btn-secondary text-white');

        applyMatchedFilters();
    }

    function applyMatchedFilters() {
        let type = window.currentBatchFilter || 'all';
        let searchQuery = ($('#searchMatchedInput').val() || '').toLowerCase().trim();

        $('#listMatched li.matched').each(function() {
            let rowStatus = $(this).attr('data-status') || 'unverified';
            let tnum = ($(this).attr('data-tnum') || '').toLowerCase();
            let title = $(this).find('h6').text().toLowerCase();

            let matchesStatus = (type === 'all');
            if (!matchesStatus) {
                if (type === 'unverified') {
                    matchesStatus = (rowStatus !== 'match' && rowStatus !== 'diff' && rowStatus !== 'api_empty');
                } else {
                    matchesStatus = (rowStatus === type);
                }
            }
            let matchesSearch = (!searchQuery || tnum.includes(searchQuery) || title.includes(searchQuery));

            if (matchesStatus && matchesSearch) {
                $(this).removeClass('d-filtered-out').addClass('d-flex');
            } else {
                $(this).addClass('d-filtered-out').removeClass('d-flex');
            }
        });
    }

    let batchPollTimer = null;

    function startBatchValidation() {
        if (window.isBatchRunning) return;
        if (!window.currentMatchedTables || window.currentMatchedTables.length === 0) {
            alert("Tidak ada tabel yang siap divalidasi.");
            return;
        }

        window.isBatchRunning = true;
        consecutiveStopCount = 0;
        $('#btnBatchVerify').prop('disabled', true).addClass('opacity-50');
        $('#btnStopBatch').removeClass('d-none');
        $('#batchProgressBox').removeClass('d-none');
        $('#batchProgressPercent').text('0%');
        $('#batchProgressBar').css('width', '0%');
        $('#batchProgressLabel').html('<i class="bi bi-cpu-fill text-primary me-1"></i> Memulai Background Worker AI...');

        $.ajax({
            url: "<?= base_url('admin/start_batch_worker') ?>",
            type: "POST",
            data: {
                ta: activeYear,
                tolerance: "0.0"
            },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    pollBatchProgress();
                } else {
                    alert("Gagal memulai proses batch: " + res.message);
                    finishBatch();
                }
            },
            error: function() {
                alert("Gagal terhubung ke server.");
                finishBatch();
            }
        });
    }

    let consecutiveStopCount = 0;

    function pollBatchProgress() {
        if (batchPollTimer) clearTimeout(batchPollTimer);

        $.ajax({
            url: "<?= base_url('admin/get_batch_progress') ?>",
            type: "GET",
            data: { ta: activeYear },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    let prog = res.progress || {};
                    let completed = prog.completed || 0;
                    let total = prog.total || (window.currentMatchedTables ? window.currentMatchedTables.length : 0);
                    let pct = prog.percent !== undefined ? prog.percent : (total > 0 ? Math.round((completed / total) * 100) : 0);

                    // Hanya update progress bar jika nilainya valid dan bergerak maju
                    if (completed > 0 || pct > 0) {
                        $('#batchProgressPercent').text(pct + '%');
                        $('#batchProgressBar').css('width', pct + '%');
                        $('#batchProgressLabel').html(`<i class="bi bi-cpu-fill text-primary me-1"></i> Background Worker AI: Selesai ${completed} dari ${total} tabel (${pct}%)`);
                    }

                    // Update local status map and UI badges incrementally
                    if (res.batch_status && Object.keys(res.batch_status).length > 0) {
                        let hasChanges = false;
                        for (let t_num in res.batch_status) {
                            let old = window.batchStatusMap[t_num];
                            let curr = res.batch_status[t_num];
                            if (!old || old.status !== curr.status || old.diff_count !== curr.diff_count) {
                                window.batchStatusMap[t_num] = curr;
                                hasChanges = true;
                                let foundItem = window.currentMatchedTables ? window.currentMatchedTables.find(t => t.nomor_tabel === t_num) : null;
                                if (foundItem) {
                                    renderRowBadge(foundItem, curr);
                                }
                            }
                        }
                        if (hasChanges) {
                            updateBatchCounters();
                        }
                    }

                    let isTrulyDone = (total > 0 && completed >= total);
                    if (prog.is_running && window.isBatchRunning) {
                        consecutiveStopCount = 0;
                        batchPollTimer = setTimeout(pollBatchProgress, 1500);
                    } else if (isTrulyDone) {
                        finishBatch(true, completed, total);
                    } else {
                        // Toleransi jika worker sesaat tidak update (misal sedang fetch API lambat / lock file)
                        consecutiveStopCount++;
                        if (consecutiveStopCount < 4 && window.isBatchRunning) {
                            batchPollTimer = setTimeout(pollBatchProgress, 1500);
                        } else {
                            finishBatch(false, completed, total);
                        }
                    }
                } else {
                    finishBatch(false);
                }
            },
            error: function() {
                if (window.isBatchRunning) {
                    batchPollTimer = setTimeout(pollBatchProgress, 3000);
                }
            }
        });
    }

    function stopBatchValidation() {
        $.post("<?= base_url('admin/stop_batch_worker') ?>", { ta: activeYear }, function() {
            finishBatch(false);
        });
    }

    function finishBatch(isSuccess = true, completed = 0, total = 0) {
        window.isBatchRunning = false;
        if (batchPollTimer) clearTimeout(batchPollTimer);
        $('#btnBatchVerify').prop('disabled', false).removeClass('opacity-50');
        $('#btnStopBatch').addClass('d-none');

        renderAllMatchedRows();
        updateBatchCounters();

        let diffCount = parseInt($('#cntDiff').text()) || 0;
        let matchCount = parseInt($('#cntMatch').text()) || 0;

        if (isSuccess || (total > 0 && completed >= total)) {
            $('#batchProgressBar').css('width', '100%');
            $('#batchProgressPercent').text('100%');
            if (diffCount > 0) {
                $('#batchProgressLabel').html(`<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Validasi selesai! Ditemukan <strong>${diffCount} tabel dengan selisih angka</strong>. <a href="javascript:void(0)" onclick="$('#btnFilterDiff').click()" class="text-danger fw-bold text-decoration-underline ms-1">Klik untuk lihat tabel beda nilai &raquo;</a>`);
            } else {
                $('#batchProgressLabel').html(`<i class="bi bi-check-circle-fill text-success me-1"></i> Validasi selesai! Seluruh tabel yang diperiksa 100% cocok.`);
            }
        } else {
            let pct = total > 0 ? Math.round((completed / total) * 100) : 0;
            if (pct > 0) {
                $('#batchProgressBar').css('width', pct + '%');
                $('#batchProgressPercent').text(pct + '%');
            }
            $('#batchProgressLabel').html(`<i class="bi bi-pause-circle-fill text-warning me-1"></i> Proses validasi terhenti di <strong>${completed} dari ${total} tabel</strong> (${pct}%). Data yang telah diperiksa tetap tersimpan.`);
        }
    }

    window.currentDiffFilter = 'diff';

    window.setDiffType = function(type, btn) {
        window.currentDiffFilter = type;
        $('.btn-filter-type').removeClass('active btn-danger btn-success btn-warning btn-dark').addClass('btn-outline-secondary');
        if (type === 'diff') $(btn).removeClass('btn-outline-secondary').addClass('btn-danger active text-white');
        else if (type === 'match') $(btn).removeClass('btn-outline-secondary').addClass('btn-success active text-white');
        else if (type === 'pdf_only') $(btn).removeClass('btn-outline-secondary').addClass('btn-warning active text-dark');
        else $(btn).removeClass('btn-outline-secondary').addClass('btn-dark active text-white');
        applyDiffFilters();
    };

    window.applyDiffFilters = function() {
        let activeType = window.currentDiffFilter || 'diff';
        let kw = ($('#diffSearchInput').val() || '').trim().toLowerCase();
        let sec = ($('#diffSectionSelect').val() || 'all').toLowerCase();

        let visibleCount = 0;
        $('.item-row').each(function() {
            let rowType = $(this).data('type');
            let rowWilayah = ($(this).data('wilayah') || '').toLowerCase();
            let rowMetric = ($(this).data('metric') || '').toLowerCase();
            let rowSec = ($(this).data('section') || '').toLowerCase();

            let matchType = (activeType === 'all') || (rowType === activeType);
            let matchKw = !kw || rowWilayah.includes(kw) || rowMetric.includes(kw);
            let matchSec = (sec === 'all') || (rowSec === sec);

            if (matchType && matchKw && matchSec) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        if (visibleCount === 0) {
            $('#noResultsMessage').removeClass('d-none');
        } else {
            $('#noResultsMessage').addClass('d-none');
        }
    };

    function verify_tabel(nomor_tabel, tolerance = 0) {
        window.currentTableNumber = nomor_tabel;
        window.currentTolerance = tolerance;

        let modal = new bootstrap.Modal(document.getElementById('modalDiff'));
        modal.show();
        $('#diffContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" style="width:2.5rem; height:2.5rem;" role="status"></div>
                <h6 class="text-dark fw-bold mb-1">Menganalisa & Membandingkan Data Tabel ${nomor_tabel}...</h6>
                <p class="text-muted small">Sedang mengunduh sumber data terbaru dan mencocokkan setiap sel angka ${tolerance > 0 ? `(Toleransi: ±${tolerance})` : ''}.</p>
            </div>
        `);

        let exportUrl = `<?= base_url('admin/export_tabel_excel') ?>?nomor_tabel=${encodeURIComponent(nomor_tabel)}&pdf_path=${encodeURIComponent(currentPdfPath)}&db_json_path=${encodeURIComponent(currentDbJsonPath)}&tolerance=${tolerance}`;

        $.ajax({
            url: "<?= base_url('admin/verify_tabel_pdf') ?>",
            type: "POST",
            data: { 
                nomor_tabel: nomor_tabel,
                pdf_path: currentPdfPath,
                db_json_path: currentDbJsonPath,
                tolerance: tolerance
            },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    let totalDiffs = res.summary ? res.summary.total_diffs : (res.diffs ? res.diffs.length : 0);
                    let totalMatches = res.summary ? res.summary.total_matches : (res.matches ? res.matches.length : 0);
                    let totalPdfOnly = res.summary ? res.summary.total_pdf_only : (res.pdf_only ? res.pdf_only.length : 0);
                    let totalAll = totalDiffs + totalMatches + totalPdfOnly;

                    window.currentDiffFilter = totalDiffs > 0 ? 'diff' : 'match';

                    // Update main list badge & counters
                    let status = totalDiffs > 0 ? 'diff' : 'match';
                    window.batchStatusMap[nomor_tabel] = {
                        status: status,
                        diff_count: totalDiffs,
                        match_count: totalMatches
                    };
                    let foundItem = window.currentMatchedTables ? window.currentMatchedTables.find(t => t.nomor_tabel === nomor_tabel) : null;
                    if (foundItem) {
                        renderRowBadge(foundItem, window.batchStatusMap[nomor_tabel]);
                        updateBatchCounters();
                    }
                    if (currentPdfPath) {
                        $.post("<?= base_url('admin/save_batch_status') ?>", {
                            pdf_path: currentPdfPath,
                            batch_data: JSON.stringify(window.batchStatusMap)
                        });
                    }

                    // Collect categories / sections if any
                    let sectionSet = new Set();
                    if (res.diffs) res.diffs.forEach(d => { if (d.section) sectionSet.add(d.section); });
                    if (res.matches) res.matches.forEach(m => { if (m.section) sectionSet.add(m.section); });
                    if (res.pdf_only) res.pdf_only.forEach(p => { if (p.section) sectionSet.add(p.section); });

                    let html = `
                        <!-- Header Box -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                            <div>
                                <h5 class="fw-black text-dark mb-1">
                                    <i class="bi bi-table text-primary me-2"></i>Tabel ${nomor_tabel}
                                </h5>
                                <div class="text-muted small">
                                    Memverifikasi kesesuaian antara cetakan buku PDF dengan database resmi.
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fs-6 fw-bold">
                                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Sumber: ${res.source_type}
                                </span>
                                <a href="${exportUrl}" target="_blank" class="btn btn-sm btn-success fw-bold rounded-pill px-3 py-2 shadow-xs">
                                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Unduh Berita Acara (.xlsx)
                                </a>
                            </div>
                        </div>
                    `;
                    
                    if (totalDiffs === 0 && totalPdfOnly === 0) {
                        html += `
                            <div class="card border border-success-subtle bg-success-subtle rounded-3 p-4 my-2 shadow-xs text-center" style="border-left: 5px solid #10b981 !important;">
                                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mx-auto mb-2 shadow-xs" style="width: 48px; height: 48px;">
                                    <i class="bi bi-check-lg fw-bold" style="font-size: 1.6rem;"></i>
                                </div>
                                <h5 class="fw-bold text-success-emphasis mb-1" style="font-size: 1.1rem;">100% Cocok Sempurna</h5>
                                <p class="text-secondary small mb-3" style="font-size: 0.82rem;">
                                    Seluruh data (<strong>${totalMatches} angka</strong>) pada cetakan PDF sama persis dengan sumber aslinya tanpa selisih.
                                </p>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge bg-white text-success border border-success-subtle px-3 py-1-5 rounded-pill fw-semibold" style="font-size: 0.75rem;">
                                        <i class="bi bi-shield-check me-1"></i> Terverifikasi Bersih
                                    </span>
                                    <a href="${exportUrl}" target="_blank" class="btn btn-sm btn-success fw-semibold rounded-pill px-3 py-1-5 shadow-xs" style="font-size: 0.78rem;">
                                        <i class="bi bi-file-earmark-excel-fill me-1"></i> Unduh Berita Acara (.xlsx)
                                    </a>
                                </div>
                            </div>
                        `;
                    } else {
                        // 1. KPI Cards
                        html += `
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="p-3 rounded-3 border ${totalDiffs > 0 ? 'bg-danger-subtle border-danger' : 'bg-light'} d-flex align-items-center shadow-xs">
                                        <div class="rounded-circle p-2 ${totalDiffs > 0 ? 'bg-danger text-white' : 'bg-secondary text-white'} me-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                                            <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-black fs-4 ${totalDiffs > 0 ? 'text-danger' : 'text-muted'} mb-0">${totalDiffs} Angka</div>
                                            <div class="small fw-semibold ${totalDiffs > 0 ? 'text-danger' : 'text-muted'}">Beda Nilai (Perlu Dicek)</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 rounded-3 border bg-success-subtle border-success d-flex align-items-center shadow-xs">
                                        <div class="rounded-circle p-2 bg-success text-white me-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                                            <i class="bi bi-check-lg fs-3 fw-bold"></i>
                                        </div>
                                        <div>
                                            <div class="fw-black fs-4 text-success mb-0">${totalMatches} Angka</div>
                                            <div class="small text-success fw-semibold">Cocok Sempurna</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 rounded-3 border bg-warning-subtle border-warning d-flex align-items-center shadow-xs">
                                        <div class="rounded-circle p-2 bg-warning-emphasis text-white me-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                                            <i class="bi bi-journal-bookmark-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-black fs-4 text-warning-emphasis mb-0">${totalPdfOnly} Info</div>
                                            <div class="small text-warning-emphasis fw-semibold">Khusus Cetakan PDF</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        // Info Alert if Multi-Year / Satudata / Google Sheets
                        if (totalPdfOnly > 0) {
                            html += `
                                <div class="alert alert-info border-info-subtle py-2 px-3 mb-3 rounded-3 d-flex align-items-center shadow-xs" style="font-size: 0.82rem;">
                                    <i class="bi bi-info-circle-fill text-info fs-5 me-2 flex-shrink-0"></i>
                                    <div>
                                        <strong>Penyelarasan Multi-Tahun:</strong> Sumber data (${res.source_type}) memuat data tahun terbaru (<strong>2025</strong>). Kolom tahun-tahun sebelumnya yang tercetak di buku PDF dipisahkan ke tab <em>'Khusus Cetakan PDF'</em> agar perbandingan tetap presisi dan tidak dianggap selisih data.
                                    </div>
                                </div>
                            `;
                        }

                        // 2. Control Toolbar (Filters & Search)
                        html += `
                            <div class="card bg-light border p-3 rounded-3 mb-3 shadow-xs">
                                <div class="row g-2 align-items-center justify-content-between">
                                    <div class="col-md-7 d-flex align-items-center flex-wrap gap-2">
                                        <span class="small fw-bold text-dark me-1"><i class="bi bi-funnel-fill text-secondary me-1"></i>Filter:</span>
                                        <div class="btn-group btn-group-sm" role="group">
                                            ${totalDiffs > 0 ? `<button type="button" class="btn btn-danger active btn-filter-type fw-bold px-3 py-1" onclick="setDiffType('diff', this)"><i class="bi bi-exclamation-octagon-fill me-1"></i> Beda Nilai (${totalDiffs})</button>` : ''}
                                            <button type="button" class="btn ${totalDiffs === 0 ? 'btn-success active text-white' : 'btn-outline-secondary'} btn-filter-type fw-bold px-3 py-1" onclick="setDiffType('match', this)"><i class="bi bi-check-circle-fill me-1"></i> Cocok (${totalMatches})</button>
                                            ${totalPdfOnly > 0 ? `<button type="button" class="btn btn-outline-secondary btn-filter-type fw-bold px-3 py-1" onclick="setDiffType('pdf_only', this)"><i class="bi bi-journal-bookmark me-1"></i> Cuma di PDF (${totalPdfOnly})</button>` : ''}
                                            <button type="button" class="btn btn-outline-secondary btn-filter-type fw-bold px-3 py-1" onclick="setDiffType('all', this)">Semua (${totalAll})</button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-5 d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                                        <div class="d-flex align-items-center gap-1 bg-white border px-2 py-1 rounded-2 shadow-xs">
                                            <span class="small fw-bold text-secondary text-nowrap" style="font-size:0.75rem;"><i class="bi bi-sliders me-1"></i>Toleransi:</span>
                                            <select class="form-select form-select-sm border-0 p-0 fw-semibold text-primary" style="width: auto; font-size: 0.78rem;" onchange="verify_tabel('${nomor_tabel}', this.value)">
                                                <option value="0" ${tolerance == 0 ? 'selected' : ''}>Persis (0.0)</option>
                                                <option value="0.05" ${tolerance == 0.05 ? 'selected' : ''}>± 0.05</option>
                                                <option value="0.1" ${tolerance == 0.1 ? 'selected' : ''}>± 0.1 (BPS)</option>
                                                <option value="0.5" ${tolerance == 0.5 ? 'selected' : ''}>± 0.5</option>
                                                <option value="1.0" ${tolerance == 1.0 ? 'selected' : ''}>± 1.0</option>
                                            </select>
                                        </div>
                                        ${sectionSet.size > 0 ? `
                                            <select id="diffSectionSelect" class="form-select form-select-sm" onchange="applyDiffFilters()" style="max-width: 130px;">
                                                <option value="all">Semua Bagian</option>
                                                ${Array.from(sectionSet).map(s => `<option value="${s}">${s}</option>`).join('')}
                                            </select>
                                        ` : ''}
                                        <div class="input-group input-group-sm flex-fill" style="min-width: 140px;">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" id="diffSearchInput" class="form-control" placeholder="Cari nama Kab/Kota..." onkeyup="applyDiffFilters()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        // 3. Card Comparison Container
                        html += `<div id="comparisonList" class="mb-3">`;

                        // Render Diffs (Default Active)
                        if (res.diffs && res.diffs.length > 0) {
                            res.diffs.forEach(d => {
                                html += `
                                    <div class="card mb-2 border border-danger-subtle shadow-xs item-row" data-type="diff" data-wilayah="${d.wilayah.toLowerCase()}" data-metric="${d.metric.toLowerCase()}" data-section="${(d.section || '').toLowerCase()}">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom border-light">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary text-white px-2 py-1 fs-6 fw-bold me-2 shadow-xs">
                                                        <i class="bi bi-geo-alt-fill me-1"></i>${d.wilayah}
                                                    </span>
                                                    ${d.section ? `<span class="badge rounded-pill px-2 py-1 fw-bold" style="background:#f3e8ff; color:#7e22ce; border:1px solid #d8b4fe; font-size:0.75rem;"><i class="bi bi-tag-fill me-1"></i>${d.section}</span>` : ''}
                                                </div>
                                                <span class="badge bg-danger text-white px-3 py-1 rounded-pill fw-bold shadow-xs">
                                                    <i class="bi bi-exclamation-octagon-fill me-1"></i> ${d.note.split('(')[0].trim() || 'Beda Nilai'}
                                                </span>
                                            </div>
                                            
                                            <div class="row align-items-center g-3">
                                                <div class="col-md-4">
                                                    <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:0.04em;">Nama Data / Metrik:</div>
                                                    <div class="fs-5 fw-bold text-dark mt-1">${d.metric}</div>
                                                </div>

                                                <div class="col-md-5">
                                                    <div class="d-flex align-items-center justify-content-center p-2 rounded-3 bg-light border">
                                                        <!-- PDF Side -->
                                                        <div class="text-center px-3 py-2 rounded-3 bg-white border border-danger shadow-xs flex-fill">
                                                            <div class="small text-danger fw-bold mb-1"><i class="bi bi-file-earmark-pdf-fill me-1"></i>Di Buku PDF</div>
                                                            <div class="fs-4 fw-black text-danger font-monospace">${d.pdf_val}</div>
                                                        </div>

                                                        <!-- VS Arrow -->
                                                        <div class="px-3 text-center">
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6">≠</span>
                                                        </div>

                                                        <!-- DB Side -->
                                                        <div class="text-center px-3 py-2 rounded-3 bg-white border border-success shadow-xs flex-fill">
                                                            <div class="small text-success fw-bold mb-1"><i class="bi bi-database-fill me-1"></i>Di Database Sumber</div>
                                                            <div class="fs-4 fw-black text-success font-monospace">${d.src_val}</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="p-2 rounded-3 bg-danger-subtle border border-danger-subtle text-danger h-100 d-flex flex-column justify-content-center">
                                                        <div class="fw-bold small"><i class="bi bi-info-circle-fill me-1"></i> Penjelasan:</div>
                                                        <div class="small mt-1" style="line-height:1.3;">
                                                            Di cetakan PDF tertulis <strong>${d.pdf_val}</strong>, tetapi di database tertulis <strong>${d.src_val}</strong>.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        }

                        // Render Matches (Hidden by default if diffs > 0)
                        if (res.matches && res.matches.length > 0) {
                            let defaultDisplay = totalDiffs > 0 ? 'display: none;' : '';
                            res.matches.forEach(m => {
                                html += `
                                    <div class="card mb-2 border border-success-subtle shadow-xs item-row" data-type="match" style="${defaultDisplay}" data-wilayah="${m.wilayah.toLowerCase()}" data-metric="${m.metric.toLowerCase()}" data-section="${(m.section || '').toLowerCase()}">
                                        <div class="card-body py-2 px-3">
                                            <div class="row align-items-center g-2">
                                                <div class="col-md-4">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                                        <i class="bi bi-geo-alt me-1"></i>${m.wilayah}
                                                    </span>
                                                    ${m.section ? `<span class="badge rounded-pill ms-1 small fw-semibold" style="background:#f3e8ff; color:#7e22ce; border:1px solid #d8b4fe;"><i class="bi bi-tag me-1"></i>${m.section}</span>` : ''}
                                                    <span class="fw-bold text-dark ms-2">${m.metric}</span>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="d-flex align-items-center justify-content-center p-2 rounded-2 ${m.is_tolerance_match ? 'bg-info-subtle border border-info-subtle' : 'bg-success-subtle border border-success-subtle'}">
                                                        <div class="text-center flex-fill">
                                                            <span class="small ${m.is_tolerance_match ? 'text-info-emphasis' : 'text-success'} fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>PDF:</span>
                                                            <span class="fs-6 fw-bold ${m.is_tolerance_match ? 'text-info-emphasis' : 'text-success'} font-monospace ms-1">${m.pdf_val !== undefined ? m.pdf_val : m.val}</span>
                                                        </div>
                                                        <div class="${m.is_tolerance_match ? 'text-info-emphasis' : 'text-success'} fw-bold px-2">${m.is_tolerance_match ? '≈' : '='}</div>
                                                        <div class="text-center flex-fill">
                                                            <span class="small ${m.is_tolerance_match ? 'text-info-emphasis' : 'text-success'} fw-semibold"><i class="bi bi-database me-1"></i>Database:</span>
                                                            <span class="fs-6 fw-bold ${m.is_tolerance_match ? 'text-info-emphasis' : 'text-success'} font-monospace ms-1">${m.src_val !== undefined ? m.src_val : m.val}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 text-end">
                                                    <span class="badge ${m.is_tolerance_match ? 'bg-info text-white' : 'bg-success text-white'} px-3 py-2 rounded-pill fw-bold">
                                                        <i class="bi ${m.is_tolerance_match ? 'bi-sliders' : 'bi-check-circle-fill'} me-1"></i> ${m.is_tolerance_match ? 'Cocok (Toleransi ' + (m.tolerance_diff > 0 ? '+' : '') + m.tolerance_diff + ')' : 'Sama Persis (Cocok)'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        }

                        // Render PDF Only (Hidden by default)
                        if (res.pdf_only && res.pdf_only.length > 0) {
                            res.pdf_only.forEach(p => {
                                html += `
                                    <div class="card mb-2 border border-warning-subtle shadow-xs item-row" data-type="pdf_only" style="display: none;" data-wilayah="${p.wilayah.toLowerCase()}" data-metric="${p.metric.toLowerCase()}" data-section="${(p.section || '').toLowerCase()}">
                                        <div class="card-body py-2 px-3">
                                            <div class="row align-items-center g-2">
                                                <div class="col-md-4">
                                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fw-bold">
                                                        <i class="bi bi-geo-alt me-1"></i>${p.wilayah}
                                                    </span>
                                                    ${p.section ? `<span class="badge rounded-pill ms-1 small bg-light text-secondary border">${p.section}</span>` : ''}
                                                    <span class="fw-bold text-dark ms-2">${p.metric}</span>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="p-2 rounded-2 bg-warning-subtle border border-warning-subtle text-center">
                                                        <span class="small text-warning-emphasis fw-bold me-2"><i class="bi bi-journal-bookmark me-1"></i>Di Buku PDF:</span>
                                                        <span class="font-monospace fw-bold text-dark fs-6">${p.values.join(' | ')}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small mb-1 d-inline-block">
                                                        <i class="bi bi-journal-bookmark me-1"></i> Cuma Ada di Cetakan PDF
                                                    </span>
                                                    <div class="text-muted small" style="font-size:0.78rem;">${p.note}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        }

                        // Empty State for search
                        html += `
                            <div id="noResultsMessage" class="d-none text-center py-5">
                                <i class="bi bi-search text-muted d-block mb-2" style="font-size: 2.5rem;"></i>
                                <h6 class="text-muted fw-bold">Tidak ada data yang cocok dengan pencarian / filter ini.</h6>
                            </div>
                        `;

                        html += `</div>`; // Close comparisonList
                    }
                    $('#diffContent').html(html);
                } else {
                    let msg = res.message || 'Terjadi kesalahan saat memproses data';
                    let isApiEmpty = msg.toLowerCase().includes('tidak ada data dalam api') || res.is_api_empty;
                    
                    let status = isApiEmpty ? 'api_empty' : 'diff';
                    window.batchStatusMap[nomor_tabel] = {
                        status: status,
                        diff_count: '!',
                        match_count: 0
                    };
                    let foundItem = window.currentMatchedTables ? window.currentMatchedTables.find(t => t.nomor_tabel === nomor_tabel) : null;
                    if (foundItem) {
                        renderRowBadge(foundItem, window.batchStatusMap[nomor_tabel]);
                        updateBatchCounters();
                    }
                    if (currentPdfPath) {
                        $.post("<?= base_url('admin/save_batch_status') ?>", {
                            pdf_path: currentPdfPath,
                            batch_data: JSON.stringify(window.batchStatusMap)
                        });
                    }
                    
                    if (isApiEmpty) {
                        $('#diffContent').html(`
                            <div class="text-center py-5">
                                <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle mb-3 shadow-xs" style="width: 72px; height: 72px;">
                                    <i class="bi bi-database-slash" style="font-size: 2.2rem;"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-2">Tidak Ada Data dalam API</h5>
                                <p class="text-muted small mx-auto mb-3" style="max-width: 480px;">
                                    Server API Satu Data Jawa Tengah belum memuat data untuk tabel ini atau tabel masih kosong di database pusat.
                                </p>
                                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-light border rounded-pill text-muted small">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    <span>Tabel tidak dapat divalidasi karena sumber data API belum tersedia.</span>
                                </div>
                            </div>
                        `);
                    } else {
                        $('#diffContent').html(`
                            <div class="alert alert-danger d-flex align-items-center p-3 rounded-3 shadow-xs">
                                <i class="bi bi-x-circle-fill fs-4 me-3"></i>
                                <div>
                                    <div class="fw-bold">${res.message}</div>
                                    ${res.detail ? `<div class="small mt-1 text-danger-emphasis">${res.detail}</div>` : ''}
                                </div>
                            </div>
                        `);
                    }
                }
            },
            error: function(xhr, status, error) {
                $('#diffContent').html(`<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-bug-fill fs-4 me-3"></i> Terjadi kesalahan internal server: ${error}</div>`);
            }
        });
    }

    // Auto-load master data if already available on server
    $(document).ready(function() {
        if (hasMasterInitial && window.currentMatchedTables && window.currentMatchedTables.length > 0) {
            $('#badgeStatus').removeClass('bg-secondary bg-primary').addClass('bg-success text-white').text('SIAP DIVALIDASI');
            $('#loaderArea').hide();
            $('#resultArea').show();

            $('#countMatched').text(window.currentMatchedTables.length);

            let missingList = <?= json_encode($master_data['missing_in_pdf'] ?? []) ?>;
            let unregList = <?= json_encode($master_data['unregistered_in_db'] ?? []) ?>;

            $('#countMissing').text(missingList.length);
            if (missingList.length === 0) {
                $('#listMissing').html('<li class="list-group-item text-muted text-center py-4">Semua tabel dari database ditemukan di PDF!</li>');
            } else {
                $('#listMissing').empty();
                missingList.forEach(item => {
                    $('#listMissing').append(`
                        <li class="list-group-item missing py-3">
                            <span class="badge bg-warning text-dark mb-1">Tabel ${item.nomor_tabel}</span>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 0.9rem;">${item.judul_db}</h6>
                            <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i> ${item.reason}</p>
                        </li>`);
                });
            }

            $('#countUnreg').text(unregList.length);
            if (unregList.length === 0) {
                $('#listUnreg').html('<li class="list-group-item text-muted text-center py-4">Tidak ada tabel asing. Bersih!</li>');
            } else {
                $('#listUnreg').empty();
                unregList.forEach(item => {
                    $('#listUnreg').append(`
                        <li class="list-group-item unreg py-3">
                            <span class="badge bg-danger mb-1">Tabel ${item.nomor_tabel}</span>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 0.9rem;">${item.judul_pdf}</h6>
                            <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i> ${item.reason}</p>
                        </li>`);
                });
            }

            renderAllMatchedRows();
            updateBatchCounters();

            if (isBatchRunningInitial) {
                window.isBatchRunning = true;
                $('#btnBatchVerify').prop('disabled', true).addClass('opacity-50');
                $('#btnStopBatch').removeClass('d-none');
                $('#batchProgressBox').removeClass('d-none');
                pollBatchProgress();
            }
        }
    });
</script>
