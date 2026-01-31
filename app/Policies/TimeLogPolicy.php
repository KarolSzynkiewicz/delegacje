<?php

namespace App\Policies;

use App\Models\TimeLog;
use App\Models\User;

class TimeLogPolicy
{
    /**
     * Determine if the user can create time logs.
     */
    public function create(User $user, ?int $assignmentId = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$assignmentId) {
            $assignmentId = request()->input('project_assignment_id') 
                ?? request()->input('entries.*.assignment_id');
        }

        if (!$assignmentId) {
            return false;
        }

        $assignment = \App\Models\ProjectAssignment::find($assignmentId);
        if (!$assignment) {
            return false;
        }

        return $user->managesProject($assignment->project_id);
    }

    /**
     * Determine if the user can update the time log.
     */
    public function update(User $user, TimeLog $timeLog): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $assignment = $timeLog->projectAssignment;
        if (!$assignment) {
            return false;
        }

        return $user->managesProject($assignment->project_id);
    }

    /**
     * Determine if the user can bulk update time logs.
     * 
     * @param User $user
     * @param string|\App\Models\TimeLog $model - Model class (ignored, kept for Laravel Gate compatibility)
     * @param array $entries - Array of time log entries
     */
    public function bulkUpdate(User $user, $model, array $entries = []): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (empty($entries)) {
            $entries = request()->input('entries', []);
        }

        if (empty($entries)) {
            return false;
        }

        $assignmentIds = collect($entries)->pluck('assignment_id')->unique()->toArray();
        $userProjectIds = $user->getManagedProjectIds();

        if (empty($userProjectIds)) {
            return false;
        }

        // Sprawdź czy wszystkie assignments należą do projektów użytkownika
        $unauthorizedAssignments = \App\Models\ProjectAssignment::whereIn('id', $assignmentIds)
            ->whereNotIn('project_id', $userProjectIds)
            ->exists();

        return !$unauthorizedAssignments;
    }
}
