@php
    $kind = $diff['kind'] ?? 'change';
    $from = $diff['from_label'] ?? 'brak';
    $to = $diff['to_label'] ?? 'brak';
    $title = $from.' → '.$to;
    $rowId = (int) $rowId;
    $editing = $this->isEdiEditing($rowId, $field);
    $rawTo = $diff['to'] ?? '';
    $editValue = is_scalar($rawTo) ? (string) $rawTo : '';
@endphp
<span class="tg-edi__cell" wire:key="edi-{{ $rowId }}-{{ $field }}">
    @if($editing)
        @if($field === 'description')
            <textarea
                class="tg-edi__input tg-edi__input--wide"
                rows="3"
                wire:keydown.escape.prevent="cancelEdiRevise"
                wire:blur="commitEdiRevise({{ $rowId }}, '{{ $field }}', $event.target.value)"
                x-init="$el.focus(); $el.select()"
            >{{ $editValue }}</textarea>
        @else
            <input
                type="text"
                class="tg-edi__input"
                value="{{ $editValue }}"
                wire:keydown.enter.prevent="commitEdiRevise({{ $rowId }}, '{{ $field }}', $event.target.value)"
                wire:keydown.escape.prevent="cancelEdiRevise"
                wire:blur="commitEdiRevise({{ $rowId }}, '{{ $field }}', $event.target.value)"
                x-init="$el.focus(); $el.select()"
            >
        @endif
    @else
        <button
            type="button"
            class="tg-edi__pair tg-edi__pair--{{ $kind }}"
            title="Zastosuj: {{ $title }}"
            wire:click="acceptEdiChange({{ $rowId }}, '{{ $field }}')"
        >
            <span class="tg-edi__from">{{ $from }}</span>
            <span class="tg-edi__arrow" aria-hidden="true">→</span>
            <span
                class="tg-edi__to"
                title="Popraw propozycję (bez zapisu do bazy)"
                wire:click.stop="startEdiRevise({{ $rowId }}, '{{ $field }}')"
            >{{ $to }}</span>
        </button>
        <button
            type="button"
            class="tg-edi__skip"
            title="Odrzuć tę zmianę"
            wire:click="rejectEdiChange({{ $rowId }}, '{{ $field }}')"
        >×</button>
    @endif
</span>
