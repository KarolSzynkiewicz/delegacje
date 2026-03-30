<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Lokalizacje">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('locations.create') }}"
                    routeName="locations.create"
                    action="create"
                >
                    Dodaj Lokalizację
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <livewire:locations-table />
</x-app-layout>
