<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\ProjectTask;
use App\Models\Sprint;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SprintInsights
{
    /**
     * @return array<string, mixed>
     */
    public function for(Sprint $sprint): array
    {
        $sprint->loadMissing(['tasks.assignedTo', 'milestones']);

        $tasks = $sprint->tasks;
        $inScope = $tasks->filter(fn (ProjectTask $task) => $task->status !== TaskStatus::CANCELLED);
        $done = $inScope->filter(fn (ProjectTask $task) => $task->status === TaskStatus::COMPLETED);
        $inProgress = $inScope->filter(fn (ProjectTask $task) => $task->status === TaskStatus::IN_PROGRESS);
        $pending = $inScope->filter(fn (ProjectTask $task) => $task->status === TaskStatus::PENDING);
        $cancelled = $tasks->filter(fn (ProjectTask $task) => $task->status === TaskStatus::CANCELLED);

        $today = Carbon::today();
        $start = $sprint->start_date->copy()->startOfDay();
        $end = $sprint->end_date->copy()->startOfDay();
        $daysTotal = max(1, $start->diffInDays($end) + 1);

        if ($today->lt($start)) {
            $daysElapsed = 0;
            $daysLeft = $start->diffInDays($today);
        } elseif ($today->gt($end)) {
            $daysElapsed = $daysTotal;
            $daysLeft = 0;
        } else {
            $daysElapsed = $start->diffInDays($today) + 1;
            $daysLeft = $today->diffInDays($end);
        }

        $scope = $inScope->count();
        $doneCount = $done->count();
        $progress = $scope > 0 ? (int) round(($doneCount / $scope) * 100) : 0;
        $idealProgress = (int) round(($daysElapsed / $daysTotal) * 100);

        $overdue = $inScope
            ->filter(fn (ProjectTask $task) => $task->status !== TaskStatus::COMPLETED
                && $task->due_date
                && $task->due_date->lt($today))
            ->count();

        $dueToday = $inScope
            ->filter(fn (ProjectTask $task) => $task->status !== TaskStatus::COMPLETED
                && $task->due_date
                && $task->due_date->isSameDay($today))
            ->count();

        $dueSoon = $inScope
            ->filter(fn (ProjectTask $task) => $task->status !== TaskStatus::COMPLETED
                && $task->due_date
                && $task->due_date->gte($today)
                && $task->due_date->lte($today->copy()->addDays(3)))
            ->count();

        $unassigned = $inScope->filter(fn (ProjectTask $task) => $task->assigned_to === null)->count();
        $noDueDate = $inScope
            ->filter(fn (ProjectTask $task) => $task->status !== TaskStatus::COMPLETED && $task->due_date === null)
            ->count();

        $scopeAdded = $tasks
            ->filter(fn (ProjectTask $task) => $task->created_at && $task->created_at->gt($start->copy()->endOfDay()))
            ->count();

        $completedToday = $done
            ->filter(fn (ProjectTask $task) => $task->completed_at && $task->completed_at->isSameDay($today))
            ->count();

        $remaining = $scope - $doneCount;
        $velocity = $daysElapsed > 0 ? $doneCount / $daysElapsed : 0;
        $forecastFinish = null;
        if ($remaining === 0) {
            $forecastFinish = $today->toDateString();
        } elseif ($velocity > 0) {
            $forecastFinish = $today->copy()->addDays((int) ceil($remaining / $velocity))->toDateString();
        }

        $health = $this->health(
            $sprint,
            $progress,
            $idealProgress,
            $overdue,
            $remaining,
            $daysLeft,
        );

        $milestones = $sprint->milestones;
        $milestoneDone = $milestones->filter(fn ($m) => $m->completed_at !== null)->count();

        $payload = [
            'total' => $tasks->count(),
            'scope' => $scope,
            'done' => $doneCount,
            'in_progress' => $inProgress->count(),
            'pending' => $pending->count(),
            'cancelled' => $cancelled->count(),
            'remaining' => $remaining,
            'progress' => $progress,
            'ideal_progress' => $idealProgress,
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'due_soon' => $dueSoon,
            'unassigned' => $unassigned,
            'no_due_date' => $noDueDate,
            'scope_added' => $scopeAdded,
            'completed_today' => $completedToday,
            'days_total' => $daysTotal,
            'days_elapsed' => $daysElapsed,
            'days_left' => $daysLeft,
            'starts_in' => $today->lt($start) ? $start->diffInDays($today) : 0,
            'health' => $health,
            'coach' => $this->coach($health, $sprint, $progress, $idealProgress, $overdue, $unassigned, $remaining, $daysLeft, $forecastFinish),
            'forecast_finish' => $forecastFinish,
            'velocity' => round($velocity, 2),
            'burndown' => $this->burndown($sprint, $inScope, $start, $end, $today, $daysTotal),
            'status_chart' => [
                'labels' => ['Oczekujące', 'W trakcie', 'Zakończone', 'Anulowane'],
                'values' => [$pending->count(), $inProgress->count(), $doneCount, $cancelled->count()],
                'colors' => ['#f59e0b', '#3b82f6', '#10b981', '#64748b'],
            ],
            'workload' => $this->workload($inScope),
            'milestones_done' => $milestoneDone,
            'milestones_total' => $milestones->count(),
        ];

        $payload['revision'] = substr(md5(json_encode([
            $payload['progress'],
            $payload['status_chart']['values'],
            $payload['burndown'],
            $payload['workload'],
            $payload['milestones_done'],
        ])), 0, 10);

        return $payload;
    }

    /**
     * @param  Collection<int, ProjectTask>  $inScope
     * @return array{labels: list<string>, ideal: list<float>, actual: list<float|null>}
     */
    private function burndown(Sprint $sprint, Collection $inScope, Carbon $start, Carbon $end, Carbon $today, int $daysTotal): array
    {
        $scope = $inScope->count();
        $labels = [];
        $ideal = [];
        $actual = [];

        $cursor = $start->copy();
        $index = 0;
        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('d.m');
            $ideal[] = round($scope * (1 - (($index + 1) / $daysTotal)), 2);

            if ($cursor->gt($today)) {
                $actual[] = null;
            } else {
                $dayEnd = $cursor->copy()->endOfDay();
                $actual[] = $inScope
                    ->filter(function (ProjectTask $task) use ($dayEnd) {
                        if ($task->status !== TaskStatus::COMPLETED || ! $task->completed_at) {
                            return true;
                        }

                        return $task->completed_at->gt($dayEnd);
                    })
                    ->count();
            }

            $cursor->addDay();
            $index++;
        }

        return compact('labels', 'ideal', 'actual');
    }

    /**
     * @param  Collection<int, ProjectTask>  $inScope
     * @return list<array{name: string, total: int, done: int}>
     */
    private function workload(Collection $inScope): array
    {
        return $inScope
            ->groupBy(fn (ProjectTask $task) => $task->assigned_to ?: 0)
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'name' => $first?->assignedTo?->name ?? 'Nieprzypisane',
                    'total' => $group->count(),
                    'done' => $group->filter(fn (ProjectTask $task) => $task->status === TaskStatus::COMPLETED)->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function health(
        Sprint $sprint,
        int $progress,
        int $idealProgress,
        int $overdue,
        int $remaining,
        int $daysLeft,
    ): string {
        if ($sprint->isScheduled()) {
            return 'upcoming';
        }

        if ($sprint->isPast()) {
            return $remaining === 0 ? 'done' : 'unfinished';
        }

        if ($remaining === 0) {
            return 'on_track';
        }

        if ($overdue >= 3 || ($idealProgress - $progress) >= 25) {
            return 'off_track';
        }

        if ($overdue > 0 || ($idealProgress - $progress) >= 12 || ($daysLeft <= 2 && $remaining > 0)) {
            return 'at_risk';
        }

        return 'on_track';
    }

    private function coach(
        string $health,
        Sprint $sprint,
        int $progress,
        int $idealProgress,
        int $overdue,
        int $unassigned,
        int $remaining,
        int $daysLeft,
        ?string $forecastFinish,
    ): string {
        return match ($health) {
            'upcoming' => 'Sprint jeszcze nie wystartował. Ułóż kolejność i kamienie milowe, zanim wejdziecie w scope.',
            'done' => 'Sprint domknięty — wszystkie zadania w zakresie są skończone.',
            'unfinished' => 'Termin minął, a w zakresie zostało '.$remaining.' otwartych zadań. Czas na retrospekcję albo przeniesienie reszty.',
            'off_track' => $overdue > 0
                ? 'Tempo nie dogania zakresu: '.$overdue.' po terminie. Ściągnij scope albo odblokuj wąskie gardło.'
                : 'Jesteście '.$progress.'% przy idealnym '.$idealProgress.'%. Bez korekty nie domkniecie sprintu.',
            'at_risk' => $overdue > 0
                ? 'Uwaga: '.$overdue.' przeterminowanych'.($unassigned ? ', '.$unassigned.' bez osoby' : '').'. Dziś priorytet to odblokowanie, nie nowe rzeczy.'
                : 'Lekkie opóźnienie względem burndownu ('.$progress.'% vs '.$idealProgress.'%). Zostało '.$daysLeft.' dni.',
            default => $forecastFinish
                ? 'Kurs trzymany ('.$progress.'%). Przy obecnym tempie domknięcie wychodzi na '.$this->formatForecast($forecastFinish).'.'
                : 'Jesteście na kursie. '.$remaining.' zadań, '.$daysLeft.' dni — trzymajcie kolejność z góry listy.',
        };
    }

    private function formatForecast(string $date): string
    {
        return Carbon::parse($date)->format('d.m');
    }
}
