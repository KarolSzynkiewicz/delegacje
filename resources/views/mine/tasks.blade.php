<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Moje zadania</h2>
        </div>
    </x-slot>

    @if (empty($projectIds))
        <x-ui.card>
            <div class="text-center py-5">
                <i class="bi bi-list-check fs-1 text-muted"></i>
                <p class="text-muted mt-3">Brak zadań w projektach zespołu.</p>
            </div>
        </x-ui.card>
    @else
        <livewire:tasks-table :filterProjectIds="$projectIds" :assignedToUserId="auth()->id()" />
    @endif
</x-app-layout>
