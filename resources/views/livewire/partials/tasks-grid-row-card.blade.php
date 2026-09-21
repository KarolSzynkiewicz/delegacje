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
                @include('livewire.partials.tg-status-chip', ['chipClass' => 'tg-dt-hit'])
            </span>
        </div>
    @endif

    @if(in_array('sprint', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Sprint</span>
            <span class="dt-card__value">
                @include('livewire.partials.tg-sprint-chip', ['chipClass' => 'tg-dt-hit'])
            </span>
        </div>
    @endif

    @if(in_array('category', $visibleColumns))
        @php $ediCategory = $this->ediCell($task, 'category'); @endphp
        <div class="dt-card__row">
            <span class="dt-card__label">Kategoria</span>
            <span class="dt-card__value {{ $ediCategory ? 'tg-edi tg-edi--'.$ediCategory['kind'] : '' }}">
                @include('livewire.partials.tg-category-chip', ['chipClass' => 'tg-dt-hit'])
            </span>
        </div>
    @endif

    @if(in_array('assigned_to', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Przypisany</span>
            <span class="dt-card__value">
                @include('livewire.partials.tg-assignee-chip', ['chipClass' => 'tg-dt-hit'])
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
            <span class="dt-card__value {{ $ediPriority ? 'tg-edi tg-edi--'.$ediPriority['kind'] : '' }}">
                @include('livewire.partials.tg-priority-chip', ['chipClass' => 'tg-dt-hit'])
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
