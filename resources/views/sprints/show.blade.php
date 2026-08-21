<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$sprint->name">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('sprints.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <a href="{{ route('tasks.grid', ['groupBy' => 'sprint']) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-kanban me-1"></i>Siatka
                </a>
                <x-ui.button variant="ghost" href="{{ route('sprints.edit', $sprint) }}" routeName="sprints.edit" action="edit">
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <livewire:sprint-board :sprint="$sprint" />

    <div class="mt-4">
        <div class="fw-semibold mb-2">Backlog sprintu</div>
        <livewire:tasks-grid :locked-sprint-id="$sprint->id" :key="'sprint-grid-'.$sprint->id" />
    </div>

    <div class="mt-4">
        <x-comments :commentable="$sprint" />
    </div>

    <div class="mt-4">
        <livewire:sprint-activity :sprint="$sprint" :key="'sprint-activity-'.$sprint->id" />
    </div>
</x-app-layout>

