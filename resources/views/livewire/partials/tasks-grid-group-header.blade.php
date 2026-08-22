@php
    $isCollapsed = $this->isGroupCollapsed((string) $groupValue);
    $groupKey = $this->groupCollapseKey((string) $groupValue);
@endphp

<tr wire:key="tg-group-{{ $groupKey }}"
    class="tg-group-header {{ $isCollapsed ? 'tg-group-collapsed' : '' }}"
    x-data="{ taskOver: false, gv: String(@js((string) $groupValue)) }"
    :class="{ 'tg-group-drop': taskOver }"
    @dragover="if (window._tgTaskDrag && String(window._tgTaskDrag.fromGroup) !== gv) { $event.preventDefault(); taskOver = true; $event.dataTransfer.dropEffect = 'move' }"
    @dragleave="if (!$el.contains($event.relatedTarget)) taskOver = false"
    @drop.prevent="if (window._tgTaskDrag && String(window._tgTaskDrag.fromGroup) !== gv) { $wire.moveTaskToGroup(window._tgTaskDrag.id, gv); window._tgTaskDrag = null; taskOver = false }">
    {{-- Ten sam chevron co przy wierszu zadania --}}
    <td style="width:36px; padding:5px 4px !important; text-align:center">
        <button type="button"
                wire:click="toggleGroupCollapse('{{ $groupKey }}')"
                class="btn btn-sm btn-link p-0"
                style="color:rgba(255,255,255,0.4); line-height:1"
                title="{{ $isCollapsed ? 'Pokaż grupę' : 'Zwiń grupę' }}">
            <i class="bi bi-chevron-{{ $isCollapsed ? 'right' : 'down' }}" style="font-size:0.75rem"></i>
        </button>
    </td>
    <td colspan="{{ $colCount - 1 }}" style="padding:6px 10px">
        <span>
            @if($groupBy === 'sprint' && (string) $groupValue !== '')
                <a href="{{ route('sprints.show', $groupValue) }}" class="text-decoration-none" style="color:inherit">{{ $groupName }}</a>
            @else
                {{ $groupName }}
            @endif
        </span>
        <span class="badge bg-secondary ms-1" style="font-size:0.68rem; border-radius:8px">{{ $groupItems->count() }}</span>
        @if(!empty($groupSubtitle))
            <div class="fw-normal mt-1" style="font-size:0.72rem; opacity:.7; font-weight:400">{{ $groupSubtitle }}</div>
        @endif
    </td>
</tr>
