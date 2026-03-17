<?php

	if(segment_safe(3) == null )

	{

		$tab=1;

	}

	else 

	{

		$tab = segment_safe(3);

	}

		

?>





<div class="clearfix">

<div class="row">

  <div class="col-lg-12">

	<div class="navbar navbar-inverse">

	

		<div class="navbar-header">

			<span class="navbar-brand" href="#" style="align:center"><b>List OPD</b></span>

			<!--<ul class="nav navbar-nav navbar-right" style="margin-right: -20px">

					<form class="navbar-form navbar-left" method="post" action="<?php echo base_URL(); ?>index.php/admin/kontrak/cari">

						<input type="text" class="form-control" name="q" style="width: 200px" placeholder="Kata kunci pencarian ..." required>

						<button type="submit" class="btn btn-danger"><i class="icon-search icon-white"> </i> Cari</button>

					</form>

			</ul>-->

		</div>



</div><!-- /.navbar -->

  

<?php 

$unitkerjalogin			=session()->get('admin_unitkerja');

echo session()->getFlashdata("k");

?>  

	  

	<div class="container">

	<div class="row">

    <div class="col-sm-12 blog-main">

	

	   

			<table class="table table-bordered table-hover">

				<thead>

					<tr>

						<th width="5%">No.</th>

						<th width="65%">Nama OPD</th>

						<?php

						if(session()->get('admin_level') != 'lo')

						{

						?>

						<th width="65%">Nama Penanggung Jawab</th>

						<?php

						}

						?>

					</tr>

				</thead>

				<tbody>

					<?php 

					if (empty($data)) {

						echo "<tr><td colspan='5' style='text-align: center; font-weight: bold'>--Data tidak ditemukan--</td></tr>";

					} else {

						$no 	= 1;

						foreach ($data as $b) {

					?>

					<tr>

						<td  align="center"><?php echo $no;?></td>

						<?php

						if(session()->get('admin_level') == 'lo')

						{

						?>

						<td><a href="<?php echo base_URL()?>index.php/admin/dda/view_tabel?id=<?php echo $b->id_unitkerja ;?>"><?php echo $b->unitkerja_ind;?></a></td>

						

						<?php

						}

						else

						{?>

						<td><a href="<?php echo base_URL()?>index.php/admin/dda/periksa_tabel?id=<?php echo $b->id_unitkerja ;?>"><?php echo $b->unitkerja_ind;?></a></td>	

						<td  align="center"><?php echo $b->user_wali;?></td>	

						<?php

						}

						?>

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

	<center><ul class="pagination"><?php echo $pagi; ?></ul></center>	

	</div><!-- /.container -->

  </div>

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

    			   url: "<?php echo base_url(); ?>index.php/admin/dda/del/",

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



<!-- Modal Popup untuk Kembali--> 

<div id="ModalEdit" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">



</div>

<!-- Javascript untuk popup modal Kembali--> 

<script type="text/javascript">

   $(document).ready(function () {

   $(".view_modal").click(function(e) {

      var m = $(this).attr("id");

		   $.ajax({

    			   url: "<?php echo base_url(); ?>index.php/admin/dda/in/",

    			   type: "GET",

    			   data : {in_id: m,},

    			   success: function (ajaxData){

      			   $("#ModalEdit").html(ajaxData);

      			   $("#ModalEdit").modal('show',{backdrop: 'true'});

      		   }

    		   });

        });

      });

</script>	





<!-- Modal Popup untuk KOnfirmasi--> 

<div id="ModalKonfirmasi" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">



</div>

<!-- Javascript untuk popup modal Kembali--> 

<script type="text/javascript">

   $(document).ready(function () {

   $(".konfirmasi_modal").click(function(e) {

      var m = $(this).attr("id");

		   $.ajax({

    			   url: "<?php echo base_url(); ?>index.php/admin/dda/konfirmasi/",

    			   type: "GET",

    			   data : {konfirmasi_id: m,},

    			   success: function (ajaxData){

      			   $("#ModalKonfirmasi").html(ajaxData);

      			   $("#ModalKonfirmasi").modal('show',{backdrop: 'true'});

      		   }

    		   });

        });

      });

</script>
