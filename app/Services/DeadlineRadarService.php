<?php

namespace App\Services;

use App\Models\AccommodationLease;
use App\Models\EmployeeDocument;
use App\Models\Project;
use App\Models\Rotation;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Rolling 30-day deadline radar for the weekly overview.
 *
 * Lanes: documents, leases, projects, rotations, vehicles (OC+AC+inspection).
 */
final class DeadlineRadarService
{
    public const FORWARD_DAYS = 30;

    public const OVERDUE_LOOKBACK_DAYS = 90;

    public const PLOT_OVERDUE_DAYS = 7;

    /**
     * @param  Carbon|null  $anchor  Reference day for “dziś” on the plot.
     *                               Weekly overview passes the reviewed week’s Monday.
     * @return array{
     *     today: string,
     *     anchor_label: string,
     *     plot_start_offset: int,
     *     plot_end_offset: int,
     *     forward_days: int,
     *     lanes: list<array{key: string, label: string, color: string, count: int}>,
     *     items: list<array<string, mixed>>,
     *     clusters: list<array<string, mixed>>,
     *     kpis: array{within_7: int, overdue: int, monitored: int}
     * }
     */
    public function build(?Carbon $anchor = null): array
    {
        $today = ($anchor ?? Carbon::today()->startOfWeek())->copy()->startOfDay();
        $isCurrentWeek = $today->isSameDay(Carbon::today()->startOfWeek());
        $windowStart = $today->copy()->subDays(self::OVERDUE_LOOKBACK_DAYS);
        $windowEnd = $today->copy()->addDays(self::FORWARD_DAYS);

        $items = collect()
            ->merge($this->documentItems($windowStart, $windowEnd, $today))
            ->merge($this->leaseItems($windowStart, $windowEnd, $today))
            ->merge($this->projectItems($windowStart, $windowEnd, $today))
            ->merge($this->rotationItems($windowStart, $windowEnd, $today))
            ->merge($this->vehicleItems($windowEnd, $today))
            ->sortBy([
                ['days', 'asc'],
                ['lane', 'asc'],
                ['title', 'asc'],
            ])
            ->values();

        $clusters = $this->clusterItems($items);
        $laneDefs = $this->laneDefinitions();
        $counts = $items->groupBy('lane')->map->count();

        $lanes = collect($laneDefs)->map(function (array $lane) use ($counts) {
            return [
                'key' => $lane['key'],
                'label' => $lane['label'],
                'color' => $lane['color'],
                'count' => (int) ($counts[$lane['key']] ?? 0),
            ];
        })->values()->all();

        return [
            'today' => $today->toDateString(),
            'anchor_label' => $isCurrentWeek ? 'dziś' : 'pon '.$today->format('d.m'),
            'plot_start_offset' => -self::PLOT_OVERDUE_DAYS,
            'plot_end_offset' => self::FORWARD_DAYS,
            'forward_days' => self::FORWARD_DAYS,
            'lanes' => $lanes,
            'items' => $items->all(),
            'clusters' => $clusters,
            'kpis' => [
                'within_7' => $items->filter(fn (array $item) => $item['days'] >= 0 && $item['days'] <= 7)->count(),
                'overdue' => $items->filter(fn (array $item) => $item['days'] < 0)->count(),
                'monitored' => $items->count(),
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, color: string}>
     */
    public function laneDefinitions(): array
    {
        return [
            ['key' => 'projects', 'label' => 'Projekty', 'color' => '#3b82f6'],
            ['key' => 'leases', 'label' => 'Najmy', 'color' => '#10b981'],
            ['key' => 'rotations', 'label' => 'Rotacje', 'color' => '#f59e0b'],
            ['key' => 'vehicles', 'label' => 'Auta', 'color' => '#60a5fa'],
            ['key' => 'documents', 'label' => 'Dokumenty', 'color' => '#a855f7'],
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function documentItems(Carbon $windowStart, Carbon $windowEnd, Carbon $today): Collection
    {
        return EmployeeDocument::query()
            ->where('kind', 'okresowy')
            ->whereNotNull('valid_to')
            ->whereBetween('valid_to', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->with(['employee', 'document'])
            ->orderBy('valid_to')
            ->get()
            ->map(function (EmployeeDocument $document) use ($today) {
                $due = $document->valid_to->copy()->startOfDay();

                return $this->makeItem(
                    id: 'document-'.$document->id,
                    lane: 'documents',
                    due: $due,
                    today: $today,
                    title: $document->employee?->full_name ?? 'Dokument',
                    subtitle: $document->document?->name ?? 'Dokument',
                    url: route('employee-documents.edit', $document),
                    badge: null,
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function leaseItems(Carbon $windowStart, Carbon $windowEnd, Carbon $today): Collection
    {
        return AccommodationLease::query()
            ->where('type', 'wynajmowany')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->with('accommodation')
            ->orderBy('end_date')
            ->get()
            ->filter(fn (AccommodationLease $lease) => $lease->accommodation !== null)
            ->map(function (AccommodationLease $lease) use ($today) {
                $accommodation = $lease->accommodation;
                $due = $lease->end_date->copy()->startOfDay();

                return $this->makeItem(
                    id: 'lease-'.$lease->id,
                    lane: 'leases',
                    due: $due,
                    today: $today,
                    title: $accommodation->name,
                    subtitle: $accommodation->address,
                    url: route('accommodations.edit', $accommodation),
                    badge: 'Najem',
                );
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function projectItems(Carbon $windowStart, Carbon $windowEnd, Carbon $today): Collection
    {
        return Project::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->orderBy('end_date')
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) use ($today) {
                $due = $project->end_date->copy()->startOfDay();

                return $this->makeItem(
                    id: 'project-'.$project->id,
                    lane: 'projects',
                    due: $due,
                    today: $today,
                    title: $project->name,
                    subtitle: 'Koniec projektu',
                    url: route('projects.show', $project),
                    badge: null,
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rotationItems(Carbon $windowStart, Carbon $windowEnd, Carbon $today): Collection
    {
        return Rotation::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->with('employee')
            ->orderBy('end_date')
            ->get()
            ->filter(fn (Rotation $rotation) => $rotation->employee !== null)
            ->map(function (Rotation $rotation) use ($today) {
                $due = $rotation->end_date->copy()->startOfDay();

                return $this->makeItem(
                    id: 'rotation-'.$rotation->id,
                    lane: 'rotations',
                    due: $due,
                    today: $today,
                    title: $rotation->employee->full_name,
                    subtitle: 'Koniec rotacji',
                    url: route('employees.rotations.edit', [
                        'employee' => $rotation->employee,
                        'rotation' => $rotation,
                    ]),
                    badge: null,
                );
            })
            ->values();
    }

    /**
     * Vehicles: OC / AC / inspection due on or before horizon end (includes all overdue).
     * One query for the fleet, then expand columns in memory (no 3× vehicle scan).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function vehicleItems(Carbon $windowEnd, Carbon $today): Collection
    {
        $specs = [
            ['column' => 'insurance_valid_to', 'badge' => 'OC'],
            ['column' => 'ac_wazne_do', 'badge' => 'AC'],
            ['column' => 'inspection_valid_to', 'badge' => 'Przegląd'],
        ];

        $lookbackStart = $today->copy()->subDays(self::OVERDUE_LOOKBACK_DAYS);
        $horizon = $windowEnd->toDateString();

        $vehicles = Vehicle::query()
            ->operational()
            ->where(function ($query) use ($specs, $horizon) {
                foreach ($specs as $i => $spec) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $query->{$method}(function ($inner) use ($spec, $horizon) {
                        $inner->whereNotNull($spec['column'])
                            ->where($spec['column'], '<=', $horizon);
                    });
                }
            })
            ->get(['id', 'registration_number', 'brand', 'model', 'insurance_valid_to', 'ac_wazne_do', 'inspection_valid_to']);

        $items = collect();

        foreach ($vehicles as $vehicle) {
            $subtitle = trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '')) ?: null;
            $url = route('vehicles.edit', $vehicle);

            foreach ($specs as $spec) {
                $due = $vehicle->{$spec['column']}?->copy()->startOfDay();
                if (! $due || $due->gt($windowEnd) || $due->lt($lookbackStart)) {
                    continue;
                }

                $items->push($this->makeItem(
                    id: 'vehicle-'.$vehicle->id.'-'.$spec['badge'],
                    lane: 'vehicles',
                    due: $due,
                    today: $today,
                    title: $vehicle->registration_number,
                    subtitle: $subtitle,
                    url: $url,
                    badge: $spec['badge'],
                ));
            }
        }

        return $items->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function makeItem(
        string $id,
        string $lane,
        Carbon $due,
        Carbon $today,
        string $title,
        ?string $subtitle,
        string $url,
        ?string $badge,
    ): array {
        $days = (int) $today->diffInDays($due, false);

        return [
            'id' => $id,
            'lane' => $lane,
            'due_on' => $due->toDateString(),
            'due_label' => $due->format('d.m.Y'),
            'days' => $days,
            'plot_offset' => max(-self::PLOT_OVERDUE_DAYS, min(self::FORWARD_DAYS, $days)),
            'overdue' => $days < 0,
            'urgency' => $this->urgencyForDays($days),
            'title' => $title,
            'subtitle' => $subtitle,
            'url' => $url,
            'badge' => $badge,
        ];
    }

    private function urgencyForDays(int $days): string
    {
        if ($days < 0) {
            return 'overdue';
        }
        if ($days <= 7) {
            return 'critical';
        }
        if ($days <= 14) {
            return 'soon';
        }

        return 'later';
    }

    /**
     * Future items: one cluster per lane+day.
     * Overdue items: one cluster per lane (no date split), pinned left of "dziś".
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function clusterItems(Collection $items): array
    {
        $overdue = $items->filter(fn (array $item) => ($item['days'] ?? 0) < 0);
        $upcoming = $items->filter(fn (array $item) => ($item['days'] ?? 0) >= 0);

        $overdueClusters = $overdue
            ->groupBy('lane')
            ->map(function (Collection $group, string $lane) {
                $count = $group->count();
                $worstDays = (int) $group->min('days');

                return [
                    'key' => $lane.'|overdue',
                    'lane' => $lane,
                    'due_on' => null,
                    'due_label' => 'Zaległe',
                    'days' => $worstDays,
                    'plot_offset' => (int) floor(-self::PLOT_OVERDUE_DAYS / 2),
                    'count' => $count,
                    'count_label' => $count >= 10 ? '9+' : (string) $count,
                    'show_count' => true,
                    'overdue' => true,
                    'urgency' => 'overdue',
                    'item_ids' => $group->pluck('id')->values()->all(),
                ];
            })
            ->values();

        $upcomingClusters = $upcoming
            ->groupBy(fn (array $item) => $item['lane'].'|'.$item['due_on'])
            ->map(function (Collection $group) {
                /** @var array<string, mixed> $first */
                $first = $group->first();
                $count = $group->count();
                $days = (int) $group->min('days');

                return [
                    'key' => $first['lane'].'|'.$first['due_on'],
                    'lane' => $first['lane'],
                    'due_on' => $first['due_on'],
                    'due_label' => $first['due_label'],
                    'days' => $days,
                    'plot_offset' => max(0, min(self::FORWARD_DAYS, $days)),
                    'count' => $count,
                    'count_label' => $count >= 10 ? '9+' : (string) $count,
                    'show_count' => $count > 1,
                    'overdue' => false,
                    'urgency' => $this->urgencyForDays($days),
                    'item_ids' => $group->pluck('id')->values()->all(),
                ];
            })
            ->values();

        return $overdueClusters
            ->merge($upcomingClusters)
            ->sortBy([
                ['days', 'asc'],
                ['lane', 'asc'],
            ])
            ->values()
            ->all();
    }
}
