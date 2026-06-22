<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Spółki">
            <x-slot name="right">
                <x-ui.button variant="primary" href="{{ route('companies.create') }}" routeName="companies.create" action="create">
                    Dodaj Spółkę
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:companies-table />
</x-app-layout>
