@props([
    'paginator' => null,
    'hasFilters' => false,
])

@php
    $hasMobileCards = isset($cards);
    $hasItems = $paginator && $paginator->count() > 0;
    $hasFiltersBar = isset($filters);
    $hasActiveFiltersSlot = isset($activeFilters);
@endphp

<div class="dt-shell">
    @if($hasFiltersBar)
        <x-ui.card class="dt-filters-card mb-2">
            <div class="card-body dt-filters-card__body">
                {{ $filters }}
            </div>
        </x-ui.card>

        @if($hasFilters && $hasActiveFiltersSlot)
            <x-data-table-active-filters>
                {{ $activeFilters }}
            </x-data-table-active-filters>
        @endif
    @endif

    @if($hasItems)
        @if($hasMobileCards)
            <div class="d-md-none">
                <div class="dt-cards">
                    {{ $cards }}
                </div>

                @if($paginator->hasPages())
                    <div class="mt-3">
                        {{ $paginator->links() }}
                    </div>
                @endif
            </div>
        @endif

        <x-ui.card @class(['dt-table-card mb-0', $hasMobileCards ? 'd-none d-md-block' : null])>
            <div class="table-responsive">
                <table {{ $attributes->merge(['class' => 'table align-middle mb-0']) }}>
                    @isset($head)
                        <thead>{{ $head }}</thead>
                    @endisset
                    <tbody>{{ $body }}</tbody>
                </table>
            </div>

            @if($paginator->hasPages())
                <div class="mt-3 pt-3 border-top">
                    {{ $paginator->links() }}
                </div>
            @endif
        </x-ui.card>
    @else
        <x-ui.card class="dt-table-card mb-0">
            @isset($empty)
                {{ $empty }}
            @else
                <x-ui.empty-state
                    icon="inbox"
                    message="Brak danych do wyświetlenia."
                    :has-filters="$hasFilters"
                    clear-filters-action="wire:clearFilters"
                />
            @endisset
        </x-ui.card>
    @endif
</div>
