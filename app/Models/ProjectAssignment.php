<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasDateRange;
use App\Models\Employee;
use App\Enums\AssignmentStatus;


class ProjectAssignment extends Model
{
    use HasFactory, HasDateRange;

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
        'notes',
        'is_cancelled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_cancelled' => 'boolean',
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
     * Get the computed status based on dates.
     * 
     * Status calculation priority:
     * 1. If is_cancelled is true, return CANCELLED
     * 2. If assignment is currently active (start_date <= today <= end_date or end_date is null) -> ACTIVE
     * 3. If assignment is in the past (end_date < today) -> COMPLETED
     * 4. If assignment is scheduled (start_date > today) -> ACTIVE
     * 
     * This ensures that "active" status is only shown for assignments that are actually active now,
     * not for past assignments.
     * 
     * @return AssignmentStatus
     */
    public function getStatusAttribute($value): AssignmentStatus
    {
        // If assignment is explicitly cancelled, return CANCELLED
        if ($this->is_cancelled) {
            return AssignmentStatus::CANCELLED;
        }

        // Calculate status based on dates
        if ($this->isCurrentlyActive()) {
            // Currently active: start_date <= today <= end_date (or end_date is null)
            // Only assignments that are actually active now get ACTIVE status
            return AssignmentStatus::ACTIVE;
        } elseif ($this->isPast()) {
            // Past: end_date < today
            // Past assignments always get COMPLETED status
            return AssignmentStatus::COMPLETED;
        } elseif ($this->isScheduled()) {
            // Scheduled: start_date > today
            // Future assignments are shown as ACTIVE (they will become active)
            return AssignmentStatus::ACTIVE;
        }

        // Fallback: default to ACTIVE
        return AssignmentStatus::ACTIVE;
    }

}
