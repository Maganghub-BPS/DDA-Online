<?php
$jenis_rekap = $jenis_rekap ?? service('request')->getPost('jenis_rekap') ?? '0';

// REPORT BERDASARKAN INSTANSI
if($jenis_rekap == '0') {
    $ta = session()->get('admin_ta');
    if(empty($ta)) $ta = date('Y');

    $data = \Config\Database::connect()->query("
        SELECT 
            m.unitkerja_ind,
            COUNT(t.id) as jumlah_tabel,
            SUM(CASE WHEN t.is_confirm != 1 AND t.is_periksa = 0 AND NOT (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%') THEN 1 ELSE 0 END) as belum_isi,
            SUM(CASE WHEN t.is_confirm != 1 AND (t.is_periksa = 1 OR (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%')) THEN 1 ELSE 0 END) as menunggu_validasi,
            SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as sudah_validasi
        FROM m_unitkerja m 
        LEFT JOIN t_list_tabel t ON m.id_unitkerja = t.id_unitkerja AND t.tahun = '$ta'
        GROUP BY m.id_unitkerja 
        ORDER BY m.unitkerja_ind ASC
    ")->getResult();
?>
    <div class="row align-items-center mb-4">
        <div class="col">
            <h6 class="text-uppercase text-secondary font-weight-bold mb-0" style="letter-spacing: 1px; font-size: 0.85rem;">
                Daftar Progres Berdasarkan Instansi
            </h6>
        </div>
        <div class="col-auto">
            <a href="<?php echo base_url(); ?>index.php/admin/export_excel_instansi" target="_blank" class="btn btn-sm btn-outline-success border-radius-lg px-3 py-2 mb-0 fw-bold">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="table-responsive bg-white rounded-3 shadow-sm border border-light overflow-hidden p-0">
        <table class="table align-items-center mb-0" id="tblInstansi">
            <thead class="bg-gray-100">
                <tr>
                    <th class="valign-middle py-3 ps-4">Nama Instansi</th>
                    <th class="valign-middle text-center py-3 px-2">Total</th>
                    <th class="valign-middle text-center text-danger py-3 px-2">Belum Diisi</th>
                    <th class="valign-middle text-center text-warning py-3 px-2">Sudah Diisi</th>
                    <th class="valign-middle text-center text-success py-3 px-2">Validasi</th>
                    <th class="valign-middle text-center py-3 ps-3" style="min-width: 180px;">Progress</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php 
                if (empty($data)) {
                    echo "<tr><td colspan='6' class='text-center py-5 text-secondary font-weight-bold opacity-5'>-- Belum Ada Data --</td></tr>";
                } else {
                    foreach ($data as $b) {
                        $persen = 0;
                        if($b->jumlah_tabel > 0){
                            $persen = ($b->sudah_validasi / $b->jumlah_tabel) * 100;
                        }
                        
                        $pg_color = 'bg-danger';
                        if ($persen > 30) $pg_color = 'bg-warning';
                        if ($persen > 70) $pg_color = 'bg-info';
                        if ($persen >= 100) $pg_color = 'bg-success';
            ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="font-weight-bold ps-4 text-wrap" style="max-width: 320px; line-height: 1.6;"><?php echo $b->unitkerja_ind; ?></td>
                    <td class="text-center font-weight-bold">
                        <span class="badge bg-light text-dark"><?php echo $b->jumlah_tabel; ?></span>
                    </td>
                    <td class="text-center">
                        <?php if($b->belum_isi > 0): ?>
                            <span class="badge border border-danger text-danger bg-danger-soft px-2 py-1" style="min-width: 25px;"><?php echo $b->belum_isi; ?></span>
                        <?php else: ?>
                            <span class="text-light opacity-5">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-xs">
                        <?php if($b->menunggu_validasi > 0): ?>
                            <span class="badge border border-warning text-warning bg-warning-soft px-2 py-1" style="min-width: 25px;"><?php echo $b->menunggu_validasi; ?></span>
                        <?php else: ?>
                            <span class="text-light opacity-5">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-xs">
                        <?php if($b->sudah_validasi > 0): ?>
                            <span class="badge border border-success text-success bg-success-soft px-2 py-1 font-weight-bolder" style="min-width: 25px;"><?php echo $b->sudah_validasi; ?></span>
                        <?php else: ?>
                            <span class="text-light opacity-5">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-items-center px-4">
                            <span class="me-2 text-xs font-weight-bold" style="min-width: 40px; text-align: right;"><?php echo number_format($persen, 0); ?>%</span>
                            <div class="progress w-100 shadow-none border-0" style="height: 6px; background-color: #ededed;">
                                <div class="progress-bar rounded-pill shadow-none <?php echo $pg_color; ?>" role="progressbar" style="width: <?php echo $persen; ?>%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php 
                    }
                }
            ?>
            </tbody>
        </table>
    </div>
<?php
}

// REPORT BERDASARKAN TIM 
if($jenis_rekap == '1') {
    $ta = session()->get('admin_ta');
    if(empty($ta)) $ta = date('Y');

    $data = \Config\Database::connect()->query("
        SELECT 
            u.user_wali,
            COUNT(t.id) as jumlah_tabel,
            SUM(CASE WHEN t.is_confirm != 1 AND t.is_periksa = 0 AND NOT (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%') THEN 1 ELSE 0 END) as belum_isi,
            SUM(CASE WHEN t.is_confirm != 1 AND (t.is_periksa = 1 OR (t.link_tabel LIKE '%portal%' OR t.link_tabel LIKE '%satudata%')) THEN 1 ELSE 0 END) as menunggu_validasi,
            SUM(CASE WHEN t.is_confirm = 1 THEN 1 ELSE 0 END) as sudah_validasi
        FROM m_unitkerja u
        LEFT JOIN t_list_tabel t ON u.id_unitkerja = t.id_unitkerja AND t.tahun = '$ta'
        WHERE u.user_wali IS NOT NULL AND u.user_wali != '' AND u.user_wali != '-'
        GROUP BY u.user_wali 
        ORDER BY u.user_wali ASC
    ")->getResult();
?>
    <div class="row align-items-center mb-4">
        <div class="col">
            <h6 class="text-uppercase text-secondary font-weight-bold mb-0" style="letter-spacing: 1px; font-size: 0.85rem;">
                Daftar Progres Berdasarkan Tim
            </h6>
        </div>
        <div class="col-auto">
            <a href="<?php echo base_url(); ?>index.php/admin/export_excel_tim" target="_blank" class="btn btn-sm btn-outline-success border-radius-lg px-3 py-2 mb-0 fw-bold">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="table-responsive bg-white rounded-3 shadow-sm border border-light overflow-hidden p-0">
        <table class="table align-items-center mb-0">
            <thead class="bg-gray-100">
                <tr>
                    <th class="valign-middle py-3 ps-4">Nama Tim</th>
                    <th class="valign-middle text-center py-3 px-2">Total</th>
                    <th class="valign-middle text-center text-danger py-3 px-2">Belum Diisi</th>
                    <th class="valign-middle text-center text-warning py-3 px-2">Sudah Diisi</th>
                    <th class="valign-middle text-center text-success py-3 px-2">Validasi</th>
                    <th class="valign-middle text-center py-3 ps-3" style="min-width: 180px;">Progress</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php 
                if (empty($data)) {
                    echo "<tr><td colspan='6' class='text-center py-5 text-secondary font-weight-bold opacity-5'>-- Belum Ada Data --</td></tr>";
                } else {
                    foreach ($data as $b) {
                        $persen = 0;
                        if($b->jumlah_tabel > 0){
                            $persen = ($b->sudah_validasi / $b->jumlah_tabel) * 100;
                        }
                        
                        $pg_color = 'bg-danger';
                        if ($persen > 30) $pg_color = 'bg-warning';
                        if ($persen > 70) $pg_color = 'bg-info';
                        if ($persen >= 100) $pg_color = 'bg-success';
            ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="font-weight-bold text-uppercase ps-4 text-dark"><?php echo $b->user_wali; ?></td>
                    <td class="text-center font-weight-bold">
                        <span class="badge bg-light text-dark"><?php echo $b->jumlah_tabel; ?></span>
                    </td>
                    <td class="text-center">
                        <?php if($b->belum_isi > 0): ?>
                            <span class="badge border border-danger text-danger bg-danger-soft px-2 py-1" style="min-width: 25px;"><?php echo $b->belum_isi; ?></span>
                        <?php else: ?>
                            <span class="text-light opacity-5">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-xs">
                        <?php if($b->menunggu_validasi > 0): ?>
                            <span class="badge border border-warning text-warning bg-warning-soft px-2 py-1" style="min-width: 25px;"><?php echo $b->menunggu_validasi; ?></span>
                        <?php else: ?>
                            <span class="text-light opacity-5">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-xs">
                        <?php if($b->sudah_validasi > 0): ?>
                            <span class="badge border border-success text-success bg-success-soft px-2 py-1 font-weight-bolder" style="min-width: 25px;"><?php echo $b->sudah_validasi; ?></span>
                        <?php else: ?>
                            <span class="text-light opacity-5">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-items-center px-4">
                            <span class="me-2 text-xs font-weight-bold" style="min-width: 40px; text-align: right;"><?php echo number_format($persen, 0); ?>%</span>
                            <div class="progress w-100 shadow-none border-0" style="height: 6px; background-color: #ededed;">
                                <div class="progress-bar rounded-pill shadow-none <?php echo $pg_color; ?>" role="progressbar" style="width: <?php echo $persen; ?>%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php 
                    }
                }
            ?>
            </tbody>
        </table>
    </div>
<?php
}

// REPORT PORTAL 
if($jenis_rekap == '2') {
?>
    <div class="row align-items-center mb-4">
        <div class="col">
            <h6 class="text-uppercase text-secondary font-weight-bold mb-0" style="letter-spacing: 1px; font-size: 0.85rem;">
                Daftar Sinkronisasi Portal Data
            </h6>
        </div>
    </div>

    <div class="table-responsive bg-white rounded-3 shadow-sm border border-light overflow-hidden p-0">
        <table class="table align-items-center mb-0">
            <thead class="bg-gray-100">
                <tr>
                    <th class="valign-middle py-3 ps-4" style="width: 15%;">Satker</th>
                    <th class="valign-middle py-3 px-2" style="width: 30%;">Judul DDA</th>
                    <th class="valign-middle py-3 px-2" style="width: 30%;">Judul Portal</th>
                    <th class="valign-middle text-center py-3 px-2">Tahun</th>
                    <th class="valign-middle text-center py-3 px-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php 
                $data = \Config\Database::connect()->query("
                    select satker, judul_dda, judul_portal, tahun_data from t_tabel_match m left join t_dataportal p on m.id_api=p.id_portal order by satker")->getResult();
                
                if (empty($data)) {
                    echo "<tr><td colspan='5' class='text-center py-5 text-secondary font-weight-bold opacity-5'>-- Belum Ada Data --</td></tr>";
                } else {
                    foreach ($data as $b) {
                        $is_filled = ($b->tahun_data == '2025');
                        $status_label = $is_filled ? 'Sinkron' : 'Belum Sinkron';
                        $status_class = $is_filled ? 'bg-success-soft text-success border-success' : 'bg-light text-secondary border-secondary';
            ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="text-xs font-weight-bold ps-4 text-uppercase text-secondary"><?php echo $b->satker ?></td>
                    <td class="text-xs text-wrap py-3" style="max-width: 250px; line-height: 1.4;">
                        <span class="font-weight-bold text-dark"><?php echo $b->judul_dda ?></span>
                    </td>
                    <td class="text-xs text-wrap" style="max-width: 250px; line-height: 1.4;">
                        <i class="bi bi-link me-1 text-info opacity-3 small"></i> 
                        <span class="text-secondary"><?php echo $b->judul_portal ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark font-weight-bold text-xs px-2"><?php echo $b->tahun_data ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge border <?php echo $status_class; ?> text-uppercase font-weight-bold" style="font-size: 9px; padding: 4px 8px;">
                            <?php echo $status_label ?>
                        </span>
                    </td>
                </tr>
            <?php 
                    }
                }
            ?>
            </tbody>
        </table>
    </div>
<?php
}
?>

<style>
    .text-sm { font-size: 0.875rem !important; }
    .text-md { font-size: 1rem !important; }
    .text-lg { font-size: 1.125rem !important; }
    .ls-1 { letter-spacing: 0.8px; }
    .text-xxs { font-size: 0.75rem !important; }
    .text-xs { font-size: 0.82rem !important; }
    .text-sm { font-size: 0.92rem !important; }
    .font-weight-bolder { font-weight: 800 !important; }
    
    .border-radius-2xl { border-radius: 1.25rem !important; }
    .border-radius-lg { border-radius: 0.6rem !important; }
    .border-radius-md { border-radius: 0.4rem !important; }
    
    .bg-gray-100 { background-color: #f8f9fa !important; }
    .bg-primary-soft { background-color: rgba(255, 109, 31, 0.08) !important; color: #FF6D1F !important; }
    .bg-danger-soft { background-color: rgba(234, 6, 6, 0.08) !important; color: #ea0606 !important; }
    .bg-success-soft { background-color: rgba(45, 206, 137, 0.08) !important; color: #2dce89 !important; }
    .bg-info-soft { background-color: rgba(17, 205, 239, 0.08) !important; color: #11cdef !important; }
    
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    .border-danger-soft { border: 1px solid rgba(234, 6, 6, 0.15) !important; }
    .border-success-soft { border: 1px solid rgba(45, 206, 137, 0.15) !important; }
    .border-info-soft { border: 1px solid rgba(17, 205, 239, 0.15) !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.95rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .table th { 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.8px;
        font-size: 0.85rem !important;
        color: #344767 !important;
        background-color: #f8f9fa !important;
    }
    
    .table td { 
        vertical-align: middle !important;
        padding-top: 1.25rem !important;
        padding-bottom: 1.25rem !important;
        font-size: 1rem !important;
        color: #444 !important;
    }
    
    .progress-wrapper {
        min-width: 130px;
    }
    
    .badge {
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        padding: 0.55em 1em !important;
        border-radius: 6px !important;
    }
    .bg-gray-100 { background-color: #f8f9fa !important; }
    .divide-y > * + * { border-top-width: 1px; }
    .divide-gray-100 > * + * { border-color: #f1f1f1; }
    .hover\:bg-gray-50:hover { background-color: #fafafa; }
    .transition-colors { transition: background-color 0.2s ease; }
    
    .bg-danger-soft { background-color: rgba(234, 6, 6, 0.05) !important; }
    .bg-warning-soft { background-color: rgba(251, 207, 51, 0.1) !important; }
    .bg-success-soft { background-color: rgba(45, 206, 137, 0.1) !important; }
    
    .progress-bar.bg-info { background-color: #11cdef !important; }
    .progress-bar.bg-danger { background: linear-gradient(310deg, #ea0606 0%, #ff667c 100%) !important; }
    .progress-bar.bg-warning { background: linear-gradient(310deg, #fbcf33 0%, #fbe07d 100%) !important; }
    .progress-bar.bg-success { background: linear-gradient(310deg, #2dce89 0%, #2dcecc 100%) !important; }
    
    .badge {
        font-weight: 500;
        letter-spacing: 0.02em;
        padding: 0.35em 0.65em;
    }
    .border-radius-lg { border-radius: 0.5rem; }
    .opacity-3 { opacity: 0.3; }
    .opacity-5 { opacity: 0.5; }
</style>
