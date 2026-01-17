<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Projekt">
                <form method="POST" action="{{ route('projects.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="location_id" 
                            label="Lokalizacja"
                            required="true"
                        >
                            <option value="">Wybierz lokalizację</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }} ({{ $location->address }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa Projektu"
                            value="{{ old('name') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="client_name" 
                            label="Klient"
                            value="{{ old('client_name') }}"
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

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="status" 
                            label="Status"
                            required="true"
                        >
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktywny</option>
                            <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>Wstrzymany</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Zakończony</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                        </x-ui.input>
                    </div>

                    <div class="mb-3" x-data="{ projectType: '{{ old('type', 'contract') }}' }">
                        <x-ui.input 
                            type="select" 
                            name="type" 
                            label="Typ Projektu"
                            required="true"
                            x-model="projectType"
                        >
                            <option value="contract" {{ old('type', 'contract') == 'contract' ? 'selected' : '' }}>Zakontraktowany</option>
                            <option value="hourly" {{ old('type') == 'hourly' ? 'selected' : '' }}>Rozliczany godzinowo</option>
                        </x-ui.input>

                        <!-- Pola dla projektów rozliczanych godzinowo -->
                        <div x-show="projectType === 'hourly'" x-transition class="mt-3">
                            <x-ui.input 
                                type="number" 
                                name="hourly_rate" 
                                label="Stawka za godzinę"
                                value="{{ old('hourly_rate') }}"
                                step="0.01"
                                required="true"
                            />
                        </div>

                        <!-- Pola dla projektów zakontraktowanych -->
                        <div x-show="projectType === 'contract'" x-transition class="mt-3">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <x-ui.input 
                                        type="number" 
                                        name="contract_amount" 
                                        label="Kwota kontraktu"
                                        value="{{ old('contract_amount') }}"
                                        step="0.01"
                                        required="true"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <x-ui.input 
                                        type="select" 
                                        name="currency" 
                                        label="Waluta"
                                        required="true"
                                    >
                                        <option value="PLN" {{ old('currency', 'PLN') == 'PLN' ? 'selected' : '' }}>PLN</option>
                                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                                    </x-ui.input>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="budget" 
                            label="Budżet (PLN)"
                            value="{{ old('budget') }}"
                            step="0.01"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('projects.index') }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
