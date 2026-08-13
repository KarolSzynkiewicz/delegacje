<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$equipmentIssue->isPermanentIssue() ? 'Wydanie bezzwrotne' : 'Wydanie do zwrotu'">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('equipment.tab.issues') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($equipmentIssue->isReturnableIssue() && $equipmentIssue->equipment->issuable && $equipmentIssue->equipment->returnable)
                    <x-ui.button 
                        variant="success" 
                        href="{{ route('equipment-issues.return', $equipmentIssue) }}"
                        routeName="equipment-issues.return"
                        action="save"
                    >
                        Zwróć/Zgłoś
                    </x-ui.button>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Informacje podstawowe">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Typ</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->equipment->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">{{ $equipmentIssue->equipment->variant_label ?: 'Rodzaj' }}</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->variant?->kind_label ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Pracownik</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->employee->full_name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Ilość</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->quantity_issued }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Data wydania</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->issue_date->format('Y-m-d') }}</p>
                    </div>
                    @if($equipmentIssue->expected_return_date)
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Oczekiwana data zwrotu</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->expected_return_date->format('Y-m-d') }}</p>
                    </div>
                    @endif
                    @if($equipmentIssue->actual_return_date)
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Rzeczywista data zwrotu</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->actual_return_date->format('Y-m-d') }}</p>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Status</h6>
                        <x-ui.badge variant="{{ $equipmentIssue->statusBadgeVariant() }}">{{ $equipmentIssue->statusLabel() }}</x-ui.badge>
                    </div>
                    @if($equipmentIssue->projectAssignment)
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Projekt</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->projectAssignment->project->name }}</p>
                    </div>
                    @endif
                    @if($equipmentIssue->notes)
                    <div class="col-12">
                        <h6 class="text-muted small mb-1">Notatki</h6>
                        <p class="mb-0">{{ $equipmentIssue->notes }}</p>
                    </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
