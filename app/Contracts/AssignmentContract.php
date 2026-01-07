<?php

namespace App\Contracts;

use App\Models\Employee;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;

/**
 * Contract for all Assignment models.
 * 
 * Provides polymorphic interface for ProjectAssignment, VehicleAssignment, 
 * and AccommodationAssignment to enable unified operations in services.
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
     * Get the current status of this assignment.
     */
    public function getStatus(): AssignmentStatus;

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
     * Updates status to COMPLETED and sets actual_end_date.
     */
    public function complete(Carbon $date): void;

    /**
     * Cancel this assignment.
     * Updates status to CANCELLED.
     */
    public function cancel(): void;
}
