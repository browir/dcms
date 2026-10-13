<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Notifications\Notification;

class MeetingInvitationNotification extends Notification
{
    // Sengaja TIDAK implements ShouldQueue: server produksi memakai QUEUE_CONNECTION=database
    // tapi tidak ada queue worker yang berjalan, jadi notifikasi ber-ShouldQueue hanya
    // menumpuk di tabel `jobs` dan tidak pernah benar-benar terkirim. Kirim langsung (sync)
    // saat notify() dipanggil supaya pasti terkirim tanpa bergantung pada worker.

    public function __construct(
        public Meeting $meeting
    ) {}

    public function via(object $notifiable): array
    {
        // Tanpa 'mail': email undangan sudah dikirim terpisah lewat MeetingInvitationMail
        // di CreateMeeting::afterCreate(). Kalau 'mail' ditambahkan di sini, peserta dapat email ganda.
        return ['database', \App\Notifications\Channels\N8nWhatsAppChannel::class];
    }

    /**
     * Payload WhatsApp yang dikirim ke webhook n8n.
     */
    public function toN8n(object $notifiable): array
    {
        return [
            'type' => 'meeting_invitation',
            'title' => 'Undangan Rapat',
            'message' => "Halo {$notifiable->name}, Anda diundang ke rapat \"{$this->meeting->title}\""
                .' pada '.($this->meeting->meeting_date ? $this->meeting->meeting_date->format('d M Y, H:i') : '-')
                .'. Lokasi: '.($this->meeting->location ?? 'Online').'.',
            'url' => url('/admin/meetings/'.$this->meeting->id),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'meeting_invitation',
            'meeting_id' => $this->meeting->id,
            'title' => 'Undangan Rapat: '.$this->meeting->title,
            'message' => 'Anda diundang ke rapat "'.$this->meeting->title.'" pada '.($this->meeting->meeting_date ? $this->meeting->meeting_date->format('d M Y, H:i') : '-'),
            'location' => $this->meeting->location ?? 'Online',
        ];
    }
}
