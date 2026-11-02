<!-- ========================================================================= -->
<!-- MODAL: KONFIGURASI TAMPILAN TABEL DINAMIS (modal_config_dinamis.php)      -->
<!-- 100% Mengadopsi Pola & UX modal_config_tabel.php Milik Tabel API          -->
<!-- ========================================================================= -->
<div class="modal fade" id="configModalDinamis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-sliders fs-5" style="color: #FF6D1F;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">KONFIGURASI TABEL DINAMIS</h5>
                        <small class="text-muted" style="font-size: 0.8rem;">Sesuaikan penyajian kolom, orientasi pivot, dan agregasi total</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-3" id="configTabsDinamis">
                    <li class="nav-item flex-fill">
                        <button class="nav-link active w-100 fw-bold py-2 rounded-3" data-bs-toggle="tab" data-bs-target="#tab-format-dinamis">
                            <i class="bi bi-layout-three-columns me-1"></i> Kolom, Format & Agregasi
                        </button>
                    </li>
                    <li class="nav-item flex-fill">
                        <button class="nav-link w-100 fw-bold py-2 rounded-3" data-bs-toggle="tab" data-bs-target="#tab-pivot-dinamis">
                            <i class="bi bi-arrow-repeat me-1"></i> Pivot & Orientasi Matriks
                        </button>
                    </li>
                </ul>

                <form id="formConfigDinamis">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="id_indikator" value="<?= (int)($indikator->id ?? 0); ?>">
                    <input type="hidden" name="id_tabel" value="<?= (int)($tabel->id_master_tabel ?? ($tabel->id ?? 0)); ?>">

                    <div class="tab-content border rounded-4 bg-white p-4 shadow-sm">
                        <!-- TAB 1: KOLOM, FORMAT & AGREGASI -->
                        <div class="tab-pane fade show active" id="tab-format-dinamis">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch bg-light p-3 rounded-3 h-100 d-flex align-items-center">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_group_by_region" name="group_by_region" value="1" <?= (!empty($config_tampilan['group_by_region']) ? 'checked' : ''); ?>>
                                        <div>
                                            <label class="form-check-label fw-bold small text-dark mb-0 d-block" for="cfg_group_by_region">Grup Wilayah (Kab/Kota)</label>
                                            <small class="text-muted" style="font-size: 11px;">Tampilkan sekat pembatas kategori Kabupaten & Kota.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch bg-light p-3 rounded-3 h-100 d-flex align-items-center">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_group_total_horizontal" name="group_total_horizontal" value="1" <?= (!empty($config_tampilan['group_total_horizontal']) ? 'checked' : ''); ?>>
                                        <div>
                                            <label class="form-check-label fw-bold small text-dark mb-0 d-block" for="cfg_group_total_horizontal">Jumlah per Kelompok Kolom</label>
                                            <small class="text-muted" style="font-size: 11px;">Sisipkan sub-kolom Jumlah di akhir tiap kelompok.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded-3">
                                        <div class="form-check form-switch d-flex align-items-center mb-2">
                                            <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_total_horizontal" name="total_horizontal" value="1" <?= (!empty($config_tampilan['total_horizontal']) ? 'checked' : ''); ?> onchange="document.getElementById('sub_total_horizontal_options').style.display = this.checked ? 'block' : 'none'">
                                            <div>
                                                <label class="form-check-label fw-bold small text-dark mb-0 d-block" for="cfg_total_horizontal">Hitung Total Horizontal (Baris)</label>
                                                <small class="text-muted" style="font-size: 11px;">Menambahkan kolom Total / Rata-rata di paling kanan tabel.</small>
                                            </div>
                                        </div>

                                        <div id="sub_total_horizontal_options" class="ps-5 pt-2" style="<?= (!empty($config_tampilan['total_horizontal']) ? 'display:block;' : 'display:none;'); ?>">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto">
                                                    <span class="small fw-semibold text-secondary">Metode:</span>
                                                </div>
                                                <div class="col-auto">
                                                    <select name="jenis_total_horizontal" id="cfg_jenis_total_horizontal" class="form-select form-select-sm" style="width: auto;">
                                                        <option value="sum" <?= (($config_tampilan['jenis_total_horizontal'] ?? 'sum') === 'sum' ? 'selected' : ''); ?>>Jumlah / SUM</option>
                                                        <option value="avg" <?= (($config_tampilan['jenis_total_horizontal'] ?? 'sum') === 'avg' ? 'selected' : ''); ?>>Rata-rata / AVERAGE</option>
                                                    </select>
                                                </div>
                                                <div class="col-auto ms-3">
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" id="cfg_only_total_horizontal" name="only_total_horizontal" value="1" <?= (!empty($config_tampilan['only_total_horizontal']) ? 'checked' : ''); ?>>
                                                        <label class="form-check-label small text-muted" for="cfg_only_total_horizontal">Sembunyikan Rincian (Hanya Total)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded-3">
                                        <div class="form-check form-switch d-flex align-items-center mb-2">
                                            <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_total_vertikal" name="total_vertikal" value="1" <?= (!empty($config_tampilan['total_vertikal']) ? 'checked' : ''); ?> onchange="document.getElementById('sub_total_vertikal_options').style.display = this.checked ? 'block' : 'none'">
                                            <div>
                                                <label class="form-check-label fw-bold small text-dark mb-0 d-block" for="cfg_total_vertikal">Tampilkan Total Vertikal (Baris JT / Grand Total)</label>
                                                <small class="text-muted" style="font-size: 11px;">Baris paling bawah: tampilkan angka Excel atau hitung otomatis vertikal SUM.</small>
                                            </div>
                                        </div>

                                        <div id="sub_total_vertikal_options" class="ps-5 pt-2" style="<?= (!empty($config_tampilan['total_vertikal']) ? 'display:block;' : 'display:none;'); ?>">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto">
                                                    <span class="small fw-semibold text-secondary">Label Baris Total:</span>
                                                </div>
                                                <div class="col-auto">
                                                    <select name="jenis_total_vertikal" id="cfg_jenis_total_vertikal" class="form-select form-select-sm" style="width: auto;" onchange="
                                                        document.getElementById('wrapper_label_kustom').style.display = (this.value === 'kustom') ? 'block' : 'none';
                                                        document.getElementById('wrapper_runtun_waktu_tahun').style.display = (this.value === 'runtun_waktu') ? 'block' : 'none';
                                                    ">
                                                        <option value="default" <?= (($config_tampilan['jenis_total_vertikal'] ?? 'default') === 'default' ? 'selected' : ''); ?>>Sesuai Master Baris / Otomatis</option>
                                                        <option value="jawa_tengah" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'jawa_tengah' ? 'selected' : ''); ?>>Jawa Tengah</option>
                                                        <option value="jumlah" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'jumlah' ? 'selected' : ''); ?>>Jumlah</option>
                                                        <option value="runtun_waktu" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'runtun_waktu' ? 'selected' : ''); ?>>Runtun Waktu Multi-Tahun</option>
                                                        <option value="kustom" <?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'kustom' ? 'selected' : ''); ?>>Kustom...</option>
                                                    </select>
                                                </div>
                                                <div class="col-auto" id="wrapper_label_kustom" style="<?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'kustom' ? 'display:block;' : 'display:none;'); ?>">
                                                    <input type="text" name="label_total_kustom" id="cfg_label_total_kustom" class="form-control form-control-sm" value="<?= esc($config_tampilan['label_total_kustom'] ?? ''); ?>" placeholder="Tulis label baris total">
                                                </div>
                                            </div>

                                            <!-- OPSI KHUSUS RUNTUN WAKTU: PILIHAN TAHUN TOTAL YANG DITAMPILKAN DI BAWAH -->
                                            <div class="mt-3 pt-2 border-top" id="wrapper_runtun_waktu_tahun" style="<?= (($config_tampilan['jenis_total_vertikal'] ?? '') === 'runtun_waktu' ? 'display:block;' : 'display:none;'); ?>">
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
                                                        <span class="text-muted" style="font-size: 11px;">Urutan tahun (dipisahkan koma):</span>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" id="cfg_tahun_runtun_waktu_custom" class="form-control form-control-sm" style="font-size: 11px; max-width: 280px;" value="<?= implode(',', $savedRuntunYears); ?>" placeholder="Contoh: 2025,2024,2023,2022,2021">
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1" style="font-size: 10.5px;">Baris total di bawah tabel akan menyajikan data per tahun sesuai pilihan di atas secara berurutan.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: PIVOT & ORIENTASI -->
                        <div class="tab-pane fade" id="tab-pivot-dinamis">
                            <div class="form-check form-switch bg-light p-3 rounded-3 mb-3 d-flex align-items-center">
                                <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_prioritas_karakteristik" name="prioritas_karakteristik" value="1" <?= (!empty($config_tampilan['prioritas_karakteristik']) ? 'checked' : ''); ?>>
                                <div>
                                    <label class="form-check-label fw-bold small text-dark mb-0 d-block" for="cfg_prioritas_karakteristik">
                                        <i class="bi bi-diagram-3-fill text-primary me-1"></i> Prioritas Karakteristik (Karakteristik &raquo; Tahun)
                                    </label>
                                    <small class="text-muted" style="font-size: 11px;">Default OFF: Tahun di atas Karakteristik. Jika ON: Karakteristik di atas Tahun.</small>
                                </div>
                            </div>

                            <div class="bg-light p-3 rounded-3 mb-3">
                                <div class="form-check form-switch d-flex align-items-center mb-2">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" id="cfg_portrait_mode" name="portrait_mode" value="1" <?= (!empty($config_tampilan['portrait_mode']) ? 'checked' : ''); ?> onchange="document.getElementById('portrait_options').style.display = this.checked ? 'block' : 'none'">
                                    <div>
                                        <label class="form-check-label fw-bold small text-dark mb-0 d-block" for="cfg_portrait_mode">
                                            <i class="bi bi-layout-text-sidebar-reverse text-success me-1"></i> Jadikan Periode sebagai Baris (Portrait Mode)
                                        </label>
                                        <small class="text-muted" style="font-size: 11px;">Memutar Tahun/Periode menjadi sub-baris vertikal di sebelah kiri.</small>
                                    </div>
                                </div>

                                <div id="portrait_options" class="ps-5 pt-2" style="<?= (!empty($config_tampilan['portrait_mode']) ? 'display:block;' : 'display:none;'); ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="small fw-semibold text-secondary">Format Kolom Baris:</span>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" name="portrait_split_col" id="cfg_split_false" value="0" <?= (empty($config_tampilan['portrait_split_col']) ? 'checked' : ''); ?>>
                                            <label class="form-check-label small" for="cfg_split_false">Gabung 1 Kolom (Wilayah - Tahun)</label>
                                        </div>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" name="portrait_split_col" id="cfg_split_true" value="1" <?= (!empty($config_tampilan['portrait_split_col']) ? 'checked' : ''); ?>>
                                            <label class="form-check-label small" for="cfg_split_true">Pisah 2 Kolom (Kolom A: Wilayah, Kolom B: Tahun)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-light p-3 rounded-3 mb-3">
                                <label class="form-label fw-bold small text-dark mb-1">Kolom Nomor Urut</label>
                                <select name="kolom_nomor" class="form-select form-select-sm">
                                    <option value="sembunyikan" <?= (($indikator->kolom_nomor ?? 'sembunyikan') === 'sembunyikan' ? 'selected' : ''); ?>>Sembunyikan Nomor</option>
                                    <option value="tampilkan" <?= (($indikator->kolom_nomor ?? 'sembunyikan') === 'tampilkan' ? 'selected' : ''); ?>>Tampilkan Nomor Urut (1, 2, 3..)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-semibold" onclick="previewConfigDinamis()">
                        <i class="bi bi-eye me-1"></i> Preview
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold" onclick="submitConfigDinamis(this)" style="background: #FF6D1F; border: none; box-shadow: 0 4px 10px rgba(255,109,31,0.3) !important;">
                        <i class="bi bi-check2-circle me-1"></i> Terapkan & Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
    var payload = buildConfigDinamisPayload();
    var url = new URL(window.location.href);
    url.searchParams.set('preview_config', JSON.stringify(payload));
    window.location.href = url.toString();
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

