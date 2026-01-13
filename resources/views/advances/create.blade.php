<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Dodaj Zaliczkę</h2>
            <x-ui.button variant="ghost" href="{{ route('advances.index') }}">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Zaliczkę">
                <form method="POST" action="{{ route('advances.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="employee_id" 
                            id="employee_id"
                            label="Pracownik"
                            required="true"
                        >
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->email }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="amount" 
                                id="amount"
                                label="Kwota"
                                value="{{ old('amount') }}"
                                step="0.01"
                                min="0"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="select" 
                                name="currency" 
                                id="currency"
                                label="Waluta"
                                required="true"
                            >
                                <option value="PLN" {{ old('currency', 'PLN') == 'PLN' ? 'selected' : '' }}>PLN</option>
                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            </x-ui.input>
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="date" 
                            id="date"
                            label="Data"
                            value="{{ old('date') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_interest_bearing" id="is_interest_bearing" value="1" {{ old('is_interest_bearing') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_interest_bearing">
                                Oprocentowana
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="interest_rate_field" style="display: {{ old('is_interest_bearing') ? 'block' : 'none' }};">
                        <x-ui.input 
                            type="number" 
                            name="interest_rate" 
                            id="interest_rate"
                            label="Stawka oprocentowania (%)"
                            value="{{ old('interest_rate') }}"
                            step="0.01"
                            min="0"
                            max="100"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('advances.index') }}">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        document.getElementById('is_interest_bearing').addEventListener('change', function() {
            document.getElementById('interest_rate_field').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</x-app-layout>
