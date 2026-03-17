<?php
$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act		= "act_edt";
	$idp			= $datpil->id_unitkerja;
	$id_unitkerja	= $datpil->id_unitkerja;
	$unitkerja_ind	= $datpil->unitkerja_ind;
	$unitkerja_en	= $datpil->unitkerja_en;
	$user_wali		= $datpil->user_wali;
	$user_spv		= $datpil->user_spv;
	
	$querynama_userwali =\Config\Database::connect()->query("SELECT nama from t_admin where username='$user_wali' LIMIT 1")->getRow();
	$nama_userwali =$querynama_userwali->nama;
	//$unitkerja_terpilih =$query_unitkerja->unitkerja_ind;
	
	
} else {
	$act		= "act_add";
	$idp			= "";
	$id_unitkerja	= "";
	$unitkerja_ind	= "";
	$unitkerja_en	= "";
	$user_wali		= "";
	$user_spv		= "";
}
?>
<div class="navbar navbar-inverse">
	<div class="container" style="z-index: 0">
		<div class="navbar-header">
			<span class="navbar-brand" href="#">Master OPD</span>
		</div>
	</div><!-- /.container -->
</div><!-- /.navbar -->
	
	<form action="<?php echo base_URL(); ?>index.php/admin/master_opd/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
	
	<input type="hidden" name="idp" value="<?php echo $idp; ?>">
	<div class="row-fluid well" style="overflow: hidden">
	
	<div class="col-lg-8">
		<table width="100%" class="table-form">
		<tr><td width="30%">Kode OPD</td><td><b><input type="text" name="id_unitkerja" required value="<?php echo $id_unitkerja; ?>" style="width: 300px" class="form-control" tabindex="1" autofocus></b></td></tr>
		<tr><td width="30%">Unit Kerja (Bahasa Indonesia)</td><td><b><input type="unitkerja_ind" name="unitkerja_ind" required value="<?php echo $unitkerja_ind; ?>" id="unitkerja_ind" style="width: 300px" class="form-control" tabindex="2" ></b></td></tr>		
		<tr><td width="30%">Unit Kerja (English)</td><td><b><input type="unitkerja_en" name="unitkerja_en" required value="<?php echo $unitkerja_en; ?>" id="unitkerja_en" style="width: 300px" class="form-control" tabindex="3	" ></b></td></tr>
		<tr>
			<td width="30%">Liassion Officer</td>
			<td>
			<?php
			if ($mode == "edt" || $mode == "act_edt") 
				{
				?>
					<select name="user_wali" id="user_wali"  class="form-control" tabindex="5" style="width: 300px">
                    <option selected value="<?php echo $user_wali; ?>"><?php echo $nama_userwali; ?></option>
                        <?php
						//mengambil nama-nama satuan yang ada di database
						$db = \Config\Database::connect();
						$user_wali_data = $db->query("select username,nama from t_admin where id_unitkerja='bps'")->getResultArray();
						
                        foreach($user_wali_data as $p){
							if ($p['username'] != $user_wali) 
							{
							echo "<option value='".$p['username']."'>".$p['nama']."</option>";
							}
						}
                        ?>
                    </select>  
				<?php
				}
				else
				{
				?>
					<select name="user_wali" id="user_wali"  class="form-control" tabindex="6" style="width: 300px">
                    <option selected value="Kosong">- Pilih Liassion Officer-</option>
                        <?php
						$db = \Config\Database::connect();
						$user_wali_data = $db->query("select username,nama from t_admin where id_unitkerja='bps'")->getResultArray();
						
                        foreach($user_wali_data as $p){
							echo "<option value='".$p['username']."'>".$p['nama']."</option>\n";
                        }
		
				}
				?>
			</td>
			</tr>
			
			<tr><td width="30%">Pengawas</td><td><b>
			<select name="user_spv" class="form-control" style="width: 200px" required tabindex="6" ><option value=""> - Pengawas - </option>
			<?php
				$id_user_spv	= array('puguh.raharjo','herpas','medha');
				$nama_user_spv	= array('Puguh Raharjo','Hermawan Prasetyo','Medha Wardhany');
				
				for ($i = 0; $i < sizeof($id_user_spv); $i++) {
					if ($id_user_spv[$i] == $user_spv) {
						echo "<option selected value='".$id_user_spv[$i]."'>".$nama_user_spv[$i]."</option>";
					} else {
						echo "<option value='".$id_user_spv[$i]."'>".$nama_user_spv[$i]."</option>";
					}				
				}			
			?>			
			</select>
			</b></td></tr>
			
		<tr><td colspan="2">
		<br><button type="submit" class="btn btn-primary" tabindex="7" ><i class="icon icon-ok icon-white"></i> Simpan</button>
		<a href="<?php echo base_URL(); ?>index.php/admin/master_opd" class="btn btn-success" tabindex="8" ><i class="icon icon-arrow-left icon-white"></i> Kembali</a>
		</td></tr>
		</table>
	</div>

	
	</div>
	
	</form>
