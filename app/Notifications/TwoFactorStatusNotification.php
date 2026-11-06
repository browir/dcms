<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorStatusNotification extends Notification
{
    public function __construct(private readonly string $status) {} // 'enabled' | 'disabled'

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $enabled = $this->status === 'enabled';
        $label   = $enabled ? 'diaktifkan' : 'dinonaktifkan';
        $subject = $enabled
            ? 'Verifikasi Dua Faktor Diaktifkan di Akun Anda'
            : 'Verifikasi Dua Faktor Dinonaktifkan di Akun Anda';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line("Verifikasi dua faktor (2FA) pada akun DCMS Anda telah **{$label}**.")
            ->line('Waktu: ' . now()->translatedFormat('d F Y, H:i T'));

        if ($enabled) {
            $message->line('Mulai sekarang, setiap login dari perangkat baru akan memerlukan kode verifikasi yang dikirim ke email Anda.');
        } else {
            $message->line('Akun Anda sekarang hanya dilindungi oleh kata sandi. Pertimbangkan untuk mengaktifkan kembali 2FA agar akun Anda lebih aman.');
        }

        return $message
            ->line('')
            ->line('Jika Anda tidak melakukan perubahan ini, segera hubungi administrator sistem.');
    }
}
