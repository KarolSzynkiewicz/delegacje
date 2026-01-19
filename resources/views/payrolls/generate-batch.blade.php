<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Generuj Payroll dla wszystkich pracowników">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('payrolls.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Generuj Payroll dla wszystkich pracowników">
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
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('payrolls.index') }}"
                        action="cancel"
                    >
                        Anuluj
                    </x-ui.button>
                    <x-ui.button 
                        variant="primary" 
                        type="submit"
                        action="save"
                    >
                        Generuj Payroll
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card label="Informacje" class="mt-4">
            <p class="text-muted mb-0">
                System wygeneruje listy płac dla wszystkich pracowników, którzy mają jakiekolwiek logowane godziny w wybranym okresie.
                Listy płac będą miały status "Wystawiony" i będą zawierać:
            </p>
            <ul class="mt-2 mb-0">
                <li>Godziny pracy (z TimeLog) × stawka (z EmployeeRate)</li>
                <li>Kary i nagrody (z Adjustments)</li>
                <li>Zaliczki (z Advances, z oprocentowaniem jeśli dotyczy)</li>
            </ul>
        </x-ui.card>
    </div>
</div>
</x-app-layout>
