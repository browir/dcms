<?php

namespace App\Listeners;

use App\Notifications\WebPushGenericNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use Throwable;

/**
 * Setiap notifikasi lonceng (channel database) ikut dikirim sebagai web push ke perangkat yang
 * sudah mengaktifkan notifikasi push — jadi tetap sampai walau aplikasi tidak sedang dibuka.
 * Push dikirim setelah response (server tidak menjalankan queue worker), dan kegagalan push
 * tidak pernah menggagalkan proses utama.
 */
class MirrorDatabaseNotificationToPush
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->response instanceof DatabaseNotification) {
            return;
        }

        $notifiable = $event->notifiable;

        if (blank(config('webpush.vapid.public_key'))
            || ! method_exists($notifiable, 'pushSubscriptions')
            || $this->alreadyPushes($event)
        ) {
            return;
        }

        $push = self::pushFor((array) $event->response->data);

        dispatch(function () use ($notifiable, $push): void {
            try {
                if ($notifiable->pushSubscriptions()->exists()) {
                    $notifiable->notify($push);
                }
            } catch (Throwable $e) {
                report($e);
            }
        })->afterResponse();
    }

    /** Ubah data notifikasi lonceng (format DCMS maupun Filament) jadi push. */
    public static function pushFor(array $data): WebPushGenericNotification
    {
        $title = $data['title'] ?? 'Notifikasi DCMS';
        $body = $data['message'] ?? $data['body'] ?? '';
        $url = $data['actions'][0]['url'] ?? $data['url'] ?? '/admin';

        return new WebPushGenericNotification(
            Str::limit(strip_tags((string) $title), 80),
            Str::limit(strip_tags((string) $body), 160),
            (string) (parse_url($url, PHP_URL_PATH) ?: '/admin').(parse_url($url, PHP_URL_QUERY) ? '?'.parse_url($url, PHP_URL_QUERY) : ''),
        );
    }

    /** Notifikasi yang sudah punya channel push sendiri tidak dikirim dua kali. */
    private function alreadyPushes(NotificationSent $event): bool
    {
        try {
            return in_array(WebPushChannel::class, (array) $event->notification->via($event->notifiable), true);
        } catch (Throwable) {
            return false;
        }
    }
}
