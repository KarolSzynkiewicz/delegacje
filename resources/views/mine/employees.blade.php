<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Pracownicy mojego zespołu</h2>
        </div>
    </x-slot>

    @if (empty($employeeIds))
        <x-ui.card>
            <div class="text-center py-5">
                <i class="bi bi-people fs-1 text-muted"></i>
                <p class="text-muted mt-3">Brak pracowników w projektach zespołu.</p>
            </div>
        </x-ui.card>
    @else
        <livewire:employees-table 
            :filterEmployeeIds="$employeeIds" 
            :filterProjectIds="$projectIds" 
        />
    @endif
</x-app-layout>
