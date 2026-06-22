<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyAssignment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CompanyAssignmentService
{
    /**
     * @throws ValidationException
     */
    public function createAssignment(
        Employee $employee,
        Company $company,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null
    ): CompanyAssignment {
        $this->validateNoOverlappingAssignment($employee, $startDate, $endDate);

        return CompanyAssignment::create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function updateAssignment(
        CompanyAssignment $assignment,
        Employee $employee,
        Company $company,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null
    ): CompanyAssignment {
        $this->validateNoOverlappingAssignment($employee, $startDate, $endDate, $assignment->id);

        $assignment->update([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
        ]);

        return $assignment;
    }

    /**
     * @throws ValidationException
     */
    protected function validateNoOverlappingAssignment(
        Employee $employee,
        Carbon $startDate,
        ?Carbon $endDate,
        ?int $excludeAssignmentId = null
    ): void {
        DateRangeService::validateNoOverlappingAssignments(
            $employee->companyAssignments(),
            $startDate,
            $endDate ?? DateRangeService::getDefaultEndDate(),
            $excludeAssignmentId,
            'employee_id',
            'Pracownik jest już przypisany do spółki w tym okresie.'
        );
    }
}
