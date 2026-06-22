<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przypisania do spółek">
            <x-slot name="right">
                <x-ui.button variant="primary" href="{{ route('company-assignments.create') }}" routeName="company-assignments.create" action="create">
                    Dodaj Przypisanie
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:company-assignments-table />
</x-app-layout>
