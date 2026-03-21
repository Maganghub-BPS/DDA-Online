<?php
	if(segment_safe(3) == null )
	{
		$tab=1;
	}
	else 
	//if(null!==segment_safe(3))
	{
		$tab = segment_safe(3);
	}
	
?>


<div class="clearfix">
<div class="row">
  <div class="col-lg-12">
	<div class="navbar navbar-inverse">
	
		<div class="navbar-header">
			<span class="navbar-brand" href="#" style="text-align:center">TABEL ANDA YANG DIMUAT DI JAWA TENGAH DALAM ANGKA </span>
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
						<?php
						if(session()->get('admin_eselon') != '0')
						{
						?>
						<th width="15%">Nama</th>
						<?php
						}
						?>
						<th width="15%">Keperluan Keluar</th>
						<th width="10%">Keluar</th>
						<th width="10%">Kembali</th>
						<th width="10%">Konfirmasi</th>
						<th width="15%"></th>
						
					</tr>
				</thead>
				<tbody>
					<?php 
					if (empty($data)) {
						echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Data tidak ditemukan--</td></tr>";
					} else {
						$no 	= (isset($offset) ? $offset : 0) + 1;
						foreach ($data as $b) {
					?>
					<tr>
						<td  align="center"><?php echo $no;?></td>
						<?php
						if(session()->get('admin_eselon') != '0')
						{
						?>
						<td align="left"><?php echo $b->nama;?></th>
						<?php
						}
						?>
						<td><a href="https://docs.google.com/spreadsheets/d/1xTacZ81bViCQfWBOcO9vLuUzal4MpquX9nITMyF6dmA/edit#gid=1686441889" target="blank"><?php echo $b->kepentingan_kel.' : '.$b->kepentingan_uraian;?></a></td>
						<td  align="center"><?php echo $b->jam_keluar; ?></td>
						<td  align="center"><?php echo $b->jam_masuk;?>
						<div class="btn-group">
						<?php
								$id_in =$b->id ;
								if($b->jam_masuk=='')
								{
								?>
								<a href="#" class="view_modal btn btn-info btn-sm" id="<?php echo $id_in ;?>"><i class="icon-download icon-white"></i>OK</a>		
								<?php
								}
								?>
						</div>
						</td>
						<td  align="center"><?php
						 if ($b->status == 'T')
						 {
						    echo $b->status.'   ' ;
						 }
						 else
						 {
							echo $b->status.' : '.$b->confirmed_by ;
						 }
			
						if(session()->get('admin_eselon') != '0' and $b->status=='T')
						{
						$id_konfirm=$b->id;
						?>
						<div class="btn-group">
								<a href="#" class="konfirmasi_modal btn btn-info btn-sm"  id="<?php echo $id_konfirm;?>" title="Konfirmasi Data"><i class="icon-check icon-white"> </i> Konfirmasi</a>
							</div>
						</td>
						<?php
						}
						?>
						<td class="ctr">
							<div class="btn-group">
							
								<a href="<?php echo base_URL()?>index.php/admin/oi/edt/<?php echo $b->id; ?>" class="btn btn-success btn-sm" title="Edit Data"><i class="icon-edit icon-white"> </i> Ubah</a>
								<?php
								$id_delete =$b->id ;
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

	    </div>

	
	<ul class="nav navbar-nav navbar-right">
					<a href="<?php echo base_URL(); ?>index.php/admin/oi/add/1" class="btn btn-danger"><i class="icon-plus-sign icon-white"> </i> Tambah</a>
				
				</ul>
	<br><br>
    </div><!-- /.blog-main -->
	</div>
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
    			   url: "<?php echo base_url(); ?>index.php/admin/oi/del/",
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
    			   url: "<?php echo base_url(); ?>index.php/admin/oi/in/",
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
    			   url: "<?php echo base_url(); ?>index.php/admin/oi/konfirmasi/",
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


