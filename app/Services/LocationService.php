<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;

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
        bool $isBase = false
    ): Location {
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
        ]);
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
        bool $isBase = false
    ): bool {
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
        ]);
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

