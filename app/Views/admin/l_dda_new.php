<?php
$tab = (segment_safe(3) == null) ? 1 : segment_safe(3);
?>

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

<div class="container">
<div class="row">
<div class="col-sm-12 blog-main">

<br>

<table class="table table-bordered table-hover">
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
<tr>
    <td align="center"><?= $no++; ?></td>

    <td>
        <a href="<?= $b->link_tabel ?>" target="_blank">
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
