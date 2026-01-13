<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Edytuj Payroll</h2>
            <x-ui.button variant="ghost" href="{{ route('payrolls.index') }}">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Payroll">
                <x-ui.alert variant="warning" class="mb-4">
                    <strong>Uwaga:</strong> Payroll jest snapshotem. Możesz edytować tylko korekty (adjustments_amount) i status.
                    Kwota z godzin (hours_amount) jest niemutowalna.
                </x-ui.alert>

                <div class="mb-4 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Pracownik:</small>
                            <strong>{{ $payroll->employee->full_name }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Okres:</small>
                            <strong>{{ $payroll->period_start->format('d.m.Y') }} - {{ $payroll->period_end->format('d.m.Y') }}</strong>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Kwota z godzin:</small>
                            <strong>{{ number_format($payroll->hours_amount, 2, ',', ' ') }} {{ $payroll->currency }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Razem:</small>
                            <strong class="text-success">{{ number_format($payroll->total_amount, 2, ',', ' ') }} {{ $payroll->currency }}</strong>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('payrolls.update', $payroll) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="adjustments_amount" 
                            id="adjustments_amount"
                            label="Korekty (dodatnie lub ujemne)"
                            value="{{ old('adjustments_amount', $payroll->adjustments_amount) }}"
                            step="0.01"
                            required="true"
                        />
                        <small class="form-text text-muted">Wprowadź dodatnią wartość dla premii lub ujemną dla kar.</small>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="status" 
                            id="status"
                            label="Status"
                            required="true"
                        >
                            <option value="draft" {{ old('status', $payroll->status->value) == 'draft' ? 'selected' : '' }}>Szkic</option>
                            <option value="approved" {{ old('status', $payroll->status->value) == 'approved' ? 'selected' : '' }}>Zatwierdzony</option>
                            <option value="paid" {{ old('status', $payroll->status->value) == 'paid' ? 'selected' : '' }}>Wypłacony</option>
                        </x-ui.input>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes', $payroll->notes) }}"
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('payrolls.index') }}">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
