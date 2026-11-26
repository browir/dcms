<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Models\DocumentChangeRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    // ── Catat aktivitas "melihat" dokumen ke audit log ────────────────────────
    public function mount(int|string $record): void
    {
        parent::mount($record);

        activity('dokumen')
            ->performedOn($this->record)
            ->causedBy(Auth::user())
            ->withProperties([
                'nomor_surat' => $this->record->nomor_surat,
                'judul'       => $this->record->judul,
                'ip'          => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ])
            ->event('viewed')
            ->log('Melihat dokumen');
    }

    protected function getHeaderActions(): array
    {
        return [

            /* ── Unduh PDF Resmi ── */
            Action::make('download_pdf')
                ->label('Unduh PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->document_type, ['form', 'hybrid']) || str_ends_with(strtolower($this->record->file_path ?? ''), '.pdf'))
                ->action(fn () => \App\Services\DocumentConversionService::downloadAsPdf($this->record)),

            /* ── Unduh Word ── */
            Action::make('download_word')
                ->label('Unduh Word')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn (): bool => in_array($this->record->document_type, ['form', 'hybrid']))
                ->action(fn () => \App\Services\DocumentConversionService::downloadAsWord($this->record)),

            /* ── Download File Asli (Khusus Berkas Unggahan) ── */
            Action::make('download')
                ->label('Unduh Berkas Asli')
                ->icon('heroicon-o-paper-clip')
                ->color('gray')
                ->visible(fn (): bool => filled($this->record->file_path))
                ->action(function () {
                    $path = $this->record->file_path;
                    if (Storage::disk('documents')->exists($path)) {
                        // Catat aktivitas unduh ke audit log
                        activity('dokumen')
                            ->performedOn($this->record)
                            ->causedBy(Auth::user())
                            ->withProperties([
                                'nomor_surat' => $this->record->nomor_surat,
                                'judul'       => $this->record->judul,
                                'file'        => basename($path),
                                'ip'          => request()->ip(),
                                'user_agent'  => request()->userAgent(),
                            ])
                            ->event('downloaded')
                            ->log('Mengunduh berkas dokumen');

                        return Storage::disk('documents')->download($path);
                    }
                    Notification::make()
                        ->title('File tidak ditemukan')
                        ->body('Berkas dokumen tidak tersedia di server.')
                        ->danger()
                        ->send();
                }),

            /* ── Ubah Perihal Surat ── */
            Action::make('edit_perihal')
                ->label('Ubah Perihal')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn (): bool => filled($this->record->letter_number_id) && (
                    Auth::user()?->can('edit_letter_perihal') || (method_exists(Auth::user(), 'hasRole') && Auth::user()?->hasRole('super_admin'))
                ))
                ->modalHeading('Ubah Perihal Surat')
                ->modalDescription('Perbarui informasi perihal surat resmi yang sudah diterbitkan.')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Simpan Perubahan')
                ->form([
                    TextInput::make('nomor_surat_display')
                        ->label('Nomor Surat')
                        ->default(fn () => $this->record->letterNumber?->nomor)
                        ->disabled(),
                    TextInput::make('judul_surat_display')
                        ->label('Judul Surat')
                        ->default(fn () => $this->record->letterNumber?->judul_surat)
                        ->disabled(),
                    Textarea::make('perihal')
                        ->label('Perihal Surat')
                        ->default(fn () => $this->record->letterNumber?->perihal)
                        ->placeholder('Masukkan perihal surat...')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $letterNumber = $this->record->letterNumber;
                    if (! $letterNumber) {
                        return;
                    }

                    $oldPerihal = $letterNumber->perihal;
                    $letterNumber->update([
                        'perihal' => $data['perihal'],
                    ]);

                    activity('dokumen')
                        ->performedOn($this->record)
                        ->causedBy(Auth::user())
                        ->withProperties([
                            'nomor_surat' => $letterNumber->nomor,
                            'old_perihal' => $oldPerihal,
                            'new_perihal' => $data['perihal'],
                        ])
                        ->event('updated')
                        ->log('Mengubah perihal surat resmi');

                    Notification::make()
                        ->title('Perihal Surat Berhasil Diperbarui')
                        ->body("Perihal untuk nomor {$letterNumber->nomor} telah diperbarui.")
                        ->success()
                        ->send();
                }),

            /* ── Terbitkan Langsung (Khusus Direktur / Super Admin) ── */
            Action::make('direct_publish')
                ->label('Terbitkan')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Terbitkan Dokumen / Surat?')
                ->modalDescription('Dokumen akan langsung diterbitkan tanpa proses review dan tanda tangan digital Anda akan disematkan secara otomatis.')
                ->modalIcon('heroicon-o-check-circle')
                ->visible(fn (): bool => (auth()->user()?->hasRole(['direktur', 'super_admin']) ?? false)
                    && in_array($this->record->status, [
                        \App\Models\Document::STATUS_DRAFT,
                        \App\Models\Document::STATUS_PENDING_KABID,
                        \App\Models\Document::STATUS_PENDING_DIREKTUR,
                    ]))
                ->action(function () {
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    $this->record->approveByDirektur($user);

                    if (in_array($this->record->document_type, ['form', 'hybrid']) && $this->record->content) {
                        $this->record->generateFileFromContent();
                    }

                    Notification::make()
                        ->title('Dokumen Berhasil Diterbitkan')
                        ->body('Dokumen telah diterbitkan dan tanda tangan digital Anda telah disematkan.')
                        ->success()
                        ->send();
                }),

            /* ── Submit / Re-submit for Review (Selain Direktur) ── */
            Action::make('submit_review')
                ->label(fn (): string => $this->record->rejections()->exists() ? 'Ajukan Ulang Review' : 'Kirim untuk Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->modalWidth('lg')
                ->modalHeading(fn (): string => $this->record->rejections()->exists()
                    ? 'Ajukan Ulang Dokumen yang Diperbaiki?'
                    : 'Kirim Dokumen untuk Review?')
                ->modalDescription('Pilih apakah dokumen diteruskan mengikuti alur otomatis atau langsung ke orang tertentu secara manual.')
                ->modalSubmitActionLabel('Kirim Review')
                ->modalIcon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => $this->record->status === 'draft' && ! (auth()->user()?->hasRole('direktur') ?? false))
                ->form([
                    Radio::make('review_mode')
                        ->label('Tujuan Penerusan Review')
                        ->options(function (): array {
                            $isSekretaris = auth()->user()?->hasRole('Sekretaris') || $this->record->user?->hasRole('Sekretaris');
                            $autoLabel = $isSekretaris
                                ? 'Otomatis sesuai alur (ke Direktur)'
                                : 'Otomatis sesuai alur (ke Kepala Bidang)';

                            return [
                                'auto' => $autoLabel,
                                'manual' => 'Pilih Orang Manual',
                            ];
                        })
                        ->default('auto')
                        ->live()
                        ->required(),

                    Select::make('reviewer_id')
                        ->label('Pilih Peninjau (Reviewer)')
                        ->placeholder('Cari dan pilih orang yang akan mereview...')
                        ->options(fn (): array => \App\Models\Document::reviewerOptions(Auth::id()))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn ($get): bool => $get('review_mode') === 'manual')
                        ->required(fn ($get): bool => $get('review_mode') === 'manual')
                        ->helperText('Surat/dokumen akan langsung diteruskan dan dinotifikasikan ke orang yang Anda pilih.'),
                ])
                ->action(function (array $data) {
                    $manualReviewerId = ($data['review_mode'] ?? 'auto') === 'manual'
                        ? (int) ($data['reviewer_id'] ?? 0)
                        : null;

                    $this->record->submitForReview($manualReviewerId ?: null);
                    $wasRejected = $this->record->rejections()->exists();

                    if ($manualReviewerId) {
                        $reviewerName = \App\Models\User::find($manualReviewerId)?->name ?? 'peninjau terpilih';
                        $targetLabel = $reviewerName;
                    } else {
                        $isSekretaris = auth()->user()?->hasRole('Sekretaris') || $this->record->user?->hasRole('Sekretaris');
                        $targetLabel = $isSekretaris ? 'Direktur' : 'Kabid';
                    }

                    Notification::make()
                        ->title($wasRejected ? 'Dokumen diajukan ulang' : 'Dokumen dikirim')
                        ->body($wasRejected
                            ? "Dokumen hasil perbaikan berhasil diajukan ulang ke {$targetLabel}."
                            : "Dokumen berhasil dikirim untuk review {$targetLabel}.")
                        ->success()
                        ->send();
                    $this->refreshFormData(['status', 'reviewer_id']);
                }),

            /* ── Setujui / Tolak untuk Peninjau Manual yang Ditunjuk ── */
            Action::make('approve_manual_review')
                ->label('Setujui Review')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Dokumen / Surat?')
                ->modalDescription(fn (): string => Auth::user()?->hasRole('direktur') || $this->record->status === \App\Models\Document::STATUS_PENDING_DIREKTUR
                    ? 'Dokumen akan disetujui final dan diterbitkan.'
                    : 'Dokumen akan disetujui dan diteruskan ke Direktur untuk persetujuan akhir.')
                ->form([
                    Textarea::make('notes')
                        ->label('Catatan Review (Opsional)')
                        ->rows(3),
                ])
                ->visible(fn (): bool => $this->record->reviewer_id === Auth::id()
                    && in_array($this->record->status, [
                        \App\Models\Document::STATUS_PENDING_KABID,
                        \App\Models\Document::STATUS_PENDING_DIREKTUR,
                    ]))
                ->action(function (array $data) {
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    if ($user->hasRole('direktur') || $this->record->status === \App\Models\Document::STATUS_PENDING_DIREKTUR) {
                        $this->record->approveByDirektur($user, $data['notes'] ?? null);
                    } else {
                        $this->record->approveByKabid($user, $data['notes'] ?? null);
                    }

                    Notification::make()
                        ->title('Review Disetujui')
                        ->body('Dokumen berhasil disetujui.')
                        ->success()
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('reject_manual_review')
                ->label('Tolak Review')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Dokumen / Surat?')
                ->form([
                    Textarea::make('notes')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn (): bool => $this->record->reviewer_id === Auth::id()
                    && in_array($this->record->status, [
                        \App\Models\Document::STATUS_PENDING_KABID,
                        \App\Models\Document::STATUS_PENDING_DIREKTUR,
                    ]))
                ->action(function (array $data) {
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    if ($user->hasRole('direktur') || $this->record->status === \App\Models\Document::STATUS_PENDING_DIREKTUR) {
                        $this->record->rejectByDirektur($user, $data['notes']);
                    } else {
                        $this->record->rejectByKabid($user, $data['notes']);
                    }

                    Notification::make()
                        ->title('Dokumen Ditolak')
                        ->body('Dokumen dikembalikan ke pembuat dengan catatan penolakan.')
                        ->danger()
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            /* ── Ajukan Revisi ── */
            Action::make('change_request')
                ->label('Ajukan Revisi')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->modalWidth('lg')
                ->modalIcon('heroicon-o-pencil-square')
                ->modalHeading('Ajukan Usulan Revisi SOP')
                ->modalDescription('Isi formulir berikut untuk mengajukan usulan perubahan pada dokumen ini.')
                ->modalSubmitActionLabel('Kirim Usulan')
                ->form([
                    Textarea::make('proposed_change')
                        ->label('Usulan Perubahan')
                        ->placeholder('Jelaskan perubahan yang Anda usulkan...')
                        ->required()
                        ->rows(3),

                    \Filament\Forms\Components\FileUpload::make('attachment_path')
                        ->label('Lampiran (Opsional)')
                        ->disk('public')
                        ->directory('change-requests')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $cr = DocumentChangeRequest::create([
                        'document_id' => $this->record->id,
                        'user_id' => Auth::id(),
                        'chapter_clause' => '-',
                        'existing_condition' => '',
                        'proposed_change' => $data['proposed_change'],
                        'attachment_path' => $data['attachment_path'] ?? null,
                        'status' => 'pending',
                    ]);

                    // Notify admins
                    \App\Models\User::role(['super_admin', 'kabid'])->each(function ($admin) use ($cr) {
                        try {
                            $admin->notify(new \App\Notifications\ChangeRequestSubmittedNotification($cr));
                        } catch (\Throwable $e) {
                            logger()->error('Failed to send ChangeRequestSubmittedNotification: '.$e->getMessage());
                        }
                    });

                    Notification::make()
                        ->title('Usulan Revisi Terkirim')
                        ->body('Usulan revisi Anda telah dikirimkan kepada administrator untuk ditinjau.')
                        ->success()
                        ->send();
                }),

            /* ── Edit ── */
            EditAction::make()
                ->label('Edit Dokumen')
                ->icon('heroicon-o-pencil-square')
                ->color('primary'),

            /* ── Hapus ── */
            DeleteAction::make()
                ->label('Hapus')
                ->icon('heroicon-o-trash'),

            RestoreAction::make()
                ->label('Pulihkan')
                ->icon('heroicon-o-arrow-uturn-left'),

            ForceDeleteAction::make()
                ->label('Hapus Permanen')
                ->icon('heroicon-o-trash'),

        ];
    }

    public function getViewData(): array
    {
        return [
            'documentId' => $this->record->id,
            'rejectionHistory' => $this->record->rejections()->with('user')->get(),
            'kabidNotes' => $this->record->kabid_notes,
            'direkturNotes' => $this->record->direktur_notes,
            'version' => $this->record->version,
        ];
    }
}
