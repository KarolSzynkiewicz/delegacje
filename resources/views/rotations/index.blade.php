<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wszystkie Rotacje Pracowników">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('rotations.create') }}"
                    routeName="rotations.create"
                    action="create"
                >
                    Dodaj Rotację
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:rotations-table />
</x-app-layout>
