<div class="clearfix">

<div class="row">

  <div class="col-lg-12">

	

	<div class="navbar navbar-inverse">

		<div class="container">

			<div class="navbar-header">

				<a class="navbar-brand" href="#">MASTER TABEL DDA</a>

			</div>

		<div class="navbar-collapse collapse navbar-inverse-collapse" style="margin-right: -20px">

		

			<ul class="nav navbar-nav">

				<li><a href="<?php echo base_URL(); ?>index.php/admin/master_tabel/add"><i class="icon-plus-sign icon-white"> </i> Tambah Tabel</a></li>

				<li><a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/"><i class="icon-zoom-in icon-white"> </i> Tabel Usulan</a></li>

			</ul>

			<ul class="nav navbar-nav navbar-right">

				<form class="navbar-form navbar-left" method="post" action="<?php echo base_URL(); ?>index.php/admin/master_tabel/cari">

					<input type="text" class="form-control" name="q" style="width: 200px" placeholder="Kata kunci pencarian ..." required>

					<button type="submit" class="btn btn-danger"><i class="icon-search icon-white"> </i> Cari</button>

				</form>

			</ul>

		</div><!-- /.nav-collapse -->

		</div><!-- /.container -->

	</div><!-- /.navbar -->



  </div>

</div>



<?php echo session()->getFlashdata("k");?>



<table class="table table-bordered table-hover">

	<thead>

		<tr>

			<th width="5%">No.</th>

			<th width="15%">Judul Indonesia</th>

			<th width="15%">Judul Inggris</th>

			<th width="20%">File Tabel</th>

			<th width="20%">Instansi yang Mengusulkan</th>

			<th width="15%">User yang Mengusulkan</th>

			<th width="10%">Action</th>

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

			<td align="center"><?php echo $no;?></td>

			<td><?php echo $b->judul_ind;?></td>

			<td><?php echo $b->judul_en ;?></td>

			<td><a href="<?php echo base_URL()?>upload/tabel_usulan/<?php echo $b->file_tabel; ?>" target='_blank'><?php echo $b->file_tabel;?></a></td>

			<td><?php echo $b->unitkerja_ind;?></td>

			<td><?php echo $b->addby;?></td>

			<td class="ctr">

				<div class="btn-group">

					<a href="<?php echo base_URL()?>index.php/admin/master_tabel_opd/edt/<?php echo $b->id; ?>" class="btn btn-success btn-sm" title="Konfirmasi Tabel"><i class="icon-edit icon-white"> </i> Konfirmasi</a>

					<?php

					$id_delete =$b->id;

					?>

					<a href="#" class="open_modal btn btn-warning btn-sm" id="<?php echo $id_delete ;?>"><i class="icon-trash icon-remove"></i> Hapus</a>		

					</div>	

				</td>

		</tr>

		<?php 

			$no++;

			}

		}

		?>

	</tbody>

</table>

<center><ul class="pagination"><?php echo $pagi; ?></ul></center>

</div>



<!-- Modal Popup untuk Delete--> 

<div id="ModalDelete" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">



</div>

<!-- Javascript untuk popup modal Edit--> 

<script type="text/javascript">

   $(document).ready(function () {

   $(".open_modal").click(function(e) {

      var m = $(this).attr("id");

		   $.ajax({

    			   url: "<?php echo base_url(); ?>index.php/admin/master_tabel_opd/del/",

    			   type: "GET",

    			   data : {delete_id: m,},

    			   success: function (ajaxData){

      			   $("#ModalDelete").html(ajaxData);

      			   $("#ModalDelete").modal('show',{backdrop: 'true'});

      		   }

    		   });

        });

      });

</script>
