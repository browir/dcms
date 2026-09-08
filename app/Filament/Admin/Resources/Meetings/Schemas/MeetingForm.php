<?php

namespace App\Filament\Admin\Resources\Meetings\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Rapat')
                ->schema([
                    TextInput::make('title')->label('Judul Rapat')->required()->maxLength(255),
                    TextInput::make('doc_number')
                        ->label('No. Dokumen')
                        ->maxLength(255)
                        ->placeholder('Contoh: 003/H.2/SGG/VIII/2025')
                        ->hiddenOn('create'),
                    Textarea::make('agenda')->label('Agenda')->rows(3)->columnSpanFull(),
                    Select::make('mode_notulen')
                        ->hiddenOn('create')
                        ->label('Metode Notulensi')
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
                        <th style='width: 80px;'>PIC</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style='text-align: center;'>1</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        "
                            );
                        }),

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
                        ->maxSize(2048)
                        ->columnSpanFull()
                        ->hiddenOn('create'),
                ])
                ->columnSpanFull(),

            Section::make('Status & Jadwal')->schema([
                DateTimePicker::make('date_time')
                    ->label('Tanggal & Waktu Mulai')
                    ->required()
                    ->live()
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
                    ->after('date_time')
                    ->validationMessages(['after' => 'Jam berakhir harus setelah jam mulai.']),
                Select::make('location')
                    ->label('Lokasi')
                    ->placeholder('Ketik atau pilih lokasi...')
                    ->searchable()
                    ->nullable()
                    ->options(fn () => \App\Models\MeetingLocation::query()
                        ->when(
                            auth()->user()?->company_id && ! auth()->user()?->hasRole('super_admin'),
                            fn ($q) => $q->where(function ($q) {
                                $q->where('company_id', auth()->user()->company_id)
                                    ->orWhereNull('company_id');
                            })
                        )
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->toArray()
                    )
                    ->getSearchResultsUsing(function (string $search): array {
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

                        // Jika teks bebas tidak ada dalam daftar, tambahkan sebagai opsi
                        $trimmed = trim($search);
                        if ($trimmed !== '' && ! array_key_exists($trimmed, $locations)) {
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:inline;vertical-align:middle;margin-right:5px;opacity:0.7;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>';
                            $locations = [$trimmed => $icon.e($trimmed).' <span style="opacity:0.5;font-size:0.8em;">(teks bebas)</span>'] + $locations;
                        }

                        return $locations;
                    })
                    ->allowHtml()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Jika lokasi dipilih dari daftar, sinkronkan meeting_location_id
                        $loc = \App\Models\MeetingLocation::where('name', $state)->first();
                        $set('meeting_location_id', $loc?->id);
                    })
                    ->getOptionLabelUsing(fn ($value) => $value),
                \Filament\Forms\Components\Hidden::make('meeting_location_id'),
                Select::make('status')
                    ->options([
                        'scheduled' => 'Terjadwal',
                        'completed' => 'Selesai',
                        'cancelled' => 'Batal',
                    ])
                    ->default('scheduled')
                    ->required()
                    ->label('Status Rapat'),
                Select::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->default(auth()->user()->company_id)
                    ->required()
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->live(),
            ]),

            Section::make('Peserta')->schema([
                \Filament\Schemas\Components\Grid::make(3)->schema([
                    Select::make('filter_company_id')
                        ->label('Perusahaan')
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
                    ->relationship('notulis', 'name')
                    ->options(function ($get) {
                        $selectedParticipants = $get('participants');
                        if (empty($selectedParticipants)) {
                            return [];
                        }

                        return User::whereIn('id', $selectedParticipants)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->placeholder('Pilih Notulis (Opsional)')
                    ->helperText('Pilih salah satu dari peserta yang telah dipilih sebagai petugas pencatat notulensi'),
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
}
