@php
    $canToggle = $item->canCompleteInline();
@endphp
<tr wire:key="wi-{{ $item->id }}">
    <td>
        @if($canToggle)
            <button
                type="button"
                class="btn btn-sm btn-link p-0"
                wire:click="toggleComplete({{ $item->id }})"
                title="{{ $item->status->isOpen() ? 'Oznacz jako zrobione' : 'Wznów' }}"
            >
                <i class="bi {{ $item->status->isOpen() ? 'bi-circle' : 'bi-check-circle-fill text-success' }}"></i>
            </button>
        @else
            <i class="bi {{ $item->type->icon() }} text-muted" title="Otwórz źródło, żeby zmienić status"></i>
        @endif
    </td>
    <td class="small text-nowrap">
        <i class="bi {{ $item->type->icon() }} me-1"></i>{{ $item->type->label() }}
    </td>
    <td>
        <a href="{{ $item->openUrl() }}" class="text-decoration-none">{{ $item->title }}</a>
    </td>
    <td class="small">{{ $item->assignee?->name ?? '—' }}</td>
    <td class="small">{{ $item->status->label() }}</td>
    <td class="small text-nowrap">{{ $item->due_at?->format('d.m.Y') ?? '—' }}</td>
    <td>
        <a href="{{ $item->openUrl() }}" class="btn btn-sm btn-link">Otwórz</a>
    </td>
</tr>
