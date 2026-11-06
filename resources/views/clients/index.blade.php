<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Kontrahenci">
            <x-slot name="right">
                <x-ui.button variant="primary" href="{{ route('clients.create') }}" routeName="clients.create" action="create">
                    Dodaj kontrahenta
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:clients-table />
</x-app-layout>
