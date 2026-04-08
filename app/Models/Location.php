<?php

namespace App\Models;

use App\Enums\LocationPurposeType;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Location extends Model
{
    use HasComments, HasFactory;

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
        'contact_person',
        'phone',
        'email',
        'description',
        'is_base',
        'latitude',
        'longitude',
    ];

    protected $with = ['purposes'];

    protected $casts = [
        'is_base' => 'boolean',
        'country' => \App\Enums\EuropeanCountry::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the projects for the location.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the purpose records for this location.
     */
    public function purposes(): HasMany
    {
        return $this->hasMany(LocationPurpose::class);
    }

    /**
     * Get accommodations (lease records) linked to this location.
     */
    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class);
    }

    /**
     * Get vehicle repair records linked to this location.
     */
    public function vehicleRepairs(): HasMany
    {
        return $this->hasMany(VehicleRepair::class);
    }

    /**
     * Add purposes without removing existing ones (idempotent).
     *
     * @param  string[]|LocationPurposeType[]  $purposes
     */
    public function addPurposes(array $purposes): void
    {
        foreach ($purposes as $purpose) {
            $value = $purpose instanceof LocationPurposeType ? $purpose->value : $purpose;
            $this->purposes()->firstOrCreate(['purpose' => $value]);
        }
    }

    /**
     * Replace all purposes with the given set.
     *
     * @param  string[]|LocationPurposeType[]  $purposes
     */
    public function syncPurposes(array $purposes): void
    {
        $values = array_map(
            fn ($p) => $p instanceof LocationPurposeType ? $p->value : $p,
            $purposes
        );

        $this->purposes()->whereNotIn('purpose', $values)->delete();

        foreach ($values as $value) {
            $this->purposes()->firstOrCreate(['purpose' => $value]);
        }
    }

    /**
     * Check if this location has a given purpose.
     */
    public function hasPurpose(LocationPurposeType|string $purpose): bool
    {
        $value = $purpose instanceof LocationPurposeType ? $purpose->value : $purpose;

        return $this->purposes()->where('purpose', $value)->exists();
    }

    /**
     * Get enum instances for all purposes of this location.
     *
     * @return Collection<int, LocationPurposeType>
     */
    public function getPurposeEnumsAttribute(): Collection
    {
        return $this->purposes->map(fn ($p) => $p->purpose)->filter()->values();
    }

    /**
     * Get the base location (singleton pattern).
     */
    public static function getBase(): Location
    {
        return static::base()->first() ?? static::create([
            'name' => 'Baza',
            'address' => 'Siedziba główna',
            'city' => 'Warszawa',
            'is_base' => true,
        ]);
    }

    /**
     * Scope a query to only include base locations.
     */
    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }

    /**
     * Check if location has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return ! is_null($this->latitude) && ! is_null($this->longitude);
    }

    /**
     * Get coordinates as array [lat, lng].
     */
    public function getCoordinates(): ?array
    {
        if (! $this->hasCoordinates()) {
            return null;
        }

        return [
            (float) $this->latitude,
            (float) $this->longitude,
        ];
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
}
