<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj konto bankowe">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ $backUrl }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj konto bankowe">
                <x-ui.errors />

                <form method="POST" action="{{ route('employee-bank-accounts.update', $employeeBankAccount) }}">
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
                                <option value="{{ $employee->id }}" {{ (string) old('employee_id', $employeeBankAccount->employee_id) === (string) $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->email }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input
                            type="text"
                            name="account_number"
                            id="account_number"
                            label="Numer konta"
                            value="{{ old('account_number', $employeeBankAccount->formattedAccountNumber()) }}"
                            required="true"
                            placeholder="NRB 26 cyfr lub IBAN"
                            class="font-mono"
                        />
                        <small class="form-text text-muted">26 cyfr (NRB) albo IBAN, np. PL + 26 cyfr. Spacje są ignorowane.</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input
                                type="date"
                                name="start_date"
                                id="start_date"
                                label="Data rozpoczęcia"
                                value="{{ old('start_date', $employeeBankAccount->start_date->format('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input
                                type="date"
                                name="end_date"
                                id="end_date"
                                label="Data zakończenia (opcjonalnie)"
                                value="{{ old('end_date', $employeeBankAccount->end_date ? $employeeBankAccount->end_date->format('Y-m-d') : '') }}"
                            />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input
                            type="textarea"
                            name="notes"
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes', $employeeBankAccount->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit" action="save">
                            Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ $backUrl }}" action="cancel">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
