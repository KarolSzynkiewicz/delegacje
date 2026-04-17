{{--
    Po zatwierdzeniu konfiguracji: wiersz z badge (podsumowanie) + opcjonalny przycisk edycji (ołówek).
    Sloty:
    - badge — główny badge (np. auto / dworce)
    - trail (opcjonalny) — drugi wiersz pod badge (np. km + przystanki); wyrównany pod pierwszym badge, ołówek zostaje w prawym górnym rogu
    - edit — przycisk edycji
--}}
<div class="transfer-segment-compact-summary">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
        <div class="d-flex flex-column align-items-stretch gap-1 min-w-0" style="max-width: 100%;">
            <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
                {{ $badge }}
            </div>
            @isset($trail)
                <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
                    {{ $trail }}
                </div>
            @endisset
        </div>
        @isset($edit)
            <div class="flex-shrink-0">{{ $edit }}</div>
        @endisset
    </div>
</div>
