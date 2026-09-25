<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\DocumentAnalyticsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class DocumentManagementDashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    public static function getNavigationIcon(): ?string
    {
        return null;
    }

    protected static ?int $navigationSort = 2;

    protected static string $routePath = '/dasbor-manajemen-dokumen';

    protected static ?string $title = 'Dasbor Manajemen Dokumen';

    public function getWidgets(): array
    {
        return [
            DocumentAnalyticsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
