<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\ProjectAssignment;
use App\Models\AccommodationAssignment;
use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Enums\EmployeeLocationState;
use Carbon\Carbon;

class LocationTrackingService
{
    public function getLocationStatus(Employee $employee, Carbon $date): array
    {
        $lastEvent = $this->findLastEvent($employee, $date);
        $state = $this->deriveStateFromEvent($lastEvent, $date);
        
        $projectAssignment = $this->findActiveProjectAssignment($employee, $date);
        if ($projectAssignment && $state === EmployeeLocationState::IN_BASE) {
            $state = EmployeeLocationState::OUTSIDE_BASE;
        }
        
        $accommodationAssignment = $this->findActiveAccommodationAssignment($employee, $date);
        
        return [
            'state' => $state,
            'project_name' => $projectAssignment?->project?->name,
            'accommodation_name' => $accommodationAssignment?->accommodation?->name,
        ];
    }

    public function getEmployeeLocation(Employee $employee): ?Location
    {
        $status = $this->getLocationStatus($employee, now());
        
        if ($status['state'] === EmployeeLocationState::IN_TRANSIT) {
            return null;
        }
        
        $projectAssignment = $this->findActiveProjectAssignment($employee, now());
        if ($projectAssignment?->project?->location) {
            return $projectAssignment->project->location;
        }
        
        if ($status['state'] === EmployeeLocationState::IN_BASE) {
            return Location::getBase();
        }
        
        return null;
    }

    public function getEmployeeLocationOnDate(Employee $employee, Carbon $date): Location|string|null
    {
        $status = $this->getLocationStatus($employee, $date);
        
        if ($status['state'] === EmployeeLocationState::IN_TRANSIT) {
            return "W PODRÓŻY";
        }
        
        $projectAssignment = $this->findActiveProjectAssignment($employee, $date);
        if ($projectAssignment?->project?->location) {
            return $projectAssignment->project->location;
        }
        
        if ($status['state'] === EmployeeLocationState::IN_BASE) {
            return Location::getBase();
        }
        
        return null;
    }

    public function getVehicleLocation(Vehicle $vehicle): ?Location
    {
        if ($vehicle->current_location_id) {
            return $vehicle->currentLocation;
        }

        $activeAssignment = $vehicle->assignments()
            ->active()
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->with('employee')
            ->first();

        if ($activeAssignment) {
            return $this->getEmployeeLocation($activeAssignment->employee);
        }

        return Location::getBase();
    }

    protected function findLastEvent(Employee $employee, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::whereHas('participants', 
            fn($q) => $q->where('employee_id', $employee->id)
        )
        ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
        ->where('status', '!=', LogisticsEventStatus::CANCELLED)
        ->where('event_date', '<=', $date)
        ->orderBy('event_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();
    }

    protected function deriveStateFromEvent(?LogisticsEvent $event, Carbon $date): EmployeeLocationState
    {
        if (!$event) {
            return EmployeeLocationState::IN_BASE;
        }

        if ($event->type === LogisticsEventType::DEPARTURE) {
            if ($event->end_date && $event->event_date <= $date && $date < $event->end_date) {
                return EmployeeLocationState::IN_TRANSIT;
            }
            return EmployeeLocationState::OUTSIDE_BASE;
        }

        if ($event->type === LogisticsEventType::RETURN) {
            if ($event->end_date && $event->end_date <= $date) {
                return EmployeeLocationState::IN_BASE;
            }
            return EmployeeLocationState::IN_TRANSIT;
        }

        return EmployeeLocationState::IN_BASE;
    }

    protected function findActiveProjectAssignment(Employee $employee, Carbon $date): ?ProjectAssignment
    {
        if ($employee->relationLoaded('assignments')) {
            $assignment = $employee->assignments
                ->filter(fn($a) => !$a->is_cancelled
                    && $a->start_date <= $date
                    && ($a->end_date === null || $a->end_date >= $date))
                ->sortByDesc('start_date')
                ->sortByDesc('id')
                ->first();
            
            if ($assignment && !$assignment->relationLoaded('project')) {
                $assignment->load('project');
            }
            
            return $assignment;
        }
        
        return $employee->assignments()
            ->where('is_cancelled', false)
            ->where('start_date', '<=', $date)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('project')
            ->first();
    }

    protected function findActiveAccommodationAssignment(Employee $employee, Carbon $date): ?AccommodationAssignment
    {
        if ($employee->relationLoaded('accommodationAssignments')) {
            $assignment = $employee->accommodationAssignments
                ->filter(fn($a) => $a->start_date <= $date
                    && ($a->end_date === null || $a->end_date >= $date))
                ->sortByDesc('start_date')
                ->sortByDesc('id')
                ->first();
            
            if ($assignment && !$assignment->relationLoaded('accommodation')) {
                $assignment->load('accommodation');
            }
            
            return $assignment;
        }
        
        return $employee->accommodationAssignments()
            ->where('start_date', '<=', $date)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('accommodation')
            ->first();
    }
}
