<div>
    {{-- Mode toggle --}}
    <div class="mb-3">
        <div class="d-flex gap-2">
            <button
                type="button"
                wire:click="setMode('existing')"
                class="btn btn-sm {{ $mode === 'existing' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                Wybierz z listy
            </button>
            <button
                type="button"
                wire:click="setMode('new')"
                class="btn btn-sm {{ $mode === 'new' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                Nowy warsztat (wyszukaj)
            </button>
        </div>
    </div>

    @if($mode === 'existing')
        {{-- Existing location dropdown --}}
        <div class="mb-3">
            <x-input-label for="location_id" value="Lokalizacja warsztatu" />
            <select
                id="location_id"
                wire:model="location_id"
                name="location_id"
                class="form-select mt-1 @error('location_id') is-invalid @enderror"
            >
                <option value="">-- Brak / Nieznana --</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ $location_id == $loc->id ? 'selected' : '' }}>
                        {{ $loc->name }}@if($loc->city), {{ $loc->city }}@endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('location_id')" class="mt-2" />
        </div>

        {{-- Hidden fields to clear new workshop data --}}
        <input type="hidden" name="workshop_name" value="">
        <input type="hidden" name="workshop_address" value="">
        <input type="hidden" name="workshop_city" value="">
        <input type="hidden" name="workshop_postal_code" value="">
        <input type="hidden" name="workshop_country" value="">
        <input type="hidden" name="workshop_lat" value="">
        <input type="hidden" name="workshop_lng" value="">

    @else
        {{-- New workshop geo search --}}
        <input type="hidden" name="location_id" value="">

        <div class="mb-3">
            <x-input-label for="workshop_search" value="Wyszukaj warsztat" />
            <div class="d-flex gap-2">
                <div class="flex-grow-1 position-relative">
                    <input
                        type="text"
                        id="workshop_search"
                        wire:model="searchQuery"
                        wire:keydown.enter.prevent="search"
                        class="form-control"
                        placeholder="Wpisz nazwę lub adres warsztatu (min. 3 znaki)..."
                        autocomplete="off"
                    />
                    <div wire:loading wire:target="search" class="position-absolute top-50 end-0 translate-middle-y pe-3">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="search"
                    wire:loading.attr="disabled"
                    wire:target="search"
                    class="btn btn-primary"
                    style="min-width: 100px;"
                >
                    <span wire:loading.remove wire:target="search">Szukaj</span>
                    <span wire:loading wire:target="search">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Szukanie...
                    </span>
                </button>
            </div>

            @if($searchError)
                <div class="alert alert-danger mt-2 mb-0" style="background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: var(--text-main);">
                    <small>{{ $searchError }}</small>
                </div>
            @endif

            @if($showResults && !empty($searchResults))
                <div class="mt-2 rounded p-3" style="max-height: 320px; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--glass-border);">
                    <strong class="d-block mb-2" style="color: var(--text-main);">Znalezione miejsca ({{ count($searchResults) }}):</strong>
                    @foreach($searchResults as $index => $result)
                        <button
                            type="button"
                            wire:click="selectResult({{ $index }})"
                            class="btn btn-outline-secondary btn-sm w-100 text-start mb-2"
                            style="white-space: normal; color: var(--text-main); border-color: var(--glass-border); background: rgba(255,255,255,0.05);"
                        >
                            <strong>{{ $result['label'] }}</strong>
                            @if(!empty($result['city']) || !empty($result['country']))
                                <br>
                                <small class="text-muted">
                                    {{ implode(', ', array_filter([$result['city'] ?? null, $result['country'] ?? null])) }}
                                </small>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mb-3">
            <x-input-label for="workshop_name" value="Nazwa warsztatu" />
            <span class="text-danger">*</span>
            <input
                type="text"
                id="workshop_name"
                wire:model="workshop_name"
                name="workshop_name"
                class="form-control mt-1 @error('workshop_name') is-invalid @enderror"
                placeholder="np. ASO Volkswagen Warszawa"
                required
            />
            <x-input-error :messages="$errors->get('workshop_name')" class="mt-2" />
        </div>

        <div class="row">
            <div class="col-md-8 mb-3">
                <x-input-label for="workshop_address" value="Adres" />
                <input
                    type="text"
                    id="workshop_address"
                    wire:model="workshop_address"
                    name="workshop_address"
                    class="form-control mt-1"
                    placeholder="ul. Przykładowa 1"
                />
            </div>
            <div class="col-md-4 mb-3">
                <x-input-label for="workshop_postal_code" value="Kod pocztowy" />
                <input
                    type="text"
                    id="workshop_postal_code"
                    wire:model="workshop_postal_code"
                    name="workshop_postal_code"
                    class="form-control mt-1"
                />
            </div>
        </div>

        <div class="mb-3">
            <x-input-label for="workshop_city" value="Miasto" />
            <input
                type="text"
                id="workshop_city"
                wire:model="workshop_city"
                name="workshop_city"
                class="form-control mt-1"
            />
        </div>

        {{-- Hidden coordinate fields synced via JS --}}
        <input type="hidden" name="workshop_lat" id="workshop_lat_hidden" value="{{ $workshop_lat ?? '' }}">
        <input type="hidden" name="workshop_lng" id="workshop_lng_hidden" value="{{ $workshop_lng ?? '' }}">
        <input type="hidden" name="workshop_country" id="workshop_country_hidden" value="{{ $workshop_country ?? '' }}">

        @if($workshop_lat)
            <small class="text-muted">
                <i class="bi bi-geo-alt-fill text-success"></i>
                Współrzędne: {{ number_format((float)$workshop_lat, 6) }}, {{ number_format((float)$workshop_lng, 6) }}
            </small>
        @endif
    @endif
</div>

@script
<script>
    function syncWorkshopCoords() {
        const latField = document.getElementById('workshop_lat_hidden');
        const lngField = document.getElementById('workshop_lng_hidden');
        const countryField = document.getElementById('workshop_country_hidden');

        if (latField) {
            const lat = @this.get('workshop_lat');
            latField.value = (lat !== null && lat !== undefined) ? lat : '';
        }
        if (lngField) {
            const lng = @this.get('workshop_lng');
            lngField.value = (lng !== null && lng !== undefined) ? lng : '';
        }
        if (countryField) {
            const country = @this.get('workshop_country');
            countryField.value = country || '';
        }
    }

    $wire.on('$refresh', () => syncWorkshopCoords());

    document.addEventListener('DOMContentLoaded', () => {
        syncWorkshopCoords();

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', () => {
                // Sync wire:model text fields to form inputs before submit
                const fields = ['workshop_name', 'workshop_address', 'workshop_city', 'workshop_postal_code'];
                fields.forEach(field => {
                    const el = form.querySelector(`[name="${field}"]`);
                    if (el && !el.hasAttribute('wire:model')) {
                        const val = @this.get(field);
                        el.value = val || '';
                    }
                });
                syncWorkshopCoords();
            });
        }
    });
</script>
@endscript
