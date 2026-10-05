<?php
/**
 * VIEW: PERSETUJUAN & KONFIRMASI USULAN TABEL OPD (f_add_opd.php)
 * Digunakan oleh BPS / Admin Provinsi untuk:
 * 1. Memeriksa usulan tabel baru yang diajukan oleh dinas/OPD.
 * 2. Mendeteksi apakah usulan OPD ini identik/serupa dengan tabel yang sudah pernah ada di Master Tabel DDA (Fuzzy Match Recommendation).
 * 3. Menautkan usulan ke Master ID historis agar tidak terjadi duplikasi tabel di database.
 * 4. Menetapkan nomor urut tabel dan menyelesaikan bentrok nomor tabel (Shift vs Kolaborasi) sebelum masuk ke DDA tahun aktif.
 */

// Inisialisasi tanggal dan mode form
$today = getdate();
$tahunsekarang = $today['year'];

$mode = segment_safe(3); // Membaca mode dari URL: "edt" (verifikasi usulan) atau "add" (tambah usulan manual)

if ($mode == "edt" || $mode == "act_edt") {
    // Mode Konfirmasi/Edit Usulan OPD: Mengambil data usulan yang diajukan OPD
    $act        = "act_edt";
    $page_title = "Konfirmasi Usulan Tabel Baru";
    $idp        = $datpil->id;
    $judul_ind  = $datpil->judul_ind;
    $judul_en   = $datpil->judul_en;
    $id_unitkerja = $datpil->id_unitkerja;
    $file_tabel = $datpil->file_tabel;
} else {
    // Mode Tambah Usulan Baru
    $act        = "act_add";
    $page_title = "Tambah Usulan Master Tabel";
    $idp        = "";
    $judul_ind  = "";
    $judul_en   = "";
    $id_unitkerja = session()->get('admin_unitkerja');
    $file_tabel = "";
}
// Tahun anggaran target (DDA yang sedang aktif dikerjakan)
$ta = $ta ?? session()->get('admin_ta') ?? $tahunsekarang;
?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel_opd" class="btn btn-sm btn-light border-0 shadow-none bg-light-subtle d-flex align-items-center justify-content-center" style="border-radius: 10px; width: 40px; height: 40px;"><i class="bi bi-arrow-left fs-5"></i></a>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-patch-question-fill me-2 text-primary"></i><?php echo $page_title; ?>
                    </h5>
                </div>
            </div>
            
            <div class="card-body p-4">
                <form id="form-add-opd" action="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                
                <input type="hidden" name="idp" value="<?php echo $idp; ?>">
                <input type="hidden" name="conflict_resolution" id="conflict_resolution" value="">
                <input type="hidden" name="ta_target" id="ta_target" value="<?php echo $ta; ?>">

                <div class="alert bg-primary-soft text-primary border-0 rounded-4 mb-4 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                    <div>
                        <span class="fw-bold d-block">Peninjauan Usulan Tabel OPD</span>
                        <small>Periksa dan lengkapi rincian tabel sebelum menyetujui usulan ini masuk ke Master Tabel DDA <b>Tahun <?php echo $ta; ?></b>.</small>
                    </div>
                </div>

                <?php if (!empty($matched_master)): ?>
                <!-- ========================================================================= -->
                <!-- KARTU REKOMENDASI MASTER TABEL SERUPA (FUZZY MATCHING RECOMMENDATION)     -->
                <!-- Sistem secara cerdas membandingkan judul usulan OPD dengan seluruh kamus   -->
                <!-- master tabel m_list_tabel untuk menemukan kandidat yang identik/mirip.     -->
                <!-- Tujuannya: Mencegah penambahan master tabel baru yang duplikat!            -->
                <!-- ========================================================================= -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #fffbf5 0%, #fff7ed 100%); border: 1.5px solid #fed7aa !important; border-left: 6px solid #f97316 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold" style="font-size: 11px;">
                                    <i class="bi bi-lightbulb-fill text-dark me-1"></i> REKOMENDASI MASTER TABEL TERDETEKSI
                                </span>
                                <!-- Skor Kemiripan String (%) hasil perhitungan Levenshtein / Token Intersect -->
                                <span class="badge bg-white text-secondary border px-2 py-1 rounded-pill" style="font-size: 11px;">
                                    Tingkat Kemiripan: <?= $matched_master->similarity ?>%
                                </span>
                            </div>
                            <!-- Status apakah master tabel ini sudah terdaftar pada tahun berjalan -->
                            <?php if ($matched_master->is_active_current_year): ?>
                                <span class="badge bg-warning text-dark border border-warning px-3 py-2 rounded-pill" style="font-size: 11px;">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Sudah Terdaftar di DDA <?= $ta ?> (No: <?= $matched_master->no_tabel_current_year ?: '-' ?>)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success text-white px-3 py-2 rounded-pill" style="font-size: 11px;">
                                    <i class="bi bi-check-circle-fill me-1"></i> Siap Ditautkan ke DDA <?= $ta ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Detail Master Eksisting Rujukan -->
                        <div class="mb-2">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">Master Tabel di Database:</span>
                            <h6 class="fw-bold text-dark mt-1 mb-1" style="font-size: 1rem;">
                                <span class="badge bg-secondary text-white me-1">ID #<span id="display_matched_id"><?= $matched_master->id ?></span></span>
                                <span id="display_matched_judul"><?= esc($matched_master->judul_ind) ?></span>
                            </h6>
                            <div class="text-muted small fst-italic mb-2" id="display_matched_judul_en" style="<?= empty($matched_master->judul_en) ? 'display: none;' : '' ?>">
                                <i class="bi bi-translate me-1"></i> <span id="display_matched_judul_en_text"><?= esc($matched_master->judul_en ?? '') ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2 align-items-center text-muted small mb-3">
                            <span class="badge bg-white text-dark border px-2 py-1">
                                <i class="bi bi-building me-1 text-primary"></i> <span id="display_matched_opd"><?= esc($matched_master->unitkerja_ind ?? $matched_master->id_unitkerja) ?></span>
                            </span>
                            <span class="badge bg-white text-dark border px-2 py-1">
                                <i class="bi bi-clock-history me-1 text-primary"></i> Riwayat Terbit DDA: <b><span id="display_matched_terbit"><?= esc($matched_master->tahun_terbit ?: 'Belum pernah terbit') ?></span></b>
                            </span>
                            <?php if (!empty($matched_master->intersect_words)): ?>
                            <span class="badge bg-white text-muted border px-2 py-1">
                                <i class="bi bi-tag me-1"></i> Kata Kunci: <?= esc($matched_master->intersect_words) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Switch Toggle: Pilihan Menautkan ke Master ID Eksisting vs Buat Master Baru -->
                        <div class="bg-white p-3 rounded-3 border mb-3 shadow-none">
                            <div class="form-check form-switch mb-1 d-flex align-items-center">
                                <input class="form-check-input mt-0 me-3" type="checkbox" name="link_to_master" value="1" id="linkToMasterSwitch" style="cursor: pointer; width: 2.75em; height: 1.4em;">
                                <input type="hidden" name="link_to_master_id" id="input_link_to_master_id" value="<?= $matched_master->id ?>">
                                <label class="form-check-label fw-bold text-dark" for="linkToMasterSwitch" style="cursor: pointer; font-size: 0.9rem;">
                                    Tautkan Usulan Ini ke Master ID #<span id="label_matched_id"><?= $matched_master->id ?></span> (Mencegah Duplikasi)
                                </label>
                            </div>
                            <small class="text-muted d-block ms-5" style="font-size: 11px;">
                                <i class="bi bi-info-circle me-1 text-primary"></i> Jika dicentang, usulan ini akan didaftarkan ke DDA <?= $ta ?> menggunakan ID Master yang sama dengan data historis. Hilangkan centang jika Anda memang berniat membuat entri Master Tabel baru.
                            </small>
                        </div>

                        <!-- Tombol Sinkronisasi Judul dan Alternatif Kandidat Lain -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-dark bg-white shadow-none" id="btn_sync_master_data" style="font-size: 12px; border-radius: 8px;">
                                <i class="bi bi-arrow-repeat me-1 text-primary"></i> Terapkan Judul Master ke Form Input
                            </button>
                            <?php if (!empty($other_matches)): ?>
                            <button class="btn btn-sm btn-link text-decoration-none text-muted p-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOtherMatches" aria-expanded="false" style="font-size: 12px;">
                                <i class="bi bi-chevron-down me-1"></i> Lihat Alternatif Master Lain (<?= count($other_matches) ?> kandidat)
                            </button>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($other_matches)): ?>
                        <div class="collapse mt-3" id="collapseOtherMatches">
                            <div class="bg-white p-3 rounded-3 border">
                                <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 11px;">Pilihan Master Tabel Alternatif:</div>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($other_matches as $om): ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light-subtle border gap-2">
                                        <div class="text-truncate">
                                            <span class="badge bg-secondary text-white me-1">#<?= $om->id ?></span>
                                            <span class="fw-semibold text-dark small"><?= esc($om->judul_ind) ?></span>
                                            <div class="text-muted" style="font-size: 11px;">
                                                Instansi: <?= esc($om->unitkerja_ind ?? $om->id_unitkerja) ?> | Terbit: <?= esc($om->tahun_terbit ?: '-') ?> | Kemiripan: <?= $om->similarity ?>%
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-select-alt-master text-nowrap flex-shrink-0" 
                                                data-id="<?= $om->id ?>" 
                                                data-judul-ind="<?= esc($om->judul_ind) ?>" 
                                                data-judul-en="<?= esc($om->judul_en ?? '') ?>"
                                                data-opd="<?= esc($om->unitkerja_ind ?? $om->id_unitkerja) ?>"
                                                data-terbit="<?= esc($om->tahun_terbit ?: '-') ?>"
                                                style="font-size: 11px; padding: 3px 10px; border-radius: 6px;">
                                            Pilih Ini
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Judul Bahasa Indonesia</label>
                            <textarea name="judul_ind" tabindex="1" required class="form-control border-radius-lg p-3" rows="3" placeholder="Ketik judul tabel bahasa Indonesia..."><?php echo $judul_ind; ?></textarea>
                            <small class="text-muted" style="font-size: 11px;">Periode akan otomatis digabungkan di akhir judul.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Judul Bahasa Inggris</label>
                            <textarea name="judul_en" tabindex="2" class="form-control border-radius-lg p-3" rows="3" placeholder="Type english table title..."><?php echo $judul_en; ?></textarea>
                        </div>

                        <?php if ($mode == "edt" || $mode == "act_edt"): ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Instansi Pengusul / Penanggung Jawab</label>
                            <select name="id_unitkerja" class="form-select border-radius-lg p-3" style="font-size: 0.9rem;">
                                <?php if (!empty($list_opd)): ?>
                                    <?php foreach ($list_opd as $opd): ?>
                                        <option value="<?php echo $opd->id_unitkerja; ?>" <?php echo (strtolower(trim($opd->id_unitkerja)) == strtolower(trim($id_unitkerja))) ? 'selected' : ''; ?>>
                                            <?php echo $opd->unitkerja_ind; ?> (<?php echo $opd->id_unitkerja; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="<?php echo $id_unitkerja; ?>" selected><?php echo $id_unitkerja; ?></option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Nomor Tabel (Opsional)</label>
                                <input type="text" name="no_tabel" id="input_no_tabel" class="form-control border-radius-lg p-3" placeholder="Contoh: 4.1.19">
                                <div id="no_tabel_opd_suggestion" style="display: none;"></div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Bisa dikosongkan untuk diratakan nanti.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Periode Tabel (ID)</label>
                                <input type="text" name="periode_id" class="form-control border-radius-lg p-3" placeholder="Contoh: <?php echo $ta; ?>" value="<?php echo $ta; ?>">
                                <small class="text-muted" style="font-size: 11px;">Periode Bahasa Indonesia.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Period (EN)</label>
                                <input type="text" name="periode_en" class="form-control border-radius-lg p-3" placeholder="Example: <?php echo $ta; ?>" value="<?php echo $ta; ?>">
                                <small class="text-muted" style="font-size: 11px;">English Period.</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Tahun DDA Target</label>
                                <input type="text" class="form-control border-radius-lg p-3 bg-light fw-bold text-primary" value="Tahun <?php echo $ta; ?>" readonly>
                                <small class="text-muted" style="font-size: 11px;">Tabel terdaftar di Master Tahun ini.</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Link Tabel Portal (Opsional)</label>
                                <input type="text" name="link_tabel" class="form-control border-radius-lg p-3" placeholder="URL API Portal Data / Link Tabel...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">Link Tabel Sebelumnya (Opsional)</label>
                                <input type="text" name="link_sebelumnya" class="form-control border-radius-lg p-3" placeholder="URL / Link Tahun Sebelumnya...">
                            </div>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="id_unitkerja" value="<?php echo $id_unitkerja; ?>">
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary mb-2 ls-1 text-uppercase text-xxs">File Tabel Pendukung</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-file-earmark-arrow-up"></i></span>
                                    <input type="file" name="file_tabel" tabindex="3" class="form-control border-start-0">
                                </div>
                                <?php if (!empty($file_tabel)): ?>
                                <div class="mt-2">
                                    <span class="text-xs text-muted me-2">File Usulan OPD:</span>
                                    <a href="<?php echo base_URL()?>upload/tabel_usulan/<?php echo $file_tabel; ?>" target='_blank' class="badge bg-light text-primary border-primary-soft text-decoration-none px-3 py-2 fw-medium">
                                        <i class="bi bi-file-earmark-text me-1"></i> <?php echo $file_tabel; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="text-secondary opacity-10 my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo base_url(); ?>index.php/admin/master_tabel_opd" class="btn btn-light border-0 shadow-none bg-light-subtle px-4 py-2" style="border-radius: 10px;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 10px; background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                        <i class="bi bi-check-lg me-2"></i> <?php echo ($mode == "edt") ? "Setujui & Daftarkan ke Master Tabel" : "Kirim Usulan Tabel"; ?>
                    </button>
                </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($mode == "edt" || $mode == "act_edt"): ?>
<!-- ========================================================================= -->
<!-- MODAL: PENYELESAIAN KONFLIK NOMOR TABEL PADA PERSETUJUAN USULAN OPD       -->
<!-- Muncul secara otomatis jika nomor tabel yang diberikan pada usulan OPD     -->
<!-- ternyata sudah ada di buku DDA tahun target.                               -->
<!-- Opsi 1: 'shift'  (Geser tabel eksisting ke bawah secara berurutan)         -->
<!-- Opsi 2: 'collab' (Pertahankan nomor kembar untuk kolaborasi OPD / lanjutan)-->
<!-- ========================================================================= -->
<!-- Modal Resolusi Konflik Nomor Tabel (BS5) -->
<div id="ModalConflictResolution" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 44px; height: 44px; background: rgba(255, 109, 31, 0.12); color: #FF6D1F;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.15rem;">Nomor Tabel Sudah Digunakan</h5>
                        <small class="text-secondary">Pilih solusi penanganan bentrok nomor tabel untuk DDA tahun target ini</small>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Info Tabel Eksisting -->
                <div class="p-3 mb-4 rounded-3 border" style="background-color: #fffaf5; border-color: rgba(255, 109, 31, 0.25) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <span class="badge px-2.5 py-1.5 fw-bold" style="background: rgba(255, 109, 31, 0.15); color: #c44d0d; font-size: 0.82rem; border-radius: 6px;">
                            <i class="bi bi-hash me-1"></i>No. Tabel: <span id="conflict_modal_no" class="fw-bolder"></span>
                        </span>
                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;">
                            <i class="bi bi-calendar3 me-1"></i>Tahun <?= esc($ta) ?>
                        </span>
                    </div>
                    <div class="fw-bold text-dark mb-2" id="conflict_modal_existing_judul" style="font-size: 0.95rem; line-height: 1.45;">
                        <!-- Judul eksisting diisi via JS -->
                    </div>
                    <div class="text-xs text-secondary d-flex align-items-center gap-1">
                        <i class="bi bi-building text-primary"></i> <span class="text-muted">Instansi/OPD Pemilik:</span> <span id="conflict_modal_existing_opd" class="fw-semibold text-dark">-</span>
                    </div>
                </div>

                <div class="text-xs font-weight-bolder text-uppercase text-secondary ls-1 mb-2">Pilih Solusi Nomor Tabel:</div>

                <!-- Option Cards -->
                <div class="row g-3">
                    <!-- Option 1: Shift (Recommended) -->
                    <div class="col-12">
                        <div class="conflict-card-option p-3 rounded-3 position-relative active" id="card_opt_shift">
                            <div class="d-flex align-items-start gap-3">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="radio" name="conflict_choice" id="radio_opt_shift" value="shift" checked style="cursor: pointer;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-1">
                                        <label for="radio_opt_shift" class="fw-bold text-dark mb-0" style="cursor: pointer; font-size: 0.95rem;">
                                            Geser Tabel ke Bawah (Shift Otomatis)
                                        </label>
                                        <span class="badge bg-primary-soft text-primary font-weight-bold" style="font-size: 0.75rem;">
                                             <i class="bi bi-arrow-down-circle me-1"></i>Sisipkan
                                        </span>
                                    </div>
                                    <p class="text-secondary text-xs mb-0" style="line-height: 1.45;">
                                        Tabel baru akan menggunakan nomor <strong class="conflict-no-target text-primary"></strong>. Tabel eksisting beserta seluruh tabel setelahnya pada bab ini otomatis digeser nomornya.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2: Collab / Twin -->
                    <div class="col-12">
                        <div class="conflict-card-option p-3 rounded-3 position-relative" id="card_opt_collab">
                            <div class="d-flex align-items-start gap-3">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="radio" name="conflict_choice" id="radio_opt_collab" value="collab" style="cursor: pointer;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-1">
                                        <label for="radio_opt_collab" class="fw-bold text-dark mb-0" style="cursor: pointer; font-size: 0.95rem;">
                                            Gunakan Nomor Kembar (Kolaborasi OPD / Lanjutan)
                                        </label>
                                        <span class="badge bg-primary-soft text-primary font-weight-bold" style="font-size: 0.75rem;">
                                            <i class="bi bi-people me-1"></i>Kolaborasi
                                        </span>
                                    </div>
                                    <p class="text-secondary text-xs mb-0" style="line-height: 1.45;">
                                        Simpan dengan nomor <strong class="conflict-no-target text-primary"></strong> yang sama tanpa menggeser tabel lain. Sesuai untuk tabel kolaborasi lintas instansi atau lanjutan halaman tabel.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light-subtle border-top py-3 px-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light px-4 border" style="border-radius: 8px;" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" id="btn_confirm_conflict" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold" style="border-radius: 8px; background: linear-gradient(45deg, #FF6D1F, #ff8c42); border: none;">
                    <i class="bi bi-check2-circle"></i>
                    <span>Terapkan & Simpan</span>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.8rem !important; }
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    
    /* Conflict Option Cards */
    .conflict-card-option {
        border: 1.5px solid #e2e8f0;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .conflict-card-option:hover {
        border-color: #cbd5e1;
        background: #fafafa;
        transform: translateY(-1px);
    }
    .conflict-card-option.active {
        border-color: #FF6D1F !important;
        background: rgba(255, 109, 31, 0.04) !important;
        box-shadow: 0 3px 10px rgba(255, 109, 31, 0.1) !important;
    }
    .conflict-card-option .form-check-input:checked {
        background-color: #FF6D1F;
        border-color: #FF6D1F;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // =========================================================================
    // 1. SINKRONISASI JUDUL MASTER KE FORM INPUT
    // Menyalin teks judul bahasa Indonesia dan Inggris dari Master rujukan
    // ke kolom input form agar ejaannya seragam dan baku.
    // =========================================================================
    var btnSync = document.getElementById("btn_sync_master_data");
    if (btnSync) {
        btnSync.addEventListener("click", function() {
            var judulIndEl = document.getElementById("display_matched_judul");
            var taJudulInd = document.querySelector('textarea[name="judul_ind"]');
            if (taJudulInd && judulIndEl) {
                taJudulInd.value = judulIndEl.innerText.trim();
            }
            
            var enTextSpan = document.getElementById("display_matched_judul_en_text");
            var taJudulEn = document.querySelector('textarea[name="judul_en"]');
            if (taJudulEn && enTextSpan) {
                var enText = enTextSpan.innerText.trim();
                if (enText) taJudulEn.value = enText;
            }
        });
    }

    // =========================================================================
    // 2. PEMILIHAN KANDIDAT MASTER TABEL ALTERNATIF
    // Jika sistem menemukan lebih dari satu tabel yang mirip di database,
    // admin dapat memilih tabel lain yang lebih sesuai dari daftar collapse.
    // =========================================================================
    document.querySelectorAll(".btn-select-alt-master").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var id = this.getAttribute("data-id");
            var judulInd = this.getAttribute("data-judul-ind");
            var judulEn = this.getAttribute("data-judul-en");
            var opd = this.getAttribute("data-opd");
            var terbit = this.getAttribute("data-terbit");
            
            // Perbarui ID master target di hidden input dan kartu rekomendasi
            var inputId = document.getElementById("input_link_to_master_id");
            if (inputId) inputId.value = id;

            var dispId = document.getElementById("display_matched_id");
            if (dispId) dispId.innerText = id;

            var labelId = document.getElementById("label_matched_id");
            if (labelId) labelId.innerText = id;

            var dispJudul = document.getElementById("display_matched_judul");
            if (dispJudul) dispJudul.innerText = judulInd;
            
            var displayOpd = document.getElementById("display_matched_opd");
            if (displayOpd) displayOpd.innerText = opd;
            
            var displayTerbit = document.getElementById("display_matched_terbit");
            if (displayTerbit) displayTerbit.innerText = terbit;

            var displayEn = document.getElementById("display_matched_judul_en");
            var displayEnText = document.getElementById("display_matched_judul_en_text");
            if (displayEn && displayEnText) {
                if (judulEn && judulEn.trim() !== '') {
                    displayEnText.innerText = judulEn;
                    displayEn.style.display = '';
                } else {
                    displayEnText.innerText = '';
                    displayEn.style.display = 'none';
                }
            }
            
            // Otomatis aktifkan switch centang tautkan master
            var switchBtn = document.getElementById("linkToMasterSwitch");
            if (switchBtn) switchBtn.checked = true;

            // Tutup accordion pilihan alternatif
            var collapseEl = document.getElementById("collapseOtherMatches");
            if (collapseEl && window.bootstrap && bootstrap.Collapse) {
                var bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl);
                bsCollapse.hide();
            }
        });
    });

    // =========================================================================
    // 3. INTERSEPSI SUBMIT FORM: CEK BENTROK NOMOR TABEL
    // Sebelum menyetujui usulan OPD, sistem memeriksa apakah nomor tabel yang
    // diisi bentrok dengan tabel lain yang sudah ada di DDA tahun berjalan.
    // =========================================================================
    var form = document.getElementById("form-add-opd");
    if (form) {
        form.addEventListener("submit", function(e) {
            var conflictRes = document.getElementById("conflict_resolution");
            if (conflictRes && conflictRes.value !== "") {
                return true; 
            }
            
            var noTabelInput = document.querySelector('input[name="no_tabel"]');
            var noTabel = noTabelInput ? noTabelInput.value.trim() : "";
            
            // Jika nomor tabel dikosongkan (akan diisi belakangan), izinkan submit langsung
            if (!noTabel) {
                if (conflictRes) conflictRes.value = "none";
                return true;
            }
            
            e.preventDefault(); // Tahan submit untuk verifikasi nomor
            
            var judulIndEl = document.querySelector('textarea[name="judul_ind"]');
            var judulInd = judulIndEl ? judulIndEl.value.trim() : "";
            var taTarget = document.getElementById("ta_target") ? document.getElementById("ta_target").value : "<?= $ta ?>";
            
            var formData = new FormData();
            formData.append("judul_ind", judulInd);
            formData.append("no_tabel", noTabel);
            formData.append("tahun", taTarget);
            
            // Jika menautkan ke master eksisting, kirim exclude_id_tabel agar tidak bentrok dengan diri sendiri
            var linkSwitch = document.getElementById("linkToMasterSwitch");
            var linkIdInput = document.getElementById("input_link_to_master_id");
            if (linkSwitch && linkSwitch.checked && linkIdInput && linkIdInput.value) {
                formData.append("exclude_id_tabel", linkIdInput.value);
            }
            
            // Panggil API deteksi konflik
            fetch("<?php echo base_url('index.php/admin/check_new_table_conflict'); ?>", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    // Terjadi bentrok nomor: Tampilkan modal pilihan solusi
                    var resolvedNo = data.no_tabel || noTabel;
                    var noEl = document.getElementById("conflict_modal_no");
                    if (noEl) noEl.innerText = resolvedNo;
                    
                    document.querySelectorAll(".conflict-no-target").forEach(function(el) {
                        el.innerText = resolvedNo;
                    });
                    
                    var judulEl = document.getElementById("conflict_modal_existing_judul");
                    if (judulEl) judulEl.innerText = data.existing_judul || "-";
                    
                    var opdEl = document.getElementById("conflict_modal_existing_opd");
                    if (opdEl) opdEl.innerText = data.existing_opd || "-";

                    // Default pilihan: Shift (geser ke bawah)
                    var radioShift = document.getElementById("radio_opt_shift");
                    if (radioShift) radioShift.checked = true;
                    
                    var cardShift = document.getElementById("card_opt_shift");
                    var cardCollab = document.getElementById("card_opt_collab");
                    if (cardShift) cardShift.classList.add("active");
                    if (cardCollab) cardCollab.classList.remove("active");

                    var modalEl = document.getElementById("ModalConflictResolution");
                    if (modalEl) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        } else if (typeof $ !== 'undefined' && $.fn.modal) {
                            $(modalEl).modal('show');
                        }
                    }
                } else {
                    // Tidak ada bentrok nomor, lanjutkan submit form
                    if (conflictRes) conflictRes.value = "none";
                    form.submit();
                }
            })
            .catch(error => {
                console.error("Error checking conflict:", error);
                if (conflictRes) conflictRes.value = "none";
                form.submit();
            });
        });

        // =====================================================================
        // 4. INTERAKSI KARTU SOLUSI KONFLIK (SHIFT VS COLLAB)
        // =====================================================================
        var cardShift = document.getElementById("card_opt_shift");
        var cardCollab = document.getElementById("card_opt_collab");
        var radioShift = document.getElementById("radio_opt_shift");
        var radioCollab = document.getElementById("radio_opt_collab");
        var btnConfirm = document.getElementById("btn_confirm_conflict");

        if (cardShift && cardCollab) {
            cardShift.addEventListener("click", function() {
                if (radioShift) radioShift.checked = true;
                cardShift.classList.add("active");
                cardCollab.classList.remove("active");
            });

            cardCollab.addEventListener("click", function() {
                if (radioCollab) radioCollab.checked = true;
                cardCollab.classList.add("active");
                cardShift.classList.remove("active");
            });

            if (radioShift) {
                radioShift.addEventListener("change", function() {
                    if (this.checked) {
                        cardShift.classList.add("active");
                        cardCollab.classList.remove("active");
                    }
                });
            }

            if (radioCollab) {
                radioCollab.addEventListener("change", function() {
                    if (this.checked) {
                        cardCollab.classList.add("active");
                        cardShift.classList.remove("active");
                    }
                });
            }
        }

        // Terapkan pilihan resolusi konflik ke form dan kirim ke controller
        if (btnConfirm) {
            btnConfirm.addEventListener("click", function() {
                var selectedChoice = document.querySelector('input[name="conflict_choice"]:checked')?.value || "shift";
                var conflictRes = document.getElementById("conflict_resolution");
                if (conflictRes) conflictRes.value = selectedChoice;

                var modalEl = document.getElementById("ModalConflictResolution");
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var inst = bootstrap.Modal.getInstance(modalEl);
                        if (inst) inst.hide();
                    } else if (typeof $ !== 'undefined' && $.fn.modal) {
                        $(modalEl).modal('hide');
                    }
                }

                form.submit();
            });
        }
    }

    // =========================================================================
    // FITUR REKOMENDASI NOMOR TABEL REAL-TIME
    // Menampilkan badge "Nomor terakhir X.X.X" saat mengetik awalan sub-bab
    // =========================================================================
    var noTabelOpdInput = document.getElementById("input_no_tabel");
    var noTabelOpdSuggestion = document.getElementById("no_tabel_opd_suggestion");
    var noTabelOpdTimer = null;
    var currentOpdTA = <?= json_encode($ta ?? session()->get('admin_ta') ?? date('Y')) ?>;

    if (noTabelOpdInput && noTabelOpdSuggestion) {
        function fetchOpdNoTabelSuggestion() {
            var val = (noTabelOpdInput.value || '').trim();

            if (!val || val.length < 2) {
                noTabelOpdSuggestion.style.display = "none";
                noTabelOpdSuggestion.innerHTML = "";
                return;
            }

            // Pastikan format diawali angka dan titik, misal "1.1", "1.1.", "1.1.4"
            if (!/^\d+(\.\d*)?/.test(val)) {
                noTabelOpdSuggestion.style.display = "none";
                noTabelOpdSuggestion.innerHTML = "";
                return;
            }

            clearTimeout(noTabelOpdTimer);
            noTabelOpdTimer = setTimeout(function() {
                var url = "<?= base_url('index.php/admin/ajax_get_last_table_number') ?>?prefix=" + encodeURIComponent(val) + "&tahun=" + encodeURIComponent(currentOpdTA);
                fetch(url)
                    .then(function(res) { return res.json(); })
                    .then(function(res) {
                        if (res && res.status) {
                            var html = '';
                            var isAlreadyNext = (val === res.next_no);

                            if (res.has_tables) {
                                html = '<div class="d-flex align-items-center justify-content-between p-1 ps-2 pe-1 rounded-pill bg-light border mt-2 shadow-xs" style="font-size: 11px;">' +
                                       '  <span class="text-secondary d-inline-flex align-items-center" style="font-size: 11px;">' +
                                       '    <i class="bi bi-info-circle text-primary me-1" style="font-size: 13px;"></i>Nomor terakhir <strong class="text-dark ms-1">' + res.last_no + '</strong>' +
                                       '  </span>';
                                if (isAlreadyNext) {
                                    html += '  <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 10px; white-space: nowrap;">' +
                                            '    <i class="bi bi-check-circle me-1"></i>Siap dipakai' +
                                            '  </span>';
                                } else {
                                    html += '  <button type="button" class="btn btn-xs rounded-pill mb-0 py-1 px-2 btn-apply-no-tabel-opd text-white fw-bold shadow-none" data-next="' + res.next_no + '" style="font-size: 10px; background: #FF6D1F; border: none; white-space: nowrap;">' +
                                            '    <i class="bi bi-plus me-1"></i>Gunakan ' + res.next_no +
                                            '  </button>';
                                }
                                html += '</div>';
                            } else {
                                html = '<div class="d-flex align-items-center justify-content-between p-1 ps-2 pe-1 rounded-pill bg-light border mt-2 shadow-xs" style="font-size: 11px;">' +
                                       '  <span class="text-muted d-inline-flex align-items-center" style="font-size: 11px;">' +
                                       '    <i class="bi bi-plus-circle text-success me-1" style="font-size: 13px;"></i>Sub-bab baru' +
                                       '  </span>' +
                                       '  <button type="button" class="btn btn-xs rounded-pill mb-0 py-1 px-2 btn-apply-no-tabel-opd text-white fw-bold shadow-none" data-next="' + res.next_no + '" style="font-size: 10px; background: #198754; border: none; white-space: nowrap;">' +
                                       '    <i class="bi bi-plus me-1"></i>Gunakan ' + res.next_no +
                                       '  </button>' +
                                       '</div>';
                            }
                            noTabelOpdSuggestion.innerHTML = html;
                            noTabelOpdSuggestion.style.display = "block";
                        } else {
                            noTabelOpdSuggestion.style.display = "none";
                            noTabelOpdSuggestion.innerHTML = "";
                        }
                    })
                    .catch(function() {
                        noTabelOpdSuggestion.style.display = "none";
                        noTabelOpdSuggestion.innerHTML = "";
                    });
            }, 220);
        }

        noTabelOpdInput.addEventListener("input", fetchOpdNoTabelSuggestion);
        noTabelOpdInput.addEventListener("keyup", fetchOpdNoTabelSuggestion);
        noTabelOpdInput.addEventListener("paste", fetchOpdNoTabelSuggestion);

        // Delegasi klik "Gunakan X.X.X"
        noTabelOpdSuggestion.addEventListener("click", function(e) {
            var btn = e.target.closest(".btn-apply-no-tabel-opd");
            if (btn) {
                e.preventDefault();
                var nextNo = btn.getAttribute("data-next");
                if (nextNo) {
                    noTabelOpdInput.value = nextNo;
                    fetchOpdNoTabelSuggestion();
                }
            }
        });

        if (noTabelOpdInput.value) {
            fetchOpdNoTabelSuggestion();
        }
    }
});
</script>
