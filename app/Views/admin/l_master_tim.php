<?php echo session()->getFlashdata("k"); ?>

<!-- Consolidated Control Card -->
<div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
    <div class="card-header pb-3 pt-3 px-4 bg-white border-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon icon-shape bg-primary-orange shadow-primary text-center border-radius-md d-flex align-items-center justify-content-center me-1" style="width: 42px; height: 42px;">
                        <i class="bi bi-people-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 font-weight-bolder text-dark">Master Tim</h4>
                        <p class="text-xs text-secondary mb-0">Kelola daftar anggota tim dan kode akses operasional</p>
                    </div>
                    <button type="button" class="btn bg-primary-orange text-white btn-sm px-4 border-radius-lg mb-0 shadow-none ms-2" data-bs-toggle="modal" data-bs-target="#modalTambahTim">
                        <i class="bi bi-person-plus me-1"></i> Tambah Tim
                    </button>
                </div>
            </div>
            <div class="col-lg-5">
                <form method="post" action="<?php echo base_url(); ?>index.php/admin/master_tim/cari" onsubmit="return false;">
                    <div class="input-group input-group-sm input-group-alternative border-radius-lg border shadow-none px-2 py-1" style="background: #f8f9fa;">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="inputSearchTim" class="form-control bg-transparent border-0 ps-0 text-sm" name="q" placeholder="Cari nama anggota atau NIP..." style="box-shadow: none;">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body px-4 pt-0 pb-4">
        <div class="table-responsive rounded-3 border border-light overflow-hidden">
            <table class="table table-hover align-items-center mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        <th width="60" class="text-center py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">No</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1 ps-4">Informasi Anggota Tim</th>
                        <th width="200" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Nomor Induk (NIP)</th>
                        <th width="180" class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ls-1">Kode Akses</th>
                    </tr>
                </thead>
                <tbody id="tableTimBody" class="divide-y divide-gray-100 bg-white">
                    <?php 
                    if (empty($data)) {
                        echo "<tr><td colspan='4' class='text-center py-5 text-secondary font-weight-bold opacity-5'><i class='bi bi-inbox fs-2 d-block mb-2'></i>Belum ada data tim operasional</td></tr>";
                    } else {
                        $no = (isset($offset) ? $offset : 0) + 1;
                        foreach ($data as $b) {
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="text-center font-weight-bold">
                            <span class="text-secondary text-sm"><?php echo $no; ?></span>
                        </td>
                        <td class="py-3 ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-gray-100 border-radius-md me-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="bi bi-person text-primary-orange"></i>
                                </div>
                                <h6 class="mb-0 text-sm font-weight-bold text-dark lh-sm"><?php echo strtoupper($b->nama); ?></h6>
                            </div>
                        </td>
                        <td>
                            <span class="text-sm text-secondary font-weight-bold"><?php echo $b->nip ?: '-'; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm bg-primary-soft text-primary-orange border border-primary-soft font-weight-bold px-3 py-2 border-radius-md">
                                <i class="bi bi-shield-lock me-1"></i><?php echo $b->username; ?>
                            </span>
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
        
        <?php if(!empty($pagi)): ?>
        <div class="mt-4">
            <?php echo $pagi; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL TAMBAH TIM (Modern BS5) -->
<div class="modal fade" id="modalTambahTim" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg border-radius-2xl overflow-hidden">
            <div class="modal-header bg-white border-0 pt-4 px-4 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon icon-shape bg-primary-orange shadow-primary text-center border-radius-md d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-plus text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bolder text-dark">Tambah Anggota</h5>
                    </div>
                </div>
                <button type="button" class="btn-close text-dark shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?php echo base_url(); ?>index.php/admin/master_tim/act_add" method="post">
                <div class="modal-body p-4">
                    <p class="text-xs text-secondary mb-4 ps-1">Silakan lengkapi biodata anggota tim baru di bawah ini.</p>
                    
                    <div class="mb-3">
                        <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-1 ps-1 ls-1">Nama Lengkap</label>
                        <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                            <span class="input-group-text bg-white border-0"><i class="bi bi-person text-primary-orange"></i></span>
                            <input type="text" name="nama" required class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Masukkan nama (Tanpa Gelar)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-1 ps-1 ls-1">NIP (Nomor Induk Pegawai)</label>
                        <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                            <span class="input-group-text bg-white border-0"><i class="bi bi-upc-scan text-primary-orange"></i></span>
                            <input type="number" name="nip" required class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="19XXXXXXXXXXXXX">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-1 ps-1 ls-1">Kode Tim (Username)</label>
                        <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                            <span class="input-group-text bg-white border-0"><i class="bi bi-at text-primary-orange"></i></span>
                            <input type="text" name="username" required class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Contoh: tim_diseminasi">
                        </div>
                        <div class="form-text text-xxs text-secondary mt-2 ps-1 italic">
                            <i class="bi bi-info-circle me-1"></i> Username ini digunakan untuk kredensial login operasional.
                        </div>
                    </div>
                    
                    <input type="hidden" name="password" value="123456">
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between align-items-center mt-2">
                    <button type="button" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none font-weight-bold" data-bs-dismiss="modal">Batalkan</button>
                    <button type="submit" class="btn bg-primary-orange text-white btn-sm border-radius-lg px-4 mb-0 shadow-primary">
                        <i class="bi bi-save me-2"></i> Simpan Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
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
    .border-primary-soft { border: 1px solid rgba(255, 109, 31, 0.15) !important; }
    .text-primary-orange { color: #FF6D1F !important; }
    .bg-primary-orange { background-color: #FF6D1F !important; }
    .shadow-primary { box-shadow: 0 4px 6px rgba(255, 109, 31, 0.11), 0 1px 3px rgba(255, 109, 31, 0.08) !important; }

    .table td, .table th { border-color: #f1f1f1 !important; vertical-align: middle !important; font-size: 0.95rem !important; }
    .table thead th { border-bottom: 0 !important; font-size: 0.75rem !important; }
    
    .input-group-alternative {
        transition: all 0.2s ease;
        background-color: #fff;
    }
    .input-group-alternative:focus-within {
        border-color: #FF6D1F !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
    }
    
    .form-control:focus {
        box-shadow: none !important;
        font-size: 0.92rem !important;
    }

    .divide-y > * + * { border-top-width: 1px; }
    .divide-gray-100 > * + * { border-color: #f1f1f1; }
</style>

<script type="text/javascript">
$(document).ready(function () {
    // Real-time Client-side Search (Automatic)
    $("#inputSearchTim").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tableTimBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        
        // Show empty state
        var visibleRows = $("#tableTimBody tr:visible").length;
        if (visibleRows === 0) {
            if ($("#emptySearchState").length === 0) {
                $("#tableTimBody").append('<tr id="emptySearchState"><td colspan="4" class="text-center py-5 text-secondary font-weight-bold opacity-5"><i class="bi bi-search fs-2 d-block mb-2"></i>Tidak ada tim yang cocok</td></tr>');
            }
        } else {
            $("#emptySearchState").remove();
        }
    });
});
</script>
