<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Stawkę">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('employee-rates.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Stawkę">
                <form method="POST" action="{{ route('employee-rates.update', $employeeRate) }}">
                    @csrf
                    @method('PUT')

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
                                <option value="{{ $employee->id }}" {{ old('employee_id', $employeeRate->employee_id) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->email }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="start_date" 
                                id="start_date"
                                label="Data rozpoczęcia"
                                value="{{ old('start_date', $employeeRate->start_date->format('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                id="end_date"
                                label="Data zakończenia (opcjonalnie)"
                                value="{{ old('end_date', $employeeRate->end_date ? $employeeRate->end_date->format('Y-m-d') : '') }}"
                            />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="amount" 
                                id="amount"
                                label="Kwota"
                                value="{{ old('amount', $employeeRate->amount) }}"
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
                                <option value="PLN" {{ old('currency', $employeeRate->currency) == 'PLN' ? 'selected' : '' }}>PLN</option>
                                <option value="EUR" {{ old('currency', $employeeRate->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="USD" {{ old('currency', $employeeRate->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                            </x-ui.input>
                        </div>
                    </div>


                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes', $employeeRate->notes) }}"
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('employee-rates.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
