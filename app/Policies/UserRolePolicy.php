<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Auth\Access\Response;

class UserRolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tymczasowo pozwól wszystkim zalogowanym użytkownikom przeglądać role
        // Po przypisaniu ról użytkownikom, zmień na: return $user->hasPermission('user-roles.viewAny');
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserRole $userRole): bool
    {
        // Tymczasowo pozwól wszystkim zalogowanym użytkownikom przeglądać role
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Tymczasowo tylko admin może tworzyć role
        return $user->isAdmin() || $user->hasPermission('user-roles.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserRole $userRole): bool
    {
        // Tymczasowo tylko admin może edytować role
        return $user->isAdmin() || $user->hasPermission('user-roles.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserRole $userRole): bool
    {
        // Tymczasowo tylko admin może usuwać role
        return $user->isAdmin() || $user->hasPermission('user-roles.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserRole $userRole): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserRole $userRole): bool
    {
        //
    }
}
