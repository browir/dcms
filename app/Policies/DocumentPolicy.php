<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Document');
    }

    public function view(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('View:Document');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Document');
    }

    public function update(AuthUser $authUser, Document $document): bool
    {
        if (! $authUser->can('Update:Document')) {
            return false;
        }

        // Pemilik dokumen selalu bisa edit
        if ($authUser->id === $document->user_id) {
            return true;
        }

        // Admin dengan permission khusus bisa edit dokumen milik orang lain
        if ($authUser->can('edit_other_documents')) {
            return true;
        }

        // Kabid bisa update (ACC/Tolak) dokumen dalam departemennya
        if ($authUser->hasRole('kabid')) {
            /** @var User $authUser */
            if ($authUser->department_id && $document->department_id === $authUser->department_id) {
                return true;
            }
            if (! $authUser->department_id && $document->company_id === $authUser->company_id) {
                return true;
            }
        }

        // Direktur bisa update (ACC/Tolak) dokumen dalam perusahaannya
        if ($authUser->hasRole('direktur') && $document->company_id === $authUser->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Apakah user bisa mereview (approve/reject) dokumen.
     * Digunakan oleh Filament Action di Reviewer Panel.
     */
    public function review(AuthUser $authUser, Document $document): bool
    {
        /** @var User $authUser */

        // Kabid: review dokumen di departemennya yang sedang menunggu ACC Kabid
        if ($authUser->hasRole('kabid') && $document->status === Document::STATUS_PENDING_KABID) {
            if ($authUser->department_id) {
                return $document->department_id === $authUser->department_id;
            }

            return $document->company_id === $authUser->company_id;
        }

        // Direktur: review dokumen yang di-ACC Kabid atau yang menunggu persetujuan Direktur
        if ($authUser->hasRole('direktur') && in_array($document->status, [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR])) {
            return $document->company_id === $authUser->company_id;
        }

        // Super admin bisa review semua
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return false;
    }

    public function delete(AuthUser $authUser, Document $document): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Delete:Document');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Delete:Document') || $authUser->can('DeleteAny:Document');
    }

    public function restore(AuthUser $authUser, Document $document): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Restore:Document');
    }

    public function forceDelete(AuthUser $authUser, Document $document): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('ForceDelete:Document');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('ForceDeleteAny:Document');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('RestoreAny:Document');
    }

    public function replicate(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Replicate:Document');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Document');
    }

    public function viewOwnCompany(AuthUser $authUser): bool
    {
        return $authUser->can('view_own_company_data');
    }

    public function viewAllCompanies(AuthUser $authUser): bool
    {
        return $authUser->can('view_all_companies_data');
    }
}
