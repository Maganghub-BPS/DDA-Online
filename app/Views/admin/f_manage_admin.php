<?php
$mode		= segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
	$act		= "act_edt";
	$idp		= $datpil->id;
	$username	= $datpil->username;
	$password	= "-";
	$nama		= $datpil->nama;
	$nip		= $datpil->nip;
	$level		= $datpil->level;
	$idunitkerja= $datpil->id_unitkerja;
	$email		= $datpil->email;
	
	$query_unitkerja =\Config\Database::connect()->query("SELECT * from m_unitkerja where id_unitkerja='$idunitkerja' LIMIT 1")->getRow();
	if ($query_unitkerja) {
		$idunitkerja_terpilih =$query_unitkerja->id_unitkerja;
		$unitkerja_terpilih =$query_unitkerja->unitkerja_ind;
	} else {
		$idunitkerja_terpilih = $idunitkerja;
		$unitkerja_terpilih = "Instansi Tidak Ditemukan ($idunitkerja)";
	}
	
	
} else {
	$act		= "act_add";
	$idp		= "";
	$username	= "";
	$password	= "";
	$nama		= "";
	$nip		= "";
	$level		= "";
	$id_unitkerja	= "";
	$email		="";
}
?>
<div class="navbar navbar-inverse">
	<div class="container" style="z-index: 0">
		<div class="navbar-header">
			<span class="navbar-brand" href="#">Manage Admin</span>
		</div>
	</div><!-- /.container -->
</div><!-- /.navbar -->
	
	<form action="<?php echo base_URL(); ?>index.php/admin/manage_admin/<?php echo $act; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
	
	<input type="hidden" name="idp" value="<?php echo $idp; ?>">
	
	<div class="row-fluid well" style="overflow: hidden">
	
	<div class="col-lg-6">
		<table width="100%" class="table-form">
		<tr><td width="30%">Username</td><td><b><input type="text" name="username" required value="<?php echo $username; ?>" style="width: 300px" class="form-control" tabindex="1" autofocus></b></td></tr>
		<tr><td width="30%">Password</td><td><b><input type="password" name="password" required value="<?php echo $password; ?>" id="dari" style="width: 300px" class="form-control" tabindex="2" ></b></td></tr>		
		<tr><td width="30%">Ulangi Password</td><td><b><input type="password" name="password2" required value="<?php echo $password; ?>" id="dari" style="width: 300px" class="form-control" tabindex="3	" ></b></td></tr>
		<tr><td width="30%">Email</td><td><b><input type="email" name="email" required value="<?php echo $email; ?>" id="email" style="width: 300px" class="form-control" tabindex="4" ></b></td></tr>
		<tr><td colspan="2">
		<br><button type="submit" class="btn btn-primary" tabindex="7" ><i class="icon icon-ok icon-white"></i> Simpan</button>
		<a href="<?php echo base_URL(); ?>index.php/admin/manage_admin" class="btn btn-success" tabindex="8" ><i class="icon icon-arrow-left icon-white"></i> Kembali</a>
		</td></tr>
		</table>
	</div>
	
	<div class="col-lg-6">	
		<table width="100%" class="table-form">
		<tr><td width="30%">Nama</td><td><b><input type="text" name="nama" required value="<?php echo $nama; ?>" style="width: 300px" class="form-control" tabindex="4" ></b></td></tr>
		<tr><td width="30%">N I P</td><td><b><input type="text" name="nip" required value="<?php echo $nip; ?>" style="width: 300px" class="form-control" tabindex="5" ></b></td></tr>
		<tr><td width="30%">Level</td><td><b>
			<select name="level" class="form-control" style="width: 200px" required tabindex="6" ><option value=""> - Level - </option>
			<?php
				$l_sifat	= array('Super Admin','Admin','spv','lo','walidata');
				
				for ($i = 0; $i < sizeof($l_sifat); $i++) {
					if ($l_sifat[$i] == $level) {
						echo "<option selected value='".$l_sifat[$i]."'>".$l_sifat[$i]."</option>";
					} else {
						echo "<option value='".$l_sifat[$i]."'>".$l_sifat[$i]."</option>";
					}				
				}			
			?>			
			</select>
			</b></td></tr>
			<tr>
			<td width="30%">Asal Instansi</td>
			<td>
			<?php
			if ($mode == "edt" || $mode == "act_edt") 
				{
				?>
					<select name="unitkerja" id="unitkerja"  class="form-control" tabindex="5" style="width: 300px">
                    <option selected value="<?php echo $idunitkerja_terpilih; ?>"><?php echo $unitkerja_terpilih; ?></option>
                        <?php
						//mengambil nama-nama satuan yang ada di database
						$db = \Config\Database::connect();
						$unitkerja_data = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();
						
                        foreach($unitkerja_data as $p){
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
					<select name="unitkerja" id="unitkerja"  class="form-control" tabindex="6" style="width: 300px">
                    <option selected value="Kosong">- Pilih Instansi -</option>
                        <?php
						//mengambil nama-nama unitkerja yang ada di database
						$db = \Config\Database::connect();
						$unitkerja_data = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();
						
                        foreach($unitkerja_data as $p){
							echo "<option value='".$p['id_unitkerja']."'>".$p['unitkerja_ind']."</option>\n";
                        }
		
				}
				?>
			</td>
			</tr>
		</table>
	</div>
	
	</div>
	
	</form>
