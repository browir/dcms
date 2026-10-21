<?php

namespace App\Notifications;

use App\Models\Agenda;
use Illuminate\Notifications\Notification;

/**
 * Pengumuman kegiatan unit di aplikasi (lonceng notifikasi). Dikirim langsung (tanpa queue)
 * seperti notifikasi lain di DCMS — server produksi tidak menjalankan queue worker.
 * WhatsApp untuk pengumuman dikirim ke GRUP (AgendaAnnouncer), bukan per orang.
 */
class AgendaAnnouncedNotification extends Notification
{
    public function __construct(public Agenda $agenda) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'agenda_announced',
            'agenda_id' => $this->agenda->id,
            'title' => 'Kegiatan: '.$this->agenda->title,
            'message' => $this->agenda->scheduleLabel().' · '.$this->agenda->placeName(),
            'letter_number' => $this->agenda->letterNumber?->nomor,
            'actions' => [
                ['label' => 'Lihat kegiatan', 'url' => url('/admin/kegiatan/'.$this->agenda->id)],
            ],
        ];
    }
}
