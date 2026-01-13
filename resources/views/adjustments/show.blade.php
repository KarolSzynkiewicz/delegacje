<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Kara/Nagroda: {{ $adjustment->employee->full_name }}</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="primary" href="{{ route('adjustments.edit', $adjustment) }}">
                    <i class="bi bi-pencil"></i> Edytuj
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('adjustments.index') }}">
                    <i class="bi bi-arrow-left"></i> Powrót
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row">
                <div class="col-lg-8">
                    <x-ui.card label="Szczegóły Kary/Nagrody">
                        <x-ui.detail-list>
                            <x-ui.detail-item label="Pracownik:">
                                <a href="{{ route('employees.show', $adjustment->employee) }}" class="text-primary text-decoration-none">
                                    {{ $adjustment->employee->full_name }}
                                </a>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Typ:">
                                <span class="badge {{ $adjustment->type === 'bonus' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $adjustment->type === 'bonus' ? 'Nagroda' : 'Kara' }}
                                </span>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Kwota:">
                                <strong class="{{ $adjustment->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($adjustment->amount, 2, ',', ' ') }} {{ $adjustment->currency }}
                                </strong>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Data:">{{ $adjustment->date->format('d.m.Y') }}</x-ui.detail-item>
                            @if($adjustment->notes)
                            <x-ui.detail-item label="Notatki:" :full-width="true">{{ $adjustment->notes }}</x-ui.detail-item>
                            @endif
                            <x-ui.detail-item label="Utworzono:">{{ $adjustment->created_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Zaktualizowano:">{{ $adjustment->updated_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                        </x-ui.detail-list>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
