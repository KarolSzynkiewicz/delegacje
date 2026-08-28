@props(['warehouse', 'count', 'canDelete'])

<x-ui.card class="dt-card" wire:key="warehouse-card-{{ $warehouse->id }}">
    <div class="d-flex align-items-start gap-3 mb-2">
        <div class="eq-wh-card__icon flex-shrink-0" aria-hidden="true" style="width:2.5rem;height:2.5rem;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;background:var(--bg-input);border:1px solid var(--glass-border);">
            <i class="bi {{ $warehouse->is_default ? 'bi-building' : 'bi-box-seam' }}"></i>
        </div>
        <div class="flex-grow-1 min-width-0">
            <div class="dt-card__title mb-0">
                <a href="{{ route('equipment.tab.stock', ['warehouse_id' => $warehouse->id]) }}" class="stretched-link">{{ $warehouse->name }}</a>
            </div>
            <div class="small text-muted">{{ $warehouse->location?->name ?? '—' }}</div>
            @if($warehouse->is_default)
                <x-ui.badge variant="accent" class="mt-1">siedziba</x-ui.badge>
            @endif
        </div>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Pozycje na stanie</span>
        <span class="dt-card__value font-mono">{{ $count }}</span>
    </div>

    <div class="dt-card__actions">
        <x-ui.button variant="ghost" type="button" class="btn-sm" wire:click="openEditModal({{ $warehouse->id }})">
            <i class="bi bi-pencil"></i> Edytuj
        </x-ui.button>
        @if($canDelete)
            <x-ui.button variant="ghost" type="button" class="btn-sm text-danger" wire:click="openDeleteModal({{ $warehouse->id }})">
                <i class="bi bi-trash"></i> Usuń
            </x-ui.button>
        @endif
    </div>
</x-ui.card>
