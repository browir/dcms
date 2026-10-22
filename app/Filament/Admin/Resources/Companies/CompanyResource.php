<?php

namespace App\Filament\Admin\Resources\Companies;

use App\Filament\Admin\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Perusahaan';

    protected static ?string $pluralModelLabel = 'Perusahaan';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Perusahaan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->maxLength(50),
                TextInput::make('whatsapp_group')
                    ->label('Grup WhatsApp')
                    ->helperText('ID grup WhatsApp untuk pengumuman kegiatan tingkat perusahaan.')
                    ->maxLength(255),
                TextInput::make('kode_surat')
                    ->label('Kode Surat')
                    ->helperText('Kode penerbit di nomor dokumen, mis. SGG atau RSUSM-BRB. Kosongkan untuk memakai Kode.')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('code')->label('Kode'),
                TextEntry::make('kode_surat')->label('Kode Surat')->placeholder('Sama dengan Kode'),
                TextEntry::make('is_active')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Tidak Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return Tables\CompaniesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCompanies::route('/'),
        ];
    }
}
