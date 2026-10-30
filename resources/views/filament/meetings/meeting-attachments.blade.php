{{-- Galeri lampiran dokumentasi rapat + lightbox (klik untuk memperbesar). --}}
@php
    /** @var \App\Models\Meeting $record */
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $images = collect(is_array($record->attachments) ? $record->attachments : [])
        ->filter(fn ($p) => is_string($p) && $p !== '')
        ->map(fn ($p) => ['url' => $disk->url($p), 'name' => basename($p)])
        ->values();
@endphp

@include('filament.meetings.partials.detail-styles')

<style>
    .mvd-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .75rem; }
    .mvd-thumb {
        position: relative; aspect-ratio: 4 / 3; border-radius: var(--ui-radius, 12px); overflow: hidden; padding: 0; cursor: zoom-in;
        border: 1px solid var(--ui-line, #e6eaf0); background: var(--ui-surface-2, #f8fafc);
    }
    .mvd-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .25s ease; }
    .mvd-thumb:hover img { transform: scale(1.04); }
    .mvd-thumb:focus-visible { outline: 2px solid var(--ui-primary, #2563eb); outline-offset: 2px; }
    .mvd-thumb-broken {
        position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .3rem;
        padding: .5rem; text-align: center; font-size: .72rem; color: var(--ui-muted, #64748b); cursor: default;
    }

    .mvd-lb { position: fixed; inset: 0; z-index: 60; background: rgba(15, 23, 42, .88); display: flex; align-items: center; justify-content: center; padding: 3.5rem 1rem 4.5rem; }
    .mvd-lb img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: .5rem; box-shadow: 0 20px 50px rgba(0,0,0,.4); }
    .mvd-lb-btn {
        position: absolute; display: flex; align-items: center; justify-content: center; width: 2.6rem; height: 2.6rem; border-radius: 999px;
        background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); cursor: pointer; text-decoration: none;
    }
    .mvd-lb-btn:hover { background: rgba(255,255,255,.22); }
    .mvd-lb-bar { position: absolute; left: 0; right: 0; bottom: 1rem; display: flex; justify-content: center; align-items: center; gap: .75rem; color: rgba(255,255,255,.85); font-size: .8rem; }
</style>

<div
    x-data="{
        images: @js($images),
        open: false,
        index: 0,
        show(i) { this.index = i; this.open = true; },
        next() { this.index = (this.index + 1) % this.images.length; },
        prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; },
    }"
    @keydown.escape.window="open = false"
    @keydown.arrow-right.window="open && next()"
    @keydown.arrow-left.window="open && prev()"
>
    <div class="mvd-gallery">
        @foreach ($images as $i => $img)
            <button type="button" class="mvd-thumb" x-data="{ broken: false }" @click="!broken && show({{ $i }})" title="{{ $img['name'] }}">
                <img src="{{ $img['url'] }}" alt="Dokumentasi rapat {{ $i + 1 }}" loading="lazy" x-show="!broken" x-on:error="broken = true" x-init="if ($el.complete && ! $el.naturalWidth) broken = true">
                <span class="mvd-thumb-broken" x-show="broken" x-cloak>
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/></svg>
                    Gambar tidak dapat dimuat
                </span>
            </button>
        @endforeach
    </div>

    <template x-teleport="body">
        <div class="mvd-lb" x-show="open" x-cloak x-transition.opacity @click.self="open = false" role="dialog" aria-modal="true" aria-label="Pratinjau dokumentasi">
            <img :src="images[index]?.url" :alt="images[index]?.name">

            <button type="button" class="mvd-lb-btn" style="top:1rem;right:1rem;" @click="open = false" aria-label="Tutup">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>

            <template x-if="images.length > 1">
                <div>
                    <button type="button" class="mvd-lb-btn" style="left:1rem;top:50%;transform:translateY(-50%);" @click="prev()" aria-label="Sebelumnya">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                    <button type="button" class="mvd-lb-btn" style="right:1rem;top:50%;transform:translateY(-50%);" @click="next()" aria-label="Berikutnya">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
            </template>

            <div class="mvd-lb-bar">
                <span x-text="(index + 1) + ' / ' + images.length"></span>
                <a :href="images[index]?.url" :download="images[index]?.name" class="mvd-lb-btn" style="position:static;width:auto;height:auto;padding:.4rem .8rem;border-radius:999px;font-size:.78rem;gap:.35rem;">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Unduh
                </a>
            </div>
        </div>
    </template>
</div>
