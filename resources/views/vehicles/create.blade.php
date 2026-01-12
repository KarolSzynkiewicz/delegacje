<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">Dodaj Nowy Pojazd</h2>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Pojazd">
                <x-ui.errors />

                <form action="{{ route('vehicles.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="registration_number" 
                            label="Numer Rejestracyjny"
                            value="{{ old('registration_number') }}"
                            placeholder="np. WA 12345"
                            required="true"
                        />
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="brand" 
                                    label="Marka"
                                    value="{{ old('brand') }}"
                                    placeholder="np. Volkswagen"
                                />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="model" 
                                    label="Model"
                                    value="{{ old('model') }}"
                                    placeholder="np. Transporter"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="number" 
                                    name="capacity" 
                                    label="Pojemność (liczba osób)"
                                    value="{{ old('capacity') }}"
                                    min="1"
                                />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="select" 
                                    name="technical_condition" 
                                    label="Stan Techniczny"
                                    required="true"
                                >
                                    <option value="">-- Wybierz Stan --</option>
                                    <option value="excellent" {{ old('technical_condition') == 'excellent' ? 'selected' : '' }}>Doskonały</option>
                                    <option value="good" {{ old('technical_condition') == 'good' ? 'selected' : '' }}>Dobry</option>
                                    <option value="fair" {{ old('technical_condition') == 'fair' ? 'selected' : '' }}>Zadowalający</option>
                                    <option value="poor" {{ old('technical_condition') == 'poor' ? 'selected' : '' }}>Słaby</option>
                                </x-ui.input>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="inspection_valid_to" 
                            label="Przegląd Ważny Do"
                            value="{{ old('inspection_valid_to') }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="4"
                        />
                    </div>

                    <x-ui.image-preview />

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Dodaj Pojazd
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('vehicles.index') }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
