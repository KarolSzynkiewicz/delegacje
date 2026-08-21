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
        $this->task = ProjectTask::with('subtasks')->findOrFail($task->id);
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
            'created_by' => auth()->id(),
        ]);

        TaskSubtaskEvent::log($subtask, 'created', auth()->id());

        app(UserMentionService::class)->notifySubtaskMentions(
            $this->task,
            $subtask,
            $name,
            auth()->user()
        );

        $this->newSubtaskName = '';
        $this->refreshTask();
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

        $this->refreshTask();
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
        $this->refreshTask();
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

        $this->refreshTask();
    }

    private function refreshTask(): void
    {
        // Resetuj tylko id — Livewire bezpiecznie dehydratuje prosty model bez relacji
        $this->task = ProjectTask::findOrFail($this->task->id);
    }

    /**
     * @return array<int, array{created_by: string|null, created_at: string|null, completed_by: string|null, completed_at: string|null}>
     */
    private function buildSubtaskMeta(Collection $subtasks): array
    {
        $subtaskIds = $subtasks->pluck('id');

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
        // Pobieramy świeże podzadania z bazy bez mutowania $this->task
        // (mutacja publicznej właściwości Eloquent w render() miesza Livewire)
        $subtasks = TaskSubtask::where('task_id', $this->task->id)
            ->with('assignedTo')
            ->orderBy('created_at')
            ->get();

        $pendingSubtasks = $subtasks->where('is_completed', false)->sortBy('created_at')->values();
        $completedSubtasks = $subtasks->where('is_completed', true)->sortByDesc('completed_at')->values();
        $totalSubtasks = $subtasks->count();
        $completedCount = $completedSubtasks->count();
        $progressPercentage = $totalSubtasks > 0
            ? round(($completedCount / $totalSubtasks) * 100, 2)
            : 0.0;

        // Numeracja podzadań według daty dodania
        $subtaskNumbers = [];
        foreach ($subtasks->sortBy(['created_at', 'id'])->values() as $i => $st) {
            $subtaskNumbers[$st->id] = $i + 1;
        }

        return view('livewire.task-subtasks', [
            'pendingSubtasks' => $pendingSubtasks,
            'completedSubtasks' => $completedSubtasks,
            'totalSubtasks' => $totalSubtasks,
            'completedCount' => $completedCount,
            'pendingCount' => $pendingSubtasks->count(),
            'progressPercentage' => $progressPercentage,
            'progressVariant' => $progressPercentage == 100 ? 'success' : ($progressPercentage > 0 ? 'warning' : 'default'),
            'subtaskNumbers' => $subtaskNumbers,
            'subtaskMeta' => $this->buildSubtaskMeta($subtasks),
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
