<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Projekty mojego zespołu</h2>
        </div>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    @if (empty($projectIds))
        <x-ui.card>
            <div class="text-center py-5">
                <i class="bi bi-folder-x fs-1 text-muted"></i>
                <p class="text-muted mt-3">Nie zarządzasz żadnymi projektami zespołu.</p>
            </div>
        </x-ui.card>
    @else
        <livewire:projects-table :filterProjectIds="$projectIds" />
    @endif
</x-app-layout>
