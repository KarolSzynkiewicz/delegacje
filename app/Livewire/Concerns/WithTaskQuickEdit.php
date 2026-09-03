<?php

namespace App\Livewire\Concerns;

use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Policies\ProjectTaskPolicy;
use Illuminate\Support\Facades\Validator;

trait WithTaskQuickEdit
{
    /** @var int|null */
    public $quickEditTaskId = null;

    public string $qeCategory = '';

    public string $qeDueDate = '';

    public string $qePriority = '';

    public string $qeAssignedTo = '';

    public string $qeSprintId = '';

    public ?string $quickEditFlash = null;

    /** category|due_date|priority|assigned_to|sprint_id */
    public string $quickEditField = 'category';

    public ?float $quickEditClientX = null;

    public ?float $quickEditClientY = null;

    public function canQuickEditTask(ProjectTask $task): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return app(ProjectTaskPolicy::class)->updateStatus($user, $task);
    }

    public function openQuickEdit(int $taskId, string $field, ?float $clientX = null, ?float $clientY = null): void
    {
        $this->quickEditFlash = null;

        if (! in_array($field, ['category', 'due_date', 'priority', 'assigned_to', 'sprint_id'], true)) {
            $field = 'category';
        }

        $this->quickEditField = $field;
        $this->quickEditClientX = $clientX;
        $this->quickEditClientY = $clientY;

        $task = ProjectTask::query()->find($taskId);
        if (! $task || ! $this->canQuickEditTask($task)) {
            return;
        }

        $this->quickEditTaskId = $task->id;
        $this->qeCategory = (string) ($task->category ?? '');
        $this->qeDueDate = $task->due_date ? $task->due_date->format('Y-m-d') : '';
        $this->qePriority = $task->priority ? (string) $task->priority : '';
        $this->qeAssignedTo = $task->assigned_to ? (string) $task->assigned_to : '';
        $this->qeSprintId = $task->sprint_id ? (string) $task->sprint_id : '';
    }

    public function closeQuickEdit(): void
    {
        $this->quickEditTaskId = null;
        $this->qeCategory = '';
        $this->qeDueDate = '';
        $this->qePriority = '';
        $this->qeAssignedTo = '';
        $this->qeSprintId = '';
        $this->quickEditField = 'category';
        $this->quickEditClientX = null;
        $this->quickEditClientY = null;
    }

    public function saveQuickEdit(): void
    {
        if (! $this->quickEditTaskId) {
            return;
        }

        $task = ProjectTask::query()->find($this->quickEditTaskId);
        if (! $task || ! $this->canQuickEditTask($task)) {
            abort(403);
        }

        if (! in_array($this->quickEditField, ['category', 'due_date', 'priority', 'assigned_to', 'sprint_id'], true)) {
            $this->quickEditField = 'category';
        }

        match ($this->quickEditField) {
            'category' => $this->saveQuickEditCategory($task),
            'due_date' => $this->saveQuickEditDueDate($task),
            'priority' => $this->saveQuickEditPriority($task),
            'assigned_to' => $this->saveQuickEditAssignedTo($task),
            'sprint_id' => $this->saveQuickEditSprint($task),
        };

        $task->refresh();
        $task->loadMissing(['assignedTo', 'sprint']);

        $this->afterTaskQuickEditSaved($task);

        $this->quickEditFlash = 'Zapisano zmiany.';
        $this->closeQuickEdit();
    }

    /**
     * Nadpisz np. w widoku pojedynczego zadania, by zsynchronizować publiczny model $task.
     */
    protected function afterTaskQuickEditSaved(ProjectTask $task): void
    {
        //
    }

    protected function saveQuickEditCategory(ProjectTask $task): void
    {
        Validator::make(
            ['qeCategory' => $this->qeCategory],
            ['qeCategory' => ['nullable', 'string', 'max:255']],
        )->validate();

        $task->update([
            'category' => $this->qeCategory === '' ? null : $this->qeCategory,
        ]);
    }

    protected function saveQuickEditDueDate(ProjectTask $task): void
    {
        Validator::make(
            ['qeDueDate' => $this->qeDueDate === '' ? null : $this->qeDueDate],
            ['qeDueDate' => ['nullable', 'date']],
            ['qeDueDate.date' => 'Nieprawidłowa data terminu.']
        )->validate();

        $task->update([
            'due_date' => $this->qeDueDate === '' ? null : $this->qeDueDate,
        ]);
    }

    protected function saveQuickEditPriority(ProjectTask $task): void
    {
        Validator::make(
            ['qePriority' => $this->qePriority === '' ? null : $this->qePriority],
            ['qePriority' => ['nullable', 'integer', 'in:1,2,3,4,5']],
        )->validate();

        $task->update([
            'priority' => $this->qePriority === '' ? null : (int) $this->qePriority,
        ]);
    }

    protected function saveQuickEditAssignedTo(ProjectTask $task): void
    {
        Validator::make(
            [
                'qeAssignedTo' => $this->qeAssignedTo === '' ? null : $this->qeAssignedTo,
            ],
            [
                'qeAssignedTo' => ['nullable', 'integer', 'exists:users,id'],
            ],
            [
                'qeAssignedTo.exists' => 'Wybrany użytkownik nie istnieje.',
            ]
        )->validate();

        $previousAssignee = $task->assigned_to;
        $newAssignee = $this->qeAssignedTo === '' ? null : (int) $this->qeAssignedTo;

        $task->update(['assigned_to' => $newAssignee]);

        if ($newAssignee && $newAssignee !== $previousAssignee && $newAssignee !== auth()->id()) {
            $assignee = User::find($newAssignee);
            $assignee?->notify(new TaskAssigned($task->fresh(), auth()->user()));
        }
    }

    protected function saveQuickEditSprint(ProjectTask $task): void
    {
        Validator::make(
            [
                'qeSprintId' => $this->qeSprintId === '' ? null : $this->qeSprintId,
            ],
            [
                'qeSprintId' => ['nullable', 'integer', 'exists:sprints,id'],
            ],
            [
                'qeSprintId.exists' => 'Wybrany sprint nie istnieje.',
            ]
        )->validate();

        $newSprintId = $this->qeSprintId === '' ? null : (int) $this->qeSprintId;
        $payload = ['sprint_id' => $newSprintId];

        if ($newSprintId && (int) $task->sprint_id !== $newSprintId) {
            $sprint = \App\Models\Sprint::query()->find($newSprintId);
            if ($sprint) {
                $payload['sprint_position'] = $sprint->nextTaskPosition();
            }
        }

        if ($newSprintId === null) {
            $payload['sprint_position'] = null;
        }

        $task->update($payload);
    }
}
