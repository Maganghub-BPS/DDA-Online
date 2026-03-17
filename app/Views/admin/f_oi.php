<?php
$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act		= "act_edt";
	$judul		= "Edit Data Out In";
	$idp		= $datpil->id;
	$nip			= $datpil->nip;
	$tgl				= $datpil->tgl;
	$jam_keluar			= $datpil->jam_keluar;
	$jam_masuk				= $datpil->jam_masuk;
	$status					= $datpil->status;
	$kepentingan_kel		= $datpil->kepentingan_kel;
	$kepentingan_uraian		= $datpil->kepentingan_uraian;
	
} else {
	$act					= "act_add";
	$idp					= "";
	$judul					= "Entri Data Keluar Kantor";
	$nip					= "";
	$tgl					= "";
	$jam_keluar				= "";
	$jam_masuk				= "";
	$status					= "";
	$kepentingan_kel		= "";
	$kepentingan_uraian		= "";
}

?>
<div class="navbar navbar-inverse">
	<div class="container z0">
		<div class="navbar-header">
			<span class="navbar-brand" href="#"><?php echo $judul;?></span>
		</div>
	</div><!-- /.container -->
</div><!-- /.navbar -->

<?php echo session()->getFlashdata("k");?>	
<div class="scroll">
	<form action="<?php echo base_URL()?>index.php/admin/oi/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
	
	<input type="hidden" name="idp" value="<?php echo $idp; ?>">
	
	<div class="row-fluid well" style="overflow: hidden">
	
	<div class="col-lg-10">
		<table width="100%" class="table-form">
		
		<tr>
		<td width="20%">Keperluan</td>
			<td>
				 <div class="radio" required >
						<label><input type="radio" required name="kepentingan_kel" id="kepentingan_kel" value="Sensus/Survei" <?php echo ($kepentingan_kel=='Sensus/Survei')?'checked':'' ?>>Sensus/Survei</label>
						</div>
						<div class="radio">
						  <label><input type="radio" required name="kepentingan_kel" id="kepentingan_kel" value="Rapat di Luar Kantor" <?php echo ($kepentingan_kel=='Rapat di Luar Kantor')?'checked':'' ?>>Rapat di Luar Kantor</label>
						</div>
						<div class="radio">
						  <label><input type="radio" required name="kepentingan_kel" id="kepentingan_kel" value="Besuk/Layat (Teman Kantor)" <?php echo ($kepentingan_kel=='Besuk/Layat (Teman Kantor)')?'checked':'' ?>>Besuk/Layat (Teman Kantor)</label>
						</div> 
						<div class="radio">
						  <label><input type="radio" required name="kepentingan_kel" id="kepentingan_kel" value="Lainnya" <?php echo ($kepentingan_kel=='Lainnya')?'checked':'' ?>>Lainnya</label>
						</div> 
			</td>
		</tr>
		
		<tr>
			<td width="20%">Uraian</td>
			<td><b><textarea name="kepentingan_uraian"  id="kepentingan_uraian" style="text-transform: uppercase" tabindex="6" required style="width: 400px; height: 90px" class="form-control"><?php echo $kepentingan_uraian; ?></textarea>
			</td>
		</tr>

		<tr><td colspan="2">
		<br><a href="javascript:history.back()" tabindex="11" class="btn btn-primary"><i class="icon icon-arrow-left icon-white"></i> Batal</a>
		<button type="submit" class="btn btn-success" tabindex="10" ><i class="icon icon-folder-close icon-white"></i> Simpan</button>
		
		</td></tr>
		</table>
	</div>

	</div>
	
	</form>
	</div>
	


	
