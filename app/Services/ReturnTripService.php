<?php

namespace App\Services;

use App\Domain\ReturnTripPreparation;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\ProjectAssignment;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for handling return trips (zjazdy) - mass operation to return employees to base.
 *
 * This service implements the domain model where Return Trip is a superior domain event
 * that affects assignments. It uses prepare/commit pattern for atomic operations.
 *
 * Zjazd nie tworzy już rekordów VehicleAssignment dla „nogi powrotnej” (is_return_trip);
 * transport jest opisany przez LogisticsEvent + uczestników. Skracane są wyłącznie
 * operacyjne przypisania (projekt / mieszkanie / auto bez flagi zjazdu).
 */
class ReturnTripService
{
    public function __construct(
        protected AssignmentQueryService $assignmentQueryService,
        protected VehicleValidationService $vehicleValidationService
    ) {}

    /**
     * Liczba osób fizycznie w aucie (kierowca zewnętrzny + uczestnicy zjazdu) vs pojemność.
     */
    public function assertReturnVehicleCapacityWithinLimits(Vehicle $vehicle, int $occupantCount): void
    {
        $capacity = (int) ($vehicle->capacity ?? 0);
        if ($capacity <= 0) {
            return;
        }

        if ($occupantCount > $capacity) {
            throw ValidationException::withMessages([
                'vehicle_id' => "Ten zjazd wymaga {$occupantCount} miejsc w pojeździe (kierowca i pasażerowie), a pojazd ma tylko {$capacity} miejsc.",
            ]);
        }
    }

    /**
     * Prepare a return trip (dry-run / simulation).
     *
     * Analyzes what would happen if the return trip is executed:
     * - Finds all assignments that would be shortened
     * - Detects conflicts with return vehicle
     *
     * Does NOT modify the database.
     *
     * @throws ValidationException
     */
    public function prepareZjazd(
        array $employeeIds,
        Carbon $returnDate,
        ?Vehicle $returnVehicle = null,
        ?Carbon $endDate = null,
        ?int $excludeEventId = null,
        ?int $vehicleOccupantCount = null
    ): ReturnTripPreparation {
        // Validate employees exist
        $employees = [];
        foreach ($employeeIds as $employeeId) {
            $employees[] = Employee::findOrFail($employeeId);
        }

        // Validate employees are not in transit on return date
        // Exclude the current event if editing (it will be reversed before this check)
        foreach ($employees as $employee) {
            // Check if employee is in transit, but exclude the event being edited
            $inTransitQuery = LogisticsEvent::inTransitOn($employee, $returnDate);
            if ($excludeEventId) {
                $inTransitQuery->where('id', '!=', $excludeEventId);
            }

            if ($inTransitQuery->exists()) {
                throw ValidationException::withMessages([
                    'employee_ids' => "Pracownik {$employee->full_name} jest już w trakcie podróży w dniu {$returnDate->format('Y-m-d')}. Nie można utworzyć powrotu dla pracownika, który jest już w podróży.",
                ]);
            }
        }

        // Validate vehicle availability if specified
        if ($returnVehicle) {
            // Use end_date if provided, otherwise use return_date (same day return)
            $effectiveEndDate = $endDate ?? $returnDate;

            // Check if vehicle is available for logistics event
            try {
                $this->vehicleValidationService->validateForLogisticsEventOrFail(
                    $returnVehicle,
                    $returnDate,
                    $effectiveEndDate,
                    $excludeEventId,
                    true // ignore project assignments; block only if vehicle is in another logistics trip
                );
            } catch (ValidationException $e) {
                // Re-throw with more specific message for return trips (keep original cause if available)
                $original = $e->errors()['vehicle_id'] ?? null;
                $suffix = '';
                if (is_array($original) && ! empty($original)) {
                    $suffix = ' ('.implode(' ', $original).')';
                }
                throw ValidationException::withMessages([
                    'vehicle_id' => "Pojazd {$returnVehicle->registration_number} jest już zajęty w tym okresie ({$returnDate->format('d.m.Y')} - {$effectiveEndDate->format('d.m.Y')}). ".
                        'Nie można utworzyć powrotu z tym pojazdem, ponieważ jest już używany w innym wyjeździe/zjeździe.'.$suffix,
                ]);
            }

            $occupants = $vehicleOccupantCount ?? count($employeeIds);
            $this->assertReturnVehicleCapacityWithinLimits($returnVehicle, $occupants);
        }

        // Get all active assignments for returning employees
        $activeAssignments = $this->assignmentQueryService->getActiveAssignmentsForEmployees($employeeIds, $returnDate);

        // Get active vehicle assignments for return vehicle (if specified)
        $returnVehicleAssignments = collect();
        if ($returnVehicle) {
            $returnVehicleAssignments = VehicleAssignment::where('vehicle_id', $returnVehicle->id)
                ->where('is_return_trip', false)
                ->activeAtDate($returnDate)
                ->get();
        }

        // Create preparation
        $preparation = new ReturnTripPreparation($employeeIds, $returnDate, $returnVehicle);
        $preparation->prepare($activeAssignments, $returnVehicleAssignments);

        return $preparation;
    }

    /**
     * Dane do podglądu zjazdu: przypisania uczestników (skrócenie) oraz osoby tracące auto powrotne.
     *
     * @return array{
     *     participant_rows: list<array{employee_id: int, full_name: string, projects_label: string, vehicle_label: string, house_label: string}>,
     *     displaced_without_vehicle: list<array{employee_id: int, full_name: string}>,
     *     requires_consequences_confirm: bool,
     * }
     */
    public function buildReturnTripPreviewUi(ReturnTripPreparation $preparation): array
    {
        $byEmployee = [];

        foreach ($preparation->assignmentsToShorten as $item) {
            $assignment = $item->assignment;
            $assignment->loadMissing('employee');
            if ($assignment instanceof ProjectAssignment) {
                $assignment->loadMissing('project');
            } elseif ($assignment instanceof VehicleAssignment) {
                $assignment->loadMissing('vehicle');
            } elseif ($assignment instanceof AccommodationAssignment) {
                $assignment->loadMissing('accommodation');
            }
            $employee = $assignment->employee;
            if (! $employee) {
                continue;
            }
            $eid = $employee->id;

            if (! isset($byEmployee[$eid])) {
                $byEmployee[$eid] = [
                    'employee_id' => $eid,
                    'full_name' => $employee->full_name,
                    'projects' => [],
                    'vehicle' => null,
                    'house' => null,
                ];
            }

            if ($assignment instanceof ProjectAssignment) {
                $name = $assignment->project?->name;
                if ($name) {
                    $byEmployee[$eid]['projects'][$name] = true;
                }
            } elseif ($assignment instanceof VehicleAssignment) {
                $v = $assignment->vehicle;
                $byEmployee[$eid]['vehicle'] = $v
                    ? trim($v->registration_number.' – '.$v->brand.' '.$v->model)
                    : '—';
            } elseif ($assignment instanceof AccommodationAssignment) {
                $byEmployee[$eid]['house'] = $assignment->accommodation?->name ?? '—';
            }
        }

        uasort($byEmployee, fn ($a, $b) => strcmp((string) $a['full_name'], (string) $b['full_name']));

        $participantRows = [];
        foreach ($byEmployee as $row) {
            $projects = array_keys($row['projects']);
            sort($projects);
            $participantRows[] = [
                'employee_id' => $row['employee_id'],
                'full_name' => $row['full_name'],
                'projects_label' => ! empty($projects) ? implode(', ', $projects) : '—',
                'vehicle_label' => $row['vehicle'] ?? '—',
                'house_label' => $row['house'] ?? '—',
            ];
        }

        $displaced = [];
        foreach ($preparation->conflicts as $conflict) {
            if ($conflict->isBlocking) {
                continue;
            }
            $assignment = $conflict->assignment;
            $assignment->loadMissing('employee');
            $emp = $assignment->employee;
            if ($emp) {
                $displaced[$emp->id] = [
                    'employee_id' => $emp->id,
                    'full_name' => $emp->full_name,
                ];
            }
        }
        usort($displaced, fn ($a, $b) => strcmp((string) $a['full_name'], (string) $b['full_name']));

        return [
            'participant_rows' => $participantRows,
            'displaced_without_vehicle' => array_values($displaced),
            'requires_consequences_confirm' => count($displaced) > 0,
        ];
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
     * @param  string|null  $notes  Additional notes
     * @param  LogisticsEvent|null  $existingEvent  If provided, updates existing event instead of creating new one
     * @param  LogisticsEventStatus|null  $status  If provided, sets this status (only for updates)
     * @param  Carbon|null  $endDate  End date for the return trip
     *
     * @throws ValidationException
     */
    public function commitZjazd(
        ReturnTripPreparation $preparation,
        ?string $notes = null,
        ?LogisticsEvent $existingEvent = null,
        ?LogisticsEventStatus $status = null,
        ?Carbon $endDate = null
    ): LogisticsEvent {
        // Validate preparation is valid (no blocking conflicts)
        if (! $preparation->isValid) {
            $blockingConflicts = $preparation->conflicts->where('isBlocking', true);
            $messages = $blockingConflicts->pluck('message')->toArray();

            throw ValidationException::withMessages([
                'return_trip' => 'Zjazd nie może zostać wykonany z powodu konfliktów: '.implode(' ', $messages),
            ]);
        }

        $baseLocation = Location::getBase();

        return DB::transaction(function () use ($preparation, $baseLocation, $notes, $existingEvent, $status, $endDate) {
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
                        return $item->assignment->employee->id === $oldVehicleAssignment->employee->id
                            && get_class($item->assignment) === get_class($oldVehicleAssignment)
                            && $item->assignment->id === $oldVehicleAssignment->id;
                    });

                    if (! $alreadyShortened) {
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
                    ->where('is_return_trip', false)
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
                    'end_date' => $endDate ?? $preparation->returnDate,
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
                    'end_date' => $endDate ?? $preparation->returnDate,
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
                    'employee_id' => $assignment->employee->id,
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

            // Zjazd nie tworzy już VehicleAssignment (leg powrotu) — samo zdarzenie + uczestnicy.
            // Upewnij się, że każdy wracający ma wpis uczestnika (np. inTransitOn, lista osób).
            foreach ($preparation->employeeIds as $employeeId) {
                $exists = LogisticsEventParticipant::query()
                    ->where('logistics_event_id', $event->id)
                    ->where('employee_id', $employeeId)
                    ->exists();

                if (! $exists) {
                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'status' => 'pending',
                    ]);
                }
            }

            if ($preparation->returnVehicle) {
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
        if (! $employee) {
            return null;
        }

        return app(LocationTrackingService::class)->getEmployeeLocation($employee);
    }

    /**
     * Reverse a return trip - clean up return trip assignments before editing.
     *
     * This method:
     * 1. Restores original end_date values for all shortened assignments
     * 2. Deletes legacy return trip vehicle assignments (is_return_trip = true), jeśli istnieją
     * 3. Deletes all participants
     *
     * @param  LogisticsEvent  $returnTrip  The return trip to reverse
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
                        'end_date' => $participant->original_end_date,
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
        $preparation = $this->prepareZjazd($employeeIds, $returnDate, $returnVehicle, null, null);

        return $this->commitZjazd($preparation, $notes, null, null, null);
    }
}
