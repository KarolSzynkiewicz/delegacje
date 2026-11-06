<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing time logs (real work hours tracking).
 */
class TimeLogService
{
    /**
     * Create a time log entry for a project assignment.
     *
     * @throws ValidationException
     */
    public function createTimeLog(
        ProjectAssignment $assignment,
        Carbon $workDate,
        float $hoursWorked,
        ?string $notes = null
    ): TimeLog {
        $workDate = $workDate->copy()->startOfDay();

        // Assignment jest już świeży - nie trzeba refresh()

        // Validate work date is within assignment period
        $this->validateWorkDateWithinAssignment($assignment, $workDate);

        // Validate hours worked
        $this->validateHoursWorked($hoursWorked);

        // Check if time log already exists for this date
        // Używamy whereBetween z startOfDay/endOfDay zamiast whereDate (problemy z timezone)
        $dayStart = $workDate->copy()->startOfDay();
        $dayEnd = $workDate->copy()->endOfDay();
        $existingLog = TimeLog::where('project_assignment_id', $assignment->id)
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->first();

        if ($existingLog) {
            throw ValidationException::withMessages([
                'work_date' => 'Dla tego przypisania już istnieje wpis czasu pracy na dzień '.$workDate->format('Y-m-d').'.',
            ]);
        }

        // Create time log
        return TimeLog::create([
            'project_assignment_id' => $assignment->id,
            'start_time' => $workDate->copy()->setTime(8, 0), // Default start time
            'end_time' => $workDate->copy()->setTime(8, 0)->addHours($hoursWorked),
            'hours_worked' => $hoursWorked,
            'notes' => $notes,
        ]);
    }

    /**
     * Update an existing time log.
     */
    public function updateTimeLog(
        TimeLog $timeLog,
        Carbon $workDate,
        float $hoursWorked,
        ?string $notes = null
    ): bool {
        // OPTIMIZATION: Eager load assignment to avoid lazy loading
        if (! $timeLog->relationLoaded('projectAssignment')) {
            $timeLog->load('projectAssignment');
        }
        $assignment = $timeLog->projectAssignment;

        return $this->updateTimeLogWithAssignment($timeLog, $assignment, $workDate, $hoursWorked, $notes);
    }

    /**
     * Update an existing time log with a specific assignment.
     * Used in bulk updates to ensure we validate against the correct assignment.
     */
    protected function updateTimeLogWithAssignment(
        TimeLog $timeLog,
        ProjectAssignment $assignment,
        Carbon $workDate,
        float $hoursWorked,
        ?string $notes = null
    ): bool {
        $workDate = $workDate->copy()->startOfDay();

        // Assignment jest już świeży z bulkUpdateTimeLogs (findOrFail)
        // Nie trzeba refresh() - może powodować problemy z cast 'date'

        // Validate work date is within assignment period
        $this->validateWorkDateWithinAssignment($assignment, $workDate);

        // Validate hours worked
        $this->validateHoursWorked($hoursWorked);

        // Check if another time log exists for this date (excluding current)
        // Używamy whereBetween z startOfDay/endOfDay zamiast whereDate (problemy z timezone)
        $dayStart = $workDate->copy()->startOfDay();
        $dayEnd = $workDate->copy()->endOfDay();
        $existingLog = TimeLog::where('project_assignment_id', $timeLog->project_assignment_id)
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->where('id', '!=', $timeLog->id)
            ->first();

        if ($existingLog) {
            throw ValidationException::withMessages([
                'work_date' => 'Dla tego przypisania już istnieje wpis czasu pracy na dzień '.$workDate->format('Y-m-d').'.',
            ]);
        }

        return $timeLog->update([
            'start_time' => $workDate->copy()->setTime(8, 0),
            'end_time' => $workDate->copy()->setTime(8, 0)->addHours($hoursWorked),
            'hours_worked' => $hoursWorked,
            'notes' => $notes,
        ]);
    }

    /**
     * Get total hours worked for a project assignment.
     */
    public function getTotalHoursForAssignment(ProjectAssignment $assignment): float
    {
        return TimeLog::where('project_assignment_id', $assignment->id)
            ->sum('hours_worked');
    }

    /**
     * Get total hours worked for a project.
     */
    public function getTotalHoursForProject(int $projectId): float
    {
        return TimeLog::whereHas('projectAssignment', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })
            ->sum('hours_worked');
    }

    /**
     * Get total hours worked for an employee in a date range.
     */
    public function getTotalHoursForEmployee(int $employeeId, Carbon $startDate, Carbon $endDate): float
    {
        // Używamy endOfDay() dla endDate, aby uwzględnić cały ostatni dzień
        return TimeLog::whereHas('projectAssignment', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->whereBetween('start_time', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->sum('hours_worked');
    }

    /**
     * Validate hours worked is in valid range (0-24).
     *
     * @throws ValidationException
     */
    protected function validateHoursWorked(float $hoursWorked): void
    {
        if ($hoursWorked < 0 || $hoursWorked > 24) {
            throw ValidationException::withMessages([
                'hours_worked' => 'Liczba godzin musi być między 0 a 24.',
            ]);
        }
    }

    /**
     * Validate work date is within assignment period.
     *
     * @throws ValidationException
     *
     * WYŁĄCZONE - wykomentowane na prośbę użytkownika
     */
    protected function validateWorkDateWithinAssignment(ProjectAssignment $assignment, Carbon $workDate): void
    {
        // WALIDACJA WYŁĄCZONA - metoda nie wykonuje żadnych sprawdzeń

        /*
        // start_date i end_date są już obiektami Carbon (cast 'date')
        // Upewniamy się, że wszystkie daty są w tym samym formacie (bez czasu)
        $startDate = $assignment->start_date instanceof Carbon
            ? $assignment->start_date->copy()->startOfDay()
            : Carbon::parse($assignment->start_date)->startOfDay();
        $endDate = $assignment->end_date
            ? ($assignment->end_date instanceof Carbon
                ? $assignment->end_date->copy()->startOfDay()
                : Carbon::parse($assignment->end_date)->startOfDay())
            : null;
        $workDateDay = $workDate->copy()->startOfDay();

        // Porównujemy tylko daty (bez czasu) jako stringi
        $startDateOnly = $startDate->format('Y-m-d');
        $endDateOnly = $endDate ? $endDate->format('Y-m-d') : null;
        $workDateOnly = $workDateDay->format('Y-m-d');

        if ($workDateOnly < $startDateOnly) {
            $startDateStr = $assignment->start_date instanceof Carbon
                ? $assignment->start_date->format('Y-m-d')
                : Carbon::parse($assignment->start_date)->format('Y-m-d');
            throw ValidationException::withMessages([
                'work_date' => 'Data pracy (' . $workDate->format('Y-m-d') . ') nie może być wcześniejsza niż data rozpoczęcia przypisania (' . $startDateStr . ').'
            ]);
        }

        // Data może być równa end_date (ostatni dzień przypisania jest dozwolony)
        // Porównujemy stringi dat (Y-m-d) zamiast obiektów Carbon
        if ($endDateOnly && $workDateOnly > $endDateOnly) {
            $endDateStr = $assignment->end_date instanceof Carbon
                ? $assignment->end_date->format('Y-m-d')
                : Carbon::parse($assignment->end_date)->format('Y-m-d');
            $startDateStr = $assignment->start_date instanceof Carbon
                ? $assignment->start_date->format('Y-m-d')
                : Carbon::parse($assignment->start_date)->format('Y-m-d');
            throw ValidationException::withMessages([
                'work_date' => 'Data pracy (' . $workDate->format('Y-m-d') . ') nie może być późniejsza niż data zakończenia przypisania (' . $endDateStr . '). Przypisanie: ID ' . $assignment->id . ', okres: ' . $startDateStr . ' - ' . $endDateStr . '.'
            ]);
        }
        */
    }

    /**
     * Get monthly grid data for time logs editing.
     *
     * @param  string  $month  Format: Y-m (e.g., '2025-01')
     * @param  array|null  $projectIds  Optional: filter by project IDs (for /mine/* routes)
     */
    public function getMonthlyGridData(string $month, ?array $projectIds = null): array
    {
        $currentDate = Carbon::parse($month.'-01');
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Navigation
        $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');

        // Constraint: tylko przypisania, które nakładają się na bieżący miesiąc.
        $assignmentInMonth = function ($query) use ($monthStart, $monthEnd) {
            $query->where('start_date', '<=', $monthEnd)
                ->where(function ($q2) use ($monthStart) {
                    $q2->whereNull('end_date')
                        ->orWhere('end_date', '>=', $monthStart);
                });
        };

        // Get all projects with their assignments.
        // Eager-loaded `assignments` ograniczamy do tych nakładających się na miesiąc,
        // żeby listować tylko osoby z przypisaniem na jakikolwiek dzień w tym miesiącu.
        $projectsQuery = Project::with([
            'assignments' => $assignmentInMonth,
            'assignments.employee',
            'assignments.role',
            'assignments.timeLogs' => function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('start_time', [$monthStart, $monthEnd->endOfDay()]);
            },
        ])
            ->whereHas('assignments', $assignmentInMonth);

        // Filter by project IDs if provided (for /mine/* routes)
        if ($projectIds !== null && ! empty($projectIds)) {
            $projectsQuery->whereIn('id', $projectIds);
        }

        $projects = $projectsQuery->orderBy('name')->get();

        // OPTIMIZATION: Get all time logs for this month in one query (even if assignment was deleted)
        // Use join instead of whereHas for better performance
        $timeLogsQuery = TimeLog::whereBetween('start_time', [$monthStart, $monthEnd->endOfDay()])
            ->join('project_assignments', 'time_logs.project_assignment_id', '=', 'project_assignments.id')
            ->with(['projectAssignment.project', 'projectAssignment.employee'])
            ->select('time_logs.*');

        // Filter by project IDs if provided (for /mine/* routes)
        if ($projectIds !== null && ! empty($projectIds)) {
            $timeLogsQuery->whereIn('project_assignments.project_id', $projectIds);
        }

        $allTimeLogs = $timeLogsQuery->get();

        // Create map of time logs by project_id, employee_id and day
        $timeLogsByProjectEmployee = [];
        foreach ($allTimeLogs as $timeLog) {
            if ($timeLog->projectAssignment) {
                $projectId = $timeLog->projectAssignment->project_id;
                $employeeId = $timeLog->projectAssignment->employee_id;
                $assignmentId = $timeLog->project_assignment_id;
                $day = Carbon::parse($timeLog->start_time)->day;

                $key = $projectId.'_'.$employeeId;
                if (! isset($timeLogsByProjectEmployee[$key])) {
                    $timeLogsByProjectEmployee[$key] = [];
                }
                $timeLogsByProjectEmployee[$key][$day] = [
                    'hours' => $timeLog->hours_worked,
                    'client_hours' => $timeLog->client_hours,
                    'settled_hours' => $timeLog->settled_hours,
                    'mismatch' => $timeLog->hasClientMismatch(),
                    'time_log_id' => $timeLog->id,
                    'assignment_id' => $assignmentId,
                ];
            }
        }

        // Prepare data structure for view
        $projectsData = [];
        foreach ($projects as $project) {
            $assignmentsData = [];

            // Group assignments by employee
            $employeesMap = [];
            foreach ($project->assignments as $assignment) {
                $employeeId = $assignment->employee_id;
                if (! isset($employeesMap[$employeeId])) {
                    $employeesMap[$employeeId] = [
                        'employee' => $assignment->employee,
                        'assignments' => [],
                    ];
                }
                $employeesMap[$employeeId]['assignments'][] = $assignment;
            }

            // Convert to array and prepare time logs data
            foreach ($employeesMap as $employeeId => $data) {
                $timeLogsMap = [];

                // Get time logs for this employee in this project
                $key = $project->id.'_'.$employeeId;
                if (isset($timeLogsByProjectEmployee[$key])) {
                    foreach ($timeLogsByProjectEmployee[$key] as $day => $timeLogData) {
                        $timeLogsMap[$day] = $timeLogData;
                    }
                }

                // Check which days are within assignment period
                $daysInAssignment = [];
                foreach ($data['assignments'] as $assignment) {
                    $assignmentStart = Carbon::parse($assignment->start_date);
                    $assignmentEnd = $assignment->end_date ? Carbon::parse($assignment->end_date) : $monthEnd;

                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $checkDate = $monthStart->copy()->addDays($day - 1);
                        if ($checkDate->between($assignmentStart, $assignmentEnd)) {
                            $daysInAssignment[$day] = true;
                        }
                    }
                }

                $assignmentsData[] = [
                    'employee' => $data['employee'],
                    'assignments' => $data['assignments'],
                    'timeLogs' => $timeLogsMap,
                    'daysInAssignment' => $daysInAssignment,
                ];
            }

            usort($assignmentsData, function (array $a, array $b): int {
                $la = mb_strtolower($a['employee']?->last_name ?? '');
                $lb = mb_strtolower($b['employee']?->last_name ?? '');
                if ($la !== $lb) {
                    return $la <=> $lb;
                }

                $fa = mb_strtolower($a['employee']?->first_name ?? '');
                $fb = mb_strtolower($b['employee']?->first_name ?? '');

                return $fa <=> $fb;
            });

            if (! empty($assignmentsData)) {
                $projectsData[] = [
                    'project' => $project,
                    'assignments' => $assignmentsData,
                ];
            }
        }

        // Generate days array
        // Upewniamy się, że wszystkie daty są w startOfDay() dla stabilności
        $days = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $monthStart->copy()->addDays($day - 1)->startOfDay();
            $days[] = [
                'number' => $day,
                'date' => $date,
                'isWeekend' => $date->isWeekend(),
            ];
        }

        return [
            'projectsData' => $projectsData,
            'days' => $days,
            'currentDate' => $currentDate,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
        ];
    }

    /**
     * Skopiuj hours_worked → client_hours tam, gdzie klient jeszcze nie ma warstwy.
     * Wywoływane przy wejściu w tryb „wersja klienta” w monthly-grid.
     *
     * @param  array<int>|null  $projectIds
     */
    public function seedClientHoursFromInternal(string $month, ?array $projectIds = null): int
    {
        $currentDate = Carbon::parse($month.'-01');
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth()->endOfDay();

        $query = TimeLog::query()
            ->whereNull('client_hours')
            ->whereNotNull('hours_worked')
            ->whereBetween('start_time', [$monthStart, $monthEnd]);

        if ($projectIds !== null && $projectIds !== []) {
            $query->whereHas('projectAssignment', function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds);
            });
        }

        // Jedno UPDATE z kopiowaniem kolumny — bez N× save.
        return $query->update([
            'client_hours' => DB::raw('hours_worked'),
        ]);
    }

    /**
     * Lista rozbieżności nasze ↔ klient w miesiącu (do ustalenia konsensusu).
     *
     * @param  array<int>|null  $projectIds
     * @return list<array{
     *     time_log_id: int,
     *     assignment_id: int,
     *     project_id: int,
     *     project_name: string,
     *     employee_id: int,
     *     employee_name: string,
     *     date: string,
     *     day_label: string,
     *     hours_worked: float,
     *     client_hours: float,
     *     settled_hours: float|null
     * }>
     */
    public function getHourDiscrepancies(string $month, ?array $projectIds = null): array
    {
        $currentDate = Carbon::parse($month.'-01');
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth()->endOfDay();

        $query = TimeLog::query()
            ->with(['projectAssignment.project', 'projectAssignment.employee'])
            ->whereNotNull('client_hours')
            ->whereBetween('start_time', [$monthStart, $monthEnd])
            ->whereRaw('ABS(COALESCE(hours_worked, 0) - client_hours) > 0.001')
            ->orderBy('start_time');

        if ($projectIds !== null && $projectIds !== []) {
            $query->whereHas('projectAssignment', function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds);
            });
        }

        return $query->get()
            ->map(function (TimeLog $log) {
                $assignment = $log->projectAssignment;
                $employee = $assignment?->employee;
                $project = $assignment?->project;
                $date = $log->start_time->copy()->startOfDay();

                return [
                    'time_log_id' => $log->id,
                    'assignment_id' => (int) $log->project_assignment_id,
                    'project_id' => (int) ($assignment?->project_id ?? 0),
                    'project_name' => $project?->name ?? '—',
                    'employee_id' => (int) ($assignment?->employee_id ?? 0),
                    'employee_name' => $employee
                        ? trim($employee->last_name.', '.$employee->first_name)
                        : '—',
                    'date' => $date->toDateString(),
                    'day_label' => $date->format('d.m.Y'),
                    'hours_worked' => (float) ($log->hours_worked ?? 0),
                    'client_hours' => (float) $log->client_hours,
                    'settled_hours' => $log->settled_hours !== null ? (float) $log->settled_hours : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Bulk update time logs.
     *
     * @param  array  $entries  [
     *                          [
     *                          'assignment_id' => int,
     *                          'date' => string (Y-m-d),
     *                          'hours' => float|null
     *                          ],
     *                          ...
     *                          ]
     * @param  string  $layer  internal|client|settled
     * @return array ['created' => int, 'updated' => int, 'deleted' => int, 'errors' => array]
     */
    public function bulkUpdateTimeLogs(array $entries, string $layer = 'internal'): array
    {
        $layer = match ($layer) {
            'client' => 'client',
            'settled' => 'settled',
            default => 'internal',
        };
        $results = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => [],
        ];

        if (empty($entries)) {
            return $results;
        }

        // OPTIMIZATION: Load all assignments at once instead of N queries
        $assignmentIds = array_unique(array_filter(array_column($entries, 'assignment_id')));
        $assignments = ProjectAssignment::whereIn('id', $assignmentIds)
            ->get()
            ->keyBy('id');

        // OPTIMIZATION: Load all existing time logs at once
        // Build date ranges for all entries
        $dateRanges = [];
        foreach ($entries as $entry) {
            if (! isset($entry['assignment_id']) || ! isset($entry['date'])) {
                continue;
            }
            $assignmentId = (int) $entry['assignment_id'];
            $date = Carbon::parse($entry['date'])->startOfDay();
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            if (! isset($dateRanges[$assignmentId])) {
                $dateRanges[$assignmentId] = [];
            }
            $dateRanges[$assignmentId][] = [$dayStart, $dayEnd];
        }

        // Load all time logs for all assignments and dates in one query
        $existingTimeLogs = collect();
        if (! empty($dateRanges)) {
            $timeLogsQuery = TimeLog::whereIn('project_assignment_id', array_keys($dateRanges));

            // Build OR conditions for all date ranges
            $timeLogsQuery->where(function ($query) use ($dateRanges) {
                foreach ($dateRanges as $assignmentId => $ranges) {
                    foreach ($ranges as $range) {
                        $query->orWhere(function ($q) use ($assignmentId, $range) {
                            $q->where('project_assignment_id', $assignmentId)
                                ->whereBetween('start_time', $range);
                        });
                    }
                }
            });

            $existingTimeLogs = $timeLogsQuery->get()->keyBy(function ($timeLog) {
                return $timeLog->project_assignment_id.'_'.$timeLog->start_time->format('Y-m-d');
            });
        }

        // Process entries
        foreach ($entries as $index => $entry) {
            try {
                if (! isset($entry['assignment_id']) || ! isset($entry['date'])) {
                    continue;
                }

                $assignmentId = (int) $entry['assignment_id'];

                // Get assignment from pre-loaded collection
                if (! isset($assignments[$assignmentId])) {
                    $results['errors'][] = [
                        'assignment_id' => $assignmentId,
                        'date' => $entry['date'] ?? null,
                        'message' => 'Przypisanie nie zostało znalezione.',
                    ];

                    continue;
                }

                $assignment = $assignments[$assignmentId];

                // Parse date
                $date = Carbon::parse($entry['date'])->startOfDay();
                $hours = isset($entry['hours']) && $entry['hours'] !== '' && $entry['hours'] !== null ? (float) $entry['hours'] : 0;

                // Find existing time log from pre-loaded collection
                $timeLogKey = $assignmentId.'_'.$date->format('Y-m-d');
                $timeLog = $existingTimeLogs->get($timeLogKey);

                if ($layer === 'client') {
                    $this->applyClientLayerHours($assignment, $date, $hours, $timeLog, $existingTimeLogs, $timeLogKey, $results);
                } elseif ($layer === 'settled') {
                    $this->applySettledLayerHours($hours, $timeLog, $results, $entry);
                } elseif ($hours > 0) {
                    if ($timeLog) {
                        $this->updateTimeLogWithAssignment($timeLog, $assignment, $date, $hours);
                        $results['updated']++;
                    } else {
                        $this->createTimeLog($assignment, $date, $hours);
                        $results['created']++;
                    }
                } elseif ($timeLog) {
                    // Puste wewnętrzne: nie kasuj wiersza, jeśli klient już coś ma.
                    if ($timeLog->client_hours !== null) {
                        $timeLog->hours_worked = 0;
                        $timeLog->end_time = $date->copy()->setTime(8, 0);
                        $timeLog->save();
                        $results['updated']++;
                    } else {
                        $timeLog->delete();
                        $existingTimeLogs->forget($timeLogKey);
                        $results['deleted']++;
                    }
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errorMsg = implode(', ', array_merge(...array_values($e->errors())));
                $results['errors'][] = [
                    'assignment_id' => $entry['assignment_id'] ?? null,
                    'date' => $entry['date'] ?? null,
                    'message' => $errorMsg,
                ];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'assignment_id' => $entry['assignment_id'] ?? null,
                    'date' => $entry['date'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, TimeLog>  $existingTimeLogs
     * @param  array{created: int, updated: int, deleted: int, errors: array}  $results
     */
    private function applyClientLayerHours(
        ProjectAssignment $assignment,
        Carbon $date,
        float $hours,
        ?TimeLog $timeLog,
        $existingTimeLogs,
        string $timeLogKey,
        array &$results
    ): void {
        if ($timeLog) {
            $timeLog->client_hours = $hours;
            $timeLog->save();
            $results['updated']++;

            return;
        }

        if ($hours <= 0) {
            return;
        }

        // Klient zgłasza godziny, których jeszcze nie mamy — utwórz wiersz z 0 wewnętrznych.
        $created = $this->createTimeLog($assignment, $date, 0);
        $created->client_hours = $hours;
        $created->save();
        $existingTimeLogs->put($timeLogKey, $created);
        $results['created']++;
    }

    /**
     * @param  array{created: int, updated: int, deleted: int, errors: array}  $results
     * @param  array<string, mixed>  $entry
     */
    private function applySettledLayerHours(float $hours, ?TimeLog $timeLog, array &$results, array $entry): void
    {
        if (! $timeLog) {
            $results['errors'][] = [
                'assignment_id' => $entry['assignment_id'] ?? null,
                'date' => $entry['date'] ?? null,
                'message' => 'Brak wpisu czasu — najpierw zapisz godziny nasze lub klienta.',
            ];

            return;
        }

        // Puste pole = wyczyść konsensus (wraca do „do ustalenia”).
        $timeLog->settled_hours = $hours > 0 ? $hours : null;
        $timeLog->save();
        $results['updated']++;
    }
}
