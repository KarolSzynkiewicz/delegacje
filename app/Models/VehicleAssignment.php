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
}
