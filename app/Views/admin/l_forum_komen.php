<?php
$id_topik = service('request')->getGet('id_topik');
$id_unitkerja = service('request')->getGet('id_unitkerja');

$db = \Config\Database::connect();
$query_topik = $db->query("SELECT * from m_forumtopic where id_topik = '$id_topik' LIMIT 1")->getRow();
$nama_topik = $query_topik ? $query_topik->nama_topik : 'Komentar Forum';

$unitkerjalogin = session()->get('admin_unitkerja');
$hariini = tgl_jam_sql(date('Y-m-d'));
?>

<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/view_topik?id=<?php echo $id_unitkerja; ?>" class="btn btn-sm btn-light border me-3 shadow-sm"><i class="bi bi-arrow-left"></i></a>
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-chat-dots me-2"></i>Komentar: <span class="text-dark small fw-normal fst-italic"><?php echo $nama_topik; ?></span>
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo session()->getFlashdata("k");?>  

<div class="row g-4">
    <div class="col-lg-8 col-md-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-list-stars me-2 text-primary"></i>Daftar Komentar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="10%" class="text-center small fw-bold">No.</th>
                                <th width="70%" class="small fw-bold">Komentar</th>
                                <th width="20%" class="text-center small fw-bold">Pengguna</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (empty($data)) {
                                echo "<tr><td colspan='3' class='text-center py-5 text-muted'><i class='bi bi-chat-square-dots me-2 d-block mb-3 display-6'></i>Belum ada komentar</td></tr>";
                            } else {
                                $no = (isset($offset) ? $offset : 0) + 1;
                                foreach ($data as $b) {
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $no;?></td>
                                <td>
                                    <div class="text-dark py-2 px-1 rounded-3 bg-light-subtle shadow-sm"><?php echo nl2br(htmlspecialchars($b->comment));?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary px-3 py-2"><i class="bi bi-person me-1"></i><?php echo $b->user_id;?></span>
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
    </div>

    <div class="col-lg-4 col-md-12">
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Komentar</h6>
            </div>
            <div class="card-body">
                <form action="<?php echo base_URL()?>index.php/admin/forum_diskusi/add_comment" method="post">
                    <input type="hidden" name="id_topik" value="<?php echo $id_topik;?>">
                    <input type="hidden" name="id_unitkerja" value="<?php echo $id_unitkerja;?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Tanggapan/Komentar Anda</label>
                        <textarea name="comment" class="form-control" rows="5" required placeholder="Tuliskan komentar di sini..."></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success shadow-sm"><i class="bi bi-send me-1"></i> Simpan Komentar</button>
                        <a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_topik?id=<?php echo $id_unitkerja; ?>" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
