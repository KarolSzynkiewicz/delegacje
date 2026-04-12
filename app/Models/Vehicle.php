<?php

namespace App\Models;

use App\Enums\VehicleType;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasComments, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'registration_number',
        'type',
        'brand',
        'model',
        'capacity',
        'technical_condition',
        'inspection_valid_to',
        'insurance_valid_to',
        'ac_wazne_do',
        'notes',
        'image_path',
        'current_location_id',
        'outside_base',
        'last_departure_id',
    ];

    /**
     * Get the image URL for the vehicle.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inspection_valid_to' => 'date',
        'insurance_valid_to' => 'date',
        'ac_wazne_do' => 'date',
        'type' => VehicleType::class,
        'outside_base' => 'boolean',
    ];

    /**
     * Get all assignments for this vehicle.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    /**
     * Get all service/repair records for this vehicle.
     */
    public function repairs(): HasMany
    {
        return $this->hasMany(\App\Models\VehicleRepair::class);
    }

    /**
     * Get the currently open (in-progress) repair for this vehicle.
     */
    public function openRepair(): ?\App\Models\VehicleRepair
    {
        return $this->repairs()->whereNull('end_date')->latest('start_date')->first();
    }

    /**
     * Get the employees assigned to this vehicle (M:N relationship).
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'vehicle_assignments')
            ->withPivot('start_date', 'end_date', 'notes')
            ->withTimestamps();
    }

    /**
     * Get current active assignment for this vehicle.
     */
    public function currentAssignment()
    {
        return $this->assignments()->active()->first();
    }

    /**
     * Check if vehicle is available in a given date range.
     */
    public function isAvailableInDateRange($startDate, $endDate): bool
    {
        return ! $this->assignments()
            ->overlappingWith($startDate, $endDate)
            ->exists();
    }

    /**
     * Get the current location of this vehicle.
     *
     * Delegates to LocationTrackingService for business logic.
     */
    public function getCurrentLocation(): ?Location
    {
        return app(\App\Services\LocationTrackingService::class)->getVehicleLocation($this);
    }

    /**
     * Get the location of this vehicle on a specific date.
     *
     * Delegates to LocationTrackingService for business logic.
     *
     * @param  \Carbon\Carbon|string  $date
     * @return \App\Models\Location|string|null Returns Location, "W PODRÓŻY" string, or null
     */
    public function getLocationOnDate($date): Location|string|null
    {
        $carbonDate = \Carbon\Carbon::parse($date);

        return app(\App\Services\LocationTrackingService::class)->getVehicleLocationOnDate($this, $carbonDate);
    }

    /**
     * Get comprehensive location status for this vehicle on a specific date.
     *
     * Delegates to LocationTrackingService for business logic.
     *
     * @param  \Carbon\Carbon|string  $date
     * @return array Returns comprehensive status with locations, occupancy, etc.
     */
    public function getLocationStatusOnDate($date): array
    {
        $carbonDate = \Carbon\Carbon::parse($date);

        return app(\App\Services\LocationTrackingService::class)->getVehicleLocationStatus($this, $carbonDate);
    }

    /**
     * Get the last departure event that set outside_base flag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastDeparture()
    {
        return $this->belongsTo(LogisticsEvent::class, 'last_departure_id');
    }

    /**
     * Get the current location relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentLocation()
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Get projects where this vehicle is currently being used.
     *
     * A vehicle is used in a project if:
     * - There's an active vehicle assignment for this vehicle
     * - The assigned employee has an active project assignment
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCurrentProjectsAttribute()
    {
        $activeAssignments = $this->assignments()->active()->with('employee')->get();

        if ($activeAssignments->isEmpty()) {
            return collect();
        }

        $employeeIds = $activeAssignments->pluck('employee_id')->unique();

        $projectAssignments = \App\Models\ProjectAssignment::whereIn('employee_id', $employeeIds)
            ->active()
            ->with('project')
            ->get();

        return $projectAssignments->pluck('project')->filter()->unique('id')->values();
    }

    /**
     * Get the number of currently assigned people to this vehicle.
     *
     * Counts unique employees, not assignments (one employee can have multiple assignments).
     */
    public function getCurrentOccupancyAttribute(): int
    {
        // If unique_employees_count is available (from addSelect), use it for better performance
        if (isset($this->attributes['unique_employees_count'])) {
            return (int) $this->attributes['unique_employees_count'];
        }

        // Otherwise, query directly - count unique employees using groupBy
        return $this->assignments()
            ->active()
            ->select('employee_id')
            ->groupBy('employee_id')
            ->get()
            ->count();
    }

    /**
     * OC / przegląd — „po terminie” wg dnia kalendarzowego (jak w widoku pojazdu).
     */
    public function hasExpiredInsurance(?\Carbon\Carbon $asOf = null): bool
    {
        $asOf ??= \Carbon\Carbon::today();

        return (bool) ($this->insurance_valid_to && $this->insurance_valid_to->lt($asOf));
    }

    public function hasExpiredInspection(?\Carbon\Carbon $asOf = null): bool
    {
        $asOf ??= \Carbon\Carbon::today();

        return (bool) ($this->inspection_valid_to && $this->inspection_valid_to->lt($asOf));
    }
}
