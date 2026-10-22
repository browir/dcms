@php
// Hide mobile bottom bar on login/register pages or when user is not authenticated
$currentPath = request()->path();
$isAuthPage = ! auth()->check() || request()->is('*/login', '*/register', 'login', 'register') || str_contains($currentPath, 'login') || str_contains($currentPath, 'register');
@endphp

@if (! $isAuthPage)
@php
// Hanya tujuan yang boleh diakses user (lihat App\Support\MobileNavigation).
$bottomBarItems = \App\Support\MobileNavigation::bottomBar();
@endphp

{{-- Compact Mobile Bottom Navigation Bar --}}
<nav class="dcms-mobile-bottom-bar" aria-label="Navigasi Bawah Mobile">
    <div class="dcms-mbb-container">
        @foreach ($bottomBarItems as $item)
            @if ($item['options'])
                {{-- Punya beberapa tujuan (mis. Dokumen): tampilkan pilihan dulu --}}
                <button type="button"
                        class="dcms-mbb-item {{ $item['active'] ? 'active' : '' }}"
                        onclick="dcmsToggleSheet('dcms-sheet-{{ $loop->index }}')"
                        aria-haspopup="dialog"
                        aria-controls="dcms-sheet-{{ $loop->index }}">
                    <div class="dcms-mbb-icon">
                        @include('filament.mobile-menu-icon', ['icon' => $item['icon'], 'color' => 'currentColor'])
                    </div>
                    <span class="dcms-mbb-label">{{ $item['label'] }}</span>
                </button>
            @else
                <a href="{{ $item['url'] }}" class="dcms-mbb-item {{ $item['active'] ? 'active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                    <div class="dcms-mbb-icon">
                        @include('filament.mobile-menu-icon', ['icon' => $item['icon'], 'color' => 'currentColor'])
                    </div>
                    <span class="dcms-mbb-label">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach

        {{-- Menu --}}
        <button type="button"
                class="dcms-mbb-item"
                onclick="dcmsOpenMenu()"
                aria-label="Buka Menu">
            <div class="dcms-mbb-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </div>
            <span class="dcms-mbb-label">Menu</span>
        </button>
    </div>
</nav>

{{-- Pilihan untuk tombol bar bawah yang punya beberapa tujuan --}}
@foreach ($bottomBarItems as $item)
    @if ($item['options'])
        <div id="dcms-sheet-{{ $loop->index }}" class="dcms-mbb-sheet" role="dialog" aria-modal="true" aria-label="Pilih {{ $item['label'] }}" hidden
             onclick="if (event.target === this) dcmsToggleSheet(this.id)">
            <div class="dcms-mbb-sheet-panel">
                <div class="dcms-mbb-sheet-title">{{ $item['label'] }}</div>
                @foreach ($item['options'] as $option)
                    <a href="{{ $option['url'] }}" class="dcms-mbb-sheet-item {{ $option['active'] ? 'active' : '' }}">
                        <span class="dcms-mbb-sheet-icon" style="background: {{ $option['color'] }}1a; color: {{ $option['color'] }}">
                            @include('filament.mobile-menu-icon', ['icon' => $option['icon'], 'color' => $option['color']])
                        </span>
                        <span class="dcms-mbb-sheet-text">
                            <strong>{{ $option['label'] }}</strong>
                            <small>{{ $option['description'] }}</small>
                        </span>
                        @if ($option['active'])
                            <svg class="dcms-mbb-sheet-check" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach

<script>
    // Buka/tutup pilihan tombol bar bawah. Esc atau ketuk di luar panel untuk menutup.
    window.dcmsToggleSheet = window.dcmsToggleSheet || function (id) {
        var sheet = document.getElementById(id);
        if (! sheet) return;
        var open = sheet.hasAttribute('hidden');
        document.querySelectorAll('.dcms-mbb-sheet').forEach(function (s) { s.setAttribute('hidden', ''); });
        if (open) sheet.removeAttribute('hidden');
    };
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') document.querySelectorAll('.dcms-mbb-sheet').forEach(function (s) { s.setAttribute('hidden', ''); });
    });
</script>

{{-- Mobile Menu Overlay --}}
@include('filament.mobile-menu-overlay')

<style>
    /* ═══════════════════════════════════════════════════
   COMPACT DCMS MOBILE BOTTOM NAVIGATION BAR (< 1024px)
═══════════════════════════════════════════════════ */
    @media (min-width: 1024px) {

        .dcms-mobile-bottom-bar,
        .dcms-mobile-only,
        .mobile-only,
        .mobile-component,
        [data-mobile-only],
        .fi-page-sub-navigation-dropdown,
        .fi-page-main-sub-navigation-mobile-menu-render-hook-ctn {
            display: none !important;
        }

        /* ── TOPBAR CONTENT ALIGNMENT (Desktop Only) ────────────────────────────
           Sejajarkan konten navbar (logo, menu, search, profil) dengan tepi
           konten dashboard di bawahnya.

           Dari compiled CSS Filament:
             fi-topbar : padding-inline: calc(var(--spacing) * 4)  ← hanya 1rem
             fi-main   : padding-inline: calc(var(--spacing) * 8)  ← 2rem di desktop

           Override harus gunakan padding-INLINE (bukan padding-left/right) dan
           nilai yang identik dengan fi-main@lg agar alignment benar-benar lurus.
        ── */
        .fi-topbar {
            padding-inline: calc(var(--spacing) * 8) !important;  /* identik fi-main@lg */
        }
    }

    @media (max-width: 1023.98px) {

        /* 1. Kembalikan Topbar Biru Tua ke Posisi Atas (Sticky Top) */
        .fi-topbar {
            position: sticky !important;
            top: 0 !important;
            bottom: auto !important;
            z-index: 1000 !important;
            background-color: #0B2545 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-top: none !important;
            box-shadow: none !important;
        }

        .fi-topbar header {
            background-color: #0B2545 !important;
        }

        /* 2. Padding Bawah Halaman Lebih Tipis & Kompak */
        body,
        .fi-main,
        .fi-layout,
        .fi-main-ctn {
            padding-bottom: 56px !important;
        }

        /* 3. High Z-Index & Clickability for Profile Icon / User Menu on Mobile */
        .fi-topbar .fi-user-menu,
        .fi-topbar .fi-user-menu-trigger,
        .fi-topbar .fi-user-menu-trigger button,
        .fi-topbar [aria-label*="user"],
        .fi-topbar [aria-label*="User"],
        .fi-user-avatar {
            position: relative !important;
            z-index: 1002 !important;
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        .fi-dropdown-panel {
            z-index: 9999999 !important;
            border-radius: 12px !important;
        }

        .fi-global-search-results-ctn {
            top: 100% !important;
            bottom: auto !important;
            border-radius: 12px !important;
        }

        .nb-panel {
            top: calc(100% + 10px) !important;
            bottom: auto !important;
            transform-origin: top right !important;
        }

        .nb-panel::before {
            top: -5px !important;
            bottom: auto !important;
            border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
            border-left: 1px solid rgba(0, 0, 0, 0.08) !important;
            border-bottom: none !important;
            border-right: none !important;
        }

        /* 3.5. Hide Audit Trail / Relation Managers on Mobile (< 768px) */
        @media (max-width: 767.98px) {
            .fi-page-relation-managers,
            .fi-resource-relation-manager {
                display: none !important;
            }
        }

        /* 4. Compact Fixed Bottom Navigation Bar (Tinggi 48px) */
        .dcms-mobile-bottom-bar {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 48px !important;
            background: #ffffff !important;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08) !important;
            border-top: 1px solid #e2e8f0 !important;
            z-index: 999999 !important;
            display: flex !important;
            font-family: 'Inter', -apple-system, sans-serif !important;
            pointer-events: auto !important;
        }

        .dcms-mbb-container {
            display: flex !important;
            align-items: center !important;
            justify-content: space-around !important;
            width: 100% !important;
            height: 100% !important;
            max-width: 480px !important;
            margin: 0 auto !important;
            padding: 0 4px !important;
            position: relative !important;
            pointer-events: auto !important;
        }

        .dcms-mbb-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 1px !important;
            flex: 1 !important;
            height: 100% !important;
            text-decoration: none !important;
            color: #64748b !important;
            transition: color 0.15s ease !important;
            -webkit-tap-highlight-color: transparent;
            cursor: pointer !important;
            pointer-events: auto !important;
            /* Reset button styles */
            background: none !important;
            border: none !important;
            padding: 0 !important;
            font-family: inherit !important;
            outline: none !important;
        }

        .dcms-mbb-item.active {
            color: #0b2545 !important;
        }

        .dcms-mbb-icon {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .dcms-mbb-icon svg {
            width: 18px !important;
            height: 18px !important;
        }

        .dcms-mbb-item.active .dcms-mbb-icon svg {
            stroke-width: 2.5 !important;
        }

        .dcms-mbb-label {
            font-size: 9.5px !important;
            font-weight: 500 !important;
            letter-spacing: 0 !important;
            white-space: nowrap !important;
            line-height: 1 !important;
        }

        .dcms-mbb-item.active .dcms-mbb-label {
            font-weight: 700 !important;
            color: #0b2545 !important;
        }
    }

    /* ── Pilihan tombol bar bawah (mis. Dokumen) — panel kecil di atas bar ── */
    .dcms-mbb-sheet {
        position: fixed; inset: 0 0 48px 0; z-index: 999998;
        background: rgba(15, 23, 42, .32);
        display: flex; align-items: flex-end; justify-content: center;
        animation: dcms-sheet-fade .15s ease-out;
    }
    .dcms-mbb-sheet[hidden] { display: none; }
    .dcms-mbb-sheet-panel {
        width: calc(100% - 24px); max-width: 420px; margin-bottom: 10px;
        background: #fff; border-radius: 16px; padding: 8px;
        box-shadow: 0 -6px 30px rgba(15, 23, 42, .18);
        animation: dcms-sheet-up .2s cubic-bezier(.16, 1, .3, 1);
    }
    .dcms-mbb-sheet-title {
        padding: 6px 10px 8px; font-size: 11px; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: #94a3b8;
    }
    .dcms-mbb-sheet-item {
        display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 12px;
        text-decoration: none; color: #0f172a;
    }
    .dcms-mbb-sheet-item:active, .dcms-mbb-sheet-item.active { background: #f1f5f9; }
    .dcms-mbb-sheet-icon {
        flex: none; width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
    }
    .dcms-mbb-sheet-icon svg { width: 20px; height: 20px; }
    .dcms-mbb-sheet-text { flex: 1; min-width: 0; display: flex; flex-direction: column; line-height: 1.3; }
    .dcms-mbb-sheet-text strong { font-size: 14px; font-weight: 600; }
    .dcms-mbb-sheet-text small { font-size: 12px; color: #64748b; }
    .dcms-mbb-sheet-check { flex: none; width: 18px; height: 18px; color: #2563eb; }
    @keyframes dcms-sheet-fade { from { opacity: 0; } }
    @keyframes dcms-sheet-up { from { transform: translateY(16px); opacity: 0; } }
    @media (min-width: 768px) { .dcms-mbb-sheet { display: none !important; } }

    html.dark .dcms-mbb-sheet { background: rgba(2, 6, 23, .55); }
    html.dark .dcms-mbb-sheet-panel { background: #111a2e; box-shadow: 0 -6px 30px rgba(0, 0, 0, .5); }
    html.dark .dcms-mbb-sheet-item { color: #e2e8f0; }
    html.dark .dcms-mbb-sheet-item:active, html.dark .dcms-mbb-sheet-item.active { background: #1b2742; }
    html.dark .dcms-mbb-sheet-text small { color: #94a3b8; }
    html.dark .dcms-mbb-sheet-check { color: #60a5fa; }
</style>
@endif