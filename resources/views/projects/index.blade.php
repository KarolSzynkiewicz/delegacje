<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Projekty</h2>
            <x-ui.button variant="primary" href="{{ route('projects.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Projekt
            </x-ui.button>
        </div>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <livewire:projects-table />
</x-app-layout>
