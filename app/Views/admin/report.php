<?php
$ta = session()->get('admin_ta');
if (empty($ta)) $ta = date('Y');
$jenis_rekap = $jenis_rekap ?? service('request')->getPost('jenis_rekap') ?? '0';
?>

<style>
    /* Pastikan flexbox container luar tidak mengompres kartu Laporan Rekap */
    .main-content {
        min-height: auto !important;
        padding-bottom: 12px !important;
    }
    .content-body {
        min-height: auto !important;
        flex: 1 0 auto !important;
    }
    #mainReportCard {
        height: calc(100vh - 82px) !important;
        min-height: 580px !important;
        flex-shrink: 0 !important;
    }

    /* Kontainer scroll internal Laporan Rekap */
    .table-scroll-container {
        height: 100%;
        min-height: 0;
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden !important;
    }

    /* Badge Portal Status Kompak & Bebas Overflow */
    .badge-portal-status {
        font-size: 0.72rem !important;
        padding: 3px 8px !important;
        white-space: nowrap !important;
        display: inline-block;
        border-radius: 6px !important;
        letter-spacing: 0.3px;
    }

    /* Custom Slim Scrollbar 6px */
    .table-scroll-container::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
        border-radius: 4px;
    }

    /* Sticky Table Header */
    .table-scroll-container thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8fafc !important;
        box-shadow: inset 0 -2px 0 #e2e8f0;
        vertical-align: middle !important;
    }

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
        min-height: 0;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
    }

    .horizontal.dark {
        background-image: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
    }

    @media (max-width: 991.98px) {
        .justify-content-lg-end {
            justify-content: flex-start !important;
        }
    }
</style>

<div class="card border-0 shadow-sm border-radius-2xl mb-0" id="mainReportCard" style="height: calc(100vh - 82px); min-height: 580px; display: flex; flex-direction: column; overflow: hidden; flex-shrink: 0;">
    <div class="card-header pb-2 pt-3 px-4 bg-white border-0 flex-shrink-0">
        <div class="row align-items-center g-3">
            <div class="col-lg-4">
                <h4 class="mb-1 font-weight-bolder text-dark">Laporan Rekap</h4>
                <p class="text-sm text-secondary mb-0">
                    <i class="bi bi-calendar-event me-1 opacity-7"></i> DDA Tahun <?php echo $ta; ?>
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
                    </div>
                </form>
            </div>
        </div>
    </div>

    <hr class="horizontal dark my-0 flex-shrink-0">

    <div class="card-body px-3 px-md-4 pt-3 pb-3 flex-grow-1 d-flex flex-column" style="min-height: 0; overflow: visible;">
        <?php echo session()->getFlashdata("k"); ?>

        <div class="report-container flex-grow-1 d-flex flex-column" style="min-height: 0;">
            <?php require "view_report.php"; ?>
        </div>
    </div>
</div>