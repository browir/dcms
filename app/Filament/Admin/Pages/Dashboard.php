<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\EmployeeActivityHubWidget;
use App\Filament\Admin\Widgets\MeetingCardsWidget;
use App\Filament\Admin\Widgets\MeetingStatsWidget;
use App\Filament\Admin\Widgets\MyCalendarWidget;
use App\Filament\Admin\Widgets\PriorityActionsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dasbor';

    protected static ?string $navigationLabel = 'Dasbor';

    /**
     * Hide the built-in Filament page header bar.
     * The greeting widget (EmployeeActivityHubWidget) serves as the single header.
     */
    public function getHeader(): ?View
    {
        return null;
    }

    public function getWidgets(): array
    {
        return [
            EmployeeActivityHubWidget::class,
            MeetingStatsWidget::class,
            MeetingCardsWidget::class,
            MyCalendarWidget::class,
            PriorityActionsWidget::class,
        ];
    }
}
