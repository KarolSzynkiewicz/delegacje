@props(['sprint', 'canMutate' => false])

@php
    $categories = $sprint->categoryLabels();
@endphp

<tr wire:key="sprint-{{ $sprint->id }}">
    <td>
        <a href="{{ route('sprints.show', $sprint) }}" class="fw-semibold text-decoration-none">{{ $sprint->name }}</a>
        @if($sprint->goal)
            <div class="small text-muted">{{ Str::limit($sprint->goal, 80) }}</div>
        @endif
    </td>
    <td data-label="Status">
        <x-sprint.status-badge :sprint="$sprint" />
    </td>
    <td data-label="Termin" class="text-nowrap">
        <div>{{ $sprint->start_date?->format('d.m') }}–{{ $sprint->end_date?->format('d.m.Y') }}</div>
        @if($sprint->isClosed())
            <div class="small text-muted">Zamknięty {{ $sprint->closed_at->format('d.m') }}</div>
        @elseif($sprint->isParked())
            <div class="small text-muted">Odstawiony</div>
        @elseif($sprint->isScheduled())
            <div class="small text-muted">Start za {{ $sprint->start_date->diffInDays(now()->startOfDay()) }} dni</div>
        @elseif($sprint->isCurrentlyActive())
            <div class="small text-muted">{{ $sprint->end_date?->diffInDays(now()->startOfDay()) }} dni do końca</div>
        @endif
    </td>
    <td data-label="Postęp" class="sp-progress-cell">
        <x-sprint.progress :sprint="$sprint" />
    </td>
    <td data-label="Uczestnicy">
        <x-ui.avatar-stack :users="$sprint->participants()" size="28px" :max="4" />
    </td>
    <td data-label="Kategorie">
        <x-sprint.category-chips :categories="$categories" />
    </td>
    <td class="text-end">
        @include('livewire.partials.sprint-row-actions', ['sprint' => $sprint, 'canMutate' => $canMutate])
    </td>
</tr>
