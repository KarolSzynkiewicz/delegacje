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

    public string $qeProjectId = '';

    public string $qeCategory = '';

    public string $qeDueDate = '';

    public string $qeAssignedTo = '';

    public ?string $quickEditFlash = null;

    /** project|category|due_date|assigned_to */
    public string $quickEditField = 'project';

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

        if (! in_array($field, ['project', 'category', 'due_date', 'assigned_to'], true)) {
            $field = 'project';
        }

        $this->quickEditField = $field;
        $this->quickEditClientX = $clientX;
        $this->quickEditClientY = $clientY;

        $task = ProjectTask::query()->find($taskId);
        if (! $task || ! $this->canQuickEditTask($task)) {
            return;
        }

        $this->quickEditTaskId = $task->id;
        $this->qeProjectId = $task->project_id ? (string) $task->project_id : '';
        $this->qeCategory = (string) ($task->category ?? '');
        $this->qeDueDate = $task->due_date ? $task->due_date->format('Y-m-d') : '';
        $this->qeAssignedTo = $task->assigned_to ? (string) $task->assigned_to : '';
    }

    public function closeQuickEdit(): void
    {
        $this->quickEditTaskId = null;
        $this->qeProjectId = '';
        $this->qeCategory = '';
        $this->qeDueDate = '';
        $this->qeAssignedTo = '';
        $this->quickEditField = 'project';
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

        if (! in_array($this->quickEditField, ['project', 'category', 'due_date', 'assigned_to'], true)) {
            $this->quickEditField = 'project';
        }

        match ($this->quickEditField) {
            'project' => $this->saveQuickEditProject($task),
            'category' => $this->saveQuickEditCategory($task),
            'due_date' => $this->saveQuickEditDueDate($task),
            'assigned_to' => $this->saveQuickEditAssignedTo($task),
        };

        $task->refresh();
        $task->loadMissing('project', 'assignedTo');

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

    protected function saveQuickEditProject(ProjectTask $task): void
    {
        Validator::make(
            [
                'qeProjectId' => $this->qeProjectId === '' ? null : $this->qeProjectId,
            ],
            [
                'qeProjectId' => ['nullable', 'integer', 'exists:projects,id'],
            ],
            [
                'qeProjectId.exists' => 'Wybrany projekt nie istnieje.',
            ]
        )->validate();

        $projectId = $this->qeProjectId === '' ? null : (int) $this->qeProjectId;
        $task->update(['project_id' => $projectId]);
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
}
