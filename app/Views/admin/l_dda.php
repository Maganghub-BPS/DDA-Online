<?php
	if(segment_safe(3) == null) {
		$tab = 1;
	} else {
		$tab = segment_safe(3);
	}
	$username = session()->get('admin_user');
?>

<style>
.search-tabel-container {
	padding: 12px 15px;
	background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f0 100%);
	border-radius: 8px;
	margin-bottom: 10px;
	border: 1px solid #d5dbe3;
}
.search-tabel-container .input-group {
	max-width: 550px;
}
.search-tabel-container input.form-control {
	border-radius: 20px 0 0 20px;
	border: 2px solid #27ae60;
	padding: 8px 15px;
	font-size: 14px;
	box-shadow: 0 2px 5px rgba(0,0,0,0.08);
	transition: border-color 0.3s, box-shadow 0.3s;
}
.search-tabel-container input.form-control:focus {
	border-color: #1e8449;
	box-shadow: 0 2px 10px rgba(39,174,96,0.3);
	outline: none;
}
.search-tabel-container .input-group-btn .btn {
	border-radius: 0 20px 20px 0;
	border: 2px solid #27ae60;
	border-left: none;
	background: #27ae60;
	color: #fff;
	padding: 8px 15px;
	transition: background 0.3s;
}
.search-tabel-container .input-group-btn .btn:hover {
	background: #1e8449;
}
.search-tabel-info {
	margin-top: 8px;
	font-size: 13px;
	color: #7f8c8d;
	display: none;
}
.search-tabel-info.active {
	display: block;
}
.no-result-tabel {
	display: none;
}
.no-result-tabel.active {
	display: table-row;
}
.no-result-tabel td {
	text-align: center;
	font-weight: bold;
	color: #e74c3c;
	padding: 20px !important;
}
</style>

<div class="clearfix">
<div class="row">
  <div class="col-lg-12">
	<div class="navbar navbar-inverse">
	
		<div class="navbar-header">
			<span class="navbar-brand" href="#" style="text-align:center">TABEL YANG DIMUAT DI JAWA TENGAH DALAM ANGKA </span>
			<?php
			if(session()->get('admin_unitkerja') != 'bps' || session()->get('admin_unitkerja') != '')
			{?>
			<ul class="nav navbar-nav">
				<li><a href="<?php echo base_URL(); ?>index.php/admin/master_tabel_opd/add" ><i class="icon-plus-sign icon-white"> </i> Tambah Tabel</a></li>
			</ul>
			<?php
			}
			?>
		</div>

</div><!-- /.navbar -->
  
<?php echo session()->getFlashdata("k");?>  

	<!-- Search Box Judul Tabel & Filter Tahun -->
	<div class="search-tabel-container">
		<form method="GET" action="<?php 
			$current_url = current_url(true);
			echo $current_url;
		?>">
		<?php 
			// Preserve URL parameters like 'id' from GET
			foreach($_GET as $key => $val) {
				if($key != 'filter_tahun' && $key != 'action') {
					echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'">';
				}
			}
		?>
		<div class="row">
			<div class="col-sm-6 col-md-5">
				<div class="input-group">
					<input type="text" class="form-control" id="searchTabel" placeholder="&#128269; Cari judul tabel..." autocomplete="off">
					<span class="input-group-btn">
						<button class="btn btn-success" type="button" id="btnClearSearchTabel" title="Hapus pencarian">
							<i class="icon-remove icon-white"></i> Reset
						</button>
					</span>
				</div>
				<div class="search-tabel-info" id="searchTabelInfo">
					Menampilkan <strong id="searchTabelCount">0</strong> dari <strong id="totalTabelCount">0</strong> tabel
				</div>
			</div>

			<div class="col-sm-4 col-md-3">
				<div class="form-group">
					<select name="filter_tahun" class="form-control" onchange="this.form.submit()">
						<option value="all" <?php echo (isset($selected_tahun) && $selected_tahun == 'all') ? 'selected' : ''; ?>>-- Semua Tahun --</option>
						<?php 
						for ($i = 2020; $i <= (date('Y')+1); $i++) {
							$sel = (isset($selected_tahun) && $selected_tahun == $i) ? 'selected' : '';
							echo "<option value='$i' $sel>$i</option>";
						}
						?>
					</select>
				</div>
			</div>

			<?php if(isset($selected_tahun) && $selected_tahun != 'all') { ?>
			<div class="col-sm-2">
				<a href="<?php 
					// Build reset URL with current parameters except action
					$reset_params = $_GET;
					unset($reset_params['filter_tahun']);
					$reset_params['action'] = 'reset_dda';
					echo site_url('admin/dda?' . http_build_query($reset_params));
				?>" class="btn btn-default"><i class="icon-refresh"></i> Reset</a>
			</div>
			<?php } ?>
		</div>
		</form>
	</div>
	  
	<div class="container">
	<div class="row">
    <div class="col-sm-12 blog-main">
		<br>
	   		<table class="table table-bordered table-hover" id="tableTabel">
				<thead>
					<tr>
						<th width="5%">No.</th>
						<th width="50%">Judul</th>
						<th width="15%">Keterangan Pengisian</th>
						<th width="15%">Keterangan Pemeriksaan</th>
						<!--<th width="15%">Data Tahun Sebelumnya</th>-->
						<th width="10%">Konsep dan Definisi</th>
					</tr>
				</thead>
				<tbody>
<?php 
if (empty($data)) {
    echo "<tr><td colspan='5' style='text-align:center;font-weight:bold'>--Tabel tidak ditemukan--</td></tr>";
} else {
    $no = 1;
    foreach ($data as $b) {

        // Bersihkan judul dari karakter berbahaya
        $judul_bersih = preg_replace("/\r|\n/", " ", $b->judul_ind);
?>
<tr class="tabel-row">
    <td align="center" class="tabel-row-number"><?php echo $no++; ?></td>

    <td class="tabel-judul">
        <?php 
        // Jika link dimulai dengan http = Google Sheet (langsung), selain itu = internal portal URL
        $tabel_url = $b->link_tabel;
        if (stripos($tabel_url, 'http') !== 0 && !empty($tabel_url)) {
            $tabel_url = base_url() . $tabel_url;
        }
        ?>
        <a href="<?php echo $tabel_url; ?>" target="_blank">
            <?php echo htmlspecialchars($judul_bersih); ?>
        </a>
    </td>

    <!-- STATUS PENGISIAN -->
    <td>
        <?php if ($b->is_periksa == '1') { ?>
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
            Belum Diisi
            <div style="margin-top:5px">
                <a href="javascript:;"
                   class="btn btn-xs btn-warning btn-isi-data"
                   data-id="<?php echo $b->id; ?>"
                   data-judul="<?php echo htmlspecialchars($judul_bersih, ENT_QUOTES); ?>"
                   data-unit="<?php echo htmlspecialchars($b->id_unitkerja, ENT_QUOTES); ?>">
                    <i class="icon-pencil icon-white"></i> Update Status
                </a>
            </div>
        <?php } ?>
    </td>

    <td>
        <?php
        if ($b->is_confirm == '2') {
            echo 'Belum Dicek';
        } else {
            echo 'Sudah Dicek : ' . htmlspecialchars($b->catatan);
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

    <td align="center">
        <a href="<?php echo base_url(); ?>index.php/admin/dda/kondef?id=<?php echo $b->id; ?>">
            <i class="icon-search icon-black"></i>
        </a>
    </td>
</tr>
<?php
    }
}
?>
				<tr class="no-result-tabel" id="noResultTabel">
					<td colspan="5">-- Tidak ada tabel yang cocok dengan pencarian --</td>
				</tr>
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


<!-- Modal Popup untuk Konfirmasi--> 
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

<script>
$(document).on('click', '.btn-isi-data', function () {
    $('#id_tabel_modal').val(this.dataset.id);
    $('#judul_tabel_modal').val(this.dataset.judul);
    $('#asal_data_modal').val(this.dataset.unit);
    $('#ModalIsiData').modal('show');
});
</script>

<!-- JavaScript untuk pencarian Judul Tabel -->
<script type="text/javascript">
$(document).ready(function() {
	var $searchInput = $('#searchTabel');
	var $rows = $('.tabel-row');
	var $searchInfo = $('#searchTabelInfo');
	var $searchCount = $('#searchTabelCount');
	var $totalCount = $('#totalTabelCount');
	var $noResult = $('#noResultTabel');
	var totalRows = $rows.length;
	
	$totalCount.text(totalRows);
	
	// Real-time search on keyup
	$searchInput.on('keyup', function() {
		var keyword = $(this).val().toLowerCase().trim();
		var visibleCount = 0;
		
		if (keyword === '') {
			$rows.show();
			$searchInfo.removeClass('active');
			$noResult.removeClass('active');
			var num = 1;
			$rows.each(function() {
				$(this).find('.tabel-row-number').text(num++);
			});
			return;
		}
		
		$searchInfo.addClass('active');
		var num = 1;
		
		$rows.each(function() {
			var judulTabel = $(this).find('.tabel-judul').text().toLowerCase();
			if (judulTabel.indexOf(keyword) > -1) {
				$(this).show();
				$(this).find('.tabel-row-number').text(num++);
				visibleCount++;
			} else {
				$(this).hide();
			}
		});
		
		$searchCount.text(visibleCount);
		
		if (visibleCount === 0) {
			$noResult.addClass('active');
		} else {
			$noResult.removeClass('active');
		}
	});
	
	// Clear search button
	$('#btnClearSearchTabel').on('click', function() {
		$searchInput.val('').trigger('keyup').focus();
	});
});
</script>
