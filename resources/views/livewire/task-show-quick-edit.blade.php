@php
    $canEdit = $this->canQuickEditTask($task);
    $categoryFilterUrl = $task->category
        ? \App\Support\TasksGridUrlParams::gridUrl(['searchCategory' => $task->category])
        : null;
    $priorityFilterUrl = $task->priority
        ? \App\Support\TasksGridUrlParams::gridUrl(['priority' => (string) $task->priority])
        : null;
    $dueFilterUrl = $task->due_date
        ? \App\Support\TasksGridUrlParams::gridUrl(['due' => $task->due_date->format('Y-m-d')])
        : null;
@endphp

<div wire:key="task-show-qe-{{ $task->id }}">
    <style>
        .task-show-meta .tg-facet {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            min-width: 0;
            max-width: 100%;
            width: 100%;
            justify-content: space-between;
        }
        .task-show-meta .tg-facet__value {
            appearance: none;
            background: none;
            border: 0;
            padding: 0;
            margin: 0;
            color: inherit;
            font: inherit;
            text-align: left;
            text-decoration: none;
            cursor: pointer;
            min-width: 0;
            max-width: 100%;
            border-radius: 4px;
        }
        .task-show-meta .tg-facet__value:hover {
            outline: 1px dashed rgba(168, 85, 247, 0.45);
        }
        .task-show-meta .tg-facet__edit {
            appearance: none;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            padding: 0;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--text-muted, #94a3b8);
            font-size: 0.78rem;
            opacity: 0.55;
            cursor: pointer;
        }
        .task-show-meta .tg-facet:hover .tg-facet__edit,
        .task-show-meta .tg-facet__edit:focus-visible {
            opacity: 1;
            color: var(--text-main, #f1f5f9);
            background: rgba(168, 85, 247, 0.16);
        }
        @media (hover: none) {
            .task-show-meta .tg-facet__edit { opacity: 0.85; }
        }
    </style>

    @if($quickEditFlash)
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ $quickEditFlash }}
            <button type="button" class="btn-close" wire:click="$set('quickEditFlash', null)" aria-label="Zamknij"></button>
        </div>
    @endif

    <div class="row g-4 align-items-start">
        {{-- Lewa połowa: szczegóły jak wiersze return-trips --}}
        <div class="col-md-6 task-show-meta">
            <div class="dt-card__title mb-1">Szczegóły</div>

            @unless($task->isProcedure() || $task->isCallback())
                @php
                    $badgeVariant = match($task->status) {
                        \App\Enums\TaskStatus::PENDING => 'warning',
                        \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                        \App\Enums\TaskStatus::COMPLETED => 'success',
                        \App\Enums\TaskStatus::CANCELLED => 'danger',
                    };
                @endphp
                <div class="dt-card__row">
                    <span class="dt-card__label">Status</span>
                    <span class="dt-card__value">
                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                    </span>
                </div>
            @endunless

            <div class="dt-card__row">
                <span class="dt-card__label">Przypisany</span>
                <span class="dt-card__value">
                    @if($canEdit)
                        @if($task->assignedTo)
                            <button type="button" class="btn btn-link p-0 text-start text-decoration-none border-0" title="Zmień przypisanie" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'assigned_to', $event.clientX, $event.clientY)">
                                <x-ui.person :user="$task->assignedTo" avatar-size="28px" :show-email="false" />
                            </button>
                        @else
                            <button type="button" class="badge bg-light text-dark border-0 d-inline-flex align-items-center gap-1" title="Przypisz osobę" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'assigned_to', $event.clientX, $event.clientY)">
                                <i class="bi bi-person-plus task-meta-icon"></i>Nie przypisane
                            </button>
                        @endif
                    @else
                        @if($task->assignedTo)
                            <x-ui.person :user="$task->assignedTo" avatar-size="28px" :show-email="false" />
                        @else
                            <span class="badge bg-light text-dark d-inline-flex align-items-center gap-1"><i class="bi bi-person task-meta-icon"></i>Nie przypisane</span>
                        @endif
                    @endif
                </span>
            </div>

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
            <div class="dt-card__row">
                <span class="dt-card__label">Priorytet</span>
                <span class="dt-card__value">
                    <div class="tg-facet">
                        @if($task->priority)
                            <a href="{{ $priorityFilterUrl }}"
                               class="tg-facet__value"
                               title="Zawęź listę do tego priorytetu">
                                <x-ui.badge variant="{{ $priorityVariant }}">
                                    <i class="bi bi-{{ $task->priority >= 4 ? 'exclamation-triangle-fill' : 'exclamation-triangle' }} me-1"></i>
                                    {{ $task->priority }} — {{ $priorityLabel }}
                                </x-ui.badge>
                            </a>
                        @else
                            <span class="badge bg-light text-dark d-inline-flex align-items-center gap-1">
                                <i class="bi bi-x-circle task-meta-icon"></i>Brak
                            </span>
                        @endif
                        @if($canEdit)
                            <button type="button"
                                    class="tg-facet__edit"
                                    title="Edytuj priorytet"
                                    aria-label="Edytuj priorytet"
                                    x-data
                                    @click.prevent.stop="$wire.openQuickEdit({{ $task->id }}, 'priority', $event.clientX, $event.clientY)">
                                <i class="bi bi-pencil"></i>
                            </button>
                        @endif
                    </div>
                </span>
            </div>

            @unless($task->isProcedure() || $task->isCallback())
                <div class="dt-card__row">
                    <span class="dt-card__label">Kategoria</span>
                    <span class="dt-card__value">
                        <div class="tg-facet">
                            @if($task->category)
                                <a href="{{ $categoryFilterUrl }}"
                                   class="tg-facet__value"
                                   title="Zawęź listę do tej kategorii">
                                    <x-ui.badge variant="info" class="text-start d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-tag task-meta-icon"></i>{{ \Illuminate\Support\Str::limit($task->category, 80) }}
                                    </x-ui.badge>
                                </a>
                            @else
                                <span class="badge bg-light text-dark d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-x-circle task-meta-icon"></i>Brak
                                </span>
                            @endif
                            @if($canEdit)
                                <button type="button"
                                        class="tg-facet__edit"
                                        title="Edytuj kategorię"
                                        aria-label="Edytuj kategorię"
                                        x-data
                                        @click.prevent.stop="$wire.openQuickEdit({{ $task->id }}, 'category', $event.clientX, $event.clientY)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif
                        </div>
                    </span>
                </div>
            @endunless

            <div class="dt-card__row">
                <span class="dt-card__label">Termin</span>
                <span class="dt-card__value">
                    @php
                        $dueDate = $task->due_date;
                        $dueDateBadgeVariant = 'info';
                        if ($dueDate) {
                            $now = \Carbon\Carbon::now();
                            $isPast = $dueDate->isPast();
                            $isToday = $dueDate->isToday();
                            $daysDiff = $now->diffInDays($dueDate, false);
                            if ($isPast || $isToday) {
                                $dueDateBadgeVariant = 'danger';
                            } elseif ($daysDiff <= 3) {
                                $dueDateBadgeVariant = 'warning';
                            }
                        }
                    @endphp
                    <div class="tg-facet">
                        @if($dueDate)
                            <a href="{{ $dueFilterUrl }}"
                               class="tg-facet__value"
                               title="Zawęź listę do tego dnia">
                                <x-ui.badge variant="{{ $dueDateBadgeVariant }}">
                                    <i class="bi bi-calendar-event me-1"></i>{{ $dueDate->format('d.m.Y') }}
                                </x-ui.badge>
                            </a>
                        @else
                            <span class="badge bg-light text-dark"><i class="bi bi-x-circle me-1"></i>Nie ustawiono</span>
                        @endif
                        @if($canEdit)
                            <button type="button"
                                    class="tg-facet__edit"
                                    title="Edytuj termin"
                                    aria-label="Edytuj termin"
                                    x-data
                                    @click.prevent.stop="$wire.openQuickEdit({{ $task->id }}, 'due_date', $event.clientX, $event.clientY)">
                                <i class="bi bi-pencil"></i>
                            </button>
                        @endif
                    </div>
                </span>
            </div>

            <div class="dt-card__row">
                <span class="dt-card__label">Sprint</span>
                <span class="dt-card__value">
                    @if($canEdit)
                        <button type="button" class="btn btn-link p-0 text-start text-decoration-none border-0" title="Zmień sprint" x-data @click.prevent="$wire.openQuickEdit({{ $task->id }}, 'sprint_id', $event.clientX, $event.clientY)">
                            @if($task->sprint)
                                <span class="fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-flag task-meta-icon"></i>{{ $task->sprint->label() }}
                                </span>
                            @else
                                <span class="badge bg-light text-dark d-inline-flex align-items-center gap-1"><i class="bi bi-inbox task-meta-icon"></i>Poza sprintem</span>
                            @endif
                        </button>
                    @else
                        @if($task->sprint)
                            <a href="{{ route('sprints.show', $task->sprint) }}" class="d-inline-flex align-items-center gap-1">
                                <i class="bi bi-flag task-meta-icon"></i>{{ $task->sprint->label() }}
                            </a>
                        @else
                            <span class="text-muted d-inline-flex align-items-center gap-1"><i class="bi bi-inbox task-meta-icon"></i>Poza sprintem</span>
                        @endif
                    @endif
                </span>
            </div>

            @if($sourceCard || $task->attachments->count() > 0)
                <div class="dt-card__row">
                    <span class="dt-card__label">Źródło</span>
                    <span class="dt-card__value d-flex flex-column align-items-start gap-2">
                        @if($sourceCard)
                            <a href="{{ $sourceCard['url'] }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}
                            </a>
                        @endif
                        @if($task->attachments->count() > 0)
                            <div class="w-100">
                                <small class="text-muted d-block mb-1">
                                    <i class="bi bi-paperclip me-1"></i>Załączniki
                                </small>
                                <x-attachment-list :attachments="$task->attachments" />
                            </div>
                        @endif
                    </span>
                </div>
            @endif
        </div>

        {{-- Prawa połowa: opis w stylu composera komentarzy --}}
        <div class="col-md-6">
            <div class="dt-card__title mb-2">Opis</div>

            @if($canEdit)
                <form wire:submit.prevent="saveDescription" class="comments-composer task-desc-composer">
                    <textarea
                        class="comments-composer-input @error('descriptionDraft') is-invalid @enderror"
                        rows="6"
                        wire:model.defer="descriptionDraft"
                        placeholder="Opisz zadanie… Ctrl+Enter zapisuje"
                        @keydown.ctrl.enter.prevent="$wire.saveDescription()"
                        @keydown.meta.enter.prevent="$wire.saveDescription()"
                    ></textarea>
                    <div class="comments-composer-toolbar">
                        <span class="comments-composer-files text-muted">
                            @if($task->description)
                                Edytuj i zapisz Enterem
                            @else
                                Dodaj opis
                            @endif
                        </span>
                        <button type="submit" class="comments-icon-btn comments-send-btn" title="Zapisz opis (Ctrl+Enter)" aria-label="Zapisz opis">
                            <i class="bi bi-arrow-return-left"></i>
                        </button>
                    </div>
                </form>
                @error('descriptionDraft')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            @else
                @if($task->description)
                    <div class="text-break" style="white-space:pre-wrap">{{ $task->plainDescription() }}</div>
                @else
                    <p class="text-muted mb-0">Brak opisu.</p>
                @endif
            @endif
        </div>
    </div>

    @include('livewire.partials.task-quick-edit-modal')
</div>
