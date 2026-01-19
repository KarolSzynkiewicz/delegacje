<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Pojazdy">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('vehicles.create') }}"
                    routeName="vehicles.create"
                    action="create"
                >
                    Dodaj Pojazd
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:vehicles-table />
</x-app-layout>
