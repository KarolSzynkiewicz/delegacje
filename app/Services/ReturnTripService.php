<?php

namespace App\Services;

use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Employee;
use App\Contracts\AssignmentContract;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Enums\AssignmentStatus;
use App\Services\AssignmentQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Service for handling return trips (zjazdy) - mass operation to return employees to base.
 * 
 * This service:
 * - Ends active project/accommodation assignments
 * - Creates vehicle assignments for transport to base
 * - Updates vehicle location
 * - Creates LogisticsEvent for audit
 * - Uses AssignmentContract[] for polymorphic operations
 */
class ReturnTripService
{
    public function __construct(
        protected AssignmentQueryService $assignmentQueryService
    ) {}

    /**
     * Create a return trip (zjazd) for multiple employees.
     * 
     * This is a transactional operation that:
     * 1. Validates all employees have active assignments
     * 2. Ends active ProjectAssignment and AccommodationAssignment
     * 3. Creates VehicleAssignment for transport
     * 4. Updates Vehicle location to base
     * 5. Creates LogisticsEvent and participants
     * 
     * @param array $data [
     *   'vehicle_id' => int,
     *   'employee_ids' => array<int>,
     *   'return_date' => string (Y-m-d),
     *   'notes' => string|null
     * ]
     * @return LogisticsEvent
     * @throws ValidationException
     */
    public function createReturn(array $data): LogisticsEvent
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $employeeIds = $data['employee_ids'];
        $returnDate = Carbon::parse($data['return_date']);
        $baseLocation = Location::getBase();

        // Validate vehicle availability
        $this->validateVehicleAvailability($vehicle, $returnDate);

        // Validate employees have active assignments
        $this->validateEmployeesHaveActiveAssignments($employeeIds, $returnDate);

        return DB::transaction(function () use ($vehicle, $employeeIds, $returnDate, $baseLocation, $data) {
            // Get all active assignments for employees
            $assignments = $this->assignmentQueryService->getActiveAssignmentsForEmployees($employeeIds, $returnDate);

            // Create LogisticsEvent
            $event = LogisticsEvent::create([
                'type' => LogisticsEventType::RETURN,
                'event_date' => $returnDate,
                'has_transport' => false, // Using company vehicle
                'vehicle_id' => $vehicle->id,
                'transport_id' => null,
                'from_location_id' => $this->getCurrentLocationForEmployees($employeeIds)->id ?? $baseLocation->id,
                'to_location_id' => $baseLocation->id,
                'status' => LogisticsEventStatus::PLANNED,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id() ?? 1, // Fallback for tests
            ]);

            // Process each employee's assignments
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::findOrFail($employeeId);
                $employeeAssignments = $assignments->filter(function ($assignment) use ($employeeId) {
                    return $assignment->getEmployee()->id === $employeeId;
                });

                // End active assignments
                foreach ($employeeAssignments as $assignment) {
                    if ($assignment instanceof \App\Models\ProjectAssignment || 
                        $assignment instanceof \App\Models\AccommodationAssignment) {
                        $assignment->complete($returnDate);
                    }
                }

                // End old vehicle assignment if exists
                $oldVehicleAssignment = $this->assignmentQueryService->getActiveVehicleAssignment($employeeId, $returnDate);

                if ($oldVehicleAssignment) {
                    $oldVehicleAssignment->complete($returnDate);
                }

                // Create new vehicle assignment for transport to base
                $newVehicleAssignment = \App\Models\VehicleAssignment::create([
                    'employee_id' => $employeeId,
                    'vehicle_id' => $vehicle->id,
                    'start_date' => $returnDate,
                    'end_date' => $returnDate->copy()->addDays(1), // Assume 1 day for return trip
                    'status' => AssignmentStatus::IN_TRANSIT,
                    'notes' => 'Zjazd do bazy',
                ]);

                // Create participant
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'assignment_type' => 'vehicle_assignment',
                    'assignment_id' => $newVehicleAssignment->id,
                    'status' => 'pending',
                ]);
            }

            // Update vehicle location to base
            $vehicle->update([
                'current_location_id' => $baseLocation->id,
            ]);

            return $event;
        });
    }


    /**
     * Validate vehicle availability for return date.
     * 
     * @throws ValidationException
     */
    protected function validateVehicleAvailability(Vehicle $vehicle, Carbon $date): void
    {
        // Check if vehicle is available (not assigned to another employee on this date)
        $hasConflict = $vehicle->assignments()
            ->activeAtDate($date)
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Pojazd jest już przypisany w dniu zjazdu.'
            ]);
        }
    }

    /**
     * Validate employees have active assignments.
     * 
     * @throws ValidationException
     */
    protected function validateEmployeesHaveActiveAssignments(array $employeeIds, Carbon $date): void
    {
        $employeesWithoutAssignments = [];

        foreach ($employeeIds as $employeeId) {
            $employee = Employee::findOrFail($employeeId);
            
            if (!$this->assignmentQueryService->hasActiveAssignment($employeeId, $date)) {
                $employeesWithoutAssignments[] = $employee->full_name;
            }
        }

        if (!empty($employeesWithoutAssignments)) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Następujący pracownicy nie mają aktywnych przypisań: ' . implode(', ', $employeesWithoutAssignments)
            ]);
        }
    }

    /**
     * Get current location for employees (for from_location_id).
     * Uses LocationTrackingService.
     */
    protected function getCurrentLocationForEmployees(array $employeeIds): ?Location
    {
        // For simplicity, use first employee's location
        // In real scenario, might need to handle multiple locations
        if (empty($employeeIds)) {
            return null;
        }

        $employee = Employee::find($employeeIds[0]);
        if (!$employee) {
            return null;
        }

        return app(LocationTrackingService::class)->forEmployee($employee);
    }
}
