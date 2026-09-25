<?php

namespace App\Filament\Admin\Resources\Units;

use App\Filament\Admin\Resources\Units\Pages\ManageUnits;
use App\Models\Unit;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Unit';

    protected static ?string $pluralModelLabel = 'Unit';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('prefix')->label('Kode')->required(),
            Select::make('company_id')
                ->label('Perusahaan')
                ->options(\App\Models\Company::where('is_active', true)->pluck('name', 'id'))
                ->live()
                ->afterStateUpdated(fn($set) => $set('department_id', null))
                ->formatStateUsing(fn($record) => $record?->department?->company_id)
                ->dehydrated(false)
                ->searchable()
                ->preload()
                ->required(),
            Select::make('department_id')
                ->label('Departemen')
                ->relationship('department', 'name', fn(\Illuminate\Database\Eloquent\Builder $query, $get) => $query->where('company_id', $get('company_id'))->where('is_active', true))
                ->live()
                ->searchable()
                ->preload()
                ->afterStateUpdated(fn($state) => session()->put('last_department_id', $state))
                ->default(fn() => session('last_department_id'))
                ->required(),
            Toggle::make('is_active')->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Nama'),
            TextEntry::make('prefix')->label('Kode'),
            TextEntry::make('department.name')->label('Departemen'),
            IconEntry::make('is_active')->boolean(),
            TextEntry::make('created_at')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return Tables\UnitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnits::route('/'),
        ];
    }
}
