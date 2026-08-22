<?php

namespace App\Services;

use App\Enums\ProcedureRunStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Models\CommentMention;
use App\Models\ProcedureRun;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\WarehouseDispatch;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Jedno miejsce mapowania źródeł na wiersz backlogu. Nowy typ = ten plik, nie siatka.
 */
class WorkItemSync
{
    public function sync(Model $model): ?WorkItem
    {
        $payload = $this->payload($model);

        if ($payload === null) {
            $this->forget($model);

            return null;
        }

        $item = WorkItem::query()->updateOrCreate(
            [
                'source_type' => $payload['source_type'],
                'source_id' => $payload['source_id'],
            ],
            $payload,
        );

        if ($model instanceof ProjectTask) {
            $this->refreshAssignedSubtaskItems($model);
        }

        return $item;
    }

    /**
     * Sprint i kategoria podzadania idą z rodzica — po zmianie zadania odśwież wiersze dzieci.
     */
    private function refreshAssignedSubtaskItems(ProjectTask $task): void
    {
        $task->subtasks()
            ->whereNotNull('assigned_to')
            ->get()
            ->each(fn (TaskSubtask $subtask) => $this->sync($subtask));
    }

    public function forget(Model $model): void
    {
        WorkItem::query()
            ->where('source_type', $model->getMorphClass())
            ->where('source_id', $model->getKey())
            ->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function payload(Model $model): ?array
    {
        return match (true) {
            $model instanceof ProjectTask => $this->fromTask($model),
            $model instanceof TaskSubtask => $this->fromSubtask($model),
            $model instanceof ProcedureRun => $this->fromProcedureRun($model),
            $model instanceof WarehouseDispatch => $this->fromDispatch($model),
            $model instanceof CommentMention => $this->fromMention($model),
            default => null,
        };
    }

    public function url(WorkItem $item): string
    {
        $source = $item->source;

        return match ($item->type) {
            WorkItemType::Task, WorkItemType::Callback => $source instanceof ProjectTask
                ? route('tasks.show', $source)
                : url('/tasks2'),
            WorkItemType::FollowUp => $source instanceof CommentMention
                ? ($source->comment?->urlWithCommentAnchor() ?? url('/tasks2'))
                : url('/tasks2'),
            WorkItemType::Subtask => $source instanceof TaskSubtask
                ? ($source->task ? route('tasks.show', $source->task) : url('/tasks2'))
                : url('/tasks2'),
            WorkItemType::ProcedureRun => $source instanceof ProcedureRun && $source->task
                ? route('tasks.show', $source->task)
                : url('/tasks2'),
            WorkItemType::Dispatch => $source instanceof WarehouseDispatch
                ? route('warehouse-dispatches.show', $source)
                : url('/tasks2'),
        };
    }

    public function canComplete(WorkItem $item): bool
    {
        return in_array($item->type, [
            WorkItemType::Task,
            WorkItemType::Subtask,
            WorkItemType::FollowUp,
            WorkItemType::Callback,
        ], true);
    }

    public function complete(WorkItem $item): void
    {
        $source = $item->source;

        if ($source instanceof ProjectTask && $item->status->isOpen()) {
            $source->markCompleted();

            return;
        }

        if ($source instanceof CommentMention && $item->status->isOpen()) {
            $source->markCompleted();

            return;
        }

        if ($source instanceof TaskSubtask && ! $source->is_completed) {
            $source->markCompleted();
        }
    }

    public function reopen(WorkItem $item): void
    {
        $source = $item->source;

        if ($source instanceof ProjectTask && $source->status === TaskStatus::COMPLETED) {
            $source->reopen();

            return;
        }

        if ($source instanceof CommentMention && $source->isCompleted()) {
            $source->reopen();

            return;
        }

        if ($source instanceof TaskSubtask && $source->is_completed) {
            $source->markIncomplete();
        }
    }

    public function backfill(): int
    {
        $count = 0;

        $this->backfillSubtaskAssigneesFromClones();

        ProjectTask::query()->orderBy('id')->chunkById(200, function ($tasks) use (&$count): void {
            foreach ($tasks as $task) {
                if ($this->sync($task)) {
                    $count++;
                }
            }
        });

        TaskSubtask::query()->whereNotNull('assigned_to')->orderBy('id')->chunkById(200, function ($subtasks) use (&$count): void {
            foreach ($subtasks as $subtask) {
                if ($this->sync($subtask)) {
                    $count++;
                }
            }
        });

        ProcedureRun::query()->orderBy('id')->chunkById(200, function ($runs) use (&$count): void {
            foreach ($runs as $run) {
                if ($this->sync($run)) {
                    $count++;
                }
            }
        });

        WarehouseDispatch::query()->orderBy('id')->chunkById(200, function ($dispatches) use (&$count): void {
            foreach ($dispatches as $dispatch) {
                if ($this->sync($dispatch)) {
                    $count++;
                }
            }
        });

        CommentMention::query()->orderBy('id')->chunkById(200, function ($mentions) use (&$count): void {
            foreach ($mentions as $mention) {
                if ($this->sync($mention)) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fromTask(ProjectTask $task): ?array
    {
        if ($task->procedure_run_id) {
            if ($task->procedureRun) {
                $this->sync($task->procedureRun);
            }
            $this->forget($task);

            return null;
        }

        $subjectType = $task->getAttribute('subject_type');
        if ($subjectType === 'warehouse_dispatch') {
            $task->loadMissing('subject');
            if ($task->subject instanceof WarehouseDispatch) {
                $this->sync($task->subject);
            }
            $this->forget($task);

            return null;
        }

        if ($subjectType === 'task_subtask') {
            $task->loadMissing('subject');
            if ($task->subject instanceof TaskSubtask) {
                if (! $task->subject->assigned_to && $task->assigned_to) {
                    $task->subject->update(['assigned_to' => $task->assigned_to]);
                }
                $this->sync($task->subject->fresh());
            }
            $this->forget($task);

            return null;
        }

        if ($subjectType === 'comment') {
            $this->forget($task);

            return null;
        }

        $type = WorkItemType::Task;
        if ($task->isCallback()) {
            $type = WorkItemType::Callback;
        }

        return [
            'type' => $type,
            'source_type' => $task->getMorphClass(),
            'source_id' => $task->id,
            'title' => $task->name,
            'category' => $task->category,
            'priority' => $task->priority,
            'status' => WorkItemStatus::fromTaskStatus($task->status),
            'assignee_id' => $task->assigned_to,
            'sprint_id' => $task->sprint_id,
            'due_at' => $task->due_date,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fromSubtask(TaskSubtask $subtask): ?array
    {
        if (! $subtask->assigned_to) {
            $this->forget($subtask);

            return null;
        }

        $subtask->loadMissing('task');

        return [
            'type' => WorkItemType::Subtask,
            'source_type' => $subtask->getMorphClass(),
            'source_id' => $subtask->id,
            'title' => $subtask->name,
            'category' => $subtask->task?->category,
            'priority' => null,
            'status' => $subtask->is_completed ? WorkItemStatus::Completed : WorkItemStatus::Pending,
            'assignee_id' => $subtask->assigned_to,
            'sprint_id' => $subtask->task?->sprint_id,
            'due_at' => $subtask->task?->due_date,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fromProcedureRun(ProcedureRun $run): ?array
    {
        $run->loadMissing(['task', 'template']);
        $task = $run->task;
        $status = match ($run->status) {
            ProcedureRunStatus::FINISHED => WorkItemStatus::Completed,
            ProcedureRunStatus::ABANDONED => WorkItemStatus::Cancelled,
            default => WorkItemStatus::InProgress,
        };

        return [
            'type' => WorkItemType::ProcedureRun,
            'source_type' => $run->getMorphClass(),
            'source_id' => $run->id,
            'title' => $task?->name ?? ($run->template?->name ?? 'Procedura #'.$run->id),
            'category' => null,
            'priority' => $task?->priority,
            'status' => $status,
            'assignee_id' => $task?->assigned_to,
            'sprint_id' => $task?->sprint_id,
            'due_at' => $task?->due_date,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromMention(CommentMention $mention): array
    {
        $mention->loadMissing('comment.commentable');

        return [
            'type' => WorkItemType::FollowUp,
            'source_type' => $mention->getMorphClass(),
            'source_id' => $mention->id,
            'title' => $mention->title,
            'category' => null,
            'status' => $mention->status,
            'assignee_id' => $mention->assigned_to,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fromDispatch(WarehouseDispatch $dispatch): ?array
    {
        $wrapper = $dispatch->tasks()->orderBy('id')->first();
        $status = $dispatch->isIssued()
            ? WorkItemStatus::Completed
            : WorkItemStatus::Pending;

        return [
            'type' => WorkItemType::Dispatch,
            'source_type' => $dispatch->getMorphClass(),
            'source_id' => $dispatch->id,
            'title' => $dispatch->taskName(),
            'category' => null,
            'priority' => null,
            'status' => $status,
            'assignee_id' => $wrapper?->assigned_to ?? $dispatch->created_by,
            'sprint_id' => $wrapper?->sprint_id,
            'due_at' => $dispatch->issue_date,
        ];
    }

    private function backfillSubtaskAssigneesFromClones(): void
    {
        ProjectTask::query()
            ->where('subject_type', 'task_subtask')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->chunkById(200, function ($clones): void {
                foreach ($clones as $clone) {
                    $subtask = TaskSubtask::query()->find($clone->subject_id);
                    if (! $subtask || $subtask->assigned_to) {
                        continue;
                    }
                    $subtask->update(['assigned_to' => $clone->assigned_to]);
                }
            });
    }
}
