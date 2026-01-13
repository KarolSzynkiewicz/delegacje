<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\VehicleType;

class Vehicle extends Model
{
    use HasFactory;

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
        'notes',
        'image_path',
        'current_location_id',
    ];

    /**
     * Get the image URL for the vehicle.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return asset('storage/' . $this->image_path);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inspection_valid_to' => 'date',
        'type' => VehicleType::class,
    ];

    /**
     * Get all assignments for this vehicle.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
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
        return !$this->assignments()
            ->overlappingWith($startDate, $endDate)
            ->exists();
    }

    /**
     * Get the current location of this vehicle.
     * 
     * Delegates to LocationTrackingService for business logic.
     * 
     * @return \App\Models\Location|null
     */
    public function getCurrentLocation(): ?Location
    {
        return app(\App\Services\LocationTrackingService::class)->forVehicle($this);
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
}
