<?php

namespace App\Services\PromptEngine;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use App\Models\Rotation;
use App\Models\VehicleAssignment;
use Carbon\Carbon;

class AssignmentPromptBundleService
{
    /**
     * Builds a JSON bundle of all assignments for given employees in the date range.
     *
     * @param  list<int>  $employeeIds  Empty array = all employees
     * @return array<string, mixed>
     */
    public function build(Carbon $start, Carbon $end, array $employeeIds = []): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $employees = Employee::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->when($employeeIds !== [], fn ($q) => $q->whereIn('id', $employeeIds))
            ->get();

        $empIds = $employees->pluck('id')->all();

        if ($empIds === []) {
            return $this->emptyResult($start, $end, $employeeIds);
        }

        $projectAssignments = ProjectAssignment::query()
            ->with(['project:id,name', 'role:id,name'])
            ->whereIn('employee_id', $empIds)
            ->overlappingWith($start, $end)
            ->orderBy('start_date')
            ->get()
            ->groupBy('employee_id');

        $vehicleAssignments = VehicleAssignment::query()
            ->with(['vehicle:id,brand,model,registration_number'])
            ->whereIn('employee_id', $empIds)
            ->overlappingWith($start, $end)
            ->orderBy('start_date')
            ->get()
            ->groupBy('employee_id');

        $accommodationAssignments = AccommodationAssignment::query()
            ->with(['accommodation:id,name,address,city'])
            ->whereIn('employee_id', $empIds)
            ->overlappingWith($start, $end)
            ->orderBy('start_date')
            ->get()
            ->groupBy('employee_id');

        $rotations = Rotation::query()
            ->whereIn('employee_id', $empIds)
            ->overlappingWith($start, $end)
            ->orderBy('start_date')
            ->get()
            ->groupBy('employee_id');

        $logisticsEvents = LogisticsEvent::query()
            ->with([
                'fromLocation:id,name,city',
                'toLocation:id,name,city',
                'participants' => fn ($q) => $q->whereIn('employee_id', $empIds),
            ])
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
            ->whereHas('participants', fn ($q) => $q->whereIn('employee_id', $empIds))
            ->where('event_date', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $start);
            })
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->orderBy('event_date')
            ->get();

        $departuresByEmp = [];
        $returnsByEmp = [];

        foreach ($logisticsEvents as $event) {
            foreach ($event->participants as $participant) {
                $eid = $participant->employee_id;
                if (! in_array($eid, $empIds, true)) {
                    continue;
                }
                if ($event->type === LogisticsEventType::DEPARTURE) {
                    $departuresByEmp[$eid][] = $event;
                } else {
                    $returnsByEmp[$eid][] = $event;
                }
            }
        }

        $serializedEmployees = $employees->map(function (Employee $emp) use (
            $projectAssignments,
            $vehicleAssignments,
            $accommodationAssignments,
            $rotations,
            $departuresByEmp,
            $returnsByEmp,
        ): array {
            return [
                'id' => $emp->id,
                'full_name' => $emp->full_name,
                'email' => $emp->email,
                'project_assignments' => ($projectAssignments[$emp->id] ?? collect())
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'project' => $a->project
                            ? ['id' => $a->project->id, 'name' => $a->project->name]
                            : null,
                        'role' => $a->role
                            ? ['id' => $a->role->id, 'name' => $a->role->name]
                            : null,
                        'start_date' => $a->start_date?->toDateString(),
                        'end_date' => $a->end_date?->toDateString(),
                        'status' => $a->status->value,
                        'notes' => $a->notes,
                    ])->values()->all(),
                'vehicle_assignments' => ($vehicleAssignments[$emp->id] ?? collect())
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'vehicle' => $a->vehicle ? [
                            'id' => $a->vehicle->id,
                            'brand' => $a->vehicle->brand,
                            'model' => $a->vehicle->model,
                            'registration_number' => $a->vehicle->registration_number,
                        ] : null,
                        'position' => $a->position?->value,
                        'start_date' => $a->start_date?->toDateString(),
                        'end_date' => $a->end_date?->toDateString(),
                        'notes' => $a->notes,
                    ])->values()->all(),
                'accommodation_assignments' => ($accommodationAssignments[$emp->id] ?? collect())
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'accommodation' => $a->accommodation ? [
                            'id' => $a->accommodation->id,
                            'name' => $a->accommodation->name,
                            'address' => $a->accommodation->address,
                            'city' => $a->accommodation->city,
                        ] : null,
                        'start_date' => $a->start_date?->toDateString(),
                        'end_date' => $a->end_date?->toDateString(),
                        'notes' => $a->notes,
                    ])->values()->all(),
                'rotations' => ($rotations[$emp->id] ?? collect())
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'start_date' => $r->start_date?->toDateString(),
                        'end_date' => $r->end_date?->toDateString(),
                        'status' => $r->status,
                        'duration_days' => $r->duration_days,
                        'notes' => $r->notes,
                    ])->values()->all(),
                'departures' => collect($departuresByEmp[$emp->id] ?? [])
                    ->map(fn ($e) => $this->serializeLogisticsEvent($e))
                    ->values()->all(),
                'return_trips' => collect($returnsByEmp[$emp->id] ?? [])
                    ->map(fn ($e) => $this->serializeLogisticsEvent($e))
                    ->values()->all(),
            ];
        })->values()->all();

        return [
            'meta' => [
                'schema_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'filters' => [
                    'employee_ids' => $employeeIds === [] ? 'all' : $employeeIds,
                ],
            ],
            'counts' => [
                'employees' => count($serializedEmployees),
            ],
            'employees' => $serializedEmployees,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLogisticsEvent(LogisticsEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type?->value,
            'type_label' => $event->type?->label(),
            'event_date' => $event->event_date?->toDateString(),
            'end_date' => $event->end_date?->toDateString(),
            'from_location' => $event->fromLocation ? [
                'id' => $event->fromLocation->id,
                'name' => $event->fromLocation->name,
                'city' => $event->fromLocation->city,
            ] : null,
            'to_location' => $event->toLocation ? [
                'id' => $event->toLocation->id,
                'name' => $event->toLocation->name,
                'city' => $event->toLocation->city,
            ] : null,
            'status' => $event->status?->value,
            'visual_status' => $event->getVisualStatus(),
            'notes' => $event->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(Carbon $start, Carbon $end, array $employeeIds): array
    {
        return [
            'meta' => [
                'schema_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'filters' => [
                    'employee_ids' => $employeeIds === [] ? 'all' : $employeeIds,
                ],
            ],
            'counts' => ['employees' => 0],
            'employees' => [],
        ];
    }
}
