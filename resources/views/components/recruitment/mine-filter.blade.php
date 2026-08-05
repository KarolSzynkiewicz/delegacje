@props(['mine' => false, 'mineCount' => null])

<button type="button" wire:click="toggleMine"
        class="btn btn-sm rp-topbar-btn {{ $mine ? 'btn-primary' : 'btn-outline-secondary' }}"
        title="{{ $mine ? 'Wyłącz filtr — pokaż wszystkich' : 'Pokaż tylko rekrutacje, w których jesteś prowadzącym' }}">
    <i class="bi bi-person-check me-1"></i>Moje
    @if($mineCount !== null)
        <span class="badge ms-1" style="font-size:.6rem;">{{ $mineCount }}</span>
    @endif
</button>
