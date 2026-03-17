<div class="clearfix">
    <div class="alert alert-dismissable alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        Selamat datang <strong><?php echo session()->get('admin_nama'); ?></strong>. 
        Berikut adalah Statistik Progres Data DDA Tahun <strong><?php echo session()->get("admin_ta"); ?></strong>.
    </div>
</div>

<!-- KOTAK ANGKA RINGKASAN -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-blue" style="background-color: #337; color: white;">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-pencil fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge" style="font-size: 40px; font-weight: bold;">
                            <?php echo isset($stat_diisi) ? $stat_diisi : 0; ?>
                        </div>
                        <div>Sudah Diisi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-red" style="background-color: #d9534f; color: white;">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-warning fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge" style="font-size: 40px; font-weight: bold;">
                            <?php echo isset($stat_belum_diisi) ? $stat_belum_diisi : 0; ?>
                        </div>
                        <div>Belum Diisi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-green" style="background-color: #5cb85c; color: white;">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-check fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge" style="font-size: 40px; font-weight: bold;">
                            <?php echo isset($stat_sudah_acc) ? $stat_sudah_acc : 0; ?>
                        </div>
                        <div>Sudah Diperiksa</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-yellow" style="background-color: #f0ad4e; color: white;">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-clock-o fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge" style="font-size: 40px; font-weight: bold;">
                            <?php echo isset($stat_belum_acc) ? $stat_belum_acc : 0; ?>
                        </div>
                        <div>Belum Diperiksa</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 1 -->
<div class="row" style="margin-top: 20px;">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-pie-chart fa-fw"></i> Persentase Pengisian Data</div>
            <div class="panel-body">
                <div id="container_pengisian" style="width: 100%; height: 350px;"></div>
            </div>
        </div>
    </div>
    <!-- BARIS GRAFIK 2 -->
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-pie-chart fa-fw"></i> Persentase Pemeriksaan Data</div>
            <div class="panel-body">
                <div id="container_acc" style="width: 100%; height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 3 -->
<div class="row" style="margin-top: 20px;">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-users fa-fw"></i> Progres per Tim 
            </div>
            <div class="panel-body">
                <div id="container_tim" style="width: 100%; height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 4 -->
<div class="row" style="margin-top: 20px;">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bar-chart-o fa-fw"></i> Detail Progres per Perangkat Daerah (OPD)/Instansi
            </div>
            <div class="panel-body">
                <div id="container_opd" style="width: 100%; height: 1500px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT -->
<script src="<?php echo base_url(); ?>aset/js/highcharts.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function(event) {
    
    // --- VARIABEL DATA ---
    var valDiisi      = <?php echo (isset($stat_diisi) && $stat_diisi != '') ? $stat_diisi : 0; ?>;
    var valBelumDiisi = <?php echo (isset($stat_belum_diisi) && $stat_belum_diisi != '') ? $stat_belum_diisi : 0; ?>;
    var valSudahAcc   = <?php echo (isset($stat_sudah_acc) && $stat_sudah_acc != '') ? $stat_sudah_acc : 0; ?>;
    var valBelumAcc   = <?php echo (isset($stat_belum_acc) && $stat_belum_acc != '') ? $stat_belum_acc : 0; ?>;
    
    // Data Array OPD
    var listOpd = <?php echo (isset($grafik_opd_nama) && $grafik_opd_nama != '') ? $grafik_opd_nama : '[]'; ?>;
    var dOpdAcc = <?php echo (isset($grafik_opd_acc) && $grafik_opd_acc != '') ? $grafik_opd_acc : '[]'; ?>;
    var dOpdWait = <?php echo (isset($grafik_opd_menunggu) && $grafik_opd_menunggu != '') ? $grafik_opd_menunggu : '[]'; ?>;
    var dOpdBelum = <?php echo (isset($grafik_opd_belum) && $grafik_opd_belum != '') ? $grafik_opd_belum : '[]'; ?>;

    // Data Array TIM
    var listTim = <?php echo (isset($grafik_tim_nama) && $grafik_tim_nama != '') ? $grafik_tim_nama : '[]'; ?>;
    var dTimAcc = <?php echo (isset($grafik_tim_acc) && $grafik_tim_acc != '') ? $grafik_tim_acc : '[]'; ?>;
    var dTimWait = <?php echo (isset($grafik_tim_menunggu) && $grafik_tim_menunggu != '') ? $grafik_tim_menunggu : '[]'; ?>;
    var dTimBelum = <?php echo (isset($grafik_tim_belum) && $grafik_tim_belum != '') ? $grafik_tim_belum : '[]'; ?>;

    // --- 1. RENDER PENGISIAN ---
    Highcharts.chart('container_pengisian', {
        chart: { type: 'pie' }, 
        title: { text: 'Persentase Pengisian Data (Tahun <?php echo session()->get("admin_ta"); ?>)' },
        tooltip: { pointFormat: '<b>{point.percentage:.1f}%</b>' },
        plotOptions: { 
            pie: {
                innerSize: '50%', 
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: { enabled: false }, 
                showInLegend: true } },
        colors: ['#337', '#d9534f'],
        series: [{ name: 'Jumlah', colorByPoint: true, data: [{ name: 'Sudah Diisi', y: valDiisi }, { name: 'Belum Diisi', y: valBelumDiisi, selected: true }] }],
        exporting: { enabled: false },
        credits: { enabled: false }
    });

    // --- 2. RENDER ACC ---
    Highcharts.chart('container_acc', {
        chart: { type: 'pie' }, 
        title: { text: 'Persentase Pemeriksaan Data (Tahun <?php echo session()->get("admin_ta"); ?>)' },
        tooltip: { pointFormat: '<b>{point.percentage:.1f}%</b>' },
        plotOptions: { pie: {
                innerSize: '50%', 
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: { enabled: false }, 
                showInLegend: true } },
        colors: ['#5cb85c', '#f0ad4e'],
        series: [{ name: 'Jumlah', colorByPoint: true, data: [{ name: 'Sudah Diperiksa', y: valSudahAcc }, { name: 'Belum Diperiksa', y: valBelumAcc }] }],
        exporting: { enabled: false },
        credits: { enabled: false }
    });

    // --- 3. RENDER GRAFIK TIM ---
   Highcharts.chart('container_tim', {
        chart: { type: 'column' },
        title: { text: 'Detail Status Pemeriksaan per Tim (Tahun <?php echo session()->get("admin_ta"); ?>)' },
        xAxis: {
            categories: listTim, 
            title: { text: null },
            labels: { 
                style: { fontWeight: 'bold' },
                autoRotation: [-45, -90] 
            }
        },
        yAxis: {
            min: 0, 
            max: 100, 
            title: { text: 'Persentase (%)' },
            stackLabels: { enabled: false }
        },
        legend: { reversed: true },
        plotOptions: {
            series: { 
                stacking: 'percent', 
                borderWidth: 0,
                dataLabels: {
                    enabled: true,
                    formatter: function() { if (this.y > 0) return this.y; },
                    style: { textOutline: '1px contrast' }
                }
            }
        },
        
        tooltip: {
            pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} Tabel</b> ({point.percentage:.0f}%)<br/>',
            shared: true
        },
        colors: ['#d9534f', '#f0ad4e', '#5cb85c'], 
        series: [
            { name: 'Belum Diisi', data: dTimBelum },       
            { name: 'Sudah Diisi', data: dTimWait },       
            { name: 'Sudah Diperiksa', data: dTimAcc }            
        ],
        exporting: { enabled: false },
        credits: { enabled: false }
    });

    // --- 4. RENDER GRAFIK OPD ---
     Highcharts.chart('container_opd', {
        chart: { type: 'bar' },
        title: { text: 'Detail Status Pemeriksaan per OPD (Tahun <?php echo session()->get("admin_ta"); ?>)' },
        xAxis: {
            categories: listOpd,
            title: { text: null }, 
            labels: { 
                step: 1,
                style: { fontSize: '11px' }  
            } 
        },
        yAxis: {
            min: 0,
            max: 100, 
            title: { text: 'Persentase (%)' }, 
            stackLabels: { enabled: false } 
        },
        legend: { reversed: true },
        
        plotOptions: {
            series: {
                stacking: 'percent',
                borderWidth: 0, 
                dataLabels: {
                    enabled: true,
                    formatter: function() {
                        if (this.y > 0) {
                            return this.y;
                        }
                    },
                    style: {
                        fontSize: '11px',
                        textOutline: '1px contrast' 
                    }
                }
            }
        },
        
        tooltip: {
            pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} Tabel</b> ({point.percentage:.0f}%)<br/>',
            shared: true
        },

        colors: ['#d9534f', '#f0ad4e', '#5cb85c'],
        series: [
            { name: 'Belum Diisi', data: dOpdBelum },       
            { name: 'Sudah Diisi', data: dOpdWait },       
            { name: 'Sudah Diperiksa', data: dOpdAcc }            
        ],
        exporting: { enabled: false },
        credits: { enabled: false }
    });

});
</script>
