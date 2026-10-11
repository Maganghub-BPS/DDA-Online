<?php echo session()->getFlashdata("k"); ?>

<!-- Consolidated Control Card (Format 1: Serasi dengan Master OPD) -->
<div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
    <div class="card-header pb-3 pt-4 px-4 bg-white border-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="mb-0 font-weight-bolder text-dark">Master Tim</h4>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="inputSearchTim" class="form-control bg-transparent border-0 ps-0 text-sm" placeholder="Cari tim, anggota..." style="box-shadow: none;">
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-4 border-radius-lg mb-0 shadow-none d-flex align-items-center" 
                            data-bs-toggle="modal" data-bs-target="#modalTambahTim" style="height: 40px; white-space: nowrap;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Tim
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body px-4 pt-0 pb-4">
        <div class="table-responsive rounded-3 border border-light overflow-hidden">
            <table class="table table-hover align-items-center mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        <th width="60" class="text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">No</th>
                        <th width="120" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Kode Tim</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Nama Tim</th>
                        <th width="130" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">OPD Binaan</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 ps-3" style="min-width: 280px;">Anggota Tim (LO BPS)</th>
                        <th width="150" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableTimBody" class="divide-y divide-gray-100 bg-white">
                    <?php 
                    if (empty($teams)) {
                        echo "<tr><td colspan='6' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Belum ada data tim terdaftar</td></tr>";
                    } else {
                        $no = 1;
                        foreach ($teams as $b) {
                            $count_members = count($b->members);
                            $count_opds = count($b->opds);
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors tim-row">
                        <td class="text-center font-weight-bold text-secondary text-sm">
                            <?php echo $no++; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-gray-100 text-dark font-weight-bold px-3 py-2 border-radius-md border border-light">
                                <?php echo strtoupper($b->kode_tim); ?>
                            </span>
                        </td>
                        <td class="py-3">
                            <h6 class="mb-0 text-sm font-weight-bold text-dark text-wrap" style="line-height: 1.5;">
                                <?php echo htmlspecialchars($b->nama); ?>
                            </h6>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-light text-muted border font-weight-bold px-2.5 py-1.5 border-radius-md">
                                <i class="bi bi-building me-1"></i><?php echo $count_opds; ?> OPD
                            </span>
                        </td>
                        <td class="ps-3 py-2">
                            <?php if (empty($b->members)): ?>
                                <span class="text-muted text-xs fst-italic">Belum ada anggota</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($b->members as $m): ?>
                                        <span class="badge badge-sm bg-primary-soft text-primary font-weight-bold px-2.5 py-1.5 border-radius-md border-primary-soft d-inline-flex align-items-center" style="font-size: 11px;">
                                            <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($m->nama); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1.5 justify-content-center align-items-center">
                                <!-- Tombol Kelola Anggota -->
                                <button type="button" class="btn btn-outline-primary btn-sm px-2.5 py-1.5 border-radius-lg shadow-none d-flex align-items-center gap-1"
                                        data-bs-toggle="modal" data-bs-target="#modalKelolaAnggota-<?php echo $b->id; ?>"
                                        title="Kelola Anggota Tim">
                                    <i class="bi bi-people-fill"></i>
                                    <span style="font-size: 11.5px; font-weight: 600;">Kelola (<?php echo $count_members; ?>)</span>
                                </button>

                                <!-- Tombol Hapus Tim -->
                                <?php if ($count_opds > 0): ?>
                                    <button type="button" class="btn btn-icon-only btn-outline-secondary btn-sm border-radius-lg shadow-none" 
                                            data-bs-toggle="tooltip" title="Tim membina <?php echo $count_opds; ?> OPD, tidak dapat dihapus"
                                            style="cursor: not-allowed; opacity: 0.5;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo base_url('admin/master_tim/act_del_tim/' . $b->id); ?>" 
                                       class="btn btn-icon-only btn-outline-danger btn-sm border-radius-lg shadow-none" 
                                       title="Hapus Tim" 
                                       onclick="return confirm('Anda yakin ingin menghapus <?php echo htmlspecialchars(addslashes($b->nama)); ?>?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>                  
                        </td>
                    </tr>
                    <?php 
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL KELOLA ANGGOTA (Dibuat untuk masing-masing Tim)    -->
<!-- ======================================================== -->
<?php if (!empty($teams)): ?>
    <?php foreach ($teams as $b): ?>
        <div class="modal fade" id="modalKelolaAnggota-<?php echo $b->id; ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg border-radius-2xl overflow-hidden">
                    <div class="modal-header bg-white border-0 pt-4 px-4 pb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-shape bg-primary-soft text-primary d-flex align-items-center justify-content-center border-radius-md" style="width: 42px; height: 42px;">
                                <i class="bi bi-people-fill fs-5"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 font-weight-bolder text-dark">Kelola Anggota: <?php echo htmlspecialchars($b->nama); ?></h5>
                                <span class="badge bg-gray-100 text-dark border px-2 py-0.5 text-xs">Kode: <?php echo htmlspecialchars($b->kode_tim); ?></span>
                            </div>
                        </div>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 pt-2">
                        <!-- Form Tambah Anggota Baru ke Tim Ini -->
                        <div class="card bg-gray-100 border-0 border-radius-lg p-3 mb-4">
                            <label class="form-label text-xs fw-bold text-dark mb-2">
                                <i class="bi bi-person-plus-fill text-primary me-1"></i> Tambah Pegawai (LO BPS) ke Tim Ini:
                            </label>
                            <form action="<?php echo base_url('admin/master_tim/act_add_anggota'); ?>" method="post" class="row g-2 align-items-center">
                                <input type="hidden" name="kode_tim" value="<?php echo htmlspecialchars($b->kode_tim); ?>">
                                <div class="col-sm-9">
                                    <select name="id_admin" class="form-select border-0 text-sm py-2 px-3 bg-white border-radius-md shadow-none" required>
                                        <option value="">-- Pilih Staf LO BPS --</option>
                                        <?php if (!empty($staff_bps)): ?>
                                            <?php foreach ($staff_bps as $s): ?>
                                                <?php 
                                                $is_assigned = !empty($s->assigned_tim);
                                                $tim_label = !empty($s->assigned_tim_nama) ? $s->assigned_tim_nama : strtoupper($s->assigned_tim ?? '');
                                                ?>
                                                <option value="<?php echo $s->id; ?>" <?php echo $is_assigned ? 'disabled class="text-muted bg-light"' : ''; ?>>
                                                    <?php echo htmlspecialchars($s->nama); ?> 
                                                    <?php if ($is_assigned): ?>
                                                        — [Sudah di <?php echo htmlspecialchars($tim_label); ?>]
                                                    <?php else: ?>
                                                        — [Tersedia]
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <button type="submit" class="btn btn-primary btn-sm w-100 py-2 border-radius-md shadow-none mb-0">
                                        <i class="bi bi-plus-lg me-1"></i> Tambahkan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Tabel Anggota Saat Ini -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-xs text-uppercase font-weight-bolder text-secondary ls-1 mb-0">
                                Daftar Anggota Tim (<?php echo count($b->members); ?> LO)
                            </h6>
                        </div>

                        <div class="table-responsive rounded-3 border border-light overflow-hidden" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                                <thead class="bg-gray-100 sticky-top" style="z-index: 1;">
                                    <tr>
                                        <th width="40" class="text-center py-2 text-secondary text-xxs font-weight-bolder">No</th>
                                        <th class="py-2 text-secondary text-xxs font-weight-bolder">Nama Pegawai (LO)</th>
                                        <th width="180" class="py-2 text-secondary text-xxs font-weight-bolder">NIP / Username</th>
                                        <th width="80" class="text-center py-2 text-secondary text-xxs font-weight-bolder">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($b->members)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted small fst-italic">
                                                Belum ada anggota terdaftar di tim ini. Gunakan form di atas untuk menambahkan.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no_m = 1; foreach ($b->members as $m): ?>
                                            <tr>
                                                <td class="text-center text-muted fw-semibold"><?php echo $no_m++; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-primary-soft text-primary d-flex align-items-center justify-content-center fw-bold me-2" 
                                                             style="width: 28px; height: 28px; font-size: 11px;">
                                                            <?php echo strtoupper(substr(trim($m->nama), 0, 1)); ?>
                                                        </div>
                                                        <div class="fw-bold text-dark lh-sm"><?php echo htmlspecialchars($m->nama); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-secondary small font-monospace"><?php echo htmlspecialchars($m->nip ?: '-'); ?></div>
                                                    <span class="badge bg-light text-muted border px-1.5 py-0" style="font-size: 10px;">@<?php echo htmlspecialchars($m->username); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="<?php echo base_url('admin/master_tim/act_del_anggota/' . $m->rel_id); ?>" 
                                                       class="btn btn-icon-only btn-outline-danger btn-sm border-radius-lg p-1 shadow-none"
                                                       onclick="return confirm('Keluarkan <?php echo htmlspecialchars(addslashes($m->nama)); ?> dari tim <?php echo htmlspecialchars(addslashes($b->kode_tim)); ?>?');"
                                                       data-bs-toggle="tooltip" title="Keluarkan dari Tim">
                                                        <i class="bi bi-x-circle fs-6"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-end">
                        <button type="button" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none font-weight-bold" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- ======================================================== -->
<!-- MODAL TAMBAH TIM BARU                                   -->
<!-- ======================================================== -->
<div class="modal fade" id="modalTambahTim" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg border-radius-2xl overflow-hidden">
            <div class="modal-header bg-white border-0 pt-4 px-4 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-shape bg-primary text-white d-flex align-items-center justify-content-center border-radius-md" style="width: 40px; height: 40px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bolder text-dark">Buat Tim Baru</h5>
                        <p class="text-xs text-secondary mb-0">Daftarkan kelompok kerja operasional baru</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?php echo base_url('admin/master_tim/act_add_tim'); ?>" method="post">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1 text-uppercase ls-1">Nama Tim</label>
                        <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                            <span class="input-group-text bg-white border-0"><i class="bi bi-people text-primary"></i></span>
                            <input type="text" name="nama_tim" required class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Contoh: Tim 7 Jawa Tengah Dalam Angka" autofocus>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-xxs font-weight-bolder text-secondary mb-1 ps-1 text-uppercase ls-1">Kode Tim (Username)</label>
                        <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                            <span class="input-group-text bg-white border-0"><i class="bi bi-at text-primary"></i></span>
                            <input type="text" name="kode_tim" required class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Contoh: tim7">
                        </div>
                        <div class="form-text text-xxs text-secondary mt-1 ps-1">
                            <i class="bi bi-info-circle me-1"></i> Kode unik tanpa spasi, otomatis digunakan untuk penugasan di Master OPD.
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none font-weight-bold" data-bs-dismiss="modal">Batalkan</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 border-radius-lg mb-0 shadow-none font-weight-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> Simpan Tim Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.92rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.4rem !important; }
    
    .bg-gray-100 { background-color: #f8f9fa !important; }
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.08) !important; color: #0dcaf0 !important; }
    .border-info-soft { border: 1px solid rgba(13, 202, 240, 0.2) !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.95rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .input-group-alternative {
        transition: all 0.2s ease;
        background-color: #fff;
    }
    .input-group-alternative:focus-within {
        border-color: #0d6efd !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
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

<script type="text/javascript">
$(document).ready(function() {
    // Inisialisasi Tooltip Bootstrap 5
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Real-time Client-side Search untuk Tabel Tim
    $('#inputSearchTim').on('input', function() {
        var keyword = $(this).val().toLowerCase().trim();
        $('.tim-row').each(function() {
            var rowText = $(this).text().toLowerCase();
            if (keyword === '' || rowText.indexOf(keyword) > -1) {
                $(this).removeClass('d-none').show();
            } else {
                $(this).addClass('d-none').hide();
            }
        });
    });
});
</script>
