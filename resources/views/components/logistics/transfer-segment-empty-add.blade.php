{{-- Pusty stan: opcjonalny slot hint (opis) + slot domyślny na przycisk „Dodaj”. --}}
<div class="text-center py-2 rounded-2 transfer-segment-empty-add" style="background: rgba(15,23,42,0.45); border: 1px dashed rgba(148,163,184,0.35);">
    @isset($hint)
        <div class="small text-muted mb-2">{{ $hint }}</div>
    @endisset
    {{ $slot }}
</div>
