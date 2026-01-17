<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Koszt Stały</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="ghost" href="{{ route('fixed-costs.edit', $fixedCost) }}">
                    <i class="bi bi-pencil"></i> Edytuj
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('fixed-costs.index') }}">
                    <i class="bi bi-arrow-left"></i> Powrót
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <x-ui.card>
                <x-ui.detail-list>
                    <x-ui.detail-item label="Nazwa:">{{ $fixedCost->name }}</x-ui.detail-item>
                    <x-ui.detail-item label="Kwota:">
                        <strong>{{ number_format($fixedCost->amount, 2) }} {{ $fixedCost->currency }}</strong>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data kosztu:">{{ $fixedCost->cost_date->format('Y-m-d') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Data rozpoczęcia:">{{ $fixedCost->start_date->format('Y-m-d') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Data zakończenia:">
                        {{ $fixedCost->end_date ? $fixedCost->end_date->format('Y-m-d') : 'Bieżące' }}
                    </x-ui.detail-item>
                    @if($fixedCost->notes)
                        <x-ui.detail-item label="Notatki:" :full-width="true">{{ $fixedCost->notes }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
