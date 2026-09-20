<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Sprinty">
            <x-slot name="right">
                <a href="{{ route('tasks.grid') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-grid-3x3-gap me-1"></i>Siatka zadań
                </a>
                <x-ui.button
                    variant="primary"
                    href="{{ route('sprints.create') }}"
                    routeName="sprints.create"
                    action="create"
                >
                    Nowy sprint
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <livewire:sprints-table />
</x-app-layout>
