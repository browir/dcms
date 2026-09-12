<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentFinalDecisionNotification extends Notification
{
    // Sengaja TIDAK implements ShouldQueue: server produksi memakai QUEUE_CONNECTION=database
    // tapi tidak ada queue worker yang berjalan, jadi notifikasi ber-ShouldQueue hanya
    // menumpuk di tabel `jobs` dan tidak pernah benar-benar terkirim.

    public function __construct(
        public Document $document,
        public string $decision // 'approved' or 'rejected'
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
        $isApproved = $this->decision === 'approved';

        return [
            'type' => 'document_final_decision',
            'title' => $isApproved ? 'Dokumen Disetujui' : 'Dokumen Ditolak',
            'message' => "Halo {$notifiable->name}, dokumen \"{$this->document->title}\" telah "
                .($isApproved ? 'DISETUJUI' : 'DITOLAK').' oleh Direktur.'
                .($isApproved ? '' : ' Silakan perbaiki sesuai catatan dan ajukan kembali.'),
            'url' => url('/admin/documents/'.$this->document->id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $direktur = $this->document->direkturReviewer;
        $isApproved = $this->decision === 'approved';

        $mail = (new MailMessage)
            ->subject(($isApproved ? 'Dokumen Disetujui' : 'Dokumen Ditolak').': '.$this->document->title)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Dokumen yang Anda ajukan telah mendapatkan keputusan final dari Direktur.')
            ->line('---')
            ->line('**Judul Dokumen:** '.$this->document->title)
            ->line('**Nomor Dokumen:** '.($this->document->doc_number ?? '—'))
            ->line('**Jenis Dokumen:** '.($this->document->documentType->name ?? '—'))
            ->line('**Keputusan:** '.($isApproved ? 'DISETUJUI' : 'DITOLAK'))
            ->line('**Diputuskan oleh:** '.($direktur?->name ?? 'Direktur'))
            ->line('**Catatan Direktur:** '.($this->document->direktur_notes ?: 'Tidak ada catatan'));

        if (! $isApproved) {
            $mail->line('')
                ->line('Silakan perbaiki dokumen Anda sesuai catatan di atas dan ajukan kembali.');
        }

        $mail->line('---')
            ->action('Lihat Dokumen', url('/admin/documents/'.$this->document->id))
            ->salutation('Salam, Sistem DCMS');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_final_decision',
            'document_id' => $this->document->id,
            'title' => $this->document->title,
            'code_number' => $this->document->code_number,
            'decision' => $this->decision,
            'decided_by' => $this->document->direkturReviewer?->name,
            'message' => 'Dokumen "'.$this->document->title.'" telah '.
                ($this->decision === 'approved' ? 'disetujui' : 'ditolak').' oleh Direktur.',
        ];
    }
}
