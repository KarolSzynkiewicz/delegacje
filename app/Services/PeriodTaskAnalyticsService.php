<?php

namespace App\Services;

use App\Enums\CommentableType;
use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PeriodTaskAnalyticsService
{
    /**
     * Zwarty raport okresu: KPI i wskaźniki współpracy, bez ciał komentarzy.
     *
     * @return array<string, mixed>
     */
    public function build(Carbon $start, Carbon $end, int $staleDays = 7): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $tasks = ProjectTask::query()
            ->with(['assignedTo:id,name', 'createdBy:id,name'])
            ->inPeriod($start, $end)
            ->orderBy('id')
            ->get();

        $taskIds = $tasks->pluck('id')->all();
        $now = now();

        $comments = $taskIds === []
            ? collect()
            : Comment::query()
                ->with('user:id,name')
                ->where('commentable_type', CommentableType::PROJECT_TASK)
                ->whereIn('commentable_id', $taskIds)
                ->orderBy('created_at')
                ->get();

        $commentsInPeriod = $comments->filter(
            fn (Comment $c) => $c->created_at && $c->created_at->between($start, $end)
        );

        $subtaskCount = $taskIds === []
            ? 0
            : TaskSubtask::query()->whereIn('task_id', $taskIds)->count();

        $byStatus = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        $byAssignee = [];
        $assigneeCompleted = [];
        $assigneeComments = [];
        $byCategory = [];
        $categoryCompleted = [];
        $weekdays = [0, 0, 0, 0, 0, 0, 0];
        $hourBuckets = [0, 0, 0, 0, 0, 0];
        $lifetimeByPerson = [];

        foreach ($tasks as $task) {
            $status = $task->status?->value ?? TaskStatus::PENDING->value;
            if (isset($byStatus[$status])) {
                $byStatus[$status]++;
            }

            $assigneeName = $task->assignedTo?->name ?? '—';
            $this->bump($byAssignee, $assigneeName);
            if ($status === TaskStatus::COMPLETED->value) {
                $this->bump($assigneeCompleted, $assigneeName);
            }

            $category = $task->category !== null && $task->category !== ''
                ? $task->category
                : 'Bez kategorii';
            $this->bump($byCategory, $category);
            if ($status === TaskStatus::COMPLETED->value) {
                $this->bump($categoryCompleted, $category);
            }

            if ($task->created_at) {
                $dow = (int) $task->created_at->dayOfWeekIso; // 1=Mon … 7=Sun
                $weekdays[$dow - 1]++;
                $hourBuckets[(int) floor($task->created_at->hour / 4)]++;
            }

            if ($task->completed_at && $task->created_at && $task->assignedTo) {
                $days = $task->created_at->diffInSeconds($task->completed_at) / 86400;
                if ($days >= 0) {
                    $lifetimeByPerson[$assigneeName][] = $days;
                }
            }
        }

        $commentsPerTask = $comments->groupBy('commentable_id');
        $commentsInPeriodPerTask = $commentsInPeriod->groupBy('commentable_id');

        foreach ($tasks as $task) {
            $assigneeName = $task->assignedTo?->name ?? '—';
            $this->bump(
                $assigneeComments,
                $assigneeName,
                $commentsPerTask->get($task->id, collect())->count()
            );
        }

        $hottest = $tasks
            ->map(function (ProjectTask $task) use ($commentsInPeriodPerTask, $commentsPerTask) {
                $inPeriod = $commentsInPeriodPerTask->get($task->id, collect());
                $all = $commentsPerTask->get($task->id, collect());
                $last = $inPeriod->last() ?? $all->last();

                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'comments_in_period' => $inPeriod->count(),
                    'comments_total' => $all->count(),
                    'last_comment_at' => $last?->created_at?->toIso8601String(),
                    'participants' => $inPeriod
                        ->map(fn (Comment $c) => $c->user?->name)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $row) => $row['comments_in_period'] > 0 || $row['comments_total'] > 0)
            ->sortByDesc('comments_in_period')
            ->take(8)
            ->values()
            ->all();

        $stale = $tasks
            ->filter(fn (ProjectTask $task) => ! in_array($task->status, [TaskStatus::COMPLETED, TaskStatus::CANCELLED], true))
            ->map(function (ProjectTask $task) use ($now) {
                $days = $task->updated_at ? (int) $task->updated_at->diffInDays($now) : 0;

                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'status' => $task->status?->value,
                    'assigned_to' => $task->assignedTo?->name,
                    'days_since_activity' => $days,
                    'updated_at' => $task->updated_at?->toIso8601String(),
                ];
            })
            ->filter(fn (array $row) => $row['days_since_activity'] >= $staleDays)
            ->sortByDesc('days_since_activity')
            ->take(12)
            ->values()
            ->all();

        $lifetime = collect($lifetimeByPerson)
            ->map(fn (array $days, string $name) => [
                'name' => $name,
                'avg' => round(array_sum($days) / count($days), 1),
                'max' => round(max($days), 1),
                'count' => count($days),
            ])
            ->sortBy('avg')
            ->values()
            ->all();

        return [
            'meta' => [
                'schema_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'start_at' => $start->toIso8601String(),
                    'end_at' => $end->toIso8601String(),
                ],
                'stale_days' => $staleDays,
            ],
            'kpis' => [
                'tasks' => $tasks->count(),
                'by_status' => $byStatus,
                'comments' => $commentsInPeriod->count(),
                'comments_total_on_tasks' => $comments->count(),
                'subtasks' => $subtaskCount,
            ],
            'by_assignee' => $this->namedCounts($byAssignee, $assigneeCompleted, $assigneeComments),
            'by_category' => collect($byCategory)
                ->map(fn (int $total, string $name) => [
                    'name' => $name,
                    'total' => $total,
                    'completed' => $categoryCompleted[$name] ?? 0,
                ])
                ->sortByDesc('total')
                ->values()
                ->all(),
            'lifetime' => $lifetime,
            'temporal' => [
                'created_by_weekday' => [
                    'labels' => ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Niedz'],
                    'values' => $weekdays,
                ],
                'created_by_hour_bucket' => [
                    'labels' => ['0–4', '4–8', '8–12', '12–16', '16–20', '20–24'],
                    'values' => $hourBuckets,
                ],
            ],
            'hottest_threads' => $hottest,
            'stale' => $stale,
            'collaboration' => [
                'comments' => $this->commentMatrix($commentsInPeriod, $tasks),
                'delegation' => $this->delegationMatrix($tasks),
                'mentions' => $this->mentionsReceived($commentsInPeriod),
                'comments_by_author' => $this->countBy(
                    $commentsInPeriod,
                    fn (Comment $c) => $c->user?->name ?? '—'
                ),
                'subtask_help' => $this->subtaskHelp($taskIds, $tasks),
            ],
            'pointers' => [
                'hottest_task_ids' => array_column($hottest, 'id'),
                'stale_task_ids' => array_column($stale, 'id'),
            ],
        ];
    }

    /**
     * @param  array<string, int>  $totals
     * @param  array<string, int>  $completed
     * @param  array<string, int>  $comments
     * @return list<array{name: string, total: int, completed: int, comments: int}>
     */
    private function namedCounts(array $totals, array $completed, array $comments): array
    {
        return collect($totals)
            ->map(fn (int $total, string $name) => [
                'name' => $name,
                'total' => $total,
                'completed' => $completed[$name] ?? 0,
                'comments' => $comments[$name] ?? 0,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Comment>  $comments
     * @param  Collection<int, ProjectTask>  $tasks
     * @return list<array{author: string, task_owner: string, comments: int}>
     */
    private function commentMatrix(Collection $comments, Collection $tasks): array
    {
        $owners = $tasks->mapWithKeys(
            fn (ProjectTask $task) => [$task->id => $task->assignedTo?->name ?? '—']
        );

        $counts = [];
        foreach ($comments as $comment) {
            $author = $comment->user?->name ?? '—';
            $owner = $owners[$comment->commentable_id] ?? '—';
            $key = $author.'|'.$owner;
            $counts[$key] = ($counts[$key] ?? ['author' => $author, 'task_owner' => $owner, 'comments' => 0]);
            $counts[$key]['comments']++;
        }

        return collect($counts)->sortByDesc('comments')->values()->all();
    }

    /**
     * @param  Collection<int, ProjectTask>  $tasks
     * @return list<array{creator: string, assignee: string, tasks: int}>
     */
    private function delegationMatrix(Collection $tasks): array
    {
        $counts = [];
        foreach ($tasks as $task) {
            $creator = $task->createdBy?->name ?? '—';
            $assignee = $task->assignedTo?->name ?? '—';
            $key = $creator.'|'.$assignee;
            $counts[$key] = ($counts[$key] ?? ['creator' => $creator, 'assignee' => $assignee, 'tasks' => 0]);
            $counts[$key]['tasks']++;
        }

        return collect($counts)->sortByDesc('tasks')->values()->all();
    }

    /**
     * @param  Collection<int, Comment>  $comments
     * @return list<array{name: string, mentions: int}>
     */
    private function mentionsReceived(Collection $comments): array
    {
        $counts = [];
        foreach ($comments as $comment) {
            foreach (UserMentionService::extractHandles((string) ($comment->body ?? '')) as $handle) {
                $user = UserMentionService::resolveUserByMentionHandle($handle);
                $name = $user?->name ?? $handle;
                $this->bump($counts, $name);
            }
        }

        return collect($counts)
            ->map(fn (int $n, string $name) => ['name' => $name, 'mentions' => $n])
            ->sortByDesc('mentions')
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $taskIds
     * @param  Collection<int, ProjectTask>  $tasks
     * @return list<array{helper: string, task_owner: string, completions: int}>
     */
    private function subtaskHelp(array $taskIds, Collection $tasks): array
    {
        if ($taskIds === []) {
            return [];
        }

        $owners = $tasks->mapWithKeys(
            fn (ProjectTask $task) => [$task->id => $task->assignedTo?->name ?? '—']
        );

        $events = TaskSubtaskEvent::query()
            ->with(['user:id,name', 'subtask:id,task_id'])
            ->where('event', 'completed')
            ->whereHas('subtask', fn ($q) => $q->whereIn('task_id', $taskIds))
            ->get();

        $counts = [];
        foreach ($events as $event) {
            $taskId = $event->subtask?->task_id;
            if (! $taskId) {
                continue;
            }
            $helper = $event->user?->name ?? '—';
            $owner = $owners[$taskId] ?? '—';
            if ($helper === $owner) {
                continue;
            }
            $key = $helper.'|'.$owner;
            $counts[$key] = ($counts[$key] ?? ['helper' => $helper, 'task_owner' => $owner, 'completions' => 0]);
            $counts[$key]['completions']++;
        }

        return collect($counts)->sortByDesc('completions')->values()->all();
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return list<array{name: string, count: int}>
     */
    private function countBy(Collection $items, callable $key): array
    {
        return $items
            ->groupBy($key)
            ->map(fn (Collection $group, $name) => ['name' => (string) $name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, int>  $map
     */
    private function bump(array &$map, string $key, int $n = 1): void
    {
        $map[$key] = ($map[$key] ?? 0) + $n;
    }
}
