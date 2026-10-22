@php
    $record   = $getRecord();
    $name     = $record->name ?? '—';
    $prefix   = $record->code ?? null;
    $kelompok = \App\Models\DocumentCategory::KELOMPOK[$record->kelompok] ?? null;
    $isActive = isset($record->is_active) ? (bool) $record->is_active : null;
    $createdAt= $record->created_at?->translatedFormat('d M Y') ?? '—';
    $initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(mb_substr($w,0,1)))->take(2)->implode('');
    $palette  = ['#5B8DF6','#3DB88A','#E07AAC','#9B79D4','#E8885A','#3AAFC4','#6879C8','#D46E82'];
    $avatarBg = $palette[abs(crc32($name)) % count($palette)];
@endphp

<style>
    @media (min-width: 768px) {
        .dcms-mobile-card { display: none !important; }
        .dcms-desktop-title-view { display: flex !important; }
    }
    @media (max-width: 767.98px) {
        .dcms-desktop-title-view { display: none !important; }
        .dcms-mobile-card { display: block !important; }
    }
</style>

{{-- TAMPILAN DESKTOP --}}
<div class="dcms-desktop-title-view hidden md:flex flex-col gap-0.5 py-0.5">
    <span style="font-size:13px;font-weight:700;color:#0f172a;">{{ $name }}</span>
</div>

{{-- TAMPILAN MOBILE --}}
<div class="dcms-mobile-card md:hidden" style="width:100%;margin:3px 0;">
    <div style="width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-sizing:border-box;">
        <div style="padding:11px 14px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:8px;background:{{ $avatarBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-size:13px;font-weight:700;letter-spacing:0.5px;">{{ $initials }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $name }}</div>
                @if($prefix)
                    <div style="font-size:11px;color:#64748b;margin-top:1px;">Kode <span style="font-weight:600;color:#334155;">{{ $prefix }}</span>@if($kelompok) · {{ $kelompok }}@endif · {{ $record->company?->name ?? 'Semua perusahaan' }}</div>
                @endif
            </div>
            @if($isActive !== null)
                @if($isActive)
                    <span style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700;flex-shrink:0;letter-spacing:0.3px;">Aktif</span>
                @else
                    <span style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700;flex-shrink:0;letter-spacing:0.3px;">Nonaktif</span>
                @endif
            @endif
        </div>
        <div style="padding:7px 14px;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:5px;">
            <svg style="width:11px;height:11px;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span style="font-size:10.5px;color:#94a3b8;">Dibuat {{ $createdAt }}</span>
        </div>
    </div>
</div>
