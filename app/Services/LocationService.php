<?php

namespace App\Services;

use App\Models\Location;
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
            // Jeśli tworzymy nową bazę, odznacz wszystkie istniejące bazy
            if ($isBase) {
                Location::where('is_base', true)->update(['is_base' => false]);
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
            // Jeśli ustawiamy tę lokalizację jako bazę, odznacz wszystkie inne bazy
            if ($isBase) {
                Location::where('is_base', true)
                    ->where('id', '!=', $location->id)
                    ->update(['is_base' => false]);
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

