@php
    $chipClass = trim('tg-col-chip tg-col-chip--assignee '.($chipClass ?? ''));
    $assignee = $task->assignedTo;
    $assigneeKey = $assignee ? (string) $assignee->id : 'unassigned';
    $assigneeLabel = $assignee?->name ?: 'Brak';
    $assigneeFilter = $this->isPlanQueue() ? null : 'filterByAssignee(\''.$assigneeKey.'\')';
    $canPickAssignee = $this->rowWritable($task, 'assigned_to');
@endphp
@if($isEditing && $editingField === 'assigned_to')
    <select wire:model="editingValue" class="form-select form-select-sm"
            wire:change="saveEdit" wire:keydown.escape="cancelEdit"
            x-data x-init="$el.focus()">
        <option value="">Nieprzypisane</option>
        @foreach($allUsers as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
        @endforeach
    </select>
@elseif($canPickAssignee)
    <x-tasks.quick-menu
        :class="$chipClass"
        :min-width="220"
        :main-click="$assigneeFilter"
        open-label="{{ $assignee ? 'Pokaż zadania tej osoby' : 'Pokaż nieprzypisane' }}"
        change-label="Zmień osobę"
    >
        <x-slot:trigger>
            <i class="bi bi-person" aria-hidden="true"></i>
            <span class="tg-col-chip__label">{{ $assigneeLabel }}</span>
        </x-slot:trigger>
        <x-slot:menu>
            @include('livewire.partials.tasks-grid-assignee-menu')
        </x-slot:menu>
    </x-tasks.quick-menu>
@elseif($assigneeFilter)
    <x-tasks.col-chip
        variant="assignee"
        :class="$chipClass"
        :main-click="$assigneeFilter"
        :main-tip="$assignee ? 'Pokaż zadania tej osoby' : 'Pokaż nieprzypisane'"
    >{{ $assigneeLabel }}</x-tasks.col-chip>
@else
    <span class="{{ $chipClass }}" style="cursor:default">
        <i class="bi bi-person" aria-hidden="true"></i>
        <span class="tg-col-chip__label">{{ $assigneeLabel }}</span>
    </span>
@endif
