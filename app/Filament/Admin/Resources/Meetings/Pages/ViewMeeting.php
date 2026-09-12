<?php

namespace App\Filament\Admin\Resources\Meetings\Pages;

use App\Filament\Admin\Resources\Meetings\MeetingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    public function getTitle(): string
    {
        return 'Detail Rapat';
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan lengkap rapat, notulensi, dan dokumentasi.';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('tutorial')
                ->label('Panduan')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Petunjuk Notulensi')
                ->modalWidth('4xl')
                ->modalContent(view('filament.tutorial-modal', ['image' => 'notulen.jpg']))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
            \Filament\Actions\Action::make('view_notulensi_pdf')
                ->label('Lihat PDF Notulensi')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->url(fn ($record) => route('notulen.view', $record->id))
                ->openUrlInNewTab()
                ->visible(fn ($record) => filled($record->file_path)),
            EditAction::make()
                ->label('Ubah Rapat')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
