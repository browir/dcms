<?php

namespace App\Filament\Admin\Resources\Documents;

use App\Filament\Admin\Resources\Documents\Pages\CreateDocument;
use App\Filament\Admin\Resources\Documents\Pages\EditDocument;
use App\Filament\Admin\Resources\Documents\Pages\ListDocuments;
use App\Filament\Admin\Resources\Documents\Pages\ViewDocument;
use App\Filament\Admin\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Admin\Resources\Documents\Schemas\DocumentInfolist;
use App\Filament\Admin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $pluralModelLabel = 'Dokumen';

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 10;

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Nomor Kode' => $record->code_number ?? '—',
            'Departemen' => $record->department?->name ?? '—',
            'Status' => match ($record->status) {
                'draft' => 'Pending',
                'pending_kabid' => 'Menunggu Kabid',
                'pending_direktur' => 'Menunggu Direktur',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                'archived' => 'Diarsipkan',
                default => $record->status,
            },
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'code_number', 'description', 'content'];
    }

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    public static function getNavigationBadge(): ?string
    {
        $pending = \Illuminate\Support\Facades\Cache::remember(
            'nav_badge_documents_pending',
            30,
            fn () => Document::query()
                ->whereIn('status', ['pending_kabid', 'pending_direktur'])
                ->count()
        );

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Dokumen menunggu review';
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->access();
    }
}
