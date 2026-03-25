<?php
$ta = session()->get('admin_ta');
if (empty($ta)) $ta = date('Y');
$jenis_rekap = $jenis_rekap ?? service('request')->getPost('jenis_rekap') ?? '0';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg border-radius-2xl mb-4 overflow-hidden">
                <div class="card-header pb-3 pt-4 px-4 bg-white border-0">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-4">
                            <h4 class="mb-1 font-weight-bolder text-dark">Laporan Rekap</h4>
                            <p class="text-sm text-secondary mb-0">
                                <i class="bi bi-calendar-event me-1 opacity-7"></i> Tahun Anggaran <?php echo $ta; ?>
                            </p>
                        </div>

                        <div class="col-lg-8">
                            <form id="formRekap" method="post" action="<?php echo site_url('admin/report'); ?>">
                                <div class="d-flex flex-wrap justify-content-lg-end align-items-center gap-3">
                                    <!-- Jenis Rekap Dropdown -->
                                    <div class="input-group input-group-sm input-group-alternative border-radius-lg overflow-hidden shadow-none border" style="max-width: 320px; border-color: #e9ecef !important;">
                                        <span class="input-group-text bg-white border-0"><i class="bi bi-filter text-primary-orange"></i></span>
                                        <select name="jenis_rekap" onchange="this.form.submit()" class="form-select bg-white border-0 text-dark font-weight-bold" style="cursor: pointer; font-size: 0.9rem; padding: 0.5rem 1rem;">
                                            <?php
                                            $l_jenis_rekap = array('Berdasarkan Instansi', 'Berdasarkan Tim', 'Progress Portal');
                                            foreach ($l_jenis_rekap as $i => $label) {
                                                $selected = ($jenis_rekap == $i) ? 'selected' : '';
                                                echo "<option value='$i' $selected>$label</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Tombol Kembali -->
                                    <a href="<?php echo site_url('admin/'); ?>" class="btn btn-sm bg-gradient-light border-radius-lg px-3 mb-0 hover-translate-y d-flex align-items-center" style="height: 38px;">
                                        <i class="bi bi-arrow-left me-1"></i> Kembali
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <hr class="horizontal dark my-0">

                <div class="card-body px-4 pt-4 pb-0">
                    <?php echo session()->getFlashdata("k"); ?>

                    <div class="report-container">
                        <?php require "view_report.php"; ?>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-orange: #FF6D1F;
        --secondary-orange: #ff8f52;
    }

    .text-primary-orange {
        color: var(--primary-orange) !important;
    }

    .bg-primary-orange {
        background-color: var(--primary-orange) !important;
    }

    .card-header {
        background: #fff !important;
    }

    .border-radius-2xl {
        border-radius: 1.25rem !important;
    }

    .border-radius-lg {
        border-radius: 0.6rem !important;
    }

    .font-weight-bolder {
        font-weight: 800 !important;
    }

    .input-group-alternative {
        background-color: #fff;
        transition: box-shadow .15s ease;
    }

    .input-group-alternative:focus-within {
        box-shadow: 0 4px 6px rgba(50, 50, 93, .1), 0 1px 3px rgba(0, 0, 0, .08);
    }

    .form-select:focus {
        box-shadow: none;
    }

    .hover-translate-y {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hover-translate-y:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .bg-gradient-light {
        background: linear-gradient(310deg, #fbfbfb 0%, #ffffff 100%);
        border: 1px solid #e9ecef;
    }

    .report-container {
        min-height: 480px;
        animation: fadeIn 0.4s ease-out;
    }

    .horizontal.dark {
        background-image: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 991.98px) {
        .justify-content-lg-end {
            justify-content: flex-start !important;
        }
    }
</style>