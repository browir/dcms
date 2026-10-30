{{-- Style bersama komponen halaman Detail Rapat. Warna memakai token --ui-* dari
     theme-modern/theme-dark sehingga otomatis ikut mode gelap. Dimuat sekali per request. --}}
@once
<style>
    .mvd-empty {
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem;
        padding: 1.75rem 1rem; text-align: center; border: 1px dashed var(--ui-line, #e6eaf0);
        border-radius: var(--ui-radius, 12px); background: var(--ui-surface-2, #f8fafc);
        color: var(--ui-muted, #64748b); font-size: .85rem; line-height: 1.5;
    }
    .mvd-empty svg { color: var(--ui-faint, #94a3b8); }
    .mvd-empty strong { color: var(--ui-ink-2, #334155); font-weight: 600; }

    .mvd-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
        padding: .55rem .9rem; border-radius: var(--ui-radius-sm, 8px); font-size: .82rem; font-weight: 600;
        text-decoration: none; border: 1px solid transparent; transition: background-color .15s, border-color .15s;
    }
    .mvd-btn-primary { background: var(--ui-primary, #2563eb); color: #fff; }
    .mvd-btn-primary:hover { background: #1d4ed8; }
    .mvd-btn-ghost { background: var(--ui-surface, #fff); color: var(--ui-ink-2, #334155); border-color: var(--ui-line, #e6eaf0); }
    .mvd-btn-ghost:hover { background: var(--ui-hover, #f5f8ff); border-color: var(--ui-primary, #2563eb); }
    .mvd-btn:focus-visible { outline: 2px solid var(--ui-primary, #2563eb); outline-offset: 2px; }

    .mvd-avatar {
        width: 2rem; height: 2rem; min-width: 2rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; color: #fff; letter-spacing: .02em;
    }
    .mvd-avatar-sm { width: 1.5rem; height: 1.5rem; min-width: 1.5rem; font-size: .6rem; }

    .mvd-tag {
        display: inline-flex; align-items: center; gap: .25rem; padding: .1rem .45rem; border-radius: 999px;
        font-size: .65rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; white-space: nowrap;
    }
    .mvd-tag-blue { background: #eff6ff; color: #1d4ed8; }
    .mvd-tag-violet { background: #f5f3ff; color: #6d28d9; }
    .mvd-tag-green { background: #ecfdf5; color: #047857; }
    .mvd-tag-red { background: #fef2f2; color: #b91c1c; }
    .mvd-tag-amber { background: #fffbeb; color: #b45309; }
    .dark .mvd-tag-blue { background: rgba(59,130,246,.15); color: #93c5fd; }
    .dark .mvd-tag-violet { background: rgba(139,92,246,.15); color: #c4b5fd; }
    .dark .mvd-tag-green { background: rgba(16,185,129,.15); color: #6ee7b7; }
    .dark .mvd-tag-red { background: rgba(239,68,68,.15); color: #fca5a5; }
    .dark .mvd-tag-amber { background: rgba(245,158,11,.15); color: #fcd34d; }

    .mvd-muted { color: var(--ui-muted, #64748b); }
    .mvd-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--ui-faint, #94a3b8); }
</style>
@endonce
