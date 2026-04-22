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
            <div class="mt-2 location-search-results rounded p-3" style="max-height: 400px; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--glass-border);">
                <div class="mb-3">
                    <strong style="color: var(--text-main);">Znalezione miejsca ({{ count($searchResults) }}):</strong>
                </div>
                @foreach($searchResults as $index => $result)
                    <button 
                        type="button"
                        wire:click="selectLocation({{ $index }})"
                        class="btn btn-outline-secondary btn-sm w-100 text-start mb-2 location-search-result-item"
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

    <!-- Współrzędne: z wyszukiwarki albo ręcznie -->
    <div class="row mb-3">
        <div class="col-md-6">
            <x-input-label for="latitude" value="Szerokość geograficzna" />
            <input
                type="number"
                step="any"
                id="latitude"
                wire:model="latitude"
                name="latitude"
                class="form-control mt-1 @error('latitude') is-invalid @enderror"
                placeholder="np. 52.2297"
            />
            <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
        </div>
        <div class="col-md-6">
            <x-input-label for="longitude" value="Długość geograficzna" />
            <input
                type="number"
                step="any"
                id="longitude"
                wire:model="longitude"
                name="longitude"
                class="form-control mt-1 @error('longitude') is-invalid @enderror"
                placeholder="np. 21.0122"
            />
            <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
        </div>
    </div>
    <p class="small text-muted mb-0">
        Wypełniają się po wybraniu wyniku z wyszukiwarki powyżej. Możesz też wpisać wartości ręcznie (np. z mapy), jeśli wyszukiwarka nie znajdzie miejsca.
    </p>

    <hr class="my-4" style="border-color: var(--glass-border);">

    <div class="mb-4">
        <x-input-label value="Typ / cel lokalizacji" />
        <small class="text-muted d-block mb-2">Jeden fizyczny obiekt może mieć wiele celów (poza siedzibą główną).</small>

        <div class="mb-3">
            <x-ui.input
                type="checkbox"
                wire:model="is_base"
                name="is_base"
                id="is_base"
                value="1"
                :checked="$is_base"
                label="Baza (siedziba główna)"
            />
            <small class="text-muted d-block mt-1 ms-1">Tylko jedna lokalizacja w systemie może być główną siedzibą — zaznaczenie automatycznie odznaczy poprzednią.</small>
        </div>

        <div class="d-flex flex-wrap gap-3">
            @foreach($purposeTypes as $pt)
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="loc_purpose_{{ $pt->value }}"
                        wire:model="purposes"
                        name="purposes[]"
                        value="{{ $pt->value }}"
                    />
                    <label class="form-check-label" for="loc_purpose_{{ $pt->value }}">{{ $pt->label() }}</label>
                </div>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('purposes')" class="mt-2" />
        <x-input-error :messages="$errors->get('purposes.*')" class="mt-2" />
    </div>

    <div class="mb-3">
        <x-input-label for="contact_person" value="Osoba kontaktowa" />
        <x-text-input id="contact_person" wire:model="contact_person" name="contact_person" type="text" class="mt-1" />
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <x-input-label for="phone" value="Telefon" />
            <x-text-input id="phone" wire:model="phone" name="phone" type="text" class="mt-1" />
        </div>

        <div class="col-md-6 mb-3">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" wire:model="email" name="email" type="email" class="mt-1" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
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
        const resultsDiv = document.querySelector('.location-search-results');
        
        if (searchInput && resultsDiv && 
            !searchInput.contains(e.target) && 
            !resultsDiv.contains(e.target)) {
            @this.closeResults();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                const component = Livewire.find(@this.__instance.id);
                if (!component) return;

                const fields = [
                    'name', 'address', 'city', 'postal_code', 'country',
                    'contact_person', 'phone', 'email', 'description',
                    'latitude', 'longitude',
                ];

                fields.forEach(field => {
                    const formField = form.querySelector(`[name="${field}"]`);
                    if (formField) {
                        const value = component.get(field);
                        if (value === null || value === undefined) {
                            formField.value = '';
                        } else {
                            formField.value = value;
                        }
                    }
                });

                const isBaseCheckbox = form.querySelector('[name="is_base"]');
                if (isBaseCheckbox) {
                    const isBase = component.get('is_base');
                    isBaseCheckbox.checked = isBase === true || isBase === '1' || isBase === 1;
                    if (isBaseCheckbox.checked) {
                        isBaseCheckbox.value = '1';
                    }
                }
            });
        }
    });
</script>
@endscript
