<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasComments;

class Accommodation extends Model
{
    use HasFactory, HasComments;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'postal_code',
        'country',
        'capacity',
        'description',
        'image_path',
        'type',
        'lease_start_date',
        'lease_end_date',
        'latitude',
        'longitude',
    ];

    /**
     * Get the image URL for the accommodation.
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
        'lease_start_date' => 'date',
        'lease_end_date' => 'date',
        'country' => \App\Enums\EuropeanCountry::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];


    /**
     * Get all assignments for this accommodation.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AccommodationAssignment::class);
    }

    /**
     * Get the employees assigned to this accommodation (M:N relationship).
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'accommodation_assignments')
            ->withPivot('start_date', 'end_date', 'notes')
            ->withTimestamps();
    }

    /**
     * Get current active assignments for this accommodation.
     */
    public function currentAssignments()
    {
        return $this->assignments()->active()->get();
    }

    /**
     * Get available capacity at a given date range.
     */
    public function getAvailableCapacity($startDate, $endDate, ?int $excludeAssignmentId = null): int
    {
        $query = $this->assignments()
            ->inDateRange($startDate, $endDate);
        
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }
        
        $occupiedCount = $query->count();

        return max(0, $this->capacity - $occupiedCount);
    }

    /**
     * Check if accommodation has available space in a given date range.
     */
    public function hasAvailableSpace($startDate, $endDate, ?int $excludeAssignmentId = null): bool
    {
        return $this->getAvailableCapacity($startDate, $endDate, $excludeAssignmentId) > 0;
    }

    /**
     * Get full address string for geocoding.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->postal_code,
            $this->country?->value ?? null,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if accommodation has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get coordinates as array [lat, lng].
     */
    public function getCoordinates(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            (float) $this->latitude,
            (float) $this->longitude,
        ];
    }
}
