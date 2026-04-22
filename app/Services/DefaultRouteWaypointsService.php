<?php

namespace App\Services;

use App\Models\Accommodation;
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
                $notes[(string) $locId] = 'Opuszcza (dom): '.implode(', ', $names);
            }
        }

        return ['waypoints' => $out, 'location_stop_notes' => $notes];
    }

    /**
     * @param  array<string, string>  $notes
     */
    private function appendLocationNote(array &$notes, int $locationId, string $segment): void
    {
        $segment = trim($segment);
        if ($segment === '' || $locationId <= 0) {
            return;
        }
        $k = (string) $locationId;
        $prev = isset($notes[$k]) ? trim((string) $notes[$k]) : '';
        $notes[$k] = $prev === '' ? $segment : $prev."\n".$segment;
    }

    /**
     * @param  list<list<string>>  $groups  kolejność grup, wewnątrz grupy zachowujemy kolejność pierwszego wystąpienia loc:
     * @return list<string>
     */
    private function mergeWaypointLocGroups(array $groups): array
    {
        $seen = [];
        $out = [];
        foreach ($groups as $keys) {
            foreach ($keys as $key) {
                if (! is_string($key) || ! str_starts_with($key, 'loc:')) {
                    continue;
                }
                $id = (int) substr($key, 4);
                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $out[] = 'loc:'.$id;
            }
        }

        return $out;
    }

    /**
     * Default waypoints for transfer with reassignment:
     * - lokalizacje projektów **opuszczanych** (bieżące project_id przypisań ze szkicu)
     * - odbiór z **obecnych** domów (mieszkań)
     * - lokalizacje **nowych** domów ze szkicu (gdy przekazano mapę)
     * - docelowe lokalizacje **projektów** z szkicu
     *
     * Notatki przy `loc:` są **doklejane** wierszami (nie nadpisują się), żeby widać było
     * zarówno opuszczanie, meldunek w nowym domu, jak i dołączenie do projektu.
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
            ->with(['project:id,name,location_id'])
            ->get();

        if ($pas->isEmpty()) {
            return ['waypoints' => [], 'location_stop_notes' => []];
        }

        $employeeIds = $pas->pluck('employee_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $notes = [];

        // 1) Projekty opuszczane (bieżące miejsce pracy wg wiersza ProjectAssignment w bazie)
        $sourceWaypoints = [];
        $sourceSeenLoc = [];
        $sourceNamesByLocProject = [];
        foreach ($pas as $pa) {
            $locId = (int) ($pa->project?->location_id ?? 0);
            if ($locId <= 0) {
                continue;
            }
            if (! isset($sourceSeenLoc[$locId])) {
                $sourceSeenLoc[$locId] = true;
                $sourceWaypoints[] = 'loc:'.$locId;
            }
            $eid = (int) $pa->employee_id;
            $pid = (int) $pa->project_id;
            $label = trim(($employees->get($eid)?->first_name ?? '').' '.($employees->get($eid)?->last_name ?? '')) ?: ('#'.$eid);
            $sourceNamesByLocProject[$locId][$pid][] = $label;
        }
        foreach ($sourceNamesByLocProject as $locId => $byPid) {
            foreach ($byPid as $pid => $labels) {
                $paRef = $pas->first(fn ($p) => (int) $p->project_id === $pid);
                $pname = $paRef?->project?->name ?? '?';
                $uniq = array_values(array_unique(array_filter($labels)));
                sort($uniq);
                if ($uniq !== []) {
                    $this->appendLocationNote($notes, (int) $locId, 'Opuszcza (projekt „'.$pname.'”): '.implode(', ', $uniq));
                }
            }
        }

        // 2) Obecne domy (odbiór)
        $pickupData = $this->buildSimpleTransferWaypoints($employeeIds, $day);
        $pickups = $pickupData['waypoints'];
        foreach ($pickupData['location_stop_notes'] as $locKey => $text) {
            $this->appendLocationNote($notes, (int) $locKey, (string) $text);
        }

        // 3) Nowe domy ze szkicu (lokalizacja mieszkania docelowego)
        $newAccWaypoints = [];
        $newAccSeenLoc = [];
        if (is_array($targetAccommodationByEmployeeId) && $targetAccommodationByEmployeeId !== []) {
            $accIds = array_values(array_unique(array_filter(array_map('intval', $targetAccommodationByEmployeeId), fn ($id) => $id > 0)));
            $accs = $accIds === []
                ? collect()
                : Accommodation::query()->whereIn('id', $accIds)->get(['id', 'location_id', 'name'])->keyBy('id');

            $newAccLabelsByLocAcc = [];
            foreach ($employeeIds as $eid) {
                $accId = (int) ($targetAccommodationByEmployeeId[$eid] ?? $targetAccommodationByEmployeeId[(string) $eid] ?? 0);
                if ($accId <= 0) {
                    continue;
                }
                $acc = $accs->get($accId);
                $locId = (int) ($acc?->location_id ?? 0);
                if ($locId <= 0) {
                    continue;
                }
                if (! isset($newAccSeenLoc[$locId])) {
                    $newAccSeenLoc[$locId] = true;
                    $newAccWaypoints[] = 'loc:'.$locId;
                }
                $label = trim(($employees->get($eid)?->first_name ?? '').' '.($employees->get($eid)?->last_name ?? '')) ?: ('#'.$eid);
                $newAccLabelsByLocAcc[$locId][$accId][] = $label;
            }
            foreach ($newAccLabelsByLocAcc as $locId => $byAccId) {
                foreach ($byAccId as $accId => $labels) {
                    $acc = $accs->get($accId);
                    $aname = $acc?->name ?? ('#'.$accId);
                    $uniq = array_values(array_unique(array_filter($labels)));
                    sort($uniq);
                    if ($uniq !== []) {
                        $this->appendLocationNote($notes, (int) $locId, 'Melduje się (dom „'.$aname.'”): '.implode(', ', $uniq));
                    }
                }
            }
        }

        // 4) Projekty docelowe ze szkicu
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

        $drops = [];
        $dropLocByProject = [];
        if ($targetProjectIds !== []) {
            $projects = Project::query()
                ->whereIn('id', $targetProjectIds)
                ->get(['id', 'location_id', 'name'])
                ->keyBy('id');

            $dropSeen = [];
            foreach ($targetProjectIds as $pid) {
                $locId = (int) ($projects->get($pid)?->location_id ?? 0);
                if ($locId <= 0 || isset($dropSeen[$locId])) {
                    continue;
                }
                $dropSeen[$locId] = true;
                $drops[] = 'loc:'.$locId;
                $dropLocByProject[$pid] = $locId;
            }

            $arrivalsByLocProject = [];
            foreach ($targetProjectIdsByEmployee as $eid => $pid) {
                $locId = (int) ($dropLocByProject[$pid] ?? 0);
                if ($locId <= 0) {
                    continue;
                }
                $arrivalsByLocProject[$locId][$pid][] = (int) $eid;
            }

            foreach ($arrivalsByLocProject as $locId => $byPid) {
                foreach ($byPid as $pid => $eids) {
                    $pname = $projects->get($pid)?->name ?? '?';
                    $names = [];
                    foreach (array_values(array_unique($eids)) as $empId) {
                        $e = $employees->get($empId);
                        if (! $e) {
                            continue;
                        }
                        $names[] = trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('#'.$empId);
                    }
                    sort($names);
                    if ($names !== []) {
                        $this->appendLocationNote($notes, (int) $locId, 'Dołącza (projekt „'.$pname.'”): '.implode(', ', $names));
                    }
                }
            }
        }

        $out = $this->mergeWaypointLocGroups([$sourceWaypoints, $pickups, $newAccWaypoints, $drops]);

        foreach ($notes as $locId => $text) {
            $notes[$locId] = trim((string) $text);
        }

        return ['waypoints' => $out, 'location_stop_notes' => array_filter($notes, fn ($v) => is_string($v) && trim($v) !== '')];
    }
}
