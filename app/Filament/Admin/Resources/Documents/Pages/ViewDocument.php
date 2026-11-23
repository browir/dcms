<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Models\DocumentChangeRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
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

            /* ── Submit / Re-submit for Review ── */
            Action::make('submit_review')
                ->label(fn (): string => $this->record->rejections()->exists() ? 'Ajukan Ulang Review' : 'Kirim untuk Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->record->rejections()->exists()
                    ? 'Ajukan Ulang Dokumen yang Diperbaiki?'
                    : 'Kirim Dokumen untuk Review?')
                ->modalDescription(function (): string {
                    $isSekretaris = auth()->user()?->hasRole('Sekretaris') || $this->record->user?->hasRole('Sekretaris');
                    $targetRole = $isSekretaris ? 'Direktur' : 'Kepala Bidang';

                    return $this->record->rejections()->exists()
                        ? "Dokumen versi terbaru Anda akan dikirim ulang ke {$targetRole} untuk direview. Pastikan semua perbaikan sudah dilakukan."
                        : "Dokumen akan dikirim ke {$targetRole} untuk direview. Pastikan dokumen sudah lengkap.";
                })
                ->modalIcon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => $this->record->status === 'draft')
                ->action(function () {
                    $this->record->submitForReview();
                    $wasRejected = $this->record->rejections()->exists();
                    $isSekretaris = auth()->user()?->hasRole('Sekretaris') || $this->record->user?->hasRole('Sekretaris');
                    $targetRole = $isSekretaris ? 'Direktur' : 'Kabid';

                    Notification::make()
                        ->title($wasRejected ? 'Dokumen diajukan ulang' : 'Dokumen dikirim')
                        ->body($wasRejected
                            ? "Dokumen hasil perbaikan berhasil diajukan ulang ke {$targetRole}."
                            : "Dokumen berhasil dikirim untuk review {$targetRole}.")
                        ->success()
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
