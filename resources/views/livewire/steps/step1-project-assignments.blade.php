<div>
<style>
/* ── Step 1 – redesign ────────────────────────────────────────── */
.s1-panel { height: 100%; }

/* Left: compact filters */
.s1-filters { display:flex; flex-direction:column; gap:8px; margin-bottom:14px; }
.s1-filters .form-select-sm,
.s1-filters .form-control-sm { border-radius:10px !important; }

/* Employee card – compact chip-style */
.s1-emp-card {
    display:flex; align-items:center; gap:10px;
    padding: 8px 10px;
    border-radius: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    cursor: grab;
    transition: all .15s ease;
    margin-bottom: 6px;
}
.s1-emp-card:hover {
    background: rgba(59,130,246,0.08);
    border-color: rgba(59,130,246,0.3);
}
.s1-emp-avatar {
    width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.75rem; flex:0 0 auto;
    background: rgba(59,130,246,0.2); color:#93c5fd;
}
.s1-emp-avatar img { width:34px; height:34px; border-radius:50%; object-fit:cover; }
.s1-emp-name { font-size:.85rem; font-weight:600; line-height:1.2; }
.s1-emp-roles { display:flex; flex-wrap:wrap; gap:3px; margin-top:3px; }
.s1-emp-role-pill {
    font-size:.68rem; padding:1px 7px; border-radius:20px;
    background:rgba(168,85,247,0.10); border:1px solid rgba(168,85,247,0.18); color:#c4b5fd;
}
.s1-emp-doc-warn { font-size:.68rem; color:#fbbf24; margin-top:3px; }
.s1-emp-rotation { font-size:.68rem; color:#94a3b8; margin-top:2px; opacity:.75; }
.s1-emp-drag-hint {
    font-size:.68rem; color:rgba(148,163,184,0.5);
    margin-top:3px; display:flex; align-items:center; gap:3px;
}
.s1-full-banner {
    border-radius:10px;
    padding: 8px 12px;
    background: rgba(239,68,68,0.09);
    border: 1px solid rgba(239,68,68,0.22);
    color: #fca5a5;
    font-size: .8rem;
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}

/* Right: project + role cards */
.s1-project-block {
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.02);
    padding: 14px 16px;
    margin-bottom: 16px;
}
.s1-project-header { margin-bottom: 12px; }
.s1-project-name { font-size:.95rem; font-weight:700; }
.s1-project-loc { font-size:.75rem; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:4px; }
.s1-roles-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; }
@media(max-width:900px){ .s1-roles-grid{ grid-template-columns: repeat(2,1fr); } }
@media(max-width:600px){ .s1-roles-grid{ grid-template-columns: 1fr; } }

.s1-role-card {
    border-radius: 12px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    padding: 10px 12px;
    display: flex; flex-direction: column; gap: 8px;
}
.s1-role-header { display:flex; align-items:center; justify-content:space-between; gap:6px; }
.s1-role-name { font-size:.82rem; font-weight:700; }
.s1-gap-pill {
    font-size:.68rem; padding:2px 8px; border-radius:20px; white-space:nowrap;
    background:rgba(245,158,11,0.09); border:1px solid rgba(245,158,11,0.18); color:#d97706;
    display:flex; align-items:center; gap:4px;
}

/* Drop zone */
.s1-drop-zone {
    border-radius: 10px;
    border: 1px dashed rgba(255,255,255,0.10);
    background: rgba(255,255,255,0.01);
    min-height: 48px;
    display: flex; flex-direction:row; align-items: center; justify-content: center; gap: 6px;
    transition: all .15s ease;
    cursor: pointer;
    padding: 6px 10px;
}
.s1-drop-zone:hover,
.s1-drop-zone.drag-over {
    border-color: rgba(59,130,246,0.40);
    background: rgba(59,130,246,0.05);
}
.s1-drop-hint { font-size:.72rem; color:rgba(148,163,184,0.40); line-height:1.3; }

/* Assigned chips */
.s1-assigned-list { display:flex; flex-direction:column; gap:5px; }
.s1-assigned-chip {
    display:flex; align-items:center; gap:7px;
    border-radius: 8px; padding: 5px 8px;
    background: rgba(59,130,246,0.09);
    border: 1px solid rgba(59,130,246,0.22);
    font-size:.78rem;
}
.s1-chip-avatar {
    width:22px; height:22px; border-radius:50%;
    background: rgba(59,130,246,0.25); color:#93c5fd;
    display:flex; align-items:center; justify-content:center;
    font-size:.63rem; font-weight:700; flex:0 0 auto;
}
.s1-chip-avatar img { width:22px; height:22px; border-radius:50%; object-fit:cover; }
.s1-chip-name { flex:1 1 auto; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.s1-chip-remove {
    background:none; border:none; padding:0; line-height:1;
    color:rgba(148,163,184,0.5); cursor:pointer; font-size:.75rem; flex:0 0 auto;
}
.s1-chip-remove:hover { color:#fca5a5; }

/* Left panel hint text */
.s1-hint { font-size:.77rem; color:rgba(148,163,184,0.55); margin-bottom:10px; display:flex; align-items:center; gap:5px; }
</style>

    <!-- Main Layout: 4/12 + 8/12 -->
    <div class="row g-4">
        <!-- Left Column: Available Employees (4/12) -->
        <div class="col-md-4">
            <x-ui.card class="s1-panel">
                @php
                    $tripEmployeeIds = [];
                    foreach (($assignments ?? []) as $dayAssignments) {
                        foreach (($dayAssignments ?? []) as $projAssignments) {
                            foreach (($projAssignments ?? []) as $roleAssignments) {
                                foreach (($roleAssignments ?? []) as $empId) { $tripEmployeeIds[] = (int) $empId; }
                            }
                        }
                    }
                    foreach (($assignmentRanges ?? []) as $r) {
                        if (!empty($r['employee_id'])) $tripEmployeeIds[] = (int) $r['employee_id'];
                    }
                    $tripEmployeeIds = array_values(array_unique(array_filter($tripEmployeeIds)));
                    $capacity = is_array($vehicleSeats) ? count($vehicleSeats) : 0;
                    $isExternalDriver = (bool) (($vehicleSeats[0]['external_driver'] ?? true) ?? true);
                    $isOwnTransport = !empty($vehicleId) && $capacity > 0;
                    $totalTripPeople = count($tripEmployeeIds) + ($isExternalDriver ? 1 : 0);
                    $isVehicleFull = $isOwnTransport && $totalTripPeople >= $capacity;
                @endphp

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="fw-semibold" style="font-size:.9rem;">
                        {{ $forTransfer ? 'Uczestnicy transferu' : 'Dostępni pracownicy' }}
                    </span>
                    @if($isVehicleFull && !$forTransfer)
                        <span style="font-size:.7rem; background:rgba(239,68,68,0.14); color:#fca5a5; border:1px solid rgba(239,68,68,0.28); border-radius:20px; padding:2px 8px;">
                            <i class="bi bi-x-octagon-fill me-1"></i>Wyjazd pełny
                        </span>
                    @endif
                </div>

                @if($isVehicleFull && !$forTransfer)
                    <div class="s1-full-banner">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        Brak miejsca w aucie — nie możesz dodać więcej osób do tego wyjazdu.
                    </div>
                @endif

                <!-- Compact filters -->
                <div class="s1-filters">
                    <input type="text"
                           wire:model.live.debounce.300ms="employeeSearch"
                           class="form-control form-control-sm"
                           placeholder="Szukaj pracownika…">
                </div>

                <div class="s1-hint">
                    <i class="bi bi-grip-horizontal"></i>
                    Przeciągnij pracownika na rolę po prawej
                </div>

                <div class="employee-list">
                    @forelse($this->getFilteredEmployees() as $employee)
                        <div class="s1-emp-card"
                             draggable="true"
                             data-employee-id="{{ $employee['id'] }}">
                            <div class="s1-emp-avatar">
                                @if($employee['image_url'])
                                    <img src="{{ $employee['image_url'] }}" alt="">
                                @else
                                    {{ substr($employee['first_name'],0,1) }}{{ substr($employee['last_name'],0,1) }}
                                @endif
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="s1-emp-name text-truncate">{{ $employee['full_name'] }}</div>
                                <div class="s1-emp-roles">
                                    @foreach($employee['roles'] as $role)
                                        <span class="s1-emp-role-pill">{{ $role['name'] }}</span>
                                    @endforeach
                                </div>
                                @if($employee['rotation'])
                                    <div class="s1-emp-rotation">
                                        <i class="bi bi-arrow-repeat"></i>
                                        {{ \Carbon\Carbon::parse($employee['rotation']['start_date'])->format('d.m.Y') }}
                                        @if($employee['rotation']['end_date'])
                                            – {{ \Carbon\Carbon::parse($employee['rotation']['end_date'])->format('d.m.Y') }}
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($employee['expiring_documents']))
                                    @php
                                        $criticalDocs = collect($employee['expiring_documents'])
                                            ->filter(fn($d) => $d['is_required'] ?? false)->count();
                                    @endphp
                                    <div class="s1-emp-doc-warn">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        {{ count($employee['expiring_documents']) }} dok.
                                        @if($criticalDocs) · {{ $criticalDocs }} wymaganych @endif
                                    </div>
                                @endif
                            </div>
                            <i class="bi bi-grip-vertical text-muted" style="opacity:.3; font-size:.8rem; flex:0 0 auto;"></i>
                        </div>
                    @empty
                        <div style="text-align:center; padding:24px 0; color:rgba(148,163,184,0.5);">
                            <i class="bi bi-person-x" style="font-size:1.6rem; display:block; margin-bottom:6px;"></i>
                            <span style="font-size:.82rem;">
                                @if($forTransfer)
                                    Brak uczestników
                                @elseif($roleFilter || filled($employeeSearch))
                                    Brak wyników dla filtrów
                                @else
                                    Brak dostępnych pracowników
                                @endif
                            </span>
                        </div>
                    @endforelse

                    @php
                        $totalEmployees = count($allAvailableEmployees);
                        $totalPages = $totalEmployees > 0 ? (int)ceil($totalEmployees / $employeesPerPage) : 1;
                        $from = (($employeesPage - 1) * $employeesPerPage) + 1;
                        $to = min($employeesPage * $employeesPerPage, $totalEmployees);
                    @endphp
                    @if($totalPages > 1)
                        <div class="d-flex align-items-center justify-content-between mt-3 pt-2"
                             style="border-top:1px solid rgba(255,255,255,0.07);">
                            <button type="button"
                                    class="btn btn-sm"
                                    style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; font-size:.75rem; padding:3px 10px; color:{{ $employeesPage <= 1 ? 'rgba(148,163,184,0.3)' : '#94a3b8' }};"
                                    wire:click="loadPrevEmployees"
                                    @if($employeesPage <= 1) disabled @endif>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span style="font-size:.72rem; color:rgba(148,163,184,0.6);">
                                {{ $from }}–{{ $to }} / {{ $totalEmployees }}
                            </span>
                            <button type="button"
                                    class="btn btn-sm"
                                    style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; font-size:.75rem; padding:3px 10px; color:{{ $employeesPage >= $totalPages ? 'rgba(148,163,184,0.3)' : '#94a3b8' }};"
                                    wire:click="loadMoreEmployees"
                                    @if($employeesPage >= $totalPages) disabled @endif>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </div>

        <!-- Right Column: Project Gaps List (8/12) -->
        <div class="col-md-8">
            <x-ui.card class="s1-panel">
                <div class="d-flex align-items-center justify-content-between mb-3 gap-3">
                    <div>
                        <div class="fw-semibold" style="font-size:.9rem;">
                            {{ $forTransfer ? 'Wybierz projekt i rolę' : 'Jakich ludzi brakuje po przyjeździe?' }}
                        </div>
                        <div style="font-size:.75rem; color:rgba(148,163,184,0.6); margin-top:2px;">
                            @if($forTransfer)
                                Przeciągnij pracownika na wybraną rolę — przypisanie obowiązuje od daty wyjazdu.
                            @else
                                Braki w rolach przez najbliższe 14 dni od daty przybycia.
                            @endif
                        </div>
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="projectSearch"
                           class="form-control form-control-sm"
                           style="max-width:200px; border-radius:10px !important;"
                           placeholder="Szukaj projektu…">
                </div>

                <div class="project-gaps-list">
                    @php $filteredProjectGapsTwoWeeks = $this->filteredProjectGapsTwoWeeks; @endphp

                    @if(!empty($filteredProjectGapsTwoWeeks))
                        @foreach($filteredProjectGapsTwoWeeks as $projectId => $project)
                            <div class="s1-project-block">
                                <div class="s1-project-header">
                                    <div class="s1-project-name">{{ $project['name'] }}</div>
                                    @if($project['location'])
                                        <div class="s1-project-loc">
                                            <i class="bi bi-geo-alt-fill" style="color:#6366f1;"></i>
                                            {{ $project['location'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="s1-roles-grid">
                                    @foreach($project['roles'] as $roleId => $role)
                                        @php
                                            $assignedEmployees = [];
                                            foreach($assignments as $dayAssignments) {
                                                if (isset($dayAssignments[$projectId][$roleId])) {
                                                    foreach($dayAssignments[$projectId][$roleId] as $empId) {
                                                        if (!in_array($empId, $assignedEmployees)) $assignedEmployees[] = $empId;
                                                    }
                                                }
                                            }
                                            foreach($assignmentRanges as $range) {
                                                if ($range['project_id'] == $projectId && $range['role_id'] == $roleId) {
                                                    if (!in_array($range['employee_id'], $assignedEmployees)) $assignedEmployees[] = $range['employee_id'];
                                                }
                                            }
                                        @endphp

                                        <div class="s1-role-card">
                                            <div class="s1-role-header">
                                                <span class="s1-role-name">{{ $role['name'] }}</span>
                                                <span class="s1-gap-pill">
                                                    <i class="bi bi-person-dash" style="font-size:.65rem;"></i>
                                                    @if($role['min_gaps'] == $role['max_gaps'])
                                                        {{ $role['min_gaps'] }} brak.
                                                    @else
                                                        {{ $role['min_gaps'] }}–{{ $role['max_gaps'] }} brak.
                                                    @endif
                                                </span>
                                            </div>

                                            <!-- Drop zone -->
                                            <div class="s1-drop-zone employee-drop-target employee-dropdown-zone"
                                                 data-project-id="{{ $projectId }}"
                                                 data-role-id="{{ $roleId }}">
                                                <i class="bi bi-person-plus" style="color:rgba(148,163,184,0.3); font-size:.95rem; flex:0 0 auto;"></i>
                                                <span class="s1-drop-hint">Przeciągnij pracownika na tę rolę</span>
                                            </div>

                                            <!-- Assigned chips -->
                                            @if(!empty($assignedEmployees))
                                                <div class="s1-assigned-list">
                                                    @foreach($assignedEmployees as $assignedEmployeeId)
                                                        @php $assignedEmployee = collect($allAvailableEmployees)->firstWhere('id', $assignedEmployeeId); @endphp
                                                        @if($assignedEmployee)
                                                            <div class="s1-assigned-chip">
                                                                <div class="s1-chip-avatar">
                                                                    @if($assignedEmployee['image_url'])
                                                                        <img src="{{ $assignedEmployee['image_url'] }}" alt="">
                                                                    @else
                                                                        {{ substr($assignedEmployee['first_name'],0,1) }}{{ substr($assignedEmployee['last_name'],0,1) }}
                                                                    @endif
                                                                </div>
                                                                <span class="s1-chip-name" title="{{ $assignedEmployee['full_name'] }}">{{ $assignedEmployee['full_name'] }}</span>
                                                                <button type="button"
                                                                        class="s1-chip-remove"
                                                                        wire:click="removeAssignmentRange({{ $assignedEmployeeId }}, {{ $projectId }}, {{ $roleId }})"
                                                                        title="Usuń przypisanie">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="text-align:center; padding:32px 0; color:rgba(148,163,184,0.5);">
                            <i class="bi bi-check2-circle" style="font-size:2rem; display:block; margin-bottom:8px; color:#10b981;"></i>
                            <span style="font-size:.85rem;">
                                @if(filled($projectSearch)) Brak braków dla filtrów @else Brak braków w rolach na najbliższe 2 tygodnie @endif
                            </span>
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
        <div class="modal-portal-to-body">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show employee-assignment-modal" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
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
                    <div class="modal-footer flex-column align-items-stretch gap-2">
                        @error('confirmation')
                            <div class="alert alert-danger mb-0 py-2 w-100" style="font-size: 0.875rem;">
                                <i class="bi bi-exclamation-triangle me-1"></i> {{ $message }}
                            </div>
                        @enderror
                        <div class="d-flex gap-2 justify-content-end w-100">
                            <button type="button" class="btn btn-secondary" wire:click="closeEmployeeModal">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmAssignment">
                                Potwierdź przypisanie
                            </button>
                        </div>
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
        const card = e.target.closest('.s1-emp-card, .employee-card');
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
        const card = e.target.closest('.s1-emp-card, .employee-card');
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
