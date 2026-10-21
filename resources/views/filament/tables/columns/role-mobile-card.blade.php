@php
    $record        = $getRecord();
    $name          = $record->name ?? '—';
    $guard         = $record->guard_name ?? 'web';
    $permsCount    = $record->permissions_count ?? $record->permissions()->count();
    $updatedAt     = $record->updated_at?->translatedFormat('d M Y') ?? '—';
    $formattedName = \Illuminate\Support\Str::headline($name);
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
        {{ $formattedName }}
    </span>
</div>

{{-- TAMPILAN MOBILE (Sembunyi di desktop) --}}
<div class="dcms-mobile-card md:hidden" style="width:100%;margin:4px 0;">
    <div style="width:100%;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);box-sizing:border-box;">
        
        {{-- CARD BODY --}}
        <div style="padding:12px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            
            {{-- LEFT: ROLE TITLE & GUARD --}}
            <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                <div style="width:36px;height:36px;border-radius:8px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#0b2545;">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div style="min-width:0;flex:1;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13.5px;font-weight:700;color:#0f172a;line-height:1.2;">
                            {{ $formattedName }}
                        </span>
                        <span style="font-size:9px;font-weight:700;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:1px 5px;text-transform:uppercase;font-family:ui-monospace,monospace;">
                            {{ $guard }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- RIGHT: PERMISSIONS COUNT --}}
            <div style="display:flex;align-items:center;gap:5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:4px 10px;flex-shrink:0;">
                <span style="font-size:13px;font-weight:800;color:#0b2545;">
                    {{ $permsCount }}
                </span>
                <span style="font-size:10px;font-weight:600;color:#64748b;">
                    Izin
                </span>
            </div>
        </div>

        {{-- FOOTER --}}
        <div style="background:#f8fafc;padding:6px 14px;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:5px;font-size:10.5px;color:#64748b;">
            <svg style="width:12px;height:12px;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Diperbarui: {{ $updatedAt }}</span>
        </div>

    </div>
</div>
