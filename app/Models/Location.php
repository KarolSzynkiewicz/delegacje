<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

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
     * Get the base location (singleton pattern).
     * 
     * @return Location
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
