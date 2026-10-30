{{-- Daftar peserta rapat (kolom samping halaman Detail Rapat). --}}
@php
    /** @var \App\Models\Meeting $record */
    $record->loadMissing('participants.company');

    // Notulis & pembuat ditampilkan paling atas, sisanya urut nama.
    $participants = $record->participants
        ->sortBy(fn ($u) => [
            $u->id === $record->notulis_id ? 0 : ($u->id === $record->created_by ? 1 : 2),
            mb_strtolower($u->name),
        ])
        ->values();

    $limit = 8;
    $palette = ['#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706', '#0891b2', '#4f46e5', '#dc2626'];
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    $attendance = [
        'confirmed' => ['Hadir', 'mvd-tag-green'],
        'declined' => ['Berhalangan', 'mvd-tag-red'],
    ];
@endphp

@include('filament.meetings.partials.detail-styles')

<style>
    .mvd-people { display: flex; flex-direction: column; }
    .mvd-person { display: flex; align-items: center; gap: .65rem; padding: .55rem 0; border-bottom: 1px solid var(--ui-line-soft, #eef1f5); }
    .mvd-person:last-child { border-bottom: 0; }
    .mvd-person-name { font-size: .84rem; font-weight: 600; color: var(--ui-ink, #0f172a); line-height: 1.3; overflow-wrap: anywhere; }
    .mvd-person-sub { font-size: .74rem; color: var(--ui-muted, #64748b); line-height: 1.3; }
    .mvd-more {
        margin-top: .5rem; width: 100%; padding: .45rem; border-radius: var(--ui-radius-sm, 8px); border: 1px solid var(--ui-line, #e6eaf0);
        background: var(--ui-surface-2, #f8fafc); color: var(--ui-primary, #2563eb); font-size: .78rem; font-weight: 600; cursor: pointer;
    }
    .mvd-more:hover { background: var(--ui-hover, #f5f8ff); }
</style>

@if ($participants->isEmpty())
    <div class="mvd-empty">
        <strong>Belum ada peserta</strong>
        <span>Tambahkan peserta lewat menu Ubah Rapat.</span>
    </div>
@else
    <div class="mvd-people" x-data="{ all: false }">
        @foreach ($participants as $i => $user)
            @php $att = $attendance[$user->pivot?->attendance] ?? null; @endphp
            <div class="mvd-person" @if ($i >= $limit) x-show="all" x-cloak @endif>
                <span class="mvd-avatar" style="background:{{ $palette[crc32($user->name) % count($palette)] }};">{{ $initials($user->name) }}</span>
                <div style="flex:1;min-width:0;">
                    <div class="mvd-person-name">{{ $user->name }}</div>
                    @if ($user->company?->name)
                        <div class="mvd-person-sub">{{ $user->company->name }}</div>
                    @endif
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.2rem;">
                    @if ($user->id === $record->notulis_id)
                        <span class="mvd-tag mvd-tag-violet">Notulis</span>
                    @endif
                    @if ($user->id === $record->created_by)
                        <span class="mvd-tag mvd-tag-blue">Pembuat</span>
                    @endif
                    @if ($att)
                        <span class="mvd-tag {{ $att[1] }}">{{ $att[0] }}</span>
                    @endif
                </div>
            </div>
        @endforeach

        @if ($participants->count() > $limit)
            <button type="button" class="mvd-more" @click="all = !all"
                x-text="all ? 'Tampilkan lebih sedikit' : 'Tampilkan semua ({{ $participants->count() }})'">
                Tampilkan semua ({{ $participants->count() }})
            </button>
        @endif
    </div>
@endif
