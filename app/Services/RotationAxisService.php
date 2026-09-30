<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Rotation;
use App\Support\AssignmentTimelineMath;
use Carbon\Carbon;

class RotationAxisService
{
    public const DAY_WIDTH = 26;

    public const SOON_DAYS = 7;

    public function __construct(
        private LocationTrackingService $locations,
        private WeeklyOverviewService $weekly,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function board(string $search, string $place): array
    {
        $today = now()->startOfDay();
        $start = $today->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(3);
        $end = $start->copy()->addDays(41);
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

        $rotations = $employees->isEmpty()
            ? collect()
            : Rotation::query()
                ->where('status', '!=', 'cancelled')
                ->whereIn('employee_id', $employees->pluck('id'))
                ->overlappingWith($startKey, $endKey)
                ->get(['id', 'employee_id', 'start_date', 'end_date'])
                ->groupBy('employee_id');

        $rows = $employees
            ->map(function (Employee $employee) use ($rotations, $plannerDocuments, $startKey, $endKey, $todayKey, $soonKey) {
                $ranges = ($rotations->get($employee->id) ?? collect())
                    ->filter(fn (Rotation $rotation) => $rotation->start_date && $rotation->end_date)
                    ->map(fn (Rotation $rotation) => [
                        'id' => $rotation->id,
                        'start' => $rotation->start_date->toDateString(),
                        'end' => $rotation->end_date->toDateString(),
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
                    $visibleStart = $range['start'] < $startKey ? $startKey : $range['start'];
                    $visibleEnd = $range['end'] > $endKey ? $endKey : $range['end'];
                    $left = AssignmentTimelineMath::dayIndex($startKey, $visibleStart) * self::DAY_WIDTH;
                    $width = (AssignmentTimelineMath::dayIndex($visibleStart, $visibleEnd) + 1) * self::DAY_WIDTH;

                    return [
                        'id' => $range['id'],
                        'start' => $range['start'],
                        'end' => $range['end'],
                        'min' => $limits['min'],
                        'max' => $limits['max'],
                        'left' => $left,
                        'width' => $width,
                        'open' => false,
                        'locked' => false,
                        'pending' => false,
                        'tone' => self::tone($range['start'], $range['end'], $todayKey, $soonKey),
                        'title' => Carbon::parse($range['start'])->format('j.m.Y').' – '.Carbon::parse($range['end'])->format('j.m.Y'),
                    ];
                })->filter()->values();

                return [
                    'id' => $employee->id,
                    'key' => 'rotation',
                    'name' => $employee->full_name,
                    'employee' => $employee,
                    'score' => $employee->latestEvaluation?->average_score,
                    'evaluation' => $employee->latestEvaluation,
                    'documents' => ($plannerDocuments->get($employee->id) ?? collect())->unique('document_id')->values(),
                    'sort' => $bars->min('start') ?? '9999-99-99',
                    'gaps' => AssignmentTimelineMath::gaps([['start' => $startKey, 'end' => $endKey]], $blocks),
                    'bars' => $bars->all(),
                ];
            })
            ->sortBy([
                ['sort', 'asc'],
                ['name', 'asc'],
            ])
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
            return $from.' '.$start->year;
        }
        if ($start->year === $end->year) {
            return $from.'–'.$to.' '.$end->year;
        }

        return $from.' '.$start->year.' – '.$to.' '.$end->year;
    }
}
