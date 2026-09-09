<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Plugins\ActivityLogPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->maxContentWidth(Width::Full)

            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            ->topNavigation()
            ->favicon(\App\Support\Branding::logoUrl())
            ->darkMode(false)
            ->login(Login::class)
            ->registration(Register::class)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => view('filament.pwa-head')->render()
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => \Illuminate\Support\Facades\Blade::render('filament.loading-screen')
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => view('filament.web-push-scripts')->render()
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => view('filament.mobile-bottom-bar')->render()
            )
            ->renderHook(
                'panels::scripts.after',
                fn (): string => new HtmlString('<script>
                    (function() {
                        function applyRolesBodyClass() {
                            var path = window.location.pathname;
                            if (path.indexOf("shield/roles") !== -1) {
                                document.body.classList.add("dcms-page-roles");
                            } else {
                                document.body.classList.remove("dcms-page-roles");
                            }
                        }
                        applyRolesBodyClass();
                        document.addEventListener("livewire:navigated", applyRolesBodyClass);
                        document.addEventListener("livewire:navigate", applyRolesBodyClass);
                    })();
                </script>')
            )
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\'bookmark-topbar-icon\')')
            )
            ->renderHook(
                'panels::styles.after',
                fn (): string => new HtmlString('
                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

                        * { font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important; }

                        /* Hide mobile-specific components on desktop screens (>= 1024px) */
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
                        }

                        /* ================================================
                           HIDE DEFAULT SIDEBAR (Replaced by Menu)
                        ================================================ */
                        aside.fi-sidebar {
                            display: none !important;
                        }
                        .fi-main-ctn, .fi-main {
                            margin-left: 0 !important;
                        }
                        /* Hide hamburger menu button in topbar */
                        .fi-topbar .fi-icon-btn.fi-sidebar-collapse-btn,
                        .fi-topbar-open-sidebar-btn,
                        .fi-topbar-close-sidebar-btn {
                            display: none !important;
                        }

                        /* Hide mobile bottom bar on login & auth screens */
                        .fi-simple-layout .dcms-mobile-bottom-bar,
                        .fi-body-login .dcms-mobile-bottom-bar,
                        body.fi-simple-layout .dcms-mobile-bottom-bar {
                            display: none !important;
                        }

                        /* ================================================
                           MOBILE TABLE: NO HORIZONTAL SCROLL
                        ================================================ */
                        /* ================================================
                           INNOVATIVE MOBILE CARD TABLE LIST (EXACT REFERENCE DESIGN)
                        ================================================ */
                        @media (max-width: 767.98px) {
                            .fi-ta-ctn {
                                overflow-x: hidden !important;
                                border: none !important;
                                background: transparent !important;
                                box-shadow: none !important;
                                margin-left: 0 !important;
                                margin-right: 0 !important;
                                padding-left: 0 !important;
                                padding-right: 0 !important;
                                width: 100% !important;
                            }

                            .fi-ta table,
                            .fi-ta .fi-ta-table,
                            .fi-ta table > tbody,
                            .fi-ta .fi-ta-table > tbody {
                                display: block !important;
                                width: 100% !important;
                                max-width: 100vw !important;
                                box-sizing: border-box !important;
                                background: transparent !important;
                                margin: 0 auto !important;
                                padding: 0 !important;
                            }

                            /* Hide header columns on mobile */
                            .fi-ta table > thead,
                            .fi-ta .fi-ta-table > thead {
                                display: none !important;
                            }

                            /* Table rows become clean transparent wrappers for the mobile cards */
                            .fi-ta table > tbody > tr,
                            .fi-ta .fi-ta-table > tbody > tr {
                                display: block !important;
                                width: 100% !important;
                                padding: 0 !important;
                                margin-bottom: 12px !important;
                                background: transparent !important;
                                border: none !important;
                                box-shadow: none !important;
                                box-sizing: border-box !important;
                            }

                            .fi-ta table > tbody > tr > td,
                            .fi-ta .fi-ta-table > tbody > tr > td {
                                display: block !important;
                                width: 100% !important;
                                padding: 0 !important;
                                border: none !important;
                                background: transparent !important;
                            }

                            /* Hide extra action cell, selection cell, and non-card cells on mobile.
                               Mobile card root element MUST include class dcms-mobile-card. */
                            .fi-ta-actions-cell,
                            .fi-ta-selection-cell,
                            .fi-ta-table > tbody > tr > td:not(:has(.dcms-mobile-card)),
                            .fi-ta table > tbody > tr > td:not(:has(.dcms-mobile-card)) {
                                display: none !important;
                            }

                            /* Hide pagination records per page dropdown on mobile */
                            .fi-pagination-records-per-page-select-ctn,
                            .fi-pagination-records-dropdown,
                            .fi-pagination-records-combined-pill {
                                display: none !important;
                            }

                            /* Prevent any SVG icon overflow on mobile */
                            .fi-ta svg {
                                max-width: 1.5rem !important;
                                max-height: 1.5rem !important;
                            }

                            /* Make header action buttons compact, uniform & 2-column grid on mobile */
                            .fi-header-actions,
                            .fi-page-header-actions,
                            .fi-ac-ctn,
                            .fi-header-actions-ctn,
                            .fi-header header > div:last-child,
                            .fi-header header .flex:has(.fi-btn),
                            header .fi-ac-ctn,
                            header .flex:has(.fi-btn) {
                                display: grid !important;
                                grid-template-columns: repeat(2, 1fr) !important;
                                gap: 6px !important;
                                width: 100% !important;
                                margin-top: 8px !important;
                            }

                            .fi-header-actions .fi-btn,
                            .fi-page-header-actions .fi-btn,
                            .fi-ac-ctn .fi-btn,
                            .fi-header-actions-ctn .fi-btn,
                            header .fi-ac-ctn .fi-btn,
                            header .fi-btn,
                            .fi-header header button,
                            .fi-header header a {
                                width: 100% !important;
                                max-width: 100% !important;
                                min-width: 0 !important;
                                display: inline-flex !important;
                                justify-content: center !important;
                                align-items: center !important;
                                text-align: center !important;
                                box-sizing: border-box !important;
                                padding: 5px 8px !important;
                                font-size: 11px !important;
                                font-weight: 600 !important;
                                border-radius: 8px !important;
                                line-height: 1.3 !important;
                                gap: 4px !important;
                                white-space: nowrap !important;
                                overflow: hidden !important;
                                text-overflow: ellipsis !important;
                            }

                            .fi-header-actions .fi-btn *,
                            .fi-page-header-actions .fi-btn *,
                            .fi-ac-ctn .fi-btn *,
                            header .fi-btn * {
                                font-size: 11px !important;
                            }

                            .fi-header-actions .fi-btn svg,
                            .fi-page-header-actions .fi-btn svg,
                            .fi-ac-ctn .fi-btn svg,
                            header .fi-btn svg {
                                width: 13px !important;
                                height: 13px !important;
                                min-width: 13px !important;
                                min-height: 13px !important;
                            }
                        }








                        /* ================================================
                           GLOBAL SCROLLBAR
                        ================================================ */
                        ::-webkit-scrollbar {
                            width: 8px !important;
                            height: 8px !important;
                        }
                        ::-webkit-scrollbar-track {
                            background: #f1f5f9 !important;
                        }
                        ::-webkit-scrollbar-thumb {
                            background: #cbd5e1 !important;
                            border-radius: 4px !important;
                        }
                        ::-webkit-scrollbar-thumb:hover {
                            background: #94a3b8 !important;
                        }

                        /* ================================================
                           NAVBAR & TOPBAR MICRO-ANIMATIONS
                        ================================================ */
                        /* Hide Audit Trail / Relation Managers on Mobile (< 768px) */
                        @media (max-width: 767.98px) {
                            .fi-page-relation-managers,
                            .fi-resource-relation-manager {
                                display: none !important;
                            }
                        }

                        .fi-topbar {
                            position: sticky !important;
                            top: 0 !important;
                            z-index: 1000 !important;
                            background-color: #0B2545 !important;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                        }

                        .fi-topbar header,
                        .fi-sidebar,
                        .fi-sidebar header {
                            background-color: #0B2545 !important;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
                        }

                        .fi-topbar-item,
                        .fi-sidebar-item {
                            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), background-color 0.2s ease, box-shadow 0.2s ease !important;
                        }

                        /* Brand Logo DCMS Text Color - Pure White (#FFFFFF) */
                        .fi-logo,
                        .fi-logo *,
                        a.fi-logo,
                        .fi-topbar .fi-logo,
                        .fi-sidebar .fi-logo,
                        .fi-topbar-header .fi-logo,
                        .fi-sidebar-header .fi-logo,
                        header .fi-logo {
                            color: #ffffff !important;
                            font-weight: 800 !important;
                        }

                        .fi-topbar .fi-topbar-item-label,
                        .fi-topbar .fi-topbar-item-icon,
                        .fi-topbar .fi-topbar-group-label,
                        .fi-topbar .fi-breadcrumbs-item-label,
                        .fi-topbar .fi-breadcrumbs-item-separator,
                        .fi-sidebar .fi-sidebar-item-label,
                        .fi-sidebar .fi-sidebar-item-icon,
                        .fi-sidebar .fi-sidebar-group-label,
                        .fi-topbar-item button svg {
                            color: rgba(255, 255, 255, 0.75) !important;
                            transition: color 0.2s ease, transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
                        }

                        .fi-topbar-item:hover,
                        .fi-sidebar-item:hover {
                            background-color: rgba(255, 255, 255, 0.12) !important;
                            border-radius: 8px !important;
                            transform: translateY(-1px) !important;
                        }

                        .fi-topbar-item:hover .fi-topbar-item-icon,
                        .fi-sidebar-item:hover .fi-sidebar-item-icon {
                            transform: scale(1.15) rotate(-3deg) !important;
                        }

                        /* Paksa semua button & link di dalam topbar & sidebar agar tidak ada background putih dari Tailwind */
                        .fi-topbar-item > button,
                        .fi-topbar-item > a,
                        .fi-topbar-item > div > button,
                        .fi-topbar-item > div > a,
                        .fi-sidebar-item > button,
                        .fi-sidebar-item > a,
                        .fi-sidebar-item > div > button,
                        .fi-sidebar-item > div > a {
                            background: transparent !important;
                            background-color: transparent !important;
                        }

                        /* Item aktif (halaman saat ini) */
                        .fi-topbar-item.fi-active,
                        .fi-sidebar-item.fi-active {
                            background: linear-gradient(135deg, #1E40AF, #1D4ED8) !important;
                            border-radius: 8px !important;
                            box-shadow: 0 4px 14px -2px rgba(30, 64, 175, 0.6) !important;
                            animation: fi-active-pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite !important;
                        }
                        @keyframes fi-active-pulse {
                            0%, 100% { box-shadow: 0 4px 14px -2px rgba(30, 64, 175, 0.6); }
                            50%      { box-shadow: 0 4px 20px 2px rgba(59, 130, 246, 0.5); }
                        }

                        /* Item dropdown yang sedang terbuka */
                        .fi-topbar-item:has(button[aria-expanded="true"]) {
                            background: rgba(255, 255, 255, 0.15) !important;
                            border-radius: 8px !important;
                            transform: none !important;
                        }

                        /* Warna teks navbar */
                        .fi-topbar .fi-topbar-item-label,
                        .fi-topbar .fi-topbar-item-icon,
                        .fi-topbar-item > button span,
                        .fi-topbar-item > a span,
                        .fi-topbar-item button .fi-topbar-item-label,
                        .fi-topbar-item a .fi-topbar-item-label,
                        .fi-topbar-item.fi-active .fi-topbar-item-label,
                        .fi-topbar-item.fi-active .fi-topbar-item-icon,
                        .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                        .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
                        .fi-topbar-item:has(button[aria-expanded="true"]) .fi-topbar-item-label,
                        .fi-topbar-item:has(button[aria-expanded="true"]) .fi-topbar-item-icon,
                        .fi-topbar-item:has(button[aria-expanded="true"]) svg {
                            color: rgba(255, 255, 255, 0.9) !important;
                        }
                        .fi-topbar-item.fi-active .fi-topbar-item-label,
                        .fi-topbar-item.fi-active .fi-topbar-item-icon {
                            color: #ffffff !important;
                            font-weight: 700 !important;
                        }

                        /* ================================================
                           BUTTON MICRO-ANIMATIONS (All Action & Form Buttons)
                        ================================================ */
                        .fi-btn,
                        .fi-ac-btn-action,
                        button[type="submit"],
                        .fi-modal-close-btn {
                            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, background-color 0.2s ease, filter 0.2s ease !important;
                        }

                        .fi-btn svg,
                        .fi-ac-btn-action svg,
                        .fi-icon-btn svg,
                        button[type="submit"] svg,
                        .fi-modal-close-btn svg {
                            transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s ease, filter 0.2s ease !important;
                            transform-origin: center !important;
                            will-change: transform !important;
                        }

                        .fi-btn:hover,
                        .fi-ac-btn-action:hover {
                            transform: translateY(-2px) scale(1.02) !important;
                            box-shadow: 0 6px 16px -2px rgba(30, 64, 175, 0.25) !important;
                        }

                        .fi-btn:hover svg,
                        .fi-ac-btn-action:hover svg,
                        .fi-icon-btn:hover svg,
                        button[type="submit"]:hover svg,
                        .fi-modal-close-btn:hover svg {
                            transform: translateY(-1px) scale(1.12) rotate(-6deg) !important;
                            filter: drop-shadow(0 2px 4px rgba(30, 64, 175, 0.22)) !important;
                        }

                        .fi-btn:active,
                        .fi-ac-btn-action:active {
                            transform: translateY(0) scale(0.97) !important;
                        }

                        .fi-btn:active svg,
                        .fi-ac-btn-action:active svg,
                        .fi-icon-btn:active svg,
                        button[type="submit"]:active svg,
                        .fi-modal-close-btn:active svg {
                            transform: scale(0.94) rotate(0deg) !important;
                        }

                        /* ================================================
                           DROPDOWN PANELS (Visual Only)
                        ================================================ */
                        .fi-dropdown-panel {
                            background-color: #ffffff !important;
                            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08) !important;
                            border: 1px solid rgba(0, 0, 0, 0.06) !important;
                            border-radius: 12px !important;
                            z-index: 60 !important;
                        }




                        /* ================================================
                           PAGE BACKGROUND, FULL WIDTH & ENTRY ANIMATION
                        ================================================ */
                        body, .fi-main, .fi-layout {
                            background: linear-gradient(-45deg, #ffffff, #bae6fd, #93c5fd, #ffffff) !important;
                            background-size: 400% 400% !important;
                            animation: bg-gradient-animation 10s ease infinite !important;
                            background-attachment: fixed !important;
                        }

                        @keyframes bg-gradient-animation {
                            0% { background-position: 0% 50%; }
                            50% { background-position: 100% 50%; }
                            100% { background-position: 0% 50%; }
                        }

                        .fi-main-ctn,
                        .fi-page,
                        .fi-main,
                        .fi-main > div,
                        .fi-content {
                            max-width: 100% !important;
                            width: 100% !important;
                        }

                        .fi-main-ctn {
                            padding-left: 24px !important;
                            padding-right: 24px !important;
                        }

                        .fi-page {
                            animation: fi-page-in 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                        }
                        @keyframes fi-page-in {
                            0%   { opacity: 0; transform: translateY(8px); }
                            100% { opacity: 1; transform: translateY(0); }
                        }


                        /* ================================================
                           PAGE HEADER BANNER ANIMATIONS
                        ================================================ */
                        .fi-header {
                            background: linear-gradient(135deg, #0B2545 0%, #133B6B 50%, #1E40AF 100%) !important;
                            border-radius: 16px !important;
                            padding: 22px 28px !important;
                            margin-top: 16px !important;
                            margin-bottom: 24px !important;
                            border: 1px solid rgba(255, 255, 255, 0.12) !important;
                            box-shadow: 0 10px 30px -5px rgba(11, 37, 69, 0.35), 0 4px 12px rgba(0, 0, 0, 0.08) !important;
                            position: relative !important;
                            overflow: hidden !important;
                            animation: fi-header-slide 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                        }

                        .fi-header-actions-ctn,
                        .fi-header-actions-ctn .fi-ac,
                        .fi-header-actions-ctn .fi-dropdown {
                            overflow: visible !important;
                        }

                        .fi-header:has(button[aria-expanded="true"]),
                        .fi-header:has(.fi-dropdown-panel:not(.fi-opacity-0)) {
                            overflow: visible !important;
                            z-index: 100 !important;
                        }
                        @keyframes fi-header-slide {
                            0%   { opacity: 0; transform: translateY(-8px); }
                            100% { opacity: 1; transform: translateY(0); }
                        }

                        .fi-header::before {
                            content: "";
                            position: absolute;
                            top: -40px; right: -40px;
                            width: 200px; height: 200px;
                            border-radius: 50%;
                            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
                            pointer-events: none;
                            animation: fi-bg-pulse 6s ease-in-out infinite alternate;
                        }
                        @keyframes fi-bg-pulse {
                            0%   { transform: scale(1); }
                            100% { transform: scale(1.2); }
                        }

                        .fi-header-heading {
                            color: #ffffff !important;
                            font-size: 22px !important;
                            font-weight: 800 !important;
                            letter-spacing: -0.5px !important;
                        }

                        .fi-header-subheading {
                            color: rgba(255, 255, 255, 0.75) !important;
                            font-size: 13px !important;
                            font-weight: 400 !important;
                            margin-top: 4px !important;
                        }


                        /* ================================================
                           TABLE & ROW SLIDE ANIMATIONS
                        ================================================ */
                        .fi-ta {
                            background: #ffffff !important;
                            border-radius: 16px !important;
                            border: 1px solid rgba(0, 0, 0, 0.06) !important;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.04) !important;
                            overflow: hidden !important;
                            animation: fi-card-fade 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                        }

                        .fi-ta:has(.fi-ta-actions button[aria-expanded="true"]) {
                            overflow: visible !important;
                        }

                        .fi-ta-content-ctn {
                            overflow-x: auto !important;
                            overflow-y: visible !important;
                        }
                        @keyframes fi-card-fade {
                            0%   { opacity: 0; transform: translateY(6px); }
                            100% { opacity: 1; transform: translateY(0); }
                        }

                        .fi-ta-table {
                            border-collapse: separate !important;
                            border-spacing: 0 !important;
                        }

                        .fi-ta-row {
                            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.15s ease, box-shadow 0.15s ease !important;
                        }

                        .fi-ta-row:hover {
                            transform: translateX(3px) !important;
                        }

                        .fi-ta-row:hover td {
                            background-color: rgba(30, 64, 175, 0.05) !important;
                        }

                        .fi-ta-header-cell {
                            background: linear-gradient(180deg, #f8faff 0%, #f0f4f8 100%) !important;
                            color: #0B2545 !important;
                            font-weight: 700 !important;
                            font-size: 11px !important;
                            letter-spacing: 0.5px !important;
                            text-transform: uppercase !important;
                            border-bottom: 2px solid rgba(30, 64, 175, 0.12) !important;
                            padding: 12px 14px !important;
                        }

                        .fi-ta-row td,
                        .fi-ta-row th {
                            background-color: #ffffff !important;
                            color: #1e293b !important;
                        }

                        .fi-ta-row:nth-child(even) td {
                            background-color: #f8faff !important;
                        }

                        .fi-ta-cell {
                            padding: 12px 14px !important;
                            border-bottom: 1px solid rgba(0,0,0,0.04) !important;
                            vertical-align: middle !important;
                        }

                        /* ================================================
                           BADGE POP ANIMATIONS
                        ================================================ */
                        .fi-badge {
                            font-size: 10px !important;
                            font-weight: 700 !important;
                            padding: 3px 8px !important;
                            border-radius: 6px !important;
                            letter-spacing: 0.1px !important;
                            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease !important;
                        }
                        .fi-badge:hover {
                            transform: scale(1.1) !important;
                        }

                        /* ================================================
                           SECTION CARDS & FORM FIELDS
                        ================================================ */
                        .fi-section {
                            background: #ffffff !important;
                            border: 1px solid rgba(0,0,0,0.05) !important;
                            border-radius: 16px !important;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04) !important;
                            /* overflow: visible agar dropdown tidak terpotong oleh section */
                            overflow: visible !important;
                            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease !important;
                        }

                        /* Clip hanya di bagian dalam section (bukan container-nya) */
                        .fi-section > .fi-section-header-ctn,
                        .fi-section > .fi-section-content-ctn {
                            overflow: visible !important;
                        }

                        .fi-section:hover {
                            transform: translateY(-2px) !important;
                            box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.15), 0 8px 24px rgba(0,0,0,0.08) !important;
                        }

                        .fi-section-header {
                            border-bottom: 1px solid rgba(0,0,0,0.05) !important;
                            padding: 16px 20px 16px 28px !important;
                            background: linear-gradient(to right, rgba(30,64,175,0.04), transparent) !important;
                            position: relative !important;
                            /* Radius hanya di sudut atas agar section header tetap rapi */
                            border-radius: 16px 16px 0 0 !important;
                            overflow: hidden !important;
                        }

                        .fi-section-header::before {
                            content: "";
                            position: absolute;
                            left: 12px; top: 12px; bottom: 12px;
                            width: 3px;
                            background: linear-gradient(180deg, #1E40AF, #3B82F6);
                            border-radius: 999px;
                        }

                        .document-status-section .fi-section-header::before {
                            left: 12px !important;
                            top: 14px !important;
                            bottom: 14px !important;
                            width: 2px !important;
                            background: linear-gradient(180deg, rgba(30, 64, 175, 0.85), rgba(59, 130, 246, 0.7)) !important;
                            border-radius: 999px !important;
                        }

                        .document-status-section .fi-section-header {
                            padding-left: 28px !important;
                        }

                        .fi-section-header-heading {
                            font-size: 13px !important;
                            font-weight: 800 !important;
                            color: #0B2545 !important;
                            letter-spacing: -0.2px !important;
                        }

                        .fi-section-content {
                            padding: 20px !important;
                            overflow: visible !important;
                        }

                        /* ── Fix dropdown Select agar keluar dari overflow container ── */
                        /* Filament v3 menggunakan Alpine.js Listbox yang bisa menggunakan fixed positioning */
                        [x-data] .choices__list--dropdown,
                        .fi-select-options-list,
                        [role="listbox"] {
                            z-index: 9999 !important;
                        }

                        .fi-input,
                        .fi-select-input,
                        .fi-textarea {
                            border: 1.5px solid rgba(0,0,0,0.1) !important;
                            border-radius: 10px !important;
                            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease !important;
                            font-size: 13px !important;
                        }
                        .fi-input:focus,
                        .fi-select-input:focus,
                        .fi-textarea:focus {
                            border-color: #1E40AF !important;
                            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.15) !important;
                            transform: translateY(-1px) !important;
                        }

                        /* Input dalam wrapper (global search, dll.) — border hanya di wrapper */
                        .fi-input-wrp .fi-input,
                        .fi-input-wrp .fi-select-input {
                            border: none !important;
                            border-radius: 0 !important;
                            box-shadow: none !important;
                            background: transparent !important;
                        }

                        .fi-input-wrp .fi-input:focus,
                        .fi-input-wrp .fi-select-input:focus {
                            border: none !important;
                            box-shadow: none !important;
                            transform: none !important;
                        }

                        /* Global search topbar — satu border saja */
                        .fi-topbar .fi-global-search .fi-input-wrp {
                            --tw-ring-shadow: 0 0 #0000 !important;
                            --tw-ring-offset-shadow: 0 0 #0000 !important;
                            box-shadow: none !important;
                            border: 1px solid rgba(255, 255, 255, 0.35) !important;
                            border-radius: 10px !important;
                            background: #ffffff !important;
                        }

                        .fi-topbar .fi-global-search .fi-input-wrp:focus-within {
                            border-color: #1E40AF !important;
                            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.15) !important;
                        }

                        .fi-topbar .fi-global-search .fi-input {
                            color: #1e293b !important;
                        }

                        .fi-topbar .fi-global-search .fi-input::placeholder {
                            color: #94a3b8 !important;
                        }

                        .fi-topbar .fi-global-search .fi-input-wrp-prefix .fi-icon {
                            color: #94a3b8 !important;
                        }

                        /* Strip outer StatsOverview wrapper – cards appear directly on page bg */
                        .fi-wi-stats-overview,
                        .fi-wi-stats-overview > div,
                        .fi-wi-stats-overview-header,
                        .fi-wi-stats-overview > .fi-section,
                        .fi-wi-stats-overview .fi-section,
                        .fi-wi-stats-overview .fi-section-content,
                        .fi-wi-stats-overview .fi-section-content-ctn {
                            background: transparent !important;
                            border: none !important;
                            box-shadow: none !important;
                            padding: 0 !important;
                        }

                        /* ================================================
                           STATS WIDGET CARDS – CLEAN STABLE STYLING
                        ================================================ */
                        .fi-wi-stats-overview-stat {
                            background-color: #ffffff !important;
                            border-radius: 16px !important;
                            border: 1.5px solid rgba(0, 0, 0, 0.055) !important;
                            box-shadow:
                                0 1px 3px rgba(0, 0, 0, 0.04),
                                0 4px 16px rgba(0, 0, 0, 0.05) !important;
                            padding: 16px 18px !important;
                            position: relative !important;
                            overflow: hidden !important;
                            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                                        box-shadow 0.25s ease,
                                        border-color 0.25s ease,
                                        background-color 0.25s ease !important;
                        }

                        /* Hover state */
                        .fi-wi-stats-overview-stat:hover {
                            transform: translateY(-4px) !important;
                            background-color: #fafcff !important;
                            border-color: rgba(59, 130, 246, 0.25) !important;
                            box-shadow:
                                0 0 0 4px rgba(59, 130, 246, 0.06),
                                0 8px 24px rgba(30, 64, 175, 0.10) !important;
                        }

                        .fi-wi-stats-overview-stat-value {
                            display: block;
                            transition: color 0.25s ease !important;
                        }

                        .fi-wi-stats-overview-stat-label {
                            transition: color 0.25s ease !important;
                        }

                        .fi-wi-stats-overview-stat-description {
                            transition: color 0.25s ease !important;
                        }

                        .fi-wi-stats-overview-stat-description svg {
                            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
                            display: inline-block;
                        }
                        .fi-wi-stats-overview-stat:hover .fi-wi-stats-overview-stat-description svg {
                            transform: scale(1.15);
                        }

                        .fi-wi-stats-overview-stat-chart {
                            transition: filter 0.25s ease, opacity 0.25s ease;
                        }
                        .fi-wi-stats-overview-stat:hover .fi-wi-stats-overview-stat-chart {
                            filter: brightness(1.1) saturate(1.2);
                        }

                        /* ================================================
                           EQUAL HEIGHT FOR DASHBOARD TABLE WIDGET CARDS
                        ================================================ */
                        .fi-wi-table,
                        .fi-wi-widget {
                            height: 100% !important;
                            display: flex !important;
                            flex-direction: column !important;
                        }

                        .fi-wi-table > div,
                        .fi-wi-table .fi-section,
                        .fi-wi-table .fi-ta-ctn {
                            height: 100% !important;
                            display: flex !important;
                            flex-direction: column !important;
                            justify-content: space-between !important;
                            flex: 1 1 auto !important;
                        }

                        .fi-wi-table .fi-ta-content {
                            flex: 1 1 auto !important;
                            display: flex !important;
                            flex-direction: column !important;
                            justify-content: flex-start !important;
                        }

                        .fi-wi-table .fi-ta-empty-state {
                            padding: 24px 16px !important;
                        }

                        .fi-wi-table .fi-ta-pagination,
                        .fi-wi-table .fi-ta-footer {
                            margin-top: auto !important;
                        }

                        /* ================================================
                           MODAL DIALOG POP ANIMATIONS
                        ================================================ */
                        .fi-modal-window {
                            margin: auto !important;
                            border-radius: 20px !important;
                            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25) !important;
                            border: 1px solid rgba(0,0,0,0.06) !important;
                            animation: fi-modal-pop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards !important;
                        }

                        @keyframes fi-modal-pop {
                            0%   { opacity: 0; transform: scale(0.92) translateY(10px); }
                            100% { opacity: 1; transform: scale(1) translateY(0); }
                        }

                        .fi-modal-header {
                            background: linear-gradient(135deg, #0B2545, #1E40AF) !important;
                            border-radius: 20px 20px 0 0 !important;
                            padding: 20px 24px !important;
                        }
                        .fi-modal-heading {
                            color: #ffffff !important;
                            font-weight: 800 !important;
                            font-size: 15px !important;
                        }

                        .fi-pagination-item-btn {
                            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), background-color 0.2s ease !important;
                        }
                        .fi-pagination-item-btn:hover {
                            transform: scale(1.1) !important;
                        }
                        .fi-pagination-item-btn[aria-current="page"] {
                            background: linear-gradient(135deg, #1E40AF, #3B82F6) !important;
                            color: #fff !important;
                            font-weight: 700 !important;
                        }

                        /* ================================================
                           COMBINED SINGLE PILL PAGINATION DROPDOWN
                        ================================================ */
                        .fi-pagination-records-combined-pill {
                            display: inline-flex !important;
                            flex-direction: row !important;
                            align-items: center !important;
                            background: #ffffff !important;
                            border: 1px solid rgba(0, 0, 0, 0.12) !important;
                            border-radius: 10px !important;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
                            padding: 0 !important;
                            margin: 0 !important;
                            cursor: pointer !important;
                            overflow: hidden !important;
                            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                        }

                        .fi-pagination-records-combined-pill:hover {
                            border-color: #1e40af !important;
                            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.12) !important;
                        }

                        .fi-pagination-records-combined-pill-label {
                            padding: 6px 12px !important;
                            font-size: 12px !important;
                            font-weight: 600 !important;
                            color: #64748b !important;
                            background-color: #f8fafc !important;
                            border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
                        }

                        .fi-pagination-records-combined-pill-value {
                            display: inline-flex !important;
                            flex-direction: row !important;
                            align-items: center !important;
                            gap: 6px !important;
                            padding: 6px 12px !important;
                            font-size: 13px !important;
                            font-weight: 700 !important;
                            color: #1e293b !important;
                        }

                        .fi-pagination-records-combined-pill:hover .fi-pagination-records-combined-pill-value {
                            color: #1e40af !important;
                        }

                        .fi-pagination-records-chevron {
                            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
                        }

                        .fi-dropdown:has(.fi-pagination-records-combined-pill)[x-data*="open: true"] .fi-pagination-records-chevron,
                        .fi-dropdown-open .fi-pagination-records-chevron {
                            transform: rotate(180deg) !important;
                        }

                        .fi-pagination-records-dropdown .fi-dropdown-panel {
                            background: #ffffff !important;
                            border: 1px solid rgba(0, 0, 0, 0.08) !important;
                            border-radius: 14px !important;
                            box-shadow: 0 16px 40px -6px rgba(0, 0, 0, 0.18), 0 4px 16px rgba(0, 0, 0, 0.08) !important;
                            z-index: 2147483647 !important;
                            min-width: 120px !important;
                        }

                        /* ================================================
                           BEAUTIFIED TABLE ACTIONS & LAYOUT
                        ================================================ */
                        .fi-ta-content,
                        .fi-ta-table,
                        .fi-ta-cell,
                        .fi-ta-actions-cell {
                            overflow: visible !important;
                        }

                        .fi-ta-table {
                            width: 100% !important;
                            table-layout: auto !important;
                        }

                        .fi-ta-actions-cell {
                            width: 1% !important;
                            white-space: nowrap !important;
                            text-align: right !important;
                            padding-right: 16px !important;
                        }

                        .fi-ta-actions {
                            display: inline-flex !important;
                            flex-direction: row !important;
                            align-items: center !important;
                            justify-content: flex-end !important;
                            gap: 6px !important;
                            white-space: nowrap !important;
                        }

                        /* Elevate active table row above all other rows when its dropdown is open */
                        tr.fi-ta-row:has(button[aria-expanded="true"]),
                        tr.fi-ta-row:has(.fi-dropdown-panel:not(.fi-opacity-0)),
                        .fi-ta-cell:has(button[aria-expanded="true"]),
                        .fi-ta-actions:has(button[aria-expanded="true"]) {
                            position: relative !important;
                            z-index: 99999 !important;
                        }

                        /* Ensure floating dropdown panels have smooth rounded corners and clean clipping */
                        .fi-dropdown-panel,
                        .fi-ta-actions .fi-dropdown-panel {
                            background-color: #ffffff !important;
                            background: #ffffff !important;
                            border: 1px solid rgba(0, 0, 0, 0.08) !important;
                            border-radius: 14px !important;
                            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.25), 0 4px 16px rgba(0, 0, 0, 0.1) !important;
                            z-index: 2147483647 !important;
                            isolation: isolate !important;
                            overflow: visible !important;
                        }

                        /* Table action dropdown — biarkan x-float atur posisi */
                        .fi-ta-actions .fi-dropdown {
                            position: relative !important;
                        }

                        .fi-ta-actions .fi-dropdown-panel {
                            min-width: 180px !important;
                            padding: 6px !important;
                            transform-origin: top right !important;
                            /* Ensure panel height matches content and scrolls if necessary */
                            height: auto !important;
                            max-height: 320px !important;
                            overflow-y: auto !important;
                        }

                        .fi-ta-actions .fi-dropdown-panel[x-placement^="top"],
                        .fi-ta-actions .fi-dropdown-panel[data-placement^="top"] {
                            transform-origin: bottom right !important;
                        }

                        .fi-ta-actions .fi-dropdown-list {
                            display: grid !important;
                            gap: 4px !important;
                            background: transparent !important;
                        }

                        .fi-ta-actions .fi-dropdown-list-item {
                            border-radius: 10px !important;
                            overflow: hidden !important;
                            background: transparent !important;
                        }

                        .fi-ta-actions .fi-dropdown-list-item > button,
                        .fi-ta-actions .fi-dropdown-list-item > a {
                            display: flex !important;
                            width: 100% !important;
                            align-items: center !important;
                            gap: 10px !important;
                            border-radius: 10px !important;
                            background: #ffffff !important;
                        }

                        .fi-ta-actions .fi-dropdown-list-item:first-child > button,
                        .fi-ta-actions .fi-dropdown-list-item:first-child > a,
                        .fi-ta-actions .fi-dropdown-list-item:last-child > button,
                        .fi-ta-actions .fi-dropdown-list-item:last-child > a {
                            border-radius: 10px !important;
                        }

                        /* Smooth hover micro-animation on dropdown items */
                        .fi-dropdown-list-item button,
                        .fi-dropdown-list-item a {
                            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
                        }

                        .fi-dropdown-list-item button:hover,
                        .fi-dropdown-list-item a:hover {
                            background-color: #f1f5f9 !important;
                        }

                        .fi-dropdown-list-item button:hover svg,
                        .fi-dropdown-list-item a:hover svg {
                            transform: scale(1.18) rotate(3deg) !important;
                            transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
                        }








                        .fi-ta-actions .fi-btn {
                            border-radius: 8px !important;
                            font-size: 11.5px !important;
                            font-weight: 600 !important;
                            padding: 5px 10px !important;
                            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
                        }

                        .fi-ta-actions .fi-btn:hover {
                            transform: translateY(-1.5px) !important;
                            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important;
                        }




                        /* Alpine x-transition keyframe animation hooks */
                        .fi-pagination-popover[x-transition\:enter] {
                            animation: fiPopoverIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
                        }

                        .fi-pagination-popover[x-transition\:leave] {
                            animation: fiPopoverOut 0.14s cubic-bezier(0.4, 0, 1, 1) forwards !important;
                        }

                        @keyframes fiPopoverIn {
                            0% {
                                opacity: 0;
                                transform: translateY(6px);
                            }
                            100% {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }

                        @keyframes fiPopoverOut {
                            0% {
                                opacity: 1;
                                transform: translateY(0);
                            }
                            100% {
                                opacity: 0;
                                transform: translateY(4px);
                            }
                        }

                        .fi-pagination-popover-item {
                            display: flex !important;
                            flex-direction: row !important;
                            align-items: center !important;
                            justify-content: space-between !important;
                            width: 100% !important;
                            padding: 8px 14px !important;
                            font-size: 13px !important;
                            font-weight: 600 !important;
                            box-sizing: border-box !important;
                        }

                        /* Fix checkmark size */
                        .fi-pagination-popover-item svg {
                            width: 14px !important;
                            height: 14px !important;
                            min-width: 14px !important;
                            min-height: 14px !important;
                            max-width: 14px !important;
                            max-height: 14px !important;
                            stroke-width: 2.2 !important;
                            color: #1e40af !important;
                            flex-shrink: 0 !important;
                        }






                        /* ================================================
                           FORM SELECT DROPDOWN, SCROLLBAR & STACKING CONTEXT
                        ================================================ */
                        .fi-section:focus-within,
                        .fi-section:has(.choices.is-open),
                        .fi-section:has(.fi-fo-select:focus-within) {
                            position: relative !important;
                            z-index: 30 !important;
                            transform: none !important;
                        }

                        .fi-fo-field-wrp:focus-within,
                        .fi-fo-field-wrp:has(.choices.is-open),
                        .choices.is-open {
                            position: relative !important;
                            z-index: 40 !important;
                        }

                        .fi-section:has(.choices.is-open),
                        .fi-section-content-ctn:has(.choices.is-open),
                        .fi-fo-field-wrp:has(.choices.is-open),
                        .fi-input-wrp:has(.choices.is-open),
                        .fi-fo-select:has(.choices.is-open),
                        .fi-section-content:has(.choices.is-open),
                        .fi-section:has([aria-expanded="true"]),
                        .fi-section-content-ctn:has([aria-expanded="true"]),
                        .fi-section-content:has([aria-expanded="true"]),
                        .fi-fo-field-wrp:has([aria-expanded="true"]),
                        .fi-input-wrp:has([aria-expanded="true"]) {
                            overflow: visible !important;
                        }


                        /* Outer floating dropdown panel */
                        .choices__list--dropdown,
                        .choices__list[aria-expanded],
                        .choices__dropdown,
                        .fi-select-option-list-wrap {
                            background: #ffffff !important;
                            background-color: #ffffff !important;
                            border: 1px solid rgba(30, 64, 175, 0.2) !important;
                            border-radius: 12px !important;
                            box-shadow: 0 12px 32px -4px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08) !important;
                            z-index: 50 !important;
                        }

                        /* Inner list options wrapper - ENABLES SCROLLING */
                        .choices__list--dropdown .choices__list,
                        .choices__list[aria-expanded] .choices__list,
                        .choices__list--dropdown[role="listbox"],
                        .choices__dropdown [role="listbox"],
                        [role="listbox"] {
                            background: #ffffff !important;
                            background-color: #ffffff !important;
                            max-height: 240px !important;
                            overflow-y: auto !important;
                            overflow-x: hidden !important;
                            -webkit-overflow-scrolling: touch !important;
                        }

                        /* Custom modern thin scrollbar for select dropdown options */
                        .choices__list--dropdown .choices__list::-webkit-scrollbar,
                        .choices__list[aria-expanded] .choices__list::-webkit-scrollbar,
                        [role="listbox"]::-webkit-scrollbar {
                            width: 6px !important;
                        }

                        .choices__list--dropdown .choices__list::-webkit-scrollbar-track,
                        .choices__list[aria-expanded] .choices__list::-webkit-scrollbar-track,
                        [role="listbox"]::-webkit-scrollbar-track {
                            background: #f1f5f9 !important;
                            border-radius: 4px !important;
                        }

                        .choices__list--dropdown .choices__list::-webkit-scrollbar-thumb,
                        .choices__list[aria-expanded] .choices__list::-webkit-scrollbar-thumb,
                        [role="listbox"]::-webkit-scrollbar-thumb {
                            background: #cbd5e1 !important;
                            border-radius: 4px !important;
                        }

                        .choices__list--dropdown .choices__list::-webkit-scrollbar-thumb:hover,
                        .choices__list[aria-expanded] .choices__list::-webkit-scrollbar-thumb:hover,
                        [role="listbox"]::-webkit-scrollbar-thumb:hover {
                            background: #94a3b8 !important;
                        }

                        /* Dropdown option items */
                        .choices__list--dropdown .choices__item,
                        .choices__list[aria-expanded] .choices__item {
                            background-color: #ffffff !important;
                            color: #1e293b !important;
                            font-size: 13px !important;
                            padding: 10px 14px !important;
                        }

                        .choices__list--dropdown .choices__item--selectable.is-highlighted,
                        .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
                            background-color: rgba(30, 64, 175, 0.08) !important;
                            color: #1e40af !important;
                            font-weight: 600 !important;
                        }

                        /* Search input inside dropdown */
                        .choices__list--dropdown .choices__input,
                        .choices__list[aria-expanded] .choices__input {
                            background-color: #f8fafc !important;
                            border: 1px solid rgba(0, 0, 0, 0.1) !important;
                            border-radius: 8px !important;
                            margin: 8px !important;
                            width: calc(100% - 16px) !important;
                            font-size: 13px !important;
                            color: #1e293b !important;
                        }

                        .choices__list--dropdown .choices__input:focus,
                        .choices__list[aria-expanded] .choices__input:focus {
                            border-color: #1e40af !important;
                            background-color: #ffffff !important;
                            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.12) !important;
                        }

                        /* ================================================
                           SAFE MOBILE WIDGET OVERRIDE (MAXIMIZED FULL WIDTH 2x2)
                        ================================================ */
                        @media (max-width: 639.98px) {
                            /* Kurangi padding samping (left & right) di mobile agar layar tidak sempit */
                            .fi-main-ctn {
                                padding-left: 12px !important;
                                padding-right: 12px !important;
                                margin-left: auto !important;
                                margin-right: auto !important;
                                width: 100% !important;
                                max-width: 100vw !important;
                                box-sizing: border-box !important;
                            }

                            .fi-page {
                                padding-left: 0 !important;
                                padding-right: 0 !important;
                            }

                            .fi-header {
                                padding: 14px 16px !important;
                                margin-left: 0 !important;
                                margin-right: 0 !important;
                            }

                            /* KONTAINER IBU (MOTHER CONTAINER): Paksa 100% Lebar Layar, Tidak Terhimpit */
                            .fi-page-header-widgets,
                            .fi-widgets,
                            .fi-page-widgets,
                            .fi-page-header-widgets > *,
                            .fi-widgets > *,
                            .fi-page-widgets > *,
                            .fi-wi-stats-overview,
                            .fi-wi-widget,
                            .fi-wi-stats-overview > .fi-section,
                            .fi-section {
                                width: 100% !important;
                                max-width: 100% !important;
                                min-width: 100% !important;
                                grid-column: 1 / -1 !important;
                                box-sizing: border-box !important;
                                flex: 1 1 100% !important;
                            }

                            /* Pangkas padding dalam kontainer ibu */
                            .fi-section-content,
                            .fi-wi-stats-overview > .fi-section > .fi-section-content {
                                padding: 10px 8px !important;
                                width: 100% !important;
                                box-sizing: border-box !important;
                            }

                            /* GRID 3-kartu: Baris 1 = lebar penuh, Baris 2 = 2 persegi */
                            .fi-wi-stats-overview [class*="grid"],
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) {
                                display: grid !important;
                                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                                gap: 0.5rem !important;
                                width: 100% !important;
                                max-width: 100% !important;
                                box-sizing: border-box !important;
                            }

                            /* Override Filament CSS custom props untuk grid */
                            .fi-wi-stats-overview .fi-grid {
                                --cols-default: repeat(2, minmax(0, 1fr)) !important;
                            }

                            /* Kartu pertama (Rapat Hari Ini): persegi panjang lebar penuh */
                            .fi-wi-stats-overview [class*="grid"] > *:first-child,
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) > *:first-child,
                            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(1) {
                                grid-column: span 2 / span 2 !important;
                                --col-span-default: span 2 / span 2 !important;
                            }

                            /* Kartu kedua (Undangan Rapat): persegi kiri bawah */
                            .fi-wi-stats-overview [class*="grid"] > *:nth-child(2),
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) > *:nth-child(2),
                            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(2) {
                                grid-column: span 1 / span 1 !important;
                                --col-span-default: span 1 / span 1 !important;
                            }

                            /* Kartu ketiga (Total Rapat Mendatang): persegi kanan bawah */
                            .fi-wi-stats-overview [class*="grid"] > *:nth-child(3),
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) > *:nth-child(3),
                            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(3) {
                                grid-column: span 1 / span 1 !important;
                                --col-span-default: span 1 / span 1 !important;
                            }
                            
                            /* Kartu Statistik: Semua kartu punya tinggi minimum */
                            .fi-wi-stats-overview-stat {
                                background-color: #ffffff !important;
                                padding: 0.55rem 0.75rem !important;
                                width: 100% !important;
                                min-height: 65px !important;
                                border-radius: 12px !important;
                                border: 1.5px solid rgba(0, 0, 0, 0.07) !important;
                                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
                                display: flex !important;
                                flex-direction: column !important;
                                justify-content: space-between !important;
                                overflow: hidden !important;
                                box-sizing: border-box !important;
                            }

                            /* Kartu pertama: persegi panjang (lebih tinggi) */
                            .fi-wi-stats-overview [class*="grid"] > *:first-child .fi-wi-stats-overview-stat,
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) > *:first-child .fi-wi-stats-overview-stat {
                                min-height: 80px !important;
                                height: auto !important;
                                max-height: none !important;
                            }

                            /* Kartu ke-2 dan ke-3: persegi panjang kecil */
                            .fi-wi-stats-overview [class*="grid"] > *:nth-child(2) .fi-wi-stats-overview-stat,
                            .fi-wi-stats-overview [class*="grid"] > *:nth-child(3) .fi-wi-stats-overview-stat,
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) > *:nth-child(2) .fi-wi-stats-overview-stat,
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) > *:nth-child(3) .fi-wi-stats-overview-stat {
                                aspect-ratio: unset !important;
                                height: 80px !important;
                                min-height: 80px !important;
                                max-height: 80px !important;
                            }
                            
                            .fi-wi-stats-overview-stat-content {
                                flex: 1 1 auto !important;
                                min-width: 0 !important;
                                display: flex !important;
                                flex-direction: column !important;
                                justify-content: space-between !important;
                                height: 100% !important;
                            }

                            .fi-wi-stats-overview-stat-label-ctn {
                                width: 100% !important;
                                display: flex !important;
                                align-items: center !important;
                                justify-content: space-between !important;
                                gap: 0.25rem !important;
                                overflow: hidden !important;
                            }

                            .fi-wi-stats-overview-stat-label {
                                font-size: 0.65rem !important;
                                font-weight: 700 !important;
                                text-transform: uppercase !important;
                                letter-spacing: 0.02em !important;
                                color: #64748b !important;
                                white-space: nowrap !important;
                                overflow: hidden !important;
                                text-overflow: ellipsis !important;
                                line-height: 1.1 !important;
                            }

                            .fi-wi-stats-overview-stat-label-ctn svg {
                                width: 15px !important;
                                height: 15px !important;
                                flex-shrink: 0 !important;
                            }

                            .fi-wi-stats-overview-stat-value {
                                font-size: 1.3rem !important;
                                font-weight: 800 !important;
                                color: #0f172a !important;
                                line-height: 1.1 !important;
                                margin-top: 0.05rem !important;
                                margin-bottom: 0.05rem !important;
                            }

                            .fi-wi-stats-overview-stat-description {
                                font-size: 0.62rem !important;
                                line-height: 1.1 !important;
                                white-space: nowrap !important;
                                overflow: hidden !important;
                                text-overflow: ellipsis !important;
                            }

                            /* Sembunyikan kode dokumen di mobile */
                            .fi-doc-code-num {
                                display: none !important;
                                visibility: hidden !important;
                                opacity: 0 !important;
                            }

                            /* Optimasi Tombol Aksi Tabel di Mobile agar Semua Tombol Muat Layar HP */
                            .fi-ta-actions {
                                gap: 3px !important;
                                flex-wrap: nowrap !important;
                                align-items: center !important;
                            }

                            .fi-ta-actions-cell {
                                padding-left: 2px !important;
                                padding-right: 4px !important;
                            }

                            .fi-ta-actions .fi-btn {
                                padding: 3px 5px !important;
                                font-size: 10px !important;
                                line-height: 1.1 !important;
                                gap: 3px !important;
                                border-radius: 6px !important;
                                font-weight: 600 !important;
                                white-space: nowrap !important;
                            }

                            .fi-ta-actions .fi-btn svg {
                                width: 12px !important;
                                height: 12px !important;
                            }

                            /* Sembunyikan chart canvas di mobile agar kartu 2x2 tetap berbentuk persegi panjang horizontal */
                            .fi-wi-stats-overview-stat-chart,
                            .fi-wi-stats-overview-stat-chart canvas {
                                display: none !important;
                                height: 0 !important;
                                max-height: 0 !important;
                                opacity: 0 !important;
                                visibility: hidden !important;
                            }

                            /* Sempurnakan Tombol Form Actions (Simpan, Kembali) di Mobile */
                            .fi-form-actions,
                            .fi-page-actions {
                                display: flex !important;
                                flex-direction: row !important;
                                flex-wrap: nowrap !important;
                                gap: 10px !important;
                                width: 100% !important;
                                padding: 14px 0 !important;
                                margin-top: 4px !important;
                            }

                            .fi-form-actions .fi-btn,
                            .fi-page-actions .fi-btn {
                                flex: 1 !important; /* Membagi ruang sama rata atau sesuai konten */
                                height: 42px !important;
                                border-radius: 10px !important;
                                font-size: 13.5px !important;
                                font-weight: 600 !important;
                                display: inline-flex !important;
                                align-items: center !important;
                                justify-content: center !important;
                                padding: 0 16px !important;
                                white-space: nowrap !important;
                            }

                            /* Beri porsi lebih besar pada tombol utama (Simpan Perubahan) jika mau, tapi flex: 1 biasanya sudah bagus */
                            .fi-form-actions .fi-btn[type="submit"] {
                                flex: 1.5 !important;
                            }

                            /* Pastikan tombol sekunder (Kembali) tidak transparan */
                            .fi-form-actions .fi-btn:not(.fi-color-primary):not([type="submit"]),
                            .fi-page-actions .fi-btn:not(.fi-color-primary):not([type="submit"]),
                            .fi-form-actions .fi-btn.fi-color-gray,
                            .fi-page-actions .fi-btn.fi-color-gray,
                            .fi-form-actions .fi-btn.fi-btn-color-gray,
                            .fi-page-actions .fi-btn.fi-btn-color-gray {
                                background-color: #ffffff !important;
                                border: 1px solid #cbd5e1 !important;
                                color: #334155 !important;
                                box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
                            }

                            /* Override pseudo-element ring border from Filament if any */
                            .fi-form-actions .fi-btn.fi-color-gray::before,
                            .fi-page-actions .fi-btn.fi-color-gray::before,
                            .fi-form-actions .fi-btn.fi-color-gray::after,
                            .fi-page-actions .fi-btn.fi-color-gray::after {
                                border: none !important;
                                box-shadow: none !important;
                            }

                            .fi-form-actions .fi-btn *,
                            .fi-page-actions .fi-btn * {
                                font-size: 13.5px !important;
                            }

                            .fi-form-actions .fi-btn svg,
                            .fi-page-actions .fi-btn svg {
                                width: 16px !important;
                                height: 16px !important;
                                margin-right: 4px !important;
                            }
                        }


                    </style>



                ')
            )

            ->renderHook(
                'panels::user-menu.before',
                fn (): string => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Blade::render('@livewire(\'notification-bell\')') : ''
            )
            ->spa()
            ->globalSearch()
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                // Widgets are automatically discovered via discoverWidgets()
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()->navigationGroup('Peran & Izin')->navigationSort(5),
                ActivityLogPlugin::make(),
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
