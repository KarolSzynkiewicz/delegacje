@if($this->rowSelectable($task))
    @php $selectId = (int) $task->id; @endphp
    @if($this->isPlanQueue())
        <x-ui.input type="checkbox"
                    :id="'tg-sel-'.$selectId"
                    :value="$selectId"
                    :checked="false"
                    class="form-check-compact form-check-table tg-select tg-dt-hit mb-0"
                    @pointerdown.stop
                    aria-label="Zaznacz" />
    @else
        <x-ui.input type="checkbox"
                    :id="'tg-sel-'.$selectId"
                    :value="$selectId"
                    :checked="$this->isSelected($selectId)"
                    class="form-check-compact form-check-table tg-select tg-dt-hit mb-0"
                    wire:click.stop="toggleSelected({{ $selectId }})"
                    @pointerdown.stop
                    aria-label="Zaznacz" />
    @endif
@endif
