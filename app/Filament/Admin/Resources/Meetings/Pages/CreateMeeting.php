<?php

namespace App\Filament\Admin\Resources\Meetings\Pages;

use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Mail\MeetingInvitationMail;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Pre-fill the date part of date_time from the calendar widget shortcut.
     * Time is intentionally left empty so the user must still enter the start time.
     */
    protected function fillForm(): void
    {
        parent::fillForm();

        $dateParam = request()->query('date_time');

        if ($dateParam) {
            try {
                // Parse the clicked date — keep only the date, set time to 00:00
                // User still has to fill in the correct start time
                $date = Carbon::parse($dateParam)->startOfDay()->format('Y-m-d\TH:i');

                $this->form->fill([
                    'date_time' => $date,
                ]);
            } catch (\Throwable $e) {
                // Silently ignore parse errors
            }
        }
    }

    public function getTitle(): string
    {
        return 'Tambah Rapat';
    }

    public function getSubheading(): ?string
    {
        return 'Lengkapi informasi rapat, jadwal & lokasi, lalu pilih peserta. Undangan email dikirim otomatis setelah rapat dibuat.';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('tutorial')
                ->label('Panduan')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Petunjuk Tambah Rapat')
                ->modalWidth('4xl')
                ->modalContent(view('filament.tutorial-modal', ['image' => 'tambah.jpg']))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Buat');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function afterCreate(): void
    {
        $meeting = $this->record;
        $creatorId = auth()->id();

        // Kirim notifikasi ke creator sebagai konfirmasi di bell
        if ($creatorId) {
            $creator = \App\Models\User::find($creatorId);
            if ($creator) {
                try {
                    $creator->notify(new \App\Notifications\MeetingInvitationNotification($meeting));
                } catch (\Throwable $e) {
                    logger()->error('Failed sending notification to creator: '.$e->getMessage());
                }
            }
        }

        // Kirim notifikasi ke setiap peserta (kecuali creator agar tidak dobel)
        foreach ($meeting->participants as $user) {
            if ($user->id === $creatorId) {
                continue; // sudah dikirim di atas
            }

            try {
                Mail::to($user->email)->send(new MeetingInvitationMail($meeting));
            } catch (\Throwable $e) {
                logger()->error('Failed sending meeting email to '.$user->email.': '.$e->getMessage());
            }

            try {
                $user->notify(new \App\Notifications\MeetingInvitationNotification($meeting));
            } catch (\Throwable $e) {
                logger()->error('Failed sending meeting notification to '.$user->email.': '.$e->getMessage());
            }
        }
    }
}
