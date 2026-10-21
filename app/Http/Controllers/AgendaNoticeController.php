<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Surat pemberitahuan kegiatan (PDF). Dua jalur akses:
 * - show()   : user yang login dan boleh melihat kegiatannya.
 * - signed() : link bertanda tangan (dikirim ke n8n/WhatsApp grup) tanpa login.
 */
class AgendaNoticeController extends Controller
{
    use AuthorizesRequests;

    public function show(Agenda $agenda): Response
    {
        $this->authorize('view', $agenda);

        return $this->render($agenda);
    }

    public function signed(Agenda $agenda): Response
    {
        return $this->render($agenda);
    }

    private function render(Agenda $agenda): Response
    {
        abort_unless($agenda->letter_number_id, 404, 'Kegiatan ini belum punya surat pemberitahuan.');

        $agenda->loadMissing(['letterNumber', 'company', 'department', 'unit', 'meetingLocation', 'creator']);

        $filename = 'Surat-Pemberitahuan-'.str_replace('/', '-', $agenda->letterNumber->nomor).'.pdf';

        return Pdf::loadView('pdf.agenda-notice', ['agenda' => $agenda])
            ->setPaper('a4')
            ->stream($filename);
    }
}
