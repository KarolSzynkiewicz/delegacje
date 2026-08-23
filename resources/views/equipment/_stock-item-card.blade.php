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
<article class="eq-stock-card{{ $hasVariants ? ' has-variants' : '' }}{{ $variantsOpen ? ' is-open' : '' }}{{ $withdrawn ? ' is-withdrawn' : '' }}">
    <div class="eq-stock-card__top">
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
            <a href="{{ $showUrl }}" class="eq-stock-card__name stretched-link">{{ $item->name }}</a>
            <div class="eq-stock-card__meta">
                @if($withdrawn)
                    <span class="eq-stock-card__flag">Wycofane</span>
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
                    <span class="text-muted">{{ $item->variant_label }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="eq-stock-card__tiles">
        @include('equipment._qty-tile', ['value' => $item->quantityIn($warehouse), 'tone' => 'stock', 'label' => 'W magazynie'])
        @include('equipment._qty-tile', ['value' => $item->reservedIn($warehouse), 'tone' => 'reserved', 'label' => 'Zarezerwowane'])
        @include('equipment._qty-tile', ['value' => $item->quantityInOthers($warehouse), 'label' => 'W innych'])
        @include('equipment._qty-tile', ['value' => $item->issuedOutstandingIn($warehouse), 'tone' => 'return', 'label' => 'Do zwrotu'])
        @include('equipment._qty-tile', ['value' => $item->issuedOutstandingInOthers($warehouse), 'label' => 'Zwrot indziej'])
    </div>

    @if($hasVariants)
        <div
            class="eq-stock-card__variants"
            data-eq-stock-parent="{{ $item->id }}"
            @if(! $variantsOpen) hidden @endif
        >
            @forelse ($item->variants as $variant)
                <div class="eq-stock-card__variant">
                    <div class="eq-stock-card__variant-name">{{ $variant->kind_label }}</div>
                    <div class="eq-stock-card__tiles eq-stock-card__tiles--compact">
                        @include('equipment._qty-tile', ['value' => $variant->quantityIn($warehouse), 'tone' => 'stock', 'label' => 'W mag.'])
                        @include('equipment._qty-tile', ['value' => $variant->reservedIn($warehouse), 'tone' => 'reserved', 'label' => 'Rez.'])
                        @include('equipment._qty-tile', ['value' => $variant->quantityInOthers($warehouse), 'label' => 'Inne'])
                        @include('equipment._qty-tile', ['value' => $variant->issuedOutstandingIn($warehouse), 'tone' => 'return', 'label' => 'Zwrot'])
                        @include('equipment._qty-tile', ['value' => $variant->issuedOutstandingInOthers($warehouse), 'label' => 'Indziej'])
                    </div>
                </div>
            @empty
                <div class="text-muted small">Brak wariantów</div>
            @endforelse
        </div>
    @endif
</article>
