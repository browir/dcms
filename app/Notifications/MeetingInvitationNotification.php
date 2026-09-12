<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Notifications\Messages\MailMessage;
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
        // 'database' duluan supaya notifikasi bell tetap masuk meski 'mail' gagal
        // (Laravel berhenti ke channel berikutnya kalau satu channel melempar exception).
        return ['database', \App\Notifications\Channels\N8nWhatsAppChannel::class, 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Undangan Rapat: '.$this->meeting->title)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Anda diundang untuk menghadiri rapat:')
            ->line('**'.$this->meeting->title.'**')
            ->line('**Tanggal:** '.($this->meeting->meeting_date ? $this->meeting->meeting_date->format('d F Y, H:i') : '-'))
            ->line('**Lokasi:** '.($this->meeting->location ?? 'Online'))
            ->action('Lihat Detail Rapat', url('/admin/meetings/'.$this->meeting->id))
            ->line('Silakan konfirmasi kehadiran Anda melalui sistem.')
            ->salutation('Terima kasih');
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
