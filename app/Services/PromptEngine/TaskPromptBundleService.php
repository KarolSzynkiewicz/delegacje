<?php

namespace App\Services\PromptEngine;

use App\Models\Comment;
use App\Models\ProjectTask;
use App\Services\UserMentionService;
use Carbon\Carbon;

class TaskPromptBundleService
{
    private const SUBTASK_REF_REGEX = '/(?<!\w)#(\d+)\b/u';

    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $tasks = ProjectTask::query()
            ->with([
                'assignedTo:id,name',
                'createdBy:id,name',
                'subtasks' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
                'comments' => fn ($q) => $q
                    ->with(['user:id,name'])
                    ->orderBy('created_at'),
            ])
            ->inPeriod($start, $end)
            ->orderBy('id')
            ->get();

        $displayNumbers = [];
        foreach ($tasks as $task) {
            $displayNumbers[$task->id] = $this->subtaskDisplayNumbers($task);
        }

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
            ],
            'counts' => [
                'tasks' => $tasks->count(),
            ],
            'tasks' => $tasks->map(fn (ProjectTask $task) => $this->serializeTask($task, $displayNumbers[$task->id] ?? []))->values()->all(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function subtaskDisplayNumbers(ProjectTask $task): array
    {
        $task->loadMissing('subtasks');
        $map = [];
        foreach ($task->subtasks->sortBy(['created_at', 'id'])->values() as $index => $subtask) {
            $map[$subtask->id] = $index + 1;
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $subtaskIdToDisplayNum
     * @return array<string, mixed>
     */
    private function serializeTask(ProjectTask $task, array $subtaskIdToDisplayNum): array
    {
        $subtasks = $task->subtasks->map(function ($st) use ($subtaskIdToDisplayNum) {
            return [
                'id' => $st->id,
                'display_number' => $subtaskIdToDisplayNum[$st->id] ?? null,
                'name' => $st->name,
                'is_completed' => (bool) $st->is_completed,
                'completed_at' => $st->completed_at?->toIso8601String(),
                'created_at' => $st->created_at?->toIso8601String(),
                'updated_at' => $st->updated_at?->toIso8601String(),
            ];
        })->values()->all();

        $comments = $task->comments
            ->sortBy('created_at')
            ->values()
            ->map(fn ($comment) => $this->serializeComment($comment))
            ->all();

        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->plainDescription(),
            'status' => $task->status?->value,
            'status_label' => $task->status?->label(),
            'priority' => $task->priority,
            'category' => $task->category,
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'subtasks_progress_percent' => $task->subtasks_progress,
            'assigned_to' => $task->assignedTo ? [
                'id' => $task->assignedTo->id,
                'name' => $task->assignedTo->name,
            ] : null,
            'created_by' => $task->createdBy ? [
                'id' => $task->createdBy->id,
                'name' => $task->createdBy->name,
            ] : null,
            'subtasks' => $subtasks,
            'comments' => $comments,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(Comment $comment): array
    {
        $body = (string) ($comment->body ?? '');
        $handles = UserMentionService::extractHandles($body);
        $mentions = [];
        foreach ($handles as $handle) {
            $user = UserMentionService::resolveUserByMentionHandle($handle);
            $mentions[] = [
                'handle' => $handle,
                'resolved_user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                ] : null,
            ];
        }

        preg_match_all(self::SUBTASK_REF_REGEX, $body, $m);
        $subtaskRefNumbers = array_values(array_unique(array_map('intval', $m[1] ?? [])));

        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'body' => $body,
            'created_at' => $comment->created_at?->toIso8601String(),
            'updated_at' => $comment->updated_at?->toIso8601String(),
            'author' => $comment->user ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ] : null,
            'mentions' => $mentions,
            'subtask_ref_numbers_in_body' => $subtaskRefNumbers,
        ];
    }
}
