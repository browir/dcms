<?php

namespace App\Services;

/**
 * Service untuk Time-based One-Time Password (TOTP) sesuai spesifikasi RFC 6238.
 * Kompatibel penuh dengan Google Authenticator, Microsoft Authenticator, Aegis, dll.
 */
class TotpService
{
    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Hasilkan secret key acak 16 karakter (Base32).
     */
    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Hitung kode 6 digit dari secret key pada waktu tertentu.
     */
    public static function getCode(string $secret, ?int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $timeSlice = (int) floor($timestamp / 30);
        $secretBin = self::base32Decode($secret);

        // 8 bytes binary time counter
        $timeBin = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $timeBin, $secretBin, true);

        // Dynamic truncation
        $offset = ord(substr($hash, -1)) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;

        $code = $value % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Alias untuk getCode.
     */
    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        return self::getCode($secret, $timestamp);
    }

    /**
     * Verifikasi kode 6 digit dengan toleransi drift waktu +/- 1 window (30 detik).
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        $currentTime = time();
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculated = self::getCode($secret, $currentTime + ($i * 30));
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Buat URL otpauth:// untuk QR Code.
     */
    public static function getOtpAuthUrl(string $accountName, string $secret, string $issuer = 'DCMS Syifa'): string
    {
        $encodedIssuer = rawurlencode($issuer);
        $encodedAccount = rawurlencode($accountName);

        return "otpauth://totp/{$encodedIssuer}:{$encodedAccount}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * URL gambar QR Code untuk dipindai oleh Google Authenticator.
     */
    public static function getQrCodeUrl(string $accountName, string $secret, string $issuer = 'DCMS Syifa'): string
    {
        $otpAuth = self::getOtpAuthUrl($accountName, $secret, $issuer);

        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' . rawurlencode($otpAuth);
    }

    /**
     * Decode string Base32 menjadi biner.
     */
    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $val = strpos(self::$base32Chars, $b32[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binary;
    }
}

