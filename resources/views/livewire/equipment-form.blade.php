<div>
    <x-ui.errors />

    @if($warehouse)
        <p class="text-muted small mb-3">
            Stan zapiszesz w magazynie <strong>{{ $warehouse->display_name }}</strong>.
            Katalog (nazwa, warianty, wydawalność) jest wspólny dla wszystkich magazynów.
        </p>
    @endif

    <div class="mb-3">
        <label class="form-label" for="equipment-name">Nazwa <span class="text-danger">*</span></label>
        <input
            id="equipment-name"
            type="text"
            class="form-control @error('name') is-invalid @enderror"
            wire:model="name"
        >
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="equipment-description">Opis</label>
        <textarea
            id="equipment-description"
            class="form-control @error('description') is-invalid @enderror"
            rows="2"
            wire:model="description"
            placeholder="Krótki opis, widoczny na liście magazynu"
        ></textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="equipment-category">Kategoria</label>
        <input
            id="equipment-category"
            type="text"
            class="form-control @error('category') is-invalid @enderror"
            placeholder="np. Odzież BHP, Ochrona oczu, Części"
            wire:model="category"
        >
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="row mb-3">
        <div class="col-md-8 mb-3 mb-md-0">
            <label class="form-label" for="equipment-unit-cost">Koszt jednostkowy</label>
            <input
                id="equipment-unit-cost"
                type="number"
                step="0.01"
                min="0"
                class="form-control @error('unit_cost') is-invalid @enderror"
                wire:model="unit_cost"
            >
            @error('unit_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="equipment-currency">Waluta</label>
            <select
                id="equipment-currency"
                class="form-select @error('currency') is-invalid @enderror"
                wire:model="currency"
            >
                @foreach(\App\Enums\Currency::cases() as $c)
                    <option value="{{ $c->value }}">{{ $c->label() }}</option>
                @endforeach
            </select>
            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-3">
        <div class="form-check">
            <input id="equipment-issuable" type="checkbox" class="form-check-input" wire:model.live="issuable">
            <label class="form-check-label" for="equipment-issuable">Wydawalny pracownikom</label>
        </div>
        <small class="form-text text-muted">Wyłącz dla zapasów magazynowych, których nie wydajesz ludziom (np. opony zamienne).</small>
    </div>

    <div class="mb-4">
        <div class="form-check">
            <input
                id="equipment-returnable"
                type="checkbox"
                class="form-check-input"
                wire:model="returnable"
                @if(!$issuable) disabled @endif
            >
            <label class="form-check-label" for="equipment-returnable">Zwracalny</label>
        </div>
        <small class="form-text text-muted">
            @if($issuable)
                Pracownik może oddać pozycję, zgłosić uszkodzenie albo zgubienie.
            @else
                Zwrot dotyczy tylko sprzętu wydawanego pracownikom.
            @endif
        </small>
        @error('returnable') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-4">
        <div class="form-check">
            <input id="equipment-has-variants" type="checkbox" class="form-check-input" wire:model.live="has_variants">
            <label class="form-check-label" for="equipment-has-variants">Ten sprzęt ma warianty</label>
        </div>
        <small class="form-text text-muted">Np. rozmiar spodni, rodzaj filtra. Jeśli nie — to jedna pozycja ze stanem.</small>
        @error('has_variants') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    @if($has_variants)
        <div class="mb-3">
            <label class="form-label" for="equipment-variant-label">Nazwa wariantu <span class="text-danger">*</span></label>
            <input
                id="equipment-variant-label"
                type="text"
                class="form-control @error('variant_label') is-invalid @enderror"
                placeholder="np. Rozmiar, Filtr"
                wire:model.live="variant_label"
            >
            @error('variant_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Warianty</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addVariant">
                    <i class="bi bi-plus-lg me-1"></i> Dodaj wariant
                </button>
            </div>
            @error('variants')
                <div class="text-danger small mb-2">{{ $message }}</div>
            @enderror

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ $variant_label !== '' ? $variant_label : 'Wariant' }}</th>
                            <th>Ilość w tym magazynie</th>
                            <th>Minimalna ilość</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($variants as $index => $variant)
                            <tr wire:key="variant-row-{{ $variant['id'] ?? 'new-'.$index }}">
                                <td>
                                    <input
                                        type="text"
                                        class="form-control @error('variants.'.$index.'.value') is-invalid @enderror"
                                        wire:model="variants.{{ $index }}.value"
                                        placeholder="np. M, UV400"
                                    >
                                    @error('variants.'.$index.'.value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td style="width: 9rem;">
                                    <input
                                        type="number"
                                        min="0"
                                        class="form-control @error('variants.'.$index.'.quantity_in_stock') is-invalid @enderror"
                                        wire:model="variants.{{ $index }}.quantity_in_stock"
                                    >
                                    @error('variants.'.$index.'.quantity_in_stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td style="width: 9rem;">
                                    <input
                                        type="number"
                                        min="0"
                                        class="form-control @error('variants.'.$index.'.min_quantity') is-invalid @enderror"
                                        wire:model="variants.{{ $index }}.min_quantity"
                                    >
                                    @error('variants.'.$index.'.min_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td class="text-end" style="width: 3rem;">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        wire:click="removeVariant({{ $index }})"
                                        @if(count($variants) <= 1) disabled @endif
                                        title="Usuń wariant"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <label class="form-label" for="equipment-qty">Ilość w tym magazynie</label>
                <input
                    id="equipment-qty"
                    type="number"
                    min="0"
                    class="form-control @error('variants.0.quantity_in_stock') is-invalid @enderror"
                    wire:model="variants.0.quantity_in_stock"
                >
                @error('variants.0.quantity_in_stock')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="equipment-min">Minimalna ilość</label>
                <input
                    id="equipment-min"
                    type="number"
                    min="0"
                    class="form-control @error('variants.0.min_quantity') is-invalid @enderror"
                    wire:model="variants.0.min_quantity"
                >
                @error('variants.0.min_quantity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-end align-items-center gap-2">
        <x-ui.button
            variant="ghost"
            href="{{ $equipmentId ? route('equipment.show', ['equipment' => $equipmentId, 'warehouse_id' => $warehouseId]) : route('equipment.index', ['warehouse_id' => $warehouseId]) }}"
            action="cancel"
        >
            Anuluj
        </x-ui.button>
        <x-ui.button
            variant="primary"
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            action="save"
        >
            Zapisz
        </x-ui.button>
    </div>
</div>
