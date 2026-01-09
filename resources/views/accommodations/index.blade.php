<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Mieszkania</h2>
            <a href="{{ route('accommodations.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Mieszkanie
            </a>
        </div>
    </x-slot>

    <livewire:accommodations-table />
</x-app-layout>
