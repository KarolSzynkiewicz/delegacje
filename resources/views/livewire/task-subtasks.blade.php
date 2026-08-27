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
                    <x-chrono.trigger target="openChronoAssist" hint="Rozbij na podzadania" />
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

    @if($showChronoAssist)
        <livewire:chrono-assist
            context="task"
            status="Wybierz akcję dla tego zadania"
            context-label="Zadanie"
            :context-chips="array_values(array_filter([$task->name]))"
            :item-count="$totalSubtasks"
            wire:key="task-{{ $task->id }}-assist"
        />
    @endif

    @if($showAiModal)
        <x-chrono.modal
            key="task-subtasks-ai"
            close="closeAiModal"
            fetch="fetchAiProposals"
            :loading="$aiLoading"
            :error="$aiError"
            :ready="$aiProposals !== []"
            status-loading="Czytam zadanie i układam kroki…"
            :status-ready="'Mam '.count($aiProposals).' propozycji — sprawdź i zatwierdź'"
            thinking="Chrono analizuje nazwę i opis zadania, a potem proponuje kroki."
            lead="Propozycje powstały na podstawie nazwy i opisu zadania. Możesz je poprawić przed zatwierdzeniem — nic nie trafi do bazy bez Twojej decyzji."
        >
            <h6 class="st-section__head">
                <i class="bi bi-circle"></i>
                <span>Do zatwierdzenia</span>
                <span class="st-section__count">{{ count($aiProposals) }}</span>
            </h6>

            <ul class="st-list">
                @foreach($aiProposals as $index => $proposal)
                    <li class="st-item st-item--input" wire:key="ai-proposal-{{ $index }}">
                        <input
                            type="checkbox"
                            class="form-check-input st-item__check flex-shrink-0"
                            value="{{ $index }}"
                            wire:model="aiSelected"
                            title="Zaznacz do zatwierdzenia"
                            aria-label="Zaznacz propozycję #{{ $index + 1 }}"
                        >

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

            <x-slot:footer>
                <button type="button" class="btn btn-outline-secondary" wire:click="closeAiModal">
                    Anuluj
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    wire:click="confirmSelectedAiProposals"
                    wire:loading.attr="disabled"
                    wire:target="confirmSelectedAiProposals"
                    @disabled(count($aiSelected) === 0)
                >
                    Zatwierdź wybrane
                    @if(count($aiSelected) > 0)
                        ({{ count($aiSelected) }})
                    @endif
                </button>
            </x-slot:footer>
        </x-chrono.modal>
    @endif

</div>
