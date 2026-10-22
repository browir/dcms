<?php

namespace App\Filament\Admin\Resources\IncomingLetters\Pages;

use App\Filament\Admin\Resources\IncomingLetters\IncomingLetterResource;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Notifications\IncomingLetterReceivedNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CreateIncomingLetter extends CreateRecord
{
    protected static string $resource = IncomingLetterResource::class;

    protected static ?string $title = 'Catat Surat Masuk';

    protected function getCreatedNotificationTitle(): ?string
    {
        /** @var IncomingLetter $letter */
        $letter = $this->getRecord();

        return "Surat masuk tercatat: {$letter->nomor_agenda}";
    }

    /** Beritahu anggota unit tujuan (selain pencatat). */
    protected function afterCreate(): void
    {
        /** @var IncomingLetter $letter */
        $letter = $this->getRecord();

        $recipients = User::query()
            ->where('unit_id', $letter->unit_id)
            ->where('is_active', true)
            ->whereKeyNot(Auth::id())
            ->get();

        Notification::send($recipients, new IncomingLetterReceivedNotification($letter));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
