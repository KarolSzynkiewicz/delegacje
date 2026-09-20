@props([
    'categories',
    'wrapClass' => 'd-flex flex-wrap gap-1',
])

<div {{ $attributes->class($wrapClass) }} style="position:relative; z-index:2;">
    @forelse($categories as $category)
        <x-ui.clickable-badge
            variant="info"
            :href="\App\Support\TasksGridUrlParams::gridUrl(['searchCategory' => $category])"
            title="Backlog: {{ $category }}"
        >{{ $category }}</x-ui.clickable-badge>
    @empty
        <span class="text-muted small">—</span>
    @endforelse
</div>
