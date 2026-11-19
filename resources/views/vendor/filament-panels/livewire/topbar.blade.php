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
           DESKTOP NAVBAR: CLEAN & STABLE
        ============================================================ */
        @media (min-width: 768px) {
            .fi-topbar-nav-groups {
                flex-wrap: nowrap !important;
            }
            .fi-topbar-end {
                flex-shrink: 0 !important;
                flex-wrap: nowrap !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 0.5rem !important;
            }
            .dcms-topbar-inner {
                flex-wrap: nowrap !important;
            }
            .fi-topbar-end > * {
                flex-shrink: 0 !important;
            }
            .fi-topbar-nav-groups .fi-topbar-item-btn {
                min-width: 0;
            }
            .fi-topbar-nav-groups .dcms-nav-icon-wrap {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                opacity: 1;
            }
            .fi-topbar-nav-groups .dcms-label-outer {
                display: inline-block;
                max-width: none !important;
                overflow: visible !important;
            }
            .fi-topbar-nav-groups .fi-topbar-item-label {
                display: inline-block;
                max-width: none !important;
                overflow: visible !important;
                opacity: 1 !important;
            }
            .fi-topbar-nav-groups .fi-topbar-group-toggle-icon {
                display: inline-flex !important;
                opacity: 1 !important;
                flex-shrink: 0;
            }
        }

        /* ── Mobile ── */
        @media (max-width: 767.98px) {
            .fi-topbar-nav-groups .dcms-label-outer {
                max-width: none !important;
            }
        }
    </style>

    <nav class="fi-topbar">
        <div class="dcms-topbar-inner">
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

                            /*
                             * Fallback icons for groups without a NavigationGroup icon.
                             * (Groups that have items with icons cannot declare an icon via
                             *  NavigationGroup::icon() — it triggers sidebar validation.)
                             * This mapping assigns an icon purely at the topbar render layer.
                             */
                            if (blank($groupIcon)) {
                                $groupIcon = match ($groupLabel) {
                                    'Agenda'         => 'heroicon-o-queue-list',
                                    'Administrasi'   => 'heroicon-o-building-office-2',
                                    'Dokumen'        => 'heroicon-o-document-duplicate',
                                    'Data Master'    => 'heroicon-o-circle-stack',
                                    'Pengaturan'     => 'heroicon-o-cog-6-tooth',
                                    default          => null,
                                };
                            }
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
