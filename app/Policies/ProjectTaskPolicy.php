<?php

namespace App\Policies;

use App\Models\ProjectTask;
use App\Models\User;

class ProjectTaskPolicy
{
    /**
     * Determine if the user can update the task status.
     */
    public function updateStatus(User $user, ProjectTask $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Sprawdź czy user zarządza projektem zadania
        return $user->managesProject($task->project_id);
    }

    /**
     * Determine if the user can mark task as in progress.
     */
    public function markInProgress(User $user, ProjectTask $task): bool
    {
        return $this->updateStatus($user, $task);
    }

    /**
     * Determine if the user can mark task as completed.
     */
    public function markCompleted(User $user, ProjectTask $task): bool
    {
        return $this->updateStatus($user, $task);
    }

    /**
     * Determine if the user can cancel the task.
     */
    public function cancel(User $user, ProjectTask $task): bool
    {
        return $this->updateStatus($user, $task);
    }
}
