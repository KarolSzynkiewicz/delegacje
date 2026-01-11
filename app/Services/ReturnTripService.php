<?php

namespace App\Services;

use App\Domain\ReturnTripPreparation;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Contracts\AssignmentContract;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Enums\AssignmentStatus;
use App\Services\AssignmentQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for handling return trips (zjazdy) - mass operation to return employees to base.
 * 
 * This service implements the domain model where Return Trip is a superior domain event
 * that affects assignments. It uses prepare/commit pattern for atomic operations.
 */
class ReturnTripService
{
    public function __construct(
        protected AssignmentQueryService $assignmentQueryService,
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Prepare a return trip (dry-run / simulation).
     * 
     * Analyzes what would happen if the return trip is executed:
     * - Finds all assignments that would be shortened
     * - Detects conflicts with return vehicle
     * 
     * Does NOT modify the database.
     * 
     * @param array $data [
     *   'vehicle_id' => int|null,
     *   'employee_ids' => array<int>,
     *   'return_date' => string (Y-m-d),
     * ]
     * @return ReturnTripPreparation
     * @throws ValidationException
     */
    public function prepareZjazd(array $data): ReturnTripPreparation
    {
        $employeeIds = $data['employee_ids'];
        $returnDate = Carbon::parse($data['return_date']);
        $returnVehicle = isset($data['vehicle_id']) ? Vehicle::findOrFail($data['vehicle_id']) : null;

        // Validate employees exist
        foreach ($employeeIds as $employeeId) {
            $this->employeeRepository->findOrFail($employeeId);
        }

        // Get all active assignments for returning employees
        $activeAssignments = $this->assignmentQueryService->getActiveAssignmentsForEmployees($employeeIds, $returnDate);

        // Get active vehicle assignments for return vehicle (if specified)
        $returnVehicleAssignments = collect();
        if ($returnVehicle) {
            $returnVehicleAssignments = VehicleAssignment::where('vehicle_id', $returnVehicle->id)
                ->activeAtDate($returnDate)
                ->get();
        }

        // Create preparation
        $preparation = new ReturnTripPreparation($employeeIds, $returnDate, $returnVehicle);
        $preparation->prepare($activeAssignments, $returnVehicleAssignments);

        return $preparation;
    }

    /**
     * Commit the return trip (execute the changes).
     * 
     * Executes exactly the changes calculated in prepareZjazd:
     * - Shortens assignments (sets end_date = return_date)
     * - Creates or updates LogisticsEvent as domain fact
     * 
     * This is an atomic transaction.
     * 
     * @param ReturnTripPreparation $preparation
     * @param array $data Additional data (notes, etc.)
     * @param LogisticsEvent|null $existingEvent If provided, updates existing event instead of creating new one
     * @return LogisticsEvent
     * @throws ValidationException
     */
    public function commitZjazd(ReturnTripPreparation $preparation, array $data = [], ?LogisticsEvent $existingEvent = null): LogisticsEvent
    {
        // Validate preparation is valid (no blocking conflicts)
        if (!$preparation->isValid) {
            $blockingConflicts = $preparation->conflicts->where('isBlocking', true);
            $messages = $blockingConflicts->pluck('message')->toArray();
            
            throw ValidationException::withMessages([
                'return_trip' => 'Zjazd nie może zostać wykonany z powodu konfliktów: ' . implode(' ', $messages)
            ]);
        }

        $baseLocation = Location::getBase();

        return DB::transaction(function () use ($preparation, $baseLocation, $data, $existingEvent) {
            // Shorten all assignments
            foreach ($preparation->assignmentsToShorten as $assignmentToShorten) {
                $assignment = $assignmentToShorten->assignment;
                
                // Update end_date to return date
                if ($assignment instanceof ProjectAssignment) {
                    $assignment->update(['end_date' => $preparation->returnDate]);
                } elseif ($assignment instanceof AccommodationAssignment) {
                    $assignment->update(['end_date' => $preparation->returnDate]);
                } elseif ($assignment instanceof VehicleAssignment) {
                    $assignment->update(['end_date' => $preparation->returnDate]);
                }
            }

            // End old vehicle assignments for returning employees (if not already shortened)
            foreach ($preparation->employeeIds as $employeeId) {
                $oldVehicleAssignment = $this->assignmentQueryService->getActiveVehicleAssignment(
                    $employeeId,
                    $preparation->returnDate
                );

                if ($oldVehicleAssignment) {
                    // Check if this assignment is already in assignmentsToShorten
                    $alreadyShortened = $preparation->assignmentsToShorten->contains(function ($item) use ($oldVehicleAssignment) {
                        return $item->assignment->getEmployee()->id === $oldVehicleAssignment->getEmployee()->id
                            && get_class($item->assignment) === get_class($oldVehicleAssignment)
                            && $item->assignment->id === $oldVehicleAssignment->id;
                    });

                    if (!$alreadyShortened) {
                        $oldVehicleAssignment->update(['end_date' => $preparation->returnDate]);
                    }
                }
            }

            // Ensure return vehicle has no active assignments after return date for employees NOT in return trip
            if ($preparation->returnVehicle) {
                $conflictingAssignments = VehicleAssignment::where('vehicle_id', $preparation->returnVehicle->id)
                    ->whereNotIn('employee_id', $preparation->employeeIds)
                    ->where(function ($query) use ($preparation) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>', $preparation->returnDate);
                    })
                    ->where('start_date', '<=', $preparation->returnDate)
                    ->get();

                foreach ($conflictingAssignments as $conflictingAssignment) {
                    $conflictingAssignment->update(['end_date' => $preparation->returnDate]);
                }
            }

            // Create or update LogisticsEvent as domain fact
            if ($existingEvent) {
                // Update existing event
                $event = $existingEvent;
                $event->update([
                    'event_date' => $preparation->returnDate,
                    'vehicle_id' => $preparation->returnVehicle?->id,
                    'from_location_id' => $this->getCurrentLocationForEmployees($preparation->employeeIds)?->id ?? $baseLocation->id,
                    'to_location_id' => $baseLocation->id,
                    'notes' => $data['notes'] ?? null,
                ]);
                
                // Delete old participants
                $event->participants()->delete();
            } else {
                // Create new event
                $event = LogisticsEvent::create([
                    'type' => LogisticsEventType::RETURN,
                    'event_date' => $preparation->returnDate,
                    'has_transport' => false,
                    'vehicle_id' => $preparation->returnVehicle?->id,
                    'transport_id' => null,
                    'from_location_id' => $this->getCurrentLocationForEmployees($preparation->employeeIds)?->id ?? $baseLocation->id,
                    'to_location_id' => $baseLocation->id,
                    'status' => LogisticsEventStatus::PLANNED,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Create vehicle assignments for return transport (if vehicle specified)
            if ($preparation->returnVehicle) {
                foreach ($preparation->employeeIds as $employeeId) {
                    $newVehicleAssignment = VehicleAssignment::create([
                        'employee_id' => $employeeId,
                        'vehicle_id' => $preparation->returnVehicle->id,
                        'start_date' => $preparation->returnDate,
                        'end_date' => $preparation->returnDate->copy()->addDays(1),
                        'status' => AssignmentStatus::IN_TRANSIT,
                        'notes' => 'Zjazd do bazy',
                        'is_return_trip' => true,
                    ]);

                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'assignment_type' => 'vehicle_assignment',
                        'assignment_id' => $newVehicleAssignment->id,
                        'status' => 'pending',
                    ]);
                }

                // Update vehicle location to base
                $preparation->returnVehicle->update([
                    'current_location_id' => $baseLocation->id,
                ]);
            }

            return $event;
        });
    }

    /**
     * Get current location for employees (for from_location_id).
     */
    protected function getCurrentLocationForEmployees(array $employeeIds): ?Location
    {
        if (empty($employeeIds)) {
            return null;
        }

        $employee = $this->employeeRepository->find($employeeIds[0]);
        if (!$employee) {
            return null;
        }

        return app(LocationTrackingService::class)->forEmployee($employee);
    }

    /**
     * Reverse a return trip - clean up return trip assignments before editing.
     * 
     * This method removes return trip vehicle assignments (is_return_trip = true)
     * so that a new return trip can be created with updated data.
     * 
     * Note: We don't restore original end_date values because we don't store them.
     * The new prepare/commit will recalculate which assignments need to be shortened.
     * 
     * @param LogisticsEvent $returnTrip The return trip to reverse
     * @return void
     */
    public function reverseZjazd(LogisticsEvent $returnTrip): void
    {
        if ($returnTrip->type !== LogisticsEventType::RETURN) {
            throw new \InvalidArgumentException('Can only reverse return trips.');
        }

        DB::transaction(function () use ($returnTrip) {
            // Get all participants and their vehicle assignments
            $participants = $returnTrip->participants()->with('assignment')->get();
            
            // Delete return trip vehicle assignments (those with is_return_trip = true)
            foreach ($participants as $participant) {
                if ($participant->assignment_type === 'vehicle_assignment' && $participant->assignment) {
                    $vehicleAssignment = $participant->assignment;
                    if ($vehicleAssignment->is_return_trip) {
                        $vehicleAssignment->delete();
                    }
                }
            }
            
            // Delete all participants (they will be recreated with new data)
            $returnTrip->participants()->delete();
        });
    }

    /**
     * @deprecated Use prepareZjazd() and commitZjazd() instead.
     * This method is kept for backward compatibility but will be removed in future versions.
     * 
     * Create a return trip (zjazd) for multiple employees - OLD IMPLEMENTATION.
     * 
     * This method uses complete() which changes status to COMPLETED.
     * New implementation uses end_date to shorten assignments.
     */
    public function createReturn(array $data): LogisticsEvent
    {
        // For backward compatibility, delegate to new prepare/commit flow
        $preparation = $this->prepareZjazd($data);
        return $this->commitZjazd($preparation, $data);
    }
}
