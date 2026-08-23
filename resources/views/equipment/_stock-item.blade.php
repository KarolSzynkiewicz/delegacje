@php
    $hasVariants = $item->hasVariants();
    $withdrawn = $item->isArchived();
    $variantCount = $hasVariants ? $item->variants->count() : 0;
    $search = mb_strtolower(trim((string) request('search')));
    $variantsOpen = $hasVariants && $search !== '' && $item->variants->contains(
        fn ($variant) => str_contains(mb_strtolower((string) $variant->value), $search)
    );
    $showUrl = route('equipment.show', ['equipment' => $item, 'warehouse_id' => $warehouse->id]);
@endphp
<tr
    class="eq-stock-item{{ $hasVariants ? ' has-variants' : '' }}{{ $variantsOpen ? ' is-open' : '' }}{{ $withdrawn ? ' is-withdrawn' : '' }}"
    data-eq-stock-href="{{ $showUrl }}"
    @if($hasVariants) data-eq-stock-item="{{ $item->id }}" @endif
>
    <td>
        <div class="eq-stock-item__main">
            <span class="eq-stock-item__photo-slot">
                @if($item->image_url)
                    <img src="{{ $item->image_url }}" alt="" class="eq-stock-item__photo">
                @else
                    <span class="eq-stock-item__photo is-placeholder" aria-hidden="true">
                        <i class="bi bi-box-seam"></i>
                    </span>
                @endif
            </span>
            <div class="eq-stock-item__text">
                <div class="eq-stock-item__title">
                    <a href="{{ $showUrl }}" class="eq-stock-item__name">{{ $item->name }}</a>
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
</tr>

@if($hasVariants)
    @forelse ($item->variants as $variant)
        <tr
            class="eq-stock-variant{{ $loop->last ? ' is-last' : '' }}"
            data-eq-stock-href="{{ $showUrl }}"
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
        </tr>
    @empty
        <tr
            class="eq-stock-variant is-last"
            data-eq-stock-parent="{{ $item->id }}"
            @if(! $variantsOpen) hidden @endif
        >
            <td colspan="6">
                <span class="eq-stock-variant__branch" aria-hidden="true"></span>
                Brak wariantów
            </td>
        </tr>
    @endforelse
@endif
