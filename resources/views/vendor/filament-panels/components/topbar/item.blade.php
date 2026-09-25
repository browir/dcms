@props([
    'active' => false,
    'activeIcon' => null,
    'badge' => null,
    'badgeColor' => null,
    'badgeTooltip' => null,
    'icon' => null,
    'shouldOpenUrlInNewTab' => false,
    'url' => null,
])

@php
    $tag = $url ? 'a' : 'button';
@endphp

<li @class([
    'fi-topbar-item',
    'fi-active' => $active,
])>
    <{{ $tag }}
        @if ($url)
            {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab) }}
        @else
            type="button"
        @endif
        class="fi-topbar-item-btn"
        data-dcms-label="{{ strip_tags((string) $slot) }}"
        title="{{ strip_tags((string) $slot) }}"
    >
        {{-- Icon: hidden by default, slides in when search bar is open --}}
        @if ($icon || $activeIcon)
            <span class="dcms-nav-icon-wrap" aria-hidden="true">
                {{ \Filament\Support\generate_icon_html(($active && $activeIcon) ? $activeIcon : $icon, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['fi-topbar-item-icon'])) }}
            </span>
        @endif

        {{-- Label: two-element wipe structure.
             dcms-label-outer  = layout space controller (max-width snaps instantly)
             fi-topbar-item-label = visual wipe (max-width animates 300ms, overflow:hidden = left→right reveal) --}}
        <span class="dcms-label-outer"><span class="fi-topbar-item-label">{{ $slot }}</span></span>

        @if (filled($badge))
            <x-filament::badge
                :color="$badgeColor"
                size="sm"
                :tooltip="$badgeTooltip"
            >
                {{ $badge }}
            </x-filament::badge>
        @endif

        @if (! $url)
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronDown, alias: \Filament\View\PanelsIconAlias::TOPBAR_GROUP_TOGGLE_BUTTON, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['fi-topbar-group-toggle-icon'])) }}
        @endif
    </{{ $tag }}>
</li>
