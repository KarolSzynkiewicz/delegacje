<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Oceny Pracowników</h2>
            <x-ui.button variant="primary" href="{{ route('employee-evaluations.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Ocenę
            </x-ui.button>
        </div>
    </x-slot>

    <livewire:employee-evaluations-table />
</x-app-layout>
