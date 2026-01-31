<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image_path',
        'employee_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the employee associated with this user.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the projects managed by this user.
     */
    public function managedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_managers')
            ->withTimestamps();
    }

    /**
     * Get the IDs of projects managed by this user.
     */
    public function getManagedProjectIds(): array
    {
        return $this->managedProjects()->pluck('project_id')->toArray();
    }

    /**
     * Check if user manages a specific project.
     */
    public function managesProject(int $projectId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        return $this->managedProjects()->where('project_id', $projectId)->exists();
    }

    /**
     * Get the delegations for the user (employee).
     */
    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class, 'employee_id');
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('administrator');
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole('kierownik');
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee(): bool
    {
        return $this->hasRole('pracownik-biurowy');
    }

    /**
     * Check if user has a specific permission (using Spatie).
     * Wrapper method for backward compatibility.
     * 
     * Dla kierowników: jeśli zarządza projektem związanym z akcją, przyznaje dostęp
     * nawet bez przypisanego permission w tabeli.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Admin zawsze ma wszystkie uprawnienia
        if ($this->isAdmin()) {
            return true;
        }

        // Sprawdź czy to jest akcja dla kierownika i czy user zarządza projektem
        $managerPermission = $this->checkManagerPermission($permissionName);
        if ($managerPermission !== null) {
            return $managerPermission;
        }

        // Użyj checkPermissionTo() zamiast hasPermissionTo()
        // - zwraca false zamiast rzucać wyjątek gdy uprawnienie nie istnieje
        return $this->checkPermissionTo($permissionName);
    }

    /**
     * Sprawdź czy user zarządza projektem związanym z akcją kierownika.
     * Zwraca true jeśli ma dostęp, false jeśli nie, null jeśli to nie jest akcja kierownika.
     */
    protected function checkManagerPermission(string $permissionName): ?bool
    {
        // Sprawdź tylko dla konkretnych permissions kierownika
        $managerPermissions = [
            'employee-evaluations.create',
            'employee-evaluations.update',
            'employee-evaluations.delete',
            'project-tasks.update', // Dla mark-in-progress, mark-completed, cancel
            'time-logs.update', // Dla bulk-update
        ];

        if (!in_array($permissionName, $managerPermissions)) {
            return null; // Nie nasza akcja - kontynuuj standardowe sprawdzanie
        }

        // Sprawdź czy user zarządza jakimkolwiek projektem
        $userProjectIds = $this->getManagedProjectIds();
        if (empty($userProjectIds)) {
            return null; // Nie zarządza projektami - kontynuuj standardowe sprawdzanie
        }

        // Sprawdź czy user zarządza projektem związanym z tym zasobem
        switch ($permissionName) {
            case 'employee-evaluations.create':
                // Sprawdź employee_id z requestu
                $employeeId = request()->input('employee_id');
                if ($employeeId) {
                    $hasAccess = \App\Models\ProjectAssignment::whereIn('project_id', $userProjectIds)
                        ->where('employee_id', $employeeId)
                        ->exists();
                    return $hasAccess;
                }
                break;

            case 'employee-evaluations.update':
            case 'employee-evaluations.delete':
                // Sprawdź employee_id z modelu w route
                $route = request()->route();
                if ($route) {
                    $evaluation = $route->parameter('employeeEvaluation') ?? $route->parameter('employee_evaluation');
                    if ($evaluation instanceof \App\Models\EmployeeEvaluation) {
                        $hasAccess = \App\Models\ProjectAssignment::whereIn('project_id', $userProjectIds)
                            ->where('employee_id', $evaluation->employee_id)
                            ->exists();
                        return $hasAccess;
                    }
                }
                break;

            case 'project-tasks.update':
                // Sprawdź project_id z zadania w route
                $route = request()->route();
                if ($route) {
                    $task = $route->parameter('task');
                    if ($task instanceof \App\Models\ProjectTask) {
                        return $this->managesProject($task->project_id);
                    }
                }
                break;

            case 'time-logs.update':
                // Sprawdź assignments z requestu (bulk-update)
                $entries = request()->input('entries', []);
                if (!empty($entries)) {
                    $assignmentIds = collect($entries)->pluck('assignment_id')->unique()->toArray();
                    $unauthorizedAssignments = \App\Models\ProjectAssignment::whereIn('id', $assignmentIds)
                        ->whereNotIn('project_id', $userProjectIds)
                        ->exists();
                    return !$unauthorizedAssignments;
                }
                break;
        }

        // Jeśli nie udało się zweryfikować - kontynuuj standardowe sprawdzanie
        return null;
    }

    /**
     * Get the image URL for the user.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return asset('storage/' . $this->image_path);
    }

    /**
     * Get user initials for avatar.
     */
    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);
        $initials = '';
        foreach ($parts as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials;
    }
}
