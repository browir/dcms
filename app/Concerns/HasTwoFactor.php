<?php

namespace App\Concerns;

use App\Models\TrustedDevice;
use App\Models\TwoFactorCode;
use App\Models\TwoFactorLog;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait HasTwoFactor
 *
 * Ditambahkan ke User model untuk menyediakan relasi dan helper 2FA.
 */
trait HasTwoFactor
{
    // ── Relasi ────────────────────────────────────────────────────────────────

    public function twoFactorCodes(): HasMany
    {
        return $this->hasMany(TwoFactorCode::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function twoFactorLogs(): HasMany
    {
        return $this->hasMany(TwoFactorLog::class);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled;
    }

    /**
     * Apakah 2FA diwajibkan untuk role user ini?
     */
    public function isTwoFactorRequired(): bool
    {
        $requiredRoles = config('two_factor.required_roles', []);
        if (empty($requiredRoles)) {
            return false;
        }

        return $this->hasAnyRole($requiredRoles);
    }

    /**
     * Daftar perangkat tepercaya yang masih aktif.
     */
    public function activeTrustedDevices()
    {
        return $this->trustedDevices()->active()->orderByDesc('last_used_at');
    }
}
