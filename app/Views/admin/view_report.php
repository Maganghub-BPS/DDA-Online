<?php

if (service('request')->getPost('jenis_rekap') != null)

{

	$jenis_rekap = service('request')->getPost('jenis_rekap');

	

	// REPORT BERDASARKAN INSTANSI
	if($jenis_rekap == '0')
	{
        $ta = session()->get('admin_ta');
        if(empty($ta)) $ta = date('Y');

        $data = \Config\Database::connect()->query("
            SELECT 
                m.unitkerja_ind,
                COUNT(t.id) as jumlah_tabel,
                SUM(CASE WHEN t.is_periksa = 0 AND t.is_confirm != 1 THEN 1 ELSE 0 END) as belum_isi,
                SUM(CASE WHEN t.is_periksa = 1 AND t.is_confirm != 1 THEN 1 ELSE 0 END) as menunggu_validasi,
                SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as sudah_validasi
            FROM m_unitkerja m 
            LEFT JOIN t_list_tabel t ON m.id_unitkerja = t.id_unitkerja AND t.tahun = '$ta'
            GROUP BY m.id_unitkerja 
            ORDER BY m.unitkerja_ind ASC
        ")->getResult();
	?>

    <div style="margin-bottom: 10px;">
        <a href="<?php echo base_url(); ?>index.php/admin/export_excel_instansi" target="_blank" class="btn btn-success btn-sm">
            <i class="icon-download-alt icon-white"></i> Export Excel
        </a>
    </div>

	<table class="table table-bordered table-hover" id="tblInstansi">
        <thead>
            <tr class="active">
                <th width="40%" style="vertical-align: middle;">Nama Instansi</th>
                <th width="10%" style="text-align: center;">Total Tabel</th>
                <th width="10%" style="text-align: center; background-color: #f2dede;">Belum Diisi</th>
                <th width="10%" style="text-align: center; background-color: #fcf8e3;">Sudah Diisi</th>
                <th width="10%" style="text-align: center; background-color: #dff0d8;">Sudah Diperiksa</th>
                <th width="10%" style="text-align: center;">Persentase (%)</th>
            </tr>
        </thead>
        <tbody>
        <?php 
            if (empty($data)) {
                echo "<tr><td colspan='6' style='text-align: center; font-weight: bold'>-- Belum Ada Data --</td></tr>";
            } else {
                foreach ($data as $b) {
                    $persen = 0;
                    if($b->jumlah_tabel > 0){
                        $persen = ($b->sudah_validasi / $b->jumlah_tabel) * 100;
                    }
                    
        ?>
            <tr>
                <td align="left"><?php echo $b->unitkerja_ind; ?></td>
                <td align="center"><b><?php echo $b->jumlah_tabel; ?></b></td>
                
                <td align="center" style="color:red;">
                    <?php echo ($b->belum_isi > 0) ? $b->belum_isi : '-'; ?>
                </td>
                <td align="center" style="color:#8a6d3b;">
                    <?php echo ($b->menunggu_validasi > 0) ? $b->menunggu_validasi : '-'; ?>
                </td>
                <td align="center" style="color:green; font-weight:bold;">
                    <?php echo ($b->sudah_validasi > 0) ? $b->sudah_validasi : '-'; ?>
                </td>
                
                <td align="center">
                    <?php echo number_format($persen, 1); ?> %
                </td>
            </tr>
        <?php 
                }
            }
        ?>
        </tbody>
    </table>

	<?php
	}
	

// REPORT BERDASARKAN TIM 
	if($jenis_rekap == '1')
	{
        $ta = session()->get('admin_ta');
        if(empty($ta)) $ta = date('Y');

        $data = \Config\Database::connect()->query("
            SELECT 
                u.user_wali,
                COUNT(t.id) as jumlah_tabel,
                SUM(CASE WHEN t.is_periksa = 0 AND t.is_confirm != 1 THEN 1 ELSE 0 END) as belum_isi,
                SUM(CASE WHEN t.is_periksa = 1 AND t.is_confirm != 1 THEN 1 ELSE 0 END) as menunggu_validasi,
                SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as sudah_validasi
            FROM m_unitkerja u
            LEFT JOIN t_list_tabel t ON u.id_unitkerja = t.id_unitkerja AND t.tahun = '$ta'
            WHERE u.user_wali IS NOT NULL AND u.user_wali != '' AND u.user_wali != '-'
            GROUP BY u.user_wali 
            ORDER BY u.user_wali ASC
        ")->getResult();
	?>

    <!-- TOMBOL EXPORT EXCEL TIM -->
    <div style="margin-bottom: 10px;">
        <a href="<?php echo base_url(); ?>index.php/admin/export_excel_tim" target="_blank" class="btn btn-success btn-sm">
            <i class="icon-download-alt icon-white"></i> Export Excel
        </a>
    </div>

	<table class="table table-bordered table-hover">
        <thead>
            <tr class="active">
                <th width="30%" style="vertical-align: middle;">Nama Tim (Wali Data)</th>
                <th width="10%" style="text-align: center;">Total Tabel</th>
                <th width="10%" style="text-align: center; background-color: #f2dede;">Belum Diisi</th>
                <th width="10%" style="text-align: center; background-color: #fcf8e3;">Sudah Diisi</th>
                <th width="10%" style="text-align: center; background-color: #dff0d8;">Sudah Diperiksa</th>
                <th width="10%" style="text-align: center;">Persentase (%)</th>
            </tr>
        </thead>
        <tbody>
        <?php 
            if (empty($data)) {
                echo "<tr><td colspan='6' style='text-align: center; font-weight: bold'>-- Belum Ada Data --</td></tr>";
            } else {
                foreach ($data as $b) {
                    $persen = 0;
                    if($b->jumlah_tabel > 0){
                        $persen = ($b->sudah_validasi / $b->jumlah_tabel) * 100;
                    }
        ?>
            <tr>
                <td align="left" style="text-transform: uppercase;"><b><?php echo $b->user_wali; ?></b></td>
                <td align="center"><b><?php echo $b->jumlah_tabel; ?></b></td>
                
                <td align="center" style="color:red;">
                    <?php echo ($b->belum_isi > 0) ? $b->belum_isi : '-'; ?>
                </td>
                <td align="center" style="color:#8a6d3b;">
                    <?php echo ($b->menunggu_validasi > 0) ? $b->menunggu_validasi : '-'; ?>
                </td>
                <td align="center" style="color:green; font-weight:bold;">
                    <?php echo ($b->sudah_validasi > 0) ? $b->sudah_validasi : '-'; ?>
                </td>
                
                <td align="center">
                    <?php echo number_format($persen, 1); ?> %
                </td>
            </tr>
        <?php 
                }
            }
        ?>
        </tbody>
    </table>

	<?php
	}
	
	// REPORT PORTAL 
	if($jenis_rekap == '2')
	{
        $ta = session()->get('admin_ta');
        if(empty($ta)) $ta = date('Y');

        $data = \Config\Database::connect()->query("
            select satker, judul_dda, judul_portal, tahun_data from t_tabel_match m left join t_dataportal p on m.id_api=p.id_portal order by satker")->getResult();
	?>

    <!-- TOMBOL EXPORT EXCEL TIM 
    <div style="margin-bottom: 10px;">
        <a href="<?php echo base_url(); ?>index.php/admin/export_excel_tim" target="_blank" class="btn btn-success btn-sm">
            <i class="icon-download-alt icon-white"></i> Export Excel
        </a>
    </div>-->

	<table class="table table-bordered table-hover">
        <thead>
            <tr class="active">
				<th width="10%" style="vertical-align: middle;">SATKER</th>
                <th width="30%" style="vertical-align: middle;">Judul DDA</th>
                <th width="30%" style="vertical-align: middle;">Total Portal</th>
                <th width="10%" style="vertical-align: middle;">Tahun Data Portal</th>
                <th width="30%" style="vertical-align: middle;">Status</th>
            </tr>
        </thead>
        <tbody>
        <?php 
            if (empty($data)) {
                echo "<tr><td colspan='6' style='text-align: center; font-weight: bold'>-- Belum Ada Data --</td></tr>";
            } else {
                foreach ($data as $b) {
                    $persen = 0;
                    if($b->tahun_data == '2025'){
                        $status='Sudah Diisi';
                    }
					else{
                        $status='Belum Diisi';
                    }
        ?>
            <tr>
				<td align="left" style="text-transform: uppercase;"><?php echo $b->satker ?></td>
                <td align="left" style="text-transform: uppercase;"><?php echo $b->judul_dda ?></td>
                <td align="left" style="text-transform: uppercase;"><?php echo $b->judul_portal ?></td>
				<td align="left" style="text-transform: uppercase;"><?php echo $b->tahun_data ?></td>
				<td align="left" style="text-transform: uppercase;"><?php echo $status ?></td>
            </tr>
        <?php 
                }
            }
        ?>
        </tbody>
    </table>

	<?php
	}


// by pengawas

	/*

	if($jenis_rekap == '2')

	{

	$data	= \Config\Database::connect()->query("SELECT u.user_spv, a.nama,t.id_unitkerja,u.unitkerja_ind,count(judul_ind) as jumlah_tabel,sum(case when t.is_periksa = 1 then 1 else 0 end ) 

	as diperiksa FROM `t_list_tabel` t left join m_unitkerja u on  t.id_unitkerja=u.id_unitkerja  left join t_admin a on u.user_spv=a.username group by user_spv order by u.user_spv")->getResult();

	?>

	<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="10%">Nama Pengawas</th>

			<th width="10%">Jumlah Tabel</th>

			<th width="10%">Sudah Diperiksa</th>

			<th width="10%">Persentase Diperiksa</th>

	</thead>

	

	<tbody>

	<?php 

		if (empty($data)) {

			echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Rekap--</td></tr>";

		} else {

		foreach ($data as $b) {

		?>

		<tr>

		<td  align ="left"><?php echo $b->nama;?></td>

		<td  align ="center"><?php echo $b->jumlah_tabel;?></td>

		<td  align ="center"><?php echo $b->diperiksa; ?></td>

		<td  align ="center"><?php echo $b->diperiksa/$b->jumlah_tabel*100; echo ' %';?></td>

		</tr>

	</tbody>

	<?php 

			}

		}

		?>

	</tbody>

</table>



	<?php

	}*/	

	

	



	

	

	



}

?>

