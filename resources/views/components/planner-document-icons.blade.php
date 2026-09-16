@props([
    'documents' => [],
    'showEmpty' => false,
    'stacked' => false,
])

@php
    $items = collect($documents)
        ->filter(function ($doc) {
            if (! $doc) {
                return false;
            }
            $type = $doc->document ?? $doc;

            return filled($type->planner_icon ?? null);
        })
        ->unique(fn ($doc) => $doc->document_id ?? $doc->id)
        ->values();
@endphp

@if($items->isNotEmpty())
    <span {{ $attributes->merge(['class' => $stacked ? 'doc-chip-stack' : 'doc-chip-row']) }}>
        @foreach($items as $item)
            @php
                $type = $item->document ?? $item;
                $icon = $type->planner_icon;
                $name = $type->name ?? 'Dokument';
            @endphp
            @if($icon)
                <span class="doc-chip" title="{{ $name }}">
                    <i class="bi {{ $icon }}" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ $name }}</span>
                </span>
            @endif
        @endforeach
    </span>
@elseif($showEmpty)
    <span {{ $attributes->merge(['class' => 'ui-rating ui-rating--empty']) }}>
        <i class="bi bi-file-earmark" aria-hidden="true"></i>
        Brak uprawnień
    </span>
@endif
