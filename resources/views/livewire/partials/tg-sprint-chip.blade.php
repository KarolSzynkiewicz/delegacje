@php
    $chipClass = trim('tg-col-chip tg-col-chip--sprint '.($chipClass ?? ''));
    $canPickSprint = $this->rowWritable($task, 'sprint') && ! $this->isLockedToSprint();
    $sprintUrl = $task->sprint ? route('sprints.show', $task->sprint) : null;
    $sprintFilter = (! $this->isPlanQueue() && ! $this->isLockedToSprint() && ! $task->sprint)
        ? "filterBySprint('none')"
        : null;
@endphp
@if($canPickSprint)
    <x-tasks.quick-menu
        :class="$chipClass"
        :min-width="220"
        :href="$sprintUrl"
        :main-click="$sprintFilter"
        :open-label="$task->sprint ? 'Otwórz sprint' : 'Pokaż poza sprintem'"
        change-label="Zmień sprint"
    >
        <x-slot:trigger>
            <i class="bi bi-flag" aria-hidden="true"></i>
            <span class="tg-col-chip__label">{{ $task->sprint?->name ?: 'Brak' }}</span>
        </x-slot:trigger>
        <x-slot:menu>
            @include('livewire.partials.tasks-grid-sprint-menu')
        </x-slot:menu>
    </x-tasks.quick-menu>
@elseif($task->sprint)
    <a href="{{ $sprintUrl }}" class="{{ $chipClass }}" data-tip="Otwórz sprint">
        <i class="bi bi-flag" aria-hidden="true"></i>
        <span class="tg-col-chip__label">{{ $task->sprint->name }}</span>
    </a>
@elseif($sprintFilter)
    <x-tasks.col-chip
        variant="sprint"
        :class="$chipClass"
        :main-click="$sprintFilter"
        main-tip="Pokaż poza sprintem"
    >Brak</x-tasks.col-chip>
@else
    <span class="text-muted" style="font-size:0.82rem">—</span>
@endif
