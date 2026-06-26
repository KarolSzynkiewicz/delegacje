<?php

namespace App\Services\PromptEngine;

use App\Models\TimeLog;
use Carbon\Carbon;

class TimeLogPromptBundleService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $logs = TimeLog::query()
            ->with([
                'projectAssignment.employee.companyAssignments.company',
                'projectAssignment.project:id,name',
            ])
            ->whereHas('projectAssignment')
            ->whereBetween('start_time', [$start, $end])
            ->orderBy('start_time')
            ->get();

        // Group by employee
        $byEmployee = $logs->groupBy(fn (TimeLog $log) => $log->projectAssignment?->employee_id);

        $employees = $byEmployee->map(function ($employeeLogs, $employeeId) use ($start, $end) {
            $firstLog = $employeeLogs->first();
            $employee = $firstLog->projectAssignment?->employee;

            if (! $employee) {
                return null;
            }

            // Find which company assignment(s) were active during the query window
            $activeCompanyAssignments = $employee->companyAssignments
                ->filter(function ($ca) use ($start, $end) {
                    $caStart = $ca->start_date ? Carbon::parse($ca->start_date) : null;
                    $caEnd   = $ca->end_date   ? Carbon::parse($ca->end_date)   : null;

                    if (! $caStart) {
                        return false;
                    }

                    $caEnd = $caEnd ?? Carbon::now()->addYears(50);

                    return $caStart->lte($end) && $caEnd->gte($start);
                })
                ->sortByDesc('start_date')
                ->values();

            $companyData = $activeCompanyAssignments->map(fn ($ca) => [
                'id'         => $ca->company?->id,
                'name'       => $ca->company?->name,
                'assigned_from' => $ca->start_date?->toDateString(),
                'assigned_to'   => $ca->end_date?->toDateString(),
            ])->values()->all();

            // Group by project
            $byProject = $employeeLogs->groupBy(fn (TimeLog $log) => $log->projectAssignment?->project_id);

            $projects = $byProject->map(function ($projectLogs, $projectId) {
                $project = $projectLogs->first()->projectAssignment?->project;

                $entries = $projectLogs->map(fn (TimeLog $log) => [
                    'id'           => $log->id,
                    'date'         => Carbon::parse($log->start_time)->toDateString(),
                    'start_time'   => $log->start_time?->toIso8601String(),
                    'end_time'     => $log->end_time?->toIso8601String(),
                    'hours_worked' => $log->hours_worked !== null ? (float) $log->hours_worked : null,
                    'notes'        => $log->notes,
                ])->values()->all();

                $totalHours = $projectLogs->sum(fn (TimeLog $l) => (float) ($l->hours_worked ?? 0));

                return [
                    'id'          => $project?->id,
                    'name'        => $project?->name,
                    'total_hours' => round($totalHours, 2),
                    'entries'     => $entries,
                ];
            })->values()->all();

            $employeeTotalHours = $employeeLogs->sum(fn (TimeLog $l) => (float) ($l->hours_worked ?? 0));

            return [
                'id'          => $employee->id,
                'full_name'   => $employee->full_name,
                'companies'   => $companyData,
                'total_hours' => round($employeeTotalHours, 2),
                'projects'    => $projects,
            ];
        })
            ->filter()
            ->values()
            ->all();

        $totalHours = $logs->sum(fn (TimeLog $l) => (float) ($l->hours_worked ?? 0));

        return [
            'meta' => [
                'schema_version' => 1,
                'generated_at'   => now()->toIso8601String(),
                'period'         => [
                    'start_date' => $start->toDateString(),
                    'end_date'   => $end->toDateString(),
                    'start_at'   => $start->toIso8601String(),
                    'end_at'     => $end->toIso8601String(),
                ],
            ],
            'counts' => [
                'time_logs'   => $logs->count(),
                'employees'   => count($employees),
                'total_hours' => round($totalHours, 2),
            ],
            'employees' => $employees,
        ];
    }
}
