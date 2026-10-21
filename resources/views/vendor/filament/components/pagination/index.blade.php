@props([
    'currentPageOptionProperty' => 'tableRecordsPerPage',
    'extremeLinks' => false,
    'paginator',
    'pageOptions' => [],
])

@php
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\View\SupportIconAlias;
    use Illuminate\Contracts\Pagination\CursorPaginator;
    use Illuminate\Pagination\LengthAwarePaginator;
    use Illuminate\Support\Number;

    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $isSimple = ! $paginator instanceof LengthAwarePaginator;
@endphp

<nav
    aria-label="{{ __('filament::components/pagination.label') }}"
    {{
        $attributes->class([
            'fi-pagination',
            'fi-simple' => $isSimple,
        ])
    }}
>
    @if (! $paginator->onFirstPage())
        @php
            if ($paginator instanceof CursorPaginator) {
                $wireClickAction = "setPage('{$paginator->previousCursor()->encode()}', '{$paginator->getCursorName()}')";
            } else {
                $wireClickAction = "previousPage('{$paginator->getPageName()}')";
            }
        @endphp

        <x-filament::button
            color="gray"
            rel="prev"
            :wire:click="$wireClickAction"
            :wire:key="$this->getId() . '.pagination.previous'"
            class="fi-pagination-previous-btn"
        >
            {{ __('filament::components/pagination.actions.previous.label') }}
        </x-filament::button>
    @endif

    @if (! $isSimple)
        <span class="fi-pagination-overview">
            {{
                trans_choice(
                    'filament::components/pagination.overview',
                    $paginator->total(),
                    [
                        'first' => Number::format($paginator->firstItem() ?? 0),
                        'last' => Number::format($paginator->lastItem() ?? 0),
                        'total' => Number::format($paginator->total()),
                    ],
                )
            }}
        </span>
    @endif

    @if (count($pageOptions) > 1)
        <div class="fi-pagination-records-per-page-select-ctn hidden md:inline-flex items-center">
            <x-filament::dropdown placement="top-end" class="fi-pagination-records-dropdown">
                <x-slot name="trigger">
                    <button
                        type="button"
                        class="fi-pagination-records-combined-pill group inline-flex flex-row items-center bg-white border border-slate-200 rounded-lg shadow-sm hover:border-blue-600 transition-all duration-200 focus:outline-none overflow-hidden"
                    >
                        <!-- Divider Left: "per halaman" label -->
                        <span class="fi-pagination-records-combined-pill-label px-3 py-1.5 text-xs font-semibold text-slate-500 bg-slate-50 border-r border-slate-200 select-none group-hover:text-slate-700">
                            {{ __('filament::components/pagination.fields.records_per_page.label') }}
                        </span>

                        <!-- Divider Right: Selected Number + Chevron Icon -->
                        <span class="fi-pagination-records-combined-pill-value inline-flex flex-row items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-700 group-hover:text-blue-600">
                            <span>{{ $paginator->perPage() }}</span>
                            <x-filament::icon
                                icon="heroicon-m-chevron-down"
                                class="fi-pagination-records-chevron w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition-transform duration-300"
                            />
                        </span>
                    </button>
                </x-slot>

                <x-filament::dropdown.list>
                    @foreach ($pageOptions as $option)
                        @php
                            $isSelected = (string) $paginator->perPage() === (string) $option;
                        @endphp
                        <x-filament::dropdown.list.item
                            :wire:click="'$set(\'' . $currentPageOptionProperty . '\', \'' . $option . '\')'"
                            :icon="$isSelected ? 'heroicon-m-check' : null"
                            :color="$isSelected ? 'primary' : 'gray'"
                            class="fi-pagination-option-item"
                        >
                            {{ $option === 'all' ? __('filament::components/pagination.fields.records_per_page.options.all') : $option }}
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        </div>
    @endif







    @if ($paginator->hasMorePages())
        @php
            if ($paginator instanceof CursorPaginator) {
                $wireClickAction = "setPage('{$paginator->nextCursor()->encode()}', '{$paginator->getCursorName()}')";
            } else {
                $wireClickAction = "nextPage('{$paginator->getPageName()}')";
            }
        @endphp

        <x-filament::button
            color="gray"
            rel="next"
            :wire:click="$wireClickAction"
            :wire:key="$this->getId() . '.pagination.next'"
            class="fi-pagination-next-btn"
        >
            {{ __('filament::components/pagination.actions.next.label') }}
        </x-filament::button>
    @endif

    @if ((! $isSimple) && $paginator->hasPages())
        <ol class="fi-pagination-items">
            @if (! $paginator->onFirstPage())
                @if ($extremeLinks)
                    <x-filament::pagination.item
                        :aria-label="__('filament::components/pagination.actions.first.label')"
                        :icon="$isRtl ? Heroicon::ChevronDoubleRight : Heroicon::ChevronDoubleLeft"
                        :icon-alias="
                            $isRtl
                            ? SupportIconAlias::PAGINATION_FIRST_BUTTON_RTL
                            : SupportIconAlias::PAGINATION_FIRST_BUTTON
                        "
                        rel="first"
                        :wire:click="'gotoPage(1, \'' . $paginator->getPageName() . '\')'"
                        :wire:key="$this->getId() . '.pagination.first'"
                    />
                @endif

                <x-filament::pagination.item
                    :aria-label="__('filament::components/pagination.actions.previous.label')"
                    :icon="$isRtl ? Heroicon::ChevronRight : Heroicon::ChevronLeft"
                    {{-- @deprecated Use `SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL` instead of `SupportIconAlias::PAGINATION_PREVIOUS_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL,
                            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON,
                        ]
                        : SupportIconAlias::PAGINATION_PREVIOUS_BUTTON
                    "
                    rel="prev"
                    :wire:click="'previousPage(\'' . $paginator->getPageName() . '\')'"
                    :wire:key="$this->getId() . '.pagination.previous'"
                />
            @endif

            @foreach ($paginator->render()->offsetGet('elements') as $element)
                @if (is_string($element))
                    <x-filament::pagination.item disabled :label="$element" />
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <x-filament::pagination.item
                            :active="$page === $paginator->currentPage()"
                            :aria-label="trans_choice('filament::components/pagination.actions.go_to_page.label', $page, ['page' => Number::format($page)])"
                            :label="$page"
                            :wire:click="'gotoPage(' . $page . ', \'' . $paginator->getPageName() . '\')'"
                            :wire:key="$this->getId() . '.pagination.' . $paginator->getPageName() . '.' . $page"
                        />
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <x-filament::pagination.item
                    :aria-label="__('filament::components/pagination.actions.next.label')"
                    :icon="$isRtl ? Heroicon::ChevronLeft : Heroicon::ChevronRight"
                    {{-- @deprecated Use `SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL` instead of `SupportIconAlias::PAGINATION_NEXT_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL,
                            SupportIconAlias::PAGINATION_NEXT_BUTTON,
                        ]
                        : SupportIconAlias::PAGINATION_NEXT_BUTTON
                    "
                    rel="next"
                    :wire:click="'nextPage(\'' . $paginator->getPageName() . '\')'"
                    :wire:key="$this->getId() . '.pagination.next'"
                />

                @if ($extremeLinks)
                    <x-filament::pagination.item
                        :aria-label="__('filament::components/pagination.actions.last.label')"
                        :icon="$isRtl ? Heroicon::ChevronDoubleLeft : Heroicon::ChevronDoubleRight"
                        :icon-alias="
                            $isRtl
                            ? SupportIconAlias::PAGINATION_LAST_BUTTON_RTL
                            : SupportIconAlias::PAGINATION_LAST_BUTTON
                        "
                        rel="last"
                        :wire:click="'gotoPage(' . $paginator->lastPage() . ', \'' . $paginator->getPageName() . '\')'"
                        :wire:key="$this->getId() . '.pagination.last'"
                    />
                @endif
            @endif
        </ol>
    @endif
</nav>
