<?php
$tab = (segment_safe(3) == null) ? 1 : segment_safe(3);
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
        <span class="navbar-brand">TABEL YANG DIMUAT DI JAWA TENGAH DALAM ANGKA</span>

        <?php if (
            session()->get('admin_unitkerja') != 'bps'
            && session()->get('admin_unitkerja') != ''
        ) { ?>
        <ul class="nav navbar-nav">
            <li>
                <a href="<?= base_url('index.php/admin/master_tabel_opd/add') ?>">
                    <i class="icon-plus-sign icon-white"></i> Tambah Tabel
                </a>
            </li>
        </ul>
        <?php } ?>
    </div>
</div>

<?= session()->getFlashdata("k"); ?>

<!-- Search Box Judul Tabel -->
<div class="search-tabel-container">
	<div class="row">
		<div class="col-sm-7 col-md-6">
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
	</div>
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
    <th width="10%">Konsep & Definisi</th>
</tr>
</thead>

<tbody>
<?php if (empty($data)) { ?>
<tr>
    <td colspan="5" class="text-center"><strong>-- Tabel tidak ditemukan --</strong></td>
</tr>
<?php } else {
$no = 1;
foreach ($data as $b) { ?>
<tr class="tabel-row">
    <td align="center" class="tabel-row-number"><?= $no++; ?></td>

    <td class="tabel-judul">
        <?php 
        // Jika link dimulai dengan http = Google Sheet, selain itu = internal portal URL
        $tabel_url = $b->link_tabel;
        if (stripos($tabel_url, 'http') !== 0 && !empty($tabel_url)) {
            $tabel_url = base_url() . $tabel_url;
        }
        ?>
        <a href="<?= $tabel_url ?>" target="_blank">
            <?= $b->judul_ind ?>
        </a>
    </td>

    <!-- STATUS PENGISIAN -->
    <td>
    <?php if ($b->is_periksa == '1') { ?>
        <strong>Sudah Diisi</strong>
        <?php if (!empty($b->catatan_periksa) && $b->catatan_periksa != '-') { ?>
            <br>Catatan: <?= $b->catatan_periksa ?>
        <?php } ?>
        <div style="margin-top:5px;">
            <a href="<?= base_url('index.php/admin/batal_isi/'.$b->id) ?>"
               class="btn btn-xs btn-danger"
               onclick="return confirm('Batalkan status?')">
               Batal
            </a>
        </div>
    <?php } else { ?>
        Belum Diisi
        <div style="margin-top:5px;">
            <a href="javascript:;"
               class="btn btn-xs btn-warning"
               onclick="isi_data(
                   <?= json_encode($b->id); ?>,
                   <?= json_encode($b->judul_ind); ?>,
                   <?= json_encode($b->id_unitkerja); ?>
               )">
               <i class="icon-pencil icon-white"></i> Update Status
            </a>
        </div>
    <?php } ?>
    </td>

    <!-- PEMERIKSAAN -->
    <td>
        <?= ($b->is_confirm == '2')
            ? 'Belum Dicek'
            : 'Sudah Dicek : '.$b->catatan; ?>

        <?php if (
            session()->get('admin_unitkerja') == 'bps'
            && $b->is_confirm == '2'
        ) { ?>
        <div class="btn-group">
            <a href="#"
               class="konfirmasi_modal btn btn-info btn-sm"
               id="<?= $b->id ?>">
               <i class="icon-check icon-white"></i> Cek Data
            </a>
        </div>
        <?php } ?>
    </td>

    <td align="center">
        <a href="<?= base_url('index.php/admin/dda/kondef?id='.$b->id) ?>">
            <i class="icon-search icon-black"></i>
        </a>
    </td>
</tr>
<?php } } ?>
<tr class="no-result-tabel" id="noResultTabel">
	<td colspan="5">-- Tidak ada tabel yang cocok dengan pencarian --</td>
</tr>
</tbody>
</table>

</div>
</div>
</div>

</div>
</div>
</div>

<!-- ================= MODAL ================= -->

<div id="ModalDelete" class="modal fade"></div>
<div id="ModalEdit" class="modal fade"></div>
<div id="ModalKonfirmasi" class="modal fade"></div>

<div id="ModalIsiData" class="modal fade">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title">Update Status Pengisian</h4>
</div>

<form action="<?= base_url('index.php/admin/act_update_isi') ?>" method="post">
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
        <label>Catatan</label>
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

<!-- ================= JAVASCRIPT ================= -->

<script type="text/javascript">
function isi_data(id, judul, asal) {
    $('#id_tabel_modal').val(id);
    $('#judul_tabel_modal').val(judul);
    $('#asal_data_modal').val(asal);
    $('#ModalIsiData').modal('show');
}

$(document).ready(function () {

    $('.open_modal').click(function () {
        $.get("<?= base_url('index.php/admin/dda/del') ?>",
            { delete_id: $(this).attr('id') },
            function (res) {
                $('#ModalDelete').html(res).modal('show');
            }
        );
    });

    $('.view_modal').click(function () {
        $.get("<?= base_url('index.php/admin/dda/in') ?>",
            { in_id: $(this).attr('id') },
            function (res) {
                $('#ModalEdit').html(res).modal('show');
            }
        );
    });

    $('.konfirmasi_modal').click(function () {
        $.get("<?= base_url('index.php/admin/dda/konfirmasi') ?>",
            { konfirmasi_id: $(this).attr('id') },
            function (res) {
                $('#ModalKonfirmasi').html(res).modal('show');
            }
        );
    });

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
