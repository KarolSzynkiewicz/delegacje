<x-app-layout :edgeToEdge="true">
    <x-slot name="header">
        <x-ui.page-header title="Zadania – Widok siatki">
            <x-slot name="right">
                <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-card-list me-1"></i>Widok kart
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:tasks-grid />
</x-app-layout>
