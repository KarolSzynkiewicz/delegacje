@php
    $chipClass = trim('tg-col-chip tg-col-chip--sprint '.($chipClass ?? ''));
    $canPickSprint = $this->rowWritable($task, 'sprint') && ! $this->isLockedToSprint();
    $sprintValue = $task->sprint ? (string) $task->sprint->id : 'none';
    $sprintLabel = $task->sprint?->name ?: 'Brak';
    $sprintFilter = $this->pinClick('filterSprint', $sprintValue);
    $sprintExclude = $this->pinClick('filterSprint', $sprintValue, 'neq');
    $sprintExcludeTip = 'Odfiltruj · bez '.$sprintLabel;
@endphp
@if($canPickSprint)
    <x-tasks.quick-menu
        :class="$chipClass"
        :min-width="220"
        :main-click="$sprintFilter"
        :exclude-click="$sprintExclude"
        :exclude-tip="$sprintExcludeTip"
        :open-label="$task->sprint ? 'Pokaż ten sprint' : 'Pokaż poza sprintem'"
        change-label="Zmień sprint"
    >
        <x-slot:trigger>
            <i class="bi bi-flag" aria-hidden="true"></i>
            <span class="tg-col-chip__label">{{ $sprintLabel }}</span>
        </x-slot:trigger>
        <x-slot:menu>
            @include('livewire.partials.tasks-grid-sprint-menu')
        </x-slot:menu>
    </x-tasks.quick-menu>
@elseif($sprintFilter)
    <x-tasks.col-chip
        variant="sprint"
        :class="$chipClass"
        :main-click="$sprintFilter"
        :main-tip="$task->sprint ? 'Pokaż ten sprint' : 'Pokaż poza sprintem'"
        :exclude-click="$sprintExclude"
        :exclude-tip="$sprintExcludeTip"
    >{{ $sprintLabel }}</x-tasks.col-chip>
@else
    <span class="text-muted" style="font-size:0.82rem">—</span>
@endif
