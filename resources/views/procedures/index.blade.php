<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Procedury (SOP)">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('dashboard') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:procedure-templates-index />
</x-app-layout>
