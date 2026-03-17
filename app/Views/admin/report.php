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
				<b><a class="navbar-brand" href="#">Report Pemasukan Data Jawa Tengah Dalam Angka 2024</a></b>
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
<form class="navbar-form navbar-left" method="post" action="<?php echo site_url('admin/report'); ?>">
<select name="jenis_rekap" class="form-control" tabindex="3" style="width: 400px" required><option value=""> - Jenis Rekap - </option>
			<?php
				if(session()->get('admin_level') == 'lo')
				{
				$l_jenis_rekap	= array('Berdasarkan Instansi','Berdasarkan Tim','Progress Portal');
				}
				else
				{
				$l_jenis_rekap	= array('Berdasarkan Instansi','Berdasarkan Tim','Progress Portal');	
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

<button type="submit" class="btn btn-primary" tabindex="24" ><i class="icon icon-ok icon-white" name="proses"></i> Submit</button>
		<a href="<?php echo site_url('admin/'); ?>" class="btn btn-success" tabindex="25" ><i class="icon icon-arrow-left icon-white"></i> Kembali</a>

<br><br><br>
<?php require "view_report.php"; ?>		
		
</form>
</div>
<br>

</div>
