<div class="fi-topbar-ctn">
    @php
        $isRtl = __('filament-panels::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
        $hasTopNavigation = filament()->hasTopNavigation();
        $hasNavigation = filament()->hasNavigation();
        $hasTenancy = filament()->hasTenancy();
    @endphp

    {{-- ── CSS: Navbar inner wrapper sejajar PRESISI dengan .fi-main-ctn ── --}}
    <style>
        /*
         * .dcms-topbar-inner meng-MIRROR persis behavior .fi-main-ctn:
         *   max-width  : var(--app-container-max-width)  = 1400px  (dari AdminPanelProvider)
         *   margin     : auto (center di layar > 1400px)
         *   padding    : var(--app-container-padding)    = 32px
         *
         * CSS variable didefinisikan SATU KALI di AdminPanelProvider :root.
         * Navbar dan dashboard MEMBACA variable yang sama → selalu sinkron.
         */

        /* ── Reset padding bawaan vendor di fi-topbar ── */
        .fi-topbar {
            padding-inline: 0 !important;
        }

        /* ── Desktop: wrapper konten navbar mirror .fi-main-ctn ── */
        @media (min-width: 768px) {
            .dcms-topbar-inner {
                display: flex;
                align-items: center;
                width: 100%;
                height: 100%;
                min-height: inherit;
                /* Mirror fi-main-ctn: */
                max-width: var(--app-container-max-width, 1400px);
                margin-left: auto;
                margin-right: auto;
                padding-left: var(--app-container-padding, 32px);
                padding-right: var(--app-container-padding, 32px);
            }

            /*
             * FIX: .fi-topbar-start .fi-logo mendapat ms-3 (12px) dari vendor.
             * Zero-kan agar logo DCMS rata persis dengan tepi kiri konten dashboard.
             */
            .fi-topbar .fi-topbar-start .fi-logo {
                margin-inline-start: 0 !important;
            }
        }

        /* ── Mobile (< 768px): wrapper transparan — tidak ubah layout ── */
        @media (max-width: 767.98px) {
            .dcms-topbar-inner {
                display: contents;
            }
        }

        /* ============================================================
           SEARCH EXPAND + NAV TEXT↔ICON SWAP
           Orchestrated: everything synced to 280ms, sequenced via delay
        ============================================================ */

        /* ── GLOBAL: Shared timing constants (via CSS vars) ── */
        :root {
            --dcms-anim-ease:  cubic-bezier(0.4, 0, 0.2, 1);

            /* ── OPEN: text collapses first, then icon+search expand ── */
            --dcms-open-text-dur:       150ms;   /* text wipes away quickly       */
            --dcms-open-text-delay:     0ms;     /* starts immediately             */
            --dcms-open-icon-dur:       220ms;   /* icon fades in                 */
            --dcms-open-icon-delay:     80ms;    /* after text finishes (~80%)    */
            --dcms-open-search-dur:     280ms;   /* search expands                */
            --dcms-open-search-delay:   60ms;    /* slightly after text starts    */

            /* ── CLOSE: search+icon collapse first, then text wipe-reveals ── */
            --dcms-close-icon-dur:      150ms;   /* icon fades out fast           */
            --dcms-close-icon-delay:    0ms;     /* immediately                   */
            --dcms-close-search-dur:    280ms;   /* search shrinks                */
            --dcms-close-search-delay:  0ms;     /* immediately                   */
            --dcms-close-text-dur:      300ms;   /* wipe reveal duration          */
            --dcms-close-text-delay:    80ms;    /* wait for search to free space */
        }

        @media (min-width: 768px) {

            /* ── Safety: prevent nav wrap during any transition phase ── */
            .fi-topbar-nav-groups {
                flex-wrap: nowrap !important;
            }
            /* The topbar-end (search + icons) must not wrap either */
            .fi-topbar-end {
                flex-shrink: 0;
                flex-wrap: nowrap !important;
            }
            /* Whole inner container: never wrap */
            .dcms-topbar-inner {
                flex-wrap: nowrap !important;
            }

            /* ── 1. Search container ── */
            .fi-global-search-ctn {
                display: inline-flex;
                align-items: center;
                flex-shrink: 0;
                position: relative;
            }

            /* ── 2. Search icon trigger button ── */
            .dcms-search-icon-btn {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: transparent;
                color: #94a3b8;
                cursor: pointer;
                flex-shrink: 0;
                overflow: hidden;
                /* CLOSE: collapse immediately */
                transition:
                    width   var(--dcms-close-icon-dur) var(--dcms-anim-ease) var(--dcms-close-icon-delay),
                    opacity var(--dcms-close-icon-dur) var(--dcms-anim-ease) var(--dcms-close-icon-delay),
                    background 0.18s ease,
                    color      0.18s ease;
            }
            .dcms-search-icon-btn:hover {
                background: rgba(30, 64, 175, 0.10);
                color: #1e40af;
            }
            .dcms-search-active .dcms-search-icon-btn {
                width: 0 !important;
                opacity: 0 !important;
                pointer-events: none !important;
                /* OPEN: collapse with same timing */
                transition:
                    width   var(--dcms-open-icon-dur) var(--dcms-anim-ease) var(--dcms-open-text-delay),
                    opacity var(--dcms-open-icon-dur) var(--dcms-anim-ease) var(--dcms-open-text-delay),
                    background 0.18s ease,
                    color      0.18s ease;
            }

            /* ── 3. Search field ── */
            .fi-global-search {
                display: block !important;
                overflow: hidden !important;
                visibility: visible !important;
                width: 0;
                min-width: 0;
                opacity: 0;
                pointer-events: none;
                /* CLOSE: shrink immediately */
                transition:
                    width   var(--dcms-close-search-dur) var(--dcms-anim-ease) var(--dcms-close-search-delay),
                    opacity var(--dcms-close-search-dur) var(--dcms-anim-ease) var(--dcms-close-search-delay);
            }
            .dcms-search-active .fi-global-search {
                width: 260px;
                opacity: 1;
                pointer-events: auto;
                /* OPEN: expand after text collapses */
                transition:
                    width   var(--dcms-open-search-dur) var(--dcms-anim-ease) var(--dcms-open-search-delay),
                    opacity var(--dcms-open-search-dur) var(--dcms-anim-ease) var(--dcms-open-search-delay);
            }

            /* ── 4. Nav item button: min-width:0 allows children to shrink ── */
            .fi-topbar-nav-groups .fi-topbar-item-btn {
                min-width: 0;
                overflow: hidden;
            }

            /* ── 5. Icon wrapper ──
               Default (CLOSED): collapsed (max-width:0, invisible)
               On OPEN:          expanded, visible
               CLOSE: fade out fast (0ms delay)
               OPEN:  fade in after text has left (~80ms delay)
            ── */
            .fi-topbar-nav-groups .dcms-nav-icon-wrap {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                flex-shrink: 0;
                max-width: 0;
                opacity: 0;
                /* CLOSE: collapse immediately so text can reuse space */
                transition:
                    max-width var(--dcms-close-icon-dur) var(--dcms-anim-ease) var(--dcms-close-icon-delay),
                    opacity   var(--dcms-close-icon-dur) var(--dcms-anim-ease) var(--dcms-close-icon-delay);
            }
            .dcms-search-active .fi-topbar-nav-groups .dcms-nav-icon-wrap {
                max-width: 26px;
                opacity: 1;
                /* OPEN: fade in after text has collapsed */
                transition:
                    max-width var(--dcms-open-icon-dur) var(--dcms-anim-ease) var(--dcms-open-icon-delay),
                    opacity   var(--dcms-open-icon-dur) var(--dcms-anim-ease) var(--dcms-open-icon-delay);
            }

            /* ── 6a. Label OUTER — layout space controller ──
               Controls how much horizontal space the label occupies in the flex row.

               CLOSE: snaps from 0 → var(--dcms-label-w) instantly at t=80ms
                      (reserves space BEFORE the visual wipe starts)
               OPEN:  snaps to 0 after wipe finishes
                      (frees space AFTER text is visually gone)
            ── */
            .fi-topbar-nav-groups .dcms-label-outer {
                display: inline-block;
                overflow: hidden;
                white-space: nowrap;
                min-width: 0;
                vertical-align: middle;
                max-width: var(--dcms-label-w, 200px);  /* CLOSED: full width */
                transition: max-width 0ms linear var(--dcms-close-text-delay);
            }
            .dcms-search-active .fi-topbar-nav-groups .dcms-label-outer {
                max-width: 0 !important;
                transition: max-width 0ms linear calc(var(--dcms-open-text-delay) + var(--dcms-open-text-dur));
            }

            /* ── 6b. Label INNER — visual wipe (clip-path) ──

               WHY clip-path, not max-width on inner:
               - max-width animation on the inner would fight the outer's
                 overflow:hidden during the reveal, causing no visible wipe.
               - clip-path runs on the GPU compositor — does not affect layout,
                 does not cause reflow, and can be freely animated.

               CLOSE (icon→text reveal):
                 clip-path: inset(0 100% 0 0)  →  inset(0 0% 0 0)
                 = right-inset shrinks from 100% to 0% over 300ms
                 = text is unmasked left-to-right (curtain opens from left)

               OPEN (text→icon collapse):
                 clip-path: inset(0 0% 0 0)  →  inset(0 100% 0 0)
                 = right-inset grows from 0% to 100% over 150ms
                 = text is masked right-to-left (curtain closes to right)
            ── */
            .fi-topbar-nav-groups .fi-topbar-item-label {
                display: inline-block;
                overflow: visible;     /* outer handles clipping, not this element */
                white-space: nowrap;
                /* CLOSED state: fully revealed */
                clip-path: inset(0 0% 0 0);
                /* CLOSE anim: wipe reveal left→right */
                transition: clip-path var(--dcms-close-text-dur) var(--dcms-anim-ease) var(--dcms-close-text-delay);
            }
            .dcms-search-active .fi-topbar-nav-groups .fi-topbar-item-label {
                /* OPEN state: fully hidden (clipped from left) */
                clip-path: inset(0 100% 0 0) !important;
                /* OPEN anim: wipe collapse right→left */
                transition: clip-path var(--dcms-open-text-dur) var(--dcms-anim-ease) var(--dcms-open-text-delay) !important;
            }

            /* ── 7. Chevron: always visible ── */
            .fi-topbar-nav-groups .fi-topbar-group-toggle-icon {
                display: inline-flex !important;
                opacity: 1 !important;
                flex-shrink: 0;
            }

            /* ── 8. Tooltip on icon-only state ── */
            .dcms-search-active .fi-topbar-nav-groups .fi-topbar-item-btn {
                position: relative;
            }
            .dcms-search-active .fi-topbar-nav-groups .fi-topbar-item-btn::after {
                content: attr(data-dcms-label);
                position: absolute;
                top: calc(100% + 6px);
                left: 50%;
                transform: translateX(-50%);
                background: #1e293b;
                color: #f8fafc;
                font-size: 11px;
                font-weight: 500;
                white-space: nowrap;
                padding: 4px 8px;
                border-radius: 6px;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.15s ease;
                z-index: 9999;
                box-shadow: 0 2px 8px rgba(0,0,0,0.18);
            }
            .dcms-search-active .fi-topbar-nav-groups .fi-topbar-item-btn:hover::after {
                opacity: 1;
            }
        }

        /* ── Mobile: no icon/text swap, search always visible ── */
        @media (max-width: 767.98px) {
            .dcms-search-icon-btn { display: none !important; }
            .fi-global-search {
                width: auto !important;
                opacity: 1 !important;
                overflow: visible !important;
                pointer-events: auto !important;
                transition: none !important;
            }
            .fi-topbar-nav-groups .fi-topbar-item-label {
                clip-path: none !important;
                transition: none !important;
            }
            .fi-topbar-nav-groups .dcms-label-outer {
                max-width: none !important;
                overflow: visible !important;
                transition: none !important;
            }
            .fi-topbar-nav-groups .dcms-nav-icon-wrap {
                max-width: 0 !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        }
    </style>

    <nav class="fi-topbar">
        <div class="dcms-topbar-inner"
            x-data="{ isSearchOpen: false }"
            x-bind:class="{ 'dcms-search-active': isSearchOpen }"
            @dcms-open-search.window="isSearchOpen = true"
            @dcms-close-search.window="isSearchOpen = false"
            x-on:keydown.escape.window="isSearchOpen = false"
            x-on:resize.window.debounce.100ms="if (isSearchOpen) { isSearchOpen = false }"
            @click.outside="isSearchOpen = false"
            x-init="
                // ── LABEL WIDTH MEASUREMENT ──
                // Measures each label's true scrollWidth so clip-path reveal
                // has the exact space it needs. Sets --dcms-label-w on .dcms-label-outer.
                //
                // RACE CONDITIONS HANDLED:
                // 1. CSS transitions disabled during measurement (prevents mid-flight reads)
                // 2. +8px safety buffer (sub-pixel rounding + kerning variation)
                // 3. Re-measured after document.fonts.ready (FOUT fix)
                // 4. Outer max-width temporarily set to 'none' to allow natural text size

                const measureLabels = () => {
                    const outers = document.querySelectorAll('.fi-topbar-nav-groups .dcms-label-outer');
                    outers.forEach(outer => {
                        const inner = outer.querySelector('.fi-topbar-item-label');
                        if (!inner) return;

                        // 1. Freeze all transitions on both elements during measurement
                        const outerOrigTrans = outer.style.transition;
                        const innerOrigTrans = inner.style.transition;
                        outer.style.transition = 'none';
                        inner.style.transition = 'none';

                        // 2. Force both to natural width so scrollWidth is accurate
                        const outerOrigMax = outer.style.maxWidth;
                        const innerOrigClip = inner.style.clipPath;
                        outer.style.maxWidth = 'none';
                        inner.style.clipPath = 'none';  // remove any clip in progress

                        // 3. Force a layout flush so browser recalculates
                        void outer.offsetWidth;

                        // 4. Read the TRUE natural text width
                        const w = inner.scrollWidth;

                        // 5. Restore previous states
                        outer.style.maxWidth = outerOrigMax;
                        inner.style.clipPath = innerOrigClip;

                        // 6. Re-enable transitions (in next frame so restore doesn't animate)
                        requestAnimationFrame(() => {
                            outer.style.transition = outerOrigTrans;
                            inner.style.transition = innerOrigTrans;
                        });

                        // 7. Store measured width + 8px safety buffer on outer
                        //    CSS var cascades from outer → inner automatically
                        outer.style.setProperty('--dcms-label-w', (w + 8) + 'px');
                    });
                };

                $nextTick(() => measureLabels());

                // Re-measure once fonts are guaranteed loaded (avoids FOUT)
                if (document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(() => {
                        // Only re-measure if values may have changed (font swap)
                        $nextTick(() => measureLabels());
                    });
                }
            "
        >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_START) }}

        @if ($hasNavigation)
            <x-filament::icon-button
                color="gray"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
                :icon-alias="\Filament\View\PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.open()"
                x-show="! $store.sidebar.isOpen"
                class="fi-topbar-open-sidebar-btn"
            />

            <x-filament::icon-button
                color="gray"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                :icon-alias="\Filament\View\PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                class="fi-topbar-close-sidebar-btn"
            />
        @endif

        <div class="fi-topbar-start">
            @if ($isSidebarCollapsibleOnDesktop)
                <x-filament::icon-button
                    color="gray"
                    :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronLeft : \Filament\Support\Icons\Heroicon::OutlinedChevronRight"
                    {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL,
                            \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON,
                        ]
                        : \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON
                    "
                    icon-size="lg"
                    :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                    x-cloak
                    x-data="{}"
                    x-on:click="$store.sidebar.open()"
                    x-show="! $store.sidebar.isOpen"
                    class="fi-topbar-open-collapse-sidebar-btn"
                />
            @endif

            @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                <x-filament::icon-button
                    color="gray"
                    :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronRight : \Filament\Support\Icons\Heroicon::OutlinedChevronLeft"
                    {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL,
                            \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON,
                        ]
                        : \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON
                    "
                    icon-size="lg"
                    :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                    x-cloak
                    x-data="{}"
                    x-on:click="$store.sidebar.close()"
                    x-show="$store.sidebar.isOpen"
                    class="fi-topbar-close-collapse-sidebar-btn"
                />
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_LOGO_BEFORE) }}

            @if ($homeUrl = filament()->getHomeUrl())
                <a {{ \Filament\Support\generate_href_html($homeUrl) }}>
                    <x-filament-panels::logo />
                </a>
            @else
                <x-filament-panels::logo />
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_LOGO_AFTER) }}
        </div>

        @if ($hasTopNavigation || (! $hasNavigation))
            @if ($hasTenancy && filament()->hasTenantMenu())
                <x-filament-panels::tenant-menu teleport />
            @endif

            @if ($hasNavigation)
                @php
                    $navigation = filament()->getNavigation();
                @endphp

                <ul class="fi-topbar-nav-groups">
                    @foreach ($navigation as $group)
                        @php
                            $groupLabel = $group->getLabel();
                            $groupExtraTopbarAttributeBag = $group->getExtraTopbarAttributeBag();
                            $isGroupActive = $group->isActive();
                            $groupIcon = $group->getIcon();
                        @endphp

                        @if ($groupLabel)
                            <x-filament::dropdown
                                class="fi-topbar-nav-group-dropdown"
                                placement="bottom-start"
                                teleport
                                :attributes="\Filament\Support\prepare_inherited_attributes($groupExtraTopbarAttributeBag)"
                            >
                                <x-slot name="trigger">
                                    <x-filament-panels::topbar.item
                                        :active="$isGroupActive"
                                        :icon="$groupIcon"
                                    >
                                        {{ $groupLabel }}
                                    </x-filament-panels::topbar.item>
                                </x-slot>

                                @php
                                    $lists = [];

                                    foreach ($group->getItems() as $item) {
                                        if ($childItems = $item->getChildItems()) {
                                            $lists[] = [
                                                $item,
                                                ...$childItems,
                                            ];
                                            $lists[] = [];

                                            continue;
                                        }

                                        if (empty($lists)) {
                                            $lists[] = [$item];

                                            continue;
                                        }

                                        $lists[count($lists) - 1][] = $item;
                                    }

                                    if (empty($lists[count($lists) - 1])) {
                                        array_pop($lists);
                                    }
                                @endphp

                                @foreach ($lists as $list)
                                    <x-filament::dropdown.list>
                                        @foreach ($list as $item)
                                            @php
                                                $isItemActive = $item->isActive();
                                                $itemBadge = $item->getBadge();
                                                $itemBadgeColor = $item->getBadgeColor();
                                                $itemBadgeTooltip = $item->getBadgeTooltip();
                                                $itemUrl = $item->getUrl();
                                                $itemIcon = $isItemActive ? ($item->getActiveIcon() ?? $item->getIcon()) : $item->getIcon();
                                                $shouldItemOpenUrlInNewTab = $item->shouldOpenUrlInNewTab();
                                            @endphp

                                            <x-filament::dropdown.list.item
                                                :badge="$itemBadge"
                                                :badge-color="$itemBadgeColor"
                                                :badge-tooltip="$itemBadgeTooltip"
                                                :color="$isItemActive ? 'primary' : 'gray'"
                                                :href="$itemUrl"
                                                :icon="$itemIcon"
                                                tag="a"
                                                :target="$shouldItemOpenUrlInNewTab ? '_blank' : null"
                                            >
                                                {{ $item->getLabel() }}
                                            </x-filament::dropdown.list.item>
                                        @endforeach
                                    </x-filament::dropdown.list>
                                @endforeach
                            </x-filament::dropdown>
                        @else
                            @foreach ($group->getItems() as $item)
                                @php
                                    $isItemActive = $item->isActive();
                                    $itemActiveIcon = $item->getActiveIcon();
                                    $itemBadge = $item->getBadge();
                                    $itemBadgeColor = $item->getBadgeColor();
                                    $itemBadgeTooltip = $item->getBadgeTooltip();
                                    $itemIcon = $item->getIcon();
                                    $shouldItemOpenUrlInNewTab = $item->shouldOpenUrlInNewTab();
                                    $itemUrl = $item->getUrl();
                                @endphp

                                <x-filament-panels::topbar.item
                                    :active="$isItemActive"
                                    :active-icon="$itemActiveIcon"
                                    :badge="$itemBadge"
                                    :badge-color="$itemBadgeColor"
                                    :badge-tooltip="$itemBadgeTooltip"
                                    :icon="$itemIcon"
                                    :should-open-url-in-new-tab="$shouldItemOpenUrlInNewTab"
                                    :url="$itemUrl"
                                >
                                    {{ $item->getLabel() }}
                                </x-filament-panels::topbar.item>
                            @endforeach
                        @endif
                    @endforeach
                </ul>
            @endif
        @endif

        <div
            @if ($hasTenancy)
                x-persist="topbar.end.panel-{{ filament()->getId() }}.tenant-{{ filament()->getTenant()?->getKey() }}"
            @else
                x-persist="topbar.end.panel-{{ filament()->getId() }}"
            @endif
            class="fi-topbar-end"
        >
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::GLOBAL_SEARCH_BEFORE) }}

            @if (filament()->isGlobalSearchEnabled() && filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Topbar)
                @livewire(Filament\Livewire\GlobalSearch::class)
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::GLOBAL_SEARCH_AFTER) }}

            @if (filament()->auth()->check())
                @if (filament()->hasDatabaseNotifications() && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Topbar)
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if (filament()->hasUserMenu() && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Topbar)
                    <x-filament-panels::user-menu />
                @endif
            @endif
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_END) }}
        </div>{{-- end .dcms-topbar-inner --}}
    </nav>

    <x-filament-actions::modals />
</div>
