<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zjazdy">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('return-trips.create') }}"
                    routeName="return-trips.create"
                    action="create"
                >
                    Utwórz Zjazd
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:return-trips-table />
</x-app-layout>
