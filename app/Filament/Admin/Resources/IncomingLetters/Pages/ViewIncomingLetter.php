<?php

namespace App\Filament\Admin\Resources\IncomingLetters\Pages;

use App\Filament\Admin\Resources\IncomingLetters\IncomingLetterResource;
use App\Models\IncomingLetter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewIncomingLetter extends ViewRecord
{
    protected static string $resource = IncomingLetterResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var User $user */
        $user = Auth::user();
        /** @var IncomingLetter $letter */
        $letter = $this->getRecord();
        // Dibuka anggota unit tujuan → status "Sudah dibaca".
        $letter->markReadBy($user);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->perihal;
    }

    public function getSubheading(): ?string
    {
        /** @var IncomingLetter $letter */
        $letter = $this->getRecord();

        return "{$letter->nomor_agenda} · dari {$letter->pengirim}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('file')
                ->label('Buka Scan Surat')
                ->icon('heroicon-m-paper-clip')
                ->visible(fn (IncomingLetter $record): bool => filled($record->file_path))
                ->url(fn (IncomingLetter $record): string => route('surat-masuk.file', $record), shouldOpenInNewTab: true),
            Action::make('complete')
                ->label('Tandai Selesai')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Surat sudah ditindaklanjuti oleh unit tujuan.')
                ->authorize('complete')
                ->action(function (IncomingLetter $record): void {
                    $record->markCompleted();
                    Notification::make()->title('Surat masuk ditandai selesai')->success()->send();
                    $this->refreshFormData(['status', 'completed_at', 'read_at']);
                }),
            EditAction::make()->color('gray'),
        ];
    }
}
