@php
    $isExpanded  = in_array($task->id, $expandedTasks);
    $canEdit     = $this->canEditTask($task);
    $isEditing   = $editingTaskId === $task->id;
    $groupValue  = $groupBy !== '' ? $this->groupValueFor($task) : '';

    $subtasksAll  = $task->subtasks->sortBy(['sort_order', 'created_at']);
    $subtaskTotal = $subtasksAll->count();
    $subtaskDone  = $subtasksAll->where('is_completed', true)->count();
    $commentsCount = (int) ($task->comments_count ?? ($task->relationLoaded('comments') ? $task->comments->count() : 0));

    $statusMap = [
        'pending'     => ['cls' => 's-pending',    'icon' => '⏳', 'label' => 'Oczekujące', 'variant' => 'warning'],
        'in_progress' => ['cls' => 's-in_progress','icon' => '▶',  'label' => 'W trakcie',  'variant' => 'info'],
        'completed'   => ['cls' => 's-completed',  'icon' => '✓',  'label' => 'Ukończone',  'variant' => 'success'],
        'cancelled'   => ['cls' => 's-cancelled',  'icon' => '✗',  'label' => 'Anulowane',  'variant' => 'danger'],
    ];
    $sc = $statusMap[$task->status->value] ?? $statusMap['pending'];

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
        'in_progress' => '#3b82f6',
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
    x-data="{ subOver: false, taskOver: false, gv: String(@js($groupValue)) }"
    @dragover="if (window._tgSubDrag && window._tgSubDrag.fromTask !== {{ $task->id }}) { subOver = true; $event.preventDefault(); }
               else if (window._tgTaskDrag && String(window._tgTaskDrag.fromGroup) !== gv) { $event.preventDefault(); taskOver = true; $event.dataTransfer.dropEffect = 'move' }"
    @dragleave="if (!$el.contains($event.relatedTarget)) { subOver = false; taskOver = false }"
    @drop.prevent="if (window._tgSubDrag && window._tgSubDrag.fromTask !== {{ $task->id }}) { $wire.moveSubtask(window._tgSubDrag.id, {{ $task->id }}); window._tgSubDrag = null; subOver = false }
                   else if (window._tgTaskDrag && String(window._tgTaskDrag.fromGroup) !== gv) { $wire.moveTaskToGroup(window._tgTaskDrag.id, gv); window._tgTaskDrag = null; taskOver = false }"
    :class="{ 'tg-row-sub-drop': subOver, 'tg-group-drop': taskOver }">

    {{-- Expand toggle --}}
    <td style="width:36px; padding:5px 4px !important; text-align:center">
        <button wire:click="toggleExpand({{ $task->id }})"
                class="btn btn-sm btn-link p-0"
                style="color:rgba(255,255,255,0.4); line-height:1"
                title="{{ $isExpanded ? 'Zwiń' : 'Rozwiń' }}">
            <i class="bi bi-chevron-{{ $isExpanded ? 'down' : 'right' }}" style="font-size:0.75rem"></i>
        </button>
    </td>

    {{-- ── Dynamic columns rendered in $visibleColumns order ── --}}
    @foreach($visibleColumns as $colKey)
    @switch($colKey)

    {{-- ── Name ── --}}
    @case('name')
    <td style="min-width:200px; max-width:320px">
        @if($isEditing && $editingField === 'name')
            <input type="text" wire:model="editingValue"
                   class="form-control form-control-sm"
                   wire:keydown.enter="saveEdit"
                   wire:keydown.escape="cancelEdit"
                   wire:blur="saveEdit"
                   x-data x-init="$el.focus(); $el.select()">
        @else
            <div class="d-flex align-items-center gap-1" style="min-width:0">
                @if($groupBy !== '' && $canEdit)
                    <i class="bi bi-grip-vertical tg-task-grip flex-shrink-0"
                       draggable="true"
                       title="Przenieś do innej grupy"
                       @dragstart.stop="window._tgSubDrag = null; window._tgTaskDrag = { id: {{ $task->id }}, fromGroup: gv }; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', '{{ $task->id }}')"
                       @dragend="window._tgTaskDrag = null"></i>
                @endif
                @if($subtaskTotal > 0)
                    <span class="badge rounded-pill flex-shrink-0"
                          style="font-size:0.6rem; min-width:32px; background:rgba(255,255,255,0.1); color:var(--text-muted,#94a3b8)"
                          title="{{ $subtaskDone }}/{{ $subtaskTotal }} podzadań">
                        {{ $subtaskDone }}/{{ $subtaskTotal }}
                    </span>
                @endif
                @if($canEdit)
                    <span wire:click="startEdit({{ $task->id }}, 'name')"
                          class="tg-hover-edit"
                          style="cursor:text; padding:2px 4px; border-radius:3px; display:block; min-width:0; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text-main,#f1f5f9)"
                          title="{{ $task->name }}">{{ $task->name }}</span>
                @else
                    <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; flex:1; color:var(--text-main,#f1f5f9)"
                          title="{{ $task->name }}">{{ $task->name }}</span>
                @endif
                @if($sourceCard = $task->sourceCard())
                    <a href="{{ $sourceCard['url'] }}"
                       class="btn btn-link btn-sm p-0 flex-shrink-0"
                       style="color:#60a5fa; line-height:1"
                       title="{{ $sourceCard['label'] }}"
                       onclick="event.stopPropagation()">
                        <i class="bi {{ $sourceCard['icon'] }}"></i>
                    </a>
                @endif
            </div>
        @endif
    </td>
    @break

    {{-- ── Status — Alpine x-teleport (bypasses backdrop-filter stacking context) ── --}}
    @case('status')
    <td style="white-space:nowrap; min-width:130px">
        @if($canEdit)
            <div x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); top=r.bottom+4; left=r.left; open=true"
                        class="tg-status-badge {{ $sc['cls'] }}"
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
                        <li>
                            <button type="button"
                                    class="dropdown-item py-2 {{ $task->status->value === 'in_progress' ? 'active' : '' }}"
                                    wire:click="quickStatusChange({{ $task->id }}, 'in_progress')"
                                    @click="open=false">
                                ▶ W trakcie
                            </button>
                        </li>
                        <li>
                            <button type="button"
                                    class="dropdown-item py-2 {{ $task->status->value === 'completed' ? 'active' : '' }}"
                                    wire:click="quickStatusChange({{ $task->id }}, 'completed')"
                                    @click="open=false">
                                ✓ Ukończone
                            </button>
                        </li>
                        <li>
                            <button type="button"
                                    class="dropdown-item py-2 {{ $task->status->value === 'cancelled' ? 'active' : '' }}"
                                    wire:click="quickStatusChange({{ $task->id }}, 'cancelled')"
                                    @click="open=false">
                                ✗ Anulowane
                            </button>
                        </li>
                    </ul>
                </template>
            </div>
        @else
            <x-ui.badge :variant="$sc['variant']">{{ $sc['icon'] }} {{ $sc['label'] }}</x-ui.badge>
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
        @else
            @if($canEdit)
                <span wire:click="startEdit({{ $task->id }}, 'sprint')" class="tg-hover-edit d-block" style="cursor:pointer; padding:2px 4px; border-radius:3px">
                    @if($task->sprint)
                        <x-ui.badge variant="accent" class="text-truncate" style="max-width:160px">{{ $task->sprint->name }}</x-ui.badge>
                    @else
                        <span class="text-muted" style="font-size:0.82rem">—</span>
                    @endif
                </span>
            @else
                @if($task->sprint)
                    <x-ui.badge variant="accent" class="text-truncate" style="max-width:160px">{{ Str::limit($task->sprint->name, 22) }}</x-ui.badge>
                @else
                    <span class="text-muted" style="font-size:0.82rem">—</span>
                @endif
            @endif
        @endif
    </td>
    @break

    {{-- ── Category ── --}}
    @case('category')
    <td style="max-width:140px">
        @if($isEditing && $editingField === 'category')
            <input type="text" wire:model="editingValue" class="form-control form-control-sm"
                   wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                   x-data x-init="$el.focus(); $el.select()">
        @else
            @if($canEdit)
                <span wire:click="startEdit({{ $task->id }}, 'category')" class="tg-hover-edit d-block" style="cursor:text; padding:2px 4px; border-radius:3px">
                    @if($task->category)
                        <x-ui.badge variant="info" class="text-truncate" style="max-width:120px">{{ $task->category }}</x-ui.badge>
                    @else
                        <span class="text-muted" style="font-size:0.82rem">—</span>
                    @endif
                </span>
            @else
                @if($task->category)
                    <x-ui.badge variant="info" class="text-truncate" style="max-width:120px">{{ Str::limit($task->category, 16) }}</x-ui.badge>
                @else
                    <span class="text-muted" style="font-size:0.82rem">—</span>
                @endif
            @endif
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
            @if($canEdit)
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

    {{-- ── Priority ── --}}
    @case('priority')
    <td style="white-space:nowrap; min-width:90px">
        @if($isEditing && $editingField === 'priority')
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
            @if($canEdit)
                <span wire:click="startEdit({{ $task->id }}, 'priority')"
                      class="tg-hover-edit"
                      style="cursor:pointer; padding:2px 4px; border-radius:3px; display:block; font-size:0.82rem; font-weight:{{ $pc ? '600' : '400' }}; color:{{ $pc ? $pc['color'] : 'rgba(255,255,255,0.2)' }}">
                    {{ $pc ? $pc['label'] : '—' }}
                </span>
            @else
                <span style="font-size:0.82rem; font-weight:{{ $pc ? '600' : '400' }}; color:{{ $pc ? $pc['color'] : 'rgba(255,255,255,0.2)' }}">
                    {{ $pc ? $pc['label'] : '—' }}
                </span>
            @endif
        @endif
    </td>
    @break

    {{-- ── Due date ── --}}
    @case('due_date')
    <td style="white-space:nowrap; min-width:95px">
        @if($isEditing && $editingField === 'due_date')
            <input type="date" wire:model="editingValue" class="form-control form-control-sm"
                   wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
                   x-data x-init="$el.focus()">
        @else
            @if($canEdit)
                <span wire:click="startEdit({{ $task->id }}, 'due_date')"
                      class="tg-hover-edit"
                      style="cursor:pointer; padding:2px 4px; border-radius:3px; display:block; font-size:0.82rem; {{ $dueStyle }}">
                    {{ $task->due_date ? $task->due_date->format('d.m.Y') : '—' }}
                </span>
            @else
                <span style="font-size:0.82rem; {{ $dueStyle }}">
                    {{ $task->due_date ? $task->due_date->format('d.m.Y') : '—' }}
                </span>
            @endif
        @endif
    </td>
    @break

    {{-- ── Subtasks progress ── --}}
    @case('subtasks')
    <td style="min-width:80px">
        @if($subtaskTotal > 0)
            <div class="d-flex align-items-center gap-1">
                <div class="progress flex-shrink-0" style="width:46px; height:4px; border-radius:2px; background:rgba(255,255,255,0.1)">
                    <div style="width:{{ $subtaskTotal > 0 ? round(($subtaskDone/$subtaskTotal)*100) : 0 }}%; height:100%; border-radius:2px; background:{{ $subtaskDone === $subtaskTotal ? '#10b981' : '#3b82f6' }}"></div>
                </div>
                <span style="font-size:0.72rem; color:var(--text-muted,#94a3b8)">{{ $subtaskDone }}/{{ $subtaskTotal }}</span>
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
            <a href="{{ route('tasks.show', $task) }}"
               class="text-decoration-none"
               style="font-size:0.75rem; color:var(--text-muted,#94a3b8)">
                <i class="bi bi-chat-dots me-1"></i>{{ $commentsCount }}
            </a>
        @else
            <span style="color:rgba(255,255,255,0.2); font-size:0.82rem">—</span>
        @endif
    </td>
    @break

    {{-- ── Created at ── --}}
    @case('created_at')
    <td style="white-space:nowrap; font-size:0.76rem; color:rgba(255,255,255,0.35)">
        {{ $task->created_at->format('d.m.Y') }}
    </td>
    @break

    {{-- ── Updated at ── --}}
    @case('updated_at')
    <td style="white-space:nowrap; font-size:0.76rem; color:rgba(255,255,255,0.35)">
        {{ $task->updated_at->format('d.m.Y') }}
    </td>
    @break

    @endswitch
    @endforeach

    {{-- ── Actions ── --}}
    <td style="white-space:nowrap; text-align:right; padding-right:8px !important">
        <x-ui.action-buttons gap="1" class="justify-content-end">
            <a href="{{ route('tasks.show', $task) }}"
               class="btn btn-sm btn-outline-secondary p-1"
               style="line-height:1; min-width:28px"
               title="Podgląd">
                <i class="bi bi-eye"></i>
            </a>
            <a href="{{ route('tasks.edit', $task) }}"
               class="btn btn-sm btn-outline-secondary p-1"
               style="line-height:1; min-width:28px"
               title="Edytuj">
                <i class="bi bi-pencil"></i>
            </a>
            <button wire:click="startAddSubtask({{ $task->id }})"
                    class="btn btn-sm btn-outline-success p-1"
                    style="line-height:1; min-width:28px"
                    title="Dodaj podzadanie">
                <i class="bi bi-plus-square"></i>
            </button>
        </x-ui.action-buttons>
    </td>
</tr>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- EXPANDED DETAIL ROW                                         --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($isExpanded)
<tr wire:key="tg-expanded-{{ $task->id }}" class="tg-expand-row">
    <td style="width:36px; border-left:3px solid {{ $borderColor }}; padding:0 !important; background:rgba(10,15,29,0.6) !important"></td>
    <td colspan="{{ count($visibleColumns) + 1 }}">
        <div class="row g-4">

            {{-- ── Description ── --}}
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted,#94a3b8)">
                        <i class="bi bi-card-text me-1"></i>Opis
                    </span>
                    @if($canEdit && !($isEditing && $editingField === 'description'))
                    <button wire:click="startEdit({{ $task->id }}, 'description')"
                            class="btn btn-link btn-sm p-0"
                            style="font-size:0.72rem; color:rgba(255,255,255,0.3); text-decoration:none; line-height:1"
                            title="Edytuj opis">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    @endif
                </div>

                @if($isEditing && $editingField === 'description')
                    <textarea wire:model="editingValue"
                              class="form-control form-control-sm"
                              rows="5"
                              placeholder="Opis zadania…"
                              wire:keydown.escape="cancelEdit"
                              x-data x-init="$el.focus()"></textarea>
                    <div class="d-flex gap-1 mt-2">
                        <button wire:click="saveEdit" class="btn btn-sm btn-primary">
                            <i class="bi bi-floppy me-1"></i>Zapisz
                        </button>
                        <button wire:click="cancelEdit" class="btn btn-sm btn-outline-secondary">Anuluj</button>
                    </div>
                @else
                    @php $descText = $task->plainDescription(); @endphp
                    @if($descText)
                        <div style="white-space:pre-wrap; max-height:160px; overflow-y:auto; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.08); border-radius:6px; padding:10px 12px; font-size:0.82rem; line-height:1.55; color:var(--text-main,#f1f5f9)">{{ $descText }}</div>
                    @else
                        <div style="font-size:0.82rem; font-style:italic; color:rgba(255,255,255,0.25)">
                            Brak opisu.
                            @if($canEdit)
                                <button wire:click="startEdit({{ $task->id }}, 'description')"
                                        class="btn btn-link btn-sm p-0 ms-1"
                                        style="font-size:0.8rem">Dodaj opis</button>
                            @endif
                        </div>
                    @endif
                    @if($sourceCard = $task->sourceCard())
                        <div class="mt-2">
                            <a href="{{ $sourceCard['url'] }}"
                               class="btn btn-sm btn-outline-primary"
                               style="font-size:0.75rem">
                                <i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}
                            </a>
                        </div>
                    @endif
                @endif
            </div>

            {{-- ── Subtasks ── --}}
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted,#94a3b8)">
                        <i class="bi bi-list-check me-1"></i>Podzadania
                    </span>
                    @if($subtaskTotal > 0)
                        <span class="badge" style="font-size:0.62rem; border-radius:8px; background:rgba(255,255,255,0.1); color:var(--text-muted,#94a3b8)">
                            {{ $subtaskDone }}/{{ $subtaskTotal }}
                        </span>
                        <div class="progress flex-grow-1" style="height:4px; max-width:70px; border-radius:2px; background:rgba(255,255,255,0.08)">
                            <div style="width:{{ round(($subtaskDone/$subtaskTotal)*100) }}%; height:100%; border-radius:2px; background:{{ $subtaskDone === $subtaskTotal ? '#10b981' : '#3b82f6' }}"></div>
                        </div>
                    @endif
                    <button wire:click="startAddSubtask({{ $task->id }})"
                            class="btn btn-link btn-sm p-0 ms-1"
                            style="font-size:0.72rem; text-decoration:none; color:rgba(16,185,129,0.8)">
                        <i class="bi bi-plus-circle me-1"></i>Dodaj podzadanie
                    </button>
                </div>

                @if($subtaskTotal > 0)
                <div style="max-height:220px; overflow-y:auto"
                     @dragover.prevent
                     @drop.prevent="if (window._tgSubDrag && window._tgSubDrag.fromTask !== {{ $task->id }}) { $wire.moveSubtask(window._tgSubDrag.id, {{ $task->id }}); window._tgSubDrag = null }">
                    @foreach($subtasksAll as $subtask)
                    <div class="d-flex align-items-center gap-2 py-1 px-1 tg-subtask-item"
                         style="border-bottom:1px solid rgba(255,255,255,0.05); border-radius:4px"
                         draggable="true"
                         wire:key="tg-st-{{ $subtask->id }}"
                         @dragstart="window._tgTaskDrag = null; window._tgSubDrag = { id: {{ $subtask->id }}, fromTask: {{ $task->id }} }; $event.dataTransfer.effectAllowed = 'move'"
                         @dragend="window._tgSubDrag = null"
                         @dragover.prevent.stop
                         @drop.prevent.stop="if (window._tgSubDrag && window._tgSubDrag.id !== {{ $subtask->id }}) { $wire.moveSubtask(window._tgSubDrag.id, {{ $task->id }}, {{ $subtask->id }}); window._tgSubDrag = null }">
                        <i class="bi bi-grip-vertical tg-subtask-grip flex-shrink-0" style="font-size:0.78rem; cursor:grab; color:rgba(255,255,255,0.2)"></i>
                        <x-ui.input type="checkbox"
                                    :id="'tg-st-chk-' . $subtask->id"
                                    :value="$subtask->is_completed"
                                    :checked="$subtask->is_completed"
                                    wire:change="toggleSubtask({{ $subtask->id }})"
                                    class="flex-shrink-0 mb-0" />
                        <span class="flex-grow-1" style="font-size:0.83rem; {{ $subtask->is_completed ? 'text-decoration:line-through; color:rgba(255,255,255,0.3)' : 'color:var(--text-main,#f1f5f9)' }}">
                            {{ $subtask->name }}
                        </span>
                        @if($subtask->is_completed && $subtask->completed_at)
                            <span style="font-size:0.68rem; color:rgba(255,255,255,0.25); flex-shrink:0; white-space:nowrap">
                                {{ $subtask->completed_at->format('d.m H:i') }}
                            </span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @elseif($addingSubtaskForTask !== $task->id)
                    <div style="font-size:0.82rem; font-style:italic; color:rgba(255,255,255,0.25)">Brak podzadań.</div>
                @endif

                @if($addingSubtaskForTask === $task->id)
                <div class="d-flex gap-1 mt-2">
                    <input type="text"
                           wire:model="newSubtaskName"
                           class="form-control form-control-sm"
                           placeholder="Nazwa podzadania…"
                           wire:keydown.enter="saveSubtask"
                           wire:keydown.escape="cancelAddSubtask"
                           x-data x-init="$el.focus()">
                    <button wire:click="saveSubtask" class="btn btn-sm btn-success flex-shrink-0">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                    <button wire:click="cancelAddSubtask" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div style="font-size:0.7rem; margin-top:4px; color:rgba(255,255,255,0.3)">
                    <kbd>Enter</kbd> aby dodać &nbsp;·&nbsp; <kbd>Esc</kbd> aby anulować
                </div>
                @endif
            </div>

        </div>
    </td>
</tr>
@endif
