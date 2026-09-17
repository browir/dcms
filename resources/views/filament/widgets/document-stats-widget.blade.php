<x-filament-widgets::widget>
<div class="dms-wrap">
    <div class="dms-grid">

        {{-- Card 1: Total Dokumen --}}
        <div class="dms-card">
            <div class="dms-accent-bar"></div>
            <div class="dms-card-body">
                <div class="dms-card-head">
                    <div>
                        <div class="dms-num">{{ number_format($total) }}</div>
                        <div class="dms-label">Total Dokumen</div>
                    </div>
                    <div class="dms-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Disetujui --}}
        <div class="dms-card">
            <div class="dms-accent-bar"></div>
            <div class="dms-card-body">
                <div class="dms-card-head">
                    <div>
                        <div class="dms-num">{{ number_format($approved) }}</div>
                        <div class="dms-label">Disetujui</div>
                    </div>
                    <div class="dms-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="dms-sub">{{ $approved }} dokumen telah disetujui</div>
            </div>
        </div>

        {{-- Card 3: Menunggu Review --}}
        <div class="dms-card">
            <div class="dms-accent-bar"></div>
            <div class="dms-card-body">
                <div class="dms-card-head">
                    <div>
                        <div class="dms-num">{{ number_format($pendingTotal) }}</div>
                        <div class="dms-label">Menunggu Review</div>
                    </div>
                    <div class="dms-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="dms-sub">{{ $pendingKabid }} Kabid · {{ $pendingDir }} Direktur</div>
            </div>
        </div>

        {{-- Card 4: Draft --}}
        <div class="dms-card">
            <div class="dms-accent-bar"></div>
            <div class="dms-card-body">
                <div class="dms-card-head">
                    <div>
                        <div class="dms-num">{{ number_format($draft) }}</div>
                        <div class="dms-label">Draft</div>
                    </div>
                    <div class="dms-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                        </svg>
                    </div>
                </div>
                <div class="dms-sub">{{ $rejected }} dokumen ditolak</div>
            </div>
        </div>

    </div>
</div>
<style>
/* ─── UNIFIED CARD SYSTEM ──────────────────────────────────── */
.dms-wrap { width: 100%; box-sizing: border-box; }

.dms-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    width: 100%;
    box-sizing: border-box;
}

@media (min-width: 1024px) {
    .dms-grid { grid-template-columns: repeat(4, 1fr); }
}

.dms-card {
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.07);
    border-radius: 1rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    min-width: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
    transition:
        transform 0.28s cubic-bezier(0.16, 1, 0.3, 1),
        box-shadow 0.28s ease,
        border-color 0.28s ease;
}

.dms-card:hover {
    transform: translateY(-4px);
    border-color: rgba(13, 148, 136, 0.22);
    box-shadow: 0 0 0 3px rgba(13,148,136,0.06), 0 8px 24px rgba(13,148,136,0.12);
}

/* Single teal accent bar */
.dms-accent-bar {
    height: 3px;
    background: linear-gradient(90deg, #0d9488, #2dd4bf);
    flex-shrink: 0;
}

.dms-card-body {
    padding: 1.1rem 1.25rem;
    flex: 1;
    min-width: 0;
}

.dms-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}

.dms-num {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.02em;
    color: #0f172a;
    transition: color 0.25s ease;
}

.dms-card:hover .dms-num { color: #0d9488; }

.dms-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-top: 4px;
}

.dms-sub {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dms-icon {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 148, 136, 0.1);
    color: #0d9488;
    transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.dms-icon svg { width: 17px; height: 17px; }

.dms-card:hover .dms-icon { transform: scale(1.15) rotate(6deg); }

/* ═══ MOBILE — DO NOT MODIFY ══════════════════════════════════ */
@media (max-width: 768px) {
    .dms-card-body { padding: 12px; }
    .dms-num { font-size: 20px; }
    .dms-label { font-size: 9px; margin-top: 2px; }
    .dms-sub { font-size: 8.5px; margin-top: 4px; }
    .dms-icon { width: 24px; height: 24px; border-radius: 6px; }
    .dms-icon svg { width: 14px; height: 14px; }
    .dms-grid { gap: 8px; }
}
/* ═══════════════════════════════════════════════════════════════ */
</style>
</x-filament-widgets::widget>
