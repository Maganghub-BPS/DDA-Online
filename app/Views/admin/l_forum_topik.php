<?php

	$id_unitkerja = service('request')->getGet('id');

	//if(null!==segment_safe(3))

	$unitkerjalogin			=session()->get('admin_unitkerja');

	$hariini=tgl_jam_sql(date('Y-m-d'));

	

?>





<div class="clearfix">

<div class="row">

  <div class="col-lg-12">

	<div class="navbar navbar-inverse">

	

		<div class="navbar-header">

			<span class="navbar-brand" href="#" style="text-align:center">SELAMAT DATANG DI FORUM DISKUSI</span>

			<!--<ul class="nav navbar-nav navbar-right" style="margin-right: -20px">

					<form class="navbar-form navbar-left" method="post" action="<?php echo base_URL(); ?>index.php/admin/kontrak/cari">

						<input type="text" class="form-control" name="q" style="width: 200px" placeholder="Kata kunci pencarian ..." required>

						<button type="submit" class="btn btn-danger"><i class="icon-search icon-white"> </i> Cari</button>

					</form>

			</ul>-->

		</div>

</div><!-- /.navbar -->

  

<?php echo session()->getFlashdata("k");?>  

	  

	<div class="container">

	<div class="row">

    <div class="col-sm-12 blog-main">

			<br>

	   		<table class="table table-bordered table-hover">

				<thead>

					<tr>

						<th width="5%">No.</th>

						<th width="15%">Topik</th>

						<th width="15%">Komentar</th>

					</tr>

				</thead>

				<tbody>

					<?php 

					if (empty($data)) {

						echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Tidak Ada Data--</td></tr>";

					} else {

						$no 	= (isset($offset) ? $offset : 0) + 1;

						foreach ($data as $b) {

					?>

					<tr>

						<td  align="center"><?php echo $no;?></td>

						<td align="left"><?php echo $b->nama_topik;?></th>

						<?php 

						$jmlhcomment=\Config\Database::connect()->query("select * from forumcomment comment where id_topik=".$b->id_topik)->getNumRows();

						?>

						<td align="center"><a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/view_comment?id_unitkerja=<?php echo $id_unitkerja.'&id_topik='.$b->id_topik; ?>"><?php echo $jmlhcomment;?></a></td>

					</tr>

					<?php 

						$no++;

						}

					}

					?>

				</tbody>

			</table>



	    </div>



	

	<ul class="nav navbar-nav navbar-right">

					<a href="<?php echo base_URL(); ?>index.php/admin/forum_diskusi/add_topik?id=<?php echo $id_unitkerja;?>" class="btn btn-danger"><i class="icon-plus-sign icon-white"> </i> Tambah Topik</a>

	</ul>

	<br><br>

    </div><!-- /.blog-main -->

	</div>

	</div><!-- /.container -->

  

  

	

	</div>

  </div>



