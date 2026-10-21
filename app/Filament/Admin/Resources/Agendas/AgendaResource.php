<?php

namespace App\Filament\Admin\Resources\Agendas;

use App\Filament\Admin\Resources\Agendas\Pages\CreateAgenda;
use App\Filament\Admin\Resources\Agendas\Pages\EditAgenda;
use App\Filament\Admin\Resources\Agendas\Pages\ListAgendas;
use App\Filament\Admin\Resources\Agendas\Pages\ViewAgenda;
use App\Filament\Admin\Resources\Agendas\Schemas\AgendaForm;
use App\Filament\Admin\Resources\Agendas\Schemas\AgendaInfolist;
use App\Models\Agenda;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Kegiatan / agenda unit: dicatat, bisa memakai ruangan, opsional punya surat
 * pemberitahuan bernomor, dan bisa diumumkan (aplikasi, API integrasi, grup WhatsApp).
 */
class AgendaResource extends Resource
{
    protected static ?string $model = Agenda::class;

    protected static ?string $slug = 'kegiatan';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Kegiatan';

    protected static ?string $modelLabel = 'Kegiatan';

    protected static ?string $pluralModelLabel = 'Kegiatan';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->access()
            ->with(['unit', 'company', 'meetingLocation', 'letterNumber', 'creator']);
    }

    public static function form(Schema $schema): Schema
    {
        return AgendaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AgendaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_time')
            ->columns([
                TextColumn::make('title')
                    ->label('Kegiatan')
                    // Desktop: judul + jenis; mobile: kartu ringkas (kolom lain disembunyikan di mobile)
                    ->view('filament.tables.columns.agenda-card')
                    ->searchable()
                    ->grow(),
                TextColumn::make('date_time')
                    ->label('Waktu')
                    ->formatStateUsing(fn (Agenda $record): string => $record->date_time->locale('id')->isoFormat('ddd, D MMM YYYY'))
                    ->description(fn (Agenda $record): string => $record->date_time->format('H.i').'–'.$record->effectiveEndTime()->format('H.i'))
                    ->sortable(),
                TextColumn::make('place')
                    ->label('Tempat')
                    ->state(fn (Agenda $record): string => $record->placeName())
                    ->icon('heroicon-m-map-pin')
                    ->visibleFrom('md'),
                TextColumn::make('unit.name')
                    ->label('Penyelenggara')
                    ->placeholder('—')
                    ->visibleFrom('lg'),
                TextColumn::make('letterNumber.nomor')
                    ->label('Surat')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->visibleFrom('xl'),
                IconColumn::make('announced_at')
                    ->label('Diumumkan')
                    ->state(fn (Agenda $record): bool => $record->isAnnounced())
                    ->boolean()
                    ->tooltip(fn (Agenda $record): ?string => $record->announced_at
                        ? 'Diumumkan '.$record->announced_at->locale('id')->diffForHumans()
                            .($record->whatsapp_sent_at ? ' · terkirim ke WhatsApp' : '')
                        : 'Belum diumumkan')
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ['scheduled' => 'Terjadwal', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$state] ?? $state)
                    ->color(fn (string $state): string => ['scheduled' => 'info', 'completed' => 'gray', 'cancelled' => 'danger'][$state] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('kategori')->label('Jenis kegiatan')->options(Agenda::KATEGORI)->multiple()->native(false),
                SelectFilter::make('unit_id')->label('Penyelenggara')->relationship('unit', 'name')->searchable()->preload()->native(false),
                SelectFilter::make('meeting_location_id')->label('Ruangan')->relationship('meetingLocation', 'name')->preload()->native(false),
                SelectFilter::make('audience')->label('Jangkauan')->options(Agenda::audienceOptions())->native(false),
                TernaryFilter::make('announced')
                    ->label('Pengumuman')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah diumumkan')
                    ->falseLabel('Belum diumumkan')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('announced_at'),
                        false: fn (Builder $query) => $query->whereNull('announced_at'),
                    )
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->emptyStateIcon('heroicon-o-megaphone')
            ->emptyStateHeading('Belum ada kegiatan')
            ->emptyStateDescription('Catat kegiatan unit — pelatihan, sosialisasi, apel, bakti sosial — lalu umumkan ke karyawan.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgendas::route('/'),
            'create' => CreateAgenda::route('/create'),
            'view' => ViewAgenda::route('/{record}'),
            'edit' => EditAgenda::route('/{record}/edit'),
        ];
    }
}
