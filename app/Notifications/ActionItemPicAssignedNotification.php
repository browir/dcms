<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ActionItemPicAssignedNotification extends Notification
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
        // Urutan sengaja: 'database' & webpush duluan, 'mail' terakhir.
        // Laravel mengirim tiap channel berurutan dan TIDAK melanjutkan ke channel
        // berikutnya kalau satu channel melempar exception — jadi kalau SMTP
        // bermasalah dan 'mail' ditaruh di depan, notifikasi bell & web push
        // ikut gagal terkirim juga meski keduanya sebenarnya tidak bermasalah.
        return ['database', WebPushChannel::class, 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Anda Ditunjuk Sebagai PIC: '.$this->meeting->title)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Anda ditunjuk sebagai PIC (Person in Charge) untuk salah satu action plan pada notulensi rapat berikut:')
            ->line('**'.$this->meeting->title.'**')
            ->line('**Tanggal:** '.($this->meeting->meeting_date ? $this->meeting->meeting_date->format('d F Y, H:i') : '-'))
            ->action('Lihat Detail Rapat', url('/admin/meetings/'.$this->meeting->id))
            ->line('Silakan cek detail action plan yang menjadi tanggung jawab Anda.')
            ->salutation('Terima kasih');
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Anda Ditunjuk Sebagai PIC')
            ->icon(\App\Support\Branding::logoUrl())
            ->body('Rapat "'.$this->meeting->title.'" — cek action plan yang menjadi tanggung jawab Anda.')
            ->action('Buka Aplikasi', 'open_app')
            ->options(['vibrate' => [100, 50, 100]])
            ->data([
                'url' => url('/admin/meetings/'.$this->meeting->id),
                'timestamp' => now()->timestamp,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'action_item_pic_assigned',
            'meeting_id' => $this->meeting->id,
            'title' => 'Anda Ditunjuk Sebagai PIC: '.$this->meeting->title,
            'message' => 'Anda ditunjuk sebagai PIC pada salah satu action plan rapat "'.$this->meeting->title.'".',
        ];
    }
}
