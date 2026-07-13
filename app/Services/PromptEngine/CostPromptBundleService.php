<?php

namespace App\Services\PromptEngine;

use App\Enums\PayrollStatus;
use App\Models\AccommodationLease;
use App\Models\Adjustment;
use App\Models\Advance;
use App\Models\EmployeeRate;
use App\Models\FixedCostEntry;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\ProjectVariableCost;
use App\Models\TimeLog;
use App\Models\TransportCost;
use App\Models\VehicleRepair;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds a self-contained JSON bundle of all cost data for a date range,
 * structured for LLM consumption + charting on the frontend.
 *
 * Design principles:
 * - Currencies NEVER mixed in aggregates (separate buckets per currency code).
 * - Transport costs are NOT allocated to projects (cost-center: "logistics").
 * - Accommodation rent is sourced from `accommodation_leases.monthly_rent`, pro-rated via
 *   {@see \App\Models\AccommodationLease::amountForPeriod()} (metodologia współdzielona
 *   z kontrolingiem rentowności): umowa na czas określony = kwota całkowita za cały okres
 *   podzielona proporcjonalnie do dni; umowa bezterminowa = stawka miesięczna /30 dni.
 * - Fixed costs are pro-rated proportionally to overlap between entry's
 *   `period_start..period_end` and the report range.
 * - Variable costs use `incurred_date` (with fallback to `created_at`).
 * - Labor costs are computed from TimeLog × EmployeeRate at the work date.
 */
class CostPromptBundleService
{
    /** @var array<int, string> */
    private const COST_TYPES = [
        'fixed',
        'variable',
        'transport',
        'accommodation',
        'labor',
        'adjustments',
        'advances',
        'vehicle_repairs',
    ];

    /**
     * @param  list<int>            $projectIds  Empty = all
     * @param  list<string>|null    $include     Null = all cost types
     * @return array<string, mixed>
     */
    public function build(
        Carbon $start,
        Carbon $end,
        array $projectIds = [],
        ?array $include = null,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $types = $include === null || $include === []
            ? self::COST_TYPES
            : array_values(array_intersect(self::COST_TYPES, $include));

        $projectFilter = $projectIds !== [] ? $projectIds : null;

        $fixedCosts        = in_array('fixed', $types, true)         ? $this->buildFixedCosts($start, $end)               : [];
        $variableCosts     = in_array('variable', $types, true)      ? $this->buildVariableCosts($start, $end, $projectFilter) : [];
        $transportCosts    = in_array('transport', $types, true)     ? $this->buildTransportCosts($start, $end)           : [];
        $accommodation     = in_array('accommodation', $types, true) ? $this->buildAccommodationCosts($start, $end)       : [];
        $laborCosts        = in_array('labor', $types, true)         ? $this->buildLaborCosts($start, $end, $projectFilter) : [];
        $adjustments       = in_array('adjustments', $types, true)   ? $this->buildAdjustments($start, $end)              : [];
        $advances          = in_array('advances', $types, true)      ? $this->buildAdvances($start, $end)                 : [];
        $vehicleRepairs    = in_array('vehicle_repairs', $types, true) ? $this->buildVehicleRepairs($start, $end)         : [];

        $summary = $this->buildSummary([
            'fixed'           => $fixedCosts,
            'variable'        => $variableCosts,
            'transport'       => $transportCosts,
            'accommodation'   => $accommodation,
            'labor'           => $laborCosts,
            'adjustments'     => $adjustments,
            'advances'        => $advances,
            'vehicle_repairs' => $vehicleRepairs,
        ], $start, $end, $projectFilter);

        return [
            'meta' => [
                'schema_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'start_at' => $start->toIso8601String(),
                    'end_at' => $end->toIso8601String(),
                    'days' => $start->diffInDays($end) + 1,
                ],
                'allocation_strategy' => 'none',
                'allocation_note' => 'Transport costs are treated as a "logistics" cost-center (no project allocation, per business decision).',
                'currency_policy' => 'per-currency-bucket (never mixed; FX conversion intentionally not applied here)',
                'project_filter' => $projectFilter,
                'cost_types_included' => $types,
            ],
            'summary' => $summary,
            'fixed_costs' => $fixedCosts,
            'variable_costs' => $variableCosts,
            'transport_costs' => $transportCosts,
            'accommodation_costs' => $accommodation,
            'labor_costs' => $laborCosts,
            'adjustments' => $adjustments,
            'advances' => $advances,
            'vehicle_repairs' => $vehicleRepairs,
        ];
    }

    // ── Fixed costs (overhead) ───────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFixedCosts(Carbon $start, Carbon $end): array
    {
        $entries = FixedCostEntry::query()
            ->with('template:id,name,interval_type,category')
            ->where('period_start', '<=', $end->toDateString())
            ->where('period_end', '>=', $start->toDateString())
            ->orderBy('period_start')
            ->orderBy('id')
            ->get();

        $result = [];
        foreach ($entries as $entry) {
            $entryStart = Carbon::parse($entry->period_start);
            $entryEnd = Carbon::parse($entry->period_end);

            $entryDays = max(1, $entryStart->diffInDays($entryEnd) + 1);
            $overlap = $this->overlapDays($entryStart, $entryEnd, $start, $end);

            if ($overlap <= 0) {
                continue;
            }

            $fullAmount = (float) $entry->amount;
            $proRated = round($fullAmount * $overlap / $entryDays, 2);

            $result[] = [
                'id' => $entry->id,
                'name' => $entry->name,
                'category' => $entry->category ?? $entry->template?->category,
                'amount_full' => $fullAmount,
                'amount_in_period' => $proRated,
                'currency' => $entry->currency,
                'period_start' => $entry->period_start?->toDateString(),
                'period_end' => $entry->period_end?->toDateString(),
                'accounting_date' => $entry->accounting_date?->toDateString(),
                'entry_days_total' => $entryDays,
                'days_in_period' => $overlap,
                'template' => $entry->template ? [
                    'id' => $entry->template->id,
                    'name' => $entry->template->name,
                    'interval_type' => $entry->template->interval_type,
                ] : null,
                'notes' => $entry->notes,
            ];
        }

        return $result;
    }

    // ── Project variable costs ───────────────────────────────────────────────

    /**
     * @param  list<int>|null  $projectIds
     * @return array<int, array<string, mixed>>
     */
    private function buildVariableCosts(Carbon $start, Carbon $end, ?array $projectIds): array
    {
        $query = ProjectVariableCost::query()
            ->with('project:id,name')
            ->whereBetween('incurred_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('incurred_date');

        if ($projectIds !== null) {
            $query->whereIn('project_id', $projectIds);
        }

        return $query->get()->map(fn (ProjectVariableCost $c) => [
            'id' => $c->id,
            'project' => $c->project ? ['id' => $c->project->id, 'name' => $c->project->name] : null,
            'category' => $c->category,
            'name' => $c->name,
            'amount' => (float) $c->amount,
            'currency' => $c->currency,
            'incurred_date' => $c->incurred_date?->toDateString(),
            'notes' => $c->notes,
        ])->values()->all();
    }

    // ── Transport costs (logistics cost center, no project allocation) ───────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTransportCosts(Carbon $start, Carbon $end): array
    {
        $costs = TransportCost::query()
            ->with([
                'logisticsEvent:id,event_date,type,from_location_id,to_location_id',
                'logisticsEvent.fromLocation:id,name,city',
                'logisticsEvent.toLocation:id,name,city',
                'vehicle:id,brand,model,registration_number',
            ])
            ->whereBetween('cost_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('cost_date')
            ->orderBy('id')
            ->get();

        return $costs->map(function (TransportCost $tc) {
            $event = $tc->logisticsEvent;

            return [
                'id' => $tc->id,
                'cost_type' => $tc->cost_type,
                'amount' => (float) $tc->amount,
                'currency' => $tc->currency,
                'cost_date' => $tc->cost_date?->toDateString(),
                'description' => $tc->description,
                'vehicle' => $tc->vehicle ? [
                    'id' => $tc->vehicle->id,
                    'label' => trim(($tc->vehicle->brand ?? '').' '.($tc->vehicle->model ?? '')).' ('.($tc->vehicle->registration_number ?? '—').')',
                ] : null,
                'logistics_event' => $event ? [
                    'id' => $event->id,
                    'type' => $event->type?->value,
                    'event_date' => $event->event_date?->toDateString(),
                    'from' => $event->fromLocation ? trim(($event->fromLocation->name ?? '').' / '.($event->fromLocation->city ?? '')) : null,
                    'to' => $event->toLocation ? trim(($event->toLocation->name ?? '').' / '.($event->toLocation->city ?? '')) : null,
                ] : null,
            ];
        })->values()->all();
    }

    // ── Accommodation costs (rent, pro-rated by lease overlap) ───────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAccommodationCosts(Carbon $start, Carbon $end): array
    {
        $leases = AccommodationLease::query()
            ->with('accommodation:id,name,city,country,capacity')
            ->whereNotNull('monthly_rent')
            ->where('start_date', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start->toDateString());
            })
            ->get();

        $result = [];
        foreach ($leases as $lease) {
            $leaseStart = $lease->start_date ? Carbon::parse($lease->start_date) : $start;
            $leaseEnd = $lease->end_date ? Carbon::parse($lease->end_date) : $end;

            $overlap = $this->overlapDays($leaseStart, $leaseEnd, $start, $end);
            if ($overlap <= 0) {
                continue;
            }

            // Metodologia (patrz AccommodationLease::amountForPeriod — wspólna z kontrolingiem
            // rentowności): umowa na czas określony = monthly_rent to kwota za CAŁY okres,
            // dzielona proporcjonalnie; umowa bezterminowa = stawka miesięczna /30 dni.
            $costInPeriod = $lease->amountForPeriod($start, $end);

            $personNights = $lease->accommodation?->occupancyNightsBetween($start, $end) ?? 0;

            $result[] = [
                'lease_id' => $lease->id,
                'accommodation' => $lease->accommodation ? [
                    'id' => $lease->accommodation->id,
                    'name' => $lease->accommodation->name,
                    'city' => $lease->accommodation->city,
                    'country' => $lease->accommodation->country instanceof \BackedEnum
                        ? $lease->accommodation->country->value
                        : $lease->accommodation->country,
                    'capacity' => $lease->accommodation->capacity,
                ] : null,
                'lease_start' => $lease->start_date?->toDateString(),
                'lease_end' => $lease->end_date?->toDateString(),
                'monthly_rent' => (float) $lease->monthly_rent,
                'currency' => $lease->currency,
                'days_in_period' => $overlap,
                'amount_in_period' => $costInPeriod,
                'person_nights_in_period' => $personNights,
                'cost_per_person_night' => $personNights > 0
                    ? round($costInPeriod / $personNights, 2)
                    : null,
                'notes' => $lease->notes,
            ];
        }

        return $result;
    }

    // ── Labor costs (TimeLog × EmployeeRate) ─────────────────────────────────

    /**
     * @param  list<int>|null  $projectIds
     * @return array<int, array<string, mixed>>
     */
    private function buildLaborCosts(Carbon $start, Carbon $end, ?array $projectIds): array
    {
        $timeLogsQuery = TimeLog::query()
            ->with([
                'projectAssignment:id,project_id,employee_id',
                'projectAssignment.project:id,name',
                'projectAssignment.employee:id,first_name,last_name',
            ])
            ->whereBetween('start_time', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        if ($projectIds !== null) {
            $timeLogsQuery->whereHas('projectAssignment', fn ($q) => $q->whereIn('project_id', $projectIds));
        }

        $timeLogs = $timeLogsQuery->get();

        // Agregacja per (employee, project, currency)
        $bucket = [];
        foreach ($timeLogs as $log) {
            $assignment = $log->projectAssignment;
            if (!$assignment) continue;

            $hours = (float) $log->hours_worked;
            if ($hours <= 0) continue;

            $workDate = Carbon::parse($log->start_time)->toDateString();

            $rate = EmployeeRate::query()
                ->where('employee_id', $assignment->employee_id)
                ->where('status', 'active')
                ->where('start_date', '<=', $workDate)
                ->where(function ($q) use ($workDate) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $workDate);
                })
                ->orderByDesc('start_date')
                ->first();

            if (!$rate) continue;

            $cost = round($hours * (float) $rate->amount, 2);
            $key = $assignment->employee_id.'|'.$assignment->project_id.'|'.$rate->currency;

            if (!isset($bucket[$key])) {
                $bucket[$key] = [
                    'employee' => $assignment->employee ? [
                        'id' => $assignment->employee->id,
                        'name' => $assignment->employee->full_name,
                    ] : null,
                    'project' => $assignment->project ? [
                        'id' => $assignment->project->id,
                        'name' => $assignment->project->name,
                    ] : null,
                    'currency' => $rate->currency,
                    'hours' => 0.0,
                    'rate_amount' => (float) $rate->amount,
                    'cost' => 0.0,
                    'time_log_count' => 0,
                ];
            }
            $bucket[$key]['hours'] = round($bucket[$key]['hours'] + $hours, 2);
            $bucket[$key]['cost'] = round($bucket[$key]['cost'] + $cost, 2);
            $bucket[$key]['time_log_count']++;
        }

        return array_values($bucket);
    }

    // ── Adjustments (bonuses & penalties) ────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAdjustments(Carbon $start, Carbon $end): array
    {
        return Adjustment::query()
            ->with(['employee:id,first_name,last_name'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(fn (Adjustment $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'amount' => (float) $a->amount,
                'effective_amount' => $a->getEffectiveAmount(),
                'currency' => $a->currency,
                'date' => $a->date?->toDateString(),
                'employee' => $a->employee ? [
                    'id' => $a->employee->id,
                    'name' => $a->employee->full_name,
                ] : null,
                'payroll_id' => $a->payroll_id,
                'logistics_event_id' => $a->logistics_event_id,
                'notes' => $a->notes,
            ])->values()->all();
    }

    // ── Advances (loans + interest) ──────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAdvances(Carbon $start, Carbon $end): array
    {
        return Advance::query()
            ->with(['employee:id,first_name,last_name'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(fn (Advance $a) => [
                'id' => $a->id,
                'amount' => (float) $a->amount,
                'interest_amount' => $a->getInterestAmount(),
                'total_deduction' => $a->getTotalDeductionAmount(),
                'is_interest_bearing' => (bool) $a->is_interest_bearing,
                'interest_rate' => $a->interest_rate !== null ? (float) $a->interest_rate : null,
                'currency' => $a->currency,
                'date' => $a->date?->toDateString(),
                'employee' => $a->employee ? [
                    'id' => $a->employee->id,
                    'name' => $a->employee->full_name,
                ] : null,
                'payroll_id' => $a->payroll_id,
                'notes' => $a->notes,
            ])->values()->all();
    }

    // ── Vehicle repairs ──────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildVehicleRepairs(Carbon $start, Carbon $end): array
    {
        return VehicleRepair::query()
            ->with(['vehicle:id,brand,model,registration_number'])
            ->where('start_date', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $start->toDateString());
            })
            ->whereNotNull('price')
            ->orderBy('start_date')
            ->get()
            ->map(fn (VehicleRepair $r) => [
                'id' => $r->id,
                'action_type' => $r->action_type?->value,
                'amount' => (float) $r->price,
                'currency' => $r->currency,
                'start_date' => $r->start_date?->toDateString(),
                'end_date' => $r->end_date?->toDateString(),
                'status' => $r->status,
                'vehicle' => $r->vehicle ? [
                    'id' => $r->vehicle->id,
                    'label' => trim(($r->vehicle->brand ?? '').' '.($r->vehicle->model ?? '')).' ('.($r->vehicle->registration_number ?? '—').')',
                ] : null,
                'notes' => $r->notes,
            ])->values()->all();
    }

    // ── Summary builder ──────────────────────────────────────────────────────

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $groups
     * @param  list<int>|null  $projectIds
     * @return array<string, mixed>
     */
    private function buildSummary(array $groups, Carbon $start, Carbon $end, ?array $projectIds): array
    {
        $byCurrencyType = [];

        $accumulator = function (string $bucket, string $currency, float $amount) use (&$byCurrencyType): void {
            $currency = strtoupper($currency ?: 'PLN');
            if (!isset($byCurrencyType[$currency])) {
                $byCurrencyType[$currency] = [
                    'fixed' => 0.0,
                    'variable' => 0.0,
                    'transport' => 0.0,
                    'accommodation' => 0.0,
                    'labor' => 0.0,
                    'adjustments_net' => 0.0,
                    'advances_net' => 0.0,
                    'vehicle_repairs' => 0.0,
                    'total' => 0.0,
                ];
            }
            $byCurrencyType[$currency][$bucket] = round($byCurrencyType[$currency][$bucket] + $amount, 2);
        };

        foreach ($groups['fixed'] as $row) {
            $accumulator('fixed', $row['currency'], (float) $row['amount_in_period']);
        }
        foreach ($groups['variable'] as $row) {
            $accumulator('variable', $row['currency'], (float) $row['amount']);
        }
        foreach ($groups['transport'] as $row) {
            $accumulator('transport', $row['currency'], (float) $row['amount']);
        }
        foreach ($groups['accommodation'] as $row) {
            if ($row['currency']) {
                $accumulator('accommodation', $row['currency'], (float) $row['amount_in_period']);
            }
        }
        foreach ($groups['labor'] as $row) {
            $accumulator('labor', $row['currency'], (float) $row['cost']);
        }
        foreach ($groups['adjustments'] as $row) {
            $accumulator('adjustments_net', $row['currency'], (float) $row['effective_amount']);
        }
        foreach ($groups['advances'] as $row) {
            $accumulator('advances_net', $row['currency'], -(float) $row['total_deduction']);
        }
        foreach ($groups['vehicle_repairs'] as $row) {
            $accumulator('vehicle_repairs', $row['currency'] ?? 'PLN', (float) $row['amount']);
        }

        // Compute totals per currency (sum of all positive cost types — adjustments/advances
        // pozostają poza "totalem", bo to korekty rozliczeniowe, nie czyste koszty zewnętrzne)
        foreach ($byCurrencyType as $cur => $row) {
            $byCurrencyType[$cur]['total'] = round(
                $row['fixed'] + $row['variable'] + $row['transport']
                + $row['accommodation'] + $row['labor'] + $row['vehicle_repairs'],
                2
            );
        }

        ksort($byCurrencyType);

        // Per project (revenue × all costs trackable per project)
        $byProject = $this->buildPerProjectSummary($groups, $start, $end, $projectIds);

        // Top cost categories (variable + fixed) per currency
        $topCategoriesByCurrency = $this->buildTopCategoriesByCurrency($groups);

        return [
            'by_currency_and_type' => $byCurrencyType,
            'currencies_present' => array_keys($byCurrencyType),
            'by_project_and_currency' => $byProject,
            'top_categories_by_currency' => $topCategoriesByCurrency,
        ];
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $groups
     * @param  list<int>|null  $projectIds
     * @return array<int, array<string, mixed>>
     */
    private function buildPerProjectSummary(array $groups, Carbon $start, Carbon $end, ?array $projectIds): array
    {
        $projectsQuery = Project::query()->select(['id', 'name', 'currency', 'type', 'hourly_rate', 'contract_amount', 'start_date', 'end_date']);
        if ($projectIds !== null) {
            $projectsQuery->whereIn('id', $projectIds);
        }
        $projects = $projectsQuery->orderBy('name')->get()->keyBy('id');

        // Aggregate variable costs by project_id × currency
        $varByProject = [];
        foreach ($groups['variable'] as $row) {
            $pid = $row['project']['id'] ?? null;
            if ($pid === null) continue;
            $cur = strtoupper($row['currency'] ?: 'PLN');
            $varByProject[$pid][$cur] = round(($varByProject[$pid][$cur] ?? 0.0) + (float) $row['amount'], 2);
        }

        // Aggregate labor costs by project_id × currency
        $laborByProject = [];
        $hoursByProject = [];
        foreach ($groups['labor'] as $row) {
            $pid = $row['project']['id'] ?? null;
            if ($pid === null) continue;
            $cur = strtoupper($row['currency'] ?: 'PLN');
            $laborByProject[$pid][$cur] = round(($laborByProject[$pid][$cur] ?? 0.0) + (float) $row['cost'], 2);
            $hoursByProject[$pid] = round(($hoursByProject[$pid] ?? 0.0) + (float) $row['hours'], 2);
        }

        $result = [];
        foreach ($projects as $project) {
            $variable = $varByProject[$project->id] ?? [];
            $labor = $laborByProject[$project->id] ?? [];

            // Merge cost currencies set
            $allCurrencies = array_unique(array_merge(array_keys($variable), array_keys($labor)));
            sort($allCurrencies);

            $totalByCurrency = [];
            foreach ($allCurrencies as $cur) {
                $totalByCurrency[$cur] = round(($variable[$cur] ?? 0.0) + ($labor[$cur] ?? 0.0), 2);
            }

            // Revenue (uproszczenie: pełna kwota kontraktu lub hourly_rate × hours w okresie)
            $revenue = $this->revenueForProjectInPeriod($project, $hoursByProject[$project->id] ?? 0.0, $start, $end);

            $result[] = [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'project_type' => $project->type?->value,
                'project_currency' => $project->currency,
                'hours_in_period' => $hoursByProject[$project->id] ?? 0.0,
                'revenue_in_period' => $revenue,
                'costs_variable_by_currency' => $variable,
                'costs_labor_by_currency' => $labor,
                'costs_total_by_currency' => $totalByCurrency,
                'margin_by_currency' => $this->computeMargin($revenue, $project->currency, $totalByCurrency),
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, float>  $costsByCurrency
     * @return array<string, float>
     */
    private function computeMargin(?array $revenue, ?string $revenueCurrency, array $costsByCurrency): array
    {
        $margin = $costsByCurrency;
        foreach ($margin as $cur => $cost) {
            $margin[$cur] = round(-$cost, 2);
        }
        if ($revenue && $revenueCurrency) {
            $cur = strtoupper($revenueCurrency);
            $margin[$cur] = round(($margin[$cur] ?? 0.0) + (float) ($revenue['amount'] ?? 0), 2);
        }

        return $margin;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function revenueForProjectInPeriod(Project $project, float $hoursInPeriod, Carbon $start, Carbon $end): ?array
    {
        if ($project->type?->value === 'hourly') {
            if (!$project->hourly_rate || $hoursInPeriod <= 0) {
                return null;
            }
            return [
                'amount' => round((float) $project->hourly_rate * $hoursInPeriod, 2),
                'currency' => $project->currency,
                'basis' => 'hourly_rate × hours_in_period',
            ];
        }

        // Contract — pro-rata po overlap z czasem trwania projektu
        if ($project->type?->value === 'contract' && $project->contract_amount && $project->start_date) {
            $projectStart = Carbon::parse($project->start_date);
            $projectEnd = $project->end_date ? Carbon::parse($project->end_date) : null;
            if (!$projectEnd) {
                return null;
            }
            $totalDays = max(1, $projectStart->diffInDays($projectEnd) + 1);
            $overlap = $this->overlapDays($projectStart, $projectEnd, $start, $end);
            if ($overlap <= 0) return null;

            return [
                'amount' => round((float) $project->contract_amount * $overlap / $totalDays, 2),
                'currency' => $project->currency,
                'basis' => 'contract_amount × (overlap_days / total_project_days)',
            ];
        }

        return null;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $groups
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildTopCategoriesByCurrency(array $groups): array
    {
        $byCur = [];

        foreach ($groups['fixed'] as $row) {
            $cur = strtoupper($row['currency'] ?: 'PLN');
            $cat = 'FIXED · '.($row['category'] ?? 'uncategorized');
            $byCur[$cur][$cat] = round(($byCur[$cur][$cat] ?? 0.0) + (float) $row['amount_in_period'], 2);
        }
        foreach ($groups['variable'] as $row) {
            $cur = strtoupper($row['currency'] ?: 'PLN');
            $cat = 'VARIABLE · '.($row['category'] ?? 'uncategorized');
            $byCur[$cur][$cat] = round(($byCur[$cur][$cat] ?? 0.0) + (float) $row['amount'], 2);
        }
        foreach ($groups['transport'] as $row) {
            $cur = strtoupper($row['currency'] ?: 'PLN');
            $cat = 'TRANSPORT · '.($row['cost_type'] ?? 'other');
            $byCur[$cur][$cat] = round(($byCur[$cur][$cat] ?? 0.0) + (float) $row['amount'], 2);
        }

        $result = [];
        foreach ($byCur as $cur => $catMap) {
            arsort($catMap);
            $result[$cur] = collect($catMap)->take(15)->map(fn ($v, $k) => [
                'category' => $k,
                'amount' => round((float) $v, 2),
            ])->values()->all();
        }

        ksort($result);

        return $result;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function overlapDays(CarbonInterface $aStart, CarbonInterface $aEnd, CarbonInterface $bStart, CarbonInterface $bEnd): int
    {
        $start = $aStart->gt($bStart) ? $aStart : $bStart;
        $end = $aEnd->lt($bEnd) ? $aEnd : $bEnd;
        if ($start->gt($end)) return 0;
        return (int) $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1;
    }
}
