<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Projekty</h2>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Projekt
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <livewire:projects-table />
</x-app-layout>
