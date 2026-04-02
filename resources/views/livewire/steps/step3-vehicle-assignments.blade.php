<div>
    <!-- Form Header: Dates Info -->
    <x-ui.card class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">Data wyjazdu</label>
                <div class="fw-semibold">{{ \Carbon\Carbon::parse($departureDate)->format('d.m.Y') }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">Data przybycia</label>
                <div class="fw-semibold">{{ \Carbon\Carbon::parse($endDate)->format('d.m.Y') }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">Liczba przypisanych pracowników</label>
                <div class="fw-semibold">{{ count($assignedEmployees) }} ({{ count($this->unassignedEmployees) }} do przypisania)</div>
            </div>
        </div>
    </x-ui.card>

    <div class="row g-4">
        <!-- Left Column: Assigned Employees (4/12) -->
        <div class="col-md-4">
            <x-ui.card>
                <h6 class="mb-3">Bez przypisanego pojazdu</h6>
                
                <div class="employee-list">
                    @forelse($this->unassignedEmployees as $employee)
                        <div 
                            class="employee-card mb-3 p-3 border rounded" 
                            draggable="true"
                            data-employee-id="{{ $employee['id'] }}"
                            style="background: rgba(255,255,255,0.05); cursor: grab;"
                            onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.05)'"
                        >
                            <div class="d-flex align-items-start gap-2">
                                @if($employee['image_url'])
                                    <img src="{{ $employee['image_url'] }}" alt="{{ $employee['full_name'] }}" 
                                         class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <span class="text-primary fw-semibold">
                                            {{ substr($employee['first_name'], 0, 1) }}{{ substr($employee['last_name'], 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $employee['full_name'] }}</div>
                                    
                                    <!-- Project Assignments -->
                                    @if(!empty($employee['project_assignments']))
                                        <div class="small mt-2">
                                            <div class="text-muted mb-1">Przypisania do projektów:</div>
                                            @foreach($employee['project_assignments'] as $assignment)
                                                <div class="mb-2 p-2 border rounded" style="background: rgba(59, 130, 246, 0.1);">
                                                    <div class="fw-semibold small">{{ $assignment['project_name'] }}</div>
                                                    <div class="text-muted small">{{ $assignment['role_name'] }}</div>
                                                    <div class="text-muted small mt-1">
                                                        <i class="bi bi-calendar3"></i>
                                                        @foreach($assignment['date_ranges'] as $range)
                                                            {{ $range }}
                                                            @if(!$loop->last), @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    <!-- Vehicle Assignment -->
                                    @if(isset($vehicleAssignments[$employee['id']]))
                                        @php
                                            $vehAssignment = $vehicleAssignments[$employee['id']];
                                            $vehicle = \App\Models\Vehicle::find($vehAssignment['vehicle_id']);
                                            $positionLabel = $vehAssignment['position'] === 'driver' ? 'Kierowca' : 'Pasażer';
                                        @endphp
                                        @if($vehicle)
                                            <div class="small mt-2 p-2 border rounded" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3) !important;">
                                                <div class="fw-semibold small text-success">
                                                    <i class="bi bi-car-front"></i> {{ $vehicle->registration_number }}
                                                    <span class="badge bg-primary ms-1">{{ $positionLabel }}</span>
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    {{ \Carbon\Carbon::parse($vehAssignment['start_date'])->format('d.m.Y') }} - 
                                                    {{ \Carbon\Carbon::parse($vehAssignment['end_date'])->format('d.m.Y') }}
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            Wszyscy mają przypisania.
                        </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <!-- Right Column: Vehicles (8/12) -->
        <div class="col-md-8">
            <x-ui.card>
                <h6 class="mb-3">Czym będą dojeżdżać do pracy?</h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Szukaj po pojeździe</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="vehicleSearch"
                        class="form-control"
                        placeholder="Wpisz rejestrację/markę/model..."
                    >
                </div>
                
                <div class="row g-3">
                    @foreach($this->filteredVehicles as $vehicle)
                        @php
                            $isSelectedVehicle = isset($vehicleId) && (int)$vehicleId === (int)$vehicle['id'];
                            $occupancy = $this->getVehicleOccupancy($vehicle['id']);
                            $projects = $this->getVehicleProjects($vehicle['id']);
                        @endphp
                        <div class="col-md-4">
                            <div class="vehicle-item p-3 border rounded h-100 {{ $isSelectedVehicle ? 'border-primary border-2' : '' }}" 
                                 style="background: {{ $isSelectedVehicle ? 'rgba(59, 130, 246, 0.1)' : 'rgba(255,255,255,0.03)' }};">
                                @if($isSelectedVehicle)
                                    <div class="small text-primary mb-2">
                                        <i class="bi bi-check-circle-fill"></i> Pojazd, którym przyjadą
                                    </div>
                                @endif
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <span class="fw-semibold">{{ $vehicle['registration_number'] }}</span>
                                        @if($vehicle['brand'] || $vehicle['model'])
                                            <div class="small text-muted">
                                                {{ ($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? '') }}
                                            </div>
                                        @endif
                                        @if($vehicle['capacity'])
                                            <div class="small text-muted">
                                                Zajęte: {{ $occupancy['occupied'] }}/{{ $occupancy['capacity'] }} 
                                                @if($occupancy['available'] > 0)
                                                    <span class="text-success">({{ $occupancy['available'] }} wolnych)</span>
                                                @else
                                                    <span class="text-danger">(brak miejsc)</span>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        @if(!empty($projects))
                                            <div class="small mt-2">
                                                <div class="text-muted mb-1">
                                                    <i class="bi bi-briefcase"></i> Projekty:
                                                </div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($projects as $project)
                                                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50" style="font-size: 0.7rem;">
                                                            {{ $project['name'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Drop zone for dragging employee -->
                                <div class="mb-2">
                                    <div 
                                        class="vehicle-drop-zone employee-drop-target border border-2 border-dashed rounded p-3 text-center"
                                        data-vehicle-id="{{ $vehicle['id'] }}"
                                        style="
                                            min-height: 60px;
                                            background: rgba(255,255,255,0.03);
                                            transition: all 0.2s ease;
                                            cursor: pointer;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                        "
                                    >
                                        <div class="text-muted small">
                                            <i class="bi bi-arrow-down-circle d-block mb-1" style="font-size: 1.5rem;"></i>
                                            <span>Kliknij aby przypisać pracownika</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Assigned employees -->
                                @php
                                    $assignedToVehicle = [];
                                    foreach($vehicleAssignments as $empId => $assignment) {
                                        if ($assignment['vehicle_id'] == $vehicle['id']) {
                                            $assignedToVehicle[] = $empId;
                                        }
                                    }
                                @endphp
                                
                                @if(!empty($assignedToVehicle))
                                    <div class="assigned-employees mt-2">
                                        <div class="small text-muted mb-2">Przypisani:</div>
                                        <div class="d-flex flex-column gap-2">
                                            @foreach($assignedToVehicle as $assignedEmployeeId)
                                                @php
                                                    $assignedEmployee = collect($assignedEmployees)->firstWhere('id', $assignedEmployeeId);
                                                    $vehAssignment = $vehicleAssignments[$assignedEmployeeId];
                                                    $positionLabel = $vehAssignment['position'] === 'driver' ? 'Kierowca' : 'Pasażer';
                                                @endphp
                                                @if($assignedEmployee)
                                                    <div class="d-flex align-items-start gap-2 p-2 border rounded" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3) !important;">
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                @if($assignedEmployee['image_url'])
                                                                    <img src="{{ $assignedEmployee['image_url'] }}" 
                                                                         class="rounded-circle" 
                                                                         style="width: 24px; height: 24px; object-fit: cover;">
                                                                @else
                                                                    <div class="bg-success bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                                                         style="width: 24px; height: 24px;">
                                                                        <span class="small fw-semibold" style="font-size: 0.7rem;">
                                                                            {{ substr($assignedEmployee['first_name'], 0, 1) }}{{ substr($assignedEmployee['last_name'], 0, 1) }}
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                                <span class="fw-semibold small">{{ $assignedEmployee['full_name'] }}</span>
                                                                <span class="badge bg-primary">{{ $positionLabel }}</span>
                                                            </div>
                                                            <div class="small text-muted" style="font-size: 0.7rem; margin-left: 32px;">
                                                                <i class="bi bi-calendar3 me-1"></i>
                                                                {{ \Carbon\Carbon::parse($vehAssignment['start_date'])->format('d.m.Y') }} - 
                                                                {{ \Carbon\Carbon::parse($vehAssignment['end_date'])->format('d.m.Y') }}
                                                            </div>
                                                        </div>
                                                        <button 
                                                            type="button"
                                                            class="btn-close btn-close-white"
                                                            style="font-size: 0.6rem; opacity: 0.7;"
                                                            wire:click="removeVehicleAssignment({{ $assignedEmployeeId }})"
                                                            title="Usuń przypisanie"
                                                        ></button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Footer: Navigation -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button 
                    variant="ghost" 
                    wire:click="$dispatch('go-to-step', { step: 2 })"
                    action="cancel"
                >
                    ← Wróć do poprzedniej karty
                </x-ui.button>
                <x-ui.button 
                    variant="primary" 
                    wire:click="$dispatch('go-to-step', { step: 4 })"
                >
                    Przejdź do następnej karty →
                </x-ui.button>
            </div>
        </div>
    </div>

    <!-- Modal: Vehicle Assignment Calendar -->
    @if($showVehicleModal && $selectedEmployee && $selectedVehicle)
        <div class="modal-portal-to-body">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show employee-assignment-modal" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Przypisz pojazd: {{ $selectedEmployee['full_name'] }}
                            <br>
                            <small class="text-muted">
                                {{ $selectedVehicle->registration_number }}
                            </small>
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeVehicleModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Role Selection -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rola w pojeździe:</label>
                            <select class="form-select" wire:model="selectedPosition">
                                <option value="passenger">Pasażer</option>
                                <option value="driver">Kierowca</option>
                            </select>
                        </div>
                        
                        <p class="text-muted small mb-3">
                            Wybierz zakres dat przypisania. Kliknij datę początkową, a następnie datę końcową.
                            Wyszarzone dni są niedostępne (brak miejsc).
                        </p>
                        
                        @if($selectedStartDate)
                            <div class="alert alert-info mb-3">
                                @if($selectedEndDate && $selectedStartDate !== $selectedEndDate)
                                    <strong>Wybrany zakres:</strong> 
                                    {{ \Carbon\Carbon::parse($selectedStartDate)->format('d.m.Y') }} - 
                                    {{ \Carbon\Carbon::parse($selectedEndDate)->format('d.m.Y') }}
                                    <br>
                                    <small>Kliknij inną datę aby zmienić zakres końcowy</small>
                                @else
                                    <strong>Wybrana data początkowa:</strong> 
                                    {{ \Carbon\Carbon::parse($selectedStartDate)->format('d.m.Y') }}
                                    <br>
                                    <small>Kliknij datę końcową aby wybrać zakres</small>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-secondary mb-3">
                                <small>Kliknij datę początkową przypisania</small>
                            </div>
                        @endif
                        
                        @php
                            $calStart = $calendarMonthStart ? \Carbon\Carbon::parse($calendarMonthStart) : $arrivalDate->copy()->startOfMonth();
                        @endphp
                        <x-ui.cal 
                            :startDate="$calStart->format('Y-m-d')"
                            :days="0"
                            :availability="$vehicleAvailability"
                            :selectedStartDate="$selectedStartDate"
                            :selectedEndDate="$selectedEndDate"
                            onDateClick="selectDate"
                            :showMonthNavigation="true"
                            onPreviousMonth="wire:click=previousMonth"
                            onNextMonth="wire:click=nextMonth"
                            :arrivalDate="$arrivalDate->format('Y-m-d')"
                        />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeVehicleModal">Anuluj</button>
                        @if($selectedStartDate)
                            <button type="button" class="btn btn-primary" wire:click="confirmVehicleAssignment">
                                Potwierdź przypisanie
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        </div>
    @endif
</div>

@script
<script>
    let draggedEmployeeId = null;

    // Employee cards - drag start
    document.addEventListener('dragstart', function(e) {
        const card = e.target.closest('.employee-card');
        if (card && card.hasAttribute('draggable')) {
            const employeeId = card.getAttribute('data-employee-id');
            if (employeeId) {
                draggedEmployeeId = employeeId;
                e.dataTransfer.setData('text/plain', employeeId);
                e.dataTransfer.effectAllowed = 'move';
                card.style.opacity = '0.5';
            }
        }
    }, true);
    
    // Employee cards - drag end
    document.addEventListener('dragend', function(e) {
        const card = e.target.closest('.employee-card');
        if (card) {
            card.style.opacity = '1';
        }
        draggedEmployeeId = null;
    }, true);

    // Drag over - drop zones
    document.addEventListener('dragover', function(e) {
        const dropZone = e.target.closest('.vehicle-drop-zone');
        const dropTarget = e.target.closest('.employee-drop-target');
        if (dropZone || dropTarget) {
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'move';
            
            const target = dropTarget || dropZone;
            if (target) {
                target.style.background = 'rgba(59, 130, 246, 0.15)';
                target.style.borderColor = 'rgba(59, 130, 246, 0.8)';
                target.style.borderStyle = 'solid';
            }
        }
    }, true);

    // Drag enter - drop zones
    document.addEventListener('dragenter', function(e) {
        const dropZone = e.target.closest('.vehicle-drop-zone');
        const dropTarget = e.target.closest('.employee-drop-target');
        
        if (dropTarget || dropZone) {
            e.preventDefault();
            const target = dropTarget || dropZone;
            if (target) {
                target.style.background = 'rgba(59, 130, 246, 0.15)';
                target.style.borderColor = 'rgba(59, 130, 246, 0.8)';
                target.style.borderStyle = 'solid';
                target.style.transform = 'scale(1.02)';
            }
        }
    }, true);

    // Drag leave - drop zones
    document.addEventListener('dragleave', function(e) {
        const dropZone = e.target.closest('.vehicle-drop-zone');
        const dropTarget = e.target.closest('.employee-drop-target');
        
        if ((dropTarget || dropZone) && !dropZone?.contains(e.relatedTarget)) {
            const target = dropTarget || dropZone;
            if (target) {
                target.style.background = '';
                target.style.borderColor = '';
                target.style.borderStyle = '';
                target.style.transform = '';
            }
        }
    }, true);

    // Drop - vehicle drop zone
    document.addEventListener('drop', function(e) {
        const dropZone = e.target.closest('.vehicle-drop-zone');
        const dropTarget = e.target.closest('.employee-drop-target');
        
        if (dropZone || dropTarget) {
            e.preventDefault();
            e.stopPropagation();
            
            const employeeId = e.dataTransfer.getData('text/plain') || draggedEmployeeId;
            const zone = dropZone || dropTarget?.closest('.vehicle-drop-zone');
            const vehicleId = parseInt(zone?.getAttribute('data-vehicle-id'));
            
            if (employeeId && vehicleId) {
                const target = dropTarget || zone;
                if (target) {
                    target.style.background = '';
                    target.style.borderColor = '';
                    target.style.borderStyle = '';
                    target.style.transform = '';
                }
                
                // Open modal with employee calendar
                $wire.openVehicleModal(
                    parseInt(employeeId),
                    vehicleId
                );
            }
        }
    }, true);

    // Handle click on vehicle drop zone (fallback)
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.vehicle-drop-zone').forEach(function(zone) {
            zone.addEventListener('click', function(e) {
                // Only handle click if not dragging
                if (!draggedEmployeeId) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const vehicleId = parseInt(this.getAttribute('data-vehicle-id'));
                    
                    // Get unassigned employees
                    const unassignedEmployees = @this.unassignedEmployees;
                    
                    if (unassignedEmployees.length === 0) {
                        alert('Wszyscy pracownicy mają już przypisany pojazd. Usuń przypisanie aby zmienić.');
                        return;
                    }
                    
                    if (unassignedEmployees.length === 1) {
                        // Only one unassigned employee, open modal directly
                        if (typeof $wire !== 'undefined') {
                            $wire.openVehicleModal(unassignedEmployees[0].id, vehicleId);
                        }
                    } else {
                        // Multiple unassigned employees, show selection
                        const selectedId = prompt(
                            `Wybierz pracownika do przypisania:\n\n${unassignedEmployees.map((emp, idx) => `${idx + 1}. ${emp.full_name}`).join('\n')}\n\nWpisz numer:`,
                            '1'
                        );
                        
                        if (selectedId) {
                            const index = parseInt(selectedId) - 1;
                            if (index >= 0 && index < unassignedEmployees.length && typeof $wire !== 'undefined') {
                                $wire.openVehicleModal(unassignedEmployees[index].id, vehicleId);
                            }
                        }
                    }
                }
            });
        });
    });
</script>
@endscript
