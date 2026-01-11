<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Edytuj Przypisanie</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('assignments.update', $assignment) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Projekt</label>
                                <select name="project_id" required
                                    class="form-select @error('project_id') is-invalid @enderror">
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ old('project_id', $assignment->project_id) == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pracownik</label>
                                <select name="employee_id" required
                                    class="form-select @error('employee_id') is-invalid @enderror">
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
                                                style="{{ $hasRequiredDocIssue ? 'color: #dc2626; font-weight: bold;' : '' }}">
                                            {{ $optionText }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                
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
                                <label class="form-label fw-semibold">Rola w Projekcie</label>
                                <select name="role_id" required
                                    class="form-select @error('role_id') is-invalid @enderror">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id', $assignment->role_id) == $role->id ? 'selected' : '' }}>
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
                                <input type="date" name="start_date" id="start-date-input" value="{{ old('start_date', $assignment->start_date->format('Y-m-d')) }}" required
                                    class="form-control @error('start_date') is-invalid @enderror">
                                @error('start_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Zakończenia (opcjonalnie)</label>
                                <input type="date" name="end_date" id="end-date-input" value="{{ old('end_date', $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '') }}"
                                    class="form-control @error('end_date') is-invalid @enderror">
                                @error('end_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" required
                                    class="form-select @error('status') is-invalid @enderror">
                                    @php
                                        $currentStatus = $assignment->status instanceof \App\Enums\AssignmentStatus 
                                            ? $assignment->status->value 
                                            : ($assignment->status ?? 'active');
                                        $oldStatus = old('status', $currentStatus);
                                    @endphp
                                    <option value="active" {{ $oldStatus == 'active' ? 'selected' : '' }}>Aktywny</option>
                                    <option value="in_transit" {{ $oldStatus == 'in_transit' ? 'selected' : '' }}>W transporcie</option>
                                    <option value="at_base" {{ $oldStatus == 'at_base' ? 'selected' : '' }}>W bazie</option>
                                    <option value="completed" {{ $oldStatus == 'completed' ? 'selected' : '' }}>Zakończony</option>
                                    <option value="cancelled" {{ $oldStatus == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Uwagi</label>
                                <textarea name="notes" rows="3"
                                    class="form-control">{{ old('notes', $assignment->notes) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Aktualizuj
                                </button>
                                <a href="{{ route('project-assignments.index') }}" class="btn btn-link text-decoration-none">Anuluj</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
            const employeeSelect = document.querySelector('select[name="employee_id"]');
            
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
