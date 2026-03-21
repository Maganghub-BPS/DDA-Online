<div class="clearfix">
<div class="row">
  <div class="col-lg-12">
  
    <div class="navbar navbar-inverse" style="background-color: #1abc9c; border-color: #16a085;">
        <div class="container">
            <div class="navbar-header">
                <a class="navbar-brand" href="#" style="color: white; font-weight: bold; text-transform: uppercase;">
                    Master Tim
                </a>
            </div>

            <div class="navbar-collapse collapse navbar-inverse-collapse" style="margin-right: -20px">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="#" data-toggle="modal" data-target="#modalTambahTim" style="color: white; font-weight: bold;">
                            <i class="icon-plus-sign icon-white"></i> Tambah Tim Baru
                        </a>
                    </li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <form class="navbar-form navbar-left" method="post" action="<?php echo base_url(); ?>index.php/admin/master_tim/cari">
                        <div class="form-group">
                            <input type="text" class="form-control" name="q" style="width: 250px" placeholder="Cari Nama Pegawai..." required>
                        </div>
                        <button type="submit" class="btn btn-danger"><i class="icon-search icon-white"></i> Cari</button>
                    </form>
                </ul>
            </div>
        </div>
    </div>
  
  </div>
</div>

<?php echo session()->getFlashdata("k");?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr style="background-color: #f5f5f5; color: #333;">
                <th width="5%" style="text-align: center; font-weight: bold; vertical-align: middle;">No.</th>
                <th width="45%" style="font-weight: bold; vertical-align: middle;">Nama Pegawai</th> 
                <th width="25%" style="font-weight: bold; vertical-align: middle;">NIP</th>
                <th width="25%" style="font-weight: bold; text-align: center; vertical-align: middle;">Tim</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (empty($data)) {
                echo "<tr><td colspan='4' style='text-align: center; font-weight: bold; padding: 20px;'>-- Belum ada Data Tim --</td></tr>";
            } else {
                $no 	= (isset($offset) ? $offset : 0) + 1;
                foreach ($data as $b) {
            ?>
            <tr>
                <td align="center" style="vertical-align: middle;"><?php echo $no;?></td>
                <td style="vertical-align: middle;"><?php echo $b->nama;?></td>
                <td style="vertical-align: middle;"><?php echo $b->nip;?></td>
                <td align="center" style="vertical-align: middle; color: #333;">
                    <?php echo $b->username; ?>
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
<center><ul class="pagination"><?php echo $pagi; ?></ul></center>
</div>

<!-- MODAL TAMBAH TIM -->
<div class="modal fade" id="modalTambahTim" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #fff; border-bottom: 1px solid #e5e5e5;">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel" style="font-weight: bold; color: #333;">Tambah Tim Baru</h4>
      </div>
      <form action="<?php echo base_url(); ?>index.php/admin/master_tim/act_add" method="post">
          <div class="modal-body" style="padding: 20px;">
                <div class="form-group">
                    <label style="font-weight: bold;">Nama Pegawai</label>
                    <input type="text" name="nama" class="form-control" style="width: 100%;" required>
                </div>
                <div class="form-group">
                    <label style="font-weight: bold;">NIP</label>
                    <input type="number" name="nip" class="form-control" style="width: 100%;" required>
                </div>
                <div class="form-group">
                    <label style="font-weight: bold;">Kode Tim (Username)</label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: tim1, tim2" style="width: 100%;" required>
                </div>
                <input type="hidden" name="password" value="123456">
          </div>
          <div class="modal-footer" style="background-color: #fff; border-top: none;">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>
