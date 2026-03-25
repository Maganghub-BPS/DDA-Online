<style>
    .sync-card { border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); }
    .btn-sync-action { border-radius: 8px; font-weight: 700; padding: 8px 12px; width: 100%; transition: all 0.2s ease; font-size: 0.8rem; margin-bottom: 5px; }
    .btn-sync-action:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .progress { height: 8px; border-radius: 10px; background-color: #f1f3f5; overflow: hidden; margin-bottom: 5px; }
    #progress { transition: width 0.4s ease; border-radius: 10px; }
    #log { 
        height: 250px; 
        overflow-y: auto; 
        background: #1e1e1e; 
        border-radius: 10px; 
        padding: 12px; 
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace; 
        font-size: 0.7rem; 
        border: 1px solid #333;
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.4);
    }
    .log-item { border-bottom: 1px solid #2d2d2d; padding: 4px 0; line-height: 1.4; color: #d4d4d4; }
    .log-item:last-child { border-bottom: none; }
    .log-time { color: #dcdcaa; font-weight: bold; margin-right: 6px; }
    .log-msg-success { color: #b5cea8; } 
    .log-msg-error { color: #f44747; font-weight: bold; }
    .log-msg-info { color: #4fc1ff; }
    .log-msg-neon { color: #FF69B4; font-weight: bold; } /* Mencolok: Hot Pink */
    
    .desc-text { font-size: 0.72rem; line-height: 1.3; color: #6c757d; margin-bottom: 12px; }
    .section-title { font-size: 0.65rem; font-weight: 800; color: #adb5bd; text-transform: uppercase; letter-spacing: 0.1em; display: block; margin-bottom: 10px; }
    
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: #1e1e1e; }
    ::-webkit-scrollbar-thumb { background: #444; border-radius: 10px; }
</style>

<div class="row mb-2 mt-1">
    <div class="col-lg-12">
        <div class="d-flex align-items-center mb-1">
            <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-arrow-repeat" style="font-size: 1rem;"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">Sinkronisasi & Matching Data</h5>
                <p class="text-muted" style="font-size: 0.75rem; margin-top: -2px; margin-bottom: 0;">Integrasi DDA Online & Portal Jawa Tengah</p>
            </div>
        </div>
        <hr class="opacity-5 my-2">
    </div>
</div>

<?php echo session()->getFlashdata("k"); ?>

<div class="row g-3">
    <!-- PANEL KIRI: TOMBOL AKSI -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm sync-card bg-white">
            <div class="card-body p-3">
                <span class="section-title">Aksi Prioritas</span>
                
                <div class="mb-3">
                    <button class="btn btn-warning text-white btn-sync-action d-flex align-items-center justify-content-center" onclick="ambilData()" style="background: linear-gradient(135deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-cloud-download me-2"></i> 1. AMBIL DATA API
                    </button>
                    <p class="desc-text px-1">Ambil metadata tabel terbaru dari Portal Jateng.</p>
                </div>
                
                <div class="mb-3 pt-1">
                    <a href="<?= site_url('admin/ambil_tahun_terakhir_dari_match') ?>"
                       class="btn btn-outline-primary btn-sync-action d-flex align-items-center justify-content-center"
                       onclick="return confirm('Update tabel match?')">
                        <i class="bi bi-database-fill-gear me-2"></i> 2. UPDATE TABEL MATCH
                    </a>
                    <p class="desc-text px-1">Sesuaikan status periode data portal dengan lokal.</p>
                </div>

                <div class="pt-2 border-top border-light">
                    <button id="btnSync" onclick="syncData()" class="btn btn-success btn-sync-action d-flex align-items-center justify-content-center py-2 mt-2" style="background: #198754; border: none;">
                        <i class="bi bi-play-circle-fill me-2"></i> 3. JALANKAN SYNC
                    </button>
                    <p class="desc-text px-1">Pemrosesan massal status sinkronisasi data.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- PANEL KANAN: MONITORING (LIGHT CARD WITH DARK TERMINAL) -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm sync-card p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="section-title mb-0"><i class="bi bi-terminal-fill me-1 text-dark"></i> Live System Monitor</span>
                <span id="badgeStatus" class="badge bg-light text-dark shadow-none px-2 py-1 rounded-3" style="font-size: 9px; font-weight: 800; border: 1px solid #eee;">STANDBY</span>
            </div>

            <div id="log">
                <div class="text-center py-5 opacity-50 text-light mt-2">
                    <i class="bi bi-reception-3 d-block mb-1" style="font-size: 1.5rem;"></i>
                    <span class="small font-monospace">READY_FOR_COMMAND...</span>
                </div>
            </div>

            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark" style="font-size: 10px;">PROGRESS BATCH</span>
                    <span id="percentText" class="fw-bold text-primary" style="font-size: 10px;">0%</span>
                </div>
                <div class="progress">
                    <div id="progress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function syncData(page = 1) {
    if (page === 1) {
        $('#log').empty();
        $('#btnSync').attr('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>INIT...');
        $('#badgeStatus').addClass('bg-primary text-white border-primary').text('SYNCING');
    }

    $.get('<?= site_url("admin/ambil_databatch") ?>?page=' + page, function(res) {
        if (res.status) {
            const time = new Date().toLocaleTimeString();
            $('#log').prepend('<div class="log-item"><span class="log-time">[' + time + ']</span> <span class="log-msg-success">' + res.message + '</span></div>');
            let percent = Math.round((res.current_page / res.total_page) * 100);
            $('#progress').css('width', percent + '%');
            $('#percentText').text(percent + '%');

            if (res.has_next) {
                syncData(res.next_page);
            } else {
                $('#log').prepend('<div class="log-item py-1 fw-bold border-top border-secondary mt-1"><span class="log-msg-neon"><i class="bi bi-check-all me-1"></i> FINALIZED: PROCESS COMPLETED.</span></div>');
                $('#btnSync').attr('disabled', false).html('<i class="bi bi-play-circle-fill me-2"></i> JALANKAN SYNC');
                $('#badgeStatus').removeClass('bg-primary').addClass('bg-success text-white border-success').text('COMPLETED');
            }
        } else {
            $('#log').prepend('<div class="log-item log-msg-error"><i class="bi bi-bug-fill me-1"></i> FAILED: ' + res.message + '</div>');
            $('#btnSync').attr('disabled', false).html('<i class="bi bi-play-circle-fill me-2"></i> JALANKAN SYNC');
            $('#badgeStatus').removeClass('bg-primary bg-success').addClass('bg-danger text-white border-danger').text('ERROR');
        }
    }, 'json').fail(function(xhr) {
        $('#log').prepend('<div class="log-item log-msg-error"><i class="bi bi-wifi-off me-1"></i> CONN_ERROR: Lost connection to server.</div>');
        $('#btnSync').attr('disabled', false).html('<i class="bi bi-play-circle-fill me-2"></i> JALANKAN SYNC');
    });
}

function ambilData() {
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    $(btn).attr('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>FETCHING...');
    
    fetch('<?= site_url("admin/ambil_data") ?>', { method: 'GET' })
    .then(async res => {
        const text = await res.text();
        try {
            const json = JSON.parse(text);
            if (!res.ok || json.status === false) throw json;
            alert('Sukses: ' + json.message);
        } catch (e) {
            alert('Port Response:\n' + text);
        }
    })
    .catch(err => alert('Runtime Error: ' + err))
    .finally(() => {
        $(btn).attr('disabled', false).html(originalText);
    });
}
</script>
