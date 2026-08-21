<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\SprintMilestone;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Jedna linia czasu sprintu: audyt + starsze zdarzenia, których wcześniej nie logowaliśmy.
 *
 * @phpstan-type ActivityEntry array{
 *     at: Carbon,
 *     actor: string,
 *     verb: string,
 *     subject: ?string,
 *     url: ?string,
 *     detail: ?string,
 *     icon: string,
 *     tone: string,
 *     kind: string
 * }
 */
final class SprintActivityFeed
{
    /** @var list<string> */
    private const NOISE_KEYS = [
        'id',
        'created_at',
        'updated_at',
        'created_by',
        'uploaded_by',
        'user_id',
        'sort_order',
        'file_path',
        'commentable_type',
        'commentable_id',
        'attachable_type',
        'attachable_id',
        'parent_id',
        'position',
        'mime_type',
        'size',
        'disk',
    ];

    /** @var array<string, true> */
    private array $auditKeys = [];

    /** @var array<int, string> */
    private array $userNames = [];

    /** @var array<int, string> */
    private array $taskNames = [];

    /** @var array<int, string> */
    private array $subtaskNames = [];

    /** @var array<int, int> */
    private array $subtaskTaskIds = [];

    /** @var array<int, string> */
    private array $sprintNames = [];

    /** @var Collection<int, ProjectTask> */
    private Collection $relatedTasks;

    /** @var Collection<int, TaskSubtask> */
    private Collection $relatedSubtasks;

    /** @var Collection<int, Comment> */
    private Collection $relatedComments;

    /** @var Collection<int, SprintMilestone> */
    private Collection $relatedMilestones;

    /**
     * @return Collection<int, ActivityEntry>
     */
    public function for(Sprint $sprint, int $limit = 120): Collection
    {
        $this->auditKeys = [];
        $this->userNames = [];
        $this->taskNames = [];
        $this->subtaskNames = [];
        $this->subtaskTaskIds = [];
        $this->sprintNames = [$sprint->id => $sprint->name];
        $this->relatedTasks = collect();
        $this->relatedSubtasks = collect();
        $this->relatedComments = collect();
        $this->relatedMilestones = collect();

        $related = $this->gatherRelated($sprint);

        $logs = $this->auditLogs($sprint, $related);
        foreach ($logs as $log) {
            $this->rememberSnapshotNames($log);
            $this->auditKeys[$this->key($log->auditable_type, (int) $log->auditable_id, $log->event)] = true;
        }
        $this->preloadLookupsFromLogs($logs);

        $entries = $logs
            ->map(fn (AuditLog $log) => $this->fromAudit($log, $sprint))
            ->filter()
            ->concat($this->synthetics($sprint, $related));

        return $entries
            ->sortByDesc(fn (array $entry) => $entry['at']->getTimestamp())
            ->values()
            ->take($limit)
            ->values();
    }

    /**
     * @return array{taskIds: list<int>, subtaskIds: list<int>, commentIds: list<int>, milestoneIds: list<int>, attachmentIds: list<int>}
     */
    private function gatherRelated(Sprint $sprint): array
    {
        $taskIds = $sprint->tasks()->pluck('id')->all();

        $movedTaskIds = AuditLog::query()
            ->where('auditable_type', ProjectTask::class)
            ->where(function ($query) use ($sprint) {
                $query->where('new_values->sprint_id', $sprint->id)
                    ->orWhere('old_values->sprint_id', $sprint->id);
            })
            ->pluck('auditable_id')
            ->all();

        $taskIds = array_values(array_unique(array_map('intval', array_merge($taskIds, $movedTaskIds))));

        $this->relatedTasks = $taskIds === []
            ? collect()
            : ProjectTask::query()->whereIn('id', $taskIds)->get();

        foreach ($this->relatedTasks as $task) {
            $this->taskNames[$task->id] = $task->name;
        }

        $this->relatedSubtasks = $taskIds === []
            ? collect()
            : TaskSubtask::query()->whereIn('task_id', $taskIds)->get();

        foreach ($this->relatedSubtasks as $subtask) {
            $this->subtaskNames[$subtask->id] = $subtask->name;
            $this->subtaskTaskIds[$subtask->id] = (int) $subtask->task_id;
        }

        $this->relatedComments = Comment::withTrashed()
            ->with('user:id,name')
            ->where(function ($query) use ($sprint, $taskIds) {
                $query->where(function ($inner) use ($sprint) {
                    $inner->where('commentable_type', 'sprint')
                        ->where('commentable_id', $sprint->id);
                });

                if ($taskIds !== []) {
                    $query->orWhere(function ($inner) use ($taskIds) {
                        $inner->where('commentable_type', 'project_task')
                            ->whereIn('commentable_id', $taskIds);
                    });
                }
            })
            ->get();

        $this->relatedMilestones = $sprint->milestones()->get();

        $commentIds = $this->relatedComments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $subtaskIds = $this->relatedSubtasks->pluck('id')->map(fn ($id) => (int) $id)->all();
        $milestoneIds = $this->relatedMilestones->pluck('id')->map(fn ($id) => (int) $id)->all();

        $attachmentIds = Attachment::query()
            ->where(function ($query) use ($sprint, $taskIds, $commentIds) {
                $query->where(function ($inner) use ($sprint) {
                    $inner->where('attachable_type', 'sprint')
                        ->where('attachable_id', $sprint->id);
                });

                if ($taskIds !== []) {
                    $query->orWhere(function ($inner) use ($taskIds) {
                        $inner->where('attachable_type', 'project_task')
                            ->whereIn('attachable_id', $taskIds);
                    });
                }

                if ($commentIds !== []) {
                    $query->orWhere(function ($inner) use ($commentIds) {
                        $inner->where('attachable_type', 'comment')
                            ->whereIn('attachable_id', $commentIds);
                    });
                }
            })
            ->pluck('id')
            ->all();

        $userIds = $this->relatedTasks->pluck('assigned_to')
            ->merge($this->relatedTasks->pluck('created_by'))
            ->merge($this->relatedSubtasks->pluck('created_by'))
            ->merge($this->relatedComments->pluck('user_id'))
            ->merge($this->relatedMilestones->pluck('created_by'))
            ->filter()
            ->unique()
            ->all();

        $this->rememberUsers($userIds);

        return [
            'taskIds' => $taskIds,
            'subtaskIds' => $subtaskIds,
            'commentIds' => $commentIds,
            'milestoneIds' => $milestoneIds,
            'attachmentIds' => array_map('intval', $attachmentIds),
        ];
    }

    /**
     * @param  array{taskIds: list<int>, subtaskIds: list<int>, commentIds: list<int>, milestoneIds: list<int>, attachmentIds: list<int>}  $related
     * @return Collection<int, AuditLog>
     */
    private function auditLogs(Sprint $sprint, array $related): Collection
    {
        return AuditLog::query()
            ->where(function ($query) use ($sprint, $related) {
                $query->where(function ($inner) use ($sprint) {
                    $inner->where('auditable_type', Sprint::class)
                        ->where('auditable_id', $sprint->id);
                });

                if ($related['taskIds'] !== []) {
                    $query->orWhere(function ($inner) use ($related) {
                        $inner->where('auditable_type', ProjectTask::class)
                            ->whereIn('auditable_id', $related['taskIds']);
                    });
                }

                if ($related['subtaskIds'] !== []) {
                    $query->orWhere(function ($inner) use ($related) {
                        $inner->where('auditable_type', TaskSubtask::class)
                            ->whereIn('auditable_id', $related['subtaskIds']);
                    });
                }

                if ($related['commentIds'] !== []) {
                    $query->orWhere(function ($inner) use ($related) {
                        $inner->where('auditable_type', Comment::class)
                            ->whereIn('auditable_id', $related['commentIds']);
                    });
                }

                if ($related['milestoneIds'] !== []) {
                    $query->orWhere(function ($inner) use ($related) {
                        $inner->where('auditable_type', SprintMilestone::class)
                            ->whereIn('auditable_id', $related['milestoneIds']);
                    });
                }

                if ($related['attachmentIds'] !== []) {
                    $query->orWhere(function ($inner) use ($related) {
                        $inner->where('auditable_type', Attachment::class)
                            ->whereIn('auditable_id', $related['attachmentIds']);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return ActivityEntry|null
     */
    private function fromAudit(AuditLog $log, Sprint $sprint): ?array
    {
        $actor = $this->actor($log);
        $at = $log->created_at ?? now();

        return match ($log->auditable_type) {
            ProjectTask::class => $this->fromTaskAudit($log, $actor, $at, $sprint),
            TaskSubtask::class => $this->fromSubtaskAudit($log, $actor, $at),
            Comment::class => $this->fromCommentAudit($log, $actor, $at, $sprint),
            Sprint::class => $this->fromSprintAudit($log, $actor, $at, $sprint),
            SprintMilestone::class => $this->fromMilestoneAudit($log, $actor, $at),
            Attachment::class => $this->fromAttachmentAudit($log, $actor, $at),
            default => null,
        };
    }

    /**
     * @return ActivityEntry|null
     */
    private function fromTaskAudit(AuditLog $log, string $actor, Carbon $at, Sprint $sprint): ?array
    {
        $name = $this->stringVal($log->new_values['name'] ?? $log->old_values['name'] ?? null)
            ?? $this->taskNames[$log->auditable_id] ?? 'zadanie';
        $url = $this->taskUrl((int) $log->auditable_id);

        if ($log->event === 'created') {
            return $this->entry($at, $actor, 'dodał zadanie', $name, $url, null, 'plus-lg', 'primary', 'task.created');
        }

        if ($log->event === 'deleted') {
            return $this->entry($at, $actor, 'usunął zadanie', $name, null, null, 'trash', 'danger', 'task.deleted');
        }

        $changes = $this->changed($log);
        if ($changes === []) {
            return null;
        }

        if (array_key_exists('status', $changes)) {
            $to = TaskStatus::tryFrom((string) ($changes['status']['after'] ?? ''));
            $from = TaskStatus::tryFrom((string) ($changes['status']['before'] ?? ''));

            if ($to === TaskStatus::COMPLETED) {
                return $this->entry($at, $actor, 'zakończył zadanie', $name, $url, null, 'check-circle', 'success', 'task.completed');
            }

            if ($from === TaskStatus::COMPLETED && $to !== null) {
                return $this->entry($at, $actor, 'wznowił zadanie', $name, $url, $to->label(), 'arrow-counterclockwise', 'warning', 'task.reopened');
            }

            if ($to === TaskStatus::CANCELLED) {
                return $this->entry($at, $actor, 'anulował zadanie', $name, $url, null, 'x-circle', 'danger', 'task.cancelled');
            }

            if ($to !== null) {
                $detail = ($from?->label() ?? '—').' → '.$to->label();

                return $this->entry($at, $actor, 'zmienił status zadania', $name, $url, $detail, 'arrow-repeat', 'info', 'task.status');
            }
        }

        if (array_key_exists('assigned_to', $changes)) {
            $after = $this->intOrNull($changes['assigned_to']['after']);
            $detail = $after
                ? '→ '.$this->userName($after)
                : 'bez przypisania';

            return $this->entry($at, $actor, 'zmienił przypisanie zadania', $name, $url, $detail, 'person', 'info', 'task.assigned');
        }

        if (array_key_exists('sprint_id', $changes)) {
            $fromId = $this->intOrNull($changes['sprint_id']['before']);
            $toId = $this->intOrNull($changes['sprint_id']['after']);

            if ($toId === $sprint->id && $fromId !== $sprint->id) {
                $fromLabel = $fromId ? $this->sprintName($fromId) : 'backlogu';

                return $this->entry($at, $actor, 'przeniósł zadanie do sprintu', $name, $url, 'z '.$fromLabel, 'box-arrow-in-right', 'primary', 'task.moved_in');
            }

            if ($fromId === $sprint->id && $toId !== $sprint->id) {
                $toLabel = $toId ? $this->sprintName($toId) : 'backlogu';

                return $this->entry($at, $actor, 'przeniósł zadanie ze sprintu', $name, $url, 'do '.$toLabel, 'box-arrow-right', 'warning', 'task.moved_out');
            }
        }

        if (array_key_exists('name', $changes)) {
            return $this->entry(
                $at,
                $actor,
                'zmienił nazwę zadania',
                (string) ($changes['name']['after'] ?? $name),
                $url,
                'było: '.((string) ($changes['name']['before'] ?? '—')),
                'pencil',
                'muted',
                'task.renamed'
            );
        }

        $interesting = $this->withoutNoise($changes);
        if ($interesting === []) {
            if (array_key_exists('sprint_position', $changes)) {
                return $this->entry($at, $actor, 'zmienił kolejność zadania', $name, $url, null, 'arrows-move', 'muted', 'task.reordered');
            }

            return null;
        }

        return $this->entry($at, $actor, 'zaktualizował zadanie', $name, $url, $this->detailLines($interesting), 'pencil', 'muted', 'task.updated');
    }

    /**
     * @return ActivityEntry|null
     */
    private function fromSubtaskAudit(AuditLog $log, string $actor, Carbon $at): ?array
    {
        $name = $this->stringVal($log->new_values['name'] ?? $log->old_values['name'] ?? null)
            ?? $this->subtaskNames[$log->auditable_id] ?? 'podzadanie';
        $parentId = $this->intOrNull($log->new_values['task_id'] ?? $log->old_values['task_id'] ?? null)
            ?? ($this->subtaskTaskIds[$log->auditable_id] ?? null);
        $url = $parentId ? $this->taskUrl($parentId) : null;
        $parentName = $parentId ? ($this->taskNames[$parentId] ?? null) : null;

        if ($log->event === 'created') {
            $detail = $parentName ? 'w zadaniu '.$parentName : null;

            return $this->entry($at, $actor, 'dodał podzadanie', $name, $url, $detail, 'plus-lg', 'primary', 'subtask.created');
        }

        if ($log->event === 'deleted') {
            return $this->entry($at, $actor, 'usunął podzadanie', $name, $url, $parentName, 'trash', 'danger', 'subtask.deleted');
        }

        $changes = $this->changed($log);
        if ($changes === []) {
            return null;
        }

        if (array_key_exists('is_completed', $changes)) {
            $done = $this->boolish($changes['is_completed']['after']);
            if ($done) {
                return $this->entry($at, $actor, 'zakończył podzadanie', $name, $url, $parentName, 'check-circle', 'success', 'subtask.completed');
            }

            return $this->entry($at, $actor, 'wznowił podzadanie', $name, $url, $parentName, 'arrow-counterclockwise', 'warning', 'subtask.reopened');
        }

        if (array_key_exists('task_id', $changes)) {
            $from = $this->taskNames[$this->intOrNull($changes['task_id']['before']) ?? 0] ?? 'inne zadanie';
            $to = $this->taskNames[$this->intOrNull($changes['task_id']['after']) ?? 0] ?? 'inne zadanie';

            return $this->entry($at, $actor, 'przeniósł podzadanie', $name, $url, $from.' → '.$to, 'arrow-left-right', 'warning', 'subtask.moved');
        }

        if (array_key_exists('name', $changes)) {
            return $this->entry(
                $at,
                $actor,
                'zmienił nazwę podzadania',
                (string) ($changes['name']['after'] ?? $name),
                $url,
                'było: '.((string) ($changes['name']['before'] ?? '—')),
                'pencil',
                'muted',
                'subtask.renamed'
            );
        }

        $interesting = $this->withoutNoise($changes);
        if ($interesting === []) {
            return null;
        }

        return $this->entry($at, $actor, 'zaktualizował podzadanie', $name, $url, $this->detailLines($interesting), 'pencil', 'muted', 'subtask.updated');
    }

    /**
     * @return ActivityEntry|null
     */
    private function fromCommentAudit(AuditLog $log, string $actor, Carbon $at, Sprint $sprint): ?array
    {
        $values = $log->event === 'deleted' ? ($log->old_values ?? []) : ($log->new_values ?? []);
        $body = $this->excerpt($this->stringVal($values['body'] ?? null));
        $type = (string) ($values['commentable_type'] ?? '');
        $commentableId = $this->intOrNull($values['commentable_id'] ?? null);
        $isReply = $this->intOrNull($values['parent_id'] ?? null) !== null;

        if ($log->event === 'deleted') {
            return $this->entry($at, $actor, 'usunął komentarz', $this->commentTarget($type, $commentableId, $sprint), $this->commentUrl($type, $commentableId), $body, 'trash', 'danger', 'comment.deleted');
        }

        if ($log->event === 'updated') {
            return $this->entry($at, $actor, 'edytował komentarz', $this->commentTarget($type, $commentableId, $sprint), $this->commentUrl($type, $commentableId), $body, 'pencil', 'muted', 'comment.updated');
        }

        $verb = $isReply ? 'odpowiedział przy' : 'dodał komentarz do';

        return $this->entry($at, $actor, $verb, $this->commentTarget($type, $commentableId, $sprint), $this->commentUrl($type, $commentableId), $body, 'chat-dots', 'info', 'comment.created');
    }

    /**
     * @return ActivityEntry|null
     */
    private function fromSprintAudit(AuditLog $log, string $actor, Carbon $at, Sprint $sprint): ?array
    {
        if ($log->event === 'created') {
            return $this->entry($at, $actor, 'utworzył sprint', $sprint->name, null, null, 'flag', 'primary', 'sprint.created');
        }

        if ($log->event === 'deleted') {
            return $this->entry($at, $actor, 'usunął sprint', $sprint->name, null, null, 'trash', 'danger', 'sprint.deleted');
        }

        $changes = $this->withoutNoise($this->changed($log));
        if ($changes === []) {
            return null;
        }

        return $this->entry($at, $actor, 'zaktualizował sprint', $sprint->name, null, $this->detailLines($changes), 'pencil', 'muted', 'sprint.updated');
    }

    /**
     * @return ActivityEntry|null
     */
    private function fromMilestoneAudit(AuditLog $log, string $actor, Carbon $at): ?array
    {
        $name = $this->stringVal($log->new_values['name'] ?? $log->old_values['name'] ?? null) ?? 'kamień milowy';

        if ($log->event === 'created') {
            return $this->entry($at, $actor, 'dodał kamień milowy', $name, null, null, 'flag', 'primary', 'milestone.created');
        }

        if ($log->event === 'deleted') {
            return $this->entry($at, $actor, 'usunął kamień milowy', $name, null, null, 'trash', 'danger', 'milestone.deleted');
        }

        $changes = $this->changed($log);
        if (array_key_exists('completed_at', $changes)) {
            $done = $this->stringVal($changes['completed_at']['after'] ?? null) !== null;

            return $done
                ? $this->entry($at, $actor, 'odhaczył kamień milowy', $name, null, null, 'check-circle', 'success', 'milestone.completed')
                : $this->entry($at, $actor, 'odznaczył kamień milowy', $name, null, null, 'arrow-counterclockwise', 'warning', 'milestone.reopened');
        }

        $interesting = $this->withoutNoise($changes);
        if ($interesting === []) {
            return null;
        }

        return $this->entry($at, $actor, 'zaktualizował kamień milowy', $name, null, $this->detailLines($interesting), 'pencil', 'muted', 'milestone.updated');
    }

    /**
     * @return ActivityEntry|null
     */
    private function fromAttachmentAudit(AuditLog $log, string $actor, Carbon $at): ?array
    {
        $values = $log->event === 'deleted' ? ($log->old_values ?? []) : ($log->new_values ?? []);
        $filename = $this->stringVal($values['original_name'] ?? null) ?? 'plik';
        $type = (string) ($values['attachable_type'] ?? '');
        $attachableId = $this->intOrNull($values['attachable_id'] ?? null);
        $target = match ($type) {
            'sprint' => 'sprintu',
            'project_task' => $this->taskNames[$attachableId ?? 0] ?? 'zadania',
            'comment' => 'komentarza',
            default => 'wpisu',
        };
        $url = $type === 'project_task' && $attachableId ? $this->taskUrl($attachableId) : null;

        if ($log->event === 'deleted') {
            return $this->entry($at, $actor, 'usunął załącznik', $filename, $url, $target, 'trash', 'danger', 'attachment.deleted');
        }

        return $this->entry($at, $actor, 'dodał załącznik', $filename, $url, 'do '.$target, 'paperclip', 'info', 'attachment.created');
    }

    /**
     * @param  array{taskIds: list<int>, subtaskIds: list<int>, commentIds: list<int>, milestoneIds: list<int>, attachmentIds: list<int>}  $related
     * @return Collection<int, ActivityEntry>
     */
    private function synthetics(Sprint $sprint, array $related): Collection
    {
        $entries = collect();

        if (! $this->hasAudit(Sprint::class, $sprint->id, 'created') && $sprint->created_at) {
            $entries->push($this->entry(
                $sprint->created_at,
                $this->userName($sprint->created_by),
                'utworzył sprint',
                $sprint->name,
                null,
                null,
                'flag',
                'primary',
                'sprint.created'
            ));
        }

        foreach ($this->relatedTasks as $task) {
            if (! $this->hasAudit(ProjectTask::class, $task->id, 'created') && $task->created_at) {
                $entries->push($this->entry(
                    $task->created_at,
                    $this->userName($task->created_by),
                    'dodał zadanie',
                    $task->name,
                    $this->taskUrl($task->id),
                    null,
                    'plus-lg',
                    'primary',
                    'task.created'
                ));
            }

            if (
                $task->status === TaskStatus::COMPLETED
                && $task->completed_at
                && ! $this->hasAudit(ProjectTask::class, $task->id, 'updated')
            ) {
                $entries->push($this->entry(
                    $task->completed_at,
                    'System',
                    'zakończył zadanie',
                    $task->name,
                    $this->taskUrl($task->id),
                    null,
                    'check-circle',
                    'success',
                    'task.completed'
                ));
            }
        }

        foreach ($this->relatedComments as $comment) {
            if ($this->hasAudit(Comment::class, $comment->id, 'created') || ! $comment->created_at) {
                continue;
            }

            $type = $comment->commentable_type instanceof \BackedEnum
                ? $comment->commentable_type->value
                : (string) $comment->commentable_type;
            $isReply = $comment->parent_id !== null;
            $entries->push($this->entry(
                $comment->created_at,
                $comment->user?->name ?: 'System',
                $isReply ? 'odpowiedział przy' : 'dodał komentarz do',
                $this->commentTarget($type, (int) $comment->commentable_id, $sprint),
                $this->commentUrl($type, (int) $comment->commentable_id),
                $this->excerpt($comment->body),
                'chat-dots',
                'info',
                'comment.created'
            ));
        }

        foreach ($this->relatedSubtasks as $subtask) {
            $parentName = $this->taskNames[$subtask->task_id] ?? null;
            $url = $this->taskUrl((int) $subtask->task_id);

            if (! $this->hasAudit(TaskSubtask::class, $subtask->id, 'created') && $subtask->created_at) {
                $entries->push($this->entry(
                    $subtask->created_at,
                    $this->userName($subtask->created_by),
                    'dodał podzadanie',
                    $subtask->name,
                    $url,
                    $parentName ? 'w zadaniu '.$parentName : null,
                    'plus-lg',
                    'primary',
                    'subtask.created'
                ));
            }
        }

        if ($related['subtaskIds'] !== []) {
            $events = TaskSubtaskEvent::query()
                ->with('user:id,name')
                ->whereIn('subtask_id', $related['subtaskIds'])
                ->whereIn('event', ['completed', 'reopened', 'moved', 'renamed', 'deleted'])
                ->get();

            foreach ($events as $event) {
                if ($this->hasAudit(TaskSubtask::class, (int) $event->subtask_id, 'updated')
                    || $this->hasAudit(TaskSubtask::class, (int) $event->subtask_id, 'deleted')) {
                    continue;
                }

                $name = $this->subtaskNames[$event->subtask_id] ?? 'podzadanie';
                $parentId = $this->subtaskTaskIds[$event->subtask_id] ?? null;
                $mapped = match ($event->event) {
                    'completed' => ['zakończył podzadanie', 'check-circle', 'success'],
                    'reopened' => ['wznowił podzadanie', 'arrow-counterclockwise', 'warning'],
                    'moved' => ['przeniósł podzadanie', 'arrow-left-right', 'warning'],
                    'renamed' => ['zmienił nazwę podzadania', 'pencil', 'muted'],
                    'deleted' => ['usunął podzadanie', 'trash', 'danger'],
                    default => null,
                };

                if ($mapped === null || ! $event->created_at) {
                    continue;
                }

                $entries->push($this->entry(
                    $event->created_at,
                    $event->user?->name ?: 'System',
                    $mapped[0],
                    $name,
                    $parentId ? $this->taskUrl($parentId) : null,
                    $parentId ? ($this->taskNames[$parentId] ?? null) : null,
                    $mapped[1],
                    $mapped[2],
                    'subtask.'.$event->event
                ));
            }
        }

        foreach ($this->relatedMilestones as $milestone) {
            if (! $this->hasAudit(SprintMilestone::class, $milestone->id, 'created') && $milestone->created_at) {
                $entries->push($this->entry(
                    $milestone->created_at,
                    $this->userName($milestone->created_by),
                    'dodał kamień milowy',
                    $milestone->name,
                    null,
                    null,
                    'flag',
                    'primary',
                    'milestone.created'
                ));
            }

            if ($milestone->completed_at && ! $this->hasAudit(SprintMilestone::class, $milestone->id, 'updated')) {
                $entries->push($this->entry(
                    $milestone->completed_at,
                    'System',
                    'odhaczył kamień milowy',
                    $milestone->name,
                    null,
                    null,
                    'check-circle',
                    'success',
                    'milestone.completed'
                ));
            }
        }

        return $entries;
    }

    /**
     * @return ActivityEntry
     */
    private function entry(
        Carbon $at,
        string $actor,
        string $verb,
        ?string $subject,
        ?string $url,
        ?string $detail,
        string $icon,
        string $tone,
        string $kind,
    ): array {
        return compact('at', 'actor', 'verb', 'subject', 'url', 'detail', 'icon', 'tone', 'kind');
    }

    private function actor(AuditLog $log): string
    {
        if ($log->user_id) {
            return $this->userName((int) $log->user_id);
        }

        foreach ([$log->new_values, $log->old_values] as $values) {
            foreach (['user_id', 'created_by', 'uploaded_by'] as $key) {
                $id = $this->intOrNull($values[$key] ?? null);
                if ($id) {
                    return $this->userName($id);
                }
            }
        }

        return 'System';
    }

    /**
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function changed(AuditLog $log): array
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $out = [];

        foreach ($keys as $key) {
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;
            if ($this->same($before, $after)) {
                continue;
            }
            $out[$key] = ['before' => $before, 'after' => $after];
        }

        return $out;
    }

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $changes
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function withoutNoise(array $changes): array
    {
        return array_filter(
            $changes,
            fn (string $key) => ! in_array($key, self::NOISE_KEYS, true)
                && $key !== 'sprint_id'
                && $key !== 'sprint_position'
                && $key !== 'completed_at',
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $changes
     */
    private function detailLines(array $changes): string
    {
        $labels = config('audit.field_labels', []);
        $parts = [];

        foreach ($changes as $key => $pair) {
            $label = $labels[$key] ?? Str::title(str_replace('_', ' ', $key));
            $parts[] = $label.': '.$this->display($key, $pair['before']).' → '.$this->display($key, $pair['after']);
        }

        return implode(' · ', $parts);
    }

    private function display(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($key === 'status' && is_string($value)) {
            return TaskStatus::tryFrom($value)?->label() ?? $value;
        }

        if (in_array($key, ['assigned_to', 'created_by'], true)) {
            $id = $this->intOrNull($value);

            return $id ? $this->userName($id) : '—';
        }

        if (is_bool($value)) {
            return $value ? 'tak' : 'nie';
        }

        $text = is_scalar($value) ? (string) $value : (json_encode($value, JSON_UNESCAPED_UNICODE) ?: '—');

        return mb_strlen($text) > 80 ? mb_substr($text, 0, 77).'…' : $text;
    }

    private function commentTarget(string $type, ?int $id, Sprint $sprint): string
    {
        if ($type === 'project_task' && $id) {
            return 'zadania '.($this->taskNames[$id] ?? '#'.$id);
        }

        return 'sprintu '.$sprint->name;
    }

    private function commentUrl(string $type, ?int $id): ?string
    {
        if ($type === 'project_task' && $id) {
            return $this->taskUrl($id);
        }

        return null;
    }

    private function taskUrl(int $taskId): string
    {
        return route('tasks.show', $taskId);
    }

    private function sprintName(int $id): string
    {
        return $this->sprintNames[$id] ?? 'innego sprintu';
    }

    private function userName(?int $id): string
    {
        if (! $id) {
            return 'System';
        }

        return $this->userNames[$id] ?? 'Użytkownik #'.$id;
    }

    /**
     * @param  list<int|string|null>  $ids
     */
    private function rememberUsers(array $ids): void
    {
        $missing = [];
        foreach ($ids as $id) {
            $id = $this->intOrNull($id);
            if ($id && ! isset($this->userNames[$id])) {
                $missing[$id] = $id;
            }
        }

        if ($missing === []) {
            return;
        }

        foreach (User::query()->whereIn('id', array_values($missing))->get(['id', 'name']) as $user) {
            $this->userNames[$user->id] = $user->name;
        }
    }

    private function rememberSnapshotNames(AuditLog $log): void
    {
        foreach ([$log->old_values, $log->new_values] as $values) {
            $name = $this->stringVal($values['name'] ?? null);
            if ($name && $log->auditable_type === ProjectTask::class) {
                $this->taskNames[(int) $log->auditable_id] = $name;
            }
            if ($name && $log->auditable_type === TaskSubtask::class) {
                $this->subtaskNames[(int) $log->auditable_id] = $name;
            }

            $taskId = $this->intOrNull($values['task_id'] ?? null);
            if ($taskId && $log->auditable_type === TaskSubtask::class) {
                $this->subtaskTaskIds[(int) $log->auditable_id] = $taskId;
            }
        }
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     */
    private function preloadLookupsFromLogs(Collection $logs): void
    {
        $userIds = [];
        $sprintIds = [];
        $taskIds = [];

        foreach ($logs as $log) {
            if ($log->user_id) {
                $userIds[] = (int) $log->user_id;
            }

            foreach ([$log->old_values, $log->new_values] as $values) {
                foreach (['assigned_to', 'created_by', 'uploaded_by', 'user_id'] as $key) {
                    $id = $this->intOrNull($values[$key] ?? null);
                    if ($id) {
                        $userIds[] = $id;
                    }
                }

                $sprintId = $this->intOrNull($values['sprint_id'] ?? null);
                if ($sprintId) {
                    $sprintIds[] = $sprintId;
                }

                $taskId = $this->intOrNull($values['task_id'] ?? null);
                if ($taskId) {
                    $taskIds[] = $taskId;
                }
            }
        }

        $this->rememberUsers($userIds);

        $missingSprints = array_values(array_unique(array_filter(
            $sprintIds,
            fn (int $id) => ! isset($this->sprintNames[$id])
        )));
        if ($missingSprints !== []) {
            foreach (Sprint::query()->whereIn('id', $missingSprints)->get(['id', 'name']) as $other) {
                $this->sprintNames[$other->id] = $other->name;
            }
        }

        $missingTasks = array_values(array_unique(array_filter(
            $taskIds,
            fn (int $id) => ! isset($this->taskNames[$id])
        )));
        if ($missingTasks !== []) {
            foreach (ProjectTask::query()->whereIn('id', $missingTasks)->get(['id', 'name']) as $task) {
                $this->taskNames[$task->id] = $task->name;
            }
        }
    }

    private function hasAudit(string $class, int $id, string $event): bool
    {
        return isset($this->auditKeys[$this->key($class, $id, $event)]);
    }

    private function key(string $type, int $id, string $event): string
    {
        return $type.'|'.$id.'|'.$event;
    }

    private function excerpt(?string $body): ?string
    {
        if ($body === null || trim($body) === '') {
            return null;
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');

        return mb_strlen($plain) > 160 ? mb_substr($plain, 0, 157).'…' : $plain;
    }

    private function stringVal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (int) $value;
    }

    private function boolish(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === 1 || $value === '1';
    }

    private function same(mixed $a, mixed $b): bool
    {
        if ($a === $b) {
            return true;
        }

        if (is_bool($a) || is_bool($b)) {
            return $this->boolish($a) === $this->boolish($b);
        }

        if ($a === null || $b === null) {
            return $a === $b;
        }

        return (string) $a === (string) $b;
    }
}
