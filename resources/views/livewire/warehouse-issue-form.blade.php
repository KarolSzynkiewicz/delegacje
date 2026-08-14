<div
    x-data="{
        payload: null,
        start(event, type, variantId, kind) {
            this.payload = { type, variantId, kind };
            event.dataTransfer.setData('text/plain', JSON.stringify(this.payload));
            event.dataTransfer.effectAllowed = 'move';
        },
        over(event, zone) {
            const p = this.payload;
            if (! p) return;
            const ok = (zone === 'stock' && p.type === 'cart') || (p.type === 'stock' && zone === p.kind);
            if (ok) {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
            }
        },
        drop(event, zone) {
            event.preventDefault();
            let p = this.payload;
            try {
                p = JSON.parse(event.dataTransfer.getData('text/plain') || '{}');
            } catch (e) {}
            if (! p || ! p.variantId) return;
            if (zone === 'stock' && p.type === 'cart') {
                $wire.removeFromCart(Number(p.variantId));
            } else if (p.type === 'stock' && zone === p.kind) {
                $wire.addToCart(Number(p.variantId), p.kind);
            }
            this.payload = null;
        }
    }"
>
    @if($flashMessage)
        <x-ui.alert variant="success" class="mb-3">
            {{ $flashMessage }}
        </x-ui.alert>
    @endif

    <x-ui.errors />

    @error('lines')
        <div class="text-danger small mb-3">{{ $message }}</div>
    @enderror

    <style>
        .warehouse-issue-board {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .warehouse-issue-board__lanes {
            min-height: 22rem;
        }
        @media (min-width: 992px) {
            .warehouse-issue-board {
                display: grid;
                grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
                grid-template-rows: auto minmax(22rem, 1fr) auto;
                column-gap: 0;
                row-gap: .75rem;
                align-items: stretch;
            }
            .warehouse-issue-board__left-head { grid-column: 1; grid-row: 1; padding-right: 1.5rem; }
            .warehouse-issue-board__left-lanes {
                grid-column: 1;
                grid-row: 2;
                padding-right: 1.5rem;
                display: flex;
                flex-direction: column;
            }
            .warehouse-issue-board__right-head {
                grid-column: 2;
                grid-row: 1;
                padding-left: 2rem;
                border-left: 2px solid var(--glass-border, rgba(255,255,255,.18));
                background: rgba(255,255,255,.02);
            }
            .warehouse-issue-board__right-lanes {
                grid-column: 2;
                grid-row: 2;
                padding-left: 2rem;
                border-left: 2px solid var(--glass-border, rgba(255,255,255,.18));
                background: rgba(255,255,255,.02);
                display: flex;
                flex-direction: column;
            }
            .warehouse-issue-board__notes {
                grid-column: 2;
                grid-row: 3;
                padding-left: 2rem;
                border-left: 2px solid var(--glass-border, rgba(255,255,255,.18));
                background: rgba(255,255,255,.02);
            }
            .warehouse-issue-board__lanes { height: 100%; min-height: 22rem; }
        }
    </style>

    <div class="warehouse-issue-board">
        <div class="warehouse-issue-board__left-head">
            <label class="form-label" for="issue-warehouse">Magazyn</label>
            <select id="issue-warehouse" class="form-select" wire:model.live="warehouseId">
                @foreach($warehouses as $option)
                    <option value="{{ $option->id }}">
                        {{ $option->display_name }}{{ $option->is_default ? ' — siedziba' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div
            class="warehouse-issue-board__left-lanes"
            x-on:dragover="over($event, 'stock')"
            x-on:drop="drop($event, 'stock')"
        >
            <div class="row g-2 warehouse-issue-board__lanes h-100">
                <div class="col-6 h-100">
                    @include('livewire.partials.warehouse-issue-lane', [
                        'title' => 'Zwracalny',
                        'cards' => $returnableStock,
                        'kind' => 'returnable',
                        'empty' => 'Brak zwracalnego sprzętu.',
                        'side' => 'stock',
                    ])
                </div>
                <div class="col-6 h-100">
                    @include('livewire.partials.warehouse-issue-lane', [
                        'title' => 'Niezwracalny',
                        'cards' => $givenStock,
                        'kind' => 'given',
                        'empty' => 'Brak bezzwrotnego sprzętu.',
                        'side' => 'stock',
                    ])
                </div>
            </div>
        </div>

        <div class="warehouse-issue-board__right-head">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Wydanie dla <span class="text-danger">*</span></label>
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
                                    <button type="button" class="dropdown-item" wire:click="selectEmployee({{ $employee->id }})">
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
                <div class="col-md-4">
                    <label class="form-label" for="issue-date">Data wydania <span class="text-danger">*</span></label>
                    <input id="issue-date" type="date" class="form-control @error('issueDate') is-invalid @enderror" wire:model="issueDate">
                    @error('issueDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="warehouse-issue-board__right-lanes">
            <div class="row g-2 warehouse-issue-board__lanes h-100">
                <div class="col-6 h-100">
                    @include('livewire.partials.warehouse-issue-lane', [
                        'title' => 'Do zwrotu',
                        'cards' => $returnableCart,
                        'kind' => 'returnable',
                        'empty' => 'Upuść zwracalne tutaj.',
                        'side' => 'cart',
                    ])
                </div>
                <div class="col-6 h-100">
                    @include('livewire.partials.warehouse-issue-lane', [
                        'title' => 'Do wydania bezzwrotnie',
                        'cards' => $givenCart,
                        'kind' => 'given',
                        'empty' => 'Upuść bezzwrotne tutaj.',
                        'side' => 'cart',
                    ])
                </div>
            </div>
        </div>

        <div class="warehouse-issue-board__notes">
            <label class="form-label" for="issue-notes">Notatki</label>
            <textarea id="issue-notes" class="form-control @error('notes') is-invalid @enderror" rows="2" wire:model="notes"></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
        <x-ui.button variant="ghost" href="{{ route('equipment.tab.issues') }}" action="cancel">
            Anuluj
        </x-ui.button>
        <button type="button" class="btn btn-outline-primary" wire:click="saveAndNext" wire:loading.attr="disabled">
            Wydaj i następna osoba
        </button>
        <x-ui.button variant="primary" type="button" wire:click="save" wire:loading.attr="disabled" action="save">
            Wydaj
        </x-ui.button>
    </div>
</div>
