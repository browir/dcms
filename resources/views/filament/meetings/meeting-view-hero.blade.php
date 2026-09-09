{{-- Hero header halaman Detail Rapat.
     Background navy gradient + efek kilau (shimmer) menyamai banner dashboard (.eah-hero). --}}
@php
    /** @var \App\Models\Meeting $record */
    $status = $record->status ?? 'scheduled';

    $statusLabel = match ($status) {
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        default     => 'Terjadwal',
    };

    $accent = match ($status) {
        'completed' => '#34d399',
        'cancelled' => '#fb7185',
        default     => '#60a5fa',
    };

    $start = $record->date_time;
    $end   = $record->end_time ?: ($start ? $record->effectiveEndTime() : null);
    $now   = now();

    if ($status === 'completed') {
        $timeText = 'Rapat telah selesai';
    } elseif ($status === 'cancelled') {
        $timeText = 'Rapat dibatalkan';
    } elseif ($start && $end && $now->gte($start) && $now->lte($end)) {
        $timeText = 'Sedang berlangsung sekarang';
    } elseif ($start && $start->isFuture()) {
        $timeText = 'Dimulai ' . $start->diffForHumans($now);
    } elseif ($start && $start->isPast()) {
        $timeText = 'Terjadwal ' . $start->diffForHumans($now);
    } else {
        $timeText = 'Jadwal belum ditentukan';
    }

    $dateStr = $start ? $start->isoFormat('dddd, D MMMM Y') : '—';
    $timeStr = $start
        ? $start->isoFormat('HH:mm') . ($end ? ' – ' . $end->isoFormat('HH:mm') : '') . ' WITA'
        : '—';

    $participants = $record->participants ?? collect();
    $pCount = $participants->count();
    $pNames = $participants->take(3)->pluck('name')->implode(', ');
    if ($pCount > 3) {
        $pNames .= ' +' . ($pCount - 3) . ' lainnya';
    }

    $facts = [
        ['icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5', 'label' => 'Tanggal', 'value' => $dateStr],
        ['icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'label' => 'Waktu', 'value' => $timeStr],
        ['icon' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z', 'label' => 'Lokasi', 'value' => $record->location ?: 'Belum ditentukan'],
        ['icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z', 'label' => 'Notulis', 'value' => $record->notulis?->name ?: 'Belum ditunjuk'],
    ];
@endphp

<style>
    .mvh-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.5rem 1.6rem;
        color: #fff;
        background:
            url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.05'/%3E%3C/svg%3E"),
            radial-gradient(circle at top right, rgba(96, 165, 250, 0.15), transparent 50%),
            radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.2), transparent 50%),
            radial-gradient(circle at center, #1e40af 0%, #1e3a8a 45%, #0f172a 100%);
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .mvh-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent 25%, rgba(255, 255, 255, 0.08) 50%, transparent 75%);
        transform: translateX(-120%);
        animation: mvh-shimmer 6s linear infinite;
        pointer-events: none;
    }

    .mvh-hero > * {
        position: relative;
        z-index: 1;
    }

    @keyframes mvh-shimmer {
        to {
            transform: translateX(120%);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .mvh-hero::after {
            animation: none;
        }
    }
</style>

<div class="mvh-hero">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.6rem;margin-bottom:.7rem;">
        <span style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-weight:700;font-size:.72rem;letter-spacing:.03em;text-transform:uppercase;padding:.3rem .7rem;border-radius:999px;">
            <span style="width:.45rem;height:.45rem;border-radius:50%;background:{{ $accent }};box-shadow:0 0 8px {{ $accent }};"></span>{{ $statusLabel }}
        </span>
        <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:rgba(255,255,255,.82);">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            {{ $timeText }}
        </span>
    </div>

    <div style="font-size:1.4rem;font-weight:800;line-height:1.25;letter-spacing:-.015em;">
        {{ $record->title }}
    </div>
    @if ($record->doc_number)
        <div style="margin-top:.3rem;font-size:.78rem;color:rgba(255,255,255,.7);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">
            No. Dok: {{ $record->doc_number }}
        </div>
    @endif
    @if ($record->agenda)
        <div style="margin-top:.55rem;font-size:.86rem;color:rgba(255,255,255,.82);max-width:52rem;line-height:1.55;">
            {{ \Illuminate\Support\Str::limit($record->agenda, 180) }}
        </div>
    @endif

    <div style="height:1px;background:rgba(255,255,255,.15);margin:1.1rem 0;"></div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.9rem;">
        @foreach ($facts as $fact)
            <div style="display:flex;align-items:flex-start;gap:.6rem;">
                <div style="width:2.1rem;height:2.1rem;min-width:2.1rem;display:flex;align-items:center;justify-content:center;border-radius:.7rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fact['icon'] }}"/></svg>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:rgba(255,255,255,.6);font-weight:700;">{{ $fact['label'] }}</div>
                    <div style="font-size:.86rem;font-weight:600;line-height:1.35;word-break:break-word;">{{ $fact['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="display:flex;align-items:center;gap:.55rem;margin-top:1rem;padding-top:.9rem;border-top:1px solid rgba(255,255,255,.15);font-size:.82rem;color:rgba(255,255,255,.82);">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
        <span><strong>{{ $pCount }} peserta</strong>@if ($pNames) &nbsp;·&nbsp; {{ $pNames }} @endif</span>
    </div>
</div>
