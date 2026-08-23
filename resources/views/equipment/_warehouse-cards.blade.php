@php
    $keep = $keep ?? [];
    $counts = $counts ?? collect();
    $highlightAll = $highlightAll ?? false;
    $currentId = $current->id ?? null;
    $routeName = $routeName ?? 'equipment.tab.stock';
    $currentCount = $highlightAll
        ? (int) $counts->sum()
        : (int) $counts->get($currentId, 0);
    $triggerName = $highlightAll
        ? 'Wszystkie magazyny'
        : ($current->name ?? 'Magazyn');
    $triggerIcon = $highlightAll
        ? 'bi-grid-3x3-gap'
        : (($current->is_default ?? false) ? 'bi-building' : 'bi-box-seam');
@endphp
<nav class="eq-wh" aria-label="Wybór magazynu">
    <div class="d-md-none mb-4 ui-compact-nav">
        <label class="form-label small text-muted mb-1" for="eq-wh-mobile-trigger">Magazyn</label>
        <div class="dropdown w-100">
            <button
                class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between gap-2 text-start"
                type="button"
                id="eq-wh-mobile-trigger"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-haspopup="true"
                aria-label="Wybierz magazyn"
            >
                <span class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                    <span class="ui-compact-nav__icon" aria-hidden="true">
                        <i class="bi {{ $triggerIcon }}"></i>
                    </span>
                    <span class="text-truncate fw-semibold">{{ $triggerName }}</span>
                    <span class="badge badge-accent flex-shrink-0 font-mono">{{ $currentCount }}</span>
                </span>
                <i class="bi bi-chevron-down flex-shrink-0" aria-hidden="true"></i>
            </button>
            <ul
                class="dropdown-menu dropdown-menu-dark border shadow-lg mt-1 p-2 w-100"
                style="max-height: min(70vh, 28rem); overflow-y: auto;"
                role="listbox"
                aria-labelledby="eq-wh-mobile-trigger"
            >
                @foreach($warehouses as $option)
                    @php
                        $active = $highlightAll || $option->id === $currentId;
                        $count = (int) $counts->get($option->id, 0);
                        $href = route($routeName, array_filter([
                            'warehouse_id' => $option->id,
                            ...$keep,
                        ], fn ($value) => $value !== null && $value !== ''));
                    @endphp
                    <li role="none">
                        <a
                            href="{{ $href }}"
                            class="dropdown-item d-flex align-items-center gap-2 rounded py-2{{ $active ? ' active' : '' }}"
                            role="option"
                            aria-current="{{ ($active && ! $highlightAll) ? 'true' : 'false' }}"
                            data-warehouse-id="{{ $option->id }}"
                        >
                            <i class="bi {{ $option->is_default ? 'bi-building' : 'bi-box-seam' }} flex-shrink-0" aria-hidden="true"></i>
                            <span class="text-truncate flex-grow-1">{{ $option->name }}</span>
                            @if($option->is_default)
                                <span class="eq-wh-card__tag">siedziba</span>
                            @endif
                            <span class="font-mono text-muted flex-shrink-0">{{ $count }}</span>
                            @if($active && ! $highlightAll)
                                <i class="bi bi-check-lg flex-shrink-0 text-success" aria-hidden="true"></i>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="eq-wh-cards d-none d-md-grid">
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
                data-warehouse-id="{{ $option->id }}"@if($active && ! $highlightAll) aria-current="page"@endif
            >
                <span class="eq-wh-card__icon" aria-hidden="true">
                    <i class="bi {{ $option->is_default ? 'bi-building' : 'bi-box-seam' }}"></i>
                </span>
                <span class="eq-wh-card__body">
                    <span class="eq-wh-card__top">
                        <span class="eq-wh-card__name">{{ $option->name }}</span>
                        @if($option->is_default)
                            <span class="eq-wh-card__tag">siedziba</span>
                        @endif
                    </span>
                    @if($option->location?->name && $option->location->name !== $option->name)
                        <span class="eq-wh-card__location">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            {{ $option->location->name }}
                        </span>
                    @endif
                    <span class="eq-wh-card__count font-mono">{{ $count }} poz.</span>
                </span>
            </a>
        @endforeach
    </div>
</nav>
