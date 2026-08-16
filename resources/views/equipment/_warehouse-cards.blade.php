@php
    $keep = $keep ?? [];
    $counts = $counts ?? collect();
    $currentId = $current->id;
    $routeName = $routeName ?? 'equipment.tab.stock';
@endphp
<nav class="eq-wh-cards" aria-label="Wybór magazynu">
    @foreach($warehouses as $option)
        @php
            $active = $option->id === $currentId;
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
