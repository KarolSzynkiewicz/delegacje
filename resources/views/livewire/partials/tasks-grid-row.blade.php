@php
    $isWorkItem   = $task instanceof \App\Models\WorkItem;
    $openUrl      = $this->itemOpenUrl($task);
    $sprintUrl    = $task->sprint ? route('sprints.show', $task->sprint) : null;
    $canAddSubtask = $this->rowSupports($task, 'subtasks');
    $canExpand   = $this->rowExpandable($task);
    $isExpanded  = $canExpand && in_array($task->id, $expandedTasks);
    $canDrag     = $this->rowCanDrag($task);
    $isEditing   = $editingTaskId === $task->id;
    $groupValue  = $groupBy !== '' ? $this->groupValueFor($task) : '';
    $statusWidget = $this->rowStatusWidget($task);
    $statusLabel = $this->rowStatusLabel($task);

    [$subtasksAll, $subtaskTotal, $subtaskDone] = $this->rowSubtaskStats($task, $isExpanded);
    $commentsCount = (int) ($task->comments_count ?? ($task->relationLoaded('comments') ? $task->comments->count() : 0));

    $statusMap = [
        'pending'     => ['cls' => 's-pending',    'icon' => '⏳', 'label' => 'Oczekujące', 'variant' => 'warning'],
        'in_progress' => ['cls' => 's-in_progress','icon' => '▶',  'label' => 'W trakcie',  'variant' => 'info'],
        'completed'   => ['cls' => 's-completed',  'icon' => '✓',  'label' => 'Ukończone',  'variant' => 'success'],
        'cancelled'   => ['cls' => 's-cancelled',  'icon' => '✗',  'label' => 'Anulowane',  'variant' => 'danger'],
    ];
    $sc = $statusMap[$task->status->value] ?? $statusMap['pending'];
    $sc['label'] = $statusLabel;
    $approvalDecision = $isWorkItem ? $task->approvalDecision() : null;
    if ($isWorkItem && $task->type === \App\Enums\WorkItemType::Approval) {
        if ($approvalDecision === \App\Enums\ApprovalDecision::Approved) {
            $sc = ['cls' => 's-completed', 'icon' => '✓', 'label' => $statusLabel];
        } elseif ($approvalDecision === \App\Enums\ApprovalDecision::Rejected) {
            $sc = ['cls' => 's-cancelled', 'icon' => '✗', 'label' => $statusLabel];
        } else {
            $sc = ['cls' => 's-pending', 'icon' => '⏳', 'label' => $statusLabel];
        }
    }

    // Priority config
    $priorityMap = [
        1 => ['color' => '#94a3b8', 'label' => '↓ Najniższy'],
        2 => ['color' => '#60a5fa', 'label' => '↓ Niski'],
        3 => ['color' => '#fb923c', 'label' => '→ Średni'],
        4 => ['color' => '#f87171', 'label' => '↑ Wysoki'],
        5 => ['color' => '#c084fc', 'label' => '↑ Krytyczny'],
    ];
    $pc = $priorityMap[$task->priority] ?? null;

    // Status → left border color
    $borderColor = [
        'pending'     => '#f59e0b',
        'in_progress' => '#a855f7',
        'completed'   => '#10b981',
        'cancelled'   => '#ef4444',
    ][$task->status->value] ?? 'rgba(255,255,255,0.1)';

    // Due date color
    if ($task->due_date) {
        if ($task->due_date->isPast() || $task->due_date->isToday()) {
            $dueStyle = 'color:#f87171;font-weight:600';
        } elseif ($task->due_date->diffInDays(now()) <= 3) {
            $dueStyle = 'color:#fb923c;font-weight:600';
        } else {
            $dueStyle = 'color:#60a5fa';
        }
    } else {
        $dueStyle = 'color:rgba(255,255,255,0.25)';
    }
@endphp

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- MAIN TASK ROW                                               --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<tr wire:key="tg-row-{{ $task->id }}"
    class="tg-task-row {{ $isExpanded ? 'tg-expanded' : '' }}"
    style="border-left:3px solid {{ $borderColor }}"
    data-tg-drop-task="{{ $task->id }}"
    data-tg-drop-group="{{ $groupValue }}"
    data-tg-accepts-sub="{{ $canAddSubtask ? '1' : '0' }}">

    {{-- Expand toggle --}}
    <td style="width:36px; padding:5px 4px !important; text-align:center">
        @if($canExpand)
        <button type="button"
                wire:click="toggleExpand({{ $task->id }})"
                class="btn btn-sm btn-link p-0 tg-expand-btn{{ $isExpanded ? ' is-open' : '' }}"
                data-tg-expand="{{ $task->id }}"
                aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                style="color:rgba(255,255,255,0.4); line-height:1"
                title="{{ $isExpanded ? 'Zwiń' : 'Rozwiń' }}">
            <i class="bi bi-chevron-right" style="font-size:0.75rem"></i>
        </button>
        @endif
    </td>

    {{-- ── Dynamic columns rendered in $visibleColumns order ── --}}
    @foreach($visibleColumns as $colKey)
    @switch($colKey)

    {{-- ── Name ── --}}
    @case('name')
    @php $ediName = $this->ediCell($task, 'name'); @endphp
    <td class="{{ $ediName ? 'tg-edi tg-edi--'.$ediName['kind'] : '' }}" style="min-width:200px; max-width:320px">
        <div class="d-flex align-items-center gap-1" style="min-width:0">
                @if($canDrag)
                    <i class="bi bi-grip-vertical tg-task-grip flex-shrink-0"
                       title="Przenieś do innej grupy"></i>
                @endif
                @if($canAddSubtask && $subtaskTotal > 0)
                    <span class="badge rounded-pill tg-mono flex-shrink-0"
                          data-tg-sub-stats="{{ $task->id }}"
                          style="font-size:0.6rem; min-width:32px; background:rgba(255,255,255,0.1); color:var(--text-muted,#94a3b8)"
                          title="{{ $subtaskDone }}/{{ $subtaskTotal }} podzadań">
                        {{ $subtaskDone }}/{{ $subtaskTotal }}
                    </span>
                @endif
                @if($ediName)
                    @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediName, 'rowId' => $task->id, 'field' => 'name'])
                @elseif($isEditing && $editingField === 'name')
                    <input type="text" wire:model="editingValue" class="form-control form-control-sm"
                           wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                           x-data x-init="$el.focus(); $el.select()"
                           style="min-width:0; flex:1">
                @else
                    <div class="tg-facet" style="flex:1; min-width:0">
                        <a href="{{ $openUrl }}"
                           class="tg-facet__value text-decoration-none"
                           style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text-main,#f1f5f9); padding:2px 4px"
                           title="{{ $task->name }}">
                            {{ $task->name }}
                        </a>
                        @if($this->rowWritable($task, 'name'))
                            <button type="button"
                                    class="tg-facet__edit"
                                    wire:click.stop="startEdit({{ $task->id }}, 'name')"
                                    title="Edytuj tytuł"
                                    aria-label="Edytuj tytuł">
                                <i class="bi bi-pencil"></i>
                            </button>
                        @endif
                    </div>
                @endif
                @if($isWorkItem && $task->type === \App\Enums\WorkItemType::Approval)
                    <x-ui.approval-decision :decision="$approvalDecision" size="sm" />
                @endif
                @if(($sourceCard = $task->sourceCard()) && ($sourceCard['url'] ?? '') !== $openUrl)
                    <a href="{{ $sourceCard['url'] }}"
                       class="btn btn-link btn-sm p-0 flex-shrink-0"
                       style="color:#60a5fa; line-height:1"
                       title="{{ $sourceCard['label'] }}"
                       onclick="event.stopPropagation()">
                        <i class="bi {{ $sourceCard['icon'] }}"></i>
                    </a>
                @endif
            </div>
    </td>
    @break

    {{-- ── Type (not editable) ── --}}
    @case('type')
    <td style="white-space:nowrap; min-width:110px">
        <span class="d-inline-flex align-items-center gap-1" style="font-size:0.82rem;color:var(--text-muted,#94a3b8)">
            <i class="bi {{ $this->rowTypeIcon($task) }}"></i>
            {{ $this->rowTypeLabel($task) }}
        </span>
    </td>
    @break

    {{-- ── Status ── --}}
    @case('status')
    <td style="white-space:nowrap; min-width:130px">
        @if(in_array($statusWidget, [\App\WorkItems\StatusWidget::TaskSelect, \App\WorkItems\StatusWidget::BinarySelect], true) && $this->rowWritable($task, 'status'))
            @php $binaryStatus = $statusWidget === \App\WorkItems\StatusWidget::BinarySelect; @endphp
            <div x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); top=r.bottom+4; left=r.left; open=true"
                        class="tg-status-badge tg-mono {{ $sc['cls'] }}"
                        style="cursor:pointer">
                    {{ $sc['icon'] }} {{ $sc['label'] }}
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
            <span class="tg-status-badge tg-mono {{ $sc['cls'] }}">{{ $sc['icon'] }} {{ $statusLabel }}</span>
        @endif
    </td>
    @break

    {{-- ── Sprint ── --}}
    @case('sprint')
    <td style="max-width:180px">
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
            <a href="{{ $sprintUrl }}" class="text-decoration-none d-block" style="padding:2px 4px">
                <x-ui.badge variant="accent" class="text-truncate" style="max-width:160px">{{ $task->sprint->name }}</x-ui.badge>
            </a>
        @elseif($this->rowWritable($task, 'sprint'))
            <span wire:click="startEdit({{ $task->id }}, 'sprint')" class="tg-hover-edit d-block text-muted" style="cursor:pointer; padding:2px 4px; border-radius:3px; font-size:0.82rem">—</span>
        @else
            <span class="text-muted" style="font-size:0.82rem">—</span>
        @endif
    </td>
    @break

    {{-- ── Category ── --}}
    @case('category')
    @php $ediCategory = $this->ediCell($task, 'category'); @endphp
    <td class="{{ $ediCategory ? 'tg-edi tg-edi--'.$ediCategory['kind'] : '' }}" style="max-width:140px">
        @if($ediCategory)
            @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediCategory, 'rowId' => $task->id, 'field' => 'category'])
        @elseif($isEditing && $editingField === 'category')
            <input type="text" wire:model="editingValue" class="form-control form-control-sm"
                   wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                   x-data x-init="$el.focus(); $el.select()">
        @else
            <div class="tg-facet">
                @if($task->category)
                    <button type="button"
                            class="tg-facet__value"
                            wire:click="filterByCategory({{ \Illuminate\Support\Js::from($task->category) }})"
                            title="Zawęź listę do tej kategorii">
                        <x-ui.badge variant="info" class="text-truncate" style="max-width:120px">{{ $task->category }}</x-ui.badge>
                    </button>
                @else
                    <span class="text-muted" style="font-size:0.82rem">—</span>
                @endif
                @if($this->rowWritable($task, 'category'))
                    <button type="button"
                            class="tg-facet__edit"
                            wire:click.stop="startEdit({{ $task->id }}, 'category')"
                            title="Edytuj kategorię"
                            aria-label="Edytuj kategorię">
                        <i class="bi bi-pencil"></i>
                    </button>
                @endif
            </div>
        @endif
    </td>
    @break

    {{-- ── Assigned to ── --}}
    @case('assigned_to')
    <td style="min-width:130px">
        @if($isEditing && $editingField === 'assigned_to')
            <select wire:model="editingValue" class="form-select form-select-sm"
                    wire:change="saveEdit" wire:keydown.escape="cancelEdit"
                    x-data x-init="$el.focus()">
                <option value="">Nieprzypisane</option>
                @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        @else
            @if($this->rowWritable($task, 'assigned_to'))
                <span wire:click="startEdit({{ $task->id }}, 'assigned_to')"
                      class="tg-hover-edit d-block" style="cursor:pointer; padding:2px 4px; border-radius:3px">
                    @if($task->assignedTo)
                        <x-ui.person :user="$task->assignedTo" avatar-size="22px" :show-email="false" name-class="small" />
                    @else
                        <span class="text-muted" style="font-size:0.82rem">—</span>
                    @endif
                </span>
            @else
                @if($task->assignedTo)
                    <x-ui.person :user="$task->assignedTo" avatar-size="22px" :show-email="false" name-class="small" />
                @else
                    <span class="text-muted" style="font-size:0.82rem">—</span>
                @endif
            @endif
        @endif
    </td>
    @break

    {{-- ── Created by ── --}}
    @case('created_by')
    <td style="min-width:130px">
        @if($task->createdBy)
            <x-ui.person :user="$task->createdBy" avatar-size="22px" :show-email="false" name-class="small" />
        @else
            <span class="text-muted" style="font-size:0.82rem">—</span>
        @endif
    </td>
    @break

    {{-- ── Priority ── --}}
    @case('priority')
    @php $ediPriority = $this->ediCell($task, 'priority'); @endphp
    <td class="{{ $ediPriority ? 'tg-edi tg-edi--'.$ediPriority['kind'] : '' }}" style="white-space:nowrap; min-width:90px">
        @if($ediPriority)
            @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediPriority, 'rowId' => $task->id, 'field' => 'priority'])
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
        @else
            <div class="tg-facet">
                @if($pc)
                    <button type="button"
                            class="tg-facet__value tg-mono"
                            wire:click="filterByPriority('{{ $task->priority }}')"
                            title="Zawęź listę do tego priorytetu"
                            style="font-weight:600; color:{{ $pc['color'] }}; font-size:0.78rem">
                        {{ $pc['label'] }}
                    </button>
                @else
                    <span class="tg-mono" style="font-size:0.78rem; color:rgba(255,255,255,0.2)">—</span>
                @endif
                @if($this->rowWritable($task, 'priority'))
                    <button type="button"
                            class="tg-facet__edit"
                            wire:click.stop="startEdit({{ $task->id }}, 'priority')"
                            title="Edytuj priorytet"
                            aria-label="Edytuj priorytet">
                        <i class="bi bi-pencil"></i>
                    </button>
                @endif
            </div>
        @endif
    </td>
    @break

    {{-- ── Due date ── --}}
    @case('due_date')
    @php $ediDue = $this->ediCell($task, 'due_date'); @endphp
    <td class="{{ $ediDue ? 'tg-edi tg-edi--'.$ediDue['kind'] : '' }}" style="white-space:nowrap; min-width:95px">
        @if($ediDue)
            @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediDue, 'rowId' => $task->id, 'field' => 'due_date'])
        @elseif($isEditing && $editingField === 'due_date')
            <input type="date" wire:model="editingValue" class="form-control form-control-sm"
                   wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                   x-data x-init="$el.focus()">
        @else
            <div class="tg-facet">
                @if($task->due_date)
                    <button type="button"
                            class="tg-facet__value tg-mono"
                            wire:click="filterByDueDate('{{ $task->due_date->format('Y-m-d') }}')"
                            title="Zawęź listę do tego dnia"
                            style="font-size:0.78rem; {{ $dueStyle }}">
                        {{ $task->due_date->format('d.m.Y') }}
                    </button>
                @else
                    <span class="tg-mono" style="font-size:0.78rem; {{ $dueStyle }}">—</span>
                @endif
                @if($this->rowWritable($task, 'due_date'))
                    <button type="button"
                            class="tg-facet__edit"
                            wire:click.stop="startEdit({{ $task->id }}, 'due_date')"
                            title="Edytuj termin"
                            aria-label="Edytuj termin">
                        <i class="bi bi-pencil"></i>
                    </button>
                @endif
            </div>
        @endif
    </td>
    @break

    {{-- ── Time blocks ── --}}
    @case('blocks')
    <td style="min-width:110px">
        @php $pills = $isWorkItem ? $task->schedulePills() : []; @endphp
        @if($pills !== [])
            <div class="d-flex flex-column gap-1">
                @foreach($pills as $pill)
                    <span class="font-mono" style="font-size:.72rem;color:var(--text-muted)">{{ $pill }}</span>
                @endforeach
            </div>
        @else
            <span class="tg-mono" style="font-size:.78rem;color:rgba(255,255,255,.25)">brak</span>
        @endif
    </td>
    @break

    {{-- ── Subtasks progress ── --}}
    @case('subtasks')
    <td style="min-width:80px">
        @if($subtaskTotal > 0)
            <div class="d-flex align-items-center gap-1">
                <div class="progress flex-shrink-0" style="width:46px; height:4px; border-radius:2px; background:rgba(255,255,255,0.1)">
                    <div data-tg-sub-bar="{{ $task->id }}" style="width:{{ $subtaskTotal > 0 ? round(($subtaskDone/$subtaskTotal)*100) : 0 }}%; height:100%; border-radius:2px; background:{{ $subtaskDone === $subtaskTotal ? '#10b981' : '#a855f7' }}"></div>
                </div>
                <span class="tg-mono" data-tg-sub-stats="{{ $task->id }}" style="font-size:0.7rem; color:var(--text-muted,#94a3b8)">{{ $subtaskDone }}/{{ $subtaskTotal }}</span>
            </div>
        @else
            <span style="color:rgba(255,255,255,0.2); font-size:0.82rem">—</span>
        @endif
    </td>
    @break

    {{-- ── Comments ── --}}
    @case('comments')
    <td style="text-align:center; min-width:60px">
        @if($commentsCount > 0)
            <a href="{{ $openUrl }}"
               class="text-decoration-none tg-mono"
               style="font-size:0.72rem; color:var(--text-muted,#94a3b8)">
                <i class="bi bi-chat-dots me-1"></i>{{ $commentsCount }}
            </a>
        @else
            <span style="color:rgba(255,255,255,0.2); font-size:0.82rem">—</span>
        @endif
    </td>
    @break

    {{-- ── Created at ── --}}
    @case('created_at')
    <td class="tg-mono" style="white-space:nowrap; font-size:0.72rem; color:rgba(255,255,255,0.35)">
        {{ $task->created_at->format('d.m.Y') }}
    </td>
    @break

    {{-- ── Updated at ── --}}
    @case('updated_at')
    <td class="tg-mono" style="white-space:nowrap; font-size:0.72rem; color:rgba(255,255,255,0.35)">
        {{ $task->updated_at->format('d.m.Y') }}
    </td>
    @break

    @endswitch
    @endforeach
</tr>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- EXPANDED DETAIL ROW                                         --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($isExpanded)
    @include('livewire.partials.tasks-grid-expand-row')
@endif
