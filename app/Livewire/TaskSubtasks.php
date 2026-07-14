<?php

namespace App\Livewire;

use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use App\Services\UserMentionService;
use Illuminate\Support\Collection;
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
            'newSubtaskName.max'      => 'Nazwa podzadania nie może przekraczać 255 znaków.',
        ]);

        $name = trim($this->newSubtaskName);

        $subtask = TaskSubtask::create([
            'task_id'      => $this->task->id,
            'name'         => $name,
            'is_completed' => false,
            'created_by'   => auth()->id(),
        ]);

        TaskSubtaskEvent::log($subtask, 'created', auth()->id());

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
            TaskSubtaskEvent::log($subtask, 'reopened', auth()->id());
        } else {
            $subtask->markCompleted();
            TaskSubtaskEvent::log($subtask, 'completed', auth()->id());
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

        $this->editingSubtaskId   = $subtask->id;
        $this->editingSubtaskName = $subtask->name;
    }

    public function cancelEditSubtask(): void
    {
        $this->editingSubtaskId   = null;
        $this->editingSubtaskName = '';
    }

    public function saveSubtaskEdits(int $subtaskId): void
    {
        $this->validate([
            'editingSubtaskName' => 'required|string|max:255',
        ], [
            'editingSubtaskName.required' => 'Nazwa podzadania jest wymagana.',
            'editingSubtaskName.max'      => 'Nazwa podzadania nie może przekraczać 255 znaków.',
        ]);

        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        $name = trim($this->editingSubtaskName);

        if ($name !== $subtask->name) {
            TaskSubtaskEvent::log($subtask, 'renamed', auth()->id());
        }

        $subtask->update(['name' => $name]);

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

        TaskSubtaskEvent::log($subtask, 'deleted', auth()->id());

        $subtask->delete();

        if ($this->editingSubtaskId === $subtaskId) {
            $this->cancelEditSubtask();
        }

        $this->task->refresh();
        $this->task->load('subtasks');
    }

    public function getCompletedSubtasksProperty(): Collection
    {
        if (! $this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }

        return $this->task->subtasks->where('is_completed', true)->sortByDesc('completed_at')->values();
    }

    public function getPendingSubtasksProperty(): Collection
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

    /**
     * @return array<int, int>
     */
    public function getSubtaskDisplayNumbersProperty(): array
    {
        return $this->task->subtaskDisplayNumbers();
    }

    /**
     * Metadane historii dla każdego podzadania: kto i kiedy je utworzył/zamknął.
     * Zwraca array indexed subtask_id => ['created_by' => ..., 'completed_by' => ...].
     *
     * @return array<int, array{created_by: string|null, created_at: string|null, completed_by: string|null, completed_at: string|null}>
     */
    public function getSubtaskMetaProperty(): array
    {
        if (! $this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }

        $subtaskIds = $this->task->subtasks->pluck('id');

        if ($subtaskIds->isEmpty()) {
            return [];
        }

        $events = TaskSubtaskEvent::query()
            ->with('user:id,name')
            ->whereIn('subtask_id', $subtaskIds)
            ->whereIn('event', ['created', 'completed', 'reopened'])
            ->orderBy('created_at')
            ->get()
            ->groupBy('subtask_id');

        $meta = [];

        foreach ($subtaskIds as $subtaskId) {
            $subtaskEvents = $events->get($subtaskId, collect());

            $createdEvent = $subtaskEvents->firstWhere('event', 'created');

            // Ostatnie zdarzenie `completed` lub `reopened` — jeśli ostatnie to `completed`, subtask jest zamknięty
            $lastCompletedEvent = $subtaskEvents
                ->filter(fn ($e) => in_array($e->event, ['completed', 'reopened']))
                ->last();

            $completedByEvent = ($lastCompletedEvent && $lastCompletedEvent->event === 'completed')
                ? $lastCompletedEvent
                : null;

            $meta[$subtaskId] = [
                'created_by' => $createdEvent?->user?->name,
                'created_at' => $createdEvent?->created_at?->format('d.m.Y H:i'),
                'completed_by' => $completedByEvent?->user?->name,
                'completed_at' => $completedByEvent?->created_at?->format('d.m.Y H:i'),
            ];
        }

        return $meta;
    }

    public function render()
    {
        return view('livewire.task-subtasks', [
            'mentionUsersForAutocomplete' => User::orderBy('name')
                ->get()
                ->map(fn (User $u) => [
                    'name'     => $u->name,
                    'initials' => $u->initials,
                ])
                ->values()
                ->all(),
        ]);
    }
}
