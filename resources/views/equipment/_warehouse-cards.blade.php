@php
    $keep = $keep ?? [];
    $counts = $counts ?? collect();
    $highlightAll = $highlightAll ?? false;
    $currentId = $current->id ?? null;
    $routeName = $routeName ?? 'equipment.tab.stock';
@endphp
<nav class="eq-wh-cards" aria-label="Wybór magazynu">
    @if($highlightAll)
        <span class="eq-wh-card is-active" data-warehouse-id="*" aria-current="page">
            <span class="eq-wh-card__top">
                <span class="eq-wh-card__name">*</span>
                <span class="eq-wh-card__tag">wszystkie</span>
            </span>
            <span class="eq-wh-card__location">Wszystkie magazyny</span>
            <span class="eq-wh-card__count">{{ (int) $counts->sum() }} poz.</span>
        </span>
    @endif
    @foreach($warehouses as $option)
        @php
            $active = $highlightAll || $option->id === $currentId;
            $count = (int) $counts->get($option->id, 0);
            $href = route($routeName, array_filter([
                'warehouse_id' => $option->id,
                ...$keep,
            ], fn ($value) => $value !== null && $value !== ''));
        @endphp
        <a
            href="{{ $href }}"
            class="eq-wh-card{{ $active ? ' is-active' : '' }}"
            data-warehouse-id="{{ $option->id }}"@if($active) aria-current="page"@endif
        >
            <span class="eq-wh-card__top">
                <span class="eq-wh-card__name">{{ $option->name }}</span>
                @if($option->is_default)
                    <span class="eq-wh-card__tag">siedziba</span>
                @endif
            </span>
            @if($option->location?->name && $option->location->name !== $option->name)
                <span class="eq-wh-card__location">{{ $option->location->name }}</span>
            @endif
            <span class="eq-wh-card__count">{{ $count }} poz.</span>
        </a>
    @endforeach
</nav>
