@php
    $record        = $getRecord();
    $causer        = $record->causer?->name ?? 'Sistem';
    $eventLabel    = match ($record->event) {
        'created'  => 'Dibuat',
        'updated'  => 'Diperbarui',
        'deleted'  => 'Dihapus',
        'restored' => 'Dipulihkan',
        default    => ucfirst($record->event ?? 'Aktivitas'),
    };
    $eventStyle    = match ($record->event) {
        'created'  => 'background:#ecfdf5;color:#047857;border-color:#6ee7b7;',
        'updated'  => 'background:#fffbeb;color:#b45309;border-color:#fcd34d;',
        'deleted'  => 'background:#fff1f2;color:#be123c;border-color:#fca5a5;',
        'restored' => 'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;',
        default    => 'background:#f8fafc;color:#475569;border-color:#cbd5e1;',
    };
    $module        = $record->subject_type ? class_basename($record->subject_type) : 'Umum';
    // Shorten long CamelCase module names: e.g. "MandatoryDocumentReminderNotification" → "Mandatory Doc..."
    $moduleShort   = strlen($module) > 14 ? substr(preg_replace('/([A-Z])/', ' $1', $module), 0, 12) . '…' : $module;
    $timeFormatted = $record->created_at ? $record->created_at->translatedFormat('d M Y, H:i:s') : '—';
    $timeAgo       = $record->created_at ? $record->created_at->diffForHumans() : '';
    // Shorten description for mobile display
    $descShort     = mb_strlen($record->description ?? '') > 60
        ? mb_substr($record->description, 0, 57) . '…'
        : ($record->description ?? '—');
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
        {{ $causer }}
    </span>
</div>

{{-- TAMPILAN MOBILE (Sembunyi di desktop) --}}
<div class="dcms-mobile-card md:hidden" style="width:100%;margin:4px 0;">
    <div style="width:100%;background:#ffffff;border:1px solid #cbd5e1;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);box-sizing:border-box;">
        <div style="background:#f0f4f9;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;">
            <div style="display:flex;align-items:center;gap:6px;overflow:hidden;min-width:0;">
                <svg style="width:14px;height:14px;color:#2563eb;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span style="font-size:12px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;">{{ $causer }}</span>
                <span style="font-size:10px;font-weight:600;color:#475569;background:#e2e8f0;padding:1px 5px;border-radius:4px;flex-shrink:0;max-width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $module }}">{{ $moduleShort }}</span>
            </div>
            <div style="display:inline-flex;align-items:center;padding:2px 7px;border-radius:6px;border:1px solid;font-size:9.5px;font-weight:700;letter-spacing:0.03em;flex-shrink:0;{{ $eventStyle }}">{{ $eventLabel }}</div>
        </div>
        <div style="padding:8px 12px;box-sizing:border-box;">
            <div style="font-size:12px;font-weight:600;color:#1e293b;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $record->description }}">{{ $descShort }}</div>
            <div style="display:flex;align-items:center;gap:4px;margin-top:6px;font-size:11px;color:#64748b;">
                <svg style="width:12px;height:12px;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $timeFormatted }}</span>
                @if($timeAgo)<span style="color:#94a3b8;">({{ $timeAgo }})</span>@endif
            </div>
        </div>
    </div>
</div>
