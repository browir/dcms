<?php

namespace App\Filament\Admin\Resources\DocumentCategories;

use App\Filament\Admin\Resources\DocumentCategories\Pages\ManageDocumentCategories;
use App\Models\DocumentCategory;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocumentCategoryResource extends Resource
{
    protected static ?string $model = DocumentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Kategori Dokumen';

    protected static ?string $pluralModelLabel = 'Kategori Dokumen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('prefix'),
            Toggle::make('is_active')->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('prefix')->placeholder('-'),
            IconEntry::make('is_active')->boolean(),
            TextEntry::make('created_at')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return Tables\DocumentCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocumentCategories::route('/'),
        ];
    }
}
