<?php

	$id			= $datpil->id;

	$judul_ind		= $datpil->judul_ind;

	$judul_en		= $datpil->judul_en;

	$id_unitkerja	= $datpil->id_unitkerja;

	$unitkerja_ind  = $datpil->unitkerja_ind;

?>

<div class="modal-dialog">

    <div class="modal-content">



    	<div class="modal-header">

            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>

            <h4 class="modal-title" id="myModalLabel">Data yang telah diperiksa</h4>

        </div>



        <div class="modal-body">

        	<form action="<?php echo site_url('admin/dda/act_periksa'); ?>" name="modal_popup" enctype="multipart/form-data" method="POST">

        		

                <div class="form-group" style="padding-bottom: 20px;">

				<table width="100%" class="table-form">

                	<!--<label for="Kegiatan Name">Uraian</label><br>-->

                    <tr><td><input type="hidden" name="id"  class="form-control" value="<?php echo $id; ?>" /></td></tr>

					<tr><td>Asal Data : </td><td><input type="text" name="unitkerja"  class="form-control" value="<?php echo $unitkerja_ind ; ?>"/><td></tr>

     				<tr><td>Judul Tabel : </td><td><input type="text" name="judul_tabel"  class="form-control" value="<?php echo $judul_ind ; ?>"/></td></tr>

					<tr><td>Catatan : </td><td><input type="text" name="catatan_periksa"  class="form-control" /></td></tr>

				</table>

                </div>



               

	            <div class="modal-footer">

	                <button class="btn btn-success" type="submit">

	                    OK

	                </button>

	                <button type="reset" class="btn btn-danger"  data-dismiss="modal" aria-hidden="true">

	               		Cancel

	                </button>

	            </div>

            	</form>

            </div>

        </div>

    </div>
