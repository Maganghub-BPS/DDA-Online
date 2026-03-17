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
			<span class="navbar-brand" href="#" style="align:center">TABEL YANG HARUS DIPERIKSA DI JAWA TENGAH DALAM ANGKA </span>
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
						<th width="50%">Judul</th>
						<th width="15%">Keterangan Pengecekan LO</th>
						<!--	<th width="15%">Keterangan Pengecekan Pengawas</th>
					<th width="10%">Metadata</th>-->
					</tr>
				</thead>
				<tbody>
					<?php 
					if (empty($data)) {
						echo "<tr><td colspan='5'  style='text-align: center; font-weight: bold'>--Tabel tidak ditemukan--</td></tr>";
					} else {
						$no 	= 1;
						foreach ($data as $b) {
					?>
					<tr>
						<td  align="center"><?php echo $no;?></td>
						<td><a href="<?php echo $b->link_tabel ; ?>" target="blank"><?php echo $b->judul_ind;?></a></td>

						<td  align="left">
						<?php
						 if ($b->is_confirm == '2')
						 {
						    echo 'Belum Dicek' ;
						 }
						 else
						 {
							echo 'Sudah Dicek : '.$b->catatan ;
						 }
			
						if(session()->get('admin_unitkerja') == 'bps' and $b->is_confirm=='2')
						{
						$id_konfirm=$b->id;
						?>
						<div class="btn-group">
								<a href="#" class="konfirmasi_modal btn btn-info btn-sm"  id="<?php echo $id_konfirm;?>" title="Cek Data"><i class="icon-check icon-white"> </i> Cek Data</a>
							</div>
						<?php
						}
						?>
						</td>





<!--
						<td  align="left">
						<?php
						 if ($b->is_periksa == '2')
						 {
						    echo 'Belum Diperiksa' ;
						 }
						 else
						 {
							echo 'Sudah Diperiksa : '.$b->catatan_periksa ;
						 }
			
						if(session()->get('admin_unitkerja') == 'bps' and $b->is_periksa=='0')
						{
						$id_konfirm=$b->id;
						?>
						<div class="btn-group">
								<a href="#" class="konfirmasi_modal btn btn-info btn-sm"  id="<?php echo $id_konfirm;?>" title="Konfirmasi Data Pengawas"><i class="icon-check icon-white"> </i> Konfirmasi Data</a>
							</div>
						<?php
						}
						?>
						</td>-->
					<!--	<td align="center"><a href="<?php echo base_URL()?>index.php/admin/dda/kondef?id=<?php echo $b->id ;?>"><i class="icon-search icon-black"> </i></a></td>-->
					</tr>
					<?php 
						
						
						$no++;
					}
					}
					?>
				</tbody>
			</table>
	    </div>
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
    			   url: "<?php echo base_url(); ?>index.php/admin/dda/periksa/",
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


