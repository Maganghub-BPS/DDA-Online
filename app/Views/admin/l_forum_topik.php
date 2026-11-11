<?php
$id_unitkerja = (string)service('request')->getGet('id');
$unitkerjalogin = session()->get('admin_unitkerja');
$hariini = tgl_jam_sql(date('Y-m-d'));

$db = \Config\Database::connect();
$query_unitkerja = $db->query("SELECT * FROM m_unitkerja WHERE id_unitkerja = ? LIMIT 1", [$id_unitkerja])->getRow();
$nama_opd = $query_unitkerja ? $query_unitkerja->unitkerja_ind : 'Forum OPD';

$commentCounts = [];
if (!empty($data)) {
    $topikIds = array_values(array_filter(array_map(fn($r) => (int)$r->id_topik, $data)));
    if (!empty($topikIds)) {
        $placeholders = implode(',', array_fill(0, count($topikIds), '?'));
        $rowsCount = $db->query("SELECT id_topik, COUNT(*) AS jml FROM forumcomment WHERE id_topik IN ($placeholders) GROUP BY id_topik", $topikIds)->getResult();
        foreach ($rowsCount as $rc) {
            $commentCounts[(int)$rc->id_topik] = (int)$rc->jml;
        }
    }
}
?>

<style>
    #tableTopik thead th { 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em;
        background-color: #f8fafc !important; 
        color: #6c757d; 
        font-weight: 700; 
        border-top: none;
        padding: 12px 15px;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: inset 0 -2px 0 #e2e8f0;
        vertical-align: middle;
    }
    .topik-judul-cell { min-width: 350px; white-space: normal !important; }
    .table-hover tbody tr:hover { background-color: rgba(21, 70, 121, 0.03); transition: background-color 0.2s ease; }
    .topik-row td { padding: 0.85rem 1rem; border-bottom: 1px solid #f1f3f5; }
    .btn-theme { 
        background-color: #154679; 
        border: none; 
        color: white; 
        font-weight: 600;
        border-radius: 10px;
    }
    .btn-theme:hover {
        background-color: #0d2e53;
        color: white;
        box-shadow: 0 4px 12px rgba(21, 70, 121, 0.2);
    }
    .search-container .input-group-text { border-radius: 10px 0 0 10px; }
    .search-container .form-control { border-radius: 0 10px 10px 0; }

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
</style>

<div class="header-container mb-3 flex-shrink-0">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="<?php echo base_url(); ?>index.php/admin/forum_diskusi" class="btn btn-sm btn-light border-0 me-3 shadow-none bg-light-subtle d-flex align-items-center justify-content-center" style="border-radius: 10px; width: 40px; height: 40px;"><i class="bi bi-arrow-left fs-5"></i></a>
            <div>
                <h4 class="fw-bold text-dark mb-1" style="letter-spacing: -0.04em;">Forum Diskusi</h4>
                <p class="text-muted small mb-0"><i class="bi bi-building me-1"></i> <?php echo esc($nama_opd); ?></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group search-container shadow-sm p-0" style="width: 280px; border-radius: 12px; overflow: hidden; background: #fff; border: 1px solid #eee;">
                <span class="input-group-text bg-white border-0 text-muted px-3"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-0 ps-0 py-2 fw-medium" id="searchTopik" placeholder="Cari topik diskusi..." autocomplete="off" style="font-size: 0.85rem;">
            </div>
            <a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/add_topik?id=<?php echo esc($id_unitkerja, 'url');?>" class="btn btn-theme px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 12px; height: 42px; font-weight: 700;">
                <i class="bi bi-plus-lg me-2"></i> Tambah Topik
            </a>
        </div>
    </div>
</div>

<hr class="text-secondary opacity-10 mb-3 flex-shrink-0">

<div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column mb-0" style="min-height: 0; border-radius: 20px; overflow: hidden; border: 1px solid rgba(0,0,0,0.03) !important;">
    <div class="card-body p-0 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: hidden;">
        <div class="table-responsive table-scroll-container flex-grow-1" style="height: 100%;">
            <table class="table table-hover align-middle mb-0" id="tableTopik">
                <thead>
                    <tr class="bg-light-subtle">
                        <th width="8%" class="text-center py-3">#</th>
                        <th width="72%" class="py-3">TOPIK DISKUSI</th>
                        <th width="20%" class="text-center py-3">KOMENTAR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='3' class='text-center py-5 text-muted'><i class='bi bi-chat-dots me-2 display-6 d-block mb-3 opacity-25'></i>Belum ada topik diskusi di OPD ini.</td></tr>";
                    } else {
                        $no = 1;
                        foreach ($data as $b) {
                            $jmlhcomment = $commentCounts[(int)$b->id_topik] ?? 0;
                    ?>
                    <tr class="topik-row">
                        <td class="text-center text-muted fw-bold topik-no" style="font-size: 0.85rem;"><?php echo $no;?></td>
                        <td>
                            <div class="d-flex align-items-center py-1">
                                <div class="icon-box me-3 bg-primary-subtle text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                                    <i class="bi bi-chat-left-text-fill" style="font-size: 1.1rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.4;"><?php echo esc($b->nama_topik);?></h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">Diskusi internal di instansi terkait</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_comment?id_unitkerja=<?php echo urlencode((string)$id_unitkerja).'&id_topik='.(int)$b->id_topik; ?>" 
                               class="btn btn-sm btn-light border-0 px-3 py-2 text-decoration-none shadow-none bg-light-subtle rounded-pill" 
                               style="font-size: 0.75rem; font-weight: 600;">
                                <i class="bi bi-chat-dots me-1 text-primary"></i> <span class="text-dark"><?php echo $jmlhcomment;?> Komentar</span>
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
    $('#searchTopik').on('input', function() {
        var keyword = $(this).val().toLowerCase().trim();
        var $rows = $('.topik-row');
        var num = 1;
        
        $rows.each(function() {
            var topikName = $(this).find('td:nth-child(2)').text().toLowerCase();
            if (topikName.indexOf(keyword) > -1) {
                $(this).show();
                $(this).find('.topik-no').text(num++);
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
