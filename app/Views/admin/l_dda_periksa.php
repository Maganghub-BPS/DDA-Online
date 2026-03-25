<?php
	if(segment_safe(3) == null) {
		$tab = 1;
	} else {
		$tab = segment_safe(3);
	}
?>

<style>
    #tableTabelPeriksa thead th { 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em;
        background-color: #f8f9fa; 
        color: #6c757d; 
        font-weight: 700; 
        border-top: none;
        padding: 12px 15px;
    }
    .tabel-pemeriksaan-container { overflow-x: auto; }
    .tabel-judul-cell { min-width: 350px; white-space: normal !important; }
    .status-badge { font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 50rem; }
    .btn-action-sm { padding: 4px 12px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; }
    .status-container { display: flex; flex-direction: column; gap: 4px; }
    .note-text { font-size: 0.8rem; color: #6c757d; line-height: 1.4; }
    .table-hover tbody tr:hover { background-color: rgba(255, 109, 31, 0.03); transition: background-color 0.2s ease; }
    .tabel-periksa-row td { padding: 1.25rem 1rem; border-bottom: 1px solid #f1f3f5; }
    .action-group { margin-top: 10px; display: flex; align-items: center; gap: 8px; }
    .hover-primary:hover { color: #FF6D1F !important; }
    .badge { letter-spacing: 0.02em; }
    @media (max-width: 991.98px) { .tabel-judul-cell { min-width: 280px; } }
</style>

<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
            <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <a href="<?php echo base_url(); ?>admin/dda" class="btn btn-sm btn-light border-0 me-3 shadow-none bg-light-subtle" style="border-radius: 8px;"><i class="bi bi-arrow-left"></i></a>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.02em;">
                            Pemeriksaan Tabel DDA
                        </h5>
                        <p class="text-muted small mb-0">Verifikasi kelengkapan data sektoral</p>
                    </div>
                </div>
                <div class="input-group input-group-sm" style="max-width: 320px;">
                    <span class="input-group-text bg-light border-0 text-muted px-3" style="border-radius: 8px 0 0 8px;"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-0 ps-0 py-2" id="searchTabelPeriksa" placeholder="Cari judul tabel..." autocomplete="off" style="border-radius: 0 8px 8px 0;">
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo session()->getFlashdata("k");?>  

<div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableTabelPeriksa">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="45%">Informasi Tabel</th>
                        <th width="25%">Status Pengisian</th>
                        <th width="25%">Status Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'><i class='bi bi-info-circle me-2 d-block mb-3 display-6 opacity-25'></i>Tabel tidak ditemukan</td></tr>";
                    } else {
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                            $judul_bersih = preg_replace("/\r|\n/", " ", $b->judul_ind);
                            $is_portal = (stripos($b->link_tabel, 'portal') !== false || 
                                          stripos($b->link_tabel, 'satudata') !== false || 
                                          (isset($b->id_api) && !empty($b->id_api)));
                    ?>
                    <tr class="tabel-periksa-row" id="row-<?php echo $b->id; ?>">
                        <td class="text-center text-muted fw-bold row-no" style="font-size: 0.85rem;"><?php echo $no++; ?></td>
                        <td class="tabel-judul-cell">
                            <?php 
                            $tabel_url = $b->link_tabel;
                            if (stripos($tabel_url, 'http') !== 0 && !empty($tabel_url)) {
                                $tabel_url = base_url() . $tabel_url;
                            }
                            ?>
                            <div class="d-flex flex-column gap-1">
                                <a href="<?php echo $tabel_url; ?>" class="text-decoration-none fw-bold text-dark hover-primary" style="font-size: 0.95rem; line-height: 1.4;">
                                    <?php echo htmlspecialchars($judul_bersih); ?>
                                </a>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <?php if($is_portal): ?>
                                        <span class="badge bg-info-subtle text-info border-0 px-2 py-1" style="font-size: 10px; font-weight: 700;">
                                            <i class="bi bi-cloud-check-fill me-1"></i> PORTAL DATA
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border-0 px-2 py-1" style="font-size: 10px; font-weight: 700;">
                                            <i class="bi bi-file-spreadsheet me-1"></i> GOOGLE SHEET
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-muted" style="font-size: 10px;"><i class="bi bi-hash me-1"></i>ID: <?php echo $b->id; ?></span>
                                </div>
                            </div>
                        </td>
                        
                        <!-- STATUS PENGISIAN -->
                        <td>
                            <div class="status-container">
                                <?php if($b->is_periksa == '1' || $is_portal): ?>
                                    <span class="badge bg-success-subtle text-success border-0 align-self-start status-badge">
                                        <i class="bi bi-check-circle-fill me-1"></i> SUDAH DIISI
                                    </span>
                                    <div class="note-text">
                                        <?php if($is_portal): ?>
                                            Tersinkronisasi otomatis dengan Portal Data
                                        <?php else: ?>
                                            <?php echo (!empty($b->catatan_periksa) && $b->catatan_periksa != '-') ? htmlspecialchars($b->catatan_periksa) : 'Diinput melalui sheet manual'; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(!$is_portal): ?>
                                        <div class="action-group">
                                            <a href="<?php echo base_url(); ?>admin/batal_isi/<?php echo $b->id; ?>" 
                                               class="btn btn-action-sm btn-outline-danger border-0 bg-danger-subtle text-danger" 
                                               onclick="return confirm('Batalkan status pengisian?')"
                                               title="Batalkan Status">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Batal
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border-0 align-self-start status-badge">
                                        <i class="bi bi-dash-circle-fill me-1"></i> BELUM DIISI
                                    </span>
                                    <div class="action-group">
                                        <a href="javascript:;"
                                           class="btn btn-action-sm btn-warning text-white btn-isi-data shadow-sm"
                                           data-id="<?php echo $b->id; ?>"
                                           data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                                           data-unit="<?php echo htmlspecialchars($b->id_unitkerja, ENT_QUOTES); ?>">
                                            <i class="bi bi-plus-circle me-1"></i> Update Status
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- STATUS VERIFIKASI BPS -->
                        <td>
                            <div class="status-container">
                                <?php if ($b->is_confirm == '1'): ?>
                                    <span class="badge bg-primary-subtle text-primary border-0 align-self-start status-badge">
                                        <i class="bi bi-patch-check-fill me-1"></i> TERVERIFIKASI
                                    </span>
                                    <div class="note-text">
                                        <i class="bi bi-chat-right-text me-1 opacity-50"></i> <?php echo (!empty($b->catatan)) ? htmlspecialchars($b->catatan) : 'Tanpa catatan'; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border-0 align-self-start status-badge">
                                        <i class="bi bi-clock-history me-1"></i> MENUNGGU CEK
                                    </span>
                                    <?php if(session()->get('admin_unitkerja') == 'bps'): ?>
                                        <div class="action-group">
                                            <button class="btn btn-action-sm btn-info text-white konfirmasi_modal shadow-sm" id="<?php echo $b->id;?>">
                                                <i class="bi bi-shield-check me-1"></i> Verifikasi Data
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        }
                    }
                    ?>
                    <tr class="no-result-row d-none" id="noResultRow">
                        <td colspan="4" class="text-center py-5">
                            <i class="bi bi-search-heart display-4 text-muted opacity-25 d-block mb-3"></i>
                            <span class="text-secondary fw-medium">Tidak ada tabel yang sesuai dengan kata kunci Anda</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL FORM PENGISIAN -->
<div class="modal fade" id="ModalIsiData" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-0 bg-white pt-4 px-4 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-warning"></i>Update Status Pengisian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo site_url('admin/act_update_isi'); ?>" method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_tabel" id="id_tabel_modal">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark small mb-1">ID UNIT KERJA</label>
                        <input type="text" class="form-control bg-light border-0 py-2 px-3 fw-medium" id="asal_data_modal" readonly style="border-radius: 10px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark small mb-1">JUDUL TABEL</label>
                        <textarea class="form-control bg-light border-0 py-2 px-3 fw-medium" id="judul_tabel_modal" readonly rows="3" style="border-radius: 10px; resize: none;"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small mb-1">CATATAN PENGISIAN</label>
                        <textarea name="catatan_periksa" class="form-control border-1 py-2 px-3" rows="3" placeholder="Misal: Sudah diupdate melalui sheet manual atau script sinkronisasi..." style="border-radius: 10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4 py-2 border-0 fw-bold text-muted" data-bs-dismiss="modal" style="border-radius: 10px;">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 py-2 text-white fw-bold shadow-sm" style="border-radius: 10px; background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi -->
<div class="modal fade" id="ModalKonfirmasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" id="isiModalKonfirmasi">
        <!-- Remote Content Load -->
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Search Functionality
    $('#searchTabelPeriksa').on('input', function() {
        var keyword = $(this).val().toLowerCase().trim();
        var $rows = $('.tabel-periksa-row');
        var $noResult = $('#noResultRow');
        var visibleCount = 0;
        var num = 1;
        
        $rows.each(function() {
            var judul = $(this).find('.tabel-judul-cell').text().toLowerCase();
            if (judul.indexOf(keyword) > -1) {
                $(this).removeClass('d-none').show();
                $(this).find('.row-no').text(num++);
                visibleCount++;
            } else {
                $(this).addClass('d-none').hide();
            }
        });

        if(visibleCount == 0 && keyword != '') {
            $noResult.removeClass('d-none').show();
        } else {
            $noResult.addClass('d-none').hide();
        }
    });

    // Modal Trigger
    $(document).on('click', '.btn-isi-data', function () {
        $('#id_tabel_modal').val(this.dataset.id);
        $('#judul_tabel_modal').val(this.dataset.judul);
        $('#asal_data_modal').val(this.dataset.unit);
        var myModal = new bootstrap.Modal(document.getElementById('ModalIsiData'));
        myModal.show();
    });

    $(document).on('click', '.konfirmasi_modal', function(e) {
        var m = $(this).attr("id");
        $.ajax({
            url: "<?php echo site_url('admin/dda/periksa'); ?>",
            type: "GET",
            data : {konfirmasi_id: m,},
            success: function (ajaxData){
                $("#isiModalKonfirmasi").html(ajaxData);
                var myModal = new bootstrap.Modal(document.getElementById('ModalKonfirmasi'));
                myModal.show();
            }
        });
    });
});
</script>
