@php
    /** @var \App\Models\LetterNumber $record */
    $record = $getRecord();
    $jenis = $record->jenis_surat;
    $used = match (true) {
        $record->document !== null => ['Sudah diisi', 'success'],
        $record->agenda !== null => ['Surat kegiatan', 'success'],
        default => ['Belum diisi', 'warning'],
    };
    $canFill = ! $record->document && ! $record->agenda && (auth()->user()?->can('create', \App\Models\Document::class) ?? false);
@endphp

{{-- Desktop: nomor --}}
<div class="dcms-desktop-title-view">
    <span class="dcms-dtitle-main dcms-mono">{{ $record->nomor }}</span>
</div>

{{-- Mobile: kartu --}}
<div class="dcms-mobile-card">
    <div class="dcms-mcard">
        <div class="dcms-mcard-head">
            {!! \App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource::jenisBadge($jenis) !!}
            <div class="dcms-mcard-heading">
                <div class="dcms-mcard-title dcms-mono">{{ $record->nomor }}</div>
                <div class="dcms-mcard-sub">{{ $jenis->label() }}</div>
            </div>
            <span class="dcms-mcard-badge dcms-tone-{{ $used[1] }}">{{ $used[0] }}</span>
        </div>
        <div class="dcms-mcard-grid">
            <div class="dcms-mcard-span">
                <div class="dcms-mcard-label">Judul</div>
                <div class="dcms-mcard-value">{{ $record->judul_surat }}</div>
                @if ($record->perihal)
                    <div class="dcms-mcard-meta">{{ $record->perihal }}</div>
                @endif
            </div>
            <div>
                <div class="dcms-mcard-label">Tanggal</div>
                <div class="dcms-mcard-value">{{ $record->tanggal_surat->translatedFormat('d M Y') }}</div>
            </div>
            <div>
                <div class="dcms-mcard-label">Unit</div>
                <div class="dcms-mcard-value">{{ $record->unit?->name ?? '—' }}</div>
            </div>
        </div>
        @if ($canFill)
            <div class="dcms-mcard-foot">
                <span class="dcms-mcard-meta">Diambil {{ $record->creator?->name ?? '—' }}</span>
                <a href="{{ \App\Filament\Admin\Resources\Documents\DocumentResource::getUrl('create', ['nomor_surat' => $record->id]) }}"
                   wire:navigate class="dcms-mcard-action">Isi Dokumen →</a>
            </div>
        @endif
    </div>
</div>
