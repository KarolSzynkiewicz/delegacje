<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Zaliczka: {{ $advance->employee->full_name }}</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="primary" href="{{ route('advances.edit', $advance) }}">
                    <i class="bi bi-pencil"></i> Edytuj
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('advances.index') }}">
                    <i class="bi bi-arrow-left"></i> Powrót
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row">
                <div class="col-lg-8">
                    <x-ui.card label="Szczegóły Zaliczki">
                        <x-ui.detail-list>
                            <x-ui.detail-item label="Pracownik:">
                                <a href="{{ route('employees.show', $advance->employee) }}" class="text-primary text-decoration-none">
                                    {{ $advance->employee->full_name }}
                                </a>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Kwota:">
                                <strong>{{ number_format($advance->amount, 2, ',', ' ') }} {{ $advance->currency }}</strong>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Data:">{{ $advance->date->format('d.m.Y') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Oprocentowana:">
                                <span class="badge {{ $advance->is_interest_bearing ? 'bg-warning' : 'bg-secondary' }}">
                                    {{ $advance->is_interest_bearing ? 'Tak' : 'Nie' }}
                                </span>
                            </x-ui.detail-item>
                            @if($advance->is_interest_bearing && $advance->interest_rate)
                            <x-ui.detail-item label="Stawka oprocentowania:">
                                <strong>{{ number_format($advance->interest_rate, 2, ',', ' ') }}%</strong>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Do odliczenia (z oprocentowaniem):">
                                <strong class="text-danger">{{ number_format($advance->getTotalDeductionAmount(), 2, ',', ' ') }} {{ $advance->currency }}</strong>
                            </x-ui.detail-item>
                            @endif
                            @if($advance->notes)
                            <x-ui.detail-item label="Notatki:" :full-width="true">{{ $advance->notes }}</x-ui.detail-item>
                            @endif
                            <x-ui.detail-item label="Utworzono:">{{ $advance->created_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Zaktualizowano:">{{ $advance->updated_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                        </x-ui.detail-list>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
