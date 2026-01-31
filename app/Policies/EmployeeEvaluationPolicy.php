<?php

namespace App\Policies;

use App\Models\EmployeeEvaluation;
use App\Models\User;

class EmployeeEvaluationPolicy
{
    /**
     * Determine if the user can create evaluations.
     * 
     * @param User $user
     * @param string|\App\Models\EmployeeEvaluation $model - Model class (ignored, kept for Laravel Gate compatibility)
     * @param int|null $employeeId - Employee ID to check
     */
    public function create(User $user, $model, ?int $employeeId = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Jeśli nie podano employee_id, sprawdź w request
        if (!$employeeId) {
            $employeeId = request()->input('employee_id');
        }

        if (!$employeeId) {
            return false;
        }

        // Sprawdź czy employee jest przypisany do projektu, którym user zarządza
        $userProjectIds = $user->getManagedProjectIds();
        if (empty($userProjectIds)) {
            return false;
        }

        return \App\Models\ProjectAssignment::whereIn('project_id', $userProjectIds)
            ->where('employee_id', $employeeId)
            ->exists();
    }

    /**
     * Determine if the user can update the evaluation.
     */
    public function update(User $user, EmployeeEvaluation $evaluation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Sprawdź czy employee z oceny jest przypisany do projektu, którym user zarządza
        $userProjectIds = $user->getManagedProjectIds();
        if (empty($userProjectIds)) {
            return false;
        }

        return \App\Models\ProjectAssignment::whereIn('project_id', $userProjectIds)
            ->where('employee_id', $evaluation->employee_id)
            ->exists();
    }

    /**
     * Determine if the user can delete the evaluation.
     */
    public function delete(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $this->update($user, $evaluation);
    }
}
