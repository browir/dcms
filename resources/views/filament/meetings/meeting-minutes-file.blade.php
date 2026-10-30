{{-- Kartu berkas notulensi (kolom samping halaman Detail Rapat). --}}
@php
    /** @var \App\Models\Meeting $record */
    $hasFile = filled($record->file_path);
    $ext = $hasFile ? strtoupper(pathinfo($record->file_path, PATHINFO_EXTENSION) ?: 'FILE') : null;
    $isWord = in_array($ext, ['DOC', 'DOCX'], true);
    $canEdit = auth()->user()?->can('update', $record) ?? false;
    $editUrl = \App\Filament\Admin\Resources\Meetings\MeetingResource::getUrl('edit', ['record' => $record]);
@endphp

@include('filament.meetings.partials.detail-styles')

@if ($hasFile)
    <div style="display:flex;align-items:center;gap:.75rem;padding:.8rem;border:1px solid var(--ui-line,#e6eaf0);border-radius:var(--ui-radius,12px);background:var(--ui-surface-2,#f8fafc);">
        <div style="width:2.6rem;height:3rem;min-width:2.6rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;letter-spacing:.03em;color:#fff;background:{{ $isWord ? '#2563eb' : '#dc2626' }};">
            {{ $ext }}
        </div>
        <div style="min-width:0;flex:1;">
            <div style="font-size:.85rem;font-weight:600;color:var(--ui-ink,#0f172a);line-height:1.3;">Notulensi rapat</div>
            <div class="mvd-muted" style="font-size:.74rem;">Diperbarui {{ $record->updated_at?->diffForHumans() }}</div>
        </div>
    </div>
    <div style="display:flex;gap:.5rem;margin-top:.75rem;">
        <a href="{{ route('notulen.view', $record->id) }}" target="_blank" rel="noopener" class="mvd-btn mvd-btn-primary" style="flex:1;">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            Buka Notulensi
        </a>
    </div>
@else
    <div class="mvd-empty">
        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        <strong>Belum ada berkas notulensi</strong>
        <span>
            @if ($record->status === 'completed')
                PDF dibuat otomatis setelah notulensi diisi lalu disimpan.
            @else
                PDF dibuat otomatis saat rapat ditandai <em>Selesai</em> dan notulensi sudah diisi.
            @endif
        </span>
        @if ($canEdit && $record->status !== 'cancelled')
            <a href="{{ $editUrl }}" class="mvd-btn mvd-btn-ghost" style="margin-top:.35rem;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                Isi Notulensi
            </a>
        @endif
    </div>
@endif
