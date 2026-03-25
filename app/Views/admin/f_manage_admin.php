<?php
$mode = segment_safe(3);

if ($mode == "edt" || $mode == "act_edt") {
    $act        = "act_edt";
    $idp        = $datpil->id;
    $username   = $datpil->username;
    $password   = "-";
    $nama       = $datpil->nama;
    $nip        = $datpil->nip;
    $level      = $datpil->level;
    $idunitkerja = $datpil->id_unitkerja;
    $email      = $datpil->email;

    $db = \Config\Database::connect();
    $query_unitkerja = $db->query("SELECT * from m_unitkerja where id_unitkerja='$idunitkerja' LIMIT 1")->getRow();
    if ($query_unitkerja) {
        $idunitkerja_terpilih = $query_unitkerja->id_unitkerja;
        $unitkerja_terpilih = $query_unitkerja->unitkerja_ind;
    } else {
        $idunitkerja_terpilih = $idunitkerja;
        $unitkerja_terpilih = "Instansi Tidak Ditemukan ($idunitkerja)";
    }
} else {
    $act        = "act_add";
    $idp        = "";
    $username   = "";
    $password   = "";
    $nama       = "";
    $nip        = "";
    $level      = "";
    $idunitkerja = "";
    $email      = "";
}
?>

<div class="container-fluid py-2 px-3">
    <div class="row justify-content-center">
        <div class="col-lg-11 col-xl-10">
            <div class="card border-0 shadow-lg border-radius-2xl overflow-hidden">
                <!-- Premium Header -->
                <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="icon icon-shape bg-primary-orange shadow-primary text-center border-radius-md me-3" style="width: 42px; height: 42px;">
                            <i class="bi bi-person-gear text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bolder text-dark">Manajemen Pengguna</h5>
                            <p class="text-xs text-secondary mb-0"><?php echo ($mode == "edt") ? "Perbarui kredensial akun" : "Daftarkan akun administrator baru"; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4 pt-3">
                    <form action="<?php echo base_URL(); ?>index.php/admin/manage_admin/<?php echo $act; ?>" method="post" accept-charset="utf-8">
                        <input type="hidden" name="idp" value="<?php echo $idp; ?>">

                        <div class="row g-3">
                            <!-- Left Column: Security Settings -->
                            <div class="col-md-6 border-end border-light pe-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-2 ls-1">Pengaturan Keamanan</label>

                                <div class="mb-2">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Username Login</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-at text-primary-orange"></i></span>
                                        <input type="text" name="username" required value="<?php echo $username; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Username unik" tabindex="1" autofocus>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Password</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-key text-primary-orange"></i></span>
                                                <input type="password" name="password" required value="<?php echo $password; ?>"
                                                    class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="••••••••" tabindex="2">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Confirm Password</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                                <span class="input-group-text bg-white border-0"><i class="bi bi-shield-check text-primary-orange"></i></span>
                                                <input type="password" name="password2" required value="<?php echo $password; ?>"
                                                    class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="••••••••" tabindex="3">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($mode == 'edt'): ?>
                                    <div class="alert bg-gray-50 border-0 py-2 px-3 border-radius-lg mb-3">
                                        <span class="text-xxs text-secondary"><i class="bi bi-info-circle-fill text-warning me-1"></i>Biarkan <strong>'-'</strong> jika tidak ingin mengubah password lama.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-0">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Email Korespondensi</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-envelope text-primary-orange"></i></span>
                                        <input type="email" name="email" required value="<?php echo $email; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="admin@jatengprov.go.id" tabindex="4">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Identity & Access -->
                            <div class="col-md-6 ps-lg-4">
                                <label class="form-label text-uppercase text-xxs font-weight-bolder text-secondary mb-3 ls-1">Identitas & Otoritas</label>

                                <div class="mb-3">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Nama Lengkap</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-person text-primary-orange"></i></span>
                                        <input type="text" name="nama" required value="<?php echo $nama; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="Nama lengkap beserta gelar" tabindex="5">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">N I P</label>
                                    <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-upc text-primary-orange"></i></span>
                                        <input type="text" name="nip" required value="<?php echo $nip; ?>"
                                            class="form-control border-0 py-2 ps-1 text-sm bg-white" placeholder="19XXXXXXXXXXXXXX" tabindex="6">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-7">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Asal Instansi</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none bg-white">
                                                <select name="unitkerja" id="unitkerja" class="form-select border-0 py-2 text-sm" tabindex="8" required>
                                                    <?php
                                                    $db = \Config\Database::connect();
                                                    $unitkerja_data = $db->query("select * from m_unitkerja order by unitkerja_ind")->getResultArray();

                                                    if ($mode == "edt" || $mode == "act_edt") {
                                                        echo "<option selected value='" . $idunitkerja_terpilih . "'>" . $unitkerja_terpilih . "</option>";
                                                        foreach ($unitkerja_data as $p) {
                                                            if ($p['id_unitkerja'] != $idunitkerja_terpilih) {
                                                                echo "<option value='" . $p['id_unitkerja'] . "'>" . $p['unitkerja_ind'] . "</option>";
                                                            }
                                                        }
                                                    } else {
                                                        echo "<option selected value=''>- Pilih Instansi -</option>";
                                                        foreach ($unitkerja_data as $p) {
                                                            echo "<option value='" . $p['id_unitkerja'] . "'>" . $p['unitkerja_ind'] . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="mb-3">
                                            <label class="form-label text-xs fw-bold text-dark ps-1 mb-1">Level Akses</label>
                                            <div class="input-group input-group-alternative border-radius-lg border border-gray-100 overflow-hidden shadow-none bg-white">
                                                <select name="level" class="form-select border-0 py-2 text-sm fw-bold text-primary-orange" required tabindex="7">
                                                    <option value="">- Level -</option>
                                                    <?php
                                                    $l_sifat = array('Super Admin', 'Admin', 'spv', 'lo', 'walidata');
                                                    foreach ($l_sifat as $lvl) {
                                                        $selected = ($lvl == $level) ? "selected" : "";
                                                        echo "<option value='" . $lvl . "' " . $selected . ">" . strtoupper($lvl) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Footer Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                            <a href="<?php echo base_URL(); ?>index.php/admin/manage_admin" class="btn btn-link text-secondary text-sm mb-0 px-0 shadow-none">
                                <i class="bi bi-arrow-left me-1"></i> Batalkan
                            </a>
                            <button type="submit" class="btn bg-primary-orange text-white btn-sm border-radius-lg px-5 mb-0 shadow-primary transition-all hover:scale-105" tabindex="9">
                                <i class="bi bi-shield-check me-2"></i> <?php echo ($mode == "edt") ? "Simpan Perubahan" : "Daftarkan Pengguna"; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .ls-1 {
        letter-spacing: 0.8px;
    }

    .text-xxs {
        font-size: 0.75rem !important;
    }

    .text-xs {
        font-size: 0.82rem !important;
    }

    .text-sm {
        font-size: 0.875rem !important;
    }

    .font-weight-bolder {
        font-weight: 800 !important;
    }

    .border-radius-2xl {
        border-radius: 1.25rem !important;
    }

    .border-radius-lg {
        border-radius: 0.6rem !important;
    }

    .border-radius-md {
        border-radius: 0.45rem !important;
    }

    .bg-gray-50 {
        background-color: #fcfcfc !important;
    }

    .bg-gray-100 {
        background-color: #f8f9fa !important;
    }

    .text-primary-orange {
        color: #FF6D1F !important;
    }

    .bg-primary-orange {
        background-color: #FF6D1F !important;
    }

    .shadow-primary {
        box-shadow: 0 4px 6px rgba(255, 109, 31, 0.11), 0 1px 3px rgba(255, 109, 31, 0.08) !important;
    }

    .icon-shape {
        width: 46px;
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .input-group-alternative {
        transition: all 0.2s ease;
        background-color: #fff;
    }

    .input-group-alternative:focus-within {
        border-color: #FF6D1F !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
    }

    .form-control:focus,
    .form-select:focus {
        box-shadow: none !important;
        font-size: 0.92rem !important;
    }

    .hover\:scale-105:hover {
        transform: scale(1.02);
    }

    .transition-all {
        transition: all 0.25s ease;
    }

    @media (min-width: 768px) {
        .border-end {
            border-right: 1px solid #f1f1f1 !important;
        }
    }
</style>