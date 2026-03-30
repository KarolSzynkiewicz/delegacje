<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zakończ naprawę #{{ $vehicleRepair->id }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.show', $vehicleRepair) }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-7">

            {{-- Summary --}}
            <x-ui.card class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">Pojazd</small>
                        <p class="mb-1"><strong>{{ $vehicleRepair->vehicle->registration_number }}</strong>
                            @if($vehicleRepair->vehicle->brand)
                                – {{ $vehicleRepair->vehicle->brand }} {{ $vehicleRepair->vehicle->model }}
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Typ serwisu</small>
                        <p class="mb-1">
                            <x-ui.badge variant="{{ $vehicleRepair->action_type->badgeVariant() }}">
                                {{ $vehicleRepair->action_type->label() }}
                            </x-ui.badge>
                        </p>
                    </div>
                    <div class="col-md-6 mt-2">
                        <small class="text-muted">Data oddania</small>
                        <p class="mb-0">{{ $vehicleRepair->start_date->format('Y-m-d') }}</p>
                    </div>
                    @if($vehicleRepair->location)
                        <div class="col-md-6 mt-2">
                            <small class="text-muted">Warsztat</small>
                            <p class="mb-0">{{ $vehicleRepair->location->name }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card label="Wypełnij dane zakończenia">
                <x-ui.errors />

                <div class="alert mb-4" style="background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.3); color: var(--text-main);">
                    <i class="bi bi-info-circle me-2"></i>
                    Po zakończeniu koszt naprawy zostanie automatycznie zaksięgowany jako <strong>Serwis #{{ $vehicleRepair->id }}</strong>.
                </div>

                <form action="{{ route('vehicle-repairs.complete', $vehicleRepair) }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="end_date" value="Data odbioru pojazdu z warsztatu" />
                        <span class="text-danger">*</span>
                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            value="{{ old('end_date', date('Y-m-d')) }}"
                            min="{{ $vehicleRepair->start_date->format('Y-m-d') }}"
                            class="form-control mt-1 @error('end_date') is-invalid @enderror"
                            required
                        />
                        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <x-input-label for="price" value="Całkowity koszt naprawy" />
                            <span class="text-danger">*</span>
                            <input
                                type="number"
                                id="price"
                                name="price"
                                value="{{ old('price') }}"
                                step="0.01"
                                min="0"
                                class="form-control mt-1 @error('price') is-invalid @enderror"
                                placeholder="np. 2800.00"
                                required
                            />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>
                        <div class="col-md-4">
                            <x-input-label for="currency" value="Waluta" />
                            <span class="text-danger">*</span>
                            <select id="currency" name="currency" class="form-select mt-1 @error('currency') is-invalid @enderror" required>
                                @foreach(['PLN','EUR','USD','GBP','CHF','CZK','HUF','UAH'] as $cur)
                                    <option value="{{ $cur }}" {{ old('currency', 'PLN') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-input-label for="new_technical_condition" value="Nowy stan techniczny pojazdu po naprawie" />
                        <span class="text-danger">*</span>
                        <select
                            id="new_technical_condition"
                            name="new_technical_condition"
                            class="form-select mt-1 @error('new_technical_condition') is-invalid @enderror"
                            required
                        >
                            <option value="">-- Wybierz stan --</option>
                            @foreach($conditionOptions as $cond)
                                @if($cond->value !== 'workshop')
                                    <option
                                        value="{{ $cond->value }}"
                                        {{ old('new_technical_condition', $vehicleRepair->previous_technical_condition) === $cond->value ? 'selected' : '' }}
                                    >
                                        {{ $cond->label() }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="text-muted">Poprzedni stan: <strong>{{ \App\Enums\VehicleCondition::tryFrom($vehicleRepair->previous_technical_condition ?? '')?->label() ?? '–' }}</strong></small>
                        <x-input-error :messages="$errors->get('new_technical_condition')" class="mt-2" />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="success" type="submit">
                            <i class="bi bi-check-circle me-1"></i> Zatwierdź zakończenie
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
