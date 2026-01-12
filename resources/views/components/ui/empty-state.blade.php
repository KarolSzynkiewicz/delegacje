@props([
    'icon' => 'inbox',
    'message' => 'Brak danych',
    'hasFilters' => false,
    'clearFiltersAction' => null,
    'clearFiltersText' => 'Wyczyść filtry',
    'inTable' => false,
    'colspan' => null,
    'class' => '',
])

@php
    $classes = 'text-center py-5';
    if ($inTable) {
        $classes = 'text-center py-5';
    } else {
        $classes = 'text-center py-4';
    }
    $classes .= ' ' . $class;
@endphp

@if($inTable)
    <tr>
        <td {{ $colspan ? 'colspan="' . $colspan . '"' : '' }} class="{{ $classes }}">
            <div class="empty-state">
                <i class="bi bi-{{ $icon }} text-muted fs-1 d-block mb-2"></i>
                <p class="text-muted small fw-medium mb-2">{{ $message }}</p>
                @if($hasFilters && $clearFiltersAction)
                    @if(str_starts_with($clearFiltersAction, 'wire:'))
                        <x-ui.button variant="ghost" wire:click="{{ str_replace('wire:', '', $clearFiltersAction) }}" class="btn-sm">
                            {{ $clearFiltersText }}
                        </x-ui.button>
                    @else
                        <x-ui.button variant="ghost" href="{{ $clearFiltersAction }}" class="btn-sm">
                            {{ $clearFiltersText }}
                        </x-ui.button>
                    @endif
                @endif
                {{ $slot }}
            </div>
        </td>
    </tr>
@else
    <div class="{{ $classes }}">
        <div class="empty-state">
            <i class="bi bi-{{ $icon }} text-muted fs-1 d-block mb-2"></i>
            <p class="text-muted {{ $inTable ? 'small fw-medium mb-2' : 'mb-0' }}">{{ $message }}</p>
            @if($hasFilters && $clearFiltersAction)
                @if(str_starts_with($clearFiltersAction, 'wire:'))
                    <x-ui.button variant="ghost" wire:click="{{ str_replace('wire:', '', $clearFiltersAction) }}" class="btn-sm">
                        {{ $clearFiltersText }}
                    </x-ui.button>
                @else
                    <x-ui.button variant="ghost" href="{{ $clearFiltersAction }}" class="btn-sm">
                        {{ $clearFiltersText }}
                    </x-ui.button>
                @endif
            @endif
            {{ $slot }}
        </div>
    </div>
@endif
