@php
    $side = $side ?? 'stock';
    $kind = $kind ?? 'returnable';
@endphp
<div
    class="border rounded p-2 h-100"
    style="background:rgba(255,255,255,.03);min-height:100%;"
    @if($side === 'cart')
        x-on:dragover="over($event, @js($kind))"
        x-on:drop="drop($event, @js($kind))"
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
                @endphp
                <div wire:key="stock-type-{{ $kind }}-{{ $type->id }}">
                    @if($hasVariants)
                        <div
                            class="border rounded px-2 py-2"
                            style="background:rgba(255,255,255,.04);cursor:pointer;{{ $canMove ? '' : 'opacity:.45;' }}"
                            wire:click="toggleType({{ $type->id }})"
                        >
                            <div class="d-flex align-items-start justify-content-between gap-1">
                                <div>
                                    <div class="fw-semibold" style="font-size:.85rem;">{{ $type->name }}</div>
                                    <div class="small mt-1" style="font-variant-numeric:tabular-nums;">
                                        {{ $remaining }} / {{ $card['stock'] }}
                                    </div>
                                </div>
                                <i class="bi bi-chevron-{{ $expanded ? 'up' : 'down' }} small text-muted"></i>
                            </div>
                        </div>
                        @if($expanded)
                            <div class="border rounded mt-1 p-1" style="background:rgba(0,0,0,.18);">
                                @foreach($card['variants'] as $option)
                                    @php
                                        $variant = $option['variant'];
                                        $variantCanMove = $option['remaining'] > 0;
                                    @endphp
                                    <div
                                        wire:key="stock-variant-{{ $variant->id }}"
                                        class="d-flex align-items-center justify-content-between gap-1 rounded px-2 py-1"
                                        style="{{ $variantCanMove ? 'cursor:grab;' : 'opacity:.45;' }}"
                                        @if($variantCanMove)
                                            draggable="true"
                                            x-on:dragstart.stop="start($event, 'stock', {{ $variant->id }}, @js($kind))"
                                        @endif
                                    >
                                        <div>
                                            <div class="small fw-semibold">{{ $variant->kind_label }}</div>
                                            <div class="small text-muted" style="font-variant-numeric:tabular-nums;">
                                                {{ $option['remaining'] }} / {{ $option['stock'] }}
                                            </div>
                                        </div>
                                        @if($variantCanMove)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                draggable="false"
                                                wire:click.stop="addToCart({{ $variant->id }}, @js($kind))"
                                                title="Dodaj"
                                            >
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        @php
                            $variant = $card['variants'][0]['variant'] ?? null;
                        @endphp
                        @if($variant)
                            <div
                                class="border rounded px-2 py-2"
                                style="background:rgba(255,255,255,.04);{{ $canMove ? 'cursor:grab;' : 'opacity:.45;' }}"
                                @if($canMove)
                                    draggable="true"
                                    x-on:dragstart="start($event, 'stock', {{ $variant->id }}, @js($kind))"
                                @endif
                            >
                                <div class="d-flex align-items-start justify-content-between gap-1">
                                    <div>
                                        <div class="fw-semibold" style="font-size:.85rem;">{{ $type->name }}</div>
                                        <div class="small mt-1" style="font-variant-numeric:tabular-nums;">
                                            {{ $remaining }} / {{ $card['stock'] }}
                                        </div>
                                    </div>
                                    @if($canMove)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            draggable="false"
                                            wire:click.stop="addToCart({{ $variant->id }}, @js($kind))"
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
                    $variant = $card['variant'];
                @endphp
                <div
                    wire:key="cart-{{ $kind }}-{{ $variant->id }}"
                    class="border rounded px-2 py-2"
                    style="background:rgba(255,255,255,.06);cursor:grab;"
                    draggable="true"
                    x-on:dragstart="start($event, 'cart', {{ $variant->id }}, @js($kind))"
                >
                    <div class="fw-semibold" style="font-size:.85rem;">{{ $variant->equipment?->name }}</div>
                    @if($variant->equipment?->hasVariants())
                        <div class="small text-muted">{{ $variant->kind_label }}</div>
                    @endif
                    <div class="d-flex align-items-center gap-1 mt-2">
                        <input
                            type="number"
                            min="1"
                            class="form-control form-control-sm"
                            style="width:4.5rem;"
                            value="{{ $card['quantity'] }}"
                            draggable="false"
                            wire:change="updateLineQuantity({{ $card['index'] }}, $event.target.value)"
                            onclick="event.stopPropagation()"
                        >
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            draggable="false"
                            wire:click.stop="removeLine({{ $card['index'] }})"
                            title="Usuń"
                        >
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            @endif
        @empty
            <div class="px-1 py-3 small text-muted">{{ $empty }}</div>
        @endforelse
    </div>
</div>
