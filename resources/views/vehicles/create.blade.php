<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Nowy Pojazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicles.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
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
                                @foreach(\App\Enums\VehicleCondition::cases() as $condition)
                                    <option 
                                        value="{{ $condition->value }}" 
                                        {{ old('technical_condition') == $condition->value ? 'selected' : '' }}
                                    >
                                        {{ $condition->label() }}
                                    </option>
                                @endforeach
                            </x-ui.input>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="date" 
                                    name="inspection_valid_to" 
                                    label="Przegląd Ważny Do"
                                    value="{{ old('inspection_valid_to') }}"
                                />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="date" 
                                    name="insurance_valid_to" 
                                    label="OC Ważne Do"
                                    value="{{ old('insurance_valid_to') }}"
                                />
                            </div>
                        </div>
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
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Dodaj Pojazd
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('vehicles.index') }}"
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
