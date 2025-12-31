<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'brand',
        'model',
        'capacity',
        'technical_condition',
        'inspection_valid_to',
        'notes',
        'image_path'
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
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where(function ($q2) use ($endDate) {
                              $q2->where('end_date', '>=', $endDate)
                                 ->orWhereNull('end_date');
                          });
                    });
            })
            ->exists();
    }
}
