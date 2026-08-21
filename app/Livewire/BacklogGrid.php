<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class BacklogGrid extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public bool $myItemsOnly = false;

    public bool $hideCallbacks = true;

    public string $type = '';

    public string $sortField = 'due_at';

    public string $sortDirection = 'asc';

    public string $groupBy = '';

    public bool $showAddRow = false;

    public string $newTaskName = '';

    public string $newTaskAssignedTo = '';

    public string $newTaskDueDate = '';

    public ?string $flash = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'myItemsOnly' => ['except' => false],
        'hideCallbacks' => ['except' => true],
        'type' => ['except' => ''],
        'sortField' => ['except' => 'due_at'],
        'sortDirection' => ['except' => 'asc'],
        'groupBy' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleComplete(int $id): void
    {
        $item = WorkItem::query()->find($id);
        if (! $item || ! $item->canCompleteInline()) {
            return;
        }

        $item->status->isOpen() ? $item->complete() : $item->reopen();
    }

    public function addTask(): void
    {
        $this->validate([
            'newTaskName' => 'required|string|max:255',
            'newTaskAssignedTo' => 'nullable|exists:users,id',
            'newTaskDueDate' => 'nullable|date',
        ]);

        ProjectTask::query()->create([
            'name' => trim($this->newTaskName),
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->newTaskAssignedTo ?: auth()->id(),
            'due_date' => $this->newTaskDueDate ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['newTaskName', 'newTaskAssignedTo', 'newTaskDueDate', 'showAddRow']);
        $this->flash = 'Zadanie dodane.';
    }

    public function render()
    {
        $query = $this->filteredQuery();

        if ($this->sortField === 'due_at') {
            $query->orderByRaw('due_at IS NULL, due_at '.$this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }
        $query->orderByDesc('id');

        $query->with(['assignee', 'sprint', 'source']);

        if ($this->groupBy !== '') {
            $items = $query->limit(400)->get();
            $grouped = $items->groupBy(fn (WorkItem $item) => $this->groupValue($item));
            $paginator = null;
        } else {
            $grouped = null;
            $paginator = $query->paginate(50);
        }

        return view('livewire.backlog-grid', [
            'items' => $paginator,
            'groupedItems' => $grouped,
            'allUsers' => User::orderedDirectory(),
            'types' => WorkItemType::cases(),
        ]);
    }

    protected function filteredQuery(): Builder
    {
        $query = WorkItem::query();

        if ($this->myItemsOnly) {
            $query->where('assignee_id', auth()->id());
        }

        if ($this->hideCallbacks) {
            $query->where('type', '!=', WorkItemType::Callback);
        }

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->status === '' || $this->status === 'active') {
            $query->whereIn('status', [WorkItemStatus::Pending, WorkItemStatus::InProgress]);
        } elseif ($this->status === 'closed') {
            $query->whereIn('status', [WorkItemStatus::Completed, WorkItemStatus::Cancelled]);
        } elseif ($this->status !== 'all' && $this->status !== '') {
            $query->where('status', $this->status);
        }

        return $query;
    }

    public function groupValue(WorkItem $item): string
    {
        return match ($this->groupBy) {
            'type' => $item->type->value,
            'status' => $item->status->value,
            'assignee' => $item->assignee_id ? (string) $item->assignee_id : '',
            default => '',
        };
    }

    public function groupLabel(WorkItem $item): string
    {
        return match ($this->groupBy) {
            'type' => $item->type->label(),
            'status' => $item->status->label(),
            'assignee' => $item->assignee?->name ?? 'Nieprzypisane',
            default => 'Wszystkie',
        };
    }
}
