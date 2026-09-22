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
    $dueValue = $task->due_date?->format('Y-m-d') ?: 'none';
    $dueLabel = $task->due_date?->format('d.m.Y') ?: 'Brak terminu';
    $dueFilter = $this->pinClick('filterDueDate', $dueValue);
    $dueExclude = $this->pinClick('filterDueDate', $dueValue, 'neq');
    $dueExcludeTip = $task->due_date
        ? 'Pokaż później niż ten dzień'
        : 'Pokaż z terminem';
    $dueWritable = $this->rowWritable($task, 'due_date');
    $dueEdit = 'startEdit('.$task->id.', \'due_date\')';
    $dueClass = 'tg-time-chip tg-time-chip--due tg-time-chip--'.$dueState;
@endphp
@if($dueWritable)
    <x-tasks.chip
        :class="$dueClass"
        :main-click="$dueFilter"
        :main-tip="$task->due_date ? 'Pokaż do tego dnia włącznie' : 'Pokaż bez terminu'"
        :exclude-click="$dueExclude"
        :exclude-tip="$dueExcludeTip"
        side="edit"
        :side-click="$dueEdit"
        :side-tip="$task->due_date ? 'Zmień do kiedy' : 'Ustaw do kiedy'"
    >
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-bullseye"></i>
        </span>
        <span class="tg-col-chip__label tg-time-chip__label">{{ $dueLabel }}</span>
    </x-tasks.chip>
@elseif($dueFilter)
    <x-tasks.chip
        :class="$dueClass"
        :main-click="$dueFilter"
        :main-tip="$task->due_date ? 'Pokaż do tego dnia włącznie' : 'Pokaż bez terminu'"
        :exclude-click="$dueExclude"
        :exclude-tip="$dueExcludeTip"
    >
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-bullseye"></i>
        </span>
        <span class="tg-col-chip__label tg-time-chip__label">{{ $dueLabel }}</span>
    </x-tasks.chip>
@else
    <span class="{{ $dueClass }} tg-time-chip--static">
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-bullseye"></i>
        </span>
        <span class="tg-time-chip__label">{{ $dueLabel }}</span>
    </span>
@endif
