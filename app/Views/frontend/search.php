<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 Overrides */
    .select2-container {
        flex: 1;
        width: 100% !important;
        text-align: left !important;
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
        padding: 0 24px 0 0 !important;
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
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        display: none !important; /* Hide default arrow, we use fb-group ::after */
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
    /* Hover / Highlighted State: Lembut, Rata, Tanpa Border Biru Menusuk */
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        border-left: none !important;
    }
    /* Selected State: Warna tetap konsisten, tidak jumping font-weight */
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
    .page-header {
        position: relative;
        min-height: 65vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 140px 15px 105px;
        border-bottom: none;
        margin-bottom: 0;
        overflow: hidden;
    }

    /* Hero Background */
    .page-header-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('<?= base_url('aset/images/hero-search-bg.jpg') ?>');
        background-size: 100% 100%;
        background-position: center bottom;
        background-repeat: no-repeat;
        z-index: 0;
    }
    @media (max-width: 991px) {
        .page-header-bg {
            background-size: cover;
            background-position: center bottom;
        }
    }

    /* Hero Title & Typography matching Beranda */
    .hero-title {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: clamp(2.8rem, 5.5vw, 4.4rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1.15;
        max-width: 1000px;
        color: #0d2c4d;
        margin-bottom: 12px;
    }

    /* Orange Gradient matching Hero Beranda */
    .hero-orange-gradient {
        background: linear-gradient(135deg, #F26522 0%, #D65518 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
        font-size: 1.15rem;
        color: #334155;
        max-width: 680px;
        font-weight: 500;
        line-height: 1.65;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        margin: 0 auto 36px;
    }

    /* Modern Unified Search Capsule */
    .filter-bar-horizontal {
        background: #ffffff;
        border-radius: 60px;
        padding: 6px 8px 6px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 10px 30px -5px rgba(21, 70, 121, 0.12), 0 2px 8px rgba(0, 0, 0, 0.04);
        margin: 0 auto;
        position: relative;
        z-index: 10;
        max-width: 1020px;
        width: 100%;
        text-align: left;
        border: 1px solid #e2e8f0;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .filter-bar-horizontal:focus-within,
    .filter-bar-horizontal.is-focused {
        box-shadow: 0 10px 30px -5px rgba(21, 70, 121, 0.15), 0 2px 8px rgba(0, 0, 0, 0.04);
        border-color: #cbd5e1;
        transform: none !important;
    }

    .fb-divider {
        width: 1px;
        height: 32px;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    .fb-group {
        display: flex;
        align-items: center;
        background: transparent;
        border: none;
        padding: 6px 6px;
        flex: 1;
        min-width: 0;
        text-align: left;
        transition: all 0.2s;
    }

    .fb-group.fb-keyword {
        flex: 1.4;
        min-width: 210px;
    }

    .fb-group.fb-opd {
        flex: 1.8;
        min-width: 240px;
    }

    .fb-group.fb-year {
        flex: 0.9;
        min-width: 155px;
        max-width: 190px;
    }

    .fb-group i {
        color: #94a3b8;
        font-size: 1rem;
        margin-right: 8px;
        flex-shrink: 0;
        transition: color 0.2s ease;
    }

    .fb-group:focus-within i {
        color: var(--bps-blue);
    }

    .fb-group.fb-keyword:focus-within i.search-main-icon {
        color: var(--bps-orange);
    }

    /* Modern Circular Clear (X) Button */
    .btn-clear-q {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        min-width: 24px;
        min-height: 24px;
        border-radius: 50%;
        border: none;
        background: #f1f5f9;
        color: #94a3b8;
        cursor: pointer;
        padding: 0;
        margin: 0 6px 0 4px;
        flex-shrink: 0;
        transition: background-color 0.15s ease, color 0.15s ease;
        outline: none;
        box-shadow: none;
    }

    .btn-clear-q i {
        font-size: 0.75rem !important;
        margin-right: 0 !important;
        margin: 0 !important;
        color: #94a3b8 !important;
        line-height: 1;
        transition: color 0.15s ease;
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

    .fb-group input {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        font-size: 0.95rem;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        color: #0f172a;
        font-weight: 500;
    }

    .fb-group input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .fb-group select {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        font-size: 0.95rem;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        color: #0f172a;
        font-weight: 500;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        padding-right: 25px;
    }
    
    .fb-group.select-group {
        position: relative;
        cursor: pointer;
    }

    .fb-group.select-group * {
        cursor: pointer;
    }

    .fb-group.select-group:hover i,
    .fb-group.select-group:focus-within i,
    .fb-group.select-group.is-active i {
        color: var(--bps-blue);
    }
    
    .fb-group.select-group::after {
        content: '\f078';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #94a3b8;
        font-size: 0.72rem;
        transition: color 0.15s ease;
    }

    .fb-group.select-group:hover::after,
    .fb-group.select-group:focus-within::after,
    .fb-group.select-group.is-active::after {
        color: var(--bps-blue);
    }

    .btn-search-bar {
        background: var(--bps-blue);
        color: #ffffff;
        border: none;
        padding: 12px 28px;
        border-radius: 50px;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color 0.15s ease;
        cursor: pointer;
        flex-shrink: 0;
        height: auto;
    }

    .btn-search-bar:hover {
        background: #113861;
        color: #ffffff;
    }

    .btn-search-bar:focus,
    .btn-search-bar:active {
        background: var(--bps-blue);
        color: #ffffff;
        outline: none;
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
        position: relative;
        overflow: hidden;
        box-shadow: 0 3px 14px rgba(0, 0, 0, 0.03);
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        text-decoration: none;
        color: inherit;
    }

    .table-clean-card:hover, .table-modern-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
        text-decoration: none;
        color: inherit;
    }

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
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .table-clean-card:hover .tcc-btn,
    .table-modern-card:hover .tcc-btn,
    .tcc-btn:hover {
        background: var(--bps-blue);
        color: #ffffff;
        border-color: var(--bps-blue);
    }

    .tcc-btn i {
        font-size: 0.78rem;
    }

    /* Modern List Card */
    .result-list-item {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
    }

    .result-list-item:hover {
        border-color: var(--bps-blue);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transform: translateX(3px);
    }

    .result-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 15px;
    }

    .result-title {
        color: var(--bps-blue);
        font-weight: 700;
        font-size: 1.15rem;
        text-decoration: none;
        line-height: 1.4;
    }

    .result-title:hover { color: var(--bps-blue-light); }

    .result-meta {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 15px;
        background: var(--bg-body);
        padding: 10px 15px;
        border-radius: 8px;
    }

    .btn-view {
        background: var(--bg-body);
        color: var(--bps-blue);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.2s;
        white-space: nowrap;
    }

    /* Numbered List Style (Ref Image 3) */
    .list-numbered-item {
        display: flex;
        align-items: flex-start;
        padding: 25px 0;
        border-bottom: 1px solid var(--border-color);
        background: white;
        transition: background-color 0.2s;
    }
    
    .list-numbered-item:hover {
        background-color: var(--bg-body);
    }

    .ln-number {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-main);
        width: 60px;
        text-align: center;
        flex-shrink: 0;
        padding-top: 3px;
    }

    .ln-content {
        flex: 1;
        padding-right: 20px;
    }

    .ln-title {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--bps-blue);
        text-decoration: none;
        line-height: 1.5;
        display: block;
        margin-bottom: 0px;
        transition: color 0.2s ease;
    }
    
    .ln-title:hover {
        color: var(--bps-blue);
    }

    .ln-badges {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .badge-opd {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Result Count Text (Pure Text) */
    .result-count-text {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 0.95rem;
        color: #475569;
        font-weight: 500;
    }
    .result-count-text .highlight-num {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        color: var(--bps-blue);
        margin: 0 2px;
        font-size: 1rem;
    }
    
    /* Modern Creative Pagination Capsule */
    .pagination-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 45px;
        margin-bottom: 20px;
    }

    .pagination-capsule {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        padding: 6px 8px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.07), 0 0 0 1px rgba(0, 0, 0, 0.02);
        position: relative;
    }

    .pagination-divider {
        width: 1px;
        height: 22px;
        background: #e2e8f0;
        margin: 0 4px;
        flex-shrink: 0;
    }

    .page-btn {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-weight: 700;
        font-size: 0.95rem;
        color: #475569;
        background: transparent;
        border: 1px solid transparent;
        transition: background-color 0.15s ease, color 0.15s ease;
        user-select: none;
    }

    .page-btn.page-num:hover:not(.active) {
        background: #f1f5f9;
        color: var(--bps-blue);
    }

    .page-btn.page-num.active {
        background: var(--bps-blue);
        color: #ffffff;
        cursor: default;
    }

    .page-btn.page-control {
        font-size: 0.85rem;
        color: #64748b;
    }

    .page-btn.page-control:hover:not(.disabled) {
        background: #eff6ff;
        color: var(--bps-blue);
        border-color: rgba(21, 70, 121, 0.15);
    }

    .page-btn.disabled {
        opacity: 0.32;
        cursor: not-allowed;
        pointer-events: none;
    }

    .page-ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
        color: #94a3b8;
        font-size: 0.8rem;
        letter-spacing: 2px;
        font-weight: 700;
    }

    @media (max-width: 576px) {
        .pagination-capsule {
            padding: 4px 6px;
            gap: 2px;
            border-radius: 30px;
        }
        .page-btn {
            width: 36px;
            height: 36px;
            font-size: 0.88rem;
        }
        .pagination-divider {
            height: 18px;
            margin: 0 2px;
        }
    }

    .btn-view:hover {
        background: var(--bps-blue);
        color: white;
        border-color: var(--bps-blue);
    }
    #search-results-container {
        position: relative;
        transition: opacity 0.2s ease;
    }
    #search-results-container.is-loading {
        opacity: 0.55;
        pointer-events: none;
    }

    /* Modern Card List Styles */
    .result-cards-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .result-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px 24px;
        display: flex;
        align-items: center;
        gap: 22px;
        transition: all 0.25s ease;
        position: relative;
    }
    
    .result-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 4px 10px -2px rgba(0, 0, 0, 0.03);
        transform: translateY(-2px);
    }
    
    .rc-number {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 1.45rem;
        font-weight: 800;
        color: #cbd5e1;
        min-width: 38px;
        text-align: center;
        transition: color 0.25s ease;
    }
    
    .result-card:hover .rc-number {
        color: var(--bps-blue);
    }
    
    .rc-content {
        flex: 1;
    }
    
    .rc-title {
        font-family: 'Inter', "Inter Fallback", sans-serif;
        font-size: 1.12rem;
        font-weight: 700;
        color: var(--bps-blue);
        text-decoration: none;
        line-height: 1.5;
        display: block;
        margin-bottom: 12px;
        transition: color 0.2s ease;
    }
    
    .result-card:hover .rc-title,
    .rc-title:hover {
        color: #1d599b;
    }
    
    .rc-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }
    
    .rc-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .rc-badge-opd {
        background-color: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    
    .rc-badge-opd i {
        color: var(--bps-blue);
    }
    
    .rc-badge-tahun {
        background-color: rgba(21, 70, 121, 0.06);
        color: var(--bps-blue);
        border: 1px solid rgba(21, 70, 121, 0.12);
    }
    
    .rc-action {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-modern-arrow {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background-color: #f8fafc;
        color: var(--bps-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        border: 1px solid #e2e8f0;
    }
    
    .result-card:hover .btn-modern-arrow,
    .btn-modern-arrow:hover {
        background-color: var(--bps-blue);
        color: #ffffff;
        border-color: var(--bps-blue);
    }
    
    @media (max-width: 768px) {
        .result-card {
            flex-direction: column;
            align-items: flex-start;
            padding: 20px;
            gap: 16px;
        }
        .rc-number {
            font-size: 1.25rem;
            text-align: left;
        }
        .rc-action {
            display: none;
        }
    }

    @media (max-width: 991px) {
        .page-header {
            padding: 100px 15px 50px;
            min-height: auto;
        }
        .filter-bar-horizontal {
            border-radius: 20px;
            padding: 16px;
            flex-direction: column;
            gap: 12px;
        }
        .fb-divider {
            display: none;
        }
        .fb-group {
            width: 100%;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
        }
        .fb-group.fb-year {
            max-width: 100%;
        }
        .btn-search-bar {
            width: 100%;
            height: 48px;
            border-radius: 12px;
        }
    }
</style>

<div class="page-header text-center">
    <!-- Hero Background Container -->
    <div class="page-header-bg"></div>

    <div class="container hero-container position-relative" style="z-index: 2; width: 100%; max-width: 1140px;">
        <!-- JUDUL -->
        <h1 class="hero-title mx-auto mb-3">
            Jelajah <span class="hero-orange-gradient">Data</span>
        </h1>

        <!-- SUB JUDUL -->
        <p class="hero-subtitle mx-auto mb-4">
            Saring dan temukan indikator statistik Daerah Dalam Angka dengan mudah.
        </p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-4 mx-auto mb-4 text-start" role="alert" style="max-width: 1020px; background-color: #fffbeb; border: 1px solid #fef3c7 !important; color: #92400e;">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-triangle-exclamation fs-5 me-3 text-warning"></i>
                    <div class="flex-grow-1 fw-medium small"><?= esc(session()->getFlashdata('error')) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- SEARCH BAR (Modern Unified Capsule) -->
        <div class="mx-auto" style="max-width: 1020px; width: 100%;">
            <form action="<?= base_url('home/search') ?>" method="GET" id="search-form">
                <div class="filter-bar-horizontal">
                    
                    <div class="fb-group fb-keyword">
                        <i class="fa-solid fa-magnifying-glass search-main-icon"></i>
                        <input type="text" name="q" id="search-q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Cari judul atau nomor tabel..." autocomplete="off">
                        <button type="button" class="btn-clear-q <?= empty($q) ? 'd-none' : '' ?>" id="btn-clear-q" title="Hapus pencarian">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="fb-divider d-none d-lg-block"></div>

                    <div class="fb-group select-group fb-opd">
                        <i class="fa-solid fa-building-columns"></i>
                        <select name="opd_search" id="select-opd">
                            <option value="">Semua OPD</option>
                            <?php foreach($unique_units as $u): ?>
                                <?php if (!empty($u->unitkerja_ind)): ?>
                                    <option value="<?= htmlspecialchars($u->unitkerja_ind) ?>" <?= ((isset($opd_search) && $opd_search === $u->unitkerja_ind) || (!empty($_GET['opd_search']) && $_GET['opd_search'] === $u->unitkerja_ind)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u->unitkerja_ind) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fb-divider d-none d-lg-block"></div>

                    <div class="fb-group select-group fb-year">
                        <i class="fa-regular fa-calendar-days"></i>
                        <select name="tahun" id="select-tahun">
                            <option value="">Semua Tahun</option>
                            <?php 
                            $years_list = !empty($available_years) ? $available_years : [2026, 2025];
                            foreach ($years_list as $y): 
                            ?>
                                <option value="<?= htmlspecialchars($y) ?>" <?= ((isset($tahun) && (string)$tahun === (string)$y) || (!empty($_GET['tahun']) && (string)$_GET['tahun'] === (string)$y)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($y) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-search-bar" id="btn-submit-search">
                        <i class="fa-solid fa-magnifying-glass search-btn-icon"></i>
                        <i class="fa-solid fa-spinner fa-spin search-btn-spinner d-none"></i>
                        <span>Cari Data</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<div class="container py-5" id="search-results-container">

    <?php
    $current_page = $page_num ?? 1;
    $per_page_count = $per_page ?? 12;
    $total_count = $total_rows ?? count($results);

    if (!empty($results)) {
        $start_item = (($current_page - 1) * $per_page_count) + 1;
        $end_item = min($total_count, $start_item + count($results) - 1);
    } else {
        $start_item = 0;
        $end_item = 0;
    }
    ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <!-- Result Count Text (Pure Text) -->
        <div class="result-count-text">
            <?php if (!empty($results)): ?>
                Menampilkan <span class="highlight-num"><?= number_format($start_item, 0, ',', '.') ?>–<?= number_format($end_item, 0, ',', '.') ?></span> dari <span class="highlight-num"><?= number_format($total_count, 0, ',', '.') ?></span> tabel data
            <?php else: ?>
                <span>Tidak ada tabel data ditemukan</span>
            <?php endif; ?>
        </div>

        <!-- View Toggle Buttons -->
        <div class="view-toggle d-flex bg-white p-1 rounded-pill shadow-sm" style="border: 1px solid #e2e8f0;">
            <button class="btn btn-sm view-btn grid-btn" onclick="switchView('grid')" title="Tampilan Grid" style="border-radius: 50px; width: 36px; height: 32px; background: transparent; color: var(--bps-blue); display: flex; align-items: center; justify-content: center; padding: 0; transition: 0.2s;"><i class="fa-solid fa-border-all"></i></button>
            <button class="btn btn-sm view-btn list-btn" onclick="switchView('list')" title="Tampilan List" style="border-radius: 50px; width: 36px; height: 32px; background: var(--bps-blue); color: white; display: flex; align-items: center; justify-content: center; padding: 0;"><i class="fa-solid fa-list"></i></button>
        </div>
    </div>

    <!-- Main Content List -->
    <div class="row">
        <div class="col-12">
            <?php if (!empty($results)): ?>
                <?php $start_no = (($page_num - 1) * $per_page) + 1; ?>

                <!-- GRID VIEW -->
                <div id="view-grid" class="row g-4 d-none mb-4">
                    <?php foreach ($results as $idx => $row): ?>
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
                            $target = '';
                        ?>
                        <div class="col-lg-3 col-md-6">
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
                    <?php endforeach; ?>
                </div>

                <!-- LIST VIEW -->
                <div id="view-list" class="result-cards-container">
                    <?php 
                    // Calculate starting number for pagination
                    $start_no = (($page_num - 1) * $per_page) + 1;
                    foreach ($results as $idx => $row): 
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
                        $target = '';
                    ?>
                        <div class="result-card">
                            <div class="rc-number">
                                <?= str_pad($start_no + $idx, 2, '0', STR_PAD_LEFT) ?>
                            </div>
                            
                            <div class="rc-content">
                                <a href="<?= $href ?>" <?= $target ?> class="rc-title">
                                    <?= htmlspecialchars($row->judul_ind) ?>
                                </a>
                                
                                <div class="rc-meta">
                                    <span class="rc-badge rc-badge-opd">
                                        <i class="fa-solid fa-building"></i> 
                                        <?= htmlspecialchars($row->unitkerja_ind ?? '-') ?>
                                    </span>
                                    <span class="rc-badge rc-badge-tahun">
                                        <i class="fa-regular fa-calendar"></i>
                                        <?= htmlspecialchars($row->tahun ?? '-') ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="rc-action">
                                <a href="<?= $href ?>" <?= $target ?> class="btn-modern-arrow" aria-label="Lihat Detail">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Modern Creative Pagination Capsule -->
                <?php if (isset($pager) && isset($total_rows) && isset($per_page) && $total_rows > $per_page): ?>
                    <?php
                    $total_pages = ceil($total_rows / $per_page);
                    $current_page = $page_num;

                    // Preserve existing query parameters (q, opd_search, tahun, etc.)
                    $queryParams = $_GET ?? [];
                    $makePageUrl = function($p) use ($queryParams) {
                        $params = $queryParams;
                        $params['page'] = $p;
                        return base_url('home/search') . '?' . http_build_query($params);
                    };
                    ?>
                    <div class="pagination-container">
                        <!-- Floating Capsule Navigation -->
                        <nav aria-label="Navigasi Halaman">
                            <div class="pagination-capsule">
                                <!-- First & Prev Controls -->
                                <?php if ($current_page > 1): ?>
                                    <a href="<?= $makePageUrl(1) ?>" class="page-btn page-control" title="Halaman Pertama" aria-label="First">
                                        <i class="fa-solid fa-angles-left"></i>
                                    </a>
                                    <a href="<?= $makePageUrl($current_page - 1) ?>" class="page-btn page-control" title="Halaman Sebelumnya" aria-label="Previous">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                    <div class="pagination-divider"></div>
                                <?php else: ?>
                                    <span class="page-btn page-control disabled" aria-disabled="true">
                                        <i class="fa-solid fa-angles-left"></i>
                                    </span>
                                    <span class="page-btn page-control disabled" aria-disabled="true">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </span>
                                    <div class="pagination-divider"></div>
                                <?php endif; ?>

                                <!-- Page Number Buttons -->
                                <?php 
                                $range = 2; // Show 2 pages before and after
                                $start_p = max(1, $current_page - $range);
                                $end_p = min($total_pages, $current_page + $range);
                                
                                if ($start_p > 1): ?>
                                    <a href="<?= $makePageUrl(1) ?>" class="page-btn page-num">1</a>
                                    <?php if ($start_p > 2): ?>
                                        <span class="page-ellipsis">•••</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_p; $i <= $end_p; $i++): ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="page-btn page-num active" aria-current="page"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="<?= $makePageUrl($i) ?>" class="page-btn page-num"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($end_p < $total_pages): ?>
                                    <?php if ($end_p < $total_pages - 1): ?>
                                        <span class="page-ellipsis">•••</span>
                                    <?php endif; ?>
                                    <a href="<?= $makePageUrl($total_pages) ?>" class="page-btn page-num"><?= $total_pages ?></a>
                                <?php endif; ?>

                                <!-- Next & Last Controls -->
                                <div class="pagination-divider"></div>
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="<?= $makePageUrl($current_page + 1) ?>" class="page-btn page-control" title="Halaman Berikutnya" aria-label="Next">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                    <a href="<?= $makePageUrl($total_pages) ?>" class="page-btn page-control" title="Halaman Terakhir" aria-label="Last">
                                        <i class="fa-solid fa-angles-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="page-btn page-control disabled" aria-disabled="true">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </span>
                                    <span class="page-btn page-control disabled" aria-disabled="true">
                                        <i class="fa-solid fa-angles-right"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <img src="<?= base_url('aset/img/empty-box.svg') ?>" alt="Data tidak ditemukan" style="max-width: 200px; opacity: 0.6; margin-bottom: 20px;">
                    <h4 class="text-muted fw-bold">Tidak Ada Data Ditemukan</h4>
                    <p class="text-muted">Maaf, tabel dengan kata kunci atau filter tersebut tidak tersedia.</p>
                    <a href="<?= base_url('home/search') ?>" class="btn btn-outline-primary mt-3 rounded-pill px-4">Tampilkan Semua Data</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchView(view) {
    const gridEl = document.getElementById('view-grid');
    const listEl = document.getElementById('view-list');
    const gridBtn = document.querySelector('.grid-btn');
    const listBtn = document.querySelector('.list-btn');

    if (view === 'grid') {
        if (gridEl) gridEl.classList.remove('d-none');
        if (listEl) listEl.classList.add('d-none');
        
        if (gridBtn) {
            gridBtn.style.background = 'var(--bps-blue)';
            gridBtn.style.color = 'white';
        }
        if (listBtn) {
            listBtn.style.background = 'transparent';
            listBtn.style.color = 'var(--bps-blue)';
        }
    } else {
        if (gridEl) gridEl.classList.add('d-none');
        if (listEl) listEl.classList.remove('d-none');
        
        if (listBtn) {
            listBtn.style.background = 'var(--bps-blue)';
            listBtn.style.color = 'white';
        }
        if (gridBtn) {
            gridBtn.style.background = 'transparent';
            gridBtn.style.color = 'var(--bps-blue)';
        }
    }
    
    // Save the user's preference in their browser
    localStorage.setItem('dda_view_pref', view);
}

// Automatically apply the saved preference when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('dda_view_pref');
    if (savedView === 'grid') {
        switchView('grid');
    }
});
</script>

<!-- jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    const $opdSelect = $('select[name="opd_search"]');
    const $tahunSelect = $('select[name="tahun"]');
    const $qInput = $('input[name="q"]');
    const $form = $('#search-form');

    $opdSelect.select2({
        width: '100%'
    });
    $tahunSelect.select2({
        width: '100%',
        minimumResultsForSearch: Infinity // Hides the search box for the year dropdown
    });

    // Select2 open/close state handling without jumping or shaking
    $('select[name="opd_search"], select[name="tahun"]').on('select2:opening select2:open', function() {
        $('.filter-bar-horizontal').addClass('is-focused');
        $(this).closest('.fb-group').addClass('is-active');
    }).on('select2:closing select2:close', function() {
        $('.filter-bar-horizontal').removeClass('is-focused');
        $(this).closest('.fb-group').removeClass('is-active');
    });

    // Clicking anywhere in the select-group box (including the icon) opens Select2
    $('.fb-group.select-group').on('click', function(e) {
        if (!$(e.target).closest('.select2-container').length) {
            $(this).find('select').select2('open');
        }
    });

    // Fallback: clear focused state if user clicks outside
    $(document).on('mousedown', function(e) {
        if (!$(e.target).closest('.filter-bar-horizontal, .select2-container--open, .select2-dropdown').length) {
            $('.filter-bar-horizontal').removeClass('is-focused');
            $('.fb-group.select-group').removeClass('is-active');
        }
    });

    // Toggle Clear (X) button for keyword input
    function toggleClearBtn() {
        const val = $qInput.val();
        if (val && val.length > 0) {
            $('#btn-clear-q').removeClass('d-none');
        } else {
            $('#btn-clear-q').addClass('d-none');
        }
    }
    toggleClearBtn();

    $('#btn-clear-q').on('click', function() {
        $qInput.val('').focus();
        toggleClearBtn();
        executeLiveSearch(null, false);
    });

    // ==========================================
    // REACTIVE LIVE SEARCH & FILTER ENGINE
    // ==========================================
    let searchDebounceTimer = null;
    let activeAjax = null;

    function executeLiveSearch(url = null, shouldScroll = false) {
        clearTimeout(searchDebounceTimer);
        
        let targetUrl = url;
        if (!targetUrl) {
            const baseUrl = $form.attr('action') || window.location.pathname;
            const qVal = $qInput.val().trim();
            const opdVal = $opdSelect.val();
            const tahunVal = $tahunSelect.val();
            
            const params = new URLSearchParams();
            if (qVal) params.set('q', qVal);
            if (opdVal) params.set('opd_search', opdVal);
            if (tahunVal) params.set('tahun', tahunVal);
            
            const qs = params.toString();
            targetUrl = baseUrl + (qs ? '?' + qs : '');
        }

        // Update URL in address bar without full page reload
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', targetUrl);
        }

        // Show loading state
        $('#search-results-container').addClass('is-loading');
        $('.search-btn-icon').addClass('d-none');
        $('.search-btn-spinner').removeClass('d-none');

        // Cancel previous request if still in flight
        if (activeAjax && activeAjax.readyState !== 4) {
            activeAjax.abort();
        }

        activeAjax = $.ajax({
            url: targetUrl,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                // Parse returned HTML and extract only the results section
                let newInner = '';
                try {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(response, 'text/html');
                    const newEl = doc.getElementById('search-results-container');
                    if (newEl) newInner = newEl.innerHTML;
                } catch (err) {}

                if (!newInner) {
                    const $wrap = $('<div>').html(response);
                    const $found = $wrap.find('#search-results-container');
                    if ($found.length) newInner = $found.html();
                }

                if (newInner) {
                    const targetEl = document.getElementById('search-results-container');
                    if (targetEl) {
                        targetEl.innerHTML = newInner;
                    }
                }

                // Re-apply view preference (grid vs list)
                const savedView = localStorage.getItem('dda_view_pref') || 'list';
                switchView(savedView);

                if (shouldScroll) {
                    const el = document.getElementById('search-results-container');
                    if (el) {
                        const topPos = el.getBoundingClientRect().top + window.pageYOffset - 90;
                        window.scrollTo({ top: topPos, behavior: 'smooth' });
                    }
                }
            },
            error: function(xhr, status) {
                if (status !== 'abort') {
                    console.error('Search request failed:', status);
                }
            },
            complete: function() {
                $('#search-results-container').removeClass('is-loading');
                $('.search-btn-icon').removeClass('d-none');
                $('.search-btn-spinner').addClass('d-none');
            }
        });
    }

    // 1. Live search as user types: searches table data titles & numbers immediately (debounced 250ms)
    $qInput.on('input keyup search paste', function(e) {
        if (e.type === 'keyup' && [9, 16, 17, 18, 27, 37, 38, 39, 40].includes(e.which)) {
            return;
        }
        toggleClearBtn();
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function() {
            executeLiveSearch(null, false);
        }, 250);
    });

    // 2. Live filter immediately when OPD dropdown changes (Select2 select, clear, and change)
    $opdSelect.on('select2:select select2:clear change', function() {
        executeLiveSearch(null, false);
    });

    // 3. Live filter immediately when Tahun dropdown changes (Select2 select, clear, and change)
    $tahunSelect.on('select2:select select2:clear change', function() {
        executeLiveSearch(null, false);
    });

    // 4. Form submission (pressing Enter or clicking Cari Data button)
    $form.on('submit', function(e) {
        e.preventDefault();
        clearTimeout(searchDebounceTimer);
        executeLiveSearch(null, false);
    });

    // 5. Intercept pagination clicks for seamless AJAX page transitions
    $(document).on('click', '#search-results-container .page-btn:not(.disabled):not(.active)', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (href && href !== '#') {
            executeLiveSearch(href, true);
        }
    });

    // 6. Intercept "Tampilkan Semua Data" reset button in empty state
    $(document).on('click', '#search-results-container a.btn-outline-primary', function(e) {
        if ($(this).text().includes('Tampilkan Semua Data')) {
            e.preventDefault();
            $qInput.val('');
            toggleClearBtn();
            $opdSelect.val('').trigger('change.select2');
            $tahunSelect.val('').trigger('change.select2');
            executeLiveSearch($(this).attr('href'), false);
        }
    });

    // 7. Support browser Back and Forward history buttons
    window.addEventListener('popstate', function() {
        executeLiveSearch(window.location.href, false);
        const urlParams = new URLSearchParams(window.location.search);
        $qInput.val(urlParams.get('q') || '');
        toggleClearBtn();
        $opdSelect.val(urlParams.get('opd_search') || '').trigger('change.select2');
        $tahunSelect.val(urlParams.get('tahun') || '').trigger('change.select2');
    });
});
</script>
