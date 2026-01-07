<?php

namespace App\Services;

use App\Models\TimeLog;
use App\Models\ProjectAssignment;
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
     * @param array $data [
     *   'work_date' => string (Y-m-d),
     *   'hours_worked' => float,
     *   'notes' => string|null
     * ]
     * @return TimeLog
     * @throws ValidationException
     */
    public function createTimeLog(ProjectAssignment $assignment, array $data): TimeLog
    {
        $workDate = Carbon::parse($data['work_date']);
        $hoursWorked = $data['hours_worked'];

        // Validate work date is within assignment period
        $this->validateWorkDateWithinAssignment($assignment, $workDate);

        // Validate hours worked
        $this->validateHoursWorked($hoursWorked);

        // Check if time log already exists for this date
        $existingLog = TimeLog::where('project_assignment_id', $assignment->id)
            ->whereDate('start_time', $workDate)
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
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update an existing time log.
     */
    public function updateTimeLog(TimeLog $timeLog, array $data): bool
    {
        $workDate = Carbon::parse($data['work_date']);
        $hoursWorked = $data['hours_worked'];

        // Validate work date is within assignment period
        $this->validateWorkDateWithinAssignment($timeLog->projectAssignment, $workDate);

        // Validate hours worked
        $this->validateHoursWorked($hoursWorked);

        // Check if another time log exists for this date (excluding current)
        $existingLog = TimeLog::where('project_assignment_id', $timeLog->project_assignment_id)
            ->whereDate('start_time', $workDate)
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
            'notes' => $data['notes'] ?? null,
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
        return TimeLog::whereHas('projectAssignment', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
        ->whereBetween('start_time', [$startDate, $endDate])
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
     */
    protected function validateWorkDateWithinAssignment(ProjectAssignment $assignment, Carbon $workDate): void
    {
        $startDate = Carbon::parse($assignment->start_date);
        $endDate = $assignment->end_date ? Carbon::parse($assignment->end_date) : null;

        if ($workDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'work_date' => 'Data pracy nie może być wcześniejsza niż data rozpoczęcia przypisania (' . $startDate->format('Y-m-d') . ').'
            ]);
        }

        if ($endDate && $workDate->gt($endDate)) {
            throw ValidationException::withMessages([
                'work_date' => 'Data pracy nie może być późniejsza niż data zakończenia przypisania (' . $endDate->format('Y-m-d') . ').'
            ]);
        }
    }
}
