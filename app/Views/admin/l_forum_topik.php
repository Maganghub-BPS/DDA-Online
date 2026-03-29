<?php
$id_unitkerja = service('request')->getGet('id');
$unitkerjalogin = session()->get('admin_unitkerja');
$hariini = tgl_jam_sql(date('Y-m-d'));

$db = \Config\Database::connect();
$query_unitkerja = $db->query("SELECT * from m_unitkerja where id_unitkerja='$id_unitkerja' LIMIT 1")->getRow();
$nama_opd = $query_unitkerja ? $query_unitkerja->unitkerja_ind : 'Forum OPD';
?>

<style>
    #tableTopik thead th { 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em;
        background-color: #f8f9fa; 
        color: #6c757d; 
        font-weight: 700; 
        border-top: none;
        padding: 12px 15px;
    }
    .topik-judul-cell { min-width: 350px; white-space: normal !important; }
    .table-hover tbody tr:hover { background-color: rgba(255, 109, 31, 0.03); transition: background-color 0.2s ease; }
    .topik-row td { padding: 0.85rem 1rem; border-bottom: 1px solid #f1f3f5; }
    .btn-theme { 
        background: linear-gradient(45deg, #FF6D1F, #ff8c42); 
        border: none; 
        color: white; 
        font-weight: 600;
        border-radius: 10px;
    }
    .btn-theme:hover {
        background: linear-gradient(45deg, #e65c19, #ff7b2b);
        color: white;
        box-shadow: 0 4px 12px rgba(255, 109, 31, 0.2);
    }
    .search-container .input-group-text { border-radius: 10px 0 0 10px; }
    .search-container .form-control { border-radius: 0 10px 10px 0; }
</style>

<div class="header-container mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="<?php echo base_url(); ?>index.php/admin/forum_diskusi" class="btn btn-sm btn-light border-0 me-3 shadow-none bg-light-subtle d-flex align-items-center justify-content-center" style="border-radius: 10px; width: 40px; height: 40px;"><i class="bi bi-arrow-left fs-5"></i></a>
            <div>
                <h4 class="fw-bold text-dark mb-1" style="letter-spacing: -0.04em;">Forum Diskusi</h4>
                <p class="text-muted small mb-0"><i class="bi bi-building me-1"></i> <?php echo $nama_opd; ?></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group search-container shadow-sm p-0" style="width: 280px; border-radius: 12px; overflow: hidden; background: #fff; border: 1px solid #eee;">
                <span class="input-group-text bg-white border-0 text-muted px-3"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-0 ps-0 py-2 fw-medium" id="searchTopik" placeholder="Cari topik diskusi..." autocomplete="off" style="font-size: 0.85rem;">
            </div>
            <a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/add_topik?id=<?php echo $id_unitkerja;?>" class="btn btn-theme px-4 py-2 shadow-sm d-flex align-items-center" style="border-radius: 12px; height: 42px; font-weight: 700;">
                <i class="bi bi-plus-lg me-2"></i> Tambah Topik
            </a>
        </div>
    </div>
</div>

<hr class="text-secondary opacity-10 mb-4">

<div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden; border: 1px solid rgba(0,0,0,0.03) !important;">
    <div class="card-body p-0">
        <div class="table-responsive">
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
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                            $jmlhcomment = $db->query("select * from forumcomment comment where id_topik=".$b->id_topik)->getNumRows();
                    ?>
                    <tr class="topik-row">
                        <td class="text-center text-muted fw-bold topik-no" style="font-size: 0.85rem;"><?php echo $no;?></td>
                        <td>
                            <div class="d-flex align-items-center py-1">
                                <div class="icon-box me-3 bg-primary-subtle text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                                    <i class="bi bi-chat-left-text-fill" style="font-size: 1.1rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.4;"><?php echo $b->nama_topik;?></h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">Diskusi internal di instansi terkait</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_comment?id_unitkerja=<?php echo $id_unitkerja.'&id_topik='.$b->id_topik; ?>" 
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
