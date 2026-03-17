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
						<th width="40%">Judul</th>
						<th width="20%">Keterangan Pengisian</th>
						<th width="20%">Keterangan Pemeriksaan LO</th>
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
						<!--STATUS PENGISIAN-->
						<td align="left">
							<?php if($b->is_periksa == '1') { ?>
								<!-- SUDAH DIISI -->
								<strong>Sudah Diisi</strong>
								<?php if(!empty($b->catatan_periksa) && $b->catatan_periksa != '-') { ?>
									<br>Catatan: <?php echo $b->catatan_periksa; ?>
								<?php } ?>
								
								<div style="margin-top: 5px;">
									<a href="<?php echo base_url(); ?>index.php/admin/batal_isi/<?php echo $b->id; ?>" 
									class="btn btn-xs btn-danger" onclick="return confirm('Batalkan status?')">
									Batal
									</a>
								</div>

							<?php } else { ?>
								<!-- BELUM DIISI -->
								Belum Diisi
								<div style="margin-top: 5px;">
									<a href="javascript:;" 
									onclick="isi_data(
									'<?php echo json_encode($b->id); ?>', 
									'<?php echo json_encode(addslashes($b->judul_ind)); ?>',
									'<?php echo json_encode(addslashes($b->id_unitkerja)); ?>')"  
   )" 
									class="btn btn-xs btn-warning">
									<i class="icon-pencil icon-white"></i> Update Status
									</a>
								</div>
							<?php } ?>
						</td>

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

    function isi_data(id, judul, asal) {
        $('#id_tabel_modal').val(id);
        $('#judul_tabel_modal').val(judul);
		$('#asal_data_modal').val(asal);
        $('#ModalIsiData').modal('show');
    }
</script>


<!-- MODAL FORM PENGISIAN -->
<div id="ModalIsiData" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Update Status Pengisian</h4>
            </div>
            <form action="<?php echo base_url(); ?>index.php/admin/act_update_isi" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_tabel" id="id_tabel_modal">
					<div class="form-group">
						<label>Asal Data</label>
						<input type="text" class="form-control" id="asal_data_modal" readonly>
					</div>
                    <div class="form-group">
                        <label>Judul Tabel</label>
                        <input type="text" class="form-control" id="judul_tabel_modal" readonly>
                    </div>
                    <div class="form-group">
                        <label>Catatan </label>
                        <textarea name="catatan_periksa" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

