<?php

namespace App\Http\Controllers;

use App\Models\IncomingLetter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Scan surat masuk (disk privat): hanya untuk user yang boleh melihat suratnya. */
class IncomingLetterFileController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(IncomingLetter $incomingLetter): Response
    {
        $this->authorize('view', $incomingLetter);

        $disk = Storage::disk('documents');
        abort_unless($incomingLetter->file_path && $disk->exists($incomingLetter->file_path), 404);

        return $disk->response($incomingLetter->file_path, basename($incomingLetter->file_path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }
}
