<?php

namespace App\Livewire;

use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Services\UserMentionService;
use Livewire\Component;

class TaskSubtasks extends Component
{
    public ProjectTask $task;

    public string $newSubtaskName = '';

    public ?int $editingSubtaskId = null;

    public string $editingSubtaskName = '';

    public function mount(ProjectTask $task): void
    {
        $this->task = $task;
        $this->task->load('subtasks');
    }

    public function addSubtask(): void
    {
        $this->validate([
            'newSubtaskName' => 'required|string|max:255',
        ], [
            'newSubtaskName.required' => 'Nazwa podzadania jest wymagana.',
            'newSubtaskName.max' => 'Nazwa podzadania nie może przekraczać 255 znaków.',
        ]);

        $name = trim($this->newSubtaskName);

        $subtask = TaskSubtask::create([
            'task_id' => $this->task->id,
            'name' => $name,
            'is_completed' => false,
        ]);

        app(UserMentionService::class)->notifySubtaskMentions(
            $this->task,
            $subtask,
            $name,
            auth()->user()
        );

        $this->newSubtaskName = '';
        $this->task->refresh();
        $this->task->load('subtasks');
    }

    public function toggleSubtask($subtaskId): void
    {
        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        if ($subtask->is_completed) {
            $subtask->markIncomplete();
        } else {
            $subtask->markCompleted();
        }

        $this->task->refresh();
        $this->task->load('subtasks');
    }

    public function startEditSubtask(int $subtaskId): void
    {
        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        $this->editingSubtaskId = $subtask->id;
        $this->editingSubtaskName = $subtask->name;
    }

    public function cancelEditSubtask(): void
    {
        $this->editingSubtaskId = null;
        $this->editingSubtaskName = '';
    }

    public function saveSubtaskEdits(int $subtaskId): void
    {
        $this->validate([
            'editingSubtaskName' => 'required|string|max:255',
        ], [
            'editingSubtaskName.required' => 'Nazwa podzadania jest wymagana.',
            'editingSubtaskName.max' => 'Nazwa podzadania nie może przekraczać 255 znaków.',
        ]);

        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        $name = trim($this->editingSubtaskName);

        $subtask->update([
            'name' => $name,
        ]);

        app(UserMentionService::class)->notifySubtaskMentions(
            $this->task,
            $subtask->fresh(),
            $name,
            auth()->user()
        );

        $this->cancelEditSubtask();
        $this->task->refresh();
        $this->task->load('subtasks');
    }

    public function deleteSubtask(int $subtaskId): void
    {
        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        $subtask->delete();

        if ($this->editingSubtaskId === $subtaskId) {
            $this->cancelEditSubtask();
        }

        $this->task->refresh();
        $this->task->load('subtasks');
    }

    public function getCompletedSubtasksProperty()
    {
        if (! $this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }

        return $this->task->subtasks->where('is_completed', true)->sortByDesc('completed_at')->values();
    }

    public function getPendingSubtasksProperty()
    {
        if (! $this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }

        return $this->task->subtasks->where('is_completed', false)->sortBy('created_at')->values();
    }

    public function getProgressPercentageProperty(): float
    {
        if (! $this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }
        $progress = $this->task->subtasks_progress;

        return is_numeric($progress) ? (float) $progress : 0.0;
    }

    public function render()
    {
        return view('livewire.task-subtasks', [
            'mentionUsersForAutocomplete' => User::orderBy('name')
                ->get()
                ->map(fn (User $u) => [
                    'name' => $u->name,
                    'initials' => $u->initials,
                ])
                ->values()
                ->all(),
        ]);
    }
}
