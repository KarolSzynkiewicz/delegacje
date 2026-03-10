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
        'logistics_event_id',
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

    /**
     * Get the logistics event that created this assignment.
     */
    public function logisticsEvent(): BelongsTo
    {
        return $this->belongsTo(LogisticsEvent::class);
    }

    public function getStatusAttribute($value): AssignmentStatus
    {
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

    /**
     * Check if the assignment is scheduled (starts in the future).
     * 
     * @return bool True if assignment starts in the future
     */
    public function isScheduled(): bool
    {
        // Użyj metody z HasDateRange trait bezpośrednio
        $start = $this->getStartDate();
        if ($start === null) {
            return false;
        }
        
        return $start->gt(Carbon::today());
    }

    /**
     * Check if the assignment is active (currently running, not scheduled).
     * 
     * @return bool True if assignment is currently active, false if scheduled or completed
     */
    public function isActive(): bool
    {
        // Jeśli start_date jest w przyszłości, to nie jest aktywne (tylko zaplanowane)
        if ($this->isScheduled()) {
            return false;
        }

        if ($this->end_date === null) {
            return true; // Open-ended assignments are always active (if not scheduled)
        }

        $today = Carbon::today();
        $endDate = \App\Services\DateRangeService::normalizeDate($this->end_date);
        
        return $endDate->gte($today);
    }

    /**
     * Check if the assignment is completed (ended).
     * 
     * @return bool True if assignment is completed, false if active or scheduled
     */
    public function isCompleted(): bool
    {
        if ($this->isScheduled()) {
            return false; // Scheduled is not completed
        }

        return !$this->isActive();
    }
}
