<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Schemas\DocumentForm;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    /** Dibuka dari menu Dokumen Resmi / Surat Keluar: pilihan nomor & kategori ikut dibatasi. */
    #[Url]
    public ?string $kelompok = null;

    public function getTitle(): string
    {
        return $this->kelompok === DocumentCategory::KELOMPOK_SURAT_KELUAR ? 'Buat Surat Keluar' : 'Buat Dokumen';
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
        /** @var User|null $user */
        $user = Auth::user();

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
        if (in_array($record->status, [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR])) {
            $record->submitForReview();
        }
    }
}
