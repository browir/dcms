<?php

namespace App\Support;

class BrowserHelper
{
    /**
     * Parse User-Agent string ke informasi terstruktur.
     */
    public static function parse(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'browser'     => 'Peramban Tidak Dikenal',
                'platform'    => 'Sistem Tidak Dikenal',
                'device_type' => 'desktop',
                'summary'     => 'Perangkat Tidak Dikenal',
                'icon'        => 'heroicon-o-computer-desktop',
            ];
        }

        $platform = self::getPlatform($userAgent);
        $browser = self::getBrowser($userAgent);
        $isMobile = self::isMobile($userAgent);

        $deviceType = $isMobile ? 'mobile' : 'desktop';
        $icon = $isMobile ? 'heroicon-o-device-phone-mobile' : 'heroicon-o-computer-desktop';

        return [
            'browser'     => $browser,
            'platform'    => $platform,
            'device_type' => $deviceType,
            'summary'     => "{$browser} di {$platform}",
            'icon'        => $icon,
        ];
    }

    private static function getPlatform(string $userAgent): string
    {
        if (preg_match('/windows|win32|win64/i', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/iphone/i', $userAgent)) {
            return 'iPhone (iOS)';
        }
        if (preg_match('/ipad/i', $userAgent)) {
            return 'iPad (iPadOS)';
        }
        if (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/macintosh|mac os x/i', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Sistem Tidak Dikenal';
    }

    private static function getBrowser(string $userAgent): string
    {
        if (preg_match('/edg/i', $userAgent)) {
            return 'Microsoft Edge';
        }
        if (preg_match('/opr|opera/i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/chrome|crios/i', $userAgent) && ! preg_match('/edg/i', $userAgent)) {
            return 'Google Chrome';
        }
        if (preg_match('/firefox|fxios/i', $userAgent)) {
            return 'Mozilla Firefox';
        }
        if (preg_match('/safari/i', $userAgent) && ! preg_match('/chrome|crios/i', $userAgent)) {
            return 'Apple Safari';
        }

        return 'Web Browser';
    }

    private static function isMobile(string $userAgent): bool
    {
        return (bool) preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent);
    }
}

