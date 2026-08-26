<?php

namespace App\Livewire;

use App\Contracts\Llm\LlmClient;
use App\Exceptions\LlmException;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use App\Services\Llm\SubtaskSuggestionService;
use App\Services\UserMentionService;
use Illuminate\Support\Collection;
use Livewire\Component;

class TaskSubtasks extends Component
{
    public ProjectTask $task;

    public string $newSubtaskName = '';

    public ?int $editingSubtaskId = null;

    public string $editingSubtaskName = '';

    public ?int $assigningSubtaskId = null;

    public string $assignSubtaskUserId = '';

    public bool $showAiModal = false;

    public bool $aiLoading = false;

    public ?string $aiError = null;

    /** @var list<string> */
    public array $aiProposals = [];

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

    /**
     * Otwiera okno od razu, jeszcze przed odpytaniem modelu — dzięki temu
     * użytkownik widzi pracującego bota zamiast zamrożonego przycisku.
     */
    public function openAiModal(): void
    {
        $this->authorizeTaskUpdate();
        $this->reset(['aiError', 'aiProposals']);
        $this->showAiModal = true;
        $this->aiLoading = true;
    }

    public function fetchAiProposals(): void
    {
        $this->authorizeTaskUpdate();

        // Alpine odpala to raz po wyrenderowaniu okna; flaga chroni przed
        // powtórnym strzałem, gdyby Livewire przemorfował ten węzeł.
        if (! $this->aiLoading) {
            return;
        }

        try {
            $this->aiProposals = app(SubtaskSuggestionService::class)
                ->suggest(ProjectTask::query()->with('subtasks')->findOrFail($this->task->id));
        } catch (LlmException $e) {
            $this->aiError = $e->getMessage();
            $this->aiProposals = [];
        } catch (\Throwable $e) {
            $this->aiError = 'Nie udało się uzyskać propozycji od modelu: '.$e->getMessage();
            $this->aiProposals = [];
        } finally {
            $this->aiLoading = false;
        }
    }

    public function closeAiModal(): void
    {
        $this->showAiModal = false;
        $this->reset(['aiError', 'aiProposals', 'aiLoading']);
    }

    public function confirmAiProposal(int $index): void
    {
        $this->authorizeTaskUpdate();

        if (! isset($this->aiProposals[$index])) {
            return;
        }

        $name = trim($this->aiProposals[$index]);

        if ($name === '') {
            $this->aiError = 'Podzadanie nie może być puste.';

            return;
        }

        $this->createSubtaskFromAi($name);
        unset($this->aiProposals[$index]);
        $this->aiProposals = array_values($this->aiProposals);
        $this->aiError = null;

        if ($this->aiProposals === []) {
            $this->closeAiModal();
        }
    }

    public function confirmAllAiProposals(): void
    {
        $this->authorizeTaskUpdate();

        $names = collect($this->aiProposals)
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->values()
            ->all();

        if ($names === []) {
            $this->aiError = 'Brak podzadań do zatwierdzenia.';

            return;
        }

        foreach ($names as $name) {
            $this->createSubtaskFromAi($name);
        }

        $this->closeAiModal();
    }

    private function createSubtaskFromAi(string $name): void
    {
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
    }

    private function authorizeTaskUpdate(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User && ($user->isAdmin() || $user->hasPermission('tasks.update')),
            403,
        );
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

    public function startAssignSubtask(int $subtaskId): void
    {
        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        $this->cancelEditSubtask();
        $this->assigningSubtaskId = $subtask->id;
        $this->assignSubtaskUserId = $subtask->assigned_to ? (string) $subtask->assigned_to : '';
    }

    public function cancelAssignSubtask(): void
    {
        $this->assigningSubtaskId = null;
        $this->assignSubtaskUserId = '';
    }

    public function saveSubtaskAssignment(int $subtaskId): void
    {
        $this->validate([
            'assignSubtaskUserId' => 'nullable|integer|exists:users,id',
        ], [
            'assignSubtaskUserId.exists' => 'Wybrany użytkownik nie istnieje.',
        ]);

        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        $previous = $subtask->assigned_to;
        $newAssignee = $this->assignSubtaskUserId === '' ? null : (int) $this->assignSubtaskUserId;

        $subtask->update(['assigned_to' => $newAssignee]);

        if ($newAssignee && $newAssignee !== $previous && $newAssignee !== auth()->id()) {
            $assignee = User::find($newAssignee);
            $assignee?->notify(new \App\Notifications\TaskAssigned($subtask->fresh() ?? $subtask, auth()->user()));
        }

        $this->cancelAssignSubtask();
        $this->refreshTask();
    }

    public function startEditSubtask(int $subtaskId): void
    {
        $subtask = TaskSubtask::findOrFail($subtaskId);

        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        $this->cancelAssignSubtask();
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

        if ($this->assigningSubtaskId === $subtaskId) {
            $this->cancelAssignSubtask();
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

        $directoryUsers = User::orderBy('name')->get();

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
            'mentionUsersForAutocomplete' => $directoryUsers
                ->map(fn (User $u) => [
                    'name' => $u->name,
                    'initials' => $u->initials,
                ])
                ->values()
                ->all(),
            'assignUsers' => $directoryUsers,
            'llmConfigured' => app(LlmClient::class)->isConfigured(),
            'canSuggestWithAi' => auth()->user()?->isAdmin() || auth()->user()?->hasPermission('tasks.update'),
        ]);
    }
}
