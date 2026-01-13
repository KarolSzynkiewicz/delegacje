<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\TimeLog;
use App\Models\EmployeeRate;
use App\Models\Adjustment;
use App\Models\Advance;
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
        if (Payroll::existsForPeriod($employeeId, $periodStart, $periodEnd)) {
            throw new \Exception('Payroll dla tego pracownika i okresu już istnieje.');
        }

        // Get all TimeLogs for this employee in the period
        $timeLogs = $this->getTimeLogsForPeriod($employeeId, $periodStart, $periodEnd);

        // Determine currency from employee rates (if available) or use provided currency
        $actualCurrency = $this->determineCurrencyForPeriod($employeeId, $periodStart, $periodEnd, $currency);

        // Calculate hours_amount based on TimeLogs and EmployeeRates
        $hoursAmount = $this->calculateHoursAmount($timeLogs, $actualCurrency);

        // Calculate adjustments_amount (adjustments + advances)
        $adjustmentsAmount = $this->calculateAdjustmentsAmount($employeeId, $periodStart, $periodEnd, $actualCurrency);

        // Create payroll snapshot
        $payroll = Payroll::create([
            'employee_id' => $employeeId,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'hours_amount' => $hoursAmount,
            'adjustments_amount' => $adjustmentsAmount,
            'total_amount' => $hoursAmount + $adjustmentsAmount,
            'currency' => $actualCurrency,
            'status' => PayrollStatus::ISSUED,
            'notes' => $notes,
        ]);

        // Link adjustments and advances to this payroll
        $this->linkAdjustmentsAndAdvancesToPayroll($employeeId, $periodStart, $periodEnd, $payroll);

        return $payroll;
    }

    /**
     * Get all employee IDs who have TimeLogs in the given period.
     * 
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return \Illuminate\Support\Collection
     */
    public function getEmployeeIdsWithTimeLogsInPeriod(Carbon $periodStart, Carbon $periodEnd)
    {
        return TimeLog::whereHas('projectAssignment')
            ->whereBetween(DB::raw('DATE(start_time)'), [
                $periodStart->toDateString(),
                $periodEnd->toDateString()
            ])
            ->with('projectAssignment.employee')
            ->get()
            ->pluck('projectAssignment.employee_id')
            ->unique()
            ->filter()
            ->values();
    }

    /**
     * Get all TimeLogs for an employee in the given period.
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTimeLogsForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd)
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
    public function calculateHoursAmount($timeLogs, string $currency): float
    {
        $totalAmount = 0;

        foreach ($timeLogs as $timeLog) {
            $workDate = Carbon::parse($timeLog->start_time)->toDateString();
            $hoursWorked = (float) $timeLog->hours_worked;

            if ($hoursWorked <= 0) {
                continue; // Skip logs with no hours
            }

            // Find active EmployeeRate for this date - first try requested currency
            $rate = $this->findEmployeeRateForDate(
                $timeLog->projectAssignment->employee_id,
                $workDate,
                $currency
            );

            // If no rate found in requested currency, try to find any active rate for this date
            if (!$rate) {
                $rate = $this->findAnyEmployeeRateForDate(
                    $timeLog->projectAssignment->employee_id,
                    $workDate
                );
            }

            if (!$rate) {
                // If no rate found at all, skip this log
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
    public function findEmployeeRateForDate(int $employeeId, string $date, string $currency): ?EmployeeRate
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

    /**
     * Find any active EmployeeRate for a specific date (any currency).
     * 
     * Returns the rate that:
     * - Is active (status = 'active')
     * - Covers the given date (start_date <= date AND (end_date >= date OR end_date IS NULL))
     * 
     * If multiple rates match, returns the most recent one (by start_date DESC).
     * 
     * @param int $employeeId
     * @param string $date Date in Y-m-d format
     * @return EmployeeRate|null
     */
    public function findAnyEmployeeRateForDate(int $employeeId, string $date): ?EmployeeRate
    {
        return EmployeeRate::where('employee_id', $employeeId)
            ->where('status', AssignmentStatus::ACTIVE)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Determine the currency to use for payroll based on employee rates in the period.
     * 
     * If employee has rates in the period, use the currency from the first/most common rate.
     * Otherwise, use the provided default currency.
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param string $defaultCurrency
     * @return string
     */
    public function determineCurrencyForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd, string $defaultCurrency): string
    {
        // Find all active rates that overlap with the period
        $rates = EmployeeRate::where('employee_id', $employeeId)
            ->where('status', AssignmentStatus::ACTIVE)
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->where(function ($q) use ($periodStart, $periodEnd) {
                    // Rate starts before or during period
                    $q->where('start_date', '<=', $periodEnd->toDateString())
                      ->where(function ($q2) use ($periodStart) {
                          // Rate ends after or during period, or has no end date
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $periodStart->toDateString());
                      });
                });
            })
            ->get();

        if ($rates->isEmpty()) {
            return $defaultCurrency;
        }

        // Count currencies and return the most common one
        $currencyCounts = $rates->groupBy('currency')->map(function ($group) {
            return $group->count();
        });
        $mostCommonCurrency = $currencyCounts->sortDesc()->keys()->first();

        return $mostCommonCurrency ?? $defaultCurrency;
    }

    /**
     * Calculate adjustments_amount based on adjustments and advances in the period.
     * 
     * Adjustments: sum of all adjustment amounts (can be positive for bonuses or negative for penalties)
     * Advances: sum of advance amounts + interest if applicable
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param string $currency
     * @param Payroll|null $excludePayroll Payroll to exclude from search (for recalculation)
     * @return float
     */
    public function calculateAdjustmentsAmount(int $employeeId, Carbon $periodStart, Carbon $periodEnd, string $currency, ?Payroll $excludePayroll = null): float
    {
        $totalAdjustments = 0;

        // Calculate adjustments contribution
        $adjustments = $this->getAdjustmentsForPeriod($employeeId, $periodStart, $periodEnd, $currency, $excludePayroll);
        foreach ($adjustments as $adjustment) {
            $totalAdjustments += $adjustment->getEffectiveAmount();
        }

        // Calculate advances contribution
        $advances = $this->getAdvancesForPeriod($employeeId, $periodStart, $periodEnd, $currency, $excludePayroll);
        foreach ($advances as $advance) {
            $totalAdjustments -= (float) $advance->amount;
            $totalAdjustments -= $advance->getInterestAmount();
        }

        return round($totalAdjustments, 2);
    }

    /**
     * Get adjustments for a period, trying requested currency first, then any currency.
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param string $currency
     * @param Payroll|null $excludePayroll
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAdjustmentsForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd, string $currency, ?Payroll $excludePayroll = null)
    {
        $query = Adjustment::forEmployee($employeeId)
            ->inDateRange($periodStart->toDateString(), $periodEnd->toDateString());

        if ($excludePayroll) {
            $query->forPayrollRecalculation($excludePayroll);
        } else {
            $query->unlinked();
        }

        // First try requested currency
        $adjustments = (clone $query)->where('currency', $currency)->get();
        
        // If no adjustments in requested currency, get all adjustments (any currency)
        if ($adjustments->isEmpty()) {
            $adjustments = $query->get();
        }

        return $adjustments;
    }

    /**
     * Get advances for a period, trying requested currency first, then any currency.
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param string $currency
     * @param Payroll|null $excludePayroll
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAdvancesForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd, string $currency, ?Payroll $excludePayroll = null)
    {
        $query = Advance::forEmployee($employeeId)
            ->inDateRange($periodStart->toDateString(), $periodEnd->toDateString());

        if ($excludePayroll) {
            $query->forPayrollRecalculation($excludePayroll);
        } else {
            $query->unlinked();
        }

        // First try requested currency
        $advances = (clone $query)->where('currency', $currency)->get();
        
        // If no advances in requested currency, get all advances (any currency)
        if ($advances->isEmpty()) {
            $advances = $query->get();
        }

        return $advances;
    }

    /**
     * Link adjustments and advances to the payroll.
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param Payroll $payroll
     * @return void
     */
    public function linkAdjustmentsAndAdvancesToPayroll(int $employeeId, Carbon $periodStart, Carbon $periodEnd, Payroll $payroll): void
    {
        // Link adjustments (including those that were previously linked to this payroll)
        Adjustment::forEmployee($employeeId)
            ->inDateRange($periodStart->toDateString(), $periodEnd->toDateString())
            ->forPayrollRecalculation($payroll)
            ->update(['payroll_id' => $payroll->id]);

        // Link advances (including those that were previously linked to this payroll)
        Advance::forEmployee($employeeId)
            ->inDateRange($periodStart->toDateString(), $periodEnd->toDateString())
            ->forPayrollRecalculation($payroll)
            ->update(['payroll_id' => $payroll->id]);
    }

    /**
     * Recalculate an existing payroll.
     * 
     * @param Payroll $payroll
     * @return Payroll
     * @throws \Exception If payroll status doesn't allow recalculation
     */
    public function recalculate(Payroll $payroll): Payroll
    {
        // Only allow recalculation of draft/issued payrolls
        if (!$payroll->canBeRecalculated()) {
            throw new \Exception('Można przeliczyć tylko payroll ze statusem "Szkic" lub "Wystawiony".');
        }

        $periodStart = Carbon::parse($payroll->period_start);
        $periodEnd = Carbon::parse($payroll->period_end);

        // Get TimeLogs for this payroll period
        $timeLogs = $this->getTimeLogsForPeriod(
            $payroll->employee_id,
            $periodStart,
            $periodEnd
        );

        // Determine currency from employee rates
        $actualCurrency = $this->determineCurrencyForPeriod(
            $payroll->employee_id,
            $periodStart,
            $periodEnd,
            $payroll->currency
        );

        // Recalculate hours_amount
        $newHoursAmount = $this->calculateHoursAmount($timeLogs, $actualCurrency);

        // Unlink old adjustments and advances from this payroll
        Adjustment::where('payroll_id', $payroll->id)->update(['payroll_id' => null]);
        Advance::where('payroll_id', $payroll->id)->update(['payroll_id' => null]);

        // Recalculate adjustments_amount
        $newAdjustmentsAmount = $this->calculateAdjustmentsAmount(
            $payroll->employee_id,
            $periodStart,
            $periodEnd,
            $actualCurrency,
            $payroll
        );

        // Link adjustments and advances to this payroll
        $this->linkAdjustmentsAndAdvancesToPayroll(
            $payroll->employee_id,
            $periodStart,
            $periodEnd,
            $payroll
        );

        // Update payroll
        $payroll->hours_amount = $newHoursAmount;
        $payroll->adjustments_amount = $newAdjustmentsAmount;
        $payroll->currency = $actualCurrency;
        $payroll->recalculateTotal();
        $payroll->save();

        return $payroll;
    }
}
