<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ ! empty($extendPlanId) ? 'Dopisz do planu zmian' : (! empty($parentEventId) ? 'Dodaj transport' : 'Utwórz plan zmian') }}">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ ! empty($extendPlanId) ? route('transfers.show', $extendPlanId) : (! empty($parentEventId) ? url()->previous(route('transfers.index')) : route('transfers.index')) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @livewire('transfer-create-board', [
        'parentEventId' => $parentEventId ?? null,
        'extendPlanId' => $extendPlanId ?? null,
    ])
</x-app-layout>
