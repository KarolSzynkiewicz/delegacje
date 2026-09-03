<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible>{{ session('success') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <p class="small text-muted mb-0">Łącznie: <span class="fw-semibold">{{ $templates->total() }}</span> szablonów</p>
            <x-ui.button variant="primary" wire:click="openNewModal" class="btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Nowa procedura
            </x-ui.button>
        </div>

        {{-- Filters --}}
        <div class="row g-2 mb-4">
            <div class="col-md-5">
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="form-control form-control-sm"
                       placeholder="Szukaj po nazwie lub opisie…">
            </div>
            <div class="col-md-3">
                <select wire:model.live="categoryFilter" class="form-select form-select-sm">
                    <option value="">Wszystkie kategorie</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            @if($search !== '' || $categoryFilter !== '')
                <div class="col-auto">
                    <x-ui.button variant="ghost" class="btn-sm"
                        wire:click="$set('search',''); $set('categoryFilter','')">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść
                    </x-ui.button>
                </div>
            @endif
        </div>

        @if($templates->count() > 0)
            <div class="row g-3">
                @foreach($templates as $template)
                    <div class="col-md-6 col-xl-4 min-w-0" wire:key="tpl-{{ $template->id }}">
                        <div class="card h-100 border pe-tpl-card" style="border-color: var(--glass-border) !important; background: var(--bg-card);">
                            <div class="card-body d-flex flex-column gap-2 min-w-0">
                                <div class="d-flex align-items-start justify-content-between gap-2 min-w-0">
                                    <div class="min-w-0 flex-grow-1">
                                        <h5 class="card-title fw-semibold mb-1 min-w-0">
                                            <a href="{{ route('procedure-templates.show', $template) }}"
                                               class="pe-tpl-card__name text-reset text-decoration-none stretched-link"
                                               title="{{ $template->name }}">
                                                {{ $template->name }}
                                            </a>
                                        </h5>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($template->category)
                                                <x-ui.badge variant="secondary">{{ $template->category }}</x-ui.badge>
                                            @endif
                                            @if($template->subjectType())
                                                <x-ui.badge variant="info">{{ $template->subjectType()->label() }}</x-ui.badge>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="dropdown flex-shrink-0" style="z-index: 5; position: relative;">
                                        <button class="btn btn-sm btn-outline-secondary px-2 py-1" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('procedure-templates.show', $template) }}">
                                                    <i class="bi bi-eye me-2"></i> Podgląd
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('procedure-templates.editor', $template) }}">
                                                    <i class="bi bi-pencil me-2"></i> Edytuj
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" wire:click="duplicateTemplate({{ $template->id }})">
                                                    <i class="bi bi-copy me-2"></i> Duplikuj
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger"
                                                    wire:click="deleteTemplate({{ $template->id }})"
                                                    wire:confirm="Czy na pewno usunąć szablon '{{ $template->name }}'? Trwające przebiegi nie zostaną usunięte.">
                                                    <i class="bi bi-trash me-2"></i> Usuń
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                @if($template->description)
                                    <p class="small text-muted mb-0 flex-grow-1" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $template->description }}
                                    </p>
                                @endif

                                <div class="d-flex flex-wrap gap-3 small text-muted mt-auto pt-2 border-top min-w-0">
                                    <span class="text-nowrap"><i class="bi bi-diagram-3 me-1"></i>{{ $template->nodeCount() }} kroków</span>
                                    <span class="text-nowrap"><i class="bi bi-play-circle me-1"></i>{{ $template->runs_count }} przebiegów</span>
                                    <span class="min-w-0 text-truncate"><i class="bi bi-person me-1"></i>{{ $template->createdBy?->name }}</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top d-flex gap-2 flex-wrap" style="z-index: 2; position: relative;">
                                <x-ui.button variant="primary" class="btn-sm flex-grow-1"
                                    wire:click="openStartModal({{ $template->id }})">
                                    <i class="bi bi-play-fill me-1"></i> Uruchom
                                </x-ui.button>
                                <a href="{{ route('procedure-templates.show', $template) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="Podgląd">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('procedure-templates.editor', $template) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($templates->hasPages())
                <div class="mt-4 pt-3 border-top">{{ $templates->links() }}</div>
            @endif
        @else
            <x-ui.empty-state icon="diagram-3" message="Brak procedur. Utwórz pierwszą procedurę.">
                <x-ui.button variant="primary" wire:click="openNewModal">
                    <i class="bi bi-plus-circle me-1"></i> Nowa procedura
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>

    {{-- ===== Start-run modal ===== --}}
    @if($showStartModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.55);"
             wire:click.self="$set('showStartModal',false)">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mx-2">
                <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;">
                    <div class="modal-header" style="border-color:var(--glass-border)!important;">
                        <h5 class="modal-title"><i class="bi bi-play-circle me-2"></i>Uruchom procedurę</h5>
                        <button type="button" class="btn-close" wire:click="$set('showStartModal',false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Nazwa zadania</label>
                                <input type="text" class="form-control" wire:model.live.debounce.300ms="startTaskName">
                                @error('startTaskName')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            @if($startSubjectType !== '')
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">
                                        Dotyczy: {{ $startSubjectTypeLabel }}
                                        <span class="text-muted fw-normal">(opcjonalnie)</span>
                                    </label>
                                    <select class="form-select" wire:model.live="startSubjectId">
                                        <option value="">— wybierz {{ mb_strtolower($startSubjectTypeLabel) }} —</option>
                                        @foreach($startSubjectOptions as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('startSubjectId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            @if($this->selectedSubjectLabel() !== '')
                                <div class="col-12">
                                    <div class="small text-muted">Nazwa zadania po zapisaniu:</div>
                                    <div class="fw-semibold">{{ $this->startFinalTaskName }}</div>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Przypisz do</label>
                                <select class="form-select" wire:model.defer="startAssignedTo">
                                    <option value="">— nieprzypisane —</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('startAssignedTo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Termin</label>
                                <input type="date" class="form-control" wire:model.defer="startDueDate">
                                @error('startDueDate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-color:var(--glass-border)!important;">
                        <x-ui.button variant="ghost" class="btn-sm" wire:click="$set('showStartModal',false)">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" class="btn-sm" wire:click="startRun">
                            <i class="bi bi-play-fill me-1"></i> Uruchom
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== New-template modal ===== --}}
    @if($showNewModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.55);"
             wire:click.self="$set('showNewModal',false)">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mx-2">
                <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;">
                    <div class="modal-header" style="border-color:var(--glass-border)!important;">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nowa procedura</h5>
                        <button type="button" class="btn-close" wire:click="$set('showNewModal',false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Nazwa procedury <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.defer="newName" placeholder="np. Onboarding pracownika">
                                @error('newName')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Kategoria</label>
                                <input type="text" class="form-control" wire:model.defer="newCategory" placeholder="np. BHP, HR, Produkcja">
                                @error('newCategory')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Dotyczy</label>
                                <select class="form-select" wire:model.defer="newSubjectType">
                                    <option value="">— bez konkretnej encji —</option>
                                    @foreach($subjectTypes as $type)
                                        <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text small">Przy uruchomieniu wybierzesz konkretny rekord tego typu (np. samochód albo zakwaterowanie).</div>
                                @error('newSubjectType')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Opis</label>
                                <textarea rows="3" class="form-control" wire:model.defer="newDescription"></textarea>
                                @error('newDescription')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-wrap justify-content-between gap-2" style="border-color:var(--glass-border)!important;">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if($llmConfigured)
                                <x-chrono.trigger
                                    target="openChronoModal"
                                    hint="Zaproponuj przepływ"
                                    hint-loading="Budzę zespół…"
                                />
                            @endif
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                wire:click="openImportModal"
                                wire:loading.attr="disabled"
                                wire:target="openImportModal"
                            >
                                <i class="bi bi-clipboard2-pulse me-1"></i>
                                Importuj z tekstu
                            </button>
                        </div>

                        <div class="d-flex gap-2">
                            <x-ui.button variant="ghost" class="btn-sm" wire:click="$set('showNewModal',false)">Anuluj</x-ui.button>
                            <x-ui.button variant="primary" class="btn-sm" wire:click="createTemplate">
                                <i class="bi bi-pencil-square me-1"></i>
                                <span class="d-none d-sm-inline">Utwórz i otwórz edytor</span>
                                <span class="d-sm-none">Utwórz</span>
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Import wklejonego JSON (np. z ChatGPT) ===== --}}
    @if($showImportModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.75);z-index:2000;"
             wire:click.self="closeImportModal">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border);color:var(--text-main,#f1f5f9);">
                    <div class="modal-header" style="border-color:var(--glass-border)!important;">
                        <h5 class="modal-title mb-0">
                            <i class="bi bi-clipboard2-pulse me-2"></i>Importuj przepływ z tekstu
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeImportModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Wklej JSON z krokami procedury — ten sam format co odpowiedź Chrono.
                            Możesz poprosić zewnętrzny chat o wygenerowanie kroków w tym formacie i skopiować wynik tutaj.
                            Nic nie zapisze się bez Twojego kliknięcia „Zapisz” w edytorze.
                        </p>

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label small fw-semibold mb-0">Oczekiwany format</label>
                                <span class="text-muted small">type: task · checklist · decision · wait</span>
                            </div>
                            <pre class="small mb-0 p-3 rounded" style="background:rgba(0,0,0,.25);border:1px solid var(--glass-border);max-height:220px;overflow:auto;"><code>{{ $importFormatExample }}</code></pre>
                        </div>

                        <div>
                            <label class="form-label small fw-semibold">Wklej tekst</label>
                            <textarea
                                rows="10"
                                class="form-control font-monospace"
                                wire:model.defer="importText"
                                placeholder='{"steps":[{"type":"task","name":"Pierwszy krok"}]}'
                                spellcheck="false"
                            ></textarea>
                        </div>

                        @if($importError)
                            <x-ui.alert variant="danger" class="mt-3 mb-0">{{ $importError }}</x-ui.alert>
                        @endif
                    </div>
                    <div class="modal-footer" style="border-color:var(--glass-border)!important;">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeImportModal">
                            Anuluj
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            wire:click="importFromText"
                            wire:loading.attr="disabled"
                            wire:target="importFromText"
                        >
                            <span wire:loading.remove wire:target="importFromText">
                                <i class="bi bi-box-arrow-in-down me-1"></i> Importuj i otwórz edytor
                            </span>
                            <span wire:loading wire:target="importFromText">Wczytuję…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Chrono projektuje przepływ; zatwierdzenie dzieje się w edytorze ===== --}}
    @if($showChronoModal)
        <x-chrono.modal
            key="procedure-chrono"
            close="closeChronoModal"
            fetch="fetchChronoFlow"
            :loading="$chronoLoading"
            :error="$chronoError"
            title="AskChrono — przepływ procedury"
            status-loading="Projektuję kroki procedury…"
            thinking="Chrono czyta nazwę, kategorię, typ encji i opis, a potem układa kroki. Za chwilę zobaczysz je na canvasie — nic nie zapisze się bez Twojego kliknięcia."
            empty-message="Wróć do formularza i doprecyzuj opis procedury."
            dialog-class="modal-dialog-centered"
        />
    @endif

    <style>
        .pe-tpl-card {
            overflow: hidden;
            min-width: 0;
        }
        .pe-tpl-card__name {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
    </style>
</div>
