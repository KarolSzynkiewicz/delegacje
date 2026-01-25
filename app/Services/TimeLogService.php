<?php

namespace App\Services;

use App\Models\TimeLog;
use App\Models\ProjectAssignment;
use App\Models\Project;
use App\Enums\AssignmentStatus;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Service for managing time logs (real work hours tracking).
 */
class TimeLogService
{
    /**
     * Create a time log entry for a project assignment.
     * 
     * @param ProjectAssignment $assignment
     * @param Carbon $workDate
     * @param float $hoursWorked
     * @param string|null $notes
     * @return TimeLog
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
        // WYŁĄCZONE - wykomentowane na prośbę użytkownika
        // $this->validateWorkDateWithinAssignment($assignment, $workDate);
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
                'work_date' => 'Dla tego przypisania już istnieje wpis czasu pracy na dzień ' . $workDate->format('Y-m-d') . '.'
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
        // WYŁĄCZONE - wykomentowane na prośbę użytkownika
        // $this->validateWorkDateWithinAssignment($assignment, $workDate);

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
                'work_date' => 'Dla tego przypisania już istnieje wpis czasu pracy na dzień ' . $workDate->format('Y-m-d') . '.'
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
            $endDate->copy()->endOfDay()
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
                'hours_worked' => 'Liczba godzin musi być między 0 a 24.'
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
        return;
        
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
     * @param string $month Format: Y-m (e.g., '2025-01')
     * @return array
     */
    public function getMonthlyGridData(string $month): array
    {
        $currentDate = Carbon::parse($month . '-01');
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Navigation
        $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');

        // Get all projects with their assignments
        $projects = Project::with([
            'assignments.employee',
            'assignments.role',
            'assignments.timeLogs' => function($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('start_time', [$monthStart, $monthEnd->endOfDay()]);
            }
        ])
        ->whereHas('assignments', function($query) use ($monthStart, $monthEnd) {
            $query->where(function($q) use ($monthStart, $monthEnd) {
                $q->where('start_date', '<=', $monthEnd)
                  ->where(function($q2) use ($monthStart) {
                      $q2->whereNull('end_date')
                         ->orWhere('end_date', '>=', $monthStart);
                  });
            })
            ->where('is_cancelled', false);
        })
        ->orderBy('name')
        ->get();
        
        // Get all time logs for this month (even if assignment was deleted)
        $allTimeLogs = TimeLog::whereBetween('start_time', [$monthStart, $monthEnd->endOfDay()])
            ->with(['projectAssignment.project', 'projectAssignment.employee'])
            ->get();
        
        // Create map of time logs by project_id, employee_id and day
        $timeLogsByProjectEmployee = [];
        foreach ($allTimeLogs as $timeLog) {
            if ($timeLog->projectAssignment) {
                $projectId = $timeLog->projectAssignment->project_id;
                $employeeId = $timeLog->projectAssignment->employee_id;
                $assignmentId = $timeLog->project_assignment_id;
                $day = Carbon::parse($timeLog->start_time)->day;
                
                $key = $projectId . '_' . $employeeId;
                if (!isset($timeLogsByProjectEmployee[$key])) {
                    $timeLogsByProjectEmployee[$key] = [];
                }
                $timeLogsByProjectEmployee[$key][$day] = [
                    'hours' => $timeLog->hours_worked,
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
                // Exclude cancelled assignments
                if ($assignment->is_cancelled) {
                    continue;
                }
                
                $employeeId = $assignment->employee_id;
                if (!isset($employeesMap[$employeeId])) {
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
                $key = $project->id . '_' . $employeeId;
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

            if (!empty($assignmentsData)) {
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
     * Bulk update time logs.
     * 
     * @param array $entries [
     *   [
     *     'assignment_id' => int,
     *     'date' => string (Y-m-d),
     *     'hours' => float|null
     *   ],
     *   ...
     * ]
     * @return array ['created' => int, 'updated' => int, 'deleted' => int, 'errors' => array]
     */
    public function bulkUpdateTimeLogs(array $entries): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => [],
        ];

        foreach ($entries as $index => $entry) {
            try {
                $assignmentId = (int)$entry['assignment_id'];
                $assignment = ProjectAssignment::findOrFail($assignmentId);
                // Parsuj datę i ustaw na startOfDay() od razu - zapewnia stabilność
                $date = Carbon::parse($entry['date'])->startOfDay();
                $hours = isset($entry['hours']) && $entry['hours'] !== '' && $entry['hours'] !== null ? (float)$entry['hours'] : 0;

                // Find existing time log
                // Używamy whereBetween z startOfDay/endOfDay zamiast whereDate (problemy z timezone)
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();
                $timeLog = TimeLog::where('project_assignment_id', $assignment->id)
                    ->whereBetween('start_time', [$dayStart, $dayEnd])
                    ->first();

                if ($hours > 0) {
                    if ($timeLog) {
                        // Update existing - use the assignment from bulkUpdate, not from timeLog
                        // This ensures we validate against the correct assignment
                        $this->updateTimeLogWithAssignment($timeLog, $assignment, $date, $hours);
                        $results['updated']++;
                    } else {
                        // Create new
                        $this->createTimeLog($assignment, $date, $hours);
                        $results['created']++;
                    }
                } else {
                    // Delete if hours is 0 or empty
                    if ($timeLog) {
                        $timeLog->delete();
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
}
