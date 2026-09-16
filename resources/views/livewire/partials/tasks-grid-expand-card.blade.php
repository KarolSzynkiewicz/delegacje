<div class="tg-card-expand tg-expand-body" data-tg-expand-for="{{ $task->id }}">
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
            <div class="d-flex align-items-center gap-2 py-1" wire:key="tg-card-st-{{ $subtask->id }}" data-tg-sub-id="{{ $subtask->id }}">
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
