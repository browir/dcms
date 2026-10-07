@php
    $record   = $getRecord();
    $title    = $record->title ?? '—';
    $start    = $record->date_time;
    $end      = $record->end_time;
    $location = $record->location;
    $status   = $record->status ?? 'scheduled';
    $creator  = $record->creator?->name;

    $statusLabel = match ($status) { 'scheduled' => 'Terjadwal', 'completed' => 'Selesai', 'cancelled' => 'Batal', default => ucfirst($status) };

    // Penanda waktu relatif (hanya untuk rapat terjadwal)
    $relative = null;
    if ($status === 'scheduled' && $start) {
        $now = now();
        $effectiveEnd = $record->effectiveEndTime();

        if ($now->between($start, $effectiveEnd)) {
            $relative = ['Sedang berlangsung', 'live'];
        } elseif ($start->isToday()) {
            $relative = ['Hari ini', 'today'];
        } elseif ($start->isTomorrow()) {
            $relative = ['Besok', 'soon'];
        } elseif ($start->isFuture() && ($days = (int) $now->copy()->startOfDay()->diffInDays($start->copy()->startOfDay())) <= 7) {
            $relative = ["{$days} hari lagi", 'soon'];
        }
    }

    $participants     = $record->participants;
    $participantCount = $participants->count();
    $avatarPalette    = ['#2563eb', '#7c3aed', '#db2777', '#ea580c', '#059669', '#0891b2'];

    $hasNotulen = ! empty($record->file_path);
@endphp

@once
    <style>
        /* ── Wrapper record Filament: biarkan kartu mengisi penuh ── */
        .fi-ta-content-grid .fi-ta-record:has(.mcard) {
            align-items: stretch !important;
            border-radius: 16px !important;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 4px 14px rgba(15, 23, 42, .05) !important;
            transition: transform .2s cubic-bezier(.16, 1, .3, 1), box-shadow .2s ease !important;
        }
        .fi-ta-content-grid .fi-ta-record:has(.mcard):hover {
            transform: translateY(-3px);
            box-shadow: 0 2px 4px rgba(15, 23, 42, .05), 0 14px 30px rgba(30, 64, 175, .12) !important;
            background: #fff !important;
        }
        .fi-ta-content-grid .fi-ta-record:has(.mcard) .fi-ta-record-content-ctn {
            flex: 1 1 auto;
            min-width: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .fi-ta-content-grid .fi-ta-record:has(.mcard) .fi-ta-record-content {
            display: block;
            padding: 0 !important;
        }
        /* Checkbox seleksi: pojok kanan atas kartu */
        .fi-ta-content-grid .fi-ta-record:has(.mcard) {
            padding: 0 !important;
        }
        .fi-ta-content-grid .fi-ta-record:has(.mcard) .fi-ta-record-checkbox {
            position: absolute;
            top: 18px;
            right: 16px;
            z-index: 5;
            margin: 0;
        }
        /* Footer aksi */
        .fi-ta-content-grid .fi-ta-record:has(.mcard) .fi-ta-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: flex-end !important;
            gap: 4px 14px !important;
            padding: 10px 16px !important;
            margin: 0 !important;
            border-top: 1px solid #eef2f7;
            background: #fafbfd;
        }

        /* ── Kartu ── */
        .mcard { position: relative; display: flex; flex-direction: column; gap: 14px; padding: 18px 16px 14px; }
        .mcard::before {
            content: ''; position: absolute; inset: 0 0 auto 0; height: 4px;
            background: var(--mcard-accent);
        }
        .mcard[data-status="scheduled"] { --mcard-accent: linear-gradient(90deg, #1e40af, #3b82f6); }
        .mcard[data-status="completed"] { --mcard-accent: linear-gradient(90deg, #94a3b8, #cbd5e1); }
        .mcard[data-status="cancelled"] { --mcard-accent: linear-gradient(90deg, #e11d48, #fb7185); }

        .mcard-head { display: flex; gap: 12px; align-items: flex-start; padding-right: 26px; }
        .mcard-date {
            flex-shrink: 0; width: 52px; border-radius: 12px; overflow: hidden; text-align: center;
            background: #fff; border: 1px solid #dbe4f3; box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .mcard-date-dow { background: #1e40af; color: #fff; font-size: 9.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; padding: 3px 0; }
        .mcard-date-day { font-size: 20px; font-weight: 900; color: #0f172a; line-height: 1.1; padding-top: 3px; }
        .mcard-date-mon { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; padding-bottom: 4px; }
        .mcard[data-status="completed"] .mcard-date-dow { background: #64748b; }
        .mcard[data-status="cancelled"] .mcard-date-dow { background: #e11d48; }

        .mcard-title {
            font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.35; word-break: break-word;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .mcard[data-status="cancelled"] .mcard-title { color: #64748b; text-decoration: line-through; text-decoration-color: #fda4af; }
        .mcard-time { margin-top: 5px; display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #475569; }
        .mcard-time svg { width: 14px; height: 14px; color: #64748b; }

        .mcard-rel { display: inline-flex; align-items: center; gap: 5px; padding: 1px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; }
        .mcard-rel[data-tone="live"]  { background: #dcfce7; color: #15803d; }
        .mcard-rel[data-tone="today"] { background: #fef3c7; color: #b45309; }
        .mcard-rel[data-tone="soon"]  { background: #e0e7ff; color: #4338ca; }
        .mcard-rel-dot { width: 6px; height: 6px; border-radius: 999px; background: currentColor; }
        .mcard-rel[data-tone="live"] .mcard-rel-dot { animation: mcard-pulse 1.4s ease-in-out infinite; }
        @keyframes mcard-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }

        .mcard-meta { display: flex; flex-direction: column; gap: 8px; }
        .mcard-row { display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; color: #334155; line-height: 1.4; }
        .mcard-row svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; color: #3b82f6; }
        .mcard-row-text { min-width: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .mcard-muted { color: #94a3b8; }

        .mcard-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: auto; }
        .mcard-people { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #475569; }
        .mcard-avatars { display: flex; }
        .mcard-avatar {
            width: 26px; height: 26px; border-radius: 999px; border: 2px solid #fff; margin-left: -8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 800; color: #fff; text-transform: uppercase;
        }
        .mcard-avatar:first-child { margin-left: 0; }
        .mcard-avatar-more { background: #e2e8f0; color: #475569; }

        .mcard-chips { display: flex; gap: 6px; flex-wrap: wrap; }
        .mcard-chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 999px; font-size: 10.5px; font-weight: 700; border: 1px solid; }
        .mcard-chip svg { width: 12px; height: 12px; }
        .mcard-chip[data-status="scheduled"] { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .mcard-chip[data-status="completed"] { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .mcard-chip[data-status="cancelled"] { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .mcard-chip-notulen { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }

        /* ── Desktop: kartu grid; Mobile: pakai meeting-mobile-card ── */
        @media (min-width: 768px) {
            .mcard-mobile { display: none !important; }
        }
        @media (max-width: 767.98px) {
            .mcard { display: none !important; }

            /* Kartu mobile sudah punya border, shadow & tombol sendiri */
            .fi-ta-content-grid .fi-ta-record:has(.mcard),
            .fi-ta-content-grid .fi-ta-record:has(.mcard):hover {
                background: transparent !important;
                box-shadow: none !important;
                transform: none !important;
                --tw-ring-shadow: 0 0 #0000 !important;
            }
            .fi-ta-content-grid .fi-ta-record:has(.mcard) .fi-ta-actions,
            .fi-ta-content-grid .fi-ta-record:has(.mcard) .fi-ta-record-checkbox {
                display: none !important;
            }
        }
    </style>
@endonce

{{-- Mobile: tetap memakai kartu mobile yang sudah ada --}}
<div class="mcard-mobile">
    @include('filament.tables.columns.meeting-mobile-card')
</div>

{{-- Desktop --}}
<div class="mcard" data-status="{{ $status }}">
    {{-- Header: blok tanggal + judul + waktu --}}
    <div class="mcard-head">
        <div class="mcard-date">
            <div class="mcard-date-dow">{{ $start?->translatedFormat('D') ?? '—' }}</div>
            <div class="mcard-date-day">{{ $start?->format('d') ?? '--' }}</div>
            <div class="mcard-date-mon">{{ $start?->translatedFormat('M Y') ?? '' }}</div>
        </div>

        <div style="flex:1;min-width:0;">
            <div class="mcard-title" title="{{ $title }}">{{ $title }}</div>
            <div class="mcard-time">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $start?->format('H:i') ?? '—' }}{{ $end ? ' – ' . $end->format('H:i') : '' }} WIB</span>
                @if ($relative)
                    <span class="mcard-rel" data-tone="{{ $relative[1] }}"><span class="mcard-rel-dot"></span>{{ $relative[0] }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Detail: lokasi & pembuat --}}
    <div class="mcard-meta">
        <div class="mcard-row">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="mcard-row-text {{ $location ? '' : 'mcard-muted' }}" title="{{ $location }}">{{ $location ?: 'Lokasi belum diatur' }}</span>
        </div>
        @if ($creator)
            <div class="mcard-row">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="mcard-row-text"><span class="mcard-muted">Dibuat oleh</span> {{ $creator }}</span>
            </div>
        @endif
    </div>

    {{-- Footer: peserta + status --}}
    <div class="mcard-foot">
        <div class="mcard-people">
            @if ($participantCount)
                <div class="mcard-avatars">
                    @foreach ($participants->take(4) as $participant)
                        <span class="mcard-avatar" style="background:{{ $avatarPalette[$participant->id % count($avatarPalette)] }};" title="{{ $participant->name }}">
                            {{ \Illuminate\Support\Str::of($participant->name)->explode(' ')->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                        </span>
                    @endforeach
                    @if ($participantCount > 4)
                        <span class="mcard-avatar mcard-avatar-more">+{{ $participantCount - 4 }}</span>
                    @endif
                </div>
                <span>{{ $participantCount }} peserta</span>
            @else
                <span class="mcard-muted">Belum ada peserta</span>
            @endif
        </div>

        <div class="mcard-chips">
            @if ($hasNotulen)
                <span class="mcard-chip mcard-chip-notulen">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Notulen
                </span>
            @endif
            <span class="mcard-chip" data-status="{{ $status }}">{{ $statusLabel }}</span>
        </div>
    </div>
</div>
