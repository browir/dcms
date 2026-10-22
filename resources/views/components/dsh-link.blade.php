{{-- Link Beranda: tanpa href (user tidak punya akses ke halaman tujuan) tampil sebagai item biasa. --}}
@props(['href' => null])

@if (filled($href))
    <a href="{{ $href }}" @unless (str_starts_with($href, '#')) wire:navigate @endunless {{ $attributes }}>{{ $slot }}</a>
@else
    <div {{ $attributes->class(['dsh-static']) }}>{{ $slot }}</div>
@endif
