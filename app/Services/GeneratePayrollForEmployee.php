<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\TimeLog;
use App\Models\EmployeeRate;
use App\Enums\PayrollStatus;
use App\Enums\AssignmentStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for generating payroll snapshots for employees.
 * 
 * Generates immutable payroll records based on TimeLogs and EmployeeRates
 * for a specific period. The payroll is a snapshot and should not be recalculated
 * after creation (only adjustments can be added).
 */
class GeneratePayrollForEmployee
{
    /**
     * Generate payroll for an employee in a given period.
     * 
     * @param int $employeeId
     * @param string|Carbon $periodStart Date in Y-m-d format or Carbon instance
     * @param string|Carbon $periodEnd Date in Y-m-d format or Carbon instance
     * @param string $currency Currency code (default: PLN)
     * @param string|null $notes Optional notes
     * @return Payroll
     * @throws \Exception If employee not found or period is invalid
     */
    public function generate(
        int $employeeId,
        string|Carbon $periodStart,
        string|Carbon $periodEnd,
        string $currency = 'PLN',
        ?string $notes = null
    ): Payroll {
        $employee = Employee::findOrFail($employeeId);
        
        $periodStart = $periodStart instanceof Carbon ? $periodStart : Carbon::parse($periodStart);
        $periodEnd = $periodEnd instanceof Carbon ? $periodEnd : Carbon::parse($periodEnd);
        
        // Validate period
        if ($periodStart->gt($periodEnd)) {
            throw new \InvalidArgumentException('Data rozpoczęcia okresu nie może być późniejsza niż data zakończenia.');
        }

        // Check if payroll already exists for this period
        $existingPayroll = Payroll::where('employee_id', $employeeId)
            ->where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if ($existingPayroll) {
            throw new \Exception('Payroll dla tego pracownika i okresu już istnieje.');
        }

        // Get all TimeLogs for this employee in the period
        $timeLogs = $this->getTimeLogsForPeriod($employeeId, $periodStart, $periodEnd);

        // Calculate hours_amount based on TimeLogs and EmployeeRates
        $hoursAmount = $this->calculateHoursAmount($timeLogs, $currency);

        // Create payroll snapshot
        $payroll = Payroll::create([
            'employee_id' => $employeeId,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'hours_amount' => $hoursAmount,
            'adjustments_amount' => 0,
            'total_amount' => $hoursAmount,
            'currency' => $currency,
            'status' => PayrollStatus::DRAFT,
            'notes' => $notes,
        ]);

        return $payroll;
    }

    /**
     * Get all TimeLogs for an employee in the given period.
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getTimeLogsForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd)
    {
        return TimeLog::whereHas('projectAssignment', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
        ->whereBetween(DB::raw('DATE(start_time)'), [
            $periodStart->toDateString(),
            $periodEnd->toDateString()
        ])
        ->with('projectAssignment.employee')
        ->get();
    }

    /**
     * Calculate hours_amount based on TimeLogs and EmployeeRates.
     * 
     * For each TimeLog:
     * - Gets the work date
     * - Finds the active EmployeeRate for that date
     * - Calculates: hours_worked * rate.amount
     * - Sums all amounts
     * 
     * @param \Illuminate\Database\Eloquent\Collection $timeLogs
     * @param string $currency
     * @return float
     */
    protected function calculateHoursAmount($timeLogs, string $currency): float
    {
        $totalAmount = 0;

        foreach ($timeLogs as $timeLog) {
            $workDate = Carbon::parse($timeLog->start_time)->toDateString();
            $hoursWorked = (float) $timeLog->hours_worked;

            if ($hoursWorked <= 0) {
                continue; // Skip logs with no hours
            }

            // Find active EmployeeRate for this date
            $rate = $this->findEmployeeRateForDate(
                $timeLog->projectAssignment->employee_id,
                $workDate,
                $currency
            );

            if (!$rate) {
                // If no rate found, skip this log (or throw exception?)
                // For now, we'll skip it and continue
                continue;
            }

            // Calculate amount for this log: hours * rate
            $logAmount = $hoursWorked * (float) $rate->amount;
            $totalAmount += $logAmount;
        }

        return round($totalAmount, 2);
    }

    /**
     * Find active EmployeeRate for a specific date and currency.
     * 
     * Returns the rate that:
     * - Is active (status = 'active')
     * - Has matching currency
     * - Covers the given date (start_date <= date AND (end_date >= date OR end_date IS NULL))
     * 
     * If multiple rates match, returns the most recent one (by start_date DESC).
     * 
     * @param int $employeeId
     * @param string $date Date in Y-m-d format
     * @param string $currency
     * @return EmployeeRate|null
     */
    protected function findEmployeeRateForDate(int $employeeId, string $date, string $currency): ?EmployeeRate
    {
        return EmployeeRate::where('employee_id', $employeeId)
            ->where('currency', $currency)
            ->where('status', AssignmentStatus::ACTIVE)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
    }
}
