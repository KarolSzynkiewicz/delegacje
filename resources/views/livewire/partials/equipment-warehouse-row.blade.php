@props(['warehouse', 'count', 'canDelete'])

<tr wire:key="warehouse-row-{{ $warehouse->id }}">
    <td>
        <div class="fw-medium">{{ $warehouse->name }}</div>
        <div class="d-md-none small text-muted mt-1">{{ $warehouse->location?->name ?? '—' }}</div>
        @if($warehouse->is_default)
            <x-ui.badge variant="accent" class="mt-1">siedziba</x-ui.badge>
        @endif
    </td>
    <td class="d-none d-md-table-cell text-muted">{{ $warehouse->location?->name ?? '—' }}</td>
    <td class="text-end font-mono">{{ $count }}</td>
    <td class="text-end">
        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
            <x-ui.button
                variant="ghost"
                type="button"
                class="btn-sm"
                wire:click="openEditModal({{ $warehouse->id }})"
            >
                <i class="bi bi-pencil"></i> Edytuj
            </x-ui.button>
            <x-ui.button
                variant="ghost"
                href="{{ route('equipment.tab.stock', ['warehouse_id' => $warehouse->id]) }}"
                class="btn-sm"
            >
                <i class="bi bi-box-seam"></i> Asortyment
            </x-ui.button>
            @if($canDelete)
                <x-ui.button
                    variant="ghost"
                    type="button"
                    class="btn-sm text-danger"
                    wire:click="openDeleteModal({{ $warehouse->id }})"
                >
                    <i class="bi bi-trash"></i> Usuń
                </x-ui.button>
            @endif
        </div>
    </td>
</tr>
