<?php
	$today=getdate();
	$tahunsekarang=$today['year'];

	$mode		= segment_safe(3);

	$idp					= $datpil->id;
	$judul_ind				= $datpil->judul_ind;
	$kondef					= $datpil->kondef;
	

?>
<div class="navbar navbar-inverse">
	<div class="container z0">
		<div class="navbar-header">
			<span class="navbar-brand" href="#">Konsep dan Definisi : <?php echo $judul_ind;?></span>
		</div>
	</div><!-- /.container -->
</div><!-- /.navbar -->

	<form action="<?php echo base_URL()?>index.php/admin/dda/kondef_update" name="modal_popup" enctype="multipart/form-data" method="POST">
	
	<input type="hidden" name="idp" value="<?php echo $idp; ?>">
	
	
	<div class="row-fluid well" style="overflow: hidden">
		
	<div class="col-lg-8">
		<table  class="table-form">
		<tr><td width="20%">Konsep Dan Definisi</td><td> :</td><td><textarea name="kondef" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $kondef; ?></textarea></td></tr>
		<tr><td colspan="2">
		<br><button type="submit" class="btn btn-primary"tabindex="10" ><i class="icon icon-ok icon-white"></i> Simpan</button>
		<a href="<?php echo base_URL(); ?>index.php/admin/dda" class="btn btn-success" tabindex="11" ><i class="icon icon-arrow-left icon-white"></i> Kembali</a>
		</td></tr>
		
		</table>
	</div>

	</div>
	
	</form>
