<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Zadania</h2>
        </div>
    </x-slot>

    <livewire:tasks-table :assignedToUserId="auth()->id()" />
</x-app-layout>
