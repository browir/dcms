<?php

namespace App\Support;

/**
 * Resolver logo aplikasi.
 *
 * Logo yang diunggah lewat menu "Pengaturan Aplikasi" disimpan di
 * storage/app/public/branding/ (di luar Git) sehingga TIDAK tertimpa
 * saat deploy `git pull` (aaPanel GitManager).
 *
 * Jika belum ada logo kustom, dipakai berkas bawaan public/images/logo.png.
 * Header PDF notulensi memakai logo terpisah (public/images/logo-pdf.png)
 * dan sengaja tidak ikut resolver ini.
 */
class Branding
{
    /** Path relatif di disk "public" (storage/app/public). */
    public const CUSTOM_LOGO = 'branding/logo.png';

    public const CUSTOM_LOGO_PREVIOUS = 'branding/logo-previous.png';

    public const CUSTOM_LOGO_PREVIEW = 'branding/logo-preview.png';

    public static function customLogoAbsolutePath(): string
    {
        return storage_path('app/public/'.self::CUSTOM_LOGO);
    }

    public static function hasCustomLogo(): bool
    {
        return is_file(self::customLogoAbsolutePath());
    }

    /**
     * Path absolut logo aktif (kustom bila ada, jika tidak bawaan).
     */
    public static function logoPath(): string
    {
        $custom = self::customLogoAbsolutePath();

        if (is_file($custom)) {
            return $custom;
        }

        return public_path('images/logo.png');
    }

    /**
     * URL logo aktif + cache-buster berdasarkan waktu modifikasi berkas.
     */
    public static function logoUrl(): string
    {
        if (self::hasCustomLogo()) {
            $path = self::customLogoAbsolutePath();

            return asset('storage/'.self::CUSTOM_LOGO).'?v='.(@filemtime($path) ?: 1);
        }

        $default = public_path('images/logo.png');

        return asset('images/logo.png').'?v='.(is_file($default) ? filemtime($default) : 1);
    }

    public static function logoVersion(): int
    {
        $path = self::logoPath();

        return is_file($path) ? (int) filemtime($path) : 1;
    }

    /**
     * Path absolut logo khusus header PDF notulensi (tidak ikut berubah
     * saat logo aplikasi diganti). Fallback ke logo bawaan lalu logo aktif.
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
}
