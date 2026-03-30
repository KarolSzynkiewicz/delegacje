<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj naprawę #{{ $vehicleRepair->id }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.show', $vehicleRepair) }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj wpis serwisowy – {{ $vehicleRepair->vehicle->registration_number }}">
                <x-ui.errors />

                @if($vehicleRepair->fixedCostEntry)
                    <div class="alert mb-4" style="background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.3); color: var(--text-main);">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Zmiana ceny lub daty odbioru automatycznie zaktualizuje powiązany koszt księgowy
                        <strong>{{ $vehicleRepair->fixedCostEntry->name }}</strong>.
                        Usunięcie ceny spowoduje usunięcie kosztu.
                    </div>
                @endif

                <form action="{{ route('vehicle-repairs.update', $vehicleRepair) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Vehicle (read-only) --}}
                    <div class="mb-4">
                        <x-input-label value="Pojazd" />
                        <p class="form-control-plaintext">
                            <strong>{{ $vehicleRepair->vehicle->registration_number }}</strong>
                            @if($vehicleRepair->vehicle->brand)
                                – {{ $vehicleRepair->vehicle->brand }} {{ $vehicleRepair->vehicle->model }}
                            @endif
                        </p>
                    </div>

                    {{-- Action type --}}
                    <div class="mb-4">
                        <x-input-label for="action_type" value="Typ akcji serwisowej" />
                        <span class="text-danger">*</span>
                        <select id="action_type" name="action_type" class="form-select mt-1 @error('action_type') is-invalid @enderror" required>
                            @foreach($actionTypes as $type)
                                <option
                                    value="{{ $type->value }}"
                                    {{ old('action_type', $vehicleRepair->action_type->value) === $type->value ? 'selected' : '' }}
                                >
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('action_type')" class="mt-2" />
                    </div>

                    {{-- Dates --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <x-input-label for="start_date" value="Data oddania do warsztatu" />
                            <span class="text-danger">*</span>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="{{ old('start_date', $vehicleRepair->start_date->format('Y-m-d')) }}"
                                class="form-control mt-1 @error('start_date') is-invalid @enderror"
                                required
                            />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>
                        <div class="col-md-6">
                            <x-input-label for="end_date" value="Data odbioru (opcjonalna)" />
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="{{ old('end_date', $vehicleRepair->end_date?->format('Y-m-d')) }}"
                                class="form-control mt-1 @error('end_date') is-invalid @enderror"
                            />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <x-input-label for="price" value="Koszt" />
                            <input
                                type="number"
                                id="price"
                                name="price"
                                value="{{ old('price', $vehicleRepair->price) }}"
                                step="0.01"
                                min="0"
                                class="form-control mt-1 @error('price') is-invalid @enderror"
                                placeholder="np. 1500.00"
                            />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>
                        <div class="col-md-4">
                            <x-input-label for="currency" value="Waluta" />
                            <select id="currency" name="currency" class="form-select mt-1 @error('currency') is-invalid @enderror">
                                <option value="">–</option>
                                @foreach(['PLN','EUR','USD','GBP','CHF','CZK','HUF','UAH'] as $cur)
                                    <option value="{{ $cur }}" {{ old('currency', $vehicleRepair->currency) === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Workshop --}}
                    <div class="mb-4">
                        <h6 class="mb-3" style="color: var(--text-main);">
                            <i class="bi bi-geo-alt me-1"></i> Warsztat
                        </h6>
                        @livewire('workshop-search', ['locationId' => old('location_id', $vehicleRepair->location_id)])
                    </div>

                    {{-- Notes --}}
                    <div class="mb-4">
                        <x-input-label for="notes" value="Notatki" />
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="form-control mt-1"
                        >{{ old('notes', $vehicleRepair->notes) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit" action="save">
                            Zapisz zmiany
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.show', $vehicleRepair) }}" action="cancel">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
