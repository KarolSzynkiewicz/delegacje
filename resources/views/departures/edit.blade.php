<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Wyjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.show', $departure) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if(session('success'))
                <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            @if(session('error'))
                <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            <x-ui.card label="Edytuj Wyjazd">
                <x-ui.errors />

                <form method="POST" action="{{ route('departures.update', $departure) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Pojazd (opcjonalne)
                            <x-tooltip title="Pojazd używany do transportu. Zostanie automatycznie zablokowany na cały czas wyjazdu (od daty wyjazdu do daty przybycia).">
                                <i class="bi bi-truck text-warning fs-6"></i>
                            </x-tooltip>
                        </label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">Brak pojazdu</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $departure->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Lokalizacja docelowa <span class="text-danger">*</span>
                            <x-tooltip title="Miejsce, do którego pracownicy dojeżdżają. Tu będą wykonywać pracę na projekcie.">
                                <i class="bi bi-geo-alt-fill text-success fs-6"></i>
                            </x-tooltip>
                        </label>
                        <select name="to_location_id" class="form-select" required>
                            <option value="">Wybierz lokalizację</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('to_location_id', $departure->to_location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @livewire('departure-employee-selector', [
                        'departureDate' => old('departure_date', $departure->event_date->format('Y-m-d')),
                        'selectedEmployeeIds' => old('employee_ids', $currentEmployeeIds),
                        'endDate' => old('end_date', $departure->end_date?->format('Y-m-d'))
                    ], key('departure-selector-edit'))

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Status przypisań
                            <x-tooltip title="Status przypisań pokazuje, czy wszyscy uczestnicy zostali przypisani do projektów. 'Oczekuje na przypisanie' = wymaga akcji. 'Przypisany' = wszyscy przypisani.">
                                <i class="bi bi-people-fill text-primary fs-6"></i>
                            </x-tooltip>
                        </label>
                        <select name="status" class="form-select">
                            @foreach(\App\Enums\LogisticsEventStatus::cases() as $status)
                                @if($status !== \App\Enums\LogisticsEventStatus::CANCELLED && $status !== \App\Enums\LogisticsEventStatus::IN_PROGRESS)
                                    <option value="{{ $status->value }}" {{ old('status', $departure->status->value) === $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Status przypisań pracowników do projektów</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Notatki
                            <x-tooltip title="Dodatkowe informacje: szczegóły trasy, miejsce spotkania, wymagania specjalne, lub inne uwagi logistyczne.">
                                <i class="bi bi-sticky text-warning fs-6"></i>
                            </x-tooltip>
                        </label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $departure->notes) }}</textarea>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('departures.show', $departure) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz Zmiany
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    @push('scripts')
    <script>
        // Initialize tooltips on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeTooltips();
        });

        // Reinitialize tooltips after Livewire updates
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => {
                initializeTooltips();
            });
        });

        function initializeTooltips() {
            document.querySelectorAll('.tooltip-hotspot').forEach(function(tooltipElement) {
                // Remove old listeners by cloning (prevents duplicate listeners)
                const newElement = tooltipElement.cloneNode(true);
                tooltipElement.parentNode.replaceChild(newElement, tooltipElement);
                
                // Add new listeners
                newElement.addEventListener('click', function(e) {
                    e.stopPropagation();
                    newElement.classList.toggle('active');
                });

                // Close tooltip when clicking outside
                document.addEventListener('click', function(e) {
                    if (!newElement.contains(e.target)) {
                        newElement.classList.remove('active');
                    }
                });

                // Close tooltip on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        newElement.classList.remove('active');
                    }
                });
            });
        }
    </script>
    @endpush
</x-app-layout>
