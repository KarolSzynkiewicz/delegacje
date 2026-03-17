<div>
    <div class="mb-3">
        <x-input-label for="name" value="Nazwa" />
        <span class="text-danger">*</span>
        <x-text-input id="name" wire:model="name" name="name" type="text" class="mt-1" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="mb-3">
        <x-input-label for="address_search" value="Wyszukaj miejsce" />
        <div class="d-flex gap-2">
            <div class="flex-grow-1 position-relative">
                <input 
                    type="text" 
                    id="address_search" 
                    wire:model="searchQuery"
                    wire:keydown.enter.prevent="search"
                    class="form-control @error('searchQuery') is-invalid @enderror" 
                    placeholder="Wpisz adres lub nazwę miejsca (min. 3 znaki)..."
                    autocomplete="off"
                />
                
                <!-- Loading indicator -->
                <div wire:loading wire:target="search" class="position-absolute top-50 end-0 translate-middle-y pe-3">
                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
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
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Szukanie...
                </span>
            </button>
        </div>
        
        <!-- Error message -->
        @if($searchError)
            <div class="alert alert-danger mt-2 mb-0" role="alert" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); color: var(--text-main);">
                <small><strong>Błąd:</strong> {{ $searchError }}</small>
            </div>
        @endif
        
        <!-- Results list -->
        @if($showResults && !empty($searchResults))
            <div class="mt-2 accommodation-search-results rounded p-3" style="max-height: 400px; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--glass-border);">
                <div class="mb-3">
                    <strong style="color: var(--text-main);">Znalezione miejsca ({{ count($searchResults) }}):</strong>
                </div>
                @foreach($searchResults as $index => $result)
                    <button 
                        type="button"
                        wire:click="selectLocation({{ $index }})"
                        class="btn btn-outline-secondary btn-sm w-100 text-start mb-2 accommodation-search-result-item"
                        style="white-space: normal; color: var(--text-main); border-color: var(--glass-border); background: rgba(255, 255, 255, 0.05);"
                        onmouseover="this.style.background='rgba(255, 255, 255, 0.15)'; this.style.borderColor='var(--glass-border)';"
                        onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.borderColor='var(--glass-border)';"
                    >
                        <strong style="color: var(--text-main);">{{ $result['label'] }}</strong>
                        @if(!empty($result['city']) || !empty($result['country']))
                            <br>
                            <small class="text-muted" style="color: var(--text-muted) !important;">
                                @if(!empty($result['city']))
                                    {{ $result['city'] }}
                                @endif
                                @if(!empty($result['city']) && !empty($result['country'])), @endif
                                @if(!empty($result['country']))
                                    {{ $result['country'] }}
                                @endif
                            </small>
                        @endif
                    </button>
                @endforeach
            </div>
        @elseif($showResults && empty($searchResults) && !$isSearching)
            <div class="alert alert-info mt-2 mb-0" role="alert" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3); color: var(--text-main);">
                <small>Brak wyników dla zapytania: "{{ $searchQuery }}"</small>
            </div>
        @endif
        
        <small class="text-muted d-block mt-1">
            Wpisz adres i kliknij "Szukaj", następnie wybierz miejsce z listy aby automatycznie wypełnić pola formularza.
        </small>
    </div>

    <div class="mb-3">
        <x-input-label for="address" value="Adres" />
        <span class="text-danger">*</span>
        <x-text-input id="address" wire:model="address" name="address" type="text" class="mt-1" required />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <x-input-label for="city" value="Miasto" />
            <x-text-input id="city" wire:model="city" name="city" type="text" class="mt-1" />
        </div>

        <div class="col-md-4 mb-3">
            <x-input-label for="postal_code" value="Kod pocztowy" />
            <x-text-input id="postal_code" wire:model="postal_code" name="postal_code" type="text" class="mt-1" />
        </div>

        <div class="col-md-4 mb-3">
            <x-input-label for="country" value="Kraj" />
            <select id="country" wire:model="country" name="country" class="form-select mt-1">
                <option value="">-- Wybierz kraj --</option>
                @foreach(\App\Enums\EuropeanCountry::sorted() as $countryEnum)
                    <option value="{{ $countryEnum->value }}">
                        {{ $countryEnum->labelWithFlag() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('country')" class="mt-2" />
        </div>
    </div>

    <!-- Coordinates display -->
    <div class="row mb-3">
        <div class="col-md-6">
            <x-input-label for="latitude_display" value="Szerokość geograficzna" />
            <input 
                type="text" 
                id="latitude_display" 
                class="form-control mt-1" 
                readonly
                placeholder="Zostanie wypełnione automatycznie po wyborze miejsca"
                value="{{ $this->formattedLatitude }}"
                style="background-color: var(--bg-input); color: var(--text-main); cursor: not-allowed;"
            />
            <!-- Hidden field for form submission -->
            <input type="hidden" wire:model="latitude" wire:key="latitude-{{ $latitude }}" name="latitude" id="latitude">
        </div>
        <div class="col-md-6">
            <x-input-label for="longitude_display" value="Długość geograficzna" />
            <input 
                type="text" 
                id="longitude_display" 
                class="form-control mt-1" 
                readonly
                placeholder="Zostanie wypełnione automatycznie po wyborze miejsca"
                value="{{ $this->formattedLongitude }}"
                style="background-color: var(--bg-input); color: var(--text-main); cursor: not-allowed;"
            />
            <!-- Hidden field for form submission -->
            <input type="hidden" wire:model="longitude" wire:key="longitude-{{ $longitude }}" name="longitude" id="longitude">
        </div>
    </div>

    <hr class="my-4" style="border-color: var(--glass-border);">

    <div class="mb-3">
        <x-input-label for="capacity" value="Pojemność (liczba osób)" />
        <span class="text-danger">*</span>
        <x-text-input id="capacity" wire:model="capacity" name="capacity" type="number" class="mt-1" min="1" required />
        <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
    </div>

    <div class="mb-3" x-data="{ type: '{{ $type }}' }">
        <x-input-label for="type" value="Typ" />
        <span class="text-danger">*</span>
        <select id="type" wire:model="type" name="type" class="form-select mt-1" required x-model="type">
            <option value="własny">Własny</option>
            <option value="wynajmowany">Wynajmowany</option>
        </select>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />

        <div class="mt-3" x-show="type === 'wynajmowany'" x-cloak>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <x-input-label for="lease_start_date" value="Okres najmu - od" />
                    <x-text-input id="lease_start_date" wire:model="lease_start_date" name="lease_start_date" type="date" class="mt-1" />
                    <x-input-error :messages="$errors->get('lease_start_date')" class="mt-2" />
                </div>
                <div class="col-md-6 mb-3">
                    <x-input-label for="lease_end_date" value="Okres najmu - do" />
                    <x-text-input id="lease_end_date" wire:model="lease_end_date" name="lease_end_date" type="date" class="mt-1" />
                    <x-input-error :messages="$errors->get('lease_end_date')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <x-input-label for="description" value="Opis" />
        <textarea id="description" wire:model="description" name="description" rows="4" class="form-control mt-1"></textarea>
    </div>
</div>

@script
<script>
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        const searchInput = document.getElementById('address_search');
        const resultsDiv = document.querySelector('.accommodation-search-results');
        
        if (searchInput && resultsDiv && 
            !searchInput.contains(e.target) && 
            !resultsDiv.contains(e.target)) {
            @this.closeResults();
        }
    });
    
    // Force sync Livewire values to hidden fields before form submit
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Wait for Livewire to sync, then update hidden fields
                setTimeout(function() {
                    const componentId = @this.__instance.id;
                    const component = Livewire.find(componentId);
                    
                    if (component) {
                        const latitudeField = form.querySelector('[name="latitude"]');
                        const longitudeField = form.querySelector('[name="longitude"]');
                        
                        if (latitudeField) {
                            const lat = component.get('latitude');
                            if (lat !== null && lat !== undefined && lat !== '') {
                                latitudeField.value = lat;
                            }
                        }
                        
                        if (longitudeField) {
                            const lng = component.get('longitude');
                            if (lng !== null && lng !== undefined && lng !== '') {
                                longitudeField.value = lng;
                            }
                        }
                    }
                }, 100);
            });
        }
    });
</script>
@endscript
