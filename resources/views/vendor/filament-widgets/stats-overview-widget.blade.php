@php
    use Filament\Support\View\ComponentAttributeBag;

    $columns = $this->getColumns();
    $pollingInterval = $this->getPollingInterval();

    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
@endphp

<x-filament-widgets::widget
    :attributes="
        (new ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
            ])
    "
>
    {{--
        Override Filament's CSS-custom-property grid system for mobile.
        Filament uses --cols-default on .fi-grid and --col-span-default on .fi-grid-col.
        We override these at mobile (<768px) so that:
          - Grid = 2 columns
          - 1st stat card = full width (spans 2)
          - 2nd & 3rd stat cards = each 1 column
    --}}
    <style>
        @media (max-width: 767px) {
            /* Force 2-column grid on mobile */
            .fi-wi-stats-overview .fi-grid {
                --cols-default: repeat(2, minmax(0, 1fr)) !important;
            }

            /* 1st card: "Rapat Hari Ini" → full width (rectangle) */
            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(1) {
                --col-span-default: span 2 / span 2 !important;
                grid-column: span 2 / span 2 !important;
            }

            /* 2nd card: "Undangan Rapat" → left square */
            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(2) {
                --col-span-default: span 1 / span 1 !important;
                grid-column: span 1 / span 1 !important;
            }

            /* 3rd card: "Total Rapat Mendatang" → right square */
            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(3) {
                --col-span-default: span 1 / span 1 !important;
                grid-column: span 1 / span 1 !important;
            }

            /* Make 2nd & 3rd cards maintain square aspect ratio */
            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(2) .fi-wi-stats-overview-stat,
            .fi-wi-stats-overview .fi-grid > .fi-grid-col:nth-child(3) .fi-wi-stats-overview-stat {
                aspect-ratio: 1 / 1;
            }

            /* Hide stat charts on mobile to keep cards compact */
            .fi-wi-stats-overview .fi-wi-stats-overview-stat-chart {
                display: none !important;
            }

            /* Fallback: also target direct children of fi-sc (schema container) */
            .fi-wi-stats-overview .fi-sc.fi-grid {
                --cols-default: repeat(2, minmax(0, 1fr)) !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .fi-wi-stats-overview .fi-sc.fi-grid > *:nth-child(1) {
                grid-column: span 2 / span 2 !important;
            }

            .fi-wi-stats-overview .fi-sc.fi-grid > *:nth-child(2) {
                grid-column: span 1 / span 1 !important;
            }

            .fi-wi-stats-overview .fi-sc.fi-grid > *:nth-child(3) {
                grid-column: span 1 / span 1 !important;
            }
        }
    </style>
    {{ $this->content }}
</x-filament-widgets::widget>
