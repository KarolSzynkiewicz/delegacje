<div>
    <x-ui.card label="Podzadania">
        @if($canSuggestWithAi && $llmConfigured)
            <div class="d-flex justify-content-end mb-3">
                <button
                    type="button"
                    class="ac-trigger"
                    wire:click="openAiModal"
                    wire:loading.attr="disabled"
                    wire:target="openAiModal"
                >
                    <x-ask-chrono-bot
                        :size="40"
                        wire:loading.class="ac-bot--thinking"
                        wire:target="openAiModal"
                    />
                    <span class="ac-trigger__text">
                        <span class="ac-trigger__name">AskChrono</span>
                        <span class="ac-trigger__hint">
                            <span wire:loading.remove wire:target="openAiModal">Rozbij na podzadania</span>
                            <span wire:loading wire:target="openAiModal">Budzę bota… </span>
                        </span>
                    </span>
                </button>
            </div>
        @endif

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

        <!-- Formularz dodawania podzadania -->
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
            <div class="mb-4" wire:key="pending-section-{{ $pendingCount }}">
                <h6 class="small fw-semibold text-muted mb-2">
                    <i class="bi bi-circle me-1"></i>Do zrobienia ({{ $pendingCount }})
                </h6>
                <div>
                    @foreach($pendingSubtasks as $subtask)
                        @php
                            $meta = $subtaskMeta[$subtask->id] ?? [];
                        @endphp
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2" wire:key="pending-{{ $subtask->id }}">
                            <div class="flex-grow-1">
                                @if($editingSubtaskId === $subtask->id)
                                    <div class="d-flex align-items-start gap-2 w-100">
                                        <span
                                            class="badge badge-secondary subtask-num fw-semibold flex-shrink-0 mt-1"
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
                                    <div class="form-check mb-0 d-flex align-items-center gap-1">
                                        <input
                                            type="checkbox"
                                            class="form-check-input flex-shrink-0"
                                            id="subtask-{{ $subtask->id }}"
                                            wire:click="toggleSubtask({{ $subtask->id }})"
                                        >
                                        <label class="form-check-label" for="subtask-{{ $subtask->id }}">
                                            <span
                                                class="badge badge-secondary subtask-num fw-semibold me-1"
                                                title="Numer podzadania w tym zadaniu"
                                            >#{{ $subtaskNumbers[$subtask->id] }}</span>
                                            {!! \App\Services\UserMentionService::highlightMentions(e($subtask->name), $mentionUsersForAutocomplete) !!}
                                            @if($subtask->assignedTo)
                                                <span class="badge bg-primary bg-opacity-25 text-primary ms-1">{{ $subtask->assignedTo->name }}</span>
                                            @endif
                                        </label>
                                        @if(!empty($meta['created_by']))
                                            @php
                                                $tipLines = [];
                                                $tipLines[] = 'Dodał: ' . ($meta['created_by'] ?? '') . ($meta['created_at'] ? ' · ' . $meta['created_at'] : '');
                                            @endphp
                                            <span
                                                class="text-muted ms-1 flex-shrink-0"
                                                style="cursor:help;font-size:.75rem;line-height:1;"
                                                title="{{ implode('&#10;', $tipLines) }}"
                                            ><i class="bi bi-info-circle"></i></span>
                                        @endif
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
                                            x-on:click="confirm('Na pewno usunąć to podzadanie?') && $wire.deleteSubtask({{ $subtask->id }})">
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
            <div wire:key="completed-section-{{ $completedCount }}">
                <h6 class="small fw-semibold text-muted mb-2">
                    <i class="bi bi-check-circle me-1"></i>Wykonane ({{ $completedCount }})
                </h6>
                <div>
                    @foreach($completedSubtasks as $subtask)
                        @php
                            $meta = $subtaskMeta[$subtask->id] ?? [];
                        @endphp
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2" wire:key="completed-{{ $subtask->id }}" style="opacity: 0.7;">
                            <div class="flex-grow-1">
                                @if($editingSubtaskId === $subtask->id)
                                    <div class="d-flex align-items-start gap-2 w-100">
                                        <span
                                            class="badge badge-secondary subtask-num fw-semibold flex-shrink-0 mt-1"
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
                                    <div class="form-check mb-0 d-flex align-items-center gap-1">
                                        <input
                                            type="checkbox"
                                            class="form-check-input flex-shrink-0"
                                            id="subtask-{{ $subtask->id }}"
                                            checked
                                            wire:click="toggleSubtask({{ $subtask->id }})"
                                        >
                                        <label class="form-check-label text-muted" for="subtask-{{ $subtask->id }}">
                                            <span
                                                class="badge badge-secondary subtask-num fw-semibold me-1"
                                                title="Numer podzadania w tym zadaniu"
                                            >#{{ $subtaskNumbers[$subtask->id] }}</span>
                                            <span class="text-decoration-line-through">{!! \App\Services\UserMentionService::highlightMentions(e($subtask->name), $mentionUsersForAutocomplete) !!}</span>
                                        </label>
                                        @if(!empty($meta['created_by']) || !empty($meta['completed_by']))
                                            @php
                                                $tipLines = [];
                                                if (!empty($meta['created_by'])) {
                                                    $tipLines[] = 'Dodał: ' . ($meta['created_by'] ?? '') . ($meta['created_at'] ? ' · ' . $meta['created_at'] : '');
                                                }
                                                if (!empty($meta['completed_by'])) {
                                                    $tipLines[] = 'Zamknął: ' . ($meta['completed_by'] ?? '') . ($meta['completed_at'] ? ' · ' . $meta['completed_at'] : '');
                                                }
                                            @endphp
                                            <span
                                                class="text-muted ms-1 flex-shrink-0"
                                                style="cursor:help;font-size:.75rem;line-height:1;"
                                                title="{{ implode('&#10;', $tipLines) }}"
                                            ><i class="bi bi-info-circle"></i></span>
                                        @endif
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
                                            x-on:click="confirm('Na pewno usunąć to podzadanie?') && $wire.deleteSubtask({{ $subtask->id }})">
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

    @if($showAiModal)
        @teleport('body')
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            style="background:rgba(0,0,0,.75);z-index:2000;"
            wire:click.self="closeAiModal"
            wire:key="task-subtasks-ai-modal"
        >
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">
                    <div class="modal-header" style="border-color:var(--glass-border);">
                        <div class="d-flex align-items-center gap-3">
                            <x-ask-chrono-bot
                                :size="54"
                                :state="$aiLoading ? 'thinking' : ($aiProposals !== [] ? 'done' : 'idle')"
                            />
                            <div>
                                <h5 class="modal-title ac-modal__title mb-0">AskChrono</h5>
                                <span class="ac-modal__status">
                                    @if($aiLoading)
                                        Czytam zadanie i układam kroki…
                                    @elseif($aiError)
                                        Nie udało się przygotować propozycji
                                    @elseif($aiProposals !== [])
                                        Mam {{ count($aiProposals) }} propozycji — sprawdź i zatwierdź
                                    @else
                                        Gotowy do pracy
                                    @endif
                                </span>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeAiModal"></button>
                    </div>

                    <div class="modal-body">
                        @if($aiLoading)
                            <div class="ac-thinking" wire:key="ai-thinking" x-data="{}" x-init="$wire.fetchAiProposals()">
                                <div class="ac-thinking__bars">
                                    <span></span><span></span><span></span>
                                </div>
                                <p class="ac-thinking__text mb-0">
                                    Chrono analizuje nazwę i opis zadania, a potem proponuje kroki.
                                </p>
                            </div>
                        @endif

                        @if(! $aiLoading)
                            <p class="text-muted small mb-3">
                                Propozycje powstały na podstawie nazwy i opisu zadania.
                                Możesz je poprawić przed zatwierdzeniem — nic nie trafi do bazy bez Twojej decyzji.
                            </p>
                        @endif

                        @if($aiError)
                            <x-ui.alert variant="danger" class="mb-3">{{ $aiError }}</x-ui.alert>
                        @endif

                        @if(! $aiLoading && $aiProposals !== [])
                            <h6 class="small fw-semibold text-muted mb-2">
                                <i class="bi bi-circle me-1"></i>Do zatwierdzenia ({{ count($aiProposals) }})
                            </h6>

                            <div>
                                @foreach($aiProposals as $index => $proposal)
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2" wire:key="ai-proposal-{{ $index }}">
                                        <div class="flex-grow-1 d-flex align-items-center gap-2">
                                            <span
                                                class="badge badge-secondary subtask-num fw-semibold flex-shrink-0"
                                                style="min-width: 2.35rem;"
                                            >#{{ $index + 1 }}</span>
                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                wire:model.defer="aiProposals.{{ $index }}"
                                            >
                                        </div>

                                        <div class="flex-shrink-0">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                wire:click="confirmAiProposal({{ $index }})"
                                                wire:loading.attr="disabled"
                                                wire:target="confirmAiProposal({{ $index }})"
                                            >
                                                Zatwierdź
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(! $aiLoading && ! $aiError)
                            <x-ui.empty-state icon="robot" message="Brak propozycji do wyświetlenia." />
                        @endif
                    </div>

                    @if(! $aiLoading && $aiProposals !== [])
                        <div class="modal-footer" style="border-color:var(--glass-border);">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeAiModal">
                                Anuluj
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                wire:click="confirmAllAiProposals"
                                wire:loading.attr="disabled"
                                wire:target="confirmAllAiProposals"
                            >
                                Zatwierdź wszystkie
                            </button>
                        </div>
                    @else
                        <div class="modal-footer" style="border-color:var(--glass-border);">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeAiModal">
                                Zamknij
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endteleport
    @endif

</div>
