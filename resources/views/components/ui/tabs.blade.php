@props([
    'tabs' => [], // Array of tabs: ['key' => ['label' => '...', 'icon' => '...', 'count' => 0, 'wireClick' => '...', 'href' => '...']]
    'activeTab' => null, // Key of active tab
    'id' => 'tabs', // ID for the tabs container
])

<ul class="nav-tabs-ui mb-4" id="{{ $id }}" role="tablist">
    @foreach($tabs as $tabKey => $tab)
        @php
            $isActive = $activeTab === $tabKey;
            $label = $tab['label'] ?? '';
            $icon = $tab['icon'] ?? null;
            $count = $tab['count'] ?? null;
            $wireClick = $tab['wireClick'] ?? null;
            $href = $tab['href'] ?? null;
        @endphp
        <li class="nav-item-ui" role="presentation">
            @if($wireClick)
                <button 
                    type="button"
                    wire:click="{{ $wireClick }}" 
                    class="nav-link-ui {{ $isActive ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                >
                    @if($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    <span>{{ $label }}</span>
                    @if($count !== null && $count > 0)
                        <span class="badge badge-accent ms-1">{{ $count }}</span>
                    @endif
                </button>
            @elseif($href)
                <a 
                    href="{{ $href }}" 
                    class="nav-link-ui {{ $isActive ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                >
                    @if($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    <span>{{ $label }}</span>
                    @if($count !== null && $count > 0)
                        <span class="badge badge-accent ms-1">{{ $count }}</span>
                    @endif
                </a>
            @else
                <button 
                    type="button"
                    class="nav-link-ui {{ $isActive ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                >
                    @if($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    <span>{{ $label }}</span>
                    @if($count !== null && $count > 0)
                        <span class="badge badge-accent ms-1">{{ $count }}</span>
                    @endif
                </button>
            @endif
        </li>
    @endforeach
</ul>
