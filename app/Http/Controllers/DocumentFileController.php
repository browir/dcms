<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * File dokumen resmi disimpan di disk privat (`documents`), jadi hanya bisa diambil lewat sini:
 * - show()   : user login yang boleh melihat dokumennya (policy + scope akses dokumen).
 * - signed() : link bertanda tangan & berbatas waktu untuk API integrasi (tanpa login).
 */
class DocumentFileController extends Controller
{
    public function show(string $path): Response
    {
        $document = $this->findDocument($path, fn ($query) => $query->access());

        abort_unless($document && auth()->user()->can('view', $document), 404);

        return $this->serve($path);
    }

    public function signed(string $path): Response
    {
        abort_unless($this->findDocument($path), 404);

        return $this->serve($path);
    }

    /** Path hanya dilayani bila memang tercatat sebagai file sebuah dokumen. */
    private function findDocument(string $path, ?callable $scope = null): ?Document
    {
        return Document::query()
            ->when($scope, $scope)
            ->where(fn ($query) => $query->where('file_path', $path)->orWhere('generated_file_path', $path))
            ->first();
    }

    private function serve(string $path): Response
    {
        $disk = Storage::disk('documents');
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        $response = $disk->response($path, basename($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], 'inline');

        // HTML hasil generate dibuka tanpa izin menjalankan skrip (cegah XSS dari isi dokumen).
        if (! str_starts_with($mimeType, 'image/') && $mimeType !== 'application/pdf') {
            $response->headers->set('Content-Security-Policy', 'sandbox');
        }

        return $response;
    }
}
