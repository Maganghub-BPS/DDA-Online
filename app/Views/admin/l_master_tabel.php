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
				<li><a href="<?php echo base_URL(); ?>index.php/admin/master_tabel/add"><i class="icon-plus-sign icon-white"> </i> Tambah Data</a></li>
				<li><a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/"><i class="icon-zoom-in icon-white"> </i> Tabel Usulan</a></li>
				<li><a href="#" data-toggle="modal" data-target="#ModalBulkPortal" class="text-info"><i class="icon-upload icon-white"> </i> Bulk Portal Update</a></li>
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
<div class="well well-sm" style="margin-top: 10px; margin-bottom: 10px;">
    <form class="form-inline" method="GET" action="<?php echo base_url(); ?>index.php/admin/master_tabel">
        
        <!-- FILTER 1: BIDANG / TIM -->
        <div class="form-group" style="margin-right: 15px;">
            <label style="font-weight: bold; margin-right: 5px;">
                <i class="icon-user"></i> Filter Bidang:
            </label>
            <select name="filter_bidang" class="form-control" onchange="this.form.submit()" style="width: 200px;">
                <option value="all">-- Semua Bidang --</option>
                <?php 
                if(isset($list_tim)) {
                    foreach($list_tim as $tim) { 
                        $sel = (isset($selected_bidang) && $selected_bidang == $tim->user_wali) ? 'selected' : '';
                        // Tampilkan nama tim (huruf besar biar rapi)
                        echo "<option value='".$tim->user_wali."' $sel>".strtoupper($tim->user_wali)."</option>";
                    } 
                }
                ?>
            </select>
        </div>

        <!-- FILTER 2: OPD -->
        <div class="form-group" style="margin-right: 15px;">
            <label style="font-weight: bold; margin-right: 5px;">
                <i class="icon-filter"></i> Filter OPD:
            </label>
            <select name="filter_opd" class="form-control" onchange="this.form.submit()" style="width: 250px;">
                <option value="all">-- Tampilkan Semua OPD --</option>
                <?php 
                if(isset($list_opd)) {
                    foreach($list_opd as $opd) { 
                        $selected = (isset($selected_opd) && $selected_opd == $opd->id_unitkerja) ? 'selected' : '';
                        echo "<option value='".$opd->id_unitkerja."' $selected>".$opd->unitkerja_ind."</option>";
                    } 
                }
                ?>
            </select>
        </div>

        <!-- FILTER 3: TAHUN -->
        <div class="form-group">
            <label style="font-weight: bold; margin-right: 5px;">
                <i class="icon-calendar"></i> Filter Tahun:
            </label>
            <select name="filter_tahun" class="form-control" onchange="this.form.submit()" style="width: 150px;">
                <option value="all">-- Semua Tahun --</option>
                <?php 
                for ($i = 2020; $i <= (date('Y')+1); $i++) {
                    $selected = (isset($selected_tahun) && $selected_tahun == $i) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i</option>";
                }
                ?>
            </select>
        </div>

        <!-- TOMBOL RESET -->
        <?php if((isset($selected_opd) && $selected_opd != 'all') || (isset($selected_bidang) && $selected_bidang != 'all') || (isset($selected_tahun) && $selected_tahun != 'all')) { ?>
           <a href="<?php echo base_url(); ?>index.php/admin/master_tabel?action=reset" class="btn btn-default btn-sm" style="margin-left: 5px;"><i class="icon-refresh"></i> Reset Filter
			</a>
        <?php } ?>

    </form>
</div>

<table class="table table-bordered table-hover">
	<thead>
		<tr>
			<th width="5%">No.</th>
			<th width="5%">Tahun</th>
			<th width="20%">Judul Indonesia</th>
			<th width="20%">Judul Inggris</th>
			<th width="15%">Link Tabel</th>
			<th width="15%">Link Tahun Sebelumnya</th>
			<th width="15%">Penanggung Jawab</th>
			<th width="5%">Action</th>
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
			<td align="center"><?php echo $b->tahun;?></td>
			<td><?php echo $b->judul_ind;?></td>
			<td><?php echo $b->judul_en ;?></td>
			<td>
				<code><?php echo $b->link_tabel;?></code><br>
				<?php if(strpos($b->link_tabel, 'view_portal_tabel') !== false): ?>
					<span class="label label-primary"><i class="icon-globe"></i> PORTAL</span>
				<?php else: ?>
					<span class="label label-success"><i class="icon-file"></i> SHEET</span>
				<?php endif; ?>
			</td>
			<td><?php echo $b->link_sebelumnya;?></td>
			<td><?php echo $b->unitkerja_ind;?></td>
			<td class="ctr">
				<div class="btn-group">
					<a href="<?php echo base_URL()?>index.php/admin/master_tabel/edt/<?php echo $b->id; ?>/1" class="btn btn-success btn-sm" title="Edit Data"><i class="icon-edit icon-white"> </i> Edt</a>
					<?php
					$id_delete =$b->id;
					?>
					<a href="#" class="open_modal btn btn-warning btn-sm" id="<?php echo $id_delete ;?>"><i class="icon-trash icon-remove"></i> Del</a>		
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

<!-- Modal Bulk Update Portal -->
<div id="ModalBulkPortal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="padding: 20px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">Bulk Update Portal Mapping</h4>
            </div>
            <form action="<?php echo base_url(); ?>index.php/admin/preview_bulk_portal" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <p>Gunakan file mapping untuk memperbarui link tabel secara massal.</p>
                    <p><a href="<?php echo base_url(); ?>index.php/admin/download_xlsx_template" class="btn btn-xs btn-default"><i class="icon-download"></i> Download Template CSV (Excel)</a></p>
                    <div class="form-group">
                        <label>Pilih File (Format .csv atau .xlsx yang disimpan sebagai .csv)</label>
                        <input type="file" name="file_mapping" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Mulai Sinkronisasi</button>
                </div>
            </form>
        </div>
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
    			   url: "<?php echo base_url(); ?>index.php/admin/master_tabel/del/",
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
