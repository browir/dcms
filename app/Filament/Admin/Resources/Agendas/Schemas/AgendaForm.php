<?php

namespace App\Filament\Admin\Resources\Agendas\Schemas;

use App\Enum\JenisSurat;
use App\Models\Agenda;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\Unit;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AgendaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Detail Kegiatan')
                ->icon('heroicon-o-megaphone')
                ->iconColor('primary')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Nama kegiatan')
                        ->placeholder('Contoh: Pelatihan Pengelolaan Arsip')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('kategori')
                        ->label('Jenis kegiatan')
                        ->options(Agenda::KATEGORI)
                        ->native(false),
                    Select::make('unit_id')
                        ->label('Unit penyelenggara')
                        ->helperText('Perusahaan & departemen mengikuti unit. Nomor surat juga mengikuti unit ini.')
                        ->options(fn (): array => self::unitOptions())
                        ->default(fn (): ?int => Auth::user()?->unit_id)
                        ->searchable()
                        ->native(false)
                        ->required(),
                    Textarea::make('description')
                        ->label('Keterangan')
                        ->placeholder('Peserta, perlengkapan yang dibawa, dress code, dll.')
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('status')
                        ->label('Status')
                        ->options(['scheduled' => 'Terjadwal', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'])
                        ->default('scheduled')
                        ->native(false)
                        ->required()
                        ->hiddenOn('create'),
                ]),

            Section::make('Waktu & Tempat')
                ->description('Ruangan yang dipakai rapat atau kegiatan lain pada jam yang sama tidak bisa dipilih.')
                ->icon('heroicon-o-calendar-days')
                ->iconColor('info')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('date_time')
                        ->label('Mulai')
                        ->native(false)
                        ->seconds(false)
                        ->displayFormat('d M Y H:i')
                        ->live(onBlur: true)
                        ->required(),
                    DateTimePicker::make('end_time')
                        ->label('Selesai')
                        ->native(false)
                        ->seconds(false)
                        ->displayFormat('d M Y H:i')
                        ->after('date_time')
                        ->live(onBlur: true)
                        ->required(),
                    Select::make('meeting_location_id')
                        ->label('Ruangan')
                        ->options(fn (): array => MeetingLocation::query()->orderBy('name')->get()
                            ->mapWithKeys(fn (MeetingLocation $l) => [$l->id => $l->name.($l->capacity ? " ({$l->capacity} orang)" : '')])
                            ->all())
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->rules([fn (Get $get, ?Agenda $record): Closure => self::roomAvailableRule($get, $record)]),
                    TextInput::make('location')
                        ->label('Lokasi lain')
                        ->placeholder('Isi jika di luar ruangan terdaftar, mis. Lapangan Parkir')
                        ->maxLength(255)
                        ->requiredWithout('meeting_location_id')
                        ->validationMessages(['required_without' => 'Pilih ruangan atau isi lokasi lain.']),
                ]),

            Section::make('Pengumuman')
                ->description('Siapa yang diberi tahu, dan apakah perlu surat pemberitahuan resmi.')
                ->icon('heroicon-o-bell-alert')
                ->iconColor('warning')
                ->schema([
                    ToggleButtons::make('audience')
                        ->label('Jangkauan pengumuman')
                        ->options(Agenda::audienceOptions())
                        ->icons([
                            Agenda::AUDIENCE_UNIT => 'heroicon-m-user-group',
                            Agenda::AUDIENCE_COMPANY => 'heroicon-m-building-office',
                            Agenda::AUDIENCE_ALL => 'heroicon-m-globe-asia-australia',
                        ])
                        ->default(Agenda::AUDIENCE_UNIT)
                        ->inline()
                        ->required(),

                    Toggle::make('buat_surat')
                        ->label('Buat surat pemberitahuan (ambil nomor surat)')
                        ->helperText('Nomor diambil dari register Nomor Surat saat disimpan. Surat bisa dicetak sebagai PDF.')
                        ->live()
                        ->dehydrated(false)
                        ->visibleOn('create'),
                    Select::make('jenis_surat')
                        ->label('Jenis surat')
                        ->options(JenisSurat::options())
                        ->default(JenisSurat::EDARAN_PENGUMUMAN->value)
                        ->native(false)
                        ->searchable()
                        ->dehydrated(false)
                        ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && (bool) $get('buat_surat')),
                    Textarea::make('notice_body')
                        ->label('Isi surat')
                        ->helperText('Kosongkan untuk memakai teks standar dari detail kegiatan. Kop, nomor, perihal, dan tanda tangan ditambahkan otomatis.')
                        ->rows(10)
                        ->visible(fn (Get $get, ?Agenda $record): bool => (bool) $get('buat_surat') || (bool) $record?->letter_number_id),

                    Toggle::make('umumkan_aplikasi')
                        ->label('Langsung umumkan di aplikasi setelah disimpan')
                        ->dehydrated(false)
                        ->visibleOn('create'),
                    Toggle::make('umumkan_whatsapp')
                        ->label('Langsung kirim ke grup WhatsApp setelah disimpan')
                        ->dehydrated(false)
                        ->visibleOn('create'),
                ]),
        ]);
    }

    /** Ruangan tidak boleh bentrok dengan rapat maupun kegiatan lain. */
    private static function roomAvailableRule(Get $get, ?Agenda $record): Closure
    {
        return function (string $attribute, $value, Closure $fail) use ($get, $record): void {
            if (blank($value) || blank($get('date_time'))) {
                return;
            }

            $start = Carbon::parse($get('date_time'));
            $end = filled($get('end_time')) ? Carbon::parse($get('end_time')) : null;
            $room = MeetingLocation::query()->find($value);

            $conflict = Meeting::locationConflict((int) $value, $room?->name, $start, $end, excludeAgendaId: $record?->getKey());

            if ($conflict) {
                $fail(sprintf(
                    'Ruangan "%s" sudah dipakai %s pada %s–%s. Pilih ruangan lain atau ubah jadwal.',
                    $room?->name,
                    $conflict->bookingLabel(),
                    $conflict->date_time->format('d M Y H:i'),
                    $conflict->effectiveEndTime()->format('H:i'),
                ));
            }
        };
    }

    /** Unit yang boleh menjadi penyelenggara: semua (admin global) atau unit di perusahaan user. */
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
            ->mapWithKeys(fn (Unit $unit) => [$unit->id => $unit->name.' — '.($unit->department?->name ?? '-')])
            ->all();
    }
}
