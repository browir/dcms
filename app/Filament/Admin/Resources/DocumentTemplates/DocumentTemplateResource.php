<?php

namespace App\Filament\Admin\Resources\DocumentTemplates;

use App\Filament\Admin\Resources\DocumentTemplates\Pages\ManageDocumentTemplates;
use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class DocumentTemplateResource extends Resource
{
    protected static ?string $model = DocumentTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Template Surat / Dokumen';

    protected static ?string $modelLabel = 'Template Surat';

    protected static ?string $pluralModelLabel = 'Template Surat / Dokumen';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama Template')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->label('Kode Template')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('Contoh: RSUSM-SE'),

                Select::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->required()
                    ->live(),

                Select::make('category_id')
                    ->label('Kategori Dokumen')
                    ->options(function (callable $get) {
                        $companyId = $get('company_id');

                        return DocumentCategory::query()
                            ->when($companyId, fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable(),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->columnSpanFull(),

                RichEditor::make('template_content')
                    ->label('Format Konten Template')
                    ->helperText('Gunakan placeholder: {nomor_surat}, {tanggal}, {nama_direktur}, {nip_direktur}')
                    ->columnSpanFull()
                    ->required(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn (DocumentTemplate $record) => $record->name)
                    ->modalWidth('4xl')
                    ->infolist([
                        TextEntry::make('name')->label('Nama Template'),
                        TextEntry::make('code')->label('Kode Template')->badge(),
                        TextEntry::make('company.name')->label('Perusahaan'),
                        TextEntry::make('category.name')->label('Kategori Dokumen'),
                        TextEntry::make('template_content')
                            ->label('Pratinjau Format Surat')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                EditAction::make()->modalWidth('4xl'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocumentTemplates::route('/'),
        ];
    }
}
