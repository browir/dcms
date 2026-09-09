<?php

namespace App\Providers\Filament;

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

class ReviewerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('reviewer')
            ->path('reviewer')
            ->maxContentWidth(Width::Full)
            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            ->topNavigation()
            ->favicon(asset('images/logo.png').'?v='.(@filemtime(public_path('images/logo.png')) ?: 1))
            ->login()
            ->colors([
                'primary' => Color::Emerald,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
                'info' => Color::Sky,
            ])
            ->darkMode(false)
            ->brandName('DCMS Reviewer')
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
                'panels::styles.after',
                fn (): string => new HtmlString('
                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300..800&display=swap");

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


                        /* ================================================
                           REVIEWER PANEL — PREMIUM DESIGN
                        ================================================ */

                        * { font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important; }

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
                            }

                            .fi-ta table,
                            .fi-ta .fi-ta-table,
                            .fi-ta table > tbody,
                            .fi-ta .fi-ta-table > tbody {
                                display: block !important;
                                width: 100% !important;
                                box-sizing: border-box !important;
                                background: transparent !important;
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








                        /* Hide Audit Trail / Relation Managers on Mobile (< 768px) */
                        @media (max-width: 767.98px) {
                            .fi-page-relation-managers,
                            .fi-resource-relation-manager {
                                display: none !important;
                            }
                        }

                        /* ── Navbar ─────────────────────────────────── */
                        .fi-topbar,
                        .fi-topbar header {
                            background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%) !important;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
                        }

                        .fi-topbar nav *:not(.nb-wrap):not(.nb-wrap *) {
                            background-color: transparent !important;
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
                        .fi-topbar-item:hover a,
                        .fi-topbar-item:hover button {
                            background-color: rgba(255, 255, 255, 0.1) !important;
                        }

                        .fi-topbar-item:hover .fi-topbar-item-label,
                        .fi-topbar-item:hover .fi-topbar-item-icon {
                            color: #ffffff !important;
                        }

                        .fi-topbar-item.fi-active,
                        .fi-topbar-item.fi-active a {
                            background-color: rgba(255, 255, 255, 0.15) !important;
                            border-radius: 8px !important;
                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2) !important;
                        }

                        .fi-topbar-item.fi-active .fi-topbar-item-label,
                        .fi-topbar-item.fi-active .fi-topbar-item-icon {
                            color: #ffffff !important;
                            font-weight: 700 !important;
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







                        .fi-ta-actions .fi-btn {
                            border-radius: 8px !important;
                            font-size: 11.5px !important;
                            font-weight: 600 !important;
                            padding: 5px 10px !important;
                            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
                        }

                        .fi-btn svg,
                        .fi-ac-btn-action svg,
                        .fi-icon-btn svg,
                        .fi-ta-actions .fi-btn svg,
                        button[type="submit"] svg,
                        .fi-modal-close-btn svg {
                            transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s ease, filter 0.2s ease !important;
                            transform-origin: center !important;
                            will-change: transform !important;
                        }

                        .fi-ta-actions .fi-btn:hover {
                            transform: translateY(-1.5px) !important;
                            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important;
                        }

                        .fi-btn:hover svg,
                        .fi-ac-btn-action:hover svg,
                        .fi-icon-btn:hover svg,
                        .fi-ta-actions .fi-btn:hover svg,
                        button[type="submit"]:hover svg,
                        .fi-modal-close-btn:hover svg {
                            transform: translateY(-1px) scale(1.1) rotate(-5deg) !important;
                            filter: drop-shadow(0 2px 4px rgba(5, 150, 105, 0.24)) !important;
                        }

                        .fi-btn:active svg,
                        .fi-ac-btn-action:active svg,
                        .fi-icon-btn:active svg,
                        .fi-ta-actions .fi-btn:active svg,
                        button[type="submit"]:active svg,
                        .fi-modal-close-btn:active svg {
                            transform: scale(0.94) rotate(0deg) !important;
                        }





                        /* ── Page ───────────────────────────────────── */
                        body, .fi-main, .fi-layout {
                            background-color: #f0fdf4 !important;
                        }

                        /* ── Widget Cards ────────────────────────────── */
                        .fi-wi-stats-overview-stat {
                            background-color: #ffffff !important;
                            border-radius: 16px !important;
                            border: 1px solid rgba(0, 0, 0, 0.06) !important;
                            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04) !important;
                            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                                        box-shadow 0.25s ease,
                                        border-color 0.25s ease !important;
                        }

                        .fi-wi-stats-overview-stat:hover {
                            transform: translateY(-3px) !important;
                            box-shadow: 0 12px 24px -6px rgba(5, 150, 105, 0.12) !important;
                            border-color: rgba(5, 150, 105, 0.2) !important;
                        }

                        /* ── Stats container transparent ────────────── */
                        .fi-wi-stats-overview,
                        .fi-sc-component,
                        .fi-grid-col {
                            background: transparent !important;
                            border: none !important;
                            box-shadow: none !important;
                            padding: 0 !important;
                            margin: 0 !important;
                            border-radius: 0 !important;
                        }

                        /* ── Section cards ───────────────────────────── */
                        .fi-section {
                            background: #ffffff !important;
                            border: 1px solid rgba(0,0,0,.05) !important;
                            border-radius: 16px !important;
                            box-shadow: 0 2px 12px -2px rgba(0,0,0,.04) !important;
                            transition: transform 0.3s ease, box-shadow 0.3s ease !important;
                        }

                        .fi-section:hover {
                            transform: translateY(-2px) !important;
                            box-shadow: 0 12px 28px -8px rgba(5, 150, 105, 0.1) !important;
                        }

                        /* ── Section header accent ──────────────────── */
                        .fi-section-header {
                            position: relative;
                            padding-bottom: 12px !important;
                            margin-bottom: 8px !important;
                            border-bottom: 1px solid rgba(0,0,0,.04) !important;
                        }

                        .fi-section-header::before {
                            content: "";
                            position: absolute;
                            left: 0; top: 4px; bottom: 4px;
                            width: 3.5px;
                            background: linear-gradient(180deg, #059669 0%, #10b981 100%);
                            border-radius: 0 6px 6px 0;
                            box-shadow: 0 0 8px rgba(5, 150, 105, 0.4);
                        }

                        .fi-section-header-heading {
                            font-size: 14px !important;
                            font-weight: 800 !important;
                            color: #0f172a !important;
                            padding-left: 14px !important;
                        }

                        /* ── Table rows ──────────────────────────────── */
                        .fi-ta-row {
                            transition: background-color 0.15s ease !important;
                        }

                        .fi-ta-row:hover {
                            background-color: rgba(5, 150, 105, 0.04) !important;
                        }

                        /* ── Badge animations ───────────────────────── */
                        .fi-badge {
                            animation: badgePop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
                            animation-delay: 0.3s;
                        }

                        @keyframes badgePop {
                            0%   { transform: scale(0.6); opacity: 0; }
                            70%  { transform: scale(1.1); }
                            100% { transform: scale(1);   opacity: 1; }
                        }

                        /* ── Review action buttons ──────────────────── */
                        .fi-ac-action.fi-color-success {
                            background: linear-gradient(135deg, #059669, #10b981) !important;
                            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3) !important;
                            transition: all 0.25s ease !important;
                        }

                        .fi-ac-action.fi-color-success:hover {
                            transform: translateY(-2px) !important;
                            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.4) !important;
                        }

                        .fi-ac-action.fi-color-danger {
                            background: linear-gradient(135deg, #e11d48, #f43f5e) !important;
                            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.3) !important;
                            transition: all 0.25s ease !important;
                        }

                        .fi-ac-action.fi-color-danger:hover {
                            transform: translateY(-2px) !important;
                            box-shadow: 0 8px 24px rgba(225, 29, 72, 0.4) !important;
                        }

                        /* ── Reviewer badge/label ───────────────────── */
                        .reviewer-role-badge {
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                            padding: 4px 12px;
                            border-radius: 50px;
                            font-size: 11px;
                            font-weight: 700;
                            letter-spacing: 0.05em;
                            text-transform: uppercase;
                        }

                        .reviewer-role-badge--kabid {
                            background: linear-gradient(135deg, #dbeafe, #eff6ff);
                            color: #1d4ed8;
                            border: 1px solid rgba(29, 78, 216, 0.15);
                        }

                        .reviewer-role-badge--direktur {
                            background: linear-gradient(135deg, #fce7f3, #fdf2f8);
                            color: #be185d;
                            border: 1px solid rgba(190, 24, 93, 0.15);
                        }

                        /* ── Select Dropdown & Stacking Context Fix ─ */
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
                        .fi-fo-select:has(.choices.is-open) {
                            overflow: visible !important;
                        }

                        /* Outer floating dropdown panel */
                        .choices__list--dropdown,
                        .choices__list[aria-expanded],
                        .choices__dropdown,
                        .fi-select-option-list-wrap {
                            background: #ffffff !important;
                            background-color: #ffffff !important;
                            border: 1px solid rgba(5, 150, 105, 0.2) !important;
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
                            background-color: rgba(5, 150, 105, 0.08) !important;
                            color: #047857 !important;
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
                            border-color: #059669 !important;
                            background-color: #ffffff !important;
                            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12) !important;
                        }

                        /* ================================================
                           SAFE MOBILE WIDGET OVERRIDE (MAXIMIZED FULL WIDTH 2x2)
                        ================================================ */
                        @media (max-width: 639.98px) {
                            /* Kurangi padding samping (left & right) di mobile agar layar tidak sempit */
                            .fi-main-ctn {
                                padding-left: 8px !important;
                                padding-right: 8px !important;
                            }

                            .fi-page {
                                padding-left: 0 !important;
                                padding-right: 0 !important;
                            }

                            /* Pangkas padding section container di mobile */
                            .fi-section-content,
                            .fi-wi-stats-overview > .fi-section > .fi-section-content {
                                padding: 8px 6px !important;
                            }

                            .fi-header {
                                padding: 14px 16px !important;
                                margin-left: 0 !important;
                                margin-right: 0 !important;
                            }

                            /* Pastikan container utama widget membentang 100% */
                            .fi-page-header-widgets,
                            .fi-page-header-widgets > *,
                            .fi-wi-stats-overview,
                            .fi-wi-stats-overview > .fi-section,
                            .fi-wi-stats-overview > .fi-section > .fi-section-content {
                                width: 100% !important;
                                max-width: 100% !important;
                                min-width: 100% !important;
                                flex: 1 1 100% !important;
                                grid-column: 1 / -1 !important;
                            }

                            /* Grid 2 Kolom Menyamping (2x2) dengan gap mepet 6px */
                            .fi-wi-stats-overview [class*="grid"],
                            .fi-wi-stats-overview div:has(> .fi-wi-stats-overview-stat) {
                                display: grid !important;
                                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                                gap: 0.375rem !important;
                            }
                            
                            /* Kartu Statistik: Persegi panjang horizontal ringkas & presisi (2x2) */
                            .fi-wi-stats-overview-stat {
                                background-color: #ffffff !important;
                                padding: 0.55rem 0.75rem !important;
                                width: 100% !important;
                                height: 70px !important;
                                min-height: 70px !important;
                                max-height: 70px !important;
                                border-radius: 12px !important;
                                border: 1.5px solid rgba(0, 0, 0, 0.07) !important;
                                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
                                display: flex !important;
                                flex-direction: column !important;
                                justify-content: space-between !important;
                                overflow: hidden !important;
                                box-sizing: border-box !important;
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
                        }

                    </style>



                ')
            )
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Blade::render('@livewire(\'notification-bell\')') : ''
            )
            ->renderHook(
                'panels::scripts.after',
                fn (): string => new \Illuminate\Support\HtmlString("<script>(function(){function adjustPanelForButton(btn){try{const id=btn.getAttribute(\"aria-controls\")||btn.getAttribute(\"data-dropdown-id\");let panel=id?document.getElementById(id):null;if(!panel){const panels=Array.from(document.querySelectorAll('.fi-dropdown-panel'));panel=panels.find(p=>{return p&&p.offsetParent!==null})||panels[0];}if(!panel)return;if(panel.dataset.__fixed)return;const rect=btn.getBoundingClientRect();panel.style.position=\"fixed\";panel.style.zIndex=\"2147483647\";panel.style.transform=\"none\";const placement=(panel.getAttribute(\"data-placement\")||panel.getAttribute(\"x-placement\")||\"bottom\").toString();const panelWidth=panel.offsetWidth||200;let left=rect.right-panelWidth;if(left<8)left=8;let top;if(placement.startsWith(\"top\")){top=rect.top-panel.offsetHeight-8;}else{top=rect.bottom+8;}panel.style.left=left+\"px\";panel.style.top=top+\"px\";panel.style.maxHeight=\"320px\";panel.style.overflowY=\"auto\";panel.dataset.__fixed=\"1\";}catch(e){console.error(e)}}function tryAdjustFromActive(){const activeBtn=document.querySelector('button[aria-expanded=\"true\"]');if(activeBtn)adjustPanelForButton(activeBtn);}document.addEventListener('click',function(e){const btn=e.target.closest('button[aria-controls],button[aria-haspopup]');if(btn) setTimeout(()=>adjustPanelForButton(btn),20);},true);const mo=new MutationObserver((mutations)=>{for(const m of mutations){for(const node of m.addedNodes){if(!(node instanceof HTMLElement))continue;if(node.classList&&node.classList.contains('fi-dropdown-panel')){setTimeout(tryAdjustFromActive,20);}}}});mo.observe(document.body,{childList:true,subtree:true});window.addEventListener('resize',tryAdjustFromActive);window.addEventListener('scroll',tryAdjustFromActive,true);})();</script>")
            )
            ->spa()
            ->discoverResources(in: app_path('Filament/Reviewer/Resources'), for: 'App\\Filament\\Reviewer\\Resources')
            ->discoverPages(in: app_path('Filament/Reviewer/Pages'), for: 'App\\Filament\\Reviewer\\Pages')
            ->discoverWidgets(in: app_path('Filament/Reviewer/Widgets'), for: 'App\\Filament\\Reviewer\\Widgets')
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
            ->authMiddleware([Authenticate::class]);
    }
}
