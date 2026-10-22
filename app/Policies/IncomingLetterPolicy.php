<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Izin Shield (ViewAny/View/Create/Update/Delete:IncomingLetter) + cakupan data
 * IncomingLetter::scopeAccess (unit tujuan, kabid departemen, direktur, pencatat).
 */
class IncomingLetterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:IncomingLetter');
    }

    public function view(User $user, IncomingLetter $letter): bool
    {
        return $user->can('View:IncomingLetter')
            && IncomingLetter::query()->access($user)->whereKey($letter->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('Create:IncomingLetter');
    }

    public function update(User $user, IncomingLetter $letter): bool
    {
        return $user->can('Update:IncomingLetter') && $this->view($user, $letter);
    }

    /** Menandai selesai: unit tujuan / kabid / pencatat yang boleh melihat surat ini. */
    public function complete(User $user, IncomingLetter $letter): bool
    {
        return $letter->status !== IncomingLetter::STATUS_SELESAI && $this->view($user, $letter);
    }

    public function delete(User $user, IncomingLetter $letter): bool
    {
        return $user->can('Delete:IncomingLetter')
            && ($letter->created_by === $user->id || $user->hasRole('super_admin'));
    }
}
