<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Projekty">
            <x-slot name="right">
                <x-ui.button variant="primary" href="{{ route('projects.create') }}" action="create">
                    Dodaj Projekt
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <livewire:projects-table />
</x-app-layout>
