<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Przypisz Dom do Pracownika</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('employees.accommodations.store', $employee) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pracownik</label>
                                <input type="text" value="{{ $employee->full_name }}" disabled
                                    class="form-control bg-light">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mieszkanie</label>
                                <select name="accommodation_id" required
                                    class="form-select @error('accommodation_id') is-invalid @enderror">
                                    <option value="">Wybierz mieszkanie</option>
                                    @foreach($accommodations as $accommodation)
                                        <option value="{{ $accommodation->id }}" {{ old('accommodation_id') == $accommodation->id ? 'selected' : '' }}>
                                            {{ $accommodation->name }} ({{ $accommodation->capacity }} miejsc)
                                        </option>
                                    @endforeach
                                </select>
                                @error('accommodation_id')
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
                                <a href="{{ route('employees.accommodations.index', $employee) }}" class="btn btn-link text-decoration-none">Anuluj</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
