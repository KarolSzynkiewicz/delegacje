<?php

namespace App\Contracts;

use App\Models\Employee;

interface HasEmployee
{
    /**
     * Get the employee assigned to this assignment.
     * 
     * NOTE: Currently returns single Employee (singular).
     * In the future, if brigades, pairs, or subcontractors are needed,
     * consider changing to getAssignees(): Collection or AssignmentSubject pattern.
     */
    public function getEmployee(): Employee;
}
