<div class="rp-doc-section rp-doc-section--tasks">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="rp-field-label mb-0">
            <i class="bi bi-check2-square me-1"></i>Zadania
            <span class="rp-plain-tag" style="cursor:default;">{{ $selected->tasks->count() }}</span>
        </div>
        <button type="button" wire:click="openTaskModalManual" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-plus me-1"></i>Dodaj
        </button>
    </div>
    @forelse($selected->tasks as $task)
        <div class="d-flex justify-content-between align-items-start gap-2" wire:key="task-{{ $task->id }}" style="font-size:.88rem;padding:.65rem 0;border-top:1px solid rgba(255,255,255,.05);">
            <div class="min-width-0">
                <div style="{{ $task->status === \App\Enums\TaskStatus::COMPLETED ? 'text-decoration:line-through;color:var(--text-muted);' : '' }}">{{ $task->name }}</div>
                <div style="color:var(--text-muted);font-size:.78rem;margin-top:.15rem;">
                    @if($task->due_date)<i class="bi bi-calendar-event me-1"></i>{{ $task->due_date->format('d.m.Y') }}@endif
                    @if($task->assignedTo) · {{ $task->assignedTo->name }} @endif
                </div>
            </div>
            <button type="button" wire:click="toggleTaskDone({{ $task->id }})" class="btn btn-sm btn-outline-secondary flex-shrink-0" style="padding:2px 9px;font-size:.75rem;">
                <i class="bi bi-check2{{ $task->status === \App\Enums\TaskStatus::COMPLETED ? '-square-fill' : '-square' }}"></i>
            </button>
        </div>
    @empty
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Brak zaplanowanych zadań.</p>
    @endforelse
</div>
