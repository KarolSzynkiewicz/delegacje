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
    public function createLocation(array $data): Location
    {
        return Location::create($data);
    }

    /**
     * Update a location.
     */
    public function updateLocation(Location $location, array $data): bool
    {
        return $location->update($data);
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

