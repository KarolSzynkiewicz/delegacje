<?php

namespace App\Services;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use Carbon\Carbon;

class DefaultRouteWaypointsService
{
    public function __construct(
        protected AssignmentQueryService $assignmentQueryService
    ) {}

    /**
     * Default waypoints for transfer (simple): unique accommodation locations of employees on given day.
     *
     * @return array{waypoints: list<string>, location_stop_notes: array<string,string>}
     */
    public function buildSimpleTransferWaypoints(array $employeeIds, Carbon $day): array
    {
        $empIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds), fn ($id) => $id > 0)));
        if ($empIds === []) {
            return ['waypoints' => [], 'location_stop_notes' => []];
        }

        $employees = Employee::query()
            ->whereIn('id', $empIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $assignments = AccommodationAssignment::query()
            ->whereIn('employee_id', $empIds)
            ->activeAtDate($day)
            ->with('accommodation:id,location_id,name')
            ->get();

        $seen = [];
        $out = [];
        $byLoc = [];
        foreach ($assignments as $aa) {
            $locId = (int) ($aa->accommodation?->location_id ?? 0);
            if ($locId <= 0 || isset($seen[$locId])) {
                // even if already seen for waypoint list, still collect employees for note aggregation
                if ($locId > 0) {
                    $byLoc[$locId][] = (int) $aa->employee_id;
                }

                continue;
            }
            $seen[$locId] = true;
            $out[] = 'loc:'.$locId;
            $byLoc[$locId] ??= [];
            $byLoc[$locId][] = (int) $aa->employee_id;
        }

        $notes = [];
        foreach ($byLoc as $locId => $eids) {
            $names = [];
            foreach (array_values(array_unique($eids)) as $eid) {
                $e = $employees->get($eid);
                if (! $e) {
                    continue;
                }
                $names[] = trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('#'.$eid);
            }
            sort($names);
            if ($names !== []) {
                $notes[(string) $locId] = 'Opuszcza: '.implode(', ', $names);
            }
        }

        return ['waypoints' => $out, 'location_stop_notes' => $notes];
    }

    /**
     * Default waypoints for transfer with reassignment:
     * - pick-ups: current accommodation locations
     * - drop-offs: target project locations from draft map (assignment_id => project_id)
     *
     * @param  array<int,int>  $draftProjectByAssignment  [project_assignment_id => project_id]
     * @param  array<int,int>|null  $targetAccommodationByEmployeeId  [employee_id => accommodation_id]
     * @return array{waypoints: list<string>, location_stop_notes: array<string,string>}
     */
    public function buildReassignmentTransferWaypoints(array $draftProjectByAssignment, Carbon $day, ?array $targetAccommodationByEmployeeId = null): array
    {
        $assignmentIds = array_values(array_unique(array_filter(array_map('intval', array_keys($draftProjectByAssignment)), fn ($id) => $id > 0)));
        if ($assignmentIds === []) {
            return ['waypoints' => [], 'location_stop_notes' => []];
        }

        $pas = ProjectAssignment::query()
            ->whereIn('id', $assignmentIds)
            ->get(['id', 'employee_id']);

        if ($pas->isEmpty()) {
            return ['waypoints' => [], 'location_stop_notes' => []];
        }

        $employeeIds = $pas->pluck('employee_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        // current project per employee (for notes)
        $currentProjects = ProjectAssignment::query()
            ->whereIn('employee_id', $employeeIds)
            ->activeAtDate($day)
            ->with('project:id,name')
            ->get()
            ->keyBy('employee_id');

        // pick-ups: accommodation locations (+ notes)
        $pickupData = $this->buildSimpleTransferWaypoints($employeeIds, $day);
        $pickups = $pickupData['waypoints'];
        $notes = $pickupData['location_stop_notes'];

        // drop-offs: target project locations
        $targetProjectIds = [];
        $targetProjectIdsByEmployee = [];
        foreach ($pas as $pa) {
            $pid = (int) ($draftProjectByAssignment[(int) $pa->id] ?? 0);
            if ($pid > 0) {
                $targetProjectIds[] = $pid;
                $targetProjectIdsByEmployee[(int) $pa->employee_id] = $pid;
            }
        }
        $targetProjectIds = array_values(array_unique($targetProjectIds));

        $dropSeen = [];
        $drops = [];
        $dropLocByProject = [];
        if ($targetProjectIds !== []) {
            $projects = Project::query()
                ->whereIn('id', $targetProjectIds)
                ->get(['id', 'location_id', 'name'])
                ->keyBy('id');

            foreach ($targetProjectIds as $pid) {
                $locId = (int) ($projects->get($pid)?->location_id ?? 0);
                if ($locId <= 0 || isset($dropSeen[$locId])) {
                    continue;
                }
                $dropSeen[$locId] = true;
                $drops[] = 'loc:'.$locId;
                $dropLocByProject[$pid] = $locId;
            }

            // drop notes: employees arriving to target projects (group by location)
            $arrivalsByLoc = [];
            foreach ($targetProjectIdsByEmployee as $eid => $pid) {
                $locId = (int) ($dropLocByProject[$pid] ?? 0);
                if ($locId > 0) {
                    $arrivalsByLoc[$locId][] = (int) $eid;
                }
            }

            foreach ($arrivalsByLoc as $locId => $eids) {
                $names = [];
                foreach (array_values(array_unique($eids)) as $eid) {
                    $e = $employees->get($eid);
                    if (! $e) {
                        continue;
                    }
                    $names[] = trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('#'.$eid);
                }
                sort($names);
                if ($names !== []) {
                    $notes[(string) $locId] = trim(($notes[(string) $locId] ?? '')."\n".'Dołącza: '.implode(', ', $names));
                }
            }
        }

        // merge unique preserving order: pickups then drops
        $seen = [];
        $out = [];
        foreach (array_merge($pickups, $drops) as $key) {
            $id = (int) (str_starts_with($key, 'loc:') ? substr($key, 4) : 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = 'loc:'.$id;
        }

        // enrich pickup notes with current project info where available (optional)
        foreach ($notes as $locId => $text) {
            // only for pickup entries (based on existing key in pickups)
            // No expensive mapping here; keep notes concise.
            $notes[$locId] = trim((string) $text);
        }

        return ['waypoints' => $out, 'location_stop_notes' => array_filter($notes, fn ($v) => is_string($v) && trim($v) !== '')];
    }
}
