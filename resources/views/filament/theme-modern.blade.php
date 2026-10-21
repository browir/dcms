<style>
    /* ═══ DCMS — tampilan modern ════════════════════════════════════════════
       Lapisan desain di atas CSS panel lama (AdminPanelProvider). Semua warna lewat
       token di :root — mode gelap cukup mengganti token (theme-dark.blade.php).
       Skala: spasi 4/8/12/16/20/24/32 · radius 8/12/16 · teks 12/13/14/16/26. */
    :root {
        --ui-bg: #f4f6fa;
        --ui-bg-glow: rgba(37, 99, 235, .07);
        --ui-surface: #ffffff;
        --ui-surface-2: #f8fafc;
        --ui-hover: #f5f8ff;
        --ui-line: #e6eaf0;
        --ui-line-soft: #eef1f5;
        --ui-ink: #0f172a;
        --ui-ink-2: #334155;
        --ui-muted: #64748b;
        --ui-faint: #94a3b8;
        --ui-primary: #2563eb;
        --ui-primary-soft: #eff6ff;
        --ui-ring: rgba(37, 99, 235, .18);
        --ui-radius-lg: 16px;
        --ui-radius: 12px;
        --ui-radius-sm: 8px;
        --ui-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 1px 3px rgba(16, 24, 40, .05);
        --ui-shadow-lg: 0 16px 40px -16px rgba(16, 24, 40, .22);
    }

    /* ── Latar: netral lembut, tanpa animasi ── */
    body, .fi-main, .fi-layout {
        background: radial-gradient(1100px 380px at 50% -140px, var(--ui-bg-glow), transparent 70%), var(--ui-bg) !important;
        background-attachment: fixed !important;
        animation: none !important;
    }
    body { color: var(--ui-ink); }

    /* ── Header halaman: ringan, tanpa blok navy ── */
    .fi-header {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 4px 0 0 !important;
        margin: 12px 0 24px !important;
        border-radius: 0 !important;
        overflow: visible !important;
        animation: none !important;
    }
    .fi-header::before { display: none !important; }
    .fi-header-heading {
        color: var(--ui-ink) !important;
        font-size: 26px !important;
        font-weight: 700 !important;
        letter-spacing: -.02em !important;
        line-height: 1.25 !important;
    }
    .fi-header-subheading {
        color: var(--ui-muted) !important;
        font-size: 14px !important;
        margin-top: 6px !important;
        max-width: 72ch;
        line-height: 1.55 !important;
    }
    .fi-header .fi-breadcrumbs { margin-bottom: 6px; }
    .fi-header .fi-breadcrumbs *,
    .fi-header .fi-breadcrumbs-item-label { color: var(--ui-muted) !important; font-size: 13px !important; }
    .fi-header .fi-breadcrumbs-item-separator { color: var(--ui-faint) !important; }
    /* Tombol sekunder (Panduan, warna info/gray) jadi tombol bergaris; aksi utama tetap biru */
    .fi-header .fi-btn.fi-color-info,
    .fi-header .fi-btn.fi-color-gray {
        background: var(--ui-surface) !important;
        color: var(--ui-ink-2) !important;
        border: 1px solid var(--ui-line) !important;
        box-shadow: var(--ui-shadow) !important;
    }
    .fi-header .fi-btn.fi-color-info:hover,
    .fi-header .fi-btn.fi-color-gray:hover { background: var(--ui-surface-2) !important; }
    .fi-header .fi-btn.fi-color-info .fi-icon,
    .fi-header .fi-btn.fi-color-gray .fi-icon { color: var(--ui-muted) !important; }

    /* ── Tombol ── */
    .fi-btn { border-radius: 10px !important; font-weight: 600 !important; }
    .fi-header .fi-btn { min-height: 40px; padding-inline: 16px !important; }

    /* ── Tab daftar: rata kiri, segmented ── */
    .fi-page .fi-tabs {
        margin-inline: 0 !important;
        max-width: 100%;
        background: var(--ui-surface) !important;
        border: 1px solid var(--ui-line) !important;
        border-radius: var(--ui-radius) !important;
        box-shadow: var(--ui-shadow) !important;
        padding: 4px !important;
        gap: 2px !important;
        overflow-x: auto;
    }
    .fi-page .fi-tabs-item {
        border-radius: var(--ui-radius-sm) !important;
        padding: 8px 14px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: var(--ui-muted) !important;
        white-space: nowrap;
    }
    .fi-page .fi-tabs-item:hover { background: var(--ui-surface-2) !important; color: var(--ui-ink-2) !important; }
    .fi-page .fi-tabs-item.fi-active { background: var(--ui-primary-soft) !important; color: var(--ui-primary) !important; font-weight: 600 !important; }
    .fi-page .fi-tabs-item .fi-badge { font-variant-numeric: tabular-nums; }

    /* ── Kartu tabel ── */
    .fi-ta {
        background: var(--ui-surface) !important;
        border: 1px solid var(--ui-line) !important;
        border-radius: var(--ui-radius-lg) !important;
        box-shadow: var(--ui-shadow) !important;
        animation: none !important;
    }
    .fi-ta-header-ctn, .fi-ta-header { background: transparent !important; }
    .fi-ta-header-toolbar { padding: 14px 20px !important; gap: 12px !important; border-bottom: 1px solid var(--ui-line) !important; }
    .fi-ta-header-toolbar .fi-input-wrp { min-height: 40px; }

    .fi-ta-table > thead > tr > th { background: var(--ui-surface-2) !important; border-bottom: 1px solid var(--ui-line) !important; }
    .fi-ta-header-cell {
        background: var(--ui-surface-2) !important;
        color: var(--ui-muted) !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        letter-spacing: .01em !important;
        text-transform: none !important;
        padding: 12px 16px !important;
        border-bottom: 1px solid var(--ui-line) !important;
        white-space: nowrap;
    }
    .fi-ta-header-cell:first-of-type, .fi-ta-cell:first-of-type { padding-inline-start: 12px !important; }
    .fi-ta-header-cell:last-of-type, .fi-ta-cell:last-of-type { padding-inline-end: 16px !important; }

    .fi-ta-row td, .fi-ta-row th,
    .fi-ta-row:nth-child(even) td { background-color: var(--ui-surface) !important; color: var(--ui-ink-2) !important; }
    .fi-ta-row { transition: background-color .15s ease !important; }
    .fi-ta-row:hover { transform: none !important; }
    .fi-ta-row:hover td { background-color: var(--ui-hover) !important; }
    .fi-ta-cell {
        padding: 0 !important;
        border-bottom: 1px solid var(--ui-line-soft) !important;
        vertical-align: middle !important;
    }
    .fi-ta-row:last-child .fi-ta-cell { border-bottom: 0 !important; }
    .fi-ta-text:not(.fi-inline),
    .fi-ta-icon:not(.fi-inline),
    .fi-ta-color:not(.fi-inline) { padding: 14px 16px !important; }
    .fi-ta-text-item { font-size: 14px; color: var(--ui-ink-2); font-variant-numeric: tabular-nums; }
    .fi-ta-text-item.fi-font-bold, .fi-ta-text-item .fi-font-bold { color: var(--ui-ink); }
    .fi-ta-text-description, .fi-ta-text .fi-ta-text-description { font-size: 12.5px !important; color: var(--ui-muted) !important; margin-top: 2px; }
    .fi-ta-actions { gap: 6px !important; }
    .fi-ta-record-checkbox, .fi-ta-page-checkbox { margin-inline: 8px; }

    /* Footer / pagination */
    .fi-ta-footer, .fi-ta .fi-pagination { padding: 12px 20px !important; }
    .fi-ta-footer { border-top: 1px solid var(--ui-line) !important; }
    .fi-ta-empty-state { padding: 56px 24px !important; }

    /* Scrollbar horizontal tipis (muncul hanya bila tabel benar-benar melebar) */
    .fi-ta-content-ctn { scrollbar-width: thin; scrollbar-color: var(--ui-line) transparent; }

    /* Panel filter (dropdown). Jumlah filter aktif sudah terlihat di bar indikator → badge angka disembunyikan. */
    .fi-ta-filters-dropdown .fi-badge { display: none !important; }
    .fi-ta-filters-dropdown .fi-dropdown-panel { border-radius: var(--ui-radius-lg) !important; box-shadow: var(--ui-shadow-lg) !important; }
    .fi-ta-filters { padding: 20px !important; }
    .fi-ta-filter-indicators { background: var(--ui-primary-soft) !important; padding: 10px 20px !important; }

    /* ── Badge: pil lembut ── */
    .fi-badge {
        border-radius: 999px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: 3px 10px !important;
        animation: none !important;
    }

    /* ── Kartu / section form & detail ── */
    .fi-section {
        background: var(--ui-surface) !important;
        border: 1px solid var(--ui-line) !important;
        border-radius: var(--ui-radius-lg) !important;
        box-shadow: var(--ui-shadow) !important;
    }
    .fi-section > .fi-section-header-ctn, .fi-section-header {
        background: transparent !important;
        padding: 18px 24px !important;
        border-bottom: 1px solid var(--ui-line-soft) !important;
    }
    .fi-section-header::before { display: none !important; }
    .fi-section-header-heading { font-size: 15px !important; font-weight: 650 !important; color: var(--ui-ink) !important; letter-spacing: -.005em; }
    .fi-section-header-description { font-size: 13px !important; color: var(--ui-muted) !important; margin-top: 2px !important; }
    .fi-section-content { padding: 24px !important; }
    .fi-section-header .fi-icon { color: var(--ui-primary) !important; }

    /* Field form */
    .fi-fo-field-label-content { font-size: 13.5px !important; font-weight: 600 !important; color: var(--ui-ink-2) !important; }
    /* Filament menggambar garis input lewat box-shadow (ring) */
    .fi-input-wrp {
        border-radius: 10px !important;
        background: var(--ui-surface) !important;
        box-shadow: 0 0 0 1px var(--ui-line) !important;
        min-height: 42px;
    }
    .fi-input-wrp:focus-within { box-shadow: 0 0 0 1px var(--ui-primary), 0 0 0 4px var(--ui-ring) !important; }
    .fi-input-wrp.fi-disabled { background: var(--ui-surface-2) !important; }
    .fi-fo-field-wrp-helper-text, .fi-fo-field-wrp-hint { font-size: 12.5px !important; color: var(--ui-muted) !important; }

    /* Detail (infolist) */
    .fi-in-entry-label { font-size: 12.5px !important; font-weight: 600 !important; color: var(--ui-muted) !important; }
    .fi-in-text-item { font-size: 14px; color: var(--ui-ink); }

    /* ── Modal: kepala terang, sudut besar ── */
    .fi-modal-window { border-radius: 20px !important; box-shadow: var(--ui-shadow-lg) !important; }
    .fi-modal-header {
        background: transparent !important;
        padding: 22px 24px 8px !important;
        border-bottom: 0 !important;
    }
    .fi-modal-heading { color: var(--ui-ink) !important; font-size: 18px !important; font-weight: 700 !important; }
    .fi-modal-description { color: var(--ui-muted) !important; }
    .fi-modal-close-btn, .fi-modal-header .fi-icon-btn { color: var(--ui-muted) !important; }
    .fi-modal-content { padding: 16px 24px !important; }
    .fi-modal-footer { padding: 16px 24px 22px !important; }

    /* ── Kartu statistik di atas daftar Dokumen ── */
    .dms-grid { gap: 16px !important; }
    .dms-card {
        background: var(--ui-surface) !important;
        border: 1px solid var(--ui-line) !important;
        border-radius: var(--ui-radius-lg) !important;
        box-shadow: var(--ui-shadow) !important;
    }
    .dms-accent-bar { display: none !important; }
    .dms-card-body { padding: 18px 20px !important; }
    .dms-card-head { flex-direction: row-reverse !important; justify-content: flex-end !important; align-items: center !important; gap: 14px !important; }
    .dms-icon { width: 44px !important; height: 44px !important; border-radius: 12px !important; flex: none; }
    .dms-icon svg { width: 20px !important; height: 20px !important; }
    .dms-card:hover .dms-icon { transform: none !important; }
    .dms-num { font-size: 26px !important; line-height: 1.1 !important; color: var(--ui-ink) !important; letter-spacing: -.02em; }
    .dms-label { font-size: 13px !important; text-transform: none !important; letter-spacing: 0 !important; font-weight: 500 !important; color: var(--ui-muted) !important; margin-top: 2px !important; }
    .dms-sub { font-size: 12px !important; color: var(--ui-faint) !important; margin-top: 10px !important; }

    /* ── Ruang antar blok halaman ── */
    .fi-page-content { row-gap: 24px !important; }

    /* Tab bisa digeser di layar sempit tanpa scrollbar yang terlihat */
    .fi-page .fi-tabs { scrollbar-width: none; }
    .fi-page .fi-tabs::-webkit-scrollbar { display: none; }

    @media (max-width: 767px) {
        .fi-header-heading { font-size: 22px !important; }
        .fi-header .fi-btn { white-space: normal !important; text-align: center; font-size: 13px !important; line-height: 1.25 !important; padding-inline: 12px !important; }
        .fi-header .fi-btn .fi-icon { flex: none; }
        .fi-header { margin: 8px 0 16px !important; }
        .fi-section-content { padding: 16px !important; }
        .fi-section > .fi-section-header-ctn, .fi-section-header { padding: 14px 16px !important; }
        .dms-card-body { padding: 14px !important; }
        .dms-num { font-size: 20px !important; }
        .dms-icon { width: 36px !important; height: 36px !important; }
    }

    /* Section form mengisi tinggi kolom grid (dulu lewat override view section) */
    .fi-sc-section > .fi-section { height: 100%; display: flex; flex-direction: column; flex: 1 1 0%; }

    /* ── Kartu baris (dipakai tabel Kegiatan & Nomor Surat) ── */
    .dcms-desktop-title-view { display: flex; }
    .dcms-mobile-card { display: none; }
    .dcms-dtitle { display: flex; flex-direction: column; gap: 2px; }
    .dcms-dtitle-main { font-size: 14px; font-weight: 600; color: var(--ui-ink); }
    .dcms-dtitle-sub { font-size: 12.5px; color: var(--ui-muted); }
    .dcms-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: -.01em; }
    .dcms-mcard {
        background: var(--ui-surface); border: 1px solid var(--ui-line); border-radius: 14px;
        box-shadow: var(--ui-shadow); overflow: hidden;
    }
    .dcms-mcard-head { display: flex; align-items: center; gap: 12px; padding: 14px 14px 12px; }
    .dcms-mcard-heading { flex: 1; min-width: 0; }
    .dcms-mcard-title { font-size: 14px; font-weight: 700; color: var(--ui-ink); line-height: 1.35; }
    .dcms-mcard-sub { font-size: 12px; color: var(--ui-muted); margin-top: 2px; }
    .dcms-mcard-date {
        flex: none; width: 46px; padding: 4px 0; border-radius: 10px; text-align: center; line-height: 1.1;
        background: var(--ui-primary-soft); color: var(--ui-primary);
    }
    .dcms-mcard-date span { display: block; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .dcms-mcard-date strong { display: block; font-size: 18px; font-weight: 800; }
    .dcms-mcard-badge { flex: none; align-self: flex-start; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .dcms-tone-info { background: #eff6ff; color: #1d4ed8; }
    .dcms-tone-success { background: #ecfdf5; color: #047857; }
    .dcms-tone-warning { background: #fffbeb; color: #b45309; }
    .dcms-tone-danger { background: #fef2f2; color: #b91c1c; }
    .dcms-tone-gray { background: #f1f5f9; color: #475569; }
    .dcms-mcard-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px;
        padding: 12px 14px; border-top: 1px solid var(--ui-line-soft);
    }
    .dcms-mcard-span { grid-column: span 2; }
    .dcms-mcard-label { font-size: 11px; font-weight: 600; color: var(--ui-faint); margin-bottom: 2px; }
    .dcms-mcard-value { font-size: 13px; font-weight: 500; color: var(--ui-ink-2); overflow-wrap: anywhere; }
    .dcms-mcard-meta { font-size: 12px; color: var(--ui-muted); }
    .dcms-mcard-foot {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 10px 14px; border-top: 1px solid var(--ui-line-soft); background: var(--ui-surface-2);
    }
    .dcms-mcard-more, .dcms-mcard-action { font-size: 12.5px; font-weight: 600; color: var(--ui-primary); white-space: nowrap; }

    /* ══ MOBILE (< 768px) ═════════════════════════════════════════════════ */
    @media (max-width: 767.98px) {
        .dcms-desktop-title-view { display: none !important; }
        .dcms-mobile-card { display: block !important; width: 100%; }

        /* Topbar: kotak cari menyusut supaya ikon & avatar tidak terpotong */
        .fi-topbar { gap: 6px !important; padding-inline: 12px !important; }
        .fi-topbar .fi-global-search-ctn { flex: 1 1 auto !important; min-width: 0 !important; }
        .fi-topbar .fi-global-search,
        .fi-topbar .fi-global-search .fi-input-wrp { width: 100% !important; min-width: 0 !important; }
        .fi-topbar-end { gap: 2px !important; padding-inline: 0 10px !important; box-sizing: border-box; }
        .dcms-mcard-title, .dcms-mcard-sub { white-space: normal !important; overflow-wrap: anywhere; }
        .fi-ta .fi-pagination:not(:has(.fi-pagination-items)) { display: none !important; }
        .dcms-theme-toggle-btn { width: 2rem; height: 2rem; }

        /* Daftar data: kartu mengambang di atas latar, toolbar & pagination kotak sendiri */
        .fi-ta { background: transparent !important; border: 0 !important; box-shadow: none !important; overflow: visible !important; }
        .fi-ta-header-toolbar {
            background: var(--ui-surface) !important; border: 1px solid var(--ui-line) !important;
            border-radius: var(--ui-radius) !important; box-shadow: var(--ui-shadow) !important;
            padding: 10px 12px !important; margin-bottom: 12px !important;
        }
        .fi-ta-header-toolbar > * { min-width: 0; }
        /* Wadah bulk action yang semua isinya tersembunyi jangan makan tempat */
        .fi-ta-header-toolbar > .fi-ta-actions:not(:has(> :not([style*="display: none"]))) { display: none !important; }
        .fi-ta-header-toolbar .fi-ta-search-field { flex: 1 1 auto; min-width: 0; }
        .fi-ta-cell { border-bottom: 0 !important; }
        .fi-ta-row:hover td { background: transparent !important; }
        .fi-ta-footer, .fi-ta .fi-pagination {
            background: var(--ui-surface) !important; border: 1px solid var(--ui-line) !important;
            border-radius: var(--ui-radius) !important; margin-top: 4px; padding: 10px 12px !important;
        }
        .fi-ta-filter-indicators { border-radius: var(--ui-radius) !important; margin-bottom: 12px; }
        .fi-ta-empty-state { background: var(--ui-surface); border: 1px solid var(--ui-line); border-radius: var(--ui-radius-lg); }

        /* Grid kartu (Rapat): satu kolom penuh, tanpa baris "pilih semua" kosong */
        .fi-ta-content-grid { padding: 0 !important; gap: 12px !important; }
        .fi-ta-content-grid .fi-ta-record { width: 100% !important; max-width: 100% !important; }
        .fi-ta-content-header { display: none !important; }

        /* Tombol header: 2 kolom, tombol ganjil terakhir selebar penuh */
        .fi-header-actions-ctn .fi-ac > :last-child:nth-child(odd) { grid-column: 1 / -1; }

        /* Beranda: baris daftar boleh turun ke baris kedua, bukan terpotong */
        .dsh-card-head { flex-wrap: wrap; }
        .dsh-card-head .dsh-card-heading { flex-basis: calc(100% - 3rem); }
        .dsh-card-head .dsh-card-heading + * { margin-left: 2.75rem; }
        .dsh-card-head .dsh-card-heading + .dsh-count { margin-left: 0; align-self: flex-start; }
        .dsh-card-head:has(.dsh-card-heading + .dsh-count) { flex-wrap: nowrap; }
        .dsh-card-head:has(.dsh-card-heading + .dsh-count) .dsh-card-heading { flex: 1 1 auto; min-width: 0; }
        .dsh-module-detail { white-space: normal; }
        .dsh-row-compact { flex-wrap: wrap; row-gap: 4px; }
        .dsh-row-compact .dsh-number { max-width: 100%; }
        .dsh-row-compact:has(.dsh-number) .dsh-row-body { flex-basis: 100%; order: 2; }
        .dsh-row-compact:has(.dsh-number) .dsh-row-age { order: 1; margin-left: auto; }
        .dsh-row-compact:has(.dsh-row-lead) .dsh-row-body { flex: 1 1 calc(100% - 2rem); }
        .dsh-row-compact:has(.dsh-row-lead) .dsh-row-age { flex-basis: 100%; padding-left: 1.875rem; }
        .dsh-row:not(.dsh-row-compact) { flex-wrap: wrap; }
        .dsh-row:not(.dsh-row-compact) .dsh-row-body { flex: 1 1 60%; }
    }

    @media (prefers-reduced-motion: reduce) {
        .fi-page, .fi-ta, .fi-header { animation: none !important; }
    }
</style>
