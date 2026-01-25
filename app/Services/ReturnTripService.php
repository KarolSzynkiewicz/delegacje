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
use App\Models\Employee;
use App\Contracts\HasEmployee;
use App\Contracts\HasDateRange;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
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
        protected AssignmentQueryService $assignmentQueryService
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
     * @param array $employeeIds
     * @param Carbon $returnDate
     * @param Vehicle|null $returnVehicle
     * @return ReturnTripPreparation
     * @throws ValidationException
     */
    public function prepareZjazd(
        array $employeeIds,
        Carbon $returnDate,
        ?Vehicle $returnVehicle = null
    ): ReturnTripPreparation {
        // Validate employees exist
        foreach ($employeeIds as $employeeId) {
            Employee::findOrFail($employeeId);
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
     * @param string|null $notes Additional notes
     * @param LogisticsEvent|null $existingEvent If provided, updates existing event instead of creating new one
     * @param LogisticsEventStatus|null $status If provided, sets this status (only for updates)
     * @return LogisticsEvent
     * @throws ValidationException
     */
    public function commitZjazd(
        ReturnTripPreparation $preparation,
        ?string $notes = null,
        ?LogisticsEvent $existingEvent = null,
        ?LogisticsEventStatus $status = null
    ): LogisticsEvent
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

        return DB::transaction(function () use ($preparation, $baseLocation, $notes, $existingEvent, $status) {
            // Shorten all assignments and save original end_date
            foreach ($preparation->assignmentsToShorten as $assignmentToShorten) {
                $assignment = $assignmentToShorten->assignment;
                $originalEndDate = $assignmentToShorten->currentEndDate;
                
                // Update end_date to return date
                // All assignments implement HasDateRange and have end_date column
                $assignment->update(['end_date' => $preparation->returnDate]);
            }

            // End old vehicle assignments for returning employees (if not already shortened)
            // Track which vehicle assignments were shortened for later participant creation
            $shortenedVehicleAssignments = [];
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
                        // Get original end_date before updating
                        $originalEndDate = $oldVehicleAssignment->end_date; // This is already a Carbon or null
                        $oldVehicleAssignment->update(['end_date' => $preparation->returnDate]);
                        $shortenedVehicleAssignments[] = [
                            'assignment' => $oldVehicleAssignment,
                            'original_end_date' => $originalEndDate, // Keep as Carbon/null for proper handling
                            'employee_id' => $employeeId,
                        ];
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
                $updateData = [
                    'event_date' => $preparation->returnDate,
                    'vehicle_id' => $preparation->returnVehicle?->id,
                    'from_location_id' => $this->getCurrentLocationForEmployees($preparation->employeeIds)?->id ?? $baseLocation->id,
                    'to_location_id' => $baseLocation->id,
                    'notes' => $notes,
                ];
                
                // Update status if provided (only for existing events, not new ones)
                if ($status !== null) {
                    $updateData['status'] = $status;
                }
                
                $event->update($updateData);
                
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
                    'notes' => $notes,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Create participants for shortened assignments (with original_end_date)
            foreach ($preparation->assignmentsToShorten as $assignmentToShorten) {
                $assignment = $assignmentToShorten->assignment;
                $originalEndDate = $assignmentToShorten->currentEndDate;
                
                // Determine assignment type for morph map
                $assignmentType = match (get_class($assignment)) {
                    ProjectAssignment::class => 'project_assignment',
                    AccommodationAssignment::class => 'accommodation_assignment',
                    VehicleAssignment::class => 'vehicle_assignment',
                    default => strtolower(class_basename($assignment)),
                };
                
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $assignment->getEmployee()->id,
                    'assignment_type' => $assignmentType,
                    'assignment_id' => $assignment->id,
                    'original_end_date' => $originalEndDate?->format('Y-m-d'),
                    'status' => 'pending',
                ]);
            }
            
            // Create participants for shortened vehicle assignments (not in assignmentsToShorten)
            foreach ($shortenedVehicleAssignments as $shortened) {
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $shortened['employee_id'],
                    'assignment_type' => 'vehicle_assignment',
                    'assignment_id' => $shortened['assignment']->id,
                    'original_end_date' => $shortened['original_end_date']?->format('Y-m-d'),
                    'status' => 'pending',
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
                    'notes' => 'Zjazd do bazy',
                    'is_return_trip' => true,
                    ]);

                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'assignment_type' => 'vehicle_assignment',
                        'assignment_id' => $newVehicleAssignment->id,
                        'original_end_date' => null, // New assignment, no original end_date
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

        $employee = Employee::find($employeeIds[0]);
        if (!$employee) {
            return null;
        }

        return app(LocationTrackingService::class)->forEmployee($employee);
    }

    /**
     * Reverse a return trip - clean up return trip assignments before editing.
     * 
     * This method:
     * 1. Restores original end_date values for all shortened assignments
     * 2. Deletes return trip vehicle assignments (is_return_trip = true)
     * 3. Deletes all participants
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
            // Get all participants with their assignments and original_end_date
            $participants = $returnTrip->participants()->with('assignment')->get();
            
            // Restore original end_date for all shortened assignments
            foreach ($participants as $participant) {
                // Skip return trip vehicle assignments (they will be deleted)
                if ($participant->assignment_type === 'vehicle_assignment' && $participant->assignment) {
                    $vehicleAssignment = $participant->assignment;
                    if ($vehicleAssignment->is_return_trip) {
                        $vehicleAssignment->delete();
                        continue;
                    }
                }
                
                // Restore original end_date if it was stored
                if ($participant->assignment && $participant->original_end_date !== null) {
                    $assignment = $participant->assignment;
                    
                    // Restore original end_date (null in database means it was indefinite)
                    $assignment->update([
                        'end_date' => $participant->original_end_date
                    ]);
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
    public function createReturn(
        array $employeeIds,
        Carbon $returnDate,
        ?Vehicle $returnVehicle = null,
        ?string $notes = null
    ): LogisticsEvent {
        // For backward compatibility, delegate to new prepare/commit flow
        $preparation = $this->prepareZjazd($employeeIds, $returnDate, $returnVehicle);
        return $this->commitZjazd($preparation, $notes);
    }
}
