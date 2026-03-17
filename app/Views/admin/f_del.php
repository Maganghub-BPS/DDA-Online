<?php

	$id			= $datpil->id;

	$kepentingan_kel		= $datpil->kepentingan_kel;

	$kepentingan_uraian		= $datpil->kepentingan_uraian;

?>

<div class="modal-dialog">

    <div class="modal-content">



    	<div class="modal-header">

            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>

            <h4 class="modal-title" id="myModalLabel">Data Yang Akan Dihapus</h4>

        </div>



        <div class="modal-body">

        	<form action="<?php echo base_URL()?>index.php/admin/oi/act_del/" name="modal_popup" enctype="multipart/form-data" method="POST">

        		

                <div class="form-group" style="padding-bottom: 20px;">

                	<label for="Kegiatan Name">Uraian</label><br>

                    <input type="hidden" name="id"  class="form-control" value="<?php echo $id; ?>" />

     				<input type="text" name="kepentingan_uraian"  class="form-control" value="<?php echo $kepentingan_kel. ' : '.$kepentingan_uraian ; ?>"/>

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
