@php
// Hide mobile bottom bar on login/register pages or when user is not authenticated
$currentPath = request()->path();
$isAuthPage = ! auth()->check() || request()->is('*/login', '*/register', 'login', 'register') || str_contains($currentPath, 'login') || str_contains($currentPath, 'register');
@endphp

@if (! $isAuthPage)
@php
$panelPath = request()->segment(1) ?: 'admin';

// Explicit absolute URLs
$dashUrl = url("/{$panelPath}");
$docUrl = ($panelPath === 'reviewer') ? url("/reviewer/review-documents") : url("/{$panelPath}/documents");
$meetingUrl = url("/{$panelPath}/meetings");
$profileUrl = url("/{$panelPath}/profile");

// Active State Checks
$isDashActive = ($currentPath === $panelPath || $currentPath === "{$panelPath}/");
$isDocActive = str_contains($currentPath, 'documents') && !str_contains($currentPath, 'create');
$isMeetingActive = str_contains($currentPath, 'meetings');
$isMenuActive = false; // menu button is never "active" like a page
@endphp

{{-- Compact Mobile Bottom Navigation Bar --}}
<nav class="dcms-mobile-bottom-bar" aria-label="Navigasi Bawah Mobile">
    <div class="dcms-mbb-container">
        {{-- 1. Dashboard --}}
        <a href="{{ $dashUrl }}" onclick="window.location.href='{{ $dashUrl }}'; return false;" class="dcms-mbb-item {{ $isDashActive ? 'active' : '' }}">
            <div class="dcms-mbb-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $isDashActive ? '2.5' : '1.8' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </div>
            <span class="dcms-mbb-label">Dashboard</span>
        </a>

        {{-- 2. Manajemen Dokumen --}}
        <a href="{{ $docUrl }}" onclick="window.location.href='{{ $docUrl }}'; return false;" class="dcms-mbb-item {{ $isDocActive ? 'active' : '' }}">
            <div class="dcms-mbb-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $isDocActive ? '2.5' : '1.8' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <span class="dcms-mbb-label">Dokumen</span>
        </a>

        {{-- 3. Rapat --}}
        <a href="{{ $meetingUrl }}" onclick="window.location.href='{{ $meetingUrl }}'; return false;" class="dcms-mbb-item {{ $isMeetingActive ? 'active' : '' }}">
            <div class="dcms-mbb-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $isMeetingActive ? '2.5' : '1.8' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                </svg>
            </div>
            <span class="dcms-mbb-label">Rapat</span>
        </a>

        {{-- 4. Menu --}}
        <button type="button"
                class="dcms-mbb-item {{ $isMenuActive ? 'active' : '' }}"
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
</style>
@endif