<div>
    <x-ui.card>
        <div class="st-head">
            <div class="st-head__title">
                <span class="card-label">Podzadania</span>
                @if($totalSubtasks > 0)
                    <span class="st-head__count">{{ $completedCount }}/{{ $totalSubtasks }}</span>
                @endif
                <button
                    type="button"
                    class="st-hint"
                    tabindex="-1"
                    title="Numery #1, #2, … idą według kolejności dodania. W komentarzu do zadania #n zamieni się na kartę z odznaką i nazwą podzadania. Kliknij nazwę, żeby skreślić lub przywrócić."
                    aria-label="Jak działają numery podzadań"
                >
                    <i class="bi bi-info-circle"></i>
                </button>
            </div>

            <div class="st-head__tools">
                @if($task->status !== \App\Enums\TaskStatus::COMPLETED && $task->status !== \App\Enums\TaskStatus::CANCELLED)
                    <x-tasks-actions
                        :task="$task"
                        size="sm"
                        gap="1"
                        :show-view="false"
                        :show-edit="false"
                        :compact="true"
                        class="st-head__actions"
                    />
                @endif

                @if($canSuggestWithAi && $llmConfigured)
                    <button
                        type="button"
                        class="ac-trigger"
                        wire:click="openAiModal"
                        wire:loading.attr="disabled"
                        wire:target="openAiModal"
                        title="AskChrono — rozbij na podzadania"
                    >
                        <x-ask-chrono-bot
                            :size="36"
                            wire:loading.class="ac-bot--thinking"
                            wire:target="openAiModal"
                        />
                        <span class="ac-trigger__text d-none d-md-flex">
                            <span class="ac-trigger__name">AskChrono</span>
                            <span class="ac-trigger__hint">
                                <span wire:loading.remove wire:target="openAiModal">Rozbij na podzadania</span>
                                <span wire:loading wire:target="openAiModal">Budzę bota… </span>
                            </span>
                        </span>
                    </button>
                @endif
            </div>
        </div>

        @if($totalSubtasks > 0)
            <div class="st-progress">
                <x-ui.progress
                    value="{{ $completedCount }}"
                    max="{{ $totalSubtasks }}"
                    variant="{{ $progressVariant }}"
                />
                <span class="st-progress__pct font-mono">{{ round($progressPercentage) }}%</span>
            </div>
        @endif

        <form wire:submit.prevent="addSubtask" class="st-add d-flex gap-2 align-items-start">
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
                @include('livewire.partials.mention-dropdown')
                @error('newSubtaskName')
                    <span class="text-danger small d-block mt-1">{{ $message }}</span>
                @enderror
            </div>
            <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled" class="flex-shrink-0">
                <span wire:loading.remove>Dodaj</span>
                <span wire:loading>Dodawanie...</span>
            </x-ui.button>
        </form>

        @if($pendingCount > 0)
            <section class="st-section" wire:key="pending-section-{{ $pendingCount }}">
                <h6 class="st-section__head">
                    <i class="bi bi-circle"></i>
                    <span>Do zrobienia</span>
                    <span class="st-section__count">{{ $pendingCount }}</span>
                </h6>
                <ul class="st-list">
                    @foreach($pendingSubtasks as $subtask)
                        @include('livewire.partials.subtask-row', ['subtask' => $subtask, 'done' => false])
                    @endforeach
                </ul>
            </section>
        @endif

        @if($completedCount > 0)
            <section class="st-section" wire:key="completed-section-{{ $completedCount }}">
                <h6 class="st-section__head">
                    <i class="bi bi-check-circle"></i>
                    <span>Wykonane</span>
                    <span class="st-section__count">{{ $completedCount }}</span>
                </h6>
                <ul class="st-list">
                    @foreach($completedSubtasks as $subtask)
                        @include('livewire.partials.subtask-row', ['subtask' => $subtask, 'done' => true])
                    @endforeach
                </ul>
            </section>
        @endif

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
                            <h6 class="st-section__head">
                                <i class="bi bi-circle"></i>
                                <span>Do zatwierdzenia</span>
                                <span class="st-section__count">{{ count($aiProposals) }}</span>
                            </h6>

                            <ul class="st-list">
                                @foreach($aiProposals as $index => $proposal)
                                    <li class="st-item st-item--input" wire:key="ai-proposal-{{ $index }}">
                                        <span class="badge badge-secondary subtask-num st-item__num">#{{ $index + 1 }}</span>

                                        <div class="st-item__editor">
                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                wire:model.defer="aiProposals.{{ $index }}"
                                            >
                                        </div>

                                        <div class="st-item__actions">
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
                                    </li>
                                @endforeach
                            </ul>
                        @elseif(! $aiLoading && ! $aiError)
                            <x-ui.empty-state icon="robot" message="Brak propozycji do wyświetlenia." />
                        @endif
                    </div>

                    <div class="modal-footer" style="border-color:var(--glass-border);">
                        @if(! $aiLoading && $aiProposals !== [])
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
                        @else
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeAiModal">
                                Zamknij
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

</div>
