<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Resolver logo aplikasi.
 *
 * Logo yang diunggah lewat "Pengaturan Aplikasi" disimpan di
 * storage/app/public/branding/ (di luar Git) sehingga TIDAK tertimpa saat
 * deploy `git pull` (aaPanel GitManager).
 *
 * Berkas TIDAK diakses lewat symlink public/storage — proyek ini menyajikan
 * berkas storage melalui route (lihat routes/web.php: branding.logo), sama
 * seperti route documents.serve yang sudah ada.
 *
 * Jika belum ada logo kustom, dipakai berkas bawaan public/images/logo.png.
 * Header PDF notulensi memakai logo terpisah (public/images/logo-pdf.png)
 * dan sengaja tidak ikut resolver ini.
 */
class Branding
{
    /** Path relatif di storage/app/public. */
    public const CUSTOM_LOGO = 'branding/logo.png';

    public const CUSTOM_LOGO_PREVIOUS = 'branding/logo-previous.png';

    public const CUSTOM_LOGO_PREVIEW = 'branding/logo-preview.png';

    /** Sisi terpanjang maksimum logo tersimpan. */
    public const MAX_LOGO_SIZE = 1024;

    /** Sisi terpanjang pratinjau. */
    public const PREVIEW_SIZE = 240;

    public static function customLogoPath(): string
    {
        return storage_path('app/public/'.self::CUSTOM_LOGO);
    }

    public static function previewPath(): string
    {
        return storage_path('app/public/'.self::CUSTOM_LOGO_PREVIEW);
    }

    public static function hasCustomLogo(): bool
    {
        return is_file(self::customLogoPath());
    }

    /**
     * Path absolut logo aktif (kustom bila ada, jika tidak bawaan).
     */
    public static function logoPath(): string
    {
        $custom = self::customLogoPath();

        if (is_file($custom)) {
            return $custom;
        }

        return public_path('images/logo.png');
    }

    public static function logoVersion(): int
    {
        $path = self::logoPath();

        return is_file($path) ? (int) filemtime($path) : 1;
    }

    /**
     * URL logo aktif (disajikan via route) + cache-buster.
     */
    public static function logoUrl(): string
    {
        return url('branding/logo').'?v='.self::logoVersion();
    }

    /**
     * URL pratinjau kecil logo (disajikan via route) + cache-buster.
     */
    public static function previewUrl(): string
    {
        return url('branding/logo/preview').'?v='.self::logoVersion();
    }

    /**
     * Path absolut logo header PDF notulensi (tidak ikut berubah saat logo
     * aplikasi diganti). Fallback ke logo bawaan lalu logo aktif.
     */
    public static function pdfLogoPath(): string
    {
        $pdf = public_path('images/logo-pdf.png');

        if (is_file($pdf)) {
            return $pdf;
        }

        $default = public_path('images/logo.png');

        return is_file($default) ? $default : self::logoPath();
    }

    /**
     * Proses berkas terunggah menjadi PNG (maks. 1024px) dan simpan sebagai
     * logo kustom. Mengembalikan true bila berhasil.
     */
    public static function storeUploadedLogo(string $sourcePath): bool
    {
        $image = self::readImage($sourcePath);

        if (! $image) {
            return false;
        }

        try {
            self::normalizeAlpha($image);
            $image = self::downscale($image, self::MAX_LOGO_SIZE);

            $target = self::customLogoPath();

            if (! is_dir(dirname($target))) {
                @mkdir(dirname($target), 0775, true);
            }

            if (is_file($target)) {
                @copy($target, storage_path('app/public/'.self::CUSTOM_LOGO_PREVIOUS));
            }

            $ok = imagepng($image, $target);
            imagedestroy($image);

            @unlink(self::previewPath());
            clearstatcache(true, $target);

            return (bool) $ok;
        } catch (\Throwable $e) {
            Log::error('Branding::storeUploadedLogo gagal: '.$e->getMessage());

            return false;
        }
    }

    public static function removeCustomLogo(): void
    {
        @unlink(self::customLogoPath());
        @unlink(self::previewPath());
        @unlink(storage_path('app/public/'.self::CUSTOM_LOGO_PREVIOUS));
    }

    /**
     * Pastikan berkas pratinjau ada & terbaru; buat ulang bila perlu.
     * Mengembalikan path pratinjau, atau path logo penuh bila gagal.
     */
    public static function ensurePreview(): string
    {
        $source = self::logoPath();
        $preview = self::previewPath();

        if (! is_file($source)) {
            return $source;
        }

        if (is_file($preview) && filemtime($preview) >= filemtime($source)) {
            return $preview;
        }

        $image = self::readImage($source);

        if (! $image) {
            return $source;
        }

        try {
            if (! is_dir(dirname($preview))) {
                @mkdir(dirname($preview), 0775, true);
            }

            self::normalizeAlpha($image);
            $image = self::downscale($image, self::PREVIEW_SIZE);
            imagepng($image, $preview);
            imagedestroy($image);
            clearstatcache(true, $preview);

            return is_file($preview) ? $preview : $source;
        } catch (\Throwable $e) {
            Log::warning('Branding::ensurePreview gagal: '.$e->getMessage());

            return $source;
        }
    }

    /**
     * @return \GdImage|false
     */
    protected static function readImage(string $source)
    {
        if (! is_file($source)) {
            return false;
        }

        $mime = @getimagesize($source)['mime'] ?? null;

        return match ($mime) {
            'image/png' => @imagecreatefrompng($source),
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
    }

    protected static function normalizeAlpha(\GdImage $image): void
    {
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    protected static function downscale(\GdImage $image, int $max): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);

        if ($w <= $max && $h <= $max) {
            return $image;
        }

        $ratio = min($max / $w, $max / $h);
        $nw = (int) round($w * $ratio);
        $nh = (int) round($h * $ratio);

        $resized = imagecreatetruecolor($nw, $nh);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($image);

        return $resized;
    }
}
