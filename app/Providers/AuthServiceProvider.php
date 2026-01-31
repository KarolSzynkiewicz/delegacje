<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\EmployeeEvaluation::class => \App\Policies\EmployeeEvaluationPolicy::class,
        \App\Models\ProjectTask::class => \App\Policies\ProjectTaskPolicy::class,
        \App\Models\TimeLog::class => \App\Policies\TimeLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Hook przed standardowym sprawdzaniem permissions
        // Jeśli user zarządza projektem, pozwól na dostęp (pomija sprawdzanie permissions)
        Gate::before(function ($user, $ability, $arguments = []) {
            // Admin zawsze ma dostęp
            if ($user->isAdmin()) {
                return null; // null = kontynuuj standardowe sprawdzanie (ale admin i tak ma wszystkie permissions)
            }

            // Sprawdź tylko dla konkretnych akcji chronionych przez Policy
            $managerAbilities = [
                'create' => [\App\Models\EmployeeEvaluation::class],
                'update' => [\App\Models\EmployeeEvaluation::class],
                'delete' => [\App\Models\EmployeeEvaluation::class],
                'markInProgress' => [\App\Models\ProjectTask::class],
                'markCompleted' => [\App\Models\ProjectTask::class],
                'cancel' => [\App\Models\ProjectTask::class],
                'bulkUpdate' => [\App\Models\TimeLog::class],
            ];

            // Sprawdź czy to jedna z akcji dla kierownika
            if (!isset($managerAbilities[$ability])) {
                return null; // Nie nasza akcja - kontynuuj standardowe sprawdzanie
            }

            $modelClass = $managerAbilities[$ability][0];
            
            // Sprawdź czy user zarządza jakimkolwiek projektem
            $userProjectIds = $user->getManagedProjectIds();
            if (empty($userProjectIds)) {
                return null; // Nie zarządza projektami - kontynuuj standardowe sprawdzanie
            }

            // Sprawdź czy user zarządza projektem związanym z tym zasobem
            switch ($ability) {
                case 'create':
                    // Dla EmployeeEvaluation - sprawdź employee_id z requestu
                    if ($modelClass === \App\Models\EmployeeEvaluation::class) {
                        $employeeId = request()->input('employee_id') ?? ($arguments[1] ?? null);
                        if ($employeeId) {
                            $hasAccess = \App\Models\ProjectAssignment::whereIn('project_id', $userProjectIds)
                                ->where('employee_id', $employeeId)
                                ->exists();
                            return $hasAccess ? true : null;
                        }
                    }
                    break;

                case 'update':
                case 'delete':
                    // Dla EmployeeEvaluation - sprawdź employee_id z modelu
                    if ($modelClass === \App\Models\EmployeeEvaluation::class && isset($arguments[0])) {
                        $evaluation = $arguments[0];
                        if ($evaluation instanceof \App\Models\EmployeeEvaluation) {
                            $hasAccess = \App\Models\ProjectAssignment::whereIn('project_id', $userProjectIds)
                                ->where('employee_id', $evaluation->employee_id)
                                ->exists();
                            return $hasAccess ? true : null;
                        }
                    }
                    break;

                case 'markInProgress':
                case 'markCompleted':
                case 'cancel':
                    // Dla ProjectTask - sprawdź project_id z modelu
                    if ($modelClass === \App\Models\ProjectTask::class && isset($arguments[0])) {
                        $task = $arguments[0];
                        if ($task instanceof \App\Models\ProjectTask) {
                            return $user->managesProject($task->project_id) ? true : null;
                        }
                    }
                    break;

                case 'bulkUpdate':
                    // Dla TimeLog - sprawdź assignments z requestu
                    if ($modelClass === \App\Models\TimeLog::class) {
                        $entries = $arguments[1] ?? request()->input('entries', []);
                        if (!empty($entries)) {
                            $assignmentIds = collect($entries)->pluck('assignment_id')->unique()->toArray();
                            $unauthorizedAssignments = \App\Models\ProjectAssignment::whereIn('id', $assignmentIds)
                                ->whereNotIn('project_id', $userProjectIds)
                                ->exists();
                            return !$unauthorizedAssignments ? true : null;
                        }
                    }
                    break;
            }

            // Jeśli nie udało się zweryfikować - kontynuuj standardowe sprawdzanie
            return null;
        });
    }
}
