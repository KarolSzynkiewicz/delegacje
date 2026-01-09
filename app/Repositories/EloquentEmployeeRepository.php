<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * Find employee by ID.
     */
    public function find(int $id): ?Employee
    {
        return Employee::find($id);
    }

    /**
     * Find employee by ID or throw exception.
     */
    public function findOrFail(int $id): Employee
    {
        return Employee::findOrFail($id);
    }

    /**
     * Get all employees with optional relations.
     */
    public function all(array $with = []): Collection
    {
        $query = Employee::query();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->get();
    }

    /**
     * Get employees with roles and documents eager loaded.
     */
    public function withRolesAndDocuments(): Collection
    {
        return Employee::with(['roles', 'employeeDocuments.document'])
            ->get();
    }

    /**
     * Get employees ordered by last name.
     */
    public function orderedByLastName(array $with = []): Collection
    {
        $query = Employee::query();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->orderBy('last_name')->get();
    }

    /**
     * Get employees that have active assignments at a specific date.
     */
    public function withActiveAssignmentsAt(Carbon $date): Collection
    {
        return Employee::whereHas('assignments', function ($query) use ($date) {
            $query->where('status', 'active')
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
                });
        })->get();
    }

    /**
     * Get employees with active project or accommodation assignments at a specific date.
     * Includes eager loaded assignments.
     */
    public function withActiveProjectOrAccommodationAssignmentsAt(Carbon $date): Collection
    {
        return Employee::whereHas('assignments', function ($query) use ($date) {
                $query->activeAtDate($date);
            })
            ->orWhereHas('accommodationAssignments', function ($query) use ($date) {
                $query->activeAtDate($date);
            })
            ->with(['assignments' => function ($query) use ($date) {
                $query->activeAtDate($date);
            }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
