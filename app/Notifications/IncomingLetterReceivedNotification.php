<?php

namespace App\Notifications;

use App\Models\IncomingLetter;
use Illuminate\Notifications\Notification;

/**
 * Surat masuk untuk unit penerima (lonceng notifikasi). Dikirim langsung tanpa queue,
 * seperti notifikasi lain di DCMS.
 */
class IncomingLetterReceivedNotification extends Notification
{
    public function __construct(public IncomingLetter $letter) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $sifat = IncomingLetter::SIFAT[$this->letter->sifat] ?? null;

        return [
            'type' => 'incoming_letter',
            'incoming_letter_id' => $this->letter->id,
            'title' => 'Surat masuk'.($sifat && $this->letter->sifat !== 'biasa' ? " ({$sifat})" : '').': '.$this->letter->perihal,
            'message' => 'Dari '.$this->letter->pengirim.' · '.$this->letter->nomor_agenda,
            'actions' => [
                ['label' => 'Buka surat', 'url' => url('/admin/surat-masuk/'.$this->letter->id)],
            ],
        ];
    }
}
