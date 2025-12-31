<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    /**
     * Get all projects with location eager loaded.
     */
    public function getAllWithLocation(): Collection
    {
        return Project::with('location')->get();
    }

    /**
     * Get paginated projects with location.
     */
    public function getPaginatedWithLocation(int $perPage = 15): LengthAwarePaginator
    {
        return Project::with('location')->paginate($perPage);
    }

    /**
     * Get active projects.
     */
    public function getActiveProjects(): Collection
    {
        return Project::active()->with('location')->get();
    }

    /**
     * Create a new project.
     */
    public function createProject(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * Update a project.
     */
    public function updateProject(Project $project, array $data): bool
    {
        return $project->update($data);
    }

    /**
     * Delete a project.
     */
    public function deleteProject(Project $project): bool
    {
        return $project->delete();
    }

    /**
     * Get projects by location.
     */
    public function getProjectsByLocation(Location $location): Collection
    {
        return $location->projects()->with('location')->get();
    }
}

