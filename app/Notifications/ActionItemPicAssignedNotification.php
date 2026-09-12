<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ActionItemPicAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Meeting $meeting
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
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
