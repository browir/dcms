<?php

namespace App\Filament\Admin\Resources\IncomingLetters;

use App\Filament\Admin\Resources\IncomingLetters\Pages\CreateIncomingLetter;
use App\Filament\Admin\Resources\IncomingLetters\Pages\EditIncomingLetter;
use App\Filament\Admin\Resources\IncomingLetters\Pages\ListIncomingLetters;
use App\Filament\Admin\Resources\IncomingLetters\Pages\ViewIncomingLetter;
use App\Models\IncomingLetter;
use App\Models\Unit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/** Surat Masuk: surat dari pihak luar, dicatat dengan nomor agenda dan diteruskan ke unit tujuan. */
class IncomingLetterResource extends Resource
{
    protected static ?string $model = IncomingLetter::class;

    protected static ?string $slug = 'surat-masuk';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Dokumen';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Surat Masuk';

    protected static ?string $modelLabel = 'Surat Masuk';

    protected static ?string $pluralModelLabel = 'Surat Masuk';

    protected static ?string $recordTitleAttribute = 'perihal';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->access()->with(['unit:id,name', 'department:id,name', 'creator:id,name']);
    }

    /** Surat baru untuk unit user yang belum dibaca. */
    public static function getNavigationBadge(): ?string
    {
        $unitId = Auth::user()?->unit_id;
        $count = $unitId ? IncomingLetter::query()->access()->where('unit_id', $unitId)->where('status', IncomingLetter::STATUS_BARU)->count() : 0;

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Surat masuk untuk unit Anda yang belum dibaca';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Surat')
                ->description('Data sesuai surat dari pengirim')
                ->icon('heroicon-o-envelope')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('pengirim')
                        ->label('Pengirim')
                        ->placeholder('Instansi / perusahaan / perorangan')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('nomor_surat')
                        ->label('Nomor Surat Pengirim')
                        ->maxLength(255),
                    DatePicker::make('tanggal_surat')
                        ->label('Tanggal Surat')
                        ->native(false)
                        ->displayFormat('d M Y'),
                    TextInput::make('perihal')
                        ->label('Perihal')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    ToggleButtons::make('sifat')
                        ->label('Sifat')
                        ->options(IncomingLetter::SIFAT)
                        ->colors(['biasa' => 'gray', 'penting' => 'info', 'segera' => 'warning', 'rahasia' => 'danger'])
                        ->default('biasa')
                        ->inline()
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('ringkasan')
                        ->label('Ringkasan Isi')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Penerimaan')
                ->description('Nomor agenda terbit otomatis saat disimpan')
                ->icon('heroicon-o-inbox-arrow-down')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('tanggal_terima')
                        ->label('Tanggal Diterima')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->default(fn () => today())
                        ->required()
                        ->disabledOn('edit'),
                    Select::make('unit_id')
                        ->label('Ditujukan ke Unit')
                        ->helperText('Anggota unit mendapat notifikasi surat ini.')
                        ->options(fn (): array => self::unitOptions())
                        ->searchable()
                        ->native(false)
                        ->required(),
                    FileUpload::make('file_path')
                        ->label('Scan Surat')
                        ->disk('documents')
                        ->directory('surat-masuk')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->helperText('PDF/JPG/PNG, maks. 10 MB. Hanya bisa dibuka user yang berhak melihat surat ini.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('nomor_agenda')->label('Nomor Agenda')->weight('bold')->copyable()->fontFamily('mono'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->formatStateUsing(fn (string $state): string => IncomingLetter::STATUS[$state] ?? $state)
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('sifat')->label('Sifat')->badge()
                        ->formatStateUsing(fn (string $state): string => IncomingLetter::SIFAT[$state] ?? $state)
                        ->color(fn (string $state): string => self::sifatColor($state)),
                    TextEntry::make('pengirim')->label('Pengirim'),
                    TextEntry::make('nomor_surat')->label('Nomor Surat Pengirim')->placeholder('—'),
                    TextEntry::make('tanggal_surat')->label('Tanggal Surat')->date('d M Y')->placeholder('—'),
                    TextEntry::make('perihal')->label('Perihal')->columnSpanFull(),
                    TextEntry::make('ringkasan')->label('Ringkasan Isi')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('tanggal_terima')->label('Diterima')->date('d M Y'),
                    TextEntry::make('unit.name')->label('Unit Tujuan')->placeholder('—'),
                    TextEntry::make('creator.name')->label('Dicatat oleh')->placeholder('—'),
                    TextEntry::make('read_at')->label('Dibaca')
                        ->formatStateUsing(fn ($state, IncomingLetter $record): string => $state->translatedFormat('d M Y H:i').($record->reader ? ' · '.$record->reader->name : ''))
                        ->placeholder('Belum dibaca'),
                    TextEntry::make('completed_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_terima', 'desc')
            ->columns([
                TextColumn::make('perihal')
                    ->label('Surat')
                    ->view('filament.tables.columns.incoming-letter-card')
                    ->searchable(['perihal', 'pengirim', 'nomor_surat', 'nomor_agenda'])
                    ->grow(),
                TextColumn::make('pengirim')
                    ->label('Pengirim')
                    ->description(fn (IncomingLetter $record): ?string => $record->nomor_surat)
                    ->wrap()
                    ->visibleFrom('lg'),
                TextColumn::make('unit.name')
                    ->label('Unit Tujuan')
                    ->placeholder('—')
                    ->visibleFrom('md'),
                TextColumn::make('tanggal_terima')
                    ->label('Diterima')
                    ->date('d M Y')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('sifat')
                    ->label('Sifat')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => IncomingLetter::SIFAT[$state] ?? $state)
                    ->color(fn (string $state): string => self::sifatColor($state))
                    ->visibleFrom('xl'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => IncomingLetter::STATUS[$state] ?? $state)
                    ->color(fn (string $state): string => self::statusColor($state))
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(IncomingLetter::STATUS)->multiple(),
                SelectFilter::make('sifat')->label('Sifat')->options(IncomingLetter::SIFAT)->multiple(),
                SelectFilter::make('unit_id')->label('Unit Tujuan')->relationship('unit', 'name')->searchable()->preload(),
                Filter::make('periode')
                    ->schema([
                        DatePicker::make('dari')->label('Diterima dari')->native(false),
                        DatePicker::make('sampai')->label('Sampai')->native(false),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal_terima', '>=', $date))
                        ->when($data['sampai'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal_terima', '<=', $date))),
            ])
            ->recordUrl(fn (IncomingLetter $record): string => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('file')
                        ->label('Buka Scan')
                        ->icon('heroicon-m-paper-clip')
                        ->visible(fn (IncomingLetter $record): bool => filled($record->file_path))
                        ->url(fn (IncomingLetter $record): string => route('surat-masuk.file', $record), shouldOpenInNewTab: true),
                    EditAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada surat masuk')
            ->emptyStateDescription('Catat surat dari pihak luar, lalu teruskan ke unit tujuan.');
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            IncomingLetter::STATUS_BARU => 'warning',
            IncomingLetter::STATUS_DIBACA => 'info',
            default => 'success',
        };
    }

    public static function sifatColor(string $state): string
    {
        return match ($state) {
            'rahasia' => 'danger',
            'segera' => 'warning',
            'penting' => 'info',
            default => 'gray',
        };
    }

    /** Unit aktif di perusahaan user (semua perusahaan untuk admin global). */
    private static function unitOptions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $all = $user?->hasRole('super_admin') || $user?->can('view_all_companies_data');

        return Unit::query()
            ->with('department.company')
            ->where('is_active', true)
            ->whereHas('department', fn (Builder $q) => $q->when(! $all, fn (Builder $d) => $d->where('company_id', $user?->company_id)))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Unit $unit) => [$unit->id => $unit->name.' — '.($unit->department?->name ?? '')])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomingLetters::route('/'),
            'create' => CreateIncomingLetter::route('/create'),
            'view' => ViewIncomingLetter::route('/{record}'),
            'edit' => EditIncomingLetter::route('/{record}/edit'),
        ];
    }
}
