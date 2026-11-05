<!-- ========================================================================= -->
<!-- MODAL: KONFIGURASI TAMPILAN TABEL DINAMIS (modal_config_dinamis.php)      -->
<!-- 100% Selaras dengan Pola & UX modal_config_tabel.php Milik Tabel API       -->
<!-- ========================================================================= -->
<style>
    /* Config Modal Dinamis Styles (100% Selaras dengan modal_config_tabel) */
    #configModalDinamis .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    }

    #configModalDinamis .modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #eef2f7;
        border-radius: 20px 20px 0 0;
        padding: 20px 25px;
    }

    #configModalDinamis .nav-pills {
        border: none;
        gap: 10px;
        margin-bottom: 20px;
    }

    #configModalDinamis .nav-link {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        font-weight: 600;
        padding: 8px 20px;
        background: #fff;
    }

    #configModalDinamis .nav-link.active {
        background: #FF6D1F !important;
        color: #fff !important;
        border-color: #FF6D1F !important;
    }

    #configModalDinamis .form-check-input:checked {
        background-color: #FF6D1F;
        border-color: #FF6D1F;
    }

    /* Preview Cepat Tabel Dinamis - 100% Identik dengan Preview Tabel API */
    #preview-container-dinamis {
        background: #ffffff !important;
    }

    #preview-container-dinamis table,
    #preview-container-dinamis .main-table,
    #preview-container-dinamis .table {
        font-size: 10px !important;
        width: 100% !important;
        border-collapse: collapse !important;
        background: #ffffff !important;
        margin-bottom: 0 !important;
        border: 1px solid #dee2e6 !important;
    }

    #preview-container-dinamis th,
    #preview-container-dinamis td {
        padding: 4px 8px !important;
        font-size: 10px !important;
        border: 1px solid #dee2e6 !important;
        vertical-align: middle !important;
    }

    #preview-container-dinamis thead {
        background: #ffffff !important;
    }

    #preview-container-dinamis thead th {
        background: #ffffff !important;
        color: #212529 !important;
        font-weight: 700 !important;
        text-align: center !important;
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }

    #preview-container-dinamis thead th div {
        color: #212529 !important;
        font-weight: 700 !important;
    }

    #preview-container-dinamis thead th i,
    #preview-container-dinamis thead th small {
        color: #6c757d !important;
        font-size: 9px !important;
        font-weight: normal !important;
        font-style: italic !important;
    }

    #preview-container-dinamis thead tr.num-row th {
        background: #ffffff !important;
        color: #6c757d !important;
        font-weight: normal !important;
        border: 1px solid #dee2e6 !important;
        font-size: 9px !important;
    }

    #preview-container-dinamis tbody tr {
        background: #ffffff !important;
    }

    #preview-container-dinamis tbody td {
        background: #ffffff !important;
        color: #212529 !important;
        border: 1px solid #dee2e6 !important;
    }

    #preview-container-dinamis .category-row td {
        background: #f8fafc !important;
        color: #334155 !important;
        font-weight: bold !important;
        border: 1px solid #dee2e6 !important;
        padding: 6px 10px !important;
    }

    #preview-container-dinamis .grand-total-row td,
    #preview-container-dinamis tr.total-row td {
        background: #f8fafc !important;
        color: #212529 !important;
        font-weight: bold !important;
        border: 1px solid #dee2e6 !important;
    }

    #preview-container-dinamis .table-responsive-dda {
        border: none !important;
        border-radius: 0 !important;
        margin-bottom: 0 !important;
        background: #ffffff !important;
    }
</style>

<div class="modal fade" id="configModalDinamis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-sliders me-2" style="color: #FF6D1F;"></i> KONFIGURASI TABEL
                    </h5>
                    <button type="button" class="btn-sm btn btn-outline-secondary rounded-pill px-3 fw-bold" onclick="toggleGuideDinamis()" style="font-size: 10px;">
                        <i class="bi bi-question-circle me-1"></i> PANDUAN PENGGUNA
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-3" id="configTabsDinamis">
                    <li class="nav-item flex-fill">
                        <button class="nav-link active w-100 fw-bold py-2 rounded-3" data-bs-toggle="tab" data-bs-target="#tab-format-dinamis">
                            Kolom & Format
                        </button>
                    </li>
                    <li class="nav-item flex-fill">
                        <button class="nav-link w-100 fw-bold py-2 rounded-3" data-bs-toggle="tab" data-bs-target="#tab-pivot-dinamis">
                            Pivot Mode
                        </button>
                    </li>
                </ul>

                <div id="guide-section-dinamis" class="mb-4 bg-info bg-opacity-10 p-3 rounded-4 border border-info border-opacity-25" style="display:none">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold text-info mb-0"><i class="bi bi-lightbulb-fill me-2"></i> Panduan Singkat</h6>
                        <button type="button" class="btn-close btn-close-sm" onclick="toggleGuideDinamis()" style="font-size:10px"></button>
                    </div>
                    <ul class="small text-dark mb-0 ps-3">
                        <li class="mb-1"><b>Grup Wilayah:</b> Mengelompokkan dan memberi sekat pembatas kategori antara Kabupaten dan Kota.</li>
                        <li class="mb-1"><b>Total Horizontal:</b> Menambahkan kolom kalkulasi (Jumlah / Rata-rata) di sisi kanan tabel.</li>
                        <li class="mb-1"><b>Total Vertikal:</b> Menampilkan baris total di baris terbawah (Jawa Tengah, Total Jumlah, atau runtun waktu).</li>
                        <li class="mb-1"><b>Pivot Mode:</b> Memutar orientasi Tahun menjadi baris vertikal (Portrait Mode) atau mengatur prioritas karakteristik.</li>
                        <li><b>Terapkan:</b> Klik tombol "Terapkan & Simpan" untuk memperbarui tampilan tabel secara langsung.</li>
                    </ul>
                </div>

                <form id="formConfigDinamis">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="id_indikator" value="<?= (int)($indikator->id ?? 0); ?>">
                    <input type="hidden" name="id_tabel" value="<?= (int)($tabel->id_master_tabel ?? ($tabel->id ?? 0)); ?>">

                    <div class="tab-content border rounded-4 bg-white p-3 shadow-sm" style="min-height: 280px;">
                        <!-- TAB 1: KOLOM & FORMAT -->
                        <div class="tab-pane fade show active" id="tab-format-dinamis">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch bg-light p-3 rounded-3 h-100 d-flex align-items-center">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_group_by_region" name="group_by_region" value="1" <?= (!empty($config_tampilan['group_by_region']) ? 'checked' : ''); ?> style="cursor: pointer;">
                                        <label class="form-check-label fw-bold small text-dark mb-0" for="cfg_group_by_region" style="cursor: pointer;">Grup Wilayah (Kab/Kota)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch bg-light p-3 rounded-3 h-100 d-flex align-items-center">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_group_total_horizontal" name="group_total_horizontal" value="1" <?= (!empty($config_tampilan['group_total_horizontal']) ? 'checked' : ''); ?> style="cursor: pointer;">
                                        <label class="form-check-label fw-bold small text-dark mb-0" for="cfg_group_total_horizontal" style="cursor: pointer;">Jumlah per Kelompok Kolom</label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3 opacity-25">

                            <!-- OPSI TOTAL HORIZONTAL (BARIS) -->
                            <div class="form-check form-switch mt-3 mb-1">
                                <input class="form-check-input" type="checkbox" id="cfg_total_horizontal" name="total_horizontal" value="1" <?= (!empty($config_tampilan['total_horizontal']) ? 'checked' : ''); ?> onchange="document.getElementById('sub_total_horizontal_options').style.display = this.checked ? 'block' : 'none'">
                                <label class="form-check-label small fw-bold" for="cfg_total_horizontal">Hitung Total Horizontal (Baris)</label>
                            </div>

                            <div id="sub_total_horizontal_options" class="ps-4 mb-3" style="<?= (!empty($config_tampilan['total_horizontal']) ? 'display:block;' : 'display:none;'); ?>">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="cfg_only_total_horizontal" name="only_total_horizontal" value="1" <?= (!empty($config_tampilan['only_total_horizontal']) ? 'checked' : ''); ?>>
                                    <label class="form-check-label small text-muted" for="cfg_only_total_horizontal">Sembunyikan Rincian (Hanya Tampilkan Total)</label>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="small text-secondary" style="font-size: 11.5px;">Metode Perhitungan:</span>
                                    <select name="jenis_total_horizontal" id="cfg_jenis_total_horizontal" class="form-select form-select-sm py-1 border-0 bg-light rounded-3" style="width: auto; font-size: 11.5px;">
                                        <option value="sum" <?= (($config_tampilan['jenis_total_horizontal'] ?? 'sum') === 'sum' ? 'selected' : ''); ?>>Jumlah / SUM</option>
                                        <option value="avg" <?= (($config_tampilan['jenis_total_horizontal'] ?? 'sum') === 'avg' ? 'selected' : ''); ?>>Rata-rata / AVERAGE</option>
                                    </select>
                                </div>
                            </div>

                            <!-- OPSI TOTAL VERTIKAL (KOLOM / JAWA TENGAH) -->
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="cfg_total_vertikal" name="total_vertikal" value="1" <?= (!empty($config_tampilan['total_vertikal']) ? 'checked' : ''); ?> onchange="document.getElementById('sub_total_vertikal_options').style.display = this.checked ? 'block' : 'none'">
                                <label class="form-check-label small fw-bold" for="cfg_total_vertikal">Hitung Total Vertikal (Grand Total)</label>
                            </div>

                            <div id="sub_total_vertikal_options" class="ps-4 mb-2" style="<?= (!empty($config_tampilan['total_vertikal']) ? 'display:block;' : 'display:none;'); ?>">
                                <div class="d-flex align-items-center gap-2 my-2">
                                    <span class="small text-secondary" style="font-size: 11.5px;">Label Baris Total:</span>
                                    <select name="jenis_total_vertikal" id="cfg_jenis_total_vertikal" class="form-select form-select-sm py-1 border-0 bg-light rounded-3" style="width: auto; font-size: 11.5px;" onchange="
                                        document.getElementById('wrapper_label_kustom').style.display = (this.value === 'kustom') ? 'block' : 'none';
                                        document.getElementById('wrapper_runtun_waktu_tahun').style.display = (this.value === 'runtun_waktu') ? 'block' : 'none';
                                    ">
                                        <option value="default" <?= (($config_tampilan['jenis_total_vertikal'] ?? 'default') === 'default' ? 'selected' : ''); ?>>Sesuai Master Baris / Otomatis</option>
                                        <option value="grand_total" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'grand_total' ? 'selected' : ''); ?>>Grand Total</option>
                                        <option value="jawa_tengah" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'jawa_tengah' ? 'selected' : ''); ?>>Jawa Tengah</option>
                                        <option value="jumlah" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'jumlah' ? 'selected' : ''); ?>>Jumlah</option>
                                        <option value="runtun_waktu" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'runtun_waktu' ? 'selected' : ''); ?>>Runtun Waktu Multi-Tahun</option>
                                        <option value="kustom" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'kustom' ? 'selected' : ''); ?>>Kustom...</option>
                                    </select>
                                    <div id="wrapper_label_kustom" style="<?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'kustom' ? 'display:block;' : 'display:none;'); ?>">
                                        <input type="text" name="label_total_kustom" id="cfg_label_total_kustom" class="form-control form-control-sm border-0 bg-light rounded-3 py-1" style="font-size: 11.5px;" value="<?= esc($config_tampilan['label_total_kustom'] ?? ''); ?>" placeholder="Tulis label baris total">
                                    </div>
                                </div>

                                <!-- OPSI KHUSUS RUNTUN WAKTU -->
                                <div class="mt-2 pt-2 border-top" id="wrapper_runtun_waktu_tahun" style="<?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'runtun_waktu' ? 'display:block;' : 'display:none;'); ?>">
                                    <label class="form-label fw-bold text-dark mb-1" style="font-size: 11.5px;">
                                        <i class="bi bi-clock-history me-1 text-warning"></i> Pilih Tahun Total yang Ditampilkan di Bawah:
                                    </label>
                                    <?php 
                                        $curYearVal = (int)($tabel->tahun ?? date('Y'));
                                        $defaultRuntunYears = [
                                            $curYearVal - 1,
                                            $curYearVal - 2,
                                            $curYearVal - 3,
                                            $curYearVal - 4,
                                            $curYearVal - 5,
                                        ];
                                        $savedRuntunYears = !empty($config_tampilan['tahun_runtun_waktu']) ? (array)$config_tampilan['tahun_runtun_waktu'] : $defaultRuntunYears;
                                        $savedRuntunYears = array_values(array_unique(array_filter(array_map('intval', $savedRuntunYears))));
                                        rsort($savedRuntunYears);

                                        $availableChoiceYears = [];
                                        for ($y = $curYearVal; $y >= $curYearVal - 7; $y--) {
                                            $availableChoiceYears[] = $y;
                                        }
                                    ?>
                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                        <?php foreach ($availableChoiceYears as $yrOpt): 
                                            $isChk = in_array($yrOpt, $savedRuntunYears, true);
                                        ?>
                                            <label class="btn btn-outline-primary btn-sm py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11px; cursor: pointer;">
                                                <input type="checkbox" class="chk-runtun-tahun form-check-input mt-0" value="<?= $yrOpt; ?>" <?= $isChk ? 'checked' : ''; ?> onchange="syncRuntunYearsInput()">
                                                <span><?= $yrOpt; ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto">
                                            <span class="text-muted" style="font-size: 11px;">Urutan tahun:</span>
                                        </div>
                                        <div class="col">
                                            <input type="text" id="cfg_tahun_runtun_waktu_custom" class="form-control form-control-sm border-0 bg-light rounded-3 py-1" style="font-size: 11px; max-width: 280px;" value="<?= implode(',', $savedRuntunYears); ?>" placeholder="Contoh: 2025,2024,2023,2022,2021">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: PIVOT MODE -->
                        <div class="tab-pane fade" id="tab-pivot-dinamis">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="cfg_prioritas_karakteristik" name="prioritas_karakteristik" value="1" <?= (!empty($config_tampilan['prioritas_karakteristik']) ? 'checked' : ''); ?>>
                                <label class="form-check-label small fw-bold" for="cfg_prioritas_karakteristik" style="color: #FF6D1F;">
                                    <i class="bi bi-diagram-3-fill me-1"></i> Prioritas Karakteristik (Karakteristik &raquo; Tahun)
                                </label>
                                <small class="text-muted d-block ps-1" style="font-size: 11px;">Default OFF: Tahun di atas Karakteristik. Jika ON: Karakteristik di atas Tahun.</small>
                            </div>

                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="cfg_portrait_mode" name="portrait_mode" value="1" <?= (!empty($config_tampilan['portrait_mode']) ? 'checked' : ''); ?> onchange="document.getElementById('portrait_options').style.display = this.checked ? 'block' : 'none'">
                                <label class="form-check-label small fw-bold text-success" for="cfg_portrait_mode">
                                    <i class="bi bi-layout-text-sidebar-reverse me-1"></i> Jadikan Periode sebagai Baris (Portrait Mode)
                                </label>
                                <small class="text-muted d-block ps-1" style="font-size: 11px;">Memutar Tahun/Periode menjadi sub-baris vertikal di sebelah kiri.</small>
                            </div>

                            <div id="portrait_options" class="ps-4 mb-4" style="<?= (!empty($config_tampilan['portrait_mode']) ? 'display:block;' : 'display:none;'); ?>">
                                <div class="d-flex align-items-center gap-3 pt-2">
                                    <span class="small fw-semibold text-secondary" style="font-size: 11.5px;">Format Kolom Baris:</span>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="portrait_split_col" id="cfg_split_false" value="0" <?= (empty($config_tampilan['portrait_split_col']) ? 'checked' : ''); ?>>
                                        <label class="form-check-label small" for="cfg_split_false" style="font-size: 11.5px;">Gabung 1 Kolom</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="portrait_split_col" id="cfg_split_true" value="1" <?= (!empty($config_tampilan['portrait_split_col']) ? 'checked' : ''); ?>>
                                        <label class="form-check-label small" for="cfg_split_true" style="font-size: 11.5px;">Pisah 2 Kolom (Kolom A: Wilayah, Kolom B: Tahun)</label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3 opacity-25">

                            <div class="d-flex align-items-center gap-3">
                                <label class="form-label fw-bold small text-dark mb-0" style="font-size: 11.5px;">Kolom Nomor Urut:</label>
                                <select name="kolom_nomor" class="form-select form-select-sm border-0 bg-light rounded-3 py-1" style="width: auto; font-size: 11.5px;">
                                    <option value="sembunyikan" <?= (($indikator->kolom_nomor ?? 'sembunyikan') === 'sembunyikan' ? 'selected' : ''); ?>>Sembunyikan Nomor</option>
                                    <option value="tampilkan" <?= (($indikator->kolom_nomor ?? 'sembunyikan') === 'tampilkan' ? 'selected' : ''); ?>>Tampilkan Nomor Urut (1, 2, 3..)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- PREVIEW AREA (Outside Tabs for visibility) -->
                <div id="preview-area-dinamis" class="mt-4 border-top pt-3" style="display:none">
                    <h6 class="fw-bold small text-muted mb-2 ps-1">
                        <i class="bi bi-eye me-1"></i> Preview Cepat (5 Baris)
                    </h6>
                    <div id="preview-container-dinamis" class="bg-white border rounded-4 overflow-auto" style="max-height: 220px; font-size: 10px;"></div>
                    <div class="text-center mt-2">
                        <button type="button" onclick="document.getElementById('preview-area-dinamis').style.display='none'" class="btn btn-sm btn-link text-muted text-decoration-none" style="font-size: 11px;">
                            Sembunyikan Preview
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 p-4 pt-2 d-flex justify-content-between">
                <button type="button" class="btn btn-light px-4 fw-bold rounded-3 text-muted" data-bs-dismiss="modal">Tutup</button>
                <div class="d-flex gap-2">
                    <button type="button" onclick="previewConfigDinamis()" class="btn btn-info text-white px-4 fw-bold rounded-pill">
                        <i class="bi bi-eye me-1"></i> Preview
                    </button>
                    <button type="button" onclick="submitConfigDinamis(this)" class="btn btn-primary px-4 fw-bold rounded-pill shadow-primary" style="background: #FF6D1F; border: none;">
                        <i class="bi bi-check-circle me-1"></i> Terapkan & Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleGuideDinamis() {
    var g = document.getElementById('guide-section-dinamis');
    if (g) {
        g.style.display = (g.style.display === 'none' || g.style.display === '') ? 'block' : 'none';
    }
}

function syncRuntunYearsInput() {
    var yrs = [];
    document.querySelectorAll('.chk-runtun-tahun:checked').forEach(function(el) {
        var v = parseInt(el.value, 10);
        if (!isNaN(v) && v > 0) yrs.push(v);
    });
    yrs.sort(function(a, b) { return b - a; });
    var customInp = document.getElementById('cfg_tahun_runtun_waktu_custom');
    if (customInp) customInp.value = yrs.join(',');
}

function buildConfigDinamisPayload() {
    var form = document.getElementById('formConfigDinamis');
    var splitRadio = form.querySelector('input[name="portrait_split_col"]:checked');

    var runtunYears = [];
    var customInp = document.getElementById('cfg_tahun_runtun_waktu_custom');
    if (customInp && customInp.value.trim() !== '') {
        runtunYears = customInp.value.split(',').map(function(y) {
            return parseInt(y.trim(), 10);
        }).filter(function(y) {
            return !isNaN(y) && y > 0;
        });
    }
    if (runtunYears.length === 0) {
        document.querySelectorAll('.chk-runtun-tahun:checked').forEach(function(el) {
            var v = parseInt(el.value, 10);
            if (!isNaN(v) && v > 0) runtunYears.push(v);
        });
    }
    runtunYears = Array.from(new Set(runtunYears));
    runtunYears.sort(function(a, b) { return b - a; });

    return {
        total_horizontal: document.getElementById('cfg_total_horizontal').checked,
        jenis_total_horizontal: document.getElementById('cfg_jenis_total_horizontal').value || 'sum',
        only_total_horizontal: document.getElementById('cfg_only_total_horizontal').checked,
        group_total_horizontal: document.getElementById('cfg_group_total_horizontal').checked,
        total_vertikal: document.getElementById('cfg_total_vertikal').checked,
        jenis_total_vertikal: document.getElementById('cfg_jenis_total_vertikal').value || 'default',
        label_total_kustom: document.getElementById('cfg_label_total_kustom').value || '',
        tahun_runtun_waktu: runtunYears,
        group_by_region: document.getElementById('cfg_group_by_region').checked,
        prioritas_karakteristik: document.getElementById('cfg_prioritas_karakteristik').checked,
        portrait_mode: document.getElementById('cfg_portrait_mode').checked,
        portrait_split_col: splitRadio ? (splitRadio.value === '1') : false,

        kolom_nomor: (form.querySelector('select[name="kolom_nomor"]') ? form.querySelector('select[name="kolom_nomor"]').value : 'sembunyikan')
    };
}

function previewConfigDinamis() {
    var form = document.getElementById('formConfigDinamis');
    var formData = new FormData(form);
    var payload = buildConfigDinamisPayload();
    formData.set('config_json', JSON.stringify(payload));

    // Sertakan pilihan tahun aktif jika ada di URL
    var currentUrl = new URL(window.location.href);
    var urlYears = currentUrl.searchParams.get('tahun') || currentUrl.searchParams.get('years');
    if (urlYears) {
        formData.set('tahun', urlYears);
    }

    var area = document.getElementById('preview-area-dinamis');
    var container = document.getElementById('preview-container-dinamis');
    if (!area || !container) return;

    area.style.display = 'block';
    container.innerHTML = '<div class="text-center p-4 text-muted small"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Menyiapkan preview...</div>';

    // Smooth scroll ke area preview di dalam modal
    setTimeout(function() {
        area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);

    fetch('<?= base_url("admin/ajax_preview_matrix_dinamis"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success' && data.html) {
            container.innerHTML = data.html;
        } else {
            container.innerHTML = '<div class="alert alert-warning m-2 p-2 small"><i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat preview: ' + (data.message || 'Respons tidak valid') + '</div>';
        }
    })
    .catch(function(err) {
        container.innerHTML = '<div class="alert alert-danger m-2 p-2 small"><i class="bi bi-x-circle me-1"></i> Terjadi kesalahan koneksi server: ' + err + '</div>';
    });
}

function submitConfigDinamis(btn) {
    var form = document.getElementById('formConfigDinamis');
    var formData = new FormData(form);
    var payload = buildConfigDinamisPayload();
    formData.set('config_json', JSON.stringify(payload));

    if (btn) btn.disabled = true;

    fetch('<?= base_url("admin/ajax_save_konfigurasi_tampilan_dinamis"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            var url = new URL(window.location.href);
            url.searchParams.delete('preview_config');
            window.location.href = url.toString();
        } else {
            alert('Gagal menyimpan konfigurasi: ' + (data.message || 'Terjadi kesalahan'));
            if (btn) btn.disabled = false;
        }
    })
    .catch(function(err) {
        alert('Terjadi kesalahan koneksi server: ' + err);
        if (btn) btn.disabled = false;
    });
}
</script>
