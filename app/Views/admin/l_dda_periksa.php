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
    .form-check-input.publish-switch {
        transition: background-color 0.25s ease, border-color 0.25s ease, transform 0.15s ease;
    }
    .form-check-input.publish-switch:checked {
        background-color: #10b981 !important;
        border-color: #10b981 !important;
    }
    .form-check-input.publish-switch:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.2);
    }
    .filter-publish-btn {
        transition: all 0.2s ease;
    }
    .filter-publish-btn.active {
        background-color: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08) !important;
    }
    .toast-container-custom {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
    }
    @media (max-width: 991.98px) { .tabel-judul-cell { min-width: 280px; } }
    @media (max-width: 575.98px) {
        .filter-search-container { flex-wrap: wrap !important; width: 100%; }
        .filter-search-container .input-group { width: 100% !important; }
    }
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
                <div class="d-flex align-items-center gap-2 flex-nowrap filter-search-container">
                    <!-- Filter Publish Pill Buttons -->
                    <div class="btn-group btn-group-sm bg-light p-1 rounded-3 flex-shrink-0 d-inline-flex align-items-center" role="group" id="filterPublishGroup" style="border: 1px solid #e9ecef; height: 38px;">
                        <button type="button" class="btn btn-sm active fw-semibold filter-publish-btn h-100 d-inline-flex align-items-center" data-filter="all" style="border-radius: 6px; font-size: 0.78rem; padding: 0 0.75rem;">
                            Semua
                        </button>
                        <button type="button" class="btn btn-sm text-secondary fw-semibold filter-publish-btn h-100 d-inline-flex align-items-center" data-filter="published" style="border-radius: 6px; font-size: 0.78rem; padding: 0 0.75rem;">
                            <i class="bi bi-eye-fill text-success me-1"></i> Publik
                        </button>
                        <button type="button" class="btn btn-sm text-secondary fw-semibold filter-publish-btn h-100 d-inline-flex align-items-center" data-filter="draft" style="border-radius: 6px; font-size: 0.78rem; padding: 0 0.75rem;">
                            <i class="bi bi-eye-slash-fill text-secondary me-1"></i> Draft
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm flex-nowrap flex-shrink-0" style="width: 250px; height: 38px;">
                        <span class="input-group-text bg-light border-0 text-muted px-3" style="border-radius: 8px 0 0 8px; font-size: 0.85rem;"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control bg-light border-0 ps-0 pe-3" id="searchTabelPeriksa" placeholder="Cari judul tabel..." autocomplete="off" style="border-radius: 0 8px 8px 0; font-size: 0.85rem; height: 38px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableTabelPeriksa">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">#</th>
                        <th width="40%">Informasi Tabel</th>
                        <th width="21%">Status Pengisian</th>
                        <th width="21%">Status Verifikasi</th>
                        <th width="14%" class="text-center">Status Tampil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='bi bi-info-circle me-2 d-block mb-3 display-6 opacity-25'></i>Tabel tidak ditemukan</td></tr>";
                    } else {
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                            $judul_bersih = preg_replace("/\r|\n/", " ", (!empty($b->no_tabel) ? $b->no_tabel . " " : "") . format_judul_tabel($b->judul_ind, $b->periode_id ?? $b->periode ?? ""));
                            $is_portal = (stripos($b->link_tabel, 'portal') !== false || 
                                          stripos($b->link_tabel, 'satudata') !== false || 
                                          (isset($b->id_api) && !empty($b->id_api)));
                            $is_published = ((int)($b->is_publish ?? 0) === 1);
                            $has_link = !empty(trim($b->link_tabel ?? ''));
                    ?>
                    <tr class="tabel-periksa-row" id="row-<?php echo $b->id; ?>" data-published="<?= $is_published ? '1' : '0' ?>" data-has-link="<?= $has_link ? '1' : '0' ?>">
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

                        <!-- STATUS TAMPIL (PUBLISH SWITCH) -->
                        <?php
                            $user_lvl_sess = strtolower(trim(session()->get('admin_level') ?? ''));
                            $is_admin_or_super = in_array($user_lvl_sess, ['admin', 'super admin', 'superadmin']);
                            $switch_disabled = (!$has_link || !$is_admin_or_super);
                            if (!$is_admin_or_super) {
                                $switch_title = 'Hanya Admin dan Super Admin yang dapat mengubah status publikasi';
                                $switch_cursor = 'not-allowed';
                            } elseif (!$has_link) {
                                $switch_title = 'Lengkapi link tabel terlebih dahulu untuk mempublish';
                                $switch_cursor = 'not-allowed';
                            } else {
                                $switch_title = $is_published ? 'Klik untuk menarik ke Draft' : 'Klik untuk mempublish ke Web Frontend';
                                $switch_cursor = 'pointer';
                            }
                        ?>
                        <td class="text-center" style="vertical-align: middle;">
                            <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                <div class="form-check form-switch m-0" style="min-height: 24px;">
                                    <input class="form-check-input publish-switch" 
                                           type="checkbox" 
                                           role="switch" 
                                           id="switch-pub-<?= $b->id ?>" 
                                           data-id="<?= $b->id ?>"
                                           <?= $is_published ? 'checked' : '' ?>
                                           <?= $switch_disabled ? 'disabled' : '' ?>
                                           title="<?= $switch_title ?>"
                                           style="width: 2.8em; height: 1.45em; cursor: <?= $switch_cursor ?>;">
                                </div>
                                <div id="pub-status-label-<?= $b->id ?>" class="mt-1">
                                    <?php if (!$has_link): ?>
                                        <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size: 10px; font-weight: 600;" title="Link data belum diisi">
                                            <i class="bi bi-link-45deg me-0.5"></i> Tanpa Link
                                        </span>
                                    <?php elseif ($is_published): ?>
                                        <span class="badge bg-success-subtle text-success border-0 px-2 py-0.5" style="font-size: 10px; font-weight: 700;">
                                            <i class="bi bi-eye-fill me-0.5"></i> Publik
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border-0 px-2 py-0.5" style="font-size: 10px; font-weight: 700;">
                                            <i class="bi bi-eye-slash-fill me-0.5"></i> Draft
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        }
                    }
                    ?>
                    <tr class="no-result-row d-none" id="noResultRow">
                        <td colspan="5" class="text-center py-5">
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
<div class="modal fade" id="ModalKonfirmasi" tabindex="-1" aria-hidden="true"></div>

<!-- Toast Container for Notifications -->
<div class="toast-container toast-container-custom position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 99999;"></div>

<script type="text/javascript">
$(document).ready(function() {
    // Current Active Publish Filter ('all', 'published', 'draft')
    var currentPublishFilter = 'all';

    // Unified Filter Function (Keyword Search + Publish Filter)
    function applyFilters() {
        var keyword = $('#searchTabelPeriksa').val().toLowerCase().trim();
        var $rows = $('.tabel-periksa-row');
        var $noResult = $('#noResultRow');
        var visibleCount = 0;
        var num = 1;

        $rows.each(function() {
            var $r = $(this);
            var judul = $r.find('.tabel-judul-cell').text().toLowerCase();
            var isPublished = $r.attr('data-published') === '1';

            var matchSearch = (keyword === '' || judul.indexOf(keyword) > -1);
            var matchPublish = (
                currentPublishFilter === 'all' ||
                (currentPublishFilter === 'published' && isPublished) ||
                (currentPublishFilter === 'draft' && !isPublished)
            );

            if (matchSearch && matchPublish) {
                $r.removeClass('d-none').show();
                $r.find('.row-no').text(num++);
                visibleCount++;
            } else {
                $r.addClass('d-none').hide();
            }
        });

        if (visibleCount === 0) {
            $noResult.removeClass('d-none').show();
        } else {
            $noResult.addClass('d-none').hide();
        }
    }

    // Search Input Event
    $('#searchTabelPeriksa').on('input', function() {
        applyFilters();
    });

    // Publish Filter Pill Buttons
    $('.filter-publish-btn').on('click', function() {
        $('.filter-publish-btn').removeClass('active').addClass('text-secondary');
        $(this).addClass('active').removeClass('text-secondary');
        currentPublishFilter = $(this).data('filter');
        applyFilters();
    });

    // Toast Notification Helper
    function showToastNotification(type, message) {
        var icon = type === 'success' ? 'bi-check-circle-fill text-success' : (type === 'danger' ? 'bi-exclamation-octagon-fill text-danger' : 'bi-info-circle-fill text-info');
        var toastHtml = `
            <div class="toast align-items-center border-0 shadow-lg text-dark bg-white show mb-2" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 12px; min-width: 290px; box-shadow: 0 10px 30px rgba(0,0,0,0.12) !important;">
                <div class="d-flex align-items-center p-3">
                    <i class="bi ${icon} fs-5 me-2.5"></i>
                    <div class="toast-body p-0 flex-grow-1 small fw-medium text-dark">${message}</div>
                    <button type="button" class="btn-close ms-2 me-0" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;
        var $toast = $(toastHtml);
        $('#toastContainer').append($toast);
        setTimeout(function() {
            $toast.fadeOut(400, function() { $(this).remove(); });
        }, 3200);
    }

    // Publish Switch Toggle Event (AJAX)
    $(document).on('change', '.publish-switch', function() {
        var $switch = $(this);
        var id = $switch.data('id');
        var isChecked = $switch.is(':checked');
        var newPublishVal = isChecked ? 1 : 0;
        var $row = $('#row-' + id);
        var $labelContainer = $('#pub-status-label-' + id);

        // Disable temporarily while request is in flight
        $switch.prop('disabled', true);

        $.ajax({
            url: "<?= base_url('admin/act_toggle_publish') ?>",
            type: "POST",
            dataType: "json",
            data: {
                id: id,
                is_publish: newPublishVal
            },
            success: function(res) {
                $switch.prop('disabled', false);
                if (res.status === 'success') {
                    $row.attr('data-published', newPublishVal);
                    if (newPublishVal === 1) {
                        $labelContainer.html('<span class="badge bg-success-subtle text-success border-0 px-2 py-0.5" style="font-size: 10px; font-weight: 700;"><i class="bi bi-eye-fill me-0.5"></i> Publik</span>');
                        showToastNotification('success', res.message);
                    } else {
                        $labelContainer.html('<span class="badge bg-secondary-subtle text-secondary border-0 px-2 py-0.5" style="font-size: 10px; font-weight: 700;"><i class="bi bi-eye-slash-fill me-0.5"></i> Draft</span>');
                        showToastNotification('info', res.message);
                    }
                    applyFilters();
                } else {
                    // Revert switch on error
                    $switch.prop('checked', !isChecked);
                    showToastNotification('danger', res.message || 'Gagal mengubah status publish.');
                }
            },
            error: function(xhr) {
                $switch.prop('disabled', false);
                $switch.prop('checked', !isChecked);
                var err = 'Terjadi kesalahan sistem.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r && r.message) err = r.message;
                } catch(e) {}
                showToastNotification('danger', err);
            }
        });
    });

    // Modal Trigger
    $(document).on('click', '.btn-isi-data', function () {
        $('#id_tabel_modal').val(this.dataset.id);
        $('#judul_tabel_modal').val(this.dataset.judul);
        $('#asal_data_modal').val(this.dataset.unit);
        var modalEl = document.getElementById('ModalIsiData');
        var myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        myModal.show();
    });

    $(document).on('click', '.konfirmasi_modal', function(e) {
        e.preventDefault();
        var m = $(this).attr("id");
        var modalEl = document.getElementById('ModalKonfirmasi');
        $.ajax({
            url: "<?php echo site_url('admin/dda/periksa'); ?>",
            type: "GET",
            data : {konfirmasi_id: m},
            success: function (ajaxData){
                $(modalEl).html(ajaxData);
                var myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                myModal.show();
            }
        });
    });

    $('#ModalKonfirmasi').on('hidden.bs.modal', function () {
        $(this).empty();
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
    });
});
</script>
