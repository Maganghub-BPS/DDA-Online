<?php
	$today=getdate();
	$tahunsekarang=$today['year'];

	$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act		= "act_edt";
	$judul_en				= "KONFIRMASI TABEL USUSLAN";
	$idp					= $datpil->id;
	$judul_ind				= $datpil->judul_ind;
	$judul_en				= $datpil->judul_en;
	$id_unitkerja			= $datpil->id_unitkerja;
	$file_tabel				= $datpil->file_tabel;
} else {
	$act		= "act_add";
	$judul_en	= " TAMBAH MASTER TABEL";
	$idp		= "";
	$judul_ind				= "";
	$judul_en				= "";
	$id_unitkerja			= session()->get('admin_unitkerja');
	$file_tabel				= "";
	
}
?>
<div class="navbar navbar-inverse">
	<div class="container z0">
		<div class="navbar-header">
			<span class="navbar-brand" href="#">USULAN TABEL BARU</span>
		</div>
	</div><!-- /.container -->
</div><!-- /.navbar -->

	
	<form action="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
	
	<input type="hidden" name="idp" value="<?php echo $idp; ?>">
	<input type="hidden" name="id_unitkerja" value="<?php echo $id_unitkerja; ?>">
	
	<div class="row-fluid well" style="overflow: hidden">
		
	<div class="col-lg-8">
		<table  class="table-form">
		<tr><td width="20%">Judul Bahasa Indonesia</td><td><b><textarea name="judul_ind" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $judul_ind; ?></textarea></b></td></tr>
		<tr><td width="20%">Judul Bahasa Inggris</td><td><b><textarea name="judul_en" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $judul_en; ?></textarea></b></td></tr>		
		<tr><td width="30%">File Tabel</td><td><b><input type="file" name="file_tabel" tabindex="23"  class="form-control" style="width: 300px" value="<?php echo $file_tabel;?>"></b><a href="<?php echo base_URL()?>upload/tabel_usulan/<?php echo $file_tabel; ?>" target='_blank'><?php echo $file_tabel;?></a></td></tr>
		<tr><td colspan="2">
		<br><button type="submit" class="btn btn-primary"tabindex="10" ><i class="icon icon-ok icon-white"></i> Setujui</button>
		<a href="javascript:history.back()" class="btn btn-success" tabindex="11" ><i class="icon icon-arrow-left icon-white"></i> Kembali</a>
		</td></tr>
		</table>
	</div>

	</div>
	
	</form>
