<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Nowa akcja serwisowa">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Zarejestruj oddanie pojazdu do serwisu">
                <x-ui.errors />

                <div class="alert alert-info mb-4" style="background: rgba(59,130,246,0.1); border-color: rgba(59,130,246,0.3); color: var(--text-main);">
                    <i class="bi bi-info-circle me-2"></i>
                    Najpierw zobaczysz <strong>podsumowanie</strong>: które przypisania do pojazdu zostaną skrócone lub usunięte
                    (jak przy zjazdzie). Dopiero po akceptacji zapiszemy serwis i ustawimy stan pojazdu na <strong>Warsztat</strong>.
                </div>

                <form action="{{ route('vehicle-repairs.prepare-form') }}" method="POST">
                    @csrf

                    {{-- Vehicle --}}
                    <div class="mb-4">
                        <x-input-label for="vehicle_id" value="Pojazd" />
                        <span class="text-danger">*</span>
                        <select id="vehicle_id" name="vehicle_id" class="form-select mt-1 @error('vehicle_id') is-invalid @enderror" required>
                            <option value="">-- Wybierz pojazd --</option>
                            @foreach($vehicles as $vehicle)
                                <option
                                    value="{{ $vehicle->id }}"
                                    {{ (old('vehicle_id', $selectedVehicleId) == $vehicle->id) ? 'selected' : '' }}
                                >
                                    {{ $vehicle->registration_number }}
                                    @if($vehicle->brand) – {{ $vehicle->brand }} {{ $vehicle->model }} @endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('vehicle_id')" class="mt-2" />
                    </div>

                    {{-- Action type --}}
                    <div class="mb-4">
                        <x-input-label for="action_type" value="Typ akcji serwisowej" />
                        <span class="text-danger">*</span>
                        <select id="action_type" name="action_type" class="form-select mt-1 @error('action_type') is-invalid @enderror" required>
                            <option value="">-- Wybierz typ --</option>
                            @foreach($actionTypes as $type)
                                <option value="{{ $type->value }}" {{ old('action_type') === $type->value ? 'selected' : '' }}>
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
                                value="{{ old('start_date', date('Y-m-d')) }}"
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
                                value="{{ old('end_date') }}"
                                class="form-control mt-1 @error('end_date') is-invalid @enderror"
                            />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <x-input-label for="price" value="Koszt (opcjonalny)" />
                            <input
                                type="number"
                                id="price"
                                name="price"
                                value="{{ old('price') }}"
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
                                    <option value="{{ $cur }}" {{ old('currency', 'PLN') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
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
                        @livewire('workshop-search', ['locationId' => old('location_id') ? (int) old('location_id') : null])
                    </div>

                    {{-- Notes --}}
                    <div class="mb-4">
                        <x-input-label for="notes" value="Notatki" />
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="form-control mt-1"
                            placeholder="Opis usterki, zakres prac, uwagi..."
                        >{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit" action="save">
                            Dalej: podsumowanie i akceptacja
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.index') }}" action="cancel">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
