<?php
/**
 * VIEW: DAFTAR USULAN TABEL DARI DINAS/OPD (l_master_tabel_opd.php)
 * Menampilkan draf tabel baru yang diajukan oleh OPD/produsen data.
 * - Mode Admin BPS: Meninjau seluruh usulan, mengunduh file, dan menyetujui (ACC).
 * - Mode Instansi (OPD/Vertikal): Menampilkan usulan milik instansi sendiri + status telaah + tombol tambah usulan.
 */
$is_bps = isset($is_bps) ? (bool)$is_bps : (session()->get('admin_unitkerja') === 'bps');
?>

<style>
    /* Pastikan flexbox container luar tidak mengompres kartu Master Tabel OPD */
    .main-content {
        min-height: auto !important;
        padding-bottom: 12px !important;
    }
    .content-body {
        min-height: auto !important;
        flex: 1 0 auto !important;
    }
    #mainMasterTabelOpdCard {
        height: auto !important;
        max-height: calc(100vh - 82px) !important;
        min-height: 0 !important;
        display: flex;
        flex-direction: column;
        flex-shrink: 0 !important;
        overflow: hidden;
    }

    /* Kontainer scroll internal adaptif */
    .table-scroll-container {
        height: auto !important;
        max-height: calc(100vh - 82px - 130px) !important;
        min-height: 0 !important;
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: auto;
        position: relative;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    /* Custom Slim Scrollbar */
    .table-scroll-container::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Sticky Table Header */
    .table-scroll-container thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8fafc !important;
        box-shadow: inset 0 -2px 0 #e2e8f0;
        vertical-align: middle !important;
    }

    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.92rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.4rem !important; }
    
    .bg-gray-100 { background-color: #f8f9fa !important; }
    .bg-primary-soft { background-color: rgba(21, 70, 121, 0.08) !important; color: #154679 !important; }
    .border-primary-soft { border: 1px solid rgba(21, 70, 121, 0.2) !important; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
    .border-success-soft { border: 1px solid rgba(25, 135, 84, 0.25) !important; }
    .bg-warning-soft { background-color: rgba(245, 158, 11, 0.12) !important; color: #b45309 !important; }
    .border-warning-soft { border: 1px solid rgba(245, 158, 11, 0.28) !important; }
    .text-primary-orange { color: #154679 !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.92rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .input-group-alternative {
        transition: all 0.2s ease;
    }
    .input-group-alternative:focus-within {
        background-color: #fff !important;
        box-shadow: 0 4px 12px rgba(21, 70, 121, 0.08) !important;
        border-color: #154679 !important;
    }
    
    .btn-icon-only {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .divide-y > * + * { border-top-width: 1px; }
    .divide-gray-100 > * + * { border-color: #f1f1f1; }
</style>

<div class="card border-0 shadow-sm border-radius-2xl mb-0" id="mainMasterTabelOpdCard" style="height: auto; max-height: calc(100vh - 82px); min-height: 0; display: flex; flex-direction: column; overflow: hidden; flex-shrink: 0;">
    <div class="card-header pb-3 pt-3 px-4 bg-white border-0 flex-shrink-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($is_bps): ?>
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel/" class="btn btn-sm btn-outline-secondary border-radius-lg px-3 mb-0 shadow-none d-flex align-items-center">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-patch-question me-2 text-primary"></i>Review Tabel Usulan
                    </h5>
                    <?php else: ?>
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel_opd/add" class="btn btn-primary btn-sm px-3 border-radius-lg mb-0 shadow-sm fw-bold d-inline-flex align-items-center gap-2" style="background: linear-gradient(45deg, #154679, #1e5c9e); border: none;">
                        <i class="bi bi-plus-circle fs-6"></i>
                        <span>Usulkan Tabel Baru</span>
                    </a>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-file-earmark-plus me-2 text-primary"></i>Daftar Usulan Tabel
                    </h5>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Input Pencarian Cepat Usulan (Real-time Filter) -->
            <div class="col-lg-5">
                <form method="post" action="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/cari" onsubmit="return false;">
                    <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="inputSearchUsulan" class="form-control bg-transparent border-0 ps-0 text-sm" name="q" placeholder="Ketik untuk mencari usulan..." style="box-shadow: none;" autocomplete="off">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body px-4 pt-0 pb-3 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
        <!-- Informasi Petunjuk -->
        <div class="alert bg-gray-100 border-0 text-sm mb-3 px-3 py-2 border-radius-lg flex-shrink-0">
            <i class="bi bi-info-circle text-primary me-2"></i>
            <?php if ($is_bps): ?>
                Halaman ini menampilkan tabel baru yang diusulkan oleh OPD. Klik <strong>ACC</strong> untuk mendaftarkan ke Master Tabel.
            <?php else: ?>
                Halaman ini menampilkan riwayat usulan tabel baru dari instansi Anda. Klik tombol <strong>Usulkan Tabel Baru</strong> untuk mengajukan draf tabel tambahan ke BPS.
            <?php endif; ?>
        </div>

        <div class="table-responsive table-scroll-container flex-grow-1 rounded-3 border border-light" style="height: auto; min-height: 0; overflow-y: auto; overflow-x: auto;">
            <table class="table table-hover align-items-center mb-0 table-sticky-header">
                <thead class="bg-gray-100 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">
                    <tr>
                        <th width="45" class="text-center py-3">No</th>
                        <th class="py-3">Judul Indonesia (Draft)</th>
                        <th class="py-3">Judul Inggris</th>
                        <th width="120" class="text-center py-3">Dokumen</th>
                        <?php if ($is_bps): ?>
                        <th width="180" class="py-3">Instansi Pengusul</th>
                        <th width="120" class="text-center py-3">Pengirim</th>
                        <th width="100" class="text-center py-3">Aksi</th>
                        <?php else: ?>
                        <th width="150" class="text-center py-3">Status Usulan</th>
                        <th width="100" class="text-center py-3">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="tableUsulanBody" class="divide-y divide-gray-100 bg-white">
                    <?php 
                    if (empty($data)) {
                        $colspan = $is_bps ? 7 : 6;
                        echo "<tr id='emptyInitialRow'><td colspan='{$colspan}' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Belum ada usulan baru</td></tr>";
                    } else {
                        $no = 1;
                        foreach ($data as $b) {
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors usulan-row">
                        <td class="text-center">
                            <span class="text-secondary text-sm"><?php echo $no; ?></span>
                        </td>
                        <td class="py-3">
                            <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="max-width:320px; line-height: 1.5;"><?php echo (!empty($b->no_tabel) ? $b->no_tabel . ' ' : '') . format_judul_tabel($b->judul_ind, $b->periode_id ?? $b->periode ?? ''); ?></h6>
                        </td>
                        <td>
                            <p class="text-xxs text-secondary mb-0 font-italic text-wrap" style="max-width:320px;"><?php echo (!empty($b->no_tabel) ? $b->no_tabel . ' ' : '') . format_judul_tabel($b->judul_en, $b->periode_en ?? $b->periode_id ?? $b->periode ?? ''); ?></p>
                        </td>
                        <td class="text-center">
                            <a href="<?php echo base_URL()?>upload/tabel_usulan/<?php echo $b->file_tabel; ?>" target='_blank' class="badge bg-primary-soft text-primary border-primary-soft align-self-start py-2 px-3 fw-bold border-radius-lg shadow-none text-decoration-none d-inline-block">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Unduh
                            </a>
                        </td>
                        <?php if ($is_bps): ?>
                        <td>
                            <span class="text-xs font-weight-bold text-dark lh-sm"><?php echo $b->unitkerja_ind; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold text-uppercase"><?php echo $b->addby; ?></span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center align-items-center">
                                <a href="<?php echo base_URL()?>index.php/admin/master_tabel_opd/edt/<?php echo $b->id; ?>" class="btn btn-icon-only <?php echo ($b->is_setujui == '1') ? 'btn-outline-success' : 'btn-success'; ?> btn-sm border-radius-lg shadow-none" title="<?php echo ($b->is_setujui == '1') ? 'Perbarui / Daftarkan Ulang' : 'Terima Usulan (ACC)'; ?>">
                                    <i class="bi bi-check-lg"></i>
                                </a>
                                <a href="#" class="open_modal btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" id="<?php echo $b->id; ?>" title="Tolak / Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>  
                        </td>
                        <?php else: ?>
                        <td class="text-center">
                            <?php if ($b->is_setujui == '1'): ?>
                                <span class="badge bg-success-soft text-success border-success-soft px-3 py-2 border-radius-lg fw-bold" style="font-size: 0.78rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-soft text-warning border-warning-soft px-3 py-2 border-radius-lg fw-bold" style="font-size: 0.78rem;">
                                    <i class="bi bi-clock-history me-1"></i> Menunggu Telaah
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center align-items-center">
                                <?php if ($b->is_setujui == '0'): ?>
                                <a href="#" class="open_modal btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" id="<?php echo $b->id; ?>" title="Batalkan / Hapus Usulan">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php else: ?>
                                <span class="badge bg-light text-secondary border border-radius-lg p-2" title="Tabel telah resmi terdaftar di Master Tabel DDA">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php 
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if(!empty($pagi)): ?>
        <div class="mt-4">
            <?php echo $pagi; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Delete (BS5) -->
<div id="ModalDelete" class="modal fade" tabindex="-1" aria-hidden="true"></div>

<script type="text/javascript">
$(document).ready(function () {
    // =========================================================================
    // FITUR PENCARIAN REAL-TIME CLIENT-SIDE DENGAN TINGGI ADAPTIF
    // Menyaring baris usulan OPD secara instan.
    // Tinggi kartu dan kontainer tabel otomatis menyusut mengikuti jumlah data yang tampak.
    // =========================================================================
    $("#inputSearchUsulan").on("input keyup", function() {
        var value = $(this).val().toLowerCase().trim();
        var $rows = $("#tableUsulanBody tr.usulan-row");
        var visibleCount = 0;

        $rows.each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(value) > -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        // Handle state jika tidak ada baris yang cocok
        if (visibleCount === 0 && value.length > 0) {
            if ($("#emptySearchState").length === 0) {
                $("#tableUsulanBody").append('<tr id="emptySearchState"><td colspan="<?= $is_bps ? 7 : 6; ?>" class="text-center py-5 text-secondary font-weight-bold opacity-7"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>Tidak ada usulan yang cocok dengan kata kunci</td></tr>');
            } else {
                $("#emptySearchState").show();
            }
        } else {
            $("#emptySearchState").hide();
        }

        // Jika baris yang tampak sedikit, kembalikan posisi scroll luar ke atas agar tidak ada ruang kosong
        if (visibleCount <= 4 && $('#content').scrollTop() > 0) {
            $('#content').scrollTop(0);
        }
    });

    // =========================================================================
    // MODAL DIALOG TOLAK / HAPUS USULAN TABEL OPD
    // Memuat view konfirmasi hapus aman dari endpoint master_tabel_opd/del/
    // =========================================================================
    $(document).on("click", ".open_modal", function(e) {
        e.preventDefault();
        var m = $(this).attr("id");

        // Tutup dropdown Bootstrap yang aktif
        var dropdownBtn = $(this).closest('.dropdown').find('[data-bs-toggle="dropdown"]')[0];
        if (dropdownBtn) {
            var dd = bootstrap.Dropdown.getInstance(dropdownBtn);
            if (dd) dd.hide();
        }

        // Muat konten konfirmasi via AJAX
        $.ajax({
            url: "<?php echo base_url(); ?>index.php/admin/master_tabel_opd/del/",
            type: "GET",
            data: {delete_id: m},
            success: function (ajaxData) {
                var modalEl = document.getElementById('ModalDelete');
                $(modalEl).html(ajaxData);
                var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsModal.show();
            }
        });
    });

    // Bersihkan isi modal dan backdrop saat modal ditutup agar layar tidak terkunci
    $('#ModalDelete').on('hidden.bs.modal', function () {
        $(this).empty();
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
    });
});
</script>
