<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Dodaj Przypisanie Pracownika do Projektu</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('projects.assignments.store', $project) }}" id="assignment-form">
                            @csrf

                            @if(isset($isDateInPast) && $isDateInPast)
                            <div class="alert alert-warning mb-4" id="past-date-warning" role="alert">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                    <div class="flex-grow-1">
                                        <h5 class="alert-heading mb-2">Uwaga: Data w przeszłości</h5>
                                        <p class="mb-2">
                                            Próbujesz dodać przypisanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                        </p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="confirm-past-date">
                                            <label class="form-check-label" for="confirm-past-date">
                                                Tak, chcę dodać przypisanie dla dat w przeszłości
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Projekt</label>
                                <input type="text" value="{{ $project->name }}" disabled
                                    class="form-control bg-light">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pracownik</label>
                                <select name="employee_id" 
                                        id="employee-select"
                                        onchange="updateLivewireComponent()"
                                        required
                                    class="form-select @error('employee_id') is-invalid @enderror">
                                    <option value="">Wybierz pracownika</option>
                                    @foreach($employees as $employee)
                                    @php
                                        $isAvailable = $employee->availability_status['available'] ?? true;
                                        $reasons = $employee->availability_status['reasons'] ?? [];
                                        $missingDocuments = $employee->availability_status['missing_documents'] ?? [];
                                        
                                        // Sprawdź czy problem jest z wymaganymi dokumentami
                                        $hasRequiredDocIssue = false;
                                        foreach ($reasons as $reason) {
                                            if (str_contains($reason, 'wymaganych dokumentów')) {
                                                $hasRequiredDocIssue = true;
                                                break;
                                            }
                                        }
                                        // Jeśli nie ma powodu o wymaganych dokumentach, ale są missing_documents, sprawdź czy są wymagane
                                        if (!$hasRequiredDocIssue && !empty($missingDocuments)) {
                                            foreach ($missingDocuments as $doc) {
                                                if (isset($doc['is_required']) && $doc['is_required']) {
                                                    $hasRequiredDocIssue = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    @php
                                        $optionText = $employee->full_name;
                                        if ($employee->roles->count() > 0) {
                                            $optionText .= ' (' . $employee->roles->pluck('name')->join(', ') . ')';
                                        }
                                        // Pokaż powody tylko jeśli są problemy z wymaganymi dokumentami lub innymi ważnymi powodami
                                        if (!$isAvailable && !empty($reasons)) {
                                            $shortReasons = [];
                                            foreach ($reasons as $reason) {
                                                if (str_contains($reason, 'wymaganych dokumentów')) {
                                                    $shortReasons[] = 'Brak wymaganych dok';
                                                } elseif (str_contains($reason, 'rotacji')) {
                                                    $shortReasons[] = 'Brak rotacji';
                                                } elseif (str_contains($reason, 'przypisany') || str_contains($reason, 'projekcie')) {
                                                    $shortReasons[] = 'W innym projekcie';
                                                } else {
                                                    $shortReasons[] = $reason;
                                                }
                                            }
                                            $optionText .= ' - ' . implode(', ', $shortReasons);
                                        }
                                    @endphp
                                    <option value="{{ $employee->id }}" 
                                            {{ old('employee_id') == $employee->id ? 'selected' : '' }}
                                            style="{{ $hasRequiredDocIssue ? 'color: #dc2626; font-weight: bold;' : '' }}">
                                        {{ $optionText }}
                                    </option>
                                @endforeach
                                </select>
                                <style>
                                    select[name="employee_id"] option[style*="color: #dc2626"] {
                                        color: #dc2626 !important;
                                        font-weight: bold;
                                    }
                                </style>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                
                                {{-- Szczegóły dostępności - wyświetlane bezpośrednio pod dropdownem --}}
                                <div id="availability-checker-container" class="mt-3">
                                    <livewire:employee-availability-checker 
                                        wire:key="availability-checker-{{ old('employee_id', '') }}-{{ $startDate ?? '' }}-{{ $endDate ?? '' }}"
                                        :employee-id="old('employee_id')"
                                        :start-date="$startDate ?? null"
                                        :end-date="$endDate ?? null"
                                    />
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rola w Projekcie</label>
                                <select name="role_id" required
                                    class="form-select @error('role_id') is-invalid @enderror">
                                    <option value="">Wybierz rolę</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Rozpoczęcia</label>
                                <input type="date" 
                                       name="start_date" 
                                       id="start-date-input"
                                       onchange="updateLivewireComponent()"
                                       oninput="updateLivewireComponent()"
                                       value="{{ old('start_date', $startDate ?? '') }}"
                                       required
                                    class="form-control @error('start_date') is-invalid @enderror">
                                @error('start_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Zakończenia (opcjonalnie)</label>
                                <input type="date" 
                                       name="end_date" 
                                       id="end-date-input"
                                       onchange="updateLivewireComponent()"
                                       oninput="updateLivewireComponent()"
                                       value="{{ old('end_date', $endDate ?? '') }}"
                                    class="form-control @error('end_date') is-invalid @enderror">
                                @error('end_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" required
                                    class="form-select">
                                    <option value="pending">Oczekujące</option>
                                    <option value="active" selected>Aktywne</option>
                                    <option value="completed">Zakończone</option>
                                    <option value="cancelled">Anulowane</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Uwagi</label>
                                <textarea name="notes" rows="3"
                                    class="form-control">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="submit" id="submit-btn" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Zapisz
                                    </button>
                                    <a href="{{ route('projects.assignments.index', $project) }}" class="btn btn-link text-decoration-none ms-2">Anuluj</a>
                                </div>
                            </div>
                            <div id="submit-warning" class="alert alert-danger mt-3 d-none">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Uwaga!</strong> Wybrany pracownik ma problemy z dostępnością. Sprawdź szczegóły powyżej.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateLivewireComponent() {
            const select = document.getElementById('employee-select');
            const startInput = document.getElementById('start-date-input');
            const endInput = document.getElementById('end-date-input');
            
            if (!select || !startInput) return;
            
            const employeeId = select.value;
            const startDate = startInput.value;
            const endDate = endInput.value || startDate;
            
            // Znajdź komponent Livewire w kontenerze
            const container = document.getElementById('availability-checker-container');
            if (!container) return;
            
            const livewireElement = container.querySelector('[wire\\:id]');
            if (!livewireElement) return;
            
            const wireId = livewireElement.getAttribute('wire:id');
            const component = Livewire.find(wireId);
            
            if (component) {
                component.set('employeeId', employeeId || null);
                component.set('startDate', startDate || null);
                component.set('endDate', endDate || null);
            }
        }
        
        // Aktualizuj przy załadowaniu strony, jeśli są wartości domyślne
        document.addEventListener('DOMContentLoaded', function() {
            const startInput = document.getElementById('start-date-input');
            const endInput = document.getElementById('end-date-input');
            const employeeSelect = document.getElementById('employee-select');
            const form = document.getElementById('assignment-form');
            let pastDateWarning = null;
            
            function checkDates() {
                const startDate = startInput.value;
                const endDate = endInput.value;
                const today = new Date().toISOString().split('T')[0];
                
                const isStartDatePast = startDate && startDate < today;
                const isEndDatePast = endDate && endDate < today;
                const isPast = isStartDatePast || isEndDatePast;

                // Sprawdź czy już istnieje warning z PHP
                const existingWarning = document.getElementById('past-date-warning');
                
                // Usuń dynamiczny warning jeśli jest
                if (pastDateWarning) {
                    pastDateWarning.remove();
                    pastDateWarning = null;
                }

                // Jeśli data jest w przeszłości i nie ma już ostrzeżenia z PHP, dodaj dynamiczny warning
                if (isPast && !existingWarning) {
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'alert alert-warning mb-4';
                    warningDiv.id = 'past-date-warning-dynamic';
                    warningDiv.setAttribute('role', 'alert');
                    warningDiv.innerHTML = `
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-2">Uwaga: Data w przeszłości</h5>
                                <p class="mb-2">
                                    Próbujesz dodać przypisanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirm-past-date-dynamic">
                                    <label class="form-check-label" for="confirm-past-date-dynamic">
                                        Tak, chcę dodać przypisanie dla dat w przeszłości
                                    </label>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Wstaw warning przed projektem
                    const projectDiv = document.querySelector('.card-body > .mb-3');
                    if (projectDiv) {
                        projectDiv.parentNode.insertBefore(warningDiv, projectDiv);
                    } else {
                        form.insertBefore(warningDiv, form.firstChild);
                    }
                    pastDateWarning = warningDiv;
                }
            }

            // Sprawdzaj daty przy zmianie
            if (startInput) {
                startInput.addEventListener('change', checkDates);
                startInput.addEventListener('input', checkDates);
            }
            if (endInput) {
                endInput.addEventListener('change', checkDates);
                endInput.addEventListener('input', checkDates);
            }

            // Blokuj submit jeśli data w przeszłości i nie potwierdzono
            if (form) {
                form.addEventListener('submit', function(e) {
                    const confirmCheckbox = document.getElementById('confirm-past-date');
                    const confirmCheckboxDynamic = document.getElementById('confirm-past-date-dynamic');
                    const isConfirmed = (confirmCheckbox && confirmCheckbox.checked) || (confirmCheckboxDynamic && confirmCheckboxDynamic.checked);
                    
                    const startDate = startInput.value;
                    const endDate = endInput.value;
                    const today = new Date().toISOString().split('T')[0];
                    const isStartDatePast = startDate && startDate < today;
                    const isEndDatePast = endDate && endDate < today;
                    const isPast = isStartDatePast || isEndDatePast;
                    
                    if (isPast && !isConfirmed) {
                        e.preventDefault();
                        alert('Musisz potwierdzić, że chcesz dodać przypisanie dla dat w przeszłości.');
                        return false;
                    }
                });
            }
            
            if (startInput && startInput.value) {
                // Opóźnij, aby upewnić się, że Livewire jest gotowy
                setTimeout(updateLivewireComponent, 200);
            }
            
            // Aktualizuj również przy zmianie pracownika
            if (employeeSelect) {
                employeeSelect.addEventListener('change', updateLivewireComponent);
            }

            // Sprawdź daty przy załadowaniu strony
            checkDates();
        });
    </script>
</x-app-layout>
