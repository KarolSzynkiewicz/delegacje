<div>
    <x-ui.errors />

    @if($action === '')
        <div class="d-flex flex-wrap gap-2">
            <x-ui.button variant="success" type="button" wire:click="startReceipt" action="add">
                Przyjmij
            </x-ui.button>
            @if($equipment->issuable)
                <x-ui.button variant="primary" type="button" wire:click="startIssue">
                    <i class="bi bi-box-arrow-up me-1"></i>
                    Wydaj
                </x-ui.button>
            @else
                <x-ui.button variant="primary" type="button" wire:click="startConsume">
                    <i class="bi bi-dash-square me-1"></i>
                    Rozchód
                </x-ui.button>
            @endif
            @if($canTransfer)
                <x-ui.button variant="primary" type="button" wire:click="startTransfer">
                    <i class="bi bi-arrow-left-right me-1"></i>
                    Przemieść
                </x-ui.button>
            @endif
            <x-ui.button variant="ghost" type="button" wire:click="startAdjustment">
                <i class="bi bi-dash-circle me-1"></i>
                Odejmij
            </x-ui.button>
        </div>
    @else
        <p class="text-muted small mb-3">
            @if($isReceipt)
                Przyjęcie dolicza sztuki do wybranego magazynu. Wpisz ilości przy wariantach, które przyjmujesz — zapisze się to razem.
            @elseif($isIssue)
                Wydanie zdejmuje sztuki z półki i przekazuje je pracownikowi (powstaje ZW). Wpisz ilości przy wariantach, które wydajesz.
            @elseif($isTransfer)
                Przerzut zdejmuje sztuki w magazynie źródłowym i dolicza je w docelowym. Wpisz ilości przy wariantach, które przemieszczasz.
            @elseif($isConsume)
                Rozchód zdejmuje stan od razu. Najpierw wybierz przeznaczenie (projekt, dom, auto albo osobę), potem konkretną pozycję i ilości.
            @else
                Korekta spisuje sztuki z wybranego magazynu (braki, zniszczenie). Nie zejdzie poniżej rezerwacji.
            @endif
        </p>

        @if($isTransfer)
            <div class="row g-3 mb-3">
                <div class="col-md-6" style="max-width:22rem;">
                    <label class="form-label" for="stock-movement-warehouse">Z magazynu</label>
                    <select
                        id="stock-movement-warehouse"
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
                <div class="col-md-6" style="max-width:22rem;">
                    <label class="form-label" for="stock-movement-target-warehouse">Do magazynu</label>
                    <select
                        id="stock-movement-target-warehouse"
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
        @else
            <div class="mb-3" style="max-width:22rem;">
                <label class="form-label" for="stock-movement-warehouse">Magazyn</label>
                <select
                    id="stock-movement-warehouse"
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
        @endif

        @if($isIssue)
            <div class="mb-3">
                <label class="form-label">Pracownik</label>
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
        @endif

        @if($isConsume)
            @include('livewire.partials.consumption-destination-picker')
        @endif

        @if($isTransfer)
            <div class="mb-3">
                <label class="form-label">Zdarzenie logistyczne <span class="text-muted fw-normal">(opcjonalnie)</span></label>
                @if($logisticsEventId)
                    <div class="d-flex align-items-center justify-content-between gap-2 border rounded px-3 py-2">
                        <span class="fw-semibold">{{ $selectedLogisticsEventLabel ?? $logisticsEventSearch }}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearLogisticsEvent">Zmień</button>
                    </div>
                @else
                    <input
                        type="search"
                        class="form-control @error('logisticsEventId') is-invalid @enderror"
                        placeholder="Szukaj wyjazdu, zjazdu albo transferu…"
                        autocomplete="off"
                        wire:model.live.debounce.300ms="logisticsEventSearch"
                    >
                    @if(filled($logisticsEventSearch))
                        <div class="border rounded mt-1" style="max-height:12rem;overflow:auto;">
                            @forelse($logisticsEventMatches as $event)
                                <button type="button" class="dropdown-item" wire:click="selectLogisticsEvent({{ $event['id'] }})">
                                    {{ $event['label'] }}
                                </button>
                            @empty
                                <div class="px-3 py-2 small text-muted">Brak wyników.</div>
                            @endforelse
                        </div>
                    @endif
                @endif
                @error('logisticsEventId')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        @endif

        @if($isReceipt || $isAdjustment)
            <div class="mb-3" style="max-width:22rem;">
                <label class="form-label" for="stock-movement-reason">Powód</label>
                <select
                    id="stock-movement-reason"
                    class="form-select @error('reason') is-invalid @enderror"
                    wire:model="reason"
                >
                    <option value="">Wybierz…</option>
                    @foreach($reasonOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endif

        @if($usesQuantityTable)
            <div class="mb-3">
                <label class="form-label">{{ $equipment->hasVariants() ? ($equipment->variant_label ?: 'Wariant') : 'Ilość' }}</label>
                @error('lines')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ $equipment->hasVariants() ? ($equipment->variant_label ?: 'Wariant') : 'Pozycja' }}</th>
                                <th class="text-nowrap">Na półce</th>
                                @if($isTransfer)
                                    <th class="text-nowrap">W celu</th>
                                @endif
                                <th style="width:8rem;">Ilość</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($variantRows as $row)
                                <tr wire:key="stock-line-{{ $row['index'] }}">
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-nowrap">
                                        {{ $row['on_hand'] }}
                                        @if(($isIssue || $isTransfer) && $row['reserved'] > 0)
                                            <span class="small text-muted">· dostępne {{ $row['available'] }}</span>
                                        @endif
                                    </td>
                                    @if($isTransfer)
                                        <td class="text-nowrap">{{ $row['target_on_hand'] ?? '—' }}</td>
                                    @endif
                                    <td>
                                        <input
                                            type="number"
                                            min="0"
                                            class="form-control @error('lines.'.$row['index'].'.quantity') is-invalid @enderror"
                                            wire:model="lines.{{ $row['index'] }}.quantity"
                                            aria-label="Ilość {{ $row['label'] }}"
                                        >
                                        @error('lines.'.$row['index'].'.quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            @if($equipment->hasVariants())
                <div class="mb-3">
                    <label class="form-label" for="stock-movement-variant">{{ $equipment->variant_label ?: 'Wariant' }}</label>
                    <select
                        id="stock-movement-variant"
                        class="form-select @error('variantId') is-invalid @enderror"
                        wire:model.live="variantId"
                    >
                        <option value="">Wybierz…</option>
                        @foreach($equipment->variants as $variant)
                            <option value="{{ $variant->id }}">
                                {{ $variant->kind_label }} — {{ $variant->quantityIn($warehouse) }} na półce
                            </option>
                        @endforeach
                    </select>
                    @error('variantId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif

            @if($selectedVariant)
                <p class="small text-muted mb-3">
                    {{ $warehouse->display_name }}: na półce <strong>{{ $onHand }}</strong>
                    @if($reserved > 0)
                        · zarezerwowane {{ $reserved }} · dostępne {{ $available }}
                    @endif
                </p>
            @endif

            <div class="mb-3">
                <label class="form-label" for="stock-movement-qty">Ilość</label>
                <input
                    id="stock-movement-qty"
                    type="number"
                    min="1"
                    class="form-control @error('quantity') is-invalid @enderror"
                    wire:model="quantity"
                    style="max-width:10rem;"
                >
                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endif

        <div class="mb-4">
            <label class="form-label" for="stock-movement-notes">Uwagi</label>
            <textarea
                id="stock-movement-notes"
                class="form-control @error('notes') is-invalid @enderror"
                rows="2"
                wire:model="notes"
                placeholder="{{ $isReceipt ? 'np. FV 12/2026' : ($isIssue ? 'np. wydanie na projekt X' : ($isTransfer ? 'np. MM na budowę' : ($isConsume ? 'np. opony do busa, zużycie na projekcie' : 'np. spis z natury 08.2026'))) }}"
            ></textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex justify-content-end align-items-center gap-2">
            <x-ui.button variant="ghost" type="button" wire:click="cancel" action="cancel">
                Anuluj
            </x-ui.button>
            <x-ui.button
                variant="{{ $isAdjustment ? 'warning' : 'primary' }}"
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                action="save"
            >
                @if($isReceipt)
                    Przyjmij
                @elseif($isIssue)
                    Wydaj
                @elseif($isTransfer)
                    Przemieść
                @elseif($isConsume)
                    Zaksięguj rozchód
                @else
                    Spisz
                @endif
            </x-ui.button>
        </div>
    @endif
</div>
