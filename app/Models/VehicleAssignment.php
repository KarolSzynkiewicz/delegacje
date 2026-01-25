<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasDateRange;
use App\Contracts\HasEmployee;
use App\Contracts\HasDateRange as HasDateRangeContract;
use App\Models\Employee;
use App\Enums\VehiclePosition;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;

class VehicleAssignment extends Model implements HasEmployee, HasDateRangeContract
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
     * Implementation of HasEmployee::getEmployee()
     */
    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    /**
     * Implementation of HasDateRange::getStartDate()
     * 
     * Note: Trait HasDateRange already provides this method, but we override it
     * to ensure it returns Carbon (not CarbonInterface) to match the contract.
     */
    public function getStartDate(): Carbon
    {
        $date = $this->start_date;
        return $date ? Carbon::instance($date) : Carbon::now();
    }

    /**
     * Implementation of HasDateRange::getEndDate()
     * 
     * Note: Trait HasDateRange already provides this method, but we override it
     * to ensure it returns Carbon|null (not CarbonInterface|null) to match the contract.
     */
    public function getEndDate(): ?Carbon
    {
        $date = $this->end_date;
        return $date ? Carbon::instance($date) : null;
    }

    /**
     * Get the computed status based on dates.
     * 
     * Status calculation priority:
     * 1. If status is CANCELLED in database, return CANCELLED
     * 2. If assignment is currently active (start_date <= today <= end_date or end_date is null) -> ACTIVE
     * 3. If assignment is in the past (end_date < today) -> COMPLETED
     * 4. If assignment is scheduled (start_date > today) -> use status from database (if set) or ACTIVE
     * 
     * This ensures that "active" status is only shown for assignments that are actually active now,
     * not for past assignments that still have "active" status in the database.
     * 
     * @return AssignmentStatus
     */
    public function getStatusAttribute($value): AssignmentStatus
    {
        // If status is explicitly CANCELLED, respect it
        if ($value === AssignmentStatus::CANCELLED->value) {
            return AssignmentStatus::CANCELLED;
        }

        // Calculate status based on dates
        if ($this->isCurrentlyActive()) {
            // Currently active: start_date <= today <= end_date (or end_date is null)
            // Only assignments that are actually active now get ACTIVE status
            return AssignmentStatus::ACTIVE;
        } elseif ($this->isPast()) {
            // Past: end_date < today
            // Past assignments always get COMPLETED status, regardless of database value
            return AssignmentStatus::COMPLETED;
        } elseif ($this->isScheduled()) {
            // Scheduled: start_date > today
            // Future assignments use status from database (if set) or default to ACTIVE
            // This allows future assignments to have different statuses (e.g., IN_TRANSIT, AT_BASE)
            if ($value) {
                return AssignmentStatus::tryFrom($value) ?? AssignmentStatus::ACTIVE;
            }
            return AssignmentStatus::ACTIVE;
        }

        // Fallback: if dates are invalid or missing, use database value or default to ACTIVE
        if ($value) {
            return AssignmentStatus::tryFrom($value) ?? AssignmentStatus::ACTIVE;
        }

        return AssignmentStatus::ACTIVE;
    }
}
