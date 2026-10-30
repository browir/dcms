{{-- Daftar Action Plan & PIC pada halaman Detail Rapat. --}}
@php
    /** @var \App\Models\Meeting $record */
    $items = array_values(is_array($record->action_items) ? $record->action_items : []);

    $pics = \App\Models\User::with('company')
        ->whereIn('id', $record->uniqueActionItemPicUserIds())
        ->get()
        ->keyBy('id');

    $me = auth()->id();
    $palette = ['#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706', '#0891b2', '#4f46e5', '#dc2626'];
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

@include('filament.meetings.partials.detail-styles')

<style>
    .mvd-ai-list { display: flex; flex-direction: column; gap: .75rem; }
    .mvd-ai {
        display: flex; gap: .9rem; padding: 1rem; border: 1px solid var(--ui-line, #e6eaf0);
        border-radius: var(--ui-radius, 12px); background: var(--ui-surface, #fff);
    }
    .mvd-ai.is-mine { border-color: #93c5fd; box-shadow: inset 3px 0 0 var(--ui-primary, #2563eb); }
    .mvd-ai-no {
        width: 1.75rem; height: 1.75rem; min-width: 1.75rem; border-radius: 999px; display: flex; align-items: center;
        justify-content: center; font-size: .75rem; font-weight: 700;
        background: var(--ui-primary-soft, #eff6ff); color: var(--ui-primary, #2563eb);
    }
    .mvd-ai-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .6rem; }
    .mvd-ai-topic { font-size: .82rem; color: var(--ui-muted, #64748b); line-height: 1.5; white-space: pre-line; overflow-wrap: anywhere; }
    .mvd-ai-plan { font-size: .92rem; font-weight: 600; color: var(--ui-ink, #0f172a); line-height: 1.5; white-space: pre-line; overflow-wrap: anywhere; }
    .mvd-ai-pics { display: flex; flex-wrap: wrap; gap: .4rem; }
    .mvd-pic {
        display: inline-flex; align-items: center; gap: .4rem; padding: .2rem .65rem .2rem .2rem; border-radius: 999px;
        background: var(--ui-surface-2, #f8fafc); border: 1px solid var(--ui-line-soft, #eef1f5); max-width: 100%;
    }
    .mvd-pic-name { font-size: .78rem; font-weight: 600; color: var(--ui-ink-2, #334155); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mvd-pic-co { font-size: .72rem; color: var(--ui-muted, #64748b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>

@if (empty($items))
    <div class="mvd-empty">
        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
        <strong>Belum ada action plan</strong>
        <span>Action plan dan PIC hasil rapat akan tampil di sini setelah notulensi diisi.</span>
    </div>
@else
    <div class="mvd-ai-list">
        @foreach ($items as $i => $item)
            @php
                $picIds = collect((array) ($item['pic_ids'] ?? []))->map(fn ($id) => (int) $id)->filter()->unique();
                $isMine = $me && $picIds->contains($me);
            @endphp
            <div class="mvd-ai {{ $isMine ? 'is-mine' : '' }}">
                <div class="mvd-ai-no">{{ $i + 1 }}</div>
                <div class="mvd-ai-body">
                    @if (filled($item['pembahasan'] ?? null))
                        <div>
                            <div class="mvd-label" style="margin-bottom:.15rem;">Pembahasan</div>
                            <div class="mvd-ai-topic">{{ $item['pembahasan'] }}</div>
                        </div>
                    @endif

                    <div>
                        <div class="mvd-label" style="margin-bottom:.15rem;display:flex;align-items:center;gap:.4rem;">
                            Action Plan
                            @if ($isMine)
                                <span class="mvd-tag mvd-tag-blue">Tugas Anda</span>
                            @endif
                        </div>
                        <div class="mvd-ai-plan">{{ filled($item['action_plan'] ?? null) ? $item['action_plan'] : '—' }}</div>
                    </div>

                    <div>
                        <div class="mvd-label" style="margin-bottom:.35rem;">PIC</div>
                        @if ($picIds->isEmpty())
                            <span class="mvd-muted" style="font-size:.8rem;">Belum ada PIC</span>
                        @else
                            <div class="mvd-ai-pics">
                                @foreach ($picIds as $picId)
                                    @php $pic = $pics->get($picId); @endphp
                                    @if ($pic)
                                        <span class="mvd-pic" title="{{ $pic->name }}{{ $pic->company?->name ? ' — '.$pic->company->name : '' }}">
                                            <span class="mvd-avatar mvd-avatar-sm" style="background:{{ $palette[crc32($pic->name) % count($palette)] }};">{{ $initials($pic->name) }}</span>
                                            <span style="display:flex;flex-direction:column;min-width:0;line-height:1.2;">
                                                <span class="mvd-pic-name">{{ $pic->name }}</span>
                                                @if ($pic->company?->name)
                                                    <span class="mvd-pic-co">{{ $pic->company->name }}</span>
                                                @endif
                                            </span>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
