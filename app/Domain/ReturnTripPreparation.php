<?php

namespace App\Domain;

use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use App\Models\Vehicle;
use App\Contracts\HasEmployee;
use App\Contracts\HasDateRange;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Domain model for Return Trip (Zjazd) preparation and execution.
 * 
 * This class represents the domain logic for return trips as a superior domain event
 * that affects assignments. It follows the prepare/commit pattern for atomic operations.
 */
class ReturnTripPreparation
{
    /**
     * List of employees returning.
     * 
     * @var array<int>
     */
    public array $employeeIds;

    /**
     * Return date.
     */
    public Carbon $returnDate;

    /**
     * Return vehicle.
     */
    public ?Vehicle $returnVehicle;

    /**
     * Assignments that will be shortened (end_date will be set to return_date).
     * 
     * @var Collection<AssignmentToShorten>
     */
    public Collection $assignmentsToShorten;

    /**
     * Conflicts that prevent the return trip from being executed.
     * 
     * @var Collection<ReturnTripConflict>
     */
    public Collection $conflicts;

    /**
     * Whether the preparation is valid (no blocking conflicts).
     */
    public bool $isValid;

    /**
     * Create a new return trip preparation.
     */
    public function __construct(
        array $employeeIds,
        Carbon $returnDate,
        ?Vehicle $returnVehicle = null
    ) {
        $this->employeeIds = $employeeIds;
        $this->returnDate = $returnDate;
        $this->returnVehicle = $returnVehicle;
        $this->assignmentsToShorten = collect();
        $this->conflicts = collect();
        $this->isValid = false;
    }

    /**
     * Prepare the return trip (dry-run).
     * 
     * Analyzes what would happen if the return trip is executed:
     * - Finds all assignments that would be shortened
     * - Detects conflicts with return vehicle
     * 
     * Does NOT modify the database.
     * 
     * @param Collection<HasEmployee&HasDateRange> $activeAssignments All active assignments for employees
     * @param Collection<VehicleAssignment> $returnVehicleAssignments Assignments for return vehicle
     * @return self
     */
    public function prepare(
        Collection $activeAssignments,
        Collection $returnVehicleAssignments
    ): self {
        $this->assignmentsToShorten = collect();
        $this->conflicts = collect();

        // Find assignments to shorten for returning employees
        // This includes: ProjectAssignment, AccommodationAssignment, VehicleAssignment
        foreach ($activeAssignments as $assignment) {
            $employeeId = $assignment->getEmployee()->id;
            
            // Only process assignments for employees in the return trip
            if (!in_array($employeeId, $this->employeeIds)) {
                continue;
            }

            // Check if assignment extends beyond return date
            $endDate = $assignment->getEndDate();
            if ($endDate === null || $endDate->gt($this->returnDate)) {
                $this->assignmentsToShorten->push(new AssignmentToShorten(
                    $assignment,
                    $endDate,
                    $this->returnDate
                ));
            }
        }

        // Check conflicts with return vehicle
        // Auto powrotne nie może mieć aktywnych przypisań po dacie zjazdu dla osób NIE objętych zjazdem
        if ($this->returnVehicle) {
            $this->detectReturnVehicleConflicts($returnVehicleAssignments);
        }

        // Preparation is valid if there are no blocking conflicts
        $this->isValid = $this->conflicts->where('isBlocking', true)->isEmpty();

        return $this;
    }

    /**
     * Detect conflicts with return vehicle.
     * 
     * A conflict occurs when:
     * - Someone is assigned to return vehicle
     * - They are NOT in the return trip
     * - Their assignment extends beyond return date
     * 
     * These conflicts are NOT blocking - the assignment will be automatically cancelled
     * and the person will be left without a vehicle.
     */
    protected function detectReturnVehicleConflicts(Collection $returnVehicleAssignments): void
    {
        foreach ($returnVehicleAssignments as $assignment) {
            $employeeId = $assignment->getEmployee()->id;
            
            // Skip if employee is part of the return trip
            if (in_array($employeeId, $this->employeeIds)) {
                continue;
            }

            // Check if assignment extends beyond return date
            $endDate = $assignment->getEndDate();
            if ($endDate === null || $endDate->gt($this->returnDate)) {
                $this->conflicts->push(new ReturnTripConflict(
                    $assignment,
                    $this->returnVehicle,
                    "Osoba {$assignment->getEmployee()->full_name} jest przypisana do auta powrotnego po dacie zjazdu. Przypisanie zostanie anulowane i osoba zostanie bez auta.",
                    false // Not blocking - can be accepted
                ));
            }
        }
    }

    /**
     * Get summary of what will happen.
     */
    public function getSummary(): array
    {
        return [
            'assignments_to_shorten' => $this->assignmentsToShorten->count(),
            'conflicts' => $this->conflicts->count(),
            'blocking_conflicts' => $this->conflicts->where('isBlocking', true)->count(),
            'is_valid' => $this->isValid,
        ];
    }
}

/**
 * Value object representing an assignment that will be shortened.
 */
class AssignmentToShorten
{
    public function __construct(
        public HasEmployee&HasDateRange $assignment,
        public ?Carbon $currentEndDate,
        public Carbon $newEndDate
    ) {}

    public function getEmployeeId(): int
    {
        return $this->assignment->getEmployee()->id;
    }

    public function getAssignmentType(): string
    {
        return get_class($this->assignment);
    }
}

/**
 * Value object representing a conflict that prevents return trip execution.
 */
class ReturnTripConflict
{
    public function __construct(
        public HasEmployee&HasDateRange $assignment,
        public ?Vehicle $vehicle,
        public string $message,
        public bool $isBlocking = true
    ) {}

    public function getEmployeeId(): int
    {
        return $this->assignment->getEmployee()->id;
    }
}
