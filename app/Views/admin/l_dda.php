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
        $tabel_url = $b->link_tabel;
        if (stripos($tabel_url, 'http') !== 0 && !empty($tabel_url)) {
            $tabel_url = base_url() . $tabel_url;
        }
        ?>
        <a href="<?php echo $tabel_url; ?>" target="_blank" class="text-decoration-none tabel-judul">
            <?php echo htmlspecialchars($judul_bersih); ?>
        </a>
    </td>

    <!-- STATUS PENGISIAN -->
    <td>
        <?php 
        $is_portal = (stripos($b->link_tabel, 'portal') !== false || 
                      stripos($b->link_tabel, 'satudata') !== false || 
                      (isset($b->id_api) && !empty($b->id_api)));

        if ($b->is_periksa == '1' || $is_portal) { ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Sudah Diisi</span>
            
            <div class="mt-2 text-start">
                <small class="text-muted d-block fst-italic">
                    <?php if($is_portal): ?>
                        <i class="bi bi-cloud-check-fill me-1 text-primary"></i>Sudah sinkron dengan portal data Jateng
                    <?php else: ?>
                        <?php echo (!empty($b->catatan_periksa) && $b->catatan_periksa != '-') ? 'Catatan: '.htmlspecialchars($b->catatan_periksa) : 'Input Manual/Sheet'; ?>
                    <?php endif; ?>
                </small>
                <?php if($is_portal): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size: 9px;">SUMBER: PORTAL DATA JATENG</span>
                <?php endif; ?>
            </div>
            
            <?php if(!$is_portal): ?>
            <div class="mt-2 text-end">
                <a href="<?php echo base_url(); ?>index.php/admin/batal_isi/<?php echo $b->id; ?>" 
                class="btn btn-sm btn-outline-danger py-0" style="font-size: 0.7rem;" onclick="return confirm('Batalkan status?')">
                    <i class="bi bi-x-circle me-1"></i>Batal
                </a>
            </div>
            <?php endif; ?>
        <?php } else { ?>
            <div class="d-flex flex-column gap-2">
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 align-self-start"><i class="bi bi-clock me-1"></i>Belum Diisi</span>
                <a href="javascript:;"
                   class="btn btn-sm btn-primary btn-isi-data shadow-sm align-self-start border-0" style="background: #FF6D1F;"
                   data-id="<?php echo $b->id; ?>"
                   data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                   data-unit="<?php echo htmlspecialchars($b->id_unitkerja, ENT_QUOTES); ?>">
                    <i class="bi bi-pencil-square me-1"></i>Update Status
                </a>
            </div>
        <?php } ?>
    </td>

    <!-- STATUS PEMERIKSAAN -->
    <td>
        <?php
        if ($b->is_confirm == '2') {
            echo '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-hourglass-split me-1"></i>Belum Dicek</span>';
        } else {
            echo '<span class="badge bg-info-subtle text-info"><i class="bi bi-check2-all me-1"></i>Sudah Dicek</span>';
            if(!empty($b->catatan)) {
                echo '<br><small class="text-muted">' . htmlspecialchars($b->catatan) . '</small>';
            }
        }

        if(session()->get('admin_unitkerja') == 'bps' and $b->is_confirm=='2') {
            $id_konfirm = $b->id;
        ?>
            <div class="mt-1">
                <a href="#" class="konfirmasi_modal btn btn-sm btn-outline-info" style="font-size: 0.75rem; padding: 3px 10px;" id="<?php echo $id_konfirm; ?>" title="Cek Data">
                    <i class="bi bi-clipboard-check me-1"></i>Cek Data
                </a>
            </div>
        <?php
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
