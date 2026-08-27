{{--
  Chrono Assist — „Jak mogę Ci pomóc?” (stepper)

  Header: 4 persony. Klik w kafel / podopcję ZAMIENIA UI (bez dropdownów).
  Alpine (rodzic): path[], picked, go(key), back(), reset(), pick(label).
  path[0] = akcja root (persona); dalsze klucze = kolejne poziomy.
--}}
@props([
    'title' => 'Jak mogę Ci pomóc?',
    'lead' => null,
    'status' => 'Wybierz specjalistę',
    'contextChips' => [],
    'itemCount' => null,
    'contextLabel' => 'Kontekst',
    'actions' => [],
    'alpine' => true,
    'enabledKeys' => null, // null = wszystko dostępne (playground); lista = tylko te liście świecą
])

@php
    $contextChips = array_values(array_filter(array_map(function ($chip) {
        if (is_array($chip)) {
            return trim((string) ($chip['label'] ?? ''));
        }

        return trim((string) $chip);
    }, $contextChips)));

    $filterCount = count($contextChips);
    $itemCount = $itemCount !== null ? (int) $itemCount : null;
    $contextListId = 'ac-assist-filters-'.uniqid();
    $restrict = is_array($enabledKeys);

    $enrich = function (array $action) use (&$enrich, $restrict, $enabledKeys): array {
        $key = $action['key'] ?? '';
        $variant = $action['persona'] ?? \App\Support\ChronoPersona::forAction($key);
        $persona = \App\Support\ChronoPersona::get($variant);
        $children = array_values(array_map(
            fn (array $child) => $enrich(array_merge($child, [
                'persona' => $child['persona'] ?? $variant,
            ])),
            $action['children'] ?? []
        ));

        $enabled = $restrict
            ? ($children === []
                ? in_array($key, $enabledKeys, true)
                : collect($children)->contains(fn (array $child) => $child['enabled']))
            : true;

        return array_merge($action, [
            'persona' => $variant,
            'persona_name' => $action['persona_name'] ?? ($persona['name'] ?? 'Chrono'),
            'persona_role' => $action['persona_role'] ?? ($persona['role'] ?? ''),
            'children' => $children,
            'enabled' => $enabled,
        ]);
    };

    $actions = array_values(array_map($enrich, $actions));

    $personaByRoot = collect($actions)->mapWithKeys(
        fn (array $a) => [($a['key'] ?? '') => $a['persona'] ?? \App\Support\ChronoPersona::CLOCK]
    )->all();

    $labelByKey = [];
    $walkLabels = function (array $nodes) use (&$walkLabels, &$labelByKey): void {
        foreach ($nodes as $node) {
            $labelByKey[$node['key'] ?? ''] = $node['label'] ?? ($node['key'] ?? '');
            if (! empty($node['children'])) {
                $walkLabels($node['children']);
            }
        }
    };
    $walkLabels($actions);

    $defaultActionByPersona = [];
    foreach ($actions as $action) {
        $variant = $action['persona'] ?? \App\Support\ChronoPersona::CLOCK;
        if (! isset($defaultActionByPersona[$variant])) {
            $defaultActionByPersona[$variant] = $action['key'] ?? null;
        }
    }

    $roster = [];
    foreach (\App\Support\ChronoPersona::variants() as $index => $variant) {
        $persona = \App\Support\ChronoPersona::get($variant);
        $root = collect($actions)->firstWhere('persona', $variant);
        $roster[] = [
            'variant' => $variant,
            'name' => $persona['name'] ?? $variant,
            'role' => $persona['role'] ?? '',
            'tagline' => $persona['tagline'] ?? '',
            'action' => $defaultActionByPersona[$variant] ?? null,
            'enabled' => (bool) ($root['enabled'] ?? true),
            'index' => $index,
        ];
    }

    /**
     * Panele steppera: path [] = root; [a] = dzieci a; [a,b] = dzieci b…
     *
     * @return list<array{path: list<string>, title: string, eyebrow: string, items: list<array>}>
     */
    $panels = [];
    $collectPanels = function (array $nodes, array $pathPrefix, ?array $parent) use (&$collectPanels, &$panels): void {
        $panels[] = [
            'path' => $pathPrefix,
            'title' => $parent['label'] ?? 'Wybierz akcję',
            'eyebrow' => $parent
                ? trim(($parent['persona_name'] ?? '').' · krok')
                : 'Zespół Chrono',
            'items' => $nodes,
            'is_root' => $parent === null,
        ];

        foreach ($nodes as $node) {
            if (empty($node['children'])) {
                continue;
            }
            $collectPanels(
                $node['children'],
                array_merge($pathPrefix, [$node['key'] ?? '']),
                $node
            );
        }
    };
    $collectPanels($actions, [], null);

    $pathEqJs = function (array $path): string {
        $json = json_encode(array_values($path), JSON_UNESCAPED_UNICODE);

        return "path.length === ".count($path)." && path.every((k, i) => k === ({$json})[i])";
    };
@endphp

{{-- Sizing krytyczny tu: Vite często nie stoi, a app.css wymaga builda. --}}
<style>
    .ac-assist__title {
        font-size: clamp(0.92rem, 3.6vw, 1.2rem);
        overflow-wrap: anywhere;
    }
    .ac-assist__tile {
        align-items: center !important;
        text-align: center !important;
        min-height: 8.75rem !important;
        padding: 1rem .75rem .9rem !important;
    }
    .ac-assist__tile-face {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100% !important;
        height: auto !important;
        min-height: 3.75rem;
        background: transparent !important;
        overflow: visible !important;
    }
    .ac-assist__tile-face .ac-bot {
        --ac-size: 56px;
        width: var(--ac-size);
    }
    .ac-assist__tile-body {
        align-items: center !important;
        text-align: center !important;
        padding-right: 0 !important;
    }
    .ac-assist--flush .ac-assist__tile {
        min-height: 10.5rem !important;
        padding: 1.15rem .85rem 1rem !important;
    }
    .ac-assist--flush .ac-assist__tile-face .ac-bot {
        --ac-size: 72px;
    }
    @media (min-width: 576px) {
        .ac-assist__header { flex-direction: row; }
        .ac-assist:not(.ac-assist--flush):not(.ac-assist--phone) .ac-assist__roster {
            width: auto;
            max-width: 15rem;
            flex: 0 0 13.5rem;
        }
    }
    @media (max-width: 575.98px) {
        .ac-assist__header { flex-direction: column !important; }
        .ac-assist__roster { width: 100% !important; max-width: none !important; flex: none !important; }
    }
    /* Flush modal: pełna szerokość rosteru pod tytułem */
    .ac-assist--flush .ac-assist__header {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: .85rem;
    }
    .ac-assist--flush .ac-assist__roster {
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        flex: none !important;
        gap: .45rem;
    }
    .ac-assist--flush .ac-assist__avatar {
        padding: .55rem .35rem .45rem;
    }
    .ac-assist--flush .ac-assist__avatar-bot {
        width: 56px;
        height: 60px;
    }
    .ac-assist--flush .ac-assist__avatar-bot .ac-bot {
        --ac-size: 56px;
        width: var(--ac-size);
    }
    .ac-assist--flush .ac-assist__avatar-name,
    .ac-assist--flush .ac-assist__avatar-role {
        overflow: visible;
        text-overflow: clip;
        white-space: nowrap;
    }
    .ac-assist--flush .ac-assist__avatar-name { font-size: .72rem; }
    .ac-assist--flush .ac-assist__avatar-role { font-size: .58rem; }
    @media (min-width: 768px) {
        .ac-assist--flush .ac-assist__grid {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: .75rem;
        }
        .ac-assist--flush .ac-assist__grid > .ac-assist__tile:last-child:nth-child(odd) {
            grid-column: auto !important;
        }
        .ac-assist--flush .ac-assist__tile {
            min-height: 12.5rem !important;
            padding: 1.35rem 1rem 1.15rem !important;
        }
        .ac-assist--flush .ac-assist__tile-face .ac-bot {
            --ac-size: 92px;
        }
        .ac-assist--flush .ac-assist__roster { gap: .65rem; }
        .ac-assist--flush .ac-assist__avatar-bot {
            width: 72px;
            height: 78px;
        }
        .ac-assist--flush .ac-assist__avatar-bot .ac-bot {
            --ac-size: 72px;
        }
        .ac-assist--flush .ac-assist__avatar-name { font-size: .8rem; }
        .ac-assist--flush .ac-assist__avatar-role { font-size: .62rem; }
    }
</style>

<div
    {{ $attributes->merge(['class' => 'ac-assist']) }}
    @if($alpine)
        x-bind:class="path.length ? 'ac-assist--focused' : ''"
    @endif
>
    <div class="ac-assist__header">
        <div class="ac-assist__intro">
            <h2 class="ac-assist__title">{{ $title }}</h2>
            @if($alpine)
                <p
                    class="ac-assist__status"
                    x-text="busy
                        ? ('Myślę… ' + (picked || ''))
                        : (ready
                            ? ('Gotowe · ' + (picked || ''))
                            : (path.length
                                ? path.map(k => (@js($labelByKey))[k] || k).join(' · ')
                                : @js($status)))"
                ></p>
            @else
                <p class="ac-assist__status">{{ $status }}</p>
            @endif
        </div>

        <div class="ac-assist__roster" role="group" aria-label="Zespół Chrono">
            @foreach($roster as $member)
                <button
                    type="button"
                    class="ac-assist__avatar {{ $member['enabled'] ? '' : 'is-wip' }}"
                    data-persona="{{ $member['variant'] }}"
                    @if($member['action'] && $member['enabled'])
                        data-root="{{ $member['action'] }}"
                    @endif
                    @unless($member['enabled'])
                        disabled
                    @endunless
                    title="{{ $member['enabled'] ? $member['name'].' — '.$member['tagline'] : $member['name'].' — w budowie' }}"
                    @if($alpine)
                        x-bind:class="{
                            'is-on': path[0] && (@js($personaByRoot))[path[0]] === @js($member['variant']),
                            'is-dim': path[0] && (@js($personaByRoot))[path[0]] !== @js($member['variant']),
                            'is-busy': busy && path[0] && (@js($personaByRoot))[path[0]] === @js($member['variant']),
                            'is-done': ready && !busy && path[0] && (@js($personaByRoot))[path[0]] === @js($member['variant']),
                        }"
                        x-bind:aria-pressed="path[0] && (@js($personaByRoot))[path[0]] === @js($member['variant']) ? 'true' : 'false'"
                        style="--ac-dim-delay: {{ $member['index'] * 100 }}ms"
                    @endif
                >
                    <span class="ac-assist__avatar-bot">
                        <x-ask-chrono-bot :variant="$member['variant']" :size="48" />
                    </span>
                    <span class="ac-assist__avatar-name">{{ $member['name'] }}</span>
                    <span class="ac-assist__avatar-role">{{ $member['role'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if($lead)
        <p class="ac-assist__lead">{{ $lead }}</p>
    @endif

    @if($filterCount > 0 || $itemCount !== null)
        <div
            class="ac-assist__context"
            x-data="{ open: false }"
            @click.stop
        >
            <span class="ac-assist__context-label">{{ $contextLabel }}</span>

            @if($filterCount > 0)
                <button
                    type="button"
                    class="ac-assist__context-btn"
                    @click="open = !open"
                    x-bind:aria-expanded="open ? 'true' : 'false'"
                    aria-controls="{{ $contextListId }}"
                >
                    <span class="ac-assist__context-btn-main">
                        <i class="bi bi-sliders" aria-hidden="true"></i>
                        <span>Filtry</span>
                        <span class="ac-assist__context-count" title="Aktywne filtry">{{ $filterCount }}</span>
                    </span>
                    <span class="ac-assist__context-btn-end">
                        @if($itemCount !== null)
                            <span class="ac-assist__context-items" title="Elementy w filtrze">
                                {{ $itemCount }}
                            </span>
                        @endif
                        <i
                            class="bi ac-assist__context-chevron"
                            aria-hidden="true"
                            x-bind:class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                        ></i>
                    </span>
                </button>

                <ul
                    id="{{ $contextListId }}"
                    class="ac-assist__context-list"
                    x-show="open"
                    x-cloak
                    x-transition.opacity.duration.150ms
                >
                    @foreach($contextChips as $chip)
                        <li class="ac-assist__context-row">{{ $chip }}</li>
                    @endforeach
                </ul>
            @elseif($itemCount !== null)
                <div class="ac-assist__context-btn ac-assist__context-btn--static" aria-hidden="false">
                    <span class="ac-assist__context-btn-main">
                        <i class="bi bi-collection" aria-hidden="true"></i>
                        <span>Elementy</span>
                    </span>
                    <span class="ac-assist__context-items">{{ $itemCount }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="ac-assist__stages">
        {{-- Symulacja myslenia: Iskra (czysty zegar) w contentcie --}}
        @if($alpine)
            <div
                class="ac-assist__stage ac-assist__stage--busy"
                role="status"
                aria-live="polite"
                x-show="busy"
                x-cloak
                x-transition:enter="ac-assist-fade"
                x-transition:enter-start="ac-assist-fade--in"
                x-transition:enter-end="ac-assist-fade--on"
                x-transition:leave="ac-assist-fade"
                x-transition:leave-start="ac-assist-fade--on"
                x-transition:leave-end="ac-assist-fade--out"
            >
                <div class="ac-assist__busy">
                    <x-ask-chrono-bot variant="spark" :size="64" class="ac-bot--thinking" />
                    <p class="ac-assist__busy-label" x-text="picked ? ('Myślę: ' + picked + '…') : 'Myślę…'"></p>
                    <div class="ac-thinking__bars" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>

            <div
                class="ac-assist__stage ac-assist__stage--ready"
                role="status"
                x-show="ready && !busy"
                x-cloak
                x-transition:enter="ac-assist-fade"
                x-transition:enter-start="ac-assist-fade--in"
                x-transition:enter-end="ac-assist-fade--on"
            >
                <div class="ac-assist__ready">
                    <x-ask-chrono-bot variant="spark" :size="48" class="ac-bot--done" />
                    <p class="ac-assist__ready-label" x-text="picked ? ('Gotowe: ' + picked) : 'Gotowe'"></p>
                    <p class="ac-assist__ready-hint">Tu wejdzie flow HITL (podgląd / zatwierdzenie).</p>
                </div>
            </div>
        @endif

        @foreach($panels as $panel)
            @php
                $isRoot = $panel['is_root'];
                $path = $panel['path'];
            @endphp
            <div
                class="ac-assist__stage {{ $isRoot ? 'ac-assist__stage--root' : 'ac-assist__stage--branch' }}"
                role="group"
                aria-label="{{ $panel['eyebrow'] }}: {{ $panel['title'] }}"
                @if($alpine)
                    x-show="!busy && !ready && ({{ $pathEqJs($path) }})"
                    x-cloak
                    x-transition:enter="ac-assist-fade"
                    x-transition:enter-start="ac-assist-fade--in"
                    x-transition:enter-end="ac-assist-fade--on"
                    x-transition:leave="ac-assist-fade"
                    x-transition:leave-start="ac-assist-fade--on"
                    x-transition:leave-end="ac-assist-fade--out"
                @endif
            >
                @if($isRoot)
                    <div class="ac-assist__grid" role="list">
                        @foreach($panel['items'] as $item)
                            @php
                                $key = $item['key'] ?? '';
                                $variant = $item['persona'] ?? \App\Support\ChronoPersona::CLOCK;
                                $hasChildren = ! empty($item['children']);
                                $enabled = (bool) ($item['enabled'] ?? true);
                            @endphp
                            <button
                                type="button"
                                role="listitem"
                                class="ac-assist__tile {{ $enabled ? '' : 'is-wip' }}"
                                @if($enabled && $hasChildren)
                                    data-go="{{ $key }}"
                                @elseif($enabled)
                                    data-leaf="{{ $key }}"
                                @endif
                                @unless($enabled)
                                    disabled
                                @endunless
                            >
                                <span class="ac-assist__tile-face" aria-hidden="true">
                                    <x-ask-chrono-bot :variant="$variant" />
                                </span>
                                <span class="ac-assist__tile-body">
                                    <span class="ac-assist__tile-who">{{ $item['persona_name'] }}</span>
                                    <span class="ac-assist__tile-label">{{ $item['label'] ?? '' }}</span>
                                    <span class="ac-assist__tile-hint">{{ $enabled ? ($item['hint'] ?? '') : 'W budowie' }}</span>
                                </span>
                                @if($hasChildren && $enabled)
                                    <span class="ac-assist__tile-chevron" aria-hidden="true">
                                        <i class="bi bi-chevron-right"></i>
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @else
                    <ul class="ac-assist__options">
                        @foreach($panel['items'] as $item)
                            @php
                                $key = $item['key'] ?? '';
                                $hasChildren = ! empty($item['children']);
                                $enabled = (bool) ($item['enabled'] ?? true);
                            @endphp
                            <li>
                                <button
                                    type="button"
                                    class="ac-assist__option {{ $hasChildren ? 'ac-assist__option--branch' : '' }} {{ $enabled ? '' : 'is-wip' }}"
                                    @if($enabled && $hasChildren)
                                        data-go="{{ $key }}"
                                    @elseif($enabled)
                                        data-leaf="{{ $key }}"
                                    @endif
                                    @unless($enabled)
                                        disabled
                                    @endunless
                                    @if($alpine && $enabled && ! $hasChildren)
                                        x-bind:class="picked === @js($item['label'] ?? $key) ? 'is-picked' : ''"
                                    @endif
                                >
                                    <span class="ac-assist__option-icon" aria-hidden="true">
                                        <i class="bi {{ $item['icon'] ?? ($hasChildren ? 'bi-folder2-open' : 'bi-arrow-right-short') }}"></i>
                                    </span>
                                    <span class="ac-assist__option-body">
                                        <span class="ac-assist__option-label">{{ $item['label'] ?? '' }}</span>
                                        <span class="ac-assist__option-hint">{{ $enabled ? ($item['hint'] ?? '') : 'W budowie' }}</span>
                                    </span>
                                    @if($enabled)
                                        <i class="bi bi-chevron-right ac-assist__option-go" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    @isset($footer)
        <div class="ac-assist__foot">
            {{ $footer }}
        </div>
    @endisset
</div>
