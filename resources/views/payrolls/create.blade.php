<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wygeneruj Payroll">
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Wygeneruj Payroll">
                <x-ui.alert variant="info" class="mb-4">
                    <strong>Uwaga:</strong> Payroll zostanie wygenerowany na podstawie TimeLogów i EmployeeRate z wybranego okresu.
                    Wygenerowany payroll jest snapshotem i nie będzie automatycznie aktualizowany.
                </x-ui.alert>

                <form method="POST" action="{{ route('payrolls.store') }}">
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
                                type="date" 
                                name="period_start" 
                                id="period_start"
                                label="Okres od"
                                value="{{ old('period_start') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="period_end" 
                                id="period_end"
                                label="Okres do"
                                value="{{ old('period_end') }}"
                                required="true"
                            />
                        </div>
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
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Wygeneruj Payroll
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('payrolls.index') }}"
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
