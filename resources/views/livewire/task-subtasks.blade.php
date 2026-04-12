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
        $subtaskNumbers = $this->subtaskDisplayNumbers;
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
                <p class="small text-muted mb-0 mt-2">
                    Numery <span class="fw-semibold">#1, #2, …</span> według kolejności dodania — w komentarzu do zadania <span class="fw-semibold">#n</span> zamieni się na kartę z odznaką i nazwą podzadania (jak poniżej na liście).
                </p>
            </div>
        @endif

        <!-- Formularz dodawania podzadania (opcjonalna wzmianka @ jak w komentarzach) -->
        <div class="mb-4">
            <form wire:submit.prevent="addSubtask" class="d-flex gap-2 align-items-start">
                <div
                    class="flex-grow-1 position-relative"
                    x-data="subtaskMention(@js($mentionUsersForAutocomplete), 'newSubtaskName')"
                >
                    <input
                        type="text"
                        wire:model.defer="newSubtaskName"
                        x-ref="inp"
                        placeholder="Wpisz nazwę podzadania…"
                        class="form-control @error('newSubtaskName') is-invalid @enderror mb-0"
                        @input="onInput($event)"
                        @keydown.escape="close()"
                    />
                    <ul
                        x-show="show && results.length > 0"
                        x-cloak
                        class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
                        style="z-index:1090;min-width:16rem;max-height:14rem;overflow-y:auto;top:100%;left:0;right:auto;"
                    >
                        <template x-for="(user, idx) in results" :key="user.name">
                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                                    :class="idx === activeIdx ? 'active' : ''"
                                    @click="selectUser(user)"
                                    @mouseenter="activeIdx = idx"
                                >
                                    <span
                                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary fw-semibold flex-shrink-0"
                                        style="width:1.75rem;height:1.75rem;font-size:.65rem;"
                                        x-text="user.initials"
                                    ></span>
                                    <span class="small fw-medium" x-text="user.name"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    @error('newSubtaskName')
                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                    @enderror
                </div>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled" class="flex-shrink-0">
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
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2" wire:key="pending-{{ $subtask->id }}">
                            <div class="flex-grow-1">
                                @if($editingSubtaskId === $subtask->id)
                                    <div class="d-flex align-items-start gap-2 w-100">
                                        <span
                                            class="badge bg-secondary bg-opacity-25 text-body-secondary fw-semibold flex-shrink-0 mt-1"
                                            style="min-width: 2.35rem;"
                                            title="Numer podzadania w tym zadaniu"
                                        >#{{ $subtaskNumbers[$subtask->id] }}</span>
                                    <div
                                        class="flex-grow-1 position-relative"
                                        x-data="subtaskMention(@js($mentionUsersForAutocomplete), 'editingSubtaskName')"
                                    >
                                        <input
                                            type="text"
                                            class="form-control form-control-sm @error('editingSubtaskName') is-invalid @enderror"
                                            wire:model.defer="editingSubtaskName"
                                            x-ref="inp"
                                            @input="onInput($event)"
                                            @keydown.escape="close()"
                                        />
                                        <ul
                                            x-show="show && results.length > 0"
                                            x-cloak
                                            class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
                                            style="z-index:1090;min-width:16rem;max-height:14rem;overflow-y:auto;top:100%;left:0;right:auto;"
                                        >
                                            <template x-for="(user, idx) in results" :key="user.name">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                                                        :class="idx === activeIdx ? 'active' : ''"
                                                        @click="selectUser(user)"
                                                        @mouseenter="activeIdx = idx"
                                                    >
                                                        <span
                                                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary fw-semibold flex-shrink-0"
                                                            style="width:1.75rem;height:1.75rem;font-size:.65rem;"
                                                            x-text="user.initials"
                                                        ></span>
                                                        <span class="small fw-medium" x-text="user.name"></span>
                                                    </button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    </div>
                                    @error('editingSubtaskName')
                                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                    @enderror
                                @else
                                    <div class="form-check mb-0">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="subtask-{{ $subtask->id }}"
                                            wire:click="toggleSubtask({{ $subtask->id }})"
                                        >
                                        <label class="form-check-label" for="subtask-{{ $subtask->id }}">
                                            <span
                                                class="badge bg-secondary bg-opacity-25 text-body-secondary fw-semibold me-1"
                                                title="Numer podzadania w tym zadaniu"
                                            >#{{ $subtaskNumbers[$subtask->id] }}</span>
                                            {!! \App\Services\UserMentionService::highlightMentions(e($subtask->name), $mentionUsersForAutocomplete) !!}
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                @if($editingSubtaskId === $subtask->id)
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="saveSubtaskEdits({{ $subtask->id }})">
                                        Zapisz
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelEditSubtask">
                                        Anuluj
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="startEditSubtask({{ $subtask->id }})">
                                        Edytuj
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="deleteSubtask({{ $subtask->id }})"
                                            onclick="return confirm('Na pewno usunąć to podzadanie?')">
                                        Usuń
                                    </button>
                                @endif
                            </div>
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
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2" wire:key="completed-{{ $subtask->id }}" style="opacity: 0.7;">
                            <div class="flex-grow-1">
                                @if($editingSubtaskId === $subtask->id)
                                    <div class="d-flex align-items-start gap-2 w-100">
                                        <span
                                            class="badge bg-secondary bg-opacity-25 text-body-secondary fw-semibold flex-shrink-0 mt-1"
                                            style="min-width: 2.35rem;"
                                            title="Numer podzadania w tym zadaniu"
                                        >#{{ $subtaskNumbers[$subtask->id] }}</span>
                                    <div
                                        class="flex-grow-1 position-relative"
                                        x-data="subtaskMention(@js($mentionUsersForAutocomplete), 'editingSubtaskName')"
                                    >
                                        <input
                                            type="text"
                                            class="form-control form-control-sm @error('editingSubtaskName') is-invalid @enderror"
                                            wire:model.defer="editingSubtaskName"
                                            x-ref="inp"
                                            @input="onInput($event)"
                                            @keydown.escape="close()"
                                        />
                                        <ul
                                            x-show="show && results.length > 0"
                                            x-cloak
                                            class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
                                            style="z-index:1090;min-width:16rem;max-height:14rem;overflow-y:auto;top:100%;left:0;right:auto;"
                                        >
                                            <template x-for="(user, idx) in results" :key="user.name">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                                                        :class="idx === activeIdx ? 'active' : ''"
                                                        @click="selectUser(user)"
                                                        @mouseenter="activeIdx = idx"
                                                    >
                                                        <span
                                                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary fw-semibold flex-shrink-0"
                                                            style="width:1.75rem;height:1.75rem;font-size:.65rem;"
                                                            x-text="user.initials"
                                                        ></span>
                                                        <span class="small fw-medium" x-text="user.name"></span>
                                                    </button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    </div>
                                    @error('editingSubtaskName')
                                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                    @enderror
                                @else
                                    <div class="form-check mb-0">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="subtask-{{ $subtask->id }}"
                                            checked
                                            wire:click="toggleSubtask({{ $subtask->id }})"
                                        >
                                        <label class="form-check-label text-muted" for="subtask-{{ $subtask->id }}">
                                            <span
                                                class="badge bg-secondary bg-opacity-25 text-body-secondary fw-semibold me-1"
                                                title="Numer podzadania w tym zadaniu"
                                            >#{{ $subtaskNumbers[$subtask->id] }}</span>
                                            <span class="text-decoration-line-through">{!! \App\Services\UserMentionService::highlightMentions(e($subtask->name), $mentionUsersForAutocomplete) !!}</span>
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                @if($editingSubtaskId === $subtask->id)
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="saveSubtaskEdits({{ $subtask->id }})">
                                        Zapisz
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelEditSubtask">
                                        Anuluj
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="startEditSubtask({{ $subtask->id }})">
                                        Edytuj
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="deleteSubtask({{ $subtask->id }})"
                                            onclick="return confirm('Na pewno usunąć to podzadanie?')">
                                        Usuń
                                    </button>
                                @endif
                            </div>
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
