@props([
    'view' => 'cards',
    'gridViewSlug' => null,
])

@php
    $user = auth()->user();
    $isActive = $view === 'cards'
        ? $user?->usesCardsAsDefaultTasksView()
        : $user?->usesGridAsDefaultTasksView($gridViewSlug);
@endphp

<form action="{{ route('tasks.default-view') }}" method="POST" {{ $attributes->merge(['class' => 'd-inline']) }}>
    @csrf
    <input type="hidden" name="view" value="{{ $view }}">
    @if($view === 'grid')
        <input type="hidden" name="grid_view_slug" value="{{ $gridViewSlug }}">
    @endif
    <button type="submit"
            class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
            title="{{ $isActive ? 'Ten widok otwiera się z menu' : 'Ustaw jako domyślny widok w menu' }}">
        <i class="bi bi-house{{ $isActive ? '-fill' : '' }} me-1"></i>Domyślny
    </button>
</form>
