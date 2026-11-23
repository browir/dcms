<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\TwoFactorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class TwoFactorService
{
    // ── Aktifkan / Nonaktifkan 2FA (Google Authenticator) ─────────────────────

    /**
     * Aktifkan Google Authenticator untuk pengguna.
     */
    public function enable(User $user, string $secret, Request $request): void
    {
        $user->update([
            'two_factor_enabled'    => true,
            'two_factor_enabled_at' => now(),
            'two_factor_type'       => 'totp',
            'two_factor_secret'     => $secret,
        ]);

        $this->log($user, TwoFactorLog::EVENT_2FA_ENABLED, $request, ['method' => 'totp']);

        try {
            $user->notify(new \App\Notifications\TwoFactorStatusNotification('enabled'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[2FA] Gagal mengirim notifikasi status enabled: ' . $e->getMessage());
        }
    }

    /**
     * Alias untuk enable() dengan parameter secret.
     */
    public function enableTotp(User $user, string $secret, ?Request $request = null): void
    {
        $this->enable($user, $secret, $request ?? request());
    }

    /**
     * Nonaktifkan 2FA pengguna dan cabut semua perangkat tepercaya.
     */
    public function disable(User $user, Request $request): void
    {
        $user->update([
            'two_factor_enabled'    => false,
            'two_factor_enabled_at' => null,
            'two_factor_type'       => 'totp',
            'two_factor_secret'     => null,
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
     * Dipanggil oleh admin atau artisan command.
     */
    public function adminReset(User $target, User|string $actor, ?Request $request = null): void
    {
        $target->update([
            'two_factor_enabled'    => false,
            'two_factor_enabled_at' => null,
            'two_factor_type'       => 'totp',
            'two_factor_secret'     => null,
        ]);

        TrustedDevice::where('user_id', $target->id)->delete();

        $req = $request ?? request();
        $actorId = $actor instanceof User ? $actor->id : null;
        $actorName = $actor instanceof User ? $actor->name : (string) $actor;

        $this->log($target, TwoFactorLog::EVENT_ADMIN_RESET, $req, [
            'actor_id'   => $actorId,
            'actor_name' => $actorName,
        ], $actor instanceof User ? $actor : null);

        \Illuminate\Support\Facades\Log::channel('stack')->info(
            "[2FA] Admin reset: {$actorName} mereset 2FA pengguna {$target->name} (#{$target->id})"
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

    // ── Audit Log ─────────────────────────────────────────────────────────────

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
