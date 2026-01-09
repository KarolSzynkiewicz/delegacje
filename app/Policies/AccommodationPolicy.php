<?php

namespace App\Policies;

use App\Models\Accommodation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccommodationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accommodations.viewAny');
    }   

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('accommodations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission('accommodations.forceDelete');
    }
}
