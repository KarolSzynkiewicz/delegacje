<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">Edytuj Lokalizację</h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="POST" action="{{ route('locations.update', $location) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <x-input-label for="name" value="Nazwa" />
                            <span class="text-danger">*</span>
                            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $location->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="address" value="Adres" />
                            <span class="text-danger">*</span>
                            <x-text-input id="address" name="address" type="text" class="mt-1" :value="old('address', $location->address)" required />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="city" value="Miasto" />
                                <x-text-input id="city" name="city" type="text" class="mt-1" :value="old('city', $location->city)" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="postal_code" value="Kod pocztowy" />
                                <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1" :value="old('postal_code', $location->postal_code)" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-input-label for="contact_person" value="Osoba kontaktowa" />
                            <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1" :value="old('contact_person', $location->contact_person)" />
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="phone" value="Telefon" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1" :value="old('phone', $location->phone)" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="email" value="Email" />
                                <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $location->email)" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-input-label for="description" value="Opis" />
                            <textarea id="description" name="description" rows="4" class="form-control mt-1">{{ old('description', $location->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <x-ui.input 
                                type="checkbox" 
                                name="is_base" 
                                id="is_base"
                                value="1"
                                label="<strong>Lokalizacja jest bazą</strong>"
                                :value="old('is_base', $location->is_base) ? true : false"
                            />
                            <small class="text-muted d-block mt-1">Zaznacz, jeśli ta lokalizacja jest siedzibą główną firmy</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <x-primary-button>
                                <i class="bi bi-check-circle me-1"></i> Zapisz
                            </x-primary-button>
                            <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Anuluj
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
