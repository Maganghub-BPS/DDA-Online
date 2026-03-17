<script> 
    function load_nama() 
    { 
        unitkerja=$('#unitkerja').val(); 

        if($('#unitkerja').val()!="" && $('#unitkerja').val()!="*") { 

            $.ajax({ 
                type: "POST", 
                url: "<?php echo site_url('admin/load_nama'); ?>", 
                data: "unitkerja=" + unitkerja, 
                success: function (msg) { 
                    if (msg) { 
                        $('#nama_pegawai').html(msg); 
                      //  $('#products').removeClass('hidden') 
                    } 
                    } 
            }); 
           // return false; 
        } 
        else { 
            $('#nama_pegawai').html(''); 
     //   $('#products').addClass('hidden') 

        } 
    } 
</script> 


<div class="clearfix">
<div class="row">
  <div class="col-lg-12">
	
	<div class="navbar navbar-inverse">
		<div class="container">
			<div class="navbar-header">
				<b><a class="navbar-brand" href="#">Report Pemasukan Data Jawa Tengah Dalam Angka Dari Portal</a></b>
			</div>
		<!--<div class="navbar-collapse collapse navbar-inverse-collapse" style="margin-right: -20px">
			<ul class="nav navbar-nav navbar-right">
				<form class="navbar-form navbar-left" method="post" action="<?php //echo base_URL(); ?>index.php/admin/rekap/cari">
					<input type="text" class="form-control" name="q" style="width: 200px" placeholder="Kata kunci pencarian ..." required>
					<button type="submit" class="btn btn-danger"><i class="icon-search icon-white"> </i> Cari</button>
				</form>
			
			</ul>
		</div><!-- /.nav-collapse -->
		</div><!-- /.container -->
	</div><!-- /.navbar -->

  </div>
</div>

<?php echo session()->getFlashdata("k");
$jenis_rekap = service('request')->getPost('jenis_rekap');
?>
	
<!--	
<div class="alert alert-dismissable alert-success">
  <button type="button" class="close" data-dismiss="alert">x</button>
  <strong>Well done!</strong> You successfully read <a href="http://bootswatch.com/amelia/#" class="alert-link">this important alert message</a>.
</div>
	
<div class="alert alert-dismissable alert-danger">
  <button type="button" class="close" data-dismiss="alert">x</button>
  <strong>Oh snap!</strong> <a href="http://bootswatch.com/amelia/#" class="alert-link">Change a few things up</a> and try submitting again.
</div>	
-->
<div class="col-lg-12">
<!--<form class="navbar-form navbar-left" method="post" action="<?php echo base_URL(); ?>index.php/admin/matching/">

<select name="jenis_rekap" class="form-control" tabindex="3" style="width: 400px" required><option value=""> - Jenis Rekap - </option>
			<?php
				if(session()->get('admin_level') == 'lo')
				{
				$l_jenis_rekap	= array('Berdasarkan Instansi','Berdasarkan Tim');
				}
				else
				{
				$l_jenis_rekap	= array('Berdasarkan Instansi','Berdasarkan Tim');	
				}
				for ($i = 0; $i < sizeof($l_jenis_rekap); $i++) {
					if ($l_jenis_rekap[$i] == $jenis_rekap) {
						echo "<option selected value='".$i."'>".$l_jenis_rekap[$i]."</option>";
					} else {
						echo "<option value='".$i."'>".$l_jenis_rekap[$i]."</option>";
					}				
				}			
			?>			
</select>

<button type="submit" class="btn btn-primary" tabindex="24" ><i class="icon icon-ok icon-white" name="proses"></i> Update</button>-->
<button class="btn btn-primary" onclick="ambilData()">Ambil Data API</button>

<br>
<a href="<?= site_url('admin/ambil_tahun_terakhir_dari_match') ?>"
   class="btn btn-primary"
   onclick="return confirm('Ambil data dari tabel match?')">
   ▶ Ambil Data Match
</a>


<br>

<button id="btnSync" onclick="syncData()">Sinkronisasi Data</button>
<div id="log"></div>
<div style="width:100%; background:#eee; margin-top:10px;">
  <div id="progress" style="width:0%; background:#4caf50; color:#fff; text-align:center;">0%</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function syncData(page = 1) {
  $.get('<?= site_url("admin/ambil_databatch") ?>?page=' + page, function(res) {
    console.log("Response:", res); // cek isi response

    if (res.status) {
      $('#log').append('<p>' + res.message + '</p>');
      let percent = Math.round((res.current_page / res.total_page) * 100);
      $('#progress').css('width', percent + '%').text(percent + '%');

      if (res.has_next) {
        syncData(res.next_page);
      } else {
        $('#log').append('<p>Sinkronisasi selesai!</p>');
      }
    } else {
      $('#log').append('<p>Gagal: ' + res.message + '</p>');
    }
  }, 'json').fail(function(xhr) {
    console.error("AJAX Error:", xhr.responseText);
    $('#log').append('<p>AJAX gagal: ' + xhr.statusText + '</p>');
  });
}
</script>



<script>
function ambilData() {
   fetch('<?= site_url("admin/ambil_data") ?>', {
    method: 'GET'
  })
  .then(async res => {
    const text = await res.text();   // baca apa pun dari server

    try {
      const json = JSON.parse(text);
      if (!res.ok || json.status === false) {
        throw json;
      }
      alert(json.message);
    } catch (e) {
      alert('ERROR DARI SERVER:\n' + text);
    }
  })
  .catch(err => {
    alert('FETCH ERROR:\n' + err);
  });
}
</script>



<br><br><br>
<?php 
//require "view_matching.php"; 
?>		
		
<!--</form>-->
</div>
<br>

</div>
