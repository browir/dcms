<?php

namespace App\Filament\Admin\Resources\Meetings\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingForm
{
    /**
     * Cache per-request: daftar nama lokasi (huruf kecil) yang sudah dipesan,
     * dikunci berdasarkan rentang waktu + rapat yang dikecualikan.
     *
     * @var array<string, string[]>
     */
    protected static array $bookedLocationMemo = [];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Rapat')
                ->description('Judul dan agenda pembahasan rapat.')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Rapat')
                        ->required()
                        ->maxLength(255)
                        ->autofocus()
                        ->prefixIcon('heroicon-m-pencil-square')
                        ->placeholder('Contoh: Rapat Koordinasi Bulanan')
                        ->columnSpanFull(),
                    TextInput::make('doc_number')
                        ->label('No. Dokumen')
                        ->maxLength(255)
                        ->prefixIcon('heroicon-m-hashtag')
                        ->placeholder('Contoh: 003/H.2/SGG/VIII/2025')
                        ->hiddenOn('create'),
                    Textarea::make('agenda')
                        ->label('Agenda')
                        ->rows(3)
                        ->placeholder('Tuliskan poin-poin agenda yang akan dibahas...')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Hasil & Dokumentasi Rapat')
                ->description('Notulensi, berkas hasil rapat, dan lampiran dokumentasi. Bagian ini muncul setelah rapat dibuat.')
                ->icon('heroicon-o-document-check')
                ->columns(2)
                ->collapsible()
                ->hiddenOn('create')
                ->schema([
                    Select::make('mode_notulen')
                        ->hiddenOn('create')
                        ->label('Metode Notulensi')
                        ->native(false)
                        ->prefixIcon('heroicon-m-clipboard-document-list')
                        ->helperText('Pilih "Template" untuk mencatat langsung, atau "Upload" bila notulensi sudah berupa file.')
                        ->options([
                            'template' => 'Gunakan Template',
                            'upload' => 'Upload File PDF',
                        ])
                        ->default('template')
                        ->live()
                        ->default(function ($record) {
                            if (! $record) {
                                return 'template';
                            } // saat create

                            // Cek content
                            $contentText = trim(strip_tags($record->content));

                            if ($contentText !== '') {
                                return 'template'; // ada isi → template
                            }

                            // content kosong + file_path ada → upload
                            if (! empty($record->file_path)) {
                                return 'upload';
                            }

                            // dua-duanya kosong → template
                            return 'template';
                        })
                        ->afterStateHydrated(function ($set, $record) {
                            if (! $record) {
                                // Saat Create → default template
                                $set('mode_notulen', 'template');

                                return;
                            }

                            // Cek content
                            $contentText = trim(strip_tags($record->content));
                            if ($contentText !== '') {
                                $set('mode_notulen', 'template');

                                return;
                            }

                            // Cek file_path
                            if (! empty($record->file_path)) {
                                $set('mode_notulen', 'upload');

                                return;
                            }

                            // Dua-duanya kosong → template
                            $set('mode_notulen', 'template');
                        })
                        ->required(),

                    // TAMPIL JIKA PILIH TEMPLATE
                    RichEditor::make('content')
                        ->label('Content / Notulensi')
                        ->columnSpanFull()
                        ->visible(fn (string $operation, $get) => $operation !== 'create' && $get('mode_notulen') === 'template')
                        ->afterStateHydrated(function ($set, $state, $record) {
                            // Saat CREATE → state kosong, jangan isi apa pun
                            if (! $record) {
                                return;
                            }

                            // Cek apakah content SUDAH ada isi
                            $plain = trim(strip_tags($state));

                            if ($plain !== '') {
                                // Sudah ada isi → jangan ganti, tampilkan apa adanya
                                return;
                            }

                            // Content kosong → generate template otomatis
                            $participantNames = $record->participants->pluck('name')->join(', ');

                            $set(
                                'content',
                                "
            <table width='100%' border='1' style='border-collapse: collapse;'>
                <thead>
                    <tr style='background-color: #f2f2f2;'>
                        <th style='width: 30px;'>NO</th>
                        <th style='width: 180px;'>PEMBAHASAN</th>
                        <th>ACTION PLAN</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style='text-align: center;'>1</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        "
                            );
                        }),

                    // ACTION PLAN & PIC — terpisah dari rich editor agar PIC bisa dipilih
                    // langsung dari data User (bukan teks bebas) dan bisa dikirimi notifikasi.
                    Repeater::make('action_items')
                        ->label('Action Plan & PIC')
                        ->helperText('Tambahkan action plan hasil rapat beserta PIC (penanggung jawab). PIC yang dipilih akan menerima notifikasi setelah disimpan.')
                        ->visible(fn (string $operation, $get) => $operation !== 'create' && $get('mode_notulen') === 'template')
                        ->columnSpanFull()
                        ->addActionLabel('Tambah Action Plan')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => filled($state['action_plan'] ?? null)
                            ? \Illuminate\Support\Str::limit($state['action_plan'], 60)
                            : 'Action Plan Baru')
                        ->schema([
                            Textarea::make('pembahasan')
                                ->label('Pembahasan')
                                ->rows(2)
                                ->columnSpanFull(),
                            Textarea::make('action_plan')
                                ->label('Action Plan')
                                ->rows(2)
                                ->columnSpanFull(),
                            Select::make('pic_ids')
                                ->label('PIC (Penanggung Jawab)')
                                ->helperText('Peserta rapat ditampilkan lebih dulu. Gunakan pencarian untuk memilih user lain di luar peserta.')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->columnSpanFull()
                                ->prefixIcon('heroicon-m-user')
                                ->options(function ($get) {
                                    $participantIds = (array) $get('../../participants');

                                    if (empty($participantIds)) {
                                        return [];
                                    }

                                    return User::with(['company', 'department', 'unit'])
                                        ->whereIn('id', $participantIds)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn ($u) => [$u->id => self::picOptionLabel($u)]);
                                })
                                ->getSearchResultsUsing(function (string $search, $get) {
                                    $participantIds = (array) $get('../../participants');

                                    return User::with(['company', 'department', 'unit'])
                                        ->active()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orderBy('name')
                                        ->limit(50)
                                        ->get()
                                        // Peserta rapat diprioritaskan tampil di atas hasil pencarian global lainnya.
                                        ->sortByDesc(fn ($u) => in_array($u->id, $participantIds, true))
                                        ->mapWithKeys(fn ($u) => [$u->id => self::picOptionLabel($u)]);
                                })
                                ->getOptionLabelsUsing(fn (array $values) => User::with(['company', 'department', 'unit'])
                                    ->whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(fn ($u) => [$u->id => self::picOptionLabel($u)])),
                        ]),

                    // TAMPIL JIKA PILIH UPLOAD
                    FileUpload::make('file_path')
                        ->label('Notulen (PDF / Word)')
                        ->directory('meetings')
                        ->disk('private')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->maxSize(2048)
                        ->helperText('Format: PDF / Word, maksimal 2 MB.')
                        ->columnSpanFull()
                        ->visible(fn (string $operation, $get) => $operation !== 'create' && $get('mode_notulen') === 'upload'),

                    FileUpload::make('attachments')
                        ->label('Lampiran Gambar (Foto/Dokumentasi)')
                        ->directory('meetings/attachments')
                        ->disk('public')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->openable()
                        ->downloadable()
                        ->reorderable()
                        ->appendFiles()
                        ->panelLayout('grid')
                        ->maxSize(2048)
                        ->helperText('Bisa unggah beberapa foto sekaligus. Setiap file maksimal 2 MB.')
                        ->columnSpanFull()
                        ->hiddenOn('create'),
                ])
                ->columnSpanFull(),

            Section::make('Jadwal & Lokasi')
                ->description('Tentukan waktu dan ruangan rapat. Ketersediaan ruangan dicek otomatis berdasarkan rentang waktu.')
                ->icon('heroicon-o-calendar-days')
                ->columns(2)
                ->collapsible()
                ->schema([
                    DateTimePicker::make('date_time')
                        ->label('Tanggal & Waktu Mulai')
                        ->required()
                        ->live()
                        ->native(false)
                        ->seconds(false)
                        ->displayFormat('d M Y, H:i')
                        ->minutesStep(5)
                        ->closeOnDateSelection()
                        ->prefixIcon('heroicon-m-clock')
                        ->minDate(fn (string $operation) => $operation === 'create' ? now()->startOfDay() : null)
                        ->hint(fn () => request()->query('date_time')
                            ? '📅 Tanggal diisi dari kalender — silakan lengkapi jam mulai rapat.'
                            : null
                        )
                        ->hintColor('primary')
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // Auto-isi end_time +2 jam jika belum diisi
                            if (! $get('end_time') && $state) {
                                $set('end_time', \Carbon\Carbon::parse($state)->addHours(2)->format('Y-m-d\TH:i'));
                            }
                        }),
                    DateTimePicker::make('end_time')
                        ->label('Jam Berakhir')
                        ->nullable()
                        ->live()
                        ->native(false)
                        ->seconds(false)
                        ->displayFormat('d M Y, H:i')
                        ->minutesStep(5)
                        ->closeOnDateSelection()
                        ->prefixIcon('heroicon-m-clock')
                        ->helperText('Otomatis terisi +2 jam dari jam mulai — sesuaikan bila perlu.')
                        ->after('date_time')
                        ->validationMessages(['after' => 'Jam berakhir harus setelah jam mulai.']),
                    Select::make('location')
                        ->label('Lokasi')
                        ->placeholder('Ketik atau pilih lokasi...')
                        ->prefixIcon('heroicon-m-map-pin')
                        ->columnSpanFull()
                        ->searchable()
                        ->nullable()
                        ->options(function (callable $get, $record) {
                            $locations = \App\Models\MeetingLocation::query()
                                ->when(
                                    auth()->user()?->company_id && ! auth()->user()?->hasRole('super_admin'),
                                    fn ($q) => $q->where(function ($q) {
                                        $q->where('company_id', auth()->user()->company_id)
                                            ->orWhereNull('company_id');
                                    })
                                )
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->toArray();

                            return self::decorateLocationOptions($locations, $get, $record);
                        })
                        ->getSearchResultsUsing(function (string $search, callable $get, $record): array {
                            $locations = \App\Models\MeetingLocation::query()
                                ->where('name', 'like', "%{$search}%")
                                ->when(
                                    auth()->user()?->company_id && ! auth()->user()?->hasRole('super_admin'),
                                    fn ($q) => $q->where(function ($q) {
                                        $q->where('company_id', auth()->user()->company_id)
                                            ->orWhereNull('company_id');
                                    })
                                )
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->toArray();

                            $decorated = self::decorateLocationOptions($locations, $get, $record);

                            // Jika teks bebas tidak ada dalam daftar, tambahkan sebagai opsi
                            $trimmed = trim($search);
                            if ($trimmed !== '' && ! array_key_exists($trimmed, $locations)) {
                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:inline;vertical-align:middle;margin-right:5px;opacity:0.7;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>';
                                $decorated = [$trimmed => $icon.e($trimmed).' <span style="opacity:0.5;font-size:0.8em;">(teks bebas)</span>'] + $decorated;
                            }

                            return $decorated;
                        })
                        ->disableOptionWhen(function (string $value, callable $get, $record): bool {
                            return in_array(
                                mb_strtolower(trim($value)),
                                self::bookedLocationNameSet($get, $record),
                                true
                            );
                        })
                        ->helperText(function (callable $get, $record) {
                            if (! self::resolveWindowStart($get)) {
                                return 'Isi tanggal & jam rapat lebih dulu untuk melihat ketersediaan ruangan.';
                            }

                            $booked = self::bookedLocationNameSet($get, $record);

                            if (empty($booked)) {
                                return null;
                            }

                            $selected = $get('location');
                            if (filled($selected) && in_array(mb_strtolower(trim($selected)), $booked, true)) {
                                return new \Illuminate\Support\HtmlString(
                                    '<span style="color:#dc2626;font-weight:600;">Ruangan / lokasi rapat sudah dipesan pada rentang waktu tersebut. Silakan pilih ruangan lain atau ubah jadwal.</span>'
                                );
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<span style="color:#b45309;">Ruangan yang terkunci sudah dipesan pada rentang waktu yang dipilih.</span>'
                            );
                        })
                        ->rules([
                            fn ($record, callable $get) => function (string $attribute, $value, \Closure $fail) use ($record, $get) {
                                if (blank($value)) {
                                    return;
                                }

                                $start = self::resolveWindowStart($get);
                                if (! $start) {
                                    return;
                                }
                                $end = self::resolveWindowEnd($get);

                                $locationId = \App\Models\MeetingLocation::query()
                                    ->where('name', $value)
                                    ->value('id');

                                $conflict = \App\Models\Meeting::locationConflict(
                                    $locationId ? (int) $locationId : null,
                                    $value,
                                    $start,
                                    $end,
                                    $record?->getKey(),
                                );

                                if ($conflict) {
                                    $conflictEnd = $conflict->effectiveEndTime();
                                    $fail(sprintf(
                                        'Ruangan / lokasi rapat "%s" sudah dipesan pada %s–%s oleh rapat "%s". Silakan pilih ruangan lain atau ubah jadwal.',
                                        $value,
                                        $conflict->date_time->format('d M Y H:i'),
                                        $conflictEnd->format('H:i'),
                                        $conflict->title,
                                    ));
                                }
                            },
                        ])
                        ->allowHtml()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Jika lokasi dipilih dari daftar, sinkronkan meeting_location_id
                            $loc = \App\Models\MeetingLocation::where('name', $state)->first();
                            $set('meeting_location_id', $loc?->id);
                        })
                        ->getOptionLabelUsing(fn ($value) => e($value)),
                    \Filament\Forms\Components\Hidden::make('meeting_location_id'),
                    Select::make('status')
                        ->options([
                            'scheduled' => 'Terjadwal',
                            'completed' => 'Selesai',
                            'cancelled' => 'Batal',
                        ])
                        ->default('scheduled')
                        ->required()
                        ->native(false)
                        ->prefixIcon('heroicon-m-flag')
                        ->label('Status Rapat')
                        ->hiddenOn('create'),
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->relationship('company', 'name')
                        ->default(auth()->user()->company_id)
                        ->required()
                        ->native(false)
                        ->prefixIcon('heroicon-m-building-office-2')
                        ->visible(fn () => auth()->user()->hasRole('super_admin'))
                        ->live(),
                ]),

            Section::make('Peserta & Notulis')
                ->description('Pilih peserta yang diundang. Gunakan filter untuk mempersempit pencarian, lalu tentukan notulis dari peserta terpilih.')
                ->icon('heroicon-o-user-group')
                ->collapsible()
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 3])->schema([
                        Select::make('filter_company_id')
                            ->label('Perusahaan')
                            ->prefixIcon('heroicon-m-building-office')
                            ->options(\App\Models\Company::pluck('name', 'id'))
                            ->default(auth()->user()->company_id)
                            ->live()
                            ->placeholder('Semua Perusahaan')
                            ->dehydrated(false)
                            ->visible(
                                fn ($record) => auth()->user()->hasRole(['super_admin', 'Sekretaris']) ||
                                auth()->user()->can('view_any_company_participants') ||
                                ($record && ($record->created_by === auth()->id() || $record->notulis_id === auth()->id())) ||
                                (! $record)
                            )
                            ->afterStateUpdated(function ($set) {
                                $set('filter_department_id', null);
                                $set('filter_unit_id', null);
                            }),

                        Select::make('filter_department_id')
                            ->label('Departemen')
                            ->options(function (callable $get) {
                                $companyId = $get('filter_company_id') ?: auth()->user()->company_id;

                                return \App\Models\Department::where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->live()
                            ->placeholder('Semua Departemen')
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($set) {
                                $set('filter_unit_id', null);
                            }),

                        Select::make('filter_unit_id')
                            ->label('Unit')
                            ->options(function (callable $get) {
                                $departmentId = $get('filter_department_id');
                                if ($departmentId) {
                                    return \App\Models\Unit::where('department_id', $departmentId)->pluck('name', 'id');
                                }
                                $companyId = $get('filter_company_id') ?: auth()->user()->company_id;

                                return \App\Models\Unit::whereHas('department', function ($q) use ($companyId) {
                                    $q->where('company_id', $companyId);
                                })->pluck('name', 'id');
                            })
                            ->live()
                            ->placeholder('Semua Unit')
                            ->dehydrated(false)
                            ->afterStateUpdated(fn ($set) => null),
                    ]),

                    Select::make('participants')
                        ->relationship('participants', 'name')
                        ->options(function ($get, $record) {
                            return self::buildParticipantQuery($get, $record)->get()
                                ->mapWithKeys(fn ($u) => [
                                    $u->id => $u->name.
                                        ($u->department ? ' — '.$u->department->name : '').
                                        ($u->unit ? ' / '.$u->unit->name : ''),
                                ]);
                        })
                        ->getSearchResultsUsing(function (string $search, $get, $record) {
                            return self::buildParticipantQuery($get, $record)
                                ->where('name', 'like', "%{$search}%")
                                ->get()
                                ->mapWithKeys(fn ($u) => [
                                    $u->id => $u->name.
                                        ($u->department ? ' — '.$u->department->name : '').
                                        ($u->unit ? ' / '.$u->unit->name : ''),
                                ]);
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $u = User::with(['department', 'unit'])->find($value);
                            if (! $u) {
                                return $value;
                            }

                            return $u->name.
                                ($u->department ? ' — '.$u->department->name : '').
                                ($u->unit ? ' / '.$u->unit->name : '');
                        })
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->live()
                        ->required()
                        ->columnSpanFull()
                        ->prefixIcon('heroicon-m-users')
                        ->label('Pilih Peserta')
                        ->helperText('Filter perusahaan wajib dipilih; departemen dan unit bersifat opsional untuk mempersempit pencarian.')
                        ->rules([
                            fn ($record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                                $user = auth()->user();
                                if (
                                    $user->hasRole(['super_admin', 'Sekretaris']) ||
                                    $user->can('view_any_company_participants') ||
                                    ($record && ($record->created_by === $user->id || $record->notulis_id === $user->id)) ||
                                    (! $record)
                                ) {
                                    return;
                                }

                                $participantIds = (array) $value;
                                $invalidCount = User::whereIn('id', $participantIds)
                                    ->where('company_id', '!=', $user->company_id)
                                    ->count();

                                if ($invalidCount > 0) {
                                    $fail('Anda hanya dapat menambahkan peserta dari perusahaan Anda sendiri.');
                                }
                            },
                        ])
                        ->afterStateUpdated(fn ($set) => $set('notulis_id', null))
                        ->validationMessages([
                            'required' => 'Wajib memilih minimal satu peserta.',
                        ]),

                    Select::make('notulis_id')
                        ->label('Notulis / Pencatat')
                        ->options(function ($get) {
                            $selectedParticipants = (array) $get('participants');
                            if (empty($selectedParticipants)) {
                                return [];
                            }

                            return User::whereIn('id', $selectedParticipants)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->getSearchResultsUsing(function (string $search, $get) {
                            $selectedParticipants = (array) $get('participants');
                            if (empty($selectedParticipants)) {
                                return [];
                            }

                            return User::whereIn('id', $selectedParticipants)
                                ->where('name', 'like', "%{$search}%")
                                ->orderBy('name')
                                ->limit(50)
                                ->pluck('name', 'id');
                        })
                        ->getOptionLabelUsing(fn ($value) => User::find($value)?->name)
                        ->searchable()
                        ->live()
                        ->columnSpanFull()
                        ->prefixIcon('heroicon-m-pencil')
                        ->placeholder('Pilih Notulis (Opsional)')
                        ->helperText('Pilih salah satu dari peserta yang telah dipilih sebagai petugas pencatat notulensi')
                        ->rules([
                            fn ($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (blank($value)) {
                                    return;
                                }

                                $selectedParticipants = array_map('strval', (array) $get('participants'));

                                if (! in_array((string) $value, $selectedParticipants, true)) {
                                    $fail('Notulis harus salah satu dari peserta rapat yang dipilih.');
                                }
                            },
                        ]),
                ]),

            Hidden::make('created_by')->default(auth()->id()),
            Hidden::make('company_id')
                ->default(fn () => auth()->user()->company_id)
                ->dehydrated(fn ($state) => filled($state))
                ->visible(fn () => ! auth()->user()->hasRole('super_admin')),
            Hidden::make('department_id')->default(fn () => auth()->user()->department_id),
            Hidden::make('unit_id')->default(fn () => auth()->user()->unit_id),

        ]);
    }

    /**
     * Label user untuk opsi PIC — menyertakan perusahaan/departemen/unit agar
     * nama yang sama di unit bisnis berbeda tidak tertukar.
     */
    protected static function picOptionLabel(User $user): string
    {
        $parts = array_filter([
            $user->company?->name,
            $user->department?->name,
            $user->unit?->name,
        ]);

        return $parts ? $user->name.' — '.implode(' / ', $parts) : $user->name;
    }

    /**
     * Query builder untuk peserta — digunakan oleh options() dan getSearchResultsUsing()
     * agar filter perusahaan/departemen/unit konsisten baik saat dropdown dibuka maupun saat search.
     */
    protected static function buildParticipantQuery(callable $get, $record): \Illuminate\Database\Eloquent\Builder
    {
        $filterCompanyId = $get('filter_company_id');
        $filterDepartmentId = $get('filter_department_id');
        $filterUnitId = $get('filter_unit_id');
        $user = auth()->user();

        $query = User::with(['department', 'unit'])->active();

        // Otoritas: super_admin / Sekretaris boleh pilih lintas perusahaan
        $hasGlobalAccess = $user->hasRole(['super_admin', 'Sekretaris']) ||
            $user->can('view_any_company_participants') ||
            ($record && ($record->created_by === $user->id || $record->notulis_id === $user->id)) ||
            (! $record);

        if ($hasGlobalAccess) {
            // Jika filter perusahaan dipilih → saring per perusahaan
            // Jika TIDAK dipilih → tampilkan semua perusahaan (lintas perusahaan)
            if ($filterCompanyId) {
                $query->where('company_id', $filterCompanyId);
            }
            // else: tidak ada batasan company → semua user dari semua perusahaan tampil
        } else {
            // User biasa: hanya bisa melihat company sendiri
            $query->where('company_id', $user->company_id);
        }

        // Filter departemen — opsional
        if ($filterDepartmentId) {
            $query->where('department_id', $filterDepartmentId);
        }

        // Filter unit — opsional
        if ($filterUnitId) {
            $query->where('unit_id', $filterUnitId);
        }

        return $query->orderBy('name');
    }

    /**
     * Jam mulai rapat dari state form (atau null bila belum diisi / tidak valid).
     */
    protected static function resolveWindowStart(callable $get): ?\Carbon\Carbon
    {
        $value = $get('date_time');

        if (blank($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Jam berakhir rapat dari state form (atau null → pakai durasi default).
     */
    protected static function resolveWindowEnd(callable $get): ?\Carbon\Carbon
    {
        $value = $get('end_time');

        if (blank($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Nama lokasi (huruf kecil) yang sudah dipesan pada rentang waktu di form.
     * Menggabungkan lokasi via FK maupun nama lokasi teks bebas.
     *
     * @return string[]
     */
    protected static function bookedLocationNameSet(callable $get, $record): array
    {
        $start = self::resolveWindowStart($get);

        if (! $start) {
            return [];
        }

        $end = self::resolveWindowEnd($get);
        $excludeId = $record?->getKey();

        $memoKey = $start->getTimestamp().'|'.($end?->getTimestamp() ?? 'x').'|'.($excludeId ?? 'x');

        if (array_key_exists($memoKey, self::$bookedLocationMemo)) {
            return self::$bookedLocationMemo[$memoKey];
        }

        $keys = \App\Models\Meeting::bookedLocationKeys($start, $end, $excludeId ? (int) $excludeId : null);

        $names = $keys['names'];

        if (! empty($keys['ids'])) {
            $names = array_merge(
                $names,
                \App\Models\MeetingLocation::query()
                    ->whereIn('id', $keys['ids'])
                    ->pluck('name')
                    ->map(fn ($n) => mb_strtolower(trim($n)))
                    ->all()
            );
        }

        return self::$bookedLocationMemo[$memoKey] = array_values(array_unique($names));
    }

    /**
     * Beri penanda "sudah dipesan" pada opsi lokasi yang bentrok jadwal.
     *
     * @param  array<string, string>  $options  [nama => nama]
     * @return array<string, string>
     */
    protected static function decorateLocationOptions(array $options, callable $get, $record): array
    {
        $booked = self::bookedLocationNameSet($get, $record);

        $decorated = [];

        foreach ($options as $key => $label) {
            $isBooked = in_array(mb_strtolower(trim((string) $key)), $booked, true);

            $decorated[$key] = $isBooked
                ? '<span style="display:inline-flex;align-items:center;gap:6px;opacity:0.55;">'
                    .'<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'
                    .e($label)
                    .' <span style="color:#dc2626;font-weight:600;font-size:0.8em;">— sudah dipesan</span>'
                    .'</span>'
                : e($label);
        }

        return $decorated;
    }
}
