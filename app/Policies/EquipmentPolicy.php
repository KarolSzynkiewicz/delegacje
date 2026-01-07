<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Equipment;

class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('equipment.viewAny');
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $user->hasPermission('equipment.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('equipment.create');
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $user->hasPermission('equipment.update');
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->hasPermission('equipment.delete');
    }
}
