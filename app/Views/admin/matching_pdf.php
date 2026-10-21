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
                <p class="text-muted" style="font-size: 0.85rem; margin-top: 0; margin-bottom: 0;">Sistem Deteksi Anomali & Sinkronisasi PDF vs Sumber Data (Tabel Dinamis & API Satu Data)</p>
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
                <!-- KPI Cards Interaktif (Klik untuk Filter Cepat) & Export Global -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <span class="small fw-bold text-secondary"><i class="bi bi-speedometer2 me-1 text-primary"></i>Ringkasan Audit (Klik kartu untuk memfilter):</span>
                    <a href="<?= base_url('admin/export_rekap_matching?ta=' . ($ta ?? '2026')) ?>" target="_blank" class="btn btn-xs btn-outline-success fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                        <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export Rekap Global (.xls)
                    </a>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 px-3 rounded-3 border bg-light d-flex align-items-center justify-content-between shadow-xs" style="cursor:pointer;" onclick="quickKpiFilter('all')" title="Tampilkan Semua Tabel Aktif">
                            <div>
                                <div class="text-muted fw-bold" style="font-size:0.7rem;">TOTAL AKTIF</div>
                                <div class="fw-black fs-5 text-dark" id="kpiTotalCard">0</div>
                            </div>
                            <i class="bi bi-layers-fill text-primary fs-4 opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 px-3 rounded-3 border bg-success-subtle border-success-subtle d-flex align-items-center justify-content-between shadow-xs" style="cursor:pointer;" onclick="quickKpiFilter('match')" title="Filter Tabel yang 100% Cocok">
                            <div>
                                <div class="text-success-emphasis fw-bold" style="font-size:0.7rem;">DATA SAMA</div>
                                <div class="fw-black fs-5 text-success" id="kpiMatchCard">0</div>
                            </div>
                            <i class="bi bi-check-circle-fill text-success fs-4 opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 px-3 rounded-3 border bg-danger-subtle border-danger-subtle d-flex align-items-center justify-content-between shadow-xs" style="cursor:pointer;" onclick="quickKpiFilter('diff')" title="Filter Tabel yang Ada Selisih">
                            <div>
                                <div class="text-danger-emphasis fw-bold" style="font-size:0.7rem;">DATA BERBEDA</div>
                                <div class="fw-black fs-5 text-danger" id="kpiDiffCard">0</div>
                            </div>
                            <i class="bi bi-exclamation-octagon-fill text-danger fs-4 opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 px-3 rounded-3 border bg-warning-subtle border-warning-subtle d-flex align-items-center justify-content-between shadow-xs" style="cursor:pointer;" onclick="quickKpiFilter('api_empty')" title="Filter Tabel yang Datanya Masih Kosong">
                            <div>
                                <div class="text-warning-emphasis fw-bold" style="font-size:0.7rem;">DATA KOSONG</div>
                                <div class="fw-black fs-5 text-warning-emphasis" id="kpiEmptyCard">0</div>
                            </div>
                            <i class="bi bi-database-slash text-warning-emphasis fs-4 opacity-75"></i>
                        </div>
                    </div>
                </div>

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
                            <!-- 1. TABS SUMBER DATA (Clean & Modern) -->
                            <div class="bg-white border-bottom px-3 pt-2">
                                <ul class="nav nav-tabs border-bottom-0 gap-1" id="sourceTabs" role="tablist">
                                    <li class="nav-item">
                                        <button type="button" id="tabSourceAll" class="nav-link active fw-bold text-dark border-0 border-bottom border-3 border-primary pb-2 px-3 bg-transparent" style="font-size:0.83rem;" onclick="filterMatchedSource('all', this)">
                                            <i class="bi bi-collection-fill text-primary me-1"></i> Semua Data (<span id="cntSourceAll">0</span>)
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" id="tabSourceDinamis" class="nav-link fw-semibold text-secondary border-0 pb-2 px-3 bg-transparent" style="font-size:0.83rem;" onclick="filterMatchedSource('dinamis', this)">
                                            <i class="bi bi-grid-3x3-gap-fill text-success me-1"></i> Tabel Dinamis (<span id="cntSourceDinamis">0</span>)
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" id="tabSourceApi" class="nav-link fw-semibold text-secondary border-0 pb-2 px-3 bg-transparent" style="font-size:0.83rem;" onclick="filterMatchedSource('api', this)">
                                            <i class="bi bi-hdd-network-fill text-info me-1"></i> API Satudata (<span id="cntSourceApi">0</span>)
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <!-- 2. STATUS FILTER & ACTION TOOLBAR (Hanya 1 Baris Rapi) -->
                            <div class="p-3 bg-light border-bottom">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <!-- Status Pills -->
                                    <div class="d-flex align-items-center gap-1 flex-wrap" id="batchFilters">
                                        <button type="button" id="btnFilterAll" class="btn btn-xs btn-secondary active fw-bold px-2 py-1 rounded-pill text-white" style="font-size:0.75rem;" onclick="filterMatchedStatus('all', this)">
                                            Semua (<span id="cntAll">0</span>)
                                        </button>
                                        <button type="button" id="btnFilterDiff" class="btn btn-xs btn-outline-danger fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('diff', this)">
                                            <i class="bi bi-exclamation-octagon-fill me-1"></i>Ada Beda (<span id="cntDiff">0</span>)
                                        </button>
                                        <button type="button" id="btnFilterMatch" class="btn btn-xs btn-outline-success fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('match', this)">
                                            <i class="bi bi-check-circle-fill me-1"></i>Cocok (<span id="cntMatch">0</span>)
                                        </button>
                                        <button type="button" id="btnFilterEmpty" class="btn btn-xs btn-outline-warning fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('api_empty', this)">
                                            <i class="bi bi-database-slash me-1"></i>Data Kosong (<span id="cntEmpty">0</span>)
                                        </button>
                                        <button type="button" id="btnFilterUnverified" class="btn btn-xs btn-outline-secondary fw-bold px-2 py-1 rounded-pill" style="font-size:0.75rem;" onclick="filterMatchedStatus('unverified', this)">
                                            <i class="bi bi-clock me-1"></i>Belum Cek (<span id="cntUnverified">0</span>)
                                        </button>
                                    </div>

                                    <!-- Action & Search -->
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <button id="btnBatchVerifyDinamis" onclick="startBatchValidation('dinamis')" class="btn btn-sm btn-outline-success fw-bold px-2 py-1 rounded-pill shadow-xs" style="font-size:0.73rem;" title="Scan Cepat (~5 detik): Hanya memvalidasi semua Tabel Dinamis">
                                            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Scan Dinamis
                                        </button>
                                        <button id="btnBatchVerify" onclick="startBatchValidation('all')" class="btn btn-sm btn-success fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem; background: linear-gradient(135deg, #10b981, #059669); border:none;" title="Validasi seluruh tabel (Tabel Dinamis & API Satu Data)">
                                            <i class="bi bi-lightning-charge-fill me-1"></i> Validasi Semua
                                        </button>
                                        <button id="btnStopBatch" onclick="stopBatchValidation()" class="btn btn-sm btn-outline-danger fw-bold px-3 py-1 rounded-pill d-none" style="font-size:0.75rem;">
                                            <i class="bi bi-stop-circle me-1"></i> Hentikan
                                        </button>
                                        <div class="input-group input-group-sm" style="max-width: 190px;">
                                            <span class="input-group-text bg-white py-0 px-2 border-end-0"><i class="bi bi-search text-muted" style="font-size:0.75rem;"></i></span>
                                            <input type="text" id="searchMatchedInput" class="form-control form-control-sm py-1 px-2 border-start-0" placeholder="Cari tabel..." style="font-size: 0.75rem;" onkeyup="applyMatchedFilters()">
                                        </div>
                                    </div>
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
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Tabel Tidak Ada di PDF (<span id="countMissing">0</span>)
                        </button>
                        </h2>
                        <div id="collapseMissing" class="accordion-collapse collapse" aria-labelledby="headingMissing" data-bs-parent="#accordionResult">
                        <div class="accordion-body p-0">
                            <ul class="list-group list-group-flush" id="listMissing"></ul>
                        </div>
                        </div>
                    </div>

                    <!-- UNREGISTERED / BELUM DIKONFIGURASI TABEL DINAMIS -->
                    <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                        <h2 class="accordion-header" id="headingUnreg">
                        <button class="accordion-button collapsed bg-danger-subtle text-danger fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUnreg" aria-expanded="false" aria-controls="collapseUnreg">
                            <i class="bi bi-sliders2-vertical me-2"></i> Belum Dikonfigurasi Tabel Dinamis (<span id="countUnreg">0</span>)
                        </button>
                        </h2>
                        <div id="collapseUnreg" class="accordion-collapse collapse" aria-labelledby="headingUnreg" data-bs-parent="#accordionResult">
                        <div class="accordion-body p-0">
                            <div class="p-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Klik <strong>Konfigurasi Tabel Dinamis</strong> untuk mulai menyusun struktur tabel dari daftar ini.</span>
                                <div class="input-group input-group-sm" style="max-width: 220px;">
                                    <span class="input-group-text bg-white py-0 px-2"><i class="bi bi-search text-muted" style="font-size:0.75rem;"></i></span>
                                    <input type="text" id="searchUnregInput" class="form-control form-control-sm py-1 px-2" placeholder="Cari tabel belum dikonfigurasi..." style="font-size: 0.75rem;" onkeyup="filterUnregList()">
                                </div>
                            </div>
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
                renderUnregList(res.unregistered_in_db || []);

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
            let srcType = (item.source_type === 'dinamis' || parseInt(item.is_dynamic || 0) === 1) ? 'dinamis' : 'api';
            let info = window.batchStatusMap[item.nomor_tabel];
            if (srcType === 'dinamis' && item.dynamic_empty) {
                info = { status: 'api_empty', reason: 'Data nilai Tabel Dinamis belum diisi di database.' };
                window.batchStatusMap[item.nomor_tabel] = info;
            }
            if (!info) info = { status: 'unverified' };
            let srcBadge = srcType === 'dinamis'
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace shadow-xs" style="font-size:0.68rem;" title="Sumber Data: Tabel Dinamis"><i class="bi bi-grid-3x3-gap-fill me-1"></i>Tabel Dinamis</span>`
                : `<span class="badge bg-info-subtle text-primary border border-info-subtle rounded-pill font-monospace shadow-xs" style="font-size:0.68rem;" title="Sumber Data: API Satudata"><i class="bi bi-hdd-network-fill me-1"></i>API Satudata</span>`;

            let html = `
                <li class="list-group-item matched py-3 d-flex justify-content-between align-items-center" data-status="${info.status}" data-source="${srcType}" data-tnum="${item.nomor_tabel}" id="matched_row_${cleanId}">
                    <div class="pe-3">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-secondary mb-0 fw-bold">Tabel ${item.nomor_tabel}</span>
                            ${srcBadge}
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

        let badgeHtml = '';
        let btnHtml = '';
        let isDyn = (item.source_type === 'dinamis' || parseInt(item.is_dynamic || 0) === 1);
        let dynLinkBtn = (isDyn && item.id_db)
            ? `<a href="<?= base_url('admin/view_tabel_dinamis') ?>/${item.id_db}" target="_blank" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 shadow-xs" title="Buka & Edit Isi Tabel Dinamis" style="font-size:0.75rem;"><i class="bi bi-pencil-square"></i></a>`
            : '';

        if (status === 'match') {
            badgeHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill me-1"></i>Cocok 100% (${info.match_count ? info.match_count + ' Data' : 'OK'})</span>`;
            btnHtml = `
                ${dynLinkBtn}
                <a href="<?= base_url('admin/export_tabel_excel') ?>?nomor_tabel=${encodeURIComponent(item.nomor_tabel)}&pdf_path=${encodeURIComponent(currentPdfPath)}&db_json_path=${encodeURIComponent(currentDbJsonPath)}&tolerance=0.0" target="_blank" class="btn btn-xs btn-outline-success rounded-pill px-2 py-1 shadow-xs" title="Unduh Laporan Validasi Data Excel (.xlsx)" style="font-size:0.75rem;">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-outline-success fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;" title="Klik untuk scan ulang & lihat detail tabel ini saja">
                    <i class="bi bi-search me-1"></i> Detail
                </button>`;
        } else if (status === 'diff') {
            let diffNum = (typeof info.diff_count === 'number') ? info.diff_count : (parseInt(info.diff_count) || null);
            let diffLabel = (diffNum !== null) ? `${diffNum} Selisih` : 'Ada Beda';
            let matchText = (info.match_count && info.match_count > 0) ? ` <span class="opacity-75 font-monospace" style="font-size:0.7rem;">(${info.match_count} Cocok)</span>` : '';
            badgeHtml = `<span class="badge bg-danger text-white px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.75rem;"><i class="bi bi-exclamation-octagon-fill me-1"></i>${diffLabel}${matchText}</span>`;
            btnHtml = `
                ${dynLinkBtn}
                <a href="<?= base_url('admin/export_tabel_excel') ?>?nomor_tabel=${encodeURIComponent(item.nomor_tabel)}&pdf_path=${encodeURIComponent(currentPdfPath)}&db_json_path=${encodeURIComponent(currentDbJsonPath)}&tolerance=0.0" target="_blank" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1 shadow-xs" title="Unduh Laporan Validasi Data Excel (.xlsx)" style="font-size:0.75rem;">
                    <i class="bi bi-file-earmark-excel"></i>
                </a>
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-danger fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;" title="Klik untuk scan ulang & periksa selisih tabel ini saja">
                    <i class="bi bi-search me-1"></i> Cek Beda
                </button>`;
        } else if (status === 'api_empty') {
            badgeHtml = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.75rem;"><i class="bi bi-database-slash me-1"></i>Data Kosong</span>`;
            btnHtml = `
                ${dynLinkBtn}
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm ${isDyn ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary fw-semibold'} px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;" title="${isDyn ? 'Klik untuk tarik nilai otomatis dari PDF atau scan ulang tabel ini' : 'Klik untuk cek ulang tabel ini'}">
                    <i class="bi ${isDyn ? 'bi-magic' : 'bi-search'} me-1"></i> ${isDyn ? 'Isi / Detail' : 'Detail'}
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
                ${dynLinkBtn}
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-outline-warning text-dark fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <i class="bi bi-search me-1"></i> Cek Angka
                </button>`;
        } else {
            badgeHtml = `<span class="badge bg-light text-muted border px-2 py-1 rounded-pill fw-normal" style="font-size:0.75rem;"><i class="bi bi-clock me-1"></i>Belum Dicek</span>`;
            btnHtml = `
                ${dynLinkBtn}
                <button onclick="verify_tabel('${item.nomor_tabel}')" class="btn btn-sm btn-primary fw-bold px-3 py-1 rounded-pill shadow-xs" style="font-size:0.75rem;">
                    <i class="bi bi-search me-1"></i> Cek Angka
                </button>`;
        }

        $rows.find('.badge-container').html(badgeHtml);
        $rows.find('.action-container').html(btnHtml);
        $(`#badge_${cleanId}`).html(badgeHtml);
        $(`#action_${cleanId}`).html(btnHtml);

        applyMatchedFilters();
    }

    function updateBatchCounters() {
        if (!window.currentMatchedTables) return;

        let srcFilter = window.currentSourceFilter || 'all';

        let stats = {
            all: { all: 0, diff: 0, match: 0, api_empty: 0, unverified: 0 },
            dinamis: { all: 0, diff: 0, match: 0, api_empty: 0, unverified: 0 },
            api: { all: 0, diff: 0, match: 0, api_empty: 0, unverified: 0 }
        };

        window.currentMatchedTables.forEach(item => {
            let srcType = (item.source_type === 'dinamis' || parseInt(item.is_dynamic || 0) === 1) ? 'dinamis' : 'api';
            let info = window.batchStatusMap[item.nomor_tabel];
            if (srcType === 'dinamis' && item.dynamic_empty) {
                info = { status: 'api_empty', reason: 'Data nilai Tabel Dinamis belum diisi di database.' };
                window.batchStatusMap[item.nomor_tabel] = info;
            }
            let status = info ? info.status : 'unverified';
            if (status !== 'diff' && status !== 'match' && status !== 'api_empty') {
                status = 'unverified';
            }

            stats.all.all++;
            stats.all[status]++;

            if (stats[srcType]) {
                stats[srcType].all++;
                stats[srcType][status]++;
            }
        });

        // 1. Update Tab Counters
        $('#cntSourceAll').text(stats.all.all);
        $('#cntSourceDinamis').text(stats.dinamis.all);
        $('#cntSourceApi').text(stats.api.all);

        // 2. Update Status Pills (Dinamis sesuai Tab Sumber yang sedang aktif)
        let activeStats = stats[srcFilter] || stats.all;
        $('#cntAll').text(activeStats.all);
        $('#cntDiff').text(activeStats.diff);
        $('#cntMatch').text(activeStats.match);
        $('#cntEmpty').text(activeStats.api_empty);
        $('#cntUnverified').text(activeStats.unverified);

        // 3. Update Top Interactive KPI Cards
        $('#kpiTotalCard').text(activeStats.all);
        $('#kpiMatchCard').text(activeStats.match);
        $('#kpiDiffCard').text(activeStats.diff);
        $('#kpiEmptyCard').text(activeStats.api_empty);
    }

    function quickKpiFilter(statusType) {
        let collapseEl = document.getElementById('collapseMatched');
        if (collapseEl && !collapseEl.classList.contains('show')) {
            new bootstrap.Collapse(collapseEl, { show: true });
        }
        let btnMap = {
            'all': '#btnFilterAll',
            'match': '#btnFilterMatch',
            'diff': '#btnFilterDiff',
            'api_empty': '#btnFilterEmpty',
            'unverified': '#btnFilterUnverified'
        };
        let targetBtn = document.querySelector(btnMap[statusType] || '#btnFilterAll');
        if (targetBtn) {
            filterMatchedStatus(statusType, targetBtn);
        }
    }

    function renderUnregList(unregList) {
        window.currentUnregTables = unregList || [];
        $('#countUnreg').text(window.currentUnregTables.length);
        if (window.currentUnregTables.length === 0) {
            $('#listUnreg').html('<li class="list-group-item text-muted text-center py-4">Semua tabel di PDF sudah dikonfigurasi ke Tabel Dinamis atau API Satu Data!</li>');
            return;
        }
        $('#listUnreg').empty();
        window.currentUnregTables.forEach(item => {
            let pagesTxt = (item.pages && item.pages.length > 0) ? `Hal. PDF: ${item.pages.join(', ')}` : '';
            let safeNo = (item.nomor_tabel || '').replace(/'/g, "\\'");
            let manualBtn = item.id_db
                ? `<a href="<?= base_url('admin/generator_tabel') ?>/${item.id_db}" target="_blank" class="btn btn-xs btn-outline-secondary fw-semibold rounded-pill px-2 py-1 shadow-xs" style="font-size:0.72rem;" title="Buka Generator Tabel Dinamis (Konfigurasi Manual)"><i class="bi bi-sliders2-vertical me-1"></i>Manual</a>`
                : `<a href="<?= base_url('admin/master_tabel') ?>?q=${encodeURIComponent(item.nomor_tabel)}" target="_blank" class="btn btn-xs btn-outline-secondary fw-semibold rounded-pill px-2 py-1 shadow-xs" style="font-size:0.72rem;" title="Cari di Master Tabel"><i class="bi bi-plus-circle me-1"></i>Master</a>`;

            let actionGroup = `
                <div class="d-flex align-items-center gap-1 flex-shrink-0 flex-wrap justify-content-end">
                    <button type="button" onclick="autoConfigFromPdf('${safeNo}', 0, this)" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 py-1 shadow-xs" style="font-size:0.74rem;" title="Deteksi otomatis baris, kolom & periode dari PDF lalu pindahkan ke Data Kosong">
                        <i class="bi bi-Cpu-fill bi-magic me-1"></i>Auto-Konfigurasi PDF
                    </button>
                    <button type="button" onclick="autoConfigFromPdf('${safeNo}', 1, this)" class="btn btn-sm btn-success text-white fw-bold rounded-pill px-3 py-1 shadow-xs" style="font-size:0.74rem;" title="Konfigurasi struktur otomatis dari PDF sekaligus langsung isi angka ke Tabel Dinamis">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Auto-Konfig + Isi Data
                    </button>
                    ${manualBtn}
                </div>`;

            $('#listUnreg').append(`
                <li class="list-group-item unreg py-3 d-flex justify-content-between align-items-center gap-3 unreg-row" id="unreg_row_${getSafeId(item.nomor_tabel)}" data-tnum="${(item.nomor_tabel || '').toLowerCase()}" data-title="${(item.judul_pdf || '').toLowerCase()}">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-danger">Tabel ${item.nomor_tabel}</span>
                            ${pagesTxt ? `<span class="badge bg-light text-secondary border font-monospace" style="font-size:0.7rem;"><i class="bi bi-file-earmark-pdf me-1"></i>${pagesTxt}</span>` : ''}
                        </div>
                        <h6 class="mb-1 fw-bold text-dark" style="font-size: 0.9rem;">${item.judul_pdf}</h6>
                        <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i> ${item.reason}</p>
                    </div>
                    ${actionGroup}
                </li>`);
        });
    }

    function autoConfigFromPdf(nomorTabel, autoFill, btnEl) {
        let item = (window.currentUnregTables || []).find(u => u.nomor_tabel === nomorTabel);
        if (!item) return;

        let $btn = $(btnEl);
        let $row = $btn.closest('li.unreg-row');
        let origHtml = $btn.html();
        $row.find('button, a').addClass('disabled').prop('disabled', true);
        $btn.html(`<span class="spinner-border spinner-border-sm me-1" style="width:0.7rem;height:0.7rem;"></span>${autoFill ? 'Merakit & Mengisi...' : 'Merakit Struktur...'}`);

        $.ajax({
            url: "<?= base_url('admin/auto_config_tabel_pdf') ?>",
            type: "POST",
            data: {
                nomor_tabel: item.nomor_tabel,
                id_db: item.id_db || 0,
                pages: JSON.stringify(item.pages || []),
                judul_pdf: item.judul_pdf || '',
                auto_fill: autoFill ? 1 : 0,
                ta: activeYear
            },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    // 1. Hapus dari daftar Belum Dikonfigurasi
                    window.currentUnregTables = (window.currentUnregTables || []).filter(u => u.nomor_tabel !== nomorTabel);
                    $('#countUnreg').text(window.currentUnregTables.length);
                    $row.fadeOut(250, function() {
                        $(this).remove();
                        if (window.currentUnregTables.length === 0) {
                            renderUnregList([]);
                        }
                    });

                    // 2. Masukkan ke daftar Tabel Ditemukan (Matched) dan set statusnya (Data Kosong / Match)
                    if (res.matched_entry) {
                        let mEntry = res.matched_entry;
                        let stEntry = res.state_entry || { status: 'api_empty' };
                        window.batchStatusMap[mEntry.nomor_tabel] = stEntry;

                        let existingIdx = (window.currentMatchedTables || []).findIndex(m => m.nomor_tabel === mEntry.nomor_tabel);
                        if (existingIdx >= 0) {
                            window.currentMatchedTables[existingIdx] = mEntry;
                        } else {
                            window.currentMatchedTables.unshift(mEntry);
                        }
                        $('#countMatched').text(window.currentMatchedTables.length);
                        renderAllMatchedRows();
                        updateBatchCounters();
                        applyMatchedFilters();
                    }
                } else {
                    alert("Gagal Auto-Konfigurasi: " + (res.message || "Terjadi kesalahan."));
                    $row.find('button, a').removeClass('disabled').prop('disabled', false);
                    $btn.html(origHtml);
                }
            },
            error: function() {
                alert("Gagal menghubungi server saat melakukan Auto-Konfigurasi PDF.");
                $row.find('button, a').removeClass('disabled').prop('disabled', false);
                $btn.html(origHtml);
            }
        });
    }

    function filterUnregList() {
        let q = ($('#searchUnregInput').val() || '').toLowerCase().trim();
        $('#listUnreg li.unreg-row').each(function() {
            let tnum = $(this).attr('data-tnum') || '';
            let title = $(this).attr('data-title') || '';
            if (!q || tnum.includes(q) || title.includes(q)) {
                $(this).removeClass('d-none').addClass('d-flex');
            } else {
                $(this).addClass('d-none').removeClass('d-flex');
            }
        });
    }

    window.currentBatchFilter = 'all';
    window.currentSourceFilter = 'all';

    function filterMatchedStatus(type, btn) {
        window.currentBatchFilter = type;
        $('#batchFilters button').removeClass('active btn-secondary btn-danger btn-success btn-warning text-white text-dark').addClass('btn-outline-secondary');
        if (type === 'all') $(btn).removeClass('btn-outline-secondary').addClass('active btn-secondary text-white');
        else if (type === 'diff') $(btn).removeClass('btn-outline-secondary').addClass('active btn-danger text-white');
        else if (type === 'match') $(btn).removeClass('btn-outline-secondary').addClass('active btn-success text-white');
        else if (type === 'api_empty') $(btn).removeClass('btn-outline-secondary').addClass('active btn-warning text-dark');
        else if (type === 'unverified') $(btn).removeClass('btn-outline-secondary').addClass('active btn-secondary text-white');

        applyMatchedFilters();
    }

    function filterMatchedSource(src, btn) {
        window.currentSourceFilter = src;
        
        // Tab indicator active style
        $('#sourceTabs button').removeClass('active fw-bold text-dark border-bottom border-3 border-primary border-success border-info')
                               .addClass('fw-semibold text-secondary');
        
        if (src === 'all') {
            $(btn).removeClass('fw-semibold text-secondary').addClass('active fw-bold text-dark border-bottom border-3 border-primary');
        } else if (src === 'dinamis') {
            $(btn).removeClass('fw-semibold text-secondary').addClass('active fw-bold text-dark border-bottom border-3 border-success');
        } else if (src === 'api') {
            $(btn).removeClass('fw-semibold text-secondary').addClass('active fw-bold text-dark border-bottom border-3 border-info');
        }

        applyMatchedFilters();
        updateBatchCounters();
    }

    function applyMatchedFilters() {
        let statusFilter = window.currentBatchFilter || 'all';
        let sourceFilter = window.currentSourceFilter || 'all';
        let searchQuery = ($('#searchMatchedInput').val() || '').toLowerCase().trim();

        $('#listMatched li.matched').each(function() {
            let rowStatus = $(this).attr('data-status') || 'unverified';
            let rowSource = $(this).attr('data-source') || 'api';
            let tnum = ($(this).attr('data-tnum') || '').toLowerCase();
            let title = $(this).find('h6').text().toLowerCase();

            // Status Filter Check
            let matchesStatus = (statusFilter === 'all');
            if (!matchesStatus) {
                if (statusFilter === 'unverified') {
                    matchesStatus = (rowStatus !== 'match' && rowStatus !== 'diff' && rowStatus !== 'api_empty');
                } else {
                    matchesStatus = (rowStatus === statusFilter);
                }
            }

            // Source Filter Check (Spreadsheet vs API)
            let matchesSource = (sourceFilter === 'all') || (rowSource === sourceFilter);

            // Search Text Check
            let matchesSearch = (!searchQuery || tnum.includes(searchQuery) || title.includes(searchQuery));

            if (matchesStatus && matchesSource && matchesSearch) {
                $(this).removeClass('d-filtered-out').addClass('d-flex');
            } else {
                $(this).addClass('d-filtered-out').removeClass('d-flex');
            }
        });
    }

    let batchPollTimer = null;

    function startBatchValidation(sourceFilter = 'all') {
        if (window.isBatchRunning) return;
        if (!window.currentMatchedTables || window.currentMatchedTables.length === 0) {
            alert("Tidak ada tabel yang siap divalidasi.");
            return;
        }

        window.isBatchRunning = true;
        consecutiveStopCount = 0;
        $('#btnBatchVerify, #btnBatchVerifyDinamis').prop('disabled', true).addClass('opacity-50');
        $('#btnStopBatch').removeClass('d-none');
        $('#batchProgressBox').removeClass('d-none');
        $('#batchProgressPercent').text('0%');
        $('#batchProgressBar').css('width', '0%');
        let labelMode = sourceFilter === 'dinamis' ? 'Tabel Dinamis' : 'Semua Tabel';
        $('#batchProgressLabel').html(`<i class="bi bi-cpu-fill text-primary me-1"></i> Memulai validasi (${labelMode})...`);

        $.ajax({
            url: "<?= base_url('admin/start_batch_worker') ?>",
            type: "POST",
            data: {
                ta: activeYear,
                tolerance: "0.0",
                source_filter: sourceFilter
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
        $('#btnBatchVerify, #btnBatchVerifyDinamis').prop('disabled', false).removeClass('opacity-50');
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
        $('.btn-filter-type').removeClass('active btn-danger btn-success btn-warning btn-dark text-white').addClass('btn-outline-secondary');
        if (type === 'diff') $(btn).removeClass('btn-outline-secondary').addClass('btn-danger active text-white');
        else if (type === 'match') $(btn).removeClass('btn-outline-secondary').addClass('btn-success active text-white');
        else if (type === 'pdf_only') $(btn).removeClass('btn-outline-secondary').addClass('btn-warning active text-dark');
        else $(btn).removeClass('btn-outline-secondary').addClass('btn-dark active text-white');
        applyDiffFilters();
    };

    window.switchDiffFilter = function(type) {
        let btn = document.querySelector(`.btn-filter-type[data-filter="${type}"]`);
        if (btn) {
            window.setDiffType(type, btn);
        }
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


    window.togglePdfSplitView = function(page = 1) {
        let $pane = $('#pdfSplitPane');
        let $main = $('#auditMainPane');
        let $iframe = $('#pdfSplitIframe');
        if ($pane.hasClass('d-none')) {
            let pdfUrl = `<?= base_url('admin/serve_master_pdf') ?>?ta=${encodeURIComponent(activeYear)}#page=${page}&view=FitH`;
            if ($iframe.attr('src') !== pdfUrl) {
                $iframe.attr('src', pdfUrl);
            }
            $pane.removeClass('d-none');
            $main.removeClass('col-lg-12').addClass('col-lg-6');
        } else {
            $pane.addClass('d-none');
            $main.removeClass('col-lg-6').addClass('col-lg-12');
        }
    };

    window.syncAutofillFromPdf = function() {
        let res = window.lastVerifyResult;
        if (!res || !res.id_indikator || !res.autofill_cells || res.autofill_cells.length === 0) return;
        if (!confirm(`Isi otomatis ${res.autofill_cells.length} sel angka dari PDF ke Tabel Dinamis untuk Tabel ${window.currentTableNumber}?`)) return;

        let $btn = $('#btnAutofillFromPdf');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan ke Tabel Dinamis...');

        $.ajax({
            url: "<?= base_url('admin/sync_pdf_to_dinamis') ?>",
            type: "POST",
            data: {
                id_indikator: res.id_indikator,
                cells: JSON.stringify(res.autofill_cells)
            },
            dataType: "json",
            success: function(syncRes) {
                if (syncRes && syncRes.status === 'success') {
                    verify_tabel(window.currentTableNumber, window.currentTolerance || 0);
                } else {
                    alert("Gagal menyimpan nilai: " + (syncRes.message || 'Unknown error'));
                    $btn.prop('disabled', false).html('<i class="bi bi-cloud-download-fill me-1"></i> Isi Otomatis ke Tabel Dinamis');
                }
            },
            error: function() {
                alert("Gagal menghubungi server saat menyimpan nilai Tabel Dinamis.");
                $btn.prop('disabled', false).html('<i class="bi bi-cloud-download-fill me-1"></i> Isi Otomatis ke Tabel Dinamis');
            }
        });
    };

    window.syncAllDiffsToDinamis = function() {
        let res = window.lastVerifyResult;
        if (!res || !res.id_indikator || !res.diffs) return;
        let cells = [];
        res.diffs.forEach(d => {
            if (d.coord) {
                cells.push({
                    id_baris_item: d.coord.id_baris_item,
                    id_karakteristik_item: d.coord.id_karakteristik_item,
                    id_periode_item: d.coord.id_periode_item,
                    nilai: d.pdf_val
                });
            }
        });
        if (cells.length === 0) return;
        if (!confirm(`Perbarui ${cells.length} sel yang selisih pada Tabel ${window.currentTableNumber} agar sama dengan angka di buku PDF?`)) return;

        $.ajax({
            url: "<?= base_url('admin/sync_pdf_to_dinamis') ?>",
            type: "POST",
            data: {
                id_indikator: res.id_indikator,
                cells: JSON.stringify(cells)
            },
            dataType: "json",
            success: function(syncRes) {
                if (syncRes && syncRes.status === 'success') {
                    verify_tabel(window.currentTableNumber, window.currentTolerance || 0);
                } else {
                    alert("Gagal menyinkronkan selisih: " + (syncRes.message || 'Unknown error'));
                }
            }
        });
    };

    window.syncSingleDiffCell = function(idIndikator, coord, pdfVal, btnEl) {
        let $btn = $(btnEl);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');
        $.ajax({
            url: "<?= base_url('admin/sync_pdf_to_dinamis') ?>",
            type: "POST",
            data: {
                id_indikator: idIndikator,
                cells: JSON.stringify([{
                    id_baris_item: coord.id_baris_item,
                    id_karakteristik_item: coord.id_karakteristik_item,
                    id_periode_item: coord.id_periode_item,
                    nilai: pdfVal
                }])
            },
            dataType: "json",
            success: function(syncRes) {
                if (syncRes && syncRes.status === 'success') {
                    verify_tabel(window.currentTableNumber, window.currentTolerance || 0);
                } else {
                    alert("Gagal menyimpan sel: " + (syncRes.message || 'Unknown error'));
                    $btn.prop('disabled', false).text('Gunakan Angka PDF');
                }
            }
        });
    };

    function verify_tabel(nomor_tabel, tolerance = 0) {
        window.currentTableNumber = nomor_tabel;
        window.currentTolerance = tolerance;

        let modalEl = document.getElementById('modalDiff');
        let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        if (!modalEl.classList.contains('show')) {
            modal.show();
        }
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

                    window.currentDiffFilter = totalDiffs > 0 ? 'diff' : (totalMatches > 0 ? 'match' : (totalPdfOnly > 0 ? 'pdf_only' : 'all'));

                    // Update main list badge & counters
                    let status = (totalDiffs > 0 || totalMatches === 0) ? 'diff' : 'match';
                    let diffCount = totalDiffs > 0 ? totalDiffs : (totalPdfOnly > 0 ? totalPdfOnly : '!');
                    window.batchStatusMap[nomor_tabel] = {
                        status: status,
                        diff_count: diffCount,
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

                    window.lastVerifyResult = res;
                    let firstPage = (res.pages && res.pages.length > 0) ? res.pages[0] : 1;
                    let pagesLabel = (res.pages && res.pages.length > 0) ? `Hal. ${res.pages.join(', ')}` : '';
                    let syncAllDiffsBtn = '';
                    if (res.source_type === 'Tabel Dinamis' && res.id_indikator && res.diffs && res.diffs.some(d => d.coord)) {
                        syncAllDiffsBtn = `
                            <button type="button" onclick="syncAllDiffsToDinamis()" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 py-2 shadow-xs" title="Perbarui seluruh sel yang selisih di Tabel Dinamis menggunakan angka dari PDF">
                                <i class="bi bi-magic me-1"></i> Samakan Semua Selisih ke Tabel Dinamis
                            </button>`;
                    }

                    let html = `
                        <!-- Header Box -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                            <div>
                                <h5 class="fw-black text-dark mb-1">
                                    <i class="bi bi-table text-primary me-2"></i>Tabel ${nomor_tabel}
                                    ${pagesLabel ? `<span class="badge bg-light text-secondary border ms-2 fw-normal" style="font-size:0.78rem;"><i class="bi bi-book me-1"></i>PDF ${pagesLabel}</span>` : ''}
                                </h5>
                                <div class="text-muted small">
                                    Hasil pemindaian langsung (live scan) tabel ${nomor_tabel} antara cetakan buku PDF dengan database resmi.
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fs-6 fw-bold">
                                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Sumber: ${res.source_type}
                                </span>
                                <button type="button" onclick="togglePdfSplitView(${firstPage})" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 py-2 shadow-xs">
                                    <i class="bi bi-layout-split me-1"></i> Sandingkan PDF Asli (${pagesLabel || 'PDF'})
                                </button>
                                ${syncAllDiffsBtn}
                                <a href="${exportUrl}" target="_blank" class="btn btn-sm btn-success fw-bold rounded-pill px-3 py-2 shadow-xs">
                                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Unduh Laporan Validasi (.xlsx)
                                </a>
                            </div>
                        </div>
                        <div class="row g-3" id="modalSplitWrapper">
                            <div class="col-lg-6 d-none" id="pdfSplitPane">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> Cetakan Buku PDF Asli (${pagesLabel})</span>
                                        <button type="button" class="btn btn-xs btn-outline-light py-0 px-2" onclick="togglePdfSplitView(${firstPage})"><i class="bi bi-x-lg"></i> Tutup Preview</button>
                                    </div>
                                    <div class="card-body p-0">
                                        <iframe id="pdfSplitIframe" src="" style="width:100%; height:68vh; border:0;"></iframe>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12" id="auditMainPane">
                    `;
                    
                    // Banner Verifikasi Bersih Ringkas (Jika 100% Cocok Sempurna)
                    // Banner Verifikasi Bersih Ringkas (Jika 100% Cocok Sempurna)
                    if (totalMatches > 0 && totalDiffs === 0 && totalPdfOnly === 0) {
                        html += `
                            <div class="alert alert-success border-success-subtle bg-success-subtle rounded-3 p-3 mb-3 shadow-xs d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-left: 5px solid #10b981 !important;">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center me-3 shadow-xs flex-shrink-0" style="width: 40px; height: 40px;">
                                        <i class="bi bi-check-lg fw-bold" style="font-size: 1.4rem;"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-success-emphasis mb-0" style="font-size: 0.95rem;">100% Cocok Sempurna</h6>
                                        <div class="text-secondary small">
                                            Seluruh data (<strong>${totalMatches} angka</strong>) pada cetakan PDF sama persis dengan database resmi tanpa selisih.
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-success border border-success-subtle px-3 py-1-5 rounded-pill fw-semibold shadow-xs" style="font-size: 0.78rem;">
                                        <i class="bi bi-shield-check me-1"></i> Terverifikasi Bersih
                                    </span>
                                </div>
                            </div>
                        `;
                    } else if (totalMatches === 0 && totalPdfOnly > 0) {
                        html += `
                            <div class="alert alert-warning border-warning-subtle bg-warning-subtle rounded-3 p-3 mb-3 shadow-xs d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-left: 5px solid #f59e0b !important;">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center me-3 shadow-xs flex-shrink-0" style="width: 40px; height: 40px;">
                                        <i class="bi bi-exclamation-triangle-fill fw-bold" style="font-size: 1.3rem;"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-warning-emphasis mb-0" style="font-size: 0.95rem;">Perbedaan Struktur Data (Khusus Cetakan PDF)</h6>
                                        <div class="text-secondary small">
                                            Tercetak <strong>${totalPdfOnly} data</strong> pada cetakan PDF, namun barisnya tidak ditemukan di database resmi.
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-dark border border-warning-subtle px-3 py-1-5 rounded-pill fw-semibold shadow-xs" style="font-size: 0.78rem;">
                                        <i class="bi bi-journal-bookmark me-1"></i> ${totalPdfOnly} Cuma di PDF
                                    </span>
                                </div>
                            </div>
                        `;
                    }

                    // 1. KPI Cards (Selalu ditampilkan untuk status cocok maupun beda nilai)
                    let isPerfectMatch = (totalMatches > 0 && totalDiffs === 0 && totalPdfOnly === 0);
                    html += `
                        <div class="row g-3 mb-4">
                            <!-- KPI: Beda Nilai -->
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border ${totalDiffs > 0 ? 'bg-danger-subtle border-danger' : 'bg-light'} d-flex align-items-center shadow-xs" 
                                     ${totalDiffs > 0 ? `onclick="switchDiffFilter('diff')" style="cursor:pointer;" title="Klik untuk menyaring data yang beda"` : 'style="opacity: 0.75;"'}>
                                    <div class="rounded-circle p-2 ${totalDiffs > 0 ? 'bg-danger text-white' : 'bg-secondary text-white'} me-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                                        <i class="bi ${totalDiffs > 0 ? 'bi-exclamation-octagon-fill' : 'bi-dash-circle'} fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-black fs-4 ${totalDiffs > 0 ? 'text-danger' : 'text-muted'} mb-0">${totalDiffs} Angka</div>
                                        <div class="small fw-semibold ${totalDiffs > 0 ? 'text-danger' : 'text-muted'}">Beda Nilai ${totalDiffs > 0 ? '(Perlu Dicek)' : '(Nihil)'}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- KPI: Cocok Sempurna (Highlight Spesial Saat 100% Cocok) -->
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border d-flex align-items-center shadow-xs ${isPerfectMatch ? 'border-success' : (totalMatches > 0 ? 'border-success bg-success-subtle' : 'bg-light border-secondary-subtle')}" 
                                     ${totalMatches > 0 ? `onclick="switchDiffFilter('match')" style="cursor:pointer;"` : 'style="opacity: 0.75;"'}
                                     ${isPerfectMatch ? 'style="cursor:pointer; border: 2px solid #10b981 !important; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important; box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.25) !important;"' : ''} 
                                     title="Klik untuk menyaring data yang cocok">
                                    <div class="rounded-circle p-2 ${totalMatches > 0 ? 'bg-success text-white' : 'bg-secondary text-white'} me-3 d-flex align-items-center justify-content-center shadow-xs" style="width:48px; height:48px;">
                                        <i class="bi bi-check-lg fs-3 fw-bold"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="fw-black fs-4 ${totalMatches > 0 ? 'text-success' : 'text-muted'} mb-0">${totalMatches} Angka</div>
                                            ${isPerfectMatch ? `<span class="badge bg-success text-white px-2 py-1 rounded-pill fw-bold shadow-xs" style="font-size:0.68rem;"><i class="bi bi-stars me-1"></i>Cocok</span>` : ''}
                                        </div>
                                        <div class="small ${totalMatches > 0 ? 'text-success' : 'text-muted'} fw-semibold">Cocok Sempurna ${isPerfectMatch ? '(100%)' : ''}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- KPI: Khusus PDF -->
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border ${totalPdfOnly > 0 ? 'bg-warning-subtle border-warning' : 'bg-light'} d-flex align-items-center shadow-xs" 
                                     ${totalPdfOnly > 0 ? `onclick="switchDiffFilter('pdf_only')" style="cursor:pointer;" title="Klik untuk menyaring data khusus cetakan PDF"` : 'style="opacity: 0.75;"'}>
                                    <div class="rounded-circle p-2 ${totalPdfOnly > 0 ? 'bg-warning-emphasis text-white' : 'bg-secondary text-white'} me-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                                        <i class="bi bi-journal-bookmark-fill fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-black fs-4 ${totalPdfOnly > 0 ? 'text-warning-emphasis' : 'text-muted'} mb-0">${totalPdfOnly} Info</div>
                                        <div class="small ${totalPdfOnly > 0 ? 'text-warning-emphasis' : 'text-muted'} fw-semibold">Khusus Cetakan PDF</div>
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
                                        ${totalDiffs > 0 ? `<button type="button" class="btn btn-danger active btn-filter-type fw-bold px-3 py-1" data-filter="diff" onclick="setDiffType('diff', this)"><i class="bi bi-exclamation-octagon-fill me-1"></i> Beda Nilai (${totalDiffs})</button>` : ''}
                                        <button type="button" class="btn ${totalDiffs === 0 && totalMatches > 0 ? 'btn-success active text-white shadow-xs' : 'btn-outline-secondary'} btn-filter-type fw-bold px-3 py-1" data-filter="match" onclick="setDiffType('match', this)"><i class="bi bi-check-circle-fill me-1"></i> Cocok (${totalMatches})</button>
                                        ${totalPdfOnly > 0 ? `<button type="button" class="btn ${totalDiffs === 0 && totalMatches === 0 ? 'btn-warning active text-dark fw-bold shadow-xs' : 'btn-outline-secondary'} btn-filter-type px-3 py-1" data-filter="pdf_only" onclick="setDiffType('pdf_only', this)"><i class="bi bi-journal-bookmark me-1"></i> Cuma di PDF (${totalPdfOnly})</button>` : ''}
                                        <button type="button" class="btn btn-outline-secondary btn-filter-type fw-bold px-3 py-1" data-filter="all" onclick="setDiffType('all', this)">Semua (${totalAll})</button>
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
                                                        ${(res.id_indikator && d.coord) ? `
                                                            <button type="button" onclick='syncSingleDiffCell(${res.id_indikator}, ${JSON.stringify(d.coord)}, ${JSON.stringify(d.pdf_val)}, this)' class="btn btn-xs btn-danger text-white fw-bold rounded-pill mt-2 py-1 px-2 shadow-xs">
                                                                <i class="bi bi-magic me-1"></i> Gunakan Angka PDF (${d.pdf_val})
                                                            </button>
                                                        ` : ''}
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
                                    <div class="card mb-2 border border-success-subtle shadow-xs item-row" data-type="match" style="${defaultDisplay} border-left: 4px solid #10b981 !important;" data-wilayah="${m.wilayah.toLowerCase()}" data-metric="${m.metric.toLowerCase()}" data-section="${(m.section || '').toLowerCase()}">
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

                        html += `</div></div></div>`; // Close comparisonList, auditMainPane, modalSplitWrapper
                    $('#diffContent').html(html);
                    applyDiffFilters();
                } else {
                    let msg = res.message || 'Terjadi kesalahan saat memproses data';
                    let isApiEmpty = msg.toLowerCase().includes('tidak ada data dalam api') || msg.toLowerCase().includes('belum diisi') || res.is_api_empty;
                    
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
                        window.lastVerifyResult = res;
                        let firstPage = (res.pages && res.pages.length > 0) ? res.pages[0] : 1;
                        let pagesLabel = (res.pages && res.pages.length > 0) ? `Hal. ${res.pages.join(', ')}` : '';
                        let autofillBox = '';

                        if (res.can_autofill && res.autofill_cells && res.autofill_cells.length > 0 && res.id_indikator) {
                            let sampleRows = res.autofill_cells.slice(0, 12).map(c => `
                                <tr>
                                    <td class="fw-semibold text-dark">${c.wilayah || '-'}</td>
                                    <td class="text-secondary">${c.metric || '-'}</td>
                                    <td class="text-end fw-bold font-monospace text-success">${c.nilai}</td>
                                </tr>
                            `).join('');
                            autofillBox = `
                                <div class="card border-success bg-success-subtle bg-opacity-25 text-start mx-auto mt-4 shadow-sm" style="max-width: 780px;">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                            <div>
                                                <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold mb-1"><i class="bi bi-stars me-1"></i>AI Auto-Fill Tersedia</span>
                                                <h6 class="fw-bold text-dark mb-0">Ditemukan <strong>${res.autofill_count} sel angka</strong> di buku PDF (${pagesLabel}) yang cocok dengan struktur Tabel Dinamis ini!</h6>
                                            </div>
                                            <div class="d-flex gap-2">
                                                ${res.pages && res.pages.length > 0 ? `
                                                    <button type="button" onclick="togglePdfSplitView(${firstPage})" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">
                                                        <i class="bi bi-layout-split me-1"></i> Lihat PDF (${pagesLabel})
                                                    </button>
                                                ` : ''}
                                                <button type="button" id="btnAutofillFromPdf" onclick="syncAutofillFromPdf()" class="btn btn-sm btn-success fw-bold rounded-pill px-4 py-2 shadow-sm">
                                                    <i class="bi bi-cloud-download-fill me-1"></i> Isi Otomatis ke Tabel Dinamis (${res.autofill_count} Sel)
                                                </button>
                                            </div>
                                        </div>
                                        <div class="table-responsive bg-white rounded-3 border" style="max-height: 220px;">
                                            <table class="table table-sm table-hover mb-0 small">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th>Baris / Wilayah</th>
                                                        <th>Kolom / Karakteristik</th>
                                                        <th class="text-end">Nilai di PDF</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${sampleRows}
                                                    ${res.autofill_cells.length > 12 ? `<tr><td colspan="3" class="text-center text-muted py-2">... dan ${res.autofill_cells.length - 12} sel angka lainnya siap disimpan</td></tr>` : ''}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else if (res.id_db && res.source_type === 'Tabel Dinamis') {
                            autofillBox = `
                                <div class="mt-3 d-flex justify-content-center gap-2">
                                    ${res.pages && res.pages.length > 0 ? `
                                        <button type="button" onclick="togglePdfSplitView(${firstPage})" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">
                                            <i class="bi bi-layout-split me-1"></i> Lihat PDF Asli (${pagesLabel})
                                        </button>
                                    ` : ''}
                                    <a href="<?= base_url('admin/view_tabel_dinamis') ?>/${res.id_db}" target="_blank" class="btn btn-sm btn-primary fw-bold rounded-pill px-3">
                                        <i class="bi bi-pencil-square me-1"></i> Buka Input Tabel Dinamis
                                    </a>
                                    <a href="<?= base_url('admin/generator_tabel') ?>/${res.id_db}" target="_blank" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3">
                                        <i class="bi bi-sliders2-vertical me-1"></i> Cek Konfigurasi Baris/Kolom
                                    </a>
                                </div>
                            `;
                        }

                        $('#diffContent').html(`
                            <div class="row g-3" id="modalSplitWrapper">
                                <div class="col-lg-6 d-none" id="pdfSplitPane">
                                    <div class="card border shadow-xs h-100">
                                        <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> Cetakan Buku PDF Asli (${pagesLabel})</span>
                                            <button type="button" class="btn btn-xs btn-outline-light py-0 px-2" onclick="togglePdfSplitView(${firstPage})"><i class="bi bi-x-lg"></i> Tutup Preview</button>
                                        </div>
                                        <div class="card-body p-0">
                                            <iframe id="pdfSplitIframe" src="" style="width:100%; height:68vh; border:0;"></iframe>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12" id="auditMainPane">
                                    <div class="text-center py-4">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle mb-3 shadow-xs" style="width: 68px; height: 68px;">
                                            <i class="bi bi-database-slash" style="font-size: 2rem;"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">Data Kosong (${res.source_type || 'Database'})</h5>
                                        <p class="text-muted small mx-auto mb-2" style="max-width: 520px;">
                                            ${res.message || 'Sumber database belum memuat nilai untuk tabel ini.'}
                                        </p>
                                        ${autofillBox}
                                    </div>
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

            renderUnregList(unregList || []);

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
