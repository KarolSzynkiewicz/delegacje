@props([
    'consumable',
    'empty' => 'Brak rozchodów z magazynu.',
])

@php
    $movements = $consumable->relationLoaded('equipmentConsumptions')
        ? $consumable->equipmentConsumptions
        : $consumable->equipmentConsumptions()
            ->with(['equipment', 'variant', 'warehouse.location', 'creator'])
            ->latest('id')
            ->limit(50)
            ->get();
@endphp

<x-ui.card label="Rozchód z magazynu" {{ $attributes }}>
    @if($movements->isNotEmpty())
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pozycja</th>
                        <th>Magazyn</th>
                        <th class="text-end">Ilość</th>
                        <th>Kto</th>
                        <th>Uwagi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $movement)
                        <tr>
                            <td class="text-nowrap">{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($movement->equipment_id)
                                    <a href="{{ route('equipment.show', ['equipment' => $movement->equipment_id, 'warehouse_id' => $movement->warehouse_id]) }}" class="text-decoration-none fw-semibold">
                                        {{ $movement->equipment?->name ?? '—' }}
                                    </a>
                                @else
                                    <span class="fw-semibold">{{ $movement->equipment?->name ?? '—' }}</span>
                                @endif
                                <div class="small text-muted">{{ $movement->variant?->kind_label ?? '—' }}</div>
                            </td>
                            <td>{{ $movement->warehouse?->display_name ?? '—' }}</td>
                            <td class="text-end" style="font-variant-numeric:tabular-nums;">−{{ $movement->quantity }}</td>
                            <td>{{ $movement->creator?->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $movement->notes ? \Illuminate\Support\Str::limit($movement->notes, 60) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted mb-0 small">{{ $empty }}</p>
    @endif
</x-ui.card>
