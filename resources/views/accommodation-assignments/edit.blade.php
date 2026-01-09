<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Edytuj Przypisanie Mieszkania</h2>
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

                        <form method="POST" action="{{ route('accommodation-assignments.update', $accommodationAssignment) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pracownik</label>
                                <select name="employee_id" required
                                    class="form-select @error('employee_id') is-invalid @enderror">
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ old('employee_id', $accommodationAssignment->employee_id) == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mieszkanie</label>
                                <select name="accommodation_id" required
                                    class="form-select @error('accommodation_id') is-invalid @enderror">
                                    @foreach($accommodations as $acc)
                                        <option value="{{ $acc->id }}" {{ old('accommodation_id', $accommodationAssignment->accommodation_id) == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }} ({{ $acc->capacity }} miejsc) - {{ $acc->city }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('accommodation_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Rozpoczęcia</label>
                                <input type="date" name="start_date" value="{{ old('start_date', $accommodationAssignment->start_date->format('Y-m-d')) }}" required
                                    class="form-control @error('start_date') is-invalid @enderror">
                                @error('start_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Data Zakończenia (opcjonalnie)</label>
                                <input type="date" name="end_date" value="{{ old('end_date', $accommodationAssignment->end_date ? $accommodationAssignment->end_date->format('Y-m-d') : '') }}"
                                    class="form-control @error('end_date') is-invalid @enderror">
                                @error('end_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Uwagi</label>
                                <textarea name="notes" rows="3"
                                    class="form-control">{{ old('notes', $accommodationAssignment->notes) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Aktualizuj
                                </button>
                                <a href="{{ route('accommodation-assignments.index') }}" class="btn btn-link text-decoration-none">Anuluj</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
