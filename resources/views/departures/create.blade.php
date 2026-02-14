<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Wyjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.index') }}"
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

            <x-ui.card label="Utwórz Nowy Wyjazd">
                <x-ui.errors />

                <form method="POST" action="{{ route('departures.store') }}">
                    @csrf

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
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
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
                                <option value="{{ $location->id }}" {{ old('to_location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @livewire('departure-employee-selector', ['departureDate' => old('departure_date', date('Y-m-d'))], key('departure-selector'))

                    <div class="mb-4">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Notatki
                            <x-tooltip title="Dodatkowe informacje: szczegóły trasy, miejsce spotkania, wymagania specjalne, lub inne uwagi logistyczne.">
                                <i class="bi bi-sticky text-warning fs-6"></i>
                            </x-tooltip>
                        </label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('departures.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Utwórz Wyjazd
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    @push('scripts')
    <script>
        // Reinitialize tooltips after Livewire updates
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', () => {
                initializeTooltips();
            });
        });

        // Initialize tooltips on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeTooltips();
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
