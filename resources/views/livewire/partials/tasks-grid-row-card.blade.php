@php
    $isWorkItem   = $task instanceof \App\Models\WorkItem;
    $openUrl      = $isWorkItem ? $task->openUrl() : route('tasks.show', $task);
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
@endphp

<div class="tg-card" wire:key="tg-card-{{ $task->id }}" style="border-left-color:{{ $borderColor }}">
    {{-- ── Top: expand + name ── --}}
    <div class="tg-card-top">
        @if($canExpand)
            <button type="button"
                    wire:click="toggleExpand({{ $task->id }})"
                    class="tg-card-expand-btn"
                    title="{{ $isExpanded ? 'Zwiń' : 'Rozwiń' }}">
                <i class="bi bi-chevron-{{ $isExpanded ? 'down' : 'right' }}" style="font-size:0.75rem"></i>
            </button>
        @endif
        @if($canAddSubtask && $subtaskTotal > 0)
            <span class="tg-card-subtask-badge" title="{{ $subtaskDone }}/{{ $subtaskTotal }} podzadań">
                {{ $subtaskDone }}/{{ $subtaskTotal }}
            </span>
        @endif
        <a href="{{ $openUrl }}" class="tg-card-name" title="{{ $task->name }}">{{ $task->name }}</a>
        @if($sourceCard && ($sourceCard['url'] ?? '') !== $openUrl)
            <a href="{{ $sourceCard['url'] }}"
               class="tg-card-source-link"
               title="{{ $sourceCard['label'] }}"
               onclick="event.stopPropagation()">
                <i class="bi {{ $sourceCard['icon'] }}"></i>
            </a>
        @endif
    </div>

    {{-- ── Meta row: status + type + sprint + category + assigned + priority + due + comments ── --}}
    <div class="tg-card-meta">
        {{-- Status --}}
        @if(in_array($statusWidget, [\App\WorkItems\StatusWidget::TaskSelect, \App\WorkItems\StatusWidget::BinarySelect], true) && $this->rowWritable($task, 'status'))
            @php $binaryStatus = $statusWidget === \App\WorkItems\StatusWidget::BinarySelect; @endphp
            <div x-data="{ open: false, top: 0, left: 0 }" class="tg-meta-item">
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
            <span class="tg-status-badge {{ $sc['cls'] }} tg-meta-item">{{ $sc['icon'] }} {{ $statusLabel }}</span>
        @endif

        {{-- Type --}}
        @if(in_array('type', $visibleColumns))
            <span class="tg-meta-item"><i class="bi {{ $this->rowTypeIcon($task) }}"></i>{{ $this->rowTypeLabel($task) }}</span>
        @endif

        {{-- Sprint --}}
        @if(in_array('sprint', $visibleColumns) && $sprintUrl)
            <a href="{{ $sprintUrl }}" class="tg-meta-item text-decoration-none">
                <x-ui.badge variant="accent" class="text-truncate" style="max-width:120px">{{ $task->sprint->name }}</x-ui.badge>
            </a>
        @endif

        {{-- Category --}}
        @if(in_array('category', $visibleColumns) && $task->category)
            <span class="tg-meta-item">
                <x-ui.badge variant="info" class="text-truncate" style="max-width:110px">{{ $task->category }}</x-ui.badge>
            </span>
        @endif

        {{-- Assigned to --}}
        @if(in_array('assigned_to', $visibleColumns) && $task->assignedTo)
            <span class="tg-meta-item"><x-ui.person :user="$task->assignedTo" avatar-size="18px" :show-email="false" name-class="small" /></span>
        @endif

        {{-- Priority --}}
        @if(in_array('priority', $visibleColumns) && $pc)
            <span class="tg-meta-item" style="color:{{ $pc['color'] }};font-weight:600">{{ $pc['label'] }}</span>
        @endif

        {{-- Due date --}}
        @if(in_array('due_date', $visibleColumns) && $task->due_date)
            <span class="tg-meta-item" style="{{ $dueStyle }}"><i class="bi bi-calendar-event"></i>{{ $task->due_date->format('d.m.Y') }}</span>
        @endif

        {{-- Comments --}}
        @if(in_array('comments', $visibleColumns) && $commentsCount > 0)
            <a href="{{ $openUrl }}" class="tg-meta-item text-decoration-none">
                <i class="bi bi-chat-dots"></i>{{ $commentsCount }}
            </a>
        @endif
    </div>

    {{-- ── Expanded: description + subtasks ── --}}
    @if($isExpanded)
    <div class="tg-card-expand">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span style="font-size:0.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted,#94a3b8)">
                <i class="bi bi-card-text me-1"></i>Opis
            </span>
            @if($this->rowWritable($task, 'description') && !($isEditing && $editingField === 'description'))
            <button wire:click="startEdit({{ $task->id }}, 'description')"
                    class="btn btn-link btn-sm p-0"
                    style="font-size:0.72rem; color:rgba(255,255,255,0.3); text-decoration:none; line-height:1">
                <i class="bi bi-pencil-square"></i>
            </button>
            @endif
        </div>

        @if($isEditing && $editingField === 'description')
            <textarea wire:model="editingValue"
                      class="form-control form-control-sm"
                      rows="4"
                      placeholder="Opis zadania…"
                      wire:keydown.escape="cancelEdit"></textarea>
            <div class="d-flex gap-1 mt-2">
                <button wire:click="saveEdit" class="btn btn-sm btn-primary">
                    <i class="bi bi-floppy me-1"></i>Zapisz
                </button>
                <button wire:click="cancelEdit" class="btn btn-sm btn-outline-secondary">Anuluj</button>
            </div>
        @else
            @php $descText = $task->plainDescription(); @endphp
            @if($descText)
                <div style="white-space:pre-wrap; max-height:160px; overflow-y:auto; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.08); border-radius:6px; padding:8px 10px; font-size:0.8rem; line-height:1.5; color:var(--text-main,#f1f5f9)">{{ $descText }}</div>
            @else
                <div style="font-size:0.8rem; font-style:italic; color:rgba(255,255,255,0.25)">
                    Brak opisu.
                    @if($this->rowWritable($task, 'description'))
                        <button wire:click="startEdit({{ $task->id }}, 'description')"
                                class="btn btn-link btn-sm p-0 ms-1"
                                style="font-size:0.78rem">Dodaj opis</button>
                    @endif
                </div>
            @endif
            @if($sourceCard)
                <div class="mt-2">
                    <a href="{{ $sourceCard['url'] }}" class="btn btn-sm btn-outline-primary" style="font-size:0.74rem">
                        <i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}
                    </a>
                </div>
            @endif
        @endif

        @if($canAddSubtask || $subtaskTotal > 0)
        <div class="mt-3">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span style="font-size:0.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted,#94a3b8)">
                    <i class="bi bi-list-check me-1"></i>Podzadania
                </span>
                @if($subtaskTotal > 0)
                    <span class="badge" style="font-size:0.6rem; border-radius:8px; background:rgba(255,255,255,0.1); color:var(--text-muted,#94a3b8)">
                        {{ $subtaskDone }}/{{ $subtaskTotal }}
                    </span>
                @endif
                @if($canAddSubtask)
                <button wire:click="startAddSubtask({{ $task->id }})"
                        class="btn btn-link btn-sm p-0 ms-1"
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
                <div style="font-size:0.8rem; font-style:italic; color:rgba(255,255,255,0.25)">Brak podzadań.</div>
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
</div>
