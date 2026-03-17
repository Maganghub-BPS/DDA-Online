<!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

		<title>.:: Jateng Dalam Angka ::.</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta charset="utf-8">
		<style type="text/css">
			@font-face {
				font-family: 'Cabin';
				font-style: normal;
				font-weight: 400;
				src: local('Cabin Regular'), local('Cabin-Regular'), url('<?php echo base_url(); ?>aset/font/satu.woff') format('woff');
			}

			@font-face {
				font-family: 'Cabin';
				font-style: normal;
				font-weight: 700;
				src: local('Cabin Bold'), local('Cabin-Bold'), url('<?php echo base_url(); ?>aset/font/dua.woff') format('woff');
			}

			@font-face {
				font-family: 'Lobster';
				font-style: normal;
				font-weight: 400;
				src: local('Lobster'), url('<?php echo base_url(); ?>aset/font/tiga.woff') format('woff');
			}
		</style>
		<link rel="stylesheet" href="<?php echo base_url(); ?>aset/css/bootstrap.css" media="screen">
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
      <script src="../bower_components/bootstrap/assets/js/html5shiv.js"></script>
      <script src="../bower_components/bootstrap/assets/js/respond.min.js"></script>
    <![endif]-->
		<link rel="stylesheet" href="<?php echo base_url(); ?>aset/js/jquery/jquery-ui.css" />



		<script src="<?php echo base_url(); ?>aset/js/jquery.min.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/bootstrap.min.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/bootswatch.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/jquery/jquery-ui.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/echarts.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/echarts-all.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/echarts.min.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/highcharts.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/highcharts-3d.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/exporting.js"></script>
		<script src="<?php echo base_url(); ?>aset/js/solid-gauge.js"></script>
		<script type="text/javascript">
			// <![CDATA[
			/*
			$(document).ready(function () {
				$(function () {
					$( "#kode_surat" ).autocomplete({
						source: function(request, response) {
							$.ajax({ 
								url: "<?php echo site_url('index.php/admin/get_klasifikasi'); ?>",
								data: { kode: $("#kode_surat").val()},
								dataType: "json",
								type: "POST",
								success: function(data){
									response(data);
								}    
							});
						},
					});
				});
				
				
				
				$(function () {
					$( "#dari" ).autocomplete({
						source: function(request, response) {
							$.ajax({ 
								url: "<?php echo site_url('index.php/admin/get_instansi_lain'); ?>",
								data: { kode: $("#dari").val()},
								dataType: "json",
								type: "POST",
								success: function(data){
									response(data);
								}    
							});
						},
					});
				});
				
				
				$(function () {
					$( "#dasar" ).autocomplete({
						source: function(request, response) {
							$.ajax({ 
								url: "<?php echo site_url('index.php/admin/get_dasar_surat_masuk'); ?>",
								data: { kode: $("#dasar").val()},
								dataType: "json",
								type: "POST",
								success: function(data){
									response(data);
								}    
							});
						},
					});
				});
				
				$(function () {
					$( "#kpd_yth" ).autocomplete({
						source: function(request, response) {
							$.ajax({ 
								url: "<?php echo site_url('index.php/admin/get_bidang'); ?>",
								data: { kode: $("#kpd_yth").val()},
								dataType: "json",
								type: "POST",
								success: function(data){
									response(data);
								}    
							});
						},
					});
				});
				
				
				$(function() {
					$( "#tgl_daftar_adk" ).datepicker({
						changeMonth: true,
						changeYear: true,
						dateFormat: 'yy-mm-dd'
					});
				});
				
				$(function() {
					$( "#tgl_maju_spm" ).datepicker({
						changeMonth: true,
						changeYear: true,
						dateFormat: 'yy-mm-dd'
					});
				});
				
				$(function() {
					$( "#tgl_spk" ).datepicker({
						changeMonth: true,
						changeYear: true,
						dateFormat: 'yy-mm-dd'
					});
				});
				
				$(function() {
					$( "#bastp" ).datepicker({
						changeMonth: true,
						changeYear: true,
						dateFormat: 'yy-mm-dd'
					});
				});
			});*/
			// ]]>
		</script>


		<header>
			<div class="navbar navbar-inverse navbar-fixed-top navbar" style="z-index:999">
				<div class="container">
					<div class="navbar-header">
						<span class="navbar-brand"><strong style="font-family: verdana;">PENGELOLAAN DDA</strong></span>
						<button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
					</div>
					<div class="navbar-collapse collapse" id="navbar-main">
						<ul class="nav navbar-nav">
							<?php
							if (session()->get('admin_level') == "kominfo") {
							?>
								<li><a href="<?php echo base_url(); ?>admin/"><i class="icon-home icon-white"> </i> Beranda</a></li>
								<li><a href="<?php echo base_url(); ?>admin/forum_diskusi/"><i class="icon-file icon-white"> </i> Forum Diskusi</a></li>
								<li><a href="<?php echo base_url(); ?>admin/report/"><i class="icon-signal icon-white"> </i> Report</a></li>
							<?php
							} else {
							?>
								<li><a href="<?php echo base_url(); ?>admin/"><i class="icon-home icon-white"> </i> Beranda</a></li>
								<li><a href="<?php echo base_url(); ?>admin/dda"><i class="icon-home icon-white"> </i> Input Data</a></li>
								<li><a href="<?php echo base_url(); ?>admin/forum_diskusi/"><i class="icon-file icon-white"> </i> Forum Diskusi</a></li>
								<?php
								if (session()->get('admin_unitkerja') == "bps") {
								?>
									<li><a href="<?php echo base_url(); ?>admin/matching/"><i class="icon-signal icon-white"> </i> Matching</a></li>
									<li><a href="<?php echo base_url(); ?>admin/report/"><i class="icon-signal icon-white"> </i> Report</a></li>
									<li class="dropdown">
										<a class="dropdown-toggle" data-toggle="dropdown" href="#" id="themes"><i class="icon-wrench icon-white"> </i> Master <span class="caret"></span></a>
										<ul class="dropdown-menu" aria-labelledby="themes">
											<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/master_tabel/"> Master Tabel</a></li>

											<?php

											$level = session()->get('admin_level');
											if (session()->get('admin_user') == "diseminasi" || $level == 'Admin' || $level == 'Super Admin') {
											?>
												<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/pengguna">Instansi Pengguna</a></li>
												<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/master_opd/">Master OPD</a></li>
												<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/manage_admin/">Manajemen Admin</a></li>
												<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/master_tim/">Master Tim</a></li>
											<?php
											}
											?>
										</ul>
									</li>
							<?php
								}
							}
							?>
						</ul>

						<ul class="nav navbar-nav navbar-right">
							<li class="dropdown">
								<a class="dropdown-toggle" data-toggle="dropdown" href="#" id="themes"><i class="icon-user icon-white"></i> <?php echo session()->get('admin_nama'); ?><span class="caret"></span></a>
								<ul class="dropdown-menu" aria-labelledby="themes">
									<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/passwod">Rubah Password</a></li>
									<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/set_tahun/2025/">2025</a></li>
									<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/set_tahun/2026/">2026</a></li>
									<li><a tabindex="-1" href="<?php echo base_url(); ?>admin/logout">Logout</a></li>
								</ul>
							</li>
						</ul>

					</div>
				</div>
			</div>

			<?php
			$db = \Config\Database::connect();
			$q_instansi	= $db->query("SELECT * FROM tr_instansi LIMIT 1")->getRow();
			?>
			<br>
			<div class="container">

				<div class="page-header" id="banner">
					<div class="row">
						<div class="" style="padding: 5px 5px 0 5px;">
							<div class="well well-sm">
								<!--<img src="<?php echo base_url(); ?>upload/<?php echo $q_instansi->logo; ?>" class="thumbnail span3" style="display: inline; float: left; margin-right: 10px; width: 60px; height: 60px">-->
								<h2 style="color: #000; font-size: 24px; font-family: Arial; margin: 0px 0 0px 0; color: #000; text-align: center;"><b><i><?php echo 'Badan Pusat Statistik Provinsi Jawa Tengah'; ?></b></i></h2>
								<!--<h2 style="color: #000; font-size: 26px; font-family: Arial; margin: 5px 0 5px 0; color: #000;"><b><i><?php echo 'Provinsi Jawa Tengah'; ?></b></i></h2>
				-->
							</div>
						</div>
					</div>
				</div>


		</header>
	</head>

<body>
	<div class="container">
		<div class="row">
			<?php echo view('admin/' . $page); ?>
		</div>
	</div>
</body>
<footer>
	<div class="span12 well well-sm" align="center">
		<h5 style="font-weight: bold; text-align: center;">BPSJATENG &copy; BPS Provinsi Jawa Tengah</h5>
		<!--<h6>&copy;  2017. Waktu Eksekusi : {elapsed_time}, Penggunaan Memori : {memory_usage}</h6>
	  </div>
	</footer>
    </div>

  
	</html>