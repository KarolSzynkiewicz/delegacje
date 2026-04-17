{{-- Wiersz przycisków akcji (np. Konfiguruj transfer / Konfiguruj trasę). --}}
<div {{ $attributes->merge(['class' => 'd-flex flex-wrap gap-2 align-items-center transfer-segment-action-row']) }}>
    {{ $slot }}
</div>
