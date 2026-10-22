<?php

namespace App\Filament\Admin\Resources\LetterNumbers;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\Agendas\AgendaResource;
use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\LetterNumbers\Pages\ListLetterNumbers;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Register nomor dokumen: nomor diambil dulu di sini, lalu dokumen resminya diisi ke nomor itu.
 */
class LetterNumberResource extends Resource
{
    protected static ?string $model = LetterNumber::class;

    protected static ?string $slug = 'nomor-dokumen';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Dokumen';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Nomor Dokumen';

    protected static ?string $modelLabel = 'Nomor Dokumen';

    protected static ?string $pluralModelLabel = 'Nomor Dokumen';

    protected static ?string $recordTitleAttribute = 'nomor';

    public static function getNavigationBadge(): ?string
    {
        $count = LetterNumber::query()->access()->available()
            ->where('created_by', auth()->id())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Nomor dokumen Anda yang belum diisi dokumen';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->access()->with(['unit', 'creator', 'document', 'agenda', 'category']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('tanggal_surat')->orderByDesc('nomor_urut'))
            ->columns([
                TextColumn::make('nomor')
                    ->label('Nomor Dokumen')
                    // Desktop: nomor; mobile: kartu ringkas (kolom lain disembunyikan di mobile)
                    ->view('filament.tables.columns.letter-number-card')
                    ->searchable(),

                TextColumn::make('jenis_surat')
                    ->label('Kategori')
                    ->formatStateUsing(fn (JenisSurat $state): HtmlString => self::jenisBadge($state))
                    ->html()
                    ->tooltip(fn (LetterNumber $record): string => $record->category?->name ?? $record->jenis_surat->label())
                    ->visibleFrom('md'),

                TextColumn::make('judul_surat')
                    ->label('Judul / Perihal')
                    ->grow()
                    ->description(fn (LetterNumber $record): ?string => $record->perihal)
                    ->searchable(['judul_surat', 'perihal'])
                    ->wrap(),

                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->visibleFrom('2xl'),

                TextColumn::make('tanggal_surat')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('creator.name')
                    ->label('Diambil oleh')
                    ->placeholder('—')
                    ->visibleFrom('2xl'),

                TextColumn::make('status')
                    ->label('Dokumen')
                    ->state(fn (LetterNumber $record): string => match (true) {
                        $record->document !== null => 'Sudah diisi',
                        $record->agenda !== null => 'Surat kegiatan',
                        default => 'Belum diisi',
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Belum diisi' ? 'warning' : 'success')
                    ->icon(fn (string $state): string => match ($state) {
                        'Sudah diisi' => 'heroicon-m-check-circle',
                        'Surat kegiatan' => 'heroicon-m-megaphone',
                        default => 'heroicon-m-clock',
                    }),
            ])
            ->filters([
                TernaryFilter::make('terpakai')
                    ->label('Status dokumen')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah diisi')
                    ->falseLabel('Belum diisi')
                    ->queries(
                        true: fn (Builder $q) => $q->used(),
                        false: fn (Builder $q) => $q->available(),
                        blank: fn (Builder $q) => $q,
                    ),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name', fn (Builder $query) => $query->whereNotNull('code')->orderBy('code'))
                    ->getOptionLabelFromRecordUsing(fn (DocumentCategory $record): string => $record->option_label)
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('kelompok')
                    ->label('Kelompok')
                    ->options(DocumentCategory::KELOMPOK)
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'], fn (Builder $q, string $kelompok) => $q->whereHas('category', fn (Builder $c) => $c->where('kelompok', $kelompok)))),
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => collect(range(now()->year, now()->year - 4))->mapWithKeys(fn ($y) => [$y => $y])->all())
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'], fn ($q, $year) => $q->whereYear('tanggal_surat', $year))),
            ])
            ->recordActions([
                Action::make('isiDokumen')
                    ->label('Isi Dokumen')
                    ->icon('heroicon-m-document-plus')
                    ->color('primary')
                    ->button()
                    ->visible(fn (LetterNumber $record): bool => ! $record->document && ! $record->agenda && (auth()->user()?->can('create', Document::class) ?? false))
                    ->url(fn (LetterNumber $record): string => DocumentResource::getUrl('create', ['nomor_surat' => $record->id])),
                Action::make('lihatDokumen')
                    ->label('Lihat Dokumen')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->visible(fn (LetterNumber $record): bool => $record->document !== null && $record->document->trashed() === false)
                    ->url(fn (LetterNumber $record): string => DocumentResource::getUrl('view', ['record' => $record->document])),
                Action::make('lihatKegiatan')
                    ->label('Lihat Kegiatan')
                    ->icon('heroicon-m-megaphone')
                    ->color('gray')
                    ->visible(fn (LetterNumber $record): bool => $record->agenda !== null)
                    ->url(fn (LetterNumber $record): string => AgendaResource::getUrl('view', ['record' => $record->agenda])),
            ])
            ->emptyStateHeading('Belum ada nomor dokumen')
            ->emptyStateDescription('Ambil nomor dokumen dulu, lalu isi dokumen resminya ke nomor tersebut.');
    }

    public static function jenisBadge(JenisSurat $jenis): HtmlString
    {
        ['bg' => $bg, 'text' => $text] = $jenis->warna();

        return new HtmlString(sprintf(
            '<span class="dcms-jenis-badge" style="--jb-bg:%s;--jb-text:%s" title="%s">%s</span>',
            $bg,
            $text,
            e($jenis->label()),
            e($jenis->value),
        ));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLetterNumbers::route('/'),
        ];
    }
}
