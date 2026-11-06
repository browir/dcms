<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    private static array $purposeLabels = [
        'login'           => 'Masuk ke akun',
        'enable_2fa'      => 'Mengaktifkan verifikasi dua faktor',
        'disable_2fa'     => 'Menonaktifkan verifikasi dua faktor',
        'change_email'    => 'Mengubah alamat email',
        'change_password' => 'Mengubah kata sandi',
    ];

    public function __construct(
        private readonly string $plainCode,
        private readonly string $purpose,
        private readonly ?string $ip = null,
        private readonly ?string $userAgent = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $purposeLabel = self::$purposeLabels[$this->purpose] ?? 'tindakan keamanan';
        $ttl          = config('two_factor.code_ttl_minutes', 10);
        $ip           = $this->ip ?: '127.0.0.1';
        $ua           = $this->truncateUserAgent($this->userAgent ?? '');
        $time         = now()->translatedFormat('d F Y, H:i T');

        return (new MailMessage)
            ->subject('Kode Verifikasi DCMS — ' . $purposeLabel)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Kami menerima permintaan untuk **' . $purposeLabel . '** pada akun Anda.')
            ->line('')
            ->line('Kode verifikasi Anda:')
            ->line('')
            ->line('## ' . chunk_split($this->plainCode, 3, ' '))
            ->line('')
            ->line("Kode ini berlaku selama **{$ttl} menit** dan hanya dapat digunakan satu kali.")
            ->line('')
            ->line('> ⚠️ **Jangan bagikan kode ini kepada siapa pun**, termasuk tim dukungan DCMS.')
            ->line('')
            ->line('**Detail permintaan:**')
            ->line("- Waktu: {$time}")
            ->line("- IP: {$ip}")
            ->line("- Perangkat: {$ua}")
            ->line('')
            ->line('Jika Anda tidak melakukan tindakan ini, abaikan email ini. Akun Anda tetap aman.');
    }

    private function truncateUserAgent(string $ua): string
    {
        return mb_strlen($ua) > 80 ? mb_substr($ua, 0, 80) . '…' : $ua;
    }
}
