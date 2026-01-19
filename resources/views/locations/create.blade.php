<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Nową Lokalizację">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('locations.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nową Lokalizację">
                    <form method="POST" action="{{ route('locations.store') }}">
                        @csrf

                        <div class="mb-3">
                            <x-input-label for="name" value="Nazwa" />
                            <span class="text-danger">*</span>
                            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="address" value="Adres" />
                            <span class="text-danger">*</span>
                            <x-text-input id="address" name="address" type="text" class="mt-1" :value="old('address')" required />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="city" value="Miasto" />
                                <x-text-input id="city" name="city" type="text" class="mt-1" :value="old('city')" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="postal_code" value="Kod pocztowy" />
                                <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1" :value="old('postal_code')" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-input-label for="contact_person" value="Osoba kontaktowa" />
                            <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1" :value="old('contact_person')" />
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="phone" value="Telefon" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1" :value="old('phone')" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="email" value="Email" />
                                <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-input-label for="description" value="Opis" />
                            <textarea id="description" name="description" rows="4" class="form-control mt-1">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <x-ui.input 
                                type="checkbox" 
                                name="is_base" 
                                id="is_base"
                                value="1"
                                label="<strong>Lokalizacja jest bazą</strong>"
                                :value="old('is_base') ? true : false"
                            />
                            <small class="text-muted d-block mt-1">Zaznacz, jeśli ta lokalizacja jest siedzibą główną firmy</small>
                        </div>

                <div class="d-flex justify-content-between align-items-center">
                    <x-ui.button 
                        variant="primary" 
                        type="submit"
                        action="save"
                    >
                        Zapisz
                    </x-ui.button>
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('locations.index') }}"
                        action="cancel"
                    >
                        Anuluj
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
</x-app-layout>
