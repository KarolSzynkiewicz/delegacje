<div>
    <x-ui.errors />

    <p class="text-muted small mb-3">
        Rozchód zdejmuje stan od razu — tylko pozycje niewydawalne. Najpierw wybierz przeznaczenie (projekt, dom, auto albo osobę), potem konkretną pozycję.
    </p>

    @include('livewire.partials.consumption-destination-picker')

    <div class="mb-4">
        <label class="form-label" for="consume-notes">Notatka</label>
        <textarea id="consume-notes" class="form-control @error('notes') is-invalid @enderror" rows="2" wire:model="notes" placeholder="np. opony do busa WZ 1234, zużycie na projekcie X"></textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <h6 class="mb-2">Pozycje niewydawalne</h6>
    @if($catalog->isEmpty())
        <p class="text-muted small">Brak pozycji niewydawalnych w katalogu. Przy dodawaniu sprzętu odznacz „Wydawalny pracownikom”.</p>
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
                                    {{ $type->name }} ({{ $type->quantityIn($warehouse) }})
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
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Pozycja</th>
                        <th>Ilość</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lineRows as $row)
                        <tr wire:key="consume-line-{{ $row['variant']->id }}">
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
                            <td colspan="3" class="text-muted">Dodaj rzeczy, które schodzą ze stanu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
        <x-ui.button
            variant="ghost"
            href="{{ route('equipment.tab.issues') }}"
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
            Zaksięguj rozchód
        </x-ui.button>
    </div>
</div>
