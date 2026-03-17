<?php
	$today=getdate();
	$tahunsekarang=$today['year'];

	$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act					= "act_edt";
	$judul_en				= "EDIT MASTER TABEL";
	$idp					= $datpil->id;
	$judul_ind				= $datpil->judul_ind;
	$judul_en				= $datpil->judul_en;
	$link_tabel				= $datpil->link_tabel;
	$link_sebelumnya		= $datpil->link_sebelumnya;
	$id_unitkerja			= $datpil->id_unitkerja;
	
	$query_unitkerja =\Config\Database::connect()->query("SELECT * from m_unitkerja where id_unitkerja='$id_unitkerja' LIMIT 1")->getRow();
	$idunitkerja_terpilih =$query_unitkerja->id_unitkerja;
	$unitkerja_terpilih =$query_unitkerja->unitkerja_ind;
	
} else {
	$act		= "act_add";
	$judul_en	= " TAMBAH MASTER TABEL";
	$idp		= "";
	$judul_ind				= "";
	$judul_en				= "";
	$link_tabel				= "";
	$link_sebelumnya		= "";
	$id_unitkerja			= "";
}
?>
<div class="navbar navbar-inverse">
	<div class="container z0">
		<div class="navbar-header">
			<span class="navbar-brand" href="#">MASTER TABEL</span>
		</div>
	</div><!-- /.container -->
</div><!-- /.navbar -->

	
	<form action="<?php echo base_URL(); ?>index.php/admin/master_tabel/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
	
	<input type="hidden" name="idp" value="<?php echo $idp; ?>">
	
	
	<div class="row-fluid well" style="overflow: hidden">
		
	<div class="col-lg-8">
		<table  class="table-form">
		<tr><td width="20%">Judul Bahasa Indonesia</td><td><b><textarea name="judul_ind" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $judul_ind; ?></textarea></b></td></tr>
		<tr><td width="20%">Judul Bahasa Inggris</td><td><b><textarea name="judul_en" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $judul_en; ?></textarea></b></td></tr>		
		<tr><td width="20%">Link Tabel</td><td><b><textarea name="link_tabel" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $link_tabel; ?></textarea></b></td></tr>	
		<tr><td width="20%">Link Tahun Sebelumnya</td><td><b><textarea name="link_sebelumnya" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $link_sebelumnya; ?></textarea></b></td></tr>	
		<tr>
			<td width="30%">Penanggung Jawab</td>
			<td>
			<?php
			if ($mode == "edt" || $mode == "act_edt") 
				{
				?>
					<select name="id_unitkerja" id="id_unitkerja"  class="form-control" tabindex="5" style="width: 300px">
                    <option selected value="<?php echo $idunitkerja_terpilih; ?>"><?php echo $unitkerja_terpilih; ?></option>
                        <?php
						//mengambil nama-nama satuan yang ada di database
						$db = \Config\Database::connect();
						$unitkerja = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();
						
                        foreach($unitkerja as $p){
							if ($p['id_unitkerja'] != $idunitkerja_terpilih) 
							{
							echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>";
							}
						}
                        ?>
                    </select>  
				<?php
				}
				else
				{
				?>
					<select name="id_unitkerja" id="id_unitkerja"  class="form-control" tabindex="6" style="width: 300px">
                    <option selected value="Kosong">- Pilih Instansi -</option>
                        <?php
						//mengambil nama-nama unitkerja yang ada di database
						$db = \Config\Database::connect();
						$unitkerja = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();
						
                        foreach($unitkerja as $p){
							echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>\n";
                        }
		
				}
				?>
			</td>
			</tr>
		
		
		<tr><td colspan="2">
		<br><button type="submit" class="btn btn-primary"tabindex="10" ><i class="icon icon-ok icon-white"></i> Simpan</button>
		<a href="<?php echo base_URL(); ?>index.php/admin/master_tabel" class="btn btn-success" tabindex="11" ><i class="icon icon-arrow-left icon-white"></i> Kembali</a>
		</td></tr>
		</table>
	</div>

	</div>
	
	</form>
