<html>
<head>
<style type="text/css" media="print">
	table {border: solid 1px #000; border-collapse: collapse; width: 100%}
	tr { border: solid 1px #000; page-break-inside: avoid;}
	td { padding: 7px 5px; font-size: 10px}
	.highlight td { border: 1px solid white; }
	th {
		font-family:Arial;
		color:black;
		font-size: 11px;
		background-color:lightgrey;
	}
	thead {
		display:table-header-group;
	}
	tbody {
		display:table-row-group;
	}
	h3 { margin-bottom: -17px }
	h2 { margin-bottom: 0px }
</style>
<style type="text/css" media="screen">
	table {border: solid 1px #000; border-collapse: collapse; width: 100%}
	tr { border: solid 1px #000}
	th {
		font-family:Arial;
		color:black;
		font-size: 11px;
		background-color: #999;
		padding: 8px 0;
	}
	td { padding: 7px 5px;font-size: 10px}
	h3 { margin-bottom: -17px }
	h2 { margin-bottom: 0px }
</style>
<title>Cetak Rekap Keluar Masuk Kantor</title>
</head>

<body onload="window.print()">
	<center><b style="font-size: 20px">REKAP KELUAR MASUK KANTOR</b><br>
	</center><br>
	
	<table class="table table-bordered table-hover">
	<thead>
		<tr>
						<th width="10%">Tanggal</th>
						<th width="20%">Nama</th>
						<th width="35%">Keperluan Keluar</th>
						<th width="10%">Keluar</th>
						<th width="10%">Kembali</th>
						<th width="15%">Konfirmasi</th>
						
		</tr>
	</thead>
	
	<tbody>
	<?php 
		if (empty($data)) {
			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Tidak Ada Data Keluar Kantor--</td></tr>";
		} else {
		foreach ($data as $b) {
		?>
		<tr>
		<td  align="center"><?php echo tgl_jam_sql($b->tgl); ?></td>
		<td  align="center"><?php echo $b->nama;?></td>
		<td  align="left"><?php echo $b->kepentingan_kel.' : '.$b->kepentingan_uraian;?></a></td>
		<td  align="center"><?php echo $b->jam_keluar; ?></td>
		<td  align="center"><?php echo $b->jam_masuk;?></td>
		<td  align="center"><?php
						 if ($b->status == 'T')
						 {
						    echo $b->status.'   ' ;
						 }
						 else
						 {
							echo $b->status.' : '.$b->confirmed_by ;
						 }
						 ?>
						</td>
		</tr>
	</tbody>
	<?php 
			}
			}
		?>
	</tbody>
</table>
<br><br>
<table cellspacing="0" cellpadding="2" border="0"  class="highlight">
<?php
$id = service('request')->getGet('id');
$unitkerja=substr($id,20,5);

//$unitkerja=session()->get('admin_unitkerja');
$bidang = substr($unitkerja,0,4);
$id_bidang=$bidang."0";
$querykabid=\Config\Database::connect()->query("select * from t_admin where id_unitkerja='$id_bidang' LIMIT 1")->getRow();
$namakabid=$querykabid->nama;
$nipkabid=$querykabid->nip;
?>

	<tr>
		<td width="287">&nbsp;</td>
		<td width="192" align="center">Semarang, <?php echo tgl_jam_sql(date('Y-m-d')); ?></td>
    </tr>

    <tr>
    	<td width="287" align="center">Mengetahui,</td>
      <td width="192" align="center">&nbsp;</td>
    </tr>
   	<tr>
    	<td width="287" align="center">Kabag Tata Usaha</td>
		<td width="192" align="center">Kepala Bagian/Bidang</td>
    </tr>
	<tr>
    	<td width="287">&nbsp;</td>
		<td width="192">&nbsp;</td>
    </tr>
	<tr>
    	<td width="287">&nbsp;</td>
		<td width="192">&nbsp;</td>
    </tr>
    <tr>
		<td width="287" align="center"><u>Atas Parlindungan Lubis<u/></td>
		<td width="192" align="center"><u><?php echo $namakabid ;?></u></td>
	 </tr>
	<tr>
		<td width="287" align="center">NIP. 196412141988021001</td>
		<td width="192" align="center">NIP. <?php echo $nipkabid ;?></td>
	 </tr>	 
</table>


</body>
</html>

