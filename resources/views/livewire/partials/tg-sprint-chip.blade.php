@php
    $chipClass = trim('tg-col-chip tg-col-chip--sprint '.($chipClass ?? ''));
    $canPickSprint = $this->rowWritable($task, 'sprint') && ! $this->isLockedToSprint();
    $sprintUrl = $task->sprint ? route('sprints.show', $task->sprint) : null;
@endphp
@if($canPickSprint)
    <x-tasks.quick-menu
        :class="$chipClass"
        :min-width="220"
        :href="$sprintUrl"
        open-label="Otwórz sprint"
        change-label="Zmień sprint"
    >
        <x-slot:trigger>
            <i class="bi bi-flag" aria-hidden="true"></i>
            <span class="tg-col-chip__label">{{ $task->sprint?->name ?: '—' }}</span>
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
@else
    <span class="text-muted" style="font-size:0.82rem">—</span>
@endif
