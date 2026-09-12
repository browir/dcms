<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingReminderNotification extends Notification
{
    // Sengaja TIDAK implements ShouldQueue: server produksi memakai QUEUE_CONNECTION=database
    // tapi tidak ada queue worker yang berjalan, jadi notifikasi ber-ShouldQueue hanya
    // menumpuk di tabel `jobs` dan tidak pernah benar-benar terkirim.

    public function __construct(
        public Meeting $meeting
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', \App\Notifications\Channels\N8nWhatsAppChannel::class];
    }

    /**
     * Payload WhatsApp yang dikirim ke webhook n8n.
     */
    public function toN8n(object $notifiable): array
    {
        return [
            'type' => 'meeting_reminder',
            'title' => 'Pengingat Rapat',
            'message' => "Halo {$notifiable->name}, pengingat rapat \"{$this->meeting->title}\""
                .' dijadwalkan pada '.optional($this->meeting->meeting_date)->format('d M Y, H:i')
                .'. Lokasi: '.($this->meeting->location ?? 'Online').'.',
            'url' => route('filament.admin.resources.meetings.index'),
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'meeting_reminder',
            'title' => 'Pengingat Rapat: '.$this->meeting->title,
            'message' => 'Rapat "'.$this->meeting->title.'" dijadwalkan pada '.optional($this->meeting->meeting_date)->format('d M Y, H:i').'.',
            'meeting_id' => $this->meeting->id,
            'url' => route('filament.admin.resources.meetings.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengingat Meeting: '.$this->meeting->title)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Ini adalah pengingat untuk meeting:')
            ->line('**'.$this->meeting->title.'**')
            ->line('**Tanggal:** '.optional($this->meeting->meeting_date)->format('d F Y H:i'))
            ->line('**Lokasi:** '.($this->meeting->location ?? 'Online'))
            ->action('Lihat Detail Meeting', route('filament.admin.resources.meetings.index'))
            ->line('Meeting akan segera dimulai!')
            ->salutation('Terima kasih');
    }
}
