<style>
    .page-header {
        position: relative;
        background-image: url('<?= base_url('aset/images/Candi%20Borobudur.jpeg') ?>');
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        padding: 180px 0 155px 0;
        border-bottom: none;
        margin-bottom: 0;
        color: white;
        text-align: center;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .page-header::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(to bottom, rgba(15, 23, 42, 0.1) 0%, rgba(15, 23, 42, 0.7) 100%);
        z-index: 1;
    }
    
    .scene-container-search {
        position: absolute;
        top: 50%;
        width: 620px;
        height: 620px;
        perspective: 2000px;
        z-index: 1;
        pointer-events: none;
        opacity: 0.22;
        transition: opacity 0.3s ease;
    }
    .scene-container-search.scene-left {
        left: -80px;
        transform: translateY(-50%) scale(0.78);
    }
    .scene-container-search.scene-right {
        right: -80px;
        left: auto;
        transform: translateY(-50%) scale(0.78) scaleX(-1);
    }
    .scene-search { position: absolute; width: 100%; height: 100%; transform-style: preserve-3d; transform: rotateX(60deg) rotateZ(45deg); }
    .floor-search { position: absolute; width: 600px; height: 600px; top: 0; left: 0; background-image: linear-gradient(rgba(255,255,255,1) 2px, transparent 2px), linear-gradient(90deg, rgba(255,255,255,1) 2px, transparent 2px); background-size: 60px 60px; border-radius: 30px; transform: translateZ(-1px); opacity: 0.35; }
    .cube-search { position: absolute; width: 40px; height: 40px; transform-style: preserve-3d; }
    .cube-search .c-top { position: absolute; width: 40px; height: 40px; background: rgba(255,255,255,0.95); transform: translateZ(60px); border: 1px solid rgba(255,255,255,0.6); }
    .cube-search .c-front { position: absolute; width: 40px; height: 60px; background: rgba(255,255,255,0.65); transform-origin: bottom; transform: rotateX(-90deg) translateY(60px); bottom: 0; border: 1px solid rgba(255,255,255,0.6); }
    .cube-search .c-right { position: absolute; width: 60px; height: 40px; background: rgba(255,255,255,0.45); transform-origin: left; transform: rotateY(90deg) translateX(-60px); left: 0; border: 1px solid rgba(255,255,255,0.6); }
    
    .cube-search.tall .c-top { transform: translateZ(120px); }
    .cube-search.tall .c-front { height: 120px; transform: rotateX(-90deg) translateY(120px); }
    .cube-search.tall .c-right { width: 120px; transform: rotateY(90deg) translateX(-120px); }
    
    .cube-search.short .c-top { transform: translateZ(30px); }
    .cube-search.short .c-front { height: 30px; transform: rotateX(-90deg) translateY(30px); }
    .cube-search.short .c-right { width: 30px; transform: rotateY(90deg) translateX(-30px); }

    @media (max-width: 992px) {
        .page-header {
            padding: 110px 15px 70px;
        }
    }
    @media (max-width: 576px) {
        .page-header {
            padding: 90px 15px 45px;
        }
    }
    
    .page-title {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 800;
        font-size: clamp(1.8rem, 5vw, 2.8rem);
        margin-bottom: 12px;
        line-height: 1.2;
    }
    
    .page-subtitle {
        font-size: clamp(0.92rem, 2.5vw, 1.1rem);
        opacity: 0.9;
        max-width: 750px;
        margin: 0 auto;
        line-height: 1.5;
    }
    
    .opd-card {
        display: flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        text-decoration: none;
        color: #1e293b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .opd-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }

    .opd-icon {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: rgba(242, 101, 34, 0.1);
        color: var(--bps-orange);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
        margin-right: 18px;
    }

    .opd-content {
        flex: 1;
        min-width: 0;
    }

    .opd-name {
        margin: 0 0 6px 0;
        font-weight: 700;
        font-size: 1.05rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #0f172a;
    }

    .opd-count {
        font-size: 0.85rem;
        color: #64748b;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-block;
        font-weight: 600;
    }
    
    .highlight-orange { color: var(--bps-orange); }

    .filter-bar-horizontal {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        margin-bottom: 40px;
        margin-top: -38px;
        position: relative;
        z-index: 10;
        align-items: center;
    }

    .fb-group {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 15px;
        flex: 1;
        min-width: 200px;
        transition: all 0.2s;
    }

    .fb-group:focus-within {
        border-color: var(--bps-blue);
        box-shadow: 0 0 0 3px rgba(21, 70, 121, 0.1);
    }

    .fb-group i {
        color: #64748b;
        margin-right: 12px;
    }

    .fb-group input {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        font-size: 1rem;
        color: #0f172a;
    }

    .btn-search-bar {
        background: var(--bps-blue);
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 50px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
        transition: all 0.2s ease;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(21, 70, 121, 0.2);
        cursor: pointer;
    }

    .btn-search-bar:hover {
        background: var(--bps-blue-light, #1e4a7d);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(21, 70, 121, 0.25);
    }

    .btn-search-bar:focus,
    .btn-search-bar:active {
        background: var(--bps-blue);
        color: white;
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(21, 70, 121, 0.2);
        outline: none;
    }
</style>

<div class="page-header text-center">
    <div class="container hero-container position-relative" style="z-index: 2;">
        <h1 class="hero-title mx-auto mb-4" style="font-size: clamp(3rem, 6vw, 5rem); font-weight: 800; letter-spacing: -0.04em; line-height: 1.1; max-width: 1000px; color: #ffffff; text-shadow: 0 10px 30px rgba(0,0,0,0.5); font-family: 'Inter', "Inter Fallback", sans-serif;">
            Jelajah <br>
            <span style="color: var(--bps-orange);">Instansi</span>
        </h1>
        <p class="hero-subtitle mx-auto mb-5" style="font-size: clamp(1.1rem, 2vw, 1.3rem); color: rgba(255,255,255,0.9); max-width: 800px; line-height: 1.6; text-shadow: 0 4px 10px rgba(0,0,0,0.5);">
            Telusuri dan temukan seluruh data statistik sektoral berdasarkan Organisasi Perangkat Daerah (OPD) terkait.
        </p>
    </div>
</div>

<div class="container pb-5 mb-5">
    
    <!-- Filter Bar -->
    <form action="<?= base_url('home/instansi') ?>" method="GET">
        <div class="filter-bar-horizontal">
            <div class="fb-group">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Cari nama instansi atau OPD...">
            </div>
            <button type="submit" class="btn-search-bar">
                Cari
            </button>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h6 class="text-muted mb-0">
            Ditemukan <span class="fw-bold" style="color: var(--bps-blue);"><?= count($opd_list) ?></span> instansi
        </h6>
    </div>

    <div class="row g-4">
        <?php if (!empty($opd_list)): ?>
            <?php foreach($opd_list as $opd): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="<?= base_url('home/search?opd_search=' . urlencode($opd->unitkerja_ind)) ?>" class="opd-card">
                        <div class="opd-icon"><i class="fa-solid fa-building-user"></i></div>
                        <div class="opd-content">
                            <h6 class="opd-name" title="<?= htmlspecialchars($opd->unitkerja_ind) ?>">
                                <?= htmlspecialchars($opd->unitkerja_ind) ?>
                            </h6>
                            <span class="opd-count"><?= $opd->total_tabel ?> Tabel Data</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5">
                <div class="mb-3" style="font-size: 3rem; color: #cbd5e1;"><i class="fa-regular fa-folder-open"></i></div>
                <h5>Belum ada data Instansi.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>
