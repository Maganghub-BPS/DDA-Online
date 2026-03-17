<?php

if (service('request')->getPost('jenis_rekap') != null)

{

	$jenis_rekap = service('request')->getPost('jenis_rekap');

	

//	by jenis kelamin

	

	if($jenis_rekap == '0')

	{

	$data = \Config\Database::connect()->query("select t.pilihan_operator, SUM(CASE WHEN t.jk = 'Laki-laki' THEN 1 ELSE 0 END) AS lakilaki,

			SUM(CASE WHEN  t.jk = 'Perempuan' THEN 1 ELSE 0 END) AS perempuan 

			from t_petugas t group by t.pilihan_operator")->getResult();

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="10%">Jenis Operator</th>

			<th width="10%">Laki-Laki</th>

			<th width="10%">Perempuan</th>

		</tr>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data)) {

			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

		foreach ($data as $b) {

		?>

		<tr>

		<td  align ="center"><?php echo $b->pilihan_operator;?></td>

		<td  align ="center"><?php echo $b->lakilaki;?></td>

		<td  align ="center"><?php echo $b->perempuan; ?></td>

		</tr>

	</tbody>

	<?php 

			}

		}

		?>

	</tbody>

</table>



	<?php

	}

// by jenis pendidikan

	

	if($jenis_rekap == '1')

	{

	$data	= \Config\Database::connect()->query("select t.pilihan_operator, SUM(CASE WHEN t.pendidikan_terakhir = 'SMP/Sederajat' THEN 1 ELSE 0 END) AS smp,

			SUM(CASE WHEN  t.pendidikan_terakhir = 'SMA/Sederajat' THEN 1 ELSE 0 END) AS sma,

			SUM(CASE WHEN  t.pendidikan_terakhir = 'DIII/Sederajat' THEN 1 ELSE 0 END) AS d3,

			SUM(CASE WHEN  t.pendidikan_terakhir = 'DIV/S1' THEN 1 ELSE 0 END) AS s1,	

			SUM(CASE WHEN  t.pendidikan_terakhir = 'S2' THEN 1 ELSE 0 END) AS s2

			from t_petugas t group by t.pilihan_operator")->getResult();

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="10%">Jenis Operator</th>

			<th width="10%">SMP/Sederajat</th>

			<th width="10%">SMA/Sederajat</th>

			<th width="10%">DIII/Sederajat</th>

			<th width="10%">DIV/S1</th>

			<th width="10%">S2</th>

		</tr>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data)) {

			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

		foreach ($data as $b) {

		?>

		<tr>

		<td  align ="center"><?php echo $b->pilihan_operator;?></td>

		<td  align ="center"><?php echo $b->smp;?></td>

		<td  align ="center"><?php echo $b->sma; ?></td>

		<td  align ="center"><?php echo $b->d3; ?></td>

		<td  align ="center"><?php echo $b->s1;?></td>

		<td  align ="center"><?php echo $b->s2; ?></td>

		</tr>

	</tbody>

	<?php 

			}

		}

		?>

	</tbody>

</table>



	<?php

	}

	

// by kelompok umur

	

	if($jenis_rekap == '2')

	{

	

	

	$data	= \Config\Database::connect()->query("select t.pilihan_operator, SUM(CASE WHEN  YEAR(CURDATE())-YEAR(tanggal_lahir) <= 20 THEN 1 ELSE 0 END) AS kurang20,

			SUM(CASE WHEN  (YEAR(CURDATE())-YEAR(tanggal_lahir) >= 21 and YEAR(CURDATE())-YEAR(tanggal_lahir) <= 30 )THEN 1 ELSE 0 END) AS u2030,

			SUM(CASE WHEN  (YEAR(CURDATE())-YEAR(tanggal_lahir) >= 31 and YEAR(CURDATE())-YEAR(tanggal_lahir) <= 40 ) THEN 1 ELSE 0 END) AS u3040,	

			SUM(CASE WHEN  (YEAR(CURDATE())-YEAR(tanggal_lahir) >= 41 and YEAR(CURDATE())-YEAR(tanggal_lahir) <= 50 ) THEN 1 ELSE 0 END) AS u4050,

			SUM(CASE WHEN  (YEAR(CURDATE())-YEAR(tanggal_lahir) >= 51) THEN 1 ELSE 0 END) AS lebih50

			from t_petugas t group by t.pilihan_operator")->getResult();

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="10%">Jenis Operator</th>

			<th width="10%">20 tahun ke bawah</th>

			<th width="10%">21 - 30</th>

			<th width="10%">31 - 40</th>

			<th width="10%">41 - 50</th>

			<th width="10%">51 tahun ke atas</th>

		</tr>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data)) {

			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

		foreach ($data as $b) {

		?>

		<tr>

		<td  align ="center"><?php echo $b->pilihan_operator;?></td>

		<td  align ="center"><?php echo $b->kurang20;?></td>

		<td  align ="center"><?php echo $b->u2030; ?></td>

		<td  align ="center"><?php echo $b->u3040;?></td>

		<td  align ="center"><?php echo $b->u4050; ?></td>

		<td  align ="center"><?php echo $b->lebih50; ?></td>

		</tr>

	</tbody>

	<?php 

			}

		}

		?>

	</tbody>

</table>



	<?php

	}	

	

	

// by jenis operator

	

	if($jenis_rekap == '3')

	{

	

	$data	= \Config\Database::connect()->query("SELECT pilihan_operator, COUNT(*) as jmlh FROM t_petugas GROUP BY pilihan_operator")->getResult();

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="10%">Jenis Operator</th>

			<th width="10%">Jumlah</th>

			

		</tr>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data)) {

			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

		foreach ($data as $b) {

		?>

		<tr>

		<td><?php echo $b->pilihan_operator;?></td>

		<td  align ="center"><?php echo $b->jmlh;?></td>

		</tr>

	</tbody>

	<?php 

			}

		}

		?>

	</tbody>

</table>



	<?php

	}	

	

// by nama rekomendator

	

	if($jenis_rekap == '4')

	{

	

	$data	= \Config\Database::connect()->query("SELECT p.nama_pegawai, COUNT(t.id) as jmlh FROM t_petugas t left join m_pegawai p on t.nip_rekomendasi=p.nip GROUP BY t.nip_rekomendasi order by p.nama_pegawai")->getResult();

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="10%">Nama Pegawai</th>

			<th width="10%">Jumlah Yang Direkomendasi</th>

			

		</tr>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data)) {

			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

		foreach ($data as $b) {

		?>

		<tr>

		<td><?php echo $b->nama_pegawai;?></td>

		<td align ="center"><?php echo $b->jmlh;?></td>

		</tr>

	</tbody>

	<?php 

			}

		}

		?>

	</tbody>

</table>



	<?php

	}	

	

	

// by shift

	

	if($jenis_rekap == '5')

	{

	$data1	= \Config\Database::connect()->query("select SUM(CASE WHEN t.pilihan_shift_1 = 'Shift 1' THEN 1 ELSE 0 END) AS pilihan1,

			 SUM(CASE WHEN t.pilihan_shift_2 = 'Shift 1' THEN 1 ELSE 0 END) AS pilihan2,

			 SUM(CASE WHEN t.pilihan_shift_3 = 'Shift 1' THEN 1 ELSE 0 END) AS pilihan3

			from t_petugas t")->getResult();

	

	$data2	= \Config\Database::connect()->query("select SUM(CASE WHEN t.pilihan_shift_1 = 'Shift 2' THEN 1 ELSE 0 END) AS pilihan1,

			 SUM(CASE WHEN t.pilihan_shift_2 = 'Shift 2' THEN 1 ELSE 0 END) AS pilihan2,

			 SUM(CASE WHEN t.pilihan_shift_3 = 'Shift 2' THEN 1 ELSE 0 END) AS pilihan3

			from t_petugas t")->getResult();

			

	$data3	= \Config\Database::connect()->query("select SUM(CASE WHEN t.pilihan_shift_1 = 'Shift 3' THEN 1 ELSE 0 END) AS pilihan1,

			 SUM(CASE WHEN t.pilihan_shift_2 = 'Shift 3' THEN 1 ELSE 0 END) AS pilihan2,

			 SUM(CASE WHEN t.pilihan_shift_3 = 'Shift 3' THEN 1 ELSE 0 END) AS pilihan3

			from t_petugas t")->getResult();



	$data4	= \Config\Database::connect()->query("select SUM(CASE WHEN t.pilihan_shift_1 = 'Tidak Memilih' THEN 1 ELSE 0 END) AS pilihan1,

			 SUM(CASE WHEN t.pilihan_shift_2 = 'Tidak Memilih' THEN 1 ELSE 0 END) AS pilihan2,

			 SUM(CASE WHEN t.pilihan_shift_3 = 'Tidak Memilih' THEN 1 ELSE 0 END) AS pilihan3

			from t_petugas t")->getResult();		

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="10%">Shift</th>

			<th width="10%">Pilihan Pertama</th>

			<th width="10%">Pilihan Kedua</th>

			<th width="10%">Pilihan Ketiga</th>

		</tr>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data1)) {

			echo "<tr><td colspan='5' style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

		foreach ($data1 as $b) {

		?>

		<tr>

		<td><?php echo "Shift 1";?></td>

		<td align ="center"><?php echo $b->pilihan1;?></td>

		<td align ="center"><?php echo $b->pilihan2;?></td>

		<td align ="center"><?php echo $b->pilihan3;?></td>

		</tr>

	</tbody>

	<?php 

			}

		foreach ($data2 as $b) {

		?>

		<tr>

		<td><?php echo "Shift 2";?></td>

		<td align ="center"><?php echo $b->pilihan1;?></td>

		<td align ="center"><?php echo $b->pilihan2;?></td>

		<td align ="center"><?php echo $b->pilihan3;?></td>

		</tr>

	</tbody>

	<?php 

			}

		

		foreach ($data3 as $b) {

		?>

		<tr>

		<td><?php echo "Shift 3";?></td>

		<td align ="center"><?php echo $b->pilihan1;?></td>

		<td align ="center"><?php echo $b->pilihan2;?></td>

		<td align ="center"><?php echo $b->pilihan3;?></td>

		</tr>

	</tbody>

	<?php 

			}



	foreach ($data4 as $b) {

		?>

		<tr>

		<td><?php echo "Tidak Memilih";?></td>

		<td align ="center"><?php echo $b->pilihan1;?></td>

		<td align ="center"><?php echo $b->pilihan2;?></td>

		<td align ="center"><?php echo $b->pilihan3;?></td>

		</tr>

	</tbody>

	<?php 

			}

			

		

		}

		?>

	</tbody>

</table>



	<?php

	}	



// by nilai



if($jenis_rekap == '6')

	{

	$data	= \Config\Database::connect()->query("select id,nama,sum(CASE WHEN is_pernah = '1.Pernah' THEN 9 ELSE 0 END) as pernah,sum(CASE WHEN nip_rekomendasi <> '5' THEN 1 ELSE 0 END) as rekom, (sum(CASE WHEN is_pernah = '1.Pernah' THEN 9 ELSE 0 END)+sum(CASE WHEN nip_rekomendasi <> '1' THEN 5 ELSE 0 END)) as total from t_petugas group by id order by total desc, id asc")->getResult();

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="5%">No.</th>

			<th width="10%">ID Petugas</th>

			<th width="10%">Nama Petugas</th>

			<th width="20%" style="display:none">Nilai Pernah</th>

			<th width="20%"  style="display:none">Nilai Rekomendasi</th>

			<th width="20%">Jumlah Nilai</th>

		</tr>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data)) {

			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

			$no=1;

		foreach ($data as $b) {

		?>

		<tr>

		<td align ="center"><?php echo $no;?></td>

		<td><?php echo $b->id;?></td>

		<td><?php echo $b->nama;?></td>

		<td align ="center"  style="display:none"><?php echo $b->pernah;?></td>

		<td align ="center"  style="display:none"><?php echo $b->rekom;?></td>

		<td align ="center"><?php echo $b->total;?></td>

		</tr>

	</tbody>

	<?php 

		$no++;

			}

		}

		?>

	</tbody>

</table>



	<?php

	}	

	

		

	

	

	



}

?>

