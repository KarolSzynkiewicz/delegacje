<div wire:key="task-show-qe-{{ $task->id }}">
    @if($quickEditFlash)
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ $quickEditFlash }}
            <button type="button" class="btn-close" wire:click="$set('quickEditFlash', null)" aria-label="Zamknij"></button>
        </div>
    @endif

    <div class="d-flex flex-column gap-2">
        {{-- Status (tylko podgląd) --}}
        <div>
            <small class="text-muted d-block mb-1">
                <i class="bi bi-flag me-1"></i>Status
            </small>
            @php
                $badgeVariant = match($task->status) {
                    \App\Enums\TaskStatus::PENDING => 'warning',
                    \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                    \App\Enums\TaskStatus::COMPLETED => 'success',
                    \App\Enums\TaskStatus::CANCELLED => 'danger',
                };
            @endphp
            <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
        </div>

        {{-- Projekt --}}
        <div>
            <small class="text-muted d-block mb-1">
                <i class="bi bi-folder me-1"></i>Projekt
            </small>
            @if($this->canQuickEditTask($task))
                @if($task->project)
                    <button type="button" class="badge bg-secondary border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'project', $event.clientX, $event.clientY)">
                        <i class="bi bi-folder me-1"></i>{{ $task->project->name }}
                    </button>
                @else
                    <button type="button" class="badge bg-light text-dark border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'project', $event.clientX, $event.clientY)">
                        <i class="bi bi-x-circle me-1"></i>Brak projektu
                    </button>
                @endif
            @else
                @if($task->project)
                    <a href="{{ route('projects.show', $task->project) }}" class="text-decoration-none">
                        <span class="badge bg-secondary">{{ $task->project->name }}</span>
                    </a>
                @else
                    <span class="badge bg-light text-dark"><i class="bi bi-x-circle me-1"></i>Brak projektu</span>
                @endif
            @endif
        </div>

        {{-- Przypisany --}}
        <div>
            <small class="text-muted d-block mb-1">
                <i class="bi bi-person-check me-1"></i>Przypisany
            </small>
            @if($this->canQuickEditTask($task))
                @if($task->assignedTo)
                    <button type="button" class="btn btn-link p-0 text-start text-decoration-none border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'assigned_to', $event.clientX, $event.clientY)">
                        <x-ui.person :user="$task->assignedTo" avatar-size="32px" :show-email="false" />
                    </button>
                @else
                    <button type="button" class="badge bg-light text-dark border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'assigned_to', $event.clientX, $event.clientY)">
                        <i class="bi bi-x-circle me-1"></i>Nie przypisane
                    </button>
                @endif
            @else
                @if($task->assignedTo)
                    <x-ui.person :user="$task->assignedTo" avatar-size="32px" :show-email="false" />
                @else
                    <span class="badge bg-light text-dark"><i class="bi bi-x-circle me-1"></i>Nie przypisane</span>
                @endif
            @endif
        </div>

        {{-- Priorytet --}}
        @if($task->priority)
            @php
                $priorityVariant = match((int) $task->priority) {
                    1, 2 => 'secondary',
                    3 => 'info',
                    4 => 'warning',
                    5 => 'danger',
                    default => 'secondary',
                };
                $priorityLabel = match((int) $task->priority) {
                    1 => 'Najniższy',
                    2 => 'Niski',
                    3 => 'Średni',
                    4 => 'Wysoki',
                    5 => 'Najwyższy',
                    default => '',
                };
            @endphp
            <div>
                <small class="text-muted d-block mb-1">
                    <i class="bi bi-exclamation-triangle me-1"></i>Priorytet
                </small>
                <x-ui.badge variant="{{ $priorityVariant }}">
                    <i class="bi bi-{{ $task->priority >= 4 ? 'exclamation-triangle-fill' : 'exclamation-triangle' }} me-1"></i>
                    {{ $task->priority }} - {{ $priorityLabel }}
                </x-ui.badge>
            </div>
        @endif

        {{-- Kategoria --}}
        <div>
            <small class="text-muted d-block mb-1">
                <i class="bi bi-tag me-1"></i>Kategoria
            </small>
            @if($this->canQuickEditTask($task))
                @if($task->category)
                    <button type="button" class="btn btn-link p-0 text-decoration-none border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'category', $event.clientX, $event.clientY)">
                        <x-ui.badge variant="info" class="text-start">
                            <i class="bi bi-tag me-1"></i>{{ \Illuminate\Support\Str::limit($task->category, 80) }}
                        </x-ui.badge>
                    </button>
                @else
                    <button type="button" class="badge bg-light text-dark border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'category', $event.clientX, $event.clientY)">
                        <i class="bi bi-x-circle me-1"></i>Brak
                    </button>
                @endif
            @else
                @if($task->category)
                    <x-ui.badge variant="info">
                        <i class="bi bi-tag me-1"></i>{{ $task->category }}
                    </x-ui.badge>
                @else
                    <span class="badge bg-light text-dark"><i class="bi bi-x-circle me-1"></i>Brak</span>
                @endif
            @endif
        </div>

        {{-- Termin --}}
        <div>
            <small class="text-muted d-block mb-1">
                <i class="bi bi-calendar-event me-1"></i>Termin wykonania
            </small>
            @if($task->due_date)
                @php
                    $dueDate = $task->due_date;
                    $now = \Carbon\Carbon::now();
                    $isPast = $dueDate->isPast();
                    $isToday = $dueDate->isToday();
                    $daysDiff = $now->diffInDays($dueDate, false);
                    $dueDateBadgeVariant = 'info';
                    if ($isPast || $isToday) {
                        $dueDateBadgeVariant = 'danger';
                    } elseif ($daysDiff <= 3) {
                        $dueDateBadgeVariant = 'warning';
                    }
                @endphp
                @if($this->canQuickEditTask($task))
                    <button type="button" class="btn btn-link p-0 text-decoration-none border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'due_date', $event.clientX, $event.clientY)">
                        <x-ui.badge variant="{{ $dueDateBadgeVariant }}">
                            <i class="bi bi-calendar-event me-1"></i>{{ $dueDate->format('d.m.Y') }}
                        </x-ui.badge>
                    </button>
                @else
                    <x-ui.badge variant="{{ $dueDateBadgeVariant }}">
                        <i class="bi bi-calendar-event me-1"></i>{{ $dueDate->format('d.m.Y') }}
                    </x-ui.badge>
                @endif
            @else
                @if($this->canQuickEditTask($task))
                    <button type="button" class="badge bg-light text-dark border-0" title="Szybka edycja" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'due_date', $event.clientX, $event.clientY)">
                        <i class="bi bi-x-circle me-1"></i>Nie ustawiono
                    </button>
                @else
                    <span class="badge bg-light text-dark"><i class="bi bi-x-circle me-1"></i>Nie ustawiono</span>
                @endif
            @endif
        </div>
    </div>

    @include('livewire.partials.task-quick-edit-modal')
</div>
