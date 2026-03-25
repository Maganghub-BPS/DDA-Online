<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
echo session()->getFlashdata("k");
?>  

<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-chat-square-text me-2"></i>FORUM DISKUSI
                </h5>
                <div class="input-group input-group-sm" style="max-width: 300px;">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="searchOPD" placeholder="Cari OPD (otomatis)..." autocomplete="off">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-0">
    <div class="row">
        <div class="col-sm-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tableForum">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">No.</th>
                                    <th width="95%">Nama OPD</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (empty($data)) {
                                    echo "<tr><td colspan='2' class='text-center py-4 text-muted'><i class='bi bi-inbox me-2'></i>Data tidak ditemukan</td></tr>";
                                } else {
                                    $no = (isset($offset) ? $offset : 0) + 1;
                                    foreach ($data as $b) {
                                ?>
                                <tr class="opd-row">
                                    <td class="text-center forum-no"><?php echo $no;?></td>
                                    <td class="opd-name">
                                        <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_topik?id=<?php echo $b->id_unitkerja ;?>" class="text-decoration-none fw-medium text-primary">
                                            <i class="bi bi-folder2-open me-2 text-warning"></i><?php echo $b->unitkerja_ind;?>
                                        </a>
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
                </div>
                <div class="card-footer bg-white text-center pb-3">
                    <?php if(!empty($pagi)): ?>
                    <div class="mt-2 text-center">
                        <?php echo $pagi; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#searchOPD').on('input', function() {
        var keyword = $(this).val().toLowerCase().trim();
        var $rows = $('.opd-row');
        var num = 1;
        
        $rows.each(function() {
            var opdName = $(this).find('.opd-name').text().toLowerCase();
            if (opdName.indexOf(keyword) > -1) {
                $(this).show();
                $(this).find('.forum-no').text(num++);
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
