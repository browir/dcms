<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentCategory') || $authUser->hasRole('super_admin');
    }

    public function view(AuthUser $authUser, DocumentTemplate $documentTemplate): bool
    {
        return $authUser->can('View:DocumentCategory') || $authUser->hasRole('super_admin');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentCategory') || $authUser->hasRole('super_admin');
    }

    public function update(AuthUser $authUser, DocumentTemplate $documentTemplate): bool
    {
        return $authUser->can('Update:DocumentCategory') || $authUser->hasRole('super_admin');
    }

    public function delete(AuthUser $authUser, DocumentTemplate $documentTemplate): bool
    {
        return $authUser->can('Delete:DocumentCategory') || $authUser->hasRole('super_admin');
    }

    public function restore(AuthUser $authUser, DocumentTemplate $documentTemplate): bool
    {
        return $authUser->can('Restore:DocumentCategory') || $authUser->hasRole('super_admin');
    }

    public function forceDelete(AuthUser $authUser, DocumentTemplate $documentTemplate): bool
    {
        return $authUser->can('ForceDelete:DocumentCategory') || $authUser->hasRole('super_admin');
    }
}

