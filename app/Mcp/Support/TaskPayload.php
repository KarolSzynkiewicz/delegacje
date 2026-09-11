<?php

namespace App\Mcp\Support;

use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Services\UserMentionService;

class TaskPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function listItem(ProjectTask $task): array
    {
        $task->loadMissing(['assignedTo:id,name', 'createdBy:id,name', 'sprint:id,name']);

        return [
            'id' => $task->id,
            'name' => $task->name,
            'status' => $task->status?->value,
            'status_label' => $task->status?->label(),
            'category' => $task->category,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'comments_count' => (int) ($task->comments_count ?? $task->comments()->count()),
            'subtasks_progress_percent' => self::progress($task),
            'assigned_to' => self::user($task->assignedTo),
            'created_by' => self::user($task->createdBy),
            'sprint' => $task->sprint ? [
                'id' => $task->sprint->id,
                'name' => $task->sprint->name,
            ] : null,
            'url' => route('tasks.show', $task),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(ProjectTask $task): array
    {
        $task->loadMissing([
            'assignedTo:id,name',
            'createdBy:id,name',
            'sprint:id,name',
            'subtasks.assignedTo:id,name',
            'comments' => fn ($q) => $q->with('user:id,name')->orderByDesc('id')->limit(3),
        ]);

        $recent = $task->comments->sortByDesc('id')->take(3)->values();

        return [
            ...self::listItem($task),
            'description' => $task->plainDescription(),
            'subtasks' => $task->subtasks
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(fn (TaskSubtask $st) => self::subtask($st))
                ->all(),
            'recent_comments' => $recent->map(fn (Comment $comment) => self::comment($comment, true, 200))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function subtask(TaskSubtask $subtask): array
    {
        $subtask->loadMissing(['assignedTo:id,name', 'task:id,name']);

        return [
            'id' => $subtask->id,
            'task_id' => $subtask->task_id,
            'name' => $subtask->name,
            'is_completed' => (bool) $subtask->is_completed,
            'completed_at' => $subtask->completed_at?->toIso8601String(),
            'sort_order' => $subtask->sort_order,
            'assigned_to' => self::user($subtask->assignedTo),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function comment(Comment $comment, bool $excerpt = false, int $excerptLength = 200): array
    {
        $comment->loadMissing('user:id,name');
        $body = (string) ($comment->body ?? '');

        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'body' => $excerpt ? self::truncate($body, $excerptLength) : $body,
            'created_at' => $comment->created_at?->toIso8601String(),
            'updated_at' => $comment->updated_at?->toIso8601String(),
            'author' => self::user($comment->user),
            'mentions' => self::commentMentions($body),
        ];
    }

    /**
     * @return list<array{handle: string, kind: string, resolved_user: array{id: int, name: string}|null}>
     */
    public static function commentMentions(string $body): array
    {
        preg_match_all(UserMentionService::MENTION_REGEX, $body, $matches, PREG_SET_ORDER);
        $mentions = [];
        $seen = [];

        foreach ($matches as $match) {
            $handle = $match[1];
            $kind = match ($match[2] ?? '') {
                '!' => 'task',
                '?' => 'approval',
                default => 'notify',
            };
            $key = mb_strtolower($handle, 'UTF-8').':'.$kind;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $mentions[] = [
                'handle' => $handle,
                'kind' => $kind,
                'resolved_user' => self::user(UserMentionService::resolveUserByMentionHandle($handle)),
            ];
        }

        return $mentions;
    }

    /**
     * @return array{task_mentions: list<array<string, mixed>>, approval_requests: list<array<string, mixed>>}
     */
    public static function commentSideEffects(Comment $comment): array
    {
        $comment->loadMissing([
            'mentions.assignedTo:id,name',
            'approvalRequests.approver:id,name',
        ]);

        return [
            'task_mentions' => $comment->mentions
                ->map(fn ($mention) => [
                    'id' => $mention->id,
                    'title' => $mention->title,
                    'status' => $mention->status?->value,
                    'assigned_to' => self::user($mention->assignedTo),
                ])
                ->values()
                ->all(),
            'approval_requests' => $comment->approvalRequests
                ->map(fn ($approval) => [
                    'id' => $approval->id,
                    'name' => $approval->name,
                    'approver' => self::user($approval->approver),
                    'url' => route('approval-requests.show', $approval),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    public static function user(?\App\Models\User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    private static function progress(ProjectTask $task): float
    {
        if (isset($task->subtasks_count)) {
            $total = (int) $task->subtasks_count;
            if ($total === 0) {
                return 0.0;
            }

            $done = (int) ($task->completed_subtasks_count ?? 0);

            return round(($done / $total) * 100, 2);
        }

        return $task->subtasks_progress;
    }

    private static function truncate(string $body, int $max): string
    {
        $body = trim(preg_replace('/\s+/u', ' ', $body) ?? '');

        if (mb_strlen($body) <= $max) {
            return $body;
        }

        return mb_substr($body, 0, $max - 1).'…';
    }
}
