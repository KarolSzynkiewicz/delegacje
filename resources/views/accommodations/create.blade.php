<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">Dodaj Nową Akomodację</h2>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nową Akomodację">
                <x-ui.errors />

                <form action="{{ route('accommodations.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa"
                            value="{{ old('name') }}"
                            placeholder="np. Mieszkanie 1, Mieszkanie 2"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="address" 
                            label="Adres"
                            value="{{ old('address') }}"
                            required="true"
                        />
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="city" 
                                    label="Miasto"
                                    value="{{ old('city') }}"
                                />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="postal_code" 
                                    label="Kod Pocztowy"
                                    value="{{ old('postal_code') }}"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="capacity" 
                            label="Pojemność (liczba osób)"
                            value="{{ old('capacity') }}"
                            min="1"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description') }}"
                            rows="4"
                        />
                    </div>

                    <x-ui.image-preview />

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Dodaj Akomodację
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('accommodations.index') }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
