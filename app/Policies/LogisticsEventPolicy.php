<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LogisticsEvent;

class LogisticsEventPolicy
{
    /**
     * Determine if the user can view any logistics events.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('logistics-events.viewAny');
    }

    /**
     * Determine if the user can view the logistics event.
     */
    public function view(User $user, LogisticsEvent $logisticsEvent): bool
    {
        return $user->hasPermission('logistics-events.view');
    }

    /**
     * Determine if the user can create logistics events.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('logistics-events.create');
    }

    /**
     * Determine if the user can update the logistics event.
     */
    public function update(User $user, LogisticsEvent $logisticsEvent): bool
    {
        return $user->hasPermission('logistics-events.update');
    }

    /**
     * Determine if the user can delete the logistics event.
     */
    public function delete(User $user, LogisticsEvent $logisticsEvent): bool
    {
        return $user->hasPermission('logistics-events.delete');
    }
}
