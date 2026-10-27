<?php
    if(segment_safe(3) == null) {
        $tab = 1;
    } else {
        $tab = segment_safe(3);
    }
    $username = session()->get('admin_user');
?>

<style>
.search-tabel-container {
    padding: 16px 20px;
    background: #fff;
    border-radius: 12px;
    margin-bottom: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.search-tabel-container .input-group {
    max-width: 550px;
}
.search-tabel-info {
    margin-top: 8px;
    font-size: 13px;
    color: #64748b;
    display: none;
}
.search-tabel-info.active {
    display: block;
}
.no-result-tabel {
    display: none;
}
.no-result-tabel.active {
    display: table-row;
}
.no-result-tabel td {
    text-align: center;
    font-weight: bold;
    color: #ef4444;
    padding: 20px !important;
}
#tableTabel thead th { font-size: 0.85rem; text-transform: uppercase; background-color: #f1f3f5; color: #495057; font-weight: 700; border-bottom: 2px solid #dee2e6; }
.tabel-pemeriksaan-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.tabel-judul-cell { min-width: 300px; white-space: normal !important; word-wrap: break-word; }
@media (max-width: 991.98px) { .tabel-judul-cell { min-width: 250px; } }
</style>

<?php echo session()->getFlashdata("k"); ?>

<!-- Toolbar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-journal-text me-2 text-primary"></i>Tabel Jawa Tengah Dalam Angka
            </h5>
            <?php if(session()->get('admin_unitkerja') != 'bps' || session()->get('admin_unitkerja') != ''): ?>
            <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/add" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Tabel
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="search-tabel-container">
    <form method="GET" action="<?php 
        $current_url = current_url(true);
        echo $current_url;
    ?>">
    <?php 
        foreach($_GET as $key => $val) {
            if($key != 'filter_tahun' && $key != 'action') {
                echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'">';
            }
        }
    ?>
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="searchTabel" placeholder="Cari judul tabel (otomatis)..." autocomplete="off">
            </div>
            <div class="search-tabel-info" id="searchTabelInfo">
                <i class="bi bi-info-circle me-1"></i>Menampilkan <strong id="searchTabelCount" class="text-primary">0</strong> dari <strong id="totalTabelCount">0</strong> tabel
            </div>
        </div>

        <div class="col-sm-3 col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                <select name="filter_tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?php echo (isset($selected_tahun) && $selected_tahun == 'all') ? 'selected' : ''; ?>>-- Semua Tahun --</option>
                    <?php 
                    for ($i = 2020; $i <= (date('Y')+1); $i++) {
                        $sel = (isset($selected_tahun) && $selected_tahun == $i) ? 'selected' : '';
                        echo "<option value='$i' $sel>$i</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <?php if(isset($selected_tahun) && $selected_tahun != 'all'): ?>
        <div class="col-sm-2">
            <a href="<?php 
                $reset_params = $_GET;
                unset($reset_params['filter_tahun']);
                $reset_params['action'] = 'reset_dda';
                echo site_url('admin/dda?' . http_build_query($reset_params));
            ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
        </div>
        <?php endif; ?>
    </div>
    </form>
</div>

<!-- Data Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableTabel">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No.</th>
                        <th width="45%">Judul</th>
                        <th width="20%">Status Pengisian</th>
                        <th width="20%">Status Pemeriksaan</th>
                        <th width="10%" class="text-center">Konsep</th>
                    </tr>
                </thead>
                <tbody>
<?php 
if (empty($data)) {
    echo "<tr><td colspan='5' class='text-center py-4 text-muted'><i class='bi bi-inbox me-2'></i>Tabel tidak ditemukan</td></tr>";
} else {
    $no = 1;
    foreach ($data as $b) {
        $judul_bersih = preg_replace("/\r|\n/", " ", (!empty($b->no_tabel) ? $b->no_tabel . " " : "") . format_judul_tabel($b->judul_ind, $b->periode_id ?? $b->periode ?? ""));
?>
<tr class="tabel-row">
    <td class="text-center tabel-row-number fw-semi-bold text-muted"><?php echo $no++; ?></td>

    <td class="tabel-judul-cell">
        <?php 
        $is_dynamic = !empty($b->is_dynamic);
        $is_portal  = (stripos($b->link_tabel, 'portal') !== false || 
                       stripos($b->link_tabel, 'satudata') !== false || 
                       (isset($b->id_api) && !empty($b->id_api)));

        if ($is_dynamic) {
            $tabel_url = base_url('admin/view_tabel_dinamis/' . $b->id);
            $target = '';
        } elseif ($is_portal) {
            $tabel_url = $b->link_tabel;
            if (stripos($tabel_url, 'http') !== 0 && !empty($tabel_url)) {
                $tabel_url = base_url() . $tabel_url;
            }
            $target = '';
        } else {
            $tabel_url = $b->link_tabel;
            if (stripos($tabel_url, 'http') !== 0 && !empty($tabel_url)) {
                $tabel_url = base_url() . $tabel_url;
            }
            $target = 'target="_blank"';
        }
        ?>
        <a href="<?php echo $tabel_url; ?>" <?php echo $target; ?> class="text-decoration-none tabel-judul">
            <?php echo htmlspecialchars($judul_bersih); ?>
        </a>
        <?php if ($is_dynamic && session()->get('admin_unitkerja') == 'bps'): ?>
            <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                <a href="<?php echo base_url('admin/generator_tabel/' . $b->id); ?>" class="btn btn-xs btn-outline-primary py-0 px-2 rounded-pill text-xxs d-inline-flex align-items-center gap-1 shadow-xs fw-semibold" style="font-size: 10px;" title="Kelola Dimensi Generator">
                    <i class="bi bi-sliders"></i> Dimensi
                </a>
            </div>
        <?php endif; ?>
    </td>

    <!-- STATUS PENGISIAN -->
    <td>
        <?php 
        $is_portal = (stripos($b->link_tabel, 'portal') !== false || 
                      stripos($b->link_tabel, 'satudata') !== false || 
                      (isset($b->id_api) && !empty($b->id_api)));
        $is_dynamic_uploaded = ($is_dynamic && !empty($b->catatan_periksa) && preg_match('/Diunggah(?:\s+via\s+Excel)?\s+oleh\s+(.+?)\s+pada\s+([\d\/\-\:\s]+)/iu', $b->catatan_periksa, $m));
        $is_sudah_diisi = ($is_portal) || ($is_dynamic ? ($b->is_periksa == '1' && $is_dynamic_uploaded) : ($b->is_periksa == '1'));

        if ($is_sudah_diisi) { ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Sudah Diisi</span>
            
            <div class="mt-2 text-start">
                <small class="text-muted d-block fst-italic">
                    <?php if($is_portal): ?>
                        <i class="bi bi-cloud-check-fill me-1 text-primary"></i>Sudah sinkron dengan portal data Jateng
                    <?php elseif($is_dynamic && $is_dynamic_uploaded): ?>
                        <?php
                        $uploader = trim($m[1]);
                        $waktu    = trim($m[2]);
                        ?>
                        <span class="d-block text-dark fw-semibold" style="font-style: normal; font-size: 0.77rem; line-height: 1.35;">
                            <i class="bi bi-file-earmark-excel-fill text-success me-1"></i>Diunggah oleh <?= htmlspecialchars($uploader); ?>
                        </span>
                        <span class="d-block text-muted mt-1" style="font-style: normal; font-size: 0.72rem;">
                            <i class="bi bi-clock me-1 text-secondary"></i>pada <?= htmlspecialchars($waktu); ?>
                        </span>
                    <?php else: ?>
                        <?php echo (!empty($b->catatan_periksa) && $b->catatan_periksa != '-') ? 'Catatan: '.htmlspecialchars($b->catatan_periksa) : 'Input Manual/Sheet'; ?>
                    <?php endif; ?>
                </small>
                <?php if($is_portal): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size: 9px;">SUMBER: PORTAL DATA JATENG</span>
                <?php elseif($is_dynamic): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size: 9px;"><i class="bi bi-boxes me-1"></i>TABEL DINAMIS</span>
                <?php endif; ?>
            </div>
            
            <?php if ($is_dynamic): ?>
                <div class="mt-2 text-start">
                    <?php if (!empty($b->catatan) && $b->catatan != '-'): ?>
                        <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-2.5 btn-lihat-catatan shadow-xs fw-semibold" 
                                data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                                data-catatan="<?php echo htmlspecialchars($b->catatan, ENT_QUOTES); ?>"
                                style="font-size: 0.74rem; border-radius: 8px;">
                            <i class="bi bi-chat-left-quote-fill me-1"></i> Catatan <span class="badge bg-danger text-white ms-1" style="font-size: 9px;">Ada Catatan</span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 btn-lihat-catatan" 
                                data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                                data-catatan=""
                                style="font-size: 0.74rem; border-radius: 8px;">
                            <i class="bi bi-chat-left-dots me-1"></i> Catatan
                        </button>
                    <?php endif; ?>
                </div>
            <?php elseif (!$is_portal): ?>
                <div class="mt-2 text-end">
                    <a href="<?php echo base_url(); ?>admin/batal_isi/<?php echo $b->id; ?>" 
                    class="btn btn-sm btn-outline-danger py-0 btn-batal-isi" style="font-size: 0.7rem;"
                    data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                    title="Batalkan status">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </a>
                </div>
            <?php endif; ?>
        <?php } else { ?>
            <div class="d-flex flex-column gap-2">
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 align-self-start"><i class="bi bi-clock me-1"></i>Belum Diisi</span>
                <?php if($is_dynamic): ?>
                    <a href="<?php echo $tabel_url; ?>"
                       class="btn btn-sm btn-primary shadow-sm align-self-start border-0 text-white"
                       title="Buka Viewer Tabel Dinamis untuk Mengisi Data">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i>Isi Data
                    </a>
                <?php else: ?>
                    <a href="javascript:;"
                       class="btn btn-sm btn-primary btn-isi-data shadow-sm align-self-start border-0" style="background: #FF6D1F;"
                       data-id="<?php echo $b->id; ?>"
                       data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                       data-unit="<?php echo htmlspecialchars($b->id_unitkerja, ENT_QUOTES); ?>">
                        <i class="bi bi-pencil-square me-1"></i>Update Status
                    </a>
                <?php endif; ?>
            </div>
        <?php } ?>
    </td>

    <!-- STATUS PEMERIKSAAN -->
    <td>
        <?php
        if ($b->is_confirm != '1') {
            echo '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-hourglass-split me-1"></i>Belum Dicek</span>';
        } else {
            echo '<span class="badge bg-info-subtle text-info"><i class="bi bi-check2-all me-1"></i>Sudah Dicek</span>';
            if(!empty($b->catatan)) {
                echo '<br><small class="text-muted">' . htmlspecialchars($b->catatan) . '</small>';
            }
        }

        if(session()->get('admin_unitkerja') == 'bps') {
            if($b->is_confirm != '1') {
                $id_konfirm = $b->id;
        ?>
                <div class="mt-1">
                    <a href="#" class="konfirmasi_modal btn btn-sm btn-outline-info" style="font-size: 0.75rem; padding: 3px 10px;" id="<?php echo $id_konfirm; ?>" title="Cek Data">
                        <i class="bi bi-clipboard-check me-1"></i>Cek Data
                    </a>
                </div>
        <?php
            } else {
        ?>
                <div class="mt-1">
                    <a href="<?php echo base_url(); ?>admin/batal_verif/<?php echo $b->id; ?>" 
                       class="btn btn-sm btn-outline-danger border-0 bg-danger-subtle text-danger btn-batal-verif" 
                       style="font-size: 0.75rem; padding: 2px 8px;"
                       data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                       title="Batalkan Verifikasi">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Batal Cek
                    </a>
                </div>
        <?php
            }
        }
        ?>
    </td>

    <td class="text-center">
        <a href="<?php echo base_url(); ?>index.php/admin/dda/kondef?id=<?php echo $b->id; ?>" class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem; padding: 3px 10px;">
            <i class="bi bi-search"></i>
        </a>
    </td>
</tr>
<?php
    }
}
?>
                <tr class="no-result-tabel" id="noResultTabel">
                    <td colspan="5">-- Tidak ada tabel yang cocok dengan pencarian --</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Delete (BS5) -->
<div id="ModalDelete" class="modal fade" tabindex="-1" aria-hidden="true"></div>

<!-- Modal Edit (BS5) -->
<div id="ModalEdit" class="modal fade" tabindex="-1" aria-hidden="true"></div>

<!-- Modal Konfirmasi (BS5) -->
<div id="ModalKonfirmasi" class="modal fade" tabindex="-1" aria-hidden="true"></div>

<!-- MODAL FORM PENGISIAN (BS5) -->
<div id="ModalIsiData" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Update Status Pengisian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo base_url(); ?>index.php/admin/act_update_isi" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_tabel" id="id_tabel_modal">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Asal Data</label>
                        <input type="text" class="form-control" id="asal_data_modal" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Judul Tabel</label>
                        <input type="text" class="form-control" id="judul_tabel_modal" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Catatan</label>
                        <textarea name="catatan_periksa" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL LIHAT CATATAN DARI ADMIN/LO (BS5) -->
<div id="ModalLihatCatatanWalidata" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-light border-0 pt-3 px-4 pb-2">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center">
                    <i class="bi bi-chat-left-quote-fill text-warning me-2 fs-5"></i> Catatan BPS
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold mb-1">JUDUL TABEL</label>
                    <div id="lihat_catatan_judul" class="p-2 bg-light rounded text-dark small fw-medium" style="border: 1px solid #e9ecef;"></div>
                </div>
                <div>
                    <label class="form-label text-secondary small fw-bold mb-1">CATATAN BPS</label>
                    <div id="lihat_catatan_isi_box"></div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn btn-sm btn-secondary px-4 fw-medium" data-bs-dismiss="modal" style="border-radius: 8px;">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript: Modal handlers using BS5 native API -->
<script type="text/javascript">
$(document).ready(function () {
    // Delete modal
    $(".open_modal").click(function(e) {
        var m = $(this).attr("id");
        $.ajax({
            url: "<?php echo base_url(); ?>index.php/admin/dda/del/",
            type: "GET",
            data: {delete_id: m},
            success: function (ajaxData) {
                $("#ModalDelete").html(ajaxData);
                var bsModal = new bootstrap.Modal(document.getElementById('ModalDelete'));
                bsModal.show();
            }
        });
    });

    // View modal
    $(".view_modal").click(function(e) {
        var m = $(this).attr("id");
        $.ajax({
            url: "<?php echo base_url(); ?>index.php/admin/dda/in/",
            type: "GET",
            data: {in_id: m},
            success: function (ajaxData) {
                $("#ModalEdit").html(ajaxData);
                var bsModal = new bootstrap.Modal(document.getElementById('ModalEdit'));
                bsModal.show();
            }
        });
    });

    // Konfirmasi modal
    $(".konfirmasi_modal").click(function(e) {
        var m = $(this).attr("id");
        $.ajax({
            url: "<?php echo base_url(); ?>index.php/admin/dda/konfirmasi/",
            type: "GET",
            data: {konfirmasi_id: m},
            success: function (ajaxData) {
                $("#ModalKonfirmasi").html(ajaxData);
                var bsModal = new bootstrap.Modal(document.getElementById('ModalKonfirmasi'));
                bsModal.show();
            }
        });
    });

    // Update status modal (BS5)
    $(document).on('click', '.btn-isi-data', function () {
        $('#id_tabel_modal').val(this.dataset.id);
        $('#judul_tabel_modal').val(this.dataset.judul);
        $('#asal_data_modal').val(this.dataset.unit);
        var bsModal = new bootstrap.Modal(document.getElementById('ModalIsiData'));
        bsModal.show();
    });

    // Lihat Catatan Admin/LO oleh Walidata
    $(document).on('click', '.btn-lihat-catatan', function () {
        var judul = $(this).data('judul');
        var catatan = $(this).data('catatan');
        $('#lihat_catatan_judul').text(judul);

        if (catatan && String(catatan).trim() !== '' && String(catatan).trim() !== '-') {
            $('#lihat_catatan_isi_box').html(`
                <div class="p-3 rounded-3" style="background: #fff8e1; border: 1px solid #ffe082; border-left: 5px solid #ffb300;">
                    <div class="d-flex align-items-center mb-1 text-warning-emphasis fw-bold small">
                        <i class="bi bi-info-circle-fill me-1"></i> Arahan / Catatan Pemeriksaan:
                    </div>
                    <div class="text-dark" style="font-size: 0.88rem; line-height: 1.5; white-space: pre-wrap;">${catatan}</div>
                </div>
            `);
        } else {
            $('#lihat_catatan_isi_box').html(`
                <div class="p-4 rounded-3 text-center" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                    <i class="bi bi-chat-square-dots text-secondary mb-2" style="font-size: 2rem;"></i>
                    <div class="fw-bold text-dark mb-1">Belum Ada Catatan</div>
                    <div class="text-secondary small">Tabel ini belum memiliki catatan khusus atau instruksi pemeriksaan dari BPS.</div>
                </div>
            `);
        }

        var bsModal = new bootstrap.Modal(document.getElementById('ModalLihatCatatanWalidata'));
        bsModal.show();
    });
});
</script>

<!-- JavaScript untuk pencarian Judul Tabel -->
<script type="text/javascript">
$(document).ready(function() {
    var $searchInput = $('#searchTabel');
    var $rows = $('.tabel-row');
    var $searchInfo = $('#searchTabelInfo');
    var $searchCount = $('#searchTabelCount');
    var $totalCount = $('#totalTabelCount');
    var $noResult = $('#noResultTabel');
    var totalRows = $rows.length;
    
    $totalCount.text(totalRows);
    
    $searchInput.on('input', function() {
        var keyword = $(this).val().toLowerCase().trim();
        var visibleCount = 0;
        
        if (keyword === '') {
            $rows.show();
            $searchInfo.removeClass('active');
            $noResult.removeClass('active');
            var num = 1;
            $rows.each(function() {
                $(this).find('.tabel-row-number').text(num++);
            });
            return;
        }
        
        $searchInfo.addClass('active');
        var num = 1;
        
        $rows.each(function() {
            var judulTabel = $(this).find('.tabel-judul').text().toLowerCase();
            if (judulTabel.indexOf(keyword) > -1) {
                $(this).show();
                $(this).find('.tabel-row-number').text(num++);
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        $searchCount.text(visibleCount);
        
        if (visibleCount === 0) {
            $noResult.addClass('active');
        } else {
            $noResult.removeClass('active');
        }
    });
});
</script>
