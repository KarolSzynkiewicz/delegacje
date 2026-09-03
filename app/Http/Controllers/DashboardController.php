<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Services\ProfitabilityService;
use App\Support\DashboardSnaps\DummyWorld;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ProfitabilityService $profitabilityService
    ) {}

    /**
     * Główny dashboard systemu (start).
     */
    public function home(): View
    {
        return view('dashboard', [
            'snaps' => DummyWorld::make()->toView(),
        ]);
    }

    /**
     * Dashboard rentowności (zyski i straty) — panel kontrolingowy.
     */
    public function index(Request $request): View
    {
        $month = $this->parseMonth($request);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $filters = $this->parseFilters($request);

        $projectsProfitability = $this->profitabilityService->getProjectsProfitabilityForMonth(
            $monthStart,
            $monthEnd,
            $filters['statuses'],
            $filters['type'],
            $filters['search']
        );
        $topEmployees = $this->profitabilityService->getTopEmployeesByRevenueFromProjects(
            $projectsProfitability,
            $monthStart,
            $monthEnd,
            10
        );
        $longestRotations = $this->profitabilityService->getEmployeesWithLongestRotations(10);
        // Podsumowanie dla bieżącego miesiąca liczone z już wyliczonych danych projektów
        // (te same filtry/miesiąc co $projectsProfitability) — patrz summarizeProjectsProfitability().
        $summary = $this->profitabilityService->summarizeProjectsProfitability(
            $projectsProfitability,
            $monthStart,
            $monthEnd
        );

        $prevMonthForDelta = $month->copy()->subMonth();
        $previousSummary = $this->profitabilityService->getRevenueVsCostsSummaryForMonth(
            $prevMonthForDelta->copy()->startOfMonth(),
            $prevMonthForDelta->copy()->endOfMonth(),
            $filters['statuses'],
            $filters['type'],
            $filters['search']
        );

        // Ranking mieszkań wg kosztu najmu w miesiącu + koszt na osobonoc (kontroling zakwaterowania)
        $topAccommodations = $this->profitabilityService->getTopAccommodationCostsForMonth($monthStart, $monthEnd, 10);

        $navigation = $this->buildNavigation($month, $filters);

        $rankings = $this->buildRankings($projectsProfitability);
        $breakdown = $this->buildBreakdown($projectsProfitability, $summary);

        $projectsProfitability = $this->sortProjects($projectsProfitability, $filters['sortBy'], $filters['sortDir']);

        return view('dashboard.profitability', compact(
            'projectsProfitability',
            'topEmployees',
            'longestRotations',
            'summary',
            'previousSummary',
            'navigation',
            'month',
            'filters',
            'rankings',
            'breakdown',
            'topAccommodations'
        ));
    }

    /**
     * Eksport CSV listy projektów wraz z rentownością dla wybranego miesiąca/filtrów.
     */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $month = $this->parseMonth($request);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $filters = $this->parseFilters($request);

        $projectsProfitability = $this->profitabilityService->getProjectsProfitabilityForMonth(
            $monthStart,
            $monthEnd,
            $filters['statuses'],
            $filters['type'],
            $filters['search']
        );
        $projectsProfitability = $this->sortProjects($projectsProfitability, $filters['sortBy'], $filters['sortDir']);

        $filename = 'rentownosc-projektow-'.$monthStart->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($projectsProfitability) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM dla Excela

            fputcsv($out, [
                'Projekt', 'Klient', 'Status', 'Typ',
                'Przychód', 'Waluta przychodu',
                'Koszty pracy', 'Koszty projektowe', 'Koszty łącznie',
                'Marża', 'Marża %',
                'Liczba pracowników', 'Godziny szacowane', 'Godziny rzeczywiste', 'Wykonanie planu %',
            ], ';');

            foreach ($projectsProfitability as $row) {
                $project = $row['project'];
                $currency = $row['revenue_currency'];

                fputcsv($out, [
                    $project->name,
                    $project->client_name ?? '',
                    $project->status?->label() ?? '',
                    $project->type?->label() ?? '',
                    number_format($row['revenue'], 2, ',', ''),
                    $currency,
                    number_format($row['labor_costs_by_currency'][$currency] ?? 0, 2, ',', ''),
                    number_format($row['variable_costs_by_currency'][$currency] ?? 0, 2, ',', ''),
                    number_format($row['total_costs_by_currency'][$currency] ?? 0, 2, ',', ''),
                    number_format($row['margin'], 2, ',', ''),
                    $row['margin_percentage'] !== null ? number_format($row['margin_percentage'], 2, ',', '') : '',
                    $row['employee_count'],
                    number_format($row['estimated_hours'], 2, ',', ''),
                    number_format($row['actual_hours'], 2, ',', ''),
                    number_format($row['plan_execution'], 2, ',', ''),
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function parseMonth(Request $request): Carbon
    {
        $year = $request->query('year');
        $month = $request->query('month');

        if ($year && $month) {
            return Carbon::create((int) $year, (int) $month, 1);
        }

        return Carbon::now()->startOfMonth();
    }

    /**
     * @return array{statuses: array<int, string>|null, type: string|null, search: string|null}
     */
    protected function parseFilters(Request $request): array
    {
        $statuses = $request->query('statuses');
        if (is_array($statuses)) {
            $validStatuses = ProjectStatus::values();
            $statuses = array_values(array_intersect($statuses, $validStatuses));
            if ($statuses === [] || count($statuses) === count($validStatuses)) {
                $statuses = null; // brak filtra = wszystkie statusy
            }
        } else {
            $statuses = null;
        }

        $type = $request->query('type');
        if (! in_array($type, ProjectType::values(), true)) {
            $type = null;
        }

        $search = trim((string) $request->query('search', ''));
        $search = $search !== '' ? $search : null;

        $sortableColumns = ['name', 'client_name', 'status', 'type', 'revenue', 'margin', 'margin_percentage', 'plan_execution', 'actual_hours'];
        $sortBy = $request->query('sort_by');
        $sortBy = in_array($sortBy, $sortableColumns, true) ? $sortBy : 'margin_percentage';

        $sortDir = $request->query('sort_dir');
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        return [
            'statuses' => $statuses,
            'type' => $type,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ];
    }

    /**
     * Sortuje listę rentowności projektów wg wybranej kolumny (tabela w UI).
     */
    protected function sortProjects(array $projectsProfitability, string $sortBy, string $sortDir): array
    {
        $sorted = collect($projectsProfitability)->sortBy(function ($row) use ($sortBy) {
            return match ($sortBy) {
                'name' => mb_strtolower($row['project']->name ?? ''),
                'client_name' => mb_strtolower($row['project']->client_name ?? ''),
                'status' => $row['project']->status?->label() ?? '',
                'type' => $row['project']->type?->label() ?? '',
                'revenue' => $row['revenue'],
                'margin' => $row['margin'],
                'margin_percentage' => $row['margin_percentage'] ?? -INF,
                'plan_execution' => $row['plan_execution'],
                'actual_hours' => $row['actual_hours'],
                default => 0,
            };
        }, SORT_REGULAR, $sortDir === 'desc');

        return $sorted->values()->all();
    }

    protected function buildNavigation(Carbon $currentMonth, array $filters): array
    {
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        $months = [
            1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień',
            5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień',
            9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień',
        ];

        $monthName = $months[(int) $currentMonth->format('n')] ?? $currentMonth->format('F');

        $filterQuery = array_filter([
            'statuses' => $filters['statuses'],
            'type' => $filters['type'],
            'search' => $filters['search'],
            'sort_by' => $filters['sortBy'] !== 'margin_percentage' ? $filters['sortBy'] : null,
            'sort_dir' => $filters['sortDir'] !== 'desc' ? $filters['sortDir'] : null,
        ], fn ($v) => $v !== null && $v !== []);

        $buildUrl = function (Carbon $month) use ($filterQuery) {
            $query = array_merge($filterQuery, [
                'year' => $month->format('Y'),
                'month' => $month->format('m'),
            ]);

            return route('profitability.index').'?'.http_build_query($query);
        };

        return [
            'current' => [
                'month' => $currentMonth->format('m'),
                'year' => $currentMonth->format('Y'),
                'label' => $monthName.' '.$currentMonth->format('Y'),
                'start' => $currentMonth->copy()->startOfMonth(),
                'end' => $currentMonth->copy()->endOfMonth(),
            ],
            'prevUrl' => $buildUrl($prevMonth),
            'nextUrl' => $buildUrl($nextMonth),
            'exportUrl' => route('profitability.export-csv').'?'.http_build_query(array_merge($filterQuery, [
                'year' => $currentMonth->format('Y'),
                'month' => $currentMonth->format('m'),
            ])),
        ];
    }

    /**
     * Rankingi kontrolingowe: najbardziej / najmniej rentowne projekty w miesiącu.
     */
    protected function buildRankings(array $projectsProfitability): array
    {
        $withMargin = collect($projectsProfitability)
            ->filter(fn ($row) => $row['margin_percentage'] !== null);

        $best = $withMargin->sortByDesc('margin_percentage')->take(5)->values()->all();
        $worst = $withMargin->sortBy('margin_percentage')->take(5)->values()->all();

        return [
            'best' => $best,
            'worst' => $worst,
        ];
    }

    /**
     * Podział przychodów/liczby projektów wg typu projektu (kontroling).
     */
    protected function buildBreakdown(array $projectsProfitability, array $summary): array
    {
        $byType = [];
        foreach (ProjectType::cases() as $type) {
            $byType[$type->value] = [
                'label' => $type->label(),
                'count' => 0,
                'revenue_by_currency' => [],
            ];
        }

        foreach ($projectsProfitability as $row) {
            $typeValue = $row['project']->type?->value;
            if (! $typeValue || ! isset($byType[$typeValue])) {
                continue;
            }
            $byType[$typeValue]['count']++;
            $currency = $row['revenue_currency'];
            $byType[$typeValue]['revenue_by_currency'][$currency] =
                ($byType[$typeValue]['revenue_by_currency'][$currency] ?? 0) + $row['revenue'];
        }

        $byStatus = [];
        foreach (ProjectStatus::cases() as $status) {
            $byStatus[$status->value] = [
                'label' => $status->label(),
                'count' => 0,
            ];
        }
        foreach ($projectsProfitability as $row) {
            $statusValue = $row['project']->status?->value;
            if ($statusValue && isset($byStatus[$statusValue])) {
                $byStatus[$statusValue]['count']++;
            }
        }

        $avgMarginPercentage = collect($projectsProfitability)
            ->pluck('margin_percentage')
            ->filter(fn ($v) => $v !== null)
            ->avg();

        $avgPlanExecution = collect($projectsProfitability)->pluck('plan_execution')->avg();

        return [
            'by_type' => $byType,
            'by_status' => $byStatus,
            'avg_margin_percentage' => $avgMarginPercentage !== null ? round($avgMarginPercentage, 2) : null,
            'avg_plan_execution' => $avgPlanExecution !== null ? round($avgPlanExecution, 2) : 0,
        ];
    }
}
