<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LetterNumber;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Hak akses penomoran dokumen:
 * - Hanya user/role dengan permission ViewAny:LetterNumber yang dapat melihat menu dan daftar nomor.
 * - Hanya user/role dengan permission Create:LetterNumber yang dapat menerbitkan nomor dokumen baru.
 * - Pengaturan hak akses dikelola oleh Super Admin di menu Peran & Hak Akses (Shield).
 * - Nomor yang sudah terbit tidak bisa diubah atau dihapus.
 */
class LetterNumberPolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): ?bool
    {
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LetterNumber');
    }

    public function view(AuthUser $authUser, LetterNumber $letterNumber): bool
    {
        return $authUser->can('View:LetterNumber') || $authUser->can('ViewAny:LetterNumber');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LetterNumber')
            || $authUser->can('CreateManual:LetterNumber')
            || (method_exists($authUser, 'hasRole') && $authUser->hasRole('Sekretaris'));
    }

    public function update(AuthUser $authUser, LetterNumber $letterNumber): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, LetterNumber $letterNumber): bool
    {
        return false;
    }
}
