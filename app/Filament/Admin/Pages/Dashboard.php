<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\Dashboard\AgendaRoomsWidget;
use App\Filament\Admin\Widgets\Dashboard\FollowUpWidget;
use App\Filament\Admin\Widgets\Dashboard\ModuleOverviewWidget;
use App\Filament\Admin\Widgets\Dashboard\NumberingDocumentsWidget;
use App\Filament\Admin\Widgets\EmployeeActivityHubWidget;
use App\Filament\Admin\Widgets\MyCalendarWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Beranda';

    protected static ?string $navigationLabel = 'Beranda';

    /**
     * Sembunyikan bar judul bawaan Filament; widget sapaan (EmployeeActivityHubWidget)
     * sudah berfungsi sebagai header. Judul tab browser tetap memakai $title.
     */
    public function getHeading(): string
    {
        return '';
    }

    /**
     * Satu beranda untuk seluruh layanan DCMS: sapaan & agenda terdekat,
     * ringkasan modul, agenda + ruangan, tindak lanjut (notulensi & dokumen),
     * penomoran surat + statistik dokumen, lalu kalender rapat.
     */
    public function getWidgets(): array
    {
        return [
            EmployeeActivityHubWidget::class,
            ModuleOverviewWidget::class,
            AgendaRoomsWidget::class,
            FollowUpWidget::class,
            NumberingDocumentsWidget::class,
            MyCalendarWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
