<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wydaj">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment.tab.issues') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <livewire:warehouse-issue-form :warehouse="$warehouse" />
    </x-ui.card>
</x-app-layout>
