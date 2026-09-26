<x-filament-widgets::widget>
    <div class="mts-wrap">
        <div class="mts-grid">

            {{-- Card 1: Rapat Hari Ini --}}
            <div class="mts-card card-full-width">
                <div class="mts-accent-bar"></div>
                <div class="mts-card-body">
                    <div class="mts-card-head">
                        <div>
                            <div class="mts-num">{{ number_format($todayCount) }}</div>
                            <div class="mts-label">Rapat Hari Ini</div>
                        </div>
                        <div class="mts-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="17" height="17">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                    </div>
                    <div class="mts-sub">Rapat terjadwal hari ini</div>
                </div>
            </div>

            {{-- Card 2: Undangan Rapat --}}
            <div class="mts-card">
                <div class="mts-accent-bar"></div>
                <div class="mts-card-body">
                    <div class="mts-card-head">
                        <div>
                            <div class="mts-num">{{ number_format($invitedCount) }}</div>
                            <div class="mts-label">Undangan Rapat</div>
                        </div>
                        <div class="mts-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="17" height="17">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </div>
                    </div>
                    <div class="mts-sub">Rapat di mana Anda peserta</div>
                </div>
            </div>

            {{-- Card 3: Rapat Mendatang --}}
            <div class="mts-card">
                <div class="mts-accent-bar"></div>
                <div class="mts-card-body">
                    <div class="mts-card-head">
                        <div>
                            <div class="mts-num">{{ number_format($upcomingCount) }}</div>
                            <div class="mts-label">Rapat Mendatang</div>
                        </div>
                        <div class="mts-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="17" height="17">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mts-sub">Semua agenda yang akan datang</div>
                </div>
            </div>

        </div>
    </div>
    <style>
        /* ─── HIDE PERMANENTLY (ALL SCREENS) ───────────────────────── */
        .mts-wrap {
            display: none !important;
        }
        /* Sembunyikan juga kontainer luar bawaan Filament agar tidak ada gap kosong */
        .fi-wi-widget:has(.mts-wrap),
        div[class*="widget"]:has(.mts-wrap) {
            display: none !important;
        }

        /* ─── UNIFIED CARD SYSTEM ──────────────────────────────────── */
        .mts-wrap {
            width: 100%;
            box-sizing: border-box;
        }

        .mts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            width: 100%;
            box-sizing: border-box;
        }

        .card-full-width {
            grid-column: span 2;
        }

        @media (min-width: 1024px) {
            .mts-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .card-full-width {
                grid-column: span 1;
            }
        }

        .mts-card {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.07);
            border-radius: 1rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            min-width: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 16px rgba(0, 0, 0, 0.05);
            transition:
                transform 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.28s ease,
                border-color 0.28s ease;
        }

        .mts-card:hover {
            transform: translateY(-4px);
            border-color: rgba(37, 99, 235, 0.22);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.06), 0 8px 24px rgba(37, 99, 235, 0.12);
        }

        /* Teal accent bar — same across all cards */
        .mts-accent-bar {
            height: 3px;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            flex-shrink: 0;
        }

        .mts-card-body {
            padding: 1.1rem 1.25rem;
            flex: 1;
            min-width: 0;
        }

        .mts-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .mts-num {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.02em;
            color: #0f172a;
            transition: color 0.25s ease;
        }

        .mts-card:hover .mts-num {
            color: #2563eb;
        }

        .mts-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-top: 4px;
        }

        .mts-sub {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mts-icon {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .mts-card:hover .mts-icon {
            transform: scale(1.15) rotate(6deg);
        }

        /* ═══ MOBILE — DO NOT MODIFY ══════════════════════════════════ */
        @media (max-width: 768px) {
            .mts-card-body {
                padding: 12px;
            }

            .mts-num {
                font-size: 20px;
            }

            .mts-label {
                font-size: 9px;
                margin-top: 2px;
            }

            .mts-sub {
                font-size: 8.5px;
                margin-top: 4px;
            }

            .mts-icon {
                width: 24px;
                height: 24px;
                border-radius: 6px;
            }

            .mts-grid {
                gap: 8px;
            }
        }

        /* ═══════════════════════════════════════════════════════════════ */
    </style>
</x-filament-widgets::widget>