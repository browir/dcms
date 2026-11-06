<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TwoFactorPolicy
{
    use HandlesAuthorization;

    /**
     * Hanya super_admin atau admin yang boleh mereset 2FA pengguna lain.
     * Admin tidak bisa mereset 2FA admin lain (hanya super_admin yang bisa).
     */
    public function resetTwoFactor(User $actor, User $target): bool
    {
        // Tidak bisa reset milik sendiri lewat aksi admin
        if ($actor->id === $target->id) {
            return false;
        }

        // super_admin bisa reset siapa saja
        if ($actor->hasRole('super_admin')) {
            return true;
        }

        // admin hanya bisa reset user biasa (bukan sesama admin / super_admin)
        if ($actor->hasRole('admin')) {
            return ! $target->hasAnyRole(['admin', 'super_admin']);
        }

        return false;
    }
}
