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
                        <ul class="dropdown-menu shadow border border-light border-radius-lg p-2 mt-1" aria-labelledby="dropdownPengelolaanTahun" style="min-width: 260px; z-index: 1050;">
                            <li><h6 class="dropdown-header text-uppercase text-xxs font-weight-bolder text-secondary px-2 py-1">Operasi Penomoran</h6></li>
                            <!-- Rapikan Penomoran Tabel yang Melompat pada Bab Tertentu -->
                            <li>
                                <a class="dropdown-item border-radius-md py-2 d-flex align-items-center" href="javascript:;" data-bs-toggle="modal" data-bs-target="#ModalResequence">
                                    <div class="icon icon-shape icon-xs rounded-circle text-center me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: #f1f5f9; border: 1px solid #e2e8f0;">
                                        <i class="bi bi-sort-numeric-down text-secondary"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-xs font-weight-bold text-dark">Rapikan Urutan</span>
                                        <span class="text-xxs text-secondary">Resequence nomor urut tabel</span>
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
                                    <?php
                                    $thDataRow = !empty($b->tahun_tampil) ? $b->tahun_tampil : (!empty($b->tahun_tersedia) ? $b->tahun_tersedia : (!empty($b->tahun) ? (int)$b->tahun - 1 : (!empty($ta) ? (int)$ta - 1 : date('Y') - 1)));
                                    $rowJudulInd = stripos($b->judul_ind ?? '', '[PERIODE]') !== false ? str_ireplace('[PERIODE]', (string)$thDataRow, $b->judul_ind) : $b->judul_ind;
                                    $rowJudulEnRaw = $b->judul_en ?: $b->judul_ind;
                                    $rowJudulEn = stripos($rowJudulEnRaw ?? '', '[PERIODE]') !== false ? str_ireplace('[PERIODE]', (string)$thDataRow, $rowJudulEnRaw) : $rowJudulEnRaw;
                                    ?>
                                    <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="max-width:350px; line-height: 1.5;"><?php echo (!empty($b->no_tabel) ? esc($b->no_tabel) . ' ' : '') . esc($rowJudulInd); ?></h6>
                                    <p class="text-xxs text-secondary mb-0 font-italic text-wrap mt-1" style="max-width:350px; line-height: 1.2;"><?php echo (!empty($b->no_tabel) ? esc($b->no_tabel) . ' ' : '') . esc($rowJudulEn); ?></p>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <?php 
                                        $is_dyn = (($b->jenis_sumber ?? '') === 'dinamis') || (empty($b->jenis_sumber) && !empty($b->is_dynamic));
                                        $id_gen_url = (int)$b->id;
                                        $api_uuid = trim((string)($b->id_api ?? ''));
                                        $api_link = trim((string)($b->link_api ?? ($b->link_tabel ?? '')));
                                        if ($is_dyn): 
                                        ?>
                                            <a href="<?php echo base_url('admin/generator_tabel/' . $id_gen_url); ?>" class="badge badge-sm text-white border-0 align-self-start fw-bold shadow-xs text-decoration-none" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); font-size: 10px;" title="Klik untuk mengelola matriks dinamis">
                                                <i class="bi bi-boxes me-1"></i>🧩 TABEL DINAMIS
                                            </a>
                                            <?php if (!empty($b->id_indikator)): ?>
                                                <span class="text-xxs text-success fw-semibold mt-0.5"><i class="bi bi-check-circle-fill me-1"></i>Matriks Siap</span>
                                            <?php else: ?>
                                                <span class="text-xxs text-warning fw-semibold mt-0.5"><i class="bi bi-exclamation-circle me-1"></i>Belum Dirakit</span>
                                            <?php endif; ?>
                                        <?php elseif (!empty($api_uuid) || !empty($api_link)): ?>
                                            <span class="badge badge-sm bg-primary-soft text-primary border-primary-soft align-self-start fw-bold" style="font-size: 10px;">
                                                <i class="bi bi-cloud-check me-1"></i>🔗 API KOMINFO
                                            </span>
                                            <code class="text-xxs text-secondary text-truncate d-block mt-1" style="max-width: 190px;" title="<?php echo esc($api_uuid ?: $api_link); ?>"><?php echo esc($api_uuid ?: str_replace('index.php/admin/', '', $api_link)); ?></code>
                                        <?php else: ?>
                                            <span class="badge badge-sm bg-light text-secondary border align-self-start fw-semibold" style="font-size: 10px;">
                                                <i class="bi bi-dash-circle me-1"></i>🔗 API (BELUM DIISI)
                                            </span>
                                            <span class="text-xxs text-muted fst-italic mt-1">Belum ada UUID API</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-xs font-weight-bold text-dark lh-sm"><?php echo esc($b->unitkerja_ind); ?></span>
                                        <span class="text-xxs text-secondary opacity-7 mt-1 text-uppercase fw-bold"><?php echo esc($b->user_wali ?: 'NASIONAL'); ?></span>
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
                                                    <i class="bi bi-pencil me-2 text-secondary"></i> Edit Master
                                                </a>
                                            </li>
                                            <?php if ($is_dyn): ?>
                                            <li>
                                                <a href="<?php echo base_url('admin/generator_tabel/' . $id_gen_url); ?>" class="dropdown-item border-radius-md py-2 text-sm text-primary fw-semibold">
                                                    <i class="bi bi-boxes me-2"></i> Generator Matriks
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?php echo base_url('admin/view_tabel_dinamis/' . $id_gen_url); ?>" class="dropdown-item border-radius-md py-2 text-sm text-info fw-semibold">
                                                    <i class="bi bi-table me-2"></i> Lihat Tabel
                                                </a>
                                            </li>
                                            <?php elseif (!empty($api_uuid) || !empty($api_link)): ?>
                                            <li>
                                                <?php 
                                                $target_portal_url = !empty($api_link) 
                                                    ? (stripos($api_link, 'http') === 0 ? $api_link : base_url($api_link)) 
                                                    : base_url('admin/view_portal_tabel?id=' . urlencode($api_uuid));
                                                if (strpos($target_portal_url, 'view_portal_tabel') !== false && strpos($target_portal_url, 'table_id=') === false) {
                                                    $target_portal_url .= (strpos($target_portal_url, '?') !== false ? '&' : '?') . 'table_id=' . $b->id;
                                                }
                                                ?>
                                                <a href="<?php echo $target_portal_url; ?>" class="dropdown-item border-radius-md py-2 text-sm text-info fw-semibold">
                                                    <i class="bi bi-table me-2"></i> Lihat Tabel API
                                                </a>
                                            </li>
                                            <?php endif; ?>
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
                <input type="hidden" name="tahun" value="<?= esc($ta ?? session()->get('admin_ta') ?? date('Y')) ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="alert bg-gray-100 border-0 text-dark text-sm mb-4">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                        Gunakan file CSV mapping untuk memperbarui tautan tabel secara massal ke Portal Data Jawa Tengah pada DDA <b>Tahun <?= esc($ta ?? session()->get('admin_ta') ?? date('Y')) ?></b>.
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
                            <input type="text" class="form-control border-start-0 ps-1" name="prefix" id="prefix_resequence" placeholder="Contoh: 1.2 (Subbab) atau 8, 11 (Bab 2-tingkat)" required style="border-radius: 0 8px 8px 0;">
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Hanya tabel dengan awalan nomor ini yang urutannya dirapikan (misal 1.2.1, 1.2.2 atau 8.1, 8.2).</small>
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