<?php

namespace App\Filament\Admin\Resources\Documents\Tables;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentConversionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Relasi yang ditampilkan dimuat sekaligus (tanpa N+1), plus status bookmark user ini.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['category:id,name,prefix', 'department:id,name', 'unit:id,name', 'user:id,name', 'updatedByUser:id,name'])
                ->withExists('rejections')
                ->withCount([
                    'bookmarks as is_bookmarked' => fn (Builder $bookmarkQuery): Builder => $bookmarkQuery
                        ->where('user_id', Auth::id() ?? 0),
                ]))
            ->recordUrl(fn (Document $record): string => DocumentResource::getUrl('view', ['record' => $record]))
            ->columns([
                IconColumn::make('is_bookmarked')
                    ->label('')
                    ->alignCenter()
                    ->boolean()
                    ->trueIcon('heroicon-s-bookmark')
                    ->falseIcon('heroicon-o-bookmark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (Document $record): string => (int) ($record->is_bookmarked ?? 0) > 0
                        ? 'Ada di daftar tersimpan Anda'
                        : 'Belum disimpan')
                    ->width('40px')
                    ->visibleFrom('md'),

                TextColumn::make('title')
                    ->label('Dokumen')
                    ->view('filament.tables.columns.document-mobile-card')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->fullTextSearch($search);
                    })
                    ->sortable()
                    ->grow(),

                TextColumn::make('jenis_surat')
                    ->label('Jenis')
                    ->formatStateUsing(fn (?JenisSurat $state): HtmlString|string => $state ? LetterNumberResource::jenisBadge($state) : '—')
                    ->html()
                    ->tooltip(fn (Document $record): ?string => $record->jenis_surat?->label() ?? $record->category?->name)
                    ->placeholder('—')
                    ->visibleFrom('lg'),

                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->description(fn (Document $record): ?string => $record->department?->name)
                    ->searchable()
                    ->sortable()
                    ->limit(28)
                    ->placeholder('—')
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'draft' => 'heroicon-o-pencil',
                        'pending_kabid', 'pending_direktur' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        'archived' => 'heroicon-o-archive-box',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending_kabid' => 'warning',
                        'pending_direktur' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'pending_kabid' => 'Menunggu Kabid',
                        'pending_direktur' => 'Menunggu Direktur',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'archived' => 'Diarsipkan',
                        default => $state,
                    })
                    ->visibleFrom('md'),

                TextColumn::make('expires_at')
                    ->label('Masa Berlaku')
                    ->badge()
                    ->sortable()
                    ->placeholder('Tidak dibatasi')
                    ->icon(fn (Document $record): ?string => match (true) {
                        blank($record->expires_at) => null,
                        $record->is_expired => 'heroicon-o-exclamation-triangle',
                        $record->is_expiring_soon => 'heroicon-o-clock',
                        default => 'heroicon-o-shield-check',
                    })
                    ->color(fn (Document $record): string => match (true) {
                        blank($record->expires_at) => 'gray',
                        $record->is_expired => 'danger',
                        $record->is_expiring_soon => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state, Document $record): string => match (true) {
                        blank($state) => 'Tidak dibatasi',
                        $record->is_expired => 'Kedaluwarsa',
                        $record->is_expiring_soon => today()->diffInDays($record->expires_at).' hari lagi',
                        default => $record->expires_at->translatedFormat('d M Y'),
                    })
                    ->tooltip(fn (Document $record): ?string => $record->expires_at ? 'Berakhir '.$record->expires_at->translatedFormat('d F Y') : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('document_type')
                    ->label('Tipe')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'file' => 'Berkas',
                        'form' => 'Formulir',
                        'hybrid' => 'Gabungan',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->limit(24)
                    ->description(
                        fn (Document $record): ?string => $record->updated_by && $record->updated_by !== $record->user_id
                            ? 'Diedit: '.($record->updatedByUser?->name ?? '—')
                            : null
                    )
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('version')
                    ->label('Versi')
                    ->badge()
                    ->color(fn (Document $record): string => $record->rejections_exists ? 'warning' : 'gray')
                    ->tooltip(fn (Document $record): ?string => $record->rejections_exists ? 'Pernah ditolak & direvisi' : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('allowedUnits.name')
                    ->label('Akses Unit Khusus')
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->tooltip(fn (Document $record): string => $record->created_at->diffForHumans())
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Document::STATUS_DRAFT => 'Draft',
                        Document::STATUS_PENDING_KABID => 'Menunggu Kabid',
                        Document::STATUS_PENDING_DIREKTUR => 'Menunggu Direktur',
                        Document::STATUS_APPROVED => 'Disetujui',
                        Document::STATUS_REJECTED => 'Ditolak',
                        Document::STATUS_ARCHIVED => 'Diarsipkan',
                    ])
                    ->multiple()
                    ->native(false),

                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->native(false),

                SelectFilter::make('jenis_surat')
                    ->label('Jenis Surat')
                    ->options(JenisSurat::options())
                    ->multiple()
                    ->searchable()
                    ->native(false),

                SelectFilter::make('masa_berlaku')
                    ->label('Masa Berlaku')
                    ->options([
                        'kritis' => 'Berakhir ≤ 7 hari',
                        'segera' => 'Berakhir ≤ 30 hari',
                        'kedaluwarsa' => 'Sudah kedaluwarsa',
                        'berlaku' => 'Masih berlaku',
                        'tanpa_batas' => 'Tidak dibatasi',
                    ])
                    ->native(false)
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'kritis' => $query->expiringSoon(7),
                        'segera' => $query->expiringSoon(30),
                        'kedaluwarsa' => $query->expired(),
                        'berlaku' => $query->whereNotNull('expires_at')->whereDate('expires_at', '>=', today()),
                        'tanpa_batas' => $query->whereNull('expires_at'),
                        default => $query,
                    }),

                SelectFilter::make('department')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('unit')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('periode')
                    ->label('Periode dibuat')
                    ->schema([
                        DatePicker::make('dari')->label('Dibuat dari')->native(false),
                        DatePicker::make('sampai')->label('Sampai')->native(false),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['sampai'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        filled($data['dari'] ?? null) ? 'Dari '.Carbon::parse($data['dari'])->translatedFormat('d M Y') : null,
                        filled($data['sampai'] ?? null) ? 'Sampai '.Carbon::parse($data['sampai'])->translatedFormat('d M Y') : null,
                    ]))),

                Filter::make('bookmarked')
                    ->label('Hanya yang saya simpan')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereHas(
                        'bookmarks',
                        fn (Builder $bookmarkQuery): Builder => $bookmarkQuery->where('user_id', Auth::id() ?? 0),
                    )),

                SelectFilter::make('company')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->preload()
                    ->native(false)
                    ->visible(fn (): bool => (bool) Auth::user()?->hasRole('super_admin')),

                TrashedFilter::make()->label('Dokumen terhapus'),
            ])
            ->recordActions([
                // Tindakan utama alur review tetap terlihat; sisanya di menu ⋯
                Action::make('process')
                    ->label('Proses')
                    ->icon('heroicon-o-bolt')
                    ->button()
                    ->size('xs')
                    ->color('primary')
                    ->modalWidth('lg')
                    ->modalIcon('heroicon-o-bolt')
                    ->modalHeading('Proses Dokumen')
                    ->modalDescription('Pilih tindakan yang ingin dijalankan untuk dokumen ini.')
                    ->modalSubmitActionLabel('Jalankan')
                    ->modalCancelActionLabel('Batal')
                    ->schema(fn (Document $record): array => [
                        Radio::make('process_action')
                            ->label('Tindakan')
                            ->options(self::getProcessOptions($record))
                            ->descriptions(self::getProcessDescriptions($record))
                            ->required(),
                    ])
                    ->action(function (array $data, Document $record): void {
                        self::handleProcessAction($record, $data['process_action']);
                    })
                    ->visible(fn (Document $record): bool => self::hasProcessActions($record))
                    ->extraAttributes(['class' => 'hidden md:inline-flex', 'data-process-action' => 'true']),

                ActionGroup::make([
                    ViewAction::make()->label('Lihat detail'),
                    EditAction::make()->label('Edit'),

                    Action::make('toggle_bookmark')
                        ->icon(fn (Document $record): string => (int) ($record->is_bookmarked ?? 0) > 0
                            ? 'heroicon-s-bookmark'
                            : 'heroicon-o-bookmark')
                        ->label(fn (Document $record): string => (int) ($record->is_bookmarked ?? 0) > 0
                            ? 'Hapus dari tersimpan'
                            : 'Simpan untuk akses cepat')
                        ->action(function (Document $record): void {
                            $isSaved = $record->toggleBookmark();

                            Notification::make()
                                ->title($isSaved ? 'Dokumen disimpan' : 'Bookmark dihapus')
                                ->body($isSaved
                                    ? 'Dokumen masuk ke daftar tersimpan Anda untuk akses cepat.'
                                    : 'Dokumen dihapus dari daftar tersimpan Anda.')
                                ->success()
                                ->send();
                        }),

                    Action::make('download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label('Unduh berkas asli')
                        ->url(function (Document $record): string {
                            /** @var FilesystemAdapter $disk */
                            $disk = Storage::disk('documents');

                            return $disk->url((string) $record->file_path);
                        })
                        ->openUrlInNewTab()
                        ->visible(fn (Document $record) => ! empty($record->file_path)),

                    Action::make('convert')
                        ->label('Unduh sebagai PDF / Word')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->modalWidth('md')
                        ->modalIcon('heroicon-o-arrow-path-rounded-square')
                        ->modalIconColor('info')
                        ->modalHeading('Konversi & Unduh Dokumen')
                        ->modalDescription('Pilih format berkas yang ingin Anda unduh.')
                        ->modalSubmitAction(fn ($action) => $action->color('info'))
                        ->modalSubmitActionLabel('Unduh Dokumen')
                        ->modalCancelActionLabel('Batal')
                        ->schema(function (Document $record): array {
                            $canWord = in_array($record->document_type, ['form', 'hybrid']) || in_array(strtolower(pathinfo($record->file_path ?? '', PATHINFO_EXTENSION)), ['doc', 'docx']);

                            $options = ['pdf' => 'Unduh sebagai PDF (.pdf)'];
                            $descriptions = ['pdf' => 'Konversi dan unduh dokumen dalam format PDF'];

                            if ($canWord) {
                                $options['word'] = 'Unduh sebagai Word (.docx)';
                                $descriptions['word'] = 'Konversi dan unduh dokumen dalam format Word Docx';
                            }

                            return [
                                Radio::make('format')
                                    ->label('Pilih Format')
                                    ->options($options)
                                    ->descriptions($descriptions)
                                    ->default('pdf')
                                    ->required(),
                            ];
                        })
                        ->action(function (array $data, Document $record) {
                            if (($data['format'] ?? 'pdf') === 'word') {
                                return DocumentConversionService::downloadAsWord($record);
                            }

                            return DocumentConversionService::downloadAsPdf($record);
                        }),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi lainnya')
                    ->extraAttributes(['class' => 'hidden md:inline-flex']),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('Tidak ada dokumen')
            ->emptyStateDescription('Tidak ada dokumen yang cocok. Coba ganti tab atau hapus filter yang aktif.')
            ->defaultSort('created_at', 'desc');
    }

    public static function hasProcessActions(Document $record): bool
    {
        return self::getProcessOptions($record) !== [];
    }

    public static function getProcessOptions(Document $record): array
    {
        $options = [];
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $options;
        }

        // Dokumen rejected/draft bisa diajukan untuk review oleh pemiliknya
        if ($record->status === 'draft' && $record->user_id === $user->id) {
            $wasRejected = $record->rejections()->exists();
            $options['submit_review'] = $wasRejected ? 'Ajukan Ulang Review' : 'Ajukan ke Review';
        }

        // Kabid / Super Admin bisa ACC atau Tolak dokumen yang pending_kabid
        if ($record->status === Document::STATUS_PENDING_KABID && ($user->hasRole(['kabid', 'super_admin']))) {
            $options['approve_kabid'] = 'Setujui Dokumen (Teruskan ke Direktur)';
            $options['reject_kabid'] = 'Tolak Dokumen';
        }

        // Direktur / Super Admin bisa ACC atau Tolak dokumen yang pending_direktur maupun pending_kabid
        if (in_array($record->status, [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR]) && ($user->hasRole(['direktur', 'super_admin']))) {
            $options['approve_direktur'] = 'Setujui Dokumen (Final)';
            $options['reject_direktur'] = 'Tolak Dokumen';
        }

        // Super admin bisa setujui/tolak langsung draft
        if ($user->hasRole('super_admin') && $record->status === 'draft') {
            $options['approve'] = 'Setujui Langsung';
            $options['reject'] = 'Tolak Dokumen';
        }

        return $options;
    }

    protected static function getProcessDescriptions(Document $record): array
    {
        $descriptions = [];
        /** @var User|null $user */
        $user = Auth::user();

        if ($record->status === 'draft') {
            $wasRejected = $record->rejections()->exists();
            $descriptions['submit_review'] = $wasRejected
                ? 'Kirim kembali dokumen yang telah diperbaiki ke Kepala Bidang untuk direview ulang.'
                : 'Kirim dokumen ke Kepala Bidang untuk direview sesuai alur.';
        }

        if ($record->status === Document::STATUS_PENDING_KABID) {
            $descriptions['approve_kabid'] = 'Setujui dokumen ini dan teruskan ke Direktur untuk persetujuan akhir.';
            $descriptions['reject_kabid'] = 'Tolak dokumen ini dan berikan alasan penolakan kepada pembuat dokumen.';
        }

        if ($record->status === Document::STATUS_PENDING_DIREKTUR) {
            $descriptions['approve_direktur'] = 'Berikan persetujuan akhir untuk mengaktifkan/menerbitkan dokumen ini.';
            $descriptions['reject_direktur'] = 'Tolak dokumen ini pada tahap persetujuan akhir.';
        }

        if ($user?->hasRole('super_admin') && $record->status === 'draft') {
            $descriptions['approve'] = 'Lewati antrean review dan langsung tandai dokumen sebagai disetujui.';
            $descriptions['reject'] = 'Tandai dokumen sebagai ditolak tanpa membuka dropdown tambahan.';
        }

        return $descriptions;
    }

    protected static function handleProcessAction(Document $record, string $processAction): void
    {
        /** @var User $user */
        $user = Auth::user();

        match ($processAction) {
            'submit_review' => $record->submitForReview(),
            'approve_kabid' => $record->approveByKabid($user),
            'reject_kabid' => $record->rejectByKabid($user, 'Ditolak dari menu proses'),
            'approve_direktur' => $record->approveByDirektur($user),
            'reject_direktur' => $record->rejectByDirektur($user, 'Ditolak dari menu proses'),
            'approve' => $record->update([
                'status' => 'approved',
                'reviewed_by_direktur' => Auth::id(),
                'direktur_reviewed_at' => now(),
            ]),
            'reject' => $record->update([
                'status' => 'rejected',
            ]),
            default => null,
        };

        Notification::make()
            ->title(match ($processAction) {
                'submit_review' => 'Dokumen diajukan untuk review',
                'approve_kabid' => 'Dokumen disetujui Kabid & diteruskan ke Direktur',
                'reject_kabid' => 'Dokumen ditolak Kabid',
                'approve_direktur' => 'Dokumen disetujui final oleh Direktur',
                'reject_direktur' => 'Dokumen ditolak oleh Direktur',
                'approve' => 'Dokumen disetujui',
                'reject' => 'Dokumen ditolak',
                default => 'Proses dokumen selesai',
            })
            ->success()
            ->send();
    }
}
