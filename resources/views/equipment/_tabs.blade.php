@php
    $activeTab = $activeTab ?? 'stock';
    $warehouse = $warehouse ?? null;
    $tabsForComponent = [
        'stock' => [
            'label' => 'Stan magazynu',
            'icon' => 'bi bi-box-seam',
            'href' => route('equipment.tab.stock', $warehouse ? ['warehouse_id' => $warehouse->id] : []),
        ],
        'issues' => [
            'label' => 'Wydania',
            'icon' => 'bi bi-box-arrow-up',
            'href' => route('equipment.tab.issues'),
        ],
    ];
@endphp
<x-ui.tabs
    :tabs="$tabsForComponent"
    :activeTab="$activeTab"
    id="warehouseTabs"
/>
