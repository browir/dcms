<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Kegiatan unit = pengumuman internal: semua karyawan boleh melihat kegiatan dalam
 * jangkauannya (lihat Agenda::scopeAccess). Membuat kegiatan mengikuti izin membuat
 * rapat (atau permission Shield Create:Agenda), jadi role yang ada tidak perlu diubah.
 */
class AgendaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Agenda $agenda): bool
    {
        return Agenda::query()->access()->whereKey($agenda->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Agenda') || $user->can('Create:Meeting');
    }

    public function update(User $user, Agenda $agenda): bool
    {
        return $this->manages($user, $agenda) || $user->can('Update:Agenda');
    }

    public function delete(User $user, Agenda $agenda): bool
    {
        // Kegiatan yang sudah punya nomor surat tidak dihapus (nomor tidak bisa dibatalkan).
        return ! $agenda->letter_number_id && ($this->manages($user, $agenda) || $user->can('Delete:Agenda'));
    }

    /** Mengumumkan & membuat surat pemberitahuan: pembuat kegiatan atau admin. */
    public function announce(User $user, Agenda $agenda): bool
    {
        return $this->update($user, $agenda);
    }

    private function manages(User $user, Agenda $agenda): bool
    {
        return $agenda->created_by === $user->id || $user->hasRole('super_admin');
    }
}
