<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszt Stały">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.edit', $fixedCost) }}"
                    routeName="fixed-costs.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

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
</x-app-layout>
