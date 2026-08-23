@php
    $hasVariants = $item->hasVariants();
    $withdrawn = $item->isArchived();
    $variantCount = $hasVariants ? $item->variants->count() : 0;
    $search = mb_strtolower(trim((string) request('search')));
    $variantsOpen = $hasVariants && $search !== '' && $item->variants->contains(
        fn ($variant) => str_contains(mb_strtolower((string) $variant->value), $search)
    );
@endphp
<tr class="eq-stock-item{{ $hasVariants ? ' has-variants' : '' }}{{ $variantsOpen ? ' is-open' : '' }}{{ $withdrawn ? ' is-withdrawn' : '' }}" @if($hasVariants) data-eq-stock-item="{{ $item->id }}" @endif>
    <td>
        <div class="eq-stock-item__main">
            @if($item->image_url)
                <img
                    src="{{ $item->image_url }}"
                    alt=""
                    class="eq-stock-item__photo"
                >
            @else
                <span class="eq-stock-item__photo is-placeholder" aria-hidden="true">
                    <i class="bi bi-box-seam"></i>
                </span>
            @endif
            <div class="eq-stock-item__text">
                <div class="eq-stock-item__title">
                    <span class="eq-stock-item__name">{{ $item->name }}</span>
                    @if($item->description)
                        <span class="eq-stock-item__desc">{{ \Illuminate\Support\Str::limit($item->description, 90) }}</span>
                    @endif
                </div>
                <div class="eq-stock-item__meta">
                    @if($withdrawn)
                        <x-ui.badge variant="secondary">Wycofane</x-ui.badge>
                    @endif
                    @if($item->category)
                        <span class="eq-stock-item__category">{{ $item->category }}</span>
                    @endif
                    @if($hasVariants)
                        <button
                            type="button"
                            class="eq-stock-item__toggle"
                            data-eq-stock-toggle="{{ $item->id }}"
                            aria-expanded="{{ $variantsOpen ? 'true' : 'false' }}"
                        >
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            {{ $item->variant_label ?: 'Warianty' }}
                            <span class="eq-stock-item__toggle-count">{{ $variantCount }}</span>
                        </button>
                    @elseif($item->variant_label)
                        <span>{{ $item->variant_label }}</span>
                    @endif
                    @if($withdrawn && $item->removed_at)
                        <span>Wycofano {{ $item->removed_at->format('Y-m-d') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </td>
    @include('equipment._qty-cell', ['value' => $item->quantityIn($warehouse), 'tone' => 'stock'])
    @include('equipment._qty-cell', ['value' => $item->reservedIn($warehouse), 'tone' => 'reserved'])
    @include('equipment._qty-cell', ['value' => $item->quantityInOthers($warehouse)])
    @include('equipment._qty-cell', ['value' => $item->issuedOutstandingIn($warehouse), 'tone' => 'return'])
    @include('equipment._qty-cell', ['value' => $item->issuedOutstandingInOthers($warehouse)])
    <td class="eq-stock-item__actions">
        @if($withdrawn)
            <div class="d-flex justify-content-end gap-1">
                <x-view-button href="{{ route('equipment.show', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}" />
                <form
                    action="{{ route('equipment.restore', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Przywrócić tę pozycję do asortymentu?')"
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
                deleteTitle="Wycofaj"
                deleteMessage="Wycofać tę pozycję z ewidencji? Historia wydań zostanie zachowana — nie będziemy już śledzić jej stanu."
            />
        @endif
    </td>
</tr>

@if($hasVariants)
    @forelse ($item->variants as $variant)
        <tr
            class="eq-stock-variant{{ $loop->last ? ' is-last' : '' }}"
            data-eq-stock-parent="{{ $item->id }}"
            @if(! $variantsOpen) hidden @endif
        >
            <td>
                <span class="eq-stock-variant__branch" aria-hidden="true"></span>
                {{ $variant->kind_label }}
            </td>
            @include('equipment._qty-cell', [
                'value' => $variant->quantityIn($warehouse),
                'compact' => true,
                'tone' => 'stock',
            ])
            @include('equipment._qty-cell', [
                'value' => $variant->reservedIn($warehouse),
                'compact' => true,
                'tone' => 'reserved',
            ])
            @include('equipment._qty-cell', [
                'value' => $variant->quantityInOthers($warehouse),
                'compact' => true,
            ])
            @include('equipment._qty-cell', [
                'value' => $variant->issuedOutstandingIn($warehouse),
                'compact' => true,
                'tone' => 'return',
            ])
            @include('equipment._qty-cell', [
                'value' => $variant->issuedOutstandingInOthers($warehouse),
                'compact' => true,
            ])
            <td></td>
        </tr>
    @empty
        <tr
            class="eq-stock-variant is-last"
            data-eq-stock-parent="{{ $item->id }}"
            @if(! $variantsOpen) hidden @endif
        >
            <td colspan="7">
                <span class="eq-stock-variant__branch" aria-hidden="true"></span>
                Brak wariantów
            </td>
        </tr>
    @endforelse
@endif
