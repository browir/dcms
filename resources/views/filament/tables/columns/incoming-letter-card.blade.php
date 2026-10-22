@php
    /** @var \App\Models\IncomingLetter $record */
    $record = $getRecord();
    $status = \App\Models\IncomingLetter::STATUS[$record->status] ?? $record->status;
    $tone = match ($record->status) {
        \App\Models\IncomingLetter::STATUS_BARU => 'warning',
        \App\Models\IncomingLetter::STATUS_DIBACA => 'info',
        default => 'success',
    };
    $sifat = $record->sifat !== 'biasa' ? (\App\Models\IncomingLetter::SIFAT[$record->sifat] ?? null) : null;
@endphp

{{-- Desktop: perihal + nomor agenda --}}
<div class="dcms-desktop-title-view">
    <div class="dcms-dtitle">
        <span class="dcms-dtitle-main">{{ $record->perihal }}</span>
        <span class="dcms-dtitle-sub dcms-mono">{{ $record->nomor_agenda }}@if ($sifat) · <strong>{{ $sifat }}</strong>@endif</span>
    </div>
</div>

{{-- Mobile: kartu --}}
<div class="dcms-mobile-card">
    <div class="dcms-mcard">
        <div class="dcms-mcard-head">
            <div class="dcms-mcard-date">
                <span>{{ $record->tanggal_terima->locale('id')->isoFormat('ddd') }}</span>
                <strong>{{ $record->tanggal_terima->format('d') }}</strong>
                <span>{{ $record->tanggal_terima->locale('id')->isoFormat('MMM') }}</span>
            </div>
            <div class="dcms-mcard-heading">
                <div class="dcms-mcard-title">{{ $record->perihal }}</div>
                <div class="dcms-mcard-sub">Dari {{ $record->pengirim }}</div>
            </div>
            <span class="dcms-mcard-badge dcms-tone-{{ $tone }}">{{ $status }}</span>
        </div>
        <div class="dcms-mcard-grid">
            <div>
                <div class="dcms-mcard-label">Nomor agenda</div>
                <div class="dcms-mcard-value dcms-mono">{{ $record->nomor_agenda }}</div>
            </div>
            <div>
                <div class="dcms-mcard-label">Unit tujuan</div>
                <div class="dcms-mcard-value">{{ $record->unit?->name ?? '—' }}</div>
            </div>
            @if ($record->nomor_surat)
                <div class="dcms-mcard-span">
                    <div class="dcms-mcard-label">Nomor surat pengirim</div>
                    <div class="dcms-mcard-value dcms-mono">{{ $record->nomor_surat }}</div>
                </div>
            @endif
        </div>
        <div class="dcms-mcard-foot">
            <span class="dcms-mcard-meta">
                {{ $sifat ? $sifat.' · ' : '' }}{{ $record->file_path ? 'Ada scan surat' : 'Tanpa scan' }}
            </span>
            <span class="dcms-mcard-more">Detail →</span>
        </div>
    </div>
</div>
