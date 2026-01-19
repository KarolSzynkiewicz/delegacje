<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Pojazd: {{ $vehicle->registration_number }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicles.show', $vehicle) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Pojazd">
                <x-ui.errors />

                <form action="{{ route('vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="registration_number" 
                            label="Numer Rejestracyjny"
                            value="{{ old('registration_number', $vehicle->registration_number) }}"
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
                                    value="{{ old('brand', $vehicle->brand) }}"
                                />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="model" 
                                    label="Model"
                                    value="{{ old('model', $vehicle->model) }}"
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
                                    value="{{ old('capacity', $vehicle->capacity) }}"
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

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="inspection_valid_to" 
                            label="Przegląd Ważny Do"
                            value="{{ old('inspection_valid_to', $vehicle->inspection_valid_to?->format('Y-m-d')) }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes', $vehicle->notes) }}"
                            rows="4"
                        />
                    </div>

                    <x-ui.image-preview 
                        :showCurrentImage="$vehicle->image_path ? true : false"
                        :currentImageUrl="$vehicle->image_path ? $vehicle->image_url : null"
                        :currentImage="$vehicle->brand . ' ' . $vehicle->model"
                    />

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zaktualizuj Pojazd
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('vehicles.show', $vehicle) }}"
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
