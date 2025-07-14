<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MobileSyncValidationIssue;
use Illuminate\Auth\Access\HandlesAuthorization;

class MobileSyncValidationIssuePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MobileSyncValidationIssue $mobileSyncValidationIssue): bool
    {
        return $user->can('view_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MobileSyncValidationIssue $mobileSyncValidationIssue): bool
    {
        return $user->can('update_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MobileSyncValidationIssue $mobileSyncValidationIssue): bool
    {
        return $user->can('delete_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, MobileSyncValidationIssue $mobileSyncValidationIssue): bool
    {
        return $user->can('force_delete_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, MobileSyncValidationIssue $mobileSyncValidationIssue): bool
    {
        return $user->can('restore_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, MobileSyncValidationIssue $mobileSyncValidationIssue): bool
    {
        return $user->can('replicate_mobile::sync::validation::issue');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_mobile::sync::validation::issue');
    }
}
