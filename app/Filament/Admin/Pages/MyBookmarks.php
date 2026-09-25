<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class MyBookmarks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Dokumen Tersimpan';

    protected static ?string $title = 'Bookmarks';

    protected static ?string $slug = 'bookmarks';

    public function getView(): string
    {
        return 'filament.admin.pages.my-bookmarks';
    }

    public static function getNavigationBadge(): ?string
    {
        $userId = Auth::id();
        if (! $userId) {
            return null;
        }

        $count = \Illuminate\Support\Facades\Cache::remember(
            'nav_badge_bookmarks_'.$userId,
            30,
            fn () => DB::table('document_bookmarks')->where('user_id', $userId)->count()
        );

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah dokumen yang Anda simpan';
    }

    /**
     * Menyediakan base query untuk tabel — wajib ada agar Filament tidak throw LogicException.
     */
    protected function getTableQuery(): Builder
    {
        return Document::query()
            ->access()
            ->whereHas('bookmarks', fn (Builder $q) => $q->where('user_id', Auth::id() ?? 0));
    }

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateHeading('Belum Ada Dokumen Tersimpan')
            ->emptyStateDescription('Tandai dokumen penting dengan mengeklik tombol Simpan agar dapat diakses dengan cepat di sini.');
    }
}
