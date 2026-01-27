<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasDateRange;
use App\Models\Employee;
use App\Enums\VehiclePosition;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;

class VehicleAssignment extends Model
{
    use HasFactory, HasDateRange;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'vehicle_id',
        'position',
        'start_date',
        'end_date',
        'notes',
        'is_return_trip',
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
        'position' => VehiclePosition::class,
        'is_return_trip' => 'boolean',
        'is_cancelled' => 'boolean',
    ];

    /**
     * Get the employee for this vehicle assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the vehicle for this assignment.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getStatusAttribute($value): AssignmentStatus
    {
        // If assignment is explicitly cancelled, return CANCELLED
        if ($this->is_cancelled) {
            return AssignmentStatus::CANCELLED;
        }

        // Calculate status based on dates
        if ($this->isCurrentlyActive()) {
            return AssignmentStatus::ACTIVE;
        } elseif ($this->isPast()) {
            return AssignmentStatus::COMPLETED;
        } elseif ($this->isScheduled()) {
            return AssignmentStatus::ACTIVE;
        }

        // Fallback: default to ACTIVE
        return AssignmentStatus::ACTIVE;
    }
}
