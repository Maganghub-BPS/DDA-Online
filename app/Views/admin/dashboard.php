<?php
// Dashboard monitoring - Bootstrap 5 version
?>

<!-- Flash Message -->
<?php if(session()->getFlashdata("k")): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert" id="alert">
    <i class="bi bi-info-circle me-2"></i>
    <?php echo session()->getFlashdata("k"); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Info Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-primary text-white p-3 h-100 position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1">100%</h3>
                    <p class="small mb-0 opacity-75">Tabel Terisi</p>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-table" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm text-white p-3 h-100 position-relative overflow-hidden" style="background: #22c55e;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1">24</h3>
                    <p class="small mb-0 opacity-75">OPD Aktif</p>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-building" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm text-white p-3 h-100 position-relative overflow-hidden" style="background: #eab308;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1">12</h3>
                    <p class="small mb-0 opacity-75">Pemeriksaan BPS</p>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-clipboard-check" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm text-white p-3 h-100 position-relative overflow-hidden" style="background: #3b82f6;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-1">5</h3>
                    <p class="small mb-0 opacity-75">Komentar Baru</p>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(255,255,255,0.15);">
                    <i class="bi bi-chat-dots" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monitoring Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold"><i class="bi bi-activity me-2 text-primary"></i>Monitoring Kegiatan Harian</h5>
            <form class="d-flex" method="post" action="<?php echo base_URL(); ?>index.php/admin/kontrak/cari">
                <div class="input-group input-group-sm" style="width: 260px;">
                    <input type="text" class="form-control" name="q" placeholder="Cari kegiatan..." required>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No.</th>
                        <th width="25%">Uraian Kegiatan</th>
                        <th width="12%" class="text-center">Rekanan</th>
                        <th width="12%" class="text-center">Nilai</th>
                        <th width="12%" class="text-center">Tanggal SPK</th>
                        <th width="34%">Status / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='6' class='text-center py-4 text-muted'><i class='bi bi-inbox me-2'></i>Data tidak ditemukan</td></tr>";
                    } else {
                        $no = 1;
                        foreach ($data as $b) {
                            $hariini = date('Y-m-d');
                            $tglhariini = new DateTime($hariini);
                            $tglbast = new DateTime($b->tglbastdeadline);
                            $tgladk = new DateTime($b->tgladkdeadline);
                            $tglspm = new DateTime($b->tglspmdeadline);
                            
                            $badges = [];
                            
                            // BASTP
                            if(strtotime($b->tglbastdeadline) < strtotime($hariini) && $b->bastp == '0000-00-00') {
                                $badges[] = '<span class="badge bg-danger-subtle text-danger">BASTP Lewat</span>';
                            } else if(strtotime($b->tglbastdeadline) > strtotime($hariini) && $b->bastp == '0000-00-00') {
                                $selisih = $tglhariini->diff($tglbast);
                                $badges[] = '<span class="badge bg-primary-subtle text-primary">BASTP: '.$selisih->format('%a hari').'</span>';
                            }
                            
                            // ADK
                            if(strtotime($b->tgladkdeadline) < strtotime($hariini) && $b->tgl_daftar_adk == '0000-00-00') {
                                $badges[] = '<span class="badge bg-danger-subtle text-danger">ADK Lewat</span>';
                            } else if(strtotime($b->tgladkdeadline) > strtotime($hariini) && $b->tgl_daftar_adk == '0000-00-00') {
                                $selisih = $tglhariini->diff($tgladk);
                                $badges[] = '<span class="badge bg-info-subtle text-info">ADK: '.$selisih->format('%a hari').'</span>';
                            }
                            
                            // SPM
                            if(strtotime($b->tglspmdeadline) < strtotime($hariini) && $b->tgl_maju_spm == '0000-00-00') {
                                $badges[] = '<span class="badge bg-danger-subtle text-danger">SPM Lewat</span>';
                            } else if(strtotime($b->tglspmdeadline) > strtotime($hariini) && $b->tgl_maju_spm == '0000-00-00') {
                                $selisih = $tglhariini->diff($tglspm);
                                $badges[] = '<span class="badge bg-success-subtle text-success">SPM: '.$selisih->format('%a hari').'</span>';
                            }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no; ?></td>
                        <td><?php echo $b->uraian_kontrak; ?></td>
                        <td class="text-center"><?php echo $b->rekanan; ?></td>
                        <td class="text-center"><?php echo $b->nilai_bruto; ?></td>
                        <td class="text-center"><?php echo tgl_jam_sql($b->tgl_spk); ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <?php echo implode(' ', $badges); ?>
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
        </div>
    </div>
</div>
