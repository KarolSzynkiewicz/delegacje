@php
    $away = ! in_array($locationStatus['state'] ?? null, [
        \App\Enums\EmployeeLocationState::IN_BASE,
        \App\Enums\EmployeeLocationState::IN_TRANSIT,
    ], true);
    $warn = (bool) ($locationStatus['has_assignment_overlap'] ?? false);
    $whereRows = [
        ['label' => 'Dom', 'items' => $locationStatus['accommodation_names'] ?? []],
        ['label' => 'Auto', 'items' => $locationStatus['vehicle_labels'] ?? []],
        ['label' => 'Projekt', 'items' => $locationStatus['project_names'] ?? []],
    ];
@endphp

@if($away)
    <div class="emp-kv">
        @foreach($whereRows as $whereRow)
            <div class="emp-kv__row">
                <span class="emp-kv__label">{{ $whereRow['label'] }}</span>
                <span class="emp-kv__value">
                    @if(! empty($whereRow['items']))
                        @foreach($whereRow['items'] as $whereItem)
                            <x-ui.badge :variant="$warn ? 'warning' : 'info'" :title="$whereItem">
                                {{ \Illuminate\Support\Str::limit($whereItem, 12, '…') }}
                            </x-ui.badge>
                        @endforeach
                    @else
                        <x-ui.badge variant="danger">Brak</x-ui.badge>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
@else
    <span class="text-muted">—</span>
@endif
