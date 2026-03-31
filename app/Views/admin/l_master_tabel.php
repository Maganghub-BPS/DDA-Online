<?php echo session()->getFlashdata("k"); ?>

<!-- Consolidated Control Card -->
<div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
    <div class="card-header pb-3 pt-3 px-4 bg-white border-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel/add" class="btn btn-primary btn-sm px-3 border-radius-lg mb-0">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Data
                    </a>
                    <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/" class="btn btn-outline-primary btn-sm px-3 border-radius-lg mb-0 shadow-none">
                        <i class="bi bi-patch-question me-1"></i> Tabel Usulan
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 border-radius-lg mb-0 shadow-none" data-bs-toggle="modal" data-bs-target="#ModalBulkPortal">
                        <i class="bi bi-cloud-upload me-1"></i> Bulk Portal
                    </button>
                </div>
            </div>
            <div class="col-lg-5">
                <form method="post" action="<?php echo base_URL(); ?>index.php/admin/master_tabel/cari">
                    <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="inputSearchTabel" class="form-control bg-transparent border-0 ps-0" name="q" placeholder="Ketik untuk mencari tabel..." style="box-shadow: none;">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body px-4 pt-0 pb-4">
        <div class="bg-light-subtle border-radius-xl p-3 mb-4 border border-light shadow-sm">
            <form class="row g-3 align-items-end" method="GET" action="<?php echo base_url(); ?>index.php/admin/master_tabel">
                <!-- Filter Bidang -->
                <div class="col-md-3">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1"><i class="bi bi-diagram-2 me-1"></i>Bidang / Tim</label>
                    <select name="filter_bidang" class="form-select border-radius-lg border-light shadow-none text-sm py-2" onchange="this.form.submit()">
                        <option value="all">-- Semua Bidang --</option>
                        <?php
                        if (isset($list_tim)) {
                            foreach ($list_tim as $tim) {
                                $sel = (isset($selected_bidang) && $selected_bidang == $tim->user_wali) ? 'selected' : '';
                                echo "<option value='" . $tim->user_wali . "' $sel>" . strtoupper($tim->user_wali) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter OPD -->
                <div class="col-md-4">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1"><i class="bi bi-building me-1"></i>Instansi (OPD)</label>
                    <select name="filter_opd" class="form-select border-radius-lg border-light shadow-none text-sm py-2" onchange="this.form.submit()">
                        <option value="all">-- Semua OPD --</option>
                        <?php
                        if (isset($list_opd)) {
                            foreach ($list_opd as $opd) {
                                $selected = (isset($selected_opd) && $selected_opd == $opd->id_unitkerja) ? 'selected' : '';
                                echo "<option value='" . $opd->id_unitkerja . "' $selected>" . $opd->unitkerja_ind . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1"><i class="bi bi-calendar3 me-1"></i>Tahun</label>
                    <select name="filter_tahun" class="form-select border-radius-lg border-light shadow-none text-sm py-2" onchange="this.form.submit()">
                        <option value="all">Semua</option>
                        <?php
                        for ($i = 2020; $i <= (date('Y') + 1); $i++) {
                            $selected = (isset($selected_tahun) && $selected_tahun == $i) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Reset -->
                <div class="col-md-3 text-end d-flex align-items-center justify-content-end gap-2">
                    <?php if ((isset($selected_opd) && $selected_opd != 'all') || (isset($selected_bidang) && $selected_bidang != 'all') || (isset($selected_tahun) && $selected_tahun != 'all')): ?>
                        <a href="<?php echo base_url(); ?>index.php/admin/master_tabel?action=reset" class="btn btn-link text-secondary text-xs mb-0 px-2 fw-bold text-decoration-none">
                            <i class="bi bi-x-circle me-1 text-danger"></i> Reset
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm border-radius-lg px-4 py-2 mb-0 shadow-sm fw-bold d-flex align-items-center" style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-filter me-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive rounded-3 border border-light overflow-hidden">
            <table class="table table-hover align-items-center mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        <th width="45" class="text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">No</th>
                        <th width="65" class="valign-middle text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Thn</th>
                        <th class="valign-middle py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Judul Tabel</th>
                        <th width="200" class="valign-middle py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Sumber / Link</th>
                        <th width="180" class="valign-middle py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Instansi / Unit Kerja</th>
                        <th width="85" class="valign-middle text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableMasterBody" class="divide-y divide-gray-100 bg-white">
                    <?php
                    if (empty($data)) {
                        echo "<tr><td colspan='6' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Data tidak ditemukan</td></tr>";
                    } else {
                        $no = 1;
                        foreach ($data as $b) {
                    ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="text-center">
                                    <span class="text-secondary text-sm"><?php echo $no; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold"><?php echo $b->tahun; ?></span>
                                </td>
                                <td class="py-3">
                                    <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="max-width:350px; line-height: 1.5;"><?php echo $b->judul_ind; ?></h6>
                                    <p class="text-xxs text-secondary mb-0 font-italic text-wrap mt-1" style="max-width:350px; line-height: 1.2;"><?php echo $b->judul_en; ?></p>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <?php if (strpos($b->link_tabel, 'view_portal_tabel') !== false): ?>
                                            <span class="badge badge-sm bg-primary-soft text-primary border-primary-soft align-self-start fw-bold">
                                                <i class="bi bi-cloud-check me-1"></i>PORTAL DATA
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-sm bg-success-soft text-success border-success-soft align-self-start fw-bold">
                                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>SPREADSHEET
                                            </span>
                                        <?php endif; ?>
                                        <code class="text-xxs text-secondary text-truncate d-block mt-1" style="max-width: 180px;"><?php echo str_replace('index.php/admin/', '', $b->link_tabel); ?></code>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-xs font-weight-bold text-dark lh-sm"><?php echo $b->unitkerja_ind; ?></span>
                                        <span class="text-xxs text-secondary opacity-7 mt-1 text-uppercase fw-bold"><?php echo $b->user_wali ?: 'NASIONAL'; ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-link text-secondary mb-0 shadow-none border-0" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical fs-6"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 border-radius-lg p-2">
                                            <li>
                                                <a href="<?php echo base_URL() ?>index.php/admin/master_tabel/edt/<?php echo $b->id; ?>/1" class="dropdown-item border-radius-md py-2 text-sm">
                                                    <i class="bi bi-pencil me-2 text-primary"></i> Edit Detail
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider opacity-5 mt-1">
                                            </li>
                                            <li>
                                                <a href="#" class="open_modal dropdown-item border-radius-md py-2 text-sm text-danger" id="<?php echo $b->id; ?>">
                                                    <i class="bi bi-trash me-2"></i> Hapus Tabel
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                    <?php
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($pagi)): ?>
            <div class="mt-4 pagination-container">
                <?php echo $pagi; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Bulk Update Portal (BS5) -->
<div id="ModalBulkPortal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
            <div class="modal-header bg-gray-100 border-0 pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-cloud-upload me-2 text-primary"></i>Bulk Update Portal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo base_url(); ?>index.php/admin/preview_bulk_portal" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="alert bg-gray-100 border-0 text-dark text-sm mb-4">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                        Gunakan file CSV mapping untuk memperbarui tautan tabel secara massal ke Portal Data Jawa Tengah.
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">1. Download Panduan</label>
                        <a href="<?php echo base_url(); ?>index.php/admin/download_xlsx_template" class="btn btn-outline-primary btn-sm w-100 border-radius-lg py-2">
                            <i class="bi bi-download me-2"></i> Download Template CSV
                        </a>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">2. Unggah File Mapping</label>
                        <div class="input-group">
                            <input type="file" name="file_mapping" class="form-control" accept=".csv" required style="border-radius: 0.6rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 border-0 p-4">
                    <button type="button" class="btn btn-link text-secondary mb-0" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm border-radius-lg px-4 mb-0">Mulai Sinkronisasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete (BS5) -->
<div id="ModalDelete" class="modal fade" tabindex="-1" aria-hidden="true"></div>

<style>
    .ls-1 {
        letter-spacing: 0.8px;
    }

    .text-xxs {
        font-size: 0.75rem !important;
    }

    .text-xs {
        font-size: 0.82rem !important;
    }

    .text-sm {
        font-size: 0.92rem !important;
    }

    .font-weight-bolder {
        font-weight: 800 !important;
    }

    .border-radius-2xl {
        border-radius: 1.25rem !important;
    }

    .border-radius-xl {
        border-radius: 1rem !important;
    }

    .border-radius-lg {
        border-radius: 0.6rem !important;
    }

    .border-radius-md {
        border-radius: 0.5rem !important;
    }

    .bg-gray-50 {
        background-color: #fcfcfc !important;
    }

    .bg-gray-100 {
        background-color: #f8f9fa !important;
    }

    .bg-primary-soft {
        background-color: rgba(255, 109, 31, 0.08) !important;
        color: #FF6D1F !important;
    }

    .bg-success-soft {
        background-color: rgba(40, 167, 69, 0.08) !important;
        color: #28a745 !important;
    }

    .border-primary-soft {
        border: 1px solid rgba(255, 109, 31, 0.15) !important;
    }

    .border-success-soft {
        border: 1px solid rgba(40, 167, 69, 0.15) !important;
    }

    .table td,
    .table th {
        border-color: #f1f1f1 !important;
        vertical-align: middle !important;
        font-size: 0.92rem !important;
    }

    .table thead th {
        border-bottom: 0 !important;
        font-size: 0.75rem !important;
    }

    .input-group-alternative {
        transition: all 0.2s ease;
    }

    .input-group-alternative:focus-within {
        background-color: #fff !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        border-color: #FF6D1F !important;
    }

    .divide-y>*+* {
        border-top-width: 1px;
    }

    .divide-gray-100>*+* {
        border-color: #f1f1f1;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #FF6D1F;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        // AJAX Live Search with Debounce
        var searchTimer;
        $("#inputSearchTabel").on("keyup", function() {
            var value = $(this).val();
            clearTimeout(searchTimer);
            
            // Local Filter (for instant feedback on current viewport)
            var lowerValue = value.toLowerCase();
            $("#tableMasterBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(lowerValue) > -1)
            });

            // AJAX Global Search (after 450ms delay)
            searchTimer = setTimeout(function() {
                if (value.length >= 2 || value.length == 0) {
                    $.ajax({
                        url: "<?php echo base_URL(); ?>index.php/admin/master_tabel/cari",
                        type: "POST",
                        data: { q: value },
                        success: function(response) {
                            $("#tableMasterBody").html(response);
                            // Hide pagination if searching
                            if (value.length > 0) {
                                $(".pagination-container").hide();
                            } else {
                                location.reload(); // Reload to restore pagination and original data
                            }
                        }
                    });
                }
            }, 450);
        });

        $(".open_modal").click(function(e) {
            e.preventDefault();
            var m = $(this).attr("id");
            $.ajax({
                url: "<?php echo base_url(); ?>index.php/admin/master_tabel/del/",
                type: "GET",
                data: {
                    delete_id: m
                },
                success: function(ajaxData) {
                    $("#ModalDelete").html(ajaxData);
                    var bsModal = new bootstrap.Modal(document.getElementById('ModalDelete'));
                    bsModal.show();
                }
            });
        });
    });
</script>