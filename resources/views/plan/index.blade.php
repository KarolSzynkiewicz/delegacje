<x-app-layout :edgeToEdge="true">
    <x-slot name="header">
        <x-ui.page-header title="Plan">
            <x-slot name="right">
                <a href="{{ route('tasks.grid') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-list-check me-1"></i>Backlog
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:work-item-plan />
</x-app-layout>
