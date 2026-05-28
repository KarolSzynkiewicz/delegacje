<div>

    {{-- ===== LOKALIZACJA ===== --}}
    <div class="mb-4">
        <x-input-label value="Lokalizacja" />
        <span class="text-danger">*</span>

        {{-- Mode toggle --}}
        <div class="d-flex gap-2 mt-1 mb-3">
            <button
                type="button"
                wire:click="setLocationMode('existing')"
                class="btn btn-sm {{ $location_mode === 'existing' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                <i class="bi bi-list-ul me-1"></i> Wybierz z listy
            </button>
            <button
                type="button"
                wire:click="setLocationMode('new')"
                class="btn btn-sm {{ $location_mode === 'new' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                <i class="bi bi-geo-alt me-1"></i> Nowa / wyszukaj
            </button>
        </div>

        @if($location_mode === 'existing')
            {{-- ---- Existing location dropdown ---- --}}
            <div class="mb-3">
                <x-input-label for="location_id_select" value="Lokalizacja" />
                <select
                    id="location_id_select"
                    class="form-select mt-1 @error('location_id') is-invalid @enderror"
                    wire:change="selectExistingLocation($event.target.value)"
                >
                    <option value="">-- Wybierz lokalizację --</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ $location_id == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}@if($loc->city), {{ $loc->city }}@endif
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('location_id')" class="mt-2" />
            </div>

            {{-- Hidden fields for existing mode --}}
            <input type="hidden" name="location_id"   value="{{ $location_id }}">
            <input type="hidden" name="location_name" value="">
            <input type="hidden" name="address"       value="{{ $address }}">
            <input type="hidden" name="city"          value="{{ $city }}">
            <input type="hidden" name="postal_code"   value="{{ $postal_code }}">
            <input type="hidden" name="country"       value="{{ $country }}">
            <input type="hidden" name="latitude"      value="{{ $latitude }}">
            <input type="hidden" name="longitude"     value="{{ $longitude }}">

            @if($location_id)
                <div class="p-3 rounded mb-2" style="background: rgba(0,0,0,0.15); border: 1px solid var(--glass-border);">
                    <small style="color: var(--text-muted);">
                        <i class="bi bi-geo-alt-fill text-success me-1"></i>
                        <strong>{{ $location_name }}</strong>
                        @if($address) — {{ $address }}@endif
                        @if($city), {{ $city }}@endif
                        @if($latitude)
                            <span class="ms-2 text-muted">({{ number_format((float)$latitude, 5) }}, {{ number_format((float)$longitude, 5) }})</span>
                        @endif
                    </small>
                </div>
            @endif

        @else
            {{-- ---- New location via geo search ---- --}}
            <input type="hidden" name="location_id" value="">

            <div class="mb-3">
                <x-input-label for="accommodation_location_search" value="Wyszukaj miejsce (geo)" />
                <div class="d-flex gap-2">
                    <div class="flex-grow-1 position-relative">
                        <input
                            type="text"
                            id="accommodation_location_search"
                            wire:model="searchQuery"
                            wire:keydown.enter.prevent="search"
                            class="form-control"
                            placeholder="Wpisz nazwę lub adres (min. 3 znaki)…"
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
                            Szukanie…
                        </span>
                    </button>
                </div>

                @if($searchError)
                    <div class="alert alert-danger mt-2 mb-0" style="background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: var(--text-main);">
                        <small>{{ $searchError }}</small>
                    </div>
                @endif

                @if($showResults && !empty($searchResults))
                    <div class="mt-2 rounded p-3 accommodation-search-results" style="max-height: 320px; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--glass-border);">
                        <strong class="d-block mb-2" style="color: var(--text-main);">Znalezione miejsca ({{ count($searchResults) }}):</strong>
                        @foreach($searchResults as $index => $result)
                            <button
                                type="button"
                                wire:click="selectGeoResult({{ $index }})"
                                class="btn btn-outline-secondary btn-sm w-100 text-start mb-2"
                                style="white-space: normal; color: var(--text-main); border-color: var(--glass-border); background: rgba(255,255,255,0.05);"
                            >
                                <strong>{{ $result['label'] }}</strong>
                                @if(!empty($result['city']) || !empty($result['country']))
                                    <br><small class="text-muted">{{ implode(', ', array_filter([$result['city'] ?? null, $result['country'] ?? null])) }}</small>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mb-3">
                <x-input-label for="location_name" value="Nazwa lokalizacji" />
                <input
                    type="text"
                    id="location_name"
                    wire:model="location_name"
                    name="location_name"
                    class="form-control mt-1 @error('location_name') is-invalid @enderror"
                    placeholder="Domyślnie: nazwa mieszkania"
                />
                <x-input-error :messages="$errors->get('location_name')" class="mt-2" />
            </div>

            <div class="row">
                <div class="col-md-8 mb-3">
                    <x-input-label for="address" value="Adres" />
                    <input
                        type="text"
                        id="address"
                        wire:model="address"
                        name="address"
                        class="form-control mt-1 @error('address') is-invalid @enderror"
                        placeholder="ul. Przykładowa 1"
                    />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
                <div class="col-md-4 mb-3">
                    <x-input-label for="postal_code" value="Kod pocztowy" />
                    <input
                        type="text"
                        id="postal_code"
                        wire:model="postal_code"
                        name="postal_code"
                        class="form-control mt-1"
                    />
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <x-input-label for="city" value="Miasto" />
                    <input
                        type="text"
                        id="city"
                        wire:model="city"
                        name="city"
                        class="form-control mt-1"
                    />
                </div>
                <div class="col-md-6 mb-3">
                    <x-input-label for="country" value="Kraj" />
                    <select id="country" wire:model="country" name="country" class="form-select mt-1">
                        <option value="">-- Wybierz kraj --</option>
                        @foreach(\App\Enums\EuropeanCountry::sorted() as $c)
                            <option value="{{ $c->value }}" {{ $country === $c->value ? 'selected' : '' }}>
                                {{ $c->labelWithFlag() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Coordinates (hidden, filled by geo search) --}}
            <input type="hidden" name="latitude"  id="acc_lat_hidden"  value="{{ $latitude }}">
            <input type="hidden" name="longitude" id="acc_lng_hidden"  value="{{ $longitude }}">

            @if($latitude)
                <small class="text-muted d-block mb-3">
                    <i class="bi bi-geo-alt-fill text-success"></i>
                    Współrzędne: {{ number_format((float)$latitude, 6) }}, {{ number_format((float)$longitude, 6) }}
                </small>
            @endif
        @endif

        <p class="small text-muted mb-0 mt-3">
            Przy zapisie wybrana lub nowa lokalizacja otrzyma automatycznie cel <strong>Kwatera</strong> (mieszkanie może współdzielić ten sam adres z innymi celami lokalizacji).
        </p>
    </div>

    <hr class="my-4" style="border-color: var(--glass-border);">

    {{-- ===== DANE MIESZKANIA ===== --}}

    <div class="mb-3">
        <x-input-label for="acc_name" value="Nazwa mieszkania" />
        <span class="text-danger">*</span>
        <input
            type="text"
            id="acc_name"
            wire:model="name"
            name="name"
            class="form-control mt-1 @error('name') is-invalid @enderror"
            placeholder="np. Mieszkanie 1, Dom Kowalskiego"
            required
        />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="mb-3">
        <x-input-label for="capacity" value="Pojemność (liczba osób)" />
        <span class="text-danger">*</span>
        <input
            type="number"
            id="capacity"
            wire:model="capacity"
            name="capacity"
            min="1"
            class="form-control mt-1 @error('capacity') is-invalid @enderror"
            required
        />
        <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
    </div>

    <div class="mb-3">
        <x-input-label for="type" value="Status własności" />
        <span class="text-danger">*</span>
        <select
            id="type"
            wire:model.live="type"
            name="type"
            class="form-select mt-1 @error('type') is-invalid @enderror"
            required
        >
            <option value="wynajmowany">Wynajmowany</option>
            <option value="własny">Własny</option>
        </select>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    @if($type === 'wynajmowany')
        <p class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Pełna historia najmu dostępna na stronie szczegółów mieszkania. Tu edytujesz <strong>bieżący aktywny najem</strong>.
        </p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <x-input-label for="lease_start_date" value="Okres najmu — od" />
                <span class="text-danger">*</span>
                <input
                    type="date"
                    id="lease_start_date"
                    wire:model="lease_start_date"
                    name="lease_start_date"
                    class="form-control mt-1 @error('lease_start_date') is-invalid @enderror"
                />
                <x-input-error :messages="$errors->get('lease_start_date')" class="mt-2" />
            </div>
            <div class="col-md-6 mb-3">
                <x-input-label for="lease_end_date" value="Okres najmu — do" />
                <span class="text-danger">*</span>
                <input
                    type="date"
                    id="lease_end_date"
                    wire:model="lease_end_date"
                    name="lease_end_date"
                    class="form-control mt-1 @error('lease_end_date') is-invalid @enderror"
                />
                <x-input-error :messages="$errors->get('lease_end_date')" class="mt-2" />
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <x-input-label for="lease_monthly_rent" value="Miesięczny czynsz (opcjonalny)" />
                <input
                    type="number"
                    id="lease_monthly_rent"
                    wire:model="lease_monthly_rent"
                    name="lease_monthly_rent"
                    class="form-control mt-1 @error('lease_monthly_rent') is-invalid @enderror"
                    step="0.01"
                    min="0"
                    placeholder="np. 1500.00"
                />
                <small class="text-muted">Używane w raportach kosztów (proporcjonalnie do dni najmu w okresie).</small>
                <x-input-error :messages="$errors->get('lease_monthly_rent')" class="mt-2" />
            </div>
            <div class="col-md-4 mb-3">
                <x-input-label for="lease_currency" value="Waluta czynszu" />
                <select
                    id="lease_currency"
                    wire:model="lease_currency"
                    name="lease_currency"
                    class="form-select mt-1 @error('lease_currency') is-invalid @enderror"
                >
                    <option value="EUR">EUR</option>
                    <option value="PLN">PLN</option>
                    <option value="USD">USD</option>
                    <option value="GBP">GBP</option>
                </select>
                <x-input-error :messages="$errors->get('lease_currency')" class="mt-2" />
            </div>
        </div>
    @endif

    <div class="mb-3">
        <x-input-label for="description" value="Opis / uwagi do wynajmu" />
        <textarea
            id="description"
            wire:model="description"
            name="description"
            rows="3"
            class="form-control mt-1"
        ></textarea>
    </div>

</div>

@script
<script>
    // Keep coordinate hidden fields in sync with Livewire state
    function syncAccCoords() {
        const latEl = document.getElementById('acc_lat_hidden');
        const lngEl = document.getElementById('acc_lng_hidden');
        if (latEl) {
            const lat = @this.get('latitude');
            latEl.value = (lat !== null && lat !== undefined) ? lat : '';
        }
        if (lngEl) {
            const lng = @this.get('longitude');
            lngEl.value = (lng !== null && lng !== undefined) ? lng : '';
        }
    }

    $wire.on('$refresh', () => syncAccCoords());

    document.addEventListener('DOMContentLoaded', () => {
        syncAccCoords();

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', () => {
                const fields = ['location_name', 'address', 'city', 'postal_code'];
                fields.forEach(field => {
                    const el = form.querySelector(`[name="${field}"]`);
                    if (el && !el.hasAttribute('wire:model')) {
                        const val = @this.get(field);
                        el.value = val || '';
                    }
                });
                syncAccCoords();
            });
        }

        // Close geo search results when clicking outside
        document.addEventListener('click', function (e) {
            const resultsDiv = document.querySelector('.accommodation-search-results');
            if (resultsDiv && !resultsDiv.contains(e.target)) {
                @this.closeResults();
            }
        });
    });
</script>
@endscript
