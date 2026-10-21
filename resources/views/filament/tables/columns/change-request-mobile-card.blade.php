@php
    $record      = $getRecord();
    $docTitle    = $record->document?->title ?? '—';
    $userName    = $record->user?->name ?? '—';
    $status      = $record->status ?? 'pending';
    $createdAt   = $record->created_at?->translatedFormat('d M Y') ?? '—';
    $createdAgo  = $record->created_at?->diffForHumans() ?? '';
    $proposed    = $record->proposed_change ?? null;
    $statusLabel = match($status) { 'pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak',default=>ucfirst($status) };
    $statusStyle = match($status) { 'pending'=>'background:#fefce8;color:#854d0e;border-color:#fde047;','approved'=>'background:#ecfdf5;color:#047857;border-color:#6ee7b7;','rejected'=>'background:#fff1f2;color:#be123c;border-color:#fca5a5;',default=>'background:#f8fafc;color:#475569;border-color:#cbd5e1;' };
    $borderColor = match($status) { 'pending'=>'#fde047','approved'=>'#6ee7b7','rejected'=>'#fca5a5',default=>'#e2e8f0' };
    $headerBg    = match($status) { 'pending'=>'linear-gradient(135deg,#fefce8 0%,#fef9c3 100%)','approved'=>'linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%)','rejected'=>'linear-gradient(135deg,#fff1f2 0%,#ffe4e6 100%)',default=>'linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%)' };
    $statusSvg   = match($status) {
        'pending'  => '<svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'approved' => '<svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'rejected' => '<svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        default    => '<svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    };
@endphp

<style>
    @media (min-width: 768px) {
        .dcms-mobile-card {
            display: none !important;
        }
        .dcms-desktop-title-view {
            display: flex !important;
        }
    }
    @media (max-width: 767.98px) {
        .dcms-desktop-title-view {
            display: none !important;
        }
        .dcms-mobile-card {
            display: block !important;
        }
    }
</style>

{{-- TAMPILAN DESKTOP (Sembunyi di mobile) --}}
<div class="dcms-desktop-title-view hidden md:flex flex-col gap-0.5 py-0.5">
    <span style="font-size:13px;font-weight:700;color:#0f172a;">
        {{ $docTitle }}
    </span>
</div>

{{-- TAMPILAN MOBILE (Sembunyi di desktop) --}}
<div class="dcms-mobile-card md:hidden" style="width:100%;margin:4px 0;">
    <div style="width:100%;background:#ffffff;border:1px solid {{ $borderColor }};border-radius:14px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06);box-sizing:border-box;">
        <div style="background:{{ $headerBg }};padding:10px 14px;display:flex;align-items:flex-start;gap:10px;border-bottom:1px solid {{ $borderColor }};">
            <div style="width:38px;height:38px;border-radius:10px;background:#1e40af;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:800;color:#0f172a;line-height:1.35;word-break:break-word;">{{ $docTitle }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:1px;">oleh {{ $userName }}</div>
            </div>
            <span style="border:1px solid;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;flex-shrink:0;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;{{ $statusStyle }}">
                {!! $statusSvg !!}
                <span>{{ $statusLabel }}</span>
            </span>
        </div>
        <div style="padding:8px 14px;">
            @if($proposed)
            <div style="margin-bottom:8px;">
                <div style="font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:3px;">Usulan Perubahan</div>
                <div style="font-size:11.5px;color:#1e293b;line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $proposed }}</div>
            </div>
            @endif
            <div style="display:flex;align-items:center;gap:4px;">
                <svg style="width:11px;height:11px;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span style="font-size:10.5px;color:#64748b;">{{ $createdAt }}{{ $createdAgo ? ' · '.$createdAgo : '' }}</span>
            </div>
        </div>
    </div>
</div>
