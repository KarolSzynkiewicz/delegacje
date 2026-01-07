<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EquipmentIssue;

class EquipmentIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('equipment-issues.viewAny');
    }

    public function view(User $user, EquipmentIssue $equipmentIssue): bool
    {
        return $user->hasPermission('equipment-issues.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('equipment-issues.create');
    }

    public function update(User $user, EquipmentIssue $equipmentIssue): bool
    {
        return $user->hasPermission('equipment-issues.update');
    }

    public function delete(User $user, EquipmentIssue $equipmentIssue): bool
    {
        return $user->hasPermission('equipment-issues.delete');
    }
}
