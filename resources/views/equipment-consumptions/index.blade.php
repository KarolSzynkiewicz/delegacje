<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Rozchód z magazynu">
            <x-slot name="right">
                @include('equipment._warehouse-switcher', [
                    'warehouses' => $warehouses,
                    'current' => $warehouse,
                    'routeName' => 'equipment-consumptions.index',
                ])
                <x-ui.button
                    variant="primary"
                    href="{{ route('equipment-consumptions.create', ['warehouse_id' => $warehouse->id]) }}"
                    action="create"
                >
                    Nowy rozchód
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        @if($movements->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Pozycja</th>
                            <th>Ilość</th>
                            <th>Przypisane do</th>
                            <th>Notatka</th>
                            <th>Kto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $movement->variant?->display_name ?? $movement->equipment?->name }}</td>
                                <td>−{{ $movement->quantity }}</td>
                                <td>
                                    @if($movement->employee)
                                        <x-employee-cell :employee="$movement->employee" />
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $movement->notes ?: '—' }}</td>
                                <td>{{ $movement->creator?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())
                <div class="mt-3">
                    <x-ui.pagination :paginator="$movements" />
                </div>
            @endif
        @else
            <x-ui.empty-state
                icon="inbox"
                message="Brak rozchodów w tym magazynie."
            >
                <x-ui.button
                    variant="primary"
                    href="{{ route('equipment-consumptions.create', ['warehouse_id' => $warehouse->id]) }}"
                    action="create"
                >
                    Zaksięguj pierwszy rozchód
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
