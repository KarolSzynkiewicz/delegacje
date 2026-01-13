<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Payroll: {{ $payroll->employee->full_name }}</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="primary" href="{{ route('payrolls.edit', $payroll) }}">
                    <i class="bi bi-pencil"></i> Edytuj
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('payrolls.index') }}">
                    <i class="bi bi-arrow-left"></i> Powrót
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row">
                <div class="col-lg-8">
                    <x-ui.card label="Szczegóły Payroll">
                        <x-ui.detail-list>
                            <x-ui.detail-item label="Pracownik:">
                                <a href="{{ route('employees.show', $payroll->employee) }}" class="text-primary text-decoration-none">
                                    {{ $payroll->employee->full_name }}
                                </a>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Okres od:">{{ $payroll->period_start->format('d.m.Y') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Okres do:">{{ $payroll->period_end->format('d.m.Y') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Kwota z godzin:">
                                <strong>{{ number_format($payroll->hours_amount, 2, ',', ' ') }} {{ $payroll->currency }}</strong>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Korekty:">
                                {{ number_format($payroll->adjustments_amount, 2, ',', ' ') }} {{ $payroll->currency }}
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Razem:">
                                <strong class="text-success fs-5">{{ number_format($payroll->total_amount, 2, ',', ' ') }} {{ $payroll->currency }}</strong>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Status:">
                                @php
                                    $badgeVariant = match($payroll->status->value) {
                                        'draft' => 'accent',
                                        'approved' => 'info',
                                        'paid' => 'success',
                                        default => 'accent'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $payroll->status->label() }}</x-ui.badge>
                            </x-ui.detail-item>
                            @if($payroll->notes)
                            <x-ui.detail-item label="Notatki:" :full-width="true">{{ $payroll->notes }}</x-ui.detail-item>
                            @endif
                            <x-ui.detail-item label="Utworzono:">{{ $payroll->created_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Zaktualizowano:">{{ $payroll->updated_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                        </x-ui.detail-list>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
