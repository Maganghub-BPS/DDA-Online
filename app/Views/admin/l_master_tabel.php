<!-- =========================================================================
     HALAMAN MASTER TABEL DDA (Daftar Publikasi Tabel Sektoral)
     Fitur Utama:
     1. Toolbar Pengelolaan: Tambah tabel, Usulan OPD, Bulk Portal, & Menu Pengelolaan Tahun
     2. Filter Data: Bidang/Tim, Instansi (OPD), dan Tahun Terbit
     3. Live Search Realtime via AJAX (tanpa refresh halaman)
     4. Modal Duplikasi Tabel (Clone), Rapikan Urutan (Resequence), dan Kosongkan Tahun (Reset)
========================================================================= -->
<?php
$current_lvl = strtolower(trim((string)(session()->get('admin_level') ?? '')));
$is_super_admin = in_array($current_lvl, ['super admin', 'superadmin']);
?>

<!-- Kartu Kontrol & Aksi Utama (Toolbar Atas) -->
<div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
    <div class="card-header pb-3 pt-3 px-4 bg-white border-0">
        <div class="row align-items-center g-3">
            <div class="col-xl-8 col-lg-7">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Tombol Tambah Tabel Baru Secara Manual -->
                    <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel/add" class="btn btn-primary btn-sm px-3 border-radius-lg mb-0 shadow-sm fw-bold d-inline-flex align-items-center gap-2" style="background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-plus-circle fs-6"></i>
                        <span>Tambah Data</span>
                    </a>

                    <!-- Tombol Menuju Daftar Usulan Tabel yang Diajukan oleh OPD -->
                    <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/" class="btn btn-sm px-3 border-radius-lg mb-0 shadow-none d-inline-flex align-items-center gap-2 fw-bold" style="background: #FFF4ED; color: #FF6D1F; border: 1px solid #FFD8C2;">
                        <i class="bi bi-patch-question fs-6"></i>
                        <span>Tabel Usulan</span>
                    </a>

                    <!-- Tombol Sinkronisasi Massal via CSV ke Portal Data Jateng -->
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 border-radius-lg mb-0 shadow-none d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ModalBulkPortal">
                        <i class="bi bi-cloud-upload fs-6"></i>
                        <span>Bulk Portal</span>
                    </button>

                    <!-- Dropdown Menu Pengelolaan Tahun: Clone, Resequence, dan Reset (Hanya Super Admin) -->
                    <?php if ($is_super_admin): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary px-3 border-radius-lg mb-0 shadow-none dropdown-toggle d-inline-flex align-items-center gap-2" type="button" id="dropdownPengelolaanTahun" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear-wide-connected fs-6"></i>
                            <span>Pengelolaan Tahun</span>
                        </button>
                        <ul class="dropdown-menu shadow-lg border-0 border-radius-lg p-2 mt-1" aria-labelledby="dropdownPengelolaanTahun" style="min-width: 260px; z-index: 1050;">
                            <li><h6 class="dropdown-header text-uppercase text-xxs font-weight-bolder text-muted px-2 py-1">Operasi Tahunan</h6></li>
                            <!-- Opsi 1: Duplikasi Seluruh Tabel dari Tahun Lalu -->
                            <li>
                                <a class="dropdown-item border-radius-md py-2 d-flex align-items-center" href="javascript:;" data-bs-toggle="modal" data-bs-target="#ModalCloneTahunan">
                                    <div class="icon icon-shape icon-xs rounded-circle text-center me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(23, 162, 184, 0.12);">
                                        <i class="bi bi-files text-info"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-xs font-weight-bold text-dark">Duplikasi Tahun</span>
                                        <span class="text-xxs text-muted">Salin daftar tabel tahun lalu</span>
                                    </div>
                                </a>
                            </li>
                            <!-- Opsi 2: Rapikan Penomoran Tabel yang Melompat pada Bab Tertentu -->
                            <li>
                                <a class="dropdown-item border-radius-md py-2 d-flex align-items-center" href="javascript:;" data-bs-toggle="modal" data-bs-target="#ModalResequence">
                                    <div class="icon icon-shape icon-xs rounded-circle text-center me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(255, 193, 7, 0.15);">
                                        <i class="bi bi-sort-numeric-down text-warning"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-xs font-weight-bold text-dark">Rapikan Urutan</span>
                                        <span class="text-xxs text-muted">Resequence nomor urut tabel</span>
                                    </div>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li><h6 class="dropdown-header text-uppercase text-xxs font-weight-bolder text-danger px-2 py-1">Zona Bahaya</h6></li>
                            <!-- Opsi 3: Kosongkan Seluruh Tabel di Tahun Tertentu -->
                            <li>
                                <a class="dropdown-item border-radius-md py-2 d-flex align-items-center text-danger" href="javascript:;" data-bs-toggle="modal" data-bs-target="#ModalResetTahun">
                                    <div class="icon icon-shape icon-xs rounded-circle text-center me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(220, 53, 69, 0.12);">
                                        <i class="bi bi-trash3-fill text-danger"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-xs font-weight-bold text-danger">Kosongkan Tahun</span>
                                        <span class="text-xxs text-danger opacity-8">Hapus semua data di tahun tertentu</span>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Kotak Pencarian Realtime (AJAX Live Search dengan Debounce) -->
            <div class="col-xl-4 col-lg-5">
                <form method="post" action="<?php echo base_URL(); ?>index.php/admin/master_tabel/cari">
                    <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="inputSearchTabel" class="form-control bg-transparent border-0 ps-0 text-sm" name="q" placeholder="Ketik untuk mencari tabel..." style="box-shadow: none;" value="<?php echo esc($cari ?? ''); ?>">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Area Filter dan Tabel Data -->
    <div class="card-body px-4 pt-0 pb-4">
        <!-- Form Filter Multi-Kategori: Bidang Tim, Instansi (OPD), dan Tahun DDA -->
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
                    <?php if ((isset($selected_opd) && $selected_opd != 'all') || (isset($selected_bidang) && $selected_bidang != 'all') || (isset($selected_tahun) && $selected_tahun != 'all') || !empty($cari)): ?>
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
                        $no = (int)($awal ?? 0) + 1;
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
                                    <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="max-width:350px; line-height: 1.5;"><?php echo (!empty($b->no_tabel) ? $b->no_tabel . ' ' : '') . format_judul_tabel($b->judul_ind, $b->periode_id ?? $b->periode ?? ''); ?></h6>
                                    <p class="text-xxs text-secondary mb-0 font-italic text-wrap mt-1" style="max-width:350px; line-height: 1.2;"><?php echo (!empty($b->no_tabel) ? $b->no_tabel . ' ' : '') . format_judul_tabel($b->judul_en, $b->periode_en ?? $b->periode_id ?? $b->periode ?? ''); ?></p>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <?php 
                                        $link_val = trim((string)($b->link_tabel ?? ''));
                                        if (empty($link_val)): 
                                        ?>
                                            <span class="badge badge-sm bg-light text-secondary border align-self-start fw-semibold" style="font-size: 10px;">
                                                <i class="bi bi-dash-circle me-1"></i>BELUM ADA LINK
                                            </span>
                                            <span class="text-xxs text-muted fst-italic mt-1">-</span>
                                        <?php elseif (strpos($link_val, 'view_portal_tabel') !== false): ?>
                                            <span class="badge badge-sm bg-primary-soft text-primary border-primary-soft align-self-start fw-bold">
                                                <i class="bi bi-cloud-check me-1"></i>PORTAL DATA
                                            </span>
                                            <code class="text-xxs text-secondary text-truncate d-block mt-1" style="max-width: 180px;"><?php echo esc(str_replace('index.php/admin/', '', $link_val)); ?></code>
                                        <?php else: ?>
                                            <span class="badge badge-sm bg-success-soft text-success border-success-soft align-self-start fw-bold">
                                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>SPREADSHEET
                                            </span>
                                            <code class="text-xxs text-secondary text-truncate d-block mt-1" style="max-width: 180px;"><?php echo esc(str_replace('index.php/admin/', '', $link_val)); ?></code>
                                        <?php endif; ?>
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

<!-- ========================================================================= -->
<!-- MODAL: BULK UPDATE PORTAL DATA (SINKRONISASI MASSAL VIA CSV)              -->
<!-- Fitur ini memungkinkan admin memperbarui tautan (link_tabel) banyak tabel  -->
<!-- sekaligus ke Portal Data Open Data Jawa Tengah menggunakan file CSV mapping.-->
<!-- Alur: Upload CSV -> Pratinjau perubahan link -> Simpan perubahan massal.   -->
<!-- Endpoint: admin/preview_bulk_portal                                       -->
<!-- ========================================================================= -->
<!-- Modal Bulk Update Portal (BS5) -->
<div id="ModalBulkPortal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
            <div class="modal-header bg-gray-100 border-0 pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-cloud-upload me-2 text-primary"></i>Bulk Update Portal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Form pengunggahan file CSV mapping -->
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
                <div class="modal-footer bg-light-subtle border-top py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4 border" style="border-radius: 8px;" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold" style="border-radius: 8px; background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-cloud-upload"></i>
                        <span>Mulai Sinkronisasi</span>
                    </button>
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
        // =====================================================================
        // FITUR LIVE SEARCH DENGAN DEBOUNCE (Pencarian Cepat & Ringan)
        // 1. Filter Lokal: Menyaring baris yang sedang tampil secara instan di layar.
        // 2. AJAX Server Search: Mengambil data pencarian tabel lengkap dari database 
        //    (melalui master_tabel/cari) setelah jeda ketik 450ms (debounce) agar tidak membebani server.
        // =====================================================================
        var searchTimer;
        $("#inputSearchTabel").on("keyup", function() {
            var value = $(this).val();
            clearTimeout(searchTimer);
            
            // Filter Lokal (instant feedback pada halaman yang sedang dibuka)
            var lowerValue = value.toLowerCase();
            $("#tableMasterBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(lowerValue) > -1)
            });

            // AJAX Global Search ke database jika kata kunci >= 2 karakter
            searchTimer = setTimeout(function() {
                if (value.length >= 2 || value.length == 0) {
                    $.ajax({
                        url: "<?php echo base_URL(); ?>index.php/admin/master_tabel/cari",
                        type: "POST",
                        data: { q: value },
                        success: function(response) {
                            $("#tableMasterBody").html(response);
                            // Sembunyikan pagination saat dalam mode pencarian aktif
                            if (value.length > 0) {
                                $(".pagination-container").hide();
                            } else {
                                window.location.href = "<?php echo base_url('index.php/admin/master_tabel'); ?>";
                            }
                        }
                    });
                }
            }, 450);
        });

        // =====================================================================
        // MODAL KONFIRMASI HAPUS TABEL (AJAX Modal Loader)
        // Memuat konten view konfirmasi penghapusan aman dari controller master_tabel/del/
        // =====================================================================
        $(document).on("click", ".open_modal", function(e) {
            e.preventDefault();
            var m = $(this).attr("id");

            // Tutup dropdown Bootstrap yang aktif agar tidak macet / membeku di layar
            var dropdownBtn = $(this).closest('.dropdown').find('[data-bs-toggle="dropdown"]')[0];
            if (dropdownBtn) {
                var dd = bootstrap.Dropdown.getInstance(dropdownBtn);
                if (dd) {
                    dd.hide();
                }
            }

            // Panggil form dialog konfirmasi hapus via AJAX
            $.ajax({
                url: "<?php echo base_url(); ?>index.php/admin/master_tabel/del/",
                type: "GET",
                data: {
                    delete_id: m
                },
                success: function(ajaxData) {
                    var modalEl = document.getElementById('ModalDelete');
                    $(modalEl).html(ajaxData);
                    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                }
            });
        });

        // Bersihkan isi modal dan backdrop sisa agar halaman tidak freeze/blur saat modal ditutup
        $('#ModalDelete').on('hidden.bs.modal', function () {
            $(this).empty();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
        });
    });



</script>

<?php if ($is_super_admin): ?>
<!-- ========================================================================= -->
<!-- MODAL: DUPLIKASI MASTER TABEL ANTAR-TAHUN (CLONE TAHUNAN)                -->
<!-- Fitur ini menyalin seluruh struktur master tabel dari satu tahun rujukan   -->
<!-- ke tahun publikasi target baru (misal menyalin dari 2026 ke 2027).       -->
<!-- Link tabel tahun berjalan pada tahun baru akan dikosongkan untuk diisi,   -->
<!-- sedangkan link tahun lalu otomatis diambil dari link tahun sumber.         -->
<!-- Endpoint: admin/clone_tahunan                                             -->
<!-- ========================================================================= -->
<!-- Modal Clone Tahunan (Duplikasi Master Tabel) -->
<div class="modal fade" id="ModalCloneTahunan" tabindex="-1" aria-labelledby="ModalCloneTahunanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Header dengan badge icon modern -->
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary" style="width: 48px; height: 48px; font-size: 22px;">
                        <i class="bi bi-copy"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="ModalCloneTahunanLabel">Duplikasi Master Tabel</h5>
                        <p class="text-muted small mb-0" style="font-size: 12px;">Salin seluruh kerangka tabel antar-tahun publikasi DDA</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Form eksekusi duplikasi tahunan -->
            <form action="<?= base_url() ?>index.php/admin/clone_tahunan" method="POST" id="formCloneTahun">
                <div class="modal-body p-4">
                    <!-- Info Alert Card penjelasan cara kerja clone -->
                    <div class="alert alert-info border-0 rounded-3 p-3 mb-4 d-flex align-items-start gap-2 shadow-none" style="background: #f0f7ff; border-left: 4px solid #0284c7 !important;">
                        <i class="bi bi-info-circle-fill fs-5 text-primary mt-0 flex-shrink-0"></i>
                        <div class="text-dark small" style="font-size: 12px; line-height: 1.5;">
                            Fitur ini menyalin <b>seluruh daftar tabel, nomor urut, judul, dan instansi</b> dari tahun acuan ke tahun target baru. Link tahun berjalan pada tahun baru akan dikosongkan, sedangkan link tahun sebelumnya otomatis terisi dari link tahun sumber.
                        </div>
                    </div>

                    <!-- Visual Flow Container: Tahun Sumber -> Panah -> Tahun Tujuan -->
                    <div class="bg-light-subtle rounded-3 p-3 border mb-3">
                        <div class="row g-3 align-items-center">
                            <!-- Dropdown Tahun Sumber yang sudah memiliki data master tabel -->
                            <div class="col-md-5">
                                <label class="form-label text-uppercase text-dark fw-bold mb-1" style="font-size: 11px;">
                                    <i class="bi bi-calendar3 me-1 text-primary"></i> Salin Dari Tahun
                                </label>
                                <select name="tahun_sumber" id="select_tahun_sumber" class="form-select fw-semibold" style="border-radius: 8px;" required>
                                    <?php 
                                    $active_ta = $ta ?? session()->get('admin_ta') ?? date('Y');
                                    if (!empty($tahun_counts)): 
                                        foreach ($tahun_counts as $tc): 
                                    ?>
                                        <option value="<?= $tc->tahun ?>" <?= ($tc->tahun == $active_ta) ? 'selected' : '' ?>>
                                            Tahun <?= $tc->tahun ?> (<?= $tc->cnt ?> tabel)
                                        </option>
                                    <?php 
                                        endforeach; 
                                    else:
                                    ?>
                                        <option value="<?= $active_ta ?>">Tahun <?= $active_ta ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Indikator Panah Alur -->
                            <div class="col-md-2 text-center py-1">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white shadow-sm border text-primary" style="width: 36px; height: 36px;">
                                    <i class="bi bi-arrow-right fs-5 d-none d-md-inline"></i>
                                    <i class="bi bi-arrow-down fs-5 d-inline d-md-none"></i>
                                </div>
                            </div>

                            <!-- Input Tahun Target / Tujuan -->
                            <div class="col-md-5">
                                <label class="form-label text-uppercase text-dark fw-bold mb-1" style="font-size: 11px;">
                                    <i class="bi bi-calendar-plus me-1 text-success"></i> Salin Ke Tahun
                                </label>
                                <input type="number" class="form-control fw-bold text-primary" name="tahun_tujuan" id="input_tahun_tujuan" required placeholder="Contoh: 2027" value="<?= intval($active_ta) + 1 ?>" style="border-radius: 8px;">
                            </div>
                        </div>
                    </div>

                    <div class="text-muted small ps-1" style="font-size: 11px;">
                        <i class="bi bi-shield-check text-success me-1"></i> Data tabel pada tahun sumber tidak akan diubah atau dihapus.
                    </div>
                </div>

                <!-- Tombol Aksi Batal dan Submit Clone -->
                <div class="modal-footer bg-light-subtle border-top py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4 border" style="border-radius: 8px;" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold" style="border-radius: 8px; background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-copy"></i>
                        <span>Proses Duplikasi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RAPAIKAN PENOMORAN TABEL (RESEQUENCE TABEL)                        -->
<!-- Fitur ini merapikan nomor urut tabel yang bolong/melompat pada subbab     -->
<!-- tertentu (misal setelah ada tabel yang dihapus atau disisipkan).           -->
<!-- Contoh: jika urutannya 1.2.1, 1.2.3, maka dirapikan jadi 1.2.1, 1.2.2.     -->
<!-- Endpoint: admin/resequence_tabel                                          -->
<!-- ========================================================================= -->
<!-- Modal Resequence (Rapikan Urutan) -->
<div class="modal fade" id="ModalResequence" tabindex="-1" aria-labelledby="ModalResequenceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning-emphasis" style="width: 48px; height: 48px; font-size: 22px;">
                        <i class="bi bi-sort-numeric-down"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="ModalResequenceLabel">Rapikan Urutan Tabel</h5>
                        <p class="text-muted small mb-0" style="font-size: 12px;">Urutkan kembali penomoran tabel yang melompat di DDA <?= $ta ?? date('Y') ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Form input prefix subbab yang ingin dirapikan -->
            <form action="<?php echo base_url('index.php/admin/resequence_tabel'); ?>" method="post">
                <input type="hidden" name="tahun" value="<?= esc($ta ?? session()->get('admin_ta') ?? date('Y')) ?>">
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 rounded-3 p-3 mb-4 d-flex align-items-start gap-2 shadow-none" style="background: #fffbeb; border-left: 4px solid #f59e0b !important;">
                        <i class="bi bi-info-circle-fill fs-5 text-warning mt-0 flex-shrink-0"></i>
                        <div class="text-dark small" style="font-size: 12px; line-height: 1.5;">
                            Fitur ini akan meratakan ulang nomor urut ujung tabel pada Subbab yang dipilih di tahun berjalan (<b><?= $ta ?? date('Y') ?></b>) secara berurutan agar tidak ada nomor yang melompat atau bolong.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="prefix_resequence" class="form-label fw-bold text-dark small">Awalan Bab / Subbab</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-hash text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-1" name="prefix" id="prefix_resequence" placeholder="Contoh: 1.2 atau 4.1" required style="border-radius: 0 8px 8px 0;">
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Hanya tabel dengan awalan nomor ini yang urutannya dirapikan (misal 1.2.1, 1.2.2, ...).</small>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle border-top py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-4 border" style="border-radius: 8px;" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 shadow-sm text-dark fw-bold d-inline-flex align-items-center gap-2" style="border-radius: 8px;">
                        <i class="bi bi-check2-circle"></i>
                        <span>Jalankan Resequence</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KOSONGKAN / RESET MASTER TABEL TAHUN TERTENTU (ZONA BAHAYA)         -->
<!-- Fitur ini menghapus seluruh keterhubungan tabel pada tahun anggaran tertentu-->
<!-- dari tabel pivot t_tahun_tabel. Kamus induk m_list_tabel tetap aman.      -->
<!-- Dilengkapi proteksi type-to-confirm (mengetik "HAPUS [TAHUN]") sebelum    -->
<!-- tombol submit merah dapat diklik, untuk mencegah klik tidak sengaja.       -->
<!-- Endpoint: admin/reset_tahun                                               -->
<!-- ========================================================================= -->
<!-- Modal Reset / Kosongkan Tabel Tahunan (Danger Zone) -->
<div class="modal fade" id="ModalResetTahun" tabindex="-1" aria-labelledby="ModalResetTahunLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 px-4 border-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <h5 class="modal-title text-white fw-bold mb-0" id="ModalResetTahunLabel">
                        Kosongkan Master Tabel Tahunan
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo base_url('index.php/admin/reset_tahun'); ?>" method="post" id="formResetTahun">
                <div class="modal-body p-4">
                    <div class="alert bg-danger-soft text-danger border-danger border-1 border-opacity-25 rounded-3 mb-4 p-3 d-flex align-items-start gap-2 shadow-none">
                        <i class="bi bi-shield-slash-fill fs-5 mt-1 flex-shrink-0"></i>
                        <div class="small" style="line-height: 1.5;">
                            <strong>ZONA BAHAYA / PERINGATAN KERAS:</strong><br>
                            Tindakan ini akan <u>menghapus seluruh tabel</u> yang terdaftar pada tahun yang Anda pilih dari DDA (termasuk status konfirmasi, catatan, dan link portal). Kamus induk master tabel tidak akan terhapus. Tindakan ini <strong>permanen dan tidak dapat dibatalkan</strong>.
                        </div>
                    </div>

                    <!-- Pilihan tahun target yang ingin dikosongkan -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-sm">Pilih Tahun yang Ingin Dikosongkan:</label>
                        <select name="target_tahun" id="target_tahun_reset" class="form-select border-danger p-2" style="border-radius: 8px;" required>
                            <?php 
                            $active_ta = $ta ?? session()->get('admin_ta') ?? date('Y');
                            if (!empty($tahun_counts)): 
                                foreach ($tahun_counts as $tc): 
                            ?>
                                <option value="<?php echo $tc->tahun; ?>" data-count="<?php echo $tc->cnt; ?>" <?php echo ($tc->tahun == $active_ta) ? 'selected' : ''; ?>>
                                    Tahun <?php echo $tc->tahun; ?> (<?php echo $tc->cnt; ?> tabel terdaftar)
                                </option>
                            <?php 
                                endforeach; 
                            endif; 
                            ?>
                        </select>
                    </div>

                    <!-- Input type-to-confirm untuk keamanan ekstra -->
                    <div class="mb-3">
                        <label class="form-label text-xs font-weight-bold text-uppercase text-secondary mb-1">
                            Ketik <span class="badge bg-danger text-white px-2 py-1 font-monospace" id="textPromptConfirm">HAPUS <?php echo $active_ta; ?></span> untuk membuka tombol:
                        </label>
                        <input type="text" class="form-control p-2 border-danger font-monospace" id="inputConfirmReset" name="confirm_text" placeholder="Ketik teks konfirmasi di sini..." autocomplete="off" required style="border-radius: 8px;">
                        <small class="text-muted" style="font-size: 11px;">Huruf besar/kecil tidak berpengaruh, namun teks harus persis.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle border-top py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-light px-4 border" style="border-radius: 8px;" data-bs-dismiss="modal">Batal</button>
                    <!-- Tombol disabled secara default, baru aktif bila ketikan persis -->
                    <button type="submit" id="btnExecuteReset" class="btn btn-danger px-4 shadow-sm d-inline-flex align-items-center gap-2" style="border-radius: 8px;" disabled>
                        <i class="bi bi-trash3-fill"></i>
                        <span>Hapus Permanen <span id="labelJumlahTabel"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// =========================================================================
// SCRIPT: INTERAKTIFITAS RESET TAHUN & SINKRONISASI TAHUN CLONE
// 1. Mengubah teks prompt konfirmasi (misal: "HAPUS 2026") saat tahun di-select.
// 2. Memvalidasi kecocokan ketikan input user secara real-time.
// 3. Mengaktifkan tombol merah jika teks sudah sesuai.
// 4. Mengatur tahun target clone = tahun sumber + 1 secara otomatis.
// =========================================================================
document.addEventListener("DOMContentLoaded", function() {
    const selectTahun = document.getElementById("target_tahun_reset");
    const promptConfirm = document.getElementById("textPromptConfirm");
    const inputConfirm = document.getElementById("inputConfirmReset");
    const btnExecute = document.getElementById("btnExecuteReset");
    const labelJumlah = document.getElementById("labelJumlahTabel");

    // Perbarui teks instruksi konfirmasi sesuai tahun yang dipilih pada dropdown
    function updateResetPrompt() {
        if (!selectTahun) return;
        const selectedOpt = selectTahun.options[selectTahun.selectedIndex];
        const tahun = selectedOpt ? selectedOpt.value : "";
        const count = selectedOpt ? selectedOpt.getAttribute("data-count") : "0";
        
        const expectedText = "HAPUS " + tahun;
        if (promptConfirm) promptConfirm.textContent = expectedText;
        if (labelJumlah) labelJumlah.textContent = "(" + count + " Tabel)";

        checkInput();
    }

    // Periksa apakah teks yang diketikkan user persis sama dengan kata kunci konfirmasi
    function checkInput() {
        if (!selectTahun || !inputConfirm || !btnExecute) return;
        const selectedOpt = selectTahun.options[selectTahun.selectedIndex];
        const tahun = selectedOpt ? selectedOpt.value : "";
        const expectedText = "HAPUS " + tahun;
        
        // Bandingkan secara case-insensitive
        if (inputConfirm.value.trim().toUpperCase() === expectedText.toUpperCase()) {
            btnExecute.removeAttribute("disabled");
            btnExecute.classList.add("shadow-lg");
        } else {
            btnExecute.setAttribute("disabled", "disabled");
            btnExecute.classList.remove("shadow-lg");
        }
    }

    // Event listener perubahan pilihan tahun di dropdown reset
    if (selectTahun) {
        selectTahun.addEventListener("change", function() {
            if (inputConfirm) inputConfirm.value = "";
            updateResetPrompt();
        });
    }

    // Event listener pengetikan teks konfirmasi
    if (inputConfirm) {
        inputConfirm.addEventListener("input", checkInput);
    }

    // Auto sinkronisasi tahun tujuan di Modal Clone (+1 tahun dari tahun sumber)
    const selectSumber = document.getElementById("select_tahun_sumber");
    const inputTujuan = document.getElementById("input_tahun_tujuan");
    if (selectSumber && inputTujuan) {
        selectSumber.addEventListener("change", function() {
            const val = parseInt(this.value);
            if (!isNaN(val)) {
                inputTujuan.value = val + 1;
            }
        });
    }

    // Inisialisasi awal prompt reset
    updateResetPrompt();
});
</script>
<?php endif; ?>