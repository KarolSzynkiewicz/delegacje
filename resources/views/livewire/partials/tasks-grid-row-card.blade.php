@php
    $isWorkItem   = $task instanceof \App\Models\WorkItem;
    $openUrl      = $this->itemOpenUrl($task);
    $canAddSubtask = $this->rowSupports($task, 'subtasks');
    $canExpand   = $this->rowExpandable($task);
    $isExpanded  = $canExpand && in_array($task->id, $expandedTasks);
    $isEditing   = $editingTaskId === $task->id;
    $statusWidget = $this->rowStatusWidget($task);
    $statusLabel = $this->rowStatusLabel($task);

    [$subtasksAll, $subtaskTotal, $subtaskDone] = $this->rowSubtaskStats($task, $isExpanded);
    $commentsCount = (int) ($task->comments_count ?? ($task->relationLoaded('comments') ? $task->comments->count() : 0));

    $statusMap = [
        'pending'     => ['cls' => 's-pending',    'icon' => '⏳'],
        'in_progress' => ['cls' => 's-in_progress','icon' => '▶'],
        'completed'   => ['cls' => 's-completed',  'icon' => '✓'],
        'cancelled'   => ['cls' => 's-cancelled',  'icon' => '✗'],
    ];
    $sc = $statusMap[$task->status->value] ?? $statusMap['pending'];
    $approvalDecision = $isWorkItem ? $task->approvalDecision() : null;

    $priorityMap = [
        1 => ['tone' => 'p1', 'label' => 'Najniższy'],
        2 => ['tone' => 'p2', 'label' => 'Niski'],
        3 => ['tone' => 'p3', 'label' => 'Średni'],
        4 => ['tone' => 'p4', 'label' => 'Wysoki'],
        5 => ['tone' => 'p5', 'label' => 'Krytyczny'],
    ];
    $pc = $priorityMap[$task->priority] ?? null;

    $borderColor = [
        'pending'     => '#f59e0b',
        'in_progress' => '#a855f7',
        'completed'   => '#10b981',
        'cancelled'   => '#ef4444',
    ][$task->status->value] ?? 'rgba(255,255,255,0.15)';

    if ($isWorkItem && $task->type === \App\Enums\WorkItemType::Approval) {
        if ($approvalDecision === \App\Enums\ApprovalDecision::Approved) {
            $sc = ['cls' => 's-completed', 'icon' => '✓'];
            $borderColor = '#10b981';
        } elseif ($approvalDecision === \App\Enums\ApprovalDecision::Rejected) {
            $sc = ['cls' => 's-cancelled', 'icon' => '✗'];
            $borderColor = '#ef4444';
        } else {
            $sc = ['cls' => 's-pending', 'icon' => '⏳'];
            $borderColor = '#f59e0b';
        }
    }

    $sourceCard = $task->sourceCard();
    $ediName = $this->ediCell($task, 'name');
    $canPickStatus = in_array($statusWidget, [\App\WorkItems\StatusWidget::TaskSelect, \App\WorkItems\StatusWidget::BinarySelect], true)
        && $this->rowWritable($task, 'status');
    $binaryStatus = $statusWidget === \App\WorkItems\StatusWidget::BinarySelect;
@endphp

<x-ui.card
    class="dt-card tg-dt-card{{ $isExpanded ? ' is-expanded' : '' }}{{ ! $this->isPlanQueue() && $this->isSelected((int) $task->id) ? ' is-selected' : '' }}{{ $this->isPlanQueue() && (int) $this->planPinId === (int) $task->id ? ' is-pin' : '' }}"
    wire:key="tg-card-{{ $task->id }}"
    style="border-left-color: {{ $borderColor }}"
    data-tg-id="{{ $task->id }}"
    data-plan-drag="{{ $this->isPlanQueue() && $isWorkItem ? 'queue:'.$task->id : '' }}"
    data-plan-title="{{ $this->isPlanQueue() && $isWorkItem ? $task->name : '' }}"
    data-plan-type="{{ $this->isPlanQueue() && $isWorkItem ? $task->type->value : '' }}"
>
    <div class="dt-card__title">
        <div class="tg-dt-card__heading">
            @if($canExpand && ! $this->isPlanQueue())
                <button type="button"
                        wire:click="toggleExpand({{ $task->id }})"
                        class="tg-card-expand-btn tg-dt-hit tg-expand-btn{{ $isExpanded ? ' is-open' : '' }}"
                        data-tg-expand="{{ $task->id }}"
                        aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                        title="{{ $isExpanded ? 'Zwiń' : 'Rozwiń' }}">
                    <i class="bi bi-chevron-right" style="font-size:0.75rem"></i>
                </button>
            @endif
            @if($this->rowSelectable($task))
                <span class="tg-dt-hit" @pointerdown.stop>
                    @include('livewire.partials.tasks-grid-select')
                </span>
            @endif
            @if($canAddSubtask && $subtaskTotal > 0 && (! $this->isPlanQueue() || in_array('subtasks', $visibleColumns, true)))
                <span class="tg-card-subtask-badge" data-tg-sub-stats="{{ $task->id }}" data-tip="{{ $subtaskDone }}/{{ $subtaskTotal }} podzadań">
                    {{ $subtaskDone }}/{{ $subtaskTotal }}
                </span>
            @endif
            @if($ediName)
                <span class="tg-dt-card__name tg-edi tg-edi--{{ $ediName['kind'] }}">
                    @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediName, 'rowId' => $task->id, 'field' => 'name'])
                </span>
            @elseif($isEditing && $editingField === 'name')
                <input type="text" wire:model="editingValue" class="form-control form-control-sm tg-dt-hit"
                       wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                       x-data x-init="$el.focus(); $el.select()">
            @elseif($this->isPlanQueue() && $isWorkItem)
                <a href="{{ $openUrl }}"
                   class="tg-dt-card__name"
                   title="{{ $task->name }}"
                   wire:click.prevent="$parent.openEvent('item', {{ $task->id }})">
                    {{ $task->name }}
                </a>
            @else
                <a href="{{ $openUrl }}" class="stretched-link tg-dt-card__name" title="{{ $task->name }}">
                    {{ $task->name }}
                </a>
            @endif
            @if(! $this->isPlanQueue() && ! $ediName && ! ($isEditing && $editingField === 'name') && $this->rowWritable($task, 'name'))
                    <button type="button"
                            class="tg-facet__edit tg-dt-hit"
                            wire:click.stop="startEdit({{ $task->id }}, 'name')"
                            title="Edytuj tytuł"
                            aria-label="Edytuj tytuł">
                        <i class="bi bi-pencil"></i>
                    </button>
            @endif
            @if(! $this->isPlanQueue() && $isWorkItem && $task->type === \App\Enums\WorkItemType::Approval)
                <span class="tg-dt-hit"><x-ui.approval-decision :decision="$approvalDecision" size="sm" /></span>
            @endif
            @if(! $this->isPlanQueue() && $sourceCard && ($sourceCard['url'] ?? '') !== $openUrl)
                <a href="{{ $sourceCard['url'] }}"
                   class="tg-card-source-link tg-dt-hit"
                   title="{{ $sourceCard['label'] }}"
                   onclick="event.stopPropagation()">
                    <i class="bi {{ $sourceCard['icon'] }}"></i>
                </a>
            @endif
        </div>
    </div>

    @unless ($isExpanded)
    @if(in_array('type', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Typ</span>
            <span class="dt-card__value">
                <i class="bi {{ $this->rowTypeIcon($task) }} me-1 opacity-75"></i>{{ $this->rowTypeLabel($task) }}
            </span>
        </div>
    @endif

    @if(in_array('status', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Status</span>
            <span class="dt-card__value">
                @if($canPickStatus)
                    <div x-data="{ open: false, top: 0, left: 0 }" class="tg-dt-hit">
                        <button type="button"
                                @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); top=r.bottom+4; left=Math.min(r.left, window.innerWidth-165); open=true"
                                class="tg-status-badge tg-col-chip--split {{ $sc['cls'] }}"
                                style="cursor:pointer">
                            <span class="tg-col-chip__main">{{ $sc['icon'] }} {{ $statusLabel }}</span>
                            <span class="tg-col-chip__side" aria-hidden="true">
                                <i class="bi bi-chevron-down"></i>
                            </span>
                        </button>
                        <template x-teleport="body">
                            <ul x-show="open" x-cloak
                                @click.outside="open = false"
                                :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;min-width:155px;font-size:0.84rem`"
                                class="dropdown-menu show py-1 shadow-lg tg-teleport-menu">
                                <li>
                                    <button type="button"
                                            class="dropdown-item py-2 {{ $task->status->value === 'pending' ? 'active' : '' }}"
                                            wire:click="quickStatusChange({{ $task->id }}, 'pending')"
                                            @click="open=false">
                                        ⏳ Oczekujące
                                    </button>
                                </li>
                                @unless($binaryStatus)
                                <li>
                                    <button type="button"
                                            class="dropdown-item py-2 {{ $task->status->value === 'in_progress' ? 'active' : '' }}"
                                            wire:click="quickStatusChange({{ $task->id }}, 'in_progress')"
                                            @click="open=false">
                                        ▶ W trakcie
                                    </button>
                                </li>
                                @endunless
                                <li>
                                    <button type="button"
                                            class="dropdown-item py-2 {{ $task->status->value === 'completed' ? 'active' : '' }}"
                                            wire:click="quickStatusChange({{ $task->id }}, 'completed')"
                                            @click="open=false">
                                        ✓ Ukończone
                                    </button>
                                </li>
                                @unless($binaryStatus)
                                <li>
                                    <button type="button"
                                            class="dropdown-item py-2 {{ $task->status->value === 'cancelled' ? 'active' : '' }}"
                                            wire:click="quickStatusChange({{ $task->id }}, 'cancelled')"
                                            @click="open=false">
                                        ✗ Anulowane
                                    </button>
                                </li>
                                @endunless
                            </ul>
                        </template>
                    </div>
                @else
                    <span class="tg-status-badge {{ $sc['cls'] }}">{{ $sc['icon'] }} {{ $statusLabel }}</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('sprint', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Sprint</span>
            <span class="dt-card__value">
                @php $canPickSprint = $this->rowWritable($task, 'sprint') && ! $this->isLockedToSprint(); @endphp
                @if($canPickSprint)
                    <x-tasks.quick-menu
                        class="tg-col-chip tg-col-chip--sprint tg-dt-hit"
                        :min-width="220"
                        :href="$task->sprint ? route('sprints.show', $task->sprint) : null"
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
                    <a href="{{ route('sprints.show', $task->sprint) }}" class="tg-col-chip tg-col-chip--sprint" data-tip="Otwórz sprint">
                        <i class="bi bi-flag" aria-hidden="true"></i>
                        <span class="tg-col-chip__label">{{ $task->sprint->name }}</span>
                    </a>
                @else
                    <span class="text-muted">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('category', $visibleColumns))
        @php $ediCategory = $this->ediCell($task, 'category'); @endphp
        <div class="dt-card__row">
            <span class="dt-card__label">Kategoria</span>
            <span class="dt-card__value">
                @if($ediCategory)
                    <span class="tg-edi tg-edi--{{ $ediCategory['kind'] }}">
                        @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediCategory, 'rowId' => $task->id, 'field' => 'category'])
                    </span>
                @elseif($isEditing && $editingField === 'category')
                    <input type="text" wire:model="editingValue" class="form-control form-control-sm"
                           wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                           x-data x-init="$el.focus(); $el.select()">
                @elseif($this->rowWritable($task, 'category') && $task->category)
                    <x-tasks.col-chip
                        variant="category"
                        side="edit"
                        class="tg-dt-hit"
                        :side-task-id="$task->id"
                        :filter-category="$task->category"
                        side-tip="Edytuj kategorię"
                        side-label="Edytuj kategorię"
                    >{{ $task->category }}</x-tasks.col-chip>
                @elseif($this->rowWritable($task, 'category'))
                    <x-tasks.col-chip
                        variant="category"
                        side="edit"
                        class="tg-dt-hit"
                        :side-task-id="$task->id"
                        :edit-category="true"
                        side-tip="Wpisz kategorię"
                        side-label="Wpisz kategorię"
                    >—</x-tasks.col-chip>
                @elseif($task->category)
                    <x-tasks.col-chip
                        variant="category"
                        class="tg-dt-hit"
                        :filter-category="$task->category"
                    >{{ $task->category }}</x-tasks.col-chip>
                @else
                    <span class="text-muted">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('assigned_to', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Przypisany</span>
            <span class="dt-card__value">
                @if($isEditing && $editingField === 'assigned_to')
                    <select wire:model="editingValue" class="form-select form-select-sm"
                            wire:change="saveEdit" wire:keydown.escape="cancelEdit"
                            x-data x-init="$el.focus()">
                        <option value="">Nieprzypisane</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                @elseif($this->rowWritable($task, 'assigned_to'))
                    <span wire:click.stop="startEdit({{ $task->id }}, 'assigned_to')" class="tg-hover-edit">
                        @if($task->assignedTo)
                            <x-ui.person :user="$task->assignedTo" avatar-size="22px" :show-email="false" name-class="small" />
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </span>
                @elseif($task->assignedTo)
                    <x-ui.person :user="$task->assignedTo" avatar-size="22px" :show-email="false" name-class="small" />
                @else
                    <span class="text-muted">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('created_by', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Autor</span>
            <span class="dt-card__value">
                @if($task->createdBy)
                    <x-ui.person :user="$task->createdBy" avatar-size="22px" :show-email="false" name-class="small" />
                @else
                    <span class="text-muted">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('priority', $visibleColumns))
        @php $ediPriority = $this->ediCell($task, 'priority'); @endphp
        <div class="dt-card__row">
            <span class="dt-card__label">Priorytet</span>
            <span class="dt-card__value">
                @if($ediPriority)
                    <span class="tg-edi tg-edi--{{ $ediPriority['kind'] }}">
                        @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediPriority, 'rowId' => $task->id, 'field' => 'priority'])
                    </span>
                @elseif($this->rowWritable($task, 'priority'))
                    <x-tasks.quick-menu class="tg-col-chip tg-col-chip--priority {{ $pc ? 'tg-col-chip--'.$pc['tone'] : 'tg-col-chip--p1' }} tg-dt-hit" :min-width="160">
                        <x-slot:trigger>
                            <i class="bi bi-fire" aria-hidden="true"></i>
                            <span class="tg-col-chip__label">{{ $pc['label'] ?? '—' }}</span>
                        </x-slot:trigger>
                        <x-slot:menu>
                            @include('livewire.partials.tasks-grid-priority-menu')
                        </x-slot:menu>
                    </x-tasks.quick-menu>
                @elseif($pc)
                    <span class="tg-col-chip tg-col-chip--priority tg-col-chip--{{ $pc['tone'] }}">
                        <i class="bi bi-fire" aria-hidden="true"></i>
                        <span class="tg-col-chip__label">{{ $pc['label'] }}</span>
                    </span>
                @else
                    <span class="tg-mono" style="color:rgba(255,255,255,0.35)">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('due_date', $visibleColumns))
        @php $ediDue = $this->ediCell($task, 'due_date'); @endphp
        <div class="dt-card__row">
            <span class="dt-card__label">Do kiedy</span>
            <span class="dt-card__value">
                @if($ediDue)
                    <span class="tg-edi tg-edi--{{ $ediDue['kind'] }}">
                        @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediDue, 'rowId' => $task->id, 'field' => 'due_date'])
                    </span>
                @elseif($isEditing && $editingField === 'due_date')
                    <input type="date" wire:model="editingValue" class="form-control form-control-sm"
                           wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                           x-data x-init="$el.focus()">
                @else
                    @include('livewire.partials.tg-due-chip', ['task' => $task, 'dueClickStop' => true])
                @endif
            </span>
        </div>
    @endif

    @if(in_array('blocks', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">W kalendarzu</span>
            <span class="dt-card__value">
                @if($isWorkItem)
                    @include('livewire.partials.tg-schedule-cell', ['item' => $task, 'assignFirst' => true, 'assignClickStop' => true])
                @else
                    <span class="text-muted">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('subtasks', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Podzadania</span>
            <span class="dt-card__value">
                @if($subtaskTotal > 0)
                    {{ $subtaskDone }}/{{ $subtaskTotal }}
                @else
                    <span class="text-muted">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('comments', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Komentarze</span>
            <span class="dt-card__value">
                @if($commentsCount > 0)
                    <a href="{{ $openUrl }}" class="text-decoration-none tg-dt-hit">
                        <i class="bi bi-chat-dots me-1"></i>{{ $commentsCount }}
                    </a>
                @else
                    <span class="text-muted">—</span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('created_at', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Utworzono</span>
            <span class="dt-card__value tg-date-plain tg-mono">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                {{ $task->created_at?->format('d.m.Y') ?? '—' }}
            </span>
        </div>
    @endif

    @if(in_array('updated_at', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Zmieniono</span>
            <span class="dt-card__value tg-date-plain tg-mono">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                {{ $task->updated_at?->format('d.m.Y') ?? '—' }}
            </span>
        </div>
    @endif
    @endunless

    @if($isExpanded)
        @include('livewire.partials.tasks-grid-expand-card')
    @endif
</x-ui.card>
