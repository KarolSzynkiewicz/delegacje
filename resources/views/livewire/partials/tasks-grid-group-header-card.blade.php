@php
    $isCollapsed = $this->isGroupCollapsed((string) $groupValue);
    $groupKey = $this->groupCollapseKey((string) $groupValue);
@endphp

<div class="tg-group-card-header" wire:key="tg-group-card-{{ $groupKey }}">
    <button type="button"
            wire:click="toggleGroupCollapse('{{ $groupKey }}')"
            class="tg-card-expand-btn"
            title="{{ $isCollapsed ? 'Pokaż grupę' : 'Zwiń grupę' }}">
        <i class="bi bi-chevron-{{ $isCollapsed ? 'right' : 'down' }}" style="font-size:0.75rem"></i>
    </button>
    <span class="flex-grow-1">
        @if($groupBy === 'sprint' && (string) $groupValue !== '')
            <a href="{{ route('sprints.show', $groupValue) }}" class="text-decoration-none" style="color:inherit">{{ $groupName }}</a>
        @else
            {{ $groupName }}
        @endif
        <span class="badge bg-secondary ms-1" style="font-size:0.65rem; border-radius:8px">{{ $groupItems->count() }}</span>
    </span>
</div>
