<?php
	if(segment_safe(3) == null) {
		$tab = 1;
	} else {
		$tab = segment_safe(3);
	}
?>

<style>
.search-box-container {
	padding: 12px 15px;
	background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f0 100%);
	border-radius: 8px;
	margin-bottom: 15px;
	border: 1px solid #d5dbe3;
}
.search-box-container .input-group {
	max-width: 500px;
}
.search-box-container input.form-control {
	border-radius: 20px 0 0 20px;
	border: 2px solid #3498db;
	padding: 8px 15px;
	font-size: 14px;
	box-shadow: 0 2px 5px rgba(0,0,0,0.08);
	transition: border-color 0.3s, box-shadow 0.3s;
}
.search-box-container input.form-control:focus {
	border-color: #2980b9;
	box-shadow: 0 2px 10px rgba(52,152,219,0.3);
	outline: none;
}
.search-box-container .input-group-btn .btn {
	border-radius: 0 20px 20px 0;
	border: 2px solid #3498db;
	border-left: none;
	background: #3498db;
	color: #fff;
	padding: 8px 15px;
	transition: background 0.3s;
}
.search-box-container .input-group-btn .btn:hover {
	background: #2980b9;
}
.search-result-info {
	margin-top: 8px;
	font-size: 13px;
	color: #7f8c8d;
	display: none;
}
.search-result-info.active {
	display: block;
}
.no-result-row {
	display: none;
}
.no-result-row.active {
	display: table-row;
}
.no-result-row td {
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
			<span class="navbar-brand" href="#" style="text-align:center"><b>List OPD</b></span>
		</div>
	</div><!-- /.navbar -->

<?php 
$unitkerjalogin = session()->get('admin_unitkerja');
echo session()->getFlashdata("k");
?>  

	<!-- Search Box OPD & Filter Tahun -->
	<div class="search-box-container">
		<form method="GET" action="<?php echo base_url(); ?>index.php/admin/dda">
		<div class="row">
			<div class="col-sm-6 col-md-5">
				<div class="input-group">
					<input type="text" class="form-control" id="searchOPD" placeholder="&#128269; Cari nama OPD..." autocomplete="off">
					<span class="input-group-btn">
						<button class="btn btn-primary" type="button" id="btnClearSearch" title="Hapus pencarian">
							<i class="icon-remove icon-white"></i> Reset
						</button>
					</span>
				</div>
				<div class="search-result-info" id="searchInfo">
					Menampilkan <strong id="searchCount">0</strong> dari <strong id="totalCount">0</strong> OPD
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
				<a href="<?php echo base_url(); ?>index.php/admin/dda?action=reset_dda" class="btn btn-default"><i class="icon-refresh"></i> Reset Tahun</a>
			</div>
			<?php } ?>
		</div>
		</form>
	</div>

	<div class="container">
	<div class="row">
    <div class="col-sm-12 blog-main">

			<table class="table table-bordered table-hover" id="tableOPD">
				<thead>
					<tr>
						<th width="5%">No.</th>
						<th width="65%">Nama OPD</th>
						<?php if(session()->get('admin_level') != 'lo') { ?>
						<th width="30%">Nama Penanggung Jawab</th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php 
					if (empty($data)) {
						echo "<tr><td colspan='5' style='text-align: center; font-weight: bold'>--Data tidak ditemukan--</td></tr>";
					} else {
						$no 	= (isset($offset) ? $offset : 0) + 1;
						foreach ($data as $b) {
					?>
					<tr class="opd-row">
						<td align="center" class="row-number"><?php echo $no;?></td>
						<?php if(session()->get('admin_level') == 'lo') { ?>
						<td class="opd-name"><a href="<?php echo base_URL()?>index.php/admin/dda/view_tabel?id=<?php echo $b->id_unitkerja ;?>"><?php echo $b->unitkerja_ind;?></a></td>
						<?php } else { ?>
						<td class="opd-name"><a href="<?php echo base_URL()?>index.php/admin/dda/periksa_tabel?id=<?php echo $b->id_unitkerja ;?>"><?php echo $b->unitkerja_ind;?></a></td>	
						<td align="center"><?php echo $b->user_wali;?></td>	
						<?php } ?>
					</tr>
					<?php 
						$no++;
						}
					}
					?>
					<tr class="no-result-row" id="noResultRow">
						<td colspan="5">-- Tidak ada OPD yang cocok dengan pencarian --</td>
					</tr>
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

<!-- JavaScript untuk pencarian OPD -->
<script type="text/javascript">
$(document).ready(function() {
	var $searchInput = $('#searchOPD');
	var $rows = $('.opd-row');
	var $searchInfo = $('#searchInfo');
	var $searchCount = $('#searchCount');
	var $totalCount = $('#totalCount');
	var $noResult = $('#noResultRow');
	var totalRows = $rows.length;
	
	$totalCount.text(totalRows);
	
	// Real-time search on keyup
	$searchInput.on('keyup', function() {
		var keyword = $(this).val().toLowerCase().trim();
		var visibleCount = 0;
		
		if (keyword === '') {
			// Show all rows and reset numbering
			$rows.show();
			$searchInfo.removeClass('active');
			$noResult.removeClass('active');
			// Reset numbering
			var num = 1;
			$rows.each(function() {
				$(this).find('.row-number').text(num++);
			});
			return;
		}
		
		$searchInfo.addClass('active');
		var num = 1;
		
		$rows.each(function() {
			var opdName = $(this).find('.opd-name').text().toLowerCase();
			if (opdName.indexOf(keyword) > -1) {
				$(this).show();
				$(this).find('.row-number').text(num++);
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
	$('#btnClearSearch').on('click', function() {
		$searchInput.val('').trigger('keyup').focus();
	});
});
</script>
