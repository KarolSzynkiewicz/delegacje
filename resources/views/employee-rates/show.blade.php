<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Stawka: {{ $employeeRate->employee->full_name }}</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="primary" href="{{ route('employee-rates.edit', $employeeRate) }}">
                    <i class="bi bi-pencil"></i> Edytuj
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('employee-rates.index') }}">
                    <i class="bi bi-arrow-left"></i> Powrót
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row">
                <div class="col-lg-8">
                    <x-ui.card label="Szczegóły Stawki">
                        <x-ui.detail-list>
                            <x-ui.detail-item label="Pracownik:">
                                <a href="{{ route('employees.show', $employeeRate->employee) }}" class="text-primary text-decoration-none">
                                    {{ $employeeRate->employee->full_name }}
                                </a>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Data rozpoczęcia:">{{ $employeeRate->start_date->format('d.m.Y') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Data zakończenia:">{{ $employeeRate->end_date ? $employeeRate->end_date->format('d.m.Y') : '-' }}</x-ui.detail-item>
                            <x-ui.detail-item label="Kwota:">
                                <strong>{{ number_format($employeeRate->amount, 2, ',', ' ') }} {{ $employeeRate->currency }}</strong>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Status:">
                                @php
                                    if ($employeeRate->isCurrentlyActive()) {
                                        $statusLabel = 'Aktywna';
                                        $badgeVariant = 'success';
                                    } elseif ($employeeRate->isPast()) {
                                        $statusLabel = 'Zakończona';
                                        $badgeVariant = 'accent';
                                    } elseif ($employeeRate->isScheduled()) {
                                        $statusLabel = 'Zaplanowana';
                                        $badgeVariant = 'info';
                                    } else {
                                        $statusLabel = 'Nieznany';
                                        $badgeVariant = 'accent';
                                    }
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                            </x-ui.detail-item>
                            @if($employeeRate->notes)
                            <x-ui.detail-item label="Notatki:" :full-width="true">{{ $employeeRate->notes }}</x-ui.detail-item>
                            @endif
                            <x-ui.detail-item label="Utworzono:">{{ $employeeRate->created_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Zaktualizowano:">{{ $employeeRate->updated_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                        </x-ui.detail-list>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
