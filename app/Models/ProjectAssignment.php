<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasDateRange;
use App\Traits\HasAssignmentLifecycle;
use App\Contracts\AssignmentContract;
use App\Models\Employee;
use Carbon\Carbon;

class ProjectAssignment extends Model implements AssignmentContract
{
    use HasFactory, 
        HasDateRange, 
        HasAssignmentLifecycle {
            HasAssignmentLifecycle::scopeActiveAtDate insteadof HasDateRange;
            HasAssignmentLifecycle::scopeCompleted insteadof HasDateRange;
            HasAssignmentLifecycle::scopeScheduled insteadof HasDateRange;
        }

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
        'actual_start_date',
        'actual_end_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
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
     * Implementation of AssignmentContract::getEmployee()
     */
    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    /**
     * Implementation of AssignmentContract::getStartDate()
     */
    public function getStartDate(): Carbon
    {
        return $this->start_date;
    }

    /**
     * Implementation of AssignmentContract::getEndDate()
     */
    public function getEndDate(): ?Carbon
    {
        return $this->end_date;
    }

    /**
     * Scope: Filter assignments that are active (date-based).
     * Uses HasAssignmentLifecycle::scopeActive() which filters by dates only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeActiveAtDate($query, Carbon::today());
    }

}
