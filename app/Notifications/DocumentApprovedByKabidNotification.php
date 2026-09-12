<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentApprovedByKabidNotification extends Notification
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
            'type' => 'document_approved_kabid',
            'title' => 'Menunggu Keputusan Direktur',
            'message' => "Halo {$notifiable->name}, dokumen \"{$this->document->title}\""
                ." (No. {$this->document->code_number}) telah disetujui Kabid dan menunggu keputusan akhir Anda.",
            'url' => url('/reviewer/review-documents/'.$this->document->id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $kabid = $this->document->kabidReviewer;
        $submitter = $this->document->user;

        return (new MailMessage)
            ->subject('Dokumen Disetujui Kabid — Menunggu Keputusan Anda: '.$this->document->title)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Dokumen berikut telah disetujui oleh Kepala Bidang dan membutuhkan keputusan akhir Anda.')
            ->line('---')
            ->line('**Judul:** '.$this->document->title)
            ->line('**Nomor Kode:** '.$this->document->code_number)
            ->line('**Diajukan oleh:** '.($submitter?->name ?? 'Unknown'))
            ->line('**Disetujui Kabid:** '.($kabid?->name ?? 'Unknown'))
            ->line('**Catatan Kabid:** '.($this->document->kabid_notes ?: 'Tidak ada catatan'))
            ->line('**Tanggal Review Kabid:** '.($this->document->kabid_reviewed_at ? $this->document->kabid_reviewed_at->format('d F Y, H:i') : '-'))
            ->line('---')
            ->action('Review & Putuskan', url('/reviewer/review-documents/'.$this->document->id))
            ->line('Silakan login ke Panel Reviewer untuk menyetujui atau menolak dokumen ini.')
            ->salutation('Salam, Sistem DCMS');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_approved_kabid',
            'document_id' => $this->document->id,
            'title' => $this->document->title,
            'code_number' => $this->document->code_number,
            'approved_by_kabid' => $this->document->kabidReviewer?->name,
            'message' => 'Dokumen "'.$this->document->title.'" telah disetujui Kabid, menunggu keputusan Anda.',
        ];
    }
}
