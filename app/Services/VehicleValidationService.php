<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\VehiclePosition;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for vehicle validation.
 *
 * This service centralizes all vehicle validation logic to ensure
 * consistency between frontend and backend validation.
 */
class VehicleValidationService
{
    /**
     * Single source of truth for vehicle validation.
     */
    public function __construct() {}

    /**
     * Validate vehicle assignment for project (VehicleAssignment).
     *
     * Checks:
     * 1. Only one driver per vehicle per period
     * 2. Employee doesn't have overlapping assignment to same vehicle
     * 3. Vehicle not used in logistics events (departures/returns)
     * 4. Vehicle capacity (if provided)
     *
     * Note: Multiple employees can use the same vehicle simultaneously if:
     * - There's only one driver (checked in point 1)
     * - Capacity is not exceeded (checked in point 4)
     * - Employee doesn't have overlapping assignment (checked in point 2)
     *
     * @return array ['valid' => bool, 'errors' => array, 'conflicts' => array]
     */
    public function validateForProjectAssignment(
        Vehicle $vehicle,
        Employee $employee,
        VehiclePosition $position,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null,
        ?int $currentCapacity = null,
        ?int $excludeEventId = null
    ): array {
        $errors = [];
        $conflicts = [];

        // 1. Check driver availability (only one driver per vehicle per period)
        if ($position === VehiclePosition::DRIVER) {
            $driverConflicts = $this->checkDriverConflicts($vehicle, $startDate, $endDate, $excludeAssignmentId);
            if (! empty($driverConflicts)) {
                $errors[] = 'Pojazd ma już przypisanego kierowcę w tym okresie.';
                $conflicts = array_merge($conflicts, $driverConflicts);
            }
        }

        // 2. Check employee doesn't have overlapping assignment to same vehicle
        $employeeConflicts = $this->checkEmployeeOverlappingAssignments($employee, $vehicle, $startDate, $endDate, $excludeAssignmentId);
        if (! empty($employeeConflicts)) {
            $errors[] = 'Pracownik ma już przypisanie do tego pojazdu w tym okresie.';
            $conflicts = array_merge($conflicts, $employeeConflicts);
        }

        // 3. Check vehicle not used in logistics events
        // Note: Wyjazd zajmuje pojazd od event_date do end_date (włącznie)
        // Ale przypisania mogą zaczynać się od end_date (dzień przyjazdu), bo wtedy pojazd już jest dostępny
        $logisticsConflicts = $this->checkVehicleLogisticsEvents($vehicle, $startDate, $endDate, $excludeEventId);
        if (! empty($logisticsConflicts)) {
            $errors[] = 'Pojazd jest już zajęty przez inny wyjazd/zjazd w tym okresie.';
            $conflicts = array_merge($conflicts, $logisticsConflicts);
        }

        // 4. Check vehicle capacity
        if ($currentCapacity !== null && $vehicle->capacity && $currentCapacity > $vehicle->capacity) {
            $errors[] = "Przekroczona pojemność pojazdu ({$currentCapacity}/{$vehicle->capacity}).";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Validate vehicle for logistics event (departure/return).
     *
     * Checks:
     * 1. Vehicle not used in other logistics events
     * 2. Vehicle not used in project assignments (VehicleAssignment)
     *
     * @return array ['valid' => bool, 'errors' => array, 'conflicts' => array]
     */
    public function validateForLogisticsEvent(
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeEventId = null,
        bool $ignoreProjectAssignments = false
    ): array {
        $errors = [];
        $conflicts = [];

        // 1. Check vehicle not used in other logistics events
        $logisticsConflicts = $this->checkVehicleLogisticsEvents($vehicle, $startDate, $endDate, $excludeEventId);
        if (! empty($logisticsConflicts)) {
            $errors[] = 'Pojazd jest już zajęty przez inny wyjazd/zjazd w tym okresie.';
            $conflicts = array_merge($conflicts, $logisticsConflicts);
        }

        // 2. Check vehicle not used in project assignments (optional)
        // For some domain flows (e.g. Return Trip / Zjazd) a vehicle can be "in project"
        // but still be allowed as long as it is not in another logistics trip.
        if (! $ignoreProjectAssignments) {
            $projectConflicts = $this->checkVehicleProjectAssignments($vehicle, null, $startDate, $endDate);
            if (! empty($projectConflicts)) {
                $errors[] = 'Pojazd jest przypisany do projektu w tym okresie.';
                $conflicts = array_merge($conflicts, $projectConflicts);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Check if vehicle has driver conflicts.
     */
    protected function checkDriverConflicts(
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null
    ): array {
        $query = $vehicle->assignments()
            ->where('position', VehiclePosition::DRIVER->value)
            ->where('is_return_trip', false)
            ->where(function ($q) {
                // Exclude assignments linked to cancelled logistics events
                $q->whereNull('logistics_event_id')
                    ->orWhereHas('logisticsEvent', function ($query) {
                        $query->where('status', '!=', LogisticsEventStatus::CANCELLED->value);
                    });
            })
            ->overlappingWith($startDate, $endDate);

        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $conflicts = $query->with('employee')->get();

        return $conflicts->map(function ($assignment) {
            return [
                'type' => 'vehicle_assignment',
                'id' => $assignment->id,
                'description' => "Kierowca: {$assignment->employee->full_name} ({$assignment->start_date->format('d.m.Y')} - {$assignment->end_date->format('d.m.Y')})",
                'route' => 'vehicle-assignments.show',
                'route_params' => ['vehicle_assignment' => $assignment->id],
            ];
        })->toArray();
    }

    /**
     * Check if employee has overlapping assignments to same vehicle.
     */
    protected function checkEmployeeOverlappingAssignments(
        Employee $employee,
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null
    ): array {
        $query = $employee->vehicleAssignments()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_return_trip', false)
            ->where(function ($q) {
                // Exclude assignments linked to cancelled logistics events
                $q->whereNull('logistics_event_id')
                    ->orWhereHas('logisticsEvent', function ($query) {
                        $query->where('status', '!=', LogisticsEventStatus::CANCELLED->value);
                    });
            })
            ->overlappingWith($startDate, $endDate);

        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $conflicts = $query->get();

        return $conflicts->map(function ($assignment) {
            return [
                'type' => 'vehicle_assignment',
                'id' => $assignment->id,
                'description' => "Przypisanie: {$assignment->start_date->format('d.m.Y')} - {$assignment->end_date->format('d.m.Y')}",
                'route' => 'vehicle-assignments.show',
                'route_params' => ['vehicle_assignment' => $assignment->id],
            ];
        })->toArray();
    }

    /**
     * Check if vehicle is used by other employees in projects.
     */
    protected function checkVehicleProjectAssignments(
        Vehicle $vehicle,
        ?int $excludeEmployeeId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null
    ): array {
        $query = VehicleAssignment::where('vehicle_id', $vehicle->id)
            ->where(function ($q) {
                // Exclude assignments linked to cancelled logistics events
                $q->whereNull('logistics_event_id')
                    ->orWhereHas('logisticsEvent', function ($query) {
                        $query->where('status', '!=', LogisticsEventStatus::CANCELLED->value);
                    });
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                    ->where(function ($q2) use ($startDate) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    });
            });

        if ($excludeEmployeeId) {
            $query->where('employee_id', '!=', $excludeEmployeeId);
        }

        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $conflicts = $query->with('employee')->get();

        return $conflicts->map(function ($assignment) {
            return [
                'type' => 'vehicle_assignment',
                'id' => $assignment->id,
                'description' => "Projekt: {$assignment->employee->full_name} ({$assignment->start_date->format('d.m.Y')} - {$assignment->end_date->format('d.m.Y')})",
                'route' => 'vehicle-assignments.show',
                'route_params' => ['vehicle_assignment' => $assignment->id],
            ];
        })->toArray();
    }

    /**
     * Check if vehicle is used in logistics events.
     */
    protected function checkVehicleLogisticsEvents(
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeEventId = null
    ): array {
        $query = LogisticsEvent::where('vehicle_id', $vehicle->id)
            ->where('status', '!=', LogisticsEventStatus::CANCELLED->value)
            ->where(function ($q) use ($startDate, $endDate) {
                // Wyjazd zajmuje pojazd od event_date do end_date (włącznie)
                // Ale przypisania mogą zaczynać się od end_date (dzień przyjazdu), bo wtedy pojazd już jest dostępny
                // Więc sprawdzamy nakładanie: event_date < assignment_end_date AND assignment_start_date < event_end_date
                $q->where(function ($query) use ($startDate, $endDate) {
                    $query->where('event_date', '<', $endDate) // event_date < assignment_end_date
                        ->where(function ($q2) use ($startDate) {
                            $q2->whereRaw('COALESCE(end_date, event_date) > ?', [$startDate]); // event_end_date > assignment_start_date
                        });
                });
            });

        if ($excludeEventId) {
            $query->where('id', '!=', $excludeEventId);
        }

        $conflicts = $query->with(['toLocation', 'fromLocation'])->get();

        return $conflicts->map(function ($event) {
            $dates = $event->event_date->format('d.m.Y');
            if ($event->end_date) {
                $dates .= ' - '.$event->end_date->format('d.m.Y');
            }

            return [
                'type' => 'logistics_event',
                'id' => $event->id,
                'description' => "{$event->type->label()}: {$dates} ({$event->toLocation->name})",
                'route' => 'departures.show',
                'route_params' => ['departure' => $event->id],
            ];
        })->toArray();
    }

    /**
     * Throw ValidationException if validation fails.
     *
     * @throws ValidationException
     */
    public function validateForProjectAssignmentOrFail(
        Vehicle $vehicle,
        Employee $employee,
        VehiclePosition $position,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null,
        ?int $currentCapacity = null,
        ?int $excludeEventId = null
    ): void {
        $result = $this->validateForProjectAssignment(
            $vehicle,
            $employee,
            $position,
            $startDate,
            $endDate,
            $excludeAssignmentId,
            $currentCapacity,
            $excludeEventId
        );

        if (! $result['valid']) {
            throw ValidationException::withMessages([
                'vehicle_id' => $result['errors'],
            ]);
        }
    }

    /**
     * Throw ValidationException if validation fails.
     *
     * @throws ValidationException
     */
    public function validateForLogisticsEventOrFail(
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeEventId = null,
        bool $ignoreProjectAssignments = false
    ): void {
        $result = $this->validateForLogisticsEvent(
            $vehicle,
            $startDate,
            $endDate,
            $excludeEventId,
            $ignoreProjectAssignments
        );

        if (! $result['valid']) {
            $vehicleLabel = trim(($vehicle->registration_number ?? '').' '.($vehicle->brand ?? '').' '.($vehicle->model ?? ''));
            $vehicleLabel = $vehicleLabel !== '' ? $vehicleLabel : ('ID: '.$vehicle->id);

            $conflictLines = collect($result['conflicts'] ?? [])
                ->pluck('description')
                ->filter()
                ->map(fn ($d) => 'Konflikt: '.$d)
                ->values()
                ->all();

            $messages = array_merge(
                array_map(fn ($e) => $e.' (Pojazd: '.$vehicleLabel.')', $result['errors'] ?? []),
                $conflictLines
            );

            throw ValidationException::withMessages([
                'vehicle_id' => ! empty($messages) ? $messages : ($result['errors'] ?? ['Pojazd jest niedostępny w tym okresie.']),
            ]);
        }
    }
}
