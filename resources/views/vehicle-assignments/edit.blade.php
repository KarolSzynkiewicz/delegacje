<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Edytuj Przypisanie Pojazdu</h2>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger mb-4">
                                <h5 class="alert-heading fw-semibold mb-2">Wystąpiły błędy:</h5>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success mb-4">
                                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('vehicle-assignments.update', $vehicleAssignment) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pracownik</label>
                                <select name="employee_id" required
                                    class="form-select @error('employee_id') is-invalid @enderror">
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ old('employee_id', $vehicleAssignment->employee_id) == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pojazd</label>
                                <select name="vehicle_id" required
                                    class="form-select @error('vehicle_id') is-invalid @enderror">
                                    @foreach($vehicles as $veh)
                                        <option value="{{ $veh->id }}" {{ old('vehicle_id', $vehicleAssignment->vehicle_id) == $veh->id ? 'selected' : '' }}>
                                            {{ $veh->registration_number }} - {{ $veh->brand }} {{ $veh->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rola w pojeździe <span class="text-danger">*</span></label>
                                @php
                                    // Handle position - can be enum, string, or null
                                    $currentPosition = 'passenger';
                                    if ($vehicleAssignment->position) {
                                        if ($vehicleAssignment->position instanceof \App\Enums\VehiclePosition) {
                                            $currentPosition = $vehicleAssignment->position->value;
                                        } else {
                                            $currentPosition = $vehicleAssignment->position;
                                        }
                                    }
                                    $oldPosition = old('position', $currentPosition);
                                @endphp
                                <select name="position" required
                                    class="form-select @error('position') is-invalid @enderror">
                                    <option value="passenger" {{ $oldPosition == 'passenger' || $oldPosition === 'passenger' ? 'selected' : '' }}>Pasażer</option>
                                    <option value="driver" {{ $oldPosition == 'driver' || $oldPosition === 'driver' ? 'selected' : '' }}>Kierowca</option>
                                </select>
                                <small class="form-text text-muted">Uwaga: W jednym pojeździe może być tylko jeden kierowca w danym okresie</small>
                                @error('position')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Rozpoczęcia</label>
                                <input type="date" name="start_date" value="{{ old('start_date', $vehicleAssignment->start_date->format('Y-m-d')) }}" required
                                    class="form-control @error('start_date') is-invalid @enderror">
                                @error('start_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Zakończenia (opcjonalnie)</label>
                                <input type="date" name="end_date" value="{{ old('end_date', $vehicleAssignment->end_date ? $vehicleAssignment->end_date->format('Y-m-d') : '') }}"
                                    class="form-control @error('end_date') is-invalid @enderror">
                                @error('end_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Uwagi</label>
                                <textarea name="notes" rows="3"
                                    class="form-control">{{ old('notes', $vehicleAssignment->notes) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Aktualizuj
                                </button>
                                <a href="{{ route('vehicle-assignments.index') }}" class="btn btn-link text-decoration-none">Anuluj</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
