<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalRequestPolicy
{
    public function view(User $user, ApprovalRequest $approval): bool
    {
        if ($user->isAdmin() || $approval->isApprover($user) || $approval->isCreator($user)) {
            return true;
        }

        return $user->hasPermission('tasks.view');
    }

    public function decide(User $user, ApprovalRequest $approval): bool
    {
        return $approval->isApprover($user) && ! $approval->isDecided();
    }
}
