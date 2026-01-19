<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Mieszkania">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('accommodations.create') }}"
                    routeName="accommodations.create"
                    action="create"
                >
                    Dodaj Mieszkanie
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:accommodations-table />
</x-app-layout>
