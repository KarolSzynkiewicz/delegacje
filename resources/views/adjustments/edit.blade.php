<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Edytuj Karę/Nagrodę</h2>
            <x-ui.button variant="ghost" href="{{ route('adjustments.index') }}">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
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
                                    {{ $employee->full_name }} ({{ $employee->email }})
                                </option>
                            @endforeach
                        </x-ui.input>
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
                                <option value="PLN" {{ old('currency', $adjustment->currency) == 'PLN' ? 'selected' : '' }}>PLN</option>
                                <option value="EUR" {{ old('currency', $adjustment->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="USD" {{ old('currency', $adjustment->currency) == 'USD' ? 'selected' : '' }}>USD</option>
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
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('adjustments.index') }}">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
