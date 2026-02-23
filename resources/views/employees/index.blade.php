<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Pracownicy">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('employees.create') }}"
                    routeName="employees.create"
                    action="create"
                >
                    Dodaj Pracownika
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:employees-table />
</x-app-layout>
