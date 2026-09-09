<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class WebPushGenericNotification extends Notification
{
    use Queueable;

    public string $title;

    public string $body;

    public string $url;

    public string $icon;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $body,
        string $url = '/admin',
        string $icon = ''
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
        $this->icon = $icon !== '' ? $icon : \App\Support\Branding::logoUrl();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the WebPush representation of the notification.
     */
    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon($this->icon)
            ->body($this->body)
            ->action('Buka Aplikasi', 'open_app')
            ->options(['vibrate' => [100, 50, 100]])
            ->data([
                'url' => $this->url,
                'timestamp' => now()->timestamp,
            ]);
    }
}
