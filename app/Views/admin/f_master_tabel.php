<?php
/**
 * VIEW FORM: MASTER TABEL (f_master_tabel.php)
 * Digunakan untuk:
 * 1. Tambah Master Tabel Baru (act_add) dengan deteksi master serupa & resolusi konflik nomor tabel.
 * 2. Edit Detail Master Tabel (act_edt) untuk memperbarui judul, periode, instansi, atau link tabel.
 */

$today = getdate();
$tahunsekarang = $today['year'];
$mode = segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
    // Mode EDIT: Mengisi nilai default form dari record tabel yang dipilih ($datpil)
    $act                    = "act_edt";
    $judul_en_page          = "EDIT MASTER TABEL";
    $idp                    = $datpil->id;
    $judul_ind              = $datpil->judul_ind;
    $judul_en               = $datpil->judul_en;
    $link_tabel             = $datpil->link_tabel;
    $link_sebelumnya        = $datpil->link_sebelumnya;
    $id_unitkerja           = $datpil->id_unitkerja;
    $periode_id             = $datpil->periode_id ?? $datpil->periode ?? '';
    $periode_en             = $datpil->periode_en ?? $periode_id;
    $no_tabel               = $datpil->no_tabel;
    
    $query_unitkerja =\Config\Database::connect()->query("SELECT * from m_unitkerja where id_unitkerja=? LIMIT 1", [$id_unitkerja])->getRow();
    if($query_unitkerja) {
        $idunitkerja_terpilih = $query_unitkerja->id_unitkerja;
        $unitkerja_terpilih = $query_unitkerja->unitkerja_ind;
    } else {
        $idunitkerja_terpilih = '';
        $unitkerja_terpilih = '- Instansi Tidak Ditemukan -';
    }
    
} else {
    // Mode TAMBAH: Mengosongkan isian form untuk entri master tabel baru
    $act                    = "act_add";
    $judul_en_page          = " TAMBAH MASTER TABEL";
    $idp                    = "";
    $judul_ind              = "";
    $judul_en               = "";
    $link_tabel             = "";
    $link_sebelumnya        = "";
    $id_unitkerja           = "";
    $periode_id             = "";
    $periode_en             = "";
    $no_tabel               = "";
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-file-earmark-plus me-2"></i><?php echo ($mode == "edt") ? "Edit Master Tabel" : "Tambah Master Tabel"; ?>
                </h5>
            </div>
            
            <div class="card-body p-4">
                <!-- Form Utama Master Tabel -->
                <form id="form-master-tabel" action="<?php echo base_URL(); ?>index.php/admin/master_tabel/<?php echo $act; ?>" method="post" accept-charset="utf-8">
                    <!-- Hidden field penanganan bentrok nomor: 'shift', 'collab', atau 'none' -->
                    <input type="hidden" name="conflict_resolution" id="conflict_resolution" value="">
                    <!-- Hidden field ID tabel (khusus saat mode edit) -->
                    <input type="hidden" name="idp" value="<?php echo $idp; ?>">
                    <!-- Hidden field ID master rujukan (jika menautkan ke tabel kamus master eksisting agar tidak duplikat) -->
                    <input type="hidden" name="master_id" id="selected_master_id" value="">
                    
                    <div class="row g-4 mb-4">
                        <!-- SISI KIRI: BAHASA INDONESIA -->
                        <div class="col-md-6">
                            <div class="mb-3 position-relative">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-translate me-1 text-primary"></i> Judul Bahasa Indonesia</span>
                                    <?php if ($act == "act_add"): ?>
                                    <span class="badge bg-light text-muted border fw-normal" style="font-size: 10px;">
                                        <i class="bi bi-lightning-charge text-warning"></i> Cek Master Otomatis
                                    </span>
                                    <?php endif; ?>
                                </label>
                                <div class="position-relative">
                                    <textarea name="judul_ind" id="judul_ind" tabindex="1" required class="form-control" rows="3" placeholder="Ketik judul tabel bahasa Indonesia..." autocomplete="off"><?php echo $judul_ind; ?></textarea>
                                    <!-- Indikator loading saat AJAX mencari master tabel serupa -->
                                    <div id="judul_spinner" class="position-absolute end-0 top-0 mt-2 me-2 d-none">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <!-- Dropdown Live Suggestion Master ID: Muncul otomatis saat mengetik judul >= 3 karakter -->
                                    <div id="master_suggestions_box" class="dropdown-menu shadow-lg w-100 p-2 border-0 mt-1" style="display:none; position:absolute; z-index:1050; max-height:360px; overflow-y:auto; border-radius: 0.75rem; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;">
                                        <div class="px-2 py-1 text-muted small fw-bold text-uppercase border-bottom mb-1 d-flex justify-content-between align-items-center" style="font-size: 11px;">
                                            <span><i class="bi bi-search me-1 text-primary"></i> Master Tabel Serupa di Database:</span>
                                            <span class="text-secondary" style="font-size: 10px;">Klik untuk menautkan</span>
                                        </div>
                                        <div id="master_suggestions_list"></div>
                                    </div>
                                </div>

                                <!-- Alert Saat Master Ditautkan: Mengonfirmasi bahwa tabel baru ini tidak membuat entri duplikat baru di kamus m_list_tabel -->
                                <div id="linked_master_badge" class="alert alert-info border-0 rounded-3 p-3 mt-2 d-none shadow-sm" style="background: #eef7ff; border-left: 4px solid #0284c7 !important;">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-link-45deg fs-4 text-primary me-2 mt-n1"></i>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                                                    Menautkan ke Master ID: <span class="badge bg-primary text-white" id="linked_master_id_text">#</span>
                                                </div>
                                                <div class="text-muted small mt-1" style="font-size: 11px;">
                                                    Tabel ini akan didaftarkan ke DDA <b>Tahun <?php echo $ta ?? date('Y'); ?></b> menggunakan ID Master yang sama (mencegah duplikasi kamus master).
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" id="btn_cancel_link_master" class="btn btn-sm btn-outline-secondary ms-2 text-nowrap" style="font-size: 11px;">
                                            <i class="bi bi-x-circle me-1"></i> Batal Tautkan
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark"><i class="bi bi-calendar3 me-1 text-primary"></i> Periode Tabel (Indonesia)</label>
                                <input type="text" name="periode_id" class="form-control" value="<?php echo $periode_id; ?>" placeholder="Contoh: 2024, Semester I 2024, 2021-2025" required>
                                <small class="text-muted" style="font-size: 11px;">Otomatis digabung di akhir judul (kecuali menyisipkan tag <code>[PERIODE]</code> di tengah).</small>
                            </div>
                        </div>

                        <!-- SISI KANAN: BAHASA INGGRIS -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark"><i class="bi bi-translate me-1 text-primary"></i> Table Title (English)</label>
                                <textarea name="judul_en" tabindex="2" required class="form-control" rows="3" placeholder="Type english table title..."><?php echo $judul_en; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark"><i class="bi bi-calendar3 me-1 text-primary"></i> Table Period (English)</label>
                                <input type="text" name="periode_en" class="form-control" value="<?php echo $periode_en; ?>" placeholder="Example: 2024, 1st Semester 2024, 2021-2025">
                                <small class="text-muted" style="font-size: 11px;">Automatically appended to title (unless <code>[PERIODE]</code> tag is used inline).</small>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-hash me-1 text-primary"></i> No Tabel</label>
                            <input type="text" name="no_tabel" id="no_tabel" class="form-control" value="<?php echo $no_tabel; ?>" placeholder="Contoh: 1.1">
                            <div id="no_tabel_suggestion" class="mt-1" style="display: none;"></div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark d-block"><i class="bi bi-building me-1 text-primary"></i> Penanggung Jawab (Instansi)</label>
                            <select name="id_unitkerja" id="id_unitkerja" class="form-select" tabindex="5" required>
                                <?php
                                $db = \Config\Database::connect();
                                $unitkerja = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();
                                
                                if ($mode == "edt" || $mode == "act_edt") {
                                    echo "<option selected value='".$idunitkerja_terpilih."'>".$unitkerja_terpilih."</option>";
                                    foreach($unitkerja as $p){
                                        if ($p['id_unitkerja'] != $idunitkerja_terpilih) {
                                            echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>";
                                        }
                                    }
                                } else {
                                    echo "<option selected value=''>- Pilih Instansi Penanggung Jawab -</option>";
                                    foreach($unitkerja as $p){
                                        echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>";
                                    }
                                }
                                ?>
                            </select>  
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-link-45deg me-1 text-primary"></i> Link Tabel (Tahun Berjalan)</label>
                            <textarea name="link_tabel" tabindex="3" class="form-control" rows="2" placeholder="Masukkan url/link tabel"><?php echo $link_tabel; ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-link-45deg me-1 text-primary"></i> Link Tabel (Tahun Sebelumnya)</label>
                            <textarea name="link_sebelumnya" tabindex="4" class="form-control" rows="2" placeholder="Masukkan url/link tabel tahun sebelumnya"><?php echo $link_sebelumnya; ?></textarea>
                        </div>
                    </div>
                    
                    <hr class="text-secondary opacity-25 mt-4 mb-3">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo base_URL(); ?>index.php/admin/master_tabel" class="btn btn-light border" tabindex="7">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary px-4" tabindex="6">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Data Tabel
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($act == "act_add"): ?>
<!-- ========================================================================= -->
<!-- MODAL: RESOLUSI KONFLIK NOMOR TABEL (INTERCEPT SUBMIT FORM)                -->
<!-- Jika admin memasukkan nomor tabel yang sudah dipakai oleh tabel lain di    -->
<!-- tahun publikasi aktif, modal ini otomatis muncul menawarkan 2 pilihan:    -->
<!-- 1. 'shift'  : Sisipkan tabel dan geser nomor urut tabel berikutnya ke bawah-->
<!-- 2. 'collab' : Gunakan nomor yang sama (kolaborasi lintas instansi/lanjutan)-->
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
                        <small class="text-secondary">Pilih solusi penanganan bentrok nomor tabel untuk DDA tahun aktif ini</small>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body: Menampilkan identitas tabel eksisting yang menggunakan nomor tersebut -->
            <div class="modal-body p-4">
                <!-- Info Tabel Eksisting -->
                <div class="p-3 mb-4 rounded-3 border" style="background-color: #fffaf5; border-color: rgba(255, 109, 31, 0.25) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <span class="badge px-2.5 py-1.5 fw-bold" style="background: rgba(255, 109, 31, 0.15); color: #c44d0d; font-size: 0.82rem; border-radius: 6px;">
                            <i class="bi bi-hash me-1"></i>No. Tabel: <span id="conflict_modal_no" class="fw-bolder"></span>
                        </span>
                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;">
                            <i class="bi bi-calendar3 me-1"></i>Tahun <?= esc(session()->get('admin_ta') ?: (session()->get('sess_tahun') ?: date('Y'))) ?>
                        </span>
                    </div>
                    <div class="fw-bold text-dark mb-2" id="conflict_modal_existing_judul" style="font-size: 0.95rem; line-height: 1.45;">
                        <!-- Judul eksisting diisi via JS saat AJAX konflik terdeteksi -->
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

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Custom adjustments for Select2 inside BS5 theme */
    .select2-container--bootstrap-5 .select2-selection {
        font-size: 0.95rem;
        min-height: 42px;
        display: flex;
        align-items: center;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        color: #212529;
        line-height: normal;
        padding-left: 0.75rem;
    }
    .select2-container--bootstrap-5 .select2-selection--single {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        display: none;
    }
    .select2-dropdown {
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .select2-search__field {
        border-radius: 0.375rem !important;
        padding: 0.5rem 0.75rem !important;
    }
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
$(document).ready(function() {
    // =========================================================================
    // INISIALISASI SELECT2 DENGAN TEMA BOOTSTRAP 5
    // Mempermudah admin mencari nama instansi/OPD dari puluhan daftar yang ada.
    // =========================================================================
    $('#id_unitkerja').select2({
        theme: 'bootstrap-5',
        placeholder: "- Cari dan Pilih Instansi -",
        allowClear: true,
        width: '100%',
        dropdownParent: $('.content-body'), // Pastikan dropdown menempel dengan benar di modal/konten
        language: {
            noResults: function() {
                return "Instansi tidak ditemukan";
            }
        }
    });

    // =========================================================================
    // FITUR REKOMENDASI NOMOR TABEL REAL-TIME
    // Menampilkan badge "Nomor terakhir X.X.X" saat mengetik awalan sub-bab
    // =========================================================================
    var noTabelTimer = null;
    var currentTA = <?= json_encode($ta ?? session()->get('admin_ta') ?? date('Y')) ?>;

    function fetchNoTabelSuggestion() {
        var val = ($('#no_tabel').val() || '').trim();
        var container = $('#no_tabel_suggestion');

        if (!val || val.length < 2) {
            container.hide().empty();
            return;
        }

        // Pastikan format diawali angka dan titik, misal "1.1", "1.1.", "1.1.4"
        if (!/^\d+(\.\d*)?/.test(val)) {
            container.hide().empty();
            return;
        }

        clearTimeout(noTabelTimer);
        noTabelTimer = setTimeout(function() {
            $.ajax({
                url: '<?= base_url('index.php/admin/ajax_get_last_table_number') ?>',
                type: 'GET',
                dataType: 'json',
                data: {
                    prefix: val,
                    tahun: currentTA
                },
                success: function(res) {
                    if (res && res.status) {
                        var html = '';
                        var isAlreadyNext = (val === res.next_no);

                        if (res.has_tables) {
                            html = '<div class="d-inline-flex align-items-center flex-wrap gap-2 mt-1">' +
                                   '  <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill" style="font-size: 11px; font-weight: 500;">' +
                                   '    <i class="bi bi-info-circle text-primary me-1"></i>Nomor terakhir <strong class="text-dark">' + res.last_no + '</strong>' +
                                   '  </span>';
                            if (isAlreadyNext) {
                                html += '  <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill" style="font-size: 11px;">' +
                                        '    <i class="bi bi-check-circle me-1"></i>Siap dipakai' +
                                        '  </span>';
                            } else {
                                html += '  <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill btn-apply-no-tabel" data-next="' + res.next_no + '" style="font-size: 11px; height: 22px; line-height: 20px;">' +
                                        '    <i class="bi bi-plus-circle me-1"></i>Gunakan ' + res.next_no +
                                        '  </button>';
                            }
                            html += '</div>';
                        } else {
                            html = '<div class="d-inline-flex align-items-center flex-wrap gap-2 mt-1">' +
                                   '  <span class="badge bg-light text-muted border px-2 py-1 rounded-pill" style="font-size: 11px;">' +
                                   '    <i class="bi bi-plus-circle text-success me-1"></i>Sub-bab baru' +
                                   '  </span>' +
                                   '  <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 rounded-pill btn-apply-no-tabel" data-next="' + res.next_no + '" style="font-size: 11px; height: 22px; line-height: 20px;">' +
                                   '    <i class="bi bi-plus-circle me-1"></i>Gunakan ' + res.next_no +
                                   '  </button>' +
                                   '</div>';
                        }
                        container.html(html).fadeIn(150);
                    } else {
                        container.hide().empty();
                    }
                },
                error: function() {
                    container.hide().empty();
                }
            });
        }, 220);
    }

    $('#no_tabel').on('input keyup paste', fetchNoTabelSuggestion);

    // Event klik "Gunakan X.X.X"
    $(document).on('click', '.btn-apply-no-tabel', function(e) {
        e.preventDefault();
        var nextNo = $(this).data('next');
        if (nextNo) {
            $('#no_tabel').val(nextNo).trigger('input');
        }
    });

    // Panggil saat halaman dibuka jika no_tabel sudah terisi (misal mode edit)
    if ($('#no_tabel').val()) {
        fetchNoTabelSuggestion();
    }
});
</script>


<?php if ($act == "act_add"): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // =========================================================================
    // FITUR LIVE SEARCH REKOMENDASI MASTER TABEL SERUPA
    // Menghubungkan judul yang diketik user dengan kamus induk m_list_tabel
    // untuk mencegah pembuatan data master ganda/duplikat antar-tahun.
    // =========================================================================
    var judulInput = document.getElementById("judul_ind");
    var suggestionsBox = document.getElementById("master_suggestions_box");
    var suggestionsList = document.getElementById("master_suggestions_list");
    var spinner = document.getElementById("judul_spinner");
    var selectedMasterInput = document.getElementById("selected_master_id");
    var linkedBadge = document.getElementById("linked_master_badge");
    var linkedIdText = document.getElementById("linked_master_id_text");
    var btnCancelLink = document.getElementById("btn_cancel_link_master");
    
    var debounceTimer = null;
    var currentTA = <?= json_encode($ta ?? session()->get('admin_ta') ?? date('Y')) ?>;
    
    // Dengarkan pengetikan judul bahasa Indonesia
    if (judulInput && suggestionsBox) {
        judulInput.addEventListener("input", function() {
            var query = this.value.trim();
            clearTimeout(debounceTimer);

            // Minimal 3 karakter sebelum melakukan pencarian ke server
            if (query.length < 3) {
                suggestionsBox.style.display = "none";
                return;
            }

            spinner.classList.remove("d-none");

            // Debounce 280ms agar tidak spam request saat admin mengetik cepat
            debounceTimer = setTimeout(function() {
                fetch("<?= base_url('index.php/admin/ajax_search_master_tabel') ?>?q=" + encodeURIComponent(query))
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        spinner.classList.add("d-none");
                        if (!data || data.length === 0) {
                            suggestionsBox.style.display = "none";
                            return;
                        }
                        renderSuggestions(data);
                    })
                    .catch(function(err) {
                        console.error("Error fetching master suggestions:", err);
                        spinner.classList.add("d-none");
                    });
            }, 280);
        });

        // Tampilkan daftar rekomendasi master tabel dalam dropdown
        function renderSuggestions(items) {
            suggestionsList.innerHTML = "";
            items.forEach(function(item) {
                var itemEl = document.createElement("div");
                itemEl.className = "p-2 rounded-2 mb-1 suggestion-item text-wrap";
                itemEl.style.cursor = "pointer";
                itemEl.style.transition = "background-color 0.15s ease";

                var isInCurrentYear = (item.is_in_ta == 1);
                var statusBadge = isInCurrentYear 
                    ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle text-wrap" style="font-size: 10px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sudah Terbit di DDA ' + currentTA + ' (No: ' + (item.no_tabel_aktif || '-') + ')</span>'
                    : '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;"><i class="bi bi-check-circle-fill me-1"></i>Tersedia untuk Ditautkan ke ' + currentTA + '</span>';

                itemEl.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <div class="fw-bold text-dark small" style="font-size: 0.85rem; line-height: 1.35;">${escapeHtml(item.judul_ind)}</div>
                        <span class="badge bg-secondary text-white flex-shrink-0" style="font-size: 10px;">ID #${item.id}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        <span class="badge bg-light text-dark border" style="font-size: 10px;"><i class="bi bi-building me-1 text-primary"></i>${escapeHtml(item.unitkerja_ind || item.id_unitkerja || '-')}</span>
                        <span class="badge bg-light text-muted border" style="font-size: 10px;"><i class="bi bi-clock-history me-1"></i>Terbit: ${escapeHtml(item.tahun_terbit || '-')}</span>
                        ${statusBadge}
                    </div>
                `;

                itemEl.addEventListener("mouseenter", function() {
                    this.style.backgroundColor = "#f1f5f9";
                });
                itemEl.addEventListener("mouseleave", function() {
                    this.style.backgroundColor = "transparent";
                });

                // Saat salah satu master diklik: tautkan dan autofill form
                itemEl.addEventListener("click", function() {
                    selectMasterTable(item);
                });

                suggestionsList.appendChild(itemEl);
            });
            suggestionsBox.style.display = "block";
        }

        // Fungsi memilih master tabel: mengisi judul en, unit kerja, link sebelumnya secara otomatis
        function selectMasterTable(item) {
            selectedMasterInput.value = item.id;
            judulInput.value = item.judul_ind;
            
            var judulEnEl = document.querySelector('textarea[name="judul_en"]');
            if (judulEnEl && item.judul_en) {
                judulEnEl.value = item.judul_en;
            }

            if (item.id_unitkerja) {
                $('#id_unitkerja').val(item.id_unitkerja).trigger('change');
            }

            var linkSebEl = document.querySelector('textarea[name="link_sebelumnya"]');
            if (linkSebEl && item.latest_link && !linkSebEl.value) {
                linkSebEl.value = item.latest_link;
            }

            var noTabelEl = document.querySelector('input[name="no_tabel"]');
            if (noTabelEl && item.prev_no_tabel && !noTabelEl.value) {
                noTabelEl.placeholder = "Tahun sebelumnya: " + item.prev_no_tabel;
            }

            linkedIdText.innerText = "#" + item.id;
            linkedBadge.classList.remove("d-none");
            suggestionsBox.style.display = "none";
        }

        // Tombol batalkan tautan master (kembali menjadi master baru mandiri)
        if (btnCancelLink) {
            btnCancelLink.addEventListener("click", function() {
                selectedMasterInput.value = "";
                linkedBadge.classList.add("d-none");
                judulInput.focus();
            });
        }

        // Tutup dropdown saran jika user mengklik area luar
        document.addEventListener("click", function(e) {
            if (!suggestionsBox.contains(e.target) && e.target !== judulInput) {
                suggestionsBox.style.display = "none";
            }
        });

        function escapeHtml(text) {
            if (!text) return "";
            var div = document.createElement("div");
            div.innerText = text;
            return div.innerHTML;
        }
    }

    // =========================================================================
    // FITUR INTERSEPSI SUBMIT FORM & CEK BENTROK NOMOR TABEL
    // Sebelum form dikirim ke controller, sistem memverifikasi apakah nomor tabel
    // sudah dipakai di tahun berjalan melalui endpoint check_new_table_conflict.
    // =========================================================================
    var form = document.getElementById("form-master-tabel");
    if (!form) return;
    
    form.addEventListener("submit", function(e) {
        // Jika sudah ada keputusan resolusi konflik ('shift', 'collab', atau 'none'), izinkan submit langsung
        if (document.getElementById("conflict_resolution").value !== "") {
            return true; 
        }
        
        e.preventDefault(); // Tahan pengiriman form sementara
        
        var judulInd = document.querySelector('textarea[name="judul_ind"]').value;
        var noTabel = document.querySelector('input[name="no_tabel"]').value;
        
        var formData = new FormData();
        formData.append("judul_ind", judulInd);
        formData.append("no_tabel", noTabel);
        formData.append("tahun", "<?= $ta ?>");
        
        // Panggil controller untuk memeriksa bentrok nomor tabel
        fetch("<?php echo base_url('index.php/admin/check_new_table_conflict'); ?>", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.conflict) {
                // Terdapat tabel lain yang menggunakan nomor ini pada tahun yang sama
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

                // Set pilihan default ke opsi Shift (geser ke bawah)
                var radioShift = document.getElementById("radio_opt_shift");
                if (radioShift) radioShift.checked = true;
                
                var cardShift = document.getElementById("card_opt_shift");
                var cardCollab = document.getElementById("card_opt_collab");
                if (cardShift) cardShift.classList.add("active");
                if (cardCollab) cardCollab.classList.remove("active");

                // Buka modal dialog resolusi konflik
                var modalEl = document.getElementById("ModalConflictResolution");
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    } else if (typeof $ !== 'undefined' && $.fn.modal) {
                        $(modalEl).modal('show');
                    }
                }
            } else {
                // Tidak ada bentrok nomor, lanjutkan simpan biasa
                document.getElementById("conflict_resolution").value = "none";
                form.submit();
            }
        })
        .catch(error => {
            console.error("Error checking conflict:", error);
            // Fallback: Jika request gagal/offline, teruskan submit normal
            document.getElementById("conflict_resolution").value = "none";
            form.submit();
        });
    });

    // =========================================================================
    // EVENT LISTENER PILIHAN KARTU SOLUSI KONFLIK (SHIFT VS COLLAB)
    // =========================================================================
    var cardShift = document.getElementById("card_opt_shift");
    var cardCollab = document.getElementById("card_opt_collab");
    var radioShift = document.getElementById("radio_opt_shift");
    var radioCollab = document.getElementById("radio_opt_collab");
    var btnConfirm = document.getElementById("btn_confirm_conflict");

    if (cardShift && cardCollab) {
        // Klik kartu Shift
        cardShift.addEventListener("click", function() {
            if (radioShift) radioShift.checked = true;
            cardShift.classList.add("active");
            cardCollab.classList.remove("active");
        });

        // Klik kartu Collab
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

    // Tombol Konfirmasi Pilihan di Modal: Terapkan pilihan ke hidden field dan submit form
    if (btnConfirm) {
        btnConfirm.addEventListener("click", function() {
            var selectedChoice = document.querySelector('input[name="conflict_choice"]:checked')?.value || "shift";
            document.getElementById("conflict_resolution").value = selectedChoice;

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
});
</script>
<?php endif; ?>
