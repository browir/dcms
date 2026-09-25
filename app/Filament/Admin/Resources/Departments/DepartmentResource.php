<?php

namespace App\Filament\Admin\Resources\Departments;

use App\Filament\Admin\Resources\Departments\Pages\ManageDepartments;
use App\Models\Department;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Departemen';

    protected static ?string $pluralModelLabel = 'Departemen';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('code')->label('Kode')->required(),
            Select::make('company_id')
                ->label('Perusahaan')
                ->relationship('company', 'name')
                ->live()
                ->afterStateUpdated(fn ($state) => session()->put('last_company_id', $state))
                ->default(fn () => session('last_company_id'))
                ->required(),
            Toggle::make('is_active')->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Nama'),
            TextEntry::make('code')->label('Kode'),
            TextColumn::make('company.name')->label('Perusahaan')->searchable(),
            IconEntry::make('is_active')->boolean(),
            TextEntry::make('created_at')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return Tables\DepartmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDepartments::route('/'),
        ];
    }
}
