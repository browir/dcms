<?php

namespace App\Http\Controllers;

use App\Support\Branding;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingController extends Controller
{
    /**
     * Logo aplikasi aktif (kustom dari storage bila ada, jika tidak bawaan).
     * Disajikan via route agar tidak bergantung pada symlink public/storage.
     */
    public function logo(): BinaryFileResponse
    {
        return $this->serve(Branding::logoPath());
    }

    /**
     * Pratinjau kecil logo (dibuat ulang otomatis bila berkas belum ada / basi).
     */
    public function preview(): BinaryFileResponse
    {
        return $this->serve(Branding::ensurePreview());
    }

    protected function serve(string $path): BinaryFileResponse
    {
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
