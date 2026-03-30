<?php

namespace App\Services;

use App\Enums\LocationPurposeType;
use App\Models\Location;
use App\Models\LocationPurpose;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LocationService
{
    /**
     * Get all locations.
     */
    public function getAll(): Collection
    {
        return Location::all();
    }

    /**
     * Create a new location.
     */
    public function createLocation(
        string $name,
        string $address,
        ?string $city = null,
        ?string $postalCode = null,
        ?string $contactPerson = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $description = null,
        bool $isBase = false,
        ?float $latitude = null,
        ?float $longitude = null
    ): Location {
        return DB::transaction(function () use (
            $name, $address, $city, $postalCode, $contactPerson, $phone, $email, 
            $description, $isBase, $latitude, $longitude
        ) {
            if ($isBase) {
                $this->demoteOtherMainOffices(null);
            }
            
            return Location::create([
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postalCode,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'email' => $email,
                'description' => $description,
                'is_base' => $isBase,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        });
    }

    /**
     * Update a location.
     */
    public function updateLocation(
        Location $location,
        string $name,
        string $address,
        ?string $city = null,
        ?string $postalCode = null,
        ?string $contactPerson = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $description = null,
        bool $isBase = false,
        ?float $latitude = null,
        ?float $longitude = null
    ): bool {
        return DB::transaction(function () use (
            $location, $name, $address, $city, $postalCode, $contactPerson, $phone, $email,
            $description, $isBase, $latitude, $longitude
        ) {
            if ($isBase) {
                $this->demoteOtherMainOffices($location->id);
            }
            
            return $location->update([
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postalCode,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'email' => $email,
                'description' => $description,
                'is_base' => $isBase,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        });
    }

    /**
     * Odznacza flagę is_base i usuwa cel „base” z pivotu u innych lokalizacji (tylko jedna siedziba główna).
     *
     * @param  int|null  $exceptLocationId  null = democja wszystkich (np. przed utworzeniem nowej)
     */
    private function demoteOtherMainOffices(?int $exceptLocationId): void
    {
        $query = Location::query()->where('is_base', true);
        if ($exceptLocationId !== null) {
            $query->where('id', '!=', $exceptLocationId);
        }
        $ids = $query->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        Location::whereIn('id', $ids)->update(['is_base' => false]);
        LocationPurpose::query()
            ->whereIn('location_id', $ids)
            ->where('purpose', LocationPurposeType::BASE->value)
            ->delete();
    }

    /**
     * Delete a location.
     */
    public function deleteLocation(Location $location): bool
    {
        return $location->delete();
    }

    /**
     * Get location with projects.
     */
    public function getLocationWithProjects(Location $location): Location
    {
        return $location->load('projects');
    }
}

