<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TransportCost;

class TransportCostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('transport-costs.viewAny');
    }

    public function view(User $user, TransportCost $transportCost): bool
    {
        return $user->hasPermission('transport-costs.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('transport-costs.create');
    }

    public function update(User $user, TransportCost $transportCost): bool
    {
        return $user->hasPermission('transport-costs.update');
    }

    public function delete(User $user, TransportCost $transportCost): bool
    {
        return $user->hasPermission('transport-costs.delete');
    }
}
