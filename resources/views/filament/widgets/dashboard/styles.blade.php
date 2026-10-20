<style>
    /* ═══ Beranda terpadu (widget Dashboard\*) ═══════════════════════════ */
    :root {
        --dsh-ink: #0f172a;
        --dsh-muted: #64748b;
        --dsh-faint: #94a3b8;
        --dsh-line: #e2e8f0;
        --dsh-soft: #f8fafc;
        --dsh-card: #ffffff;
        --dsh-meeting: #2563eb;
        --dsh-minutes: #7c3aed;
        --dsh-document: #059669;
        --dsh-numbering: #d97706;
        --dsh-room: #0891b2;
        --dsh-danger: #dc2626;
        --dsh-warning: #d97706;
        --dsh-radius: 16px;
    }

    .dsh-tone-meeting { --dsh-tone: var(--dsh-meeting); }
    .dsh-tone-minutes { --dsh-tone: var(--dsh-minutes); }
    .dsh-tone-document { --dsh-tone: var(--dsh-document); }
    .dsh-tone-numbering { --dsh-tone: var(--dsh-numbering); }
    .dsh-tone-room { --dsh-tone: var(--dsh-room); }

    .dsh-icon { width: 1.25rem; height: 1.25rem; }
    .dsh-text-minutes { color: var(--dsh-minutes); }
    .dsh-text-danger { color: var(--dsh-danger) !important; }
    .dsh-text-warning { color: var(--dsh-warning); }
    .dsh-mono { font-variant-numeric: tabular-nums; }

    /* ── Section header + quick actions ── */
    .dsh-section { display: grid; gap: 1rem; }
    .dsh-section-head {
        display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .75rem 1.5rem;
    }
    .dsh-section-title { font-size: 1.125rem; font-weight: 700; color: var(--dsh-ink); letter-spacing: -.01em; }
    .dsh-section-sub { font-size: .8125rem; color: var(--dsh-muted); margin-top: .125rem; }
    .dsh-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .dsh-btn {
        display: inline-flex; align-items: center; gap: .375rem;
        padding: .5rem .875rem; border-radius: 10px;
        font-size: .8125rem; font-weight: 600; color: #1e293b;
        background: var(--dsh-card); border: 1px solid var(--dsh-line);
        transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .dsh-btn:hover { border-color: #cbd5e1; background: var(--dsh-soft); }
    .dsh-btn:focus-visible, .dsh-module:focus-visible, .dsh-row:focus-visible, .dsh-agenda-item:focus-visible, .dsh-link:focus-visible {
        outline: 2px solid var(--dsh-meeting); outline-offset: 2px;
    }
    .dsh-btn-primary { background: var(--dsh-meeting); border-color: var(--dsh-meeting); color: #fff; }
    .dsh-btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
    .dsh-btn-icon { width: 1rem; height: 1rem; }

    /* ── Module cards ── */
    .dsh-modules { display: grid; gap: .75rem; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .dsh-module {
        position: relative; display: flex; flex-direction: column; gap: .125rem;
        padding: 1rem 1.125rem 1.125rem; border-radius: var(--dsh-radius);
        background: var(--dsh-card); border: 1px solid var(--dsh-line);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        transition: transform .15s, box-shadow .15s, border-color .15s;
        overflow: hidden;
    }
    .dsh-module::before {
        content: ""; position: absolute; inset: 0 0 auto 0; height: 3px; background: var(--dsh-tone);
    }
    .dsh-module:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15, 23, 42, .08); border-color: #cbd5e1; }
    .dsh-module-top { display: flex; align-items: center; gap: .5rem; margin-bottom: .625rem; }
    .dsh-module-icon, .dsh-card-icon {
        display: inline-grid; place-items: center; flex: none;
        width: 2rem; height: 2rem; border-radius: 10px;
        color: var(--dsh-tone); background: color-mix(in srgb, var(--dsh-tone) 10%, white);
    }
    .dsh-module-label { font-size: .8125rem; font-weight: 600; color: #334155; }
    .dsh-module-value {
        display: flex; align-items: center; gap: .5rem;
        font-size: 1.875rem; line-height: 1.1; font-weight: 700; color: var(--dsh-ink);
        font-variant-numeric: tabular-nums; letter-spacing: -.02em;
    }
    .dsh-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--dsh-danger); box-shadow: 0 0 0 3px rgba(220, 38, 38, .15); }
    .dsh-module-unit { font-size: .8125rem; color: var(--dsh-muted); }
    .dsh-module-detail {
        margin-top: .625rem; padding-top: .625rem; border-top: 1px dashed var(--dsh-line);
        font-size: .75rem; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ── Layout grids ── */
    .dsh-grid { display: grid; gap: 1rem; align-items: start; }
    .dsh-grid-2-1 { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); }
    .dsh-grid-1-1 { grid-template-columns: repeat(2, minmax(0, 1fr)); }

    /* ── Cards ── */
    .dsh-card {
        display: flex; flex-direction: column; gap: .75rem;
        padding: 1.125rem 1.25rem 1.25rem; border-radius: var(--dsh-radius);
        background: var(--dsh-card); border: 1px solid var(--dsh-line);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        min-width: 0; scroll-margin-top: 5rem;
    }
    .dsh-card-head { display: flex; align-items: center; gap: .75rem; }
    .dsh-card-heading { flex: 1; min-width: 0; }
    .dsh-card-title { font-size: .9375rem; font-weight: 700; color: var(--dsh-ink); }
    .dsh-card-sub { font-size: .75rem; color: var(--dsh-muted); }
    .dsh-link { font-size: .75rem; font-weight: 600; color: var(--dsh-meeting); white-space: nowrap; border-radius: 4px; }
    .dsh-link:hover { text-decoration: underline; }
    .dsh-count {
        min-width: 1.75rem; padding: .125rem .5rem; border-radius: 999px; text-align: center;
        font-size: .75rem; font-weight: 700; background: #f1f5f9; color: #334155;
    }
    .dsh-count-alert { background: #fef2f2; color: var(--dsh-danger); }
    .dsh-subhead {
        margin-top: .25rem; font-size: .6875rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: var(--dsh-faint);
    }

    /* ── Lists & rows ── */
    .dsh-list { display: grid; grid-template-columns: minmax(0, 1fr); gap: .25rem; }
    .dsh-list > li { min-width: 0; }
    .dsh-row, .dsh-agenda-item {
        display: flex; align-items: center; gap: .75rem;
        padding: .625rem .75rem; border-radius: 10px; transition: background .15s;
    }
    .dsh-row:hover, .dsh-agenda-item:hover { background: var(--dsh-soft); }
    .dsh-row-compact { padding: .5rem .75rem; }
    .dsh-row-body, .dsh-agenda-body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .dsh-row-side { display: flex; align-items: center; gap: .5rem; flex: none; }
    .dsh-row-lead { width: 1.125rem; height: 1.125rem; flex: none; color: var(--dsh-faint); }
    .dsh-row-age { font-size: .75rem; color: var(--dsh-muted); white-space: nowrap; flex: none; }
    .dsh-item-title {
        font-size: .8125rem; font-weight: 600; color: #1e293b;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dsh-item-meta {
        display: flex; align-items: center; gap: .25rem; min-width: 0;
        font-size: .75rem; color: var(--dsh-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dsh-row .dsh-item-meta { display: block; }
    .dsh-meta-icon { width: .875rem; height: .875rem; flex: none; color: var(--dsh-faint); }
    .dsh-sep { color: #cbd5e1; padding: 0 .125rem; }

    /* ── Chips ── */
    .dsh-chip {
        flex: none; padding: .1875rem .5rem; border-radius: 999px;
        font-size: .6875rem; font-weight: 600; white-space: nowrap;
        background: #f1f5f9; color: #475569;
    }
    .dsh-chip-ongoing { background: #dcfce7; color: #15803d; }
    .dsh-chip-scheduled { background: #eff6ff; color: #1d4ed8; }
    .dsh-chip-done { background: #f1f5f9; color: #64748b; }
    .dsh-chip-busy { background: #fef2f2; color: #b91c1c; }
    .dsh-chip-free { background: #ecfdf5; color: #047857; }
    .dsh-chip-minutes { background: #f5f3ff; color: #6d28d9; }
    .dsh-doc-draft { background: #f1f5f9; color: #475569; }
    .dsh-doc-rejected { background: #fef2f2; color: #b91c1c; }
    .dsh-doc-pending_kabid { background: #fffbeb; color: #b45309; }
    .dsh-doc-pending_direktur { background: #eff6ff; color: #1d4ed8; }

    /* ── Agenda ── */
    .dsh-day { display: grid; gap: .25rem; }
    .dsh-day + .dsh-day { margin-top: .25rem; }
    .dsh-day-label { font-size: .75rem; font-weight: 700; color: #334155; padding: 0 .75rem; }
    .dsh-agenda-time {
        flex: none; width: 6.5rem; padding-left: .625rem; border-left: 3px solid var(--dsh-meeting);
        font-size: .8125rem; font-weight: 600; color: #334155; font-variant-numeric: tabular-nums;
    }
    .dsh-state-ongoing .dsh-agenda-time { border-color: #16a34a; }
    .dsh-state-done { opacity: .6; }
    .dsh-state-done .dsh-agenda-time { border-color: #cbd5e1; }

    /* ── Rooms ── */
    .dsh-room { display: grid; gap: .375rem; padding: .625rem 0; border-top: 1px solid #f1f5f9; }
    .dsh-room:first-of-type { border-top: 0; padding-top: 0; }
    .dsh-room-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .dsh-room-status { font-size: .75rem; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dsh-timeline {
        position: relative; height: .625rem; border-radius: 999px; background: #f1f5f9; overflow: hidden;
    }
    .dsh-block { position: absolute; top: 0; bottom: 0; border-radius: 999px; background: var(--dsh-room); }
    .dsh-block-past { background: #a5d8e3; }
    .dsh-now { position: absolute; top: -2px; bottom: -2px; width: 2px; margin-left: -1px; background: var(--dsh-danger); }
    .dsh-hours { position: relative; height: .875rem; font-size: .625rem; color: var(--dsh-faint); }
    .dsh-hours span { position: absolute; transform: translateX(-50%); }
    .dsh-hours span:first-child { transform: none; }
    .dsh-hours span:last-child { transform: translateX(-100%); }

    /* Badge jenis surat (warna dari JenisSurat::warna()) — dipakai di tabel Nomor Surat & Dokumen */
    .dcms-jenis-badge {
        display: inline-block; padding: .125rem .5rem; border-radius: 6px; white-space: nowrap;
        font-size: .75rem; font-weight: 600; background: var(--jb-bg); color: var(--jb-text);
    }
    html.dark .dcms-jenis-badge { background: color-mix(in srgb, var(--jb-text) 60%, transparent); color: var(--jb-bg); }

    /* ── Numbering ── */
    .dsh-sequences { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; font-size: .75rem; color: var(--dsh-muted); }
    .dsh-sequences-label { margin-right: .125rem; }
    .dsh-sequence {
        padding: .1875rem .5rem; border-radius: 999px; background: var(--dsh-soft); border: 1px solid var(--dsh-line);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .6875rem; color: var(--dsh-muted);
    }
    .dsh-sequence strong { color: var(--dsh-ink); }
    .dsh-counters { display: grid; gap: .625rem; }
    .dsh-counter { display: flex; align-items: center; gap: .75rem; }
    .dsh-prefix {
        flex: none; min-width: 2.75rem; padding: .25rem .375rem; border-radius: 8px; text-align: center;
        font-size: .6875rem; font-weight: 700; letter-spacing: .03em;
        color: var(--dsh-cat); background: color-mix(in srgb, var(--dsh-cat) 10%, white);
    }
    .dsh-counter-body { flex: 1; min-width: 0; display: grid; gap: .25rem; }
    .dsh-counter-name { font-size: .8125rem; font-weight: 600; color: #1e293b; }
    .dsh-counter-value { flex: none; font-size: 1rem; font-weight: 700; color: var(--dsh-ink); font-variant-numeric: tabular-nums; }
    .dsh-bar { display: block; height: .375rem; border-radius: 999px; background: #f1f5f9; overflow: hidden; }
    .dsh-bar span { display: block; height: 100%; border-radius: 999px; }
    .dsh-number {
        flex: none; max-width: 45%; padding: .1875rem .5rem; border-radius: 6px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .6875rem; font-weight: 600;
        color: #92400e; background: #fffbeb; border: 1px solid #fde68a;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ── Document stats ── */
    .dsh-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .dsh-kpi { display: flex; flex-direction: column; padding: .625rem .75rem; border-radius: 12px; background: var(--dsh-soft); }
    .dsh-kpi-value { font-size: 1.25rem; font-weight: 700; color: var(--dsh-ink); font-variant-numeric: tabular-nums; }
    .dsh-kpi-label { font-size: .6875rem; color: var(--dsh-muted); }
    .dsh-stack { display: flex; gap: 2px; height: .625rem; border-radius: 999px; overflow: hidden; background: #f1f5f9; }
    .dsh-stack span { display: block; height: 100%; }
    .dsh-legend { display: flex; flex-wrap: wrap; gap: .375rem 1rem; font-size: .75rem; color: #475569; }
    .dsh-legend li { display: inline-flex; align-items: center; gap: .375rem; }
    .dsh-legend strong { color: var(--dsh-ink); font-variant-numeric: tabular-nums; }
    .dsh-swatch { width: .625rem; height: .625rem; border-radius: 3px; }
    .dsh-trend { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .5rem; height: 8.5rem; }
    .dsh-trend-col { display: flex; flex-direction: column; align-items: center; gap: .25rem; min-width: 0; }
    .dsh-trend-value { font-size: .6875rem; font-weight: 600; color: #475569; font-variant-numeric: tabular-nums; }
    .dsh-trend-track { flex: 1; width: 100%; max-width: 2.5rem; display: flex; align-items: flex-end; }
    .dsh-trend-bar { display: block; width: 100%; border-radius: 6px 6px 2px 2px; background: var(--dsh-document); }
    .dsh-trend-col:last-child .dsh-trend-bar { background: #047857; }
    .dsh-trend-label { font-size: .6875rem; color: var(--dsh-muted); }

    /* ── Empty states ── */
    .dsh-empty {
        display: flex; flex-direction: column; align-items: center; gap: .375rem;
        padding: 1.5rem 1rem; border-radius: 12px; background: var(--dsh-soft);
        font-size: .8125rem; color: var(--dsh-muted); text-align: center;
    }
    .dsh-empty-icon { width: 1.75rem; height: 1.75rem; color: #cbd5e1; }
    .dsh-empty-ok .dsh-empty-icon { color: #34d399; }

    /* ── Responsive ── */
    @media (max-width: 1279px) {
        .dsh-modules { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 1023px) {
        .dsh-grid-2-1, .dsh-grid-1-1 { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 767px) {
        .dsh-modules { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dsh-module { padding: .875rem; }
        .dsh-module:last-child:nth-child(odd) { grid-column: span 2; }
        .dsh-row, .dsh-agenda-item { padding-left: .5rem; padding-right: .5rem; gap: .5rem; }
        .dsh-number { max-width: 40%; }
        .dsh-module-value { font-size: 1.5rem; }
        .dsh-card { padding: 1rem; }
        .dsh-agenda-item { flex-wrap: wrap; }
        .dsh-agenda-time { width: auto; }
        .dsh-agenda-body { flex-basis: 100%; order: 3; }
        .dsh-section-head { align-items: flex-start; }
    }
    @media (prefers-reduced-motion: reduce) {
        .dsh-module, .dsh-btn, .dsh-row, .dsh-agenda-item { transition: none; }
        .dsh-module:hover { transform: none; }
    }
</style>
