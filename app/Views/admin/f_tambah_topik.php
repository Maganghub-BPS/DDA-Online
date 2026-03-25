<?php
$id_unitkerja = service('request')->getGet('id');

$db = \Config\Database::connect();
$query_unitkerja = $db->query("SELECT * from m_unitkerja where id_unitkerja='$id_unitkerja' LIMIT 1")->getRow();
$nama_opd = $query_unitkerja ? $query_unitkerja->unitkerja_ind : 'Forum OPD';
?>

<style>
    .card-premium {
        border-radius: 20px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .btn-orange-gradient {
        background: linear-gradient(135deg, #FF6D1F 0%, #ff8c42 100%);
        border: none;
        color: white;
        font-weight: 700;
        letter-spacing: 0.02em;
        transition: all 0.2s ease;
    }

    .btn-orange-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 109, 31, 0.2);
        color: white;
    }

    .opd-badge-container {
        background-color: #fffaf7;
        border: 1px dashed rgba(255, 109, 31, 0.15);
        border-radius: 12px;
    }

    .form-label-premium {
        font-size: 0.7rem;
        font-weight: 800;
        color: #adb5bd;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 8px;
        display: block;
    }

    .textarea-premium {
        border-radius: 12px;
        border: 2px solid #f1f3f5;
        background-color: #fafbfc;
        padding: 1rem;
        font-weight: 500;
        color: #343a40;
        transition: all 0.2s ease;
        line-height: 1.5;
    }

    .textarea-premium:focus {
        border-color: #FF6D1F;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(255, 109, 31, 0.05);
        outline: none;
    }

    .btn-pill-cancel {
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 600;
        border-radius: 50rem;
    }
</style>

<div class="row justify-content-center pt-2">
    <div class="col-lg-6 col-md-8">
        <div class="card card-premium shadow-lg border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-light">
                    <div class="bg-warning-subtle text-warning d-flex align-items-center justify-content-center rounded-3 me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-chat-left-text-fill" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0" style="letter-spacing: -0.03em;">Mulai Diskusi Baru</h4>
                        <p class="text-muted small mb-0">Bagikan ide atau bahas data secara internal</p>
                    </div>
                </div>

                <form action="<?php echo base_URL() ?>index.php/admin/forum_diskusi/act_add_topik" method="post">
                    <input type="hidden" name="id_unitkerja" value="<?php echo $id_unitkerja; ?>">

                    <div class="opd-badge-container p-3 mb-4">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-white shadow-sm text-primary px-2 py-1 me-3 border border-light" style="font-size: 10px; font-weight: 800;">
                                <i class="bi bi-building-fill me-1"></i> INSTANSI
                            </span>
                            <h6 class="text-dark fw-bold mb-0" style="font-size: 0.9rem; color: #FF6D1F !important; line-height: 1.2;"><?php echo $nama_opd; ?></h6>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-premium">Topik Bahasan</label>
                        <textarea name="nama_topik" id="nama_topik" class="form-control textarea-premium" rows="4" required tabindex="1" placeholder="Masukkan judul topik diskusi..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-3">
                        <a href="javascript:history.back()" class="btn btn-sm btn-pill-cancel px-4 py-2" tabindex="3">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-orange-gradient px-4 py-2 rounded-pill shadow-sm" style="font-size: 0.9rem;" tabindex="2">
                            <i class="bi bi-send-fill me-2"></i> Tambahkan Topik
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="text-center mt-3">
            <span class="text-muted" style="font-size: 0.7rem;">
                <i class="bi bi-lock-fill me-1"></i> Forum diskusi privat untuk modul DDA.
            </span>
        </div>
    </div>
</div>