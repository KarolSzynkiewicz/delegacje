@props([
    'tabs' => [], // Array of tabs: ['key' => ['label' => '...', 'icon' => '...', 'count' => 0, 'wireClick' => '...', 'href' => '...', 'warning' => bool]]
    'activeTab' => null, // Key of active tab
    'id' => 'tabs', // ID for the tabs container
    'compactMobile' => false, // Na < md: jeden wiersz + dropdown zamiast wielu rzędów tabów
])

@php
    $firstKey = array_key_first($tabs);
    $activeConfig = ($activeTab !== null && isset($tabs[$activeTab]))
        ? $tabs[$activeTab]
        : ($firstKey !== null ? $tabs[$firstKey] : []);
@endphp

@if($compactMobile && count($tabs) > 0)
    <div class="d-md-none mb-4 employee-tabs-mobile-nav" data-employee-tabs-mobile>
        <label class="form-label small text-muted mb-1" for="{{ $id }}-mobile-trigger">Sekcja</label>
        <div class="dropdown w-100">
            <button
                class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between gap-2 text-start"
                type="button"
                id="{{ $id }}-mobile-trigger"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-haspopup="true"
                aria-label="Wybierz sekcję profilu"
                style="min-height: 3rem;"
            >
                <span class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                    @if(! empty($activeConfig['icon']))
                        <i class="{{ $activeConfig['icon'] }} flex-shrink-0" aria-hidden="true"></i>
                    @endif
                    @if(! empty($activeConfig['warning']))
                        <i class="bi bi-exclamation-lg ui-tab-warn-icon flex-shrink-0" title="Brakuje danych w tej sekcji" aria-hidden="true"></i>
                    @endif
                    <span class="text-truncate fw-semibold">{{ $activeConfig['label'] ?? '' }}</span>
                    @php
                        $activeCount = $activeConfig['count'] ?? null;
                    @endphp
                    @if($activeCount !== null && $activeCount > 0)
                        <span class="badge badge-accent flex-shrink-0">{{ $activeCount }}</span>
                    @endif
                </span>
                <i class="bi bi-chevron-down flex-shrink-0" aria-hidden="true"></i>
            </button>
            <ul
                class="dropdown-menu dropdown-menu-dark border shadow-lg mt-1 p-2 w-100 employee-tabs-mobile-menu"
                style="max-height: min(70vh, 28rem); overflow-y: auto;"
                role="listbox"
                aria-labelledby="{{ $id }}-mobile-trigger"
            >
                @foreach($tabs as $tabKey => $tab)
                    @php
                        $isActive = $activeTab === $tabKey;
                        $label = $tab['label'] ?? '';
                        $icon = $tab['icon'] ?? null;
                        $count = $tab['count'] ?? null;
                        $wireClick = $tab['wireClick'] ?? null;
                        $href = $tab['href'] ?? null;
                        $warning = ! empty($tab['warning']);
                    @endphp
                    <li role="none">
                        @if($wireClick)
                            <button
                                type="button"
                                wire:click="{{ $wireClick }}"
                                wire:key="{{ $id }}-m-{{ $tabKey }}"
                                @class(['dropdown-item d-flex align-items-center gap-2 rounded py-2', 'active' => $isActive, 'dropdown-item--tab-warning' => $warning])
                                role="option"
                                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                            >
                                @if($icon)
                                    <i class="{{ $icon }} flex-shrink-0" aria-hidden="true"></i>
                                @endif
                                <span class="text-truncate flex-grow-1 text-start">{{ $label }}</span>
                                @if($warning)
                                    <i class="bi bi-exclamation-lg ui-tab-warn-icon flex-shrink-0" title="Brakuje danych w tej sekcji" aria-hidden="true"></i>
                                @endif
                                @if($count !== null && $count > 0)
                                    <span class="badge badge-accent flex-shrink-0">{{ $count }}</span>
                                @endif
                                @if($isActive)
                                    <i class="bi bi-check-lg flex-shrink-0 text-success" aria-hidden="true"></i>
                                @endif
                            </button>
                        @elseif($href)
                            <a
                                href="{{ $href }}"
                                @class(['dropdown-item d-flex align-items-center gap-2 rounded py-2', 'active' => $isActive, 'dropdown-item--tab-warning' => $warning])
                                role="option"
                                aria-current="{{ $isActive ? 'true' : 'false' }}"
                            >
                                @if($icon)
                                    <i class="{{ $icon }} flex-shrink-0" aria-hidden="true"></i>
                                @endif
                                <span class="text-truncate flex-grow-1">{{ $label }}</span>
                                @if($warning)
                                    <i class="bi bi-exclamation-lg ui-tab-warn-icon flex-shrink-0" title="Brakuje danych w tej sekcji" aria-hidden="true"></i>
                                @endif
                                @if($count !== null && $count > 0)
                                    <span class="badge badge-accent flex-shrink-0">{{ $count }}</span>
                                @endif
                            </a>
                        @else
                            <span class="dropdown-item disabled d-flex align-items-center gap-2 py-2">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<ul @class(['nav-tabs-ui mb-4', 'd-none d-md-flex' => $compactMobile]) id="{{ $id }}" role="tablist">
    @foreach($tabs as $tabKey => $tab)
        @php
            $isActive = $activeTab === $tabKey;
            $label = $tab['label'] ?? '';
            $icon = $tab['icon'] ?? null;
            $count = $tab['count'] ?? null;
            $wireClick = $tab['wireClick'] ?? null;
            $href = $tab['href'] ?? null;
            $warning = ! empty($tab['warning']);
            $navLinkClass = 'nav-link-ui '.($isActive ? 'active' : '').($warning ? ' nav-link-ui--warning' : '');
        @endphp
        <li class="nav-item-ui" role="presentation">
            @if($wireClick)
                <button
                    type="button"
                    wire:click="{{ $wireClick }}"
                    class="{{ trim($navLinkClass) }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                >
                    @if($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    @if($warning)
                        <i class="bi bi-exclamation-lg ui-tab-warn-icon" title="Brakuje danych w tej sekcji" aria-hidden="true"></i>
                    @endif
                    <span>{{ $label }}</span>
                    @if($count !== null && $count > 0)
                        <span class="badge badge-accent ms-1">{{ $count }}</span>
                    @endif
                </button>
            @elseif($href)
                <a
                    href="{{ $href }}"
                    class="{{ trim($navLinkClass) }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                >
                    @if($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    @if($warning)
                        <i class="bi bi-exclamation-lg ui-tab-warn-icon" title="Brakuje danych w tej sekcji" aria-hidden="true"></i>
                    @endif
                    <span>{{ $label }}</span>
                    @if($count !== null && $count > 0)
                        <span class="badge badge-accent ms-1">{{ $count }}</span>
                    @endif
                </a>
            @else
                <button
                    type="button"
                    class="{{ trim($navLinkClass) }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                >
                    @if($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    @if($warning)
                        <i class="bi bi-exclamation-lg ui-tab-warn-icon" title="Brakuje danych w tej sekcji" aria-hidden="true"></i>
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
