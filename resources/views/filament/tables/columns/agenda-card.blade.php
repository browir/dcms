@php
    /** @var \App\Models\Agenda $record */
    $record = $getRecord();
    $kategori = \App\Models\Agenda::KATEGORI[$record->kategori] ?? 'Kegiatan';
    $state = match (true) {
        $record->status === 'cancelled' => ['Dibatalkan', 'danger'],
        $record->status === 'completed' || $record->effectiveEndTime()->isPast() => ['Selesai', 'gray'],
        $record->date_time->isPast() => ['Berlangsung', 'success'],
        default => ['Terjadwal', 'info'],
    };
@endphp

{{-- Desktop: judul + jenis kegiatan --}}
<div class="dcms-desktop-title-view">
    <div class="dcms-dtitle">
        <span class="dcms-dtitle-main">{{ $record->title }}</span>
        <span class="dcms-dtitle-sub">{{ $kategori }}</span>
    </div>
</div>

{{-- Mobile: kartu --}}
<div class="dcms-mobile-card">
    <div class="dcms-mcard">
        <div class="dcms-mcard-head">
            <div class="dcms-mcard-date">
                <span>{{ $record->date_time->locale('id')->isoFormat('ddd') }}</span>
                <strong>{{ $record->date_time->format('d') }}</strong>
                <span>{{ $record->date_time->locale('id')->isoFormat('MMM') }}</span>
            </div>
            <div class="dcms-mcard-heading">
                <div class="dcms-mcard-title">{{ $record->title }}</div>
                <div class="dcms-mcard-sub">{{ $kategori }} · {{ $record->date_time->format('H.i') }}–{{ $record->effectiveEndTime()->format('H.i') }}</div>
            </div>
            <span class="dcms-mcard-badge dcms-tone-{{ $state[1] }}">{{ $state[0] }}</span>
        </div>
        <div class="dcms-mcard-grid">
            <div>
                <div class="dcms-mcard-label">Tempat</div>
                <div class="dcms-mcard-value">{{ $record->placeName() }}</div>
            </div>
            <div>
                <div class="dcms-mcard-label">Penyelenggara</div>
                <div class="dcms-mcard-value">{{ $record->unit?->name ?? '—' }}</div>
            </div>
            @if ($record->letterNumber)
                <div class="dcms-mcard-span">
                    <div class="dcms-mcard-label">Surat pemberitahuan</div>
                    <div class="dcms-mcard-value dcms-mono">{{ $record->letterNumber->nomor }}</div>
                </div>
            @endif
        </div>
        <div class="dcms-mcard-foot">
            <span class="dcms-mcard-meta">
                {{ $record->isAnnounced() ? 'Diumumkan '.$record->announced_at->locale('id')->diffForHumans() : 'Belum diumumkan' }}
                @if ($record->whatsapp_sent_at) · WhatsApp ✓ @endif
            </span>
            <span class="dcms-mcard-more">Detail →</span>
        </div>
    </div>
</div>
