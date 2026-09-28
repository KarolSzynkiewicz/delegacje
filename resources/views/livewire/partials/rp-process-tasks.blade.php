@php
    $processTasks = $selected->tasks
        ->reject(fn ($task) => $task->status === \App\Enums\TaskStatus::CANCELLED)
        ->sortBy(fn ($task) => [
            $task->status === \App\Enums\TaskStatus::COMPLETED ? 1 : 0,
            $task->due_date?->timestamp ?? ($task->starts_at?->timestamp ?? PHP_INT_MAX),
            $task->id,
        ])
        ->values();
@endphp
<div class="rp-doc-section rp-doc-section--tasks">
    <div class="rp-tasks__head">
        <div class="rp-field-label mb-0">
            <i class="bi bi-check2-square"></i>
            Zadania
            <span class="rp-tasks__count">{{ $processTasks->count() }}</span>
        </div>
        <button type="button" wire:click="openTaskModalManual" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-plus me-1"></i>Dodaj
        </button>
    </div>
    @if($processTasks->isEmpty())
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Brak zaplanowanych zadań.</p>
    @else
        <div class="rp-tasks">
            @foreach($processTasks as $task)
                @php
                    $done = $task->status === \App\Enums\TaskStatus::COMPLETED;
                    $kind = $task->isCallback() ? 'callback' : ($task->isMeeting() ? 'meeting' : 'task');
                    $kindLabel = match ($kind) {
                        'callback' => 'Oddzwonienie',
                        'meeting' => 'Spotkanie',
                        default => 'Zadanie',
                    };
                    $kindIcon = match ($kind) {
                        'callback' => 'bi-telephone-fill',
                        'meeting' => 'bi-calendar-event',
                        default => 'bi-check2-square',
                    };
                    $when = $task->isMeeting()
                        ? $task->meetingSlotLabel()
                        : $task->due_date?->format('d.m.Y');
                    $description = trim(strip_tags($task->plainDescription()));
                @endphp
                <article class="rp-task rp-task--{{ $kind }}{{ $done ? ' is-done' : '' }}" wire:key="task-{{ $task->id }}">
                    <button type="button"
                            wire:click="toggleTaskDone({{ $task->id }})"
                            class="rp-task__check"
                            aria-pressed="{{ $done ? 'true' : 'false' }}"
                            title="{{ $done ? 'Przywróć' : ($task->isMeeting() ? 'Odbyło się' : 'Oznacz jako zrobione') }}">
                        <i class="bi bi-check2"></i>
                    </button>
                    <div class="rp-task__body">
                        <a href="{{ route('tasks.show', $task) }}" class="rp-task__name">{{ $task->name }}</a>
                        @if($description !== '')
                            <p class="rp-task__desc">{{ $description }}</p>
                        @endif
                        <div class="rp-task__meta">
                            @if($when)
                                <span class="rp-task__when"><i class="bi bi-calendar3"></i>{{ $when }}</span>
                            @endif
                            <span class="rp-task__kind"><i class="bi {{ $kindIcon }}"></i>{{ $kindLabel }}</span>
                            @if($task->assignedTo)
                                <span class="rp-task__who">{{ $task->assignedTo->name }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
