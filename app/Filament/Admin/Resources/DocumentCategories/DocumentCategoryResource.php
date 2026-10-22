<?php

namespace App\Filament\Admin\Resources\DocumentCategories;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\DocumentCategories\Pages\ManageDocumentCategories;
use App\Models\Company;
use App\Models\DocumentCategory;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Kategori Dokumen = master jenis dokumen. Kode jenis HRIS dipakai di nomor dokumen,
 * kelompok menentukan dokumen tampil di Dokumen Resmi atau Surat Keluar.
 */
class DocumentCategoryResource extends Resource
{
    protected static ?string $model = DocumentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Kategori Dokumen';

    protected static ?string $pluralModelLabel = 'Kategori Dokumen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('company:id,name')->withCount('documents');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')
                ->label('Nama Kategori')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('code')
                ->label('Kode Jenis (HRIS)')
                ->helperText('Dipakai di nomor dokumen, mis. 001/C.2/SGG/X/2026.')
                ->options(JenisSurat::options())
                ->searchable()
                ->native(false)
                ->required()
                ->live()
                ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                    // Kategori baru: isi nama & kelompok dari jenis HRIS bila belum diisi.
                    $jenis = JenisSurat::tryFrom((string) $state);
                    if ($jenis && blank($get('name'))) {
                        $set('name', $jenis->label());
                    }
                    if ($jenis) {
                        $set('kelompok', $jenis->kelompokAwal());
                    }
                }),
            ToggleButtons::make('kelompok')
                ->label('Tampil di menu')
                ->options(DocumentCategory::KELOMPOK)
                ->icons([
                    DocumentCategory::KELOMPOK_DOKUMEN => 'heroicon-m-document-text',
                    DocumentCategory::KELOMPOK_SURAT_KELUAR => 'heroicon-m-paper-airplane',
                ])
                ->default(DocumentCategory::KELOMPOK_DOKUMEN)
                ->inline()
                ->required(),
            Select::make('company_id')
                ->label('Perusahaan')
                ->helperText('Kosongkan bila kategori berlaku untuk semua perusahaan.')
                ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id'))
                ->placeholder('Semua perusahaan')
                ->searchable()
                ->native(false),
            Toggle::make('is_active')
                ->label('Aktif')
                ->helperText('Kategori nonaktif tidak bisa dipilih saat mengambil nomor.')
                ->default(true)
                ->inline(false),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Nama Kategori'),
            TextEntry::make('code')->label('Kode Jenis')->badge()->placeholder('-'),
            TextEntry::make('kelompok')->label('Menu')->formatStateUsing(fn (?string $state) => DocumentCategory::KELOMPOK[$state] ?? '-'),
            TextEntry::make('company.name')->label('Perusahaan')->placeholder('Semua perusahaan'),
            IconEntry::make('is_active')->label('Aktif')->boolean(),
            TextEntry::make('documents_count')->label('Jumlah dokumen'),
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
