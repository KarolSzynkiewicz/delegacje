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
                                        <option value="{{ $employee->id }}" {{ old('employee_id', $assignment->employee_id) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name }}@if($employee->roles->count() > 0) ({{ $employee->roles->pluck('name')->join(', ') }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
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
                                <input type="date" name="start_date" value="{{ old('start_date', $assignment->start_date->format('Y-m-d')) }}" required
                                    class="form-control @error('start_date') is-invalid @enderror">
                                @error('start_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Zakończenia (opcjonalnie)</label>
                                <input type="date" name="end_date" value="{{ old('end_date', $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '') }}"
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
</x-app-layout>
