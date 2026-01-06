<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ProjectAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'employee_id',
        'role_id',
        'start_date',
        'end_date',
        'status', 
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the project for this assignment.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the employee for this assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the role for this assignment.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the time logs for this assignment.
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Scope a query to only include active assignments.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include assignments within a date range.
     */
    public function scopeInDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($assignment) {
            // Sprawdź czy pracownik ma daną rolę
            if ($assignment->employee_id && $assignment->role_id) {
                $employee = Employee::with('roles')->find($assignment->employee_id);
                
                if (!$employee) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Pracownik nie został znaleziony.');
                }

                $hasRole = $employee->roles->contains('id', $assignment->role_id);

                if (!$hasRole) {
                    $role = Role::find($assignment->role_id);
                    $roleName = $role ? $role->name : 'nieznana';
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ["role_id" => "Pracownik {$employee->full_name} nie posiada roli: {$roleName}. Nie można przypisać go do projektu z tą rolą."]
                    );
                }
            }
        });
    }
}
