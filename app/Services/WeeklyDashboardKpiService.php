<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use Carbon\Carbon;

final class WeeklyDashboardKpiService
{
    /**
     * Statystyki dla wybranego tygodnia (np. z nawigacji przeglądu tygodniowego).
     *
     * @return array{
     *     week_start: \Carbon\Carbon,
     *     week_end: \Carbon\Carbon,
     *     week_label: string,
     *     transfers_count: int,
     *     departures_count: int,
     *     returns_count: int,
     *     employees_in_field_count: int
     * }
     */
    public function getKpiForWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $rangeStart = $weekStart->copy()->startOfDay();
        $rangeEnd = $weekEnd->copy()->endOfDay();

        $activeEvents = fn ($q) => $q->where('status', '!=', LogisticsEventStatus::CANCELLED);

        $transfersCount = LogisticsEvent::query()
            ->where('type', LogisticsEventType::TRANSFER)
            ->where($activeEvents)
            ->whereBetween('event_date', [$rangeStart, $rangeEnd])
            ->count();

        $departuresCount = LogisticsEvent::query()
            ->where('type', LogisticsEventType::DEPARTURE)
            ->where($activeEvents)
            ->whereBetween('event_date', [$rangeStart, $rangeEnd])
            ->count();

        $returnsCount = LogisticsEvent::query()
            ->where('type', LogisticsEventType::RETURN)
            ->where($activeEvents)
            ->whereBetween('event_date', [$rangeStart, $rangeEnd])
            ->count();

        $employeesInFieldCount = $this->countEmployeesInFieldForWeek($weekStart, $weekEnd);

        return [
            'week_start' => $weekStart->copy(),
            'week_end' => $weekEnd->copy(),
            'week_label' => $weekStart->format('d.m').' – '.$weekEnd->format('d.m.Y'),
            'transfers_count' => $transfersCount,
            'departures_count' => $departuresCount,
            'returns_count' => $returnsCount,
            'employees_in_field_count' => $employeesInFieldCount,
        ];
    }

    /**
     * Unikalne osoby z przypisaniem do projektu przecinającym dany tydzień.
     */
    public function countEmployeesInFieldForWeek(Carbon $weekStart, Carbon $weekEnd): int
    {
        return (int) ProjectAssignment::query()
            ->overlappingWith($weekStart, $weekEnd)
            ->toBase()
            ->selectRaw('count(distinct employee_id) as c')
            ->value('c');
    }

    /**
     * Statystyki bieżącego tygodnia kalendarzowego (ISO).
     */
    public function getCurrentWeekKpi(?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();

        return $this->getKpiForWeek(
            $now->copy()->startOfWeek(),
            $now->copy()->endOfWeek()
        );
    }
}
