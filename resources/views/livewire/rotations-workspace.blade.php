<div class="rot-workspace">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <div class="rot-view-switch" role="group" aria-label="Widok rotacji">
            <button
                type="button"
                class="rot-view-switch__btn {{ $view === 'axis' ? 'is-active' : '' }}"
                wire:click="setView('axis')"
            >
                <i class="bi bi-bar-chart-steps" aria-hidden="true"></i>
                Widok osi
            </button>
            <button
                type="button"
                class="rot-view-switch__btn {{ $view === 'table' ? 'is-active' : '' }}"
                wire:click="setView('table')"
            >
                <i class="bi bi-table" aria-hidden="true"></i>
                Widok tabeli
            </button>
        </div>

        <x-ui.button variant="primary" href="{{ route('rotations.create') }}" class="btn-sm">
            <i class="bi bi-plus-circle"></i> Dodaj rotację
        </x-ui.button>
    </div>

    @if($view === 'table')
        <livewire:rotations-table wire:key="rotations-table-global" />
    @else
        <livewire:rotation-axis wire:key="rotations-axis-global" />
    @endif
</div>
