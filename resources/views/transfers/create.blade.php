<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz transfer">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @livewire('transfer-create-board')
</x-app-layout>
