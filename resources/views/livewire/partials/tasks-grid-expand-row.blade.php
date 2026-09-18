<tr wire:key="tg-expanded-{{ $task->id }}"
    class="tg-expand-row"
    data-tg-drop-task="{{ $task->id }}"
    data-tg-accepts-sub="{{ $canAddSubtask ? '1' : '0' }}"
    data-tg-expand-for="{{ $task->id }}">
    <td style="width:36px; border-left:3px solid {{ $borderColor }}; padding:0 !important; background:rgba(10,15,29,0.6) !important"></td>
    <td style="width:36px; padding:0 !important; background:rgba(10,15,29,0.6) !important"></td>
    <td colspan="{{ count($visibleColumns) }}">
        <div class="tg-expand-body">
        <div class="row g-4">

            {{-- ── Description ── --}}
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="tg-mono" style="font-size:0.66rem; font-weight:600; text-transform:uppercase; letter-spacing:.7px; color:var(--text-muted,#94a3b8)">
                        <i class="bi bi-card-text me-1"></i>Opis
                    </span>
                    @if($this->rowWritable($task, 'description') && !($isEditing && $editingField === 'description'))
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
                    @php
                        $descText = $task->plainDescription();
                        $ediDesc = $this->ediCell($task, 'description');
                    @endphp
                    @if($ediDesc)
                        <div class="tg-edi tg-edi--{{ $ediDesc['kind'] }} p-2 rounded">
                            @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediDesc, 'rowId' => $task->id, 'field' => 'description'])
                        </div>
                    @elseif($descText)
                        <div style="white-space:pre-wrap; max-height:160px; overflow-y:auto; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.08); border-radius:6px; padding:10px 12px; font-size:0.82rem; line-height:1.55; color:var(--text-main,#f1f5f9)">{{ $descText }}</div>
                    @else
                        <div style="font-size:0.82rem; font-style:italic; color:rgba(255,255,255,0.25)">
                            Brak opisu.
                            @if($this->rowWritable($task, 'description'))
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
            @if($canAddSubtask || $subtaskTotal > 0)
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="tg-mono" style="font-size:0.66rem; font-weight:600; text-transform:uppercase; letter-spacing:.7px; color:var(--text-muted,#94a3b8)">
                        <i class="bi bi-list-check me-1"></i>Podzadania
                    </span>
                    @if($subtaskTotal > 0)
                        <span class="badge tg-mono" style="font-size:0.62rem; border-radius:8px; background:rgba(255,255,255,0.1); color:var(--text-muted,#94a3b8)">
                            {{ $subtaskDone }}/{{ $subtaskTotal }}
                        </span>
                        <div class="progress flex-grow-1" style="height:4px; max-width:70px; border-radius:2px; background:rgba(255,255,255,0.08)">
                            <div style="width:{{ round(($subtaskDone/$subtaskTotal)*100) }}%; height:100%; border-radius:2px; background:{{ $subtaskDone === $subtaskTotal ? '#10b981' : '#a855f7' }}"></div>
                        </div>
                    @endif
                    @if($canAddSubtask)
                    <button wire:click="startAddSubtask({{ $task->id }})"
                            class="btn btn-link btn-sm p-0 ms-1"
                            style="font-size:0.72rem; text-decoration:none; color:rgba(16,185,129,0.8)">
                        <i class="bi bi-plus-circle me-1"></i>Dodaj podzadanie
                    </button>
                    @endif
                </div>

                @if($subtaskTotal > 0)
                <div style="max-height:220px; overflow-y:auto">
                    @foreach($subtasksAll as $subtask)
                    <div class="d-flex align-items-center gap-2 py-1 px-1 tg-subtask-item"
                         style="border-bottom:1px solid rgba(255,255,255,0.05); border-radius:4px"
                         data-tg-sub-id="{{ $subtask->id }}"
                         wire:key="tg-st-{{ $subtask->id }}">
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
            @endif

        </div>
        </div>
    </td>
</tr>
