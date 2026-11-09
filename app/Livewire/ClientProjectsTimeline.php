<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectDemand;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Oś zapotrzebowania klienta: tory = projekty, paski = agregat osób (suma required_count).
 * Tylko podgląd — edycja w karcie projektu.
 */
class ClientProjectsTimeline extends Component
{
    public const WEEK_WIDTH = 64;

    public const AXIS_PAD_WEEKS = 2;

    public Client $client;

    public function mount(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @return array<string, mixed>
     */
    public function board(): array
    {
        /** @var Collection<int, Project> $projects */
        $projects = $this->client->projects()
            ->with(['demands.role'])
            ->orderBy('name')
            ->get();

        $range = $this->portfolioRange($projects);
        $portfolioStart = $range['start'];
        $portfolioEnd = $range['end'];

        $axisStart = $portfolioStart->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(self::AXIS_PAD_WEEKS);
        $axisEnd = $portfolioEnd->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()->addWeeks(self::AXIS_PAD_WEEKS);
        $weekCount = max(1, (int) floor($axisStart->diffInDays($axisEnd) / 7) + 1);

        $todayWeek = now()->startOfWeek(Carbon::MONDAY);
        $portfolioWeekStart = $portfolioStart->copy()->startOfWeek(Carbon::MONDAY);
        $portfolioWeekEnd = $portfolioEnd->copy()->startOfWeek(Carbon::MONDAY);

        $ticks = [];
        for ($i = 0; $i < $weekCount; $i++) {
            $week = $axisStart->copy()->addWeeks($i);
            $weekEnd = $week->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
            $iso = $week->isoWeek();
            $ticks[] = [
                'left' => $i * self::WEEK_WIDTH,
                'label' => 'T'.$iso,
                'sub' => $week->format('j.m'),
                'title' => 'Tydzień '.$iso.' · '.$week->format('d.m').'–'.$weekEnd->format('d.m.Y'),
                'is_current' => $week->equalTo($todayWeek),
                'in_project' => $week->betweenIncluded($portfolioWeekStart, $portfolioWeekEnd),
            ];
        }

        $markers = [
            [
                'kind' => 'project-start',
                'left' => $this->weekOffsetPx($axisStart, $portfolioStart),
                'label' => 'Od '.$portfolioStart->format('j.m'),
            ],
            [
                'kind' => 'project-end',
                'left' => $this->weekOffsetPx($axisStart, $portfolioEnd) + self::WEEK_WIDTH - 2,
                'label' => 'Do '.$portfolioEnd->format('j.m'),
            ],
        ];

        $today = now()->startOfDay();
        if ($today->betweenIncluded($axisStart, $axisEnd)) {
            $markers[] = [
                'kind' => 'today',
                'left' => $this->weekOffsetPx($axisStart, $today) + (int) round(self::WEEK_WIDTH / 2),
                'label' => 'Dziś',
            ];
        }

        $allDemands = $projects->flatMap(fn (Project $project) => $project->demands);
        $lanes = $projects->map(function (Project $project) use ($axisStart, $weekCount) {
            $weekly = $this->weeklyCounts($axisStart, $weekCount, $project->demands);
            $peak = $weekly === [] ? 0 : max($weekly);
            $bars = $this->barsFromWeekly($weekly, $axisStart);

            return [
                'key' => 'project-'.$project->id,
                'project_id' => $project->id,
                'label' => $project->name,
                'url' => route('projects.show', $project),
                'peak' => $peak,
                'empty' => $bars === [],
                'rows' => 1,
                'bars' => $bars,
            ];
        })->values()->all();

        $aggregate = $this->buildAggregate($axisStart, $weekCount, $allDemands);

        return [
            'start' => $axisStart->toDateString(),
            'end' => $axisEnd->toDateString(),
            'portfolio_start' => $portfolioStart->toDateString(),
            'portfolio_end' => $portfolioEnd->toDateString(),
            'week_count' => $weekCount,
            'week_width' => self::WEEK_WIDTH,
            'ticks' => $ticks,
            'markers' => $markers,
            'lanes' => $lanes,
            'aggregate' => $aggregate,
            'project_count' => $projects->count(),
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array{start: Carbon, end: Carbon}
     */
    protected function portfolioRange(Collection $projects): array
    {
        $starts = [];
        $ends = [];

        foreach ($projects as $project) {
            $start = $project->start_date?->copy()->startOfDay()
                ?? $project->demands->min(fn ($d) => $d->start_date)?->copy()->startOfDay();
            $end = $project->end_date?->copy()->startOfDay()
                ?? $project->demands
                    ->map(fn ($d) => $d->end_date ?? $d->start_date)
                    ->filter()
                    ->max()
                    ?->copy()
                    ->startOfDay();

            if ($start) {
                $starts[] = $start;
            }
            if ($end) {
                $ends[] = $end;
            } elseif ($start) {
                $ends[] = $start->copy()->addWeeks(8);
            }
        }

        $portfolioStart = $starts !== []
            ? collect($starts)->min()->copy()
            : now()->startOfWeek(Carbon::MONDAY);
        $portfolioEnd = $ends !== []
            ? collect($ends)->max()->copy()
            : $portfolioStart->copy()->addWeeks(12);

        if ($portfolioEnd->lt($portfolioStart)) {
            $portfolioEnd = $portfolioStart->copy()->addWeeks(8);
        }

        return [
            'start' => $portfolioStart,
            'end' => $portfolioEnd,
        ];
    }

    /**
     * @param  Collection<int, ProjectDemand>  $demands
     * @return list<int>
     */
    protected function weeklyCounts(Carbon $axisStart, int $weekCount, Collection $demands): array
    {
        $counts = array_fill(0, $weekCount, 0);

        for ($i = 0; $i < $weekCount; $i++) {
            $weekMonday = $axisStart->copy()->addWeeks($i);
            $weekSunday = $weekMonday->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();

            foreach ($demands as $demand) {
                $start = $demand->start_date?->copy()->startOfDay();
                if (! $start) {
                    continue;
                }
                $end = $demand->end_date?->copy()->startOfDay() ?? $start->copy();
                if ($end->lt($start)) {
                    $end = $start->copy();
                }
                if ($start->lte($weekSunday) && $end->gte($weekMonday)) {
                    $counts[$i] += (int) $demand->required_count;
                }
            }
        }

        return $counts;
    }

    /**
     * Scal tygodnie o tej samej liczbie osób w ciągłe paski.
     *
     * @param  list<int>  $weekly
     * @return list<array{id: string, start: string, end: string, left: int, width: int, label: string, count: int, row: int}>
     */
    protected function barsFromWeekly(array $weekly, Carbon $axisStart): array
    {
        $bars = [];
        $i = 0;
        $n = count($weekly);

        while ($i < $n) {
            $count = $weekly[$i];
            if ($count <= 0) {
                $i++;
                continue;
            }

            $startIdx = $i;
            while ($i + 1 < $n && $weekly[$i + 1] === $count) {
                $i++;
            }
            $endIdx = $i;

            $startWeek = $axisStart->copy()->addWeeks($startIdx);
            $endWeek = $axisStart->copy()->addWeeks($endIdx);

            $bars[] = [
                'id' => 'seg-'.$startIdx.'-'.$endIdx.'-'.$count,
                'start' => $startWeek->toDateString(),
                'end' => $endWeek->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()->toDateString(),
                'left' => $startIdx * self::WEEK_WIDTH,
                'width' => ($endIdx - $startIdx + 1) * self::WEEK_WIDTH,
                'label' => $count.' os.',
                'count' => $count,
                'row' => 0,
            ];
            $i++;
        }

        return $bars;
    }

    /**
     * @param  Collection<int, ProjectDemand>  $demands
     * @return array{max: int, columns: list<array{left: int, count: int, height: float, label: string}>}
     */
    protected function buildAggregate(Carbon $axisStart, int $weekCount, Collection $demands): array
    {
        $weekly = $this->weeklyCounts($axisStart, $weekCount, $demands);
        $max = $weekly === [] ? 0 : max($weekly);
        $columns = [];

        for ($i = 0; $i < $weekCount; $i++) {
            $count = $weekly[$i] ?? 0;
            $weekMonday = $axisStart->copy()->addWeeks($i);
            $weekSunday = $weekMonday->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
            $iso = $weekMonday->isoWeek();
            $columns[] = [
                'left' => $i * self::WEEK_WIDTH,
                'count' => $count,
                'height' => $max > 0
                    ? max($count > 0 ? 12.0 : 0.0, ($count / $max) * 100)
                    : 0.0,
                'label' => 'T'.$iso.' · '.$weekMonday->format('d.m').'–'.$weekSunday->format('d.m'),
            ];
        }

        return [
            'max' => $max,
            'columns' => $columns,
        ];
    }

    protected function weekOffsetPx(Carbon $axisStart, Carbon $date): int
    {
        $week = $date->copy()->startOfWeek(Carbon::MONDAY);
        $idx = max(0, (int) floor($axisStart->diffInDays($week) / 7));

        return $idx * self::WEEK_WIDTH;
    }

    public function render()
    {
        return view('livewire.client-projects-timeline', [
            'board' => $this->board(),
        ]);
    }
}
