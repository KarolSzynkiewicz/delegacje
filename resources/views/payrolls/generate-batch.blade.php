<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Generuj Payroll dla wszystkich pracowników</h2>
        </div>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('payrolls.generate-batch.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="period_start" class="form-label">Data od <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('period_start') is-invalid @enderror" 
                               id="period_start" 
                               name="period_start" 
                               value="{{ old('period_start') }}" 
                               required>
                        @error('period_start')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="period_end" class="form-label">Data do <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('period_end') is-invalid @enderror" 
                               id="period_end" 
                               name="period_end" 
                               value="{{ old('period_end') }}" 
                               required>
                        @error('period_end')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notatki</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                              id="notes" 
                              name="notes" 
                              rows="3">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if ($errors->has('error'))
                    <div class="alert alert-danger">
                        {{ $errors->first('error') }}
                    </div>
                @endif

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('payrolls.index') }}" class="btn btn-secondary">Anuluj</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-gear"></i> Generuj Payroll
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body">
            <h5 class="card-title">Informacje</h5>
            <p class="text-muted mb-0">
                System wygeneruje listy płac dla wszystkich pracowników, którzy mają jakiekolwiek logowane godziny w wybranym okresie.
                Listy płac będą miały status "Wystawiony" i będą zawierać:
            </p>
            <ul class="mt-2 mb-0">
                <li>Godziny pracy (z TimeLog) × stawka (z EmployeeRate)</li>
                <li>Kary i nagrody (z Adjustments)</li>
                <li>Zaliczki (z Advances, z oprocentowaniem jeśli dotyczy)</li>
            </ul>
        </div>
    </div>
</x-app-layout>
