<?php
$mode		= segment_safe(3);
$id_unitkerja = service('request')->getGet('id');
?>
<div class="navbar navbar-inverse">
	<div class="container z0">
		<div class="navbar-header">
			<span class="navbar-brand" href="#"><?php echo 'Tambah Topik';?></span>
		</div>
	</div><!-- /.container -->
</div><!-- /.navbar -->

<?php echo session()->getFlashdata("k");?>	
<div class="scroll">
	<form action="<?php echo base_URL()?>index.php/admin/forum_diskusi/act_add_topik" method="post" accept-charset="utf-8" enctype="multipart/form-data">
	<input type="hidden" name="id_unitkerja" value="<?php echo $id_unitkerja; ?>">
	<div class="row-fluid well" style="overflow: hidden">
	
	<div class="col-lg-10">
		<table width="100%" class="table-form">
		
		<tr>
			<td width="20%">Topik</td>
			<td><b><textarea name="nama_topik"  id="nama_topik" style="text-transform: uppercase" tabindex="6" required style="width: 400px; height: 90px" class="form-control"></textarea>
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
	


	
