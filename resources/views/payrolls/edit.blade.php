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

                @php
                    $payroll->load(['adjustments', 'advances']);
                @endphp

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
                            <small class="text-muted d-block">Korekty:</small>
                            <strong class="{{ $payroll->adjustments_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($payroll->adjustments_amount, 2, ',', ' ') }} {{ $payroll->currency }}
                            </strong>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <small class="text-muted d-block">Razem:</small>
                            <strong class="text-success fs-5">{{ number_format($payroll->total_amount, 2, ',', ' ') }} {{ $payroll->currency }}</strong>
                        </div>
                    </div>
                </div>

                @if($payroll->adjustments->count() > 0 || $payroll->advances->count() > 0)
                <div class="mb-4">
                    <h5 class="mb-3">Szczegóły korekt</h5>
                    
                    @if($payroll->adjustments->count() > 0)
                    <div class="mb-3">
                        <h6>Kary i nagrody:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Typ</th>
                                        <th>Kwota</th>
                                        <th>Notatki</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payroll->adjustments as $adjustment)
                                    <tr>
                                        <td>{{ $adjustment->date->format('d.m.Y') }}</td>
                                        <td>
                                            <span class="badge {{ $adjustment->type === 'bonus' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $adjustment->type === 'bonus' ? 'Nagroda' : 'Kara' }}
                                            </span>
                                        </td>
                                        <td class="{{ $adjustment->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($adjustment->amount, 2, ',', ' ') }} {{ $adjustment->currency }}
                                        </td>
                                        <td>{{ $adjustment->notes ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($payroll->advances->count() > 0)
                    <div class="mb-3">
                        <h6>Zaliczki:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Kwota</th>
                                        <th>Oprocentowanie</th>
                                        <th>Do odliczenia</th>
                                        <th>Notatki</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payroll->advances as $advance)
                                    <tr>
                                        <td>{{ $advance->date->format('d.m.Y') }}</td>
                                        <td>{{ number_format($advance->amount, 2, ',', ' ') }} {{ $advance->currency }}</td>
                                        <td>
                                            @if($advance->is_interest_bearing && $advance->interest_rate)
                                                {{ number_format($advance->interest_rate, 2, ',', ' ') }}%
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-danger">
                                            <strong>{{ number_format($advance->getTotalDeductionAmount(), 2, ',', ' ') }} {{ $advance->currency }}</strong>
                                        </td>
                                        <td>{{ $advance->notes ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <form method="POST" action="{{ route('payrolls.update', $payroll) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Korekty (obliczane automatycznie)</label>
                        <div class="form-control-plaintext">
                            <strong>{{ number_format($payroll->adjustments_amount, 2, ',', ' ') }} {{ $payroll->currency }}</strong>
                            <small class="text-muted d-block mt-1">
                                Kwota jest obliczana automatycznie na podstawie zaliczek, kar i nagród przypisanych do tego payroll.
                            </small>
                        </div>
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
                            <option value="issued" {{ old('status', $payroll->status->value) == 'issued' ? 'selected' : '' }}>Wystawiony</option>
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
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
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
