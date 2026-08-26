@props([
    'variant' => 'timer',
    'auto' => false,
])

{{-- Pełnoekranowa inicjalizacja systemu.

     Tryb domyślny: ukryty, pokazuje go submit formularza z data-boot-screen
     (patrz resources/js/app.js) — to tylko natychmiastowy feedback na czas,
     w którym leci POST logowania.

     Tryb auto: renderowany już widoczny na stronie docelowej po zalogowaniu
     i gaszony czystym CSS-em dopiero po przegraniu całej sekwencji, więc
     przeładowanie strony nie ucina animacji w połowie. --}}
<div
    class="cl-boot @if ($auto) cl-boot--on cl-boot--auto @endif"
    id="clBootScreen"
    aria-hidden="{{ $auto ? 'false' : 'true' }}"
    role="status"
>
    <div class="cl-boot__grid" aria-hidden="true"></div>

    <div class="cl-boot__inner">
        <div class="cl-boot__mark">
            <span class="cl-boot__orbit" aria-hidden="true"></span>
            <x-brand-mark :variant="$variant" :size="84" />
        </div>

        <p class="cl-boot__word">Chrono<span>Logic</span></p>
        <p class="cl-boot__tagline font-mono">Inicjalizacja systemu</p>

        <div class="cl-boot__bar" aria-hidden="true"><span></span></div>

        <ul class="cl-boot__steps font-mono">
            <li><i class="cl-boot__dot"></i>Uwierzytelnianie</li>
            <li><i class="cl-boot__dot"></i>Wczytywanie uprawnień</li>
            <li><i class="cl-boot__dot"></i>Szykowanie agentów AI</li>
            <li><i class="cl-boot__dot"></i>Przygotowanie pulpitu</li>
        </ul>
    </div>
</div>
