<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Wyjazd (Krok 1/2)">
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

            <x-ui.card label="Krok 1: Wybierz uczestników i podstawowe informacje">
                <x-ui.errors />

                <form method="POST" action="{{ route('departures.prepare-bulk-assignment') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Pojazd (opcjonalne)
                            <x-tooltip title="Pojazd używany do transportu. Zostanie automatycznie zablokowany na cały czas wyjazdu (od daty wyjazdu do daty przybycia).">
                                <i class="bi bi-truck text-warning fs-6"></i>
                            </x-tooltip>
                        </label>
                        <select 
                            name="vehicle_id" 
                            id="vehicle_select"
                            class="form-select"
                            x-data="{ 
                                vehicleId: '{{ old('vehicle_id') ?? '' }}',
                                departureDate: '{{ old('departure_date', date('Y-m-d')) }}',
                                endDate: '{{ old('end_date') ?? '' }}'
                            }"
                            x-on:change="vehicleId = $event.target.value; 
                                        departureDate = document.querySelector('[name=departure_date]')?.value || '{{ date('Y-m-d') }}';
                                        endDate = document.querySelector('[name=end_date]')?.value || '';
                                        $wire.set('vehicleId', vehicleId);
                                        $wire.set('departureDate', departureDate);
                                        $wire.set('endDate', endDate);"
                        >
                            <option value="">Brak pojazdu</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </select>
                        
                        @livewire('vehicle-availability-checker', [
                            'vehicleId' => old('vehicle_id') ?? '',
                            'departureDate' => old('departure_date', date('Y-m-d')),
                            'endDate' => old('end_date') ?? ''
                        ], key('vehicle-checker'))
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

                    @livewire('departure-employee-selector', [
                        'departureDate' => old('departure_date', date('Y-m-d')),
                        'endDate' => old('end_date')
                    ], key('departure-selector'))

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
                            Dalej → Przypisz do projektów (Krok 2/2)
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
            setupVehicleValidation();
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
        
        function setupVehicleValidation() {
            const vehicleSelect = document.getElementById('vehicle_select');
            
            // Wait for Livewire to be ready
            document.addEventListener('livewire:init', () => {
                const checker = Livewire.find('vehicle-checker');
                if (!checker) return;
                
                // Listen for vehicle selection changes
                if (vehicleSelect) {
                    vehicleSelect.addEventListener('change', function() {
                        const vehicleId = this.value;
                        const departureDate = document.querySelector('[name="departure_date"]')?.value || '';
                        const endDate = document.querySelector('[name="end_date"]')?.value || '';
                        
                        checker.set('vehicleId', vehicleId);
                        if (departureDate) checker.set('departureDate', departureDate);
                        if (endDate) checker.set('endDate', endDate);
                    });
                }
                
                // Listen for date changes from Livewire departure-employee-selector
                Livewire.on('dateChanged', (data) => {
                    const vehicleId = vehicleSelect?.value || '';
                    if (vehicleId && data.departureDate) {
                        checker.set('departureDate', data.departureDate);
                    }
                    if (vehicleId && data.endDate) {
                        checker.set('endDate', data.endDate);
                    }
                });
                
                // Also listen to DOM changes (fallback)
                const observer = new MutationObserver(() => {
                    const vehicleId = vehicleSelect?.value || '';
                    const departureDate = document.querySelector('[name="departure_date"]')?.value || '';
                    const endDate = document.querySelector('[name="end_date"]')?.value || '';
                    
                    if (vehicleId && departureDate && endDate) {
                        checker.set('vehicleId', vehicleId);
                        checker.set('departureDate', departureDate);
                        checker.set('endDate', endDate);
                    }
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['value']
                });
            });
        }
    </script>
    @endpush

</x-app-layout>
