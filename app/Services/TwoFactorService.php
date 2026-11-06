<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\TwoFactorCode;
use App\Models\TwoFactorLog;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorService
{
    // ── Kode OTP ─────────────────────────────────────────────────────────────

    /**
     * Buat dan kirim kode OTP baru ke email pengguna.
     * Kode lama dengan purpose yang sama hangus secara otomatis (set used_at).
     *
     * @throws \RuntimeException jika rate limit kirim ulang tercapai
     */
    public function sendCode(User $user, string $purpose, Request $request): TwoFactorCode
    {
        $this->enforceResendRateLimit($user);

        // Hanguskan kode lama yang belum dipakai untuk purpose yang sama
        TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        // Buat kode baru — aman secara kriptografis
        $plain = $this->generatePlainCode();

        $code = TwoFactorCode::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make($plain),
            'purpose'    => $purpose,
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(config('two_factor.code_ttl_minutes', 10)),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Kirim email notifikasi OTP secara sinkron (time-sensitive)
        try {
            $user->notify(new TwoFactorCodeNotification(
                plainCode: $plain,
                purpose: $purpose,
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal mengirim email OTP: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'purpose' => $purpose,
                'exception' => $e,
            ]);
            throw new \RuntimeException('Gagal mengirim kode verifikasi ke email. Silakan periksa koneksi email atau coba lagi nanti.');
        }

        $this->log($user, TwoFactorLog::EVENT_CODE_SENT, $request, ['purpose' => $purpose]);
        $this->incrementResendCounter($user);

        return $code;
    }

    /**
     * Verifikasi kode OTP. Mengembalikan TwoFactorCode jika berhasil, null jika gagal.
     * Selalu mencatat log.
     */
    public function verifyCode(User $user, string $purpose, string $plainCode, Request $request): ?TwoFactorCode
    {
        $code = TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $code || ! $code->isValid()) {
            $this->log($user, TwoFactorLog::EVENT_FAILED, $request, [
                'purpose' => $purpose,
                'reason'  => 'no_valid_code',
            ]);
            return null;
        }

        if (! $code->verify($plainCode)) {
            if ($code->fresh()->isExhausted()) {
                $this->log($user, TwoFactorLog::EVENT_LOCKED, $request, ['purpose' => $purpose]);
            } else {
                $this->log($user, TwoFactorLog::EVENT_FAILED, $request, [
                    'purpose'  => $purpose,
                    'attempts' => $code->fresh()->attempts,
                ]);
            }
            return null;
        }

        $this->log($user, TwoFactorLog::EVENT_VERIFIED, $request, ['purpose' => $purpose]);
        return $code;
    }

    /**
     * Cek apakah ada kode aktif (untuk tombol kirim ulang — tahu berapa detik lagi boleh kirim).
     */
    public function getResendCooldownSeconds(User $user): int
    {
        $key = "2fa_resend_cooldown_{$user->id}";
        $ttl = Cache::get($key);
        return $ttl ? (int) ($ttl - now()->timestamp) : 0;
    }

    // ── Aktifkan / Nonaktifkan 2FA ────────────────────────────────────────────

    public function enable(User $user, Request $request): void
    {
        $user->update([
            'two_factor_enabled'    => true,
            'two_factor_enabled_at' => now(),
        ]);

        $this->log($user, TwoFactorLog::EVENT_2FA_ENABLED, $request);

        try {
            $user->notify(new \App\Notifications\TwoFactorStatusNotification('enabled'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[2FA] Gagal mengirim notifikasi status enabled: ' . $e->getMessage());
        }
    }

    public function disable(User $user, Request $request): void
    {
        $user->update([
            'two_factor_enabled'    => false,
            'two_factor_enabled_at' => null,
        ]);

        $this->revokeAllTrustedDevices($user, $request);
        $this->log($user, TwoFactorLog::EVENT_2FA_DISABLED, $request);

        try {
            $user->notify(new \App\Notifications\TwoFactorStatusNotification('disabled'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[2FA] Gagal mengirim notifikasi status disabled: ' . $e->getMessage());
        }
    }

    // ── Perangkat Tepercaya ───────────────────────────────────────────────────

    /**
     * Daftarkan perangkat tepercaya baru. Kembalikan plain token untuk disimpan di cookie.
     */
    public function trustDevice(User $user, int $days, Request $request): string
    {
        $plainToken = Str::random(64);

        $device = TrustedDevice::create([
            'user_id'      => $user->id,
            'token_hash'   => hash('sha256', $plainToken),
            'device_name'  => null,
            'user_agent'   => $request->userAgent(),
            'ip_address'   => $request->ip(),
            'last_ip'      => $request->ip(),
            'last_used_at' => now(),
            'expires_at'   => now()->addDays($days),
        ]);

        $this->log($user, TwoFactorLog::EVENT_DEVICE_TRUSTED, $request, [
            'device_id'  => $device->id,
            'expires_at' => $device->expires_at->toDateTimeString(),
            'days'       => $days,
        ]);

        return $plainToken;
    }

    /**
     * Periksa apakah perangkat saat ini tepercaya.
     */
    public function isDeviceTrusted(User $user, Request $request): bool
    {
        $plainToken = $request->cookie(config('two_factor.cookie_name', 'dcms_trusted_device'));

        if (! $plainToken) {
            return false;
        }

        $device = TrustedDevice::where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('expires_at', '>', now())
            ->first();

        if (! $device) {
            return false;
        }

        // Update IP dan waktu terakhir
        $device->update([
            'last_ip'      => $request->ip(),
            'last_used_at' => now(),
        ]);

        return true;
    }

    /**
     * Cabut perangkat tertentu.
     */
    public function revokeDevice(TrustedDevice $device, Request $request): void
    {
        $user = $device->user;
        $this->log($user, TwoFactorLog::EVENT_DEVICE_REVOKED, $request, [
            'device_id'   => $device->id,
            'device_name' => $device->display_name,
        ]);
        $device->delete();
    }

    /**
     * Cabut semua perangkat tepercaya pengguna.
     */
    public function revokeAllTrustedDevices(User $user, Request $request): void
    {
        $count = TrustedDevice::where('user_id', $user->id)->count();
        TrustedDevice::where('user_id', $user->id)->delete();

        if ($count > 0) {
            $this->log($user, TwoFactorLog::EVENT_DEVICE_REVOKED, $request, [
                'all'   => true,
                'count' => $count,
            ]);
        }
    }

    /**
     * Buat cookie perangkat tepercaya (httpOnly, secure, SameSite=Lax).
     */
    public function makeTrustedDeviceCookie(string $plainToken, int $days): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make(
            name:     config('two_factor.cookie_name', 'dcms_trusted_device'),
            value:    $plainToken,
            minutes:  $days * 24 * 60,
            path:     '/',
            domain:   null,
            secure:   config('two_factor.cookie_secure', true),
            httpOnly: true,
            raw:      false,
            sameSite: config('two_factor.cookie_samesite', 'Lax'),
        );
    }

    /**
     * Hapus cookie perangkat tepercaya (saat revoke all atau disable 2FA).
     */
    public function forgetTrustedDeviceCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::forget(config('two_factor.cookie_name', 'dcms_trusted_device'));
    }

    // ── Reset oleh Admin ──────────────────────────────────────────────────────

    /**
     * Reset 2FA pengguna: nonaktifkan dan cabut semua perangkat.
     * Dipanggil hanya oleh admin yang berwenang.
     */
    public function adminReset(User $target, User $actor, Request $request): void
    {
        $target->update([
            'two_factor_enabled'    => false,
            'two_factor_enabled_at' => null,
        ]);

        TrustedDevice::where('user_id', $target->id)->delete();

        $this->log($target, TwoFactorLog::EVENT_ADMIN_RESET, $request, [
            'actor_id'   => $actor->id,
            'actor_name' => $actor->name,
        ], $actor);

        \Illuminate\Support\Facades\Log::channel('stack')->info(
            "[2FA] Admin reset: {$actor->name} (#{$actor->id}) mereset 2FA pengguna {$target->name} (#{$target->id})"
        );
    }

    // ── Pencabutan otomatis (password/email change) ───────────────────────────

    /**
     * Dipanggil saat password atau email berubah.
     */
    public function onSensitiveDataChanged(User $user, Request $request): void
    {
        $this->revokeAllTrustedDevices($user, $request);
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function generatePlainCode(): string
    {
        $length = config('two_factor.code_length', 6);
        return str_pad(
            (string) random_int(0, (int) str_repeat('9', $length)),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }

    private function enforceResendRateLimit(User $user): void
    {
        // Cooldown per kirim
        $cooldownKey = "2fa_resend_cooldown_{$user->id}";
        if (Cache::has($cooldownKey)) {
            $seconds = (int) (Cache::get($cooldownKey) - now()->timestamp);
            throw new \RuntimeException("Tunggu {$seconds} detik sebelum mengirim ulang kode.");
        }

        // Batas per jam
        $hourlyKey   = "2fa_resend_hourly_{$user->id}";
        $max         = config('two_factor.resend_max_per_hour', 5);
        $sent        = (int) Cache::get($hourlyKey, 0);
        if ($sent >= $max) {
            throw new \RuntimeException('Batas pengiriman kode per jam tercapai. Coba lagi nanti.');
        }
    }

    private function incrementResendCounter(User $user): void
    {
        $cooldown  = config('two_factor.resend_cooldown_seconds', 60);
        $cooldownKey = "2fa_resend_cooldown_{$user->id}";
        Cache::put($cooldownKey, now()->addSeconds($cooldown)->timestamp, $cooldown);

        $hourlyKey = "2fa_resend_hourly_{$user->id}";
        Cache::increment($hourlyKey);
        // TTL hourly key: 1 jam (auto-expire)
        if (! Cache::has("{$hourlyKey}_ttl")) {
            Cache::put("{$hourlyKey}_ttl", 1, 3600);
            Cache::put($hourlyKey, Cache::get($hourlyKey, 0), 3600);
        }
    }

    public function log(
        User    $user,
        string  $event,
        Request $request,
        array   $meta  = [],
        ?User   $actor = null
    ): void {
        TwoFactorLog::create([
            'user_id'    => $user->id,
            'event'      => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta'       => $meta ?: null,
            'actor_id'   => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
