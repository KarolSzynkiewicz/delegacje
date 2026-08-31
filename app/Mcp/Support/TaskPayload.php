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
                ->map(fn (TaskSubtask $st) => [
                    'id' => $st->id,
                    'name' => $st->name,
                    'is_completed' => (bool) $st->is_completed,
                    'completed_at' => $st->completed_at?->toIso8601String(),
                    'assigned_to' => self::user($st->assignedTo),
                ])
                ->all(),
            'recent_comments' => $recent->map(fn (Comment $comment) => self::comment($comment, true, 200))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function comment(Comment $comment, bool $excerpt = false, int $excerptLength = 200): array
    {
        $comment->loadMissing('user:id,name');
        $body = (string) ($comment->body ?? '');
        $handles = UserMentionService::extractHandles($body);
        $mentions = [];
        foreach ($handles as $handle) {
            $user = UserMentionService::resolveUserByMentionHandle($handle);
            $mentions[] = [
                'handle' => $handle,
                'resolved_user' => self::user($user),
            ];
        }

        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'body' => $excerpt ? self::truncate($body, $excerptLength) : $body,
            'created_at' => $comment->created_at?->toIso8601String(),
            'updated_at' => $comment->updated_at?->toIso8601String(),
            'author' => self::user($comment->user),
            'mentions' => $mentions,
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
