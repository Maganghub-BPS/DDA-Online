<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
echo session()->getFlashdata("k");
?>  

<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-building me-2"></i>Daftar Organisasi Daerah
                    </h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-md-nowrap">
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="searchOPD" placeholder="Cari organisasi..." autocomplete="off">
                        </div>
                        <form method="GET" action="<?php echo base_url(); ?>index.php/admin/dda" class="d-flex align-items-center gap-2 flex-nowrap m-0">
                            <?php if (in_array(session()->get('admin_level'), ['Admin', 'Super Admin'])): ?>
                            <select name="filter_jenis" id="filterJenisInstansi" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; min-width: 175px;">
                                <option value="all" <?php echo (isset($selected_jenis) && $selected_jenis == 'all') ? 'selected' : ''; ?>>-- Semua Organisasi --</option>
                                <option value="opd" <?php echo (isset($selected_jenis) && $selected_jenis == 'opd') ? 'selected' : ''; ?>>Organisasi Perangkat Daerah</option>
                                <option value="vertikal" <?php echo (isset($selected_jenis) && $selected_jenis == 'vertikal') ? 'selected' : ''; ?>>Organisasi Vertikal</option>
                            </select>
                            <?php endif; ?>
                            <select name="filter_tahun" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; min-width: 135px;">
                                <option value="all" <?php echo (isset($selected_tahun) && $selected_tahun == 'all') ? 'selected' : ''; ?>>-- Semua Tahun --</option>
                                <?php 
                                for ($i = 2020; $i <= (date('Y')+1); $i++) {
                                    $sel = (isset($selected_tahun) && $selected_tahun == $i) ? 'selected' : '';
                                    echo "<option value='$i' $sel>$i</option>";
                                }
                                ?>
                            </select>
                            <?php if((isset($selected_tahun) && $selected_tahun != 'all') || (isset($selected_jenis) && $selected_jenis != 'all')): ?>
                                <a href="<?php echo base_url(); ?>index.php/admin/dda?action=reset_dda" class="btn btn-sm btn-light border text-danger d-inline-flex align-items-center justify-content-center" title="Reset Filter" style="height: 31px; width: 31px;">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableOPD">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No.</th>
                        <th width="65%">Nama Organisasi</th>
                        <?php if(session()->get('admin_level') != 'lo'): ?>
                        <th width="30%" class="text-center">Penanggung Jawab (Tim)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='3' class='text-center py-5 text-muted'><i class='bi bi-inbox me-2 d-block mb-3 display-6'></i>Data tidak ditemukan</td></tr>";
                    } else {
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                    ?>
                    <tr class="opd-row" data-jenis="<?php echo $b->jenis_instansi ?? 'vertikal'; ?>">
                        <td class="text-center opd-no"><?php echo $no;?></td>
                        <td>
                            <a href="<?php echo base_URL()?>index.php/admin/dda/periksa_tabel?id=<?php echo $b->id_unitkerja ;?>" class="text-decoration-none fw-medium text-dark d-inline-flex align-items-center">
                                <i class="bi bi-folder-check me-2 text-primary"></i><?php echo $b->unitkerja_ind;?>
                            </a>
                        </td>
                        <?php if(session()->get('admin_level') != 'lo'): ?>
                        <td class="text-center text-muted small">
                            <span class="badge bg-light text-dark border"><i class="bi bi-person-badge me-1"></i><?php echo $b->user_wali;?></span>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php 
                        $no++;
                        }
                    }
                    ?>
                    <tr class="no-result-row d-none" id="noResultRow">
                        <td colspan="3" class="text-center py-4 text-danger fw-bold">-- Tidak ada organisasi yang cocok dengan pencarian --</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-center pb-3">
        <?php if(!empty($pagi)): ?>
        <div class="mt-2 text-center">
            <?php echo $pagi; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    function applyDdaFilter() {
        var keyword = $('#searchOPD').val().toLowerCase().trim();
        var selectedJenis = $('#filterJenisInstansi').length ? ($('#filterJenisInstansi').val() || 'all') : 'all';
        var $rows = $('.opd-row');
        var $noResult = $('#noResultRow');
        var visibleCount = 0;
        var num = 1;

        $rows.each(function() {
            var orgText = $(this).find('td:nth-child(2)').text().toLowerCase();
            var jenis = $(this).attr('data-jenis') || 'vertikal';

            var matchKeyword = (keyword === '' || orgText.indexOf(keyword) > -1);
            var matchJenis = (selectedJenis === 'all' || jenis === selectedJenis);

            if (matchKeyword && matchJenis) {
                $(this).removeClass('d-none').show();
                $(this).find('.opd-no').text(num++);
                visibleCount++;
            } else {
                $(this).addClass('d-none').hide();
            }
        });

        if (visibleCount === 0) {
            $noResult.removeClass('d-none').show();
        } else {
            $noResult.addClass('d-none').hide();
        }
    }

    $('#searchOPD').on('input', applyDdaFilter);
});
</script>