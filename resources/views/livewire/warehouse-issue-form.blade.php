<div>
    @if($flashMessage)
        <x-ui.alert variant="success" class="mb-3">
            {{ $flashMessage }}
        </x-ui.alert>
    @endif

    <x-ui.errors />

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="mb-3">
                <label class="form-label">Pracownik <span class="text-danger">*</span></label>
                @if($employeeId)
                    <div class="d-flex align-items-center justify-content-between gap-2 border rounded px-3 py-2">
                        <span class="fw-semibold">{{ $employeeSearch }}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearEmployee">Zmień</button>
                    </div>
                @else
                    <input
                        type="search"
                        class="form-control @error('employeeId') is-invalid @enderror"
                        placeholder="Szukaj pracownika…"
                        autocomplete="off"
                        wire:model.live.debounce.300ms="employeeSearch"
                    >
                    @if(filled($employeeSearch))
                        <div class="border rounded mt-1" style="max-height:12rem;overflow:auto;">
                            @forelse($employeeMatches as $employee)
                                <button
                                    type="button"
                                    class="dropdown-item"
                                    wire:click="selectEmployee({{ $employee->id }})"
                                >
                                    {{ $employee->full_name }}
                                </button>
                            @empty
                                <div class="px-3 py-2 small text-muted">Brak wyników.</div>
                            @endforelse
                        </div>
                    @endif
                @endif
                @error('employeeId')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Przypisanie do projektu (opcjonalne)</label>
                <select
                    class="form-select @error('projectAssignmentId') is-invalid @enderror"
                    wire:model="projectAssignmentId"
                    @if(!$employeeId) disabled @endif
                >
                    <option value="">Brak</option>
                    @foreach($assignments as $assignment)
                        <option value="{{ $assignment->id }}">
                            {{ $assignment->project->name ?? 'Projekt' }}
                        </option>
                    @endforeach
                </select>
                @error('projectAssignmentId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label" for="issue-date">Data wydania <span class="text-danger">*</span></label>
                    <input id="issue-date" type="date" class="form-control @error('issueDate') is-invalid @enderror" wire:model="issueDate">
                    @error('issueDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="expected-return-date">Oczekiwana data zwrotu</label>
                    <input id="expected-return-date" type="date" class="form-control @error('expectedReturnDate') is-invalid @enderror" wire:model="expectedReturnDate" @disabled($this->isGivenMode())>
                    @error('expectedReturnDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="form-text text-muted">
                        {{ $this->isGivenMode() ? 'Wydanie bezzwrotne — bez zwrotu do magazynu.' : 'Tylko dla pozycji zwracalnych.' }}
                    </small>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="issue-notes">Notatki</label>
                <textarea id="issue-notes" class="form-control @error('notes') is-invalid @enderror" rows="2" wire:model="notes"></textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h6 class="mb-2">{{ $this->isGivenMode() ? 'Dodaj pozycję bezzwrotną' : 'Dodaj pozycję do zwrotu' }}</h6>
            <div class="row g-2 align-items-end mb-3">
                <div class="{{ $selectedType?->hasVariants() ? 'col-md-5' : 'col-md-8' }}">
                    <label class="form-label small">Typ</label>
                    @if($addEquipmentId)
                        <div class="d-flex align-items-center justify-content-between gap-2 border rounded px-3 py-2">
                            <span>{{ $equipmentSearch }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearEquipment">Zmień</button>
                        </div>
                    @else
                        <input
                            type="search"
                            class="form-control @error('addEquipmentId') is-invalid @enderror"
                            placeholder="Szukaj typu…"
                            autocomplete="off"
                            wire:model.live.debounce.300ms="equipmentSearch"
                        >
                        @if(filled($equipmentSearch))
                            <div class="border rounded mt-1" style="max-height:12rem;overflow:auto;">
                                @forelse($equipmentMatches as $type)
                                    <button type="button" class="dropdown-item" wire:click="selectEquipment({{ $type->id }})">
                                        {{ $type->name }}
                                    </button>
                                @empty
                                    <div class="px-3 py-2 small text-muted">Brak wyników.</div>
                                @endforelse
                            </div>
                        @endif
                    @endif
                    @error('addEquipmentId')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                @if($selectedType?->hasVariants())
                <div class="col-md-4">
                    <label class="form-label small">{{ $selectedType->variant_label ?: 'Wariant' }}</label>
                    <select
                        class="form-select @error('addVariantId') is-invalid @enderror"
                        wire:model="addVariantId"
                    >
                        <option value="">Wybierz wariant</option>
                        @foreach($selectedVariants as $variant)
                            <option value="{{ $variant->id }}">
                                {{ $variant->kind_label }} ({{ $this->remainingFor($variant->id) }})
                            </option>
                        @endforeach
                    </select>
                    @error('addVariantId')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small">Ilość</label>
                    <input
                        type="number"
                        min="1"
                        class="form-control @error('addQuantity') is-invalid @enderror"
                        wire:model="addQuantity"
                    >
                    @error('addQuantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-primary w-100" wire:click="addLine" title="Dodaj">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
            @error('lines')
                <div class="text-danger small mb-3">{{ $message }}</div>
            @enderror

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Typ</th>
                            @if(collect($lineRows)->contains(fn ($row) => $row['variant']->equipment?->hasVariants()))
                                <th>Wariant</th>
                            @endif
                            <th style="width: 7rem;">Ilość</th>
                            <th>Zostanie</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lineRows as $row)
                            <tr wire:key="issue-line-{{ $row['variant']->id }}">
                                <td class="fw-medium">{{ $row['variant']->equipment?->name }}</td>
                                @if(collect($lineRows)->contains(fn ($r) => $r['variant']->equipment?->hasVariants()))
                                    <td>{{ $row['variant']->equipment?->hasVariants() ? $row['variant']->kind_label : '—' }}</td>
                                @endif
                                <td>
                                    <input
                                        type="number"
                                        min="1"
                                        max="{{ $row['stock'] }}"
                                        class="form-control form-control-sm"
                                        value="{{ $row['quantity'] }}"
                                        wire:change="updateLineQuantity({{ $row['index'] }}, $event.target.value)"
                                    >
                                </td>
                                <td>
                                    {{ $row['remaining_after'] }}
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        wire:click="removeLine({{ $row['index'] }})"
                                        title="Usuń"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Brak pozycji. Dodaj rzeczy, które wyjeżdżają z tą osobą.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="border rounded p-3 h-100" style="background: rgba(0,0,0,0.04);">
                <h6 class="mb-3">Stan magazynu po tym wydaniu</h6>
                @if(count($stockPreview) === 0)
                    <p class="text-muted small mb-0">Dodaj pozycje, żeby zobaczyć jak zmieni się stan.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Pozycja</th>
                                    <th class="text-end">Teraz</th>
                                    <th class="text-end">Wydanie</th>
                                    <th class="text-end">Zostanie</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockPreview as $preview)
                                    <tr>
                                        <td>{{ $preview['variant']->display_name }}</td>
                                        <td class="text-end">{{ $preview['stock'] }}</td>
                                        <td class="text-end">-{{ $preview['in_cart'] }}</td>
                                        <td class="text-end fw-semibold {{ $preview['remaining'] === 0 ? 'text-danger' : '' }}">
                                            {{ $preview['remaining'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
        <x-ui.button
            variant="ghost"
            href="{{ route('equipment.tab.issues') }}"
            action="cancel"
        >
            Anuluj
        </x-ui.button>
        <button
            type="button"
            class="btn btn-outline-primary"
            wire:click="saveAndNext"
            wire:loading.attr="disabled"
        >
            {{ $this->isGivenMode() ? 'Wydaj bezzwrotnie i następna osoba' : 'Wydaj i następna osoba' }}
        </button>
        <x-ui.button
            variant="primary"
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            action="save"
        >
            {{ $this->isGivenMode() ? 'Wydaj bezzwrotnie' : 'Wydaj do zwrotu' }}
        </x-ui.button>
    </div>
</div>
