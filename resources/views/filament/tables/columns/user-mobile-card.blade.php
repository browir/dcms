@php
    $record    = $getRecord();
    $name      = $record->name ?? '—';
    $email     = $record->email ?? '—';
    $company   = $record->company?->name ?? null;
    $department= $record->department?->name ?? null;
    $unit      = $record->unit?->name ?? null;
    $isActive  = $record->is_active ?? false;
    $createdAt = $record->created_at?->translatedFormat('d M Y') ?? '—';
    $roles     = $record->roles->pluck('name');
    $avatarInitials = collect(explode(' ', $name))->map(fn($w) => strtoupper(mb_substr($w,0,1)))->take(2)->implode('');
    $avatarPalette = ['#5B8DF6','#3DB88A','#E07AAC','#9B79D4','#E8885A','#3AAFC4','#6879C8','#D46E82'];
    $avatarBg = $avatarPalette[abs(crc32($name)) % count($avatarPalette)];
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

        {{-- Header: Avatar + Nama + Status --}}
        <div style="padding:11px 14px;display:flex;align-items:center;gap:12px;">
            <div style="width:38px;height:38px;border-radius:8px;background:{{ $avatarBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-size:13px;font-weight:700;letter-spacing:0.5px;">{{ $avatarInitials }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $name }}</div>
                <div style="font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;">{{ $email }}</div>
            </div>
            @if($isActive)
                <span style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700;flex-shrink:0;letter-spacing:0.3px;">Aktif</span>
            @else
                <span style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700;flex-shrink:0;letter-spacing:0.3px;">Nonaktif</span>
            @endif
        </div>

        {{-- Roles --}}
        @if($roles->isNotEmpty())
        <div style="padding:7px 14px;border-top:1px solid #f1f5f9;display:flex;flex-wrap:wrap;gap:5px;">
            @foreach($roles as $role)
                <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:4px;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;letter-spacing:0.2px;">{{ ucwords(str_replace('_',' ',$role)) }}</span>
            @endforeach
        </div>
        @endif

        {{-- Meta info --}}
        <div style="padding:8px 14px;border-top:1px solid #f1f5f9;display:grid;grid-template-columns:1fr 1fr;gap:6px 10px;">
            @if($company)
            <div>
                <div style="font-size:9.5px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:2px;">Perusahaan</div>
                <div style="font-size:11.5px;font-weight:600;color:#1e293b;">{{ $company }}</div>
            </div>
            @endif
            @if($department)
            <div>
                <div style="font-size:9.5px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:2px;">Departemen</div>
                <div style="font-size:11.5px;font-weight:600;color:#1e293b;">{{ $department }}</div>
            </div>
            @endif
            @if($unit)
            <div>
                <div style="font-size:9.5px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:2px;">Unit</div>
                <div style="font-size:11.5px;font-weight:600;color:#1e293b;">{{ $unit }}</div>
            </div>
            @endif
            <div>
                <div style="font-size:9.5px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:2px;">Bergabung</div>
                <div style="font-size:11.5px;font-weight:600;color:#1e293b;">{{ $createdAt }}</div>
            </div>
        </div>

    </div>
</div>
