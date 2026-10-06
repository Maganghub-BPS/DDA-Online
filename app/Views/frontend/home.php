<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 Overrides */
    .select2-container {
        flex: 1;
        width: 100% !important;
        text-align: left !important;
    }
    .input-group .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
    }
    .select2-container--default .select2-selection--single {
        border: none !important;
        background: transparent !important;
        height: auto !important;
        display: flex !important;
        align-items: center !important;
        text-align: left !important;
        cursor: pointer !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .select2-container--default .select2-selection--single:focus {
        outline: none !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #0f172a !important;
        padding: 8px 0 !important;
        line-height: normal !important;
        font-size: 0.95rem !important;
        font-family: 'Inter', "Inter Fallback", sans-serif !important;
        font-weight: 600 !important;
        text-align: left !important;
        display: block !important;
        width: 100% !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Dropdown Options List - Clean, Soft, No Over-Animation */
    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
        margin-top: 6px !important;
        background: #ffffff !important;
        overflow: hidden !important;
        z-index: 9999 !important;
    }
    .select2-search--dropdown {
        padding: 8px 10px !important;
        background: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .select2-search--dropdown .select2-search__field {
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 7px 12px !important;
        font-size: 0.9rem !important;
        font-family: 'Inter', "Inter Fallback", sans-serif !important;
        color: #0f172a !important;
        outline: none !important;
        box-shadow: none !important;
        background: #f8fafc !important;
        transition: border-color 0.15s ease, background-color 0.15s ease !important;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: var(--bps-blue) !important;
        background: #ffffff !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .select2-results__options {
        max-height: 280px !important;
        padding: 4px 0 !important;
    }
    .select2-results__option {
        padding: 10px 18px !important;
        font-size: 0.92rem !important;
        font-family: 'Inter', "Inter Fallback", sans-serif !important;
        color: #334155 !important;
        font-weight: 500 !important;
        line-height: 1.4 !important;
        border-bottom: 1px solid #f8fafc !important;
        border-left: none !important;
        transition: background-color 0.12s ease, color 0.12s ease !important;
    }
    .select2-results__option:last-child {
        border-bottom: none !important;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        border-left: none !important;
    }
    .select2-container--default .select2-results__option--selected {
        background-color: #edf4fc !important;
        color: var(--bps-blue) !important;
        font-weight: 600 !important;
        border-left: none !important;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selected {
        background-color: #e2e8f0 !important;
        color: var(--bps-blue) !important;
        border-left: none !important;
    }



    /* 3D Isometric Video-like Hero Section */
    .hero-airy {
        position: relative;
        padding: 80px 0 120px;
        background: var(--bps-blue);
        display: flex;
        align-items: center;
        min-height: 100vh; /* Changed from 70vh to 100vh */
        overflow: hidden;
    }

    .hero-content-left {
        position: relative;
        z-index: 10;
        padding-right: 40px;
    }

    .hero-title-airy {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 3.8rem;
        font-weight: 800;
        color: white;
        line-height: 1.15;
        letter-spacing: -1.5px;
        margin-bottom: 20px;
    }

    .hero-title-airy span {
        color: var(--bps-orange);
    }

    .hero-desc-airy {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.7;
        margin-bottom: 35px;
        max-width: 550px;
    }

    /* ===== BENTO STAT CARDS & STATS OVERVIEW SECTION ===== */
    .stats-overview-section {
        padding: 55px 0 40px;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        position: relative;
        z-index: 10;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    }

    .stats-pill-badge {
        display: inline-flex;
        align-items: center;
        background: rgba(21, 70, 121, 0.08);
        color: var(--bps-blue);
        font-size: 0.82rem;
        font-weight: 700;
        padding: 5px 16px;
        border-radius: 50px;
        border: 1px solid rgba(21, 70, 121, 0.14);
        letter-spacing: 0.3px;
        margin-bottom: 12px;
    }

    .stats-section-title {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.03em;
        margin-bottom: 8px;
    }

    .stats-section-subtitle {
        font-size: 0.95rem;
        color: #64748b;
        max-width: 620px;
        margin: 0 auto;
        line-height: 1.6;
    }

    /* Clean, Modern 2-Card Metric Section (Simple & Authentic) */
    .stats-cards-grid {
        max-width: 940px;
        margin: 0 auto;
    }

    .stat-clean-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 30px 28px 26px;
        display: flex;
        flex-direction: column;
        height: 100%;
        text-decoration: none;
        color: inherit;
        position: relative;
        box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    /* Efek naik saat dihover / diklik */
    .stat-clean-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -6px rgba(15, 23, 42, 0.08);
    }

    .stat-clean-card:active {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.06);
    }

    /* Header Row: Icon + Action Button */
    .stat-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
    }

    .stat-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .stat-clean-card.stat-blue .stat-icon-wrapper {
        background: #f0f5fa;
        color: #154679;
        border: 1px solid rgba(21, 70, 121, 0.12);
    }

    .stat-clean-card.stat-orange .stat-icon-wrapper {
        background: #fff5ee;
        color: #f26522;
        border: 1px solid rgba(242, 101, 34, 0.15);
    }

    /* Action button in the top right: clickable button link */
    .stat-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 30px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .stat-action-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .stat-action-btn:active {
        background: #e2e8f0;
    }

    .stat-action-btn i {
        font-size: 0.75rem;
    }

    /* Card Body */
    .stat-card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .stat-number {
        font-family: 'Inter', "Inter Fallback", -apple-system, sans-serif;
        font-size: 2.75rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.035em;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .stat-title {
        font-family: 'Inter', "Inter Fallback", -apple-system, sans-serif;
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .stat-desc {
        font-size: 0.9rem;
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 0;
    }

    @media (max-width: 767.98px) {
        .stat-clean-card {
            padding: 22px 20px;
            border-radius: 14px;
        }
        .stat-number {
            font-size: 2.25rem;
        }
        .stat-icon-wrapper {
            width: 42px;
            height: 42px;
            font-size: 1.2rem;
        }
        .stat-action-btn {
            font-size: 0.78rem;
            padding: 5px 12px;
        }
    }


    /* Hero Search Tab Box */
    .search-tabs-wrapper {
        margin-top: -44px; /* Centered without tabs */
        position: relative;
        z-index: 40;
        margin-bottom: 20px;
    }
    
    .tab-content-search {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.15);
    }
    
    .search-tabs-wrapper .form-label {
        font-size: 0.8rem;
        letter-spacing: 1px;
        color: var(--bps-orange);
        margin-bottom: 5px;
    }
    .search-tabs-wrapper .input-group {
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        background: #f8fafc;
        transition: 0.3s;
    }
    .search-tabs-wrapper .input-group:focus-within {
        border-color: var(--bps-blue) !important;
        background: #ffffff;
        box-shadow: 0 0 0 0.25rem rgba(21, 70, 121, 0.1);
    }
    .search-tabs-wrapper .input-group-text {
        background: transparent;
        padding-left: 12px;
        color: #64748b;
        border: none;
        font-size: 0.95rem; /* Matched with Jelajah Data */
    }
    .search-tabs-wrapper .form-control, .search-tabs-wrapper .form-select {
        background: transparent;
        border: none;
        padding-left: 8px;
        padding-top: 10px;
        padding-bottom: 10px;
        font-weight: 500;
        color: #0f172a;
        box-shadow: none !important;
        font-size: 0.95rem; /* Matched with Jelajah Data */
    }
    .search-tabs-wrapper .form-control::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    .search-tabs-wrapper .btn-search {
        background: var(--bps-blue);
        color: white;
        border-radius: 8px;
        font-weight: 700;
        transition: background-color 0.15s ease;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        letter-spacing: 0.5px;
        font-size: 1.1rem;
        padding: 12px 24px;
        min-height: 48px;
        border: none;
    }
    .search-tabs-wrapper .btn-search:hover {
        background: #113861;
    }

    @media (max-width: 576px) {
        .custom-tabs { display: flex; width: 100%; }
        .custom-tabs .nav-item { flex: 1; }
        .custom-tabs .nav-link { padding: 12px 10px; font-size: 0.95rem; text-align: center; }
        .custom-tabs .nav-item:first-child .nav-link { border-radius: 12px 0 0 0; }
        .custom-tabs .nav-item:last-child .nav-link { border-radius: 0 12px 0 0; }
        .tab-content-search { border-radius: 0 0 12px 12px; padding: 25px !important; }
    }

    @media (max-width: 991px) {
        .stats-bar-wrapper { flex-direction: column; border-radius: 24px; gap: 20px; padding: 25px; }
        .stat-divider { width: 100%; height: 1px; margin: 5px 0; }
        .hero-title-airy { font-size: 2.5rem; }
        .hero-airy { padding: 100px 0 180px; }
        .scene-container { right: -50%; opacity: 0.4; }
    }

    /* Compact Features / Layanan Kami */
    .feature-compact {
        background: white;
        border-radius: 24px;
        padding: 40px 30px;
        border: none;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        position: relative;
        overflow: hidden;
    }
    .feature-compact:hover { 
        box-shadow: 0 20px 50px rgba(21, 70, 121, 0.08); 
        transform: translateY(-8px);
    }

    .fc-icon {
        width: 70px; height: 70px;
        margin: 0 auto 20px;
        background: rgba(21, 70, 121, 0.05);
        color: var(--bps-blue);
        border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem;
        transition: 0.3s;
    }

    .feature-compact h5 { font-family: 'Inter', "Inter Fallback", sans-serif; font-weight: 700; font-size: 1.3rem; color: var(--text-main); margin-bottom: 12px; }
    .feature-compact p { font-size: 0.95rem; color: var(--text-muted); margin: 0; line-height: 1.6; }

    /* ===== CREATIVE MODERN INSTANSI DIRECTORY (SIDEBAR + SLIM HORIZONTAL CARDS) ===== */
    .instansi-section {
        background: #f8fafc;
        position: relative;
        border-top: 1px solid rgba(226, 232, 240, 0.6);
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    }

    /* Left Sidebar Menu Card */
    .opd-sidebar-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 14px 10px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
        position: sticky;
        top: 20px;
    }

    .opd-sidebar-search {
        position: relative;
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px 12px;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }

    .opd-sidebar-search:focus-within {
        background: #ffffff;
        border-color: var(--bps-blue);
        box-shadow: 0 0 0 3px rgba(21, 70, 121, 0.12);
    }

    .opd-sidebar-search i {
        color: #94a3b8;
        font-size: 0.88rem;
        margin-right: 8px;
    }

    .opd-sidebar-search input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.86rem;
        color: #0f172a;
        width: 100%;
        padding: 0;
    }

    .opd-sidebar-search input::placeholder {
        color: #94a3b8;
    }

    .opd-sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .opd-sidebar-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 16px;
        border-radius: 12px;
        color: #334155;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        border: 1px solid transparent;
        user-select: none;
    }

    .opd-sidebar-item:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .opd-sidebar-item.active {
        background: #eef5ff;
        color: var(--bps-blue);
        border-color: rgba(21, 70, 121, 0.12);
    }

    .opd-sidebar-label {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .opd-sidebar-label i {
        font-size: 1.05rem;
        width: 20px;
        text-align: center;
        color: #64748b;
        transition: color 0.2s ease;
    }

    .opd-sidebar-item.active .opd-sidebar-label i {
        color: var(--bps-blue);
    }

    .opd-sidebar-badge {
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 20px;
        min-width: 28px;
        text-align: center;
        transition: all 0.2s ease;
    }

    .opd-sidebar-item.active .opd-sidebar-badge {
        background: #dbeafe;
        color: var(--bps-blue);
    }

    /* Elongated Horizontal Cards (Image 1 reference) */
    .opd-strip-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 13px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        height: 100%;
        min-height: 72px;
        text-decoration: none;
        color: #1e293b;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.025);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .opd-strip-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
        text-decoration: none;
        color: #1e293b;
    }

    .opd-strip-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }

    .opd-strip-card:hover .opd-strip-icon {
        transform: scale(1.06);
    }

    /* Icon Color Themes matching Image 1 */
    .opd-strip-icon.opd-icon-orange { background: #fff7ed; color: #ea580c; }
    .opd-strip-icon.opd-icon-rose   { background: #fdf2f8; color: #db2777; }
    .opd-strip-icon.opd-icon-green  { background: #f0fdf4; color: #16a34a; }
    .opd-strip-icon.opd-icon-blue   { background: #eff6ff; color: #2563eb; }
    .opd-strip-icon.opd-icon-indigo { background: #eef2ff; color: #4f46e5; }
    .opd-strip-icon.opd-icon-amber  { background: #fefce8; color: #ca8a04; }
    .opd-strip-icon.opd-icon-purple { background: #faf5ff; color: #9333ea; }
    .opd-strip-icon.opd-icon-cyan   { background: #ecfeff; color: #0891b2; }

    .opd-strip-info {
        flex: 1;
        min-width: 0;
    }

    .opd-strip-name {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.92rem;
        color: #0f172a;
        margin: 0 0 3px 0;
        line-height: 1.35;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: color 0.2s ease;
    }

    .opd-strip-card:hover .opd-strip-name {
        color: var(--bps-blue);
    }

    .opd-strip-meta {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
        display: block;
    }

    .opd-strip-arrow {
        color: #94a3b8;
        font-size: 0.85rem;
        transition: transform 0.2s ease, color 0.2s ease;
        flex-shrink: 0;
    }

    .opd-strip-card:hover .opd-strip-arrow {
        transform: translateX(3px);
        color: var(--bps-blue);
    }

    /* Button from Image 2 */
    .btn-opd-expand {
        background: var(--bps-blue);
        color: #ffffff !important;
        border: none;
        border-radius: 50px;
        padding: 12px 36px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.96rem;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: background-color 0.15s ease;
        cursor: pointer;
        text-decoration: none !important;
    }

    a.btn-opd-expand,
    a.btn-opd-expand:hover,
    a.btn-opd-expand:focus,
    a.btn-opd-expand:active,
    a.btn-opd-expand:visited,
    .btn-opd-expand,
    .btn-opd-expand:hover,
    .btn-opd-expand:focus,
    .btn-opd-expand:active,
    .btn-opd-expand *,
    .btn-opd-expand:hover * {
        text-decoration: none !important;
        text-decoration-line: none !important;
        border-bottom: none !important;
    }

    .btn-opd-expand:hover {
        background: #113861;
        color: #ffffff !important;
        text-decoration: none !important;
    }

    .btn-opd-expand:active {
        opacity: 0.9;
    }

    .btn-opd-collapse {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        padding: 12px 34px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        cursor: pointer;
    }

    .btn-opd-collapse:hover {
        background: #e2e8f0;
        color: #0f172a;
        border-color: #cbd5e1;
    }

    /* ===== MODERN TABEL TERBARU SECTION & CLEAN CARDS ===== */
    .tabel-terbaru-section {
        background: #ffffff;
        position: relative;
        border-top: 1px solid rgba(226, 232, 240, 0.7);
        border-bottom: 1px solid rgba(226, 232, 240, 0.7);
    }

    /* Floating Swiper Side Arrows */
    .tt-swiper-wrap {
        position: relative;
    }

    .tabel-terbaru-swiper {
        padding-top: 18px !important;
        padding-bottom: 24px !important;
        padding-left: 6px !important;
        padding-right: 6px !important;
        margin-top: -12px !important;
        margin-bottom: -10px !important;
        overflow: hidden !important;
    }

    .tabel-terbaru-swiper .swiper-wrapper {
        align-items: stretch;
    }

    .tabel-terbaru-swiper .swiper-slide {
        height: auto;
        display: flex;
        box-sizing: border-box;
    }

    .tt-nav-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        cursor: pointer;
        z-index: 10;
        transition: all 0.22s ease;
    }

    .tt-nav-arrow:hover {
        background: #ffffff;
        border-color: var(--bps-blue);
        color: var(--bps-blue);
        box-shadow: 0 6px 20px rgba(21, 70, 121, 0.18);
        transform: translateY(-50%) scale(1.06);
    }

    .tt-nav-arrow.tt-prev {
        left: -22px;
    }

    .tt-nav-arrow.tt-next {
        right: -22px;
    }

    @media (max-width: 991px) {
        .tt-nav-arrow {
            display: none;
        }
    }

    /* Clean Minimalist Table Card (Only 4 Elements: Instansi, Judul, Tahun, Button Buka) */
    .table-clean-card, .table-modern-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 24px 22px 20px;
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        position: relative;
        box-shadow: 0 3px 14px rgba(0, 0, 0, 0.03);
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        text-decoration: none;
        color: inherit;
    }

    .table-clean-card:hover, .table-modern-card:hover,
    .table-clean-card:focus-within, .table-modern-card:focus-within {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
        text-decoration: none;
        color: inherit;
    }

    /* 1. Instansi (Agency pill) */
    .tcc-header {
        margin-bottom: 12px;
    }

    .tcc-agency {
        background: #edf4fc;
        color: var(--bps-blue);
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 0.78rem;
        font-weight: 700;
        border-radius: 30px;
        padding: 5px 12px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        max-width: 100%;
        border: 1px solid rgba(21, 70, 121, 0.08);
    }

    .tcc-agency i {
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .tcc-agency span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* 2. Judul (Title) */
    .tcc-title {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.5;
        margin-bottom: 18px;
        text-decoration: none;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 72px;
        transition: color 0.2s ease;
    }

    .table-clean-card:hover .tcc-title,
    .table-modern-card:hover .tcc-title,
    .tcc-title:hover {
        color: var(--bps-blue);
    }

    /* Footer: 3. Tahun & 4. Button Buka */
    .tcc-footer {
        margin-top: auto;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .tcc-year {
        font-size: 0.86rem;
        color: #64748b;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .tcc-year i {
        font-size: 0.9rem;
        color: var(--bps-blue);
    }

    .tcc-btn {
        background: #eef5ff;
        color: var(--bps-blue);
        border: 1px solid rgba(21, 70, 121, 0.12);
        border-radius: 50px;
        padding: 7px 18px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.22s ease;
    }

    .table-clean-card:hover .tcc-btn,
    .table-modern-card:hover .tcc-btn {
        background: var(--bps-blue);
        color: #ffffff;
        border-color: var(--bps-blue);
        box-shadow: 0 4px 12px rgba(21, 70, 121, 0.25);
    }

    .tcc-btn i {
        font-size: 0.78rem;
        transition: transform 0.2s ease;
    }

    .table-clean-card:hover .tcc-btn i,
    .table-modern-card:hover .tcc-btn i {
        transform: translateX(3px);
    }

    /* Custom Swiper Pagination Dots */
    .swiper-pagination-custom {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .swiper-pagination-custom .swiper-pagination-bullet {
        background: #cbd5e1;
        opacity: 0.7;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin: 0;
        transition: all 0.25s ease;
        cursor: pointer;
    }

    .swiper-pagination-custom .swiper-pagination-bullet-active {
        background: var(--bps-blue);
        opacity: 1;
        width: 24px;
        border-radius: 10px;
    }

    .section-title h3 { font-size: 2.2rem; font-weight: 800; color: var(--bps-blue); margin-bottom: 8px; font-family: 'Inter', "Inter Fallback", sans-serif;}
    .section-title p { color: var(--text-muted); font-size: 0.9rem; }

    @media (max-width: 991px) {
        .hero-pill-shape { display: none; }
        .hero-airy { min-height: auto; text-align: center; padding-top: 100px; }
        .hero-content-left { padding-right: 0; }
        .badge-float { display: none; }
    }

    @media (max-width: 768px) {
        .hero-section {
            padding: 110px 16px 60px !important;
            min-height: auto;
        }
    }

    /* Hero Illustration Background */
    .hero-illustration-bg {
        background-image: url('<?= base_url('aset/images/hero-home-bg.jpg') ?>');
        background-size: 100% 100%;
        background-position: center bottom;
        background-repeat: no-repeat;
    }
    @media (max-width: 991px) {
        .hero-illustration-bg {
            background-size: cover;
            background-position: center bottom;
        }
    }

    /* Hero Search Bar - Simple, Bersih, Tanpa Efek Lompat/Animasi Berlebih */
    .hero-search-single {
        background: #ffffff;
        border-radius: 50px;
        padding: 6px 8px 6px 24px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
        margin: 0 auto;
        width: 100%;
        min-height: 56px;
        position: relative;
        border: 1px solid #e2e8f0;
        transition: border-color 0.15s ease;
    }
    .hero-search-single:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.1);
        transform: none;
    }
    .hero-search-single:focus-within {
        border-color: #94a3b8;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.12);
        transform: none;
    }
    .hero-search-single .search-icon {
        color: #94a3b8;
        font-size: 1.15rem;
        margin-right: 14px;
        flex-shrink: 0;
    }
    .hero-search-single input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 1.02rem;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        color: #0f172a;
        flex: 1;
        min-width: 0;
        padding: 9px 0;
        font-weight: 500;
        line-height: 1.4;
    }
    .hero-search-single input::placeholder {
        color: #94a3b8;
        font-weight: 400;
        font-size: 1rem;
    }
    /* Circular Clear (X) Button */
    .btn-clear-q {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        min-width: 26px;
        min-height: 26px;
        border-radius: 50%;
        border: none;
        background: #f1f5f9;
        color: #94a3b8;
        cursor: pointer;
        padding: 0;
        margin: 0 10px 0 4px;
        flex-shrink: 0;
        outline: none;
        box-shadow: none;
        transition: background-color 0.15s ease;
    }
    .btn-clear-q i {
        font-size: 0.78rem !important;
        margin: 0 !important;
        color: #94a3b8 !important;
        line-height: 1;
    }
    .btn-clear-q:hover {
        background: #fee2e2;
    }
    .btn-clear-q:hover i {
        color: #ef4444 !important;
    }
    .btn-clear-q:active {
        background: #fecaca;
    }
    .btn-hero-search {
        background: var(--bps-blue);
        color: #ffffff;
        border: none;
        height: 44px;
        padding: 0 32px;
        border-radius: 50px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.98rem;
        letter-spacing: 0.2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .btn-hero-search:hover {
        background: #113861;
        color: #ffffff;
    }
    .btn-hero-search:focus,
    .btn-hero-search:active {
        background: var(--bps-blue);
        color: #ffffff;
        outline: none;
    }
    @media (max-width: 576px) {
        .hero-search-single {
            padding: 4px 6px 4px 16px;
            min-height: 48px;
        }
        .hero-search-single input {
            font-size: 0.92rem;
        }
        .btn-hero-search {
            height: 40px;
            padding: 0 22px;
            font-size: 0.9rem;
        }
    }
</style>

<!-- ===== HERO SECTION (Dedicated Illustration Background) ===== -->
<section class="hero-section text-center position-relative overflow-hidden d-flex align-items-center justify-content-center" style="min-height: 100vh; padding: 120px 20px 80px;">
    
    <!-- Hero Illustration Background Container -->
    <div class="position-absolute top-0 start-0 w-100 h-100 hero-illustration-bg" style="z-index: 0;">
        <!-- Soft light radial glow in the center so the title & search box pop crisply while leaving map & Lawang Sewu vibrant -->
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(ellipse at 50% 50%, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0.12) 50%, transparent 75%); pointer-events: none;"></div>
    </div>

    <div class="container hero-container position-relative" style="z-index: 2; width: 100%; max-width: 960px;">
        <!-- JUDUL -->
        <h1 class="hero-title mx-auto mb-3" style="font-size: clamp(2.9rem, 5.4vw, 4.4rem); font-weight: 800; letter-spacing: -0.04em; line-height: 1.14; max-width: 920px; color: #0d2c4d;">
            Satu Data Daerah <br>
            <span style="background: linear-gradient(135deg, #F26522 0%, #D65518 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Dalam Angka</span>
        </h1>
        
        <!-- SUB JUDUL -->
        <p class="hero-desc mx-auto mb-4 pb-2" style="font-size: 1.22rem; color: #334155; max-width: 760px; font-weight: 500; line-height: 1.65;">
            Platform terpadu untuk menelusuri, menganalisis, dan mengunduh data statistik dari seluruh Instansi dan Organisasi Perangkat Daerah di Jawa Tengah secara gratis.
        </p>

        <!-- SEARCH BAR -->
        <div class="search-hero-box mx-auto" style="max-width: 860px; width: 100%;">
            <!-- Search Content -->
            <form action="<?= base_url('home/search') ?>" method="GET" class="w-100">
                <div class="hero-search-single">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="q" id="hero-search-q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Cari judul tabel..." autocomplete="off">
                    <button type="button" class="btn-clear-q <?= empty($q) ? 'd-none' : '' ?>" id="btn-clear-hero" title="Hapus pencarian">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <button type="submit" class="btn-hero-search">
                        Cari
                    </button>
                </div>
            </form>
        </div>

    </div>
</section>

<!-- ===== STATS OVERVIEW SECTION ===== -->
<section class="stats-overview-section">
    <div class="container">
        <!-- Section Header to fill the top nicely -->
        <div class="text-center stats-header-wrap mb-4 pb-1">
            <h2 class="stats-section-title">Statistik Terpadu Jawa Tengah</h2>
            <p class="stats-section-subtitle">
                Akses terbuka dan terintegrasi ke seluruh indikator data statistik sektoral yang dikelola secara resmi oleh Instansi Pemerintah Provinsi Jawa Tengah.
            </p>
        </div>

        <div class="row g-4 justify-content-center stats-cards-grid">
            <!-- Stat 1: Tabel Data -->
            <div class="col-md-6">
                <div class="stat-clean-card stat-blue">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class="fa-solid fa-table-cells-large"></i>
                        </div>
                        <a href="<?= base_url('home/search') ?>" class="stat-action-btn">
                            Eksplorasi Data <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="stat-card-body">
                        <div class="stat-number">
                            <?= isset($total_tabel) ? number_format($total_tabel, 0, ',', '.') : '0' ?>
                        </div>
                        <h3 class="stat-title">Tabel Data Statistik</h3>
                        <p class="stat-desc">
                            Kumpulan dataset statistik sektoral siap pakai dari berbagai bidang urusan pemerintahan Jawa Tengah.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stat 2: OPD Terdaftar -->
            <div class="col-md-6">
                <div class="stat-clean-card stat-orange">
                    <div class="stat-card-header">
                        <div class="stat-icon-wrapper">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <a href="#instansi" class="stat-action-btn">
                            Lihat Instansi <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="stat-card-body">
                        <div class="stat-number">
                            <?= isset($total_opd) ? number_format($total_opd, 0, ',', '.') : '0' ?>
                        </div>
                        <h3 class="stat-title">OPD & Instansi Terdaftar</h3>
                        <p class="stat-desc">
                            Organisasi Perangkat Daerah dan Instansi resmi penyedia data yang terintegrasi di portal ini.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ===== JELAJAHI BERDASARKAN OPD / INSTANSI ===== -->
<section id="instansi" class="instansi-section py-5">
    <div class="container py-2">
        <!-- Section Header matching design language -->
        <div class="text-center mb-4 pb-1">
            <h2 class="stats-section-title">Jelajahi Instansi</h2>
            <p class="stats-section-subtitle">
                Telusuri ketersediaan data dan publikasi statistik resmi berdasarkan Organisasi Perangkat Daerah di Jawa Tengah.
            </p>
        </div>

        <?php
        $cat_counts = [
            'all' => !empty($opd_list) ? count($opd_list) : 0,
            'badan' => 0,
            'dinas' => 0,
            'balai' => 0,
            'lembaga' => 0,
            'lainnya' => 0
        ];

        if (!empty($opd_list)) {
            foreach ($opd_list as $opd_item) {
                $l = strtolower($opd_item->unitkerja_ind);
                if (strpos($l, 'badan') !== false) {
                    $cat_counts['badan']++;
                } elseif (strpos($l, 'dinas') !== false) {
                    $cat_counts['dinas']++;
                } elseif (strpos($l, 'balai') !== false || strpos($l, 'rsud') !== false || strpos($l, 'upt') !== false || strpos($l, 'laboratorium') !== false) {
                    $cat_counts['balai']++;
                } elseif (strpos($l, 'lembaga') !== false || strpos($l, 'sekretariat') !== false || strpos($l, 'biro') !== false || strpos($l, 'inspektorat') !== false || strpos($l, 'dewan') !== false || strpos($l, 'komisi') !== false || strpos($l, 'bank') !== false || strpos($l, 'kantor') !== false) {
                    $cat_counts['lembaga']++;
                } else {
                    $cat_counts['lainnya']++;
                }
            }
        }

        if (!function_exists('getOpdDirectoryMeta')) {
            function getOpdDirectoryMeta($name, $index) {
                $lower = strtolower($name);
                
                // Category
                if (strpos($lower, 'badan') !== false) {
                    $catSlug = 'badan';
                    $catLabel = 'Badan Daerah';
                } elseif (strpos($lower, 'dinas') !== false) {
                    $catSlug = 'dinas';
                    $catLabel = 'Dinas Daerah';
                } elseif (strpos($lower, 'balai') !== false || strpos($lower, 'rsud') !== false || strpos($lower, 'upt') !== false || strpos($lower, 'laboratorium') !== false) {
                    $catSlug = 'balai';
                    $catLabel = 'Balai & UPT';
                } elseif (strpos($lower, 'lembaga') !== false || strpos($lower, 'sekretariat') !== false || strpos($lower, 'biro') !== false || strpos($lower, 'inspektorat') !== false || strpos($lower, 'dewan') !== false || strpos($lower, 'komisi') !== false || strpos($lower, 'bank') !== false || strpos($lower, 'kantor') !== false) {
                    $catSlug = 'lembaga';
                    $catLabel = 'Lembaga / Sekretariat';
                } else {
                    $catSlug = 'lainnya';
                    $catLabel = 'Instansi Lainnya';
                }

                // Specific Icon & Color Theme matching Image 1
                if (strpos($lower, 'kepegawaian') !== false || strpos($lower, 'sdm') !== false || strpos($lower, 'bkd') !== false) {
                    $icon = 'fa-solid fa-id-card-clip';
                    $colorClass = 'opd-icon-orange';
                } elseif (strpos($lower, 'bencana') !== false || strpos($lower, 'bpbd') !== false) {
                    $icon = 'fa-solid fa-house-fire';
                    $colorClass = 'opd-icon-orange';
                } elseif (strpos($lower, 'politik') !== false || strpos($lower, 'kesbangpol') !== false || strpos($lower, 'bangsa') !== false) {
                    $icon = 'fa-solid fa-landmark-flag';
                    $colorClass = 'opd-icon-rose';
                } elseif (strpos($lower, 'keuangan') !== false || strpos($lower, 'aset') !== false || strpos($lower, 'bpkad') !== false) {
                    $icon = 'fa-solid fa-file-invoice-dollar';
                    $colorClass = 'opd-icon-orange';
                } elseif (strpos($lower, 'pendapatan') !== false || strpos($lower, 'bapenda') !== false || strpos($lower, 'pajak') !== false) {
                    $icon = 'fa-solid fa-money-bill-trend-up';
                    $colorClass = 'opd-icon-orange';
                } elseif (strpos($lower, 'pom') !== false || strpos($lower, 'obat') !== false || strpos($lower, 'makanan') !== false) {
                    $icon = 'fa-solid fa-arrow-trend-up';
                    $colorClass = 'opd-icon-green';
                } elseif (strpos($lower, 'aliran sungai') !== false || strpos($lower, 'sungai') !== false || strpos($lower, 'das') !== false || strpos($lower, 'air') !== false) {
                    $icon = 'fa-solid fa-water';
                    $colorClass = 'opd-icon-blue';
                } elseif (strpos($lower, 'bank') !== false || strpos($lower, 'bi ') !== false || strpos($lower, 'ojk') !== false) {
                    $icon = 'fa-solid fa-building-columns';
                    $colorClass = 'opd-icon-indigo';
                } elseif (strpos($lower, 'kesehatan') !== false || strpos($lower, 'rsud') !== false) {
                    $icon = 'fa-solid fa-heart-pulse';
                    $colorClass = 'opd-icon-rose';
                } elseif (strpos($lower, 'pendidikan') !== false || strpos($lower, 'sekolah') !== false) {
                    $icon = 'fa-solid fa-graduation-cap';
                    $colorClass = 'opd-icon-blue';
                } elseif (strpos($lower, 'pertanian') !== false || strpos($lower, 'kehutanan') !== false || strpos($lower, 'lingkungan') !== false || strpos($lower, 'pangan') !== false) {
                    $icon = 'fa-solid fa-leaf';
                    $colorClass = 'opd-icon-green';
                } elseif (strpos($lower, 'komunikasi') !== false || strpos($lower, 'informatika') !== false || strpos($lower, 'diskominfo') !== false) {
                    $icon = 'fa-solid fa-network-wired';
                    $colorClass = 'opd-icon-cyan';
                } elseif (strpos($lower, 'perhubungan') !== false || strpos($lower, 'dishub') !== false) {
                    $icon = 'fa-solid fa-route';
                    $colorClass = 'opd-icon-amber';
                } elseif (strpos($lower, 'hukum') !== false || strpos($lower, 'kejaksaan') !== false || strpos($lower, 'pengadilan') !== false) {
                    $icon = 'fa-solid fa-scale-balanced';
                    $colorClass = 'opd-icon-purple';
                } elseif (strpos($lower, 'sosial') !== false || strpos($lower, 'dinsos') !== false) {
                    $icon = 'fa-solid fa-hands-holding-child';
                    $colorClass = 'opd-icon-rose';
                } elseif (strpos($lower, 'energi') !== false || strpos($lower, 'esdm') !== false) {
                    $icon = 'fa-solid fa-bolt';
                    $colorClass = 'opd-icon-amber';
                } elseif (strpos($lower, 'kearsipan') !== false || strpos($lower, 'perpustakaan') !== false) {
                    $icon = 'fa-solid fa-book-bookmark';
                    $colorClass = 'opd-icon-blue';
                } elseif (strpos($lower, 'pariwisata') !== false || strpos($lower, 'budaya') !== false) {
                    $icon = 'fa-solid fa-masks-theater';
                    $colorClass = 'opd-icon-rose';
                } elseif (strpos($lower, 'industri') !== false || strpos($lower, 'perdagangan') !== false) {
                    $icon = 'fa-solid fa-industry';
                    $colorClass = 'opd-icon-indigo';
                } elseif (strpos($lower, 'bappeda') !== false || strpos($lower, 'perencanaan') !== false) {
                    $icon = 'fa-solid fa-chart-line';
                    $colorClass = 'opd-icon-cyan';
                } elseif (strpos($lower, 'inspektorat') !== false) {
                    $icon = 'fa-solid fa-clipboard-check';
                    $colorClass = 'opd-icon-purple';
                } else {
                    $palette = [
                        ['icon' => 'fa-solid fa-building-user', 'color' => 'opd-icon-orange'],
                        ['icon' => 'fa-solid fa-landmark', 'color' => 'opd-icon-blue'],
                        ['icon' => 'fa-solid fa-city', 'color' => 'opd-icon-green'],
                        ['icon' => 'fa-solid fa-building', 'color' => 'opd-icon-indigo'],
                        ['icon' => 'fa-solid fa-house-chimney', 'color' => 'opd-icon-rose']
                    ];
                    $selected = $palette[$index % count($palette)];
                    $icon = $selected['icon'];
                    $colorClass = $selected['color'];
                }

                return ['icon' => $icon, 'colorClass' => $colorClass, 'catSlug' => $catSlug, 'catLabel' => $catLabel];
            }
        }
        ?>

        <div class="row g-4 align-items-start">
            <!-- Left Sidebar Toolbar (Image 1 reference) -->
            <div class="col-lg-3 col-md-4">
                <div class="opd-sidebar-card">
                    <!-- Quick search inside sidebar -->
                    <div class="opd-sidebar-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="opdFilterInput" placeholder="Cari instansi..." autocomplete="off">
                        <button type="button" id="opdClearSearch" class="btn-clear-q" style="display: none; margin: 0 4px 0 0;" title="Hapus pencarian">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Category Items with Badges -->
                    <div class="opd-sidebar-menu">
                        <button type="button" class="opd-sidebar-item active" data-filter="all">
                            <span class="opd-sidebar-label">
                                <i class="fa-solid fa-house"></i>
                                <span>Semua Instansi</span>
                            </span>
                            <span class="opd-sidebar-badge"><?= $cat_counts['all'] ?></span>
                        </button>
                        <button type="button" class="opd-sidebar-item" data-filter="badan">
                            <span class="opd-sidebar-label">
                                <i class="fa-solid fa-users"></i>
                                <span>Badan</span>
                            </span>
                            <span class="opd-sidebar-badge"><?= $cat_counts['badan'] ?></span>
                        </button>
                        <button type="button" class="opd-sidebar-item" data-filter="dinas">
                            <span class="opd-sidebar-label">
                                <i class="fa-solid fa-building"></i>
                                <span>Dinas</span>
                            </span>
                            <span class="opd-sidebar-badge"><?= $cat_counts['dinas'] ?></span>
                        </button>
                        <button type="button" class="opd-sidebar-item" data-filter="balai">
                            <span class="opd-sidebar-label">
                                <i class="fa-solid fa-house-laptop"></i>
                                <span>Balai</span>
                            </span>
                            <span class="opd-sidebar-badge"><?= $cat_counts['balai'] ?></span>
                        </button>
                        <button type="button" class="opd-sidebar-item" data-filter="lembaga">
                            <span class="opd-sidebar-label">
                                <i class="fa-solid fa-scale-balanced"></i>
                                <span>Lembaga</span>
                            </span>
                            <span class="opd-sidebar-badge"><?= $cat_counts['lembaga'] ?></span>
                        </button>
                        <button type="button" class="opd-sidebar-item" data-filter="lainnya">
                            <span class="opd-sidebar-label">
                                <i class="fa-solid fa-boxes-stacked"></i>
                                <span>Lainnya</span>
                            </span>
                            <span class="opd-sidebar-badge"><?= $cat_counts['lainnya'] ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Elongated Horizontal Strip Cards (Image 1 reference) -->
            <div class="col-lg-9 col-md-8">
                <div class="row g-3" id="opd-grid">
                    <?php if (!empty($opd_list)) { ?>
                        <?php foreach($opd_list as $index => $opd) { 
                            $meta = getOpdDirectoryMeta($opd->unitkerja_ind, $index);
                        ?>
                            <div class="col-md-6 col-12 opd-item" data-category="<?= $meta['catSlug'] ?>" data-name="<?= strtolower(htmlspecialchars($opd->unitkerja_ind)) ?>" <?= $index >= 8 ? 'style="display:none;"' : '' ?>>
                                <a href="<?= base_url('home/search?opd_search=' . urlencode($opd->unitkerja_ind)) ?>" class="opd-strip-card" title="<?= htmlspecialchars($opd->unitkerja_ind) ?>">
                                    <!-- Left Round Icon with pastel background -->
                                    <div class="opd-strip-icon <?= $meta['colorClass'] ?>">
                                        <i class="<?= $meta['icon'] ?>"></i>
                                    </div>

                                    <!-- Middle Details -->
                                    <div class="opd-strip-info">
                                        <h6 class="opd-strip-name"><?= htmlspecialchars($opd->unitkerja_ind) ?></h6>
                                        <span class="opd-strip-meta"><?= $opd->total_tabel ?> Tabel Data</span>
                                    </div>

                                    <!-- Right Chevron Arrow -->
                                    <div class="opd-strip-arrow">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="col-12 text-center text-muted py-5 bg-white rounded-4 border">Belum ada data Instansi.</div>
                    <?php } ?>
                </div>

                <!-- Empty search state -->
                <div id="opd-empty-state" class="text-center py-5 bg-white rounded-4 border mt-3" style="display: none;">
                    <i class="fa-solid fa-magnifying-glass fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0 fw-semibold">Tidak ditemukan instansi yang cocok dengan filter atau kata kunci.</p>
                </div>

                <!-- Bottom Button: Exactly matching Image 2 -->
                <?php if (!empty($opd_list) && count($opd_list) > 8) { ?>
                <div class="text-center mt-4 pt-3">
                    <button type="button" id="btn-load-more-opd" class="btn-opd-expand">
                        <span>Tampilkan Lebih Banyak (<?= count($opd_list) ?> Instansi)</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <button type="button" id="btn-hide-opd" class="btn-opd-collapse" style="display: none;">
                        <i class="fa-solid fa-chevron-up"></i>
                        <span>Tutup Kembali</span>
                    </button>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var opdItems = Array.from(document.querySelectorAll('.opd-item'));
    var loadMoreBtn = document.getElementById('btn-load-more-opd');
    var hideBtn = document.getElementById('btn-hide-opd');
    var filterInput = document.getElementById('opdFilterInput');
    var clearSearchBtn = document.getElementById('opdClearSearch');
    var sidebarBtns = document.querySelectorAll('.opd-sidebar-item');
    var emptyState = document.getElementById('opd-empty-state');
    
    var activeFilter = 'all';
    var searchQuery = '';
    var defaultVisible = 8;
    var currentlyShown = defaultVisible;

    function updateDisplay() {
        var matches = opdItems.filter(function(item) {
            var cat = item.getAttribute('data-category');
            var name = item.getAttribute('data-name');
            var matchesCat = (activeFilter === 'all' || cat === activeFilter);
            var matchesSearch = (searchQuery === '' || name.indexOf(searchQuery) !== -1);
            return matchesCat && matchesSearch;
        });

        // Hide all items first
        opdItems.forEach(function(item) {
            item.style.display = 'none';
        });

        if (matches.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            if (hideBtn) hideBtn.style.display = 'none';
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        if (searchQuery !== '') {
            // Show all search matches
            matches.forEach(function(item) {
                item.style.display = 'block';
            });
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            if (hideBtn) hideBtn.style.display = 'none';
        } else {
            // Paginated view
            var toShow = matches.slice(0, currentlyShown);
            toShow.forEach(function(item) {
                item.style.display = 'block';
            });

            if (loadMoreBtn) {
                if (matches.length > currentlyShown) {
                    loadMoreBtn.style.display = 'inline-flex';
                    var btnText = loadMoreBtn.querySelector('span');
                    if (btnText) {
                        btnText.textContent = 'Tampilkan Lebih Banyak (' + matches.length + ' Instansi)';
                    }
                } else {
                    loadMoreBtn.style.display = 'none';
                }
            }
            if (hideBtn) {
                hideBtn.style.display = (currentlyShown > defaultVisible && matches.length > defaultVisible) ? 'inline-flex' : 'none';
            }
        }
    }

    // Sidebar Category Filter Clicks
    sidebarBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebarBtns.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            activeFilter = this.getAttribute('data-filter') || 'all';
            currentlyShown = defaultVisible;
            updateDisplay();
        });
    });

    // Live Search in Sidebar
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            searchQuery = this.value.trim().toLowerCase();
            if (clearSearchBtn) {
                clearSearchBtn.style.display = (searchQuery !== '') ? 'inline-block' : 'none';
            }
            updateDisplay();
        });
    }

    // Clear Search Click
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (filterInput) {
                filterInput.value = '';
                searchQuery = '';
                this.style.display = 'none';
                updateDisplay();
            }
        });
    }

    // Load More Click
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            currentlyShown += 8;
            updateDisplay();
        });
    }

    // Hide / Reset Click
    if (hideBtn) {
        hideBtn.addEventListener('click', function() {
            currentlyShown = defaultVisible;
            updateDisplay();
            var section = document.querySelector('.instansi-section');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
});
</script>

<!-- ===== DATASET TABULAR TERBARU ===== -->
<section class="tabel-terbaru-section py-5">
    <div class="container py-2">
        <!-- Section Header matching Gambar 2 (Centered) -->
        <div class="text-center mb-4 pb-2">
            <h2 class="stats-section-title">Tabel Terbaru</h2>
            <p class="stats-section-subtitle">
                Rilis dataset tabular dan publikasi statistik sektoral teraktual dari berbagai instansi di Jawa Tengah.
            </p>
        </div>

        <!-- Carousel with Floating Side Navigation Arrows -->
        <div class="tt-swiper-wrap">
            <button type="button" class="tt-nav-arrow tt-prev swiper-prev-custom" aria-label="Sebelumnya" title="Geser ke kiri">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <!-- Swiper Carousel -->
            <div class="swiper tabel-terbaru-swiper">
                <div class="swiper-wrapper">
                    <?php if (!empty($recent_tables)) { ?>
                        <?php foreach ($recent_tables as $idx => $row) { ?>
                            <?php 
                                $is_sheet = (strpos($row->link_tabel, 'view_portal_tabel') === false);
                                if ($is_sheet) {
                                    $href = base_url('home/view_sheet?url=' . urlencode($row->link_tabel) . '&table_id=' . $row->id);
                                } else {
                                    $portal_id = $row->link_tabel;
                                    if (preg_match('/[?&]id=([^&]+)/', $row->link_tabel, $m_id)) {
                                        $portal_id = $m_id[1];
                                    }
                                    $href = base_url('home/view_tabel?id=' . urlencode($portal_id) . '&table_id=' . $row->id);
                                }
                            ?>
                            <div class="swiper-slide" style="height: auto;">
                                <div class="table-clean-card">
                                    <!-- 1. Instansi -->
                                    <div class="tcc-header">
                                        <span class="tcc-agency" title="<?= htmlspecialchars($row->unitkerja_ind ?? 'Instansi Daerah') ?>">
                                            <i class="fa-solid fa-building-columns"></i>
                                            <span><?= htmlspecialchars($row->unitkerja_ind ?? 'Instansi Daerah') ?></span>
                                        </span>
                                    </div>

                                    <!-- 2. Judul -->
                                    <a href="<?= $href ?>" class="tcc-title" title="<?= htmlspecialchars($row->judul_ind) ?>">
                                        <?= htmlspecialchars($row->judul_ind) ?>
                                    </a>

                                    <!-- 3. Tahun & 4. Button Buka -->
                                    <div class="tcc-footer">
                                        <span class="tcc-year">
                                            <i class="fa-regular fa-calendar-days"></i>
                                            <span>Tahun <?= htmlspecialchars($row->tahun ?? date('Y')) ?></span>
                                        </span>
                                        <a href="<?= $href ?>" class="tcc-btn">
                                            <span>Buka</span>
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="col-12 text-center py-5 bg-white rounded-4 border">
                            <i class="fa-solid fa-table fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0 fw-semibold">Belum ada dataset yang dipublikasikan.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <button type="button" class="tt-nav-arrow tt-next swiper-next-custom" aria-label="Selanjutnya" title="Geser ke kanan">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Custom Pagination Bullets -->
        <div class="swiper-pagination-custom mt-3"></div>

        <!-- Centered "Lihat Semua" Button -->
        <div class="text-center mt-4 pt-1">
            <a href="<?= base_url('home/search') ?>" class="btn-opd-expand text-decoration-none" style="text-decoration: none !important; border-bottom: none !important;">
                <span style="text-decoration: none !important; border-bottom: none !important;">Lihat Semua</span>
                <i class="fa-solid fa-arrow-right" style="text-decoration: none !important; border-bottom: none !important;"></i>
            </a>
        </div>
    </div>
</section>


<!-- KEUNGGULAN (Kenali Fitur Portal) -->
<section class="py-5" style="background-color: #f8fafc;">
    <div class="container py-3">
        <!-- Section Header matching design language -->
        <div class="text-center mb-4 pb-2">
            <h2 class="stats-section-title">Kenali Fitur Portal</h2>
            <p class="stats-section-subtitle">
                Ragam kemudahan dan keandalan sistem untuk mendukung kebutuhan eksplorasi data statistik Anda.
            </p>
        </div>

        <!-- Custom Tabs Navigation (Segmented Pill Bar) -->
        <div class="d-flex justify-content-center mb-4">
            <ul class="nav nav-pills feature-nav-pills" id="keunggulan-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-pencarian" data-bs-toggle="pill" data-bs-target="#pane-pencarian" type="button" role="tab" aria-controls="pane-pencarian" aria-selected="true">
                        <i class="fa-solid fa-magnifying-glass me-2"></i>Pencarian Cepat
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-data" data-bs-toggle="pill" data-bs-target="#pane-data" type="button" role="tab" aria-controls="pane-data" aria-selected="false">
                        <i class="fa-solid fa-shield-halved me-2"></i>Data Terjamin 100%
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-export" data-bs-toggle="pill" data-bs-target="#pane-export" type="button" role="tab" aria-controls="pane-export" aria-selected="false">
                        <i class="fa-solid fa-file-export me-2"></i>Multi-Format Export
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-api" data-bs-toggle="pill" data-bs-target="#pane-api" type="button" role="tab" aria-controls="pane-api" aria-selected="false">
                        <i class="fa-solid fa-network-wired me-2"></i>Terintegrasi API
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tabs Content -->
        <div class="tab-content" id="keunggulan-tabContent">
            <!-- TAB 1: Pencarian Cepat -->
            <div class="tab-pane fade show active" id="pane-pencarian" role="tabpanel" aria-labelledby="tab-pencarian">
                <div class="keunggulan-content-box">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="stats-pill-badge mb-2">
                                <i class="fa-solid fa-bolt me-1.5" style="color: var(--bps-orange);"></i> Penelusuran Instan
                            </div>
                            <h3 class="kc-title">Pencarian Dataset Cepat & Filter Presisi</h3>
                            <p class="kc-desc">
                                Temukan data yang Anda butuhkan dalam hitungan detik dengan sistem pencarian dinamis, filter multi-instansi, dan pengelompokan tahun yang presisi.
                            </p>

                            <div class="kc-highlight-list mb-4">
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-sliders"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Filter Multi-Kriteria</h6>
                                        <p>Saring dataset berdasarkan instansi (OPD), rentang tahun spesifik, hingga topik statistik dalam satu antarmuka terpadu.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-magnifying-glass-chart"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Pencarian Kata Kunci Real-Time</h6>
                                        <p>Mendukung pencarian cerdas berbasis indeks kata kunci judul tabel tanpa perlu memuat ulang keseluruhan laman.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Akses Langsung ke Metadata</h6>
                                        <p>Tinjau ringkasan variabel dan sumber data secara instan sebelum melakukan pengunduhan berkas.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-1">
                                <a href="<?= base_url('home/search') ?>" class="btn-opd-expand text-decoration-none" style="text-decoration: none !important; border-bottom: none !important; padding: 11px 30px; font-size: 0.92rem;">
                                    <span style="text-decoration: none !important; border-bottom: none !important;">Coba Fitur Pencarian</span>
                                    <i class="fa-solid fa-arrow-right" style="text-decoration: none !important; border-bottom: none !important;"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: Interactive Search Simulation Widget -->
                        <div class="col-lg-5">
                            <div class="kc-preview-card">
                                <div class="kc-preview-header">
                                    <div class="kc-window-dots">
                                        <span class="dot dot-red"></span>
                                        <span class="dot dot-yellow"></span>
                                        <span class="dot dot-green"></span>
                                    </div>
                                    <span class="kc-preview-tag">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> Penelusuran Cerdas
                                    </span>
                                </div>
                                <div class="kc-preview-body">
                                    <!-- Simulated Search Input -->
                                    <?php 
                                        // Ambil 1 contoh judul tabel riil dari database (jangan ngarang)
                                        $sample_title = 'Jumlah Penduduk dan Rasio Jenis Kelamin Menurut Kabupaten/Kota di Provinsi Jawa Tengah, 2024';
                                        $sample_opd   = 'BPS Provinsi Jawa Tengah';
                                        $sample_year  = '2024';
                                        $sample_query = 'Jumlah Penduduk';

                                        if (!empty($recent_tables)) {
                                            $chosen = null;
                                            foreach ($recent_tables as $rt) {
                                                $j = $rt->judul_ind ?? ($rt->judul ?? '');
                                                if (stripos($j, 'Penduduk') !== false) {
                                                    $chosen = $rt;
                                                    break;
                                                }
                                            }
                                            if (!$chosen && isset($recent_tables[0])) {
                                                $chosen = $recent_tables[0];
                                            }

                                            if ($chosen) {
                                                $raw_title = $chosen->judul_ind ?? ($chosen->judul ?? '');
                                                if (!empty($raw_title)) {
                                                    $clean_title = preg_replace('/\s+/', ' ', trim($raw_title));
                                                    $sample_title = $clean_title;
                                                    $sample_opd   = !empty($chosen->unitkerja_ind) ? $chosen->unitkerja_ind : 'BPS Provinsi Jawa Tengah';
                                                    $sample_year  = !empty($chosen->tahun) ? $chosen->tahun : '2024';
                                                    
                                                    $no_label = trim(preg_replace('/^(Tabel\s+[0-9\.\-]+\s*)/i', '', $clean_title));
                                                    $words = array_values(array_filter(explode(' ', $no_label), function($w) {
                                                        return !in_array(strtolower($w), ['dan', 'di', 'ke', 'dari', 'pada', 'yang', 'untuk', 'menurut']);
                                                    }));
                                                    $sample_query = implode(' ', array_slice($words, 0, 3));
                                                }
                                            }
                                        }
                                    ?>
                                    <div class="sim-search-bar mb-3">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                        <span class="sim-search-text"><?= htmlspecialchars($sample_query) ?></span>
                                        <span class="sim-search-badge"><?= htmlspecialchars($sample_year) ?></span>
                                    </div>

                                    <!-- Simulated Result List (1 Contoh Nyata Judul Tabel) -->
                                    <div class="sim-result-list">
                                        <div class="sim-result-item">
                                            <div class="sim-result-icon">
                                                <i class="fa-solid fa-table"></i>
                                            </div>
                                            <div class="sim-result-info">
                                                <div class="sim-result-title" title="<?= htmlspecialchars($sample_title) ?>">
                                                    <?= htmlspecialchars($sample_title) ?>
                                                </div>
                                                <div class="sim-result-meta">
                                                    <span><i class="fa-solid fa-building me-1"></i> <?= htmlspecialchars($sample_opd) ?></span>
                                                    <span class="sim-badge-year"><?= htmlspecialchars($sample_year) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bottom Metric Strip -->
                                    <div class="sim-metrics-strip mt-3">
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">&lt; 0.2s</span>
                                            <span class="sim-metric-lbl">Waktu Temu</span>
                                        </div>
                                        <div class="sim-metric-divider"></div>
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">100%</span>
                                            <span class="sim-metric-lbl">Akurasi Data</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Data Terjamin 100% -->
            <div class="tab-pane fade" id="pane-data" role="tabpanel" aria-labelledby="tab-data">
                <div class="keunggulan-content-box">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="stats-pill-badge mb-2">
                                <i class="fa-solid fa-shield-halved me-1.5" style="color: var(--bps-green);"></i> Kualitas & Integritas
                            </div>
                            <h3 class="kc-title">Data Resmi Sesuai Standar Satu Data Indonesia</h3>
                            <p class="kc-desc">
                                Seluruh informasi yang disajikan berasal langsung dari sumber resmi dan dijamin keakuratannya oleh instansi pemerintah yang bersangkutan melalui validasi BPS.
                            </p>

                            <div class="kc-highlight-list mb-4">
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-award"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Pembinaan Statistik Sektoral</h6>
                                        <p>Dikelola di bawah pembinaan Badan Pusat Statistik (BPS) Provinsi Jawa Tengah selaku Pembina Data Statistik.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-building-circle-check"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Produsen Data Terverifikasi</h6>
                                        <p>Setiap tabel disusun dan dipublikasikan langsung oleh OPD teknis yang berwenang dan bertanggung jawab penuh.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-file-shield"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Metadata Sesuai Standar SDI</h6>
                                        <p>Struktur data mematuhi kaidah Satu Data Indonesia, menjamin konsistensi konsep, definisi, dan interoperabilitas.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-1">
                                <a href="<?= base_url('home/search') ?>" class="btn-opd-expand text-decoration-none" style="text-decoration: none !important; border-bottom: none !important; padding: 11px 30px; font-size: 0.92rem;">
                                    <span style="text-decoration: none !important; border-bottom: none !important;">Jelajahi Data Resmi</span>
                                    <i class="fa-solid fa-arrow-right" style="text-decoration: none !important; border-bottom: none !important;"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: Verification Trust Mockup -->
                        <div class="col-lg-5">
                            <div class="kc-preview-card">
                                <div class="kc-preview-header">
                                    <div class="kc-window-dots">
                                        <span class="dot dot-red"></span>
                                        <span class="dot dot-yellow"></span>
                                        <span class="dot dot-green"></span>
                                    </div>
                                    <span class="kc-preview-tag" style="background: rgba(16, 185, 129, 0.1); color: var(--bps-green);">
                                        <i class="fa-solid fa-certificate me-1"></i> Terverifikasi Resmi
                                    </span>
                                </div>
                                <div class="kc-preview-body">
                                    <!-- Trust Badge Hero -->
                                    <div class="sim-trust-box text-center py-3 mb-3">
                                        <div class="sim-trust-icon mb-2">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </div>
                                        <h5 class="sim-trust-title mb-1">Standar Satu Data Indonesia</h5>
                                        <p class="sim-trust-sub mb-0">Tervalidasi & Bebas Manipulasi</p>
                                    </div>

                                    <!-- Verification Points -->
                                    <div class="sim-verify-list">
                                        <div class="sim-verify-item">
                                            <i class="fa-solid fa-circle-check text-success me-2"></i>
                                            <span>Pembina Data: <strong>BPS Jawa Tengah</strong></span>
                                        </div>
                                        <div class="sim-verify-item">
                                            <i class="fa-solid fa-circle-check text-success me-2"></i>
                                            <span>Walidata: <strong>Diskominfo Prov. Jateng</strong></span>
                                        </div>
                                        <div class="sim-verify-item">
                                            <i class="fa-solid fa-circle-check text-success me-2"></i>
                                            <span>Produsen Data: <strong><?= isset($total_opd) ? number_format($total_opd, 0, ',', '.') : '0' ?> OPD Terdaftar</strong></span>
                                        </div>
                                    </div>

                                    <!-- Bottom Metric Strip -->
                                    <div class="sim-metrics-strip mt-3">
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">100%</span>
                                            <span class="sim-metric-lbl">Otentisitas</span>
                                        </div>
                                        <div class="sim-metric-divider"></div>
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">SDI</span>
                                            <span class="sim-metric-lbl">Standar Data</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Multi-Format Export -->
            <div class="tab-pane fade" id="pane-export" role="tabpanel" aria-labelledby="tab-export">
                <div class="keunggulan-content-box">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="stats-pill-badge mb-2">
                                <i class="fa-solid fa-download me-1.5" style="color: var(--bps-orange);"></i> Akses & Unduhan
                            </div>
                            <h3 class="kc-title">Fleksibilitas Unduhan Excel & Dokumen PDF</h3>
                            <p class="kc-desc">
                                Menyediakan berbagai opsi pengunduhan berkas yang disesuaikan secara khusus dengan kebutuhan analisis data, pelaporan resmi dinas, maupun pengarsipan.
                            </p>

                            <div class="kc-highlight-list mb-4">
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-file-excel"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Spreadsheet Excel (XLSX)</h6>
                                        <p>Format tabel terstruktur yang siap diolah untuk kalkulasi statistik, pivot table, dan analisis lanjutan.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Dokumen Cetak Resmi (PDF)</h6>
                                        <p>Tata letak rapi berstandar cetak publikasi BPS, cocok sebagai lampiran laporan resmi dan dokumen arsip.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-unlock-keyhole"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Akses Unduhan Bebas & Instan</h6>
                                        <p>Unduh langsung tanpa proses registrasi yang rumit, bebas biaya, dan tanpa batasan kuota berkas.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-1">
                                <a href="<?= base_url('home/search') ?>" class="btn-opd-expand text-decoration-none" style="text-decoration: none !important; border-bottom: none !important; padding: 11px 30px; font-size: 0.92rem;">
                                    <span style="text-decoration: none !important; border-bottom: none !important;">Unduh Dataset Sekarang</span>
                                    <i class="fa-solid fa-arrow-right" style="text-decoration: none !important; border-bottom: none !important;"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: Download Formats Showcase -->
                        <div class="col-lg-5">
                            <div class="kc-preview-card">
                                <div class="kc-preview-header">
                                    <div class="kc-window-dots">
                                        <span class="dot dot-red"></span>
                                        <span class="dot dot-yellow"></span>
                                        <span class="dot dot-green"></span>
                                    </div>
                                    <span class="kc-preview-tag">
                                        <i class="fa-solid fa-file-arrow-down me-1"></i> Format Tersedia
                                    </span>
                                </div>
                                <div class="kc-preview-body">
                                    <!-- Format Cards -->
                                    <div class="sim-format-list">
                                        <div class="sim-format-item">
                                            <div class="sim-format-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                                <i class="fa-solid fa-file-excel"></i>
                                            </div>
                                            <div class="sim-format-info">
                                                <div class="sim-format-name">Microsoft Excel (.xlsx)</div>
                                                <div class="sim-format-desc">Format tabel data siap olah</div>
                                            </div>
                                            <span class="sim-format-chip text-success">Editable</span>
                                        </div>

                                        <div class="sim-format-item">
                                            <div class="sim-format-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </div>
                                            <div class="sim-format-info">
                                                <div class="sim-format-name">Dokumen Resmi (.pdf)</div>
                                                <div class="sim-format-desc">Layout standar cetak & arsip</div>
                                            </div>
                                            <span class="sim-format-chip text-danger">Official</span>
                                        </div>

                                        <div class="sim-format-item">
                                            <div class="sim-format-icon" style="background: rgba(21, 70, 121, 0.1); color: var(--bps-blue);">
                                                <i class="fa-solid fa-file-lines"></i>
                                            </div>
                                            <div class="sim-format-info">
                                                <div class="sim-format-name">Ringkasan Metadata (.txt)</div>
                                                <div class="sim-format-desc">Definisi variabel & sumber</div>
                                            </div>
                                            <span class="sim-format-chip text-primary">Metadata</span>
                                        </div>
                                    </div>

                                    <!-- Bottom Metric Strip -->
                                    <div class="sim-metrics-strip mt-3">
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">100%</span>
                                            <span class="sim-metric-lbl">Gratis & Terbuka</span>
                                        </div>
                                        <div class="sim-metric-divider"></div>
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">Tanpa Kuota</span>
                                            <span class="sim-metric-lbl">Bebas Akses</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Terintegrasi API -->
            <div class="tab-pane fade" id="pane-api" role="tabpanel" aria-labelledby="tab-api">
                <div class="keunggulan-content-box">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="stats-pill-badge mb-2">
                                <i class="fa-solid fa-network-wired me-1.5" style="color: var(--bps-blue);"></i> Konektivitas Sistem
                            </div>
                            <h3 class="kc-title">Koneksi Host-to-Host & Sinkronisasi Real-Time</h3>
                            <p class="kc-desc">
                                Infrastruktur sistem yang terhubung langsung dengan server pusat pemerintah provinsi, menjamin distribusi dataset yang cepat, otomatis, dan konsisten.
                            </p>

                            <div class="kc-highlight-list mb-4">
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Sinkronisasi Otomatis</h6>
                                        <p>Setiap penambahan atau pembaruan dataset pada portal utama langsung terefleksi di sistem tanpa penundaan waktu.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-code"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Arsitektur Terstandar REST API</h6>
                                        <p>Dirancang modular untuk memudahkan integrasi data ke aplikasi eksternal, dashboard pimpinan, maupun platform Satu Data.</p>
                                    </div>
                                </div>
                                <div class="kc-highlight-item">
                                    <div class="kc-highlight-icon">
                                        <i class="fa-solid fa-lock"></i>
                                    </div>
                                    <div class="kc-highlight-text">
                                        <h6>Keamanan Jalur Distribusi Data</h6>
                                        <p>Koneksi terlindungi protokol enkripsi modern untuk menjaga keutuhan dan mencegah manipulasi pihak luar.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-1">
                                <a href="<?= base_url('home/search') ?>" class="btn-opd-expand text-decoration-none" style="text-decoration: none !important; border-bottom: none !important; padding: 11px 30px; font-size: 0.92rem;">
                                    <span style="text-decoration: none !important; border-bottom: none !important;">Akses Portal Data</span>
                                    <i class="fa-solid fa-arrow-right" style="text-decoration: none !important; border-bottom: none !important;"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: API Code / Endpoint Mockup -->
                        <div class="col-lg-5">
                            <div class="kc-preview-card">
                                <div class="kc-preview-header">
                                    <div class="kc-window-dots">
                                        <span class="dot dot-red"></span>
                                        <span class="dot dot-yellow"></span>
                                        <span class="dot dot-green"></span>
                                    </div>
                                    <span class="kc-preview-tag" style="background: rgba(21, 70, 121, 0.08); color: var(--bps-blue);">
                                        <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.6rem;"></i> Live API Status
                                    </span>
                                </div>
                                <div class="kc-preview-body">
                                    <!-- Endpoint Badge -->
                                    <div class="sim-endpoint-bar mb-3">
                                        <span class="sim-method">GET</span>
                                        <span class="sim-url">/api/v1/dataset/sektoral</span>
                                        <span class="sim-status">200 OK</span>
                                    </div>

                                    <!-- Code Box -->
                                    <div class="sim-code-box mb-3">
                                        <div class="sim-code-line"><span class="code-k">{</span></div>
                                        <div class="sim-code-line ps-3"><span class="code-p">"status"</span>: <span class="code-s">"success"</span>,</div>
                                        <div class="sim-code-line ps-3"><span class="code-p">"sync"</span>: <span class="code-s">"realtime"</span>,</div>
                                        <div class="sim-code-line ps-3"><span class="code-p">"source"</span>: <span class="code-s">"OPD Jawa Tengah"</span>,</div>
                                        <div class="sim-code-line ps-3"><span class="code-p">"total"</span>: <span class="code-n">1024</span></div>
                                        <div class="sim-code-line"><span class="code-k">}</span></div>
                                    </div>

                                    <!-- Bottom Metric Strip -->
                                    <div class="sim-metrics-strip">
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">99.9%</span>
                                            <span class="sim-metric-lbl">Uptime Server</span>
                                        </div>
                                        <div class="sim-metric-divider"></div>
                                        <div class="sim-metric">
                                            <span class="sim-metric-val">&lt; 50ms</span>
                                            <span class="sim-metric-lbl">Latensi Data</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Segmented Pill Navigation */
    .feature-nav-pills {
        background: #ffffff;
        padding: 6px;
        border-radius: 50px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        display: inline-flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
    }
    .feature-nav-pills .nav-link {
        border-radius: 50px;
        padding: 10px 22px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.92rem;
        color: #64748b;
        background: transparent;
        border: 1px solid transparent;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
    }
    .feature-nav-pills .nav-link:hover {
        color: var(--bps-blue);
        background: #f8fafc;
    }
    .feature-nav-pills .nav-link.active {
        background: var(--bps-blue) !important;
        color: #ffffff !important;
        border-color: var(--bps-blue) !important;
        box-shadow: 0 4px 14px rgba(21, 70, 121, 0.25);
    }
    .feature-nav-pills .nav-link::after {
        display: none !important;
        content: none !important;
    }
    
    /* Main Showcase Box */
    .keunggulan-content-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        padding: 42px 45px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .keunggulan-content-box:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.07);
    }
    
    .kc-title {
        color: #0f172a;
        font-weight: 800;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 1.75rem;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }
    
    .kc-desc {
        color: #64748b;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 0.96rem;
        line-height: 1.65;
    }
    
    /* Highlight List Items */
    .kc-highlight-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .kc-highlight-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        cursor: default;
    }
    .kc-highlight-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(21, 70, 121, 0.08);
        color: var(--bps-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .kc-highlight-text h6 {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 0.96rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }
    .kc-highlight-text p {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 0;
        line-height: 1.5;
    }
    
    /* Right Preview Window Card */
    .kc-preview-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px 22px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        position: relative;
    }
    .kc-preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 16px;
    }
    .kc-window-dots {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .kc-window-dots .dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
    }
    .dot-red { background: #ef4444; }
    .dot-yellow { background: #f59e0b; }
    .dot-green { background: #10b981; }
    .kc-preview-tag {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 50px;
        background: rgba(21, 70, 121, 0.08);
        color: var(--bps-blue);
        letter-spacing: 0.3px;
    }
    
    /* Simulation Elements */
    .sim-search-bar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.88rem;
    }
    .sim-search-text {
        font-weight: 600;
        color: #0f172a;
        flex: 1;
    }
    .sim-search-badge {
        background: var(--bps-blue);
        color: white;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .sim-result-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .sim-result-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .sim-result-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: rgba(242, 101, 34, 0.1);
        color: var(--bps-orange);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.88rem;
        flex-shrink: 0;
    }
    .sim-result-info {
        flex: 1;
        min-width: 0;
    }
    .sim-result-title {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.88rem;
        color: #0f172a;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .sim-result-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 2px;
    }
    .sim-badge-year {
        background: #f1f5f9;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 600;
    }
    
    /* Metrics Strip */
    .sim-metrics-strip {
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 10px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .sim-metric {
        text-align: center;
    }
    .sim-metric-val {
        display: block;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 800;
        font-size: 1.05rem;
        color: var(--bps-blue);
    }
    .sim-metric-lbl {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 600;
    }
    .sim-metric-divider {
        width: 1px;
        height: 24px;
        background: #cbd5e1;
    }
    
    /* Trust Box */
    .sim-trust-box {
        background: rgba(16, 185, 129, 0.05);
        border: 1px solid rgba(16, 185, 129, 0.15);
        border-radius: 14px;
    }
    .sim-trust-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: white;
        color: var(--bps-green);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.15);
    }
    .sim-trust-title {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 800;
        font-size: 1rem;
        color: #0f172a;
    }
    .sim-trust-sub {
        font-size: 0.8rem;
        color: #10b981;
        font-weight: 600;
    }
    .sim-verify-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .sim-verify-item {
        font-size: 0.82rem;
        color: #334155;
        padding: 7px 10px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
    }
    
    /* Formats */
    .sim-format-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .sim-format-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        transition: transform 0.2s ease;
    }
    .sim-format-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .sim-format-info {
        flex: 1;
    }
    .sim-format-name {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.86rem;
        color: #0f172a;
    }
    .sim-format-desc {
        font-size: 0.74rem;
        color: #64748b;
    }
    .sim-format-chip {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
    
    /* Code Box */
    .sim-endpoint-bar {
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 10px;
        padding: 8px 12px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.76rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sim-method {
        background: var(--bps-orange);
        color: white;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 800;
        font-size: 0.68rem;
    }
    .sim-url {
        flex: 1;
        color: #94a3b8;
    }
    .sim-status {
        color: #10b981;
        font-weight: 700;
    }
    .sim-code-box {
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.78rem;
        line-height: 1.5;
    }
    .code-k { color: #f8fafc; }
    .code-p { color: #38bdf8; }
    .code-s { color: #34d399; }
    .code-n { color: #fbbf24; }
    
    @media (max-width: 991px) {
        .keunggulan-content-box {
            padding: 28px 22px;
        }
        .kc-title {
            font-size: 1.45rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const words = ["Temukan", "Jelajahi", "Analisis", "Unduh"];
    let i = 0;
    const dynamicWord = document.querySelector('.dynamic-word');
    
    if (dynamicWord) {
        setInterval(() => {
            dynamicWord.style.opacity = 0;
            
            setTimeout(() => {
                i = (i + 1) % words.length;
                dynamicWord.textContent = words[i];
                dynamicWord.style.opacity = 1;
            }, 300);
            
        }, 2000);
    }
});
</script>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var swiperEl = document.querySelector('.tabel-terbaru-swiper');
    
    // Prevent browser auto-scroll jumping on swiper container when cards/links are clicked or focused
    if (swiperEl) {
        swiperEl.addEventListener('scroll', function() {
            if (this.scrollTop !== 0) {
                this.scrollTop = 0;
            }
        });
        swiperEl.addEventListener('focusin', function() {
            swiperEl.scrollTop = 0;
        });
    }

    var swiper = new Swiper('.tabel-terbaru-swiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 3500,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        navigation: {
            nextEl: '.swiper-next-custom',
            prevEl: '.swiper-prev-custom',
        },
        pagination: {
            el: '.swiper-pagination-custom',
            clickable: true,
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 18,
            },
            992: {
                slidesPerView: 3,
                spaceBetween: 20,
            },
            1200: {
                slidesPerView: 4,
                spaceBetween: 22,
            }
        }
    });
});
</script>

<!-- jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('select[name="opd_search"]').select2();
    $('select[name="tahun"]').select2({
        minimumResultsForSearch: Infinity
    });

    // Hero search clear (X) button logic
    const $heroQ = $('#hero-search-q');
    const $heroClear = $('#btn-clear-hero');
    function toggleHeroClear() {
        if ($heroQ.length && $heroQ.val() && $heroQ.val().length > 0) {
            $heroClear.removeClass('d-none');
        } else {
            $heroClear.addClass('d-none');
        }
    }
    if ($heroQ.length) {
        $heroQ.on('input keyup', toggleHeroClear);
        $heroClear.on('click', function() {
            $heroQ.val('').focus();
            toggleHeroClear();
        });
        toggleHeroClear();
    }
});
</script>

