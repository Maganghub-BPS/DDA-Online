<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
?>  

<div class="row mb-3 flex-shrink-0">
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

<div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column mb-0" style="min-height: 0;">
    <div class="card-body p-0 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
        <div class="table-responsive table-scroll-container flex-grow-1" style="height: 100%;">
            <table class="table table-hover align-middle mb-0 table-sticky-header" id="tableForum">
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
                        $no = 1;
                        foreach ($data as $b) {
                    ?>
                    <tr class="opd-row">
                        <td class="text-center forum-no"><?php echo $no;?></td>
                        <td class="opd-name">
                            <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_topik?id=<?php echo esc($b->id_unitkerja, 'url');?>" class="text-decoration-none fw-medium text-primary">
                                <i class="bi bi-folder2-open me-2 text-warning"></i><?php echo esc($b->unitkerja_ind);?>
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

<style>
/* Kontainer scroll internal agar scrollbar ada di dalam dan header tetap terlihat */
.table-scroll-container {
    max-height: calc(100vh - 235px);
    min-height: 350px;
    height: 100%;
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: auto;
    position: relative;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

/* Custom Slim Scrollbar */
.table-scroll-container::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.table-scroll-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.table-scroll-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.table-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Sticky Table Header */
.table-sticky-header thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8fafc !important;
    box-shadow: inset 0 -2px 0 #e2e8f0;
    vertical-align: middle;
}
</style>
