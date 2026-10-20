<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Schemas\DocumentForm;
use App\Models\Document;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('guide')
                ->label('Panduan Tambah Dokumen')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Panduan Cara Menambahkan Dokumen')
                ->modalIcon('heroicon-o-information-circle')
                ->modalWidth('3xl')
                ->modalContent(view('filament.admin.resources.documents.partials.create-document-guide-modal'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    /**
     * Dibuka dari tombol "Isi Dokumen" (?nomor_surat=ID): nomor sudah terpilih lewat default,
     * jadi isi juga jenis, tanggal, organisasi, dan judul yang mengikuti nomor itu.
     */
    protected function afterFill(): void
    {
        if (filled($this->data['letter_number_id'] ?? null)) {
            $this->data = [
                ...$this->data,
                ...DocumentForm::letterNumberState($this->data['letter_number_id'], $this->data['title'] ?? null),
            ];
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user) {
            if (empty($data['company_id']) && $user->company_id) {
                $data['company_id'] = $user->company_id;
            }
            if (empty($data['department_id']) && $user->department_id) {
                $data['department_id'] = $user->department_id;
            }
            if (empty($data['unit_id']) && $user->unit_id) {
                $data['unit_id'] = $user->unit_id;
            }
        }

        // Assign file hash if computed during upload
        if (isset($data['_computed_file_hash'])) {
            $data['file_hash'] = $data['_computed_file_hash'];
            unset($data['_computed_file_hash']);
        }

        // Handle 'is_permanent' logic
        if (isset($data['is_permanent'])) {
            if ($data['is_permanent']) {
                $data['expires_at'] = null;
            }
            unset($data['is_permanent']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Document $record */
        $record = $this->getRecord();
        if ($record->status === Document::STATUS_PENDING_KABID) {
            $record->submitForReview();
        }
    }
}
