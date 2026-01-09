<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    /**
     * Find employee by ID.
     */
    public function find(int $id): ?Employee;

    /**
     * Find employee by ID or throw exception.
     */
    public function findOrFail(int $id): Employee;

    /**
     * Get all employees with optional relations.
     */
    public function all(array $with = []): Collection;

    /**
     * Get employees with roles and documents eager loaded.
     */
    public function withRolesAndDocuments(): Collection;

    /**
     * Get employees ordered by last name.
     */
    public function orderedByLastName(array $with = []): Collection;

    /**
     * Get employees that have active assignments at a specific date.
     */
    public function withActiveAssignmentsAt(\Carbon\Carbon $date): Collection;

    /**
     * Get employees with active project or accommodation assignments at a specific date.
     * Includes eager loaded assignments.
     */
    public function withActiveProjectOrAccommodationAssignmentsAt(\Carbon\Carbon $date): Collection;
}
