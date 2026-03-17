<?php

//$data=\Config\Database::connect()->query("Select k.*,DATE_ADD(tgl_spk, INTERVAL 3 DAY) as tglbastdeadline,DATE_ADD(tgl_spk, INTERVAL 4 DAY) as tgladkdeadline,DATE_ADD(tgl_spk, INTERVAL 5 DAY) as tglspmdeadline from t_kontrak as k")->getResult();



?>

<div class="clearfix">

<div class="row">

  <div class="col-lg-12">

	<div class="navbar navbar-inverse">

	

		<div class="navbar-header">

			<span class="navbar-brand" href="#" style="align:center">DAFTAR KELUAR MASUK HARI INI</span>

		</div>



</div><!-- /.navbar -->

  

<?php echo session()->getFlashdata("k");?>  

	  

	<div class="container">

	<div class="row">

    <div class="col-sm-12 blog-main">

	

			

				<ul class="nav navbar-nav navbar-right">

					<form class="navbar-form navbar-left" method="post" action="<?php echo base_URL(); ?>index.php/admin/kontrak/cari">

						<input type="text" class="form-control" name="q" style="width: 200px" placeholder="Kata kunci pencarian ..." required>

						<button type="submit" class="btn btn-danger"><i class="icon-search icon-white"> </i> Cari</button>

					</form>

				</ul>

			<br>

	   		<table class="table table-bordered table-hover">

				<thead>

					<tr>

						<th width="5%">No.</th>

						<th width="20%">Jam Masuk</th>

						<th width="10%">Jam Keluar</th>

						<th width="10%">Keperluan</th>

						<th width="10%">Tanggal</th>

						<th width="45%">Keterangan</th>

		

					</tr>

				</thead>

				<tbody>

					<?php 

					if (empty($data)) {

						echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Data tidak ditemukan--</td></tr>";

					} else {

						$no 	= 1;

						foreach ($data as $b) {

					?>

					<tr>

						<td  align="center"><?php echo $no;?></td>

						<td align="left"><?php echo $b->uraian_kontrak;?></td>

						<td  align="center"><?php echo $b->rekanan; ?></td>

						<td  align="center"><?php echo $b->nilai_bruto;?></td>

						<td  align="center"><?php echo tgl_jam_sql($b->tgl_spk);?></td>

						<?php

						$hariini = date('Y-m-d');

						$tglspk = new DateTime($b->tgl_spk);

						$keteranganbast="";

						$keteranganadk="";

						$keteranganspm="";

						$tglhariini=new DateTime($hariini);

						$tglbast=new DateTime($b->tglbastdeadline);

						$tgladk=new DateTime($b->tgladkdeadline);

						$tglspm=new DateTime($b->tglspmdeadline);

						/*echo $tglspk->format('Y-m-d').'<br>';

						echo $tglbast->format('Y-m-d').'<br>';

						echo $tgladk->format('Y-m-d').'<br>';

						echo $tglspm->format('Y-m-d').'<br>';

						*/

						if(strtotime($b->tglbastdeadline) < strtotime($hariini) && $b->bastp =='0000-00-00')

						{

							$keteranganbast = 'Jadwal BASTP sudah Lewat';

						}

						else if(strtotime($b->tglbastdeadline) > strtotime($hariini) && $b->bastp =='0000-00-00')

						{

							$selisih=$tglhariini->diff($tglbast);

							$strselisih =$selisih->format('%R%a Hari');

							$keteranganbast = 'Jadwal BASTP Kurang '.$strselisih;

						}

								if(strtotime($b->tgladkdeadline) < strtotime($hariini) && $b->tgl_daftar_adk =='0000-00-00')

								{

									$keteranganadk = 'Jadwal Daftar ADK sudah Lewat';

								}

								else if(strtotime($b->tgladkdeadline) > strtotime($hariini) && $b->tgl_daftar_adk =='0000-00-00')

								{

									$selisih=$tglhariini->diff($tgladk);

									$strselisih =$selisih->format('%R%a Hari');

									$keteranganadk = 'Jadwal ADK Kurang '.$strselisih;

								}

						if(strtotime($b->tglspmdeadline) < strtotime($hariini) && $b->tgl_maju_spm =='0000-00-00')

						{

							$keteranganspm = 'Jadwal Daftar SPM sudah Lewat';

						}

						else if(strtotime($b->tgladkdeadline) > strtotime($hariini) && $b->tgl_maju_spm =='0000-00-00')

						{

							$selisih=$tglhariini->diff($tglspm);

							$strselisih =$selisih->format('%R%a Hari');

							$keteranganspm = 'Jadwal SPM Kurang '.$strselisih;

						}

						?>

						<td  align="center"><font color="red"><?php echo $keteranganbast.' - '.$keteranganadk.' - '.$keteranganspm;?><font></td>

					</tr>

					<?php 

						$no++;

						}

					}

					?>

				</tbody>

			</table>



	    </div>



	

	

	

    </div><!-- /.blog-main -->

	</div>

	</div><!-- /.container -->

  

  

	

	</div>

  </div>
