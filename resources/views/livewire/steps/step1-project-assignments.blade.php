<div>
    <x-departure.planner-step1-assignments-styles />

    <!-- Main Layout: 4/12 + 8/12 -->
    <div class="row g-4">
        <!-- Left Column: Available Employees (4/12) -->
        <div class="col-md-4">
            <x-ui.card class="s1-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="fw-semibold s1-panel-title">
                        {{ $forTransfer ? 'Uczestnicy transferu' : 'Dostępni pracownicy' }}
                    </span>
                    @if($isVehicleFull && !$forTransfer)
                        <span class="s1-capacity-badge">
                            <i class="bi bi-x-octagon-fill me-1"></i>Wyjazd pełny
                        </span>
                    @endif
                </div>

                @if($showFullBanner)
                    <div class="s1-full-banner">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        Brak miejsca w aucie — nie możesz dodać więcej osób do tego wyjazdu.
                    </div>
                @endif

                @unless($showFullBanner)
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
                        @forelse($employees as $employee)
                            <div class="s1-emp-card"
                                 draggable="true"
                                 data-employee-id="{{ $employee['id'] }}">
                                <div class="s1-emp-avatar">
                                    @if($employee['image_url'])
                                        <img src="{{ $employee['image_url'] }}" alt="">
                                    @else
                                        {{ $employee['initials'] }}
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="s1-emp-name text-truncate">{{ $employee['full_name'] }}</div>
                                    <div class="s1-emp-roles">
                                        @foreach($employee['roles'] as $role)
                                            <span class="s1-emp-role-pill">{{ $role['name'] }}</span>
                                        @endforeach
                                    </div>
                                    @if($employee['has_rotation'])
                                        <div class="s1-emp-rotation">
                                            <i class="bi bi-arrow-repeat"></i>
                                            {{ $employee['rotation_label'] }}
                                        </div>
                                    @else
                                        <div class="s1-emp-rotation s1-emp-rotation--missing">
                                            <span>
                                                <i class="bi bi-exclamation-circle"></i>
                                                Brak rotacji
                                            </span>
                                            <button type="button"
                                                    class="s1-emp-add-rotation"
                                                    draggable="false"
                                                    wire:click.stop="openRotationModal({{ $employee['id'] }})"
                                                    title="Dodaj rotację">
                                                Dodaj
                                            </button>
                                        </div>
                                    @endif
                                    @if($employee['docs_warning'])
                                        <div class="s1-emp-doc-warn">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            {{ $employee['docs_warning']['total'] }} dok.
                                            @if($employee['docs_warning']['critical'])
                                                · {{ $employee['docs_warning']['critical'] }} wymaganych
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <i class="bi bi-grip-vertical text-muted s1-grip-icon"></i>
                            </div>
                        @empty
                            <div class="s1-empty-state">
                                <i class="bi bi-person-x s1-empty-state__icon"></i>
                                <span class="s1-empty-state__text">
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

                        @if($pagination)
                            <div class="d-flex align-items-center justify-content-between mt-3 pt-2 s1-pagination-bar">
                                <button type="button"
                                        class="btn btn-sm s1-pagination-btn {{ $pagination['can_prev'] ? '' : 's1-pagination-btn--disabled' }}"
                                        wire:click="loadPrevEmployees"
                                        @if(!$pagination['can_prev']) disabled @endif>
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span class="s1-pagination-info">{{ $pagination['label'] }}</span>
                                <button type="button"
                                        class="btn btn-sm s1-pagination-btn {{ $pagination['can_next'] ? '' : 's1-pagination-btn--disabled' }}"
                                        wire:click="loadMoreEmployees"
                                        @if(!$pagination['can_next']) disabled @endif>
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                @endunless
            </x-ui.card>
        </div>

        <!-- Right Column: Project Gaps List (8/12) -->
        <div class="col-md-8">
            <x-ui.card class="s1-panel">
                <div class="d-flex align-items-center justify-content-between mb-3 gap-3">
                    <div>
                        <div class="fw-semibold s1-panel-title">
                            {{ $forTransfer ? 'Wybierz projekt i rolę' : 'Jakich ludzi brakuje po przyjeździe?' }}
                        </div>
                        <div class="s1-section-subtitle">
                            @if($forTransfer)
                                Przeciągnij pracownika na wybraną rolę — przypisanie obowiązuje od daty wyjazdu.
                            @else
                                Braki w rolach przez najbliższe 14 dni od daty przybycia.
                            @endif
                        </div>
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="projectSearch"
                           class="form-control form-control-sm s1-project-search-input"
                           placeholder="Szukaj projektu…">
                </div>

                <div class="project-gaps-list">
                    @if(!$projectsEmpty)
                        @foreach($projects as $project)
                            <div class="s1-project-block">
                                <div class="s1-project-header">
                                    <div class="s1-project-name">{{ $project['name'] }}</div>
                                    @if($project['location'])
                                        <div class="s1-project-loc">
                                            <i class="bi bi-geo-alt-fill s1-project-loc-icon"></i>
                                            {{ $project['location'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="s1-roles-grid">
                                    @foreach($project['roles'] as $role)
                                        <div class="s1-role-card">
                                            <div class="s1-role-header">
                                                <span class="s1-role-name">{{ $role['name'] }}</span>
                                                <span class="s1-gap-pill">
                                                    <i class="bi bi-person-dash"></i>
                                                    {{ $role['gap_label'] }}
                                                </span>
                                            </div>

                                            <!-- Drop zone -->
                                            <div class="s1-drop-zone employee-drop-target employee-dropdown-zone"
                                                 data-project-id="{{ $project['id'] }}"
                                                 data-role-id="{{ $role['id'] }}">
                                                <i class="bi bi-person-plus s1-drop-zone-icon"></i>
                                                <span class="s1-drop-hint">Przeciągnij pracownika na tę rolę</span>
                                            </div>

                                            <!-- Assigned chips -->
                                            @if(!empty($role['assigned_chips']))
                                                <div class="s1-assigned-list">
                                                    @foreach($role['assigned_chips'] as $chip)
                                                        <div class="s1-assigned-chip">
                                                            <div class="s1-chip-avatar">
                                                                @if($chip['image_url'])
                                                                    <img src="{{ $chip['image_url'] }}" alt="">
                                                                @else
                                                                    {{ $chip['initials'] }}
                                                                @endif
                                                            </div>
                                                            <span class="s1-chip-name" title="{{ $chip['name'] }}">{{ $chip['name'] }}</span>
                                                            <button type="button"
                                                                    class="s1-chip-remove"
                                                                    wire:click="removeAssignmentRange({{ $chip['employee_id'] }}, {{ $chip['project_id'] }}, {{ $chip['role_id'] }})"
                                                                    title="Usuń przypisanie">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="s1-empty-success">
                            <i class="bi bi-check2-circle s1-empty-success__icon"></i>
                            <span class="s1-empty-success__text">{{ $projectsEmptyMsg }}</span>
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

    <!-- Modal: Dodaj rotację -->
    @if($showRotationModal && $rotationModalEmployeeId)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 style="background-color: rgba(0,0,0,0.55);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title">
                                <i class="bi bi-arrow-repeat text-warning me-2"></i>
                                Dodaj rotację: {{ $rotationModalEmployeeName }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeRotationModal" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="rotationStartDate" class="form-label">Data rozpoczęcia *</label>
                                    <input type="date"
                                           id="rotationStartDate"
                                           class="form-control @error('rotationStartDate') is-invalid @enderror"
                                           wire:model="rotationStartDate">
                                    @error('rotationStartDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="rotationEndDate" class="form-label">Data zakończenia *</label>
                                    <input type="date"
                                           id="rotationEndDate"
                                           class="form-control @error('rotationEndDate') is-invalid @enderror"
                                           wire:model="rotationEndDate">
                                    @error('rotationEndDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="rotationNotes" class="form-label">Notatki</label>
                                    <textarea id="rotationNotes"
                                              class="form-control @error('rotationNotes') is-invalid @enderror"
                                              rows="3"
                                              wire:model="rotationNotes"></textarea>
                                    @error('rotationNotes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <p class="text-muted small mt-3 mb-0">
                                Daty domyślnie pokrywają okres wyjazdu. Status rotacji ustala się automatycznie na podstawie dat.
                            </p>
                        </div>
                        <div class="modal-footer border-secondary gap-2">
                            <button type="button" class="btn btn-outline-light" wire:click="closeRotationModal">Anuluj</button>
                            <button type="button"
                                    class="btn btn-primary"
                                    wire:click="saveRotation"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveRotation">
                                    <i class="bi bi-plus-circle me-1"></i>Zapisz rotację
                                </span>
                                <span wire:loading wire:target="saveRotation">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Zapisywanie…
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    <!-- Modal: Employee Assignment Calendar -->
    @if($showEmployeeModal && $selectedEmployee && $selectedProject && $selectedRole)
        <div class="modal-portal-to-body">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show employee-assignment-modal s1-assignment-modal-visible" tabindex="-1" role="dialog">
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

                        @if($calendar['selected_range'])
                            <div class="alert alert-info mb-3">
                                @if($calendar['selected_range']['type'] === 'range')
                                    <strong>Wybrany zakres:</strong> {{ $calendar['selected_range']['label'] }}
                                    <br>
                                    <small>Kliknij inną datę aby zmienić zakres końcowy</small>
                                @else
                                    <strong>Wybrana data początkowa:</strong> {{ $calendar['selected_range']['label'] }}
                                    <br>
                                    <small>Kliknij datę końcową aby zakończyć wybór</small>
                                @endif
                            </div>
                        @endif

                        <x-ui.cal
                            :startDate="$calendar['start_date']"
                            :days="0"
                            :availability="$employeeAvailability"
                            :selectedStartDate="$selectedStartDate"
                            :selectedEndDate="$selectedEndDate"
                            onDateClick="selectDate"
                            :showMonthNavigation="true"
                            onPreviousMonth="wire:click=previousMonth"
                            onNextMonth="wire:click=nextMonth"
                            :arrivalDate="$calendar['arrival_date']"
                        />
                    </div>
                    <div class="modal-footer flex-column align-items-stretch gap-2">
                        @error('confirmation')
                            <div class="alert alert-danger mb-0 py-2 w-100 s1-modal-footer-error">
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

    function startAutoScroll(e) {
        if (!isDragging) return;

        const viewportHeight = window.innerHeight;
        const scrollThreshold = 100;
        const scrollSpeed = 10;
        const mouseY = e.clientY;

        if (mouseY < scrollThreshold) {
            if (autoScrollInterval) clearInterval(autoScrollInterval);
            autoScrollInterval = setInterval(() => {
                if (!isDragging) { clearInterval(autoScrollInterval); return; }
                window.scrollBy(0, -scrollSpeed);
            }, 10);
        } else if (mouseY > viewportHeight - scrollThreshold) {
            if (autoScrollInterval) clearInterval(autoScrollInterval);
            autoScrollInterval = setInterval(() => {
                if (!isDragging) { clearInterval(autoScrollInterval); return; }
                window.scrollBy(0, scrollSpeed);
            }, 10);
        } else {
            if (autoScrollInterval) { clearInterval(autoScrollInterval); autoScrollInterval = null; }
        }
    }

    document.addEventListener('dragstart', function(e) {
        if (e.target.closest('.s1-emp-add-rotation')) {
            e.preventDefault();
            return;
        }
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

    document.addEventListener('dragend', function(e) {
        const card = e.target.closest('.s1-emp-card, .employee-card');
        if (card) { card.style.opacity = '1'; }
        draggedEmployeeId = null;
        isDragging = false;
        if (autoScrollInterval) { clearInterval(autoScrollInterval); autoScrollInterval = null; }
    }, true);

    document.addEventListener('dragover', function(e) {
        if (isDragging) startAutoScroll(e);
    }, true);

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

    document.addEventListener('dragleave', function(e) {
        const dropTarget = e.target.closest('.employee-drop-target');
        if (dropTarget && !dropTarget.contains(e.relatedTarget)) {
            dropTarget.style.background = '';
            dropTarget.style.borderColor = '';
            dropTarget.style.borderStyle = '';
            dropTarget.style.transform = '';
        }
    }, true);

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
                $wire.openEmployeeModal(parseInt(employeeId), projectId, roleId);
            }
        }
    }, true);
</script>
@endscript
