@php
    $chipClass = trim('tg-status-badge tg-mono '.$sc['cls'].' '.($chipClass ?? ''));
    $canPickStatus = in_array($statusWidget, [\App\WorkItems\StatusWidget::TaskSelect, \App\WorkItems\StatusWidget::BinarySelect], true)
        && $this->rowWritable($task, 'status');
    $binaryStatus = $statusWidget === \App\WorkItems\StatusWidget::BinarySelect;
    $statusFilter = $this->isPlanQueue() ? null : 'filterByStatus(\''.$task->status->value.'\')';
@endphp
@if($canPickStatus)
    <x-tasks.quick-menu
        :class="$chipClass"
        :min-width="155"
        :main-click="$statusFilter"
        open-label="Pokaż ten status"
        change-label="Zmień status"
    >
        <x-slot:trigger>
            {{ $sc['icon'] }} {{ $statusLabel }}
        </x-slot:trigger>
        <x-slot:menu>
            @include('livewire.partials.tasks-grid-status-menu')
        </x-slot:menu>
    </x-tasks.quick-menu>
@elseif($statusFilter)
    <x-tasks.chip :class="$chipClass" :main-click="$statusFilter" main-tip="Pokaż ten status">
        {{ $sc['icon'] }} {{ $statusLabel }}
    </x-tasks.chip>
@else
    <span class="{{ $chipClass }}">{{ $sc['icon'] }} {{ $statusLabel }}</span>
@endif
