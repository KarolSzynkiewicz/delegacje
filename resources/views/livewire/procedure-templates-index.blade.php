<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible>{{ session('success') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fs-5 fw-semibold mb-1">Procedury (SOP)</h3>
                <p class="small text-muted mb-0">Łącznie: <span class="fw-semibold">{{ $templates->total() }}</span> szablonów</p>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                <x-ui.button variant="primary" wire:click="openNewModal" class="btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Nowa procedura
                </x-ui.button>
            </div>
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
                    <div class="col-md-6 col-xl-4" wire:key="tpl-{{ $template->id }}">
                        <div class="card h-100 border" style="border-color: var(--glass-border) !important; background: var(--bg-card);">
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <h5 class="card-title fw-semibold mb-1 text-truncate">{{ $template->name }}</h5>
                                        @if($template->category)
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis small">{{ $template->category }}</span>
                                        @endif
                                    </div>
                                    <div class="dropdown flex-shrink-0">
                                        <button class="btn btn-sm btn-outline-secondary px-2 py-1" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
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

                                <div class="d-flex flex-wrap gap-3 small text-muted mt-auto pt-2 border-top">
                                    <span><i class="bi bi-diagram-3 me-1"></i>{{ $template->nodeCount() }} kroków</span>
                                    <span><i class="bi bi-play-circle me-1"></i>{{ $template->runs_count }} przebiegów</span>
                                    <span><i class="bi bi-person me-1"></i>{{ $template->createdBy?->name }}</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top d-flex gap-2">
                                <x-ui.button variant="primary" class="btn-sm flex-grow-1"
                                    wire:click="openStartModal({{ $template->id }})">
                                    <i class="bi bi-play-fill me-1"></i> Uruchom
                                </x-ui.button>
                                <a href="{{ route('procedure-templates.editor', $template) }}"
                                   class="btn btn-sm btn-outline-secondary">
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
            <div class="modal-dialog modal-dialog-centered">
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
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Dotyczy (opcjonalnie)</label>
                                <input type="text" class="form-control" wire:model.live.debounce.300ms="startDetailName"
                                       placeholder="np. Michał Jagiełło">
                                <div class="form-text small">Dopisane do nazwy zadania, żeby było wiadomo czego/kogo dokładnie dotyczy.</div>
                                @error('startDetailName')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            @if(trim($startDetailName) !== '')
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
            <div class="modal-dialog modal-dialog-centered">
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
                                <label class="form-label small fw-semibold">Opis</label>
                                <textarea rows="3" class="form-control" wire:model.defer="newDescription"></textarea>
                                @error('newDescription')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-color:var(--glass-border)!important;">
                        <x-ui.button variant="ghost" class="btn-sm" wire:click="$set('showNewModal',false)">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" class="btn-sm" wire:click="createTemplate">
                            <i class="bi bi-pencil-square me-1"></i> Utwórz i otwórz edytor
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
