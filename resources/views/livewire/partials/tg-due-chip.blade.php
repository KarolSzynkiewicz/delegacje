@php
    $dueState = 'none';
    if ($task->due_date) {
        if ($task->due_date->isPast() || $task->due_date->isToday()) {
            $dueState = 'late';
        } elseif ($task->due_date->diffInDays(now()) <= 3) {
            $dueState = 'soon';
        } else {
            $dueState = 'ok';
        }
    }
    $dueLabel = $task->due_date?->format('d.m.Y') ?: 'Brak terminu';
    $dueWritable = $this->rowWritable($task, 'due_date');
    $dueClickStop = $dueClickStop ?? false;
@endphp
@if($dueWritable)
    <button type="button"
            class="tg-time-chip tg-time-chip--due tg-time-chip--{{ $dueState }}"
            @if($dueClickStop) wire:click.stop="startEdit({{ $task->id }}, 'due_date')"
            @else wire:click="startEdit({{ $task->id }}, 'due_date')" @endif
            data-tip="Ustaw do kiedy"
            aria-label="Ustaw do kiedy">
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-bullseye"></i>
        </span>
        <span class="tg-time-chip__label">{{ $dueLabel }}</span>
        <i class="bi bi-chevron-right tg-time-chip__go" aria-hidden="true"></i>
    </button>
@else
    <span class="tg-time-chip tg-time-chip--due tg-time-chip--{{ $dueState }} tg-time-chip--static">
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-bullseye"></i>
        </span>
        <span class="tg-time-chip__label">{{ $dueLabel }}</span>
    </span>
@endif
