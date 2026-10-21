@php
    $record        = $getRecord();
    $causer        = $record->causer?->name ?? 'Sistem';
    $logName       = $record->log_name ?? 'general';
    $event         = $record->event ?? '';
    $description   = $record->description ?? '—';
    $subjectType   = $record->subject_type ? \Illuminate\Support\Str::of($record->subject_type)->afterLast('\\')->headline()->toString() : null;
    $subjectLabel  = $subjectType ? "{$subjectType} #{$record->subject_id}" : '—';
    $logNameFormatted = ucwords($logName);
    $eventFormatted   = ucwords($event);
    $eventStyle = match ($event) {
        'created'  => 'background:#ecfdf5;color:#047857;border-color:#6ee7b7;',
        'updated'  => 'background:#fffbeb;color:#b45309;border-color:#fcd34d;',
        'deleted'  => 'background:#fff1f2;color:#be123c;border-color:#fca5a5;',
        'restored' => 'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;',
        default    => 'background:#f8fafc;color:#475569;border-color:#cbd5e1;',
    };
    $logBadgeStyle = match (strtolower($logName)) {
        'resource'      => 'background:#dbeafe;color:#1e40af;',
        'models'        => 'background:#d1fae5;color:#065f46;',
        'access'        => 'background:#fef3c7;color:#92400e;',
        'notifications' => 'background:#ede9fe;color:#5b21b6;',
        default         => 'background:#e2e8f0;color:#334155;',
    };
    $timeFormatted = $record->created_at ? $record->created_at->translatedFormat('d M Y, H:i') : '—';
    $timeAgo       = $record->created_at ? $record->created_at->diffForHumans() : '';
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
        <div style="background:#f0f4f9;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;gap:6px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;overflow:hidden;flex:1;min-width:0;">
                <svg style="width:14px;height:14px;color:#2563eb;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span style="font-size:12px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px;">{{ $causer }}</span>
                <span style="font-size:10px;font-weight:600;padding:1px 6px;border-radius:4px;flex-shrink:0;{{ $logBadgeStyle }}">{{ $logNameFormatted }}</span>
            </div>
            @if($event)
                <div style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:6px;border:1px solid;font-size:9.5px;font-weight:700;letter-spacing:0.03em;flex-shrink:0;{{ $eventStyle }}">{{ $eventFormatted }}</div>
            @endif
        </div>
        <div style="padding:10px 12px;box-sizing:border-box;">
            <div style="font-size:12.5px;font-weight:600;color:#1e293b;line-height:1.45;word-break:break-word;margin-bottom:8px;">{{ $description }}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px 10px;">
                @if($subjectType)<div><div style="font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:1px;">Subjek</div><div style="font-size:11px;font-weight:600;color:#334155;">{{ $subjectLabel }}</div></div>@endif
                <div>
                    <div style="font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:1px;">Waktu</div>
                    <div style="font-size:11px;font-weight:600;color:#334155;">{{ $timeFormatted }}</div>
                    @if($timeAgo)<div style="font-size:10px;color:#94a3b8;">{{ $timeAgo }}</div>@endif
                </div>
            </div>
        </div>
    </div>
</div>
