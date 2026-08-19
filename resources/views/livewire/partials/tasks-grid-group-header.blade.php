@php
    $isCollapsed = $this->isGroupCollapsed((string) $groupName);
    $groupKey = $this->groupCollapseKey((string) $groupName);
@endphp

<tr wire:key="tg-group-{{ $groupKey }}" class="tg-group-header {{ $isCollapsed ? 'tg-group-collapsed' : '' }}">
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
        <span>{{ $groupName }}</span>
        <span class="badge bg-secondary ms-1" style="font-size:0.68rem; border-radius:8px">{{ $groupItems->count() }}</span>
    </td>
</tr>
