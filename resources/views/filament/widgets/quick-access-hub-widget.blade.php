<div class="qah-widget">
    {{-- Header — teal gradient --}}
    <div class="qah-header">

        <div class="qah-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:20px;height:20px">
                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
            </svg>
        </div>
        <div class="qah-header-text">
            <div class="qah-header-title">Akses Cepat &amp; Pintasan</div>
            <div class="qah-header-desc">Pintasan aksi utama dan pemantauan status dokumen</div>
        </div>
    </div>

    <div class="qah-body">

        {{-- Quick Actions Grid --}}
        <div class="qah-actions-grid">

            <a href="/admin/documents/create" class="qah-action">
                <div class="qah-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <div class="qah-action-text">
                    <div class="qah-action-title">Buat SOP Baru</div>
                    <div class="qah-action-desc">Unggah dokumen baru</div>
                </div>
            </a>

            <a href="{{ route('filament.admin.pages.reminders') }}" class="qah-action">
                <div class="qah-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
                <div class="qah-action-text">
                    <div class="qah-action-title">Buat Reminder</div>
                    <div class="qah-action-desc">Ingatkan pegawai baca SOP</div>
                </div>
            </a>

            <a href="/admin/compliance-hub" class="qah-action">
                <div class="qah-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                </div>
                <div class="qah-action-text">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="qah-action-title">Compliance Hub</span>
                        @if($unreadComplianceCount > 0)
                        <span class="qah-badge-pill">{{ $unreadComplianceCount }}</span>
                        @endif
                    </div>
                    <div class="qah-action-desc">Dokumen wajib baca</div>
                </div>
            </a>

            <a href="/admin/bookmarks" class="qah-action">
                <div class="qah-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                    </svg>
                </div>
                <div class="qah-action-text">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="qah-action-title">Dokumen Tersimpan</span>
                        @if($bookmarkCount > 0)
                        <span class="qah-badge-pill">{{ $bookmarkCount }}</span>
                        @endif
                    </div>
                    <div class="qah-action-desc">Akses cepat favorit</div>
                </div>
            </a>
        </div>

        {{-- Recent Documents --}}
        <div class="qah-section">
            <div class="qah-section-head">
                <div class="qah-section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span>Dokumen Terakhir Anda</span>
                </div>
                <a href="/admin/documents" class="qah-link-all">Lihat Semua →</a>
            </div>

            <div class="qah-doc-list">
                @forelse($myRecentDocs as $doc)
                @php
                    $statusConfig = match($doc->status) {
                        'draft'             => ['label' => 'Draft',             'class' => 'qah-st--slate'],
                        'pending_kabid'     => ['label' => 'Review Kabid',      'class' => 'qah-st--amber'],
                        'pending_direktur'  => ['label' => 'Decision Direktur', 'class' => 'qah-st--sky'],
                        'approved'          => ['label' => 'Disetujui',         'class' => 'qah-st--emerald'],
                        'rejected'          => ['label' => 'Ditolak',           'class' => 'qah-st--rose'],
                        default             => ['label' => ucfirst($doc->status), 'class' => 'qah-st--slate'],
                    };
                @endphp
                <a href="/admin/documents/{{ $doc->id }}" class="qah-doc-item">
                    <div class="qah-doc-info">
                        <div class="qah-doc-title">{{ $doc->title }}</div>
                        <div class="qah-doc-meta">{{ $doc->code_number ?? 'SOP' }} · {{ $doc->updated_at->diffForHumans() }}</div>
                    </div>
                    <span class="qah-status {{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                </a>
                @empty
                <div class="qah-empty-msg">Belum ada dokumen yang Anda buat</div>
                @endforelse
            </div>
        </div>

        {{-- Expiry alert / health banner --}}
        @if($hasExpiryAlerts)
        <div class="qah-alert qah-alert--danger">
            <div class="qah-alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="qah-alert-body">
                <div class="qah-alert-title">Perhatian Masa Berlaku</div>
                <div class="qah-alert-sub">
                    {{ $expiredCount > 0 ? $expiredCount.' kedaluwarsa' : '' }}
                    {{ ($expiredCount > 0 && $expiringSoonCount > 0) ? ' · ' : '' }}
                    {{ $expiringSoonCount > 0 ? $expiringSoonCount.' mendekati kedaluwarsa' : '' }}
                </div>
            </div>
            <a href="/admin/documents" class="qah-alert-btn">Tinjau</a>
        </div>
        @else
        <div class="qah-alert qah-alert--ok">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#2563eb" style="width:14px;height:14px;flex-shrink:0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span class="qah-alert-ok-text">Semua dokumen aktif &amp; dalam kondisi valid</span>
        </div>
        @endif

    </div>
</div>

<style>
/* ─── WIDGET WRAPPER — unified card system ────────────────── */
.qah-widget {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid rgba(0, 0, 0, 0.07);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 8px;
    transition: box-shadow 0.28s ease, border-color 0.28s ease;
}

.qah-widget:hover {
    border-color: rgba(37,99,235,0.18);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.05), 0 8px 24px rgba(37,99,235,0.1);
}

/* ─── HEADER — teal gradient, floating shapes ────────────── */
.qah-header {
    padding: 1.1rem 1.4rem;
    background: linear-gradient(150deg, #0f172a 0%, #2563eb 60%, #1e3a8a 100%);
    border-bottom: 1px solid rgba(52,211,153,0.12);
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    overflow: hidden;
}

.qah-hgeo {
    position: absolute;
    pointer-events: none;
}

.qah-hgeo--circle {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(94,234,212,0.16) 0%, transparent 70%);
    top: -25px;
    right: 30px;
    animation: geo-float-a 6s ease-in-out infinite;
}

.qah-hgeo--ring {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid rgba(167,243,208,0.14);
    bottom: 6px;
    right: 110px;
    animation: geo-float-b 8s ease-in-out infinite;
    animation-delay: -3s;
}

/* Shared float keyframes — kept for header animation compat */
@keyframes geo-float-a {
    0%   { transform: translateY(0px); }
    50%  { transform: translateY(-14px); }
    100% { transform: translateY(0px); }
}

@keyframes geo-float-b {
    0%   { transform: translateY(0px) rotate(0deg); }
    50%  { transform: translateY(-10px) rotate(8deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

.qah-header-icon {
    width: 38px;
    height: 38px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(167,243,208,0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
    z-index: 2;
}

.qah-header-text { position: relative; z-index: 2; }

.qah-header-title {
    font-size: 14px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.01em;
}

.qah-header-desc {
    font-size: 11px;
    color: rgba(167,243,208,0.82);
    margin-top: 2px;
}

/* ─── BODY ────────────────────────────────────────────────── */
.qah-body {
    padding: 1.1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* ─── ACTIONS GRID ────────────────────────────────────────── */
.qah-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.6rem;
}

.qah-action {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0.7rem 0.9rem;
    border-radius: 0.75rem;
    text-decoration: none;
    background: #fafafa;
    border: 1px solid rgba(0,0,0,0.06);
    transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease;
}

.qah-action:hover {
    transform: translateY(-3px);
    background: #f0fdfa;
    border-color: rgba(37,99,235,0.2);
    box-shadow: 0 4px 14px -6px rgba(37,99,235,0.2);
}

.qah-action-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: rgba(37,99,235,0.1);
    color: #2563eb;
    transition: transform 0.28s cubic-bezier(0.34,1.56,0.64,1);
}

.qah-action:hover .qah-action-icon { transform: scale(1.12) rotate(6deg); }

.qah-action-text { min-width: 0; }
.qah-action-title { font-size: 12px; font-weight: 700; color: #0f172a; }
.qah-action-desc  { font-size: 10px; color: #64748b; margin-top: 1px; }

.qah-badge-pill {
    font-size: 9px;
    font-weight: 800;
    background: #2563eb;
    color: #fff;
    padding: 1px 6px;
    border-radius: 50px;
}

/* ─── SECTION ─────────────────────────────────────────────── */
.qah-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.qah-section-title {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 10.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.qah-link-all {
    font-size: 10.5px;
    font-weight: 700;
    color: #2563eb;
    text-decoration: none;
    transition: color 0.2s ease;
}

.qah-link-all:hover { color: #0f766e; }

/* ─── DOC LIST ────────────────────────────────────────────── */
.qah-doc-list { display: flex; flex-direction: column; gap: 5px; }

.qah-doc-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 7px 10px;
    background: #fafafa;
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: 0.6rem;
    text-decoration: none;
    transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

.qah-doc-item:hover {
    background: #f0fdfa;
    border-color: rgba(37,99,235,0.18);
    transform: translateX(3px);
}

.qah-doc-info { min-width: 0; flex: 1; }

.qah-doc-title {
    font-size: 11.5px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.qah-doc-meta { font-size: 9.5px; color: #94a3b8; margin-top: 1px; }

.qah-status {
    padding: 2px 8px;
    border-radius: 50px;
    font-size: 9.5px;
    font-weight: 700;
    flex-shrink: 0;
}

.qah-st--slate   { background: #f1f5f9; color: #475569; }
.qah-st--amber   { background: #fef3c7; color: #b45309; }
.qah-st--sky     { background: #dbeafe; color: #1d4ed8; }
.qah-st--emerald { background: #dcfce7; color: #15803d; }
.qah-st--rose    { background: #fee2e2; color: #b91c1c; }

.qah-empty-msg {
    font-size: 11px;
    color: #94a3b8;
    text-align: center;
    padding: 10px;
    background: #fafafa;
    border-radius: 0.6rem;
}

/* ─── ALERTS ──────────────────────────────────────────────── */
.qah-alert {
    padding: 10px 12px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    gap: 9px;
    border: 1px solid transparent;
}

.qah-alert--danger {
    background: #fff5f5;
    border-color: rgba(239,68,68,0.15);
}

.qah-alert--ok {
    background: #f0fdfa;
    border-color: rgba(37,99,235,0.15);
}

.qah-alert-icon {
    width: 28px;
    height: 28px;
    background: rgba(239,68,68,0.1);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
    flex-shrink: 0;
}

.qah-alert-body { flex: 1; min-width: 0; }
.qah-alert-title { font-size: 11.5px; font-weight: 700; color: #991b1b; }
.qah-alert-sub   { font-size: 10px; color: #b91c1c; margin-top: 1px; }

.qah-alert-btn {
    font-size: 10px;
    font-weight: 700;
    color: #ef4444;
    background: #fff;
    padding: 3px 10px;
    border-radius: 6px;
    text-decoration: none;
    border: 1px solid rgba(239,68,68,0.2);
    flex-shrink: 0;
    transition: background 0.2s ease;
}

.qah-alert-btn:hover { background: #fef2f2; }

.qah-alert-ok-text { font-size: 11px; font-weight: 600; color: #2563eb; }
</style>
