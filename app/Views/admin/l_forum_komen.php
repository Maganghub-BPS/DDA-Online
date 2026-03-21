<?php

	$id_topik					= service('request')->getGet('id_topik');

	$id_unitkerja 				= service('request')->getGet('id_unitkerja');

	$query_topik=\Config\Database::connect()->query("SELECT * from m_forumtopic where id_topik = '$id_topik' LIMIT 1")->getRow();

	$nama_topik =$query_topik->nama_topik;

	$unitkerjalogin			=session()->get('admin_unitkerja');

	$hariini				=tgl_jam_sql(date('Y-m-d'));

?>





<div class="clearfix">

<div class="row">

  <div class="col-lg-12">

	<div class="navbar navbar-inverse">

	

		<div class="navbar-header">

			<span class="navbar-brand" href="#" style="align:center">Topik : <?php echo $nama_topik; ?></span>

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

						<th width="15%">Komentar</th>

						<th width="15%">Pengguna</th>

					</tr>

				</thead>

				<tbody>

					<?php 

					if (empty($data)) {

						echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Belum Ada Komentar--</td></tr>";

					} else {

						$no 	= (isset($offset) ? $offset : 0) + 1;

						foreach ($data as $b) {

					?>

					<tr>

						<td  align="center"><?php echo $no;?></td>

						<td align="left"><?php echo $b->comment;?></th>

						<td align="center"><?php echo $b->user_id;?></td>

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

	

  

  <div class="form-group">

  <form action="<?php echo base_URL()?>index.php/admin/forum_diskusi/add_comment" method="post" accept-charset="utf-8" enctype="multipart/form-data">

        <label class="col-lg-3 control-label">Tambah Comment</label>

        <div class="col-lg-5">

            <input type="hidden" name="id_topik" value="<?php echo $id_topik;?>">

			 <input type="hidden"  name="id_unitkerja" value="<?php echo $id_unitkerja;?>">

            <textarea name="comment" class="form-control"></textarea>

			<br>

       </div>

		

		<!-- 

            <button id="simpan" class="btn btn-primary"><i class="glyphicon glyphicon-saved"></i> Simpan</button>

            <a href="<?php //echo base_URL()?>index.php/admin/forum_diskusi/<?php //echo $unitkerjalogin; ?>/" class="btn btn-default">Kembali</a>

			-->

		<div class="col-lg-5">

			<br><a href="<?php echo base_URL()?>index.php/admin/forum_diskusi/<?php echo $id_unitkerja; ?>/" tabindex="11" class="btn btn-primary"><i class="icon icon-arrow-left icon-white"></i> Batal</a>

			<button type="submit" class="btn btn-success" tabindex="10" ><i class="icon icon-folder-close icon-white"></i> Simpan</button>

        </div>

	</form>

    </div>

  

	</div><!-- /.container -->

	</div>

  </div>



