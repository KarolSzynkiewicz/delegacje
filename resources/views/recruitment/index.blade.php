<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Kandydatury rekrutacyjne">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('recruitment.apply') }}"
                    routeName="recruitment-processes.index"
                    action="create"
                >
                    Dodaj kandydata
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-recruitment.tabs active="candidates" />

    <livewire:recruitment-processes-table />
</x-app-layout>
