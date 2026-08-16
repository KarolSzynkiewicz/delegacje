<x-ui.card label="Transfer międzymagazynowy" class="mb-0">
    @if(session('warehouseTransferSuccess'))
        <x-alert type="success" dismissible icon="check-circle" class="mb-3">
            {{ session('warehouseTransferSuccess') }}
        </x-alert>
    @endif

    @error('action')
        <div class="text-danger small mb-3">{{ $message }}</div>
    @enderror

    @if($groups->isNotEmpty())
        <div class="d-flex flex-column gap-3">
            @foreach($groups as $group)
                <div class="rounded-3 p-3 border" style="border-color: rgba(255,255,255,0.08) !important; background: rgba(255,255,255,0.03);">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                        <div class="fw-semibold">
                            {{ $group['from'] }}
                            <span class="mx-1 text-muted">→</span>
                            {{ $group['to'] }}
                        </div>
                        <div class="small text-muted">
                            {{ $group['total_qty'] }} szt.
                            @if($group['happened_at'])
                                · {{ $group['happened_at'] }}
                            @endif
                        </div>
                    </div>
                    <div class="small">
                        @foreach($group['lines'] as $line)
                            <div class="d-flex justify-content-between gap-3">
                                <span>
                                    @if($line['href'])
                                        <a href="{{ $line['href'] }}" class="text-decoration-none">{{ $line['name'] }}</a>
                                    @else
                                        {{ $line['name'] }}
                                    @endif
                                </span>
                                <span class="text-nowrap">{{ $line['quantity'] }} szt.</span>
                            </div>
                        @endforeach
                    </div>
                    @if($group['notes'] || $group['creator'])
                        <div class="small text-muted mt-2 pt-2 border-top" style="border-color: rgba(255,255,255,0.08) !important;">
                            @if($group['notes'])
                                {{ $group['notes'] }}
                            @endif
                            @if($group['notes'] && $group['creator'])
                                <span class="mx-1">·</span>
                            @endif
                            @if($group['creator'])
                                {{ $group['creator'] }}
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif(! $adding && $canAdd)
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <p class="text-muted mb-0 small">{{ $emptyHint }}</p>
            <button type="button" class="btn btn-sm btn-outline-info flex-shrink-0" wire:click="startAdding">
                <i class="bi bi-plus-lg me-1"></i>Dodaj przemieszczenie
            </button>
        </div>
    @elseif(! $adding)
        <p class="text-muted mb-0 small">{{ $emptyHint }}</p>
    @endif

    @if($adding)
        <div class="{{ $groups->isNotEmpty() ? 'mt-3 pt-3 border-top' : '' }}" style="border-color: rgba(255,255,255,0.08) !important;">
            <x-ui.errors />

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="event-wh-from">Z magazynu</label>
                    <select
                        id="event-wh-from"
                        class="form-select @error('warehouseId') is-invalid @enderror"
                        wire:model.live="warehouseId"
                    >
                        @foreach($warehouses as $option)
                            <option value="{{ $option->id }}">
                                {{ $option->display_name }}{{ $option->is_default ? ' — siedziba' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouseId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="event-wh-to">Do magazynu</label>
                    <select
                        id="event-wh-to"
                        class="form-select @error('targetWarehouseId') is-invalid @enderror"
                        wire:model.live="targetWarehouseId"
                    >
                        <option value="">Wybierz…</option>
                        @foreach($warehouses as $option)
                            @if($option->id !== (int) $warehouseId)
                                <option value="{{ $option->id }}">
                                    {{ $option->display_name }}{{ $option->is_default ? ' — siedziba' : '' }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('targetWarehouseId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="event-wh-notes">Uwagi</label>
                <textarea
                    id="event-wh-notes"
                    class="form-control @error('notes') is-invalid @enderror"
                    rows="2"
                    wire:model="notes"
                    placeholder="np. MM na wyjazd"
                ></textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h6 class="mb-2">Pozycje</h6>
            @if($catalog->isEmpty())
                <p class="text-muted small">Brak pozycji w asortymencie.</p>
            @else
                <div class="row g-2 align-items-end mb-3">
                    <div class="{{ $selectedType?->hasVariants() ? 'col-md-5' : 'col-md-8' }}">
                        <label class="form-label small">Pozycja</label>
                        @if($addEquipmentId)
                            <div class="d-flex align-items-center justify-content-between gap-2 border rounded px-3 py-2">
                                <span>{{ $equipmentSearch }}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearEquipment">Zmień</button>
                            </div>
                        @else
                            <input
                                type="search"
                                class="form-control @error('addEquipmentId') is-invalid @enderror"
                                placeholder="Szukaj pozycji…"
                                autocomplete="off"
                                wire:model.live.debounce.300ms="equipmentSearch"
                            >
                            @if(filled($equipmentSearch))
                                <div class="border rounded mt-1" style="max-height:12rem;overflow:auto;">
                                    @forelse($equipmentMatches as $type)
                                        <button type="button" class="dropdown-item" wire:click="selectEquipment({{ $type->id }})">
                                            {{ $type->name }} ({{ $type->availableIn($fromWarehouse) }})
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
                            <select class="form-select @error('addVariantId') is-invalid @enderror" wire:model="addVariantId">
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
                        <input type="number" min="1" class="form-control @error('addQuantity') is-invalid @enderror" wire:model="addQuantity">
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
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pozycja</th>
                                <th>Ilość</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lineRows as $row)
                                <tr wire:key="event-wh-line-{{ $row['variant']->id }}">
                                    <td>{{ $row['variant']->display_name }}</td>
                                    <td>{{ $row['quantity'] }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeLine({{ $row['index'] }})" title="Usuń">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Dodaj rzeczy do przemieszczenia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
                <x-ui.button variant="ghost" type="button" wire:click="cancelAdding" action="cancel">
                    Anuluj
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    action="save"
                >
                    Przemieść
                </x-ui.button>
            </div>
        </div>
    @elseif($groups->isNotEmpty() && $canAdd)
        <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center gap-2" style="border-color: rgba(255,255,255,0.08) !important;">
            <span class="small text-muted">Przerzut sprzętu między magazynami powiążesz z tym zdarzeniem.</span>
            <button type="button" class="btn btn-sm btn-outline-info" wire:click="startAdding">
                <i class="bi bi-plus-lg me-1"></i>Dodaj przemieszczenie
            </button>
        </div>
    @endif
</x-ui.card>
