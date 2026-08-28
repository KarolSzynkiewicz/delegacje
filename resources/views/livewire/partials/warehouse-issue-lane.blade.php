@php
    $side = $side ?? 'stock';
    $multipleRecipients = $multipleRecipients ?? false;
@endphp
<div
    class="h-100 p-2"
    style="background:var(--bg-input);border:1px solid var(--glass-border);border-radius:12px;min-height:100%;color:var(--text-main);"
    @if($side === 'cart')
        x-on:dragover="over($event, 'cart')"
        x-on:drop="drop($event, 'cart')"
    @endif
>
    <div class="px-1 pb-2" style="font-size:.67rem;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);">
        {{ $title }}
        <span class="ms-1">{{ count($cards) }}</span>
    </div>
    <div class="d-flex flex-column gap-2">
        @forelse($cards as $card)
            @if($side === 'stock')
                @php
                    $type = $card['type'];
                    $hasVariants = $card['has_variants'];
                    $remaining = $card['remaining'];
                    $canMove = $remaining > 0;
                    $expanded = $expandedTypeId === $type->id;
                    $variantLabel = $type->variant_label ?: 'Wariant';
                @endphp
                <div wire:key="stock-type-{{ $type->id }}">
                    @if($hasVariants)
                        <div
                            @class(['warehouse-issue-item px-2 py-2', 'is-muted' => ! $canMove])
                            style="cursor:pointer;"
                            @if($canMove)
                                wire:click="addTypeToCart({{ $type->id }})"
                            @endif
                        >
                            <div class="d-flex align-items-start justify-content-between gap-1">
                                <div>
                                    @include('livewire.partials.warehouse-issue-item-title', ['type' => $type])
                                    <div class="small mt-1" style="font-variant-numeric:tabular-nums;color:var(--text-muted);">
                                        {{ $remaining }} / {{ $card['stock'] }}
                                    </div>
                                </div>
                                @if($canMove)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        draggable="false"
                                        wire:click.stop="addTypeToCart({{ $type->id }})"
                                        title="Dobierz rozmiary"
                                    >
                                        <i class="bi bi-plus"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <span class="badge warehouse-issue-legend-badge">{{ $variantLabel }} · dostępne</span>
                                @foreach($card['variants'] as $option)
                                    <span
                                        class="badge badge-secondary"
                                        style="font-weight:600;{{ $option['remaining'] < 1 ? 'opacity:.4;' : '' }}"
                                    >
                                        {{ $option['variant']->kind_label }} · {{ $option['remaining'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        @php
                            $variant = $card['variants'][0]['variant'] ?? null;
                        @endphp
                        @if($variant)
                            <div
                                @class(['warehouse-issue-item px-2 py-2', 'is-muted' => ! $canMove])
                                style="{{ $canMove ? 'cursor:grab;' : '' }}"
                                @if($canMove)
                                    draggable="true"
                                    x-on:dragstart="start($event, 'stock', {{ $variant->id }})"
                                @endif
                            >
                                <div class="d-flex align-items-start justify-content-between gap-1">
                                    <div>
                                        @include('livewire.partials.warehouse-issue-item-title', ['type' => $type])
                                        <div class="small mt-1" style="font-variant-numeric:tabular-nums;color:var(--text-muted);">
                                            {{ $remaining }} / {{ $card['stock'] }}
                                        </div>
                                    </div>
                                    @if($canMove)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        draggable="false"
                                        wire:click.stop="addTypeToCart({{ $type->id }})"
                                        title="Dodaj"
                                    >
                                        <i class="bi bi-plus"></i>
                                    </button>
                                @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @else
                @php
                    $type = $card['type'];
                    $incomplete = $card['filled'] < $card['total'] && ($card['has_variants'] || $multipleRecipients);
                    $single = ! $multipleRecipients && $card['total'] === 1 ? ($card['assignments'][0] ?? null) : null;
                    $variantLabel = $type->variant_label ?: 'Wariant';
                @endphp
                <div
                    wire:key="cart-{{ $type->id }}"
                    class="warehouse-issue-item px-2 py-2"
                    style="cursor:grab;"
                    draggable="true"
                    x-on:dragstart="start($event, 'cart', {{ $type->id }})"
                    @if($card['has_variants'] || ($multipleRecipients && $card['total'] > 1))
                        wire:click="openSizePanel({{ $type->id }})"
                    @endif
                >
                    <div class="d-flex align-items-start justify-content-between gap-1">
                        <div>
                            @include('livewire.partials.warehouse-issue-item-title', ['type' => $type])
                            @if($incomplete)
                                <div class="mt-1">
                                    <x-ui.badge variant="warning">
                                        @if($card['has_variants'])
                                            {{ mb_strtolower($variantLabel) }} {{ $card['filled'] }}/{{ $card['total'] }}
                                        @else
                                            odbiorcy {{ $card['filled'] }}/{{ $card['total'] }}
                                        @endif
                                    </x-ui.badge>
                                </div>
                            @endif
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            draggable="false"
                            wire:click.stop="removeTypeFromCart({{ $type->id }})"
                            title="Usuń"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        @if($card['has_variants'])
                            <span class="badge warehouse-issue-legend-badge">{{ $variantLabel }} · do wydania</span>
                        @endif
                        @foreach($card['variant_badges'] as $badge)
                            <span
                                class="badge badge-info position-relative"
                                style="font-weight:600;"
                                draggable="false"
                                x-data="{ tip: false }"
                                x-on:mouseenter="tip = true"
                                x-on:mouseleave="tip = false"
                                x-on:click.stop
                            >
                                {{ $badge['label'] }} · {{ $badge['count'] }}
                                @if(count($badge['recipients']) > 0)
                                    <span class="warehouse-issue-tip" x-show="tip" x-cloak>
                                        @foreach($badge['recipients'] as $name)
                                            <div>{{ $name }}</div>
                                        @endforeach
                                    </span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                    @if($single)
                        <div class="mt-2">
                            <input
                                type="number"
                                min="1"
                                class="form-control form-control-sm"
                                style="width:4.5rem;"
                                value="{{ $single['quantity'] }}"
                                draggable="false"
                                wire:change="setAssignmentQuantity({{ $type->id }}, {{ $single['employee_id'] }}, $event.target.value)"
                                onclick="event.stopPropagation()"
                            >
                        </div>
                    @endif
                </div>
            @endif
        @empty
            <div class="px-1 py-3 small" style="color:var(--text-muted);">{{ $empty }}</div>
        @endforelse
    </div>
</div>
