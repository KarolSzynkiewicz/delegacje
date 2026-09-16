@include('livewire.partials.tg-filter-op', ['field' => 'status'])
<div class="rp-filter-chips">
    <button type="button" wire:click="setStatusBucket('active')"
            class="rp-filter-chip {{ $this->currentStatusBucket() === '' ? 'is-active' : '' }}">Aktywne</button>
    <button type="button" wire:click="setStatusBucket('closed')"
            class="rp-filter-chip {{ $this->currentStatusBucket() === 'closed' ? 'is-active' : '' }}">Zamknięte</button>
    <button type="button" wire:click="setStatusBucket('all')"
            class="rp-filter-chip {{ $this->currentStatusBucket() === 'all' ? 'is-active' : '' }}">Wszystkie</button>
</div>
<span class="rp-filter-hint mt-2 d-block">Konkretne statusy (kilka = lub)</span>
<div class="rp-filter-chips">
    @foreach(\App\Enums\TaskStatus::cases() as $st)
        <button type="button" wire:click="toggleStatusValue('{{ $st->value }}')"
                class="rp-filter-option {{ in_array($st->value, $selectedStatuses, true) ? 'is-active' : '' }}">
            <span class="rp-filter-check {{ in_array($st->value, $selectedStatuses, true) ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
            <span class="rp-filter-option__label">{{ $st->label() }}</span>
        </button>
    @endforeach
</div>
