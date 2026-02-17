<?php

namespace App\Policies;

use App\Models\TimeLog;
use App\Models\User;

class TimeLogPolicy
{
    /**
     * Determine if the user can view any time logs.
     */
    public function viewAny(User $user): bool
    {
        // Administrator widzi wszystkie
        if ($user->isAdmin()) {
            return true;
        }

        // Sprawdź uprawnienia z roli - jeśli ma uprawnienie w roli, widzi wszystkie
        if ($user->hasPermission('time-logs.viewAny') || $user->hasPermission('time-logs.view')) {
            return true;
        }

        // Kierownik widzi time-logi ze swoich projektów
        $managedProjectIds = $user->getManagedProjectIds();
        return !empty($managedProjectIds);
    }

    /**
     * Determine if the user can view the time log.
     */
    public function view(User $user, TimeLog $timeLog): bool
    {
        // Administrator widzi wszystkie
        if ($user->isAdmin()) {
            return true;
        }

        // Sprawdź uprawnienia z roli - jeśli ma uprawnienie w roli, widzi wszystkie
        if ($user->hasPermission('time-logs.viewAny') || $user->hasPermission('time-logs.view')) {
            return true;
        }

        $assignment = $timeLog->projectAssignment;
        if (!$assignment) {
            return false;
        }

        // Kierownik widzi tylko time-logi ze swoich projektów
        return $user->managesProject($assignment->project_id);
    }

    /**
     * Determine if the user can create time logs.
     */
    public function create(User $user, ?int $assignmentId = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Sprawdź uprawnienia z roli - jeśli ma uprawnienie w roli, może tworzyć wszystkie
        if ($user->hasPermission('time-logs.create')) {
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

        // Sprawdź uprawnienia z roli - jeśli ma uprawnienie w roli, może aktualizować wszystkie
        if ($user->hasPermission('time-logs.update')) {
            return true;
        }

        $assignment = $timeLog->projectAssignment;
        if (!$assignment) {
            return false;
        }

        return $user->managesProject($assignment->project_id);
    }

    /**
     * Determine if the user can delete the time log.
     */
    public function delete(User $user, TimeLog $timeLog): bool
    {
        // Tylko admin może usuwać
        return $user->isAdmin();
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

        // Sprawdź uprawnienia z roli - jeśli ma uprawnienie w roli, może aktualizować wszystkie
        if ($user->hasPermission('time-logs.update')) {
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
