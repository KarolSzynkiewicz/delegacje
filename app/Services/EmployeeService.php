<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService
{
    /**
     * Get all employees with role eager loaded.
     */
    public function getAllWithRole(): Collection
    {
        return Employee::with('role')->get();
    }

    /**
     * Get paginated employees with role.
     */
    public function getPaginatedWithRole(int $perPage = 10): LengthAwarePaginator
    {
        return Employee::with('role')->paginate($perPage);
    }

    /**
     * Create a new employee.
     */
    public function createEmployee(array $data): Employee
    {
        return Employee::create($data);
    }

    /**
     * Update an employee.
     */
    public function updateEmployee(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    /**
     * Delete an employee.
     */
    public function deleteEmployee(Employee $employee): bool
    {
        return $employee->delete();
    }

    /**
     * Check if employee is available in date range.
     */
    public function isAvailableInDateRange(Employee $employee, string $startDate, string $endDate): bool
    {
        return $employee->isAvailableInDateRange($startDate, $endDate);
    }

    /**
     * Get employees by role.
     */
    public function getEmployeesByRole(Role $role): Collection
    {
        return $role->employees()->with('role')->get();
    }
}

