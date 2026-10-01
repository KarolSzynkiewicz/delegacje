<?php

namespace App\Services;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\ProjectAssignment;
use App\Models\Rotation;
use App\Models\VehicleAssignment;
use App\Support\AssignmentTimelineMath;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RotationAxisService
{
    public const DAY_WIDTH = 26;

    public const SOON_DAYS = 7;

    /** Długość okna osi (6 tygodni). */
    public const WINDOW_DAYS = 41;

    /** Przesunięcie strzałkami: połowa okna (3 tygodnie), żeby nachodziły się okresy. */
    public const STEP_DAYS = 21;

    public function __construct(
        private LocationTrackingService $locations,
        private WeeklyOverviewService $weekly,
    ) {}

    /**
     * Domyślny start okna: poniedziałek bieżącego tygodnia minus 3 tygodnie.
     */
    public static function defaultWindowStart(?Carbon $today = null): Carbon
    {
        $today = ($today ?? now())->copy()->startOfDay();

        return $today->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(3);
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function windowForOffset(int $periodOffset, ?Carbon $today = null): array
    {
        $start = self::defaultWindowStart($today)->addDays($periodOffset * self::STEP_DAYS);
        $end = $start->copy()->addDays(self::WINDOW_DAYS);

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array<string, mixed>
     */
    public function board(string $search, string $place, int $periodOffset = 0): array
    {
        $today = now()->startOfDay();
        $window = self::windowForOffset($periodOffset, $today);
        $start = $window['start'];
        $end = $window['end'];
        $todayKey = $today->toDateString();
        $soonKey = $today->copy()->addDays(self::SOON_DAYS)->toDateString();
        $startKey = $start->toDateString();
        $endKey = $end->toDateString();

        $away = array_fill_keys($this->locations->employeeIdsNotInBaseOn($today), true);
        $needle = mb_strtolower(trim($search));

        $employees = Employee::query()
            ->with(['latestEvaluation.createdBy'])
            ->whereNull('terminated_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'phone', 'image_path'])
            ->filter(function (Employee $employee) use ($away, $place, $needle) {
                $isAway = isset($away[$employee->id]);
                if ($place === 'away' && ! $isAway) {
                    return false;
                }
                if ($place === 'base' && $isAway) {
                    return false;
                }
                if ($needle === '') {
                    return true;
                }
                $name = mb_strtolower($employee->full_name);

                return str_contains($name, $needle) || str_contains(mb_strtolower((string) $employee->phone), $needle);
            })
            ->values();

        $plannerDocuments = $this->weekly->loadPlannerDocumentsByEmployee($employees->pluck('id'));

        $employeeIds = $employees->pluck('id');

        $rotations = $employees->isEmpty()
            ? collect()
            : Rotation::query()
                ->where('status', '!=', 'cancelled')
                ->whereIn('employee_id', $employeeIds)
                ->overlappingWith($startKey, $endKey)
                ->get(['id', 'employee_id', 'start_date', 'end_date', 'notes'])
                ->groupBy('employee_id');

        $projects = $employees->isEmpty()
            ? collect()
            : ProjectAssignment::query()
                ->whereIn('employee_id', $employeeIds)
                ->overlappingWith($startKey, $endKey)
                ->with('project:id,name')
                ->orderBy('start_date')
                ->get(['id', 'employee_id', 'project_id', 'start_date', 'end_date'])
                ->groupBy('employee_id');

        $houses = $employees->isEmpty()
            ? collect()
            : AccommodationAssignment::query()
                ->whereIn('employee_id', $employeeIds)
                ->overlappingWith($startKey, $endKey)
                ->with('accommodation:id,name')
                ->orderBy('start_date')
                ->get(['id', 'employee_id', 'accommodation_id', 'start_date', 'end_date'])
                ->groupBy('employee_id');

        $cars = $employees->isEmpty()
            ? collect()
            : VehicleAssignment::query()
                ->whereIn('employee_id', $employeeIds)
                ->overlappingWith($startKey, $endKey)
                ->with('vehicle:id,registration_number,brand,model')
                ->orderBy('start_date')
                ->get(['id', 'employee_id', 'vehicle_id', 'start_date', 'end_date', 'position', 'is_return_trip'])
                ->groupBy('employee_id');

        $rows = $employees
            ->map(function (Employee $employee) use ($rotations, $projects, $houses, $cars, $plannerDocuments, $startKey, $endKey, $todayKey, $soonKey) {
                $ranges = ($rotations->get($employee->id) ?? collect())
                    ->filter(fn (Rotation $rotation) => $rotation->start_date && $rotation->end_date)
                    ->map(fn (Rotation $rotation) => [
                        'id' => $rotation->id,
                        'start' => $rotation->start_date->toDateString(),
                        'end' => $rotation->end_date->toDateString(),
                        'notes' => $rotation->notes,
                        'show_url' => route('employees.rotations.show', [$employee, $rotation]),
                    ])
                    ->sortBy('start')
                    ->values();

                $blocks = $ranges->map(fn (array $range) => ['start' => $range['start'], 'end' => $range['end']])->all();
                $bars = $ranges->map(function (array $range) use ($ranges, $startKey, $endKey, $todayKey, $soonKey) {
                    if ($range['end'] < $startKey || $range['start'] > $endKey) {
                        return null;
                    }
                    $siblings = $ranges
                        ->reject(fn (array $sibling) => $sibling['id'] === $range['id'])
                        ->map(fn (array $sibling) => ['start' => $sibling['start'], 'end' => $sibling['end']])
                        ->all();
                    $limits = AssignmentTimelineMath::resizeLimits($range['start'], $range['end'], $siblings, $startKey, $endKey);
                    $pixels = $this->visiblePixels($startKey, $endKey, $range['start'], $range['end']);
                    if ($pixels === null) {
                        return null;
                    }

                    return [
                        'id' => $range['id'],
                        'start' => $range['start'],
                        'end' => $range['end'],
                        'notes' => $range['notes'],
                        'show_url' => $range['show_url'],
                        'min' => $limits['min'],
                        'max' => $limits['max'],
                        'left' => $pixels['left'],
                        'width' => $pixels['width'],
                        'open' => false,
                        'locked' => false,
                        'pending' => false,
                        'row' => 0,
                        'tone' => self::tone($range['start'], $range['end'], $todayKey, $soonKey),
                        'label' => 'Rotacja',
                        'title' => Carbon::parse($range['start'])->format('j.m.Y').' – '.Carbon::parse($range['end'])->format('j.m.Y')
                            .($range['notes'] ? ' · '.$range['notes'] : ''),
                    ];
                })->filter()->values();

                $context = $this->contextBars(
                    $projects->get($employee->id) ?? collect(),
                    $houses->get($employee->id) ?? collect(),
                    $cars->get($employee->id) ?? collect(),
                    $startKey,
                    $endKey,
                );

                return [
                    'id' => $employee->id,
                    'key' => 'rotation',
                    'name' => $employee->full_name,
                    'employee' => $employee,
                    'score' => $employee->latestEvaluation?->average_score,
                    'evaluation' => $employee->latestEvaluation,
                    'documents' => ($plannerDocuments->get($employee->id) ?? collect())->unique('document_id')->values(),
                    'gaps' => AssignmentTimelineMath::gaps([['start' => $startKey, 'end' => $endKey]], $blocks),
                    'bars' => $bars->all(),
                    'context' => $context,
                    'track_rows' => 4,
                ];
            })
            ->values();

        $days = [];
        $cursor = $start->copy();
        $weekdays = ['Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'Nd'];
        while ($cursor->lte($end)) {
            $iso = $cursor->dayOfWeekIso;
            $days[] = [
                'number' => $cursor->format('j'),
                'weekday' => $weekdays[$iso - 1],
                'weekend' => $iso >= 6,
            ];
            $cursor->addDay();
        }

        $wednesday = $today->copy()->next(Carbon::WEDNESDAY);

        return [
            'start' => $startKey,
            'end' => $endKey,
            'today' => $todayKey,
            'soon' => $soonKey,
            'period_offset' => $periodOffset,
            'range_label' => self::rangeLabel($start, $end),
            'day_width' => self::DAY_WIDTH,
            'day_count' => count($days),
            'days' => $days,
            'today_left' => self::markerLeft($startKey, $endKey, $todayKey),
            'wednesday_left' => self::markerLeft($startKey, $endKey, $wednesday->toDateString()),
            'rows' => $rows->all(),
        ];
    }

    public static function tone(string $start, string $end, string $today, string $soon): string
    {
        if ($end < $today) {
            return 'past';
        }
        if ($start > $today) {
            return 'future';
        }
        if ($end <= $soon) {
            return 'soon';
        }

        return 'active';
    }

    public static function initials(string $first, string $last): string
    {
        $a = mb_substr(trim($first), 0, 1);
        $b = mb_substr(trim($last), 0, 1);

        return mb_strtoupper($a.$b);
    }

    /**
     * Paski projektu / domu / auta (tylko podgląd) — jak na osi pracownika.
     *
     * @return list<array<string, mixed>>
     */
    private function contextBars(Collection $projects, Collection $houses, Collection $cars, string $startKey, string $endKey): array
    {
        $out = [];

        foreach ($projects as $assignment) {
            $start = $assignment->start_date?->toDateString();
            $end = $assignment->end_date?->toDateString();
            if (! $start) {
                continue;
            }
            $pixels = $this->visiblePixels($startKey, $endKey, $start, $end);
            if ($pixels === null) {
                continue;
            }
            $label = $assignment->project?->name ?? 'Projekt';
            $out[] = [
                'id' => 'p-'.$assignment->id,
                'kind' => 'project',
                'label' => $label,
                'title' => $label.' · '.Carbon::parse($start)->format('j.m.Y').($end ? ' – '.Carbon::parse($end)->format('j.m.Y') : ' →'),
                'left' => $pixels['left'],
                'width' => $pixels['width'],
                'open' => $end === null,
                'row' => 1,
            ];
        }

        foreach ($houses as $assignment) {
            $start = $assignment->start_date?->toDateString();
            $end = $assignment->end_date?->toDateString();
            if (! $start) {
                continue;
            }
            $pixels = $this->visiblePixels($startKey, $endKey, $start, $end);
            if ($pixels === null) {
                continue;
            }
            $label = $assignment->accommodation?->name ?? 'Dom';
            $out[] = [
                'id' => 'a-'.$assignment->id,
                'kind' => 'accommodation',
                'label' => $label,
                'title' => $label.' · '.Carbon::parse($start)->format('j.m.Y').($end ? ' – '.Carbon::parse($end)->format('j.m.Y') : ' →'),
                'left' => $pixels['left'],
                'width' => $pixels['width'],
                'open' => $end === null,
                'row' => 2,
            ];
        }

        foreach ($cars as $assignment) {
            $start = $assignment->start_date?->toDateString();
            $end = $assignment->end_date?->toDateString();
            if (! $start) {
                continue;
            }
            $pixels = $this->visiblePixels($startKey, $endKey, $start, $end);
            if ($pixels === null) {
                continue;
            }
            $vehicle = $assignment->vehicle;
            $label = trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '').' '.($vehicle->registration_number ?? ''));
            $label = $label !== '' ? $label : 'Auto';
            $out[] = [
                'id' => 'v-'.$assignment->id,
                'kind' => 'vehicle',
                'label' => $label,
                'title' => $label.' · '.Carbon::parse($start)->format('j.m.Y').($end ? ' – '.Carbon::parse($end)->format('j.m.Y') : ' →'),
                'left' => $pixels['left'],
                'width' => $pixels['width'],
                'open' => $end === null,
                'row' => 3,
            ];
        }

        return $out;
    }

    /**
     * @return array{left: int, width: int}|null
     */
    private function visiblePixels(string $windowStart, string $windowEnd, string $start, ?string $end): ?array
    {
        $visibleEnd = $end ?? $windowEnd;
        if ($visibleEnd < $windowStart || $start > $windowEnd) {
            return null;
        }
        $visibleStart = $start < $windowStart ? $windowStart : $start;
        $visibleEnd = $visibleEnd > $windowEnd ? $windowEnd : $visibleEnd;
        if ($visibleStart > $visibleEnd) {
            return null;
        }

        return [
            'left' => AssignmentTimelineMath::dayIndex($windowStart, $visibleStart) * self::DAY_WIDTH,
            'width' => (AssignmentTimelineMath::dayIndex($visibleStart, $visibleEnd) + 1) * self::DAY_WIDTH,
        ];
    }

    private static function markerLeft(string $windowStart, string $windowEnd, string $day): ?int
    {
        if ($day < $windowStart || $day > $windowEnd) {
            return null;
        }

        $index = AssignmentTimelineMath::dayIndex($windowStart, $day);

        return $index * self::DAY_WIDTH + intdiv(self::DAY_WIDTH, 2);
    }

    private static function rangeLabel(Carbon $start, Carbon $end): string
    {
        $months = [1 => 'sty', 2 => 'lut', 3 => 'mar', 4 => 'kwi', 5 => 'maj', 6 => 'cze', 7 => 'lip', 8 => 'sie', 9 => 'wrz', 10 => 'paź', 11 => 'lis', 12 => 'gru'];
        $from = mb_convert_case($months[$start->month], MB_CASE_TITLE, 'UTF-8');
        $to = mb_convert_case($months[$end->month], MB_CASE_TITLE, 'UTF-8');
        if ($start->year === $end->year && $start->month === $end->month) {
            return $start->format('j').'–'.$end->format('j').' '.$from.' '.$start->year;
        }
        if ($start->year === $end->year) {
            return $start->format('j').' '.$from.' – '.$end->format('j').' '.$to.' '.$end->year;
        }

        return $start->format('j').' '.$from.' '.$start->year.' – '.$end->format('j').' '.$to.' '.$end->year;
    }
}
