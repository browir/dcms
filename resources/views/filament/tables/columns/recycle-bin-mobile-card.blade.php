@php
    $record      = $getRecord();
    $title       = $record->title ?? '—';
    $codeNumber  = $record->code_number ?? null;
    $department  = $record->department?->name ?? null;
    $deletedAt   = $record->deleted_at?->translatedFormat('d M Y, H:i') ?? '—';
    $deletedAgo  = $record->deleted_at?->diffForHumans() ?? '';
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
        {{ $title }}
    </span>
</div>

{{-- TAMPILAN MOBILE (Sembunyi di desktop) --}}
<div class="dcms-mobile-card md:hidden" style="width:100%;margin:4px 0;">
    <div style="width:100%;background:#ffffff;border:1px solid #fca5a5;border-radius:14px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06);box-sizing:border-box;">
        <div style="background:linear-gradient(135deg,#fff1f2 0%,#ffe4e6 100%);padding:10px 14px;display:flex;align-items:flex-start;gap:10px;border-bottom:1px solid #fca5a5;">
            <div style="width:38px;height:38px;border-radius:10px;background:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:800;color:#0f172a;line-height:1.35;word-break:break-word;">{{ $title }}</div>
                @if($codeNumber)<div style="margin-top:3px;"><span style="font-size:10px;font-weight:700;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;border-radius:4px;padding:1px 6px;"># {{ $codeNumber }}</span></div>@endif
            </div>
            <span style="background:#fff1f2;color:#be123c;border:1px solid #fca5a5;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;flex-shrink:0;white-space:nowrap;">Dihapus</span>
        </div>
        <div style="padding:8px 14px;display:grid;grid-template-columns:1fr 1fr;gap:6px;border-bottom:1px solid #fca5a5;">
            @if($department)<div><div style="font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:2px;">Departemen</div><div style="font-size:11.5px;font-weight:600;color:#1e293b;">{{ $department }}</div></div>@endif
            <div>
                <div style="font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:2px;">Dihapus Pada</div>
                <div style="font-size:11px;font-weight:600;color:#be123c;">{{ $deletedAt }}</div>
                @if($deletedAgo)<div style="font-size:10px;color:#94a3b8;">{{ $deletedAgo }}</div>@endif
            </div>
        </div>
        <div style="background:#fff5f5;padding:10px 14px;display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" wire:click="mountTableAction('restore', '{{ $record->getKey() }}')" style="background:#10b981;color:#fff;border:none;border-radius:6px;padding:6px 12px;font-size:11px;font-weight:700;display:flex;align-items:center;gap:4px;cursor:pointer;">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                Pulihkan
            </button>
            <button type="button" wire:click="mountTableAction('forceDelete', '{{ $record->getKey() }}')" style="background:#ef4444;color:#fff;border:none;border-radius:6px;padding:6px 12px;font-size:11px;font-weight:700;display:flex;align-items:center;gap:4px;cursor:pointer;">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                Hapus Permanen
            </button>
        </div>
    </div>
</div>
