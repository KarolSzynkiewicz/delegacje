@php
    $activeTab = $activeTab ?? 'stock';
    $warehouse = $warehouse ?? null;
    $pendingOrdersCount = $warehouse
        ? \App\Models\WarehouseDispatch::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('status', \App\Models\WarehouseDispatch::STATUS_RESERVED)
            ->count()
        : 0;
    $tabsForComponent = [
        'stock' => [
            'label' => 'Asortyment',
            'icon' => 'bi bi-box-seam',
            'href' => route('equipment.tab.stock', $warehouse ? ['warehouse_id' => $warehouse->id] : []),
        ],
        'orders' => [
            'label' => 'Zlecenia',
            'icon' => 'bi bi-clipboard-check',
            'href' => route('equipment.tab.orders', $warehouse ? ['warehouse_id' => $warehouse->id] : []),
            'count' => $pendingOrdersCount,
        ],
        'issues' => [
            'label' => 'Wydane',
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
