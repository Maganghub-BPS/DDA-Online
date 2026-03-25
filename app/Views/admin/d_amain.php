<!-- Custom Styles for Dashboard -->
<style>
    .card-stat {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .stat-icon {
        transition: transform 0.3s ease;
    }

    .card-stat:hover .stat-icon {
        transform: scale(1.1);
    }
</style>

<!-- Welcome Alert -->
<div class="alert alert-light alert-dismissible fade show border shadow-sm" role="alert" id="alert" style="border-radius: 12px;">
    <div class="d-flex align-items-center">
        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: rgba(255,109,31,0.1); flex-shrink: 0;">
            <i class="bi bi-hand-thumbs-up text-primary" style="font-size: 1.2rem;"></i>
        </div>
        <div>
            Selamat datang <strong><?php echo session()->get('admin_nama'); ?></strong>.
            Berikut adalah Statistik Progres Data DDA Tahun <strong><?php echo session()->get("admin_ta"); ?></strong>.
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Info Cards -->
<div class="row g-3 mb-4">
    <?php
    $total_tabel = (isset($stat_diisi) ? $stat_diisi : 0) + (isset($stat_belum_diisi) ? $stat_belum_diisi : 0);
    // "Sudah Diisi" (Menunggu Verifikasi) = Total yang sudah diisi - yang sudah diverifikasi
    $sudah_diisi_waiting = (isset($stat_diisi) ? $stat_diisi : 0) - (isset($stat_sudah_acc) ? $stat_sudah_acc : 0);
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat border-0 shadow-sm bg-primary text-white p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1"><?php echo number_format($total_tabel); ?></h3>
                    <p class="small mb-0 opacity-75">Total Tabel DDA</p>
                </div>
                <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-table" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat border-0 shadow-sm text-white p-3 h-100" style="background: #ef4444;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1"><?php echo isset($stat_belum_diisi) ? number_format($stat_belum_diisi) : 0; ?></h3>
                    <p class="small mb-0 opacity-75">Belum Diisi</p>
                </div>
                <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-exclamation-triangle" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat border-0 shadow-sm text-white p-3 h-100" style="background: #eab308;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1"><?php echo number_format($sudah_diisi_waiting); ?></h3>
                    <p class="small mb-0 opacity-75">Sudah Diisi</p>
                </div>
                <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-pencil-square" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat border-0 shadow-sm text-white p-3 h-100" style="background: #22c55e;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1"><?php echo isset($stat_sudah_acc) ? number_format($stat_sudah_acc) : 0; ?></h3>
                    <p class="small mb-0 opacity-75">Diverifikasi</p>
                </div>
                <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-check-circle" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Row 1: Pie Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i>Persentase Pengisian Data</h6>
            </div>
            <div class="card-body">
                <div id="container_pengisian" style="width: 100%; height: 350px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-success"></i>Persentase Verifikasi Data</h6>
            </div>
            <div class="card-body">
                <div id="container_acc" style="width: 100%; height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Section: Progres per Tim Kerja -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2 text-primary"></i>Progres per Tim Kerja</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="25%">Tim Kerja</th>
                                <th width="20%">Progres</th>
                                <th width="12%" class="text-center">Belum Diisi</th>
                                <th width="12%" class="text-center">Sudah Diisi</th>
                                <th width="12%" class="text-center">Diverifikasi</th>
                                <th width="12%" class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody id="timTableBody">
                            <!-- Data akan diisi via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaderboard OPD -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Progres per Perangkat Daerah (OPD)</h6>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchOPD" placeholder="Cari OPD...">
                        </div>
                        <select class="form-select form-select-sm" id="sortOPD" style="width: auto;">
                            <option value="name-asc">Nama A-Z</option>
                            <option value="name-desc">Nama Z-A</option>
                            <option value="progress-desc">Progres Tertinggi</option>
                            <option value="progress-asc">Progres Terendah</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="25%">Perangkat Daerah</th>
                                <th width="20%">Progres</th>
                                <th width="12%" class="text-center">Belum Diisi</th>
                                <th width="12%" class="text-center">Sudah Diisi</th>
                                <th width="12%" class="text-center">Diverifikasi</th>
                                <th width="12%" class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- JAVASCRIPT -->
<script>
    document.addEventListener("DOMContentLoaded", function(event) {

        // --- DATA VARIABLES ---
        var valDiisi = <?php echo (isset($stat_diisi) && $stat_diisi != '') ? $stat_diisi : 0; ?>;
        var valBelumDiisi = <?php echo (isset($stat_belum_diisi) && $stat_belum_diisi != '') ? $stat_belum_diisi : 0; ?>;
        var valSudahAcc = <?php echo (isset($stat_sudah_acc) && $stat_sudah_acc != '') ? $stat_sudah_acc : 0; ?>;
        var valBelumAcc = <?php echo (isset($stat_belum_acc) && $stat_belum_acc != '') ? $stat_belum_acc : 0; ?>;

        var listOpd = <?php echo (isset($grafik_opd_nama) && $grafik_opd_nama != '') ? $grafik_opd_nama : '[]'; ?>;
        var dOpdAcc = <?php echo (isset($grafik_opd_acc) && $grafik_opd_acc != '') ? $grafik_opd_acc : '[]'; ?>;
        var dOpdWait = <?php echo (isset($grafik_opd_menunggu) && $grafik_opd_menunggu != '') ? $grafik_opd_menunggu : '[]'; ?>;
        var dOpdBelum = <?php echo (isset($grafik_opd_belum) && $grafik_opd_belum != '') ? $grafik_opd_belum : '[]'; ?>;

        var listTim = <?php echo (isset($grafik_tim_nama) && $grafik_tim_nama != '') ? $grafik_tim_nama : '[]'; ?>;
        var dTimAcc = <?php echo (isset($grafik_tim_acc) && $grafik_tim_acc != '') ? $grafik_tim_acc : '[]'; ?>;
        var dTimWait = <?php echo (isset($grafik_tim_menunggu) && $grafik_tim_menunggu != '') ? $grafik_tim_menunggu : '[]'; ?>;
        var dTimBelum = <?php echo (isset($grafik_tim_belum) && $grafik_tim_belum != '') ? $grafik_tim_belum : '[]'; ?>;

        // --- 1. PIE CHART: Pengisian ---
        Highcharts.chart('container_pengisian', {
            chart: {
                type: 'pie',
                backgroundColor: 'transparent',
                style: {
                    fontFamily: "'Public Sans', sans-serif"
                },
                marginTop: 0,
                spacingTop: 0
            },
            title: {
                text: null
            }, // Sudah ada di card header
            tooltip: {
                pointFormat: '<b>{point.y}</b> Tabel ({point.percentage:.1f}%)'
            },
            plotOptions: {
                pie: {
                    innerSize: '55%',
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '{point.name}: {point.percentage:.1f}%',
                        distance: 20,
                        style: {
                            fontWeight: '500',
                            fontSize: '11px',
                            color: '#64748b',
                            textOutline: 'none'
                        }
                    },
                    showInLegend: true,
                    borderWidth: 2,
                    borderColor: '#fff'
                }
            },
            legend: {
                itemStyle: {
                    fontSize: '11px',
                    fontWeight: '500',
                    color: '#64748b'
                },
                align: 'center',
                verticalAlign: 'bottom',
                layout: 'horizontal'
            },
            colors: ['#FF6D1F', '#ef4444'],
            series: [{
                name: 'Status',
                colorByPoint: true,
                data: [{
                        name: 'Sudah Diisi',
                        y: valDiisi
                    },
                    {
                        name: 'Belum Diisi',
                        y: valBelumDiisi
                    }
                ]
            }],
            exporting: {
                enabled: false
            },
            credits: {
                enabled: false
            }
        });

        // --- 2. PIE CHART: Pemeriksaan ---
        Highcharts.chart('container_acc', {
            chart: {
                type: 'pie',
                backgroundColor: 'transparent',
                style: {
                    fontFamily: "'Public Sans', sans-serif"
                },
                marginTop: 0,
                spacingTop: 0
            },
            title: {
                text: null
            }, // Sudah ada di card header
            tooltip: {
                pointFormat: '<b>{point.y}</b> Tabel ({point.percentage:.1f}%)'
            },
            plotOptions: {
                pie: {
                    innerSize: '55%',
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '{point.name}: {point.percentage:.1f}%',
                        distance: 20,
                        style: {
                            fontWeight: '500',
                            fontSize: '11px',
                            color: '#64748b',
                            textOutline: 'none'
                        }
                    },
                    showInLegend: true,
                    borderWidth: 2,
                    borderColor: '#fff'
                }
            },
            legend: {
                itemStyle: {
                    fontSize: '11px',
                    fontWeight: '500',
                    color: '#64748b'
                },
                align: 'center',
                verticalAlign: 'bottom',
                layout: 'horizontal'
            },
            colors: ['#22c55e', '#eab308'],
            series: [{
                name: 'Status',
                colorByPoint: true,
                data: [{
                        name: 'Diverifikasi',
                        y: valSudahAcc
                    },
                    {
                        name: 'Sudah Diisi',
                        y: valBelumAcc
                    }
                ]
            }],
            exporting: {
                enabled: false
            },
            credits: {
                enabled: false
            }
        });

        // --- 3. TABEL: Progres Tim Kerja ---
        function renderTimTable() {
            var tbody = document.getElementById('timTableBody');
            if (!tbody) return;
            var html = '';
            if (listTim.length === 0) {
                html = '<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data tim</td></tr>';
            } else {
                for (var i = 0; i < listTim.length; i++) {
                    var acc = dTimAcc[i] || 0;
                    var wait = dTimWait[i] || 0;
                    var belum = dTimBelum[i] || 0;
                    var total = acc + wait + belum;
                    var progress = total > 0 ? Math.round(((acc + wait) / total) * 100) : 0;

                    var accPct = total > 0 ? ((acc / total) * 100).toFixed(1) : 0;
                    var waitPct = total > 0 ? ((wait / total) * 100).toFixed(1) : 0;
                    var belumPct = total > 0 ? ((belum / total) * 100).toFixed(1) : 0;

                    html += '<tr>';
                    html += '<td class="text-center">' + (i + 1) + '</td>';
                    html += '<td class="fw-medium">' + listTim[i] + '</td>';
                    html += '<td>';
                    html += '<div class="progress" style="height: 10px; border-radius: 5px;" data-bs-toggle="tooltip" title="Diverifikasi: ' + accPct + '% | Sudah Diisi: ' + waitPct + '% | Belum Diisi: ' + belumPct + '%">';
                    html += '<div class="progress-bar" style="width: ' + accPct + '%; background: #22c55e;"></div>';
                    html += '<div class="progress-bar" style="width: ' + waitPct + '%; background: #eab308;"></div>';
                    html += '<div class="progress-bar" style="width: ' + belumPct + '%; background: #ef4444;"></div>';
                    html += '</div>';
                    html += '<small class="text-muted">' + progress + '% terisi</small>';
                    html += '</td>';
                    html += '<td class="text-center"><span class="badge bg-danger-subtle text-danger">' + belum + '</span></td>';
                    html += '<td class="text-center"><span class="badge bg-warning-subtle text-warning" style="color: #856404 !important;">' + wait + '</span></td>';
                    html += '<td class="text-center"><span class="badge bg-success-subtle text-success">' + acc + '</span></td>';
                    html += '<td class="text-center"><span class="badge bg-primary-subtle text-primary">' + total + '</span></td>';
                    html += '</tr>';
                }
            }
            tbody.innerHTML = html;
            // Tooltip init will follow in renderLeaderboard (global init)
        }

        renderTimTable();


        // --- 5. LEADERBOARD ---
        var leaderboardData = [];
        for (var i = 0; i < listOpd.length; i++) {
            var acc = dOpdAcc[i] || 0;
            var wait = dOpdWait[i] || 0;
            var belum = dOpdBelum[i] || 0;
            var total = acc + wait + belum;
            var progress = total > 0 ? Math.round(((acc + wait) / total) * 100) : 0;
            leaderboardData.push({
                name: listOpd[i],
                acc: acc,
                wait: wait,
                belum: belum,
                total: total,
                progress: progress
            });
        }

        function renderLeaderboard(data) {
            var tbody = document.getElementById('leaderboardBody');
            if (!tbody) return;
            var html = '';
            if (data.length === 0) {
                html = '<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data</td></tr>';
            } else {
                data.forEach(function(item, idx) {
                    var accPct = item.total > 0 ? ((item.acc / item.total) * 100).toFixed(1) : 0;
                    var waitPct = item.total > 0 ? ((item.wait / item.total) * 100).toFixed(1) : 0;
                    var belumPct = item.total > 0 ? ((item.belum / item.total) * 100).toFixed(1) : 0;
                    html += '<tr>';
                    html += '<td class="text-center">' + (idx + 1) + '</td>';
                    html += '<td class="fw-medium text-uppercase">' + item.name + '</td>';
                    html += '<td>';
                    html += '<div class="progress" style="height: 10px; border-radius: 5px;" data-bs-toggle="tooltip" title="Diverifikasi: ' + accPct + '% | Sudah Diisi: ' + waitPct + '% | Belum Diisi: ' + belumPct + '%">';
                    html += '<div class="progress-bar" style="width: ' + accPct + '%; background: #22c55e;"></div>';
                    html += '<div class="progress-bar" style="width: ' + waitPct + '%; background: #eab308;"></div>';
                    html += '<div class="progress-bar" style="width: ' + belumPct + '%; background: #ef4444;"></div>';
                    html += '</div>';
                    html += '<small class="text-muted">' + item.progress + '% terisi</small>';
                    html += '</td>';
                    html += '<td class="text-center"><span class="badge bg-danger-subtle text-danger">' + item.belum + '</span></td>';
                    html += '<td class="text-center"><span class="badge bg-warning-subtle text-warning" style="color: #856404 !important;">' + item.wait + '</span></td>';
                    html += '<td class="text-center"><span class="badge bg-success-subtle text-success">' + item.acc + '</span></td>';
                    html += '<td class="text-center"><span class="badge bg-primary-subtle text-primary">' + item.total + '</span></td>';
                    html += '</tr>';
                });
            }
            tbody.innerHTML = html;
            // Initialize tooltips
            var tooltipList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipList.map(function(el) {
                return new bootstrap.Tooltip(el);
            });
        }

        renderLeaderboard(leaderboardData);

        // Search & Sort
        var searchInput = document.getElementById('searchOPD');
        var sortSelect = document.getElementById('sortOPD');

        function filterAndSort() {
            var keyword = searchInput ? searchInput.value.toLowerCase().trim() : '';
            var sortVal = sortSelect ? sortSelect.value : 'name-asc';
            var filtered = leaderboardData.filter(function(item) {
                return item.name.toLowerCase().indexOf(keyword) > -1;
            });
            filtered.sort(function(a, b) {
                if (sortVal === 'name-asc') return a.name.localeCompare(b.name);
                if (sortVal === 'name-desc') return b.name.localeCompare(a.name);
                if (sortVal === 'progress-desc') return b.progress - a.progress;
                if (sortVal === 'progress-asc') return a.progress - b.progress;
                return 0;
            });
            renderLeaderboard(filtered);
        }

        if (searchInput) searchInput.addEventListener('keyup', filterAndSort);
        if (sortSelect) sortSelect.addEventListener('change', filterAndSort);
    });
</script>