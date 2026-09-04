<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Zaliczkę">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('advances.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
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
                                <option value="{{ $employee->id }}" {{ (string) old('employee_id', request('employee_id')) === (string) $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                        <small class="form-text text-muted">Wybierz pracownika, którego dotyczy zaliczka</small>
                    </div>

                    <div class="mb-3">
                        <x-ui.input
                            type="select"
                            name="payroll_id"
                            id="payroll_id"
                            label="Payroll (opcjonalnie)"
                        >
                            <option value="">— przypisz później —</option>
                            @foreach($payrolls as $payroll)
                                <option
                                    value="{{ $payroll->id }}"
                                    data-employee-id="{{ $payroll->employee_id }}"
                                    {{ old('payroll_id') == $payroll->id ? 'selected' : '' }}
                                >
                                    {{ $payroll->display_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                        <small class="form-text text-muted">Jeśli payroll nie istnieje jeszcze, zostaw puste</small>
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
                                @foreach(\App\Enums\Currency::cases() as $c)
                                    <option value="{{ $c->value }}" {{ old('currency', 'PLN') == $c->value ? 'selected' : '' }}>
                                        {{ $c->label() }}
                                    </option>
                                @endforeach
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
                        <x-ui.button
                            variant="primary"
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                        <x-ui.button
                            variant="ghost"
                            href="{{ route('advances.index') }}"
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const employeeSelect = document.getElementById('employee_id');
    const payrollSelect = document.getElementById('payroll_id');
    const interestCheckbox = document.getElementById('is_interest_bearing');
    const interestField = document.getElementById('interest_rate_field');

    if (interestCheckbox && interestField) {
        interestCheckbox.addEventListener('change', function () {
            interestField.style.display = this.checked ? 'block' : 'none';
        });
    }

    if (!employeeSelect || !payrollSelect) return;

    function syncPayrollOptions() {
        const employeeId = employeeSelect.value;
        const options = Array.from(payrollSelect.options);

        options.forEach((opt) => {
            const optEmployeeId = opt.getAttribute('data-employee-id');
            if (!optEmployeeId) return;
            const match = employeeId && optEmployeeId === employeeId;
            opt.hidden = !match;
            opt.disabled = !match;
        });

        const selected = payrollSelect.selectedOptions[0];
        if (selected && selected.getAttribute('data-employee-id') && selected.disabled) {
            payrollSelect.value = '';
        }
    }

    employeeSelect.addEventListener('change', syncPayrollOptions);
    syncPayrollOptions();
});
</script>
