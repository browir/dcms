<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSubmittedNotification extends Notification
{
    // Sengaja TIDAK implements ShouldQueue: server produksi memakai QUEUE_CONNECTION=database
    // tapi tidak ada queue worker yang berjalan, jadi notifikasi ber-ShouldQueue hanya
    // menumpuk di tabel `jobs` dan tidak pernah benar-benar terkirim.

    public function __construct(
        public Document $document
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
            'type' => 'document_submitted',
            'title' => 'Dokumen Menunggu Review',
            'message' => "Halo {$notifiable->name}, ada dokumen baru \"{$this->document->title}\""
                ." (No. {$this->document->code_number}) yang menunggu review Anda sebagai Kabid.",
            'url' => url('/reviewer/review-documents/'.$this->document->id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $submitter = $this->document->user;
        $department = $this->document->department;

        return (new MailMessage)
            ->subject('Dokumen Baru Menunggu Review: '.$this->document->title)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Ada dokumen baru yang membutuhkan review Anda sebagai Kepala Bidang.')
            ->line('---')
            ->line('**Judul:** '.$this->document->title)
            ->line('**Nomor Kode:** '.$this->document->code_number)
            ->line('**Diajukan oleh:** '.($submitter?->name ?? 'Unknown'))
            ->line('**Departemen:** '.($department?->name ?? '-'))
            ->line('**Tanggal Submit:** '.$this->document->created_at->format('d F Y, H:i'))
            ->line('---')
            ->action('Review Dokumen', url('/reviewer/review-documents/'.$this->document->id))
            ->line('Silakan login ke Panel Reviewer untuk mereview dokumen ini.')
            ->salutation('Salam, Sistem DCMS');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_submitted',
            'document_id' => $this->document->id,
            'title' => $this->document->title,
            'code_number' => $this->document->code_number,
            'submitted_by' => $this->document->user?->name,
            'message' => 'Dokumen baru "'.$this->document->title.'" menunggu review Anda.',
        ];
    }
}
