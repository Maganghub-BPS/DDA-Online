<div class="clearfix">
<div class="row">
  <div class="col-lg-12">
	
	<div class="navbar navbar-inverse">
		<div class="container">
			<div class="navbar-header">
				<span class="navbar-brand">Master OPD</span>
			</div>
		<div class="navbar-collapse collapse navbar-inverse-collapse" style="margin-right: -20px">
			<ul class="nav navbar-nav">
				<li><a href="<?php echo base_URL(); ?>index.php/admin/master_opd/add" ><i class="icon-plus-sign icon-white"> </i> Tambah OPD</a></li>
			</ul>
			
			<ul class="nav navbar-nav navbar-right">
				<form class="navbar-form navbar-left" method="post" action="<?php echo base_URL(); ?>index.php/admin/master_opd/cari">
					<input type="text" class="form-control" name="q" style="width: 200px" placeholder="Kata kunci pencarian ..." required>
					<button type="submit" class="btn btn-danger"><i class="icon-search icon-white"> </i> Cari</button>
				</form>
			</ul>
		</div><!-- /.nav-collapse -->
		</div><!-- /.container -->
	</div><!-- /.navbar -->

  </div>
</div>

<?php echo session()->getFlashdata("k");?>

<table class="table table-bordered table-hover">
	<thead>
		<tr>
			<th width="5%">Kode OPD</th>
			<th width="30%">Nama OPD (Bahasa Indonesia)</th>
			<th width="30%">Nama OPD (English)</th>
			<th width="20%">Walidata</th>
			<th width="15%">Pengawas</th>
		</tr>
	</thead>
	
	<tbody>
		<?php 
		if (empty($data)) {
			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Data tidak ditemukan--</td></tr>";
		} else {

			foreach ($data as $b) {
		?>
		<tr>
			<td class="ctr"><?php echo $b->id_unitkerja;?></td>
			<td><?php echo $b->unitkerja_ind;?></td>
			<td><?php echo $b->unitkerja_en;?></td>
			<td><?php echo $b->user_wali;?></td>
			<td><?php echo $b->user_spv;?></td>
			<td class="ctr">
				<div class="btn-group">
					<a href="<?php echo base_URL(); ?>index.php/admin/master_opd/edt/<?php echo $b->id_unitkerja; ?>" class="btn btn-success btn-sm" title="Edit Data"><i class="icon-edit icon-white"> </i> Edt</a>
					<a href="<?php echo base_URL()?>index.php/admin/master_opd/del/<?php echo $b->id_unitkerja;?>" class="btn btn-warning btn-sm" title="Hapus Data" onclick="return confirm('Anda Yakin AKan Menghapus OPD <?php echo $b->id_unitkerja;?> ?')"><i class="icon-trash icon-remove">  </i> Del</a>	
				</div>					
			</td>
		</tr>
		<?php 

			}
		}
		?>
	</tbody>
</table>
<center><ul class="pagination"><?php echo $pagi; ?></ul></center>
</div>
