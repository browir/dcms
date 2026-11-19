<?php

namespace App\Notifications;

use App\Support\BrowserHelper;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification
{
    public function __construct(
        public readonly string $ip,
        public readonly ?string $userAgent = null,
        public readonly ?string $time = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deviceInfo = BrowserHelper::parse($this->userAgent);
        $time = $this->time ?: now()->translatedFormat('d F Y, H:i T');
        $ip = $this->ip ?: '127.0.0.1';

        return (new MailMessage)
            ->subject('⚠️ Peringatan Keamanan: Masuk dari Perangkat Baru — DCMS')
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Kami mendeteksi aktivitas masuk (**login**) ke akun DCMS Anda dari perangkat atau alamat IP yang belum pernah digunakan sebelumnya.')
            ->line('')
            ->line('**Rincian Masuk:**')
            ->line("- **Perangkat & Peramban:** {$deviceInfo['summary']}")
            ->line("- **Alamat IP:** {$ip}")
            ->line("- **Waktu:** {$time}")
            ->line('')
            ->line('Jika ini memang Anda, Anda tidak perlu melakukan tindakan apa pun.')
            ->line('')
            ->line('⚠️ **Jika ini BUKAN Anda:**')
            ->line('Seseorang mungkin telah mengetahui kata sandi Anda. Segera lakukan langkah berikut:')
            ->line('1. Masuk dan ubah kata sandi akun Anda segera.')
            ->line('2. Buka menu **Profil** dan klik **Keluar dari Semua Perangkat Lain**.')
            ->line('3. Aktifkan **Verifikasi Dua Faktor (2FA)** untuk perlindungan maksimal.')
            ->action('Buka Halaman Profil DCMS', url('/admin/profile'));
    }
}

