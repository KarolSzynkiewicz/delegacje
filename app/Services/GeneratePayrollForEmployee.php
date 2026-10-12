<?php

namespace App\Services;

use App\Enums\PayrollStatus;
use App\Models\Adjustment;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\EmployeeRate;
use App\Models\Payroll;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * @param  string|Carbon  $periodStart  Date in Y-m-d format or Carbon instance
     * @param  string|Carbon  $periodEnd  Date in Y-m-d format or Carbon instance
     * @param  string  $currency  Currency code (default: PLN)
     * @param  string|null  $notes  Optional notes
     *
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

        // Check if payroll already exists for this period (exact match)
        if (Payroll::existsForPeriod($employeeId, $periodStart, $periodEnd)) {
            throw new \Exception('Payroll dla tego pracownika i okresu już istnieje.');
        }

        // Check if payroll overlaps with existing payrolls
        if (Payroll::hasOverlappingPayrolls($employeeId, $periodStart, $periodEnd)) {
            throw new \Exception('Payroll dla tego pracownika nakłada się z istniejącym payroll w tym okresie.');
        }

        // Get all TimeLogs for this employee in the period
        $timeLogs = $this->getTimeLogsForPeriod($employeeId, $periodStart, $periodEnd);

        // Determine currency from employee rates (if available) or use provided currency
        $actualCurrency = $this->determineCurrencyForPeriod($employeeId, $periodStart, $periodEnd, $currency);

        // Calculate hours_amount based on TimeLogs and EmployeeRates
        $hoursAmount = $this->calculateHoursAmount($timeLogs, $actualCurrency);

        // Create payroll snapshot (draft status - placeholder)
        $payroll = Payroll::create([
            'employee_id' => $employeeId,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'hours_amount' => $hoursAmount,
            'adjustments_amount' => 0, // Będzie obliczone z advances/adjustments które mają payroll_id
            'total_amount' => $hoursAmount,
            'currency' => $actualCurrency,
            'status' => PayrollStatus::DRAFT, // Draft - placeholder, user może dodać advances/adjustments
            'notes' => $notes,
        ]);

        // Oblicz adjustments_amount z advances/adjustments które mają payroll_id
        $adjustmentsAmount = $this->calculateAdjustmentsAmountForPayroll($payroll);
        $payroll->adjustments_amount = $adjustmentsAmount;
        $payroll->recalculateTotal();
        $payroll->save();

        return $payroll;
    }

    /**
     * Get all employee IDs who have TimeLogs in the given period.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEmployeeIdsWithTimeLogsInPeriod(Carbon $periodStart, Carbon $periodEnd)
    {
        // Lean query: JOIN + DISTINCT zamiast ładowania pełnych modeli do PHP
        return TimeLog::join('project_assignments', 'time_logs.project_assignment_id', '=', 'project_assignments.id')
            ->whereBetween('time_logs.start_time', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->whereNotNull('project_assignments.employee_id')
            ->distinct()
            ->pluck('project_assignments.employee_id');
    }

    /**
     * Get all TimeLogs for an employee in the given period.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTimeLogsForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd)
    {
        // Używamy whereBetween z startOfDay/endOfDay zamiast DATE() w SQL (problemy z granicami dni)
        // endOfDay() zapewnia, że ostatni dzień jest uwzględniony
        return TimeLog::whereHas('projectAssignment', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->whereBetween('start_time', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
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
     * @param  \Illuminate\Database\Eloquent\Collection  $timeLogs
     */
    public function calculateHoursAmount($timeLogs, string $currency): float
    {
        return $this->sumLoggedHours($timeLogs, $currency, $this->activeRatesForLogs($timeLogs));
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
     * @param  string  $date  Date in Y-m-d format
     */
    public function findEmployeeRateForDate(int $employeeId, string $date, string $currency): ?EmployeeRate
    {
        return EmployeeRate::where('employee_id', $employeeId)
            ->where('currency', $currency)
            ->where('status', 'active')
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
     * @param  string  $date  Date in Y-m-d format
     */
    public function findAnyEmployeeRateForDate(int $employeeId, string $date): ?EmployeeRate
    {
        return EmployeeRate::where('employee_id', $employeeId)
            ->where('status', 'active')
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
     */
    public function determineCurrencyForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd, string $defaultCurrency): string
    {
        // Find all active rates that overlap with the period
        $rates = EmployeeRate::where('employee_id', $employeeId)
            ->where('status', 'active')
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
     * Calculate total adjustments amount (adjustments + advances) for a specific payroll.
     * Now advances and adjustments are directly linked to payroll_id.
     */
    public function calculateAdjustmentsAmountForPayroll(Payroll $payroll): float
    {
        $totalAdjustments = 0;

        // Get adjustments linked to this payroll
        $adjustments = Adjustment::where('payroll_id', $payroll->id)->get();
        foreach ($adjustments as $adjustment) {
            $totalAdjustments += $adjustment->getEffectiveAmount();
        }

        // Get advances linked to this payroll
        $advances = Advance::where('payroll_id', $payroll->id)->get();
        foreach ($advances as $advance) {
            $totalAdjustments -= (float) $advance->amount;
            $totalAdjustments -= $advance->getInterestAmount();
        }

        return round($totalAdjustments, 2);
    }

    /**
     * Recalculate an existing payroll.
     *
     * @throws \Exception If payroll status doesn't allow recalculation
     */
    public function recalculate(Payroll $payroll): Payroll
    {
        $result = $this->recalculateMany([$payroll]);
        if ($result['errors'] !== []) {
            throw new \Exception($result['errors'][0]['message']);
        }

        return $payroll->refresh();
    }

    /**
     * Przelicz wiele payrolli jednym odczytem godzin, stawek, korekt i zaliczek.
     *
     * @param  iterable<int, Payroll>  $payrolls
     * @return array{recalculated: int, errors: list<array{id: int, message: string}>}
     */
    public function recalculateMany(iterable $payrolls): array
    {
        $payrolls = collect($payrolls)->values();
        $recalculated = 0;
        $errors = [];

        if ($payrolls->isEmpty()) {
            return ['recalculated' => 0, 'errors' => []];
        }

        $employeeIds = $payrolls->pluck('employee_id')->map(fn ($id) => (int) $id)->unique()->filter()->values();
        $rangeStart = $payrolls->map(fn (Payroll $payroll) => Carbon::parse($payroll->period_start)->startOfDay())->min();
        $rangeEnd = $payrolls->map(fn (Payroll $payroll) => Carbon::parse($payroll->period_end)->endOfDay())->max();

        $logsByEmployee = $employeeIds->isEmpty()
            ? collect()
            : TimeLog::query()
                ->join('project_assignments', 'time_logs.project_assignment_id', '=', 'project_assignments.id')
                ->whereIn('project_assignments.employee_id', $employeeIds)
                ->whereBetween('time_logs.start_time', [$rangeStart, $rangeEnd])
                ->select('time_logs.*')
                ->addSelect('project_assignments.employee_id as payroll_employee_id')
                ->get()
                ->groupBy(fn (TimeLog $log) => (int) $log->payroll_employee_id);

        $ratesByEmployee = $employeeIds->isEmpty()
            ? collect()
            : EmployeeRate::query()
                ->whereIn('employee_id', $employeeIds)
                ->where('status', 'active')
                ->orderByDesc('start_date')
                ->get()
                ->groupBy(fn (EmployeeRate $rate) => (int) $rate->employee_id);

        $payrollIds = $payrolls->pluck('id');
        $adjustmentsByPayroll = Adjustment::query()
            ->whereIn('payroll_id', $payrollIds)
            ->get()
            ->groupBy('payroll_id');
        $advancesByPayroll = Advance::query()
            ->whereIn('payroll_id', $payrollIds)
            ->get()
            ->groupBy('payroll_id');

        foreach ($payrolls as $payroll) {
            try {
                if (! $payroll->canBeRecalculated()) {
                    throw new \Exception('Można przeliczyć tylko payroll ze statusem "Szkic" lub "Wystawiony".');
                }

                $periodStart = Carbon::parse($payroll->period_start);
                $periodEnd = Carbon::parse($payroll->period_end);
                $employeeId = (int) $payroll->employee_id;
                $logs = ($logsByEmployee->get($employeeId) ?? collect())->filter(function (TimeLog $log) use ($periodStart, $periodEnd) {
                    $started = Carbon::parse($log->start_time);

                    return $started->gte($periodStart->copy()->startOfDay())
                        && $started->lte($periodEnd->copy()->endOfDay());
                })->values();
                $rates = $ratesByEmployee->get($employeeId) ?? collect();
                $currency = $this->currencyFromRates($rates, $periodStart, $periodEnd, (string) $payroll->currency);

                $payroll->hours_amount = $this->sumLoggedHours($logs, $currency, collect([$employeeId => $rates]));
                $payroll->currency = $currency;
                $payroll->adjustments_amount = $this->adjustmentsTotal(
                    $adjustmentsByPayroll->get($payroll->id) ?? collect(),
                    $advancesByPayroll->get($payroll->id) ?? collect(),
                );
                $payroll->recalculateTotal();
                $payroll->save();
                $recalculated++;
            } catch (\Exception $e) {
                $errors[] = ['id' => (int) $payroll->id, 'message' => $e->getMessage()];
            }
        }

        return ['recalculated' => $recalculated, 'errors' => $errors];
    }

    /**
     * @param  iterable<int, TimeLog>  $timeLogs
     * @return Collection<int, Collection<int, EmployeeRate>>
     */
    private function activeRatesForLogs(iterable $timeLogs): Collection
    {
        $employeeIds = collect($timeLogs)
            ->map(fn (TimeLog $log) => $this->employeeIdForLog($log))
            ->unique()
            ->filter()
            ->values();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return EmployeeRate::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->get()
            ->groupBy(fn (EmployeeRate $rate) => (int) $rate->employee_id);
    }

    /**
     * @param  iterable<int, TimeLog>  $timeLogs
     * @param  Collection<int, Collection<int, EmployeeRate>>  $ratesByEmployee
     */
    private function sumLoggedHours(iterable $timeLogs, string $currency, Collection $ratesByEmployee): float
    {
        $totalAmount = 0.0;

        foreach ($timeLogs as $timeLog) {
            $hoursWorked = (float) $timeLog->hours_worked;
            if ($hoursWorked <= 0) {
                continue;
            }

            $employeeId = $this->employeeIdForLog($timeLog);
            $workDate = Carbon::parse($timeLog->start_time)->toDateString();
            $rates = $ratesByEmployee->get($employeeId) ?? collect();
            $rate = $this->firstCoveringRate($rates, $workDate, $currency)
                ?? $this->firstCoveringRate($rates, $workDate, null);

            if (! $rate) {
                continue;
            }

            $totalAmount += $hoursWorked * (float) $rate->amount;
        }

        return round($totalAmount, 2);
    }

    private function employeeIdForLog(TimeLog $timeLog): int
    {
        $fromJoin = $timeLog->getAttribute('payroll_employee_id');
        if ($fromJoin) {
            return (int) $fromJoin;
        }

        return (int) $timeLog->projectAssignment->employee_id;
    }

    /**
     * @param  Collection<int, EmployeeRate>  $rates
     */
    private function firstCoveringRate(Collection $rates, string $date, ?string $currency): ?EmployeeRate
    {
        foreach ($rates as $rate) {
            if ($currency !== null && $rate->currency !== $currency) {
                continue;
            }

            $start = $rate->start_date?->toDateString();
            if ($start === null || $start > $date) {
                continue;
            }

            $end = $rate->end_date?->toDateString();
            if ($end !== null && $end < $date) {
                continue;
            }

            return $rate;
        }

        return null;
    }

    /**
     * @param  Collection<int, EmployeeRate>  $rates
     */
    private function currencyFromRates(Collection $rates, Carbon $periodStart, Carbon $periodEnd, string $defaultCurrency): string
    {
        $start = $periodStart->toDateString();
        $end = $periodEnd->toDateString();
        $overlapping = $rates->filter(function (EmployeeRate $rate) use ($start, $end) {
            $rateStart = $rate->start_date?->toDateString();
            if ($rateStart === null || $rateStart > $end) {
                return false;
            }

            $rateEnd = $rate->end_date?->toDateString();

            return $rateEnd === null || $rateEnd >= $start;
        });

        if ($overlapping->isEmpty()) {
            return $defaultCurrency;
        }

        $currencyCounts = $overlapping->groupBy('currency')->map(fn (Collection $group) => $group->count());

        return (string) ($currencyCounts->sortDesc()->keys()->first() ?? $defaultCurrency);
    }

    /**
     * @param  Collection<int, Adjustment>  $adjustments
     * @param  Collection<int, Advance>  $advances
     */
    private function adjustmentsTotal(Collection $adjustments, Collection $advances): float
    {
        $total = 0.0;

        foreach ($adjustments as $adjustment) {
            $total += $adjustment->getEffectiveAmount();
        }

        foreach ($advances as $advance) {
            $total -= (float) $advance->amount;
            $total -= $advance->getInterestAmount();
        }

        return round($total, 2);
    }
}
