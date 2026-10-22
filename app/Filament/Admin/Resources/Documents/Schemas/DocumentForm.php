<?php

namespace App\Filament\Admin\Resources\Documents\Schemas;

use App\Filament\Admin\Resources\LetterNumbers\Schemas\LetterNumberForm;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([

            Placeholder::make('rejection_history')
                ->hiddenLabel()
                ->content(function ($record) {
                    if (! $record || $record->status !== Document::STATUS_REJECTED) {
                        return null;
                    }
                    $rejections = $record->rejections()->with('user')->get();
                    if ($rejections->isEmpty()) {
                        return null;
                    }

                    $html = '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">';
                    $html .= '<h3 class="font-bold text-lg mb-2 flex items-center"><svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"></path></svg> Riwayat Penolakan Dokumen</h3>';
                    $html .= '<ul class="list-disc pl-5 space-y-2">';
                    foreach ($rejections as $rej) {
                        $roleName = ucfirst($rej->role);
                        $date = $rej->created_at->format('d M Y H:i');
                        $html .= "<li><strong>{$rej->user->name} ({$roleName})</strong> pada {$date}: <br> <span class='italic'>\"{$rej->notes}\"</span></li>";
                    }
                    $html .= '</ul>';
                    $html .= '<p class="mt-3 font-semibold">Silakan perbaiki dokumen dan perbarui Versi Dokumen di bawah, lalu simpan.</p>';
                    $html .= '</div>';

                    return new HtmlString($html);
                })
                ->visible(fn ($record) => $record && $record->status === Document::STATUS_REJECTED)
                ->columnSpanFull(),

            /* ─── TIPE DOKUMEN ─── */
            Section::make('Tipe Dokumen')
                ->description('Pilih jenis dokumen yang akan dibuat')
                ->icon('heroicon-o-squares-2x2')
                ->iconColor('primary')
                ->columnSpanFull()
                ->schema([
                    ToggleButtons::make('document_type')
                        ->hiddenLabel()
                        ->options([
                            'file' => 'Unggah Berkas',
                            'hybrid' => 'Gabungan',
                        ])
                        ->icons([
                            'file' => 'heroicon-o-paper-clip',
                            'hybrid' => 'heroicon-o-squares-plus',
                        ])
                        ->colors([
                            'file' => 'primary',
                            'hybrid' => 'warning',
                        ])
                        ->default('file')
                        ->required()
                        ->live()
                        ->inline()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state === 'file') {
                                $set('content', null);
                            } elseif ($state === 'form') {
                                $set('file_path', null);
                                $set('file_name', null);
                            }
                        }),
                ]),

            View::make('filament.admin.resources.documents.partials.document-readiness-assistant')
                ->viewData(fn (Get $get): array => self::buildReadinessAssistantData($get))
                ->columnSpanFull(),

            /* ─── IDENTITAS DOKUMEN ─── */
            Section::make('Identitas Dokumen')
                ->description('Isi informasi dasar dokumen')
                ->icon('heroicon-o-document-text')
                ->iconColor('primary')
                ->columnSpanFull()
                ->schema([
                    Select::make('letter_number_id')
                        ->label('Nomor Dokumen')
                        ->helperText('Dokumen diisi ke nomor dokumen yang sudah diambil. Belum punya nomor? Klik tombol + untuk mengambil nomor baru.')
                        ->options(fn ($livewire): array => self::letterNumberOptions(data_get($livewire, 'kelompok')))
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->default(fn (): ?int => request()->integer('nomor_surat') ?: null)
                        ->required()
                        ->visibleOn('create')
                        ->prefixIcon('heroicon-o-hashtag')
                        ->createOptionForm(fn ($livewire): array => LetterNumberForm::components(data_get($livewire, 'kelompok')))
                        ->createOptionUsing(fn (array $data): int => LetterNumberForm::issue($data)->id)
                        ->createOptionAction(fn (Action $action) => $action
                            ->modalHeading('Ambil Nomor Dokumen')
                            ->modalSubmitActionLabel('Terbitkan Nomor')
                            ->modalWidth('lg'))
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            foreach (self::letterNumberState($state, $get('title')) as $field => $value) {
                                $set($field, $value);
                            }
                        }),

                    TextInput::make('code_number')
                        ->label('Nomor Dokumen')
                        ->readOnly()
                        ->dehydrated(false)
                        ->visibleOn('edit')
                        ->prefixIcon('heroicon-o-hashtag')
                        ->helperText('Nomor dokumen yang sudah terbit tidak berubah.'),

                    // Kategori = jenis dokumen, selalu mengikuti nomor dokumen (diset model saat simpan).
                    Select::make('category_id')
                        ->label('Kategori Dokumen')
                        ->options(fn () => DocumentCategory::query()->whereNotNull('code')->get()->mapWithKeys(fn (DocumentCategory $c) => [$c->id => $c->option_label]))
                        ->placeholder('Mengikuti nomor dokumen')
                        ->disabled()
                        ->dehydrated(false)
                        ->native(false)
                        ->prefixIcon('heroicon-o-tag'),

                    DatePicker::make('tanggal_surat')
                        ->label('Tanggal Dokumen')
                        ->placeholder('Mengikuti nomor dokumen')
                        ->disabled()
                        ->dehydrated(false)
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->prefixIcon('heroicon-o-calendar'),

                    TextInput::make('title')
                        ->label('Judul Dokumen')
                        ->required()
                        ->live(onBlur: true)
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-pencil')
                        ->placeholder('Masukkan judul dokumen'),

                    TextInput::make('version')
                        ->label('Versi Dokumen')
                        ->default('1.0')
                        ->maxLength(50)
                        ->prefixIcon('heroicon-o-document-duplicate')
                        ->helperText('Perbarui nomor versi saat merevisi dokumen yang ditolak (misal: 1.1)'),
                ]),

            /* ─── BERKAS DOKUMEN ─── */
            Section::make('Berkas Dokumen')
                ->description('Unggah berkas dokumen (PDF, Word, Excel, atau Gambar)')
                ->icon('heroicon-o-paper-clip')
                ->iconColor('success')
                ->columnSpanFull()
                ->hidden(fn ($get) => $get('document_type') === 'form')
                ->schema([
                    Hidden::make('file_name'),
                    FileUpload::make('file_path')
                        ->hiddenLabel()
                        ->live()
                        ->disk('documents')
                        ->directory('documents')
                        ->preserveFilenames()
                        ->afterStateUpdated(function ($state, callable $set, $record) {
                            if ($state) {
                                $set('file_name', basename($state));

                                // ── Duplicate File Detection ──
                                $hash = Document::computeFileHash($state);
                                if ($hash) {
                                    $existingDoc = Document::findDuplicateByHash(
                                        $hash,
                                        $record?->id
                                    );
                                    if ($existingDoc) {
                                        $title = $existingDoc->title ?? 'Tidak diketahui';
                                        $code = $existingDoc->code_number ?? '-';
                                        $deletedInfo = $existingDoc->trashed()
                                            ? ' (di Recycle Bin)'
                                            : '';

                                        Notification::make()
                                            ->title('File Duplikat Terdeteksi')
                                            ->body("File yang Anda unggah identik dengan dokumen yang sudah ada: **{$title}** [{$code}]{$deletedInfo}. Silakan gunakan dokumen tersebut atau unggah file yang berbeda.")
                                            ->danger()
                                            ->persistent()
                                            ->send();

                                        // Reset file upload
                                        $set('file_path', null);
                                        $set('file_name', null);

                                        return;
                                    }
                                    // Store hash in hidden field for saving
                                    $set('_computed_file_hash', $hash);
                                }
                            } else {
                                $set('file_name', null);
                                $set('_computed_file_hash', null);
                            }
                        })
                        ->maxSize(10240)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'image/jpeg',
                            'image/png',
                        ])
                        ->required(fn ($get) => in_array($get('document_type'), ['file', 'hybrid']))
                        ->helperText('Maksimal 10 MB. Format: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG'),

                    // Hidden field to carry computed hash to CreateDocument/EditDocument
                    Hidden::make('_computed_file_hash'),
                ]),

            /* ─── KONTEN FORMULIR ─── */
            Section::make('Konten Dokumen')
                ->description('Tulis isi dokumen menggunakan editor teks kaya')
                ->icon('heroicon-o-pencil-square')
                ->iconColor('info')
                ->columnSpanFull()
                ->hidden(fn ($get) => $get('document_type') === 'file')
                ->schema([
                    RichEditor::make('content')
                        ->hiddenLabel()
                        ->live(debounce: 900)
                        ->maxLength(65535)
                        ->required(fn ($get) => in_array($get('document_type'), ['form', 'hybrid']))
                        ->helperText('Gunakan toolbar di atas untuk memformat teks'),
                ]),

            /* ─── ORGANISASI ─── */
            Section::make('Organisasi')
                ->description('Tentukan perusahaan, departemen, unit, dan kategori dokumen')
                ->icon('heroicon-o-building-office-2')
                ->iconColor('warning')
                ->columnSpanFull()
                ->extraAttributes(['class' => 'relative !z-20'])
                ->schema([
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->options(fn () => Company::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->reactive()
                        ->prefixIcon('heroicon-o-building-office')
                        ->default(function () {
                            /** @var User $user */
                            $user = Auth::user();

                            return optional($user)->company_id ?? null;
                        })
                        ->disabled(function (Get $get) {
                            // Mengikuti unit & perusahaan penerbit nomor dokumen.
                            if (filled($get('letter_number_id'))) {
                                return true;
                            }

                            /** @var User $user */
                            $user = Auth::user();

                            return ! $user->hasRole(['super_admin', 'direktur']);
                        })
                        ->dehydrated()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $operation): void {
                            $set('department_id', null);
                            $set('unit_id', null);
                        })
                        ->required(),

                    Select::make('department_id')
                        ->label('Departemen')
                        ->options(function (callable $get) {
                            /** @var User $user */
                            $user = Auth::user();
                            $companyId = $get('company_id') ?? optional($user)->company_id;

                            return Department::when($companyId, fn ($q) => $q->where('company_id', $companyId))
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->reactive()
                        ->prefixIcon('heroicon-o-building-office-2')
                        ->default(function () {
                            /** @var User $user */
                            $user = Auth::user();

                            return optional($user)->department_id ?? null;
                        })
                        ->disabled(function (Get $get) {
                            // Mengikuti unit & perusahaan penerbit nomor dokumen.
                            if (filled($get('letter_number_id'))) {
                                return true;
                            }

                            /** @var User $user */
                            $user = Auth::user();

                            return ! $user->hasRole(['super_admin', 'direktur']);
                        })
                        ->dehydrated()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $operation): void {
                            $set('unit_id', null);
                        })
                        ->required(),

                    Select::make('unit_id')
                        ->label('Unit')
                        ->options(function (callable $get) {
                            /** @var User $user */
                            $user = Auth::user();
                            $departmentId = $get('department_id') ?? optional($user)->department_id;

                            return Unit::when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->reactive()
                        ->prefixIcon('heroicon-o-user-group')
                        ->default(function () {
                            /** @var User $user */
                            $user = Auth::user();

                            return optional($user)->unit_id ?? null;
                        })
                        ->disabled(function (Get $get) {
                            // Mengikuti unit & perusahaan penerbit nomor dokumen.
                            if (filled($get('letter_number_id'))) {
                                return true;
                            }

                            /** @var User $user */
                            $user = Auth::user();

                            return ! $user->hasRole(['super_admin', 'direktur', 'kabid']);
                        })
                        ->dehydrated()
                        ->required(),
                ]),

            /* ─── STATUS ─── */
            Section::make('Status Publikasi')
                ->description('Atur status awal dokumen setelah disimpan')
                ->icon('heroicon-o-cog-6-tooth')
                ->iconColor('gray')
                ->extraAttributes([
                    'class' => 'document-status-section',
                ])
                ->columnSpanFull()
                ->schema([
                    ToggleButtons::make('status')
                        ->hiddenLabel()
                        ->options(function () {
                            /** @var User|null $user */
                            $user = Auth::user();
                            $canDirectApprove = $user && ($user->can('direct_approve_document') || $user->hasRole(['direktur', 'super_admin']));

                            if ($canDirectApprove) {
                                return [
                                    'draft' => 'Draft',
                                    'approved' => 'Diterbitkan',
                                    'archived' => 'Diarsipkan',
                                ];
                            }

                            return [
                                'draft' => 'Draft',
                                'pending_kabid' => 'Pending',
                                'archived' => 'Diarsipkan',
                            ];
                        })
                        ->icons([
                            'draft' => 'heroicon-o-pencil',
                            'pending_kabid' => 'heroicon-o-paper-airplane',
                            'approved' => 'heroicon-o-check-circle',
                            'archived' => 'heroicon-o-archive-box',
                        ])
                        ->colors([
                            'draft' => 'primary',
                            'pending_kabid' => 'primary',
                            'approved' => 'primary',
                            'archived' => 'primary',
                        ])
                        ->default('draft')
                        ->required()
                        ->live()
                        ->inline()
                        ->helperText(function () {
                            /** @var User|null $user */
                            $user = Auth::user();
                            $canDirectApprove = $user && ($user->can('direct_approve_document') || $user->hasRole(['direktur', 'super_admin']));

                            $style = '<style>.hlp-desk{display:inline}.hlp-mob{display:none}@media(max-width:767px){.hlp-desk{display:none}.hlp-mob{display:inline}}</style>';

                            if ($canDirectApprove) {
                                return new HtmlString($style.'<span class="hlp-desk">Draft = simpan sementara. Diterbitkan = dokumen langsung diterbitkan &amp; aktif (akses Direktur).</span><span class="hlp-mob">Draft: simpan sementara. Diterbitkan: langsung aktif.</span>');
                            }

                            return new HtmlString($style.'<span class="hlp-desk">Draft = simpan sementara. Pending = ajukan dokumen untuk peninjauan persetujuan.</span><span class="hlp-mob">Draft: simpan sementara. Pending: ajukan review.</span>');
                        }),

                    Toggle::make('is_public')
                        ->label('Dokumen Publik (Terlihat oleh semua departemen)')
                        ->helperText(new HtmlString('<span class="hlp-desk">Jika diaktifkan, dokumen ini dapat dilihat oleh seluruh departemen dalam perusahaan. Jika dinonaktifkan, hanya departemen Anda yang bisa melihat.</span><span class="hlp-mob">Aktif: terlihat semua dept. Nonaktif: hanya dept. Anda.</span>'))
                        ->default(false),
                ]),

            /* ─── HAK AKSES GRANULAR ─── */
            Section::make('Hak Akses Granular Unit')
                ->description('Atur unit mana saja yang diberikan hak akses tambahan untuk membaca dokumen ini')
                ->icon('heroicon-o-key')
                ->iconColor('success')
                ->columnSpanFull()
                ->hidden()
                ->extraAttributes(['class' => 'relative !z-10'])
                ->schema([
                    Select::make('allowedUnits')
                        ->label('Unit Tambahan Yang Diizinkan Mengakses')
                        ->relationship('allowedUnits', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->prefixIcon('heroicon-o-user-group')
                        ->placeholder('Pilih unit lain yang dapat mengakses dokumen ini...')
                        ->helperText('Kosongkan jika dokumen hanya diakses oleh unit utama dokumen ini. Jika dipilih, pegawai di unit-unit tersebut akan diberikan izin khusus untuk membaca dokumen ini.'),
                ]),

            /* ─── INFORMASI TAMBAHAN ─── */
            Section::make('Informasi Tambahan')
                ->description('Deskripsi singkat dan masa berlaku dokumen (opsional)')
                ->icon('heroicon-o-information-circle')
                ->iconColor('info')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->maxLength(1000)
                        ->placeholder('Tuliskan deskripsi singkat tentang dokumen ini (opsional)...')
                        ->helperText(new HtmlString('<span class="hlp-desk">Deskripsi membantu reviewer dan pengguna lain memahami isi dokumen dengan cepat.</span><span class="hlp-mob">Bantu reviewer memahami isi dokumen.</span>')),

                    // ── Expired Date + "Berlaku Selamanya" ──
                    Toggle::make('is_permanent')
                        ->label('Berlaku Selamanya')
                        ->helperText(new HtmlString('<span class="hlp-desk">Aktifkan jika dokumen tidak memiliki batas masa berlaku.</span><span class="hlp-mob">Tanpa batas masa berlaku.</span>'))
                        ->default(true)
                        ->live()
                        ->afterStateUpdated(function (bool $state, Set $set): void {
                            if ($state) {
                                // Cleared expires_at when set to permanent
                                $set('expires_at', null);
                            }
                        })
                        ->dehydrated(false),  // Not a DB column – handled in mutateFormData

                    DatePicker::make('expires_at')
                        ->label('Berlaku Hingga (Tanggal Kedaluwarsa)')
                        ->icon('heroicon-o-calendar')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->placeholder('Pilih tanggal berakhirnya dokumen')
                        ->minDate(today()->addDay())
                        ->helperText('Sistem akan mengingatkan 30 hari sebelum kedaluwarsa.')
                        ->hidden(fn (Get $get): bool => (bool) $get('is_permanent'))
                        ->required(fn (Get $get): bool => ! (bool) $get('is_permanent')),

                    Toggle::make('is_mandatory_read')
                        ->label('Wajib Dibaca (Compliance Hub)')
                        ->helperText(new HtmlString('<span class="hlp-desk">Aktifkan agar dokumen ini muncul sebagai dokumen wajib baca untuk pegawai divisi terkait.</span><span class="hlp-mob">Dokumen wajib baca untuk divisi terkait.</span>'))
                        ->default(false),
                ]),

        ]);
    }

    private static function buildReadinessAssistantData(Get $get): array
    {
        $documentType = $get('document_type') ?: 'file';
        $title = trim((string) ($get('title') ?? ''));
        $hasFile = filled($get('file_path'));
        $hasContent = filled(trim(strip_tags((string) ($get('content') ?? ''))));
        $companyId = $get('company_id');
        $departmentId = $get('department_id');
        $unitId = $get('unit_id');
        $categoryId = $get('category_id');
        $status = $get('status') ?: 'draft';
        $isMandatoryRead = (bool) $get('is_mandatory_read');
        $isPermanent = (bool) $get('is_permanent');
        $expiresAt = $get('expires_at');

        $hasLetterNumber = filled($get('letter_number_id')) || filled($get('code_number'));

        $checklist = [
            [
                'label' => 'Nomor dokumen',
                'completed' => $hasLetterNumber,
                'description' => $hasLetterNumber
                    ? 'Dokumen diisi ke nomor dokumen yang sudah diambil.'
                    : 'Pilih nomor dokumen yang sudah diambil, atau ambil nomor baru.',
            ],
            [
                'label' => 'Judul dokumen',
                'completed' => $title !== '',
                'description' => $title !== '' ? $title : 'Tambahkan judul yang jelas agar mudah dicari.',
            ],
        ];

        if (in_array($documentType, ['file', 'hybrid'])) {
            $checklist[] = [
                'label' => 'Berkas utama',
                'completed' => $hasFile,
                'description' => $hasFile
                    ? 'Berkas sudah diunggah dan siap disimpan.'
                    : 'Unggah PDF, Word, Excel, JPG, atau PNG untuk melengkapi dokumen.',
            ];
        }

        if (in_array($documentType, ['form', 'hybrid'])) {
            $checklist[] = [
                'label' => 'Isi formulir',
                'completed' => $hasContent,
                'description' => $hasContent
                    ? 'Konten formulir sudah terisi.'
                    : 'Tambahkan isi dokumen pada editor agar informasi tidak kosong.',
            ];
        }

        $checklist[] = [
            'label' => 'Perusahaan',
            'completed' => filled($companyId),
            'description' => filled($companyId)
                ? 'Perusahaan sudah dipilih.'
                : 'Pilih perusahaan agar dokumen masuk ke struktur yang benar.',
        ];

        $checklist[] = [
            'label' => 'Departemen',
            'completed' => filled($departmentId),
            'description' => filled($departmentId)
                ? 'Departemen sudah dipilih.'
                : 'Pilih departemen untuk menentukan alur review yang tepat.',
        ];

        $checklist[] = [
            'label' => 'Unit kerja',
            'completed' => filled($unitId),
            'description' => filled($unitId)
                ? 'Unit kerja sudah dipilih.'
                : 'Pilih unit agar kepemilikan dokumen lebih presisi.',
        ];

        $checklist[] = [
            'label' => 'Kategori',
            'completed' => filled($categoryId),
            'description' => filled($categoryId)
                ? 'Kategori sudah dipilih.'
                : 'Pilih kategori agar nomor dokumen tergenerate dengan pola yang sesuai.',
        ];

        $checklist[] = [
            'label' => 'Status awal',
            'completed' => filled($status),
            'description' => self::getStatusHint($status),
        ];

        $checklist[] = [
            'label' => 'Masa berlaku',
            'completed' => $isPermanent || filled($expiresAt),
            'description' => $isPermanent
                ? 'Dokumen berlaku selamanya (tanpa batas waktu).'
                : (filled($expiresAt)
                    ? 'Tanggal kedaluwarsa dokumen sudah diatur.'
                    : 'Pilih tanggal kedaluwarsa dokumen (Berlaku Hingga).'),
        ];

        $checklist[] = [
            'label' => 'Wajib dibaca',
            'completed' => true,
            'description' => $isMandatoryRead
                ? 'Dokumen ditandai sebagai Wajib Dibaca (Compliance Hub).'
                : 'Dokumen ini opsional (tidak wajib dibaca).',
        ];

        $completedSteps = collect($checklist)->where('completed', true)->count();
        $totalSteps = count($checklist);
        $progress = $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0;

        [$tone, $headline, $summary] = match (true) {
            $progress >= 100 => [
                'success',
                'Siap diproses',
                'Dokumen sudah lengkap untuk disimpan. Setelah tersimpan, Anda bisa langsung melanjutkan proses review sesuai kebutuhan.',
            ],
            $progress >= 70 => [
                'warning',
                'Hampir lengkap',
                'Tinggal sedikit lagi. Lengkapi poin yang masih kosong supaya dokumen tidak tertahan di proses berikutnya.',
            ],
            default => [
                'danger',
                'Masih perlu dilengkapi',
                'Beberapa bagian penting masih kosong. Asisten ini membantu Anda melihat apa yang perlu dibereskan terlebih dulu.',
            ],
        };

        return [
            'documentTypeLabel' => match ($documentType) {
                'file' => 'Unggah Berkas',
                'form' => 'Formulir',
                'hybrid' => 'Gabungan',
                default => 'Dokumen',
            },
            'progress' => $progress,
            'completedSteps' => $completedSteps,
            'totalSteps' => $totalSteps,
            'tone' => $tone,
            'headline' => $headline,
            'summary' => $summary,
            'checklist' => $checklist,
            'missingItems' => collect($checklist)
                ->reject(fn (array $item): bool => $item['completed'])
                ->pluck('label')
                ->values()
                ->all(),
            'codePreview' => LetterNumber::query()->whereKey($get('letter_number_id'))->value('nomor') ?? $get('code_number') ?? '—',
            'codePreviewNote' => filled($get('letter_number_id')) || filled($get('code_number'))
                ? 'Dokumen ini diisi ke nomor dokumen tersebut.'
                : 'Pilih nomor dokumen yang sudah diambil, atau ambil nomor baru dengan tombol +.',
            'statusLabel' => match ($status) {
                'approved' => 'Diterbitkan',
                'archived' => 'Diarsipkan',
                default => 'Pending',
            },
            'nextStep' => self::buildNextStepMessage($documentType, $progress, $status),
        ];
    }

    /** Nomor dokumen yang belum diisi dokumen; nomor yang diambil user sendiri tampil lebih dulu. */
    private static function letterNumberOptions(?string $kelompok = null): array
    {
        return LetterNumber::query()
            ->access()
            ->available()
            ->when(isset(DocumentCategory::KELOMPOK[$kelompok]), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('kelompok', $kelompok)))
            ->orderByRaw('created_by = ? desc', [Auth::id()])
            ->orderByDesc('tanggal_surat')
            ->orderByDesc('nomor_urut')
            ->limit(300)
            ->get()
            ->mapWithKeys(fn (LetterNumber $letter) => [$letter->id => $letter->option_label])
            ->all();
    }

    /**
     * Nilai field form yang mengikuti nomor dokumen terpilih: jenis, tanggal, organisasi
     * penerbit, dan judul (bila judul dokumen masih kosong).
     *
     * @return array<string, mixed>
     */
    public static function letterNumberState(mixed $letterNumberId, ?string $currentTitle = null): array
    {
        $letter = filled($letterNumberId) ? LetterNumber::query()->find($letterNumberId) : null;

        if (! $letter) {
            return ['category_id' => null, 'tanggal_surat' => null];
        }

        return [
            'category_id' => $letter->category_id,
            'tanggal_surat' => $letter->tanggal_surat->toDateString(),
            'company_id' => $letter->company_id,
            'department_id' => $letter->department_id,
            'unit_id' => $letter->unit_id,
            'title' => filled($currentTitle) ? $currentTitle : $letter->judul_surat,
        ];
    }

    private static function getStatusHint(string $status): string
    {
        return match ($status) {
            'approved' => 'Dokumen akan langsung aktif setelah disimpan.',
            'archived' => 'Dokumen disimpan sebagai arsip dan tidak masuk proses aktif.',
            default => 'Dokumen disimpan sebagai pending/draft dan bisa diajukan review setelahnya.',
        };
    }

    private static function buildNextStepMessage(string $documentType, int $progress, string $status): string
    {
        if ($progress < 100) {
            return 'Fokuskan pengisian pada item yang masih berwarna abu-abu agar dokumen cepat siap diproses.';
        }

        if ($documentType === 'hybrid') {
            return 'Dokumen gabungan sudah lengkap. Sistem akan menyimpan berkas dan konten formulir dalam satu alur.';
        }

        if ($status === 'archived') {
            return 'Dokumen siap diarsipkan. Pastikan memang belum perlu diajukan ke alur review aktif.';
        }

        return 'Dokumen siap disimpan. Setelah itu Anda bisa lanjut ke proses review dari daftar dokumen.';
    }
}
