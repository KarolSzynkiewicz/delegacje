<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Przypisania mojego zespołu</h2>
        </div>
    </x-slot>

    @if (empty($projectIds))
        <x-ui.card>
            <div class="text-center py-5">
                <i class="bi bi-person-check fs-1 text-muted"></i>
                <p class="text-muted mt-3">Brak przypisań w projektach zespołu.</p>
            </div>
        </x-ui.card>
    @else
        <livewire:assignments-table :filterProjectIds="$projectIds" />
    @endif
</x-app-layout>
