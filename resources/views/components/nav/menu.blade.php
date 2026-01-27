@php
    $menuService = app(\App\Services\MenuService::class);
    $menuItems = $menuService->getFilteredMenu();
@endphp

@foreach($menuItems as $item)
    @if($item['type'] === 'link')
        <x-nav.link 
            :route="$item['route'] ?? null"
            :routePattern="$item['routePattern'] ?? null"
            :icon="$item['icon'] ?? null"
            :permission="$item['permission'] ?? null"
        >
            {{ $item['label'] }}
        </x-nav.link>
    @elseif($item['type'] === 'dropdown')
        <x-nav.dropdown 
            :label="$item['label']"
            :icon="$item['icon'] ?? null"
            :routePatterns="$item['routePatterns'] ?? []"
        >
            @foreach($item['items'] ?? [] as $childItem)
                <x-nav.item 
                    :route="$childItem['route'] ?? null"
                    :routePattern="$childItem['routePattern'] ?? null"
                    :icon="$childItem['icon'] ?? null"
                    :permission="$childItem['permission'] ?? null"
                >
                    {{ $childItem['label'] }}
                </x-nav.item>
            @endforeach
        </x-nav.dropdown>
    @endif
@endforeach
