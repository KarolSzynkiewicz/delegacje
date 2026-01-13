<?php

namespace App\Contracts;

use App\Models\Employee;
use Carbon\Carbon;

/**
 * Contract for all Assignment models.
 * 
 * Provides polymorphic interface for ProjectAssignment, VehicleAssignment, 
 * and AccommodationAssignment to enable unified operations in services.
 * 
 * Status is calculated from dates - no status field needed.
 * If assignment is not needed, simply delete it from database.
 * 
 * @see app/Models/ProjectAssignment
 * @see app/Models/VehicleAssignment
 * @see app/Models/AccommodationAssignment
 */
interface AssignmentContract
{
    /**
     * Get the employee assigned to this assignment.
     * 
     * NOTE: Currently returns single Employee (singular).
     * In the future, if brigades, pairs, or subcontractors are needed,
     * consider changing to getAssignees(): Collection or AssignmentSubject pattern.
     */
    public function getEmployee(): Employee;

    /**
     * Get the start date of this assignment.
     */
    public function getStartDate(): Carbon;

    /**
     * Get the end date of this assignment (nullable).
     */
    public function getEndDate(): ?Carbon;

    /**
     * Complete this assignment on the given date.
     * Sets actual_end_date if the column exists.
     */
    public function complete(Carbon $date): void;
}
