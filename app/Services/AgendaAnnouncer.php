<?php

namespace App\Services;

use App\Enum\JenisSurat;
use App\Models\Agenda;
use App\Models\LetterNumber;
use App\Models\User;
use App\Notifications\AgendaAnnouncedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

/**
 * Surat pemberitahuan & pengumuman kegiatan unit.
 *
 * - issueNotice()   : ambil nomor surat pemberitahuan dari register Nomor Surat.
 * - announceInApp() : notifikasi lonceng ke karyawan sesuai jangkauan (audience).
 *                     Aplikasi lain membaca pengumuman lewat API /api/v1/kegiatan.
 * - sendToWhatsAppGroup() : kirim ke grup WhatsApp lewat webhook n8n yang sama dengan
 *                     notifikasi WA lain (channel "whatsapp_group", lihat docs/notifikasi-whatsapp-n8n.md).
 */
class AgendaAnnouncer
{
    public function __construct(private readonly LetterNumberService $letters) {}

    /** Terbitkan nomor surat pemberitahuan untuk kegiatan ini (sekali saja). */
    public function issueNotice(Agenda $agenda, JenisSurat $jenis = JenisSurat::EDARAN_PENGUMUMAN, ?User $by = null): LetterNumber
    {
        if ($agenda->letter_number_id) {
            return $agenda->letterNumber;
        }

        if (! $agenda->unit) {
            throw new InvalidArgumentException('Pilih unit penyelenggara dulu — nomor surat mengikuti unit.');
        }

        return DB::transaction(function () use ($agenda, $jenis, $by) {
            $letter = $this->letters->issue(
                $agenda->unit,
                $jenis,
                today(),
                'Pemberitahuan '.$agenda->title,
                'Pemberitahuan kegiatan',
                $agenda->description ? strip_tags($agenda->description) : null,
                $by,
            );

            $agenda->forceFill([
                'letter_number_id' => $letter->id,
                'notice_body' => $agenda->notice_body ?: self::defaultNoticeBody($agenda),
            ])->save();

            return $letter;
        });
    }

    /** @return int jumlah karyawan yang menerima notifikasi */
    public function announceInApp(Agenda $agenda): int
    {
        $recipients = $this->recipients($agenda);

        Notification::send($recipients, new AgendaAnnouncedNotification($agenda));

        $agenda->forceFill(['announced_at' => now()])->save();

        return $recipients->count();
    }

    /**
     * @return array{sent: bool, reason: ?string, group: ?string}
     */
    public function sendToWhatsAppGroup(Agenda $agenda): array
    {
        $webhook = config('services.n8n.webhook_url');
        $group = $this->whatsAppGroup($agenda);

        if (blank($webhook)) {
            return ['sent' => false, 'reason' => 'Webhook n8n (N8N_WEBHOOK_URL) belum diatur.', 'group' => $group];
        }

        if (blank($group)) {
            return ['sent' => false, 'reason' => 'ID grup WhatsApp belum diatur untuk unit/perusahaan ini.', 'group' => null];
        }

        try {
            $response = Http::asJson()
                ->timeout(15)
                ->when(filled(config('services.n8n.secret')), fn ($http) => $http->withHeaders(['X-DCMS-Signature' => config('services.n8n.secret')]))
                ->post($webhook, [
                    'to' => $group,
                    'channel' => 'whatsapp_group',
                    'source' => 'dcms',
                    'type' => 'agenda_announcement',
                    'title' => 'Pemberitahuan Kegiatan',
                    'message' => self::whatsAppMessage($agenda),
                    'url' => url('/admin/kegiatan/'.$agenda->id),
                    'letter_number' => $agenda->letterNumber?->nomor,
                    // Link PDF bertanda tangan (tanpa login) supaya n8n bisa melampirkan surat ke grup.
                    'notice_pdf_url' => $agenda->letter_number_id
                        ? URL::temporarySignedRoute('kegiatan.surat.signed', now()->addDays(30), ['agenda' => $agenda->id])
                        : null,
                ]);
        } catch (\Throwable $e) {
            Log::error('AgendaAnnouncer: gagal mengirim ke webhook n8n: '.$e->getMessage());

            return ['sent' => false, 'reason' => 'Tidak bisa menghubungi webhook n8n.', 'group' => $group];
        }

        if ($response->failed()) {
            Log::error('AgendaAnnouncer: webhook n8n mengembalikan error', ['status' => $response->status(), 'body' => $response->body()]);

            return ['sent' => false, 'reason' => "Webhook n8n menolak (HTTP {$response->status()}).", 'group' => $group];
        }

        $agenda->forceFill(['whatsapp_sent_at' => now()])->save();

        return ['sent' => true, 'reason' => null, 'group' => $group];
    }

    /** @return Collection<int, User> karyawan aktif dalam jangkauan pengumuman */
    public function recipients(Agenda $agenda): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->when($agenda->audience === Agenda::AUDIENCE_UNIT, fn ($q) => $q->where('unit_id', $agenda->unit_id ?? 0))
            ->when($agenda->audience === Agenda::AUDIENCE_COMPANY, fn ($q) => $q->where('company_id', $agenda->company_id))
            ->get();
    }

    /** Grup WA tujuan: grup unit (jangkauan unit), grup perusahaan, lalu grup default di .env. */
    public function whatsAppGroup(Agenda $agenda): ?string
    {
        $unitGroup = $agenda->audience === Agenda::AUDIENCE_UNIT ? $agenda->unit?->whatsapp_group : null;
        $companyGroup = $agenda->audience !== Agenda::AUDIENCE_ALL ? $agenda->company?->whatsapp_group : null;

        return $unitGroup ?: $companyGroup ?: config('services.n8n.default_group') ?: null;
    }

    public static function defaultNoticeBody(Agenda $agenda): string
    {
        return "Dengan hormat,\n\n"
            ."Bersama ini kami beritahukan bahwa {$agenda->organizerName()} akan menyelenggarakan kegiatan "
            ."\"{$agenda->title}\" pada:\n\n"
            ."Hari/tanggal : {$agenda->date_time->locale('id')->isoFormat('dddd, D MMMM YYYY')}\n"
            ."Waktu        : {$agenda->date_time->format('H.i')}–{$agenda->effectiveEndTime()->format('H.i')} WITA\n"
            ."Tempat       : {$agenda->placeName()}\n\n"
            .($agenda->description ? trim(strip_tags($agenda->description))."\n\n" : '')
            .'Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kehadirannya kami ucapkan terima kasih.';
    }

    public static function whatsAppMessage(Agenda $agenda): string
    {
        $lines = ['📢 *PEMBERITAHUAN KEGIATAN*'];

        if ($agenda->letterNumber) {
            $lines[] = 'Nomor: '.$agenda->letterNumber->nomor;
        }

        $lines[] = '';
        $lines[] = '*'.$agenda->title.'*';
        $lines[] = '🗓 '.$agenda->scheduleLabel();
        $lines[] = '📍 '.$agenda->placeName();
        $lines[] = '👥 Penyelenggara: '.$agenda->organizerName();

        if ($agenda->description) {
            $lines[] = '';
            $lines[] = trim(strip_tags($agenda->description));
        }

        $lines[] = '';
        $lines[] = 'Detail: '.url('/admin/kegiatan/'.$agenda->id);

        return implode("\n", $lines);
    }
}
