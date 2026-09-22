@php
    $chipClass = trim(($chipClass ?? ''));
    $ediPriority = $this->ediCell($task, 'priority');
    $priorityValue = $pc ? (string) $task->priority : 'none';
    $priorityLabel = $pc['label'] ?? 'Brak';
    $priorityFilter = $this->pinClick('filterPriority', $priorityValue);
    $priorityExclude = $this->pinClick('filterPriority', $priorityValue, 'neq');
    $priorityExcludeTip = 'Odfiltruj · bez '.$priorityLabel;
    $priorityChipClass = trim('tg-col-chip tg-col-chip--priority '.($pc ? 'tg-col-chip--'.$pc['tone'] : 'tg-col-chip--p1').' '.$chipClass);
@endphp
@if($ediPriority)
    @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediPriority, 'rowId' => $task->id, 'field' => 'priority'])
@elseif($this->rowWritable($task, 'priority'))
    <x-tasks.quick-menu
        :class="$priorityChipClass"
        :min-width="160"
        :main-click="$priorityFilter"
        :exclude-click="$priorityExclude"
        :exclude-tip="$priorityExcludeTip"
        :open-label="$pc ? 'Pokaż ten priorytet' : 'Pokaż bez priorytetu'"
        change-label="Zmień priorytet"
    >
        <x-slot:trigger>
            <i class="bi bi-fire" aria-hidden="true"></i>
            <span class="tg-col-chip__label">{{ $priorityLabel }}</span>
        </x-slot:trigger>
        <x-slot:menu>
            @include('livewire.partials.tasks-grid-priority-menu')
        </x-slot:menu>
    </x-tasks.quick-menu>
@elseif($priorityFilter)
    <x-tasks.col-chip
        variant="priority"
        :tone="$pc['tone'] ?? 'p1'"
        :class="$chipClass"
        :main-click="$priorityFilter"
        :main-tip="$pc ? 'Pokaż ten priorytet' : 'Pokaż bez priorytetu'"
        :exclude-click="$priorityExclude"
        :exclude-tip="$priorityExcludeTip"
    >{{ $priorityLabel }}</x-tasks.col-chip>
@else
    <span class="tg-mono" style="font-size:0.78rem; color:rgba(255,255,255,0.2)">—</span>
@endif
