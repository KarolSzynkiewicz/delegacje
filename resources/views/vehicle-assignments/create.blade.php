<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Przypisz Auto do Pracownika</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('employees.vehicles.store', $employee) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pracownik</label>
                                <input type="text" value="{{ $employee->full_name }}" disabled
                                    class="form-control bg-light">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pojazd</label>
                                <select name="vehicle_id" required
                                    class="form-select @error('vehicle_id') is-invalid @enderror">
                                    <option value="">Wybierz pojazd</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rola w pojeździe <span class="text-danger">*</span></label>
                                <select name="position" required
                                    class="form-select @error('position') is-invalid @enderror">
                                    <option value="passenger" {{ old('position', 'passenger') == 'passenger' ? 'selected' : '' }}>Pasażer</option>
                                    <option value="driver" {{ old('position') == 'driver' ? 'selected' : '' }}>Kierowca</option>
                                </select>
                                <small class="form-text text-muted">Uwaga: W jednym pojeździe może być tylko jeden kierowca w danym okresie</small>
                                @error('position')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Rozpoczęcia</label>
                                <input type="date" 
                                       name="start_date" 
                                       value="{{ old('start_date', $dateFrom ?? '') }}" 
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
                                       value="{{ old('end_date', $dateTo ?? '') }}"
                                    class="form-control @error('end_date') is-invalid @enderror">
                                @error('end_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Uwagi</label>
                                <textarea name="notes" rows="3"
                                    class="form-control">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Zapisz
                                </button>
                                <a href="{{ route('employees.vehicles.index', $employee) }}" class="btn btn-link text-decoration-none">Anuluj</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
