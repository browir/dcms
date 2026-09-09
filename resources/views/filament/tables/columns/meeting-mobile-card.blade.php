@php
    $record        = $getRecord();
    $title         = $record->title ?? '—';
    $dateTime      = $record->date_time ? \Carbon\Carbon::parse($record->date_time) : null;
    $dateFormatted = $dateTime?->translatedFormat('d M Y') ?? '—';
    $timeFormatted = $dateTime?->format('H:i') ?? '';
    $location      = $record->location ?? null;
    $status        = $record->status ?? 'scheduled';
    $creator       = $record->creator?->name ?? null;
    $statusLabel   = match ($status) { 'scheduled'=>'Terjadwal','completed'=>'Berakhir','cancelled'=>'Batal',default=>ucfirst($status) };
    $statusStyle   = match ($status) { 'scheduled'=>'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;','completed'=>'background:#f8fafc;color:#475569;border-color:#cbd5e1;','cancelled'=>'background:#fff1f2;color:#be123c;border-color:#fca5a5;',default=>'background:#f8fafc;color:#475569;border-color:#cbd5e1;' };
    $headerBg      = match ($status) { 'scheduled'=>'linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%)','cancelled'=>'linear-gradient(135deg,#fff1f2 0%,#ffe4e6 100%)',default=>'linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%)' };
    $borderColor   = match ($status) { 'scheduled'=>'#bfdbfe','cancelled'=>'#fca5a5',default=>'#e2e8f0' };
    $hasNotulen    = ! empty($record->file_path) &&
                     (auth()->user()->hasRole(['super_admin', 'Sekretaris']) ||
                      $record->created_by === auth()->id() ||
                      $record->participants()->where('users.id', auth()->id())->exists());
    $notulenUrl    = $hasNotulen ? route('notulen.view', $record) : null;
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
    <span style="font-size:13.5px;font-weight:700;color:#0f172a;line-height:1.35;">
        {{ $title }}
    </span>
</div>

{{-- TAMPILAN MOBILE (Sembunyi di desktop) --}}
<div class="dcms-mobile-card md:hidden" style="width:100%;margin:4px 0;">
    <div style="width:100%;background:#ffffff;border:1px solid {{ $borderColor }};border-radius:14px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06);box-sizing:border-box;">
        <div style="background:{{ $headerBg }};padding:10px 14px;display:flex;align-items:flex-start;gap:10px;border-bottom:1px solid {{ $borderColor }};">
            <div style="min-width:42px;background:#1e40af;border-radius:10px;padding:5px 6px;text-align:center;flex-shrink:0;color:#fff;">
                <div style="font-size:16px;font-weight:900;line-height:1;">{{ $dateTime?->format('d') ?? '--' }}</div>
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;line-height:1.2;">{{ $dateTime?->translatedFormat('M') ?? '' }}</div>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:800;color:#0f172a;line-height:1.35;word-break:break-word;">{{ $title }}</div>
                @if($timeFormatted)
                    <div style="font-size:11px;color:#64748b;margin-top:2px;display:flex;align-items:center;gap:4px;">
                        <svg style="width:12px;height:12px;color:#64748b;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $timeFormatted }} WIB</span>
                    </div>
                @endif
            </div>
            <div style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:6px;border:1px solid;font-size:9.5px;font-weight:700;flex-shrink:0;{{ $statusStyle }}">{{ $statusLabel }}</div>
        </div>
        <div style="padding:8px 14px;display:grid;grid-template-columns:1fr 1fr;gap:6px;">
            @if($location)
                <div>
                    <div style="font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:2px;">Lokasi</div>
                    <div style="font-size:11.5px;font-weight:600;color:#1e293b;word-break:break-word;display:flex;align-items:center;gap:4px;">
                        <svg style="width:13px;height:13px;color:#2563eb;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>{{ $location }}</span>
                    </div>
                </div>
            @endif
            @if($creator)
                <div>
                    <div style="font-size:9.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:2px;">Dibuat Oleh</div>
                    <div style="font-size:11.5px;font-weight:600;color:#1e293b;">{{ $creator }}</div>
                </div>
            @endif
        </div>
        {{-- FOOTER --}}
        <div style="padding:8px 12px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:nowrap;white-space:nowrap;">
            @if($hasNotulen)
                <a href="{{ $notulenUrl }}" target="_blank" x-data="{}" x-on:click="$event.stopPropagation()" title="Lihat Hasil Notulensi"
                   style="display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:4px 10px;height:30px;border-radius:9999px;border:1px solid #16a34a;color:#fff;background:#16a34a;font-size:11px;font-weight:700;text-decoration:none;position:relative;z-index:20;flex-shrink:0;">
                    <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Notulensi</span>
                </a>
            @endif
            <a href="{{ \App\Filament\Admin\Resources\Meetings\MeetingResource::getUrl('view', ['record' => $record->id]) }}" x-data="{}" x-on:click="$event.stopPropagation()" title="Detail Rapat"
               style="display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:4px 10px;height:30px;border-radius:9999px;border:1px solid #3b82f6;color:#2563eb;background:#fff;font-size:11px;font-weight:700;text-decoration:none;position:relative;z-index:20;flex-shrink:0;">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>Detail</span>
            </a>
        </div>
    </div>
</div>
