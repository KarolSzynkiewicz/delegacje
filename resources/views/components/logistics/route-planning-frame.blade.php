{{-- Wspólna ramka sekcji „planowanie trasy” — slot = treść (np. @livewire step4 lub transfer). --}}
@props([
    'title' => 'Planowanie trasy',
    'icon' => 'bi-signpost-split',
])
<x-ui.card {{ $attributes->merge(['class' => 'mb-4']) }}>
    <x-logistics.section-header :title="$title" :icon="$icon" />
    {{ $slot }}
</x-ui.card>
