@props([
    'compact' => false,
])

<x-ui.badge variant="warning" {{ $attributes->merge(['title' => 'Kierownik']) }}>
    <i class="bi bi-person-badge{{ $compact ? '' : ' me-1' }}"></i>@unless($compact)
        Kierownik
    @endunless
</x-ui.badge>
