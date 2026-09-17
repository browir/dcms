<?php

namespace App\Filament\Admin\Resources;

use App\Models\Meeting;
use App\Models\MeetingLocation;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class MeetingLocationResource extends Resource
{
    protected static ?string $model = MeetingLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Rapat';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Lokasi Rapat';

    protected static ?string $modelLabel = 'Lokasi Rapat';

    protected static ?string $pluralModelLabel = 'Lokasi Rapat';

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'kabid', 'direktur']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Lokasi')
                ->required()
                ->maxLength(255)
                ->placeholder('Contoh: Ruang Meeting A, Aula Lantai 2'),

            TextInput::make('address')
                ->label('Alamat / Keterangan')
                ->maxLength(255)
                ->placeholder('Contoh: Gedung Utama Lt. 3')
                ->nullable(),

            TextInput::make('capacity')
                ->label('Kapasitas (Orang)')
                ->numeric()
                ->minValue(1)
                ->nullable()
                ->placeholder('Contoh: 20'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile card
                TextColumn::make('name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.meeting-location-mobile-card')
                    ->grow(),

                // Desktop columns
                TextColumn::make('address')
                    ->label('Alamat / Keterangan')
                    ->placeholder('—')
                    ->limit(50)
                    ->visibleFrom('md'),

                TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->placeholder('—')
                    ->suffix(' orang')
                    ->alignCenter()
                    ->width('100px')
                    ->visibleFrom('md'),

                \Filament\Tables\Columns\TextColumn::make('status_ruangan')
                    ->label('Status')
                    ->badge()
                    ->state(function (MeetingLocation $record, $livewire): string {
                        return static::resolveMeetingForRow($record, $livewire) ? 'Sedang Dipakai' : 'Tersedia';
                    })
                    ->color(function (MeetingLocation $record, $livewire): string {
                        return static::resolveMeetingForRow($record, $livewire) ? 'danger' : 'success';
                    })
                    ->icon(function (MeetingLocation $record, $livewire): string {
                        return static::resolveMeetingForRow($record, $livewire)
                            ? 'heroicon-o-lock-closed'
                            : 'heroicon-o-check-circle';
                    })
                    ->tooltip(function (MeetingLocation $record, $livewire): ?string {
                        $meeting = static::resolveMeetingForRow($record, $livewire);

                        return $meeting ? $meeting->title : null;
                    })
                    ->visibleFrom('md'),

                \Filament\Tables\Columns\TextColumn::make('jam_mulai')
                    ->label('Mulai')
                    ->icon('heroicon-o-play-circle')
                    ->iconColor('info')
                    ->state(function (MeetingLocation $record, $livewire): string {
                        $meeting = static::resolveMeetingForRow($record, $livewire);

                        return $meeting ? $meeting->date_time->format('H:i') : '—';
                    })
                    ->color(fn (MeetingLocation $record, $livewire) => static::resolveMeetingForRow($record, $livewire) ? 'info' : 'gray')
                    ->alignCenter()
                    ->visibleFrom('md'),

                \Filament\Tables\Columns\TextColumn::make('jam_berakhir')
                    ->label('Berakhir')
                    ->icon('heroicon-o-stop-circle')
                    ->iconColor('info')
                    ->state(function (MeetingLocation $record, $livewire): string {
                        $meeting = static::resolveMeetingForRow($record, $livewire);
                        if (! $meeting) {
                            return '—';
                        }

                        return $meeting->end_time
                            ? $meeting->end_time->format('H:i')
                            : '—';
                    })
                    ->color(fn (MeetingLocation $record, $livewire) => static::resolveMeetingForRow($record, $livewire) ? 'info' : 'gray')
                    ->alignCenter()
                    ->visibleFrom('md'),

                TextColumn::make('meetings_count')
                    ->label('Total Rapat')
                    ->counts('meetings')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->width('100px')
                    ->visibleFrom('md'),
            ])
            ->filters([
                \Filament\Tables\Filters\Filter::make('available')
                    ->label('Tersedia Sekarang')
                    ->query(fn ($query) => $query->available()),

                \Filament\Tables\Filters\Filter::make('occupied')
                    ->label('Sedang Dipakai Sekarang')
                    ->query(fn ($query) => $query->occupied()),

                Filter::make('monitor')
                    ->label('Cek Ketersediaan pada Tanggal & Jam')
                    ->form([
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        TimePicker::make('jam_mulai')
                            ->label('Jam Mulai')
                            ->seconds(false),

                        TimePicker::make('jam_selesai')
                            ->label('Jam Selesai (opsional)')
                            ->seconds(false)
                            ->after('jam_mulai'),

                        Select::make('status')
                            ->label('Tampilkan')
                            ->placeholder('Semua Lokasi')
                            ->options([
                                'available' => 'Hanya yang Tersedia',
                                'occupied' => 'Hanya yang Terpakai',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $window = static::windowFromFilterData($data);

                        if (! $window || blank($data['status'] ?? null)) {
                            return $query;
                        }

                        $booked = Meeting::bookedLocationKeys($window['start'], $window['end']);
                        $ids = $booked['ids'];
                        $names = $booked['names'];

                        $matchesBooked = fn (Builder $q) => $q
                            ->whereIn('id', $ids)
                            ->orWhereIn(DB::raw('LOWER(name)'), $names);

                        return $data['status'] === 'occupied'
                            ? $query->where($matchesBooked)
                            : $query->where(fn (Builder $q) => $q->whereNot($matchesBooked));
                    })
                    ->indicateUsing(function (array $data): array {
                        $window = static::windowFromFilterData($data);

                        if (! $window) {
                            return [];
                        }

                        $label = 'Memantau '.$window['start']->translatedFormat('d M Y, H:i');

                        if ($window['end']) {
                            $label .= '–'.$window['end']->format('H:i');
                        }

                        if (filled($data['status'] ?? null)) {
                            $label .= ' · '.($data['status'] === 'occupied' ? 'Terpakai' : 'Tersedia');
                        }

                        return [Indicator::make($label)];
                    }),
            ])
            ->recordActions([
                EditAction::make()->button()->outlined()->size('xs'),
                DeleteAction::make()->button()->outlined()->size('xs'),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('Belum ada lokasi rapat')
            ->emptyStateDescription('Tambahkan lokasi rapat yang sering digunakan.')
            ->striped()
            ->poll('60s'); // refresh otomatis setiap 60 detik
    }

    /**
     * Baca state filter 'monitor' dari tabel Livewire dan ubah menjadi rentang waktu.
     * Null jika filter belum diisi (tanggal + jam mulai wajib) — artinya pantau status "sekarang".
     */
    protected static function resolveMonitorWindow($livewire): ?array
    {
        if (! $livewire || ! method_exists($livewire, 'getTableFilterState')) {
            return null;
        }

        return static::windowFromFilterData($livewire->getTableFilterState('monitor') ?? []);
    }

    protected static function windowFromFilterData(array $data): ?array
    {
        if (blank($data['tanggal'] ?? null) || blank($data['jam_mulai'] ?? null)) {
            return null;
        }

        // DatePicker::native(false) menyimpan state sebagai datetime penuh (mis. "2026-09-17 00:00:00"),
        // bukan cuma "Y-m-d". Ambil bagian tanggalnya saja agar tidak bentrok dengan string jam
        // yang ditempel setelahnya (concat mentah bisa menghasilkan "double time specification").
        $tanggal = Carbon::parse($data['tanggal'])->format('Y-m-d');

        $start = Carbon::parse($tanggal.' '.$data['jam_mulai']);
        $end = filled($data['jam_selesai'] ?? null)
            ? Carbon::parse($tanggal.' '.$data['jam_selesai'])
            : null;

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Rapat yang relevan untuk baris lokasi ini: pada jendela waktu yang dipantau (jika filter
     * 'monitor' diisi), atau rapat yang sedang berlangsung "sekarang" (perilaku default/live).
     */
    protected static function resolveMeetingForRow(MeetingLocation $record, $livewire): ?Meeting
    {
        $window = static::resolveMonitorWindow($livewire);

        return $window
            ? $record->meetingAt($window['start'], $window['end'])
            : $record->getCurrentMeeting();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\MeetingLocationResource\Pages\ListMeetingLocations::route('/'),
        ];
    }
}
