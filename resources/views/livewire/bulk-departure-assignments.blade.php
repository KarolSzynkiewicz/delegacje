<div>
    <!-- Walidator na górze -->
    @if(count($validationErrors) > 0)
        <div class="alert alert-danger mb-4">
            <h5 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Znaleziono {{ count($validationErrors) }} {{ count($validationErrors) == 1 ? 'problem' : 'problemów' }}</h5>
            @foreach($validationErrors as $employeeId => $data)
                <div class="mb-2">
                    <strong>{{ $data['name'] }}:</strong>
                    <ul class="mb-0">
                        @foreach($data['errors'] as $error)
                            <li>{{ $error }}</li>
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
                    <td colspan="{{ count($employees) + 1 }}"><strong>🏢 PROJEKT</strong></td>
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
                    <td colspan="{{ count($employees) + 1 }}"><strong>🚗 AUTO</strong></td>
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
                                        {{ $vehicle->registration_number }}
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
                    <td colspan="{{ count($employees) + 1 }}"><strong>🏡 DOM</strong></td>
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
    
    <!-- Hidden inputs dla submita formularza -->
    @foreach($employees as $employee)
        @foreach($assignments[$employee->id] as $key => $value)
            <input type="hidden" name="assignments[{{ $employee->id }}][{{ $key }}]" value="{{ $value }}">
        @endforeach
    @endforeach
</div>
