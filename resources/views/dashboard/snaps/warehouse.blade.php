@php $wh = $snaps['warehouse']; @endphp

<x-dashboard.snap
    kicker="Magazyn"
    title="Magazyny i asortyment"
    caption="Karty magazynów (siedziba vs teren) i stany: w magazynie / zarezerwowane / w innych / do zwrotu. To te same kafelki co na /equipment."
    :href="Route::has('equipment.tab.stock') ? route('equipment.tab.stock') : null"
    tall
>
    @include('equipment._warehouse-cards', [
        'warehouses' => $wh['warehouses'],
        'current' => $wh['current'],
        'counts' => $wh['counts'],
        'routeName' => 'equipment.tab.stock',
        'keep' => [],
    ])

    <div class="eq-stock-cards mt-3">
        @foreach($wh['items'] as $item)
            @include('equipment._stock-item-card', [
                'item' => $item,
                'warehouse' => $wh['current'],
            ])
        @endforeach
    </div>
</x-dashboard.snap>
