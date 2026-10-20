{{-- Dark mode DCMS.
     1) public/css/dcms-dark.css — dibuat otomatis oleh `php artisan theme:build-dark`
        dari CSS kustom mode terang (jalankan ulang setelah mengubah CSS kustom).
     2) Override manual di bawah — untuk hal yang tidak bisa dipetakan otomatis. --}}
@php($darkCss = public_path('css/dcms-dark.css'))
<link rel="stylesheet" href="{{ asset('css/dcms-dark.css') }}?v={{ is_file($darkCss) ? filemtime($darkCss) : 0 }}">

<style>
    html.dark { color-scheme: dark; }

    /* ── Latar halaman ── */
    html.dark body,
    html.dark .fi-main,
    html.dark .fi-layout {
        background: linear-gradient(160deg, #070d1a 0%, #0b1530 55%, #0e1b3d 100%) !important;
        background-color: #070d1a !important;
        background-attachment: fixed !important;
    }

    /* ── Token dashboard (Beranda) ── */
    html.dark {
        --dsh-ink: #e5e7eb;
        --dsh-muted: #94a3b8;
        --dsh-faint: #64748b;
        --dsh-line: #26344d;
        --dsh-soft: #17233a;
        --dsh-card: #111a2e;
        --dsh-meeting: #60a5fa;
        --dsh-minutes: #a78bfa;
        --dsh-document: #34d399;
        --dsh-numbering: #fbbf24;
        --dsh-room: #22d3ee;
        --dsh-danger: #f87171;
        --dsh-warning: #fbbf24;
    }
    html.dark .dsh-module-icon,
    html.dark .dsh-card-icon { background: color-mix(in srgb, var(--dsh-tone) 16%, transparent) !important; }
    html.dark .dsh-prefix { background: color-mix(in srgb, var(--dsh-cat) 18%, transparent) !important; color: color-mix(in srgb, var(--dsh-cat) 55%, white) !important; }
    html.dark .dsh-btn-primary { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
    html.dark .dsh-btn-primary:hover { background: #1d4ed8 !important; }
    html.dark .dsh-module,
    html.dark .dsh-card { box-shadow: 0 1px 0 rgba(255, 255, 255, .03) inset, 0 8px 24px rgba(0, 0, 0, .25) !important; }
    html.dark .dsh-module:hover { border-color: #334155 !important; }
    html.dark .dsh-number { background: #33270f !important; border-color: #5b4517 !important; color: #fcd34d !important; }
    html.dark .dsh-block-past { background: #155e75 !important; }
    html.dark .dsh-trend-col:last-child .dsh-trend-bar { background: #10b981 !important; }

    /* ── Komponen Filament yang diberi warna terang oleh CSS kustom ── */
    html.dark .fi-ta-header-cell { color: #cbd5e1 !important; }
    html.dark .fi-ta-row:hover td { background-color: #1a2740 !important; }
    html.dark .fi-section-header-ctn { background: transparent !important; }
    html.dark .fi-input-wrp { background-color: #0f1729 !important; border-color: #26344d !important; }
    html.dark .fi-input-wrp .fi-input,
    html.dark .fi-select-input,
    html.dark .fi-input-wrp input,
    html.dark .fi-input-wrp textarea { color: #e5e7eb !important; background-color: transparent !important; }
    html.dark .fi-topbar .fi-global-search .fi-input-wrp { background: rgba(255, 255, 255, .06) !important; border-color: rgba(255, 255, 255, .14) !important; }
    html.dark .fi-dropdown-list-item:hover,
    html.dark .fi-dropdown-list-item:focus-visible,
    html.dark [role="listbox"] [role="option"]:hover { background-color: #1a2740 !important; }
    html.dark .fi-modal-window { background-color: #111a2e !important; }
    html.dark .fi-fo-rich-editor,
    html.dark .tiptap,
    html.dark .ProseMirror { color: #e5e7eb !important; }

    /* Scrollbar */
    html.dark ::-webkit-scrollbar-thumb { background: #334155 !important; }
    html.dark ::-webkit-scrollbar-track { background: #0b1220 !important; }

    /* Kalender rapat (guava/event-calendar) */
    html.dark .ec { --ec-border-color: #26344d; --ec-bg-color: #111a2e; --ec-today-bg-color: rgba(96, 165, 250, .12); color: #e5e7eb; }
    html.dark .ec-day-head,
    html.dark .ec-title { color: #cbd5e1 !important; }
    html.dark .ec-button { background: #17233a !important; border-color: #26344d !important; color: #e5e7eb !important; }
</style>
