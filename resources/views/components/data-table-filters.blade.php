@props([
    'count' => 0,
])

{{--
    Kompaktowy wiersz filtrów — wszystkie pola obok siebie (flex), licznik
    rekordów po prawej. Renderowany w pierwszej karcie wewnątrz <x-data-table>.
    Aktywne filtry (chipy + Wyczyść) idą w osobnym slocie activeFilters.
--}}
<div class="dt-toolbar">
    <div class="dt-toolbar__fields">
        {{ $slot }}
    </div>

    <div class="dt-toolbar__meta">
        @isset($actions)
            {{ $actions }}
        @endisset
        <span class="dt-toolbar__count">
            Rekordów: <strong>{{ $count }}</strong>
            @isset($note)
                <span class="dt-toolbar__note">{{ $note }}</span>
            @endisset
        </span>
    </div>
</div>
