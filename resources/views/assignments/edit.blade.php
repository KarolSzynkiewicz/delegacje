<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Przypisanie">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('assignments.show', $assignment) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Przypisanie">
                <form method="POST" action="{{ route('assignments.update', $assignment) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="project_id" 
                            label="Projekt"
                            required="true"
                        >
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $assignment->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="employee_id" 
                            id="employee-select"
                            label="Pracownik"
                            required="true"
                        >
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
                                        {{ old('employee_id', $assignment->employee_id) == $employee->id ? 'selected' : '' }}
                                        data-has-issue="{{ $hasRequiredDocIssue ? '1' : '0' }}">
                                    {{ $optionText }}
                                </option>
                            @endforeach
                        </x-ui.input>
                        
                        @if(isset($startDate) && isset($endDate))
                        {{-- Szczegóły dostępności - wyświetlane bezpośrednio pod dropdownem --}}
                        <div id="availability-checker-container" class="mt-3">
                            <livewire:employee-availability-checker 
                                wire:key="availability-checker-{{ old('employee_id', $assignment->employee_id) }}-{{ $startDate }}-{{ $endDate }}"
                                :employee-id="old('employee_id', $assignment->employee_id)"
                                :start-date="$startDate"
                                :end-date="$endDate"
                            />
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="role_id" 
                            label="Rola w Projekcie"
                            required="true"
                        >
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $assignment->role_id) == $role->id ? 'selected' : '' }}>
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
                            value="{{ old('start_date', $assignment->start_date->format('Y-m-d')) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="end_date" 
                            id="end-date-input"
                            label="Data Zakończenia (opcjonalnie)"
                            value="{{ old('end_date', $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '') }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi"
                            value="{{ old('notes', $assignment->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Aktualizuj
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('project-assignments.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    @if(isset($startDate) && isset($endDate))
    <script>
        function updateLivewireComponent() {
            const select = document.getElementById('employee-select') || document.querySelector('select[name="employee_id"]');
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
        
        document.addEventListener('DOMContentLoaded', function() {
            const startInput = document.getElementById('start-date-input');
            const endInput = document.getElementById('end-date-input');
            const employeeSelect = document.getElementById('employee-select') || document.querySelector('select[name="employee_id"]');
            
            if (startInput) {
                startInput.addEventListener('change', updateLivewireComponent);
                startInput.addEventListener('input', updateLivewireComponent);
            }
            if (endInput) {
                endInput.addEventListener('change', updateLivewireComponent);
                endInput.addEventListener('input', updateLivewireComponent);
            }
            if (employeeSelect) {
                employeeSelect.addEventListener('change', updateLivewireComponent);
            }
            
            // Aktualizuj przy załadowaniu
            if (startInput && startInput.value) {
                setTimeout(updateLivewireComponent, 200);
            }
        });
    </script>
    @endif
</x-app-layout>
