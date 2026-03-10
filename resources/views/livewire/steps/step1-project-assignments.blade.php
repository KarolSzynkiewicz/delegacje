<div>
    <!-- Vehicle Seats Placeholders or No Vehicle List -->
    @if($vehicleId && $vehicle && $vehicle->capacity)
        <x-ui.card class="mb-4">
            <h6 class="mb-3">Miejsca w pojeździe ({{ $vehicle->registration_number }})</h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach($vehicleSeats as $index => $seat)
                    <div 
                        class="vehicle-seat-placeholder border rounded p-3 text-center"
                        style="min-width: 120px; background: rgba(255,255,255,0.05);"
                        wire:key="vehicle-seat-{{ $index }}-{{ $seat['employee_id'] ?? 'empty' }}-{{ $seat['position'] ?? 'passenger' }}"
                    >
                        @if(!empty($seat['employee_id']))
                            @php
                                $employee = collect($allAvailableEmployees)->firstWhere('id', $seat['employee_id']);
                            @endphp
                            @if($employee)
                                <div class="d-flex align-items-center gap-2">
                                    @if($employee['image_url'])
                                        <img src="{{ $employee['image_url'] }}" alt="{{ $employee['full_name'] }}" 
                                             class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <span class="text-primary fw-semibold small">
                                                {{ substr($employee['first_name'], 0, 1) }}{{ substr($employee['last_name'], 0, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 text-start">
                                        <div class="small fw-semibold">{{ $employee['full_name'] }}</div>
                                        <select 
                                            wire:change="updateVehicleSeatPosition({{ $index }}, $event.target.value)"
                                            class="form-select"
                                        >
                                            <option value="driver" {{ $seat['position'] == 'driver' ? 'selected' : '' }}>Kierowca</option>
                                            <option value="passenger" {{ $seat['position'] == 'passenger' ? 'selected' : '' }}>Pasażer</option>
                                        </select>
                                    </div>
                                    <button 
                                        type="button"
                                        wire:click="updateVehicleSeat({{ $index }}, null)"
                                        class="btn btn-sm btn-link text-danger p-0"
                                        title="Usuń"
                                    >
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            @endif
                        @else
                            <div class="text-muted small">
                                <i class="bi bi-person"></i><br>
                                Miejsce {{ $index + 1 }}<br>
                                <small>{{ $seat['position'] == 'driver' ? 'Kierowca' : 'Pasażer' }}</small>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @elseif(!$vehicleId && (!empty($assignmentRanges) || !empty($assignments)))
        @php
            $assignedEmployees = $this->getAssignedEmployeesForNoVehicle();
        @endphp
        @if(!empty($assignedEmployees))
            <x-ui.card class="mb-4">
                <h6 class="mb-3">wyjazd transportem publicznym lub własnym</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($assignedEmployees as $employee)
                    <div 
                        class="border rounded p-3"
                        style="min-width: 200px; background: rgba(255,255,255,0.05);"
                        wire:key="no-vehicle-employee-{{ $employee['id'] }}"
                    >
                        <div class="d-flex align-items-center gap-2">
                            @if($employee['image_url'])
                                <img src="{{ $employee['image_url'] }}" alt="{{ $employee['full_name'] }}" 
                                     class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            @else
                                <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px;">
                                    <span class="text-primary fw-semibold small">
                                        {{ substr($employee['first_name'], 0, 1) }}{{ substr($employee['last_name'], 0, 1) }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex-grow-1 text-start">
                                <div class="small fw-semibold">{{ $employee['full_name'] }}</div>
                                @if(!empty($employee['roles']))
                                    <div class="small mt-1">
                                        @foreach($employee['roles'] as $role)
                                            <x-ui.badge variant="accent" class="me-1 small">{{ $role['name'] }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
        @endif
    @endif

    <!-- Main Layout: 4/12 + 8/12 -->
    <div class="row g-4">
        <!-- Left Column: Available Employees (4/12) -->
        <div class="col-md-4">
            <x-ui.card>
                <h5 class="mb-3">Dostępni pracownicy</h5>
                
                <!-- Role filter -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Filtruj po roli</label>
                    <select 
                        wire:model.live="roleFilter" 
                        class="form-select"
                    >
                        <option value="">Wszystkie role</option>
                        @foreach(\App\Models\Role::orderBy('name')->get() as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <p class="small text-muted mb-3">
                    Przeciągnij pracownika do luki w projekcie
                </p>
                
                <div class="employee-list">
                    @forelse($this->getFilteredEmployees() as $employee)
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
                                    <div class="small mt-1">
                                        @foreach($employee['roles'] as $role)
                                            <x-ui.badge variant="accent" class="me-1 small">{{ $role['name'] }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                    @if($employee['rotation'])
                                        <div class="small text-info mt-1">
                                            <i class="bi bi-arrow-repeat"></i> Rotacja: 
                                            {{ \Carbon\Carbon::parse($employee['rotation']['start_date'])->format('d.m.Y') }}
                                            @if($employee['rotation']['end_date'])
                                                - {{ \Carbon\Carbon::parse($employee['rotation']['end_date'])->format('d.m.Y') }}
                                            @else
                                                (bezterminowa)
                                            @endif
                                        </div>
                                    @endif
                                    @if(!empty($employee['expiring_documents']))
                                        @php
                                            // Sort documents: required first, then others; expired first within each group
                                            $sortedDocs = collect($employee['expiring_documents'])->sortBy(function($doc) {
                                                $priority = 0;
                                                // Required documents first
                                                if ($doc['is_required']) {
                                                    $priority += 0;
                                                } else {
                                                    $priority += 1000;
                                                }
                                                // Expired documents first within each group
                                                if ($doc['is_expired'] ?? false) {
                                                    $priority += 0;
                                                } else {
                                                    $priority += 100;
                                                }
                                                return $priority;
                                            })->values();
                                        @endphp
                                        <div class="small mt-1">
                                            <i class="bi bi-exclamation-triangle"></i> Dokumenty:
                                            @foreach($sortedDocs as $doc)
                                                @php
                                                    $isExpired = $doc['is_expired'] ?? false;
                                                    $isRequired = $doc['is_required'] ?? false;
                                                    $isMissing = $doc['is_missing'] ?? false;
                                                    
                                                    // Określ tekst dla dokumentu
                                                    if ($isMissing) {
                                                        $daysText = 'brak dokumentu';
                                                    } elseif ($isExpired && isset($doc['days_until_expiry'])) {
                                                        $daysText = 'wygasł ' . abs($doc['days_until_expiry']) . ' dni temu';
                                                    } elseif ($isExpired) {
                                                        $daysText = 'wygasł';
                                                    } elseif (isset($doc['days_until_expiry'])) {
                                                        $daysText = 'wygasa za ' . $doc['days_until_expiry'] . ' dni';
                                                    } else {
                                                        $daysText = 'wygasł';
                                                    }
                                                    
                                                    $color = $isRequired ? '#ef4444' : '#f59e0b';
                                                @endphp
                                                <span style="color: {{ $color }}; font-weight: {{ $isRequired ? 'bold' : 'normal' }};">
                                                    {{ $doc['document_name'] }} ({{ $daysText }})
                                                </span>
                                                @if(!$loop->last)
                                                    <span class="text-muted">, </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            @if($roleFilter)
                                Brak dostępnych pracowników z wybraną rolą na wybraną datę.
                            @else
                                Brak dostępnych pracowników na wybraną datę.
                            @endif
                        </div>
                    @endforelse
                    
                    @if($hasMoreEmployees)
                        <div class="text-center mt-3">
                            <button 
                                type="button" 
                                class="btn btn-sm btn-outline-primary"
                                wire:click="loadMoreEmployees"
                            >
                                <i class="bi bi-arrow-down"></i> Załaduj więcej
                            </button>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </div>

        <!-- Right Column: Project Gaps List (8/12) -->
        <div class="col-md-8">
            <x-ui.card>
                <h5 class="mb-3">Jakich ludzi brakuje po przyjeździe?</h5>
                <p class="small text-muted mb-3">
                    Braki w rolach na najbliższe 2 tygodnie (14 dni od przybycia). Zakres pokazuje minimalną i maksymalną liczbę braków w ciągu 14 dni.
                </p>
                
                <div class="project-gaps-list">
                    @if(!empty($projectGapsTwoWeeks))
                        @foreach($projectGapsTwoWeeks as $projectId => $project)
                            <div class="project-item mb-4 p-3 border rounded">
                                <div class="d-flex align-items-start gap-2 mb-3">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-semibold mb-1">{{ $project['name'] }}</h6>
                                        @if($project['location'])
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt"></i> {{ $project['location'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    @foreach($project['roles'] as $roleId => $role)
                                        <div class="col-md-4">
                                            <div class="role-item mb-3 p-3 border rounded h-100" style="background: rgba(255,255,255,0.03);">
                                                @php
                                                    // Calculate assigned employees for this role in this project
                                                    $assignedEmployees = [];
                                                    
                                                    // From day-based assignments
                                                    foreach($assignments as $dayKey => $dayAssignments) {
                                                        if (isset($dayAssignments[$projectId][$roleId])) {
                                                            foreach($dayAssignments[$projectId][$roleId] as $empId) {
                                                                if (!in_array($empId, $assignedEmployees)) {
                                                                    $assignedEmployees[] = $empId;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    // From range-based assignments
                                                    foreach($assignmentRanges as $range) {
                                                        if ($range['project_id'] == $projectId && $range['role_id'] == $roleId) {
                                                            if (!in_array($range['employee_id'], $assignedEmployees)) {
                                                                $assignedEmployees[] = $range['employee_id'];
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <span class="fw-semibold">{{ $role['name'] }}</span>
                                                        @if($role['min_gaps'] == $role['max_gaps'])
                                                            <x-ui.alert variant="warning" class="d-inline-flex align-items-center ms-2 py-1 px-2">
                                                                Braki: {{ $role['min_gaps'] }}
                                                            </x-ui.alert>
                                                        @else
                                                            <x-ui.alert variant="warning" class="d-inline-flex align-items-center ms-2 py-1 px-2">
                                                                Braki: {{ $role['min_gaps'] }}-{{ $role['max_gaps'] }}
                                                            </x-ui.alert>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- Drop zone for dragging employee -->
                                                <div class="mb-2">
                                                    <div 
                                                        class="employee-dropdown-zone employee-drop-target border border-2 border-dashed rounded p-3 text-center"
                                                        data-project-id="{{ $projectId }}"
                                                        data-role-id="{{ $roleId }}"
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
                                                            <span>Przeciągnij pracownika tutaj</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Assigned employees -->
                                                @if(!empty($assignedEmployees))
                                                    <div class="assigned-employees mt-2">
                                                        <div class="small text-muted mb-2">Przypisani:</div>
                                                        <div class="d-flex flex-column gap-2">
                                                            @foreach($assignedEmployees as $assignedEmployeeId)
                                                                @php
                                                                    $assignedEmployee = collect($allAvailableEmployees)->firstWhere('id', $assignedEmployeeId);
                                                                @endphp
                                                                @if($assignedEmployee)
                                                                    <div class="d-flex align-items-start gap-2 p-2 border rounded" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3) !important;">
                                                                        <div class="flex-grow-1">
                                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                                @if($assignedEmployee['image_url'])
                                                                                    <img src="{{ $assignedEmployee['image_url'] }}" 
                                                                                         class="rounded-circle" 
                                                                                         style="width: 24px; height: 24px; object-fit: cover;">
                                                                                @else
                                                                                    <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                                                                         style="width: 24px; height: 24px;">
                                                                                        <span class="small fw-semibold" style="font-size: 0.7rem;">
                                                                                            {{ substr($assignedEmployee['first_name'], 0, 1) }}{{ substr($assignedEmployee['last_name'], 0, 1) }}
                                                                                        </span>
                                                                                    </div>
                                                                                @endif
                                                                                <span class="fw-semibold small">{{ $assignedEmployee['full_name'] }}</span>
                                                                            </div>
                                                                        </div>
                                                                        <button 
                                                                            type="button"
                                                                            class="btn-close btn-close-white"
                                                                            style="font-size: 0.6rem; opacity: 0.7;"
                                                                            onclick="$dispatch('assignment-range-removed', { employee_id: {{ $assignedEmployeeId }}, project_id: {{ $projectId }}, role_id: {{ $roleId }} })"
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
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mt-2">Brak braków w rolach na najbliższe 2 tygodnie</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.index') }}"
                    action="cancel"
                >
                    Anuluj
                </x-ui.button>
                <x-ui.button 
                    variant="primary" 
                    wire:click="goToNextStep"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="goToNextStep">
                        Dalej
                    </span>
                    <span wire:loading wire:target="goToNextStep">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Przetwarzanie...
                    </span>
                </x-ui.button>
            </div>
        </div>
    </div>

    <!-- Modal: Employee Assignment Calendar -->
    @if($showEmployeeModal && $selectedEmployee && $selectedProject && $selectedRole)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Przypisz pracownika: {{ $selectedEmployee['full_name'] }}
                            <br>
                            <small class="text-muted">
                                {{ $selectedProject->name }} - {{ $selectedRole->name }}
                            </small>
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeEmployeeModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Wybierz zakres dat przypisania. Kliknij datę początkową, a następnie datę końcową.
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
                                    <small>Kliknij datę końcową aby zakończyć wybór</small>
                                @endif
                            </div>
                        @endif
                        
                        @php
                            $arrivalDate = \Carbon\Carbon::parse($endDate);
                            $calStart = $calendarMonthStart ? \Carbon\Carbon::parse($calendarMonthStart) : $arrivalDate->copy()->startOfMonth();
                        @endphp
                        <x-ui.cal
                            :startDate="$calStart->format('Y-m-d')"
                            :days="0"
                            :availability="$employeeAvailability"
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
                        <button type="button" class="btn btn-secondary" wire:click="closeEmployeeModal">Anuluj</button>
                        @if($selectedStartDate)
                            <button type="button" class="btn btn-primary" wire:click="confirmAssignment">
                                Potwierdź przypisanie
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>

@script
<script>
    let draggedEmployeeId = null;
    let autoScrollInterval = null;
    let isDragging = false;

    // Auto-scroll during drag
    function startAutoScroll(e) {
        if (!isDragging) return;
        
        const viewportHeight = window.innerHeight;
        const scrollThreshold = 100; // Distance from top/bottom in pixels to trigger scroll
        const scrollSpeed = 10; // Pixels per scroll step
        const mouseY = e.clientY;
        
        // Check if mouse is near top or bottom of viewport
        if (mouseY < scrollThreshold) {
            // Scroll up
            if (autoScrollInterval) clearInterval(autoScrollInterval);
            autoScrollInterval = setInterval(() => {
                if (!isDragging) {
                    clearInterval(autoScrollInterval);
                    return;
                }
                window.scrollBy(0, -scrollSpeed);
            }, 10);
        } else if (mouseY > viewportHeight - scrollThreshold) {
            // Scroll down
            if (autoScrollInterval) clearInterval(autoScrollInterval);
            autoScrollInterval = setInterval(() => {
                if (!isDragging) {
                    clearInterval(autoScrollInterval);
                    return;
                }
                window.scrollBy(0, scrollSpeed);
            }, 10);
        } else {
            // Stop scrolling if mouse is in middle area
            if (autoScrollInterval) {
                clearInterval(autoScrollInterval);
                autoScrollInterval = null;
            }
        }
    }

    // Employee cards - drag start
    document.addEventListener('dragstart', function(e) {
        const card = e.target.closest('.employee-card');
        if (card && card.hasAttribute('draggable')) {
            const employeeId = card.getAttribute('data-employee-id');
            if (employeeId) {
                draggedEmployeeId = employeeId;
                isDragging = true;
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
        isDragging = false;
        if (autoScrollInterval) {
            clearInterval(autoScrollInterval);
            autoScrollInterval = null;
        }
    }, true);
    
    // Auto-scroll during drag over
    document.addEventListener('dragover', function(e) {
        if (isDragging) {
            startAutoScroll(e);
        }
    }, true);

    // Drag over - drop zones (project/role only)
    document.addEventListener('dragover', function(e) {
        const dropTarget = e.target.closest('.employee-drop-target');
        if (dropTarget) {
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'move';
            
            dropTarget.style.background = 'rgba(59, 130, 246, 0.15)';
            dropTarget.style.borderColor = 'rgba(59, 130, 246, 0.8)';
            dropTarget.style.borderStyle = 'solid';
        }
    }, true);

    // Drag enter - drop zones (project/role only)
    document.addEventListener('dragenter', function(e) {
        const dropTarget = e.target.closest('.employee-drop-target');
        
        if (dropTarget) {
            e.preventDefault();
            dropTarget.style.background = 'rgba(59, 130, 246, 0.15)';
            dropTarget.style.borderColor = 'rgba(59, 130, 246, 0.8)';
            dropTarget.style.borderStyle = 'solid';
            dropTarget.style.transform = 'scale(1.02)';
        }
    }, true);

    // Drag leave - drop zones (project/role only)
    document.addEventListener('dragleave', function(e) {
        const dropTarget = e.target.closest('.employee-drop-target');
        
        if (dropTarget && !dropTarget.contains(e.relatedTarget)) {
            dropTarget.style.background = '';
            dropTarget.style.borderColor = '';
            dropTarget.style.borderStyle = '';
            dropTarget.style.transform = '';
        }
    }, true);

    // Drop - drop zones (project/role only)
    document.addEventListener('drop', function(e) {
        const dropTarget = e.target.closest('.employee-drop-target');
        
        if (dropTarget) {
            e.preventDefault();
            e.stopPropagation();
            
            const employeeId = e.dataTransfer.getData('text/plain') || draggedEmployeeId;
            const projectId = parseInt(dropTarget.getAttribute('data-project-id'));
            const roleId = parseInt(dropTarget.getAttribute('data-role-id'));
            
            if (employeeId && projectId && roleId) {
                dropTarget.style.background = '';
                dropTarget.style.borderColor = '';
                dropTarget.style.borderStyle = '';
                dropTarget.style.transform = '';
                
                // Open modal with employee calendar
                $wire.openEmployeeModal(
                    parseInt(employeeId),
                    projectId,
                    roleId
                );
            }
        }
    }, true);
</script>
@endscript
