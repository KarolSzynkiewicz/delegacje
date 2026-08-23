<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wyjazdy">
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('departures.create-v2') }}"
                    routeName="departures.create-v2"
                    action="create"
                >
                    Utwórz wyjazd
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:departures-table />
</x-app-layout>
