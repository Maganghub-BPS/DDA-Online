<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
?>  

<style>
/* Pastikan flexbox container luar tidak mengompres kartu Forum Diskusi */
.main-content {
    min-height: auto !important;
    padding-bottom: 12px !important;
}
.content-body {
    min-height: auto !important;
    flex: 1 0 auto !important;
}
#mainForumCard {
    height: auto !important;
    max-height: calc(100vh - 82px) !important;
    min-height: 0 !important;
    display: flex;
    flex-direction: column;
    flex-shrink: 0 !important;
}

/* Kontainer scroll internal Forum Diskusi */
.table-scroll-container {
    height: auto !important;
    max-height: calc(100vh - 82px - 65px) !important;
    min-height: 0 !important;
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: hidden !important;
    position: relative;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

/* Custom Slim Scrollbar 6px */
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

<div class="card border-0 shadow-sm border-radius-2xl mb-0" id="mainForumCard" style="height: auto; max-height: calc(100vh - 82px); min-height: 0; display: flex; flex-direction: column; overflow: hidden; flex-shrink: 0;">
    <!-- Header Toolbar: Judul & Kotak Pencarian Realtime -->
    <div class="card-header pb-2 pt-3 px-4 bg-white border-0 flex-shrink-0">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="fw-bold mb-0 text-primary">
                <i class="bi bi-chat-square-text me-2"></i>FORUM DISKUSI
            </h5>
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="searchOPD" placeholder="Cari OPD (otomatis)..." autocomplete="off">
            </div>
        </div>
    </div>
    
    <hr class="horizontal dark my-0 flex-shrink-0">
    
    <!-- Body: Tabel Forum dengan Scroll Internal Adaptif -->
    <div class="card-body p-0 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
        <div class="table-responsive table-scroll-container flex-grow-1" style="height: auto; min-height: 0; overflow-y: auto; overflow-x: hidden;">
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
                    <tr class="no-result-row d-none" id="noResultRow">
                        <td colspan="2" class="text-center py-4 text-danger fw-bold">-- Tidak ada OPD yang cocok dengan pencarian --</td>
                    </tr>
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
        var $noResult = $('#noResultRow');
        var num = 1;
        var visibleCount = 0;
        
        $rows.each(function() {
            var opdName = $(this).find('.opd-name').text().toLowerCase();
            if (keyword === '' || opdName.indexOf(keyword) > -1) {
                $(this).removeClass('d-none').show();
                $(this).find('.forum-no').text(num++);
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
    });
});
</script>
