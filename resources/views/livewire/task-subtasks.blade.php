<div>
    @php
        // Upewnij się, że relacja jest załadowana
        if (!$this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }
        
        $completedSubtasks = $this->completedSubtasks ?? collect([]);
        $pendingSubtasks = $this->pendingSubtasks ?? collect([]);
        $totalSubtasks = $completedSubtasks->count() + $pendingSubtasks->count();
        $completedCount = $completedSubtasks->count();
        $pendingCount = $pendingSubtasks->count();
        $progressPercentage = is_numeric($this->progressPercentage) ? (float)$this->progressPercentage : 0;
        $progressVariant = $progressPercentage == 100 ? 'success' : ($progressPercentage > 0 ? 'warning' : 'default');
    @endphp

    <x-ui.card label="Podzadania">
        <!-- Progress bar -->
        @if($totalSubtasks > 0)
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted">
                        <i class="bi bi-list-check me-1"></i>
                        {{ $completedCount }}/{{ $totalSubtasks }} ukończone
                    </span>
                    <span class="small fw-bold">{{ round($progressPercentage) }}%</span>
                </div>
                <x-ui.progress 
                    value="{{ $completedCount }}" 
                    max="{{ $totalSubtasks }}" 
                    variant="{{ $progressVariant }}"
                />
            </div>
        @endif

        <!-- Formularz dodawania podzadania -->
        <div class="mb-4">
            <form wire:submit.prevent="addSubtask" class="d-flex gap-2">
                <div class="flex-grow-1">
                    <input 
                        type="text" 
                        wire:model="newSubtaskName"
                        placeholder="Dodaj nowe podzadanie..."
                        class="form-control @error('newSubtaskName') is-invalid @enderror mb-0"
                    />
                    @error('newSubtaskName')
                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                    @enderror
                </div>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Dodaj</span>
                    <span wire:loading>Dodawanie...</span>
                </x-ui.button>
            </form>
        </div>

        <!-- Lista podzadań do zrobienia -->
        @if($pendingCount > 0)
            <div class="mb-4">
                <h6 class="small fw-semibold text-muted mb-2">
                    <i class="bi bi-circle me-1"></i>Do zrobienia ({{ $pendingCount }})
                </h6>
                <div>
                    @foreach($pendingSubtasks as $subtask)
                        <div class="form-check" wire:key="pending-{{ $subtask->id }}">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="subtask-{{ $subtask->id }}"
                                wire:click="toggleSubtask({{ $subtask->id }})"
                            >
                            <label class="form-check-label" for="subtask-{{ $subtask->id }}">
                                {{ $subtask->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Lista wykonanych podzadań -->
        @if($completedCount > 0)
            <div>
                <h6 class="small fw-semibold text-muted mb-2">
                    <i class="bi bi-check-circle me-1"></i>Wykonane ({{ $completedCount }})
                </h6>
                <div>
                    @foreach($completedSubtasks as $subtask)
                        <div class="form-check" wire:key="completed-{{ $subtask->id }}" style="opacity: 0.7;">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="subtask-{{ $subtask->id }}"
                                checked
                                wire:click="toggleSubtask({{ $subtask->id }})"
                            >
                            <label class="form-check-label text-decoration-line-through text-muted" for="subtask-{{ $subtask->id }}">
                                {{ $subtask->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Empty state -->
        @if($totalSubtasks === 0)
            <x-ui.empty-state 
                icon="list-check" 
                message="Brak podzadań. Dodaj pierwsze podzadanie powyżej."
            />
        @endif
    </x-ui.card>
</div>
