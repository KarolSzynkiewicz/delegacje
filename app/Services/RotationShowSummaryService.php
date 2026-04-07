<?php

namespace App\Services;

use App\Models\AccommodationAssignment;
use App\Models\Adjustment;
use App\Models\Employee;
use App\Models\Rotation;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RotationShowSummaryService
{
    public function __construct(
        protected GeneratePayrollForEmployee $payrollHelper
    ) {}

    /**
     * @return array{
     *     accommodation_assignments: \Illuminate\Database\Eloquent\Collection<int, AccommodationAssignment>,
     *     vehicle_assignments: \Illuminate\Database\Eloquent\Collection<int, VehicleAssignment>,
     *     total_hours: float,
     *     earnings_by_currency: array<string, float>,
     *     adjustments: \Illuminate\Database\Eloquent\Collection<int, Adjustment>,
     *     bonus_by_currency: array<string, float>,
     *     penalty_by_currency: array<string, float>,
     *     net_by_currency: array<string, float>,
     * }
     */
    public function summarize(Employee $employee, Rotation $rotation): array
    {
        $start = $rotation->start_date->copy()->startOfDay();
        $end = $rotation->end_date->copy()->endOfDay();

        $overlap = function ($q) use ($rotation) {
            $q->where('start_date', '<=', $rotation->end_date)
                ->where(fn ($q2) => $q2->whereNull('end_date')->orWhere('end_date', '>=', $rotation->start_date));
        };

        $accommodationAssignments = AccommodationAssignment::query()
            ->where('employee_id', $employee->id)
            ->where($overlap)
            ->with(['accommodation.location'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $vehicleAssignments = VehicleAssignment::query()
            ->where('employee_id', $employee->id)
            ->where($overlap)
            ->with('vehicle')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $timeLogs = $this->payrollHelper->getTimeLogsForPeriod($employee->id, $start, $end);
        $timeLogs->load('projectAssignment');

        $totalHours = (float) $timeLogs->sum('hours_worked');

        $earningsByCurrency = $this->earningsByCurrencyFromTimeLogs($timeLogs);

        $adjustments = Adjustment::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [
                $rotation->start_date->toDateString(),
                $rotation->end_date->toDateString(),
            ])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $bonusByCurrency = [];
        $penaltyByCurrency = [];
        foreach ($adjustments as $adj) {
            $cur = $adj->currency ?? 'PLN';
            $amt = (float) $adj->amount;
            if ($adj->type === 'bonus') {
                $bonusByCurrency[$cur] = ($bonusByCurrency[$cur] ?? 0) + $amt;
            } else {
                $penaltyByCurrency[$cur] = ($penaltyByCurrency[$cur] ?? 0) + $amt;
            }
        }

        foreach (array_keys($bonusByCurrency) as $k) {
            $bonusByCurrency[$k] = round($bonusByCurrency[$k], 2);
        }
        foreach (array_keys($penaltyByCurrency) as $k) {
            $penaltyByCurrency[$k] = round($penaltyByCurrency[$k], 2);
        }

        $netByCurrency = $this->mergeCurrencyTotals($earningsByCurrency, $bonusByCurrency, $penaltyByCurrency);

        return [
            'accommodation_assignments' => $accommodationAssignments,
            'vehicle_assignments' => $vehicleAssignments,
            'total_hours' => $totalHours,
            'earnings_by_currency' => $earningsByCurrency,
            'adjustments' => $adjustments,
            'bonus_by_currency' => $bonusByCurrency,
            'penalty_by_currency' => $penaltyByCurrency,
            'net_by_currency' => $netByCurrency,
        ];
    }

    /**
     * Zarobek z godzin wg stawki obowiązującej w dniu wpisu (jak przy generowaniu listy płac).
     *
     * @return array<string, float>
     */
    protected function earningsByCurrencyFromTimeLogs(Collection $timeLogs): array
    {
        $byCurrency = [];

        foreach ($timeLogs as $log) {
            $hours = (float) $log->hours_worked;
            if ($hours <= 0 || ! $log->projectAssignment) {
                continue;
            }

            $workDate = Carbon::parse($log->start_time)->toDateString();
            $rate = $this->payrollHelper->findAnyEmployeeRateForDate(
                $log->projectAssignment->employee_id,
                $workDate
            );

            if (! $rate) {
                continue;
            }

            $currency = $rate->currency;
            $byCurrency[$currency] = ($byCurrency[$currency] ?? 0) + $hours * (float) $rate->amount;
        }

        foreach (array_keys($byCurrency) as $k) {
            $byCurrency[$k] = round($byCurrency[$k], 2);
        }
        ksort($byCurrency);

        return $byCurrency;
    }

    /**
     * @param  array<string, float>  $earnings
     * @param  array<string, float>  $bonuses
     * @param  array<string, float>  $penalties
     * @return array<string, float>
     */
    protected function mergeCurrencyTotals(array $earnings, array $bonuses, array $penalties): array
    {
        $currencies = array_unique(array_merge(
            array_keys($earnings),
            array_keys($bonuses),
            array_keys($penalties)
        ));
        sort($currencies);

        $net = [];
        foreach ($currencies as $cur) {
            $net[$cur] = round(
                ($earnings[$cur] ?? 0) + ($bonuses[$cur] ?? 0) - ($penalties[$cur] ?? 0),
                2
            );
        }

        return $net;
    }
}
