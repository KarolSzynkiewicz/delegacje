@php
    $hiddenKeys = $this->hiddenKeys();
    $visibleCount = $layers->count() - count(array_intersect($hiddenKeys, $layers->keys()->all()));
@endphp

<div class="rc" x-data="{ filtersOpen: window.innerWidth >= 992 }">

    {{-- Pasek widoku: dzień / tydzień / miesiąc --}}
    <div class="rc-viewbar">
        <div class="btn-group rc-viewswitch" role="group" aria-label="Zakres kalendarza">
            @foreach(\App\Livewire\ResourceCalendar::VIEWS as $viewKey => $viewLabel)
                <button
                    type="button"
                    wire:click="setView('{{ $viewKey }}')"
                    class="btn btn-sm rc-viewswitch__btn @if($view === $viewKey) is-active @endif"
                    @if($view === $viewKey) aria-current="true" @endif
                >{{ $viewLabel }}</button>
            @endforeach
        </div>

        <div class="rc-viewbar__right">
            <span class="rc-viewbar__count font-mono">{{ $totalEvents }} zdarzeń</span>
            <button type="button" wire:click="goToToday" class="btn btn-sm btn-outline-secondary rc-today">
                <i class="bi bi-dot"></i> Dziś
            </button>
        </div>
    </div>

    {{-- Nawigacja okresu --}}
    <x-ui.period-nav class="mb-3">
        <x-slot name="prev">
            <button type="button" wire:click="previous" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-left"></i> Poprzedni
            </button>
        </x-slot>

        {{ $periodTitle }}

        <x-slot name="next">
            <button type="button" wire:click="next" class="btn btn-outline-secondary btn-sm">
                Następny <i class="bi bi-chevron-right"></i>
            </button>
        </x-slot>
    </x-ui.period-nav>

    <div class="row g-3">

        {{-- 4/12 — warstwy --}}
        <div class="col-12 col-lg-4">
            <x-ui.card class="rc-filters">
                <div class="rc-filters__head">
                    <div class="rc-filters__title">
                        <i class="bi bi-layers"></i> Warstwy
                        <span class="rc-filters__ratio font-mono">{{ $visibleCount }}/{{ $layers->count() }}</span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary d-lg-none"
                        x-on:click="filtersOpen = !filtersOpen"
                        x-text="filtersOpen ? 'Zwiń' : 'Rozwiń'"
                    >Rozwiń</button>
                </div>

                <div x-show="filtersOpen" x-cloak>
                    <div class="rc-filters__search">
                        <i class="bi bi-search"></i>
                        <input
                            type="search"
                            class="form-control form-control-sm"
                            placeholder="Szukaj w zdarzeniach…"
                            wire:model.live.debounce.400ms="search"
                        >
                    </div>

                    <div class="rc-filters__bulk">
                        <button type="button" wire:click="showAllLayers" class="btn btn-sm btn-link">Zaznacz wszystkie</button>
                        <button type="button" wire:click="hideAllLayers" class="btn btn-sm btn-link">Odznacz wszystkie</button>
                    </div>

                    @forelse($layerGroups as $groupName => $groupLayers)
                        <div class="rc-group">
                            <button type="button" wire:click="toggleGroup('{{ $groupName }}')" class="rc-group__head">
                                {{ $groupName }}
                                <i class="bi bi-toggles2" aria-hidden="true"></i>
                            </button>

                            @foreach($groupLayers as $layer)
                                @php
                                    $layerKey = $layer->key();
                                    $enabled = $this->isLayerEnabled($layerKey);
                                @endphp

                                <button
                                    type="button"
                                    wire:click="toggleLayer('{{ $layerKey }}')"
                                    wire:key="rc-layer-{{ $layerKey }}"
                                    class="rc-layer @if($enabled) is-on @endif"
                                    style="--rc-c: {{ $layer->color() }}; --rc-c-rgb: {{ $layer->rgb() }};"
                                    aria-pressed="{{ $enabled ? 'true' : 'false' }}"
                                >
                                    <span class="rc-layer__check">
                                        @if($enabled)
                                            <i class="bi bi-check-lg"></i>
                                        @endif
                                    </span>

                                    <span class="rc-layer__body">
                                        <span class="rc-layer__label">
                                            <i class="{{ $layer->icon() }}"></i>
                                            {{ $layer->label() }}
                                        </span>
                                        @if($layer->description())
                                            <span class="rc-layer__desc">{{ $layer->description() }}</span>
                                        @endif
                                    </span>

                                    <span class="rc-layer__count font-mono">{{ $counts[$layerKey] ?? 0 }}</span>
                                </button>
                            @endforeach
                        </div>
                    @empty
                        <x-ui.empty-state
                            icon="layers"
                            message="Brak uprawnień do jakiegokolwiek źródła danych kalendarza."
                        />
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        {{-- 8/12 — kalendarz --}}
        <div class="col-12 col-lg-8">
            @if($this->hasFilters())
                <div class="rp-active-filters rc-active-filters">
                    <span class="rp-active-filters__label">Filtry:</span>

                    @if(trim($search) !== '')
                        <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
                    @endif

                    @foreach($hiddenKeys as $hiddenKey)
                        @if($layers->has($hiddenKey))
                            <x-data-table-filter-chip
                                label="Ukryto: {{ $layers[$hiddenKey]->label() }}"
                                wire:click="toggleLayer('{{ $hiddenKey }}')"
                            />
                        @endif
                    @endforeach

                    <button type="button" wire:click="clearFilters" class="rp-active-filters__clear">Wyczyść</button>
                </div>
            @endif

            <x-ui.card class="rc-calendar rc-calendar--{{ $view }}">
                @if($view === 'day')
                    @include('livewire.partials.calendar-day', ['day' => $days[0] ?? null])
                @elseif($view === 'week')
                    @include('livewire.partials.calendar-week')
                @else
                    @include('livewire.partials.calendar-month')
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
