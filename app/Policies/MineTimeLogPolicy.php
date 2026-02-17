<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProjectAssignment;

class MineTimeLogPolicy
{
    /**
     * Get assignment IDs for time logs that user can access in mine view.
     * Returns empty array if user doesn't manage any projects.
     */
    public function getScopeAssignmentIds(User $user): array
    {
        // Admin nie powinien używać /mine/* - przekieruj do globalnego widoku
        if ($user->isAdmin()) {
            return [];
        }

        // Pobierz ID projektów którymi zarządza użytkownik
        $projectIds = $user->getManagedProjectIds();

        if (empty($projectIds)) {
            return [];
        }

        // Pobierz ID przypisań do tych projektów
        return ProjectAssignment::whereIn('project_id', $projectIds)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if user can access mine time logs view.
     */
    public function viewAny(User $user): bool
    {
        // Admin nie powinien używać /mine/* - przekieruj do globalnego widoku
        if ($user->isAdmin()) {
            return false;
        }

        // User musi zarządzać przynajmniej jednym projektem
        $projectIds = $user->getManagedProjectIds();
        return !empty($projectIds);
    }
}
