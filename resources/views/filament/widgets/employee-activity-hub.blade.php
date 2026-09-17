<x-filament-widgets::widget>
    @php
        $todayLoad = $todayMeetingsCount + $documentsNeedActionCount + $unreadNotificationsCount;
        $focusTone = match (true) {
            $todayLoad >= 8 => ['label' => 'Padat', 'class' => 'eah-chip--rose'],
            $todayLoad >= 4 => ['label' => 'Aktif', 'class' => 'eah-chip--amber'],
            default => ['label' => 'Terkendali', 'class' => 'eah-chip--emerald'],
        };
    @endphp

    <div class="eah-shell" x-data="{ tab: 'focus' }">
        <div class="eah-hero eah-animate" style="--eah-delay: 0ms;">
            {{-- Floating geometric background shapes — clearly animated --}}
            <span class="eah-geo eah-geo--circle-lg" aria-hidden="true"></span>
            <span class="eah-geo eah-geo--square-rnd" aria-hidden="true"></span>
            <span class="eah-geo eah-geo--blob" aria-hidden="true"></span>
            <span class="eah-geo eah-geo--ring" aria-hidden="true"></span>

            <div class="eah-hero-content">
                {{-- "Dasbor" label replaces the Filament page header --}}
                <div class="eah-page-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:middle;opacity:0.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                    Dasbor
                </div>
                <h2 class="eah-title">{{ $greeting }}, {{ $userName }}</h2>
                <p class="eah-subtitle">{{ $subtitle }}</p>

                <div class="eah-meta">
                    <span class="eah-chip {{ $focusTone['class'] }}">{{ $focusTone['label'] }}</span>
                    <span class="eah-meta-text">{{ $organizationPath }}</span>
                </div>
            </div>

            <div class="eah-next-card">
                <div class="eah-next-label">Agenda terdekat</div>

                @if ($nextMeeting)
                    <a href="{{ $nextMeeting['url'] }}" wire:navigate class="eah-next-link">
                        <div class="eah-next-title">{{ $nextMeeting['title'] }}</div>
                        <div class="eah-next-time">{{ $nextMeeting['when'] }}</div>
                        <div class="eah-next-location">{{ $nextMeeting['location'] }}</div>
                    </a>
                @else
                    <div class="eah-next-empty">
                        Belum ada agenda rapat mendatang.
                    </div>
                @endif
            </div>
        </div>

        {{-- Stat Cards — uniform white, single teal accent --}}
        <div class="eah-stats">
            <div class="eah-stat eah-animate" style="--eah-delay: 70ms;">
                <div class="eah-stat-bar"></div>
                <div class="eah-stat-icon">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="eah-icon" />
                </div>
                <div class="eah-stat-content">
                    <div class="eah-stat-value">{{ $todayMeetingsCount }}</div>
                    <div class="eah-stat-label">Agenda hari ini</div>
                </div>
            </div>

            <div class="eah-stat eah-animate" style="--eah-delay: 140ms;">
                <div class="eah-stat-bar"></div>
                <div class="eah-stat-icon">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="eah-icon" />
                </div>
                <div class="eah-stat-content">
                    <div class="eah-stat-value">{{ $documentsNeedActionCount }}</div>
                    <div class="eah-stat-label">Perlu tindakan</div>
                </div>
            </div>

            <div class="eah-stat eah-animate" style="--eah-delay: 210ms;">
                <div class="eah-stat-bar"></div>
                <div class="eah-stat-icon">
                    <x-filament::icon icon="heroicon-o-bell" class="eah-icon" />
                </div>
                <div class="eah-stat-content">
                    <div class="eah-stat-value">{{ $unreadNotificationsCount }}</div>
                    <div class="eah-stat-label">Notifikasi belum dibaca</div>
                </div>
            </div>
        </div>

    </div>

    <style>
        [x-cloak] { display: none !important; }

        /* ─── SHELL ──────────────────────────────────────────────── */
        .eah-shell {
            display: grid;
            gap: 1rem;
        }

        /* ─── ENTRY ANIMATION ────────────────────────────────────── */
        .eah-hero,
        .eah-stat,
        .eah-panel {
            opacity: 0;
            transform: translateY(14px);
            animation: eah-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: var(--eah-delay, 0ms);
        }

        @keyframes eah-rise {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ─── HERO ─────────────────────────────────────────────────
           Single hero — replaces the Filament page header bar.
           Stays dark teal; all other cards are white.
        ──────────────────────────────────────────────────────────── */
        .eah-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(260px, 0.85fr);
            gap: 1.25rem;
            padding: 1.75rem 2rem;
            border-radius: 1rem;
            background:
                radial-gradient(ellipse at top right, rgba(110,231,183,0.15) 0%, transparent 55%),
                radial-gradient(ellipse at bottom left, rgba(16,185,129,0.18) 0%, transparent 55%),
                linear-gradient(150deg, #134e4a 0%, #0d9488 55%, #065f46 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow:
                0 8px 32px -8px rgba(13,148,136,0.45),
                0 2px 8px -2px rgba(0,0,0,0.15),
                inset 0 1px 0 rgba(255,255,255,0.08);
        }

        /* Shimmer sweep */
        .eah-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 20%, rgba(255,255,255,0.05) 50%, transparent 80%);
            transform: translateX(-120%);
            animation: eah-shimmer 8s linear infinite;
            pointer-events: none;
        }

        @keyframes eah-shimmer {
            to { transform: translateX(120%); }
        }

        /* Text layers sit above geo shapes */
        .eah-hero-content {
            position: relative;
            z-index: 2;
        }

        /* ─── FLOATING GEOMETRIC SHAPES ─────────────────────────────
           Each shape uses a simple translateY up-down animation
           so the movement is immediately visible when the page is idle.
        ──────────────────────────────────────────────────────────── */
        .eah-geo {
            position: absolute;
            pointer-events: none;
        }

        /* Large radial circle — top-right */
        .eah-geo--circle-lg {
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(94,234,212,0.18) 0%, transparent 65%);
            top: -70px;
            right: -50px;
            z-index: 1;
            animation: geo-float-a 6s ease-in-out infinite;
        }

        /* Rounded square — bottom-left */
        .eah-geo--square-rnd {
            width: 110px;
            height: 110px;
            border-radius: 24px;
            border: 2px solid rgba(167,243,208,0.18);
            bottom: -25px;
            left: 6%;
            z-index: 1;
            animation: geo-float-b 7s ease-in-out infinite;
            animation-delay: -2s;
        }

        /* Organic blob — center */
        .eah-geo--blob {
            width: 160px;
            height: 160px;
            border-radius: 60% 40% 70% 30% / 50% 60% 40% 50%;
            background: rgba(52,211,153,0.09);
            top: 10px;
            left: 38%;
            z-index: 1;
            animation: geo-float-a 5s ease-in-out infinite;
            animation-delay: -1s;
        }

        /* Ring — lower-right */
        .eah-geo--ring {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(167,243,208,0.14);
            bottom: 18px;
            right: 28%;
            z-index: 1;
            animation: geo-float-b 8s ease-in-out infinite;
            animation-delay: -3s;
        }

        /* ── float animations: clear up-down motion, ease-in-out ── */
        @keyframes geo-float-a {
            0%   { transform: translateY(0px); }
            50%  { transform: translateY(-18px); }
            100% { transform: translateY(0px); }
        }

        @keyframes geo-float-b {
            0%   { transform: translateY(0px) rotate(0deg); }
            50%  { transform: translateY(-14px) rotate(6deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        /* ─── HERO TEXT ──────────────────────────────────────────── */
        .eah-page-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(167,243,208,0.8);
            margin-bottom: 0.55rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .eah-title {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.15;
            margin: 0;
            text-shadow: 0 2px 8px rgba(0,0,0,0.18);
        }

        .eah-subtitle {
            max-width: 60ch;
            margin-top: 0.6rem;
            color: rgba(255,255,255,0.82);
            line-height: 1.6;
            font-size: 0.92rem;
        }

        .eah-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            align-items: center;
            margin-top: 1rem;
        }

        .eah-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .eah-chip--rose    { background: rgba(254,205,211,0.2); color: #fecdd3; border: 1px solid rgba(254,205,211,0.25); }
        .eah-chip--amber   { background: rgba(253,230,138,0.2); color: #fde68a; border: 1px solid rgba(253,230,138,0.25); }
        .eah-chip--emerald { background: rgba(167,243,208,0.2); color: #a7f3d0; border: 1px solid rgba(167,243,208,0.25); }

        .eah-meta-text {
            color: rgba(255,255,255,0.75);
            font-size: 0.86rem;
        }

        /* ─── NEXT MEETING CARD (inside hero) ────────────────────── */
        .eah-next-card {
            border-radius: 0.85rem;
            padding: 1.1rem 1.2rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(167,243,208,0.18);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
            transition: background 0.3s ease;
        }

        .eah-next-card:hover { background: rgba(255,255,255,0.15); }

        .eah-next-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: rgba(167,243,208,0.85);
            margin-bottom: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .eah-next-link {
            color: #fff;
            text-decoration: none;
            display: grid;
            gap: 0.3rem;
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }

        .eah-next-link:hover { transform: translateY(-2px); }

        .eah-next-title {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .eah-next-time,
        .eah-next-location,
        .eah-next-empty {
            color: rgba(255,255,255,0.76);
            line-height: 1.5;
            font-size: 0.86rem;
        }

        /* ─── STAT CARDS ─────────────────────────────────────────────
           Uniform white — same card system as all other widgets.
           ONE teal accent bar on top; no per-card color competition.
        ──────────────────────────────────────────────────────────── */
        .eah-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .eah-stat {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #fff;
            border-radius: 1rem;
            padding: 1rem 1.5rem 1rem 1rem;
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.28s cubic-bezier(0.16,1,0.3,1), box-shadow 0.28s ease, border-color 0.28s ease;
        }

        .eah-stat:hover {
            transform: translateY(-4px);
            border-color: rgba(13,148,136,0.22);
            box-shadow: 0 0 0 3px rgba(13,148,136,0.06), 0 8px 24px rgba(13,148,136,0.12);
        }

        /* Top teal accent bar — same teal across all three */
        .eah-stat-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #0d9488, #2dd4bf);
            border-radius: 0 0 3px 3px;
        }

        .eah-stat-icon {
            flex-shrink: 0;
            width: 3rem;
            height: 3rem;
            border-radius: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13,148,136,0.1);
            color: #0d9488;
            transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }

        .eah-stat:hover .eah-stat-icon {
            transform: scale(1.12) rotate(6deg);
        }

        .eah-icon {
            width: 1.45rem;
            height: 1.45rem;
        }

        .eah-stat-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .eah-stat-value {
            font-size: 1.7rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.04em;
            color: #0f172a;
        }

        .eah-stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            margin-top: 0.18rem;
        }

        /* ─── PANELS / LISTS (kept for backward compat) ──────────── */
        .eah-grid {
            display: grid;
            grid-template-columns: minmax(0,1.7fr) minmax(280px,0.95fr);
            gap: 1rem;
        }

        .eah-panel {
            background: #fff;
            border-radius: 1rem;
            padding: 1.2rem;
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s ease;
        }

        .eah-panel:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px -8px rgba(13,148,136,0.25);
        }

        .eah-panel-head {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .eah-panel-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
        }

        .eah-panel-desc {
            margin-top: 0.2rem;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        /* ─── TABS ───────────────────────────────────────────────── */
        .eah-tabs {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem;
            border-radius: 999px;
            background: #f0fdfa;
            border: 1px solid rgba(13,148,136,0.15);
        }

        .eah-tab {
            border: none;
            background: transparent;
            color: #475569;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .eah-tab--active {
            background: #fff;
            color: #0d9488;
            box-shadow: 0 2px 8px -2px rgba(13,148,136,0.3);
        }

        /* ─── LIST ITEMS ─────────────────────────────────────────── */
        .eah-list { display: grid; gap: 0.7rem; }
        .eah-tab-stage { min-height: 10rem; }

        .eah-item,
        .eah-action {
            opacity: 0;
            transform: translateY(10px);
            animation: eah-rise 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
            animation-delay: var(--eah-delay, 0ms);
        }

        .eah-item {
            display: flex;
            justify-content: space-between;
            gap: 0.9rem;
            align-items: center;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            color: inherit;
            background: #fafafa;
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1), border-color 0.22s ease, box-shadow 0.22s ease;
        }

        .eah-item:hover,
        .eah-action:hover {
            transform: translateY(-2px);
            border-color: rgba(13,148,136,0.22);
            box-shadow: 0 4px 16px -8px rgba(13,148,136,0.3);
        }

        .eah-item-main { min-width: 0; }

        .eah-item-title,
        .eah-action-title,
        .eah-note-title {
            font-weight: 700;
            color: #0f172a;
        }

        .eah-item-meta-line,
        .eah-action-desc,
        .eah-note-text,
        .eah-empty-text {
            margin-top: 0.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.83rem;
            line-height: 1.5;
        }

        .eah-item-code {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.4rem;
            border-radius: 999px;
            background: rgba(13,148,136,0.1);
            color: #0d9488;
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* ─── BADGES ─────────────────────────────────────────────── */
        .eah-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .eah-badge--amber, .eah-badge--warning { background: #fef3c7; color: #92400e; }
        .eah-badge--rose                        { background: #ffe4e6; color: #be123c; }
        .eah-badge--sky, .eah-badge--info       { background: #e0f2fe; color: #0369a1; }
        .eah-badge--slate, .eah-badge--gray     { background: #e2e8f0; color: #334155; }
        .eah-badge--emerald, .eah-badge--success{ background: #dcfce7; color: #166534; }

        /* ─── EMPTY STATE ────────────────────────────────────────── */
        .eah-empty {
            border-radius: 0.75rem;
            padding: 1.2rem;
            background: #fafafa;
            border: 1px dashed rgba(0,0,0,0.1);
        }

        .eah-empty-title { font-weight: 700; color: #0f172a; }

        /* ─── ACTIONS ────────────────────────────────────────────── */
        .eah-action-list { display: grid; gap: 0.7rem; }

        .eah-action-list--horizontal {
            grid-template-columns: repeat(auto-fit, minmax(260px,1fr));
        }

        .eah-action {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.25rem 0.85rem 0.85rem;
            border-radius: 1rem;
            text-decoration: none;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.28s cubic-bezier(0.16,1,0.3,1);
            color: inherit;
        }

        .eah-action:hover {
            transform: translateY(-3px);
            border-color: rgba(13,148,136,0.22);
            box-shadow: 0 6px 20px -6px rgba(13,148,136,0.2);
        }

        .eah-action-icon {
            flex-shrink: 0;
            width: 3rem;
            height: 3rem;
            border-radius: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }

        .eah-action:hover .eah-action-icon { transform: scale(1.1) rotate(6deg); }

        /* All action icons → teal (single accent) */
        .eah-action--indigo .eah-action-icon,
        .eah-action--blue   .eah-action-icon {
            background: rgba(13,148,136,0.12);
            color: #0d9488;
        }

        .eah-action--emerald .eah-action-icon {
            background: rgba(5,150,105,0.12);
            color: #059669;
        }

        .eah-action--amber .eah-action-icon {
            background: rgba(217,119,6,0.1);
            color: #d97706;
        }

        .eah-action--rose .eah-action-icon {
            background: rgba(225,29,72,0.1);
            color: #e11d48;
        }

        .eah-action-text { min-width: 0; display: grid; gap: 0.2rem; }

        /* ─── NOTE ───────────────────────────────────────────────── */
        .eah-note {
            margin-top: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 0.85rem;
            background: linear-gradient(135deg, #134e4a, #065f46);
            color: #fff;
        }

        .eah-note-title { color: #fff; }
        .eah-note-text  { color: rgba(255,255,255,0.8); }

        /* ─── PANEL TRANSITIONS ──────────────────────────────────── */
        .eah-panel-enter       { transition: opacity 0.18s ease, transform 0.22s cubic-bezier(0.22,1,0.36,1); }
        .eah-panel-enter-from,
        .eah-panel-leave-to    { opacity: 0; transform: translateY(8px); }
        .eah-panel-enter-to,
        .eah-panel-leave-from  { opacity: 1; transform: translateY(0); }
        .eah-panel-leave       { transition: opacity 0.14s ease, transform 0.14s ease; }

        /* ─── TABLET GRID COLLAPSE (NOT mobile) ──────────────────── */
        @media (max-width: 1024px) {
            .eah-hero,
            .eah-grid,
            .eah-stats {
                grid-template-columns: 1fr;
            }
        }

        /* ═══ MOBILE OVERRIDES — DO NOT MODIFY ══════════════════════ */
        .eah-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start; }

        @media (max-width: 900px) {
            .eah-grid-2col { grid-template-columns: 1fr; }
        }

        @media (max-width: 767.98px) {
            .eah-hero {
                padding: 0.9rem 1rem !important;
                gap: 0.75rem !important;
                border-radius: 1rem !important;
            }

            .eah-kicker {
                font-size: 0.65rem !important;
                margin-bottom: 0.2rem !important;
            }

            .eah-title {
                font-size: 1.2rem !important;
                line-height: 1.2 !important;
            }

            .eah-subtitle {
                font-size: 0.8rem !important;
                margin-top: 0.3rem !important;
                line-height: 1.4 !important;
            }

            .eah-meta {
                margin-top: 0.5rem !important;
                gap: 0.4rem !important;
            }

            .eah-chip {
                padding: 0.2rem 0.6rem !important;
                font-size: 0.7rem !important;
            }

            .eah-meta-text {
                font-size: 0.78rem !important;
            }

            .eah-next-card {
                padding: 0.65rem 0.85rem !important;
                border-radius: 0.8rem !important;
            }

            .eah-next-label {
                font-size: 0.65rem !important;
                margin-bottom: 0.3rem !important;
            }

            .eah-next-title {
                font-size: 0.88rem !important;
            }

            .eah-next-time,
            .eah-next-location,
            .eah-next-empty {
                font-size: 0.76rem !important;
                line-height: 1.35 !important;
            }

            .eah-stat {
                padding: 0.5rem 1.25rem 0.5rem 0.5rem !important;
                border-radius: 999px !important;
                gap: 0.85rem !important;
            }

            .eah-stat-icon {
                width: 2.8rem !important;
                height: 2.8rem !important;
                border-radius: 50% !important;
            }

            .eah-stat-value {
                font-size: 1.35rem !important;
            }

            .eah-stat-label {
                font-size: 0.75rem !important;
                margin-top: 0.1rem !important;
            }

            .eah-icon {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }

            .eah-panel {
                padding: 0.9rem !important;
                border-radius: 0.9rem !important;
            }

            .eah-panel-head {
                flex-direction: column;
            }

            .eah-tabs {
                width: 100%;
                justify-content: space-between;
            }

            .eah-tab {
                flex: 1;
                text-align: center;
                padding: 0.4rem 0.6rem !important;
                font-size: 0.75rem !important;
            }

            .eah-item,
            .eah-action {
                flex-direction: column;
                align-items: flex-start;
                padding: 0.7rem 0.85rem !important;
            }
        }
        /* ══════════════════════════════════════════════════════════ */
    </style>
</x-filament-widgets::widget>
