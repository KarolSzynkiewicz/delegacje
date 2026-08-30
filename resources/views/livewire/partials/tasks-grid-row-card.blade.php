@php
    $isWorkItem   = $task instanceof \App\Models\WorkItem;
    $openUrl      = $this->itemOpenUrl($task);
    $sprintUrl    = $task->sprint ? route('sprints.show', $task->sprint) : null;
    $canAddSubtask = $this->rowSupports($task, 'subtasks');
    $canExpand   = $this->rowExpandable($task);
    $isExpanded  = $canExpand && in_array($task->id, $expandedTasks);
    $isEditing   = $editingTaskId === $task->id;
    $statusWidget = $this->rowStatusWidget($task);
    $statusLabel = $this->rowStatusLabel($task);

    $subtasksAll  = $task->subtasks->sortBy(['sort_order', 'created_at']);
    $subtaskTotal = $subtasksAll->count();
    $subtaskDone  = $subtasksAll->where('is_completed', true)->count();
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
        1 => ['color' => '#94a3b8', 'label' => '↓ Najniższy'],
        2 => ['color' => '#60a5fa', 'label' => '↓ Niski'],
        3 => ['color' => '#fb923c', 'label' => '→ Średni'],
        4 => ['color' => '#f87171', 'label' => '↑ Wysoki'],
        5 => ['color' => '#c084fc', 'label' => '↑ Krytyczny'],
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

    $dueStyle = 'color:rgba(255,255,255,0.4)';
    if ($task->due_date) {
        if ($task->due_date->isPast() || $task->due_date->isToday()) {
            $dueStyle = 'color:#f87171;font-weight:600';
        } elseif ($task->due_date->diffInDays(now()) <= 3) {
            $dueStyle = 'color:#fb923c;font-weight:600';
        } else {
            $dueStyle = 'color:#60a5fa';
        }
    }

    $sourceCard = $task->sourceCard();
    $ediName = $this->ediCell($task, 'name');
    $canPickStatus = in_array($statusWidget, [\App\WorkItems\StatusWidget::TaskSelect, \App\WorkItems\StatusWidget::BinarySelect], true)
        && $this->rowWritable($task, 'status');
    $binaryStatus = $statusWidget === \App\WorkItems\StatusWidget::BinarySelect;
@endphp

<x-ui.card
    class="dt-card tg-dt-card{{ $isExpanded ? ' is-expanded' : '' }}"
    wire:key="tg-card-{{ $task->id }}"
    style="border-left-color: {{ $borderColor }}"
>
    <div class="dt-card__title">
        <div class="tg-dt-card__heading">
            @if($canExpand)
                <button type="button"
                        wire:click="toggleExpand({{ $task->id }})"
                        class="tg-card-expand-btn tg-dt-hit"
                        title="{{ $isExpanded ? 'Zwiń' : 'Rozwiń' }}">
                    <i class="bi bi-chevron-{{ $isExpanded ? 'down' : 'right' }}" style="font-size:0.75rem"></i>
                </button>
            @endif
            @if($canAddSubtask && $subtaskTotal > 0)
                <span class="tg-card-subtask-badge" title="{{ $subtaskDone }}/{{ $subtaskTotal }} podzadań">
                    {{ $subtaskDone }}/{{ $subtaskTotal }}
                </span>
            @endif
            @if($ediName)
                <span class="tg-dt-card__name tg-edi tg-edi--{{ $ediName['kind'] }}">
                    @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediName, 'rowId' => $task->id, 'field' => 'name'])
                </span>
            @else
                <a href="{{ $openUrl }}" class="stretched-link tg-dt-card__name" title="{{ $task->name }}">
                    {{ $task->name }}
                </a>
            @endif
            @if($isWorkItem && $task->type === \App\Enums\WorkItemType::Approval)
                <span class="tg-dt-hit"><x-ui.approval-decision :decision="$approvalDecision" size="sm" /></span>
            @endif
            @if($sourceCard && ($sourceCard['url'] ?? '') !== $openUrl)
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
                                class="tg-status-badge {{ $sc['cls'] }}"
                                style="cursor:pointer">
                            {{ $sc['icon'] }} {{ $statusLabel }}
                            <i class="bi bi-chevron-down" style="font-size:0.5rem;opacity:.6;margin-left:3px"></i>
                        </button>
                        <template x-teleport="body">
                            <ul x-show="open" x-cloak
                                @click.outside="open = false"
                                :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;min-width:155px;font-size:0.84rem`"
                                class="dropdown-menu show py-1 shadow-lg">
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
                @if($isEditing && $editingField === 'sprint')
                    <select wire:model="editingValue" class="form-select form-select-sm"
                            wire:change="saveEdit" wire:keydown.escape="cancelEdit"
                            x-data x-init="$el.focus()">
                        <option value="">Poza sprintem</option>
                        @foreach($allSprints as $sprintOption)
                            <option value="{{ $sprintOption->id }}">{{ $sprintOption->label() }}</option>
                        @endforeach
                    </select>
                @elseif($sprintUrl)
                    <a href="{{ $sprintUrl }}" class="text-decoration-none tg-dt-hit">
                        <x-ui.badge variant="accent">{{ $task->sprint->name }}</x-ui.badge>
                    </a>
                @elseif($this->rowWritable($task, 'sprint'))
                    <span wire:click.stop="startEdit({{ $task->id }}, 'sprint')" class="tg-hover-edit text-muted">—</span>
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
                @elseif($this->rowWritable($task, 'category'))
                    <span wire:click.stop="startEdit({{ $task->id }}, 'category')" class="tg-hover-edit">
                        @if($task->category)
                            <x-ui.badge variant="info">{{ $task->category }}</x-ui.badge>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </span>
                @elseif($task->category)
                    <x-ui.badge variant="info">{{ $task->category }}</x-ui.badge>
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
                @elseif($isEditing && $editingField === 'priority')
                    <select wire:model="editingValue" class="form-select form-select-sm"
                            wire:change="saveEdit" wire:keydown.escape="cancelEdit"
                            x-data x-init="$el.focus()">
                        <option value="">Brak</option>
                        <option value="1">1 – Najniższy</option>
                        <option value="2">2 – Niski</option>
                        <option value="3">3 – Średni</option>
                        <option value="4">4 – Wysoki</option>
                        <option value="5">5 – Krytyczny</option>
                    </select>
                @elseif($this->rowWritable($task, 'priority'))
                    <span wire:click.stop="startEdit({{ $task->id }}, 'priority')"
                          class="tg-hover-edit tg-mono"
                          style="font-weight:{{ $pc ? '600' : '400' }}; color:{{ $pc ? $pc['color'] : 'rgba(255,255,255,0.35)' }}">
                        {{ $pc ? $pc['label'] : '—' }}
                    </span>
                @else
                    <span class="tg-mono" style="font-weight:{{ $pc ? '600' : '400' }}; color:{{ $pc ? $pc['color'] : 'rgba(255,255,255,0.35)' }}">
                        {{ $pc ? $pc['label'] : '—' }}
                    </span>
                @endif
            </span>
        </div>
    @endif

    @if(in_array('due_date', $visibleColumns))
        @php $ediDue = $this->ediCell($task, 'due_date'); @endphp
        <div class="dt-card__row">
            <span class="dt-card__label">Termin</span>
            <span class="dt-card__value">
                @if($ediDue)
                    <span class="tg-edi tg-edi--{{ $ediDue['kind'] }}">
                        @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediDue, 'rowId' => $task->id, 'field' => 'due_date'])
                    </span>
                @elseif($isEditing && $editingField === 'due_date')
                    <input type="date" wire:model="editingValue" class="form-control form-control-sm"
                           wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                           x-data x-init="$el.focus()">
                @elseif($this->rowWritable($task, 'due_date'))
                    <span wire:click.stop="startEdit({{ $task->id }}, 'due_date')"
                          class="tg-hover-edit tg-mono"
                          style="{{ $dueStyle }}">
                        {{ $task->due_date ? $task->due_date->format('d.m.Y') : '—' }}
                    </span>
                @else
                    <span class="tg-mono" style="{{ $dueStyle }}">
                        {{ $task->due_date ? $task->due_date->format('d.m.Y') : '—' }}
                    </span>
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
            <span class="dt-card__value font-mono">{{ $task->created_at?->format('d.m.Y') ?? '—' }}</span>
        </div>
    @endif

    @if(in_array('updated_at', $visibleColumns))
        <div class="dt-card__row">
            <span class="dt-card__label">Zmieniono</span>
            <span class="dt-card__value font-mono">{{ $task->updated_at?->format('d.m.Y') ?? '—' }}</span>
        </div>
    @endif
    @endunless

    @if($isExpanded)
    <div class="tg-card-expand">
        @if($canAddSubtask || $subtaskTotal > 0)
        <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="dt-card__label" style="border:0;padding:0">
                    <i class="bi bi-list-check me-1"></i>Podzadania
                </span>
                @if($subtaskTotal > 0)
                    <span class="badge" style="font-size:0.6rem; border-radius:8px; background:rgba(255,255,255,0.1); color:var(--text-muted,#94a3b8)">
                        {{ $subtaskDone }}/{{ $subtaskTotal }}
                    </span>
                @endif
                @if($canAddSubtask)
                <button wire:click="startAddSubtask({{ $task->id }})"
                        class="btn btn-link btn-sm p-0 ms-1 tg-dt-hit"
                        style="font-size:0.7rem; text-decoration:none; color:rgba(16,185,129,0.8)">
                    <i class="bi bi-plus-circle me-1"></i>Dodaj
                </button>
                @endif
            </div>

            @if($subtaskTotal > 0)
                @foreach($subtasksAll as $subtask)
                <div class="d-flex align-items-center gap-2 py-1" wire:key="tg-card-st-{{ $subtask->id }}">
                    <x-ui.input type="checkbox"
                                :id="'tg-card-st-chk-' . $subtask->id"
                                :value="$subtask->is_completed"
                                :checked="$subtask->is_completed"
                                wire:change="toggleSubtask({{ $subtask->id }})"
                                class="flex-shrink-0 mb-0" />
                    <span class="flex-grow-1" style="font-size:0.82rem; {{ $subtask->is_completed ? 'text-decoration:line-through; color:rgba(255,255,255,0.3)' : 'color:var(--text-main,#f1f5f9)' }}">
                        {{ $subtask->name }}
                    </span>
                </div>
                @endforeach
            @elseif($addingSubtaskForTask !== $task->id)
                <div class="text-muted" style="font-size:0.8rem; font-style:italic">Brak podzadań.</div>
            @endif

            @if($addingSubtaskForTask === $task->id)
            <div class="d-flex gap-1 mt-2">
                <input type="text"
                       wire:model="newSubtaskName"
                       class="form-control form-control-sm"
                       placeholder="Nazwa podzadania…"
                       wire:keydown.enter="saveSubtask"
                       wire:keydown.escape="cancelAddSubtask">
                <button wire:click="saveSubtask" class="btn btn-sm btn-success flex-shrink-0">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <button wire:click="cancelAddSubtask" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif
</x-ui.card>
