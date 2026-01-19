<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Stawki Pracowników">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('employee-rates.create') }}"
                    routeName="employee-rates.create"
                    action="create"
                >
                    Dodaj Stawkę
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:employee-rates-table />
</x-app-layout>
