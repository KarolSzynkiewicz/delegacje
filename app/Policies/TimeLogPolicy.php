<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TimeLog;

class TimeLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('time-logs.viewAny');
    }

    public function view(User $user, TimeLog $timeLog): bool
    {
        return $user->hasPermission('time-logs.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('time-logs.create');
    }

    public function update(User $user, TimeLog $timeLog): bool
    {
        return $user->hasPermission('time-logs.update');
    }

    public function delete(User $user, TimeLog $timeLog): bool
    {
        return $user->hasPermission('time-logs.delete');
    }
}
