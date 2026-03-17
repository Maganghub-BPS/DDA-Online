<div class="clearfix">
<div class="row">
  <div class="col-lg-12">
	
	<div class="navbar navbar-inverse">
		<div class="container">
			<div class="navbar-header">
				<b><a class="navbar-brand" href="#">Rekap Pendaftaran Calon Petugas Pengolahan SE2016</a></b>
			</div>
		<!--<div class="navbar-collapse collapse navbar-inverse-collapse" style="margin-right: -20px">
			<ul class="nav navbar-nav navbar-right">
				<form class="navbar-form navbar-left" method="post" action="<?php //echo base_URL(); ?>index.php/admin/rekap/cari">
					<input type="text" class="form-control" name="q" style="width: 200px" placeholder="Kata kunci pencarian ..." required>
					<button type="submit" class="btn btn-danger"><i class="icon-search icon-white"> </i> Cari</button>
				</form>
			
			</ul>
		</div><!-- /.nav-collapse -->
		</div><!-- /.container -->
	</div><!-- /.navbar -->

  </div>
</div>

<?php echo session()->getFlashdata("k");
$jenis_rekap = service('request')->getPost('jenis_rekap');
?>
	
<!--	
<div class="alert alert-dismissable alert-success">
  <button type="button" class="close" data-dismiss="alert">x</button>
  <strong>Well done!</strong> You successfully read <a href="http://bootswatch.com/amelia/#" class="alert-link">this important alert message</a>.
</div>
	
<div class="alert alert-dismissable alert-danger">
  <button type="button" class="close" data-dismiss="alert">x</button>
  <strong>Oh snap!</strong> <a href="http://bootswatch.com/amelia/#" class="alert-link">Change a few things up</a> and try submitting again.
</div>	
-->
<form class="navbar-form navbar-left" method="post" action="<?php echo base_URL(); ?>index.php/admin/rekap/">
<select name="jenis_rekap" class="form-control" tabindex="3" style="width: 400px" required><option value=""> - Jenis Rekap - </option>
			<?php
				$l_jenis_rekap	= array('Petugas Berdasarkan Jenis Kelamin','Petugas Berdasarkan Jenis Pendidikan','Petugas Berdasarkan Kelompok Umur','Petugas Berdasarkan Pilihan Jenis Operator','Petugas Berdasarkan Rekomendator','Petugas Berdasarkan Pilihan Shift','Petugas Berdasarkan Nilai');
				
				for ($i = 0; $i < sizeof($l_jenis_rekap); $i++) {
					if ($l_jenis_rekap[$i] == $jenis_rekap) {
						echo "<option selected value='".$i."'>".$l_jenis_rekap[$i]."</option>";
					} else {
						echo "<option value='".$i."'>".$l_jenis_rekap[$i]."</option>";
					}				
				}			
			?>			
</select>

<button type="submit" class="btn btn-primary" tabindex="24" ><i class="icon icon-ok icon-white" name="proses"></i> Submit</button>
		<a href="<?php echo base_URL(); ?>index.php/admin/rekap" class="btn btn-success" tabindex="25" ><i class="icon icon-arrow-left icon-white"></i> Kembali</a>

<br><br><br>
<?php require "view_rekap.php"; ?>		
		
</form>
<br>

</div>
