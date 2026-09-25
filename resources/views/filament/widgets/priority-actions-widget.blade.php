<x-filament-widgets::widget>
    <div class="pa-shell">
        <div class="pa-grid">
            {{-- Prioritas Hari Ini --}}
            <section class="pa-panel pa-animate" style="--pa-delay: 60ms;">
                <div class="pa-panel-head">
                    <div>
                        <div class="pa-panel-title">Prioritas Hari Ini</div>
                        <div class="pa-panel-desc">Pekerjaan yang memerlukan tindakan segera dari Anda.</div>
                    </div>
                    @if(count($documentsNeedAction))
                    <span class="pa-count-chip">{{ count($documentsNeedAction) }}</span>
                    @endif
                </div>

                @if (count($documentsNeedAction))
                <div class="pa-list">
                    @foreach ($documentsNeedAction as $index => $document)
                    <a
                        href="{{ $document['url'] }}"
                        wire:navigate
                        class="pa-item"
                        style="--pa-delay: {{ 80 + $index * 50 }}ms;">
                        <div class="pa-item-header">
                            <div class="pa-item-title">{{ $document['title'] }}</div>
                            <span class="pa-badge pa-badge--{{ $document['badgeColor'] }}">{{ $document['badge'] }}</span>
                        </div>
                        <div class="pa-item-meta">
                            <div><span class="pa-item-code">{{ $document['code'] }}</span></div>
                            <div class="pa-meta-bottom">
                                <span>{{ $document['meta'] }}</span>
                                <span class="pa-meta-time"><span class="pa-meta-dot">&bull;</span> {{ $document['updatedAt'] }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="pa-empty">
                    <div class="pa-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="26" height="26">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <div class="pa-empty-title">Tidak ada tindakan mendesak.</div>
                    <div class="pa-empty-text">Semua dokumen Anda saat ini berada pada jalur yang aman.</div>
                </div>
                @endif
            </section>

            {{-- Aksi Cepat --}}
            <section class="pa-panel pa-animate" style="--pa-delay: 120ms;">
                <div class="pa-panel-head">
                    <div>
                        <div class="pa-panel-title">Aksi Cepat</div>
                        <div class="pa-panel-desc">Akses tugas &amp; pengingat dokumen Anda langsung dari sini.</div>
                    </div>
                </div>

                <div class="pa-action-list">
                    @foreach ($quickActions as $index => $action)
                    <a
                        href="{{ $action['url'] }}"
                        wire:navigate
                        class="pa-action pa-action--{{ $action['accent'] }}"
                        style="--pa-delay: {{ 140 + $index * 55 }}ms;">
                        <span class="pa-action-icon">
                            <x-filament::icon :icon="$action['icon']" class="pa-icon" />
                        </span>
                        <span class="pa-action-text">
                            <span class="pa-action-title">{{ $action['label'] }}</span>
                            <span class="pa-action-desc">{{ $action['description'] }}</span>
                        </span>
                        <span class="pa-action-arrow" aria-hidden="true">→</span>
                    </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <style>
        .pa-shell { display: grid; gap: 0; }

        .pa-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            align-items: start;
        }

        /* ═══ MOBILE — DO NOT MODIFY ══════════════════════════════ */
        @media (max-width: 900px) {
            .pa-grid { grid-template-columns: 1fr; }
        }
        /* ══════════════════════════════════════════════════════════ */

        .pa-animate {
            opacity: 0;
            transform: translateY(10px);
            animation: pa-rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: var(--pa-delay, 0ms);
        }

        @keyframes pa-rise { to { opacity: 1; transform: translateY(0); } }

        /* ─── PANEL — unified card system ────────────────────────── */
        .pa-panel {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.07);
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
            transition: transform 0.28s cubic-bezier(0.16,1,0.3,1), box-shadow 0.28s ease, border-color 0.28s ease;
        }

        .pa-panel:hover {
            transform: translateY(-3px);
            border-color: rgba(37,99,235,0.18);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.05), 0 8px 24px rgba(37,99,235,0.1);
        }

        .pa-panel-head {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .pa-panel-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        .pa-panel-desc {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.18rem;
            line-height: 1.4;
        }

        .pa-count-chip {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            background: #2563eb;
            color: #fff;
            padding: 0 7px;
        }

        /* ─── LIST ITEMS ─────────────────────────────────────────── */
        .pa-list { display: flex; flex-direction: column; gap: 0.4rem; }

        .pa-item {
            display: flex;
            flex-direction: column;
            gap: 0.38rem;
            padding: 0.65rem 0.8rem;
            border-radius: 0.65rem;
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(0,0,0,0.06);
            background: #fafafa;
            transition: all 0.22s cubic-bezier(0.16,1,0.3,1);
        }

        .pa-item:hover {
            background: #f0fdfa;
            border-color: rgba(37,99,235,0.2);
            transform: translateX(3px);
        }

        .pa-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
            width: 100%;
        }

        .pa-item-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            flex: 1;
        }

        .pa-item-meta { display: flex; flex-direction: column; gap: 0.28rem; }

        .pa-item-code {
            background: rgba(37,99,235,0.1);
            color: #2563eb;
            border-radius: 4px;
            padding: 1px 6px;
            font-weight: 700;
            font-size: 0.65rem;
            border: 1px solid rgba(37,99,235,0.15);
            display: inline-block;
        }

        .pa-meta-bottom {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            column-gap: 0.35rem;
            row-gap: 0.15rem;
            font-size: 0.65rem;
            color: #64748b;
        }

        .pa-meta-time { white-space: nowrap; }
        .pa-meta-dot  { color: #cbd5e1; font-size: 0.6rem; margin-right: 0.1rem; }

        /* ─── BADGES (status only — not full backgrounds) ────────── */
        .pa-badge {
            flex-shrink: 0;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            font-size: 0.6rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .pa-badge--amber   { background: #fef3c7; color: #b45309; }
        .pa-badge--rose    { background: #ffe4e6; color: #e11d48; }
        .pa-badge--sky     { background: #e0f2fe; color: #0369a1; }
        .pa-badge--slate   { background: #f1f5f9; color: #475569; }
        .pa-badge--gray    { background: #f1f5f9; color: #64748b; }
        .pa-badge--success { background: #d1fae5; color: #047857; }

        /* ─── EMPTY STATE ────────────────────────────────────────── */
        .pa-empty {
            padding: 1.75rem 1rem;
            text-align: center;
            color: #94a3b8;
        }

        .pa-empty-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 0.55rem;
            color: #2563eb;
            opacity: 0.6;
        }

        .pa-empty-title { font-weight: 700; color: #475569; font-size: 0.875rem; }
        .pa-empty-text  { font-size: 0.78rem; margin-top: 0.25rem; color: #94a3b8; }

        /* ─── QUICK ACTIONS ──────────────────────────────────────── */
        .pa-action-list { display: grid; gap: 0.6rem; }

        .pa-action {
            display: flex;
            gap: 0.85rem;
            align-items: center;
            padding: 0.9rem 1rem;
            border-radius: 0.85rem;
            text-decoration: none;
            color: inherit;
            background: #fafafa;
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease;
        }

        .pa-action:hover {
            transform: translateY(-2px);
            background: #f0fdfa;
            border-color: rgba(37,99,235,0.2);
            box-shadow: 0 4px 16px -6px rgba(37,99,235,0.2);
        }

        .pa-action-icon {
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 0.7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(37,99,235,0.1);
            color: #2563eb;
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }

        .pa-action:hover .pa-action-icon { transform: scale(1.12) rotate(6deg); }

        .pa-icon { width: 1.05rem; height: 1.05rem; }

        .pa-action-arrow {
            margin-left: auto;
            font-size: 1rem;
            color: #94a3b8;
            opacity: 0;
            transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }

        .pa-action:hover .pa-action-arrow { opacity: 1; transform: translateX(4px); }

        .pa-action-text { display: grid; gap: 0.12rem; }
        .pa-action-title { font-size: 0.875rem; font-weight: 700; color: #1e293b; }
        .pa-action-desc  { font-size: 0.78rem; color: #64748b; line-height: 1.35; }
    </style>
</x-filament-widgets::widget>