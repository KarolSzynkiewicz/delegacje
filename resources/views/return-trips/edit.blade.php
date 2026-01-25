<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Zjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('return-trips.show', $returnTrip) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Zjazd">
                    <x-ui.errors />

                    <form method="POST" action="{{ route('return-trips.prepare-form') }}">
                        @csrf
                        <input type="hidden" name="edit_mode" value="1">
                        <input type="hidden" name="return_trip_id" value="{{ $returnTrip->id }}">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pojazd powrotny</label>
                            <select name="vehicle_id" class="form-select">
                                <option value="">Brak pojazdu (opcjonalne)</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $returnTrip->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    @livewire('return-trip-employee-selector', [
                        'returnDate' => old('return_date', $returnTrip->event_date->format('Y-m-d')),
                        'selectedEmployeeIds' => old('employee_ids', $currentEmployeeIds),
                        'returnTripId' => $returnTrip->id
                    ], key('return-trip-selector-edit'))

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                @foreach(\App\Enums\LogisticsEventStatus::cases() as $status)
                                    @if($status !== \App\Enums\LogisticsEventStatus::CANCELLED)
                                        <option value="{{ $status->value }}" {{ old('status', $returnTrip->status->value) === $status->value ? 'selected' : '' }}>
                                            {{ $status->label() }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Status zjazdu można zmienić tylko jeśli nie jest anulowany</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notatki</label>
                            <textarea name="notes" rows="3" class="form-control">{{ old('notes', $returnTrip->notes) }}</textarea>
                        </div>

                <div class="d-flex gap-2">
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('return-trips.show', $returnTrip) }}"
                        action="cancel"
                    >
                        Anuluj
                    </x-ui.button>
                    <x-ui.button 
                        variant="primary" 
                        type="submit"
                        action="save"
                    >
                        Przygotuj Zmiany
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
</x-app-layout>
