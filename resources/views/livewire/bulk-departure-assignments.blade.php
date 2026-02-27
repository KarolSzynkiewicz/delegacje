<div>
    <!-- Dispatch location update on mount and when assignments change -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            const component = @this;
            
            // Update location on initial load
            updateDestinationLocation();
            
            // Listen for assignment changes
            Livewire.hook('morph.updated', () => {
                updateDestinationLocation();
            });
            
            function updateDestinationLocation() {
                const locationName = component.get('destinationLocationName');
                Livewire.dispatch('destination-location-updated', { locationName: locationName });
            }
        });
    </script>
    
    <!-- Toast notification -->
    <div id="copy-toast" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toast-message">
                    <i class="bi bi-check-circle me-2"></i>
                    <span></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('assignment-copied', (event) => {
                const toastEl = document.querySelector('#copy-toast .toast');
                const messageEl = document.querySelector('#toast-message span');
                messageEl.textContent = event.message;
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            });
        });
    </script>

    <!-- Komunikaty z sesji -->
    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-4">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-4">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <!-- Walidator na górze -->
    @if(count($validationErrors) > 0)
        <div class="alert alert-danger mb-4">
            <h5 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Znaleziono {{ count($validationErrors) }} {{ count($validationErrors) == 1 ? 'problem' : 'problemów' }}</h5>
            @foreach($validationErrors as $employeeId => $data)
                <div class="mb-2">
                    <strong>{{ $data['name'] }}:</strong>
                    <ul class="mb-0">
                        @foreach($data['errors'] as $error)
                            <li>
                                @if(is_array($error) && isset($error['message']))
                                    {{ $error['message'] }}
                                    @if(isset($error['overlapping_assignments']) && count($error['overlapping_assignments']) > 0)
                                        <div class="mt-1 ms-3">
                                            <small class="text-muted">Kolidujące przypisania:</small>
                                            @foreach($error['overlapping_assignments'] as $overlap)
                                                @if($overlap['type'] === 'vehicle_assignment')
                                                    <a href="{{ route('vehicle-assignments.show', $overlap['id']) }}" 
                                                       target="_blank" 
                                                       class="badge bg-warning text-dark text-decoration-none ms-1"
                                                       title="Otwórz przypisanie pojazdu">
                                                        <i class="bi bi-link-45deg"></i> Auto #{{ $overlap['id'] }}
                                                    </a>
                                                @elseif($overlap['type'] === 'project_assignment')
                                                    <a href="{{ route('project-assignments.show', $overlap['id']) }}" 
                                                       target="_blank" 
                                                       class="badge bg-info text-white text-decoration-none ms-1"
                                                       title="Otwórz przypisanie projektu">
                                                        <i class="bi bi-link-45deg"></i> Projekt #{{ $overlap['id'] }}
                                                    </a>
                                                @elseif($overlap['type'] === 'accommodation_assignment')
                                                    <a href="{{ route('accommodation-assignments.show', $overlap['id']) }}" 
                                                       target="_blank" 
                                                       class="badge bg-success text-white text-decoration-none ms-1"
                                                       title="Otwórz przypisanie zakwaterowania">
                                                        <i class="bi bi-link-45deg"></i> Dom #{{ $overlap['id'] }}
                                                    </a>
                                                @elseif($overlap['type'] === 'logistics_event')
                                                    <a href="{{ route($overlap['route'] ?? 'departures.show', $overlap['route_params'] ?? $overlap['id']) }}" 
                                                       target="_blank" 
                                                       class="badge bg-danger text-white text-decoration-none ms-1"
                                                       title="{{ $overlap['description'] ?? 'Otwórz wyjazd/zjazd' }}">
                                                        <i class="bi bi-link-45deg"></i> Wyjazd #{{ $overlap['id'] }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    {{ $error }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle"></i> <strong>Wszystkie przypisania są poprawne!</strong> Możesz zapisać wyjazd.
        </div>
    @endif

    <!-- Layout: Wiersze = Pola, Kolumny = Pracownicy -->
    <style>
        .section-header {
            font-size: 1.1rem;
            padding: 1rem !important;
            border-top: 3px solid !important;
        }
        
        /* Delikatne tła dla sekcji */
        .section-project-row {
            background-color: #f0f6ff !important;
        }
        
        .section-vehicle-row {
            background-color: #fffef0 !important;
        }
        
        .section-accommodation-row {
            background-color: #f0fff4 !important;
        }
        
        /* Grubsze obramowanie między sekcjami */
        .section-header td {
            border-top: 4px solid !important;
        }
    </style>
    
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 150px;">Przypisanie</th>
                    @foreach($employees as $employee)
                        <th class="text-center" style="min-width: 200px;">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <x-employee-cell :employee="$employee" />
                            </div>
                            @if($employee->roles->count() > 0)
                                <small class="text-muted d-block mt-1">
                                    {{ $employee->roles->pluck('name')->join(', ') }}
                                </small>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <!-- PROJEKT -->
                <tr class="table-primary section-header">
                    <td colspan="{{ count($employees) + 1 }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>🏢 PROJEKT</strong>
                            <button 
                                type="button" 
                                wire:click="copyProjectFromFirst" 
                                class="btn btn-sm btn-outline-light"
                                title="Skopiuj dane z pierwszego pracownika do wszystkich"
                            >
                                <i class="bi bi-arrow-right"></i> Kopiuj z pierwszego
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="section-project-row">
                    <td><strong>Projekt</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <select 
                                wire:model.live="assignments.{{ $employee->id }}.project_id" 
                                class="form-select"
                            >
                                <option value="">-- Wybierz --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    @endforeach
                </tr>
                <tr class="section-project-row">
                    <td><strong>Rola</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            @if($assignments[$employee->id]['project_id'])
                                <select 
                                    wire:model.live="assignments.{{ $employee->id }}.role_id" 
                                    class="form-select"
                                >
                                    <option value="">-- Wybierz --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="text-muted">─</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr class="section-project-row">
                    <td><strong>Data od</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <input 
                                type="date" 
                                wire:model.live="assignments.{{ $employee->id }}.project_start_date"
                                class="form-control"
                            >
                        </td>
                    @endforeach
                </tr>
                <tr class="section-project-row">
                    <td><strong>Data do</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <input 
                                type="date" 
                                wire:model.live="assignments.{{ $employee->id }}.project_end_date"
                                class="form-control"
                            >
                        </td>
                    @endforeach
                </tr>

                <!-- AUTO -->
                <tr class="table-warning section-header">
                    <td colspan="{{ count($employees) + 1 }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>🚗 AUTO</strong>
                            <button 
                                type="button" 
                                wire:click="copyVehicleFromFirst" 
                                class="btn btn-sm btn-outline-dark"
                                title="Skopiuj dane z pierwszego pracownika do wszystkich"
                            >
                                <i class="bi bi-arrow-right"></i> Kopiuj z pierwszego
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="section-vehicle-row">
                    <td><strong>Pojazd</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <select 
                                wire:model.live="assignments.{{ $employee->id }}.vehicle_id" 
                                class="form-select"
                            >
                                <option value="">-- Wybierz --</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">
                                        {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    @endforeach
                </tr>
                <tr class="section-vehicle-row">
                    <td><strong>Pozycja</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <select 
                                wire:model.live="assignments.{{ $employee->id }}.position" 
                                class="form-select"
                            >
                                <option value="passenger">Pasażer</option>
                                <option value="driver">Kierowca</option>
                            </select>
                        </td>
                    @endforeach
                </tr>
                <tr class="section-vehicle-row">
                    <td><strong>Data od</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <input 
                                type="date" 
                                wire:model.live="assignments.{{ $employee->id }}.vehicle_start_date"
                                class="form-control"
                            >
                        </td>
                    @endforeach
                </tr>
                <tr class="section-vehicle-row">
                    <td><strong>Data do</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <input 
                                type="date" 
                                wire:model.live="assignments.{{ $employee->id }}.vehicle_end_date"
                                class="form-control"
                            >
                        </td>
                    @endforeach
                </tr>

                <!-- DOM -->
                <tr class="table-success section-header">
                    <td colspan="{{ count($employees) + 1 }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>🏡 DOM</strong>
                            <button 
                                type="button" 
                                wire:click="copyAccommodationFromFirst" 
                                class="btn btn-sm btn-outline-light"
                                title="Skopiuj dane z pierwszego pracownika do wszystkich"
                            >
                                <i class="bi bi-arrow-right"></i> Kopiuj z pierwszego
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="section-accommodation-row">
                    <td><strong>Zakwaterowanie</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <select 
                                wire:model.live="assignments.{{ $employee->id }}.accommodation_id" 
                                class="form-select"
                            >
                                <option value="">-- Wybierz --</option>
                                @foreach($accommodations as $accommodation)
                                    <option value="{{ $accommodation->id }}">
                                        {{ $accommodation->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    @endforeach
                </tr>
                <tr class="section-accommodation-row">
                    <td><strong>Data od</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <input 
                                type="date" 
                                wire:model.live="assignments.{{ $employee->id }}.accommodation_start_date"
                                class="form-control"
                            >
                        </td>
                    @endforeach
                </tr>
                <tr class="section-accommodation-row">
                    <td><strong>Data do</strong></td>
                    @foreach($employees as $employee)
                        <td>
                            <input 
                                type="date" 
                                wire:model.live="assignments.{{ $employee->id }}.accommodation_end_date"
                                class="form-control"
                            >
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Action buttons -->
    <div class="mt-4 d-flex justify-content-between align-items-center gap-2 sticky-bottom bg-white p-3 border-top">
        <a href="{{ route('departures.create') }}" class="btn btn-secondary">
            ← Wróć do kroku 1
        </a>
        
        <button 
            type="button" 
            wire:click="submitAssignments"
            wire:loading.attr="disabled"
            class="btn btn-success btn-lg"
            @if(count($validationErrors) > 0) disabled @endif
        >
            <span wire:loading.remove wire:target="submitAssignments">
                <i class="bi bi-save"></i> Zapisz wyjazd + wszystkie przypisania
            </span>
            <span wire:loading wire:target="submitAssignments">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Zapisuję...
            </span>
        </button>
    </div>
</div>
