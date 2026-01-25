<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Przypisanie Pracownika do Projektu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.show', $project) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Przypisanie Pracownika do Projektu">
                <form method="POST" action="{{ route('projects.assignments.store', $project) }}" id="assignment-form">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Projekt</label>
                        <input type="text" value="{{ $project->name }}" disabled class="form-control bg-light">
                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="employee_id" 
                            id="employee-select"
                            label="Pracownik"
                            required="true"
                        >
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                @php
                                    $isAvailable = $employee->availability_status['available'] ?? true;
                                    $reasons = $employee->availability_status['reasons'] ?? [];
                                    $missingDocuments = $employee->availability_status['missing_documents'] ?? [];

                                    $hasRequiredDocIssue = false;
                                    foreach ($reasons as $reason) {
                                        if (str_contains($reason, 'wymaganych dokumentów')) {
                                            $hasRequiredDocIssue = true;
                                            break;
                                        }
                                    }
                                    if (!$hasRequiredDocIssue && !empty($missingDocuments)) {
                                        foreach ($missingDocuments as $doc) {
                                            if (isset($doc['is_required']) && $doc['is_required']) {
                                                $hasRequiredDocIssue = true;
                                                break;
                                            }
                                        }
                                    }

                                    $optionText = $employee->full_name;
                                    if ($employee->roles->count() > 0) {
                                        $optionText .= ' (' . $employee->roles->pluck('name')->join(', ') . ')';
                                    }

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
                                        data-has-issue="{{ $hasRequiredDocIssue ? '1' : '0' }}">
                                    {{ $optionText }}
                                </option>
                            @endforeach
                        </x-ui.input>

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
                        <x-ui.input 
                            type="select" 
                            name="role_id" 
                            label="Rola w Projekcie"
                            required="true"
                        >
                            <option value="">Wybierz rolę</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="start_date" 
                            id="start-date-input"
                            label="Data Rozpoczęcia"
                            value="{{ old('start_date', $startDate ?? '') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="end_date" 
                            id="end-date-input"
                            label="Data Zakończenia (opcjonalnie)"
                            value="{{ old('end_date', $endDate ?? '') }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi"
                            value="{{ old('notes') }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <x-ui.button variant="primary" type="submit" id="submit-btn">
                                <i class="bi bi-save me-1"></i> Zapisz
                            </x-ui.button>
                            <x-ui.button variant="ghost" href="{{ route('projects.assignments.index', $project) }}">Anuluj</x-ui.button>
                            
                            @if(isset($isDateInPast) && $isDateInPast)
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input" id="confirm-past-date" name="confirm_past_date">
                                    <label class="form-check-label small text-muted" for="confirm-past-date">
                                        Data w przeszłości
                                    </label>
                                </div>
                            @endif
                            
                            <div id="past-date-checkbox-container" class="d-none">
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input" id="confirm-past-date-dynamic" name="confirm_past_date">
                                    <label class="form-check-label small text-muted" for="confirm-past-date-dynamic">
                                        Data w przeszłości
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="submit-warning" class="alert alert-danger mt-3 d-none">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Uwaga!</strong> Wybrany pracownik ma problemy z dostępnością. Sprawdź szczegóły powyżej.
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        function updateLivewireComponent() {
            const select = document.getElementById('employee-select');
            const startInput = document.getElementById('start-date-input');
            const endInput = document.getElementById('end-date-input');
            const container = document.getElementById('availability-checker-container');

            if (!select || !startInput || !container) return;

            const livewireElement = container.querySelector('[wire\\:id]');
            if (!livewireElement) return;

            const wireId = livewireElement.getAttribute('wire:id');
            const component = Livewire.find(wireId);

            if (component) {
                component.set('employeeId', select.value || null);
                component.set('startDate', startInput.value || null);
                component.set('endDate', endInput.value || null);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('assignment-form');
            const startInput = document.getElementById('start-date-input');
            const endInput = document.getElementById('end-date-input');
            const employeeSelect = document.getElementById('employee-select');
            let pastDateWarning = null;

            function checkDates() {
                const today = new Date().toISOString().split('T')[0];
                const startDate = startInput.value;
                const endDate = endInput.value;
                const isPast = (startDate && startDate < today) || (endDate && endDate < today);

                const checkboxContainer = document.getElementById('past-date-checkbox-container');
                const existingCheckbox = document.getElementById('confirm-past-date');
                
                // Jeśli już jest checkbox z PHP, nie dodawaj kolejnego
                if (existingCheckbox) {
                    return;
                }

                if (isPast) {
                    if (checkboxContainer) {
                        checkboxContainer.classList.remove('d-none');
                    }
                } else {
                    if (checkboxContainer) {
                        checkboxContainer.classList.add('d-none');
                    }
                    const dynamicCheckbox = document.getElementById('confirm-past-date-dynamic');
                    if (dynamicCheckbox) {
                        dynamicCheckbox.checked = false;
                    }
                }
            }

            [startInput, endInput].forEach(input => {
                input.addEventListener('change', () => { checkDates(); updateLivewireComponent(); });
                input.addEventListener('input', () => { checkDates(); updateLivewireComponent(); });
            });

            if (employeeSelect) employeeSelect.addEventListener('change', updateLivewireComponent);

            form.addEventListener('submit', function(e) {
                const checkbox = document.getElementById('confirm-past-date') || document.getElementById('confirm-past-date-dynamic');
                const startDate = startInput.value;
                const endDate = endInput.value;
                const today = new Date().toISOString().split('T')[0];
                const isPast = (startDate && startDate < today) || (endDate && endDate < today);

                if (isPast && !(checkbox && checkbox.checked)) {
                    e.preventDefault();
                    alert('Musisz potwierdzić, że chcesz dodać przypisanie dla dat w przeszłości.');
                }
            });

            if (startInput.value) setTimeout(updateLivewireComponent, 200);
            checkDates();
        });
    </script>
</x-app-layout>
