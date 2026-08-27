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
])

@php
    use App\Support\ChronoPersona;

    $contextChips = array_values(array_filter(array_map(function ($chip) {
        if (is_array($chip)) {
            return trim((string) ($chip['label'] ?? ''));
        }

        return trim((string) $chip);
    }, $contextChips)));

    $filterCount = count($contextChips);
    $itemCount = $itemCount !== null ? (int) $itemCount : null;
    $contextListId = 'ac-assist-filters-'.uniqid();

    $enrich = function (array $action) use (&$enrich): array {
        $key = $action['key'] ?? '';
        $variant = $action['persona'] ?? ChronoPersona::forAction($key);
        $persona = ChronoPersona::get($variant);
        $children = array_values(array_map(
            fn (array $child) => $enrich(array_merge($child, [
                'persona' => $child['persona'] ?? $variant,
            ])),
            $action['children'] ?? []
        ));

        return array_merge($action, [
            'persona' => $variant,
            'persona_name' => $action['persona_name'] ?? ($persona['name'] ?? 'Chrono'),
            'persona_role' => $action['persona_role'] ?? ($persona['role'] ?? ''),
            'children' => $children,
        ]);
    };

    $actions = array_values(array_map($enrich, $actions));

    $personaByRoot = collect($actions)->mapWithKeys(
        fn (array $a) => [($a['key'] ?? '') => $a['persona'] ?? ChronoPersona::CLOCK]
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
        $variant = $action['persona'] ?? ChronoPersona::CLOCK;
        if (! isset($defaultActionByPersona[$variant])) {
            $defaultActionByPersona[$variant] = $action['key'] ?? null;
        }
    }

    $roster = [];
    foreach (ChronoPersona::variants() as $index => $variant) {
        $persona = ChronoPersona::get($variant);
        $roster[] = [
            'variant' => $variant,
            'name' => $persona['name'] ?? $variant,
            'role' => $persona['role'] ?? '',
            'tagline' => $persona['tagline'] ?? '',
            'action' => $defaultActionByPersona[$variant] ?? null,
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
                    x-text="path.length
                        ? path.map(k => (@js($labelByKey))[k] || k).join(' · ')
                        : @js($status)"
                ></p>
            @else
                <p class="ac-assist__status">{{ $status }}</p>
            @endif
        </div>

        <div class="ac-assist__roster" role="group" aria-label="Zespół Chrono">
            @foreach($roster as $member)
                <button
                    type="button"
                    class="ac-assist__avatar"
                    data-persona="{{ $member['variant'] }}"
                    @if($member['action'])
                        data-root="{{ $member['action'] }}"
                    @endif
                    title="{{ $member['name'] }} — {{ $member['tagline'] }}"
                    @if($alpine)
                        x-bind:class="{
                            'is-on': path[0] && (@js($personaByRoot))[path[0]] === @js($member['variant']),
                            'is-dim': path[0] && (@js($personaByRoot))[path[0]] !== @js($member['variant']),
                        }"
                        x-bind:aria-pressed="path[0] && (@js($personaByRoot))[path[0]] === @js($member['variant']) ? 'true' : 'false'"
                        style="--ac-dim-delay: {{ $member['index'] * 100 }}ms"
                    @endif
                >
                    <span class="ac-assist__avatar-bot">
                        @if($alpine)
                            <x-ask-chrono-bot
                                :variant="$member['variant']"
                                :size="40"
                                ::class="
                                    (path[0] && (@js($personaByRoot))[path[0]] === @js($member['variant']))
                                        ? (picked ? 'ac-bot--done' : 'ac-bot--thinking')
                                        : ''
                                "
                            />
                        @else
                            <x-ask-chrono-bot :variant="$member['variant']" :size="40" />
                        @endif
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
                    x-show="{{ $pathEqJs($path) }}"
                    x-cloak
                    x-transition:enter="ac-assist-fade"
                    x-transition:enter-start="ac-assist-fade--in"
                    x-transition:enter-end="ac-assist-fade--on"
                    x-transition:leave="ac-assist-fade"
                    x-transition:leave-start="ac-assist-fade--on"
                    x-transition:leave-end="ac-assist-fade--out"
                @endif
            >
                @unless($isRoot)
                    <div class="ac-assist__crumb">
                        <button type="button" class="ac-assist__back" data-back title="Wstecz">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            <span>Wstecz</span>
                        </button>
                        <div class="ac-assist__crumb-text">
                            <span class="ac-assist__crumb-eyebrow">{{ $panel['eyebrow'] }}</span>
                            <span class="ac-assist__crumb-title">{{ $panel['title'] }}</span>
                        </div>
                    </div>
                @endunless

                @if($isRoot)
                    <div class="ac-assist__grid" role="list">
                        @foreach($panel['items'] as $item)
                            @php
                                $key = $item['key'] ?? '';
                                $variant = $item['persona'] ?? ChronoPersona::CLOCK;
                                $hasChildren = ! empty($item['children']);
                            @endphp
                            <button
                                type="button"
                                role="listitem"
                                class="ac-assist__tile"
                                @if($hasChildren)
                                    data-go="{{ $key }}"
                                @else
                                    data-leaf="{{ $key }}"
                                @endif
                            >
                                <span class="ac-assist__tile-face" aria-hidden="true">
                                    <x-ask-chrono-bot :variant="$variant" :size="32" />
                                </span>
                                <span class="ac-assist__tile-body">
                                    <span class="ac-assist__tile-who">{{ $item['persona_name'] }}</span>
                                    <span class="ac-assist__tile-label">{{ $item['label'] ?? '' }}</span>
                                    @if(! empty($item['hint']))
                                        <span class="ac-assist__tile-hint">{{ $item['hint'] }}</span>
                                    @endif
                                </span>
                                @if($hasChildren)
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
                            @endphp
                            <li>
                                <button
                                    type="button"
                                    class="ac-assist__option {{ $hasChildren ? 'ac-assist__option--branch' : '' }}"
                                    @if($hasChildren)
                                        data-go="{{ $key }}"
                                    @else
                                        data-leaf="{{ $key }}"
                                    @endif
                                    @if($alpine && ! $hasChildren)
                                        x-bind:class="picked === @js($item['label'] ?? $key) ? 'is-picked' : ''"
                                    @endif
                                >
                                    <span class="ac-assist__option-icon" aria-hidden="true">
                                        <i class="bi {{ $item['icon'] ?? ($hasChildren ? 'bi-folder2-open' : 'bi-arrow-right-short') }}"></i>
                                    </span>
                                    <span class="ac-assist__option-body">
                                        <span class="ac-assist__option-label">{{ $item['label'] ?? '' }}</span>
                                        @if(! empty($item['hint']))
                                            <span class="ac-assist__option-hint">{{ $item['hint'] }}</span>
                                        @endif
                                    </span>
                                    <i class="bi bi-chevron-right ac-assist__option-go" aria-hidden="true"></i>
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
