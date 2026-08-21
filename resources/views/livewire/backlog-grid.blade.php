<div>
    @if($flash)
        <x-ui.alert variant="success" title="OK" dismissible class="mb-3">{{ $flash }}</x-ui.alert>
    @endif

    <div class="card border-0 mb-3">
        <div class="card-body py-3 d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small text-muted mb-1">Szukaj</label>
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Tytuł…">
            </div>
            <div>
                <label class="form-label small text-muted mb-1">Status</label>
                <select wire:model.live="status" class="form-select form-select-sm">
                    <option value="">Otwarte</option>
                    <option value="all">Wszystkie</option>
                    <option value="closed">Zamknięte</option>
                    <option value="pending">Oczekujące</option>
                    <option value="in_progress">W trakcie</option>
                    <option value="completed">Zakończone</option>
                </select>
            </div>
            <div>
                <label class="form-label small text-muted mb-1">Typ</label>
                <select wire:model.live="type" class="form-select form-select-sm">
                    <option value="">Wszystkie typy</option>
                    @foreach($types as $workType)
                        <option value="{{ $workType->value }}">{{ $workType->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label small text-muted mb-1">Grupuj</label>
                <select wire:model.live="groupBy" class="form-select form-select-sm">
                    <option value="">Bez grupowania</option>
                    <option value="type">Typ</option>
                    <option value="status">Status</option>
                    <option value="assignee">Osoba</option>
                </select>
            </div>
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" wire:model.live="myItemsOnly" id="bl-mine">
                <label class="form-check-label small" for="bl-mine">Tylko moje</label>
            </div>
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" wire:model.live="hideCallbacks" id="bl-cb">
                <label class="form-check-label small" for="bl-cb">Ukryj oddzwonienia</label>
            </div>
            <div class="ms-auto small text-muted">
                @if($items)
                    {{ $items->total() }} pozycji
                @elseif($groupedItems)
                    {{ $groupedItems->flatten()->count() }} pozycji
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th style="width:36px"></th>
                        <th>Typ</th>
                        <th>Tytuł</th>
                        <th>Osoba</th>
                        <th>Status</th>
                        <th>Termin</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if($groupedItems)
                        @foreach($groupedItems as $groupItems)
                            @php $first = $groupItems->first(); @endphp
                            <tr>
                                <td colspan="7" class="small fw-semibold text-muted pt-3">
                                    {{ $first ? $this->groupLabel($first) : '' }}
                                    <span class="ms-1 opacity-75">{{ $groupItems->count() }}</span>
                                </td>
                            </tr>
                            @foreach($groupItems as $item)
                                @include('livewire.partials.backlog-row', ['item' => $item])
                            @endforeach
                        @endforeach
                    @elseif($items && $items->count())
                        @foreach($items as $item)
                            @include('livewire.partials.backlog-row', ['item' => $item])
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Brak pozycji w backlogu.</td>
                        </tr>
                    @endif

                    @if($showAddRow)
                        <tr>
                            <td></td>
                            <td class="small text-muted">Zadanie</td>
                            <td>
                                <input type="text" wire:model="newTaskName" class="form-control form-control-sm" placeholder="Nazwa *" wire:keydown.enter="addTask">
                            </td>
                            <td>
                                <select wire:model="newTaskAssignedTo" class="form-select form-select-sm">
                                    <option value="">Ja</option>
                                    @foreach($allUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td></td>
                            <td>
                                <input type="date" wire:model="newTaskDueDate" class="form-control form-control-sm">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" wire:click="addTask">Dodaj</button>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center py-2">
            @if(!$showAddRow)
                <button type="button" class="btn btn-sm btn-link text-decoration-none" wire:click="$set('showAddRow', true)">
                    <i class="bi bi-plus-circle me-1"></i>Dodaj zadanie
                </button>
            @else
                <button type="button" class="btn btn-sm btn-link" wire:click="$set('showAddRow', false)">Anuluj</button>
            @endif
            @if($items)
                {{ $items->links() }}
            @endif
        </div>
    </div>
</div>
