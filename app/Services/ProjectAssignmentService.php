<?php

namespace App\Services;

use App\Models\ProjectAssignment;
use App\Models\Project;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ProjectAssignmentService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}
    /**
     * Create a new project assignment with business logic validation.
     */
    public function createAssignment(Project $project, array $data): ProjectAssignment
    {
        $employee = $this->employeeRepository->findOrFail($data['employee_id']);
        $endDate = $data['end_date'] ?? now()->addYears(10)->format('Y-m-d');

        // Validate employee has the required role
        $this->validateEmployeeHasRole($employee, $data['role_id']);

        // Validate employee has all documents
        $this->validateEmployeeDocuments($employee, $data['start_date'], $endDate);

        // Validate employee availability
        $this->validateEmployeeAvailability($employee, $data['start_date'], $endDate);

        // Validate project demand
        $this->validateProjectDemand($project, $data['role_id'], $data['start_date'], $endDate);

        return $project->assignments()->create($data);
    }

    /**
     * Update an existing project assignment with business logic validation.
     */
    public function updateAssignment(ProjectAssignment $assignment, array $data): bool
    {
        $employee = $this->employeeRepository->findOrFail($data['employee_id']);
        $endDate = $data['end_date'] ?? now()->addYears(10)->format('Y-m-d');

        // Validate employee has the required role
        $this->validateEmployeeHasRole($employee, $data['role_id']);

        // Validate employee has all documents
        $this->validateEmployeeDocuments($employee, $data['start_date'], $endDate);

        // Validate employee availability (excluding current assignment)
        $this->validateEmployeeAvailability($employee, $data['start_date'], $endDate, $assignment->id);

        // Validate project demand
        $project = Project::findOrFail($data['project_id']);
        $this->validateProjectDemand($project, $data['role_id'], $data['start_date'], $endDate);

        return $assignment->update($data);
    }

    /**
     * Validate that employee has the required role.
     *
     * @throws ValidationException
     */
    protected function validateEmployeeHasRole(Employee $employee, int $roleId): void
    {
        if (!$employee->hasRole($roleId)) {
            $role = \App\Models\Role::find($roleId);
            $roleName = $role ? $role->name : 'nieznana';
            throw ValidationException::withMessages([
                'role_id' => "Pracownik {$employee->full_name} nie posiada roli: {$roleName}. Nie można przypisać go do projektu z tą rolą."
            ]);
        }
    }

    /**
     * Validate that employee has all required documents active in date range.
     *
     * @throws ValidationException
     */
    protected function validateEmployeeDocuments(Employee $employee, string $startDate, string $endDate): void
    {
        // Sprawdź czy kolumna is_required istnieje
        $hasIsRequiredColumn = \Illuminate\Support\Facades\Schema::hasColumn('documents', 'is_required');
        
        // Jeśli kolumna nie istnieje, nie ma wymaganych dokumentów - nie blokuj
        if (!$hasIsRequiredColumn) {
            return;
        }
        
        // Sprawdź czy są wymagane dokumenty
        $requiredDocuments = \App\Models\Document::where('is_required', true)->get();
        
        // Jeśli nie ma żadnych wymaganych dokumentów, nie blokuj przypisania
        if ($requiredDocuments->isEmpty()) {
            return; // Nie ma wymaganych dokumentów - wszystko OK
        }
        
        // Sprawdź czy pracownik ma wszystkie wymagane dokumenty
        if (!$employee->hasAllDocumentsActiveInDateRange($startDate, $endDate)) {
            // Znajdź brakujące wymagane dokumenty
            $missingDocuments = [];
            
            foreach ($requiredDocuments as $document) {
                $hasActiveDocument = $employee->employeeDocuments()
                    ->where('document_id', $document->id)
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->where(function ($q2) use ($startDate, $endDate) {
                            $q2->where('kind', 'bezokresowy')
                               ->where('valid_from', '<=', $endDate);
                        })->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('kind', 'okresowy')
                               ->where('valid_from', '<=', $startDate)
                               ->where(function ($q3) use ($endDate) {
                                   $q3->whereNull('valid_to')
                                      ->orWhere('valid_to', '>=', $endDate);
                               });
                        });
                    })
                    ->exists();
                
                if (!$hasActiveDocument) {
                    $missingDocuments[] = $document->name;
                }
            }
            
            // Jeśli nie ma brakujących wymaganych dokumentów, nie blokuj
            if (empty($missingDocuments)) {
                return;
            }
            
            $missingList = implode(', ', $missingDocuments);
            $message = "Pracownik {$employee->full_name} nie ma wszystkich wymaganych dokumentów aktywnych w okresie od {$startDate} do {$endDate}. Brakuje wymaganych dokumentów: {$missingList}.";
            
            throw ValidationException::withMessages([
                'employee_id' => $message
            ]);
        }
    }

    /**
     * Validate employee availability for assignment.
     *
     * @throws ValidationException
     */
    protected function validateEmployeeAvailability(
        Employee $employee,
        string $startDate,
        string $endDate,
        ?int $excludeAssignmentId = null
    ): void {
        // Check if employee has active rotation covering the entire period
        if (!$employee->hasActiveRotationInDateRange($startDate, $endDate)) {
            throw ValidationException::withMessages([
                'employee_id' => "Pracownik nie ma aktywnej rotacji pokrywającej cały okres przypisania (od {$startDate} do {$endDate}). Rotacja musi pokrywać cały okres przypisania."
            ]);
        }

        // Check if employee is available (no conflicting assignments)
        if ($excludeAssignmentId) {
            // For updates, check conflicts excluding current assignment
            $hasConflictingAssignments = $employee->assignments()
                ->active()
                ->where('id', '!=', $excludeAssignmentId)
                ->overlappingWith($startDate, $endDate)
                ->exists();

            if ($hasConflictingAssignments) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Pracownik jest już przypisany do innego projektu w tym okresie.'
                ]);
            }
        } else {
            // For creates, use the model method
            if (!$employee->isAvailableInDateRange($startDate, $endDate)) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Pracownik jest już przypisany do innego projektu w tym okresie.'
                ]);
            }
        }
    }

    /**
     * Validate that project has demand for the role in the date range.
     *
     * @throws ValidationException
     */
    protected function validateProjectDemand(
        Project $project,
        int $roleId,
        string $startDate,
        string $endDate
    ): void {
        if (!$project->hasDemandForRoleInDateRange($roleId, $startDate, $endDate)) {
            throw ValidationException::withMessages([
                'role_id' => "Brak zapotrzebowania dla roli w tym projekcie w okresie od {$startDate} do {$endDate}."
            ]);
        }
    }

    /**
     * Get employees with availability status for assignment creation.
     */
    public function getEmployeesWithAvailabilityStatus(?string $startDate = null, ?string $endDate = null, ?int $excludeAssignmentId = null): \Illuminate\Support\Collection
    {
        $employees = $this->employeeRepository->withRolesAndDocuments()
            ->sortBy('last_name');

        if ($startDate && $endDate) {
            return $employees->map(function ($employee) use ($startDate, $endDate, $excludeAssignmentId) {
                $status = $employee->getAvailabilityStatus($startDate, $endDate, $excludeAssignmentId);
                // Upewnij się, że missing_documents jest zawsze tablicą
                if (!isset($status['missing_documents'])) {
                    $status['missing_documents'] = [];
                }
                $employee->availability_status = $status;
                return $employee;
            });
        }

        // If no dates, all employees are available
        return $employees->map(function ($employee) {
            $employee->availability_status = [
                'available' => true, 
                'reasons' => [],
                'missing_documents' => []
            ];
            return $employee;
        });
    }
}
