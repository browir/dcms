<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LetterNumber;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Nomor dokumen diambil sebelum dokumen dibuat, jadi siapa pun yang boleh membuat/melihat
 * dokumen juga boleh mengambil/melihat nomor — tanpa perlu menambah permission di tiap role.
 * Nomor yang sudah terbit tidak bisa diubah atau dihapus (sama seperti HRIS).
 */
class LetterNumberPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LetterNumber') || $authUser->can('ViewAny:Document');
    }

    public function view(AuthUser $authUser, LetterNumber $letterNumber): bool
    {
        return $this->viewAny($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LetterNumber') || $authUser->can('Create:Document');
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
