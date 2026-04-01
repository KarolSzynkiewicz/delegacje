<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Karę/Nagrodę">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('adjustments.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Karę/Nagrodę">
                <form method="POST" action="{{ route('adjustments.update', $adjustment) }}">
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
                                <option value="{{ $employee->id }}" {{ old('employee_id', $adjustment->employee_id) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                        <small class="form-text text-muted">Pracownik, którego dotyczy kara/nagroda</small>
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
                                    {{ old('payroll_id', $adjustment->payroll_id) == $payroll->id ? 'selected' : '' }}
                                >
                                    {{ $payroll->display_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                        <small class="form-text text-muted">Payroll można przypisać później</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="select" 
                                name="type" 
                                id="type"
                                label="Typ"
                                required="true"
                            >
                                <option value="penalty" {{ old('type', $adjustment->type) == 'penalty' ? 'selected' : '' }}>Kara</option>
                                <option value="bonus" {{ old('type', $adjustment->type) == 'bonus' ? 'selected' : '' }}>Nagroda</option>
                            </x-ui.input>
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="date" 
                                id="date"
                                label="Data"
                                value="{{ old('date', $adjustment->date->format('Y-m-d')) }}"
                                required="true"
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
                                value="{{ old('amount', $adjustment->amount) }}"
                                step="0.01"
                                required="true"
                            />
                            <small class="form-text text-muted">Dodatnia dla nagrody, ujemna dla kary</small>
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
                                    <option value="{{ $c->value }}" {{ old('currency', $adjustment->currency) == $c->value ? 'selected' : '' }}>
                                        {{ $c->label() }}
                                    </option>
                                @endforeach
                            </x-ui.input>
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes', $adjustment->notes) }}"
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
                            href="{{ route('adjustments.index') }}"
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

    if (!employeeSelect || !payrollSelect) return;

    function syncPayrollOptions() {
        const employeeId = employeeSelect.value;
        const options = Array.from(payrollSelect.options);

        options.forEach((opt) => {
            const optEmployeeId = opt.getAttribute('data-employee-id');
            if (!optEmployeeId) return; // placeholder
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
