<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['is_permanent'] = empty($data['expires_at']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

        // Jika dokumen sebelumnya ditolak, reset kembali ke draft ketika diedit
        if ($this->getRecord()->status === \App\Models\Document::STATUS_REJECTED) {
            $data['status'] = \App\Models\Document::STATUS_DRAFT;
            // Optionally, we could clear notes, but history preserves them anyway.
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var \App\Models\Document $record */
        $record = $this->getRecord();
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($record->status === \App\Models\Document::STATUS_APPROVED && $user?->hasRole(['direktur', 'super_admin']) && ! $record->reviewed_by_direktur) {
            $record->approveByDirektur($user);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
