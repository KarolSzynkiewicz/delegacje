@php
    $hasVariants = $item->hasVariants();
    $archived = $archived ?? false;
@endphp
<tr style="background:rgba(255,255,255,.025);border-top:2px solid var(--glass-border);">
    <td style="padding:.65rem 1rem;">
        <div class="d-flex align-items-baseline flex-wrap gap-2">
            <span class="fw-semibold" style="font-size:.92rem;">{{ $item->name }}</span>
            @if($item->description)
                <span style="font-size:.82rem;color:var(--text-muted);">{{ \Illuminate\Support\Str::limit($item->description, 90) }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
            @if($item->category)
                <span class="badge badge-secondary" style="font-size:.62rem;">{{ $item->category }}</span>
            @endif
            @if($item->variant_label)
                <span style="font-size:.72rem;color:var(--text-muted);">{{ $item->variant_label }}</span>
            @endif
            @if($archived && $item->removed_at)
                <span style="font-size:.72rem;color:var(--text-muted);">Usunięto {{ $item->removed_at->format('Y-m-d') }}</span>
            @endif
        </div>
    </td>
    @include('equipment._qty-cell', ['value' => $item->quantityIn($warehouse)])
    @include('equipment._qty-cell', ['value' => $item->quantityInOthers($warehouse)])
    @include('equipment._qty-cell', ['value' => $item->issuedOutstandingIn($warehouse)])
    @include('equipment._qty-cell', ['value' => $item->issuedOutstandingInOthers($warehouse)])
    <td style="padding:.65rem .75rem;">
        @if($archived)
            <div class="d-flex justify-content-end gap-1">
                <x-view-button href="{{ route('equipment.show', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}" />
                <form
                    action="{{ route('equipment.restore', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Przywrócić tę pozycję do bieżącego asortymentu?')"
                >
                    @csrf
                    <x-ui.button variant="ghost" type="submit" title="Przywróć" class="btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </x-ui.button>
                </form>
            </div>
        @else
            <x-action-buttons
                viewRoute="{{ route('equipment.show', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}"
                editRoute="{{ route('equipment.edit', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}"
                deleteRoute="{{ route('equipment.destroy', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}"
                deleteMessage="Przenieść tę pozycję do asortymentu historycznego? Stan i historia wydań zostaną zachowane."
            />
        @endif
    </td>
</tr>

@if($hasVariants)
    @forelse ($item->variants as $variant)
        <tr style="font-size:.82rem;">
            <td style="padding:.45rem 1rem .45rem 1.75rem;color:var(--text-muted);white-space:nowrap;">
                <i class="bi bi-arrow-return-right me-1" style="font-size:.65rem;"></i>
                {{ $variant->kind_label }}
            </td>
            @include('equipment._qty-cell', [
                'value' => $variant->quantityIn($warehouse),
                'compact' => true,
            ])
            @include('equipment._qty-cell', [
                'value' => $variant->quantityInOthers($warehouse),
                'compact' => true,
            ])
            @include('equipment._qty-cell', [
                'value' => $variant->issuedOutstandingIn($warehouse),
                'compact' => true,
            ])
            @include('equipment._qty-cell', [
                'value' => $variant->issuedOutstandingInOthers($warehouse),
                'compact' => true,
            ])
            <td></td>
        </tr>
    @empty
        <tr style="font-size:.82rem;">
            <td colspan="6" style="padding:.45rem 1rem .45rem 1.75rem;color:var(--text-muted);">
                <i class="bi bi-arrow-return-right me-1" style="font-size:.65rem;"></i>
                Brak wariantów
            </td>
        </tr>
    @endforelse
@endif
