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
                        <i class="bi bi-building me-2"></i>Daftar Perangkat Daerah (OPD)
                    </h5>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group input-group-sm" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="searchOPD" placeholder="Cari OPD (otomatis)..." autocomplete="off">
                        </div>
                        <form method="GET" action="<?php echo base_url(); ?>index.php/admin/dda" class="d-flex gap-2">
                            <select name="filter_tahun" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                <option value="all" <?php echo (isset($selected_tahun) && $selected_tahun == 'all') ? 'selected' : ''; ?>>-- Semua Tahun --</option>
                                <?php 
                                for ($i = 2020; $i <= (date('Y')+1); $i++) {
                                    $sel = (isset($selected_tahun) && $selected_tahun == $i) ? 'selected' : '';
                                    echo "<option value='$i' $sel>$i</option>";
                                }
                                ?>
                            </select>
                            <?php if(isset($selected_tahun) && $selected_tahun != 'all'): ?>
                                <a href="<?php echo base_url(); ?>index.php/admin/dda?action=reset_dda" class="btn btn-sm btn-light border" title="Reset Tahun">
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
                        <th width="65%">Nama OPD</th>
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
                    <tr class="opd-row">
                        <td class="text-center opd-no"><?php echo $no;?></td>
                        <td>
                            <?php if(session()->get('admin_level') == 'lo'): ?>
                                <a href="<?php echo base_URL()?>index.php/admin/dda/view_tabel?id=<?php echo $b->id_unitkerja ;?>" class="text-decoration-none fw-medium text-dark">
                                    <i class="bi bi-folder2 me-2 text-warning"></i><?php echo $b->unitkerja_ind;?>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo base_URL()?>index.php/admin/dda/periksa_tabel?id=<?php echo $b->id_unitkerja ;?>" class="text-decoration-none fw-medium text-dark">
                                    <i class="bi bi-folder-check me-2 text-primary"></i><?php echo $b->unitkerja_ind;?>
                                </a>
                            <?php endif; ?>
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
                        <td colspan="3" class="text-center py-4 text-danger fw-bold">-- Tidak ada OPD yang cocok dengan pencarian --</td>
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
    $('#searchOPD').on('input', function() {
        var keyword = $(this).val().toLowerCase().trim();
        var $rows = $('.opd-row');
        var $noResult = $('#noResultRow');
        var visibleCount = 0;
        var num = 1;
        
        $rows.each(function() {
            var opdName = $(this).find('td:nth-child(2)').text().toLowerCase();
            if (opdName.indexOf(keyword) > -1) {
                $(this).removeClass('d-none').show();
                $(this).find('.opd-no').text(num++);
                visibleCount++;
            } else {
                $(this).addClass('d-none').hide();
            }
        });

        if(visibleCount == 0 && keyword != '') {
            $noResult.removeClass('d-none').show();
        } else {
            $noResult.addClass('d-none').hide();
        }
    });
});
</script>
