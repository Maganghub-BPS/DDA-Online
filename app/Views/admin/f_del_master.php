<?php

	$id						= $datpil->id;

	$judul_ind		= $datpil->judul_ind;

	$judul_en		= $datpil->judul_en;

?>

<div class="modal-dialog">

    <div class="modal-content">



    	<div class="modal-header">

            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>

            <h4 class="modal-title" id="myModalLabel">Tabel Yang Akan Dihapus</h4>

        </div>



        <div class="modal-body">

        	<form action="<?php echo base_URL()?>index.php/admin/master_tabel/act_del/" name="modal_popup" enctype="multipart/form-data" method="POST">

        		

                <div class="form-group" style="padding-bottom: 20px;">

                	<label for="Kegiatan Name">Judul Tabel</label><br>

                    <input type="hidden" name="id"  class="form-control" value="<?php echo $id; ?>" />

     				<textarea name="judul_en" tabindex="4" required style="width: 400px; height: 90px" class="form-control"><?php echo $judul_en; ?></textarea>

                </div>



               

	            <div class="modal-footer">

	                <button class="btn btn-success" type="submit">

	                    Delete

	                </button>

	                <button type="reset" class="btn btn-danger"  data-dismiss="modal" aria-hidden="true">

	               		Cancel

	                </button>

	            </div>

            	</form>

            </div>

        </div>

    </div>
