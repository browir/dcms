<?php

namespace App\Notifications;

use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke penerima terusan surat masuk (disposisi).
 */
class IncomingLetterForwardedNotification extends Notification
{
    public function __construct(
        public IncomingLetter $letter,
        public User $forwardedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'               => 'incoming_letter_forwarded',
            'incoming_letter_id' => $this->letter->id,
            'title'              => 'Surat masuk diteruskan ke Anda: ' . $this->letter->perihal,
            'message'            => 'Diteruskan oleh ' . $this->forwardedBy->name
                . ' · ' . $this->letter->nomor_agenda
                . ($this->letter->forward_notes ? ' — "' . $this->letter->forward_notes . '"' : ''),
            'actions' => [
                ['label' => 'Buka surat', 'url' => url('/admin/surat-masuk/' . $this->letter->id)],
            ],
        ];
    }
}

